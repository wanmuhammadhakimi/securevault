<?php
/**
 * AES-256-CBC file encryption with HMAC-SHA256 integrity tags (encrypt-then-MAC).
 *
 * The key is read from the AES_KEY_HEX environment variable when set (set
 * this on your host so the key isn't committed to version control); the
 * key lives in /etc/apache2/conf-available/securevault-secrets.conf (root only).
 * Generate your own with:  php -r "echo bin2hex(random_bytes(32));"
 */

$__aesKeyHex = getenv('AES_KEY_HEX') ?: ($_SERVER['AES_KEY_HEX'] ?? '');
if (!preg_match('/^[0-9a-f]{64}$/i', $__aesKeyHex)) {
    http_response_code(500);
    error_log('SecureVault: AES_KEY_HEX is not set');
    die('Server encryption key is not configured');
}
define('ENCRYPTION_KEY', hex2bin($__aesKeyHex)); // 32 bytes for AES-256
unset($__aesKeyHex);
define('CIPHER_METHOD', 'aes-256-cbc');
define('ENCRYPTED_DIR', __DIR__ . '/../uploads_encrypted/');

// ---- File format ----------------------------------------------------------
// New files:  "SVH1" | ciphertext (AES-256-CBC) | HMAC-SHA256 tag (32 bytes)
// The tag covers "SVH1" + IV + ciphertext (encrypt-then-MAC), using a MAC key
// derived from ENCRYPTION_KEY, so any change to a stored file is detected
// before it is decrypted.
define('SV_FILE_MAGIC', 'SVH1');
define('SV_ALLOW_LEGACY', false);

function svMacKey(): string {
    return hash_hmac('sha256', 'securevault-file-mac-v1', ENCRYPTION_KEY, true);
}

/**
 * Encrypts raw file data. Returns [stored_blob, iv_hex].
 */
function encryptData(string $plainData): array {
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(CIPHER_METHOD));
    $cipherText = openssl_encrypt($plainData, CIPHER_METHOD, ENCRYPTION_KEY, OPENSSL_RAW_DATA, $iv);
    $tag = hash_hmac('sha256', SV_FILE_MAGIC . $iv . $cipherText, svMacKey(), true);
    return [SV_FILE_MAGIC . $cipherText . $tag, bin2hex($iv)];
}

/** Adds a tag to a legacy (untagged) blob without re-encrypting it. */
function svWrapLegacy(string $cipherText, string $ivHex): string {
    $tag = hash_hmac('sha256', SV_FILE_MAGIC . hex2bin($ivHex) . $cipherText, svMacKey(), true);
    return SV_FILE_MAGIC . $cipherText . $tag;
}

/**
 * Verifies and decrypts a stored blob. Returns plaintext, or false on failure.
 * After a failure, $GLOBALS['SV_CRYPTO_ERROR'] is 'tampered' (tag mismatch or
 * untagged file when legacy is disabled) or 'decrypt'.
 */
function decryptData(string $stored, string $ivHex) {
    $GLOBALS['SV_CRYPTO_ERROR'] = null;
    $iv = hex2bin($ivHex);

    if (strncmp($stored, SV_FILE_MAGIC, 4) === 0 && strlen($stored) >= 4 + 16 + 32) {
        $cipherText = substr($stored, 4, -32);
        $tag        = substr($stored, -32);
        $expected   = hash_hmac('sha256', SV_FILE_MAGIC . $iv . $cipherText, svMacKey(), true);
        if (!hash_equals($expected, $tag)) {
            $GLOBALS['SV_CRYPTO_ERROR'] = 'tampered';
            return false;
        }
    } elseif (SV_ALLOW_LEGACY) {
        $cipherText = $stored;          // file stored before integrity tags existed
    } else {
        $GLOBALS['SV_CRYPTO_ERROR'] = 'tampered';
        return false;
    }

    $plain = openssl_decrypt($cipherText, CIPHER_METHOD, ENCRYPTION_KEY, OPENSSL_RAW_DATA, $iv);
    if ($plain === false) $GLOBALS['SV_CRYPTO_ERROR'] = 'decrypt';
    return $plain;
}

/** Generates a random storage filename so originals aren't guessable on disk. */
function generateStoredFilename(): string {
    return bin2hex(random_bytes(16)) . '.enc';
}