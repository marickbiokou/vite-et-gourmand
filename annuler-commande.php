
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}

if (
    empty($_SESSION['csrf_token']) ||
    !isset($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    http_response_code(403);
    exit('Requête non autorisée.');
}

$commandeId = filter_input(INPUT_POST, 'commande_id', FILTER_VALIDATE_INT);

if (!$commandeId || $commandeId < 1) {
    http_response_code(400);
    exit('Commande invalide.');
}

try {
    $pdo->beginTransaction();

    // Verrouiller la commande pour éviter deux annulations simultanées.
    $stmt = $pdo->prepare(
        "SELECT c.id, c.menu_id, c.nombre_personnes,
                c.statut, m.nombre_personnes_min
         FROM commandes c
         INNER JOIN menus m ON m.id = c.menu_id
         WHERE c.id = :commande_id
           AND c.utilisateur_id = :utilisateur_id
         FOR UPDATE"
    );

    $stmt->execute([
        'commande_id' => $commandeId,
        'utilisateur_id' => $_SESSION['utilisateur_id']
    ]);

    $commande = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$commande) {
        throw new RuntimeException('Commande introuvable.');
    }

    if ($commande['statut'] !== 'en_attente') {
        throw new RuntimeException('Cette commande ne peut plus être annulée.');
    }

    // Calcul identique à commander.php.
    $stockARestituer = (int) ceil(
        (int) $commande['nombre_personnes'] /
        (int) $commande['nombre_personnes_min']
    );

    // Modifier le statut.
    $stmt = $pdo->prepare(
        "UPDATE commandes
         SET statut = 'annulee'
         WHERE id = :id AND statut = 'en_attente'"
    );
    $stmt->execute(['id' => $commandeId]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Annulation impossible.');
    }

    // Restituer le stock réservé.
    $stmt = $pdo->prepare(
        "UPDATE menus SET stock = stock + :quantite WHERE id = :menu_id"
    );
    $stmt->execute([
        'quantite' => $stockARestituer,
        'menu_id' => $commande['menu_id']
    ]);

    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException('Restitution du stock impossible.');
    }

    // Enregistrer le changement de statut.
    $stmt = $pdo->prepare(
        "INSERT INTO historique_statuts
            (commande_id, statut, date_modification)
         VALUES (:commande_id, 'annulee', NOW())"
    );
    $stmt->execute(['commande_id' => $commandeId]);

    $pdo->commit();

    header('Location: mes-commandes.php?annulation=ok');
    exit;

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());
    header('Location: mes-commandes.php?annulation=erreur');
    exit;
}
