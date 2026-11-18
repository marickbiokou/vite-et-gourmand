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

$erreur = '';

$categories = [
    'entree' => 'Entrée',
    'plat' => 'Plat principal',
    'dessert' => 'Dessert'
];

// Valeurs par défaut du formulaire
$plat = [
    'id' => 0,
    'nom' => '',
    'description' => '',
    'categorie' => 'entree',
    'allergenes' => ''
];

// Récupération du plat à modifier
if (isset($_GET['id'])) {

    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

    if (!$id || $id < 1) {
        http_response_code(400);
        exit('Identifiant invalide.');
    }

    $stmt = $pdo->prepare("
        SELECT *
        FROM plats
        WHERE id = :id
    ");

    $stmt->execute(['id' => $id]);

    $plat = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$plat) {
        http_response_code(404);
        exit('Plat introuvable.');
    }
}

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $csrfToken = $_POST['csrf_token'] ?? '';

    if (
        !is_string($csrfToken) ||
        !hash_equals($_SESSION['csrf_token'], $csrfToken)
    ) {
        http_response_code(403);
        exit('Requête non autorisée.');
    }

    $action = $_POST['action'] ?? '';
    $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

    // Ajouter ou modifier un plat
    if ($action === 'enregistrer') {

        $nom = trim((string) filter_input(INPUT_POST, 'nom'));
        $description = trim((string) filter_input(INPUT_POST, 'description'));
        $categorie = trim((string) filter_input(INPUT_POST, 'categorie'));
        $allergenes = trim((string) filter_input(INPUT_POST, 'allergenes'));

        $plat = [
            'id' => $id,
            'nom' => $nom,
            'description' => $description,
            'categorie' => $categorie,
            'allergenes' => $allergenes
        ];

        if (
            $id === false ||
            $id === null ||
            $id < 0 ||
            $nom === '' ||
            mb_strlen($nom) > 150 ||
            !isset($categories[$categorie])
        ) {

            $erreur = 'Veuillez vérifier les informations saisies.';

        } else {

            try {

                $parametres = [
                    'nom' => $nom,
                    'description' => $description,
                    'categorie' => $categorie,
                    'allergenes' => $allergenes
                ];

                if ($id > 0) {

                    $stmt = $pdo->prepare("
                        UPDATE plats
                        SET nom = :nom,
                            description = :description,
                            categorie = :categorie,
                            allergenes = :allergenes
                        WHERE id = :id
                    ");

                    $parametres['id'] = $id;

                } else {

                    $stmt = $pdo->prepare("
                        INSERT INTO plats (
                            nom,
                            description,
                            categorie,
                            allergenes
                        )
                        VALUES (
                            :nom,
                            :description,
                            :categorie,
                            :allergenes
                        )
                    ");
                }

                $stmt->execute($parametres);

                header('Location: gestion-plats.php?enregistrement=ok');
                exit;

            } catch (Throwable $e) {

                $erreur = 'Impossible d’enregistrer le plat.';
            }
        }

    // Suppression d'un plat
    } elseif ($action === 'supprimer') {

        if (!$id || $id < 1) {

            $erreur = 'Identifiant invalide.';

        } else {

            try {

                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    SELECT id
                    FROM plats
                    WHERE id = :id
                    FOR UPDATE
                ");

                $stmt->execute(['id' => $id]);

                if (!$stmt->fetch()) {
                    throw new RuntimeException('Plat introuvable.');
                }

                // Vérifier si le plat appartient à un menu
                $stmt = $pdo->prepare("
                    SELECT COUNT(*)
                    FROM menu_plats
                    WHERE plat_id = :id
                ");

                $stmt->execute(['id' => $id]);

                if ($stmt->fetchColumn() > 0) {

                    $pdo->rollBack();

                    $erreur = 'Ce plat est associé à un menu et ne peut pas être supprimé.';

                } else {

                    $stmt = $pdo->prepare("
                        DELETE FROM plats
                        WHERE id = :id
                    ");

                    $stmt->execute(['id' => $id]);

                    $pdo->commit();

                    header('Location: gestion-plats.php?suppression=ok');
                    exit;
                }

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $erreur = 'Impossible de supprimer le plat.';
            }
        }
    }
}

// Récupération des plats
$stmt = $pdo->query("
    SELECT *
    FROM plats
    ORDER BY FIELD(categorie, 'entree', 'plat', 'dessert'), nom
");

$plats = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des plats - Vite & Gourmand</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<main class="conteneur">

    <a href="<?= $_SESSION['role'] === 'admin'
        ? 'espace-admin.php'
        : 'espace-employe.php' ?>"
        class="lien-retour">
        Retour à mon espace
    </a>

    <h1>Gestion des plats</h1>

    <p>Gérez les plats et les allergènes de Vite & Gourmand.</p>

    <?php if ($erreur !== ''): ?>

        <p class="message-erreur">
            <?= htmlspecialchars($erreur) ?>
        </p>

    <?php endif; ?>

    <?php if (($_GET['enregistrement'] ?? '') === 'ok'): ?>

        <p class="message-succes">
            Le plat a bien été enregistré.
        </p>

    <?php endif; ?>

    <?php if (($_GET['suppression'] ?? '') === 'ok'): ?>

        <p class="message-succes">
            Le plat a bien été supprimé.
        </p>

    <?php endif; ?>

    <h2>
        <?= $plat['id'] > 0 ? 'Modifier un plat' : 'Ajouter un plat' ?>
    </h2>

    <form method="POST"
          action="gestion-plats.php"
          class="formulaire">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <input type="hidden" name="action" value="enregistrer">

        <input type="hidden" name="id"
               value="<?= (int) $plat['id'] ?>">

        <label for="nom">Nom du plat</label>

        <input type="text" id="nom" name="nom"
               maxlength="150"
               value="<?= htmlspecialchars($plat['nom']) ?>"
               required>

        <label for="description">Description</label>

        <textarea id="description"
                  name="description"><?= htmlspecialchars($plat['description'] ?? '') ?></textarea>

        <label for="categorie">Catégorie</label>

        <select id="categorie" name="categorie" required>

            <?php foreach ($categories as $valeur => $libelle): ?>

                <option value="<?= htmlspecialchars($valeur) ?>"
                    <?= $plat['categorie'] === $valeur ? 'selected' : '' ?>>

                    <?= htmlspecialchars($libelle) ?>

                </option>

            <?php endforeach; ?>

        </select>

        <label for="allergenes">Allergènes</label>

        <textarea id="allergenes" name="allergenes"
                  placeholder="Indiquez les allergènes du plat"><?= htmlspecialchars($plat['allergenes'] ?? '') ?></textarea>

        <button type="submit">
            Enregistrer le plat
        </button>

        <?php if ($plat['id'] > 0): ?>

            <a href="gestion-plats.php" class="lien-retour">
                Annuler la modification
            </a>

        <?php endif; ?>

    </form>

    <h2>Liste des plats</h2>

    <?php if (empty($plats)): ?>

        <p>Aucun plat enregistré.</p>

    <?php else: ?>

        <div class="table-responsive">

            <table class="table-commandes">

                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Catégorie</th>
                        <th>Description</th>
                        <th>Allergènes</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($plats as $ligne): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($ligne['nom']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($categories[$ligne['categorie']] ?? $ligne['categorie']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($ligne['description'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($ligne['allergenes'] ?? '') ?>
                            </td>

                            <td>

                                <a href="gestion-plats.php?id=<?= (int) $ligne['id'] ?>">
                                    Modifier
                                </a>

                                <form method="POST"
                                      action="gestion-plats.php"
                                      onsubmit="return confirm('Voulez-vous vraiment supprimer ce plat ?');">

                                    <input type="hidden" name="csrf_token"
                                           value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                                    <input type="hidden" name="action"
                                           value="supprimer">

                                    <input type="hidden" name="id"
                                           value="<?= (int) $ligne['id'] ?>">

                                    <button type="submit">
                                        Supprimer
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</main>

</body>
</html>