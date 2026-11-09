<?php
require_once __DIR__ . '/../database.php';

if (empty($_SESSION['auth'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode invalide');
}

$raison = trim($_POST['raison'] ?? '');
$description = trim($_POST['description'] ?? '');
$questionId = isset($_POST['question_id']) ? (int)$_POST['question_id'] : null;
$replyId = isset($_POST['reponse_id']) ? (int)$_POST['reponse_id'] : null;

if ($raison === '' || ((!$questionId && !$replyId) || ($questionId && $replyId))) {
    http_response_code(400);
    exit('Signalement incomplet');
}

$statement = $bdb->prepare(
    'INSERT INTO signalements (utilisateur_id, question_id, reponse_id, raison, description)
     VALUES (?, ?, ?, ?, ?)'
);
$statement->execute([$_SESSION['id'], $questionId, $replyId, $raison, $description]);

$_SESSION['message'] = 'Signalement envoyé.';
$returnPath = $questionId
    ? '../question-detail.php?id=' . $questionId
    : '../index.php';
header('Location: ' . $returnPath);
exit;
