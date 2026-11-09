<?php
require('../actions/database.php');

if (!isset($_SESSION['auth']) || $_SESSION['auth'] == false) {
    header('Location: ../login.php');
    exit;
}

$type = $_GET['type'] ?? 'like'; // like ou dislike
$question_id = $_GET['question_id'] ?? null;
$reponse_id = $_GET['reponse_id'] ?? null;

if (!$question_id && !$reponse_id) {
    die('Paramètres invalides');
}

try {
    // Vérifier s'il existe déjà un vote
    $stmt = $bdb->prepare("SELECT * FROM votes WHERE utilisateur_id = ? AND question_id = ? AND reponse_id = ?");
    $stmt->execute([$_SESSION['id'], $question_id, $reponse_id]);
    $existing = $stmt->fetch();

    if ($existing) {
        // Supprimer le vote existant
        $stmt = $bdb->prepare("DELETE FROM votes WHERE id = ?");
        $stmt->execute([$existing['id']]);
    } else {
        // Ajouter nouveau vote
        $stmt = $bdb->prepare("INSERT INTO votes (utilisateur_id, question_id, reponse_id, type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['id'], $question_id, $reponse_id, $type]);
    }

    // Rediriger
    if ($question_id) {
        header('Location: ../question-detail.php?id=' . $question_id);
    }

} catch (PDOException $e) {
    die('Erreur : ' . $e->getMessage());
}
?>
