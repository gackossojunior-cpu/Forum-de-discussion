<?php
require_once __DIR__ . '/../database.php';
use Forum\Repositories\QuestionRepository;
use Forum\Services\QuestionService;

if (empty($_SESSION['auth'])) {
    header('Location: ../login.php');
    exit;
}

if (isset($_POST['update'])) {
    $questionId = (int)($_POST['question_id'] ?? 0);
    $titre = trim($_POST['titre'] ?? '');
    $contenu = trim($_POST['contenu'] ?? '');

    try {
        $updated = (new QuestionService(new QuestionRepository($bdb)))->update(
            $questionId,
            (int)$_SESSION['id'],
            $titre,
            $contenu
        );
        if ($updated) {
            header('Location: ../question-detail.php?id=' . $questionId);
            exit;
        }
        $errorMsg = 'Vous n\'avez pas le droit de modifier cette question';
    } catch (InvalidArgumentException $exception) {
        $errorMsg = $exception->getMessage();
    }
}
