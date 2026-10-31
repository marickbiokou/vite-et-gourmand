
<?php
$nomEntreprise = "Vite & Gourmand";
$message = "Bienvenue sur notre site de restauration !";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($nomEntreprise) ?></title>
</head>
<body>
    <h1><?= htmlspecialchars($nomEntreprise) ?></h1>
    <p><?= htmlspecialchars($message) ?></p>
</body>
</html>
