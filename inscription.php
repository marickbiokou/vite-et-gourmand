
<?php

session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/envoyer-mail.php';

$erreur = '';
$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!is_string($_POST['csrf_token'] ?? null) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        exit('Formulaire invalide.');
    }

    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';

    // Vérifier les champs obligatoires
    if (
        $nom === '' ||
        $prenom === '' ||
        $telephone === '' ||
        $email === '' ||
        $adresse === '' ||
        $motDePasse === ''
    ) {
        $erreur = 'Tous les champs sont obligatoires.';

    } elseif (
        strlen($nom) > 100 ||
        strlen($prenom) > 100 ||
        strlen($telephone) > 20 ||
        strlen($email) > 255 ||
        strlen($adresse) > 255
    ) {
        $erreur = 'Un ou plusieurs champs sont trop longs.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erreur = 'Adresse email invalide.';

    } elseif (
        !is_string($motDePasse) ||
        strlen($motDePasse) < 10 ||
        strlen($motDePasse) > 72 ||
        !preg_match('/[A-Z]/', $motDePasse) ||
        !preg_match('/[a-z]/', $motDePasse) ||
        !preg_match('/[0-9]/', $motDePasse) ||
        !preg_match('/[^a-zA-Z0-9]/', $motDePasse)
    ) {
        $erreur = 'Le mot de passe ne respecte pas les critères.';

    } else {

        // Vérifier si l'adresse email existe déjà
        $sql = "SELECT id FROM utilisateurs WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $email]);

        if ($stmt->fetch()) {
            $erreur = 'Cette adresse email est déjà utilisée.';

        } else {

            // Hacher le mot de passe
            $motDePasseHash = password_hash(
                $motDePasse,
                PASSWORD_DEFAULT
            );

            // Enregistrer le nouveau client
            $sql = "INSERT INTO utilisateurs
                    (nom, prenom, telephone, email,
                     adresse, mot_de_passe, role)
                    VALUES
                    (:nom, :prenom, :telephone, :email,
                     :adresse, :mot_de_passe, 'client')";

            $stmt = $pdo->prepare($sql);

            $stmt->execute([
                'nom' => $nom,
                'prenom' => $prenom,
                'telephone' => $telephone,
                'email' => $email,
                'adresse' => $adresse,
                'mot_de_passe' => $motDePasseHash
            ]);

            $succes = 'Votre compte a été créé avec succès.';

            // Envoyer le message de bienvenue après la création du compte
            $message = "Bonjour $prenom,\n\n"
                . "Bienvenue chez Vite & Gourmand !\n"
                . "Votre compte client est créé.\n\n"
                . "L’équipe Vite & Gourmand";

            if (!envoyerMail($email, 'Bienvenue chez Vite & Gourmand', $message)) {
                $succes .= ' L’e-mail de bienvenue n’a pas pu être envoyé.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">
    <title>Inscription - Vite & Gourmand</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<main class="conteneur">

    <h1>Créer un compte</h1>

    <?php if ($erreur !== ''): ?>
        <p class="message-erreur" role="alert">
            <?= htmlspecialchars($erreur) ?>
        </p>
    <?php endif; ?>

    <?php if ($succes !== ''): ?>
        <p class="message-succes" role="status">
            <?= htmlspecialchars($succes) ?>
        </p>
    <?php endif; ?>


    <form action="inscription.php" method="POST"
          class="formulaire">

        <input type="hidden" name="csrf_token"
               value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

        <label for="nom">Nom</label>
        <input type="text" id="nom" name="nom"
               maxlength="100" autocomplete="family-name"
               required>

        <label for="prenom">Prénom</label>
        <input type="text" id="prenom" name="prenom"
               maxlength="100" autocomplete="given-name"
               required>

        <label for="telephone">Téléphone</label>
        <input type="tel" id="telephone" name="telephone"
               maxlength="20" autocomplete="tel" required>

        <label for="email">Adresse email</label>
        <input type="email" id="email" name="email"
               maxlength="255" autocomplete="email" required>

        <label for="adresse">Adresse postale</label>
        <input type="text" id="adresse" name="adresse"
               maxlength="255" autocomplete="street-address"
               required>

        <label for="mot_de_passe">Mot de passe</label>
        <input type="password" id="mot_de_passe"
               name="mot_de_passe"
               minlength="10"
               autocomplete="new-password" required>

        <p class="aide-formulaire">
            Minimum 10 caractères, avec une majuscule,
            une minuscule, un chiffre et un caractère spécial.
        </p>

        <button type="submit" class="bouton-detail">
            Créer mon compte
        </button>

    </form>

    <p>Déjà inscrit ? <a href="connexion.php">Se connecter</a></p>

</main>

</body>
</html>
