<?php

namespace App;

use RuntimeException;

/**
 * A saved credential in the user's vault.
 *
 * The secret is stored encrypted with the user's data KEY (AES-256-GCM).
 * created_at is filled automatically by the database default.
 */
final class PasswordEntry extends Model
{
    public static function table(): string
    {
        return 'password_entries';
    }

    /** Encrypt and store one credential. Returns the new row id. */
    public function add(int $userId, string $serviceName, string $secret, string $dataKey): int
    {
        $serviceName = trim($serviceName);
        if ($serviceName === '' || $secret === '') {
            throw new RuntimeException('Service name and password are required.');
        }

        return $this->insertRow([
            'user_id'      => $userId,
            'service_name' => $serviceName,
            'secret_enc'   => Crypto::encrypt($secret, $dataKey),
        ]);
    }

    /**
     * Return all of a user's credentials with the password decrypted.
     * Each item: ['id', 'service_name', 'password', 'created_at'].
     */
    public function listForUser(int $userId, string $dataKey): array
    {
        $rows   = $this->select('user_id = :uid ORDER BY created_at DESC', ['uid' => $userId]);
        $result = [];

        foreach ($rows as $row) {
            $result[] = [
                'id'           => (int) $row['id'],
                'service_name' => $row['service_name'],
                'password'     => Crypto::decrypt($row['secret_enc'], $dataKey),
                'created_at'   => $row['created_at'],
            ];
        }

        return $result;
    }

    /** Delete one of the user's credentials. */
    public function delete(int $entryId, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM password_entries WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute(['id' => $entryId, 'uid' => $userId]);
    }
}
