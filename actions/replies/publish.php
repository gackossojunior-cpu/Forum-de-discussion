<?php
require_once __DIR__ . '/../database.php';
use Forum\Repositories\QuestionRepository;
use Forum\Repositories\ReplyRepository;
use Forum\Services\ReplyService;

if (empty($_SESSION['auth'])) {
    header('Location: ../login.php');
    exit;
}

if (isset($_POST['reply'], $_POST['question_id'])) {
    $questionId = (int)$_POST['question_id'];
    try {
        (new ReplyService(
            new ReplyRepository($bdb),
            new QuestionRepository($bdb)
        ))->publish($questionId, (int)$_SESSION['id'], $_POST['contenu'] ?? '');
        header('Location: ../question-detail.php?id=' . $questionId);
        exit;
    } catch (InvalidArgumentException $exception) {
        $errorMsg = $exception->getMessage();
    }
}
