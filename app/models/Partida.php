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
}
