# 🚀 Forum Moderne et Professionnel v2.0

Bienvenue dans votre forum de discussion avec un design moderne, professionnel et entièrement responsive!

## ✨ Nouvelles Fonctionnalités (v2.0)

### 🎨 Design Professionnel
- ✅ Interface épurée avec une palette neutre et des accents bleus
- ✅ Thème clair/sombre, avec préférence mémorisée et prise en compte du thème système
- ✅ Boutons et contrôles cohérents, adaptés au tactile
- ✅ Mise en page responsive (mobile, tablette et ordinateur)
- ✅ Icônes Font Awesome et navigation intuitive

### 💬 Gestion Améliorée
- ✅ Système de catégories pour organiser les questions
- ✅ Votes positifs/négatifs (👍 👎) sur les questions et réponses
- ✅ Signalement de contenu problématique
- ✅ Système de notifications en temps réel
- ✅ Recherche avancée avec filtrage par catégorie

### 👥 Profils Utilisateurs
- ✅ Profils publics avec statistiques
- ✅ Compteur de questions posées
- ✅ Compteur de réponses données
- ✅ Date d'inscription membre

### 🔔 Notifications
- ✅ Système de notifications personnalisé
- ✅ Marquage des notifications comme lues
- ✅ Timestamps relatifs (il y a X minutes)
- ✅ Badge nouveau pour les non-lues

## 📋 Catégories Disponibles

1. **Technique** - Problèmes et questions techniques
2. **Général** - Discussions générales
3. **Aide** - Demandes d'aide et support
4. **Annonces** - Annonces importantes
5. **Autre** - Autres sujets

## 📁 Structure des modules

```
forum/
├── index.php                    # Accueil - Liste des questions
├── login.php                    # 🆕 Page de connexion rénovée
├── signup.php                   # 🆕 Page d'inscription rénovée
├── publish-question.php         # 🆕 Publier avec catégories
├── edit-question.php            # 🆕 Modifier une question
├── question-detail.php          # 🆕 Question avec réponses/votes/signalement
├── user-profile.php             # 🆕 Profil utilisateur amélioré
├── notifications.php            # 🆕 Page de notifications
├── setup.php                    # 🆕 Interface d'installation moderne
├── setup-action.php             # Traitement du setup
├── admin/                       # Tableau de bord et modération
├── app/
│   ├── Infrastructure/          # Connexion PDO et services techniques
│   ├── Repositories/            # Requêtes et persistance
│   └── Services/                # Règles métier
├── views/                       # Templates HTML rendus par les routes publiques
├── assets/
│   └── style.css                # Styles du forum
├── includes/
│   ├── admin.php                # Vérification des droits admin et CSRF
│   ├── formatting.php           # Échappement HTML et extraits
│   ├── head.php
│   └── navbar.php
└── actions/
    ├── auth/                    # Connexion, inscription et déconnexion
    ├── moderation/              # Signalements
    ├── questions/               # Création, modification, suppression
    ├── reactions/               # Votes
    ├── replies/                 # Réponses
    └── database.php             # Adaptateur de compatibilité vers le bootstrap
```

Les pages PHP à la racine restent les URL publiques du forum et servent de contrôleurs fins. Les vues des questions et profils se trouvent dans `views/`; les pages de publication et d’édition seront migrées suivant le même modèle progressivement. Les anciens noms de fichiers d’action restent des points d’entrée de compatibilité.

### Architecture PHP

- `app/bootstrap.php` initialise la session, le chargement automatique de la classe `Forum\` et la connexion commune à la base.
- `app/Infrastructure/Database.php` est l’unique fabrique PDO; `actions/database.php` maintient la compatibilité des scripts existants.
- `app/Repositories/` centralise les requêtes des questions, réponses et profils; les vues ne font pas d’accès SQL.
- `app/Services/` porte les validations et règles de publication, modification et suppression des questions et réponses.
- Les fichiers historiques `actions/*Action.php` délèguent aux actions organisées par domaine sous `actions/`.

Pour ajouter une fonctionnalité, placer les requêtes dans un repository, les règles métier dans un service, garder la route publique comme contrôleur et mettre son HTML dans `views/`. Cela permet de poursuivre la migration sans framework ni changement des URL existantes.

### Administration

Connectez-vous avec un compte dont le rôle MariaDB est `admin`, puis ouvrez **Administration** dans la navigation ou rendez-vous sur `/admin/`. Le tableau de bord affiche les statistiques et les signalements. Depuis un signalement, un administrateur peut le refuser ou l’accepter; l’acceptation supprime le contenu dans une transaction et conserve la trace du signalement. Les formulaires admin sont protégés par un jeton CSRF.

Après la mise à jour, ouvrez une fois `setup.php` et lancez l’initialisation pour ajouter la table `signalements`, conserver les anciens rapports et vérifier la colonne `users.role`. Déconnectez-vous puis reconnectez-vous pour charger le rôle dans la session.

Pour donner l’accès à un compte existant, remplacez `VOTRE_PSEUDO` par son pseudo dans cette commande MariaDB :

```sql
UPDATE users SET role = 'admin' WHERE pseudo = 'VOTRE_PSEUDO';
```

Le tableau de bord vérifie le rôle directement dans la base à chaque requête; masquer le lien dans le menu ne suffit pas à accorder l’accès.

## 🎯 Quick Start

Sur Ubuntu, suivez d'abord le [guide d'installation](INSTALLATION.md) pour installer Apache, PHP et MariaDB, créer la base et configurer `config.php`.

### Démarrer avec Docker

Prérequis : Docker Engine avec le plugin Docker Compose.

Depuis la racine du projet, copiez `.env.example` vers `.env`, puis remplacez les deux mots de passe par des valeurs longues et uniques :

```bash
cp .env.example .env
nano .env
docker compose up --build -d
```

La première initialisation de MariaDB peut prendre quelques minutes. Si l'application ne démarre pas immédiatement, vérifiez `docker compose ps`, attendez que `db` soit `healthy`, puis relancez `docker compose up -d`.

Ouvrez `http://localhost:8080/setup.php` et cliquez sur **Initialiser la base de données**. Le forum sera ensuite disponible sur `http://localhost:8080`.

Pour arrêter les conteneurs sans supprimer les données :

```bash
docker compose down
```

Les données MariaDB sont conservées dans le volume `forum_db_data`. Pour supprimer aussi ce volume et toutes les données du forum, utilisez `docker compose down -v`.

### 1️⃣ Initialiser la Base de Données
```
1. Allez sur: http://localhost/forum/setup.php
2. Cliquez "Initialiser la base de données"
3. Attendez le message de succès
```

### 2️⃣ Créer un Compte
```
1. Cliquez sur "Inscription"
2. Remplissez: Pseudo, Nom, Prénom, Mot de passe
3. Cliquez "S'inscrire"
```

### 3️⃣ Poser une Question
```
1. Cliquez sur "Nouvelle question" (menu)
2. Sélectionnez une catégorie
3. Écrivez le titre et la description
4. Cliquez "Publier la question"
```

### 4️⃣ Répondre et Voter
```
1. Cliquez sur une question pour l'ouvrir
2. Scrollez vers le bas
3. Écrivez votre réponse
4. Votez pour les meilleures réponses (👍)
```

## 🔍 Pages du Forum

| Page | URL | Description |
|------|-----|-------------|
| 🏠 Accueil | `/forum/` | Liste des questions avec recherche |
| 📝 Inscription | `/forum/signup.php` | Créer un compte |
| 🔑 Connexion | `/forum/login.php` | Se connecter |
| ➕ Nouvelle Question | `/forum/publish-question.php` | Poser une question |
| 💬 Détail Question | `/forum/question-detail.php?id=X` | Voir et répondre |
| ✏️ Modifier Question | `/forum/edit-question.php?id=X` | Éditer sa question |
| 👤 Profil | `/forum/user-profile.php?id=X` | Profil utilisateur |
| 🔔 Notifications | `/forum/notifications.php` | Vos notifications |
| 🛠️ Configuration | `/forum/setup.php` | Initialiseur BD |

## 🎨 Design Professionnel

### Palette de Couleurs
- **Primaire**: `#6366f1` (Indigo) - Actions principales
- **Secondaire**: `#8b5cf6` (Violet) - Accents
- **Succès**: `#10b981` (Vert) - Confirmations
- **Danger**: `#ef4444` (Rouge) - Suppressions
- **Warning**: `#f59e0b` (Orange) - Alertes

### Fonctionnalités CSS
- ✅ Dégradés linéaires attrayants
- ✅ Animations smooth sur les hovers
- ✅ Ombres cohérentes et professionnelles
- ✅ Variables CSS pour la maintenabilité
- ✅ Media queries pour le responsive

## 📱 Responsive Design

Fonctionne parfaitement sur:
- **📱 Mobile** (< 576px) - Single column
- **📱 Tablet** (576px - 992px) - Adaptable
- **💻 Desktop** (> 992px) - Full layout

## 🔐 Sécurité Améliorée

- ✅ Mots de passe hashés (PASSWORD_BCRYPT)
- ✅ Requêtes préparées contre les injections SQL
- ✅ Échappement des sorties (htmlspecialchars)
- ✅ Validation des entrées utilisateur
- ✅ Sessions sécurisées
- ✅ Protection des accès (auteur uniquement pour modifications)

## 💾 Base de Données (MySQL)

### Configuration
- **Host**: `127.0.0.1`
- **Port**: `3306` (port MySQL/MariaDB standard)
- **Base**: forum
- **User**: `forum_app` (compte applicatif dédié)
- **Charset**: utf8mb4
- Les identifiants sont dans le fichier local `config.php`, à créer depuis `config.example.php`. Ne partagez pas ce fichier.

### Tables
- `users` - Utilisateurs
- `questions` - Questions postées
- `reponses` - Réponses
- `categories` - Catégories
- `votes` - Système de votes
- `signalements` - Signalements modérés par l’administration
- `notifications` - Notifications

La table historique `reports` est importée vers `signalements` lors de la mise à jour avec `setup.php`.

Pour tester, créez un compte depuis la page d'inscription.

## ❓ FAQ

**Q: Puis-je supprimer mes questions?**
> Oui! Seul l'auteur peut supprimer. Allez sur la question et cliquez "Supprimer".

**Q: Comment ça marche les votes?**
> Votez 👍 pour les bonnes réponses, 👎 pour les mauvaises. Cela aide à classer.

**Q: Je peux signaler une question offensante?**
> Oui! Cliquez sur "Signaler" sous la question/réponse concernée.

**Q: Mes données sont-elles sécurisées?**
> Oui! Nous utilisons des mots de passe hashés et des requêtes sécurisées.

**Q: Puis-je voir tous les utilisateurs?**
> Non, mais vous pouvez voir les profils en cliquant sur les noms d'utilisateurs.

## 🚀 Améliorations Futures

- [ ] Édition des réponses
- [ ] Système de points/réputation
- [ ] Messages directs entre utilisateurs
- [ ] Tags sur les questions
- [ ] Pagination
- [ ] Markdown dans les questions
- [ ] Mode sombre
- [ ] Préférences utilisateur

## 📞 Support

Pour toute aide, consultez la section "Aide" du forum ou contactez l'administrateur.

---

**Développé avec ❤️ - Forum v2.0 - 2024**
