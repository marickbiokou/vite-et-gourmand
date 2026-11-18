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

// Vérification du compte actif
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

// Récupération des horaires
$stmt = $pdo->query("
    SELECT *
    FROM horaires
    ORDER BY FIELD(
        LOWER(jour),
        'lundi',
        'mardi',
        'mercredi',
        'jeudi',
        'vendredi',
        'samedi',
        'dimanche'
    )
");

$horaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

    $donnees = $_POST['horaires'] ?? [];

    if (!is_array($donnees)) {
        http_response_code(400);
        exit('Données invalides.');
    }

    $nouveauxHoraires = [];

    // Vérification des horaires saisis
    foreach ($horaires as $horaire) {

        $id = (int) $horaire['id'];

        $ligne = $donnees[$id] ?? [];

        if (!is_array($ligne)) {
            $erreur = 'Données invalides.';
            break;
        }

        $ferme = isset($ligne['ferme']) ? 1 : 0;

        $ouverture = $ligne['ouverture'] ?? '';
        $fermeture = $ligne['fermeture'] ?? '';

        if ($ferme === 1) {

            $ouverture = null;
            $fermeture = null;

        } else {

            if (
                !is_string($ouverture) ||
                !is_string($fermeture) ||
                !preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $ouverture) ||
                !preg_match('/^([01][0-9]|2[0-3]):[0-5][0-9]$/', $fermeture) ||
                $ouverture >= $fermeture
            ) {

                $erreur = 'Veuillez vérifier les horaires du ' .
                    $horaire['jour'] . '.';

                break;
            }
        }

        $nouveauxHoraires[] = [
            'id' => $id,
            'heure_ouverture' => $ouverture,
            'heure_fermeture' => $fermeture,
            'ferme' => $ferme
        ];
    }

    // Enregistrement des horaires
    if ($erreur === '') {

        try {

            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE horaires
                SET heure_ouverture = :heure_ouverture,
                    heure_fermeture = :heure_fermeture,
                    ferme = :ferme
                WHERE id = :id
            ");

            foreach ($nouveauxHoraires as $horaire) {

                $stmt->execute($horaire);
            }

            $pdo->commit();

            header('Location: gestion-horaires.php?enregistrement=ok');
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $erreur = 'Impossible d’enregistrer les horaires.';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des horaires - Vite & Gourmand</title>
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

    <h1>Gestion des horaires</h1>

    <p>Modifiez les horaires d’ouverture de Vite & Gourmand.</p>

    <?php if ($erreur !== ''): ?>

        <p class="message-erreur">
            <?= htmlspecialchars($erreur) ?>
        </p>

    <?php endif; ?>

    <?php if (($_GET['enregistrement'] ?? '') === 'ok'): ?>

        <p class="message-succes">
            Les horaires ont bien été enregistrés.
        </p>

    <?php endif; ?>

    <?php if (empty($horaires)): ?>

        <p>Aucun horaire enregistré.</p>

    <?php else: ?>

        <form method="POST" action="gestion-horaires.php">

            <input type="hidden"
                   name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="table-responsive">

                <table class="table-commandes">

                    <thead>
                        <tr>
                            <th>Jour</th>
                            <th>Heure d'ouverture</th>
                            <th>Heure de fermeture</th>
                            <th>Fermé</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($horaires as $horaire): ?>

                            <?php $id = (int) $horaire['id']; ?>

                            <tr>

                                <td>
                                    <?= htmlspecialchars(ucfirst($horaire['jour'])) ?>
                                </td>

                                <td>
                                    <input type="time"
                                           name="horaires[<?= $id ?>][ouverture]"
                                           aria-label="Ouverture du <?= htmlspecialchars($horaire['jour']) ?>"
                                           value="<?= htmlspecialchars(substr($horaire['heure_ouverture'] ?? '', 0, 5)) ?>">
                                </td>

                                <td>
                                    <input type="time"
                                           name="horaires[<?= $id ?>][fermeture]"
                                           aria-label="Fermeture du <?= htmlspecialchars($horaire['jour']) ?>"
                                           value="<?= htmlspecialchars(substr($horaire['heure_fermeture'] ?? '', 0, 5)) ?>">
                                </td>

                                <td>
                                    <input type="checkbox"
                                           name="horaires[<?= $id ?>][ferme]"
                                           aria-label="<?= htmlspecialchars($horaire['jour']) ?> fermé"
                                           value="1"
                                           <?= $horaire['ferme'] ? 'checked' : '' ?>>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <button type="submit" class="bouton-detail">
                Enregistrer les horaires
            </button>

        </form>

    <?php endif; ?>

</main>

</body>
</html>