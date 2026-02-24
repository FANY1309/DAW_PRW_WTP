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
        // Incluimos estado de resolución para que el index se cargue bloqueado si corresponde.
        $alreadySolved = $service->hasSolvedTodayChallenge();
        // Incluimos resumen de puntaje/intentos para mostrar al usuario cuando ya resolvio.
        $solvedSummary = $service->getResumenIntentoResuelto();

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
            // Estado booleano de bloqueo del reto de hoy.
            'alreadySolved' => $alreadySolved,
            // Mensaje listo para pintar en UI cuando ya se resolvió el reto diario.
            'alreadySolvedMessage' => $alreadySolved ? 'Ya acertaste el reto de hoy. Vuelve manana para un nuevo reto.' : null,
            // Datos de puntaje para mostrar al usuario que ya resolvio
            'alreadySolvedPoints' => $solvedSummary ? (int)$solvedSummary['puntos'] : null,
            'alreadySolvedFailedAttempts' => $solvedSummary ? (int)$solvedSummary['intentosFallidosAntesDelAcierto'] : null,
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
