<?php

require_once __DIR__ . '/includes/espace-commun.php';

// Vérification des droits administrateur

$admin = compteActif(['admin']);


// Activation ou désactivation d'un employé

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifierJeton();

    $id = filter_input(
        INPUT_POST,
        'employe_id',
        FILTER_VALIDATE_INT
    );

    $actif = $_POST['actif'] ?? '';

    if (!$id || $id < 1 || !in_array($actif, ['0', '1'], true)) {
        exit('Données invalides.');
    }

    $sql = "
        UPDATE utilisateurs
        SET actif = ?
        WHERE id = ?
        AND role = 'employe'
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([$actif, $id]);

    header('Location: gestion-employes.php?modification=ok');
    exit;
}


// Récupération des employés

$sql = "
    SELECT id, nom, prenom, email, actif
    FROM utilisateurs
    WHERE role = 'employe'
    ORDER BY nom, prenom
";

$stmt = $pdo->query($sql);

$employes = $stmt->fetchAll(PDO::FETCH_ASSOC);

debutPage('Gestion des employés');

?>

<a href="espace-admin.php" class="lien-retour">
    Retour à l'espace administrateur
</a>

<?php if (($_GET['modification'] ?? '') === 'ok'): ?>

    <p class="message-succes">
        Le compte employé a été mis à jour.
    </p>

<?php endif; ?>

<p>
    Consultez et gérez les comptes de vos employés.
</p>

<a href="creer-employe.php" class="bouton-detail">
    + Créer un employé
</a>

<div class="table-responsive">

    <table class="table-commandes">

        <thead>

            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Email</th>
                <th>État</th>
                <th>Actions</th>
            </tr>

        </thead>

        <tbody>

            <?php foreach ($employes as $employe): ?>

                <tr>

                    <td><?= ech($employe['nom']) ?></td>

                    <td><?= ech($employe['prenom']) ?></td>

                    <td><?= ech($employe['email']) ?></td>

                    <td>

                        <?php if ($employe['actif']): ?>

                            <span class="message-succes">
                                Actif
                            </span>

                        <?php else: ?>

                            <span class="message-erreur">
                                Désactivé
                            </span>

                        <?php endif; ?>

                    </td>

                    <td>

                        <form method="post">

                            <?php jeton(); ?>

                            <input type="hidden"
                                   name="employe_id"
                                   value="<?= (int) $employe['id'] ?>">

                            <?php if ($employe['actif']): ?>

                                <button type="submit"
                                        name="actif"
                                        value="0">
                                    Désactiver
                                </button>

                            <?php else: ?>

                                <button type="submit"
                                        name="actif"
                                        value="1">
                                    Réactiver
                                </button>

                            <?php endif; ?>

                        </form>

                    </td>

                </tr>

            <?php endforeach; ?>

        </tbody>

    </table>

</div>

<?php

finPage();