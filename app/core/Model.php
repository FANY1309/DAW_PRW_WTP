<?php

namespace App\Core;

use App\Core\Database;

abstract class Model
{
    protected Database $db;

    public function __construct()
    {
        // Cada vez que se cree un modelo se hará una conexión a la bd
        $config = require __DIR__ . '/../config/database.php';
        $this->db = new Database($config);
    }
}
