<?php
require __DIR__ . "/db.php";

/**
 * Importa un Pokémon desde PokéAPI y lo guarda en MySQL:
 * - pokemon (nombre, altura, peso, imagen, generacion, color)
 * - tipo (si no existe lo crea)
 * - pokemon_tipo (relación N:M)
 *
 * Uso:
 *   /backend/import_pokemon.php?name=pikachu
 *   /backend/import_pokemon.php?name=4
 */

header('Content-Type: application/json; charset=utf-8');

$name = isset($_GET['name']) ? trim($_GET['name']) : '';
if ($name === '') {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Falta parámetro ?name= (nombre o id)"]);
  exit;
}

function http_get_json(string $url): array {
  $ctx = stream_context_create([
    "http" => [
      "method" => "GET",
      "header" => "User-Agent: WTP-DAW-Project\r\n"
    ]
  ]);
  $raw = @file_get_contents($url, false, $ctx);
  if ($raw === false) return [];
  $json = json_decode($raw, true);
  return is_array($json) ? $json : [];
}

$pokemonUrl = "https://pokeapi.co/api/v2/pokemon/" . rawurlencode(mb_strtolower($name)) . "/";
$p = http_get_json($pokemonUrl);
if (!$p) {
  http_response_code(404);
  echo json_encode(["ok" => false, "error" => "No encontrado en PokéAPI", "url" => $pokemonUrl], JSON_UNESCAPED_UNICODE);
  exit;
}

$pokeId = (int)($p["id"] ?? 0);
$pokeName = (string)($p["name"] ?? "");
$heightDm = (int)($p["height"] ?? 0);
$weightHg = (int)($p["weight"] ?? 0);

// Convertimos a unidades “humanas” (m, kg) para tus DECIMAL
$alturaM = $heightDm / 10.0;
$pesoKg = $weightHg / 10.0;

// Imagen (sprite oficial)
$imagen = $p["sprites"]["other"]["official-artwork"]["front_default"]
  ?? $p["sprites"]["front_default"]
  ?? null;

// Tipos (vienen en /pokemon)
$types = [];
if (!empty($p["types"]) && is_array($p["types"])) {
  foreach ($p["types"] as $t) {
    $types[] = (string)($t["type"]["name"] ?? "");
  }
}
$types = array_values(array_filter(array_unique($types)));

// Para color + generación usamos /pokemon-species
$speciesUrl = "https://pokeapi.co/api/v2/pokemon-species/" . $pokeId . "/";
$s = http_get_json($speciesUrl);
$color = $s["color"]["name"] ?? null;

$generacion = null;
if (!empty($s["generation"]["name"])) {
  // gen-i, gen-ii... -> lo pasamos a número simple si podemos
  $g = (string)$s["generation"]["name"];
  $map = [
    "generation-i" => 1,
    "generation-ii" => 2,
    "generation-iii" => 3,
    "generation-iv" => 4,
    "generation-v" => 5,
    "generation-vi" => 6,
    "generation-vii" => 7,
    "generation-viii" => 8,
    "generation-ix" => 9,
  ];
  $generacion = $map[$g] ?? null;
}

try {
  $pdo->beginTransaction();

  // 1) Upsert de pokemon por nombre
  $stmt = $pdo->prepare("SELECT id FROM pokemon WHERE nombre = ?");
  $stmt->execute([$pokeName]);
  $existingId = $stmt->fetchColumn();

  if ($existingId) {
    $pokemonIdDb = (int)$existingId;
    $upd = $pdo->prepare("
      UPDATE pokemon
      SET generacion = ?, color = ?, altura = ?, peso = ?, imagen = ?, fechaUltimaModificacion = NOW()
      WHERE id = ?
    ");
    $upd->execute([$generacion, $color, $alturaM, $pesoKg, $imagen, $pokemonIdDb]);
  } else {
    $ins = $pdo->prepare("
      INSERT INTO pokemon (nombre, generacion, color, altura, peso, imagen, fechaCreacion)
      VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $ins->execute([$pokeName, $generacion, $color, $alturaM, $pesoKg, $imagen]);
    $pokemonIdDb = (int)$pdo->lastInsertId();
  }

  // 2) Tipos + relación
  $typeIds = [];
  foreach ($types as $tname) {
    // tipo existe?
    $st = $pdo->prepare("SELECT id FROM tipo WHERE nombre = ?");
    $st->execute([$tname]);
    $tid = $st->fetchColumn();

    if (!$tid) {
      $it = $pdo->prepare("INSERT INTO tipo (nombre, fechaCreacion) VALUES (?, NOW())");
      $it->execute([$tname]);
      $tid = $pdo->lastInsertId();
    }
    $typeIds[] = (int)$tid;
  }

  // limpiamos relaciones previas y reinsertamos (así queda consistente)
  $del = $pdo->prepare("DELETE FROM pokemon_tipo WHERE idPokemon = ?");
  $del->execute([$pokemonIdDb]);

  $rel = $pdo->prepare("INSERT INTO pokemon_tipo (idPokemon, idTipo) VALUES (?, ?)");
  foreach ($typeIds as $tid) {
    $rel->execute([$pokemonIdDb, $tid]);
  }

  $pdo->commit();

  echo json_encode([
    "ok" => true,
    "importado" => [
      "idDb" => $pokemonIdDb,
      "pokeapiId" => $pokeId,
      "nombre" => $pokeName,
      "generacion" => $generacion,
      "color" => $color,
      "altura_m" => $alturaM,
      "peso_kg" => $pesoKg,
      "imagen" => $imagen,
      "tipos" => $types
    ]
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
