<?php

namespace App;

use RuntimeException;

/**
 * All cryptographic primitives used by the application.
 *
 *  - AES-256-GCM for encrypting the data KEY and the stored passwords.
 *  - PBKDF2-SHA256 to turn the user's plain master password into a
 *    32-byte wrapping key (so the plain password is never stored).
 *
 * Output blob layout for encrypt():  base64( IV[12] | TAG[16] | CIPHERTEXT )
 */
final class Crypto
{
    private const CIPHER         = 'aes-256-gcm';
    private const IV_LEN         = 12;   // 96-bit nonce, recommended for GCM
    private const TAG_LEN        = 16;   // 128-bit auth tag
    private const KEY_LEN        = 32;   // 256-bit key
    private const KDF_ITERATIONS = 200000;

    /** Generate a cryptographically secure random key / salt. */
    public static function randomKey(int $bytes = self::KEY_LEN): string
    {
        return random_bytes($bytes);
    }

    /** Derive a 32-byte AES key from a plain password + salt (PBKDF2-SHA256). */
    public static function deriveKey(string $password, string $salt): string
    {
        return hash_pbkdf2('sha256', $password, $salt, self::KDF_ITERATIONS, self::KEY_LEN, true);
    }

    /** Encrypt plaintext with a raw 32-byte key. Returns a base64 string. */
    public static function encrypt(string $plaintext, string $key): string
    {
        $iv  = random_bytes(self::IV_LEN);
        $tag = '';
        $cipher = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LEN
        );

        if ($cipher === false) {
            throw new RuntimeException('Encryption failed.');
        }

        return base64_encode($iv . $tag . $cipher);
    }

    /** Decrypt a base64 blob produced by encrypt() with the same key. */
    public static function decrypt(string $blob, string $key): string
    {
        $raw = base64_decode($blob, true);
        if ($raw === false || strlen($raw) < self::IV_LEN + self::TAG_LEN) {
            throw new RuntimeException('Malformed ciphertext.');
        }

        $iv     = substr($raw, 0, self::IV_LEN);
        $tag    = substr($raw, self::IV_LEN, self::TAG_LEN);
        $cipher = substr($raw, self::IV_LEN + self::TAG_LEN);

        $plaintext = openssl_decrypt(
            $cipher,
            self::CIPHER,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            // Wrong key or tampered data — GCM tag check failed.
            throw new RuntimeException('Decryption failed (wrong password or corrupted data).');
        }

        return $plaintext;
    }
}
