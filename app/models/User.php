<?php

namespace App\Models;

use App\Core\Model;
use PDOException;

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
    public function encontrarPorID(int $id): ?array
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

    // Verificamos si ya existe un usuario o email en la base
    public function existeUsuarioOEmail(string $usuario, string $email): bool
    {
        $sql = "
            SELECT id
            FROM usuario
            WHERE usuario = :usuario OR email = :email
            LIMIT 1
        ";

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute([
            ':usuario' => $usuario,
            ':email' => $email,
        ]);

        return (bool)$stmt->fetch();
    }

    // Crea un usuario activo con rol normal y devuelve sus datos publicos
    public function crearUsuario(string $usuario, string $email, string $nombre, string $passwordHash): ?array
    {
        $sql = "
            INSERT INTO usuario (
                usuario,
                email,
                nombre,
                password,
                fechaRegistro,
                estado,
                rol,
                fechaCreacion,
                fechaUltimaModificacion
            ) VALUES (
                :usuario,
                :email,
                :nombre,
                :password,
                NOW(),
                1,
                'usuario',
                NOW(),
                NOW()
            )
        ";

        $stmt = $this->db->pdo()->prepare($sql);

        try {
            $stmt->execute([
                ':usuario' => $usuario,
                ':email' => $email,
                ':nombre' => $nombre,
                ':password' => $passwordHash,
            ]);
        } catch (PDOException $e) {
            return null;
        }

        $id = (int)$this->db->pdo()->lastInsertId();
        if ($id <= 0) {
            return null;
        }

        return $this->encontrarPorID($id);
    }
}
