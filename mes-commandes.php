<?php

require_once __DIR__ . '/includes/espace-commun.php';

// Vérifier les droits d'accès du client

$u = compteActif(['client']);

// Récupérer les commandes du client connecté

$sql = "SELECT c.*, m.nom AS nom_menu
        FROM commandes c
        JOIN menus m ON m.id = c.menu_id
        WHERE c.utilisateur_id = ?
        ORDER BY c.date_creation DESC, c.id DESC";

$s = $pdo->prepare($sql);
$s->execute([$u['id']]);

$commandes = $s->fetchAll(PDO::FETCH_ASSOC);

// Affichage de la page

debutPage('Mes commandes');

?>

<a class="lien-retour" href="mon-profil.php">
    Mon profil
</a>

<?php

// Définir les messages de confirmation

$messages = [

    'annulation' => 'annulée',
    'modification' => 'modifiée'

];

?>

<!-- Affichage des messages de confirmation ou d'erreur -->

<?php foreach ($messages as $k => $v): ?>

    <?php if (($_GET[$k] ?? '') === 'ok'): ?>

        <p class="message-succes">
            La commande a bien été <?= ech($v) ?>.
        </p>

    <?php elseif (($_GET[$k] ?? '') === 'erreur'): ?>

        <p class="message-erreur">
            Impossible de traiter la commande.
        </p>

    <?php endif; ?>

<?php endforeach; ?>

<!-- Vérifier si le client possède des commandes -->

<?php if (!$commandes): ?>

    <p>Aucune commande enregistrée.</p>

    <a href="menus.php" class="bouton-detail">
        Découvrir nos menus
    </a>

<?php endif; ?>

<!-- Affichage des commandes du client -->

<?php foreach ($commandes as $c): ?>

    <article class="carte-menu">

        <h2>
            Commande n°<?= (int) $c['id'] ?>
        </h2>

        <!-- Informations du menu -->

        <p>
            <?= ech($c['nom_menu']) ?>
            — <?= (int) $c['nombre_personnes'] ?> personnes
        </p>

        <!-- Date de prestation -->

        <p>
            <?= ech($c['date_prestation']) ?>
        </p>

        <!-- Prix total de la commande -->

        <p class="prix">
            <?= number_format(
                (float) $c['prix_total'],
                2,
                ',',
                ' '
            ) ?> €
        </p>

        <!-- Statut actuel de la commande -->

        <p>
            <?php statut($c['statut']); ?>
        </p>

        <!-- Consultation du détail de la commande -->

        <a
            class="bouton-detail"
            href="detail-commande.php?id=<?= (int) $c['id'] ?>"
        >
            Voir le détail
        </a>

        <!-- Modification et annulation avant acceptation -->

        <?php if ($c['statut'] === 'en_attente'): ?>

            <a
                class="bouton-detail"
                href="modifier-commande.php?id=<?= (int) $c['id'] ?>"
            >
                Modifier
            </a>

            <form
                method="post"
                action="annuler-commande.php"
                onsubmit="return confirm('Annuler cette commande ?')"
            >

                <?php jeton(); ?>

                <input
                    type="hidden"
                    name="commande_id"
                    value="<?= (int) $c['id'] ?>"
                >

                <button>
                    Annuler
                </button>

            </form>

        <?php endif; ?>

    </article>

<?php endforeach; ?>

<?php

// Afficher la fin de la page

finPage();