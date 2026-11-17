
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

// Vérifier que le compte est toujours actif
$stmt = $pdo->prepare(
    "SELECT id FROM utilisateurs
     WHERE id = :id AND role = 'employe' AND actif = 1"
);

$stmt->execute(['id' => $_SESSION['utilisateur_id']]);

if (!$stmt->fetch()) {
    http_response_code(403);
    exit('Accès refusé.');
}

// Récupérer l'identifiant de la commande
$commandeId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$commandeId || $commandeId < 1) {
    exit('Commande invalide.');
}

// Récupérer la commande et les coordonnées du client
$sql = "SELECT c.id, c.statut, u.nom, u.prenom,
               u.email, u.telephone
        FROM commandes c
        INNER JOIN utilisateurs u ON c.utilisateur_id = u.id
        WHERE c.id = :id";

$stmt = $pdo->prepare($sql);
$stmt->execute(['id' => $commandeId]);

$commande = $stmt->fetch();

if (
    !$commande ||
    !in_array(
        $commande['statut'],
        ['en_attente', 'acceptee', 'en_preparation'],
        true
    )
) {
    exit('Cette commande ne peut pas être annulée.');
}

// Générer le jeton CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Définir les libellés des statuts
$libellesStatuts = [
    'en_attente' => 'En attente',
    'acceptee' => 'Acceptée',
    'en_preparation' => 'En préparation'
];

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Annuler une commande - Vite & Gourmand</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require_once __DIR__ . '/includes/header.php'; ?>

<main class="conteneur">

    <h1>Annulation de la commande n°<?= (int) $commande['id'] ?></h1>

    <a href="espace-employe.php" class="lien-retour">
        Retour aux commandes
    </a>

    <!-- Informations sur le client -->

    <section class="carte-menu">

        <h2>Coordonnées du client</h2>

        <p>
            <strong>Client :</strong>
            <?= htmlspecialchars($commande['prenom'] . ' ' . $commande['nom']) ?>
        </p>

        <p>
            <strong>Email :</strong>
            <?= htmlspecialchars($commande['email']) ?>
        </p>

        <p>
            <strong>Téléphone :</strong>
            <?= htmlspecialchars($commande['telephone'] ?? '') ?>
        </p>

        <p>
            <strong>Statut actuel :</strong>

            <span class="statut statut-<?= htmlspecialchars($commande['statut']) ?>">
                <?= htmlspecialchars($libellesStatuts[$commande['statut']]) ?>
            </span>
        </p>

    </section>

    <h2>Informations relatives à l'annulation</h2>

    <p class="message-erreur">
        Le client doit être contacté avant toute annulation de sa commande.
    </p>

    <!-- Formulaire d'annulation -->

    <form method="POST"
          action="traiter-annulation-employe.php"
          class="formulaire">

        <input type="hidden"
               name="commande_id"
               value="<?= (int) $commande['id'] ?>">

        <input type="hidden"
               name="csrf_token"
               value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

        <label for="mode_contact">Mode de contact</label>

        <select name="mode_contact" id="mode_contact" required>

            <option value="">Choisir un mode de contact</option>
            <option value="telephone">Téléphone</option>
            <option value="email">Email</option>

        </select>

        <label for="motif_annulation">Motif de l'annulation</label>

        <textarea name="motif_annulation"
                  id="motif_annulation"
                  placeholder="Indiquez le motif de l'annulation..."
                  rows="5"
                  required></textarea>

        <label>
            <input type="checkbox"
                   name="contact_confirme"
                   value="1"
                   required>

            Je confirme avoir contacté le client.
        </label>

        <button type="submit">
            Confirmer l'annulation
        </button>

        <a href="espace-employe.php" class="lien-retour">
            Retour sans annuler
        </a>

    </form>

</main>

</body>
</html>
