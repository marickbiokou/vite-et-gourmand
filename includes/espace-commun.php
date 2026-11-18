<?php

// Fonctions communes aux espaces privés.

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';


// Protection des données affichées

function ech($v)
{
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
}


// Vérification du compte et des droits d'accès

function compteActif($roles)
{
    global $pdo;

    if (
        !isset($_SESSION['utilisateur_id'])
        || !in_array($_SESSION['role'] ?? '', $roles, true)
    ) {
        http_response_code(403);
        exit('Accès refusé.');
    }

    $s = $pdo->prepare(
        'SELECT * FROM utilisateurs
         WHERE id = ? AND role = ? AND actif = 1'
    );

    $s->execute([
        $_SESSION['utilisateur_id'],
        $_SESSION['role']
    ]);

    $u = $s->fetch(PDO::FETCH_ASSOC);

    if (!$u) {
        http_response_code(403);
        exit('Accès refusé.');
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $u;
}


// Vérification du jeton CSRF

function verifierJeton()
{
    if (
        !is_string($_POST['csrf_token'] ?? null)
        || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        http_response_code(403);
        exit('Requête non autorisée.');
    }
}


// Génération du champ CSRF

function jeton()
{
    echo '<input type="hidden" name="csrf_token" value="'
        . ech($_SESSION['csrf_token'])
        . '">';
}


// Récupération d'un champ de formulaire

function champ($nom)
{
    return is_string($_POST[$nom] ?? null)
        ? trim($_POST[$nom])
        : '';
}


// Vérification d'un identifiant

function identifiant()
{
    $id = filter_input(
        INPUT_GET,
        'id',
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if (!$id) {
        http_response_code(404);
        exit('Élément introuvable.');
    }

    return $id;
}


// Libellés des statuts de commande

function libelles()
{
    return [
        'en_attente' => 'En attente',
        'acceptee' => 'Acceptée',
        'en_preparation' => 'En préparation',
        'en_livraison' => 'En cours de livraison',
        'livree' => 'Livrée',
        'en_attente_retour_materiel' => 'En attente du retour du matériel',
        'terminee' => 'Terminée',
        'annulee' => 'Annulée'
    ];
}


// Affichage du statut d'une commande

function statut($s)
{
    echo '<span class="statut statut-' . ech($s) . '">'
        . ech(libelles()[$s] ?? $s)
        . '</span>';
}


// Début d'une page HTML

function debutPage($titre)
{
    ?>

    <!DOCTYPE html>
    <html lang="fr">

    <head>
        <meta charset="UTF-8">

        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title><?= ech($titre) ?> - Vite &amp; Gourmand</title>

        <link rel="stylesheet" href="assets/css/style.css">
    </head>

    <body>

        <?php require __DIR__ . '/header.php'; ?>

        <main class="conteneur">

            <h1><?= ech($titre) ?></h1>

    <?php
}


// Fin d'une page HTML

function finPage()
{
    global $pdo;

    ?>

        </main>

        <?php require __DIR__ . '/footer.php'; ?>

    </body>

    </html>

    <?php
}


// Récupération des informations d'une commande

function commande($id, $client = null)
{
    global $pdo;

    $sql = '
        SELECT
            c.*,
            m.nom AS nom_menu,
            m.nombre_personnes_min,
            m.prix_base,
            u.nom,
            u.prenom,
            u.email,
            u.telephone

        FROM commandes c

        JOIN menus m
            ON m.id = c.menu_id

        JOIN utilisateurs u
            ON u.id = c.utilisateur_id

        WHERE c.id = ?
    ';

    $params = [$id];

    if ($client !== null) {
        $sql .= ' AND c.utilisateur_id = ?';
        $params[] = $client;
    }

    $s = $pdo->prepare($sql);

    $s->execute($params);

    $c = $s->fetch(PDO::FETCH_ASSOC);

    if (!$c) {
        http_response_code(404);
        exit('Commande introuvable.');
    }

    return $c;
}


// Affichage des informations d'une commande

function infosCommande($c)
{
    ?>

    <article class="carte-menu">

        <h2>Commande n°<?= (int) $c['id'] ?></h2>

        <p>
            Menu : <?= ech($c['nom_menu']) ?>
        </p>

        <p>
            Nombre de personnes : <?= (int) $c['nombre_personnes'] ?>
        </p>

        <p>
            Date : <?= ech($c['date_prestation']) ?>
            à <?= ech(substr($c['heure_livraison'] ?? '', 0, 5)) ?>
        </p>

        <p>
            Adresse : <?= ech($c['adresse_livraison']) ?>,
            <?= ech($c['ville_livraison']) ?>
        </p>

        <p>
            Prix du menu :
            <?= number_format(
                (float) $c['prix_total'] - (float) $c['frais_livraison'],
                2,
                ',',
                ' '
            ) ?> €
        </p>

        <p>
            Livraison :
            <?= number_format(
                (float) $c['frais_livraison'],
                2,
                ',',
                ' '
            ) ?> €
        </p>

        <p class="prix">
            Total :
            <?= number_format(
                (float) $c['prix_total'],
                2,
                ',',
                ' '
            ) ?> €
        </p>

        <p>
            Statut : <?php statut($c['statut']); ?>
        </p>

    </article>

    <?php
}


// Affichage de l'historique des statuts

function historique($id)
{
    global $pdo;

    $s = $pdo->prepare(
        'SELECT statut, date_modification
         FROM historique_statuts
         WHERE commande_id = ?
         ORDER BY date_modification, id'
    );

    $s->execute([$id]);

    ?>

    <h2>Historique des statuts</h2>

    <div class="table-responsive">

        <table class="table-commandes">

            <thead>

                <tr>
                    <th>Statut</th>
                    <th>Date et heure</th>
                </tr>

            </thead>

            <tbody>

                <?php foreach ($s->fetchAll(PDO::FETCH_ASSOC) as $h): ?>

                    <tr>

                        <td>
                            <?php statut($h['statut']); ?>
                        </td>

                        <td>
                            <?= ech($h['date_modification']) ?>
                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

    <?php
}