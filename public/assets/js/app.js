import { loadChallenge, submitAttempt } from './modules/game.js';

// Buscamos en el HTML los elementos que vamos a usar.
const dateNode = document.getElementById('challenge-date');
const resultNode = document.getElementById('result');
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
        return;
    }

    // Si todo salió bien, mostramos la fecha del reto.
    dateNode.textContent = 'Reto activo: ' + response.data.fecha;

    // Mostramos la respuesta completa en modo debug.
    debugNode.textContent = JSON.stringify(response.data, null, 2);
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
    } else {
        resultNode.classList.add('error');
        resultNode.classList.remove('success');
    }
});

// Lanzamos la carga inicial.
init();
