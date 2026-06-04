<?php
/* =====================================================================
 *  Migración de procedimientos VIEJOS hacia la tabla puente.
 *
 *  Copia la columna JSON `eventualidades.procedimientos` (formato viejo)
 *  a `eventualidad_tiene_procedimiento`, matcheando por NOMBRE contra el
 *  catálogo `procedimiento` y mapeando  ✔ -> realizado  /  * -> no_realizado.
 *
 *  No usa Laravel ni el .env (PDO directo): completá las credenciales de
 *  PRODUCCIÓN abajo. Pensado para correr con php7.0 (o cualquier PHP con PDO).
 *
 *  USO:
 *    php7.0 02_migrar_procedimientos_viejos.php            # DRY-RUN (no escribe, sólo reporta)
 *    php7.0 02_migrar_procedimientos_viejos.php --commit   # escribe de verdad
 *
 *  ES IDEMPOTENTE: saltea las eventualidades que YA tienen filas en la
 *  puente, y además usa INSERT IGNORE. Se puede correr varias veces.
 *
 *  REQUISITO: correr ANTES 01_esquema_eventualidades.sql (necesita el catálogo seedeado).
 * ===================================================================== */

$DB_HOST = 'CAMBIAR_HOST_PROD';
$DB_NAME = 'CAMBIAR_BASE_PROD';
$DB_USER = 'CAMBIAR_USER_PROD';
$DB_PASS = 'CAMBIAR_PASS_PROD';

$commit = in_array('--commit', $argv, true);

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER, $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Throwable $e) {
    fwrite(STDERR, "No se pudo conectar a la base: " . $e->getMessage() . "\n");
    exit(1);
}

// Catálogo: nombre -> id_procedimiento
$catalogo = [];
foreach ($pdo->query("SELECT id_procedimiento, nombre FROM procedimiento") as $r) {
    $catalogo[trim($r['nombre'])] = (int) $r['id_procedimiento'];
}
if (!$catalogo) {
    fwrite(STDERR, "El catálogo `procedimiento` está vacío. Corré antes 01_esquema_eventualidades.sql.\n");
    exit(1);
}

// Eventualidades que YA tienen datos en la puente (para saltearlas).
$yaMigrados = [];
foreach ($pdo->query("SELECT DISTINCT id_eventualidades FROM eventualidad_tiene_procedimiento") as $r) {
    $yaMigrados[(int) $r['id_eventualidades']] = true;
}

$mapEstado = ['✔' => 'realizado', '*' => 'no_realizado'];

$ins = $pdo->prepare(
    "INSERT IGNORE INTO eventualidad_tiene_procedimiento
     (id_eventualidades, id_procedimiento, estado, observacion) VALUES (?, ?, ?, ?)"
);

$evs = $pdo->query(
    "SELECT id_eventualidades, procedimientos FROM eventualidades
     WHERE procedimientos IS NOT NULL AND procedimientos NOT IN ('', '[]', 'null')"
);

$nEv = 0; $nSkip = 0; $nIns = 0; $sinMatch = []; $jsonMal = [];

foreach ($evs as $row) {
    $idEv = (int) $row['id_eventualidades'];
    if (isset($yaMigrados[$idEv])) { $nSkip++; continue; }

    // El JSON viejo viene doble-encodeado (bug histórico): decodificar dos veces si hace falta.
    $arr = json_decode($row['procedimientos'], true);
    if (is_string($arr)) $arr = json_decode($arr, true);
    if (!is_array($arr)) { $jsonMal[] = $idEv; continue; }

    $nEv++;
    foreach ($arr as $p) {
        $nombre = trim($p['procedimiento'] ?? '');
        if ($nombre === '') continue; // entradas en blanco del JSON viejo

        if (!isset($catalogo[$nombre])) {
            $sinMatch[$nombre] = ($sinMatch[$nombre] ?? 0) + 1;
            continue;
        }
        $idProc = $catalogo[$nombre];
        $estado = $mapEstado[$p['estado'] ?? ''] ?? null;
        $obs    = isset($p['observacion']) ? $p['observacion'] : null;

        if ($commit) {
            $ins->execute([$idEv, $idProc, $estado, $obs]);
            $nIns += $ins->rowCount();         // 1 si insertó, 0 si IGNORE (duplicado)
        } else {
            $nIns++;                           // dry-run: contamos las que se insertarían
        }
    }
}

echo $commit
    ? "===== COMMIT (escribiendo) =====\n"
    : "===== DRY-RUN (no escribe; agregá --commit para aplicar) =====\n";
echo "Eventualidades a migrar (sin datos previos en la puente): {$nEv}\n";
echo "Eventualidades salteadas (ya tenían datos en la puente):  {$nSkip}\n";
echo "Filas " . ($commit ? "insertadas" : "que se insertarían") . ": {$nIns}\n";

if ($jsonMal) {
    echo "\n⚠ JSON ilegible en estas eventualidades (revisar a mano): " . implode(', ', $jsonMal) . "\n";
}
if ($sinMatch) {
    echo "\n⚠ NOMBRES SIN MATCH en el catálogo (REVISAR antes de commitear):\n";
    foreach ($sinMatch as $n => $c) echo "   - '{$n}'  (x{$c})\n";
    echo "  -> Si aparecen, NO commitees: revisá que el seed del catálogo (01_*.sql)\n";
    echo "     tenga exactamente esos nombres, o agregalos.\n";
} else {
    echo "\n✅ Todos los procedimientos matchean con el catálogo.\n";
}
echo "\nListo.\n";
