<?php

declare(strict_types=1);

final class Ticket
{
    private const STATUSES = ['open', 'assigned', 'in_progress', 'resolved', 'closed'];
    private const PRIORITIES = ['low', 'medium', 'high', 'critical'];
    private const CATEGORIES = ['hardware', 'software', 'network', 'printer', 'security', 'other'];

    public function __construct(private PDO $pdo)
    {
    }

    public static function statuses(): array { return self::STATUSES; }
    public static function priorities(): array { return self::PRIORITIES; }
    public static function categories(): array { return self::CATEGORIES; }

    public function create(int $userId, ?int $computerId, string $title, string $description, string $category, string $priority): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO tickets (user_id, computer_id, title, description, category, priority, status)
             VALUES (:user_id, :computer_id, :title, :description, :category, :priority, :status)'
        );
        $statement->execute([
            'user_id' => $userId,
            'computer_id' => $computerId,
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'priority' => $priority,
            'status' => 'open',
        ]);
        $ticketId = (int) $this->pdo->lastInsertId();
        (new TicketActivity($this->pdo))->record($ticketId, $userId, 'created', null, 'open', 'Ticket created');
        return $ticketId;
    }

    private function query(string $where = '', array $params = []): array
    {
        $statement = $this->pdo->prepare(
            'SELECT t.*, CONCAT(u.first_name, \' \', u.last_name) AS requester,
                    CONCAT(tech.first_name, \' \', tech.last_name) AS technician_name,
                    c.asset_tag
             FROM tickets t JOIN users u ON u.id = t.user_id
             LEFT JOIN users tech ON tech.id = t.technician_id
             LEFT JOIN computers c ON c.id = t.computer_id ' . $where . ' ORDER BY t.created_at DESC'
        );
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function forEmployee(int $userId): array { return $this->query('WHERE t.user_id = :user_id', ['user_id' => $userId]); }
    public function forTechnician(int $technicianId): array { return $this->query('WHERE t.technician_id = :technician_id', ['technician_id' => $technicianId]); }
    public function all(?string $status = null, ?string $priority = null): array
    {
        $conditions = [];
        $params = [];
        if ($status !== null && valid_enum($status, self::STATUSES)) { $conditions[] = 't.status = :status'; $params['status'] = $status; }
        if ($priority !== null && valid_enum($priority, self::PRIORITIES)) { $conditions[] = 't.priority = :priority'; $params['priority'] = $priority; }
        return $this->query($conditions ? 'WHERE ' . implode(' AND ', $conditions) : '', $params);
    }

    public function findForUser(int $ticketId, int $userId, string $role): ?array
    {
        $where = $role === 'employee' ? 't.id = :ticket_id AND t.user_id = :user_id' : 't.id = :ticket_id AND t.technician_id = :user_id';
        $rows = $this->query('WHERE ' . $where, ['ticket_id' => $ticketId, 'user_id' => $userId]);
        return $rows[0] ?? null;
    }

    public function find(int $ticketId): ?array
    {
        $rows = $this->query('WHERE t.id = :ticket_id', ['ticket_id' => $ticketId]);
        return $rows[0] ?? null;
    }

    public function assign(int $ticketId, int $technicianId, ?int $actorId = null): bool
    {
        $ticket = $this->find($ticketId);
        $statement = $this->pdo->prepare('UPDATE tickets SET technician_id = :technician_id, status = :status WHERE id = :id');
        $updated = $statement->execute(['technician_id' => $technicianId, 'status' => 'assigned', 'id' => $ticketId]);
        if ($updated && $ticket) {
            $activity = new TicketActivity($this->pdo);
            $activity->record($ticketId, $actorId, 'assigned', $ticket['status'], 'assigned', 'Technician assigned');
            $activity->notify($technicianId, $ticketId, 'A ticket has been assigned to you: ' . $ticket['title']);
            if ((int) $ticket['user_id'] !== $technicianId) $activity->notify((int) $ticket['user_id'], $ticketId, 'Your ticket has been assigned to a technician.');
        }
        return $updated;
    }

    public function updateStatus(int $ticketId, string $status, ?int $actorId = null): bool
    {
        $ticket = $this->find($ticketId);
        $fields = $status === 'resolved' ? ', resolved_at = CURRENT_TIMESTAMP' : ($status === 'closed' ? ', closed_at = CURRENT_TIMESTAMP' : '');
        $statement = $this->pdo->prepare("UPDATE tickets SET status = :status{$fields} WHERE id = :id");
        $updated = $statement->execute(['status' => $status, 'id' => $ticketId]);
        if ($updated && $ticket && $ticket['status'] !== $status) {
            $activity = new TicketActivity($this->pdo);
            $activity->record($ticketId, $actorId, 'status_changed', $ticket['status'], $status, 'Ticket status updated');
            $message = 'Ticket status changed to ' . str_replace('_', ' ', $status) . '.';
            $activity->notify((int) $ticket['user_id'], $ticketId, $message);
            if ($ticket['technician_id'] && (int) $ticket['technician_id'] !== (int) $ticket['user_id']) $activity->notify((int) $ticket['technician_id'], $ticketId, $message);
        }
        return $updated;
    }

    public function countForUser(int $userId, string $role): array
    {
        $column = $role === 'employee' ? 'user_id' : 'technician_id';
        $statement = $this->pdo->prepare("SELECT status, COUNT(*) AS total FROM tickets WHERE {$column} = :user_id GROUP BY status");
        $statement->execute(['user_id' => $userId]);
        $counts = array_fill_keys(self::STATUSES, 0);
        foreach ($statement->fetchAll() as $row) { $counts[$row['status']] = (int) $row['total']; }
        return $counts;
    }

    public function countAll(): array
    {
        $counts = array_fill_keys(self::STATUSES, 0);
        foreach ($this->pdo->query('SELECT status, COUNT(*) AS total FROM tickets GROUP BY status')->fetchAll() as $row) { $counts[$row['status']] = (int) $row['total']; }
        return $counts;
    }

    public function recentActivity(int $limit = 8): array
    {
        $limit = max(1, min($limit, 50));
        return $this->pdo->query(
            'SELECT t.id, t.title, t.status, t.updated_at,
                    CONCAT(requester.first_name, \' \', requester.last_name) AS requester_name,
                    CONCAT(tech.first_name, \' \', tech.last_name) AS technician_name
             FROM tickets t
             JOIN users requester ON requester.id = t.user_id
             LEFT JOIN users tech ON tech.id = t.technician_id
             ORDER BY t.updated_at DESC
             LIMIT ' . $limit
        )->fetchAll();
    }
}