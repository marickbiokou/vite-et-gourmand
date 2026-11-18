<?php

$estConnecte = isset($_SESSION['utilisateur_id']);

$estClient = $estConnecte
    && ($_SESSION['role'] ?? '') === 'client';

$estEmploye = $estConnecte
    && ($_SESSION['role'] ?? '') === 'employe';

$estAdmin = $estConnecte
    && ($_SESSION['role'] ?? '') === 'admin';

?>

<header>

    <nav class="conteneur navigation-principale">

        <a href="index.php">Vite & Gourmand</a>

        <a href="menus.php">Nos menus</a> <a href="contact.php">Contact</a>

        <?php if ($estClient): ?>

            <a href="mon-profil.php">Mon profil</a>

            <a href="mes-commandes.php">Mes commandes</a>

        <?php endif; ?>

        <?php if ($estEmploye): ?>

            <a href="espace-employe.php">Espace employé</a>

        <?php endif; ?>

        <?php if ($estAdmin): ?>

            <a href="espace-admin.php">Espace administrateur</a>

        <?php endif; ?>

        <?php if ($estConnecte): ?>

            <a href="deconnexion.php">Déconnexion</a>

        <?php else: ?>

            <a href="connexion.php">Connexion</a>

            <a href="inscription.php">Inscription</a>

        <?php endif; ?>

    </nav>

</header>