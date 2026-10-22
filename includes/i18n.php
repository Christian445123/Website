<?php
/**
 * i18n – Deutsch (Quellsprache) / Englisch
 * ----------------------------------------
 * Jede öffentliche Seite gibt es in zwei Sprachen unter eigener URL:
 *     Deutsch:  /vorstellung.php          Englisch:  /en/vorstellung.php
 * Die Dateien in /en/ sind nur kleine Stubs, die die Sprache festlegen und die deutsche
 * Seite einbinden (funktioniert ohne Rewrite-Regeln, also auch auf nginx).
 *
 * Für Englisch wird die fertige HTML-Ausgabe segmentweise anhand von lang/en.php übersetzt
 * (Text zwischen Block-Tags inkl. Inline-Tags sowie Attribute wie placeholder/alt/title/
 * aria-label/content) und interne Links werden auf /en/… umgeschrieben.
 *
 * lang/en.php wird mit scripts/i18n_build.php (DeepL, Key nur lokal in .deepl_key) erzeugt –
 * zur Laufzeit ist kein API-Key nötig. Der Admin-Bereich bleibt bewusst nur deutsch.
 * Dynamische Inhalte (Benutzernamen, Ankündigungen, Projekte aus der DB) bleiben unverändert.
 */

declare(strict_types=1);

require_once __DIR__ . '/security.php'; // cg_safe_redirect()

const CG_LANGS = ['de', 'en'];

/** Inline-Tags, die zu einem Textsegment gehören (alles andere – auch <bdi> – trennt Segmente). */
const CG_I18N_INLINE = 'strong|em|b|i|a|br|span|code|small|u|abbr|mark|sup|sub';

/** Regex für ein Textsegment (Text + Inline-Tags) – identisch im Build-Skript verwendet. */
function cg_i18n_segment_regex(): string
{
    $in = CG_I18N_INLINE;
    return '#>((?>[^<]+|</?(?:' . $in . ')\b[^>]*>)+)(?=<)#';
}

/**
 * Normalisiert ein Segment für den Abgleich: Zeilenumbrüche (CRLF/LF) und Leerraum-Folgen
 * werden zu einem Leerzeichen. So passen Schlüssel unabhängig vom Betriebssystem/Einrückung.
 */
function cg_i18n_norm(string $s): string
{
    return trim((string) preg_replace('/\s+/u', ' ', $s));
}

/** Sprache der aktuellen Seite: 'en' nur, wenn der /en/-Stub sie festgelegt hat. */
function cg_lang(): string
{
    return (defined('CG_LANG_FORCE') && CG_LANG_FORCE === 'en') ? 'en' : 'de';
}

/**
 * Interne URL in der aktuellen Sprache: cg_url('/login.php') → '/en/login.php' (bei Englisch).
 * Für Weiterleitungen und E-Mail-Links.
 */
function cg_url(string $path = '/'): string
{
    if (cg_lang() !== 'en') {
        return $path;
    }
    if ($path === '' || $path === '/') {
        return '/en/';
    }
    return '/en' . ($path[0] === '/' ? $path : '/' . $path);
}

/** Aktueller Pfad ohne /en-Präfix. */
function cg_path_without_lang(): string
{
    $path = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    if ($path === '/en' || $path === '/en/') {
        return '/';
    }
    return str_starts_with($path, '/en/') ? substr($path, 3) : ($path === '' ? '/' : $path);
}

/** Dieselbe Seite in einer anderen Sprache (übrige Query-Parameter bleiben erhalten). */
function cg_lang_url(string $lang): string
{
    $path  = cg_safe_redirect(cg_path_without_lang());
    $query = $_GET;
    unset($query['lang']);
    $url = ($lang === 'en') ? ($path === '/' ? '/en/' : '/en' . $path) : $path;
    return $url . ($query ? '?' . http_build_query($query) : '');
}

/** ?lang=en|de (alte Links) → auf die gleichwertige URL der Sprachversion umleiten. */
function cg_i18n_handle_switch(): void
{
    if (PHP_SAPI === 'cli' || !isset($_GET['lang'])) {
        return;
    }
    $new = (string) $_GET['lang'];
    if (in_array($new, CG_LANGS, true) && $new !== cg_lang()) {
        header('Location: ' . cg_lang_url($new));
        exit;
    }
}

/** Output-Buffer-Callback: übersetzt die Seite nach Englisch und schreibt interne Links um. */
function cg_i18n_filter(string $html): string
{
    if (cg_lang() !== 'en' || stripos($html, '<html') === false) {
        return $html;
    }
    $script = (string) ($_SERVER['PHP_SELF'] ?? '');

    static $map = null;
    if ($map === null) {
        $file = dirname(__DIR__) . '/lang/en.php';
        $map  = is_file($file) ? (array) require $file : [];
    }

    if ($map) {
        // 1) Textsegmente (Text + Inline-Tags) – Leerraum außen bleibt erhalten
        $html = preg_replace_callback(cg_i18n_segment_regex(), static function (array $m) use ($map): string {
            $raw  = $m[1];
            $trim = cg_i18n_norm($raw);
            if ($trim === '' || !isset($map[$trim])) {
                return $m[0];
            }
            $lead  = substr($raw, 0, strlen($raw) - strlen(ltrim($raw)));
            $trail = substr($raw, strlen(rtrim($raw)));
            return '>' . $lead . $map[$trim] . $trail;
        }, $html) ?? $html;

        // 2) Attribute
        $html = preg_replace_callback('#\b(placeholder|alt|title|aria-label|content)="([^"]*)"#', static function (array $m) use ($map): string {
            $k = cg_i18n_norm($m[2]);
            return isset($map[$k]) ? $m[1] . '="' . $map[$k] . '"' : $m[0];
        }, $html) ?? $html;
    }

    // 3) Interne Links/Formulare auf die englische Version umschreiben
    //    (nicht: Sprachumschalter mit hreflang, /assets, /admin, bereits /en/)
    $html = preg_replace_callback(
        '#(<(?:a|form)\b(?![^>]*\shreflang=)[^>]*?\s(?:href|action)=")(/[^"]*)(")#',
        static function (array $m): string {
            $url = $m[2];
            if (preg_match('#^/(?:en(?:/|$)|assets/|admin/)#', $url)) {
                return $m[0];
            }
            if (!preg_match('#^/(?:[a-z0-9_-]+\.php)?(?:[?\#].*)?$#i', $url)) {
                return $m[0];
            }
            return $m[1] . ($url === '/' ? '/en/' : '/en' . $url) . $m[3];
        },
        $html
    ) ?? $html;

    // 4) <html lang>
    $html = preg_replace('#<html lang="de">#', '<html lang="en">', $html, 1) ?? $html;

    // 5) Hinweis auf rechtlich maßgebliche deutsche Fassung
    if (in_array(basename($script), ['impressum.php', 'datenschutz.php', 'dsgvo.php', 'hinweise.php'], true)) {
        $note = '<div class="container pt-4"><div class="cg-alert-warn" role="note">'
              . 'Machine-translated for convenience. The German version is legally binding.</div></div>';
        $html = preg_replace('#(<section class="cg-section)#', $note . '$1', $html, 1) ?? $html;
    }

    return $html;
}

cg_i18n_handle_switch();
