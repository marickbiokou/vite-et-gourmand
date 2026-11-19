-- Donnees fictives de demonstration locale. Ne pas importer en production.
-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : jeu. 19 nov. 2026 à 19:51
-- Version du serveur : 10.11.16-MariaDB
-- Version de PHP : 8.4.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `vite_et_gourmand`
--

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

CREATE TABLE `avis` (
  `id` int(10) UNSIGNED NOT NULL,
  `commande_id` int(10) UNSIGNED NOT NULL,
  `note` tinyint(3) UNSIGNED NOT NULL,
  `commentaire` text DEFAULT NULL,
  `valide` tinyint(1) NOT NULL DEFAULT 0,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `avis`
--

INSERT INTO `avis` (`id`, `commande_id`, `note`, `commentaire`, `valide`, `date_creation`) VALUES
(1, 3, 5, 'Excellent service, repas de qualité et livraison impeccable.', 1, '2026-11-18 20:37:20'),
(2, 6, 1, 'Commande rapise et surtout super bon. Merci à l\'équipe', 0, '2026-11-19 19:11:58');

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

CREATE TABLE `commandes` (
  `id` int(10) UNSIGNED NOT NULL,
  `utilisateur_id` int(10) UNSIGNED NOT NULL,
  `menu_id` int(10) UNSIGNED NOT NULL,
  `nombre_personnes` int(10) UNSIGNED NOT NULL,
  `date_prestation` date NOT NULL,
  `adresse_livraison` varchar(255) NOT NULL,
  `prix_total` decimal(10,2) NOT NULL,
  `statut` enum('en_attente','acceptee','en_preparation','en_livraison','livree','en_attente_retour_materiel','terminee','annulee') NOT NULL DEFAULT 'en_attente',
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `heure_livraison` time DEFAULT NULL,
  `ville_livraison` varchar(100) DEFAULT NULL,
  `distance_km` decimal(8,2) DEFAULT NULL,
  `frais_livraison` decimal(10,2) NOT NULL DEFAULT 0.00,
  `mode_contact` varchar(20) DEFAULT NULL,
  `motif_annulation` text DEFAULT NULL,
  `date_contact` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id`, `utilisateur_id`, `menu_id`, `nombre_personnes`, `date_prestation`, `adresse_livraison`, `prix_total`, `statut`, `date_creation`, `heure_livraison`, `ville_livraison`, `distance_km`, `frais_livraison`, `mode_contact`, `motif_annulation`, `date_contact`) VALUES
(1, 1, 1, 10, '2026-11-17', 'Adresse fictive 1', 180.00, 'annulee', '2026-11-15 18:22:35', '15:00:00', 'bordeaux', 0.00, 0.00, 'telephone', 'test d\'annulation de la commande 1\r\nAnnulation demandée par le client', '2026-11-17 21:11:35'),
(2, 1, 1, 15, '2026-11-17', 'Adresse fictive 1', 243.00, 'en_attente_retour_materiel', '2026-11-15 18:31:14', '10:00:00', 'bordeaux', 0.00, 0.00, NULL, NULL, NULL),
(3, 1, 1, 10, '2026-11-18', 'Adresse fictive 1', 205.65, 'terminee', '2026-11-15 18:42:41', '20:00:00', 'libourne', 35.00, 25.65, NULL, NULL, NULL),
(4, 1, 1, 15, '2026-11-18', 'Adresse fictive 1', 243.00, 'annulee', '2026-11-15 19:08:02', '19:09:00', 'bordeaux', 0.00, 0.00, NULL, NULL, NULL),
(5, 1, 3, 15, '2026-11-18', 'Adresse fictive 1', 300.00, 'en_attente', '2026-11-17 21:58:39', '23:00:00', 'bordeaux', 0.00, 0.00, NULL, NULL, NULL),
(6, 2, 3, 15, '2026-11-19', 'Adresse fictive 2', 310.90, 'terminee', '2026-11-18 20:32:38', '22:34:00', 'merignac', 10.00, 10.90, NULL, NULL, NULL),
(7, 2, 1, 15, '2026-11-27', 'Adresse fictive 2', 243.00, 'terminee', '2026-11-18 20:43:20', '22:45:00', 'bordeaux', 0.00, 0.00, 'telephone', NULL, '2026-11-18 20:45:17'),
(8, 7, 3, 15, '2026-11-20', 'Adresse fictive 7', 300.00, 'terminee', '2026-11-18 23:25:44', '20:00:00', 'bordeaux', 0.00, 0.00, NULL, NULL, NULL),
(9, 2, 3, 15, '2026-11-20', 'Adresse fictive 2', 300.00, 'en_attente_retour_materiel', '2026-11-19 19:15:48', '20:00:00', 'bordeaux', 0.00, 0.00, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `historique_statuts`
--

CREATE TABLE `historique_statuts` (
  `id` int(10) UNSIGNED NOT NULL,
  `commande_id` int(10) UNSIGNED NOT NULL,
  `statut` varchar(50) NOT NULL,
  `date_modification` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `historique_statuts`
--

INSERT INTO `historique_statuts` (`id`, `commande_id`, `statut`, `date_modification`) VALUES
(1, 3, 'en_attente', '2026-11-15 18:42:41'),
(2, 4, 'en_attente', '2026-11-15 19:08:02'),
(3, 4, 'annulee', '2026-11-16 23:23:11'),
(4, 3, 'acceptee', '2026-11-17 20:56:41'),
(5, 3, 'en_preparation', '2026-11-17 20:58:04'),
(6, 3, 'en_livraison', '2026-11-17 20:58:06'),
(7, 3, 'livree', '2026-11-17 20:58:08'),
(8, 3, 'terminee', '2026-11-17 20:58:10'),
(9, 2, 'acceptee', '2026-11-17 21:01:19'),
(10, 2, 'en_preparation', '2026-11-17 21:01:22'),
(11, 2, 'en_livraison', '2026-11-17 21:01:24'),
(12, 2, 'livree', '2026-11-17 21:01:29'),
(13, 2, 'en_attente_retour_materiel', '2026-11-17 21:01:39'),
(14, 1, 'annulee', '2026-11-17 21:11:35'),
(15, 5, 'en_attente', '2026-11-17 21:58:39'),
(16, 6, 'en_attente', '2026-11-18 20:32:38'),
(17, 7, 'en_attente', '2026-11-18 20:43:20'),
(18, 7, 'acceptee', '2026-11-18 21:17:14'),
(19, 7, 'en_preparation', '2026-11-18 21:17:20'),
(20, 8, 'en_attente', '2026-11-18 23:25:44'),
(21, 8, 'acceptee', '2026-11-18 23:27:54'),
(22, 8, 'en_preparation', '2026-11-18 23:28:59'),
(23, 8, 'en_livraison', '2026-11-18 23:30:29'),
(24, 8, 'livree', '2026-11-18 23:31:36'),
(25, 8, 'terminee', '2026-11-18 23:31:38'),
(26, 7, 'en_livraison', '2026-11-18 23:31:51'),
(27, 7, 'livree', '2026-11-18 23:31:56'),
(28, 7, 'en_attente_retour_materiel', '2026-11-18 23:31:59'),
(29, 7, 'terminee', '2026-11-18 23:32:22'),
(30, 6, 'acceptee', '2026-11-19 19:09:42'),
(31, 6, 'en_preparation', '2026-11-19 19:09:44'),
(32, 6, 'en_livraison', '2026-11-19 19:09:45'),
(33, 6, 'livree', '2026-11-19 19:09:46'),
(34, 6, 'terminee', '2026-11-19 19:09:48'),
(35, 9, 'en_attente', '2026-11-19 19:15:48'),
(36, 9, 'acceptee', '2026-11-19 19:19:00'),
(37, 9, 'en_preparation', '2026-11-19 19:19:02'),
(38, 9, 'en_livraison', '2026-11-19 19:19:03'),
(39, 9, 'livree', '2026-11-19 19:19:04'),
(40, 9, 'en_attente_retour_materiel', '2026-11-19 19:19:06');

-- --------------------------------------------------------

--
-- Structure de la table `horaires`
--

CREATE TABLE `horaires` (
  `id` int(10) UNSIGNED NOT NULL,
  `jour` varchar(20) NOT NULL,
  `heure_ouverture` time DEFAULT NULL,
  `heure_fermeture` time DEFAULT NULL,
  `ferme` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `horaires`
--

INSERT INTO `horaires` (`id`, `jour`, `heure_ouverture`, `heure_fermeture`, `ferme`) VALUES
(1, 'Lundi', '08:00:00', '19:00:00', 0),
(2, 'Mardi', '09:00:00', '18:00:00', 0),
(3, 'Mercredi', '09:00:00', '18:00:00', 0),
(4, 'Jeudi', '09:00:00', '18:00:00', 0),
(5, 'Vendredi', '09:00:00', '18:00:00', 0),
(6, 'Samedi', NULL, NULL, 1),
(7, 'Dimanche', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Structure de la table `menus`
--

CREATE TABLE `menus` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `prix_base` decimal(10,2) NOT NULL,
  `nombre_personnes_min` int(10) UNSIGNED NOT NULL,
  `stock` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `theme` varchar(100) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `regime` varchar(50) DEFAULT 'classique',
  `conditions` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `menus`
--

INSERT INTO `menus` (`id`, `nom`, `description`, `prix_base`, `nombre_personnes_min`, `stock`, `image`, `theme`, `actif`, `regime`, `conditions`) VALUES
(1, 'Menu Tradition', 'Un repas convivial aux saveurs traditionnelles.', 180.00, 10, 18, NULL, 'Traditionnel', 1, 'classique', 'Commande au moins 48 heures à l avance.'),
(2, 'Menu Végétarien', 'Une sélection de plats savoureux sans viande.', 160.00, 10, 15, NULL, 'Nature', 1, 'vegetarien', 'Commande au moins 48 heures à l avance.'),
(3, 'Menu Fêtes', 'Un menu généreux pour vos événements festifs.', 300.00, 15, 8, NULL, 'Festif', 1, 'classique', 'Commande au moins 72 heures à l avance.'),
(4, 'Menu Test', 'Menu de test pour la gestion du catalogue.', 150.00, 10, 5, NULL, 'Traditionnel', 0, 'classique', 'Commande 48 heures à l\'avance.');

-- --------------------------------------------------------

--
-- Structure de la table `menu_images`
--

CREATE TABLE `menu_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `menu_id` int(10) UNSIGNED NOT NULL,
  `chemin` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `menu_images`
--

INSERT INTO `menu_images` (`id`, `menu_id`, `chemin`) VALUES
(1, 1, 'assets/images/menus/menu-tradition.jpg'),
(2, 2, 'assets/images/menus/menu-vegetarien.jpg'),
(3, 3, 'assets/images/menus/menu-fetes.jpg'),
(4, 4, 'assets/images/uploads/18a92d0dd56d29660d79d4c57fb2a225.jpg');

-- --------------------------------------------------------

--
-- Structure de la table `menu_plats`
--

CREATE TABLE `menu_plats` (
  `menu_id` int(10) UNSIGNED NOT NULL,
  `plat_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `menu_plats`
--

INSERT INTO `menu_plats` (`menu_id`, `plat_id`) VALUES
(1, 1),
(1, 3),
(1, 5),
(2, 2),
(2, 4),
(2, 6),
(3, 1),
(3, 4),
(3, 5),
(4, 1),
(4, 3),
(4, 5);

-- --------------------------------------------------------

--
-- Structure de la table `plats`
--

CREATE TABLE `plats` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `categorie` enum('entree','plat','dessert') NOT NULL,
  `allergenes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `plats`
--

INSERT INTO `plats` (`id`, `nom`, `description`, `categorie`, `allergenes`) VALUES
(1, 'Salade de chèvre chaud', 'Salade verte accompagnée de fromage de chèvre.', 'entree', 'Lait, gluten'),
(2, 'Velouté de légumes', 'Soupe onctueuse aux légumes de saison.', 'entree', 'Lait'),
(3, 'Poulet rôti aux herbes', 'Poulet accompagné de pommes de terre.', 'plat', 'Aucun allergène déclaré'),
(4, 'Risotto aux champignons', 'Riz crémeux aux champignons.', 'plat', 'Lait'),
(5, 'Mousse au chocolat', 'Mousse au chocolat noir.', 'dessert', 'Oeufs, lait'),
(6, 'Salade de fruits', 'Assortiment de fruits frais de saison.', 'dessert', 'Aucun allergène déclaré');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `role` enum('client','employe','admin') NOT NULL DEFAULT 'client',
  `email_verifie` tinyint(1) NOT NULL DEFAULT 0,
  `date_creation` datetime NOT NULL DEFAULT current_timestamp(),
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expiration` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `email`, `mot_de_passe`, `telephone`, `adresse`, `role`, `email_verifie`, `date_creation`, `actif`, `reset_token`, `reset_expiration`) VALUES
(1, 'Demo 1', 'Compte', 'client@example.com', '$2y$12$0592wxd0xTB1XAMFK3SxveJAE0TuhBjUC7z9jsWdK4XGPPpsD2n9O', NULL, 'Adresse fictive 1, Bordeaux', 'client', 1, '2026-11-15 12:00:00', 1, NULL, NULL),
(2, 'Demo 2', 'Compte', 'client2@example.com', '$2y$12$zNdaLim0cF93srWRh7TZJeZtwGpuZ8yC26U7TYIsa5pYfjW5RXCyu', NULL, 'Adresse fictive 2, Bordeaux', 'client', 1, '2026-11-15 12:00:00', 1, NULL, NULL),
(3, 'Demo 3', 'Compte', 'client3@example.com', '$2y$12$2Y08PurG.IhHjMN9l9FeeuXCgo9GNsAM1fjVrEbd6ZD.IN/Vhb9Zy', NULL, 'Adresse fictive 3, Bordeaux', 'client', 1, '2026-11-15 12:00:00', 1, NULL, NULL),
(4, 'Demo 4', 'Compte', 'employe@example.com', '$2y$12$zKwh7tGj4NTRypntrSp7wes8RrkbSRlvOVFHTTQRzgv8ha.jgnk4m', NULL, 'Adresse fictive 4, Bordeaux', 'employe', 1, '2026-11-15 12:00:00', 1, NULL, NULL),
(5, 'Demo 5', 'Compte', 'admin@example.com', '$2y$12$fAFuKlCxuKTKTZIZMvk9DegxNfGEWZpBJMv8zy3n3OXXyDuO6rSHa', NULL, 'Adresse fictive 5, Bordeaux', 'admin', 1, '2026-11-15 12:00:00', 1, NULL, NULL),
(6, 'Demo 6', 'Compte', 'employe6@example.com', '$2y$12$gU0u1H2sSWAtzkuMR9LA0eU0DAzRHCojSKoNsRTQb17qQrlEau/bi', NULL, 'Adresse fictive 6, Bordeaux', 'employe', 1, '2026-11-15 12:00:00', 1, NULL, NULL),
(7, 'Demo 7', 'Compte', 'client7@example.com', '$2y$12$YdB2BHzCnqfhm2MVQc9UI.94lm.Fw/bC6yllEtCkva.bMFkSizoou', NULL, 'Adresse fictive 7, Bordeaux', 'client', 1, '2026-11-15 12:00:00', 1, NULL, NULL);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `commande_id` (`commande_id`);

--
-- Index pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_commandes_utilisateur` (`utilisateur_id`),
  ADD KEY `fk_commandes_menu` (`menu_id`);

--
-- Index pour la table `historique_statuts`
--
ALTER TABLE `historique_statuts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `commande_id` (`commande_id`);

--
-- Index pour la table `horaires`
--
ALTER TABLE `horaires`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `jour` (`jour`);

--
-- Index pour la table `menus`
--
ALTER TABLE `menus`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `menu_images`
--
ALTER TABLE `menu_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `menu_id` (`menu_id`);

--
-- Index pour la table `menu_plats`
--
ALTER TABLE `menu_plats`
  ADD PRIMARY KEY (`menu_id`,`plat_id`),
  ADD KEY `plat_id` (`plat_id`);

--
-- Index pour la table `plats`
--
ALTER TABLE `plats`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `avis`
--
ALTER TABLE `avis`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `historique_statuts`
--
ALTER TABLE `historique_statuts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT pour la table `horaires`
--
ALTER TABLE `horaires`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `menus`
--
ALTER TABLE `menus`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `menu_images`
--
ALTER TABLE `menu_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `plats`
--
ALTER TABLE `plats`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `fk_avis_commande` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`);

--
-- Contraintes pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `fk_commandes_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`),
  ADD CONSTRAINT `fk_commandes_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`);

--
-- Contraintes pour la table `historique_statuts`
--
ALTER TABLE `historique_statuts`
  ADD CONSTRAINT `historique_statuts_ibfk_1` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `menu_images`
--
ALTER TABLE `menu_images`
  ADD CONSTRAINT `menu_images_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `menu_plats`
--
ALTER TABLE `menu_plats`
  ADD CONSTRAINT `menu_plats_ibfk_1` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `menu_plats_ibfk_2` FOREIGN KEY (`plat_id`) REFERENCES `plats` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
