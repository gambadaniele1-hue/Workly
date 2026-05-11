-- Migrazione: Aggiunta ID_utente e vincoli UNIQUE a Busta_paga
-- Questo script aggiorna la tabella esistente senza perdere dati

ALTER TABLE `Busta_paga`
	ADD COLUMN IF NOT EXISTS `ID_utente` INT(11) NULL AFTER `ID_busta`,
	ADD COLUMN IF NOT EXISTS `Data_creazione` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER `Ore_trasferta`;

-- Aggiungi gli indici senza vincolo UNIQUE per permettere più generazioni nello stesso mese
ALTER TABLE `Busta_paga`
	ADD KEY `idx_utente` (`ID_utente`),
	ADD KEY `idx_mese` (`Mese_riferimento`);

-- Aggiungi la foreign key se vuoi integrità referenziale
-- ALTER TABLE `Busta_paga`
-- ADD CONSTRAINT `fk_busta_utente` FOREIGN KEY (`ID_utente`) REFERENCES `Utenti` (`ID_utente`) ON DELETE CASCADE;
