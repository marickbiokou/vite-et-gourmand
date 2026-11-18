<?php

require_once __DIR__ . '/includes/espace-commun.php';

// Vérification des droits administrateur

$admin = compteActif(['admin']);

// Initialisation des variables

$erreur = '';
$statistiques = [];
$chiffreAffaires = 0;

// Récupération des filtres

$menuId = filter_input(INPUT_GET, 'menu_id', FILTER_VALIDATE_INT);
$dateDebut = $_GET['date_debut'] ?? '';
$dateFin = $_GET['date_fin'] ?? '';

// Vérification du format des dates

function dateValide($date)
{
    if (!is_string($date)) {
        return false;
    }

    $objet = DateTime::createFromFormat('!Y-m-d', $date);

    return $objet && $objet->format('Y-m-d') === $date;
}

// Récupération des menus depuis MySQL

$menus = $pdo->query(
    'SELECT id, nom FROM menus ORDER BY nom'
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($menus as $menu) {

    $statistiques[(int) $menu['id']] = [
        'nom' => $menu['nom'],
        'nombre' => 0,
        'chiffre_affaires' => 0
    ];
}

try {

    require_once __DIR__ . '/config/mongodb.php';

    // Vérification des filtres

    if (
        ($dateDebut !== '' && !dateValide($dateDebut)) ||
        ($dateFin !== '' && !dateValide($dateFin))
    ) {
        throw new InvalidArgumentException('Date invalide.');
    }

    if (
        $dateDebut !== '' &&
        $dateFin !== '' &&
        $dateDebut > $dateFin
    ) {
        throw new InvalidArgumentException(
            'La date de début doit précéder la date de fin.'
        );
    }

    if (
        isset($_GET['menu_id']) &&
        $_GET['menu_id'] !== '' &&
        !isset($statistiques[$menuId])
    ) {
        throw new InvalidArgumentException('Menu invalide.');
    }

    // Récupération des commandes depuis MySQL

    $requete = $pdo->query(
        'SELECT
            c.id,
            c.menu_id,
            m.nom AS nom_menu,
            c.nombre_personnes,
            c.prix_total,
            c.date_prestation,
            c.statut
        FROM commandes c
        INNER JOIN menus m ON m.id = c.menu_id'
    );

    $commandes = $requete->fetchAll(PDO::FETCH_ASSOC);

    // Synchronisation des commandes dans MongoDB

    $bulk = new MongoDB\Driver\BulkWrite();

    $identifiants = [];

    foreach ($commandes as $commande) {

        $id = (int) $commande['id'];

        $identifiants[] = $id;

        $document = [
            'menu_id' => (int) $commande['menu_id'],
            'nom_menu' => $commande['nom_menu'],
            'nombre_personnes' => (int) $commande['nombre_personnes'],
            'prix_total_centimes' => (int) round(
                (float) $commande['prix_total'] * 100
            ),
            'date_prestation' => $commande['date_prestation'],
            'statut' => $commande['statut']
        ];

        // Mise à jour ou création du document

        $bulk->update(
            ['_id' => $id],
            ['$set' => $document],
            ['upsert' => true]
        );
    }

    // Suppression des documents qui ne sont plus dans MySQL

    $bulk->delete(
        ['_id' => ['$nin' => $identifiants]],
        ['limit' => 0]
    );

    $mongo->executeBulkWrite(
        $baseMongo . '.commandes',
        $bulk
    );

    // Calcul du nombre de commandes par menu

    $aggregation = new MongoDB\Driver\Command([
        'aggregate' => 'commandes',
        'pipeline' => [
            [
                '$match' => [
                    'statut' => ['$ne' => 'annulee']
                ]
            ],
            [
                '$group' => [
                    '_id' => '$menu_id',
                    'nombre' => ['$sum' => 1]
                ]
            ]
        ],
        'cursor' => new stdClass()
    ]);

    $resultats = $mongo->executeCommand(
        $baseMongo,
        $aggregation
    );

    foreach ($resultats as $resultat) {

        $id = (int) $resultat->_id;

        if (isset($statistiques[$id])) {

            $statistiques[$id]['nombre'] =
                (int) $resultat->nombre;
        }
    }

    // Préparation des filtres du chiffre d'affaires

    $filtre = [
        'statut' => 'terminee'
    ];

    if ($menuId) {

        $filtre['menu_id'] = $menuId;
    }

    if ($dateDebut !== '') {

        $filtre['date_prestation']['$gte'] = $dateDebut;
    }

    if ($dateFin !== '') {

        $filtre['date_prestation']['$lte'] = $dateFin;
    }

    // Calcul du chiffre d'affaires depuis MongoDB

    $aggregationCA = new MongoDB\Driver\Command([
        'aggregate' => 'commandes',
        'pipeline' => [
            [
                '$match' => $filtre
            ],
            [
                '$group' => [
                    '_id' => '$menu_id',
                    'total' => [
                        '$sum' => '$prix_total_centimes'
                    ]
                ]
            ]
        ],
        'cursor' => new stdClass()
    ]);

    $resultatsCA = $mongo->executeCommand(
        $baseMongo,
        $aggregationCA
    );

    foreach ($resultatsCA as $resultat) {

        $id = (int) $resultat->_id;
        $total = $resultat->total / 100;

        if (isset($statistiques[$id])) {

            $statistiques[$id]['chiffre_affaires'] = $total;

            $chiffreAffaires += $total;
        }
    }

} catch (InvalidArgumentException $e) {

    $erreur = $e->getMessage();

} catch (Throwable $e) {

    // Enregistrement de l'erreur sans divulgation technique

    error_log($e->getMessage());

    $erreur = 'Impossible de récupérer les statistiques.';
}

// Préparation des données du graphique

$nomsMenus = [];
$nombresCommandes = [];

foreach ($statistiques as $statistique) {

    $nomsMenus[] = $statistique['nom'];
    $nombresCommandes[] = $statistique['nombre'];
}

debutPage('Statistiques des commandes');

?>

<a href="espace-admin.php" class="lien-retour">
    Retour à l'espace administrateur
</a>

<?php if ($erreur !== '') : ?>

    <p class="message-erreur">
        <?= ech($erreur) ?>
    </p>

<?php endif; ?>

<?php if ($erreur === '') : ?>

    <section class="carte-menu">

        <h2>Nombre de commandes par menu</h2>

        <p>
            Toutes les commandes, sauf les commandes annulées.
        </p>

        <div class="graphique-statistiques">

            <canvas id="graphiqueCommandes"
                    role="img"
                    aria-label="Nombre de commandes par menu">
            </canvas>

        </div>

        <div class="table-responsive">

            <table class="table-commandes">

                <thead>
                    <tr>
                        <th scope="col">Menu</th>
                        <th scope="col">Nombre de commandes</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($statistiques as $statistique) : ?>

                        <tr>
                            <td><?= ech($statistique['nom']) ?></td>
                            <td><?= $statistique['nombre'] ?></td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>

<?php endif; ?>

<section class="carte-menu statistiques-ca">

    <h2>Chiffre d'affaires</h2>

    <p>
        Calcul effectué sur les commandes terminées,
        selon leur date de prestation.
    </p>

    <form method="get" class="filtres-commandes">

        <label for="menu_id">Menu</label>

        <select name="menu_id" id="menu_id">

            <option value="">Tous les menus</option>

            <?php foreach ($menus as $menu) : ?>

                <option
                    value="<?= (int) $menu['id'] ?>"
                    <?= $menuId === (int) $menu['id']
                        ? 'selected' : '' ?>
                >
                    <?= ech($menu['nom']) ?>
                </option>

            <?php endforeach; ?>

        </select>

        <label for="date_debut">Date de début</label>

        <input
            type="date"
            name="date_debut"
            id="date_debut"
            value="<?= ech(is_string($dateDebut) ? $dateDebut : '') ?>"
        >

        <label for="date_fin">Date de fin</label>

        <input
            type="date"
            name="date_fin"
            id="date_fin"
            value="<?= ech(is_string($dateFin) ? $dateFin : '') ?>"
        >

        <button type="submit" class="bouton-detail">
            Filtrer
        </button>

        <a href="statistiques.php" class="lien-retour">
            Réinitialiser
        </a>

    </form>

    <?php if ($erreur === '') : ?>

        <p class="total-statistiques">
            <?= number_format($chiffreAffaires, 2, ',', ' ') ?> €
        </p>

        <div class="table-responsive">

            <table class="table-commandes">

                <thead>
                    <tr>
                        <th scope="col">Menu</th>
                        <th scope="col">Chiffre d'affaires</th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($statistiques as $id => $statistique) : ?>

                        <?php if ($menuId && $menuId !== $id) continue; ?>

                        <tr>

                            <td>
                                <?= ech($statistique['nom']) ?>
                            </td>

                            <td>
                                <?= number_format(
                                    $statistique['chiffre_affaires'],
                                    2,
                                    ',',
                                    ' '
                                ) ?> €
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    <?php endif; ?>

</section>

<?php if ($erreur === '') : ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js"></script>

    <script>

        // Création du graphique des commandes

        const nomsMenus = <?= json_encode(
            $nomsMenus,
            JSON_HEX_TAG | JSON_HEX_AMP |
            JSON_HEX_APOS | JSON_HEX_QUOT
        ) ?>;

        const nombresCommandes = <?= json_encode(
            $nombresCommandes
        ) ?>;

        if (typeof Chart !== 'undefined') {

            new Chart(
                document.getElementById('graphiqueCommandes'),
                {
                    type: 'bar',

                    data: {
                        labels: nomsMenus,

                        datasets: [{
                            label: 'Nombre de commandes',
                            data: nombresCommandes,
                            backgroundColor: '#7b3028',
                            borderRadius: 5
                        }]
                    },

                    options: {
                        responsive: true,
                        maintainAspectRatio: false,

                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0
                                }
                            }
                        }
                    }
                }
            );
        }

    </script>

<?php endif; ?>

<?php finPage(); ?>