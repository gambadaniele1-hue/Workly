-- ============================================================
-- phpMyAdmin SQL Dump
-- version 5.2.3 | https://www.phpmyadmin.net/
-- Host: localhost
-- Versione del server: 10.11.13-MariaDB-0ubuntu0.24.04.1
-- Versione PHP: 8.3.6
-- ============================================================
 
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
 
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
 
-- ============================================================
-- CREAZIONE E SELEZIONE DEL DATABASE
-- ============================================================
 
CREATE DATABASE IF NOT EXISTS `gestione_utenti_bp`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
 
USE `gestione_utenti_bp`;
 
-- Disabilita i controlli FK durante la creazione
SET FOREIGN_KEY_CHECKS = 0;
 
-- ============================================================
-- STRUTTURA DELLE TABELLE
-- ============================================================
 
CREATE TABLE IF NOT EXISTS `Busta_paga` (
  `ID_busta`         int(11)       NOT NULL AUTO_INCREMENT,
  `ID_utente`        int(11)       NOT NULL,
  `Mese_riferimento` varchar(7)    NOT NULL,
  `Stipendio_lordo`  decimal(10,2) NOT NULL,
  `Stipendio_netto`  decimal(10,2) NOT NULL,
  `Ore_lavorate`     decimal(10,2) NOT NULL DEFAULT 0.00,
  `Paga_oraria`      decimal(10,2) NOT NULL DEFAULT 0.00,
  `Ore_ferie`        decimal(10,2) NOT NULL DEFAULT 0.00,
  `Ore_malattia`     decimal(10,2) NOT NULL DEFAULT 0.00,
  `Ore_straordinari` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Ore_festivi`      decimal(10,2) NOT NULL DEFAULT 0.00,
  `Ore_prefestivi`   decimal(10,2) NOT NULL DEFAULT 0.00,
  `Ore_notturne`     decimal(10,2) NOT NULL DEFAULT 0.00,
  `Ore_reperibilita` decimal(10,2) NOT NULL DEFAULT 0.00,
  `Ore_trasferta`    decimal(10,2) NOT NULL DEFAULT 0.00,
  `Data_creazione`   timestamp      NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID_busta`),
  KEY `idx_utente` (`ID_utente`),
  KEY `idx_mese` (`Mese_riferimento`),
  CONSTRAINT `fk_busta_utente` FOREIGN KEY (`ID_utente`) REFERENCES `Utenti` (`ID_utente`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- --------------------------------------------------------
 
CREATE TABLE IF NOT EXISTS `Utenti` (
  `ID_utente`     int(11)      NOT NULL AUTO_INCREMENT,
  `N_Telefono`    varchar(20)  DEFAULT NULL,
  `Email`         varchar(100) DEFAULT NULL,
  `ID_busta`      int(11)      DEFAULT NULL,
  `Password_hash` varchar(255) NOT NULL,
  PRIMARY KEY (`ID_utente`),
  UNIQUE KEY `Email` (`Email`),
  KEY `idx_email` (`Email`),
  KEY `ID_busta`  (`ID_busta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Utenti del sistema (gerarchia collassata)'
  AUTO_INCREMENT=5;
 
-- --------------------------------------------------------
 
CREATE TABLE IF NOT EXISTS `Confronta` (
  `ID_utente`      int(11)   NOT NULL,
  `ID_busta`       int(11)   NOT NULL,
  `Data_confronto` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID_utente`, `ID_busta`),
  KEY `idx_confronta_utente` (`ID_utente`),
  KEY `idx_confronta_busta`  (`ID_busta`),
  KEY `idx_confronta_data`   (`Data_confronto`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Confronti tra buste paga (solo utenti abbonati)';
 
-- --------------------------------------------------------
 
CREATE TABLE IF NOT EXISTS `Impostazioni_contratto` (
  `ID_utente`                   int(100)     NOT NULL,
  `tipologia_dipendente`        enum('Statale','Mettalmeccanico','Commerciale','') NOT NULL DEFAULT '',
  `Livello_dipendente`          varchar(10)  NOT NULL DEFAULT '',
  `Maggiorazione_notturna`      decimal(6,2) NOT NULL DEFAULT 0.00,
  `Maggiorazione_straordinaria` decimal(6,2) NOT NULL DEFAULT 0.00,
  `Maggiorazione_festiva`       decimal(6,2) NOT NULL DEFAULT 0.00,
  `Maggiorazione_prefestiva`    decimal(6,2) NOT NULL DEFAULT 0.00,
  `Indennita_malattia`          decimal(6,2) NOT NULL DEFAULT 0.00,
  `Indennita_reperibilita`      decimal(6,2) NOT NULL DEFAULT 0.00,
  `Indennita_trasferta`         decimal(6,2) NOT NULL DEFAULT 0.00,
  `Tredicesima`                 enum('SI','NO') NOT NULL DEFAULT 'NO',
  `Quattordicesima`             enum('SI','NO') NOT NULL DEFAULT 'NO',
  PRIMARY KEY (`ID_utente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- --------------------------------------------------------
 
CREATE TABLE IF NOT EXISTS `Profilo_contratto` (
  `ID_utente`                   int(100)     NOT NULL AUTO_INCREMENT,
  `tipologia_dipendente`        enum('Statale','Mettalmeccanico','Commerciale','') NOT NULL,
  `Livello_dipendente`          varchar(10)  NOT NULL DEFAULT '',
  `Maggiorazione_notturna`      decimal(6,2) NOT NULL DEFAULT 0.00 COMMENT '%',
  `Maggiorazione_straordinaria` decimal(6,2) NOT NULL DEFAULT 0.00 COMMENT '%',
  `Maggiorazione_festiva`       decimal(6,2) NOT NULL DEFAULT 0.00 COMMENT '%',
  `Maggiorazione_prefestiva`    decimal(6,2) NOT NULL DEFAULT 0.00 COMMENT '%',
  `Indennita_malattia`          decimal(6,2) NOT NULL DEFAULT 0.00 COMMENT '%',
  `Indennita_reperibilita`      decimal(6,2) NOT NULL DEFAULT 0.00 COMMENT '€/ora',
  `Indennita_trasferta`         decimal(6,2) NOT NULL DEFAULT 0.00 COMMENT '€/ora',
  `Tredicesima`                 enum('SI','NO') NOT NULL DEFAULT 'NO',
  `Quattordicesima`             enum('SI','NO') NOT NULL DEFAULT 'NO',
  PRIMARY KEY (`ID_utente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
 
-- --------------------------------------------------------
 
CREATE TABLE IF NOT EXISTS `Privilegi` (
  `ID_privilegio`  int(11)      NOT NULL AUTO_INCREMENT,
  `Nome_privilegio` varchar(100) NOT NULL,
  `Descrizione`    text         DEFAULT NULL,
  `Risorsa`        varchar(100) NOT NULL,
  `Azione`         enum('SELECT','INSERT','UPDATE','DELETE','ALL') NOT NULL,
  PRIMARY KEY (`ID_privilegio`),
  UNIQUE KEY `Nome_privilegio` (`Nome_privilegio`),
  KEY `idx_risorsa_azione` (`Risorsa`, `Azione`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Privilegi atomici del sistema'
  AUTO_INCREMENT=11;
 
-- --------------------------------------------------------
 
CREATE TABLE IF NOT EXISTS `Ruoli` (
  `ID_ruolo`    int(11)     NOT NULL AUTO_INCREMENT,
  `Nome_ruolo`  varchar(50) NOT NULL,
  `Descrizione` text        DEFAULT NULL,
  `Attivo`      tinyint(1)  DEFAULT 1,
  PRIMARY KEY (`ID_ruolo`),
  UNIQUE KEY `Nome_ruolo` (`Nome_ruolo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ruoli come insiemi di privilegi'
  AUTO_INCREMENT=4;
 
-- --------------------------------------------------------
 
CREATE TABLE IF NOT EXISTS `Ruolo_Privilegio` (
  `ID_ruolo`          int(11)   NOT NULL,
  `ID_privilegio`     int(11)   NOT NULL,
  `Data_assegnazione` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID_ruolo`, `ID_privilegio`),
  KEY `idx_ruolo`      (`ID_ruolo`),
  KEY `idx_privilegio` (`ID_privilegio`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Definisce i ruoli come insiemi di privilegi';
 
-- --------------------------------------------------------
 
CREATE TABLE IF NOT EXISTS `Utente_Ruolo` (
  `ID_ruolo`     int(11)      NOT NULL,
  `email_utente` varchar(100) NOT NULL,
  PRIMARY KEY (`email_utente`, `ID_ruolo`),
  KEY `idx_ruolo` (`ID_ruolo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Assegna ruoli agli utenti';
 
-- --------------------------------------------------------
 
CREATE TABLE IF NOT EXISTS `Aziende_tenant` (
  `ID_azienda`        int(11)      NOT NULL AUTO_INCREMENT,
  `ID_tenant`         int(11)      NOT NULL,
  `Ragione_sociale`   varchar(120) NOT NULL,
  `Settore`           varchar(80)  DEFAULT NULL,
  `Email_commerciale` varchar(120) DEFAULT NULL,
  `Stato_relazione`   enum('prospect','in_negoziazione','attiva','chiusa') NOT NULL DEFAULT 'prospect',
  `Data_creazione`    timestamp    NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID_azienda`),
  KEY `idx_aziende_tenant` (`ID_tenant`),
  KEY `idx_aziende_stato`  (`Stato_relazione`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Aziende gestite dai tenant';
 
-- --------------------------------------------------------
 
CREATE TABLE IF NOT EXISTS `Vendite_tenant` (
  `ID_vendita`             int(11)       NOT NULL AUTO_INCREMENT,
  `ID_tenant`              int(11)       NOT NULL,
  `ID_azienda`             int(11)       NOT NULL,
  `Nome_deal`              varchar(120)  NOT NULL,
  `Valore_previsto`        decimal(12,2) NOT NULL,
  `Stato`                  enum('bozza','trattativa','vinta','persa') NOT NULL DEFAULT 'bozza',
  `Data_chiusura_prevista` date          DEFAULT NULL,
  `Note`                   text          DEFAULT NULL,
  `Data_creazione`         timestamp     NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`ID_vendita`),
  KEY `idx_vendite_tenant`  (`ID_tenant`),
  KEY `idx_vendite_azienda` (`ID_azienda`),
  KEY `idx_vendite_stato`   (`Stato`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Pipeline vendite gestita dai tenant';
 
-- ============================================================
-- DATI
-- ============================================================
 
INSERT INTO `Privilegi`
  (`ID_privilegio`, `Nome_privilegio`, `Descrizione`, `Risorsa`, `Azione`) VALUES
(1,  'Inserimento contratto',            'Permette di inserire nuovi contratti',               'contratti',      'INSERT'),
(2,  'Inserimento ore',                  'Permette di inserire ore lavorate',                  'ore',            'INSERT'),
(3,  'Generazione busta paga senza PDF', 'Permette di generare buste paga senza scaricare PDF','buste_paga',     'INSERT'),
(4,  'Download PDF',                     'Permette di scaricare PDF delle buste paga',         'pdf',            'SELECT'),
(5,  'Invio PDF via email',              'Permette di inviare PDF via email',                  'email',          'INSERT'),
(6,  'Archivio buste paga',              'Accesso all\'archivio delle buste paga',             'archivio',       'SELECT'),
(7,  'Confronto tra buste paga',         'Permette di confrontare buste paga',                 'confronto',      'SELECT'),
(8,  'Gestione utenti',                  'Gestione degli utenti del sistema',                  'utenti',         'ALL'),
(9,  'Gestione ruoli',                   'Gestione dei ruoli',                                 'ruoli',          'ALL'),
(10, 'Gestione privilegi',               'Gestione dei privilegi',                             'privilegi',      'ALL'),
(11, 'Gestione vendite tenant',          'Gestione completa vendite e aziende del tenant',     'vendite_tenant', 'ALL');
 
-- --------------------------------------------------------
 
INSERT INTO `Ruoli`
  (`ID_ruolo`, `Nome_ruolo`, `Descrizione`, `Attivo`) VALUES
(1, 'admin',               'Amministratore del sistema con pieni privilegi', 1),
(2, 'utente_abbonato',     'Utente con abbonamento attivo',                  1),
(3, 'utente_non_abbonato', 'Utente senza abbonamento',                       1),
(4, 'tenant',              'Gestisce aziende e pipeline vendite del sito',   1);
 
-- --------------------------------------------------------
 
-- admin (ruolo 1): tutti i privilegi
-- utente_abbonato (ruolo 2): privilegi 1-7
-- utente_non_abbonato (ruolo 3): privilegi 1-3
-- tenant (ruolo 4): privilegio 11
INSERT INTO `Ruolo_Privilegio`
  (`ID_ruolo`, `ID_privilegio`, `Data_assegnazione`) VALUES
(1, 1,  '2026-01-27 17:33:21'),
(1, 2,  '2026-01-27 17:33:21'),
(1, 3,  '2026-01-27 17:33:21'),
(1, 4,  '2026-01-27 17:33:21'),
(1, 5,  '2026-01-27 17:33:21'),
(1, 6,  '2026-01-27 17:33:21'),
(1, 7,  '2026-01-27 17:33:21'),
(1, 8,  '2026-01-27 17:33:21'),
(1, 9,  '2026-01-27 17:33:21'),
(1, 10, '2026-01-27 17:33:21'),
(2, 1,  '2026-01-27 17:34:50'),
(2, 2,  '2026-01-27 17:34:50'),
(2, 3,  '2026-01-27 17:34:50'),
(2, 4,  '2026-01-27 17:34:50'),
(2, 5,  '2026-01-27 17:34:50'),
(2, 6,  '2026-01-27 17:34:50'),
(2, 7,  '2026-01-27 17:34:50'),
(3, 1,  '2026-01-27 17:34:50'),
(3, 2,  '2026-01-27 17:34:50'),
(3, 3,  '2026-01-27 17:34:50'),
(4, 11, '2026-04-13 09:30:00');
 
-- --------------------------------------------------------
 
INSERT INTO `Utenti`
  (`ID_utente`, `N_Telefono`, `Email`, `ID_busta`, `Password_hash`) VALUES
(2, '3922566605', 'mattia.corna2007@gmail.com',             NULL, '$2y$10$C9yMB.fzzuiUGIez6kWiCOHW6TBksDhVm9wUrw1/WsHPn.D9PQY.G'),
(3, '3922566605', 'corna.mattia.studente@itispaleocapa.it', NULL, '$2y$10$W2q5l/EC786G.nhB1DdRU.WDSNAvEdSEO7MrfGuSTx6nSeIRCU4JK'),
(4, '124',        'aaaa@gmail.com',                        NULL, '$2y$10$0pRGhTkD3250WUU1IJx36ebwSDEXpDEpl5xpA35g7RbP6GvmKdsmO');
 
-- --------------------------------------------------------
 
INSERT INTO `Utente_Ruolo`
  (`ID_ruolo`, `email_utente`) VALUES
(3, 'aaaa@gmail.com'),
(1, 'corna.mattia.studente@itispaleocapa.it'),
(1, 'mattia.corna2007@gmail.com');

-- Inserimento account tenant richiesto dall'utente
INSERT INTO `Utenti` (`N_Telefono`, `Email`, `ID_busta`, `Password_hash`) VALUES
(NULL, 'a@gmail.com', NULL, '$2y$10$n8w7UKrmIOA.50/4EBFBR.PWhFp0oYVuXGj8MLcKGFh9GudqKH8Xq');

INSERT INTO `Utente_Ruolo` (`ID_ruolo`, `email_utente`) VALUES
(4, 'a@gmail.com');
 
-- --------------------------------------------------------
 
INSERT INTO `Impostazioni_contratto`
  (`ID_utente`, `tipologia_dipendente`, `Livello_dipendente`,
   `Maggiorazione_notturna`, `Maggiorazione_straordinaria`, `Maggiorazione_festiva`,
   `Maggiorazione_prefestiva`, `Indennita_malattia`, `Indennita_reperibilita`,
   `Indennita_trasferta`, `Tredicesima`, `Quattordicesima`) VALUES
(2, 'Mettalmeccanico', 'C1', 10.00, 53.00, 65.00, 23.00, 98.00, 4.00, 12.00, 'SI', 'SI');
 
-- --------------------------------------------------------
 
INSERT INTO `Profilo_contratto`
  (`ID_utente`, `tipologia_dipendente`, `Livello_dipendente`,
   `Maggiorazione_notturna`, `Maggiorazione_straordinaria`, `Maggiorazione_festiva`,
   `Maggiorazione_prefestiva`, `Indennita_malattia`, `Indennita_reperibilita`,
   `Indennita_trasferta`, `Tredicesima`, `Quattordicesima`) VALUES
(2, 'Mettalmeccanico', '1', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 'NO', 'NO');
 
-- ============================================================
-- FOREIGN KEYS
-- ============================================================
 
ALTER TABLE `Utenti`
  ADD CONSTRAINT `Utenti_ibfk_2`
    FOREIGN KEY (`ID_busta`) REFERENCES `Busta_paga` (`ID_busta`)
    ON DELETE SET NULL ON UPDATE CASCADE;
 
ALTER TABLE `Confronta`
  ADD CONSTRAINT `Confronta_ibfk_1`
    FOREIGN KEY (`ID_utente`) REFERENCES `Utenti` (`ID_utente`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `Confronta_ibfk_2`
    FOREIGN KEY (`ID_busta`) REFERENCES `Busta_paga` (`ID_busta`)
    ON DELETE CASCADE ON UPDATE CASCADE;
 
ALTER TABLE `Ruolo_Privilegio`
  ADD CONSTRAINT `Ruolo_Privilegio_ibfk_1`
    FOREIGN KEY (`ID_ruolo`) REFERENCES `Ruoli` (`ID_ruolo`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `Ruolo_Privilegio_ibfk_2`
    FOREIGN KEY (`ID_privilegio`) REFERENCES `Privilegi` (`ID_privilegio`)
    ON DELETE CASCADE ON UPDATE CASCADE;
 
ALTER TABLE `Utente_Ruolo`
  ADD CONSTRAINT `Utente_Ruolo_ibfk_1`
    FOREIGN KEY (`ID_ruolo`) REFERENCES `Ruoli` (`ID_ruolo`)
    ON DELETE CASCADE ON UPDATE CASCADE;
 
ALTER TABLE `Aziende_tenant`
  ADD CONSTRAINT `Aziende_tenant_ibfk_1`
    FOREIGN KEY (`ID_tenant`) REFERENCES `Utenti` (`ID_utente`)
    ON DELETE CASCADE ON UPDATE CASCADE;
 
ALTER TABLE `Vendite_tenant`
  ADD CONSTRAINT `Vendite_tenant_ibfk_1`
    FOREIGN KEY (`ID_tenant`) REFERENCES `Utenti` (`ID_utente`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `Vendite_tenant_ibfk_2`
    FOREIGN KEY (`ID_azienda`) REFERENCES `Aziende_tenant` (`ID_azienda`)
    ON DELETE CASCADE ON UPDATE CASCADE;
 
-- Riabilita i controlli FK
SET FOREIGN_KEY_CHECKS = 1;
 
-- ============================================================
-- VISTE
-- ============================================================
 
--
-- UC: Generazione busta paga senza PDF
-- Ruoli: utente_non_abbonato, utente_abbonato, admin
--
CREATE OR REPLACE VIEW `v_generazione_busta_paga` AS
SELECT
  u.`ID_utente`,
  u.`Email`,
  bp.`ID_busta`,
  bp.`Mese_riferimento`,
  bp.`Stipendio_lordo`,
  bp.`Stipendio_netto`,
  bp.`Ore_lavorate`,
  bp.`Paga_oraria`,
  bp.`Ore_ferie`,
  bp.`Ore_malattia`,
  bp.`Ore_straordinari`,
  bp.`Ore_festivi`,
  bp.`Ore_prefestivi`,
  bp.`Ore_notturne`,
  bp.`Ore_reperibilita`,
  bp.`Ore_trasferta`
FROM `Utenti` u
LEFT JOIN `Busta_paga` bp ON bp.`ID_busta` = u.`ID_busta`;
 
--
-- UC: Download PDF
-- Ruoli: utente_abbonato, admin
--
CREATE OR REPLACE VIEW `v_download_pdf` AS
SELECT
  u.`ID_utente`,
  u.`Email`,
  u.`N_Telefono`,
  bp.`ID_busta`,
  bp.`Mese_riferimento`,
  bp.`Stipendio_lordo`,
  bp.`Stipendio_netto`,
  bp.`Ore_lavorate`,
  bp.`Paga_oraria`,
  bp.`Ore_ferie`,
  bp.`Ore_malattia`,
  bp.`Ore_straordinari`,
  bp.`Ore_festivi`,
  bp.`Ore_prefestivi`,
  bp.`Ore_notturne`,
  bp.`Ore_reperibilita`,
  bp.`Ore_trasferta`,
  ic.`tipologia_dipendente`,
  ic.`Livello_dipendente`,
  ic.`Tredicesima`,
  ic.`Quattordicesima`
FROM `Utenti` u
JOIN `Busta_paga` bp
  ON bp.`ID_busta` = u.`ID_busta`
LEFT JOIN `Impostazioni_contratto` ic
  ON ic.`ID_utente` = u.`ID_utente`;
 
--
-- UC: Invio PDF via email
-- Ruoli: utente_abbonato, admin
--
CREATE OR REPLACE VIEW `v_invio_pdf_email` AS
SELECT
  u.`ID_utente`,
  u.`Email`,
  bp.`ID_busta`,
  bp.`Mese_riferimento`,
  bp.`Stipendio_lordo`,
  bp.`Stipendio_netto`,
  bp.`Ore_lavorate`,
  bp.`Paga_oraria`,
  bp.`Ore_ferie`,
  bp.`Ore_malattia`,
  bp.`Ore_straordinari`,
  bp.`Ore_festivi`,
  bp.`Ore_prefestivi`,
  bp.`Ore_notturne`,
  bp.`Ore_reperibilita`,
  bp.`Ore_trasferta`
FROM `Utenti` u
JOIN `Busta_paga` bp ON bp.`ID_busta` = u.`ID_busta`;
 
--
-- UC: Archivio buste paga
-- Ruoli: utente_abbonato, admin
--
CREATE OR REPLACE VIEW `v_archivio_buste_paga` AS
SELECT
  u.`ID_utente`,
  u.`Email`,
  c.`ID_busta`,
  c.`Data_confronto`  AS `Data_archiviazione`,
  bp.`Mese_riferimento`,
  bp.`Stipendio_lordo` AS `Lordo`,
  bp.`Stipendio_netto` AS `Netto`,
  (bp.`Stipendio_lordo` - bp.`Stipendio_netto`) AS `Tasse`
FROM `Confronta` c
JOIN `Utenti`     u  ON u.`ID_utente` = c.`ID_utente`
JOIN `Busta_paga` bp ON bp.`ID_busta` = c.`ID_busta`
ORDER BY c.`Data_confronto` DESC;
 
--
-- UC: Confronto tra buste paga
-- Ruoli: utente_abbonato, admin
--
CREATE OR REPLACE VIEW `v_confronto_buste_paga` AS
SELECT
  c1.`ID_utente`,
  u.`Email`,
  c1.`ID_busta`          AS `ID_busta_A`,
  bp1.`Mese_riferimento` AS `Mese_A`,
  bp1.`Stipendio_lordo`  AS `Lordo_A`,
  bp1.`Stipendio_netto`  AS `Netto_A`,
  bp1.`Ore_lavorate`     AS `Ore_lavorate_A`,
  bp1.`Paga_oraria`      AS `Paga_oraria_A`,
  bp1.`Ore_ferie`        AS `Ore_ferie_A`,
  bp1.`Ore_malattia`     AS `Ore_malattia_A`,
  bp1.`Ore_straordinari` AS `Ore_straordinari_A`,
  bp1.`Ore_festivi`      AS `Ore_festivi_A`,
  bp1.`Ore_prefestivi`   AS `Ore_prefestivi_A`,
  bp1.`Ore_notturne`     AS `Ore_notturne_A`,
  bp1.`Ore_reperibilita` AS `Ore_reperibilita_A`,
  bp1.`Ore_trasferta`    AS `Ore_trasferta_A`,
  c1.`Data_confronto`    AS `Data_A`,
  c2.`ID_busta`          AS `ID_busta_B`,
  bp2.`Mese_riferimento` AS `Mese_B`,
  bp2.`Stipendio_lordo`  AS `Lordo_B`,
  bp2.`Stipendio_netto`  AS `Netto_B`,
  bp2.`Ore_lavorate`     AS `Ore_lavorate_B`,
  bp2.`Paga_oraria`      AS `Paga_oraria_B`,
  bp2.`Ore_ferie`        AS `Ore_ferie_B`,
  bp2.`Ore_malattia`     AS `Ore_malattia_B`,
  bp2.`Ore_straordinari` AS `Ore_straordinari_B`,
  bp2.`Ore_festivi`      AS `Ore_festivi_B`,
  bp2.`Ore_prefestivi`   AS `Ore_prefestivi_B`,
  bp2.`Ore_notturne`     AS `Ore_notturne_B`,
  bp2.`Ore_reperibilita` AS `Ore_reperibilita_B`,
  bp2.`Ore_trasferta`    AS `Ore_trasferta_B`,
  c2.`Data_confronto`    AS `Data_B`,
  (bp1.`Stipendio_lordo` - bp2.`Stipendio_lordo`) AS `Diff_lordo`,
  (bp1.`Stipendio_netto` - bp2.`Stipendio_netto`) AS `Diff_netto`
FROM `Confronta`  c1
JOIN `Confronta`  c2  ON  c2.`ID_utente` = c1.`ID_utente`
                      AND c2.`ID_busta`  > c1.`ID_busta`
JOIN `Utenti`     u   ON  u.`ID_utente`  = c1.`ID_utente`
JOIN `Busta_paga` bp1 ON bp1.`ID_busta`  = c1.`ID_busta`
JOIN `Busta_paga` bp2 ON bp2.`ID_busta`  = c2.`ID_busta`;
 
--
-- UC: Gestione utenti (ADMIN)
--
CREATE OR REPLACE VIEW `v_gestione_utenti` AS
SELECT
  u.`ID_utente`,
  u.`Email`,
  u.`N_Telefono`,
  r.`ID_ruolo`,
  r.`Nome_ruolo`,
  r.`Attivo` AS `Ruolo_attivo`
FROM `Utenti` u
LEFT JOIN `Utente_Ruolo` ur ON ur.`email_utente` = u.`Email`
LEFT JOIN `Ruoli`         r  ON r.`ID_ruolo`      = ur.`ID_ruolo`;
 
--
-- UC: Gestione ruoli (ADMIN)
--
CREATE OR REPLACE VIEW `v_gestione_ruoli` AS
SELECT
  r.`ID_ruolo`,
  r.`Nome_ruolo`,
  r.`Descrizione`  AS `Descrizione_ruolo`,
  r.`Attivo`,
  p.`ID_privilegio`,
  p.`Nome_privilegio`,
  p.`Risorsa`,
  p.`Azione`
FROM `Ruoli` r
JOIN `Ruolo_Privilegio` rp ON rp.`ID_ruolo`     = r.`ID_ruolo`
JOIN `Privilegi`         p  ON p.`ID_privilegio` = rp.`ID_privilegio`
WHERE r.`Attivo` = 1;
 
--
-- UC: Gestione privilegi (ADMIN)
--
CREATE OR REPLACE VIEW `v_gestione_privilegi` AS
SELECT
  p.`ID_privilegio`,
  p.`Nome_privilegio`,
  p.`Descrizione`,
  p.`Risorsa`,
  p.`Azione`,
  r.`ID_ruolo`,
  r.`Nome_ruolo`
FROM `Privilegi` p
LEFT JOIN `Ruolo_Privilegio` rp ON rp.`ID_privilegio` = p.`ID_privilegio`
LEFT JOIN `Ruoli`             r  ON r.`ID_ruolo`       = rp.`ID_ruolo`
ORDER BY p.`ID_privilegio`, r.`ID_ruolo`;
 
--
-- UC: Dashboard vendite tenant
-- Ruoli: tenant, admin
--
CREATE OR REPLACE VIEW `v_tenant_dashboard_vendite` AS
SELECT
  vt.`ID_vendita`,
  vt.`ID_tenant`,
  u.`Email`             AS `Email_tenant`,
  vt.`ID_azienda`,
  a.`Ragione_sociale`,
  a.`Settore`,
  vt.`Nome_deal`,
  vt.`Valore_previsto`,
  vt.`Stato`,
  vt.`Data_chiusura_prevista`,
  vt.`Data_creazione`
FROM `Vendite_tenant` vt
JOIN `Utenti`       u ON u.`ID_utente`  = vt.`ID_tenant`
JOIN `Aziende_tenant` a ON a.`ID_azienda` = vt.`ID_azienda`;
 
-- ============================================================
 
COMMIT;
 
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;