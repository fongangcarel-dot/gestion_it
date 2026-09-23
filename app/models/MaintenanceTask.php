<?php

declare(strict_types=1);

final class MaintenanceTask
{
    public function __construct(private PDO $pdo) {}

    public function forMaintenance(int $maintenanceId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM maintenance_tasks WHERE maintenance_id = :maintenance_id ORDER BY id');
        $statement->execute(['maintenance_id' => $maintenanceId]);
        return $statement->fetchAll();
    }

    public function create(int $maintenanceId, string $name, string $description, string $status): bool
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO maintenance_tasks (maintenance_id, task_name, description, status)
             VALUES (:maintenance_id, :task_name, :description, :status)'
        );
        return $statement->execute(['maintenance_id' => $maintenanceId, 'task_name' => $name, 'description' => $description, 'status' => $status]);
    }
}