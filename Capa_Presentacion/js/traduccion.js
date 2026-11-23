// (el nombre quedó de cuando solo había inglés: traduce al idioma que se le pasa, 'en' o 'de')
async function aplicarTraduccionIngles(idioma) {
    try {
        const respuesta = await fetch('./lang/' + (idioma || 'en') + '.json');
        if (!respuesta.ok) return;

        const diccionario = await respuesta.json();

        // Traduce textos visibles
        const caminador = document.createTreeWalker(
            document.body,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );

        let nodo;
        while ((nodo = caminador.nextNode())) {
            const textoLimpio = nodo.nodeValue.trim();
            if (diccionario[textoLimpio]) {
                nodo.nodeValue = nodo.nodeValue.replace(textoLimpio, diccionario[textoLimpio]);
            }
        }

        // Traduce placeholders en inputs y textareas
        document.querySelectorAll('input[placeholder], textarea[placeholder]').forEach(el => {
            const phLimpio = el.placeholder.trim();
            if (diccionario[phLimpio]) {
                el.placeholder = diccionario[phLimpio];
            }
        });
    } catch (error) {
        console.error('Error al cargar la traducción:', error);
    }
}