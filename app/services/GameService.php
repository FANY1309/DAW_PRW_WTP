<?php

namespace App\Services;

use App\Models\RetoDiario;
use App\Models\Partida;

class GameService
{
    private RetoDiario $retoModel;
    private Partida $partidaModel;

    public function __construct()
    {
        $this->retoModel = new RetoDiario();
        $this->partidaModel = new Partida();
    }

    // esta función devuelve el reto diario de hoy o, en su defecto, el último reto diario disponible
    public function getTodayChallenge(): ?array
    {
        return $this->retoModel->findTodayActive() ?? $this->retoModel->findAnyActive();
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

        // transformamos los string a comparar en minúsculas para saber si el intento es acertado o no
        $target = strtolower(trim($reto['nombre']));
        $current = strtolower(trim($guess));
        $isCorrect = $target === $current;

        // TODO: implementar auth, usamos un id de usuario ficticio
        $partidaId = $this->partidaModel->saveAttempt(1, (int)$reto['id'], $isCorrect ? 'acierto' : 'fallo');

        return [
            'ok' => true,
            'correcto' => $isCorrect,
            'partidaId' => $partidaId,
            'retoFecha' => $reto['fecha'],
            'pokemon' => $isCorrect ? $reto['nombre'] : null,
            'pista' => $isCorrect ? null : $this->buildHint($reto),
            'message' => $isCorrect ? 'Correcto, adivinaste.' : 'No coincide. Intenta de nuevo.',
        ];
    }

    // Montamos los datos que pasaremos para montar las pistas 
    private function buildHint(array $reto): array
    {
        $tipos = [];
        if (!empty($reto['tipos'])) {
            $tipos = array_values(array_filter(array_map('trim', explode(',', (string)$reto['tipos']))));
        }

        return [
            'generacion' => $reto['generacion'] !== null ? (int)$reto['generacion'] : null,
            'tipo' => $tipos,
            'color' => $reto['color'] !== null ? (string)$reto['color'] : null,
            'altura' => $reto['altura'] !== null ? (float)$reto['altura'] : null,
            'peso' => $reto['peso'] !== null ? (float)$reto['peso'] : null,
        ];
    }
}
