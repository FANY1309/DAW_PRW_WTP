<?php

namespace App\Models;

use App\Core\Model;

class RetoDiario extends Model
{
    // función para encontrar el reto de hoy
    public function findTodayActive(): ?array
    {
        $sql = "
            SELECT
                rd.id,
                rd.fecha,
                rd.activo,
                p.id AS pokemon_id,
                p.nombre,
                p.imagen,
                p.generacion,
                p.color,
                p.altura,
                p.peso,
                GROUP_CONCAT(DISTINCT t.nombre ORDER BY t.nombre SEPARATOR ',') AS tipos
            FROM reto_diario rd
            INNER JOIN pokemon p ON p.id = rd.idPokemon
            LEFT JOIN pokemon_tipo pt ON pt.idPokemon = p.id
            LEFT JOIN tipo t ON t.id = pt.idTipo
            WHERE rd.fecha = CURDATE() AND rd.activo = 1
            GROUP BY rd.id, rd.fecha, rd.activo, p.id, p.nombre, p.imagen, p.generacion, p.color, p.altura, p.peso
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
            SELECT
                rd.id,
                rd.fecha,
                rd.activo,
                p.id AS pokemon_id,
                p.nombre,
                p.imagen,
                p.generacion,
                p.color,
                p.altura,
                p.peso,
                GROUP_CONCAT(DISTINCT t.nombre ORDER BY t.nombre SEPARATOR ',') AS tipos
            FROM reto_diario rd
            INNER JOIN pokemon p ON p.id = rd.idPokemon
            LEFT JOIN pokemon_tipo pt ON pt.idPokemon = p.id
            LEFT JOIN tipo t ON t.id = pt.idTipo
            WHERE rd.activo = 1
            GROUP BY rd.id, rd.fecha, rd.activo, p.id, p.nombre, p.imagen, p.generacion, p.color, p.altura, p.peso
            ORDER BY rd.fecha DESC
            LIMIT 1
        ";

        $consulta = $this->db->pdo()->query($sql);
        $fila = $consulta->fetch();

        return $fila ?: null;
    }
}
