
<?php
session_start();

require_once __DIR__ . '/config/database.php';

// Vérifier que le client est connecté
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: connexion.php');
    exit;
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


?>


<?php
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

    <?php if (empty($commandes)): ?>
        <p>Vous n'avez pas encore passé de commande.</p>
        <a href="menus.php">Découvrir nos menus</a>
    <?php else: ?>

        <?php foreach ($commandes as $commande): ?>
            <article class="carte-menu">
                <h2>
                    Commande n°
                    <?= (int) $commande['id'] ?>
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
                    <?= htmlspecialchars($commande['date_prestation']) ?>
                </p>

                <p>
                    <strong>Prix total :</strong>
                    <?= number_format((float) $commande['prix_total'], 2, ',', ' ') ?> €
                </p>

                <p>
                    <strong>Statut :</strong>
                    <?= htmlspecialchars($libellesStatuts[$commande['statut']] ?? $commande['statut']) ?>
                </p>
            </article>
        <?php endforeach; ?>

    <?php endif; ?>
</main>

</body>
</html>

