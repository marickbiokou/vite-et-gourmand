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

// Vérifier que le compte employé est toujours actif
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

// Vérifier l'identifiant de la commande
$commandeId = filter_input(
    INPUT_POST,
    'commande_id',
    FILTER_VALIDATE_INT
);

if (!$commandeId || $commandeId < 1) {
    exit('Commande invalide.');
}

// Définir les transitions autorisées
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

try {

    $pdo->beginTransaction();

    // Récupérer le statut actuel
    $stmt = $pdo->prepare(
        "SELECT statut FROM commandes
         WHERE id = :id
         FOR UPDATE"
    );

    $stmt->execute(['id' => $commandeId]);

    $commande = $stmt->fetch();

    // Récupérer le statut choisi par l'employé
    $nouveauStatut = $_POST['nouveau_statut'] ?? '';

    // Vérifier que la transition est autorisée
    if (
        !$commande ||
        !is_string($nouveauStatut) ||
        !isset($transitions[$commande['statut']]) ||
        !in_array(
            $nouveauStatut,
            $transitions[$commande['statut']],
            true
        )
    ) {
        throw new Exception('Transition impossible.');
    }

    // Modifier le statut de la commande
    $stmt = $pdo->prepare(
        "UPDATE commandes
         SET statut = :statut
         WHERE id = :id"
    );

    $stmt->execute([
        'statut' => $nouveauStatut,
        'id' => $commandeId
    ]);

    // Enregistrer le changement dans l'historique
    $stmt = $pdo->prepare(
        "INSERT INTO historique_statuts
         (commande_id, statut, date_modification)
         VALUES (:commande_id, :statut, NOW())"
    );

    $stmt->execute([
        'commande_id' => $commandeId,
        'statut' => $nouveauStatut
    ]);

    $pdo->commit();

    header('Location: espace-employe.php?statut=ok');
    exit;

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    header('Location: espace-employe.php?statut=erreur');
    exit;
}