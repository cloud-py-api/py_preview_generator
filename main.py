from sys import argv
from threading import Thread
from io import BytesIO
from time import sleep

from requests import request, Response
import uvicorn
from fastapi import FastAPI
from PIL import Image

APP = FastAPI()

INPUT_QUEUE: list[tuple] = []


@APP.get("/generate_thumbnail")
def generate_thumbnail(file_id: int, user_id: str = ""):
    INPUT_QUEUE.append((user_id, file_id))
    return Response()


@APP.get("/queue_list")
def queue_list():
    return {
        "nItems": len(INPUT_QUEUE),
        "Items": [i for i in INPUT_QUEUE],
    }


def request_file(user_id: str, file_id: int) -> bytes:
    response = request(
        "GET",
        argv[1],
        params={
            "user_id": user_id,
            "file_id": file_id,
        },
    )
    return response.content if response.ok else b""


def save_thumbnail(user_id: str, file_id: int, data: bytes) -> None:
    response = request(
        "POST",
        argv[2],
        params={
            "user_id": user_id,
            "file_id": file_id,
        },
        files={"data": data},
    )
    print(response.ok)


def background_thread():
    print("bt: started")
    while True:
        if INPUT_QUEUE:
            e = INPUT_QUEUE.pop()
            print(f"bt: processing: {e[0]}:{e[1]}")
            data = request_file(e[0], e[1])
            if data:
                im = Image.open(BytesIO(data))
                im.thumbnail((64, 64))
                if im.mode != "RGB":
                    im = im.convert("RGB")
                out = BytesIO()
                im.save(out, format="JPEG")
                out.seek(0)
                save_thumbnail(e[0], e[1], out.read())
        else:
            sleep(0.1)


@APP.on_event("startup")
async def startup_event():
    print("request_file_endpoint: ", argv[1])
    print("store_thumbnail_endpoint: ", argv[2])
    b_thread = Thread(
        target=background_thread,
        daemon=False,
    )
    b_thread.start()


if __name__ == '__main__':
    uvicorn.run("main:APP")
