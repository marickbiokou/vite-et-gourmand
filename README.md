# Vite & Gourmand

Application web de réservation de menus pour un service traiteur situé à Bordeaux.

Projet réalisé dans le cadre de l'ECF STUDI – Titre professionnel Développeur Web et Web Mobile.

## 1. Présentation du projet

L'objectif de cette application est de permettre à l'entreprise Vite & Gourmand de présenter ses menus en ligne, de recevoir des commandes et de faciliter leur gestion.

## 2. Application en ligne

https://vgourmand2026.alwaysdata.net/

## 3. Fonctionnalités

### Visiteur

- Consultation des menus et de leurs détails.
- Filtrage dynamique des menus.
- Création d'un compte.
- Formulaire de contact.

### Client

- Connexion et réinitialisation du mot de passe.
- Gestion du profil.
- Création et suivi des commandes.
- Modification et annulation selon le statut de la commande.
- Dépôt d'un avis.

### Employé

- Gestion des commandes et de leurs statuts.
- Gestion des menus, des plats et des horaires.
- Modération des avis.

### Administrateur

- Accès aux fonctionnalités de l'employé.
- Création et désactivation des comptes employés.
- Consultation des statistiques et du chiffre d'affaires.

## 4. Technologies utilisées

- HTML5
- CSS3
- JavaScript
- PHP 8.4
- MySQL
- MongoDB
- PHPMailer
- Chart.js
- Composer
- Git et GitHub

## 5. Prérequis

- Serveur Apache avec PHP 8.4.
- Serveur MySQL.
- Serveur MongoDB.
- Extensions PHP PDO MySQL et MongoDB.
- Composer.
- Git.

## 6. Installation locale

### Récupération du projet

```bash
git clone https://github.com/marickbiokou/vite-et-gourmand.git
cd vite-et-gourmand
```

Avec XAMPP, le projet doit être accessible depuis le dossier htdocs.

### Installation des dépendances

```bash
composer install
```

### Configuration MySQL

Créer une base de données nommée `vite_et_gourmand`, puis importer le fichier SQL fourni avec le projet.

Créer le fichier `config/database.php` :

```php
<?php

$host = 'localhost';
$dbname = 'vite_et_gourmand';
$username = 'root';
$password = '';

$pdo = new PDO(
    "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
    $username,
    $password
);

$pdo->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);

$pdo->setAttribute(
    PDO::ATTR_DEFAULT_FETCH_MODE,
    PDO::FETCH_ASSOC
);
```

Les identifiants doivent correspondre à la configuration MySQL locale.

### Configuration MongoDB

Démarrer le serveur MongoDB et vérifier la configuration du fichier `config/mongodb.php`.

```php
<?php

$mongo = new MongoDB\Driver\Manager(
    'mongodb://127.0.0.1:27017'
);

$baseMongo = 'vite_et_gourmand';
```

La collection des commandes est alimentée lors de la consultation des statistiques.

### Configuration de la messagerie

Créer le fichier `config/mail.php` :

```php
<?php

return [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'adresse@gmail.com',
    'password' => 'mot_de_passe_application',
    'from_email' => 'adresse@gmail.com',
    'from_name' => 'Vite & Gourmand',
    'contact_email' => 'adresse@gmail.com'
];
```

Les identifiants SMTP sont propres à chaque environnement.

### Lancement de l'application

Démarrer Apache, MySQL et MongoDB.

Accéder à l'application depuis le navigateur :

http://localhost/vite-et-gourmand/

## 7. Sécurité

- Hachage des mots de passe.
- Requêtes SQL préparées.
- Vérification des droits utilisateurs.
- Protection CSRF des formulaires sensibles.
- Validation des données côté serveur.
- Échappement des données affichées.
- Protection des fichiers de configuration en production.
- Utilisation du protocole HTTPS en production.

Les identifiants confidentiels ne sont pas publiés dans le dépôt GitHub.

## 8. Organisation des branches

- `main` : version stable du projet.
- `develop` : intégration des développements.
- `feature/...` : développement des fonctionnalités.

La gestion du projet est réalisée avec GitHub Projects.

## 9. Documentation

Le dossier `documentation` contient :

- Le manuel d'utilisation.
- La documentation technique.
- La documentation de gestion de projet.
- La charte graphique.
- Les maquettes graphiques desktop et mobile.
- Les wireframes desktop et mobile.

## 10. Hébergement

- Application PHP : Alwaysdata.
- Base relationnelle : MySQL sur Alwaysdata.
- Base non relationnelle : MongoDB Atlas.
- Messagerie : Gmail SMTP.

## 11. Auteur

Marick Biokou

ECF STUDI – Novembre 2026
