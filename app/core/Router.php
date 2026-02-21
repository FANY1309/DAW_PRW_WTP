<?php

namespace App\Core;

class Router
{
    // Aqui guardamos todas las rutas del sistema.
    // Ejemplo de estructura:
    // [
    //   'GET' => [
    //     '/api/health' => [HealthController::class, 'check']
    //   ],
    //   'POST' => [
    //     '/api/login' => [AuthController::class, 'login']
    //   ]
    // ]
    public $routes = [];

    // Registra una ruta GET.
    // Ejemplo:
    // $router->get('/api/health', [HealthController::class, 'check']);
    public function get($path, $handler)
    {
        if (!isset($this->routes['GET'])) {
            $this->routes['GET'] = [];
        }

        $this->routes['GET'][$path] = $handler;
    }

    // Registra una ruta POST.
    // Ejemplo:
    // $router->post('/api/login', [AuthController::class, 'login']);
    public function post($path, $handler)
    {
        if (!isset($this->routes['POST'])) {
            $this->routes['POST'] = [];
        }

        $this->routes['POST'][$path] = $handler;
    }

    // Busca la ruta y ejecuta el controlador.
    // Ejemplo:
    // Si llega GET /api/health y existe:
    // '/api/health' => [HealthController::class, 'check']
    // entonces instancia HealthController y ejecuta check().
    public function dispatch($method, $uriPath)
    {
        // Si no existe el metodo HTTP en el array, devolvemos 404.
        if (!isset($this->routes[$method])) {
            Response::json([
                'ok' => false,
                'message' => 'Route not found'
            ], 404);
            return;
        }

        // Si existe el metodo pero no la URI exacta, devolvemos 404.
        if (!isset($this->routes[$method][$uriPath])) {
            Response::json([
                'ok' => false,
                'message' => 'Route not found'
            ], 404);
            return;
        }

        // Handler esperado: [NombreControlador::class, 'metodo']
        $handler = $this->routes[$method][$uriPath];

        // Sacamos controlador y accion por separado.
        $controllerClass = $handler[0];
        $action = $handler[1];

        // Creamos una instancia del controlador.
        $controller = new $controllerClass();

        // Validacion basica por si el metodo no existe en el controlador.
        if (!method_exists($controller, $action)) {
            Response::json([
                'ok' => false,
                'message' => 'Action not found'
            ], 500);
            return;
        }

        // Ejecutamos la accion final.
        $controller->$action();
    }
}
