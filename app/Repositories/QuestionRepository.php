<?php
namespace Forum\Repositories;

use PDO;

final class QuestionRepository
{
    public function __construct(private PDO $database)
    {
    }

    public function categories(): array
    {
        return $this->database->query('SELECT * FROM categories ORDER BY nom')->fetchAll();
    }

    public function categoryExists(int $categoryId): bool
    {
        $statement = $this->database->prepare('SELECT 1 FROM categories WHERE id = ?');
        $statement->execute([$categoryId]);
        return (bool)$statement->fetchColumn();
    }

    public function search(string $search, int $categoryId): array
    {
        $sql = 'SELECT q.*, u.pseudo, c.nom AS categorie, COALESCE(reply_counts.total, 0) AS reponses_count
                FROM questions q
                LEFT JOIN users u ON u.id = q.utilisateur_id
                LEFT JOIN categories c ON c.id = q.categorie_id
                LEFT JOIN (
                    SELECT question_id, COUNT(*) AS total
                    FROM reponses
                    GROUP BY question_id
                ) reply_counts ON reply_counts.question_id = q.id';
        $conditions = [];
        $parameters = [];

        if ($search !== '') {
            $conditions[] = '(q.titre LIKE ? OR q.contenu LIKE ?)';
            $term = '%' . $search . '%';
            $parameters[] = $term;
            $parameters[] = $term;
        }
        if ($categoryId > 0) {
            $conditions[] = 'q.categorie_id = ?';
            $parameters[] = $categoryId;
        }
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY q.date_creation DESC';

        $statement = $this->database->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }

    public function findWithAuthor(int $questionId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT q.*, u.pseudo, u.nom, u.prenom, u.id AS user_id,
                    COALESCE(vote_counts.like_count, 0) AS like_count,
                    COALESCE(vote_counts.dislike_count, 0) AS dislike_count
             FROM questions q
             JOIN users u ON u.id = q.utilisateur_id
             LEFT JOIN (
                 SELECT question_id,
                        SUM(type = \'like\') AS like_count,
                        SUM(type = \'dislike\') AS dislike_count
                 FROM votes
                 WHERE question_id IS NOT NULL
                 GROUP BY question_id
             ) vote_counts ON vote_counts.question_id = q.id
             WHERE q.id = ?'
        );
        $statement->execute([$questionId]);
        $question = $statement->fetch();
        return $question ?: null;
    }

    public function repliesWithVoteCounts(int $questionId): array
    {
        $statement = $this->database->prepare(
            'SELECT r.*, u.pseudo, u.nom, u.prenom, u.id AS user_id,
                    COALESCE(vote_counts.like_count, 0) AS like_count,
                    COALESCE(vote_counts.dislike_count, 0) AS dislike_count
             FROM reponses r
             JOIN users u ON u.id = r.utilisateur_id
             LEFT JOIN (
                 SELECT reponse_id,
                        SUM(type = \'like\') AS like_count,
                        SUM(type = \'dislike\') AS dislike_count
                 FROM votes
                 WHERE reponse_id IS NOT NULL
                 GROUP BY reponse_id
             ) vote_counts ON vote_counts.reponse_id = r.id
             WHERE r.question_id = ?
             ORDER BY r.date_creation DESC'
        );
        $statement->execute([$questionId]);
        return $statement->fetchAll();
    }

    public function findOwned(int $questionId, int $userId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT * FROM questions WHERE id = ? AND utilisateur_id = ?'
        );
        $statement->execute([$questionId, $userId]);
        $question = $statement->fetch();
        return $question ?: null;
    }

    public function create(string $title, string $content, int $categoryId, int $userId): int
    {
        $statement = $this->database->prepare(
            'INSERT INTO questions (titre, contenu, utilisateur_id, categorie_id) VALUES (?, ?, ?, ?)'
        );
        $statement->execute([$title, $content, $userId, $categoryId]);
        return (int)$this->database->lastInsertId();
    }

    public function findForRealtime(int $questionId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT q.id, q.titre, q.contenu, q.utilisateur_id, q.categorie_id, q.date_creation,
                    u.pseudo, c.nom AS categorie
             FROM questions q
             JOIN users u ON u.id = q.utilisateur_id
             LEFT JOIN categories c ON c.id = q.categorie_id
             WHERE q.id = ?'
        );
        $statement->execute([$questionId]);
        $question = $statement->fetch();
        return $question ?: null;
    }

    public function updateOwned(int $questionId, int $userId, string $title, string $content): bool
    {
        $statement = $this->database->prepare(
            'UPDATE questions SET titre = ?, contenu = ? WHERE id = ? AND utilisateur_id = ?'
        );
        $statement->execute([$title, $content, $questionId, $userId]);
        return $this->findOwned($questionId, $userId) !== null;
    }

    public function deleteOwned(int $questionId, int $userId): bool
    {
        $statement = $this->database->prepare(
            'DELETE FROM questions WHERE id = ? AND utilisateur_id = ?'
        );
        $statement->execute([$questionId, $userId]);
        return $statement->rowCount() > 0;
    }

    public function forUser(int $userId): array
    {
        $statement = $this->database->prepare(
            'SELECT q.*, COALESCE(reply_counts.total, 0) AS reponses_count
             FROM questions q
             LEFT JOIN (
                 SELECT question_id, COUNT(*) AS total
                 FROM reponses
                 GROUP BY question_id
             ) reply_counts ON reply_counts.question_id = q.id
             WHERE q.utilisateur_id = ?
             ORDER BY q.date_creation DESC'
        );
        $statement->execute([$userId]);
        return $statement->fetchAll();
    }
}
