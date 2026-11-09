<?php
namespace Forum\Repositories;

use PDO;

final class NotificationRepository
{
    public function __construct(private PDO $database)
    {
    }

    public function create(int $userId, string $title, string $message): int
    {
        $statement = $this->database->prepare(
            'INSERT INTO notifications (utilisateur_id, titre, message) VALUES (?, ?, ?)'
        );
        $statement->execute([$userId, $title, $message]);
        return (int)$this->database->lastInsertId();
    }

    public function unreadCount(int $userId): int
    {
        $statement = $this->database->prepare(
            'SELECT COUNT(*) FROM notifications WHERE utilisateur_id = ? AND lue = 0'
        );
        $statement->execute([$userId]);
        return (int)$statement->fetchColumn();
    }

    public function markAllRead(int $userId): void
    {
        $statement = $this->database->prepare(
            'UPDATE notifications SET lue = 1 WHERE utilisateur_id = ? AND lue = 0'
        );
        $statement->execute([$userId]);
    }

    public function markRead(int $notificationId, int $userId): bool
    {
        $statement = $this->database->prepare(
            'UPDATE notifications SET lue = 1 WHERE id = ? AND utilisateur_id = ? AND lue = 0'
        );
        $statement->execute([$notificationId, $userId]);
        return $statement->rowCount() > 0;
    }
}
