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
 * Format: ENC:<iv_base64>:<ciphertext_base64>
 */
function decryptEnvValue(string $encoded): string
{
    $keyFile = dirname(__DIR__) . '/.db_enc_key';

    if (!is_file($keyFile)) {
        throw new \RuntimeException('[CG] Encryption key file .db_enc_key not found.');
    }

    $key   = base64_decode(trim((string) file_get_contents($keyFile)));
    $parts = explode(':', $encoded, 2);

    if (count($parts) !== 2) {
        throw new \RuntimeException('[CG] Invalid ENC: value format in .env.');
    }

    $iv     = base64_decode($parts[0]);
    $cipher = base64_decode($parts[1]);
    $plain  = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

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

    $envFile = dirname(__DIR__) . '/.env';

    if (!is_file($envFile)) {
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
