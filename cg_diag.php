<?php
// Temporäres Diagnose-Script – NACH dem Debugging löschen!
// Aufruf: https://chrisgaming.at/cg_diag.php?key=cg_debug_2026

if (($_GET['key'] ?? '') !== 'cg_debug_2026') {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: text/plain; charset=UTF-8');

echo "=== PHP VERSION ===\n";
echo PHP_VERSION . "\n\n";

echo "=== LAST PHP ERROR ===\n";
$e = error_get_last();
echo ($e ? print_r($e, true) : "none") . "\n\n";

echo "=== OPCACHE RESET ===\n";
if (function_exists('opcache_reset')) {
    $r = opcache_reset();
    echo $r ? "opcache_reset() OK\n" : "opcache_reset() FAILED\n";
} else {
    echo "OPcache not available\n";
}

echo "\n=== SYNTAX CHECK: includes/db.php ===\n";
$file = __DIR__ . '/includes/db.php';
$content = file_get_contents($file);
// Check for conflict markers
if (strpos($content, '<<<<<<<') !== false || strpos($content, '>>>>>>>') !== false) {
    echo "CONFLICT MARKERS FOUND in db.php!\n";
} else {
    echo "No conflict markers in db.php\n";
}
echo "File size: " . filesize($file) . " bytes\n";

echo "\n=== SYNTAX CHECK: includes/rate_limit.php ===\n";
$rl = file_get_contents(__DIR__ . '/includes/rate_limit.php');
if (strpos($rl, ': never') !== false) {
    echo "DEPRECATED 'never' return type found!\n";
} else {
    echo "No 'never' return type\n";
}

echo "\n=== DB CONNECTION ===\n";
require_once __DIR__ . '/includes/env.php';
$host    = env('DB_HOST', '?');
$name    = env('DB_NAME', '?');
$user    = env('DB_USER', '?');
$port    = env('DB_PORT', 3306);
$charset = env('DB_CHARSET', 'utf8mb4');
echo "Host: $host:$port / DB: $name / User: $user\n";

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";
    $pdo = new PDO($dsn, $user, env('DB_PASS',''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5,
    ]);
    echo "DB Connection: OK\n";

    // Check tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n";
} catch (Throwable $ex) {
    echo "DB Connection FAILED: " . $ex->getMessage() . "\n";
}

echo "\n=== INCLUDE PATH TEST ===\n";
foreach (['env.php','db.php','logger.php','rate_limit.php','auth.php'] as $f) {
    $path = __DIR__ . '/includes/' . $f;
    echo $f . ': ' . (file_exists($path) ? 'EXISTS (' . filesize($path) . ' B)' : 'MISSING') . "\n";
}

echo "\n=== DONE ===\n";
