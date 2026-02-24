<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Pokemon;
use App\Models\RetoDiario;
use App\Models\User;
use PDOException;

class AdminRetoController extends Controller
{
    // POST /api/admin/reto-diario
    public function create(): void
    {   
        // Verificar que el usuario esté autenticado y tenga rol de admin para permitir la creación de retos diarios
        $auth = $_SESSION['auth'] ?? null;
        if (!is_array($auth) || (int)($auth['id'] ?? 0) <= 0) {
            Response::json([
                'ok' => false,
                'message' => 'Debes iniciar sesion.',
            ], 401);
            return;
        }

        // Verificar que el usuario tenga rol de admin para permitir la creación de retos diarios, solo los admins pueden crear retos diarios
        $rol = strtolower(trim((string)($auth['rol'] ?? '')));
        if ($rol !== 'admin') {
            Response::json([
                'ok' => false,
                'message' => 'Solo administradores pueden crear retos diarios.',
            ], 403);
            return;
        }

        // Validar que el usuario admin aún exista en la base de datos, para evitar que un usuario que ha sido eliminado o ha perdido su rol de admin pueda crear retos diarios
        $adminUserId = (int)$auth['id'];
        $userModel = new User();
        $admin = $userModel->encontrarPorID($adminUserId);
        if (!$admin) {
            Response::json([
                'ok' => false,
                'message' => 'Tu sesion de administrador ya no es valida. Cierra sesion e inicia nuevamente.',
            ], 401);
            return;
        }

        // Validar input, la fecha debe tener formato YYYY-MM-DD y el pokemonId debe ser un entero positivo
        $payload = $this->inputJson();
        $fecha = trim((string)($payload['fecha'] ?? ''));
        $pokemonId = (int)($payload['pokemonId'] ?? 0);
        
        if (!$this->isValidDate($fecha)) {
            Response::json([
                'ok' => false,
                'message' => 'La fecha debe tener formato YYYY-MM-DD.',
            ], 422);
            return;
        }

        // Validar que el pokemonId corresponda a un pokemon existente en la base de datos, para evitar crear retos diarios con pokemones no válidos
        if ($pokemonId <= 0) {
            Response::json([
                'ok' => false,
                'message' => 'Debes seleccionar un pokemon valido.',
            ], 422);
            return;
        }

        // Validar que el pokemonId corresponda a un pokemon existente en la base de datos, para evitar crear retos diarios con pokemones no válidos
        $pokemonModel = new Pokemon();
        $pokemon = $pokemonModel->encontrarPorId($pokemonId);
        if (!$pokemon) {
            Response::json([
                'ok' => false,
                'message' => 'El pokemon seleccionado no existe.',
            ], 404);
            return;
        }

        // Validar que no exista ya un reto diario para la fecha indicada, para evitar crear múltiples retos diarios para la misma fecha, lo cual podría generar confusión entre los usuarios
        $retoModel = new RetoDiario();
        if ($retoModel->existsByDate($fecha)) {
            Response::json([
                'ok' => false,
                'message' => 'Ya existe un reto diario para esa fecha.',
            ], 409);
            return;
        }

        // Intentar crear el reto diario, manejando posibles errores de la base de datos, como violaciones de restricciones o problemas de conexión
        try {
            $retoId = $retoModel->create($adminUserId, $fecha, $pokemonId);
        } catch (PDOException $e) {
            $driverCode = (int)($e->errorInfo[1] ?? 0);
            if ($driverCode === 1062) {
                Response::json([
                    'ok' => false,
                    'message' => 'Ya existe un reto diario para esa fecha.',
                ], 409);
                return;
            }

            Response::json([
                'ok' => false,
                'message' => 'No se pudo crear el reto diario.',
            ], 500);
            return;
        }

        Response::json([
            'ok' => true,
            'message' => 'Reto diario creado correctamente.',
            'data' => [
                'id' => $retoId,
                'fecha' => $fecha,
                'pokemonId' => $pokemonId,
                'pokemonNombre' => (string)$pokemon['nombre'],
            ],
        ], 201);
    }

    // Validamos fechas con formato YYYY-MM-DD 
    private function isValidDate(string $fecha): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $fecha));
        return checkdate($month, $day, $year);
    }
}
