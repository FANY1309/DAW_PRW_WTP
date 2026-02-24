<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Response;
use App\Models\User;

class AuthController extends Controller
{
    // Maneja la ruta POST /api/login para iniciar sesión
    public function login(): void
    {
        $payload = $this->inputJson();
        $identifier = trim((string)($payload['identifier'] ?? ''));
        $password = (string)($payload['password'] ?? '');

        if ($identifier === '' || $password === '') {
            Response::json([
                'ok' => false,
                'message' => 'Usuario/email y contrasena son obligatorios.',
            ], 422);
            return;
        }

        $userModel = new User();
        $user = $userModel->encontrarPorLogin($identifier);

        if (!$user || (int)($user['estado'] ?? 0) !== 1) {
            Response::json([
                'ok' => false,
                'message' => 'Credenciales invalidas.',
            ], 401);
            return;
        }

        $storedPassword = (string)($user['password'] ?? '');
        $isValidPassword = password_verify($password, $storedPassword) || hash_equals($storedPassword, $password);

        if (!$isValidPassword) {
            Response::json([
                'ok' => false,
                'message' => 'Credenciales invalidas.',
            ], 401);
            return;
        }

        @session_regenerate_id(true);

        $auth = [
            'id' => (int)$user['id'],
            'usuario' => (string)$user['usuario'],
            'email' => (string)$user['email'],
            'nombre' => (string)$user['nombre'],
            'rol' => (string)$user['rol'],
        ];

        $_SESSION['auth'] = $auth;
        $userModel->actualizarSesion((int)$user['id'], session_id());

        Response::json([
            'ok' => true,
            'authenticated' => true,
            'user' => $auth,
            'message' => 'Sesion iniciada.',
        ]);
    }

    // Maneja la ruta GET /api/me para obtener información del usuario autenticado
    public function me(): void
    {
        if (!$this->idUsuarioActual()) {
            Response::json([
                'ok' => true,
                'authenticated' => false,
                'user' => null,
                'message' => 'No hay sesion activa.',
            ]);
            return;
        }

        $auth = $_SESSION['auth'] ?? [];
        Response::json([
            'ok' => true,
            'authenticated' => true,
            'user' => [
                'id' => (int)($auth['id'] ?? 0),
                'usuario' => (string)($auth['usuario'] ?? ''),
                'email' => (string)($auth['email'] ?? ''),
                'nombre' => (string)($auth['nombre'] ?? ''),
                'rol' => (string)($auth['rol'] ?? ''),
            ],
        ]);
    }

    // Maneja la ruta POST /api/logout para cerrar sesión
    public function logout(): void
    {
        unset($_SESSION['auth']);
        @session_regenerate_id(true);

        Response::json([
            'ok' => true,
            'authenticated' => false,
            'message' => 'Sesion cerrada.',
        ]);
    }
}
