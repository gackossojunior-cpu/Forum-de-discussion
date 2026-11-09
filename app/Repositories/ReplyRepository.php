<?php
namespace Forum\Repositories;

use PDO;

final class ReplyRepository
{
    public function __construct(private PDO $database)
    {
    }

    public function create(int $questionId, int $userId, string $content): int
    {
        $statement = $this->database->prepare(
            'INSERT INTO reponses (contenu, question_id, utilisateur_id) VALUES (?, ?, ?)'
        );
        $statement->execute([$content, $questionId, $userId]);
        return (int)$this->database->lastInsertId();
    }

    public function findForRealtime(int $replyId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT r.id, r.question_id, r.contenu, r.utilisateur_id, r.date_creation, u.pseudo
             FROM reponses r
             JOIN users u ON u.id = r.utilisateur_id
             WHERE r.id = ?'
        );
        $statement->execute([$replyId]);
        $reply = $statement->fetch();
        return $reply ?: null;
    }

    public function findOwned(int $replyId, int $userId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT id, question_id FROM reponses WHERE id = ? AND utilisateur_id = ?'
        );
        $statement->execute([$replyId, $userId]);
        $reply = $statement->fetch();
        return $reply ?: null;
    }

    public function deleteOwned(int $replyId, int $userId): bool
    {
        $statement = $this->database->prepare(
            'DELETE FROM reponses WHERE id = ? AND utilisateur_id = ?'
        );
        $statement->execute([$replyId, $userId]);
        return $statement->rowCount() > 0;
    }
}
