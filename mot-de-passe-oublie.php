<?php

require_once __DIR__ . '/includes/espace-commun.php';
require_once __DIR__ . '/includes/envoyer-mail.php';

// Générer un jeton de protection CSRF

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$erreur = '';
$succes = '';

// Traitement du formulaire

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifierJeton();

    $email = champ('email');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {

        $erreur = 'Veuillez saisir une adresse e-mail valide.';

    } elseif (time() - ($_SESSION['dernier_reset'] ?? 0) < 60) {

        $erreur = 'Veuillez patienter une minute avant de réessayer.';

    } else {

        $_SESSION['dernier_reset'] = time();

        // Rechercher un compte actif

        $sql = "SELECT id, email
                FROM utilisateurs
                WHERE email = ?
                AND actif = 1";

        $s = $pdo->prepare($sql);
        $s->execute([$email]);

        $utilisateur = $s->fetch(PDO::FETCH_ASSOC);

        if ($utilisateur) {

            // Générer un jeton sécurisé

            $token = bin2hex(random_bytes(32));

            // Stocker uniquement son empreinte

            $tokenHash = hash('sha256', $token);

            $sql = "UPDATE utilisateurs
                    SET reset_token = ?,
                        reset_expiration = DATE_ADD(NOW(), INTERVAL 30 MINUTE)
                    WHERE id = ?";

            $s = $pdo->prepare($sql);

            $s->execute([
                $tokenHash,
                $utilisateur['id']
            ]);

            // Construire le lien de réinitialisation

            $url = 'http://localhost/vite-et-gourmand';

            $lien = $url . '/reinitialiser-mot-de-passe.php?token=' . $token;

            // Préparer et envoyer le message

            $sujet = 'Réinitialisation de votre mot de passe';

            $message = "Bonjour,\n\n";
            $message .= "Vous avez demandé la réinitialisation de votre mot de passe.\n\n";
            $message .= "Cliquez sur le lien suivant :\n";
            $message .= $lien . "\n\n";
            $message .= "Ce lien est valable pendant 30 minutes.\n\n";
            $message .= "Si vous n'avez pas effectué cette demande, ignorez ce message.\n\n";
            $message .= "L'équipe Vite & Gourmand.";

            envoyerMail($utilisateur['email'], $sujet, $message);
        }

        // Ne pas révéler l'existence d'un compte

        $succes = 'Si un compte correspond à cette adresse, un lien de réinitialisation vous sera envoyé.';
    }
}

debutPage('Mot de passe oublié');

?>

<a class="lien-retour" href="connexion.php">
    Retour à la connexion
</a>

<?php if ($erreur): ?>

    <p class="message-erreur">
        <?= ech($erreur) ?>
    </p>

<?php endif; ?>

<?php if ($succes): ?>

    <p class="message-succes">
        <?= ech($succes) ?>
    </p>

<?php endif; ?>

<form method="post" class="formulaire">

    <?php jeton(); ?>

    <p>
        Saisissez votre adresse e-mail pour réinitialiser votre mot de passe.
    </p>

    <label for="email">Adresse e-mail</label>

    <input
        type="email"
        id="email"
        name="email"
        maxlength="255"
        required
    >

    <button type="submit">
        Envoyer le lien
    </button>

</form>

<?php

finPage();