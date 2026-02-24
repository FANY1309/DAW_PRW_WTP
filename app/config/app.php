<?php

// Base URL de public/index.php (ej: /wtp)
return [
    'base_url' => $_ENV['APP_BASE_URL'] ?? $_SERVER['APP_BASE_URL'] ?? '/',
    'app_name' => 'WhosThatPokemon',
    'timezone' => 'Atlantic/Canary',
];
