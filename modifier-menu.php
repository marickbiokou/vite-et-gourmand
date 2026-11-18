<?php
session_start();

require_once __DIR__ . '/config/database.php';

// Vérification des droits d'accès
if (
    !isset($_SESSION['utilisateur_id']) ||
    !in_array($_SESSION['role'] ?? '', ['employe', 'admin'], true)
) {
    http_response_code(403);
    exit('Accès refusé.');
}

$stmt = $pdo->prepare("
    SELECT id
    FROM utilisateurs
    WHERE id = :id
    AND role = :role
    AND actif = 1
");

$stmt->execute([
    'id' => $_SESSION['utilisateur_id'],
    'role' => $_SESSION['role']
]);

if (!$stmt->fetch()) {
    http_response_code(403);
    exit('Accès refusé.');
}

// Protection CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Identification du menu
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (isset($_GET['id']) && (!$id || $id < 1)) {
    http_response_code(400);
    exit('Identifiant invalide.');
}

$modification = $id !== null;

$erreur = '';
$minimumVerrouille = false;

// Valeurs par défaut pour un nouveau menu
$menu = [
    'nom' => '',
    'description' => '',
    'prix_base' => '',
    'nombre_personnes_min' => 1,
    'stock' => 0,
    'theme' => '',
    'regime' => 'classique',
    'conditions' => '',
    'actif' => 1
];

$platsSelectionnes = [];
$imageExistante = null;

// Récupération du menu existant
if ($modification) {

    $stmt = $pdo->prepare("
        SELECT *
        FROM menus
        WHERE id = :id
    ");

    $stmt->execute(['id' => $id]);

    $menu = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$menu) {
        http_response_code(404);
        exit('Menu introuvable.');
    }

    // Récupération de la première image du menu
    $stmt = $pdo->prepare("SELECT chemin FROM menu_images WHERE menu_id = :id ORDER BY id ASC LIMIT 1");
    $stmt->execute(['id' => $id]);
    $imageExistante = $stmt->fetchColumn() ?: null;

    // Vérification des commandes associées
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM commandes
        WHERE menu_id = :id
    ");

    $stmt->execute(['id' => $id]);

    $minimumVerrouille = $stmt->fetchColumn() > 0;

    // Récupération des plats associés
    $stmt = $pdo->prepare("
        SELECT plat_id
        FROM menu_plats
        WHERE menu_id = :id
    ");

    $stmt->execute(['id' => $id]);

    $platsSelectionnes = array_map(
        'intval',
        $stmt->fetchAll(PDO::FETCH_COLUMN)
    );
}

// Récupération des plats disponibles
$stmt = $pdo->query("
    SELECT id, nom, categorie
    FROM plats
    ORDER BY FIELD(categorie, 'entree', 'plat', 'dessert'), nom
");

$plats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrfToken) ||
        !hash_equals($_SESSION['csrf_token'], $csrfToken)
    ) {
        http_response_code(403);
        exit('Requête non autorisée.');
    }

    // Récupération des données
    $nom = trim((string) filter_input(INPUT_POST, 'nom'));
    $description = trim((string) filter_input(INPUT_POST, 'description'));
    $theme = trim((string) filter_input(INPUT_POST, 'theme'));
    $regime = trim((string) filter_input(INPUT_POST, 'regime'));
    $conditions = trim((string) filter_input(INPUT_POST, 'conditions'));

    $prix = filter_input(INPUT_POST, 'prix_base', FILTER_VALIDATE_FLOAT);
    $minimum = filter_input(INPUT_POST, 'nombre_personnes_min', FILTER_VALIDATE_INT);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);

    $actif = isset($_POST['actif']) ? 1 : 0;

    $platsEnvoyes = $_POST['plats'] ?? [];

    // Conservation du minimum si des commandes existent
    if ($minimumVerrouille) {
        $minimum = (int) $menu['nombre_personnes_min'];
    }

    // Vérification des plats sélectionnés
    $platsAutorises = array_map(
        'intval',
        array_column($plats, 'id')
    );

    $selectionValide = is_array($platsEnvoyes);
    $platsSelectionnes = [];

    if ($selectionValide) {

        foreach ($platsEnvoyes as $platId) {

            $platId = filter_var($platId, FILTER_VALIDATE_INT);

            if (!$platId || !in_array($platId, $platsAutorises, true)) {
                $selectionValide = false;
                break;
            }

            $platsSelectionnes[] = $platId;
        }
    }

    $platsSelectionnes = array_unique($platsSelectionnes);

    // Conservation des valeurs saisies
    $menu = [
        'nom' => $nom,
        'description' => $description,
        'prix_base' => $prix,
        'nombre_personnes_min' => $minimum,
        'stock' => $stock,
        'theme' => $theme,
        'regime' => $regime,
        'conditions' => $conditions,
        'actif' => $actif
    ];

    // Validation des données
    if (
        $nom === '' ||
        mb_strlen($nom) > 150 ||
        $description === '' ||
        $prix === false ||
        $prix === null ||
        $prix <= 0 ||
        $prix > 99999999.99 ||
        $minimum === false ||
        $minimum === null ||
        $minimum < 1 ||
        $minimum > 10000 ||
        $stock === false ||
        $stock === null ||
        $stock < 0 ||
        $stock > 4294967295 ||
        mb_strlen($theme) > 100 ||
        !in_array($regime, ['classique', 'vegetarien', 'vegan'], true) ||
        !$selectionValide
    ) {

        $erreur = 'Veuillez vérifier les informations saisies.';

    } else {

        $fichierCree = null;
        $nouvelleImage = null;

        try {

            // Vérification et transfert de l'image facultative
            $fichier = $_FILES['image'] ?? null;

            if ($fichier !== null && $fichier['error'] !== UPLOAD_ERR_NO_FILE) {
                if ($fichier['error'] !== UPLOAD_ERR_OK ||
                    $fichier['size'] > 5 * 1024 * 1024 ||
                    !is_uploaded_file($fichier['tmp_name'])) {
                    throw new InvalidArgumentException('Image invalide ou trop volumineuse (5 Mo maximum).');
                }

                $types = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp'
                ];

                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($fichier['tmp_name']);
                $dimensions = @getimagesize($fichier['tmp_name']);

                if (!isset($types[$mime]) || !$dimensions ||
                    $dimensions['mime'] !== $mime) {
                    throw new InvalidArgumentException('Formats autorisés : JPEG, PNG et WebP.');
                }

                $nomImage = bin2hex(random_bytes(16)) . '.' . $types[$mime];
                $nouvelleImage = 'assets/images/uploads/' . $nomImage;
                $destination = __DIR__ . '/' . $nouvelleImage;

                if (!move_uploaded_file($fichier['tmp_name'], $destination)) {
                    throw new RuntimeException('Transfert impossible.');
                }
                $fichierCree = $destination;
            }

            $pdo->beginTransaction();

            $parametres = [
                'nom' => $nom,
                'description' => $description,
                'prix_base' => $prix,
                'nombre_personnes_min' => $minimum,
                'stock' => $stock,
                'theme' => $theme,
                'regime' => $regime,
                'conditions' => $conditions,
                'actif' => $actif
            ];

            if ($modification) {

                // Verrouillage du menu pendant la modification
                $stmt = $pdo->prepare("
                    SELECT nombre_personnes_min
                    FROM menus
                    WHERE id = :id
                    FOR UPDATE
                ");

                $stmt->execute(['id' => $id]);
                $menuActuel = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$menuActuel) {
                    throw new RuntimeException('Menu introuvable.');
                }

                // Nouvelle vérification des commandes
                $stmt = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM commandes
                    WHERE menu_id = :id
                ");

                $stmt->execute(['id' => $id]);

                if (
                    $stmt->fetchColumn() > 0 &&
                    $minimum !== (int) $menuActuel['nombre_personnes_min']
                ) {
                    throw new RuntimeException('Minimum non modifiable.');
                }

                $parametres['id'] = $id;

                $stmt = $pdo->prepare("
                    UPDATE menus
                    SET nom = :nom,
                        description = :description,
                        prix_base = :prix_base,
                        nombre_personnes_min = :nombre_personnes_min,
                        stock = :stock,
                        theme = :theme,
                        regime = :regime,
                        conditions = :conditions,
                        actif = :actif
                    WHERE id = :id
                ");

                $stmt->execute($parametres);

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO menus (
                        nom,
                        description,
                        prix_base,
                        nombre_personnes_min,
                        stock,
                        theme,
                        regime,
                        conditions,
                        actif
                    )
                    VALUES (
                        :nom,
                        :description,
                        :prix_base,
                        :nombre_personnes_min,
                        :stock,
                        :theme,
                        :regime,
                        :conditions,
                        :actif
                    )
                ");

                $stmt->execute($parametres);

                $id = (int) $pdo->lastInsertId();
            }

            // Ajout ou remplacement de la première image (galerie conservée)
            if ($nouvelleImage !== null) {
                $stmt = $pdo->prepare("SELECT id FROM menu_images WHERE menu_id = :id ORDER BY id ASC LIMIT 1 FOR UPDATE");
                $stmt->execute(['id' => $id]);
                $imageId = $stmt->fetchColumn();

                if ($imageId !== false) {
                    $stmt = $pdo->prepare("UPDATE menu_images SET chemin = :chemin WHERE id = :id AND menu_id = :menu_id");
                    $stmt->execute(['chemin' => $nouvelleImage, 'id' => $imageId, 'menu_id' => $id]);
                } else {
                    $stmt = $pdo->prepare("INSERT INTO menu_images (menu_id, chemin) VALUES (:menu_id, :chemin)");
                    $stmt->execute(['menu_id' => $id, 'chemin' => $nouvelleImage]);
                }
            }

            // Actualisation des plats associés
            $stmt = $pdo->prepare("
                DELETE FROM menu_plats
                WHERE menu_id = :menu_id
            ");

            $stmt->execute([
                'menu_id' => $id
            ]);

            $stmt = $pdo->prepare("
                INSERT INTO menu_plats (menu_id, plat_id)
                VALUES (:menu_id, :plat_id)
            ");

            foreach ($platsSelectionnes as $platId) {

                $stmt->execute([
                    'menu_id' => $id,
                    'plat_id' => $platId
                ]);
            }

            $pdo->commit();

            header('Location: gestion-menus.php?enregistrement=ok');
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($fichierCree !== null) {
                @unlink($fichierCree);
            }

            $erreur = $e instanceof InvalidArgumentException
                ? $e->getMessage()
                : 'Impossible d’enregistrer le menu.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= $modification ? 'Modifier un menu' : 'Ajouter un menu' ?>
        - Vite & Gourmand
    </title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<main class="conteneur">

    <a href="gestion-menus.php" class="lien-retour">
        Retour à la gestion des menus
    </a>

    <h1>
        <?= $modification ? 'Modifier un menu' : 'Ajouter un menu' ?>
    </h1>

    <?php if ($erreur !== ''): ?>

        <p class="message-erreur">
            <?= htmlspecialchars($erreur) ?>
        </p>

    <?php endif; ?>

    <form method="POST" class="formulaire" enctype="multipart/form-data">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <label for="nom">Nom du menu</label>

        <input type="text" id="nom" name="nom"
               maxlength="150"
               value="<?= htmlspecialchars($menu['nom']) ?>"
               required>

        <label for="description">Description</label>

        <textarea id="description" name="description"
                  required><?= htmlspecialchars($menu['description']) ?></textarea>

        <label for="prix_base">Prix de base (€)</label>

        <input type="number" id="prix_base" name="prix_base"
               min="0.01" max="99999999.99" step="0.01"
               value="<?= htmlspecialchars((string) $menu['prix_base']) ?>"
               required>

        <label for="nombre_personnes_min">
            Nombre minimum de personnes
        </label>

        <input type="number" id="nombre_personnes_min"
               name="nombre_personnes_min"
               min="1" max="10000"
               value="<?= htmlspecialchars((string) $menu['nombre_personnes_min']) ?>"
               <?= $minimumVerrouille ? 'readonly' : '' ?>
               required>

        <?php if ($minimumVerrouille): ?>

            <small>
                Le nombre minimum de personnes est verrouillé
                car des commandes sont associées à ce menu.
            </small>

        <?php endif; ?>

        <label for="stock">Stock disponible</label>

        <input type="number" id="stock" name="stock"
               min="0" max="4294967295"
               value="<?= htmlspecialchars((string) $menu['stock']) ?>"
               required>

        <label for="theme">Thème</label>

        <input type="text" id="theme" name="theme"
               maxlength="100"
               value="<?= htmlspecialchars($menu['theme'] ?? '') ?>">

        <label for="regime">Régime alimentaire</label>

        <select id="regime" name="regime" required>

            <option value="classique"
                <?= $menu['regime'] === 'classique' ? 'selected' : '' ?>>
                Classique
            </option>

            <option value="vegetarien"
                <?= $menu['regime'] === 'vegetarien' ? 'selected' : '' ?>>
                Végétarien
            </option>

            <option value="vegan"
                <?= $menu['regime'] === 'vegan' ? 'selected' : '' ?>>
                Vegan
            </option>

        </select>

        <label for="conditions">Conditions du menu</label>

        <textarea id="conditions"
                  name="conditions"><?= htmlspecialchars($menu['conditions'] ?? '') ?></textarea>

        <?php if (!empty($imageExistante)): ?>
            <img class="image-menu" src="<?= htmlspecialchars($imageExistante) ?>"
                 alt="Image actuelle du menu">
        <?php endif; ?>

        <label for="image">Image principale du menu</label>
        <input type="file" id="image" name="image"
               accept="image/jpeg,image/png,image/webp">
        <small>JPEG, PNG ou WebP, 5 Mo maximum. Facultatif.</small>

        <fieldset>

            <legend>Plats associés au menu</legend>

            <?php foreach ($plats as $plat): ?>

                <p>
                    <label>
                        <input type="checkbox" name="plats[]"
                               value="<?= (int) $plat['id'] ?>"
                               <?= in_array((int) $plat['id'], $platsSelectionnes, true)
                                   ? 'checked' : '' ?>>

                        <?= htmlspecialchars($plat['nom']) ?>
                        (<?= htmlspecialchars($plat['categorie']) ?>)
                    </label>
                </p>

            <?php endforeach; ?>

        </fieldset>

        <label>
            <input type="checkbox" name="actif" value="1"
                   <?= $menu['actif'] ? 'checked' : '' ?>>

            Menu actif
        </label>

        <button type="submit">
            Enregistrer le menu
        </button>

    </form>

</main>

</body>
</html>