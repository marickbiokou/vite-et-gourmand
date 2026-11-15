
<?php

session_start();

require_once __DIR__ . '/config/database.php';

// Récupérer et vérifier l'identifiant du menu
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    http_response_code(404);
    exit('Menu introuvable.');
}

// Rechercher le menu dans la base
$sql = "SELECT id, nom, description, prix_base,
               nombre_personnes_min, stock,
               theme, regime, conditions
        FROM menus
        WHERE id = :id AND actif = 1";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $id]);

$menu = $stmt->fetch();

if (!$menu) {
    http_response_code(404);
    exit('Menu introuvable.');
}


// Récupérer les plats associés au menu
$sqlPlats = "SELECT p.nom, p.description,
                    p.categorie, p.allergenes
             FROM plats AS p
             INNER JOIN menu_plats AS mp
                 ON p.id = mp.plat_id
             WHERE mp.menu_id = :menu_id
             ORDER BY FIELD(
                 p.categorie,
                 'entree', 'plat', 'dessert'
             ), p.nom";

$stmtPlats = $pdo->prepare($sqlPlats);
$stmtPlats->execute(['menu_id' => $id]);

$plats = $stmtPlats->fetchAll();


// Récupérer toutes les images du menu
$sqlImages = "SELECT chemin
              FROM menu_images
              WHERE menu_id = :menu_id
              ORDER BY id ASC";

$stmtImages = $pdo->prepare($sqlImages);
$stmtImages->execute(['menu_id' => $id]);

$images = $stmtImages->fetchAll();



?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>
        <?= htmlspecialchars($menu['nom']) ?> - Vite & Gourmand
    </title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<main class="conteneur">

    <a href="menus.php">← Retour aux menus</a>

    <h1><?= htmlspecialchars($menu['nom']) ?></h1>

    <?php if (!empty($images)): ?>

        <section class="galerie-menu"
                aria-label="Photographies du menu">

            <?php foreach ($images as $image): ?>

                <img
                    src="<?= htmlspecialchars($image['chemin']) ?>"
                    alt="Photographie du menu <?= htmlspecialchars($menu['nom']) ?>"
                    loading="lazy"
                >

            <?php endforeach; ?>

        </section>

    <?php endif; ?>

    <p><?= htmlspecialchars($menu['description']) ?></p>

    <p>
        <strong>Thème :</strong>
        <?= htmlspecialchars($menu['theme'] ?? 'Non précisé') ?>
    </p>

    <p>
        <strong>Régime :</strong>
        <?= htmlspecialchars($menu['regime'] ?? 'Non précisé') ?>
    </p>

    <p>
        <strong>Nombre minimum de personnes :</strong>
        <?= (int) $menu['nombre_personnes_min'] ?>
    </p>

    <p>
        <strong>Prix de base :</strong>
        <?= number_format($menu['prix_base'], 2, ',', ' ') ?> €
    </p>

    <p>
        <strong>Disponibilité :</strong>
        <?= (int) $menu['stock'] ?>
    </p>

    <h2>Conditions</h2>

    <p>
        <?= nl2br(htmlspecialchars(
            $menu['conditions'] ?? 'Aucune condition précisée.'
        )) ?>
    </p>


<h2>Composition du menu</h2>

<?php foreach ($plats as $plat): ?>

    <article class="plat-menu">

        <h3>
            <?= htmlspecialchars(
                ucfirst($plat['categorie'])
            ) ?> :
            <?= htmlspecialchars($plat['nom']) ?>
        </h3>

        <p>
            <?= htmlspecialchars(
                $plat['description'] ?? ''
            ) ?>
        </p>

        <p>
            <strong>Allergènes :</strong>
            <?= htmlspecialchars(
                $plat['allergenes'] ?? 'Non renseignés'
            ) ?>
        </p>

    </article>

<?php endforeach; ?>


<section class="commande-menu">

    <?php if (isset($_SESSION['utilisateur_id'])): ?>

        <a
            href="commander.php?menu_id=<?= (int) $menu['id'] ?>"
            class="bouton-detail"
        >
            Commander ce menu
        </a>

    <?php else: ?>

        <p>Connectez-vous pour commander ce menu.</p>

        <a href="connexion.php" class="bouton-detail">
            Se connecter
        </a>

    <?php endif; ?>

</section>

</main>

</body>
</html>
