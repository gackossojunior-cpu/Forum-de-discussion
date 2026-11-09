<?php
namespace Forum\Services;

use Forum\Repositories\QuestionRepository;
use Forum\Repositories\ReplyRepository;
use InvalidArgumentException;

final class ReplyService
{
    public function __construct(
        private ReplyRepository $replies,
        private QuestionRepository $questions
    ) {
    }

    public function publish(int $questionId, int $userId, string $content): int
    {
        $content = trim($content);
        if ($content === '') {
            throw new InvalidArgumentException('Veuillez écrire une réponse');
        }
        if (!$this->questions->findWithAuthor($questionId)) {
            throw new InvalidArgumentException('Cette question n’existe plus.');
        }

        return $this->replies->create($questionId, $userId, $content);
    }

    public function delete(int $replyId, int $userId): ?int
    {
        $reply = $this->replies->findOwned($replyId, $userId);
        if (!$reply) {
            return null;
        }

        $this->replies->deleteOwned($replyId, $userId);
        return (int)$reply['question_id'];
    }
}
