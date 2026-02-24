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

// Esta función envía el intento del jugador (nombre del Pokémon).
// Usamos POST porque estamos mandando datos.
// JSON.stringify convierte el objeto a texto JSON.
export async function submitAttempt(nombre) {
    return apiFetch('api/partida/intento', {
        method: 'POST',
        body: JSON.stringify({ nombre }),
    });
}
