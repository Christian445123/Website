<?php
/**
 * Logout
 * Nur per POST mit gültigem CSRF-Token – ein GET-Link könnte sonst von jeder
 * fremden Seite (z. B. <img src="…/logout.php">) ausgelöst werden.
 */
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid()) {
    auth_logout(cg_url('/'));
    exit;
}

// Direkter Aufruf (GET) oder ungültiges Token: nichts tun, zurück zur Startseite.
header('Location: ' . cg_url('/'));
exit;
