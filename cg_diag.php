<?php
// Temporäres Diagnose-Script – NACH dem Debugging löschen!
if (($_GET['key'] ?? '') !== 'cg_debug_2026') { http_response_code(403); exit('Forbidden'); }

error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain; charset=UTF-8');

echo "=== PHP " . PHP_VERSION . " ===\n\n";

// ── Schritt 1: env.php ────────────────────────────────────────
echo "STEP 1: Loading env.php ... ";
try {
    require_once __DIR__ . '/includes/env.php';
    echo "OK\n";
} catch (Throwable $ex) {
    echo "FAILED: " . $ex->getMessage() . " in " . $ex->getFile() . ":" . $ex->getLine() . "\n";
    exit;
}

// ── Schritt 2: db.php ─────────────────────────────────────────
echo "STEP 2: Loading db.php ... ";
try {
    require_once __DIR__ . '/includes/db.php';
    echo "OK\n";
} catch (Throwable $ex) {
    echo "FAILED: " . $ex->getMessage() . " in " . $ex->getFile() . ":" . $ex->getLine() . "\n";
    exit;
}

// ── Schritt 3: db() aufrufen ──────────────────────────────────
echo "STEP 3: Calling db() ... ";
try {
    $pdo = db();
    echo ($pdo ? "OK\n" : "RETURNED NULL (DB unreachable)\n");
} catch (Throwable $ex) {
    echo "FAILED: " . $ex->getMessage() . " in " . $ex->getFile() . ":" . $ex->getLine() . "\n";
}

// ── Schritt 4: logger.php ─────────────────────────────────────
echo "STEP 4: Loading logger.php ... ";
try {
    require_once __DIR__ . '/includes/logger.php';
    echo "OK\n";
} catch (Throwable $ex) {
    echo "FAILED: " . $ex->getMessage() . " in " . $ex->getFile() . ":" . $ex->getLine() . "\n";
    exit;
}

// ── Schritt 5: rate_limit.php laden OHNE rate_limit_check() ───
echo "STEP 5: Checking rate_limit.php content ... ";
$rl_content = file_get_contents(__DIR__ . '/includes/rate_limit.php');
echo (strpos($rl_content, ': never') !== false ? "HAS 'never' TYPE!\n" : "OK (no 'never')\n");

// ── Schritt 6: auth.php ───────────────────────────────────────
echo "STEP 6: Loading auth.php ... ";
// Fake minimal session so auth.php doesn't crash
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
try {
    require_once __DIR__ . '/includes/auth.php';
    echo "OK\n";
} catch (Throwable $ex) {
    echo "FAILED: " . $ex->getMessage() . " in " . $ex->getFile() . ":" . $ex->getLine() . "\n";
    exit;
}

// ── Schritt 7: head.php simulieren ───────────────────────────
echo "STEP 7: Simulating head.php boot (rate_limit_check) ... ";
try {
    // rate_limit_check() was already registered – test if it would fail
    // We can't safely call it again, but we can check the function exists
    echo (function_exists('rate_limit_check') ? "function exists OK\n" : "MISSING!\n");
} catch (Throwable $ex) {
    echo "FAILED: " . $ex->getMessage() . "\n";
}

// ── Schritt 8: OPcache leeren ─────────────────────────────────
echo "STEP 8: OPcache flush ... ";
echo (function_exists('opcache_reset') && opcache_reset() ? "OK\n" : "n/a\n");

// ── Schritt 9: E_STRICT Konstante ────────────────────────────
echo "STEP 9: E_STRICT constant ... ";
echo (defined('E_STRICT') ? "defined (" . E_STRICT . ")\n" : "NOT defined (removed in PHP 8.4 - OK if patched)\n");

// ── Schritt 10: logger.php bytestamp ─────────────────────────
echo "STEP 10: logger.php content check ... ";
$lc = file_get_contents(__DIR__ . '/includes/logger.php');
echo (strpos($lc, 'E_STRICT)') !== false ? "HAS E_STRICT USAGE - NOT PATCHED!\n" : "E_STRICT not used directly - OK\n");
echo "  logger.php size: " . strlen($lc) . " bytes\n";

// ── Schritt 11: Fatal error check ─────────────────────────────
echo "\nSTEP 11: Last PHP error after all loads:\n";
$err = error_get_last();
echo ($err ? print_r($err, true) : "none\n");

echo "\n=== ALL STEPS DONE ===\n";

