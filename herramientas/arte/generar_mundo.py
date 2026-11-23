#!/usr/bin/env python3
"""
Genera el arte y el mapa del mundo abierto (Berlín) de forma procedural.
Requiere Python 3 + Pillow. NO hace falta para jugar: los archivos generados ya
están en el proyecto. Se corre solo para regenerar o cambiar el mapa:

    python herramientas/arte/generar_mundo.py

Salidas:
  datos/mundo/berlin.json                      mapa en formato Tiled (lo lee el servidor Y el navegador)
  Capa_Presentacion/img/mundo/tileset.png      mosaicos de 40x40
  Capa_Presentacion/img/mundo/edificios/*.png  un sprite por edificio
  Capa_Presentacion/img/mundo/personaje_*.png  hojas de sprites (3 cuadros x 4 direcciones)
El mapa también se puede abrir y editar con Tiled (mapeditor.org).
"""
import json, os, random, sys
from PIL import Image, ImageDraw, ImageFont

RAIZ = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
OUT_IMG = os.path.join(RAIZ, 'Capa_Presentacion', 'img', 'mundo')
OUT_ED = os.path.join(OUT_IMG, 'edificios')
OUT_DATOS = os.path.join(RAIZ, 'datos', 'mundo')
for d in (OUT_IMG, OUT_ED, OUT_DATOS):
    os.makedirs(d, exist_ok=True)

T = 40            # tamaño del mosaico
W, H = 60, 35     # mapa: 2400 x 1400
rnd = random.Random(1961)


def rgb(h):
    h = h.lstrip('#')
    return tuple(int(h[i:i + 2], 16) for i in (0, 2, 4))


def jitter(c, a):
    return tuple(max(0, min(255, v + rnd.randint(-a, a))) for v in c)


def blocks(img, base, var, size=2):
    """Rellena con bloques de 'size' px y una variación leve (aspecto pixel-art)."""
    px = img.load()
    for y in range(0, img.height, size):
        for x in range(0, img.width, size):
            c = jitter(base, var)
            for dy in range(size):
                for dx in range(size):
                    if x + dx < img.width and y + dy < img.height:
                        px[x + dx, y + dy] = c + (255,)


# ---------------------------------------------------------------- MOSAICOS
def t_pasto(base, var, motas):
    im = Image.new('RGBA', (T, T)); blocks(im, base, var)
    d = ImageDraw.Draw(im)
    for _ in range(motas):
        x, y = rnd.randrange(0, T - 2), rnd.randrange(0, T - 3)
        d.line([(x, y + 2), (x, y)], fill=jitter(tuple(int(v * 0.8) for v in base), 8))
        d.point((x + 1, y + 1), fill=jitter(tuple(min(255, int(v * 1.2)) for v in base), 8))
    return im


def t_adoquin(base, linea):
    im = Image.new('RGBA', (T, T)); blocks(im, base, 7, 2)
    d = ImageDraw.Draw(im)
    for fila in range(0, T, 8):
        d.line([(0, fila), (T, fila)], fill=linea)
        off = 0 if (fila // 8) % 2 == 0 else 6
        for x in range(off, T, 12):
            d.line([(x, fila), (x, fila + 8)], fill=linea)
    return im


def t_asfalto(base):
    im = Image.new('RGBA', (T, T)); blocks(im, base, 6, 2)
    d = ImageDraw.Draw(im)
    for _ in range(14):
        d.point((rnd.randrange(T), rnd.randrange(T)), fill=jitter((20, 20, 24), 6))
    return im


def t_vereda(base, linea):
    im = Image.new('RGBA', (T, T)); blocks(im, base, 5, 2)
    d = ImageDraw.Draw(im)
    d.rectangle([0, 0, T - 1, T - 1], outline=linea)
    d.line([(T // 2, 0), (T // 2, T)], fill=linea)
    d.line([(0, T // 2), (T, T // 2)], fill=linea)
    return im


def t_panel(base, linea):
    im = Image.new('RGBA', (T, T)); blocks(im, base, 4, 2)
    d = ImageDraw.Draw(im)
    d.rectangle([0, 0, T - 1, T - 1], outline=linea)
    d.rectangle([2, 2, T - 3, T - 3], outline=jitter(linea, 3))
    return im


def t_muro():
    im = Image.new('RGBA', (T, T)); blocks(im, rgb('#8a8d94'), 6, 2)
    d = ImageDraw.Draw(im)
    d.rectangle([0, 0, 5, T], fill=rgb('#a9acb3'))          # luz del lado oeste
    d.rectangle([T - 4, 0, T, T], fill=rgb('#62656c'))      # sombra del lado este
    for y in (0, 19, 39):
        d.line([(0, y), (T, y)], fill=rgb('#5d6068'))
    # grafitis sueltos
    for _ in range(3):
        x, y = rnd.randrange(8, 30), rnd.randrange(3, 34)
        d.rectangle([x, y, x + rnd.randint(2, 6), y + 2], fill=rnd.choice([rgb('#d94a58'), rgb('#e6c640'), rgb('#4a8cff'), rgb('#e9e9e9')]))
    return im


def t_franja():
    im = Image.new('RGBA', (T, T)); blocks(im, rgb('#b8a77c'), 9, 2)
    d = ImageDraw.Draw(im)
    for _ in range(18):
        d.point((rnd.randrange(T), rnd.randrange(T)), fill=jitter(rgb('#6f6446'), 8))
    d.line([(0, 12), (T, 12)], fill=rgb('#a39370')); d.line([(0, 28), (T, 28)], fill=rgb('#a39370'))
    return im


def t_checkpoint():
    im = t_asfalto(rgb('#3d3f46'))
    d = ImageDraw.Draw(im)
    for x in range(4, T, 10):
        d.rectangle([x, 6, x + 4, T - 7], fill=rgb('#d9d9d2'))
    return im


def t_arbol():
    im = Image.new('RGBA', (T, T), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.ellipse([8, 28, 32, 38], fill=(0, 0, 0, 70))
    d.rectangle([18, 22, 22, 34], fill=rgb('#5b3d27'))
    for cx, cy, r, col in [(20, 14, 13, '#2f6a35'), (13, 18, 8, '#37793d'), (27, 18, 8, '#37793d'), (20, 10, 8, '#3f8a46')]:
        d.ellipse([cx - r, cy - r, cx + r, cy + r], fill=rgb(col))
    for _ in range(14):
        x, y = rnd.randint(9, 31), rnd.randint(4, 22)
        d.point((x, y), fill=rgb('#5cae62'))
    return im


def t_farol():
    im = Image.new('RGBA', (T, T), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.ellipse([14, 32, 26, 38], fill=(0, 0, 0, 70))
    d.rectangle([19, 10, 21, 34], fill=rgb('#2a2d35'))
    d.rectangle([15, 4, 25, 11], fill=rgb('#2a2d35')); d.rectangle([16, 5, 24, 10], fill=rgb('#f4d878'))
    return im


def t_barrera():
    im = Image.new('RGBA', (T, T), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    for fila, y in enumerate((26, 17, 8)):
        n = 4 - fila
        x0 = 20 - n * 5
        for i in range(n):
            d.rounded_rectangle([x0 + i * 10, y, x0 + i * 10 + 9, y + 8], 3, fill=jitter(rgb('#b9a37a'), 6), outline=rgb('#7b6a4b'))
    return im


def t_valla():
    im = Image.new('RGBA', (T, T), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    for x in (4, 20, 36):
        d.rectangle([x, 6, x + 2, 36], fill=rgb('#3a3d44'))
    for y in (10, 18, 26, 34):
        d.line([(0, y), (T, y)], fill=rgb('#6b6f78'))
        for x in range(2, T, 6):
            d.point((x, y - 1), fill=rgb('#6b6f78'))
    return im


def t_torre():
    im = Image.new('RGBA', (T, T), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.ellipse([6, 32, 34, 39], fill=(0, 0, 0, 70))
    d.rectangle([14, 16, 26, 35], fill=rgb('#6a6d75')); d.rectangle([14, 16, 26, 35], outline=rgb('#44474e'))
    d.polygon([(8, 16), (32, 16), (28, 6), (12, 6)], fill=rgb('#4e5159'))
    d.rectangle([12, 8, 28, 14], fill=rgb('#2b3a4a'))
    return im


def t_vias():
    im = t_asfalto(rgb('#4a4c54')); d = ImageDraw.Draw(im)
    for x in range(2, T, 8):
        d.rectangle([x, 9, x + 3, 31], fill=rgb('#5a4632'))
    for y in (12, 27):
        d.rectangle([0, y, T, y + 2], fill=rgb('#9da3ad')); d.line([(0, y), (T, y)], fill=rgb('#c9ced6'))
    return im


def t_colision():
    im = Image.new('RGBA', (T, T), (227, 38, 54, 90)); d = ImageDraw.Draw(im)
    d.rectangle([0, 0, T - 1, T - 1], outline=(227, 38, 54, 200)); d.line([(0, 0), (T, T)], fill=(227, 38, 54, 200))
    return im


NOMBRES = ['pasto_oeste', 'pasto_oeste_b', 'adoquin_oeste', 'vereda_oeste', 'plaza_oeste',
           'pasto_este', 'pasto_este_b', 'asfalto_este', 'vereda_este', 'plaza_este',
           'muro', 'franja', 'checkpoint', 'adoquin_oeste_b', 'asfalto_este_b',
           'arbol', 'farol', 'barrera', 'valla', 'torre', 'colision', 'vias']
G = {n: i + 1 for i, n in enumerate(NOMBRES)}    # gid de cada mosaico (empieza en 1)
SOLIDO = G['colision']


def hacer_tileset():
    fabricas = [
        lambda: t_pasto(rgb('#3f6d3a'), 8, 5), lambda: t_pasto(rgb('#467a40'), 8, 9),
        lambda: t_adoquin(rgb('#7b7468'), rgb('#5c564d')), lambda: t_vereda(rgb('#a9a79f'), rgb('#8c8a82')),
        lambda: t_vereda(rgb('#c9b78e'), rgb('#a8966e')),
        lambda: t_pasto(rgb('#5b6350'), 7, 4), lambda: t_pasto(rgb('#626a56'), 7, 8),
        lambda: t_asfalto(rgb('#4a4c54')), lambda: t_panel(rgb('#8a8c90'), rgb('#6e7075')),
        lambda: t_panel(rgb('#a3a5a8'), rgb('#84868a')),
        t_muro, t_franja, t_checkpoint,
        lambda: t_adoquin(rgb('#847c6f'), rgb('#625b50')), lambda: t_asfalto(rgb('#44464d')),
        t_arbol, t_farol, t_barrera, t_valla, t_torre, t_colision, t_vias,
    ]
    cols = 8
    filas = (len(fabricas) + cols - 1) // cols
    hoja = Image.new('RGBA', (cols * T, filas * T), (0, 0, 0, 0))
    for i, f in enumerate(fabricas):
        hoja.paste(f(), ((i % cols) * T, (i // cols) * T))
    hoja.save(os.path.join(OUT_IMG, 'tileset.png'))
    return cols, len(fabricas), filas


# ---------------------------------------------------------------- EDIFICIOS
TIPOGRAFIAS = ['/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf', '/usr/share/fonts/dejavu/DejaVuSans-Bold.ttf',
               'C:/Windows/Fonts/arialbd.ttf', '/System/Library/Fonts/Supplemental/Arial Bold.ttf']


def fuente(n):
    for ruta in TIPOGRAFIAS:
        if os.path.exists(ruta):
            return ImageFont.truetype(ruta, n)
    try:
        return ImageFont.load_default(size=n)
    except TypeError:
        return ImageFont.load_default()


LUCES = []   # centros de ventanas del edificio que se está dibujando (para iluminarlas de noche)


def ventanas(d, x0, y0, x1, y1, nx, ny, col, marco):
    ancho = (x1 - x0) / nx
    alto = (y1 - y0) / ny
    for i in range(nx):
        for j in range(ny):
            a, b = x0 + i * ancho + ancho * 0.2, y0 + j * alto + alto * 0.2
            d.rectangle([a, b, a + ancho * 0.6, b + alto * 0.6], fill=col, outline=marco)
            LUCES.append((int(a + ancho * 0.3), int(b + alto * 0.3)))


def base_edificio(w, h, extra, fachada, techo, borde):
    im = Image.new('RGBA', (w, h + extra), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.rectangle([0, extra, w - 1, h + extra - 1], fill=fachada, outline=borde)
    d.rectangle([0, 0, w - 1, extra + 6], fill=techo, outline=borde)
    d.rectangle([2, 2, w - 3, 8], fill=tuple(min(255, v + 25) for v in techo))
    return im, d


def puerta(d, w, h, extra, col=(40, 30, 24)):
    cx = w // 2
    d.rectangle([cx - 9, h + extra - 24, cx + 9, h + extra - 1], fill=col, outline=(15, 12, 10))
    d.rectangle([cx - 7, h + extra - 3, cx + 7, h + extra - 1], fill=(244, 196, 48))
    LUCES.append((cx, h + extra - 12))


def ed_cafe(este):
    w, h, extra = 120, 80, 36
    if este:
        im, d = base_edificio(w, h, extra, rgb('#9a9ca0'), rgb('#6d6f74'), rgb('#44464b'))
        for x in range(0, w, 20):
            d.line([(x, extra + 8), (x, h + extra - 2)], fill=rgb('#868890'))
        d.rectangle([14, extra + 6, w - 15, extra + 24], fill=rgb('#e07a26'), outline=rgb('#8a4a14'))
        d.text((w // 2, extra + 15), 'CAFÉ', font=fuente(15), fill=rgb('#fff1d8'), anchor='mm')
    else:
        im, d = base_edificio(w, h, extra, rgb('#a8523a'), rgb('#5d3b30'), rgb('#3a2219'))
        for y in range(extra + 8, h + extra - 2, 8):
            d.line([(1, y), (w - 2, y)], fill=rgb('#8c4430'))
        d.rectangle([14, extra + 6, w - 15, extra + 24], fill=rgb('#2d2a28'), outline=rgb('#14110f'))
        d.text((w // 2, extra + 15), 'CAFÉ', font=fuente(15), fill=rgb('#f4d878'), anchor='mm')
    ventanas(d, 8, extra + 28, 52, h + extra - 26, 2, 1, rgb('#f2d98d'), rgb('#3a2a1c'))
    ventanas(d, w - 52, extra + 28, w - 8, h + extra - 26, 2, 1, rgb('#f2d98d'), rgb('#3a2a1c'))
    # toldo a rayas sobre la puerta
    for i, x in enumerate(range(w // 2 - 22, w // 2 + 22, 7)):
        d.rectangle([x, h + extra - 38, x + 6, h + extra - 26], fill=rgb('#d94a58') if i % 2 == 0 else rgb('#f0ece4'))
    puerta(d, w, h, extra)
    return im


def ed_reichstag():
    w, h, extra = 200, 120, 60
    im, d = base_edificio(w, h, extra, rgb('#cfc4a8'), rgb('#9c917a'), rgb('#6a604d'))
    d.polygon([(10, extra + 10), (w // 2, 4), (w - 10, extra + 10)], fill=rgb('#b9ad92'), outline=rgb('#6a604d'))   # frontón
    # Sin cúpula: la original se demolió en 1954 y la de vidrio es de 1999. En los 70, techo plano con cuatro torres.
    for x0 in (2, w - 34):
        d.rectangle([x0, 6, x0 + 32, extra + 18], fill=rgb('#c4b99c'), outline=rgb('#6a604d'))
        d.rectangle([x0 + 4, 2, x0 + 28, 8], fill=rgb('#9c917a'), outline=rgb('#6a604d'))
        ventanas(d, x0 + 4, 14, x0 + 28, extra + 14, 2, 2, rgb('#44566a'), rgb('#2c2a24'))
    for x in range(14, w - 10, 26):
        d.rectangle([x, extra + 14, x + 9, h + extra - 3], fill=rgb('#e6dcc2'), outline=rgb('#8c8268'))
    ventanas(d, 8, extra + 12, w - 8, h + extra - 26, 7, 1, rgb('#44566a'), rgb('#2c2a24'))
    d.text((w // 2, extra + 38), 'REICHSTAG', font=fuente(13), fill=rgb('#2d2a24'), anchor='mm')
    puerta(d, w, h, extra)
    return im


def ed_kadewe():
    w, h, extra = 160, 120, 44
    im, d = base_edificio(w, h, extra, rgb('#e2d6b8'), rgb('#b24a3a'), rgb('#6b5d46'))
    ventanas(d, 8, extra + 12, w - 8, h + extra - 30, 6, 3, rgb('#8fb4cc'), rgb('#5a6068'))
    d.rectangle([20, extra + 2, w - 21, extra + 20], fill=rgb('#a63a2c'), outline=rgb('#5c1d14'))
    d.text((w // 2, extra + 11), 'KaDeWe', font=fuente(15), fill=rgb('#fbeedd'), anchor='mm')
    puerta(d, w, h, extra, rgb('#3a2c20'))
    return im


def ed_museo():
    w, h, extra = 160, 80, 40
    im, d = base_edificio(w, h, extra, rgb('#4b5668'), rgb('#313a49'), rgb('#1f2631'))
    d.rectangle([8, extra + 10, w - 9, h + extra - 28], fill=(120, 170, 200, 230), outline=rgb('#1f2631'))
    for x in range(8, w - 8, 20):
        d.line([(x, extra + 10), (x, h + extra - 28)], fill=rgb('#2a3442'))
    for i in range(5):    # un trozo del Muro expuesto delante
        d.rectangle([w - 46 + i * 7, h + extra - 24, w - 41 + i * 7, h + extra - 1], fill=rgb('#8a8d94'), outline=rgb('#5d6068'))
    d.text((60, extra + 20), 'MUSEO DEL MURO', font=fuente(11), fill=rgb('#e9e9e9'), anchor='mm')
    puerta(d, w, h, extra)
    return im


def ed_torre():
    w, h, extra = 80, 80, 230
    im = Image.new('RGBA', (w, h + extra), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.rectangle([4, extra + 30, w - 5, h + extra - 1], fill=rgb('#9a9ca0'), outline=rgb('#44464b'))
    d.rectangle([4, extra + 24, w - 5, extra + 34], fill=rgb('#6d6f74'), outline=rgb('#44464b'))
    d.polygon([(w // 2 - 5, extra + 26), (w // 2 + 5, extra + 26), (w // 2 + 2, 70), (w // 2 - 2, 70)], fill=rgb('#c8cad0'), outline=rgb('#6d6f74'))
    d.ellipse([w // 2 - 24, 24, w // 2 + 24, 72], fill=rgb('#d9dbe0'), outline=rgb('#6d6f74'))
    d.ellipse([w // 2 - 20, 28, w // 2 - 4, 44], fill=(255, 255, 255, 140))
    d.rectangle([w // 2 - 24, 46, w // 2 + 24, 52], fill=rgb('#8e939c'))
    d.line([(w // 2, 0), (w // 2, 26)], fill=rgb('#e32636'), width=3)
    d.text((w // 2, extra + 52), 'TV', font=fuente(13), fill=rgb('#fff1d8'), anchor='mm')
    puerta(d, w, h, extra)
    return im


def ed_centrum():
    w, h, extra = 160, 120, 44
    im, d = base_edificio(w, h, extra, rgb('#b9bcc2'), rgb('#8a8d94'), rgb('#51545a'))
    for y in range(extra + 12, h + extra - 28, 9):      # fachada de alveolos metálicos
        for x in range(6 + (y // 9 % 2) * 4, w - 8, 8):
            d.ellipse([x, y, x + 5, y + 5], fill=rgb('#6f7279'), outline=rgb('#5d6066'))
    d.rectangle([14, extra + 2, w - 15, extra + 20], fill=rgb('#c0392b'), outline=rgb('#6b1d14'))
    d.text((w // 2, extra + 11), 'CENTRUM', font=fuente(15), fill=rgb('#fbeedd'), anchor='mm')
    puerta(d, w, h, extra)
    return im


def ed_cuartel():
    w, h, extra = 200, 120, 44
    im, d = base_edificio(w, h, extra, rgb('#6b7558'), rgb('#4b5340'), rgb('#2f3527'))
    ventanas(d, 10, extra + 14, w - 10, h + extra - 30, 8, 2, rgb('#2b3a4a'), rgb('#232a1d'))
    d.rectangle([w // 2 - 4, 0, w // 2 - 2, extra + 4], fill=rgb('#2a2d35'))
    d.rectangle([w // 2 - 2, 2, w // 2 + 22, 16], fill=rgb('#e32636'))
    d.text((w // 2 + 10, 9), '★', font=fuente(11), fill=rgb('#f4c430'), anchor='mm')
    d.text((w // 2, extra + 30), 'CUARTEL', font=fuente(14), fill=rgb('#e9e4cc'), anchor='mm')
    puerta(d, w, h, extra)
    return im


# ---------------------------------------------------------------- PERSONAJES
PALETAS = {
    'oeste': dict(abrigo='#3e78d6', abrigo2='#2f5fb0'),
    'este': dict(abrigo='#cf3040', abrigo2='#a32333'),
    'neutro': dict(abrigo='#8a8f99', abrigo2='#6a6f78'),
}
PIELES = ['#f1c9a5', '#d6a77a', '#8d5a3b']
PELOS = ['#2b1d14', '#6b4a2b', '#c9a05a', '#8a8a8a']
GORROS = 3                       # 0 sin gorro, 1 gorra, 2 sombrero
VARIANTES = len(PIELES) * len(PELOS) * GORROS   # 36: índice = piel * 12 + pelo * 3 + gorro
ABRIGOS_CIVILES = [('#7a5a3a', '#5e452c'), ('#3d4a5c', '#2e3846'), ('#5c3d4a', '#472f39'),
                   ('#4a5c3d', '#38462e'), ('#8a7a5a', '#6b5e45'), ('#2f3a4a', '#232c38')]


def escurecer(c, k=0.8):
    return tuple(int(v * k) for v in c)


def hoja_personaje(abrigo, abrigo2, piel, pelo, gorro, pantalon='#2b2f3a', casco=None):
    """Una hoja de 3 cuadros x 4 direcciones (abajo, izquierda, derecha, arriba) de 32x48."""
    piel, pelo, pant, zap = rgb(piel), rgb(pelo), rgb(pantalon), rgb('#14161b')
    ab, ab2 = rgb(abrigo), rgb(abrigo2)
    hoja = Image.new('RGBA', (3 * 32, 6 * 48), (0, 0, 0, 0))   # 4 filas para caminar + 2 de gestos
    for fila, dir_ in enumerate(['abajo', 'izq', 'der', 'arriba']):
        for cuadro in range(3):
            im = Image.new('RGBA', (16, 24), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
            paso = 0 if cuadro == 0 else (1 if cuadro == 1 else -1)
            if dir_ in ('abajo', 'arriba'):
                l1, l2 = (1 if paso == 1 else 0), (1 if paso == -1 else 0)
                d.rectangle([5, 16, 7, 21 + l1], fill=pant); d.rectangle([5, 21 + l1, 7, 22 + l1], fill=zap)
                d.rectangle([9, 16, 11, 21 + l2], fill=pant); d.rectangle([9, 21 + l2, 11, 22 + l2], fill=zap)
                d.rectangle([2, 8 + l2, 3, 14], fill=ab2); d.rectangle([12, 8 + l1, 13, 14], fill=ab2)
                d.rectangle([2, 14, 3, 15], fill=piel); d.rectangle([12, 14, 13, 15], fill=piel)
                d.rectangle([4, 8, 11, 16], fill=ab); d.rectangle([4, 14, 11, 16], fill=ab2)
                if dir_ == 'abajo':
                    d.line([(8, 9), (8, 16)], fill=ab2)
                d.rectangle([5, 1, 10, 7], fill=piel)
                if dir_ == 'abajo':
                    d.rectangle([5, 1, 10, 3], fill=pelo); d.point((6, 5), fill=(20, 20, 24)); d.point((9, 5), fill=(20, 20, 24))
                else:
                    d.rectangle([5, 1, 10, 7], fill=pelo)
            else:
                f = 1 if dir_ == 'der' else -1
                d.rectangle([7 + paso * f, 16, 9 + paso * f, 22], fill=pant); d.rectangle([7 + paso * f, 22, 10 + paso * f, 22], fill=zap)
                d.rectangle([7 - paso * f, 16, 9 - paso * f, 22], fill=escurecer(pant, 0.85)); d.rectangle([7 - paso * f, 22, 10 - paso * f, 22], fill=zap)
                d.rectangle([5, 8, 10, 16], fill=ab); d.rectangle([5, 14, 10, 16], fill=ab2)
                d.rectangle([7 - paso * f, 8, 8 - paso * f, 14], fill=ab2); d.rectangle([7 - paso * f, 14, 8 - paso * f, 15], fill=piel)
                d.rectangle([5, 1, 10, 7], fill=piel)
                d.rectangle([5, 1, 10, 3], fill=pelo)
                if dir_ == 'der':
                    d.rectangle([5, 3, 6, 7], fill=pelo); d.point((9, 5), fill=(20, 20, 24))
                else:
                    d.rectangle([9, 3, 10, 7], fill=pelo); d.point((6, 5), fill=(20, 20, 24))
            # Lo que va en la cabeza
            if casco is not None:
                c = rgb(casco)
                d.rectangle([4, 0, 11, 3], fill=c); d.rectangle([3, 3, 12, 3], fill=escurecer(c))
            elif gorro == 1:      # gorra de obrero
                c = rgb('#3b3f47')
                d.rectangle([5, 0, 10, 2], fill=c)
                if dir_ == 'abajo':
                    d.rectangle([4, 2, 11, 2], fill=escurecer(c))
                elif dir_ == 'der':
                    d.rectangle([9, 2, 12, 2], fill=escurecer(c))
                elif dir_ == 'izq':
                    d.rectangle([3, 2, 6, 2], fill=escurecer(c))
            elif gorro == 2:      # sombrero
                c = rgb('#4a3b2e')
                d.rectangle([5, -1, 10, 1], fill=c); d.rectangle([3, 2, 12, 2], fill=escurecer(c))
                d.rectangle([5, 1, 10, 1], fill=rgb('#1e1a16'))
            hoja.paste(im.resize((32, 48), Image.NEAREST), (cuadro * 32, fila * 48))
    for i, gesto in enumerate(['saludar_a', 'saludar_b', 'aplaudir_cerrado', 'sentado', 'encogerse', 'aplaudir_abierto']):
        im = Image.new('RGBA', (16, 24), (0, 0, 0, 0))
        gesto_frente(ImageDraw.Draw(im), gesto, piel, pelo, pant, zap, ab, ab2, gorro, casco)
        hoja.paste(im.resize((32, 48), Image.NEAREST), ((i % 3) * 32, (4 + i // 3) * 48))
    return hoja


def gesto_frente(d, gesto, piel, pelo, pant, zap, ab, ab2, gorro, casco):
    """Personaje de frente haciendo un gesto (para el mundo y para las cinemáticas)."""
    sentado = gesto == 'sentado'
    dy = 5 if sentado else 0
    if sentado:   # piernas dobladas hacia adelante
        d.rectangle([4, 20, 7, 22], fill=pant); d.rectangle([8, 20, 11, 22], fill=escurecer(pant, 0.85))
        d.rectangle([4, 22, 5, 23], fill=zap); d.rectangle([10, 22, 11, 23], fill=zap)
    else:
        d.rectangle([5, 16, 7, 21], fill=pant); d.rectangle([5, 21, 7, 22], fill=zap)
        d.rectangle([9, 16, 11, 21], fill=pant); d.rectangle([9, 21, 11, 22], fill=zap)
    d.rectangle([4, 8 + dy, 11, 16 + dy if not sentado else 20], fill=ab); d.rectangle([4, 14 + dy, 11, 16 + dy if not sentado else 20], fill=ab2)
    brazos = {
        'saludar_a': [(2, 8, 3, 14, 2, 14, 3, 15), (12, 2, 13, 8, 12, 0, 13, 1)],
        'saludar_b': [(2, 8, 3, 14, 2, 14, 3, 15), (13, 2, 14, 8, 14, 0, 15, 1)],
        'aplaudir_cerrado': [(3, 9, 5, 12, 6, 11, 7, 13), (10, 9, 12, 12, 8, 11, 9, 13)],
        'aplaudir_abierto': [(3, 9, 4, 12, 5, 11, 6, 12), (11, 9, 12, 12, 9, 11, 10, 12)],
        'encogerse': [(1, 9, 3, 10, 0, 8, 1, 9), (12, 9, 14, 10, 14, 8, 15, 9)],
        'sentado': [(3, 13, 4, 18, 3, 19, 4, 19), (11, 13, 12, 18, 11, 19, 12, 19)],
    }[gesto]
    for (x0, y0, x1, y1, hx0, hy0, hx1, hy1) in brazos:
        d.rectangle([x0, y0 + (dy if sentado else 0), x1, y1 + (dy if sentado else 0)], fill=ab2)
        d.rectangle([hx0, hy0 + (dy if sentado else 0), hx1, hy1 + (dy if sentado else 0)], fill=piel)
    cabeza = -1 if gesto == 'encogerse' else 0   # al encogerse de hombros la cabeza baja un poco
    y = dy + (1 if gesto == 'encogerse' else 0)
    d.rectangle([5, 1 + y, 10, 7 + y], fill=piel)
    d.rectangle([5, 1 + y, 10, 3 + y], fill=pelo)
    d.point((6, 5 + y), fill=(20, 20, 24)); d.point((9, 5 + y), fill=(20, 20, 24))
    if gesto in ('saludar_a', 'saludar_b', 'aplaudir_abierto'):
        d.line([(7, 6 + y), (8, 6 + y)], fill=escurecer(piel, 0.7))   # sonrisa
    if casco is not None:
        cc = rgb(casco); d.rectangle([4, 0 + y, 11, 3 + y], fill=cc); d.rectangle([3, 3 + y, 12, 3 + y], fill=escurecer(cc))
    elif gorro == 1:
        cc = rgb('#3b3f47'); d.rectangle([5, 0 + y, 10, 2 + y], fill=cc); d.rectangle([4, 2 + y, 11, 2 + y], fill=escurecer(cc))
    elif gorro == 2:
        cc = rgb('#4a3b2e'); d.rectangle([5, -1 + y, 10, 1 + y], fill=cc); d.rectangle([3, 2 + y, 12, 2 + y], fill=escurecer(cc))
        d.rectangle([5, 1 + y, 10, 1 + y], fill=rgb('#1e1a16'))


def atlas(hojas):
    """36 hojas en una sola imagen: 6 variantes por fila, cada una de 3 x 6 cuadros (576 x 1728)."""
    a = Image.new('RGBA', (6 * 96, 6 * 288), (0, 0, 0, 0))
    for v, h in enumerate(hojas):
        a.paste(h, ((v % 6) * 96, (v // 6) * 288))
    return a


def variantes(abrigo, abrigo2):
    return [hoja_personaje(abrigo, abrigo2, PIELES[v // 12], PELOS[(v // 3) % 4], v % 3) for v in range(VARIANTES)]


def personaje(pal):
    """Compatibilidad: la hoja de la variante 0 de un bando."""
    return hoja_personaje(pal['abrigo'], pal['abrigo2'], PIELES[0], PELOS[0], 0)


def hacer_personajes():
    for nombre, pal in PALETAS.items():
        atlas(variantes(pal['abrigo'], pal['abrigo2'])).save(os.path.join(OUT_IMG, 'personaje_%s.png' % nombre))
    civiles = [hoja_personaje(*ABRIGOS_CIVILES[v % 6], PIELES[v % 3], PELOS[(v // 3) % 4], (v // 12) % 3) for v in range(VARIANTES)]
    atlas(civiles).save(os.path.join(OUT_IMG, 'npc_civiles.png'))
    hoja_personaje('#5b6447', '#474e37', PIELES[0], PELOS[0], 0, pantalon='#474e37', casco='#4e5640').save(os.path.join(OUT_IMG, 'npc_soldado.png'))
    hoja_personaje('#556b2f', '#435424', PIELES[1], PELOS[1], 0, pantalon='#435424', casco='#e9e9e9').save(os.path.join(OUT_IMG, 'npc_mp.png'))


def retrato(variante, emocion):
    """Retrato de 32x32 (se agranda a 96x96) para los diálogos. Misma piel, pelo, gorro y abrigo que el sprite del contacto."""
    piel, pelo = rgb(PIELES[variante % 3]), rgb(PELOS[(variante // 3) % 4])
    gorro = (variante // 12) % 3
    ab, ab2 = (rgb(x) for x in ABRIGOS_CIVILES[variante % 6])
    im = Image.new('RGBA', (32, 32), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.ellipse([1, 1, 30, 30], fill=rgb('#1b2440'))                       # fondo
    d.rectangle([6, 24, 25, 31], fill=ab); d.rectangle([6, 29, 25, 31], fill=ab2)   # hombros
    d.polygon([(13, 24), (16, 28), (19, 24)], fill=rgb('#e9e4cc'))       # camisa
    d.rectangle([14, 21, 17, 24], fill=escurecer(piel, 0.9))             # cuello
    d.rectangle([10, 8, 21, 21], fill=piel)                              # cara
    d.rectangle([9, 12, 9, 16], fill=escurecer(piel, 0.9)); d.rectangle([22, 12, 22, 16], fill=escurecer(piel, 0.9))   # orejas
    d.rectangle([10, 6, 21, 10], fill=pelo); d.rectangle([10, 10, 11, 14], fill=pelo); d.rectangle([20, 10, 21, 14], fill=pelo)
    ojo, ceja = (20, 20, 24), escurecer(pelo, 0.8)
    d.rectangle([12, 13, 13, 14], fill=ojo); d.rectangle([18, 13, 19, 14], fill=ojo)
    boca = escurecer(piel, 0.6)
    if emocion == 'contento':
        d.line([(12, 11), (14, 11)], fill=ceja); d.line([(17, 11), (19, 11)], fill=ceja)
        d.line([(13, 17), (18, 17)], fill=boca); d.point((12, 16), fill=boca); d.point((19, 16), fill=boca)
    elif emocion == 'enojado':
        d.line([(12, 10), (14, 12)], fill=ceja); d.line([(17, 12), (19, 10)], fill=ceja)
        d.line([(13, 18), (18, 18)], fill=boca); d.point((12, 19), fill=boca); d.point((19, 19), fill=boca)
    elif emocion == 'triste':
        d.line([(12, 12), (14, 10)], fill=ceja); d.line([(17, 10), (19, 12)], fill=ceja)
        d.line([(14, 18), (17, 18)], fill=boca); d.point((13, 19), fill=boca); d.point((18, 19), fill=boca)
        d.point((12, 15), fill=rgb('#9cc0ff'))                          # una lágrima
    else:   # neutral
        d.line([(12, 11), (14, 11)], fill=ceja); d.line([(17, 11), (19, 11)], fill=ceja)
        d.line([(14, 18), (17, 18)], fill=boca)
    if gorro == 1:
        c = rgb('#3b3f47'); d.rectangle([10, 4, 21, 7], fill=c); d.rectangle([9, 8, 23, 8], fill=escurecer(c))
    elif gorro == 2:
        c = rgb('#4a3b2e'); d.rectangle([11, 2, 20, 7], fill=c); d.rectangle([7, 8, 24, 8], fill=escurecer(c)); d.rectangle([11, 6, 20, 6], fill=rgb('#1e1a16'))
    return im.resize((96, 96), Image.NEAREST)


def hacer_retratos():
    datos = json.load(open(os.path.join(OUT_DATOS, 'misiones.json'), encoding='utf-8'))
    carpeta = os.path.join(OUT_IMG, 'retratos')
    os.makedirs(carpeta, exist_ok=True)
    for c in datos['contactos']:
        for emocion in ('neutral', 'contento', 'enojado', 'triste'):
            retrato(int(c['variante']), emocion).save(os.path.join(carpeta, '%s_%s.png' % (c['id'], emocion)))


def hacer_decorados_sucesos():
    """Decorados de los sucesos (etapa B): puesto de mercado, escenario, auto oficial y cartel."""
    # Puesto de mercado (64x60): mesa con libros, discos y postales bajo un toldo a rayas
    im = Image.new('RGBA', (64, 60), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.ellipse([4, 52, 60, 60], fill=(0, 0, 0, 70))
    for x in (6, 56):
        d.rectangle([x, 14, x + 2, 54], fill=rgb('#5a4632'))
    for i, x in enumerate(range(2, 62, 8)):
        d.rectangle([x, 6, x + 7, 16], fill=rgb('#2f6a35') if i % 2 else rgb('#e9e4cc'))
    d.rectangle([4, 34, 60, 46], fill=rgb('#7b5a3a'), outline=rgb('#4e3a26'))
    for k, col in enumerate(['#b3262b', '#3f5466', '#c9a05a', '#2f3a4a', '#e9e4cc', '#6b4a2b']):
        d.rectangle([8 + k * 8, 28, 13 + k * 8, 34], fill=rgb(col))
    d.ellipse([46, 24, 56, 34], fill=rgb('#14161b')); d.ellipse([49, 27, 53, 31], fill=rgb('#c9a05a'))   # un disco
    im.save(os.path.join(OUT_IMG, 'suceso_puesto.png'))
    # Escenario (176x96): tarima con parlantes y luces
    im = Image.new('RGBA', (176, 96), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.ellipse([6, 84, 170, 96], fill=(0, 0, 0, 80))
    d.rectangle([8, 54, 168, 88], fill=rgb('#3a2e24'), outline=rgb('#1e1813'))
    d.rectangle([8, 50, 168, 56], fill=rgb('#5a4632'))
    d.rectangle([12, 8, 16, 52], fill=rgb('#2a2d35')); d.rectangle([160, 8, 164, 52], fill=rgb('#2a2d35'))
    d.rectangle([12, 6, 164, 12], fill=rgb('#2a2d35'))
    for x in (30, 60, 90, 120, 146):
        d.ellipse([x, 12, x + 8, 20], fill=rgb('#f4d878'))
        LUCES.append((x + 4, 16))
    for x in (20, 140):
        d.rectangle([x, 26, x + 16, 52], fill=rgb('#14161b')); d.ellipse([x + 3, 30, x + 13, 40], fill=rgb('#3a3d44')); d.ellipse([x + 4, 42, x + 12, 50], fill=rgb('#3a3d44'))
    im.save(os.path.join(OUT_IMG, 'suceso_escenario.png'))
    # Auto oficial (80x34), de costado, mirando a la derecha
    im = Image.new('RGBA', (80, 34), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.ellipse([4, 28, 76, 34], fill=(0, 0, 0, 80))
    d.rounded_rectangle([2, 12, 77, 27], 4, fill=rgb('#16181d'), outline=rgb('#0a0b0e'))
    d.polygon([(18, 12), (26, 3), (54, 3), (62, 12)], fill=rgb('#16181d'))
    d.polygon([(22, 12), (28, 5), (38, 5), (38, 12)], fill=rgb('#4d6272')); d.polygon([(41, 12), (41, 5), (52, 5), (58, 12)], fill=rgb('#4d6272'))
    d.rectangle([74, 16, 77, 19], fill=rgb('#f4d878')); d.rectangle([2, 16, 4, 19], fill=rgb('#b3262b'))
    for x in (12, 56):
        d.ellipse([x, 22, x + 12, 33], fill=rgb('#0a0b0e')); d.ellipse([x + 3, 25, x + 9, 31], fill=rgb('#8a8d94'))
    d.rectangle([68, 6, 69, 12], fill=rgb('#c9ced6')); d.rectangle([69, 6, 74, 9], fill=rgb('#e32636'))   # banderín
    im.save(os.path.join(OUT_IMG, 'suceso_auto.png'))
    # Cartel de manifestación (24x40)
    im = Image.new('RGBA', (24, 40), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.rectangle([11, 14, 12, 40], fill=rgb('#5a4632'))
    d.rectangle([1, 1, 22, 16], fill=rgb('#f0ece4'), outline=rgb('#5a4632'))
    for y in (5, 9, 12):
        d.line([(4, y), (19, y)], fill=rgb('#b3262b') if y == 5 else rgb('#2b2f3a'))
    im.save(os.path.join(OUT_IMG, 'suceso_cartel.png'))


def hacer_tranvia():
    """Tranvía Tatra del Este, de costado (mirando a la derecha). 128 x 52."""
    w, h = 128, 52
    im = Image.new('RGBA', (w, h), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.ellipse([4, h - 8, w - 4, h], fill=(0, 0, 0, 80))
    d.rounded_rectangle([2, 10, w - 3, h - 6], 6, fill=rgb('#e8dcc0'), outline=rgb('#6b5e45'))
    d.rectangle([2, h - 20, w - 3, h - 12], fill=rgb('#b3262b'))
    for x in range(10, w - 16, 16):
        d.rectangle([x, 16, x + 11, 28], fill=rgb('#3f5466'), outline=rgb('#2a3540'))
        LUCES.append((x + 5, 22))
    d.rectangle([w - 14, 14, w - 5, 30], fill=rgb('#3f5466'))   # parabrisas
    d.rectangle([w - 10, h - 14, w - 6, h - 10], fill=rgb('#f4d878'))   # farol delantero
    d.rectangle([4, 4, w - 6, 10], fill=rgb('#9a9ca0'), outline=rgb('#6d6f74'))
    d.line([(w // 2 - 12, 4), (w // 2, 0), (w // 2 + 12, 4)], fill=rgb('#2a2d35'), width=2)   # pantógrafo
    for x in (16, w - 30):
        d.ellipse([x, h - 10, x + 12, h - 2], fill=rgb('#2a2d35'))
    im.save(os.path.join(OUT_IMG, 'tranvia.png'))


# ---------------------------------------------------------------- MAPA
def hacer_mapa():
    suelo = [[0] * W for _ in range(H)]
    deco = [[0] * W for _ in range(H)]
    col = [[0] * W for _ in range(H)]
    COL_MURO, FILAS_PASO = 29, (22, 23, 24)
    calles_v_o, calles_v_e = [(6, 7), (15, 16), (24, 25)], [(36, 37), (45, 46), (54, 55)]
    calles_h = [(3, 4), (13, 14), (22, 24), (31, 32)]
    es_calle_v = lambda c: any(a <= c <= b for a, b in calles_v_o + calles_v_e)
    es_calle_h = lambda r: any(a <= r <= b for a, b in calles_h)
    for r in range(H):
        for c in range(W):
            oeste = c <= COL_MURO
            if c == COL_MURO:
                suelo[r][c] = G['muro']; col[r][c] = SOLIDO if r not in FILAS_PASO else 0
                if r in FILAS_PASO:
                    suelo[r][c] = G['checkpoint']
            elif c in (30, 31):
                suelo[r][c] = G['checkpoint'] if r in FILAS_PASO else G['franja']
            elif c == 32:
                suelo[r][c] = G['checkpoint'] if r in FILAS_PASO else G['franja']
                if r not in FILAS_PASO:
                    deco[r][c] = G['valla']; col[r][c] = SOLIDO
            elif es_calle_v(c) or es_calle_h(r):
                if oeste:
                    suelo[r][c] = G['adoquin_oeste'] if rnd.random() < 0.8 else G['adoquin_oeste_b']
                else:
                    suelo[r][c] = G['asfalto_este'] if rnd.random() < 0.85 else G['asfalto_este_b']
            else:
                # manzana: vereda en el borde junto a la calle, pasto adentro
                vecino = es_calle_v(c - 1) or es_calle_v(c + 1) or es_calle_h(r - 1) or es_calle_h(r + 1)
                if oeste:
                    suelo[r][c] = G['vereda_oeste'] if vecino else (G['pasto_oeste'] if rnd.random() < 0.7 else G['pasto_oeste_b'])
                else:
                    suelo[r][c] = G['vereda_este'] if vecino else (G['pasto_este'] if rnd.random() < 0.7 else G['pasto_este_b'])
    # Vías del tranvía en la avenida del Este (filas 13 y 14): en el Oeste los tranvías se fueron quitando; en el Este siguieron.
    for r in (13, 14):
        for c in range(33, W):
            suelo[r][c] = G['vias']
    # El paso de Checkpoint Charlie atraviesa toda la franja
    for r in FILAS_PASO:
        for c in range(26, 36):
            suelo[r][c] = G['checkpoint']
    # Torres de vigilancia a lo largo de la franja
    for r in (5, 10, 17, 28, 33):
        deco[r][31] = G['torre']; col[r][31] = SOLIDO
    # Sacos de arena junto al paso
    for (c, r) in ((27, 21), (27, 25), (34, 21), (34, 25)):
        deco[r][c] = G['barrera']; col[r][c] = SOLIDO

    # Edificios: (id, nombre, tipo, distrito, x, y, w, h) en mosaicos
    ED = [
        ('cafe-1', 'Café Spandau', 'cafe', 1, 9, 6, 3, 2, 'oeste'), ('cafe-2', 'Café Wedding', 'cafe', 2, 19, 6, 3, 2, 'oeste'),
        ('cafe-3', 'Café Charlottenburg', 'cafe', 3, 9, 17, 3, 2, 'oeste'), ('cafe-4', 'Café Tiergarten', 'cafe', 4, 26, 17, 3, 2, 'oeste'),
        ('cafe-5', 'Café Schöneberg', 'cafe', 5, 1, 26, 3, 2, 'oeste'), ('cafe-6', 'Café Kreuzberg', 'cafe', 6, 26, 26, 3, 2, 'oeste'),
        ('cafe-7', 'Café Prenzlauer Berg', 'cafe', 7, 39, 6, 3, 2, 'este'), ('cafe-8', 'Café Pankow', 'cafe', 8, 48, 6, 3, 2, 'este'),
        ('cafe-9', 'Café Mitte', 'cafe', 9, 39, 16, 3, 2, 'este'), ('cafe-10', 'Café Lichtenberg', 'cafe', 10, 56, 16, 3, 2, 'este'),
        ('cafe-11', 'Café Friedrichshain', 'cafe', 11, 38, 26, 3, 2, 'este'), ('cafe-12', 'Café Treptow', 'cafe', 12, 48, 26, 3, 2, 'este'),
        ('reichstag', 'Reichstag', 'ranking', None, 18, 16, 5, 3, None), ('kadewe', 'KaDeWe', 'tienda', None, 9, 26, 4, 3, None),
        ('museo', 'Museo del Muro', 'galeria', None, 19, 26, 4, 2, None), ('fernsehturm', 'Torre de TV', 'torneos', None, 42, 18, 2, 2, None),
        ('centrum', 'Centrum Warenhaus', 'tienda', None, 41, 26, 4, 3, None), ('cuartel', 'Cuartel', 'competitivo', None, 48, 17, 5, 3, None),
    ]
    objetos, nid = [], 1
    for (id_, nombre, tipo, dist, x, y, w, h, estilo) in ED:
        for r in range(y, y + h):
            for c in range(x, x + w):
                col[r][c] = SOLIDO
                deco[r][c] = 0
                suelo[r][c] = G['plaza_este'] if x >= 30 else G['plaza_oeste']
        puerta_c, puerta_r = x + w // 2, y + h
        suelo[puerta_r][puerta_c] = G['plaza_este'] if x >= 30 else G['plaza_oeste']
        props = [('id', id_), ('nombre', nombre), ('tipo', tipo), ('puertaX', puerta_c * T + T // 2), ('puertaY', puerta_r * T + T // 2)]
        if dist:
            props.append(('distrito', dist))
        objetos.append(dict(id=nid, name=id_, type='edificio', x=x * T, y=y * T, width=w * T, height=h * T, rotation=0, visible=True,
                            properties=[dict(name=k, type='int' if isinstance(v, int) else 'string', value=v) for k, v in props]))
        nid += 1
        # sprite (las luces se registran en LUCES mientras se dibuja)
        LUCES.clear()
        if tipo == 'cafe':
            img = ed_cafe(estilo == 'este')
            img.save(os.path.join(OUT_ED, 'cafe_%s.png' % estilo))
        else:
            img = {'reichstag': ed_reichstag, 'kadewe': ed_kadewe, 'museo': ed_museo, 'fernsehturm': ed_torre, 'centrum': ed_centrum, 'cuartel': ed_cuartel}[id_]()
            img.save(os.path.join(OUT_ED, id_ + '.png'))
        if id_ == 'fernsehturm':
            LUCES.append((img.width // 2, 2))   # luz roja de la antena
        # luces relativas a la base del sprite: [dx desde la izquierda, dy hacia arriba desde la base]
        luces = [[lx, img.height - ly] for lx, ly in LUCES]
        objetos[-1]['properties'].append(dict(name='luces', type='string', value=json.dumps(luces)))
    # Árboles y faroles (no sobre calles, edificios ni puertas)
    ocupado = {(r, c) for r in range(H) for c in range(W) if col[r][c]}
    puertas = {(int(o['properties'][4]['value']) // T, int(o['properties'][3]['value']) // T) for o in objetos}
    cerca_puerta = lambda r, c: any(abs(r - pr) <= 2 and abs(c - pc) <= 2 for pr, pc in puertas)
    for r in range(H):
        for c in range(W):
            if (r, c) in ocupado or cerca_puerta(r, c):
                continue
            if suelo[r][c] in (G['pasto_oeste'], G['pasto_oeste_b'], G['pasto_este'], G['pasto_este_b']) and rnd.random() < 0.07:
                deco[r][c] = G['arbol']; col[r][c] = SOLIDO
            elif suelo[r][c] in (G['vereda_oeste'], G['vereda_este']) and rnd.random() < 0.05 and not es_calle_h(r):
                deco[r][c] = G['farol']; col[r][c] = SOLIDO
    # Rutas de NPCs (el navegador las recorre según el reloj compartido: todos los ven en el mismo lugar)
    def centro(c, r):
        return [c * T + T // 2, r * T + T // 2]
    manzanas = [(0, 5), (8, 14), (17, 23), (26, 28), (33, 35), (38, 44), (47, 53), (56, 59)]
    franjas = [(5, 12), (15, 21), (25, 30)]
    def ruta(nombre, puntos, clase, cantidad, velocidad, modo):
        nonlocal_rutas.append(dict(id=0, name=nombre, type='ruta', x=0, y=0, width=0, height=0, rotation=0, visible=True,
                                   polyline=[dict(x=px, y=py) for px, py in puntos],
                                   properties=[dict(name='clase', type='string', value=clase), dict(name='cantidad', type='int', value=cantidad),
                                               dict(name='velocidad', type='int', value=velocidad), dict(name='modo', type='string', value=modo)]))
    nonlocal_rutas = []
    huellas = set()
    for o in objetos:
        for r in range(o['y'] // T, (o['y'] + o['height']) // T):
            for c in range(o['x'] // T, (o['x'] + o['width']) // T):
                huellas.add((r, c))
    for (c0, c1) in manzanas:
        for (r0, r1) in franjas:
            anillo = [(r0, c) for c in range(c0, c1 + 1)] + [(r1, c) for c in range(c0, c1 + 1)] + \
                     [(r, c0) for r in range(r0, r1 + 1)] + [(r, c1) for r in range(r0, r1 + 1)]
            if any(t in huellas for t in anillo) or c1 - c0 < 2:
                continue   # no hay vuelta libre alrededor de esta manzana
            esquinas = [centro(c0, r0), centro(c1, r0), centro(c1, r1), centro(c0, r1), centro(c0, r0)]
            ruta('peatones_%d_%d' % (c0, r0), esquinas, 'civil', 2 if c1 - c0 > 4 else 1, 42, 'vuelta')
    # Patrullas de la Grenztruppen por la franja (ida y vuelta, sin cruzar el paso)
    ruta('patrulla_norte', [centro(30, 1), centro(30, 20)], 'patrulla', 1, 34, 'ida_vuelta')
    ruta('patrulla_sur', [centro(31, 26), centro(31, 33)], 'patrulla', 1, 30, 'ida_vuelta')
    # Tranvías por la avenida del Este
    ruta('tranvia_este', [[33 * T + 64, 13 * T + T // 2 + 6], [W * T - 64, 13 * T + T // 2 + 6]], 'tranvia', 1, 95, 'ida_vuelta')
    ruta('tranvia_oeste', [[W * T - 64, 14 * T + T // 2 + 10], [33 * T + 64, 14 * T + T // 2 + 10]], 'tranvia', 1, 80, 'ida_vuelta')
    for i, rt in enumerate(nonlocal_rutas):
        rt['id'] = nid + 10 + i
    # Guardias quietos a cada lado del paso de Checkpoint Charlie
    guardias = [dict(id=nid + 100, name='guardia_mp', type='guardia', x=26 * T + 20, y=21 * T + 30, point=True, width=0, height=0, rotation=0, visible=True,
                     properties=[dict(name='clase', type='string', value='mp'), dict(name='dir', type='int', value=1)]),
                dict(id=nid + 101, name='guardia_este', type='guardia', x=35 * T + 20, y=21 * T + 30, point=True, width=0, height=0, rotation=0, visible=True,
                     properties=[dict(name='clase', type='string', value='soldado'), dict(name='dir', type='int', value=3)])]
    # Puntos de aparición
    spawn = [
        dict(id=nid, name='spawn_oeste', type='spawn', x=24 * T + 20, y=17 * T + 20, point=True, width=0, height=0, rotation=0, visible=True),
        dict(id=nid + 1, name='spawn_este', type='spawn', x=35 * T + 20, y=17 * T + 20, point=True, width=0, height=0, rotation=0, visible=True),
    ]
    for r, c in ((17, 24), (17, 35)):
        assert col[r][c] == 0, 'el punto de aparición cae en un obstáculo'
    plano = lambda m: [v for fila in m for v in fila]
    return suelo, deco, col, objetos + spawn + nonlocal_rutas + guardias, nid + 200


def escribir_json(suelo, deco, col, objetos, nextid, cols, cuenta, filas):
    plano = lambda m: [v for fila in m for v in fila]
    capa = lambda id_, nombre, datos, visible=True: dict(id=id_, name=nombre, type='tilelayer', width=W, height=H, x=0, y=0, opacity=1, visible=visible, data=plano(datos))
    mapa = dict(
        compressionlevel=-1, height=H, width=W, tileheight=T, tilewidth=T, infinite=False, orientation='orthogonal',
        renderorder='right-down', tiledversion='1.10.2', type='map', version='1.10', nextlayerid=5, nextobjectid=nextid,
        layers=[capa(1, 'suelo', suelo), capa(2, 'decoracion', deco), capa(3, 'colision', col, False),
                dict(id=4, name='objetos', type='objectgroup', draworder='topdown', x=0, y=0, opacity=1, visible=True, objects=objetos)],
        tilesets=[dict(firstgid=1, name='berlin', image='tileset.png', imagewidth=cols * T, imageheight=filas * T,
                       tilewidth=T, tileheight=T, tilecount=cuenta, columns=cols, margin=0, spacing=0,
                       tiles=[dict(id=i, properties=[dict(name='nombre', type='string', value=n)]) for i, n in enumerate(NOMBRES)])],
    )
    with open(os.path.join(OUT_DATOS, 'berlin.json'), 'w', encoding='utf-8') as f:
        json.dump(mapa, f, ensure_ascii=False, separators=(',', ':'))


def vista_previa(suelo, deco, objetos):
    ts = Image.open(os.path.join(OUT_IMG, 'tileset.png'))
    cols = ts.width // T
    img = Image.new('RGBA', (W * T, H * T))
    def tile(g): return ts.crop((((g - 1) % cols) * T, ((g - 1) // cols) * T, ((g - 1) % cols) * T + T, ((g - 1) // cols) * T + T))
    for r in range(H):
        for c in range(W):
            img.alpha_composite(tile(suelo[r][c]), (c * T, r * T))
    for r in range(H):
        for c in range(W):
            if deco[r][c]:
                img.alpha_composite(tile(deco[r][c]), (c * T, r * T))
    for o in sorted([o for o in objetos if o['type'] == 'edificio'], key=lambda o: o['y'] + o['height']):
        p = {q['name']: q['value'] for q in o['properties']}
        arch = ('cafe_%s' % ('este' if o['x'] >= 30 * T else 'oeste')) if p['tipo'] == 'cafe' else o['name']
        s = Image.open(os.path.join(OUT_ED, arch + '.png'))
        img.alpha_composite(s, (o['x'], o['y'] + o['height'] - s.height))
    for nombre, (c, r) in (('oeste', (24, 17)), ('este', (35, 17))):
        pj = personaje(PALETAS[nombre]).crop((0, 0, 32, 48))
        img.alpha_composite(pj, (c * T + 4, r * T - 20))
    tranvia = Image.open(os.path.join(OUT_IMG, 'tranvia.png'))
    img.alpha_composite(tranvia, (40 * T, 13 * T + 26 - tranvia.height))
    civ = Image.open(os.path.join(OUT_IMG, 'npc_civiles.png'))
    for k, (c, r) in enumerate(((8, 5), (14, 12), (38, 15), (47, 25))):
        img.alpha_composite(civ.crop(((k % 6) * 96, 0, (k % 6) * 96 + 32, 48)), (c * T + 4, r * T - 8))
    img.alpha_composite(Image.open(os.path.join(OUT_IMG, 'npc_soldado.png')).crop((0, 0, 32, 48)), (30 * T + 4, 8 * T))
    img.convert('RGB').save(os.path.join(RAIZ, 'herramientas', 'arte', 'vista_previa_mapa.png'))


if __name__ == '__main__':
    cols, cuenta, filas = hacer_tileset()
    hacer_personajes()
    LUCES.clear(); hacer_tranvia(); LUCES.clear()
    hacer_retratos()
    LUCES.clear(); hacer_decorados_sucesos(); LUCES.clear()
    suelo, deco, col, objetos, nextid = hacer_mapa()
    escribir_json(suelo, deco, col, objetos, nextid, cols, cuenta, filas)
    vista_previa(suelo, deco, objetos)
    print('Mapa %dx%d mosaicos (%dx%d px), %d objetos, %d obstáculos.' % (W, H, W * T, H * T, len(objetos), sum(1 for f in col for v in f if v)))
