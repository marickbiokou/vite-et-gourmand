<?php

require_once __DIR__ . '/includes/espace-commun.php';
require_once __DIR__ . '/includes/envoyer-mail.php';

// Initialisation du jeton de sécurité

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialisation des variables

$nom = '';
$email = '';
$sujet = '';
$message = '';
$erreur = '';

$succes = $_SESSION['contact_succes'] ?? '';

unset($_SESSION['contact_succes']);

// Traitement du formulaire

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifierJeton();

    $nom = champ('nom');
    $email = champ('email');
    $sujet = champ('sujet');
    $message = champ('message');

    // Vérification des champs

    if (
        $nom === '' ||
        $email === '' ||
        $sujet === '' ||
        $message === ''
    ) {

        $erreur = 'Veuillez compléter tous les champs.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $erreur = 'Veuillez saisir une adresse e-mail valide.';

    } elseif (
        mb_strlen($nom) > 100 ||
        mb_strlen($email) > 255 ||
        mb_strlen($sujet) > 150 ||
        mb_strlen($message) > 5000 ||
        preg_match('/[\r\n]/', $sujet)
    ) {

        $erreur = 'Un ou plusieurs champs sont trop longs ou invalides.';

    } elseif (
        time() - ($_SESSION['dernier_contact'] ?? 0) < 30
    ) {

        $erreur = 'Veuillez patienter avant un nouvel envoi.';

    } else {

        // Chargement de la configuration

        $config = require __DIR__ . '/config/mail.php';

        // Préparation du contenu de l'e-mail

        $contenu = "Nouveau message de contact\n\n";

        $contenu .= "Nom : " . $nom . "\n";
        $contenu .= "E-mail : " . $email . "\n";
        $contenu .= "Sujet : " . $sujet . "\n\n";
        $contenu .= "Message :\n" . $message;

        // Limitation des envois successifs

        $_SESSION['dernier_contact'] = time();

        // Envoi de l'e-mail

        $resultat = envoyerMail(
            $config['contact_email'],
            'Contact - ' . $sujet,
            $contenu,
            $email
        );

        if ($resultat) {

            $_SESSION['contact_succes'] =
                'Votre message a bien été envoyé.';

            header('Location: contact.php');
            exit;

        } else {

            $erreur = 'Impossible d’envoyer votre message. '
                . 'Veuillez réessayer ultérieurement.';
        }
    }
}

// Affichage de la page

debutPage('Contact');

?>

<p>
    Une question concernant nos menus ou nos prestations ?
    N'hésitez pas à nous contacter.
</p>

<?php if ($erreur !== ''): ?>

    <p class="message-erreur" role="alert">
        <?= ech($erreur) ?>
    </p>

<?php endif; ?>

<?php if ($succes !== ''): ?>

    <p class="message-succes" role="status">
        <?= ech($succes) ?>
    </p>

<?php endif; ?>

<form method="POST" class="formulaire">

    <?php jeton(); ?>

    <label for="nom">Nom</label>

    <input
        type="text"
        id="nom"
        name="nom"
        maxlength="100"
        value="<?= ech($nom) ?>"
        required
    >

    <label for="email">Adresse e-mail</label>

    <input
        type="email"
        id="email"
        name="email"
        maxlength="255"
        value="<?= ech($email) ?>"
        required
    >

    <label for="sujet">Sujet</label>

    <input
        type="text"
        id="sujet"
        name="sujet"
        maxlength="150"
        value="<?= ech($sujet) ?>"
        required
    >

    <label for="message">Message</label>

    <textarea
        id="message"
        name="message"
        rows="6"
        maxlength="5000"
        required
    ><?= ech($message) ?></textarea>

    <button type="submit" class="bouton-detail">
        Envoyer le message
    </button>

</form>

<?php finPage(); ?>