<?php

// Cogemos los datos de la base de datos de las variables de entorno
return [
    'host' => $_ENV['DB_HOST'] ?? $_SERVER['DB_HOST'] ?? '',
    'port' => $_ENV['DB_PORT'] ?? $_SERVER['DB_PORT'] ?? '',
    'name' => $_ENV['DB_NAME'] ?? $_SERVER['DB_NAME'] ?? '',
    'user' => $_ENV['DB_USER'] ?? $_SERVER['DB_USER'] ?? '',
    'pass' => $_ENV['DB_PASS'] ?? $_SERVER['DB_PASS'] ?? '',
    'charset' => 'utf8mb4',
];
