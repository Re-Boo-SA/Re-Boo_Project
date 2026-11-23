#!/usr/bin/env python3
"""
Arte y mapas de la CAMPAÑA (Nacht über Berlin). Requiere Python 3 + Pillow; no hace falta para jugar.
    python herramientas/arte/generar_campana.py
Usa un azar propio (no cambia el mapa de Berlín) y reutiliza las funciones de generar_mundo.py.
Salidas:
  datos/campana/mapas/*.json                       escenas en formato Tiled
  Capa_Presentacion/img/campana/tileset.png        mosaicos de las escenas
  Capa_Presentacion/img/campana/pj_*.png           personajes (3 x 6 cuadros de 32x48)
  Capa_Presentacion/img/campana/retratos/*.png     retratos con emociones
  Capa_Presentacion/img/campana/*.png              objetos (autos, vagón, maleta...)
"""
import importlib.util, json, os, random
from PIL import Image, ImageDraw

AQUI = os.path.dirname(os.path.abspath(__file__))
spec = importlib.util.spec_from_file_location('gm', os.path.join(AQUI, 'generar_mundo.py'))
gm = importlib.util.module_from_spec(spec); spec.loader.exec_module(gm)
rgb, escurecer, hoja_personaje, fuente = gm.rgb, gm.escurecer, gm.hoja_personaje, gm.fuente
RAIZ = gm.RAIZ
OUT = os.path.join(RAIZ, 'Capa_Presentacion', 'img', 'campana')
OUT_MAPAS = os.path.join(RAIZ, 'datos', 'campana', 'mapas')
for d in (OUT, os.path.join(OUT, 'retratos'), OUT_MAPAS):
    os.makedirs(d, exist_ok=True)
T = 40
r = random.Random(1948)


def bloques(im, base, var, size=2):
    px = im.load()
    for y in range(0, im.height, size):
        for x in range(0, im.width, size):
            c = tuple(max(0, min(255, v + r.randint(-var, var))) for v in base)
            for dy in range(size):
                for dx in range(size):
                    if x + dx < im.width and y + dy < im.height:
                        px[x + dx, y + dy] = c + (255,)


def nuevo(base, var=6):
    im = Image.new('RGBA', (T, T)); bloques(im, rgb(base), var); return im, ImageDraw.Draw(im)


# ---------------- mosaicos ----------------
def t_adoquin_mojado():
    im, d = nuevo('#3b3a3f', 6)
    for f in range(0, T, 8):
        d.line([(0, f), (T, f)], fill=rgb('#2a292e'))
        for x in range(0 if (f // 8) % 2 == 0 else 6, T, 12):
            d.line([(x, f), (x, f + 8)], fill=rgb('#2a292e'))
    for _ in range(3):
        x, y = r.randint(2, 32), r.randint(2, 32); d.ellipse([x, y, x + 6, y + 3], fill=(120, 140, 170, 120))   # charcos
    return im
def t_vereda_noche():
    im, d = nuevo('#5d5e63', 4); d.rectangle([0, 0, T - 1, T - 1], outline=rgb('#4a4b50')); d.line([(20, 0), (20, T)], fill=rgb('#4a4b50')); return im
def t_asfalto_mojado():
    im, d = nuevo('#26272c', 5)
    for _ in range(2):
        x, y = r.randint(0, 30), r.randint(0, 34); d.ellipse([x, y, x + 10, y + 4], fill=(90, 110, 140, 110))
    return im
def t_madera():
    im, d = nuevo('#6b4a2e', 6)
    for y in range(0, T, 10):
        d.line([(0, y), (T, y)], fill=rgb('#4e3520'))
    d.line([(r.randint(5, 35), 0), (r.randint(5, 35), 10)], fill=rgb('#4e3520'))
    return im
def t_cemento():
    im, d = nuevo('#7a7b7e', 4)
    for _ in range(4):
        x, y = r.randint(0, 36), r.randint(0, 36); d.point((x, y), fill=rgb('#5d5e61'))
    return im
def t_pared():
    im, d = nuevo('#40434c', 3); d.rectangle([0, 0, T - 1, 6], fill=rgb('#5a5e69')); return im
def t_ladrillo():
    im, d = nuevo('#5a3a30', 5)
    for f in range(0, T, 8):
        d.line([(0, f), (T, f)], fill=rgb('#3d2620'))
        for x in range(0 if (f // 8) % 2 == 0 else 8, T, 16):
            d.line([(x, f), (x, f + 8)], fill=rgb('#3d2620'))
    return im
def t_grava():
    im, d = nuevo('#55524a', 9)
    for _ in range(20):
        d.point((r.randint(0, 39), r.randint(0, 39)), fill=rgb('#3a3832'))
    return im
def t_vias():
    im = t_grava(); d = ImageDraw.Draw(im)
    for x in range(2, T, 8):
        d.rectangle([x, 8, x + 3, 32], fill=rgb('#4a3a2a'))
    for y in (12, 27):
        d.rectangle([0, y, T, y + 2], fill=rgb('#8a8f99'))
    return im
def t_banco():
    im = Image.new('RGBA', (T, T), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.rectangle([1, 6, 38, 30], fill=rgb('#6b4a2e'), outline=rgb('#3a2818'))
    d.rectangle([6, 10, 14, 16], fill=rgb('#8a8f99')); d.rectangle([20, 12, 30, 14], fill=rgb('#b3262b'))   # herramientas
    return im
def t_cajon():
    im = Image.new('RGBA', (T, T), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.ellipse([4, 32, 36, 39], fill=(0, 0, 0, 80))
    d.rectangle([4, 6, 35, 34], fill=rgb('#8a6a42'), outline=rgb('#4e3a22'))
    d.line([(4, 6), (35, 34)], fill=rgb('#4e3a22')); d.line([(35, 6), (4, 34)], fill=rgb('#4e3a22'))
    return im
def t_mesa():
    im = Image.new('RGBA', (T, T), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.rectangle([0, 4, T - 1, 34], fill=rgb('#5a3e26'), outline=rgb('#33220f')); d.line([(0, 8), (T, 8)], fill=rgb('#6e4e33'))
    return im
def t_escritorio():
    im = Image.new('RGBA', (T, T), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.rectangle([0, 4, T - 1, 32], fill=rgb('#3a2a1e'), outline=rgb('#1e150e')); d.rectangle([6, 10, 18, 16], fill=rgb('#e9e4cc'))
    return im
def t_estante():
    im = Image.new('RGBA', (T, T), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.rectangle([2, 0, 37, 39], fill=rgb('#4e3a26'), outline=rgb('#2a1e12'))
    for y in (10, 22, 34):
        d.line([(2, y), (37, y)], fill=rgb('#2a1e12'))
    for x in range(5, 34, 6):
        d.rectangle([x, 3, x + 4, 9], fill=r.choice([rgb('#8a8f99'), rgb('#b3262b'), rgb('#c9a05a')]))
    return im
def t_colision():
    im = Image.new('RGBA', (T, T), (227, 38, 54, 90)); ImageDraw.Draw(im).rectangle([0, 0, T - 1, T - 1], outline=(227, 38, 54, 200)); return im
def t_puerta():
    im, d = nuevo('#4e3520', 4); d.rectangle([4, 0, 36, T - 1], outline=rgb('#2a1e12')); return im
def t_alfombra():
    im, d = nuevo('#5a2026', 4); d.rectangle([2, 2, 37, 37], outline=rgb('#c9a05a')); return im
def t_farol():
    im = Image.new('RGBA', (T, T), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.rectangle([19, 10, 21, 34], fill=rgb('#2a2d35')); d.rectangle([15, 4, 25, 11], fill=rgb('#2a2d35')); d.rectangle([16, 5, 24, 10], fill=rgb('#f4d878'))
    return im
def t_ventana():
    im, d = nuevo('#40434c', 3); d.rectangle([4, 4, 35, 30], fill=rgb('#1b2440'), outline=rgb('#5a5e69')); d.line([(20, 4), (20, 30)], fill=rgb('#5a5e69')); return im

NOMBRES = ['adoquin', 'vereda', 'asfalto', 'madera', 'cemento', 'pared', 'ladrillo', 'grava', 'vias', 'banco', 'cajon', 'mesa',
           'escritorio', 'estante', 'colision', 'puerta', 'alfombra', 'farol', 'ventana']
FAB = [t_adoquin_mojado, t_vereda_noche, t_asfalto_mojado, t_madera, t_cemento, t_pared, t_ladrillo, t_grava, t_vias, t_banco, t_cajon, t_mesa,
       t_escritorio, t_estante, t_colision, t_puerta, t_alfombra, t_farol, t_ventana]
G = {n: i + 1 for i, n in enumerate(NOMBRES)}
SOLIDOS = {'pared', 'ladrillo', 'banco', 'cajon', 'mesa', 'escritorio', 'estante', 'farol', 'ventana'}
DECO = {'banco', 'cajon', 'mesa', 'escritorio', 'estante', 'farol'}   # van en la capa de decoración, sobre el suelo


def hacer_tileset():
    cols = 8
    filas = (len(FAB) + cols - 1) // cols
    hoja = Image.new('RGBA', (cols * T, filas * T), (0, 0, 0, 0))
    for i, f in enumerate(FAB):
        hoja.paste(f(), ((i % cols) * T, (i // cols) * T))
    hoja.save(os.path.join(OUT, 'tileset.png'))
    return cols, filas


# ---------------- objetos ----------------
def auto(color, salida, capot=False):
    w, h = 120, 52
    im = Image.new('RGBA', (w, h), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.ellipse([6, h - 8, w - 6, h], fill=(0, 0, 0, 90))
    d.rounded_rectangle([2, 22, w - 3, h - 10], 10, fill=rgb(color), outline=escurecer(rgb(color), 0.5))      # carrocería "Ponton"
    d.polygon([(28, 22), (40, 6), (80, 6), (94, 22)], fill=rgb(color), outline=escurecer(rgb(color), 0.5))
    d.polygon([(33, 22), (43, 9), (59, 9), (59, 22)], fill=rgb('#4d6272')); d.polygon([(62, 22), (62, 9), (77, 9), (89, 22)], fill=rgb('#4d6272'))
    d.rectangle([w - 8, 28, w - 3, 33], fill=rgb('#f4d878')); d.rectangle([2, 28, 6, 33], fill=rgb('#b3262b'))
    d.line([(4, 34), (w - 4, 34)], fill=rgb('#c9ced6'))   # moldura cromada
    for x in (16, w - 36):
        d.ellipse([x, h - 22, x + 20, h - 4], fill=rgb('#0a0b0e')); d.ellipse([x + 5, h - 17, x + 15, h - 9], fill=rgb('#c9ced6'))
    if capot:
        d.polygon([(w - 30, 22), (w - 6, 2), (w - 2, 8), (w - 24, 26)], fill=escurecer(rgb(color), 0.8), outline=escurecer(rgb(color), 0.5))
    im.save(os.path.join(OUT, salida + '.png'))


def hacer_objetos():
    auto('#5f6b5a', 'auto_vopo')   # un Wartburg gris verdoso de la Volkspolizei
    im = Image.new('RGBA', (26, 18), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.rectangle([1, 3, 24, 16], fill=rgb('#b89a6a'), outline=rgb('#6b5a3a')); d.line([(1, 9), (24, 9)], fill=rgb('#6b5a3a')); d.line([(12, 3), (12, 16)], fill=rgb('#6b5a3a'))
    im.save(os.path.join(OUT, 'paquete.png'))
    # Capítulo 3: los papeles quemados, las fotos en la pared, la pared de nombres y el papel del final
    im = Image.new('RGBA', (60, 26), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.ellipse([2, 8, 58, 25], fill=rgb('#2a2622'))
    for _ in range(14):
        x, y = r.randint(6, 50), r.randint(9, 20); d.polygon([(x, y), (x + 6, y - 2), (x + 9, y + 3), (x + 2, y + 4)], fill=r.choice([rgb('#e9e4cc'), rgb('#8a7a5a'), rgb('#14161b')]))
    im.save(os.path.join(OUT, 'papeles_quemados.png'))
    im = Image.new('RGBA', (90, 40), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    for k, x in enumerate((4, 34, 62)):
        d.rectangle([x, 6 + (k % 2) * 4, x + 22, 32 + (k % 2) * 4], fill=rgb('#4e3a26')); d.rectangle([x + 3, 9 + (k % 2) * 4, x + 19, 29 + (k % 2) * 4], fill=rgb('#c9c2ae'))
        d.ellipse([x + 7, 12 + (k % 2) * 4, x + 15, 20 + (k % 2) * 4], fill=rgb('#8a8278'))
    im.save(os.path.join(OUT, 'fotos_pared.png'))
    im = Image.new('RGBA', (260, 110), (0, 0, 0, 0)); d = ImageDraw.Draw(im)   # trazos ilegibles: no son nombres reales
    d.rectangle([0, 0, 259, 109], fill=rgb('#d8d2c0'))
    for y in range(6, 104, 7):
        x = 6
        while x < 250:
            largo = r.randint(10, 34)
            d.line([(x, y), (min(254, x + largo), y + r.randint(-1, 1))], fill=rgb('#2b2f3a'))
            if r.random() < 0.18: d.line([(x - 2, y), (min(256, x + largo + 2), y)], fill=rgb('#b3262b'), width=2)   # tachado
            x += largo + r.randint(5, 10)
    d.rectangle([92, 46, 168, 62], fill=rgb('#d8d2c0')); d.rectangle([92, 46, 168, 62], outline=rgb('#b3262b'), width=2)
    im.save(os.path.join(OUT, 'pared_nombres.png'))
    im = Image.new('RGBA', (16, 12), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.rectangle([0, 0, 15, 11], fill=rgb('#efe9d8'), outline=rgb('#8a7a5a')); d.line([(3, 4), (12, 4)], fill=rgb('#2b2f3a')); d.line([(3, 7), (10, 7)], fill=rgb('#2b2f3a'))
    im.save(os.path.join(OUT, 'papel.png'))
    im = Image.new('RGBA', (22, 16), (0, 0, 0, 0)); d = ImageDraw.Draw(im)   # Cap. 5: un documento sobre la mesa
    d.rectangle([1, 1, 20, 14], fill=rgb('#e9e4cc'), outline=rgb('#8a7a5a')); d.line([(4, 5), (16, 5)], fill=rgb('#2b2f3a')); d.line([(4, 8), (14, 8)], fill=rgb('#2b2f3a')); d.ellipse([14, 9, 19, 13], outline=rgb('#b3262b'))
    im.save(os.path.join(OUT, 'documento.png'))
    # Capítulo 6: la locomotora a vapor (la Reichsbahn todavía usaba muchas en 1961) y las palancas de las agujas
    im = Image.new('RGBA', (220, 80), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.ellipse([10, 70, 210, 80], fill=(0, 0, 0, 90))
    d.rectangle([20, 28, 160, 62], fill=rgb('#14161b'), outline=rgb('#0a0b0e'))          # caldera
    d.rectangle([150, 12, 212, 62], fill=rgb('#1b1e25'), outline=rgb('#0a0b0e'))          # cabina
    d.rectangle([160, 20, 178, 36], fill=rgb('#f4d878')); d.rectangle([186, 20, 204, 36], fill=rgb('#3a3f4a'))
    d.rectangle([40, 8, 56, 28], fill=rgb('#14161b')); d.rectangle([90, 16, 104, 28], fill=rgb('#2a2d35'))   # chimenea y domo
    d.rectangle([20, 58, 212, 62], fill=rgb('#b3262b'))                                     # bastidor rojo
    for x in (36, 72, 108, 144):
        d.ellipse([x - 14, 52, x + 14, 78], fill=rgb('#2a2d35'), outline=rgb('#b3262b')); d.ellipse([x - 3, 62, x + 3, 68], fill=rgb('#c9ced6'))
    d.text((112, 36), 'DR', font=fuente(12), fill=rgb('#e9e4cc'))
    im.save(os.path.join(OUT, 'locomotora.png'))
    im = Image.new('RGBA', (20, 34), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.ellipse([2, 28, 18, 34], fill=(0, 0, 0, 80)); d.rectangle([4, 22, 16, 30], fill=rgb('#3a3f4a'))
    d.line([(10, 24), (15, 4)], fill=rgb('#c9ced6'), width=3); d.ellipse([12, 1, 18, 7], fill=rgb('#b3262b'))
    im.save(os.path.join(OUT, 'palanca.png'))
    # Acto III: el alambre de púas sobre caballetes, un camión militar y el cartel del S-Bahn
    im = Image.new('RGBA', (80, 40), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    for x in (10, 70):
        d.line([(x - 8, 38), (x + 8, 6)], fill=rgb('#5e452c'), width=3); d.line([(x - 8, 6), (x + 8, 38)], fill=rgb('#5e452c'), width=3)
    for y in (10, 18, 26):
        pts = [(2 + i * 4, y + (2 if i % 2 else -2)) for i in range(20)]
        d.line(pts, fill=rgb('#9aa0aa'), width=1)
        for (px, py) in pts[::2]: d.line([(px - 1, py - 2), (px + 1, py + 2)], fill=rgb('#c9ced6'))
    im.save(os.path.join(OUT, 'alambre.png'))
    im = Image.new('RGBA', (170, 74), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.ellipse([8, 64, 162, 74], fill=(0, 0, 0, 90))
    d.rectangle([4, 8, 120, 56], fill=rgb('#4e5640'), outline=rgb('#3a4030'))                 # la caja con lona
    for x in range(14, 120, 18): d.line([(x, 8), (x, 56)], fill=rgb('#454c38'))
    d.rectangle([122, 22, 166, 56], fill=rgb('#5b6447'), outline=rgb('#3a4030')); d.rectangle([132, 26, 160, 38], fill=rgb('#9fb4c8'))
    for x in (28, 96, 146): d.ellipse([x - 12, 48, x + 12, 72], fill=rgb('#1b1e25')); d.ellipse([x - 4, 56, x + 4, 64], fill=rgb('#5a5f6a'))
    im.save(os.path.join(OUT, 'camion.png'))
    im = Image.new('RGBA', (40, 54), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.rectangle([18, 20, 22, 54], fill=rgb('#3a3f4a')); d.ellipse([2, 0, 38, 30], fill=rgb('#2f7a3a'), outline=rgb('#e9e4cc'), width=2)
    d.text((13, 7), 'S', font=fuente(14), fill=rgb('#e9e4cc'))
    im.save(os.path.join(OUT, 'cartel_sbahn.png'))
    im = Image.new('RGBA', (60, 56), (0, 0, 0, 0)); d = ImageDraw.Draw(im)   # Cap. 10: el horario viejo de la estación
    d.rectangle([27, 30, 33, 56], fill=rgb('#3a3f4a')); d.rectangle([2, 0, 58, 34], fill=rgb('#e9e4cc'), outline=rgb('#14161b'), width=2)
    d.rectangle([4, 2, 56, 8], fill=rgb('#14161b'))
    for y in range(12, 32, 4): d.line([(6, y), (54, y)], fill=rgb('#8a7a5a'))
    d.line([(4, 32), (56, 4)], fill=rgb('#b3262b'), width=2)
    im.save(os.path.join(OUT, 'horario.png'))
    im = Image.new('RGBA', (24, 14), (0, 0, 0, 0)); d = ImageDraw.Draw(im)   # Cap. 16: el estuche de ganzúas de Thomas
    d.rounded_rectangle([1, 2, 22, 12], 3, fill=rgb('#5e3a22'), outline=rgb('#3a2414'))
    for x in (6, 10, 14, 18): d.line([(x, 4), (x, 10)], fill=rgb('#c9ced6'))
    im.save(os.path.join(OUT, 'estuche.png'))
    # Final C: la pared cubierta de carteles de personas desaparecidas
    im = Image.new('RGBA', (160, 70), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    for k in range(18):
        x, y = 4 + (k % 6) * 26 + r.randint(-2, 2), 2 + (k // 6) * 22 + r.randint(-1, 2)
        d.rectangle([x, y, x + 21, y + 19], fill=rgb('#e9e4cc') if k % 4 else rgb('#d8d2c0'), outline=rgb('#8a7a5a'))
        d.rectangle([x + 6, y + 3, x + 15, y + 11], fill=rgb('#8a8278')); d.line([(x + 3, y + 15), (x + 18, y + 15)], fill=rgb('#2b2f3a'))
    im.save(os.path.join(OUT, 'carteles.png'))
    auto('#2f3a33', 'auto_viktor');
    # Cap. 11: el auto con las puertas abiertas y sangre en el asiento de atrás
    auto('#3a3f4a', 'auto_abierto')
    im = Image.open(os.path.join(OUT, 'auto_abierto.png')).convert('RGBA'); d = ImageDraw.Draw(im)
    w, h = im.size
    for x0 in (int(w * 0.30), int(w * 0.52)):                       # las dos puertas abiertas, hacia afuera
        d.polygon([(x0, int(h * 0.35)), (x0 + int(w * 0.16), int(h * 0.35)), (x0 + int(w * 0.20), int(h * 0.12)), (x0 + int(w * 0.04), int(h * 0.12))], fill=rgb('#2e333c'), outline=rgb('#14161b'))
    d.ellipse([int(w * 0.55), int(h * 0.42), int(w * 0.66), int(h * 0.56)], fill=rgb('#6e0f14'))     # la mancha en el asiento de atrás
    im.save(os.path.join(OUT, 'auto_abierto.png'))
    auto('#2a2c31', 'auto_vigilado'); auto('#3e5a78', 'auto_taller', capot=True)
    im = Image.new('RGBA', (220, 70), (0, 0, 0, 0)); d = ImageDraw.Draw(im)       # vagón de carga
    d.ellipse([10, 60, 210, 70], fill=(0, 0, 0, 90))
    d.rectangle([4, 4, 215, 56], fill=rgb('#5a3a26'), outline=rgb('#2e1d12'))
    for x in range(14, 210, 20):
        d.line([(x, 6), (x, 54)], fill=rgb('#4a2e1e'))
    d.rectangle([90, 14, 130, 52], fill=rgb('#4a2e1e'), outline=rgb('#2e1d12'))
    d.text((40, 18), 'DR', font=fuente(14), fill=rgb('#e9e4cc'))
    for x in (24, 60, 160, 196):
        d.ellipse([x - 8, 52, x + 8, 66], fill=rgb('#14161b'))
    im.save(os.path.join(OUT, 'vagon.png'))
    im = Image.new('RGBA', (28, 22), (0, 0, 0, 0)); d = ImageDraw.Draw(im)       # maleta
    d.rounded_rectangle([1, 5, 26, 21], 3, fill=rgb('#6b4a2b'), outline=rgb('#33220f')); d.rectangle([10, 1, 17, 5], outline=rgb('#33220f')); d.line([(1, 12), (26, 12)], fill=rgb('#c9a05a'))
    im.save(os.path.join(OUT, 'maleta.png'))
    im = Image.new('RGBA', (20, 14), (0, 0, 0, 0)); d = ImageDraw.Draw(im)       # sobre
    d.rectangle([0, 0, 19, 13], fill=rgb('#d9cfae'), outline=rgb('#8a7a5a')); d.line([(0, 0), (10, 7), (19, 0)], fill=rgb('#8a7a5a'))
    im.save(os.path.join(OUT, 'sobre.png'))
    im = Image.new('RGBA', (24, 16), (0, 0, 0, 0)); d = ImageDraw.Draw(im)       # teléfono
    d.rounded_rectangle([2, 6, 21, 15], 3, fill=rgb('#14161b')); d.rounded_rectangle([0, 0, 23, 6], 3, fill=rgb('#14161b')); d.ellipse([8, 8, 15, 14], outline=rgb('#8a8f99'))
    im.save(os.path.join(OUT, 'telefono.png'))
    im = Image.new('RGBA', (16, 20), (0, 0, 0, 0)); d = ImageDraw.Draw(im)       # libreta negra
    d.rectangle([1, 1, 14, 18], fill=rgb('#14161b'), outline=rgb('#3a3d44')); d.line([(3, 1), (3, 18)], fill=rgb('#3a3d44'))
    im.save(os.path.join(OUT, 'libreta.png'))


# ---------------- personajes ----------------
PERSONAJES = {
    # id: (abrigo, abrigo2, piel, pelo, gorro, pantalón)
    'viktor': ('#3a3f4a', '#2a2e37', '#e0b48a', '#2b1d14', 0, '#22252c'),
    'friedrich': ('#5a4632', '#463624', '#f1c9a5', '#c9a05a', 0, '#2b2f3a'),
    'thomas': ('#3e5a78', '#2f4660', '#f1c9a5', '#6b4a2b', 1, '#2f4660'),
    'anneliese': ('#1e1e24', '#141418', '#f1c9a5', '#3a2a1e', 0, '#1e1e24'),
    'falk': ('#4a4d55', '#383a40', '#e0b48a', '#8a8a8a', 0, '#383a40'),
    'maton': ('#26282e', '#1b1c20', '#d6a77a', '#2b1d14', 2, '#1b1c20'),
    'ingrid': ('#5c3d4a', '#472f39', '#8d5a3b', '#c9a05a', 0, '#2b2f3a'),
    'otto': ('#6b5a3a', '#54452c', '#f1c9a5', '#8a6a42', 2, '#3a3226'),        # el sobrino pobre: saco gastado y sombrero
    'trapo': ('#2f3a55', '#232c42', '#e0b48a', '#3a2a1e', 1, '#232c42'),       # Transportpolizei: uniforme azul oscuro y gorra
    'cliente': ('#4a2e3a', '#3a2430', '#d6a77a', '#2b1d14', 0, '#2b2f3a'),
    'agente': ('#3f4248', '#2f3237', '#e0b48a', '#2b1d14', 2, '#2a2c31'),     # hombres de civil: sobretodo gris y sombrero
    'guardia': ('#5a6050', '#474c3f', '#f1c9a5', '#3a2a1e', 1, '#3b3f35'),    # guardias del edificio: uniforme gris verdoso y gorra
    'vuniforme': ('#5a6050', '#474c3f', '#e0b48a', '#2b1d14', 1, '#3b3f35'),  # Viktor con el uniforme robado
    'tuniforme': ('#5a6050', '#474c3f', '#f1c9a5', '#6b4a2b', 1, '#3b3f35'),  # Thomas con el uniforme robado
    'vferroviario': ('#1f2a40', '#172033', '#e0b48a', '#2b1d14', 1, '#172033'),  # Viktor con el uniforme azul oscuro de la Reichsbahn
    'anciano': ('#5b5246', '#4a4238', '#e8c6a8', '#d9d9d9', 0, '#3a352d'),      # el compañero del padre de Anneliese
    'prisionero': ('#6b6a62', '#55544d', '#d6a77a', '#3a2a1e', 0, '#4a4943'),
    'kampf': ('#6b6f5a', '#55594a', '#e8c6a8', '#3a2a1e', 1, '#4a4d3e'),      # Kampfgruppen der Arbeiterklasse: uniforme gris oliva y gorra
    'mujer': ('#8a4a5a', '#6e3a48', '#f1c9a5', '#6b4a2b', 0, '#3a2a30'),
    'vecino': ('#5a5446', '#47423a', '#d6a77a', '#8a8a8a', 2, '#2b2f3a'),
    'muchacho': ('#3d5a7a', '#2e4660', '#f1c9a5', '#c9a05a', 0, '#2b2f3a'),
    'lukas': ('#4a4038', '#3a322b', '#e8c6a8', '#6b4a2b', 0, '#2b2f3a'),       # Lukas Brandt: ropa sucia, varios días sin dormir
    'martin': ('#6e6e6e', '#5a5a5a', '#e0b48a', '#5e5248', 0, '#2b2f3a'),      # Martin Adler, unos 45 años, abrigo gris
    'paul': ('#1f3a5c', '#172c47', '#f1c9a5', '#2b1d14', 1, '#22252c'),       # Paul Lange joven, ropa de trabajo de la BVG
    'padre': ('#5e452c', '#4a3622', '#e8c6a8', '#9a9a9a', 0, '#2b2f3a'),      # el padre de Viktor (solo se lo escucha, en la grabación)
    'weiss': ('#c8c4b8', '#a8a498', '#f1c9a5', '#bdbdbd', 0, '#8a867a'),      # Konrad Weiss: el traje occidental claro de la foto del tren
    'invitado': ('#22252c', '#1a1c22', '#e8c6a8', '#3a2a1e', 0, '#1a1c22'),   # invitados a la cena: trajes oscuros
    'samuel': ('#6b5a44', '#574836', '#e0b48a', '#a8a8a8', 2, '#3a3f4a'),     # Samuel Hartmann, antiguo agente occidental (el hermano de Gertrud)
    'trabajador': ('#5a5f66', '#484c52', '#d6a77a', '#3a2a1e', 0, '#3a3f46'),  # los trabajadores de la instalación
    'voz': ('#14161b', '#0e0f12', '#2a2c31', '#14161b', 2, '#0e0f12'),         # «una voz» por teléfono (final B): una silueta en sombras
}


def retrato(abrigo, abrigo2, piel, pelo, gorro, emocion):
    piel, pelo, ab, ab2 = rgb(piel), rgb(pelo), rgb(abrigo), rgb(abrigo2)
    im = Image.new('RGBA', (32, 32), (0, 0, 0, 0)); d = ImageDraw.Draw(im)
    d.ellipse([1, 1, 30, 30], fill=rgb('#1b2440'))
    d.rectangle([6, 24, 25, 31], fill=ab); d.rectangle([6, 29, 25, 31], fill=ab2)
    d.polygon([(13, 24), (16, 28), (19, 24)], fill=rgb('#e9e4cc'))
    d.rectangle([14, 21, 17, 24], fill=escurecer(piel, 0.9)); d.rectangle([10, 8, 21, 21], fill=piel)
    d.rectangle([9, 12, 9, 16], fill=escurecer(piel, 0.9)); d.rectangle([22, 12, 22, 16], fill=escurecer(piel, 0.9))
    d.rectangle([10, 6, 21, 10], fill=pelo); d.rectangle([10, 10, 11, 14], fill=pelo); d.rectangle([20, 10, 21, 14], fill=pelo)
    ojo, ceja, boca = (20, 20, 24), escurecer(pelo, 0.8), escurecer(piel, 0.6)
    d.rectangle([12, 13, 13, 14], fill=ojo); d.rectangle([18, 13, 19, 14], fill=ojo)
    if emocion == 'contento':
        d.line([(12, 11), (14, 11)], fill=ceja); d.line([(17, 11), (19, 11)], fill=ceja); d.line([(13, 17), (18, 17)], fill=boca); d.point((12, 16), fill=boca); d.point((19, 16), fill=boca)
    elif emocion == 'enojado':
        d.line([(12, 10), (14, 12)], fill=ceja); d.line([(17, 12), (19, 10)], fill=ceja); d.line([(13, 18), (18, 18)], fill=boca); d.point((12, 19), fill=boca); d.point((19, 19), fill=boca)
    elif emocion == 'triste':
        d.line([(12, 12), (14, 10)], fill=ceja); d.line([(17, 10), (19, 12)], fill=ceja); d.line([(14, 18), (17, 18)], fill=boca); d.point((13, 19), fill=boca); d.point((18, 19), fill=boca)
    elif emocion == 'dolor':
        d.line([(12, 10), (14, 12)], fill=ceja); d.line([(17, 12), (19, 10)], fill=ceja); d.rectangle([12, 13, 13, 13], fill=ojo); d.rectangle([18, 13, 19, 13], fill=ojo)
        d.rectangle([13, 17, 18, 19], fill=rgb('#3a1418'))
    else:
        d.line([(12, 11), (14, 11)], fill=ceja); d.line([(17, 11), (19, 11)], fill=ceja); d.line([(14, 18), (17, 18)], fill=boca)
    if gorro == 1:
        c = rgb('#3b3f47'); d.rectangle([10, 4, 21, 7], fill=c); d.rectangle([9, 8, 23, 8], fill=escurecer(c))
    elif gorro == 2:
        c = rgb('#2a2622'); d.rectangle([11, 2, 20, 7], fill=c); d.rectangle([7, 8, 24, 8], fill=escurecer(c))
    return im.resize((96, 96), Image.NEAREST)


def hacer_personajes():
    for pid, (ab, ab2, piel, pelo, gorro, pant) in PERSONAJES.items():
        hoja_personaje(ab, ab2, piel, pelo, gorro, pantalon=pant).save(os.path.join(OUT, 'pj_%s.png' % pid))
        for emo in ('neutral', 'contento', 'enojado', 'triste', 'dolor'):
            retrato(ab, ab2, piel, pelo, gorro, emo).save(os.path.join(OUT, 'retratos', '%s_%s.png' % (pid, emo)))


# ---------------- mapas (24 x 16 mosaicos) ----------------
W, H = 24, 16


def mapa_vacio(base):
    return [[G[base]] * W for _ in range(H)], [[0] * W for _ in range(H)]


def poner(suelo, deco, c, f, nombre):
    if nombre in DECO:
        deco[f][c] = G[nombre]
    else:
        suelo[f][c] = G[nombre]


def colision(suelo, deco, extra=()):
    col = [[0] * W for _ in range(H)]
    for f in range(H):
        for c in range(W):
            nombres = {NOMBRES[suelo[f][c] - 1]} | ({NOMBRES[deco[f][c] - 1]} if deco[f][c] else set())
            if nombres & SOLIDOS:
                col[f][c] = G['colision']
    for (c, f) in extra:
        col[f][c] = G['colision']
    return col


def punto(nombre, tipo, x, y, **props):
    o = dict(id=0, name=nombre, type=tipo, x=x, y=y, point=True, width=0, height=0, rotation=0, visible=True)
    if props:
        o['properties'] = [dict(name=k, type='string', value=json.dumps(v) if not isinstance(v, str) else v) for k, v in props.items()]
    return o


def guardar(nombre, suelo, deco, col, objetos, cols, filas, noche=True, lluvia=False):
    plano = lambda m: [v for fila in m for v in fila]
    for i, o in enumerate(objetos):
        o['id'] = i + 1
    capa = lambda i, n, dat, vis=True: dict(id=i, name=n, type='tilelayer', width=W, height=H, x=0, y=0, opacity=1, visible=vis, data=plano(dat))
    mapa = dict(compressionlevel=-1, height=H, width=W, tileheight=T, tilewidth=T, infinite=False, orientation='orthogonal', renderorder='right-down',
                tiledversion='1.10.2', type='map', version='1.10', nextlayerid=5, nextobjectid=len(objetos) + 1,
                properties=[dict(name='noche', type='bool', value=noche), dict(name='lluvia', type='bool', value=lluvia)],
                layers=[capa(1, 'suelo', suelo), capa(2, 'decoracion', deco), capa(3, 'colision', col, False),
                        dict(id=4, name='objetos', type='objectgroup', draworder='topdown', x=0, y=0, opacity=1, visible=True, objects=objetos)],
                tilesets=[dict(firstgid=1, name='campana', image='tileset.png', imagewidth=cols * T, imageheight=filas * T, tilewidth=T, tileheight=T,
                               tilecount=len(FAB), columns=cols, margin=0, spacing=0,
                               tiles=[dict(id=i, properties=[dict(name='nombre', type='string', value=n)]) for i, n in enumerate(NOMBRES)])])
    json.dump(mapa, open(os.path.join(OUT_MAPAS, nombre + '.json'), 'w', encoding='utf-8'), ensure_ascii=False, separators=(',', ':'))
    return mapa


def mapas(cols, filas):
    # 1) La calle de Berlín Occidental bajo la lluvia
    s, d = mapa_vacio('ladrillo')
    for c in range(W):
        s[6][c] = G['vereda']; s[10][c] = G['vereda']
        for f in (7, 8, 9):
            s[f][c] = G['asfalto']
    s[11][18] = G['puerta']
    for c in (4, 13):
        poner(s, d, c, 6, 'farol')
    poner(s, d, 9, 10, 'farol'); poner(s, d, 20, 10, 'farol')
    for c in (2, 6, 10, 16, 21):
        s[5][c] = G['ventana']
    guardar('calle', s, d, colision(s, d), [
        punto('auto', 'prop', -80, 352, imagen='auto_viktor'), punto('llegada', 'marca', 690, 352), punto('auto_vopo', 'prop', -300, 352, imagen='auto_vopo'),
        punto('viktor', 'actor', 700, 420), punto('friedrich', 'actor', 650, 420), punto('puerta_taller', 'marca', 740, 450),
        punto('thomas', 'actor', 600, 420), punto('anneliese', 'actor', 760, 420),
        punto('lukas', 'actor', 470, 330), punto('agente', 'actor', 60, 300), punto('escucha', 'marca', 250, 410),   # Cap. 9: Thomas sigue a Lukas
        punto('auto_abierto', 'prop', 330, 352, imagen='auto_abierto'), punto('frente_auto', 'marca', 330, 400),   # Cap. 11: el secuestro de Thomas
        punto('martin', 'actor', 640, 425),   # Cap. 12: Martin herido
        punto('carteles', 'prop', 520, 238, imagen='carteles'), punto('frente_carteles', 'marca', 520, 290),   # Final C
        punto('ruta_lukas', 'ruta', 0, 0, puntos=[[470, 330], [300, 330], [300, 380], [160, 380], [100, 330]], modo='ida'),
        punto('luz_1', 'luz', 180, 250), punto('luz_2', 'luz', 540, 250), punto('luz_3', 'luz', 380, 410), punto('luz_4', 'luz', 820, 410),
    ], cols, filas, noche=True, lluvia=True)

    # 2) El taller de Thomas
    s, d = mapa_vacio('cemento')
    for c in range(W):
        s[0][c] = G['pared']; s[15][c] = G['pared']
    for f in range(H):
        s[f][0] = G['pared']; s[f][23] = G['pared']
    s[15][11] = G['puerta']; s[15][12] = G['puerta']
    for c in range(3, 10):
        poner(s, d, c, 1, 'banco')
    for f in range(2, 9):
        poner(s, d, 22, f, 'estante')
    for c in (14, 15):
        for f in (9, 10):
            poner(s, d, c, f, 'mesa')
    s[0][17] = G['ventana']
    guardar('taller', s, d, colision(s, d, extra=[(c, f) for c in range(4, 8) for f in range(6, 8)]), [
        punto('auto_taller', 'prop', 250, 320, imagen='auto_taller'), punto('sobre', 'prop', 600, 368, imagen='sobre'),
        punto('thomas', 'actor', 400, 300), punto('viktor', 'actor', 470, 560), punto('friedrich', 'actor', 420, 570),
        punto('anneliese_entra', 'marca', 470, 630), punto('anneliese', 'marca', 540, 480), punto('mesa', 'marca', 600, 440),
        punto('luz_1', 'luz', 300, 120), punto('luz_2', 'luz', 600, 360), punto('luz_3', 'luz', 480, 560),
    ], cols, filas, noche=True)

    # 3) La estación de mercancías (sigilo, tiroteo y persecución)
    s, d = mapa_vacio('grava')
    for c in range(W):
        s[3][c] = G['vias']; s[12][c] = G['vias']
    # Los vagones ocupan dos filas en el dibujo: las dos son obstáculo (si no, se camina por el techo)
    extra = [(c, f) for c in range(2, 7) for f in (2, 3)] + [(c, f) for c in range(12, 17) for f in (11, 12)] + [(c, 7) for c in range(19, 22)]
    for (c, f) in [(9, 7), (10, 7), (14, 5), (14, 6), (6, 9), (17, 9), (18, 9), (12, 9), (4, 6), (20, 10)]:
        poner(s, d, c, f, 'cajon')
    for c in (1, 11, 22):
        poner(s, d, c, 14, 'farol')
    poner(s, d, 16, 2, 'farol')
    guardar('estacion', s, d, colision(s, d, extra=extra), [
        punto('vagon_1', 'prop', 180, 160, imagen='vagon'), punto('vagon_2', 'prop', 580, 520, imagen='vagon'),
        punto('auto_vigilado', 'prop', 820, 318, imagen='auto_vigilado'), punto('maleta', 'prop', 800, 360, imagen='maleta'),
        punto('auto_viktor', 'prop', 110, 600, imagen='auto_viktor'),
        punto('viktor', 'actor', 230, 560), punto('friedrich', 'actor', 160, 520), punto('thomas', 'actor', 60, 520), punto('anneliese', 'actor', 230, 620),
        punto('maton_1', 'actor', 700, 200), punto('maton_2', 'actor', 460, 120),
        punto('maton_3', 'actor', 900, 470), punto('maton_4', 'actor', 940, 250), punto('maton_5', 'actor', 940, 550),
        punto('ruta_maton_1', 'ruta', 0, 0, puntos=[[700, 200], [900, 200], [900, 460], [700, 460]], modo='vuelta'),
        punto('ruta_maton_2', 'ruta', 0, 0, puntos=[[460, 120], [460, 470]], modo='ida_vuelta'),
        punto('meta_maleta', 'marca', 790, 380), punto('salida', 'marca', 130, 590),
        punto('luz_1', 'luz', 60, 560), punto('luz_2', 'luz', 460, 560), punto('luz_3', 'luz', 900, 560), punto('luz_4', 'luz', 660, 90),
    ], cols, filas, noche=True, lluvia=True)

    # 4) El escondite
    s, d = mapa_vacio('madera')
    for c in range(W):
        s[0][c] = G['pared']; s[15][c] = G['pared']
    for f in range(H):
        s[f][0] = G['pared']; s[f][23] = G['pared']
    for c in range(10, 14):
        for f in (7, 8):
            poner(s, d, c, f, 'mesa')
    s[0][6] = G['ventana']; s[0][17] = G['ventana']
    guardar('escondite', s, d, colision(s, d), [
        punto('maleta', 'prop', 470, 318, imagen='maleta'), punto('libreta', 'prop', 520, 318, imagen='libreta'), punto('paquete', 'prop', 500, 318, imagen='paquete'),
        punto('viktor', 'actor', 480, 400), punto('friedrich', 'actor', 360, 300), punto('thomas', 'actor', 600, 300), punto('anneliese', 'actor', 580, 400),
        punto('mesa', 'marca', 490, 380), punto('luz_1', 'luz', 480, 320),
    ], cols, filas, noche=True)

    # 5) La oficina de Erich Falk, en Berlín Oriental
    s, d = mapa_vacio('alfombra')
    for c in range(W):
        s[0][c] = G['pared']; s[15][c] = G['pared']
        for f in range(1, 15):
            if c < 6 or c > 17:
                s[f][c] = G['madera']
    for f in range(H):
        s[f][0] = G['pared']; s[f][23] = G['pared']
    for c in range(10, 14):
        poner(s, d, c, 5, 'escritorio')
    s[0][11] = G['ventana']; s[0][12] = G['ventana']
    guardar('oficina_falk', s, d, colision(s, d), [
        punto('telefono', 'prop', 520, 214, imagen='telefono'), punto('falk', 'actor', 480, 180), punto('luz_1', 'luz', 480, 220),
    ], cols, filas, noche=True)

    # 6) 1975: la redacción de Ingrid (el marco: abre la caja)
    s, d = mapa_vacio('madera')
    for c in range(W):
        s[0][c] = G['pared']; s[15][c] = G['pared']
    for f in range(H):
        s[f][0] = G['pared']; s[f][23] = G['pared']
    for c in range(9, 15):
        poner(s, d, c, 6, 'escritorio')
    for f in range(2, 10):
        poner(s, d, 2, f, 'estante')
    guardar('redaccion_1975', s, d, colision(s, d), [
        punto('caja', 'prop', 470, 230, imagen='maleta'), punto('ingrid', 'actor', 470, 200), punto('yo', 'actor', 470, 360), punto('luz_1', 'luz', 470, 230),
    ], cols, filas, noche=False)


def mapas_acto_1(cols, filas):
    # Capítulo 1: el Club Silberfisch (ficticio), un club de jazz de Kreuzberg
    s, d = mapa_vacio('madera')
    for c in range(W):
        s[0][c] = G['pared']; s[15][c] = G['pared']
    for f in range(H):
        s[f][0] = G['pared']; s[f][23] = G['pared']
    for c in range(16, 23):
        s[f][c] = s[f][c]
    for f in range(1, 7):                        # la trastienda (pared con una puerta)
        s[f][16] = G['pared']
    for c in range(16, 23):                      # ...y cerrada por abajo: se entra solo por la puerta
        s[7][c] = G['pared']
    s[4][16] = G['puerta']
    for c in range(17, 23):
        for f in range(1, 7):
            s[f][c] = G['alfombra']
    for c in range(2, 8):                         # la barra
        poner(s, d, c, 2, 'mesa')
    for f in range(3, 9):
        poner(s, d, 22, f + 6, 'estante')
    for (c, f) in [(4, 8), (8, 9), (12, 7), (12, 11), (5, 12)]:   # mesas del salón
        poner(s, d, c, f, 'mesa')
    for c in (19, 20):                            # la mesa de apuestas de Otto
        poner(s, d, c, 3, 'mesa')
    guardar('club', s, d, colision(s, d), [
        punto('viktor', 'actor', 300, 560), punto('friedrich', 'actor', 250, 565), punto('anneliese', 'actor', 350, 565),
        punto('maton', 'actor', 620, 200), punto('otto', 'actor', 790, 100), punto('cliente_1', 'actor', 180, 400), punto('cliente_2', 'actor', 520, 330),
        punto('cliente_3', 'actor', 520, 500), punto('cliente_4', 'actor', 230, 560), punto('trastienda', 'marca', 620, 190), punto('mesa_otto', 'marca', 790, 170),
        punto('dentro', 'marca', 720, 190),
        punto('luz_1', 'luz', 200, 120), punto('luz_2', 'luz', 480, 400), punto('luz_3', 'luz', 790, 150), punto('luz_4', 'luz', 260, 520),
    ], cols, filas, noche=True)

    # Capítulo 3: la casa de los muertos (un departamento vacío en Berlín Oriental, de madrugada)
    s, d = mapa_vacio('madera')
    for c in range(W):
        s[0][c] = G['pared']; s[15][c] = G['pared']
    for f in range(H):
        s[f][0] = G['pared']; s[f][23] = G['pared']; s[f][15] = G['pared']   # la pared de la habitación cerrada
    s[7][15] = G['puerta']; s[15][11] = G['puerta']
    extra_cerrada = [(15, 7)]                    # cerrada: Thomas la abre, pero Viktor entra en la cinemática
    for f in (5, 6):
        s[f][0] = G['ventana']                   # la ventana por donde se escapan
    for c in range(16, 23):
        for f in range(1, 15):
            s[f][c] = G['cemento']
    for (c, f) in [(6, 9), (7, 9), (2, 12), (3, 12)]:
        poner(s, d, c, f, 'mesa')
    for f in range(9, 14):
        poner(s, d, 13, f, 'estante')
    guardar('casa_muertos', s, d, colision(s, d, extra=extra_cerrada), [
        punto('fotos', 'prop', 380, 80, imagen='fotos_pared'), punto('papeles', 'prop', 300, 470, imagen='papeles_quemados'),
        punto('pared_nombres', 'prop', 780, 150, imagen='pared_nombres'), punto('papel', 'prop', 420, 560, imagen='papel'),
        punto('viktor', 'actor', 460, 580), punto('friedrich', 'actor', 400, 580), punto('thomas', 'actor', 520, 580), punto('anneliese', 'actor', 560, 580),
        punto('agente_1', 'actor', 440, 600), punto('agente_2', 'actor', 500, 600),
        punto('puerta_cerrada', 'marca', 580, 300), punto('thomas_puerta', 'marca', 560, 340), punto('frente_pared', 'marca', 780, 240), punto('salon', 'marca', 520, 300),
        punto('ventana', 'marca', 70, 230), punto('entrada', 'marca', 460, 600), punto('friedrich_atrapado', 'marca', 300, 360),
        punto('agente_1_atrapa', 'marca', 270, 360), punto('agente_2_atrapa', 'marca', 330, 380),
        punto('luz_1', 'luz', 380, 140), punto('luz_2', 'luz', 780, 200), punto('luz_3', 'luz', 460, 560),
    ], cols, filas, noche=True)

    # Capítulo 4: la sala de interrogatorio (un edificio del gobierno en Berlín Oriental)
    s, d = mapa_vacio('cemento')
    for c in range(W):
        for f in range(H):
            if c < 7 or c > 16 or f < 3 or f > 12: s[f][c] = G['pared']
    s[12][11] = G['puerta']
    for c in (11, 12):
        poner(s, d, c, 7, 'escritorio')
    guardar('sala_interrogatorio', s, d, colision(s, d), [
        punto('friedrich', 'actor', 470, 390), punto('falk', 'actor', 480, 270), punto('guardia', 'actor', 420, 470),
        punto('viktor', 'actor', 480, 270), punto('anneliese', 'actor', 560, 300), punto('falk_silla', 'marca', 470, 390),
        punto('puerta', 'marca', 460, 480), punto('luz_1', 'luz', 480, 300),
    ], cols, filas, noche=True)

    # Capítulo 4: el pasillo del edificio, con las celdas (sigilo y tiroteo)
    s, d = mapa_vacio('cemento')
    for c in range(W):
        for f in list(range(0, 5)) + list(range(11, H)):
            s[f][c] = G['pared']
    for c in range(2, 22, 4):                       # puertas de oficinas y celdas
        s[4][c] = G['puerta']; s[11][c + 1] = G['puerta']
    for f in range(5, 11):
        s[f][0] = G['puerta'] if f in (7, 8) else G['pared']; s[f][23] = G['pared']
    for (c, f) in [(5, 6), (6, 6), (10, 9), (11, 9), (15, 6), (16, 6), (19, 9)]:
        poner(s, d, c, f, 'escritorio')
    for (c, f) in [(8, 8), (13, 7), (18, 7), (21, 6)]:
        poner(s, d, c, f, 'cajon')
    guardar('edificio', s, d, colision(s, d), [
        punto('vuniforme', 'actor', 60, 330), punto('tuniforme', 'actor', 60, 380), punto('friedrich', 'actor', 900, 270),
        punto('guardia_1', 'actor', 300, 230), punto('guardia_2', 'actor', 580, 400), punto('guardia_3', 'actor', 820, 400),
        punto('ruta_guardia_1', 'ruta', 0, 0, puntos=[[300, 230], [480, 230], [480, 300], [300, 300]], modo='vuelta'),
        punto('ruta_guardia_2', 'ruta', 0, 0, puntos=[[580, 400], [580, 330], [700, 330], [700, 400]], modo='vuelta'),
        punto('celda', 'marca', 900, 300), punto('salida', 'marca', 50, 340), punto('afuera', 'marca', 20, 360),
        punto('luz_1', 'luz', 180, 300), punto('luz_2', 'luz', 520, 300), punto('luz_3', 'luz', 860, 300),
    ], cols, filas, noche=True)

    # Capítulo 5: la casa abandonada
    s, d = mapa_vacio('madera')
    for c in range(W):
        s[0][c] = G['pared']; s[15][c] = G['pared']
    for f in range(H):
        s[f][0] = G['pared']; s[f][23] = G['pared']
    for c in (5, 11, 17):
        s[0][c] = G['ventana']
    for c in range(9, 15):
        poner(s, d, c, 7, 'mesa')
    for (c, f) in [(3, 3), (4, 3), (20, 12), (19, 12), (2, 11)]:
        poner(s, d, c, f, 'cajon')
    guardar('casa_abandonada', s, d, colision(s, d), [
        punto('doc_1', 'prop', 400, 296, imagen='documento'), punto('doc_2', 'prop', 480, 296, imagen='documento'), punto('doc_3', 'prop', 560, 296, imagen='documento'),
        punto('viktor', 'actor', 480, 420), punto('friedrich', 'actor', 330, 390), punto('thomas', 'actor', 630, 390), punto('anneliese', 'actor', 700, 220),
        punto('luz_1', 'luz', 480, 300), punto('luz_2', 'luz', 700, 220),
    ], cols, filas, noche=True)

    # Capítulo 6: la playa de maniobras (casilleros, palancas, garita y la vía de carga donde frena el tren)
    s, d = mapa_vacio('grava')
    for c in range(W):
        s[2][c] = G['vias']; s[5][c] = G['vias']; s[9][c] = G['vias']
    for f in range(1, 5):                           # el depósito con los casilleros (arriba a la izquierda)
        for c in range(0, 5):
            s[f][c] = G['cemento']
    for c in range(0, 6):
        s[0][c] = G['pared']; s[5][c] = G['pared'] if c != 3 else G['puerta']
    for f in range(0, 6):
        s[f][5] = G['pared']
    for f in (1, 2, 3):
        poner(s, d, 0, f, 'estante')
    for (c, f) in [(2, 13), (3, 13)]:
        poner(s, d, c, f, 'escritorio')             # la garita de señales
    for (c, f) in [(9, 11), (14, 11), (15, 13), (17, 12), (11, 14), (6, 14), (12, 7), (17, 7)]:
        poner(s, d, c, f, 'cajon')
    for c in (8, 16, 22):
        poner(s, d, c, 15, 'farol')
    extra = [(c, 9) for c in range(2, 24)]          # la vía de carga: ahí frena el tren (y tapa la vista); se cruza solo por la izquierda
    guardar('estacion_carga', s, d, colision(s, d, extra=extra), [
        punto('locomotora', 'prop', -120, 400, imagen='locomotora'), punto('vagon_t1', 'prop', -340, 400, imagen='vagon'),
        punto('vagon_t2', 'prop', -560, 400, imagen='vagon'), punto('vagon_t3', 'prop', -780, 400, imagen='vagon'),
        punto('palanca_A', 'prop', 300, 140, imagen='palanca'), punto('palanca_B', 'prop', 500, 260, imagen='palanca'),
        punto('palanca_C', 'prop', 700, 140, imagen='palanca'), punto('palanca_D', 'prop', 860, 300, imagen='palanca'),
        punto('loco_parada', 'marca', 850, 400), punto('t1_parada', 'marca', 630, 400), punto('t2_parada', 'marca', 410, 400), punto('t3_parada', 'marca', 190, 400),
        punto('viktor', 'actor', 60, 580), punto('vferroviario', 'actor', 60, 580), punto('friedrich', 'actor', 160, 600), punto('thomas', 'actor', 60, 600), punto('anneliese', 'actor', 220, 560),
        punto('trapo_1', 'actor', 140, 100), punto('trapo', 'actor', 140, 280),
        punto('ruta_trapo_1', 'ruta', 0, 0, puntos=[[140, 100], [140, 170], [60, 170], [60, 100]], modo='vuelta'),
        punto('anciano', 'actor', 410, 470), punto('prisionero_1', 'actor', 330, 480), punto('prisionero_2', 'actor', 470, 480),
        punto('trapo_2', 'actor', 900, 470), punto('trapo_3', 'actor', 920, 560), punto('trapo_4', 'actor', 860, 600),
        punto('casillero', 'marca', 50, 140), punto('garita', 'marca', 110, 500), punto('porton', 'marca', 140, 330),
        punto('frente_t1', 'marca', 630, 450), punto('frente_t2', 'marca', 410, 450), punto('frente_t3', 'marca', 190, 450),
        punto('luz_1', 'luz', 100, 500), punto('luz_2', 'luz', 520, 470), punto('luz_3', 'luz', 860, 560), punto('luz_4', 'luz', 80, 140),
    ], cols, filas, noche=True)

    # Acto III: 13 de agosto de 1961, una calle cortada por la línea de los sectores (Oeste a la izquierda)
    s, d = mapa_vacio('adoquin')
    for c in range(W):
        for f in (0, 1, 2, 13, 14, 15):
            s[f][c] = G['pared'] if f in (0, 15) else G['vereda']
        for f in range(6, 10):
            s[f][c] = G['asfalto']
    for c in range(W):
        s[1][c] = G['pared'] if c % 5 else G['ventana']
        s[14][c] = G['pared'] if c % 5 else G['ventana']
    for c in range(14, W):
        s[12][c] = G['vias']                              # el S-Bahn, cortado
    for (c, f) in [(3, 4), (8, 11), (19, 4)]:
        poner(s, d, c, f, 'farol')
    extra = [(12, f) for f in range(2, 14)]              # la línea de los sectores: el alambre ya está tendido
    guardar('trece_agosto', s, d, colision(s, d, extra=extra), [
        punto('alambre_1', 'prop', 490, 160, imagen='alambre'), punto('alambre_2', 'prop', 490, 240, imagen='alambre'), punto('alambre_3', 'prop', 490, 320, imagen='alambre'),
        punto('alambre_4', 'prop', 490, 400, imagen='alambre'), punto('alambre_5', 'prop', 490, 480, imagen='alambre'),
        punto('camion', 'prop', 760, 250, imagen='camion'), punto('cartel_sbahn', 'prop', 640, 540, imagen='cartel_sbahn'),
        punto('viktor', 'actor', 120, 440), punto('thomas', 'actor', 60, 300),
        punto('mujer', 'actor', 420, 300), punto('vecino', 'actor', 560, 380), punto('muchacho', 'actor', 620, 470),
        punto('guardia_1', 'actor', 540, 300), punto('guardia_2', 'actor', 560, 200), punto('kampf_1', 'actor', 600, 330), punto('kampf_2', 'actor', 590, 430),
        punto('kampf_3', 'actor', 680, 180), punto('cliente_1', 'actor', 360, 360), punto('cliente_2', 'actor', 380, 250),
        punto('frente_mujer', 'marca', 380, 320), punto('frente_vecino', 'marca', 440, 390), punto('frente_vias', 'marca', 440, 520), punto('frente_alambre', 'marca', 440, 200),
        punto('muchacho_salta', 'marca', 440, 470), punto('muchacho_huye', 'marca', 200, 560), punto('viktor_mira', 'marca', 420, 440),
    ], cols, filas, noche=False)

    # Capítulo 8: el departamento de Berlín Occidental (invierno de 1962)
    s, d = mapa_vacio('madera')
    for c in range(W):
        for f in range(H):
            if c < 5 or c > 18 or f < 3 or f > 13: s[f][c] = G['pared']
    s[13][11] = G['puerta']; s[3][8] = G['ventana']; s[3][15] = G['ventana']
    for c in (9, 10):
        poner(s, d, c, 7, 'mesa')
    for f in (5, 6, 7):
        poner(s, d, 18, f, 'estante')
    poner(s, d, 6, 11, 'banco')
    guardar('departamento', s, d, colision(s, d), [
        punto('fotos', 'prop', 520, 150, imagen='fotos_pared'), punto('carta', 'prop', 380, 296, imagen='documento'), punto('estuche', 'prop', 420, 300, imagen='estuche'),
        punto('frente_ventana', 'marca', 340, 170),
        punto('viktor', 'actor', 470, 420), punto('anneliese', 'actor', 330, 300), punto('thomas', 'actor', 640, 340), punto('lukas', 'actor', 460, 500), punto('martin', 'actor', 600, 440),
        punto('puerta', 'marca', 460, 500), punto('frente_fotos', 'marca', 520, 200),
        punto('luz_1', 'luz', 400, 300), punto('luz_2', 'luz', 600, 420),
    ], cols, filas, noche=True)

    # Capítulo 9: el túnel de mantenimiento y el andén de una estación fantasma
    s, d = mapa_vacio('ladrillo')
    for c in range(0, 24):
        for f in (8, 9):
            s[f][c] = G['cemento']                     # el túnel de mantenimiento
    for c in range(8, 20):
        s[2][c] = G['vias']
        for f in (3, 4, 5):
            s[f][c] = G['vereda']                      # el andén cerrado, en penumbra
    for f in (6, 7):
        s[f][10] = G['cemento']; s[f][17] = G['cemento']   # dos escaleras de servicio que suben al andén
    for c in range(19, 24):
        for f in range(10, 14):
            s[f][c] = G['cemento']                     # el escondite
    for (c, f) in [(21, 12), (22, 12), (20, 13)]:
        poner(s, d, c, f, 'cajon')
    guardar('tunel', s, d, colision(s, d), [
        punto('caja', 'prop', 860, 470, imagen='maleta'),
        punto('viktor', 'actor', 40, 360), punto('lukas', 'actor', 90, 340), punto('anneliese', 'actor', 60, 380), punto('thomas', 'actor', 120, 380), punto('paul', 'actor', 160, 350),
        punto('guardia_1', 'actor', 420, 180), punto('guardia_2', 'actor', 700, 150),
        punto('ruta_guardia_1', 'ruta', 0, 0, puntos=[[420, 180], [560, 180], [560, 220], [420, 220]], modo='vuelta'),
        punto('ruta_guardia_2', 'ruta', 0, 0, puntos=[[700, 150], [700, 210], [620, 210]], modo='vuelta'),
        punto('escondite', 'marca', 860, 430), punto('anden', 'marca', 560, 200),
        punto('luz_1', 'luz', 480, 170), punto('luz_2', 'luz', 860, 460), punto('luz_3', 'luz', 120, 360),
    ], cols, filas, noche=True)

    # Capítulo 10: la pequeña estación abandonada
    s, d = mapa_vacio('grava')
    for c in range(W):
        s[3][c] = G['vias']
        for f in (4, 5, 6):
            s[f][c] = G['vereda']
    for c in range(2, 9):
        for f in range(9, 13):
            s[f][c] = G['pared']
        s[9][c] = G['ventana'] if c in (3, 7) else G['pared']
    s[12][5] = G['puerta']
    for c in (12, 13):
        poner(s, d, c, 5, 'banco')
    poner(s, d, 18, 6, 'farol')
    guardar('estacion_abandonada', s, d, colision(s, d), [
        punto('horario', 'prop', 360, 260, imagen='horario'),
        punto('viktor', 'actor', 520, 250), punto('martin', 'actor', 900, 230), punto('lukas', 'actor', 440, 320),
        punto('frente_banco', 'marca', 500, 260), punto('frente_vias', 'marca', 680, 170), punto('frente_horario', 'marca', 360, 290),
        punto('encuentro', 'marca', 760, 230), punto('viktor_encuentro', 'marca', 700, 230),
    ], cols, filas, noche=False)

    # Capítulo 11: el restaurante apartado
    s, d = mapa_vacio('madera')
    for c in range(W):
        for f in range(H):
            if c < 2 or c > 22 or f < 2 or f > 13: s[f][c] = G['pared']
    for f in range(2, 14):
        s[f][18] = G['pared']                           # el pasillo del baño, detrás de una pared
    s[7][18] = G['puerta']; s[13][10] = G['puerta']; s[2][6] = G['ventana']; s[2][13] = G['ventana']
    for c in range(8, 14):
        poner(s, d, c, 7, 'mesa')                       # la mesa larga del grupo
    for (c, f) in [(4, 4), (5, 4), (4, 10), (5, 10), (15, 4), (15, 10)]:
        poner(s, d, c, f, 'mesa')                       # otras mesas
    guardar('restaurante', s, d, colision(s, d), [
        punto('viktor', 'actor', 420, 360), punto('martin', 'actor', 500, 360), punto('lukas', 'actor', 340, 340),
        punto('anneliese', 'actor', 380, 250), punto('thomas', 'actor', 520, 250),
        punto('cliente_1', 'actor', 200, 250), punto('cliente_2', 'actor', 620, 490),
        punto('bano', 'marca', 820, 300), punto('salida', 'marca', 420, 500), punto('thomas_sale', 'marca', 740, 300),
        punto('falk', 'actor', 600, 250), punto('samuel', 'actor', 560, 370), punto('frente_falk', 'marca', 560, 270),
        punto('guardia_1', 'actor', 300, 480), punto('guardia_2', 'actor', 660, 360), punto('guardia_3', 'actor', 220, 260),
        punto('ruta_guardia_1', 'ruta', 0, 0, puntos=[[300, 480], [620, 480]], modo='vuelta'),
        punto('ruta_guardia_2', 'ruta', 0, 0, puntos=[[660, 360], [660, 200]], modo='vuelta'),
        punto('luz_1', 'luz', 440, 300), punto('luz_2', 'luz', 200, 180), punto('luz_3', 'luz', 820, 280),
    ], cols, filas, noche=True)

    # Capítulo 12: el almacén donde tienen a Thomas
    s, d = mapa_vacio('cemento')
    for c in range(W):
        s[0][c] = G['pared']; s[15][c] = G['pared']
    for f in range(H):
        s[f][0] = G['puerta'] if f in (7, 8) else G['pared']; s[f][23] = G['pared']
    for c in range(17, 23):
        s[1][c] = G['pared']; s[6][c] = G['pared'] if c != 19 else G['puerta']
    for f in range(1, 7):
        s[f][17] = G['pared']                           # la oficina, arriba a la derecha
    for col in (5, 10, 15):
        for f in list(range(2, 6)) + list(range(10, 14)):
            poner(s, d, col, f, 'estante')              # estanterías
    for (c, f) in [(8, 7), (13, 8), (8, 12), (16, 9), (4, 10)]:
        poner(s, d, c, f, 'cajon')
    guardar('almacen', s, d, colision(s, d), [
        punto('viktor', 'actor', 60, 340), punto('martin', 'actor', 60, 400), punto('lukas', 'actor', 100, 300), punto('anneliese', 'actor', 100, 440),
        punto('thomas', 'actor', 820, 160),
        punto('maton_1', 'actor', 300, 200), punto('maton_2', 'actor', 500, 480), punto('maton_3', 'actor', 660, 300), punto('maton_4', 'actor', 900, 500),
        punto('ruta_maton_1', 'ruta', 0, 0, puntos=[[300, 200], [300, 480]], modo='vuelta'),
        punto('ruta_maton_2', 'ruta', 0, 0, puntos=[[500, 480], [500, 200]], modo='vuelta'),
        punto('ruta_maton_3', 'ruta', 0, 0, puntos=[[660, 300], [700, 300], [700, 440]], modo='vuelta'),
        punto('oficina', 'marca', 780, 200), punto('entrada', 'marca', 60, 320),
        punto('luz_1', 'luz', 300, 300), punto('luz_2', 'luz', 600, 380), punto('luz_3', 'luz', 800, 160),
    ], cols, filas, noche=True)

    # Acto VI: la mansión de Konrad Weiss (salón, pasillo y oficina)
    s, d = mapa_vacio('alfombra')
    for c in range(W):
        s[0][c] = G['pared'] if c % 4 else G['ventana']; s[15][c] = G['pared']
    for f in range(H):
        s[f][0] = G['pared']; s[f][23] = G['pared']; s[f][12] = G['pared']; s[f][16] = G['pared']
    for f in range(1, 15):
        for c in (13, 14, 15):
            s[f][c] = G['madera']                        # el pasillo
    for f in range(1, 9):
        for c in range(17, 23):
            s[f][c] = G['madera']                        # la oficina
    for c in range(16, 24):
        s[9][c] = G['pared']
    for f in range(10, 15):
        for c in range(17, 23):
            s[f][c] = G['madera']
    s[7][12] = G['puerta']; s[4][16] = G['puerta']; s[15][5] = G['puerta']; s[12][16] = G['puerta']
    for c in range(2, 8):
        poner(s, d, c, 4, 'mesa')                        # la mesa de la cena
    for c in (8, 9):
        poner(s, d, c, 10, 'mesa')                       # la mesa de juego
    for c in (19, 20):
        poner(s, d, c, 4, 'escritorio')                  # el escritorio de Weiss
    for c in range(17, 23):
        poner(s, d, c, 14, 'estante')                    # la biblioteca
    poner(s, d, 2, 12, 'banco'); poner(s, d, 3, 12, 'banco')
    guardar('mansion', s, d, colision(s, d), [
        punto('fotos', 'prop', 800, 80, imagen='fotos_pared'), punto('carta', 'prop', 800, 175, imagen='documento'),
        punto('viktor', 'actor', 360, 480), punto('weiss', 'actor', 360, 390),
        punto('invitado_1', 'actor', 120, 230), punto('invitado_2', 'actor', 200, 230), punto('invitado_3', 'actor', 280, 230),
        punto('guardia_1', 'actor', 560, 140), punto('guardia_2', 'actor', 560, 480), punto('guardia_3', 'actor', 600, 520),
        punto('guardia_4', 'actor', 560, 80), punto('guardia_5', 'actor', 600, 80),
        punto('ruta_guardia_1', 'ruta', 0, 0, puntos=[[560, 140], [560, 520]], modo='vuelta'),
        punto('anneliese', 'actor', 760, 260), punto('thomas', 'actor', 840, 260), punto('lukas', 'actor', 720, 320),
        punto('oficina', 'marca', 720, 190), punto('frente_carta', 'marca', 800, 230), punto('weiss_entra', 'marca', 700, 180),
        punto('weiss_rincon', 'marca', 900, 330), punto('lukas_huye', 'marca', 560, 600), punto('salida', 'marca', 220, 600),
        punto('luz_1', 'luz', 360, 420), punto('luz_2', 'luz', 200, 180), punto('luz_3', 'luz', 800, 200),
    ], cols, filas, noche=True)

    # Acto VIII: la instalación militar abandonada (oficinas, pasillos, la sala del archivo y el depósito)
    s, d = mapa_vacio('ladrillo')
    for c in range(1, 23):
        for f in (7, 8):
            s[f][c] = G['cemento']                       # el pasillo principal
    for f in range(1, 15):
        for c in (13, 14):
            s[f][c] = G['cemento']                       # el pasillo que baja
    for c in range(1, 11):
        for f in range(2, 6):
            s[f][c] = G['cemento']                       # las oficinas abandonadas
    s[6][4] = G['cemento']; s[6][8] = G['cemento']
    for c in range(16, 23):
        for f in range(1, 7):
            s[f][c] = G['cemento']                       # la sala del archivo
        for f in range(10, 15):
            s[f][c] = G['cemento']                       # el depósito de cajas
    s[4][15] = G['cemento']; s[12][15] = G['cemento']
    for (c, f) in [(2, 3), (3, 3), (7, 4), (8, 4)]:
        poner(s, d, c, f, 'escritorio')
    for (c, f) in [(18, 3), (19, 3), (21, 5)]:
        poner(s, d, c, f, 'mesa')
    for (c, f) in [(17, 2), (22, 2), (17, 6), (20, 6)] + [(c, f) for c in (17, 19, 21) for f in (11, 13)]:
        poner(s, d, c, f, 'cajon')
    guardar('instalacion', s, d, colision(s, d), [
        punto('carpeta', 'prop', 780, 135, imagen='documento'), punto('archivo', 'prop', 880, 220, imagen='maleta'),
        punto('viktor', 'actor', 60, 320), punto('anneliese', 'actor', 100, 300), punto('falk', 'actor', 140, 330), punto('thomas', 'actor', 60, 350),
        punto('lukas', 'actor', 860, 140), punto('maton_1', 'actor', 860, 100), punto('maton_2', 'actor', 820, 250),
        punto('trabajador_1', 'actor', 260, 320), punto('trabajador_2', 'actor', 420, 340), punto('trabajador_3', 'actor', 550, 500),
        punto('agente', 'actor', 500, 320),
        punto('frente_carpeta', 'marca', 780, 175), punto('entrada_archivo', 'marca', 660, 180), punto('falk_huye', 'marca', 40, 320),
        punto('deposito', 'marca', 760, 480), punto('frente_lukas', 'marca', 720, 480), punto('escombros', 'marca', 520, 330),
        punto('ruta_lukas', 'ruta', 0, 0, puntos=[[860, 140], [700, 180], [560, 180], [560, 300], [550, 440], [600, 490], [800, 490]], modo='ida'),
        punto('luz_1', 'luz', 120, 320), punto('luz_2', 'luz', 780, 160), punto('luz_3', 'luz', 760, 480), punto('luz_4', 'luz', 420, 320),
    ], cols, filas, noche=True)

    # Capítulo 2: Bahnhof Friedrichstraße en julio de 1961 (antes del Muro: el control lo hace la Transportpolizei en el andén)
    s, d = mapa_vacio('cemento')
    for c in range(W):
        s[2][c] = G['vias']; s[3][c] = G['vias']; s[11][c] = G['vias']
        s[0][c] = G['pared']
    for f in range(4, 11):                        # andén con la caseta del control
        for c in range(0, 10):
            s[f][c] = G['vereda']
    for c in range(10, 24):
        for f in range(4, 11):
            s[f][c] = G['grava']
    for f in range(12, H):
        for c in range(W):
            s[f][c] = G['asfalto']
    for c in (6, 7):
        poner(s, d, c, 6, 'escritorio')        # el puesto de control
    for (c, f) in [(13, 6), (13, 7), (17, 9), (20, 5), (16, 5)]:
        poner(s, d, c, f, 'cajon')
    for c in (3, 9, 15, 21):
        poner(s, d, c, 14, 'farol')
    extra = [(c, f) for c in range(1, 6) for f in (2, 3)] + [(c, f) for c in range(12, 17) for f in (10, 11)] + [(c, f) for c in range(18, 23) for f in (2, 3)]
    guardar('friedrichstrasse', s, d, colision(s, d, extra=extra), [
        punto('vagon_1', 'prop', 140, 160, imagen='vagon'), punto('vagon_2', 'prop', 580, 480, imagen='vagon'), punto('vagon_3', 'prop', 820, 160, imagen='vagon'),
        punto('paquete', 'prop', 860, 220, imagen='paquete'), punto('auto_viktor', 'prop', 120, 600, imagen='auto_viktor'),
        punto('viktor', 'actor', 80, 330), punto('friedrich', 'actor', 200, 560), punto('thomas', 'actor', 60, 560), punto('anneliese', 'actor', 40, 330), punto('lukas', 'actor', 40, 380),
        punto('trapo', 'actor', 270, 290), punto('trapo_1', 'actor', 600, 260), punto('trapo_2', 'actor', 900, 400),
        punto('ruta_trapo_1', 'ruta', 0, 0, puntos=[[600, 260], [660, 260], [660, 380], [600, 380]], modo='vuelta'),
        punto('ruta_trapo_2', 'ruta', 0, 0, puntos=[[900, 400], [900, 260], [740, 260], [740, 400]], modo='vuelta'),
        punto('control', 'marca', 270, 330), punto('meta_paquete', 'marca', 860, 250), punto('salida', 'marca', 140, 580),
        punto('luz_1', 'luz', 270, 280), punto('luz_2', 'luz', 120, 560), punto('luz_3', 'luz', 600, 560), punto('luz_4', 'luz', 860, 240),
    ], cols, filas, noche=True)


if __name__ == '__main__':
    cols, filas = hacer_tileset()
    hacer_objetos()
    hacer_personajes()
    mapas(cols, filas)
    mapas_acto_1(cols, filas)
    print('Campaña: tileset, %d personajes con retratos, objetos y 6 escenas.' % len(PERSONAJES))
