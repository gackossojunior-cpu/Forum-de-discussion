<?php
require('database.php');

if (!isset($_SESSION['auth']) || !$_SESSION['auth']) {
    header('Location: login.php');
    exit;
}

if (isset($_POST['publish'])) {
    $titre = htmlspecialchars(trim($_POST['titre']));
    $contenu = htmlspecialchars(trim($_POST['contenu']));
    $categorie_id = isset($_POST['categorie_id']) ? (int)$_POST['categorie_id'] : 1;

    if (!empty($titre) && !empty($contenu)) {
        $req = $bdb->prepare('INSERT INTO questions (titre, contenu, utilisateur_id, categorie_id) VALUES (?, ?, ?, ?)');
        $req->execute(array($titre, $contenu, $_SESSION['id'], $categorie_id));
        header('Location: index.php');
        exit;
    } else {
        $errorMsg = "Veuillez remplir tous les champs";
    }
}
?>
