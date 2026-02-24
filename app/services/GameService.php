<?php

namespace App\Services;

use App\Models\Partida;
use App\Models\Pokemon;
use App\Models\RetoDiario;

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
        // esta funcion devuelve el reto diario de hoy o, en su defecto, el ultimo reto diario disponible
        return $this->retoModel->findTodayActive() ?? $this->retoModel->findAnyActive();
    }

    // esta función devuelve la lista completa de pokemones para el select del index
    public function getListaPokemons(): array
    {
        return $this->pokemonModel->ListaTodos();
    }

    // Permite al frontend saber si debe bloquear el formulario
    public function hasSolvedTodayChallenge(): bool
    {
        $reto = $this->getTodayChallenge();
        if (!$reto) {
            return false;
        }

        return $this->obtenerResumenRetoResuelto((int)$reto['id']) !== null;
    }

    // Entrega el resumen del reto resuelto hoy (puntos e intentos fallidos) o null.
    public function getResumenIntentoResuelto(): ?array
    {
        // Devuelve un resumen del reto resuelto hoy para pintar mensaje/puntos al recargar
        $reto = $this->getTodayChallenge();
        if (!$reto) {
            return null;
        }

        return $this->obtenerResumenRetoResuelto((int)$reto['id']);
    }

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

        $retoId = (int)$reto['id'];
        // Si ya fue resuelto (usuario o invitado), bloqueamos nuevos intentos
        $summary = $this->obtenerResumenRetoResuelto($retoId);
        if ($summary !== null) {
            return [
                'ok' => false,
                'alreadySolved' => true,
                'message' => 'Ya acertaste el reto de hoy. Vuelve manana para un nuevo reto.',
                'puntos' => (int)$summary['puntos'],
                'intentosFallidosAntesDelAcierto' => (int)$summary['intentosFallidosAntesDelAcierto'],
            ];
        }

        $target = strtolower(trim((string)$reto['nombre']));
        $current = strtolower(trim($guess));
        $isCorrect = $target === $current;

        $partidaId = null;
        $intentosFallidosPrevios = $this->obtenerIntentosFallidosActuales($retoId);
        $intentosFallidos = $intentosFallidosPrevios;
        $puntos = null;

        if ($this->puedeGuardarIntentos()) {
            // Si es usuario autenticado guardamos cada intento en la tabla partida
            $partidaId = $this->partidaModel->saveAttempt(
                $this->usuarioId,
                $retoId,
                $isCorrect ? 'acierto' : 'fallo'
            );

            if (!$isCorrect) {
                $intentosFallidos = $intentosFallidosPrevios + 1;
            }
        } elseif (!$isCorrect) {
            // Invitado: los fallos se guardan en sesion para pistas progresivas
            $intentosFallidos = $this->registrarIntentosFallidosInvitado($retoId);
        }

        if ($isCorrect) {
            // El puntaje depende de cuantas veces fallo antes de acertar
            $puntos = $this->calcularPuntosPorAcierto($intentosFallidosPrevios);
            if (!$this->puedeGuardarIntentos()) {
                // Invitado: guardamos en sesion que ya resolvio y su puntaje del dia
                $this->guardarResolucionInvitado($retoId, $puntos, $intentosFallidosPrevios);
            }
        }

        return [
            'ok' => true,
            'correcto' => $isCorrect,
            'partidaId' => $partidaId,
            'intentosFallidos' => $intentosFallidos,
            'retoFecha' => $reto['fecha'],
            'pokemon' => $isCorrect ? $reto['nombre'] : null,
            'pista' => $isCorrect ? null : $this->montarPistas($reto, $intentosFallidos),
            'puntos' => $puntos,
            'intentosFallidosAntesDelAcierto' => $isCorrect ? $intentosFallidosPrevios : null,
            'message' => $isCorrect ? 'Correcto, adivinaste.' : 'No coincide. Intenta de nuevo.',
            'persisted' => $this->puedeGuardarIntentos(),
        ];
    }

    private function puedeGuardarIntentos(): bool
    {
        // Verifica si el usuario actual tiene permisos para guardar intentos en la base de datos
        if (!$this->usuarioId || !$this->userRole) {
            return false;
        }

        return in_array($this->userRole, ['admin', 'usuario'], true);
    }

    private function registrarIntentosFallidosInvitado(int $retoId): int
    {
        // Para usuarios no autenticados, registramos los intentos fallidos en la sesion para poder mostrar pistas progresivas
        $this->asegurarSesionInvitado($retoId);
        $_SESSION['guest_attempts'][$retoId]['failed']++;
        return (int)$_SESSION['guest_attempts'][$retoId]['failed'];
    }

    private function guardarResolucionInvitado(int $retoId, int $puntos, int $intentosFallidosPrevios): void
    {
        // Guarda estado final del invitado para bloquear nuevos intentos y mostrar puntos al recargar
        $this->asegurarSesionInvitado($retoId);
        $_SESSION['guest_attempts'][$retoId]['solved'] = true;
        $_SESSION['guest_attempts'][$retoId]['points'] = $puntos;
        $_SESSION['guest_attempts'][$retoId]['failed_before_success'] = $intentosFallidosPrevios;
    }

    private function asegurarSesionInvitado(int $retoId): void
    {
        // Estructura base en sesion por reto para invitados
        if (!isset($_SESSION['guest_attempts']) || !is_array($_SESSION['guest_attempts'])) {
            $_SESSION['guest_attempts'] = [];
        }

        if (!isset($_SESSION['guest_attempts'][$retoId]) || !is_array($_SESSION['guest_attempts'][$retoId])) {
            $_SESSION['guest_attempts'][$retoId] = [
                'failed' => 0,
                'solved' => false,
                'points' => null,
                'failed_before_success' => 0,
            ];
        }
    }

    private function obtenerIntentosFallidosActuales(int $retoId): int
    {
        // Obtiene la cantidad actual de fallos para el reto activo
        if ($this->puedeGuardarIntentos()) {
            return $this->partidaModel->countFailedAttempts($this->usuarioId, $retoId);
        }

        if (!isset($_SESSION['guest_attempts'][$retoId]['failed'])) {
            return 0;
        }

        return (int)$_SESSION['guest_attempts'][$retoId]['failed'];
    }

    private function obtenerResumenRetoResuelto(int $retoId): ?array
    {
        // Devuelve resumen cuando el reto ya fue resuelto por usuario o invitado
        if ($this->puedeGuardarIntentos()) {
            if (!$this->partidaModel->hasCorrectAttempt($this->usuarioId, $retoId)) {
                return null;
            }

            $fallidos = $this->partidaModel->countFailedAttempts($this->usuarioId, $retoId);
            return [
                'puntos' => $this->calcularPuntosPorAcierto($fallidos),
                'intentosFallidosAntesDelAcierto' => $fallidos,
            ];
        }

        if (
            !isset($_SESSION['guest_attempts'][$retoId]) ||
            !is_array($_SESSION['guest_attempts'][$retoId]) ||
            empty($_SESSION['guest_attempts'][$retoId]['solved'])
        ) {
            return null;
        }

        $fallidos = (int)($_SESSION['guest_attempts'][$retoId]['failed_before_success'] ?? 0);
        $pointsInSession = $_SESSION['guest_attempts'][$retoId]['points'] ?? null;
        $puntos = is_numeric($pointsInSession)
            ? (int)$pointsInSession
            : $this->calcularPuntosPorAcierto($fallidos);

        return [
            'puntos' => $puntos,
            'intentosFallidosAntesDelAcierto' => $fallidos,
        ];
    }

    private function calcularPuntosPorAcierto(int $intentosFallidosPrevios): int
    {
        // Formula base: menos puntos cuantos mas fallos hubo antes del acierto
        $base = 100;
        $penalizacionPorFallo = 15;
        $minimo = 10;

        return max($minimo, $base - ($intentosFallidosPrevios * $penalizacionPorFallo));
    }

    private function montarPistas(array $reto, int $intentosFallidos): array
    {
        // Monta las pistas progresivas en base al numero de intentos fallidos
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
        // solo mostramos el numero de pistas según el número de intentos del usuario
        // Ejemplo: si intentosFallidos=2, solo mostramos las 2 primeras pistas del arreglo
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
