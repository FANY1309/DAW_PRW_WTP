import { loadChallenge, loadPokemonList, submitAttempt } from './modules/game.js';

const dateNode = document.getElementById('challenge-date');
const resultNode = document.getElementById('result');
const hintNode = document.getElementById('hint');
const debugNode = document.getElementById('debug');
const form = document.getElementById('guess-form');
const pokemonSearch = document.getElementById('pokemon-search');
const suggestionsNode = document.getElementById('pokemon-suggestions');

// Cache local con los nombres que llegan del backend (evita pedirlos en cada tecla)
let allPokemons = [];

// Esta función corre cuando carga la página.
async function iniciar() {
    const pokemonListResponse = await loadPokemonList();

    if (!pokemonListResponse.ok || !pokemonListResponse.data.ok || !Array.isArray(pokemonListResponse.data.items)) {
        resultNode.textContent = 'No se pudo cargar la lista de pokemon.';
        resultNode.classList.add('error');
        resultNode.classList.remove('success');
    } else {
        allPokemons = sanitizarListaPokemon(pokemonListResponse.data.items);
        vincularBusquedaPokemon();
    }

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
    const pokemonName = pokemonSearch.value.trim();

    // Si está vacío, avisamos y no seguimos.
    if (pokemonName === '') {
        resultNode.textContent = 'Debes escribir un pokemon.';
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
        pokemonSearch.value = '';
        ocultarSugerencias();
        ocultarPistas();
    } else {
        resultNode.classList.add('error');
        resultNode.classList.remove('success');
        mostrarPistas(response.data.pista);
    }
});

function vincularBusquedaPokemon() {
    if (!pokemonSearch || !suggestionsNode) {
        return;
    }

    // Cada letra vuelve a filtrar y renderizar la lista de sugerencias
    pokemonSearch.addEventListener('input', function () {
        const query = pokemonSearch.value.trim();
        const filtered = filtrarYOrdenarPokemones(query, allPokemons).slice(0, 25);
        renderizarSugerencias(filtered);
    });

    // Click fuera del formulario: cerrar el panel para no dejarlo abierto.
    document.addEventListener('click', function (event) {
        if (!form.contains(event.target)) {
            ocultarSugerencias();
        }
    });
}

function renderizarSugerencias(items) {
    if (!suggestionsNode) {
        return;
    }

    if (items.length === 0 || pokemonSearch.value.trim() === '') {
        ocultarSugerencias();
        return;
    }

    // Se escapan textos/atributos para evitar inyectar HTML desde datos externos.
    let html = '';

    for (let i = 0; i < items.length; i++) {
        const pokemon = items[i];
        html += `<li class="pokemon-suggestion-item" data-name="${escaparAtributoHtml(pokemon.nombre)}">${escaparTextoHtml(pokemon.nombre)}</li>`;
    }

    suggestionsNode.innerHTML = html;
    suggestionsNode.hidden = false;

    const suggestionItems = suggestionsNode.querySelectorAll('.pokemon-suggestion-item');
    for (let i = 0; i < suggestionItems.length; i++) {
        // mousedown evita que el blur del input cierre la lista antes de seleccionar.
        suggestionItems[i].addEventListener('mousedown', function (event) {
            event.preventDefault();
            const name = suggestionItems[i].getAttribute('data-name') || '';
            pokemonSearch.value = name;
            ocultarSugerencias();
        });
    }
}

function ocultarSugerencias() {
    if (!suggestionsNode) {
        return;
    }

    suggestionsNode.innerHTML = '';
    suggestionsNode.hidden = true;
}

function sanitizarListaPokemon(rawItems) {
    const output = [];

    for (let i = 0; i < rawItems.length; i++) {
        const raw = rawItems[i];
        const nombre = raw && raw.nombre !== undefined && raw.nombre !== null ? String(raw.nombre).trim() : '';

        if (nombre !== '') {
            output.push({ nombre });
        }
    }

    return output;
}

function filtrarYOrdenarPokemones(query, pokemons) {
    if (!query) {
        return [];
    }

    // Prioridad de coincidencias: empieza por texto > contiene texto > similitud por subsecuencia.
    const normalizedQuery = normalizarTexto(query);
    const startsWith = [];
    const includes = [];
    const similar = [];

    for (let i = 0; i < pokemons.length; i++) {
        const pokemon = pokemons[i];
        const normalizedName = normalizarTexto(pokemon.nombre);

        if (normalizedName.startsWith(normalizedQuery)) {
            startsWith.push(pokemon);
            continue;
        }

        if (normalizedName.includes(normalizedQuery)) {
            includes.push(pokemon);
            continue;
        }

        if (esSubsecuencia(normalizedQuery, normalizedName)) {
            similar.push(pokemon);
        }
    }

    return startsWith.concat(includes, similar);
}

function normalizarTexto(text) {
    // Normaliza para comparar sin depender de mayúsculas o acentos.
    return String(text)
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
}

function esSubsecuencia(query, target) {
    // Similitud básica: "pkc" coincide con "pikachu" por orden de caracteres.
    if (query.length === 0) {
        return true;
    }

    let q = 0;

    for (let t = 0; t < target.length; t++) {
        if (target[t] === query[q]) {
            q++;
            if (q === query.length) {
                return true;
            }
        }
    }

    return false;
}

function escaparTextoHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function escaparAtributoHtml(value) {
    return escaparTextoHtml(value);
}

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
    let htmlLista = '';

    for (let i = 0; i < listaPistas.length; i++) {
        htmlLista = htmlLista + listaPistas[i];
    }

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

iniciar();
