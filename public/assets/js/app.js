import { loadChallenge } from './modules/game.js';

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

// Lanzamos la carga inicial.
init();
