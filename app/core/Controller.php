<?php

namespace App\Core;

abstract class Controller
{
    // Muestra una vista y luego la mete dentro del layout principal
    // Ejemplo: $this->render('home/index', ['title' => 'Inicio']);
    protected function render($view, $data = [])
    {
        // Montamos la ruta del archivo de la vista
        $viewFile = __DIR__ . '/../views/' . $view . '.php';

        // Si no existe la vista, devolvemos error
        if (!is_file($viewFile)) {
            http_response_code(500);
            echo 'View not found';
            return;
        }

        // Si data es array, lo pasamos a variables para la vista
        // Ej: ['title' => 'Hola'] crea $title
        if (is_array($data)) {
            extract($data);
        }

        // Comenzamos a capturar el html de la vista
        ob_start();
        // pintamos el html
        require $viewFile;
        // obtenemos en una variable el html pintado
        $content = ob_get_clean();

        // Cargamos el layout principal (dentro se usa $content)
        require __DIR__ . '/../views/layout/main.php';
    }
}
