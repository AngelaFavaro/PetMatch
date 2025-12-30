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
	public function getRichiestaDettagli($emailRichiedente, $idAnimale) {
		if (!$this->connection){ //se la connessione non è aperta
			return null;
		}

		$default = [
			'stato' => null,
			'data-richiesta' => null,
			'lettera-di-presentazione' => null,
			'trasporto-richiesta' => null,
			'email-richiedente' => null,
			'id-animale' => null,
			'nome-animale' => null,
			'sesso-animale' => null,
			'eta-animale' => null,
			'razza-animale' => null,
			'trasporto-animale' => null,
			'famiglia-ideale' => null,
			'condizioni-mediche' => null,
			'descrizione-acaratteriale' => null,
			'nome-richiedente' => null,
			'cognome-richiedente' => null,
			'telefono-richiedente' => null,
			'indirizzo-richiedente' => null
		];

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
				CONCAT_WS(', ', u.Via, u.Citta, u.CAP) AS indirizzo_richiedente
			FROM RICHIESTE_ADOZIONI ra
			JOIN UTENTI u ON u.Email = ra.Email
			JOIN ANIMALI a ON a.IDanimale = ra.IDanimale
			WHERE ra.Email = ? AND ra.IDanimale = ?
			LIMIT 1
		";
        
        $queryResult= mysqli_query($this->connection, $query) or die("Errore in dbConnection: ".mysqli_error($this->connection)); 

        if(mysqli_num_rows($queryResult)!=0){
		    $row = $queryResult->fetch_assoc();
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
                'descrizione-acaratteriale' => $row['descrizione_caratteriale'],
                'nome-richiedente' => $row['nome_richiedente'],
                'cognome-richiedente' => $row['cognome_richiedente'],
                'telefono-richiedente' => $row['telefono_richiedente'],
                'indirizzo-richiedente' => $row['indirizzo_richiedente']
        ];
        }else{
			return $default;
		}

		
	}

	
}


?>