-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 03 sep. 2025 à 14:51
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `cppf_e_services`
--

-- --------------------------------------------------------

--
-- Structure de la table `agents`
--

CREATE TABLE `agents` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `matricule_solde` varchar(13) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenoms` varchar(255) NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `poste` varchar(255) NOT NULL,
  `direction` varchar(255) NOT NULL,
  `etablissement` varchar(255) DEFAULT NULL,
  `grade` varchar(255) DEFAULT NULL,
  `indice` int(11) DEFAULT NULL,
  `salaire_base` decimal(12,2) DEFAULT NULL,
  `montant_bonifications` decimal(12,2) NOT NULL DEFAULT 0.00,
  `taux_cotisation` decimal(5,2) NOT NULL DEFAULT 6.00,
  `corps` varchar(255) DEFAULT NULL,
  `date_prise_service` date DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telephone` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `first_login` tinyint(1) NOT NULL DEFAULT 1,
  `password_changed` tinyint(1) NOT NULL DEFAULT 0,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `status` enum('actif','suspendu','transfere') NOT NULL DEFAULT 'actif',
  `position_administrative` varchar(255) NOT NULL DEFAULT 'ACTIVITE',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `situation_matrimoniale` varchar(255) DEFAULT NULL,
  `sexe` varchar(1) DEFAULT NULL,
  `verification_code` varchar(255) DEFAULT NULL,
  `verification_code_expires_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `agents`
--

INSERT INTO `agents` (`id`, `matricule_solde`, `nom`, `prenoms`, `date_naissance`, `poste`, `direction`, `etablissement`, `grade`, `indice`, `salaire_base`, `montant_bonifications`, `taux_cotisation`, `corps`, `date_prise_service`, `email`, `telephone`, `password`, `first_login`, `password_changed`, `email_verified_at`, `phone_verified_at`, `status`, `position_administrative`, `is_active`, `created_at`, `updated_at`, `situation_matrimoniale`, `sexe`, `verification_code`, `verification_code_expires_at`) VALUES
(1, '123456M', 'MBEMBA', 'Jean Claude', '1975-05-12', 'Directeur des Ressources Humaines', 'Direction Générale', NULL, 'Administrateur Principal', 1150, NULL, 0.00, 6.00, 'FONCTIONNAIRES', '2010-03-15', NULL, NULL, NULL, 1, 0, NULL, NULL, 'actif', 'ACTIVITE', 1, '2025-07-10 10:39:54', '2025-07-31 11:35:35', 'Marié(e)', 'M', NULL, NULL),
(2, '234567A', 'NZAMBA', 'Marie Antoinette', '1980-03-08', 'Chef de Service Prestations', 'Direction des Prestations Familiales', NULL, 'Attaché Principal', 1050, NULL, 0.00, 6.00, 'FONCTIONNAIRES', '2015-07-20', 'nzamba@gmail.com', '+24177651601', '$2y$12$wxCrH9h/4j2xiWS00XV9zuzS9vqFwBqSbW4lBzrRiutodskt/L/ne', 0, 1, NULL, '2025-08-05 10:56:34', 'actif', 'ACTIVITE', 1, '2025-07-10 10:39:54', '2025-08-06 07:25:54', 'Célibataire', 'F', NULL, NULL),
(3, '345678B', 'OBAME', 'Pierre François', '1978-11-25', 'Comptable Principal', 'Direction Financière et Comptable', NULL, 'Contrôleur des Services Financiers', 1080, NULL, 0.00, 6.00, 'FONCTIONNAIRES', '2012-01-10', NULL, NULL, NULL, 1, 0, NULL, NULL, 'actif', 'ACTIVITE', 1, '2025-07-10 10:39:54', '2025-07-31 11:35:35', 'Marié(e)', 'M', NULL, NULL),
(4, '456789C', 'ALLOGHO', 'Sylvie Marguerite', '1985-07-18', 'Secrétaire de Direction', 'Direction Générale', NULL, 'Secrétaire Principal', 920, NULL, 0.00, 6.00, 'FONCTIONNAIRES', '2018-09-05', NULL, NULL, NULL, 1, 0, NULL, NULL, 'actif', 'ACTIVITE', 1, '2025-07-10 10:39:54', '2025-07-31 11:35:35', 'Célibataire', 'F', NULL, NULL),
(5, '567890D', 'MINTSA', 'Bernard Thierry', '1982-04-30', 'Chef de Service Informatique', 'Direction des Systèmes d\'Information', NULL, 'Ingénieur Principal', 1200, NULL, 0.00, 6.00, 'FONCTIONNAIRES', '2014-11-12', 'lloyddarrylobg@gmail.com', '+24177651601', '$2y$12$Dt1U7cXYkVdzxybF0UopwOIniOCM5FVZh.OzyMDRR7LFNfVgVDtwG', 0, 1, NULL, '2025-08-05 08:32:03', 'actif', 'ACTIVITE', 1, '2025-07-10 10:39:54', '2025-08-06 07:25:54', 'Marié(e)', 'M', NULL, NULL),
(6, '890121E', 'MOUNDOUNGA', 'Georgette Pascaline', '1983-05-30', 'Directrice des Affaires Juridiques', 'Direction des Affaires Juridiques', NULL, 'Conseiller Juridique Principal', NULL, NULL, 0.00, 6.00, NULL, '2008-05-30', NULL, NULL, NULL, 1, 0, NULL, NULL, 'actif', 'ACTIVITE', 1, '2025-07-10 10:39:54', '2025-07-15 12:34:56', NULL, 'F', NULL, NULL),
(7, '890123F', 'ENGONE', 'Rodrigue Emmanuel', '1981-09-06', 'Chef de Service Communication', 'Direction de la Communication', NULL, 'Attaché de Communication', 980, NULL, 0.00, 6.00, 'FONCTIONNAIRES', '2016-02-18', NULL, NULL, NULL, 1, 0, NULL, NULL, 'actif', 'ACTIVITE', 1, '2025-07-10 10:39:54', '2025-07-31 11:35:35', 'Célibataire', 'M', NULL, NULL),
(14, '678901E', 'MOUNDOUNGA', 'Georgette Pascaline', '1973-12-14', 'Directrice des Affaires Juridiques', 'Direction des Affaires Juridiques', NULL, 'Conseiller Juridique Principal', 1300, NULL, 0.00, 6.00, 'FONCTIONNAIRES', '2008-05-30', NULL, NULL, NULL, 1, 0, NULL, NULL, 'actif', 'ACTIVITE', 1, '2025-07-31 11:35:35', '2025-07-31 11:35:35', 'Marié(e)', 'F', NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `carrieres`
--

CREATE TABLE `carrieres` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `agent_id` bigint(20) UNSIGNED NOT NULL,
  `matricule_assure` varchar(255) NOT NULL,
  `grade_code` int(11) NOT NULL,
  `grade_libelle` varchar(255) DEFAULT NULL,
  `indice` int(11) NOT NULL,
  `statut` varchar(255) NOT NULL,
  `position_administrative` varchar(255) NOT NULL,
  `presence` varchar(255) NOT NULL DEFAULT 'PRESENT',
  `fonction` varchar(255) DEFAULT NULL,
  `corps` varchar(255) DEFAULT NULL,
  `etablissement` varchar(255) DEFAULT NULL,
  `departement_ministere` varchar(255) DEFAULT NULL,
  `salaire_brut` decimal(12,2) DEFAULT NULL,
  `salaire_net` decimal(12,2) DEFAULT NULL,
  `salaire_base` decimal(12,2) DEFAULT NULL,
  `montant_bonifications` decimal(12,2) NOT NULL DEFAULT 0.00,
  `cotisations` decimal(12,2) DEFAULT NULL,
  `detachement` tinyint(1) NOT NULL DEFAULT 0,
  `date_debut_detachement` date DEFAULT NULL,
  `date_fin_detachement` date DEFAULT NULL,
  `date_suspension_solde` date DEFAULT NULL,
  `date_carriere` date NOT NULL,
  `etat_general` varchar(255) DEFAULT NULL,
  `taux_cotisation` decimal(5,2) NOT NULL DEFAULT 6.00,
  `valide` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `carriere_historiques`
--

CREATE TABLE `carriere_historiques` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `coefficients_temporels`
--

CREATE TABLE `coefficients_temporels` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `annee` int(11) NOT NULL,
  `coefficient` decimal(5,2) NOT NULL,
  `periode_debut` varchar(255) NOT NULL,
  `periode_fin` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `coefficients_temporels`
--

INSERT INTO `coefficients_temporels` (`id`, `annee`, `coefficient`, `periode_debut`, `periode_fin`, `description`, `actif`, `created_at`, `updated_at`) VALUES
(1, 2015, 70.00, 'août 2015', 'juillet 2016', 'Coefficient applicable aux pensions liquidées de août 2015 à juillet 2016', 1, '2025-07-31 07:03:44', '2025-07-31 07:03:44'),
(2, 2016, 72.00, 'août 2016', 'juillet 2017', 'Coefficient applicable aux pensions liquidées de août 2016 à juillet 2017', 1, '2025-07-31 07:03:44', '2025-07-31 07:03:44'),
(3, 2017, 74.00, 'août 2017', 'juillet 2018', 'Coefficient applicable aux pensions liquidées de août 2017 à juillet 2018', 1, '2025-07-31 07:03:44', '2025-07-31 07:03:44'),
(4, 2018, 76.00, 'août 2018', 'juillet 2019', 'Coefficient applicable aux pensions liquidées de août 2018 à juillet 2019', 1, '2025-07-31 07:03:44', '2025-07-31 07:03:44'),
(5, 2019, 79.00, 'août 2019', 'juillet 2020', 'Coefficient applicable aux pensions liquidées de août 2019 à juillet 2020', 1, '2025-07-31 07:03:44', '2025-07-31 07:03:44'),
(6, 2020, 81.00, 'août 2020', 'juillet 2021', 'Coefficient applicable aux pensions liquidées de août 2020 à juillet 2021', 1, '2025-07-31 07:03:44', '2025-07-31 07:03:44'),
(7, 2021, 83.00, 'août 2021', 'juillet 2022', 'Coefficient applicable aux pensions liquidées de août 2021 à juillet 2022', 1, '2025-07-31 07:03:44', '2025-07-31 07:03:44'),
(8, 2022, 85.00, 'août 2022', 'juillet 2023', 'Coefficient applicable aux pensions liquidées de août 2022 à juillet 2023', 1, '2025-07-31 07:03:44', '2025-07-31 07:03:44'),
(9, 2023, 87.00, 'août 2023', 'juillet 2024', 'Coefficient applicable aux pensions liquidées de août 2023 à juillet 2024', 1, '2025-07-31 07:03:44', '2025-07-31 07:03:44'),
(10, 2024, 89.00, 'août 2024', 'juillet 2025', 'Coefficient applicable aux pensions liquidées de août 2024 à juillet 2025', 1, '2025-07-31 07:03:44', '2025-07-31 07:03:44'),
(11, 2025, 91.00, 'août 2025', 'juillet 2026', 'Coefficient applicable aux pensions liquidées de août 2025 à juillet 2026', 1, '2025-07-31 07:03:44', '2025-07-31 07:03:44'),
(12, 2026, 94.00, 'août 2026', 'juillet 2027', 'Coefficient applicable aux pensions liquidées de août 2026 à juillet 2027', 1, '2025-07-31 07:03:44', '2025-07-31 07:03:44'),
(13, 2027, 96.00, 'août 2027', 'juillet 2028', 'Coefficient applicable aux pensions liquidées de août 2027 à juillet 2028', 1, '2025-07-31 07:03:44', '2025-07-31 07:03:44'),
(14, 2028, 98.00, 'août 2028', 'juillet 2029', 'Coefficient applicable aux pensions liquidées de août 2028 à juillet 2029', 1, '2025-07-31 07:03:44', '2025-07-31 07:03:44'),
(15, 2029, 100.00, 'août 2029', 'indéterminée', 'Coefficient applicable aux pensions liquidées de août 2029 à indéterminée', 1, '2025-07-31 07:03:44', '2025-07-31 07:03:44');

-- --------------------------------------------------------

--
-- Structure de la table `conjoints`
--

CREATE TABLE `conjoints` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `agent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `retraite_id` bigint(20) UNSIGNED DEFAULT NULL,
  `matricule_conjoint` varchar(255) DEFAULT NULL,
  `nag_conjoint` varchar(255) DEFAULT NULL,
  `nom` varchar(255) NOT NULL,
  `prenoms` varchar(255) NOT NULL,
  `sexe` enum('M','F') NOT NULL,
  `date_naissance` date NOT NULL,
  `date_mariage` date DEFAULT NULL,
  `statut` enum('ACTIF','DIVORCE','VEUF') NOT NULL DEFAULT 'ACTIF',
  `travaille` tinyint(1) NOT NULL DEFAULT 0,
  `profession` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `conjoints`
--

INSERT INTO `conjoints` (`id`, `agent_id`, `retraite_id`, `matricule_conjoint`, `nag_conjoint`, `nom`, `prenoms`, `sexe`, `date_naissance`, `date_mariage`, `statut`, `travaille`, `profession`, `created_at`, `updated_at`) VALUES
(1, 1, NULL, '998877F', NULL, 'MBEMBA', 'Sylvie Joséphine', 'F', '1978-08-20', '2005-06-15', 'ACTIF', 0, 'Infirmière', '2025-08-06 07:25:54', '2025-08-06 07:25:54'),
(2, 3, NULL, NULL, '1982051502', 'OBAME', 'Marie Christine', 'F', '1982-05-15', '2008-12-20', 'ACTIF', 0, 'Ménagère', '2025-08-06 07:25:54', '2025-08-06 07:25:54'),
(3, 5, NULL, '556677G', NULL, 'MINTSA', 'Patricia Solange', 'F', '1985-12-10', '2010-07-24', 'ACTIF', 0, 'Comptable', '2025-08-06 07:25:54', '2025-08-06 07:25:54'),
(4, 14, NULL, '445566H', NULL, 'MOUNDOUNGA', 'Rodrigue Emmanuel', 'M', '1970-03-25', '1998-08-12', 'ACTIF', 0, 'Avocat', '2025-08-06 07:25:54', '2025-08-06 07:25:54'),
(5, NULL, 1, NULL, '1958021801', 'MOUSSOUNDA', 'Bernadette Solange', 'F', '1958-02-18', '1980-05-20', 'ACTIF', 0, 'Ancienne institutrice', '2025-08-06 07:25:54', '2025-08-06 07:25:54'),
(6, NULL, 3, NULL, '1962081201', 'KOUMBA', 'Marie Antoinette', 'F', '1962-08-12', '1985-04-15', 'ACTIF', 0, 'Ancienne secrétaire', '2025-08-06 07:25:54', '2025-08-06 07:25:54');

-- --------------------------------------------------------

--
-- Structure de la table `documents_retraites`
--

CREATE TABLE `documents_retraites` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `retraite_id` bigint(20) UNSIGNED NOT NULL,
  `nom_original` varchar(255) NOT NULL,
  `nom_fichier` varchar(255) NOT NULL,
  `chemin_fichier` varchar(255) NOT NULL,
  `type_document` enum('certificat_vie','autre') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `taille_fichier` int(11) NOT NULL,
  `extension` varchar(255) NOT NULL,
  `statut` enum('actif','expire','remplace','supprime') NOT NULL DEFAULT 'actif',
  `date_emission` date DEFAULT NULL,
  `date_expiration` date DEFAULT NULL,
  `autorite_emission` varchar(255) DEFAULT NULL,
  `date_depot` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notifie_par_email` tinyint(1) NOT NULL DEFAULT 0,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `documents_retraites`
--

INSERT INTO `documents_retraites` (`id`, `retraite_id`, `nom_original`, `nom_fichier`, `chemin_fichier`, `type_document`, `description`, `taille_fichier`, `extension`, `statut`, `date_emission`, `date_expiration`, `autorite_emission`, `date_depot`, `notifie_par_email`, `metadata`, `created_at`, `updated_at`) VALUES
(1, 4, 'bulletins_annee.pdf', 'retraite_4_2025-09-02_09-00-59_Rx3wIf27.pdf', 'documents/retraites/4/retraite_4_2025-09-02_09-00-59_Rx3wIf27.pdf', 'certificat_vie', NULL, 699728, 'pdf', 'actif', '2025-07-08', '2026-07-08', 'Mairie 1er arrondissement', '2025-09-02 09:01:12', 1, '{\"mime_type\":\"application\\/pdf\",\"ip_depot\":\"127.0.0.1\",\"user_agent\":\"Mozilla\\/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit\\/537.36 (KHTML, like Gecko) Chrome\\/139.0.0.0 Safari\\/537.36\"}', '2025-09-02 08:01:00', '2025-09-02 08:01:12');

-- --------------------------------------------------------

--
-- Structure de la table `enfants`
--

CREATE TABLE `enfants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `enfant_id` varchar(255) NOT NULL,
  `matricule_parent` varchar(255) NOT NULL,
  `agent_id` bigint(20) UNSIGNED DEFAULT NULL,
  `retraite_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nom` varchar(255) NOT NULL,
  `prenoms` varchar(255) NOT NULL,
  `sexe` enum('M','F') NOT NULL,
  `date_naissance` date NOT NULL,
  `prestation_familiale` tinyint(1) NOT NULL DEFAULT 0,
  `scolarise` tinyint(1) NOT NULL DEFAULT 1,
  `niveau_scolaire` varchar(255) DEFAULT NULL,
  `actif` tinyint(1) NOT NULL DEFAULT 1,
  `observations` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `enfants`
--

INSERT INTO `enfants` (`id`, `enfant_id`, `matricule_parent`, `agent_id`, `retraite_id`, `nom`, `prenoms`, `sexe`, `date_naissance`, `prestation_familiale`, `scolarise`, `niveau_scolaire`, `actif`, `observations`, `created_at`, `updated_at`) VALUES
(1, '2010123456', '123456', 1, NULL, 'MBEMBA', 'Kevin Junior', 'M', '2010-03-10', 1, 1, 'CM2', 1, NULL, '2025-08-06 07:25:54', '2025-08-06 07:25:54'),
(2, '2015567890', '123456', 1, NULL, 'MBEMBA', 'Grace Ornella', 'F', '2015-11-25', 1, 1, 'CE2', 1, NULL, '2025-08-06 07:25:54', '2025-08-06 07:25:54'),
(3, '2012345678', '345678', 3, NULL, 'OBAME', 'Sandra Lisa', 'F', '2012-09-12', 1, 1, '6ème', 1, NULL, '2025-08-06 07:25:54', '2025-08-06 07:25:54'),
(4, '2016789012', '345678', 3, NULL, 'OBAME', 'David Michel', 'M', '2016-04-08', 1, 1, 'CE1', 1, NULL, '2025-08-06 07:25:54', '2025-08-06 07:25:54'),
(5, '2020111222', '345678', 3, NULL, 'OBAME', 'Ethan Prince', 'M', '2020-01-18', 1, 1, 'Petite section', 1, NULL, '2025-08-06 07:25:54', '2025-08-06 07:25:54'),
(6, '2008654321', '567890', 5, NULL, 'MINTSA', 'Jordan Alex', 'M', '2008-06-15', 1, 1, 'Première', 1, NULL, '2025-08-06 07:25:54', '2025-08-06 07:25:54'),
(7, '2000987654', '678901', 14, NULL, 'MOUNDOUNGA', 'Excellence Rodrigue', 'M', '2000-10-05', 0, 0, 'Université terminée', 1, 'Diplômé en Droit', '2025-08-06 07:25:54', '2025-08-06 07:25:54'),
(8, '2007456789', '678901', 14, NULL, 'MOUNDOUNGA', 'Grâce Ornella', 'F', '2007-02-28', 1, 1, 'Terminale', 1, NULL, '2025-08-06 07:25:54', '2025-08-06 07:25:54'),
(9, '2019333444', '456789', 4, NULL, 'ALLOGHO', 'Destiny Chance', 'F', '2019-12-08', 1, 1, 'Petite section', 1, NULL, '2025-08-06 07:25:54', '2025-08-06 07:25:54'),
(10, '1985123789', '2020001234', NULL, 1, 'MOUSSOUNDA', 'Patricia Excellence', 'F', '1985-03-12', 0, 0, 'Master en Gestion', 1, 'Directrice d\'entreprise', '2025-08-06 07:25:54', '2025-08-06 07:25:54'),
(11, '1988456123', '2020001234', NULL, 1, 'MOUSSOUNDA', 'Jean Rodrigue', 'M', '1988-11-08', 0, 0, 'Ingénieur', 1, 'Ingénieur informatique', '2025-08-06 07:25:54', '2025-08-06 07:25:54'),
(12, '2005789456', '2020001234', NULL, 1, 'MOUSSOUNDA', 'Kevin Emmanuel', 'M', '2005-07-22', 1, 1, 'Terminale', 1, NULL, '2025-08-06 07:25:54', '2025-08-06 07:25:54'),
(13, '1990234567', '2019005678', NULL, 2, 'NTOUTOUME', 'Michel Prince', 'M', '1990-05-15', 0, 0, 'Licence en Économie', 1, 'Banquier', '2025-08-06 07:25:54', '2025-08-06 07:25:54'),
(14, '1995678901', '2019005678', NULL, 2, 'NTOUTOUME', 'Grâce Divine', 'F', '1995-09-30', 0, 0, 'Master en Communication', 1, 'Journaliste', '2025-08-06 07:25:54', '2025-08-06 07:25:54'),
(15, '2010998877', '2021009876', NULL, 3, 'KOUMBA', 'Darren Excellence', 'M', '2010-12-20', 1, 1, 'CM1', 1, 'Petit-fils à charge', '2025-08-06 07:25:54', '2025-08-06 07:25:54'),
(16, '1992555666', '2018012345', NULL, 4, 'MOUNANGA', 'Sandra Ornella', 'F', '1992-06-10', 0, 0, 'Infirmière diplômée', 1, 'Infirmière à l\'hôpital', '2025-08-06 07:25:54', '2025-08-06 07:25:54');

-- --------------------------------------------------------

--
-- Structure de la table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `famille_tables`
--

CREATE TABLE `famille_tables` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `famille_tables_extend`
--

CREATE TABLE `famille_tables_extend` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `grilles_indiciaires`
--

CREATE TABLE `grilles_indiciaires` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `type_grille` varchar(255) NOT NULL,
  `categorie` varchar(255) NOT NULL,
  `classe` int(11) DEFAULT NULL,
  `duree_classe` int(11) DEFAULT NULL,
  `indice_ancien` int(11) DEFAULT NULL,
  `indice_nouveau` int(11) DEFAULT NULL,
  `valeur_point` decimal(8,2) NOT NULL DEFAULT 500.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `grille_indiciaires`
--

CREATE TABLE `grille_indiciaires` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2025_07_09_093207_create_agents_table', 1),
(5, '2025_07_09_093209_create_retraites_table', 1),
(6, '2025_07_10_115532_create_personal_access_tokens_table', 2),
(7, '2025_07_29_120000_add_pension_simulation_fields', 3),
(8, '2025_07_30_140000_update_pension_article94', 3),
(9, '2025_07_31_080006_add_pension_simulation_fields_corrected', 3),
(11, '2025_07_31_080934_create_parametres_pension_table', 4),
(12, '2025_07_31_add_missing_fields_to_agents', 5),
(13, '2025_07_31_122259_add_missing_fields_to_agents_table', 6),
(14, '2025_07_31_123106_add_missing_fields_to_agents_table', 6),
(15, '2025_07_31_123106_create_simulation_pensions_table', 6),
(16, '2025_07_31_123107_create_carriere_historiques_table', 6),
(17, '2025_07_31_123108_create_grille_indiciaires_table', 6),
(18, '2025_01_XX_create_famille_tables', 7),
(19, '2025_08_04_085526_create_famille_tables', 7),
(20, '2025_08_04_093345_create_famille_tables_extend', 8),
(21, '2025_08_04_143133_add_missing_fields_to_enfants', 9),
(22, '2025_08_04_add_missing_fields_to_enfants', 9),
(23, '2025_08_05_131344_add_verification_fields_to_retraites_table', 10),
(24, '2025_08_05_135019_ensure_retraite_support_in_famille_tables', 11),
(26, '2025_08_11_075159_create_reclamation_historique_table', 12),
(27, '2025_08_27_add_reminder_fields_to_rendez_vous_demandes_table', 13),
(28, 'create_documents_retraites_table', 14);

-- --------------------------------------------------------

--
-- Structure de la table `parametres_pension`
--

CREATE TABLE `parametres_pension` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code_parametre` varchar(50) NOT NULL,
  `libelle` varchar(255) NOT NULL,
  `valeur` decimal(15,4) NOT NULL,
  `type_valeur` enum('decimal','percentage','integer','boolean') NOT NULL DEFAULT 'decimal',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `date_effet` date NOT NULL DEFAULT '2025-07-31',
  `date_fin` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parametres_pension`
--

INSERT INTO `parametres_pension` (`id`, `code_parametre`, `libelle`, `valeur`, `type_valeur`, `description`, `is_active`, `date_effet`, `date_fin`, `created_at`, `updated_at`) VALUES
(1, 'AGE_RETRAITE', 'Âge de départ à la retraite', 60.0000, 'integer', 'Âge légal de départ à la retraite pour les fonctionnaires', 1, '2025-08-01', NULL, '2025-08-01 08:20:10', '2025-08-01 08:20:10'),
(2, 'DUREE_SERVICE_MIN', 'Durée de service minimum', 15.0000, 'integer', 'Durée minimum de service pour avoir droit à une pension', 1, '2025-08-01', NULL, '2025-08-01 08:20:10', '2025-08-01 08:20:10'),
(3, 'TAUX_LIQUIDATION_ANNUEL', 'Taux de liquidation par année', 1.8000, 'decimal', 'Taux de liquidation par année de service (années × 1,8%)', 1, '2025-08-01', NULL, '2025-08-01 08:20:10', '2025-08-01 08:20:10'),
(4, 'VALEUR_POINT_INDICE', 'Valeur du point d\'indice', 500.0000, 'decimal', 'Valeur du point d\'indice en FCFA', 1, '2025-08-01', NULL, '2025-08-01 08:20:10', '2025-08-01 08:20:10'),
(5, 'TAUX_LIQUIDATION_MAX', 'Taux de liquidation maximum', 75.0000, 'decimal', 'Taux maximum de liquidation (75%)', 1, '2025-08-01', NULL, '2025-08-01 08:20:10', '2025-08-01 08:20:10'),
(6, 'BONIF_CONJOINT', 'Bonification pour conjoint', 3.0000, 'decimal', 'Majoration pour conjoint à charge (3%)', 1, '2025-08-01', NULL, '2025-08-01 08:20:10', '2025-08-01 08:20:10'),
(7, 'BONIF_ENFANT', 'Bonification par enfant', 2.0000, 'decimal', 'Majoration par enfant à charge (2%)', 1, '2025-08-01', NULL, '2025-08-01 08:20:10', '2025-08-01 08:20:10');

-- --------------------------------------------------------

--
-- Structure de la table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `personal_access_tokens`
--

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `personal_access_tokens`
--

INSERT INTO `personal_access_tokens` (`id`, `tokenable_type`, `tokenable_id`, `name`, `token`, `abilities`, `last_used_at`, `expires_at`, `created_at`, `updated_at`) VALUES
(2, 'App\\Models\\Agent', 1, 'auth-session', 'b02dfb2a933a3952a9a3170fda3968cd12693dca344777484da1095d6d11825f', '[\"*\"]', '2025-07-11 08:13:53', NULL, '2025-07-11 07:13:38', '2025-07-11 08:13:53'),
(4, 'App\\Models\\Retraite', 1, 'auth-session', 'cd1d1f8d22e97f4c5e512b9a511fedb1f558e5c66107e3057d40e8786dcef170', '[\"*\"]', '2025-07-11 09:47:34', NULL, '2025-07-11 08:14:29', '2025-07-11 09:47:34'),
(9, 'App\\Models\\Retraite', 2, 'auth-session', 'e5dc613a4ea3ea1886e7c336178aeffa4f1efbbef7f6209fe5682cfc88352900', '[\"*\"]', '2025-07-11 10:19:51', NULL, '2025-07-11 10:18:20', '2025-07-11 10:19:51'),
(17, 'App\\Models\\Agent', 1, 'auth-session', 'e6c4ac8a83248338d6e5f0153168c5c2f766581d9f6781812e973df8a451185b', '[\"*\"]', '2025-07-11 11:32:55', NULL, '2025-07-11 11:32:52', '2025-07-11 11:32:55'),
(18, 'App\\Models\\Agent', 1, 'auth-session', '3b7013e9acf9e8930b72a5710f2490bc70e450c28602a7e6cc55688081cc917b', '[\"*\"]', '2025-07-14 09:28:21', NULL, '2025-07-14 07:47:23', '2025-07-14 09:28:21'),
(20, 'App\\Models\\Agent', 1, 'auth-session', '35cc616f921e47f597556743eb71ecf7fc134cb6d3b131367426943d6cc49f92', '[\"*\"]', '2025-07-14 10:43:45', NULL, '2025-07-14 10:43:42', '2025-07-14 10:43:45'),
(23, 'App\\Models\\Retraite', 1, 'auth-session', 'a57ff9bf529d683a77caf69e231f8c52917dff9452b78581ba0d15eeef32a6e8', '[\"*\"]', '2025-07-15 12:03:17', NULL, '2025-07-15 11:46:44', '2025-07-15 12:03:17'),
(24, 'App\\Models\\Agent', 1, 'auth-session', '434f31d6551820b09c5b725a75211e55b9a97cd9874772f0f3ac00884a9f8bb7', '[\"*\"]', '2025-07-15 12:20:21', NULL, '2025-07-15 12:04:10', '2025-07-15 12:20:21'),
(26, 'App\\Models\\Agent', 6, 'auth-session', 'd08baa241c0608370e50ca37f6ca2866221fb82b41c611ca17ecc49f8f11fc34', '[\"*\"]', '2025-07-15 13:41:44', NULL, '2025-07-15 12:35:46', '2025-07-15 13:41:44'),
(27, 'App\\Models\\Agent', 1, 'auth-session', '0edd0b7980e199a522668792d2ac370122e9635c96452b8d2703150c030a0d42', '[\"*\"]', '2025-07-16 11:24:34', NULL, '2025-07-16 10:54:04', '2025-07-16 11:24:34'),
(28, 'App\\Models\\Agent', 1, 'auth-session', 'bd5990924bc176c8dc479df99cc2fa598d1403306caed843a9a250b22e8cda81', '[\"*\"]', '2025-07-16 11:40:04', NULL, '2025-07-16 11:30:17', '2025-07-16 11:40:04'),
(29, 'App\\Models\\Agent', 1, 'auth-session', '2b2844bfba13b0c10c0a143f09f2d5d3946113da5d5e02c9792053b3d73f281e', '[\"*\"]', '2025-07-16 13:41:37', NULL, '2025-07-16 11:41:06', '2025-07-16 13:41:37'),
(30, 'App\\Models\\Agent', 1, 'auth-session', '308c84ab83865b87aa466b21d652fe3eb283cf9de79551a05ff41295600a602e', '[\"*\"]', '2025-07-16 13:45:08', NULL, '2025-07-16 13:42:20', '2025-07-16 13:45:08'),
(31, 'App\\Models\\Agent', 1, 'auth-session', '46570a73e6a6b2005f297c8ea13d4a50d0c7bebe5732220907fcb3da8262b73d', '[\"*\"]', '2025-07-17 07:42:40', NULL, '2025-07-17 07:16:13', '2025-07-17 07:42:40'),
(32, 'App\\Models\\Agent', 1, 'auth-session', '617ea7a7e14e1b2fcf3a368b6f3089e6326500ebc98a72adf84cf0b399d334c9', '[\"*\"]', '2025-07-17 07:43:56', NULL, '2025-07-17 07:43:47', '2025-07-17 07:43:56'),
(34, 'App\\Models\\Agent', 1, 'auth-session', '40a9b09f24edc97e59aa778d56d5648fca4fa5666da091a253e14f75ef734ca3', '[\"*\"]', NULL, NULL, '2025-07-17 10:42:00', '2025-07-17 10:42:00'),
(37, 'App\\Models\\Agent', 1, 'auth-session', '830ebec16e3e7747062ee9743ae36b079447f11751b875ecc93f964daa48072f', '[\"*\"]', NULL, NULL, '2025-07-17 12:05:56', '2025-07-17 12:05:56'),
(39, 'App\\Models\\Retraite', 1, 'auth-session', 'd85b0f217e4a3230cbe33c6fb838222aea8ec82f42ac403d3d8f76951a60faa9', '[\"*\"]', '2025-07-21 08:34:35', NULL, '2025-07-21 08:18:39', '2025-07-21 08:34:35'),
(40, 'App\\Models\\Agent', 1, 'auth-session', 'c84e6d499d1e70655fa2bb782f220b0cc6b1189633e4f423f129e9e89801ef7e', '[\"*\"]', '2025-07-21 12:29:20', NULL, '2025-07-21 12:27:09', '2025-07-21 12:29:20'),
(41, 'App\\Models\\Agent', 1, 'auth-session', '4eac6575e1ab88dca074fc8c4a52cda4c846856a0dffbc13db6556788c17ee22', '[\"*\"]', '2025-07-23 10:21:08', NULL, '2025-07-21 13:37:37', '2025-07-23 10:21:08'),
(66, 'App\\Models\\Retraite', 1, 'auth-session', '2578281d380f156ff0d2713daefa1069547305f296c149f48967c904c54d130e', '[\"*\"]', '2025-07-28 12:36:08', NULL, '2025-07-28 12:29:57', '2025-07-28 12:36:08'),
(68, 'App\\Models\\Retraite', 3, 'setup-session', '443be3e836baf396e4e7a652a8effedec85c2482553e2fb5c357f44d914f00a6', '[\"*\"]', NULL, NULL, '2025-07-28 12:56:32', '2025-07-28 12:56:32'),
(99, 'App\\Models\\Retraite', 5, 'auth-session', '575ea0e1156e64f5afdc6d4fb2a8048ee599cbacbdfd4e23ee0d77ea2ae41f0f', '[\"*\"]', '2025-08-05 11:08:58', NULL, '2025-08-05 11:08:39', '2025-08-05 11:08:58'),
(102, 'App\\Models\\Retraite', 5, 'setup-session', '987322ac6a9eabb028e5669cc4fc215dfd882193dae59f21f3668dffba3a521b', '[\"*\"]', NULL, NULL, '2025-08-05 12:39:49', '2025-08-05 12:39:49'),
(187, 'App\\Models\\Agent', 5, 'auth-session', '28cc5eae402d9eb4d0a5346cf2df796524c51c897dfb0e1161f89b639b1e72e5', '[\"*\"]', '2025-08-28 12:29:34', NULL, '2025-08-28 09:09:49', '2025-08-28 12:29:34'),
(193, 'App\\Models\\Retraite', 4, 'auth-session', '734581e4af2c1fa9f9e4f05d23e7fb90d9396061ed96e5cb6e837e300c398125', '[\"*\"]', '2025-09-03 11:45:25', NULL, '2025-09-02 09:22:28', '2025-09-03 11:45:25');

-- --------------------------------------------------------

--
-- Structure de la table `prestations_familiales`
--

CREATE TABLE `prestations_familiales` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `enfant_id` bigint(20) UNSIGNED NOT NULL,
  `type_prestation` varchar(255) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `statut` enum('ACTIF','SUSPENDU','ARRETE') NOT NULL DEFAULT 'ACTIF',
  `motif_arret` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reclamations`
--

CREATE TABLE `reclamations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `user_type` varchar(255) NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `user_telephone` varchar(255) DEFAULT NULL,
  `numero_reclamation` varchar(255) NOT NULL,
  `type_reclamation` varchar(255) NOT NULL,
  `sujet_personnalise` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `priorite` enum('basse','normale','haute','urgente') DEFAULT 'normale',
  `statut` enum('en_attente','en_cours','en_revision','resolu','ferme','rejete') DEFAULT 'en_attente',
  `necessite_document` tinyint(1) DEFAULT 0,
  `documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`documents`)),
  `date_soumission` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `reclamations`
--

INSERT INTO `reclamations` (`id`, `user_id`, `user_type`, `user_email`, `user_telephone`, `numero_reclamation`, `type_reclamation`, `sujet_personnalise`, `description`, `priorite`, `statut`, `necessite_document`, `documents`, `date_soumission`, `created_at`, `updated_at`) VALUES
(40, 5, 'agent', 'lloyddarrylobg@gmail.com', '+24177651601', 'REC-202508-0008cf', 'pension', NULL, 'salutttttttt', 'urgente', 'en_attente', 1, '[{\"nom_original\":\"Good-vibe-good-life-Delphine-Billaut-Leduc_-2020-E\\u0301ditions-Leduc_s-9781028518005-f3cb24cda6257940900.pdf\",\"nom_stocke\":\"1755082512_689c6f1070638.pdf\",\"chemin\":\"reclamations\\/5\\/1755082512_689c6f1070638.pdf\",\"url\":\"\\/storage\\/reclamations\\/5\\/1755082512_689c6f1070638.pdf\",\"taille\":2229194,\"type\":\"pdf\",\"date_upload\":\"2025-08-13 10:55:12\"}]', '2025-08-13 09:55:12', '2025-08-13 09:55:12', '2025-08-13 09:55:12'),
(42, 5, 'agent', 'lloyddarrylobg@gmail.com', '+24177651601', 'REC-202508-00033b', 'pension', NULL, 'tttttttttttttteeeeeeeeeeeessssssssssssyyyyyyyyyyyyyy', 'urgente', 'en_attente', 1, '[{\"nom_original\":\"rapport_securite_wordpress_final_correct.pdf\",\"nom_stocke\":\"1755087929_689c8439889ac.pdf\",\"chemin\":\"reclamations\\/5\\/1755087929_689c8439889ac.pdf\",\"url\":\"\\/storage\\/reclamations\\/5\\/1755087929_689c8439889ac.pdf\",\"taille\":4320,\"type\":\"pdf\",\"date_upload\":\"2025-08-13 12:25:29\"}]', '2025-08-13 11:25:29', '2025-08-13 11:25:29', '2025-08-13 11:25:29'),
(44, 5, 'agent', 'lloyddarrylobg@gmail.com', '+24177651601', 'REC-202508-0004f3', 'cotisation', NULL, 'Petit test pour la réclamation.', 'urgente', 'en_attente', 1, '[{\"nom_original\":\"PasseportLloyd_compressed.pdf\",\"nom_stocke\":\"1756282936_68aec0388e877.pdf\",\"chemin\":\"reclamations\\/5\\/1756282936_68aec0388e877.pdf\",\"url\":\"\\/storage\\/reclamations\\/5\\/1756282936_68aec0388e877.pdf\",\"taille\":252106,\"type\":\"pdf\",\"date_upload\":\"2025-08-27 08:22:17\"},{\"nom_original\":\"PasseportLloyd.pdf\",\"nom_stocke\":\"1756282937_68aec03933bc4.pdf\",\"chemin\":\"reclamations\\/5\\/1756282937_68aec03933bc4.pdf\",\"url\":\"\\/storage\\/reclamations\\/5\\/1756282937_68aec03933bc4.pdf\",\"taille\":1062950,\"type\":\"pdf\",\"date_upload\":\"2025-08-27 08:22:17\"},{\"nom_original\":\"Actes de naissance FN-3_compressed.pdf\",\"nom_stocke\":\"1756282937_68aec039342b0.pdf\",\"chemin\":\"reclamations\\/5\\/1756282937_68aec039342b0.pdf\",\"url\":\"\\/storage\\/reclamations\\/5\\/1756282937_68aec039342b0.pdf\",\"taille\":314081,\"type\":\"pdf\",\"date_upload\":\"2025-08-27 08:22:17\"}]', '2025-08-27 07:22:16', '2025-08-27 07:22:17', '2025-08-27 07:22:17');

-- --------------------------------------------------------

--
-- Structure de la table `reclamation_historique`
--

CREATE TABLE `reclamation_historique` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `reclamation_id` bigint(20) UNSIGNED NOT NULL,
  `ancien_statut` varchar(255) DEFAULT NULL,
  `nouveau_statut` varchar(255) NOT NULL,
  `commentaire` text DEFAULT NULL,
  `modifie_par` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `reclamation_historique`
--

INSERT INTO `reclamation_historique` (`id`, `reclamation_id`, `ancien_statut`, `nouveau_statut`, `commentaire`, `modifie_par`, `created_at`, `updated_at`) VALUES
(2, 44, NULL, 'en_attente', 'Réclamation créée', 'Système', '2025-08-27 07:22:17', '2025-08-27 07:22:17');

-- --------------------------------------------------------

--
-- Structure de la table `rendez_vous_demandes`
--

CREATE TABLE `rendez_vous_demandes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `user_type` enum('agent','retraite') NOT NULL,
  `user_email` varchar(255) NOT NULL,
  `user_telephone` varchar(255) DEFAULT NULL,
  `user_nom` varchar(255) NOT NULL,
  `user_prenoms` varchar(255) NOT NULL,
  `numero_demande` varchar(255) NOT NULL,
  `date_demandee` date NOT NULL,
  `heure_demandee` time NOT NULL,
  `motif` enum('probleme_cotisations','questions_pension','mise_a_jour_dossier','reclamation_complexe','autre') NOT NULL,
  `motif_autre` varchar(255) DEFAULT NULL,
  `commentaires` text DEFAULT NULL,
  `statut` enum('en_attente','accepte','refuse','reporte','annule') NOT NULL DEFAULT 'en_attente',
  `reponse_admin` text DEFAULT NULL,
  `date_reponse` datetime DEFAULT NULL,
  `date_rdv_confirme` datetime DEFAULT NULL,
  `lieu_rdv` varchar(255) DEFAULT NULL,
  `email_admin_envoye` tinyint(1) NOT NULL DEFAULT 0,
  `email_user_reponse_envoye` tinyint(1) NOT NULL DEFAULT 0,
  `rappel_j1_envoye` tinyint(1) NOT NULL DEFAULT 0,
  `date_rappel_j1` timestamp NULL DEFAULT NULL,
  `rappel_j7_envoye` tinyint(1) NOT NULL DEFAULT 0,
  `date_rappel_j7` timestamp NULL DEFAULT NULL,
  `notification_dashboard_lue` tinyint(1) NOT NULL DEFAULT 0,
  `date_soumission` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `rendez_vous_demandes`
--

INSERT INTO `rendez_vous_demandes` (`id`, `user_id`, `user_type`, `user_email`, `user_telephone`, `user_nom`, `user_prenoms`, `numero_demande`, `date_demandee`, `heure_demandee`, `motif`, `motif_autre`, `commentaires`, `statut`, `reponse_admin`, `date_reponse`, `date_rdv_confirme`, `lieu_rdv`, `email_admin_envoye`, `email_user_reponse_envoye`, `rappel_j1_envoye`, `date_rappel_j1`, `rappel_j7_envoye`, `date_rappel_j7`, `notification_dashboard_lue`, `date_soumission`, `created_at`, `updated_at`) VALUES
(5, 5, 'agent', 'lloyddarrylobg@gmail.com', '+24177651601', 'MINTSA', 'Bernard Thierry', 'RDV-202508-0002ed', '2025-08-27', '11:00:00', 'questions_pension', NULL, 'test rapide d\'annulation de rdv par mail.', 'annule', 'Annulé par l\'utilisateur: Je me suis trompé de date dsl.', '2025-08-21 08:35:56', NULL, NULL, 1, 0, 0, NULL, 0, NULL, 0, '2025-08-21 08:35:28', '2025-08-21 07:35:28', '2025-08-21 07:35:56'),
(6, 5, 'agent', 'lloyddarrylobg@gmail.com', '+24177651601', 'MINTSA', 'Bernard Thierry', 'RDV-202508-000333', '2025-09-01', '12:30:00', 'probleme_cotisations', NULL, 'petit test', 'en_attente', NULL, NULL, NULL, NULL, 1, 0, 0, NULL, 0, NULL, 0, '2025-08-27 08:27:26', '2025-08-27 07:27:26', '2025-08-27 07:27:31'),
(7, 5, 'agent', 'lloyddarrylobg@gmail.com', '+24177651601', 'MINTSA', 'Bernard Thierry', 'RDV-202508-000424', '2025-09-01', '12:00:00', 'mise_a_jour_dossier', NULL, NULL, 'en_attente', NULL, NULL, NULL, NULL, 1, 0, 0, NULL, 0, NULL, 0, '2025-08-28 13:29:16', '2025-08-28 12:29:16', '2025-08-28 12:29:23');

-- --------------------------------------------------------

--
-- Structure de la table `retraites`
--

CREATE TABLE `retraites` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `numero_pension` varchar(255) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenoms` varchar(255) NOT NULL,
  `date_naissance` date NOT NULL,
  `date_retraite` date NOT NULL,
  `duree_service_mois` decimal(8,2) DEFAULT NULL,
  `taux_liquidation` decimal(5,2) DEFAULT NULL,
  `salaire_reference` decimal(12,2) DEFAULT NULL,
  `pension_base` decimal(12,2) DEFAULT NULL,
  `bonifications_totales` decimal(12,2) NOT NULL DEFAULT 0.00,
  `ancien_poste` varchar(255) NOT NULL,
  `ancienne_direction` varchar(255) NOT NULL,
  `parcours_professionnel` text DEFAULT NULL,
  `montant_pension` decimal(15,2) DEFAULT NULL,
  `indice_retraite` int(11) DEFAULT NULL,
  `corps` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telephone` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `verification_code` varchar(6) DEFAULT NULL,
  `verification_code_expires_at` timestamp NULL DEFAULT NULL,
  `first_login` tinyint(1) NOT NULL DEFAULT 1,
  `password_changed` tinyint(1) NOT NULL DEFAULT 0,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `phone_verified_at` timestamp NULL DEFAULT NULL,
  `status` enum('actif','suspendu','decede') NOT NULL DEFAULT 'actif',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `num_affiliation` varchar(255) DEFAULT NULL,
  `situation_matrimoniale` varchar(255) DEFAULT NULL,
  `sexe` varchar(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `retraites`
--

INSERT INTO `retraites` (`id`, `numero_pension`, `nom`, `prenoms`, `date_naissance`, `date_retraite`, `duree_service_mois`, `taux_liquidation`, `salaire_reference`, `pension_base`, `bonifications_totales`, `ancien_poste`, `ancienne_direction`, `parcours_professionnel`, `montant_pension`, `indice_retraite`, `corps`, `email`, `telephone`, `password`, `verification_code`, `verification_code_expires_at`, `first_login`, `password_changed`, `email_verified_at`, `phone_verified_at`, `status`, `is_active`, `created_at`, `updated_at`, `num_affiliation`, `situation_matrimoniale`, `sexe`) VALUES
(1, '2020001234', 'MOUSSOUNDA', 'Albert Jean', '1955-08-15', '2020-08-31', NULL, NULL, NULL, NULL, 0.00, 'Directeur Général', 'Direction Générale', 'Directeur Général (2010-2020), Directeur Adjoint (2005-2010), Chef de Service (2000-2005)', 850000.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, NULL, 'actif', 1, '2025-07-11 07:52:17', '2025-07-31 11:35:35', NULL, NULL, 'M'),
(2, '2019005678', 'NTOUTOUME', 'Henriette Solange', '1959-12-03', '2019-12-31', NULL, NULL, NULL, NULL, 0.00, 'Directrice des Prestations Familiales', 'Direction des Prestations Familiales', 'Directrice (2015-2019), Chef de Service (2010-2015), Attachée (2005-2010)', 720000.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, NULL, 'actif', 1, '2025-07-11 07:52:17', '2025-08-06 07:25:54', NULL, 'Veuf(ve)', 'F'),
(3, '2021009876', 'KOUMBA', 'François Xavier', '1956-04-22', '2021-04-30', NULL, NULL, NULL, NULL, 0.00, 'Directeur Financier et Comptable', 'Direction Financière et Comptable', 'Directeur Financier (2012-2021), Chef Comptable (2008-2012), Contrôleur (2003-2008)', 780000.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, NULL, 'actif', 1, '2025-07-11 07:52:17', '2025-08-06 07:25:54', NULL, 'Marié(e)', 'M'),
(4, '2018012345', 'MOUNANGA', 'Célestine Rose', '1958-11-10', '2018-11-30', NULL, NULL, NULL, NULL, 0.00, 'Secrétaire Générale', 'Secrétariat Général', 'Secrétaire Générale (2010-2018), Chef de Cabinet (2005-2010), Secrétaire de Direction (2000-2005)', 650000.00, NULL, NULL, 'rose@gmail.com', '+24177651601', '$2y$12$4ggE2TCmzLdS9ZGsvO2b..w0nGDckJwp1hRWVOdDLHx7DeDOPzKam', NULL, NULL, 0, 1, NULL, '2025-08-05 12:41:38', 'actif', 1, '2025-07-11 07:52:17', '2025-08-06 07:25:54', NULL, 'Divorcé(e)', 'F'),
(5, '2022003456', 'NDONG', 'Paul Martin', '1957-06-18', '2022-06-30', NULL, NULL, NULL, NULL, 0.00, 'Chef de Service Juridique', 'Direction des Affaires Juridiques', 'Chef de Service Juridique (2015-2022), Conseiller Juridique (2010-2015), Attaché Juridique (2005-2010)', 580000.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, NULL, 'actif', 1, '2025-07-11 07:52:17', '2025-08-05 11:05:58', NULL, NULL, 'M'),
(6, '2020007890', 'ELLA', 'Catherine Micheline', '1960-02-14', '2020-02-29', NULL, NULL, NULL, NULL, 0.00, 'Chef de Service Ressources Humaines', 'Direction des Ressources Humaines', 'Chef de Service RH (2012-2020), Gestionnaire RH (2008-2012), Assistante RH (2003-2008)', 520000.00, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, 0, NULL, NULL, 'actif', 1, '2025-07-11 07:52:17', '2025-08-05 08:30:42', NULL, NULL, 'F');

-- --------------------------------------------------------

--
-- Structure de la table `retraites_details`
--

CREATE TABLE `retraites_details` (
  `retraite_id` bigint(20) UNSIGNED NOT NULL,
  `numero_ordre` int(11) NOT NULL,
  `position` varchar(255) NOT NULL,
  `etablissement` varchar(255) DEFAULT NULL,
  `corps` varchar(255) DEFAULT NULL,
  `grade` varchar(255) DEFAULT NULL,
  `indice` varchar(255) DEFAULT NULL,
  `retenue` decimal(15,2) DEFAULT NULL,
  `nombre_mois` int(11) DEFAULT NULL,
  `regime` int(11) DEFAULT NULL,
  `sous_regime` varchar(255) DEFAULT NULL,
  `annuite` decimal(15,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('JBMJyZZ3NsMkW6TKfLcDJZtay4lKOXt3bVvdQ0nq', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiV2p3N0dVZERQbnRWZVdRU2FjZHE3Q25ib3A3aGpNMW9pOFdkREs4UiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1752228189),
('ohoyFuQ1t06GGs2nO7llKWYEzI00Q2xJ3GCtiPGk', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/138.0.0.0 Safari/537.36 Edg/138.0.0.0', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoiSnNVZEtXSXRyOTdsUTBBeVd6U25rVXdBTlF0Z1ZFNzF2Q05qQTBWYiI7czo5OiJfcHJldmlvdXMiO2E6MTp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMCI7fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=', 1752060564);

-- --------------------------------------------------------

--
-- Structure de la table `simulations_pension`
--

CREATE TABLE `simulations_pension` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `agent_id` bigint(20) UNSIGNED NOT NULL,
  `date_simulation` date NOT NULL,
  `date_retraite_prevue` date NOT NULL,
  `duree_service_simulee` decimal(8,2) NOT NULL,
  `indice_simule` int(11) NOT NULL,
  `salaire_reference` decimal(12,2) NOT NULL,
  `taux_liquidation` decimal(5,2) NOT NULL,
  `pension_base` decimal(12,2) NOT NULL,
  `bonifications` decimal(12,2) NOT NULL DEFAULT 0.00,
  `pension_totale` decimal(12,2) NOT NULL,
  `coefficient_temporel` decimal(5,2) DEFAULT NULL,
  `pension_apres_coefficient` decimal(12,2) DEFAULT NULL,
  `annee_pension` int(11) DEFAULT NULL,
  `methode_calcul` varchar(255) NOT NULL DEFAULT 'Article_94',
  `parametres_utilises` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parametres_utilises`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `simulations_pension`
--

INSERT INTO `simulations_pension` (`id`, `agent_id`, `date_simulation`, `date_retraite_prevue`, `duree_service_simulee`, `indice_simule`, `salaire_reference`, `taux_liquidation`, `pension_base`, `bonifications`, `pension_totale`, `coefficient_temporel`, `pension_apres_coefficient`, `annee_pension`, `methode_calcul`, `parametres_utilises`, `created_at`, `updated_at`) VALUES
(1, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 08:36:24', '2025-08-01 08:36:24'),
(2, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 08:36:25', '2025-08-01 08:36:25'),
(3, 5, '2025-08-01', '2048-04-28', 33.46, 1200, 600000.00, 60.23, 361357.38, 10840.72, 372198.10, 100.00, 361357.38, 2048, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2048,\"pension_apres_coefficient\":361357.37704918033}', '2025-08-01 08:39:27', '2025-08-01 08:39:27'),
(4, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 08:39:36', '2025-08-01 08:39:36'),
(5, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 08:39:37', '2025-08-01 08:39:37'),
(6, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 08:56:28', '2025-08-01 08:56:28'),
(7, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 08:56:28', '2025-08-01 08:56:28'),
(8, 5, '2025-08-01', '2050-03-26', 35.37, 1200, 600000.00, 63.66, 381964.93, 11458.95, 393423.88, 100.00, 381964.93, 2050, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2050,\"pension_apres_coefficient\":381964.9315068493}', '2025-08-01 08:57:53', '2025-08-01 08:57:53'),
(9, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:08:34', '2025-08-01 09:08:34'),
(10, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:08:37', '2025-08-01 09:08:37'),
(11, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:09:32', '2025-08-01 09:09:32'),
(12, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:09:37', '2025-08-01 09:09:37'),
(13, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:09:41', '2025-08-01 09:09:41'),
(14, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:09:45', '2025-08-01 09:09:45'),
(15, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:09:49', '2025-08-01 09:09:49'),
(16, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:10:32', '2025-08-01 09:10:32'),
(17, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:10:34', '2025-08-01 09:10:34'),
(18, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:10:41', '2025-08-01 09:10:41'),
(19, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:10:43', '2025-08-01 09:10:43'),
(20, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:10:49', '2025-08-01 09:10:49'),
(21, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:10:57', '2025-08-01 09:10:57'),
(22, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:11:01', '2025-08-01 09:11:01'),
(23, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:11:06', '2025-08-01 09:11:06'),
(24, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:11:12', '2025-08-01 09:11:12'),
(25, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:11:16', '2025-08-01 09:11:16'),
(26, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:11:18', '2025-08-01 09:11:18'),
(27, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:11:36', '2025-08-01 09:11:36'),
(28, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:11:36', '2025-08-01 09:11:36'),
(29, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:13:10', '2025-08-01 09:13:10'),
(30, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:13:10', '2025-08-01 09:13:10'),
(31, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:16:16', '2025-08-01 09:16:16'),
(32, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:16:16', '2025-08-01 09:16:16'),
(33, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:24:34', '2025-08-01 09:24:34'),
(34, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:24:42', '2025-08-01 09:24:42'),
(35, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:24:44', '2025-08-01 09:24:44'),
(36, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:24:47', '2025-08-01 09:24:47'),
(37, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:40:38', '2025-08-01 09:40:38'),
(38, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:41:48', '2025-08-01 09:41:48'),
(39, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:51:19', '2025-08-01 09:51:19'),
(40, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:51:19', '2025-08-01 09:51:19'),
(41, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 09:51:19', '2025-08-01 09:51:19'),
(42, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 10:01:30', '2025-08-01 10:01:30'),
(43, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 10:01:30', '2025-08-01 10:01:30'),
(44, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 10:14:29', '2025-08-01 10:14:29'),
(45, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 10:14:30', '2025-08-01 10:14:30'),
(46, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 10:15:31', '2025-08-01 10:15:31'),
(47, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 10:15:31', '2025-08-01 10:15:31'),
(48, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 10:31:52', '2025-08-01 10:31:52'),
(49, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 10:31:52', '2025-08-01 10:31:52'),
(50, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 10:39:15', '2025-08-01 10:39:15'),
(51, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 10:39:15', '2025-08-01 10:39:15'),
(52, 5, '2025-08-01', '2040-03-15', 25.34, 1200, 600000.00, 45.61, 273659.02, 8209.77, 281868.79, 100.00, 273659.02, 2040, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2040,\"pension_apres_coefficient\":273659.01639344264}', '2025-08-01 10:43:57', '2025-08-01 10:43:57'),
(53, 5, '2025-08-01', '2028-03-15', 13.34, 1200, 600000.00, 0.00, 0.00, 0.00, 0.00, 98.00, 0.00, 2028, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":98,\"annee_pension\":2028,\"pension_apres_coefficient\":0}', '2025-08-01 10:51:34', '2025-08-01 10:51:34'),
(54, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 10:53:47', '2025-08-01 10:53:47'),
(55, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 10:53:47', '2025-08-01 10:53:47'),
(56, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 11:52:04', '2025-08-01 11:52:04'),
(57, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 11:52:04', '2025-08-01 11:52:04'),
(58, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:06:30', '2025-08-01 12:06:30'),
(59, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:06:32', '2025-08-01 12:06:32'),
(60, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:06:35', '2025-08-01 12:06:35'),
(61, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:06:37', '2025-08-01 12:06:37'),
(62, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:06:39', '2025-08-01 12:06:39'),
(63, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:06:42', '2025-08-01 12:06:42'),
(64, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:06:43', '2025-08-01 12:06:43'),
(65, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:06:45', '2025-08-01 12:06:45'),
(66, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:06:47', '2025-08-01 12:06:47'),
(67, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:06:51', '2025-08-01 12:06:51'),
(68, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:06:54', '2025-08-01 12:06:54'),
(69, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:06:56', '2025-08-01 12:06:56'),
(70, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:07:32', '2025-08-01 12:07:32'),
(71, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:09:40', '2025-08-01 12:09:40'),
(72, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:10:45', '2025-08-01 12:10:45'),
(73, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:11:03', '2025-08-01 12:11:03'),
(74, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:11:03', '2025-08-01 12:11:03'),
(75, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:12:15', '2025-08-01 12:12:15'),
(76, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:12:16', '2025-08-01 12:12:16'),
(77, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:12:17', '2025-08-01 12:12:17'),
(78, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:12:30', '2025-08-01 12:12:30'),
(79, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:12:51', '2025-08-01 12:12:51'),
(80, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:13:35', '2025-08-01 12:13:35'),
(81, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:13:39', '2025-08-01 12:13:39'),
(82, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:13:41', '2025-08-01 12:13:41'),
(83, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:13:46', '2025-08-01 12:13:46'),
(84, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:13:53', '2025-08-01 12:13:53'),
(85, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:14:23', '2025-08-01 12:14:23'),
(86, 5, '2025-08-01', '2042-04-30', 27.46, 1200, 600000.00, 49.43, 296600.55, 8898.02, 305498.56, 100.00, 296600.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":296600.5479452055}', '2025-08-01 12:14:26', '2025-08-01 12:14:26'),
(87, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 12:50:10', '2025-08-01 12:50:10'),
(88, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 12:50:14', '2025-08-01 12:50:14'),
(89, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 12:50:18', '2025-08-01 12:50:18'),
(90, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 12:50:20', '2025-08-01 12:50:20'),
(91, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 12:50:22', '2025-08-01 12:50:22'),
(92, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 12:53:28', '2025-08-01 12:53:28'),
(93, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 12:53:28', '2025-08-01 12:53:28'),
(94, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 12:53:34', '2025-08-01 12:53:34'),
(95, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 12:53:35', '2025-08-01 12:53:35'),
(96, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 12:55:24', '2025-08-01 12:55:24'),
(97, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 12:55:25', '2025-08-01 12:55:25'),
(98, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 12:55:34', '2025-08-01 12:55:34'),
(99, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 12:55:34', '2025-08-01 12:55:34'),
(100, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 12:56:44', '2025-08-01 12:56:44'),
(101, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 12:56:45', '2025-08-01 12:56:45'),
(102, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 13:01:04', '2025-08-01 13:01:04'),
(103, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 13:01:42', '2025-08-01 13:01:42'),
(104, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 13:16:13', '2025-08-01 13:16:13'),
(105, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 13:18:40', '2025-08-01 13:18:40'),
(106, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 13:18:40', '2025-08-01 13:18:40'),
(107, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 13:42:35', '2025-08-01 13:42:35'),
(108, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 13:42:40', '2025-08-01 13:42:40'),
(109, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 13:42:41', '2025-08-01 13:42:41'),
(110, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 13:43:36', '2025-08-01 13:43:36'),
(111, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 13:43:38', '2025-08-01 13:43:38'),
(112, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 13:43:40', '2025-08-01 13:43:40'),
(113, 5, '2025-08-01', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-01 13:43:42', '2025-08-01 13:43:42'),
(114, 5, '2025-08-03', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-03 12:01:13', '2025-08-03 12:01:13'),
(115, 5, '2025-08-03', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-03 12:01:14', '2025-08-03 12:01:14'),
(116, 5, '2025-08-03', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-03 12:01:48', '2025-08-03 12:01:48'),
(117, 5, '2025-08-03', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-03 12:03:02', '2025-08-03 12:03:02'),
(118, 5, '2025-08-03', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-03 12:03:06', '2025-08-03 12:03:06'),
(119, 5, '2025-08-03', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-03 12:03:10', '2025-08-03 12:03:10'),
(120, 5, '2025-08-03', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-03 12:03:18', '2025-08-03 12:03:18'),
(121, 5, '2025-08-03', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-03 12:03:19', '2025-08-03 12:03:19'),
(122, 5, '2025-08-05', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-05 09:07:22', '2025-08-05 09:07:22'),
(123, 5, '2025-08-05', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-05 09:07:22', '2025-08-05 09:07:22'),
(124, 5, '2025-08-05', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-05 10:22:49', '2025-08-05 10:22:49'),
(125, 5, '2025-08-05', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 9060.02, 311060.56, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-05 10:22:49', '2025-08-05 10:22:49'),
(126, 2, '2025-08-05', '2040-03-08', 25.63, 1050, 525000.00, 46.14, 242240.16, 0.00, 242240.16, 100.00, 242240.16, 2040, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2040,\"pension_apres_coefficient\":242240.16393442627,\"principe_annuite\":true}', '2025-08-05 10:57:00', '2025-08-05 10:57:00'),
(127, 2, '2025-08-05', '2040-03-08', 25.63, 1050, 525000.00, 46.14, 242240.16, 0.00, 242240.16, 100.00, 242240.16, 2040, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2040,\"pension_apres_coefficient\":242240.16393442627,\"principe_annuite\":true}', '2025-08-05 10:57:00', '2025-08-05 10:57:00'),
(128, 5, '2025-08-06', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 15100.03, 317100.58, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-06 13:28:09', '2025-08-06 13:28:09'),
(129, 5, '2025-08-06', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 15100.03, 317100.58, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-06 13:28:09', '2025-08-06 13:28:09'),
(130, 5, '2025-08-06', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 15100.03, 317100.58, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-06 13:29:44', '2025-08-06 13:29:44'),
(131, 5, '2025-08-06', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 15100.03, 317100.58, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-06 13:29:44', '2025-08-06 13:29:44'),
(132, 5, '2025-08-06', '2050-06-25', 36.62, 1200, 600000.00, 65.91, 395457.53, 19772.88, 415230.41, 100.00, 395457.53, 2050, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2050,\"pension_apres_coefficient\":395457.5342465753,\"principe_annuite\":true}', '2025-08-06 13:30:34', '2025-08-06 13:30:34'),
(133, 5, '2025-08-06', '2028-06-25', 14.62, 1200, 600000.00, 0.00, 0.00, 0.00, 0.00, 98.00, 0.00, 2028, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":98,\"annee_pension\":2028,\"pension_apres_coefficient\":0,\"principe_annuite\":true}', '2025-08-06 13:30:58', '2025-08-06 13:30:58'),
(134, 5, '2025-08-06', '2028-06-25', 14.62, 1200, 600000.00, 0.00, 0.00, 0.00, 0.00, 98.00, 0.00, 2028, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":98,\"annee_pension\":2028,\"pension_apres_coefficient\":0,\"principe_annuite\":true}', '2025-08-06 13:31:00', '2025-08-06 13:31:00'),
(135, 5, '2025-08-10', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 15100.03, 317100.58, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-10 15:55:20', '2025-08-10 15:55:20'),
(136, 5, '2025-08-10', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 15100.03, 317100.58, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-10 15:55:20', '2025-08-10 15:55:20'),
(137, 5, '2025-08-10', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 15100.03, 317100.58, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-10 15:56:16', '2025-08-10 15:56:16'),
(138, 5, '2025-08-10', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 15100.03, 317100.58, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-10 15:56:16', '2025-08-10 15:56:16'),
(139, 5, '2025-08-11', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 15100.03, 317100.58, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-11 09:51:27', '2025-08-11 09:51:27'),
(140, 5, '2025-08-11', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 15100.03, 317100.58, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-11 09:51:27', '2025-08-11 09:51:27'),
(141, 5, '2025-08-11', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 15100.03, 317100.58, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-11 10:39:02', '2025-08-11 10:39:02'),
(142, 5, '2025-08-11', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 15100.03, 317100.58, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-11 10:39:02', '2025-08-11 10:39:02'),
(143, 5, '2025-08-11', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 15100.03, 317100.58, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-11 10:39:13', '2025-08-11 10:39:13'),
(144, 5, '2025-08-14', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 15100.03, 317100.58, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-14 13:06:38', '2025-08-14 13:06:38'),
(145, 5, '2025-08-14', '2042-04-30', 27.96, 1200, 600000.00, 50.33, 302000.55, 15100.03, 317100.58, 100.00, 302000.55, 2042, 'Article_94', '{\"formule_taux\":\"annees_x_1.8\",\"coefficient_temporel\":100,\"annee_pension\":2042,\"pension_apres_coefficient\":302000.5479452055,\"principe_annuite\":true}', '2025-08-14 13:06:38', '2025-08-14 13:06:38');

-- --------------------------------------------------------

--
-- Structure de la table `simulation_pensions`
--

CREATE TABLE `simulation_pensions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `agents_matricule_solde_unique` (`matricule_solde`),
  ADD KEY `agents_matricule_solde_status_index` (`matricule_solde`,`status`),
  ADD KEY `agents_email_index` (`email`);

--
-- Index pour la table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`);

--
-- Index pour la table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`);

--
-- Index pour la table `carrieres`
--
ALTER TABLE `carrieres`
  ADD PRIMARY KEY (`id`),
  ADD KEY `carrieres_agent_id_date_carriere_index` (`agent_id`,`date_carriere`),
  ADD KEY `carrieres_matricule_assure_index` (`matricule_assure`);

--
-- Index pour la table `carriere_historiques`
--
ALTER TABLE `carriere_historiques`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `coefficients_temporels`
--
ALTER TABLE `coefficients_temporels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `coefficients_temporels_annee_unique` (`annee`);

--
-- Index pour la table `conjoints`
--
ALTER TABLE `conjoints`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conjoints_agent_id_index` (`agent_id`),
  ADD KEY `conjoints_matricule_conjoint_index` (`matricule_conjoint`),
  ADD KEY `conjoints_nag_conjoint_index` (`nag_conjoint`),
  ADD KEY `conjoints_retraite_statut_index` (`retraite_id`,`statut`);

--
-- Index pour la table `documents_retraites`
--
ALTER TABLE `documents_retraites`
  ADD PRIMARY KEY (`id`),
  ADD KEY `documents_retraites_retraite_id_type_document_index` (`retraite_id`,`type_document`),
  ADD KEY `documents_retraites_type_document_statut_index` (`type_document`,`statut`),
  ADD KEY `documents_retraites_date_expiration_statut_index` (`date_expiration`,`statut`);

--
-- Index pour la table `enfants`
--
ALTER TABLE `enfants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `enfants_enfant_id_agent_id_unique` (`enfant_id`,`agent_id`),
  ADD KEY `enfants_agent_id_index` (`agent_id`),
  ADD KEY `enfants_matricule_parent_index` (`matricule_parent`),
  ADD KEY `enfants_enfant_id_index` (`enfant_id`),
  ADD KEY `enfants_retraite_actif_index` (`retraite_id`,`actif`);

--
-- Index pour la table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Index pour la table `famille_tables`
--
ALTER TABLE `famille_tables`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `famille_tables_extend`
--
ALTER TABLE `famille_tables_extend`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `grilles_indiciaires`
--
ALTER TABLE `grilles_indiciaires`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grilles_indiciaires_type_grille_categorie_classe_unique` (`type_grille`,`categorie`,`classe`),
  ADD KEY `grilles_indiciaires_indice_nouveau_index` (`indice_nouveau`);

--
-- Index pour la table `grille_indiciaires`
--
ALTER TABLE `grille_indiciaires`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Index pour la table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `parametres_pension`
--
ALTER TABLE `parametres_pension`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `parametres_pension_code_parametre_unique` (`code_parametre`),
  ADD KEY `parametres_pension_code_parametre_is_active_index` (`code_parametre`,`is_active`),
  ADD KEY `parametres_pension_date_effet_date_fin_index` (`date_effet`,`date_fin`);

--
-- Index pour la table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Index pour la table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  ADD KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`);

--
-- Index pour la table `prestations_familiales`
--
ALTER TABLE `prestations_familiales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `prestations_familiales_enfant_id_statut_index` (`enfant_id`,`statut`);

--
-- Index pour la table `reclamations`
--
ALTER TABLE `reclamations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_reclamation` (`numero_reclamation`);

--
-- Index pour la table `reclamation_historique`
--
ALTER TABLE `reclamation_historique`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reclamation_historique_reclamation_id_index` (`reclamation_id`);

--
-- Index pour la table `rendez_vous_demandes`
--
ALTER TABLE `rendez_vous_demandes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_demande` (`numero_demande`),
  ADD KEY `rendez_vous_demandes_user_id_user_type_index` (`user_id`,`user_type`),
  ADD KEY `rendez_vous_demandes_date_demandee_index` (`date_demandee`),
  ADD KEY `rendez_vous_demandes_statut_index` (`statut`),
  ADD KEY `rendez_vous_demandes_numero_demande_index` (`numero_demande`);

--
-- Index pour la table `retraites`
--
ALTER TABLE `retraites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `retraites_numero_pension_unique` (`numero_pension`),
  ADD KEY `retraites_numero_pension_status_index` (`numero_pension`,`status`),
  ADD KEY `retraites_email_index` (`email`),
  ADD KEY `retraites_date_naissance_index` (`date_naissance`);

--
-- Index pour la table `retraites_details`
--
ALTER TABLE `retraites_details`
  ADD KEY `retraite_id` (`retraite_id`);

--
-- Index pour la table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Index pour la table `simulations_pension`
--
ALTER TABLE `simulations_pension`
  ADD PRIMARY KEY (`id`),
  ADD KEY `simulations_pension_agent_id_date_simulation_index` (`agent_id`,`date_simulation`);

--
-- Index pour la table `simulation_pensions`
--
ALTER TABLE `simulation_pensions`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `agents`
--
ALTER TABLE `agents`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT pour la table `carrieres`
--
ALTER TABLE `carrieres`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `carriere_historiques`
--
ALTER TABLE `carriere_historiques`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `coefficients_temporels`
--
ALTER TABLE `coefficients_temporels`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `conjoints`
--
ALTER TABLE `conjoints`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `documents_retraites`
--
ALTER TABLE `documents_retraites`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `enfants`
--
ALTER TABLE `enfants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `famille_tables`
--
ALTER TABLE `famille_tables`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `famille_tables_extend`
--
ALTER TABLE `famille_tables_extend`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `grilles_indiciaires`
--
ALTER TABLE `grilles_indiciaires`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `grille_indiciaires`
--
ALTER TABLE `grille_indiciaires`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT pour la table `parametres_pension`
--
ALTER TABLE `parametres_pension`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `personal_access_tokens`
--
ALTER TABLE `personal_access_tokens`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=194;

--
-- AUTO_INCREMENT pour la table `prestations_familiales`
--
ALTER TABLE `prestations_familiales`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `reclamations`
--
ALTER TABLE `reclamations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT pour la table `reclamation_historique`
--
ALTER TABLE `reclamation_historique`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `rendez_vous_demandes`
--
ALTER TABLE `rendez_vous_demandes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `retraites`
--
ALTER TABLE `retraites`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `simulations_pension`
--
ALTER TABLE `simulations_pension`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=146;

--
-- AUTO_INCREMENT pour la table `simulation_pensions`
--
ALTER TABLE `simulation_pensions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `carrieres`
--
ALTER TABLE `carrieres`
  ADD CONSTRAINT `carrieres_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `conjoints`
--
ALTER TABLE `conjoints`
  ADD CONSTRAINT `conjoints_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `conjoints_retraite_id_foreign` FOREIGN KEY (`retraite_id`) REFERENCES `retraites` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `documents_retraites`
--
ALTER TABLE `documents_retraites`
  ADD CONSTRAINT `documents_retraites_retraite_id_foreign` FOREIGN KEY (`retraite_id`) REFERENCES `retraites` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `enfants`
--
ALTER TABLE `enfants`
  ADD CONSTRAINT `enfants_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `enfants_retraite_id_foreign` FOREIGN KEY (`retraite_id`) REFERENCES `retraites` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `prestations_familiales`
--
ALTER TABLE `prestations_familiales`
  ADD CONSTRAINT `prestations_familiales_enfant_id_foreign` FOREIGN KEY (`enfant_id`) REFERENCES `enfants` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `reclamation_historique`
--
ALTER TABLE `reclamation_historique`
  ADD CONSTRAINT `reclamation_historique_reclamation_id_foreign` FOREIGN KEY (`reclamation_id`) REFERENCES `reclamations` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `retraites_details`
--
ALTER TABLE `retraites_details`
  ADD CONSTRAINT `retraites_details_ibfk_1` FOREIGN KEY (`retraite_id`) REFERENCES `retraites` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `simulations_pension`
--
ALTER TABLE `simulations_pension`
  ADD CONSTRAINT `simulations_pension_agent_id_foreign` FOREIGN KEY (`agent_id`) REFERENCES `agents` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
