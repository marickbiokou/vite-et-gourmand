<?php

require_once __DIR__ . '/includes/espace-commun.php';

// Vérifier les droits d'accès

compteActif(['employe', 'admin']);

// Traitement du formulaire de modération

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifierJeton();

    // Récupérer l'identifiant de l'avis

    $id = filter_input(
        INPUT_POST,
        'avis_id',
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 1
            ]
        ]
    );

    // Récupérer la décision de l'employé

    $decision = champ('decision');

    // Vérifier les données reçues

    if (!$id || !in_array($decision, ['valide', 'refuse'], true)) {

        http_response_code(400);
        exit('Données invalides.');
    }

    // Mettre à jour l'état de validation de l'avis

    $sql = "UPDATE avis
            SET valide = ?
            WHERE id = ?";

    $s = $pdo->prepare($sql);

    $s->execute([
        $decision === 'valide' ? 1 : 0,
        $id
    ]);

    // Rediriger après le traitement de l'avis

    header('Location: gestion-avis.php?ok=1');
    exit;
}

// Récupérer les avis et les informations des clients

$sql = "SELECT a.*, u.prenom, u.nom
        FROM avis a
        JOIN commandes c ON c.id = a.commande_id
        JOIN utilisateurs u ON u.id = c.utilisateur_id
        ORDER BY a.id DESC";

$avis = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// Affichage de la page

debutPage('Gestion des avis');

?>

<a class="lien-retour" href="espace-employe.php">
    Retour à mon espace
</a>

<!-- Affichage du message de confirmation -->

<?php if (isset($_GET['ok'])): ?>

    <p class="message-succes">
        Avis traité.
    </p>

<?php endif; ?>

<!-- Vérifier si des avis sont disponibles -->

<?php if (!$avis): ?>

    <p>Aucun avis.</p>

<?php endif; ?>

<!-- Affichage des avis enregistrés -->

<?php foreach ($avis as $a): ?>

    <article class="carte-menu">

        <h2>
            <?= ech($a['prenom'] . ' ' . $a['nom']) ?>
            — <?= (int) $a['note'] ?>/5
        </h2>

        <p>
            <?= ech($a['commentaire']) ?>
        </p>

        <p>
            État : <?= ($a['valide'] ? 'Publié' : 'Non publié') ?>
        </p>

        <!-- Formulaire de validation ou de refus -->

        <form method="post">

            <?php jeton(); ?>

            <input
                type="hidden"
                name="avis_id"
                value="<?= (int) $a['id'] ?>"
            >

            <button name="decision" value="valide">
                Valider
            </button>

            <button name="decision" value="refuse">
                Refuser
            </button>

        </form>

    </article>

<?php endforeach; ?>

<?php

// Afficher la fin de la page

finPage();