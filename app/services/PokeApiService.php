<?php

namespace App\Services;

class PokeApiService
{
    // URL base de la PokeAPI
    private const BASE_URL = 'https://pokeapi.co/api/v2/';

    // Función para obtener la lista de especies de pokémon por generación
    public function obtenerEspeciesPorGeneracion(int $generacion): array
    {
        $payload = $this->requestJson('generation/' . $generacion . '/');
        $species = isset($payload['pokemon_species']) && is_array($payload['pokemon_species'])
            ? $payload['pokemon_species']
            : [];

        $names = [];
        // Extraemos solo los nombres de las especies
        foreach ($species as $item) {
            $name = trim((string)($item['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $names[] = $name;
        }

        return $names;
    }

    // Función para obtener los detalles de un pokémon por su nombre
    public function obtenerPokemon(string $nombre): array
    {
        return $this->requestJson('pokemon/' . rawurlencode($nombre) . '/');
    }

    // Función para obtener los detalles de una especie de pokémon por su nombre
    public function obtenerSpecies(string $nombre): array
    {
        return $this->requestJson('pokemon-species/' . rawurlencode($nombre) . '/');
    }

    // Función auxiliar para realizar una solicitud GET a la PokeAPI y decodificar la respuesta JSON
    private function requestJson(string $path): array
    {
        // Construimos la URL completa, configuramos opciones de tiempo de espera y encabezados
        $url = self::BASE_URL . ltrim($path, '/');
        $timeout = 20;
        $headers = "Accept: application/json\r\nUser-Agent: WTP/1.0\r\n";

        // Intentamos usar cURL si está disponible, de lo contrario usamos file_get_contents
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'User-Agent: WTP/1.0',
            ]);
            $raw = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            // Si hubo un error de cURL o la respuesta HTTP no es exitosa, lanzamos una excepción
            if ($raw === false || $status < 200 || $status >= 300) {
                throw new \RuntimeException(
                    'No se pudo consultar PokeAPI (' . $status . '). ' . ($error !== '' ? $error : 'Error HTTP.')
                );
            }

            // Decodificamos la respuesta JSON y verificamos que sea un array
            $decoded = json_decode((string)$raw, true);
            if (!is_array($decoded)) {
                throw new \RuntimeException('Respuesta inválida de PokeAPI.');
            }

            return $decoded;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $timeout,
                'header' => $headers,
                'ignore_errors' => true,
            ],
        ]);

        // Realizamos la solicitud y capturamos el código de estado HTTP
        $raw = @file_get_contents($url, false, $context);
        $status = 0;
        if (isset($http_response_header) && is_array($http_response_header) && isset($http_response_header[0])) {
            if (preg_match('/\s(\d{3})\s/', (string)$http_response_header[0], $matches)) {
                $status = (int)$matches[1];
            }
        }

        // Si la solicitud falló o la respuesta HTTP no es exitosa, lanzamos una excepción
        if ($raw === false || $status < 200 || $status >= 300) {
            throw new \RuntimeException('No se pudo consultar PokeAPI (' . $status . ').');
        }

        // Decodificamos la respuesta JSON y verificamos que sea un array
        $decoded = json_decode((string)$raw, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Respuesta inválida de PokeAPI.');
        }

        return $decoded;
    }
}
