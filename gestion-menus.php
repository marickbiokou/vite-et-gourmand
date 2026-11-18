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

// Vérification du compte dans la base de données
$stmt = $pdo->prepare("
    SELECT id
    FROM utilisateurs
    WHERE id = :id
    AND role IN ('employe', 'admin')
    AND actif = 1
");

$stmt->execute([
    'id' => $_SESSION['utilisateur_id']
]);

if (!$stmt->fetch()) {
    http_response_code(403);
    exit('Accès refusé.');
}

// Récupération des menus
$stmt = $pdo->query("
    SELECT *
    FROM menus
    ORDER BY nom ASC
");

$menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des menus - Vite & Gourmand</title>
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

    <h1>Gestion des menus</h1>

    <p>Consultez et gérez les menus proposés par Vite & Gourmand.</p>

    <?php if (($_GET['enregistrement'] ?? '') === 'ok'): ?>

        <p class="message-succes">
            Le menu a bien été enregistré.
        </p>

    <?php endif; ?>

    <a href="modifier-menu.php" class="bouton-detail">
        + Ajouter un menu
    </a>

    <?php if (empty($menus)): ?>

        <p>Aucun menu enregistré.</p>

    <?php else: ?>

        <div class="table-responsive">

            <table class="table-commandes">

                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Thème</th>
                        <th>Régime</th>
                        <th>Prix de base</th>
                        <th>Minimum de personnes</th>
                        <th>Stock</th>
                        <th>État</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($menus as $menu): ?>

                        <tr>
                            <td>
                                <?= htmlspecialchars($menu['nom']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($menu['theme'] ?? '') ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($menu['regime'] ?? '') ?>
                            </td>

                            <td>
                                <?= number_format($menu['prix_base'], 2, ',', ' ') ?> €
                            </td>

                            <td>
                                <?= (int) $menu['nombre_personnes_min'] ?>
                            </td>

                            <td>
                                <?= (int) $menu['stock'] ?>
                            </td>

                            <td>
                                <?php if ($menu['actif']): ?>

                                    <span class="statut statut-acceptee">
                                        Actif
                                    </span>

                                <?php else: ?>

                                    <span class="statut statut-annulee">
                                        Désactivé
                                    </span>

                                <?php endif; ?>
                            </td>

                            <td>
                                <a href="modifier-menu.php?id=<?= (int) $menu['id'] ?>">
                                    Modifier
                                </a>
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