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
    "sizes": [256],
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
        old_cores = OPTIONS["cores"]
        OPTIONS["cores"] = cores if cores else cpu_count()
        diff = OPTIONS["cores"] - old_cores
        if diff > 0:
            add_threads(diff)
        else:
            pass  # set exit flags?
    return Response()


@APP.get("/thumbnail")
def thumbnail(file_id: int, user_id: str = ""):
    INPUT_QUEUE.put((file_id, user_id))
    return Response()


@APP.get("/queue")
def queue_list():
    return {"nItems": INPUT_QUEUE.qsize, "nThreads": len(THREADS)}


def request_file(user_id: str, file_id: int) -> bytes:
    try:
        response = request(
            "GET",
            argv[1],
            params={
                "user_id": user_id,
                "file_id": file_id,
            },
            timeout=30,
        )
    except Exception as e:  # pylint: disable=broad-except
        print(e)
        return b""
    data = response.content if response.ok else b""
    response.close()
    return data


def save_thumbnail(user_id: str, file_id: int, data: bytes) -> None:
    request(
        "POST",
        argv[2],
        params={
            "user_id": user_id,
            "file_id": file_id,
        },
        files={"data": data},
        timeout=30,
    )


def background_thread():
    print("bt: started")
    while True:
        file_id, user_id = INPUT_QUEUE.get(timeout=None)
        print(f"bt: processing: {user_id}:{file_id}")
        data = request_file(user_id, file_id)
        if data:
            im = Image.open(BytesIO(data))
            for size in OPTIONS["sizes"]:
                im.thumbnail((size, size))
                if im.mode != "RGB":
                    im = im.convert("RGB")
                out = BytesIO()
                im.save(out, format=OPTIONS["output_format"], quality=OPTIONS["quality"])
                out.seek(0)
                save_thumbnail(user_id, file_id, out.read())


def add_threads(count: int) -> None:
    for _ in range(count):
        t = Thread(target=background_thread)
        t.start()
        THREADS.append(t)


@APP.on_event("startup")
def start_threads():
    print("request_file_endpoint: ", argv[1])
    print("store_thumbnail_endpoint: ", argv[2])
    add_threads(OPTIONS["cores"])


@APP.on_event("shutdown")
def stop_threads():
    print("shutdown")
    for t in THREADS:
        t.join()


if __name__ == "__main__":
    uvicorn.run("main:APP", port=9001)
