import json
EN = {}
def tr(es, en):
    if es in EN and EN[es] != en and es != '…': raise SystemExit('traducción distinta para: ' + es)
    EN[es] = en; return es
D = lambda quien, es, en, emocion='neutral': {'tipo': 'dialogo', 'quien': quien, 'emocion': emocion, 'texto': tr(es, en)}
F = lambda a, seg=1: {'tipo': 'fundido', 'a': a, 'segundos': seg}
TJ = lambda es, en, seg=3, **k: dict({'tipo': 'tarjeta', 'texto': tr(es, en), 'segundos': seg}, **k)
G = lambda punto: {'tipo': 'guardar', 'punto': punto}
O = lambda es, en, **k: dict({'texto': tr(es, en)}, **k)
base = json.load(open('datos/campana/capitulo_3.json', encoding='utf-8'))
PERS = dict(base['personajes'], guardia='Guardia', vuniforme='Viktor Adler', tuniforme='Thomas')
LEEME = base['_leeme'] + " Un sigilo con 'registra' anota si te descubrieron (si/no) y la historia sigue por la rama que corresponde (con su combate)."

# ---------------- Capítulo 4 ----------------
inter = {'tipo': 'interrogatorio', 'id': 'falk_keller', 'quien': 'falk', 'limite': 60, 'sinRespuesta': 15, 'segundos': 8,
 'texto': tr('No le des nada a Falk', "Don't give Falk anything"),
 'detenido': tr('¿Ve, Keller? Al final, todos hablan.', 'You see, Keller? In the end, everyone talks.'),
 'pase': tr('Muy bien. Tenemos toda la noche.', 'Very well. We have all night.'),
 'medidor': tr('Lo que Falk averigua: {s} de {l}', 'What Falk finds out: {s} of {l}'),
 'falla': tr('Friedrich habló de más. Probá de nuevo.', 'Friedrich said too much. Try again.'),
 'preguntas': [
  {'texto': tr('Usted tiene algo que me pertenece, Herr Keller.', 'You have something that belongs to me, Herr Keller.'), 'opciones': [
    O('No sé de qué me habla.', "I don't know what you're talking about.", sospecha=5), O('Si es suyo, ¿por qué lo tenían otros?', 'If it is yours, why did others have it?', sospecha=15),
    O('Está en un lugar seguro.', "It's somewhere safe.", sospecha=35)]},
  {'texto': tr('Le ofrezco un trato. Los documentos, y usted sale caminando por esa puerta.', 'I offer you a deal. The documents, and you walk out through that door.'), 'opciones': [
    O('No hay trato.', 'There is no deal.', sospecha=5), O('¿Y mis amigos?', 'And my friends?', sospecha=25), O('Déjeme pensarlo.', 'Let me think about it.', sospecha=20)]},
  {'texto': tr('¿La reconoce? Margarete. Vive en Weißensee, en un tercer piso.', 'Do you recognise her? Margarete. She lives in Weißensee, on a third floor.'), 'opciones': [
    O('No decir nada.', 'Say nothing.', sospecha=5), O('¡Ella no tiene nada que ver!', 'She has nothing to do with this!', sospecha=25), O('Hace años que no la veo.', "I haven't seen her in years.", sospecha=10)]},
  {'texto': tr('Puedo hacer que su vida sea muy difícil. O muy corta.', 'I can make your life very difficult. Or very short.'), 'opciones': [
    O('Hágalo.', 'Do it.', sospecha=5), O('¿Qué quiere saber?', 'What do you want to know?', sospecha=35), O('Viktor no va a dejar que...', "Viktor won't let...", sospecha=30)]},
  {'texto': tr('¿Dónde están los papeles, Keller?', 'Where are the papers, Keller?'), 'opciones': [
    O('Los quemé.', 'I burned them.', sospecha=10), O('Se los di a los americanos.', 'I gave them to the Americans.', sospecha=20), O('No contestar y mirar la pared.', 'Say nothing and stare at the wall.', sospecha=5)]},
 ]}
def combate(id_, texto_es, texto_en, unidades):
    return {'tipo': 'combate', 'id': id_, 'mapa': 'edificio', 'texto': tr(texto_es, texto_en), 'lider': 'vuniforme', 'objetivo': {'tipo': 'resistir', 'rondas': 5}, 'unidades': unidades}
U = lambda lado, celda, vida, mov, punt: {'lado': lado, 'celda': celda, 'vida': vida, 'mov': mov, 'punteria': punt}
cap4 = {'_leeme': LEEME, 'id': 'capitulo_4', 'titulo': tr('Capítulo 4: Un hombre en una habitación', 'Chapter 4: A Man in a Room'), 'duracionMinima': 45, 'aviso': base['aviso'],
 'recompensa': {'xp': 300, 'logro': 'nacht_capitulo_4'}, 'personajes': PERS, 'pasos': [
  {'tipo': 'escena', 'mapa': 'redaccion_1975', 'actores': ['ingrid'], 'jugador': None, 'props': ['caja']},
  F('escena', 1.5), TJ('Berlín Occidental · 1975', 'West Berlin · 1975'),
  D('ingrid', 'La quinta foto es de Friedrich. Tiene un ojo morado y mira a la cámara como si la odiara.', 'The fifth photo is of Friedrich. He has a black eye and stares at the camera as if he hated it.', 'triste'),
  F('negro', 1.5),
  TJ('Friedrich Keller', 'Friedrich Keller', 2, estilo='documento'),
  TJ('Esta parte se juega como Friedrich: Viktor no puede estar en esa habitación.', "This part is played as Friedrich: Viktor can't be in that room.", 4),
  {'tipo': 'escena', 'mapa': 'sala_interrogatorio', 'actores': ['falk', 'guardia'], 'jugador': 'friedrich'},
  F('escena', 1), TJ('Berlín Oriental · Agosto de 1961', 'East Berlin · August 1961'), G('sala'),
  {'tipo': 'franjas', 'visibles': True},
  D('falk', 'Herr Keller. Siéntese. Tenemos tiempo. Usted, no tanto.', 'Herr Keller. Sit down. We have time. You, not so much.'),
  inter,
  TJ('Varias horas después', 'Several hours later', 2),
  D('falk', 'Usted no es un hombre leal, Keller. Es un hombre que necesita creer que lo es.', 'You are not a loyal man, Keller. You are a man who needs to believe he is.', 'enojado'),
  {'tipo': 'mover', 'actor': 'falk', 'a': 'puerta', 'segundos': 2.5},
  D('friedrich', '…', '…', 'triste'),
  F('negro', 1.5), {'tipo': 'franjas', 'visibles': False},
  TJ('Viktor', 'Viktor', 2, estilo='documento'),
  TJ('La historia vuelve a Viktor. Esa misma noche, el rescate.', 'The story returns to Viktor. That same night, the rescue.', 4),
  {'tipo': 'escena', 'mapa': 'edificio', 'actores': ['tuniforme', 'friedrich', 'guardia_1', 'guardia_2', 'guardia_3'], 'jugador': 'vuniforme'},
  F('escena', 1), G('entrada'),
  D('tuniforme', 'Uniformes robados y papeles de traslado. Si alguien nos mira de cerca, se acabó.', "Stolen uniforms and transfer papers. If anyone looks at us closely, it's over."),
  D('vuniforme', 'Entonces que nadie nos mire de cerca.', "Then let's make sure nobody does."),
  {'tipo': 'sigilo', 'guardias': ['guardia_1', 'guardia_2'], 'meta': 'celda', 'radio': 45, 'registra': 'descubierto_rescate',
   'texto': tr('Llegá a la celda de Friedrich sin que te descubran', "Reach Friedrich's cell without being discovered")},
  G('tiroteo'),
  {'tipo': 'si', 'clave': 'descubierto_rescate', 'valor': 'si', 'pasos': [
    {'tipo': 'franjas', 'visibles': True},
    D('guardia_1', '¡Esos uniformes no son de acá! ¡Alarma!', "Those uniforms aren't from here! Alarm!", 'enojado'),
    {'tipo': 'franjas', 'visibles': False},
    combate('rescate_adentro', 'Te descubrieron: derribá a los guardias o resistí 5 rondas', 'You were discovered: take down the guards or hold out for 5 rounds', {
      'vuniforme': U('aliado', [3, 7], 4, 4, 75), 'tuniforme': U('aliado', [3, 9], 3, 4, 65),
      'guardia_1': U('enemigo', [12, 6], 3, 4, 50), 'guardia_2': U('enemigo', [16, 8], 3, 4, 45), 'guardia_3': U('enemigo', [20, 8], 3, 3, 45)}),
    {'tipo': 'objetivo', 'texto': tr('Sacá a Friedrich de la celda', 'Get Friedrich out of the cell'), 'zona': 'celda', 'radio': 70, 'accion': tr('Abrir la celda', 'Open the cell')},
    D('friedrich', '¡Viktor! Pensé que no venían.', "Viktor! I thought you weren't coming."),
    D('vuniforme', 'Callate y caminá.', 'Shut up and walk.')]},
  {'tipo': 'si', 'clave': 'descubierto_rescate', 'valor': 'no', 'pasos': [
    {'tipo': 'objetivo', 'texto': tr('Abrí la celda de Friedrich', "Open Friedrich's cell"), 'zona': 'celda', 'radio': 70, 'accion': 'Abrir la celda'},
    D('friedrich', 'Viktor... ¿Qué hacés vestido así?', 'Viktor... What are you doing dressed like that?'),
    D('vuniforme', 'Te sacamos. Caminá normal.', "We're getting you out. Walk normally."),
    {'tipo': 'franjas', 'visibles': True},
    D('guardia_1', '¡Alto! ¿Adónde llevan al prisionero?', 'Halt! Where are you taking the prisoner?', 'enojado'),
    {'tipo': 'franjas', 'visibles': False},
    combate('rescate_salida', 'Abrite paso hasta la salida: derribá a los guardias o resistí 5 rondas', 'Fight your way to the exit: take down the guards or hold out for 5 rounds', {
      'vuniforme': U('aliado', [21, 8], 4, 4, 75), 'tuniforme': U('aliado', [20, 9], 3, 4, 65), 'friedrich': U('aliado', [22, 7], 3, 3, 60),
      'guardia_1': U('enemigo', [6, 7], 3, 4, 50), 'guardia_2': U('enemigo', [9, 8], 3, 4, 45)})]},
  G('foto'),
  {'tipo': 'interactuar', 'objeto': 'guardia_1', 'texto': tr('Revisá al guardia caído', 'Search the fallen guard'), 'accion': tr('Revisar al guardia', 'Search the guard'), 'quedarse': True},
  {'tipo': 'franjas', 'visibles': True},
  TJ('Una foto de Margarete. La misma que Falk puso sobre la mesa.', 'A photo of Margarete. The same one Falk put on the table.', 4, estilo='documento'),
  D('friedrich', 'No estaba mintiendo.', "He wasn't lying.", 'triste'),
  {'tipo': 'franjas', 'visibles': False},
  {'tipo': 'objetivo', 'texto': tr('Salí del edificio', 'Get out of the building'), 'zona': 'salida', 'radio': 60},
  F('negro', 2), G('final'), TJ('Fin del Capítulo 4', 'End of Chapter 4'), {'tipo': 'fin'}]}

# ---------------- Capítulo 5 ----------------
cap5 = {'_leeme': LEEME, 'id': 'capitulo_5', 'titulo': tr('Capítulo 5: La traición de Anneliese', "Chapter 5: Anneliese's Betrayal"), 'duracionMinima': 45, 'aviso': base['aviso'],
 'recompensa': {'xp': 250, 'logro': 'nacht_capitulo_5'}, 'personajes': PERS, 'pasos': [
  {'tipo': 'escena', 'mapa': 'redaccion_1975', 'actores': ['ingrid'], 'jugador': None, 'props': ['caja']},
  F('escena', 1.5), TJ('Berlín Occidental · 1975', 'West Berlin · 1975'),
  D('ingrid', 'La sexta foto: una casa con las ventanas tapiadas. Atrás, un nombre subrayado dos veces: «Schwarz».', 'The sixth photo: a house with boarded-up windows. On the back, a name underlined twice: “Schwarz”.', 'triste'),
  F('negro', 1.5),
  {'tipo': 'escena', 'mapa': 'casa_abandonada', 'actores': ['friedrich', 'thomas', 'anneliese'], 'jugador': 'viktor', 'props': ['doc_1', 'doc_2', 'doc_3']},
  F('escena', 1), TJ('Berlín Occidental · Agosto de 1961', 'West Berlin · August 1961'), G('casa'),
  {'tipo': 'si', 'clave': 'esperar_a_friedrich', 'valor': 'esperar', 'pasos': [D('friedrich', 'Me esperaste en esa casa. No me lo voy a olvidar.', "You waited for me in that house. I won't forget it.")]},
  {'tipo': 'si', 'clave': 'esperar_a_friedrich', 'valor': 'ventana', 'pasos': [D('friedrich', 'Saltaste por la ventana y me dejaste. Ya sé, ya sé: era lo correcto.', 'You jumped out the window and left me. I know, I know: it was the right thing.', 'enojado')]},
  D('thomas', 'Acá no nos va a buscar nadie. Ni las ratas.', "Nobody will look for us here. Not even the rats."),
  {'tipo': 'interactuar', 'objeto': 'doc_1', 'texto': tr('Revisá los documentos de la maleta', 'Go through the documents from the suitcase'), 'accion': tr('Mirar el documento', 'Look at the document'), 'quedarse': True},
  D('viktor', 'Una cruz chiquita en lápiz, en la esquina.', 'A tiny pencil cross, in the corner.'),
  {'tipo': 'interactuar', 'objeto': 'doc_2', 'texto': tr('Revisá el segundo documento', 'Check the second document'), 'accion': 'Mirar el documento', 'quedarse': True},
  D('viktor', 'La misma marca. Y el sello está estampado encima: la cruz estaba antes.', 'The same mark. And the stamp is on top of it: the cross was there first.'),
  {'tipo': 'interactuar', 'objeto': 'doc_3', 'texto': tr('Revisá el tercero', 'Check the third'), 'accion': 'Mirar el documento', 'quedarse': True},
  D('viktor', 'Todos marcados antes de que los robáramos.', 'All of them marked before we stole them.'),
  D('viktor', 'Alguien sabía que los íbamos a encontrar.', 'Someone knew we were going to find them.', 'enojado'),
  {'tipo': 'objetivo', 'texto': tr('Hablá con Anneliese', 'Talk to Anneliese'), 'zona': 'anneliese', 'radio': 80, 'accion': tr('Hablar con Anneliese', 'Talk to Anneliese')},
  {'tipo': 'elegir', 'clave': 'tono_anneliese', 'pregunta': tr('¿Cómo la encara Viktor?', 'How does Viktor confront her?'), 'opciones': [
    {'valor': 'calma', 'texto': tr('Decime la verdad. Despacio.', 'Tell me the truth. Slowly.')}, {'valor': 'furia', 'texto': tr('¿Para quién trabajás?', 'Who do you work for?')}]},
  {'tipo': 'si', 'clave': 'tono_anneliese', 'valor': 'calma', 'pasos': [D('anneliese', 'Está bien. Te lo debo.', 'All right. I owe you that.', 'triste')]},
  {'tipo': 'si', 'clave': 'tono_anneliese', 'valor': 'furia', 'pasos': [D('anneliese', 'No me grites. No sabés nada.', "Don't shout at me. You don't know anything.", 'enojado'), D('viktor', 'Entonces contame.', 'Then tell me.', 'enojado')]},
  D('anneliese', 'Antes de conocerlos trabajaba para un hombre. Se hacía llamar Herr Schwarz.', 'Before I met you, I worked for a man. He called himself Herr Schwarz.', 'triste'),
  D('anneliese', 'Me prometió ayudarme a saber qué le pasó a mi padre.', 'He promised to help me find out what happened to my father.'),
  D('anneliese', 'A cambio, yo le pasaba datos de gente que cruzaba entre los sectores.', 'In return, I gave him information about people who crossed between the sectors.', 'triste'),
  D('viktor', '¿Y qué les pasaba a esos?', 'And what happened to them?', 'enojado'),
  D('anneliese', 'No sabía que los mataban. Te lo juro, Viktor. No lo sabía.', "I didn't know they were being killed. I swear, Viktor. I didn't know.", 'triste'),
  {'tipo': 'si', 'clave': 'trato_anneliese', 'valor': 'regatear', 'pasos': [D('anneliese', 'Y vos me regateaste desde el primer día. Nadie es inocente acá.', "And you haggled with me from day one. Nobody here is innocent.", 'enojado')]},
  D('friedrich', 'Que se vaya. Hoy.', 'She goes. Today.', 'enojado'),
  D('thomas', 'Nos salvó más de una vez. Sin ella no estaríamos vivos.', "She saved us more than once. Without her we wouldn't be alive."),
  {'tipo': 'elegir', 'clave': 'perdon_anneliese', 'pregunta': tr('¿Qué decide Viktor?', 'What does Viktor decide?'), 'opciones': [
    {'valor': 'perdonar', 'texto': tr('Te quedás. Pero nos das todos tus contactos. Todos.', 'You stay. But you give us all your contacts. All of them.')},
    {'valor': 'no_perdonar', 'texto': tr('Te quedás porque te necesitamos. Nada más.', 'You stay because we need you. Nothing more.')}]},
  {'tipo': 'si', 'clave': 'perdon_anneliese', 'valor': 'perdonar', 'pasos': [
    D('anneliese', 'Hay una mujer, Lotte. Siempre cerca del Reichstag. Ella me pasaba los mensajes de Schwarz.', 'There is a woman, Lotte. Always near the Reichstag. She passed me the messages from Schwarz.'),
    D('friedrich', 'Esto no se termina acá.', "This doesn't end here.", 'enojado')]},
  {'tipo': 'si', 'clave': 'perdon_anneliese', 'valor': 'no_perdonar', 'pasos': [
    D('anneliese', 'Entiendo.', 'I understand.', 'triste'), D('thomas', 'Esto nos va a costar caro.', 'This is going to cost us.')]},
  {'tipo': 'franjas', 'visibles': True},
  TJ('Esa noche nadie durmió. La confianza del grupo se había roto.', "Nobody slept that night. The group's trust was broken.", 4),
  F('negro', 2), G('final'), TJ('Fin del Capítulo 5', 'End of Chapter 5'), {'tipo': 'fin'}]}

json.dump(cap4, open('datos/campana/capitulo_4.json', 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
json.dump(cap5, open('datos/campana/capitulo_5.json', 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
caps = json.load(open('datos/campana/capitulos.json', encoding='utf-8'))
for c in caps['capitulos']:
    if c['id'] == 'capitulo_4': c['disponible'] = True
if not any(c['id'] == 'capitulo_5' for c in caps['capitulos']):
    caps['capitulos'].append({'id': 'capitulo_5', 'titulo': cap5['titulo'], 'disponible': True})
if not any(c['id'] == 'capitulo_6' for c in caps['capitulos']):
    caps['capitulos'].append({'id': 'capitulo_6', 'titulo': tr('Capítulo 6: El tren de las tres', "Chapter 6: The Three O'Clock Train"), 'disponible': False})
json.dump(caps, open('datos/campana/capitulos.json', 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
p='datos/mundo/misiones.json'; d=json.load(open(p,encoding='utf-8'))
for lid, nom, desc, nen, den in [('nacht_capitulo_4', 'Un hombre en una habitación', 'Terminá el Capítulo 4 de Nacht über Berlin.', 'A Man in a Room', 'Finish Chapter 4 of Nacht über Berlin.'),
                                 ('nacht_capitulo_5', 'La traición de Anneliese', 'Terminá el Capítulo 5 de Nacht über Berlin.', "Anneliese's Betrayal", 'Finish Chapter 5 of Nacht über Berlin.')]:
    if not any(l['id'] == lid for l in d['logros']):
        d['logros'].append({'id': lid, 'nombre': nom, 'descripcion': desc, 'condicion': {'campana': lid}})
    tr(nom, nen); tr(desc, den)
json.dump(d,open(p,'w',encoding='utf-8'),ensure_ascii=False,indent=2)
for es, en in {'Guardia': 'Guard', 'Revisar al guardia': 'Search the guard', 'Abrir la celda': 'Open the cell'}.items(): tr(es, en)
json.dump(EN, open('herramientas/traducciones_capitulos.json', 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
print('capítulo 4:', len(cap4['pasos']), 'pasos | capítulo 5:', len(cap5['pasos']), 'pasos | traducciones:', len(EN))
