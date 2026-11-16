
<?php
session_start();
require_once __DIR__ . '/config/database.php';

if (
    !isset($_SESSION['utilisateur_id']) ||
    ($_SESSION['role'] ?? '') !== 'client'
) {
    header('Location: connexion.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$distancesLivraison = [
    'bordeaux' => 0,
    'merignac' => 10,
    'pessac' => 15,
    'libourne' => 35
];

$commandeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$commandeId || $commandeId < 1) {
    http_response_code(400);
    exit('Commande invalide.');
}

// Charger la commande du client.
$stmt = $pdo->prepare(
    "SELECT c.*, m.nom AS nom_menu, m.prix_base,
            m.nombre_personnes_min
     FROM commandes c
     INNER JOIN menus m ON m.id = c.menu_id
     WHERE c.id = :id AND c.utilisateur_id = :utilisateur_id"
);

$stmt->execute([
    'id' => $commandeId,
    'utilisateur_id' => $_SESSION['utilisateur_id']
]);

$commande = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$commande) {
    http_response_code(404);
    exit('Commande introuvable.');
}

if ($commande['statut'] !== 'en_attente') {
    http_response_code(403);
    exit('Cette commande ne peut plus être modifiée.');
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        exit('Formulaire invalide.');
    }

    $nombrePersonnes = filter_input(
        INPUT_POST,
        'nombre_personnes',
        FILTER_VALIDATE_INT
    );

    $ville = mb_strtolower(
        trim($_POST['ville_livraison'] ?? ''),
        'UTF-8'
    );

    $adresse = trim($_POST['adresse_livraison'] ?? '');
    $datePrestation = $_POST['date_prestation'] ?? '';
    $heureLivraison = $_POST['heure_livraison'] ?? '';

    $minimum = (int) $commande['nombre_personnes_min'];

    if (!$nombrePersonnes || $nombrePersonnes < $minimum) {
        $erreur = 'Nombre de personnes invalide.';
    } elseif (!array_key_exists($ville, $distancesLivraison)) {
        $erreur = 'Cette ville n’est pas desservie.';
    } elseif (
        $adresse === '' ||
        strlen($adresse) > 255 ||
        $datePrestation === '' ||
        $heureLivraison === ''
    ) {
        $erreur = 'Informations de livraison invalides.';
    }

    if ($erreur === '') {
        $dateHeure = DateTimeImmutable::createFromFormat(
            '!Y-m-d H:i',
            $datePrestation . ' ' . $heureLivraison
        );

        $erreursDate = DateTimeImmutable::getLastErrors();

        if (
            $dateHeure === false ||
            ($erreursDate !== false &&
                ($erreursDate['warning_count'] > 0 ||
                 $erreursDate['error_count'] > 0)) ||
            $dateHeure <= new DateTimeImmutable()
        ) {
            $erreur = 'Veuillez choisir une date et une heure futures et valides.';
        }
    }

    if ($erreur === '') {
        try {
            $pdo->beginTransaction();

            // Relire et verrouiller la commande avant modification.
            $stmt = $pdo->prepare(
                "SELECT c.nombre_personnes, c.statut, c.menu_id,
                        m.nombre_personnes_min, m.prix_base
                 FROM commandes c
                 INNER JOIN menus m ON m.id = c.menu_id
                 WHERE c.id = :id
                   AND c.utilisateur_id = :utilisateur_id
                 FOR UPDATE"
            );

            $stmt->execute([
                'id' => $commandeId,
                'utilisateur_id' => $_SESSION['utilisateur_id']
            ]);

            $actuelle = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$actuelle || $actuelle['statut'] !== 'en_attente') {
                throw new RuntimeException('Modification non autorisée.');
            }

            $minimum = (int) $actuelle['nombre_personnes_min'];

            $ancienStock = (int) ceil(
                (int) $actuelle['nombre_personnes'] / $minimum
            );

            $nouveauStock = (int) ceil($nombrePersonnes / $minimum);
            $difference = $nouveauStock - $ancienStock;

            // Si davantage de stock est nécessaire, le réserver.
            if ($difference > 0) {
                $stmt = $pdo->prepare(
                    "UPDATE menus
                     SET stock = stock - :quantite
                     WHERE id = :menu_id
                       AND actif = 1
                       AND stock >= :minimum_stock"
                );

                $stmt->execute([
                    'quantite' => $difference,
                    'menu_id' => $actuelle['menu_id'],
                    'minimum_stock' => $difference
                ]);

                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException('Stock insuffisant.');
                }
            }

            // Si moins de stock est nécessaire, restituer le surplus.
            if ($difference < 0) {
                $stmt = $pdo->prepare(
                    "UPDATE menus
                     SET stock = stock + :quantite
                     WHERE id = :menu_id"
                );

                $stmt->execute([
                    'quantite' => abs($difference),
                    'menu_id' => $actuelle['menu_id']
                ]);

                if ($stmt->rowCount() !== 1) {
                    throw new RuntimeException('Ajustement du stock impossible.');
                }
            }

            $prixBase = (float) $actuelle['prix_base'];
            $prixMenu = ($prixBase / $minimum) * $nombrePersonnes;

            $reduction = $nombrePersonnes >= $minimum + 5
                ? $prixMenu * 0.10
                : 0;

            $distance = $distancesLivraison[$ville];

            $fraisLivraison = $ville === 'bordeaux'
                ? 0
                : 5 + (0.59 * $distance);

            $prixTotal = round(
                $prixMenu - $reduction + $fraisLivraison,
                2
            );

            $stmt = $pdo->prepare(
                "UPDATE commandes SET
                    nombre_personnes = :nombre_personnes,
                    date_prestation = :date_prestation,
                    heure_livraison = :heure_livraison,
                    adresse_livraison = :adresse_livraison,
                    ville_livraison = :ville_livraison,
                    distance_km = :distance_km,
                    frais_livraison = :frais_livraison,
                    prix_total = :prix_total
                 WHERE id = :id AND statut = 'en_attente'"
            );

            $stmt->execute([
                'nombre_personnes' => $nombrePersonnes,
                'date_prestation' => $datePrestation,
                'heure_livraison' => $heureLivraison,
                'adresse_livraison' => $adresse,
                'ville_livraison' => $ville,
                'distance_km' => $distance,
                'frais_livraison' => $fraisLivraison,
                'prix_total' => $prixTotal,
                'id' => $commandeId
            ]);

            $pdo->commit();

            header('Location: mes-commandes.php?modification=ok');
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log($e->getMessage());
            $erreur = 'Modification impossible. Vérifiez les informations et le stock disponible.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier ma commande - Vite & Gourmand</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php require_once __DIR__ . '/includes/header.php'; ?>

<main class="conteneur">
    <h1>Modifier la commande n°<?= (int) $commandeId ?></h1>

    <p>
        <strong>Menu :</strong>
        <?= htmlspecialchars($commande['nom_menu'], ENT_QUOTES, 'UTF-8') ?>
        (non modifiable)
    </p>

    <?php if ($erreur !== ''): ?>
        <p class="message-erreur">
            <?= htmlspecialchars($erreur, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>

    <form method="POST" class="formulaire">
        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <label for="nombre_personnes">Nombre de personnes</label>
        <input type="number" id="nombre_personnes"
               name="nombre_personnes"
               min="<?= (int) $commande['nombre_personnes_min'] ?>"
               value="<?= htmlspecialchars((string) ($_POST['nombre_personnes'] ?? $commande['nombre_personnes']), ENT_QUOTES, 'UTF-8') ?>"
               required>

        <label for="date_prestation">Date de prestation</label>
        <input type="date" id="date_prestation"
               name="date_prestation"
               min="<?= date('Y-m-d') ?>"
               value="<?= htmlspecialchars($_POST['date_prestation'] ?? $commande['date_prestation'], ENT_QUOTES, 'UTF-8') ?>"
               required>

        <label for="heure_livraison">Heure de livraison</label>
        <input type="time" id="heure_livraison"
               name="heure_livraison"
               value="<?= htmlspecialchars(substr($_POST['heure_livraison'] ?? $commande['heure_livraison'] ?? '', 0, 5), ENT_QUOTES, 'UTF-8') ?>"
               required>

        <label for="adresse_livraison">Adresse de livraison</label>
        <input type="text" id="adresse_livraison"
               name="adresse_livraison"
               maxlength="255"
               value="<?= htmlspecialchars($_POST['adresse_livraison'] ?? $commande['adresse_livraison'], ENT_QUOTES, 'UTF-8') ?>"
               required>

        <label for="ville_livraison">Ville de livraison</label>
        <select id="ville_livraison" name="ville_livraison" required>
            <?php foreach ($distancesLivraison as $ville => $km): ?>
                <option value="<?= htmlspecialchars($ville, ENT_QUOTES, 'UTF-8') ?>"
                    <?= ($ville === ($_POST['ville_livraison'] ?? $commande['ville_livraison'])) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(ucfirst($ville), ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <p>Le prix total sera recalculé lors de l'enregistrement.</p>

        <button type="submit" class="bouton-detail">
            Enregistrer les modifications
        </button>
    </form>

    <p><a href="mes-commandes.php">Retour à mes commandes</a></p>
</main>
</body>
</html>
