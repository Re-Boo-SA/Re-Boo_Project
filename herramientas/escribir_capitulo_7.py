#!/usr/bin/env python3
"""Escribe el guion del Capítulo 7 («Los que quedaron detrás»), que abre con el 13 de agosto de 1961 (Acto III), con su inglés al lado.
Correr desde la raíz:  python herramientas/escribir_capitulo_7.py   (después: python herramientas/traducir_contenido.py)
Datos de época verificados (Bundesarchiv, bpb): domingo; la operación empezó una hora después de medianoche; se cortaron las vías del
S-Bahn y el U-Bahn hacia el Oeste, se levantaron calles y se tendió alambre de púas; policías de frontera, Volkspolizei y Kampfgruppen,
con el ejército detrás. La Puerta de Brandeburgo se cerró el 14 y los primeros bloques de hormigón son del 15 (no aparecen el 13)."""
import json, os
RUTA_EN = 'herramientas/traducciones_capitulos.json'
EN = json.load(open(RUTA_EN, encoding='utf-8')) if os.path.exists(RUTA_EN) else {}
def tr(es, en):
    EN[es] = en; return es
D = lambda quien, es, en, emocion='neutral': {'tipo': 'dialogo', 'quien': quien, 'emocion': emocion, 'texto': tr(es, en)}
F = lambda a, seg=1: {'tipo': 'fundido', 'a': a, 'segundos': seg}
TJ = lambda es, en, seg=3, **k: dict({'tipo': 'tarjeta', 'texto': tr(es, en), 'segundos': seg}, **k)
G = lambda punto: {'tipo': 'guardar', 'punto': punto}
MIRAR = tr('Mirar', 'Look')
base = json.load(open('datos/campana/capitulo_6.json', encoding='utf-8'))
PERS = dict(base['personajes'], kampf='Kampfgruppe', mujer='Una mujer', vecino='Un vecino', muchacho='Un muchacho', cliente='Un vecino')
for a, b in (('Kampfgruppe', 'Kampfgruppe'), ('Una mujer', 'A woman'), ('Un vecino', 'A neighbour'), ('Un muchacho', 'A young man')): tr(a, b)
paseo = {'tipo': 'paseo', 'texto': tr('Mirá a tu alrededor', 'Look around'), 'minimo': 3,
 'fin': {'actor': 'thomas', 'accion': tr('Hablar con Thomas', 'Talk to Thomas')},
 # Cada punto se mira desde el lado occidental, junto al alambre (las escenas del lado oriental no se alcanzan caminando)
 'puntos': [
  {'objeto': 'mujer', 'accion': MIRAR, 'pasos': [
    D('mujer', '¡Jürgen! ¡Jürgen, quedate ahí! ¡Voy a buscarte!', "Jürgen! Jürgen, stay there! I'm coming to get you!", 'triste'),
    D('guardia', 'Atrás. Por acá ya no pasa nadie.', 'Get back. Nobody goes through here any more.', 'enojado'),
    D('mujer', 'Es mi hijo. Está del otro lado de la calle. ¡Es mi hijo!', "He's my son. He's on the other side of the street. He's my son!", 'triste')]},
  {'objeto': 'frente_vecino', 'accion': MIRAR, 'pasos': [
    D('vecino', '¡Trabajo en Kreuzberg! ¡Mañana a las seis tengo turno!', "I work in Kreuzberg! I'm on shift tomorrow at six!", 'enojado'),
    D('kampf', 'Mañana no tenés nada del otro lado. Volvé a tu casa.', "Tomorrow you've got nothing over there. Go home.", 'enojado')]},
  {'objeto': 'frente_vias', 'accion': MIRAR, 'pasos': [
    D('cliente', 'Cortaron el S-Bahn. A la una de la mañana, dicen. Los trenes al Oeste ya no pasan.', "They've cut the S-Bahn. At one in the morning, they say. The trains to the West don't run any more."),
    D('viktor', 'Y el U-Bahn, y las calles. Todo en una noche.', 'And the U-Bahn, and the streets. All in one night.')]},
  {'objeto': 'frente_alambre', 'accion': MIRAR, 'pasos': [
    TJ('Alambre de púas sobre caballetes de madera. Detrás, más rollos esperando en el camión.', 'Barbed wire on wooden trestles. Behind it, more coils waiting on the truck.', 4),
    D('viktor', 'Ayer cruzaba esta calle para comprar cigarrillos.', 'Yesterday I crossed this street to buy cigarettes.', 'triste')]},
  {'objeto': 'muchacho_salta', 'accion': MIRAR, 'pasos': [
    D('muchacho', 'Si no es ahora, no es nunca.', "If it's not now, it's never."),
    {'tipo': 'mover', 'actor': 'muchacho', 'a': 'muchacho_salta', 'segundos': 0.8},
    {'tipo': 'efecto', 'nombre': 'golpe'},
    {'tipo': 'mover', 'actor': 'muchacho', 'a': 'muchacho_huye', 'segundos': 1.4},
    D('kampf', '¡Alto! ¡Alto!', 'Halt! Halt!', 'enojado'),
    TJ('Salta el alambre antes de que lo alcancen. Los que quedan del otro lado lo miran irse.', 'He jumps the wire before they reach him. Those left on the other side watch him go.', 4)]},
 ]}
cap7 = {'_leeme': base['_leeme'] + " paseo: caminata libre sin objetivos; cerca de cada punto aparece «E · Mirar» y se juega su escena; después de ver 'minimo', se puede terminar hablando con 'fin'.",
 'id': 'capitulo_7', 'titulo': 'Capítulo 7: Los que quedaron detrás', 'duracionMinima': 60, 'aviso': base['aviso'],
 'recompensa': {'xp': 350, 'logro': 'nacht_capitulo_7'}, 'personajes': PERS, 'pasos': [
  {'tipo': 'escena', 'mapa': 'redaccion_1975', 'actores': ['ingrid'], 'jugador': None, 'props': ['caja']},
  F('escena', 1.5), TJ('Berlín Occidental · 1975', 'West Berlin · 1975'),
  D('ingrid', 'La octava foto no tiene a nadie. Una calle, alambre y un camión. Atrás, una fecha: 13 de agosto de 1961.', 'The eighth photo has no one in it. A street, wire and a truck. On the back, a date: 13 August 1961.', 'triste'),
  F('negro', 1.5),
  TJ('Acto III · El Muro', 'Act III · The Wall', 3, estilo='documento'),
  {'tipo': 'efecto', 'nombre': 'motores', 'segundos': 2},
  TJ('Domingo, 13 de agosto de 1961. Viktor se despierta con el ruido de los motores.', 'Sunday, 13 August 1961. Viktor wakes to the sound of engines.', 4),
  {'tipo': 'escena', 'mapa': 'trece_agosto', 'actores': ['mujer', 'vecino', 'muchacho', 'guardia_1', 'guardia_2', 'kampf_1', 'kampf_2', 'kampf_3', 'cliente_1', 'cliente_2'], 'jugador': 'viktor',
   'props': ['alambre_1', 'alambre_2', 'alambre_3', 'alambre_4', 'alambre_5', 'camion', 'cartel_sbahn']},
  F('escena', 1.5), G('trece'),
  paseo,
  {'tipo': 'franjas', 'visibles': True},
  D('thomas', '¿Qué está pasando?', "What's happening?"),
  {'tipo': 'mover', 'actor': 'viktor', 'a': 'viktor_mira', 'segundos': 1.5},
  D('viktor', 'Están construyendo una prisión.', "They're building a prison.", 'enojado'),
  TJ('Ese día cambió todo. Los negocios clandestinos se volvieron más peligrosos. Las rutas de contrabando desaparecieron. Las familias quedaron separadas.', 'That day changed everything. Black-market dealings became more dangerous. The smuggling routes disappeared. Families were separated.', 5),
  TJ('Y había algo más: varios hombres habían desaparecido en las semanas anteriores. Ahora sus nombres se borraban de los registros.', 'And there was something else: several men had disappeared in the weeks before. Now their names were being erased from the records.', 5),
  F('negro', 1.5), {'tipo': 'franjas', 'visibles': False},
  {'tipo': 'escena', 'mapa': 'taller', 'actores': ['thomas', 'friedrich', 'anneliese'], 'jugador': 'viktor', 'props': ['auto_taller']},
  F('escena', 1), TJ('Berlín Occidental · Fines de agosto de 1961', 'West Berlin · Late August 1961'), G('plan'),
  {'tipo': 'objetivo', 'texto': tr('Hablá con Anneliese', 'Talk to Anneliese'), 'zona': 'anneliese', 'radio': 80, 'accion': tr('Hablar con Anneliese', 'Talk to Anneliese')},
  D('anneliese', 'Hay un edificio administrativo viejo. Ahí guardan los expedientes de los que intentaron irse al Oeste.', 'There is an old administrative building. They keep the files of those who tried to leave for the West there.'),
  D('thomas', 'Si están los nombres que borraron, están ahí.', 'If the names they erased are anywhere, they are there.'),
  {'tipo': 'efecto', 'nombre': 'telefono', 'segundos': 2},
  D('friedrich', '¿Sí? ... ¿Margarete?', 'Yes? ... Margarete?'),
  D('friedrich', 'Quiere cruzar. Esta noche. Quiere verme del otro lado.', 'She wants to cross. Tonight. She wants to see me on the other side.', 'triste'),
  D('viktor', 'Es una trampa.', "It's a trap.", 'enojado'),
  D('friedrich', 'Puede ser.', 'Maybe.'),
  D('viktor', 'Te van a matar.', "They'll kill you.", 'enojado'),
  D('friedrich', 'Entonces será mi problema.', "Then that's my problem."),
  {'tipo': 'elegir', 'clave': 'friedrich_margarete', 'pregunta': tr('¿Qué hace Viktor?', 'What does Viktor do?'), 'opciones': [
    {'valor': 'ayudar', 'texto': tr('Darle la radio de Thomas y el arma. Si va a ir, que vaya preparado.', "Give him Thomas's radio and the gun. If he's going, he goes prepared.")},
    {'valor': 'frenar', 'texto': tr('Pararse en la puerta. No va a salir.', "Stand in the doorway. He's not leaving.")}]},
  {'tipo': 'si', 'clave': 'friedrich_margarete', 'valor': 'ayudar', 'pasos': [
    D('friedrich', 'Gracias, Viktor. Te debo una.', 'Thanks, Viktor. I owe you one.'),
    D('thomas', 'La radio tiene alcance hasta el canal. Si pasa algo, llamá.', 'The radio reaches as far as the canal. If anything happens, call.')]},
  {'tipo': 'si', 'clave': 'friedrich_margarete', 'valor': 'frenar', 'pasos': [
    D('friedrich', 'Correte.', 'Move.', 'enojado'),
    D('viktor', 'No.', 'No.', 'enojado'),
    D('friedrich', 'Es mi mujer, Viktor. Correte.', "She's my wife, Viktor. Move.", 'enojado'),
    TJ('Viktor se corre.', 'Viktor steps aside.', 2)]},
  {'tipo': 'mover', 'actor': 'friedrich', 'a': 'anneliese_entra', 'segundos': 2.5},   # sale por la entrada del taller
  {'tipo': 'desaparecer', 'actores': ['friedrich']},
  F('negro', 1.5),
  TJ('Esa noche, el grupo escucha disparos cerca de la frontera.', 'That night, the group hears shots near the border.', 3),
  {'tipo': 'efecto', 'nombre': 'disparos', 'desde': ['nadie'], 'segundos': 1.2},
  TJ('Al amanecer suena el teléfono.', 'At dawn the phone rings.', 2),
  {'tipo': 'efecto', 'nombre': 'telefono', 'segundos': 2},
  {'tipo': 'escena', 'mapa': 'taller', 'actores': ['thomas', 'anneliese'], 'jugador': 'viktor', 'props': ['auto_taller']},
  F('escena', 1), G('llamada'),
  D('anneliese', 'Era Lotte. Lo detuvieron en la frontera. Está en el mismo edificio de antes.', 'It was Lotte. They arrested him at the border. He is in the same building as before.', 'triste'),
  D('viktor', 'Entonces vamos a buscarlo.', "Then we're going to get him.", 'enojado'),
  F('negro', 1.5),
  {'tipo': 'escena', 'mapa': 'edificio', 'actores': ['tuniforme', 'friedrich', 'guardia_1', 'guardia_2'], 'jugador': 'vuniforme'},
  {'tipo': 'tumbar', 'actor': 'friedrich'},
  F('escena', 1), G('edificio'),
  D('tuniforme', 'Los mismos uniformes. Esperemos que nadie se acuerde de la última vez.', 'Same uniforms. Let us hope nobody remembers last time.'),
  {'tipo': 'sigilo', 'guardias': ['guardia_1', 'guardia_2'], 'meta': 'celda', 'radio': 45, 'texto': tr('Llegá a la celda sin que te vean', 'Reach the cell without being seen')},
  {'tipo': 'objetivo', 'texto': tr('Abrí la celda', 'Open the cell'), 'zona': 'celda', 'radio': 70, 'accion': tr('Abrir la celda', 'Open the cell')},
  {'tipo': 'franjas', 'visibles': True},
  {'tipo': 'camara', 'actor': 'friedrich', 'zoom': 1.6, 'segundos': 2},
  TJ('Friedrich está en el piso. Le cuesta respirar.', 'Friedrich is on the floor. He can barely breathe.', 3),
  D('vuniforme', 'Vamos. Nos vamos de acá.', "Come on. We're getting out of here.", 'enojado'),
  D('friedrich', 'No pude sacarla.', "I couldn't get her out.", 'dolor'),
  TJ('Margarete murió intentando cruzar.', 'Margarete died trying to cross.', 3, estilo='documento'),
  D('vuniforme', '…', '…', 'triste'),
  {'tipo': 'si', 'clave': 'friedrich_margarete', 'valor': 'ayudar', 'pasos': [D('friedrich', 'Me diste la radio... Te dije que podía hacerlo.', 'You gave me the radio... I told you I could do it.', 'dolor')]},
  {'tipo': 'si', 'clave': 'friedrich_margarete', 'valor': 'frenar', 'pasos': [D('friedrich', 'Tenías razón... Te dije que podía hacerlo. No podía.', "You were right... I told you I could do it. I couldn't.", 'dolor')]},
  TJ('Friedrich muere antes de que puedan salir del edificio.', 'Friedrich dies before they can get out of the building.', 4, estilo='documento'),
  F('negro', 2.5),
  TJ('Viktor se volvió más violento. Thomas empezó a tenerle miedo. Anneliese se sintió responsable por haberlos llevado hasta ahí.', 'Viktor became more violent. Thomas began to fear him. Anneliese felt responsible for having led them there.', 6),
  TJ('Friedrich Keller', 'Friedrich Keller', 3, estilo='documento'),
  G('final'), TJ('Fin del Acto III', 'End of Act III'), {'tipo': 'fin'}]}
tr(cap7['titulo'], 'Chapter 7: Those Left Behind')
json.dump(cap7, open('datos/campana/capitulo_7.json', 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
caps = json.load(open('datos/campana/capitulos.json', encoding='utf-8'))
for c in caps['capitulos']:
    if c['id'] == 'capitulo_7': c['disponible'] = True
if not any(c['id'] == 'capitulo_8' for c in caps['capitulos']):
    caps['capitulos'].append({'id': 'capitulo_8', 'titulo': tr('Capítulo 8: Lukas', 'Chapter 8: Lukas'), 'disponible': False})
json.dump(caps, open('datos/campana/capitulos.json', 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
p = 'datos/mundo/misiones.json'; d = json.load(open(p, encoding='utf-8'))
if not any(l['id'] == 'nacht_capitulo_7' for l in d['logros']):
    d['logros'].append({'id': 'nacht_capitulo_7', 'nombre': 'Los que quedaron detrás', 'descripcion': 'Terminá el Capítulo 7 de Nacht über Berlin.', 'condicion': {'campana': 'nacht_capitulo_7'}})
json.dump(d, open(p, 'w', encoding='utf-8'), ensure_ascii=False, indent=2)
tr('Los que quedaron detrás', 'Those Left Behind'); tr('Terminá el Capítulo 7 de Nacht über Berlin.', 'Finish Chapter 7 of Nacht über Berlin.')
json.dump(EN, open(RUTA_EN, 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
print('capítulo 7:', len(cap7['pasos']), 'pasos')
