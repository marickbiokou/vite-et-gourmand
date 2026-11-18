<?php

require_once __DIR__ . '/includes/espace-commun.php';

// Vérifier les droits d'accès

compteActif(['employe', 'admin']);

// Récupérer les informations de la commande

$id = identifiant();
$c = commande($id);

// Définir les statuts autorisant une modification

$autorises = [

    'en_attente',
    'acceptee',
    'en_preparation'

];

// Définir les distances de livraison

$distances = [

    'bordeaux' => 0,
    'merignac' => 10,
    'pessac' => 15,
    'libourne' => 35

];

$erreur = '';

// Vérifier que la commande peut être modifiée

if (!in_array($c['statut'], $autorises, true)) {

    http_response_code(403);
    exit('Modification interdite.');
}

// Traitement du formulaire

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifierJeton();

    // Récupérer les informations du formulaire

    $n = filter_input(
        INPUT_POST,
        'nombre_personnes',
        FILTER_VALIDATE_INT,
        [
            'options' => [
                'min_range' => 1,
                'max_range' => 10000
            ]
        ]
    );

    $date = champ('date_prestation');
    $heure = champ('heure_livraison');
    $adresse = champ('adresse_livraison');
    $ville = champ('ville_livraison');
    $mode = champ('mode_contact');

    // Vérifier le format de la date

    $d = DateTime::createFromFormat('!Y-m-d', $date);

    // Vérifier les informations saisies
    // et la confirmation du contact avec le client

    if (
        !$n ||
        !$d ||
        $d->format('Y-m-d') !== $date ||
        $date < date('Y-m-d') ||
        !preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/', $heure) ||
        !$adresse ||
        strlen($adresse) > 255 ||
        !array_key_exists($ville, $distances) ||
        !in_array($mode, ['telephone', 'email'], true) ||
        champ('contact_confirme') !== '1'
    ) {

        $erreur = 'Vérifiez les informations et confirmez le contact avec le client.';

    } else {

        try {

            $pdo->beginTransaction();

            // Récupérer et verrouiller les données de la commande

            $sql = "SELECT c.*, m.nombre_personnes_min,
                           m.prix_base, m.stock
                    FROM commandes c
                    JOIN menus m ON m.id = c.menu_id
                    WHERE c.id = ?
                    FOR UPDATE";

            $s = $pdo->prepare($sql);
            $s->execute([$id]);

            $a = $s->fetch(PDO::FETCH_ASSOC);

            // Vérifier à nouveau le statut de la commande

            if (!$a || !in_array($a['statut'], $autorises, true)) {

                throw new RuntimeException('Modification interdite.');
            }

            // Vérifier le nombre minimum de personnes

            $min = (int) $a['nombre_personnes_min'];

            if ($n < $min) {

                throw new RuntimeException(
                    'Nombre de personnes insuffisant.'
                );
            }

            // Calculer la différence de stock nécessaire

            $delta = (int) ceil($n / $min)
                - (int) ceil($a['nombre_personnes'] / $min);

            // Vérifier la disponibilité du stock

            if ($delta > (int) $a['stock']) {

                throw new RuntimeException('Stock insuffisant.');
            }

            // Calculer le nouveau prix du menu

            $prix = (float) $a['prix_base'] / $min * $n;

            // Appliquer une réduction de 10 %
            // si le nombre minimum est dépassé de 5 personnes

            if ($n >= $min + 5) {

                $prix *= 0.9;
            }

            // Calculer les frais de livraison

            $km = $distances[$ville];

            $frais = $km === 0
                ? 0
                : 5 + 0.59 * $km;

            // Calculer le prix total de la commande

            $total = round($prix + $frais, 2);

            // Mettre à jour le stock du menu

            $sql = "UPDATE menus
                    SET stock = stock - ?
                    WHERE id = ?";

            $s = $pdo->prepare($sql);

            $s->execute([
                $delta,
                $a['menu_id']
            ]);

            // Mettre à jour les informations de la commande

            $sql = "UPDATE commandes
                    SET nombre_personnes = ?,
                        date_prestation = ?,
                        heure_livraison = ?,
                        adresse_livraison = ?,
                        ville_livraison = ?,
                        distance_km = ?,
                        frais_livraison = ?,
                        prix_total = ?,
                        mode_contact = ?,
                        date_contact = NOW()
                    WHERE id = ?";

            $s = $pdo->prepare($sql);

            $s->execute([
                $n,
                $date,
                $heure,
                $adresse,
                $ville,
                $km,
                $frais,
                $total,
                $mode,
                $id
            ]);

            // Valider la transaction

            $pdo->commit();

            // Rediriger vers le détail de la commande

            header(
                'Location: detail-commande-employe.php?id=' . $id . '&ok=1'
            );

            exit;

        } catch (Throwable $e) {

            // Annuler la transaction en cas d'erreur

            if ($pdo->inTransaction()) {

                $pdo->rollBack();
            }

            // Définir le message d'erreur

            $erreur = $e instanceof PDOException
                ? 'Impossible de modifier la commande.'
                : (
                    $e instanceof RuntimeException
                        ? $e->getMessage()
                        : 'Impossible de modifier la commande.'
                );
        }
    }
}

// Affichage de la page

debutPage('Modifier la commande');

?>

<a
    class="lien-retour"
    href="detail-commande-employe.php?id=<?= $id ?>"
>
    Retour au détail
</a>

<!-- Affichage des coordonnées du client -->

<p>
    Client :
    <?= ech(
        $c['prenom'] . ' ' .
        $c['nom'] . ' — ' .
        $c['telephone'] . ' — ' .
        $c['email']
    ) ?>
</p>

<!-- Affichage du menu commandé -->

<p>
    Menu : <?= ech($c['nom_menu']) ?> (inchangé)
</p>

<!-- Affichage du message d'erreur -->

<?php if ($erreur): ?>

    <p class="message-erreur">
        <?= ech($erreur) ?>
    </p>

<?php endif; ?>

<!-- Formulaire de modification de la commande -->

<form method="post" class="formulaire">

    <?php jeton(); ?>

    <?php

    // Définir les champs modifiables

    $champs = [

        'nombre_personnes' => ['Nombre de personnes', 'number'],
        'date_prestation' => ['Date', 'date'],
        'heure_livraison' => ['Heure', 'time'],
        'adresse_livraison' => ['Adresse', 'text']

    ];

    ?>

    <!-- Affichage des champs du formulaire -->

    <?php foreach ($champs as $k => $v): ?>

        <label for="<?= $k ?>">
            <?= $v[0] ?>
        </label>

        <input
            required
            id="<?= $k ?>"
            name="<?= $k ?>"
            type="<?= $v[1] ?>"
            value="<?= ech(
                $k === 'heure_livraison'
                    ? substr($c[$k] ?? '', 0, 5)
                    : $c[$k]
            ) ?>"
        >

    <?php endforeach; ?>

    <!-- Sélection de la ville de livraison -->

    <label for="ville">Ville</label>

    <select name="ville_livraison" id="ville">

        <?php foreach ($distances as $v => $km): ?>

            <option
                value="<?= ech($v) ?>"
                <?= strtolower($c['ville_livraison'] ?? '') === $v
                    ? 'selected'
                    : '' ?>
            >
                <?= ech(ucfirst($v)) ?>
            </option>

        <?php endforeach; ?>

    </select>

    <!-- Sélection du mode de contact -->

    <label for="mode">Mode de contact</label>

    <select name="mode_contact" id="mode">

        <option value="telephone">Téléphone</option>

        <option value="email">E-mail</option>

    </select>

    <!-- Confirmation du contact préalable avec le client -->

    <label>

        <input
            type="checkbox"
            name="contact_confirme"
            value="1"
            required
        >

        Je confirme avoir contacté le client avant cette modification.

    </label>

    <button>
        Enregistrer
    </button>

</form>

<?php

// Afficher la fin de la page

finPage();