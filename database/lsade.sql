-- phpMyAdmin SQL Dump
-- version 5.2.2deb1+deb13u1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Creato il: Dic 31, 2025 alle 17:05
-- Versione del server: 11.8.3-MariaDB-0+deb13u1 from Debian
-- Versione PHP: 8.4.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lsade`
--

--
-- Dump dei dati per la tabella `AMMINISTRATORI`
--

INSERT INTO `AMMINISTRATORI` (`Email`, `Nome`, `Cognome`, `AdminPW`, `ImgPath`) VALUES
('lindorlinor@gmail.com', 'Linor', 'Sadè', 'password123', 'assets/images/admins/linor.jpg');

--
-- Dump dei dati per la tabella `ANIMALI`
--

INSERT INTO `ANIMALI` (`IDanimale`, `Nome`, `DataNascita`, `DataRegistrazione`, `Sesso`, `Tipo`, `Colore`, `Pelo`, `Taglia`, `Razza`, `DescrFamiglia`, `DescrComportamentale`, `CondizioniMediche`, `Trasporto`, `ImgPath`, `Email`) VALUES
(1, 'Shaker', '2014-07-15', '2025-12-30', 'M', 'Cane', 'Bianco, Marrone', 'Corto', 'Piccolo', 'Jack russell terrier', 'molto molto calma...molto calma', 'Morde quando non gli dai la pizza, morde quando esci di case, distrugge tutti i giochi, le cucce e le coperte, trema sempre, ha sempre bisogno di coccole (gli piacciono i piedi), penso sia pazzo per colpa della famiglia precedente', NULL, 1, 'assets/images/animals/bobo.png', 'lindorlinor@gmail.com'),
(4, 'Test', '2024-01-01', '2025-12-31', 'M', 'Gatto', 'Nero', 'Corto', 'Piccolo', 'Europeo', 'Test family', 'Test behavior', 'Sano', 0, 'assets/images/animals/animals_1767198281_ffa86946.png', 'lindorlinor@gmail.com');

--
-- Dump dei dati per la tabella `RICHIESTE_ADOZIONI`
--

INSERT INTO `UTENTI` (`Email`, `Nome`, `Cognome`, `UtentePW`, `Telefono`, `Via`, `Citta`, `CAP`, `ImgPath`) VALUES
('lindorlinor@gmail.com', 'Linor', 'Sadè', 'password123456', '3779765767', 'Via campagna alta 2', 'Montegrotto Terme (PD)', '35036', 'assets/images/users/linor.jpg');
COMMIT;
INSERT INTO `RICHIESTE_ADOZIONI` (`Email`, `IDanimale`, `Trasporto`, `Appunti`, `LetteraPresentazione`, `Stato`, `DataRichiesta`, `DataFineValutazione`, `DataInizioValutazione`) VALUES
('lindorlinor@gmail.com', 1, 1, 'sta cercando di prendere il cane che aveva prima. È pazzo per colpa sua, nn accetto la richiesta. aggiornamento: va bene inizio la valutazione', 'Prova di lettera di presentazione, giusto per far apparire qualcosa. Io sono perfetta per questo animale perchè in realtà è mio. Effettivamente non significa che io sia perfetta per l\'animale, l\'animale è pazzo per un motivo...', 'In valutazione', '2025-12-31', NULL, '2025-12-31'),
('lindorlinor@gmail.com', 4, 0, NULL, '', 'Nuova', '2026-01-01', NULL, NULL);

--
-- Dump dei dati per la tabella `UTENTI`
--


/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
