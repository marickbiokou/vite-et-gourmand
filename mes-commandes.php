
<?php

session_start();

// Créer un jeton CSRF pour sécuriser les actions du client
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/config/database.php';

// Vérifier que le client est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: connexion.php');
    exit;
}

// Vérifier le rôle de l'utilisateur
if (($_SESSION['role'] ?? '') !== 'client') {
    http_response_code(403);
    exit('Accès refusé.');
}

// Récupérer les commandes du client connecté
$sql = "SELECT
            c.id,
            c.nombre_personnes,
            c.date_prestation,
            c.prix_total,
            c.statut,
            m.nom AS nom_menu
        FROM commandes c
        INNER JOIN menus m ON c.menu_id = m.id
        WHERE c.utilisateur_id = :utilisateur_id
        ORDER BY c.date_creation DESC, c.id DESC";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    'utilisateur_id' => $_SESSION['utilisateur_id']
]);

$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Définir les libellés des statuts
$libellesStatuts = [
    'en_attente' => 'En attente',
    'acceptee' => 'Acceptée',
    'en_preparation' => 'En préparation',
    'en_livraison' => 'En cours de livraison',
    'livree' => 'Livrée',
    'en_attente_retour_materiel' => 'En attente du retour du matériel',
    'terminee' => 'Terminée',
    'annulee' => 'Annulée'
];

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes commandes - Vite & Gourmand</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<main class="conteneur">

    <h1>Mes commandes</h1>

    <p>Retrouvez vos commandes et suivez leur état d'avancement.</p>

    <!-- Messages de confirmation -->

    <?php if (($_GET['annulation'] ?? '') === 'ok'): ?>

        <p class="message-succes">
            Votre commande a bien été annulée.
        </p>

    <?php elseif (($_GET['annulation'] ?? '') === 'erreur'): ?>

        <p class="message-erreur">
            Annulation impossible.
        </p>

    <?php endif; ?>

    <?php if (($_GET['modification'] ?? '') === 'ok'): ?>

        <p class="message-succes">
            Votre commande a bien été modifiée.
        </p>

    <?php endif; ?>

    <!-- Liste des commandes -->

    <?php if (empty($commandes)): ?>

        <div class="carte-menu">

            <p>Vous n'avez pas encore passé de commande.</p>

            <a href="menus.php" class="bouton-detail">
                Découvrir nos menus
            </a>

        </div>

    <?php else: ?>

        <?php foreach ($commandes as $commande): ?>

            <article class="carte-menu">

                <h2>
                    Commande n°<?= (int) $commande['id'] ?>
                </h2>

                <p>
                    <strong>Menu :</strong>
                    <?= htmlspecialchars($commande['nom_menu']) ?>
                </p>

                <p>
                    <strong>Personnes :</strong>
                    <?= (int) $commande['nombre_personnes'] ?>
                </p>

                <p>
                    <strong>Date de prestation :</strong>
                    <?= date('d/m/Y', strtotime($commande['date_prestation'])) ?>
                </p>

                <p>
                    <strong>Statut :</strong>

                    <span class="statut statut-<?= htmlspecialchars($commande['statut']) ?>">

                        <?= htmlspecialchars($libellesStatuts[$commande['statut']] ?? $commande['statut']) ?>

                    </span>
                </p>

                <p class="prix">
                    Prix total :
                    <?= number_format((float) $commande['prix_total'], 2, ',', ' ') ?> €
                </p>

                <!-- Actions autorisées avant acceptation -->

                <?php if ($commande['statut'] === 'en_attente'): ?>

                    <p>
                        <a class="bouton-detail"
                           href="modifier-commande.php?id=<?= (int) $commande['id'] ?>">

                            Modifier ma commande

                        </a>
                    </p>

                    <form action="annuler-commande.php" method="POST">

                        <input type="hidden"
                               name="commande_id"
                               value="<?= (int) $commande['id'] ?>">

                        <input type="hidden"
                               name="csrf_token"
                               value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                        <button type="submit">
                            Annuler ma commande
                        </button>

                    </form>

                <?php endif; ?>

            </article>

        <?php endforeach; ?>

    <?php endif; ?>

</main>

</body>
</html>
