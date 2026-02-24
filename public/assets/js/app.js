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
    if (!hint) {
        ocultarPistas();
        return;
    }

    // mostramos las pistas recibidas en el elemento html con id "hint"
    const generation = hint.generacion ?? 'Desconocida';
    const types = Array.isArray(hint.tipo) && hint.tipo.length > 0 ? hint.tipo.join(', ') : 'Desconocido';
    const color = hint.color || 'Desconocido';
    const height = hint.altura !== null && hint.altura !== undefined ? `${hint.altura} m` : 'Desconocida';
    const weight = hint.peso !== null && hint.peso !== undefined ? `${hint.peso} kg` : 'Desconocido';

    hintNode.innerHTML = `
        <h3>Pista</h3>
        <ul>
            <li><strong>Generaci&oacute;n:</strong> ${generation}</li>
            <li><strong>Tipo:</strong> ${types}</li>
            <li><strong>Color:</strong> ${color}</li>
            <li><strong>Altura:</strong> ${height}</li>
            <li><strong>Peso:</strong> ${weight}</li>
        </ul>
    `;

    hintNode.hidden = false;
}

function ocultarPistas() {
    hintNode.hidden = true;
    hintNode.innerHTML = '';
}

// Lanzamos la carga inicial.
init();
