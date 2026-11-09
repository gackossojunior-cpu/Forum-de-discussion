<?php
namespace Forum\Repositories;

use PDO;
use RuntimeException;

final class MessagingRepository
{
    public function __construct(private PDO $database)
    {
    }

    public function conversationsForUser(int $userId): array
    {
        $statement = $this->database->prepare(
            'SELECT c.id, c.type, c.title,
                    GROUP_CONCAT(DISTINCT CASE WHEN u.id <> ? THEN u.pseudo END ORDER BY u.pseudo SEPARATOR \', \') AS participants,
                    GROUP_CONCAT(DISTINCT CASE WHEN u.id <> ? THEN u.id END) AS participant_ids,
                    latest.body AS last_message,
                    latest.date_creation AS last_message_at,
                    (SELECT COUNT(*) FROM messages unread
                     JOIN message_receipts receipt ON receipt.message_id = unread.id
                     WHERE unread.conversation_id = c.id AND receipt.user_id = ? AND receipt.read_at IS NULL) AS unread_count
             FROM conversations c
             JOIN conversation_members mine ON mine.conversation_id = c.id AND mine.user_id = ?
             JOIN conversation_members members ON members.conversation_id = c.id
             JOIN users u ON u.id = members.user_id
             LEFT JOIN (
                 SELECT m.conversation_id, m.body, m.date_creation
                 FROM messages m
                 JOIN (SELECT conversation_id, MAX(id) AS latest_id FROM messages GROUP BY conversation_id) latest_ids
                   ON latest_ids.latest_id = m.id
             ) latest ON latest.conversation_id = c.id
             GROUP BY c.id, c.type, c.title, latest.body, latest.date_creation
             ORDER BY COALESCE(latest.date_creation, c.date_creation) DESC'
        );
        $statement->execute([$userId, $userId, $userId, $userId]);
        return $statement->fetchAll();
    }

    public function contactsForUser(int $userId, string $search = ''): array
    {
        $statement = $this->database->prepare(
            'SELECT u.id, u.pseudo,
                    COALESCE(p.status, \'offline\') AS presence,
                    p.last_seen_at
             FROM users u
             LEFT JOIN user_presence p ON p.user_id = u.id
             WHERE u.id <> ? AND (? = \'\' OR u.pseudo LIKE ?)
             ORDER BY u.pseudo
             LIMIT 100'
        );
        $statement->execute([$userId, $search, '%' . $search . '%']);
        return $statement->fetchAll();
    }

    public function messagesForConversation(int $conversationId, int $userId, ?int $before = null): array
    {
        if (!$this->isMember($conversationId, $userId)) {
            throw new RuntimeException('Vous ne faites pas partie de cette conversation.');
        }

        $beforeClause = $before ? ' AND m.id < ?' : '';
        $statement = $this->database->prepare(
            'SELECT m.id, m.conversation_id, m.sender_id, m.body, m.message_type, m.date_creation,
                    u.pseudo AS sender_name,
                    (SELECT COUNT(*) FROM message_receipts r WHERE r.message_id = m.id AND r.read_at IS NOT NULL) AS read_count
             FROM messages m
             JOIN users u ON u.id = m.sender_id
             WHERE m.conversation_id = ?' . $beforeClause . '
             ORDER BY m.id DESC
             LIMIT 50'
        );
        $parameters = [$conversationId];
        if ($before) {
            $parameters[] = $before;
        }
        $statement->execute($parameters);
        return array_reverse($statement->fetchAll());
    }

    public function conversationMembers(int $conversationId): array
    {
        $statement = $this->database->prepare(
            'SELECT user_id FROM conversation_members WHERE conversation_id = ?'
        );
        $statement->execute([$conversationId]);
        return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function isMember(int $conversationId, int $userId): bool
    {
        $statement = $this->database->prepare(
            'SELECT 1 FROM conversation_members WHERE conversation_id = ? AND user_id = ?'
        );
        $statement->execute([$conversationId, $userId]);
        return (bool)$statement->fetchColumn();
    }

    public function createConversation(int $creatorId, array $memberIds, string $type, ?string $title): int
    {
        $normalizedMemberIds = [];
        foreach ($memberIds as $memberId) {
            $validatedId = filter_var($memberId, FILTER_VALIDATE_INT);
            if ($validatedId === false || $validatedId < 1) {
                throw new RuntimeException('Un participant sélectionné est invalide.');
            }
            $normalizedMemberIds[] = (int)$validatedId;
        }
        $memberIds = array_values(array_unique($normalizedMemberIds));
        $memberIds[] = $creatorId;
        $memberIds = array_values(array_unique($memberIds));

        if ($type === 'direct' && count($memberIds) !== 2) {
            throw new RuntimeException('Une conversation privée doit avoir deux participants.');
        }
        if ($type === 'group' && count($memberIds) < 3) {
            throw new RuntimeException('Un groupe doit avoir au moins trois participants.');
        }
        if (!in_array($type, ['direct', 'group'], true)) {
            throw new RuntimeException('Type de conversation invalide.');
        }
        if ($type === 'group' && trim((string)$title) === '') {
            throw new RuntimeException('Donnez un nom au groupe.');
        }

        $placeholders = implode(',', array_fill(0, count($memberIds), '?'));
        $check = $this->database->prepare("SELECT COUNT(*) FROM users WHERE id IN ({$placeholders})");
        $check->execute($memberIds);
        if ((int)$check->fetchColumn() !== count($memberIds)) {
            throw new RuntimeException('Un participant sélectionné n’existe plus.');
        }

        if ($type === 'direct') {
            $existing = $this->database->prepare(
                'SELECT c.id
                 FROM conversations c
                 JOIN conversation_members cm ON cm.conversation_id = c.id
                 WHERE c.type = \'direct\' AND cm.user_id IN (?, ?)
                 GROUP BY c.id
                 HAVING COUNT(DISTINCT cm.user_id) = 2 AND COUNT(*) = 2
                 LIMIT 1'
            );
            $existing->execute($memberIds);
            $existingId = $existing->fetchColumn();
            if ($existingId) {
                return (int)$existingId;
            }
        }

        $this->database->beginTransaction();
        try {
            $create = $this->database->prepare(
                'INSERT INTO conversations (type, title, created_by) VALUES (?, ?, ?)'
            );
            $create->execute([$type, $type === 'group' ? trim((string)$title) : null, $creatorId]);
            $conversationId = (int)$this->database->lastInsertId();

            $member = $this->database->prepare(
                'INSERT INTO conversation_members (conversation_id, user_id, role) VALUES (?, ?, ?)'
            );
            foreach ($memberIds as $memberId) {
                $member->execute([$conversationId, $memberId, $memberId === $creatorId ? 'admin' : 'member']);
            }

            $this->database->commit();
            return $conversationId;
        } catch (\Throwable $exception) {
            $this->database->rollBack();
            throw $exception;
        }
    }

    public function sendMessage(int $conversationId, int $senderId, string $body): array
    {
        $body = trim($body);
        if ($body === '' || mb_strlen($body) > 5000) {
            throw new RuntimeException('Le message doit contenir entre 1 et 5 000 caractères.');
        }
        if (!$this->isMember($conversationId, $senderId)) {
            throw new RuntimeException('Vous ne faites pas partie de cette conversation.');
        }

        $this->database->beginTransaction();
        try {
            $statement = $this->database->prepare(
                'INSERT INTO messages (conversation_id, sender_id, body) VALUES (?, ?, ?)'
            );
            $statement->execute([$conversationId, $senderId, $body]);
            $messageId = (int)$this->database->lastInsertId();

            $receipts = $this->database->prepare(
                'INSERT INTO message_receipts (message_id, user_id)
                 SELECT ?, user_id FROM conversation_members
                 WHERE conversation_id = ? AND user_id <> ?'
            );
            $receipts->execute([$messageId, $conversationId, $senderId]);

            $this->database->commit();
        } catch (\Throwable $exception) {
            $this->database->rollBack();
            throw $exception;
        }

        $message = $this->database->prepare(
            'SELECT m.id, m.conversation_id, m.sender_id, m.body, m.message_type, m.date_creation,
                    u.pseudo AS sender_name, 0 AS read_count
             FROM messages m JOIN users u ON u.id = m.sender_id WHERE m.id = ?'
        );
        $message->execute([$messageId]);
        return $message->fetch();
    }

    public function markRead(int $conversationId, int $userId): ?int
    {
        if (!$this->isMember($conversationId, $userId)) {
            throw new RuntimeException('Vous ne faites pas partie de cette conversation.');
        }
        $latest = $this->database->prepare(
            'SELECT MAX(id) FROM messages WHERE conversation_id = ?'
        );
        $latest->execute([$conversationId]);
        $messageId = $latest->fetchColumn();
        if (!$messageId) {
            return null;
        }

        $this->database->beginTransaction();
        try {
            $member = $this->database->prepare(
                'UPDATE conversation_members
                 SET last_read_message_id = ?, last_read_at = CURRENT_TIMESTAMP
                 WHERE conversation_id = ? AND user_id = ?'
            );
            $member->execute([$messageId, $conversationId, $userId]);
            $receipts = $this->database->prepare(
                'UPDATE message_receipts r
                 JOIN messages m ON m.id = r.message_id
                 SET r.read_at = COALESCE(r.read_at, CURRENT_TIMESTAMP),
                     r.delivered_at = COALESCE(r.delivered_at, CURRENT_TIMESTAMP)
                 WHERE m.conversation_id = ? AND r.user_id = ? AND r.read_at IS NULL'
            );
            $receipts->execute([$conversationId, $userId]);
            $this->database->commit();
        } catch (\Throwable $exception) {
            $this->database->rollBack();
            throw $exception;
        }
        return (int)$messageId;
    }
}
