#!/usr/bin/env python3
"""Escribe los guiones del Acto VI «El Arquitecto» (capítulos 14 y 15) con su inglés y su alemán al lado.
Correr desde la raíz:  python herramientas/escribir_acto_vi.py   (después: python herramientas/traducir_contenido.py)
Adaptación: la partida en la cena de Konrad Weiss (bot 110, base v18) es la distracción mientras Thomas y Anneliese suben a la oficina.
Decisión nueva (Cap. 15): `trato_thomas` (Viktor se entera de que Thomas sospechaba de Lukas desde el Cap. 9); la usa el Cap. 16."""
import json, os
RUTA_EN, RUTA_DE = 'herramientas/traducciones_capitulos.json', 'herramientas/traducciones_de.json'
EN = json.load(open(RUTA_EN, encoding='utf-8')) if os.path.exists(RUTA_EN) else {}
DE = json.load(open(RUTA_DE, encoding='utf-8')) if os.path.exists(RUTA_DE) else {}
def tr(es, en, de):
    EN[es] = en; DE[es] = de; return es
D = lambda quien, es, en, de, emocion='neutral': {'tipo': 'dialogo', 'quien': quien, 'emocion': emocion, 'texto': tr(es, en, de)}
F = lambda a, seg=1: {'tipo': 'fundido', 'a': a, 'segundos': seg}
TJ = lambda es, en, de, seg=3, **k: dict({'tipo': 'tarjeta', 'texto': tr(es, en, de), 'segundos': seg}, **k)
G = lambda punto: {'tipo': 'guardar', 'punto': punto}
SI = lambda clave, valor, pasos: {'tipo': 'si', 'clave': clave, 'valor': valor, 'pasos': pasos}
FR = lambda v: {'tipo': 'franjas', 'visibles': v}
U = lambda lado, celda, vida, mov, punt: {'lado': lado, 'celda': celda, 'vida': vida, 'mov': mov, 'punteria': punt}
INGRID = lambda es, en, de: [{'tipo': 'escena', 'mapa': 'redaccion_1975', 'actores': ['ingrid'], 'jugador': None, 'props': ['caja']},
                             F('escena', 1.5), TJ('Berlín Occidental · 1975', 'West Berlin · 1975', 'West-Berlin · 1975'), D('ingrid', es, en, de, 'triste'), F('negro', 1.5)]
base = json.load(open('datos/campana/capitulo_13.json', encoding='utf-8'))
PERS = dict(base['personajes'], weiss='Konrad Weiss', invitado='Un invitado', guardia='Un guardia de Weiss')
tr('Un invitado', 'A guest', 'Ein Gast'); tr('Un guardia de Weiss', "One of Weiss's guards", 'Ein Wachmann von Weiss')
def capitulo(id_, n, t_es, t_en, t_de, xp, pasos):
    titulo = tr('Capítulo %d: %s' % (n, t_es), 'Chapter %d: %s' % (n, t_en), 'Kapitel %d: %s' % (n, t_de))
    tr(t_es, t_en, t_de)
    desc = tr('Terminá el Capítulo %d de Nacht über Berlin.' % n, 'Finish Chapter %d of Nacht über Berlin.' % n, 'Beende Kapitel %d von Nacht über Berlin.' % n)
    logro = 'nacht_capitulo_%d' % n
    return {'_leeme': base['_leeme'], 'id': id_, 'titulo': titulo, 'duracionMinima': 45, 'aviso': base['aviso'],
            'recompensa': {'xp': xp, 'logro': logro}, 'personajes': PERS, 'pasos': pasos}, (logro, t_es, desc)

# ---------------- Capítulo 14: El hombre que vendía la paz ----------------
partida = {'tipo': 'partida', 'id': 'mesa_weiss', 'bot': 110, 'quien': 'weiss', 'reglas': {'rondas': 1},
  'nombre': tr('La mesa de Konrad Weiss', "Konrad Weiss's table", 'Der Tisch von Konrad Weiss'),
  'texto': tr('Ganale a Weiss en su mesa y mantenelo ocupado', 'Beat Weiss at his table and keep him busy', 'Schlag Weiss an seinem Tisch und halt ihn beschäftigt'),
  'invitacion': tr('Una partida, señor... ¿cómo dijo que se llamaba? Las cenas son largas; las partidas, más.', 'A game, Mr... what did you say your name was? Dinners are long; games are longer.', 'Eine Partie, Herr... wie war noch Ihr Name? Die Abendessen sind lang, die Partien noch länger.'),
  'perdiste': tr('Perdió. En esta casa se juega hasta ganar. Otra.', 'You lost. In this house we play until we win. Another.', 'Verloren. In diesem Haus spielt man, bis man gewinnt. Noch eine.'),
  'enJuego': tr('La partida sigue en la mesa. Weiss espera.', 'The game is still on the table. Weiss is waiting.', 'Die Partie liegt noch auf dem Tisch. Weiss wartet.')}
cap14, l14 = capitulo('capitulo_14', 14, 'El hombre que vendía la paz', 'The Man Who Sold Peace', 'Der Mann, der den Frieden verkaufte', 350, INGRID(
  'La foto catorce: una mansión iluminada, de noche. Autos caros en la entrada.', 'Photo fourteen: a mansion lit up at night. Expensive cars at the entrance.', 'Foto vierzehn: eine erleuchtete Villa bei Nacht. Teure Autos vor dem Eingang.') + [
  TJ('Acto VI · El Arquitecto', 'Act VI · The Architect', 'Sechster Akt · Der Architekt', 3, estilo='documento'),
  {'tipo': 'escena', 'mapa': 'departamento', 'actores': ['anneliese', 'thomas', 'lukas'], 'jugador': 'viktor', 'props': ['fotos']},
  F('escena', 1), TJ('Berlín Occidental · 1962', 'West Berlin · 1962', 'West-Berlin · 1962'), G('plan'),
  {'tipo': 'objetivo', 'texto': tr('Hablá con Anneliese', 'Talk to Anneliese', 'Rede mit Anneliese'), 'zona': 'anneliese', 'radio': 80, 'accion': tr('Hablar con Anneliese', 'Talk to Anneliese', 'Mit Anneliese reden')},
  D('anneliese', 'Encontré a Konrad Weiss. Empresario respetado: propiedades, inversiones, amigos en la política.', 'I found Konrad Weiss. A respected businessman: property, investments, friends in politics.', 'Ich habe Konrad Weiss gefunden. Ein angesehener Geschäftsmann: Immobilien, Beteiligungen, Freunde in der Politik.'),
  D('anneliese', 'Da cenas privadas y financia obras de beneficencia. Nadie sospecharía de él.', 'He gives private dinners and funds charities. Nobody would suspect him.', 'Er gibt private Abendessen und finanziert Wohltätigkeit. Niemand würde ihn verdächtigen.'),
  D('lukas', 'El sábado recibe a varios funcionarios en su casa de las afueras. Ahí están los papeles del Arquitecto.', "On Saturday he's hosting several officials at his house outside the city. That's where the Architect's papers are.", 'Am Samstag empfängt er mehrere Beamte in seinem Haus vor der Stadt. Dort sind die Papiere des Architekten.'),
  D('thomas', 'Weiss juega después de la cena. Siempre. Alguien tiene que sentarse con él y tenerlo ocupado.', 'Weiss plays after dinner. Always. Someone has to sit with him and keep him busy.', 'Weiss spielt nach dem Essen. Immer. Jemand muss sich zu ihm setzen und ihn beschäftigen.'),
  D('viktor', 'Yo juego. Ustedes suben.', "I'll play. You go upstairs.", 'enojado'),
  F('negro', 1),
  {'tipo': 'escena', 'mapa': 'mansion', 'actores': ['weiss', 'invitado_1', 'invitado_2', 'invitado_3', 'guardia_1'], 'jugador': 'viktor', 'props': ['fotos', 'carta']},
  F('escena', 1), TJ('Una mansión en las afueras · Después de la cena', 'A mansion outside the city · After dinner', 'Eine Villa vor der Stadt · Nach dem Abendessen'), G('cena'),
  D('weiss', 'Usted no es de los habituales. Me gustan las caras nuevas: todavía no aprendieron a mentir conmigo.', "You're not one of the regulars. I like new faces: they haven't learned to lie to me yet.", 'Sie gehören nicht zu den Stammgästen. Ich mag neue Gesichter: Sie haben noch nicht gelernt, mich anzulügen.'),
  {'tipo': 'objetivo', 'texto': tr('Sentate a la mesa de Weiss', "Sit down at Weiss's table", 'Setz dich an Weiss’ Tisch'), 'zona': 'weiss', 'radio': 90, 'accion': tr('Sentarse a jugar', 'Sit down and play', 'Hinsetzen und spielen')},
  partida,
  G('partida'),
  D('weiss', 'Bien jugado. Disculpe: una llamada. Siga disfrutando de la casa.', 'Well played. Excuse me: a phone call. Do enjoy the house.', 'Gut gespielt. Entschuldigen Sie: ein Anruf. Genießen Sie weiter das Haus.'),
  {'tipo': 'mover', 'actor': 'weiss', 'a': 'salida', 'segundos': 2.5},
  {'tipo': 'desaparecer', 'actores': ['weiss']},
  {'tipo': 'sigilo', 'guardias': ['guardia_1'], 'meta': 'oficina', 'radio': 45, 'texto': tr('Subí a la oficina sin que te vea el guardia', 'Get up to the office without the guard seeing you', 'Geh ins Büro, ohne dass dich die Wache sieht')},
  {'tipo': 'aparecer', 'actores': ['anneliese', 'thomas', 'lukas']},
  G('oficina'),
  D('thomas', 'Abrimos la caja fuerte. Hay más de lo que esperábamos.', "We opened the safe. There's more than we expected."  , 'Wir haben den Tresor geöffnet. Da ist mehr, als wir erwartet haben.'),
  {'tipo': 'interactuar', 'objeto': 'fotos', 'texto': tr('Mirá lo que hay en la pared', "Look at what's on the wall", 'Sieh dir an, was an der Wand hängt'), 'accion': tr('Mirar las fotos', 'Look at the photos', 'Die Fotos ansehen'), 'quedarse': True},
  TJ('Una foto de Anneliese de chica, de la mano de su padre.', 'A photo of Anneliese as a girl, holding her father’s hand.', 'Ein Foto von Anneliese als Mädchen, an der Hand ihres Vaters.', 3, estilo='documento'),
  {'tipo': 'interactuar', 'objeto': 'carta', 'texto': tr('Leé la carta del escritorio', 'Read the letter on the desk', 'Lies den Brief auf dem Schreibtisch'), 'accion': tr('Leer la carta', 'Read the letter', 'Den Brief lesen'), 'quedarse': True},
  FR(True),
  TJ('La carta está dirigida a Anneliese. Su padre había descubierto la red. Su detención se negoció con funcionarios occidentales.', 'The letter is addressed to Anneliese. Her father had uncovered the network. His arrest was negotiated with Western officials.', 'Der Brief ist an Anneliese gerichtet. Ihr Vater hatte das Netz entdeckt. Seine Verhaftung wurde mit westlichen Beamten ausgehandelt.', 5, estilo='documento'),
  TJ('Su desaparición no fue un accidente. Fue una operación conjunta.', 'His disappearance was no accident. It was a joint operation.', 'Sein Verschwinden war kein Zufall. Es war eine gemeinsame Operation.', 4, estilo='documento'),
  D('anneliese', 'Toda mi vida pensé que se lo había llevado el Este. Fueron los dos.', 'All my life I thought the East had taken him. It was both of them.', 'Mein ganzes Leben dachte ich, der Osten hätte ihn geholt. Es waren beide.', 'triste'),
  {'tipo': 'efecto', 'nombre': 'puerta'},
  {'tipo': 'aparecer', 'actores': ['weiss'], 'en': 'weiss_entra'},
  TJ('Konrad Weiss no parece sorprendido. Parece que los estaba esperando.', 'Konrad Weiss does not look surprised. He looks as if he was expecting them.', 'Konrad Weiss wirkt nicht überrascht. Er scheint sie erwartet zu haben.', 3),
  D('weiss', 'Tardaron bastante.', 'You took your time.', 'Sie haben sich Zeit gelassen.', 'contento'),
  D('weiss', '¿De verdad creen que pueden destruir una organización que lleva veinte años construyéndose?', 'Do you really think you can destroy an organisation that has been twenty years in the making?', 'Glauben Sie wirklich, Sie könnten eine Organisation zerstören, an der seit zwanzig Jahren gebaut wird?'),
  D('viktor', '¿Quién es el Arquitecto?', 'Who is the Architect?', 'Wer ist der Architekt?', 'enojado'),
  D('weiss', 'Yo no soy el Arquitecto.', 'I am not the Architect.', 'Ich bin nicht der Architekt.'),
  D('weiss', 'Lo conocen mucho mejor de lo que creen.', 'You know him far better than you think.', 'Sie kennen ihn viel besser, als Sie glauben.', 'contento'),
  TJ('Thomas entiende algo. Mira a Lukas. Lukas no se mueve.', 'Thomas understands something. He looks at Lukas. Lukas does not move.', 'Thomas begreift etwas. Er sieht Lukas an. Lukas rührt sich nicht.', 3),
  D('thomas', 'No puede ser...', "It can't be...", 'Das kann nicht sein...', 'triste'),
  TJ('Lukas saca una pistola. Apunta a Viktor.', 'Lukas draws a pistol. He aims at Viktor.', 'Lukas zieht eine Pistole. Er zielt auf Viktor.', 3, estilo='documento'),
  F('negro', 2), G('final'), TJ('Fin del Capítulo 14', 'End of Chapter 14', 'Ende von Kapitel 14'), {'tipo': 'fin'}])

# ---------------- Capítulo 15: El verdadero traidor ----------------
cap15, l15 = capitulo('capitulo_15', 15, 'El verdadero traidor', 'The Real Traitor', 'Der wahre Verräter', 400, INGRID(
  'La foto quince es un recorte de diario: «Muere el empresario Konrad Weiss en su casa de las afueras». La policía no detuvo a nadie.', 'Photo fifteen is a newspaper clipping: “Businessman Konrad Weiss dies at his house outside the city.” The police arrested no one.', 'Foto fünfzehn ist ein Zeitungsausschnitt: „Unternehmer Konrad Weiss stirbt in seinem Haus vor der Stadt.“ Die Polizei verhaftete niemanden.') + [
  {'tipo': 'escena', 'mapa': 'mansion', 'actores': ['weiss', 'anneliese', 'thomas', 'lukas', 'guardia_1', 'guardia_2', 'guardia_3'], 'jugador': 'viktor', 'props': ['fotos', 'carta']},
  {'tipo': 'mover', 'actor': 'weiss', 'a': 'weiss_rincon', 'segundos': 0.2},
  F('escena', 1), G('verdad'),
  {'tipo': 'objetivo', 'texto': tr('Encará a Lukas', 'Face Lukas', 'Stell dich Lukas'), 'zona': 'lukas', 'radio': 80, 'accion': tr('Hablar con Lukas', 'Talk to Lukas', 'Mit Lukas reden')},
  FR(True),
  D('lukas', 'Nunca me escapé del Este, Viktor. Mi llegada estaba organizada.', 'I never escaped from the East, Viktor. My arrival was arranged.', 'Ich bin nie aus dem Osten geflohen, Viktor. Meine Ankunft war arrangiert.'),
  D('lukas', 'Tenía que encontrar los documentos, encontrar a Martin y recuperar la grabación. Trabajaba para Erich Falk.', 'I had to find the documents, find Martin and get the recording back. I worked for Erich Falk.', 'Ich sollte die Dokumente finden, Martin finden und die Aufnahme zurückholen. Ich habe für Erich Falk gearbeitet.'),
  D('lukas', 'Mi padre fue uno de los que crearon Projekt Morgen. Lo mataron cuando la red decidió que sabía demasiado.', 'My father was one of the men who created Projekt Morgen. They killed him when the network decided he knew too much.', 'Mein Vater gehörte zu denen, die Projekt Morgen geschaffen haben. Sie haben ihn umgebracht, als das Netz beschloss, dass er zu viel wusste.'),
  D('lukas', 'No quiero destruir la red. Quiero recuperarla.', "I don't want to destroy the network. I want to take it back.", 'Ich will das Netz nicht zerstören. Ich will es zurückholen.'),
  D('viktor', '¿Y por qué nos ayudaste?', 'Then why did you help us?', 'Und warum hast du uns geholfen?', 'enojado'),
  D('lukas', 'Porque necesitaba que encontraras a tu hermano.', 'Because I needed you to find your brother.', 'Weil ich brauchte, dass du deinen Bruder findest.'),
  D('viktor', '¿Para qué?', 'What for?', 'Wofür?', 'enojado'),
  D('lukas', 'Porque Martin era el único que sabía dónde está el archivo original.', 'Because Martin was the only one who knew where the original archive is.', 'Weil Martin der Einzige war, der wusste, wo das Originalarchiv ist.'),
  D('lukas', 'Está escondido en una instalación militar abandonada. Y la grabación de tu padre tiene la pista para encontrarlo.', "It's hidden in an abandoned military installation. And your father's recording holds the clue to finding it.", 'Es ist in einer verlassenen Militäranlage versteckt. Und die Aufnahme deines Vaters enthält den Hinweis, wie man es findet.'),
  TJ('Viktor levanta el arma. Lukas es más rápido: sale por el pasillo y los hombres de Weiss entran disparando.', "Viktor raises his gun. Lukas is faster: he slips out into the corridor and Weiss's men come in shooting.", 'Viktor hebt die Waffe. Lukas ist schneller: Er verschwindet in den Flur, und Weiss’ Männer stürmen schießend herein.', 4),
  {'tipo': 'mover', 'actor': 'lukas', 'a': 'lukas_huye', 'segundos': 1.5, 'esperar': False},
  FR(False),
  G('tiroteo'),
  {'tipo': 'combate', 'id': 'mansion', 'mapa': 'mansion', 'lider': 'viktor', 'objetivo': {'tipo': 'eliminar', 'rondas': 10},
   'texto': tr('Los hombres de Weiss entran a la oficina: derribalos antes de que lleguen más (10 rondas)', "Weiss's men burst into the office: take them down before more arrive (10 rounds)", 'Weiss’ Männer stürmen ins Büro: Schalte sie aus, bevor mehr kommen (10 Runden)'),
   'unidades': {'viktor': U('aliado', [18, 6], 4, 4, 80), 'anneliese': U('aliado', [20, 7], 3, 4, 60), 'thomas': U('aliado', [21, 6], 3, 4, 60),
                'guardia_1': U('enemigo', [14, 3], 3, 4, 55), 'guardia_2': U('enemigo', [14, 7], 3, 4, 55), 'guardia_3': U('enemigo', [13, 11], 3, 4, 50)}},
  {'tipo': 'desaparecer', 'actores': ['lukas']},
  FR(True),
  {'tipo': 'tumbar', 'actor': 'weiss'},
  TJ('Konrad Weiss quedó en el medio del tiroteo. No se levanta.', 'Konrad Weiss was caught in the crossfire. He does not get up.', 'Konrad Weiss geriet ins Kreuzfeuer. Er steht nicht mehr auf.', 3, estilo='documento'),
  D('thomas', '¡Suben más por la escalera!', "More of them are coming up the stairs!", 'Da kommen noch mehr die Treppe hoch!', 'enojado'),
  {'tipo': 'aparecer', 'actores': ['guardia_4', 'guardia_5']},
  FR(False), G('huida'),
  {'tipo': 'persecucion', 'perseguidores': ['guardia_4', 'guardia_5'], 'meta': 'salida', 'radio': 50, 'retraso': 1.3, 'velocidad': 0.85,
   'texto': tr('Salí de la mansión', 'Get out of the mansion', 'Raus aus der Villa')},
  F('negro', 1),
  {'tipo': 'escena', 'mapa': 'calle', 'actores': ['anneliese', 'thomas'], 'jugador': 'viktor'},
  F('escena', 1), G('calle'),
  D('anneliese', 'Lukas. Todo este tiempo.', 'Lukas. All this time.', 'Lukas. Die ganze Zeit.', 'triste'),
  D('thomas', 'Viktor... yo lo sabía. Lo seguí una noche, hace meses. Lo oí hablar con un hombre de Falk.', 'Viktor... I knew. I followed him one night, months ago. I heard him talking to one of Falk’s men.', 'Viktor... ich wusste es. Ich bin ihm vor Monaten eines Nachts gefolgt. Ich habe gehört, wie er mit einem von Falks Männern geredet hat.', 'triste'),
  D('thomas', 'No te dije nada. Tenía miedo de lo que ibas a hacer.', "I didn't tell you anything. I was afraid of what you would do.", 'Ich habe dir nichts gesagt. Ich hatte Angst davor, was du tun würdest.', 'triste'),
  {'tipo': 'elegir', 'clave': 'trato_thomas', 'pregunta': tr('¿Qué le dice Viktor?', 'What does Viktor say to him?', 'Was sagt Viktor zu ihm?'), 'opciones': [
    {'valor': 'entender', 'texto': tr('Tenías miedo de mí. Lo entiendo. Yo también me tendría miedo.', 'You were afraid of me. I understand. I would be afraid of me too.', 'Du hattest Angst vor mir. Ich verstehe das. Ich hätte auch Angst vor mir.')},
    {'valor': 'culpar', 'texto': tr('Por tu silencio casi nos matan a todos.', 'Your silence nearly got us all killed.', 'Dein Schweigen hätte uns fast alle umgebracht.')}]},
  SI('trato_thomas', 'entender', [D('thomas', 'Gracias, Viktor.', 'Thanks, Viktor.', 'Danke, Viktor.')]),
  SI('trato_thomas', 'culpar', [D('thomas', '...', '...', '...', 'triste'), TJ('Thomas no contesta. Esa noche no vuelve a hablar.', 'Thomas does not answer. He does not speak again that night.', 'Thomas antwortet nicht. In dieser Nacht spricht er kein Wort mehr.', 3)]),
  FR(True),
  TJ('Ahora los persiguen Erich Falk y Lukas Brandt. Y el archivo original guarda la verdad definitiva.', 'Now Erich Falk and Lukas Brandt are hunting them. And the original archive holds the final truth.', 'Jetzt sind Erich Falk und Lukas Brandt hinter ihnen her. Und das Originalarchiv birgt die endgültige Wahrheit.', 5),
  F('negro', 2), G('final'), TJ('Fin del Acto VI', 'End of Act VI', 'Ende des sechsten Akts'), {'tipo': 'fin'}])

for cap in (cap14, cap15):
    json.dump(cap, open('datos/campana/%s.json' % cap['id'], 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
caps = json.load(open('datos/campana/capitulos.json', encoding='utf-8'))
for c in caps['capitulos']:
    if c['id'] == 'capitulo_14': c['disponible'] = True
if not any(c['id'] == 'capitulo_15' for c in caps['capitulos']): caps['capitulos'].append({'id': 'capitulo_15', 'titulo': cap15['titulo'], 'disponible': True})
if not any(c['id'] == 'capitulo_16' for c in caps['capitulos']):
    caps['capitulos'].append({'id': 'capitulo_16', 'titulo': tr('Capítulo 16: La ciudad arde', 'Chapter 16: The City Burns', 'Kapitel 16: Die Stadt brennt'), 'disponible': False})
json.dump(caps, open('datos/campana/capitulos.json', 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
p = 'datos/mundo/misiones.json'; d = json.load(open(p, encoding='utf-8'))
for (lid, nom, desc) in (l14, l15):
    if not any(l['id'] == lid for l in d['logros']): d['logros'].append({'id': lid, 'nombre': nom, 'descripcion': desc, 'condicion': {'campana': lid}})
json.dump(d, open(p, 'w', encoding='utf-8'), ensure_ascii=False, indent=2)
json.dump(EN, open(RUTA_EN, 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
json.dump(DE, open(RUTA_DE, 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
print('capítulos 14 y 15:', len(cap14['pasos']), len(cap15['pasos']), 'pasos')
