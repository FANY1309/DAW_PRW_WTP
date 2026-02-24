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

    // Verifica si ya existe un reto para la fecha indicada
    public function existsByDate(string $fecha): bool
    {
        $sql = "
            SELECT 1
            FROM reto_diario
            WHERE fecha = :fecha
            LIMIT 1
        ";

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute([
            ':fecha' => $fecha,
        ]);

        return (bool)$stmt->fetchColumn();
    }

    // Crea un nuevo reto diario para una fecha y pokemon especificos
    public function create(int $adminUserId, string $fecha, int $pokemonId): int
    {
        $sql = "
            INSERT INTO reto_diario (
                fecha,
                activo,
                idPokemon,
                creadoPorUsuario,
                fechaCreacion,
                modificadoPorUsuario,
                fechaUltimaModificacion
            ) VALUES (
                :fecha,
                1,
                :pokemonId,
                :adminUserId,
                NOW(),
                :adminUserId,
                NOW()
            )
        ";

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute([
            ':fecha' => $fecha,
            ':pokemonId' => $pokemonId,
            ':adminUserId' => $adminUserId,
        ]);

        return (int)$this->db->pdo()->lastInsertId();
    }
}
