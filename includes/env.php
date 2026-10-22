<?php
/**
 * .env Loader
 * -----------
 * Parses the project root .env file once and stores every key/value
 * in $_ENV and putenv() so that other includes can call env() or
 * getenv() to retrieve settings.
 *
 * Rules:
 *   - Lines starting with # are comments
 *   - Blank lines are ignored
 *   - KEY=value  (no quotes required, but double/single quotes are stripped)
 *   - Inline comments after a # are NOT supported intentionally to allow
 *     passwords that contain # characters
 */

declare(strict_types=1);

/**
 * Entschlüsselt einen AES-256-CBC verschlüsselten Wert aus der .env.
 * Format: ENC:<base64(iv + ciphertext)>  (IV und Ciphertext konkateniert)
 *
 * Schlüssel-Lookup (erste Treffer gewinnt):
 *  1. Datei über Umgebungsvariable  CG_KEY_FILE  (Pfad zur secret.key)
 *  2. Standard-Serverpfad           /home/chrisgaming/secret.key
 *  3. Umgebungsvariable             CG_SECRET_KEY (Schlüssel-String direkt)
 *
 * Der Schlüssel liegt BEWUSST außerhalb des Webroots (htdocs).
 */
function decryptEnvValue(string $encoded): string
{
    $keyFile = getenv('CG_KEY_FILE') ?: '/home/chrisgaming/secret.key';

    if (is_readable($keyFile)) {
        $raw = trim((string) file_get_contents($keyFile));
    } else {
        $raw = (string) getenv('CG_SECRET_KEY');
    }

    if ($raw === '') {
        throw new \RuntimeException(
            '[CG] Encryption key not found. ' .
            'Create /home/chrisgaming/secret.key or set CG_SECRET_KEY.'
        );
    }

    $key = hash('sha256', $raw, true);
    $bin = base64_decode($encoded, true);

    if ($bin === false || strlen($bin) <= 16) {
        throw new \RuntimeException('[CG] Invalid ENC: value in .env.');
    }

    $iv     = substr($bin, 0, 16);
    $cipher = substr($bin, 16);
    $plain  = openssl_decrypt($cipher, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

    if ($plain === false) {
        throw new \RuntimeException('[CG] AES decryption failed – wrong key or corrupted value.');
    }

    return $plain;
}

(static function (): void {
    static $loaded = false;   // kein Typ – kompatibel mit PHP 8.0+
    if ($loaded) {
        return;
    }

    // Suchreihenfolge: CG_ENV_FILE → .env EINE EBENE ÜBER dem Webroot (empfohlen, nicht per
    // HTTP erreichbar) → .env im Webroot (Fallback; muss per Webserver-Regel gesperrt sein)
    $envFile = '';
    foreach ([getenv('CG_ENV_FILE') ?: '', dirname(__DIR__, 2) . '/.env', dirname(__DIR__) . '/.env'] as $cand) {
        if ($cand !== '' && is_file($cand) && is_readable($cand)) {
            $envFile = $cand;
            break;
        }
    }

    if ($envFile === '') {
        // .env fehlt – kein fataler Fehler, weiter mit Defaults
        return;
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        // Split only on first = sign
        $eqPos = strpos($line, '=');
        if ($eqPos === false) {
            continue;
        }

        $key   = trim(substr($line, 0, $eqPos));
        $value = trim(substr($line, $eqPos + 1));

        // Strip surrounding quotes
        if (
            strlen($value) >= 2 &&
            (($value[0] === '"' && $value[-1] === '"') ||
             ($value[0] === "'" && $value[-1] === "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        // AES-256-CBC verschlüsselte Werte automatisch entschlüsseln
        if (str_starts_with($value, 'ENC:')) {
            $value = decryptEnvValue(substr($value, 4));
        }

        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
    }

    $loaded = true;
})();

/**
 * Retrieve a value from the loaded environment.
 *
 * @param  string      $key      The environment variable name.
 * @param  mixed       $default  Returned when the key is missing.
 * @return mixed
 */
function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? $default;
}
