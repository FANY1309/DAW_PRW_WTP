import { loadChallenge, submitAttempt } from './modules/game.js';

// Buscamos en el HTML los elementos que vamos a usar.
const dateNode = document.getElementById('challenge-date');
const resultNode = document.getElementById('result');
const hintNode = document.getElementById('hint');
// TODO: borrar el elemento debug
const debugNode = document.getElementById('debug');
const form = document.getElementById('guess-form');
const input = document.getElementById('pokemon-name');

// Esta función corre cuando carga la página.
async function init() {
    // Pedimos al backend el reto del día.
    const response = await loadChallenge();

    // Si algo falla, mostramos un mensaje y paramos aquí.
    if (!response.ok || !response.data.ok) {
        dateNode.textContent = 'No hay reto activo cargado en la BD.';
        debugNode.textContent = JSON.stringify(response.data, null, 2);
        ocultarPistas();
        return;
    }

    // Si todo salió bien, mostramos la fecha del reto.
    dateNode.textContent = 'Reto activo: ' + response.data.fecha;

    // Mostramos la respuesta completa en modo debug.
    debugNode.textContent = JSON.stringify(response.data, null, 2);
    ocultarPistas();
}

// Esto se ejecuta cuando el usuario envía el formulario.
form.addEventListener('submit', async function (event) {
    // Evita que la página se recargue.
    event.preventDefault();

    // Tomamos el texto escrito y quitamos espacios al inicio/fin.
    const pokemonName = input.value.trim();

    // Si está vacío, avisamos y no seguimos.
    if (pokemonName === '') {
        resultNode.textContent = 'Debes escribir un nombre.';
        resultNode.classList.add('error');
        resultNode.classList.remove('success');
        ocultarPistas();
        return;
    }

    // Enviamos el intento al backend.
    const response = await submitAttempt(pokemonName);

    // Mostramos la respuesta completa en modo debug.
    debugNode.textContent = JSON.stringify(response.data, null, 2);

    // Si hay error, mostramos mensaje de error.
    if (!response.ok || !response.data.ok) {
        resultNode.textContent = response.data.message || 'Error en el intento.';
        resultNode.classList.add('error');
        resultNode.classList.remove('success');
        ocultarPistas();
        return;
    }

    // Si no hay error, mostramos el mensaje que venga del backend.
    resultNode.textContent = response.data.message;

    // Si adivinó, pintamos éxito y limpiamos el input.
    // Si no adivinó, pintamos error.
    if (response.data.correcto) {
        resultNode.classList.add('success');
        resultNode.classList.remove('error');
        input.value = '';
        ocultarPistas();
    } else {
        resultNode.classList.add('error');
        resultNode.classList.remove('success');
        mostrarPistas(response.data.pista);
    }
});

// funcion para mostrar las pistas del pokemon en el html
function mostrarPistas(hint) {
    // comprobamos que existen pistas que mostrar
    if (!hint || !hint.datos) {
        ocultarPistas();
        return;
    }

    // "hint.datos" trae las pistas desbloqueadas según el numero de fallos
    // Por eso preguntamos una por una si existe cada campo
    const listaPistas = [];
    const data = hint.datos;

    if (data.generacion !== undefined) {
        const generation = data.generacion ?? 'Desconocida';
        listaPistas.push(`<li><strong>Generación:</strong> ${generation}</li>`);
    }

    if (data.tipo !== undefined) {
        const types = Array.isArray(data.tipo) && data.tipo.length > 0 ? data.tipo.join(', ') : 'Desconocido';
        listaPistas.push(`<li><strong>Tipo:</strong> ${types}</li>`);
    }

    if (data.color !== undefined) {
        const color = data.color || 'Desconocido';
        listaPistas.push(`<li><strong>Color:</strong> ${color}</li>`);
    }

    if (data.altura !== undefined) {
        const height = data.altura !== null && data.altura !== undefined ? `${data.altura} m` : 'Desconocida';
        listaPistas.push(`<li><strong>Altura:</strong> ${height}</li>`);
    }

    if (data.peso !== undefined) {
        const weight = data.peso !== null && data.peso !== undefined ? `${data.peso} kg` : 'Desconocido';
        listaPistas.push(`<li><strong>Peso:</strong> ${weight}</li>`);
    }

    let outputHtml = '';
    if (data.silueta !== undefined && data.silueta) {
        // Esta imagen no se muestra normal: se pinta en negro por CSS para que sea silueta
        outputHtml = `
            <div class="hint-silhouette-wrap">
                <p><strong>Silueta:</strong></p>
                <img class="hint-silhouette" src="${data.silueta}" alt="Silueta del Pokemon" loading="lazy">
            </div>
        `;
    }

    // Si no hay pistas de texto todavía, ocultamos el bloque para no dejar basura visual
    if (listaPistas.length === 0) {
        ocultarPistas();
        return;
    }

    const failedText = hint.intentosFallidos ? `Intento fallido #${hint.intentosFallidos}` : 'Pista';
    let htmlLista = '<ul>';

    for (let i = 0; i < listaPistas.length; i++) {
        htmlLista = htmlLista + listaPistas[i];
    }

    htmlLista = htmlLista + '</ul>';
    hintNode.innerHTML = `
        <h3>${failedText}</h3>
        <ul>
            ${htmlLista}
        </ul>
        ${outputHtml}
    `;

    hintNode.hidden = false;
}

function ocultarPistas() {
    hintNode.hidden = true;
    hintNode.innerHTML = '';
}

// Lanzamos la carga inicial.
init();
