<?php

require_once __DIR__ . '/includes/espace-commun.php';

// Vérifier les droits d'accès du client

$u = compteActif(['client']);

$id = identifiant();

$c = commande($id, $u['id']);

// Vérifier que la commande est terminée

if ($c['statut'] !== 'terminee') {

    http_response_code(403);
    exit('Commande non terminée.');
}

$erreur = '';

// Traitement du formulaire

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifierJeton();

    // Récupérer la note et le commentaire

    $note = filter_input(
        INPUT_POST,
        'note',
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 1,
                'max_range' => 5
            ]
        ]
    );

    $commentaire = champ('commentaire');

    // Vérifier les informations saisies

    if (!$note || !$commentaire || strlen($commentaire) > 2000) {

        $erreur = 'Veuillez saisir une note et un commentaire.';

    } else {

        try {

            $pdo->beginTransaction();

            // Vérifier que la commande appartient au client
            // et qu'elle est toujours terminée

            $sql = "SELECT id
                    FROM commandes
                    WHERE id = ?
                    AND utilisateur_id = ?
                    AND statut = 'terminee'
                    FOR UPDATE";

            $s = $pdo->prepare($sql);
            $s->execute([$id, $u['id']]);

            if (!$s->fetch()) {

                throw new RuntimeException();
            }

            // Vérifier qu'aucun avis n'a déjà été déposé

            $sql = "SELECT id
                    FROM avis
                    WHERE commande_id = ?";

            $s = $pdo->prepare($sql);
            $s->execute([$id]);

            if ($s->fetch()) {

                throw new RuntimeException();
            }

            // Enregistrer l'avis en attente de validation

            $sql = "INSERT INTO avis (
                        commande_id,
                        note,
                        commentaire,
                        valide
                    )
                    VALUES (?, ?, ?, ?)";

            $s = $pdo->prepare($sql);

            $s->execute([
                $id,
                $note,
                $commentaire,
                0
            ]);

            // Valider la transaction

            $pdo->commit();

            // Rediriger le client après l'enregistrement

            header('Location: deposer-avis.php?id=' . $id . '&ok=1');
            exit;

        } catch (Throwable $e) {

            // Annuler la transaction en cas d'erreur

            if ($pdo->inTransaction()) {

                $pdo->rollBack();
            }

            $erreur = 'Impossible de déposer cet avis.';
        }
    }
}

// Récupérer l'avis associé à la commande

$sql = "SELECT note, commentaire, valide
        FROM avis
        WHERE commande_id = ?";

$s = $pdo->prepare($sql);
$s->execute([$id]);

$avis = $s->fetch(PDO::FETCH_ASSOC);

// Affichage de la page

debutPage('Donner mon avis');

?>

<a class="lien-retour" href="detail-commande.php?id=<?= $id ?>">
    Retour à la commande
</a>

<?php if ($erreur): ?>

    <p class="message-erreur">
        <?= ech($erreur) ?>
    </p>

<?php endif; ?>

<?php if ($avis): ?>

    <!-- Affichage de l'avis déjà enregistré -->

    <p class="message-succes">
        Votre avis a été enregistré.
    </p>

    <p>
        Note : <?= (int) $avis['note'] ?>/5
    </p>

    <p>
        <?= ech($avis['commentaire']) ?>
    </p>

    <p>
        État : <?= ($avis['valide'] ? 'Publié' : 'Non publié') ?>
    </p>

<?php else: ?>

    <!-- Formulaire de dépôt d'un avis -->

    <form method="post" class="formulaire">

        <?php jeton(); ?>

        <label for="note">Note</label>

        <select name="note" id="note">

            <?php for ($i = 1; $i <= 5; $i++): ?>

                <option><?= $i ?></option>

            <?php endfor; ?>

        </select>

        <label for="commentaire">Commentaire</label>

        <textarea
            name="commentaire"
            id="commentaire"
            maxlength="2000"
            required
        ></textarea>

        <button>
            Envoyer mon avis
        </button>

    </form>

<?php endif; ?>

<?php

finPage();