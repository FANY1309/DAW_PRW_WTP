<?php

namespace App\Core;

/**
 * Clase utilitaria para construir respuestas HTTP
 * Evita repetir headers/status en cada controlador
 * 
 * Ejemplo de uso:
 * Response::json(['ok' => true, 'message' => 'Todo bien'], 200);
 */
class Response
{
    /**
     * Envía una respuesta JSON al cliente.
     *
     * @param array $data   Datos que se convertirán a JSON.
     * @param int   $status Código de estado HTTP (200, 404, 500, etc).
     *
     * 1) Define el status code HTTP.
     * 2) Define el header Content-Type como JSON UTF-8.
     * 3) Convierte el array a JSON y lo imprime en la salida.
     */
    public static function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
