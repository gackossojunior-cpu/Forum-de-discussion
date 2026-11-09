<?php
require_once __DIR__ . '/../database.php';
use Forum\Repositories\QuestionRepository;
use Forum\Repositories\ReplyRepository;
use Forum\Services\ReplyService;

if (empty($_SESSION['auth'])) {
    header('Location: ../login.php');
    exit;
}

$replyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$questionId = (new ReplyService(
    new ReplyRepository($bdb),
    new QuestionRepository($bdb)
))->delete($replyId, (int)$_SESSION['id']);

if ($questionId !== null) {
    header('Location: ../question-detail.php?id=' . $questionId);
    exit;
}

header('Location: ../index.php');
exit;
