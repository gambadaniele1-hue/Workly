# Manuale Utente — BPIC

## Introduzione
Questo manuale descrive passo-passo le funzionalità disponibili per gli utenti del sistema BPIC: accesso, gestione sottoscrizioni, inserimento dati per la generazione delle buste paga, conservazione, confronto e le operazioni riservate agli amministratori.

---

## 1. Accesso e autenticazione
- Pagina di login: inserire email e password per autenticarsi.
- Sessione: il sistema utilizza un token JWT memorizzato in cookie sicuri (HttpOnly, SameSite=Strict). La sessione ha durata limitata dal server.
- Logout: usare il pulsante "Esci" per invalidare il cookie e tornare al login.

### 1.1 Token API (per integrazioni)
- Esiste un endpoint API (`/api/token.php`) che restituisce un token Bearer per client esterni.
- I token API possono avere TTL diversi rispetto alla sessione browser. Gli sviluppatori devono usare l'endpoint API per integrazioni programmatiche.

---

## 2. Registrazione e gestione account
- Registrazione: compilare i campi richiesti (nome, email, password). Dopo la registrazione l'utente viene creato con ruolo di base.
- Recupero password: seguire la procedura presente nella pagina di login (se abilitata dall'amministratore).
- Profilo utente: aggiornare le informazioni personali dall'area personale.

---

## 3. Sottoscrizioni e privilegi
- Il sistema supporta ruoli e privilegi (es. utente standard, manager, amministratore).
- Le funzionalità disponibili nella UI dipendono dal ruolo associato all'account.
- Gli amministratori possono creare/assegnare ruoli e privileggi tramite l'area admin.

---

## 4. Panoramica funzionalità per l'utente
- Creazione busta paga: inserimento manuale o import dati per generare una busta paga.
- Campi contratto: configurare gli elementi contrattuali (ore, livello, INPS/INAIL se previsto, detrazioni, ecc.).
- Generazione PDF: al termine della compilazione si può generare la busta paga in PDF.
- Invio/archiviazione: le buste generate possono essere archiviate nel sistema e inviate via email (se abilitato).
- Storico: consultare l'elenco delle buste generate (filtri per periodo, utente, azienda).
- Confronta: funzione per confrontare due generazioni (prima/dopo migrazione o versioni diverse) e visualizzare differenze.

---

## 5. Flusso dettagliato — Creare una busta paga
1. Accedere con le proprie credenziali.
2. Dal menu, selezionare "Nuova busta paga".
3. Compilare i dati anagrafici del dipendente e i campi contrattuali (inclusi eventuali riferimenti a contratti collettivi).
4. Inserire gli importi retributivi, trattenute e contributi.
5. Usare i pulsanti di anteprima per verificare i calcoli.
6. Generare PDF e scegliere se "Salvare in archivio" e/o "Inviare per email".
7. Controllare lo storico per assicurarsi che la busta risulti archiviata.

---

## 6. Confronto risultati e verifica qualità
- La funzione di confronto accetta due versioni o output e mostra differenze riga-per-riga.
- Utile per validare risultati dopo migrazioni o aggiornamenti.
- Per problemi nei calcoli, esportare i dati e contattare il supporto con i file di log/anteprima.

---

## 7. Funzionalità amministrative
- Gestione utenti: creare, modificare, disabilitare account.
- Gestione ruoli/privilegi: definire azioni consentite per ogni ruolo.
- Generazione bulk: operazioni di massa (es. generare buste per un gruppo di lavoratori).
- Controllo accessi API: generare token, revocare token e verificare permessi tramite gli endpoint API `/api/verify_token.php` e `/api/permissions.php`.

---

## 8. Linee guida di sicurezza e privacy
- Non condividere le credenziali.
- Le password sono memorizzate in forma sicura (hash); per modifiche usare le procedure di reset.
- Per integrazioni, usare token API con scadenze adeguate e conservarli in modo sicuro.

---

## 9. Risoluzione problemi comuni
- Login fallito: verificare email/password; se il problema persiste, resettare la password.
- Token scaduto: effettuare nuovamente il login.
- Errori nella generazione PDF: verificare i dati inseriti (campi obbligatori) e riprovare.
- Mancata visualizzazione funzioni: contattare l'amministratore per verificare i privilegi del ruolo.

---

## 10. Contatti e supporto
- Per supporto tecnico interno, contattare l'amministratore del sistema o l'indirizzo email riportato nella pagina "Contatti" dell'applicazione.

---

## Nota finale
Questo documento riassume le operazioni utente principali e le pratiche consigliate. Se desideri che il manuale contenga esempi passo-passo per task specifici (es. schermate illustrate, esempi di CSV di import), dimmi quali sezioni arricchire e le aggiungo.
