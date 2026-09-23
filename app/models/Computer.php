<?php

declare(strict_types=1);

final class Computer
{
    public function __construct(private PDO $pdo)
    {
    }

    public function forEmployee(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT c.id, c.asset_tag, c.brand, c.model
             FROM computers c
             INNER JOIN computer_user cu ON cu.computer_id = c.id
             WHERE cu.user_id = :user_id
             ORDER BY c.asset_tag'
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function exists(int $id): bool
    {
        $statement = $this->pdo->prepare('SELECT id FROM computers WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        return $statement->fetch() !== false;
    }

    public function belongsToUser(int $id, int $userId): bool
    {
        $statement = $this->pdo->prepare('SELECT computer_id FROM computer_user WHERE computer_id = :id AND user_id = :user_id LIMIT 1');
        $statement->execute(['id' => $id, 'user_id' => $userId]);
        return $statement->fetch() !== false;
    }

    public function all(): array
    {
        return $this->pdo->query(
            'SELECT c.*, COALESCE(assigned.users, \'Unassigned\') AS assigned_user
             FROM computers c
             LEFT JOIN (
                 SELECT cu.computer_id, GROUP_CONCAT(CONCAT(u.first_name, \' \', u.last_name) ORDER BY u.last_name, u.first_name SEPARATOR \', \') AS users
                 FROM computer_user cu
                 INNER JOIN users u ON u.id = cu.user_id
                 GROUP BY cu.computer_id
             ) assigned ON assigned.computer_id = c.id
             ORDER BY c.asset_tag'
        )->fetchAll();
    }

    public function countAll(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM computers')->fetchColumn();
    }

    public function availableForAssignment(?int $userId = null): array
    {
        return $this->pdo->query('SELECT id, asset_tag, brand, model FROM computers ORDER BY asset_tag')->fetchAll();
    }

    public function assignToUser(int $computerId, int $userId): bool
    {
        $statement = $this->pdo->prepare('UPDATE computers SET user_id = :assigned_user_id WHERE id = :id AND (user_id IS NULL OR user_id = :current_user_id)');
        return $statement->execute(['assigned_user_id' => $userId, 'id' => $computerId, 'current_user_id' => $userId]);
    }

    public function assignedToUser(int $userId): ?array
    {
        $statement = $this->pdo->prepare('SELECT c.id, c.asset_tag, c.brand, c.model FROM computers c INNER JOIN computer_user cu ON cu.computer_id = c.id WHERE cu.user_id = :user_id ORDER BY c.id LIMIT 1');
        $statement->execute(['user_id' => $userId]);
        $computer = $statement->fetch();
        return $computer === false ? null : $computer;
    }

    public function replaceUserAssignment(int $computerId, int $userId): bool
    {
        return $this->replaceUserAssignments([$computerId], $userId);
    }

    public function replaceUserAssignments(array $computerIds, int $userId): bool
    {
        $delete = $this->pdo->prepare('DELETE FROM computer_user WHERE user_id = :user_id');
        $delete->execute(['user_id' => $userId]);
        $insert = $this->pdo->prepare('INSERT INTO computer_user (computer_id, user_id) VALUES (:computer_id, :user_id)');
        foreach (array_unique(array_map('intval', $computerIds)) as $computerId) {
            if ($computerId > 0) {
                $insert->execute(['computer_id' => $computerId, 'user_id' => $userId]);
            }
        }
        return true;
    }

    public function assignedUserIds(int $computerId): array
    {
        $statement = $this->pdo->prepare('SELECT user_id FROM computer_user WHERE computer_id = :computer_id');
        $statement->execute(['computer_id' => $computerId]);
        return array_map('intval', array_column($statement->fetchAll(), 'user_id'));
    }

    public function replaceComputerUsers(int $computerId, array $userIds): bool
    {
        $delete = $this->pdo->prepare('DELETE FROM computer_user WHERE computer_id = :computer_id');
        $delete->execute(['computer_id' => $computerId]);
        $insert = $this->pdo->prepare('INSERT INTO computer_user (computer_id, user_id) VALUES (:computer_id, :user_id)');
        foreach (array_unique(array_map('intval', $userIds)) as $userId) {
            if ($userId > 0) {
                $insert->execute(['computer_id' => $computerId, 'user_id' => $userId]);
            }
        }
        return true;
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM computers WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $computer = $statement->fetch();
        return $computer === false ? null : $computer;
    }

    public function assetExists(string $assetTag, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM computers WHERE asset_tag = :asset_tag';
        $params = ['asset_tag' => $assetTag];
        if ($exceptId !== null) { $sql .= ' AND id <> :except_id'; $params['except_id'] = $exceptId; }
        $statement = $this->pdo->prepare($sql . ' LIMIT 1');
        $statement->execute($params);
        return $statement->fetch() !== false;
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare('INSERT INTO computers (asset_tag, brand, model, serial_number, operating_system, ip_address, department_id, user_id, status, purchase_date) VALUES (:asset_tag, :brand, :model, :serial_number, :operating_system, :ip_address, :department_id, :user_id, :status, :purchase_date)');
        $statement->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $statement = $this->pdo->prepare('UPDATE computers SET asset_tag = :asset_tag, brand = :brand, model = :model, serial_number = :serial_number, operating_system = :operating_system, ip_address = :ip_address, department_id = :department_id, user_id = :user_id, status = :status, purchase_date = :purchase_date WHERE id = :id');
        $data['id'] = $id;
        return $statement->execute($data);
    }
}