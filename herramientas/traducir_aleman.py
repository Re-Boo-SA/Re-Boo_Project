#!/usr/bin/env python3
"""
Genera Capa_Presentacion/lang/de.json (alemán) con las mismas claves que lang/en.json.
    python herramientas/traducir_aleman.py
Las traducciones están en herramientas/traducciones_de.json (clave: el texto en español, como en en.json).
Comprueba que no falte ninguna, que se conserven los marcadores ({n}, {nombre}…) y avisa si cambian los espacios de los bordes
(algunos textos se pegan a otros: «Paso » + número). Lo corre también herramientas/traducir_contenido.py al final.
Los capítulos nuevos se escriben con su alemán al lado (herramientas/escribir_*.py) y lo agregan a traducciones_de.json.
"""
import json, os, re, sys
RAIZ = os.path.abspath(os.path.join(os.path.dirname(__file__), '..'))
EN = os.path.join(RAIZ, 'Capa_Presentacion', 'lang', 'en.json')
DE = os.path.join(RAIZ, 'Capa_Presentacion', 'lang', 'de.json')
FUENTE = os.path.join(RAIZ, 'herramientas', 'traducciones_de.json')
MARCA = re.compile(r'\{[a-z_]+\}')

def generar(silencioso=False):
    en = json.load(open(EN, encoding='utf-8'))
    tr = json.load(open(FUENTE, encoding='utf-8')) if os.path.exists(FUENTE) else {}
    salida, faltan, marcas, bordes = {}, [], [], []
    for clave, ingles in en.items():
        if clave not in tr:
            faltan.append(clave); salida[clave] = ingles; continue      # mejor inglés que nada
        aleman = tr[clave]
        if sorted(MARCA.findall(aleman)) != sorted(MARCA.findall(clave)): marcas.append(clave)
        if (clave[:1].isspace(), clave[-1:].isspace()) != (aleman[:1].isspace(), aleman[-1:].isspace()): bordes.append(clave)
        salida[clave] = aleman
    json.dump(salida, open(DE, 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
    if not silencioso:
        print('alemán: %d textos | sin traducir: %d %s | marcadores distintos: %d %s | espacios en los bordes distintos: %d %s' % (
            len(salida), len(faltan), faltan[:5], len(marcas), marcas[:5], len(bordes), bordes[:5]))
    return faltan, marcas

if __name__ == '__main__':
    faltan, marcas = generar()
    sys.exit(1 if faltan or marcas else 0)
