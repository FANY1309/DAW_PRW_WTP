<?php

namespace App\Models;

use App\Core\Model;
use Throwable;

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

    // Encontramos un pokémon por su nombre (exacto)
    public function encontrarPorNombre(string $nombre): ?array
    {
        $sql = "
            SELECT id, nombre
            FROM pokemon
            WHERE nombre = :nombre
            LIMIT 1
        ";

        $stmt = $this->db->pdo()->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
        ]);

        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    // Guardamos o actualizamos un pokémon a partir de datos obtenidos desde la API
    public function guardarDesdeApi(array $pokemonData, int $adminUserId): array
    {
        $pdo = $this->db->pdo();
        // Iniciamos una transacción para asegurar la integridad de los datos
        $pdo->beginTransaction();

        try {
            // Validamos y preparamos los datos del pokémon
            $nombre = trim((string)($pokemonData['nombre'] ?? ''));
            if ($nombre === '') {
                throw new \RuntimeException('Nombre de pokemon vacio.');
            }

            // Otros campos opcionales
            $generacion = isset($pokemonData['generacion']) ? (int)$pokemonData['generacion'] : null;
            $color = isset($pokemonData['color']) ? trim((string)$pokemonData['color']) : null;
            $altura = isset($pokemonData['altura']) ? (float)$pokemonData['altura'] : null;
            $peso = isset($pokemonData['peso']) ? (float)$pokemonData['peso'] : null;
            $imagen = isset($pokemonData['imagen']) ? trim((string)$pokemonData['imagen']) : null;
            $tipos = isset($pokemonData['tipos']) && is_array($pokemonData['tipos']) ? $pokemonData['tipos'] : [];

            $existente = $this->encontrarPorNombre($nombre);
            $pokemonId = 0;
            $creado = false;

            // Si el pokémon ya existe, lo actualizamos. Si no, lo creamos.
            if ($existente) {
                $pokemonId = (int)$existente['id'];
                $sqlUpdate = "
                    UPDATE pokemon
                    SET
                        generacion = :generacion,
                        color = :color,
                        altura = :altura,
                        peso = :peso,
                        imagen = :imagen,
                        modificadoPorUsuario = :adminUserId,
                        fechaUltimaModificacion = NOW()
                    WHERE id = :id
                ";

                $stmtUpdate = $pdo->prepare($sqlUpdate);
                $stmtUpdate->execute([
                    ':generacion' => $generacion,
                    ':color' => $color,
                    ':altura' => $altura,
                    ':peso' => $peso,
                    ':imagen' => $imagen,
                    ':adminUserId' => $adminUserId,
                    ':id' => $pokemonId,
                ]);
            } else {
                $creado = true;
                $sqlInsert = "
                    INSERT INTO pokemon (
                        nombre,
                        generacion,
                        color,
                        altura,
                        peso,
                        imagen,
                        creadoPor,
                        fechaCreacion,
                        modificadoPorUsuario,
                        fechaUltimaModificacion
                    ) VALUES (
                        :nombre,
                        :generacion,
                        :color,
                        :altura,
                        :peso,
                        :imagen,
                        :adminUserId,
                        NOW(),
                        :adminUserId,
                        NOW()
                    )
                ";

                $stmtInsert = $pdo->prepare($sqlInsert);
                $stmtInsert->execute([
                    ':nombre' => $nombre,
                    ':generacion' => $generacion,
                    ':color' => $color,
                    ':altura' => $altura,
                    ':peso' => $peso,
                    ':imagen' => $imagen,
                    ':adminUserId' => $adminUserId,
                ]);

                $pokemonId = (int)$pdo->lastInsertId();
            }

            // Actualizamos la relación de tipos del pokémon
            $this->reemplazarTiposPokemon($pokemonId, $tipos, $adminUserId);
            $pdo->commit();

            return [
                'id' => $pokemonId,
                'nombre' => $nombre,
                'creado' => $creado,
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    // Función auxiliar para reemplazar los tipos asociados a un pokémon
    private function reemplazarTiposPokemon(int $pokemonId, array $tipos, int $adminUserId): void
    {
        $pdo = $this->db->pdo();
        $deleteSql = "DELETE FROM pokemon_tipo WHERE idPokemon = :pokemonId";
        $stmtDelete = $pdo->prepare($deleteSql);
        $stmtDelete->execute([
            ':pokemonId' => $pokemonId,
        ]);

        if (count($tipos) === 0) {
            return;
        }

        $insertBridgeSql = "
            INSERT INTO pokemon_tipo (idPokemon, idTipo)
            VALUES (:pokemonId, :tipoId)
        ";
        $stmtBridge = $pdo->prepare($insertBridgeSql);

        foreach ($tipos as $tipoNombre) {
            $tipoNombreNormalizado = strtolower(trim((string)$tipoNombre));
            if ($tipoNombreNormalizado === '') {
                continue;
            }

            $tipoId = $this->obtenerOCrearTipo($tipoNombreNormalizado, $adminUserId);
            $stmtBridge->execute([
                ':pokemonId' => $pokemonId,
                ':tipoId' => $tipoId,
            ]);
        }
    }

    // Función auxiliar para obtener el ID de un tipo por su nombre, o crear el tipo si no existe
    private function obtenerOCrearTipo(string $nombreTipo, int $adminUserId): int
    {
        $pdo = $this->db->pdo();
        $sqlFind = "
            SELECT id
            FROM tipo
            WHERE nombre = :nombre
            LIMIT 1
        ";
        $stmtFind = $pdo->prepare($sqlFind);
        $stmtFind->execute([
            ':nombre' => $nombreTipo,
        ]);
        $fila = $stmtFind->fetch();

        // Si el tipo ya existe, retornamos su ID. Si no, lo creamos y retornamos el nuevo ID.
        if ($fila) {
            $tipoId = (int)$fila['id'];
            $sqlUpdate = "
                UPDATE tipo
                SET modificadoPorUsuario = :adminUserId, fechaUltimaModificacion = NOW()
                WHERE id = :id
            ";
            $stmtUpdate = $pdo->prepare($sqlUpdate);
            $stmtUpdate->execute([
                ':adminUserId' => $adminUserId,
                ':id' => $tipoId,
            ]);
            return $tipoId;
        }

        $sqlInsert = "
            INSERT INTO tipo (
                nombre,
                creadoPor,
                fechaCreacion,
                modificadoPorUsuario,
                fechaUltimaModificacion
            ) VALUES (
                :nombre,
                :adminUserId,
                NOW(),
                :adminUserId,
                NOW()
            )
        ";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([
            ':nombre' => $nombreTipo,
            ':adminUserId' => $adminUserId,
        ]);

        return (int)$pdo->lastInsertId();
    }
}
