#!/usr/bin/env python3
"""Escribe los guiones del Acto VII «La caída de los hombres» (capítulos 16 y 17) con su inglés y su alemán al lado.
Correr desde la raíz:  python herramientas/escribir_acto_vii.py   (después: python herramientas/traducir_contenido.py)
Adaptación: Thomas se va solo si en el Cap. 15 Viktor lo culpó (`trato_thomas`); si lo entendió, se queda hasta el final.
Samuel Hartmann es el hermano de Gertrud Hartmann (el contacto del Berlín de 1975), que «se fue al Oeste en el 59».
Decisiones nuevas: `plan_falk` (escuchar a Anneliese: sigilo / a mi manera: combate) y `destino_falk` (disparar / usarlo)."""
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
INGRID = lambda pasos: [{'tipo': 'escena', 'mapa': 'redaccion_1975', 'actores': ['ingrid'], 'jugador': None, 'props': ['caja']},
                        F('escena', 1.5), TJ('Berlín Occidental · 1975', 'West Berlin · 1975', 'West-Berlin · 1975')] + pasos + [F('negro', 1.5)]
base = json.load(open('datos/campana/capitulo_15.json', encoding='utf-8'))
PERS = dict(base['personajes'], samuel='Samuel Hartmann', guardia='Un guardia de Falk')
tr('Un guardia de Falk', "One of Falk's guards", 'Ein Wachmann von Falk')
MIRAR = tr('Mirar', 'Look', 'Ansehen')
def capitulo(id_, n, t_es, t_en, t_de, xp, pasos):
    titulo = tr('Capítulo %d: %s' % (n, t_es), 'Chapter %d: %s' % (n, t_en), 'Kapitel %d: %s' % (n, t_de))
    tr(t_es, t_en, t_de)
    desc = tr('Terminá el Capítulo %d de Nacht über Berlin.' % n, 'Finish Chapter %d of Nacht über Berlin.' % n, 'Beende Kapitel %d von Nacht über Berlin.' % n)
    logro = 'nacht_capitulo_%d' % n
    return {'_leeme': base['_leeme'], 'id': id_, 'titulo': titulo, 'duracionMinima': 45, 'aviso': base['aviso'],
            'recompensa': {'xp': xp, 'logro': logro}, 'personajes': PERS, 'pasos': pasos}, (logro, t_es, desc)

# ---------------- Capítulo 16: La ciudad arde ----------------
cap16, l16 = capitulo('capitulo_16', 16, 'La ciudad arde', 'The City Burns', 'Die Stadt brennt', 300, INGRID([
  D('ingrid', 'La foto dieciséis es de una ventana con las cortinas cerradas. De día.', 'Photo sixteen is of a window with the curtains drawn. In daytime.', 'Foto sechzehn zeigt ein Fenster mit zugezogenen Vorhängen. Am Tag.', 'triste')]) + [
  TJ('Acto VII · La caída de los hombres', 'Act VII · The Fall of Men', 'Siebter Akt · Der Fall der Männer', 3, estilo='documento'),
  TJ('Berlín se vuelve irrespirable. La organización perdió a varios de sus intermediarios. La policía busca a Viktor. A Anneliese la persiguen sus viejos contactos.',
     'Berlin becomes suffocating. The organisation has lost several of its go-betweens. The police are looking for Viktor. Anneliese is being hunted by her old contacts.',
     'Berlin wird unerträglich. Die Organisation hat mehrere ihrer Mittelsmänner verloren. Die Polizei sucht Viktor. Anneliese wird von ihren alten Kontakten verfolgt.', 5),
  {'tipo': 'escena', 'mapa': 'departamento', 'actores': ['anneliese', 'thomas'], 'jugador': 'viktor', 'props': ['fotos']},
  F('escena', 1), TJ('Berlín Occidental · 1962 · De noche', 'West Berlin · 1962 · At night', 'West-Berlin · 1962 · Nachts'), G('noche'),
  TJ('Thomas ya no duerme. Escucha ruidos todo el tiempo.', 'Thomas no longer sleeps. He hears noises all the time.', 'Thomas schläft nicht mehr. Er hört ständig Geräusche.', 3),
  {'tipo': 'paseo', 'texto': tr('Revisá la casa', 'Check the flat', 'Sieh in der Wohnung nach'), 'minimo': 2, 'fin': {'actor': 'thomas', 'accion': tr('Hablar con Thomas', 'Talk to Thomas', 'Mit Thomas reden')}, 'puntos': [
    {'objeto': 'frente_ventana', 'accion': MIRAR, 'pasos': [
      TJ('Un auto estacionado con el motor apagado. Hace una hora que está ahí.', 'A parked car with its engine off. It has been there for an hour.', 'Ein geparktes Auto mit abgestelltem Motor. Es steht schon seit einer Stunde da.', 3),
      D('viktor', 'Puede ser cualquiera.', 'It could be anyone.', 'Das kann jeder sein.')]},
    {'objeto': 'puerta', 'accion': MIRAR, 'pasos': [
      TJ('La cerradura nueva que puso Thomas. Tres vueltas. Y una silla trabando el picaporte.', 'The new lock Thomas fitted. Three turns. And a chair wedged under the handle.', 'Das neue Schloss, das Thomas eingebaut hat. Drei Umdrehungen. Und ein Stuhl unter der Klinke.', 3)]},
    {'objeto': 'fotos', 'accion': MIRAR, 'pasos': [
      TJ('La foto de los tres hombres. Dos ya están muertos.', 'The photo of the three men. Two of them are already dead.', 'Das Foto der drei Männer. Zwei von ihnen sind schon tot.', 3)]}]},
  FR(True),
  D('thomas', '¿Escuchaste eso?', 'Did you hear that?', 'Hast du das gehört?'),
  D('viktor', 'No hay nada, Thomas.', "There's nothing there, Thomas.", 'Da ist nichts, Thomas.'),
  D('thomas', 'Siempre hay algo.', 'There is always something.', 'Irgendwas ist immer.', 'enojado'),
  D('thomas', 'Vos nos condenaste, Anneliese. Desde el primer día, con tu Herr Schwarz.', 'You doomed us, Anneliese. From day one, with your Herr Schwarz.', 'Du hast uns ins Verderben gestürzt, Anneliese. Vom ersten Tag an, mit deinem Herrn Schwarz.', 'enojado'),
  D('anneliese', '¿Yo? ¿Y quién siguió a Lukas una noche y no dijo nada?', 'Me? And who followed Lukas one night and said nothing?', 'Ich? Und wer ist Lukas eines Nachts gefolgt und hat nichts gesagt?', 'enojado'),
  D('viktor', 'Basta. Los dos.', 'Enough. Both of you.', 'Schluss. Alle beide.', 'enojado'),
  D('thomas', '¡Friedrich está muerto! ¡Martin está muerto! ¡Y seguimos haciendo exactamente lo mismo!', "Friedrich is dead! Martin is dead! And we're still doing exactly the same thing!", 'Friedrich ist tot! Martin ist tot! Und wir machen immer noch genau dasselbe!', 'enojado'),
  D('viktor', 'Todavía podemos terminar con todo.', 'We can still finish all of this.', 'Wir können das alles noch zu Ende bringen.'),
  D('thomas', '¿Y después qué?', 'And then what?', 'Und dann?', 'triste'),
  TJ('Nadie responde.', 'Nobody answers.', 'Niemand antwortet.', 3, estilo='documento'),
  F('negro', 1.5), TJ('A la mañana siguiente', 'The next morning', 'Am nächsten Morgen', 2),
  SI('trato_thomas', 'culpar', [
    {'tipo': 'escena', 'mapa': 'departamento', 'actores': ['anneliese'], 'jugador': 'viktor', 'props': ['fotos', 'carta', 'estuche']},
    F('escena', 1),
    D('anneliese', 'Thomas no está. Dejó algo en la mesa.', "Thomas is gone. He left something on the table.", 'Thomas ist weg. Er hat etwas auf dem Tisch gelassen.', 'triste'),
    {'tipo': 'interactuar', 'objeto': 'carta', 'texto': tr('Leé lo que dejó Thomas', 'Read what Thomas left', 'Lies, was Thomas hinterlassen hat'), 'accion': tr('Leer la carta', 'Read the letter', 'Den Brief lesen'), 'quedarse': True},
    TJ('Está cansado. No quiere morir. Juntó plata suficiente para intentar empezar una vida nueva.', 'He is tired. He does not want to die. He has scraped together enough money to try to start a new life.', 'Er ist müde. Er will nicht sterben. Er hat genug Geld beisammen, um ein neues Leben zu versuchen.', 5, estilo='documento'),
    {'tipo': 'interactuar', 'objeto': 'estuche', 'texto': tr('Mirá lo que hay al lado de la carta', 'Look at what is next to the letter', 'Sieh dir an, was neben dem Brief liegt'), 'accion': tr('Mirar el estuche', 'Look at the case', 'Das Etui ansehen'), 'quedarse': True},
    TJ('Su estuche de ganzúas. El que nunca fallaba.', 'His case of lock picks. The one that never failed.', 'Sein Etui mit Dietrichen. Das, mit dem es immer klappte.', 3),
    D('anneliese', '¿Lo vamos a buscar?', 'Are we going after him?', 'Suchen wir ihn?'),
    D('viktor', 'No.', 'No.', 'Nein.', 'triste'),
    TJ('Viktor decide no perseguirlo.', 'Viktor decides not to go after him.', 'Viktor beschließt, ihm nicht zu folgen.', 3)]),
  SI('trato_thomas', 'entender', [
    {'tipo': 'escena', 'mapa': 'departamento', 'actores': ['anneliese', 'thomas'], 'jugador': 'viktor', 'props': ['fotos', 'estuche']},
    F('escena', 1),
    TJ('Thomas está sentado a la mesa, con su estuche de ganzúas abierto.', 'Thomas is sitting at the table with his case of lock picks open.', 'Thomas sitzt am Tisch, sein Etui mit Dietrichen aufgeklappt.', 3),
    D('thomas', 'No me fui.', "I didn't leave.", 'Ich bin nicht gegangen.'),
    D('viktor', 'Ya veo.', 'So I see.', 'Das sehe ich.'),
    D('thomas', 'Me quedo hasta el final. Pero cuando esto termine, me voy lejos. Y no me van a buscar.', "I'll stay until the end. But when this is over, I'm going far away. And you won't come looking for me.", 'Ich bleibe bis zum Ende. Aber wenn das vorbei ist, gehe ich weit weg. Und ihr sucht mich nicht.'),
    D('viktor', 'Trato.', 'Deal.', 'Abgemacht.')]),
  G('manana'),
  SI('trato_thomas', 'culpar', [TJ('El grupo queda reducido a Viktor y Anneliese. Lukas sigue desaparecido. Erich Falk todavía controla gran parte de la operación.', 'The group is down to Viktor and Anneliese. Lukas is still missing. Erich Falk still controls much of the operation.', 'Von der Gruppe sind nur noch Viktor und Anneliese übrig. Lukas bleibt verschwunden. Erich Falk kontrolliert noch immer einen Großteil der Operation.', 5)]),
  SI('trato_thomas', 'entender', [TJ('Quedan tres: Viktor, Anneliese y Thomas. Lukas sigue desaparecido. Erich Falk todavía controla gran parte de la operación.', 'Three are left: Viktor, Anneliese and Thomas. Lukas is still missing. Erich Falk still controls much of the operation.', 'Drei sind noch übrig: Viktor, Anneliese und Thomas. Lukas bleibt verschwunden. Erich Falk kontrolliert noch immer einen Großteil der Operation.', 5)]),
  F('negro', 2), G('final'), TJ('Fin del Capítulo 16', 'End of Chapter 16', 'Ende von Kapitel 16'), {'tipo': 'fin'}])

# ---------------- Capítulo 17: El último aliado ----------------
cap17, l17 = capitulo('capitulo_17', 17, 'El último aliado', 'The Last Ally', 'Der letzte Verbündete', 400, INGRID([
  D('ingrid', 'La foto diecisiete: un hombre mayor con sombrero, en la puerta de una librería. Atrás, dos iniciales: «S. H.»', 'Photo seventeen: an older man in a hat, at the door of a bookshop. On the back, two initials: “S. H.”', 'Foto siebzehn: ein älterer Mann mit Hut vor der Tür einer Buchhandlung. Hinten zwei Initialen: „S. H.“', 'triste'),
  D('ingrid', 'S. H.... ¿Hartmann? La señora del registro de Mitte tiene un hermano que se llama Samuel.', "S. H.... Hartmann? The woman at the registry office in Mitte has a brother called Samuel.", 'S. H.... Hartmann? Die Frau von der Meldestelle in Mitte hat einen Bruder namens Samuel.')]) + [
  {'tipo': 'escena', 'mapa': 'restaurante', 'actores': ['anneliese', 'samuel'], 'jugador': 'viktor'},
  SI('trato_thomas', 'entender', [{'tipo': 'aparecer', 'actores': ['thomas']}]),
  F('escena', 1), TJ('Berlín Occidental · 1962 · Un café de Schöneberg', 'West Berlin · 1962 · A café in Schöneberg', 'West-Berlin · 1962 · Ein Café in Schöneberg'), G('samuel'),
  {'tipo': 'objetivo', 'texto': tr('Hablá con Samuel Hartmann', 'Talk to Samuel Hartmann', 'Rede mit Samuel Hartmann'), 'zona': 'samuel', 'radio': 80, 'accion': tr('Hablar con Samuel', 'Talk to Samuel', 'Mit Samuel reden')},
  D('samuel', 'Trabajé para los americanos hasta que entendí que mis jefes también le pagaban a Weiss.', 'I worked for the Americans until I realised my bosses were paying Weiss too.', 'Ich habe für die Amerikaner gearbeitet, bis ich begriff, dass meine Vorgesetzten auch Weiss bezahlten.'),
  D('samuel', 'El archivo original lo trasladaron a un depósito subterráneo, en una instalación militar abandonada.', 'The original archive was moved to an underground depot, in an abandoned military installation.', 'Das Originalarchiv wurde in ein unterirdisches Depot gebracht, in einer verlassenen Militäranlage.'),
  D('samuel', 'Para entrar hace falta un código de acceso. Lo tiene Erich Falk.', 'You need an access code to get in. Erich Falk has it.', 'Um hineinzukommen, braucht man einen Zugangscode. Den hat Erich Falk.'),
  D('anneliese', '¿Por qué nos ayuda?', 'Why are you helping us?', 'Warum helfen Sie uns?'),
  D('samuel', 'Tengo una hermana del otro lado del Muro. Gertrud. Trabaja en un registro, en Mitte. Cree que yo tuve suerte.', 'I have a sister on the other side of the Wall. Gertrud. She works at a registry office in Mitte. She thinks I was lucky.', 'Ich habe eine Schwester auf der anderen Seite der Mauer. Gertrud. Sie arbeitet in einer Meldestelle in Mitte. Sie glaubt, ich hätte Glück gehabt.'),
  D('samuel', 'Quiero que algún día eso sea verdad.', 'I want that to be true one day.', 'Ich möchte, dass das eines Tages wahr ist.', 'triste'),
  D('viktor', 'Entonces voy a buscar a Falk.', "Then I'm going after Falk.", 'Dann hole ich mir Falk.', 'enojado'),
  D('anneliese', 'No así. Esta vez, a mi manera: el jueves tiene una reunión privada. Lo sacamos sin un tiro.', 'Not like that. This time, my way: on Thursday he has a private meeting. We get him out without firing a shot.', 'Nicht so. Diesmal auf meine Art: Am Donnerstag hat er ein privates Treffen. Wir holen ihn raus, ohne einen Schuss.'),
  SI('trato_thomas', 'entender', [D('thomas', 'Yo abro las puertas.', "I'll open the doors.", 'Ich mache die Türen auf.')]),
  {'tipo': 'elegir', 'clave': 'plan_falk', 'pregunta': tr('¿Qué hace Viktor?', 'What does Viktor do?', 'Was macht Viktor?'), 'opciones': [
    {'valor': 'escuchar', 'texto': tr('Está bien. A tu manera.', 'All right. Your way.', 'Gut. Auf deine Art.')},
    {'valor': 'a_mi_manera', 'texto': tr('No. Entramos y lo sacamos.', 'No. We go in and take him.', 'Nein. Wir gehen rein und holen ihn.')}]},
  SI('plan_falk', 'escuchar', [TJ('Por primera vez, Viktor la escucha.', 'For the first time, Viktor listens to her.', 'Zum ersten Mal hört Viktor auf sie.', 3)]),
  F('negro', 1),
  {'tipo': 'escena', 'mapa': 'restaurante', 'actores': ['falk', 'anneliese', 'guardia_1', 'guardia_2', 'guardia_3'], 'jugador': 'viktor'},
  F('escena', 1), TJ('El jueves · Un salón privado', 'Thursday · A private room', 'Donnerstag · Ein Nebenzimmer'), G('reunion'),
  SI('plan_falk', 'escuchar', [
    {'tipo': 'sigilo', 'guardias': ['guardia_1', 'guardia_2'], 'meta': 'frente_falk', 'radio': 45, 'texto': tr('Llegá hasta Falk sin que te vean', 'Reach Falk without being seen', 'Gelange zu Falk, ohne gesehen zu werden')}]),
  SI('plan_falk', 'a_mi_manera', [
    {'tipo': 'combate', 'id': 'reunion_falk', 'mapa': 'restaurante', 'lider': 'viktor', 'objetivo': {'tipo': 'eliminar', 'rondas': 10},
     'texto': tr('Entran a la reunión: derribá a los guardias de Falk antes de que se lo lleven (10 rondas)', "You burst into the meeting: take down Falk's guards before they get him out (10 rounds)", 'Ihr stürmt das Treffen: Schalte Falks Wachen aus, bevor sie ihn wegbringen (10 Runden)'),
     'unidades': {'viktor': U('aliado', [9, 11], 5, 4, 80), 'anneliese': U('aliado', [7, 11], 4, 4, 65),
                  'guardia_1': U('enemigo', [14, 5], 3, 4, 50), 'guardia_2': U('enemigo', [16, 9], 3, 4, 50), 'guardia_3': U('enemigo', [5, 6], 3, 4, 50)}}]),
  {'tipo': 'interactuar', 'objeto': 'falk', 'texto': tr('Agarrá a Falk', 'Grab Falk', 'Pack dir Falk'), 'accion': tr('Reducir a Falk', 'Overpower Falk', 'Falk überwältigen')},
  D('falk', 'Adler. Tenía que ser.', 'Adler. It had to be you.', 'Adler. Wer sonst.'),
  F('negro', 1.5),
  {'tipo': 'escena', 'mapa': 'sala_interrogatorio', 'actores': ['anneliese'], 'jugador': 'viktor'},
  {'tipo': 'aparecer', 'actores': ['falk'], 'en': 'falk_silla'},
  F('escena', 1), G('interrogatorio'),
  TJ('La misma sala. Ahora el de la silla es Falk.', 'The same room. Now Falk is the one in the chair.', 'Derselbe Raum. Jetzt sitzt Falk auf dem Stuhl.', 3, estilo='documento'),
  FR(True),
  D('falk', 'Projekt Morgen nunca fue para controlar Alemania. Es una red de chantaje y de plata, hecha para sobrevivir a cualquier gobierno.', 'Projekt Morgen was never about controlling Germany. It is a network of blackmail and money, built to survive any government.', 'Bei Projekt Morgen ging es nie darum, Deutschland zu kontrollieren. Es ist ein Netz aus Erpressung und Geld, gebaut, um jede Regierung zu überleben.'),
  D('falk', 'Durante años usamos el miedo a otra guerra para quedarnos donde estábamos.', 'For years we used the fear of another war to stay where we were.', 'Jahrelang haben wir die Angst vor einem neuen Krieg benutzt, um zu bleiben, wo wir waren.'),
  D('falk', 'El archivo tiene todo: asesinatos, operaciones clandestinas, identidades falsas, acuerdos que nadie firmó en público.', 'The archive has everything: murders, covert operations, false identities, agreements nobody signed in public.', 'Im Archiv steht alles: Morde, verdeckte Operationen, falsche Identitäten, Abkommen, die niemand öffentlich unterschrieben hat.'),
  D('viktor', '¿Quién es el Arquitecto?', 'Who is the Architect?', 'Wer ist der Architekt?', 'enojado'),
  TJ('Falk sonríe.', 'Falk smiles.', 'Falk lächelt.', 2),
  D('falk', 'No hay un solo Arquitecto.', 'There is no single Architect.', 'Es gibt nicht den einen Architekten.', 'contento'),
  D('viktor', '¿Qué?', 'What?', 'Was?'),
  D('falk', 'Siempre hubo alguien detrás de nosotros. Pero nunca fue la misma persona.', 'There was always someone behind us. But it was never the same person.', 'Es stand immer jemand hinter uns. Aber es war nie dieselbe Person.'),
  D('falk', 'La organización está hecha para sobrevivir a sus jefes. Y Lukas ya está armando la próxima.', 'The organisation is built to outlive its leaders. And Lukas is already building the next one.', 'Die Organisation ist so gebaut, dass sie ihre Anführer überlebt. Und Lukas baut schon die nächste auf.'),
  {'tipo': 'elegir', 'clave': 'destino_falk', 'pregunta': tr('¿Qué hace Viktor con Falk?', 'What does Viktor do with Falk?', 'Was macht Viktor mit Falk?'), 'opciones': [
    {'valor': 'disparar', 'texto': tr('Apretar el gatillo.', 'Pull the trigger.', 'Abdrücken.')},
    {'valor': 'usarlo', 'texto': tr('Bajar el arma. Todavía sirve.', "Lower the gun. He's still useful.", 'Die Waffe senken. Er ist noch nützlich.')}]},
  SI('destino_falk', 'disparar', [D('anneliese', '¡No! Si lo matás, no hay archivo.', 'No! If you kill him, there is no archive.', 'Nein! Wenn du ihn tötest, gibt es kein Archiv.', 'enojado'),
                                  TJ('Viktor baja el arma. Le tiembla la mano.', 'Viktor lowers the gun. His hand is shaking.', 'Viktor senkt die Waffe. Seine Hand zittert.', 3)]),
  SI('destino_falk', 'usarlo', [TJ('Viktor baja el arma.', 'Viktor lowers the gun.', 'Viktor senkt die Waffe.', 2)]),
  D('falk', 'Sabía que eras más listo que tu hermano.', 'I knew you were smarter than your brother.', 'Ich wusste, dass du klüger bist als dein Bruder.', 'contento'),
  TJ('Matar a Erich no terminaría nada. Pero Erich todavía puede llevarlos hasta el archivo.', 'Killing Erich would end nothing. But Erich can still lead them to the archive.', 'Erich zu töten würde nichts beenden. Aber Erich kann sie noch zum Archiv führen.', 5),
  F('negro', 2), G('final'), TJ('Fin del Acto VII', 'End of Act VII', 'Ende des siebten Akts'), {'tipo': 'fin'}])

for cap in (cap16, cap17):
    json.dump(cap, open('datos/campana/%s.json' % cap['id'], 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
caps = json.load(open('datos/campana/capitulos.json', encoding='utf-8'))
for c in caps['capitulos']:
    if c['id'] == 'capitulo_16': c['disponible'] = True
if not any(c['id'] == 'capitulo_17' for c in caps['capitulos']): caps['capitulos'].append({'id': 'capitulo_17', 'titulo': cap17['titulo'], 'disponible': True})
if not any(c['id'] == 'capitulo_18' for c in caps['capitulos']):
    caps['capitulos'].append({'id': 'capitulo_18', 'titulo': tr('Capítulo 18: El archivo', 'Chapter 18: The Archive', 'Kapitel 18: Das Archiv'), 'disponible': False})
json.dump(caps, open('datos/campana/capitulos.json', 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
p = 'datos/mundo/misiones.json'; d = json.load(open(p, encoding='utf-8'))
for (lid, nom, desc) in (l16, l17):
    if not any(l['id'] == lid for l in d['logros']): d['logros'].append({'id': lid, 'nombre': nom, 'descripcion': desc, 'condicion': {'campana': lid}})
json.dump(d, open(p, 'w', encoding='utf-8'), ensure_ascii=False, indent=2)
json.dump(EN, open(RUTA_EN, 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
json.dump(DE, open(RUTA_DE, 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
print('capítulos 16 y 17:', len(cap16['pasos']), len(cap17['pasos']), 'pasos')
