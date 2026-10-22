<?php
/**
 * Security helpers
 * ----------------
 *  - HTTP security headers (CSP, COOP, …) – gesendet bei jedem PHP-Request
 *  - Client-IP-Erkennung (Proxy-Header nur von vertrauenswürdigen Proxies)
 *  - CSRF-Token (Session-gebunden)
 *  - Sichere Redirect-Pfade, Escape-Helper
 *
 * Wird von db.php/head.php eingebunden, bevor irgendeine Ausgabe passiert.
 */

declare(strict_types=1);

// ═══════════════════════════════════════════════════════════════
//  HTTP-Header
// ═══════════════════════════════════════════════════════════════

function cg_send_security_headers(): void
{
    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }

    $csp = implode('; ', [
        "default-src 'self'",
        // GA wird erst nach Einwilligung nachgeladen (custom.js)
        "script-src 'self' https://www.googletagmanager.com",
        "style-src 'self' 'unsafe-inline'",
        "font-src 'self'",
        "img-src 'self' data: https://*.google-analytics.com https://*.googletagmanager.com",
        "connect-src 'self' https://*.google-analytics.com https://*.analytics.google.com https://*.googletagmanager.com",
        "frame-ancestors 'none'",
        "base-uri 'self'",
        "form-action 'self'",
        "object-src 'none'",
        'upgrade-insecure-requests',
    ]);

    header('Content-Security-Policy: ' . $csp);
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=(), interest-cohort=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-origin');
    header('X-Permitted-Cross-Domain-Policies: none');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    header_remove('X-Powered-By');
}

/** Seiten mit Account-Bezug dürfen nicht gecacht oder indexiert werden. */
function cg_send_private_headers(): void
{
    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('Pragma: no-cache');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
}

cg_send_security_headers();

// ═══════════════════════════════════════════════════════════════
//  Client-IP
// ═══════════════════════════════════════════════════════════════

/** true, wenn $ip privat/loopback/link-local ist (also ein lokaler Reverse-Proxy). */
function cg_ip_is_private(string $ip): bool
{
    return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) === false;
}

/** Offizielle Cloudflare-Netze (https://www.cloudflare.com/ips/). */
function cg_ip_is_cloudflare(string $ip): bool
{
    static $ranges = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
        '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
        '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
        '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
    ];
    foreach ($ranges as $cidr) {
        if (cg_ip_in_cidr($ip, $cidr)) {
            return true;
        }
    }
    return false;
}

function cg_ip_in_cidr(string $ip, string $cidr): bool
{
    [$subnet, $bits] = explode('/', $cidr) + [1 => ''];
    $ipBin  = @inet_pton($ip);
    $subBin = @inet_pton($subnet);
    if ($ipBin === false || $subBin === false || strlen($ipBin) !== strlen($subBin)) {
        return false;
    }
    $bits  = (int) $bits;
    $bytes = intdiv($bits, 8);
    $rest  = $bits % 8;
    if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subBin, 0, $bytes)) {
        return false;
    }
    if ($rest === 0) {
        return true;
    }
    $mask = (0xFF << (8 - $rest)) & 0xFF;
    return (ord($ipBin[$bytes]) & $mask) === (ord($subBin[$bytes]) & $mask);
}

/**
 * Ermittelt die echte Client-IP.
 *
 * Forwarding-Header werden NUR akzeptiert, wenn die Verbindung von einem
 * vertrauenswürdigen Proxy kommt (Cloudflare oder lokaler Reverse-Proxy).
 * Sonst könnte jeder Besucher per "X-Forwarded-For: 1.2.3.4" das Rate-Limit
 * umgehen und Logs fälschen.
 */
function cg_client_ip(): string
{
    static $ip = null;
    if ($ip !== null) {
        return $ip;
    }

    $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $ip     = filter_var($remote, FILTER_VALIDATE_IP) ? $remote : '0.0.0.0';

    if (cg_ip_is_cloudflare($ip)) {
        $cf = trim((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
        if (filter_var($cf, FILTER_VALIDATE_IP)) {
            return $ip = $cf;
        }
    } elseif (cg_ip_is_private($ip)) {
        $real = trim((string) ($_SERVER['HTTP_X_REAL_IP'] ?? ''));
        if (filter_var($real, FILTER_VALIDATE_IP)) {
            return $ip = $real;
        }
        // X-Forwarded-For: rechtester öffentlicher Eintrag (vom eigenen Proxy angehängt)
        $xff = array_reverse(array_map('trim', explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''))));
        foreach ($xff as $cand) {
            if (filter_var($cand, FILTER_VALIDATE_IP) && !cg_ip_is_private($cand)) {
                return $ip = $cand;
            }
        }
    }

    return $ip;
}

// ═══════════════════════════════════════════════════════════════
//  CSRF
// ═══════════════════════════════════════════════════════════════

function csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return '';
    }
    if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/** Hidden-Input für <form method="POST">. */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_valid(): bool
{
    $sent = (string) ($_POST['_csrf'] ?? '');
    $real = (string) ($_SESSION['_csrf'] ?? '');
    return $sent !== '' && $real !== '' && hash_equals($real, $sent);
}

/** Bricht bei ungültigem Token mit 403 ab. Für alle POST-Handler aufrufen. */
function csrf_require(): void
{
    if (csrf_valid()) {
        return;
    }
    http_response_code(403);
    exit(
        '<!DOCTYPE html><html lang="de"><head><meta charset="UTF-8"><title>403</title></head>' .
        '<body style="font-family:sans-serif;text-align:center;padding:4rem;background:#090a0c;color:#eaeaef">' .
        '<h1>403 &ndash; Ungültige Anfrage</h1>' .
        '<p>Das Sicherheits-Token ist abgelaufen. Bitte lade die Seite neu und versuche es erneut.</p>' .
        '</body></html>'
    );
}

// ═══════════════════════════════════════════════════════════════
//  Sonstiges
// ═══════════════════════════════════════════════════════════════

/** Kurzform für htmlspecialchars. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Lässt nur lokale Pfade ("/foo?bar") als Redirect-Ziel zu.
 * Blockt "//evil.com", "/\evil.com", "https://…", Steuerzeichen.
 */
function cg_safe_redirect(?string $target, string $fallback = '/'): string
{
    $target = (string) $target;
    if (
        $target === '' ||
        $target[0] !== '/' ||
        str_starts_with($target, '//') ||
        str_contains($target, '\\') ||
        preg_match('/[\x00-\x1F\x7F]/', $target)
    ) {
        return $fallback;
    }
    return $target;
}

/** true, wenn $url ein http(s)-Link ist. */
function cg_is_http_url(string $url): bool
{
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    return $scheme === 'http' || $scheme === 'https';
}

/** Erlaubt nur CSS-Farben (#hex, rgb[a](), hsl[a](), Namen). */
function cg_clean_css_color(string $value, string $fallback): string
{
    $value = trim($value);
    if (
        preg_match('/^#[0-9a-fA-F]{3,8}$/', $value) ||
        preg_match('/^(rgb|hsl)a?\(\s*[0-9.,%\s\/]+\)$/i', $value) ||
        preg_match('/^[a-zA-Z]{3,20}$/', $value)
    ) {
        return $value;
    }
    return $fallback;
}

/** Erlaubt nur Font-Awesome-artige Klassen ("fas fa-star"). */
function cg_clean_icon_class(string $value, string $fallback = 'fas fa-star'): string
{
    $value = trim($value);
    return preg_match('/^[a-z0-9]+( [a-z0-9-]+){0,3}$/', $value) ? $value : $fallback;
}
