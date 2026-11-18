<?php

require_once __DIR__ . '/includes/espace-commun.php';

// Vérification des droits administrateur

$admin = compteActif(['admin']);

debutPage('Espace administrateur');

?>

<p>
    Bienvenue dans votre espace administrateur.
</p>

<div class="grille-menus">

    <article class="carte-menu">

        <h2>Gestion des employés</h2>

        <p>
            Créer et gérer les comptes des employés.
        </p>

        <a href="gestion-employes.php" class="bouton-detail">
            Gérer les employés
        </a>

    </article>

    <article class="carte-menu">

        <h2>Gestion des commandes</h2>

        <p>
            Consulter et gérer les commandes des clients.
        </p>

        <a href="espace-employe.php" class="bouton-detail">
            Gérer les commandes
        </a>

    </article>

    <article class="carte-menu">

        <h2>Gestion des menus</h2>

        <p>
            Modifier les menus et gérer les stocks.
        </p>

        <a href="gestion-menus.php" class="bouton-detail">
            Gérer les menus
        </a>

    </article>

    <article class="carte-menu">

        <h2>Gestion des plats</h2>

        <p>
            Ajouter, modifier ou supprimer des plats.
        </p>

        <a href="gestion-plats.php" class="bouton-detail">
            Gérer les plats
        </a>

    </article>

    <article class="carte-menu">

        <h2>Gestion des horaires</h2>

        <p>
            Modifier les horaires d'ouverture.
        </p>

        <a href="gestion-horaires.php" class="bouton-detail">
            Gérer les horaires
        </a>

    </article>

    <article class="carte-menu">

        <h2>Gestion des avis</h2>

        <p>
            Consulter et modérer les avis des clients.
        </p>

        <a href="gestion-avis.php" class="bouton-detail">
            Gérer les avis
        </a>

    </article>

    <article class="carte-menu">

        <h2>Statistiques</h2>

        <p>
            Consulter le nombre de commandes et
            le chiffre d'affaires par menu.
        </p>

        <a href="statistiques.php" class="bouton-detail">
            Voir les statistiques
        </a>

    </article>

</div>

<?php

finPage();