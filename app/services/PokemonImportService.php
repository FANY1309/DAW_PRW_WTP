<?php

namespace App\Services;

use App\Models\Pokemon;
use Throwable;

class PokemonImportService
{
    private PokeApiService $pokeApi;
    private Pokemon $pokemonModel;

    public function __construct()
    {
        $this->pokeApi = new PokeApiService();
        $this->pokemonModel = new Pokemon();
    }

    // Sincronizamos los pokemones de una generación específica obtenidos desde la PokeAPI
    public function sincronizarPorGeneracion(int $generacion, int $adminUserId): array
    {
        // Obtenemos la lista de nombres de especies de pokémon para la generación indicada
        $speciesNames = $this->pokeApi->obtenerEspeciesPorGeneracion($generacion);

        $creados = 0;
        $actualizados = 0;
        $errores = [];

        // Procesamos cada especie de pokémon, obtenemos sus detalles y los guardamos o actualizamos en la base de datos
        foreach ($speciesNames as $speciesName) {
            try {
                $pokemon = $this->pokeApi->obtenerPokemon($speciesName);
                $species = $this->pokeApi->obtenerSpecies($speciesName);
                $payload = $this->mapearPokemon($pokemon, $species, $generacion);
                $resultado = $this->pokemonModel->guardarDesdeApi($payload, $adminUserId);

                if (!empty($resultado['creado'])) {
                    $creados++;
                } else {
                    $actualizados++;
                }
            } catch (Throwable $e) {
                $errores[] = [
                    'pokemon' => $speciesName,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'generacion' => $generacion,
            'totalProcesados' => count($speciesNames),
            'creados' => $creados,
            'actualizados' => $actualizados,
            'errores' => $errores,
        ];
    }

    // Función auxiliar para mapear los datos obtenidos desde la PokeAPI a un formato adecuado para guardar en la base de datos
    private function mapearPokemon(array $pokemon, array $species, int $generacion): array
    {
        $types = [];
        // Extraemos los tipos del pokémon, asegurándonos de que sean válidos y no estén vacíos
        $rawTypes = isset($pokemon['types']) && is_array($pokemon['types']) ? $pokemon['types'] : [];
        foreach ($rawTypes as $item) {
            $typeName = trim((string)($item['type']['name'] ?? ''));
            if ($typeName === '') {
                continue;
            }
            $types[] = $typeName;
        }

        // Mapeamos los datos del pokémon, aplicando validaciones y transformaciones necesarias
        $nombre = trim((string)($pokemon['name'] ?? ''));
        $altura = isset($pokemon['height']) ? ((float)$pokemon['height']) / 10 : null;
        $peso = isset($pokemon['weight']) ? ((float)$pokemon['weight']) / 10 : null;

        // Intentamos obtener la imagen oficial del pokémon, si no está disponible, usamos la imagen frontal por defecto
        $imagen = trim((string)($pokemon['sprites']['other']['official-artwork']['front_default'] ?? ''));
        if ($imagen === '') {
            $imagen = trim((string)($pokemon['sprites']['front_default'] ?? ''));
        }

        // Extraemos el color de la especie del pokémon, si no está disponible, lo dejamos como null
        $color = trim((string)($species['color']['name'] ?? ''));
        if ($color === '') {
            $color = null;
        }

        return [
            'nombre' => $nombre,
            'generacion' => $generacion,
            'color' => $color,
            'altura' => $altura,
            'peso' => $peso,
            'imagen' => $imagen !== '' ? $imagen : null,
            'tipos' => $types,
        ];
    }
}
