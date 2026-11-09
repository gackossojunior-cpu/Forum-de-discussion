<?php
require('database.php');

if (!isset($_SESSION['auth']) || !$_SESSION['auth']) {
    header('Location: ../login.php');
    exit;
}

if (isset($_POST['reply']) && isset($_POST['question_id'])) {
    $question_id = (int)$_POST['question_id'];
    $contenu = htmlspecialchars(trim($_POST['contenu']));

    if (!empty($contenu)) {
        $req = $bdb->prepare('INSERT INTO reponses (contenu, question_id, utilisateur_id) VALUES (?, ?, ?)');
        $req->execute(array($contenu, $question_id, $_SESSION['id']));
        header('Location: ../question-detail.php?id=' . $question_id);
        exit;
    } else {
        $errorMsg = "Veuillez écrire une réponse";
    }
}
?>
