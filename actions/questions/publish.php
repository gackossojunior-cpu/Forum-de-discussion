<?php
require_once __DIR__ . '/../database.php';
use Forum\Repositories\QuestionRepository;
use Forum\Services\QuestionService;

if (empty($_SESSION['auth'])) {
    header('Location: login.php');
    exit;
}

if (isset($_POST['publish'])) {
    $categorieId = isset($_POST['categorie_id']) ? (int)$_POST['categorie_id'] : 1;

    try {
        (new QuestionService(new QuestionRepository($bdb)))->publish(
            $_POST['titre'] ?? '',
            $_POST['contenu'] ?? '',
            $categorieId,
            (int)$_SESSION['id']
        );
        header('Location: index.php');
        exit;
    } catch (InvalidArgumentException $exception) {
        $errorMsg = $exception->getMessage();
    }
}
