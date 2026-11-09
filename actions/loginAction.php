<?php
require('database.php');

if (isset($_POST['validate'])) {
    if (!empty($_POST['pseudo']) && !empty($_POST['password'])) {
        $user_pseudo = trim($_POST['pseudo']);
        $user_password = $_POST['password'];

        $checkIfUserAlreadyExists = $bdb->prepare('SELECT * FROM users WHERE pseudo = ?');
        $checkIfUserAlreadyExists->execute(array($user_pseudo));

        if ($checkIfUserAlreadyExists->rowCount() > 0) {
            $userInfos = $checkIfUserAlreadyExists->fetch();

            if (password_verify($user_password, $userInfos['mdp'])) {
                $_SESSION['auth'] = true;
                $_SESSION['id'] = $userInfos['id'];
                $_SESSION['lastname'] = $userInfos['nom'];
                $_SESSION['firstname'] = $userInfos['prenom'];
                $_SESSION['pseudo'] = $userInfos['pseudo'];

                header('Location: index.php');
                exit;
            } else {
                $errorMsg = 'Mot de passe incorrect';
            }
        } else {
            $errorMsg = 'Utilisateur non trouvé';
        }
    } else {
        $errorMsg = 'Veuillez remplir tous les champs';
    }
}