<?php
require('database.php');

if (!isset($_SESSION['auth']) || !$_SESSION['auth']) {
    header('Location: ../login.php');
    exit;
}

if (isset($_GET['id'])) {
    $question_id = (int)$_GET['id'];

    // Vérifier que c'est bien l'auteur
    $checkOwner = $bdb->prepare('SELECT utilisateur_id FROM questions WHERE id = ?');
    $checkOwner->execute(array($question_id));
    $question = $checkOwner->fetch();

    if ($question && $question['utilisateur_id'] == $_SESSION['id']) {
        $req = $bdb->prepare('DELETE FROM questions WHERE id = ?');
        $req->execute(array($question_id));
    }
}

header('Location: ../index.php');
exit;
?>
