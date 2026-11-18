<?php

require_once __DIR__ . '/config/database.php';

// Récupérer les avis validés par un employé ou un administrateur

$sql = "SELECT a.note, a.commentaire, u.prenom
        FROM avis a
        JOIN commandes c ON c.id = a.commande_id
        JOIN utilisateurs u ON u.id = c.utilisateur_id
        WHERE a.valide = 1
        ORDER BY a.id DESC";

$avisPublics = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

?>

<section>

    <h2>Les avis de nos clients</h2>

    <?php if (!$avisPublics): ?>

        <p>Aucun avis publié pour le moment.</p>

    <?php endif; ?>

    <?php foreach ($avisPublics as $a): ?>

        <article class="carte-menu">

            <h3>
                <?= htmlspecialchars($a['prenom'], ENT_QUOTES, 'UTF-8') ?>
                — <?= (int) $a['note'] ?>/5
            </h3>

            <p>
                <?= htmlspecialchars($a['commentaire'], ENT_QUOTES, 'UTF-8') ?>
            </p>

        </article>

    <?php endforeach; ?>

</section>