<?php

declare(strict_types=1);

final class Printer
{
    public function __construct(private PDO $pdo)
    {
    }

    public function countAll(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM printers')->fetchColumn();
    }

    public function all(): array
    {
        return $this->pdo->query(
            'SELECT p.*, d.name AS department_name,
                    GROUP_CONCAT(CONCAT(u.first_name, \' \', u.last_name) ORDER BY u.last_name, u.first_name SEPARATOR \' , \') AS assigned_users
             FROM printers p
             LEFT JOIN departments d ON d.id = p.department_id
             LEFT JOIN printer_user pu ON pu.printer_id = p.id
             LEFT JOIN users u ON u.id = pu.user_id
             GROUP BY p.id
             ORDER BY p.asset_tag'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM printers WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $printer = $statement->fetch();
        return $printer === false ? null : $printer;
    }

    public function assetExists(string $assetTag, ?int $exceptId = null): bool
    {
        $sql = 'SELECT id FROM printers WHERE asset_tag = :asset_tag';
        $params = ['asset_tag' => $assetTag];
        if ($exceptId !== null) { $sql .= ' AND id <> :except_id'; $params['except_id'] = $exceptId; }
        $statement = $this->pdo->prepare($sql . ' LIMIT 1');
        $statement->execute($params);
        return $statement->fetch() !== false;
    }

    public function assignedUserIds(int $printerId): array
    {
        $statement = $this->pdo->prepare('SELECT user_id FROM printer_user WHERE printer_id = :printer_id');
        $statement->execute(['printer_id' => $printerId]);
        return array_map('intval', array_column($statement->fetchAll(), 'user_id'));
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO printers (asset_tag, brand, model, serial_number, printer_type, ip_address, location, department_id, status, purchase_date)
             VALUES (:asset_tag, :brand, :model, :serial_number, :printer_type, :ip_address, :location, :department_id, :status, :purchase_date)'
        );
        $statement->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE printers SET asset_tag = :asset_tag, brand = :brand, model = :model, serial_number = :serial_number,
             printer_type = :printer_type, ip_address = :ip_address, location = :location, department_id = :department_id,
             status = :status, purchase_date = :purchase_date WHERE id = :id'
        );
        $data['id'] = $id;
        return $statement->execute($data);
    }

    public function replaceUserAssignments(int $printerId, array $userIds): void
    {
        $delete = $this->pdo->prepare('DELETE FROM printer_user WHERE printer_id = :printer_id');
        $delete->execute(['printer_id' => $printerId]);
        $insert = $this->pdo->prepare('INSERT INTO printer_user (printer_id, user_id) VALUES (:printer_id, :user_id)');
        foreach (array_unique(array_map('intval', $userIds)) as $userId) {
            if ($userId > 0) $insert->execute(['printer_id' => $printerId, 'user_id' => $userId]);
        }
    }
}
