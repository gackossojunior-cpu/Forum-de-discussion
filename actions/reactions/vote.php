<?php
require_once __DIR__ . '/../database.php';

if (empty($_SESSION['auth'])) {
    header('Location: ../login.php');
    exit;
}

$type = $_GET['type'] ?? 'like';
$contextQuestionId = isset($_GET['question_id']) ? (int)$_GET['question_id'] : 0;
$replyId = isset($_GET['reponse_id']) ? (int)$_GET['reponse_id'] : null;
$questionId = $replyId ? null : $contextQuestionId;

if (!in_array($type, ['like', 'dislike'], true) || (!$questionId && !$replyId)) {
    http_response_code(400);
    exit('Paramètres invalides');
}

$statement = $bdb->prepare(
    'SELECT id, type FROM votes WHERE utilisateur_id = ? AND question_id <=> ? AND reponse_id <=> ?'
);
$statement->execute([$_SESSION['id'], $questionId, $replyId]);
$existingVote = $statement->fetch();

if ($existingVote) {
    if ($existingVote['type'] === $type) {
        $statement = $bdb->prepare('DELETE FROM votes WHERE id = ?');
        $statement->execute([$existingVote['id']]);
    } else {
        $statement = $bdb->prepare('UPDATE votes SET type = ? WHERE id = ?');
        $statement->execute([$type, $existingVote['id']]);
    }
} else {
    $statement = $bdb->prepare(
        'INSERT INTO votes (utilisateur_id, question_id, reponse_id, type) VALUES (?, ?, ?, ?)'
    );
    $statement->execute([$_SESSION['id'], $questionId, $replyId, $type]);
}

$returnPath = $questionId
    ? '../question-detail.php?id=' . $questionId
    : '../question-detail.php?id=' . $contextQuestionId;
header('Location: ' . $returnPath);
exit;
