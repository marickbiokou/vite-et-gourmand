
<?php

session_start();

require_once __DIR__ . '/config/database.php';

$sql = 
$sql = "SELECT m.id, m.nom, m.description,
               m.prix_base, m.nombre_personnes_min,
               m.stock, m.theme, m.regime,
               (
                   SELECT mi.chemin
                   FROM menu_images AS mi
                   WHERE mi.menu_id = m.id
                   ORDER BY mi.id ASC
                   LIMIT 1
               ) AS image
        FROM menus AS m
        WHERE m.actif = 1
        ORDER BY m.id DESC";


$stmt = $pdo->query($sql);
$menus = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Nos menus - Vite & Gourmand</title>

    <link rel="stylesheet" href="assets/css/style.css">

</head>

    
<body>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<main class="conteneur">



    <h1>Découvrez nos menus</h1>

    <section class="filtres" aria-label="Filtres des menus">

        <h2>Filtrer les menus</h2>

        <label for="prix-min">Prix minimum (€)</label>
        <input type="number" id="prix-min" min="0">

        <label for="prix-max">Prix maximum (€)</label>
        <input type="number" id="prix-max" min="0">

        <label for="theme">Thème</label>
        <select id="theme">
            <option value="">Tous les thèmes</option>
            <option value="Traditionnel">Traditionnel</option>
            <option value="Nature">Nature</option>
            <option value="Festif">Festif</option>
        </select>

        <label for="regime">Régime alimentaire</label>
        <select id="regime">
            <option value="">Tous les régimes</option>
            <option value="classique">Classique</option>
            <option value="vegetarien">Végétarien</option>
        </select>

        <label for="personnes">Nombre de personnes</label>
        <input type="number" id="personnes" min="1">

    </section>


    <section class="grille-menus">

        <?php foreach ($menus as $menu): ?>

            
            <article
                class="carte-menu"
                data-prix="<?= (float) $menu['prix_base'] ?>"
                data-theme="<?= htmlspecialchars($menu['theme'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                data-regime="<?= htmlspecialchars($menu['regime'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                data-personnes="<?= (int) $menu['nombre_personnes_min'] ?>"
            >

                <?php if (!empty($menu['image'])): ?>

                    <img
                        src="<?= htmlspecialchars($menu['image']) ?>"
                        alt="<?= htmlspecialchars($menu['nom']) ?>"
                        class="image-menu"
                    >

                <?php endif; ?>


                <h2>
                    <?= htmlspecialchars($menu['nom']) ?>
                </h2>

                <p>
                    <?= htmlspecialchars($menu['description']) ?>
                </p>

                <p>
                    À partir de
                    <?= (int) $menu['nombre_personnes_min'] ?>
                    personnes
                </p>

                <p class="prix">
                    <?= number_format(
                        $menu['prix_base'], 2, ',', ' '
                    ) ?> €
                </p>


                <a href="menu-detail.php?id=<?= (int) $menu['id'] ?>"
                class="bouton-detail">
                    Voir le détail
                </a>


            </article>

        <?php endforeach; ?>

    </section>

</main>

<script src="assets/js/filtres.js"></script>

</body>

</html>
