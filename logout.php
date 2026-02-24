<?php
/**
 * Logout
 * Beendet die Session und leitet zur Startseite weiter.
 */
require_once __DIR__ . '/includes/auth.php';

auth_logout('/');
