<?php
/**
 * i18n_build.php – erzeugt lang/en.php mit DeepL (nur lokal ausführen, CLI)
 * ========================================================================
 * Ablauf:
 *   1. Die Seiten werden von einem lokal laufenden Server geholt (deutsche Ausgabe)
 *   2. Textsegmente + Attribute werden mit derselben Regex extrahiert, die zur Laufzeit
 *      in includes/i18n.php benutzt wird
 *   3. Neue/geänderte Segmente werden per DeepL ins Englische übersetzt
 *      (bereits vorhandene Einträge in lang/en.php werden wiederverwendet → spart Kontingent)
 *
 * Aufruf (aus dem Projektordner):
 *   php scripts/i18n_build.php http://127.0.0.1:8099 [cookiejar-eines-eingeloggten-admins]
 *
 * DeepL-Key: Datei .deepl_key im Projektordner (gitignored) oder Umgebungsvariable DEEPL_API_KEY.
 */

if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

$root = dirname(__DIR__);
require_once $root . '/includes/i18n.php';

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8099', '/');
$jar  = $argv[2] ?? '';
$key  = trim((string) (getenv('DEEPL_API_KEY') ?: (is_file($root . '/.deepl_key') ? file_get_contents($root . '/.deepl_key') : '')));
if ($key === '') {
    exit("Kein DeepL-Key (.deepl_key oder DEEPL_API_KEY).\n");
}
$apiHost = str_ends_with($key, ':fx') ? 'api-free.deepl.com' : 'api.deepl.com';

$pages = [
    'index.php', 'vorstellung.php', 'cgprojekte.php', 'equipment.php', 'hinweise.php', 'impressum.php',
    'datenschutz.php', 'dsgvo.php', 'login.php', 'register.php', 'verify.php?token=bad',
    'community.php', 'dashboard.php',
];

function fetch_page(string $url, string $jar): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_FOLLOWLOCATION => true]);
    if ($jar !== '') {
        curl_setopt($ch, CURLOPT_COOKIEFILE, $jar);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $jar);
    }
    $html = (string) curl_exec($ch);
    curl_close($ch);
    return $html;
}

function has_text(string $s): bool
{
    $plain = html_entity_decode(strip_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return (bool) preg_match('/\p{L}{2,}/u', $plain);
}

// ── 1) Segmente sammeln ─────────────────────────────────────────
$candidates = [];   // key => true
foreach ($pages as $p) {
    $html = fetch_page($base . "/" . $p, in_array($p, ["community.php", "dashboard.php"], true) ? $jar : "");
    if (stripos($html, '<html') === false) {
        fwrite(STDERR, "WARN: $p nicht abrufbar\n");
        continue;
    }
    // Skripte/Styles enthalten keinen sichtbaren Text
    $clean = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '<$1></$1>', $html);

    if (preg_match_all(cg_i18n_segment_regex(), $clean, $m)) {
        foreach ($m[1] as $seg) {
            $seg = trim($seg);
            if ($seg !== '' && has_text($seg)) {
                $candidates[$seg] = true;
            }
        }
    }
    if (preg_match_all('#\b(?:placeholder|alt|title|aria-label)="([^"]+)"#', $clean, $m)) {
        foreach ($m[1] as $v) {
            if (has_text($v)) {
                $candidates[$v] = true;
            }
        }
    }
    if (preg_match_all('#<meta\s+(?:name="description"|property="og:(?:title|description)")\s+(?:property="[^"]*"\s+)?content="([^"]+)"#', $clean, $m)) {
        foreach ($m[1] as $v) {
            $candidates[$v] = true;
        }
    }
    echo "gelesen: $p\n";
}

// ── 2) Dynamische Meldungen aus dem PHP-Code (nicht immer sichtbar beim Abruf) ──
$messages = require __DIR__ . '/i18n_messages.php';
foreach ($messages as $msg) {
    $esc = htmlspecialchars($msg, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    foreach (['fa-exclamation-circle', 'fa-circle-check', 'fa-envelope-circle-check'] as $icon) {
        $candidates['<i class="fas ' . $icon . ' me-2"></i>' . $esc] = true;
    }
    $candidates[$esc] = true;
}

// ── 3) Vorhandene Übersetzungen weiterverwenden ───────────────────
$file = $root . '/lang/en.php';
$map  = is_file($file) ? (array) require $file : [];
$todo = array_values(array_filter(array_keys($candidates), static fn($k) => !array_key_exists($k, $map)));
echo count($candidates) . ' Segmente, ' . count($todo) . " neu zu übersetzen\n";

// ── 4) DeepL ─────────────────────────────────────────────────────
function deepl(array $texts, string $host, string $key): array
{
    $fields = [];
    foreach ($texts as $t) {
        $fields[] = 'text=' . rawurlencode($t);
    }
    $body = implode('&', $fields) . '&source_lang=DE&target_lang=EN-GB&tag_handling=html&preserve_formatting=1';
    $ch   = curl_init("https://$host/v2/translate");
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $body,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_CAINFO         => getenv('CG_CAINFO') ?: null,
        CURLOPT_HTTPHEADER     => ['Authorization: DeepL-Auth-Key ' . $key, 'Content-Type: application/x-www-form-urlencoded'],
    ]);
    $resp = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200) {
        fwrite(STDERR, "DeepL HTTP $code: $resp\n");
        return [];
    }
    $data = json_decode($resp, true);
    return array_map(static fn($t) => (string) $t['text'], $data['translations'] ?? []);
}

$skipped = 0;
$failed  = [];
foreach (array_chunk($todo, 40) as $chunk) {
    $out = deepl($chunk, $apiHost, $key);
    if (count($out) !== count($chunk)) {
        fwrite(STDERR, "Chunk übersprungen (Antwort unvollständig)\n");
        continue;
    }
    foreach ($chunk as $i => $src) {
        $dst = trim($out[$i]);
        // Tags müssen erhalten bleiben, Attribute dürfen keine " enthalten
        $isAttr = !str_contains($src, '<');
        if ($isAttr) {
            $dst = str_replace('"', '&quot;', $dst);
        }
        if (substr_count($src, '<') !== substr_count($dst, '<')) {
            fwrite(STDERR, 'Tag-Mismatch, übersprungen: ' . mb_substr($src, 0, 60) . "\n");
            $failed[] = $src;
            continue;
        }
        $map[$src] = $dst; // auch identische Ergebnisse merken (kein erneuter API-Aufruf)
    }
    usleep(300000);
}


// Fallback für Segmente, bei denen DeepL Tags verändert hat: nur die Textteile einzeln übersetzen
foreach ($failed as $src) {
    $parts = preg_split('/(<[^>]*>)/', $src, -1, PREG_SPLIT_DELIM_CAPTURE);
    $idx = [];
    $texts = [];
    foreach ($parts as $i => $part) {
        if ($part !== '' && $part[0] !== '<' && has_text($part)) {
            $idx[] = $i;
            $texts[] = trim($part);
        }
    }
    $out = $texts ? deepl($texts, $apiHost, $key) : [];
    if (count($out) !== count($texts) || !$texts) {
        $skipped++;
        continue;
    }
    foreach ($idx as $n => $i) {
        $orig = $parts[$i];
        $lead = substr($orig, 0, strlen($orig) - strlen(ltrim($orig)));
        $trail = substr($orig, strlen(rtrim($orig)));
        $parts[$i] = $lead . trim($out[$n]) . $trail;
    }
    $map[$src] = trim(implode('', $parts));
}

// ── 5) Schreiben (identische Einträge werden zur Laufzeit ignoriert) ──
ksort($map);
if (!is_dir($root . '/lang')) {
    mkdir($root . '/lang', 0755, true);
}
$php = "<?php\n// Automatisch erzeugt von scripts/i18n_build.php (DeepL) – nicht von Hand bearbeiten.\n// Quelle (Deutsch) => Englisch\nreturn " . var_export(array_filter($map, static fn($v, $k) => $v !== $k, ARRAY_FILTER_USE_BOTH), true) . ";\n";
file_put_contents($file, $php);
echo 'lang/en.php geschrieben (' . count($map) . " Einträge, $skipped übersprungen)\n";
