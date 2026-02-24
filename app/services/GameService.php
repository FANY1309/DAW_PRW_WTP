<?php

namespace App\Services;

use App\Models\RetoDiario;
use App\Models\Partida;
use App\Models\Pokemon;

class GameService
{
    private ?int $usuarioId = null;
    private ?string $userRole = null;
    private RetoDiario $retoModel;
    private Partida $partidaModel;
    private Pokemon $pokemonModel;

    public function __construct()
    {
        // Lee datos de autenticacion de la sesion
        $auth = $_SESSION['auth'] ?? null;
        if (is_array($auth)) {
            // Toma el id del usuario solo si viene como entero valido mayor que 0
            $candidateId = (int)($auth['id'] ?? 0);
            if ($candidateId > 0) {
                $this->usuarioId = $candidateId;
            }

            // Normaliza el rol en minúsculas; si no existe, se mantiene null
            $role = trim((string)($auth['rol'] ?? ''));
            $this->userRole = $role !== '' ? strtolower($role) : null;
        }

        $this->retoModel = new RetoDiario();
        $this->partidaModel = new Partida();
        $this->pokemonModel = new Pokemon();
    }

    // esta función devuelve el reto diario de hoy o, en su defecto, el último reto diario disponible
    public function getTodayChallenge(): ?array
    {
        return $this->retoModel->findTodayActive() ?? $this->retoModel->findAnyActive();
    }

    // esta función devuelve la lista completa de pokemones para el select del index
    public function getListaPokemons(): array
    {
        return $this->pokemonModel->ListaTodos();
    }

    // Consulta rápida para el frontend: permite bloquear el formulario al entrar al index.
    public function hasSolvedTodayChallenge(): bool
    {
        if (!$this->puedeGuardarIntentos()) {
            return false;
        }

        $reto = $this->getTodayChallenge();
        if (!$reto) {
            return false;
        }

        return $this->partidaModel->hasCorrectAttempt($this->usuarioId, (int)$reto['id']);
    }

    // esta función ejecuta el intento al darle al botón "Probar"
    public function attempt(string $guess): array
    {
        // extraemos el reto diario
        $reto = $this->getTodayChallenge();

        // si no existe reto diario, no se hace nada
        if (!$reto) {
            return [
                'ok' => false,
                'message' => 'No hay reto activo.',
            ];
        }

        // Usamos el id del reto activo para comprobar histórico de partidas del usuario.
        $retoId = (int)$reto['id'];

        // transformamos los string a comparar en minúsculas para saber si el intento es acertado o no
        $target = strtolower(trim($reto['nombre']));
        $current = strtolower(trim($guess));
        $isCorrect = $target === $current;

        $partidaId = null;
        $intentosFallidos = 0;

        if ($this->puedeGuardarIntentos()) {
            $partidaId = $this->partidaModel->saveAttempt($this->usuarioId, $retoId, $isCorrect ? 'acierto' : 'fallo');
            $intentosFallidos = $isCorrect ? 0 : $this->partidaModel->countFailedAttempts($this->usuarioId, $retoId);
        } else {
            $intentosFallidos = $isCorrect ? 0 : $this->registrarIntentosFallidosInvitado($retoId);
        }

        return [
            'ok' => true,
            'correcto' => $isCorrect,
            'partidaId' => $partidaId,
            'retoFecha' => $reto['fecha'],
            'pokemon' => $isCorrect ? $reto['nombre'] : null,
            'pista' => $isCorrect ? null : $this->montarPistas($reto, $intentosFallidos),
            'message' => $isCorrect ? 'Correcto, adivinaste.' : 'No coincide. Intenta de nuevo.',
            'persisted' => $this->puedeGuardarIntentos(),
        ];
    }

    // Verifica si el usuario actual tiene permisos para guardar intentos en la base de datos
    private function puedeGuardarIntentos(): bool
    {
        if (!$this->usuarioId || !$this->userRole) {
            return false;
        }

        return in_array($this->userRole, ['admin', 'usuario'], true);
    }

    // Para usuarios no autenticados, registramos los intentos fallidos en la sesión para poder mostrar pistas progresivas
    private function registrarIntentosFallidosInvitado(int $retoId): int
    {
        if (!isset($_SESSION['guest_attempts']) || !is_array($_SESSION['guest_attempts'])) {
            $_SESSION['guest_attempts'] = [];
        }

        if (!isset($_SESSION['guest_attempts'][$retoId])) {
            $_SESSION['guest_attempts'][$retoId] = [
                'failed' => 0,
            ];
        }

        $_SESSION['guest_attempts'][$retoId]['failed']++;
        return (int)$_SESSION['guest_attempts'][$retoId]['failed'];
    }

    // Montamos los datos que pasaremos para montar las pistas 
    private function montarPistas(array $reto, int $intentosFallidos): array
    {
        $tipos = [];
        if (!empty($reto['tipos'])) {
            $tipos = array_values(array_filter(array_map('trim', explode(',', (string)$reto['tipos']))));
        }

        // Orden fijo de pistas progresivas:
        // 1 - generacion
        // 2 - tipo, 
        // 3 - color, 
        // 4 - altura, 
        // 5 - peso, 
        // 6 - silueta
        $ordenPistas = [
            'generacion' => $reto['generacion'] !== null ? (int)$reto['generacion'] : null,
            'tipo' => $tipos,
            'color' => $reto['color'] !== null ? (string)$reto['color'] : null,
            'altura' => $reto['altura'] !== null ? (float)$reto['altura'] : null,
            'peso' => $reto['peso'] !== null ? (float)$reto['peso'] : null,
            'silueta' => $reto['imagen'] !== null ? (string)$reto['imagen'] : null,
        ];

        $pistasVisibles = [];
        // solo mostramos el numero de pistas según el número de intentos del usuario.
        // Ejemplo: si intentosFallidos=2, solo mostramos las 2 primeras pistas del arreglo.
        $maxPistas = min($intentosFallidos, count($ordenPistas));
        $index = 0;

        foreach ($ordenPistas as $key => $value) {
            // si llega el numero máximo de pistas, sale del bucle
            if ($index >= $maxPistas) {
                break;
            }
            $pistasVisibles[$key] = $value;
            $index++;
        }

        return [
            'intentosFallidos' => $intentosFallidos,
            'datos' => $pistasVisibles,
        ];
    }
}



