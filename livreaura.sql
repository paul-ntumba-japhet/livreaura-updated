-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 08, 2026 at 09:53 PM
-- Server version: 8.4.8
-- PHP Version: 8.4.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `livreaura`
--

-- Le serveur WAMP utilise MyISAM par défaut. phpMyAdmin n'applique pas
-- toujours le ENGINE=InnoDB de ce dump, et MyISAM refuse un index sur une
-- colonne générée virtuelle (erreur 1478).
SET SESSION default_storage_engine = InnoDB;
ALTER DATABASE `livreaura` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin;

-- --------------------------------------------------------

--
-- Table structure for table `acces_documents_support`
--

DROP TABLE IF EXISTS `acces_documents_support`;
CREATE TABLE IF NOT EXISTS `acces_documents_support` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `demande_support_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `facture_vente_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `facture_commission_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `avoir_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `recu_paiement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `debut` datetime(6) NOT NULL,
  `fin` datetime(6) NOT NULL,
  `revoque_a` datetime(6) DEFAULT NULL,
  `autorise_par` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_acces_documents_support_df7db26d23` (`demande_support_id`),
  KEY `ix_acces_documents_support_940f1f0c40` (`compte_id`),
  KEY `ix_acces_documents_support_3b530ef393` (`facture_vente_id`),
  KEY `ix_acces_documents_support_eb7b937696` (`facture_commission_id`),
  KEY `ix_acces_documents_support_42318f3fe1` (`avoir_id`),
  KEY `ix_acces_documents_support_75359aed2d` (`recu_paiement_id`),
  KEY `ix_acces_documents_support_36e5a78461` (`autorise_par`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `acces_dossiers_conformite`
--

DROP TABLE IF EXISTS `acces_dossiers_conformite`;
CREATE TABLE IF NOT EXISTS `acces_dossiers_conformite` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `litige_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `debut` datetime(6) NOT NULL,
  `fin` datetime(6) DEFAULT NULL,
  `revoque_a` datetime(6) DEFAULT NULL,
  `reference_autorisation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_acces_dossiers_conformite_940f1f0c40` (`compte_id`),
  KEY `ix_acces_dossiers_conformite_855a2083e7` (`signalement_id`),
  KEY `ix_acces_dossiers_conformite_ac97dff02e` (`litige_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `acces_dossiers_publication`
--

DROP TABLE IF EXISTS `acces_dossiers_publication`;
CREATE TABLE IF NOT EXISTS `acces_dossiers_publication` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `debut` datetime(6) NOT NULL,
  `fin` datetime(6) DEFAULT NULL,
  `revoque_a` datetime(6) DEFAULT NULL,
  `reference_autorisation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `origine_systeme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_acces_dossiers_publication_0259b9fde9` (`dossier_id`),
  KEY `ix_acces_dossiers_publication_940f1f0c40` (`compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `acces_reponse_conformite`
--

DROP TABLE IF EXISTS `acces_reponse_conformite`;
CREATE TABLE IF NOT EXISTS `acces_reponse_conformite` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `partie_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_acces` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_secret` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `preuve_verification` json NOT NULL,
  `portee` json NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  `expire_a` datetime(6) NOT NULL,
  `revoque_a` datetime(6) DEFAULT NULL,
  `reference_autorisation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_acces_reponse_conformite_51df92f810` (`reference_acces`),
  UNIQUE KEY `uq_acces_reponse_conformite_2d9bf671d7` (`id`,`partie_id`),
  KEY `ix_acces_reponse_conformite_fa9d31b5b8` (`partie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `actions_conservation`
--

DROP TABLE IF EXISTS `actions_conservation`;
CREATE TABLE IF NOT EXISTS `actions_conservation` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `objet_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `application_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `action` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  `preuve_eligibilite` json NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `confirme_a` datetime(6) DEFAULT NULL,
  `resultat` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_actions_conservation_52367a6622` (`reference`),
  KEY `ix_actions_conservation_1b624651ed` (`statut`,`cree_a`),
  KEY `ix_actions_conservation_8df4fbd3d7` (`application_id`,`objet_id`),
  KEY `ix_actions_conservation_afd38cd64f` (`objet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `adaptateurs_conservation`
--

DROP TABLE IF EXISTS `adaptateurs_conservation`;
CREATE TABLE IF NOT EXISTS `adaptateurs_conservation` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `proprietaire` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type_objet` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version` bigint NOT NULL,
  `categorie` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `schema_reference` json NOT NULL,
  `capacite_barriere` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `preuve_validation` text COLLATE utf8mb4_0900_bin,
  `valide_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_adaptateurs_conservation_0ccb3db80a` (`proprietaire`,`type_objet`,`version`),
  UNIQUE KEY `uq_adaptateurs_conservation_718908be78` (`id`,`proprietaire`,`type_objet`,`categorie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `adhesions_organisation`
--

DROP TABLE IF EXISTS `adhesions_organisation`;
CREATE TABLE IF NOT EXISTS `adhesions_organisation` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `organisation_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `fonction` text COLLATE utf8mb4_0900_bin NOT NULL,
  `principal` tinyint UNSIGNED NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `origine` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  `active_a` datetime(6) DEFAULT NULL,
  `revoque_a` datetime(6) DEFAULT NULL,
  `motif` text COLLATE utf8mb4_0900_bin,
  `uq_principal_actif` tinyint UNSIGNED GENERATED ALWAYS AS ((case when ((`principal` = 1) and (`statut` = _ascii'active')) then 1 else NULL end)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_adhesions_organisation_6cd11e2a80` (`compte_id`,`organisation_id`),
  UNIQUE KEY `uq_adhesions_organisation_a6e60fd4c7` (`id`,`compte_id`),
  UNIQUE KEY `uq_adhesions_organisation_c02c2ab616` (`organisation_id`,`uq_principal_actif`),
  KEY `ix_adhesions_organisation_275b7bcab0` (`organisation_id`,`statut`,`principal`),
  KEY `ix_adhesions_organisation_2f97eab048` (`compte_id`,`statut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `adhesions_permissions`
--

DROP TABLE IF EXISTS `adhesions_permissions`;
CREATE TABLE IF NOT EXISTS `adhesions_permissions` (
  `adhesion_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `permission_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`adhesion_id`,`permission_id`),
  KEY `ix_adhesions_permissions_50d0bf2736` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `adresses`
--

DROP TABLE IF EXISTS `adresses`;
CREATE TABLE IF NOT EXISTS `adresses` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `libelle` varchar(512) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `ligne1` text COLLATE utf8mb4_0900_bin NOT NULL,
  `ligne2` text COLLATE utf8mb4_0900_bin,
  `ville` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `region` varchar(512) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `cp` varchar(64) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `pays` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `telephone` varchar(64) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `defaut` tinyint UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_adresses_940f1f0c40` (`compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `adresses_livraison_commande`
--

DROP TABLE IF EXISTS `adresses_livraison_commande`;
CREATE TABLE IF NOT EXISTS `adresses_livraison_commande` (
  `commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `adresse_origine_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `nom_destinataire` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `adresse` text COLLATE utf8mb4_0900_bin NOT NULL,
  `ville` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `region` varchar(512) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `code_postal` varchar(64) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `pays` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `telephone` varchar(64) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `instructions` text COLLATE utf8mb4_0900_bin,
  PRIMARY KEY (`commande_id`),
  KEY `ix_adresses_livraison_commande_f5b050879a` (`adresse_origine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `adresses_livraison_commande`
--
DROP TRIGGER IF EXISTS `bu_adresses_livraison_commande_e818f024`;
DELIMITER $$
CREATE TRIGGER `bu_adresses_livraison_commande_e818f024` BEFORE UPDATE ON `adresses_livraison_commande` FOR EACH ROW BEGIN
  IF NOT (NEW.`commande_id` <=> OLD.`commande_id`) OR NOT (NEW.`nom_destinataire` <=> OLD.`nom_destinataire`) OR NOT (NEW.`adresse` <=> OLD.`adresse`) OR NOT (NEW.`ville` <=> OLD.`ville`) OR NOT (NEW.`region` <=> OLD.`region`) OR NOT (NEW.`code_postal` <=> OLD.`code_postal`) OR NOT (NEW.`pays` <=> OLD.`pays`) OR NOT (NEW.`telephone` <=> OLD.`telephone`) OR NOT (NEW.`instructions` <=> OLD.`instructions`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NEW.`adresse_origine_id` IS NOT NULL AND NOT (NEW.`adresse_origine_id` <=> OLD.`adresse_origine_id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Provenance adresse : seul retrait du lien autorise';
  END IF;
  IF NOT (NEW.`commande_id` <=> OLD.`commande_id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `affectations_conformite`
--

DROP TABLE IF EXISTS `affectations_conformite`;
CREATE TABLE IF NOT EXISTS `affectations_conformite` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `phase_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `responsable_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `debut` datetime(6) NOT NULL,
  `fin` datetime(6) DEFAULT NULL,
  `reference_regle` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_affectations_conformite_0b894e36da` (`phase_id`,`debut`,`fin`),
  KEY `ix_affectations_conformite_ea1f499ce8` (`responsable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `affectations_revue`
--

DROP TABLE IF EXISTS `affectations_revue`;
CREATE TABLE IF NOT EXISTS `affectations_revue` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `responsable_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `debut` datetime(6) NOT NULL,
  `fin` datetime(6) DEFAULT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `reference_regle_affectation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_affectations_revue_6fa43b0109` (`dossier_id`,`debut`,`fin`),
  KEY `ix_affectations_revue_de060ee14d` (`responsable_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `ajustements_echeance`
--

DROP TABLE IF EXISTS `ajustements_echeance`;
CREATE TABLE IF NOT EXISTS `ajustements_echeance` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `echeance_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ancien_etat` json NOT NULL,
  `nouvel_etat` json NOT NULL,
  `date` datetime(6) NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `auteur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_systeme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_ajustements_echeance_d6ba07e399` (`echeance_id`),
  KEY `ix_ajustements_echeance_1c7f79acb8` (`auteur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `ajustements_echeance`
--
DROP TRIGGER IF EXISTS `bu_ajustements_echeance_d43b6b26`;
DELIMITER $$
CREATE TRIGGER `bu_ajustements_echeance_d43b6b26` BEFORE UPDATE ON `ajustements_echeance` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`echeance_id` <=> OLD.`echeance_id`) OR NOT (NEW.`ancien_etat` <=> OLD.`ancien_etat`) OR NOT (NEW.`nouvel_etat` <=> OLD.`nouvel_etat`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `alertes_journaux`
--

DROP TABLE IF EXISTS `alertes_journaux`;
CREATE TABLE IF NOT EXISTS `alertes_journaux` (
  `alerte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `journal_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`alerte_id`,`journal_id`),
  KEY `ix_alertes_journaux_7ad2d71a58` (`journal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `alertes_securite`
--

DROP TABLE IF EXISTS `alertes_securite`;
CREATE TABLE IF NOT EXISTS `alertes_securite` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `licence_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `appareil_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `type` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `severite` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `detecte_a` datetime(6) NOT NULL,
  `traite_a` datetime(6) DEFAULT NULL,
  `motif` text COLLATE utf8mb4_0900_bin,
  `responsable_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `reference_regle_detection` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_alertes_securite_270e734475` (`statut`,`severite`,`detecte_a`),
  KEY `ix_alertes_securite_940f1f0c40` (`compte_id`),
  KEY `ix_alertes_securite_97d325fd54` (`licence_id`),
  KEY `ix_alertes_securite_4955bd56f2` (`appareil_id`),
  KEY `ix_alertes_securite_de060ee14d` (`responsable_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `allocations_promo_ligne`
--

DROP TABLE IF EXISTS `allocations_promo_ligne`;
CREATE TABLE IF NOT EXISTS `allocations_promo_ligne` (
  `application_promo_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `montant` decimal(20,4) NOT NULL,
  PRIMARY KEY (`application_promo_id`,`ligne_commande_id`),
  KEY `ix_allocations_promo_ligne_3ff8abbe12` (`ligne_commande_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `allocations_remboursement`
--

DROP TABLE IF EXISTS `allocations_remboursement`;
CREATE TABLE IF NOT EXISTS `allocations_remboursement` (
  `remboursement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `montant` decimal(20,4) NOT NULL,
  `quantite_annulee` bigint DEFAULT NULL,
  `part_remise` decimal(20,4) NOT NULL,
  `part_taxe` decimal(20,4) NOT NULL,
  `part_livraison` decimal(20,4) NOT NULL,
  PRIMARY KEY (`remboursement_id`,`ligne_commande_id`),
  KEY `ix_allocations_remboursement_3ff8abbe12` (`ligne_commande_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `anomalies_paiement`
--

DROP TABLE IF EXISTS `anomalies_paiement`;
CREATE TABLE IF NOT EXISTS `anomalies_paiement` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `paiement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `evenement_psp_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `type` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `montant` decimal(20,4) DEFAULT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  `resolue_a` datetime(6) DEFAULT NULL,
  `decision` json DEFAULT NULL,
  `decide_par_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `decide_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_anomalies_paiement_68d4d872e4` (`paiement_id`),
  KEY `ix_anomalies_paiement_ddb2f596e8` (`evenement_psp_id`),
  KEY `ix_anomalies_paiement_a11d9493dc` (`decide_par_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `appareils`
--

DROP TABLE IF EXISTS `appareils`;
CREATE TABLE IF NOT EXISTS `appareils` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `identifiant_enregistrement` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_secret_enregistrement` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_preuve` bigint NOT NULL,
  `libelle` varchar(512) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `plateforme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `fingerprint_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `actif` tinyint UNSIGNED NOT NULL,
  `enregistre_a` datetime(6) NOT NULL,
  `dernier_vu_a` datetime(6) NOT NULL,
  `revoque_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_appareils_6ef8a3e05d` (`identifiant_enregistrement`),
  UNIQUE KEY `uq_appareils_a6e60fd4c7` (`id`,`compte_id`),
  KEY `ix_appareils_7c1d6e22f5` (`compte_id`,`actif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `applications_conservation`
--

DROP TABLE IF EXISTS `applications_conservation`;
CREATE TABLE IF NOT EXISTS `applications_conservation` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `objet_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `politique_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `debut_application` datetime(6) NOT NULL,
  `fin_application` datetime(6) DEFAULT NULL,
  `preuve_declencheur` json DEFAULT NULL,
  `depart` datetime(6) DEFAULT NULL,
  `archive_prevu_a` datetime(6) DEFAULT NULL,
  `destruction_prevue_a` datetime(6) DEFAULT NULL,
  `motif_adoption` text COLLATE utf8mb4_0900_bin NOT NULL,
  `uq_application_courante` tinyint UNSIGNED GENERATED ALWAYS AS ((case when (`fin_application` is null) then 1 else NULL end)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_applications_conservation_3f10e7e1d8` (`id`,`objet_id`),
  UNIQUE KEY `uq_applications_conservation_47776f930b` (`objet_id`,`uq_application_courante`),
  KEY `ix_applications_conservation_f2f13b571c` (`fin_application`,`destruction_prevue_a`),
  KEY `ix_applications_conservation_169d85acce` (`politique_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `applications_promo`
--

DROP TABLE IF EXISTS `applications_promo`;
CREATE TABLE IF NOT EXISTS `applications_promo` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_promo_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `remise_calculee` decimal(20,4) NOT NULL,
  `ordre_application` bigint NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reserve_a` datetime(6) NOT NULL,
  `expire_a` datetime(6) DEFAULT NULL,
  `consomme_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_applications_promo_144373b76d` (`commande_id`,`version_promo_id`),
  KEY `ix_applications_promo_81e29b0b9f` (`version_promo_id`,`statut`,`expire_a`),
  KEY `ix_applications_promo_f489472060` (`compte_id`,`version_promo_id`,`statut`),
  KEY `ix_applications_promo_35a9acb36a` (`commande_id`,`compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `attributions_internes`
--

DROP TABLE IF EXISTS `attributions_internes`;
CREATE TABLE IF NOT EXISTS `attributions_internes` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `permission_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `attribue_par` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `attribue_a` datetime(6) NOT NULL,
  `expire_a` datetime(6) DEFAULT NULL,
  `revoque_a` datetime(6) DEFAULT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attributions_internes_a6e60fd4c7` (`id`,`compte_id`),
  KEY `ix_attributions_internes_dfc2f67cb1` (`compte_id`,`permission_id`,`expire_a`),
  KEY `ix_attributions_internes_50d0bf2736` (`permission_id`),
  KEY `ix_attributions_internes_3562d11560` (`attribue_par`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `auteur`
--

DROP TABLE IF EXISTS `auteur`;
CREATE TABLE IF NOT EXISTS `auteur` (
  `auteur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `slug` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `nom_affiche` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `bio` text COLLATE utf8mb4_0900_bin,
  `pays` varchar(64) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `role_affiche` text COLLATE utf8mb4_0900_bin,
  `verified` tinyint UNSIGNED NOT NULL,
  `mis_en_avant` tinyint UNSIGNED NOT NULL,
  `actif` tinyint UNSIGNED NOT NULL,
  `statut_publication` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`auteur_id`),
  UNIQUE KEY `uq_auteur_cd03861f0f` (`slug`),
  UNIQUE KEY `uq_auteur_940f1f0c40` (`compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `auteur_tag`
--

DROP TABLE IF EXISTS `auteur_tag`;
CREATE TABLE IF NOT EXISTS `auteur_tag` (
  `auteur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `tag_auteur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`auteur_id`,`tag_auteur_id`),
  KEY `ix_auteur_tag_9c1eb4efa8` (`tag_auteur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `autorisations_contenu_origine`
--

DROP TABLE IF EXISTS `autorisations_contenu_origine`;
CREATE TABLE IF NOT EXISTS `autorisations_contenu_origine` (
  `origine_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_contenu_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `accorde_a` datetime(6) NOT NULL,
  `reference_decision` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  PRIMARY KEY (`origine_id`,`version_contenu_id`),
  KEY `ix_autorisations_contenu_origine_158d483baf` (`version_contenu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `avis`
--

DROP TABLE IF EXISTS `avis`;
CREATE TABLE IF NOT EXISTS `avis` (
  `avis_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `oeuvre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `note` bigint NOT NULL,
  `corps` text COLLATE utf8mb4_0900_bin,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `soumis_a` datetime(6) NOT NULL,
  `moderateur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `modere_a` datetime(6) DEFAULT NULL,
  `motif_rejet` text COLLATE utf8mb4_0900_bin,
  `uq_avis_non_rejete` tinyint UNSIGNED GENERATED ALWAYS AS ((case when (`statut` in (_ascii'pending',_ascii'published')) then 1 else NULL end)) STORED,
  PRIMARY KEY (`avis_id`),
  UNIQUE KEY `uq_avis_45b94da625` (`compte_id`,`oeuvre_id`,`uq_avis_non_rejete`),
  KEY `ix_avis_4c018e4b20` (`oeuvre_id`,`statut`,`soumis_a`),
  KEY `ix_avis_87fbcc7c32` (`moderateur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `avis`
--
DROP TRIGGER IF EXISTS `bu_avis_010a4e39`;
DELIMITER $$
CREATE TRIGGER `bu_avis_010a4e39` BEFORE UPDATE ON `avis` FOR EACH ROW BEGIN
  IF OLD.`statut` IN ('rejected') AND NOT (NEW.`statut` <=> OLD.`statut`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Etat terminal : nouvelle demande requise';
  END IF;
  IF NOT (NEW.`avis_id` <=> OLD.`avis_id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `avoirs`
--

DROP TABLE IF EXISTS `avoirs`;
CREATE TABLE IF NOT EXISTS `avoirs` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `facture_vente_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `facture_commission_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `decision_remboursement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `cle_emission` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `numero` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `serie` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `emetteur_cle_snap` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `montant` decimal(20,4) NOT NULL,
  `devise` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `emis_a` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_avoirs_4d7904f990` (`cle_emission`),
  UNIQUE KEY `uq_avoirs_e7d6741b79` (`emetteur_cle_snap`,`serie`,`numero`),
  KEY `ix_avoirs_3b530ef393` (`facture_vente_id`),
  KEY `ix_avoirs_eb7b937696` (`facture_commission_id`),
  KEY `ix_avoirs_f1014d9025` (`decision_remboursement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `avoirs`
--
DROP TRIGGER IF EXISTS `bu_avoirs_a2d8a392`;
DELIMITER $$
CREATE TRIGGER `bu_avoirs_a2d8a392` BEFORE UPDATE ON `avoirs` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`facture_vente_id` <=> OLD.`facture_vente_id`) OR NOT (NEW.`facture_commission_id` <=> OLD.`facture_commission_id`) OR NOT (NEW.`decision_remboursement_id` <=> OLD.`decision_remboursement_id`) OR NOT (NEW.`cle_emission` <=> OLD.`cle_emission`) OR NOT (NEW.`numero` <=> OLD.`numero`) OR NOT (NEW.`serie` <=> OLD.`serie`) OR NOT (NEW.`emetteur_cle_snap` <=> OLD.`emetteur_cle_snap`) OR NOT (NEW.`montant` <=> OLD.`montant`) OR NOT (NEW.`devise` <=> OLD.`devise`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`emis_a` <=> OLD.`emis_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `besoins_preparation`
--

DROP TABLE IF EXISTS `besoins_preparation`;
CREATE TABLE IF NOT EXISTS `besoins_preparation` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `quantite` bigint NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  `termine_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_besoins_preparation_3ff8abbe12` (`ligne_commande_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `blocages_acces`
--

DROP TABLE IF EXISTS `blocages_acces`;
CREATE TABLE IF NOT EXISTS `blocages_acces` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `licence_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `cause` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `producteur` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_source` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `debut` datetime(6) NOT NULL,
  `fin` datetime(6) DEFAULT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  `leve_a` datetime(6) DEFAULT NULL,
  `auteur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_systeme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_blocages_acces_96802877b6` (`producteur`,`reference_source`),
  KEY `ix_blocages_acces_4f43cf2a73` (`compte_id`,`debut`,`fin`),
  KEY `ix_blocages_acces_ea09f7ca77` (`licence_id`,`debut`,`fin`),
  KEY `ix_blocages_acces_1c7f79acb8` (`auteur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `capacites_encaissement`
--

DROP TABLE IF EXISTS `capacites_encaissement`;
CREATE TABLE IF NOT EXISTS `capacites_encaissement` (
  `configuration_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `moyen` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `devise` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`configuration_id`,`moyen`,`devise`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `categorie`
--

DROP TABLE IF EXISTS `categorie`;
CREATE TABLE IF NOT EXISTS `categorie` (
  `categorie_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `parent_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `slug` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `nom` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `sort_order` bigint NOT NULL,
  `actif` tinyint UNSIGNED NOT NULL,
  `uq_racine` tinyint UNSIGNED GENERATED ALWAYS AS ((case when (`parent_id` is null) then 1 else NULL end)) STORED,
  PRIMARY KEY (`categorie_id`),
  UNIQUE KEY `uq_categorie_614c157be1` (`parent_id`,`slug`),
  UNIQUE KEY `uq_categorie_a0b3868f4a` (`slug`,`uq_racine`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `categories_gel`
--

DROP TABLE IF EXISTS `categories_gel`;
CREATE TABLE IF NOT EXISTS `categories_gel` (
  `gel_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `categorie` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`gel_id`,`categorie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `cibles_extension_contenu`
--

DROP TABLE IF EXISTS `cibles_extension_contenu`;
CREATE TABLE IF NOT EXISTS `cibles_extension_contenu` (
  `decision_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_acquisition_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `origine_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_reponse` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `repondu_a` datetime(6) DEFAULT NULL,
  `motif` text COLLATE utf8mb4_0900_bin,
  PRIMARY KEY (`decision_id`,`reference_acquisition_id`),
  KEY `ix_cibles_extension_contenu_967027099e` (`reference_acquisition_id`),
  KEY `ix_cibles_extension_contenu_ee38579093` (`origine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `cibles_gels_financiers`
--

DROP TABLE IF EXISTS `cibles_gels_financiers`;
CREATE TABLE IF NOT EXISTS `cibles_gels_financiers` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `gel_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `paiement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cibles_gels_financiers_d7f10cd424` (`gel_id`,`paiement_id`),
  UNIQUE KEY `uq_cibles_gels_financiers_50497c5763` (`gel_id`,`commande_id`),
  KEY `ix_cibles_gels_financiers_68d4d872e4` (`paiement_id`),
  KEY `ix_cibles_gels_financiers_94c4e6db2b` (`commande_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `cibles_instructions_paiement`
--

DROP TABLE IF EXISTS `cibles_instructions_paiement`;
CREATE TABLE IF NOT EXISTS `cibles_instructions_paiement` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `instruction_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `paiement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cibles_instructions_paiement_c8542e12ec` (`instruction_id`,`paiement_id`),
  UNIQUE KEY `uq_cibles_instructions_paiement_1b9ad0974c` (`instruction_id`,`commande_id`),
  UNIQUE KEY `uq_cibles_instructions_paiement_f2f7c7c1db` (`instruction_id`,`compte_id`),
  KEY `ix_cibles_instructions_paiement_68d4d872e4` (`paiement_id`),
  KEY `ix_cibles_instructions_paiement_94c4e6db2b` (`commande_id`),
  KEY `ix_cibles_instructions_paiement_940f1f0c40` (`compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `cibles_revocation_origine`
--

DROP TABLE IF EXISTS `cibles_revocation_origine`;
CREATE TABLE IF NOT EXISTS `cibles_revocation_origine` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `demande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `producteur_acquisition` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_demande_acquisition` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `origine_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `reference_instruction_a4` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `confirme_a` datetime(6) DEFAULT NULL,
  `resultat` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cibles_revocation_origine_28fa5dd9d7` (`reference_instruction_a4`),
  UNIQUE KEY `uq_cibles_revocation_origine_8cb161530b` (`demande_id`,`producteur_acquisition`,`reference_demande_acquisition`),
  KEY `ix_cibles_revocation_origine_ee38579093` (`origine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `circuits_facturation_vendeur`
--

DROP TABLE IF EXISTS `circuits_facturation_vendeur`;
CREATE TABLE IF NOT EXISTS `circuits_facturation_vendeur` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `vendeur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `mandat_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `mode` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `actif` tinyint UNSIGNED NOT NULL,
  `valide_a` datetime(6) DEFAULT NULL,
  `reference_configuration` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_circuits_facturation_vendeur_f03e110afc` (`id`,`vendeur_id`),
  KEY `ix_circuits_facturation_vendeur_7ad420d99e` (`mandat_id`,`vendeur_id`),
  KEY `ix_circuits_facturation_vendeur_59710ce746` (`vendeur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `codes_promo`
--

DROP TABLE IF EXISTS `codes_promo`;
CREATE TABLE IF NOT EXISTS `codes_promo` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `actif` tinyint UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_codes_promo_5694d08a2e` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `collection`
--

DROP TABLE IF EXISTS `collection`;
CREATE TABLE IF NOT EXISTS `collection` (
  `collection_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `editeur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `slug` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `nom` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `kind` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `actif` tinyint UNSIGNED NOT NULL,
  PRIMARY KEY (`collection_id`),
  UNIQUE KEY `uq_collection_cd03861f0f` (`slug`),
  KEY `ix_collection_a7ec1680cf` (`editeur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `commandes`
--

DROP TABLE IF EXISTS `commandes`;
CREATE TABLE IF NOT EXISTS `commandes` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `cle_checkout` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_requete` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `devise` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sous_total` decimal(20,4) NOT NULL,
  `remise_total` decimal(20,4) NOT NULL,
  `livraison_total` decimal(20,4) NOT NULL,
  `taxe_total` decimal(20,4) NOT NULL,
  `grand_total` decimal(20,4) NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  `expire_a` datetime(6) NOT NULL,
  `confirme_a` datetime(6) DEFAULT NULL,
  `paye_a` datetime(6) DEFAULT NULL,
  `annule_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_commandes_52367a6622` (`reference`),
  UNIQUE KEY `uq_commandes_aaea54b3e5` (`compte_id`,`cle_checkout`),
  UNIQUE KEY `uq_commandes_a6e60fd4c7` (`id`,`compte_id`),
  UNIQUE KEY `uq_commandes_73595fbbf9` (`id`,`devise`),
  KEY `ix_commandes_b02ee09556` (`compte_id`,`cree_a`,`id`),
  KEY `ix_commandes_2488678d6d` (`statut`,`expire_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `commandes`
--
DROP TRIGGER IF EXISTS `bu_commandes_9d0ff7c1`;
DELIMITER $$
CREATE TRIGGER `bu_commandes_9d0ff7c1` BEFORE UPDATE ON `commandes` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`compte_id` <=> OLD.`compte_id`) OR NOT (NEW.`reference` <=> OLD.`reference`) OR NOT (NEW.`cle_checkout` <=> OLD.`cle_checkout`) OR NOT (NEW.`empreinte_requete` <=> OLD.`empreinte_requete`) OR NOT (NEW.`devise` <=> OLD.`devise`) OR NOT (NEW.`sous_total` <=> OLD.`sous_total`) OR NOT (NEW.`remise_total` <=> OLD.`remise_total`) OR NOT (NEW.`livraison_total` <=> OLD.`livraison_total`) OR NOT (NEW.`taxe_total` <=> OLD.`taxe_total`) OR NOT (NEW.`grand_total` <=> OLD.`grand_total`) OR NOT (NEW.`cree_a` <=> OLD.`cree_a`) OR NOT (NEW.`expire_a` <=> OLD.`expire_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `complements_signalement`
--

DROP TABLE IF EXISTS `complements_signalement`;
CREATE TABLE IF NOT EXISTS `complements_signalement` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_source` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `corps` json NOT NULL,
  `recu_a` datetime(6) NOT NULL,
  `preuve_auteur` json NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_complements_signalement_6a8b03625f` (`signalement_id`,`reference_source`),
  UNIQUE KEY `uq_complements_signalement_b052c60551` (`id`,`signalement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `comptes`
--

DROP TABLE IF EXISTS `comptes`;
CREATE TABLE IF NOT EXISTS `comptes` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `email_normalise` varchar(320) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `telephone_normalise` varchar(64) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `hash_mdp` varchar(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `locale` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `email_verifie_a` datetime(6) DEFAULT NULL,
  `telephone_verifie_a` datetime(6) DEFAULT NULL,
  `cree_a` datetime(6) NOT NULL,
  `ferme_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_comptes_4cc444db7a` (`email_normalise`),
  KEY `ix_comptes_1b624651ed` (`statut`,`cree_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `comptes`
--
DROP TRIGGER IF EXISTS `bu_comptes_7853db33`;
DELIMITER $$
CREATE TRIGGER `bu_comptes_7853db33` BEFORE UPDATE ON `comptes` FOR EACH ROW BEGIN
  IF OLD.`statut` IN ('closed') AND NOT (NEW.`statut` <=> OLD.`statut`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Etat terminal : nouvelle demande requise';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `comptes_reversement`
--

DROP TABLE IF EXISTS `comptes_reversement`;
CREATE TABLE IF NOT EXISTS `comptes_reversement` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sujet_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version` bigint NOT NULL,
  `canal` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `prestataire` text COLLATE utf8mb4_0900_bin,
  `titulaire` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `pays` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `devise` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_prestataire` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `coordonnees_chiffrees` mediumblob,
  `affichage_masque` text COLLATE utf8mb4_0900_bin NOT NULL,
  `statut_controle` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `verifie_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_comptes_reversement_aefc0facfe` (`sujet_id`,`version`),
  UNIQUE KEY `uq_comptes_reversement_4838ff0457` (`id`,`sujet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `comptes_roles`
--

DROP TABLE IF EXISTS `comptes_roles`;
CREATE TABLE IF NOT EXISTS `comptes_roles` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `role_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `origine` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `attribue_a` datetime(6) NOT NULL,
  `revoque_a` datetime(6) DEFAULT NULL,
  `motif` text COLLATE utf8mb4_0900_bin,
  `attribue_par` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_comptes_roles_f7939aa691` (`compte_id`,`role_id`),
  UNIQUE KEY `uq_comptes_roles_a6e60fd4c7` (`id`,`compte_id`),
  KEY `ix_comptes_roles_26525afb8b` (`role_id`),
  KEY `ix_comptes_roles_3562d11560` (`attribue_par`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `configurations_encaissement`
--

DROP TABLE IF EXISTS `configurations_encaissement`;
CREATE TABLE IF NOT EXISTS `configurations_encaissement` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `prestataire_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_compte_marchand` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `environnement` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `pays` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `priorite` bigint NOT NULL,
  `actif` tinyint UNSIGNED NOT NULL,
  `reference_secrets` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_configurations_encaissement_17115b9bf0` (`prestataire_id`,`reference_compte_marchand`,`environnement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `configurations_service`
--

DROP TABLE IF EXISTS `configurations_service`;
CREATE TABLE IF NOT EXISTS `configurations_service` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version` bigint NOT NULL,
  `schema_version` bigint NOT NULL,
  `valeurs` json NOT NULL,
  `empreinte` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_par` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  `approuve_par` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `approuve_a` datetime(6) DEFAULT NULL,
  `effet_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_configurations_service_f5b433d863` (`code`,`version`),
  UNIQUE KEY `uq_configurations_service_0835abdd54` (`code`,`effet_a`),
  KEY `ix_configurations_service_98f9ebd965` (`cree_par`),
  KEY `ix_configurations_service_4245cfefa6` (`approuve_par`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `configurations_service`
--
DROP TRIGGER IF EXISTS `bu_configurations_service_5e0540c9`;
DELIMITER $$
CREATE TRIGGER `bu_configurations_service_5e0540c9` BEFORE UPDATE ON `configurations_service` FOR EACH ROW BEGIN
  IF OLD.`approuve_a` IS NOT NULL AND (NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`code` <=> OLD.`code`) OR NOT (NEW.`version` <=> OLD.`version`) OR NOT (NEW.`schema_version` <=> OLD.`schema_version`) OR NOT (NEW.`valeurs` <=> OLD.`valeurs`) OR NOT (NEW.`empreinte` <=> OLD.`empreinte`) OR NOT (NEW.`cree_par` <=> OLD.`cree_par`) OR NOT (NEW.`cree_a` <=> OLD.`cree_a`) OR NOT (NEW.`approuve_par` <=> OLD.`approuve_par`) OR NOT (NEW.`approuve_a` <=> OLD.`approuve_a`) OR NOT (NEW.`effet_a` <=> OLD.`effet_a`)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Version approuvee ou soumise immuable';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `confirmations_effet`
--

DROP TABLE IF EXISTS `confirmations_effet`;
CREATE TABLE IF NOT EXISTS `confirmations_effet` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `autorite` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_demande` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `recu_instruction_a` datetime(6) DEFAULT NULL,
  `applique_a` datetime(6) DEFAULT NULL,
  `resultat` json DEFAULT NULL,
  `revision_source` text COLLATE utf8mb4_0900_bin,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_confirmations_effet_76bbdb8964` (`reference_demande`),
  UNIQUE KEY `uq_confirmations_effet_360bb66e99` (`ligne_id`,`autorite`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `consentements`
--

DROP TABLE IF EXISTS `consentements`;
CREATE TABLE IF NOT EXISTS `consentements` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `texte_version_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `nature` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `accepte_a` datetime(6) NOT NULL,
  `retire_a` datetime(6) DEFAULT NULL,
  `source` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `canal` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_consentements_423fd0467e` (`compte_id`,`texte_version_id`),
  KEY `ix_consentements_74718e64cc` (`texte_version_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `contenus_vises`
--

DROP TABLE IF EXISTS `contenus_vises`;
CREATE TABLE IF NOT EXISTS `contenus_vises` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `complement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `type_declare` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `url_declaree` text COLLATE utf8mb4_0900_bin,
  `description` text COLLATE utf8mb4_0900_bin NOT NULL,
  `instantane` json DEFAULT NULL,
  `declare_a` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_contenus_vises_9425755a5b` (`complement_id`,`signalement_id`),
  KEY `ix_contenus_vises_855a2083e7` (`signalement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `contestations_paiement`
--

DROP TABLE IF EXISTS `contestations_paiement`;
CREATE TABLE IF NOT EXISTS `contestations_paiement` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `producteur` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_litige` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `source` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `montant_indicatif` decimal(20,4) DEFAULT NULL,
  `devise` varchar(32) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `ouvert_a` datetime(6) NOT NULL,
  `clos_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_contestations_paiement_0b94ae01cf` (`producteur`,`reference_litige`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `contributions_publication`
--

DROP TABLE IF EXISTS `contributions_publication`;
CREATE TABLE IF NOT EXISTS `contributions_publication` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `auteur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `nom_declare` text COLLATE utf8mb4_0900_bin NOT NULL,
  `role` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ordre` bigint NOT NULL,
  `justification` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_contributions_publication_52c9056653` (`dossier_id`,`ordre`),
  KEY `ix_contributions_publication_6e38b1ca93` (`auteur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `controles_completude`
--

DROP TABLE IF EXISTS `controles_completude`;
CREATE TABLE IF NOT EXISTS `controles_completude` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero` bigint NOT NULL,
  `resultat` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `manques` json NOT NULL,
  `reference_regle` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_elements` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` datetime(6) NOT NULL,
  `auteur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_systeme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_controles_completude_14aafe1a5a` (`signalement_id`,`numero`),
  KEY `ix_controles_completude_1c7f79acb8` (`auteur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `controles_completude`
--
DROP TRIGGER IF EXISTS `bu_controles_completude_32795b7e`;
DELIMITER $$
CREATE TRIGGER `bu_controles_completude_32795b7e` BEFORE UPDATE ON `controles_completude` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`signalement_id` <=> OLD.`signalement_id`) OR NOT (NEW.`numero` <=> OLD.`numero`) OR NOT (NEW.`resultat` <=> OLD.`resultat`) OR NOT (NEW.`manques` <=> OLD.`manques`) OR NOT (NEW.`reference_regle` <=> OLD.`reference_regle`) OR NOT (NEW.`empreinte_elements` <=> OLD.`empreinte_elements`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `controles_documents`
--

DROP TABLE IF EXISTS `controles_documents`;
CREATE TABLE IF NOT EXISTS `controles_documents` (
  `controle_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `document_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`controle_id`,`document_id`),
  KEY `ix_controles_documents_e3c59ff60a` (`controle_id`,`dossier_id`),
  KEY `ix_controles_documents_5b73f77d86` (`document_id`,`dossier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `controles_publication`
--

DROP TABLE IF EXISTS `controles_publication`;
CREATE TABLE IF NOT EXISTS `controles_publication` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `exigence_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `auteur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero` bigint NOT NULL,
  `resultat` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `observation` text COLLATE utf8mb4_0900_bin NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  `empreinte_contenu_examine` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_controles_publication_1ceb82c089` (`exigence_id`,`numero`),
  KEY `ix_controles_publication_1c7f79acb8` (`auteur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `controles_publication`
--
DROP TRIGGER IF EXISTS `bu_controles_publication_a6e24bfb`;
DELIMITER $$
CREATE TRIGGER `bu_controles_publication_a6e24bfb` BEFORE UPDATE ON `controles_publication` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`exigence_id` <=> OLD.`exigence_id`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`numero` <=> OLD.`numero`) OR NOT (NEW.`resultat` <=> OLD.`resultat`) OR NOT (NEW.`observation` <=> OLD.`observation`) OR NOT (NEW.`cree_a` <=> OLD.`cree_a`) OR NOT (NEW.`empreinte_contenu_examine` <=> OLD.`empreinte_contenu_examine`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `controles_verification`
--

DROP TABLE IF EXISTS `controles_verification`;
CREATE TABLE IF NOT EXISTS `controles_verification` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `exigence_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resultat` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `observation` text COLLATE utf8mb4_0900_bin,
  `controle_a` datetime(6) DEFAULT NULL,
  `justification_non_applicable` text COLLATE utf8mb4_0900_bin,
  `controleur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_systeme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_controles_verification_adb4d2e03d` (`dossier_id`,`exigence_id`),
  UNIQUE KEY `uq_controles_verification_d68c96505f` (`id`,`dossier_id`),
  KEY `ix_controles_verification_dee52e834b` (`exigence_id`),
  KEY `ix_controles_verification_bc4ddaba69` (`controleur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `decisions_alerte`
--

DROP TABLE IF EXISTS `decisions_alerte`;
CREATE TABLE IF NOT EXISTS `decisions_alerte` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `alerte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `auteur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_systeme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `date` datetime(6) NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `action` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_action` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_decisions_alerte_f785163aa8` (`alerte_id`),
  KEY `ix_decisions_alerte_1c7f79acb8` (`auteur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `decisions_alerte`
--
DROP TRIGGER IF EXISTS `bu_decisions_alerte_9077fb05`;
DELIMITER $$
CREATE TRIGGER `bu_decisions_alerte_9077fb05` BEFORE UPDATE ON `decisions_alerte` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`alerte_id` <=> OLD.`alerte_id`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`action` <=> OLD.`action`) OR NOT (NEW.`reference_action` <=> OLD.`reference_action`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `decisions_controles`
--

DROP TABLE IF EXISTS `decisions_controles`;
CREATE TABLE IF NOT EXISTS `decisions_controles` (
  `decision_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `controle_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`decision_id`,`controle_id`),
  UNIQUE KEY `uq_decisions_controles_0ed016e731` (`controle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `decisions_controles`
--
DROP TRIGGER IF EXISTS `bu_decisions_controles_3687f316`;
DELIMITER $$
CREATE TRIGGER `bu_decisions_controles_3687f316` BEFORE UPDATE ON `decisions_controles` FOR EACH ROW BEGIN
  IF NOT (NEW.`decision_id` <=> OLD.`decision_id`) OR NOT (NEW.`controle_id` <=> OLD.`controle_id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`decision_id` <=> OLD.`decision_id`) OR NOT (NEW.`controle_id` <=> OLD.`controle_id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `decisions_extension_contenu`
--

DROP TABLE IF EXISTS `decisions_extension_contenu`;
CREATE TABLE IF NOT EXISTS `decisions_extension_contenu` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `manuscrit_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_contenu_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `approbateur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_decision` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_intention` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `decide_a` datetime(6) NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_decisions_extension_contenu_1b1f21f3f5` (`reference_decision`),
  KEY `ix_decisions_extension_contenu_62122d9acb` (`manuscrit_id`),
  KEY `ix_decisions_extension_contenu_158d483baf` (`version_contenu_id`),
  KEY `ix_decisions_extension_contenu_de3c581231` (`approbateur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `decisions_moderation`
--

DROP TABLE IF EXISTS `decisions_moderation`;
CREATE TABLE IF NOT EXISTS `decisions_moderation` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `phase_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `decide_a` datetime(6) NOT NULL,
  `decideur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `preuve_independance` json NOT NULL,
  `correlation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_decisions_moderation_902b502e39` (`phase_id`),
  KEY `ix_decisions_moderation_d6d83fdc33` (`decideur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `decisions_moderation`
--
DROP TRIGGER IF EXISTS `bu_decisions_moderation_10248b4d`;
DELIMITER $$
CREATE TRIGGER `bu_decisions_moderation_10248b4d` BEFORE UPDATE ON `decisions_moderation` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`phase_id` <=> OLD.`phase_id`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`decide_a` <=> OLD.`decide_a`) OR NOT (NEW.`decideur_id` <=> OLD.`decideur_id`) OR NOT (NEW.`preuve_independance` <=> OLD.`preuve_independance`) OR NOT (NEW.`correlation` <=> OLD.`correlation`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `decisions_publication`
--

DROP TABLE IF EXISTS `decisions_publication`;
CREATE TABLE IF NOT EXISTS `decisions_publication` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decideur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resultat` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `decide_a` datetime(6) NOT NULL,
  `preuve_independance` json NOT NULL,
  `correlation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_decisions_publication_0259b9fde9` (`dossier_id`),
  KEY `ix_decisions_publication_2b292ce11f` (`decideur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `decisions_publication`
--
DROP TRIGGER IF EXISTS `bu_decisions_publication_19249f5d`;
DELIMITER $$
CREATE TRIGGER `bu_decisions_publication_19249f5d` BEFORE UPDATE ON `decisions_publication` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`dossier_id` <=> OLD.`dossier_id`) OR NOT (NEW.`decideur_compte_id` <=> OLD.`decideur_compte_id`) OR NOT (NEW.`resultat` <=> OLD.`resultat`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`decide_a` <=> OLD.`decide_a`) OR NOT (NEW.`preuve_independance` <=> OLD.`preuve_independance`) OR NOT (NEW.`correlation` <=> OLD.`correlation`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `decisions_rattachement_compensation`
--

DROP TABLE IF EXISTS `decisions_rattachement_compensation`;
CREATE TABLE IF NOT EXISTS `decisions_rattachement_compensation` (
  `reception_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decideur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `preuve_equivalence` json NOT NULL,
  `decide_a` datetime(6) NOT NULL,
  PRIMARY KEY (`reception_id`),
  KEY `ix_decisions_rattachement_compensation_0259b9fde9` (`dossier_id`),
  KEY `ix_decisions_rattachement_compensation_d6d83fdc33` (`decideur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `decisions_rattachement_compensation`
--
DROP TRIGGER IF EXISTS `bu_decisions_rattachement_compensation_4ee76195`;
DELIMITER $$
CREATE TRIGGER `bu_decisions_rattachement_compensation_4ee76195` BEFORE UPDATE ON `decisions_rattachement_compensation` FOR EACH ROW BEGIN
  IF NOT (NEW.`reception_id` <=> OLD.`reception_id`) OR NOT (NEW.`dossier_id` <=> OLD.`dossier_id`) OR NOT (NEW.`decideur_id` <=> OLD.`decideur_id`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`preuve_equivalence` <=> OLD.`preuve_equivalence`) OR NOT (NEW.`decide_a` <=> OLD.`decide_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`reception_id` <=> OLD.`reception_id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `decisions_remboursement`
--

DROP TABLE IF EXISTS `decisions_remboursement`;
CREATE TABLE IF NOT EXISTS `decisions_remboursement` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decideur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `montant_autorise` decimal(20,4) NOT NULL,
  `devise` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `effet_droit` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decide_a` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_decisions_remboursement_0259b9fde9` (`dossier_id`),
  UNIQUE KEY `uq_decisions_remboursement_73595fbbf9` (`id`,`devise`),
  KEY `ix_decisions_remboursement_d6d83fdc33` (`decideur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `decisions_remboursement`
--
DROP TRIGGER IF EXISTS `bu_decisions_remboursement_3f4a318d`;
DELIMITER $$
CREATE TRIGGER `bu_decisions_remboursement_3f4a318d` BEFORE UPDATE ON `decisions_remboursement` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`dossier_id` <=> OLD.`dossier_id`) OR NOT (NEW.`decideur_id` <=> OLD.`decideur_id`) OR NOT (NEW.`decision` <=> OLD.`decision`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`montant_autorise` <=> OLD.`montant_autorise`) OR NOT (NEW.`devise` <=> OLD.`devise`) OR NOT (NEW.`effet_droit` <=> OLD.`effet_droit`) OR NOT (NEW.`decide_a` <=> OLD.`decide_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `decisions_verification`
--

DROP TABLE IF EXISTS `decisions_verification`;
CREATE TABLE IF NOT EXISTS `decisions_verification` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decideur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resultat` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `decide_a` datetime(6) NOT NULL,
  `valide_jusqua` datetime(6) DEFAULT NULL,
  `restrictions` text COLLATE utf8mb4_0900_bin,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_decisions_verification_0259b9fde9` (`dossier_id`),
  UNIQUE KEY `uq_decisions_verification_d68c96505f` (`id`,`dossier_id`),
  KEY `ix_decisions_verification_d6d83fdc33` (`decideur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `decisions_verification`
--
DROP TRIGGER IF EXISTS `bu_decisions_verification_8f457611`;
DELIMITER $$
CREATE TRIGGER `bu_decisions_verification_8f457611` BEFORE UPDATE ON `decisions_verification` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`dossier_id` <=> OLD.`dossier_id`) OR NOT (NEW.`decideur_id` <=> OLD.`decideur_id`) OR NOT (NEW.`resultat` <=> OLD.`resultat`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`decide_a` <=> OLD.`decide_a`) OR NOT (NEW.`valide_jusqua` <=> OLD.`valide_jusqua`) OR NOT (NEW.`restrictions` <=> OLD.`restrictions`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `defis_verification`
--

DROP TABLE IF EXISTS `defis_verification`;
CREATE TABLE IF NOT EXISTS `defis_verification` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `finalite` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `canal` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `destination_chiffree` mediumblob NOT NULL,
  `empreinte_destination` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `destination_masquee` text COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_secret` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  `expire_a` datetime(6) NOT NULL,
  `consomme_a` datetime(6) DEFAULT NULL,
  `revoque_a` datetime(6) DEFAULT NULL,
  `nombre_essais` bigint NOT NULL,
  `plafond_essais` bigint NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_defis_verification_94f55fcefb` (`compte_id`,`finalite`,`expire_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `demandes_attribution_droit`
--

DROP TABLE IF EXISTS `demandes_attribution_droit`;
CREATE TABLE IF NOT EXISTS `demandes_attribution_droit` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `offre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `cle_idempotence` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `origine` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  `confirme_a` datetime(6) DEFAULT NULL,
  `reference_acquisition_a4_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_a4_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `licence_a4_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `resultat_metier` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `reference_reponse` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `repondu_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_demandes_attribution_droit_9f407e61c3` (`cle_idempotence`),
  UNIQUE KEY `uq_demandes_attribution_droit_3ff8abbe12` (`ligne_commande_id`),
  KEY `ix_demandes_attribution_droit_c4a8720abd` (`licence_a4_id`,`compte_id`,`offre_id`),
  KEY `ix_demandes_attribution_droit_90c3f66211` (`origine_a4_id`,`reference_acquisition_a4_id`,`licence_a4_id`),
  KEY `ix_demandes_attribution_droit_1b624651ed` (`statut`,`cree_a`),
  KEY `ix_demandes_attribution_droit_940f1f0c40` (`compte_id`),
  KEY `ix_demandes_attribution_droit_0d7af68203` (`offre_id`),
  KEY `ix_demandes_attribution_droit_2f6cd18f4f` (`reference_acquisition_a4_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `demandes_compensation_conformite`
--

DROP TABLE IF EXISTS `demandes_compensation_conformite`;
CREATE TABLE IF NOT EXISTS `demandes_compensation_conformite` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_intention` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `litige_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `decision_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_reutilisation_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  `auteur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resultat` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_demandes_compensation_conformite_52367a6622` (`reference`),
  KEY `ix_demandes_compensation_conformite_3202030bfd` (`dossier_reutilisation_id`,`commande_id`),
  KEY `ix_demandes_compensation_conformite_855a2083e7` (`signalement_id`),
  KEY `ix_demandes_compensation_conformite_ac97dff02e` (`litige_id`),
  KEY `ix_demandes_compensation_conformite_3d7b176fd7` (`decision_id`),
  KEY `ix_demandes_compensation_conformite_94c4e6db2b` (`commande_id`),
  KEY `ix_demandes_compensation_conformite_6e38b1ca93` (`auteur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `demandes_revocation_droit`
--

DROP TABLE IF EXISTS `demandes_revocation_droit`;
CREATE TABLE IF NOT EXISTS `demandes_revocation_droit` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `demande_attribution_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cle_idempotence` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_acquisition_a4_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_a4_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `licence_a4_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `resultat_metier` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `reference_reponse` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `repondu_a` datetime(6) DEFAULT NULL,
  `cree_a` datetime(6) NOT NULL,
  `confirme_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_demandes_revocation_droit_9f407e61c3` (`cle_idempotence`),
  UNIQUE KEY `uq_demandes_revocation_droit_8e37ec5ac6` (`decision_id`,`demande_attribution_id`),
  KEY `ix_demandes_revocation_droit_90c3f66211` (`origine_a4_id`,`reference_acquisition_a4_id`,`licence_a4_id`),
  KEY `ix_demandes_revocation_droit_8d35524b10` (`demande_attribution_id`),
  KEY `ix_demandes_revocation_droit_2f6cd18f4f` (`reference_acquisition_a4_id`),
  KEY `ix_demandes_revocation_droit_444f43db70` (`licence_a4_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `demandes_revocation_origines`
--

DROP TABLE IF EXISTS `demandes_revocation_origines`;
CREATE TABLE IF NOT EXISTS `demandes_revocation_origines` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_intention` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `instruction_paiement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `effet_a` datetime(6) NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_demandes_revocation_origines_52367a6622` (`reference`),
  KEY `ix_demandes_revocation_origines_3d7b176fd7` (`decision_id`),
  KEY `ix_demandes_revocation_origines_343ce2e3d7` (`instruction_paiement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `demandes_support`
--

DROP TABLE IF EXISTS `demandes_support`;
CREATE TABLE IF NOT EXISTS `demandes_support` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `type` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `demandeur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `compte_concerne_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `organisation_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `sujet_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `dossier_kyc_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `reference_auteur_externe` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `contact_chiffre` mediumblob,
  `contact_masque` text COLLATE utf8mb4_0900_bin,
  `cree_a` datetime(6) NOT NULL,
  `identite_etat` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `identite_verifiee_par` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `identite_verifiee_a` datetime(6) DEFAULT NULL,
  `methode_verification` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `conclusion` text COLLATE utf8mb4_0900_bin,
  `decide_par` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `clos_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_demandes_support_52367a6622` (`reference`),
  KEY `ix_demandes_support_156dce9101` (`compte_concerne_id`,`cree_a`),
  KEY `ix_demandes_support_3435c3bf38` (`demandeur_compte_id`),
  KEY `ix_demandes_support_1b213432c4` (`organisation_id`),
  KEY `ix_demandes_support_38e2c20786` (`sujet_id`),
  KEY `ix_demandes_support_d5aba0d290` (`dossier_kyc_id`),
  KEY `ix_demandes_support_9fd8da60f6` (`identite_verifiee_par`),
  KEY `ix_demandes_support_d5155db14d` (`decide_par`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `derives_catalogue_publication`
--

DROP TABLE IF EXISTS `derives_catalogue_publication`;
CREATE TABLE IF NOT EXISTS `derives_catalogue_publication` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `execution_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `media_soumis_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_derive_privee` varchar(1024) COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `media_catalogue_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_derives_catalogue_publication_51227e1206` (`media_catalogue_id`),
  KEY `ix_derives_catalogue_publication_6d6de5b8d6` (`execution_id`),
  KEY `ix_derives_catalogue_publication_4ac4d57434` (`media_soumis_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `developpements_portee`
--

DROP TABLE IF EXISTS `developpements_portee`;
CREATE TABLE IF NOT EXISTS `developpements_portee` (
  `portee_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resolution_enfant_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `developpe_a` datetime(6) NOT NULL,
  `preuve_perimetre` json NOT NULL,
  PRIMARY KEY (`portee_id`,`resolution_enfant_id`),
  KEY `ix_developpements_portee_3f7341f87c` (`resolution_enfant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `documents_justificatifs`
--

DROP TABLE IF EXISTS `documents_justificatifs`;
CREATE TABLE IF NOT EXISTS `documents_justificatifs` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `deposant_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type_doc` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_privee` varchar(1024) COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `autorite` text COLLATE utf8mb4_0900_bin,
  `delivre_le` date DEFAULT NULL,
  `expire_le` date DEFAULT NULL,
  `depose_a` datetime(6) NOT NULL,
  `statut_verification` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif_rejet` text COLLATE utf8mb4_0900_bin,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_documents_justificatifs_d68c96505f` (`id`,`dossier_id`),
  KEY `ix_documents_justificatifs_0259b9fde9` (`dossier_id`),
  KEY `ix_documents_justificatifs_fc6ac65f65` (`deposant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `dossiers_categories`
--

DROP TABLE IF EXISTS `dossiers_categories`;
CREATE TABLE IF NOT EXISTS `dossiers_categories` (
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `categorie_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`dossier_id`,`categorie_id`),
  KEY `ix_dossiers_categories_52493cdd56` (`categorie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `dossiers_facturation`
--

DROP TABLE IF EXISTS `dossiers_facturation`;
CREATE TABLE IF NOT EXISTS `dossiers_facturation` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `vendeur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `circuit_checkout_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `regle_remuneration_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `conditions_commission_snap` json NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  `derniere_erreur` text COLLATE utf8mb4_0900_bin,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dossiers_facturation_04f2d585b4` (`commande_id`,`vendeur_id`),
  KEY `ix_dossiers_facturation_11354efbca` (`circuit_checkout_id`,`vendeur_id`),
  KEY `ix_dossiers_facturation_59710ce746` (`vendeur_id`),
  KEY `ix_dossiers_facturation_5e0edcf951` (`regle_remuneration_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `dossiers_langues`
--

DROP TABLE IF EXISTS `dossiers_langues`;
CREATE TABLE IF NOT EXISTS `dossiers_langues` (
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `langue_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `principale` tinyint UNSIGNED NOT NULL,
  `uq_langue_principale` tinyint UNSIGNED GENERATED ALWAYS AS ((case when (`principale` = 1) then 1 else NULL end)) STORED,
  PRIMARY KEY (`dossier_id`,`langue_id`),
  UNIQUE KEY `uq_dossiers_langues_a6654eb1c4` (`dossier_id`,`uq_langue_principale`),
  KEY `ix_dossiers_langues_4a0e2288d6` (`langue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `dossiers_publication`
--

DROP TABLE IF EXISTS `dossiers_publication`;
CREATE TABLE IF NOT EXISTS `dossiers_publication` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `manuscrit_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `precedent_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `numero` bigint NOT NULL,
  `nature` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `titre` varchar(512) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `sous_titre` varchar(512) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `resume` text COLLATE utf8mb4_0900_bin,
  `mots_cles` json DEFAULT NULL,
  `declarations_droits` json DEFAULT NULL,
  `niveau_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `cree_a` datetime(6) NOT NULL,
  `soumis_a` datetime(6) DEFAULT NULL,
  `termine_a` datetime(6) DEFAULT NULL,
  `reference_regles_completude` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `empreinte_soumission` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `oeuvre_cible_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `empreinte_base_oeuvre` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `snapshot_base_oeuvre` json DEFAULT NULL,
  `uq_version_courante` tinyint UNSIGNED GENERATED ALWAYS AS ((case when (`statut` in (_ascii'draft',_ascii'submitted',_ascii'under_review')) then 1 else NULL end)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dossiers_publication_7147aa24e4` (`manuscrit_id`,`numero`),
  UNIQUE KEY `uq_dossiers_publication_21db65a88f` (`id`,`manuscrit_id`),
  UNIQUE KEY `uq_dossiers_publication_4ce4a55fd4` (`precedent_id`),
  UNIQUE KEY `uq_dossiers_publication_2473f2491e` (`manuscrit_id`,`uq_version_courante`),
  KEY `ix_dossiers_publication_6a4864c5d9` (`manuscrit_id`,`statut`,`numero`),
  KEY `ix_dossiers_publication_4183ae6c37` (`precedent_id`,`manuscrit_id`),
  KEY `ix_dossiers_publication_bc5641478c` (`niveau_id`),
  KEY `ix_dossiers_publication_4bf83f3fa7` (`oeuvre_cible_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `dossiers_publication`
--
DROP TRIGGER IF EXISTS `bu_dossiers_publication_f3a2add8`;
DELIMITER $$
CREATE TRIGGER `bu_dossiers_publication_f3a2add8` BEFORE UPDATE ON `dossiers_publication` FOR EACH ROW BEGIN
  IF OLD.`statut` <> 'draft' AND (NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`manuscrit_id` <=> OLD.`manuscrit_id`) OR NOT (NEW.`precedent_id` <=> OLD.`precedent_id`) OR NOT (NEW.`numero` <=> OLD.`numero`) OR NOT (NEW.`nature` <=> OLD.`nature`) OR NOT (NEW.`titre` <=> OLD.`titre`) OR NOT (NEW.`sous_titre` <=> OLD.`sous_titre`) OR NOT (NEW.`resume` <=> OLD.`resume`) OR NOT (NEW.`mots_cles` <=> OLD.`mots_cles`) OR NOT (NEW.`declarations_droits` <=> OLD.`declarations_droits`) OR NOT (NEW.`niveau_id` <=> OLD.`niveau_id`) OR NOT (NEW.`cree_a` <=> OLD.`cree_a`) OR NOT (NEW.`soumis_a` <=> OLD.`soumis_a`) OR NOT (NEW.`reference_regles_completude` <=> OLD.`reference_regles_completude`) OR NOT (NEW.`empreinte_soumission` <=> OLD.`empreinte_soumission`) OR NOT (NEW.`oeuvre_cible_id` <=> OLD.`oeuvre_cible_id`) OR NOT (NEW.`empreinte_base_oeuvre` <=> OLD.`empreinte_base_oeuvre`) OR NOT (NEW.`snapshot_base_oeuvre` <=> OLD.`snapshot_base_oeuvre`)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Version approuvee ou soumise immuable';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `dossiers_remboursement`
--

DROP TABLE IF EXISTS `dossiers_remboursement`;
CREATE TABLE IF NOT EXISTS `dossiers_remboursement` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `demandeur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_ouverture` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `initiateur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_systeme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `dossier_precedent_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `reference` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `preuves_privees` json DEFAULT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dossiers_remboursement_52367a6622` (`reference`),
  UNIQUE KEY `uq_dossiers_remboursement_29306169f2` (`id`,`commande_id`),
  KEY `ix_dossiers_remboursement_c1163a4d7f` (`commande_id`,`statut`,`cree_a`),
  KEY `ix_dossiers_remboursement_cfaf4f3494` (`dossier_precedent_id`,`commande_id`),
  KEY `ix_dossiers_remboursement_b9c128857f` (`demandeur_id`),
  KEY `ix_dossiers_remboursement_7fcda8b148` (`initiateur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `dossiers_verification`
--

DROP TABLE IF EXISTS `dossiers_verification`;
CREATE TABLE IF NOT EXISTS `dossiers_verification` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sujet_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `version` bigint NOT NULL,
  `precedent_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `niveau` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `deposant_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `profil_fiscal_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `compte_reversement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `cree_a` datetime(6) NOT NULL,
  `soumis_a` datetime(6) DEFAULT NULL,
  `clos_a` datetime(6) DEFAULT NULL,
  `uq_dossier_courant` tinyint UNSIGNED GENERATED ALWAYS AS ((case when (`statut` in (_ascii'draft',_ascii'submitted',_ascii'under_review')) then 1 else NULL end)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_dossiers_verification_15820251d8` (`numero`,`version`),
  UNIQUE KEY `uq_dossiers_verification_4838ff0457` (`id`,`sujet_id`),
  UNIQUE KEY `uq_dossiers_verification_ecced08308` (`id`,`sujet_id`,`niveau`),
  UNIQUE KEY `uq_dossiers_verification_108f486e92` (`id`,`sujet_id`,`niveau`,`numero`),
  UNIQUE KEY `uq_dossiers_verification_4ce4a55fd4` (`precedent_id`),
  UNIQUE KEY `uq_dossiers_verification_f66bb07ba1` (`sujet_id`,`niveau`,`uq_dossier_courant`),
  KEY `ix_dossiers_verification_60ed19426d` (`sujet_id`,`niveau`,`cree_a`,`id`),
  KEY `ix_dossiers_verification_eb3bc9fa94` (`precedent_id`,`sujet_id`,`niveau`,`numero`),
  KEY `ix_dossiers_verification_8048081132` (`statut`,`soumis_a`,`id`),
  KEY `ix_dossiers_verification_70e5509c24` (`profil_fiscal_id`,`sujet_id`),
  KEY `ix_dossiers_verification_d492b15cd7` (`compte_reversement_id`,`sujet_id`),
  KEY `ix_dossiers_verification_fc6ac65f65` (`deposant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `dossiers_verification`
--
DROP TRIGGER IF EXISTS `bu_dossiers_verification_155f57c3`;
DELIMITER $$
CREATE TRIGGER `bu_dossiers_verification_155f57c3` BEFORE UPDATE ON `dossiers_verification` FOR EACH ROW BEGIN
  IF OLD.`statut` <> 'draft' AND (NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`sujet_id` <=> OLD.`sujet_id`) OR NOT (NEW.`numero` <=> OLD.`numero`) OR NOT (NEW.`version` <=> OLD.`version`) OR NOT (NEW.`precedent_id` <=> OLD.`precedent_id`) OR NOT (NEW.`niveau` <=> OLD.`niveau`) OR NOT (NEW.`deposant_id` <=> OLD.`deposant_id`) OR NOT (NEW.`profil_fiscal_id` <=> OLD.`profil_fiscal_id`) OR NOT (NEW.`compte_reversement_id` <=> OLD.`compte_reversement_id`) OR NOT (NEW.`cree_a` <=> OLD.`cree_a`) OR NOT (NEW.`soumis_a` <=> OLD.`soumis_a`)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Version approuvee ou soumise immuable';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `droits_invoques`
--

DROP TABLE IF EXISTS `droits_invoques`;
CREATE TABLE IF NOT EXISTS `droits_invoques` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `complement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `code` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `description` text COLLATE utf8mb4_0900_bin NOT NULL,
  `declare_a` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_droits_invoques_9425755a5b` (`complement_id`,`signalement_id`),
  KEY `ix_droits_invoques_855a2083e7` (`signalement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `droits_offres_publication`
--

DROP TABLE IF EXISTS `droits_offres_publication`;
CREATE TABLE IF NOT EXISTS `droits_offres_publication` (
  `offre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `perimetre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `execution_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `acte` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `actif` tinyint UNSIGNED NOT NULL,
  PRIMARY KEY (`offre_id`,`perimetre_id`,`acte`),
  KEY `ix_droits_offres_publication_a0f73fc9bd` (`offre_id`,`acte`,`actif`),
  KEY `ix_droits_offres_publication_f4fd69f4b8` (`perimetre_id`),
  KEY `ix_droits_offres_publication_6d6de5b8d6` (`execution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `droits_territoires`
--

DROP TABLE IF EXISTS `droits_territoires`;
CREATE TABLE IF NOT EXISTS `droits_territoires` (
  `droit_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `territoire_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`droit_id`,`territoire_id`),
  KEY `ix_droits_territoires_6590bb9872` (`territoire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `droits_versions_publication`
--

DROP TABLE IF EXISTS `droits_versions_publication`;
CREATE TABLE IF NOT EXISTS `droits_versions_publication` (
  `version_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `perimetre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `execution_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `actif` tinyint UNSIGNED NOT NULL,
  PRIMARY KEY (`version_id`,`perimetre_id`),
  KEY `ix_droits_versions_publication_f0d5f3ff70` (`version_id`,`actif`),
  KEY `ix_droits_versions_publication_f4fd69f4b8` (`perimetre_id`),
  KEY `ix_droits_versions_publication_6d6de5b8d6` (`execution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `echanges_support`
--

DROP TABLE IF EXISTS `echanges_support`;
CREATE TABLE IF NOT EXISTS `echanges_support` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `demande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `auteur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `visibilite` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `contenu_prive` text COLLATE utf8mb4_0900_bin NOT NULL,
  `date` datetime(6) NOT NULL,
  `canal` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_echanges_support_78e46a12e9` (`id`,`demande_id`),
  KEY `ix_echanges_support_8067cf7f71` (`demande_id`,`date`,`id`),
  KEY `ix_echanges_support_1c7f79acb8` (`auteur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `echanges_support`
--
DROP TRIGGER IF EXISTS `bu_echanges_support_d45bddec`;
DELIMITER $$
CREATE TRIGGER `bu_echanges_support_d45bddec` BEFORE UPDATE ON `echanges_support` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`demande_id` <=> OLD.`demande_id`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine` <=> OLD.`origine`) OR NOT (NEW.`visibilite` <=> OLD.`visibilite`) OR NOT (NEW.`contenu_prive` <=> OLD.`contenu_prive`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`canal` <=> OLD.`canal`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `echeances_conformite`
--

DROP TABLE IF EXISTS `echeances_conformite`;
CREATE TABLE IF NOT EXISTS `echeances_conformite` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `phase_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `partie_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `notification_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `parametre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `fait_notification_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `preuve_autre_declencheur` json DEFAULT NULL,
  `debut` datetime(6) DEFAULT NULL,
  `echeance` datetime(6) DEFAULT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_echeances_conformite_67cefabb4f` (`statut`,`echeance`),
  KEY `ix_echeances_conformite_3d05ed0fef` (`partie_id`,`phase_id`),
  KEY `ix_echeances_conformite_a2d7b75921` (`notification_id`,`partie_id`),
  KEY `ix_echeances_conformite_8693d56dfc` (`fait_notification_id`,`notification_id`),
  KEY `ix_echeances_conformite_902b502e39` (`phase_id`),
  KEY `ix_echeances_conformite_eed6930686` (`parametre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `editeur`
--

DROP TABLE IF EXISTS `editeur`;
CREATE TABLE IF NOT EXISTS `editeur` (
  `editeur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `slug` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `nom` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `type` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `presentation` text COLLATE utf8mb4_0900_bin,
  `pays` varchar(64) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `is_partner` tinyint UNSIGNED NOT NULL,
  `is_academic` tinyint UNSIGNED NOT NULL,
  `is_youth` tinyint UNSIGNED NOT NULL,
  `is_african` tinyint UNSIGNED NOT NULL,
  `is_indie` tinyint UNSIGNED NOT NULL,
  `mis_en_avant` tinyint UNSIGNED NOT NULL,
  `actif` tinyint UNSIGNED NOT NULL,
  PRIMARY KEY (`editeur_id`),
  UNIQUE KEY `uq_editeur_cd03861f0f` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `editeurs_legaux_publication`
--

DROP TABLE IF EXISTS `editeurs_legaux_publication`;
CREATE TABLE IF NOT EXISTS `editeurs_legaux_publication` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `edition_cible_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `libelle` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `editeur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_editeurs_legaux_publication_44cc54aa64` (`edition_cible_id`),
  KEY `ix_editeurs_legaux_publication_a7ec1680cf` (`editeur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `edition`
--

DROP TABLE IF EXISTS `edition`;
CREATE TABLE IF NOT EXISTS `edition` (
  `edition_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `oeuvre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `editeur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `isbn` text COLLATE utf8mb4_0900_bin,
  `titre_edition` varchar(512) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `editeur_legal_libelle` text COLLATE utf8mb4_0900_bin,
  `role_ae` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `responsable_depot` text COLLATE utf8mb4_0900_bin,
  `territoire` text COLLATE utf8mb4_0900_bin,
  `date_edition` date DEFAULT NULL,
  `statut_depot` text COLLATE utf8mb4_0900_bin,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `consultable` tinyint UNSIGNED NOT NULL,
  PRIMARY KEY (`edition_id`),
  KEY `ix_edition_a3fad59948` (`oeuvre_id`),
  KEY `ix_edition_a7ec1680cf` (`editeur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `editions_cibles`
--

DROP TABLE IF EXISTS `editions_cibles`;
CREATE TABLE IF NOT EXISTS `editions_cibles` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_locale` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `libelle` varchar(512) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `date_edition` date DEFAULT NULL,
  `role_ae` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `responsable_depot` text COLLATE utf8mb4_0900_bin,
  `statut_depot` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `edition_cible_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `empreinte_base_edition` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `snapshot_base_edition` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_editions_cibles_07e25edb4a` (`dossier_id`,`reference_locale`),
  KEY `ix_editions_cibles_44cc54aa64` (`edition_cible_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `editions_territoires`
--

DROP TABLE IF EXISTS `editions_territoires`;
CREATE TABLE IF NOT EXISTS `editions_territoires` (
  `edition_cible_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `territoire_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`edition_cible_id`,`territoire_id`),
  KEY `ix_editions_territoires_6590bb9872` (`territoire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `effets_retrait_publication`
--

DROP TABLE IF EXISTS `effets_retrait_publication`;
CREATE TABLE IF NOT EXISTS `effets_retrait_publication` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `instruction_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `destinataire` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_effet` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `confirme_a` datetime(6) DEFAULT NULL,
  `resultat` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_effets_retrait_publication_a280b897e4` (`reference_effet`),
  UNIQUE KEY `uq_effets_retrait_publication_920c4acd8e` (`instruction_id`,`destinataire`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `elements_controle`
--

DROP TABLE IF EXISTS `elements_controle`;
CREATE TABLE IF NOT EXISTS `elements_controle` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `controle_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_global_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `selection_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `piece_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `edition_cible_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `offre_cible_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `contribution_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `media_soumis_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `perimetre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `empreinte_examinee` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_elements_controle_0ed016e731` (`controle_id`),
  KEY `ix_elements_controle_142313eb1f` (`dossier_global_id`),
  KEY `ix_elements_controle_3a61b1695a` (`selection_id`),
  KEY `ix_elements_controle_22f58a002c` (`piece_id`),
  KEY `ix_elements_controle_44cc54aa64` (`edition_cible_id`),
  KEY `ix_elements_controle_456622d42e` (`offre_cible_id`),
  KEY `ix_elements_controle_d4a1d9166f` (`contribution_id`),
  KEY `ix_elements_controle_4ac4d57434` (`media_soumis_id`),
  KEY `ix_elements_controle_f4fd69f4b8` (`perimetre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Stand-in structure for view `envois_defis`
-- (See below for the actual view)
--
DROP VIEW IF EXISTS `envois_defis`;
CREATE TABLE IF NOT EXISTS `envois_defis` (
`defi_id` char(26)
,`transmission_id` char(26)
,`numero_essai` bigint
,`resultat` varchar(32)
,`demande_a` datetime(6)
,`termine_a` datetime(6)
,`reference_envoi` varchar(191)
,`code_erreur` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `evenements_appareil`
--

DROP TABLE IF EXISTS `evenements_appareil`;
CREATE TABLE IF NOT EXISTS `evenements_appareil` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `appareil_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_preuve` bigint NOT NULL,
  `date` datetime(6) NOT NULL,
  `auteur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_systeme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `correlation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_evenements_appareil_4955bd56f2` (`appareil_id`),
  KEY `ix_evenements_appareil_1c7f79acb8` (`auteur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `evenements_appareil`
--
DROP TRIGGER IF EXISTS `bu_evenements_appareil_7e91f29f`;
DELIMITER $$
CREATE TRIGGER `bu_evenements_appareil_7e91f29f` BEFORE UPDATE ON `evenements_appareil` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`appareil_id` <=> OLD.`appareil_id`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`version_preuve` <=> OLD.`version_preuve`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`correlation` <=> OLD.`correlation`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `evenements_blocage`
--

DROP TABLE IF EXISTS `evenements_blocage`;
CREATE TABLE IF NOT EXISTS `evenements_blocage` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `blocage_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` datetime(6) NOT NULL,
  `auteur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_systeme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `correlation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_evenements_blocage_0deaf29506` (`blocage_id`),
  KEY `ix_evenements_blocage_1c7f79acb8` (`auteur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `evenements_blocage`
--
DROP TRIGGER IF EXISTS `bu_evenements_blocage_7bd0ae1c`;
DELIMITER $$
CREATE TRIGGER `bu_evenements_blocage_7bd0ae1c` BEFORE UPDATE ON `evenements_blocage` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`blocage_id` <=> OLD.`blocage_id`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`correlation` <=> OLD.`correlation`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `evenements_compte`
--

DROP TABLE IF EXISTS `evenements_compte`;
CREATE TABLE IF NOT EXISTS `evenements_compte` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_sujet_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `adhesion_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `compte_role_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `permission_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `attribution_interne_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `type` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` datetime(6) NOT NULL,
  `ancien_etat` text COLLATE utf8mb4_0900_bin,
  `nouvel_etat` text COLLATE utf8mb4_0900_bin,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `auteur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_systeme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `fin_prevue` datetime(6) DEFAULT NULL,
  `correlation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_evenements_compte_b209833760` (`compte_sujet_id`,`date`,`id`),
  KEY `ix_evenements_compte_fda8e38ca7` (`adhesion_id`,`compte_sujet_id`),
  KEY `ix_evenements_compte_3040e78aff` (`compte_role_id`,`compte_sujet_id`),
  KEY `ix_evenements_compte_9e0164325d` (`attribution_interne_id`,`compte_sujet_id`),
  KEY `ix_evenements_compte_577bf0c4f4` (`correlation`),
  KEY `ix_evenements_compte_50d0bf2736` (`permission_id`),
  KEY `ix_evenements_compte_1c7f79acb8` (`auteur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `evenements_compte`
--
DROP TRIGGER IF EXISTS `bu_evenements_compte_ff4485d3`;
DELIMITER $$
CREATE TRIGGER `bu_evenements_compte_ff4485d3` BEFORE UPDATE ON `evenements_compte` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`compte_sujet_id` <=> OLD.`compte_sujet_id`) OR NOT (NEW.`adhesion_id` <=> OLD.`adhesion_id`) OR NOT (NEW.`compte_role_id` <=> OLD.`compte_role_id`) OR NOT (NEW.`permission_id` <=> OLD.`permission_id`) OR NOT (NEW.`attribution_interne_id` <=> OLD.`attribution_interne_id`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`ancien_etat` <=> OLD.`ancien_etat`) OR NOT (NEW.`nouvel_etat` <=> OLD.`nouvel_etat`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) OR NOT (NEW.`fin_prevue` <=> OLD.`fin_prevue`) OR NOT (NEW.`correlation` <=> OLD.`correlation`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `evenements_conformite`
--

DROP TABLE IF EXISTS `evenements_conformite`;
CREATE TABLE IF NOT EXISTS `evenements_conformite` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `litige_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `politique_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `objet_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `phase_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `type` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` datetime(6) NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `ancien_etat` json DEFAULT NULL,
  `nouvel_etat` json DEFAULT NULL,
  `correlation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `auteur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_systeme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_evenements_conformite_841cb5934d` (`phase_id`,`signalement_id`),
  KEY `ix_evenements_conformite_855a2083e7` (`signalement_id`),
  KEY `ix_evenements_conformite_ac97dff02e` (`litige_id`),
  KEY `ix_evenements_conformite_169d85acce` (`politique_id`),
  KEY `ix_evenements_conformite_afd38cd64f` (`objet_id`),
  KEY `ix_evenements_conformite_1c7f79acb8` (`auteur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `evenements_consentement`
--

DROP TABLE IF EXISTS `evenements_consentement`;
CREATE TABLE IF NOT EXISTS `evenements_consentement` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `consentement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` datetime(6) NOT NULL,
  `source` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `canal` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_evenements_consentement_5f062e1a89` (`consentement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `evenements_consentement`
--
DROP TRIGGER IF EXISTS `bu_evenements_consentement_1f47bd52`;
DELIMITER $$
CREATE TRIGGER `bu_evenements_consentement_1f47bd52` BEFORE UPDATE ON `evenements_consentement` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`consentement_id` <=> OLD.`consentement_id`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`source` <=> OLD.`source`) OR NOT (NEW.`canal` <=> OLD.`canal`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `evenements_licence`
--

DROP TABLE IF EXISTS `evenements_licence`;
CREATE TABLE IF NOT EXISTS `evenements_licence` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `licence_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `origine_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `type` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `date` datetime(6) NOT NULL,
  `auteur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_systeme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `correlation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `reference_decision` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_evenements_licence_5bd44e52f5` (`origine_id`,`licence_id`),
  KEY `ix_evenements_licence_97d325fd54` (`licence_id`),
  KEY `ix_evenements_licence_1c7f79acb8` (`auteur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `evenements_licence`
--
DROP TRIGGER IF EXISTS `bu_evenements_licence_910e3721`;
DELIMITER $$
CREATE TRIGGER `bu_evenements_licence_910e3721` BEFORE UPDATE ON `evenements_licence` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`licence_id` <=> OLD.`licence_id`) OR NOT (NEW.`origine_id` <=> OLD.`origine_id`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) OR NOT (NEW.`correlation` <=> OLD.`correlation`) OR NOT (NEW.`reference_decision` <=> OLD.`reference_decision`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `evenements_psp`
--

DROP TABLE IF EXISTS `evenements_psp`;
CREATE TABLE IF NOT EXISTS `evenements_psp` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `configuration_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `paiement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `ref_evenement` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `cle_deduplication` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `type` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `recu_a` datetime(6) NOT NULL,
  `authenticite_verifiee` tinyint UNSIGNED NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `payload_reference` text COLLATE utf8mb4_0900_bin,
  `correlation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_evenements_psp_f25d55da2d` (`configuration_id`,`cle_deduplication`),
  UNIQUE KEY `uq_evenements_psp_b01a19ade8` (`configuration_id`,`ref_evenement`),
  KEY `ix_evenements_psp_f7db5d9425` (`statut`,`recu_a`),
  KEY `ix_evenements_psp_4e32fc5b03` (`paiement_id`,`configuration_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `evenements_publication`
--

DROP TABLE IF EXISTS `evenements_publication`;
CREATE TABLE IF NOT EXISTS `evenements_publication` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `manuscrit_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `type` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` datetime(6) NOT NULL,
  `ancien_etat` json DEFAULT NULL,
  `nouvel_etat` json DEFAULT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `auteur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_systeme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `correlation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `reference_action` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_evenements_publication_32d26f2c57` (`dossier_id`,`manuscrit_id`),
  KEY `ix_evenements_publication_62122d9acb` (`manuscrit_id`),
  KEY `ix_evenements_publication_1c7f79acb8` (`auteur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `evenements_publication`
--
DROP TRIGGER IF EXISTS `bu_evenements_publication_254bae37`;
DELIMITER $$
CREATE TRIGGER `bu_evenements_publication_254bae37` BEFORE UPDATE ON `evenements_publication` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`manuscrit_id` <=> OLD.`manuscrit_id`) OR NOT (NEW.`dossier_id` <=> OLD.`dossier_id`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`ancien_etat` <=> OLD.`ancien_etat`) OR NOT (NEW.`nouvel_etat` <=> OLD.`nouvel_etat`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) OR NOT (NEW.`correlation` <=> OLD.`correlation`) OR NOT (NEW.`reference_action` <=> OLD.`reference_action`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `evenements_taches`
--

DROP TABLE IF EXISTS `evenements_taches`;
CREATE TABLE IF NOT EXISTS `evenements_taches` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `tache_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` datetime(6) NOT NULL,
  `auteur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_systeme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `ancien_responsable_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `nouveau_responsable_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `ancien_etat` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `nouvel_etat` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `prochaine_action_a` datetime(6) DEFAULT NULL,
  `correlation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_evenements_taches_ef7597418f` (`tache_id`,`date`,`id`),
  KEY `ix_evenements_taches_6e38b1ca93` (`auteur_id`),
  KEY `ix_evenements_taches_8fcb06fcb7` (`ancien_responsable_id`),
  KEY `ix_evenements_taches_7e8c71c9be` (`nouveau_responsable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `evenements_taches`
--
DROP TRIGGER IF EXISTS `bu_evenements_taches_f32f63ab`;
DELIMITER $$
CREATE TRIGGER `bu_evenements_taches_f32f63ab` BEFORE UPDATE ON `evenements_taches` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`tache_id` <=> OLD.`tache_id`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`auteur_id` <=> OLD.`auteur_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) OR NOT (NEW.`ancien_responsable_id` <=> OLD.`ancien_responsable_id`) OR NOT (NEW.`nouveau_responsable_id` <=> OLD.`nouveau_responsable_id`) OR NOT (NEW.`ancien_etat` <=> OLD.`ancien_etat`) OR NOT (NEW.`nouvel_etat` <=> OLD.`nouvel_etat`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`prochaine_action_a` <=> OLD.`prochaine_action_a`) OR NOT (NEW.`correlation` <=> OLD.`correlation`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `evenements_verification`
--

DROP TABLE IF EXISTS `evenements_verification`;
CREATE TABLE IF NOT EXISTS `evenements_verification` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `habilitation_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `controle_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `decision_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `type` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` datetime(6) NOT NULL,
  `ancien_etat` text COLLATE utf8mb4_0900_bin,
  `nouvel_etat` text COLLATE utf8mb4_0900_bin,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `auteur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_systeme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `correlation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_evenements_verification_e3c59ff60a` (`controle_id`,`dossier_id`),
  KEY `ix_evenements_verification_86f0b18fa2` (`decision_id`,`dossier_id`),
  KEY `ix_evenements_verification_0259b9fde9` (`dossier_id`),
  KEY `ix_evenements_verification_b3ef6e8f54` (`habilitation_id`),
  KEY `ix_evenements_verification_1c7f79acb8` (`auteur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `evenements_verification`
--
DROP TRIGGER IF EXISTS `bu_evenements_verification_78d5a6ed`;
DELIMITER $$
CREATE TRIGGER `bu_evenements_verification_78d5a6ed` BEFORE UPDATE ON `evenements_verification` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`dossier_id` <=> OLD.`dossier_id`) OR NOT (NEW.`habilitation_id` <=> OLD.`habilitation_id`) OR NOT (NEW.`controle_id` <=> OLD.`controle_id`) OR NOT (NEW.`decision_id` <=> OLD.`decision_id`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`ancien_etat` <=> OLD.`ancien_etat`) OR NOT (NEW.`nouvel_etat` <=> OLD.`nouvel_etat`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) OR NOT (NEW.`correlation` <=> OLD.`correlation`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `executions_publication`
--

DROP TABLE IF EXISTS `executions_publication`;
CREATE TABLE IF NOT EXISTS `executions_publication` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_execution` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_intention` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  `publie_a` datetime(6) DEFAULT NULL,
  `annule_a` datetime(6) DEFAULT NULL,
  `motif` text COLLATE utf8mb4_0900_bin,
  `revision_execution` bigint NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_executions_publication_0259b9fde9` (`dossier_id`),
  UNIQUE KEY `uq_executions_publication_ea7b3e4580` (`reference_execution`),
  KEY `ix_executions_publication_1b624651ed` (`statut`,`cree_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `exigences_dossier`
--

DROP TABLE IF EXISTS `exigences_dossier`;
CREATE TABLE IF NOT EXISTS `exigences_dossier` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `regle_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `justification_applicabilite` text COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_exigences_dossier_ae9f8361d8` (`dossier_id`,`regle_id`),
  KEY `ix_exigences_dossier_d421e46a2f` (`regle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `exigences_verification`
--

DROP TABLE IF EXISTS `exigences_verification`;
CREATE TABLE IF NOT EXISTS `exigences_verification` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_regles` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `type_sujet` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type_organisation_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `pays` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `niveau` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `volet` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `nature_preuve` text COLLATE utf8mb4_0900_bin NOT NULL,
  `obligatoire` tinyint UNSIGNED NOT NULL,
  `autorise_non_applicable` tinyint UNSIGNED NOT NULL,
  `date_effet` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_exigences_verification_04e2d46948` (`code`,`version_regles`),
  KEY `ix_exigences_verification_272df1a952` (`type_organisation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `factures_commission`
--

DROP TABLE IF EXISTS `factures_commission`;
CREATE TABLE IF NOT EXISTS `factures_commission` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_facturation_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `regle_remuneration_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `serie` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `emetteur_cle_snap` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `identite_ae_snap` json NOT NULL,
  `identite_vendeur_snap` json NOT NULL,
  `assiette_snap` json NOT NULL,
  `regle_snap` json NOT NULL,
  `commission` decimal(20,4) NOT NULL,
  `frais` decimal(20,4) NOT NULL,
  `taxes` decimal(20,4) NOT NULL,
  `total` decimal(20,4) NOT NULL,
  `devise` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `emise_a` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_factures_commission_812499081a` (`dossier_facturation_id`),
  UNIQUE KEY `uq_factures_commission_e7d6741b79` (`emetteur_cle_snap`,`serie`,`numero`),
  KEY `ix_factures_commission_5e0edcf951` (`regle_remuneration_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `factures_commission`
--
DROP TRIGGER IF EXISTS `bu_factures_commission_1092233b`;
DELIMITER $$
CREATE TRIGGER `bu_factures_commission_1092233b` BEFORE UPDATE ON `factures_commission` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`dossier_facturation_id` <=> OLD.`dossier_facturation_id`) OR NOT (NEW.`regle_remuneration_id` <=> OLD.`regle_remuneration_id`) OR NOT (NEW.`numero` <=> OLD.`numero`) OR NOT (NEW.`serie` <=> OLD.`serie`) OR NOT (NEW.`emetteur_cle_snap` <=> OLD.`emetteur_cle_snap`) OR NOT (NEW.`identite_ae_snap` <=> OLD.`identite_ae_snap`) OR NOT (NEW.`identite_vendeur_snap` <=> OLD.`identite_vendeur_snap`) OR NOT (NEW.`assiette_snap` <=> OLD.`assiette_snap`) OR NOT (NEW.`regle_snap` <=> OLD.`regle_snap`) OR NOT (NEW.`commission` <=> OLD.`commission`) OR NOT (NEW.`frais` <=> OLD.`frais`) OR NOT (NEW.`taxes` <=> OLD.`taxes`) OR NOT (NEW.`total` <=> OLD.`total`) OR NOT (NEW.`devise` <=> OLD.`devise`) OR NOT (NEW.`emise_a` <=> OLD.`emise_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `factures_vente`
--

DROP TABLE IF EXISTS `factures_vente`;
CREATE TABLE IF NOT EXISTS `factures_vente` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_facturation_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `mandat_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `numero` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `serie` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `emetteur_type` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `emetteur_cle_snap` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `identite_vendeur_snap` json NOT NULL,
  `identite_client_snap` json NOT NULL,
  `ht` decimal(20,4) NOT NULL,
  `taxes` decimal(20,4) NOT NULL,
  `ttc` decimal(20,4) NOT NULL,
  `devise` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `emise_a` datetime(6) NOT NULL,
  `reference_document` varchar(1024) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `empreinte` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_factures_vente_812499081a` (`dossier_facturation_id`),
  UNIQUE KEY `uq_factures_vente_e7d6741b79` (`emetteur_cle_snap`,`serie`,`numero`),
  KEY `ix_factures_vente_fd3ad1101b` (`mandat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `factures_vente`
--
DROP TRIGGER IF EXISTS `bu_factures_vente_4d1a8e4d`;
DELIMITER $$
CREATE TRIGGER `bu_factures_vente_4d1a8e4d` BEFORE UPDATE ON `factures_vente` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`dossier_facturation_id` <=> OLD.`dossier_facturation_id`) OR NOT (NEW.`mandat_id` <=> OLD.`mandat_id`) OR NOT (NEW.`numero` <=> OLD.`numero`) OR NOT (NEW.`serie` <=> OLD.`serie`) OR NOT (NEW.`emetteur_type` <=> OLD.`emetteur_type`) OR NOT (NEW.`emetteur_cle_snap` <=> OLD.`emetteur_cle_snap`) OR NOT (NEW.`identite_vendeur_snap` <=> OLD.`identite_vendeur_snap`) OR NOT (NEW.`identite_client_snap` <=> OLD.`identite_client_snap`) OR NOT (NEW.`ht` <=> OLD.`ht`) OR NOT (NEW.`taxes` <=> OLD.`taxes`) OR NOT (NEW.`ttc` <=> OLD.`ttc`) OR NOT (NEW.`devise` <=> OLD.`devise`) OR NOT (NEW.`emise_a` <=> OLD.`emise_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF OLD.`reference_document` IS NOT NULL AND NOT (NEW.`reference_document` <=> OLD.`reference_document`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Document facture deja fixe';
  END IF;
  IF OLD.`empreinte` IS NOT NULL AND NOT (NEW.`empreinte` <=> OLD.`empreinte`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Document facture deja fixe';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `faits_litige`
--

DROP TABLE IF EXISTS `faits_litige`;
CREATE TABLE IF NOT EXISTS `faits_litige` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `litige_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `producteur` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_fait` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `survenu_a` datetime(6) NOT NULL,
  `recu_a` datetime(6) NOT NULL,
  `preuve` json NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_faits_litige_343e17312e` (`producteur`,`reference_fait`),
  KEY `ix_faits_litige_ac97dff02e` (`litige_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `faits_litige`
--
DROP TRIGGER IF EXISTS `bu_faits_litige_4af2d7d1`;
DELIMITER $$
CREATE TRIGGER `bu_faits_litige_4af2d7d1` BEFORE UPDATE ON `faits_litige` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`litige_id` <=> OLD.`litige_id`) OR NOT (NEW.`producteur` <=> OLD.`producteur`) OR NOT (NEW.`reference_fait` <=> OLD.`reference_fait`) OR NOT (NEW.`survenu_a` <=> OLD.`survenu_a`) OR NOT (NEW.`recu_a` <=> OLD.`recu_a`) OR NOT (NEW.`preuve` <=> OLD.`preuve`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `faits_notification`
--

DROP TABLE IF EXISTS `faits_notification`;
CREATE TABLE IF NOT EXISTS `faits_notification` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `notification_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `producteur` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_source` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `type` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `survenu_a` datetime(6) NOT NULL,
  `recu_a` datetime(6) NOT NULL,
  `preuve` json NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_faits_notification_96802877b6` (`producteur`,`reference_source`),
  UNIQUE KEY `uq_faits_notification_2c376f2058` (`id`,`notification_id`),
  KEY `ix_faits_notification_45159833ee` (`notification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `faits_notification`
--
DROP TRIGGER IF EXISTS `bu_faits_notification_252622af`;
DELIMITER $$
CREATE TRIGGER `bu_faits_notification_252622af` BEFORE UPDATE ON `faits_notification` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`notification_id` <=> OLD.`notification_id`) OR NOT (NEW.`producteur` <=> OLD.`producteur`) OR NOT (NEW.`reference_source` <=> OLD.`reference_source`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`survenu_a` <=> OLD.`survenu_a`) OR NOT (NEW.`recu_a` <=> OLD.`recu_a`) OR NOT (NEW.`preuve` <=> OLD.`preuve`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `fichiers_manuscrit`
--

DROP TABLE IF EXISTS `fichiers_manuscrit`;
CREATE TABLE IF NOT EXISTS `fichiers_manuscrit` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `manuscrit_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_privee` varchar(1024) COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `format_fichier` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `taille_octets` bigint NOT NULL,
  `depose_a` datetime(6) NOT NULL,
  `depose_par` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_fichiers_manuscrit_62122d9acb` (`manuscrit_id`),
  KEY `ix_fichiers_manuscrit_9c32561e4b` (`depose_par`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `formats_promo`
--

DROP TABLE IF EXISTS `formats_promo`;
CREATE TABLE IF NOT EXISTS `formats_promo` (
  `version_promo_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `format_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`version_promo_id`,`format_id`),
  KEY `ix_formats_promo_5532d2348c` (`format_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `format_technique`
--

DROP TABLE IF EXISTS `format_technique`;
CREATE TABLE IF NOT EXISTS `format_technique` (
  `format_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `libelle` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `sort_order` bigint NOT NULL,
  `actif` tinyint UNSIGNED NOT NULL,
  PRIMARY KEY (`format_id`),
  UNIQUE KEY `uq_format_technique_5694d08a2e` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `format_technique`
--

INSERT INTO `format_technique` (`format_id`, `code`, `libelle`, `sort_order`, `actif`) VALUES
('48K5P57SJ4SWH8VKZ8YFARG6Z4', 'audio', 'AUDIO', 2, 1),
('61W6KDFQA6550MPDCTHEAXMW8V', 'pdf', 'PDF', 1, 1),
('6TPKHAHP9VK2DKFQ4Z0TTRYW6J', 'epub', 'EPUB', 0, 1),
('7HG5ETDS8FW4X9HA1D23CRNGQH', 'paper', 'PAPER', 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `fusions_panier`
--

DROP TABLE IF EXISTS `fusions_panier`;
CREATE TABLE IF NOT EXISTS `fusions_panier` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `panier_source_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `panier_cible_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cle_idempotence` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resultat` json DEFAULT NULL,
  `cree_a` datetime(6) NOT NULL,
  `termine_a` datetime(6) DEFAULT NULL,
  `uq_fusion_ouverte` tinyint UNSIGNED GENERATED ALWAYS AS ((case when (`statut` in (_ascii'en_cours',_ascii'a_corriger')) then 1 else NULL end)) STORED,
  `uq_fusion_finale` tinyint UNSIGNED GENERATED ALWAYS AS ((case when (`statut` = _ascii'terminee') then 1 else NULL end)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_fusions_panier_9f407e61c3` (`cle_idempotence`),
  UNIQUE KEY `uq_fusions_panier_50c5a91ab7` (`panier_source_id`,`uq_fusion_ouverte`),
  UNIQUE KEY `uq_fusions_panier_c13e44a7b8` (`panier_source_id`,`uq_fusion_finale`),
  KEY `ix_fusions_panier_cefd3ffaa3` (`panier_cible_id`,`compte_id`),
  KEY `ix_fusions_panier_940f1f0c40` (`compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `gels_conservation`
--

DROP TABLE IF EXISTS `gels_conservation`;
CREATE TABLE IF NOT EXISTS `gels_conservation` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `litige_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `cause` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `debut` datetime(6) NOT NULL,
  `leve_a` datetime(6) DEFAULT NULL,
  `reference_regle` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `auteur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_systeme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gels_conservation_6a7ef1ed9f` (`cause`),
  KEY `ix_gels_conservation_407603efa5` (`signalement_id`,`leve_a`),
  KEY `ix_gels_conservation_a520c31441` (`litige_id`,`leve_a`),
  KEY `ix_gels_conservation_1c7f79acb8` (`auteur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `gels_operations_financieres`
--

DROP TABLE IF EXISTS `gels_operations_financieres`;
CREATE TABLE IF NOT EXISTS `gels_operations_financieres` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `instruction_creatrice_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cause` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `debut` datetime(6) NOT NULL,
  `fin` datetime(6) DEFAULT NULL,
  `leve_a` datetime(6) DEFAULT NULL,
  `leve_effet_a` datetime(6) DEFAULT NULL,
  `operations_bloquees` json NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_gels_operations_financieres_6a7ef1ed9f` (`cause`),
  KEY `ix_gels_operations_financieres_1543a52274` (`debut`,`fin`,`leve_effet_a`),
  KEY `ix_gels_operations_financieres_242216b08f` (`instruction_creatrice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `gels_operations_financieres`
--
DROP TRIGGER IF EXISTS `bu_gels_operations_financieres_7590c3b3`;
DELIMITER $$
CREATE TRIGGER `bu_gels_operations_financieres_7590c3b3` BEFORE UPDATE ON `gels_operations_financieres` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`instruction_creatrice_id` <=> OLD.`instruction_creatrice_id`) OR NOT (NEW.`cause` <=> OLD.`cause`) OR NOT (NEW.`debut` <=> OLD.`debut`) OR NOT (NEW.`fin` <=> OLD.`fin`) OR NOT (NEW.`operations_bloquees` <=> OLD.`operations_bloquees`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `habilitations_verification`
--

DROP TABLE IF EXISTS `habilitations_verification`;
CREATE TABLE IF NOT EXISTS `habilitations_verification` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sujet_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `niveau` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_courant_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision_courante_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `etat` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `obtenue_a` datetime(6) NOT NULL,
  `valide_jusqua` datetime(6) DEFAULT NULL,
  `motif_restriction` text COLLATE utf8mb4_0900_bin,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_habilitations_verification_c6cc25b07f` (`sujet_id`,`niveau`),
  UNIQUE KEY `uq_habilitations_verification_aaddb70f7a` (`decision_courante_id`),
  KEY `ix_habilitations_verification_8218efd771` (`dossier_courant_id`,`sujet_id`,`niveau`),
  KEY `ix_habilitations_verification_83d56e4bfc` (`etat`,`valide_jusqua`),
  KEY `ix_habilitations_verification_9122bb04b4` (`decision_courante_id`,`dossier_courant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `identites_facturation_commande`
--

DROP TABLE IF EXISTS `identites_facturation_commande`;
CREATE TABLE IF NOT EXISTS `identites_facturation_commande` (
  `commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `nom_identite` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `adresse` text COLLATE utf8mb4_0900_bin NOT NULL,
  `ville` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `pays` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `autres_mentions` json DEFAULT NULL,
  PRIMARY KEY (`commande_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `identites_facturation_commande`
--
DROP TRIGGER IF EXISTS `bu_identites_facturation_commande_ec524716`;
DELIMITER $$
CREATE TRIGGER `bu_identites_facturation_commande_ec524716` BEFORE UPDATE ON `identites_facturation_commande` FOR EACH ROW BEGIN
  IF NOT (NEW.`commande_id` <=> OLD.`commande_id`) OR NOT (NEW.`nom_identite` <=> OLD.`nom_identite`) OR NOT (NEW.`adresse` <=> OLD.`adresse`) OR NOT (NEW.`ville` <=> OLD.`ville`) OR NOT (NEW.`pays` <=> OLD.`pays`) OR NOT (NEW.`autres_mentions` <=> OLD.`autres_mentions`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`commande_id` <=> OLD.`commande_id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `identites_filigrane`
--

DROP TABLE IF EXISTS `identites_filigrane`;
CREATE TABLE IF NOT EXISTS `identites_filigrane` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `licence_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `identifiant_opaque` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `reference_secret` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `cree_a` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_identites_filigrane_97d325fd54` (`licence_id`),
  UNIQUE KEY `uq_identites_filigrane_904c5b9ae0` (`identifiant_opaque`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `instructions_conformite_paiement`
--

DROP TABLE IF EXISTS `instructions_conformite_paiement`;
CREATE TABLE IF NOT EXISTS `instructions_conformite_paiement` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `litige_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `type` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `decide_a` datetime(6) NOT NULL,
  `effet_a` datetime(6) NOT NULL,
  `reference_regle` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `gel_leve_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `auteur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_systeme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_instructions_conformite_paiement_52367a6622` (`reference`),
  KEY `ix_instructions_conformite_paiement_ac97dff02e` (`litige_id`),
  KEY `ix_instructions_conformite_paiement_3d20b9fac5` (`gel_leve_id`),
  KEY `ix_instructions_conformite_paiement_1c7f79acb8` (`auteur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `instructions_conformite_paiement`
--
DROP TRIGGER IF EXISTS `bu_instructions_conformite_paiement_46820f3e`;
DELIMITER $$
CREATE TRIGGER `bu_instructions_conformite_paiement_46820f3e` BEFORE UPDATE ON `instructions_conformite_paiement` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`litige_id` <=> OLD.`litige_id`) OR NOT (NEW.`reference` <=> OLD.`reference`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`decide_a` <=> OLD.`decide_a`) OR NOT (NEW.`effet_a` <=> OLD.`effet_a`) OR NOT (NEW.`reference_regle` <=> OLD.`reference_regle`) OR NOT (NEW.`gel_leve_id` <=> OLD.`gel_leve_id`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `instructions_retrait_publication`
--

DROP TABLE IF EXISTS `instructions_retrait_publication`;
CREATE TABLE IF NOT EXISTS `instructions_retrait_publication` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `manuscrit_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `producteur` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_instruction` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_intention` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `effet` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `restriction_source_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `restriction_levee_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `fin_effet_a` datetime(6) DEFAULT NULL,
  `cible_manuscrit_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `oeuvre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `edition_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `offre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `version_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `reference_decision` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `effet_a` datetime(6) NOT NULL,
  `recu_a` datetime(6) NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `auteur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_systeme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `resultat` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_instructions_retrait_publication_a648930d1f` (`producteur`,`reference_instruction`),
  KEY `ix_instructions_retrait_publication_0292e5fa0b` (`oeuvre_id`,`effet`,`effet_a`),
  KEY `ix_instructions_retrait_publication_09e1a813df` (`edition_id`,`effet`,`effet_a`),
  KEY `ix_instructions_retrait_publication_713d72eb1a` (`offre_id`,`effet`,`effet_a`),
  KEY `ix_instructions_retrait_publication_f09f0582a4` (`version_id`,`effet`,`effet_a`),
  KEY `ix_instructions_retrait_publication_634d3046dc` (`statut`,`effet_a`),
  KEY `ix_instructions_retrait_publication_62122d9acb` (`manuscrit_id`),
  KEY `ix_instructions_retrait_publication_41feac9dd3` (`restriction_source_id`),
  KEY `ix_instructions_retrait_publication_1c3fce3839` (`restriction_levee_id`),
  KEY `ix_instructions_retrait_publication_e970cfc4c0` (`cible_manuscrit_id`),
  KEY `ix_instructions_retrait_publication_1c7f79acb8` (`auteur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `instructions_retrait_publication`
--
DROP TRIGGER IF EXISTS `bu_instructions_retrait_publication_51d41c9a`;
DELIMITER $$
CREATE TRIGGER `bu_instructions_retrait_publication_51d41c9a` BEFORE UPDATE ON `instructions_retrait_publication` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`manuscrit_id` <=> OLD.`manuscrit_id`) OR NOT (NEW.`producteur` <=> OLD.`producteur`) OR NOT (NEW.`reference_instruction` <=> OLD.`reference_instruction`) OR NOT (NEW.`empreinte_intention` <=> OLD.`empreinte_intention`) OR NOT (NEW.`effet` <=> OLD.`effet`) OR NOT (NEW.`restriction_source_id` <=> OLD.`restriction_source_id`) OR NOT (NEW.`restriction_levee_id` <=> OLD.`restriction_levee_id`) OR NOT (NEW.`fin_effet_a` <=> OLD.`fin_effet_a`) OR NOT (NEW.`cible_manuscrit_id` <=> OLD.`cible_manuscrit_id`) OR NOT (NEW.`oeuvre_id` <=> OLD.`oeuvre_id`) OR NOT (NEW.`edition_id` <=> OLD.`edition_id`) OR NOT (NEW.`offre_id` <=> OLD.`offre_id`) OR NOT (NEW.`version_id` <=> OLD.`version_id`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`reference_decision` <=> OLD.`reference_decision`) OR NOT (NEW.`effet_a` <=> OLD.`effet_a`) OR NOT (NEW.`recu_a` <=> OLD.`recu_a`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `instructions_revocation`
--

DROP TABLE IF EXISTS `instructions_revocation`;
CREATE TABLE IF NOT EXISTS `instructions_revocation` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `producteur` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_instruction` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `cle_idempotence` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `reference_acquisition_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `origine_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `reference_decision` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `effet_a` datetime(6) NOT NULL,
  `recu_a` datetime(6) NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resultat` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_instructions_revocation_a648930d1f` (`producteur`,`reference_instruction`),
  UNIQUE KEY `uq_instructions_revocation_965fa85f85` (`producteur`,`cle_idempotence`),
  KEY `ix_instructions_revocation_9d82fc4ac3` (`reference_acquisition_id`,`statut`,`effet_a`),
  KEY `ix_instructions_revocation_b811844f7c` (`origine_id`,`reference_acquisition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `instructions_revocation`
--
DROP TRIGGER IF EXISTS `bu_instructions_revocation_44a77531`;
DELIMITER $$
CREATE TRIGGER `bu_instructions_revocation_44a77531` BEFORE UPDATE ON `instructions_revocation` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`producteur` <=> OLD.`producteur`) OR NOT (NEW.`reference_instruction` <=> OLD.`reference_instruction`) OR NOT (NEW.`cle_idempotence` <=> OLD.`cle_idempotence`) OR NOT (NEW.`reference_acquisition_id` <=> OLD.`reference_acquisition_id`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`reference_decision` <=> OLD.`reference_decision`) OR NOT (NEW.`effet_a` <=> OLD.`effet_a`) OR NOT (NEW.`recu_a` <=> OLD.`recu_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `interets_profils`
--

DROP TABLE IF EXISTS `interets_profils`;
CREATE TABLE IF NOT EXISTS `interets_profils` (
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `libelle_normalise` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`compte_id`,`libelle_normalise`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `isbn_demandes`
--

DROP TABLE IF EXISTS `isbn_demandes`;
CREATE TABLE IF NOT EXISTS `isbn_demandes` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `edition_cible_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `isbn_normalise` text COLLATE utf8mb4_0900_bin,
  `reference_preuve_privee` varchar(1024) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `empreinte_preuve` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_isbn_demandes_44cc54aa64` (`edition_cible_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `jetons_acces`
--

DROP TABLE IF EXISTS `jetons_acces`;
CREATE TABLE IF NOT EXISTS `jetons_acces` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `session_lecture_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_contenu_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `jti` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_ou_reference` varchar(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `emet_a` datetime(6) NOT NULL,
  `expire_a` datetime(6) NOT NULL,
  `revoque_a` datetime(6) DEFAULT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `perimetre_ressources` json NOT NULL,
  `audience` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `operation_autorisee` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_cle_signature` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_jetons_acces_5b1288bdb8` (`jti`),
  KEY `ix_jetons_acces_b04749ebba` (`session_lecture_id`,`statut`,`expire_a`),
  KEY `ix_jetons_acces_158d483baf` (`version_contenu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `jetons_panier_anonyme`
--

DROP TABLE IF EXISTS `jetons_panier_anonyme`;
CREATE TABLE IF NOT EXISTS `jetons_panier_anonyme` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `empreinte_reference` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  `expire_a` datetime(6) NOT NULL,
  `revoque_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_jetons_panier_anonyme_e578af1c9f` (`empreinte_reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `journaux_acces`
--

DROP TABLE IF EXISTS `journaux_acces`;
CREATE TABLE IF NOT EXISTS `journaux_acces` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` datetime(6) NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `licence_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `session_lecture_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `appareil_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `jeton_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `segment_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `ip` varchar(64) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_0900_bin,
  `duree_ms` bigint DEFAULT NULL,
  `resultat` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif_code` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `correlation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_journaux_acces_57a46ee9b8` (`compte_id`,`date`,`id`),
  KEY `ix_journaux_acces_80893aa21f` (`licence_id`,`date`,`id`),
  KEY `ix_journaux_acces_577bf0c4f4` (`correlation`),
  KEY `ix_journaux_acces_cf3446ae37` (`session_lecture_id`),
  KEY `ix_journaux_acces_4955bd56f2` (`appareil_id`),
  KEY `ix_journaux_acces_b5b6647a13` (`jeton_id`),
  KEY `ix_journaux_acces_067de3ca37` (`segment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `langue`
--

DROP TABLE IF EXISTS `langue`;
CREATE TABLE IF NOT EXISTS `langue` (
  `langue_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `nom` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `actif` tinyint UNSIGNED NOT NULL,
  PRIMARY KEY (`langue_id`),
  UNIQUE KEY `uq_langue_5694d08a2e` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `levees_gels_conservation`
--

DROP TABLE IF EXISTS `levees_gels_conservation`;
CREATE TABLE IF NOT EXISTS `levees_gels_conservation` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `gel_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` datetime(6) NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `preuve_fin_cause` json NOT NULL,
  `reference_regle` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `auteur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_systeme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_levees_gels_conservation_6bde880050` (`gel_id`),
  KEY `ix_levees_gels_conservation_1c7f79acb8` (`auteur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `levees_gels_conservation`
--
DROP TRIGGER IF EXISTS `bu_levees_gels_conservation_8101b786`;
DELIMITER $$
CREATE TRIGGER `bu_levees_gels_conservation_8101b786` BEFORE UPDATE ON `levees_gels_conservation` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`gel_id` <=> OLD.`gel_id`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`preuve_fin_cause` <=> OLD.`preuve_fin_cause`) OR NOT (NEW.`reference_regle` <=> OLD.`reference_regle`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `levees_restrictions`
--

DROP TABLE IF EXISTS `levees_restrictions`;
CREATE TABLE IF NOT EXISTS `levees_restrictions` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `restriction_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_levee_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `instruction_levee_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `reference` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `decide_a` datetime(6) NOT NULL,
  `effet_a` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_levees_restrictions_52367a6622` (`reference`),
  UNIQUE KEY `uq_levees_restrictions_2b1ac88a3b` (`ligne_levee_id`),
  UNIQUE KEY `uq_levees_restrictions_09d2996498` (`instruction_levee_id`),
  KEY `ix_levees_restrictions_9ad28b56f7` (`restriction_id`,`effet_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `levees_restrictions`
--
DROP TRIGGER IF EXISTS `bu_levees_restrictions_d0b6f049`;
DELIMITER $$
CREATE TRIGGER `bu_levees_restrictions_d0b6f049` BEFORE UPDATE ON `levees_restrictions` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`restriction_id` <=> OLD.`restriction_id`) OR NOT (NEW.`ligne_levee_id` <=> OLD.`ligne_levee_id`) OR NOT (NEW.`instruction_levee_id` <=> OLD.`instruction_levee_id`) OR NOT (NEW.`reference` <=> OLD.`reference`) OR NOT (NEW.`decide_a` <=> OLD.`decide_a`) OR NOT (NEW.`effet_a` <=> OLD.`effet_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `liaisons_compensation`
--

DROP TABLE IF EXISTS `liaisons_compensation`;
CREATE TABLE IF NOT EXISTS `liaisons_compensation` (
  `demande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_remboursement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `lie_a` datetime(6) NOT NULL,
  `reference_reponse` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`demande_id`),
  KEY `ix_liaisons_compensation_016767a439` (`dossier_remboursement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `licences_contenu`
--

DROP TABLE IF EXISTS `licences_contenu`;
CREATE TABLE IF NOT EXISTS `licences_contenu` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `offre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_licences_contenu_6880210b9a` (`compte_id`,`offre_id`),
  UNIQUE KEY `uq_licences_contenu_de6658c5d7` (`id`,`compte_id`,`offre_id`),
  UNIQUE KEY `uq_licences_contenu_a6e60fd4c7` (`id`,`compte_id`),
  KEY `ix_licences_contenu_0d7af68203` (`offre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `lignes_avoir`
--

DROP TABLE IF EXISTS `lignes_avoir`;
CREATE TABLE IF NOT EXISTS `lignes_avoir` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `avoir_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_facture_vente_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `quantite` bigint DEFAULT NULL,
  `montant` decimal(20,4) NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_lignes_avoir_42318f3fe1` (`avoir_id`),
  KEY `ix_lignes_avoir_49ef57d2af` (`ligne_facture_vente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `lignes_avoir`
--
DROP TRIGGER IF EXISTS `bu_lignes_avoir_73c126a2`;
DELIMITER $$
CREATE TRIGGER `bu_lignes_avoir_73c126a2` BEFORE UPDATE ON `lignes_avoir` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`avoir_id` <=> OLD.`avoir_id`) OR NOT (NEW.`ligne_facture_vente_id` <=> OLD.`ligne_facture_vente_id`) OR NOT (NEW.`quantite` <=> OLD.`quantite`) OR NOT (NEW.`montant` <=> OLD.`montant`) OR NOT (NEW.`motif` <=> OLD.`motif`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `lignes_commande`
--

DROP TABLE IF EXISTS `lignes_commande`;
CREATE TABLE IF NOT EXISTS `lignes_commande` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `offre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `vendeur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `quantite` bigint NOT NULL,
  `titre_snap` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `format_snap` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `condition_snap` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `modalite_snap` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `isbn_snap` text COLLATE utf8mb4_0900_bin,
  `vendeur_identite_snap` json NOT NULL,
  `prix_unitaire` decimal(20,4) NOT NULL,
  `brut_ligne` decimal(20,4) NOT NULL,
  `remise_ligne` decimal(20,4) NOT NULL,
  `livraison_ligne` decimal(20,4) NOT NULL,
  `taxe_ligne` decimal(20,4) NOT NULL,
  `total_ligne` decimal(20,4) NOT NULL,
  `devise` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type_ligne` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `regles_calcul_snap` json NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_lignes_commande_04f2d585b4` (`commande_id`,`vendeur_id`),
  KEY `ix_lignes_commande_7fb72176f0` (`commande_id`,`devise`),
  KEY `ix_lignes_commande_9eaa7978a9` (`offre_id`,`vendeur_id`),
  KEY `ix_lignes_commande_59710ce746` (`vendeur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `lignes_commande`
--
DROP TRIGGER IF EXISTS `bu_lignes_commande_c59d0d6c`;
DELIMITER $$
CREATE TRIGGER `bu_lignes_commande_c59d0d6c` BEFORE UPDATE ON `lignes_commande` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`commande_id` <=> OLD.`commande_id`) OR NOT (NEW.`offre_id` <=> OLD.`offre_id`) OR NOT (NEW.`vendeur_id` <=> OLD.`vendeur_id`) OR NOT (NEW.`quantite` <=> OLD.`quantite`) OR NOT (NEW.`titre_snap` <=> OLD.`titre_snap`) OR NOT (NEW.`format_snap` <=> OLD.`format_snap`) OR NOT (NEW.`condition_snap` <=> OLD.`condition_snap`) OR NOT (NEW.`modalite_snap` <=> OLD.`modalite_snap`) OR NOT (NEW.`isbn_snap` <=> OLD.`isbn_snap`) OR NOT (NEW.`vendeur_identite_snap` <=> OLD.`vendeur_identite_snap`) OR NOT (NEW.`prix_unitaire` <=> OLD.`prix_unitaire`) OR NOT (NEW.`brut_ligne` <=> OLD.`brut_ligne`) OR NOT (NEW.`remise_ligne` <=> OLD.`remise_ligne`) OR NOT (NEW.`livraison_ligne` <=> OLD.`livraison_ligne`) OR NOT (NEW.`taxe_ligne` <=> OLD.`taxe_ligne`) OR NOT (NEW.`total_ligne` <=> OLD.`total_ligne`) OR NOT (NEW.`devise` <=> OLD.`devise`) OR NOT (NEW.`type_ligne` <=> OLD.`type_ligne`) OR NOT (NEW.`regles_calcul_snap` <=> OLD.`regles_calcul_snap`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `lignes_compensation_conformite`
--

DROP TABLE IF EXISTS `lignes_compensation_conformite`;
CREATE TABLE IF NOT EXISTS `lignes_compensation_conformite` (
  `demande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `effet_droit_demande` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`demande_id`,`ligne_commande_id`),
  KEY `ix_lignes_compensation_conformite_3ff8abbe12` (`ligne_commande_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `lignes_decision_remboursement`
--

DROP TABLE IF EXISTS `lignes_decision_remboursement`;
CREATE TABLE IF NOT EXISTS `lignes_decision_remboursement` (
  `decision_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `montant_autorise` decimal(20,4) NOT NULL,
  `quantite_annulee` bigint DEFAULT NULL,
  `effet_droit` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`decision_id`,`ligne_commande_id`),
  KEY `ix_lignes_decision_remboursement_3ff8abbe12` (`ligne_commande_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `lignes_facture_vente`
--

DROP TABLE IF EXISTS `lignes_facture_vente`;
CREATE TABLE IF NOT EXISTS `lignes_facture_vente` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `facture_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `libelle_snap` text COLLATE utf8mb4_0900_bin NOT NULL,
  `quantite` bigint NOT NULL,
  `pu` decimal(20,4) NOT NULL,
  `remise` decimal(20,4) NOT NULL,
  `livraison` decimal(20,4) NOT NULL,
  `taxes` decimal(20,4) NOT NULL,
  `total` decimal(20,4) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lignes_facture_vente_3ff8abbe12` (`ligne_commande_id`),
  KEY `ix_lignes_facture_vente_2efab036bc` (`facture_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `lignes_facture_vente`
--
DROP TRIGGER IF EXISTS `bu_lignes_facture_vente_fb570247`;
DELIMITER $$
CREATE TRIGGER `bu_lignes_facture_vente_fb570247` BEFORE UPDATE ON `lignes_facture_vente` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`facture_id` <=> OLD.`facture_id`) OR NOT (NEW.`ligne_commande_id` <=> OLD.`ligne_commande_id`) OR NOT (NEW.`libelle_snap` <=> OLD.`libelle_snap`) OR NOT (NEW.`quantite` <=> OLD.`quantite`) OR NOT (NEW.`pu` <=> OLD.`pu`) OR NOT (NEW.`remise` <=> OLD.`remise`) OR NOT (NEW.`livraison` <=> OLD.`livraison`) OR NOT (NEW.`taxes` <=> OLD.`taxes`) OR NOT (NEW.`total` <=> OLD.`total`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `lignes_ordres_retrait`
--

DROP TABLE IF EXISTS `lignes_ordres_retrait`;
CREATE TABLE IF NOT EXISTS `lignes_ordres_retrait` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ordre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resolution_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `portee_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `action` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_effet` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `restriction_levee_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `effet_a` datetime(6) NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lignes_ordres_retrait_a280b897e4` (`reference_effet`),
  KEY `ix_lignes_ordres_retrait_d42c73ee45` (`resolution_id`,`action`,`effet_a`),
  KEY `ix_lignes_ordres_retrait_634d3046dc` (`statut`,`effet_a`),
  KEY `ix_lignes_ordres_retrait_352fe72186` (`ordre_id`),
  KEY `ix_lignes_ordres_retrait_5e63526464` (`portee_id`),
  KEY `ix_lignes_ordres_retrait_1c3fce3839` (`restriction_levee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `lignes_ordres_retrait`
--
DROP TRIGGER IF EXISTS `bu_lignes_ordres_retrait_d70a5eb3`;
DELIMITER $$
CREATE TRIGGER `bu_lignes_ordres_retrait_d70a5eb3` BEFORE UPDATE ON `lignes_ordres_retrait` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`ordre_id` <=> OLD.`ordre_id`) OR NOT (NEW.`resolution_id` <=> OLD.`resolution_id`) OR NOT (NEW.`portee_id` <=> OLD.`portee_id`) OR NOT (NEW.`action` <=> OLD.`action`) OR NOT (NEW.`reference_effet` <=> OLD.`reference_effet`) OR NOT (NEW.`restriction_levee_id` <=> OLD.`restriction_levee_id`) OR NOT (NEW.`effet_a` <=> OLD.`effet_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `lignes_panier`
--

DROP TABLE IF EXISTS `lignes_panier`;
CREATE TABLE IF NOT EXISTS `lignes_panier` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `panier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `offre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `quantite` bigint NOT NULL,
  `prix_unitaire_affiche` decimal(20,4) NOT NULL,
  `devise` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ajoute_a` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_lignes_panier_c709096419` (`panier_id`,`offre_id`),
  KEY `ix_lignes_panier_e96b881fdb` (`panier_id`,`devise`),
  KEY `ix_lignes_panier_0d7af68203` (`offre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `litiges_anomalies`
--

DROP TABLE IF EXISTS `litiges_anomalies`;
CREATE TABLE IF NOT EXISTS `litiges_anomalies` (
  `litige_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `anomalie_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`litige_id`,`anomalie_id`),
  KEY `ix_litiges_anomalies_28885fb289` (`anomalie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `litiges_commandes`
--

DROP TABLE IF EXISTS `litiges_commandes`;
CREATE TABLE IF NOT EXISTS `litiges_commandes` (
  `litige_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`litige_id`,`commande_id`),
  KEY `ix_litiges_commandes_94c4e6db2b` (`commande_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `litiges_paiements`
--

DROP TABLE IF EXISTS `litiges_paiements`;
CREATE TABLE IF NOT EXISTS `litiges_paiements` (
  `litige_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `paiement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`litige_id`,`paiement_id`),
  KEY `ix_litiges_paiements_68d4d872e4` (`paiement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `mandats_facturation`
--

DROP TABLE IF EXISTS `mandats_facturation`;
CREATE TABLE IF NOT EXISTS `mandats_facturation` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `vendeur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signe_le` date NOT NULL,
  `valide_jusqua` datetime(6) DEFAULT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_preuve` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_mandats_facturation_f03e110afc` (`id`,`vendeur_id`),
  KEY `ix_mandats_facturation_59710ce746` (`vendeur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `manifestes_contenu`
--

DROP TABLE IF EXISTS `manifestes_contenu`;
CREATE TABLE IF NOT EXISTS `manifestes_contenu` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_contenu_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `structure` json NOT NULL,
  `nombre_parties` bigint NOT NULL,
  `empreinte` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_manifestes_contenu_158d483baf` (`version_contenu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `manuscrits`
--

DROP TABLE IF EXISTS `manuscrits`;
CREATE TABLE IF NOT EXISTS `manuscrits` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `deposant_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `organisation_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `titre_travail` varchar(512) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `cycle` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  `abandonne_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_manuscrits_3844c98e10` (`deposant_compte_id`),
  KEY `ix_manuscrits_1b213432c4` (`organisation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `medias_prives`
--

DROP TABLE IF EXISTS `medias_prives`;
CREATE TABLE IF NOT EXISTS `medias_prives` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `edition_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `format_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `collection` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `disque_prive` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_objet_privee` varchar(1024) COLLATE utf8mb4_0900_bin NOT NULL,
  `actif` tinyint UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_medias_prives_cf279400f6` (`edition_id`),
  KEY `ix_medias_prives_5532d2348c` (`format_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `medias_soumis_publication`
--

DROP TABLE IF EXISTS `medias_soumis_publication`;
CREATE TABLE IF NOT EXISTS `medias_soumis_publication` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `usage` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_privee` varchar(1024) COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `format` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `depose_a` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_medias_soumis_publication_0259b9fde9` (`dossier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `media_catalogue`
--

DROP TABLE IF EXISTS `media_catalogue`;
CREATE TABLE IF NOT EXISTS `media_catalogue` (
  `media_catalogue_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `oeuvre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `edition_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `type` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_publique` varchar(1024) COLLATE utf8mb4_0900_bin NOT NULL,
  `texte_alternatif` text COLLATE utf8mb4_0900_bin,
  `ordre` bigint NOT NULL,
  `actif` tinyint UNSIGNED NOT NULL,
  `retire_a` datetime(6) DEFAULT NULL,
  `volume_apercu` decimal(20,8) DEFAULT NULL,
  `unite_volume` text COLLATE utf8mb4_0900_bin,
  PRIMARY KEY (`media_catalogue_id`),
  KEY `ix_media_catalogue_d7670e9f8c` (`oeuvre_id`,`actif`,`ordre`),
  KEY `ix_media_catalogue_69ce383f06` (`edition_id`,`actif`,`ordre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `messages_sortants`
--

DROP TABLE IF EXISTS `messages_sortants`;
CREATE TABLE IF NOT EXISTS `messages_sortants` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cle_idempotence` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `type` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `operation` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `correlation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `demande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `defi_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `evenement_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `evenement_kyc_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `version_objet` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `producteur` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `schema_message_version` bigint NOT NULL,
  `empreinte_intention` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `donnees_minimales` json NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_messages_sortants_9f407e61c3` (`cle_idempotence`),
  KEY `ix_messages_sortants_490c6ec1ce` (`type`,`cree_a`),
  KEY `ix_messages_sortants_577bf0c4f4` (`correlation`),
  KEY `ix_messages_sortants_940f1f0c40` (`compte_id`),
  KEY `ix_messages_sortants_0259b9fde9` (`dossier_id`),
  KEY `ix_messages_sortants_bed904885e` (`demande_id`),
  KEY `ix_messages_sortants_e25d4f3ebc` (`defi_id`),
  KEY `ix_messages_sortants_f8c919a6d0` (`evenement_compte_id`),
  KEY `ix_messages_sortants_abb3be0c44` (`evenement_kyc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `messages_sortants`
--
DROP TRIGGER IF EXISTS `bu_messages_sortants_93ab5ac4`;
DELIMITER $$
CREATE TRIGGER `bu_messages_sortants_93ab5ac4` BEFORE UPDATE ON `messages_sortants` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`cle_idempotence` <=> OLD.`cle_idempotence`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`operation` <=> OLD.`operation`) OR NOT (NEW.`correlation` <=> OLD.`correlation`) OR NOT (NEW.`compte_id` <=> OLD.`compte_id`) OR NOT (NEW.`dossier_id` <=> OLD.`dossier_id`) OR NOT (NEW.`demande_id` <=> OLD.`demande_id`) OR NOT (NEW.`defi_id` <=> OLD.`defi_id`) OR NOT (NEW.`evenement_compte_id` <=> OLD.`evenement_compte_id`) OR NOT (NEW.`evenement_kyc_id` <=> OLD.`evenement_kyc_id`) OR NOT (NEW.`version_objet` <=> OLD.`version_objet`) OR NOT (NEW.`producteur` <=> OLD.`producteur`) OR NOT (NEW.`schema_message_version` <=> OLD.`schema_message_version`) OR NOT (NEW.`empreinte_intention` <=> OLD.`empreinte_intention`) OR NOT (NEW.`donnees_minimales` <=> OLD.`donnees_minimales`) OR NOT (NEW.`cree_a` <=> OLD.`cree_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `mesures_conservatoires`
--

DROP TABLE IF EXISTS `mesures_conservatoires`;
CREATE TABLE IF NOT EXISTS `mesures_conservatoires` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `phase_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  `decideur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_regle` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `decide_a` datetime(6) NOT NULL,
  `debut` datetime(6) NOT NULL,
  `fin` datetime(6) NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_mesures_conservatoires_902b502e39` (`phase_id`),
  KEY `ix_mesures_conservatoires_d6d83fdc33` (`decideur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `metadonnees_bibliotheque`
--

DROP TABLE IF EXISTS `metadonnees_bibliotheque`;
CREATE TABLE IF NOT EXISTS `metadonnees_bibliotheque` (
  `origine_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `titre_historique` varchar(512) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `format_historique` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `source` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_preuve` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `capture_a` datetime(6) NOT NULL,
  PRIMARY KEY (`origine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `niveau_educatif`
--

DROP TABLE IF EXISTS `niveau_educatif`;
CREATE TABLE IF NOT EXISTS `niveau_educatif` (
  `niveau_educatif_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `parent_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `slug` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `nom` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `sort_order` bigint NOT NULL,
  `actif` tinyint UNSIGNED NOT NULL,
  PRIMARY KEY (`niveau_educatif_id`),
  UNIQUE KEY `uq_niveau_educatif_cd03861f0f` (`slug`),
  KEY `ix_niveau_educatif_7b54484fae` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `notifications_fournisseur`
--

DROP TABLE IF EXISTS `notifications_fournisseur`;
CREATE TABLE IF NOT EXISTS `notifications_fournisseur` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `partie_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `canal` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `destinataire_snap` json NOT NULL,
  `contenu_snap` json NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `preparee_a` datetime(6) NOT NULL,
  `envoyee_a` datetime(6) DEFAULT NULL,
  `livree_a` datetime(6) DEFAULT NULL,
  `accuse_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notifications_fournisseur_52367a6622` (`reference`),
  UNIQUE KEY `uq_notifications_fournisseur_2d9bf671d7` (`id`,`partie_id`),
  KEY `ix_notifications_fournisseur_fa9d31b5b8` (`partie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `objets_conserves`
--

DROP TABLE IF EXISTS `objets_conserves`;
CREATE TABLE IF NOT EXISTS `objets_conserves` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `proprietaire` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type_objet` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `adaptateur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_objet` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `version_objet` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `categorie` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_preuve` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `empreinte` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `constate_a` datetime(6) NOT NULL,
  `etat` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_objets_conserves_2ae5636f5d` (`proprietaire`,`type_objet`,`reference_objet`,`version_objet`),
  KEY `ix_objets_conserves_269aeee7f6` (`adaptateur_id`,`proprietaire`,`type_objet`,`categorie`),
  KEY `ix_objets_conserves_603be81455` (`categorie`,`etat`,`constate_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `objets_dossiers_conformite`
--

DROP TABLE IF EXISTS `objets_dossiers_conformite`;
CREATE TABLE IF NOT EXISTS `objets_dossiers_conformite` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `objet_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `litige_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `lie_a` datetime(6) NOT NULL,
  `justification` text COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_objets_dossiers_conformite_27074f7c24` (`objet_id`,`signalement_id`),
  UNIQUE KEY `uq_objets_dossiers_conformite_8091f350b1` (`objet_id`,`litige_id`),
  KEY `ix_objets_dossiers_conformite_855a2083e7` (`signalement_id`),
  KEY `ix_objets_dossiers_conformite_ac97dff02e` (`litige_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `objets_geles`
--

DROP TABLE IF EXISTS `objets_geles`;
CREATE TABLE IF NOT EXISTS `objets_geles` (
  `gel_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `objet_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `rattache_a` datetime(6) NOT NULL,
  PRIMARY KEY (`gel_id`,`objet_id`),
  KEY `ix_objets_geles_afd38cd64f` (`objet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `oeuvre`
--

DROP TABLE IF EXISTS `oeuvre`;
CREATE TABLE IF NOT EXISTS `oeuvre` (
  `oeuvre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `slug` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `titre` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `sous_titre` varchar(512) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `resume` text COLLATE utf8mb4_0900_bin,
  `pages` bigint DEFAULT NULL,
  `langue_principale_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `availability` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `badge` text COLLATE utf8mb4_0900_bin,
  `africain` tinyint UNSIGNED NOT NULL,
  `mis_en_avant` tinyint UNSIGNED NOT NULL,
  `publie_a` datetime(6) DEFAULT NULL,
  `note_moyenne` decimal(20,8) DEFAULT NULL,
  `avis_count` bigint NOT NULL,
  `retire_a` datetime(6) DEFAULT NULL,
  `niveau_educatif_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `collection_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`oeuvre_id`),
  UNIQUE KEY `uq_oeuvre_cd03861f0f` (`slug`),
  KEY `ix_oeuvre_f61eb7fd13` (`statut`,`availability`,`publie_a`,`oeuvre_id`),
  KEY `ix_oeuvre_84aa491f0b` (`collection_id`,`statut`),
  KEY `ix_oeuvre_2603951ba7` (`langue_principale_id`),
  KEY `ix_oeuvre_3479940c48` (`niveau_educatif_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `oeuvre_auteur`
--

DROP TABLE IF EXISTS `oeuvre_auteur`;
CREATE TABLE IF NOT EXISTS `oeuvre_auteur` (
  `oeuvre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `auteur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ordre_affichage` bigint DEFAULT NULL,
  `role` text COLLATE utf8mb4_0900_bin,
  PRIMARY KEY (`oeuvre_id`,`auteur_id`),
  KEY `ix_oeuvre_auteur_6e38b1ca93` (`auteur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `oeuvre_categorie`
--

DROP TABLE IF EXISTS `oeuvre_categorie`;
CREATE TABLE IF NOT EXISTS `oeuvre_categorie` (
  `oeuvre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `categorie_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`oeuvre_id`,`categorie_id`),
  KEY `ix_oeuvre_categorie_52493cdd56` (`categorie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `oeuvre_langue`
--

DROP TABLE IF EXISTS `oeuvre_langue`;
CREATE TABLE IF NOT EXISTS `oeuvre_langue` (
  `oeuvre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `langue_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`oeuvre_id`,`langue_id`),
  KEY `ix_oeuvre_langue_4a0e2288d6` (`langue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `offre`
--

DROP TABLE IF EXISTS `offre`;
CREATE TABLE IF NOT EXISTS `offre` (
  `offre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `edition_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `format_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `vendeur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `modalite_commerciale` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `prix` decimal(20,4) NOT NULL,
  `devise` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `condition` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sku` text COLLATE utf8mb4_0900_bin,
  `actif` tinyint UNSIGNED NOT NULL,
  `stock` bigint DEFAULT NULL,
  `retire_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`offre_id`),
  UNIQUE KEY `uq_offre_ae7cb3843a` (`edition_id`,`format_id`,`vendeur_id`,`condition`,`modalite_commerciale`,`devise`),
  UNIQUE KEY `uq_offre_9eaa7978a9` (`offre_id`,`vendeur_id`),
  KEY `ix_offre_3772191b07` (`edition_id`,`actif`,`format_id`,`devise`,`prix`),
  KEY `ix_offre_5532d2348c` (`format_id`),
  KEY `ix_offre_59710ce746` (`vendeur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `offres_cibles`
--

DROP TABLE IF EXISTS `offres_cibles`;
CREATE TABLE IF NOT EXISTS `offres_cibles` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `edition_cible_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_locale` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `format_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `vendeur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `condition` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `modalite` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `prix` decimal(20,4) DEFAULT NULL,
  `devise` varchar(32) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `actif_souhaite` tinyint UNSIGNED NOT NULL,
  `offre_cible_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `empreinte_base_offre` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `snapshot_base_offre` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_offres_cibles_b967beed38` (`edition_cible_id`,`reference_locale`),
  KEY `ix_offres_cibles_5532d2348c` (`format_id`),
  KEY `ix_offres_cibles_59710ce746` (`vendeur_id`),
  KEY `ix_offres_cibles_456622d42e` (`offre_cible_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `offres_contenu`
--

DROP TABLE IF EXISTS `offres_contenu`;
CREATE TABLE IF NOT EXISTS `offres_contenu` (
  `offre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_contenu_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `actif` tinyint UNSIGNED NOT NULL,
  PRIMARY KEY (`offre_id`,`version_contenu_id`),
  KEY `ix_offres_contenu_158d483baf` (`version_contenu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `offres_stock`
--

DROP TABLE IF EXISTS `offres_stock`;
CREATE TABLE IF NOT EXISTS `offres_stock` (
  `offre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `unite_stock_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`offre_id`),
  KEY `ix_offres_stock_d632d23af0` (`unite_stock_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `ordres_retrait`
--

DROP TABLE IF EXISTS `ordres_retrait`;
CREATE TABLE IF NOT EXISTS `ordres_retrait` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_intention` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `mesure_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `cree_a` datetime(6) NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `execute_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_ordres_retrait_52367a6622` (`reference`),
  KEY `ix_ordres_retrait_1b624651ed` (`statut`,`cree_a`),
  KEY `ix_ordres_retrait_3d7b176fd7` (`decision_id`),
  KEY `ix_ordres_retrait_04cdbb6725` (`mesure_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `organisations`
--

DROP TABLE IF EXISTS `organisations`;
CREATE TABLE IF NOT EXISTS `organisations` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type_organisation_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `raison_sociale` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `email_pro` text COLLATE utf8mb4_0900_bin,
  `site` text COLLATE utf8mb4_0900_bin,
  `pays` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ville` varchar(512) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `statut_operationnel` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_organisations_272df1a952` (`type_organisation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `origines_acquisition`
--

DROP TABLE IF EXISTS `origines_acquisition`;
CREATE TABLE IF NOT EXISTS `origines_acquisition` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `licence_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_acquisition_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `origine` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `debut` datetime(6) DEFAULT NULL,
  `fin` datetime(6) DEFAULT NULL,
  `cree_a` datetime(6) NOT NULL,
  `attribue_a` datetime(6) DEFAULT NULL,
  `revoque_a` datetime(6) DEFAULT NULL,
  `motif` text COLLATE utf8mb4_0900_bin,
  `reference_preuve` json NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_origines_acquisition_967027099e` (`reference_acquisition_id`),
  UNIQUE KEY `uq_origines_acquisition_b434481b84` (`id`,`reference_acquisition_id`,`licence_id`),
  UNIQUE KEY `uq_origines_acquisition_bce2fa9e9e` (`id`,`licence_id`),
  UNIQUE KEY `uq_origines_acquisition_0a81dee31f` (`id`,`reference_acquisition_id`),
  UNIQUE KEY `uq_origines_acquisition_3ff8abbe12` (`ligne_commande_id`),
  KEY `ix_origines_acquisition_612e7cec9f` (`licence_id`,`statut`,`fin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `origines_acquisition`
--
DROP TRIGGER IF EXISTS `bu_origines_acquisition_daf1156e`;
DELIMITER $$
CREATE TRIGGER `bu_origines_acquisition_daf1156e` BEFORE UPDATE ON `origines_acquisition` FOR EACH ROW BEGIN
  IF OLD.`statut` IN ('revoked','expired','refusee') AND NOT (NEW.`statut` <=> OLD.`statut`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Etat terminal : nouvelle demande requise';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`licence_id` <=> OLD.`licence_id`) OR NOT (NEW.`reference_acquisition_id` <=> OLD.`reference_acquisition_id`) OR NOT (NEW.`origine` <=> OLD.`origine`) OR NOT (NEW.`ligne_commande_id` <=> OLD.`ligne_commande_id`) OR NOT (NEW.`cree_a` <=> OLD.`cree_a`) OR NOT (NEW.`reference_preuve` <=> OLD.`reference_preuve`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Provenance acquisition immuable';
  END IF;
  IF OLD.`statut` <> 'en_attente' AND (NOT (NEW.`debut` <=> OLD.`debut`) OR NOT (NEW.`fin` <=> OLD.`fin`)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Periode acquisition figee';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `paiements`
--

DROP TABLE IF EXISTS `paiements`;
CREATE TABLE IF NOT EXISTS `paiements` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `configuration_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ref_interne` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `cle_initiation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_intention` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `montant` decimal(20,4) NOT NULL,
  `devise` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `moyen` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ref_externe` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `initie_a` datetime(6) NOT NULL,
  `confirme_a` datetime(6) DEFAULT NULL,
  `expire_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_paiements_b8ffdb38cb` (`ref_interne`),
  UNIQUE KEY `uq_paiements_5869782377` (`configuration_id`,`cle_initiation`),
  UNIQUE KEY `uq_paiements_df567f8417` (`id`,`configuration_id`),
  UNIQUE KEY `uq_paiements_73595fbbf9` (`id`,`devise`),
  UNIQUE KEY `uq_paiements_f7e0bfaedc` (`id`,`commande_id`,`devise`),
  UNIQUE KEY `uq_paiements_3093a88ffc` (`configuration_id`,`ref_externe`),
  KEY `ix_paiements_3ebd612a25` (`configuration_id`,`moyen`,`devise`),
  KEY `ix_paiements_5162febf56` (`commande_id`,`statut`),
  KEY `ix_paiements_da4bf8316f` (`statut`,`initie_a`),
  KEY `ix_paiements_7fb72176f0` (`commande_id`,`devise`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `paiements`
--
DROP TRIGGER IF EXISTS `bu_paiements_ac616d99`;
DELIMITER $$
CREATE TRIGGER `bu_paiements_ac616d99` BEFORE UPDATE ON `paiements` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`commande_id` <=> OLD.`commande_id`) OR NOT (NEW.`configuration_id` <=> OLD.`configuration_id`) OR NOT (NEW.`ref_interne` <=> OLD.`ref_interne`) OR NOT (NEW.`cle_initiation` <=> OLD.`cle_initiation`) OR NOT (NEW.`empreinte_intention` <=> OLD.`empreinte_intention`) OR NOT (NEW.`montant` <=> OLD.`montant`) OR NOT (NEW.`devise` <=> OLD.`devise`) OR NOT (NEW.`moyen` <=> OLD.`moyen`) OR NOT (NEW.`initie_a` <=> OLD.`initie_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `paniers`
--

DROP TABLE IF EXISTS `paniers`;
CREATE TABLE IF NOT EXISTS `paniers` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `jeton_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `devise` varchar(32) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  `maj_a` datetime(6) NOT NULL,
  `uq_panier_actif` tinyint UNSIGNED GENERATED ALWAYS AS ((case when (`statut` = _ascii'actif') then 1 else NULL end)) STORED,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_paniers_b5b6647a13` (`jeton_id`),
  UNIQUE KEY `uq_paniers_73595fbbf9` (`id`,`devise`),
  UNIQUE KEY `uq_paniers_a6e60fd4c7` (`id`,`compte_id`),
  UNIQUE KEY `uq_paniers_ae99e8a7f5` (`compte_id`,`uq_panier_actif`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `parametres_delai_conformite`
--

DROP TABLE IF EXISTS `parametres_delai_conformite`;
CREATE TABLE IF NOT EXISTS `parametres_delai_conformite` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version` bigint NOT NULL,
  `declencheur` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `duree` decimal(20,8) NOT NULL,
  `unite` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `calendrier_reference` text COLLATE utf8mb4_0900_bin NOT NULL,
  `fuseau` text COLLATE utf8mb4_0900_bin NOT NULL,
  `preuve_requise` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `regle_depassement` json NOT NULL,
  `valide_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_parametres_delai_conformite_f5b433d863` (`code`,`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `parties_cibles`
--

DROP TABLE IF EXISTS `parties_cibles`;
CREATE TABLE IF NOT EXISTS `parties_cibles` (
  `partie_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resolution_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`partie_id`,`resolution_id`),
  KEY `ix_parties_cibles_4569ddcc79` (`resolution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `parties_fournisseur`
--

DROP TABLE IF EXISTS `parties_fournisseur`;
CREATE TABLE IF NOT EXISTS `parties_fournisseur` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `phase_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `qualite` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `organisation_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `vendeur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `contact_verifie` json NOT NULL,
  `preuve_mandat` json NOT NULL,
  `verifie_a` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_parties_fournisseur_0d5c6856fb` (`id`,`phase_id`),
  KEY `ix_parties_fournisseur_902b502e39` (`phase_id`),
  KEY `ix_parties_fournisseur_940f1f0c40` (`compte_id`),
  KEY `ix_parties_fournisseur_1b213432c4` (`organisation_id`),
  KEY `ix_parties_fournisseur_59710ce746` (`vendeur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `perimetres_actes`
--

DROP TABLE IF EXISTS `perimetres_actes`;
CREATE TABLE IF NOT EXISTS `perimetres_actes` (
  `perimetre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `acte` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`perimetre_id`,`acte`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `perimetres_droits_publication`
--

DROP TABLE IF EXISTS `perimetres_droits_publication`;
CREATE TABLE IF NOT EXISTS `perimetres_droits_publication` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_declarant` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `declaration` json NOT NULL,
  `debut` datetime(6) DEFAULT NULL,
  `fin` datetime(6) DEFAULT NULL,
  `mode_territoires` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_perimetres_droits_publication_0259b9fde9` (`dossier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `perimetres_editions`
--

DROP TABLE IF EXISTS `perimetres_editions`;
CREATE TABLE IF NOT EXISTS `perimetres_editions` (
  `perimetre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `edition_cible_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`perimetre_id`,`edition_cible_id`),
  KEY `ix_perimetres_editions_44cc54aa64` (`edition_cible_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `perimetres_formats`
--

DROP TABLE IF EXISTS `perimetres_formats`;
CREATE TABLE IF NOT EXISTS `perimetres_formats` (
  `perimetre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `format_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`perimetre_id`,`format_id`),
  KEY `ix_perimetres_formats_5532d2348c` (`format_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `perimetres_pieces`
--

DROP TABLE IF EXISTS `perimetres_pieces`;
CREATE TABLE IF NOT EXISTS `perimetres_pieces` (
  `perimetre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `piece_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`perimetre_id`,`piece_id`),
  KEY `ix_perimetres_pieces_22f58a002c` (`piece_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `perimetres_territoires`
--

DROP TABLE IF EXISTS `perimetres_territoires`;
CREATE TABLE IF NOT EXISTS `perimetres_territoires` (
  `perimetre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `territoire_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`perimetre_id`,`territoire_id`),
  KEY `ix_perimetres_territoires_6590bb9872` (`territoire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `permissions_internes`
--

DROP TABLE IF EXISTS `permissions_internes`;
CREATE TABLE IF NOT EXISTS `permissions_internes` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `libelle` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permissions_internes_5694d08a2e` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `permissions_internes`
--

INSERT INTO `permissions_internes` (`id`, `code`, `libelle`) VALUES
('05SVBJSZC1PMMDQSZJH6E1PX5A', 'configurer_service', 'configurer_service'),
('0V1B99WWM88PT2E2CCCKXC04F6', 'gerer_facturation', 'gerer_facturation'),
('169JE2F3J7GPX0H4YHERXZEFSP', 'consulter_conformite', 'consulter_conformite'),
('1ABPN3VPVVT02JYCNS43JAYNWF', 'executer_remboursement', 'executer_remboursement'),
('1N2MA2F1DAVJ4XZ79ZFM7MYDWB', 'decider_publication', 'decider_publication'),
('1T37WGA9M28NR7BE3A99XJ74GD', 'decider_kyc_commercial', 'decider_kyc_commercial'),
('1YVPN5QV4N50Q5S1740JQV5299', 'verifier_representation', 'verifier_representation'),
('28SWRQMBWDEF3JMN20NWNKVPHN', 'cloturer_compte', 'cloturer_compte'),
('29VPG1G35BD1QP2T2AQTZW6CT6', 'reprendre_traitements_catalogue', 'reprendre_traitements_catalogue'),
('2A1JJE70KWNH1BJNK42BD5JZDJ', 'gerer_retraits_publication', 'gerer_retraits_publication'),
('2E43MGTXT6JZ79AH99K023EW0G', 'attribuer_droit', 'attribuer_droit'),
('2JF6HG292DXE2SF46HTETEH3DS', 'transferer_principal', 'transferer_principal'),
('2KPE8849QHNGT262B7H31FD2CQ', 'reprendre_transmissions', 'reprendre_transmissions'),
('2MX8YEX51JCCEEYS8VNJ9VB9BP', 'instruire_contestation_paiement', 'instruire_contestation_paiement'),
('2X40NMX5ZBM55RBEVMNWQB7MGX', 'revoquer_origine_droit', 'revoquer_origine_droit'),
('3AVP09MDJ1C31VF9ADR7TJN19K', 'controler_kyc', 'controler_kyc'),
('3GGNY7GK5HSKE1JEC2E78GK110', 'decider_retrait', 'decider_retrait'),
('3NAMPV6W1JP5PWFK8MKYZ3R3EV', 'gerer_vitrine', 'gerer_vitrine'),
('3NWZBN49X8JTZKN38XW7TB7FTM', 'decider_kyc', 'decider_kyc'),
('3Y8RM9S956DX4EXWK16PRS22V7', 'demander_examen_compte', 'demander_examen_compte'),
('45GH1K0X3HDWSF5WTM1KSKKJ3B', 'gerer_gels_conservation', 'gerer_gels_conservation'),
('48FDET8XTEQT470CB9YNTRMXYP', 'traiter_support', 'traiter_support'),
('49K07NY7SACJGBEF48CC049D7F', 'affecter_taches', 'affecter_taches'),
('4ED674X4K6XCNY12MQSPCVMRSE', 'revoquer_appareil_tiers', 'revoquer_appareil_tiers'),
('4KWGC8X7AA19MZAS2THENEHG8Q', 'autoriser_recours', 'autoriser_recours'),
('4TCD88A2Z88D3X1TMH7NYER5Q9', 'coordonner_compensation', 'coordonner_compensation'),
('56JJDCB9Z4WQVADKFQE3YMDTMG', 'decider_remboursement', 'decider_remboursement'),
('5BCR2TV8AZT95ETS7JFRED2FJF', 'suspendre_compte', 'suspendre_compte'),
('5FJERK0TGDZAHM56SKN2MK5WK9', 'restreindre_habilitation', 'restreindre_habilitation'),
('5YP0ABFXZK2CHW86Z659WE1G6A', 'decider_mesure_conservatoire', 'decider_mesure_conservatoire'),
('699JP2DTWVGZ4QJJDRC744M4EQ', 'gerer_habilitations_internes', 'gerer_habilitations_internes'),
('6EQ3BPACWT8R4V3NE4VRTETS43', 'approuver_extension_contenu', 'approuver_extension_contenu'),
('6S9B6032B61YS3KD03PQKNFQAV', 'gerer_politiques_conservation', 'gerer_politiques_conservation'),
('6T93H7RK98C9X0BM2M9N8W5P8M', 'instruire_signalement', 'instruire_signalement'),
('71NRTY8FY5VC3HWGSKVD6BE0J8', 'moderer_avis', 'moderer_avis'),
('75GTQTQY7S638JK0VWCNH2XE1D', 'traiter_alertes_lecture', 'traiter_alertes_lecture'),
('78TJ7BZDE9K5ZMHDXYHC5HRVTF', 'consulter_publications', 'consulter_publications'),
('7BV3S92NKBRNNA3C4DGPS3PNCT', 'gerer_blocages_lecture', 'gerer_blocages_lecture'),
('7PRT4WCTSPVZ89918ME53WKHHZ', 'reactiver_compte', 'reactiver_compte'),
('7WX4C5B28ZQKKW499DXS03QKHX', 'controler_publication', 'controler_publication');

-- --------------------------------------------------------

--
-- Table structure for table `permissions_organisation`
--

DROP TABLE IF EXISTS `permissions_organisation`;
CREATE TABLE IF NOT EXISTS `permissions_organisation` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `libelle` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permissions_organisation_5694d08a2e` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `permissions_organisation`
--

INSERT INTO `permissions_organisation` (`id`, `code`, `libelle`) VALUES
('12RWZ2QZ60VY07YDGZ9BY0VCR3', 'deposer_kyc', 'deposer_kyc'),
('18XNF9W85KERKHYHVT3PJR56B0', 'deposer_publication', 'deposer_publication'),
('41YWF32E8G19A28XHH1EHQK739', 'soumettre_publication', 'soumettre_publication'),
('655MC10P356HXGTJGDWMN7A8EY', 'retirer_publication', 'retirer_publication'),
('6BYXR1VBYPNJGD9GJ0WBZVPMK5', 'repondre_conformite', 'repondre_conformite'),
('6DMJ15N11XMFCQ1CN94Z983Y63', 'consulter_publication', 'consulter_publication');

-- --------------------------------------------------------

--
-- Table structure for table `phases_instruction`
--

DROP TABLE IF EXISTS `phases_instruction`;
CREATE TABLE IF NOT EXISTS `phases_instruction` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero` bigint NOT NULL,
  `precedente_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `nature` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision_contestee_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ouverte_a` datetime(6) NOT NULL,
  `close_a` datetime(6) DEFAULT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_phases_instruction_14aafe1a5a` (`signalement_id`,`numero`),
  UNIQUE KEY `uq_phases_instruction_b052c60551` (`id`,`signalement_id`),
  UNIQUE KEY `uq_phases_instruction_9b825829b3` (`precedente_id`),
  KEY `ix_phases_instruction_dd34ae4c61` (`signalement_id`,`statut`,`ouverte_a`),
  KEY `ix_phases_instruction_e37b7a80bb` (`precedente_id`,`signalement_id`),
  KEY `ix_phases_instruction_efb1d851c5` (`decision_contestee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pieces_conformite`
--

DROP TABLE IF EXISTS `pieces_conformite`;
CREATE TABLE IF NOT EXISTS `pieces_conformite` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_privee` varchar(1024) COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `categorie` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `format` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `depose_a` datetime(6) NOT NULL,
  `provenance` json NOT NULL,
  `statut_controle` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pieces_droits_publication`
--

DROP TABLE IF EXISTS `pieces_droits_publication`;
CREATE TABLE IF NOT EXISTS `pieces_droits_publication` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type_document` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_privee` varchar(1024) COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `depose_a` datetime(6) NOT NULL,
  `depose_par` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_pieces_droits_publication_0259b9fde9` (`dossier_id`),
  KEY `ix_pieces_droits_publication_9c32561e4b` (`depose_par`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pieces_rattachements`
--

DROP TABLE IF EXISTS `pieces_rattachements`;
CREATE TABLE IF NOT EXISTS `pieces_rattachements` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `piece_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `complement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `reponse_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `date` datetime(6) NOT NULL,
  `justification` text COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pieces_rattachements_bf53c3ddb5` (`piece_id`,`signalement_id`),
  UNIQUE KEY `uq_pieces_rattachements_d9265a8e4a` (`piece_id`,`complement_id`),
  UNIQUE KEY `uq_pieces_rattachements_e4083d5a88` (`piece_id`,`reponse_id`),
  KEY `ix_pieces_rattachements_855a2083e7` (`signalement_id`),
  KEY `ix_pieces_rattachements_a650099499` (`complement_id`),
  KEY `ix_pieces_rattachements_8b095d2f13` (`reponse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `pieces_support`
--

DROP TABLE IF EXISTS `pieces_support`;
CREATE TABLE IF NOT EXISTS `pieces_support` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `demande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `echange_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `deposant_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `type` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_privee` varchar(1024) COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `depose_a` datetime(6) NOT NULL,
  `acces` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_pieces_support_4f1bf3be90` (`echange_id`,`demande_id`),
  KEY `ix_pieces_support_bed904885e` (`demande_id`),
  KEY `ix_pieces_support_fc6ac65f65` (`deposant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `plaignants`
--

DROP TABLE IF EXISTS `plaignants`;
CREATE TABLE IF NOT EXISTS `plaignants` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_systeme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `nom` varchar(512) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `contact` json DEFAULT NULL,
  `qualite_invoquee` text COLLATE utf8mb4_0900_bin,
  PRIMARY KEY (`id`),
  KEY `ix_plaignants_940f1f0c40` (`compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `politiques_conservation`
--

DROP TABLE IF EXISTS `politiques_conservation`;
CREATE TABLE IF NOT EXISTS `politiques_conservation` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `categorie` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version` bigint NOT NULL,
  `finalite` text COLLATE utf8mb4_0900_bin NOT NULL,
  `base_reference` text COLLATE utf8mb4_0900_bin NOT NULL,
  `declencheur` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `duree_active` decimal(20,8) NOT NULL,
  `duree_archive` decimal(20,8) NOT NULL,
  `unite` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `regle_action` json NOT NULL,
  `date_effet` datetime(6) NOT NULL,
  `valide_a` datetime(6) DEFAULT NULL,
  `valide_par` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_politiques_conservation_be4bf3b7d7` (`categorie`,`version`),
  KEY `ix_politiques_conservation_5cfe14a9a7` (`valide_par`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `politiques_conservation`
--
DROP TRIGGER IF EXISTS `bu_politiques_conservation_7a2e8d35`;
DELIMITER $$
CREATE TRIGGER `bu_politiques_conservation_7a2e8d35` BEFORE UPDATE ON `politiques_conservation` FOR EACH ROW BEGIN
  IF OLD.`valide_a` IS NOT NULL AND (NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`categorie` <=> OLD.`categorie`) OR NOT (NEW.`version` <=> OLD.`version`) OR NOT (NEW.`finalite` <=> OLD.`finalite`) OR NOT (NEW.`base_reference` <=> OLD.`base_reference`) OR NOT (NEW.`declencheur` <=> OLD.`declencheur`) OR NOT (NEW.`duree_active` <=> OLD.`duree_active`) OR NOT (NEW.`duree_archive` <=> OLD.`duree_archive`) OR NOT (NEW.`unite` <=> OLD.`unite`) OR NOT (NEW.`regle_action` <=> OLD.`regle_action`) OR NOT (NEW.`date_effet` <=> OLD.`date_effet`) OR NOT (NEW.`valide_a` <=> OLD.`valide_a`) OR NOT (NEW.`valide_par` <=> OLD.`valide_par`)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Version approuvee ou soumise immuable';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `portees_decisions_mesures`
--

DROP TABLE IF EXISTS `portees_decisions_mesures`;
CREATE TABLE IF NOT EXISTS `portees_decisions_mesures` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `mesure_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `resolution_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `effet` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_portees_decisions_mesures_3d7b176fd7` (`decision_id`),
  KEY `ix_portees_decisions_mesures_04cdbb6725` (`mesure_id`),
  KEY `ix_portees_decisions_mesures_4569ddcc79` (`resolution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `portees_decisions_mesures`
--
DROP TRIGGER IF EXISTS `bu_portees_decisions_mesures_c9bc9d3b`;
DELIMITER $$
CREATE TRIGGER `bu_portees_decisions_mesures_c9bc9d3b` BEFORE UPDATE ON `portees_decisions_mesures` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`decision_id` <=> OLD.`decision_id`) OR NOT (NEW.`mesure_id` <=> OLD.`mesure_id`) OR NOT (NEW.`resolution_id` <=> OLD.`resolution_id`) OR NOT (NEW.`effet` <=> OLD.`effet`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `preparations_filigrane`
--

DROP TABLE IF EXISTS `preparations_filigrane`;
CREATE TABLE IF NOT EXISTS `preparations_filigrane` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `identite_filigrane_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_contenu_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `methode` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `algorithme_version` text COLLATE utf8mb4_0900_bin NOT NULL,
  `reference_derive_privee` varchar(1024) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `empreinte` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `prepare_a` datetime(6) DEFAULT NULL,
  `derniere_erreur` text COLLATE utf8mb4_0900_bin,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_preparations_filigrane_db7eef43ec` (`identite_filigrane_id`,`version_contenu_id`),
  KEY `ix_preparations_filigrane_158d483baf` (`version_contenu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `preparations_papier`
--

DROP TABLE IF EXISTS `preparations_papier`;
CREATE TABLE IF NOT EXISTS `preparations_papier` (
  `offre_cible_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `unite_stock_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `configuration_reference` text COLLATE utf8mb4_0900_bin,
  `configuration_empreinte` text COLLATE utf8mb4_0900_bin,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `verifie_a` datetime(6) DEFAULT NULL,
  `resultat` json DEFAULT NULL,
  PRIMARY KEY (`offre_cible_id`),
  KEY `ix_preparations_papier_d632d23af0` (`unite_stock_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `prestataires_paiement`
--

DROP TABLE IF EXISTS `prestataires_paiement`;
CREATE TABLE IF NOT EXISTS `prestataires_paiement` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `nom` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `actif` tinyint UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_prestataires_paiement_5694d08a2e` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `preuves_completude`
--

DROP TABLE IF EXISTS `preuves_completude`;
CREATE TABLE IF NOT EXISTS `preuves_completude` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `controle_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `contenu_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `droit_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `piece_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `complement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `empreinte` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_preuves_completude_0ed016e731` (`controle_id`),
  KEY `ix_preuves_completude_1d6047096b` (`contenu_id`),
  KEY `ix_preuves_completude_0895f4915e` (`droit_id`),
  KEY `ix_preuves_completude_22f58a002c` (`piece_id`),
  KEY `ix_preuves_completude_a650099499` (`complement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `preuves_decisions_mesures`
--

DROP TABLE IF EXISTS `preuves_decisions_mesures`;
CREATE TABLE IF NOT EXISTS `preuves_decisions_mesures` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `mesure_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `piece_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `controle_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `reponse_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `empreinte` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `role_preuve` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin,
  PRIMARY KEY (`id`),
  KEY `ix_preuves_decisions_mesures_3d7b176fd7` (`decision_id`),
  KEY `ix_preuves_decisions_mesures_04cdbb6725` (`mesure_id`),
  KEY `ix_preuves_decisions_mesures_22f58a002c` (`piece_id`),
  KEY `ix_preuves_decisions_mesures_0ed016e731` (`controle_id`),
  KEY `ix_preuves_decisions_mesures_8b095d2f13` (`reponse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `preuves_decisions_mesures`
--
DROP TRIGGER IF EXISTS `bu_preuves_decisions_mesures_441e8ab0`;
DELIMITER $$
CREATE TRIGGER `bu_preuves_decisions_mesures_441e8ab0` BEFORE UPDATE ON `preuves_decisions_mesures` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`decision_id` <=> OLD.`decision_id`) OR NOT (NEW.`mesure_id` <=> OLD.`mesure_id`) OR NOT (NEW.`piece_id` <=> OLD.`piece_id`) OR NOT (NEW.`controle_id` <=> OLD.`controle_id`) OR NOT (NEW.`reponse_id` <=> OLD.`reponse_id`) OR NOT (NEW.`empreinte` <=> OLD.`empreinte`) OR NOT (NEW.`role_preuve` <=> OLD.`role_preuve`) OR NOT (NEW.`motif` <=> OLD.`motif`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `productions_contenu`
--

DROP TABLE IF EXISTS `productions_contenu`;
CREATE TABLE IF NOT EXISTS `productions_contenu` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `execution_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `edition_cible_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `format_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_production` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `methode` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_methode` text COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_configuration` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_resultat_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `resultat` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_productions_contenu_3793efe75b` (`reference_production`),
  UNIQUE KEY `uq_productions_contenu_529fd87c30` (`execution_id`,`edition_cible_id`,`format_id`),
  KEY `ix_productions_contenu_44cc54aa64` (`edition_cible_id`),
  KEY `ix_productions_contenu_5532d2348c` (`format_id`),
  KEY `ix_productions_contenu_adaea13ae4` (`version_resultat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `productions_sources`
--

DROP TABLE IF EXISTS `productions_sources`;
CREATE TABLE IF NOT EXISTS `productions_sources` (
  `production_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `selection_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`production_id`,`selection_id`),
  KEY `ix_productions_sources_3a61b1695a` (`selection_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `profils_fiscaux`
--

DROP TABLE IF EXISTS `profils_fiscaux`;
CREATE TABLE IF NOT EXISTS `profils_fiscaux` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sujet_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version` bigint NOT NULL,
  `identifiant_fiscal` text COLLATE utf8mb4_0900_bin,
  `pays_fiscal` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `nom_legal` text COLLATE utf8mb4_0900_bin NOT NULL,
  `statut_controle` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_privee` varchar(1024) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_profils_fiscaux_aefc0facfe` (`sujet_id`,`version`),
  UNIQUE KEY `uq_profils_fiscaux_4838ff0457` (`id`,`sujet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `profils_particuliers`
--

DROP TABLE IF EXISTS `profils_particuliers`;
CREATE TABLE IF NOT EXISTS `profils_particuliers` (
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `prenom` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `nom` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `nom_plume` varchar(512) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `pays` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ville` varchar(512) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `langue_ecriture` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `progressions_lecture`
--

DROP TABLE IF EXISTS `progressions_lecture`;
CREATE TABLE IF NOT EXISTS `progressions_lecture` (
  `licence_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_contenu_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `position_ref` json NOT NULL,
  `pct` decimal(20,8) DEFAULT NULL,
  `revision` bigint NOT NULL,
  `maj_a` datetime(6) NOT NULL,
  `dernier_appareil_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`licence_id`,`version_contenu_id`),
  KEY `ix_progressions_lecture_158d483baf` (`version_contenu_id`),
  KEY `ix_progressions_lecture_efa8f32c0a` (`dernier_appareil_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `rattachements_resultats_compensation`
--

DROP TABLE IF EXISTS `rattachements_resultats_compensation`;
CREATE TABLE IF NOT EXISTS `rattachements_resultats_compensation` (
  `resultat_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `demande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`resultat_id`,`demande_id`),
  KEY `ix_rattachements_resultats_compensatio_bed904885e` (`demande_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `receptions_compensation`
--

DROP TABLE IF EXISTS `receptions_compensation`;
CREATE TABLE IF NOT EXISTS `receptions_compensation` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `producteur` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_demande` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `demande_conformite_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `empreinte_intention` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `dossier_reutilisation_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin,
  `cree_a` datetime(6) NOT NULL,
  `repondu_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_receptions_compensation_ff9d8f0b15` (`producteur`,`reference_demande`),
  UNIQUE KEY `uq_receptions_compensation_534a0d6ba1` (`demande_conformite_id`),
  KEY `ix_receptions_compensation_1c8b37225a` (`dossier_id`,`commande_id`),
  KEY `ix_receptions_compensation_3202030bfd` (`dossier_reutilisation_id`,`commande_id`),
  KEY `ix_receptions_compensation_94c4e6db2b` (`commande_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `receptions_extension_contenu`
--

DROP TABLE IF EXISTS `receptions_extension_contenu`;
CREATE TABLE IF NOT EXISTS `receptions_extension_contenu` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision_extension_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_acquisition_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_contenu_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `empreinte_intention` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin,
  `recu_a` datetime(6) NOT NULL,
  `repondu_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_receptions_extension_contenu_e03f51e08b` (`decision_extension_id`,`reference_acquisition_id`),
  KEY `ix_receptions_extension_contenu_967027099e` (`reference_acquisition_id`),
  KEY `ix_receptions_extension_contenu_158d483baf` (`version_contenu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `receptions_messages`
--

DROP TABLE IF EXISTS `receptions_messages`;
CREATE TABLE IF NOT EXISTS `receptions_messages` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `evenement_externe_id` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `producteur` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `consommateur` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `correlation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `recu_a` datetime(6) NOT NULL,
  `traite_a` datetime(6) DEFAULT NULL,
  `empreinte_intention` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `schema_message_version` bigint NOT NULL,
  `donnees_minimales` json NOT NULL,
  `niveau_acquittement` varchar(32) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `reference_resultat` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_receptions_messages_7d59c5152d` (`producteur`,`evenement_externe_id`,`consommateur`),
  KEY `ix_receptions_messages_ac03cf58f4` (`consommateur`,`traite_a`,`recu_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `recus_paiement`
--

DROP TABLE IF EXISTS `recus_paiement`;
CREATE TABLE IF NOT EXISTS `recus_paiement` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `paiement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `montant` decimal(20,4) NOT NULL,
  `devise` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `emis_a` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_recus_paiement_68d4d872e4` (`paiement_id`),
  UNIQUE KEY `uq_recus_paiement_52367a6622` (`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `recus_paiement`
--
DROP TRIGGER IF EXISTS `bu_recus_paiement_a11186b9`;
DELIMITER $$
CREATE TRIGGER `bu_recus_paiement_a11186b9` BEFORE UPDATE ON `recus_paiement` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`paiement_id` <=> OLD.`paiement_id`) OR NOT (NEW.`reference` <=> OLD.`reference`) OR NOT (NEW.`montant` <=> OLD.`montant`) OR NOT (NEW.`devise` <=> OLD.`devise`) OR NOT (NEW.`emis_a` <=> OLD.`emis_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `references_acquisition`
--

DROP TABLE IF EXISTS `references_acquisition`;
CREATE TABLE IF NOT EXISTS `references_acquisition` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `producteur` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_demande` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `cle_idempotence` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `empreinte_intention` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `cree_a` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_references_acquisition_ff9d8f0b15` (`producteur`,`reference_demande`),
  UNIQUE KEY `uq_references_acquisition_965fa85f85` (`producteur`,`cle_idempotence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `references_acquisition`
--
DROP TRIGGER IF EXISTS `bu_references_acquisition_a6ec3da6`;
DELIMITER $$
CREATE TRIGGER `bu_references_acquisition_a6ec3da6` BEFORE UPDATE ON `references_acquisition` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`producteur` <=> OLD.`producteur`) OR NOT (NEW.`reference_demande` <=> OLD.`reference_demande`) OR NOT (NEW.`cree_a` <=> OLD.`cree_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF OLD.`cle_idempotence` IS NOT NULL AND NOT (NEW.`cle_idempotence` <=> OLD.`cle_idempotence`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Reference acquisition deja renseignee';
  END IF;
  IF OLD.`empreinte_intention` IS NOT NULL AND NOT (NEW.`empreinte_intention` <=> OLD.`empreinte_intention`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Reference acquisition deja renseignee';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `regles_controle_publication`
--

DROP TABLE IF EXISTS `regles_controle_publication`;
CREATE TABLE IF NOT EXISTS `regles_controle_publication` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version` bigint NOT NULL,
  `obligatoire` tinyint UNSIGNED NOT NULL,
  `autorise_non_applicable` tinyint UNSIGNED NOT NULL,
  `applicabilite` json NOT NULL,
  `definition` json NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_regles_controle_publication_f5b433d863` (`code`,`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `regles_remuneration`
--

DROP TABLE IF EXISTS `regles_remuneration`;
CREATE TABLE IF NOT EXISTS `regles_remuneration` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `version` bigint NOT NULL,
  `assiette` json NOT NULL,
  `formule` json NOT NULL,
  `devise` varchar(32) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `effet_debut` datetime(6) NOT NULL,
  `effet_fin` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_regles_remuneration_f5b433d863` (`code`,`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `remboursements`
--

DROP TABLE IF EXISTS `remboursements`;
CREATE TABLE IF NOT EXISTS `remboursements` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `paiement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `anomalie_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `cle_idempotence` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `ref_externe` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `montant` decimal(20,4) NOT NULL,
  `devise` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `demande_a` datetime(6) NOT NULL,
  `confirme_a` datetime(6) DEFAULT NULL,
  `configuration_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_remboursements_b44590de90` (`paiement_id`,`cle_idempotence`),
  UNIQUE KEY `uq_remboursements_cc1db12470` (`id`,`paiement_id`),
  UNIQUE KEY `uq_remboursements_3093a88ffc` (`configuration_id`,`ref_externe`),
  KEY `ix_remboursements_072b055964` (`paiement_id`,`statut`),
  KEY `ix_remboursements_92b7644a97` (`decision_id`,`statut`),
  KEY `ix_remboursements_ca2d2a0b7f` (`paiement_id`,`devise`),
  KEY `ix_remboursements_76233a4fa1` (`decision_id`,`devise`),
  KEY `ix_remboursements_4e32fc5b03` (`paiement_id`,`configuration_id`),
  KEY `ix_remboursements_28885fb289` (`anomalie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `remboursements`
--
DROP TRIGGER IF EXISTS `bu_remboursements_7f44c5be`;
DELIMITER $$
CREATE TRIGGER `bu_remboursements_7f44c5be` BEFORE UPDATE ON `remboursements` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`paiement_id` <=> OLD.`paiement_id`) OR NOT (NEW.`decision_id` <=> OLD.`decision_id`) OR NOT (NEW.`anomalie_id` <=> OLD.`anomalie_id`) OR NOT (NEW.`cle_idempotence` <=> OLD.`cle_idempotence`) OR NOT (NEW.`montant` <=> OLD.`montant`) OR NOT (NEW.`devise` <=> OLD.`devise`) OR NOT (NEW.`demande_a` <=> OLD.`demande_a`) OR NOT (NEW.`configuration_id` <=> OLD.`configuration_id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `reponses_fournisseur`
--

DROP TABLE IF EXISTS `reponses_fournisseur`;
CREATE TABLE IF NOT EXISTS `reponses_fournisseur` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `partie_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `notification_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `acces_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `auteur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `reference_source` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `type` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `corps` json NOT NULL,
  `recue_a` datetime(6) NOT NULL,
  `preuve_qualite` json NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reponses_fournisseur_4025551488` (`partie_id`,`reference_source`),
  KEY `ix_reponses_fournisseur_a2d7b75921` (`notification_id`,`partie_id`),
  KEY `ix_reponses_fournisseur_ecc4769e32` (`acces_id`,`partie_id`),
  KEY `ix_reponses_fournisseur_1c7f79acb8` (`auteur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `reservations_stock`
--

DROP TABLE IF EXISTS `reservations_stock`;
CREATE TABLE IF NOT EXISTS `reservations_stock` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `unite_stock_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `quantite` bigint NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reserve_a` datetime(6) NOT NULL,
  `expire_a` datetime(6) NOT NULL,
  `termine_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reservations_stock_8f8ae1d06b` (`ligne_commande_id`,`unite_stock_id`),
  KEY `ix_reservations_stock_abea4b1789` (`unite_stock_id`,`statut`,`expire_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `resolutions_cibles`
--

DROP TABLE IF EXISTS `resolutions_cibles`;
CREATE TABLE IF NOT EXISTS `resolutions_cibles` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `contenu_vise_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero` bigint NOT NULL,
  `resultat` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `oeuvre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `edition_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `offre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `media_catalogue_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `avis_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `version_contenu_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `preuve_resolution` json NOT NULL,
  `resolu_a` datetime(6) NOT NULL,
  `auteur_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `origine_systeme` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_resolutions_cibles_7f6960c224` (`contenu_vise_id`,`numero`),
  KEY `ix_resolutions_cibles_a3fad59948` (`oeuvre_id`),
  KEY `ix_resolutions_cibles_cf279400f6` (`edition_id`),
  KEY `ix_resolutions_cibles_0d7af68203` (`offre_id`),
  KEY `ix_resolutions_cibles_158d483baf` (`version_contenu_id`),
  KEY `ix_resolutions_cibles_daeda78b68` (`avis_id`),
  KEY `ix_resolutions_cibles_51227e1206` (`media_catalogue_id`),
  KEY `ix_resolutions_cibles_1c7f79acb8` (`auteur_compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `resolutions_cibles`
--
DROP TRIGGER IF EXISTS `bu_resolutions_cibles_a9c9d8d3`;
DELIMITER $$
CREATE TRIGGER `bu_resolutions_cibles_a9c9d8d3` BEFORE UPDATE ON `resolutions_cibles` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`contenu_vise_id` <=> OLD.`contenu_vise_id`) OR NOT (NEW.`numero` <=> OLD.`numero`) OR NOT (NEW.`resultat` <=> OLD.`resultat`) OR NOT (NEW.`oeuvre_id` <=> OLD.`oeuvre_id`) OR NOT (NEW.`edition_id` <=> OLD.`edition_id`) OR NOT (NEW.`offre_id` <=> OLD.`offre_id`) OR NOT (NEW.`media_catalogue_id` <=> OLD.`media_catalogue_id`) OR NOT (NEW.`avis_id` <=> OLD.`avis_id`) OR NOT (NEW.`version_contenu_id` <=> OLD.`version_contenu_id`) OR NOT (NEW.`preuve_resolution` <=> OLD.`preuve_resolution`) OR NOT (NEW.`resolu_a` <=> OLD.`resolu_a`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `restitutions_quota_promo`
--

DROP TABLE IF EXISTS `restitutions_quota_promo`;
CREATE TABLE IF NOT EXISTS `restitutions_quota_promo` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `application_promo_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision_remboursement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` datetime(6) NOT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_restitutions_quota_promo_f43c31f657` (`application_promo_id`),
  KEY `ix_restitutions_quota_promo_f1014d9025` (`decision_remboursement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `restrictions_conformite`
--

DROP TABLE IF EXISTS `restrictions_conformite`;
CREATE TABLE IF NOT EXISTS `restrictions_conformite` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_creatrice_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `instruction_publication_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `cause` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `debut` datetime(6) NOT NULL,
  `fin` datetime(6) DEFAULT NULL,
  `levee_decidee_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_restrictions_conformite_6a7ef1ed9f` (`cause`),
  UNIQUE KEY `uq_restrictions_conformite_891eec8886` (`ligne_creatrice_id`),
  UNIQUE KEY `uq_restrictions_conformite_9cb352ee38` (`instruction_publication_id`),
  KEY `ix_restrictions_conformite_34b7c40804` (`debut`,`fin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `restrictions_conformite`
--
DROP TRIGGER IF EXISTS `bu_restrictions_conformite_e0b95779`;
DELIMITER $$
CREATE TRIGGER `bu_restrictions_conformite_e0b95779` BEFORE UPDATE ON `restrictions_conformite` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`ligne_creatrice_id` <=> OLD.`ligne_creatrice_id`) OR NOT (NEW.`instruction_publication_id` <=> OLD.`instruction_publication_id`) OR NOT (NEW.`cause` <=> OLD.`cause`) OR NOT (NEW.`debut` <=> OLD.`debut`) OR NOT (NEW.`fin` <=> OLD.`fin`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `resultats_compensation`
--

DROP TABLE IF EXISTS `resultats_compensation`;
CREATE TABLE IF NOT EXISTS `resultats_compensation` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_remboursement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `producteur` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_fait` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `nature` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resultat` json NOT NULL,
  `recu_a` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_resultats_compensation_343e17312e` (`producteur`,`reference_fait`),
  KEY `ix_resultats_compensation_016767a439` (`dossier_remboursement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `resultats_compensation`
--
DROP TRIGGER IF EXISTS `bu_resultats_compensation_5e04a3fc`;
DELIMITER $$
CREATE TRIGGER `bu_resultats_compensation_5e04a3fc` BEFORE UPDATE ON `resultats_compensation` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`dossier_remboursement_id` <=> OLD.`dossier_remboursement_id`) OR NOT (NEW.`producteur` <=> OLD.`producteur`) OR NOT (NEW.`reference_fait` <=> OLD.`reference_fait`) OR NOT (NEW.`nature` <=> OLD.`nature`) OR NOT (NEW.`resultat` <=> OLD.`resultat`) OR NOT (NEW.`recu_a` <=> OLD.`recu_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `resultats_editions_publication`
--

DROP TABLE IF EXISTS `resultats_editions_publication`;
CREATE TABLE IF NOT EXISTS `resultats_editions_publication` (
  `edition_cible_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `execution_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `edition_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `operation` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `valeurs_preparees` json NOT NULL,
  `empreinte_preparee` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `empreinte_appliquee` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`edition_cible_id`),
  KEY `ix_resultats_editions_publication_6d6de5b8d6` (`execution_id`),
  KEY `ix_resultats_editions_publication_cf279400f6` (`edition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `resultats_oeuvres_publication`
--

DROP TABLE IF EXISTS `resultats_oeuvres_publication`;
CREATE TABLE IF NOT EXISTS `resultats_oeuvres_publication` (
  `execution_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `oeuvre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `operation` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `valeurs_preparees` json NOT NULL,
  `empreinte_preparee` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `empreinte_appliquee` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`execution_id`),
  KEY `ix_resultats_oeuvres_publication_a3fad59948` (`oeuvre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `resultats_offres_publication`
--

DROP TABLE IF EXISTS `resultats_offres_publication`;
CREATE TABLE IF NOT EXISTS `resultats_offres_publication` (
  `offre_cible_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `execution_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `offre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `operation` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `valeurs_preparees` json NOT NULL,
  `empreinte_preparee` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `empreinte_appliquee` char(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`offre_cible_id`),
  KEY `ix_resultats_offres_publication_6d6de5b8d6` (`execution_id`),
  KEY `ix_resultats_offres_publication_0d7af68203` (`offre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE IF NOT EXISTS `roles` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `libelle` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_5694d08a2e` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `code`, `libelle`) VALUES
('01BB34Y6675D00ZA24296R8GX6', 'admin', 'admin'),
('047M6WJZZPTY2A7SRMK7MN3Y6T', 'publisher', 'publisher'),
('40VAW59R8WFEH1JNGKT3CYSJTT', 'author', 'author'),
('48PYG2FM5N5BWH0ZSVQ1F1J9Y9', 'support', 'support'),
('4C9A4QP7VJBPMFAV5SJXQF0JEH', 'reader', 'reader'),
('5EF03F8P4SA9HC2B827D8MCGF1', 'moderator', 'moderator');

-- --------------------------------------------------------

--
-- Table structure for table `secrets_transmission`
--

DROP TABLE IF EXISTS `secrets_transmission`;
CREATE TABLE IF NOT EXISTS `secrets_transmission` (
  `transmission_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `defi_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `acces_reponse_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`transmission_id`),
  KEY `ix_secrets_transmission_e25d4f3ebc` (`defi_id`),
  KEY `ix_secrets_transmission_edb0e641fb` (`acces_reponse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `segments_livre`
--

DROP TABLE IF EXISTS `segments_livre`;
CREATE TABLE IF NOT EXISTS `segments_livre` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `manifeste_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `index_segment` bigint NOT NULL,
  `type` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_privee` varchar(1024) COLLATE utf8mb4_0900_bin NOT NULL,
  `borne_debut` decimal(20,8) DEFAULT NULL,
  `borne_fin` decimal(20,8) DEFAULT NULL,
  `unite_borne` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `empreinte` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_segments_livre_6dff3b31a5` (`manifeste_id`,`index_segment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `selections_fichiers_publication`
--

DROP TABLE IF EXISTS `selections_fichiers_publication`;
CREATE TABLE IF NOT EXISTS `selections_fichiers_publication` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `fichier_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `usage` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `format_cible_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_selections_fichiers_publication_6512fb6ccc` (`dossier_id`,`fichier_id`,`usage`,`format_cible_id`),
  KEY `ix_selections_fichiers_publication_b2379dd0f5` (`fichier_id`),
  KEY `ix_selections_fichiers_publication_ccff9c03f8` (`format_cible_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `selection_editoriale`
--

DROP TABLE IF EXISTS `selection_editoriale`;
CREATE TABLE IF NOT EXISTS `selection_editoriale` (
  `selection_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `libelle` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `regle_ou_manuelle` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `parametres` json DEFAULT NULL,
  `actif` tinyint UNSIGNED NOT NULL,
  `sort_order` bigint NOT NULL,
  PRIMARY KEY (`selection_id`),
  UNIQUE KEY `uq_selection_editoriale_5694d08a2e` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `selection_oeuvre`
--

DROP TABLE IF EXISTS `selection_oeuvre`;
CREATE TABLE IF NOT EXISTS `selection_oeuvre` (
  `selection_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `oeuvre_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sort_order` bigint NOT NULL,
  `ajoute_a` datetime(6) NOT NULL,
  PRIMARY KEY (`selection_id`,`oeuvre_id`),
  KEY `ix_selection_oeuvre_a3fad59948` (`oeuvre_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `sessions_authentification`
--

DROP TABLE IF EXISTS `sessions_authentification`;
CREATE TABLE IF NOT EXISTS `sessions_authentification` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `empreinte_reference` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `portee` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ouverte_a` datetime(6) NOT NULL,
  `derniere_activite_a` datetime(6) NOT NULL,
  `expire_absolue_a` datetime(6) NOT NULL,
  `expire_inactivite_a` datetime(6) NOT NULL,
  `revoque_a` datetime(6) DEFAULT NULL,
  `motif_revocation` text COLLATE utf8mb4_0900_bin,
  `contexte_client` text COLLATE utf8mb4_0900_bin,
  `demande_support_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `autorise_par` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `config_acces_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sessions_authentification_e578af1c9f` (`empreinte_reference`),
  UNIQUE KEY `uq_sessions_authentification_a6e60fd4c7` (`id`,`compte_id`),
  KEY `ix_sessions_authentification_2f97eab048` (`compte_id`,`statut`),
  KEY `ix_sessions_authentification_afd0b96e52` (`expire_absolue_a`),
  KEY `ix_sessions_authentification_df7db26d23` (`demande_support_id`),
  KEY `ix_sessions_authentification_36e5a78461` (`autorise_par`),
  KEY `ix_sessions_authentification_653084ec8b` (`config_acces_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `sessions_lecture`
--

DROP TABLE IF EXISTS `sessions_lecture`;
CREATE TABLE IF NOT EXISTS `sessions_lecture` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `licence_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `appareil_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `session_auth_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_preuve_appareil` bigint NOT NULL,
  `ouverte_a` datetime(6) NOT NULL,
  `derniere_activite_a` datetime(6) NOT NULL,
  `expire_a` datetime(6) NOT NULL,
  `fermee_a` datetime(6) DEFAULT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_sessions_lecture_eef1915057` (`compte_id`,`statut`,`expire_a`),
  KEY `ix_sessions_lecture_4d250f31b2` (`session_auth_id`,`statut`),
  KEY `ix_sessions_lecture_8707a69255` (`licence_id`,`compte_id`),
  KEY `ix_sessions_lecture_6b49ec8856` (`appareil_id`,`compte_id`),
  KEY `ix_sessions_lecture_29ae4ac1c1` (`session_auth_id`,`compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `signalements_contenu`
--

DROP TABLE IF EXISTS `signalements_contenu`;
CREATE TABLE IF NOT EXISTS `signalements_contenu` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `plaignant_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `canal` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `recu_a` datetime(6) NOT NULL,
  `identite_declaree` json NOT NULL,
  `declaration_initiale` json NOT NULL,
  `completude` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `urgence` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cycle` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `clos_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_signalements_contenu_52367a6622` (`reference`),
  KEY `ix_signalements_contenu_129ab42907` (`plaignant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `suivis_instructions_externes`
--

DROP TABLE IF EXISTS `suivis_instructions_externes`;
CREATE TABLE IF NOT EXISTS `suivis_instructions_externes` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `instruction_paiement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `gel_conservation_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `levee_gel_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `cible_instruction_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `objet_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `destinataire` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `action` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `recu_a` datetime(6) DEFAULT NULL,
  `applique_a` datetime(6) DEFAULT NULL,
  `resultat` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_suivis_instructions_externes_52367a6622` (`reference`),
  KEY `ix_suivis_instructions_externes_38558487f1` (`statut`,`destinataire`),
  KEY `ix_suivis_instructions_externes_343ce2e3d7` (`instruction_paiement_id`),
  KEY `ix_suivis_instructions_externes_f9abbd3f0c` (`gel_conservation_id`),
  KEY `ix_suivis_instructions_externes_ff0e989f22` (`levee_gel_id`),
  KEY `ix_suivis_instructions_externes_277a6c4d94` (`cible_instruction_id`),
  KEY `ix_suivis_instructions_externes_afd38cd64f` (`objet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `sujets_kyc`
--

DROP TABLE IF EXISTS `sujets_kyc`;
CREATE TABLE IF NOT EXISTS `sujets_kyc` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `profil_compte_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `organisation_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sujets_kyc_442b0c944e` (`profil_compte_id`),
  UNIQUE KEY `uq_sujets_kyc_1b213432c4` (`organisation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `suppleances_service`
--

DROP TABLE IF EXISTS `suppleances_service`;
CREATE TABLE IF NOT EXISTS `suppleances_service` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `titulaire_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `suppleant_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `fonction` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `debut_a` datetime(6) NOT NULL,
  `fin_a` datetime(6) NOT NULL,
  `defini_par` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `defini_a` datetime(6) NOT NULL,
  `annule_a` datetime(6) DEFAULT NULL,
  `motif` text COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_suppleances_service_fd75bf3119` (`titulaire_id`,`fonction`,`debut_a`,`fin_a`),
  KEY `ix_suppleances_service_f04acd55c5` (`suppleant_id`),
  KEY `ix_suppleances_service_dd44b2a55b` (`defini_par`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `taches_service`
--

DROP TABLE IF EXISTS `taches_service`;
CREATE TABLE IF NOT EXISTS `taches_service` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_kyc_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `demande_support_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `responsable_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `etat_travail` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `priorite` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif_priorite` text COLLATE utf8mb4_0900_bin,
  `cree_a` datetime(6) NOT NULL,
  `affecte_a` datetime(6) DEFAULT NULL,
  `premiere_prise_a` datetime(6) DEFAULT NULL,
  `premiere_reponse_a` datetime(6) DEFAULT NULL,
  `echeance_prise_a` datetime(6) NOT NULL,
  `echeance_reponse_a` datetime(6) NOT NULL,
  `prochaine_action_a` datetime(6) DEFAULT NULL,
  `motif_attente` text COLLATE utf8mb4_0900_bin,
  `clos_a` datetime(6) DEFAULT NULL,
  `calendrier_config_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `delais_config_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_taches_service_d5aba0d290` (`dossier_kyc_id`),
  UNIQUE KEY `uq_taches_service_df7db26d23` (`demande_support_id`),
  KEY `ix_taches_service_202849c30d` (`responsable_id`,`etat_travail`,`echeance_reponse_a`),
  KEY `ix_taches_service_57bd0c5a4b` (`etat_travail`,`prochaine_action_a`),
  KEY `ix_taches_service_f96193e322` (`calendrier_config_id`),
  KEY `ix_taches_service_f5cc9dce14` (`delais_config_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `tag_auteur`
--

DROP TABLE IF EXISTS `tag_auteur`;
CREATE TABLE IF NOT EXISTS `tag_auteur` (
  `tag_auteur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `slug` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `nom` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`tag_auteur_id`),
  UNIQUE KEY `uq_tag_auteur_cd03861f0f` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `tentatives_publication`
--

DROP TABLE IF EXISTS `tentatives_publication`;
CREATE TABLE IF NOT EXISTS `tentatives_publication` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `execution_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero` bigint NOT NULL,
  `etape` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `debut` datetime(6) NOT NULL,
  `fin` datetime(6) DEFAULT NULL,
  `resultat` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `erreur_code` varchar(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `detail` text COLLATE utf8mb4_0900_bin,
  `correlation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tentatives_publication_9cfae1852e` (`execution_id`,`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `tentatives_transmission`
--

DROP TABLE IF EXISTS `tentatives_transmission`;
CREATE TABLE IF NOT EXISTS `tentatives_transmission` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `transmission_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero` bigint NOT NULL,
  `commence_a` datetime(6) NOT NULL,
  `termine_a` datetime(6) DEFAULT NULL,
  `resultat` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_prestataire` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `code_erreur_masque` text COLLATE utf8mb4_0900_bin,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tentatives_transmission_726cb0d706` (`transmission_id`,`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `territoires_conformite`
--

DROP TABLE IF EXISTS `territoires_conformite`;
CREATE TABLE IF NOT EXISTS `territoires_conformite` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `libelle` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `definition` text COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_territoires_conformite_5694d08a2e` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `territoires_publication`
--

DROP TABLE IF EXISTS `territoires_publication`;
CREATE TABLE IF NOT EXISTS `territoires_publication` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `libelle` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  `reference_definition` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `actif` tinyint UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_territoires_publication_5694d08a2e` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `textes_legaux_versions`
--

DROP TABLE IF EXISTS `textes_legaux_versions`;
CREATE TABLE IF NOT EXISTS `textes_legaux_versions` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `langue` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `contenu` text COLLATE utf8mb4_0900_bin,
  `reference_immuable` varchar(1024) COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `empreinte` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `publie_a` datetime(6) NOT NULL,
  `requis_pour_activation` tinyint UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_textes_legaux_versions_23f1bea69d` (`type`,`version`,`langue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `transactions_paiement`
--

DROP TABLE IF EXISTS `transactions_paiement`;
CREATE TABLE IF NOT EXISTS `transactions_paiement` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `paiement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `remboursement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `evenement_psp_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `nature` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_mouvement` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `cle_mouvement` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `montant` decimal(20,4) NOT NULL,
  `devise` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resultat` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `horodatage` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_transactions_paiement_867f624ec2` (`paiement_id`,`cle_mouvement`),
  KEY `ix_transactions_paiement_ca2d2a0b7f` (`paiement_id`,`devise`),
  KEY `ix_transactions_paiement_dbfc368150` (`remboursement_id`,`paiement_id`),
  KEY `ix_transactions_paiement_ddb2f596e8` (`evenement_psp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `transactions_paiement`
--
DROP TRIGGER IF EXISTS `bu_transactions_paiement_0b7bbb91`;
DELIMITER $$
CREATE TRIGGER `bu_transactions_paiement_0b7bbb91` BEFORE UPDATE ON `transactions_paiement` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`paiement_id` <=> OLD.`paiement_id`) OR NOT (NEW.`remboursement_id` <=> OLD.`remboursement_id`) OR NOT (NEW.`evenement_psp_id` <=> OLD.`evenement_psp_id`) OR NOT (NEW.`nature` <=> OLD.`nature`) OR NOT (NEW.`reference_mouvement` <=> OLD.`reference_mouvement`) OR NOT (NEW.`cle_mouvement` <=> OLD.`cle_mouvement`) OR NOT (NEW.`montant` <=> OLD.`montant`) OR NOT (NEW.`devise` <=> OLD.`devise`) OR NOT (NEW.`resultat` <=> OLD.`resultat`) OR NOT (NEW.`horodatage` <=> OLD.`horodatage`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `transmissions_messages`
--

DROP TABLE IF EXISTS `transmissions_messages`;
CREATE TABLE IF NOT EXISTS `transmissions_messages` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `message_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `destinataire_cle` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `canal` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_destinataire_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `destination_chiffree` mediumblob,
  `destination_masquee` text COLLATE utf8mb4_0900_bin,
  `etat` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `config_delais_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `prochaine_tentative_a` datetime(6) DEFAULT NULL,
  `nombre_tentatives` bigint NOT NULL,
  `bail_jusqua` datetime(6) DEFAULT NULL,
  `pris_par` text COLLATE utf8mb4_0900_bin,
  `envoye_a` datetime(6) DEFAULT NULL,
  `acquitte_a` datetime(6) DEFAULT NULL,
  `niveau_acquittement` varchar(32) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `reference_resultat` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `incident_demande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `contenu_chiffre_temporaire` mediumblob,
  `cle_chiffrement_ref` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin DEFAULT NULL,
  `contenu_expire_a` datetime(6) DEFAULT NULL,
  `contenu_purge_a` datetime(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_transmissions_messages_d1aa4d343b` (`message_id`,`destinataire_cle`,`canal`),
  KEY `ix_transmissions_messages_76bbe6d065` (`etat`,`prochaine_tentative_a`),
  KEY `ix_transmissions_messages_f123c00495` (`bail_jusqua`),
  KEY `ix_transmissions_messages_ce5f933f10` (`compte_destinataire_id`),
  KEY `ix_transmissions_messages_81476d9843` (`config_delais_id`),
  KEY `ix_transmissions_messages_cf5457ce05` (`incident_demande_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `types_organisation`
--

DROP TABLE IF EXISTS `types_organisation`;
CREATE TABLE IF NOT EXISTS `types_organisation` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `libelle` varchar(512) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_types_organisation_5694d08a2e` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `unites_stock`
--

DROP TABLE IF EXISTS `unites_stock`;
CREATE TABLE IF NOT EXISTS `unites_stock` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  `vendeur_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `quantite_physique` bigint NOT NULL,
  `actif` tinyint UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_unites_stock_52367a6622` (`reference`),
  KEY `ix_unites_stock_59710ce746` (`vendeur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `usages_acces_reponse`
--

DROP TABLE IF EXISTS `usages_acces_reponse`;
CREATE TABLE IF NOT EXISTS `usages_acces_reponse` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `acces_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` datetime(6) NOT NULL,
  `action` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resultat` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` varchar(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `correlation` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_usages_acces_reponse_a172c5d9d4` (`acces_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `validations_reglement`
--

DROP TABLE IF EXISTS `validations_reglement`;
CREATE TABLE IF NOT EXISTS `validations_reglement` (
  `commande_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `paiement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `mode` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `montant` decimal(20,4) NOT NULL,
  `devise` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `valide_a` datetime(6) NOT NULL,
  PRIMARY KEY (`commande_id`),
  UNIQUE KEY `uq_validations_reglement_68d4d872e4` (`paiement_id`),
  KEY `ix_validations_reglement_c7fba6138f` (`paiement_id`,`commande_id`,`devise`),
  KEY `ix_validations_reglement_7fb72176f0` (`commande_id`,`devise`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `vendeurs`
--

DROP TABLE IF EXISTS `vendeurs`;
CREATE TABLE IF NOT EXISTS `vendeurs` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sujet_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `profil_fiscal_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `compte_reversement_id` char(26) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `serie_facturation` text COLLATE utf8mb4_0900_bin,
  `actif` tinyint UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_vendeurs_70e5509c24` (`profil_fiscal_id`,`sujet_id`),
  KEY `ix_vendeurs_d492b15cd7` (`compte_reversement_id`,`sujet_id`),
  KEY `ix_vendeurs_38e2c20786` (`sujet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `verrous_coordination`
--

DROP TABLE IF EXISTS `verrous_coordination`;
CREATE TABLE IF NOT EXISTS `verrous_coordination` (
  `code` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Dumping data for table `verrous_coordination`
--

INSERT INTO `verrous_coordination` (`code`) VALUES
('catalogue'),
('conservation'),
('finance'),
('identite');

-- --------------------------------------------------------

--
-- Table structure for table `versions_code_promo`
--

DROP TABLE IF EXISTS `versions_code_promo`;
CREATE TABLE IF NOT EXISTS `versions_code_promo` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code_promo_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero_version` bigint NOT NULL,
  `type` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `valeur` decimal(20,8) NOT NULL,
  `devise` varchar(32) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `valid_from` datetime(6) DEFAULT NULL,
  `valid_to` datetime(6) DEFAULT NULL,
  `min_sous_total` decimal(20,4) DEFAULT NULL,
  `plafond_usage` bigint DEFAULT NULL,
  `plafond_par_compte` bigint DEFAULT NULL,
  `regles_cumul_exclusion` json NOT NULL,
  `regle_restitution_quota` json NOT NULL,
  `regle_arrondi` json NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_versions_code_promo_19d429f37e` (`code_promo_id`,`numero_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- --------------------------------------------------------

--
-- Table structure for table `versions_contenu`
--

DROP TABLE IF EXISTS `versions_contenu`;
CREATE TABLE IF NOT EXISTS `versions_contenu` (
  `id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `media_prive_id` char(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero_version` bigint NOT NULL,
  `reference_objet_version` varchar(1024) COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_fichier` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` varchar(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `chiffrement_reference` text COLLATE utf8mb4_0900_bin,
  `cree_a` datetime(6) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_versions_contenu_9c7aa265aa` (`media_prive_id`,`numero_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

--
-- Triggers `versions_contenu`
--
DROP TRIGGER IF EXISTS `bu_versions_contenu_1c0e79f2`;
DELIMITER $$
CREATE TRIGGER `bu_versions_contenu_1c0e79f2` BEFORE UPDATE ON `versions_contenu` FOR EACH ROW BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`media_prive_id` <=> OLD.`media_prive_id`) OR NOT (NEW.`numero_version` <=> OLD.`numero_version`) OR NOT (NEW.`reference_objet_version` <=> OLD.`reference_objet_version`) OR NOT (NEW.`empreinte_fichier` <=> OLD.`empreinte_fichier`) OR NOT (NEW.`cree_a` <=> OLD.`cree_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure for view `envois_defis`
--
DROP TABLE IF EXISTS `envois_defis`;

DROP VIEW IF EXISTS `envois_defis`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY INVOKER VIEW `envois_defis`  AS SELECT `m`.`defi_id` AS `defi_id`, `t`.`id` AS `transmission_id`, `e`.`numero` AS `numero_essai`, `e`.`resultat` AS `resultat`, `e`.`commence_a` AS `demande_a`, `e`.`termine_a` AS `termine_a`, `e`.`reference_prestataire` AS `reference_envoi`, `e`.`code_erreur_masque` AS `code_erreur` FROM ((`messages_sortants` `m` join `transmissions_messages` `t` on((`t`.`message_id` = `m`.`id`))) join `tentatives_transmission` `e` on((`e`.`transmission_id` = `t`.`id`))) WHERE (`m`.`defi_id` is not null) ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `acces_documents_support`
--
ALTER TABLE `acces_documents_support`
  ADD CONSTRAINT `fk_acces_documents_support_0bc448e690` FOREIGN KEY (`recu_paiement_id`) REFERENCES `recus_paiement` (`id`),
  ADD CONSTRAINT `fk_acces_documents_support_3bcdd87061` FOREIGN KEY (`facture_vente_id`) REFERENCES `factures_vente` (`id`),
  ADD CONSTRAINT `fk_acces_documents_support_75ff383585` FOREIGN KEY (`demande_support_id`) REFERENCES `demandes_support` (`id`),
  ADD CONSTRAINT `fk_acces_documents_support_813176aceb` FOREIGN KEY (`autorise_par`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_acces_documents_support_93f9346ea2` FOREIGN KEY (`avoir_id`) REFERENCES `avoirs` (`id`),
  ADD CONSTRAINT `fk_acces_documents_support_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_acces_documents_support_e1caacfd22` FOREIGN KEY (`facture_commission_id`) REFERENCES `factures_commission` (`id`);

--
-- Constraints for table `acces_dossiers_conformite`
--
ALTER TABLE `acces_dossiers_conformite`
  ADD CONSTRAINT `fk_acces_dossiers_conformite_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_acces_dossiers_conformite_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`),
  ADD CONSTRAINT `fk_acces_dossiers_conformite_fe1d57a629` FOREIGN KEY (`litige_id`) REFERENCES `contestations_paiement` (`id`);

--
-- Constraints for table `acces_dossiers_publication`
--
ALTER TABLE `acces_dossiers_publication`
  ADD CONSTRAINT `fk_acces_dossiers_publication_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`),
  ADD CONSTRAINT `fk_acces_dossiers_publication_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `acces_reponse_conformite`
--
ALTER TABLE `acces_reponse_conformite`
  ADD CONSTRAINT `fk_acces_reponse_conformite_405965a1a8` FOREIGN KEY (`partie_id`) REFERENCES `parties_fournisseur` (`id`);

--
-- Constraints for table `actions_conservation`
--
ALTER TABLE `actions_conservation`
  ADD CONSTRAINT `fk_actions_conservation_57b1ad9851` FOREIGN KEY (`objet_id`) REFERENCES `objets_conserves` (`id`),
  ADD CONSTRAINT `fk_actions_conservation_d74aece195` FOREIGN KEY (`application_id`,`objet_id`) REFERENCES `applications_conservation` (`id`, `objet_id`),
  ADD CONSTRAINT `fk_actions_conservation_e17ebd9784` FOREIGN KEY (`application_id`) REFERENCES `applications_conservation` (`id`);

--
-- Constraints for table `adhesions_organisation`
--
ALTER TABLE `adhesions_organisation`
  ADD CONSTRAINT `fk_adhesions_organisation_d73eda69b5` FOREIGN KEY (`organisation_id`) REFERENCES `organisations` (`id`),
  ADD CONSTRAINT `fk_adhesions_organisation_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `adhesions_permissions`
--
ALTER TABLE `adhesions_permissions`
  ADD CONSTRAINT `fk_adhesions_permissions_ead0d9dd58` FOREIGN KEY (`permission_id`) REFERENCES `permissions_organisation` (`id`),
  ADD CONSTRAINT `fk_adhesions_permissions_f6dd176b08` FOREIGN KEY (`adhesion_id`) REFERENCES `adhesions_organisation` (`id`);

--
-- Constraints for table `adresses`
--
ALTER TABLE `adresses`
  ADD CONSTRAINT `fk_adresses_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `adresses_livraison_commande`
--
ALTER TABLE `adresses_livraison_commande`
  ADD CONSTRAINT `fk_adresses_livraison_commande_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`),
  ADD CONSTRAINT `fk_adresses_livraison_commande_3a0d5f2fcf` FOREIGN KEY (`adresse_origine_id`) REFERENCES `adresses` (`id`);

--
-- Constraints for table `affectations_conformite`
--
ALTER TABLE `affectations_conformite`
  ADD CONSTRAINT `fk_affectations_conformite_00da6ae87e` FOREIGN KEY (`phase_id`) REFERENCES `phases_instruction` (`id`),
  ADD CONSTRAINT `fk_affectations_conformite_a2e76a7a7d` FOREIGN KEY (`responsable_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `affectations_revue`
--
ALTER TABLE `affectations_revue`
  ADD CONSTRAINT `fk_affectations_revue_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`),
  ADD CONSTRAINT `fk_affectations_revue_823e78106f` FOREIGN KEY (`responsable_compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `ajustements_echeance`
--
ALTER TABLE `ajustements_echeance`
  ADD CONSTRAINT `fk_ajustements_echeance_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_ajustements_echeance_d1d4f527ad` FOREIGN KEY (`echeance_id`) REFERENCES `echeances_conformite` (`id`);

--
-- Constraints for table `alertes_journaux`
--
ALTER TABLE `alertes_journaux`
  ADD CONSTRAINT `fk_alertes_journaux_206190a76e` FOREIGN KEY (`journal_id`) REFERENCES `journaux_acces` (`id`),
  ADD CONSTRAINT `fk_alertes_journaux_c76ff85a73` FOREIGN KEY (`alerte_id`) REFERENCES `alertes_securite` (`id`);

--
-- Constraints for table `alertes_securite`
--
ALTER TABLE `alertes_securite`
  ADD CONSTRAINT `fk_alertes_securite_823e78106f` FOREIGN KEY (`responsable_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_alertes_securite_92d0c5e8e8` FOREIGN KEY (`appareil_id`) REFERENCES `appareils` (`id`),
  ADD CONSTRAINT `fk_alertes_securite_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_alertes_securite_dccc261267` FOREIGN KEY (`licence_id`) REFERENCES `licences_contenu` (`id`);

--
-- Constraints for table `allocations_promo_ligne`
--
ALTER TABLE `allocations_promo_ligne`
  ADD CONSTRAINT `fk_allocations_promo_ligne_919d0b6c8c` FOREIGN KEY (`application_promo_id`) REFERENCES `applications_promo` (`id`),
  ADD CONSTRAINT `fk_allocations_promo_ligne_a90de5d467` FOREIGN KEY (`ligne_commande_id`) REFERENCES `lignes_commande` (`id`);

--
-- Constraints for table `allocations_remboursement`
--
ALTER TABLE `allocations_remboursement`
  ADD CONSTRAINT `fk_allocations_remboursement_2a07d83cbc` FOREIGN KEY (`remboursement_id`) REFERENCES `remboursements` (`id`),
  ADD CONSTRAINT `fk_allocations_remboursement_a90de5d467` FOREIGN KEY (`ligne_commande_id`) REFERENCES `lignes_commande` (`id`);

--
-- Constraints for table `anomalies_paiement`
--
ALTER TABLE `anomalies_paiement`
  ADD CONSTRAINT `fk_anomalies_paiement_0a4bf6e587` FOREIGN KEY (`decide_par_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_anomalies_paiement_571ddb756e` FOREIGN KEY (`paiement_id`) REFERENCES `paiements` (`id`),
  ADD CONSTRAINT `fk_anomalies_paiement_a8780dfce1` FOREIGN KEY (`evenement_psp_id`) REFERENCES `evenements_psp` (`id`);

--
-- Constraints for table `appareils`
--
ALTER TABLE `appareils`
  ADD CONSTRAINT `fk_appareils_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `applications_conservation`
--
ALTER TABLE `applications_conservation`
  ADD CONSTRAINT `fk_applications_conservation_1fe59bac09` FOREIGN KEY (`politique_id`) REFERENCES `politiques_conservation` (`id`),
  ADD CONSTRAINT `fk_applications_conservation_57b1ad9851` FOREIGN KEY (`objet_id`) REFERENCES `objets_conserves` (`id`);

--
-- Constraints for table `applications_promo`
--
ALTER TABLE `applications_promo`
  ADD CONSTRAINT `fk_applications_promo_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`),
  ADD CONSTRAINT `fk_applications_promo_80ea762411` FOREIGN KEY (`version_promo_id`) REFERENCES `versions_code_promo` (`id`),
  ADD CONSTRAINT `fk_applications_promo_9a94a15c45` FOREIGN KEY (`commande_id`,`compte_id`) REFERENCES `commandes` (`id`, `compte_id`),
  ADD CONSTRAINT `fk_applications_promo_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `attributions_internes`
--
ALTER TABLE `attributions_internes`
  ADD CONSTRAINT `fk_attributions_internes_67827c7edb` FOREIGN KEY (`attribue_par`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_attributions_internes_9cc75ebbdd` FOREIGN KEY (`permission_id`) REFERENCES `permissions_internes` (`id`),
  ADD CONSTRAINT `fk_attributions_internes_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `auteur`
--
ALTER TABLE `auteur`
  ADD CONSTRAINT `fk_auteur_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `auteur_tag`
--
ALTER TABLE `auteur_tag`
  ADD CONSTRAINT `fk_auteur_tag_e44f799281` FOREIGN KEY (`auteur_id`) REFERENCES `auteur` (`auteur_id`),
  ADD CONSTRAINT `fk_auteur_tag_e9bed2ef9c` FOREIGN KEY (`tag_auteur_id`) REFERENCES `tag_auteur` (`tag_auteur_id`);

--
-- Constraints for table `autorisations_contenu_origine`
--
ALTER TABLE `autorisations_contenu_origine`
  ADD CONSTRAINT `fk_autorisations_contenu_origine_86003bf47d` FOREIGN KEY (`version_contenu_id`) REFERENCES `versions_contenu` (`id`),
  ADD CONSTRAINT `fk_autorisations_contenu_origine_9a0f3a5ebf` FOREIGN KEY (`origine_id`) REFERENCES `origines_acquisition` (`id`);

--
-- Constraints for table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `fk_avis_13b943eabc` FOREIGN KEY (`oeuvre_id`) REFERENCES `oeuvre` (`oeuvre_id`),
  ADD CONSTRAINT `fk_avis_c04f6964f0` FOREIGN KEY (`moderateur_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_avis_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `avoirs`
--
ALTER TABLE `avoirs`
  ADD CONSTRAINT `fk_avoirs_3bcdd87061` FOREIGN KEY (`facture_vente_id`) REFERENCES `factures_vente` (`id`),
  ADD CONSTRAINT `fk_avoirs_85050287e8` FOREIGN KEY (`decision_remboursement_id`) REFERENCES `decisions_remboursement` (`id`),
  ADD CONSTRAINT `fk_avoirs_e1caacfd22` FOREIGN KEY (`facture_commission_id`) REFERENCES `factures_commission` (`id`);

--
-- Constraints for table `besoins_preparation`
--
ALTER TABLE `besoins_preparation`
  ADD CONSTRAINT `fk_besoins_preparation_a90de5d467` FOREIGN KEY (`ligne_commande_id`) REFERENCES `lignes_commande` (`id`);

--
-- Constraints for table `blocages_acces`
--
ALTER TABLE `blocages_acces`
  ADD CONSTRAINT `fk_blocages_acces_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_blocages_acces_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_blocages_acces_dccc261267` FOREIGN KEY (`licence_id`) REFERENCES `licences_contenu` (`id`);

--
-- Constraints for table `capacites_encaissement`
--
ALTER TABLE `capacites_encaissement`
  ADD CONSTRAINT `fk_capacites_encaissement_a53c9de021` FOREIGN KEY (`configuration_id`) REFERENCES `configurations_encaissement` (`id`);

--
-- Constraints for table `categorie`
--
ALTER TABLE `categorie`
  ADD CONSTRAINT `fk_categorie_d44daa8088` FOREIGN KEY (`parent_id`) REFERENCES `categorie` (`categorie_id`);

--
-- Constraints for table `categories_gel`
--
ALTER TABLE `categories_gel`
  ADD CONSTRAINT `fk_categories_gel_6cf547d612` FOREIGN KEY (`gel_id`) REFERENCES `gels_conservation` (`id`);

--
-- Constraints for table `cibles_extension_contenu`
--
ALTER TABLE `cibles_extension_contenu`
  ADD CONSTRAINT `fk_cibles_extension_contenu_80cf80e023` FOREIGN KEY (`reference_acquisition_id`) REFERENCES `references_acquisition` (`id`),
  ADD CONSTRAINT `fk_cibles_extension_contenu_9a0f3a5ebf` FOREIGN KEY (`origine_id`) REFERENCES `origines_acquisition` (`id`),
  ADD CONSTRAINT `fk_cibles_extension_contenu_fbe8cb9000` FOREIGN KEY (`decision_id`) REFERENCES `decisions_extension_contenu` (`id`);

--
-- Constraints for table `cibles_gels_financiers`
--
ALTER TABLE `cibles_gels_financiers`
  ADD CONSTRAINT `fk_cibles_gels_financiers_1657037bdf` FOREIGN KEY (`gel_id`) REFERENCES `gels_operations_financieres` (`id`),
  ADD CONSTRAINT `fk_cibles_gels_financiers_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`),
  ADD CONSTRAINT `fk_cibles_gels_financiers_571ddb756e` FOREIGN KEY (`paiement_id`) REFERENCES `paiements` (`id`);

--
-- Constraints for table `cibles_instructions_paiement`
--
ALTER TABLE `cibles_instructions_paiement`
  ADD CONSTRAINT `fk_cibles_instructions_paiement_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`),
  ADD CONSTRAINT `fk_cibles_instructions_paiement_571ddb756e` FOREIGN KEY (`paiement_id`) REFERENCES `paiements` (`id`),
  ADD CONSTRAINT `fk_cibles_instructions_paiement_aade979bc3` FOREIGN KEY (`instruction_id`) REFERENCES `instructions_conformite_paiement` (`id`),
  ADD CONSTRAINT `fk_cibles_instructions_paiement_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `cibles_revocation_origine`
--
ALTER TABLE `cibles_revocation_origine`
  ADD CONSTRAINT `fk_cibles_revocation_origine_9a0f3a5ebf` FOREIGN KEY (`origine_id`) REFERENCES `origines_acquisition` (`id`),
  ADD CONSTRAINT `fk_cibles_revocation_origine_e7b8a48218` FOREIGN KEY (`demande_id`) REFERENCES `demandes_revocation_origines` (`id`);

--
-- Constraints for table `circuits_facturation_vendeur`
--
ALTER TABLE `circuits_facturation_vendeur`
  ADD CONSTRAINT `fk_circuits_facturation_vendeur_14a8e4c9ac` FOREIGN KEY (`mandat_id`,`vendeur_id`) REFERENCES `mandats_facturation` (`id`, `vendeur_id`),
  ADD CONSTRAINT `fk_circuits_facturation_vendeur_54ee10151f` FOREIGN KEY (`vendeur_id`) REFERENCES `vendeurs` (`id`),
  ADD CONSTRAINT `fk_circuits_facturation_vendeur_9908d49023` FOREIGN KEY (`mandat_id`) REFERENCES `mandats_facturation` (`id`);

--
-- Constraints for table `collection`
--
ALTER TABLE `collection`
  ADD CONSTRAINT `fk_collection_9fa7a99695` FOREIGN KEY (`editeur_id`) REFERENCES `editeur` (`editeur_id`);

--
-- Constraints for table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `fk_commandes_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `complements_signalement`
--
ALTER TABLE `complements_signalement`
  ADD CONSTRAINT `fk_complements_signalement_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`);

--
-- Constraints for table `comptes_reversement`
--
ALTER TABLE `comptes_reversement`
  ADD CONSTRAINT `fk_comptes_reversement_0b971bb865` FOREIGN KEY (`sujet_id`) REFERENCES `sujets_kyc` (`id`);

--
-- Constraints for table `comptes_roles`
--
ALTER TABLE `comptes_roles`
  ADD CONSTRAINT `fk_comptes_roles_67827c7edb` FOREIGN KEY (`attribue_par`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_comptes_roles_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_comptes_roles_dcd3b743d9` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);

--
-- Constraints for table `configurations_encaissement`
--
ALTER TABLE `configurations_encaissement`
  ADD CONSTRAINT `fk_configurations_encaissement_5afa1331bb` FOREIGN KEY (`prestataire_id`) REFERENCES `prestataires_paiement` (`id`);

--
-- Constraints for table `configurations_service`
--
ALTER TABLE `configurations_service`
  ADD CONSTRAINT `fk_configurations_service_21ce1dc4f7` FOREIGN KEY (`cree_par`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_configurations_service_7e307fc26a` FOREIGN KEY (`approuve_par`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `confirmations_effet`
--
ALTER TABLE `confirmations_effet`
  ADD CONSTRAINT `fk_confirmations_effet_fbd23836f4` FOREIGN KEY (`ligne_id`) REFERENCES `lignes_ordres_retrait` (`id`);

--
-- Constraints for table `consentements`
--
ALTER TABLE `consentements`
  ADD CONSTRAINT `fk_consentements_59a749685a` FOREIGN KEY (`texte_version_id`) REFERENCES `textes_legaux_versions` (`id`),
  ADD CONSTRAINT `fk_consentements_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `contenus_vises`
--
ALTER TABLE `contenus_vises`
  ADD CONSTRAINT `fk_contenus_vises_674fec4915` FOREIGN KEY (`complement_id`) REFERENCES `complements_signalement` (`id`),
  ADD CONSTRAINT `fk_contenus_vises_e67a56a050` FOREIGN KEY (`complement_id`,`signalement_id`) REFERENCES `complements_signalement` (`id`, `signalement_id`),
  ADD CONSTRAINT `fk_contenus_vises_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`);

--
-- Constraints for table `contributions_publication`
--
ALTER TABLE `contributions_publication`
  ADD CONSTRAINT `fk_contributions_publication_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`),
  ADD CONSTRAINT `fk_contributions_publication_e44f799281` FOREIGN KEY (`auteur_id`) REFERENCES `auteur` (`auteur_id`);

--
-- Constraints for table `controles_completude`
--
ALTER TABLE `controles_completude`
  ADD CONSTRAINT `fk_controles_completude_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_controles_completude_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`);

--
-- Constraints for table `controles_documents`
--
ALTER TABLE `controles_documents`
  ADD CONSTRAINT `fk_controles_documents_2505d46904` FOREIGN KEY (`controle_id`,`dossier_id`) REFERENCES `controles_verification` (`id`, `dossier_id`),
  ADD CONSTRAINT `fk_controles_documents_813ae27fec` FOREIGN KEY (`document_id`,`dossier_id`) REFERENCES `documents_justificatifs` (`id`, `dossier_id`);

--
-- Constraints for table `controles_publication`
--
ALTER TABLE `controles_publication`
  ADD CONSTRAINT `fk_controles_publication_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_controles_publication_6440fb55b5` FOREIGN KEY (`exigence_id`) REFERENCES `exigences_dossier` (`id`);

--
-- Constraints for table `controles_verification`
--
ALTER TABLE `controles_verification`
  ADD CONSTRAINT `fk_controles_verification_22e20c26bc` FOREIGN KEY (`exigence_id`) REFERENCES `exigences_verification` (`id`),
  ADD CONSTRAINT `fk_controles_verification_6b82bdeec5` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_verification` (`id`),
  ADD CONSTRAINT `fk_controles_verification_cc9631d429` FOREIGN KEY (`controleur_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `decisions_alerte`
--
ALTER TABLE `decisions_alerte`
  ADD CONSTRAINT `fk_decisions_alerte_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_decisions_alerte_c76ff85a73` FOREIGN KEY (`alerte_id`) REFERENCES `alertes_securite` (`id`);

--
-- Constraints for table `decisions_controles`
--
ALTER TABLE `decisions_controles`
  ADD CONSTRAINT `fk_decisions_controles_23f016ba3f` FOREIGN KEY (`decision_id`) REFERENCES `decisions_publication` (`id`),
  ADD CONSTRAINT `fk_decisions_controles_a81cd81ed7` FOREIGN KEY (`controle_id`) REFERENCES `controles_publication` (`id`);

--
-- Constraints for table `decisions_extension_contenu`
--
ALTER TABLE `decisions_extension_contenu`
  ADD CONSTRAINT `fk_decisions_extension_contenu_036f24e3da` FOREIGN KEY (`manuscrit_id`) REFERENCES `manuscrits` (`id`),
  ADD CONSTRAINT `fk_decisions_extension_contenu_86003bf47d` FOREIGN KEY (`version_contenu_id`) REFERENCES `versions_contenu` (`id`),
  ADD CONSTRAINT `fk_decisions_extension_contenu_e0542c6149` FOREIGN KEY (`approbateur_compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `decisions_moderation`
--
ALTER TABLE `decisions_moderation`
  ADD CONSTRAINT `fk_decisions_moderation_00da6ae87e` FOREIGN KEY (`phase_id`) REFERENCES `phases_instruction` (`id`),
  ADD CONSTRAINT `fk_decisions_moderation_17bb05379b` FOREIGN KEY (`decideur_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `decisions_publication`
--
ALTER TABLE `decisions_publication`
  ADD CONSTRAINT `fk_decisions_publication_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`),
  ADD CONSTRAINT `fk_decisions_publication_b20aeb369b` FOREIGN KEY (`decideur_compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `decisions_rattachement_compensation`
--
ALTER TABLE `decisions_rattachement_compensation`
  ADD CONSTRAINT `fk_decisions_rattachement_compensation_17bb05379b` FOREIGN KEY (`decideur_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_decisions_rattachement_compensation_69d077b21d` FOREIGN KEY (`reception_id`) REFERENCES `receptions_compensation` (`id`),
  ADD CONSTRAINT `fk_decisions_rattachement_compensation_c53ea5fd9f` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_remboursement` (`id`);

--
-- Constraints for table `decisions_remboursement`
--
ALTER TABLE `decisions_remboursement`
  ADD CONSTRAINT `fk_decisions_remboursement_17bb05379b` FOREIGN KEY (`decideur_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_decisions_remboursement_c53ea5fd9f` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_remboursement` (`id`);

--
-- Constraints for table `decisions_verification`
--
ALTER TABLE `decisions_verification`
  ADD CONSTRAINT `fk_decisions_verification_17bb05379b` FOREIGN KEY (`decideur_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_decisions_verification_6b82bdeec5` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_verification` (`id`);

--
-- Constraints for table `defis_verification`
--
ALTER TABLE `defis_verification`
  ADD CONSTRAINT `fk_defis_verification_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `demandes_attribution_droit`
--
ALTER TABLE `demandes_attribution_droit`
  ADD CONSTRAINT `fk_demandes_attribution_droit_396e5b38ce` FOREIGN KEY (`reference_acquisition_a4_id`) REFERENCES `references_acquisition` (`id`),
  ADD CONSTRAINT `fk_demandes_attribution_droit_49d2468cd3` FOREIGN KEY (`origine_a4_id`) REFERENCES `origines_acquisition` (`id`),
  ADD CONSTRAINT `fk_demandes_attribution_droit_5d79eb4ed5` FOREIGN KEY (`licence_a4_id`,`compte_id`,`offre_id`) REFERENCES `licences_contenu` (`id`, `compte_id`, `offre_id`),
  ADD CONSTRAINT `fk_demandes_attribution_droit_9b3980579d` FOREIGN KEY (`offre_id`) REFERENCES `offre` (`offre_id`),
  ADD CONSTRAINT `fk_demandes_attribution_droit_a90de5d467` FOREIGN KEY (`ligne_commande_id`) REFERENCES `lignes_commande` (`id`),
  ADD CONSTRAINT `fk_demandes_attribution_droit_b6825e989d` FOREIGN KEY (`origine_a4_id`,`reference_acquisition_a4_id`,`licence_a4_id`) REFERENCES `origines_acquisition` (`id`, `reference_acquisition_id`, `licence_id`),
  ADD CONSTRAINT `fk_demandes_attribution_droit_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_demandes_attribution_droit_f56bd65283` FOREIGN KEY (`licence_a4_id`) REFERENCES `licences_contenu` (`id`);

--
-- Constraints for table `demandes_compensation_conformite`
--
ALTER TABLE `demandes_compensation_conformite`
  ADD CONSTRAINT `fk_demandes_compensation_conformite_024cdf0aed` FOREIGN KEY (`decision_id`) REFERENCES `decisions_moderation` (`id`),
  ADD CONSTRAINT `fk_demandes_compensation_conformite_26081d76b9` FOREIGN KEY (`auteur_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_demandes_compensation_conformite_316a237ace` FOREIGN KEY (`dossier_reutilisation_id`,`commande_id`) REFERENCES `dossiers_remboursement` (`id`, `commande_id`),
  ADD CONSTRAINT `fk_demandes_compensation_conformite_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`),
  ADD CONSTRAINT `fk_demandes_compensation_conformite_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`),
  ADD CONSTRAINT `fk_demandes_compensation_conformite_f839016762` FOREIGN KEY (`dossier_reutilisation_id`) REFERENCES `dossiers_remboursement` (`id`),
  ADD CONSTRAINT `fk_demandes_compensation_conformite_fe1d57a629` FOREIGN KEY (`litige_id`) REFERENCES `contestations_paiement` (`id`);

--
-- Constraints for table `demandes_revocation_droit`
--
ALTER TABLE `demandes_revocation_droit`
  ADD CONSTRAINT `fk_demandes_revocation_droit_396e5b38ce` FOREIGN KEY (`reference_acquisition_a4_id`) REFERENCES `references_acquisition` (`id`),
  ADD CONSTRAINT `fk_demandes_revocation_droit_49d2468cd3` FOREIGN KEY (`origine_a4_id`) REFERENCES `origines_acquisition` (`id`),
  ADD CONSTRAINT `fk_demandes_revocation_droit_7f3cb95a6c` FOREIGN KEY (`demande_attribution_id`) REFERENCES `demandes_attribution_droit` (`id`),
  ADD CONSTRAINT `fk_demandes_revocation_droit_831bf2000f` FOREIGN KEY (`decision_id`) REFERENCES `decisions_remboursement` (`id`),
  ADD CONSTRAINT `fk_demandes_revocation_droit_b6825e989d` FOREIGN KEY (`origine_a4_id`,`reference_acquisition_a4_id`,`licence_a4_id`) REFERENCES `origines_acquisition` (`id`, `reference_acquisition_id`, `licence_id`),
  ADD CONSTRAINT `fk_demandes_revocation_droit_f56bd65283` FOREIGN KEY (`licence_a4_id`) REFERENCES `licences_contenu` (`id`);

--
-- Constraints for table `demandes_revocation_origines`
--
ALTER TABLE `demandes_revocation_origines`
  ADD CONSTRAINT `fk_demandes_revocation_origines_024cdf0aed` FOREIGN KEY (`decision_id`) REFERENCES `decisions_moderation` (`id`),
  ADD CONSTRAINT `fk_demandes_revocation_origines_933ac7cd20` FOREIGN KEY (`instruction_paiement_id`) REFERENCES `instructions_conformite_paiement` (`id`);

--
-- Constraints for table `demandes_support`
--
ALTER TABLE `demandes_support`
  ADD CONSTRAINT `fk_demandes_support_0b971bb865` FOREIGN KEY (`sujet_id`) REFERENCES `sujets_kyc` (`id`),
  ADD CONSTRAINT `fk_demandes_support_0c8030acb3` FOREIGN KEY (`decide_par`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_demandes_support_4915ae99e3` FOREIGN KEY (`demandeur_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_demandes_support_4b58081aa6` FOREIGN KEY (`compte_concerne_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_demandes_support_843694ef5d` FOREIGN KEY (`identite_verifiee_par`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_demandes_support_aac02d4042` FOREIGN KEY (`dossier_kyc_id`) REFERENCES `dossiers_verification` (`id`),
  ADD CONSTRAINT `fk_demandes_support_d73eda69b5` FOREIGN KEY (`organisation_id`) REFERENCES `organisations` (`id`);

--
-- Constraints for table `derives_catalogue_publication`
--
ALTER TABLE `derives_catalogue_publication`
  ADD CONSTRAINT `fk_derives_catalogue_publication_6f04578157` FOREIGN KEY (`media_soumis_id`) REFERENCES `medias_soumis_publication` (`id`),
  ADD CONSTRAINT `fk_derives_catalogue_publication_a23d04988b` FOREIGN KEY (`media_catalogue_id`) REFERENCES `media_catalogue` (`media_catalogue_id`),
  ADD CONSTRAINT `fk_derives_catalogue_publication_c507e46451` FOREIGN KEY (`execution_id`) REFERENCES `executions_publication` (`id`);

--
-- Constraints for table `developpements_portee`
--
ALTER TABLE `developpements_portee`
  ADD CONSTRAINT `fk_developpements_portee_136a389008` FOREIGN KEY (`portee_id`) REFERENCES `portees_decisions_mesures` (`id`),
  ADD CONSTRAINT `fk_developpements_portee_f7a720cd2c` FOREIGN KEY (`resolution_enfant_id`) REFERENCES `resolutions_cibles` (`id`);

--
-- Constraints for table `documents_justificatifs`
--
ALTER TABLE `documents_justificatifs`
  ADD CONSTRAINT `fk_documents_justificatifs_6b82bdeec5` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_verification` (`id`),
  ADD CONSTRAINT `fk_documents_justificatifs_9ba3d4c7c2` FOREIGN KEY (`deposant_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `dossiers_categories`
--
ALTER TABLE `dossiers_categories`
  ADD CONSTRAINT `fk_dossiers_categories_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`),
  ADD CONSTRAINT `fk_dossiers_categories_1b981b983f` FOREIGN KEY (`categorie_id`) REFERENCES `categorie` (`categorie_id`);

--
-- Constraints for table `dossiers_facturation`
--
ALTER TABLE `dossiers_facturation`
  ADD CONSTRAINT `fk_dossiers_facturation_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`),
  ADD CONSTRAINT `fk_dossiers_facturation_54ee10151f` FOREIGN KEY (`vendeur_id`) REFERENCES `vendeurs` (`id`),
  ADD CONSTRAINT `fk_dossiers_facturation_7c2e755be9` FOREIGN KEY (`circuit_checkout_id`) REFERENCES `circuits_facturation_vendeur` (`id`),
  ADD CONSTRAINT `fk_dossiers_facturation_b75c378bdd` FOREIGN KEY (`circuit_checkout_id`,`vendeur_id`) REFERENCES `circuits_facturation_vendeur` (`id`, `vendeur_id`),
  ADD CONSTRAINT `fk_dossiers_facturation_d9ded24ce1` FOREIGN KEY (`regle_remuneration_id`) REFERENCES `regles_remuneration` (`id`);

--
-- Constraints for table `dossiers_langues`
--
ALTER TABLE `dossiers_langues`
  ADD CONSTRAINT `fk_dossiers_langues_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`),
  ADD CONSTRAINT `fk_dossiers_langues_de182a5d9b` FOREIGN KEY (`langue_id`) REFERENCES `langue` (`langue_id`);

--
-- Constraints for table `dossiers_publication`
--
ALTER TABLE `dossiers_publication`
  ADD CONSTRAINT `fk_dossiers_publication_036f24e3da` FOREIGN KEY (`manuscrit_id`) REFERENCES `manuscrits` (`id`),
  ADD CONSTRAINT `fk_dossiers_publication_3159957e59` FOREIGN KEY (`niveau_id`) REFERENCES `niveau_educatif` (`niveau_educatif_id`),
  ADD CONSTRAINT `fk_dossiers_publication_95f0b708c3` FOREIGN KEY (`precedent_id`,`manuscrit_id`) REFERENCES `dossiers_publication` (`id`, `manuscrit_id`),
  ADD CONSTRAINT `fk_dossiers_publication_b35525c3bb` FOREIGN KEY (`oeuvre_cible_id`) REFERENCES `oeuvre` (`oeuvre_id`),
  ADD CONSTRAINT `fk_dossiers_publication_e669df51dd` FOREIGN KEY (`precedent_id`) REFERENCES `dossiers_publication` (`id`);

--
-- Constraints for table `dossiers_remboursement`
--
ALTER TABLE `dossiers_remboursement`
  ADD CONSTRAINT `fk_dossiers_remboursement_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`),
  ADD CONSTRAINT `fk_dossiers_remboursement_70797ac77a` FOREIGN KEY (`initiateur_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_dossiers_remboursement_a66fa8f091` FOREIGN KEY (`demandeur_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_dossiers_remboursement_c6171f680e` FOREIGN KEY (`dossier_precedent_id`) REFERENCES `dossiers_remboursement` (`id`),
  ADD CONSTRAINT `fk_dossiers_remboursement_cddb72d9b7` FOREIGN KEY (`dossier_precedent_id`,`commande_id`) REFERENCES `dossiers_remboursement` (`id`, `commande_id`);

--
-- Constraints for table `dossiers_verification`
--
ALTER TABLE `dossiers_verification`
  ADD CONSTRAINT `fk_dossiers_verification_0b971bb865` FOREIGN KEY (`sujet_id`) REFERENCES `sujets_kyc` (`id`),
  ADD CONSTRAINT `fk_dossiers_verification_381aee1d52` FOREIGN KEY (`precedent_id`,`sujet_id`,`niveau`,`numero`) REFERENCES `dossiers_verification` (`id`, `sujet_id`, `niveau`, `numero`),
  ADD CONSTRAINT `fk_dossiers_verification_5a1652e88c` FOREIGN KEY (`profil_fiscal_id`,`sujet_id`) REFERENCES `profils_fiscaux` (`id`, `sujet_id`),
  ADD CONSTRAINT `fk_dossiers_verification_9ba3d4c7c2` FOREIGN KEY (`deposant_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_dossiers_verification_de3e8c7660` FOREIGN KEY (`precedent_id`) REFERENCES `dossiers_verification` (`id`),
  ADD CONSTRAINT `fk_dossiers_verification_e9689b7329` FOREIGN KEY (`compte_reversement_id`,`sujet_id`) REFERENCES `comptes_reversement` (`id`, `sujet_id`);

--
-- Constraints for table `droits_invoques`
--
ALTER TABLE `droits_invoques`
  ADD CONSTRAINT `fk_droits_invoques_674fec4915` FOREIGN KEY (`complement_id`) REFERENCES `complements_signalement` (`id`),
  ADD CONSTRAINT `fk_droits_invoques_e67a56a050` FOREIGN KEY (`complement_id`,`signalement_id`) REFERENCES `complements_signalement` (`id`, `signalement_id`),
  ADD CONSTRAINT `fk_droits_invoques_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`);

--
-- Constraints for table `droits_offres_publication`
--
ALTER TABLE `droits_offres_publication`
  ADD CONSTRAINT `fk_droits_offres_publication_385788081a` FOREIGN KEY (`perimetre_id`) REFERENCES `perimetres_droits_publication` (`id`),
  ADD CONSTRAINT `fk_droits_offres_publication_9b3980579d` FOREIGN KEY (`offre_id`) REFERENCES `offre` (`offre_id`),
  ADD CONSTRAINT `fk_droits_offres_publication_c507e46451` FOREIGN KEY (`execution_id`) REFERENCES `executions_publication` (`id`);

--
-- Constraints for table `droits_territoires`
--
ALTER TABLE `droits_territoires`
  ADD CONSTRAINT `fk_droits_territoires_8d2076ce20` FOREIGN KEY (`territoire_id`) REFERENCES `territoires_conformite` (`id`),
  ADD CONSTRAINT `fk_droits_territoires_a3ba5bfeec` FOREIGN KEY (`droit_id`) REFERENCES `droits_invoques` (`id`);

--
-- Constraints for table `droits_versions_publication`
--
ALTER TABLE `droits_versions_publication`
  ADD CONSTRAINT `fk_droits_versions_publication_385788081a` FOREIGN KEY (`perimetre_id`) REFERENCES `perimetres_droits_publication` (`id`),
  ADD CONSTRAINT `fk_droits_versions_publication_c507e46451` FOREIGN KEY (`execution_id`) REFERENCES `executions_publication` (`id`),
  ADD CONSTRAINT `fk_droits_versions_publication_d34a9dc928` FOREIGN KEY (`version_id`) REFERENCES `versions_contenu` (`id`);

--
-- Constraints for table `echanges_support`
--
ALTER TABLE `echanges_support`
  ADD CONSTRAINT `fk_echanges_support_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_echanges_support_9168e4abdc` FOREIGN KEY (`demande_id`) REFERENCES `demandes_support` (`id`);

--
-- Constraints for table `echeances_conformite`
--
ALTER TABLE `echeances_conformite`
  ADD CONSTRAINT `fk_echeances_conformite_00da6ae87e` FOREIGN KEY (`phase_id`) REFERENCES `phases_instruction` (`id`),
  ADD CONSTRAINT `fk_echeances_conformite_19ef3b61bf` FOREIGN KEY (`partie_id`,`phase_id`) REFERENCES `parties_fournisseur` (`id`, `phase_id`),
  ADD CONSTRAINT `fk_echeances_conformite_341dfe6fb8` FOREIGN KEY (`fait_notification_id`,`notification_id`) REFERENCES `faits_notification` (`id`, `notification_id`),
  ADD CONSTRAINT `fk_echeances_conformite_405965a1a8` FOREIGN KEY (`partie_id`) REFERENCES `parties_fournisseur` (`id`),
  ADD CONSTRAINT `fk_echeances_conformite_8e449da11b` FOREIGN KEY (`notification_id`) REFERENCES `notifications_fournisseur` (`id`),
  ADD CONSTRAINT `fk_echeances_conformite_af0445bd98` FOREIGN KEY (`fait_notification_id`) REFERENCES `faits_notification` (`id`),
  ADD CONSTRAINT `fk_echeances_conformite_b0d494995c` FOREIGN KEY (`notification_id`,`partie_id`) REFERENCES `notifications_fournisseur` (`id`, `partie_id`),
  ADD CONSTRAINT `fk_echeances_conformite_edc5fb5026` FOREIGN KEY (`parametre_id`) REFERENCES `parametres_delai_conformite` (`id`);

--
-- Constraints for table `editeurs_legaux_publication`
--
ALTER TABLE `editeurs_legaux_publication`
  ADD CONSTRAINT `fk_editeurs_legaux_publication_96d8157653` FOREIGN KEY (`edition_cible_id`) REFERENCES `editions_cibles` (`id`),
  ADD CONSTRAINT `fk_editeurs_legaux_publication_9fa7a99695` FOREIGN KEY (`editeur_id`) REFERENCES `editeur` (`editeur_id`);

--
-- Constraints for table `edition`
--
ALTER TABLE `edition`
  ADD CONSTRAINT `fk_edition_13b943eabc` FOREIGN KEY (`oeuvre_id`) REFERENCES `oeuvre` (`oeuvre_id`),
  ADD CONSTRAINT `fk_edition_9fa7a99695` FOREIGN KEY (`editeur_id`) REFERENCES `editeur` (`editeur_id`);

--
-- Constraints for table `editions_cibles`
--
ALTER TABLE `editions_cibles`
  ADD CONSTRAINT `fk_editions_cibles_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`),
  ADD CONSTRAINT `fk_editions_cibles_3957212bef` FOREIGN KEY (`edition_cible_id`) REFERENCES `edition` (`edition_id`);

--
-- Constraints for table `editions_territoires`
--
ALTER TABLE `editions_territoires`
  ADD CONSTRAINT `fk_editions_territoires_0b9be60b2f` FOREIGN KEY (`territoire_id`) REFERENCES `territoires_publication` (`id`),
  ADD CONSTRAINT `fk_editions_territoires_96d8157653` FOREIGN KEY (`edition_cible_id`) REFERENCES `editions_cibles` (`id`);

--
-- Constraints for table `effets_retrait_publication`
--
ALTER TABLE `effets_retrait_publication`
  ADD CONSTRAINT `fk_effets_retrait_publication_b02a50a4d3` FOREIGN KEY (`instruction_id`) REFERENCES `instructions_retrait_publication` (`id`);

--
-- Constraints for table `elements_controle`
--
ALTER TABLE `elements_controle`
  ADD CONSTRAINT `fk_elements_controle_0a402600f0` FOREIGN KEY (`offre_cible_id`) REFERENCES `offres_cibles` (`id`),
  ADD CONSTRAINT `fk_elements_controle_1c86a07e5c` FOREIGN KEY (`selection_id`) REFERENCES `selections_fichiers_publication` (`id`),
  ADD CONSTRAINT `fk_elements_controle_385788081a` FOREIGN KEY (`perimetre_id`) REFERENCES `perimetres_droits_publication` (`id`),
  ADD CONSTRAINT `fk_elements_controle_6f04578157` FOREIGN KEY (`media_soumis_id`) REFERENCES `medias_soumis_publication` (`id`),
  ADD CONSTRAINT `fk_elements_controle_96d8157653` FOREIGN KEY (`edition_cible_id`) REFERENCES `editions_cibles` (`id`),
  ADD CONSTRAINT `fk_elements_controle_a81cd81ed7` FOREIGN KEY (`controle_id`) REFERENCES `controles_publication` (`id`),
  ADD CONSTRAINT `fk_elements_controle_b12e1d8d4f` FOREIGN KEY (`contribution_id`) REFERENCES `contributions_publication` (`id`),
  ADD CONSTRAINT `fk_elements_controle_c391d4f36d` FOREIGN KEY (`dossier_global_id`) REFERENCES `dossiers_publication` (`id`),
  ADD CONSTRAINT `fk_elements_controle_ec57fb551d` FOREIGN KEY (`piece_id`) REFERENCES `pieces_droits_publication` (`id`);

--
-- Constraints for table `evenements_appareil`
--
ALTER TABLE `evenements_appareil`
  ADD CONSTRAINT `fk_evenements_appareil_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_evenements_appareil_92d0c5e8e8` FOREIGN KEY (`appareil_id`) REFERENCES `appareils` (`id`);

--
-- Constraints for table `evenements_blocage`
--
ALTER TABLE `evenements_blocage`
  ADD CONSTRAINT `fk_evenements_blocage_11eac69b0a` FOREIGN KEY (`blocage_id`) REFERENCES `blocages_acces` (`id`),
  ADD CONSTRAINT `fk_evenements_blocage_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `evenements_compte`
--
ALTER TABLE `evenements_compte`
  ADD CONSTRAINT `fk_evenements_compte_3ebaa6013b` FOREIGN KEY (`compte_role_id`,`compte_sujet_id`) REFERENCES `comptes_roles` (`id`, `compte_id`),
  ADD CONSTRAINT `fk_evenements_compte_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_evenements_compte_74cabe212b` FOREIGN KEY (`attribution_interne_id`,`compte_sujet_id`) REFERENCES `attributions_internes` (`id`, `compte_id`),
  ADD CONSTRAINT `fk_evenements_compte_b5842dab0c` FOREIGN KEY (`compte_role_id`) REFERENCES `comptes_roles` (`id`),
  ADD CONSTRAINT `fk_evenements_compte_c91cad6707` FOREIGN KEY (`adhesion_id`,`compte_sujet_id`) REFERENCES `adhesions_organisation` (`id`, `compte_id`),
  ADD CONSTRAINT `fk_evenements_compte_e259db8cec` FOREIGN KEY (`compte_sujet_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_evenements_compte_ead0d9dd58` FOREIGN KEY (`permission_id`) REFERENCES `permissions_organisation` (`id`),
  ADD CONSTRAINT `fk_evenements_compte_f384135efb` FOREIGN KEY (`attribution_interne_id`) REFERENCES `attributions_internes` (`id`),
  ADD CONSTRAINT `fk_evenements_compte_f6dd176b08` FOREIGN KEY (`adhesion_id`) REFERENCES `adhesions_organisation` (`id`);

--
-- Constraints for table `evenements_conformite`
--
ALTER TABLE `evenements_conformite`
  ADD CONSTRAINT `fk_evenements_conformite_00da6ae87e` FOREIGN KEY (`phase_id`) REFERENCES `phases_instruction` (`id`),
  ADD CONSTRAINT `fk_evenements_conformite_1fe59bac09` FOREIGN KEY (`politique_id`) REFERENCES `politiques_conservation` (`id`),
  ADD CONSTRAINT `fk_evenements_conformite_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_evenements_conformite_57b1ad9851` FOREIGN KEY (`objet_id`) REFERENCES `objets_conserves` (`id`),
  ADD CONSTRAINT `fk_evenements_conformite_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`),
  ADD CONSTRAINT `fk_evenements_conformite_f6afe4287f` FOREIGN KEY (`phase_id`,`signalement_id`) REFERENCES `phases_instruction` (`id`, `signalement_id`),
  ADD CONSTRAINT `fk_evenements_conformite_fe1d57a629` FOREIGN KEY (`litige_id`) REFERENCES `contestations_paiement` (`id`);

--
-- Constraints for table `evenements_consentement`
--
ALTER TABLE `evenements_consentement`
  ADD CONSTRAINT `fk_evenements_consentement_bdfbdc7576` FOREIGN KEY (`consentement_id`) REFERENCES `consentements` (`id`);

--
-- Constraints for table `evenements_licence`
--
ALTER TABLE `evenements_licence`
  ADD CONSTRAINT `fk_evenements_licence_1eb7cf1c68` FOREIGN KEY (`origine_id`,`licence_id`) REFERENCES `origines_acquisition` (`id`, `licence_id`),
  ADD CONSTRAINT `fk_evenements_licence_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_evenements_licence_9a0f3a5ebf` FOREIGN KEY (`origine_id`) REFERENCES `origines_acquisition` (`id`),
  ADD CONSTRAINT `fk_evenements_licence_dccc261267` FOREIGN KEY (`licence_id`) REFERENCES `licences_contenu` (`id`);

--
-- Constraints for table `evenements_psp`
--
ALTER TABLE `evenements_psp`
  ADD CONSTRAINT `fk_evenements_psp_571ddb756e` FOREIGN KEY (`paiement_id`) REFERENCES `paiements` (`id`),
  ADD CONSTRAINT `fk_evenements_psp_a53c9de021` FOREIGN KEY (`configuration_id`) REFERENCES `configurations_encaissement` (`id`),
  ADD CONSTRAINT `fk_evenements_psp_fbc678df10` FOREIGN KEY (`paiement_id`,`configuration_id`) REFERENCES `paiements` (`id`, `configuration_id`);

--
-- Constraints for table `evenements_publication`
--
ALTER TABLE `evenements_publication`
  ADD CONSTRAINT `fk_evenements_publication_036f24e3da` FOREIGN KEY (`manuscrit_id`) REFERENCES `manuscrits` (`id`),
  ADD CONSTRAINT `fk_evenements_publication_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`),
  ADD CONSTRAINT `fk_evenements_publication_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_evenements_publication_761a7b0b71` FOREIGN KEY (`dossier_id`,`manuscrit_id`) REFERENCES `dossiers_publication` (`id`, `manuscrit_id`);

--
-- Constraints for table `evenements_taches`
--
ALTER TABLE `evenements_taches`
  ADD CONSTRAINT `fk_evenements_taches_26081d76b9` FOREIGN KEY (`auteur_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_evenements_taches_2a6b17418c` FOREIGN KEY (`tache_id`) REFERENCES `taches_service` (`id`),
  ADD CONSTRAINT `fk_evenements_taches_6a4f2fbe96` FOREIGN KEY (`nouveau_responsable_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_evenements_taches_b148842b3a` FOREIGN KEY (`ancien_responsable_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `evenements_verification`
--
ALTER TABLE `evenements_verification`
  ADD CONSTRAINT `fk_evenements_verification_20c7ab8c90` FOREIGN KEY (`habilitation_id`) REFERENCES `habilitations_verification` (`id`),
  ADD CONSTRAINT `fk_evenements_verification_2505d46904` FOREIGN KEY (`controle_id`,`dossier_id`) REFERENCES `controles_verification` (`id`, `dossier_id`),
  ADD CONSTRAINT `fk_evenements_verification_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_evenements_verification_6b82bdeec5` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_verification` (`id`),
  ADD CONSTRAINT `fk_evenements_verification_82b87edb7f` FOREIGN KEY (`decision_id`,`dossier_id`) REFERENCES `decisions_verification` (`id`, `dossier_id`),
  ADD CONSTRAINT `fk_evenements_verification_d02e1bcff8` FOREIGN KEY (`controle_id`) REFERENCES `controles_verification` (`id`),
  ADD CONSTRAINT `fk_evenements_verification_fe17b34aa7` FOREIGN KEY (`decision_id`) REFERENCES `decisions_verification` (`id`);

--
-- Constraints for table `executions_publication`
--
ALTER TABLE `executions_publication`
  ADD CONSTRAINT `fk_executions_publication_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`);

--
-- Constraints for table `exigences_dossier`
--
ALTER TABLE `exigences_dossier`
  ADD CONSTRAINT `fk_exigences_dossier_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`),
  ADD CONSTRAINT `fk_exigences_dossier_cea39f9a74` FOREIGN KEY (`regle_id`) REFERENCES `regles_controle_publication` (`id`);

--
-- Constraints for table `exigences_verification`
--
ALTER TABLE `exigences_verification`
  ADD CONSTRAINT `fk_exigences_verification_d561fb6580` FOREIGN KEY (`type_organisation_id`) REFERENCES `types_organisation` (`id`);

--
-- Constraints for table `factures_commission`
--
ALTER TABLE `factures_commission`
  ADD CONSTRAINT `fk_factures_commission_1eb43daf24` FOREIGN KEY (`dossier_facturation_id`) REFERENCES `dossiers_facturation` (`id`),
  ADD CONSTRAINT `fk_factures_commission_d9ded24ce1` FOREIGN KEY (`regle_remuneration_id`) REFERENCES `regles_remuneration` (`id`);

--
-- Constraints for table `factures_vente`
--
ALTER TABLE `factures_vente`
  ADD CONSTRAINT `fk_factures_vente_1eb43daf24` FOREIGN KEY (`dossier_facturation_id`) REFERENCES `dossiers_facturation` (`id`),
  ADD CONSTRAINT `fk_factures_vente_9908d49023` FOREIGN KEY (`mandat_id`) REFERENCES `mandats_facturation` (`id`);

--
-- Constraints for table `faits_litige`
--
ALTER TABLE `faits_litige`
  ADD CONSTRAINT `fk_faits_litige_fe1d57a629` FOREIGN KEY (`litige_id`) REFERENCES `contestations_paiement` (`id`);

--
-- Constraints for table `faits_notification`
--
ALTER TABLE `faits_notification`
  ADD CONSTRAINT `fk_faits_notification_8e449da11b` FOREIGN KEY (`notification_id`) REFERENCES `notifications_fournisseur` (`id`);

--
-- Constraints for table `fichiers_manuscrit`
--
ALTER TABLE `fichiers_manuscrit`
  ADD CONSTRAINT `fk_fichiers_manuscrit_036f24e3da` FOREIGN KEY (`manuscrit_id`) REFERENCES `manuscrits` (`id`),
  ADD CONSTRAINT `fk_fichiers_manuscrit_a10b6ac8d8` FOREIGN KEY (`depose_par`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `formats_promo`
--
ALTER TABLE `formats_promo`
  ADD CONSTRAINT `fk_formats_promo_27a6c5be26` FOREIGN KEY (`format_id`) REFERENCES `format_technique` (`format_id`),
  ADD CONSTRAINT `fk_formats_promo_80ea762411` FOREIGN KEY (`version_promo_id`) REFERENCES `versions_code_promo` (`id`);

--
-- Constraints for table `fusions_panier`
--
ALTER TABLE `fusions_panier`
  ADD CONSTRAINT `fk_fusions_panier_a043895cb3` FOREIGN KEY (`panier_cible_id`,`compte_id`) REFERENCES `paniers` (`id`, `compte_id`),
  ADD CONSTRAINT `fk_fusions_panier_b45e538898` FOREIGN KEY (`panier_source_id`) REFERENCES `paniers` (`id`),
  ADD CONSTRAINT `fk_fusions_panier_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_fusions_panier_f3b951cfc6` FOREIGN KEY (`panier_cible_id`) REFERENCES `paniers` (`id`);

--
-- Constraints for table `gels_conservation`
--
ALTER TABLE `gels_conservation`
  ADD CONSTRAINT `fk_gels_conservation_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_gels_conservation_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`),
  ADD CONSTRAINT `fk_gels_conservation_fe1d57a629` FOREIGN KEY (`litige_id`) REFERENCES `contestations_paiement` (`id`);

--
-- Constraints for table `gels_operations_financieres`
--
ALTER TABLE `gels_operations_financieres`
  ADD CONSTRAINT `fk_gels_operations_financieres_2d61304f77` FOREIGN KEY (`instruction_creatrice_id`) REFERENCES `instructions_conformite_paiement` (`id`);

--
-- Constraints for table `habilitations_verification`
--
ALTER TABLE `habilitations_verification`
  ADD CONSTRAINT `fk_habilitations_verification_0b971bb865` FOREIGN KEY (`sujet_id`) REFERENCES `sujets_kyc` (`id`),
  ADD CONSTRAINT `fk_habilitations_verification_8e5ccf5e24` FOREIGN KEY (`decision_courante_id`,`dossier_courant_id`) REFERENCES `decisions_verification` (`id`, `dossier_id`),
  ADD CONSTRAINT `fk_habilitations_verification_f7f8b11187` FOREIGN KEY (`dossier_courant_id`,`sujet_id`,`niveau`) REFERENCES `dossiers_verification` (`id`, `sujet_id`, `niveau`);

--
-- Constraints for table `identites_facturation_commande`
--
ALTER TABLE `identites_facturation_commande`
  ADD CONSTRAINT `fk_identites_facturation_commande_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`);

--
-- Constraints for table `identites_filigrane`
--
ALTER TABLE `identites_filigrane`
  ADD CONSTRAINT `fk_identites_filigrane_dccc261267` FOREIGN KEY (`licence_id`) REFERENCES `licences_contenu` (`id`);

--
-- Constraints for table `instructions_conformite_paiement`
--
ALTER TABLE `instructions_conformite_paiement`
  ADD CONSTRAINT `fk_instructions_conformite_paiement_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_instructions_conformite_paiement_fe1d57a629` FOREIGN KEY (`litige_id`) REFERENCES `contestations_paiement` (`id`),
  ADD CONSTRAINT `fk_instructions_conformite_paiement_fe35155024` FOREIGN KEY (`gel_leve_id`) REFERENCES `gels_operations_financieres` (`id`);

--
-- Constraints for table `instructions_retrait_publication`
--
ALTER TABLE `instructions_retrait_publication`
  ADD CONSTRAINT `fk_instructions_retrait_publication_036f24e3da` FOREIGN KEY (`manuscrit_id`) REFERENCES `manuscrits` (`id`),
  ADD CONSTRAINT `fk_instructions_retrait_publication_13b943eabc` FOREIGN KEY (`oeuvre_id`) REFERENCES `oeuvre` (`oeuvre_id`),
  ADD CONSTRAINT `fk_instructions_retrait_publication_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_instructions_retrait_publication_75580fac81` FOREIGN KEY (`edition_id`) REFERENCES `edition` (`edition_id`),
  ADD CONSTRAINT `fk_instructions_retrait_publication_886afd29ba` FOREIGN KEY (`cible_manuscrit_id`) REFERENCES `manuscrits` (`id`),
  ADD CONSTRAINT `fk_instructions_retrait_publication_9b3980579d` FOREIGN KEY (`offre_id`) REFERENCES `offre` (`offre_id`),
  ADD CONSTRAINT `fk_instructions_retrait_publication_bc17b2f481` FOREIGN KEY (`restriction_source_id`) REFERENCES `restrictions_conformite` (`id`),
  ADD CONSTRAINT `fk_instructions_retrait_publication_bf53b87dbc` FOREIGN KEY (`restriction_levee_id`) REFERENCES `restrictions_conformite` (`id`),
  ADD CONSTRAINT `fk_instructions_retrait_publication_d34a9dc928` FOREIGN KEY (`version_id`) REFERENCES `versions_contenu` (`id`);

--
-- Constraints for table `instructions_revocation`
--
ALTER TABLE `instructions_revocation`
  ADD CONSTRAINT `fk_instructions_revocation_2d54dc47c2` FOREIGN KEY (`origine_id`,`reference_acquisition_id`) REFERENCES `origines_acquisition` (`id`, `reference_acquisition_id`),
  ADD CONSTRAINT `fk_instructions_revocation_80cf80e023` FOREIGN KEY (`reference_acquisition_id`) REFERENCES `references_acquisition` (`id`),
  ADD CONSTRAINT `fk_instructions_revocation_9a0f3a5ebf` FOREIGN KEY (`origine_id`) REFERENCES `origines_acquisition` (`id`);

--
-- Constraints for table `interets_profils`
--
ALTER TABLE `interets_profils`
  ADD CONSTRAINT `fk_interets_profils_6b80fbc851` FOREIGN KEY (`compte_id`) REFERENCES `profils_particuliers` (`compte_id`);

--
-- Constraints for table `isbn_demandes`
--
ALTER TABLE `isbn_demandes`
  ADD CONSTRAINT `fk_isbn_demandes_96d8157653` FOREIGN KEY (`edition_cible_id`) REFERENCES `editions_cibles` (`id`);

--
-- Constraints for table `jetons_acces`
--
ALTER TABLE `jetons_acces`
  ADD CONSTRAINT `fk_jetons_acces_86003bf47d` FOREIGN KEY (`version_contenu_id`) REFERENCES `versions_contenu` (`id`),
  ADD CONSTRAINT `fk_jetons_acces_9b268dadc0` FOREIGN KEY (`session_lecture_id`) REFERENCES `sessions_lecture` (`id`);

--
-- Constraints for table `journaux_acces`
--
ALTER TABLE `journaux_acces`
  ADD CONSTRAINT `fk_journaux_acces_60572305bd` FOREIGN KEY (`segment_id`) REFERENCES `segments_livre` (`id`),
  ADD CONSTRAINT `fk_journaux_acces_6d7fa58d1c` FOREIGN KEY (`jeton_id`) REFERENCES `jetons_acces` (`id`),
  ADD CONSTRAINT `fk_journaux_acces_92d0c5e8e8` FOREIGN KEY (`appareil_id`) REFERENCES `appareils` (`id`),
  ADD CONSTRAINT `fk_journaux_acces_9b268dadc0` FOREIGN KEY (`session_lecture_id`) REFERENCES `sessions_lecture` (`id`),
  ADD CONSTRAINT `fk_journaux_acces_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_journaux_acces_dccc261267` FOREIGN KEY (`licence_id`) REFERENCES `licences_contenu` (`id`);

--
-- Constraints for table `levees_gels_conservation`
--
ALTER TABLE `levees_gels_conservation`
  ADD CONSTRAINT `fk_levees_gels_conservation_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_levees_gels_conservation_6cf547d612` FOREIGN KEY (`gel_id`) REFERENCES `gels_conservation` (`id`);

--
-- Constraints for table `levees_restrictions`
--
ALTER TABLE `levees_restrictions`
  ADD CONSTRAINT `fk_levees_restrictions_0206fe73a6` FOREIGN KEY (`instruction_levee_id`) REFERENCES `instructions_retrait_publication` (`id`),
  ADD CONSTRAINT `fk_levees_restrictions_026d5ad365` FOREIGN KEY (`ligne_levee_id`) REFERENCES `lignes_ordres_retrait` (`id`),
  ADD CONSTRAINT `fk_levees_restrictions_e6055b8e5f` FOREIGN KEY (`restriction_id`) REFERENCES `restrictions_conformite` (`id`);

--
-- Constraints for table `liaisons_compensation`
--
ALTER TABLE `liaisons_compensation`
  ADD CONSTRAINT `fk_liaisons_compensation_a07c8a49f3` FOREIGN KEY (`demande_id`) REFERENCES `demandes_compensation_conformite` (`id`),
  ADD CONSTRAINT `fk_liaisons_compensation_eca8c049ad` FOREIGN KEY (`dossier_remboursement_id`) REFERENCES `dossiers_remboursement` (`id`);

--
-- Constraints for table `licences_contenu`
--
ALTER TABLE `licences_contenu`
  ADD CONSTRAINT `fk_licences_contenu_9b3980579d` FOREIGN KEY (`offre_id`) REFERENCES `offre` (`offre_id`),
  ADD CONSTRAINT `fk_licences_contenu_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `lignes_avoir`
--
ALTER TABLE `lignes_avoir`
  ADD CONSTRAINT `fk_lignes_avoir_93f9346ea2` FOREIGN KEY (`avoir_id`) REFERENCES `avoirs` (`id`),
  ADD CONSTRAINT `fk_lignes_avoir_9ad5a606b7` FOREIGN KEY (`ligne_facture_vente_id`) REFERENCES `lignes_facture_vente` (`id`);

--
-- Constraints for table `lignes_commande`
--
ALTER TABLE `lignes_commande`
  ADD CONSTRAINT `fk_lignes_commande_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`),
  ADD CONSTRAINT `fk_lignes_commande_54ee10151f` FOREIGN KEY (`vendeur_id`) REFERENCES `vendeurs` (`id`),
  ADD CONSTRAINT `fk_lignes_commande_9b3980579d` FOREIGN KEY (`offre_id`) REFERENCES `offre` (`offre_id`),
  ADD CONSTRAINT `fk_lignes_commande_ab891a205c` FOREIGN KEY (`offre_id`,`vendeur_id`) REFERENCES `offre` (`offre_id`, `vendeur_id`),
  ADD CONSTRAINT `fk_lignes_commande_f0d004c022` FOREIGN KEY (`commande_id`,`devise`) REFERENCES `commandes` (`id`, `devise`);

--
-- Constraints for table `lignes_compensation_conformite`
--
ALTER TABLE `lignes_compensation_conformite`
  ADD CONSTRAINT `fk_lignes_compensation_conformite_a07c8a49f3` FOREIGN KEY (`demande_id`) REFERENCES `demandes_compensation_conformite` (`id`),
  ADD CONSTRAINT `fk_lignes_compensation_conformite_a90de5d467` FOREIGN KEY (`ligne_commande_id`) REFERENCES `lignes_commande` (`id`);

--
-- Constraints for table `lignes_decision_remboursement`
--
ALTER TABLE `lignes_decision_remboursement`
  ADD CONSTRAINT `fk_lignes_decision_remboursement_831bf2000f` FOREIGN KEY (`decision_id`) REFERENCES `decisions_remboursement` (`id`),
  ADD CONSTRAINT `fk_lignes_decision_remboursement_a90de5d467` FOREIGN KEY (`ligne_commande_id`) REFERENCES `lignes_commande` (`id`);

--
-- Constraints for table `lignes_facture_vente`
--
ALTER TABLE `lignes_facture_vente`
  ADD CONSTRAINT `fk_lignes_facture_vente_a90de5d467` FOREIGN KEY (`ligne_commande_id`) REFERENCES `lignes_commande` (`id`),
  ADD CONSTRAINT `fk_lignes_facture_vente_edcee08fda` FOREIGN KEY (`facture_id`) REFERENCES `factures_vente` (`id`);

--
-- Constraints for table `lignes_ordres_retrait`
--
ALTER TABLE `lignes_ordres_retrait`
  ADD CONSTRAINT `fk_lignes_ordres_retrait_136a389008` FOREIGN KEY (`portee_id`) REFERENCES `portees_decisions_mesures` (`id`),
  ADD CONSTRAINT `fk_lignes_ordres_retrait_bf53b87dbc` FOREIGN KEY (`restriction_levee_id`) REFERENCES `restrictions_conformite` (`id`),
  ADD CONSTRAINT `fk_lignes_ordres_retrait_d0f0ffc543` FOREIGN KEY (`ordre_id`) REFERENCES `ordres_retrait` (`id`),
  ADD CONSTRAINT `fk_lignes_ordres_retrait_fc0cbc7ed7` FOREIGN KEY (`resolution_id`) REFERENCES `resolutions_cibles` (`id`);

--
-- Constraints for table `lignes_panier`
--
ALTER TABLE `lignes_panier`
  ADD CONSTRAINT `fk_lignes_panier_11a38e374a` FOREIGN KEY (`panier_id`,`devise`) REFERENCES `paniers` (`id`, `devise`),
  ADD CONSTRAINT `fk_lignes_panier_96f9c2483d` FOREIGN KEY (`panier_id`) REFERENCES `paniers` (`id`),
  ADD CONSTRAINT `fk_lignes_panier_9b3980579d` FOREIGN KEY (`offre_id`) REFERENCES `offre` (`offre_id`);

--
-- Constraints for table `litiges_anomalies`
--
ALTER TABLE `litiges_anomalies`
  ADD CONSTRAINT `fk_litiges_anomalies_1f2ee634f4` FOREIGN KEY (`anomalie_id`) REFERENCES `anomalies_paiement` (`id`),
  ADD CONSTRAINT `fk_litiges_anomalies_fe1d57a629` FOREIGN KEY (`litige_id`) REFERENCES `contestations_paiement` (`id`);

--
-- Constraints for table `litiges_commandes`
--
ALTER TABLE `litiges_commandes`
  ADD CONSTRAINT `fk_litiges_commandes_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`),
  ADD CONSTRAINT `fk_litiges_commandes_fe1d57a629` FOREIGN KEY (`litige_id`) REFERENCES `contestations_paiement` (`id`);

--
-- Constraints for table `litiges_paiements`
--
ALTER TABLE `litiges_paiements`
  ADD CONSTRAINT `fk_litiges_paiements_571ddb756e` FOREIGN KEY (`paiement_id`) REFERENCES `paiements` (`id`),
  ADD CONSTRAINT `fk_litiges_paiements_fe1d57a629` FOREIGN KEY (`litige_id`) REFERENCES `contestations_paiement` (`id`);

--
-- Constraints for table `mandats_facturation`
--
ALTER TABLE `mandats_facturation`
  ADD CONSTRAINT `fk_mandats_facturation_54ee10151f` FOREIGN KEY (`vendeur_id`) REFERENCES `vendeurs` (`id`);

--
-- Constraints for table `manifestes_contenu`
--
ALTER TABLE `manifestes_contenu`
  ADD CONSTRAINT `fk_manifestes_contenu_86003bf47d` FOREIGN KEY (`version_contenu_id`) REFERENCES `versions_contenu` (`id`);

--
-- Constraints for table `manuscrits`
--
ALTER TABLE `manuscrits`
  ADD CONSTRAINT `fk_manuscrits_986f2a4234` FOREIGN KEY (`deposant_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_manuscrits_d73eda69b5` FOREIGN KEY (`organisation_id`) REFERENCES `organisations` (`id`);

--
-- Constraints for table `medias_prives`
--
ALTER TABLE `medias_prives`
  ADD CONSTRAINT `fk_medias_prives_27a6c5be26` FOREIGN KEY (`format_id`) REFERENCES `format_technique` (`format_id`),
  ADD CONSTRAINT `fk_medias_prives_75580fac81` FOREIGN KEY (`edition_id`) REFERENCES `edition` (`edition_id`);

--
-- Constraints for table `medias_soumis_publication`
--
ALTER TABLE `medias_soumis_publication`
  ADD CONSTRAINT `fk_medias_soumis_publication_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`);

--
-- Constraints for table `media_catalogue`
--
ALTER TABLE `media_catalogue`
  ADD CONSTRAINT `fk_media_catalogue_13b943eabc` FOREIGN KEY (`oeuvre_id`) REFERENCES `oeuvre` (`oeuvre_id`),
  ADD CONSTRAINT `fk_media_catalogue_75580fac81` FOREIGN KEY (`edition_id`) REFERENCES `edition` (`edition_id`);

--
-- Constraints for table `messages_sortants`
--
ALTER TABLE `messages_sortants`
  ADD CONSTRAINT `fk_messages_sortants_1c405b8b73` FOREIGN KEY (`defi_id`) REFERENCES `defis_verification` (`id`),
  ADD CONSTRAINT `fk_messages_sortants_6b82bdeec5` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_verification` (`id`),
  ADD CONSTRAINT `fk_messages_sortants_8788445382` FOREIGN KEY (`evenement_compte_id`) REFERENCES `evenements_compte` (`id`),
  ADD CONSTRAINT `fk_messages_sortants_9168e4abdc` FOREIGN KEY (`demande_id`) REFERENCES `demandes_support` (`id`),
  ADD CONSTRAINT `fk_messages_sortants_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_messages_sortants_f753ab7a7e` FOREIGN KEY (`evenement_kyc_id`) REFERENCES `evenements_verification` (`id`);

--
-- Constraints for table `mesures_conservatoires`
--
ALTER TABLE `mesures_conservatoires`
  ADD CONSTRAINT `fk_mesures_conservatoires_00da6ae87e` FOREIGN KEY (`phase_id`) REFERENCES `phases_instruction` (`id`),
  ADD CONSTRAINT `fk_mesures_conservatoires_17bb05379b` FOREIGN KEY (`decideur_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `metadonnees_bibliotheque`
--
ALTER TABLE `metadonnees_bibliotheque`
  ADD CONSTRAINT `fk_metadonnees_bibliotheque_9a0f3a5ebf` FOREIGN KEY (`origine_id`) REFERENCES `origines_acquisition` (`id`);

--
-- Constraints for table `niveau_educatif`
--
ALTER TABLE `niveau_educatif`
  ADD CONSTRAINT `fk_niveau_educatif_41c50041c1` FOREIGN KEY (`parent_id`) REFERENCES `niveau_educatif` (`niveau_educatif_id`);

--
-- Constraints for table `notifications_fournisseur`
--
ALTER TABLE `notifications_fournisseur`
  ADD CONSTRAINT `fk_notifications_fournisseur_405965a1a8` FOREIGN KEY (`partie_id`) REFERENCES `parties_fournisseur` (`id`);

--
-- Constraints for table `objets_conserves`
--
ALTER TABLE `objets_conserves`
  ADD CONSTRAINT `fk_objets_conserves_ba2cf1b855` FOREIGN KEY (`adaptateur_id`,`proprietaire`,`type_objet`,`categorie`) REFERENCES `adaptateurs_conservation` (`id`, `proprietaire`, `type_objet`, `categorie`),
  ADD CONSTRAINT `fk_objets_conserves_e80837b9f6` FOREIGN KEY (`adaptateur_id`) REFERENCES `adaptateurs_conservation` (`id`);

--
-- Constraints for table `objets_dossiers_conformite`
--
ALTER TABLE `objets_dossiers_conformite`
  ADD CONSTRAINT `fk_objets_dossiers_conformite_57b1ad9851` FOREIGN KEY (`objet_id`) REFERENCES `objets_conserves` (`id`),
  ADD CONSTRAINT `fk_objets_dossiers_conformite_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`),
  ADD CONSTRAINT `fk_objets_dossiers_conformite_fe1d57a629` FOREIGN KEY (`litige_id`) REFERENCES `contestations_paiement` (`id`);

--
-- Constraints for table `objets_geles`
--
ALTER TABLE `objets_geles`
  ADD CONSTRAINT `fk_objets_geles_57b1ad9851` FOREIGN KEY (`objet_id`) REFERENCES `objets_conserves` (`id`),
  ADD CONSTRAINT `fk_objets_geles_6cf547d612` FOREIGN KEY (`gel_id`) REFERENCES `gels_conservation` (`id`);

--
-- Constraints for table `oeuvre`
--
ALTER TABLE `oeuvre`
  ADD CONSTRAINT `fk_oeuvre_5285da674f` FOREIGN KEY (`langue_principale_id`) REFERENCES `langue` (`langue_id`),
  ADD CONSTRAINT `fk_oeuvre_9675d1d27e` FOREIGN KEY (`collection_id`) REFERENCES `collection` (`collection_id`),
  ADD CONSTRAINT `fk_oeuvre_fa71241bfe` FOREIGN KEY (`niveau_educatif_id`) REFERENCES `niveau_educatif` (`niveau_educatif_id`);

--
-- Constraints for table `oeuvre_auteur`
--
ALTER TABLE `oeuvre_auteur`
  ADD CONSTRAINT `fk_oeuvre_auteur_13b943eabc` FOREIGN KEY (`oeuvre_id`) REFERENCES `oeuvre` (`oeuvre_id`),
  ADD CONSTRAINT `fk_oeuvre_auteur_e44f799281` FOREIGN KEY (`auteur_id`) REFERENCES `auteur` (`auteur_id`);

--
-- Constraints for table `oeuvre_categorie`
--
ALTER TABLE `oeuvre_categorie`
  ADD CONSTRAINT `fk_oeuvre_categorie_13b943eabc` FOREIGN KEY (`oeuvre_id`) REFERENCES `oeuvre` (`oeuvre_id`),
  ADD CONSTRAINT `fk_oeuvre_categorie_1b981b983f` FOREIGN KEY (`categorie_id`) REFERENCES `categorie` (`categorie_id`);

--
-- Constraints for table `oeuvre_langue`
--
ALTER TABLE `oeuvre_langue`
  ADD CONSTRAINT `fk_oeuvre_langue_13b943eabc` FOREIGN KEY (`oeuvre_id`) REFERENCES `oeuvre` (`oeuvre_id`),
  ADD CONSTRAINT `fk_oeuvre_langue_de182a5d9b` FOREIGN KEY (`langue_id`) REFERENCES `langue` (`langue_id`);

--
-- Constraints for table `offre`
--
ALTER TABLE `offre`
  ADD CONSTRAINT `fk_offre_27a6c5be26` FOREIGN KEY (`format_id`) REFERENCES `format_technique` (`format_id`),
  ADD CONSTRAINT `fk_offre_54ee10151f` FOREIGN KEY (`vendeur_id`) REFERENCES `vendeurs` (`id`),
  ADD CONSTRAINT `fk_offre_75580fac81` FOREIGN KEY (`edition_id`) REFERENCES `edition` (`edition_id`);

--
-- Constraints for table `offres_cibles`
--
ALTER TABLE `offres_cibles`
  ADD CONSTRAINT `fk_offres_cibles_27a6c5be26` FOREIGN KEY (`format_id`) REFERENCES `format_technique` (`format_id`),
  ADD CONSTRAINT `fk_offres_cibles_54ee10151f` FOREIGN KEY (`vendeur_id`) REFERENCES `vendeurs` (`id`),
  ADD CONSTRAINT `fk_offres_cibles_7a3e3dd7d1` FOREIGN KEY (`offre_cible_id`) REFERENCES `offre` (`offre_id`),
  ADD CONSTRAINT `fk_offres_cibles_96d8157653` FOREIGN KEY (`edition_cible_id`) REFERENCES `editions_cibles` (`id`);

--
-- Constraints for table `offres_contenu`
--
ALTER TABLE `offres_contenu`
  ADD CONSTRAINT `fk_offres_contenu_86003bf47d` FOREIGN KEY (`version_contenu_id`) REFERENCES `versions_contenu` (`id`),
  ADD CONSTRAINT `fk_offres_contenu_9b3980579d` FOREIGN KEY (`offre_id`) REFERENCES `offre` (`offre_id`);

--
-- Constraints for table `offres_stock`
--
ALTER TABLE `offres_stock`
  ADD CONSTRAINT `fk_offres_stock_229658e0f2` FOREIGN KEY (`unite_stock_id`) REFERENCES `unites_stock` (`id`),
  ADD CONSTRAINT `fk_offres_stock_9b3980579d` FOREIGN KEY (`offre_id`) REFERENCES `offre` (`offre_id`);

--
-- Constraints for table `ordres_retrait`
--
ALTER TABLE `ordres_retrait`
  ADD CONSTRAINT `fk_ordres_retrait_024cdf0aed` FOREIGN KEY (`decision_id`) REFERENCES `decisions_moderation` (`id`),
  ADD CONSTRAINT `fk_ordres_retrait_ac460afc57` FOREIGN KEY (`mesure_id`) REFERENCES `mesures_conservatoires` (`id`);

--
-- Constraints for table `organisations`
--
ALTER TABLE `organisations`
  ADD CONSTRAINT `fk_organisations_d561fb6580` FOREIGN KEY (`type_organisation_id`) REFERENCES `types_organisation` (`id`);

--
-- Constraints for table `origines_acquisition`
--
ALTER TABLE `origines_acquisition`
  ADD CONSTRAINT `fk_origines_acquisition_80cf80e023` FOREIGN KEY (`reference_acquisition_id`) REFERENCES `references_acquisition` (`id`),
  ADD CONSTRAINT `fk_origines_acquisition_a90de5d467` FOREIGN KEY (`ligne_commande_id`) REFERENCES `lignes_commande` (`id`),
  ADD CONSTRAINT `fk_origines_acquisition_dccc261267` FOREIGN KEY (`licence_id`) REFERENCES `licences_contenu` (`id`);

--
-- Constraints for table `paiements`
--
ALTER TABLE `paiements`
  ADD CONSTRAINT `fk_paiements_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`),
  ADD CONSTRAINT `fk_paiements_a53c9de021` FOREIGN KEY (`configuration_id`) REFERENCES `configurations_encaissement` (`id`),
  ADD CONSTRAINT `fk_paiements_ab4b49935a` FOREIGN KEY (`configuration_id`,`moyen`,`devise`) REFERENCES `capacites_encaissement` (`configuration_id`, `moyen`, `devise`),
  ADD CONSTRAINT `fk_paiements_f0d004c022` FOREIGN KEY (`commande_id`,`devise`) REFERENCES `commandes` (`id`, `devise`);

--
-- Constraints for table `paniers`
--
ALTER TABLE `paniers`
  ADD CONSTRAINT `fk_paniers_01df6083b5` FOREIGN KEY (`jeton_id`) REFERENCES `jetons_panier_anonyme` (`id`),
  ADD CONSTRAINT `fk_paniers_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `parties_cibles`
--
ALTER TABLE `parties_cibles`
  ADD CONSTRAINT `fk_parties_cibles_405965a1a8` FOREIGN KEY (`partie_id`) REFERENCES `parties_fournisseur` (`id`),
  ADD CONSTRAINT `fk_parties_cibles_fc0cbc7ed7` FOREIGN KEY (`resolution_id`) REFERENCES `resolutions_cibles` (`id`);

--
-- Constraints for table `parties_fournisseur`
--
ALTER TABLE `parties_fournisseur`
  ADD CONSTRAINT `fk_parties_fournisseur_00da6ae87e` FOREIGN KEY (`phase_id`) REFERENCES `phases_instruction` (`id`),
  ADD CONSTRAINT `fk_parties_fournisseur_54ee10151f` FOREIGN KEY (`vendeur_id`) REFERENCES `vendeurs` (`id`),
  ADD CONSTRAINT `fk_parties_fournisseur_d73eda69b5` FOREIGN KEY (`organisation_id`) REFERENCES `organisations` (`id`),
  ADD CONSTRAINT `fk_parties_fournisseur_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `perimetres_actes`
--
ALTER TABLE `perimetres_actes`
  ADD CONSTRAINT `fk_perimetres_actes_385788081a` FOREIGN KEY (`perimetre_id`) REFERENCES `perimetres_droits_publication` (`id`);

--
-- Constraints for table `perimetres_droits_publication`
--
ALTER TABLE `perimetres_droits_publication`
  ADD CONSTRAINT `fk_perimetres_droits_publication_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`);

--
-- Constraints for table `perimetres_editions`
--
ALTER TABLE `perimetres_editions`
  ADD CONSTRAINT `fk_perimetres_editions_385788081a` FOREIGN KEY (`perimetre_id`) REFERENCES `perimetres_droits_publication` (`id`),
  ADD CONSTRAINT `fk_perimetres_editions_96d8157653` FOREIGN KEY (`edition_cible_id`) REFERENCES `editions_cibles` (`id`);

--
-- Constraints for table `perimetres_formats`
--
ALTER TABLE `perimetres_formats`
  ADD CONSTRAINT `fk_perimetres_formats_27a6c5be26` FOREIGN KEY (`format_id`) REFERENCES `format_technique` (`format_id`),
  ADD CONSTRAINT `fk_perimetres_formats_385788081a` FOREIGN KEY (`perimetre_id`) REFERENCES `perimetres_droits_publication` (`id`);

--
-- Constraints for table `perimetres_pieces`
--
ALTER TABLE `perimetres_pieces`
  ADD CONSTRAINT `fk_perimetres_pieces_385788081a` FOREIGN KEY (`perimetre_id`) REFERENCES `perimetres_droits_publication` (`id`),
  ADD CONSTRAINT `fk_perimetres_pieces_ec57fb551d` FOREIGN KEY (`piece_id`) REFERENCES `pieces_droits_publication` (`id`);

--
-- Constraints for table `perimetres_territoires`
--
ALTER TABLE `perimetres_territoires`
  ADD CONSTRAINT `fk_perimetres_territoires_0b9be60b2f` FOREIGN KEY (`territoire_id`) REFERENCES `territoires_publication` (`id`),
  ADD CONSTRAINT `fk_perimetres_territoires_385788081a` FOREIGN KEY (`perimetre_id`) REFERENCES `perimetres_droits_publication` (`id`);

--
-- Constraints for table `phases_instruction`
--
ALTER TABLE `phases_instruction`
  ADD CONSTRAINT `fk_phases_instruction_28ba3c63b4` FOREIGN KEY (`precedente_id`) REFERENCES `phases_instruction` (`id`),
  ADD CONSTRAINT `fk_phases_instruction_2b9f0a7cad` FOREIGN KEY (`decision_contestee_id`) REFERENCES `decisions_moderation` (`id`),
  ADD CONSTRAINT `fk_phases_instruction_b59061ae2e` FOREIGN KEY (`precedente_id`,`signalement_id`) REFERENCES `phases_instruction` (`id`, `signalement_id`),
  ADD CONSTRAINT `fk_phases_instruction_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`);

--
-- Constraints for table `pieces_droits_publication`
--
ALTER TABLE `pieces_droits_publication`
  ADD CONSTRAINT `fk_pieces_droits_publication_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`),
  ADD CONSTRAINT `fk_pieces_droits_publication_a10b6ac8d8` FOREIGN KEY (`depose_par`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `pieces_rattachements`
--
ALTER TABLE `pieces_rattachements`
  ADD CONSTRAINT `fk_pieces_rattachements_674fec4915` FOREIGN KEY (`complement_id`) REFERENCES `complements_signalement` (`id`),
  ADD CONSTRAINT `fk_pieces_rattachements_c699391bfa` FOREIGN KEY (`reponse_id`) REFERENCES `reponses_fournisseur` (`id`),
  ADD CONSTRAINT `fk_pieces_rattachements_d1f63ca293` FOREIGN KEY (`piece_id`) REFERENCES `pieces_conformite` (`id`),
  ADD CONSTRAINT `fk_pieces_rattachements_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`);

--
-- Constraints for table `pieces_support`
--
ALTER TABLE `pieces_support`
  ADD CONSTRAINT `fk_pieces_support_398bd7a2bd` FOREIGN KEY (`echange_id`,`demande_id`) REFERENCES `echanges_support` (`id`, `demande_id`),
  ADD CONSTRAINT `fk_pieces_support_9168e4abdc` FOREIGN KEY (`demande_id`) REFERENCES `demandes_support` (`id`),
  ADD CONSTRAINT `fk_pieces_support_9ba3d4c7c2` FOREIGN KEY (`deposant_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `plaignants`
--
ALTER TABLE `plaignants`
  ADD CONSTRAINT `fk_plaignants_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `politiques_conservation`
--
ALTER TABLE `politiques_conservation`
  ADD CONSTRAINT `fk_politiques_conservation_f43d190340` FOREIGN KEY (`valide_par`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `portees_decisions_mesures`
--
ALTER TABLE `portees_decisions_mesures`
  ADD CONSTRAINT `fk_portees_decisions_mesures_024cdf0aed` FOREIGN KEY (`decision_id`) REFERENCES `decisions_moderation` (`id`),
  ADD CONSTRAINT `fk_portees_decisions_mesures_ac460afc57` FOREIGN KEY (`mesure_id`) REFERENCES `mesures_conservatoires` (`id`),
  ADD CONSTRAINT `fk_portees_decisions_mesures_fc0cbc7ed7` FOREIGN KEY (`resolution_id`) REFERENCES `resolutions_cibles` (`id`);

--
-- Constraints for table `preparations_filigrane`
--
ALTER TABLE `preparations_filigrane`
  ADD CONSTRAINT `fk_preparations_filigrane_2e0ee9f050` FOREIGN KEY (`identite_filigrane_id`) REFERENCES `identites_filigrane` (`id`),
  ADD CONSTRAINT `fk_preparations_filigrane_86003bf47d` FOREIGN KEY (`version_contenu_id`) REFERENCES `versions_contenu` (`id`);

--
-- Constraints for table `preparations_papier`
--
ALTER TABLE `preparations_papier`
  ADD CONSTRAINT `fk_preparations_papier_0a402600f0` FOREIGN KEY (`offre_cible_id`) REFERENCES `offres_cibles` (`id`),
  ADD CONSTRAINT `fk_preparations_papier_229658e0f2` FOREIGN KEY (`unite_stock_id`) REFERENCES `unites_stock` (`id`);

--
-- Constraints for table `preuves_completude`
--
ALTER TABLE `preuves_completude`
  ADD CONSTRAINT `fk_preuves_completude_674fec4915` FOREIGN KEY (`complement_id`) REFERENCES `complements_signalement` (`id`),
  ADD CONSTRAINT `fk_preuves_completude_a3ba5bfeec` FOREIGN KEY (`droit_id`) REFERENCES `droits_invoques` (`id`),
  ADD CONSTRAINT `fk_preuves_completude_b0bcf9af74` FOREIGN KEY (`controle_id`) REFERENCES `controles_completude` (`id`),
  ADD CONSTRAINT `fk_preuves_completude_c34c8dee41` FOREIGN KEY (`contenu_id`) REFERENCES `contenus_vises` (`id`),
  ADD CONSTRAINT `fk_preuves_completude_d1f63ca293` FOREIGN KEY (`piece_id`) REFERENCES `pieces_conformite` (`id`);

--
-- Constraints for table `preuves_decisions_mesures`
--
ALTER TABLE `preuves_decisions_mesures`
  ADD CONSTRAINT `fk_preuves_decisions_mesures_024cdf0aed` FOREIGN KEY (`decision_id`) REFERENCES `decisions_moderation` (`id`),
  ADD CONSTRAINT `fk_preuves_decisions_mesures_ac460afc57` FOREIGN KEY (`mesure_id`) REFERENCES `mesures_conservatoires` (`id`),
  ADD CONSTRAINT `fk_preuves_decisions_mesures_b0bcf9af74` FOREIGN KEY (`controle_id`) REFERENCES `controles_completude` (`id`),
  ADD CONSTRAINT `fk_preuves_decisions_mesures_c699391bfa` FOREIGN KEY (`reponse_id`) REFERENCES `reponses_fournisseur` (`id`),
  ADD CONSTRAINT `fk_preuves_decisions_mesures_d1f63ca293` FOREIGN KEY (`piece_id`) REFERENCES `pieces_conformite` (`id`);

--
-- Constraints for table `productions_contenu`
--
ALTER TABLE `productions_contenu`
  ADD CONSTRAINT `fk_productions_contenu_27a6c5be26` FOREIGN KEY (`format_id`) REFERENCES `format_technique` (`format_id`),
  ADD CONSTRAINT `fk_productions_contenu_8829188f33` FOREIGN KEY (`version_resultat_id`) REFERENCES `versions_contenu` (`id`),
  ADD CONSTRAINT `fk_productions_contenu_96d8157653` FOREIGN KEY (`edition_cible_id`) REFERENCES `editions_cibles` (`id`),
  ADD CONSTRAINT `fk_productions_contenu_c507e46451` FOREIGN KEY (`execution_id`) REFERENCES `executions_publication` (`id`);

--
-- Constraints for table `productions_sources`
--
ALTER TABLE `productions_sources`
  ADD CONSTRAINT `fk_productions_sources_0dad25e4f0` FOREIGN KEY (`production_id`) REFERENCES `productions_contenu` (`id`),
  ADD CONSTRAINT `fk_productions_sources_1c86a07e5c` FOREIGN KEY (`selection_id`) REFERENCES `selections_fichiers_publication` (`id`);

--
-- Constraints for table `profils_fiscaux`
--
ALTER TABLE `profils_fiscaux`
  ADD CONSTRAINT `fk_profils_fiscaux_0b971bb865` FOREIGN KEY (`sujet_id`) REFERENCES `sujets_kyc` (`id`);

--
-- Constraints for table `profils_particuliers`
--
ALTER TABLE `profils_particuliers`
  ADD CONSTRAINT `fk_profils_particuliers_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `progressions_lecture`
--
ALTER TABLE `progressions_lecture`
  ADD CONSTRAINT `fk_progressions_lecture_5ee72058aa` FOREIGN KEY (`dernier_appareil_id`) REFERENCES `appareils` (`id`),
  ADD CONSTRAINT `fk_progressions_lecture_86003bf47d` FOREIGN KEY (`version_contenu_id`) REFERENCES `versions_contenu` (`id`),
  ADD CONSTRAINT `fk_progressions_lecture_dccc261267` FOREIGN KEY (`licence_id`) REFERENCES `licences_contenu` (`id`);

--
-- Constraints for table `rattachements_resultats_compensation`
--
ALTER TABLE `rattachements_resultats_compensation`
  ADD CONSTRAINT `fk_rattachements_resultats_compensatio_a07c8a49f3` FOREIGN KEY (`demande_id`) REFERENCES `demandes_compensation_conformite` (`id`),
  ADD CONSTRAINT `fk_rattachements_resultats_compensatio_a4da1aea34` FOREIGN KEY (`resultat_id`) REFERENCES `resultats_compensation` (`id`);

--
-- Constraints for table `receptions_compensation`
--
ALTER TABLE `receptions_compensation`
  ADD CONSTRAINT `fk_receptions_compensation_316a237ace` FOREIGN KEY (`dossier_reutilisation_id`,`commande_id`) REFERENCES `dossiers_remboursement` (`id`, `commande_id`),
  ADD CONSTRAINT `fk_receptions_compensation_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`),
  ADD CONSTRAINT `fk_receptions_compensation_7cca1a7868` FOREIGN KEY (`dossier_id`,`commande_id`) REFERENCES `dossiers_remboursement` (`id`, `commande_id`),
  ADD CONSTRAINT `fk_receptions_compensation_b093402d65` FOREIGN KEY (`demande_conformite_id`) REFERENCES `demandes_compensation_conformite` (`id`),
  ADD CONSTRAINT `fk_receptions_compensation_c53ea5fd9f` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_remboursement` (`id`),
  ADD CONSTRAINT `fk_receptions_compensation_f839016762` FOREIGN KEY (`dossier_reutilisation_id`) REFERENCES `dossiers_remboursement` (`id`);

--
-- Constraints for table `receptions_extension_contenu`
--
ALTER TABLE `receptions_extension_contenu`
  ADD CONSTRAINT `fk_receptions_extension_contenu_80cf80e023` FOREIGN KEY (`reference_acquisition_id`) REFERENCES `references_acquisition` (`id`),
  ADD CONSTRAINT `fk_receptions_extension_contenu_86003bf47d` FOREIGN KEY (`version_contenu_id`) REFERENCES `versions_contenu` (`id`),
  ADD CONSTRAINT `fk_receptions_extension_contenu_fd86a2a692` FOREIGN KEY (`decision_extension_id`) REFERENCES `decisions_extension_contenu` (`id`);

--
-- Constraints for table `recus_paiement`
--
ALTER TABLE `recus_paiement`
  ADD CONSTRAINT `fk_recus_paiement_571ddb756e` FOREIGN KEY (`paiement_id`) REFERENCES `paiements` (`id`);

--
-- Constraints for table `remboursements`
--
ALTER TABLE `remboursements`
  ADD CONSTRAINT `fk_remboursements_1f2ee634f4` FOREIGN KEY (`anomalie_id`) REFERENCES `anomalies_paiement` (`id`),
  ADD CONSTRAINT `fk_remboursements_367eebd481` FOREIGN KEY (`decision_id`,`devise`) REFERENCES `decisions_remboursement` (`id`, `devise`),
  ADD CONSTRAINT `fk_remboursements_571ddb756e` FOREIGN KEY (`paiement_id`) REFERENCES `paiements` (`id`),
  ADD CONSTRAINT `fk_remboursements_831bf2000f` FOREIGN KEY (`decision_id`) REFERENCES `decisions_remboursement` (`id`),
  ADD CONSTRAINT `fk_remboursements_c23ea80130` FOREIGN KEY (`paiement_id`,`devise`) REFERENCES `paiements` (`id`, `devise`),
  ADD CONSTRAINT `fk_remboursements_fbc678df10` FOREIGN KEY (`paiement_id`,`configuration_id`) REFERENCES `paiements` (`id`, `configuration_id`);

--
-- Constraints for table `reponses_fournisseur`
--
ALTER TABLE `reponses_fournisseur`
  ADD CONSTRAINT `fk_reponses_fournisseur_405965a1a8` FOREIGN KEY (`partie_id`) REFERENCES `parties_fournisseur` (`id`),
  ADD CONSTRAINT `fk_reponses_fournisseur_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_reponses_fournisseur_8e449da11b` FOREIGN KEY (`notification_id`) REFERENCES `notifications_fournisseur` (`id`),
  ADD CONSTRAINT `fk_reponses_fournisseur_b0d494995c` FOREIGN KEY (`notification_id`,`partie_id`) REFERENCES `notifications_fournisseur` (`id`, `partie_id`),
  ADD CONSTRAINT `fk_reponses_fournisseur_b4c190bb1d` FOREIGN KEY (`acces_id`,`partie_id`) REFERENCES `acces_reponse_conformite` (`id`, `partie_id`),
  ADD CONSTRAINT `fk_reponses_fournisseur_d92967da0e` FOREIGN KEY (`acces_id`) REFERENCES `acces_reponse_conformite` (`id`);

--
-- Constraints for table `reservations_stock`
--
ALTER TABLE `reservations_stock`
  ADD CONSTRAINT `fk_reservations_stock_229658e0f2` FOREIGN KEY (`unite_stock_id`) REFERENCES `unites_stock` (`id`),
  ADD CONSTRAINT `fk_reservations_stock_a90de5d467` FOREIGN KEY (`ligne_commande_id`) REFERENCES `lignes_commande` (`id`);

--
-- Constraints for table `resolutions_cibles`
--
ALTER TABLE `resolutions_cibles`
  ADD CONSTRAINT `fk_resolutions_cibles_13b943eabc` FOREIGN KEY (`oeuvre_id`) REFERENCES `oeuvre` (`oeuvre_id`),
  ADD CONSTRAINT `fk_resolutions_cibles_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_resolutions_cibles_4e3423e639` FOREIGN KEY (`contenu_vise_id`) REFERENCES `contenus_vises` (`id`),
  ADD CONSTRAINT `fk_resolutions_cibles_75580fac81` FOREIGN KEY (`edition_id`) REFERENCES `edition` (`edition_id`),
  ADD CONSTRAINT `fk_resolutions_cibles_86003bf47d` FOREIGN KEY (`version_contenu_id`) REFERENCES `versions_contenu` (`id`),
  ADD CONSTRAINT `fk_resolutions_cibles_9b3980579d` FOREIGN KEY (`offre_id`) REFERENCES `offre` (`offre_id`),
  ADD CONSTRAINT `fk_resolutions_cibles_a23d04988b` FOREIGN KEY (`media_catalogue_id`) REFERENCES `media_catalogue` (`media_catalogue_id`),
  ADD CONSTRAINT `fk_resolutions_cibles_a3a27d5058` FOREIGN KEY (`avis_id`) REFERENCES `avis` (`avis_id`);

--
-- Constraints for table `restitutions_quota_promo`
--
ALTER TABLE `restitutions_quota_promo`
  ADD CONSTRAINT `fk_restitutions_quota_promo_85050287e8` FOREIGN KEY (`decision_remboursement_id`) REFERENCES `decisions_remboursement` (`id`),
  ADD CONSTRAINT `fk_restitutions_quota_promo_919d0b6c8c` FOREIGN KEY (`application_promo_id`) REFERENCES `applications_promo` (`id`);

--
-- Constraints for table `restrictions_conformite`
--
ALTER TABLE `restrictions_conformite`
  ADD CONSTRAINT `fk_restrictions_conformite_4bfce63e32` FOREIGN KEY (`instruction_publication_id`) REFERENCES `instructions_retrait_publication` (`id`),
  ADD CONSTRAINT `fk_restrictions_conformite_5af095e2d3` FOREIGN KEY (`ligne_creatrice_id`) REFERENCES `lignes_ordres_retrait` (`id`);

--
-- Constraints for table `resultats_compensation`
--
ALTER TABLE `resultats_compensation`
  ADD CONSTRAINT `fk_resultats_compensation_eca8c049ad` FOREIGN KEY (`dossier_remboursement_id`) REFERENCES `dossiers_remboursement` (`id`);

--
-- Constraints for table `resultats_editions_publication`
--
ALTER TABLE `resultats_editions_publication`
  ADD CONSTRAINT `fk_resultats_editions_publication_75580fac81` FOREIGN KEY (`edition_id`) REFERENCES `edition` (`edition_id`),
  ADD CONSTRAINT `fk_resultats_editions_publication_96d8157653` FOREIGN KEY (`edition_cible_id`) REFERENCES `editions_cibles` (`id`),
  ADD CONSTRAINT `fk_resultats_editions_publication_c507e46451` FOREIGN KEY (`execution_id`) REFERENCES `executions_publication` (`id`);

--
-- Constraints for table `resultats_oeuvres_publication`
--
ALTER TABLE `resultats_oeuvres_publication`
  ADD CONSTRAINT `fk_resultats_oeuvres_publication_13b943eabc` FOREIGN KEY (`oeuvre_id`) REFERENCES `oeuvre` (`oeuvre_id`),
  ADD CONSTRAINT `fk_resultats_oeuvres_publication_c507e46451` FOREIGN KEY (`execution_id`) REFERENCES `executions_publication` (`id`);

--
-- Constraints for table `resultats_offres_publication`
--
ALTER TABLE `resultats_offres_publication`
  ADD CONSTRAINT `fk_resultats_offres_publication_0a402600f0` FOREIGN KEY (`offre_cible_id`) REFERENCES `offres_cibles` (`id`),
  ADD CONSTRAINT `fk_resultats_offres_publication_9b3980579d` FOREIGN KEY (`offre_id`) REFERENCES `offre` (`offre_id`),
  ADD CONSTRAINT `fk_resultats_offres_publication_c507e46451` FOREIGN KEY (`execution_id`) REFERENCES `executions_publication` (`id`);

--
-- Constraints for table `secrets_transmission`
--
ALTER TABLE `secrets_transmission`
  ADD CONSTRAINT `fk_secrets_transmission_1c405b8b73` FOREIGN KEY (`defi_id`) REFERENCES `defis_verification` (`id`),
  ADD CONSTRAINT `fk_secrets_transmission_955e03f499` FOREIGN KEY (`acces_reponse_id`) REFERENCES `acces_reponse_conformite` (`id`),
  ADD CONSTRAINT `fk_secrets_transmission_aea44c15cf` FOREIGN KEY (`transmission_id`) REFERENCES `transmissions_messages` (`id`);

--
-- Constraints for table `segments_livre`
--
ALTER TABLE `segments_livre`
  ADD CONSTRAINT `fk_segments_livre_9e5125097a` FOREIGN KEY (`manifeste_id`) REFERENCES `manifestes_contenu` (`id`);

--
-- Constraints for table `selections_fichiers_publication`
--
ALTER TABLE `selections_fichiers_publication`
  ADD CONSTRAINT `fk_selections_fichiers_publication_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`),
  ADD CONSTRAINT `fk_selections_fichiers_publication_220c298c90` FOREIGN KEY (`format_cible_id`) REFERENCES `format_technique` (`format_id`),
  ADD CONSTRAINT `fk_selections_fichiers_publication_c341d0ac6d` FOREIGN KEY (`fichier_id`) REFERENCES `fichiers_manuscrit` (`id`);

--
-- Constraints for table `selection_oeuvre`
--
ALTER TABLE `selection_oeuvre`
  ADD CONSTRAINT `fk_selection_oeuvre_13b943eabc` FOREIGN KEY (`oeuvre_id`) REFERENCES `oeuvre` (`oeuvre_id`),
  ADD CONSTRAINT `fk_selection_oeuvre_6a5164c8ce` FOREIGN KEY (`selection_id`) REFERENCES `selection_editoriale` (`selection_id`);

--
-- Constraints for table `sessions_authentification`
--
ALTER TABLE `sessions_authentification`
  ADD CONSTRAINT `fk_sessions_authentification_4bd3e0a2a7` FOREIGN KEY (`config_acces_id`) REFERENCES `configurations_service` (`id`),
  ADD CONSTRAINT `fk_sessions_authentification_75ff383585` FOREIGN KEY (`demande_support_id`) REFERENCES `demandes_support` (`id`),
  ADD CONSTRAINT `fk_sessions_authentification_813176aceb` FOREIGN KEY (`autorise_par`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_sessions_authentification_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `sessions_lecture`
--
ALTER TABLE `sessions_lecture`
  ADD CONSTRAINT `fk_sessions_lecture_172115ff14` FOREIGN KEY (`licence_id`,`compte_id`) REFERENCES `licences_contenu` (`id`, `compte_id`),
  ADD CONSTRAINT `fk_sessions_lecture_1f0117b0dc` FOREIGN KEY (`appareil_id`,`compte_id`) REFERENCES `appareils` (`id`, `compte_id`),
  ADD CONSTRAINT `fk_sessions_lecture_4a6473f986` FOREIGN KEY (`session_auth_id`) REFERENCES `sessions_authentification` (`id`),
  ADD CONSTRAINT `fk_sessions_lecture_92d0c5e8e8` FOREIGN KEY (`appareil_id`) REFERENCES `appareils` (`id`),
  ADD CONSTRAINT `fk_sessions_lecture_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_sessions_lecture_d9b7aab294` FOREIGN KEY (`session_auth_id`,`compte_id`) REFERENCES `sessions_authentification` (`id`, `compte_id`),
  ADD CONSTRAINT `fk_sessions_lecture_dccc261267` FOREIGN KEY (`licence_id`) REFERENCES `licences_contenu` (`id`);

--
-- Constraints for table `signalements_contenu`
--
ALTER TABLE `signalements_contenu`
  ADD CONSTRAINT `fk_signalements_contenu_92dd4f7653` FOREIGN KEY (`plaignant_id`) REFERENCES `plaignants` (`id`);

--
-- Constraints for table `suivis_instructions_externes`
--
ALTER TABLE `suivis_instructions_externes`
  ADD CONSTRAINT `fk_suivis_instructions_externes_017479c0f6` FOREIGN KEY (`gel_conservation_id`) REFERENCES `gels_conservation` (`id`),
  ADD CONSTRAINT `fk_suivis_instructions_externes_57b1ad9851` FOREIGN KEY (`objet_id`) REFERENCES `objets_conserves` (`id`),
  ADD CONSTRAINT `fk_suivis_instructions_externes_660d4aa1d5` FOREIGN KEY (`levee_gel_id`) REFERENCES `levees_gels_conservation` (`id`),
  ADD CONSTRAINT `fk_suivis_instructions_externes_933ac7cd20` FOREIGN KEY (`instruction_paiement_id`) REFERENCES `instructions_conformite_paiement` (`id`),
  ADD CONSTRAINT `fk_suivis_instructions_externes_fd228d52d8` FOREIGN KEY (`cible_instruction_id`) REFERENCES `cibles_instructions_paiement` (`id`);

--
-- Constraints for table `sujets_kyc`
--
ALTER TABLE `sujets_kyc`
  ADD CONSTRAINT `fk_sujets_kyc_5f6babd021` FOREIGN KEY (`profil_compte_id`) REFERENCES `profils_particuliers` (`compte_id`),
  ADD CONSTRAINT `fk_sujets_kyc_d73eda69b5` FOREIGN KEY (`organisation_id`) REFERENCES `organisations` (`id`);

--
-- Constraints for table `suppleances_service`
--
ALTER TABLE `suppleances_service`
  ADD CONSTRAINT `fk_suppleances_service_14f0f196f0` FOREIGN KEY (`suppleant_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_suppleances_service_bf88553300` FOREIGN KEY (`titulaire_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_suppleances_service_fb720039b3` FOREIGN KEY (`defini_par`) REFERENCES `comptes` (`id`);

--
-- Constraints for table `taches_service`
--
ALTER TABLE `taches_service`
  ADD CONSTRAINT `fk_taches_service_3a6420bc24` FOREIGN KEY (`calendrier_config_id`) REFERENCES `configurations_service` (`id`),
  ADD CONSTRAINT `fk_taches_service_3a8b8a0753` FOREIGN KEY (`delais_config_id`) REFERENCES `configurations_service` (`id`),
  ADD CONSTRAINT `fk_taches_service_75ff383585` FOREIGN KEY (`demande_support_id`) REFERENCES `demandes_support` (`id`),
  ADD CONSTRAINT `fk_taches_service_a2e76a7a7d` FOREIGN KEY (`responsable_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_taches_service_aac02d4042` FOREIGN KEY (`dossier_kyc_id`) REFERENCES `dossiers_verification` (`id`);

--
-- Constraints for table `tentatives_publication`
--
ALTER TABLE `tentatives_publication`
  ADD CONSTRAINT `fk_tentatives_publication_c507e46451` FOREIGN KEY (`execution_id`) REFERENCES `executions_publication` (`id`);

--
-- Constraints for table `tentatives_transmission`
--
ALTER TABLE `tentatives_transmission`
  ADD CONSTRAINT `fk_tentatives_transmission_aea44c15cf` FOREIGN KEY (`transmission_id`) REFERENCES `transmissions_messages` (`id`);

--
-- Constraints for table `transactions_paiement`
--
ALTER TABLE `transactions_paiement`
  ADD CONSTRAINT `fk_transactions_paiement_2a07d83cbc` FOREIGN KEY (`remboursement_id`) REFERENCES `remboursements` (`id`),
  ADD CONSTRAINT `fk_transactions_paiement_38d403ac22` FOREIGN KEY (`remboursement_id`,`paiement_id`) REFERENCES `remboursements` (`id`, `paiement_id`),
  ADD CONSTRAINT `fk_transactions_paiement_571ddb756e` FOREIGN KEY (`paiement_id`) REFERENCES `paiements` (`id`),
  ADD CONSTRAINT `fk_transactions_paiement_a8780dfce1` FOREIGN KEY (`evenement_psp_id`) REFERENCES `evenements_psp` (`id`),
  ADD CONSTRAINT `fk_transactions_paiement_c23ea80130` FOREIGN KEY (`paiement_id`,`devise`) REFERENCES `paiements` (`id`, `devise`);

--
-- Constraints for table `transmissions_messages`
--
ALTER TABLE `transmissions_messages`
  ADD CONSTRAINT `fk_transmissions_messages_053121dac3` FOREIGN KEY (`config_delais_id`) REFERENCES `configurations_service` (`id`),
  ADD CONSTRAINT `fk_transmissions_messages_5d3b1caab8` FOREIGN KEY (`message_id`) REFERENCES `messages_sortants` (`id`),
  ADD CONSTRAINT `fk_transmissions_messages_90cf6771d3` FOREIGN KEY (`compte_destinataire_id`) REFERENCES `comptes` (`id`),
  ADD CONSTRAINT `fk_transmissions_messages_d7ec2bb3d3` FOREIGN KEY (`incident_demande_id`) REFERENCES `demandes_support` (`id`);

--
-- Constraints for table `unites_stock`
--
ALTER TABLE `unites_stock`
  ADD CONSTRAINT `fk_unites_stock_54ee10151f` FOREIGN KEY (`vendeur_id`) REFERENCES `vendeurs` (`id`);

--
-- Constraints for table `usages_acces_reponse`
--
ALTER TABLE `usages_acces_reponse`
  ADD CONSTRAINT `fk_usages_acces_reponse_d92967da0e` FOREIGN KEY (`acces_id`) REFERENCES `acces_reponse_conformite` (`id`);

--
-- Constraints for table `validations_reglement`
--
ALTER TABLE `validations_reglement`
  ADD CONSTRAINT `fk_validations_reglement_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`),
  ADD CONSTRAINT `fk_validations_reglement_571ddb756e` FOREIGN KEY (`paiement_id`) REFERENCES `paiements` (`id`),
  ADD CONSTRAINT `fk_validations_reglement_b74a36ca3a` FOREIGN KEY (`paiement_id`,`commande_id`,`devise`) REFERENCES `paiements` (`id`, `commande_id`, `devise`),
  ADD CONSTRAINT `fk_validations_reglement_f0d004c022` FOREIGN KEY (`commande_id`,`devise`) REFERENCES `commandes` (`id`, `devise`);

--
-- Constraints for table `vendeurs`
--
ALTER TABLE `vendeurs`
  ADD CONSTRAINT `fk_vendeurs_0b971bb865` FOREIGN KEY (`sujet_id`) REFERENCES `sujets_kyc` (`id`),
  ADD CONSTRAINT `fk_vendeurs_32f5ef11cd` FOREIGN KEY (`profil_fiscal_id`) REFERENCES `profils_fiscaux` (`id`),
  ADD CONSTRAINT `fk_vendeurs_5a1652e88c` FOREIGN KEY (`profil_fiscal_id`,`sujet_id`) REFERENCES `profils_fiscaux` (`id`, `sujet_id`),
  ADD CONSTRAINT `fk_vendeurs_a0eccedec5` FOREIGN KEY (`compte_reversement_id`) REFERENCES `comptes_reversement` (`id`),
  ADD CONSTRAINT `fk_vendeurs_e9689b7329` FOREIGN KEY (`compte_reversement_id`,`sujet_id`) REFERENCES `comptes_reversement` (`id`, `sujet_id`);

--
-- Constraints for table `versions_code_promo`
--
ALTER TABLE `versions_code_promo`
  ADD CONSTRAINT `fk_versions_code_promo_8b0c721d46` FOREIGN KEY (`code_promo_id`) REFERENCES `codes_promo` (`id`);

--
-- Constraints for table `versions_contenu`
--
ALTER TABLE `versions_contenu`
  ADD CONSTRAINT `fk_versions_contenu_2c5dd34761` FOREIGN KEY (`media_prive_id`) REFERENCES `medias_prives` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
