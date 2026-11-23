#!/usr/bin/env python3
"""
Prueba de carga del mundo abierto: N jugadores caminando a la vez.
    python3 tests/carga/mundo.py http://127.0.0.1:8081/DraftDerMauer 40 20
Mide cuántos pedidos por segundo atiende el servidor y cuánto tarda cada uno.
"""
import sys, re, time, random, threading, statistics
import requests

base = sys.argv[1]
n = int(sys.argv[2]) if len(sys.argv) > 2 else 20
segundos = int(sys.argv[3]) if len(sys.argv) > 3 else 15
lat, errores, corregidos, total = [], [0], [0], [0]
lock = threading.Lock()

def jugador(i):
    s = requests.Session()
    nombre = f"carga{i:03d}"
    time.sleep(i * 0.12)   # entran de a poco: el login con Argon2id es pesado a propósito
    try:
        html = s.get(base + "/login", timeout=20).text
        t = re.search(r'name="csrf_token" value="([^"]+)"', html).group(1)
        r = s.post(base + "/login", data={"csrf_token": t, "usuario": nombre, "password": "Prueba2026!", "recaptcha_token": ""}, allow_redirects=False, timeout=30)
        if r.status_code != 302:
            with lock: errores[0] += 1
            return
        html = s.get(base + "/mundo", timeout=20).text
        csrf = re.search(r'data-csrf="([^"]+)"', html).group(1)
        h = {"X-CSRF-Token": csrf}
        s.post(base + "/api-mundo", data={"accion": "bando", "bando": "oeste" if i % 2 else "este"}, headers=h, timeout=20)
        d = s.post(base + "/api-mundo", data={"accion": "mover", "x": 0, "y": 0, "dir": 0}, headers=h, timeout=20).json()
        x, y = d["posicion"]["x"], d["posicion"]["y"]
        lado = 1 if i % 2 else -1
        fin = time.time() + segundos
        time.sleep(random.random() * 0.5)
        while time.time() < fin:
            x = min(max(x + random.randint(-12, 12), 60), 1100) if lado == 1 else min(max(x + random.randint(-12, 12), 1300), 2300)
            y = min(max(y + random.randint(-12, 12), 60), 1300)
            t0 = time.time()
            try:
                r = s.post(base + "/api-mundo", data={"accion": "mover", "x": x, "y": y, "dir": random.randint(0, 3)}, headers=h, timeout=20)
                ok = r.status_code == 200
                j = r.json() if ok else {}
            except Exception:
                ok, j = False, {}
            dt = time.time() - t0
            with lock:
                total[0] += 1
                lat.append(dt)
                if not ok: errores[0] += 1
                elif j.get("posicion", {}).get("corregido"): corregidos[0] += 1
            if ok and j.get("posicion"):
                x, y = j["posicion"]["x"], j["posicion"]["y"]
            time.sleep(max(0, 0.2 - dt))
    except Exception as e:
        with lock: errores[0] += 1
        print("fallo jugador", i, repr(e)[:100])

hilos = [threading.Thread(target=jugador, args=(i,)) for i in range(1, n + 1)]
t0 = time.time()
for h in hilos: h.start()
for h in hilos: h.join()
dur = time.time() - t0
if lat:
    lat.sort()
    p = lambda q: lat[min(len(lat) - 1, int(len(lat) * q))] * 1000
    print(f"jugadores={n} pedidos={total[0]} errores={errores[0]} corregidos={corregidos[0]} duracion={dur:.0f}s")
    print(f"pedidos/s={total[0] / max(1, segundos):.0f}  latencia ms: mediana={p(.5):.0f} p95={p(.95):.0f} max={lat[-1]*1000:.0f}")
