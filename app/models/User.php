<?php

declare(strict_types=1);

final class User
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findActiveByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id, department_id, first_name, last_name, email, phone, password, role, status, created_at
             FROM users
             WHERE email = :email AND status = :status
             LIMIT 1'
        );
        $statement->execute([
            'email' => $email,
            'status' => 'active',
        ]);

        $user = $statement->fetch();
        return $user === false ? null : $user;
    }

    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->findActiveByEmail($email);
        if ($user === null || !password_verify($password, $user['password'])) {
            return null;
        }

        unset($user['password']);
        return $user;
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function technicians(): array
    {
        $statement = $this->pdo->prepare("SELECT id, first_name, last_name, email FROM users WHERE role = :role AND status = :status ORDER BY last_name, first_name");
        $statement->execute(['role' => 'technician', 'status' => 'active']);
        return $statement->fetchAll();
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT id, first_name, last_name, email, role, status, created_at FROM users ORDER BY last_name, first_name')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, department_id, first_name, last_name, email, phone, role, status FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();
        return $user === false ? null : $user;
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM users WHERE email = :email';
        $params = ['email' => $email];
        if ($exceptId !== null) { $sql .= ' AND id <> :except_id'; $params['except_id'] = $exceptId; }
        $statement = $this->pdo->prepare($sql . ' LIMIT 1');
        $statement->execute($params);
        return $statement->fetch() !== false;
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare('INSERT INTO users (department_id, first_name, last_name, email, phone, password, role, status) VALUES (:department_id, :first_name, :last_name, :email, :phone, :password, :role, :status)');
        $statement->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $passwordSql = '';
        if (isset($data['password'])) { $passwordSql = ', password = :password'; }
        $statement = $this->pdo->prepare("UPDATE users SET department_id = :department_id, first_name = :first_name, last_name = :last_name, email = :email, phone = :phone, role = :role, status = :status{$passwordSql} WHERE id = :id");
        $data['id'] = $id;
        return $statement->execute($data);
    }
}