-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : jeu. 28 mai 2026 à 20:02
-- Version du serveur : 8.0.31
-- Version de PHP : 8.2.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `eglise_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `budgets`
--

DROP TABLE IF EXISTS `budgets`;
CREATE TABLE IF NOT EXISTS `budgets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `annee` year NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `statut` enum('Brouillon','Validé','Clôturé') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Brouillon',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_annee_unique` (`annee`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `budgets`
--

INSERT INTO `budgets` (`id`, `annee`, `description`, `statut`) VALUES
(1, '2026', 'Validé par le conseil pastoral', 'Validé');

-- --------------------------------------------------------

--
-- Structure de la table `comites`
--

DROP TABLE IF EXISTS `comites`;
CREATE TABLE IF NOT EXISTS `comites` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `responsable_id` int DEFAULT NULL,
  `parent_id` int DEFAULT NULL,
  `ordre_affichage` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `responsable_id` (`responsable_id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `cultes`
--

DROP TABLE IF EXISTS `cultes`;
CREATE TABLE IF NOT EXISTS `cultes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type_culte` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `date_culte` date NOT NULL,
  `nombre_hommes` int DEFAULT '0',
  `nombre_femmes` int DEFAULT '0',
  `nombre_enfants` int DEFAULT '0',
  `nombre_visiteurs` int DEFAULT '0',
  `theme_message` text COLLATE utf8mb4_general_ci,
  `predicateur` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total_presences` int GENERATED ALWAYS AS ((((`nombre_hommes` + `nombre_femmes`) + `nombre_enfants`) + `nombre_visiteurs`)) VIRTUAL,
  `utilisateur_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `enfants`
--

DROP TABLE IF EXISTS `enfants`;
CREATE TABLE IF NOT EXISTS `enfants` (
  `id` int NOT NULL AUTO_INCREMENT,
  `membre_id` int NOT NULL,
  `enfant_membre_id` int DEFAULT NULL,
  `nom` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `prenoms` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sexe` enum('Masculin','Feminin') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_enfants_parent` (`membre_id`),
  KEY `fk_enfants_membre_enfant` (`enfant_membre_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `enfants`
--

INSERT INTO `enfants` (`id`, `membre_id`, `enfant_membre_id`, `nom`, `prenoms`, `sexe`, `date_naissance`) VALUES
(19, 1, NULL, 'Kondoh', 'daniel', 'Masculin', '2024-05-13'),
(20, 1, NULL, 'Kondoh', 'lea', 'Feminin', '2026-05-06'),
(21, 1, NULL, 'Kondoh', 'Odelia', 'Feminin', '2026-05-24');

-- --------------------------------------------------------

--
-- Structure de la table `evenements`
--

DROP TABLE IF EXISTS `evenements`;
CREATE TABLE IF NOT EXISTS `evenements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) NOT NULL,
  `type_evenement` enum('Baptême','Mariage','Fête','Concert','Séminaire','Autre') NOT NULL,
  `date_evenement` date NOT NULL,
  `description` text,
  `lieu` varchar(255) DEFAULT 'Au temple',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `evenements`
--

INSERT INTO `evenements` (`id`, `titre`, `type_evenement`, `date_evenement`, `description`, `lieu`, `created_at`) VALUES
(1, 'Apothéose de la semaine national de la jeunesse', 'Fête', '2026-05-24', 'Fête de célébration de la jeunesse', 'Temple principal', '2026-05-19 20:10:07');

-- --------------------------------------------------------

--
-- Structure de la table `evenement_photos`
--

DROP TABLE IF EXISTS `evenement_photos`;
CREATE TABLE IF NOT EXISTS `evenement_photos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `evenement_id` int NOT NULL,
  `nom_fichier` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `evenement_id` (`evenement_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `evenement_photos`
--

INSERT INTO `evenement_photos` (`id`, `evenement_id`, `nom_fichier`) VALUES
(1, 1, 'EVT_1_6a0cc39f5c997.png');

-- --------------------------------------------------------

--
-- Structure de la table `historique_membre`
--

DROP TABLE IF EXISTS `historique_membre`;
CREATE TABLE IF NOT EXISTS `historique_membre` (
  `id` int NOT NULL AUTO_INCREMENT,
  `membre_id` int NOT NULL,
  `type_evenement` enum('Naissance','Mariage','Baptême','Décès','Départ','Arrivée','Autre') COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `date_evenement` date DEFAULT NULL,
  `utilisateur_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `fk_historique_membre` (`membre_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `historique_membre`
--

INSERT INTO `historique_membre` (`id`, `membre_id`, `type_evenement`, `description`, `date_evenement`, `utilisateur_id`) VALUES
(10, 1, 'Naissance', 'Naissance d\'un fils nommé(e) Kondoh daniel.', '2024-05-13', 2),
(11, 1, 'Naissance', 'Naissance d\'une fille nommé(e) Kondoh lea.', '2026-05-06', 2),
(12, 1, 'Naissance', 'Naissance d\'une fille nommé(e) Kondoh Odelia.', '2026-05-24', 2);

-- --------------------------------------------------------

--
-- Structure de la table `lignes_budget`
--

DROP TABLE IF EXISTS `lignes_budget`;
CREATE TABLE IF NOT EXISTS `lignes_budget` (
  `id` int NOT NULL AUTO_INCREMENT,
  `budget_id` int NOT NULL,
  `libelle` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `type_ligne` enum('ENTREE','SORTIE') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'ENTREE',
  `montant_prevu` decimal(12,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  KEY `fk_lignes_budget_budget` (`budget_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `lignes_budget`
--

INSERT INTO `lignes_budget` (`id`, `budget_id`, `libelle`, `type_ligne`, `montant_prevu`) VALUES
(1, 1, 'Frais d\'entretien', 'SORTIE', 30000.00),
(2, 1, 'Electricité', 'SORTIE', 30000.00),
(3, 1, 'Réception (eau, semaines efts et past. etc)', 'SORTIE', 30000.00),
(4, 1, 'Documents /  photocopie', 'SORTIE', 30000.00),
(5, 1, 'Déplacements / communication', 'SORTIE', 30000.00),
(6, 1, 'Dîmes', 'ENTREE', 50000.00),
(7, 1, 'Offrandes ordinaires', 'ENTREE', 50000.00),
(8, 1, 'Offrandes du soir', 'ENTREE', 50000.00),
(9, 1, 'Offrandes des enfants', 'ENTREE', 50000.00),
(10, 1, 'Offrandes écoles de dimanche', 'ENTREE', 50000.00),
(11, 1, 'Remerciements/actions de grâce', 'ENTREE', 50000.00),
(12, 1, 'Offrandes de mission', 'ENTREE', 50000.00),
(13, 1, 'Autres (AG CBT, Formation etc)', 'ENTREE', 50000.00);

-- --------------------------------------------------------

--
-- Structure de la table `lois_eglise`
--

DROP TABLE IF EXISTS `lois_eglise`;
CREATE TABLE IF NOT EXISTS `lois_eglise` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `contenu` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `categorie` enum('Constitution','Statuts','Règlement Intérieur','Résolution','Charte') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Règlement Intérieur',
  `version` varchar(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT '1.0',
  `date_adoption` date DEFAULT NULL,
  `utilisateur_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `membres`
--

DROP TABLE IF EXISTS `membres`;
CREATE TABLE IF NOT EXISTS `membres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `matricule` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `nom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `prenoms` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `sexe` enum('Masculin','Feminin') COLLATE utf8mb4_general_ci NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `lieu_naissance` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `profession` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `eglise_provenance` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_arrivee` date DEFAULT NULL,
  `baptise` tinyint(1) DEFAULT '0',
  `date_bapteme` date DEFAULT NULL,
  `lieu_bapteme` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `engagement_moral` tinyint(1) DEFAULT '0',
  `groupe_action` enum('Hommes','Femmes','Jeunesses','Enfants') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `qualite` enum('Membre','Ami','Enfant') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `statut_membre` enum('Actif','Inactif','Abandon','Depart') COLLATE utf8mb4_general_ci DEFAULT 'Actif',
  `telephone1` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `telephone2` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quartier` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `situation_matrimoniale` enum('Marié(e)','Célibataire','Veuf(ve)','Divorcé(e)') COLLATE utf8mb4_general_ci DEFAULT 'Célibataire',
  `date_mariage` date DEFAULT NULL,
  `lieu_mariage` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nom_conjoint` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nombre_enfants` int DEFAULT '0',
  `photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `commentaire` text COLLATE utf8mb4_general_ci,
  `date_enregistrement` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `utilisateur_id` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `matricule` (`matricule`),
  KEY `utilisateur_id` (`utilisateur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `membres`
--

INSERT INTO `membres` (`id`, `matricule`, `nom`, `prenoms`, `sexe`, `date_naissance`, `lieu_naissance`, `profession`, `eglise_provenance`, `date_arrivee`, `baptise`, `date_bapteme`, `lieu_bapteme`, `engagement_moral`, `groupe_action`, `qualite`, `statut_membre`, `telephone1`, `telephone2`, `email`, `quartier`, `situation_matrimoniale`, `date_mariage`, `lieu_mariage`, `nom_conjoint`, `nombre_enfants`, `photo`, `commentaire`, `date_enregistrement`, `utilisateur_id`) VALUES
(1, 'MEMB-2026-0001', 'Kondoh', 'kokouvi Emma', 'Masculin', '2004-12-24', 'lomé', 'Etudiant', 'Lomé 2', '2024-01-10', 1, '2026-02-18', 'Lomé 2', 0, 'Jeunesses', 'Membre', 'Actif', '96620756', '', 'elgonnkondoh@gmail.com', 'Novissi', 'Marié(e)', '2026-05-06', 'lomé', 'afi', 3, 'PHOTO_MEMB-2026-0001_1779421472.png', '', '2026-05-17 11:52:57', 2);

-- --------------------------------------------------------

--
-- Structure de la table `mutuelle_comptes`
--

DROP TABLE IF EXISTS `mutuelle_comptes`;
CREATE TABLE IF NOT EXISTS `mutuelle_comptes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `membre_id` int NOT NULL,
  `date_adhesion` date NOT NULL,
  `mise_journaliere` decimal(15,2) NOT NULL DEFAULT '0.00',
  `frais_tenue_mensuel` decimal(15,2) GENERATED ALWAYS AS ((`mise_journaliere` / 2)) STORED,
  `solde_tontine` decimal(15,2) DEFAULT '0.00',
  `solde_social` decimal(15,2) DEFAULT '0.00',
  `statut` enum('ACTIF','INACTIF','SUSPENDU') DEFAULT 'ACTIF',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_membre` (`membre_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `mutuelle_comptes`
--

INSERT INTO `mutuelle_comptes` (`id`, `membre_id`, `date_adhesion`, `mise_journaliere`, `solde_tontine`, `solde_social`, `statut`, `created_at`) VALUES
(4, 1, '2026-05-28', 500.00, 0.00, 0.00, 'ACTIF', '2026-05-28 02:06:02');

-- --------------------------------------------------------

--
-- Structure de la table `mutuelle_operations`
--

DROP TABLE IF EXISTS `mutuelle_operations`;
CREATE TABLE IF NOT EXISTS `mutuelle_operations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `compte_id` int NOT NULL,
  `pret_id` int DEFAULT NULL,
  `type_operation` enum('DEPOT','RETRAIT','PRET','REMBOURSEMENT','SOCIAL','FRAIS_TENUE','COMMISSION_PRET') NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `date_op` date NOT NULL,
  `commentaire` varchar(255) DEFAULT NULL,
  `utilisateur_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `compte_id` (`compte_id`),
  KEY `pret_id` (`pret_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `mutuelle_prets`
--

DROP TABLE IF EXISTS `mutuelle_prets`;
CREATE TABLE IF NOT EXISTS `mutuelle_prets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `compte_id` int NOT NULL,
  `montant_prete` decimal(15,2) NOT NULL,
  `taux` decimal(5,2) NOT NULL DEFAULT '5.00',
  `commission` decimal(15,2) GENERATED ALWAYS AS (((`montant_prete` * `taux`) / 100)) STORED,
  `commission_payee` enum('NON','OUI') DEFAULT 'NON',
  `date_pret` date NOT NULL,
  `date_debut_remboursement` date NOT NULL,
  `date_echeance` date DEFAULT NULL,
  `montant_rembourse` decimal(15,2) DEFAULT '0.00',
  `statut` enum('EN_COURS','SOLDE','RETARD') DEFAULT 'EN_COURS',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `compte_id` (`compte_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `organigramme`
--

DROP TABLE IF EXISTS `organigramme`;
CREATE TABLE IF NOT EXISTS `organigramme` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titre_poste` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `parent_id` int DEFAULT NULL,
  `titulaire_id` int DEFAULT NULL,
  `niveau_hierarchique` int DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=MyISAM AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `organigramme`
--

INSERT INTO `organigramme` (`id`, `titre_poste`, `parent_id`, `titulaire_id`, `niveau_hierarchique`) VALUES
(1, 'DIEU', NULL, NULL, 1),
(2, 'Pasteur / Assemblée Générale', 1, NULL, 2),
(3, 'Comité Exécutif', 2, NULL, 3),
(4, 'Commissariat aux Comptes', 2, NULL, 3),
(5, 'ADMINISTRATION', 3, NULL, 4),
(6, 'FIANANCES', 3, NULL, 4),
(7, 'OEUVRES SOCIALES / VISITES', 3, NULL, 4),
(8, 'MUSIQUE', 3, NULL, 4),
(9, 'PASTORAL', 3, NULL, 4),
(10, 'MISSION / EVANGELISATION', 3, NULL, 4),
(11, 'HOMMES', 3, NULL, 4),
(12, 'FEMMES', 3, NULL, 4),
(13, 'JEUNESSE', 3, NULL, 4),
(14, 'Secrétariat', 5, NULL, 5),
(15, 'Logistique Patrimoine', 5, NULL, 5),
(16, 'Relations publiques', 5, NULL, 5),
(17, 'Services', 5, NULL, 5),
(18, 'Budget', 6, NULL, 5),
(19, 'Trésorerie', 6, NULL, 5);

-- --------------------------------------------------------

--
-- Structure de la table `permissions_modules`
--

DROP TABLE IF EXISTS `permissions_modules`;
CREATE TABLE IF NOT EXISTS `permissions_modules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_module` varchar(50) NOT NULL,
  `role_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `module_role` (`nom_module`,`role_id`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `permissions_modules`
--

INSERT INTO `permissions_modules` (`id`, `nom_module`, `role_id`) VALUES
(27, 'communication', 1),
(24, 'finances', 1),
(22, 'gouvernance', 1),
(25, 'membres', 1),
(26, 'mutuelle', 1),
(23, 'tresorerie', 1);

-- --------------------------------------------------------

--
-- Structure de la table `plan_actions`
--

DROP TABLE IF EXISTS `plan_actions`;
CREATE TABLE IF NOT EXISTS `plan_actions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `budget_id` int NOT NULL,
  `objectif` varchar(255) NOT NULL,
  `theme` varchar(255) DEFAULT NULL,
  `activite` varchar(255) NOT NULL,
  `resultats_attendus` text,
  `periode_visee` varchar(100) DEFAULT NULL,
  `budget_estime` decimal(15,2) DEFAULT '0.00',
  `statut_action` enum('En attente','En cours','Réalisé','Annulé') DEFAULT 'En attente',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `budget_id` (`budget_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Structure de la table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_role` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `roles`
--

INSERT INTO `roles` (`id`, `nom_role`) VALUES
(1, 'Admin'),
(2, 'Secrétaire'),
(3, 'Trésorier'),
(4, 'Pasteur');

-- --------------------------------------------------------

--
-- Structure de la table `tresorerie`
--

DROP TABLE IF EXISTS `tresorerie`;
CREATE TABLE IF NOT EXISTS `tresorerie` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date_operation` date NOT NULL,
  `type_mouvement` enum('ENTREE','SORTIE') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ligne_budget_id` int DEFAULT NULL,
  `categorie` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `montant` decimal(15,2) NOT NULL,
  `libelle` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `membre_id` int DEFAULT NULL,
  `piece_justificative` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `utilisateur_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `date_op_idx` (`date_operation`),
  KEY `type_cat_idx` (`type_mouvement`,`categorie`),
  KEY `membre_id` (`membre_id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  KEY `fk_tresorerie_ligne_budget` (`ligne_budget_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE IF NOT EXISTS `utilisateurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `passwd` varchar(256) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `role_id` int NOT NULL,
  `statut` enum('actif','inactif') COLLATE utf8mb4_general_ci DEFAULT 'actif',
  `date_creation` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `email`, `passwd`, `role_id`, `statut`, `date_creation`) VALUES
(2, 'Emmanuel', 'admin@test.com', '$2y$10$LFmcZZAtxJqK4BAttjbjUeoaLZXwTAFDzmaF30VxfkaZGRR.GEnFu', 1, 'actif', '2026-05-14 22:25:06');

-- --------------------------------------------------------

--
-- Structure de la table `visiteurs`
--

DROP TABLE IF EXISTS `visiteurs`;
CREATE TABLE IF NOT EXISTS `visiteurs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_prenoms` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `telephone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quartier` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `invite_par` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `culte_id` int DEFAULT NULL,
  `date_visite` date NOT NULL,
  `statut_suivi` enum('À contacter','En cours','Fidélisé','Perdu') COLLATE utf8mb4_general_ci DEFAULT 'À contacter',
  `observations` text COLLATE utf8mb4_general_ci,
  `utilisateur_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `culte_id` (`culte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `comites`
--
ALTER TABLE `comites`
  ADD CONSTRAINT `fk_comites_membre` FOREIGN KEY (`responsable_id`) REFERENCES `membres` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_comites_parent` FOREIGN KEY (`parent_id`) REFERENCES `comites` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `enfants`
--
ALTER TABLE `enfants`
  ADD CONSTRAINT `fk_enfants_membre_enfant` FOREIGN KEY (`enfant_membre_id`) REFERENCES `membres` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_enfants_parent` FOREIGN KEY (`membre_id`) REFERENCES `membres` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `evenement_photos`
--
ALTER TABLE `evenement_photos`
  ADD CONSTRAINT `evenement_photos_ibfk_1` FOREIGN KEY (`evenement_id`) REFERENCES `evenements` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `historique_membre`
--
ALTER TABLE `historique_membre`
  ADD CONSTRAINT `fk_historique_membre` FOREIGN KEY (`membre_id`) REFERENCES `membres` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `lignes_budget`
--
ALTER TABLE `lignes_budget`
  ADD CONSTRAINT `fk_lignes_budget_budget` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `lois_eglise`
--
ALTER TABLE `lois_eglise`
  ADD CONSTRAINT `fk_lois_utilisateur` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `mutuelle_comptes`
--
ALTER TABLE `mutuelle_comptes`
  ADD CONSTRAINT `mutuelle_comptes_ibfk_1` FOREIGN KEY (`membre_id`) REFERENCES `membres` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `mutuelle_operations`
--
ALTER TABLE `mutuelle_operations`
  ADD CONSTRAINT `mutuelle_operations_ibfk_1` FOREIGN KEY (`compte_id`) REFERENCES `mutuelle_comptes` (`id`),
  ADD CONSTRAINT `mutuelle_operations_ibfk_2` FOREIGN KEY (`pret_id`) REFERENCES `mutuelle_prets` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `mutuelle_prets`
--
ALTER TABLE `mutuelle_prets`
  ADD CONSTRAINT `mutuelle_prets_ibfk_1` FOREIGN KEY (`compte_id`) REFERENCES `mutuelle_comptes` (`id`);

--
-- Contraintes pour la table `permissions_modules`
--
ALTER TABLE `permissions_modules`
  ADD CONSTRAINT `permissions_modules_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `plan_actions`
--
ALTER TABLE `plan_actions`
  ADD CONSTRAINT `plan_actions_ibfk_1` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `tresorerie`
--
ALTER TABLE `tresorerie`
  ADD CONSTRAINT `fk_tresorerie_ligne_budget` FOREIGN KEY (`ligne_budget_id`) REFERENCES `lignes_budget` (`id`) ON DELETE RESTRICT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
