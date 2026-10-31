
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
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <h1><?= htmlspecialchars($nomEntreprise) ?></h1>
    <p><?= htmlspecialchars($message) ?></p>

    <script src="assets/js/main.js"></script>
</body>
</html>
