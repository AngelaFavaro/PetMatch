-- DROP TABLE senza CASCADE, ordine corretto
DROP TABLE IF EXISTS SEGNALAZIONI_NUOVE_ACCOGLIENZE;
DROP TABLE IF EXISTS TRASPORTI;
DROP TABLE IF EXISTS RICHIESTE_ADOZIONI;
DROP TABLE IF EXISTS PREFERITI;
DROP TABLE IF EXISTS FOTO;
DROP TABLE IF EXISTS ANIMALI;
DROP TABLE IF EXISTS ORGANIZZAZIONE;
DROP TABLE IF EXISTS EVENTI;
DROP TABLE IF EXISTS UTENTI;

-- UTENTI
CREATE TABLE UTENTI (
    Email VARCHAR(255) PRIMARY KEY,
    Nome VARCHAR(100) NOT NULL,
    Cognome VARCHAR(100) NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Telefono VARCHAR(20),
    Via VARCHAR(255),
    Citta VARCHAR(100),
    CAP VARCHAR(5),
    Ruolo VARCHAR(5) NOT NULL,
    ImgPath VARCHAR(512) DEFAULT 'assets/images/users/default-pic.png'
    CHECK (Ruolo IN ('Admin','User')),
    CHECK (
        (Via IS NULL AND Citta IS NULL AND CAP IS NULL)
        OR
        (Via IS NOT NULL AND Citta IS NOT NULL AND CAP IS NOT NULL)
    )
);

-- EVENTI
CREATE TABLE EVENTI (
    Titolo VARCHAR(255) NOT NULL,
    DataPubblicazione DATE NOT NULL,
    DataEvento DATE NOT NULL,
    DescrEvento TEXT NOT NULL,
    ImgPath VARCHAR(512) NOT NULL, -- Già presente, rinominato per coerenza
    PRIMARY KEY (Titolo, DataEvento),
    Via VARCHAR(255) NOT NULL,
    Citta VARCHAR(100) NOT NULL
    CHECK (DataEvento >= DataPubblicazione)
);

-- ORGANIZZAZIONE
CREATE TABLE ORGANIZZAZIONE(
    Titolo VARCHAR(255) NOT NULL,
    DataEvento DATE NOT NULL,
    Email VARCHAR(255) NOT NULL,
    PRIMARY KEY (Titolo, DataEvento, Email),
    FOREIGN KEY (Titolo, DataEvento) REFERENCES EVENTI (Titolo, DataEvento) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (Email) REFERENCES UTENTI (Email) ON DELETE CASCADE ON UPDATE CASCADE
);

-- ANIMALI
CREATE TABLE ANIMALI(
    IDanimale INT AUTO_INCREMENT PRIMARY KEY,
    Nome VARCHAR(100) NOT NULL,
    DataNascita DATE NOT NULL,
    DataRegistrazione DATE NOT NULL,
    Sesso CHAR(1) NOT NULL,
    Tipo VARCHAR(5) NOT NULL,
    Colore VARCHAR(100) NOT NULL,
    Pelo VARCHAR(20) NOT NULL,
    Taglia VARCHAR(10) NOT NULL,
    Razza VARCHAR(100) NOT NULL,
    DescrFamiglia TEXT NOT NULL,
    DescrComportamentale TEXT NOT NULL,
    CondizioniMediche TEXT,
    Trasporto TINYINT(1) NOT NULL,
    ImgPath VARCHAR(512) NOT NULL,
    Email VARCHAR(255),

    FOREIGN KEY (Email) REFERENCES UTENTI (Email) ON DELETE SET NULL ON UPDATE CASCADE,

    CHECK (Sesso IN ('F','M')),
    CHECK (Tipo IN ('Gatto','Cane')),
    CHECK ((Pelo IN ('Corto','Medio','Lungo')) OR (Pelo = 'Senza pelo' AND Tipo = 'Gatto')),
    CHECK (Taglia IN ('Piccolo','Medio','Grande')),
    CHECK (DataRegistrazione >= DataNascita)
);

-- FOTO (Galleria multi immagine per ogni animale, per ora non la usiamo, se ne abbiamo bisogno è pronta)
CREATE TABLE FOTO(
    Path VARCHAR(512) PRIMARY KEY,
    IDanimale INT NOT NULL,
    FOREIGN KEY (IDanimale) REFERENCES ANIMALI (IDanimale) ON DELETE CASCADE ON UPDATE CASCADE
);

-- PREFERITI
CREATE TABLE PREFERITI(
    Email VARCHAR(255) NOT NULL,
    IDanimale INT NOT NULL,
    PRIMARY KEY (Email, IDanimale),
    FOREIGN KEY (Email) REFERENCES UTENTI (Email) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (IDanimale) REFERENCES ANIMALI (IDanimale) ON DELETE CASCADE ON UPDATE CASCADE
);

-- RICHIESTE_ADOZIONI
CREATE TABLE RICHIESTE_ADOZIONI(
    Email VARCHAR(255) NOT NULL,
    IDanimale INT NOT NULL,
    Trasporto TINYINT(1) NOT NULL,
    Appunti TEXT,
    LetteraPresentazione TEXT NOT NULL,
    Stato VARCHAR(20) NOT NULL DEFAULT 'Nuova', 
    DataRichiesta DATE NOT NULL,
    DataFineValutazione DATE,
    DataInizioValutazione DATE,
    PRIMARY KEY(Email, IDanimale),
    FOREIGN KEY (Email) REFERENCES UTENTI (Email) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (IDanimale) REFERENCES ANIMALI (IDanimale) ON DELETE CASCADE ON UPDATE CASCADE,
    CHECK (Stato IN ('Nuova', 'In valutazione','Da trasportare','Accettata', 'Respinta', 'Annullata')),
    CHECK (DataFineValutazione IS NULL OR DataFineValutazione >= DataRichiesta),
    CHECK (DataFineValutazione IS NULL OR DataInizioValutazione IS NULL OR DataFineValutazione >= DataInizioValutazione)
    CHECK (
        (Stato <> 'Nuova') OR --se lo stato è diverso da nuova allora tutto ok, altrimenti SE è nuova allora controlla le date
        (Stato = 'Nuova' AND DataInizioValutazione IS NULL AND DataFineValutazione IS NULL)
    )
);

-- TRASPORTI
CREATE TABLE TRASPORTI(
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Email VARCHAR(255) NULL,
    IDanimale INT NULL,
    Via VARCHAR(255) NOT NULL,
    Citta VARCHAR(100) NOT NULL,
    CAP VARCHAR(5) NOT NULL,
    DataArrivo DATE NOT NULL,
    DataPartenza DATE NOT NULL,
    CHECK (DataArrivo >= DataPartenza),
    FOREIGN KEY (Email, IDanimale) REFERENCES RICHIESTE_ADOZIONI (Email, IDanimale) ON DELETE SET NULL ON UPDATE CASCADE
);

-- SEGNALAZIONI_NUOVE_ACCOGLIENZE
CREATE TABLE SEGNALAZIONI_NUOVE_ACCOGLIENZE (
    ID INT AUTO_INCREMENT PRIMARY KEY, 
    NominativoRichiedente VARCHAR(255) NOT NULL,
    DataRichiesta DATETIME DEFAULT CURRENT_TIMESTAMP,
    EmailAmm VARCHAR(255),
    EmailRichiedente VARCHAR(255) NOT NULL,
    TipoAnimale VARCHAR(5) NOT NULL,
    FOREIGN KEY (EmailAmm) REFERENCES UTENTI (Email) ON DELETE SET NULL ON UPDATE CASCADE
);



INSERT INTO `ANIMALI` (`IDanimale`, `Nome`, `DataNascita`, `DataRegistrazione`, `Sesso`, `Tipo`, `Colore`, `Pelo`, `Taglia`, `Razza`, `DescrFamiglia`, `DescrComportamentale`, `CondizioniMediche`, `Trasporto`, `ImgPath`, `Email`) VALUES
(1, 'Shaker', '2014-07-15', '2025-12-30', 'M', 'Cane', 'Bianco, Marrone', 'Corto', 'Piccolo', 'Jack russell terrier', 'molto molto calma...molto calma', 'Morde quando non gli dai la pizza, morde quando esci di case, distrugge tutti i giochi, le cucce e le coperte, trema sempre, ha sempre bisogno di coccole (gli piacciono i piedi), penso sia pazzo per colpa della famiglia precedente', NULL, 1, 'assets/images/animals/bobo.png', ''),
(4, 'Test', '2024-01-01', '2025-12-31', 'M', 'Gatto', 'Nero', 'Corto', 'Piccolo', 'Europeo', 'Test family', 'Test behavior', 'Sano', 0, 'assets/images/animals/animals_1767198281_ffa86946.png', '');
