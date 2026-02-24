<?php

namespace App\Controllers\Api;

use App\Core\Controller;
use App\Core\Response;
use App\Models\User;

class AuthController extends Controller
{
    // Maneja la ruta POST /api/auth/register para registrar usuarios invitados
    public function register(): void
    {
        if ($this->idUsuarioActual()) {
            Response::json([
                'ok' => false,
                'message' => 'Ya tienes una sesion activa.',
            ], 403);
            return;
        }

        $payload = $this->inputJson();
        $usuario = trim((string)($payload['usuario'] ?? ''));
        $email = trim((string)($payload['email'] ?? ''));
        $nombre = trim((string)($payload['nombre'] ?? ''));
        $password = (string)($payload['password'] ?? '');

        if ($usuario === '' || $email === '' || $nombre === '' || $password === '') {
            Response::json([
                'ok' => false,
                'message' => 'Usuario, email, nombre y contraseña son obligatorios.',
            ], 422);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json([
                'ok' => false,
                'message' => 'El email no es válido.',
            ], 422);
            return;
        }

        if (strlen($usuario) < 3 || strlen($usuario) > 50) {
            Response::json([
                'ok' => false,
                'message' => 'El usuario debe tener entre 3 y 50 carácteres.',
            ], 422);
            return;
        }

        if (strlen($nombre) < 2 || strlen($nombre) > 120) {
            Response::json([
                'ok' => false,
                'message' => 'El nombre debe tener entre 2 y 120 carácteres.',
            ], 422);
            return;
        }

        if (strlen($password) < 6) {
            Response::json([
                'ok' => false,
                'message' => 'La contraseña debe tener al menos 6 carácteres.',
            ], 422);
            return;
        }

        $userModel = new User();
        if ($userModel->existeUsuarioOEmail($usuario, $email)) {
            Response::json([
                'ok' => false,
                'message' => 'El usuario o email ya existen.',
            ], 409);
            return;
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $user = $userModel->crearUsuario($usuario, $email, $nombre, $passwordHash);

        if (!$user) {
            Response::json([
                'ok' => false,
                'message' => 'No se pudo completar el registro.',
            ], 500);
            return;
        }

        Response::json([
            'ok' => true,
            'message' => 'Registro completado. Ya puedes iniciar sesión.',
            'user' => [
                'id' => (int)($user['id'] ?? 0),
                'usuario' => (string)($user['usuario'] ?? ''),
                'email' => (string)($user['email'] ?? ''),
                'nombre' => (string)($user['nombre'] ?? ''),
                'rol' => (string)($user['rol'] ?? 'usuario'),
            ],
        ], 201);
    }

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
