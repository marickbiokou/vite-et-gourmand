<?php

require_once __DIR__ . '/includes/espace-commun.php';

// Vérifier les droits d'accès du client

$u = compteActif(['client']);

// Récupérer la commande appartenant au client connecté

$c = commande(identifiant(), $u['id']);

// Affichage de la page

debutPage('Détail de ma commande');

?>

<a class="lien-retour" href="mes-commandes.php">
    Retour à mes commandes
</a>

<?php

// Afficher les informations de la commande

infosCommande($c);

// Afficher l'historique si la commande n'est plus en attente

if ($c['statut'] !== 'en_attente') {

    historique($c['id']);
}

?>

<?php if ($c['statut'] === 'terminee'): ?>

    <!-- Permettre au client de donner son avis -->

    <a
        class="bouton-detail"
        href="deposer-avis.php?id=<?= (int) $c['id'] ?>"
    >
        Donner mon avis
    </a>

<?php endif; ?>

<?php

// Afficher la fin de la page

finPage();