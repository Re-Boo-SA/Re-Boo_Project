#!/usr/bin/env python3
"""
Formas del personaje personalizable del mundo abierto. Requiere Python 3 + Pillow.
    python herramientas/arte/generar_apariencia.py
Dibuja con COLORES MARCADORES (rojo puro = piel, verde = pelo, azul = abrigo, cian = detalle,
amarillo = pantalón, magenta = sombrero, naranja = bufanda). El navegador reemplaza cada marcador
(y sus sombras) por el color que eligió el jugador (js/apariencia.js).
Salidas en Capa_Presentacion/img/mundo/apariencia/:
  plantilla.png   75 hojas (5 pelos x 5 sombreros x 3 abrigos), 15 por fila, cada una de 3 x 6 cuadros de 32 x 48
  anteojos.png, bigote.png, barba.png, bufanda.png   capas que van encima (una hoja de 3 x 6 cada una)
"""
import os
from PIL import Image, ImageDraw

RAIZ = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
OUT = os.path.join(RAIZ, 'Capa_Presentacion', 'img', 'mundo', 'apariencia')
os.makedirs(OUT, exist_ok=True)
PIEL, PELO, AB, AB2, PANT, SOMB, BUF = (255, 0, 0), (0, 255, 0), (0, 0, 255), (0, 255, 255), (255, 255, 0), (255, 0, 255), (254, 127, 0)
ZAP, OJO = (20, 22, 27), (20, 20, 24)
def osc(c, k=0.8): return tuple(int(v * k) for v in c)

PELOS = ['corto', 'largo', 'rapado', 'peinado', 'rodete']
SOMBREROS = ['ninguno', 'gorra', 'sombrero', 'boina', 'panuelo']
ABRIGOS = ['sobretodo', 'largo', 'campera']
VISTAS = ['abajo', 'izq', 'der', 'arriba']
GESTOS = ['saludar_a', 'saludar_b', 'aplaudir_cerrado', 'sentado', 'encogerse', 'aplaudir_abierto']


def cuerpo(d, vista, paso, abrigo):
    """Piernas, abrigo, brazos y cara de un cuadro de caminar. Devuelve el desplazamiento vertical de la cabeza (0)."""
    fin = {'sobretodo': 16, 'largo': 19, 'campera': 14}[abrigo]
    if vista in ('abajo', 'arriba'):
        l1, l2 = (1 if paso == 1 else 0), (1 if paso == -1 else 0)
        d.rectangle([5, 15, 7, 21 + l1], fill=PANT); d.rectangle([5, 21 + l1, 7, 22 + l1], fill=ZAP)
        d.rectangle([9, 15, 11, 21 + l2], fill=PANT); d.rectangle([9, 21 + l2, 11, 22 + l2], fill=ZAP)
        d.rectangle([2, 8 + l2, 3, 14], fill=AB2); d.rectangle([12, 8 + l1, 13, 14], fill=AB2)
        d.rectangle([2, 14, 3, 15], fill=PIEL); d.rectangle([12, 14, 13, 15], fill=PIEL)
        d.rectangle([4, 8, 11, fin], fill=AB); d.rectangle([4, fin - 2, 11, fin], fill=AB2)
        if vista == 'abajo':
            d.line([(8, 9), (8, fin)], fill=AB2)
            if abrigo == 'campera': d.rectangle([7, 8, 8, 9], fill=(233, 228, 204))   # cuello de la camisa
        d.rectangle([5, 1, 10, 7], fill=PIEL)
        if vista == 'abajo':
            d.point((6, 5), fill=OJO); d.point((9, 5), fill=OJO)
    else:
        f = 1 if vista == 'der' else -1
        d.rectangle([7 + paso * f, 15, 9 + paso * f, 22], fill=PANT); d.rectangle([7 + paso * f, 22, 10 + paso * f, 22], fill=ZAP)
        d.rectangle([7 - paso * f, 15, 9 - paso * f, 22], fill=osc(PANT, 0.85)); d.rectangle([7 - paso * f, 22, 10 - paso * f, 22], fill=ZAP)
        d.rectangle([5, 8, 10, fin], fill=AB); d.rectangle([5, fin - 2, 10, fin], fill=AB2)
        d.rectangle([7 - paso * f, 8, 8 - paso * f, 14], fill=AB2); d.rectangle([7 - paso * f, 14, 8 - paso * f, 15], fill=PIEL)
        d.rectangle([5, 1, 10, 7], fill=PIEL)
        d.point((9, 5) if vista == 'der' else (6, 5), fill=OJO)
    return 0


def cuerpo_gesto(d, gesto, abrigo):
    """Lo mismo para los gestos (de frente). Devuelve el desplazamiento vertical de la cabeza."""
    sentado = gesto == 'sentado'
    dy = 5 if sentado else 0
    fin = {'sobretodo': 16, 'largo': 19, 'campera': 14}[abrigo]
    if sentado:
        d.rectangle([4, 20, 7, 22], fill=PANT); d.rectangle([8, 20, 11, 22], fill=osc(PANT, 0.85))
        d.rectangle([4, 22, 5, 23], fill=ZAP); d.rectangle([10, 22, 11, 23], fill=ZAP)
        d.rectangle([4, 8 + dy, 11, 20], fill=AB); d.rectangle([4, 18, 11, 20], fill=AB2)
    else:
        d.rectangle([5, 15, 7, 21], fill=PANT); d.rectangle([5, 21, 7, 22], fill=ZAP)
        d.rectangle([9, 15, 11, 21], fill=PANT); d.rectangle([9, 21, 11, 22], fill=ZAP)
        d.rectangle([4, 8, 11, fin], fill=AB); d.rectangle([4, fin - 2, 11, fin], fill=AB2)
    brazos = {
        'saludar_a': [(2, 8, 3, 14, 2, 14, 3, 15), (12, 2, 13, 8, 12, 0, 13, 1)],
        'saludar_b': [(2, 8, 3, 14, 2, 14, 3, 15), (13, 2, 14, 8, 14, 0, 15, 1)],
        'aplaudir_cerrado': [(3, 9, 5, 12, 6, 11, 7, 13), (10, 9, 12, 12, 8, 11, 9, 13)],
        'aplaudir_abierto': [(3, 9, 4, 12, 5, 11, 6, 12), (11, 9, 12, 12, 9, 11, 10, 12)],
        'encogerse': [(1, 9, 3, 10, 0, 8, 1, 9), (12, 9, 14, 10, 14, 8, 15, 9)],
        'sentado': [(3, 13, 4, 18, 3, 19, 4, 19), (11, 13, 12, 18, 11, 19, 12, 19)],
    }[gesto]
    for (x0, y0, x1, y1, hx0, hy0, hx1, hy1) in brazos:
        e = dy if sentado else 0
        d.rectangle([x0, y0 + e, x1, y1 + e], fill=AB2); d.rectangle([hx0, hy0 + e, hx1, hy1 + e], fill=PIEL)
    y = dy + (1 if gesto == 'encogerse' else 0)
    d.rectangle([5, 1 + y, 10, 7 + y], fill=PIEL)
    d.point((6, 5 + y), fill=OJO); d.point((9, 5 + y), fill=OJO)
    if gesto in ('saludar_a', 'saludar_b', 'aplaudir_abierto'):
        d.line([(7, 6 + y), (8, 6 + y)], fill=osc(PIEL, 0.7))
    return y


def pelo(d, vista, y, estilo):
    R = lambda x0, y0, x1, y1, c=PELO: d.rectangle([x0, y0 + y, x1, y1 + y], fill=c)
    if estilo == 'rapado':
        if vista == 'arriba': R(5, 1, 10, 3)
        else: R(5, 1, 10, 1)
        return
    base = 0 if estilo == 'peinado' else 1
    if vista == 'abajo': R(5, base, 10, 3)
    elif vista == 'arriba': R(5, base, 10, 7)
    elif vista == 'der': R(5, base, 10, 3); R(5, 3, 6, 7)
    else: R(5, base, 10, 3); R(9, 3, 10, 7)
    if estilo == 'peinado' and vista == 'abajo': R(9, 1, 10, 1, osc(PELO, 0.7))   # la raya al costado
    if estilo == 'largo':
        if vista == 'abajo': R(4, 2, 4, 9); R(11, 2, 11, 9)
        elif vista == 'arriba': R(5, 7, 10, 9)
        elif vista == 'der': R(4, 3, 6, 9)
        else: R(9, 3, 11, 9)
    if estilo == 'rodete':
        if vista in ('abajo', 'arriba'): R(6, 0, 9, 0); R(7, 0, 8, 0, osc(PELO, 0.8))
        elif vista == 'der': R(3, 1, 4, 3)
        else: R(11, 1, 12, 3)


def sombrero(d, vista, y, tipo):
    R = lambda x0, y0, x1, y1, c=SOMB: d.rectangle([x0, y0 + y, x1, y1 + y], fill=c)
    if tipo == 'gorra':
        R(5, 0, 10, 2)
        if vista == 'abajo': R(4, 2, 11, 2, osc(SOMB))
        elif vista == 'der': R(9, 2, 12, 2, osc(SOMB))
        elif vista == 'izq': R(3, 2, 6, 2, osc(SOMB))
    elif tipo == 'sombrero':
        R(5, 0, 10, 1); R(3, 2, 12, 2, osc(SOMB)); R(5, 1, 10, 1, osc(SOMB, 0.4))
    elif tipo == 'boina':
        R(4, 0, 11, 1); R(5, 2, 10, 2, osc(SOMB))
        if vista == 'der': R(11, 1, 12, 1)
        elif vista == 'izq': R(3, 1, 4, 1)
    elif tipo == 'panuelo':
        R(5, 0, 10, 2); R(4, 2, 4, 3); R(11, 2, 11, 3)
        if vista == 'der': R(3, 3, 4, 5, osc(SOMB))
        elif vista == 'izq': R(11, 3, 12, 5, osc(SOMB))
        elif vista == 'arriba': R(6, 3, 9, 5, osc(SOMB))


def accesorio(d, vista, y, tipo):
    R = lambda x0, y0, x1, y1, c: d.rectangle([x0, y0 + y, x1, y1 + y], fill=c)
    if tipo == 'anteojos':
        marco, vidrio = (34, 34, 40), (130, 160, 190)
        if vista == 'abajo': R(5, 4, 10, 4, marco); R(5, 5, 6, 5, vidrio); R(9, 5, 10, 5, vidrio); R(7, 5, 8, 5, marco)
        elif vista == 'der': R(8, 4, 10, 4, marco); R(9, 5, 10, 5, vidrio)
        elif vista == 'izq': R(5, 4, 7, 4, marco); R(5, 5, 6, 5, vidrio)
    elif tipo == 'bigote':
        if vista == 'abajo': R(6, 6, 9, 6, PELO)
        elif vista == 'der': R(9, 6, 10, 6, PELO)
        elif vista == 'izq': R(5, 6, 6, 6, PELO)
    elif tipo == 'barba':
        if vista == 'abajo': R(5, 6, 10, 7, PELO); R(7, 6, 8, 6, osc(PELO, 0.7))
        elif vista == 'der': R(7, 6, 10, 7, PELO)
        elif vista == 'izq': R(5, 6, 8, 7, PELO)
    elif tipo == 'bufanda':
        if vista == 'abajo': R(5, 8, 10, 8, BUF); R(9, 9, 9, 11, osc(BUF, 0.85))
        elif vista == 'arriba': R(5, 8, 10, 8, BUF); R(6, 9, 6, 11, osc(BUF, 0.85))
        elif vista == 'der': R(5, 8, 9, 8, BUF); R(5, 9, 5, 11, osc(BUF, 0.85))
        else: R(6, 8, 10, 8, BUF); R(10, 9, 10, 11, osc(BUF, 0.85))


def hoja(dibujar):
    """Arma una hoja de 3 x 6 cuadros llamando a dibujar(d, vista, paso, gesto) en cada uno."""
    h = Image.new('RGBA', (96, 288), (0, 0, 0, 0))
    for fila, vista in enumerate(VISTAS):
        for c in range(3):
            im = Image.new('RGBA', (16, 24), (0, 0, 0, 0))
            dibujar(ImageDraw.Draw(im), vista, 0 if c == 0 else (1 if c == 1 else -1), None)
            h.paste(im.resize((32, 48), Image.NEAREST), (c * 32, fila * 48))
    for i, gesto in enumerate(GESTOS):
        im = Image.new('RGBA', (16, 24), (0, 0, 0, 0))
        dibujar(ImageDraw.Draw(im), 'abajo', 0, gesto)
        h.paste(im.resize((32, 48), Image.NEAREST), ((i % 3) * 32, (4 + i // 3) * 48))
    return h


def variante(estilo, tipo, abrigo):
    def dib(d, vista, paso, gesto):
        y = cuerpo_gesto(d, gesto, abrigo) if gesto else cuerpo(d, vista, paso, abrigo)
        pelo(d, vista, y, estilo)
        sombrero(d, vista, y, tipo)
    return hoja(dib)


def capa(tipo):
    def dib(d, vista, paso, gesto):
        y = 0 if not gesto else (5 if gesto == 'sentado' else (1 if gesto == 'encogerse' else 0))
        accesorio(d, vista, y, tipo)
    return hoja(dib)


if __name__ == '__main__':
    atlas = Image.new('RGBA', (15 * 96, 5 * 288), (0, 0, 0, 0))
    n = 0
    for ip, estilo in enumerate(PELOS):
        for isom, tipo in enumerate(SOMBREROS):
            for ia, abrigo in enumerate(ABRIGOS):
                v = (ip * len(SOMBREROS) + isom) * len(ABRIGOS) + ia
                atlas.paste(variante(estilo, tipo, abrigo), ((v % 15) * 96, (v // 15) * 288)); n += 1
    atlas.save(os.path.join(OUT, 'plantilla.png'), optimize=True)
    for t in ('anteojos', 'bigote', 'barba', 'bufanda'):
        capa(t).save(os.path.join(OUT, t + '.png'), optimize=True)
    print('Apariencia: %d variantes en la plantilla y 4 capas de accesorios.' % n)
