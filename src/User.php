<?php

namespace App;

use RuntimeException;

/**
 * A user account.
 *
 * Stores:
 *   - password_hash : bcrypt hash, used ONLY to verify login.
 *   - encrypted_key : the per-user data KEY, AES-wrapped with a key derived
 *                     from the plain master password.
 *   - key_salt      : PBKDF2 salt for that derivation.
 *
 * The plain password is never written to the database. The data KEY is fixed
 * for the lifetime of the account; changing the master password only re-wraps
 * it, it is never regenerated.
 */
final class User extends Model
{
    public static function table(): string
    {
        return 'users';
    }

    /**
     * Create a new account and return its id.
     * Throws if the login is already taken.
     */
    public function register(string $login, string $plainPassword): int
    {
        $login = trim($login);
        if ($login === '' || $plainPassword === '') {
            throw new RuntimeException('Login and password are required.');
        }
        if ($this->findByLogin($login) !== null) {
            throw new RuntimeException('That login is already taken.');
        }

        $hash = password_hash($plainPassword, PASSWORD_BCRYPT);

        // Generate the immutable data KEY and wrap it with the master password.
        $dataKey      = Crypto::randomKey(32);
        $salt         = Crypto::randomKey(16);
        $wrappingKey  = Crypto::deriveKey($plainPassword, $salt);
        $encryptedKey = Crypto::encrypt($dataKey, $wrappingKey);

        return $this->insertRow([
            'login'         => $login,
            'password_hash' => $hash,
            'encrypted_key' => $encryptedKey,
            'key_salt'      => base64_encode($salt),
        ]);
    }

    /**
     * Verify credentials. On success returns the unwrapped raw data KEY,
     * which the caller stores in the session. Returns null on failure.
     */
    public function authenticate(string $login, string $plainPassword): ?array
    {
        $row = $this->findByLogin(trim($login));
        if ($row === null || !password_verify($plainPassword, $row['password_hash'])) {
            return null;
        }

        $salt        = base64_decode($row['key_salt'], true);
        $wrappingKey = Crypto::deriveKey($plainPassword, $salt);
        $dataKey     = Crypto::decrypt($row['encrypted_key'], $wrappingKey);

        return [
            'id'       => (int) $row['id'],
            'login'    => $row['login'],
            'data_key' => $dataKey,
        ];
    }

    /**
     * Change the master password.
     *
     * Unwraps the data KEY with the old password, then re-wraps the SAME KEY
     * with the new password. The KEY itself does not change, so every stored
     * password remains decryptable.
     */
    public function changeMasterPassword(int $userId, string $oldPassword, string $newPassword): void
    {
        if ($newPassword === '') {
            throw new RuntimeException('New password cannot be empty.');
        }

        $rows = $this->select('id = :id', ['id' => $userId]);
        $row  = $rows[0] ?? null;
        if ($row === null || !password_verify($oldPassword, $row['password_hash'])) {
            throw new RuntimeException('Current password is incorrect.');
        }

        // Unwrap with the old password.
        $oldSalt    = base64_decode($row['key_salt'], true);
        $oldWrapKey = Crypto::deriveKey($oldPassword, $oldSalt);
        $dataKey    = Crypto::decrypt($row['encrypted_key'], $oldWrapKey);

        // Re-wrap the unchanged data KEY with the new password (fresh salt).
        $newSalt    = Crypto::randomKey(16);
        $newWrapKey = Crypto::deriveKey($newPassword, $newSalt);
        $newEnc     = Crypto::encrypt($dataKey, $newWrapKey);
        $newHash    = password_hash($newPassword, PASSWORD_BCRYPT);

        $stmt = $this->pdo->prepare(
            'UPDATE users
                SET password_hash = :hash,
                    encrypted_key = :ekey,
                    key_salt      = :salt
              WHERE id = :id'
        );
        $stmt->execute([
            'hash' => $newHash,
            'ekey' => $newEnc,
            'salt' => base64_encode($newSalt),
            'id'   => $userId,
        ]);
    }

    public function findByLogin(string $login): ?array
    {
        $rows = $this->select('login = :login', ['login' => $login]);
        return $rows[0] ?? null;
    }
}
