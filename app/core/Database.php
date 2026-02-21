<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    // Guardamos aqui la conexion PDO para poder usarla desde otros sitios
    private PDO $pdo;

    public function __construct(array $config)
    {
        // Sacamos cada valor de configuracion para que sea mas facil de leer
        $host = $config['host'];
        $port = $config['port'];
        $name = $config['name'];
        $charset = $config['charset'];
        $user = $config['user'];
        $pass = $config['pass'];

        // Montamos el DSN (la cadena que PDO usa para saber a que BD conectarse)
        $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $name . ';charset=' . $charset;

        try {
            // Intentamos crear la conexion con la base de datos
            $this->pdo = new PDO($dsn, $user, $pass);

            // Si hay un error SQL, queremos que PDO lance excepciones
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Por defecto, cada fila que leamos vendra como array asociativo
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Si falla la conexion, devolvemos error HTTP 500
            http_response_code(500);

            // Indicamos que la respuesta es JSON
            header('Content-Type: application/json; charset=utf-8');

            // Mostramos un JSON simple explicando el error
            echo json_encode([
                'ok' => false,
                'message' => 'Database connection error',
                'error' => $e->getMessage(),
            ]);

            // Cortamos la ejecucion porque sin BD no podemos seguir
            exit;
        }
    }

    public function pdo(): PDO
    {
        // Devolvemos la conexion para que otros objetos puedan hacer consultas
        return $this->pdo;
    }
}
