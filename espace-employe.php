
<?php

session_start();

require_once __DIR__ . '/config/database.php';

// Vérifier les droits d'accès
if (
    !isset($_SESSION['utilisateur_id']) ||
    ($_SESSION['role'] ?? '') !== 'employe'
) {
    http_response_code(403);
    exit('Accès refusé.');
}

// Vérifier que le compte employé est actif
$stmt = $pdo->prepare(
    "SELECT id FROM utilisateurs
     WHERE id = :id AND role = 'employe' AND actif = 1"
);

$stmt->execute([
    'id' => $_SESSION['utilisateur_id']
]);

if (!$stmt->fetch()) {
    http_response_code(403);
    exit('Accès refusé.');
}

// Générer un jeton de sécurité CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Définir les changements de statut autorisés
$transitions = [
    'en_attente' => ['acceptee'],
    'acceptee' => ['en_preparation'],
    'en_preparation' => ['en_livraison'],
    'en_livraison' => ['livree'],
    'livree' => [
        'terminee',
        'en_attente_retour_materiel'
    ],
    'en_attente_retour_materiel' => ['terminee']
];

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

// Récupérer les statuts autorisés
$statutsAutorises = array_keys($libellesStatuts);

// Récupérer les valeurs des filtres
$filtreStatut = $_GET['statut'] ?? '';
$filtreClient = $_GET['client'] ?? '';

// Vérifier les valeurs des filtres
if (!in_array($filtreStatut, $statutsAutorises, true)) {
    $filtreStatut = '';
}

$filtreClient = is_string($filtreClient)
    ? trim($filtreClient)
    : '';

// Récupérer les commandes des clients
$sql = "SELECT c.id,
               c.nombre_personnes,
               c.date_prestation,
               c.heure_livraison,
               c.prix_total,
               c.statut,
               m.nom AS nom_menu,
               u.nom,
               u.prenom
        FROM commandes c
        INNER JOIN menus m ON c.menu_id = m.id
        INNER JOIN utilisateurs u ON c.utilisateur_id = u.id
        WHERE 1 = 1";

$parametres = [];

// Filtrer par statut
if ($filtreStatut !== '') {
    $sql .= " AND c.statut = :statut";
    $parametres['statut'] = $filtreStatut;
}

// Filtrer par client
if ($filtreClient !== '') {
    $sql .= " AND (u.nom LIKE :nom OR u.prenom LIKE :prenom)";

    $parametres['nom'] = '%' . $filtreClient . '%';
    $parametres['prenom'] = '%' . $filtreClient . '%';
}

$sql .= " ORDER BY c.date_creation DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($parametres);

$commandes = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace employé - Vite & Gourmand</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<main class="conteneur">

    <h1>Espace employé</h1>

    <p>Bienvenue <?= htmlspecialchars($_SESSION['prenom']) ?>.</p>

    <h2>Gestion des commandes</h2>

    <!-- Messages de confirmation -->

    <?php if (($_GET['statut'] ?? '') === 'ok'): ?>

        <p class="message-succes">
            Le statut de la commande a bien été modifié.
        </p>

    <?php elseif (($_GET['statut'] ?? '') === 'erreur'): ?>

        <p class="message-erreur">
            Impossible de modifier le statut de la commande.
        </p>

    <?php endif; ?>

    <?php if (($_GET['annulation'] ?? '') === 'ok'): ?>

        <p class="message-succes">
            La commande a bien été annulée.
        </p>

    <?php elseif (($_GET['annulation'] ?? '') === 'erreur'): ?>

        <p class="message-erreur">
            Impossible d'annuler la commande.
        </p>

    <?php endif; ?>

    <!-- Formulaire de filtrage -->

    <form method="GET"
          action="espace-employe.php"
          class="filtres-commandes">

        <label for="statut">Statut</label>

        <select name="statut" id="statut">

            <option value="">Tous les statuts</option>

            <?php foreach ($statutsAutorises as $statut): ?>

                <option value="<?= htmlspecialchars($statut) ?>"
                    <?= $filtreStatut === $statut ? 'selected' : '' ?>>

                    <?= htmlspecialchars($libellesStatuts[$statut]) ?>

                </option>

            <?php endforeach; ?>

        </select>

        <label for="client">Client</label>

        <input type="text"
               name="client"
               id="client"
               placeholder="Nom ou prénom du client"
               value="<?= htmlspecialchars($filtreClient) ?>">

        <button type="submit">Filtrer</button>

        <a href="espace-employe.php">Réinitialiser</a>

    </form>

    <!-- Liste des commandes -->

    <?php if (empty($commandes)): ?>

        <p>Aucune commande trouvée.</p>

    <?php else: ?>

        <div class="table-responsive">

            <table class="table-commandes">

                <thead>
                    <tr>
                        <th>N°</th>
                        <th>Client</th>
                        <th>Menu</th>
                        <th>Personnes</th>
                        <th>Date</th>
                        <th>Heure</th>
                        <th>Prix total</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($commandes as $commande): ?>

                        <tr>

                            <td><?= (int) $commande['id'] ?></td>

                            <td>
                                <?= htmlspecialchars($commande['prenom'] . ' ' . $commande['nom']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($commande['nom_menu']) ?>
                            </td>

                            <td>
                                <?= (int) $commande['nombre_personnes'] ?>
                            </td>

                            <td>
                                <?= date('d/m/Y', strtotime($commande['date_prestation'])) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(substr($commande['heure_livraison'] ?? '', 0, 5)) ?>
                            </td>

                            <td>
                                <?= number_format((float) $commande['prix_total'], 2, ',', ' ') ?> €
                            </td>

                            <!-- Statut de la commande -->

                            <td>
                                <span class="statut statut-<?= htmlspecialchars($commande['statut']) ?>">

                                    <?= htmlspecialchars($libellesStatuts[$commande['statut']] ?? $commande['statut']) ?>

                                </span>
                            </td>

                            <!-- Actions disponibles -->

                            <td>

                                <?php if (isset($transitions[$commande['statut']])): ?>

                                    <form method="POST" action="modifier-statut.php">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                                        <input type="hidden"
                                               name="commande_id"
                                               value="<?= (int) $commande['id'] ?>">

                                        <?php foreach ($transitions[$commande['statut']] as $statut): ?>

                                            <button type="submit"
                                                    name="nouveau_statut"
                                                    value="<?= htmlspecialchars($statut) ?>">

                                                <?= htmlspecialchars($libellesStatuts[$statut]) ?>

                                            </button>

                                        <?php endforeach; ?>

                                    </form>

                                <?php endif; ?>

                                <?php if (in_array(
                                    $commande['statut'],
                                    ['en_attente', 'acceptee', 'en_preparation'],
                                    true
                                )): ?>

                                    <a href="annuler-commande-employe.php?id=<?= (int) $commande['id'] ?>">
                                        Annuler
                                    </a>

                                <?php endif; ?>

                                <a href="historique-commande.php?id=<?= (int) $commande['id'] ?>">
                                    Historique
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
