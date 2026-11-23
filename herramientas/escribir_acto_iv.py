#!/usr/bin/env python3
"""Escribe los guiones del Acto IV «El hombre del otro lado» (capítulos 8, 9 y 10) con su inglés al lado.
Correr desde la raíz:  python herramientas/escribir_acto_iv.py   (después: python herramientas/traducir_contenido.py)
Dato de época verificado (visitberlin / Gedenkstätte Berliner Mauer, Bundesarchiv): desde el 13 de agosto de 1961, dos líneas de U-Bahn
y una de S-Bahn pasaban bajo Berlín Oriental; sus estaciones quedaron cerradas («Geisterbahnhöfe»), en penumbra y custodiadas por guardias
armados, y los trenes del Oeste pasaban sin parar."""
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
INGRID = lambda es, en: [{'tipo': 'escena', 'mapa': 'redaccion_1975', 'actores': ['ingrid'], 'jugador': None, 'props': ['caja']},
                         F('escena', 1.5), TJ('Berlín Occidental · 1975', 'West Berlin · 1975'), D('ingrid', es, en, 'triste'), F('negro', 1.5)]
base = json.load(open('datos/campana/capitulo_7.json', encoding='utf-8'))
PERS = dict(base['personajes'], lukas='Lukas Brandt', martin='Martin Adler', paul='Paul Lange', agente='Un desconocido')
tr('Un desconocido', 'A stranger')
def capitulo(id_, titulo_es, titulo_en, xp, logro, logro_es, logro_en, pasos):
    tr(titulo_es, titulo_en); tr(logro_es, logro_en)
    tr('Terminá el %s de Nacht über Berlin.' % titulo_es.split(':')[0], 'Finish %s of Nacht über Berlin.' % titulo_en.split(':')[0])
    return {'_leeme': base['_leeme'] + " seguir: seguir a alguien por su ruta sin que te vea cuando mira hacia atrás (ni perderlo de vista).",
            'id': id_, 'titulo': titulo_es, 'duracionMinima': 45, 'aviso': base['aviso'], 'recompensa': {'xp': xp, 'logro': logro},
            'personajes': PERS, 'pasos': pasos}, (logro, logro_es, 'Terminá el %s de Nacht über Berlin.' % titulo_es.split(':')[0])

# ---------------- Capítulo 8: Lukas ----------------
cap8, l8 = capitulo('capitulo_8', 'Capítulo 8: Lukas', 'Chapter 8: Lukas', 250, 'nacht_capitulo_8', 'Lukas', 'Lukas', INGRID(
  'La novena foto: un muchacho con la cara lastimada, apoyado en una puerta. Atrás: «Lukas Brandt».', 'The ninth photo: a young man with a bruised face, leaning in a doorway. On the back: “Lukas Brandt”.') + [
  TJ('Acto IV · El hombre del otro lado', 'Act IV · The Man from the Other Side', 3, estilo='documento'),
  TJ('Pasaron varios meses. Del grupo quedan tres.', 'Several months have passed. Three of the group are left.', 3),
  {'tipo': 'escena', 'mapa': 'departamento', 'actores': ['anneliese', 'thomas'], 'jugador': 'viktor', 'props': ['fotos']},
  F('escena', 1), TJ('Berlín Occidental · Invierno de 1962', 'West Berlin · Winter 1962'), G('casa'),
  D('anneliese', 'Comé algo, Viktor. Hace dos días que no comés.', "Eat something, Viktor. You haven't eaten in two days."),
  D('viktor', 'No tengo hambre.', "I'm not hungry.", 'enojado'),
  D('thomas', '…', '…', 'triste'),
  {'tipo': 'interactuar', 'objeto': 'fotos', 'texto': tr('Mirá la foto de la pared', 'Look at the photo on the wall'), 'accion': tr('Mirar la foto', 'Look at the photo'), 'quedarse': True},
  D('viktor', 'Martin. Falk. Y el del traje.', 'Martin. Falk. And the one in the suit.', 'enojado'),
  {'tipo': 'efecto', 'nombre': 'puerta'}, {'tipo': 'efecto', 'nombre': 'puerta'}, {'tipo': 'efecto', 'nombre': 'puerta'},
  D('thomas', '¿Esperamos a alguien?', 'Are we expecting anyone?'),
  D('anneliese', 'No.', 'No.'),
  {'tipo': 'objetivo', 'texto': tr('Abrí la puerta', 'Open the door'), 'zona': 'puerta', 'radio': 70, 'accion': tr('Abrir con el arma en la mano', 'Open with the gun in hand')},
  {'tipo': 'aparecer', 'actores': ['lukas']},
  {'tipo': 'franjas', 'visibles': True},
  TJ('Del otro lado hay un hombre joven. La ropa sucia, una herida en la cara, días sin dormir.', 'On the other side stands a young man. Dirty clothes, a wound on his face, days without sleep.', 4),
  D('lukas', 'Me llamo Lukas Brandt. Sé lo que es Projekt Morgen.', 'My name is Lukas Brandt. I know what Projekt Morgen is.'),
  {'tipo': 'elegir', 'clave': 'trato_lukas', 'pregunta': tr('¿Qué hace Viktor?', 'What does Viktor do?'), 'opciones': [
    {'valor': 'escuchar', 'texto': tr('Hablá. Tenés un minuto.', 'Talk. You have one minute.')},
    {'valor': 'echar', 'texto': tr('Andate antes de que dispare.', 'Leave before I shoot.')}]},
  SI('trato_lukas', 'echar', [D('lukas', 'Martin Adler.', 'Martin Adler.', 'enojado'), D('viktor', '¿Qué dijiste?', 'What did you say?', 'enojado'),
    D('lukas', 'Martin Adler. Tu hermano. ¿Ahora me dejás pasar?', 'Martin Adler. Your brother. Will you let me in now?')]),
  SI('trato_lukas', 'escuchar', [D('lukas', 'Gracias. Lo que vengo a contarte es sobre tu hermano. Martin Adler.', "Thank you. What I've come to tell you is about your brother. Martin Adler.")]),
  D('lukas', 'Tu hermano no murió en 1948.', "Your brother didn't die in 1948."),
  D('lukas', 'Lo reclutó una organización que trabajaba entre el Este y el Oeste. Hacía de intermediario: documentos, plata, personas.', 'He was recruited by an organisation working between East and West. He was a go-between: documents, money, people.'),
  D('lukas', 'Durante años trabajó con Erich Falk.', 'For years he worked with Erich Falk.'),
  D('lukas', 'Hasta que entendió que Projekt Morgen no protegía a ningún país. Era una red propia: políticos, policías, funcionarios, gente del hampa.', "Until he realised that Projekt Morgen didn't protect any country. It was a network of its own: politicians, police, officials, criminals."),
  D('lukas', 'Y tu hermano decidió destruirla.', 'And your brother decided to destroy it.'),
  D('viktor', '¿Dónde está?', 'Where is he?', 'triste'),
  D('lukas', 'Eso es lo que vine a buscar con ustedes.', "That's what I came to find out with you."),
  D('thomas', '¿Y por qué te vamos a creer?', 'And why should we believe you?'),
  D('lukas', 'Porque si me equivoco, estoy muerto antes que ustedes.', "Because if I'm wrong, I'm dead before you are."),
  F('negro', 2), G('final'), TJ('Fin del Capítulo 8', 'End of Chapter 8'), {'tipo': 'fin'}])

# ---------------- Capítulo 9: La ciudad bajo la ciudad ----------------
cap9, l9 = capitulo('capitulo_9', 'Capítulo 9: La ciudad bajo la ciudad', 'Chapter 9: The City Beneath the City', 350, 'nacht_capitulo_9', 'La ciudad bajo la ciudad', 'The City Beneath the City', INGRID(
  'La décima foto está sacada bajo tierra: un andén vacío, un guardia y un cartel de estación que nadie lee.', 'The tenth photo was taken underground: an empty platform, a guard and a station sign nobody reads.') + [
  {'tipo': 'escena', 'mapa': 'tunel', 'actores': ['lukas', 'anneliese', 'thomas', 'paul', 'guardia_1', 'guardia_2'], 'jugador': 'viktor', 'props': ['caja']},
  F('escena', 1), TJ('Bajo Berlín · 1962', 'Beneath Berlin · 1962'), G('tunel'),
  D('paul', 'Por acá pasan los cables y el agua. Arriba hay una de las estaciones cerradas: los trenes del Oeste pasan sin parar.', 'Cables and water run through here. Above us is one of the closed stations: the trains from the West go through without stopping.'),
  D('paul', 'Me llamo Paul. Paul Lange. Trabajo en la BVG; si me ven con ustedes, no trabajo más.', "My name's Paul. Paul Lange. I work for the BVG; if they see me with you, I won't any more."),
  TJ('El andén está en penumbra. Lo custodian guardias armados.', 'The platform is dimly lit. Armed guards watch over it.', 3),
  {'tipo': 'sigilo', 'guardias': ['guardia_1', 'guardia_2'], 'meta': 'escondite', 'radio': 45, 'texto': tr('Pasá por debajo del andén hasta el escondite sin que te vean', 'Get past below the platform to the hiding place without being seen')},
  {'tipo': 'interactuar', 'objeto': 'caja', 'texto': tr('Revisá lo que hay en el escondite', 'Check what is in the hiding place'), 'accion': tr('Abrir la maleta', 'Open the suitcase'), 'quedarse': True},
  {'tipo': 'franjas', 'visibles': True},
  TJ('Documentos, sellos, dinero. Y una lista de nombres.', 'Documents, stamps, money. And a list of names.', 3, estilo='documento'),
  D('anneliese', 'Mirá este. Konrad Weiss.', 'Look at this one. Konrad Weiss.'),
  D('viktor', 'El del traje. El de la foto.', 'The one in the suit. The one in the photo.', 'enojado'),
  D('lukas', 'No es un empresario cualquiera. Mueve plata para gente importante de los dos lados.', 'He is no ordinary businessman. He moves money for important people on both sides.'),
  D('viktor', 'Entonces Falk no es el que manda.', "So Falk isn't the one in charge."),
  D('lukas', 'Falk obedece. Weiss paga. Y hay alguien más arriba.', 'Falk obeys. Weiss pays. And there is someone higher up.'),
  F('negro', 1.5), {'tipo': 'franjas', 'visibles': False},
  TJ('Thomas', 'Thomas', 2, estilo='documento'),
  TJ('Esta parte se juega como Thomas: Viktor no sabe nada.', "This part is played as Thomas: Viktor doesn't know anything.", 3),
  TJ('Thomas vio a Lukas hablar por teléfono a escondidas. Y Lukas lleva un arma distinta a la que traía cuando llegó.', 'Thomas saw Lukas making secret phone calls. And Lukas carries a different gun from the one he had when he arrived.', 5),
  {'tipo': 'escena', 'mapa': 'calle', 'actores': ['lukas', 'agente'], 'jugador': 'thomas'},
  F('escena', 1), TJ('Esa noche', 'That night', 2), G('noche'),
  {'tipo': 'seguir', 'objetivo': 'lukas', 'ruta': 'ruta_lukas', 'velocidad': 55, 'mirarSegundos': 1.6, 'lejos': 330,
   'texto': tr('Seguí a Lukas sin que te vea y sin perderlo', 'Follow Lukas without being seen and without losing him')},
  {'tipo': 'objetivo', 'texto': tr('Acercate a escuchar', 'Get close enough to listen'), 'zona': 'escucha', 'radio': 60, 'accion': tr('Escuchar', 'Listen')},
  {'tipo': 'franjas', 'visibles': True},
  TJ('Thomas no alcanza a escuchar todo. Solo una frase.', "Thomas can't hear everything. Just one sentence.", 3),
  D('agente', 'Adler todavía no sabe quién mató a su hermano.', "Adler still doesn't know who killed his brother."),
  F('negro', 1.5), {'tipo': 'franjas', 'visibles': False},
  {'tipo': 'escena', 'mapa': 'departamento', 'actores': ['viktor', 'anneliese'], 'jugador': 'thomas', 'props': ['fotos']},
  F('escena', 1), G('vuelta'),
  D('viktor', '¿Dónde estabas?', 'Where were you?'),
  D('thomas', 'Caminando. No podía dormir.', "Walking. I couldn't sleep.", 'triste'),
  TJ('Thomas no le cuenta nada a Viktor.', "Thomas doesn't tell Viktor anything.", 3, estilo='documento'),
  F('negro', 2), G('final'), TJ('Fin del Capítulo 9', 'End of Chapter 9'), {'tipo': 'fin'}])

# ---------------- Capítulo 10: El hermano ----------------
MIRAR = tr('Mirar', 'Look')
cap10, l10 = capitulo('capitulo_10', 'Capítulo 10: El hermano', 'Chapter 10: The Brother', 300, 'nacht_capitulo_10', 'El hermano', 'The Brother', INGRID(
  'La última foto del sobre es una estación vacía. Un banco. Y en el borde, fuera de foco, un hombre de abrigo gris.', 'The last photo in the envelope is an empty station. A bench. And at the edge, out of focus, a man in a grey coat.') + [
  {'tipo': 'escena', 'mapa': 'estacion_abandonada', 'actores': ['lukas'], 'jugador': 'viktor', 'props': ['horario']},
  F('escena', 1), TJ('Una pequeña estación abandonada · 1962', 'A small abandoned station · 1962'), G('estacion'),
  D('lukas', 'Me dijo que iba a venir. No me dijo cuándo.', "He said he'd come. He didn't say when."),
  D('viktor', 'Espero.', "I'll wait."),
  {'tipo': 'paseo', 'texto': tr('Esperá', 'Wait'), 'minimo': 2, 'fin': {'actor': 'martin', 'accion': tr('Acercarse', 'Go closer')}, 'puntos': [
    {'objeto': 'frente_horario', 'accion': MIRAR, 'pasos': [TJ('El horario es de antes de la guerra. Alguien lo tachó y nadie lo cambió.', 'The timetable is from before the war. Someone crossed it out and nobody replaced it.', 4)]},
    {'objeto': 'frente_banco', 'accion': tr('Sentarse', 'Sit down'), 'pasos': [TJ('Una hora.', 'One hour.', 2), TJ('Dos horas.', 'Two hours.', 2), D('viktor', '…', '…', 'triste')]},
    {'objeto': 'frente_vias', 'accion': MIRAR, 'pasos': [D('viktor', 'Por acá nos íbamos con papá a la laguna. Martin me llevaba de la mano.', 'We used to come through here with Dad on the way to the lake. Martin held my hand.', 'triste')]}]},
  {'tipo': 'franjas', 'visibles': True},
  {'tipo': 'mover', 'actor': 'martin', 'a': 'encuentro', 'segundos': 3, 'esperar': False},
  {'tipo': 'mover', 'actor': 'viktor', 'a': 'viktor_encuentro', 'segundos': 2},
  TJ('Unos cuarenta y cinco años. Envejecido. Un abrigo gris.', 'About forty-five. Worn. A grey coat.', 3),
  TJ('Es Martin Adler.', 'It is Martin Adler.', 3, estilo='documento'),
  D('martin', 'Creciste.', "You've grown."),
  TJ('Viktor llora, aunque intenta que no se note.', 'Viktor cries, though he tries to hide it.', 3),
  {'tipo': 'elegir', 'clave': 'recuerdo_martin', 'pregunta': tr('¿De qué hablan primero?', 'What do they talk about first?'), 'opciones': [
    {'valor': 'infancia', 'texto': tr('De cuando éramos chicos.', 'When we were kids.')},
    {'valor': 'padres', 'texto': tr('De mamá y papá.', 'Mum and Dad.')},
    {'valor': 'casa', 'texto': tr('De la casa donde crecimos.', 'The house where we grew up.')}]},
  SI('recuerdo_martin', 'infancia', [D('martin', 'Te subías al techo del galpón para ver los aviones del puente aéreo. Te tenía que bajar a los gritos.', 'You used to climb onto the shed roof to watch the airlift planes. I had to shout you down.', 'contento'),
    D('viktor', 'Y vos me decías que un día nos íbamos a ir en uno.', "And you used to tell me that one day we'd leave on one.")]),
  SI('recuerdo_martin', 'padres', [D('martin', 'Mamá escondía la radio debajo de la cama. Decía que si la escuchaban los vecinos, nos llevaban a todos.', 'Mum hid the radio under the bed. She said that if the neighbours heard it, they would take us all away.', 'triste'),
    D('viktor', 'Papá nunca más dijo tu nombre.', 'Dad never said your name again.', 'triste')]),
  SI('recuerdo_martin', 'casa', [D('martin', 'El tercer escalón crujía. Yo lo saltaba para que no me oyeran volver tarde.', 'The third step creaked. I used to skip it so they wouldn’t hear me come home late.'),
    D('viktor', 'La casa ya no existe. La calle tampoco.', "The house doesn't exist any more. Nor does the street.", 'triste')]),
  D('viktor', '¿Por qué desapareciste?', 'Why did you disappear?', 'enojado'),
  D('martin', 'Me reclutaron para una red clandestina. Al principio llevaba documentos. Después, personas.', 'I was recruited into a clandestine network. At first I carried documents. Then people.', 'triste'),
  D('martin', 'Descubrí que a algunos de los que huían los mataban, para usar su desaparición como propaganda.', 'I found out that some of those fleeing were killed, so their disappearance could be used as propaganda.', 'triste'),
  D('martin', 'Quise irme. Falk me dijo que si me iba, mataba a toda la familia. Desaparecí para protegerte.', "I wanted to leave. Falk said that if I left, he'd kill the whole family. I disappeared to protect you."),
  {'tipo': 'efecto', 'nombre': 'golpe'},
  TJ('Viktor lo golpea. Martin no se defiende.', "Viktor hits him. Martin doesn't defend himself.", 3),
  D('martin', 'Tenía miedo de que te mataran por mi culpa.', 'I was afraid they would kill you because of me.', 'triste'),
  D('viktor', 'Y lo lograste igual. Me dejaste solo.', 'And you managed it anyway. You left me alone.', 'enojado'),
  TJ('Se van juntos. Pero Martin sabe que no tiene mucho tiempo: Erich Falk ya sabe dónde está.', 'They leave together. But Martin knows he does not have much time: Erich Falk already knows where he is.', 5),
  F('negro', 2), G('final'), TJ('Fin del Acto IV', 'End of Act IV'), {'tipo': 'fin'}])

for cap in (cap8, cap9, cap10):
    json.dump(cap, open('datos/campana/%s.json' % cap['id'], 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
caps = json.load(open('datos/campana/capitulos.json', encoding='utf-8'))
for c in caps['capitulos']:
    if c['id'] == 'capitulo_8': c['disponible'] = True
for cap in (cap9, cap10):
    if not any(c['id'] == cap['id'] for c in caps['capitulos']): caps['capitulos'].append({'id': cap['id'], 'titulo': cap['titulo'], 'disponible': True})
if not any(c['id'] == 'capitulo_11' for c in caps['capitulos']):
    caps['capitulos'].append({'id': 'capitulo_11', 'titulo': tr('Capítulo 11: La última cena', 'Chapter 11: The Last Supper'), 'disponible': False})
json.dump(caps, open('datos/campana/capitulos.json', 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
p = 'datos/mundo/misiones.json'; d = json.load(open(p, encoding='utf-8'))
for (lid, nom, desc) in (l8, l9, l10):
    if not any(l['id'] == lid for l in d['logros']): d['logros'].append({'id': lid, 'nombre': nom, 'descripcion': desc, 'condicion': {'campana': lid}})
json.dump(d, open(p, 'w', encoding='utf-8'), ensure_ascii=False, indent=2)
for es, en in {'Te vio. Probá de nuevo.': 'He saw you. Try again.', 'Lo perdiste. Probá de nuevo.': 'You lost him. Try again.'}.items(): tr(es, en)
json.dump(EN, open(RUTA_EN, 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
print('capítulos 8, 9 y 10:', len(cap8['pasos']), len(cap9['pasos']), len(cap10['pasos']), 'pasos')
