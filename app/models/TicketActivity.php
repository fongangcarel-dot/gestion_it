<?php

declare(strict_types=1);

final class TicketActivity
{
    public function __construct(private PDO $pdo)
    {
    }

    public function record(int $ticketId, ?int $actorId, string $action, ?string $oldStatus, ?string $newStatus, ?string $details): void
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO ticket_history (ticket_id, actor_id, action, old_status, new_status, details)
             VALUES (:ticket_id, :actor_id, :action, :old_status, :new_status, :details)'
        );
        $statement->execute([
            'ticket_id' => $ticketId, 'actor_id' => $actorId, 'action' => $action,
            'old_status' => $oldStatus, 'new_status' => $newStatus, 'details' => $details,
        ]);
    }

    public function notify(int $userId, ?int $ticketId, string $message): void
    {
        $statement = $this->pdo->prepare('INSERT INTO notifications (user_id, ticket_id, message) VALUES (:user_id, :ticket_id, :message)');
        $statement->execute(['user_id' => $userId, 'ticket_id' => $ticketId, 'message' => $message]);
    }

    public function history(int $ticketId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT h.*, COALESCE(CONCAT(u.first_name, \' \', u.last_name), \'System\') AS actor_name
             FROM ticket_history h LEFT JOIN users u ON u.id = h.actor_id
             WHERE h.ticket_id = :ticket_id ORDER BY h.created_at DESC, h.id DESC'
        );
        $statement->execute(['ticket_id' => $ticketId]);
        return $statement->fetchAll();
    }

    public function unread(int $userId, int $limit = 8): array
    {
        $limit = max(1, min($limit, 30));
        $statement = $this->pdo->prepare('SELECT n.*, t.title AS ticket_title FROM notifications n LEFT JOIN tickets t ON t.id = n.ticket_id WHERE n.user_id = :user_id AND n.is_read = 0 ORDER BY n.created_at DESC, n.id DESC LIMIT ' . $limit);
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }
}
