<?php

declare(strict_types=1);

final class Maintenance
{
    public function __construct(private PDO $pdo) {}

    public function forTicket(int $ticketId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM maintenance WHERE ticket_id = :ticket_id ORDER BY created_at DESC');
        $statement->execute(['ticket_id' => $ticketId]);
        return $statement->fetchAll();
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT m.*, t.title AS ticket_title, CONCAT(u.first_name, \' \', u.last_name) AS technician_name FROM maintenance m LEFT JOIN tickets t ON t.id = m.ticket_id JOIN users u ON u.id = m.technician_id ORDER BY m.created_at DESC')->fetchAll();
    }

    public function create(?int $ticketId, int $technicianId, array $data): int
    {
        $startDate = $data['start_date'] ? str_replace('T', ' ', $data['start_date']) : null;
        $endDate = $data['end_date'] ? str_replace('T', ' ', $data['end_date']) : null;
        if ($startDate !== null && strlen($startDate) === 10) $startDate .= ' 00:00:00';
        if ($endDate !== null && strlen($endDate) === 10) $endDate .= ' 00:00:00';
        $statement = $this->pdo->prepare(
            'INSERT INTO maintenance (ticket_id, technician_id, maintenance_type, diagnosis, actions_taken, result, start_date, end_date, status, observations)
             VALUES (:ticket_id, :technician_id, :maintenance_type, :diagnosis, :actions_taken, :result, :start_date, :end_date, :status, :observations)'
        );
        $statement->execute([
            'ticket_id' => $ticketId, 'technician_id' => $technicianId,
            'maintenance_type' => $data['maintenance_type'], 'diagnosis' => $data['diagnosis'],
            'actions_taken' => $data['actions_taken'], 'result' => $data['result'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $data['status'], 'observations' => $data['observations'],
        ]);
        return (int) $this->pdo->lastInsertId();
    }
}