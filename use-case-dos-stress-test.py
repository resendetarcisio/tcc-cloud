import argparse
import asyncio
from aiohttp import ClientSession
from threading import Thread, Lock
from datetime import datetime

i = 0
last_i = 0
current_time = datetime.now()
counter_lock = Lock()

async def fetch_url(session: ClientSession, url: str):
    global i, last_i, current_time

    try:
        async with session.get(url) as response:
            print(f"Status: {response.status}")
            with counter_lock:
                i += 1

                if i % 1000 == 0:
                    segundos = (datetime.now() - current_time).seconds
                    iteracoes = i - last_i
                    if segundos > 0:
                        velocidade = iteracoes / segundos
                        print(f"{velocidade:.2f} requisições/s")
                    last_i = i
                    current_time = datetime.now()
    except Exception as e:
        print(f"Erro: {e}")

async def session_requests(url: str, requests_per_thread: int = 1000):
    async with ClientSession() as session:
        tarefas = [
            asyncio.create_task(fetch_url(session, url))
            for _ in range(requests_per_thread)
        ]
        await asyncio.gather(*tarefas)

def inicia_thread(url: str, requests_per_thread: int):
    asyncio.run(session_requests(url, requests_per_thread))

def main():
    parser = argparse.ArgumentParser(description="Teste de carga")
    parser.add_argument("--url", required=True,
                        help="Endereço base para as requisições (ex: http://example.com)")
    parser.add_argument("--threads", type=int, default=10,
                        help="Quantidade de threads a serem usadas")
    parser.add_argument("--requests", type=int, default=10000,
                        help="Quantidade de requisições por thread")
    args = parser.parse_args()

    print(f"Iniciando teste de carga em {args.url} com {args.threads} threads, "
          f"{args.requests} requisições por thread.")

    threads = []
    for _ in range(args.threads):
        t = Thread(target=inicia_thread, args=(args.url, args.requests))
        t.start()
        threads.append(t)

    for t in threads:
        t.join()

if __name__ == "__main__":
    main()
