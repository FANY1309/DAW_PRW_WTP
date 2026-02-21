// Este archivo sirve de mensajero entre el javascript y el backend
// Basicamente monta la peticion http (url) y con fetch hace la llamada al servidor
export async function apiFetch(path, options = {}) {
    // Guardamos la ruta que llega (por ejemplo: "api/reto/hoy")
    let route = path;

    // Si la ruta NO empieza con "/", se la ponemos
    // Así evitamos errores de formato
    if (!route.startsWith('/')) {
        route = '/' + route;
    }

    // Construimos la URL final que entiende el backend
    // encodeURIComponent protege caracteres raros en la ruta
    let url = 'index.php?route=' + encodeURIComponent(route);

    // Creamos la configuración de la petición
    let config = {};

    // Si no nos pasan método, usamos GET por defecto
    config.method = options.method || 'GET';

    // Cabecera básica: decimos que trabajamos con JSON
    config.headers = {
        'Content-Type': 'application/json'
    };

    // Si llegan más cabeceras, las copiamos una por una
    if (options.headers) {
        for (let key in options.headers) {
            config.headers[key] = options.headers[key];
        }
    }

    // Si hay body (datos a enviar), lo añadimos
    if (options.body) {
        config.body = options.body;
    }

    // Hacemos la petición HTTP al servidor
    let response = await fetch(url, config);

    // Intentamos leer JSON de la respuesta
    // Si falla (por ejemplo respuesta vacía), devolvemos {}
    let data = {};
    try {
        data = await response.json();
    } catch (e) {
        data = {};
    }

    // Devolvemos un objeto simple para usar en la app
    return {
        status: response.status,
        ok: response.ok,
        data: data
    };
}
