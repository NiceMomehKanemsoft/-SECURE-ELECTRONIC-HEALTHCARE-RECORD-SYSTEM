<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/security.php';

/**
 * AES-256-GCM encryption engine.
 * Keys are derived on-demand and never stored.
 */
class CryptoEngine {

    /**
     * Derive a 256-bit key from session token + patient UUID using PBKDF2.
     * Key material is returned as raw bytes and must be zeroed after use.
     */
    public static function deriveKey(string $sessionToken, string $patientUuid, string $salt): string {
        $ikm = $sessionToken . '|' . $patientUuid;
        $key = hash_pbkdf2(
            PBKDF2_ALGO,
            $ikm,
            $salt,
            PBKDF2_ITERATIONS,
            PBKDF2_KEY_LENGTH,
            true
        );
        // Zero IKM from memory
        $ikm = str_repeat("\x00", strlen($ikm));
        unset($ikm);
        return $key;
    }

    /**
     * Encrypt plaintext with AES-256-GCM.
     * Returns ['ciphertext'=>base64, 'iv'=>binary, 'tag'=>binary, 'salt'=>binary, 'key_id'=>string]
     */
    public static function encrypt(string $plaintext, string $sessionToken, string $patientUuid): array {
        $iv   = random_bytes(GCM_IV_LENGTH);
        $salt = random_bytes(16);
        $key  = self::deriveKey($sessionToken, $patientUuid, $salt);

        $tag        = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', GCM_TAG_LENGTH);

        // Zero key from memory
        $key = str_repeat("\x00", strlen($key));
        unset($key);

        if ($ciphertext === false) {
            throw new RuntimeException('Encryption failed.');
        }

        return [
            'ciphertext' => base64_encode($ciphertext),
            'iv'         => $iv,
            'tag'        => $tag,
            'salt'       => $salt,
            'key_id'     => bin2hex(random_bytes(8)), // non-secret identifier
        ];
    }

    /**
     * Decrypt ciphertext with AES-256-GCM.
     * Throws RuntimeException if authentication tag fails.
     */
    public static function decrypt(
        string $ciphertextB64,
        string $iv,
        string $tag,
        string $salt,
        string $sessionToken,
        string $patientUuid
    ): string {
        $key        = self::deriveKey($sessionToken, $patientUuid, $salt);
        $ciphertext = base64_decode($ciphertextB64, true);

        if ($ciphertext === false) {
            throw new RuntimeException('Invalid ciphertext encoding.');
        }

        $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        // Zero key from memory
        $key = str_repeat("\x00", strlen($key));
        unset($key);

        if ($plaintext === false) {
            throw new RuntimeException('Authentication tag validation failed. Data may be tampered.');
        }

        return $plaintext;
    }
}
