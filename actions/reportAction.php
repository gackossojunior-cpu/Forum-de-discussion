<?php
require('../actions/database.php');

if (!isset($_SESSION['auth']) || $_SESSION['auth'] == false) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    die('Méthode invalide');
}

$raison = htmlspecialchars($_POST['raison'] ?? '');
$description = htmlspecialchars($_POST['description'] ?? '');
$question_id = $_POST['question_id'] ?? null;
$reponse_id = $_POST['reponse_id'] ?? null;

if (!$raison || (!$question_id && !$reponse_id)) {
    die('Champs incomplets');
}

try {
    $stmt = $bdb->prepare("INSERT INTO signalements (utilisateur_id, question_id, reponse_id, raison, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['id'], $question_id, $reponse_id, $raison, $description]);

    // Message de succès
    $_SESSION['message'] = '✓ Signalement envoyé!';

    if ($question_id) {
        header('Location: ../question-detail.php?id=' . $question_id);
    }

} catch (PDOException $e) {
    die('Erreur : ' . $e->getMessage());
}
?>
