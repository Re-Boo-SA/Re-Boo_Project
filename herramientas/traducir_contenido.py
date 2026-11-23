#!/usr/bin/env python3
"""
Traducciones al inglés del CONTENIDO del mundo (diálogos, misiones, contactos, logros, sucesos y plantillas del diario).
Las traducciones siguen la estructura de los archivos de datos (contacto, nodo, opción, paso), así no hay que
reescribir las frases en español como claves. Al final verifica que no quede ninguna frase sin traducir.
Correr desde la raíz del proyecto después de cambiar contenido en datos/mundo:
    python herramientas/traducir_contenido.py
Si agregás un nodo, una misión o un suceso, agregá su traducción acá en el mismo lugar de la estructura.
"""
import json
D='datos/mundo/'
dlg=json.load(open(D+'dialogos.json',encoding='utf-8')); mis=json.load(open(D+'misiones.json',encoding='utf-8')); suc=json.load(open(D+'sucesos.json',encoding='utf-8'))
EN={}
def par(es, en):
    if es and en: EN[es]=en
# ---------- Diálogos (por contacto y nodo: texto, ánimo, opciones en orden) ----------
T = {
 'periodista': {
  'inicio': ("Are you here to tell me something or to ask me something? Either works for me.", {'malhumorado': "Today's not a good day. Quick, what do you need?", 'animado': "You're just in time: my head is full of ideas. What do you need?"}, ["What are you working on?", "Just stopping by to say hello.", "Why does the Wall matter so much to you?"]),
  'trabajo': ("A piece about the people who wait at the crossings. Every one of them has someone on the other side.", {}, ["I can help you with that.", "Sounds dangerous."]),
  'peligro': ("Not telling it is more dangerous.", {}, []),
  'gracias': ("I'll take you up on that. Come by whenever you like.", {}, []),
  'saludo': ("Hello to you too. Be careful near the crossing.", {}, []),
  'nota': ("A journalist from the East sent me his last article before he disappeared. I never published it. I still don't know if it was caution or cowardice.", {}, ["Maybe it's not too late.", "You were right to wait."]),
  'tiempo': ("Maybe. When I have something to back it up.", {}, []),
  'esperar': ("Don't console me. It doesn't help.", {}, []),
  'reencuentro': ("I keep thinking about what I told you. Thanks for not repeating it around.", {}, ["What are you working on?", "See you."]),
  'fria': ("I'd rather not talk to you. You know why.", {}, []),
  'suceso_manifestacion': ("Did you see the demonstration? I'm going to cover it. Come along if you want: two pairs of eyes see more than one.", {}, ["I'll go with you.", "I'd rather stay out of it."]),
  'suceso_control_checkpoint': ("Today they're checking everyone at the crossing. You can't work like this: my sources can't get through.", {}, ["What are they looking for?", "It'll pass."]),
  'cierre_suceso': ("Let's hope so. Take care.", {}, [])},
 'obrero': {
  'desconfiado': ("From the other side, you? People here don't talk much with Westerners.", {}, ["I just want to get to know the city.", "Relax, I don't bite."]),
  'conocer': ("The city is the same on both sides. What changes is who's watching you.", {}, []),
  'seco': ("That's what they all say.", {}, []),
  'inicio': ("Another day on the site. What do you want?", {'malhumorado': "You can't work in this weather. What do you want?", 'animado': "Today the concrete sets by itself! What can I do for you?"}, ["What are you building?", "Do you have family here?", "Nothing, carry on."]),
  'obra': ("Housing. Prefab panel blocks. They're not pretty, but they have heating, and in winter that's worth gold.", {}, ["It's important work.", "They all look the same."]),
  'orgullo': ("Somebody has to do it.", {}, []),
  'iguales': ("The ones on the other side too, I bet. Just with advertising.", {}, []),
  'familia': ("My brother stayed on the other side in '61. I write to him. Sometimes he writes back.", {}, ["I'm very sorry.", "Have you thought about crossing?"]),
  'gracias': ("It's not your fault.", {}, []),
  'cuidado': ("You don't ask questions like that out loud. Ever.", {}, []),
  'nada': ("All right.", {}, []),
  'fria': ("I have nothing to talk to you about.", {}, []),
  'suceso_control_checkpoint': ("Today they're even checking pockets at the crossing. What my brother sent me won't get through.", {}, ["I'm sorry.", "What did he send you?"]),
  'suceso_apagon': ("No power, no work. The only good thing is I get off early today.", {}, ["Make the most of it."]),
  'suceso_torneo_cafe': ("There's a tournament at a café tonight! The guys on the crew can't talk about anything else.", {}, ["Are you going to play?"]),
  'cierre_suceso': ("Let's hope so. Take care.", {}, [])},
 'funcionaria': {
  'inicio': ("Registry office. Do you have an appointment?", {'animado': "Good afternoon. Few people today, luckily. Do you have an appointment?", 'malhumorado': "Without an appointment, I can't help you."}, ["No, I just wanted to talk.", "What do you register here?", "Have you always worked here?"]),
  'conversar': ("Conversation is not one of our procedures. But... all right, just a moment.", {}, ["Thank you for your time."]),
  'registro': ("Births, moves, changes of address. Everything that makes a person exist on paper.", {}, ["And if someone disappears from the papers?", "That must be a lot of work."]),
  'incomoda': ("That doesn't happen. The papers are never wrong.", {}, []),
  'trabajo': ("A lot. And nobody appreciates it.", {}, []),
  'samuel': ("Since '59. My brother Samuel left for the West before they closed it. He was lucky. Or so I want to believe.", {}, ["Do you miss him?", "Maybe one day you'll see each other again."]),
  'extrana': ("Every day. But that isn't written on any form.", {}, []),
  'algundia': ("Maybe. Don't get my hopes up.", {}, []),
  'confidencia': ("Please don't tell anyone about my brother.", {}, ["Don't worry.", "What do you register here?"]),
  'cortes': ("Have a good day.", {}, []),
  'fria': ("I have no pending business with you.", {}, []),
  'suceso_visita_oficial': ("Important people are coming today. Everything has to be perfect. I have no time to talk.", {}, ["Sorry, I'll come back another day."]),
  'suceso_apagon': ("Without power nothing can be registered. And the work piles up anyway.", {}, ["That must be frustrating."]),
  'cierre_suceso': ("Let's hope so. Take care.", {}, [])},
 'espia': {
  'inicio': ("I don't know you. And yet here you are.", {'animado': "A fine night for nobody to see you, isn't it?", 'malhumorado': "Too much sun. Too many people watching. What do you want?"}, ["I've seen you around before.", "What do you do?", "Sorry, wrong person."]),
  'observadora': ("And I've seen you. More often than you think.", {}, ["Are you following me?", "Then you know I can be trusted."]),
  'siguiendo': ("There's no need to follow anyone in this city. Everyone ends up passing through the same place.", {}, []),
  'veremos': ("I'll be the judge of that.", {}, []),
  'dedica': ("Listening. It's more useful than talking.", {}, ["I know how to listen too.", "Sounds like an excuse."]),
  'escuchar': ("We'll see.", {}, []),
  'excusa': ("Everything in Berlin sounds like an excuse. Get used to it.", {}, []),
  'equivoco': ("Nobody gets the wrong person near the Reichstag.", {}, []),
  'confianza': ("You're learning how to move around. I like that.", {}, ["What do you know about the Night List?", "See you."]),
  'lista': ("That it's better not to say its name out loud. Ask the journalist: she has something she never published.", {}, []),
  'adios': ("Don't look for me. I'll find you.", {}, []),
  'fria': ("Keep walking.", {}, []),
  'suceso_visita_oficial': ("Lots of important people in the city. Lots of people watching. I like it.", {}, ["Watching whom?"]),
  'suceso_concierto': ("Music covers conversations. That's why I like concerts.", {}, ["Who do you talk to?", "Enjoy it."]),
  'suceso_apagon': ("Without light, the city is honest: nobody sees anything, so nobody lies.", {}, ["What a way to see it."]),
  'cierre_suceso': ("Let's hope so. Take care.", {}, [])},
}
faltan_estructura=[]
for c, nodos in T.items():
    for idn, (texto, animos, opciones) in nodos.items():
        n = dlg[c]['nodos'].get(idn)
        if n is None: faltan_estructura.append((c, idn)); continue
        par(n['texto'], texto)
        for k, v in animos.items(): par(n.get('textoPorAnimo', {}).get(k), v)
        if len(opciones) != len(n['opciones']): faltan_estructura.append((c, idn, 'opciones', len(opciones), len(n['opciones'])))
        for o, en in zip(n['opciones'], opciones): par(o['texto'], en)
# ---------- Misiones ----------
M = {
 'periodista_1': ("First impressions", "I'm writing about the border crossing. Can you go to Checkpoint Charlie and tell me what it's like this week?", [("Go to Checkpoint Charlie, on the West side.", None), ("Go back to Ingrid and tell her what you saw.", "Lots of people waiting? I figured. This helps.")], "Thanks. With this I can start the article."),
 'periodista_2': ("Voices from the café", "I need to hear from ordinary people. Sit down and play a match at a café, then come back and tell me what people are saying.", [("Play a complete match (without leaving).", None), ("Go back to Ingrid.", "So in the cafés they talk about everything except the Wall. Interesting.")], "This gives the article some color. Thanks."),
 'obrero_1': ("Shift on the avenue", "I left my lunch at the TV Tower site. Would you do me the favor of picking it up?", [("Go to the TV Tower, in Mitte.", None), ("Bring Paul his lunch.", "My lunch! Without it I won't make it to the end of the shift.")], "I owe you one, comrade."),
 'obrero_2': ("The crew", "On the crew we're betting on who wins the most matches this week. Win one and I'll put you on the team.", [("Win a match.", None), ("Go back to Paul.", "That's it! You're part of the crew now.")], "The guys are going to be happy."),
 'funcionaria_1': ("Papers in order", "I need a witness's signature. Paul Lange, the one from the building site, will do. Talk to him and come back.", [("Talk to Paul, in Friedrichshain, next to the Centrum.", "Hartmann with her forms again? Fine, I'll sign."), ("Go back to Frau Hartmann.", "Correct signature. A stamp here, and here.")], "Your procedure is now on record."),
 'funcionaria_2': ("Signed and stamped", "The office needs to know how the cafés in the East are used. Play two matches in cafés on this side and I'll give you the certificate.", [("Play two matches in cafés on the East side (set up or join a table at an East café).", None), ("Go back to Frau Hartmann.", "Two matches, duly recorded. Very good.")], "Here is your certificate. Don't lose it."),
 'espia_1': ("A discreet errand", "Stop by the KaDeWe, then cross over and take a walk around the Barracks in the East. Don't talk to anyone. Then come back.", [("Go to the KaDeWe, in Schöneberg.", None), ("Cross at Checkpoint Charlie and go to the Barracks, in Lichtenberg.", None), ("Go back to Lotte, near the Reichstag.", "Nobody followed you. Good.")], "We never spoke. Here, for your trouble."),
 'periodista_3': ("The front page", "For the big story I need two testimonies: the woman who hangs around the Reichstag and the official from the East.", [("Talk to Lotte, near the Reichstag.", "A journalist? Tell her she doesn't know me. Well... tell her this."), ("Talk to Frau Hartmann, in Mitte.", "Officially I have nothing to declare. Unofficially..."), ("Take the testimonies to Ingrid.", "This is front-page news! I don't know how to thank you.")], "Tomorrow it's on the front page."),
}
M.update({
 'periodista_4': ("Turning the story around", "When a story goes badly, you write it again. Let's play: I start ahead and you have a single round to turn it around.", [("Beat Ingrid in a one-round match, starting behind.", None), ("Go back to Ingrid.", "From behind and in a single round. That's how a good story gets written.")], "You've earned a place in the newsroom."),
 'obrero_3': ("Checkpoint day", "Today they're checking everything at the crossing, and at the café we play the same way: no spies, no swaps. Beat me, and with soldiers in Military, which is what counts.", [("Beat Paul with at least 2 tiles in Military (the die has no spies and no tile swap).", None), ("Go back to Paul.", "That's playing like the crew!")], "Now you can play with us on Fridays."),
 'funcionaria_3': ("Everything in order", "A match with me, with the official rules. But without using the Wall: every tile in its proper place.", [("Beat Gertrud without putting any tile on the Wall.", None), ("Go back to Frau Hartmann.", "Impeccable. Not a single tile out of place.")], "I'll put it on record for you."),
 'espia_2': ("A game of spies", "Let's play my way: spies and tile swaps. Beat me, and with two spies on your board.", [("Beat Lotte with 2 tiles in your Spies enclosure (the die favors spies).", None), ("Go back to Lotte.", "Now you're thinking like me.")], "I'm going to need you soon."),
})
DES = {
 'periodista_4': ("Look at the board: I start ahead. A single round. Are you up for it?", "Plenty of stories got away from me too. Another one?", "", None),
 'obrero_3': ("Today we play it solid. Shall we?", "A building isn't finished in a day. Rematch?", "You won, but without soldiers in Military. That doesn't count, comrade.", "At least 2 tiles in Military"),
 'funcionaria_3': ("Rules are rules. Shall we begin?", "Losing is also a procedure. You may try again.", "You won, yes. But you used the Wall. That is not in order.", "No tiles on the Wall"),
 'espia_2': ("Cards on the table. Shall we play?", "I saw you coming from the first round. Again.", "You won. But without spies you learned nothing.", "Two tiles in Spies"),
}
por_mision={m['id']:m for m in mis['misiones']}
for idm,(titulo,oferta,pasos,final) in M.items():
    m=por_mision[idm]; par(m['titulo'],titulo); par(m['oferta'],oferta); par(m['final'],final)
    if len(pasos)!=len(m['pasos']): faltan_estructura.append((idm,'pasos'))
    for p,(tx,dl) in zip(m['pasos'],pasos):
        par(p['texto'],tx); par(p.get('dialogo'),dl)
for idm, (inv, per, falta, sec) in DES.items():
    for pa in por_mision[idm]['pasos']:
        if pa['tipo'] == 'desafio':
            par(pa['invitacion'], inv); par(pa['perdiste'], per); par(pa.get('faltaSecundario'), falta)
            if sec: par(pa['secundario']['texto'], sec)
# ---------- Contactos (rol, lo que hacen según la rutina, saludos) ----------
C = {
 'periodista': ("West Berlin journalist", ["Writing next to the Wall Museum", "Having lunch near Café Kreuzberg", "Writing next to the Wall Museum", "Interviewing people in front of the KaDeWe", "At home"], ["In this city everyone has a story. Most don't tell it.", "If you see anything strange near the Wall, let me know."]),
 'obrero': ("Construction worker", ["Working on the site next to the Centrum", "Checking the TV Tower site", "Working on the site next to the Centrum", "Having a beer at Café Friedrichshain", "Asleep at home"], ["The avenue doesn't build itself, comrade.", "Tomorrow we start early again."]),
 'funcionaria': ("Registry office official", ["Serving at the registry office", "Having lunch at Café Mitte", "Serving at the registry office", "At home"], ["Without the right form I can't do anything.", "Come back when everything is in order."]),
 'espia': ("Nobody really knows what she does", ["Watching people go by in front of the Reichstag", "Near the Wall, in Tiergarten", "Watching the Checkpoint Charlie crossing", "Nobody knows where"], ["Don't stare at me like that. Keep walking.", "I have nothing for you today."]),
}
for c in mis['contactos']:
    rol, acts, sal = C[c['id']]; par(c['rol'], rol)
    for r, a in zip(c['rutina'], acts): par(r['actividad'], a)
    for s, a in zip(c['saludos'], sal): par(s, a)
L_EXTRA = {'nacht_prologo': ("Viktor's box", 'Finish the prologue of Nacht über Berlin.'),
 'nacht_capitulo_1': ('Money Has No Homeland', 'Finish Chapter 1 of Nacht über Berlin.'), 'nacht_capitulo_2': ('The Price of the Border', 'Finish Chapter 2 of Nacht über Berlin.'), 'nacht_capitulo_3': ('The House of the Dead', 'Finish Chapter 3 of Nacht über Berlin.')}
L = {'primera_mision': ("First steps", "Complete your first mission."), 'cruzo_el_muro': ("On the other side", "Complete Lotte's errand."),
     'cronista': ("Berlin chronicler", "Help Ingrid publish the front-page story."), 'conocido': ("Known in Berlin", "Complete a mission for every contact."),
     'nivel_3': ("Lifelong neighbor", "Reach level 3."), 'campeon_torneo': ("Berlin champion", "Win a lightning tournament.")}
for l in mis['logros']:
    if l['id'] in {**L, **L_EXTRA}:   # los de los capítulos nuevos vienen en traducciones_capitulos.json
        n, d = {**L, **L_EXTRA}[l['id']]; par(l['nombre'], n); par(l['descripcion'], d)
S = {'control_checkpoint': ("Surprise check at Checkpoint Charlie", "The guards check papers one by one: crossing takes longer."),
     'torneo_cafe': ("Tournament night at the {distrito} café", "Matches played at that café count double influence."),
     'mercado': ("Street market in {distrito}", "Stalls with old books, records and postcards. Come take a look."),
     'apagon': ("Blackout in {distrito}", "The power is out in the whole district. At night you can't see a thing."),
     'manifestacion': ("Demonstration in {distrito}", "Hundreds of people march with signs. The police watch closely."),
     'concierto': ("Open-air concert in Tiergarten", "A band is playing in the park. Half of West Berlin is there."),
     'visita_oficial': ("Official visit in {distrito}", "A convoy of black cars crosses the city. Streets closed and heavy security.")}
for s in suc['tipos']:
    a, b = S[s['id']]; par(s['titulo'], a); par(s['descripcion'], b)
# ---------- Plantillas del código (diario, radio, avisos) ----------
for es, en in {
 'El Oeste se impone en {n} de los 12 distritos': 'The West prevails in {n} of the 12 districts', 'Los cafés del sector occidental no dan abasto.': 'The cafés of the western sector can barely keep up.',
 'Los trabajadores del Este sostienen {n} distritos': 'The workers of the East hold {n} districts', 'La planificación da sus frutos en cada café de la capital.': 'Planning bears fruit in every café of the capital.',
 'Preocupación: el Este controla {n} distritos': 'Concern: the East controls {n} districts', 'Se pide a los vecinos del Oeste que vuelvan a las mesas.': 'West Berliners are urged to return to the tables.',
 'Ofensiva occidental en {n} distritos: la capital responde': 'Western offensive in {n} districts: the capital responds', 'Los cafés del Este llaman a redoblar el esfuerzo.': 'The cafés of the East call for redoubled effort.',
 'Empate en la ciudad dividida: {n} distritos por lado': 'Deadlock in the divided city: {n} districts each', 'Ningún bando logra sacar ventaja.': 'Neither side manages to get ahead.',
 'Hoy': 'Today', 'Más tarde': 'Later', '{campeon} gana el torneo «{torneo}»': '{campeon} wins the “{torneo}” tournament', 'La Torre de TV transmitió la final.': 'The TV Tower broadcast the final.',
 'Figura del día: {jugador}, con {puntos} puntos': 'Player of the day: {jugador}, with {puntos} points', 'Pronóstico: {clima}': 'Forecast: {clima}', 'Quedan {dias} días de la temporada {n}': '{dias} days left in season {n}',
 'Berlín, {d} de {m} de {a}': 'Berlin, {m} {d}, {a}', 'Leer el diario del Este': "Read the East's newspaper", 'Leer el diario del Oeste': "Read the West's newspaper", 'Diario del día': "Today's newspaper",
 'enero': 'January', 'febrero': 'February', 'marzo': 'March', 'abril': 'April', 'mayo': 'May', 'junio': 'June', 'julio': 'July', 'agosto': 'August', 'septiembre': 'September', 'octubre': 'October', 'noviembre': 'November', 'diciembre': 'December',
 'Más tarde: {suceso}': 'Later: {suceso}', 'La ciudad: el Oeste domina {o} distritos y el Este {e}.': 'The city: the West holds {o} districts and the East {e}.',
 'Misión completada: {m} (+{xp} XP, +{p} puntos de tienda)': 'Mission completed: {m} (+{xp} XP, +{p} shop points)', 'Objetivo cumplido: {m}': 'Objective completed: {m}', 'Misión aceptada: {m}': 'Mission accepted: {m}',
 'Logro: {l}': 'Achievement: {l}', '¡Subiste al nivel {n}!': 'You reached level {n}!', '{n} no está: {a}. Vuelve a las {h}.': '{n} is not here: {a}. Back at {h}.', '{n} no está: {a}.': '{n} is not here: {a}.',
 'Dar una vuelta por el mercado': 'Take a look around the market', 'Noche de torneo: las partidas de este café valen doble influencia.': 'Tournament night: matches at this café count double influence.',
 'Ahora no hay nada para visitar.': 'There is nothing to visit right now.', 'Acercate a los puestos.': 'Get closer to the stalls.', 'Ya diste una vuelta por este mercado.': 'You already looked around this market.', 'Diste una vuelta por el mercado.': 'You looked around the market.',
 '¡Abran el paso!': 'Open the crossing!', '¡Libertad de prensa!': 'Freedom of the press!', '¡Que vuelvan las familias!': 'Bring the families back!', '¡Basta de controles!': 'No more checks!',
 'Esa respuesta no está disponible.': 'That answer is not available.', 'Objetivo extra: {o}': 'Extra goal: {o}', 'Jugar la partida': 'Play the match', 'Volver a la partida': 'Back to the match', 'Volver a Berlín': 'Back to Berlin', 'Ahora no tenés ese desafío pendiente.': "You don't have that challenge pending right now.", 'Terminá la partida en la que estás.': 'Finish the match you are in first.', 'Esa persona no existe.': 'That person does not exist.', 'Esa misión no existe.': 'That mission does not exist.', 'Cuartel': 'Barracks',
}.items(): par(es, en)
# ---------- Campaña: prólogo (las frases van en el orden en que aparecen en el guion) ----------
PROLOGO_EN = [
 "West Berlin · 1975",
 "This box came to me with no sender. Photos, clippings, a notebook... and one name that turns up everywhere: Viktor Adler.",
 "Some of the photos are from 1961, before the Wall. I want to know what happened to these people.",
 "Let's start with the first one. A wet street, an old car and two men inside.",
 "West Berlin · June 1961 · 11:47 p.m.",
 "You know what I hate most about this city?", "…", "That it always feels like something is about to happen.", "That's because something always does.",
 "No. I mean something big. Something that will change everything.", "Then let's hope it changes the price of beer too.",
 "Talk to Thomas", "You're late.", "And you're still unbearable.", "The envelope's on the table. Don't count it in front of me, it depresses me.",
 "Pick up the envelope from the table", "We're almost two hundred marks short. Where's the rest?", "Otto Weiss decided to delay the payment.",
 "That son of a bitch is playing us for fools.", "If you want to get paid, stop looking for Weiss.", "And who are you?", "Someone who knows where he is.",
 "Weiss has disappeared. But first he stole something that belongs to people far more dangerous than him. Tonight some documents are being moved in a suitcase.",
 "Not interested.", "Two thousand marks. Each.", "Now I'm interested.", "What does Viktor answer?", "Deal.", "Make it three thousand.",
 "I knew you were a reasonable man.", "Two thousand five hundred. And don't haggle with me again.", "I like her.",
 "First mission: The Last Job", "Two armed men by the car. This isn't a courier job.", "Go between the wagons and the crates. Don't let them see you.",
 "Reach the suitcase without being seen", "Take the suitcase", "Stop! Drop that!", "Viktor, take cover! I'll cover you!", "Take down the thugs or hold out for 5 rounds",
 "Agh! They got my arm!", "More are coming! I heard cars!", "To the car! Now!", "Run to the car!",
 "It's just a graze. Carry on, I'll bleed quietly.", "Open the suitcase", "Identity papers. Photos of missing people. Lists of payments. Train records...",
 "And that black notebook?", "Open the notebook", "VIKTOR ADLER — 17 NOVEMBER 1948", "…", "It's the day my brother disappeared.",
 "This stamp... it's from an East German security office.", "And what does that mean?", "That we've just stolen something we were never supposed to see.",
 "East Berlin · That same night", "…", "Find them.", "End of the prologue",
]
def frases_guion(pasos):
    for q in pasos:
        if q['tipo'] in ('dialogo', 'tarjeta', 'objetivo', 'interactuar', 'sigilo', 'persecucion', 'combate'):
            yield q['texto']
        elif q['tipo'] == 'elegir':
            yield q['pregunta']
            for o in q['opciones']:
                yield o['texto']
        elif q['tipo'] == 'si':
            yield from frases_guion(q['pasos'])
prologo = json.load(open('datos/campana/prologo.json', encoding='utf-8'))
es_prologo = list(frases_guion(prologo['pasos']))
if len(es_prologo) != len(PROLOGO_EN):
    faltan_estructura.append(('prologo', len(es_prologo), len(PROLOGO_EN)))
for a, b in zip(es_prologo, PROLOGO_EN):
    par(a, b)
par(prologo['titulo'], "Prologue: The Last Job")
par(prologo['aviso'], "Historical fiction. The characters and events are invented, but they take place in the real Berlin of the Cold War. Contains violence.")
par('Matón', 'Thug')
for es, en in {'Hablar con Thomas': 'Talk to Thomas', 'Agarrar el sobre': 'Pick up the envelope', 'Tomar la maleta': 'Take the suitcase',
 'Abrir la maleta': 'Open the suitcase', 'Abrir la libreta': 'Open the notebook',
 'Fuera de combate': 'Out of action', 'Ronda {n} de {m}': 'Round {n} of {m}', 'Tu turno': 'Your turn', 'Turno de los rivales': "Enemies' turn",
 'Cubrirse': 'Take cover', 'Terminar turno': 'End turn', 'Reintentar': 'Retry', 'Falló': 'Miss', '¡Lo lograste!': 'You made it!', 'Viktor cayó': 'Viktor is down',
 'No se pudo empezar el combate.': 'The fight could not start.',
 'Elegí un personaje: clic en una casilla azul para moverte, o en un rival para dispararle. C: cubrirse · T: terminar turno · Tab: cambiar de personaje':
   'Pick a character: click a blue square to move, or an enemy to shoot. C: take cover · T: end turn · Tab: switch character',
 'El combate ya terminó.': 'The fight is over.', 'Ese personaje no puede actuar ahora.': "That character can't act now.",
 'Ese personaje está fuera de combate.': 'That character is out of action.', 'Ese personaje ya se movió o actuó en este turno.': 'That character has already moved or acted this turn.',
 'No puede llegar hasta ahí.': "Can't reach that spot.", 'Ese personaje ya actuó en este turno.': 'That character has already acted this turn.',
 'Ese no es un blanco válido.': 'Not a valid target.', 'No tiene línea de vista.': 'No line of sight.', 'No es tu turno.': "It's not your turn.",
 'Primero empezá el combate.': 'Start the fight first.', 'Ese combate no existe o el capítulo no empezó.': "That fight doesn't exist or the chapter hasn't started.",
 'Primero hay que ganar el combate.': 'You have to win the fight first.', 'Acción desconocida.': 'Unknown action.'}.items():
    par(es, en)
for q in prologo['pasos']:
    if q.get('accion'): todas_acciones = True
for es, en in {'Capítulo 1: Líneas cruzadas': 'Chapter 1: Crossed Lines', 'Historia: Nacht über Berlin': 'Story: Nacht über Berlin', 'próximamente': 'coming soon', 'Jugar': 'Play',
 'Retomar': 'Resume', 'Empezar de nuevo': 'Start over', 'Volver a jugar': 'Play again', 'Empezar': 'Start', '¡Te vieron!': 'They saw you!', '¡Te alcanzaron!': 'They caught you!',
 'Ya habías terminado este capítulo. La historia sigue pronto.': 'You had already finished this chapter. The story continues soon.',
 'Ganaste {xp} XP y el logro «La caja de Viktor». La historia sigue pronto.': "You earned {xp} XP and the “Viktor's box” achievement. The story continues soon.",
 'No se pudo guardar el final.': 'The ending could not be saved.', 'Ese capítulo todavía no está disponible.': 'That chapter is not available yet.',
 'Fue demasiado rápido.': 'That was too fast.', 'Todavía no llegaste al final.': "You haven't reached the end yet.", 'Primero empezá el capítulo.': 'Start the chapter first.',
 'Ese punto de la historia no corresponde todavía.': "That point in the story doesn't apply yet.", 'Esa decisión no existe.': 'That decision does not exist.',
 'WASD o flechas para caminar · E para usar · Espacio para seguir los diálogos': 'WASD or arrows to walk · E to use · Space to continue dialogue',
 'Espacio, Enter o tocá para seguir': 'Space, Enter or tap to continue', 'Salir': 'Exit', 'La caja de Viktor': "Viktor's box"}.items():
    par(es, en)
# ---------- Campaña: capítulos (las frases en el orden de frases_capitulo) ----------
CAPITULOS_EN = {
 'capitulo_1': [
  "West Berlin · 1975",
  "The second photo is from a jazz club in Kreuzberg. On the back someone wrote: “Otto. July 1961”.",
  "Otto Weiss. His name is in the notebook, next to a cross.",
  "West Berlin · July 1961", "Talk to Anneliese",
  "These papers have a name. They call it the Nachtliste. The Night List.", "A list of what?",
  "Of people arrested, moved or disappeared after the war.", "All this trouble over a list of prisoners?",
  "It's worse than that. Some are listed as dead. Others as working for the government. And some get paid by Western banks.",
  "So it's not just an Eastern thing.", "Someone in the West is paying.", "Then let's sell part of it. Someone will pay well for this.",
  "What does Viktor do with the list?", "Let's sell part of it.", "No. First let's understand what we have.",
  "That's what I like to hear.", "Understanding doesn't put food on the table, Viktor.", "We'll need Otto anyway. He knows who wants it.",
  "Otto Weiss is hiding in a jazz club in Kreuzberg. The Silberfisch.", "Kreuzberg · Club Silberfisch",
  "Find Otto in the back room", "The table in the back is for players only.", "Then I'll play.", "Go in. And don't make trouble.",
  "Talk to Otto", "Viktor? What are you doing here? I don't know anything, I swear.", "You know enough to be hiding.",
  "At this table we don't talk. We play. Beat me and I'll tell you what I know.", "Beat Otto at his gambling table",
  "One round. If you win, I talk. If you lose, you leave and you never saw me.", "You lost, Viktor. One more and you're out.",
  "The game is still on the table. Finish it.", "All right, all right...",
  "I didn't know what I was carrying. They paid me to move papers, that's all.", "Your name is on the list, Otto. Why?",
  "Because your brother didn't die, Viktor.", "…", "I saw him three years ago.", "Where?", "Viktor! Are you all right?", "Otto...",
  "Someone is killing everyone who touched those papers.", "And we have them.", "End of Chapter 1",
 ],
 'capitulo_3': [
  "West Berlin · 1975",
  "The fourth photo is a door. A flat in Prenzlauer Berg. On the back, in another hand: “They did not disappear”.",
  "East Berlin · Prenzlauer Berg · August 1961 · 4:50 a.m.",
  "An official who worked with my father lived here. He left months ago and nobody knows where.",
  "Five minutes. If it gets light, they'll see us leave.",
  "Look at the photos on the wall", "Faces cut out. Someone took the people and left the frames.",
  "Check the burned papers", "They burned everything in a hurry. This is still warm.", "Then they were here not long ago.",
  "Check the locked door", "Leave it to me. And keep the torch steady.",
  "Help Thomas: press when the marker is in the light zone", "Done. Never fails.",
  "Hundreds of names. Some crossed out. Others with dates.", "“They did not disappear. They were moved”.",
  "…", "My father's name is here. With a date.", "And Martin's?", "I can't see it. But there are too many.",
  "Someone is opening the door downstairs.", "What does Viktor do?", "Out the window, now!", "Wait for Friedrich, who's still behind.",
  "Go! I'll catch up!", "Get out through the window!", "Friedrich, come on!", "Don't wait for me, you idiot! Go!", "Get out through the window!",
  "Don't move. Hands where I can see them.", "All right, all right... There's nobody else.", "We'll be the ones to decide that.",
  "And Friedrich?", "He was right behind me. I swear he was right behind me.", "I waited for him. It wasn't enough.", "I left him behind.",
  "I'm going to find him.", "One hour later", "…", "Check what was left on the floor",
  "An address. A time. And one sentence: “Come alone if you want to see him again”.", "Friedrich...", "End of Act I",
 ],
 'capitulo_2': [
  "West Berlin · 1975",
  "The third photo is a railway platform. Friedrichstraße, July 1961. On the back, a note: “Lindner. Freight track”.",
  "West Berlin · July 1961", "Talk to Anneliese",
  "Lindner, an old railwayman, got us papers. With these names we get through the Friedrichstraße checkpoint.",
  "Forged papers. If they catch us, we don't come back.", "That's why they won't catch us.",
  "On the freight track there's a wagon with a package. Lindner says it's Weiss's.",
  "And this time don't haggle, Viktor. Lindner charges what he asks and doesn't argue.",
  "Lindner trusts you because you kept your word with me. Don't ruin it.",
  "What we made from the list paid for the papers.", "If we'd sold something, we wouldn't be counting coins.",
  "Bahnhof Friedrichstraße · East Berlin · July 1961", "Go through the Transportpolizei checkpoint", "Get through the checkpoint without raising suspicion",
  "Come with me, please.", "Go ahead.",
  "Papers. Where are you going?", "To Pankow, to visit my aunt.", "To work. I'm a mechanic.", "Just around, for a walk.",
  "Are you carrying Western goods? Coffee, stockings, cigarettes...", "A pack of cigarettes, for my uncle.", "No, nothing.", "Why do you ask?",
  "This photo doesn't look much like you.", "I was younger. And I had more hair.", "It's an old photo.", "Say nothing and hold his gaze.",
  "When are you going back to the Western sector?", "Tonight.", "On Sunday, after Mass.", "I don't know, it depends.",
  "The wagon is on the freight track. I'll wait for you in the car.", "Reach the wagon without being seen", "Take the package", "Go back to the car",
  "Halt! That car! Papers!", "Get in! Quick!", "Escape from the Volkspolizei",
  "You hesitated a second too long and the Wartburg is right behind you.",
  "A Volkspolizei Wartburg pulls out behind you. Which way?",
  "Down the avenue, flat out", "On the straight, the old Mercedes still pulls: you gain a few metres.", "There's nowhere to hide on the avenue: the Wartburg closes in.",
  "Through the narrow back streets", "Two quick turns and they lose sight of you for a moment.", "A street closed for roadworks: you have to turn back.",
  "Thomas grabs the car radio: “I can get onto their frequency”.",
  "Let him try", "Thomas gives a false location over the police radio: the next junction will be clear.", "The radio shrieks and nothing makes sense. You lost time.",
  "No: tell him to hold on tight", "Viktor takes a sharp bend and gains ground.", "Viktor brakes late in the bend and you nearly crash.",
  "A tram is crossing ahead. The way is about to close.",
  "Get past before the tram", "You scrape past the tram. Behind you, the Wartburg has to brake.", "You don't make it: the tram forces you to stop dead.",
  "Turn right and go round the block", "You go round the block unseen.", "You go round the block... and the Wartburg was waiting on the other side.",
  "The sector boundary, ahead. A policeman raises his hand.",
  "Speed up and cross", "The policeman throws himself aside. You're in the West.", "A second Wartburg blocks your way: you have to turn back.",
  "Stop, show the forged papers and pray", "The policeman looks at the papers, looks at the car... and lets you through.", "The policeman hesitates and calls someone on the radio.",
  "We lost them!", "Welcome back to the West.", "Open the package", "Photos. The same woman, in all of them.", "…", "It's my mother.",
  "In one of the photos, the woman is next to a young man.", "It's Martin. My brother.",
  "Your brother and Anneliese's mother? What the hell is this?", "I don't know. But this isn't just your story anymore, Viktor.", "End of Chapter 2",
 ],
}
def frases_capitulo(pasos):
    for q in pasos:
        t = q['tipo']
        if t in ('dialogo', 'tarjeta', 'objetivo', 'interactuar', 'sigilo', 'persecucion', 'combate', 'cerradura', 'seguir'): yield q['texto']
        elif t == 'elegir':
            yield q['pregunta']
            for o in q['opciones']: yield o['texto']
        elif t == 'si': yield from frases_capitulo(q['pasos'])
        elif t == 'partida':
            for k in ('texto', 'invitacion', 'perdiste', 'enJuego'): yield q[k]
        elif t == 'interrogatorio':
            for k in ('texto', 'detenido', 'pase', 'medidor', 'falla'):
                if k in q: yield q[k]
            for pr in q['preguntas']:
                yield pr['texto']
                for o in pr['opciones']: yield o['texto']
        elif t == 'paseo':
            yield q['texto']
            for pt in q['puntos']: yield from frases_capitulo(pt['pasos'])
        elif t == 'agujas':
            yield q['texto']
            if q.get('acoplada'): yield q['acoplada']
            for v in q['vias'].values(): yield v
        elif t == 'persecucion_auto':
            for k in ('texto', 'sinDecidir'): yield q[k]
            for tr in q['tramos']:
                yield tr['texto']
                for o in tr['opciones']:
                    for k in ('texto', 'bien', 'mal'): yield o[k]
# Capítulos escritos con su traducción al lado (herramientas/escribir_capitulos_*.py -> traducciones_capitulos.json)
import os
_ruta = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'traducciones_capitulos.json')
for a, b in (json.load(open(_ruta, encoding='utf-8')) if os.path.exists(_ruta) else {}).items():
    par(a, b)
_ruta_ui = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'traducciones_interfaz.json')   # textos de la interfaz (página Personalizar, perfil)
for a, b in (json.load(open(_ruta_ui, encoding='utf-8')) if os.path.exists(_ruta_ui) else {}).items():
    par(a, b)
es_capitulos = []
for _cap in ('capitulo_4', 'capitulo_5', 'capitulo_6', 'capitulo_7', 'capitulo_8', 'capitulo_9', 'capitulo_10', 'capitulo_11', 'capitulo_12', 'capitulo_13', 'capitulo_14', 'capitulo_15', 'capitulo_16', 'capitulo_17', 'capitulo_18', 'capitulo_19'):
    _g = json.load(open('datos/campana/' + _cap + '.json', encoding='utf-8'))
    es_capitulos += list(frases_capitulo(_g['pasos'])) + [q['accion'] for q in _g['pasos'] if q.get('accion')]
for cap, en in CAPITULOS_EN.items():
    g = json.load(open('datos/campana/' + cap + '.json', encoding='utf-8'))
    es = list(frases_capitulo(g['pasos']))
    if len(es) != len(en): faltan_estructura.append((cap, len(es), len(en)))
    for a, b in zip(es, en): par(a, b)
    es_capitulos += es + [q['accion'] for q in g['pasos'] if q.get('accion')]
for es, en in {'Capítulo 1: El dinero no tiene patria': 'Chapter 1: Money Has No Homeland', 'Capítulo 2: El precio de la frontera': 'Chapter 2: The Price of the Border',
 'Capítulo 3: La casa de los muertos': 'Chapter 3: The House of the Dead',
 'Ficción histórica. Los personajes y la trama son inventados; el contexto histórico es real. Contiene violencia.': 'Historical fiction. The characters and plot are invented; the historical context is real. Contains violence.',
 'Hablar con Anneliese': 'Talk to Anneliese', 'Hablar con el portero': 'Talk to the doorman', 'Hablar con Otto': 'Talk to Otto', 'Mostrar los papeles': 'Show your papers',
 'Tomar el paquete': 'Take the package', 'Abrir el paquete': 'Open the package', 'Transportpolizei': 'Transportpolizei', 'Cliente': 'Patron',
 'Sentarse a jugar': 'Sit down and play', 'Sospecha: {s} de {l}': 'Suspicion: {s} of {l}', 'Tardaste en contestar': 'You took too long to answer',
 'Te detuvieron. Probá de nuevo.': 'You were arrested. Try again.', 'Ventaja: {v}': 'Lead: {v}', 'Los alcanzaron. Probá de nuevo.': 'They caught you. Try again.',
 'Ya habías terminado este capítulo.': 'You had already finished this chapter.', 'Ganaste {xp} XP y el logro «{logro}».': 'You earned {xp} XP and the “{logro}” achievement.',
 'Seguir: {titulo}': 'Continue: {titulo}', 'La historia sigue pronto.': 'The story continues soon.', 'Volver a la historia': 'Back to the story',
 'Esa partida no existe o el capítulo no empezó.': "That match doesn't exist or the chapter hasn't started.", 'Esa partida no existe.': "That match doesn't exist.",
 'Ese interrogatorio no existe o el capítulo no empezó.': "That interrogation doesn't exist or the chapter hasn't started.", 'Faltan respuestas.': 'Some answers are missing.',
 'Esa persecución no existe o el capítulo no empezó.': "That chase doesn't exist or the chapter hasn't started.", 'Primero empezá la persecución.': 'Start the chase first.',
 'La persecución ya terminó.': 'The chase is over.', 'Esa opción no existe.': "That option doesn't exist.", 'Primero hay que superar esa parte de la historia.': 'You have to get through that part of the story first.', 'Primero terminá el capítulo anterior.': 'Finish the previous chapter first.', 'Capítulo 4: Un hombre en una habitación': 'Chapter 4: A Man in a Room', 'Hombre de civil': 'Plainclothesman', 'Mirar las fotos': 'Look at the photos', 'Revisar los papeles quemados': 'Check the burned papers', 'Revisar la puerta': 'Check the door', 'Levantar el papel': 'Pick up the paper', 'Perno {n} de {m}': 'Pin {n} of {m}', 'Ahora': 'Now', 'Se resbaló la ganzúa': 'The pick slipped', 'Terminá el anterior': 'Finish the previous one', 'No se pudo empezar.': 'Could not start.'}.items():
    par(es, en)
p='Capa_Presentacion/lang/en.json'; d=json.load(open(p,encoding='utf-8'))
d.update(EN)   # las de contenido reemplazan versiones viejas si las hubiera
json.dump(d,open(p,'w',encoding='utf-8'),ensure_ascii=False,indent=2)
# ---------- Verificación: ninguna frase de contenido sin traducir ----------
todas=set()
for c,a in dlg.items():
    if c=='_leeme': continue
    for n in a['nodos'].values():
        todas.add(n['texto']); todas.update(n.get('textoPorAnimo',{}).values()); todas.update(o['texto'] for o in n['opciones'])
for m in mis['misiones']:
    todas.update([m['titulo'],m['oferta'],m['final']])
    for pa in m['pasos']:
        todas.add(pa['texto']); todas.add(pa.get('dialogo') or m['titulo'])
        for k in ('invitacion', 'perdiste', 'faltaSecundario'):
            if pa.get(k): todas.add(pa[k])
        if pa.get('secundario', {}).get('texto'): todas.add(pa['secundario']['texto'])
for c in mis['contactos']:
    todas.add(c['rol']); todas.update(r['actividad'] for r in c['rutina']); todas.update(c['saludos'])
for l in mis['logros']: todas.update([l['nombre'],l['descripcion']])
for s in suc['tipos']: todas.update([s['titulo'],s['descripcion']])
todas.update(es_prologo)
todas.update(es_capitulos)
todas.update(q['accion'] for q in prologo['pasos'] if q.get('accion'))
sin=[x for x in todas if x not in d]
print('traducciones de contenido:', len(EN), '| sin traducir:', len(sin), sin[:5], '| problemas de estructura:', faltan_estructura)

# Al final, el alemán: lang/de.json con las mismas claves (ver herramientas/traducir_aleman.py)
import sys as _sys
_sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))
from traducir_aleman import generar as _aleman
_aleman()
