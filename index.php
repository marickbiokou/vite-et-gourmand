<?php
session_start();

$nomEntreprise = "Vite & Gourmand";
$message = "Découvrez nos menus et commandez pour vos événements.";

$estConnecte = isset($_SESSION['utilisateur_id']);
$estClient = $estConnecte
    && ($_SESSION['role'] ?? '') === 'client';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($nomEntreprise) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

    <?php require_once __DIR__ . '/includes/header.php'; ?>

    <main class="conteneur">

        <section class="accueil-hero">

            <h1>Vite & Gourmand</h1>

            <p>
                Des menus savoureux pour tous vos événements.
                Découvrez notre sélection et commandez
                facilement votre prestation traiteur.
            </p>

            <a href="menus.php" class="bouton-detail">
                Découvrir nos menus
            </a>

        </section>
<?php require_once __DIR__ . '/avis-accueil.php'; ?>
    </main>

    <?php require_once __DIR__ . '/includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>

</body>
</html>