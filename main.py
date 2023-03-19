""" Example of multithreading thumbnailer generator in Python """

from io import BytesIO
from multiprocessing import cpu_count
from queue import Queue
from sys import argv
from threading import Thread
from typing import List, Union

import uvicorn
from fastapi import FastAPI
from PIL import Image
from requests import Response, request

APP = FastAPI()
INPUT_QUEUE: Queue = Queue()
OPTIONS = {
    "sizes": [128, 256],
    "output_format": "JPEG",
    "quality": 70,
    "cores": 2,
}
THREADS: List[Thread] = []


@APP.post("/options")
def options(
    sizes: List[int],
    output_format: Union[str, None] = None,
    quality: Union[int, None] = None,
    cores: Union[int, None] = None,
):
    OPTIONS["sizes"] = sizes
    if output_format:
        OPTIONS["output_format"] = output_format
    if quality is not None:
        OPTIONS["quality"] = quality
    if cores is not None:
        OPTIONS["cores"] = cores if cores else cpu_count()
    return Response()


@APP.get("/thumbnail")
def thumbnail(file_id: int, user_id: str = ""):
    INPUT_QUEUE.put((file_id, user_id))
    return Response()


@APP.get("/queue")
def queue_list():
    return {"nItems": INPUT_QUEUE.qsize}


def request_file(user_id: str, file_id: int) -> bytes:
    response = request(
        "GET",
        argv[1],
        params={
            "user_id": user_id,
            "file_id": file_id,
        },  # type: ignore
        timeout=30,
    )
    return response.content if response.ok else b""


def save_thumbnail(user_id: str, file_id: int, data: bytes) -> None:
    request(
        "POST",
        argv[2],
        params={
            "user_id": user_id,
            "file_id": file_id,
        },  # type: ignore
        files={"data": data},
        timeout=30,
    )


def background_thread():
    print("bt: started")
    while True:
        user_id, file_id = INPUT_QUEUE.get(timeout=None)
        print(f"bt: processing: {user_id}:{file_id}")
        data = request_file(user_id, file_id)
        if data:
            im = Image.open(BytesIO(data))
            im.thumbnail((64, 64))
            if im.mode != "RGB":
                im = im.convert("RGB")
            out = BytesIO()
            im.save(out, format=OPTIONS["format"], quality=OPTIONS["quality"])
            out.seek(0)
            save_thumbnail(user_id, file_id, out.read())


@APP.on_event("startup")
def start_threads():
    print("request_file_endpoint: ", argv[1])
    print("store_thumbnail_endpoint: ", argv[2])
    for _ in range(OPTIONS["cores"]):
        t = Thread(target=background_thread)
        t.start()
        THREADS.append(t)


@APP.on_event("shutdown")
def stop_threads():
    print("shutdown")
    for t in THREADS:
        t.join()


if __name__ == "__main__":
    uvicorn.run("main:APP", port=9001)
