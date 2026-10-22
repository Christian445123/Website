<?php
// Ausgabe puffern: Seiten leiten nach POST per header('Location') weiter bzw. rotieren die
// Session-ID, nachdem head.php schon HTML geschrieben hat. Ohne Puffer würde das scheitern.
if (ob_get_level() === 0) {
    ob_start();
}

// Boot order: security → env → db → logger → rate_limit → auth
// rate_limit.php requires logger.php + db.php (which requires security.php + env.php)
require_once __DIR__ . '/rate_limit.php';  // DB-backed rate limiting (lädt auch logger.php)
require_once __DIR__ . '/auth.php';        // Session bootstrap + login helpers

// Seiten mit Account-Bezug: nicht cachen, nicht indexieren
if (!empty($noindex) || isset($_SESSION['user_id'])) {
    cg_send_private_headers();
}

// $pageTitle should be set before including this file
// $pageDescription is optional, $noindex = true für Login/Registrierung/Admin
$pageTitle = $pageTitle ?? 'Chris_Gaming445';
$pageDescription = $pageDescription ?? 'Die offizielle Website von Chris_Gaming445 – Streamer, Gamer und IT-Enthusiast aus Tirol.';
$assetVer = static function (string $path): string {
    $file = dirname(__DIR__) . $path;
    return $path . '?v=' . (is_file($file) ? filemtime($file) : '1');
};
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
  <meta name="description" content="<?= e($pageDescription) ?>">
  <meta name="theme-color" content="#090a0c">
  <meta name="color-scheme" content="dark">
  <?php if (!empty($noindex)): ?>
  <meta name="robots" content="noindex, nofollow">
  <?php endif; ?>
  <meta property="og:type" content="website">
  <meta property="og:site_name" content="Chris_Gaming445">
  <meta property="og:title" content="<?= e($pageTitle) ?> | Chris_Gaming445">
  <meta property="og:description" content="<?= e($pageDescription) ?>">
  <meta property="og:image" content="https://chrisgaming.at/assets/images/cg-logo-neu.png-800x800.png">
  <link rel="icon" href="/assets/images/cg-logo-neu.png-496x496.png" type="image/png">
  <title><?= e($pageTitle) ?> | Chris_Gaming445</title>

  <!-- Bootstrap -->
  <link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">
  <!-- Fonts & Icons (selbst gehostet – keine Verbindung zu Google/Cloudflare-CDNs) -->
  <link rel="preload" href="/assets/fonts/golos-latin.woff2" as="font" type="font/woff2" crossorigin>
  <link rel="stylesheet" href="/assets/fonts/fonts.css">
  <link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="<?= e($assetVer('/assets/css/custom.css')) ?>">
</head>
<body>
<a class="cg-skip-link" href="#main">Zum Inhalt springen</a>
