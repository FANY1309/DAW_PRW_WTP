import { apiFetch } from '../api.js';

// Esta función pide al backend el reto de hoy.
// Devuelve la respuesta completa para que otro archivo la use.
export async function loadChallenge() {
    return apiFetch('api/reto/hoy');
}

