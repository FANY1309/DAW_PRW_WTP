<?php

namespace App\Models;

use App\Core\Model;

class RetoDiario extends Model
{
    // función para encontrar el reto de hoy
    public function findTodayActive(): ?array
    {
        $sql = "
            SELECT rd.id, rd.fecha, rd.activo, p.id AS pokemon_id, p.nombre, p.imagen
            FROM reto_diario rd
            INNER JOIN pokemon p ON p.id = rd.idPokemon
            WHERE rd.fecha = CURDATE() AND rd.activo = 1
            LIMIT 1
        ";

        $consulta = $this->db->pdo()->query($sql);
        $fila = $consulta->fetch();

        return $fila ?: null;
    }

    // función para encontrar el último reto diario disponible
    public function findAnyActive(): ?array
    {
        $sql = "
            SELECT rd.id, rd.fecha, rd.activo, p.id AS pokemon_id, p.nombre, p.imagen
            FROM reto_diario rd
            INNER JOIN pokemon p ON p.id = rd.idPokemon
            WHERE rd.activo = 1
            ORDER BY rd.fecha DESC
            LIMIT 1
        ";

        $consulta = $this->db->pdo()->query($sql);
        $fila = $consulta->fetch();

        return $fila ?: null;
    }
}
