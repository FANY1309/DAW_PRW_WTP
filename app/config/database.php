<?php

// Cogemos los datos de la base de datos de las variables de entorno
return [
    'host' => getenv('DB_HOST'),
    'port' => getenv('DB_PORT'),
    'name' => getenv('DB_NAME'),
    'user' => getenv('DB_USER'),
    'pass' => getenv('DB_PASS'),
    'charset' => 'utf8mb4',
];
