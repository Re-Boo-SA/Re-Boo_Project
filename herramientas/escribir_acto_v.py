#!/usr/bin/env python3
"""Escribe los guiones del Acto V «Los muertos también hablan» (capítulos 11, 12 y 13) con su inglés al lado.
Correr desde la raíz:  python herramientas/escribir_acto_v.py   (después: python herramientas/traducir_contenido.py)
Dato de época verificado (Bundesarchiv, «Agentenschleuse und Gespensteröffnung» y «Die Festung»): el Bahnhof Friedrichstraße quedaba
en Berlín Oriental pero seguía siendo parte del transporte de Berlín Occidental; desde los andenes del Oeste se podía cambiar del S-Bahn
al U-Bahn sin pasar el control, bajo vigilancia total, y unos biombos tapaban el andén del Este."""
import json, os
RUTA_EN = 'herramientas/traducciones_capitulos.json'
EN = json.load(open(RUTA_EN, encoding='utf-8')) if os.path.exists(RUTA_EN) else {}
def tr(es, en):
    EN[es] = en; return es
D = lambda quien, es, en, emocion='neutral': {'tipo': 'dialogo', 'quien': quien, 'emocion': emocion, 'texto': tr(es, en)}
F = lambda a, seg=1: {'tipo': 'fundido', 'a': a, 'segundos': seg}
TJ = lambda es, en, seg=3, **k: dict({'tipo': 'tarjeta', 'texto': tr(es, en), 'segundos': seg}, **k)
G = lambda punto: {'tipo': 'guardar', 'punto': punto}
SI = lambda clave, valor, pasos: {'tipo': 'si', 'clave': clave, 'valor': valor, 'pasos': pasos}
FR = lambda v: {'tipo': 'franjas', 'visibles': v}
U = lambda lado, celda, vida, mov, punt: {'lado': lado, 'celda': celda, 'vida': vida, 'mov': mov, 'punteria': punt}
INGRID = lambda es, en: [{'tipo': 'escena', 'mapa': 'redaccion_1975', 'actores': ['ingrid'], 'jugador': None, 'props': ['caja']},
                         F('escena', 1.5), TJ('Berlín Occidental · 1975', 'West Berlin · 1975'), D('ingrid', es, en, 'triste'), F('negro', 1.5)]
base = json.load(open('datos/campana/capitulo_10.json', encoding='utf-8'))
PERS = dict(base['personajes'], padre='El padre de Viktor', maton='Un secuestrador', cliente='Un comensal')
for a, b in (('El padre de Viktor', "Viktor's father"), ('Un secuestrador', 'A kidnapper'), ('Un comensal', 'A diner')): tr(a, b)
def capitulo(id_, titulo_es, titulo_en, xp, logro, logro_es, logro_en, pasos):
    tr(titulo_es, titulo_en); tr(logro_es, logro_en)
    desc = 'Terminá el %s de Nacht über Berlin.' % titulo_es.split(':')[0]
    tr(desc, 'Finish %s of Nacht über Berlin.' % titulo_en.split(':')[0])
    return {'_leeme': base['_leeme'], 'id': id_, 'titulo': titulo_es, 'duracionMinima': 45, 'aviso': base['aviso'],
            'recompensa': {'xp': xp, 'logro': logro}, 'personajes': PERS, 'pasos': pasos}, (logro, logro_es, desc)

# ---------------- Capítulo 11: La última cena ----------------
cap11, l11 = capitulo('capitulo_11', 'Capítulo 11: La última cena', 'Chapter 11: The Last Supper', 300, 'nacht_capitulo_11', 'La última cena', 'The Last Supper', INGRID(
  'La foto once es de una mesa servida para cinco. Un plato quedó sin tocar.', 'Photo eleven is of a table laid for five. One plate was left untouched.') + [
  TJ('Acto V · Los muertos también hablan', 'Act V · The Dead Also Speak', 3, estilo='documento'),
  {'tipo': 'escena', 'mapa': 'restaurante', 'actores': ['martin', 'lukas', 'anneliese', 'thomas', 'cliente_1', 'cliente_2'], 'jugador': 'viktor'},
  F('escena', 1), TJ('Berlín Occidental · 1962 · Un restaurante apartado', 'West Berlin · 1962 · An out-of-the-way restaurant', 3), G('cena'),
  TJ('Por primera vez en años, Viktor siente que tiene una familia.', 'For the first time in years, Viktor feels he has a family.', 3),
  {'tipo': 'paseo', 'texto': tr('Hablá con los de la mesa', 'Talk to the people at the table'), 'minimo': 3,
   'fin': {'actor': 'martin', 'accion': tr('Escuchar a Martin', 'Listen to Martin')}, 'puntos': [
    {'objeto': 'anneliese', 'accion': tr('Hablar', 'Talk'), 'pasos': [
      D('anneliese', 'Nunca pensé que iba a sentarme a comer con un Adler. Con dos.', 'I never thought I would sit down to eat with an Adler. With two of them.', 'contento'),
      D('viktor', 'Yo tampoco.', 'Neither did I.')]},
    {'objeto': 'lukas', 'accion': 'Hablar', 'pasos': [
      D('lukas', 'Este lugar es seguro. El dueño no hace preguntas.', "This place is safe. The owner doesn't ask questions."),
      D('viktor', 'Nadie hace preguntas hasta que alguien le paga para que las haga.', 'Nobody asks questions until someone pays them to.')]},
    {'objeto': 'thomas', 'accion': 'Hablar', 'pasos': [
      D('thomas', 'Viktor... tengo que contarte algo. Después. Cuando estemos solos.', 'Viktor... I have to tell you something. Later. When we are alone.'),
      D('viktor', 'Después.', 'Later.')]}]},
  FR(True),
  {'tipo': 'elegir', 'clave': 'brindis', 'pregunta': tr('Martin levanta el vaso. ¿Por quién brinda Viktor?', 'Martin raises his glass. Who does Viktor drink to?'), 'opciones': [
    {'valor': 'friedrich', 'texto': tr('Por Friedrich.', 'To Friedrich.')},
    {'valor': 'ausentes', 'texto': tr('Por los que quedaron del otro lado.', 'To those left on the other side.')},
    {'valor': 'nosotros', 'texto': tr('Por nosotros. Por lo que queda.', "To us. To what's left.")}]},
  SI('brindis', 'friedrich', [D('martin', 'Por Friedrich. No lo conocí, y me hubiera gustado.', "To Friedrich. I never met him, and I wish I had.")]),
  SI('brindis', 'ausentes', [D('anneliese', 'Por mi padre, entonces.', 'To my father, then.', 'triste')]),
  SI('brindis', 'nosotros', [D('lukas', 'Por nosotros.', 'To us.')]),
  D('martin', 'Tengo pruebas de que Konrad Weiss financia Projekt Morgen desde Occidente.', 'I have proof that Konrad Weiss funds Projekt Morgen from the West.'),
  D('martin', 'Y Erich Falk no es el que manda. El que manda es un hombre al que llaman «Der Architekt».', 'And Erich Falk is not the one in charge. The one in charge is a man they call “Der Architekt”.'),
  D('martin', 'El Arquitecto. Nadie sabe quién es. Pero tiene contactos en los dos bloques.', 'The Architect. Nobody knows who he is. But he has contacts in both blocs.'),
  TJ('Thomas escucha con atención.', 'Thomas listens closely.', 2),
  D('thomas', 'Ya vuelvo. Voy al baño.', "I'll be right back. Going to the toilet."),
  FR(False),
  {'tipo': 'mover', 'actor': 'thomas', 'a': 'thomas_sale', 'segundos': 2.5},
  {'tipo': 'desaparecer', 'actores': ['thomas']},
  TJ('Pasan diez minutos.', 'Ten minutes go by.', 2),
  D('viktor', '¿Y Thomas?', 'Where is Thomas?'),
  G('baño'),
  {'tipo': 'objetivo', 'texto': tr('Fijate dónde está Thomas', 'Find out where Thomas is'), 'zona': 'bano', 'radio': 70, 'accion': tr('Revisar el baño', 'Check the toilet')},
  TJ('El baño está vacío. La ventana, abierta.', 'The toilet is empty. The window is open.', 3),
  {'tipo': 'objetivo', 'texto': tr('Salí a buscarlo', 'Go out and look for him'), 'zona': 'salida', 'radio': 70, 'accion': tr('Salir a la calle', 'Go out into the street')},
  F('negro', 1),
  {'tipo': 'escena', 'mapa': 'calle', 'actores': [], 'jugador': 'viktor', 'props': ['auto_abierto']},
  F('escena', 1), G('calle'),
  {'tipo': 'interactuar', 'objeto': 'auto_abierto', 'texto': tr('Revisá el auto', 'Check the car'), 'accion': tr('Mirar el auto', 'Look at the car'), 'quedarse': True},
  FR(True),
  TJ('Las puertas abiertas. Sangre en el asiento de atrás.', 'The doors are open. Blood on the back seat.', 3, estilo='documento'),
  D('viktor', 'Thomas...', 'Thomas...', 'enojado'),
  TJ('Thomas fue secuestrado.', 'Thomas has been kidnapped.', 3, estilo='documento'),
  F('negro', 2), G('final'), TJ('Fin del Capítulo 11', 'End of Chapter 11'), {'tipo': 'fin'}])

# ---------------- Capítulo 12: El precio de la verdad ----------------
OP = lambda es, en, riesgo, bien_es, bien_en, mal_es, mal_en, ganancia=1: {'texto': tr(es, en), 'riesgo': riesgo, 'ganancia': ganancia, 'bien': tr(bien_es, bien_en), 'mal': tr(mal_es, mal_en)}
persecucion = {'tipo': 'persecucion_auto', 'id': 'huida_almacen', 'texto': tr('Escapá con Thomas', 'Get away with Thomas'), 'ventaja': 2, 'segundos': 7,
 'sinDecidir': tr('Dudaste y el auto de atrás se les pegó.', 'You hesitated and the car behind closed in.'), 'tramos': [
  {'texto': tr('Dos autos salen detrás de ustedes desde el almacén. ¿Por dónde?', 'Two cars pull out of the warehouse behind you. Which way?'), 'opciones': [
    OP('Entre los galpones del puerto', 'Between the harbour sheds', 40, 'Entre los galpones sobra sombra: pierden a uno.', "There's plenty of shadow between the sheds: they lose one.", 'Un camión cierra el paso: hay que dar la vuelta.', 'A lorry blocks the way: you have to turn back.'),
    OP('Por la avenida, a fondo', 'Down the avenue, flat out', 55, 'En la recta les sacan unos metros.', 'On the straight you gain a few metres.', 'En la avenida no hay dónde esconderse: se acercan.', "There's nowhere to hide on the avenue: they close in.")]},
  {'texto': tr('Lukas baja la ventanilla: «Puedo tirarle a las ruedas».', 'Lukas rolls down the window: “I can shoot at the tyres.”'), 'opciones': [
    OP('Que lo haga', 'Let him', 45, 'Revienta una rueda y el primer auto se va contra el cordón.', 'A tyre bursts and the first car hits the kerb.', 'Falla. Ahora también les disparan a ellos.', 'He misses. Now they are shooting back.', ganancia=2),
    OP('No: que no se asome', "No: he mustn't lean out", 35, 'Viktor encara una curva cerrada y gana terreno.', 'Viktor takes a tight bend and gains ground.', 'Viktor frena tarde y casi chocan.', 'Viktor brakes late and they nearly crash.')]},
  {'texto': tr('El puente sobre el canal empieza a levantarse.', 'The bridge over the canal starts to rise.'), 'opciones': [
    OP('Saltar antes de que suba', 'Jump it before it rises', 55, 'Vuelan medio metro y caen del otro lado. Atrás, se quedan.', 'They fly half a metre and land on the other side. Behind them, the others are stuck.', 'No llegan: frenan en seco al borde.', "They don't make it: they stop dead at the edge.", ganancia=2),
    OP('Doblar por la orilla', 'Turn along the bank', 35, 'Por la orilla, entre los árboles, ganan unos segundos.', 'Along the bank, among the trees, they gain a few seconds.', 'La orilla está embarrada y patinan.', 'The bank is muddy and they skid.')]},
  {'texto': tr('Último cruce antes de los barrios del Oeste.', 'Last crossing before the western districts.'), 'opciones': [
    OP('Entre los camiones de reparto', 'Between the delivery lorries', 40, 'Se meten entre dos camiones y desaparecen.', 'They slip between two lorries and vanish.', 'Un camión se detiene de golpe: casi los encierra.', 'A lorry stops dead: it almost boxes them in.'),
    OP('Por el medio del tráfico', 'Through the middle of the traffic', 50, 'Esquivan tres autos y los perseguidores quedan atrapados.', 'They dodge three cars and the pursuers get stuck.', 'Una bocina, un volantazo, y el auto de atrás se acerca.', 'A horn, a swerve, and the car behind gets closer.')]}]}
cap12, l12 = capitulo('capitulo_12', 'Capítulo 12: El precio de la verdad', 'Chapter 12: The Price of Truth', 400, 'nacht_capitulo_12', 'El precio de la verdad', 'The Price of Truth', INGRID(
  'La foto doce es de un almacén, de lejos. Alguien la sacó desde un auto, con prisa.', 'Photo twelve is of a warehouse, from a distance. Someone took it from a car, in a hurry.') + [
  {'tipo': 'escena', 'mapa': 'departamento', 'actores': ['anneliese', 'lukas', 'martin'], 'jugador': 'viktor', 'props': ['fotos']},
  F('escena', 1), G('llamada'),
  {'tipo': 'efecto', 'nombre': 'telefono', 'segundos': 2},
  D('lukas', '¿Sí? ...', 'Yes? ...'),
  TJ('Thomas está retenido en un almacén. Los secuestradores quieren los documentos a cambio.', 'Thomas is being held in a warehouse. The kidnappers want the documents in exchange.', 4),
  D('viktor', 'Vamos ahora.', "We're going now.", 'enojado'),
  D('martin', 'Esperá. Es lo que quieren: que entres apurado y sin pensar.', "Wait. That's what they want: for you to rush in without thinking."),
  D('viktor', 'No voy a esperar.', "I'm not waiting.", 'enojado'),
  {'tipo': 'elegir', 'clave': 'rescate_thomas', 'pregunta': tr('¿Cómo entran al almacén?', 'How do you go into the warehouse?'), 'opciones': [
    {'valor': 'sigilo', 'texto': tr('En silencio. Sin disparar si no hace falta.', "Quietly. No shooting unless we have to.")},
    {'valor': 'asalto', 'texto': tr('Con todo. Ahora.', 'All in. Now.')}]},
  F('negro', 1),
  {'tipo': 'escena', 'mapa': 'almacen', 'actores': ['martin', 'lukas', 'anneliese', 'thomas', 'maton_1', 'maton_2', 'maton_3', 'maton_4'], 'jugador': 'viktor'},
  F('escena', 1), TJ('Un almacén junto al canal · De noche', 'A warehouse by the canal · At night', 3), G('almacen'),
  SI('rescate_thomas', 'sigilo', [
    D('martin', 'Lukas y yo cubrimos la puerta. Andá.', 'Lukas and I will cover the door. Go.'),
    {'tipo': 'sigilo', 'guardias': ['maton_1', 'maton_2', 'maton_3'], 'meta': 'oficina', 'radio': 45, 'texto': tr('Llegá a la oficina sin que te vean', 'Reach the office without being seen')},
    {'tipo': 'objetivo', 'texto': tr('Desatá a Thomas', 'Untie Thomas'), 'zona': 'oficina', 'radio': 70, 'accion': tr('Desatar a Thomas', 'Untie Thomas')},
    D('thomas', 'Sabía que ibas a venir.', 'I knew you would come.', 'dolor'),
    FR(True), D('maton_4', '¡Eh! ¡Se llevan al chico!', "Hey! They're taking the kid!", 'enojado'), FR(False),
    {'tipo': 'combate', 'id': 'almacen_salida', 'mapa': 'almacen', 'lider': 'viktor', 'objetivo': {'tipo': 'eliminar', 'rondas': 10},
     'texto': tr('Te descubrieron a la salida: derribá a los secuestradores antes de que lleguen más (10 rondas)', 'They spotted you on the way out: take down the kidnappers before more arrive (10 rounds)'),
     'unidades': {'viktor': U('aliado', [18, 7], 4, 4, 80), 'thomas': U('aliado', [19, 8], 2, 3, 45), 'martin': U('aliado', [17, 8], 4, 4, 70), 'lukas': U('aliado', [16, 7], 3, 4, 65),
                  'maton_1': U('enemigo', [6, 6], 3, 4, 55), 'maton_2': U('enemigo', [8, 10], 3, 4, 50), 'maton_3': U('enemigo', [11, 7], 3, 4, 50), 'maton_4': U('enemigo', [12, 12], 3, 3, 50)}}]),
  SI('rescate_thomas', 'asalto', [
    D('martin', 'Si lo vamos a hacer así, lo hacemos juntos.', "If we're doing it this way, we do it together.", 'enojado'),
    {'tipo': 'combate', 'id': 'almacen_asalto', 'mapa': 'almacen', 'lider': 'viktor', 'objetivo': {'tipo': 'eliminar', 'rondas': 10},
     'texto': tr('Entran a los tiros: derribá a los secuestradores antes de que se lleven a Thomas (10 rondas)', 'You go in shooting: take down the kidnappers before they move Thomas (10 rounds)'),
     'unidades': {'viktor': U('aliado', [1, 7], 4, 4, 80), 'martin': U('aliado', [1, 9], 4, 4, 70), 'lukas': U('aliado', [2, 8], 3, 4, 65), 'anneliese': U('aliado', [2, 10], 3, 4, 60),
                  'maton_1': U('enemigo', [9, 5], 4, 4, 55), 'maton_2': U('enemigo', [12, 9], 4, 4, 55), 'maton_3': U('enemigo', [15, 6], 4, 4, 55), 'maton_4': U('enemigo', [17, 11], 4, 3, 55)}},
    {'tipo': 'objetivo', 'texto': tr('Desatá a Thomas', 'Untie Thomas'), 'zona': 'oficina', 'radio': 70, 'accion': 'Desatar a Thomas'},
    D('thomas', 'Sabía que ibas a venir.', 'I knew you would come.', 'dolor')]),
  G('huida'),
  persecucion,
  G('herido'),
  FR(True),
  TJ('Ya casi están a salvo cuando una bala atraviesa la luneta de atrás.', 'They are almost safe when a bullet comes through the rear window.', 4),
  {'tipo': 'efecto', 'nombre': 'disparos', 'desde': ['nadie'], 'segundos': 0.4},
  {'tipo': 'escena', 'mapa': 'calle', 'actores': ['martin', 'anneliese', 'lukas', 'thomas'], 'jugador': 'viktor'},
  {'tipo': 'tumbar', 'actor': 'martin'},
  F('escena', 1), FR(False),
  {'tipo': 'objetivo', 'texto': tr('Andá con Martin', 'Go to Martin'), 'zona': 'martin', 'radio': 70, 'accion': tr('Apretar la herida', 'Press on the wound')},
  FR(True),
  TJ('Viktor intenta frenar la sangre.', 'Viktor tries to stop the bleeding.', 2),
  D('martin', 'Hay una caja... en la estación de Friedrichstraße.', "There's a box... at Friedrichstraße station.", 'dolor'),
  D('viktor', 'No hables.', "Don't talk.", 'triste'),
  D('martin', 'Escuchame.', 'Listen to me.', 'dolor'),
  TJ('Martin respira con dificultad.', 'Martin struggles to breathe.', 2),
  D('martin', 'Papá no murió como te dijeron.', "Dad didn't die the way they told you.", 'dolor'),
  TJ('Viktor se queda inmóvil.', 'Viktor freezes.', 2),
  D('martin', 'Descubrió Projekt Morgen antes que nadie. Lo mataron por orden de Konrad Weiss.', 'He discovered Projekt Morgen before anyone else. He was killed on Konrad Weiss’s orders.', 'dolor'),
  D('martin', 'Esto no empezó después de la guerra, Viktor. Viene de mucho antes.', "This didn't start after the war, Viktor. It goes back much further.", 'dolor'),
  TJ('Martin le pone en la mano una llave chiquita.', 'Martin presses a small key into his hand.', 3, estilo='documento'),
  TJ('Martin Adler muere.', 'Martin Adler dies.', 3, estilo='documento'),
  F('negro', 2.5),
  TJ('Viktor pierde a su hermano por segunda vez. Pero ahora tiene algo que nunca tuvo: una dirección. Y una llave.', 'Viktor loses his brother for the second time. But now he has something he never had: an address. And a key.', 5),
  G('final'), TJ('Fin del Capítulo 12', 'End of Chapter 12'), {'tipo': 'fin'}])

# ---------------- Capítulo 13: La caja de Friedrichstraße ----------------
cap13, l13 = capitulo('capitulo_13', 'Capítulo 13: La caja de Friedrichstraße', 'Chapter 13: The Box at Friedrichstraße', 300, 'nacht_capitulo_13', 'La caja de Friedrichstraße', 'The Box at Friedrichstraße', INGRID(
  'La foto trece es solo una mano abierta. En la palma, una llave chiquita.', 'Photo thirteen is just an open hand. In the palm, a small key.') + [
  TJ('Bahnhof Friedrichstraße · Pocos días después', 'Bahnhof Friedrichstraße · A few days later', 3),
  TJ('La estación queda en el Este, pero algunos andenes siguen siendo del Oeste: desde ahí se cambia de tren sin pasar el control. Igual, todo está vigilado.', 'The station is in the East, but some platforms still belong to the West: from there you can change trains without going through the checkpoint. Even so, everything is watched.', 5),
  {'tipo': 'escena', 'mapa': 'friedrichstrasse', 'actores': ['anneliese', 'thomas', 'lukas', 'trapo_1', 'trapo_2'], 'jugador': 'viktor', 'props': ['paquete', 'vagon_1', 'vagon_2', 'vagon_3']},
  F('escena', 1), G('estacion'),
  D('thomas', 'Martin dijo que está en un armario de mantenimiento, al fondo del andén.', "Martin said it's in a maintenance cupboard, at the end of the platform."),
  D('lukas', 'La Transportpolizei pasa cada pocos minutos. Que no te vean abrirlo.', "The Transportpolizei come by every few minutes. Don't let them see you open it."),
  {'tipo': 'sigilo', 'guardias': ['trapo_1', 'trapo_2'], 'meta': 'meta_paquete', 'radio': 45, 'texto': tr('Llegá al armario sin que te vean', 'Reach the cupboard without being seen')},
  {'tipo': 'interactuar', 'objeto': 'paquete', 'texto': tr('Abrí la caja', 'Open the box'), 'accion': tr('Abrir con la llave de Martin', "Open it with Martin's key")},
  FR(True),
  TJ('Fotos de familia. Documentos de los años treinta. Registros bancarios. Cartas. Una cinta grabada. Y un cuaderno escrito a mano por su padre.', 'Family photos. Documents from the 1930s. Bank records. Letters. A tape recording. And a notebook handwritten by his father.', 6, estilo='documento'),
  F('negro', 1.5), FR(False),
  {'tipo': 'escena', 'mapa': 'departamento', 'actores': ['anneliese', 'thomas', 'lukas'], 'jugador': 'viktor', 'props': ['fotos']},
  F('escena', 1), G('caja'),
  {'tipo': 'elegir', 'clave': 'primero_caja', 'pregunta': tr('¿Qué mira primero?', 'What does he look at first?'), 'opciones': [
    {'valor': 'fotos', 'texto': tr('Las fotos.', 'The photos.')}, {'valor': 'cuaderno', 'texto': tr('El cuaderno.', 'The notebook.')}, {'valor': 'cartas', 'texto': tr('Las cartas.', 'The letters.')}]},
  SI('primero_caja', 'fotos', [TJ('En una foto, su madre se ríe. Martin, de chico, le tapa la lente a la cámara.', 'In one photo, his mother is laughing. Martin, as a boy, is covering the camera lens.', 4)]),
  SI('primero_caja', 'cuaderno', [TJ('La letra de su padre. Nombres, fechas, montos. Y una palabra que se repite: «Morgen».', 'His father’s handwriting. Names, dates, sums. And one word, repeated: “Morgen”.', 4)]),
  SI('primero_caja', 'cartas', [TJ('Cartas que nunca se mandaron, dirigidas a «mis hijos». Ninguna tiene fecha.', 'Letters that were never sent, addressed to “my sons”. None of them is dated.', 4)]),
  {'tipo': 'objetivo', 'texto': tr('Escuchá la grabación', 'Listen to the recording'), 'zona': 'frente_fotos', 'radio': 80, 'accion': tr('Poner la cinta', 'Play the tape')},
  FR(True),
  TJ('La voz de su padre.', "His father's voice.", 2, estilo='documento'),
  D('padre', 'En los últimos meses de la guerra empecé a trabajar con una red de funcionarios.', 'In the last months of the war I began working with a network of officials.'),
  D('padre', 'Se quedaban con la plata, los nombres y los papeles de gente perseguida.', 'They took the money, the names and the papers of people who were being persecuted.'),
  D('padre', 'Después de la guerra, la red se partió. Unos terminaron en el Oeste. Otros, en el Este.', 'After the war, the network split. Some ended up in the West. Others, in the East.'),
  D('padre', 'La Guerra Fría les dio el escenario perfecto para seguir.', 'The Cold War gave them the perfect stage to carry on.'),
  D('padre', 'No eran espías al servicio de una idea. Eran criminales que usaban las ideas como herramientas.', 'They were not spies serving an idea. They were criminals who used ideas as tools.'),
  D('padre', 'Si estás escuchando esto, Viktor, quiere decir que encontraron a nuestra familia. No confíes en nadie que te diga que está tratando de salvarte.', "If you are listening to this, Viktor, it means they found our family. Don't trust anyone who tells you they are trying to save you.", 'triste'),
  TJ('Viktor apaga la grabadora.', 'Viktor switches off the recorder.', 2),
  TJ('Mira a Anneliese. Después, a Lukas.', 'He looks at Anneliese. Then at Lukas.', 3),
  TJ('Por primera vez, no confía en ninguno de los dos.', 'For the first time, he trusts neither of them.', 4, estilo='documento'),
  F('negro', 2), G('final'), TJ('Fin del Acto V', 'End of Act V'), {'tipo': 'fin'}])

for cap in (cap11, cap12, cap13):
    json.dump(cap, open('datos/campana/%s.json' % cap['id'], 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
caps = json.load(open('datos/campana/capitulos.json', encoding='utf-8'))
for c in caps['capitulos']:
    if c['id'] == 'capitulo_11': c['disponible'] = True
for cap in (cap12, cap13):
    if not any(c['id'] == cap['id'] for c in caps['capitulos']): caps['capitulos'].append({'id': cap['id'], 'titulo': cap['titulo'], 'disponible': True})
if not any(c['id'] == 'capitulo_14' for c in caps['capitulos']):
    caps['capitulos'].append({'id': 'capitulo_14', 'titulo': tr('Capítulo 14: El hombre que vendía la paz', 'Chapter 14: The Man Who Sold Peace'), 'disponible': False})
json.dump(caps, open('datos/campana/capitulos.json', 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
p = 'datos/mundo/misiones.json'; d = json.load(open(p, encoding='utf-8'))
for (lid, nom, desc) in (l11, l12, l13):
    if not any(l['id'] == lid for l in d['logros']): d['logros'].append({'id': lid, 'nombre': nom, 'descripcion': desc, 'condicion': {'campana': lid}})
json.dump(d, open(p, 'w', encoding='utf-8'), ensure_ascii=False, indent=2)
json.dump(EN, open(RUTA_EN, 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
print('capítulos 11, 12 y 13:', len(cap11['pasos']), len(cap12['pasos']), len(cap13['pasos']), 'pasos')
