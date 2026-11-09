<?php
require('database.php');

if (!isset($_SESSION['auth']) || !$_SESSION['auth']) {
    header('Location: ../login.php');
    exit;
}

if (isset($_POST['update'])) {
    $question_id = (int)$_POST['question_id'];
    $titre = htmlspecialchars(trim($_POST['titre']));
    $contenu = htmlspecialchars(trim($_POST['contenu']));

    // Vérifier que c'est bien l'auteur
    $checkOwner = $bdb->prepare('SELECT utilisateur_id FROM questions WHERE id = ?');
    $checkOwner->execute(array($question_id));
    $question = $checkOwner->fetch();

    if ($question && $question['utilisateur_id'] == $_SESSION['id']) {
        if (!empty($titre) && !empty($contenu)) {
            $req = $bdb->prepare('UPDATE questions SET titre = ?, contenu = ? WHERE id = ?');
            $req->execute(array($titre, $contenu, $question_id));
            header('Location: ../question-detail.php?id=' . $question_id);
            exit;
        } else {
            $errorMsg = "Veuillez remplir tous les champs";
        }
    } else {
        $errorMsg = "Vous n'avez pas le droit de modifier cette question";
    }
}
?>
