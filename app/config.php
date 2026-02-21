<?php

// Carga manual de variables desde /wtp/.env
// NOTA: mirar el archivo /wtp/.env.example para ver un ejemplo
$envPath = dirname(__DIR__) . '/.env';
if (is_file($envPath)) {
    $envData = parse_ini_file($envPath, false, INI_SCANNER_RAW);
    if (is_array($envData)) {
        foreach ($envData as $key => $value) {
            $line = $key . '=' . $value;
            putenv($line);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// cargamos la configuración de la aplicación y la base de datos
$appConfig = require __DIR__ . "/config/app.php";
$dbConfig = require __DIR__ . "/config/database.php";

// seteamos el timezone
if (isset($appConfig["timezone"])) {
    date_default_timezone_set($appConfig["timezone"]);
} else {
    date_default_timezone_set("UTC");
}

// iniciamos la sesion
if (!isset($_SESSION)) {
    @session_start();
}

// cargamos y devolvemos toda la configuración en un array
$arr = [];
$arr["app"] = $appConfig;
$arr["db"] = $dbConfig;

return $arr;
