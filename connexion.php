
<?php

session_start();

require_once __DIR__ . '/config/database.php';

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';

    if ($email === '' || $motDePasse === '') {
        $erreur = 'Tous les champs sont obligatoires.';

    } else {

        $sql = "SELECT id, nom, prenom, email,
                       mot_de_passe, role, actif
                FROM utilisateurs
                WHERE email = :email";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(['email' => $email]);

        $utilisateur = $stmt->fetch();

        if (
            !$utilisateur ||
            (int) $utilisateur['actif'] !== 1 ||
            !password_verify(
                $motDePasse,
                $utilisateur['mot_de_passe']
            )
        ) {
            $erreur = 'Email ou mot de passe incorrect.';

        } else {

            // Renouveler l'identifiant de session
            session_regenerate_id(true);

            // Enregistrer les informations utiles
            $_SESSION['utilisateur_id'] = $utilisateur['id'];
            $_SESSION['prenom'] = $utilisateur['prenom'];
            $_SESSION['role'] = $utilisateur['role'];

            header('Location: menus.php');
            exit;
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
    <title>Connexion - Vite & Gourmand</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<main class="conteneur">

    <h1>Se connecter</h1>

    <?php if ($erreur !== ''): ?>
        <p class="message-erreur" role="alert">
            <?= htmlspecialchars($erreur) ?>
        </p>
    <?php endif; ?>

    <form action="connexion.php" method="POST"
          class="formulaire">

        <label for="email">Adresse email</label>
        <input type="email" id="email" name="email"
               autocomplete="email" required>

        <label for="mot_de_passe">Mot de passe</label>
        <input type="password"
               id="mot_de_passe"
               name="mot_de_passe"
               autocomplete="current-password" required>

        <button type="submit" class="bouton-detail">
            Se connecter
        </button>

    </form>

    <p>
        Pas encore de compte ?
        <a href="inscription.php">Créer un compte</a>
    </p>

</main>

</body>
</html>
