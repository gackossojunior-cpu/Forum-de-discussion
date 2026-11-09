<?php
namespace Forum\Services;

use Forum\Repositories\QuestionRepository;
use InvalidArgumentException;

final class QuestionService
{
    public function __construct(private QuestionRepository $questions)
    {
    }

    public function publish(string $title, string $content, int $categoryId, int $userId): int
    {
        $title = trim($title);
        $content = trim($content);
        if ($title === '' || $content === '') {
            throw new InvalidArgumentException('Veuillez remplir tous les champs');
        }
        if (!$this->questions->categoryExists($categoryId)) {
            throw new InvalidArgumentException('Veuillez sélectionner une catégorie valide.');
        }

        return $this->questions->create($title, $content, $categoryId, $userId);
    }

    public function update(int $questionId, int $userId, string $title, string $content): bool
    {
        $title = trim($title);
        $content = trim($content);
        if ($title === '' || $content === '') {
            throw new InvalidArgumentException('Veuillez remplir tous les champs');
        }
        if (!$this->questions->findOwned($questionId, $userId)) {
            return false;
        }

        return $this->questions->updateOwned($questionId, $userId, $title, $content);
    }

    public function delete(int $questionId, int $userId): bool
    {
        return $this->questions->deleteOwned($questionId, $userId);
    }
}
