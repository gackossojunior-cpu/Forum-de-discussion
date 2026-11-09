<?php
namespace Forum\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $database)
    {
    }

    public function findProfile(int $userId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT id, pseudo, nom, prenom, date_inscription
             FROM users
             WHERE id = ?'
        );
        $statement->execute([$userId]);
        $user = $statement->fetch();
        return $user ?: null;
    }

    public function countReplies(int $userId): int
    {
        $statement = $this->database->prepare(
            'SELECT COUNT(*) FROM reponses WHERE utilisateur_id = ?'
        );
        $statement->execute([$userId]);
        return (int)$statement->fetchColumn();
    }
}
