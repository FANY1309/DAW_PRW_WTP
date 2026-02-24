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
require __DIR__ . '/../app/models/User.php';
require __DIR__ . '/../app/services/GameService.php';
require __DIR__ . '/../app/services/PokeApiService.php';
require __DIR__ . '/../app/services/PokemonImportService.php';
require __DIR__ . '/../app/controllers/Api/RetoController.php';
require __DIR__ . '/../app/controllers/Api/PartidaController.php';
require __DIR__ . '/../app/controllers/Api/AuthController.php';
require __DIR__ . '/../app/controllers/Api/AdminPokemonController.php';
require __DIR__ . '/../app/controllers/Api/AdminRetoController.php';
require __DIR__ . '/../app/core/Router.php';

// la clase que registra rutas (GET/POST) y envía cada request al controlador correcto. (MVC)
use App\Core\Router;
// La clase controlador del home
use App\Controllers\HomeController;
use App\Controllers\Api\RetoController;
use App\Controllers\Api\PartidaController;
use App\Controllers\Api\AuthController;
use App\Controllers\Api\AdminPokemonController;
use App\Controllers\Api\AdminRetoController;

// Creamos el enrutador (decide qué controlador se ejecuta).
$router = new Router();

// registramos los controladores correspondientes
$router->get('/', [HomeController::class, 'index']);
$router->get('/api/reto/hoy', [RetoController::class, 'hoy']);
$router->get('/api/pokemon/lista', [RetoController::class, 'pokemones']);
$router->get('/api/ranking/global', [RetoController::class, 'rankingGlobal']);
$router->post('/api/partida/intento', [PartidaController::class, 'intento']);
$router->get('/api/auth/me', [AuthController::class, 'me']);
$router->post('/api/auth/register', [AuthController::class, 'register']);
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/logout', [AuthController::class, 'logout']);
$router->post('/api/admin/pokemon/sync-generation', [AdminPokemonController::class, 'syncGeneration']);
$router->post('/api/admin/reto-diario', [AdminRetoController::class, 'create']);

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
