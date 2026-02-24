<?php

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    // El modelo de usuario se encarga de manejar la autenticación
    public function encontrarPorLogin(string $identifier): ?array
    {
        $sql = "
            SELECT id, usuario, email, nombre, password, estado, rol
            FROM usuario
            WHERE usuario = :identifier OR email = :identifier
            LIMIT 1
        ";

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute([
            ':identifier' => $identifier,
        ]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    // Encuentra un usuario por su ID, sin incluir la contraseña
    public function encontrarPorContrasenia(int $id): ?array
    {
        $sql = "
            SELECT id, usuario, email, nombre, estado, rol
            FROM usuario
            WHERE id = :id
            LIMIT 1
        ";

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute([
            ':id' => $id,
        ]);

        $row = $stmt->fetch();
        return $row ?: null;
    }

    // Actualiza el tracking de sesión del usuario al iniciar sesión
    public function actualizarSesion(int $id, string $sessionId): void
    {
        $sql = "
            UPDATE usuario
            SET idSesion = :sessionId, ultimoSesion = NOW(), fechaUltimaModificacion = NOW()
            WHERE id = :id
        ";

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':sessionId' => $sessionId,
        ]);
    }
}
