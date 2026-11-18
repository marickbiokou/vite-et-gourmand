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

// Vérifier la méthode POST et le jeton CSRF
if (
    $_SERVER['REQUEST_METHOD'] !== 'POST' ||
    empty($_SESSION['csrf_token']) ||
    !is_string($_POST['csrf_token'] ?? null) ||
    !hash_equals(
        $_SESSION['csrf_token'],
        $_POST['csrf_token']
    )
) {
    http_response_code(403);
    exit('Requête non autorisée.');
}

// Récupérer les données du formulaire
$commandeId = filter_input(
    INPUT_POST,
    'commande_id',
    FILTER_VALIDATE_INT
);

$modeContact = $_POST['mode_contact'] ?? '';
$motif = $_POST['motif_annulation'] ?? '';
$contactConfirme = $_POST['contact_confirme'] ?? '';

// Vérifier les données
if (
    !$commandeId ||
    $commandeId < 1 ||
    !in_array($modeContact, ['telephone', 'email'], true) ||
    !is_string($motif) ||
    trim($motif) === '' ||
    $contactConfirme !== '1'
) {
    exit('Informations obligatoires ou invalides.');
}

$motif = trim($motif);

try {

    $pdo->beginTransaction();

    // Récupérer la commande et les informations du menu
    $sql = "SELECT c.id, c.statut, c.menu_id,
                   c.nombre_personnes,
                   m.nombre_personnes_min
            FROM commandes c
            INNER JOIN menus m ON c.menu_id = m.id
            WHERE c.id = :id
            FOR UPDATE";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $commandeId]);

    $commande = $stmt->fetch();

    // Vérifier que l'annulation est autorisée
    if (
        !$commande ||
        !in_array(
            $commande['statut'],
            ['en_attente', 'acceptee', 'en_preparation'],
            true
        )
    ) {
        throw new Exception('Annulation impossible.');
    }

    // Calculer le stock à restituer
    $quantite = (int) ceil(
        $commande['nombre_personnes'] /
        $commande['nombre_personnes_min']
    );

    // Annuler la commande
    $sql = "UPDATE commandes
            SET statut = 'annulee',
                mode_contact = :mode_contact,
                motif_annulation = :motif,
                date_contact = NOW()
            WHERE id = :id";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        'mode_contact' => $modeContact,
        'motif' => $motif,
        'id' => $commandeId
    ]);

    // Restituer le stock réservé
    $stmt = $pdo->prepare(
        "UPDATE menus
         SET stock = stock + :quantite
         WHERE id = :menu_id"
    );

    $stmt->execute([
        'quantite' => $quantite,
        'menu_id' => $commande['menu_id']
    ]);

    // Enregistrer l'annulation dans l'historique
    $stmt = $pdo->prepare(
        "INSERT INTO historique_statuts
         (commande_id, statut, date_modification)
         VALUES (:commande_id, 'annulee', NOW())"
    );

    $stmt->execute([
        'commande_id' => $commandeId
    ]);

    $pdo->commit();

    header('Location: espace-employe.php?annulation=ok');
    exit;

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header('Location: espace-employe.php?annulation=erreur');
    exit;
}
