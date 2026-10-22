<?php
/**
 * i18n – Deutsch (Quellsprache) / Englisch
 * ----------------------------------------
 * Die Seiten bleiben deutsch geschrieben. Für Englisch wird die fertige HTML-Ausgabe
 * segmentweise anhand von lang/en.php übersetzt (Text zwischen Block-Tags inkl. Inline-Tags
 * sowie Attribute wie placeholder/alt/title/aria-label/content).
 *
 * lang/en.php wird mit scripts/i18n_build.php (DeepL, Key nur lokal in .deepl_key)
 * erzeugt – zur Laufzeit ist kein API-Key nötig.
 *
 * Dynamische Inhalte (Benutzernamen, Ankündigungen, Projekte aus der DB) bleiben unverändert.
 * Der Admin-Bereich ist bewusst nur deutsch.
 */

declare(strict_types=1);

const CG_LANGS = ['de', 'en'];

/** Inline-Tags, die zu einem Textsegment gehören (alles andere trennt Segmente). */
const CG_I18N_INLINE = 'strong|em|b|i|a|br|span|code|small|u|abbr|mark|sup|sub';

/** Regex für ein Textsegment (Text + Inline-Tags) – identisch im Build-Skript verwendet. */
function cg_i18n_segment_regex(): string
{
    $in = CG_I18N_INLINE;
    return '#>((?>[^<]+|</?(?:' . $in . ')\b[^>]*>)+)(?=<)#';
}

function cg_lang(): string
{
    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }

    $cookie = (string) ($_COOKIE['cg_lang'] ?? '');
    if (in_array($cookie, CG_LANGS, true)) {
        return $lang = $cookie;
    }

    // Erste passende Sprache aus Accept-Language, Standard: Deutsch
    foreach (explode(',', strtolower((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''))) as $part) {
        $tag = substr(trim($part), 0, 2);
        if (in_array($tag, CG_LANGS, true)) {
            return $lang = $tag;
        }
    }
    return $lang = 'de';
}

/** URL der aktuellen Seite mit ?lang=xx (übrige Query-Parameter bleiben erhalten). */
function cg_lang_url(string $lang): string
{
    $path  = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $query = $_GET;
    $query['lang'] = $lang;
    return cg_safe_redirect($path) . '?' . http_build_query($query);
}

/** ?lang=en|de → Cookie setzen und auf die URL ohne Parameter umleiten. */
function cg_i18n_handle_switch(): void
{
    if (PHP_SAPI === 'cli' || !isset($_GET['lang'])) {
        return;
    }
    $new = (string) $_GET['lang'];
    if (in_array($new, CG_LANGS, true)) {
        setcookie('cg_lang', $new, [
            'expires'  => time() + 31536000,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    $query = $_GET;
    unset($query['lang']);
    $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    header('Location: ' . cg_safe_redirect($path) . ($query ? '?' . http_build_query($query) : ''));
    exit;
}

/** Output-Buffer-Callback: übersetzt die Seite nach Englisch. */
function cg_i18n_filter(string $html): string
{
    if (cg_lang() !== 'en' || stripos($html, '<html') === false) {
        return $html;
    }
    $script = (string) ($_SERVER['PHP_SELF'] ?? '');
    if (str_contains($script, '/admin/')) {
        return $html; // Admin-Bereich bleibt deutsch
    }

    static $map = null;
    if ($map === null) {
        $file = dirname(__DIR__) . '/lang/en.php';
        $map  = is_file($file) ? (array) require $file : [];
    }
    if (!$map) {
        return $html;
    }

    // 1) Textsegmente (Text + Inline-Tags) – Leerraum außen bleibt erhalten
    $html = preg_replace_callback(cg_i18n_segment_regex(), static function (array $m) use ($map): string {
        $raw  = $m[1];
        $trim = trim($raw);
        if ($trim === '' || !isset($map[$trim])) {
            return $m[0];
        }
        $lead  = substr($raw, 0, strlen($raw) - strlen(ltrim($raw)));
        $trail = substr($raw, strlen(rtrim($raw)));
        return '>' . $lead . $map[$trim] . $trail;
    }, $html) ?? $html;

    // 2) Attribute
    $html = preg_replace_callback('#\b(placeholder|alt|title|aria-label|content)="([^"]*)"#', static function (array $m) use ($map): string {
        return isset($map[$m[2]]) ? $m[1] . '="' . $map[$m[2]] . '"' : $m[0];
    }, $html) ?? $html;

    // 3) <html lang>
    $html = preg_replace('#<html lang="de">#', '<html lang="en">', $html, 1) ?? $html;

    // 4) Hinweis auf rechtlich maßgebliche deutsche Fassung
    if (in_array(basename($script), ['impressum.php', 'datenschutz.php', 'dsgvo.php', 'hinweise.php'], true)) {
        $note = '<div class="container pt-4"><div class="cg-alert-warn" role="note">'
              . 'Machine-translated for convenience. The German version is legally binding.</div></div>';
        $html = preg_replace('#(<section class="cg-section)#', $note . '$1', $html, 1) ?? $html;
    }

    return $html;
}

cg_i18n_handle_switch();
