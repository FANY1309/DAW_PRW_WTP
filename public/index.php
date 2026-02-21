<?php

// Arrancamos la app (configuraciones)
$config = require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/core/Router.php';
// Router: registra rutas (GET/POST) y despacha cada request al controlador correcto. (MVC)
use App\Core\Router;

// Creamos el enrutador (decide qué controlador se ejecuta).
$router = new Router();
