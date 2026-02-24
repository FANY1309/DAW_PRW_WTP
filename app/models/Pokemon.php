<?php

namespace App\Models;

use App\Core\Model;

class Pokemon extends Model
{
    // Listamos la lista completa de pokémons
    public function ListaTodos(): array
    {
        $sql = "
            SELECT id, nombre
            FROM pokemon
            ORDER BY nombre ASC
        ";

        $consulta = $this->db->pdo()->query($sql);
        $filas = $consulta->fetchAll();

        return is_array($filas) ? $filas : [];
    }
}
