<?php

// Arrancamos la app (configuraciones)
$config = require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/core/Response.php';
require __DIR__ . '/../app/core/Database.php';
require __DIR__ . '/../app/core/Controller.php';
require __DIR__ . '/../app/core/Model.php';
require __DIR__ . '/../app/controllers/HomeController.php';
require __DIR__ . '/../app/models/RetoDiario.php';
require __DIR__ . '/../app/models/Partida.php';
require __DIR__ . '/../app/models/Pokemon.php';
require __DIR__ . '/../app/services/GameService.php';
require __DIR__ . '/../app/controllers/Api/RetoController.php';
require __DIR__ . '/../app/controllers/Api/PartidaController.php';
require __DIR__ . '/../app/core/Router.php';

// la clase que registra rutas (GET/POST) y envía cada request al controlador correcto. (MVC)
use App\Core\Router;
// La clase controlador del home
use App\Controllers\HomeController;
use App\Controllers\Api\RetoController;
use App\Controllers\Api\PartidaController;

// Creamos el enrutador (decide qué controlador se ejecuta).
$router = new Router();

// registramos los controladores correspondientes
$router->get('/', [HomeController::class, 'index']);
$router->get('/api/reto/hoy', [RetoController::class, 'hoy']);
$router->get('/api/pokemon/lista', [RetoController::class, 'pokemones']);
$router->post('/api/partida/intento', [PartidaController::class, 'intento']);

// capturamos el método HTTP actual (GET, POST, etc.).
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Comenzamos a limpiar la ruta
$routeParam = isset($_GET['route']) ? trim((string)$_GET['route']) : '';

if ($routeParam !== '') {
    $uriPath = '/' . ltrim($routeParam, '/');
} else {
    // 3) Si no llega ?route=..., tomamos la ruta real de la URL
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $uriPath = parse_url($requestUri, PHP_URL_PATH);

    if (!is_string($uriPath) || $uriPath === '') {
        $uriPath = '/';
    }
}

// Quitamos la carpeta base (ej: /wtp/public) para dejar una ruta limpia
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseFolder = dirname($scriptName);          // ej: /wtp/public
$baseFolder = str_replace('\\', '/', $baseFolder);
$baseFolder = rtrim($baseFolder, '/');

if ($baseFolder !== '' && $baseFolder !== '/') {
    if (strpos($uriPath, $baseFolder) === 0) {
        $uriPath = substr($uriPath, strlen($baseFolder));
    }
}

// Si empieza por /index.php, quitamos /index.php
if (strpos($uriPath, '/index.php') === 0) {
    $uriPath = substr($uriPath, strlen('/index.php'));
}

// Si quedó vacío, lo tratamos como la raíz.
if ($uriPath === '') {
    $uriPath = '/';
}

// Enviamos método + ruta al router.
// var_dump($uriPath);
$router->dispatch($method, $uriPath);
