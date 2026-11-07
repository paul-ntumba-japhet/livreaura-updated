-- Afrique-Educ | MPD V1.0 | sources MLD/MOT V1.3 | MySQL 8.4 / InnoDB
-- Installation initiale dans une base VIDE explicitement choisie. Ne pas utiliser --force.
-- ID ULID fournis par application. Aucun DROP, aucun FOREIGN_KEY_CHECKS=0.
SET NAMES utf8mb4 COLLATE utf8mb4_0900_bin;
SET SESSION time_zone = '+00:00';
SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED;
SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION,ONLY_FULL_GROUP_BY';

-- A1 : comptes
CREATE TABLE `comptes` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `email_normalise` VARCHAR(320) COLLATE utf8mb4_0900_bin NOT NULL,
  `telephone_normalise` VARCHAR(64) NULL,
  `hash_mdp` VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `locale` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `email_verifie_a` DATETIME(6) NULL,
  `telephone_verifie_a` DATETIME(6) NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `ferme_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_comptes_4cc444db7a` UNIQUE (`email_normalise`),
  KEY `ix_comptes_1b624651ed` (`statut`, `cree_a`),
  CONSTRAINT `ck_comptes_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_comptes_8597e6b13c` CHECK (`statut` IN ('pending','active','suspended','closed')),
  CONSTRAINT `ck_comptes_5d9d4684b7` CHECK ((`statut` = 'closed') = (`ferme_a` IS NOT NULL)),
  CONSTRAINT `ck_comptes_c2bf38e013` CHECK (`statut` <> 'active' OR `email_verifie_a` IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : profils_particuliers
CREATE TABLE `profils_particuliers` (
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `prenom` VARCHAR(512) NOT NULL,
  `nom` VARCHAR(512) NOT NULL,
  `nom_plume` VARCHAR(512) NULL,
  `pays` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ville` VARCHAR(512) NULL,
  `langue_ecriture` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`compte_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : interets_profils
CREATE TABLE `interets_profils` (
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `libelle_normalise` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`compte_id`, `libelle_normalise`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : types_organisation
CREATE TABLE `types_organisation` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `libelle` VARCHAR(512) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_types_organisation_5694d08a2e` UNIQUE (`code`),
  CONSTRAINT `ck_types_organisation_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : organisations
CREATE TABLE `organisations` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type_organisation_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `raison_sociale` VARCHAR(512) NOT NULL,
  `email_pro` TEXT NULL,
  `site` TEXT NULL,
  `pays` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ville` VARCHAR(512) NULL,
  `statut_operationnel` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_organisations_272df1a952` (`type_organisation_id`),
  CONSTRAINT `ck_organisations_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_organisations_12018d6aad` CHECK (`statut_operationnel` IN ('active','restricted','closed'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : adhesions_organisation
CREATE TABLE `adhesions_organisation` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `organisation_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `fonction` TEXT NOT NULL,
  `principal` TINYINT UNSIGNED NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `origine` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `active_a` DATETIME(6) NULL,
  `revoque_a` DATETIME(6) NULL,
  `motif` TEXT NULL,
  `uq_principal_actif` TINYINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN `principal` = 1 AND `statut` = 'active' THEN 1 ELSE NULL END) VIRTUAL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_adhesions_organisation_6cd11e2a80` UNIQUE (`compte_id`, `organisation_id`),
  CONSTRAINT `uq_adhesions_organisation_c02c2ab616` UNIQUE (`organisation_id`, `uq_principal_actif`),
  CONSTRAINT `uq_adhesions_organisation_a6e60fd4c7` UNIQUE (`id`, `compte_id`),
  KEY `ix_adhesions_organisation_275b7bcab0` (`organisation_id`, `statut`, `principal`),
  KEY `ix_adhesions_organisation_2f97eab048` (`compte_id`, `statut`),
  CONSTRAINT `ck_adhesions_organisation_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_adhesions_organisation_82f4b0a801` CHECK (`principal` IN (0,1)),
  CONSTRAINT `ck_adhesions_organisation_ca0cf6b416` CHECK (`statut` IN ('active','suspended','revoked','invited'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : permissions_organisation
CREATE TABLE `permissions_organisation` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `libelle` VARCHAR(512) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_permissions_organisation_5694d08a2e` UNIQUE (`code`),
  CONSTRAINT `ck_permissions_organisation_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : adhesions_permissions
CREATE TABLE `adhesions_permissions` (
  `adhesion_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `permission_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`adhesion_id`, `permission_id`),
  KEY `ix_adhesions_permissions_50d0bf2736` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : roles
CREATE TABLE `roles` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `libelle` VARCHAR(512) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_roles_5694d08a2e` UNIQUE (`code`),
  CONSTRAINT `ck_roles_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : comptes_roles
CREATE TABLE `comptes_roles` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `role_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `origine` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `attribue_a` DATETIME(6) NOT NULL,
  `revoque_a` DATETIME(6) NULL,
  `motif` TEXT NULL,
  `attribue_par` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_comptes_roles_f7939aa691` UNIQUE (`compte_id`, `role_id`),
  CONSTRAINT `uq_comptes_roles_a6e60fd4c7` UNIQUE (`id`, `compte_id`),
  KEY `ix_comptes_roles_26525afb8b` (`role_id`),
  KEY `ix_comptes_roles_3562d11560` (`attribue_par`),
  CONSTRAINT `ck_comptes_roles_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_comptes_roles_fd4b44dca4` CHECK (`statut` IN ('active','revoked'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : evenements_compte
CREATE TABLE `evenements_compte` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_sujet_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `adhesion_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `compte_role_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `permission_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `attribution_interne_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `type` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` DATETIME(6) NOT NULL,
  `ancien_etat` TEXT NULL,
  `nouvel_etat` TEXT NULL,
  `motif` TEXT NOT NULL,
  `auteur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_systeme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `fin_prevue` DATETIME(6) NULL,
  `correlation` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_evenements_compte_b209833760` (`compte_sujet_id`, `date`, `id`),
  KEY `ix_evenements_compte_fda8e38ca7` (`adhesion_id`, `compte_sujet_id`),
  KEY `ix_evenements_compte_3040e78aff` (`compte_role_id`, `compte_sujet_id`),
  KEY `ix_evenements_compte_9e0164325d` (`attribution_interne_id`, `compte_sujet_id`),
  KEY `ix_evenements_compte_577bf0c4f4` (`correlation`),
  KEY `ix_evenements_compte_50d0bf2736` (`permission_id`),
  KEY `ix_evenements_compte_1c7f79acb8` (`auteur_compte_id`),
  CONSTRAINT `ck_evenements_compte_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_evenements_compte_34b91e2384` CHECK ((`auteur_compte_id` IS NOT NULL) + (`origine_systeme` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : adresses
CREATE TABLE `adresses` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `libelle` VARCHAR(512) NULL,
  `ligne1` TEXT NOT NULL,
  `ligne2` TEXT NULL,
  `ville` VARCHAR(512) NOT NULL,
  `region` VARCHAR(512) NULL,
  `cp` VARCHAR(64) NULL,
  `pays` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `telephone` VARCHAR(64) NULL,
  `defaut` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_adresses_940f1f0c40` (`compte_id`),
  CONSTRAINT `ck_adresses_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_adresses_3ba7f6f281` CHECK (`defaut` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : textes_legaux_versions
CREATE TABLE `textes_legaux_versions` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `langue` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `contenu` TEXT NULL,
  `reference_immuable` VARCHAR(1024) NULL,
  `empreinte` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `publie_a` DATETIME(6) NOT NULL,
  `requis_pour_activation` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_textes_legaux_versions_23f1bea69d` UNIQUE (`type`, `version`, `langue`),
  CONSTRAINT `ck_textes_legaux_versions_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_textes_legaux_versions_d4405cc371` CHECK (`requis_pour_activation` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : consentements
CREATE TABLE `consentements` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `texte_version_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `nature` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `accepte_a` DATETIME(6) NOT NULL,
  `retire_a` DATETIME(6) NULL,
  `source` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `canal` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_consentements_423fd0467e` UNIQUE (`compte_id`, `texte_version_id`),
  KEY `ix_consentements_74718e64cc` (`texte_version_id`),
  CONSTRAINT `ck_consentements_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : evenements_consentement
CREATE TABLE `evenements_consentement` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `consentement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` DATETIME(6) NOT NULL,
  `source` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `canal` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_evenements_consentement_5f062e1a89` (`consentement_id`),
  CONSTRAINT `ck_evenements_consentement_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : sujets_kyc
CREATE TABLE `sujets_kyc` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `profil_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `organisation_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_sujets_kyc_442b0c944e` UNIQUE (`profil_compte_id`),
  CONSTRAINT `uq_sujets_kyc_1b213432c4` UNIQUE (`organisation_id`),
  CONSTRAINT `ck_sujets_kyc_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_sujets_kyc_b4c7ab7a23` CHECK ((`profil_compte_id` IS NOT NULL) + (`organisation_id` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : exigences_verification
CREATE TABLE `exigences_verification` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_regles` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `type_sujet` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type_organisation_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `pays` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `niveau` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `volet` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `nature_preuve` TEXT NOT NULL,
  `obligatoire` TINYINT UNSIGNED NOT NULL,
  `autorise_non_applicable` TINYINT UNSIGNED NOT NULL,
  `date_effet` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_exigences_verification_04e2d46948` UNIQUE (`code`, `version_regles`),
  KEY `ix_exigences_verification_272df1a952` (`type_organisation_id`),
  CONSTRAINT `ck_exigences_verification_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_exigences_verification_eee7d56226` CHECK (`obligatoire` IN (0,1)),
  CONSTRAINT `ck_exigences_verification_aa6b0ea1a2` CHECK (`autorise_non_applicable` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : profils_fiscaux
CREATE TABLE `profils_fiscaux` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sujet_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version` BIGINT NOT NULL,
  `identifiant_fiscal` TEXT NULL,
  `pays_fiscal` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `nom_legal` TEXT NOT NULL,
  `statut_controle` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_privee` VARCHAR(1024) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_profils_fiscaux_aefc0facfe` UNIQUE (`sujet_id`, `version`),
  CONSTRAINT `uq_profils_fiscaux_4838ff0457` UNIQUE (`id`, `sujet_id`),
  CONSTRAINT `ck_profils_fiscaux_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_profils_fiscaux_97a58d3a38` CHECK (`version` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : comptes_reversement
CREATE TABLE `comptes_reversement` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sujet_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version` BIGINT NOT NULL,
  `canal` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `prestataire` TEXT NULL,
  `titulaire` VARCHAR(512) NOT NULL,
  `pays` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `devise` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_prestataire` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  `coordonnees_chiffrees` MEDIUMBLOB NULL,
  `affichage_masque` TEXT NOT NULL,
  `statut_controle` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `verifie_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_comptes_reversement_aefc0facfe` UNIQUE (`sujet_id`, `version`),
  CONSTRAINT `uq_comptes_reversement_4838ff0457` UNIQUE (`id`, `sujet_id`),
  CONSTRAINT `ck_comptes_reversement_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_comptes_reversement_97a58d3a38` CHECK (`version` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : dossiers_verification
CREATE TABLE `dossiers_verification` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sujet_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `version` BIGINT NOT NULL,
  `precedent_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `niveau` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `deposant_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `profil_fiscal_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `compte_reversement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `soumis_a` DATETIME(6) NULL,
  `clos_a` DATETIME(6) NULL,
  `uq_dossier_courant` TINYINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN `statut` IN ('draft','submitted','under_review') THEN 1 ELSE NULL END) VIRTUAL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_dossiers_verification_15820251d8` UNIQUE (`numero`, `version`),
  CONSTRAINT `uq_dossiers_verification_4ce4a55fd4` UNIQUE (`precedent_id`),
  CONSTRAINT `uq_dossiers_verification_4838ff0457` UNIQUE (`id`, `sujet_id`),
  CONSTRAINT `uq_dossiers_verification_ecced08308` UNIQUE (`id`, `sujet_id`, `niveau`),
  CONSTRAINT `uq_dossiers_verification_f66bb07ba1` UNIQUE (`sujet_id`, `niveau`, `uq_dossier_courant`),
  CONSTRAINT `uq_dossiers_verification_108f486e92` UNIQUE (`id`, `sujet_id`, `niveau`, `numero`),
  KEY `ix_dossiers_verification_60ed19426d` (`sujet_id`, `niveau`, `cree_a`, `id`),
  KEY `ix_dossiers_verification_eb3bc9fa94` (`precedent_id`, `sujet_id`, `niveau`, `numero`),
  KEY `ix_dossiers_verification_8048081132` (`statut`, `soumis_a`, `id`),
  KEY `ix_dossiers_verification_70e5509c24` (`profil_fiscal_id`, `sujet_id`),
  KEY `ix_dossiers_verification_d492b15cd7` (`compte_reversement_id`, `sujet_id`),
  KEY `ix_dossiers_verification_fc6ac65f65` (`deposant_id`),
  CONSTRAINT `ck_dossiers_verification_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_dossiers_verification_97a58d3a38` CHECK (`version` > 0),
  CONSTRAINT `ck_dossiers_verification_3063479354` CHECK (`statut` IN ('draft','submitted','under_review','approved','needs_fix','rejected','expired')),
  CONSTRAINT `ck_dossiers_verification_e500eb952f` CHECK (`clos_a` >= `cree_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : documents_justificatifs
CREATE TABLE `documents_justificatifs` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `deposant_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type_doc` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_privee` VARCHAR(1024) NOT NULL,
  `empreinte` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  `autorite` TEXT NULL,
  `delivre_le` DATE NULL,
  `expire_le` DATE NULL,
  `depose_a` DATETIME(6) NOT NULL,
  `statut_verification` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif_rejet` TEXT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_documents_justificatifs_d68c96505f` UNIQUE (`id`, `dossier_id`),
  KEY `ix_documents_justificatifs_0259b9fde9` (`dossier_id`),
  KEY `ix_documents_justificatifs_fc6ac65f65` (`deposant_id`),
  CONSTRAINT `ck_documents_justificatifs_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_documents_justificatifs_130142c2e2` CHECK (`expire_le` > `delivre_le`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : controles_verification
CREATE TABLE `controles_verification` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `exigence_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resultat` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `observation` TEXT NULL,
  `controle_a` DATETIME(6) NULL,
  `justification_non_applicable` TEXT NULL,
  `controleur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_systeme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_controles_verification_adb4d2e03d` UNIQUE (`dossier_id`, `exigence_id`),
  CONSTRAINT `uq_controles_verification_d68c96505f` UNIQUE (`id`, `dossier_id`),
  KEY `ix_controles_verification_dee52e834b` (`exigence_id`),
  KEY `ix_controles_verification_bc4ddaba69` (`controleur_id`),
  CONSTRAINT `ck_controles_verification_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_controles_verification_358c258d25` CHECK (`resultat` IN ('pending','passed','failed','not_applicable')),
  CONSTRAINT `ck_controles_verification_93fe60c3a1` CHECK (`resultat` = 'pending' OR (`controle_a` IS NOT NULL AND ((`controleur_id` IS NOT NULL) + (`origine_systeme` IS NOT NULL)) = 1)),
  CONSTRAINT `ck_controles_verification_f77fd04e32` CHECK (`resultat` <> 'not_applicable' OR `justification_non_applicable` IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : controles_documents
CREATE TABLE `controles_documents` (
  `controle_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `document_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`controle_id`, `document_id`),
  KEY `ix_controles_documents_e3c59ff60a` (`controle_id`, `dossier_id`),
  KEY `ix_controles_documents_5b73f77d86` (`document_id`, `dossier_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : decisions_verification
CREATE TABLE `decisions_verification` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decideur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resultat` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` TEXT NOT NULL,
  `decide_a` DATETIME(6) NOT NULL,
  `valide_jusqua` DATETIME(6) NULL,
  `restrictions` TEXT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_decisions_verification_0259b9fde9` UNIQUE (`dossier_id`),
  CONSTRAINT `uq_decisions_verification_d68c96505f` UNIQUE (`id`, `dossier_id`),
  KEY `ix_decisions_verification_d6d83fdc33` (`decideur_id`),
  CONSTRAINT `ck_decisions_verification_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_decisions_verification_ad72d1549f` CHECK (`resultat` IN ('approved','needs_fix','rejected')),
  CONSTRAINT `ck_decisions_verification_81e3dfd123` CHECK (`valide_jusqua` > `decide_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : habilitations_verification
CREATE TABLE `habilitations_verification` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sujet_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `niveau` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_courant_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision_courante_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `etat` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `obtenue_a` DATETIME(6) NOT NULL,
  `valide_jusqua` DATETIME(6) NULL,
  `motif_restriction` TEXT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_habilitations_verification_c6cc25b07f` UNIQUE (`sujet_id`, `niveau`),
  CONSTRAINT `uq_habilitations_verification_aaddb70f7a` UNIQUE (`decision_courante_id`),
  KEY `ix_habilitations_verification_8218efd771` (`dossier_courant_id`, `sujet_id`, `niveau`),
  KEY `ix_habilitations_verification_83d56e4bfc` (`etat`, `valide_jusqua`),
  KEY `ix_habilitations_verification_9122bb04b4` (`decision_courante_id`, `dossier_courant_id`),
  CONSTRAINT `ck_habilitations_verification_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_habilitations_verification_4507c019aa` CHECK (`etat` IN ('valid','a_renouveler','restreint','expired','revoked'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : evenements_verification
CREATE TABLE `evenements_verification` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `habilitation_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `controle_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `decision_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `type` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` DATETIME(6) NOT NULL,
  `ancien_etat` TEXT NULL,
  `nouvel_etat` TEXT NULL,
  `motif` TEXT NOT NULL,
  `auteur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_systeme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `correlation` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_evenements_verification_e3c59ff60a` (`controle_id`, `dossier_id`),
  KEY `ix_evenements_verification_86f0b18fa2` (`decision_id`, `dossier_id`),
  KEY `ix_evenements_verification_0259b9fde9` (`dossier_id`),
  KEY `ix_evenements_verification_b3ef6e8f54` (`habilitation_id`),
  KEY `ix_evenements_verification_1c7f79acb8` (`auteur_compte_id`),
  CONSTRAINT `ck_evenements_verification_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_evenements_verification_34b91e2384` CHECK ((`auteur_compte_id` IS NOT NULL) + (`origine_systeme` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : defis_verification
CREATE TABLE `defis_verification` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `finalite` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `canal` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `destination_chiffree` MEDIUMBLOB NOT NULL,
  `empreinte_destination` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `destination_masquee` TEXT NOT NULL,
  `empreinte_secret` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `expire_a` DATETIME(6) NOT NULL,
  `consomme_a` DATETIME(6) NULL,
  `revoque_a` DATETIME(6) NULL,
  `nombre_essais` BIGINT NOT NULL,
  `plafond_essais` BIGINT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_defis_verification_94f55fcefb` (`compte_id`, `finalite`, `expire_a`),
  CONSTRAINT `ck_defis_verification_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_defis_verification_929ad189a0` CHECK (`finalite` IN ('email_activation','password_reset','phone_verification')),
  CONSTRAINT `ck_defis_verification_0ca1723a0d` CHECK (`nombre_essais` >= 0),
  CONSTRAINT `ck_defis_verification_12fa96fb42` CHECK (`plafond_essais` > 0),
  CONSTRAINT `ck_defis_verification_e912f92822` CHECK (NOT (`consomme_a` IS NOT NULL AND `revoque_a` IS NOT NULL)),
  CONSTRAINT `ck_defis_verification_9e2862e502` CHECK (`nombre_essais` <= `plafond_essais`),
  CONSTRAINT `ck_defis_verification_f3e549fe05` CHECK (`expire_a` > `cree_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : sessions_authentification
CREATE TABLE `sessions_authentification` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `empreinte_reference` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `portee` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ouverte_a` DATETIME(6) NOT NULL,
  `derniere_activite_a` DATETIME(6) NOT NULL,
  `expire_absolue_a` DATETIME(6) NOT NULL,
  `expire_inactivite_a` DATETIME(6) NOT NULL,
  `revoque_a` DATETIME(6) NULL,
  `motif_revocation` TEXT NULL,
  `contexte_client` TEXT NULL,
  `demande_support_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `autorise_par` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `config_acces_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_sessions_authentification_e578af1c9f` UNIQUE (`empreinte_reference`),
  CONSTRAINT `uq_sessions_authentification_a6e60fd4c7` UNIQUE (`id`, `compte_id`),
  KEY `ix_sessions_authentification_2f97eab048` (`compte_id`, `statut`),
  KEY `ix_sessions_authentification_afd0b96e52` (`expire_absolue_a`),
  KEY `ix_sessions_authentification_df7db26d23` (`demande_support_id`),
  KEY `ix_sessions_authentification_36e5a78461` (`autorise_par`),
  KEY `ix_sessions_authentification_653084ec8b` (`config_acces_id`),
  CONSTRAINT `ck_sessions_authentification_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_sessions_authentification_0167c3f374` CHECK (`portee` IN ('limited','normal','support_only')),
  CONSTRAINT `ck_sessions_authentification_cc132f4b8c` CHECK (`statut` IN ('active','expired','revoked')),
  CONSTRAINT `ck_sessions_authentification_6b1e7e651f` CHECK ((`portee` = 'support_only' AND `demande_support_id` IS NOT NULL AND `autorise_par` IS NOT NULL) OR (`portee` <> 'support_only' AND `demande_support_id` IS NULL AND `autorise_par` IS NULL)),
  CONSTRAINT `ck_sessions_authentification_ec6a23076f` CHECK (`expire_absolue_a` > `ouverte_a` AND `expire_inactivite_a` > `ouverte_a` AND `expire_inactivite_a` <= `expire_absolue_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : permissions_internes
CREATE TABLE `permissions_internes` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `libelle` VARCHAR(512) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_permissions_internes_5694d08a2e` UNIQUE (`code`),
  CONSTRAINT `ck_permissions_internes_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : attributions_internes
CREATE TABLE `attributions_internes` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `permission_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `attribue_par` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `attribue_a` DATETIME(6) NOT NULL,
  `expire_a` DATETIME(6) NULL,
  `revoque_a` DATETIME(6) NULL,
  `motif` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_attributions_internes_a6e60fd4c7` UNIQUE (`id`, `compte_id`),
  KEY `ix_attributions_internes_dfc2f67cb1` (`compte_id`, `permission_id`, `expire_a`),
  KEY `ix_attributions_internes_50d0bf2736` (`permission_id`),
  KEY `ix_attributions_internes_3562d11560` (`attribue_par`),
  CONSTRAINT `ck_attributions_internes_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : configurations_service
CREATE TABLE `configurations_service` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version` BIGINT NOT NULL,
  `schema_version` BIGINT NOT NULL,
  `valeurs` JSON NOT NULL,
  `empreinte` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_par` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `approuve_par` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `approuve_a` DATETIME(6) NULL,
  `effet_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_configurations_service_f5b433d863` UNIQUE (`code`, `version`),
  CONSTRAINT `uq_configurations_service_0835abdd54` UNIQUE (`code`, `effet_a`),
  KEY `ix_configurations_service_98f9ebd965` (`cree_par`),
  KEY `ix_configurations_service_4245cfefa6` (`approuve_par`),
  CONSTRAINT `ck_configurations_service_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_configurations_service_97a58d3a38` CHECK (`version` > 0),
  CONSTRAINT `ck_configurations_service_5de5a7b618` CHECK (`schema_version` > 0),
  CONSTRAINT `ck_configurations_service_66ce8bdf54` CHECK (JSON_TYPE(`valeurs`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_configurations_service_d73ced787e` CHECK ((`approuve_a` IS NULL) = (`approuve_par` IS NULL)),
  CONSTRAINT `ck_configurations_service_3324c33fe8` CHECK (`effet_a` IS NULL OR `approuve_a` IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : suppleances_service
CREATE TABLE `suppleances_service` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `titulaire_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `suppleant_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `fonction` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `debut_a` DATETIME(6) NOT NULL,
  `fin_a` DATETIME(6) NOT NULL,
  `defini_par` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `defini_a` DATETIME(6) NOT NULL,
  `annule_a` DATETIME(6) NULL,
  `motif` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_suppleances_service_fd75bf3119` (`titulaire_id`, `fonction`, `debut_a`, `fin_a`),
  KEY `ix_suppleances_service_f04acd55c5` (`suppleant_id`),
  KEY `ix_suppleances_service_dd44b2a55b` (`defini_par`),
  CONSTRAINT `ck_suppleances_service_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_suppleances_service_b697448d63` CHECK (`titulaire_id` <> `suppleant_id`),
  CONSTRAINT `ck_suppleances_service_29402577d0` CHECK (`fin_a` > `debut_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : demandes_support
CREATE TABLE `demandes_support` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `type` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `demandeur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `compte_concerne_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `organisation_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `sujet_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `dossier_kyc_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `reference_auteur_externe` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  `contact_chiffre` MEDIUMBLOB NULL,
  `contact_masque` TEXT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `identite_etat` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `identite_verifiee_par` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `identite_verifiee_a` DATETIME(6) NULL,
  `methode_verification` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `conclusion` TEXT NULL,
  `decide_par` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `clos_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_demandes_support_52367a6622` UNIQUE (`reference`),
  KEY `ix_demandes_support_156dce9101` (`compte_concerne_id`, `cree_a`),
  KEY `ix_demandes_support_3435c3bf38` (`demandeur_compte_id`),
  KEY `ix_demandes_support_1b213432c4` (`organisation_id`),
  KEY `ix_demandes_support_38e2c20786` (`sujet_id`),
  KEY `ix_demandes_support_d5aba0d290` (`dossier_kyc_id`),
  KEY `ix_demandes_support_9fd8da60f6` (`identite_verifiee_par`),
  KEY `ix_demandes_support_d5155db14d` (`decide_par`),
  CONSTRAINT `ck_demandes_support_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_demandes_support_2b7a15839b` CHECK (`type` IN ('recours_suspension','recuperation_canal','revendication_auteur','representation','transfert_principal','reactivation','cloture','incident_technique','examen_compte_conformite')),
  CONSTRAINT `ck_demandes_support_f4ce970f41` CHECK (`identite_etat` IN ('non_verifiee','verifiee','refusee','non_requise')),
  CONSTRAINT `ck_demandes_support_e500eb952f` CHECK (`clos_a` >= `cree_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : echanges_support
CREATE TABLE `echanges_support` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `demande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `auteur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `visibilite` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `contenu_prive` TEXT NOT NULL,
  `date` DATETIME(6) NOT NULL,
  `canal` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_echanges_support_78e46a12e9` UNIQUE (`id`, `demande_id`),
  KEY `ix_echanges_support_8067cf7f71` (`demande_id`, `date`, `id`),
  KEY `ix_echanges_support_1c7f79acb8` (`auteur_compte_id`),
  CONSTRAINT `ck_echanges_support_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_echanges_support_b459addc88` CHECK (`origine` IN ('demandeur','agent','systeme')),
  CONSTRAINT `ck_echanges_support_f89a4cd8c2` CHECK (`visibilite` IN ('demandeur','interne'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : pieces_support
CREATE TABLE `pieces_support` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `demande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `echange_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `deposant_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `type` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_privee` VARCHAR(1024) NOT NULL,
  `empreinte` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `depose_a` DATETIME(6) NOT NULL,
  `acces` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_pieces_support_4f1bf3be90` (`echange_id`, `demande_id`),
  KEY `ix_pieces_support_bed904885e` (`demande_id`),
  KEY `ix_pieces_support_fc6ac65f65` (`deposant_id`),
  CONSTRAINT `ck_pieces_support_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_pieces_support_b5196c2abe` CHECK (`acces` IN ('demandeur','interne'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : taches_service
CREATE TABLE `taches_service` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_kyc_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `demande_support_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `responsable_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `etat_travail` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `priorite` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif_priorite` TEXT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `affecte_a` DATETIME(6) NULL,
  `premiere_prise_a` DATETIME(6) NULL,
  `premiere_reponse_a` DATETIME(6) NULL,
  `echeance_prise_a` DATETIME(6) NOT NULL,
  `echeance_reponse_a` DATETIME(6) NOT NULL,
  `prochaine_action_a` DATETIME(6) NULL,
  `motif_attente` TEXT NULL,
  `clos_a` DATETIME(6) NULL,
  `calendrier_config_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `delais_config_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_taches_service_d5aba0d290` UNIQUE (`dossier_kyc_id`),
  CONSTRAINT `uq_taches_service_df7db26d23` UNIQUE (`demande_support_id`),
  KEY `ix_taches_service_202849c30d` (`responsable_id`, `etat_travail`, `echeance_reponse_a`),
  KEY `ix_taches_service_57bd0c5a4b` (`etat_travail`, `prochaine_action_a`),
  KEY `ix_taches_service_f96193e322` (`calendrier_config_id`),
  KEY `ix_taches_service_f5cc9dce14` (`delais_config_id`),
  CONSTRAINT `ck_taches_service_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_taches_service_0abb18d8b6` CHECK (`etat_travail` IN ('a_affecter','en_cours','attente_demandeur','attente_tiers','terminee')),
  CONSTRAINT `ck_taches_service_b7f3a2e0e3` CHECK (`priorite` IN ('normale','urgente')),
  CONSTRAINT `ck_taches_service_329c315d82` CHECK ((`dossier_kyc_id` IS NOT NULL) + (`demande_support_id` IS NOT NULL) = 1),
  CONSTRAINT `ck_taches_service_0c0799fb2c` CHECK (`priorite` <> 'urgente' OR `motif_priorite` IS NOT NULL),
  CONSTRAINT `ck_taches_service_a7d825beb9` CHECK (`etat_travail` <> 'terminee' OR `clos_a` IS NOT NULL),
  CONSTRAINT `ck_taches_service_e500eb952f` CHECK (`clos_a` >= `cree_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : evenements_taches
CREATE TABLE `evenements_taches` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `tache_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` DATETIME(6) NOT NULL,
  `auteur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_systeme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `ancien_responsable_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `nouveau_responsable_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `ancien_etat` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `nouvel_etat` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `motif` TEXT NOT NULL,
  `prochaine_action_a` DATETIME(6) NULL,
  `correlation` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_evenements_taches_ef7597418f` (`tache_id`, `date`, `id`),
  KEY `ix_evenements_taches_6e38b1ca93` (`auteur_id`),
  KEY `ix_evenements_taches_8fcb06fcb7` (`ancien_responsable_id`),
  KEY `ix_evenements_taches_7e8c71c9be` (`nouveau_responsable_id`),
  CONSTRAINT `ck_evenements_taches_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_evenements_taches_bb7ed3c84d` CHECK ((`auteur_id` IS NOT NULL) + (`origine_systeme` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : messages_sortants
CREATE TABLE `messages_sortants` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cle_idempotence` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `type` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `operation` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `correlation` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `demande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `defi_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `evenement_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `evenement_kyc_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `version_objet` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  `producteur` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `schema_message_version` BIGINT NOT NULL,
  `empreinte_intention` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `donnees_minimales` JSON NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_messages_sortants_9f407e61c3` UNIQUE (`cle_idempotence`),
  KEY `ix_messages_sortants_490c6ec1ce` (`type`, `cree_a`),
  KEY `ix_messages_sortants_577bf0c4f4` (`correlation`),
  KEY `ix_messages_sortants_940f1f0c40` (`compte_id`),
  KEY `ix_messages_sortants_0259b9fde9` (`dossier_id`),
  KEY `ix_messages_sortants_bed904885e` (`demande_id`),
  KEY `ix_messages_sortants_e25d4f3ebc` (`defi_id`),
  KEY `ix_messages_sortants_f8c919a6d0` (`evenement_compte_id`),
  KEY `ix_messages_sortants_abb3be0c44` (`evenement_kyc_id`),
  CONSTRAINT `ck_messages_sortants_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_messages_sortants_a34b381374` CHECK (`schema_message_version` > 0),
  CONSTRAINT `ck_messages_sortants_5e9788042c` CHECK (JSON_TYPE(`donnees_minimales`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : transmissions_messages
CREATE TABLE `transmissions_messages` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `message_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `destinataire_cle` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `canal` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_destinataire_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `destination_chiffree` MEDIUMBLOB NULL,
  `destination_masquee` TEXT NULL,
  `etat` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `config_delais_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `prochaine_tentative_a` DATETIME(6) NULL,
  `nombre_tentatives` BIGINT NOT NULL,
  `bail_jusqua` DATETIME(6) NULL,
  `pris_par` TEXT NULL,
  `envoye_a` DATETIME(6) NULL,
  `acquitte_a` DATETIME(6) NULL,
  `niveau_acquittement` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `reference_resultat` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  `incident_demande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `contenu_chiffre_temporaire` MEDIUMBLOB NULL,
  `cle_chiffrement_ref` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  `contenu_expire_a` DATETIME(6) NULL,
  `contenu_purge_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_transmissions_messages_d1aa4d343b` UNIQUE (`message_id`, `destinataire_cle`, `canal`),
  KEY `ix_transmissions_messages_76bbe6d065` (`etat`, `prochaine_tentative_a`),
  KEY `ix_transmissions_messages_f123c00495` (`bail_jusqua`),
  KEY `ix_transmissions_messages_ce5f933f10` (`compte_destinataire_id`),
  KEY `ix_transmissions_messages_81476d9843` (`config_delais_id`),
  KEY `ix_transmissions_messages_cf5457ce05` (`incident_demande_id`),
  CONSTRAINT `ck_transmissions_messages_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_transmissions_messages_7569cad785` CHECK (`canal` IN ('email','sms','interne')),
  CONSTRAINT `ck_transmissions_messages_d046c2869e` CHECK (`etat` IN ('queued','processing','retry_wait','sent','acked','failed','cancelled')),
  CONSTRAINT `ck_transmissions_messages_cabadcb161` CHECK (`nombre_tentatives` >= 0),
  CONSTRAINT `ck_transmissions_messages_6d21f72f3a` CHECK (`niveau_acquittement` IN ('recu_durable','engage_local','effet_confirme')),
  CONSTRAINT `ck_transmissions_messages_cc15b101d5` CHECK (`contenu_chiffre_temporaire` IS NULL OR (`cle_chiffrement_ref` IS NOT NULL AND `contenu_expire_a` IS NOT NULL AND `contenu_purge_a` IS NULL)),
  CONSTRAINT `ck_transmissions_messages_326659c7ee` CHECK (`contenu_purge_a` IS NULL OR `contenu_chiffre_temporaire` IS NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : tentatives_transmission
CREATE TABLE `tentatives_transmission` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `transmission_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero` BIGINT NOT NULL,
  `commence_a` DATETIME(6) NOT NULL,
  `termine_a` DATETIME(6) NULL,
  `resultat` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_prestataire` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  `code_erreur_masque` TEXT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_tentatives_transmission_726cb0d706` UNIQUE (`transmission_id`, `numero`),
  CONSTRAINT `ck_tentatives_transmission_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_tentatives_transmission_367e138391` CHECK (`numero` > 0),
  CONSTRAINT `ck_tentatives_transmission_6b40dc04c9` CHECK (`resultat` IN ('started','accepted','acked','failed','unknown','cancelled')),
  CONSTRAINT `ck_tentatives_transmission_aea252c459` CHECK (`termine_a` >= `commence_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : receptions_messages
CREATE TABLE `receptions_messages` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `evenement_externe_id` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `producteur` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `consommateur` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `correlation` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `recu_a` DATETIME(6) NOT NULL,
  `traite_a` DATETIME(6) NULL,
  `empreinte_intention` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `schema_message_version` BIGINT NOT NULL,
  `donnees_minimales` JSON NOT NULL,
  `niveau_acquittement` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `reference_resultat` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_receptions_messages_7d59c5152d` UNIQUE (`producteur`, `evenement_externe_id`, `consommateur`),
  KEY `ix_receptions_messages_ac03cf58f4` (`consommateur`, `traite_a`, `recu_a`),
  CONSTRAINT `ck_receptions_messages_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_receptions_messages_a34b381374` CHECK (`schema_message_version` > 0),
  CONSTRAINT `ck_receptions_messages_5e9788042c` CHECK (JSON_TYPE(`donnees_minimales`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_receptions_messages_6d21f72f3a` CHECK (`niveau_acquittement` IN ('recu_durable','engage_local','effet_confirme')),
  CONSTRAINT `ck_receptions_messages_29fd46c205` CHECK (`traite_a` >= `recu_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : vendeurs
CREATE TABLE `vendeurs` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sujet_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `profil_fiscal_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `compte_reversement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `serie_facturation` TEXT NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_vendeurs_70e5509c24` (`profil_fiscal_id`, `sujet_id`),
  KEY `ix_vendeurs_d492b15cd7` (`compte_reversement_id`, `sujet_id`),
  KEY `ix_vendeurs_38e2c20786` (`sujet_id`),
  CONSTRAINT `ck_vendeurs_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_vendeurs_5f015f328b` CHECK (`actif` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : acces_documents_support
CREATE TABLE `acces_documents_support` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `demande_support_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `facture_vente_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `facture_commission_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `avoir_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `recu_paiement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `debut` DATETIME(6) NOT NULL,
  `fin` DATETIME(6) NOT NULL,
  `revoque_a` DATETIME(6) NULL,
  `autorise_par` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_acces_documents_support_df7db26d23` (`demande_support_id`),
  KEY `ix_acces_documents_support_940f1f0c40` (`compte_id`),
  KEY `ix_acces_documents_support_3b530ef393` (`facture_vente_id`),
  KEY `ix_acces_documents_support_eb7b937696` (`facture_commission_id`),
  KEY `ix_acces_documents_support_42318f3fe1` (`avoir_id`),
  KEY `ix_acces_documents_support_75359aed2d` (`recu_paiement_id`),
  KEY `ix_acces_documents_support_36e5a78461` (`autorise_par`),
  CONSTRAINT `ck_acces_documents_support_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_acces_documents_support_5869ed1c79` CHECK ((`facture_vente_id` IS NOT NULL) + (`facture_commission_id` IS NOT NULL) + (`avoir_id` IS NOT NULL) + (`recu_paiement_id` IS NOT NULL) = 1),
  CONSTRAINT `ck_acces_documents_support_e2d2138eff` CHECK (`fin` > `debut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : secrets_transmission
CREATE TABLE `secrets_transmission` (
  `transmission_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `defi_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `acces_reponse_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`transmission_id`),
  KEY `ix_secrets_transmission_e25d4f3ebc` (`defi_id`),
  KEY `ix_secrets_transmission_edb0e641fb` (`acces_reponse_id`),
  CONSTRAINT `ck_secrets_transmission_62815cdde2` CHECK ((`defi_id` IS NOT NULL) + (`acces_reponse_id` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A1 : verrous_coordination
CREATE TABLE `verrous_coordination` (
  `code` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`code`),
  CONSTRAINT `ck_verrous_coordination_23179c0eca` CHECK (`code` IN ('identite','catalogue','finance','conservation'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A2 : OEUVRE
CREATE TABLE `oeuvre` (
  `oeuvre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `slug` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `titre` VARCHAR(512) NOT NULL,
  `sous_titre` VARCHAR(512) NULL,
  `resume` TEXT NULL,
  `pages` BIGINT NULL,
  `langue_principale_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `availability` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `badge` TEXT NULL,
  `africain` TINYINT UNSIGNED NOT NULL,
  `mis_en_avant` TINYINT UNSIGNED NOT NULL,
  `publie_a` DATETIME(6) NULL,
  `note_moyenne` DECIMAL(20,8) NULL,
  `avis_count` BIGINT NOT NULL,
  `retire_a` DATETIME(6) NULL,
  `niveau_educatif_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `collection_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`oeuvre_id`),
  CONSTRAINT `uq_oeuvre_cd03861f0f` UNIQUE (`slug`),
  KEY `ix_oeuvre_f61eb7fd13` (`statut`, `availability`, `publie_a`, `oeuvre_id`),
  KEY `ix_oeuvre_84aa491f0b` (`collection_id`, `statut`),
  KEY `ix_oeuvre_2603951ba7` (`langue_principale_id`),
  KEY `ix_oeuvre_3479940c48` (`niveau_educatif_id`),
  CONSTRAINT `ck_oeuvre_dcfa28fe48` CHECK (REGEXP_LIKE(`oeuvre_id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_oeuvre_a742274f1d` CHECK (`pages` > 0),
  CONSTRAINT `ck_oeuvre_90b25d9713` CHECK (`statut` IN ('draft','published','archived')),
  CONSTRAINT `ck_oeuvre_e2a43f44e0` CHECK (`availability` IN ('available','soon')),
  CONSTRAINT `ck_oeuvre_9a5bdc595a` CHECK (`africain` IN (0,1)),
  CONSTRAINT `ck_oeuvre_f25c8151f2` CHECK (`mis_en_avant` IN (0,1)),
  CONSTRAINT `ck_oeuvre_2be46bf3a9` CHECK (`avis_count` >= 0),
  CONSTRAINT `ck_oeuvre_7bbf6ff4b8` CHECK (`note_moyenne` BETWEEN 1 AND 5),
  CONSTRAINT `ck_oeuvre_3e22ec76ab` CHECK (`statut` <> 'published' OR `publie_a` IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A2 : EDITION
CREATE TABLE `edition` (
  `edition_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `oeuvre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `editeur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `isbn` TEXT NULL,
  `titre_edition` VARCHAR(512) NULL,
  `editeur_legal_libelle` TEXT NULL,
  `role_ae` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `responsable_depot` TEXT NULL,
  `territoire` TEXT NULL,
  `date_edition` DATE NULL,
  `statut_depot` TEXT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `consultable` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`edition_id`),
  KEY `ix_edition_a3fad59948` (`oeuvre_id`),
  KEY `ix_edition_a7ec1680cf` (`editeur_id`),
  CONSTRAINT `ck_edition_1e33303867` CHECK (REGEXP_LIKE(`edition_id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_edition_c980299200` CHECK (`role_ae` IN ('distributeur','prestataire','editeur_label')),
  CONSTRAINT `ck_edition_c1099e7b1b` CHECK (`statut` IN ('active','archived')),
  CONSTRAINT `ck_edition_991ee826a4` CHECK (`consultable` IN (0,1)),
  CONSTRAINT `ck_edition_f91d225887` CHECK (`consultable` = 0 OR `role_ae` <> 'editeur_label')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A2 : FORMAT_TECHNIQUE
CREATE TABLE `format_technique` (
  `format_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `libelle` VARCHAR(512) NOT NULL,
  `sort_order` BIGINT NOT NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`format_id`),
  CONSTRAINT `uq_format_technique_5694d08a2e` UNIQUE (`code`),
  CONSTRAINT `ck_format_technique_cb425fb4a3` CHECK (REGEXP_LIKE(`format_id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_format_technique_cc6f6520f6` CHECK (`code` IN ('epub','pdf','audio','paper')),
  CONSTRAINT `ck_format_technique_77e1fce5a5` CHECK (`sort_order` >= 0),
  CONSTRAINT `ck_format_technique_5f015f328b` CHECK (`actif` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A2 : OFFRE
CREATE TABLE `offre` (
  `offre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `edition_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `format_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `vendeur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `modalite_commerciale` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `prix` DECIMAL(20,4) NOT NULL,
  `devise` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `condition` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sku` TEXT NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  `stock` BIGINT NULL,
  `retire_a` DATETIME(6) NULL,
  PRIMARY KEY (`offre_id`),
  CONSTRAINT `uq_offre_ae7cb3843a` UNIQUE (`edition_id`, `format_id`, `vendeur_id`, `condition`, `modalite_commerciale`, `devise`),
  CONSTRAINT `uq_offre_9eaa7978a9` UNIQUE (`offre_id`, `vendeur_id`),
  KEY `ix_offre_3772191b07` (`edition_id`, `actif`, `format_id`, `devise`, `prix`),
  KEY `ix_offre_5532d2348c` (`format_id`),
  KEY `ix_offre_59710ce746` (`vendeur_id`),
  CONSTRAINT `ck_offre_8d632a34c5` CHECK (REGEXP_LIKE(`offre_id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_offre_eea3a49c44` CHECK (`modalite_commerciale` IN ('achat','location')),
  CONSTRAINT `ck_offre_dc389a0ff5` CHECK (`devise` IN ('CDF','USD')),
  CONSTRAINT `ck_offre_5f7c6c8db4` CHECK (`condition` IN ('none','new','used')),
  CONSTRAINT `ck_offre_5f015f328b` CHECK (`actif` IN (0,1)),
  CONSTRAINT `ck_offre_07de1eff26` CHECK (`stock` >= 0),
  CONSTRAINT `ck_offre_adfea8dd71` CHECK (`prix` >= 0),
  CONSTRAINT `ck_offre_3ba34892b4` CHECK (`actif` = 0 OR `modalite_commerciale` = 'achat')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A2 : AUTEUR
CREATE TABLE `auteur` (
  `auteur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `slug` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `nom_affiche` VARCHAR(512) NOT NULL,
  `bio` TEXT NULL,
  `pays` VARCHAR(64) NULL,
  `role_affiche` TEXT NULL,
  `verified` TINYINT UNSIGNED NOT NULL,
  `mis_en_avant` TINYINT UNSIGNED NOT NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  `statut_publication` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`auteur_id`),
  CONSTRAINT `uq_auteur_cd03861f0f` UNIQUE (`slug`),
  CONSTRAINT `uq_auteur_940f1f0c40` UNIQUE (`compte_id`),
  CONSTRAINT `ck_auteur_4dd8bbfd60` CHECK (REGEXP_LIKE(`auteur_id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_auteur_def29b961f` CHECK (`verified` IN (0,1)),
  CONSTRAINT `ck_auteur_f25c8151f2` CHECK (`mis_en_avant` IN (0,1)),
  CONSTRAINT `ck_auteur_5f015f328b` CHECK (`actif` IN (0,1)),
  CONSTRAINT `ck_auteur_2856daf5c0` CHECK (`statut_publication` IN ('draft','published','archived'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A2 : EDITEUR
CREATE TABLE `editeur` (
  `editeur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `slug` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `nom` VARCHAR(512) NOT NULL,
  `type` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `presentation` TEXT NULL,
  `pays` VARCHAR(64) NULL,
  `is_partner` TINYINT UNSIGNED NOT NULL,
  `is_academic` TINYINT UNSIGNED NOT NULL,
  `is_youth` TINYINT UNSIGNED NOT NULL,
  `is_african` TINYINT UNSIGNED NOT NULL,
  `is_indie` TINYINT UNSIGNED NOT NULL,
  `mis_en_avant` TINYINT UNSIGNED NOT NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`editeur_id`),
  CONSTRAINT `uq_editeur_cd03861f0f` UNIQUE (`slug`),
  CONSTRAINT `ck_editeur_6ee3de4cfb` CHECK (REGEXP_LIKE(`editeur_id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_editeur_22c835d349` CHECK (`type` IN ('publisher','house')),
  CONSTRAINT `ck_editeur_047d9e5565` CHECK (`is_partner` IN (0,1)),
  CONSTRAINT `ck_editeur_0c8349b10f` CHECK (`is_academic` IN (0,1)),
  CONSTRAINT `ck_editeur_8cc9784321` CHECK (`is_youth` IN (0,1)),
  CONSTRAINT `ck_editeur_8f50cf01c8` CHECK (`is_african` IN (0,1)),
  CONSTRAINT `ck_editeur_3cd7145471` CHECK (`is_indie` IN (0,1)),
  CONSTRAINT `ck_editeur_f25c8151f2` CHECK (`mis_en_avant` IN (0,1)),
  CONSTRAINT `ck_editeur_5f015f328b` CHECK (`actif` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A2 : CATEGORIE
CREATE TABLE `categorie` (
  `categorie_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `parent_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `slug` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `nom` VARCHAR(512) NOT NULL,
  `sort_order` BIGINT NOT NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  `uq_racine` TINYINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN `parent_id` IS NULL THEN 1 ELSE NULL END) VIRTUAL,
  PRIMARY KEY (`categorie_id`),
  CONSTRAINT `uq_categorie_614c157be1` UNIQUE (`parent_id`, `slug`),
  CONSTRAINT `uq_categorie_a0b3868f4a` UNIQUE (`slug`, `uq_racine`),
  CONSTRAINT `ck_categorie_e803e53622` CHECK (REGEXP_LIKE(`categorie_id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_categorie_77e1fce5a5` CHECK (`sort_order` >= 0),
  CONSTRAINT `ck_categorie_5f015f328b` CHECK (`actif` IN (0,1)),
  CONSTRAINT `ck_categorie_dcc16b7e7d` CHECK (`parent_id` <> `categorie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A2 : LANGUE
CREATE TABLE `langue` (
  `langue_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `nom` VARCHAR(512) NOT NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`langue_id`),
  CONSTRAINT `uq_langue_5694d08a2e` UNIQUE (`code`),
  CONSTRAINT `ck_langue_c08270bfe2` CHECK (REGEXP_LIKE(`langue_id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_langue_5f015f328b` CHECK (`actif` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A2 : NIVEAU_EDUCATIF
CREATE TABLE `niveau_educatif` (
  `niveau_educatif_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `parent_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `slug` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `nom` VARCHAR(512) NOT NULL,
  `sort_order` BIGINT NOT NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`niveau_educatif_id`),
  CONSTRAINT `uq_niveau_educatif_cd03861f0f` UNIQUE (`slug`),
  KEY `ix_niveau_educatif_7b54484fae` (`parent_id`),
  CONSTRAINT `ck_niveau_educatif_7e20d55c25` CHECK (REGEXP_LIKE(`niveau_educatif_id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_niveau_educatif_77e1fce5a5` CHECK (`sort_order` >= 0),
  CONSTRAINT `ck_niveau_educatif_5f015f328b` CHECK (`actif` IN (0,1)),
  CONSTRAINT `ck_niveau_educatif_26b307e490` CHECK (`parent_id` <> `niveau_educatif_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A2 : COLLECTION
CREATE TABLE `collection` (
  `collection_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `editeur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `slug` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `nom` VARCHAR(512) NOT NULL,
  `kind` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`collection_id`),
  CONSTRAINT `uq_collection_cd03861f0f` UNIQUE (`slug`),
  KEY `ix_collection_a7ec1680cf` (`editeur_id`),
  CONSTRAINT `ck_collection_660bf878a7` CHECK (REGEXP_LIKE(`collection_id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_collection_e667da9230` CHECK (`kind` IN ('collection','bundle')),
  CONSTRAINT `ck_collection_5f015f328b` CHECK (`actif` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A2 : TAG_AUTEUR
CREATE TABLE `tag_auteur` (
  `tag_auteur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `slug` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `nom` VARCHAR(512) NOT NULL,
  PRIMARY KEY (`tag_auteur_id`),
  CONSTRAINT `uq_tag_auteur_cd03861f0f` UNIQUE (`slug`),
  CONSTRAINT `ck_tag_auteur_24b48cb00f` CHECK (REGEXP_LIKE(`tag_auteur_id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A2 : OEUVRE_AUTEUR
CREATE TABLE `oeuvre_auteur` (
  `oeuvre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `auteur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ordre_affichage` BIGINT NULL,
  `role` TEXT NULL,
  PRIMARY KEY (`oeuvre_id`, `auteur_id`),
  KEY `ix_oeuvre_auteur_6e38b1ca93` (`auteur_id`),
  CONSTRAINT `ck_oeuvre_auteur_4b5304523e` CHECK (`ordre_affichage` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A2 : OEUVRE_CATEGORIE
CREATE TABLE `oeuvre_categorie` (
  `oeuvre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `categorie_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`oeuvre_id`, `categorie_id`),
  KEY `ix_oeuvre_categorie_52493cdd56` (`categorie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A2 : OEUVRE_LANGUE
CREATE TABLE `oeuvre_langue` (
  `oeuvre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `langue_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`oeuvre_id`, `langue_id`),
  KEY `ix_oeuvre_langue_4a0e2288d6` (`langue_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A2 : AUTEUR_TAG
CREATE TABLE `auteur_tag` (
  `auteur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `tag_auteur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`auteur_id`, `tag_auteur_id`),
  KEY `ix_auteur_tag_9c1eb4efa8` (`tag_auteur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A2 : SELECTION_EDITORIALE
CREATE TABLE `selection_editoriale` (
  `selection_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `libelle` VARCHAR(512) NOT NULL,
  `regle_ou_manuelle` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `parametres` JSON NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  `sort_order` BIGINT NOT NULL,
  PRIMARY KEY (`selection_id`),
  CONSTRAINT `uq_selection_editoriale_5694d08a2e` UNIQUE (`code`),
  CONSTRAINT `ck_selection_editoriale_3dbdb1dd81` CHECK (REGEXP_LIKE(`selection_id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_selection_editoriale_7ff922adf0` CHECK (`regle_ou_manuelle` IN ('filtre','manuelle')),
  CONSTRAINT `ck_selection_editoriale_52eeccf598` CHECK (JSON_TYPE(`parametres`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_selection_editoriale_5f015f328b` CHECK (`actif` IN (0,1)),
  CONSTRAINT `ck_selection_editoriale_77e1fce5a5` CHECK (`sort_order` >= 0),
  CONSTRAINT `ck_selection_editoriale_f5e1700504` CHECK ((`regle_ou_manuelle` = 'filtre' AND `parametres` IS NOT NULL) OR (`regle_ou_manuelle` = 'manuelle' AND `parametres` IS NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A2 : SELECTION_OEUVRE
CREATE TABLE `selection_oeuvre` (
  `selection_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `oeuvre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sort_order` BIGINT NOT NULL,
  `ajoute_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`selection_id`, `oeuvre_id`),
  KEY `ix_selection_oeuvre_a3fad59948` (`oeuvre_id`),
  CONSTRAINT `ck_selection_oeuvre_77e1fce5a5` CHECK (`sort_order` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A2 : MEDIA_CATALOGUE
CREATE TABLE `media_catalogue` (
  `media_catalogue_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `oeuvre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `edition_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `type` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_publique` VARCHAR(1024) NOT NULL,
  `texte_alternatif` TEXT NULL,
  `ordre` BIGINT NOT NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  `retire_a` DATETIME(6) NULL,
  `volume_apercu` DECIMAL(20,8) NULL,
  `unite_volume` TEXT NULL,
  PRIMARY KEY (`media_catalogue_id`),
  KEY `ix_media_catalogue_d7670e9f8c` (`oeuvre_id`, `actif`, `ordre`),
  KEY `ix_media_catalogue_69ce383f06` (`edition_id`, `actif`, `ordre`),
  CONSTRAINT `ck_media_catalogue_b0e4af4a70` CHECK (REGEXP_LIKE(`media_catalogue_id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_media_catalogue_4a076dcffc` CHECK (`type` IN ('couverture','galerie','apercu')),
  CONSTRAINT `ck_media_catalogue_19a0a5b563` CHECK (`ordre` >= 0),
  CONSTRAINT `ck_media_catalogue_5f015f328b` CHECK (`actif` IN (0,1)),
  CONSTRAINT `ck_media_catalogue_2ef35c2aa2` CHECK ((`oeuvre_id` IS NOT NULL) + (`edition_id` IS NOT NULL) = 1),
  CONSTRAINT `ck_media_catalogue_a7078b6de2` CHECK ((`type` = 'apercu' AND `volume_apercu` IS NOT NULL AND `volume_apercu` > 0 AND `unite_volume` IS NOT NULL) OR (`type` <> 'apercu' AND `volume_apercu` IS NULL AND `unite_volume` IS NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A2 : AVIS
CREATE TABLE `avis` (
  `avis_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `oeuvre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `note` BIGINT NOT NULL,
  `corps` TEXT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `soumis_a` DATETIME(6) NOT NULL,
  `moderateur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `modere_a` DATETIME(6) NULL,
  `motif_rejet` TEXT NULL,
  `uq_avis_non_rejete` TINYINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN `statut` IN ('pending','published') THEN 1 ELSE NULL END) VIRTUAL,
  PRIMARY KEY (`avis_id`),
  CONSTRAINT `uq_avis_45b94da625` UNIQUE (`compte_id`, `oeuvre_id`, `uq_avis_non_rejete`),
  KEY `ix_avis_4c018e4b20` (`oeuvre_id`, `statut`, `soumis_a`),
  KEY `ix_avis_87fbcc7c32` (`moderateur_compte_id`),
  CONSTRAINT `ck_avis_26d4aa00bc` CHECK (REGEXP_LIKE(`avis_id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_avis_bc10eb8957` CHECK (`note` >= 0),
  CONSTRAINT `ck_avis_b8145a66a5` CHECK (`statut` IN ('pending','published','rejected')),
  CONSTRAINT `ck_avis_c53e387a2c` CHECK (`note` BETWEEN 1 AND 5),
  CONSTRAINT `ck_avis_3594afcdc4` CHECK ((`statut` = 'pending' AND `modere_a` IS NULL AND `moderateur_compte_id` IS NULL AND `motif_rejet` IS NULL) OR (`statut` = 'published' AND `modere_a` IS NOT NULL AND `moderateur_compte_id` IS NOT NULL AND `motif_rejet` IS NULL) OR (`statut` = 'rejected' AND `modere_a` IS NOT NULL AND `moderateur_compte_id` IS NOT NULL AND `motif_rejet` IS NOT NULL AND CHAR_LENGTH(TRIM(`motif_rejet`)) > 0)),
  CONSTRAINT `ck_avis_cebdf2f843` CHECK (`modere_a` >= `soumis_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : jetons_panier_anonyme
CREATE TABLE `jetons_panier_anonyme` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `empreinte_reference` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `expire_a` DATETIME(6) NOT NULL,
  `revoque_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_jetons_panier_anonyme_e578af1c9f` UNIQUE (`empreinte_reference`),
  CONSTRAINT `ck_jetons_panier_anonyme_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_jetons_panier_anonyme_f3e549fe05` CHECK (`expire_a` > `cree_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : paniers
CREATE TABLE `paniers` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `jeton_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `devise` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `maj_a` DATETIME(6) NOT NULL,
  `uq_panier_actif` TINYINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN `statut` = 'actif' THEN 1 ELSE NULL END) VIRTUAL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_paniers_b5b6647a13` UNIQUE (`jeton_id`),
  CONSTRAINT `uq_paniers_ae99e8a7f5` UNIQUE (`compte_id`, `uq_panier_actif`),
  CONSTRAINT `uq_paniers_73595fbbf9` UNIQUE (`id`, `devise`),
  CONSTRAINT `uq_paniers_a6e60fd4c7` UNIQUE (`id`, `compte_id`),
  CONSTRAINT `ck_paniers_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_paniers_dc389a0ff5` CHECK (`devise` IN ('CDF','USD')),
  CONSTRAINT `ck_paniers_338973dd04` CHECK (`statut` IN ('actif','converti','abandonne')),
  CONSTRAINT `ck_paniers_fadfd6dd0a` CHECK ((`compte_id` IS NOT NULL) + (`jeton_id` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : lignes_panier
CREATE TABLE `lignes_panier` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `panier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `offre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `quantite` BIGINT NOT NULL,
  `prix_unitaire_affiche` DECIMAL(20,4) NOT NULL,
  `devise` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ajoute_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_lignes_panier_c709096419` UNIQUE (`panier_id`, `offre_id`),
  KEY `ix_lignes_panier_e96b881fdb` (`panier_id`, `devise`),
  KEY `ix_lignes_panier_0d7af68203` (`offre_id`),
  CONSTRAINT `ck_lignes_panier_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_lignes_panier_7cd005528e` CHECK (`quantite` > 0),
  CONSTRAINT `ck_lignes_panier_cdea21c2ca` CHECK (`prix_unitaire_affiche` >= 0),
  CONSTRAINT `ck_lignes_panier_dc389a0ff5` CHECK (`devise` IN ('CDF','USD'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : fusions_panier
CREATE TABLE `fusions_panier` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `panier_source_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `panier_cible_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cle_idempotence` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resultat` JSON NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `termine_a` DATETIME(6) NULL,
  `uq_fusion_ouverte` TINYINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN `statut` IN ('en_cours','a_corriger') THEN 1 ELSE NULL END) VIRTUAL,
  `uq_fusion_finale` TINYINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN `statut` = 'terminee' THEN 1 ELSE NULL END) VIRTUAL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_fusions_panier_9f407e61c3` UNIQUE (`cle_idempotence`),
  CONSTRAINT `uq_fusions_panier_50c5a91ab7` UNIQUE (`panier_source_id`, `uq_fusion_ouverte`),
  CONSTRAINT `uq_fusions_panier_c13e44a7b8` UNIQUE (`panier_source_id`, `uq_fusion_finale`),
  KEY `ix_fusions_panier_cefd3ffaa3` (`panier_cible_id`, `compte_id`),
  KEY `ix_fusions_panier_940f1f0c40` (`compte_id`),
  CONSTRAINT `ck_fusions_panier_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_fusions_panier_e8d346559d` CHECK (`statut` IN ('en_cours','terminee','a_corriger')),
  CONSTRAINT `ck_fusions_panier_57a028ab8d` CHECK (JSON_TYPE(`resultat`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_fusions_panier_38accbffa0` CHECK (`panier_source_id` <> `panier_cible_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : unites_stock
CREATE TABLE `unites_stock` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `vendeur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `quantite_physique` BIGINT NOT NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_unites_stock_52367a6622` UNIQUE (`reference`),
  KEY `ix_unites_stock_59710ce746` (`vendeur_id`),
  CONSTRAINT `ck_unites_stock_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_unites_stock_20b8a2a157` CHECK (`quantite_physique` >= 0),
  CONSTRAINT `ck_unites_stock_5f015f328b` CHECK (`actif` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : offres_stock
CREATE TABLE `offres_stock` (
  `offre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `unite_stock_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`offre_id`),
  KEY `ix_offres_stock_d632d23af0` (`unite_stock_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : reservations_stock
CREATE TABLE `reservations_stock` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `unite_stock_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `quantite` BIGINT NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reserve_a` DATETIME(6) NOT NULL,
  `expire_a` DATETIME(6) NOT NULL,
  `termine_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_reservations_stock_8f8ae1d06b` UNIQUE (`ligne_commande_id`, `unite_stock_id`),
  KEY `ix_reservations_stock_abea4b1789` (`unite_stock_id`, `statut`, `expire_a`),
  CONSTRAINT `ck_reservations_stock_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_reservations_stock_7cd005528e` CHECK (`quantite` > 0),
  CONSTRAINT `ck_reservations_stock_a09b18f747` CHECK (`statut` IN ('reservee','consommee','liberee','expiree')),
  CONSTRAINT `ck_reservations_stock_35675e7998` CHECK (`expire_a` > `reserve_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : codes_promo
CREATE TABLE `codes_promo` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_codes_promo_5694d08a2e` UNIQUE (`code`),
  CONSTRAINT `ck_codes_promo_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_codes_promo_5f015f328b` CHECK (`actif` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : versions_code_promo
CREATE TABLE `versions_code_promo` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code_promo_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero_version` BIGINT NOT NULL,
  `type` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `valeur` DECIMAL(20,8) NOT NULL,
  `devise` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `valid_from` DATETIME(6) NULL,
  `valid_to` DATETIME(6) NULL,
  `min_sous_total` DECIMAL(20,4) NULL,
  `plafond_usage` BIGINT NULL,
  `plafond_par_compte` BIGINT NULL,
  `regles_cumul_exclusion` JSON NOT NULL,
  `regle_restitution_quota` JSON NOT NULL,
  `regle_arrondi` JSON NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_versions_code_promo_19d429f37e` UNIQUE (`code_promo_id`, `numero_version`),
  CONSTRAINT `ck_versions_code_promo_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_versions_code_promo_95f3959b4a` CHECK (`numero_version` > 0),
  CONSTRAINT `ck_versions_code_promo_a1006e0c90` CHECK (`type` IN ('percent','fixed')),
  CONSTRAINT `ck_versions_code_promo_dc389a0ff5` CHECK (`devise` IN ('CDF','USD')),
  CONSTRAINT `ck_versions_code_promo_7e10d25e00` CHECK (`min_sous_total` >= 0),
  CONSTRAINT `ck_versions_code_promo_ebd6715133` CHECK (`plafond_usage` > 0),
  CONSTRAINT `ck_versions_code_promo_5b72b0b6bb` CHECK (`plafond_par_compte` > 0),
  CONSTRAINT `ck_versions_code_promo_22bd7b0f04` CHECK (JSON_TYPE(`regles_cumul_exclusion`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_versions_code_promo_2e8a70fe3a` CHECK (JSON_TYPE(`regle_restitution_quota`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_versions_code_promo_9eb2cbb07b` CHECK (JSON_TYPE(`regle_arrondi`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_versions_code_promo_3c37654332` CHECK (`valeur` >= 0),
  CONSTRAINT `ck_versions_code_promo_e3a272551c` CHECK (`type` <> 'percent' OR `valeur` <= 100),
  CONSTRAINT `ck_versions_code_promo_3ca0975d3c` CHECK ((`type` <> 'fixed' AND `min_sous_total` IS NULL) OR `devise` IS NOT NULL),
  CONSTRAINT `ck_versions_code_promo_bb2e3c9c0b` CHECK (`valid_to` > `valid_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : formats_promo
CREATE TABLE `formats_promo` (
  `version_promo_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `format_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`version_promo_id`, `format_id`),
  KEY `ix_formats_promo_5532d2348c` (`format_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : applications_promo
CREATE TABLE `applications_promo` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_promo_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `remise_calculee` DECIMAL(20,4) NOT NULL,
  `ordre_application` BIGINT NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reserve_a` DATETIME(6) NOT NULL,
  `expire_a` DATETIME(6) NULL,
  `consomme_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_applications_promo_144373b76d` UNIQUE (`commande_id`, `version_promo_id`),
  KEY `ix_applications_promo_81e29b0b9f` (`version_promo_id`, `statut`, `expire_a`),
  KEY `ix_applications_promo_f489472060` (`compte_id`, `version_promo_id`, `statut`),
  KEY `ix_applications_promo_35a9acb36a` (`commande_id`, `compte_id`),
  CONSTRAINT `ck_applications_promo_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_applications_promo_f35494d766` CHECK (`remise_calculee` >= 0),
  CONSTRAINT `ck_applications_promo_dd103e41bb` CHECK (`ordre_application` >= 0),
  CONSTRAINT `ck_applications_promo_a09b18f747` CHECK (`statut` IN ('reservee','consommee','liberee','expiree')),
  CONSTRAINT `ck_applications_promo_35675e7998` CHECK (`expire_a` > `reserve_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : allocations_promo_ligne
CREATE TABLE `allocations_promo_ligne` (
  `application_promo_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `montant` DECIMAL(20,4) NOT NULL,
  PRIMARY KEY (`application_promo_id`, `ligne_commande_id`),
  KEY `ix_allocations_promo_ligne_3ff8abbe12` (`ligne_commande_id`),
  CONSTRAINT `ck_allocations_promo_ligne_828dec08ff` CHECK (`montant` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : restitutions_quota_promo
CREATE TABLE `restitutions_quota_promo` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `application_promo_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision_remboursement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `motif` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_restitutions_quota_promo_f43c31f657` UNIQUE (`application_promo_id`),
  KEY `ix_restitutions_quota_promo_f1014d9025` (`decision_remboursement_id`),
  CONSTRAINT `ck_restitutions_quota_promo_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : commandes
CREATE TABLE `commandes` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `cle_checkout` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_requete` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `devise` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `sous_total` DECIMAL(20,4) NOT NULL,
  `remise_total` DECIMAL(20,4) NOT NULL,
  `livraison_total` DECIMAL(20,4) NOT NULL,
  `taxe_total` DECIMAL(20,4) NOT NULL,
  `grand_total` DECIMAL(20,4) NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `expire_a` DATETIME(6) NOT NULL,
  `confirme_a` DATETIME(6) NULL,
  `paye_a` DATETIME(6) NULL,
  `annule_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_commandes_52367a6622` UNIQUE (`reference`),
  CONSTRAINT `uq_commandes_aaea54b3e5` UNIQUE (`compte_id`, `cle_checkout`),
  CONSTRAINT `uq_commandes_a6e60fd4c7` UNIQUE (`id`, `compte_id`),
  CONSTRAINT `uq_commandes_73595fbbf9` UNIQUE (`id`, `devise`),
  KEY `ix_commandes_b02ee09556` (`compte_id`, `cree_a`, `id`),
  KEY `ix_commandes_2488678d6d` (`statut`, `expire_a`),
  CONSTRAINT `ck_commandes_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_commandes_8609cb5bb0` CHECK (`statut` IN ('pending','paid','confirmed_without_payment','fulfilled','cancelled','expired','refunded','partially_refunded')),
  CONSTRAINT `ck_commandes_dc389a0ff5` CHECK (`devise` IN ('CDF','USD')),
  CONSTRAINT `ck_commandes_9667db69f7` CHECK (`sous_total` >= 0),
  CONSTRAINT `ck_commandes_4996fca832` CHECK (`remise_total` >= 0),
  CONSTRAINT `ck_commandes_d92b90ca89` CHECK (`livraison_total` >= 0),
  CONSTRAINT `ck_commandes_fa8588c189` CHECK (`taxe_total` >= 0),
  CONSTRAINT `ck_commandes_b392070c27` CHECK (`grand_total` >= 0),
  CONSTRAINT `ck_commandes_9b6eaf848a` CHECK (`remise_total` <= `sous_total`),
  CONSTRAINT `ck_commandes_590ef8b7a4` CHECK (`grand_total` = `sous_total` - `remise_total` + `livraison_total` + `taxe_total`),
  CONSTRAINT `ck_commandes_f3e549fe05` CHECK (`expire_a` > `cree_a`),
  CONSTRAINT `ck_commandes_bccbdff9bb` CHECK (`annule_a` >= `cree_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : lignes_commande
CREATE TABLE `lignes_commande` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `offre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `vendeur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `quantite` BIGINT NOT NULL,
  `titre_snap` VARCHAR(512) NOT NULL,
  `format_snap` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `condition_snap` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `modalite_snap` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `isbn_snap` TEXT NULL,
  `vendeur_identite_snap` JSON NOT NULL,
  `prix_unitaire` DECIMAL(20,4) NOT NULL,
  `brut_ligne` DECIMAL(20,4) NOT NULL,
  `remise_ligne` DECIMAL(20,4) NOT NULL,
  `livraison_ligne` DECIMAL(20,4) NOT NULL,
  `taxe_ligne` DECIMAL(20,4) NOT NULL,
  `total_ligne` DECIMAL(20,4) NOT NULL,
  `devise` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type_ligne` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `regles_calcul_snap` JSON NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_lignes_commande_04f2d585b4` (`commande_id`, `vendeur_id`),
  KEY `ix_lignes_commande_7fb72176f0` (`commande_id`, `devise`),
  KEY `ix_lignes_commande_9eaa7978a9` (`offre_id`, `vendeur_id`),
  KEY `ix_lignes_commande_59710ce746` (`vendeur_id`),
  CONSTRAINT `ck_lignes_commande_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_lignes_commande_7cd005528e` CHECK (`quantite` > 0),
  CONSTRAINT `ck_lignes_commande_bfbdb36dcf` CHECK (`modalite_snap` IN ('achat')),
  CONSTRAINT `ck_lignes_commande_ef33576173` CHECK (JSON_TYPE(`vendeur_identite_snap`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_lignes_commande_67add16445` CHECK (`prix_unitaire` >= 0),
  CONSTRAINT `ck_lignes_commande_14042a26ee` CHECK (`brut_ligne` >= 0),
  CONSTRAINT `ck_lignes_commande_9c10c2e9a5` CHECK (`remise_ligne` >= 0),
  CONSTRAINT `ck_lignes_commande_96234ab1b5` CHECK (`livraison_ligne` >= 0),
  CONSTRAINT `ck_lignes_commande_a1b636ed87` CHECK (`taxe_ligne` >= 0),
  CONSTRAINT `ck_lignes_commande_07c98ffdb5` CHECK (`total_ligne` >= 0),
  CONSTRAINT `ck_lignes_commande_dc389a0ff5` CHECK (`devise` IN ('CDF','USD')),
  CONSTRAINT `ck_lignes_commande_25af5c4764` CHECK (`type_ligne` IN ('numerique','audio','papier')),
  CONSTRAINT `ck_lignes_commande_03ed9b67d2` CHECK (JSON_TYPE(`regles_calcul_snap`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_lignes_commande_e38756f7de` CHECK (`brut_ligne` = `quantite` * `prix_unitaire`),
  CONSTRAINT `ck_lignes_commande_f2fcda4b00` CHECK (`remise_ligne` <= `brut_ligne`),
  CONSTRAINT `ck_lignes_commande_fe8ee13a56` CHECK (`total_ligne` = `brut_ligne` - `remise_ligne` + `livraison_ligne` + `taxe_ligne`),
  CONSTRAINT `ck_lignes_commande_5a483af0c6` CHECK (`type_ligne` = 'papier' OR `quantite` = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : adresses_livraison_commande
CREATE TABLE `adresses_livraison_commande` (
  `commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `adresse_origine_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `nom_destinataire` VARCHAR(512) NOT NULL,
  `adresse` TEXT NOT NULL,
  `ville` VARCHAR(512) NOT NULL,
  `region` VARCHAR(512) NULL,
  `code_postal` VARCHAR(64) NULL,
  `pays` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `telephone` VARCHAR(64) NULL,
  `instructions` TEXT NULL,
  PRIMARY KEY (`commande_id`),
  KEY `ix_adresses_livraison_commande_f5b050879a` (`adresse_origine_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : identites_facturation_commande
CREATE TABLE `identites_facturation_commande` (
  `commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `nom_identite` VARCHAR(512) NOT NULL,
  `adresse` TEXT NOT NULL,
  `ville` VARCHAR(512) NOT NULL,
  `pays` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `autres_mentions` JSON NULL,
  PRIMARY KEY (`commande_id`),
  CONSTRAINT `ck_identites_facturation_commande_45d80754db` CHECK (JSON_TYPE(`autres_mentions`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : prestataires_paiement
CREATE TABLE `prestataires_paiement` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `nom` VARCHAR(512) NOT NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_prestataires_paiement_5694d08a2e` UNIQUE (`code`),
  CONSTRAINT `ck_prestataires_paiement_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_prestataires_paiement_5f015f328b` CHECK (`actif` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : configurations_encaissement
CREATE TABLE `configurations_encaissement` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `prestataire_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_compte_marchand` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `environnement` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `pays` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `priorite` BIGINT NOT NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  `reference_secrets` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_configurations_encaissement_17115b9bf0` UNIQUE (`prestataire_id`, `reference_compte_marchand`, `environnement`),
  CONSTRAINT `ck_configurations_encaissement_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_configurations_encaissement_1365d77a69` CHECK (`environnement` IN ('sandbox','live')),
  CONSTRAINT `ck_configurations_encaissement_3566ebc26e` CHECK (`priorite` >= 0),
  CONSTRAINT `ck_configurations_encaissement_5f015f328b` CHECK (`actif` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : capacites_encaissement
CREATE TABLE `capacites_encaissement` (
  `configuration_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `moyen` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `devise` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`configuration_id`, `moyen`, `devise`),
  CONSTRAINT `ck_capacites_encaissement_dc389a0ff5` CHECK (`devise` IN ('CDF','USD'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : paiements
CREATE TABLE `paiements` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `configuration_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ref_interne` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `cle_initiation` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_intention` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `montant` DECIMAL(20,4) NOT NULL,
  `devise` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `moyen` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ref_externe` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  `initie_a` DATETIME(6) NOT NULL,
  `confirme_a` DATETIME(6) NULL,
  `expire_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_paiements_b8ffdb38cb` UNIQUE (`ref_interne`),
  CONSTRAINT `uq_paiements_5869782377` UNIQUE (`configuration_id`, `cle_initiation`),
  CONSTRAINT `uq_paiements_3093a88ffc` UNIQUE (`configuration_id`, `ref_externe`),
  CONSTRAINT `uq_paiements_df567f8417` UNIQUE (`id`, `configuration_id`),
  CONSTRAINT `uq_paiements_73595fbbf9` UNIQUE (`id`, `devise`),
  CONSTRAINT `uq_paiements_f7e0bfaedc` UNIQUE (`id`, `commande_id`, `devise`),
  KEY `ix_paiements_3ebd612a25` (`configuration_id`, `moyen`, `devise`),
  KEY `ix_paiements_5162febf56` (`commande_id`, `statut`),
  KEY `ix_paiements_da4bf8316f` (`statut`, `initie_a`),
  KEY `ix_paiements_7fb72176f0` (`commande_id`, `devise`),
  CONSTRAINT `ck_paiements_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_paiements_828dec08ff` CHECK (`montant` >= 0),
  CONSTRAINT `ck_paiements_dc389a0ff5` CHECK (`devise` IN ('CDF','USD')),
  CONSTRAINT `ck_paiements_e26cd274ef` CHECK (`statut` IN ('pending','unknown','succeeded','failed','cancelled','expired')),
  CONSTRAINT `ck_paiements_1924e25a70` CHECK (`montant` > 0),
  CONSTRAINT `ck_paiements_c4a58041d3` CHECK (`statut` <> 'succeeded' OR `confirme_a` IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : evenements_psp
CREATE TABLE `evenements_psp` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `configuration_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `paiement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `ref_evenement` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  `cle_deduplication` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `type` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `recu_a` DATETIME(6) NOT NULL,
  `authenticite_verifiee` TINYINT UNSIGNED NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `payload_reference` TEXT NULL,
  `correlation` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_evenements_psp_f25d55da2d` UNIQUE (`configuration_id`, `cle_deduplication`),
  CONSTRAINT `uq_evenements_psp_b01a19ade8` UNIQUE (`configuration_id`, `ref_evenement`),
  KEY `ix_evenements_psp_f7db5d9425` (`statut`, `recu_a`),
  KEY `ix_evenements_psp_4e32fc5b03` (`paiement_id`, `configuration_id`),
  CONSTRAINT `ck_evenements_psp_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_evenements_psp_aec4eac5a5` CHECK (`authenticite_verifiee` IN (0,1)),
  CONSTRAINT `ck_evenements_psp_07422a406f` CHECK (`statut` IN ('recu','traite','a_rapprocher','rejete'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : transactions_paiement
CREATE TABLE `transactions_paiement` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `paiement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `remboursement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `evenement_psp_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `nature` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_mouvement` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  `cle_mouvement` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `montant` DECIMAL(20,4) NOT NULL,
  `devise` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resultat` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `horodatage` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_transactions_paiement_867f624ec2` UNIQUE (`paiement_id`, `cle_mouvement`),
  KEY `ix_transactions_paiement_ca2d2a0b7f` (`paiement_id`, `devise`),
  KEY `ix_transactions_paiement_dbfc368150` (`remboursement_id`, `paiement_id`),
  KEY `ix_transactions_paiement_ddb2f596e8` (`evenement_psp_id`),
  CONSTRAINT `ck_transactions_paiement_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_transactions_paiement_ab5b85affd` CHECK (`nature` IN ('encaissement','remboursement','contestation')),
  CONSTRAINT `ck_transactions_paiement_828dec08ff` CHECK (`montant` >= 0),
  CONSTRAINT `ck_transactions_paiement_dc389a0ff5` CHECK (`devise` IN ('CDF','USD')),
  CONSTRAINT `ck_transactions_paiement_1924e25a70` CHECK (`montant` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : anomalies_paiement
CREATE TABLE `anomalies_paiement` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `paiement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `evenement_psp_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `type` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `montant` DECIMAL(20,4) NULL,
  `motif` TEXT NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `resolue_a` DATETIME(6) NULL,
  `decision` JSON NULL,
  `decide_par_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `decide_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_anomalies_paiement_68d4d872e4` (`paiement_id`),
  KEY `ix_anomalies_paiement_ddb2f596e8` (`evenement_psp_id`),
  KEY `ix_anomalies_paiement_a11d9493dc` (`decide_par_id`),
  CONSTRAINT `ck_anomalies_paiement_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_anomalies_paiement_00fb44eb91` CHECK (`type` IN ('double_encaissement','succes_tardif','montant_incoherent','retour_inconnu','chargeback')),
  CONSTRAINT `ck_anomalies_paiement_3c6d484849` CHECK (`statut` IN ('ouverte','en_cours','resolue')),
  CONSTRAINT `ck_anomalies_paiement_828dec08ff` CHECK (`montant` >= 0),
  CONSTRAINT `ck_anomalies_paiement_3f9141b6e0` CHECK (JSON_TYPE(`decision`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : validations_reglement
CREATE TABLE `validations_reglement` (
  `commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `paiement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `mode` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `montant` DECIMAL(20,4) NOT NULL,
  `devise` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `valide_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`commande_id`),
  CONSTRAINT `uq_validations_reglement_68d4d872e4` UNIQUE (`paiement_id`),
  KEY `ix_validations_reglement_c7fba6138f` (`paiement_id`, `commande_id`, `devise`),
  KEY `ix_validations_reglement_7fb72176f0` (`commande_id`, `devise`),
  CONSTRAINT `ck_validations_reglement_c22480e76c` CHECK (`mode` IN ('encaissement','total_nul')),
  CONSTRAINT `ck_validations_reglement_828dec08ff` CHECK (`montant` >= 0),
  CONSTRAINT `ck_validations_reglement_dc389a0ff5` CHECK (`devise` IN ('CDF','USD')),
  CONSTRAINT `ck_validations_reglement_cc3b807961` CHECK ((`mode` = 'total_nul' AND `paiement_id` IS NULL AND `montant` = 0) OR (`mode` = 'encaissement' AND `paiement_id` IS NOT NULL AND `montant` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : besoins_preparation
CREATE TABLE `besoins_preparation` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `quantite` BIGINT NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `termine_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_besoins_preparation_3ff8abbe12` UNIQUE (`ligne_commande_id`),
  CONSTRAINT `ck_besoins_preparation_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_besoins_preparation_2f687679cf` CHECK (`statut` IN ('a_traiter','en_cours','livre','annule')),
  CONSTRAINT `ck_besoins_preparation_7cd005528e` CHECK (`quantite` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : mandats_facturation
CREATE TABLE `mandats_facturation` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `vendeur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signe_le` DATE NOT NULL,
  `valide_jusqua` DATETIME(6) NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_preuve` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_mandats_facturation_f03e110afc` UNIQUE (`id`, `vendeur_id`),
  KEY `ix_mandats_facturation_59710ce746` (`vendeur_id`),
  CONSTRAINT `ck_mandats_facturation_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_mandats_facturation_65e1b2077e` CHECK (`statut` IN ('actif','expire','revoque'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : circuits_facturation_vendeur
CREATE TABLE `circuits_facturation_vendeur` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `vendeur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `mandat_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `mode` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  `valide_a` DATETIME(6) NULL,
  `reference_configuration` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_circuits_facturation_vendeur_f03e110afc` UNIQUE (`id`, `vendeur_id`),
  KEY `ix_circuits_facturation_vendeur_7ad420d99e` (`mandat_id`, `vendeur_id`),
  KEY `ix_circuits_facturation_vendeur_59710ce746` (`vendeur_id`),
  CONSTRAINT `ck_circuits_facturation_vendeur_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_circuits_facturation_vendeur_dde641231f` CHECK (`mode` IN ('ae_mandat','vendeur')),
  CONSTRAINT `ck_circuits_facturation_vendeur_5f015f328b` CHECK (`actif` IN (0,1)),
  CONSTRAINT `ck_circuits_facturation_vendeur_95d3b564f2` CHECK (`mode` <> 'ae_mandat' OR `mandat_id` IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : regles_remuneration
CREATE TABLE `regles_remuneration` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `version` BIGINT NOT NULL,
  `assiette` JSON NOT NULL,
  `formule` JSON NOT NULL,
  `devise` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `effet_debut` DATETIME(6) NOT NULL,
  `effet_fin` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_regles_remuneration_f5b433d863` UNIQUE (`code`, `version`),
  CONSTRAINT `ck_regles_remuneration_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_regles_remuneration_97a58d3a38` CHECK (`version` > 0),
  CONSTRAINT `ck_regles_remuneration_e066b9c9fc` CHECK (JSON_TYPE(`assiette`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_regles_remuneration_f9d0b75eef` CHECK (JSON_TYPE(`formule`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_regles_remuneration_dc389a0ff5` CHECK (`devise` IN ('CDF','USD')),
  CONSTRAINT `ck_regles_remuneration_366c86cf82` CHECK (`effet_fin` > `effet_debut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : dossiers_facturation
CREATE TABLE `dossiers_facturation` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `vendeur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `circuit_checkout_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `regle_remuneration_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `conditions_commission_snap` JSON NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `derniere_erreur` TEXT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_dossiers_facturation_04f2d585b4` UNIQUE (`commande_id`, `vendeur_id`),
  KEY `ix_dossiers_facturation_11354efbca` (`circuit_checkout_id`, `vendeur_id`),
  KEY `ix_dossiers_facturation_59710ce746` (`vendeur_id`),
  KEY `ix_dossiers_facturation_5e0edcf951` (`regle_remuneration_id`),
  CONSTRAINT `ck_dossiers_facturation_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_dossiers_facturation_664b3582d3` CHECK (JSON_TYPE(`conditions_commission_snap`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_dossiers_facturation_e3102feba1` CHECK (`statut` IN ('a_emettre','attente_vendeur','partiellement_emis','emis','en_erreur'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : factures_vente
CREATE TABLE `factures_vente` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_facturation_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `mandat_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `numero` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `serie` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `emetteur_type` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `emetteur_cle_snap` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `identite_vendeur_snap` JSON NOT NULL,
  `identite_client_snap` JSON NOT NULL,
  `ht` DECIMAL(20,4) NOT NULL,
  `taxes` DECIMAL(20,4) NOT NULL,
  `ttc` DECIMAL(20,4) NOT NULL,
  `devise` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `emise_a` DATETIME(6) NOT NULL,
  `reference_document` VARCHAR(1024) NULL,
  `empreinte` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_factures_vente_812499081a` UNIQUE (`dossier_facturation_id`),
  CONSTRAINT `uq_factures_vente_e7d6741b79` UNIQUE (`emetteur_cle_snap`, `serie`, `numero`),
  KEY `ix_factures_vente_fd3ad1101b` (`mandat_id`),
  CONSTRAINT `ck_factures_vente_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_factures_vente_6ca83256c2` CHECK (`emetteur_type` IN ('vendeur','ae_mandat')),
  CONSTRAINT `ck_factures_vente_e5bd6df34f` CHECK (JSON_TYPE(`identite_vendeur_snap`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_factures_vente_6d16da4db4` CHECK (JSON_TYPE(`identite_client_snap`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_factures_vente_4f1cafa31b` CHECK (`ht` >= 0),
  CONSTRAINT `ck_factures_vente_d8438257d2` CHECK (`taxes` >= 0),
  CONSTRAINT `ck_factures_vente_f2bc459751` CHECK (`ttc` >= 0),
  CONSTRAINT `ck_factures_vente_dc389a0ff5` CHECK (`devise` IN ('CDF','USD')),
  CONSTRAINT `ck_factures_vente_6d8e408874` CHECK (`ttc` = `ht` + `taxes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : lignes_facture_vente
CREATE TABLE `lignes_facture_vente` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `facture_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `libelle_snap` TEXT NOT NULL,
  `quantite` BIGINT NOT NULL,
  `pu` DECIMAL(20,4) NOT NULL,
  `remise` DECIMAL(20,4) NOT NULL,
  `livraison` DECIMAL(20,4) NOT NULL,
  `taxes` DECIMAL(20,4) NOT NULL,
  `total` DECIMAL(20,4) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_lignes_facture_vente_3ff8abbe12` UNIQUE (`ligne_commande_id`),
  KEY `ix_lignes_facture_vente_2efab036bc` (`facture_id`),
  CONSTRAINT `ck_lignes_facture_vente_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_lignes_facture_vente_7cd005528e` CHECK (`quantite` > 0),
  CONSTRAINT `ck_lignes_facture_vente_676aa94782` CHECK (`pu` >= 0),
  CONSTRAINT `ck_lignes_facture_vente_9819357f10` CHECK (`remise` >= 0),
  CONSTRAINT `ck_lignes_facture_vente_6838779376` CHECK (`livraison` >= 0),
  CONSTRAINT `ck_lignes_facture_vente_d8438257d2` CHECK (`taxes` >= 0),
  CONSTRAINT `ck_lignes_facture_vente_9670051db7` CHECK (`total` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : factures_commission
CREATE TABLE `factures_commission` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_facturation_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `regle_remuneration_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `serie` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `emetteur_cle_snap` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `identite_ae_snap` JSON NOT NULL,
  `identite_vendeur_snap` JSON NOT NULL,
  `assiette_snap` JSON NOT NULL,
  `regle_snap` JSON NOT NULL,
  `commission` DECIMAL(20,4) NOT NULL,
  `frais` DECIMAL(20,4) NOT NULL,
  `taxes` DECIMAL(20,4) NOT NULL,
  `total` DECIMAL(20,4) NOT NULL,
  `devise` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `emise_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_factures_commission_812499081a` UNIQUE (`dossier_facturation_id`),
  CONSTRAINT `uq_factures_commission_e7d6741b79` UNIQUE (`emetteur_cle_snap`, `serie`, `numero`),
  KEY `ix_factures_commission_5e0edcf951` (`regle_remuneration_id`),
  CONSTRAINT `ck_factures_commission_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_factures_commission_4855a536df` CHECK (JSON_TYPE(`identite_ae_snap`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_factures_commission_e5bd6df34f` CHECK (JSON_TYPE(`identite_vendeur_snap`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_factures_commission_81a4c78782` CHECK (JSON_TYPE(`assiette_snap`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_factures_commission_1d8b498b8f` CHECK (JSON_TYPE(`regle_snap`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_factures_commission_1a674a03bb` CHECK (`commission` >= 0),
  CONSTRAINT `ck_factures_commission_ce46593951` CHECK (`frais` >= 0),
  CONSTRAINT `ck_factures_commission_d8438257d2` CHECK (`taxes` >= 0),
  CONSTRAINT `ck_factures_commission_9670051db7` CHECK (`total` >= 0),
  CONSTRAINT `ck_factures_commission_dc389a0ff5` CHECK (`devise` IN ('CDF','USD')),
  CONSTRAINT `ck_factures_commission_e65a6ea92f` CHECK (`total` = `commission` + `frais` + `taxes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : recus_paiement
CREATE TABLE `recus_paiement` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `paiement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `montant` DECIMAL(20,4) NOT NULL,
  `devise` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `emis_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_recus_paiement_68d4d872e4` UNIQUE (`paiement_id`),
  CONSTRAINT `uq_recus_paiement_52367a6622` UNIQUE (`reference`),
  CONSTRAINT `ck_recus_paiement_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_recus_paiement_828dec08ff` CHECK (`montant` >= 0),
  CONSTRAINT `ck_recus_paiement_dc389a0ff5` CHECK (`devise` IN ('CDF','USD'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : avoirs
CREATE TABLE `avoirs` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `facture_vente_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `facture_commission_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `decision_remboursement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `cle_emission` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `numero` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `serie` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `emetteur_cle_snap` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `montant` DECIMAL(20,4) NOT NULL,
  `devise` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` TEXT NOT NULL,
  `emis_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_avoirs_4d7904f990` UNIQUE (`cle_emission`),
  CONSTRAINT `uq_avoirs_e7d6741b79` UNIQUE (`emetteur_cle_snap`, `serie`, `numero`),
  KEY `ix_avoirs_3b530ef393` (`facture_vente_id`),
  KEY `ix_avoirs_eb7b937696` (`facture_commission_id`),
  KEY `ix_avoirs_f1014d9025` (`decision_remboursement_id`),
  CONSTRAINT `ck_avoirs_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_avoirs_828dec08ff` CHECK (`montant` >= 0),
  CONSTRAINT `ck_avoirs_dc389a0ff5` CHECK (`devise` IN ('CDF','USD')),
  CONSTRAINT `ck_avoirs_096890ea29` CHECK ((`facture_vente_id` IS NOT NULL) + (`facture_commission_id` IS NOT NULL) = 1),
  CONSTRAINT `ck_avoirs_1924e25a70` CHECK (`montant` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : lignes_avoir
CREATE TABLE `lignes_avoir` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `avoir_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_facture_vente_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `quantite` BIGINT NULL,
  `montant` DECIMAL(20,4) NOT NULL,
  `motif` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_lignes_avoir_42318f3fe1` (`avoir_id`),
  KEY `ix_lignes_avoir_49ef57d2af` (`ligne_facture_vente_id`),
  CONSTRAINT `ck_lignes_avoir_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_lignes_avoir_7cd005528e` CHECK (`quantite` > 0),
  CONSTRAINT `ck_lignes_avoir_828dec08ff` CHECK (`montant` >= 0),
  CONSTRAINT `ck_lignes_avoir_1924e25a70` CHECK (`montant` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : demandes_attribution_droit
CREATE TABLE `demandes_attribution_droit` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `offre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `cle_idempotence` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `origine` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `confirme_a` DATETIME(6) NULL,
  `reference_acquisition_a4_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_a4_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `licence_a4_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `resultat_metier` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `reference_reponse` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  `repondu_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_demandes_attribution_droit_3ff8abbe12` UNIQUE (`ligne_commande_id`),
  CONSTRAINT `uq_demandes_attribution_droit_9f407e61c3` UNIQUE (`cle_idempotence`),
  KEY `ix_demandes_attribution_droit_c4a8720abd` (`licence_a4_id`, `compte_id`, `offre_id`),
  KEY `ix_demandes_attribution_droit_90c3f66211` (`origine_a4_id`, `reference_acquisition_a4_id`, `licence_a4_id`),
  KEY `ix_demandes_attribution_droit_1b624651ed` (`statut`, `cree_a`),
  KEY `ix_demandes_attribution_droit_940f1f0c40` (`compte_id`),
  KEY `ix_demandes_attribution_droit_0d7af68203` (`offre_id`),
  KEY `ix_demandes_attribution_droit_2f6cd18f4f` (`reference_acquisition_a4_id`),
  CONSTRAINT `ck_demandes_attribution_droit_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_demandes_attribution_droit_37df1dd3c3` CHECK (`origine` IN ('achat','gratuit')),
  CONSTRAINT `ck_demandes_attribution_droit_f0d020bc4b` CHECK (`statut` IN ('a_transmettre','transmise','en_attente','confirmee','annulee','refusee','en_erreur')),
  CONSTRAINT `ck_demandes_attribution_droit_1eeb8605f8` CHECK ((`origine` = 'achat' AND `ligne_commande_id` IS NOT NULL) OR (`origine` = 'gratuit' AND `ligne_commande_id` IS NULL)),
  CONSTRAINT `ck_demandes_attribution_droit_9465a23029` CHECK (`origine_a4_id` IS NULL OR (`reference_acquisition_a4_id` IS NOT NULL AND `licence_a4_id` IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : dossiers_remboursement
CREATE TABLE `dossiers_remboursement` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `demandeur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_ouverture` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `initiateur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_systeme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `dossier_precedent_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `reference` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `motif` TEXT NOT NULL,
  `preuves_privees` JSON NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_dossiers_remboursement_52367a6622` UNIQUE (`reference`),
  CONSTRAINT `uq_dossiers_remboursement_29306169f2` UNIQUE (`id`, `commande_id`),
  KEY `ix_dossiers_remboursement_c1163a4d7f` (`commande_id`, `statut`, `cree_a`),
  KEY `ix_dossiers_remboursement_cfaf4f3494` (`dossier_precedent_id`, `commande_id`),
  KEY `ix_dossiers_remboursement_b9c128857f` (`demandeur_id`),
  KEY `ix_dossiers_remboursement_7fcda8b148` (`initiateur_compte_id`),
  CONSTRAINT `ck_dossiers_remboursement_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_dossiers_remboursement_24dd2b34c3` CHECK (`origine_ouverture` IN ('lecteur','conformite','interne')),
  CONSTRAINT `ck_dossiers_remboursement_b4467926af` CHECK (JSON_TYPE(`preuves_privees`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_dossiers_remboursement_b40cffdb81` CHECK (`statut` IN ('ouvert','en_examen','accepte','refuse','clos')),
  CONSTRAINT `ck_dossiers_remboursement_8164b11601` CHECK ((`initiateur_compte_id` IS NOT NULL) + (`origine_systeme` IS NOT NULL) = 1),
  CONSTRAINT `ck_dossiers_remboursement_083a774b75` CHECK (`origine_ouverture` <> 'lecteur' OR (`demandeur_id` IS NOT NULL AND `initiateur_compte_id` IS NOT NULL AND `demandeur_id` = `initiateur_compte_id`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : decisions_remboursement
CREATE TABLE `decisions_remboursement` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decideur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` TEXT NOT NULL,
  `montant_autorise` DECIMAL(20,4) NOT NULL,
  `devise` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `effet_droit` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decide_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_decisions_remboursement_0259b9fde9` UNIQUE (`dossier_id`),
  CONSTRAINT `uq_decisions_remboursement_73595fbbf9` UNIQUE (`id`, `devise`),
  KEY `ix_decisions_remboursement_d6d83fdc33` (`decideur_id`),
  CONSTRAINT `ck_decisions_remboursement_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_decisions_remboursement_51a8b41e0a` CHECK (`decision` IN ('acceptee','refusee')),
  CONSTRAINT `ck_decisions_remboursement_171a95b552` CHECK (`montant_autorise` >= 0),
  CONSTRAINT `ck_decisions_remboursement_dc389a0ff5` CHECK (`devise` IN ('CDF','USD')),
  CONSTRAINT `ck_decisions_remboursement_5889258d0d` CHECK (`effet_droit` IN ('aucun','revocation_lignes'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : lignes_decision_remboursement
CREATE TABLE `lignes_decision_remboursement` (
  `decision_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `montant_autorise` DECIMAL(20,4) NOT NULL,
  `quantite_annulee` BIGINT NULL,
  `effet_droit` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`decision_id`, `ligne_commande_id`),
  KEY `ix_lignes_decision_remboursement_3ff8abbe12` (`ligne_commande_id`),
  CONSTRAINT `ck_lignes_decision_remboursement_171a95b552` CHECK (`montant_autorise` >= 0),
  CONSTRAINT `ck_lignes_decision_remboursement_285fa209ee` CHECK (`quantite_annulee` > 0),
  CONSTRAINT `ck_lignes_decision_remboursement_6ee5d2b539` CHECK (`effet_droit` IN ('aucun','revocation'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : remboursements
CREATE TABLE `remboursements` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `paiement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `anomalie_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `cle_idempotence` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `ref_externe` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  `montant` DECIMAL(20,4) NOT NULL,
  `devise` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `demande_a` DATETIME(6) NOT NULL,
  `confirme_a` DATETIME(6) NULL,
  `configuration_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_remboursements_b44590de90` UNIQUE (`paiement_id`, `cle_idempotence`),
  CONSTRAINT `uq_remboursements_3093a88ffc` UNIQUE (`configuration_id`, `ref_externe`),
  CONSTRAINT `uq_remboursements_cc1db12470` UNIQUE (`id`, `paiement_id`),
  KEY `ix_remboursements_072b055964` (`paiement_id`, `statut`),
  KEY `ix_remboursements_92b7644a97` (`decision_id`, `statut`),
  KEY `ix_remboursements_ca2d2a0b7f` (`paiement_id`, `devise`),
  KEY `ix_remboursements_76233a4fa1` (`decision_id`, `devise`),
  KEY `ix_remboursements_4e32fc5b03` (`paiement_id`, `configuration_id`),
  KEY `ix_remboursements_28885fb289` (`anomalie_id`),
  CONSTRAINT `ck_remboursements_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_remboursements_828dec08ff` CHECK (`montant` >= 0),
  CONSTRAINT `ck_remboursements_dc389a0ff5` CHECK (`devise` IN ('CDF','USD')),
  CONSTRAINT `ck_remboursements_d34d1812ef` CHECK (`statut` IN ('pending','unknown','succeeded','failed','cancelled')),
  CONSTRAINT `ck_remboursements_0d81f40d17` CHECK ((`decision_id` IS NOT NULL) + (`anomalie_id` IS NOT NULL) = 1),
  CONSTRAINT `ck_remboursements_1924e25a70` CHECK (`montant` > 0),
  CONSTRAINT `ck_remboursements_c4a58041d3` CHECK (`statut` <> 'succeeded' OR `confirme_a` IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : allocations_remboursement
CREATE TABLE `allocations_remboursement` (
  `remboursement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `montant` DECIMAL(20,4) NOT NULL,
  `quantite_annulee` BIGINT NULL,
  `part_remise` DECIMAL(20,4) NOT NULL,
  `part_taxe` DECIMAL(20,4) NOT NULL,
  `part_livraison` DECIMAL(20,4) NOT NULL,
  PRIMARY KEY (`remboursement_id`, `ligne_commande_id`),
  KEY `ix_allocations_remboursement_3ff8abbe12` (`ligne_commande_id`),
  CONSTRAINT `ck_allocations_remboursement_828dec08ff` CHECK (`montant` >= 0),
  CONSTRAINT `ck_allocations_remboursement_285fa209ee` CHECK (`quantite_annulee` > 0),
  CONSTRAINT `ck_allocations_remboursement_713ac3c141` CHECK (`part_remise` >= 0),
  CONSTRAINT `ck_allocations_remboursement_b6f2c08f50` CHECK (`part_taxe` >= 0),
  CONSTRAINT `ck_allocations_remboursement_785a5f5220` CHECK (`part_livraison` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : demandes_revocation_droit
CREATE TABLE `demandes_revocation_droit` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `demande_attribution_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cle_idempotence` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `motif` TEXT NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_acquisition_a4_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_a4_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `licence_a4_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `resultat_metier` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `reference_reponse` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  `repondu_a` DATETIME(6) NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `confirme_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_demandes_revocation_droit_9f407e61c3` UNIQUE (`cle_idempotence`),
  CONSTRAINT `uq_demandes_revocation_droit_8e37ec5ac6` UNIQUE (`decision_id`, `demande_attribution_id`),
  KEY `ix_demandes_revocation_droit_90c3f66211` (`origine_a4_id`, `reference_acquisition_a4_id`, `licence_a4_id`),
  KEY `ix_demandes_revocation_droit_8d35524b10` (`demande_attribution_id`),
  KEY `ix_demandes_revocation_droit_2f6cd18f4f` (`reference_acquisition_a4_id`),
  KEY `ix_demandes_revocation_droit_444f43db70` (`licence_a4_id`),
  CONSTRAINT `ck_demandes_revocation_droit_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_demandes_revocation_droit_de142f47a1` CHECK (`statut` IN ('a_transmettre','transmise','en_attente','confirmee','refusee','en_erreur')),
  CONSTRAINT `ck_demandes_revocation_droit_9465a23029` CHECK (`origine_a4_id` IS NULL OR (`reference_acquisition_a4_id` IS NOT NULL AND `licence_a4_id` IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : receptions_compensation
CREATE TABLE `receptions_compensation` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `producteur` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_demande` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `demande_conformite_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `empreinte_intention` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `dossier_reutilisation_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` TEXT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `repondu_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_receptions_compensation_534a0d6ba1` UNIQUE (`demande_conformite_id`),
  CONSTRAINT `uq_receptions_compensation_ff9d8f0b15` UNIQUE (`producteur`, `reference_demande`),
  KEY `ix_receptions_compensation_1c8b37225a` (`dossier_id`, `commande_id`),
  KEY `ix_receptions_compensation_3202030bfd` (`dossier_reutilisation_id`, `commande_id`),
  KEY `ix_receptions_compensation_94c4e6db2b` (`commande_id`),
  CONSTRAINT `ck_receptions_compensation_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_receptions_compensation_50fdc54f8d` CHECK (`statut` IN ('en_examen','liee','refusee'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A3 : decisions_rattachement_compensation
CREATE TABLE `decisions_rattachement_compensation` (
  `reception_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decideur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` TEXT NOT NULL,
  `preuve_equivalence` JSON NOT NULL,
  `decide_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`reception_id`),
  KEY `ix_decisions_rattachement_compensation_0259b9fde9` (`dossier_id`),
  KEY `ix_decisions_rattachement_compensation_d6d83fdc33` (`decideur_id`),
  CONSTRAINT `ck_decisions_rattachement_compensation_a4e6545321` CHECK (JSON_TYPE(`preuve_equivalence`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : licences_contenu
CREATE TABLE `licences_contenu` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `offre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_licences_contenu_6880210b9a` UNIQUE (`compte_id`, `offre_id`),
  CONSTRAINT `uq_licences_contenu_de6658c5d7` UNIQUE (`id`, `compte_id`, `offre_id`),
  CONSTRAINT `uq_licences_contenu_a6e60fd4c7` UNIQUE (`id`, `compte_id`),
  KEY `ix_licences_contenu_0d7af68203` (`offre_id`),
  CONSTRAINT `ck_licences_contenu_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : references_acquisition
CREATE TABLE `references_acquisition` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `producteur` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_demande` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `cle_idempotence` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  `empreinte_intention` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `cree_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_references_acquisition_ff9d8f0b15` UNIQUE (`producteur`, `reference_demande`),
  CONSTRAINT `uq_references_acquisition_965fa85f85` UNIQUE (`producteur`, `cle_idempotence`),
  CONSTRAINT `ck_references_acquisition_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : origines_acquisition
CREATE TABLE `origines_acquisition` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `licence_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_acquisition_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `origine` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `debut` DATETIME(6) NULL,
  `fin` DATETIME(6) NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `attribue_a` DATETIME(6) NULL,
  `revoque_a` DATETIME(6) NULL,
  `motif` TEXT NULL,
  `reference_preuve` JSON NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_origines_acquisition_967027099e` UNIQUE (`reference_acquisition_id`),
  CONSTRAINT `uq_origines_acquisition_3ff8abbe12` UNIQUE (`ligne_commande_id`),
  CONSTRAINT `uq_origines_acquisition_b434481b84` UNIQUE (`id`, `reference_acquisition_id`, `licence_id`),
  CONSTRAINT `uq_origines_acquisition_bce2fa9e9e` UNIQUE (`id`, `licence_id`),
  CONSTRAINT `uq_origines_acquisition_0a81dee31f` UNIQUE (`id`, `reference_acquisition_id`),
  KEY `ix_origines_acquisition_612e7cec9f` (`licence_id`, `statut`, `fin`),
  CONSTRAINT `ck_origines_acquisition_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_origines_acquisition_5886f95f05` CHECK (`origine` IN ('achat','gratuit','attribution')),
  CONSTRAINT `ck_origines_acquisition_3b47a6c24b` CHECK (`statut` IN ('en_attente','active','revoked','expired','refusee')),
  CONSTRAINT `ck_origines_acquisition_513bf9b33a` CHECK (JSON_TYPE(`reference_preuve`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_origines_acquisition_d3749aa9da` CHECK ((`origine` = 'achat' AND `ligne_commande_id` IS NOT NULL) OR (`origine` <> 'achat' AND `ligne_commande_id` IS NULL)),
  CONSTRAINT `ck_origines_acquisition_a8092fb9c2` CHECK (`statut` <> 'revoked' OR `revoque_a` IS NOT NULL),
  CONSTRAINT `ck_origines_acquisition_e2d2138eff` CHECK (`fin` > `debut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : evenements_licence
CREATE TABLE `evenements_licence` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `licence_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `origine_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `type` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` TEXT NOT NULL,
  `date` DATETIME(6) NOT NULL,
  `auteur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_systeme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `correlation` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `reference_decision` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  PRIMARY KEY (`id`),
  KEY `ix_evenements_licence_5bd44e52f5` (`origine_id`, `licence_id`),
  KEY `ix_evenements_licence_97d325fd54` (`licence_id`),
  KEY `ix_evenements_licence_1c7f79acb8` (`auteur_compte_id`),
  CONSTRAINT `ck_evenements_licence_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_evenements_licence_34b91e2384` CHECK ((`auteur_compte_id` IS NOT NULL) + (`origine_systeme` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : instructions_revocation
CREATE TABLE `instructions_revocation` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `producteur` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_instruction` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `cle_idempotence` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `reference_acquisition_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `origine_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `motif` TEXT NOT NULL,
  `reference_decision` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `effet_a` DATETIME(6) NOT NULL,
  `recu_a` DATETIME(6) NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resultat` JSON NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_instructions_revocation_a648930d1f` UNIQUE (`producteur`, `reference_instruction`),
  CONSTRAINT `uq_instructions_revocation_965fa85f85` UNIQUE (`producteur`, `cle_idempotence`),
  KEY `ix_instructions_revocation_9d82fc4ac3` (`reference_acquisition_id`, `statut`, `effet_a`),
  KEY `ix_instructions_revocation_b811844f7c` (`origine_id`, `reference_acquisition_id`),
  CONSTRAINT `ck_instructions_revocation_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_instructions_revocation_18ab015a94` CHECK (`statut` IN ('recue','appliquee','rejetee')),
  CONSTRAINT `ck_instructions_revocation_57a028ab8d` CHECK (JSON_TYPE(`resultat`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : blocages_acces
CREATE TABLE `blocages_acces` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `licence_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `cause` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `producteur` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_source` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `motif` TEXT NOT NULL,
  `debut` DATETIME(6) NOT NULL,
  `fin` DATETIME(6) NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `leve_a` DATETIME(6) NULL,
  `auteur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_systeme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_blocages_acces_96802877b6` UNIQUE (`producteur`, `reference_source`),
  KEY `ix_blocages_acces_4f43cf2a73` (`compte_id`, `debut`, `fin`),
  KEY `ix_blocages_acces_ea09f7ca77` (`licence_id`, `debut`, `fin`),
  KEY `ix_blocages_acces_1c7f79acb8` (`auteur_compte_id`),
  CONSTRAINT `ck_blocages_acces_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_blocages_acces_ca93c8c7c2` CHECK (`cause` IN ('compte','securite','decision')),
  CONSTRAINT `ck_blocages_acces_5302fcba7b` CHECK (`statut` IN ('actif','leve')),
  CONSTRAINT `ck_blocages_acces_cfbcbb1fa5` CHECK ((`compte_id` IS NOT NULL) + (`licence_id` IS NOT NULL) = 1),
  CONSTRAINT `ck_blocages_acces_34b91e2384` CHECK ((`auteur_compte_id` IS NOT NULL) + (`origine_systeme` IS NOT NULL) = 1),
  CONSTRAINT `ck_blocages_acces_c903345710` CHECK (`statut` <> 'leve' OR `leve_a` IS NOT NULL),
  CONSTRAINT `ck_blocages_acces_e2d2138eff` CHECK (`fin` > `debut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : evenements_blocage
CREATE TABLE `evenements_blocage` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `blocage_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` DATETIME(6) NOT NULL,
  `auteur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_systeme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `motif` TEXT NOT NULL,
  `correlation` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_evenements_blocage_0deaf29506` (`blocage_id`),
  KEY `ix_evenements_blocage_1c7f79acb8` (`auteur_compte_id`),
  CONSTRAINT `ck_evenements_blocage_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_evenements_blocage_decef4b9d4` CHECK (`type` IN ('creation','levee','expiration_constatee')),
  CONSTRAINT `ck_evenements_blocage_34b91e2384` CHECK ((`auteur_compte_id` IS NOT NULL) + (`origine_systeme` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : appareils
CREATE TABLE `appareils` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `identifiant_enregistrement` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_secret_enregistrement` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_preuve` BIGINT NOT NULL,
  `libelle` VARCHAR(512) NULL,
  `plateforme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `fingerprint_hash` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  `enregistre_a` DATETIME(6) NOT NULL,
  `dernier_vu_a` DATETIME(6) NOT NULL,
  `revoque_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_appareils_6ef8a3e05d` UNIQUE (`identifiant_enregistrement`),
  CONSTRAINT `uq_appareils_a6e60fd4c7` UNIQUE (`id`, `compte_id`),
  KEY `ix_appareils_7c1d6e22f5` (`compte_id`, `actif`),
  CONSTRAINT `ck_appareils_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_appareils_e5b7cfac10` CHECK (`version_preuve` > 0),
  CONSTRAINT `ck_appareils_5f015f328b` CHECK (`actif` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : evenements_appareil
CREATE TABLE `evenements_appareil` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `appareil_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_preuve` BIGINT NOT NULL,
  `date` DATETIME(6) NOT NULL,
  `auteur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_systeme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `motif` TEXT NOT NULL,
  `correlation` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_evenements_appareil_4955bd56f2` (`appareil_id`),
  KEY `ix_evenements_appareil_1c7f79acb8` (`auteur_compte_id`),
  CONSTRAINT `ck_evenements_appareil_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_evenements_appareil_1d866140a4` CHECK (`type` IN ('enregistre','revoque','reenregistre')),
  CONSTRAINT `ck_evenements_appareil_e5b7cfac10` CHECK (`version_preuve` > 0),
  CONSTRAINT `ck_evenements_appareil_34b91e2384` CHECK ((`auteur_compte_id` IS NOT NULL) + (`origine_systeme` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : sessions_lecture
CREATE TABLE `sessions_lecture` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `licence_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `appareil_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `session_auth_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_preuve_appareil` BIGINT NOT NULL,
  `ouverte_a` DATETIME(6) NOT NULL,
  `derniere_activite_a` DATETIME(6) NOT NULL,
  `expire_a` DATETIME(6) NOT NULL,
  `fermee_a` DATETIME(6) NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_sessions_lecture_eef1915057` (`compte_id`, `statut`, `expire_a`),
  KEY `ix_sessions_lecture_4d250f31b2` (`session_auth_id`, `statut`),
  KEY `ix_sessions_lecture_8707a69255` (`licence_id`, `compte_id`),
  KEY `ix_sessions_lecture_6b49ec8856` (`appareil_id`, `compte_id`),
  KEY `ix_sessions_lecture_29ae4ac1c1` (`session_auth_id`, `compte_id`),
  CONSTRAINT `ck_sessions_lecture_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_sessions_lecture_19fbddff27` CHECK (`version_preuve_appareil` > 0),
  CONSTRAINT `ck_sessions_lecture_c237f3e2d6` CHECK (`statut` IN ('open','closed','revoked','expired')),
  CONSTRAINT `ck_sessions_lecture_c90fef655f` CHECK (`expire_a` > `ouverte_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : medias_prives
CREATE TABLE `medias_prives` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `edition_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `format_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `collection` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `disque_prive` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_objet_privee` VARCHAR(1024) NOT NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_medias_prives_cf279400f6` (`edition_id`),
  KEY `ix_medias_prives_5532d2348c` (`format_id`),
  CONSTRAINT `ck_medias_prives_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_medias_prives_5f015f328b` CHECK (`actif` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : versions_contenu
CREATE TABLE `versions_contenu` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `media_prive_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero_version` BIGINT NOT NULL,
  `reference_objet_version` VARCHAR(1024) NOT NULL,
  `empreinte_fichier` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `chiffrement_reference` TEXT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_versions_contenu_9c7aa265aa` UNIQUE (`media_prive_id`, `numero_version`),
  CONSTRAINT `ck_versions_contenu_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_versions_contenu_95f3959b4a` CHECK (`numero_version` > 0),
  CONSTRAINT `ck_versions_contenu_58d9aa8787` CHECK (`statut` IN ('preparation','ready','bloquee','archivee'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : offres_contenu
CREATE TABLE `offres_contenu` (
  `offre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_contenu_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`offre_id`, `version_contenu_id`),
  KEY `ix_offres_contenu_158d483baf` (`version_contenu_id`),
  CONSTRAINT `ck_offres_contenu_5f015f328b` CHECK (`actif` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : autorisations_contenu_origine
CREATE TABLE `autorisations_contenu_origine` (
  `origine_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_contenu_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `accorde_a` DATETIME(6) NOT NULL,
  `reference_decision` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  PRIMARY KEY (`origine_id`, `version_contenu_id`),
  KEY `ix_autorisations_contenu_origine_158d483baf` (`version_contenu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : manifestes_contenu
CREATE TABLE `manifestes_contenu` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_contenu_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `structure` JSON NOT NULL,
  `nombre_parties` BIGINT NOT NULL,
  `empreinte` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_manifestes_contenu_158d483baf` UNIQUE (`version_contenu_id`),
  CONSTRAINT `ck_manifestes_contenu_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_manifestes_contenu_a5e36c3e4b` CHECK (JSON_TYPE(`structure`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_manifestes_contenu_453eba4271` CHECK (`nombre_parties` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : segments_livre
CREATE TABLE `segments_livre` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `manifeste_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `index_segment` BIGINT NOT NULL,
  `type` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_privee` VARCHAR(1024) NOT NULL,
  `borne_debut` DECIMAL(20,8) NULL,
  `borne_fin` DECIMAL(20,8) NULL,
  `unite_borne` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `empreinte` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_segments_livre_6dff3b31a5` UNIQUE (`manifeste_id`, `index_segment`),
  CONSTRAINT `ck_segments_livre_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_segments_livre_3c65bfbb4a` CHECK (`index_segment` >= 0),
  CONSTRAINT `ck_segments_livre_853b840525` CHECK (`type` IN ('page','chapter','audio_range')),
  CONSTRAINT `ck_segments_livre_40a610a4d3` CHECK (`type` <> 'audio_range' OR (`borne_debut` IS NOT NULL AND `borne_fin` IS NOT NULL AND `unite_borne` IS NOT NULL AND `borne_debut` >= 0 AND `borne_fin` > `borne_debut`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : identites_filigrane
CREATE TABLE `identites_filigrane` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `licence_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `identifiant_opaque` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `reference_secret` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  `cree_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_identites_filigrane_97d325fd54` UNIQUE (`licence_id`),
  CONSTRAINT `uq_identites_filigrane_904c5b9ae0` UNIQUE (`identifiant_opaque`),
  CONSTRAINT `ck_identites_filigrane_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : preparations_filigrane
CREATE TABLE `preparations_filigrane` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `identite_filigrane_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_contenu_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `methode` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `algorithme_version` TEXT NOT NULL,
  `reference_derive_privee` VARCHAR(1024) NULL,
  `empreinte` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `prepare_a` DATETIME(6) NULL,
  `derniere_erreur` TEXT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_preparations_filigrane_db7eef43ec` UNIQUE (`identite_filigrane_id`, `version_contenu_id`),
  KEY `ix_preparations_filigrane_158d483baf` (`version_contenu_id`),
  CONSTRAINT `ck_preparations_filigrane_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_preparations_filigrane_80a0c14aaa` CHECK (`statut` IN ('pending','ready','error'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : jetons_acces
CREATE TABLE `jetons_acces` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `session_lecture_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_contenu_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `jti` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_ou_reference` VARCHAR(191) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `emet_a` DATETIME(6) NOT NULL,
  `expire_a` DATETIME(6) NOT NULL,
  `revoque_a` DATETIME(6) NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `perimetre_ressources` JSON NOT NULL,
  `audience` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `operation_autorisee` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_cle_signature` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_jetons_acces_5b1288bdb8` UNIQUE (`jti`),
  KEY `ix_jetons_acces_b04749ebba` (`session_lecture_id`, `statut`, `expire_a`),
  KEY `ix_jetons_acces_158d483baf` (`version_contenu_id`),
  CONSTRAINT `ck_jetons_acces_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_jetons_acces_cc132f4b8c` CHECK (`statut` IN ('active','expired','revoked')),
  CONSTRAINT `ck_jetons_acces_c1decec4d3` CHECK (JSON_TYPE(`perimetre_ressources`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_jetons_acces_741efd50d8` CHECK (`expire_a` > `emet_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : journaux_acces
CREATE TABLE `journaux_acces` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` DATETIME(6) NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `licence_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `session_lecture_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `appareil_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `jeton_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `segment_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `ip` VARCHAR(64) NULL,
  `user_agent` TEXT NULL,
  `duree_ms` BIGINT NULL,
  `resultat` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif_code` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `correlation` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_journaux_acces_57a46ee9b8` (`compte_id`, `date`, `id`),
  KEY `ix_journaux_acces_80893aa21f` (`licence_id`, `date`, `id`),
  KEY `ix_journaux_acces_577bf0c4f4` (`correlation`),
  KEY `ix_journaux_acces_cf3446ae37` (`session_lecture_id`),
  KEY `ix_journaux_acces_4955bd56f2` (`appareil_id`),
  KEY `ix_journaux_acces_b5b6647a13` (`jeton_id`),
  KEY `ix_journaux_acces_067de3ca37` (`segment_id`),
  CONSTRAINT `ck_journaux_acces_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_journaux_acces_d35b0beca9` CHECK (`duree_ms` >= 0),
  CONSTRAINT `ck_journaux_acces_4924e7b286` CHECK (`resultat` IN ('ok','denied','error'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : alertes_securite
CREATE TABLE `alertes_securite` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `licence_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `appareil_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `type` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `severite` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `detecte_a` DATETIME(6) NOT NULL,
  `traite_a` DATETIME(6) NULL,
  `motif` TEXT NULL,
  `responsable_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `reference_regle_detection` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_alertes_securite_270e734475` (`statut`, `severite`, `detecte_a`),
  KEY `ix_alertes_securite_940f1f0c40` (`compte_id`),
  KEY `ix_alertes_securite_97d325fd54` (`licence_id`),
  KEY `ix_alertes_securite_4955bd56f2` (`appareil_id`),
  KEY `ix_alertes_securite_de060ee14d` (`responsable_compte_id`),
  CONSTRAINT `ck_alertes_securite_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_alertes_securite_4ca7f359f2` CHECK (`severite` IN ('low','medium','high')),
  CONSTRAINT `ck_alertes_securite_d272c9a5ed` CHECK (`statut` IN ('open','ack','closed'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : alertes_journaux
CREATE TABLE `alertes_journaux` (
  `alerte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `journal_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`alerte_id`, `journal_id`),
  KEY `ix_alertes_journaux_7ad2d71a58` (`journal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : decisions_alerte
CREATE TABLE `decisions_alerte` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `alerte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `auteur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_systeme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `date` DATETIME(6) NOT NULL,
  `motif` TEXT NOT NULL,
  `action` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_action` JSON NULL,
  PRIMARY KEY (`id`),
  KEY `ix_decisions_alerte_f785163aa8` (`alerte_id`),
  KEY `ix_decisions_alerte_1c7f79acb8` (`auteur_compte_id`),
  CONSTRAINT `ck_decisions_alerte_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_decisions_alerte_4beeda43b3` CHECK (`action` IN ('observation','limitation','blocage','revocation_appareil','revocation_origine')),
  CONSTRAINT `ck_decisions_alerte_efceb5c6ed` CHECK (JSON_TYPE(`reference_action`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_decisions_alerte_34b91e2384` CHECK ((`auteur_compte_id` IS NOT NULL) + (`origine_systeme` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : progressions_lecture
CREATE TABLE `progressions_lecture` (
  `licence_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_contenu_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `position_ref` JSON NOT NULL,
  `pct` DECIMAL(20,8) NULL,
  `revision` BIGINT NOT NULL,
  `maj_a` DATETIME(6) NOT NULL,
  `dernier_appareil_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`licence_id`, `version_contenu_id`),
  KEY `ix_progressions_lecture_158d483baf` (`version_contenu_id`),
  KEY `ix_progressions_lecture_efa8f32c0a` (`dernier_appareil_id`),
  CONSTRAINT `ck_progressions_lecture_9ec1e43c65` CHECK (JSON_TYPE(`position_ref`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_progressions_lecture_6b9cb0834e` CHECK (`revision` > 0),
  CONSTRAINT `ck_progressions_lecture_09eda3c0da` CHECK (`pct` BETWEEN 0 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : receptions_extension_contenu
CREATE TABLE `receptions_extension_contenu` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision_extension_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_acquisition_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_contenu_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `empreinte_intention` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` TEXT NULL,
  `recu_a` DATETIME(6) NOT NULL,
  `repondu_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_receptions_extension_contenu_e03f51e08b` UNIQUE (`decision_extension_id`, `reference_acquisition_id`),
  KEY `ix_receptions_extension_contenu_967027099e` (`reference_acquisition_id`),
  KEY `ix_receptions_extension_contenu_158d483baf` (`version_contenu_id`),
  CONSTRAINT `ck_receptions_extension_contenu_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_receptions_extension_contenu_85e952d708` CHECK (`statut` IN ('en_attente','confirmee','refusee')),
  CONSTRAINT `ck_receptions_extension_contenu_d807601759` CHECK (`repondu_a` >= `recu_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A4 : metadonnees_bibliotheque
CREATE TABLE `metadonnees_bibliotheque` (
  `origine_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `titre_historique` VARCHAR(512) NULL,
  `format_historique` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `source` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_preuve` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `capture_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`origine_id`),
  CONSTRAINT `ck_metadonnees_bibliotheque_ced135e445` CHECK (`source` IN ('snapshot_commande','catalogue_autorise','attribution_verifiee'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : manuscrits
CREATE TABLE `manuscrits` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `deposant_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `organisation_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `titre_travail` VARCHAR(512) NULL,
  `cycle` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `abandonne_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  KEY `ix_manuscrits_3844c98e10` (`deposant_compte_id`),
  KEY `ix_manuscrits_1b213432c4` (`organisation_id`),
  CONSTRAINT `ck_manuscrits_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_manuscrits_f75b9a3f81` CHECK (`cycle` IN ('ouvert','abandonne'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : fichiers_manuscrit
CREATE TABLE `fichiers_manuscrit` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `manuscrit_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_privee` VARCHAR(1024) NOT NULL,
  `empreinte` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `format_fichier` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `taille_octets` BIGINT NOT NULL,
  `depose_a` DATETIME(6) NOT NULL,
  `depose_par` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_fichiers_manuscrit_62122d9acb` (`manuscrit_id`),
  KEY `ix_fichiers_manuscrit_9c32561e4b` (`depose_par`),
  CONSTRAINT `ck_fichiers_manuscrit_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_fichiers_manuscrit_236411b386` CHECK (`taille_octets` > 0),
  CONSTRAINT `ck_fichiers_manuscrit_17334898fa` CHECK (`statut` IN ('quarantaine','valide','refuse','archive'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : dossiers_publication
CREATE TABLE `dossiers_publication` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `manuscrit_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `precedent_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `numero` BIGINT NOT NULL,
  `nature` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `titre` VARCHAR(512) NULL,
  `sous_titre` VARCHAR(512) NULL,
  `resume` TEXT NULL,
  `mots_cles` JSON NULL,
  `declarations_droits` JSON NULL,
  `niveau_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `soumis_a` DATETIME(6) NULL,
  `termine_a` DATETIME(6) NULL,
  `reference_regles_completude` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  `empreinte_soumission` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `oeuvre_cible_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `empreinte_base_oeuvre` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `snapshot_base_oeuvre` JSON NULL,
  `uq_version_courante` TINYINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN `statut` IN ('draft','submitted','under_review') THEN 1 ELSE NULL END) VIRTUAL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_dossiers_publication_4ce4a55fd4` UNIQUE (`precedent_id`),
  CONSTRAINT `uq_dossiers_publication_7147aa24e4` UNIQUE (`manuscrit_id`, `numero`),
  CONSTRAINT `uq_dossiers_publication_2473f2491e` UNIQUE (`manuscrit_id`, `uq_version_courante`),
  CONSTRAINT `uq_dossiers_publication_21db65a88f` UNIQUE (`id`, `manuscrit_id`),
  KEY `ix_dossiers_publication_6a4864c5d9` (`manuscrit_id`, `statut`, `numero`),
  KEY `ix_dossiers_publication_4183ae6c37` (`precedent_id`, `manuscrit_id`),
  KEY `ix_dossiers_publication_bc5641478c` (`niveau_id`),
  KEY `ix_dossiers_publication_4bf83f3fa7` (`oeuvre_cible_id`),
  CONSTRAINT `ck_dossiers_publication_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_dossiers_publication_367e138391` CHECK (`numero` > 0),
  CONSTRAINT `ck_dossiers_publication_aa699cc604` CHECK (`nature` IN ('initiale','correction','nouvelle_edition')),
  CONSTRAINT `ck_dossiers_publication_35a2c80ba8` CHECK (`statut` IN ('draft','submitted','under_review','needs_fix','accepted','rejected','withdrawn')),
  CONSTRAINT `ck_dossiers_publication_bde2a85654` CHECK (JSON_TYPE(`mots_cles`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_dossiers_publication_a9803199b3` CHECK (JSON_TYPE(`declarations_droits`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_dossiers_publication_c693c6afc1` CHECK (JSON_TYPE(`snapshot_base_oeuvre`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : selections_fichiers_publication
CREATE TABLE `selections_fichiers_publication` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `fichier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `usage` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `format_cible_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_selections_fichiers_publication_6512fb6ccc` UNIQUE (`dossier_id`, `fichier_id`, `usage`, `format_cible_id`),
  KEY `ix_selections_fichiers_publication_b2379dd0f5` (`fichier_id`),
  KEY `ix_selections_fichiers_publication_ccff9c03f8` (`format_cible_id`),
  CONSTRAINT `ck_selections_fichiers_publication_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : dossiers_categories
CREATE TABLE `dossiers_categories` (
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `categorie_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`dossier_id`, `categorie_id`),
  KEY `ix_dossiers_categories_52493cdd56` (`categorie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : dossiers_langues
CREATE TABLE `dossiers_langues` (
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `langue_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `principale` TINYINT UNSIGNED NOT NULL,
  `uq_langue_principale` TINYINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN `principale` = 1 THEN 1 ELSE NULL END) VIRTUAL,
  PRIMARY KEY (`dossier_id`, `langue_id`),
  CONSTRAINT `uq_dossiers_langues_a6654eb1c4` UNIQUE (`dossier_id`, `uq_langue_principale`),
  KEY `ix_dossiers_langues_4a0e2288d6` (`langue_id`),
  CONSTRAINT `ck_dossiers_langues_31340162b0` CHECK (`principale` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : editions_cibles
CREATE TABLE `editions_cibles` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_locale` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `libelle` VARCHAR(512) NULL,
  `date_edition` DATE NULL,
  `role_ae` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `responsable_depot` TEXT NULL,
  `statut_depot` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `edition_cible_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `empreinte_base_edition` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `snapshot_base_edition` JSON NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_editions_cibles_07e25edb4a` UNIQUE (`dossier_id`, `reference_locale`),
  KEY `ix_editions_cibles_44cc54aa64` (`edition_cible_id`),
  CONSTRAINT `ck_editions_cibles_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_editions_cibles_2c46d1575f` CHECK (`role_ae` IN ('distributeur','prestataire')),
  CONSTRAINT `ck_editions_cibles_4ad4eb0d9a` CHECK (JSON_TYPE(`snapshot_base_edition`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : editeurs_legaux_publication
CREATE TABLE `editeurs_legaux_publication` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `edition_cible_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `libelle` VARCHAR(512) NOT NULL,
  `editeur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_editeurs_legaux_publication_44cc54aa64` UNIQUE (`edition_cible_id`),
  KEY `ix_editeurs_legaux_publication_a7ec1680cf` (`editeur_id`),
  CONSTRAINT `ck_editeurs_legaux_publication_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_editeurs_legaux_publication_c93c9cb4dd` CHECK (`type` IN ('maison','autoedite','autre'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : isbn_demandes
CREATE TABLE `isbn_demandes` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `edition_cible_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `isbn_normalise` TEXT NULL,
  `reference_preuve_privee` VARCHAR(1024) NULL,
  `empreinte_preuve` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_isbn_demandes_44cc54aa64` UNIQUE (`edition_cible_id`),
  CONSTRAINT `ck_isbn_demandes_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_isbn_demandes_2d09fcf5c7` CHECK (`statut` IN ('fourni','absent','en_cours_exterieur')),
  CONSTRAINT `ck_isbn_demandes_b80f774487` CHECK ((`statut` = 'fourni' AND `isbn_normalise` IS NOT NULL) OR (`statut` <> 'fourni' AND `isbn_normalise` IS NULL)),
  CONSTRAINT `ck_isbn_demandes_44c3dea957` CHECK ((`reference_preuve_privee` IS NULL) = (`empreinte_preuve` IS NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : offres_cibles
CREATE TABLE `offres_cibles` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `edition_cible_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_locale` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `format_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `vendeur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `condition` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `modalite` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `prix` DECIMAL(20,4) NULL,
  `devise` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `actif_souhaite` TINYINT UNSIGNED NOT NULL,
  `offre_cible_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `empreinte_base_offre` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `snapshot_base_offre` JSON NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_offres_cibles_b967beed38` UNIQUE (`edition_cible_id`, `reference_locale`),
  KEY `ix_offres_cibles_5532d2348c` (`format_id`),
  KEY `ix_offres_cibles_59710ce746` (`vendeur_id`),
  KEY `ix_offres_cibles_456622d42e` (`offre_cible_id`),
  CONSTRAINT `ck_offres_cibles_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_offres_cibles_5f7c6c8db4` CHECK (`condition` IN ('none','new','used')),
  CONSTRAINT `ck_offres_cibles_2f6c60387f` CHECK (`modalite` IN ('achat')),
  CONSTRAINT `ck_offres_cibles_adfea8dd71` CHECK (`prix` >= 0),
  CONSTRAINT `ck_offres_cibles_dc389a0ff5` CHECK (`devise` IN ('CDF','USD')),
  CONSTRAINT `ck_offres_cibles_7ec9fc7ec9` CHECK (`actif_souhaite` IN (0,1)),
  CONSTRAINT `ck_offres_cibles_6abb19749a` CHECK (JSON_TYPE(`snapshot_base_offre`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : preparations_papier
CREATE TABLE `preparations_papier` (
  `offre_cible_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `unite_stock_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `configuration_reference` TEXT NULL,
  `configuration_empreinte` TEXT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `verifie_a` DATETIME(6) NULL,
  `resultat` JSON NULL,
  PRIMARY KEY (`offre_cible_id`),
  KEY `ix_preparations_papier_d632d23af0` (`unite_stock_id`),
  CONSTRAINT `ck_preparations_papier_80a0c14aaa` CHECK (`statut` IN ('pending','ready','error')),
  CONSTRAINT `ck_preparations_papier_57a028ab8d` CHECK (JSON_TYPE(`resultat`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : pieces_droits_publication
CREATE TABLE `pieces_droits_publication` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type_document` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_privee` VARCHAR(1024) NOT NULL,
  `empreinte` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `depose_a` DATETIME(6) NOT NULL,
  `depose_par` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_pieces_droits_publication_0259b9fde9` (`dossier_id`),
  KEY `ix_pieces_droits_publication_9c32561e4b` (`depose_par`),
  CONSTRAINT `ck_pieces_droits_publication_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : perimetres_droits_publication
CREATE TABLE `perimetres_droits_publication` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_declarant` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `declaration` JSON NOT NULL,
  `debut` DATETIME(6) NULL,
  `fin` DATETIME(6) NULL,
  `mode_territoires` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_perimetres_droits_publication_0259b9fde9` (`dossier_id`),
  CONSTRAINT `ck_perimetres_droits_publication_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_perimetres_droits_publication_7c050400fc` CHECK (JSON_TYPE(`declaration`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_perimetres_droits_publication_f4787cdbdf` CHECK (`mode_territoires` IN ('monde','selection')),
  CONSTRAINT `ck_perimetres_droits_publication_e2d2138eff` CHECK (`fin` > `debut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : territoires_publication
CREATE TABLE `territoires_publication` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `libelle` VARCHAR(512) NOT NULL,
  `reference_definition` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_territoires_publication_5694d08a2e` UNIQUE (`code`),
  CONSTRAINT `ck_territoires_publication_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_territoires_publication_5f015f328b` CHECK (`actif` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : editions_territoires
CREATE TABLE `editions_territoires` (
  `edition_cible_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `territoire_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`edition_cible_id`, `territoire_id`),
  KEY `ix_editions_territoires_6590bb9872` (`territoire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : perimetres_territoires
CREATE TABLE `perimetres_territoires` (
  `perimetre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `territoire_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`perimetre_id`, `territoire_id`),
  KEY `ix_perimetres_territoires_6590bb9872` (`territoire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : perimetres_formats
CREATE TABLE `perimetres_formats` (
  `perimetre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `format_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`perimetre_id`, `format_id`),
  KEY `ix_perimetres_formats_5532d2348c` (`format_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : perimetres_actes
CREATE TABLE `perimetres_actes` (
  `perimetre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `acte` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`perimetre_id`, `acte`),
  CONSTRAINT `ck_perimetres_actes_f447a6f68c` CHECK (`acte` IN ('vente','diffusion'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : perimetres_pieces
CREATE TABLE `perimetres_pieces` (
  `perimetre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `piece_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`perimetre_id`, `piece_id`),
  KEY `ix_perimetres_pieces_22f58a002c` (`piece_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : perimetres_editions
CREATE TABLE `perimetres_editions` (
  `perimetre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `edition_cible_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`perimetre_id`, `edition_cible_id`),
  KEY `ix_perimetres_editions_44cc54aa64` (`edition_cible_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : contributions_publication
CREATE TABLE `contributions_publication` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `auteur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `nom_declare` TEXT NOT NULL,
  `role` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ordre` BIGINT NOT NULL,
  `justification` JSON NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_contributions_publication_52c9056653` UNIQUE (`dossier_id`, `ordre`),
  KEY `ix_contributions_publication_6e38b1ca93` (`auteur_id`),
  CONSTRAINT `ck_contributions_publication_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_contributions_publication_19a0a5b563` CHECK (`ordre` >= 0),
  CONSTRAINT `ck_contributions_publication_8bf53a17b2` CHECK (JSON_TYPE(`justification`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : medias_soumis_publication
CREATE TABLE `medias_soumis_publication` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `usage` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_privee` VARCHAR(1024) NOT NULL,
  `empreinte` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `format` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `depose_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_medias_soumis_publication_0259b9fde9` (`dossier_id`),
  CONSTRAINT `ck_medias_soumis_publication_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_medias_soumis_publication_3574d9a471` CHECK (`usage` IN ('couverture','extrait')),
  CONSTRAINT `ck_medias_soumis_publication_17334898fa` CHECK (`statut` IN ('quarantaine','valide','refuse','archive'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : regles_controle_publication
CREATE TABLE `regles_controle_publication` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version` BIGINT NOT NULL,
  `obligatoire` TINYINT UNSIGNED NOT NULL,
  `autorise_non_applicable` TINYINT UNSIGNED NOT NULL,
  `applicabilite` JSON NOT NULL,
  `definition` JSON NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_regles_controle_publication_f5b433d863` UNIQUE (`code`, `version`),
  CONSTRAINT `ck_regles_controle_publication_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_regles_controle_publication_97a58d3a38` CHECK (`version` > 0),
  CONSTRAINT `ck_regles_controle_publication_eee7d56226` CHECK (`obligatoire` IN (0,1)),
  CONSTRAINT `ck_regles_controle_publication_aa6b0ea1a2` CHECK (`autorise_non_applicable` IN (0,1)),
  CONSTRAINT `ck_regles_controle_publication_cbc91d7245` CHECK (JSON_TYPE(`applicabilite`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_regles_controle_publication_c30259bb9b` CHECK (JSON_TYPE(`definition`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : exigences_dossier
CREATE TABLE `exigences_dossier` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `regle_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `justification_applicabilite` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_exigences_dossier_ae9f8361d8` UNIQUE (`dossier_id`, `regle_id`),
  KEY `ix_exigences_dossier_d421e46a2f` (`regle_id`),
  CONSTRAINT `ck_exigences_dossier_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : acces_dossiers_publication
CREATE TABLE `acces_dossiers_publication` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `debut` DATETIME(6) NOT NULL,
  `fin` DATETIME(6) NULL,
  `revoque_a` DATETIME(6) NULL,
  `reference_autorisation` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `origine_systeme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_acces_dossiers_publication_0259b9fde9` (`dossier_id`),
  KEY `ix_acces_dossiers_publication_940f1f0c40` (`compte_id`),
  CONSTRAINT `ck_acces_dossiers_publication_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_acces_dossiers_publication_e2d2138eff` CHECK (`fin` > `debut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : affectations_revue
CREATE TABLE `affectations_revue` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `responsable_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `debut` DATETIME(6) NOT NULL,
  `fin` DATETIME(6) NULL,
  `motif` TEXT NOT NULL,
  `reference_regle_affectation` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_affectations_revue_6fa43b0109` (`dossier_id`, `debut`, `fin`),
  KEY `ix_affectations_revue_de060ee14d` (`responsable_compte_id`),
  CONSTRAINT `ck_affectations_revue_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_affectations_revue_e2d2138eff` CHECK (`fin` > `debut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : controles_publication
CREATE TABLE `controles_publication` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `exigence_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `auteur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero` BIGINT NOT NULL,
  `resultat` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `observation` TEXT NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `empreinte_contenu_examine` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_controles_publication_1ceb82c089` UNIQUE (`exigence_id`, `numero`),
  KEY `ix_controles_publication_1c7f79acb8` (`auteur_compte_id`),
  CONSTRAINT `ck_controles_publication_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_controles_publication_367e138391` CHECK (`numero` > 0),
  CONSTRAINT `ck_controles_publication_358c258d25` CHECK (`resultat` IN ('pending','passed','failed','not_applicable'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : elements_controle
CREATE TABLE `elements_controle` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `controle_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_global_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `selection_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `piece_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `edition_cible_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `offre_cible_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `contribution_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `media_soumis_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `perimetre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `empreinte_examinee` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_elements_controle_0ed016e731` (`controle_id`),
  KEY `ix_elements_controle_142313eb1f` (`dossier_global_id`),
  KEY `ix_elements_controle_3a61b1695a` (`selection_id`),
  KEY `ix_elements_controle_22f58a002c` (`piece_id`),
  KEY `ix_elements_controle_44cc54aa64` (`edition_cible_id`),
  KEY `ix_elements_controle_456622d42e` (`offre_cible_id`),
  KEY `ix_elements_controle_d4a1d9166f` (`contribution_id`),
  KEY `ix_elements_controle_4ac4d57434` (`media_soumis_id`),
  KEY `ix_elements_controle_f4fd69f4b8` (`perimetre_id`),
  CONSTRAINT `ck_elements_controle_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_elements_controle_ca2df74eaa` CHECK ((`dossier_global_id` IS NOT NULL) + (`selection_id` IS NOT NULL) + (`piece_id` IS NOT NULL) + (`edition_cible_id` IS NOT NULL) + (`offre_cible_id` IS NOT NULL) + (`contribution_id` IS NOT NULL) + (`media_soumis_id` IS NOT NULL) + (`perimetre_id` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : decisions_publication
CREATE TABLE `decisions_publication` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decideur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resultat` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` TEXT NOT NULL,
  `decide_a` DATETIME(6) NOT NULL,
  `preuve_independance` JSON NOT NULL,
  `correlation` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_decisions_publication_0259b9fde9` UNIQUE (`dossier_id`),
  KEY `ix_decisions_publication_2b292ce11f` (`decideur_compte_id`),
  CONSTRAINT `ck_decisions_publication_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_decisions_publication_893d74ed84` CHECK (`resultat` IN ('accepted','needs_fix','rejected')),
  CONSTRAINT `ck_decisions_publication_f92f1259c7` CHECK (JSON_TYPE(`preuve_independance`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : decisions_controles
CREATE TABLE `decisions_controles` (
  `decision_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `controle_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`decision_id`, `controle_id`),
  CONSTRAINT `uq_decisions_controles_0ed016e731` UNIQUE (`controle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : executions_publication
CREATE TABLE `executions_publication` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_execution` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_intention` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `publie_a` DATETIME(6) NULL,
  `annule_a` DATETIME(6) NULL,
  `motif` TEXT NULL,
  `revision_execution` BIGINT NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_executions_publication_0259b9fde9` UNIQUE (`dossier_id`),
  CONSTRAINT `uq_executions_publication_ea7b3e4580` UNIQUE (`reference_execution`),
  KEY `ix_executions_publication_1b624651ed` (`statut`, `cree_a`),
  CONSTRAINT `ck_executions_publication_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_executions_publication_89fa08153e` CHECK (`statut` IN ('pending','hold','preparing','ready','published','error','cancelled')),
  CONSTRAINT `ck_executions_publication_f48eb0152c` CHECK (`revision_execution` > 0),
  CONSTRAINT `ck_executions_publication_bccbdff9bb` CHECK (`annule_a` >= `cree_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : tentatives_publication
CREATE TABLE `tentatives_publication` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `execution_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero` BIGINT NOT NULL,
  `etape` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `debut` DATETIME(6) NOT NULL,
  `fin` DATETIME(6) NULL,
  `resultat` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `erreur_code` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `detail` TEXT NULL,
  `correlation` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_tentatives_publication_9cfae1852e` UNIQUE (`execution_id`, `numero`),
  CONSTRAINT `ck_tentatives_publication_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_tentatives_publication_367e138391` CHECK (`numero` > 0),
  CONSTRAINT `ck_tentatives_publication_66f4c58496` CHECK (`resultat` IN ('en_cours','reussie','echec','annulee')),
  CONSTRAINT `ck_tentatives_publication_e2d2138eff` CHECK (`fin` > `debut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : productions_contenu
CREATE TABLE `productions_contenu` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `execution_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `edition_cible_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `format_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_production` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `methode` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_methode` TEXT NOT NULL,
  `empreinte_configuration` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_resultat_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `resultat` JSON NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_productions_contenu_3793efe75b` UNIQUE (`reference_production`),
  CONSTRAINT `uq_productions_contenu_529fd87c30` UNIQUE (`execution_id`, `edition_cible_id`, `format_id`),
  KEY `ix_productions_contenu_44cc54aa64` (`edition_cible_id`),
  KEY `ix_productions_contenu_5532d2348c` (`format_id`),
  KEY `ix_productions_contenu_adaea13ae4` (`version_resultat_id`),
  CONSTRAINT `ck_productions_contenu_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_productions_contenu_3a43532500` CHECK (`statut` IN ('pending','preparing','ready','error')),
  CONSTRAINT `ck_productions_contenu_57a028ab8d` CHECK (JSON_TYPE(`resultat`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : productions_sources
CREATE TABLE `productions_sources` (
  `production_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `selection_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`production_id`, `selection_id`),
  KEY `ix_productions_sources_3a61b1695a` (`selection_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : resultats_oeuvres_publication
CREATE TABLE `resultats_oeuvres_publication` (
  `execution_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `oeuvre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `operation` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `valeurs_preparees` JSON NOT NULL,
  `empreinte_preparee` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `empreinte_appliquee` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`execution_id`),
  KEY `ix_resultats_oeuvres_publication_a3fad59948` (`oeuvre_id`),
  CONSTRAINT `ck_resultats_oeuvres_publication_94334d7554` CHECK (`operation` IN ('creation','mise_a_jour')),
  CONSTRAINT `ck_resultats_oeuvres_publication_3514792969` CHECK (JSON_TYPE(`valeurs_preparees`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : resultats_editions_publication
CREATE TABLE `resultats_editions_publication` (
  `edition_cible_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `execution_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `edition_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `operation` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `valeurs_preparees` JSON NOT NULL,
  `empreinte_preparee` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `empreinte_appliquee` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`edition_cible_id`),
  KEY `ix_resultats_editions_publication_6d6de5b8d6` (`execution_id`),
  KEY `ix_resultats_editions_publication_cf279400f6` (`edition_id`),
  CONSTRAINT `ck_resultats_editions_publication_94334d7554` CHECK (`operation` IN ('creation','mise_a_jour')),
  CONSTRAINT `ck_resultats_editions_publication_3514792969` CHECK (JSON_TYPE(`valeurs_preparees`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : resultats_offres_publication
CREATE TABLE `resultats_offres_publication` (
  `offre_cible_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `execution_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `offre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `operation` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `valeurs_preparees` JSON NOT NULL,
  `empreinte_preparee` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `empreinte_appliquee` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`offre_cible_id`),
  KEY `ix_resultats_offres_publication_6d6de5b8d6` (`execution_id`),
  KEY `ix_resultats_offres_publication_0d7af68203` (`offre_id`),
  CONSTRAINT `ck_resultats_offres_publication_94334d7554` CHECK (`operation` IN ('creation','mise_a_jour')),
  CONSTRAINT `ck_resultats_offres_publication_3514792969` CHECK (JSON_TYPE(`valeurs_preparees`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : derives_catalogue_publication
CREATE TABLE `derives_catalogue_publication` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `execution_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `media_soumis_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_derive_privee` VARCHAR(1024) NOT NULL,
  `empreinte` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `media_catalogue_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_derives_catalogue_publication_51227e1206` UNIQUE (`media_catalogue_id`),
  KEY `ix_derives_catalogue_publication_6d6de5b8d6` (`execution_id`),
  KEY `ix_derives_catalogue_publication_4ac4d57434` (`media_soumis_id`),
  CONSTRAINT `ck_derives_catalogue_publication_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_derives_catalogue_publication_80a0c14aaa` CHECK (`statut` IN ('pending','ready','error'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : instructions_retrait_publication
CREATE TABLE `instructions_retrait_publication` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `manuscrit_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `producteur` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_instruction` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_intention` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `effet` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `restriction_source_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `restriction_levee_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `fin_effet_a` DATETIME(6) NULL,
  `cible_manuscrit_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `oeuvre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `edition_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `offre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `version_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `motif` TEXT NOT NULL,
  `reference_decision` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `effet_a` DATETIME(6) NOT NULL,
  `recu_a` DATETIME(6) NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `auteur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_systeme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `resultat` JSON NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_instructions_retrait_publication_a648930d1f` UNIQUE (`producteur`, `reference_instruction`),
  KEY `ix_instructions_retrait_publication_0292e5fa0b` (`oeuvre_id`, `effet`, `effet_a`),
  KEY `ix_instructions_retrait_publication_09e1a813df` (`edition_id`, `effet`, `effet_a`),
  KEY `ix_instructions_retrait_publication_713d72eb1a` (`offre_id`, `effet`, `effet_a`),
  KEY `ix_instructions_retrait_publication_f09f0582a4` (`version_id`, `effet`, `effet_a`),
  KEY `ix_instructions_retrait_publication_634d3046dc` (`statut`, `effet_a`),
  KEY `ix_instructions_retrait_publication_62122d9acb` (`manuscrit_id`),
  KEY `ix_instructions_retrait_publication_41feac9dd3` (`restriction_source_id`),
  KEY `ix_instructions_retrait_publication_1c3fce3839` (`restriction_levee_id`),
  KEY `ix_instructions_retrait_publication_e970cfc4c0` (`cible_manuscrit_id`),
  KEY `ix_instructions_retrait_publication_1c7f79acb8` (`auteur_compte_id`),
  CONSTRAINT `ck_instructions_retrait_publication_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_instructions_retrait_publication_f8cb9a6749` CHECK (`effet` IN ('abandon','arret_ventes','retrait_catalogue','interdiction_diffusion','levee_restriction')),
  CONSTRAINT `ck_instructions_retrait_publication_18ab015a94` CHECK (`statut` IN ('recue','appliquee','rejetee')),
  CONSTRAINT `ck_instructions_retrait_publication_57a028ab8d` CHECK (JSON_TYPE(`resultat`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_instructions_retrait_publication_b2d4293c68` CHECK ((`cible_manuscrit_id` IS NOT NULL) + (`oeuvre_id` IS NOT NULL) + (`edition_id` IS NOT NULL) + (`offre_id` IS NOT NULL) + (`version_id` IS NOT NULL) = 1),
  CONSTRAINT `ck_instructions_retrait_publication_34b91e2384` CHECK ((`auteur_compte_id` IS NOT NULL) + (`origine_systeme` IS NOT NULL) = 1),
  CONSTRAINT `ck_instructions_retrait_publication_b27badd452` CHECK ((`effet` = 'levee_restriction') = (`restriction_levee_id` IS NOT NULL)),
  CONSTRAINT `ck_instructions_retrait_publication_8d7785930a` CHECK (NOT (`restriction_source_id` IS NOT NULL AND `restriction_levee_id` IS NOT NULL)),
  CONSTRAINT `ck_instructions_retrait_publication_7d04b13fed` CHECK (`fin_effet_a` > `effet_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : effets_retrait_publication
CREATE TABLE `effets_retrait_publication` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `instruction_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `destinataire` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_effet` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `confirme_a` DATETIME(6) NULL,
  `resultat` JSON NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_effets_retrait_publication_a280b897e4` UNIQUE (`reference_effet`),
  CONSTRAINT `uq_effets_retrait_publication_920c4acd8e` UNIQUE (`instruction_id`, `destinataire`),
  CONSTRAINT `ck_effets_retrait_publication_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_effets_retrait_publication_8f0d5057d0` CHECK (`statut` IN ('en_attente','confirme','refuse')),
  CONSTRAINT `ck_effets_retrait_publication_57a028ab8d` CHECK (JSON_TYPE(`resultat`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : decisions_extension_contenu
CREATE TABLE `decisions_extension_contenu` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `manuscrit_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version_contenu_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `approbateur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_decision` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_intention` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` TEXT NOT NULL,
  `decide_a` DATETIME(6) NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_decisions_extension_contenu_1b1f21f3f5` UNIQUE (`reference_decision`),
  KEY `ix_decisions_extension_contenu_62122d9acb` (`manuscrit_id`),
  KEY `ix_decisions_extension_contenu_158d483baf` (`version_contenu_id`),
  KEY `ix_decisions_extension_contenu_de3c581231` (`approbateur_compte_id`),
  CONSTRAINT `ck_decisions_extension_contenu_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_decisions_extension_contenu_fe3f3d434f` CHECK (`statut` IN ('en_attente','confirmee','partiellement_confirmee','refusee'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : cibles_extension_contenu
CREATE TABLE `cibles_extension_contenu` (
  `decision_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_acquisition_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `origine_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_reponse` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  `repondu_a` DATETIME(6) NULL,
  `motif` TEXT NULL,
  PRIMARY KEY (`decision_id`, `reference_acquisition_id`),
  KEY `ix_cibles_extension_contenu_967027099e` (`reference_acquisition_id`),
  KEY `ix_cibles_extension_contenu_ee38579093` (`origine_id`),
  CONSTRAINT `ck_cibles_extension_contenu_85e952d708` CHECK (`statut` IN ('en_attente','confirmee','refusee'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : evenements_publication
CREATE TABLE `evenements_publication` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `manuscrit_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `type` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` DATETIME(6) NOT NULL,
  `ancien_etat` JSON NULL,
  `nouvel_etat` JSON NULL,
  `motif` TEXT NOT NULL,
  `auteur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_systeme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `correlation` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `reference_action` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  PRIMARY KEY (`id`),
  KEY `ix_evenements_publication_32d26f2c57` (`dossier_id`, `manuscrit_id`),
  KEY `ix_evenements_publication_62122d9acb` (`manuscrit_id`),
  KEY `ix_evenements_publication_1c7f79acb8` (`auteur_compte_id`),
  CONSTRAINT `ck_evenements_publication_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_evenements_publication_a451813d6c` CHECK (JSON_TYPE(`ancien_etat`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_evenements_publication_5a9562d49a` CHECK (JSON_TYPE(`nouvel_etat`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_evenements_publication_34b91e2384` CHECK ((`auteur_compte_id` IS NOT NULL) + (`origine_systeme` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : droits_offres_publication
CREATE TABLE `droits_offres_publication` (
  `offre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `perimetre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `execution_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `acte` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`offre_id`, `perimetre_id`, `acte`),
  KEY `ix_droits_offres_publication_a0f73fc9bd` (`offre_id`, `acte`, `actif`),
  KEY `ix_droits_offres_publication_f4fd69f4b8` (`perimetre_id`),
  KEY `ix_droits_offres_publication_6d6de5b8d6` (`execution_id`),
  CONSTRAINT `ck_droits_offres_publication_f447a6f68c` CHECK (`acte` IN ('vente','diffusion')),
  CONSTRAINT `ck_droits_offres_publication_5f015f328b` CHECK (`actif` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A5 : droits_versions_publication
CREATE TABLE `droits_versions_publication` (
  `version_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `perimetre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `execution_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `actif` TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (`version_id`, `perimetre_id`),
  KEY `ix_droits_versions_publication_f0d5f3ff70` (`version_id`, `actif`),
  KEY `ix_droits_versions_publication_f4fd69f4b8` (`perimetre_id`),
  KEY `ix_droits_versions_publication_6d6de5b8d6` (`execution_id`),
  CONSTRAINT `ck_droits_versions_publication_5f015f328b` CHECK (`actif` IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : plaignants
CREATE TABLE `plaignants` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_systeme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `nom` VARCHAR(512) NULL,
  `contact` JSON NULL,
  `qualite_invoquee` TEXT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_plaignants_940f1f0c40` (`compte_id`),
  CONSTRAINT `ck_plaignants_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_plaignants_5879b1a2f2` CHECK (`type` IN ('compte','externe','interne')),
  CONSTRAINT `ck_plaignants_c371ddbb9c` CHECK (JSON_TYPE(`contact`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_plaignants_ed113db005` CHECK ((`type` = 'compte' AND `compte_id` IS NOT NULL AND `origine_systeme` IS NULL) OR (`type` = 'interne' AND `origine_systeme` IS NOT NULL) OR (`type` = 'externe' AND `compte_id` IS NULL AND `origine_systeme` IS NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : signalements_contenu
CREATE TABLE `signalements_contenu` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `plaignant_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `canal` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `recu_a` DATETIME(6) NOT NULL,
  `identite_declaree` JSON NOT NULL,
  `declaration_initiale` JSON NOT NULL,
  `completude` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `urgence` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cycle` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `clos_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_signalements_contenu_52367a6622` UNIQUE (`reference`),
  KEY `ix_signalements_contenu_129ab42907` (`plaignant_id`),
  CONSTRAINT `ck_signalements_contenu_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_signalements_contenu_3d48b99253` CHECK (JSON_TYPE(`identite_declaree`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_signalements_contenu_f353714ee3` CHECK (JSON_TYPE(`declaration_initiale`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_signalements_contenu_7ae07f27eb` CHECK (`completude` IN ('recu','incomplet','complet')),
  CONSTRAINT `ck_signalements_contenu_67844f0819` CHECK (`urgence` IN ('standard','manifeste')),
  CONSTRAINT `ck_signalements_contenu_ad63c45392` CHECK (`cycle` IN ('ouvert','clos'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : complements_signalement
CREATE TABLE `complements_signalement` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_source` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `corps` JSON NOT NULL,
  `recu_a` DATETIME(6) NOT NULL,
  `preuve_auteur` JSON NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_complements_signalement_6a8b03625f` UNIQUE (`signalement_id`, `reference_source`),
  CONSTRAINT `uq_complements_signalement_b052c60551` UNIQUE (`id`, `signalement_id`),
  CONSTRAINT `ck_complements_signalement_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_complements_signalement_5a674ab570` CHECK (JSON_TYPE(`corps`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_complements_signalement_e2d108f9c4` CHECK (JSON_TYPE(`preuve_auteur`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : contenus_vises
CREATE TABLE `contenus_vises` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `complement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `type_declare` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `url_declaree` TEXT NULL,
  `description` TEXT NOT NULL,
  `instantane` JSON NULL,
  `declare_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_contenus_vises_9425755a5b` (`complement_id`, `signalement_id`),
  KEY `ix_contenus_vises_855a2083e7` (`signalement_id`),
  CONSTRAINT `ck_contenus_vises_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_contenus_vises_76dbfbcb93` CHECK (JSON_TYPE(`instantane`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : resolutions_cibles
CREATE TABLE `resolutions_cibles` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `contenu_vise_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero` BIGINT NOT NULL,
  `resultat` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `oeuvre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `edition_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `offre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `media_catalogue_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `avis_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `version_contenu_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `preuve_resolution` JSON NOT NULL,
  `resolu_a` DATETIME(6) NOT NULL,
  `auteur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_systeme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_resolutions_cibles_7f6960c224` UNIQUE (`contenu_vise_id`, `numero`),
  KEY `ix_resolutions_cibles_a3fad59948` (`oeuvre_id`),
  KEY `ix_resolutions_cibles_cf279400f6` (`edition_id`),
  KEY `ix_resolutions_cibles_0d7af68203` (`offre_id`),
  KEY `ix_resolutions_cibles_158d483baf` (`version_contenu_id`),
  KEY `ix_resolutions_cibles_daeda78b68` (`avis_id`),
  KEY `ix_resolutions_cibles_51227e1206` (`media_catalogue_id`),
  KEY `ix_resolutions_cibles_1c7f79acb8` (`auteur_compte_id`),
  CONSTRAINT `ck_resolutions_cibles_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_resolutions_cibles_367e138391` CHECK (`numero` > 0),
  CONSTRAINT `ck_resolutions_cibles_4fbb0c11d4` CHECK (`resultat` IN ('resolue','non_resolue','ambigue')),
  CONSTRAINT `ck_resolutions_cibles_9c12502d75` CHECK (JSON_TYPE(`preuve_resolution`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_resolutions_cibles_34b91e2384` CHECK ((`auteur_compte_id` IS NOT NULL) + (`origine_systeme` IS NOT NULL) = 1),
  CONSTRAINT `ck_resolutions_cibles_8fc1859b15` CHECK (((`oeuvre_id` IS NOT NULL) + (`edition_id` IS NOT NULL) + (`offre_id` IS NOT NULL) + (`media_catalogue_id` IS NOT NULL) + (`avis_id` IS NOT NULL) + (`version_contenu_id` IS NOT NULL)) = CASE WHEN `resultat` = 'resolue' THEN 1 ELSE 0 END)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : droits_invoques
CREATE TABLE `droits_invoques` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `complement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `code` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `description` TEXT NOT NULL,
  `declare_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_droits_invoques_9425755a5b` (`complement_id`, `signalement_id`),
  KEY `ix_droits_invoques_855a2083e7` (`signalement_id`),
  CONSTRAINT `ck_droits_invoques_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_droits_invoques_5f36220b0e` CHECK (`code` IN ('droit_auteur','marque','autre'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : territoires_conformite
CREATE TABLE `territoires_conformite` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `libelle` VARCHAR(512) NOT NULL,
  `definition` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_territoires_conformite_5694d08a2e` UNIQUE (`code`),
  CONSTRAINT `ck_territoires_conformite_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : droits_territoires
CREATE TABLE `droits_territoires` (
  `droit_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `territoire_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`droit_id`, `territoire_id`),
  KEY `ix_droits_territoires_6590bb9872` (`territoire_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : pieces_conformite
CREATE TABLE `pieces_conformite` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_privee` VARCHAR(1024) NOT NULL,
  `empreinte` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `categorie` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `format` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `depose_a` DATETIME(6) NOT NULL,
  `provenance` JSON NOT NULL,
  `statut_controle` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `ck_pieces_conformite_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_pieces_conformite_0166fd5f90` CHECK (JSON_TYPE(`provenance`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_pieces_conformite_8ccb85ac78` CHECK (`statut_controle` IN ('quarantaine','valide','refuse'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : pieces_rattachements
CREATE TABLE `pieces_rattachements` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `piece_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `complement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `reponse_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `date` DATETIME(6) NOT NULL,
  `justification` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_pieces_rattachements_bf53c3ddb5` UNIQUE (`piece_id`, `signalement_id`),
  CONSTRAINT `uq_pieces_rattachements_d9265a8e4a` UNIQUE (`piece_id`, `complement_id`),
  CONSTRAINT `uq_pieces_rattachements_e4083d5a88` UNIQUE (`piece_id`, `reponse_id`),
  KEY `ix_pieces_rattachements_855a2083e7` (`signalement_id`),
  KEY `ix_pieces_rattachements_a650099499` (`complement_id`),
  KEY `ix_pieces_rattachements_8b095d2f13` (`reponse_id`),
  CONSTRAINT `ck_pieces_rattachements_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_pieces_rattachements_a7d030e838` CHECK ((`signalement_id` IS NOT NULL) + (`complement_id` IS NOT NULL) + (`reponse_id` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : controles_completude
CREATE TABLE `controles_completude` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero` BIGINT NOT NULL,
  `resultat` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `manques` JSON NOT NULL,
  `reference_regle` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_elements` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` DATETIME(6) NOT NULL,
  `auteur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_systeme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_controles_completude_14aafe1a5a` UNIQUE (`signalement_id`, `numero`),
  KEY `ix_controles_completude_1c7f79acb8` (`auteur_compte_id`),
  CONSTRAINT `ck_controles_completude_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_controles_completude_367e138391` CHECK (`numero` > 0),
  CONSTRAINT `ck_controles_completude_6d2ea33809` CHECK (`resultat` IN ('passed','failed')),
  CONSTRAINT `ck_controles_completude_5424245e4f` CHECK (JSON_TYPE(`manques`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_controles_completude_34b91e2384` CHECK ((`auteur_compte_id` IS NOT NULL) + (`origine_systeme` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : preuves_completude
CREATE TABLE `preuves_completude` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `controle_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `contenu_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `droit_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `piece_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `complement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `empreinte` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_preuves_completude_0ed016e731` (`controle_id`),
  KEY `ix_preuves_completude_1d6047096b` (`contenu_id`),
  KEY `ix_preuves_completude_0895f4915e` (`droit_id`),
  KEY `ix_preuves_completude_22f58a002c` (`piece_id`),
  KEY `ix_preuves_completude_a650099499` (`complement_id`),
  CONSTRAINT `ck_preuves_completude_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_preuves_completude_d43ac8c238` CHECK ((`contenu_id` IS NOT NULL) + (`droit_id` IS NOT NULL) + (`piece_id` IS NOT NULL) + (`complement_id` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : phases_instruction
CREATE TABLE `phases_instruction` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `numero` BIGINT NOT NULL,
  `precedente_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `nature` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision_contestee_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ouverte_a` DATETIME(6) NOT NULL,
  `close_a` DATETIME(6) NULL,
  `motif` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_phases_instruction_9b825829b3` UNIQUE (`precedente_id`),
  CONSTRAINT `uq_phases_instruction_14aafe1a5a` UNIQUE (`signalement_id`, `numero`),
  CONSTRAINT `uq_phases_instruction_b052c60551` UNIQUE (`id`, `signalement_id`),
  KEY `ix_phases_instruction_dd34ae4c61` (`signalement_id`, `statut`, `ouverte_a`),
  KEY `ix_phases_instruction_e37b7a80bb` (`precedente_id`, `signalement_id`),
  KEY `ix_phases_instruction_efb1d851c5` (`decision_contestee_id`),
  CONSTRAINT `ck_phases_instruction_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_phases_instruction_367e138391` CHECK (`numero` > 0),
  CONSTRAINT `ck_phases_instruction_0031591880` CHECK (`nature` IN ('initiale','recours','reexamen')),
  CONSTRAINT `ck_phases_instruction_af3779f386` CHECK (`statut` IN ('ouverte','under_review','awaiting_provider','decision_pending','decidee','close')),
  CONSTRAINT `ck_phases_instruction_32ca984716` CHECK (`close_a` >= `ouverte_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : affectations_conformite
CREATE TABLE `affectations_conformite` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `phase_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `responsable_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `debut` DATETIME(6) NOT NULL,
  `fin` DATETIME(6) NULL,
  `reference_regle` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `motif` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_affectations_conformite_0b894e36da` (`phase_id`, `debut`, `fin`),
  KEY `ix_affectations_conformite_ea1f499ce8` (`responsable_id`),
  CONSTRAINT `ck_affectations_conformite_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_affectations_conformite_e2d2138eff` CHECK (`fin` > `debut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : acces_dossiers_conformite
CREATE TABLE `acces_dossiers_conformite` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `litige_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `debut` DATETIME(6) NOT NULL,
  `fin` DATETIME(6) NULL,
  `revoque_a` DATETIME(6) NULL,
  `reference_autorisation` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_acces_dossiers_conformite_940f1f0c40` (`compte_id`),
  KEY `ix_acces_dossiers_conformite_855a2083e7` (`signalement_id`),
  KEY `ix_acces_dossiers_conformite_ac97dff02e` (`litige_id`),
  CONSTRAINT `ck_acces_dossiers_conformite_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_acces_dossiers_conformite_70bcf71cc6` CHECK ((`signalement_id` IS NOT NULL) + (`litige_id` IS NOT NULL) = 1),
  CONSTRAINT `ck_acces_dossiers_conformite_e2d2138eff` CHECK (`fin` > `debut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : parties_fournisseur
CREATE TABLE `parties_fournisseur` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `phase_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `qualite` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `organisation_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `vendeur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `contact_verifie` JSON NOT NULL,
  `preuve_mandat` JSON NOT NULL,
  `verifie_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_parties_fournisseur_0d5c6856fb` UNIQUE (`id`, `phase_id`),
  KEY `ix_parties_fournisseur_902b502e39` (`phase_id`),
  KEY `ix_parties_fournisseur_940f1f0c40` (`compte_id`),
  KEY `ix_parties_fournisseur_1b213432c4` (`organisation_id`),
  KEY `ix_parties_fournisseur_59710ce746` (`vendeur_id`),
  CONSTRAINT `ck_parties_fournisseur_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_parties_fournisseur_b5c5c7af0f` CHECK (`qualite` IN ('titulaire','vendeur','representant')),
  CONSTRAINT `ck_parties_fournisseur_c7d756b501` CHECK (JSON_TYPE(`contact_verifie`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_parties_fournisseur_0726d47596` CHECK (JSON_TYPE(`preuve_mandat`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : parties_cibles
CREATE TABLE `parties_cibles` (
  `partie_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resolution_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`partie_id`, `resolution_id`),
  KEY `ix_parties_cibles_4569ddcc79` (`resolution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : acces_reponse_conformite
CREATE TABLE `acces_reponse_conformite` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `partie_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_acces` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_secret` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `preuve_verification` JSON NOT NULL,
  `portee` JSON NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `expire_a` DATETIME(6) NOT NULL,
  `revoque_a` DATETIME(6) NULL,
  `reference_autorisation` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_acces_reponse_conformite_51df92f810` UNIQUE (`reference_acces`),
  CONSTRAINT `uq_acces_reponse_conformite_2d9bf671d7` UNIQUE (`id`, `partie_id`),
  KEY `ix_acces_reponse_conformite_fa9d31b5b8` (`partie_id`),
  CONSTRAINT `ck_acces_reponse_conformite_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_acces_reponse_conformite_16f76ec2de` CHECK (JSON_TYPE(`preuve_verification`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_acces_reponse_conformite_1423c801fc` CHECK (JSON_TYPE(`portee`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_acces_reponse_conformite_f3e549fe05` CHECK (`expire_a` > `cree_a`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : usages_acces_reponse
CREATE TABLE `usages_acces_reponse` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `acces_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` DATETIME(6) NOT NULL,
  `action` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resultat` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `correlation` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_usages_acces_reponse_a172c5d9d4` (`acces_id`),
  CONSTRAINT `ck_usages_acces_reponse_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_usages_acces_reponse_f3284b255f` CHECK (`resultat` IN ('autorise','refuse'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : notifications_fournisseur
CREATE TABLE `notifications_fournisseur` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `partie_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `canal` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `destinataire_snap` JSON NOT NULL,
  `contenu_snap` JSON NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `preparee_a` DATETIME(6) NOT NULL,
  `envoyee_a` DATETIME(6) NULL,
  `livree_a` DATETIME(6) NULL,
  `accuse_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_notifications_fournisseur_52367a6622` UNIQUE (`reference`),
  CONSTRAINT `uq_notifications_fournisseur_2d9bf671d7` UNIQUE (`id`, `partie_id`),
  KEY `ix_notifications_fournisseur_fa9d31b5b8` (`partie_id`),
  CONSTRAINT `ck_notifications_fournisseur_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_notifications_fournisseur_b1e447af39` CHECK (JSON_TYPE(`destinataire_snap`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_notifications_fournisseur_7d82881136` CHECK (JSON_TYPE(`contenu_snap`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_notifications_fournisseur_509fc9b882` CHECK (`statut` IN ('preparee','en_attente','livree','echec'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : faits_notification
CREATE TABLE `faits_notification` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `notification_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `producteur` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_source` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `type` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `survenu_a` DATETIME(6) NOT NULL,
  `recu_a` DATETIME(6) NOT NULL,
  `preuve` JSON NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_faits_notification_96802877b6` UNIQUE (`producteur`, `reference_source`),
  CONSTRAINT `uq_faits_notification_2c376f2058` UNIQUE (`id`, `notification_id`),
  KEY `ix_faits_notification_45159833ee` (`notification_id`),
  CONSTRAINT `ck_faits_notification_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_faits_notification_36980c83f0` CHECK (`type` IN ('envoi','livraison','accuse','echec')),
  CONSTRAINT `ck_faits_notification_0a3ec4dc01` CHECK (JSON_TYPE(`preuve`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : reponses_fournisseur
CREATE TABLE `reponses_fournisseur` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `partie_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `notification_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `acces_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `auteur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `reference_source` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `type` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `corps` JSON NOT NULL,
  `recue_a` DATETIME(6) NOT NULL,
  `preuve_qualite` JSON NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_reponses_fournisseur_4025551488` UNIQUE (`partie_id`, `reference_source`),
  KEY `ix_reponses_fournisseur_a2d7b75921` (`notification_id`, `partie_id`),
  KEY `ix_reponses_fournisseur_ecc4769e32` (`acces_id`, `partie_id`),
  KEY `ix_reponses_fournisseur_1c7f79acb8` (`auteur_compte_id`),
  CONSTRAINT `ck_reponses_fournisseur_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_reponses_fournisseur_b3f59c6566` CHECK (`type` IN ('observations','complement','contestation','retrait_volontaire')),
  CONSTRAINT `ck_reponses_fournisseur_5a674ab570` CHECK (JSON_TYPE(`corps`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_reponses_fournisseur_91bf3a46b2` CHECK (JSON_TYPE(`preuve_qualite`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : parametres_delai_conformite
CREATE TABLE `parametres_delai_conformite` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `code` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version` BIGINT NOT NULL,
  `declencheur` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `duree` DECIMAL(20,8) NOT NULL,
  `unite` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `calendrier_reference` TEXT NOT NULL,
  `fuseau` TEXT NOT NULL,
  `preuve_requise` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `regle_depassement` JSON NOT NULL,
  `valide_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_parametres_delai_conformite_f5b433d863` UNIQUE (`code`, `version`),
  CONSTRAINT `ck_parametres_delai_conformite_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_parametres_delai_conformite_97a58d3a38` CHECK (`version` > 0),
  CONSTRAINT `ck_parametres_delai_conformite_1b8df4ab3c` CHECK (JSON_TYPE(`regle_depassement`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_parametres_delai_conformite_ed1189598a` CHECK (`duree` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : echeances_conformite
CREATE TABLE `echeances_conformite` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `phase_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `partie_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `notification_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `parametre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `fait_notification_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `preuve_autre_declencheur` JSON NULL,
  `debut` DATETIME(6) NULL,
  `echeance` DATETIME(6) NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_echeances_conformite_67cefabb4f` (`statut`, `echeance`),
  KEY `ix_echeances_conformite_3d05ed0fef` (`partie_id`, `phase_id`),
  KEY `ix_echeances_conformite_a2d7b75921` (`notification_id`, `partie_id`),
  KEY `ix_echeances_conformite_8693d56dfc` (`fait_notification_id`, `notification_id`),
  KEY `ix_echeances_conformite_902b502e39` (`phase_id`),
  KEY `ix_echeances_conformite_eed6930686` (`parametre_id`),
  CONSTRAINT `ck_echeances_conformite_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_echeances_conformite_6a1e8d1a51` CHECK (JSON_TYPE(`preuve_autre_declencheur`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_echeances_conformite_84cfa24400` CHECK (`statut` IN ('en_attente_declencheur','en_cours','atteinte','close')),
  CONSTRAINT `ck_echeances_conformite_10283d3324` CHECK ((`statut` = 'en_attente_declencheur' AND `debut` IS NULL AND `echeance` IS NULL) OR (`statut` <> 'en_attente_declencheur' AND `debut` IS NOT NULL AND `echeance` IS NOT NULL AND ((`fait_notification_id` IS NOT NULL) + (`preuve_autre_declencheur` IS NOT NULL)) = 1)),
  CONSTRAINT `ck_echeances_conformite_4d2bfbb5e8` CHECK (`fait_notification_id` IS NULL OR (`notification_id` IS NOT NULL AND `partie_id` IS NOT NULL)),
  CONSTRAINT `ck_echeances_conformite_cc79160491` CHECK (`echeance` >= `debut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : ajustements_echeance
CREATE TABLE `ajustements_echeance` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `echeance_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ancien_etat` JSON NOT NULL,
  `nouvel_etat` JSON NOT NULL,
  `date` DATETIME(6) NOT NULL,
  `motif` TEXT NOT NULL,
  `auteur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_systeme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`id`),
  KEY `ix_ajustements_echeance_d6ba07e399` (`echeance_id`),
  KEY `ix_ajustements_echeance_1c7f79acb8` (`auteur_compte_id`),
  CONSTRAINT `ck_ajustements_echeance_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_ajustements_echeance_a451813d6c` CHECK (JSON_TYPE(`ancien_etat`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_ajustements_echeance_5a9562d49a` CHECK (JSON_TYPE(`nouvel_etat`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_ajustements_echeance_34b91e2384` CHECK ((`auteur_compte_id` IS NOT NULL) + (`origine_systeme` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : decisions_moderation
CREATE TABLE `decisions_moderation` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `phase_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` TEXT NOT NULL,
  `decide_a` DATETIME(6) NOT NULL,
  `decideur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `preuve_independance` JSON NOT NULL,
  `correlation` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_decisions_moderation_902b502e39` UNIQUE (`phase_id`),
  KEY `ix_decisions_moderation_d6d83fdc33` (`decideur_id`),
  CONSTRAINT `ck_decisions_moderation_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_decisions_moderation_2fbb6e2f14` CHECK (`type` IN ('retrait','maintien','retablissement','classement')),
  CONSTRAINT `ck_decisions_moderation_f92f1259c7` CHECK (JSON_TYPE(`preuve_independance`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : mesures_conservatoires
CREATE TABLE `mesures_conservatoires` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `phase_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` TEXT NOT NULL,
  `decideur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_regle` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `decide_a` DATETIME(6) NOT NULL,
  `debut` DATETIME(6) NOT NULL,
  `fin` DATETIME(6) NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_mesures_conservatoires_902b502e39` (`phase_id`),
  KEY `ix_mesures_conservatoires_d6d83fdc33` (`decideur_id`),
  CONSTRAINT `ck_mesures_conservatoires_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_mesures_conservatoires_c93d5afa70` CHECK (`statut` IN ('demandee','active','levee','expiree')),
  CONSTRAINT `ck_mesures_conservatoires_e2d2138eff` CHECK (`fin` > `debut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : portees_decisions_mesures
CREATE TABLE `portees_decisions_mesures` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `mesure_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `resolution_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `effet` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_portees_decisions_mesures_3d7b176fd7` (`decision_id`),
  KEY `ix_portees_decisions_mesures_04cdbb6725` (`mesure_id`),
  KEY `ix_portees_decisions_mesures_4569ddcc79` (`resolution_id`),
  CONSTRAINT `ck_portees_decisions_mesures_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_portees_decisions_mesures_0edfa52eef` CHECK ((`decision_id` IS NOT NULL) + (`mesure_id` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : preuves_decisions_mesures
CREATE TABLE `preuves_decisions_mesures` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `mesure_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `piece_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `controle_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `reponse_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `empreinte` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `role_preuve` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` TEXT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_preuves_decisions_mesures_3d7b176fd7` (`decision_id`),
  KEY `ix_preuves_decisions_mesures_04cdbb6725` (`mesure_id`),
  KEY `ix_preuves_decisions_mesures_22f58a002c` (`piece_id`),
  KEY `ix_preuves_decisions_mesures_0ed016e731` (`controle_id`),
  KEY `ix_preuves_decisions_mesures_8b095d2f13` (`reponse_id`),
  CONSTRAINT `ck_preuves_decisions_mesures_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_preuves_decisions_mesures_0edfa52eef` CHECK ((`decision_id` IS NOT NULL) + (`mesure_id` IS NOT NULL) = 1),
  CONSTRAINT `ck_preuves_decisions_mesures_bf1d774962` CHECK ((`piece_id` IS NOT NULL) + (`controle_id` IS NOT NULL) + (`reponse_id` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : ordres_retrait
CREATE TABLE `ordres_retrait` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_intention` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `mesure_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `execute_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_ordres_retrait_52367a6622` UNIQUE (`reference`),
  KEY `ix_ordres_retrait_1b624651ed` (`statut`, `cree_a`),
  KEY `ix_ordres_retrait_3d7b176fd7` (`decision_id`),
  KEY `ix_ordres_retrait_04cdbb6725` (`mesure_id`),
  CONSTRAINT `ck_ordres_retrait_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_ordres_retrait_400b858b71` CHECK (`statut` IN ('a_executer','execute','echec','partiel','annule')),
  CONSTRAINT `ck_ordres_retrait_0edfa52eef` CHECK ((`decision_id` IS NOT NULL) + (`mesure_id` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : lignes_ordres_retrait
CREATE TABLE `lignes_ordres_retrait` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ordre_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resolution_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `portee_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `action` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_effet` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `restriction_levee_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `effet_a` DATETIME(6) NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_lignes_ordres_retrait_a280b897e4` UNIQUE (`reference_effet`),
  KEY `ix_lignes_ordres_retrait_d42c73ee45` (`resolution_id`, `action`, `effet_a`),
  KEY `ix_lignes_ordres_retrait_634d3046dc` (`statut`, `effet_a`),
  KEY `ix_lignes_ordres_retrait_352fe72186` (`ordre_id`),
  KEY `ix_lignes_ordres_retrait_5e63526464` (`portee_id`),
  KEY `ix_lignes_ordres_retrait_1c3fce3839` (`restriction_levee_id`),
  CONSTRAINT `ck_lignes_ordres_retrait_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_lignes_ordres_retrait_f7b6713819` CHECK (`action` IN ('arreter_ventes','retirer_catalogue','masquer_media_public','masquer_avis','interdire_diffusion','masquer_titre_prive','lever_restriction')),
  CONSTRAINT `ck_lignes_ordres_retrait_e6d346fbef` CHECK (`statut` IN ('en_attente','confirmee','refusee','erreur','annulee')),
  CONSTRAINT `ck_lignes_ordres_retrait_07ca5c33f6` CHECK ((`action` = 'lever_restriction') = (`restriction_levee_id` IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : developpements_portee
CREATE TABLE `developpements_portee` (
  `portee_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resolution_enfant_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `developpe_a` DATETIME(6) NOT NULL,
  `preuve_perimetre` JSON NOT NULL,
  PRIMARY KEY (`portee_id`, `resolution_enfant_id`),
  KEY `ix_developpements_portee_3f7341f87c` (`resolution_enfant_id`),
  CONSTRAINT `ck_developpements_portee_9234696308` CHECK (JSON_TYPE(`preuve_perimetre`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : restrictions_conformite
CREATE TABLE `restrictions_conformite` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_creatrice_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `instruction_publication_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `cause` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `debut` DATETIME(6) NOT NULL,
  `fin` DATETIME(6) NULL,
  `levee_decidee_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_restrictions_conformite_891eec8886` UNIQUE (`ligne_creatrice_id`),
  CONSTRAINT `uq_restrictions_conformite_9cb352ee38` UNIQUE (`instruction_publication_id`),
  CONSTRAINT `uq_restrictions_conformite_6a7ef1ed9f` UNIQUE (`cause`),
  KEY `ix_restrictions_conformite_34b7c40804` (`debut`, `fin`),
  CONSTRAINT `ck_restrictions_conformite_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_restrictions_conformite_68f71e7dce` CHECK ((`ligne_creatrice_id` IS NOT NULL) + (`instruction_publication_id` IS NOT NULL) = 1),
  CONSTRAINT `ck_restrictions_conformite_e2d2138eff` CHECK (`fin` > `debut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : confirmations_effet
CREATE TABLE `confirmations_effet` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `autorite` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_demande` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `recu_instruction_a` DATETIME(6) NULL,
  `applique_a` DATETIME(6) NULL,
  `resultat` JSON NULL,
  `revision_source` TEXT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_confirmations_effet_76bbdb8964` UNIQUE (`reference_demande`),
  CONSTRAINT `uq_confirmations_effet_360bb66e99` UNIQUE (`ligne_id`, `autorite`),
  CONSTRAINT `ck_confirmations_effet_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_confirmations_effet_a98bb45d23` CHECK (`statut` IN ('en_attente','confirme','refuse','erreur')),
  CONSTRAINT `ck_confirmations_effet_57a028ab8d` CHECK (JSON_TYPE(`resultat`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : contestations_paiement
CREATE TABLE `contestations_paiement` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `producteur` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_litige` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `source` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `montant_indicatif` DECIMAL(20,4) NULL,
  `devise` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `ouvert_a` DATETIME(6) NOT NULL,
  `clos_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_contestations_paiement_0b94ae01cf` UNIQUE (`producteur`, `reference_litige`),
  CONSTRAINT `ck_contestations_paiement_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_contestations_paiement_8015a17a8c` CHECK (`source` IN ('psp','client','interne')),
  CONSTRAINT `ck_contestations_paiement_d2e7731786` CHECK (`statut` IN ('ouvert','under_review','decision_pending','clos')),
  CONSTRAINT `ck_contestations_paiement_2b66546cf0` CHECK (`montant_indicatif` >= 0),
  CONSTRAINT `ck_contestations_paiement_dc389a0ff5` CHECK (`devise` IN ('CDF','USD'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : faits_litige
CREATE TABLE `faits_litige` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `litige_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `producteur` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_fait` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `survenu_a` DATETIME(6) NOT NULL,
  `recu_a` DATETIME(6) NOT NULL,
  `preuve` JSON NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_faits_litige_343e17312e` UNIQUE (`producteur`, `reference_fait`),
  KEY `ix_faits_litige_ac97dff02e` (`litige_id`),
  CONSTRAINT `ck_faits_litige_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_faits_litige_0a3ec4dc01` CHECK (JSON_TYPE(`preuve`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : litiges_paiements
CREATE TABLE `litiges_paiements` (
  `litige_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `paiement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`litige_id`, `paiement_id`),
  KEY `ix_litiges_paiements_68d4d872e4` (`paiement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : litiges_commandes
CREATE TABLE `litiges_commandes` (
  `litige_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`litige_id`, `commande_id`),
  KEY `ix_litiges_commandes_94c4e6db2b` (`commande_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : litiges_anomalies
CREATE TABLE `litiges_anomalies` (
  `litige_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `anomalie_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`litige_id`, `anomalie_id`),
  KEY `ix_litiges_anomalies_28885fb289` (`anomalie_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : instructions_conformite_paiement
CREATE TABLE `instructions_conformite_paiement` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `litige_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `type` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` TEXT NOT NULL,
  `decide_a` DATETIME(6) NOT NULL,
  `effet_a` DATETIME(6) NOT NULL,
  `reference_regle` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  `gel_leve_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `auteur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_systeme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_instructions_conformite_paiement_52367a6622` UNIQUE (`reference`),
  KEY `ix_instructions_conformite_paiement_ac97dff02e` (`litige_id`),
  KEY `ix_instructions_conformite_paiement_3d20b9fac5` (`gel_leve_id`),
  KEY `ix_instructions_conformite_paiement_1c7f79acb8` (`auteur_compte_id`),
  CONSTRAINT `ck_instructions_conformite_paiement_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_instructions_conformite_paiement_a6ce380725` CHECK (`type` IN ('geler_refunds','lever_gel_refunds','autoriser_refund','demander_revocation_droits','demander_examen_compte','classer')),
  CONSTRAINT `ck_instructions_conformite_paiement_34b91e2384` CHECK ((`auteur_compte_id` IS NOT NULL) + (`origine_systeme` IS NOT NULL) = 1),
  CONSTRAINT `ck_instructions_conformite_paiement_6b361ce1a4` CHECK ((`type` = 'lever_gel_refunds') = (`gel_leve_id` IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : cibles_instructions_paiement
CREATE TABLE `cibles_instructions_paiement` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `instruction_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `paiement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_cibles_instructions_paiement_c8542e12ec` UNIQUE (`instruction_id`, `paiement_id`),
  CONSTRAINT `uq_cibles_instructions_paiement_1b9ad0974c` UNIQUE (`instruction_id`, `commande_id`),
  CONSTRAINT `uq_cibles_instructions_paiement_f2f7c7c1db` UNIQUE (`instruction_id`, `compte_id`),
  KEY `ix_cibles_instructions_paiement_68d4d872e4` (`paiement_id`),
  KEY `ix_cibles_instructions_paiement_94c4e6db2b` (`commande_id`),
  KEY `ix_cibles_instructions_paiement_940f1f0c40` (`compte_id`),
  CONSTRAINT `ck_cibles_instructions_paiement_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_cibles_instructions_paiement_a9b5a82d44` CHECK ((`paiement_id` IS NOT NULL) + (`commande_id` IS NOT NULL) + (`compte_id` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : gels_operations_financieres
CREATE TABLE `gels_operations_financieres` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `instruction_creatrice_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cause` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `debut` DATETIME(6) NOT NULL,
  `fin` DATETIME(6) NULL,
  `leve_a` DATETIME(6) NULL,
  `leve_effet_a` DATETIME(6) NULL,
  `operations_bloquees` JSON NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_gels_operations_financieres_6a7ef1ed9f` UNIQUE (`cause`),
  KEY `ix_gels_operations_financieres_1543a52274` (`debut`, `fin`, `leve_effet_a`),
  KEY `ix_gels_operations_financieres_242216b08f` (`instruction_creatrice_id`),
  CONSTRAINT `ck_gels_operations_financieres_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_gels_operations_financieres_668f73938b` CHECK (JSON_TYPE(`operations_bloquees`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_gels_operations_financieres_9bf9a1abba` CHECK ((`leve_a` IS NULL) = (`leve_effet_a` IS NULL)),
  CONSTRAINT `ck_gels_operations_financieres_94c43110fb` CHECK (JSON_TYPE(`operations_bloquees`) = 'ARRAY' AND JSON_LENGTH(`operations_bloquees`) BETWEEN 1 AND 2 AND JSON_CONTAINS(JSON_ARRAY('refund_vente','refund_excedent'), `operations_bloquees`) = 1),
  CONSTRAINT `ck_gels_operations_financieres_9c6b7c648c` CHECK (JSON_LENGTH(`operations_bloquees`) <> 2 OR JSON_EXTRACT(`operations_bloquees`,'$[0]') <> JSON_EXTRACT(`operations_bloquees`,'$[1]')),
  CONSTRAINT `ck_gels_operations_financieres_e2d2138eff` CHECK (`fin` > `debut`),
  CONSTRAINT `ck_gels_operations_financieres_c070acb860` CHECK (`leve_effet_a` >= `debut`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : cibles_gels_financiers
CREATE TABLE `cibles_gels_financiers` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `gel_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `paiement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_cibles_gels_financiers_d7f10cd424` UNIQUE (`gel_id`, `paiement_id`),
  CONSTRAINT `uq_cibles_gels_financiers_50497c5763` UNIQUE (`gel_id`, `commande_id`),
  KEY `ix_cibles_gels_financiers_68d4d872e4` (`paiement_id`),
  KEY `ix_cibles_gels_financiers_94c4e6db2b` (`commande_id`),
  CONSTRAINT `ck_cibles_gels_financiers_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_cibles_gels_financiers_bd0f86fea9` CHECK ((`paiement_id` IS NOT NULL) + (`commande_id` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : demandes_revocation_origines
CREATE TABLE `demandes_revocation_origines` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_intention` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `decision_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `instruction_paiement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `motif` TEXT NOT NULL,
  `effet_a` DATETIME(6) NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_demandes_revocation_origines_52367a6622` UNIQUE (`reference`),
  KEY `ix_demandes_revocation_origines_3d7b176fd7` (`decision_id`),
  KEY `ix_demandes_revocation_origines_343ce2e3d7` (`instruction_paiement_id`),
  CONSTRAINT `ck_demandes_revocation_origines_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_demandes_revocation_origines_82020c3111` CHECK ((`decision_id` IS NOT NULL) + (`instruction_paiement_id` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : cibles_revocation_origine
CREATE TABLE `cibles_revocation_origine` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `demande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `producteur_acquisition` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_demande_acquisition` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `origine_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `reference_instruction_a4` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `confirme_a` DATETIME(6) NULL,
  `resultat` JSON NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_cibles_revocation_origine_28fa5dd9d7` UNIQUE (`reference_instruction_a4`),
  CONSTRAINT `uq_cibles_revocation_origine_8cb161530b` UNIQUE (`demande_id`, `producteur_acquisition`, `reference_demande_acquisition`),
  KEY `ix_cibles_revocation_origine_ee38579093` (`origine_id`),
  CONSTRAINT `ck_cibles_revocation_origine_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_cibles_revocation_origine_85e952d708` CHECK (`statut` IN ('en_attente','confirmee','refusee')),
  CONSTRAINT `ck_cibles_revocation_origine_57a028ab8d` CHECK (JSON_TYPE(`resultat`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : demandes_compensation_conformite
CREATE TABLE `demandes_compensation_conformite` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `empreinte_intention` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `litige_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `decision_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_reutilisation_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `motif` TEXT NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `auteur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resultat` JSON NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_demandes_compensation_conformite_52367a6622` UNIQUE (`reference`),
  KEY `ix_demandes_compensation_conformite_3202030bfd` (`dossier_reutilisation_id`, `commande_id`),
  KEY `ix_demandes_compensation_conformite_855a2083e7` (`signalement_id`),
  KEY `ix_demandes_compensation_conformite_ac97dff02e` (`litige_id`),
  KEY `ix_demandes_compensation_conformite_3d7b176fd7` (`decision_id`),
  KEY `ix_demandes_compensation_conformite_94c4e6db2b` (`commande_id`),
  KEY `ix_demandes_compensation_conformite_6e38b1ca93` (`auteur_id`),
  CONSTRAINT `ck_demandes_compensation_conformite_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_demandes_compensation_conformite_35d357007d` CHECK (`statut` IN ('en_attente','liee','refusee')),
  CONSTRAINT `ck_demandes_compensation_conformite_57a028ab8d` CHECK (JSON_TYPE(`resultat`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_demandes_compensation_conformite_db65aa4c26` CHECK (`signalement_id` IS NOT NULL OR `litige_id` IS NOT NULL),
  CONSTRAINT `ck_demandes_compensation_conformite_12379ce68b` CHECK (`decision_id` IS NULL OR `signalement_id` IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : lignes_compensation_conformite
CREATE TABLE `lignes_compensation_conformite` (
  `demande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_commande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `effet_droit_demande` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `motif` TEXT NOT NULL,
  PRIMARY KEY (`demande_id`, `ligne_commande_id`),
  KEY `ix_lignes_compensation_conformite_3ff8abbe12` (`ligne_commande_id`),
  CONSTRAINT `ck_lignes_compensation_conformite_46ff164c5a` CHECK (`effet_droit_demande` IN ('aucun','revocation'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : liaisons_compensation
CREATE TABLE `liaisons_compensation` (
  `demande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_remboursement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `lie_a` DATETIME(6) NOT NULL,
  `reference_reponse` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  PRIMARY KEY (`demande_id`),
  KEY `ix_liaisons_compensation_016767a439` (`dossier_remboursement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : resultats_compensation
CREATE TABLE `resultats_compensation` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `dossier_remboursement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `producteur` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_fait` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `nature` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `resultat` JSON NOT NULL,
  `recu_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_resultats_compensation_343e17312e` UNIQUE (`producteur`, `reference_fait`),
  KEY `ix_resultats_compensation_016767a439` (`dossier_remboursement_id`),
  CONSTRAINT `ck_resultats_compensation_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_resultats_compensation_a2e62f940c` CHECK (`nature` IN ('decision_montant','refund','avoir','droit')),
  CONSTRAINT `ck_resultats_compensation_57a028ab8d` CHECK (JSON_TYPE(`resultat`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : politiques_conservation
CREATE TABLE `politiques_conservation` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `categorie` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version` BIGINT NOT NULL,
  `finalite` TEXT NOT NULL,
  `base_reference` TEXT NOT NULL,
  `declencheur` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `duree_active` DECIMAL(20,8) NOT NULL,
  `duree_archive` DECIMAL(20,8) NOT NULL,
  `unite` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `regle_action` JSON NOT NULL,
  `date_effet` DATETIME(6) NOT NULL,
  `valide_a` DATETIME(6) NULL,
  `valide_par` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_politiques_conservation_be4bf3b7d7` UNIQUE (`categorie`, `version`),
  KEY `ix_politiques_conservation_5cfe14a9a7` (`valide_par`),
  CONSTRAINT `ck_politiques_conservation_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_politiques_conservation_97a58d3a38` CHECK (`version` > 0),
  CONSTRAINT `ck_politiques_conservation_d890bcfefc` CHECK (JSON_TYPE(`regle_action`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_politiques_conservation_c414aa3226` CHECK (`duree_active` >= 0 AND `duree_archive` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : objets_conserves
CREATE TABLE `objets_conserves` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `proprietaire` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type_objet` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `adaptateur_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_objet` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `version_objet` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `categorie` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference_preuve` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  `empreinte` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `constate_a` DATETIME(6) NOT NULL,
  `etat` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_objets_conserves_2ae5636f5d` UNIQUE (`proprietaire`, `type_objet`, `reference_objet`, `version_objet`),
  KEY `ix_objets_conserves_269aeee7f6` (`adaptateur_id`, `proprietaire`, `type_objet`, `categorie`),
  KEY `ix_objets_conserves_603be81455` (`categorie`, `etat`, `constate_a`),
  CONSTRAINT `ck_objets_conserves_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_objets_conserves_af3fc00e8a` CHECK (`etat` IN ('disponible','archive','detruit'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : objets_dossiers_conformite
CREATE TABLE `objets_dossiers_conformite` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `objet_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `litige_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `lie_a` DATETIME(6) NOT NULL,
  `justification` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_objets_dossiers_conformite_27074f7c24` UNIQUE (`objet_id`, `signalement_id`),
  CONSTRAINT `uq_objets_dossiers_conformite_8091f350b1` UNIQUE (`objet_id`, `litige_id`),
  KEY `ix_objets_dossiers_conformite_855a2083e7` (`signalement_id`),
  KEY `ix_objets_dossiers_conformite_ac97dff02e` (`litige_id`),
  CONSTRAINT `ck_objets_dossiers_conformite_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_objets_dossiers_conformite_70bcf71cc6` CHECK ((`signalement_id` IS NOT NULL) + (`litige_id` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : applications_conservation
CREATE TABLE `applications_conservation` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `objet_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `politique_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `debut_application` DATETIME(6) NOT NULL,
  `fin_application` DATETIME(6) NULL,
  `preuve_declencheur` JSON NULL,
  `depart` DATETIME(6) NULL,
  `archive_prevu_a` DATETIME(6) NULL,
  `destruction_prevue_a` DATETIME(6) NULL,
  `motif_adoption` TEXT NOT NULL,
  `uq_application_courante` TINYINT UNSIGNED GENERATED ALWAYS AS (CASE WHEN `fin_application` IS NULL THEN 1 ELSE NULL END) VIRTUAL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_applications_conservation_47776f930b` UNIQUE (`objet_id`, `uq_application_courante`),
  CONSTRAINT `uq_applications_conservation_3f10e7e1d8` UNIQUE (`id`, `objet_id`),
  KEY `ix_applications_conservation_f2f13b571c` (`fin_application`, `destruction_prevue_a`),
  KEY `ix_applications_conservation_169d85acce` (`politique_id`),
  CONSTRAINT `ck_applications_conservation_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_applications_conservation_7640f85bef` CHECK (JSON_TYPE(`preuve_declencheur`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_applications_conservation_055e7a708f` CHECK (`depart` IS NOT NULL OR (`archive_prevu_a` IS NULL AND `destruction_prevue_a` IS NULL)),
  CONSTRAINT `ck_applications_conservation_df4006755a` CHECK (`destruction_prevue_a` >= `archive_prevu_a`),
  CONSTRAINT `ck_applications_conservation_f1d7021fcc` CHECK (`fin_application` > `debut_application`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : gels_conservation
CREATE TABLE `gels_conservation` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `litige_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `cause` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `motif` TEXT NOT NULL,
  `debut` DATETIME(6) NOT NULL,
  `leve_a` DATETIME(6) NULL,
  `reference_regle` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `auteur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_systeme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_gels_conservation_6a7ef1ed9f` UNIQUE (`cause`),
  KEY `ix_gels_conservation_407603efa5` (`signalement_id`, `leve_a`),
  KEY `ix_gels_conservation_a520c31441` (`litige_id`, `leve_a`),
  KEY `ix_gels_conservation_1c7f79acb8` (`auteur_compte_id`),
  CONSTRAINT `ck_gels_conservation_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_gels_conservation_70bcf71cc6` CHECK ((`signalement_id` IS NOT NULL) + (`litige_id` IS NOT NULL) = 1),
  CONSTRAINT `ck_gels_conservation_34b91e2384` CHECK ((`auteur_compte_id` IS NOT NULL) + (`origine_systeme` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : categories_gel
CREATE TABLE `categories_gel` (
  `gel_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `categorie` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`gel_id`, `categorie`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : objets_geles
CREATE TABLE `objets_geles` (
  `gel_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `objet_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `rattache_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`gel_id`, `objet_id`),
  KEY `ix_objets_geles_afd38cd64f` (`objet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : levees_gels_conservation
CREATE TABLE `levees_gels_conservation` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `gel_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` DATETIME(6) NOT NULL,
  `motif` TEXT NOT NULL,
  `preuve_fin_cause` JSON NOT NULL,
  `reference_regle` VARCHAR(191) COLLATE utf8mb4_0900_bin NULL,
  `auteur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_systeme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_levees_gels_conservation_6bde880050` UNIQUE (`gel_id`),
  KEY `ix_levees_gels_conservation_1c7f79acb8` (`auteur_compte_id`),
  CONSTRAINT `ck_levees_gels_conservation_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_levees_gels_conservation_afcd1ea26b` CHECK (JSON_TYPE(`preuve_fin_cause`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_levees_gels_conservation_34b91e2384` CHECK ((`auteur_compte_id` IS NOT NULL) + (`origine_systeme` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : suivis_instructions_externes
CREATE TABLE `suivis_instructions_externes` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `instruction_paiement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `gel_conservation_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `levee_gel_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `cible_instruction_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `objet_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `destinataire` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `action` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `recu_a` DATETIME(6) NULL,
  `applique_a` DATETIME(6) NULL,
  `resultat` JSON NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_suivis_instructions_externes_52367a6622` UNIQUE (`reference`),
  KEY `ix_suivis_instructions_externes_38558487f1` (`statut`, `destinataire`),
  KEY `ix_suivis_instructions_externes_343ce2e3d7` (`instruction_paiement_id`),
  KEY `ix_suivis_instructions_externes_f9abbd3f0c` (`gel_conservation_id`),
  KEY `ix_suivis_instructions_externes_ff0e989f22` (`levee_gel_id`),
  KEY `ix_suivis_instructions_externes_277a6c4d94` (`cible_instruction_id`),
  KEY `ix_suivis_instructions_externes_afd38cd64f` (`objet_id`),
  CONSTRAINT `ck_suivis_instructions_externes_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_suivis_instructions_externes_a98bb45d23` CHECK (`statut` IN ('en_attente','confirme','refuse','erreur')),
  CONSTRAINT `ck_suivis_instructions_externes_57a028ab8d` CHECK (JSON_TYPE(`resultat`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_suivis_instructions_externes_59ccc31f3e` CHECK ((`instruction_paiement_id` IS NOT NULL) + (`gel_conservation_id` IS NOT NULL) + (`levee_gel_id` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : actions_conservation
CREATE TABLE `actions_conservation` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `objet_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `application_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `reference` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `action` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `cree_a` DATETIME(6) NOT NULL,
  `preuve_eligibilite` JSON NOT NULL,
  `statut` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `confirme_a` DATETIME(6) NULL,
  `resultat` JSON NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_actions_conservation_52367a6622` UNIQUE (`reference`),
  KEY `ix_actions_conservation_1b624651ed` (`statut`, `cree_a`),
  KEY `ix_actions_conservation_8df4fbd3d7` (`application_id`, `objet_id`),
  KEY `ix_actions_conservation_afd38cd64f` (`objet_id`),
  CONSTRAINT `ck_actions_conservation_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_actions_conservation_1ac834516f` CHECK (`action` IN ('archivage','destruction','anonymisation')),
  CONSTRAINT `ck_actions_conservation_64d1261745` CHECK (JSON_TYPE(`preuve_eligibilite`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_actions_conservation_a98bb45d23` CHECK (`statut` IN ('en_attente','confirme','refuse','erreur')),
  CONSTRAINT `ck_actions_conservation_57a028ab8d` CHECK (JSON_TYPE(`resultat`) IN ('OBJECT','ARRAY'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : evenements_conformite
CREATE TABLE `evenements_conformite` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `signalement_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `litige_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `politique_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `objet_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `phase_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `type` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `date` DATETIME(6) NOT NULL,
  `motif` TEXT NOT NULL,
  `ancien_etat` JSON NULL,
  `nouvel_etat` JSON NULL,
  `correlation` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `auteur_compte_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `origine_systeme` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NULL,
  PRIMARY KEY (`id`),
  KEY `ix_evenements_conformite_841cb5934d` (`phase_id`, `signalement_id`),
  KEY `ix_evenements_conformite_855a2083e7` (`signalement_id`),
  KEY `ix_evenements_conformite_ac97dff02e` (`litige_id`),
  KEY `ix_evenements_conformite_169d85acce` (`politique_id`),
  KEY `ix_evenements_conformite_afd38cd64f` (`objet_id`),
  KEY `ix_evenements_conformite_1c7f79acb8` (`auteur_compte_id`),
  CONSTRAINT `ck_evenements_conformite_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_evenements_conformite_a451813d6c` CHECK (JSON_TYPE(`ancien_etat`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_evenements_conformite_5a9562d49a` CHECK (JSON_TYPE(`nouvel_etat`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_evenements_conformite_d42a4d93cc` CHECK ((`signalement_id` IS NOT NULL) + (`litige_id` IS NOT NULL) + (`politique_id` IS NOT NULL) + (`objet_id` IS NOT NULL) = 1),
  CONSTRAINT `ck_evenements_conformite_34b91e2384` CHECK ((`auteur_compte_id` IS NOT NULL) + (`origine_systeme` IS NOT NULL) = 1),
  CONSTRAINT `ck_evenements_conformite_5adda685f1` CHECK (`phase_id` IS NULL OR `signalement_id` IS NOT NULL)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : levees_restrictions
CREATE TABLE `levees_restrictions` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `restriction_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `ligne_levee_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `instruction_levee_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NULL,
  `reference` VARCHAR(191) COLLATE utf8mb4_0900_bin NOT NULL,
  `decide_a` DATETIME(6) NOT NULL,
  `effet_a` DATETIME(6) NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_levees_restrictions_2b1ac88a3b` UNIQUE (`ligne_levee_id`),
  CONSTRAINT `uq_levees_restrictions_09d2996498` UNIQUE (`instruction_levee_id`),
  CONSTRAINT `uq_levees_restrictions_52367a6622` UNIQUE (`reference`),
  KEY `ix_levees_restrictions_9ad28b56f7` (`restriction_id`, `effet_a`),
  CONSTRAINT `ck_levees_restrictions_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_levees_restrictions_293402481b` CHECK ((`ligne_levee_id` IS NOT NULL) + (`instruction_levee_id` IS NOT NULL) = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : adaptateurs_conservation
CREATE TABLE `adaptateurs_conservation` (
  `id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `proprietaire` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `type_objet` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `version` BIGINT NOT NULL,
  `categorie` VARCHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `schema_reference` JSON NOT NULL,
  `capacite_barriere` VARCHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `preuve_validation` TEXT NULL,
  `valide_a` DATETIME(6) NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `uq_adaptateurs_conservation_0ccb3db80a` UNIQUE (`proprietaire`, `type_objet`, `version`),
  CONSTRAINT `uq_adaptateurs_conservation_718908be78` UNIQUE (`id`, `proprietaire`, `type_objet`, `categorie`),
  CONSTRAINT `ck_adaptateurs_conservation_362b6ab488` CHECK (REGEXP_LIKE(`id`, '^[0-7][0-9A-HJKMNP-TV-Z]{25}$', 'c')),
  CONSTRAINT `ck_adaptateurs_conservation_97a58d3a38` CHECK (`version` > 0),
  CONSTRAINT `ck_adaptateurs_conservation_7ee6baa4df` CHECK (JSON_TYPE(`schema_reference`) IN ('OBJECT','ARRAY')),
  CONSTRAINT `ck_adaptateurs_conservation_c102d112de` CHECK (`capacite_barriere` IN ('non_validee','validee')),
  CONSTRAINT `ck_adaptateurs_conservation_5456d1ebc8` CHECK (`capacite_barriere` <> 'validee' OR (`preuve_validation` IS NOT NULL AND `valide_a` IS NOT NULL))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- A6 : rattachements_resultats_compensation
CREATE TABLE `rattachements_resultats_compensation` (
  `resultat_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `demande_id` CHAR(26) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  PRIMARY KEY (`resultat_id`, `demande_id`),
  KEY `ix_rattachements_resultats_compensatio_bed904885e` (`demande_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_bin ROW_FORMAT=DYNAMIC;

-- Afrique-Educ | MPD V1.0 | sources MLD/MOT V1.3 | MySQL 8.4 / InnoDB
-- Installation initiale dans une base VIDE explicitement choisie. Ne pas utiliser --force.
-- ID ULID fournis par application. Aucun DROP, aucun FOREIGN_KEY_CHECKS=0.
SET NAMES utf8mb4 COLLATE utf8mb4_0900_bin;
SET SESSION time_zone = '+00:00';
SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED;
SET SESSION sql_mode = 'STRICT_ALL_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION,ONLY_FULL_GROUP_BY';

-- Deuxième passe : toutes les tables et clés candidates existent.
ALTER TABLE `profils_particuliers` ADD CONSTRAINT `fk_profils_particuliers_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `interets_profils` ADD CONSTRAINT `fk_interets_profils_6b80fbc851` FOREIGN KEY (`compte_id`) REFERENCES `profils_particuliers` (`compte_id`);
ALTER TABLE `organisations` ADD CONSTRAINT `fk_organisations_d561fb6580` FOREIGN KEY (`type_organisation_id`) REFERENCES `types_organisation` (`id`);
ALTER TABLE `adhesions_organisation` ADD CONSTRAINT `fk_adhesions_organisation_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `adhesions_organisation` ADD CONSTRAINT `fk_adhesions_organisation_d73eda69b5` FOREIGN KEY (`organisation_id`) REFERENCES `organisations` (`id`);
ALTER TABLE `adhesions_permissions` ADD CONSTRAINT `fk_adhesions_permissions_f6dd176b08` FOREIGN KEY (`adhesion_id`) REFERENCES `adhesions_organisation` (`id`);
ALTER TABLE `adhesions_permissions` ADD CONSTRAINT `fk_adhesions_permissions_ead0d9dd58` FOREIGN KEY (`permission_id`) REFERENCES `permissions_organisation` (`id`);
ALTER TABLE `comptes_roles` ADD CONSTRAINT `fk_comptes_roles_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `comptes_roles` ADD CONSTRAINT `fk_comptes_roles_dcd3b743d9` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`);
ALTER TABLE `comptes_roles` ADD CONSTRAINT `fk_comptes_roles_67827c7edb` FOREIGN KEY (`attribue_par`) REFERENCES `comptes` (`id`);
ALTER TABLE `evenements_compte` ADD CONSTRAINT `fk_evenements_compte_e259db8cec` FOREIGN KEY (`compte_sujet_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `evenements_compte` ADD CONSTRAINT `fk_evenements_compte_f6dd176b08` FOREIGN KEY (`adhesion_id`) REFERENCES `adhesions_organisation` (`id`);
ALTER TABLE `evenements_compte` ADD CONSTRAINT `fk_evenements_compte_b5842dab0c` FOREIGN KEY (`compte_role_id`) REFERENCES `comptes_roles` (`id`);
ALTER TABLE `evenements_compte` ADD CONSTRAINT `fk_evenements_compte_ead0d9dd58` FOREIGN KEY (`permission_id`) REFERENCES `permissions_organisation` (`id`);
ALTER TABLE `evenements_compte` ADD CONSTRAINT `fk_evenements_compte_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `evenements_compte` ADD CONSTRAINT `fk_evenements_compte_f384135efb` FOREIGN KEY (`attribution_interne_id`) REFERENCES `attributions_internes` (`id`);
ALTER TABLE `evenements_compte` ADD CONSTRAINT `fk_evenements_compte_c91cad6707` FOREIGN KEY (`adhesion_id`, `compte_sujet_id`) REFERENCES `adhesions_organisation` (`id`, `compte_id`);
ALTER TABLE `evenements_compte` ADD CONSTRAINT `fk_evenements_compte_3ebaa6013b` FOREIGN KEY (`compte_role_id`, `compte_sujet_id`) REFERENCES `comptes_roles` (`id`, `compte_id`);
ALTER TABLE `evenements_compte` ADD CONSTRAINT `fk_evenements_compte_74cabe212b` FOREIGN KEY (`attribution_interne_id`, `compte_sujet_id`) REFERENCES `attributions_internes` (`id`, `compte_id`);
ALTER TABLE `adresses` ADD CONSTRAINT `fk_adresses_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `consentements` ADD CONSTRAINT `fk_consentements_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `consentements` ADD CONSTRAINT `fk_consentements_59a749685a` FOREIGN KEY (`texte_version_id`) REFERENCES `textes_legaux_versions` (`id`);
ALTER TABLE `evenements_consentement` ADD CONSTRAINT `fk_evenements_consentement_bdfbdc7576` FOREIGN KEY (`consentement_id`) REFERENCES `consentements` (`id`);
ALTER TABLE `sujets_kyc` ADD CONSTRAINT `fk_sujets_kyc_5f6babd021` FOREIGN KEY (`profil_compte_id`) REFERENCES `profils_particuliers` (`compte_id`);
ALTER TABLE `sujets_kyc` ADD CONSTRAINT `fk_sujets_kyc_d73eda69b5` FOREIGN KEY (`organisation_id`) REFERENCES `organisations` (`id`);
ALTER TABLE `exigences_verification` ADD CONSTRAINT `fk_exigences_verification_d561fb6580` FOREIGN KEY (`type_organisation_id`) REFERENCES `types_organisation` (`id`);
ALTER TABLE `profils_fiscaux` ADD CONSTRAINT `fk_profils_fiscaux_0b971bb865` FOREIGN KEY (`sujet_id`) REFERENCES `sujets_kyc` (`id`);
ALTER TABLE `comptes_reversement` ADD CONSTRAINT `fk_comptes_reversement_0b971bb865` FOREIGN KEY (`sujet_id`) REFERENCES `sujets_kyc` (`id`);
ALTER TABLE `dossiers_verification` ADD CONSTRAINT `fk_dossiers_verification_0b971bb865` FOREIGN KEY (`sujet_id`) REFERENCES `sujets_kyc` (`id`);
ALTER TABLE `dossiers_verification` ADD CONSTRAINT `fk_dossiers_verification_de3e8c7660` FOREIGN KEY (`precedent_id`) REFERENCES `dossiers_verification` (`id`);
ALTER TABLE `dossiers_verification` ADD CONSTRAINT `fk_dossiers_verification_9ba3d4c7c2` FOREIGN KEY (`deposant_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `dossiers_verification` ADD CONSTRAINT `fk_dossiers_verification_5a1652e88c` FOREIGN KEY (`profil_fiscal_id`, `sujet_id`) REFERENCES `profils_fiscaux` (`id`, `sujet_id`);
ALTER TABLE `dossiers_verification` ADD CONSTRAINT `fk_dossiers_verification_e9689b7329` FOREIGN KEY (`compte_reversement_id`, `sujet_id`) REFERENCES `comptes_reversement` (`id`, `sujet_id`);
ALTER TABLE `dossiers_verification` ADD CONSTRAINT `fk_dossiers_verification_381aee1d52` FOREIGN KEY (`precedent_id`, `sujet_id`, `niveau`, `numero`) REFERENCES `dossiers_verification` (`id`, `sujet_id`, `niveau`, `numero`);
ALTER TABLE `documents_justificatifs` ADD CONSTRAINT `fk_documents_justificatifs_6b82bdeec5` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_verification` (`id`);
ALTER TABLE `documents_justificatifs` ADD CONSTRAINT `fk_documents_justificatifs_9ba3d4c7c2` FOREIGN KEY (`deposant_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `controles_verification` ADD CONSTRAINT `fk_controles_verification_6b82bdeec5` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_verification` (`id`);
ALTER TABLE `controles_verification` ADD CONSTRAINT `fk_controles_verification_22e20c26bc` FOREIGN KEY (`exigence_id`) REFERENCES `exigences_verification` (`id`);
ALTER TABLE `controles_verification` ADD CONSTRAINT `fk_controles_verification_cc9631d429` FOREIGN KEY (`controleur_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `controles_documents` ADD CONSTRAINT `fk_controles_documents_2505d46904` FOREIGN KEY (`controle_id`, `dossier_id`) REFERENCES `controles_verification` (`id`, `dossier_id`);
ALTER TABLE `controles_documents` ADD CONSTRAINT `fk_controles_documents_813ae27fec` FOREIGN KEY (`document_id`, `dossier_id`) REFERENCES `documents_justificatifs` (`id`, `dossier_id`);
ALTER TABLE `decisions_verification` ADD CONSTRAINT `fk_decisions_verification_6b82bdeec5` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_verification` (`id`);
ALTER TABLE `decisions_verification` ADD CONSTRAINT `fk_decisions_verification_17bb05379b` FOREIGN KEY (`decideur_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `habilitations_verification` ADD CONSTRAINT `fk_habilitations_verification_0b971bb865` FOREIGN KEY (`sujet_id`) REFERENCES `sujets_kyc` (`id`);
ALTER TABLE `habilitations_verification` ADD CONSTRAINT `fk_habilitations_verification_f7f8b11187` FOREIGN KEY (`dossier_courant_id`, `sujet_id`, `niveau`) REFERENCES `dossiers_verification` (`id`, `sujet_id`, `niveau`);
ALTER TABLE `habilitations_verification` ADD CONSTRAINT `fk_habilitations_verification_8e5ccf5e24` FOREIGN KEY (`decision_courante_id`, `dossier_courant_id`) REFERENCES `decisions_verification` (`id`, `dossier_id`);
ALTER TABLE `evenements_verification` ADD CONSTRAINT `fk_evenements_verification_6b82bdeec5` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_verification` (`id`);
ALTER TABLE `evenements_verification` ADD CONSTRAINT `fk_evenements_verification_20c7ab8c90` FOREIGN KEY (`habilitation_id`) REFERENCES `habilitations_verification` (`id`);
ALTER TABLE `evenements_verification` ADD CONSTRAINT `fk_evenements_verification_d02e1bcff8` FOREIGN KEY (`controle_id`) REFERENCES `controles_verification` (`id`);
ALTER TABLE `evenements_verification` ADD CONSTRAINT `fk_evenements_verification_fe17b34aa7` FOREIGN KEY (`decision_id`) REFERENCES `decisions_verification` (`id`);
ALTER TABLE `evenements_verification` ADD CONSTRAINT `fk_evenements_verification_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `evenements_verification` ADD CONSTRAINT `fk_evenements_verification_2505d46904` FOREIGN KEY (`controle_id`, `dossier_id`) REFERENCES `controles_verification` (`id`, `dossier_id`);
ALTER TABLE `evenements_verification` ADD CONSTRAINT `fk_evenements_verification_82b87edb7f` FOREIGN KEY (`decision_id`, `dossier_id`) REFERENCES `decisions_verification` (`id`, `dossier_id`);
ALTER TABLE `defis_verification` ADD CONSTRAINT `fk_defis_verification_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `sessions_authentification` ADD CONSTRAINT `fk_sessions_authentification_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `sessions_authentification` ADD CONSTRAINT `fk_sessions_authentification_75ff383585` FOREIGN KEY (`demande_support_id`) REFERENCES `demandes_support` (`id`);
ALTER TABLE `sessions_authentification` ADD CONSTRAINT `fk_sessions_authentification_813176aceb` FOREIGN KEY (`autorise_par`) REFERENCES `comptes` (`id`);
ALTER TABLE `sessions_authentification` ADD CONSTRAINT `fk_sessions_authentification_4bd3e0a2a7` FOREIGN KEY (`config_acces_id`) REFERENCES `configurations_service` (`id`);
ALTER TABLE `attributions_internes` ADD CONSTRAINT `fk_attributions_internes_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `attributions_internes` ADD CONSTRAINT `fk_attributions_internes_9cc75ebbdd` FOREIGN KEY (`permission_id`) REFERENCES `permissions_internes` (`id`);
ALTER TABLE `attributions_internes` ADD CONSTRAINT `fk_attributions_internes_67827c7edb` FOREIGN KEY (`attribue_par`) REFERENCES `comptes` (`id`);
ALTER TABLE `configurations_service` ADD CONSTRAINT `fk_configurations_service_21ce1dc4f7` FOREIGN KEY (`cree_par`) REFERENCES `comptes` (`id`);
ALTER TABLE `configurations_service` ADD CONSTRAINT `fk_configurations_service_7e307fc26a` FOREIGN KEY (`approuve_par`) REFERENCES `comptes` (`id`);
ALTER TABLE `suppleances_service` ADD CONSTRAINT `fk_suppleances_service_bf88553300` FOREIGN KEY (`titulaire_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `suppleances_service` ADD CONSTRAINT `fk_suppleances_service_14f0f196f0` FOREIGN KEY (`suppleant_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `suppleances_service` ADD CONSTRAINT `fk_suppleances_service_fb720039b3` FOREIGN KEY (`defini_par`) REFERENCES `comptes` (`id`);
ALTER TABLE `demandes_support` ADD CONSTRAINT `fk_demandes_support_4915ae99e3` FOREIGN KEY (`demandeur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `demandes_support` ADD CONSTRAINT `fk_demandes_support_4b58081aa6` FOREIGN KEY (`compte_concerne_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `demandes_support` ADD CONSTRAINT `fk_demandes_support_d73eda69b5` FOREIGN KEY (`organisation_id`) REFERENCES `organisations` (`id`);
ALTER TABLE `demandes_support` ADD CONSTRAINT `fk_demandes_support_0b971bb865` FOREIGN KEY (`sujet_id`) REFERENCES `sujets_kyc` (`id`);
ALTER TABLE `demandes_support` ADD CONSTRAINT `fk_demandes_support_aac02d4042` FOREIGN KEY (`dossier_kyc_id`) REFERENCES `dossiers_verification` (`id`);
ALTER TABLE `demandes_support` ADD CONSTRAINT `fk_demandes_support_843694ef5d` FOREIGN KEY (`identite_verifiee_par`) REFERENCES `comptes` (`id`);
ALTER TABLE `demandes_support` ADD CONSTRAINT `fk_demandes_support_0c8030acb3` FOREIGN KEY (`decide_par`) REFERENCES `comptes` (`id`);
ALTER TABLE `echanges_support` ADD CONSTRAINT `fk_echanges_support_9168e4abdc` FOREIGN KEY (`demande_id`) REFERENCES `demandes_support` (`id`);
ALTER TABLE `echanges_support` ADD CONSTRAINT `fk_echanges_support_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `pieces_support` ADD CONSTRAINT `fk_pieces_support_9168e4abdc` FOREIGN KEY (`demande_id`) REFERENCES `demandes_support` (`id`);
ALTER TABLE `pieces_support` ADD CONSTRAINT `fk_pieces_support_398bd7a2bd` FOREIGN KEY (`echange_id`, `demande_id`) REFERENCES `echanges_support` (`id`, `demande_id`);
ALTER TABLE `pieces_support` ADD CONSTRAINT `fk_pieces_support_9ba3d4c7c2` FOREIGN KEY (`deposant_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `taches_service` ADD CONSTRAINT `fk_taches_service_aac02d4042` FOREIGN KEY (`dossier_kyc_id`) REFERENCES `dossiers_verification` (`id`);
ALTER TABLE `taches_service` ADD CONSTRAINT `fk_taches_service_75ff383585` FOREIGN KEY (`demande_support_id`) REFERENCES `demandes_support` (`id`);
ALTER TABLE `taches_service` ADD CONSTRAINT `fk_taches_service_a2e76a7a7d` FOREIGN KEY (`responsable_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `taches_service` ADD CONSTRAINT `fk_taches_service_3a6420bc24` FOREIGN KEY (`calendrier_config_id`) REFERENCES `configurations_service` (`id`);
ALTER TABLE `taches_service` ADD CONSTRAINT `fk_taches_service_3a8b8a0753` FOREIGN KEY (`delais_config_id`) REFERENCES `configurations_service` (`id`);
ALTER TABLE `evenements_taches` ADD CONSTRAINT `fk_evenements_taches_2a6b17418c` FOREIGN KEY (`tache_id`) REFERENCES `taches_service` (`id`);
ALTER TABLE `evenements_taches` ADD CONSTRAINT `fk_evenements_taches_26081d76b9` FOREIGN KEY (`auteur_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `evenements_taches` ADD CONSTRAINT `fk_evenements_taches_b148842b3a` FOREIGN KEY (`ancien_responsable_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `evenements_taches` ADD CONSTRAINT `fk_evenements_taches_6a4f2fbe96` FOREIGN KEY (`nouveau_responsable_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `messages_sortants` ADD CONSTRAINT `fk_messages_sortants_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `messages_sortants` ADD CONSTRAINT `fk_messages_sortants_6b82bdeec5` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_verification` (`id`);
ALTER TABLE `messages_sortants` ADD CONSTRAINT `fk_messages_sortants_9168e4abdc` FOREIGN KEY (`demande_id`) REFERENCES `demandes_support` (`id`);
ALTER TABLE `messages_sortants` ADD CONSTRAINT `fk_messages_sortants_1c405b8b73` FOREIGN KEY (`defi_id`) REFERENCES `defis_verification` (`id`);
ALTER TABLE `messages_sortants` ADD CONSTRAINT `fk_messages_sortants_8788445382` FOREIGN KEY (`evenement_compte_id`) REFERENCES `evenements_compte` (`id`);
ALTER TABLE `messages_sortants` ADD CONSTRAINT `fk_messages_sortants_f753ab7a7e` FOREIGN KEY (`evenement_kyc_id`) REFERENCES `evenements_verification` (`id`);
ALTER TABLE `transmissions_messages` ADD CONSTRAINT `fk_transmissions_messages_5d3b1caab8` FOREIGN KEY (`message_id`) REFERENCES `messages_sortants` (`id`);
ALTER TABLE `transmissions_messages` ADD CONSTRAINT `fk_transmissions_messages_90cf6771d3` FOREIGN KEY (`compte_destinataire_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `transmissions_messages` ADD CONSTRAINT `fk_transmissions_messages_053121dac3` FOREIGN KEY (`config_delais_id`) REFERENCES `configurations_service` (`id`);
ALTER TABLE `transmissions_messages` ADD CONSTRAINT `fk_transmissions_messages_d7ec2bb3d3` FOREIGN KEY (`incident_demande_id`) REFERENCES `demandes_support` (`id`);
ALTER TABLE `tentatives_transmission` ADD CONSTRAINT `fk_tentatives_transmission_aea44c15cf` FOREIGN KEY (`transmission_id`) REFERENCES `transmissions_messages` (`id`);
ALTER TABLE `vendeurs` ADD CONSTRAINT `fk_vendeurs_0b971bb865` FOREIGN KEY (`sujet_id`) REFERENCES `sujets_kyc` (`id`);
ALTER TABLE `vendeurs` ADD CONSTRAINT `fk_vendeurs_32f5ef11cd` FOREIGN KEY (`profil_fiscal_id`) REFERENCES `profils_fiscaux` (`id`);
ALTER TABLE `vendeurs` ADD CONSTRAINT `fk_vendeurs_a0eccedec5` FOREIGN KEY (`compte_reversement_id`) REFERENCES `comptes_reversement` (`id`);
ALTER TABLE `vendeurs` ADD CONSTRAINT `fk_vendeurs_5a1652e88c` FOREIGN KEY (`profil_fiscal_id`, `sujet_id`) REFERENCES `profils_fiscaux` (`id`, `sujet_id`);
ALTER TABLE `vendeurs` ADD CONSTRAINT `fk_vendeurs_e9689b7329` FOREIGN KEY (`compte_reversement_id`, `sujet_id`) REFERENCES `comptes_reversement` (`id`, `sujet_id`);
ALTER TABLE `acces_documents_support` ADD CONSTRAINT `fk_acces_documents_support_75ff383585` FOREIGN KEY (`demande_support_id`) REFERENCES `demandes_support` (`id`);
ALTER TABLE `acces_documents_support` ADD CONSTRAINT `fk_acces_documents_support_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `acces_documents_support` ADD CONSTRAINT `fk_acces_documents_support_3bcdd87061` FOREIGN KEY (`facture_vente_id`) REFERENCES `factures_vente` (`id`);
ALTER TABLE `acces_documents_support` ADD CONSTRAINT `fk_acces_documents_support_e1caacfd22` FOREIGN KEY (`facture_commission_id`) REFERENCES `factures_commission` (`id`);
ALTER TABLE `acces_documents_support` ADD CONSTRAINT `fk_acces_documents_support_93f9346ea2` FOREIGN KEY (`avoir_id`) REFERENCES `avoirs` (`id`);
ALTER TABLE `acces_documents_support` ADD CONSTRAINT `fk_acces_documents_support_0bc448e690` FOREIGN KEY (`recu_paiement_id`) REFERENCES `recus_paiement` (`id`);
ALTER TABLE `acces_documents_support` ADD CONSTRAINT `fk_acces_documents_support_813176aceb` FOREIGN KEY (`autorise_par`) REFERENCES `comptes` (`id`);
ALTER TABLE `secrets_transmission` ADD CONSTRAINT `fk_secrets_transmission_aea44c15cf` FOREIGN KEY (`transmission_id`) REFERENCES `transmissions_messages` (`id`);
ALTER TABLE `secrets_transmission` ADD CONSTRAINT `fk_secrets_transmission_1c405b8b73` FOREIGN KEY (`defi_id`) REFERENCES `defis_verification` (`id`);
ALTER TABLE `secrets_transmission` ADD CONSTRAINT `fk_secrets_transmission_955e03f499` FOREIGN KEY (`acces_reponse_id`) REFERENCES `acces_reponse_conformite` (`id`);
ALTER TABLE `oeuvre` ADD CONSTRAINT `fk_oeuvre_5285da674f` FOREIGN KEY (`langue_principale_id`) REFERENCES `langue` (`langue_id`);
ALTER TABLE `oeuvre` ADD CONSTRAINT `fk_oeuvre_fa71241bfe` FOREIGN KEY (`niveau_educatif_id`) REFERENCES `niveau_educatif` (`niveau_educatif_id`);
ALTER TABLE `oeuvre` ADD CONSTRAINT `fk_oeuvre_9675d1d27e` FOREIGN KEY (`collection_id`) REFERENCES `collection` (`collection_id`);
ALTER TABLE `edition` ADD CONSTRAINT `fk_edition_13b943eabc` FOREIGN KEY (`oeuvre_id`) REFERENCES `oeuvre` (`oeuvre_id`);
ALTER TABLE `edition` ADD CONSTRAINT `fk_edition_9fa7a99695` FOREIGN KEY (`editeur_id`) REFERENCES `editeur` (`editeur_id`);
ALTER TABLE `offre` ADD CONSTRAINT `fk_offre_75580fac81` FOREIGN KEY (`edition_id`) REFERENCES `edition` (`edition_id`);
ALTER TABLE `offre` ADD CONSTRAINT `fk_offre_27a6c5be26` FOREIGN KEY (`format_id`) REFERENCES `format_technique` (`format_id`);
ALTER TABLE `offre` ADD CONSTRAINT `fk_offre_54ee10151f` FOREIGN KEY (`vendeur_id`) REFERENCES `vendeurs` (`id`);
ALTER TABLE `auteur` ADD CONSTRAINT `fk_auteur_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `categorie` ADD CONSTRAINT `fk_categorie_d44daa8088` FOREIGN KEY (`parent_id`) REFERENCES `categorie` (`categorie_id`);
ALTER TABLE `niveau_educatif` ADD CONSTRAINT `fk_niveau_educatif_41c50041c1` FOREIGN KEY (`parent_id`) REFERENCES `niveau_educatif` (`niveau_educatif_id`);
ALTER TABLE `collection` ADD CONSTRAINT `fk_collection_9fa7a99695` FOREIGN KEY (`editeur_id`) REFERENCES `editeur` (`editeur_id`);
ALTER TABLE `oeuvre_auteur` ADD CONSTRAINT `fk_oeuvre_auteur_13b943eabc` FOREIGN KEY (`oeuvre_id`) REFERENCES `oeuvre` (`oeuvre_id`);
ALTER TABLE `oeuvre_auteur` ADD CONSTRAINT `fk_oeuvre_auteur_e44f799281` FOREIGN KEY (`auteur_id`) REFERENCES `auteur` (`auteur_id`);
ALTER TABLE `oeuvre_categorie` ADD CONSTRAINT `fk_oeuvre_categorie_13b943eabc` FOREIGN KEY (`oeuvre_id`) REFERENCES `oeuvre` (`oeuvre_id`);
ALTER TABLE `oeuvre_categorie` ADD CONSTRAINT `fk_oeuvre_categorie_1b981b983f` FOREIGN KEY (`categorie_id`) REFERENCES `categorie` (`categorie_id`);
ALTER TABLE `oeuvre_langue` ADD CONSTRAINT `fk_oeuvre_langue_13b943eabc` FOREIGN KEY (`oeuvre_id`) REFERENCES `oeuvre` (`oeuvre_id`);
ALTER TABLE `oeuvre_langue` ADD CONSTRAINT `fk_oeuvre_langue_de182a5d9b` FOREIGN KEY (`langue_id`) REFERENCES `langue` (`langue_id`);
ALTER TABLE `auteur_tag` ADD CONSTRAINT `fk_auteur_tag_e44f799281` FOREIGN KEY (`auteur_id`) REFERENCES `auteur` (`auteur_id`);
ALTER TABLE `auteur_tag` ADD CONSTRAINT `fk_auteur_tag_e9bed2ef9c` FOREIGN KEY (`tag_auteur_id`) REFERENCES `tag_auteur` (`tag_auteur_id`);
ALTER TABLE `selection_oeuvre` ADD CONSTRAINT `fk_selection_oeuvre_6a5164c8ce` FOREIGN KEY (`selection_id`) REFERENCES `selection_editoriale` (`selection_id`);
ALTER TABLE `selection_oeuvre` ADD CONSTRAINT `fk_selection_oeuvre_13b943eabc` FOREIGN KEY (`oeuvre_id`) REFERENCES `oeuvre` (`oeuvre_id`);
ALTER TABLE `media_catalogue` ADD CONSTRAINT `fk_media_catalogue_13b943eabc` FOREIGN KEY (`oeuvre_id`) REFERENCES `oeuvre` (`oeuvre_id`);
ALTER TABLE `media_catalogue` ADD CONSTRAINT `fk_media_catalogue_75580fac81` FOREIGN KEY (`edition_id`) REFERENCES `edition` (`edition_id`);
ALTER TABLE `avis` ADD CONSTRAINT `fk_avis_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `avis` ADD CONSTRAINT `fk_avis_13b943eabc` FOREIGN KEY (`oeuvre_id`) REFERENCES `oeuvre` (`oeuvre_id`);
ALTER TABLE `avis` ADD CONSTRAINT `fk_avis_c04f6964f0` FOREIGN KEY (`moderateur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `paniers` ADD CONSTRAINT `fk_paniers_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `paniers` ADD CONSTRAINT `fk_paniers_01df6083b5` FOREIGN KEY (`jeton_id`) REFERENCES `jetons_panier_anonyme` (`id`);
ALTER TABLE `lignes_panier` ADD CONSTRAINT `fk_lignes_panier_96f9c2483d` FOREIGN KEY (`panier_id`) REFERENCES `paniers` (`id`);
ALTER TABLE `lignes_panier` ADD CONSTRAINT `fk_lignes_panier_9b3980579d` FOREIGN KEY (`offre_id`) REFERENCES `offre` (`offre_id`);
ALTER TABLE `lignes_panier` ADD CONSTRAINT `fk_lignes_panier_11a38e374a` FOREIGN KEY (`panier_id`, `devise`) REFERENCES `paniers` (`id`, `devise`);
ALTER TABLE `fusions_panier` ADD CONSTRAINT `fk_fusions_panier_b45e538898` FOREIGN KEY (`panier_source_id`) REFERENCES `paniers` (`id`);
ALTER TABLE `fusions_panier` ADD CONSTRAINT `fk_fusions_panier_f3b951cfc6` FOREIGN KEY (`panier_cible_id`) REFERENCES `paniers` (`id`);
ALTER TABLE `fusions_panier` ADD CONSTRAINT `fk_fusions_panier_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `fusions_panier` ADD CONSTRAINT `fk_fusions_panier_a043895cb3` FOREIGN KEY (`panier_cible_id`, `compte_id`) REFERENCES `paniers` (`id`, `compte_id`);
ALTER TABLE `unites_stock` ADD CONSTRAINT `fk_unites_stock_54ee10151f` FOREIGN KEY (`vendeur_id`) REFERENCES `vendeurs` (`id`);
ALTER TABLE `offres_stock` ADD CONSTRAINT `fk_offres_stock_9b3980579d` FOREIGN KEY (`offre_id`) REFERENCES `offre` (`offre_id`);
ALTER TABLE `offres_stock` ADD CONSTRAINT `fk_offres_stock_229658e0f2` FOREIGN KEY (`unite_stock_id`) REFERENCES `unites_stock` (`id`);
ALTER TABLE `reservations_stock` ADD CONSTRAINT `fk_reservations_stock_a90de5d467` FOREIGN KEY (`ligne_commande_id`) REFERENCES `lignes_commande` (`id`);
ALTER TABLE `reservations_stock` ADD CONSTRAINT `fk_reservations_stock_229658e0f2` FOREIGN KEY (`unite_stock_id`) REFERENCES `unites_stock` (`id`);
ALTER TABLE `versions_code_promo` ADD CONSTRAINT `fk_versions_code_promo_8b0c721d46` FOREIGN KEY (`code_promo_id`) REFERENCES `codes_promo` (`id`);
ALTER TABLE `formats_promo` ADD CONSTRAINT `fk_formats_promo_80ea762411` FOREIGN KEY (`version_promo_id`) REFERENCES `versions_code_promo` (`id`);
ALTER TABLE `formats_promo` ADD CONSTRAINT `fk_formats_promo_27a6c5be26` FOREIGN KEY (`format_id`) REFERENCES `format_technique` (`format_id`);
ALTER TABLE `applications_promo` ADD CONSTRAINT `fk_applications_promo_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`);
ALTER TABLE `applications_promo` ADD CONSTRAINT `fk_applications_promo_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `applications_promo` ADD CONSTRAINT `fk_applications_promo_80ea762411` FOREIGN KEY (`version_promo_id`) REFERENCES `versions_code_promo` (`id`);
ALTER TABLE `applications_promo` ADD CONSTRAINT `fk_applications_promo_9a94a15c45` FOREIGN KEY (`commande_id`, `compte_id`) REFERENCES `commandes` (`id`, `compte_id`);
ALTER TABLE `allocations_promo_ligne` ADD CONSTRAINT `fk_allocations_promo_ligne_919d0b6c8c` FOREIGN KEY (`application_promo_id`) REFERENCES `applications_promo` (`id`);
ALTER TABLE `allocations_promo_ligne` ADD CONSTRAINT `fk_allocations_promo_ligne_a90de5d467` FOREIGN KEY (`ligne_commande_id`) REFERENCES `lignes_commande` (`id`);
ALTER TABLE `restitutions_quota_promo` ADD CONSTRAINT `fk_restitutions_quota_promo_919d0b6c8c` FOREIGN KEY (`application_promo_id`) REFERENCES `applications_promo` (`id`);
ALTER TABLE `restitutions_quota_promo` ADD CONSTRAINT `fk_restitutions_quota_promo_85050287e8` FOREIGN KEY (`decision_remboursement_id`) REFERENCES `decisions_remboursement` (`id`);
ALTER TABLE `commandes` ADD CONSTRAINT `fk_commandes_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `lignes_commande` ADD CONSTRAINT `fk_lignes_commande_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`);
ALTER TABLE `lignes_commande` ADD CONSTRAINT `fk_lignes_commande_9b3980579d` FOREIGN KEY (`offre_id`) REFERENCES `offre` (`offre_id`);
ALTER TABLE `lignes_commande` ADD CONSTRAINT `fk_lignes_commande_54ee10151f` FOREIGN KEY (`vendeur_id`) REFERENCES `vendeurs` (`id`);
ALTER TABLE `lignes_commande` ADD CONSTRAINT `fk_lignes_commande_f0d004c022` FOREIGN KEY (`commande_id`, `devise`) REFERENCES `commandes` (`id`, `devise`);
ALTER TABLE `lignes_commande` ADD CONSTRAINT `fk_lignes_commande_ab891a205c` FOREIGN KEY (`offre_id`, `vendeur_id`) REFERENCES `offre` (`offre_id`, `vendeur_id`);
ALTER TABLE `adresses_livraison_commande` ADD CONSTRAINT `fk_adresses_livraison_commande_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`);
ALTER TABLE `adresses_livraison_commande` ADD CONSTRAINT `fk_adresses_livraison_commande_3a0d5f2fcf` FOREIGN KEY (`adresse_origine_id`) REFERENCES `adresses` (`id`);
ALTER TABLE `identites_facturation_commande` ADD CONSTRAINT `fk_identites_facturation_commande_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`);
ALTER TABLE `configurations_encaissement` ADD CONSTRAINT `fk_configurations_encaissement_5afa1331bb` FOREIGN KEY (`prestataire_id`) REFERENCES `prestataires_paiement` (`id`);
ALTER TABLE `capacites_encaissement` ADD CONSTRAINT `fk_capacites_encaissement_a53c9de021` FOREIGN KEY (`configuration_id`) REFERENCES `configurations_encaissement` (`id`);
ALTER TABLE `paiements` ADD CONSTRAINT `fk_paiements_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`);
ALTER TABLE `paiements` ADD CONSTRAINT `fk_paiements_a53c9de021` FOREIGN KEY (`configuration_id`) REFERENCES `configurations_encaissement` (`id`);
ALTER TABLE `paiements` ADD CONSTRAINT `fk_paiements_f0d004c022` FOREIGN KEY (`commande_id`, `devise`) REFERENCES `commandes` (`id`, `devise`);
ALTER TABLE `paiements` ADD CONSTRAINT `fk_paiements_ab4b49935a` FOREIGN KEY (`configuration_id`, `moyen`, `devise`) REFERENCES `capacites_encaissement` (`configuration_id`, `moyen`, `devise`);
ALTER TABLE `evenements_psp` ADD CONSTRAINT `fk_evenements_psp_a53c9de021` FOREIGN KEY (`configuration_id`) REFERENCES `configurations_encaissement` (`id`);
ALTER TABLE `evenements_psp` ADD CONSTRAINT `fk_evenements_psp_571ddb756e` FOREIGN KEY (`paiement_id`) REFERENCES `paiements` (`id`);
ALTER TABLE `evenements_psp` ADD CONSTRAINT `fk_evenements_psp_fbc678df10` FOREIGN KEY (`paiement_id`, `configuration_id`) REFERENCES `paiements` (`id`, `configuration_id`);
ALTER TABLE `transactions_paiement` ADD CONSTRAINT `fk_transactions_paiement_571ddb756e` FOREIGN KEY (`paiement_id`) REFERENCES `paiements` (`id`);
ALTER TABLE `transactions_paiement` ADD CONSTRAINT `fk_transactions_paiement_2a07d83cbc` FOREIGN KEY (`remboursement_id`) REFERENCES `remboursements` (`id`);
ALTER TABLE `transactions_paiement` ADD CONSTRAINT `fk_transactions_paiement_a8780dfce1` FOREIGN KEY (`evenement_psp_id`) REFERENCES `evenements_psp` (`id`);
ALTER TABLE `transactions_paiement` ADD CONSTRAINT `fk_transactions_paiement_c23ea80130` FOREIGN KEY (`paiement_id`, `devise`) REFERENCES `paiements` (`id`, `devise`);
ALTER TABLE `transactions_paiement` ADD CONSTRAINT `fk_transactions_paiement_38d403ac22` FOREIGN KEY (`remboursement_id`, `paiement_id`) REFERENCES `remboursements` (`id`, `paiement_id`);
ALTER TABLE `anomalies_paiement` ADD CONSTRAINT `fk_anomalies_paiement_571ddb756e` FOREIGN KEY (`paiement_id`) REFERENCES `paiements` (`id`);
ALTER TABLE `anomalies_paiement` ADD CONSTRAINT `fk_anomalies_paiement_a8780dfce1` FOREIGN KEY (`evenement_psp_id`) REFERENCES `evenements_psp` (`id`);
ALTER TABLE `anomalies_paiement` ADD CONSTRAINT `fk_anomalies_paiement_0a4bf6e587` FOREIGN KEY (`decide_par_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `validations_reglement` ADD CONSTRAINT `fk_validations_reglement_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`);
ALTER TABLE `validations_reglement` ADD CONSTRAINT `fk_validations_reglement_571ddb756e` FOREIGN KEY (`paiement_id`) REFERENCES `paiements` (`id`);
ALTER TABLE `validations_reglement` ADD CONSTRAINT `fk_validations_reglement_b74a36ca3a` FOREIGN KEY (`paiement_id`, `commande_id`, `devise`) REFERENCES `paiements` (`id`, `commande_id`, `devise`);
ALTER TABLE `validations_reglement` ADD CONSTRAINT `fk_validations_reglement_f0d004c022` FOREIGN KEY (`commande_id`, `devise`) REFERENCES `commandes` (`id`, `devise`);
ALTER TABLE `besoins_preparation` ADD CONSTRAINT `fk_besoins_preparation_a90de5d467` FOREIGN KEY (`ligne_commande_id`) REFERENCES `lignes_commande` (`id`);
ALTER TABLE `mandats_facturation` ADD CONSTRAINT `fk_mandats_facturation_54ee10151f` FOREIGN KEY (`vendeur_id`) REFERENCES `vendeurs` (`id`);
ALTER TABLE `circuits_facturation_vendeur` ADD CONSTRAINT `fk_circuits_facturation_vendeur_54ee10151f` FOREIGN KEY (`vendeur_id`) REFERENCES `vendeurs` (`id`);
ALTER TABLE `circuits_facturation_vendeur` ADD CONSTRAINT `fk_circuits_facturation_vendeur_9908d49023` FOREIGN KEY (`mandat_id`) REFERENCES `mandats_facturation` (`id`);
ALTER TABLE `circuits_facturation_vendeur` ADD CONSTRAINT `fk_circuits_facturation_vendeur_14a8e4c9ac` FOREIGN KEY (`mandat_id`, `vendeur_id`) REFERENCES `mandats_facturation` (`id`, `vendeur_id`);
ALTER TABLE `dossiers_facturation` ADD CONSTRAINT `fk_dossiers_facturation_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`);
ALTER TABLE `dossiers_facturation` ADD CONSTRAINT `fk_dossiers_facturation_54ee10151f` FOREIGN KEY (`vendeur_id`) REFERENCES `vendeurs` (`id`);
ALTER TABLE `dossiers_facturation` ADD CONSTRAINT `fk_dossiers_facturation_7c2e755be9` FOREIGN KEY (`circuit_checkout_id`) REFERENCES `circuits_facturation_vendeur` (`id`);
ALTER TABLE `dossiers_facturation` ADD CONSTRAINT `fk_dossiers_facturation_d9ded24ce1` FOREIGN KEY (`regle_remuneration_id`) REFERENCES `regles_remuneration` (`id`);
ALTER TABLE `dossiers_facturation` ADD CONSTRAINT `fk_dossiers_facturation_b75c378bdd` FOREIGN KEY (`circuit_checkout_id`, `vendeur_id`) REFERENCES `circuits_facturation_vendeur` (`id`, `vendeur_id`);
ALTER TABLE `factures_vente` ADD CONSTRAINT `fk_factures_vente_1eb43daf24` FOREIGN KEY (`dossier_facturation_id`) REFERENCES `dossiers_facturation` (`id`);
ALTER TABLE `factures_vente` ADD CONSTRAINT `fk_factures_vente_9908d49023` FOREIGN KEY (`mandat_id`) REFERENCES `mandats_facturation` (`id`);
ALTER TABLE `lignes_facture_vente` ADD CONSTRAINT `fk_lignes_facture_vente_edcee08fda` FOREIGN KEY (`facture_id`) REFERENCES `factures_vente` (`id`);
ALTER TABLE `lignes_facture_vente` ADD CONSTRAINT `fk_lignes_facture_vente_a90de5d467` FOREIGN KEY (`ligne_commande_id`) REFERENCES `lignes_commande` (`id`);
ALTER TABLE `factures_commission` ADD CONSTRAINT `fk_factures_commission_1eb43daf24` FOREIGN KEY (`dossier_facturation_id`) REFERENCES `dossiers_facturation` (`id`);
ALTER TABLE `factures_commission` ADD CONSTRAINT `fk_factures_commission_d9ded24ce1` FOREIGN KEY (`regle_remuneration_id`) REFERENCES `regles_remuneration` (`id`);
ALTER TABLE `recus_paiement` ADD CONSTRAINT `fk_recus_paiement_571ddb756e` FOREIGN KEY (`paiement_id`) REFERENCES `paiements` (`id`);
ALTER TABLE `avoirs` ADD CONSTRAINT `fk_avoirs_3bcdd87061` FOREIGN KEY (`facture_vente_id`) REFERENCES `factures_vente` (`id`);
ALTER TABLE `avoirs` ADD CONSTRAINT `fk_avoirs_e1caacfd22` FOREIGN KEY (`facture_commission_id`) REFERENCES `factures_commission` (`id`);
ALTER TABLE `avoirs` ADD CONSTRAINT `fk_avoirs_85050287e8` FOREIGN KEY (`decision_remboursement_id`) REFERENCES `decisions_remboursement` (`id`);
ALTER TABLE `lignes_avoir` ADD CONSTRAINT `fk_lignes_avoir_93f9346ea2` FOREIGN KEY (`avoir_id`) REFERENCES `avoirs` (`id`);
ALTER TABLE `lignes_avoir` ADD CONSTRAINT `fk_lignes_avoir_9ad5a606b7` FOREIGN KEY (`ligne_facture_vente_id`) REFERENCES `lignes_facture_vente` (`id`);
ALTER TABLE `demandes_attribution_droit` ADD CONSTRAINT `fk_demandes_attribution_droit_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `demandes_attribution_droit` ADD CONSTRAINT `fk_demandes_attribution_droit_9b3980579d` FOREIGN KEY (`offre_id`) REFERENCES `offre` (`offre_id`);
ALTER TABLE `demandes_attribution_droit` ADD CONSTRAINT `fk_demandes_attribution_droit_a90de5d467` FOREIGN KEY (`ligne_commande_id`) REFERENCES `lignes_commande` (`id`);
ALTER TABLE `demandes_attribution_droit` ADD CONSTRAINT `fk_demandes_attribution_droit_396e5b38ce` FOREIGN KEY (`reference_acquisition_a4_id`) REFERENCES `references_acquisition` (`id`);
ALTER TABLE `demandes_attribution_droit` ADD CONSTRAINT `fk_demandes_attribution_droit_49d2468cd3` FOREIGN KEY (`origine_a4_id`) REFERENCES `origines_acquisition` (`id`);
ALTER TABLE `demandes_attribution_droit` ADD CONSTRAINT `fk_demandes_attribution_droit_f56bd65283` FOREIGN KEY (`licence_a4_id`) REFERENCES `licences_contenu` (`id`);
ALTER TABLE `demandes_attribution_droit` ADD CONSTRAINT `fk_demandes_attribution_droit_5d79eb4ed5` FOREIGN KEY (`licence_a4_id`, `compte_id`, `offre_id`) REFERENCES `licences_contenu` (`id`, `compte_id`, `offre_id`);
ALTER TABLE `demandes_attribution_droit` ADD CONSTRAINT `fk_demandes_attribution_droit_b6825e989d` FOREIGN KEY (`origine_a4_id`, `reference_acquisition_a4_id`, `licence_a4_id`) REFERENCES `origines_acquisition` (`id`, `reference_acquisition_id`, `licence_id`);
ALTER TABLE `dossiers_remboursement` ADD CONSTRAINT `fk_dossiers_remboursement_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`);
ALTER TABLE `dossiers_remboursement` ADD CONSTRAINT `fk_dossiers_remboursement_a66fa8f091` FOREIGN KEY (`demandeur_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `dossiers_remboursement` ADD CONSTRAINT `fk_dossiers_remboursement_70797ac77a` FOREIGN KEY (`initiateur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `dossiers_remboursement` ADD CONSTRAINT `fk_dossiers_remboursement_c6171f680e` FOREIGN KEY (`dossier_precedent_id`) REFERENCES `dossiers_remboursement` (`id`);
ALTER TABLE `dossiers_remboursement` ADD CONSTRAINT `fk_dossiers_remboursement_cddb72d9b7` FOREIGN KEY (`dossier_precedent_id`, `commande_id`) REFERENCES `dossiers_remboursement` (`id`, `commande_id`);
ALTER TABLE `decisions_remboursement` ADD CONSTRAINT `fk_decisions_remboursement_c53ea5fd9f` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_remboursement` (`id`);
ALTER TABLE `decisions_remboursement` ADD CONSTRAINT `fk_decisions_remboursement_17bb05379b` FOREIGN KEY (`decideur_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `lignes_decision_remboursement` ADD CONSTRAINT `fk_lignes_decision_remboursement_831bf2000f` FOREIGN KEY (`decision_id`) REFERENCES `decisions_remboursement` (`id`);
ALTER TABLE `lignes_decision_remboursement` ADD CONSTRAINT `fk_lignes_decision_remboursement_a90de5d467` FOREIGN KEY (`ligne_commande_id`) REFERENCES `lignes_commande` (`id`);
ALTER TABLE `remboursements` ADD CONSTRAINT `fk_remboursements_571ddb756e` FOREIGN KEY (`paiement_id`) REFERENCES `paiements` (`id`);
ALTER TABLE `remboursements` ADD CONSTRAINT `fk_remboursements_831bf2000f` FOREIGN KEY (`decision_id`) REFERENCES `decisions_remboursement` (`id`);
ALTER TABLE `remboursements` ADD CONSTRAINT `fk_remboursements_1f2ee634f4` FOREIGN KEY (`anomalie_id`) REFERENCES `anomalies_paiement` (`id`);
ALTER TABLE `remboursements` ADD CONSTRAINT `fk_remboursements_c23ea80130` FOREIGN KEY (`paiement_id`, `devise`) REFERENCES `paiements` (`id`, `devise`);
ALTER TABLE `remboursements` ADD CONSTRAINT `fk_remboursements_367eebd481` FOREIGN KEY (`decision_id`, `devise`) REFERENCES `decisions_remboursement` (`id`, `devise`);
ALTER TABLE `remboursements` ADD CONSTRAINT `fk_remboursements_fbc678df10` FOREIGN KEY (`paiement_id`, `configuration_id`) REFERENCES `paiements` (`id`, `configuration_id`);
ALTER TABLE `allocations_remboursement` ADD CONSTRAINT `fk_allocations_remboursement_2a07d83cbc` FOREIGN KEY (`remboursement_id`) REFERENCES `remboursements` (`id`);
ALTER TABLE `allocations_remboursement` ADD CONSTRAINT `fk_allocations_remboursement_a90de5d467` FOREIGN KEY (`ligne_commande_id`) REFERENCES `lignes_commande` (`id`);
ALTER TABLE `demandes_revocation_droit` ADD CONSTRAINT `fk_demandes_revocation_droit_831bf2000f` FOREIGN KEY (`decision_id`) REFERENCES `decisions_remboursement` (`id`);
ALTER TABLE `demandes_revocation_droit` ADD CONSTRAINT `fk_demandes_revocation_droit_7f3cb95a6c` FOREIGN KEY (`demande_attribution_id`) REFERENCES `demandes_attribution_droit` (`id`);
ALTER TABLE `demandes_revocation_droit` ADD CONSTRAINT `fk_demandes_revocation_droit_396e5b38ce` FOREIGN KEY (`reference_acquisition_a4_id`) REFERENCES `references_acquisition` (`id`);
ALTER TABLE `demandes_revocation_droit` ADD CONSTRAINT `fk_demandes_revocation_droit_49d2468cd3` FOREIGN KEY (`origine_a4_id`) REFERENCES `origines_acquisition` (`id`);
ALTER TABLE `demandes_revocation_droit` ADD CONSTRAINT `fk_demandes_revocation_droit_f56bd65283` FOREIGN KEY (`licence_a4_id`) REFERENCES `licences_contenu` (`id`);
ALTER TABLE `demandes_revocation_droit` ADD CONSTRAINT `fk_demandes_revocation_droit_b6825e989d` FOREIGN KEY (`origine_a4_id`, `reference_acquisition_a4_id`, `licence_a4_id`) REFERENCES `origines_acquisition` (`id`, `reference_acquisition_id`, `licence_id`);
ALTER TABLE `receptions_compensation` ADD CONSTRAINT `fk_receptions_compensation_b093402d65` FOREIGN KEY (`demande_conformite_id`) REFERENCES `demandes_compensation_conformite` (`id`);
ALTER TABLE `receptions_compensation` ADD CONSTRAINT `fk_receptions_compensation_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`);
ALTER TABLE `receptions_compensation` ADD CONSTRAINT `fk_receptions_compensation_c53ea5fd9f` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_remboursement` (`id`);
ALTER TABLE `receptions_compensation` ADD CONSTRAINT `fk_receptions_compensation_f839016762` FOREIGN KEY (`dossier_reutilisation_id`) REFERENCES `dossiers_remboursement` (`id`);
ALTER TABLE `receptions_compensation` ADD CONSTRAINT `fk_receptions_compensation_7cca1a7868` FOREIGN KEY (`dossier_id`, `commande_id`) REFERENCES `dossiers_remboursement` (`id`, `commande_id`);
ALTER TABLE `receptions_compensation` ADD CONSTRAINT `fk_receptions_compensation_316a237ace` FOREIGN KEY (`dossier_reutilisation_id`, `commande_id`) REFERENCES `dossiers_remboursement` (`id`, `commande_id`);
ALTER TABLE `decisions_rattachement_compensation` ADD CONSTRAINT `fk_decisions_rattachement_compensation_69d077b21d` FOREIGN KEY (`reception_id`) REFERENCES `receptions_compensation` (`id`);
ALTER TABLE `decisions_rattachement_compensation` ADD CONSTRAINT `fk_decisions_rattachement_compensation_c53ea5fd9f` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_remboursement` (`id`);
ALTER TABLE `decisions_rattachement_compensation` ADD CONSTRAINT `fk_decisions_rattachement_compensation_17bb05379b` FOREIGN KEY (`decideur_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `licences_contenu` ADD CONSTRAINT `fk_licences_contenu_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `licences_contenu` ADD CONSTRAINT `fk_licences_contenu_9b3980579d` FOREIGN KEY (`offre_id`) REFERENCES `offre` (`offre_id`);
ALTER TABLE `origines_acquisition` ADD CONSTRAINT `fk_origines_acquisition_dccc261267` FOREIGN KEY (`licence_id`) REFERENCES `licences_contenu` (`id`);
ALTER TABLE `origines_acquisition` ADD CONSTRAINT `fk_origines_acquisition_80cf80e023` FOREIGN KEY (`reference_acquisition_id`) REFERENCES `references_acquisition` (`id`);
ALTER TABLE `origines_acquisition` ADD CONSTRAINT `fk_origines_acquisition_a90de5d467` FOREIGN KEY (`ligne_commande_id`) REFERENCES `lignes_commande` (`id`);
ALTER TABLE `evenements_licence` ADD CONSTRAINT `fk_evenements_licence_dccc261267` FOREIGN KEY (`licence_id`) REFERENCES `licences_contenu` (`id`);
ALTER TABLE `evenements_licence` ADD CONSTRAINT `fk_evenements_licence_9a0f3a5ebf` FOREIGN KEY (`origine_id`) REFERENCES `origines_acquisition` (`id`);
ALTER TABLE `evenements_licence` ADD CONSTRAINT `fk_evenements_licence_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `evenements_licence` ADD CONSTRAINT `fk_evenements_licence_1eb7cf1c68` FOREIGN KEY (`origine_id`, `licence_id`) REFERENCES `origines_acquisition` (`id`, `licence_id`);
ALTER TABLE `instructions_revocation` ADD CONSTRAINT `fk_instructions_revocation_80cf80e023` FOREIGN KEY (`reference_acquisition_id`) REFERENCES `references_acquisition` (`id`);
ALTER TABLE `instructions_revocation` ADD CONSTRAINT `fk_instructions_revocation_9a0f3a5ebf` FOREIGN KEY (`origine_id`) REFERENCES `origines_acquisition` (`id`);
ALTER TABLE `instructions_revocation` ADD CONSTRAINT `fk_instructions_revocation_2d54dc47c2` FOREIGN KEY (`origine_id`, `reference_acquisition_id`) REFERENCES `origines_acquisition` (`id`, `reference_acquisition_id`);
ALTER TABLE `blocages_acces` ADD CONSTRAINT `fk_blocages_acces_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `blocages_acces` ADD CONSTRAINT `fk_blocages_acces_dccc261267` FOREIGN KEY (`licence_id`) REFERENCES `licences_contenu` (`id`);
ALTER TABLE `blocages_acces` ADD CONSTRAINT `fk_blocages_acces_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `evenements_blocage` ADD CONSTRAINT `fk_evenements_blocage_11eac69b0a` FOREIGN KEY (`blocage_id`) REFERENCES `blocages_acces` (`id`);
ALTER TABLE `evenements_blocage` ADD CONSTRAINT `fk_evenements_blocage_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `appareils` ADD CONSTRAINT `fk_appareils_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `evenements_appareil` ADD CONSTRAINT `fk_evenements_appareil_92d0c5e8e8` FOREIGN KEY (`appareil_id`) REFERENCES `appareils` (`id`);
ALTER TABLE `evenements_appareil` ADD CONSTRAINT `fk_evenements_appareil_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `sessions_lecture` ADD CONSTRAINT `fk_sessions_lecture_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `sessions_lecture` ADD CONSTRAINT `fk_sessions_lecture_dccc261267` FOREIGN KEY (`licence_id`) REFERENCES `licences_contenu` (`id`);
ALTER TABLE `sessions_lecture` ADD CONSTRAINT `fk_sessions_lecture_92d0c5e8e8` FOREIGN KEY (`appareil_id`) REFERENCES `appareils` (`id`);
ALTER TABLE `sessions_lecture` ADD CONSTRAINT `fk_sessions_lecture_4a6473f986` FOREIGN KEY (`session_auth_id`) REFERENCES `sessions_authentification` (`id`);
ALTER TABLE `sessions_lecture` ADD CONSTRAINT `fk_sessions_lecture_172115ff14` FOREIGN KEY (`licence_id`, `compte_id`) REFERENCES `licences_contenu` (`id`, `compte_id`);
ALTER TABLE `sessions_lecture` ADD CONSTRAINT `fk_sessions_lecture_1f0117b0dc` FOREIGN KEY (`appareil_id`, `compte_id`) REFERENCES `appareils` (`id`, `compte_id`);
ALTER TABLE `sessions_lecture` ADD CONSTRAINT `fk_sessions_lecture_d9b7aab294` FOREIGN KEY (`session_auth_id`, `compte_id`) REFERENCES `sessions_authentification` (`id`, `compte_id`);
ALTER TABLE `medias_prives` ADD CONSTRAINT `fk_medias_prives_75580fac81` FOREIGN KEY (`edition_id`) REFERENCES `edition` (`edition_id`);
ALTER TABLE `medias_prives` ADD CONSTRAINT `fk_medias_prives_27a6c5be26` FOREIGN KEY (`format_id`) REFERENCES `format_technique` (`format_id`);
ALTER TABLE `versions_contenu` ADD CONSTRAINT `fk_versions_contenu_2c5dd34761` FOREIGN KEY (`media_prive_id`) REFERENCES `medias_prives` (`id`);
ALTER TABLE `offres_contenu` ADD CONSTRAINT `fk_offres_contenu_9b3980579d` FOREIGN KEY (`offre_id`) REFERENCES `offre` (`offre_id`);
ALTER TABLE `offres_contenu` ADD CONSTRAINT `fk_offres_contenu_86003bf47d` FOREIGN KEY (`version_contenu_id`) REFERENCES `versions_contenu` (`id`);
ALTER TABLE `autorisations_contenu_origine` ADD CONSTRAINT `fk_autorisations_contenu_origine_9a0f3a5ebf` FOREIGN KEY (`origine_id`) REFERENCES `origines_acquisition` (`id`);
ALTER TABLE `autorisations_contenu_origine` ADD CONSTRAINT `fk_autorisations_contenu_origine_86003bf47d` FOREIGN KEY (`version_contenu_id`) REFERENCES `versions_contenu` (`id`);
ALTER TABLE `manifestes_contenu` ADD CONSTRAINT `fk_manifestes_contenu_86003bf47d` FOREIGN KEY (`version_contenu_id`) REFERENCES `versions_contenu` (`id`);
ALTER TABLE `segments_livre` ADD CONSTRAINT `fk_segments_livre_9e5125097a` FOREIGN KEY (`manifeste_id`) REFERENCES `manifestes_contenu` (`id`);
ALTER TABLE `identites_filigrane` ADD CONSTRAINT `fk_identites_filigrane_dccc261267` FOREIGN KEY (`licence_id`) REFERENCES `licences_contenu` (`id`);
ALTER TABLE `preparations_filigrane` ADD CONSTRAINT `fk_preparations_filigrane_2e0ee9f050` FOREIGN KEY (`identite_filigrane_id`) REFERENCES `identites_filigrane` (`id`);
ALTER TABLE `preparations_filigrane` ADD CONSTRAINT `fk_preparations_filigrane_86003bf47d` FOREIGN KEY (`version_contenu_id`) REFERENCES `versions_contenu` (`id`);
ALTER TABLE `jetons_acces` ADD CONSTRAINT `fk_jetons_acces_9b268dadc0` FOREIGN KEY (`session_lecture_id`) REFERENCES `sessions_lecture` (`id`);
ALTER TABLE `jetons_acces` ADD CONSTRAINT `fk_jetons_acces_86003bf47d` FOREIGN KEY (`version_contenu_id`) REFERENCES `versions_contenu` (`id`);
ALTER TABLE `journaux_acces` ADD CONSTRAINT `fk_journaux_acces_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `journaux_acces` ADD CONSTRAINT `fk_journaux_acces_dccc261267` FOREIGN KEY (`licence_id`) REFERENCES `licences_contenu` (`id`);
ALTER TABLE `journaux_acces` ADD CONSTRAINT `fk_journaux_acces_9b268dadc0` FOREIGN KEY (`session_lecture_id`) REFERENCES `sessions_lecture` (`id`);
ALTER TABLE `journaux_acces` ADD CONSTRAINT `fk_journaux_acces_92d0c5e8e8` FOREIGN KEY (`appareil_id`) REFERENCES `appareils` (`id`);
ALTER TABLE `journaux_acces` ADD CONSTRAINT `fk_journaux_acces_6d7fa58d1c` FOREIGN KEY (`jeton_id`) REFERENCES `jetons_acces` (`id`);
ALTER TABLE `journaux_acces` ADD CONSTRAINT `fk_journaux_acces_60572305bd` FOREIGN KEY (`segment_id`) REFERENCES `segments_livre` (`id`);
ALTER TABLE `alertes_securite` ADD CONSTRAINT `fk_alertes_securite_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `alertes_securite` ADD CONSTRAINT `fk_alertes_securite_dccc261267` FOREIGN KEY (`licence_id`) REFERENCES `licences_contenu` (`id`);
ALTER TABLE `alertes_securite` ADD CONSTRAINT `fk_alertes_securite_92d0c5e8e8` FOREIGN KEY (`appareil_id`) REFERENCES `appareils` (`id`);
ALTER TABLE `alertes_securite` ADD CONSTRAINT `fk_alertes_securite_823e78106f` FOREIGN KEY (`responsable_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `alertes_journaux` ADD CONSTRAINT `fk_alertes_journaux_c76ff85a73` FOREIGN KEY (`alerte_id`) REFERENCES `alertes_securite` (`id`);
ALTER TABLE `alertes_journaux` ADD CONSTRAINT `fk_alertes_journaux_206190a76e` FOREIGN KEY (`journal_id`) REFERENCES `journaux_acces` (`id`);
ALTER TABLE `decisions_alerte` ADD CONSTRAINT `fk_decisions_alerte_c76ff85a73` FOREIGN KEY (`alerte_id`) REFERENCES `alertes_securite` (`id`);
ALTER TABLE `decisions_alerte` ADD CONSTRAINT `fk_decisions_alerte_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `progressions_lecture` ADD CONSTRAINT `fk_progressions_lecture_dccc261267` FOREIGN KEY (`licence_id`) REFERENCES `licences_contenu` (`id`);
ALTER TABLE `progressions_lecture` ADD CONSTRAINT `fk_progressions_lecture_86003bf47d` FOREIGN KEY (`version_contenu_id`) REFERENCES `versions_contenu` (`id`);
ALTER TABLE `progressions_lecture` ADD CONSTRAINT `fk_progressions_lecture_5ee72058aa` FOREIGN KEY (`dernier_appareil_id`) REFERENCES `appareils` (`id`);
ALTER TABLE `receptions_extension_contenu` ADD CONSTRAINT `fk_receptions_extension_contenu_fd86a2a692` FOREIGN KEY (`decision_extension_id`) REFERENCES `decisions_extension_contenu` (`id`);
ALTER TABLE `receptions_extension_contenu` ADD CONSTRAINT `fk_receptions_extension_contenu_80cf80e023` FOREIGN KEY (`reference_acquisition_id`) REFERENCES `references_acquisition` (`id`);
ALTER TABLE `receptions_extension_contenu` ADD CONSTRAINT `fk_receptions_extension_contenu_86003bf47d` FOREIGN KEY (`version_contenu_id`) REFERENCES `versions_contenu` (`id`);
ALTER TABLE `metadonnees_bibliotheque` ADD CONSTRAINT `fk_metadonnees_bibliotheque_9a0f3a5ebf` FOREIGN KEY (`origine_id`) REFERENCES `origines_acquisition` (`id`);
ALTER TABLE `manuscrits` ADD CONSTRAINT `fk_manuscrits_986f2a4234` FOREIGN KEY (`deposant_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `manuscrits` ADD CONSTRAINT `fk_manuscrits_d73eda69b5` FOREIGN KEY (`organisation_id`) REFERENCES `organisations` (`id`);
ALTER TABLE `fichiers_manuscrit` ADD CONSTRAINT `fk_fichiers_manuscrit_036f24e3da` FOREIGN KEY (`manuscrit_id`) REFERENCES `manuscrits` (`id`);
ALTER TABLE `fichiers_manuscrit` ADD CONSTRAINT `fk_fichiers_manuscrit_a10b6ac8d8` FOREIGN KEY (`depose_par`) REFERENCES `comptes` (`id`);
ALTER TABLE `dossiers_publication` ADD CONSTRAINT `fk_dossiers_publication_036f24e3da` FOREIGN KEY (`manuscrit_id`) REFERENCES `manuscrits` (`id`);
ALTER TABLE `dossiers_publication` ADD CONSTRAINT `fk_dossiers_publication_e669df51dd` FOREIGN KEY (`precedent_id`) REFERENCES `dossiers_publication` (`id`);
ALTER TABLE `dossiers_publication` ADD CONSTRAINT `fk_dossiers_publication_3159957e59` FOREIGN KEY (`niveau_id`) REFERENCES `niveau_educatif` (`niveau_educatif_id`);
ALTER TABLE `dossiers_publication` ADD CONSTRAINT `fk_dossiers_publication_b35525c3bb` FOREIGN KEY (`oeuvre_cible_id`) REFERENCES `oeuvre` (`oeuvre_id`);
ALTER TABLE `dossiers_publication` ADD CONSTRAINT `fk_dossiers_publication_95f0b708c3` FOREIGN KEY (`precedent_id`, `manuscrit_id`) REFERENCES `dossiers_publication` (`id`, `manuscrit_id`);
ALTER TABLE `selections_fichiers_publication` ADD CONSTRAINT `fk_selections_fichiers_publication_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`);
ALTER TABLE `selections_fichiers_publication` ADD CONSTRAINT `fk_selections_fichiers_publication_c341d0ac6d` FOREIGN KEY (`fichier_id`) REFERENCES `fichiers_manuscrit` (`id`);
ALTER TABLE `selections_fichiers_publication` ADD CONSTRAINT `fk_selections_fichiers_publication_220c298c90` FOREIGN KEY (`format_cible_id`) REFERENCES `format_technique` (`format_id`);
ALTER TABLE `dossiers_categories` ADD CONSTRAINT `fk_dossiers_categories_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`);
ALTER TABLE `dossiers_categories` ADD CONSTRAINT `fk_dossiers_categories_1b981b983f` FOREIGN KEY (`categorie_id`) REFERENCES `categorie` (`categorie_id`);
ALTER TABLE `dossiers_langues` ADD CONSTRAINT `fk_dossiers_langues_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`);
ALTER TABLE `dossiers_langues` ADD CONSTRAINT `fk_dossiers_langues_de182a5d9b` FOREIGN KEY (`langue_id`) REFERENCES `langue` (`langue_id`);
ALTER TABLE `editions_cibles` ADD CONSTRAINT `fk_editions_cibles_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`);
ALTER TABLE `editions_cibles` ADD CONSTRAINT `fk_editions_cibles_3957212bef` FOREIGN KEY (`edition_cible_id`) REFERENCES `edition` (`edition_id`);
ALTER TABLE `editeurs_legaux_publication` ADD CONSTRAINT `fk_editeurs_legaux_publication_96d8157653` FOREIGN KEY (`edition_cible_id`) REFERENCES `editions_cibles` (`id`);
ALTER TABLE `editeurs_legaux_publication` ADD CONSTRAINT `fk_editeurs_legaux_publication_9fa7a99695` FOREIGN KEY (`editeur_id`) REFERENCES `editeur` (`editeur_id`);
ALTER TABLE `isbn_demandes` ADD CONSTRAINT `fk_isbn_demandes_96d8157653` FOREIGN KEY (`edition_cible_id`) REFERENCES `editions_cibles` (`id`);
ALTER TABLE `offres_cibles` ADD CONSTRAINT `fk_offres_cibles_96d8157653` FOREIGN KEY (`edition_cible_id`) REFERENCES `editions_cibles` (`id`);
ALTER TABLE `offres_cibles` ADD CONSTRAINT `fk_offres_cibles_27a6c5be26` FOREIGN KEY (`format_id`) REFERENCES `format_technique` (`format_id`);
ALTER TABLE `offres_cibles` ADD CONSTRAINT `fk_offres_cibles_54ee10151f` FOREIGN KEY (`vendeur_id`) REFERENCES `vendeurs` (`id`);
ALTER TABLE `offres_cibles` ADD CONSTRAINT `fk_offres_cibles_7a3e3dd7d1` FOREIGN KEY (`offre_cible_id`) REFERENCES `offre` (`offre_id`);
ALTER TABLE `preparations_papier` ADD CONSTRAINT `fk_preparations_papier_0a402600f0` FOREIGN KEY (`offre_cible_id`) REFERENCES `offres_cibles` (`id`);
ALTER TABLE `preparations_papier` ADD CONSTRAINT `fk_preparations_papier_229658e0f2` FOREIGN KEY (`unite_stock_id`) REFERENCES `unites_stock` (`id`);
ALTER TABLE `pieces_droits_publication` ADD CONSTRAINT `fk_pieces_droits_publication_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`);
ALTER TABLE `pieces_droits_publication` ADD CONSTRAINT `fk_pieces_droits_publication_a10b6ac8d8` FOREIGN KEY (`depose_par`) REFERENCES `comptes` (`id`);
ALTER TABLE `perimetres_droits_publication` ADD CONSTRAINT `fk_perimetres_droits_publication_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`);
ALTER TABLE `editions_territoires` ADD CONSTRAINT `fk_editions_territoires_96d8157653` FOREIGN KEY (`edition_cible_id`) REFERENCES `editions_cibles` (`id`);
ALTER TABLE `editions_territoires` ADD CONSTRAINT `fk_editions_territoires_0b9be60b2f` FOREIGN KEY (`territoire_id`) REFERENCES `territoires_publication` (`id`);
ALTER TABLE `perimetres_territoires` ADD CONSTRAINT `fk_perimetres_territoires_385788081a` FOREIGN KEY (`perimetre_id`) REFERENCES `perimetres_droits_publication` (`id`);
ALTER TABLE `perimetres_territoires` ADD CONSTRAINT `fk_perimetres_territoires_0b9be60b2f` FOREIGN KEY (`territoire_id`) REFERENCES `territoires_publication` (`id`);
ALTER TABLE `perimetres_formats` ADD CONSTRAINT `fk_perimetres_formats_385788081a` FOREIGN KEY (`perimetre_id`) REFERENCES `perimetres_droits_publication` (`id`);
ALTER TABLE `perimetres_formats` ADD CONSTRAINT `fk_perimetres_formats_27a6c5be26` FOREIGN KEY (`format_id`) REFERENCES `format_technique` (`format_id`);
ALTER TABLE `perimetres_actes` ADD CONSTRAINT `fk_perimetres_actes_385788081a` FOREIGN KEY (`perimetre_id`) REFERENCES `perimetres_droits_publication` (`id`);
ALTER TABLE `perimetres_pieces` ADD CONSTRAINT `fk_perimetres_pieces_385788081a` FOREIGN KEY (`perimetre_id`) REFERENCES `perimetres_droits_publication` (`id`);
ALTER TABLE `perimetres_pieces` ADD CONSTRAINT `fk_perimetres_pieces_ec57fb551d` FOREIGN KEY (`piece_id`) REFERENCES `pieces_droits_publication` (`id`);
ALTER TABLE `perimetres_editions` ADD CONSTRAINT `fk_perimetres_editions_385788081a` FOREIGN KEY (`perimetre_id`) REFERENCES `perimetres_droits_publication` (`id`);
ALTER TABLE `perimetres_editions` ADD CONSTRAINT `fk_perimetres_editions_96d8157653` FOREIGN KEY (`edition_cible_id`) REFERENCES `editions_cibles` (`id`);
ALTER TABLE `contributions_publication` ADD CONSTRAINT `fk_contributions_publication_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`);
ALTER TABLE `contributions_publication` ADD CONSTRAINT `fk_contributions_publication_e44f799281` FOREIGN KEY (`auteur_id`) REFERENCES `auteur` (`auteur_id`);
ALTER TABLE `medias_soumis_publication` ADD CONSTRAINT `fk_medias_soumis_publication_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`);
ALTER TABLE `exigences_dossier` ADD CONSTRAINT `fk_exigences_dossier_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`);
ALTER TABLE `exigences_dossier` ADD CONSTRAINT `fk_exigences_dossier_cea39f9a74` FOREIGN KEY (`regle_id`) REFERENCES `regles_controle_publication` (`id`);
ALTER TABLE `acces_dossiers_publication` ADD CONSTRAINT `fk_acces_dossiers_publication_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`);
ALTER TABLE `acces_dossiers_publication` ADD CONSTRAINT `fk_acces_dossiers_publication_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `affectations_revue` ADD CONSTRAINT `fk_affectations_revue_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`);
ALTER TABLE `affectations_revue` ADD CONSTRAINT `fk_affectations_revue_823e78106f` FOREIGN KEY (`responsable_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `controles_publication` ADD CONSTRAINT `fk_controles_publication_6440fb55b5` FOREIGN KEY (`exigence_id`) REFERENCES `exigences_dossier` (`id`);
ALTER TABLE `controles_publication` ADD CONSTRAINT `fk_controles_publication_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `elements_controle` ADD CONSTRAINT `fk_elements_controle_a81cd81ed7` FOREIGN KEY (`controle_id`) REFERENCES `controles_publication` (`id`);
ALTER TABLE `elements_controle` ADD CONSTRAINT `fk_elements_controle_c391d4f36d` FOREIGN KEY (`dossier_global_id`) REFERENCES `dossiers_publication` (`id`);
ALTER TABLE `elements_controle` ADD CONSTRAINT `fk_elements_controle_1c86a07e5c` FOREIGN KEY (`selection_id`) REFERENCES `selections_fichiers_publication` (`id`);
ALTER TABLE `elements_controle` ADD CONSTRAINT `fk_elements_controle_ec57fb551d` FOREIGN KEY (`piece_id`) REFERENCES `pieces_droits_publication` (`id`);
ALTER TABLE `elements_controle` ADD CONSTRAINT `fk_elements_controle_96d8157653` FOREIGN KEY (`edition_cible_id`) REFERENCES `editions_cibles` (`id`);
ALTER TABLE `elements_controle` ADD CONSTRAINT `fk_elements_controle_0a402600f0` FOREIGN KEY (`offre_cible_id`) REFERENCES `offres_cibles` (`id`);
ALTER TABLE `elements_controle` ADD CONSTRAINT `fk_elements_controle_b12e1d8d4f` FOREIGN KEY (`contribution_id`) REFERENCES `contributions_publication` (`id`);
ALTER TABLE `elements_controle` ADD CONSTRAINT `fk_elements_controle_6f04578157` FOREIGN KEY (`media_soumis_id`) REFERENCES `medias_soumis_publication` (`id`);
ALTER TABLE `elements_controle` ADD CONSTRAINT `fk_elements_controle_385788081a` FOREIGN KEY (`perimetre_id`) REFERENCES `perimetres_droits_publication` (`id`);
ALTER TABLE `decisions_publication` ADD CONSTRAINT `fk_decisions_publication_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`);
ALTER TABLE `decisions_publication` ADD CONSTRAINT `fk_decisions_publication_b20aeb369b` FOREIGN KEY (`decideur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `decisions_controles` ADD CONSTRAINT `fk_decisions_controles_23f016ba3f` FOREIGN KEY (`decision_id`) REFERENCES `decisions_publication` (`id`);
ALTER TABLE `decisions_controles` ADD CONSTRAINT `fk_decisions_controles_a81cd81ed7` FOREIGN KEY (`controle_id`) REFERENCES `controles_publication` (`id`);
ALTER TABLE `executions_publication` ADD CONSTRAINT `fk_executions_publication_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`);
ALTER TABLE `tentatives_publication` ADD CONSTRAINT `fk_tentatives_publication_c507e46451` FOREIGN KEY (`execution_id`) REFERENCES `executions_publication` (`id`);
ALTER TABLE `productions_contenu` ADD CONSTRAINT `fk_productions_contenu_c507e46451` FOREIGN KEY (`execution_id`) REFERENCES `executions_publication` (`id`);
ALTER TABLE `productions_contenu` ADD CONSTRAINT `fk_productions_contenu_96d8157653` FOREIGN KEY (`edition_cible_id`) REFERENCES `editions_cibles` (`id`);
ALTER TABLE `productions_contenu` ADD CONSTRAINT `fk_productions_contenu_27a6c5be26` FOREIGN KEY (`format_id`) REFERENCES `format_technique` (`format_id`);
ALTER TABLE `productions_contenu` ADD CONSTRAINT `fk_productions_contenu_8829188f33` FOREIGN KEY (`version_resultat_id`) REFERENCES `versions_contenu` (`id`);
ALTER TABLE `productions_sources` ADD CONSTRAINT `fk_productions_sources_0dad25e4f0` FOREIGN KEY (`production_id`) REFERENCES `productions_contenu` (`id`);
ALTER TABLE `productions_sources` ADD CONSTRAINT `fk_productions_sources_1c86a07e5c` FOREIGN KEY (`selection_id`) REFERENCES `selections_fichiers_publication` (`id`);
ALTER TABLE `resultats_oeuvres_publication` ADD CONSTRAINT `fk_resultats_oeuvres_publication_c507e46451` FOREIGN KEY (`execution_id`) REFERENCES `executions_publication` (`id`);
ALTER TABLE `resultats_oeuvres_publication` ADD CONSTRAINT `fk_resultats_oeuvres_publication_13b943eabc` FOREIGN KEY (`oeuvre_id`) REFERENCES `oeuvre` (`oeuvre_id`);
ALTER TABLE `resultats_editions_publication` ADD CONSTRAINT `fk_resultats_editions_publication_96d8157653` FOREIGN KEY (`edition_cible_id`) REFERENCES `editions_cibles` (`id`);
ALTER TABLE `resultats_editions_publication` ADD CONSTRAINT `fk_resultats_editions_publication_c507e46451` FOREIGN KEY (`execution_id`) REFERENCES `executions_publication` (`id`);
ALTER TABLE `resultats_editions_publication` ADD CONSTRAINT `fk_resultats_editions_publication_75580fac81` FOREIGN KEY (`edition_id`) REFERENCES `edition` (`edition_id`);
ALTER TABLE `resultats_offres_publication` ADD CONSTRAINT `fk_resultats_offres_publication_0a402600f0` FOREIGN KEY (`offre_cible_id`) REFERENCES `offres_cibles` (`id`);
ALTER TABLE `resultats_offres_publication` ADD CONSTRAINT `fk_resultats_offres_publication_c507e46451` FOREIGN KEY (`execution_id`) REFERENCES `executions_publication` (`id`);
ALTER TABLE `resultats_offres_publication` ADD CONSTRAINT `fk_resultats_offres_publication_9b3980579d` FOREIGN KEY (`offre_id`) REFERENCES `offre` (`offre_id`);
ALTER TABLE `derives_catalogue_publication` ADD CONSTRAINT `fk_derives_catalogue_publication_c507e46451` FOREIGN KEY (`execution_id`) REFERENCES `executions_publication` (`id`);
ALTER TABLE `derives_catalogue_publication` ADD CONSTRAINT `fk_derives_catalogue_publication_6f04578157` FOREIGN KEY (`media_soumis_id`) REFERENCES `medias_soumis_publication` (`id`);
ALTER TABLE `derives_catalogue_publication` ADD CONSTRAINT `fk_derives_catalogue_publication_a23d04988b` FOREIGN KEY (`media_catalogue_id`) REFERENCES `media_catalogue` (`media_catalogue_id`);
ALTER TABLE `instructions_retrait_publication` ADD CONSTRAINT `fk_instructions_retrait_publication_036f24e3da` FOREIGN KEY (`manuscrit_id`) REFERENCES `manuscrits` (`id`);
ALTER TABLE `instructions_retrait_publication` ADD CONSTRAINT `fk_instructions_retrait_publication_bc17b2f481` FOREIGN KEY (`restriction_source_id`) REFERENCES `restrictions_conformite` (`id`);
ALTER TABLE `instructions_retrait_publication` ADD CONSTRAINT `fk_instructions_retrait_publication_bf53b87dbc` FOREIGN KEY (`restriction_levee_id`) REFERENCES `restrictions_conformite` (`id`);
ALTER TABLE `instructions_retrait_publication` ADD CONSTRAINT `fk_instructions_retrait_publication_886afd29ba` FOREIGN KEY (`cible_manuscrit_id`) REFERENCES `manuscrits` (`id`);
ALTER TABLE `instructions_retrait_publication` ADD CONSTRAINT `fk_instructions_retrait_publication_13b943eabc` FOREIGN KEY (`oeuvre_id`) REFERENCES `oeuvre` (`oeuvre_id`);
ALTER TABLE `instructions_retrait_publication` ADD CONSTRAINT `fk_instructions_retrait_publication_75580fac81` FOREIGN KEY (`edition_id`) REFERENCES `edition` (`edition_id`);
ALTER TABLE `instructions_retrait_publication` ADD CONSTRAINT `fk_instructions_retrait_publication_9b3980579d` FOREIGN KEY (`offre_id`) REFERENCES `offre` (`offre_id`);
ALTER TABLE `instructions_retrait_publication` ADD CONSTRAINT `fk_instructions_retrait_publication_d34a9dc928` FOREIGN KEY (`version_id`) REFERENCES `versions_contenu` (`id`);
ALTER TABLE `instructions_retrait_publication` ADD CONSTRAINT `fk_instructions_retrait_publication_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `effets_retrait_publication` ADD CONSTRAINT `fk_effets_retrait_publication_b02a50a4d3` FOREIGN KEY (`instruction_id`) REFERENCES `instructions_retrait_publication` (`id`);
ALTER TABLE `decisions_extension_contenu` ADD CONSTRAINT `fk_decisions_extension_contenu_036f24e3da` FOREIGN KEY (`manuscrit_id`) REFERENCES `manuscrits` (`id`);
ALTER TABLE `decisions_extension_contenu` ADD CONSTRAINT `fk_decisions_extension_contenu_86003bf47d` FOREIGN KEY (`version_contenu_id`) REFERENCES `versions_contenu` (`id`);
ALTER TABLE `decisions_extension_contenu` ADD CONSTRAINT `fk_decisions_extension_contenu_e0542c6149` FOREIGN KEY (`approbateur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `cibles_extension_contenu` ADD CONSTRAINT `fk_cibles_extension_contenu_fbe8cb9000` FOREIGN KEY (`decision_id`) REFERENCES `decisions_extension_contenu` (`id`);
ALTER TABLE `cibles_extension_contenu` ADD CONSTRAINT `fk_cibles_extension_contenu_80cf80e023` FOREIGN KEY (`reference_acquisition_id`) REFERENCES `references_acquisition` (`id`);
ALTER TABLE `cibles_extension_contenu` ADD CONSTRAINT `fk_cibles_extension_contenu_9a0f3a5ebf` FOREIGN KEY (`origine_id`) REFERENCES `origines_acquisition` (`id`);
ALTER TABLE `evenements_publication` ADD CONSTRAINT `fk_evenements_publication_036f24e3da` FOREIGN KEY (`manuscrit_id`) REFERENCES `manuscrits` (`id`);
ALTER TABLE `evenements_publication` ADD CONSTRAINT `fk_evenements_publication_05e20d3527` FOREIGN KEY (`dossier_id`) REFERENCES `dossiers_publication` (`id`);
ALTER TABLE `evenements_publication` ADD CONSTRAINT `fk_evenements_publication_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `evenements_publication` ADD CONSTRAINT `fk_evenements_publication_761a7b0b71` FOREIGN KEY (`dossier_id`, `manuscrit_id`) REFERENCES `dossiers_publication` (`id`, `manuscrit_id`);
ALTER TABLE `droits_offres_publication` ADD CONSTRAINT `fk_droits_offres_publication_9b3980579d` FOREIGN KEY (`offre_id`) REFERENCES `offre` (`offre_id`);
ALTER TABLE `droits_offres_publication` ADD CONSTRAINT `fk_droits_offres_publication_385788081a` FOREIGN KEY (`perimetre_id`) REFERENCES `perimetres_droits_publication` (`id`);
ALTER TABLE `droits_offres_publication` ADD CONSTRAINT `fk_droits_offres_publication_c507e46451` FOREIGN KEY (`execution_id`) REFERENCES `executions_publication` (`id`);
ALTER TABLE `droits_versions_publication` ADD CONSTRAINT `fk_droits_versions_publication_d34a9dc928` FOREIGN KEY (`version_id`) REFERENCES `versions_contenu` (`id`);
ALTER TABLE `droits_versions_publication` ADD CONSTRAINT `fk_droits_versions_publication_385788081a` FOREIGN KEY (`perimetre_id`) REFERENCES `perimetres_droits_publication` (`id`);
ALTER TABLE `droits_versions_publication` ADD CONSTRAINT `fk_droits_versions_publication_c507e46451` FOREIGN KEY (`execution_id`) REFERENCES `executions_publication` (`id`);
ALTER TABLE `plaignants` ADD CONSTRAINT `fk_plaignants_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `signalements_contenu` ADD CONSTRAINT `fk_signalements_contenu_92dd4f7653` FOREIGN KEY (`plaignant_id`) REFERENCES `plaignants` (`id`);
ALTER TABLE `complements_signalement` ADD CONSTRAINT `fk_complements_signalement_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`);
ALTER TABLE `contenus_vises` ADD CONSTRAINT `fk_contenus_vises_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`);
ALTER TABLE `contenus_vises` ADD CONSTRAINT `fk_contenus_vises_674fec4915` FOREIGN KEY (`complement_id`) REFERENCES `complements_signalement` (`id`);
ALTER TABLE `contenus_vises` ADD CONSTRAINT `fk_contenus_vises_e67a56a050` FOREIGN KEY (`complement_id`, `signalement_id`) REFERENCES `complements_signalement` (`id`, `signalement_id`);
ALTER TABLE `resolutions_cibles` ADD CONSTRAINT `fk_resolutions_cibles_4e3423e639` FOREIGN KEY (`contenu_vise_id`) REFERENCES `contenus_vises` (`id`);
ALTER TABLE `resolutions_cibles` ADD CONSTRAINT `fk_resolutions_cibles_13b943eabc` FOREIGN KEY (`oeuvre_id`) REFERENCES `oeuvre` (`oeuvre_id`);
ALTER TABLE `resolutions_cibles` ADD CONSTRAINT `fk_resolutions_cibles_75580fac81` FOREIGN KEY (`edition_id`) REFERENCES `edition` (`edition_id`);
ALTER TABLE `resolutions_cibles` ADD CONSTRAINT `fk_resolutions_cibles_9b3980579d` FOREIGN KEY (`offre_id`) REFERENCES `offre` (`offre_id`);
ALTER TABLE `resolutions_cibles` ADD CONSTRAINT `fk_resolutions_cibles_a23d04988b` FOREIGN KEY (`media_catalogue_id`) REFERENCES `media_catalogue` (`media_catalogue_id`);
ALTER TABLE `resolutions_cibles` ADD CONSTRAINT `fk_resolutions_cibles_a3a27d5058` FOREIGN KEY (`avis_id`) REFERENCES `avis` (`avis_id`);
ALTER TABLE `resolutions_cibles` ADD CONSTRAINT `fk_resolutions_cibles_86003bf47d` FOREIGN KEY (`version_contenu_id`) REFERENCES `versions_contenu` (`id`);
ALTER TABLE `resolutions_cibles` ADD CONSTRAINT `fk_resolutions_cibles_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `droits_invoques` ADD CONSTRAINT `fk_droits_invoques_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`);
ALTER TABLE `droits_invoques` ADD CONSTRAINT `fk_droits_invoques_674fec4915` FOREIGN KEY (`complement_id`) REFERENCES `complements_signalement` (`id`);
ALTER TABLE `droits_invoques` ADD CONSTRAINT `fk_droits_invoques_e67a56a050` FOREIGN KEY (`complement_id`, `signalement_id`) REFERENCES `complements_signalement` (`id`, `signalement_id`);
ALTER TABLE `droits_territoires` ADD CONSTRAINT `fk_droits_territoires_a3ba5bfeec` FOREIGN KEY (`droit_id`) REFERENCES `droits_invoques` (`id`);
ALTER TABLE `droits_territoires` ADD CONSTRAINT `fk_droits_territoires_8d2076ce20` FOREIGN KEY (`territoire_id`) REFERENCES `territoires_conformite` (`id`);
ALTER TABLE `pieces_rattachements` ADD CONSTRAINT `fk_pieces_rattachements_d1f63ca293` FOREIGN KEY (`piece_id`) REFERENCES `pieces_conformite` (`id`);
ALTER TABLE `pieces_rattachements` ADD CONSTRAINT `fk_pieces_rattachements_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`);
ALTER TABLE `pieces_rattachements` ADD CONSTRAINT `fk_pieces_rattachements_674fec4915` FOREIGN KEY (`complement_id`) REFERENCES `complements_signalement` (`id`);
ALTER TABLE `pieces_rattachements` ADD CONSTRAINT `fk_pieces_rattachements_c699391bfa` FOREIGN KEY (`reponse_id`) REFERENCES `reponses_fournisseur` (`id`);
ALTER TABLE `controles_completude` ADD CONSTRAINT `fk_controles_completude_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`);
ALTER TABLE `controles_completude` ADD CONSTRAINT `fk_controles_completude_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `preuves_completude` ADD CONSTRAINT `fk_preuves_completude_b0bcf9af74` FOREIGN KEY (`controle_id`) REFERENCES `controles_completude` (`id`);
ALTER TABLE `preuves_completude` ADD CONSTRAINT `fk_preuves_completude_c34c8dee41` FOREIGN KEY (`contenu_id`) REFERENCES `contenus_vises` (`id`);
ALTER TABLE `preuves_completude` ADD CONSTRAINT `fk_preuves_completude_a3ba5bfeec` FOREIGN KEY (`droit_id`) REFERENCES `droits_invoques` (`id`);
ALTER TABLE `preuves_completude` ADD CONSTRAINT `fk_preuves_completude_d1f63ca293` FOREIGN KEY (`piece_id`) REFERENCES `pieces_conformite` (`id`);
ALTER TABLE `preuves_completude` ADD CONSTRAINT `fk_preuves_completude_674fec4915` FOREIGN KEY (`complement_id`) REFERENCES `complements_signalement` (`id`);
ALTER TABLE `phases_instruction` ADD CONSTRAINT `fk_phases_instruction_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`);
ALTER TABLE `phases_instruction` ADD CONSTRAINT `fk_phases_instruction_28ba3c63b4` FOREIGN KEY (`precedente_id`) REFERENCES `phases_instruction` (`id`);
ALTER TABLE `phases_instruction` ADD CONSTRAINT `fk_phases_instruction_2b9f0a7cad` FOREIGN KEY (`decision_contestee_id`) REFERENCES `decisions_moderation` (`id`);
ALTER TABLE `phases_instruction` ADD CONSTRAINT `fk_phases_instruction_b59061ae2e` FOREIGN KEY (`precedente_id`, `signalement_id`) REFERENCES `phases_instruction` (`id`, `signalement_id`);
ALTER TABLE `affectations_conformite` ADD CONSTRAINT `fk_affectations_conformite_00da6ae87e` FOREIGN KEY (`phase_id`) REFERENCES `phases_instruction` (`id`);
ALTER TABLE `affectations_conformite` ADD CONSTRAINT `fk_affectations_conformite_a2e76a7a7d` FOREIGN KEY (`responsable_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `acces_dossiers_conformite` ADD CONSTRAINT `fk_acces_dossiers_conformite_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `acces_dossiers_conformite` ADD CONSTRAINT `fk_acces_dossiers_conformite_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`);
ALTER TABLE `acces_dossiers_conformite` ADD CONSTRAINT `fk_acces_dossiers_conformite_fe1d57a629` FOREIGN KEY (`litige_id`) REFERENCES `contestations_paiement` (`id`);
ALTER TABLE `parties_fournisseur` ADD CONSTRAINT `fk_parties_fournisseur_00da6ae87e` FOREIGN KEY (`phase_id`) REFERENCES `phases_instruction` (`id`);
ALTER TABLE `parties_fournisseur` ADD CONSTRAINT `fk_parties_fournisseur_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `parties_fournisseur` ADD CONSTRAINT `fk_parties_fournisseur_d73eda69b5` FOREIGN KEY (`organisation_id`) REFERENCES `organisations` (`id`);
ALTER TABLE `parties_fournisseur` ADD CONSTRAINT `fk_parties_fournisseur_54ee10151f` FOREIGN KEY (`vendeur_id`) REFERENCES `vendeurs` (`id`);
ALTER TABLE `parties_cibles` ADD CONSTRAINT `fk_parties_cibles_405965a1a8` FOREIGN KEY (`partie_id`) REFERENCES `parties_fournisseur` (`id`);
ALTER TABLE `parties_cibles` ADD CONSTRAINT `fk_parties_cibles_fc0cbc7ed7` FOREIGN KEY (`resolution_id`) REFERENCES `resolutions_cibles` (`id`);
ALTER TABLE `acces_reponse_conformite` ADD CONSTRAINT `fk_acces_reponse_conformite_405965a1a8` FOREIGN KEY (`partie_id`) REFERENCES `parties_fournisseur` (`id`);
ALTER TABLE `usages_acces_reponse` ADD CONSTRAINT `fk_usages_acces_reponse_d92967da0e` FOREIGN KEY (`acces_id`) REFERENCES `acces_reponse_conformite` (`id`);
ALTER TABLE `notifications_fournisseur` ADD CONSTRAINT `fk_notifications_fournisseur_405965a1a8` FOREIGN KEY (`partie_id`) REFERENCES `parties_fournisseur` (`id`);
ALTER TABLE `faits_notification` ADD CONSTRAINT `fk_faits_notification_8e449da11b` FOREIGN KEY (`notification_id`) REFERENCES `notifications_fournisseur` (`id`);
ALTER TABLE `reponses_fournisseur` ADD CONSTRAINT `fk_reponses_fournisseur_405965a1a8` FOREIGN KEY (`partie_id`) REFERENCES `parties_fournisseur` (`id`);
ALTER TABLE `reponses_fournisseur` ADD CONSTRAINT `fk_reponses_fournisseur_8e449da11b` FOREIGN KEY (`notification_id`) REFERENCES `notifications_fournisseur` (`id`);
ALTER TABLE `reponses_fournisseur` ADD CONSTRAINT `fk_reponses_fournisseur_d92967da0e` FOREIGN KEY (`acces_id`) REFERENCES `acces_reponse_conformite` (`id`);
ALTER TABLE `reponses_fournisseur` ADD CONSTRAINT `fk_reponses_fournisseur_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `reponses_fournisseur` ADD CONSTRAINT `fk_reponses_fournisseur_b0d494995c` FOREIGN KEY (`notification_id`, `partie_id`) REFERENCES `notifications_fournisseur` (`id`, `partie_id`);
ALTER TABLE `reponses_fournisseur` ADD CONSTRAINT `fk_reponses_fournisseur_b4c190bb1d` FOREIGN KEY (`acces_id`, `partie_id`) REFERENCES `acces_reponse_conformite` (`id`, `partie_id`);
ALTER TABLE `echeances_conformite` ADD CONSTRAINT `fk_echeances_conformite_00da6ae87e` FOREIGN KEY (`phase_id`) REFERENCES `phases_instruction` (`id`);
ALTER TABLE `echeances_conformite` ADD CONSTRAINT `fk_echeances_conformite_405965a1a8` FOREIGN KEY (`partie_id`) REFERENCES `parties_fournisseur` (`id`);
ALTER TABLE `echeances_conformite` ADD CONSTRAINT `fk_echeances_conformite_8e449da11b` FOREIGN KEY (`notification_id`) REFERENCES `notifications_fournisseur` (`id`);
ALTER TABLE `echeances_conformite` ADD CONSTRAINT `fk_echeances_conformite_edc5fb5026` FOREIGN KEY (`parametre_id`) REFERENCES `parametres_delai_conformite` (`id`);
ALTER TABLE `echeances_conformite` ADD CONSTRAINT `fk_echeances_conformite_af0445bd98` FOREIGN KEY (`fait_notification_id`) REFERENCES `faits_notification` (`id`);
ALTER TABLE `echeances_conformite` ADD CONSTRAINT `fk_echeances_conformite_19ef3b61bf` FOREIGN KEY (`partie_id`, `phase_id`) REFERENCES `parties_fournisseur` (`id`, `phase_id`);
ALTER TABLE `echeances_conformite` ADD CONSTRAINT `fk_echeances_conformite_b0d494995c` FOREIGN KEY (`notification_id`, `partie_id`) REFERENCES `notifications_fournisseur` (`id`, `partie_id`);
ALTER TABLE `echeances_conformite` ADD CONSTRAINT `fk_echeances_conformite_341dfe6fb8` FOREIGN KEY (`fait_notification_id`, `notification_id`) REFERENCES `faits_notification` (`id`, `notification_id`);
ALTER TABLE `ajustements_echeance` ADD CONSTRAINT `fk_ajustements_echeance_d1d4f527ad` FOREIGN KEY (`echeance_id`) REFERENCES `echeances_conformite` (`id`);
ALTER TABLE `ajustements_echeance` ADD CONSTRAINT `fk_ajustements_echeance_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `decisions_moderation` ADD CONSTRAINT `fk_decisions_moderation_00da6ae87e` FOREIGN KEY (`phase_id`) REFERENCES `phases_instruction` (`id`);
ALTER TABLE `decisions_moderation` ADD CONSTRAINT `fk_decisions_moderation_17bb05379b` FOREIGN KEY (`decideur_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `mesures_conservatoires` ADD CONSTRAINT `fk_mesures_conservatoires_00da6ae87e` FOREIGN KEY (`phase_id`) REFERENCES `phases_instruction` (`id`);
ALTER TABLE `mesures_conservatoires` ADD CONSTRAINT `fk_mesures_conservatoires_17bb05379b` FOREIGN KEY (`decideur_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `portees_decisions_mesures` ADD CONSTRAINT `fk_portees_decisions_mesures_024cdf0aed` FOREIGN KEY (`decision_id`) REFERENCES `decisions_moderation` (`id`);
ALTER TABLE `portees_decisions_mesures` ADD CONSTRAINT `fk_portees_decisions_mesures_ac460afc57` FOREIGN KEY (`mesure_id`) REFERENCES `mesures_conservatoires` (`id`);
ALTER TABLE `portees_decisions_mesures` ADD CONSTRAINT `fk_portees_decisions_mesures_fc0cbc7ed7` FOREIGN KEY (`resolution_id`) REFERENCES `resolutions_cibles` (`id`);
ALTER TABLE `preuves_decisions_mesures` ADD CONSTRAINT `fk_preuves_decisions_mesures_024cdf0aed` FOREIGN KEY (`decision_id`) REFERENCES `decisions_moderation` (`id`);
ALTER TABLE `preuves_decisions_mesures` ADD CONSTRAINT `fk_preuves_decisions_mesures_ac460afc57` FOREIGN KEY (`mesure_id`) REFERENCES `mesures_conservatoires` (`id`);
ALTER TABLE `preuves_decisions_mesures` ADD CONSTRAINT `fk_preuves_decisions_mesures_d1f63ca293` FOREIGN KEY (`piece_id`) REFERENCES `pieces_conformite` (`id`);
ALTER TABLE `preuves_decisions_mesures` ADD CONSTRAINT `fk_preuves_decisions_mesures_b0bcf9af74` FOREIGN KEY (`controle_id`) REFERENCES `controles_completude` (`id`);
ALTER TABLE `preuves_decisions_mesures` ADD CONSTRAINT `fk_preuves_decisions_mesures_c699391bfa` FOREIGN KEY (`reponse_id`) REFERENCES `reponses_fournisseur` (`id`);
ALTER TABLE `ordres_retrait` ADD CONSTRAINT `fk_ordres_retrait_024cdf0aed` FOREIGN KEY (`decision_id`) REFERENCES `decisions_moderation` (`id`);
ALTER TABLE `ordres_retrait` ADD CONSTRAINT `fk_ordres_retrait_ac460afc57` FOREIGN KEY (`mesure_id`) REFERENCES `mesures_conservatoires` (`id`);
ALTER TABLE `lignes_ordres_retrait` ADD CONSTRAINT `fk_lignes_ordres_retrait_d0f0ffc543` FOREIGN KEY (`ordre_id`) REFERENCES `ordres_retrait` (`id`);
ALTER TABLE `lignes_ordres_retrait` ADD CONSTRAINT `fk_lignes_ordres_retrait_fc0cbc7ed7` FOREIGN KEY (`resolution_id`) REFERENCES `resolutions_cibles` (`id`);
ALTER TABLE `lignes_ordres_retrait` ADD CONSTRAINT `fk_lignes_ordres_retrait_136a389008` FOREIGN KEY (`portee_id`) REFERENCES `portees_decisions_mesures` (`id`);
ALTER TABLE `lignes_ordres_retrait` ADD CONSTRAINT `fk_lignes_ordres_retrait_bf53b87dbc` FOREIGN KEY (`restriction_levee_id`) REFERENCES `restrictions_conformite` (`id`);
ALTER TABLE `developpements_portee` ADD CONSTRAINT `fk_developpements_portee_136a389008` FOREIGN KEY (`portee_id`) REFERENCES `portees_decisions_mesures` (`id`);
ALTER TABLE `developpements_portee` ADD CONSTRAINT `fk_developpements_portee_f7a720cd2c` FOREIGN KEY (`resolution_enfant_id`) REFERENCES `resolutions_cibles` (`id`);
ALTER TABLE `restrictions_conformite` ADD CONSTRAINT `fk_restrictions_conformite_5af095e2d3` FOREIGN KEY (`ligne_creatrice_id`) REFERENCES `lignes_ordres_retrait` (`id`);
ALTER TABLE `restrictions_conformite` ADD CONSTRAINT `fk_restrictions_conformite_4bfce63e32` FOREIGN KEY (`instruction_publication_id`) REFERENCES `instructions_retrait_publication` (`id`);
ALTER TABLE `confirmations_effet` ADD CONSTRAINT `fk_confirmations_effet_fbd23836f4` FOREIGN KEY (`ligne_id`) REFERENCES `lignes_ordres_retrait` (`id`);
ALTER TABLE `faits_litige` ADD CONSTRAINT `fk_faits_litige_fe1d57a629` FOREIGN KEY (`litige_id`) REFERENCES `contestations_paiement` (`id`);
ALTER TABLE `litiges_paiements` ADD CONSTRAINT `fk_litiges_paiements_fe1d57a629` FOREIGN KEY (`litige_id`) REFERENCES `contestations_paiement` (`id`);
ALTER TABLE `litiges_paiements` ADD CONSTRAINT `fk_litiges_paiements_571ddb756e` FOREIGN KEY (`paiement_id`) REFERENCES `paiements` (`id`);
ALTER TABLE `litiges_commandes` ADD CONSTRAINT `fk_litiges_commandes_fe1d57a629` FOREIGN KEY (`litige_id`) REFERENCES `contestations_paiement` (`id`);
ALTER TABLE `litiges_commandes` ADD CONSTRAINT `fk_litiges_commandes_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`);
ALTER TABLE `litiges_anomalies` ADD CONSTRAINT `fk_litiges_anomalies_fe1d57a629` FOREIGN KEY (`litige_id`) REFERENCES `contestations_paiement` (`id`);
ALTER TABLE `litiges_anomalies` ADD CONSTRAINT `fk_litiges_anomalies_1f2ee634f4` FOREIGN KEY (`anomalie_id`) REFERENCES `anomalies_paiement` (`id`);
ALTER TABLE `instructions_conformite_paiement` ADD CONSTRAINT `fk_instructions_conformite_paiement_fe1d57a629` FOREIGN KEY (`litige_id`) REFERENCES `contestations_paiement` (`id`);
ALTER TABLE `instructions_conformite_paiement` ADD CONSTRAINT `fk_instructions_conformite_paiement_fe35155024` FOREIGN KEY (`gel_leve_id`) REFERENCES `gels_operations_financieres` (`id`);
ALTER TABLE `instructions_conformite_paiement` ADD CONSTRAINT `fk_instructions_conformite_paiement_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `cibles_instructions_paiement` ADD CONSTRAINT `fk_cibles_instructions_paiement_aade979bc3` FOREIGN KEY (`instruction_id`) REFERENCES `instructions_conformite_paiement` (`id`);
ALTER TABLE `cibles_instructions_paiement` ADD CONSTRAINT `fk_cibles_instructions_paiement_571ddb756e` FOREIGN KEY (`paiement_id`) REFERENCES `paiements` (`id`);
ALTER TABLE `cibles_instructions_paiement` ADD CONSTRAINT `fk_cibles_instructions_paiement_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`);
ALTER TABLE `cibles_instructions_paiement` ADD CONSTRAINT `fk_cibles_instructions_paiement_d975e28e84` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `gels_operations_financieres` ADD CONSTRAINT `fk_gels_operations_financieres_2d61304f77` FOREIGN KEY (`instruction_creatrice_id`) REFERENCES `instructions_conformite_paiement` (`id`);
ALTER TABLE `cibles_gels_financiers` ADD CONSTRAINT `fk_cibles_gels_financiers_1657037bdf` FOREIGN KEY (`gel_id`) REFERENCES `gels_operations_financieres` (`id`);
ALTER TABLE `cibles_gels_financiers` ADD CONSTRAINT `fk_cibles_gels_financiers_571ddb756e` FOREIGN KEY (`paiement_id`) REFERENCES `paiements` (`id`);
ALTER TABLE `cibles_gels_financiers` ADD CONSTRAINT `fk_cibles_gels_financiers_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`);
ALTER TABLE `demandes_revocation_origines` ADD CONSTRAINT `fk_demandes_revocation_origines_024cdf0aed` FOREIGN KEY (`decision_id`) REFERENCES `decisions_moderation` (`id`);
ALTER TABLE `demandes_revocation_origines` ADD CONSTRAINT `fk_demandes_revocation_origines_933ac7cd20` FOREIGN KEY (`instruction_paiement_id`) REFERENCES `instructions_conformite_paiement` (`id`);
ALTER TABLE `cibles_revocation_origine` ADD CONSTRAINT `fk_cibles_revocation_origine_e7b8a48218` FOREIGN KEY (`demande_id`) REFERENCES `demandes_revocation_origines` (`id`);
ALTER TABLE `cibles_revocation_origine` ADD CONSTRAINT `fk_cibles_revocation_origine_9a0f3a5ebf` FOREIGN KEY (`origine_id`) REFERENCES `origines_acquisition` (`id`);
ALTER TABLE `demandes_compensation_conformite` ADD CONSTRAINT `fk_demandes_compensation_conformite_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`);
ALTER TABLE `demandes_compensation_conformite` ADD CONSTRAINT `fk_demandes_compensation_conformite_fe1d57a629` FOREIGN KEY (`litige_id`) REFERENCES `contestations_paiement` (`id`);
ALTER TABLE `demandes_compensation_conformite` ADD CONSTRAINT `fk_demandes_compensation_conformite_024cdf0aed` FOREIGN KEY (`decision_id`) REFERENCES `decisions_moderation` (`id`);
ALTER TABLE `demandes_compensation_conformite` ADD CONSTRAINT `fk_demandes_compensation_conformite_38683c76ec` FOREIGN KEY (`commande_id`) REFERENCES `commandes` (`id`);
ALTER TABLE `demandes_compensation_conformite` ADD CONSTRAINT `fk_demandes_compensation_conformite_f839016762` FOREIGN KEY (`dossier_reutilisation_id`) REFERENCES `dossiers_remboursement` (`id`);
ALTER TABLE `demandes_compensation_conformite` ADD CONSTRAINT `fk_demandes_compensation_conformite_26081d76b9` FOREIGN KEY (`auteur_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `demandes_compensation_conformite` ADD CONSTRAINT `fk_demandes_compensation_conformite_316a237ace` FOREIGN KEY (`dossier_reutilisation_id`, `commande_id`) REFERENCES `dossiers_remboursement` (`id`, `commande_id`);
ALTER TABLE `lignes_compensation_conformite` ADD CONSTRAINT `fk_lignes_compensation_conformite_a07c8a49f3` FOREIGN KEY (`demande_id`) REFERENCES `demandes_compensation_conformite` (`id`);
ALTER TABLE `lignes_compensation_conformite` ADD CONSTRAINT `fk_lignes_compensation_conformite_a90de5d467` FOREIGN KEY (`ligne_commande_id`) REFERENCES `lignes_commande` (`id`);
ALTER TABLE `liaisons_compensation` ADD CONSTRAINT `fk_liaisons_compensation_a07c8a49f3` FOREIGN KEY (`demande_id`) REFERENCES `demandes_compensation_conformite` (`id`);
ALTER TABLE `liaisons_compensation` ADD CONSTRAINT `fk_liaisons_compensation_eca8c049ad` FOREIGN KEY (`dossier_remboursement_id`) REFERENCES `dossiers_remboursement` (`id`);
ALTER TABLE `resultats_compensation` ADD CONSTRAINT `fk_resultats_compensation_eca8c049ad` FOREIGN KEY (`dossier_remboursement_id`) REFERENCES `dossiers_remboursement` (`id`);
ALTER TABLE `politiques_conservation` ADD CONSTRAINT `fk_politiques_conservation_f43d190340` FOREIGN KEY (`valide_par`) REFERENCES `comptes` (`id`);
ALTER TABLE `objets_conserves` ADD CONSTRAINT `fk_objets_conserves_e80837b9f6` FOREIGN KEY (`adaptateur_id`) REFERENCES `adaptateurs_conservation` (`id`);
ALTER TABLE `objets_conserves` ADD CONSTRAINT `fk_objets_conserves_ba2cf1b855` FOREIGN KEY (`adaptateur_id`, `proprietaire`, `type_objet`, `categorie`) REFERENCES `adaptateurs_conservation` (`id`, `proprietaire`, `type_objet`, `categorie`);
ALTER TABLE `objets_dossiers_conformite` ADD CONSTRAINT `fk_objets_dossiers_conformite_57b1ad9851` FOREIGN KEY (`objet_id`) REFERENCES `objets_conserves` (`id`);
ALTER TABLE `objets_dossiers_conformite` ADD CONSTRAINT `fk_objets_dossiers_conformite_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`);
ALTER TABLE `objets_dossiers_conformite` ADD CONSTRAINT `fk_objets_dossiers_conformite_fe1d57a629` FOREIGN KEY (`litige_id`) REFERENCES `contestations_paiement` (`id`);
ALTER TABLE `applications_conservation` ADD CONSTRAINT `fk_applications_conservation_57b1ad9851` FOREIGN KEY (`objet_id`) REFERENCES `objets_conserves` (`id`);
ALTER TABLE `applications_conservation` ADD CONSTRAINT `fk_applications_conservation_1fe59bac09` FOREIGN KEY (`politique_id`) REFERENCES `politiques_conservation` (`id`);
ALTER TABLE `gels_conservation` ADD CONSTRAINT `fk_gels_conservation_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`);
ALTER TABLE `gels_conservation` ADD CONSTRAINT `fk_gels_conservation_fe1d57a629` FOREIGN KEY (`litige_id`) REFERENCES `contestations_paiement` (`id`);
ALTER TABLE `gels_conservation` ADD CONSTRAINT `fk_gels_conservation_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `categories_gel` ADD CONSTRAINT `fk_categories_gel_6cf547d612` FOREIGN KEY (`gel_id`) REFERENCES `gels_conservation` (`id`);
ALTER TABLE `objets_geles` ADD CONSTRAINT `fk_objets_geles_6cf547d612` FOREIGN KEY (`gel_id`) REFERENCES `gels_conservation` (`id`);
ALTER TABLE `objets_geles` ADD CONSTRAINT `fk_objets_geles_57b1ad9851` FOREIGN KEY (`objet_id`) REFERENCES `objets_conserves` (`id`);
ALTER TABLE `levees_gels_conservation` ADD CONSTRAINT `fk_levees_gels_conservation_6cf547d612` FOREIGN KEY (`gel_id`) REFERENCES `gels_conservation` (`id`);
ALTER TABLE `levees_gels_conservation` ADD CONSTRAINT `fk_levees_gels_conservation_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `suivis_instructions_externes` ADD CONSTRAINT `fk_suivis_instructions_externes_933ac7cd20` FOREIGN KEY (`instruction_paiement_id`) REFERENCES `instructions_conformite_paiement` (`id`);
ALTER TABLE `suivis_instructions_externes` ADD CONSTRAINT `fk_suivis_instructions_externes_017479c0f6` FOREIGN KEY (`gel_conservation_id`) REFERENCES `gels_conservation` (`id`);
ALTER TABLE `suivis_instructions_externes` ADD CONSTRAINT `fk_suivis_instructions_externes_660d4aa1d5` FOREIGN KEY (`levee_gel_id`) REFERENCES `levees_gels_conservation` (`id`);
ALTER TABLE `suivis_instructions_externes` ADD CONSTRAINT `fk_suivis_instructions_externes_fd228d52d8` FOREIGN KEY (`cible_instruction_id`) REFERENCES `cibles_instructions_paiement` (`id`);
ALTER TABLE `suivis_instructions_externes` ADD CONSTRAINT `fk_suivis_instructions_externes_57b1ad9851` FOREIGN KEY (`objet_id`) REFERENCES `objets_conserves` (`id`);
ALTER TABLE `actions_conservation` ADD CONSTRAINT `fk_actions_conservation_57b1ad9851` FOREIGN KEY (`objet_id`) REFERENCES `objets_conserves` (`id`);
ALTER TABLE `actions_conservation` ADD CONSTRAINT `fk_actions_conservation_e17ebd9784` FOREIGN KEY (`application_id`) REFERENCES `applications_conservation` (`id`);
ALTER TABLE `actions_conservation` ADD CONSTRAINT `fk_actions_conservation_d74aece195` FOREIGN KEY (`application_id`, `objet_id`) REFERENCES `applications_conservation` (`id`, `objet_id`);
ALTER TABLE `evenements_conformite` ADD CONSTRAINT `fk_evenements_conformite_e8428813a1` FOREIGN KEY (`signalement_id`) REFERENCES `signalements_contenu` (`id`);
ALTER TABLE `evenements_conformite` ADD CONSTRAINT `fk_evenements_conformite_fe1d57a629` FOREIGN KEY (`litige_id`) REFERENCES `contestations_paiement` (`id`);
ALTER TABLE `evenements_conformite` ADD CONSTRAINT `fk_evenements_conformite_1fe59bac09` FOREIGN KEY (`politique_id`) REFERENCES `politiques_conservation` (`id`);
ALTER TABLE `evenements_conformite` ADD CONSTRAINT `fk_evenements_conformite_57b1ad9851` FOREIGN KEY (`objet_id`) REFERENCES `objets_conserves` (`id`);
ALTER TABLE `evenements_conformite` ADD CONSTRAINT `fk_evenements_conformite_00da6ae87e` FOREIGN KEY (`phase_id`) REFERENCES `phases_instruction` (`id`);
ALTER TABLE `evenements_conformite` ADD CONSTRAINT `fk_evenements_conformite_469890ebf2` FOREIGN KEY (`auteur_compte_id`) REFERENCES `comptes` (`id`);
ALTER TABLE `evenements_conformite` ADD CONSTRAINT `fk_evenements_conformite_f6afe4287f` FOREIGN KEY (`phase_id`, `signalement_id`) REFERENCES `phases_instruction` (`id`, `signalement_id`);
ALTER TABLE `levees_restrictions` ADD CONSTRAINT `fk_levees_restrictions_e6055b8e5f` FOREIGN KEY (`restriction_id`) REFERENCES `restrictions_conformite` (`id`);
ALTER TABLE `levees_restrictions` ADD CONSTRAINT `fk_levees_restrictions_026d5ad365` FOREIGN KEY (`ligne_levee_id`) REFERENCES `lignes_ordres_retrait` (`id`);
ALTER TABLE `levees_restrictions` ADD CONSTRAINT `fk_levees_restrictions_0206fe73a6` FOREIGN KEY (`instruction_levee_id`) REFERENCES `instructions_retrait_publication` (`id`);
ALTER TABLE `rattachements_resultats_compensation` ADD CONSTRAINT `fk_rattachements_resultats_compensatio_a4da1aea34` FOREIGN KEY (`resultat_id`) REFERENCES `resultats_compensation` (`id`);
ALTER TABLE `rattachements_resultats_compensation` ADD CONSTRAINT `fk_rattachements_resultats_compensatio_a07c8a49f3` FOREIGN KEY (`demande_id`) REFERENCES `demandes_compensation_conformite` (`id`);

-- Afrique-Educ MPD V1.0 : protections locales UPDATE.
-- DELETE reste reserve au circuit de conservation ; pas de suppression par ces triggers.
SET NAMES utf8mb4 COLLATE utf8mb4_0900_bin;
SET SESSION time_zone = '+00:00';
DELIMITER $$
CREATE TRIGGER `bu_evenements_compte_ff4485d3` BEFORE UPDATE ON `evenements_compte` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`compte_sujet_id` <=> OLD.`compte_sujet_id`) OR NOT (NEW.`adhesion_id` <=> OLD.`adhesion_id`) OR NOT (NEW.`compte_role_id` <=> OLD.`compte_role_id`) OR NOT (NEW.`permission_id` <=> OLD.`permission_id`) OR NOT (NEW.`attribution_interne_id` <=> OLD.`attribution_interne_id`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`ancien_etat` <=> OLD.`ancien_etat`) OR NOT (NEW.`nouvel_etat` <=> OLD.`nouvel_etat`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) OR NOT (NEW.`fin_prevue` <=> OLD.`fin_prevue`) OR NOT (NEW.`correlation` <=> OLD.`correlation`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_evenements_consentement_1f47bd52` BEFORE UPDATE ON `evenements_consentement` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`consentement_id` <=> OLD.`consentement_id`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`source` <=> OLD.`source`) OR NOT (NEW.`canal` <=> OLD.`canal`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_evenements_verification_78d5a6ed` BEFORE UPDATE ON `evenements_verification` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`dossier_id` <=> OLD.`dossier_id`) OR NOT (NEW.`habilitation_id` <=> OLD.`habilitation_id`) OR NOT (NEW.`controle_id` <=> OLD.`controle_id`) OR NOT (NEW.`decision_id` <=> OLD.`decision_id`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`ancien_etat` <=> OLD.`ancien_etat`) OR NOT (NEW.`nouvel_etat` <=> OLD.`nouvel_etat`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) OR NOT (NEW.`correlation` <=> OLD.`correlation`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_decisions_verification_8f457611` BEFORE UPDATE ON `decisions_verification` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`dossier_id` <=> OLD.`dossier_id`) OR NOT (NEW.`decideur_id` <=> OLD.`decideur_id`) OR NOT (NEW.`resultat` <=> OLD.`resultat`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`decide_a` <=> OLD.`decide_a`) OR NOT (NEW.`valide_jusqua` <=> OLD.`valide_jusqua`) OR NOT (NEW.`restrictions` <=> OLD.`restrictions`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_evenements_taches_f32f63ab` BEFORE UPDATE ON `evenements_taches` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`tache_id` <=> OLD.`tache_id`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`auteur_id` <=> OLD.`auteur_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) OR NOT (NEW.`ancien_responsable_id` <=> OLD.`ancien_responsable_id`) OR NOT (NEW.`nouveau_responsable_id` <=> OLD.`nouveau_responsable_id`) OR NOT (NEW.`ancien_etat` <=> OLD.`ancien_etat`) OR NOT (NEW.`nouvel_etat` <=> OLD.`nouvel_etat`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`prochaine_action_a` <=> OLD.`prochaine_action_a`) OR NOT (NEW.`correlation` <=> OLD.`correlation`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_echanges_support_d45bddec` BEFORE UPDATE ON `echanges_support` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`demande_id` <=> OLD.`demande_id`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine` <=> OLD.`origine`) OR NOT (NEW.`visibilite` <=> OLD.`visibilite`) OR NOT (NEW.`contenu_prive` <=> OLD.`contenu_prive`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`canal` <=> OLD.`canal`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_messages_sortants_93ab5ac4` BEFORE UPDATE ON `messages_sortants` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`cle_idempotence` <=> OLD.`cle_idempotence`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`operation` <=> OLD.`operation`) OR NOT (NEW.`correlation` <=> OLD.`correlation`) OR NOT (NEW.`compte_id` <=> OLD.`compte_id`) OR NOT (NEW.`dossier_id` <=> OLD.`dossier_id`) OR NOT (NEW.`demande_id` <=> OLD.`demande_id`) OR NOT (NEW.`defi_id` <=> OLD.`defi_id`) OR NOT (NEW.`evenement_compte_id` <=> OLD.`evenement_compte_id`) OR NOT (NEW.`evenement_kyc_id` <=> OLD.`evenement_kyc_id`) OR NOT (NEW.`version_objet` <=> OLD.`version_objet`) OR NOT (NEW.`producteur` <=> OLD.`producteur`) OR NOT (NEW.`schema_message_version` <=> OLD.`schema_message_version`) OR NOT (NEW.`empreinte_intention` <=> OLD.`empreinte_intention`) OR NOT (NEW.`donnees_minimales` <=> OLD.`donnees_minimales`) OR NOT (NEW.`cree_a` <=> OLD.`cree_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_transactions_paiement_0b7bbb91` BEFORE UPDATE ON `transactions_paiement` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`paiement_id` <=> OLD.`paiement_id`) OR NOT (NEW.`remboursement_id` <=> OLD.`remboursement_id`) OR NOT (NEW.`evenement_psp_id` <=> OLD.`evenement_psp_id`) OR NOT (NEW.`nature` <=> OLD.`nature`) OR NOT (NEW.`reference_mouvement` <=> OLD.`reference_mouvement`) OR NOT (NEW.`cle_mouvement` <=> OLD.`cle_mouvement`) OR NOT (NEW.`montant` <=> OLD.`montant`) OR NOT (NEW.`devise` <=> OLD.`devise`) OR NOT (NEW.`resultat` <=> OLD.`resultat`) OR NOT (NEW.`horodatage` <=> OLD.`horodatage`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_decisions_remboursement_3f4a318d` BEFORE UPDATE ON `decisions_remboursement` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`dossier_id` <=> OLD.`dossier_id`) OR NOT (NEW.`decideur_id` <=> OLD.`decideur_id`) OR NOT (NEW.`decision` <=> OLD.`decision`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`montant_autorise` <=> OLD.`montant_autorise`) OR NOT (NEW.`devise` <=> OLD.`devise`) OR NOT (NEW.`effet_droit` <=> OLD.`effet_droit`) OR NOT (NEW.`decide_a` <=> OLD.`decide_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_decisions_rattachement_compensation_4ee76195` BEFORE UPDATE ON `decisions_rattachement_compensation` FOR EACH ROW
BEGIN
  IF NOT (NEW.`reception_id` <=> OLD.`reception_id`) OR NOT (NEW.`dossier_id` <=> OLD.`dossier_id`) OR NOT (NEW.`decideur_id` <=> OLD.`decideur_id`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`preuve_equivalence` <=> OLD.`preuve_equivalence`) OR NOT (NEW.`decide_a` <=> OLD.`decide_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`reception_id` <=> OLD.`reception_id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_evenements_licence_910e3721` BEFORE UPDATE ON `evenements_licence` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`licence_id` <=> OLD.`licence_id`) OR NOT (NEW.`origine_id` <=> OLD.`origine_id`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) OR NOT (NEW.`correlation` <=> OLD.`correlation`) OR NOT (NEW.`reference_decision` <=> OLD.`reference_decision`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_evenements_blocage_7bd0ae1c` BEFORE UPDATE ON `evenements_blocage` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`blocage_id` <=> OLD.`blocage_id`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`correlation` <=> OLD.`correlation`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_evenements_appareil_7e91f29f` BEFORE UPDATE ON `evenements_appareil` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`appareil_id` <=> OLD.`appareil_id`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`version_preuve` <=> OLD.`version_preuve`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`correlation` <=> OLD.`correlation`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_decisions_alerte_9077fb05` BEFORE UPDATE ON `decisions_alerte` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`alerte_id` <=> OLD.`alerte_id`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`action` <=> OLD.`action`) OR NOT (NEW.`reference_action` <=> OLD.`reference_action`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_controles_publication_a6e24bfb` BEFORE UPDATE ON `controles_publication` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`exigence_id` <=> OLD.`exigence_id`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`numero` <=> OLD.`numero`) OR NOT (NEW.`resultat` <=> OLD.`resultat`) OR NOT (NEW.`observation` <=> OLD.`observation`) OR NOT (NEW.`cree_a` <=> OLD.`cree_a`) OR NOT (NEW.`empreinte_contenu_examine` <=> OLD.`empreinte_contenu_examine`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_decisions_publication_19249f5d` BEFORE UPDATE ON `decisions_publication` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`dossier_id` <=> OLD.`dossier_id`) OR NOT (NEW.`decideur_compte_id` <=> OLD.`decideur_compte_id`) OR NOT (NEW.`resultat` <=> OLD.`resultat`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`decide_a` <=> OLD.`decide_a`) OR NOT (NEW.`preuve_independance` <=> OLD.`preuve_independance`) OR NOT (NEW.`correlation` <=> OLD.`correlation`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_evenements_publication_254bae37` BEFORE UPDATE ON `evenements_publication` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`manuscrit_id` <=> OLD.`manuscrit_id`) OR NOT (NEW.`dossier_id` <=> OLD.`dossier_id`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`ancien_etat` <=> OLD.`ancien_etat`) OR NOT (NEW.`nouvel_etat` <=> OLD.`nouvel_etat`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) OR NOT (NEW.`correlation` <=> OLD.`correlation`) OR NOT (NEW.`reference_action` <=> OLD.`reference_action`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_resolutions_cibles_a9c9d8d3` BEFORE UPDATE ON `resolutions_cibles` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`contenu_vise_id` <=> OLD.`contenu_vise_id`) OR NOT (NEW.`numero` <=> OLD.`numero`) OR NOT (NEW.`resultat` <=> OLD.`resultat`) OR NOT (NEW.`oeuvre_id` <=> OLD.`oeuvre_id`) OR NOT (NEW.`edition_id` <=> OLD.`edition_id`) OR NOT (NEW.`offre_id` <=> OLD.`offre_id`) OR NOT (NEW.`media_catalogue_id` <=> OLD.`media_catalogue_id`) OR NOT (NEW.`avis_id` <=> OLD.`avis_id`) OR NOT (NEW.`version_contenu_id` <=> OLD.`version_contenu_id`) OR NOT (NEW.`preuve_resolution` <=> OLD.`preuve_resolution`) OR NOT (NEW.`resolu_a` <=> OLD.`resolu_a`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_controles_completude_32795b7e` BEFORE UPDATE ON `controles_completude` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`signalement_id` <=> OLD.`signalement_id`) OR NOT (NEW.`numero` <=> OLD.`numero`) OR NOT (NEW.`resultat` <=> OLD.`resultat`) OR NOT (NEW.`manques` <=> OLD.`manques`) OR NOT (NEW.`reference_regle` <=> OLD.`reference_regle`) OR NOT (NEW.`empreinte_elements` <=> OLD.`empreinte_elements`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_ajustements_echeance_d43b6b26` BEFORE UPDATE ON `ajustements_echeance` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`echeance_id` <=> OLD.`echeance_id`) OR NOT (NEW.`ancien_etat` <=> OLD.`ancien_etat`) OR NOT (NEW.`nouvel_etat` <=> OLD.`nouvel_etat`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_decisions_moderation_10248b4d` BEFORE UPDATE ON `decisions_moderation` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`phase_id` <=> OLD.`phase_id`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`decide_a` <=> OLD.`decide_a`) OR NOT (NEW.`decideur_id` <=> OLD.`decideur_id`) OR NOT (NEW.`preuve_independance` <=> OLD.`preuve_independance`) OR NOT (NEW.`correlation` <=> OLD.`correlation`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_faits_notification_252622af` BEFORE UPDATE ON `faits_notification` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`notification_id` <=> OLD.`notification_id`) OR NOT (NEW.`producteur` <=> OLD.`producteur`) OR NOT (NEW.`reference_source` <=> OLD.`reference_source`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`survenu_a` <=> OLD.`survenu_a`) OR NOT (NEW.`recu_a` <=> OLD.`recu_a`) OR NOT (NEW.`preuve` <=> OLD.`preuve`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_faits_litige_4af2d7d1` BEFORE UPDATE ON `faits_litige` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`litige_id` <=> OLD.`litige_id`) OR NOT (NEW.`producteur` <=> OLD.`producteur`) OR NOT (NEW.`reference_fait` <=> OLD.`reference_fait`) OR NOT (NEW.`survenu_a` <=> OLD.`survenu_a`) OR NOT (NEW.`recu_a` <=> OLD.`recu_a`) OR NOT (NEW.`preuve` <=> OLD.`preuve`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_resultats_compensation_5e04a3fc` BEFORE UPDATE ON `resultats_compensation` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`dossier_remboursement_id` <=> OLD.`dossier_remboursement_id`) OR NOT (NEW.`producteur` <=> OLD.`producteur`) OR NOT (NEW.`reference_fait` <=> OLD.`reference_fait`) OR NOT (NEW.`nature` <=> OLD.`nature`) OR NOT (NEW.`resultat` <=> OLD.`resultat`) OR NOT (NEW.`recu_a` <=> OLD.`recu_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_levees_restrictions_d0b6f049` BEFORE UPDATE ON `levees_restrictions` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`restriction_id` <=> OLD.`restriction_id`) OR NOT (NEW.`ligne_levee_id` <=> OLD.`ligne_levee_id`) OR NOT (NEW.`instruction_levee_id` <=> OLD.`instruction_levee_id`) OR NOT (NEW.`reference` <=> OLD.`reference`) OR NOT (NEW.`decide_a` <=> OLD.`decide_a`) OR NOT (NEW.`effet_a` <=> OLD.`effet_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_levees_gels_conservation_8101b786` BEFORE UPDATE ON `levees_gels_conservation` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`gel_id` <=> OLD.`gel_id`) OR NOT (NEW.`date` <=> OLD.`date`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`preuve_fin_cause` <=> OLD.`preuve_fin_cause`) OR NOT (NEW.`reference_regle` <=> OLD.`reference_regle`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_instructions_conformite_paiement_46820f3e` BEFORE UPDATE ON `instructions_conformite_paiement` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`litige_id` <=> OLD.`litige_id`) OR NOT (NEW.`reference` <=> OLD.`reference`) OR NOT (NEW.`type` <=> OLD.`type`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`decide_a` <=> OLD.`decide_a`) OR NOT (NEW.`effet_a` <=> OLD.`effet_a`) OR NOT (NEW.`reference_regle` <=> OLD.`reference_regle`) OR NOT (NEW.`gel_leve_id` <=> OLD.`gel_leve_id`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_decisions_controles_3687f316` BEFORE UPDATE ON `decisions_controles` FOR EACH ROW
BEGIN
  IF NOT (NEW.`decision_id` <=> OLD.`decision_id`) OR NOT (NEW.`controle_id` <=> OLD.`controle_id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`decision_id` <=> OLD.`decision_id`) OR NOT (NEW.`controle_id` <=> OLD.`controle_id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_preuves_decisions_mesures_441e8ab0` BEFORE UPDATE ON `preuves_decisions_mesures` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`decision_id` <=> OLD.`decision_id`) OR NOT (NEW.`mesure_id` <=> OLD.`mesure_id`) OR NOT (NEW.`piece_id` <=> OLD.`piece_id`) OR NOT (NEW.`controle_id` <=> OLD.`controle_id`) OR NOT (NEW.`reponse_id` <=> OLD.`reponse_id`) OR NOT (NEW.`empreinte` <=> OLD.`empreinte`) OR NOT (NEW.`role_preuve` <=> OLD.`role_preuve`) OR NOT (NEW.`motif` <=> OLD.`motif`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_portees_decisions_mesures_c9bc9d3b` BEFORE UPDATE ON `portees_decisions_mesures` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`decision_id` <=> OLD.`decision_id`) OR NOT (NEW.`mesure_id` <=> OLD.`mesure_id`) OR NOT (NEW.`resolution_id` <=> OLD.`resolution_id`) OR NOT (NEW.`effet` <=> OLD.`effet`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fait immuable : ajouter une nouvelle preuve ou decision';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_commandes_9d0ff7c1` BEFORE UPDATE ON `commandes` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`compte_id` <=> OLD.`compte_id`) OR NOT (NEW.`reference` <=> OLD.`reference`) OR NOT (NEW.`cle_checkout` <=> OLD.`cle_checkout`) OR NOT (NEW.`empreinte_requete` <=> OLD.`empreinte_requete`) OR NOT (NEW.`devise` <=> OLD.`devise`) OR NOT (NEW.`sous_total` <=> OLD.`sous_total`) OR NOT (NEW.`remise_total` <=> OLD.`remise_total`) OR NOT (NEW.`livraison_total` <=> OLD.`livraison_total`) OR NOT (NEW.`taxe_total` <=> OLD.`taxe_total`) OR NOT (NEW.`grand_total` <=> OLD.`grand_total`) OR NOT (NEW.`cree_a` <=> OLD.`cree_a`) OR NOT (NEW.`expire_a` <=> OLD.`expire_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_lignes_commande_c59d0d6c` BEFORE UPDATE ON `lignes_commande` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`commande_id` <=> OLD.`commande_id`) OR NOT (NEW.`offre_id` <=> OLD.`offre_id`) OR NOT (NEW.`vendeur_id` <=> OLD.`vendeur_id`) OR NOT (NEW.`quantite` <=> OLD.`quantite`) OR NOT (NEW.`titre_snap` <=> OLD.`titre_snap`) OR NOT (NEW.`format_snap` <=> OLD.`format_snap`) OR NOT (NEW.`condition_snap` <=> OLD.`condition_snap`) OR NOT (NEW.`modalite_snap` <=> OLD.`modalite_snap`) OR NOT (NEW.`isbn_snap` <=> OLD.`isbn_snap`) OR NOT (NEW.`vendeur_identite_snap` <=> OLD.`vendeur_identite_snap`) OR NOT (NEW.`prix_unitaire` <=> OLD.`prix_unitaire`) OR NOT (NEW.`brut_ligne` <=> OLD.`brut_ligne`) OR NOT (NEW.`remise_ligne` <=> OLD.`remise_ligne`) OR NOT (NEW.`livraison_ligne` <=> OLD.`livraison_ligne`) OR NOT (NEW.`taxe_ligne` <=> OLD.`taxe_ligne`) OR NOT (NEW.`total_ligne` <=> OLD.`total_ligne`) OR NOT (NEW.`devise` <=> OLD.`devise`) OR NOT (NEW.`type_ligne` <=> OLD.`type_ligne`) OR NOT (NEW.`regles_calcul_snap` <=> OLD.`regles_calcul_snap`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_adresses_livraison_commande_e818f024` BEFORE UPDATE ON `adresses_livraison_commande` FOR EACH ROW
BEGIN
  IF NOT (NEW.`commande_id` <=> OLD.`commande_id`) OR NOT (NEW.`nom_destinataire` <=> OLD.`nom_destinataire`) OR NOT (NEW.`adresse` <=> OLD.`adresse`) OR NOT (NEW.`ville` <=> OLD.`ville`) OR NOT (NEW.`region` <=> OLD.`region`) OR NOT (NEW.`code_postal` <=> OLD.`code_postal`) OR NOT (NEW.`pays` <=> OLD.`pays`) OR NOT (NEW.`telephone` <=> OLD.`telephone`) OR NOT (NEW.`instructions` <=> OLD.`instructions`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NEW.`adresse_origine_id` IS NOT NULL AND NOT (NEW.`adresse_origine_id` <=> OLD.`adresse_origine_id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Provenance adresse : seul retrait du lien autorise';
  END IF;
  IF NOT (NEW.`commande_id` <=> OLD.`commande_id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_identites_facturation_commande_ec524716` BEFORE UPDATE ON `identites_facturation_commande` FOR EACH ROW
BEGIN
  IF NOT (NEW.`commande_id` <=> OLD.`commande_id`) OR NOT (NEW.`nom_identite` <=> OLD.`nom_identite`) OR NOT (NEW.`adresse` <=> OLD.`adresse`) OR NOT (NEW.`ville` <=> OLD.`ville`) OR NOT (NEW.`pays` <=> OLD.`pays`) OR NOT (NEW.`autres_mentions` <=> OLD.`autres_mentions`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`commande_id` <=> OLD.`commande_id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_factures_vente_4d1a8e4d` BEFORE UPDATE ON `factures_vente` FOR EACH ROW
BEGIN
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
END$$

CREATE TRIGGER `bu_lignes_facture_vente_fb570247` BEFORE UPDATE ON `lignes_facture_vente` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`facture_id` <=> OLD.`facture_id`) OR NOT (NEW.`ligne_commande_id` <=> OLD.`ligne_commande_id`) OR NOT (NEW.`libelle_snap` <=> OLD.`libelle_snap`) OR NOT (NEW.`quantite` <=> OLD.`quantite`) OR NOT (NEW.`pu` <=> OLD.`pu`) OR NOT (NEW.`remise` <=> OLD.`remise`) OR NOT (NEW.`livraison` <=> OLD.`livraison`) OR NOT (NEW.`taxes` <=> OLD.`taxes`) OR NOT (NEW.`total` <=> OLD.`total`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_factures_commission_1092233b` BEFORE UPDATE ON `factures_commission` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`dossier_facturation_id` <=> OLD.`dossier_facturation_id`) OR NOT (NEW.`regle_remuneration_id` <=> OLD.`regle_remuneration_id`) OR NOT (NEW.`numero` <=> OLD.`numero`) OR NOT (NEW.`serie` <=> OLD.`serie`) OR NOT (NEW.`emetteur_cle_snap` <=> OLD.`emetteur_cle_snap`) OR NOT (NEW.`identite_ae_snap` <=> OLD.`identite_ae_snap`) OR NOT (NEW.`identite_vendeur_snap` <=> OLD.`identite_vendeur_snap`) OR NOT (NEW.`assiette_snap` <=> OLD.`assiette_snap`) OR NOT (NEW.`regle_snap` <=> OLD.`regle_snap`) OR NOT (NEW.`commission` <=> OLD.`commission`) OR NOT (NEW.`frais` <=> OLD.`frais`) OR NOT (NEW.`taxes` <=> OLD.`taxes`) OR NOT (NEW.`total` <=> OLD.`total`) OR NOT (NEW.`devise` <=> OLD.`devise`) OR NOT (NEW.`emise_a` <=> OLD.`emise_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_avoirs_a2d8a392` BEFORE UPDATE ON `avoirs` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`facture_vente_id` <=> OLD.`facture_vente_id`) OR NOT (NEW.`facture_commission_id` <=> OLD.`facture_commission_id`) OR NOT (NEW.`decision_remboursement_id` <=> OLD.`decision_remboursement_id`) OR NOT (NEW.`cle_emission` <=> OLD.`cle_emission`) OR NOT (NEW.`numero` <=> OLD.`numero`) OR NOT (NEW.`serie` <=> OLD.`serie`) OR NOT (NEW.`emetteur_cle_snap` <=> OLD.`emetteur_cle_snap`) OR NOT (NEW.`montant` <=> OLD.`montant`) OR NOT (NEW.`devise` <=> OLD.`devise`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`emis_a` <=> OLD.`emis_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_lignes_avoir_73c126a2` BEFORE UPDATE ON `lignes_avoir` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`avoir_id` <=> OLD.`avoir_id`) OR NOT (NEW.`ligne_facture_vente_id` <=> OLD.`ligne_facture_vente_id`) OR NOT (NEW.`quantite` <=> OLD.`quantite`) OR NOT (NEW.`montant` <=> OLD.`montant`) OR NOT (NEW.`motif` <=> OLD.`motif`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_recus_paiement_a11186b9` BEFORE UPDATE ON `recus_paiement` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`paiement_id` <=> OLD.`paiement_id`) OR NOT (NEW.`reference` <=> OLD.`reference`) OR NOT (NEW.`montant` <=> OLD.`montant`) OR NOT (NEW.`devise` <=> OLD.`devise`) OR NOT (NEW.`emis_a` <=> OLD.`emis_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_paiements_ac616d99` BEFORE UPDATE ON `paiements` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`commande_id` <=> OLD.`commande_id`) OR NOT (NEW.`configuration_id` <=> OLD.`configuration_id`) OR NOT (NEW.`ref_interne` <=> OLD.`ref_interne`) OR NOT (NEW.`cle_initiation` <=> OLD.`cle_initiation`) OR NOT (NEW.`empreinte_intention` <=> OLD.`empreinte_intention`) OR NOT (NEW.`montant` <=> OLD.`montant`) OR NOT (NEW.`devise` <=> OLD.`devise`) OR NOT (NEW.`moyen` <=> OLD.`moyen`) OR NOT (NEW.`initie_a` <=> OLD.`initie_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_remboursements_7f44c5be` BEFORE UPDATE ON `remboursements` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`paiement_id` <=> OLD.`paiement_id`) OR NOT (NEW.`decision_id` <=> OLD.`decision_id`) OR NOT (NEW.`anomalie_id` <=> OLD.`anomalie_id`) OR NOT (NEW.`cle_idempotence` <=> OLD.`cle_idempotence`) OR NOT (NEW.`montant` <=> OLD.`montant`) OR NOT (NEW.`devise` <=> OLD.`devise`) OR NOT (NEW.`demande_a` <=> OLD.`demande_a`) OR NOT (NEW.`configuration_id` <=> OLD.`configuration_id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_references_acquisition_a6ec3da6` BEFORE UPDATE ON `references_acquisition` FOR EACH ROW
BEGIN
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
END$$

CREATE TRIGGER `bu_instructions_revocation_44a77531` BEFORE UPDATE ON `instructions_revocation` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`producteur` <=> OLD.`producteur`) OR NOT (NEW.`reference_instruction` <=> OLD.`reference_instruction`) OR NOT (NEW.`cle_idempotence` <=> OLD.`cle_idempotence`) OR NOT (NEW.`reference_acquisition_id` <=> OLD.`reference_acquisition_id`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`reference_decision` <=> OLD.`reference_decision`) OR NOT (NEW.`effet_a` <=> OLD.`effet_a`) OR NOT (NEW.`recu_a` <=> OLD.`recu_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_versions_contenu_1c0e79f2` BEFORE UPDATE ON `versions_contenu` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`media_prive_id` <=> OLD.`media_prive_id`) OR NOT (NEW.`numero_version` <=> OLD.`numero_version`) OR NOT (NEW.`reference_objet_version` <=> OLD.`reference_objet_version`) OR NOT (NEW.`empreinte_fichier` <=> OLD.`empreinte_fichier`) OR NOT (NEW.`cree_a` <=> OLD.`cree_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_restrictions_conformite_e0b95779` BEFORE UPDATE ON `restrictions_conformite` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`ligne_creatrice_id` <=> OLD.`ligne_creatrice_id`) OR NOT (NEW.`instruction_publication_id` <=> OLD.`instruction_publication_id`) OR NOT (NEW.`cause` <=> OLD.`cause`) OR NOT (NEW.`debut` <=> OLD.`debut`) OR NOT (NEW.`fin` <=> OLD.`fin`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_gels_operations_financieres_7590c3b3` BEFORE UPDATE ON `gels_operations_financieres` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`instruction_creatrice_id` <=> OLD.`instruction_creatrice_id`) OR NOT (NEW.`cause` <=> OLD.`cause`) OR NOT (NEW.`debut` <=> OLD.`debut`) OR NOT (NEW.`fin` <=> OLD.`fin`) OR NOT (NEW.`operations_bloquees` <=> OLD.`operations_bloquees`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_instructions_retrait_publication_51d41c9a` BEFORE UPDATE ON `instructions_retrait_publication` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`manuscrit_id` <=> OLD.`manuscrit_id`) OR NOT (NEW.`producteur` <=> OLD.`producteur`) OR NOT (NEW.`reference_instruction` <=> OLD.`reference_instruction`) OR NOT (NEW.`empreinte_intention` <=> OLD.`empreinte_intention`) OR NOT (NEW.`effet` <=> OLD.`effet`) OR NOT (NEW.`restriction_source_id` <=> OLD.`restriction_source_id`) OR NOT (NEW.`restriction_levee_id` <=> OLD.`restriction_levee_id`) OR NOT (NEW.`fin_effet_a` <=> OLD.`fin_effet_a`) OR NOT (NEW.`cible_manuscrit_id` <=> OLD.`cible_manuscrit_id`) OR NOT (NEW.`oeuvre_id` <=> OLD.`oeuvre_id`) OR NOT (NEW.`edition_id` <=> OLD.`edition_id`) OR NOT (NEW.`offre_id` <=> OLD.`offre_id`) OR NOT (NEW.`version_id` <=> OLD.`version_id`) OR NOT (NEW.`motif` <=> OLD.`motif`) OR NOT (NEW.`reference_decision` <=> OLD.`reference_decision`) OR NOT (NEW.`effet_a` <=> OLD.`effet_a`) OR NOT (NEW.`recu_a` <=> OLD.`recu_a`) OR NOT (NEW.`auteur_compte_id` <=> OLD.`auteur_compte_id`) OR NOT (NEW.`origine_systeme` <=> OLD.`origine_systeme`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_lignes_ordres_retrait_d70a5eb3` BEFORE UPDATE ON `lignes_ordres_retrait` FOR EACH ROW
BEGIN
  IF NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`ordre_id` <=> OLD.`ordre_id`) OR NOT (NEW.`resolution_id` <=> OLD.`resolution_id`) OR NOT (NEW.`portee_id` <=> OLD.`portee_id`) OR NOT (NEW.`action` <=> OLD.`action`) OR NOT (NEW.`reference_effet` <=> OLD.`reference_effet`) OR NOT (NEW.`restriction_levee_id` <=> OLD.`restriction_levee_id`) OR NOT (NEW.`effet_a` <=> OLD.`effet_a`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Identite ou intention figee : modification interdite';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_comptes_7853db33` BEFORE UPDATE ON `comptes` FOR EACH ROW
BEGIN
  IF OLD.`statut` IN ('closed') AND NOT (NEW.`statut` <=> OLD.`statut`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Etat terminal : nouvelle demande requise';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_avis_010a4e39` BEFORE UPDATE ON `avis` FOR EACH ROW
BEGIN
  IF OLD.`statut` IN ('rejected') AND NOT (NEW.`statut` <=> OLD.`statut`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Etat terminal : nouvelle demande requise';
  END IF;
  IF NOT (NEW.`avis_id` <=> OLD.`avis_id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_origines_acquisition_daf1156e` BEFORE UPDATE ON `origines_acquisition` FOR EACH ROW
BEGIN
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
END$$

CREATE TRIGGER `bu_dossiers_verification_155f57c3` BEFORE UPDATE ON `dossiers_verification` FOR EACH ROW
BEGIN
  IF OLD.`statut` <> 'draft' AND (NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`sujet_id` <=> OLD.`sujet_id`) OR NOT (NEW.`numero` <=> OLD.`numero`) OR NOT (NEW.`version` <=> OLD.`version`) OR NOT (NEW.`precedent_id` <=> OLD.`precedent_id`) OR NOT (NEW.`niveau` <=> OLD.`niveau`) OR NOT (NEW.`deposant_id` <=> OLD.`deposant_id`) OR NOT (NEW.`profil_fiscal_id` <=> OLD.`profil_fiscal_id`) OR NOT (NEW.`compte_reversement_id` <=> OLD.`compte_reversement_id`) OR NOT (NEW.`cree_a` <=> OLD.`cree_a`) OR NOT (NEW.`soumis_a` <=> OLD.`soumis_a`)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Version approuvee ou soumise immuable';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_dossiers_publication_f3a2add8` BEFORE UPDATE ON `dossiers_publication` FOR EACH ROW
BEGIN
  IF OLD.`statut` <> 'draft' AND (NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`manuscrit_id` <=> OLD.`manuscrit_id`) OR NOT (NEW.`precedent_id` <=> OLD.`precedent_id`) OR NOT (NEW.`numero` <=> OLD.`numero`) OR NOT (NEW.`nature` <=> OLD.`nature`) OR NOT (NEW.`titre` <=> OLD.`titre`) OR NOT (NEW.`sous_titre` <=> OLD.`sous_titre`) OR NOT (NEW.`resume` <=> OLD.`resume`) OR NOT (NEW.`mots_cles` <=> OLD.`mots_cles`) OR NOT (NEW.`declarations_droits` <=> OLD.`declarations_droits`) OR NOT (NEW.`niveau_id` <=> OLD.`niveau_id`) OR NOT (NEW.`cree_a` <=> OLD.`cree_a`) OR NOT (NEW.`soumis_a` <=> OLD.`soumis_a`) OR NOT (NEW.`reference_regles_completude` <=> OLD.`reference_regles_completude`) OR NOT (NEW.`empreinte_soumission` <=> OLD.`empreinte_soumission`) OR NOT (NEW.`oeuvre_cible_id` <=> OLD.`oeuvre_cible_id`) OR NOT (NEW.`empreinte_base_oeuvre` <=> OLD.`empreinte_base_oeuvre`) OR NOT (NEW.`snapshot_base_oeuvre` <=> OLD.`snapshot_base_oeuvre`)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Version approuvee ou soumise immuable';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_configurations_service_5e0540c9` BEFORE UPDATE ON `configurations_service` FOR EACH ROW
BEGIN
  IF OLD.`approuve_a` IS NOT NULL AND (NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`code` <=> OLD.`code`) OR NOT (NEW.`version` <=> OLD.`version`) OR NOT (NEW.`schema_version` <=> OLD.`schema_version`) OR NOT (NEW.`valeurs` <=> OLD.`valeurs`) OR NOT (NEW.`empreinte` <=> OLD.`empreinte`) OR NOT (NEW.`cree_par` <=> OLD.`cree_par`) OR NOT (NEW.`cree_a` <=> OLD.`cree_a`) OR NOT (NEW.`approuve_par` <=> OLD.`approuve_par`) OR NOT (NEW.`approuve_a` <=> OLD.`approuve_a`) OR NOT (NEW.`effet_a` <=> OLD.`effet_a`)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Version approuvee ou soumise immuable';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

CREATE TRIGGER `bu_politiques_conservation_7a2e8d35` BEFORE UPDATE ON `politiques_conservation` FOR EACH ROW
BEGIN
  IF OLD.`valide_a` IS NOT NULL AND (NOT (NEW.`id` <=> OLD.`id`) OR NOT (NEW.`categorie` <=> OLD.`categorie`) OR NOT (NEW.`version` <=> OLD.`version`) OR NOT (NEW.`finalite` <=> OLD.`finalite`) OR NOT (NEW.`base_reference` <=> OLD.`base_reference`) OR NOT (NEW.`declencheur` <=> OLD.`declencheur`) OR NOT (NEW.`duree_active` <=> OLD.`duree_active`) OR NOT (NEW.`duree_archive` <=> OLD.`duree_archive`) OR NOT (NEW.`unite` <=> OLD.`unite`) OR NOT (NEW.`regle_action` <=> OLD.`regle_action`) OR NOT (NEW.`date_effet` <=> OLD.`date_effet`) OR NOT (NEW.`valide_a` <=> OLD.`valide_a`) OR NOT (NEW.`valide_par` <=> OLD.`valide_par`)) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Version approuvee ou soumise immuable';
  END IF;
  IF NOT (NEW.`id` <=> OLD.`id`) THEN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cle primaire immuable';
  END IF;
END$$

DELIMITER ;

CREATE SQL SECURITY INVOKER VIEW `envois_defis` AS
SELECT m.defi_id, t.id AS transmission_id, e.numero AS numero_essai,
       e.resultat, e.commence_a AS demande_a, e.termine_a,
       e.reference_prestataire AS reference_envoi, e.code_erreur_masque AS code_erreur
FROM messages_sortants m
JOIN transmissions_messages t ON t.message_id = m.id
JOIN tentatives_transmission e ON e.transmission_id = t.id
WHERE m.defi_id IS NOT NULL;

-- Referentiels techniques et vocabulaires uniquement ; aucun compte ni attribution.
SET NAMES utf8mb4 COLLATE utf8mb4_0900_bin;
START TRANSACTION;
INSERT INTO `verrous_coordination` (`code`) VALUES ('identite'),('catalogue'),('finance'),('conservation');
INSERT INTO `roles` (`id`,`code`,`libelle`) VALUES ('4C9A4QP7VJBPMFAV5SJXQF0JEH','reader','reader');
INSERT INTO `roles` (`id`,`code`,`libelle`) VALUES ('40VAW59R8WFEH1JNGKT3CYSJTT','author','author');
INSERT INTO `roles` (`id`,`code`,`libelle`) VALUES ('047M6WJZZPTY2A7SRMK7MN3Y6T','publisher','publisher');
INSERT INTO `roles` (`id`,`code`,`libelle`) VALUES ('5EF03F8P4SA9HC2B827D8MCGF1','moderator','moderator');
INSERT INTO `roles` (`id`,`code`,`libelle`) VALUES ('48PYG2FM5N5BWH0ZSVQ1F1J9Y9','support','support');
INSERT INTO `roles` (`id`,`code`,`libelle`) VALUES ('01BB34Y6675D00ZA24296R8GX6','admin','admin');
INSERT INTO `format_technique` (`format_id`,`code`,`libelle`,`sort_order`,`actif`) VALUES ('6TPKHAHP9VK2DKFQ4Z0TTRYW6J','epub','EPUB',0,1);
INSERT INTO `format_technique` (`format_id`,`code`,`libelle`,`sort_order`,`actif`) VALUES ('61W6KDFQA6550MPDCTHEAXMW8V','pdf','PDF',1,1);
INSERT INTO `format_technique` (`format_id`,`code`,`libelle`,`sort_order`,`actif`) VALUES ('48K5P57SJ4SWH8VKZ8YFARG6Z4','audio','AUDIO',2,1);
INSERT INTO `format_technique` (`format_id`,`code`,`libelle`,`sort_order`,`actif`) VALUES ('7HG5ETDS8FW4X9HA1D23CRNGQH','paper','PAPER',3,1);
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('49K07NY7SACJGBEF48CC049D7F','affecter_taches','affecter_taches');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('6EQ3BPACWT8R4V3NE4VRTETS43','approuver_extension_contenu','approuver_extension_contenu');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('2E43MGTXT6JZ79AH99K023EW0G','attribuer_droit','attribuer_droit');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('4KWGC8X7AA19MZAS2THENEHG8Q','autoriser_recours','autoriser_recours');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('28SWRQMBWDEF3JMN20NWNKVPHN','cloturer_compte','cloturer_compte');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('05SVBJSZC1PMMDQSZJH6E1PX5A','configurer_service','configurer_service');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('169JE2F3J7GPX0H4YHERXZEFSP','consulter_conformite','consulter_conformite');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('78TJ7BZDE9K5ZMHDXYHC5HRVTF','consulter_publications','consulter_publications');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('3AVP09MDJ1C31VF9ADR7TJN19K','controler_kyc','controler_kyc');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('7WX4C5B28ZQKKW499DXS03QKHX','controler_publication','controler_publication');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('4TCD88A2Z88D3X1TMH7NYER5Q9','coordonner_compensation','coordonner_compensation');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('3NWZBN49X8JTZKN38XW7TB7FTM','decider_kyc','decider_kyc');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('1T37WGA9M28NR7BE3A99XJ74GD','decider_kyc_commercial','decider_kyc_commercial');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('5YP0ABFXZK2CHW86Z659WE1G6A','decider_mesure_conservatoire','decider_mesure_conservatoire');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('1N2MA2F1DAVJ4XZ79ZFM7MYDWB','decider_publication','decider_publication');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('56JJDCB9Z4WQVADKFQE3YMDTMG','decider_remboursement','decider_remboursement');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('3GGNY7GK5HSKE1JEC2E78GK110','decider_retrait','decider_retrait');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('3Y8RM9S956DX4EXWK16PRS22V7','demander_examen_compte','demander_examen_compte');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('1ABPN3VPVVT02JYCNS43JAYNWF','executer_remboursement','executer_remboursement');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('7BV3S92NKBRNNA3C4DGPS3PNCT','gerer_blocages_lecture','gerer_blocages_lecture');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('0V1B99WWM88PT2E2CCCKXC04F6','gerer_facturation','gerer_facturation');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('45GH1K0X3HDWSF5WTM1KSKKJ3B','gerer_gels_conservation','gerer_gels_conservation');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('699JP2DTWVGZ4QJJDRC744M4EQ','gerer_habilitations_internes','gerer_habilitations_internes');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('6S9B6032B61YS3KD03PQKNFQAV','gerer_politiques_conservation','gerer_politiques_conservation');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('2A1JJE70KWNH1BJNK42BD5JZDJ','gerer_retraits_publication','gerer_retraits_publication');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('3NAMPV6W1JP5PWFK8MKYZ3R3EV','gerer_vitrine','gerer_vitrine');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('2MX8YEX51JCCEEYS8VNJ9VB9BP','instruire_contestation_paiement','instruire_contestation_paiement');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('6T93H7RK98C9X0BM2M9N8W5P8M','instruire_signalement','instruire_signalement');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('71NRTY8FY5VC3HWGSKVD6BE0J8','moderer_avis','moderer_avis');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('7PRT4WCTSPVZ89918ME53WKHHZ','reactiver_compte','reactiver_compte');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('29VPG1G35BD1QP2T2AQTZW6CT6','reprendre_traitements_catalogue','reprendre_traitements_catalogue');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('2KPE8849QHNGT262B7H31FD2CQ','reprendre_transmissions','reprendre_transmissions');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('5FJERK0TGDZAHM56SKN2MK5WK9','restreindre_habilitation','restreindre_habilitation');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('4ED674X4K6XCNY12MQSPCVMRSE','revoquer_appareil_tiers','revoquer_appareil_tiers');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('2X40NMX5ZBM55RBEVMNWQB7MGX','revoquer_origine_droit','revoquer_origine_droit');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('5BCR2TV8AZT95ETS7JFRED2FJF','suspendre_compte','suspendre_compte');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('75GTQTQY7S638JK0VWCNH2XE1D','traiter_alertes_lecture','traiter_alertes_lecture');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('48FDET8XTEQT470CB9YNTRMXYP','traiter_support','traiter_support');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('2JF6HG292DXE2SF46HTETEH3DS','transferer_principal','transferer_principal');
INSERT INTO `permissions_internes` (`id`,`code`,`libelle`) VALUES ('1YVPN5QV4N50Q5S1740JQV5299','verifier_representation','verifier_representation');
INSERT INTO `permissions_organisation` (`id`,`code`,`libelle`) VALUES ('6DMJ15N11XMFCQ1CN94Z983Y63','consulter_publication','consulter_publication');
INSERT INTO `permissions_organisation` (`id`,`code`,`libelle`) VALUES ('12RWZ2QZ60VY07YDGZ9BY0VCR3','deposer_kyc','deposer_kyc');
INSERT INTO `permissions_organisation` (`id`,`code`,`libelle`) VALUES ('18XNF9W85KERKHYHVT3PJR56B0','deposer_publication','deposer_publication');
INSERT INTO `permissions_organisation` (`id`,`code`,`libelle`) VALUES ('6BYXR1VBYPNJGD9GJ0WBZVPMK5','repondre_conformite','repondre_conformite');
INSERT INTO `permissions_organisation` (`id`,`code`,`libelle`) VALUES ('655MC10P356HXGTJGDWMN7A8EY','retirer_publication','retirer_publication');
INSERT INTO `permissions_organisation` (`id`,`code`,`libelle`) VALUES ('41YWF32E8G19A28XHH1EHQK739','soumettre_publication','soumettre_publication');
COMMIT;
