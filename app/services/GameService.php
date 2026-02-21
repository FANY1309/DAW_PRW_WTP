<?php

namespace App\Services;

use App\Models\RetoDiario;

class GameService
{
    private RetoDiario $retoModel;

    public function __construct()
    {
        $this->retoModel = new RetoDiario();
    }

    // esta función devuelve el reto diario de hoy o, en su defecto, el último reto diario disponible
    public function getTodayChallenge(): ?array
    {
        return $this->retoModel->findTodayActive() ?? $this->retoModel->findAnyActive();
    }
}
