<?php

namespace App\Models;

use App\Core\Model;

class Partida extends Model
{   
    // guarda un intento en la base de datos
    public function saveAttempt(int $usuarioId, int $retoId, string $resultado): int
    {
        // Sql para registrar el intento en la base de datos
        $sql = "
            INSERT INTO partida (fecha, idUsuario, idReto, resultado, creadoPorUsuario, fechaCreacion, fechaUltimaModificacion)
            VALUES (NOW(), :usuario, :reto, :resultado, :usuario, NOW(), NOW())
        ";

        // Preparamos y ejecutamos con parámetros enlazados para evitar SQL injection en el formulario
        $stmt = $this->db->pdo()->prepare($sql);
        // le pasamos los datos a la sql
        $stmt->execute([
            ':usuario' => $usuarioId,
            ':reto' => $retoId,
            ':resultado' => $resultado,
        ]);

        // Devolvemos el id del registro recién creado
        return (int)$this->db->pdo()->lastInsertId();
    }

    // contamos los fallos de un usuario concreto en un reto concreto
    public function countFailedAttempts(int $usuarioId, int $retoId): int
    {
        // Esto se usa para saber cuántas pistas debemos mostrar
        $sql = "
            SELECT COUNT(*) AS total
            FROM partida
            WHERE idUsuario = :usuario
              AND idReto = :reto
              AND resultado = 'fallo'
        ";

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute([
            ':usuario' => $usuarioId,
            ':reto' => $retoId,
        ]);

        $row = $stmt->fetch();
        return (int)($row['total'] ?? 0);
    }

    // Si existe al menos un "acierto" para usuario - reto
    // el jugador no puede seguir enviando intentos ese mismo día
    public function hasCorrectAttempt(int $usuarioId, int $retoId): bool
    {
        $sql = "
            SELECT 1
            FROM partida
            WHERE idUsuario = :usuario
              AND idReto = :reto
              AND resultado = 'acierto'
            LIMIT 1
        ";

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute([
            ':usuario' => $usuarioId,
            ':reto' => $retoId,
        ]);

        return (bool)$stmt->fetchColumn();
    }
}

