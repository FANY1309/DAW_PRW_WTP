import {
    loadChallenge,
    loadPokemonList,
    loadGlobalRanking,
    submitAttempt,
    getCurrentUser,
    loginUser,
    logoutUser
} from './modules/game.js';

const dateNode = document.getElementById('challenge-date');
const resultNode = document.getElementById('result');
const pointsNode = document.getElementById('points');
const hintNode = document.getElementById('hint');
const debugNode = document.getElementById('debug');
const form = document.getElementById('guess-form');
const pokemonSearch = document.getElementById('pokemon-search');
const suggestionsNode = document.getElementById('pokemon-suggestions');
const submitButton = form ? form.querySelector('button[type="submit"]') : null;

const showLoginButton = document.getElementById('show-login-button');
const closeLoginButton = document.getElementById('close-login-button');
const loginModal = document.getElementById('login-modal');
const loginForm = document.getElementById('login-form');
const loginIdentifier = document.getElementById('login-identifier');
const loginPassword = document.getElementById('login-password');
const logoutButton = document.getElementById('logout-button');
const showRankingButton = document.getElementById('show-ranking-button');
const rankingModal = document.getElementById('ranking-modal');
const closeRankingButton = document.getElementById('close-ranking-button');
const rankingBody = document.getElementById('ranking-body');
const rankingMeNode = document.getElementById('ranking-me');
const authStatusNode = document.getElementById('auth-status');

let allPokemons = [];
let userSession = null;

async function iniciar() {
    vincularBusquedaPokemon();
    vincularEventosAuth();
    vincularEventosRanking();
    await refrescarSesionJuego();
}

async function refrescarSesionJuego() {
    // Este flujo unifica el estado de autenticacion (invitado o usuario logueado)
    // antes de recargar el reto y la lista de pokemon, para mantener la UI consistente.
    const sessionResponse = await getCurrentUser();
    const isAuthenticated = Boolean(
        sessionResponse.ok &&
        sessionResponse.data &&
        sessionResponse.data.ok &&
        sessionResponse.data.authenticated === true
    );

    if (!isAuthenticated) {
        userSession = null;
        mostrarEstadoInvitado();
    } else {
        userSession = sessionResponse.data.user;
        mostrarEstadoUsuarioAuth();
    }

    await cargarDatosJuego();
}

async function cargarDatosJuego() {
    const pokemonListResponse = await loadPokemonList();

    if (!pokemonListResponse.ok || !pokemonListResponse.data.ok || !Array.isArray(pokemonListResponse.data.items)) {
        resultNode.textContent = 'No se pudo cargar la lista de pokemon.';
        resultNode.classList.add('error');
        resultNode.classList.remove('success');
        ocultarPuntos();
        return;
    }

    allPokemons = sanitizarListaPokemon(pokemonListResponse.data.items);

    // Pedimos al backend el reto del día
    const response = await loadChallenge();

    // Si algo falla, mostramos un mensaje y paramos aquí
    if (!response.ok || !response.data.ok) {
        dateNode.textContent = response.data && response.data.message ? response.data.message : 'No hay reto activo cargado en la BD.';
        debugNode.textContent = JSON.stringify(response.data, null, 2);
        ocultarPistas();
        ocultarPuntos();
        bloquearEnvio();
        return;
    }

    // Si todo salió bien, mostramos la fecha del reto.
    dateNode.textContent = 'Reto activo: ' + response.data.fecha;
    // Si ya fue resuelto (usuario o invitado), bloqueamos intentos y mostramos mensaje + puntos.
    if (response.data.alreadySolved) {
        bloquearEnvio();
        resultNode.textContent = response.data.alreadySolvedMessage || 'Ya acertaste el reto de hoy.';
        resultNode.classList.add('success');
        resultNode.classList.remove('error');
        mostrarPuntos(response.data.alreadySolvedPoints, response.data.alreadySolvedFailedAttempts);
    } else {
        desbloquearEnvio();
        resultNode.textContent = '';
        resultNode.classList.remove('error');
        resultNode.classList.remove('success');
        ocultarPuntos();
    }
    // Mostramos la respuesta completa en modo debug.
    debugNode.textContent = JSON.stringify(response.data, null, 2);
    ocultarPistas();
}

// Vincula eventos de login/logout y sincroniza estado de sesión + juego tras cada accion
function vincularEventosAuth() {
    if (showLoginButton) {
        showLoginButton.addEventListener('click', function () {
            abrirVentanaLogin();
        });
    }

    if (closeLoginButton) {
        closeLoginButton.addEventListener('click', function () {
            cerrarVentanaLogin();
        });
    }

    if (loginModal) {
        loginModal.addEventListener('click', function (event) {
            if (event.target === loginModal) {
                cerrarVentanaLogin();
            }
        });
    }

    if (loginForm) {
        loginForm.addEventListener('submit', async function (event) {
            event.preventDefault();

            const identifier = loginIdentifier ? loginIdentifier.value.trim() : '';
            const password = loginPassword ? loginPassword.value : '';

            if (identifier === '' || password === '') {
                setAuthStatus('Debes ingresar usuario/email y contraseña.', true);
                return;
            }

            const response = await loginUser(identifier, password);
            debugNode.textContent = JSON.stringify(response.data, null, 2);

            if (!response.ok || !response.data.ok) {
                setAuthStatus(response.data.message || 'No se pudo iniciar sesión.', true);
                return;
            }

            setAuthStatus('Sesión iniciada.', false);
            if (loginPassword) {
                loginPassword.value = '';
            }

            cerrarVentanaLogin();
            await refrescarSesionJuego();
        });
    }

    if (logoutButton) {
        logoutButton.addEventListener('click', async function () {
            const response = await logoutUser();
            debugNode.textContent = JSON.stringify(response.data, null, 2);
            cerrarRanking();
            await refrescarSesionJuego();
        });
    }
}

// Vincula eventos para mostrar/ocultar el ranking global, y para cargar el ranking al abrirlo
function vincularEventosRanking() {
    if (showRankingButton) {
        showRankingButton.addEventListener('click', async function () {
            await abrirRanking();
        });
    }

    if (closeRankingButton) {
        closeRankingButton.addEventListener('click', function () {
            cerrarRanking();
        });
    }

    if (rankingModal) {
        rankingModal.addEventListener('click', function (event) {
            if (event.target === rankingModal) {
                cerrarRanking();
            }
        });
    }
}

form.addEventListener('submit', async function (event) {
    // Evita que la página se recargue.
    event.preventDefault();

    const pokemonName = pokemonSearch.value.trim();

    // Si está vacío, avisamos y no seguimos
    if (pokemonName === '') {
        resultNode.textContent = 'Debes escribir un pokemon.';
        resultNode.classList.add('error');
        resultNode.classList.remove('success');
        ocultarPistas();
        ocultarPuntos();
        return;
    }

    // Enviamos el intento al backend
    const response = await submitAttempt(pokemonName);

    // Mostramos la respuesta completa en modo debug
    debugNode.textContent = JSON.stringify(response.data, null, 2);

    // Si hay error, mostramos mensaje de error
    if (!response.ok || !response.data.ok) {
        resultNode.textContent = response.data.message || 'Error en el intento.';
        // ya resuelto = lo tratamos como estado exitoso bloqueado
        if (response.data && response.data.alreadySolved) {
            resultNode.classList.add('success');
            resultNode.classList.remove('error');
            mostrarPuntos(response.data.puntos, response.data.intentosFallidosAntesDelAcierto);
            bloquearEnvio();
        } else {
            resultNode.classList.add('error');
            resultNode.classList.remove('success');
            ocultarPuntos();
        }
        ocultarPistas();
        return;
    }

    // Si no hay error, mostramos el mensaje que venga del backend
    resultNode.textContent = response.data.message;

    // Si adivinó, pintamos éxito y limpiamos el input
    // Si no adivinó, pintamos error
    if (response.data.correcto) {
        resultNode.classList.add('success');
        resultNode.classList.remove('error');
        pokemonSearch.value = '';
        ocultarSugerencias();
        ocultarPistas();
        mostrarPuntos(response.data.puntos, response.data.intentosFallidosAntesDelAcierto);
    } else {
        resultNode.classList.add('error');
        resultNode.classList.remove('success');
        mostrarPistas(response.data.pista);
        ocultarPuntos();
    }
});

// renderiza la vista para usuario no autenticado (invitado)
function mostrarEstadoInvitado() {
    if (showLoginButton) {
        showLoginButton.hidden = false;
    }
    if (loginModal) {
        loginModal.hidden = true;
    }
    if (logoutButton) {
        logoutButton.hidden = true;
    }
    if (showRankingButton) {
        showRankingButton.hidden = true;
    }
    if (loginIdentifier) {
        loginIdentifier.disabled = false;
    }
    if (loginPassword) {
        loginPassword.disabled = false;
    }

    cerrarRanking();
    dateNode.textContent = 'Cargando reto...';
    resultNode.textContent = '';
    resultNode.classList.remove('error');
    resultNode.classList.remove('success');
    debugNode.textContent = '';
    ocultarPistas();
    ocultarPuntos();
    desbloquearEnvio();
    setAuthStatus('Modo invitado: puedes jugar, pero no se guardan partidas.', false);
}

// renderiza la vista para usuario autenticado
function mostrarEstadoUsuarioAuth() {
    if (showLoginButton) {
        showLoginButton.hidden = true;
    }
    if (loginModal) {
        loginModal.hidden = true;
    }
    if (logoutButton) {
        logoutButton.hidden = false;
    }
    if (showRankingButton) {
        showRankingButton.hidden = false;
    }

    setAuthStatus('Sesión activa: ' + (userSession.nombre || userSession.usuario) + '. Tus partidas se guardan.', false);
}

// Muestra un mensaje de estado de autenticación
function setAuthStatus(message, isError) {
    if (!authStatusNode) {
        return;
    }

    authStatusNode.textContent = message;
    authStatusNode.classList.toggle('error', Boolean(isError));
}

function abrirVentanaLogin() {
    if (!loginModal) {
        return;
    }

    loginModal.hidden = false;

    if (loginIdentifier) {
        loginIdentifier.focus();
    }
}

function cerrarVentanaLogin() {
    if (!loginModal) {
        return;
    }

    loginModal.hidden = true;
}

// Abre el modal de ranking global y carga los datos desde el backend para mostrarlo
async function abrirRanking() {
    if (!rankingModal) {
        return;
    }

    rankingModal.hidden = false;
    await cargarRankingGlobal();
}

// Cierra el modal de ranking global y limpia su contenido para no mostrar datos viejos al reabrirlo
function cerrarRanking() {
    if (!rankingModal) {
        return;
    }

    rankingModal.hidden = true;
}

// Pide al backend el ranking global y lo muestra, junto con la posición del usuario autenticado si está disponible
async function cargarRankingGlobal() {
    if (!rankingBody || !rankingMeNode) {
        return;
    }

    rankingBody.innerHTML = '<tr><td colspan="4">Cargando ranking...</td></tr>';
    rankingMeNode.textContent = '';

    const response = await loadGlobalRanking();
    if (!response.ok || !response.data.ok) {
        rankingBody.innerHTML = '<tr><td colspan="4">No se pudo cargar el ranking.</td></tr>';
        rankingMeNode.textContent = response.data && response.data.message
            ? response.data.message
            : 'No se pudo obtener tu posición.';
        return;
    }

    renderizarRanking(response.data.items || []);
    renderizarMiPosicion(response.data);
}

// Muestra la tabla de ranking global con los datos obtenidos del backend
function renderizarRanking(items) {
    if (!rankingBody) {
        return;
    }

    if (!Array.isArray(items) || items.length === 0) {
        rankingBody.innerHTML = '<tr><td colspan="4">Aún no hay usuarios rankeados.</td></tr>';
        return;
    }

    let html = '';

    for (let i = 0; i < items.length; i++) {
        const item = items[i];
        const jugador = item.nombre && String(item.nombre).trim() !== '' ? item.nombre : item.usuario;
        html += `
            <tr>
                <td>${Number(item.posicion) || i + 1}</td>
                <td>${escaparTextoHtml(jugador || '')}</td>
                <td>${Number(item.puntosTotales) || 0}</td>
                <td>${Number(item.retosResueltos) || 0}</td>
            </tr>
        `;
    }

    rankingBody.innerHTML = html;
}

// Renderiza la posición del usuario autenticado en el ranking global
function renderizarMiPosicion(data) {
    if (!rankingMeNode) {
        return;
    }

    const miPosicion = Number(data && data.miPosicion);
    const miResumen = data && data.miResumen ? data.miResumen : null;

    if (!Number.isFinite(miPosicion) || !miResumen) {
        rankingMeNode.textContent = 'Todavía no tienes posición en el ranking global.';
        return;
    }

    const puntos = Number(miResumen.puntosTotales) || 0;
    const retos = Number(miResumen.retosResueltos) || 0;
    rankingMeNode.textContent = `Tu posición actual: #${miPosicion} (${puntos} puntos, ${retos} retos resueltos).`;
}

// Desactiva interacción cuando el reto no está disponible o ya fue resuelto
function bloquearEnvio() {
    if (pokemonSearch) {
        pokemonSearch.disabled = true;
    }
    if (submitButton) {
        submitButton.disabled = true;
    }
    ocultarSugerencias();
}

// Reactiva interacción cuando el reto todavía no está resuelto
function desbloquearEnvio() {
    if (pokemonSearch) {
        pokemonSearch.disabled = false;
    }
    if (submitButton) {
        submitButton.disabled = false;
    }
}

// Vincula eventos para el buscador de pokemon y su panel de sugerencias
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

    // Click fuera del formulario: cerrar el panel para no dejarlo abierto
    document.addEventListener('click', function (event) {
        if (!form.contains(event.target)) {
            ocultarSugerencias();
        }
    });
}

// Renderiza la lista de sugerencias debajo del input
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
        // mousedown evita que el blur del input cierre la lista antes de seleccionar
        suggestionItems[i].addEventListener('mousedown', function (event) {
            event.preventDefault();
            const name = suggestionItems[i].getAttribute('data-name') || '';
            pokemonSearch.value = name;
            ocultarSugerencias();
        });
    }
}

// Limpia la lista de sugerencias y la oculta
function ocultarSugerencias() {
    if (!suggestionsNode) {
        return;
    }

    suggestionsNode.innerHTML = '';
    suggestionsNode.hidden = true;
}

// Toma la lista de pokemons y la limpia para evitar problemas de datos mal formados
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

// Filtra y ordena la lista de pokemons según el texto ingresado por el usuario, con varias prioridades de coincidencia
function filtrarYOrdenarPokemones(query, pokemons) {
    if (!query) {
        return [];
    }

    // Prioridad de coincidencias: empieza por texto > contiene texto > similitud por subsecuencia
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

// Normaliza el texto para las comparaciones; minúsculas, sin acentos ni espacios al inicio/final
function normalizarTexto(text) {
    // Normaliza para comparar sin depender de mayúsculas o acentos
    return String(text)
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .trim();
}

// Verifica si los caracteres de la busqueda aparecen en orden dentro de 'target'
function esSubsecuencia(query, target) {
    // Similitud básica: "pkc" coincide con "pikachu" por orden de caracteres
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

// Escapa caracteres especiales para mostrar texto sin riesgo de inyectar HTML (seguridad)
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

// Toma la pista que viene del backend y la muestra en el panel de pistas
function mostrarPistas(hint) {
    if (!hint || !hint.datos) {
        ocultarPistas();
        return;
    }

    const listaPistas = [];
    const data = hint.datos;

    if (data.generacion !== undefined) {
        const generation = data.generacion ?? 'Desconocida';
        listaPistas.push(`<li><strong>Generacion:</strong> ${generation}</li>`);
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
        outputHtml = `
            <div class="hint-silhouette-wrap">
                <p><strong>Silueta:</strong></p>
                <img class="hint-silhouette" src="${data.silueta}" alt="Silueta del Pokemon" loading="lazy">
            </div>
        `;
    }

    if (listaPistas.length === 0) {
        ocultarPistas();
        return;
    }

    const failedText = hint.intentosFallidos ? `Intento fallido #${hint.intentosFallidos}` : 'Pista';
    let htmlLista = '';

    for (let i = 0; i < listaPistas.length; i++) {
        htmlLista += listaPistas[i];
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

// Limpia y oculta el panel de pistas
function ocultarPistas() {
    hintNode.hidden = true;
    hintNode.innerHTML = '';
}

// Toma los puntos obtenidos y los muestra en el panel de puntos, junto con el número de intentos fallidos antes del acierto si está disponible
function mostrarPuntos(points, failedBeforeSuccess) {
    if (!pointsNode) {
        return;
    }

    const puntos = Number(points);
    const fallos = Number(failedBeforeSuccess);

    if (!Number.isFinite(puntos)) {
        ocultarPuntos();
        return;
    }

    const fallosText = Number.isFinite(fallos) ? ` (fallos previos: ${fallos})` : '';
    pointsNode.textContent = `Puntos obtenidos: ${puntos}${fallosText}`;
    pointsNode.hidden = false;
}

function ocultarPuntos() {
    if (!pointsNode) {
        return;
    }

    pointsNode.hidden = true;
    pointsNode.textContent = '';
}

iniciar();
