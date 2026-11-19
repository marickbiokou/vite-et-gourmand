<?php

require_once __DIR__ . '/includes/espace-commun.php';

// Générer un jeton de protection CSRF

if (empty($_SESSION['csrf_token'])) {

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$erreur = '';
$succes = '';

$token = $_GET['token'] ?? '';

// Vérifier le format du jeton

if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {

    $token = '';
}

$tokenHash = hash('sha256', $token);

// Rechercher un jeton valide et non expiré

$sql = "SELECT id
        FROM utilisateurs
        WHERE reset_token = ?
        AND reset_expiration > NOW()
        AND actif = 1";

$s = $pdo->prepare($sql);
$s->execute([$tokenHash]);

$utilisateur = $s->fetch(PDO::FETCH_ASSOC);

// Traitement du formulaire

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $utilisateur) {

    verifierJeton();

    $motDePasse = $_POST['mot_de_passe'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    // Vérifier la sécurité du nouveau mot de passe

    if (
        !is_string($motDePasse) ||
        strlen($motDePasse) < 10 ||
        strlen($motDePasse) > 72 ||
        !preg_match('/[A-Z]/', $motDePasse) ||
        !preg_match('/[a-z]/', $motDePasse) ||
        !preg_match('/[0-9]/', $motDePasse) ||
        !preg_match('/[^a-zA-Z0-9\s]/', $motDePasse)
    ) {

        $erreur = 'Le mot de passe doit respecter les conditions de sécurité.';

    } elseif ($motDePasse !== $confirmation) {

        $erreur = 'Les mots de passe ne correspondent pas.';

    } else {

        // Hacher le nouveau mot de passe

        $motDePasseHash = password_hash($motDePasse, PASSWORD_DEFAULT);

        // Enregistrer le mot de passe et supprimer le jeton

        $sql = "UPDATE utilisateurs
                SET mot_de_passe = ?,
                    reset_token = NULL,
                    reset_expiration = NULL
                WHERE id = ?
                AND reset_token = ?
                AND reset_expiration > NOW()
                AND actif = 1";

        $s = $pdo->prepare($sql);

        $s->execute([
            $motDePasseHash,
            $utilisateur['id'],
            $tokenHash
        ]);

        if ($s->rowCount() === 1) {

            $succes = 'Votre mot de passe a bien été modifié.';

        } else {

            $erreur = 'Ce lien est invalide ou expiré.';
        }

        $utilisateur = false;
    }
}

// Éviter la transmission du lien à des sites externes

header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');

// Affichage de la page

debutPage('Réinitialiser mon mot de passe');

?>

<?php if ($erreur): ?>

    <p class="message-erreur">
        <?= ech($erreur) ?>
    </p>

<?php endif; ?>

<?php if ($succes): ?>

    <p class="message-succes">
        <?= ech($succes) ?>
    </p>

    <a class="bouton-detail" href="connexion.php">
        Se connecter
    </a>

<?php elseif (!$utilisateur): ?>

    <p class="message-erreur">
        Ce lien est invalide ou expiré.
    </p>

    <a class="bouton-detail" href="mot-de-passe-oublie.php">
        Demander un nouveau lien
    </a>

<?php else: ?>

    <form method="post" class="formulaire">

        <?php jeton(); ?>

        <p>
            Votre mot de passe doit contenir au minimum 10 caractères,
            une majuscule, une minuscule, un chiffre et un caractère spécial.
        </p>

        <label for="mot_de_passe">
            Nouveau mot de passe
        </label>

        <input
            type="password"
            id="mot_de_passe"
            name="mot_de_passe"
            autocomplete="new-password"
            minlength="10"
            maxlength="72"
            required
        >

        <label for="confirmation">
            Confirmer le mot de passe
        </label>

        <input
            type="password"
            id="confirmation"
            name="confirmation"
            autocomplete="new-password"
            required
        >

        <button type="submit">
            Modifier mon mot de passe
        </button>

    </form>

<?php endif; ?>

<?php

finPage();