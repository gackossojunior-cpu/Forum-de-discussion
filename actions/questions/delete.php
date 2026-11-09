<?php
require_once __DIR__ . '/../database.php';
use Forum\Repositories\QuestionRepository;
use Forum\Services\QuestionService;

if (empty($_SESSION['auth'])) {
    header('Location: ../login.php');
    exit;
}

$questionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($questionId > 0) {
    (new QuestionService(new QuestionRepository($bdb)))->delete($questionId, (int)$_SESSION['id']);
}

header('Location: ../index.php');
exit;
