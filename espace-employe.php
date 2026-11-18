<?php

require_once __DIR__ . '/includes/espace-commun.php';

// Vérifier les droits d'accès

$u = compteActif(['employe', 'admin']);

// Récupérer les libellés des statuts

$labels = libelles();

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

// Récupérer et vérifier les filtres

$statut = $_GET['statut'] ?? '';

if (!is_string($statut) || !isset($labels[$statut])) {

    $statut = '';
}

$client = is_string($_GET['client'] ?? null)
    ? trim($_GET['client'])
    : '';

// Préparer la requête de récupération des commandes

$sql = "SELECT c.*, m.nom AS nom_menu, u.nom, u.prenom
        FROM commandes c
        JOIN menus m ON m.id = c.menu_id
        JOIN utilisateurs u ON u.id = c.utilisateur_id
        WHERE 1 = 1";

$params = [];

// Appliquer le filtre par statut

if ($statut !== '') {

    $sql .= ' AND c.statut = ?';

    $params[] = $statut;
}

// Appliquer le filtre par client

if ($client !== '') {

    $sql .= ' AND (u.nom LIKE ? OR u.prenom LIKE ?)';

    $params[] = '%' . $client . '%';
    $params[] = '%' . $client . '%';
}

// Trier les commandes de la plus récente à la plus ancienne

$sql .= ' ORDER BY c.date_creation DESC, c.id DESC';

// Exécuter la requête

$s = $pdo->prepare($sql);
$s->execute($params);

$commandes = $s->fetchAll(PDO::FETCH_ASSOC);

// Affichage de la page

debutPage('Espace employé');

?>

<p>
    Bienvenue <?= ech($u['prenom']) ?>.
</p>

<!-- Gestion du catalogue -->

<h2>Gestion du catalogue</h2>

<?php

$liensCatalogue = [

    'gestion-menus.php' => 'Gérer les menus',
    'gestion-plats.php' => 'Gérer les plats',
    'gestion-horaires.php' => 'Gérer les horaires',
    'gestion-avis.php' => 'Gérer les avis'

];

?>

<?php foreach ($liensCatalogue as $url => $label): ?>

    <a class="bouton-detail" href="<?= ech($url) ?>">
        <?= ech($label) ?>
    </a>

<?php endforeach; ?>

<!-- Gestion des commandes -->

<h2>Gestion des commandes</h2>

<?php

// Définir les messages de confirmation

$messages = [

    'statut' => 'Statut modifié.',
    'annulation' => 'Commande annulée.'

];

?>

<!-- Afficher les messages de confirmation ou d'erreur -->

<?php foreach ($messages as $k => $message): ?>

    <?php if (($_GET[$k] ?? '') === 'ok'): ?>

        <p class="message-succes">
            <?= ech($message) ?>
        </p>

    <?php elseif (($_GET[$k] ?? '') === 'erreur'): ?>

        <p class="message-erreur">
            Opération impossible.
        </p>

    <?php endif; ?>

<?php endforeach; ?>

<!-- Formulaire de filtrage des commandes -->

<form method="get" class="filtres-commandes">

    <label for="statut">Statut</label>

    <select id="statut" name="statut">

        <option value="">Tous les statuts</option>

        <?php foreach ($labels as $k => $v): ?>

            <option
                value="<?= ech($k) ?>"
                <?= $statut === $k ? 'selected' : '' ?>
            >
                <?= ech($v) ?>
            </option>

        <?php endforeach; ?>

    </select>

    <label for="client">Client</label>

    <input
        id="client"
        name="client"
        value="<?= ech($client) ?>"
    >

    <button>
        Filtrer
    </button>

    <a href="espace-employe.php">
        Réinitialiser
    </a>

</form>

<!-- Affichage des commandes -->

<?php if (!$commandes): ?>

    <p>Aucune commande trouvée.</p>

<?php else: ?>

    <div class="table-responsive">

        <table class="table-commandes">

            <thead>

                <tr>

                    <?php

                    $colonnes = [

                        'N°',
                        'Client',
                        'Menu',
                        'Personnes',
                        'Date',
                        'Heure',
                        'Prix total',
                        'Statut',
                        'Actions'

                    ];

                    ?>

                    <?php foreach ($colonnes as $h): ?>

                        <th><?= ech($h) ?></th>

                    <?php endforeach; ?>

                </tr>

            </thead>

            <tbody>

                <?php foreach ($commandes as $c): ?>

                    <tr>

                        <!-- Numéro de la commande -->

                        <td>
                            <?= (int) $c['id'] ?>
                        </td>

                        <!-- Nom et prénom du client -->

                        <td>
                            <?= ech($c['prenom'] . ' ' . $c['nom']) ?>
                        </td>

                        <!-- Menu commandé -->

                        <td>
                            <?= ech($c['nom_menu']) ?>
                        </td>

                        <!-- Nombre de personnes -->

                        <td>
                            <?= (int) $c['nombre_personnes'] ?>
                        </td>

                        <!-- Date de prestation -->

                        <td>
                            <?= ech($c['date_prestation']) ?>
                        </td>

                        <!-- Heure de livraison -->

                        <td>
                            <?= ech(substr(
                                $c['heure_livraison'] ?? '',
                                0,
                                5
                            )) ?>
                        </td>

                        <!-- Prix total -->

                        <td>
                            <?= number_format(
                                (float) $c['prix_total'],
                                2,
                                ',',
                                ' '
                            ) ?> €
                        </td>

                        <!-- Statut actuel -->

                        <td>

                            <?php statut($c['statut']); ?>

                        </td>

                        <!-- Actions disponibles -->

                        <td>

                            <!-- Modification du statut -->

                            <?php if (isset($transitions[$c['statut']])): ?>

                                <form
                                    method="post"
                                    action="modifier-statut.php"
                                >

                                    <?php jeton(); ?>

                                    <input
                                        type="hidden"
                                        name="commande_id"
                                        value="<?= (int) $c['id'] ?>"
                                    >

                                    <?php foreach ($transitions[$c['statut']] as $next): ?>

                                        <button
                                            name="nouveau_statut"
                                            value="<?= ech($next) ?>"
                                        >
                                            <?= ech($labels[$next]) ?>
                                        </button>

                                    <?php endforeach; ?>

                                </form>

                            <?php endif; ?>

                            <!-- Modification et annulation de la commande -->

                            <?php if (in_array(
                                $c['statut'],
                                [
                                    'en_attente',
                                    'acceptee',
                                    'en_preparation'
                                ],
                                true
                            )): ?>

                                <a href="modifier-commande-employe.php?id=<?= (int) $c['id'] ?>">
                                    Modifier
                                </a>

                                <a href="annuler-commande-employe.php?id=<?= (int) $c['id'] ?>">
                                    Annuler
                                </a>

                            <?php endif; ?>

                            <!-- Consultation du détail de la commande -->

                            <a href="detail-commande-employe.php?id=<?= (int) $c['id'] ?>">
                                Détail
                            </a>

                            <!-- Consultation de l'historique -->

                            <a href="historique-commande.php?id=<?= (int) $c['id'] ?>">
                                Historique
                            </a>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

<?php endif; ?>

<?php

// Afficher la fin de la page

finPage();