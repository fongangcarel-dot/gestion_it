<?php

declare(strict_types=1);

final class Department
{
    public function __construct(private PDO $pdo) {}

    public function all(): array
    {
        return $this->pdo->query('SELECT id, name FROM departments ORDER BY name')->fetchAll();
    }

    public function exists(int $id): bool
    {
        $statement = $this->pdo->prepare('SELECT id FROM departments WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        return $statement->fetch() !== false;
    }
}