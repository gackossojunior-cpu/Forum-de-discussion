<?php
require('database.php');

if (isset($_POST['validate'])) {
    if (!empty($_POST['pseudo']) && !empty($_POST['lastname']) && !empty($_POST['firstname']) && !empty($_POST['password'])) {
        $user_pseudo = trim($_POST['pseudo']);
        $user_lastname = trim($_POST['lastname']);
        $user_firstname = trim($_POST['firstname']);
        $user_password = $_POST['password'];

        if (strlen($user_pseudo) < 3 || strlen($user_lastname) < 2 || strlen($user_firstname) < 2 || strlen($user_password) < 6) {
            $errorMsg = 'Veuillez vérifier les informations saisies. Le pseudo et les noms doivent être valides et le mot de passe doit contenir au moins 6 caractères.';
        } else {
            $user_pseudo = htmlspecialchars($user_pseudo);
            $user_lastname = htmlspecialchars($user_lastname);
            $user_firstname = htmlspecialchars($user_firstname);
            $user_password_hash = password_hash($user_password, PASSWORD_DEFAULT);

            $checkIfUserAlreadyExists = $bdb->prepare('SELECT pseudo FROM users WHERE pseudo = ?');
            $checkIfUserAlreadyExists->execute(array($user_pseudo));

            if ($checkIfUserAlreadyExists->rowCount() == 0) {
                $insertUser = $bdb->prepare('INSERT INTO users (pseudo, nom, prenom, mdp) VALUES (?, ?, ?, ?)');
                $insertUser->execute(array($user_pseudo, $user_lastname, $user_firstname, $user_password_hash));

                $getInfoOfThisUserReq = $bdb->prepare('SELECT id, pseudo, nom, prenom FROM users WHERE pseudo = ? AND nom = ? AND prenom = ?');
                $getInfoOfThisUserReq->execute(array($user_pseudo, $user_lastname, $user_firstname));
                $userInfos = $getInfoOfThisUserReq->fetch();

                $_SESSION['auth'] = true;
                $_SESSION['id'] = $userInfos['id'];
                $_SESSION['lastname'] = $userInfos['nom'];
                $_SESSION['firstname'] = $userInfos['prenom'];
                $_SESSION['pseudo'] = $userInfos['pseudo'];

                header('Location: index.php');
                exit;
            } else {
                $errorMsg = 'L\'utilisateur existe déjà !';
            }
        }
    } else {
        $errorMsg = 'Veuillez remplir tous les champs !';
    }
}