<?php
require_once __DIR__ . '/../database.php';

if (isset($_POST['validate'])) {
    if (!empty($_POST['pseudo']) && !empty($_POST['lastname']) && !empty($_POST['firstname']) && !empty($_POST['password'])) {
        $userPseudo = trim($_POST['pseudo']);
        $userLastname = trim($_POST['lastname']);
        $userFirstname = trim($_POST['firstname']);
        $userPassword = $_POST['password'];

        if (strlen($userPseudo) < 3 || strlen($userLastname) < 2 || strlen($userFirstname) < 2 || strlen($userPassword) < 6) {
            $errorMsg = 'Veuillez vérifier les informations saisies. Le pseudo et les noms doivent être valides et le mot de passe doit contenir au moins 6 caractères.';
        } else {
            $statement = $bdb->prepare('SELECT id FROM users WHERE pseudo = ?');
            $statement->execute([$userPseudo]);
            $existingUser = $statement->fetch();

            if ($existingUser !== false) {
                $errorMsg = 'L\'utilisateur existe déjà !';
            } else {
                $insertUser = $bdb->prepare('INSERT INTO users (pseudo, nom, prenom, mdp) VALUES (?, ?, ?, ?)');
                $insertUser->execute([
                    $userPseudo,
                    $userLastname,
                    $userFirstname,
                    password_hash($userPassword, PASSWORD_DEFAULT),
                ]);

                $statement = $bdb->prepare('SELECT id, pseudo, nom, prenom, role FROM users WHERE id = ?');
                $statement->execute([$bdb->lastInsertId()]);
                $user = $statement->fetch();

                session_regenerate_id(true);
                $_SESSION['auth'] = true;
                $_SESSION['id'] = $user['id'];
                $_SESSION['lastname'] = $user['nom'];
                $_SESSION['firstname'] = $user['prenom'];
                $_SESSION['pseudo'] = $user['pseudo'];
                $_SESSION['role'] = $user['role'] ?? 'user';

                header('Location: index.php');
                exit;
            }
        }
    } else {
        $errorMsg = 'Veuillez remplir tous les champs !';
    }
}
