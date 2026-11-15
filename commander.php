
<?php

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


$distancesLivraison = [
    'bordeaux' => 0,
    'merignac' => 10,
    'pessac' => 15,
    'libourne' => 35
];


require_once __DIR__ . '/config/database.php';

// Vérifier que le client est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: connexion.php');
    exit;
}

// Vérifier que le compte est actif et appartient à un client
$sql = "SELECT id, nom, prenom, email, telephone, adresse
        FROM utilisateurs
        WHERE id = :id AND actif = 1 AND role = 'client'";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $_SESSION['utilisateur_id']]);

$client = $stmt->fetch();

if (!$client) {
    http_response_code(403);
    exit('Accès refusé.');
}

// Récupérer le menu sélectionné
$menuId = filter_input(INPUT_GET, 'menu_id', FILTER_VALIDATE_INT);

if (!$menuId || $menuId < 1) {
    http_response_code(404);
    exit('Menu introuvable.');
}

$sql = "SELECT id, nom, prix_base, nombre_personnes_min, stock
        FROM menus
        WHERE id = :id AND actif = 1 AND stock > 0";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $menuId]);

$menu = $stmt->fetch();

if (!$menu) {
    http_response_code(404);
    exit('Menu indisponible.');
}

$erreur = '';

$stockNecessaire = 0;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Vérifier le jeton CSRF
    if (
        !isset($_POST['csrf_token']) ||
        !hash_equals(
            $_SESSION['csrf_token'],
            $_POST['csrf_token']
        )
    ) {
        http_response_code(403);
        exit('Formulaire invalide.');
    }

    // Récupérer les informations envoyées
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

    // Vérifications de base
    if (
        $nombrePersonnes === false ||
        $nombrePersonnes === null ||
        $nombrePersonnes < (int) $menu['nombre_personnes_min']
    ) {
        $erreur = 'Nombre de personnes invalide.';
    } elseif (!array_key_exists($ville, $distancesLivraison)) {
        $erreur = 'Cette ville n’est pas desservie.';
    } elseif (
        $adresse === '' ||
        $datePrestation === '' ||
        $heureLivraison === ''
    ) {
        $erreur = 'Veuillez remplir tous les champs obligatoires.';
    }


    // Vérifier la date et l'heure de livraison
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
            $erreur = 'Veuillez choisir une date et une heure de livraison futures et valides.';
        }
    }


    // Calculer le nombre de menus nécessaires
    if ($erreur === '') {
        $stockNecessaire = (int) ceil(
            $nombrePersonnes / $menu['nombre_personnes_min']
        );
    }


    // Vérifier que le stock est suffisant
    if ($erreur === '' && $stockNecessaire > (int) $menu['stock']) {
        $erreur = 'Stock insuffisant pour cette commande.';
    }



    // Calculer le prix uniquement si les données sont valides
    if ($erreur === '') {
        $minimum = (int) $menu['nombre_personnes_min'];
        $prixBase = (float) $menu['prix_base'];

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


        // Enregistrer la commande si toutes les données sont valides
        if ($erreur === '') {
            try {
                $pdo->beginTransaction();


                // Réserver le stock nécessaire à la commande
                $sqlStock = "UPDATE menus
                             SET stock = stock - :quantite
                             WHERE id = :menu_id
                             AND actif = 1
                             AND stock >= :stock_minimum";

                $stmtStock = $pdo->prepare($sqlStock);

                $stmtStock->execute([
                    'quantite' => $stockNecessaire,
                    'menu_id' => $menu['id'],
                    'stock_minimum' => $stockNecessaire
                ]);

                // Vérifier que le stock a bien été réservé
                if ($stmtStock->rowCount() !== 1) {
                    throw new RuntimeException(
                        'Stock insuffisant pour cette commande.'
                    );
                }


                $sql = "INSERT INTO commandes (
                            utilisateur_id,
                            menu_id,
                            nombre_personnes,
                            date_prestation,
                            heure_livraison,
                            adresse_livraison,
                            ville_livraison,
                            distance_km,
                            frais_livraison,
                            prix_total,
                            statut
                        ) VALUES (
                            :utilisateur_id,
                            :menu_id,
                            :nombre_personnes,
                            :date_prestation,
                            :heure_livraison,
                            :adresse_livraison,
                            :ville_livraison,
                            :distance_km,
                            :frais_livraison,
                            :prix_total,
                            'en_attente'
                        )";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    'utilisateur_id' => $client['id'],
                    'menu_id' => $menu['id'],
                    'nombre_personnes' => $nombrePersonnes,
                    'date_prestation' => $datePrestation,
                    'heure_livraison' => $heureLivraison,
                    'adresse_livraison' => $adresse,
                    'ville_livraison' => $ville,
                    'distance_km' => $distance,
                    'frais_livraison' => $fraisLivraison,
                    'prix_total' => $prixTotal
                ]);

                $commandeId = $pdo->lastInsertId();


                // Enregistrer le premier statut de la commande
                $sqlHistorique = "INSERT INTO historique_statuts (
                                    commande_id,
                                    statut,
                                    date_modification
                                ) VALUES (
                                    :commande_id,
                                    :statut,
                                    NOW()
                                )";

                $stmtHistorique = $pdo->prepare($sqlHistorique);

                $stmtHistorique->execute([
                    'commande_id' => $commandeId,
                    'statut' => 'en_attente'
                ]);

                // Valider les deux insertions
                $pdo->commit();


                // Confirmation temporaire pour notre test
                header('Location: commander.php?menu_id=' . $menuId . '&commande_creee=' . $commandeId);
                exit;

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log($e->getMessage());
                $erreur = "Une erreur est survenue lors de l'enregistrement de la commande.";
            }
        }

    }

}





?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Commander - Vite & Gourmand</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<main class="conteneur">
    
    <a href="menu-detail.php?id=<?= (int) $menu['id'] ?>"
    class="lien-retour">
        ← Retour au menu
    </a>


    <h1>Commander : <?= htmlspecialchars($menu['nom']) ?></h1>


    <?php if ($erreur !== ''): ?>
        <p class="message-erreur">
            <?= htmlspecialchars($erreur, ENT_QUOTES, 'UTF-8') ?>
        </p>
    <?php endif; ?>


    <form action="commander.php?menu_id=<?= (int) $menu['id'] ?>"
          method="POST" class="formulaire">

        <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars(
                $_SESSION['csrf_token'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>"
        >

        <label for="nom">Nom et prénom</label>
        <input type="text" id="nom"
               value="<?= htmlspecialchars(
                   $client['prenom'] . ' ' . $client['nom'],
                   ENT_QUOTES, 'UTF-8'
               ) ?>" readonly>

        <label for="email">Email</label>
        <input type="email" id="email"
               value="<?= htmlspecialchars(
                   $client['email'], ENT_QUOTES, 'UTF-8'
               ) ?>" readonly>

        <label for="telephone">Téléphone</label>
        <input type="tel" id="telephone"
               value="<?= htmlspecialchars(
                   $client['telephone'] ?? '',
                   ENT_QUOTES, 'UTF-8'
               ) ?>" readonly>

        <label for="adresse_livraison">Adresse de livraison</label>
        <input type="text" id="adresse_livraison"
               name="adresse_livraison"
               value="<?= htmlspecialchars(
                   $client['adresse'] ?? '',
                   ENT_QUOTES, 'UTF-8'
               ) ?>" required>

        <label for="ville_livraison">Ville de livraison</label>
        <input type="text" id="ville_livraison"
               name="ville_livraison" required>

        
        <p id="distance-information">
            Les frais de livraison seront confirmés
            lors de la validation de la commande.
        </p>


        <label for="date_prestation">Date de prestation</label>
        <input type="date" id="date_prestation"
               name="date_prestation"
               min="<?= date('Y-m-d') ?>" required>

        <label for="heure_livraison">Heure de livraison</label>
        <input type="time" id="heure_livraison"
               name="heure_livraison" required>

        <label for="nombre_personnes">Nombre de personnes</label>
        <input type="number" id="nombre_personnes"
               name="nombre_personnes"
               min="<?= (int) $menu['nombre_personnes_min'] ?>"
               value="<?= (int) $menu['nombre_personnes_min'] ?>"
               required>

        
        <p id="prix-base"
            data-prix="<?= (float) $menu['prix_base'] ?>">
                <strong>Prix de base :</strong>
                <?= number_format($menu['prix_base'], 2, ',', ' ') ?> €
        </p>



        <div class="recapitulatif">
            <h2>Récapitulatif du prix</h2>

            <p>
                Prix du menu :
                <strong id="prix-menu">0,00 €</strong>
            </p>

            <p>
                Réduction :
                <strong id="reduction">0,00 €</strong>
            </p>

            <p>
                Frais de livraison :
                <strong id="frais-livraison">0,00 €</strong>
            </p>

            <p>
                <strong>Total estimé :</strong>
                <strong id="total">0,00 €</strong>
            </p>

        </div>


        <button type="submit" class="bouton-detail">
            Continuer la commande
        </button>

    </form>

</main>

    <script src="assets/js/commande.js"></script>

</body>
</html>
