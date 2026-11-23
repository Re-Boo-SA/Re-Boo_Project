#!/usr/bin/env python3
"""Escribe el guion del Capítulo 6 («El tren de las tres») con su traducción al inglés al lado.
Correr desde la raíz:  python herramientas/escribir_capitulo_6.py   (después: python herramientas/traducir_contenido.py)"""
import json, os
RUTA_EN = 'herramientas/traducciones_capitulos.json'
EN = json.load(open(RUTA_EN, encoding='utf-8')) if os.path.exists(RUTA_EN) else {}
def tr(es, en):
    EN[es] = en; return es
D = lambda quien, es, en, emocion='neutral': {'tipo': 'dialogo', 'quien': quien, 'emocion': emocion, 'texto': tr(es, en)}
F = lambda a, seg=1: {'tipo': 'fundido', 'a': a, 'segundos': seg}
TJ = lambda es, en, seg=3, **k: dict({'tipo': 'tarjeta', 'texto': tr(es, en), 'segundos': seg}, **k)
G = lambda punto: {'tipo': 'guardar', 'punto': punto}
O = lambda es, en, **k: dict({'texto': tr(es, en)}, **k)
U = lambda lado, celda, vida, mov, punt: {'lado': lado, 'celda': celda, 'vida': vida, 'mov': mov, 'punteria': punt}
base = json.load(open('datos/campana/capitulo_5.json', encoding='utf-8'))
PERS = dict(base['personajes'], vferroviario='Viktor Adler', anciano='El anciano', prisionero='Prisionero')
tr('El anciano', 'The old man'); tr('Prisionero', 'Prisoner')
MOVER = lambda actor, a, seg, esperar=False: {'tipo': 'mover', 'actor': actor, 'a': a, 'segundos': seg, 'esperar': esperar}
cap6 = {'_leeme': base['_leeme'] + " agujas: el acertijo de los cambios de vía (el servidor verifica adónde va el tren). vestir: un personaje se cambia de ropa (pasa a ser otro actor).",
 'id': 'capitulo_6', 'titulo': tr('Capítulo 6: El tren de las tres', "Chapter 6: The Three O'Clock Train"), 'duracionMinima': 60, 'aviso': base['aviso'],
 'recompensa': {'xp': 350, 'logro': 'nacht_capitulo_6'}, 'personajes': PERS, 'pasos': [
  {'tipo': 'escena', 'mapa': 'redaccion_1975', 'actores': ['ingrid'], 'jugador': None, 'props': ['caja']},
  F('escena', 1.5), TJ('Berlín Occidental · 1975', 'West Berlin · 1975'),
  D('ingrid', 'La séptima foto es un tren de carga, de noche. Atrás, dos palabras: «Projekt Morgen».', 'The seventh photo is a freight train, at night. On the back, two words: “Projekt Morgen”.', 'triste'),
  F('negro', 1.5),
  {'tipo': 'escena', 'mapa': 'taller', 'actores': ['thomas', 'friedrich', 'anneliese'], 'jugador': 'viktor', 'props': ['auto_taller']},
  F('escena', 1), TJ('Berlín Occidental · Agosto de 1961', 'West Berlin · August 1961'), G('plan'),
  {'tipo': 'objetivo', 'texto': tr('Hablá con Anneliese', 'Talk to Anneliese'), 'zona': 'anneliese', 'radio': 80, 'accion': tr('Hablar con Anneliese', 'Talk to Anneliese')},
  D('anneliese', 'Esta noche sale un tren de Berlín Oriental hacia una planta industrial. Lleva documentos.', 'Tonight a train leaves East Berlin for an industrial plant. It is carrying documents.'),
  D('thomas', 'Conseguí un camión. No preguntes de quién.', "I got a truck. Don't ask whose."),
  D('friedrich', '¿Y cómo se para un tren?', 'And how do you stop a train?'),
  D('anneliese', 'No se para. Se desvía. A la vía de carga, donde no mira nadie.', "You don't stop it. You divert it. Onto the freight track, where nobody is watching."),
  {'tipo': 'si', 'clave': 'perdon_anneliese', 'valor': 'perdonar', 'pasos': [
    D('thomas', '¿Esto también lo sabés por Lotte?', 'Did you get this from Lotte too?'),
    D('anneliese', 'Sí. Y esta vez es para nosotros.', 'Yes. And this time it is for us.')]},
  {'tipo': 'si', 'clave': 'perdon_anneliese', 'valor': 'no_perdonar', 'pasos': [
    D('friedrich', '¿Y por qué te vamos a creer esta vez?', 'And why should we believe you this time?', 'enojado'),
    D('anneliese', 'Porque yo también voy a estar ahí.', 'Because I will be there too.', 'triste')]},
  TJ('Berlín Oriental · Playa de maniobras de la Reichsbahn · 2:40', 'East Berlin · Reichsbahn marshalling yard · 2:40 a.m.', 4), F('negro', 1),
  {'tipo': 'escena', 'mapa': 'estacion_carga', 'actores': ['friedrich', 'thomas', 'anneliese', 'trapo_1', 'trapo'], 'jugador': 'viktor',
   'props': ['palanca_A', 'palanca_B', 'palanca_C', 'palanca_D', 'locomotora', 'vagon_t1', 'vagon_t2', 'vagon_t3']},
  F('escena', 1), G('playa'),
  D('thomas', 'Los uniformes de la Reichsbahn están en los casilleros del depósito. Sin eso no llegás ni a la garita.', "The Reichsbahn uniforms are in the depot lockers. Without one you won't even reach the signal box."),
  {'tipo': 'sigilo', 'guardias': ['trapo_1'], 'meta': 'casillero', 'radio': 45, 'texto': tr('Entrá al depósito y llegá a los casilleros sin que te vean', 'Get into the depot and reach the lockers unseen')},
  {'tipo': 'objetivo', 'texto': tr('Sacá un uniforme del casillero', 'Take a uniform from the locker'), 'zona': 'casillero', 'radio': 60, 'accion': tr('Abrir el casillero', 'Open the locker')},
  {'tipo': 'vestir', 'actor': 'viktor', 'como': 'vferroviario'},
  D('vferroviario', 'Me queda chico. Va a tener que alcanzar.', "It's too small. It will have to do."),
  {'tipo': 'objetivo', 'texto': tr('Pasá frente al guardia de la Transportpolizei', 'Walk past the Transportpolizei guard'), 'zona': 'trapo', 'radio': 80, 'accion': tr('Saludar al guardia', 'Greet the guard')},
  {'tipo': 'interrogatorio', 'id': 'guardia_playa', 'quien': 'trapo', 'limite': 45, 'sinRespuesta': 20, 'segundos': 8,
   'texto': tr('Que no sospeche del ferroviario nuevo', "Don't let him suspect the new railwayman"),
   'detenido': tr('Vos no sos de acá. Quedate quieto.', "You're not from here. Don't move."),
   'pase': tr('Andá. Y que no se atrase el de las tres.', "Go on. And don't let the three o'clock run late."),
   'preguntas': [
    {'texto': tr('No te conozco. ¿De qué turno sos?', "I don't know you. Which shift are you on?"), 'opciones': [
      O('Del de la noche. Me mandaron de Lichtenberg.', 'The night shift. They sent me from Lichtenberg.', sospecha=5), O('Del de siempre.', 'The usual one.', sospecha=20),
      O('¿Y vos quién sos para preguntar?', 'And who are you to ask?', sospecha=35)]},
    {'texto': tr('¿Quién es tu jefe de turno?', "Who's your shift boss?"), 'opciones': [
      O('El viejo Lindner.', 'Old Lindner.', sospecha=5), O('No me acuerdo el nombre.', "I can't remember his name.", sospecha=25), O('Uno con bigote.', 'One with a moustache.', sospecha=15)]},
    {'texto': tr('¿Qué venís a hacer a esta hora a la playa?', 'What are you doing in the yard at this hour?'), 'opciones': [
      O('Revisar las agujas antes del tren de las tres.', "Checking the points before the three o'clock train.", sospecha=5), O('Lo que me mandaron.', 'What I was told.', sospecha=15),
      O('Nada, paseo.', 'Nothing, just walking around.', sospecha=35)]}]},
  G('agujas'),
  D('vferroviario', 'Cuatro agujas. Si me equivoco, el tren se va a la estación y nos esperan con la policía.', 'Four sets of points. If I get it wrong, the train goes to the station and the police will be waiting for us.'),
  {'tipo': 'agujas', 'id': 'agujas_carga', 'segundos': 120, 'inicio': 'A', 'meta': 'via_carga',
   'texto': tr('Mové las agujas para que el tren entre en la vía de carga, y dale la señal desde la garita', 'Set the points so the train enters the freight track, then give the signal from the signal box'),
   'agujas': {
     'A': {'recta': 'via_estacion', 'desvio': 'B', 'palanca': 'palanca_A', 'estado': 'recta'},
     'B': {'recta': 'D', 'desvio': 'C', 'palanca': 'palanca_B', 'estado': 'desvio', 'acopla': 'D'},
     'C': {'recta': 'via_taller', 'desvio': 'via_muerta', 'palanca': 'palanca_C', 'estado': 'recta'},
     'D': {'recta': 'via_carga', 'desvio': 'via_cocheras', 'palanca': 'palanca_D', 'estado': 'recta'}},
   'vias': {'via_estacion': tr('Estación (la policía)', 'Station (the police)'), 'via_taller': tr('Taller', 'Workshop'), 'via_muerta': tr('Vía muerta', 'Dead-end track'),
            'via_carga': tr('Vía de carga (ustedes)', 'Freight track (you)'), 'via_cocheras': tr('Cocheras', 'Sheds')},
   'acoplada': tr('La aguja B está acoplada a la D: al mover B, D también cambia.', 'Points B are coupled to D: moving B also switches D.'),
   'plano': {'A': [50, 95], 'B': [150, 95], 'C': [250, 45], 'D': [250, 145], 'via_estacion': [150, 20], 'via_taller': [370, 25], 'via_muerta': [370, 70], 'via_carga': [370, 125], 'via_cocheras': [370, 170]},
   'garita': 'garita'},
  G('tren'),
  {'tipo': 'franjas', 'visibles': True}, {'tipo': 'efecto', 'nombre': 'tren', 'segundos': 3},
  MOVER('locomotora', 'loco_parada', 7), MOVER('vagon_t1', 't1_parada', 7), MOVER('vagon_t2', 't2_parada', 7), MOVER('vagon_t3', 't3_parada', 7, True),
  {'tipo': 'franjas', 'visibles': False},
  D('thomas', 'Frenó justo donde queríamos.', 'It stopped right where we wanted.', 'contento'),
  D('friedrich', '¿Cuál de los vagones?', 'Which of the wagons?'),
  {'tipo': 'interactuar', 'objeto': 'vagon_t3', 'texto': tr('Buscá el vagón correcto', 'Find the right wagon'), 'accion': tr('Abrir el vagón', 'Open the wagon'), 'quedarse': True},
  D('vferroviario', 'Repuestos. Este no.', 'Spare parts. Not this one.'),
  {'tipo': 'interactuar', 'objeto': 'vagon_t1', 'texto': tr('Probá con otro', 'Try another one'), 'accion': 'Abrir el vagón', 'quedarse': True},
  D('vferroviario', 'Carbón. Tampoco.', 'Coal. Not this one either.'),
  {'tipo': 'interactuar', 'objeto': 'vagon_t2', 'texto': tr('Probá con el del medio', 'Try the one in the middle'), 'accion': 'Abrir el vagón', 'quedarse': True},
  {'tipo': 'aparecer', 'actores': ['anciano', 'prisionero_1', 'prisionero_2']},
  {'tipo': 'franjas', 'visibles': True},
  D('anneliese', 'Hay gente adentro.', 'There are people inside.', 'triste'),
  D('anciano', '¿Anneliese? Sos igual a tu padre.', 'Anneliese? You look just like your father.', 'triste'),
  D('anneliese', '¿Usted lo conoció?', 'You knew him?'),
  D('anciano', 'Trabajamos juntos. Él descubrió una operación. La llamaban Projekt Morgen.', 'We worked together. He uncovered an operation. They called it Projekt Morgen.'),
  D('anciano', 'Usaban la tensión entre los dos bloques para eliminar gente y fabricar pruebas contra sus enemigos. De los dos lados.', 'They used the tension between the two blocs to get rid of people and fabricate evidence against their enemies. On both sides.'),
  D('vferroviario', '¿Y mi hermano? ¿Martin Adler?', 'And my brother? Martin Adler?'),
  D('anciano', 'Martin estaba adentro. Trabajaba para ellos.', 'Martin was inside. He worked for them.', 'triste'),
  D('vferroviario', 'Mentira.', "That's a lie.", 'enojado'),
  D('thomas', '¡Luces! ¡Vienen por las vías!', 'Lights! They are coming along the tracks!', 'enojado'),
  {'tipo': 'aparecer', 'actores': ['trapo_2', 'trapo_3', 'trapo_4']},
  {'tipo': 'franjas', 'visibles': False},
  G('enfrentamiento'),
  {'tipo': 'combate', 'id': 'tren', 'mapa': 'estacion_carga', 'lider': 'vferroviario', 'objetivo': {'tipo': 'eliminar', 'rondas': 9},
   'texto': tr('Cubrí a los prisioneros: derribá a los guardias antes de que lleguen más (9 rondas)', 'Cover the prisoners: take down the guards before more arrive (9 rounds)'),
   'unidades': {'vferroviario': U('aliado', [10, 12], 4, 4, 75), 'friedrich': U('aliado', [8, 13], 5, 4, 70), 'anneliese': U('aliado', [6, 12], 3, 4, 60), 'thomas': U('aliado', [12, 14], 3, 4, 60),
                'trapo_2': U('enemigo', [21, 11], 4, 4, 60), 'trapo_3': U('enemigo', [22, 13], 4, 4, 55), 'trapo_4': U('enemigo', [20, 14], 4, 3, 55)}},
  {'tipo': 'franjas', 'visibles': True},
  {'tipo': 'efecto', 'nombre': 'disparos', 'desde': ['nadie'], 'segundos': 0.4},
  {'tipo': 'tumbar', 'actor': 'anciano'},
  D('anneliese', '¡No!', 'No!', 'triste'),
  D('anciano', 'Tomá... esto es para vos, Viktor.', 'Take this... it is for you, Viktor.', 'dolor'),
  TJ('Una foto: tres hombres. Uno es Martin. Otro es Erich Falk. El tercero lleva un traje occidental, elegante.', 'A photo: three men. One is Martin. Another is Erich Falk. The third wears an elegant Western suit.', 5, estilo='documento'),
  D('vferroviario', 'Martin... y Falk.', 'Martin... and Falk.', 'triste'),
  D('friedrich', '¿Y el tercero?', 'And the third one?'),
  D('vferroviario', 'No lo conozco.', "I don't know him."),
  TJ('Todavía.', 'Not yet.', 3, estilo='documento'),
  F('negro', 2), G('final'), TJ('Fin del Acto II', 'End of Act II'), {'tipo': 'fin'}]}
json.dump(cap6, open('datos/campana/capitulo_6.json', 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
caps = json.load(open('datos/campana/capitulos.json', encoding='utf-8'))
for c in caps['capitulos']:
    if c['id'] == 'capitulo_6': c['disponible'] = True
if not any(c['id'] == 'capitulo_7' for c in caps['capitulos']):
    caps['capitulos'].append({'id': 'capitulo_7', 'titulo': tr('Acto III: El Muro', 'Act III: The Wall'), 'disponible': False})
json.dump(caps, open('datos/campana/capitulos.json', 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
p = 'datos/mundo/misiones.json'; d = json.load(open(p, encoding='utf-8'))
if not any(l['id'] == 'nacht_capitulo_6' for l in d['logros']):
    d['logros'].append({'id': 'nacht_capitulo_6', 'nombre': 'El tren de las tres', 'descripcion': 'Terminá el Capítulo 6 de Nacht über Berlin.', 'condicion': {'campana': 'nacht_capitulo_6'}})
json.dump(d, open(p, 'w', encoding='utf-8'), ensure_ascii=False, indent=2)
tr('El tren de las tres', "The Three O'Clock Train"); tr('Terminá el Capítulo 6 de Nacht über Berlin.', 'Finish Chapter 6 of Nacht über Berlin.')
for es, en in {'Mover la aguja {a}': 'Move points {a}', 'Dar la señal': 'Give the signal', 'El tren va a: {v}': 'The train goes to: {v}', 'recta': 'straight', 'desvío': 'diverging',
               'El tren va a ir a la vía equivocada.': 'The train is heading for the wrong track.', 'El tren pasó de largo. Probá de nuevo.': 'The train went past. Try again.',
               'Tiempo: {s} s': 'Time: {s} s', 'Esas agujas no existen o el capítulo no empezó.': "Those points don't exist or the chapter hasn't started.", 'Faltan agujas.': 'Some points are missing.'}.items():
    tr(es, en)
json.dump(EN, open(RUTA_EN, 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
print('capítulo 6:', len(cap6['pasos']), 'pasos')
