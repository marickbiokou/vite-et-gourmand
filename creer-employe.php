<?php

require_once __DIR__ . '/includes/espace-commun.php';
require_once __DIR__ . '/includes/envoyer-mail.php';

// Vérification des droits administrateur

$admin = compteActif(['admin']);

$erreur = '';
$succes = '';

$nom = '';
$prenom = '';
$email = '';


// Traitement du formulaire

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifierJeton();

    $nom = champ('nom');
    $prenom = champ('prenom');
    $email = champ('email');

    $motDePasse = $_POST['mot_de_passe'] ?? '';

    // Vérification des informations

    if ($nom === '' || $prenom === '' || $email === '') {

        $erreur = 'Veuillez remplir tous les champs.';

    } elseif (strlen($nom) > 100 || strlen($prenom) > 100) {

        $erreur = 'Le nom ou le prénom est trop long.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($email) > 255) {

        $erreur = 'Adresse email invalide.';

    } elseif (!is_string($motDePasse)
        || strlen($motDePasse) < 10
        || strlen($motDePasse) > 72
        || !preg_match('/[A-Z]/', $motDePasse)
        || !preg_match('/[a-z]/', $motDePasse)
        || !preg_match('/[0-9]/', $motDePasse)
        || !preg_match('/[^a-zA-Z0-9]/', $motDePasse)) {

        $erreur = 'Le mot de passe doit contenir au moins 10 caractères, '
                . 'une majuscule, une minuscule, un chiffre '
                . 'et un caractère spécial.';

    } else {

        // Vérification de l'adresse email

        $stmt = $pdo->prepare(
            'SELECT id FROM utilisateurs WHERE email = ?'
        );

        $stmt->execute([$email]);

        if ($stmt->fetch()) {

            $erreur = 'Cette adresse email est déjà utilisée.';

        } else {

            // Hachage du mot de passe

            $motDePasseHash = password_hash(
                $motDePasse,
                PASSWORD_DEFAULT
            );

            // Création du compte employé

            $sql = "
                INSERT INTO utilisateurs (
                    nom,
                    prenom,
                    email,
                    mot_de_passe,
                    role,
                    email_verifie,
                    actif
                )
                VALUES (?, ?, ?, ?, 'employe', 1, 1)
            ";

            $stmt = $pdo->prepare($sql);

            try {

                $stmt->execute([
                    $nom,
                    $prenom,
                    $email,
                    $motDePasseHash
                ]);

                $succes = 'Le compte employé a été créé.';

                // Informer l'employé sans transmettre son mot de passe
                $message = "Bonjour $prenom,\n\n"
                    . "Votre compte employé Vite & Gourmand a été créé.\n"
                    . "Contactez votre administrateur pour vos accès.\n\n"
                    . "L’équipe Vite & Gourmand";

                if (!envoyerMail($email, 'Création de votre compte employé', $message)) {
                    $succes .= ' La notification n’a pas pu être envoyée.';
                }

                $nom = '';
                $prenom = '';
                $email = '';

            } catch (PDOException $e) {

                $erreur = 'Impossible de créer le compte employé.';

            }
        }
    }
}

debutPage('Créer un employé');

?>

<a href="gestion-employes.php" class="lien-retour">
    Retour à la gestion des employés
</a>

<?php if ($erreur !== ''): ?>

    <p class="message-erreur">
        <?= ech($erreur) ?>
    </p>

<?php endif; ?>

<?php if ($succes !== ''): ?>

    <p class="message-succes">
        <?= ech($succes) ?>
    </p>

<?php endif; ?>

<form method="post" class="formulaire">

    <?php jeton(); ?>

    <label for="nom">Nom</label>

    <input type="text"
           id="nom"
           name="nom"
           maxlength="100"
           value="<?= ech($nom) ?>"
           required>

    <label for="prenom">Prénom</label>

    <input type="text"
           id="prenom"
           name="prenom"
           maxlength="100"
           value="<?= ech($prenom) ?>"
           required>

    <label for="email">Adresse email</label>

    <input type="email"
           id="email"
           name="email"
           maxlength="255"
           value="<?= ech($email) ?>"
           required>

    <label for="mot_de_passe">Mot de passe</label>

    <input type="password"
           id="mot_de_passe"
           name="mot_de_passe"
           minlength="10"
           maxlength="72"
           autocomplete="new-password"
           required>

    <p>
        Minimum 10 caractères, une majuscule, une minuscule,
        un chiffre et un caractère spécial.
    </p>

    <button type="submit">
        Créer le compte employé
    </button>

</form>

<?php

finPage();