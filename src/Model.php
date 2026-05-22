<?php

namespace App;

use PDO;

/**
 * Abstract base class for database-backed entities.
 *
 * Holds the shared PDO connection and provides small reusable INSERT/SELECT
 * helpers. User and PasswordEntry inherit from it, so the connection handling
 * and basic query building live in exactly one place.
 */
abstract class Model
{
    protected PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance()->pdo();
    }

    /** Each subclass declares the table it maps to. */
    abstract public static function table(): string;

    /** Generic INSERT helper. Returns the new row id. */
    protected function insertRow(array $columns): int
    {
        $names        = array_keys($columns);
        $placeholders = array_map(static fn ($c) => ':' . $c, $names);

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            static::table(),
            implode(', ', $names),
            implode(', ', $placeholders)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($columns);

        return (int) $this->pdo->lastInsertId();
    }

    /** Generic SELECT helper. */
    protected function select(string $where = '', array $params = []): array
    {
        $sql = 'SELECT * FROM ' . static::table();
        if ($where !== '') {
            $sql .= ' WHERE ' . $where;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }
}
