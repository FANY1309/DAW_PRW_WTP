<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Response;
use App\Services\GameService;

// Esta clase devuelve respuestas en formato JSON para las rutas relacionadas con el reto diario
class RetoController extends Controller
{
    public function hoy(): void
    {
        // llamamos al servicio que nos permite obtener con un select el reto diario
        $service = new GameService();
        $reto = $service->getTodayChallenge();

        // respuesta del json en caso de que la consulta no encuentre un reto diario
        if (!$reto) {
            Response::json([
                'ok' => false,
                'message' => 'No hay reto cargado.',
            ], 404);
            return;
        }
        
        Response::json([
            'ok' => true,
            'id' => (int)$reto['id'],
            'fecha' => $reto['fecha'],
            'activo' => (int)$reto['activo'],
            // No exponemos el nombre para no romper el juego.
            'pokemon' => [
                'id' => (int)$reto['pokemon_id'],
                'imagen' => $reto['imagen'],
            ],
        ]);
    }

    // Capturamos la lista de pokemon para el select del index
    public function pokemones(): void
    {
        $service = new GameService();
        $pokemones = $service->getListaPokemons();

        Response::json([
            'ok' => true,
            'items' => array_map(static function (array $pokemon): array {
                return [
                    'id' => (int)$pokemon['id'],
                    'nombre' => (string)$pokemon['nombre'],
                ];
            }, $pokemones),
        ]);
    }
}
