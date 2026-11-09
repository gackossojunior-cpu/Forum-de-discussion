<?php
require_once __DIR__ . '/../database.php';
use Forum\Infrastructure\Realtime;
use Forum\Repositories\NotificationRepository;
use Forum\Repositories\QuestionRepository;
use Forum\Repositories\ReplyRepository;
use Forum\Services\ReplyService;

if (empty($_SESSION['auth'])) {
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Connectez-vous pour publier une réponse.']);
        exit;
    }
    header('Location: ../login.php');
    exit;
}

if (isset($_POST['reply'], $_POST['question_id'])) {
    $questionId = (int)$_POST['question_id'];
    try {
        $questions = new QuestionRepository($bdb);
        $question = $questions->findWithAuthor($questionId);
        $replies = new ReplyRepository($bdb);
        $replyId = (new ReplyService(
            $replies,
            $questions
        ))->publish($questionId, (int)$_SESSION['id'], $_POST['contenu'] ?? '');

        $reply = $replies->findForRealtime($replyId);
        $event = null;
        if ($reply) {
            $event = [
                'id' => (int)$reply['id'],
                'questionId' => (int)$reply['question_id'],
                'content' => $reply['contenu'],
                'userId' => (int)$reply['utilisateur_id'],
                'author' => $reply['pseudo'],
                'dateLabel' => date('d/m/Y à H:i', strtotime($reply['date_creation'])),
                'profileUrl' => 'user-profile.php?id=' . (int)$reply['utilisateur_id'],
            ];
            Realtime::emit(['type' => 'reply:new', 'reply' => $event]);

            if ((int)$question['user_id'] !== (int)$_SESSION['id']) {
                $notificationId = (new NotificationRepository($bdb))->create(
                    (int)$question['user_id'],
                    'Nouvelle réponse à votre question',
                    $reply['pseudo'] . ' a répondu à « ' . $question['titre'] . ' ».'
                );
                Realtime::emit([
                    'type' => 'notification:new',
                    'notification' => [
                        'id' => $notificationId,
                        'userId' => (int)$question['user_id'],
                        'title' => 'Nouvelle réponse à votre question',
                        'message' => $reply['pseudo'] . ' a répondu à « ' . $question['titre'] . ' ».',
                        'dateLabel' => date('d/m/Y à H:i'),
                        'url' => '/question-detail.php?id=' . $questionId,
                    ],
                ]);
            }
        } else {
            error_log('Published forum reply could not be loaded for realtime delivery: ' . $replyId);
        }

        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true, 'reply' => $event], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        header('Location: ../question-detail.php?id=' . $questionId);
        exit;
    } catch (InvalidArgumentException $exception) {
        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            http_response_code(422);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $errorMsg = $exception->getMessage();
    }
}
