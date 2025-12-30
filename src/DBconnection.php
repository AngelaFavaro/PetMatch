<?php
namespace DB;

use mysqli_warning;

class DBAccess {

	private const HOST_DB = "localhost"; 
	/* */
	private const DATABASE_NAME = "lsade"; //qui devi mettere le tue credenziali di login
	private const USERNAME = "lsade";
	private const PASSWORD = "ohdi6Quohtoo6eiD"; //quella dentro il file  pwd_db_2526.txt

	private $connection;

	public function openDBConnection() { //funzione per capire se la connessione è stata aperta o no

		mysqli_report(MYSQLI_REPORT_ERROR); //stampa a browser gli errori di connessione al db

		$this->connection = mysqli_connect(DBAccess::HOST_DB, DBAccess::USERNAME, DBAccess::PASSWORD, DBAccess::DATABASE_NAME);

		if (mysqli_connect_errno()) { //fa il cast della stringa, se vuota restituisce true (nessun errore) 
			return false;
		} else {
			return true;
		}
	} //il risultato dopo questo è che la variabile connection ha dentro la connessione al db

	public function closeConnection() {
		mysqli_close($this->connection);
	}

    //qui funzioni per fare query

	/**
	 * Restituisce i dettagli di una richiesta di adozione (utente + animale).
	 */
	public function getRequestDetails($emailRichiedente, $idAnimale): array|null {
		if (!$this->connection){ //se la connessione non è aperta
			return null;
		}
        //query per prendere i dettagli della richiesta di adozione
		$query = "
			SELECT
				ra.Stato AS stato,
				ra.DataRichiesta AS data_richiesta,
				ra.LetteraPresentazione AS lettera_presentazione,
				ra.Trasporto AS trasporto_richiesta,
				ra.Email AS email_richiedente,
				ra.IDanimale AS id_animale,
				a.Nome AS nome_animale,
				a.Sesso AS sesso_animale,
				TIMESTAMPDIFF(YEAR, a.DataNascita, CURDATE()) AS eta_animale,
				a.Razza AS razza_animale,
				a.Trasporto AS trasporto_animale,
				a.DescrFamiglia AS famiglia_ideale,
				a.CondizioniMediche AS condizioni_mediche,
				a.DescrComportamentale AS descrizione_caratteriale,
				u.Nome AS nome_richiedente,
				u.Cognome AS cognome_richiedente,
				u.Telefono AS telefono_richiedente,
				CONCAT_WS(', ', u.Via, u.Citta, u.CAP) AS indirizzo_richiedente,
				ra.Appunti AS appunti,
                ra.DataFineValutazione AS data_fine_valutazione,
                ra.DataInizioValutazione AS data_inizio_valutazione,
                a.Email as email_admin,
                m.Nome as nome_admin,
                m.Cognome as cognome_admin
			FROM RICHIESTE_ADOZIONI ra
			JOIN UTENTI u ON u.Email = ra.Email
			JOIN ANIMALI a ON a.IDanimale = ra.IDanimale
			JOIN AMMINISTRATORI m ON m.Email = a.Email
			WHERE ra.Email = ? AND ra.IDanimale = ?
		";
        
        $stmt = mysqli_prepare($this->connection, $query);
        if($stmt === false){
            return null;
        }
        
        mysqli_stmt_bind_param($stmt, 'si', $emailRichiedente, $idAnimale);
        
        if(!mysqli_stmt_execute($stmt)){
            mysqli_stmt_close($stmt);
            return null;
        }
        
        $queryResult = mysqli_stmt_get_result($stmt);
        
        if($queryResult === false || mysqli_num_rows($queryResult) == 0){
            mysqli_stmt_close($stmt);
			return null;
		}
        
        $row = mysqli_fetch_assoc($queryResult);
        mysqli_stmt_close($stmt);
        
        return [ //array associativo con i dettagli della richiesta
            'stato' => $row['stato'],
            'data-richiesta' => $row['data_richiesta'],
            'lettera-di-presentazione' => $row['lettera_presentazione'],
            'trasporto-richiesta' => $row['trasporto_richiesta'],
            'email-richiedente' => $row['email_richiedente'],
            'id-animale' => $row['id_animale'],
            'nome-animale' => $row['nome_animale'],
            'sesso-animale' => $row['sesso_animale'],
            'eta-animale' => $row['eta_animale'],
            'razza-animale' => $row['razza_animale'],
            'trasporto-animale' => $row['trasporto_animale'],
            'famiglia-ideale' => $row['famiglia_ideale'],
            'condizioni-mediche' => $row['condizioni_mediche'],
            'descrizione-caratteriale' => $row['descrizione_caratteriale'],
            'nome-richiedente' => $row['nome_richiedente'],
            'cognome-richiedente' => $row['cognome_richiedente'],
            'telefono-richiedente' => $row['telefono_richiedente'],
            'indirizzo-richiedente' => $row['indirizzo_richiedente'],
            'data_fine_valutazione' => $row['data_fine_valutazione'],
            'data_inizio_valutazione' => $row['data_inizio_valutazione'],
            'appunti' => $row['appunti'],
            'email-admin' => $row['email_admin'],
            'nome-admin' => $row['nome_admin'],
            'cognome-admin' => $row['cognome_admin'],
        ];

		
	}

    public function startEvaluation($emailRichiedente, $idAnimale): bool {
        if (!$this->connection){ //se la connessione non è aperta
            return false;
        }

        $query = "UPDATE RICHIESTE_ADOZIONI SET Stato = 'In valutazione', DataInizioValutazione = ? WHERE Email = ? AND IDanimale = ?";

        $stmt = mysqli_prepare($this->connection, $query);
        if($stmt === false){
            return false;
        }
        $oggi = date('Y-m-d');
        mysqli_stmt_bind_param($stmt, 'ssi', $oggi, $emailRichiedente, $idAnimale);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }

    public function rejectRequest($emailRichiedente, $idAnimale, $statoPrecedente): bool {
        if (!$this->connection){ //se la connessione non è aperta
            return false;
        }

        // prima controlla se la richiesta era in valutazione, se sì imposta stato Respinta e la data di fine, altrimenti Annullata
        if($statoPrecedente === 'In valutazione'){
            $query = "UPDATE RICHIESTE_ADOZIONI SET Stato = 'Annullata', DataFineValutazione=? WHERE Email = ? AND IDanimale = ?";
            $stmt = mysqli_prepare($this->connection, $query);
            if($stmt === false){
                return false;
            }
            $oggi = date('Y-m-d');
            mysqli_stmt_bind_param($stmt, 'ssi',$oggi, $emailRichiedente, $idAnimale);
            
        } else {
            $query = "UPDATE RICHIESTE_ADOZIONI SET Stato = 'Respinta' WHERE Email = ? AND IDanimale = ?";
            $stmt = mysqli_prepare($this->connection, $query);
            if($stmt === false){
                return false;
            }
            mysqli_stmt_bind_param($stmt, 'si', $emailRichiedente, $idAnimale);
        }

        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }

    public function openRequest($emailRichiedente, $idAnimale): bool {
        if (!$this->connection){ //se la connessione non è aperta
            return false;
        }

        $query = "UPDATE RICHIESTE_ADOZIONI SET Stato = 'Nuova', DataFineValutazione = NULL WHERE Email = ? AND IDanimale = ?";

        $stmt = mysqli_prepare($this->connection, $query);
        if($stmt === false){
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'si', $emailRichiedente, $idAnimale);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }

    public function setToTransport($emailRichiedente, $idAnimale): bool {
        if (!$this->connection){ //se la connessione non è aperta
            return false;
        }

        $query = "UPDATE RICHIESTE_ADOZIONI SET Stato = 'Da trasportare' WHERE Email = ? AND IDanimale = ?";

        $stmt = mysqli_prepare($this->connection, $query);
        if($stmt === false){
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'si', $emailRichiedente, $idAnimale);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }

    public function acceptRequest($emailRichiedente, $idAnimale): bool {
        if (!$this->connection){ //se la connessione non è aperta
            return false;
        }

        $query = "UPDATE RICHIESTE_ADOZIONI SET Stato = 'Conclusa' SET DataWHERE Email = ? AND IDanimale = ?";

        $stmt = mysqli_prepare($this->connection, $query);
        if($stmt === false){
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'si', $emailRichiedente, $idAnimale);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }


	
    public function updateNote($emailRichiedente, $idAnimale, $note): bool {
        if (!$this->connection){ //se la connessione non è aperta
            return false;
        }

        $query = "UPDATE RICHIESTE_ADOZIONI SET Appunti = ? WHERE Email = ? AND IDanimale = ?";

        $stmt = mysqli_prepare($this->connection, $query);
        if($stmt === false){
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'ssi', $note, $emailRichiedente, $idAnimale);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }
}


?>