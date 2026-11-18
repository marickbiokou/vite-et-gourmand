<?php

require_once __DIR__ . '/includes/espace-commun.php';

// Vérifier les droits d'accès du client

$u = compteActif(['client']);

$erreur = '';

// Traitement du formulaire de modification du profil

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifierJeton();

    // Récupérer les informations saisies

    $nom = champ('nom');
    $prenom = champ('prenom');
    $tel = champ('telephone');
    $adresse = champ('adresse');

    // Vérifier les informations du client

    if (
        !$nom ||
        !$prenom ||
        !$tel ||
        !$adresse ||
        strlen($nom) > 100 ||
        strlen($prenom) > 100 ||
        strlen($tel) > 30 ||
        strlen($adresse) > 255
    ) {

        $erreur = 'Veuillez vérifier les informations.';

    } else {

        // Mettre à jour les informations du client

        $sql = "UPDATE utilisateurs
                SET nom = ?,
                    prenom = ?,
                    telephone = ?,
                    adresse = ?
                WHERE id = ?
                AND role = 'client'
                AND actif = 1";

        $s = $pdo->prepare($sql);

        $s->execute([
            $nom,
            $prenom,
            $tel,
            $adresse,
            $u['id']
        ]);

        // Mettre à jour le prénom dans la session

        $_SESSION['prenom'] = $prenom;

        // Rediriger après l'enregistrement du profil

        header('Location: mon-profil.php?ok=1');
        exit;
    }
}

// Affichage de la page

debutPage('Mon profil');

?>

<!-- Affichage du message d'erreur -->

<?php if ($erreur): ?>

    <p class="message-erreur">
        <?= ech($erreur) ?>
    </p>

<?php endif; ?>

<!-- Affichage du message de confirmation -->

<?php if (isset($_GET['ok']) && !$erreur): ?>

    <p class="message-succes">
        Votre profil a bien été enregistré.
    </p>

<?php endif; ?>

<!-- Formulaire de modification du profil -->

<form method="post" class="formulaire">

    <?php jeton(); ?>

    <?php

    // Définir les champs modifiables du profil

    $champs = [

        'nom' => 'Nom',
        'prenom' => 'Prénom',
        'telephone' => 'Téléphone',
        'adresse' => 'Adresse'

    ];

    ?>

    <!-- Affichage des informations modifiables -->

    <?php foreach ($champs as $k => $label): ?>

        <label for="<?= ech($k) ?>">
            <?= ech($label) ?>
        </label>

        <input
            id="<?= ech($k) ?>"
            name="<?= ech($k) ?>"
            value="<?= ech($u[$k]) ?>"
            required
        >

    <?php endforeach; ?>

    <!-- Adresse e-mail non modifiable -->

    <label for="email">
        E-mail (identifiant)
    </label>

    <input
        id="email"
        type="email"
        value="<?= ech($u['email']) ?>"
        readonly
    >

    <button>
        Enregistrer
    </button>

</form>

<?php

// Afficher la fin de la page

finPage();