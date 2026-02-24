<?php

namespace App\Services;

use App\Models\RetoDiario;
use App\Models\Partida;
use App\Models\Pokemon;

class GameService
{
    private int $usuarioId = 1; // TODO: reemplazar al implementar auth
    private RetoDiario $retoModel;
    private Partida $partidaModel;
    private Pokemon $pokemonModel;

    public function __construct()
    {
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

        // si ya acerto este reto, bloqueamos nuevos envíos
        if ($this->partidaModel->hasCorrectAttempt($this->usuarioId, $retoId)) {
            return [
                'ok' => false,
                'alreadySolved' => true, // Flag consumido por frontend/controlador para bloqueo y mensaje.
                'message' => 'Ya acertaste el reto de hoy. Vuelve mañana para un nuevo reto.',
            ];
        }

        // transformamos los string a comparar en minúsculas para saber si el intento es acertado o no
        $target = strtolower(trim($reto['nombre']));
        $current = strtolower(trim($guess));
        $isCorrect = $target === $current;

        // Guardamos el intento SIEMPRE, sea acierto o fallo
        $partidaId = $this->partidaModel->saveAttempt($this->usuarioId, $retoId, $isCorrect ? 'acierto' : 'fallo');
        // Solo contamos fallos cuando realmente falló
        $intentosFallidos = $isCorrect ? 0 : $this->partidaModel->countFailedAttempts($this->usuarioId, $retoId);

        return [
            'ok' => true,
            'correcto' => $isCorrect,
            'partidaId' => $partidaId,
            'retoFecha' => $reto['fecha'],
            'pokemon' => $isCorrect ? $reto['nombre'] : null,
            'pista' => $isCorrect ? null : $this->montarPistas($reto, $intentosFallidos),
            'message' => $isCorrect ? 'Correcto, adivinaste.' : 'No coincide. Intenta de nuevo.',
        ];
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



