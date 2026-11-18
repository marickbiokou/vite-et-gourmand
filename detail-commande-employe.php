<?php

require_once __DIR__ . '/includes/espace-commun.php';

// Vérifier les droits d'accès

compteActif(['employe', 'admin']);

// Récupérer les informations de la commande

$c = commande(identifiant());

// Affichage de la page

debutPage('Détail de la commande');

?>

<a href="espace-employe.php" class="lien-retour">
    Retour aux commandes
</a>

<!-- Affichage des coordonnées du client -->

<article class="carte-menu">

    <h2>Coordonnées du client</h2>

    <p>
        <?= ech($c['prenom'] . ' ' . $c['nom']) ?>
    </p>

    <p>
        <?= ech($c['email']) ?>
    </p>

    <p>
        <?= ech($c['telephone']) ?>
    </p>

</article>

<?php

// Afficher les informations de la commande

infosCommande($c);

// Afficher l'historique des statuts

historique($c['id']);

// Vérifier si la commande peut encore être modifiée

if (in_array(
    $c['statut'],
    ['en_attente', 'acceptee', 'en_preparation'],
    true
)): ?>

    <a
        class="bouton-detail"
        href="modifier-commande-employe.php?id=<?= (int) $c['id'] ?>"
    >
        Modifier la commande
    </a>

<?php endif; ?>

<?php

// Afficher la fin de la page

finPage();