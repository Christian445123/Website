<?php
// Rate limiting – must run before any output
require_once __DIR__ . '/rate_limit.php';

// $pageTitle should be set before including this file
// $pageDescription is optional
$pageTitle = $pageTitle ?? 'Chris_Gaming445';
$pageDescription = $pageDescription ?? 'Die offizielle Website von Chris_Gaming445 – Streamer, Gamer und IT-Enthusiast aus Tirol.';
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
  <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
  <link rel="shortcut icon" href="assets/images/cg-logo-neu.png-496x496.png" type="image/x-icon">
  <title><?= htmlspecialchars($pageTitle) ?> | Chris_Gaming445</title>

  <!-- Bootstrap -->
  <link rel="stylesheet" href="assets/bootstrap/css/bootstrap.min.css">
  <!-- Animate.css -->
  <link rel="stylesheet" href="assets/animatecss/animate.css">
  <!-- Socicon -->
  <link rel="stylesheet" href="assets/socicon/css/styles.css">
  <!-- Mobirise Icons -->
  <link rel="stylesheet" href="assets/web/assets/mobirise-icons/mobirise-icons.css">
  <link rel="stylesheet" href="assets/web/assets/mobirise-icons2/mobirise2.css">
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Tilt+Warp&family=Golos+Text:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/css/custom.css">
</head>
<body>
