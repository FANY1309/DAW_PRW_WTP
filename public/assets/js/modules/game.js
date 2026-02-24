import { apiFetch } from '../api.js';

// Esta función pide al backend el reto de hoy.
// Devuelve la respuesta completa para que otro archivo la use.
export async function loadChallenge() {
    return apiFetch('api/reto/hoy');
}

// Esta función pide al backend la lista de pokemons disponibles
export async function loadPokemonList() {
    return apiFetch('api/pokemon/lista');
}

// Carga el ranking global para usuarios autenticados
export async function loadGlobalRanking() {
    return apiFetch('api/ranking/global');
}

// Esta función envía el intento del jugador (nombre del Pokémon).
// Usamos POST porque estamos mandando datos.
// JSON.stringify convierte el objeto a texto JSON.
export async function submitAttempt(nombre) {
    return apiFetch('api/partida/intento', {
        method: 'POST',
        body: JSON.stringify({ nombre }),
    });
}

// Consulta la sesion actual para saber si hay un usuario autenticado
export async function getCurrentUser() {
    return apiFetch('api/auth/me');
}

// Registra un usuario nuevo para invitados
export async function registerUser(usuario, email, nombre, password) {
    return apiFetch('api/auth/register', {
        method: 'POST',
        body: JSON.stringify({ usuario, email, nombre, password }),
    });
}

// Inicia sesion enviando identificador (usuario/email) y contraseña
export async function loginUser(identifier, password) {
    return apiFetch('api/auth/login', {
        method: 'POST',
        body: JSON.stringify({ identifier, password }),
    });
}

// Cierra la sesion actual del usuario autenticado
export async function logoutUser() {
    return apiFetch('api/auth/logout', {
        method: 'POST',
    });
}

// Sincroniza pokemons de una generacion via endpoint admin
export async function syncPokemonGeneration(generacion) {
    return apiFetch('api/admin/pokemon/sync-generation', {
        method: 'POST',
        body: JSON.stringify({ generacion }),
    });
}

// Crea un reto diario manualmente via endpoint admin
export async function createDailyChallenge(fecha, pokemonId) {
    return apiFetch('api/admin/reto-diario', {
        method: 'POST',
        body: JSON.stringify({ fecha, pokemonId }),
    });
}
