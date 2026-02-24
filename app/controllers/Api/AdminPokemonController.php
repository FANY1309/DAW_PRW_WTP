<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Response;
use App\Services\PokemonImportService;
use Throwable;

class AdminPokemonController extends Controller
{
    /// POST /api/admin/pokemon/sync-generation
    public function syncGeneration(): void
    {
        $auth = $_SESSION['auth'] ?? null;
        // Verificar que el usuario esté autenticado
        if (!is_array($auth) || (int)($auth['id'] ?? 0) <= 0) {
            Response::json([
                'ok' => false,
                'message' => 'Debes iniciar sesion.',
            ], 401);
            return;
        }

        // Verificar que el usuario tenga rol de admin
        $rol = strtolower(trim((string)($auth['rol'] ?? '')));
        if ($rol !== 'admin') {
            Response::json([
                'ok' => false,
                'message' => 'Solo administradores pueden actualizar pokemones.',
            ], 403);
            return;
        }

        // Validar input
        $payload = $this->inputJson();
        $generacion = (int)($payload['generacion'] ?? 0);
        if ($generacion < 1 || $generacion > 9) {
            Response::json([
                'ok' => false,
                'message' => 'La generacion debe estar entre 1 y 9.',
            ], 422);
            return;
        }

        // Sincronizar pokemones de la generación indicada
        $service = new PokemonImportService();

        // Manejar posibles errores durante la sincronización
        try {
            $resultado = $service->sincronizarPorGeneracion($generacion, (int)$auth['id']);
        } catch (Throwable $e) {
            Response::json([
                'ok' => false,
                'message' => 'No se pudo sincronizar con PokeAPI.',
                'error' => $e->getMessage(),
            ], 502);
            return;
        }

        // Responder con el resultado de la sincronización
        Response::json([
            'ok' => true,
            'message' => 'Sincronizacion completada.',
            'data' => [
                'generacion' => (int)$resultado['generacion'],
                'totalProcesados' => (int)$resultado['totalProcesados'],
                'creados' => (int)$resultado['creados'],
                'actualizados' => (int)$resultado['actualizados'],
                'errores' => $resultado['errores'],
            ],
        ]);
    }
}
