<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Response;
use App\Services\GameService;

/**
 * Controlador API para registrar intentos de partida.
 *
 * Responsabilidad:
 * - Recibir el nombre enviado por el cliente.
 * - Validar que el valor exista.
 * - Delegar la lógica de negocio al GameService.
 * - Devolver respuesta JSON con código HTTP adecuado.
 */
class PartidaController extends Controller
{
    /**
     * Endpoint: POST /api/partida/intento
     *
     * Body JSON esperado:
     * {
     *   "nombre": "pikachu"
     * }
     *
     * Respuestas:
     * - 200: intento procesado correctamente
     * - 404: no hay reto activo
     * - 422: validación de entrada fallida (nombre vacío)
     */
    public function intento(): void
    {
        // Lee el body de la petición y lo transforma en un array
        $payload = $this->inputJson();
        $guess = trim((string)($payload['nombre'] ?? ''));

        // Si no llega nombre, devolvemos error de validación
        if ($guess === '') {
            Response::json([
                'ok' => false,
                'message' => 'El nombre del pokemon es obligatorio.',
            ], 422);
            return;
        }

        // Ejecutamos la lógica del intento
        $service = new GameService();
        $result = $service->attempt($guess);

        // Si ya estaba resuelto, devolvemos 409 para diferenciarlo de "sin reto activo".
        if (!$result['ok'] && !empty($result['alreadySolved'])) {
            Response::json($result, 409);
            return;
        }

        // Si el servicio marca ok=false (ej. sin reto), devolvemos 404
        $status = $result['ok'] ? 200 : 404;
        Response::json($result, $status);
    }
}

