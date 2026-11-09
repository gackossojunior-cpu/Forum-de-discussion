<?php
require_once __DIR__ . '/../database.php';
use Forum\Repositories\QuestionRepository;
use Forum\Infrastructure\Realtime;
use Forum\Services\QuestionService;

if (empty($_SESSION['auth'])) {
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Connectez-vous pour publier une question.']);
        exit;
    }
    header('Location: login.php');
    exit;
}

if (isset($_POST['publish'])) {
    $categorieId = isset($_POST['categorie_id']) ? (int)$_POST['categorie_id'] : 1;

    try {
        $questions = new QuestionRepository($bdb);
        $questionId = (new QuestionService($questions))->publish(
            $_POST['titre'] ?? '',
            $_POST['contenu'] ?? '',
            $categorieId,
            (int)$_SESSION['id']
        );

        $question = $questions->findForRealtime($questionId);
        if ($question) {
            $event = [
                'id' => (int)$question['id'],
                'title' => $question['titre'],
                'content' => $question['contenu'],
                'userId' => (int)$question['utilisateur_id'],
                'author' => $question['pseudo'],
                'categoryId' => (int)$question['categorie_id'],
                'category' => $question['categorie'],
                'dateLabel' => date('d/m/Y à H:i', strtotime($question['date_creation'])),
                'url' => 'question-detail.php?id=' . (int)$question['id'],
            ];
            Realtime::emit(['type' => 'question:new', 'question' => $event]);
        } else {
            error_log('Published forum question could not be loaded for realtime delivery: ' . $questionId);
        }

        if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => true,
                'question' => $event ?? null,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        header('Location: index.php');
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
