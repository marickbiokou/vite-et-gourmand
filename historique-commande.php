
<?php

session_start();

require_once __DIR__ . '/config/database.php';

// Vérifier les droits d'accès
if (
    !isset($_SESSION['utilisateur_id']) ||
    !in_array($_SESSION['role'] ?? '', ['employe', 'admin'], true)
) {
    http_response_code(403);
    exit('Accès refusé.');
}

// Vérifier que le compte employé est actif
$stmt = $pdo->prepare(
    "SELECT id FROM utilisateurs
     WHERE id = :id AND role IN ('employe', 'admin') AND actif = 1"
);

$stmt->execute([
    'id' => $_SESSION['utilisateur_id']
]);

if (!$stmt->fetch()) {
    http_response_code(403);
    exit('Accès refusé.');
}

// Récupérer l'identifiant de la commande
$commandeId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$commandeId || $commandeId < 1) {
    exit('Commande invalide.');
}

// Vérifier que la commande existe
$stmt = $pdo->prepare(
    "SELECT id FROM commandes WHERE id = :id"
);

$stmt->execute(['id' => $commandeId]);

if (!$stmt->fetch()) {
    exit('Commande inexistante.');
}

// Récupérer l'historique
$sql = "SELECT statut, date_modification
        FROM historique_statuts
        WHERE commande_id = :id
        ORDER BY date_modification ASC, id ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $commandeId]);

$historique = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Historique de la commande - Vite & Gourmand</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<main class="conteneur">

    <h1>Historique de la commande n°<?= (int) $commandeId ?></h1>

    <p>Consultez les différentes étapes de traitement de cette commande.</p>

    <?php if (empty($historique)): ?>

        <div class="carte-menu">
            <p>Aucun historique disponible.</p>
        </div>

    <?php else: ?>

        <div class="table-responsive">

            <table class="table-commandes">

                <thead>
                    <tr>
                        <th>Statut</th>
                        <th>Date et heure</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($historique as $ligne): ?>

                        <tr>

                            <td>
                                <span class="statut statut-<?= htmlspecialchars($ligne['statut']) ?>">

                                    <?= htmlspecialchars($libellesStatuts[$ligne['statut']] ?? $ligne['statut']) ?>

                                </span>
                            </td>

                            <td>
                                <?= date(
                                    'd/m/Y à H:i:s',
                                    strtotime($ligne['date_modification'])
                                ) ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

    <p>
        <a href="espace-employe.php" class="bouton-detail">
            Retour aux commandes
        </a>
    </p>

</main>

</body>
</html>
