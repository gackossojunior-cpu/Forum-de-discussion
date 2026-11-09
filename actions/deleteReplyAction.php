<?php
require('database.php');

if (!isset($_SESSION['auth']) || !$_SESSION['auth']) {
    header('Location: ../login.php');
    exit;
}

if (isset($_GET['id'])) {
    $reply_id = (int)$_GET['id'];

    // Récupérer la question_id pour rediriger après
    $getQuestion = $bdb->prepare('SELECT question_id FROM reponses WHERE id = ?');
    $getQuestion->execute(array($reply_id));
    $reply = $getQuestion->fetch();

    if ($reply) {
        // Vérifier que c'est bien l'auteur
        $checkOwner = $bdb->prepare('SELECT utilisateur_id FROM reponses WHERE id = ?');
        $checkOwner->execute(array($reply_id));
        $replyData = $checkOwner->fetch();

        if ($replyData && $replyData['utilisateur_id'] == $_SESSION['id']) {
            $req = $bdb->prepare('DELETE FROM reponses WHERE id = ?');
            $req->execute(array($reply_id));
        }

        header('Location: ../question-detail.php?id=' . $reply['question_id']);
        exit;
    }
}

header('Location: ../index.php');
exit;
?>
