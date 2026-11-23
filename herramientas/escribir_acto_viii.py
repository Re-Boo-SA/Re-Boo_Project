#!/usr/bin/env python3
"""Escribe el final de la campaña: Acto VIII «La última noche» (capítulo 18) y el capítulo 19 con los tres finales,
con su inglés y su alemán al lado.   python herramientas/escribir_acto_viii.py   (después: python herramientas/traducir_contenido.py)
Las decisiones de toda la campaña cambian el tono y las consecuencias (como pide el documento):
 - trato_thomas = entender: Thomas está en la instalación, le cierra el paso a Falk y aparece en los finales.
 - perdon_anneliese = perdonar: Lotte ayuda a repartir los documentos (final A).
 - destino_falk / Falk capturado o escapado: cambia lo que se publica.
Decisión final: `final` = entregar (A, «La verdad») / quedarse (B, «El hombre que sobrevivió») / destruir (C, «Nadie sale de Berlín»).
El marco de 1975 se cierra: Ingrid termina de leer la caja, distinto en cada final."""
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
                        F('escena', 1.5), TJ('Berlín Occidental · 1975', 'West Berlin · 1975', 'West-Berlin · 1975')] + pasos
base = json.load(open('datos/campana/capitulo_17.json', encoding='utf-8'))
PERS = dict(base['personajes'], trabajador='Un trabajador', maton='Un hombre de Lukas', agente='Un desconocido', voz='Una voz')
tr('Un trabajador', 'A worker', 'Ein Arbeiter'); tr('Un hombre de Lukas', "One of Lukas's men", 'Einer von Lukas’ Männern'); tr('Una voz', 'A voice', 'Eine Stimme')
def capitulo(id_, n, t_es, t_en, t_de, xp, pasos):
    titulo = tr('Capítulo %d: %s' % (n, t_es), 'Chapter %d: %s' % (n, t_en), 'Kapitel %d: %s' % (n, t_de))
    tr(t_es, t_en, t_de)
    desc = tr('Terminá el Capítulo %d de Nacht über Berlin.' % n, 'Finish Chapter %d of Nacht über Berlin.' % n, 'Beende Kapitel %d von Nacht über Berlin.' % n)
    logro = 'nacht_capitulo_%d' % n
    return {'_leeme': base['_leeme'], 'id': id_, 'titulo': titulo, 'duracionMinima': 45, 'aviso': base['aviso'],
            'recompensa': {'xp': xp, 'logro': logro}, 'personajes': PERS, 'pasos': pasos}, (logro, t_es, desc)

# ---------------- Capítulo 18: El archivo ----------------
cap18, l18 = capitulo('capitulo_18', 18, 'El archivo', 'The Archive', 'Das Archiv', 400, INGRID([
  D('ingrid', 'Las últimas fotos de la caja son de 1968. Viktor ya no se parece al de las primeras.', 'The last photos in the box are from 1968. Viktor no longer looks like the man in the first ones.', 'Die letzten Fotos in der Kiste sind von 1968. Viktor sieht nicht mehr aus wie auf den ersten.', 'triste'),
  F('negro', 1.5)]) + [
  TJ('Acto VIII · La última noche', 'Act VIII · The Last Night', 'Achter Akt · Die letzte Nacht', 3, estilo='documento'),
  TJ('Berlín Oriental · Noviembre de 1968', 'East Berlin · November 1968', 'Ost-Berlin · November 1968', 3),
  TJ('Pasaron varios años. Viktor perdió a Friedrich. Perdió a su hermano. Ahora solo le queda Anneliese.', 'Several years have passed. Viktor has lost Friedrich. He has lost his brother. Now only Anneliese is left.', 'Mehrere Jahre sind vergangen. Viktor hat Friedrich verloren. Er hat seinen Bruder verloren. Jetzt ist nur noch Anneliese da.', 5),
  SI('trato_thomas', 'entender', [TJ('Y Thomas, que prometió quedarse hasta el final.', 'And Thomas, who promised to stay until the end.', 'Und Thomas, der versprochen hat, bis zum Ende zu bleiben.', 3)]),
  TJ('Erich Falk los guía. Viktor no le saca los ojos de encima.', 'Erich Falk is guiding them. Viktor never takes his eyes off him.', 'Erich Falk führt sie. Viktor lässt ihn keinen Moment aus den Augen.', 3),
  D('anneliese', '¿Qué vas a hacer cuando todo esto termine?', 'What will you do when all this is over?', 'Was machst du, wenn das alles vorbei ist?'),
  D('viktor', 'No sé.', "I don't know.", 'Weiß ich nicht.'),
  D('anneliese', '¿Vas a volver a manejar?', 'Will you go back to driving?', 'Fährst du wieder?'),
  D('viktor', 'Supongo.', 'I suppose.', 'Schätze schon.'),
  D('anneliese', '¿Y después?', 'And after that?', 'Und danach?'),
  TJ('Viktor tarda en responder.', 'Viktor takes a while to answer.', 'Viktor braucht lange für die Antwort.', 2),
  D('viktor', 'Voy a intentar acordarme de cómo era vivir sin mirar todo el tiempo por encima del hombro.', 'I will try to remember what it was like to live without always looking over my shoulder.', 'Ich werde versuchen, mich zu erinnern, wie es war, zu leben, ohne ständig über die Schulter zu schauen.'),
  TJ('Anneliese sonríe. Es uno de los pocos momentos tranquilos de toda la historia.', 'Anneliese smiles. It is one of the few quiet moments in the whole story.', 'Anneliese lächelt. Es ist einer der wenigen ruhigen Momente der ganzen Geschichte.', 4),
  F('negro', 1.5),
  {'tipo': 'escena', 'mapa': 'instalacion', 'actores': ['anneliese', 'falk', 'trabajador_1', 'trabajador_2', 'trabajador_3'], 'jugador': 'viktor', 'props': ['carpeta', 'archivo']},
  {'tipo': 'tumbar', 'actor': 'trabajador_1'}, {'tipo': 'tumbar', 'actor': 'trabajador_2'}, {'tipo': 'tumbar', 'actor': 'trabajador_3'},
  SI('trato_thomas', 'entender', [{'tipo': 'aparecer', 'actores': ['thomas']}]),
  F('escena', 1), TJ('Una instalación militar abandonada', 'An abandoned military installation', 'Eine verlassene Militäranlage', 3), G('instalacion'),
  D('falk', 'El archivo está en el nivel de abajo. Por el pasillo del fondo.', 'The archive is on the lower level. Down the corridor at the end.', 'Das Archiv ist auf der unteren Ebene. Den Gang am Ende entlang.'),
  {'tipo': 'interactuar', 'objeto': 'trabajador_2', 'texto': tr('Revisá el pasillo', 'Check the corridor', 'Sieh dir den Gang an'), 'accion': tr('Mirar', 'Look', 'Ansehen'), 'quedarse': True},
  TJ('Cadáveres. Todos eran trabajadores de la instalación. Los mataron hace poco.', 'Bodies. All of them worked at the installation. They were killed recently.', 'Leichen. Alle haben in der Anlage gearbeitet. Sie wurden vor Kurzem getötet.', 4, estilo='documento'),
  D('anneliese', 'Lukas ya está acá.', 'Lukas is already here.', 'Lukas ist schon hier.', 'triste'),
  {'tipo': 'objetivo', 'texto': tr('Entrá a la sala del archivo', 'Go into the archive room', 'Geh in den Archivraum'), 'zona': 'entrada_archivo', 'radio': 70},
  {'tipo': 'aparecer', 'actores': ['lukas', 'maton_1', 'maton_2']},
  FR(True),
  D('lukas', 'Llegaste. Sabía que la grabación de tu padre te iba a traer hasta acá.', "You made it. I knew your father's recording would bring you here.", 'Du bist gekommen. Ich wusste, dass dich die Aufnahme deines Vaters hierherführen würde.'),
  D('lukas', 'Tengo una propuesta. Trabajá conmigo. Plata, una identidad nueva y la posibilidad de irte de Europa.', 'I have a proposal. Work with me. Money, a new identity and the chance to leave Europe.', 'Ich habe einen Vorschlag. Arbeite mit mir. Geld, eine neue Identität und die Möglichkeit, Europa zu verlassen.'),
  D('viktor', '¿Después de todo esto?', 'After all this?', 'Nach allem, was passiert ist?', 'enojado'),
  D('lukas', 'Precisamente por todo esto.', 'Precisely because of all this.', 'Gerade deswegen.'),
  FR(False),
  {'tipo': 'interactuar', 'objeto': 'carpeta', 'texto': tr('Anneliese encontró una carpeta: mirala con ella', 'Anneliese has found a folder: look at it with her', 'Anneliese hat eine Mappe gefunden: Sieh sie dir mit ihr an'), 'accion': tr('Abrir la carpeta', 'Open the folder', 'Die Mappe öffnen'), 'quedarse': True},
  FR(True),
  TJ('El nombre de su padre. Fotografías, informes médicos, papeles de traslado.', 'Her father’s name. Photographs, medical reports, transfer papers.', 'Der Name ihres Vaters. Fotos, ärztliche Berichte, Überstellungspapiere.', 4, estilo='documento'),
  TJ('Y un acta de defunción. Murió en 1957. No en una cárcel: en un hospital privado de Berlín Occidental. Envenenado.', 'And a death certificate. He died in 1957. Not in a prison: in a private hospital in West Berlin. Poisoned.', 'Und eine Sterbeurkunde. Er starb 1957. Nicht in einem Gefängnis: in einer Privatklinik in West-Berlin. Vergiftet.', 5, estilo='documento'),
  TJ('Lo mantuvieron vivo durante años. Y lo mataron cuando dejó de ser útil.', 'They kept him alive for years. And killed him when he stopped being useful.', 'Sie hielten ihn jahrelang am Leben. Und töteten ihn, als er nicht mehr nützlich war.', 4),
  D('anneliese', '¿Lo sabías?', 'Did you know?', 'Hast du es gewusst?', 'enojado'),
  TJ('Lukas no contesta.', 'Lukas does not answer.', 'Lukas antwortet nicht.', 2),
  TJ('Anneliese levanta el arma. Lukas dispara primero.', 'Anneliese raises her gun. Lukas fires first.', 'Anneliese hebt die Waffe. Lukas schießt zuerst.', 3, estilo='documento'),
  {'tipo': 'efecto', 'nombre': 'disparos', 'desde': ['lukas'], 'segundos': 0.4},
  {'tipo': 'tumbar', 'actor': 'anneliese'},
  {'tipo': 'mover', 'actor': 'lukas', 'a': 'deposito', 'segundos': 1.2, 'esperar': False},
  FR(False), G('tiroteo'),
  {'tipo': 'combate', 'id': 'archivo', 'mapa': 'instalacion', 'lider': 'viktor', 'objetivo': {'tipo': 'eliminar', 'rondas': 10},
   'texto': tr('Lukas huye a los túneles: derribá a sus hombres (10 rondas)', 'Lukas flees into the tunnels: take down his men (10 rounds)', 'Lukas flieht in die Tunnel: Schalte seine Männer aus (10 Runden)'),
   'unidades': {'viktor': U('aliado', [16, 4], 5, 4, 85),
                'maton_1': U('enemigo', [21, 2], 3, 4, 50), 'maton_2': U('enemigo', [20, 5], 3, 4, 50)}},
  FR(True),
  SI('trato_thomas', 'culpar', [
    {'tipo': 'mover', 'actor': 'falk', 'a': 'falk_huye', 'segundos': 1.5},
    {'tipo': 'desaparecer', 'actores': ['falk']},
    TJ('En el medio del tiroteo, Erich Falk se escapó con una parte del archivo.', 'In the middle of the shootout, Erich Falk escaped with part of the archive.', 'Mitten in der Schießerei ist Erich Falk mit einem Teil des Archivs entkommen.', 4)]),
  SI('trato_thomas', 'entender', [
    TJ('Falk intenta escaparse con una parte del archivo. No llega a la puerta: Thomas le cierra el paso.', 'Falk tries to escape with part of the archive. He never reaches the door: Thomas blocks his way.', 'Falk versucht, mit einem Teil des Archivs zu fliehen. Er kommt nicht bis zur Tür: Thomas stellt sich ihm in den Weg.', 4),
    D('thomas', 'Vos no vas a ningún lado.', "You're not going anywhere.", 'Du gehst nirgendwohin.', 'enojado')]),
  {'tipo': 'objetivo', 'texto': tr('Andá con Anneliese', 'Go to Anneliese', 'Geh zu Anneliese'), 'zona': 'anneliese', 'radio': 70, 'accion': tr('Levantarla', 'Lift her up', 'Sie aufheben')},
  TJ('Está muy mal herida. Le pone en la mano una fotografía chiquita: su padre.', 'She is badly wounded. She presses a small photograph into his hand: her father.', 'Sie ist schwer verletzt. Sie drückt ihm ein kleines Foto in die Hand: ihr Vater.', 4),
  D('anneliese', 'No dejes que desaparezcan otra vez.', "Don't let them disappear again.", 'Lass nicht zu, dass sie wieder verschwinden.', 'dolor'),
  TJ('Viktor intenta levantarla. Anneliese muere.', 'Viktor tries to lift her. Anneliese dies.', 'Viktor versucht, sie hochzuheben. Anneliese stirbt.', 4, estilo='documento'),
  F('negro', 2.5),
  SI('trato_thomas', 'culpar', [TJ('Viktor se queda solo.', 'Viktor is left alone.', 'Viktor bleibt allein zurück.', 3, estilo='documento')]),
  SI('trato_thomas', 'entender', [TJ('De los que empezaron en aquel auto bajo la lluvia, solo quedan Viktor y Thomas.', 'Of those who started out in that car in the rain, only Viktor and Thomas are left.', 'Von denen, die damals im Auto im Regen angefangen haben, sind nur Viktor und Thomas übrig.', 4, estilo='documento')]),
  G('final'), TJ('Fin del Capítulo 18', 'End of Chapter 18', 'Ende von Kapitel 18'), {'tipo': 'fin'}])

# ---------------- Capítulo 19: El último hombre (y los tres finales) ----------------
FIN = lambda: [F('negro', 2), G('final'), TJ('FIN', 'THE END', 'ENDE', 4, estilo='documento'), {'tipo': 'fin'}]
final_a = [
  TJ('Final A · La verdad', 'Ending A · The Truth', 'Ende A · Die Wahrheit', 3, estilo='documento'),
  TJ('Viktor entrega el archivo a periodistas y organizaciones independientes. No busca plata. No busca poder. Quiere que los desaparecidos tengan un nombre.',
     'Viktor hands the archive over to journalists and independent organisations. He does not want money. He does not want power. He wants the disappeared to have a name.',
     'Viktor übergibt das Archiv an Journalisten und unabhängige Organisationen. Er will kein Geld. Er will keine Macht. Er will, dass die Verschwundenen einen Namen haben.', 6),
  SI('perdon_anneliese', 'perdonar', [TJ('Lotte, la de los mensajes junto al Reichstag, se encarga de que los sobres lleguen a las redacciones de los dos lados.', 'Lotte, the woman with the messages near the Reichstag, makes sure the envelopes reach newsrooms on both sides.', 'Lotte, die Frau mit den Nachrichten am Reichstag, sorgt dafür, dass die Umschläge die Redaktionen auf beiden Seiten erreichen.', 5)]),
  TJ('En las semanas siguientes se publican cientos de documentos. Las autoridades lo niegan todo. Hay investigaciones. Algunos funcionarios renuncian. Otros desaparecen.',
     'Over the following weeks hundreds of documents are published. The authorities deny everything. There are investigations. Some officials resign. Others disappear.',
     'In den folgenden Wochen werden Hunderte Dokumente veröffentlicht. Die Behörden bestreiten alles. Es gibt Ermittlungen. Einige Beamte treten zurück. Andere verschwinden.', 6),
  SI('trato_thomas', 'entender', [TJ('Erich Falk declara ante un juez. Dice muchos nombres. No todos.', 'Erich Falk testifies before a judge. He names many names. Not all of them.', 'Erich Falk sagt vor einem Richter aus. Er nennt viele Namen. Nicht alle.', 4)]),
  SI('trato_thomas', 'culpar', [TJ('De Erich Falk no se vuelve a saber nada. La parte del archivo que se llevó nunca aparece.', 'Nothing more is heard of Erich Falk. The part of the archive he took never turns up.', 'Von Erich Falk hört man nie wieder etwas. Der Teil des Archivs, den er mitgenommen hat, taucht nie auf.', 4)]),
  TJ('Projekt Morgen no desaparece. Pero queda gravemente herido.', 'Projekt Morgen does not disappear. But it is badly wounded.', 'Projekt Morgen verschwindet nicht. Aber es ist schwer getroffen.', 4),
  F('negro', 2),
  {'tipo': 'escena', 'mapa': 'taller', 'actores': [], 'jugador': 'viktor', 'props': ['auto_taller']},
  F('escena', 1.5), TJ('Un pueblo de Alemania Occidental · Invierno', 'A small town in West Germany · Winter', 'Eine Kleinstadt in Westdeutschland · Winter', 3),
  TJ('Viktor vive en una casa sencilla. Arregla autos.', 'Viktor lives in a simple house. He repairs cars.', 'Viktor lebt in einem einfachen Haus. Er repariert Autos.', 3),
  SI('trato_thomas', 'entender', [TJ('Cada tanto llega una postal de algún lugar lejano. Nunca tiene firma. Siempre tiene un dibujito de una ganzúa.', 'Every so often a postcard arrives from somewhere far away. It is never signed. It always has a little drawing of a lock pick.', 'Ab und zu kommt eine Postkarte von irgendwo weit weg. Sie ist nie unterschrieben. Sie hat immer eine kleine Zeichnung von einem Dietrich.', 5)]),
  TJ('Una mañana llega una carta sin remitente. Adentro, una foto vieja de su familia.', 'One morning a letter arrives with no sender. Inside, an old photograph of his family.', 'Eines Morgens kommt ein Brief ohne Absender. Darin ein altes Foto seiner Familie.', 4),
  TJ('Atrás, con la letra de su hermano: «Al menos uno de nosotros consiguió volver a casa».', 'On the back, in his brother’s handwriting: “At least one of us made it home.”', 'Hinten, in der Handschrift seines Bruders: „Wenigstens einer von uns ist nach Hause gekommen.“', 5, estilo='documento'),
  TJ('Por primera vez en muchos años, Viktor no parece tener miedo.', 'For the first time in many years, Viktor does not seem afraid.', 'Zum ersten Mal seit vielen Jahren scheint Viktor keine Angst zu haben.', 4),
  F('negro', 2),
  {'tipo': 'escena', 'mapa': 'redaccion_1975', 'actores': ['ingrid'], 'jugador': None, 'props': ['caja']},
  F('escena', 1.5),
  D('ingrid', 'Así que esto es lo que me mandaron. No el archivo: la historia de cómo lo encontraron. Y de lo que costó.', 'So this is what they sent me. Not the archive: the story of how they found it. And what it cost.', 'Das ist es also, was man mir geschickt hat. Nicht das Archiv: die Geschichte, wie sie es gefunden haben. Und was es gekostet hat.'),
  D('ingrid', 'Mañana empiezo a escribir.', 'Tomorrow I start writing.', 'Morgen fange ich an zu schreiben.', 'contento')] + FIN()
final_b = [
  TJ('Final B · El hombre que sobrevivió', 'Ending B · The Man Who Survived', 'Ende B · Der Mann, der überlebte', 3, estilo='documento'),
  TJ('Viktor se queda con el archivo. Con los papeles chantajea a políticos, empresarios y funcionarios. Junta plata suficiente para irse de Berlín y empezar otra vida.',
     'Viktor keeps the archive. With the papers he blackmails politicians, businessmen and officials. He makes enough money to leave Berlin and start another life.',
     'Viktor behält das Archiv. Mit den Papieren erpresst er Politiker, Unternehmer und Beamte. Er verdient genug, um Berlin zu verlassen und ein anderes Leben anzufangen.', 6),
  TJ('Termina pareciéndose a los hombres que persiguió.', 'He ends up resembling the men he hunted.', 'Am Ende ähnelt er den Männern, die er gejagt hat.', 4),
  F('negro', 2),
  {'tipo': 'escena', 'mapa': 'mansion', 'actores': [], 'jugador': 'viktor', 'props': ['fotos', 'carta']},
  F('escena', 1.5), TJ('Un departamento de lujo · Años después', 'A luxury flat · Years later', 'Eine Luxuswohnung · Jahre später', 3),
  {'tipo': 'efecto', 'nombre': 'telefono', 'segundos': 2},
  D('voz', 'Tenemos un problema.', 'We have a problem.', 'Wir haben ein Problem.'),
  D('viktor', '¿Cuál?', 'What is it?', 'Welches?'),
  D('voz', 'Hay un hombre preguntando por el archivo.', 'There is a man asking about the archive.', 'Da ist ein Mann, der nach dem Archiv fragt.'),
  TJ('Viktor se queda en silencio. Después sonríe.', 'Viktor stays silent. Then he smiles.', 'Viktor schweigt. Dann lächelt er.', 3),
  D('viktor', 'Entonces hagan que desaparezca.', 'Then make him disappear.', 'Dann lasst ihn verschwinden.'),
  TJ('Sobre la mesa hay una foto: Friedrich, Martin, Thomas y Anneliese. Viktor la guarda en un cajón.', 'On the table there is a photograph: Friedrich, Martin, Thomas and Anneliese. Viktor puts it away in a drawer.', 'Auf dem Tisch liegt ein Foto: Friedrich, Martin, Thomas und Anneliese. Viktor legt es in eine Schublade.', 5, estilo='documento'),
  F('negro', 2),
  {'tipo': 'escena', 'mapa': 'redaccion_1975', 'actores': ['ingrid'], 'jugador': None, 'props': ['caja']},
  F('escena', 1.5),
  D('ingrid', 'No hay remitente. Pero alguien quería que yo leyera esto...', 'There is no sender. But someone wanted me to read this...', 'Es gibt keinen Absender. Aber jemand wollte, dass ich das lese...', 'triste'),
  D('ingrid', '...o quería saber hasta dónde iba a llegar.', '...or wanted to find out how far I would go.', '...oder wollte herausfinden, wie weit ich gehe.', 'triste')] + FIN()
final_c = [
  TJ('Final C · Nadie sale de Berlín', 'Ending C · Nobody Leaves Berlin', 'Ende C · Niemand verlässt Berlin', 3, estilo='documento'),
  TJ('Viktor decide destruir el archivo. Mientras exista, siempre va a haber alguien dispuesto a matar por él.', 'Viktor decides to destroy the archive. As long as it exists, there will always be someone willing to kill for it.', 'Viktor beschließt, das Archiv zu zerstören. Solange es existiert, wird es immer jemanden geben, der dafür tötet.', 5),
  {'tipo': 'efecto', 'nombre': 'golpe'}, {'tipo': 'efecto', 'nombre': 'disparos', 'desde': ['nadie'], 'segundos': 0.6},
  TJ('La instalación vuela por los aires. Viktor consigue salir, gravemente herido.', 'The installation blows up. Viktor gets out, badly wounded.', 'Die Anlage fliegt in die Luft. Viktor kommt heraus, schwer verletzt.', 4),
  TJ('Projekt Morgen pierde gran parte de sus registros. Pero algunos de sus integrantes sobreviven.', 'Projekt Morgen loses most of its records. But some of its members survive.', 'Projekt Morgen verliert einen Großteil seiner Unterlagen. Aber einige seiner Mitglieder überleben.', 4),
  F('negro', 2),
  {'tipo': 'escena', 'mapa': 'calle', 'actores': [], 'jugador': 'viktor', 'props': ['carteles']},
  F('escena', 1.5), TJ('Berlín Occidental', 'West Berlin', 'West-Berlin', 2),
  {'tipo': 'interactuar', 'objeto': 'carteles', 'texto': tr('Mirá la pared de carteles', 'Look at the wall of posters', 'Sieh dir die Wand mit den Plakaten an'), 'accion': tr('Mirar los carteles', 'Look at the posters', 'Die Plakate ansehen'), 'quedarse': True},
  TJ('Carteles de personas desaparecidas. Uno es de su hermano.', 'Posters of missing people. One of them is his brother’s.', 'Plakate von vermissten Menschen. Eines zeigt seinen Bruder.', 4, estilo='documento'),
  TJ('Viktor lo arranca despacio. Mira la ciudad. Después sigue caminando.', 'Viktor slowly tears it down. He looks at the city. Then he walks on.', 'Viktor reißt es langsam ab. Er sieht auf die Stadt. Dann geht er weiter.', 5),
  F('negro', 2),
  {'tipo': 'escena', 'mapa': 'instalacion', 'actores': ['agente'], 'jugador': None},
  F('escena', 1.5),
  TJ('Entre los escombros, un desconocido levanta un papel y se lo guarda en el abrigo.', 'Among the rubble, a stranger picks up a sheet of paper and tucks it into his coat.', 'In den Trümmern hebt ein Unbekannter ein Blatt Papier auf und steckt es in seinen Mantel.', 4),
  TJ('En el papel hay un sello. El mismo que estaba en la primera maleta.', 'On the paper there is a stamp. The same one that was in the first suitcase.', 'Auf dem Papier ist ein Stempel. Derselbe wie im ersten Koffer.', 4, estilo='documento'),
  TJ('Projekt Morgen continúa.', 'Projekt Morgen goes on.', 'Projekt Morgen geht weiter.', 4, estilo='documento'),
  F('negro', 2),
  {'tipo': 'escena', 'mapa': 'redaccion_1975', 'actores': ['ingrid'], 'jugador': None, 'props': ['caja']},
  F('escena', 1.5),
  D('ingrid', 'La última hoja tiene un sello. Lo vi antes...', 'The last page has a stamp. I have seen it before...', 'Auf der letzten Seite ist ein Stempel. Ich habe ihn schon einmal gesehen...', 'triste'),
  TJ('Ingrid da vuelta la caja. El mismo sello está en la tapa.', 'Ingrid turns the box over. The same stamp is on the lid.', 'Ingrid dreht die Kiste um. Derselbe Stempel ist auf dem Deckel.', 4, estilo='documento')] + FIN()

cap19, l19 = capitulo('capitulo_19', 19, 'El último hombre', 'The Last Man', 'Der letzte Mann', 500, [
  TJ('El final · La ciudad no perdona', 'The ending · The City Does Not Forgive', 'Das Ende · Die Stadt vergibt nicht', 3, estilo='documento'),
  {'tipo': 'escena', 'mapa': 'instalacion', 'actores': ['lukas'], 'jugador': 'viktor', 'props': ['archivo']},
  SI('trato_thomas', 'entender', [{'tipo': 'aparecer', 'actores': ['thomas', 'falk']}]),
  F('escena', 1), G('tuneles'),
  TJ('Lukas está herido. Huye por los túneles.', 'Lukas is wounded. He flees through the tunnels.', 'Lukas ist verletzt. Er flieht durch die Tunnel.', 3),
  SI('trato_thomas', 'entender', [D('thomas', 'Andá. Yo me quedo con Falk.', "Go. I'll stay with Falk.", 'Geh. Ich bleibe bei Falk.')]),
  {'tipo': 'seguir', 'objetivo': 'lukas', 'ruta': 'ruta_lukas', 'velocidad': 50, 'mirarSegundos': 1.4, 'lejos': 330,
   'texto': tr('Seguí a Lukas por los túneles: que no te vea, está armado', 'Follow Lukas through the tunnels: do not let him see you, he is armed', 'Folge Lukas durch die Tunnel: Lass dich nicht sehen, er ist bewaffnet')},
  {'tipo': 'objetivo', 'texto': tr('Entrá al depósito', 'Go into the storeroom', 'Geh ins Lager'), 'zona': 'frente_lukas', 'radio': 70},
  FR(True),
  TJ('Cientos de cajas. Documentos, fotos, grabaciones. Más de veinte años de operaciones.', 'Hundreds of boxes. Documents, photographs, recordings. More than twenty years of operations.', 'Hunderte Kisten. Dokumente, Fotos, Aufnahmen. Mehr als zwanzig Jahre Operationen.', 4, estilo='documento'),
  D('lukas', 'Esto no te va a servir de nada. Los gobiernos van a negarlo todo.', 'This will do you no good. The governments will deny everything.', 'Das wird dir nichts nützen. Die Regierungen werden alles abstreiten.', 'dolor'),
  D('lukas', 'A los que aparezcan acá los van a reemplazar. Y la organización va a seguir.', 'Whoever is named in here will be replaced. And the organisation will carry on.', 'Wer hier drinsteht, wird ersetzt. Und die Organisation macht weiter.', 'dolor'),
  D('viktor', '¿Por qué?', 'Why?', 'Warum?', 'enojado'),
  D('lukas', 'Porque el mundo no funciona como vos creés. La gente no quiere conocer la verdad. Quiere escuchar algo que la deje dormir tranquila.', "Because the world doesn't work the way you think. People don't want to know the truth. They want to hear something that lets them sleep at night.", 'Weil die Welt nicht so funktioniert, wie du denkst. Die Leute wollen die Wahrheit nicht wissen. Sie wollen etwas hören, mit dem sie ruhig schlafen können.', 'dolor'),
  {'tipo': 'efecto', 'nombre': 'disparos', 'desde': ['viktor'], 'segundos': 0.4},
  {'tipo': 'tumbar', 'actor': 'lukas'},
  TJ('Viktor dispara. Lukas cae.', 'Viktor fires. Lukas falls.', 'Viktor schießt. Lukas fällt.', 3, estilo='documento'),
  TJ('Viktor queda frente al archivo.', 'Viktor is left facing the archive.', 'Viktor steht vor dem Archiv.', 3),
  G('decision'),
  {'tipo': 'elegir', 'clave': 'final', 'pregunta': tr('¿Qué hace Viktor con el archivo?', 'What does Viktor do with the archive?', 'Was macht Viktor mit dem Archiv?'), 'opciones': [
    {'valor': 'entregar', 'texto': tr('Entregarlo: que los desaparecidos tengan un nombre.', 'Hand it over: let the disappeared have a name.', 'Es übergeben: Die Verschwundenen sollen einen Namen haben.')},
    {'valor': 'quedarse', 'texto': tr('Quedárselo: es la única garantía de seguir vivo.', "Keep it: it's the only guarantee of staying alive.", 'Es behalten: Es ist die einzige Garantie, am Leben zu bleiben.')},
    {'valor': 'destruir', 'texto': tr('Destruirlo: mientras exista, van a seguir matando por él.', 'Destroy it: as long as it exists, people will keep killing for it.', 'Es zerstören: Solange es existiert, wird man dafür töten.')}]},
  FR(False),
  SI('final', 'entregar', final_a), SI('final', 'quedarse', final_b), SI('final', 'destruir', final_c)])

cap19['logrosPorDecision'] = {'final': {'entregar': 'nacht_final_a', 'quedarse': 'nacht_final_b', 'destruir': 'nacht_final_c'}}   # un logro por final
for cap in (cap18, cap19):
    json.dump(cap, open('datos/campana/%s.json' % cap['id'], 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
caps = json.load(open('datos/campana/capitulos.json', encoding='utf-8'))
for c in caps['capitulos']:
    if c['id'] == 'capitulo_18': c['disponible'] = True
if not any(c['id'] == 'capitulo_19' for c in caps['capitulos']): caps['capitulos'].append({'id': 'capitulo_19', 'titulo': cap19['titulo'], 'disponible': True})
json.dump(caps, open('datos/campana/capitulos.json', 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
p = 'datos/mundo/misiones.json'; d = json.load(open(p, encoding='utf-8'))
extra = [(l18[0], l18[1], l18[2]), (l19[0], l19[1], l19[2])]
for lid, nom_es, nom_en, nom_de, desc_es, desc_en, desc_de in [
    ('nacht_final_a', 'La verdad', 'The Truth', 'Die Wahrheit', 'Llegá al final A de Nacht über Berlin.', 'Reach ending A of Nacht über Berlin.', 'Erreiche Ende A von Nacht über Berlin.'),
    ('nacht_final_b', 'El hombre que sobrevivió', 'The Man Who Survived', 'Der Mann, der überlebte', 'Llegá al final B de Nacht über Berlin.', 'Reach ending B of Nacht über Berlin.', 'Erreiche Ende B von Nacht über Berlin.'),
    ('nacht_final_c', 'Nadie sale de Berlín', 'Nobody Leaves Berlin', 'Niemand verlässt Berlin', 'Llegá al final C de Nacht über Berlin.', 'Reach ending C of Nacht über Berlin.', 'Erreiche Ende C von Nacht über Berlin.')]:
    tr(nom_es, nom_en, nom_de); tr(desc_es, desc_en, desc_de); extra.append((lid, nom_es, desc_es))
for (lid, nom, desc) in extra:
    if not any(l['id'] == lid for l in d['logros']): d['logros'].append({'id': lid, 'nombre': nom, 'descripcion': desc, 'condicion': {'campana': lid}})
json.dump(d, open(p, 'w', encoding='utf-8'), ensure_ascii=False, indent=2)
json.dump(EN, open(RUTA_EN, 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
json.dump(DE, open(RUTA_DE, 'w', encoding='utf-8'), ensure_ascii=False, indent=1)
print('capítulos 18 y 19:', len(cap18['pasos']), len(cap19['pasos']), 'pasos')
