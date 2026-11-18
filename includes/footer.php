<?php

require_once __DIR__ . '/../config/database.php';

// Récupération des horaires d'ouverture
$stmt = $pdo->query("
    SELECT *
    FROM horaires
    ORDER BY FIELD(
        LOWER(jour),
        'lundi',
        'mardi',
        'mercredi',
        'jeudi',
        'vendredi',
        'samedi',
        'dimanche'
    )
");

$horaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<footer class="pied-page">

    <div class="conteneur contenu-footer">

        <section>

            <h2>Vite & Gourmand</h2>

            <p>
                Votre traiteur pour tous vos événements.
            </p>

        </section>

        <section>

            <h2>Horaires d'ouverture</h2>

            <?php if (empty($horaires)): ?>

                <p>Horaires non disponibles.</p>

            <?php else: ?>

                <ul class="liste-horaires">

                    <?php foreach ($horaires as $horaire): ?>

                        <li>

                            <?= htmlspecialchars($horaire['jour']) ?> :

                            <?php if ($horaire['ferme']): ?>

                                Fermé

                            <?php else: ?>

                                <?= htmlspecialchars(substr($horaire['heure_ouverture'], 0, 5)) ?>

                                -

                                <?= htmlspecialchars(substr($horaire['heure_fermeture'], 0, 5)) ?>

                            <?php endif; ?>

                        </li>

                    <?php endforeach; ?>

                </ul>

            <?php endif; ?>

        </section>

    </div>

    <p class="copyright">
        &copy; <?= date('Y') ?> Vite & Gourmand.
        Tous droits réservés.
    </p>

</footer>