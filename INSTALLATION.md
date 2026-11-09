# Installation du forum sur Ubuntu

Ce guide utilise Apache, PHP et MariaDB depuis les paquets Ubuntu. Le projet est configuré pour le port MySQL standard `3306`; il n'a pas besoin de XAMPP.

## Installation alternative avec Docker

Prérequis : Docker Engine avec le plugin Docker Compose. Depuis la racine du projet :

```bash
cp .env.example .env
nano .env
```

Remplacez `DB_PASSWORD` et `DB_ROOT_PASSWORD` dans `.env` par deux mots de passe longs et uniques. Puis construisez et démarrez les conteneurs :

```bash
docker compose up --build -d
docker compose ps
```

La première initialisation de MariaDB peut prendre quelques minutes. Si `db` n'est pas encore `healthy`, attendez puis vérifiez de nouveau avec `docker compose ps`. Si l'application est restée arrêtée après un échec, relancez-la avec `docker compose up -d`.

Quand le service de base de données est prêt, ouvrez `http://localhost:8080/setup.php` et cliquez sur **Initialiser la base de données**. Le forum est accessible sur `http://localhost:8080`.

`docker compose down` arrête les conteneurs sans effacer la base. Pour supprimer aussi les données persistantes, exécutez `docker compose down -v`.

Les identifiants locaux sont stockés dans `.env`, exclu du contrôle de version. Ne partagez pas ce fichier.

Pour l'installation sans Docker, continuez ci-dessous.

## 1. Installer les dépendances

Dans un terminal :

```bash
sudo apt update
sudo apt install apache2 mariadb-server php libapache2-mod-php php-mysql
sudo systemctl enable --now apache2 mariadb
```

Vérifier que PHP et son pilote MySQL sont présents :

```bash
php -v
php -m | grep -E 'PDO|pdo_mysql'
```

La seconde commande doit afficher `PDO` et `pdo_mysql`.

## 2. Déployer le projet

Depuis le dossier du projet :

```bash
sudo mkdir -p /var/www/html/forum
sudo cp -a . /var/www/html/forum/
sudo cp /var/www/html/forum/config.example.php /var/www/html/forum/config.php
sudo chown -R "$USER":www-data /var/www/html/forum
sudo find /var/www/html/forum -type d -exec chmod 755 {} \;
sudo find /var/www/html/forum -type f -exec chmod 644 {} \;
sudo chmod 640 /var/www/html/forum/config.php
```

## 3. Créer la base et un compte dédié

Générer un mot de passe solide et le conserver pour la configuration PHP :

```bash
openssl rand -base64 24
```

Ouvrir la console MariaDB :

```bash
sudo mariadb
```

Remplacer `REMPLACER_PAR_UN_MOT_DE_PASSE_SOLIDE` par le mot de passe généré :

```sql
CREATE DATABASE forum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'forum_app'@'127.0.0.1' IDENTIFIED BY 'REMPLACER_PAR_UN_MOT_DE_PASSE_SOLIDE';
GRANT ALL PRIVILEGES ON forum.* TO 'forum_app'@'127.0.0.1';
FLUSH PRIVILEGES;
EXIT;
```

Cette application n'utilise pas le compte administrateur MariaDB (`root`).

## 4. Configurer l'accès à la base

Modifier le fichier local, qui n'est pas destiné à être partagé :

```bash
sudo nano /var/www/html/forum/config.php
```

Y reporter le même mot de passe que lors de la création de `forum_app` :

```php
<?php
if (!defined('FORUM_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}

return [
    'host' => '127.0.0.1',
    'port' => 3306,
    'database' => 'forum',
    'username' => 'forum_app',
    'password' => 'MOT_DE_PASSE_CHOISI',
];
```

Après l'enregistrement, rétablir les permissions de lecture pour Apache :

```bash
sudo chown "$USER":www-data /var/www/html/forum/config.php
sudo chmod 640 /var/www/html/forum/config.php
```

## 5. Initialiser et ouvrir le forum

Dans le navigateur, ouvrir :

```text
http://localhost/forum/setup.php
```

Cliquer sur **Initialiser la base de données**, puis accéder à :

```text
http://localhost/forum/
```

Créer un compte depuis **Inscription** pour commencer à utiliser le forum.

## Dépannage

- **Accès refusé à MariaDB** : vérifier le nom d'utilisateur et le mot de passe dans `config.php`, et le compte créé dans MariaDB.
- **Pilote MySQL introuvable / `could not find driver`** : installer `php-mysql`, puis redémarrer Apache avec `sudo systemctl restart apache2`.
- **Page blanche ou erreur PHP** : vérifier Apache avec `sudo systemctl status apache2` et consulter `/var/log/apache2/error.log`.
- **Erreur de connexion** : vérifier que MariaDB tourne avec `sudo systemctl status mariadb`.

Ne publiez pas `config.php` et n'utilisez pas le compte `root` de MariaDB dans l'application.
