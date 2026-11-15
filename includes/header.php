
<?php
// La session doit être démarrée par la page principale.

$estConnecte = isset($_SESSION['utilisateur_id']);
$estClient = $estConnecte
    && ($_SESSION['role'] ?? '') === 'client';
?>

<header>
    <nav class="conteneur navigation-principale">
        <a href="index.php">Vite & Gourmand</a>
        <a href="menus.php">Nos menus</a>

        <?php if ($estClient): ?>
            <a href="mes-commandes.php">Mes commandes</a>
        <?php endif; ?>

        <?php if ($estConnecte): ?>
            <a href="deconnexion.php">Déconnexion</a>
        <?php else: ?>
            <a href="connexion.php">Connexion</a>
            <a href="inscription.php">Inscription</a>
        <?php endif; ?>
    </nav>
</header>
