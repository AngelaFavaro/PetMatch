<?php
namespace DB;

use mysqli_warning;

class DBAccess {

	private const HOST_DB = "localhost"; 

	private const DATABASE_NAME = "acanazza";
	private const USERNAME = "acanazza";
	private const PASSWORD = "meiSeeQueN4their";

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

    //FUNZIONI PER FARE QUERY

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
                a.DataNascita AS data_nascita_animale,
                a.Razza AS razza_animale,
                a.Trasporto AS trasporto_animale,
                a.DescrFamiglia AS famiglia_ideale,
                a.CondizioniMediche AS condizioni_mediche,
                a.Tipo AS tipo_animale,
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
                m.Cognome as cognome_admin,
                u.Via AS via_trasporto,
                u.Citta AS citta_trasporto,
                u.CAP AS cap_trasporto,
                t.DataArrivo AS data_arrivo,
                t.DataPartenza AS data_partenza,
                u.imgPath AS imgPath,
                a.imgPath AS animalImgPath
            FROM RICHIESTE_ADOZIONI ra
            JOIN UTENTI u ON u.Email = ra.Email
            JOIN ANIMALI a ON a.IDanimale = ra.IDanimale
            LEFT JOIN UTENTI m ON m.Email = a.Email
            LEFT JOIN TRASPORTI t ON t.Email = ra.Email AND t.IDanimale = ra.IDanimale
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
            'data-nascita' => $row['data_nascita_animale'],
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
            'data-arrivo' => $row['data_arrivo'],
            'via-trasporto' => $row['via_trasporto'],
            'citta-trasporto' => $row['citta_trasporto'],
            'cap-trasporto' => $row['cap_trasporto'],
            'data-partenza' => $row['data_partenza'],
            'imgPath' => $row['imgPath'],
            'animalImgPath' => $row['animalImgPath']
        ];

		
	}

    /** trova per un determinato user il numero richieste attive dove attive significa non concluse, non annullate, non respinte
     */
    public function countActiveRequestsForUser($email): int {
        if (!$this->connection){
            return 0;
        }

        $query = "SELECT COUNT(*)-1 AS totale FROM RICHIESTE_ADOZIONI R WHERE R.Stato NOT IN ('Accettata', 'Annullata', 'Respinta') AND R.Email = ?";

        $stmt = mysqli_prepare($this->connection, $query);
        if($stmt === false){
            return 0;
        }

        mysqli_stmt_bind_param($stmt, 's', $email);
        if(!mysqli_stmt_execute($stmt)){
            mysqli_stmt_close($stmt);
            return 0;
        }
        $queryResult = mysqli_stmt_get_result($stmt);
        if($queryResult === false || mysqli_num_rows($queryResult) == 0){
            mysqli_stmt_close($stmt);
            return 0;
        }
        $row = mysqli_fetch_assoc($queryResult);
        mysqli_stmt_close($stmt);
        $totale = (int)$row['totale'];
        return $totale < 0 ? 0 : $totale;
    }


    public function setTransportDates($email, $idAnimale, $newDatePartenza,$newDateArrivo): bool {
        if (!$this->connection){ //se la connessione non è aperta
            return false;
        }

        $query = "UPDATE TRASPORTI SET DataArrivo = ?, DataPartenza = ? WHERE Email = ? AND IDanimale = ?";

        $stmt = mysqli_prepare($this->connection, $query);
        if($stmt === false){
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'sssi', $newDateArrivo, $newDatePartenza, $email, $idAnimale);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
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

    //imposta come "Accettata" la richiesta a cui fa riferimeto il trasporto
    public function markTransportCompleted($emailRichiedente, $idAnimale): bool {
        if (!$this->connection){ //se la connessione non è aperta
            return false;
        }

        $query = "UPDATE RICHIESTE_ADOZIONI SET Stato = 'Accettata' WHERE Email = ? AND IDanimale = ?";
        
        $stmt = mysqli_prepare($this->connection, $query);
        if($stmt === false){
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'si', $emailRichiedente, $idAnimale);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if($result){
            return $this->setRespintaOtherRequestsForAnimal($idAnimale, $emailRichiedente);
        }else{
            return $result;
        }
    }
    
    private function setRespintaOtherRequestsForAnimal($idAnimale, $acceptedEmail): bool {
        if (!$this->connection){ //se la connessione non è aperta
            return false;
        }

        $query = "UPDATE RICHIESTE_ADOZIONI SET Stato = 'Respinta', DataFineValutazione = ? WHERE IDanimale = ? AND Email <> ?";

        $stmt = mysqli_prepare($this->connection, $query);
        if($stmt === false){
            return false;
        }
        $oggi = date('Y-m-d');
        mysqli_stmt_bind_param($stmt, 'sis', $oggi, $idAnimale, $acceptedEmail);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }
    public function rejectRequest($emailRichiedente, $idAnimale, $statoPrecedente): bool {
        if (!$this->connection){ //se la connessione non è aperta
            return false;
        }

        // prima controlla se la richiesta era in valutazione, se sì imposta stato Respinta e la data di fine, altrimenti Annullata
        if($statoPrecedente === 'Nuova') {
            $query = "UPDATE RICHIESTE_ADOZIONI SET Stato = 'Respinta' WHERE Email = ? AND IDanimale = ?";
            $stmt = mysqli_prepare($this->connection, $query);
            if($stmt === false){
                return false;
            }
            mysqli_stmt_bind_param($stmt, 'si', $emailRichiedente, $idAnimale);
            
        } else {
            $query = "UPDATE RICHIESTE_ADOZIONI SET Stato = 'Respinta', DataFineValutazione=? WHERE Email = ? AND IDanimale = ?";
            $stmt = mysqli_prepare($this->connection, $query);
            if($stmt === false){
                return false;
            }
            $oggi = date('Y-m-d');
            mysqli_stmt_bind_param($stmt, 'ssi',$oggi, $emailRichiedente, $idAnimale);
        }
        
        if($statoPrecedente === 'Da trasportare' || $statoPrecedente === 'Accettata') {
            //ELIMINA IL TRASPORTO DELLA RICHIESTA SE CE NE ERA UNO
            $query2 = "DELETE FROM TRASPORTI where Email = ? AND IDanimale = ?";
            $stmt2 = mysqli_prepare($this->connection, $query2);
            if($stmt2 === false){
                return false;
            }
            mysqli_stmt_bind_param($stmt2, 'si', $emailRichiedente, $idAnimale);
            $result2 = mysqli_stmt_execute($stmt2);
        }
        
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        // ritorna falso se uno dei due fallisce, true solo se entrambi vanno a buon fine, se result2 non è settato (non c'era da eliminare il trasporto) ritorna true se result è true
        return $result && (!isset($result2) || $result2);
    }

    public function openRequest($emailRichiedente, $idAnimale): bool {
        if (!$this->connection){ //se la connessione non è aperta
            return false;
        }

        $query = "UPDATE RICHIESTE_ADOZIONI SET Stato = 'Nuova', DataFineValutazione = NULL, DataInizioValutazione = NULL WHERE Email = ? AND IDanimale = ?";

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

        $query = "UPDATE RICHIESTE_ADOZIONI SET Stato = 'Da trasportare', DataFineValutazione = ? WHERE Email = ? AND IDanimale = ?";

        $stmt = mysqli_prepare($this->connection, $query);
        if($stmt === false){
            return false;
        }
        $oggi = date('Y-m-d');
        mysqli_stmt_bind_param($stmt, 'ssi', $oggi, $emailRichiedente, $idAnimale);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if(!$result){
            return false;
        }

        $queryTransport = "INSERT INTO TRASPORTI (Email, IDanimale) VALUES (?, ?)";
        $stmtTransport = mysqli_prepare($this->connection, $queryTransport);
        if($stmtTransport === false){
            return false;
        }
        
        mysqli_stmt_bind_param($stmtTransport, 'si', $emailRichiedente, $idAnimale);
        $resultTransport = mysqli_stmt_execute($stmtTransport);
        mysqli_stmt_close($stmtTransport);

        return $resultTransport;
    }

    public function acceptRequest($emailRichiedente, $idAnimale): bool {
        if (!$this->connection){ //se la connessione non è aperta
            return false;
        }

        //la data di fine valutazione viene settata a oggi
        $query = "UPDATE RICHIESTE_ADOZIONI SET Stato = 'Accettata', DataFineValutazione = ? WHERE Email = ? AND IDanimale = ?";

        $stmt = mysqli_prepare($this->connection, $query);
        if($stmt === false){
            return false;
        }

        $oggi = date('Y-m-d');
        mysqli_stmt_bind_param($stmt, 'ssi', $oggi, $emailRichiedente, $idAnimale);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        if($result){
            return $this->setRespintaOtherRequestsForAnimal($idAnimale, $emailRichiedente);
        }else{
            return $result;
        }
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

    public function getConnectionError() {
    return $this->connection->error; 
    }

    public function addAnimal(array $data, ?string $email): ?int {
        $query = "INSERT INTO ANIMALI (
                    Nome, DataNascita, DataRegistrazione, Sesso, Tipo, 
                    Colore, Pelo, Taglia, Razza, DescrFamiglia, 
                    DescrComportamentale, CondizioniMediche, Trasporto, ImgPath, Email
                ) VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->connection->prepare($query);

        if ($stmt === false) {
            return false;
        }

        $stmt->bind_param(
            "sssssssssssiss",
            $data['nome'],
            $data['dataNascita'],
            $data['sesso'],
            $data['tipologia'],
            $data['colore'],
            $data['pelo'],
            $data['taglia'],
            $data['razza'],
            $data['famiglia'],
            $data['carattere'],
            $data['condMediche'],
            $data['trasporto'],
            $data['foto'],
            $email
        );
        $success = $stmt->execute();
        
        if ($success) {
            // Recupera l'ID autogenerato dall'ultima query INSERT
            $insertedId = (int)$this->connection->insert_id;
            $stmt->close();
            return $insertedId;
        } else {
            error_log("Errore inserimento animale: " . $stmt->error);
            $stmt->close();
            return false;
        }
    }

    public function getAnimalById($id) {
        $query = "SELECT 
                    IDanimale, Nome, DataNascita, Sesso, Tipo, Colore, 
                    Pelo, Taglia, Razza, DescrFamiglia, DescrComportamentale, 
                    CondizioniMediche, Trasporto, ImgPath, 
                    Email AS EmailAdmin 
                FROM ANIMALI WHERE IDanimale = ?";

        $stmt = $this->connection->prepare($query);
        if ($stmt === false) return null;

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        return $data;
    }

    public function getFotoAnimalById($id) {
        $query = "SELECT ImgPath FROM ANIMALI WHERE IDanimale = ?";
        $stmt = $this->connection->prepare($query);
        if ($stmt === false) return null;

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();

        return $data ? $data['ImgPath'] : null;
    }

    public function updateAnimal(array $data, $idanimale): bool {
        $query = "UPDATE ANIMALI SET 
                    Nome = ?, Razza = ?, Taglia = ?, DataNascita = ?, 
                    Pelo = ?, Colore = ?, DescrComportamentale = ?, 
                    CondizioniMediche = ?, DescrFamiglia = ?, 
                    ImgPath = ?, Trasporto = ? 
                WHERE IDanimale = ?";

        $stmt = $this->connection->prepare($query);
        if ($stmt === false) return false;

        $stmt->bind_param("ssssssssssii", 
            $data['nome'], $data['razza'], $data['taglia'], $data['dataNascita'],
            $data['pelo'], $data['colore'], $data['carattere'], 
            $data['condMediche'], $data['famiglia'], $data['foto'], 
            $data['trasporto'], $idanimale
        );

        $res = $stmt->execute();
        $stmt->close();
        return $res;
    }

    function createAdminTasks($email): array {
        $tasks = [0, 0, 0, 0, 0];

        $queries = [
            "query1" => ["sql" => "SELECT COUNT(*) AS totale FROM ANIMALI WHERE Email IS NULL", "param" => null],
            "query2" => ["sql" => "SELECT COUNT(*) AS totale FROM RICHIESTE_ADOZIONI R JOIN ANIMALI A ON R.IDanimale = A.IDanimale WHERE R.Stato = 'In valutazione' AND (R.Appunti IS NULL OR R.Appunti = '') AND A.Email = ?", "param" => $email],
            "query3" => [
                "sql" => "SELECT COUNT(*) AS totale FROM SEGNALAZIONI_NUOVE_ACCOGLIENZE WHERE EmailAmm IS NULL OR EmailAmm = ?", 
                "param" => $email
            ],
            "query4" => ["sql" => "SELECT COUNT(*) AS totale FROM RICHIESTE_ADOZIONI R JOIN ANIMALI A ON R.IDanimale = A.IDanimale WHERE R.Stato = 'Nuova' AND A.Email = ?", "param" => $email],
            "query5" => ["sql" => "SELECT COUNT(*) AS totale FROM RICHIESTE_ADOZIONI R JOIN ANIMALI A ON R.IDanimale = A.IDanimale LEFT JOIN TRASPORTI T ON (R.Email = T.Email AND R.IDanimale = T.IDanimale) WHERE R.Stato = 'Da trasportare' AND T.DataArrivo IS NULL AND A.Email = ?", "param" => $email]
        ];

        $index = 0;
        foreach ($queries as $q) {
            $stmt = mysqli_prepare($this->connection, $q['sql']);
            if ($stmt) {
                if ($q['param'] !== null) {
                    mysqli_stmt_bind_param($stmt, 's', $q['param']);
                }
                
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if ($row = mysqli_fetch_assoc($result)) {
                    $tasks[$index] = $row['totale'];
                }
                
                mysqli_stmt_close($stmt); // IMPORTANTE: Libera la risorsa
            }
            $index++;
        }

        return $tasks;
    }

    function createAdminStats($email): array {
        $stats = [0, 0, 0];
        $queries = [
            "query1" => ["sql" => "SELECT COUNT(*) AS totale FROM RICHIESTE_ADOZIONI R JOIN ANIMALI A ON R.IDanimale = A.IDanimale WHERE R.Stato = 'Accettata' AND A.Email = ?", "param" => $email],
            "query2" => ["sql" => "SELECT COUNT(*) AS totale FROM RICHIESTE_ADOZIONI R JOIN ANIMALI A ON R.IDanimale = A.IDanimale WHERE R.Stato <> 'Nuova' AND A.Email = ?", "param" => $email],
            "query3" => ["sql" => "SELECT COUNT(*) AS totale FROM RICHIESTE_ADOZIONI R JOIN ANIMALI A ON R.IDanimale = A.IDanimale WHERE R.Stato NOT IN ('Nuova', 'Conclusa', 'Annullata', 'Respinta') AND A.Email = ?", "param" => $email]
        ];
        $index = 0;
        foreach ($queries as $q) {
            $stmt = mysqli_prepare($this->connection, $q['sql']);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 's', $q['param']);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                if ($row = mysqli_fetch_assoc($result)) {
                    $stats[$index] = $row['totale'];
                }
                
                mysqli_stmt_close($stmt);
            }
            $index++;
        }
        return $stats;
    }

    // Recupera le informazioni di un amministratore data l'email
    function findAdminByEmail(string $email): array|null {
        if (!$this->connection){
            return null;
        }

        $query = "SELECT * FROM UTENTI WHERE Email = ?";
        $stmt = mysqli_prepare($this->connection, $query);
        if($stmt === false){
            return null;
        }
        mysqli_stmt_bind_param($stmt, 's', $email);
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
        return [
            'email' => $row['Email'],
            'nome' => $row['Nome'],
            'cognome' => $row['Cognome'],
            'telefono' => $row['Telefono'],
            'imgPath' => $row['ImgPath']
        ];
    }

    function getNewRequests($email): array {
        $requests = [];
        
        $query = "SELECT R.Email AS email_richiedente, A.ImgPath, A.Nome AS nome_animale, R.DataRichiesta AS data_richiesta, R.IDanimale AS id_animale
                FROM RICHIESTE_ADOZIONI R
                JOIN ANIMALI A ON R.IDanimale = A.IDanimale
                WHERE R.Stato = 'Nuova' 
                AND A.Email = ?
                ORDER BY R.DataRichiesta DESC";

        $stmt = mysqli_prepare($this->connection, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $requests[] = $row;
            }
            
            mysqli_stmt_close($stmt);
        }
        
        return $requests;
    }

    /**
     * @param string $email Email dell'admin
     * @param string|null $filtroAppunti Valori attesi: '1' (Sì), '0' (No)
     */
    function getInEvaluationRequests($email, $filtroAppunti = null): array {
        $requests = [];
        
        $query = "SELECT 
                    A.Nome AS nome_animale, 
                    R.Email AS email_richiedente, 
                    R.DataInizioValutazione AS data_inizio_valutazione, 
                    R.Appunti AS appunti,
                    R.IDanimale AS id_animale
                FROM RICHIESTE_ADOZIONI R
                JOIN ANIMALI A ON R.IDanimale = A.IDanimale
                WHERE R.Stato = 'In valutazione' 
                AND A.Email = ?";

        if ($filtroAppunti === '1') {
            $query .= " AND R.Appunti IS NOT NULL AND R.Appunti <> ''";
        } elseif ($filtroAppunti === '0') {
            $query .= " AND (R.Appunti IS NULL OR R.Appunti = '')";
        }

        $query .= " ORDER BY R.DataInizioValutazione ASC";

        $stmt = mysqli_prepare($this->connection, $query);
        if ($stmt) {
            //il parametro bind è uno perché i filtri li aggiungo hardcoded nella query sopra!! 
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $requests[] = $row;
            }
            
            mysqli_stmt_close($stmt);
        }
        
        return $requests;
    }

    /**
     * @param string $email Email dell'admin
     * @param string|null $filtroData Valori attesi: '1' (ossia valorizzata), '0' (non valorizzata), null (prende tutte le richieste)
     */
    function getTransportRequests($email, $filtroData = null): array {
        $requests = [];
        
        $query = "SELECT 
                    A.Nome AS nome_animale, 
                    R.Email AS email_richiedente, 
                    R.DataFineValutazione AS data_fine_valutazione,
                    T.DataArrivo AS data_arrivo,
                    R.IDanimale AS id_animale
                FROM RICHIESTE_ADOZIONI R
                JOIN ANIMALI A ON R.IDanimale = A.IDanimale
                LEFT JOIN TRASPORTI T ON R.Email = T.Email AND R.IDanimale = T.IDanimale
                WHERE R.Stato = 'Da trasportare'
                AND A.Email = ?";

        if ($filtroData === '1') {
            $query .= " AND T.DataArrivo IS NOT NULL";
        } elseif ($filtroData === '0') {
            $query .= " AND T.DataArrivo IS NULL";
        }
        $query .=" ORDER BY (T.DataArrivo IS NULL) ASC, T.DataArrivo ASC"; //ordina prima quelle senza data, poi le altre in ordine crescente di data

        $stmt =mysqli_prepare($this->connection, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            
            $result= mysqli_stmt_get_result($stmt);
            
            while ($row=mysqli_fetch_assoc($result)) {
                if ($row['data_arrivo'] === null)
                    $row['data_arrivo'] = '';
                $requests[] = $row;
            }         
            mysqli_stmt_close($stmt);
        }
        
        return $requests;
    }
    function getNRequestByStatus($email): array {
        $counts = [
            'Nuova' => 0,
            'In valutazione' => 0,
            'Da trasportare' => 0,
            'Conclusa' => 0,
            'Annullata' => 0,
            'Respinta' => 0
        ];

        $query = "SELECT R.Stato, COUNT(*) AS totale
                FROM RICHIESTE_ADOZIONI R
                JOIN ANIMALI A ON R.IDanimale = A.IDanimale
                WHERE A.Email = ?
                GROUP BY R.Stato";
        $stmt = mysqli_prepare($this->connection, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $counts[$row['Stato']] = $row['totale'];
            }
            
            mysqli_stmt_close($stmt);
        }
        return $counts;
    }  

    function getNNonAdminByType(): array {
        $counts = [
            'Gatto' => 0,
            'Cane' => 0
        ];

        $query = "SELECT Tipo, COUNT(*) AS totale
                FROM ANIMALI
                WHERE Email IS NULL
                GROUP BY Tipo";

        $stmt = mysqli_prepare($this->connection, $query);

        if ($stmt) {
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            while ($row = mysqli_fetch_assoc($result)) {
                if (isset($counts[$row['Tipo']])) {
                    $counts[$row['Tipo']] = (int)$row['totale'];
                }
            }
            
            mysqli_stmt_close($stmt);
        }

        return $counts;
    }

    function getCancelledRequests($email): array {
        $requests = [];
        
        $query = "SELECT 
                    A.Nome AS nome_animale, 
                    R.Email AS email_richiedente, 
                    R.DataFineValutazione AS data_fine_valutazione,
                    R.IDanimale AS id_animale
                FROM RICHIESTE_ADOZIONI R
                JOIN ANIMALI A ON R.IDanimale = A.IDanimale
                WHERE R.Stato = 'Annullata'
                AND A.Email = ?
                ORDER BY R.DataFineValutazione DESC";
        $stmt = mysqli_prepare($this->connection, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            while ($row = mysqli_fetch_assoc($result)) {
                $requests[] = $row;
            }

            mysqli_stmt_close($stmt);
        }
        return $requests;
    }

    function getRejectedRequests($email): array {
        $requests = [];
        
        $query = "SELECT 
                    A.Nome AS nome_animale, 
                    R.Email AS email_richiedente, 
                    R.IDanimale AS id_animale
                FROM RICHIESTE_ADOZIONI R
                JOIN ANIMALI A ON R.IDanimale = A.IDanimale
                WHERE R.Stato = 'Respinta'
                AND A.Email = ?";
        $stmt = mysqli_prepare($this->connection, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            while ($row = mysqli_fetch_assoc($result)) {
                $requests[] = $row;
            }

            mysqli_stmt_close($stmt);
        }
        return $requests;
    }

    function getDetailsNonAdminAnimalsPaged(int $limit, int $offCani, int $offGatti): array {
        $results = ['Gatto' => [], 'Cane' => []];

        $query = "(SELECT *, IDanimale AS id_animale, Nome AS nome_animale, 
                    DataRegistrazione AS data_registrazione, Trasporto AS trasporto_animale, 
                    Razza AS razza_animale, DataNascita AS data_nascita
                    FROM ANIMALI WHERE Email IS NULL AND Tipo = 'Cane' 
                    LIMIT ? OFFSET ?)
                UNION ALL
                (SELECT *, IDanimale AS id_animale, Nome AS nome_animale, 
                    DataRegistrazione AS data_registrazione, Trasporto AS trasporto_animale, 
                    Razza AS razza_animale, DataNascita AS data_nascita
                    FROM ANIMALI WHERE Email IS NULL AND Tipo = 'Gatto' 
                    LIMIT ? OFFSET ?)";

        $stmt = mysqli_prepare($this->connection, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "iiii", $limit, $offCani, $limit, $offGatti);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);

            while ($row = mysqli_fetch_assoc($res)) {
                $tipo = $row['Tipo'];
                if (isset($results[$tipo])) {
                    $results[$tipo][] = $row;
                }
            }
        }

        return $results;
    }

    public function assignAnimalToAdmin(int $idAnimale, string $emailAdmin): bool {
        $query = "UPDATE ANIMALI 
                SET Email = ? 
                WHERE IDanimale = ? AND Email IS NULL";

        $stmt = mysqli_prepare($this->connection, $query);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $emailAdmin, $idAnimale);
            mysqli_stmt_execute($stmt);
            $success = mysqli_stmt_affected_rows($stmt) > 0;          
            mysqli_stmt_close($stmt);
            return $success;
        }
        
        return false;
    }

    // per vedere solo le segnalazioni proprie, si passa mode = 'mie' e l'email dell'admin
    // per vedere solo le segnalazioni senza admin, si passa mode = 'nessuno' e si può lasciare emailAdmin a null
    // per vedere tutte le segnalazioni, si passa mode = 'tutte' e l'email dell'admin
    function getDetailsSegnalazioniAnimalsPaged($perPagina, $offCani, $offGatti, ?string $emailAdmin = null, string $mode = 'tutte'): array {
        $results = ['Gatto' => [], 'Cane' => []];
        $config = ['Cane' => $offCani, 'Gatto' => $offGatti];

        foreach ($config as $tipo => $offset) {
            $query = "SELECT 
                        ID AS id_segnalazione, 
                        DataRichiesta AS data_segnalazione, 
                        NominativoRichiedente AS nominativo_segnalante, 
                        EmailRichiedente AS email_segnalante,
                        EmailAmm AS email_admin
                    FROM SEGNALAZIONI_NUOVE_ACCOGLIENZE 
                    WHERE TipoAnimale = ? ";

            if ($mode === 'nessuno') {
                $query .= "AND EmailAmm IS NULL ";
            } elseif ($mode === 'mie') {
                $query .= "AND EmailAmm = ? ";
            } else {
                $query .= "AND (EmailAmm IS NULL OR EmailAmm = ?) ";
            }

            $query .= "ORDER BY DataRichiesta DESC LIMIT ? OFFSET ?";

            $stmt = mysqli_prepare($this->connection, $query);

            if ($stmt) {
                if ($mode === 'nessuno') {
                    mysqli_stmt_bind_param($stmt, 'sii', $tipo, $perPagina, $offset);
                } else {
                    mysqli_stmt_bind_param($stmt, 'ssii', $tipo, $emailAdmin, $perPagina, $offset);
                }

                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);

                while ($row = mysqli_fetch_assoc($result)) {
                    $results[$tipo][] = $row;
                }
                mysqli_stmt_close($stmt);
            }
        }

        return $results;
    }

    public function getAdoptedAnimalsPaged(int $limit, int $offCani, int $offGatti, string $filtro, ?string $myEmail = null): array {
        $results = ['Cane' => [], 'Gatto' => []];
        $tipi = ['Cane', 'Gatto'];

        foreach ($tipi as $tipo) {
            $offset = ($tipo === 'Cane') ? $offCani : $offGatti;
            
            $filterQuery = "";
            if ($filtro === 'mie') {
                $filterQuery = " AND A.Email = ? ";
            } elseif ($filtro === 'non-mie') {
                $filterQuery = " AND A.Email <> ? OR A.Email IS NULL "; //questo OR serve per includere anche gli animali adottati che non hano più admin (es admin viene elimimato, la segnalazione rimane) TODO in realtà è da controllare se è effettivamente così da db
            }

            $query = "SELECT 
                        A.IDanimale AS id_animale, 
                        A.Nome AS nome_animale, 
                        U_Adottante.Nome AS nome_adottante, 
                        U_Adottante.Cognome AS cognome_adottante, 
                        R.Email AS email_adottante, 
                        R.DataFineValutazione AS data_chiusura, 
                        U_Admin.Nome AS nome_admin, 
                        U_Admin.Cognome AS cognome_admin
                    FROM RICHIESTE_ADOZIONI R
                    JOIN ANIMALI A ON R.IDanimale = A.IDanimale
                    JOIN UTENTI U_Adottante ON R.Email = U_Adottante.Email
                    LEFT JOIN UTENTI U_Admin ON A.Email = U_Admin.Email
                    WHERE R.Stato = 'Accettata' 
                    AND A.Tipo = ? 
                    $filterQuery
                    ORDER BY R.DataFineValutazione DESC 
                    LIMIT ? OFFSET ?";

            $stmt = mysqli_prepare($this->connection, $query);
            
            if ($stmt) {
                if ($filtro === 'mie' || $filtro === 'non-mie') {
                    mysqli_stmt_bind_param($stmt, 'ssii', $tipo, $myEmail, $limit, $offset);
                } else {
                    mysqli_stmt_bind_param($stmt, 'sii', $tipo, $limit, $offset);
                }
                
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                while ($row = mysqli_fetch_assoc($res)) {
                    $results[$tipo][] = $row;
                }
                mysqli_stmt_close($stmt);
            }
        }
        return $results;
    }

    public function getNAdoptedAnimals(): array{
        $counts = [
            'Cane' => 0,
            'Gatto' => 0
        ];

        $query = "SELECT A.Tipo, COUNT(*) AS totale
                FROM RICHIESTE_ADOZIONI R
                JOIN ANIMALI A ON R.IDanimale = A.IDanimale
                WHERE R.Stato = 'Accettata'
                GROUP BY A.Tipo";

        $stmt = mysqli_prepare($this->connection, $query);
        if ($stmt) {
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);

            while ($row = mysqli_fetch_assoc($res)) {
                if (isset($counts[$row['Tipo']])) {
                    $counts[$row['Tipo']] = $row['totale'];
                }
            }
            mysqli_stmt_close($stmt);
        }

        return $counts;
    }

    public function assignAdminToSegnalazione(int $idSegnalazione, string $emailAdmin): bool {
        $query = "UPDATE SEGNALAZIONI_NUOVE_ACCOGLIENZE 
                SET EmailAmm = ? 
                WHERE ID = ? AND EmailAmm IS NULL";
                
        $stmt = mysqli_prepare($this->connection, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $emailAdmin, $idSegnalazione);
            $success = mysqli_stmt_execute($stmt);
            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);
            
            return $success && $affected > 0;
        }
        return false;
    }


    public function deleteSegnalazione(int $idSegnalazione): bool {
        $query = "DELETE FROM SEGNALAZIONI_NUOVE_ACCOGLIENZE WHERE ID = ?";
        
        $stmt = mysqli_prepare($this->connection, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $idSegnalazione);
            $success = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return $success;
        }
        return false;
    }
    
    public function getNSegnalazioni($email): array {
        $counts = [
            'Cane' => 0,
            'Gatto' => 0
        ];
        $query = "SELECT TipoAnimale, COUNT(*) AS totale 
                FROM SEGNALAZIONI_NUOVE_ACCOGLIENZE 
                WHERE EmailAmm = ? OR EmailAmm IS NULL 
                GROUP BY TipoAnimale";
    
        $stmt = mysqli_prepare($this->connection, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            
            while ($row = mysqli_fetch_assoc($res)) {
                if (isset($counts[$row['TipoAnimale']])) {
                    $counts[$row['TipoAnimale']] = $row['totale'];
                }
            }
            mysqli_stmt_close($stmt);
        }

        return $counts;
    }

    public function insertReportForm(string $name, string $email, string $animal): bool {
        if (!$this->connection){
            return false;
        }

        $query = "INSERT INTO SEGNALAZIONI_NUOVE_ACCOGLIENZE (NominativoRichiedente, EmailRichiedente, TipoAnimale) VALUES (?, ?, ?)";

        $stmt = mysqli_prepare($this->connection, $query);
        if($stmt === false){
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'sss', $name, $email, $animal);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }

    function checkEmailExists($email): bool {
        $requests = [];
        
        $query = "  SELECT COUNT(*)
                    FROM UTENTI U
                    WHERE U.Email = ?";
        $stmt = mysqli_prepare($this->connection, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);

            mysqli_stmt_bind_result($stmt, $totale);
            mysqli_stmt_fetch($stmt);

            mysqli_stmt_close($stmt);

            return $totale > 0;
        }
        return false;
    }

    public function insertNewUser(string $email, string $name, string  $surname, string  $hashedPassword): bool {
        if (!$this->connection){
            return false;
        }

        $query = "INSERT INTO UTENTI (Email, Nome, Cognome, Password, Ruolo) VALUES (?, ?, ?, ?, ?)";

        $stmt = mysqli_prepare($this->connection, $query);
        if($stmt === false){
            return false;
        }

        $role ='User';

        mysqli_stmt_bind_param($stmt, 'sssss', $email, $name, $surname, $hashedPassword, $role);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }

    function getUserInfo($email): ?array {
        $requests = [];
        
        $query = "SELECT 
                    Nome,
                    Cognome,
                    Password,
                    Telefono,
                    Via,
                    Citta,
                    CAP,
                    ImgPath
                FROM UTENTI WHERE Email = ?";
        $stmt = mysqli_prepare($this->connection, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if($row = mysqli_fetch_assoc($result)) {
                mysqli_stmt_close($stmt);
                return $row;
            }
            mysqli_stmt_close($stmt);
        }
        return null;
    }

    //ordine le richieste in base a quella che ha avuto un movimento più recente, che sia in DataRichieste,
    //inizioValutazione o FineValutazione
    function getUserRequests($email, $filtro): array {
        $requests = [];
        
        $query = "SELECT 
                    R.IDanimale,            
                    R.Stato,
                    T.DataPartenza,         
                    T.DataArrivo,
                    A.Nome AS NomeAnimale
                FROM RICHIESTE_ADOZIONI R
                JOIN ANIMALI A ON R.IDanimale = A.IDanimale
                LEFT JOIN TRASPORTI T ON R.IDanimale = T.IDanimale 
                WHERE R.Email = ? AND R.Stato IN ($filtro)
                ORDER BY GREATEST(
                R.DataRichiesta, 
                COALESCE(R.DataInizioValutazione, R.DataRichiesta), 
                COALESCE(R.DataFineValutazione, R.DataRichiesta)
                ) DESC";

        $stmt = mysqli_prepare($this->connection, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            while ($row = mysqli_fetch_assoc($result)) {
                $requests[] = $row;
            }
            
            mysqli_stmt_close($stmt);
        }
        return $requests;
    }

    function getAddressPermissionEdit($email): bool {
        
        $query = "SELECT count(*) 
                FROM RICHIESTE_ADOZIONI 
                WHERE Email = ? 
                AND Stato = 'Da trasportare'"; 

        $stmt = mysqli_prepare($this->connection, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_array($result)) {
                $count = $row[0];

                return $count>0 ? false : true;

            } 
            mysqli_stmt_close($stmt);
        }

        return true;
    }

    function getAddressPermissionRemove($email): bool {
        
        $query = "SELECT count(*) 
                FROM RICHIESTE_ADOZIONI 
                WHERE Email = ? 
                AND Stato IN ('Nuova', 'In valutazione') AND Trasporto = 1"; 

        $stmt = mysqli_prepare($this->connection, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_array($result)) {
                $count = $row[0];

                return $count>0 ? false : true;

            } 
            mysqli_stmt_close($stmt);
        }

        return true;
    }

    public function getRole($email): ?string {
        
        $query = "SELECT Ruolo FROM UTENTI WHERE Email = ?";
        $stmt = mysqli_prepare($this->connection, $query);

        $role = null;

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($result)) {
                $role = $row['Ruolo'];
            }
            mysqli_stmt_close($stmt);
        }
        return $role;
    }

    public function updateUserInfo(string $email, array $newUserInfo): bool {
        if (!$this->connection){
            return false;
        }

        $query = "UPDATE UTENTI SET Nome = ?, Cognome = ?, 
        Telefono = ?, Via = ?, Citta = ?, CAP = ?, ImgPath = ? WHERE Email = ?";

        $stmt = mysqli_prepare($this->connection, $query);
        if($stmt === false){
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'ssssssss',  $newUserInfo['name'], $newUserInfo['surname'], 
        $newUserInfo['phoneNumber'], $newUserInfo['address'], $newUserInfo['city'], 
        $newUserInfo['CAP'], $newUserInfo['profilePic'], $email);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }

    public function updateUserManagement(string $email, array $newUserInfo): bool {
        if (!$this->connection){
            return false;
        }

        $query = "UPDATE UTENTI SET Email = ?, Password = ? WHERE Email = ?";

        $stmt = mysqli_prepare($this->connection, $query);
        if($stmt === false){
            return false;
        }

        mysqli_stmt_bind_param($stmt, 'sss', $newUserInfo['email'], $newUserInfo['Newpassword'], $email);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }

    function getCredentials($email): array {
        $row = [];
        $query = "SELECT Email, Password FROM UTENTI WHERE Email = ?";

        $stmt = mysqli_prepare($this->connection, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            $row = mysqli_fetch_assoc($result) ?? [];

            mysqli_stmt_close($stmt);
        }

        return $row;
    }

    public function getAnimalRequest($email, $idAnimale): ?array {
        
        $request = null; 
        
        $query = "SELECT 
                    R.IDanimale,            
                    R.LetteraPresentazione, 
                    R.Stato,
                    R.DataRichiesta,
                    R.DataInizioValutazione,
                    R.DataFineValutazione,
                    T.DataPartenza,         
                    T.DataArrivo,
                    R.Trasporto,
                    A.Nome AS NomeAnimale,
                    A.Sesso,
                    A.DataNascita,
                    A.Razza,
                    A.DescrFamiglia,
                    A.DescrComportamentale,
                    A.CondizioniMediche,
                    A.ImgPath,
                    A.Trasporto AS TrasportoAnimale
                FROM RICHIESTE_ADOZIONI R
                JOIN ANIMALI A ON R.IDanimale = A.IDanimale
                LEFT JOIN TRASPORTI T ON R.IDanimale = T.IDanimale 
                WHERE R.Email = ? AND R.IDanimale = ?";

        $stmt = mysqli_prepare($this->connection, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $email, $idAnimale);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $request = mysqli_fetch_assoc($result);
            
            mysqli_stmt_close($stmt);
        }
        return $request;
    }

    public function updateRequestState(string $email, int $idAnimale, string $stato): bool {

        $dataOggi = date("Y-m-d");
        // controllo se dataInizioValutazione esiste, se non esiste metto quella di oggi
        $query = "UPDATE RICHIESTE_ADOZIONI 
                SET Stato = ?, 
                    DataFineValutazione = ?
                WHERE Email = ? AND IDanimale = ?";

        $stmt = mysqli_prepare($this->connection, $query);
        
        if ($stmt === false) {
            return false;
        }

        mysqli_stmt_bind_param(
            $stmt, 'sssi', $stato, $dataOggi, $email, $idAnimale);

        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        return $result;
    }

    function getStateRequest(string $email, int $idAnimale): ?string {
        $state = null;
        
        $query = "SELECT          
                    Stato
                    FROM RICHIESTE_ADOZIONI 
                    WHERE Email = ? AND IDanimale = ?";

        $stmt = mysqli_prepare($this->connection, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'si', $email,$idAnimale);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            $row = mysqli_fetch_assoc($result);
            if($row){
                $state = $row['Stato'];
            }
            
            mysqli_stmt_close($stmt);
        }
        return $state;
    }

    public function countAnimalsFiltered(string $type, array $filters): int {

    if (!$this->connection) {
        return 0;
    }

    $where = 'WHERE R.IDanimale IS NULL';
    $params = [];
    $types = '';

    // filtri comuni (Tipo, nome, taglia, ecc.)
    $this->applyFilters($type, $filters, $where, $params, $types);

    $visibilityInTransport="IN ('Accettata', 'Da trasportare')";
        if(isset($filters['assegnati'])) {
            $visibilityInTransport="='Accettata'";
        }
    $query = "
        SELECT COUNT(DISTINCT A.IDanimale) AS totale
        FROM ANIMALI A
        LEFT JOIN RICHIESTE_ADOZIONI R
            ON A.IDanimale = R.IDanimale
            AND R.Stato $visibilityInTransport
        $where
    ";

    $stmt = mysqli_prepare($this->connection, $query);
    if ($stmt) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }

        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);
        mysqli_stmt_close($stmt);

        return (int)$row['totale'];
    }

    return 0;
}


    public function countAssignedAnimalsFiltered(string $type, array $filters, string $adminEmail): int {
        $where = "WHERE A.Email = ? AND R.IDanimale IS NULL";
        $params = [$adminEmail];
        $types = "s";

        $this->applyFilters($type, $filters, $where, $params, $types);

        $query = "SELECT COUNT(DISTINCT A.IDanimale) AS totale 
                FROM ANIMALI A 
                LEFT JOIN RICHIESTE_ADOZIONI R ON A.IDanimale = R.IDanimale AND R.Stato = 'Accettata' 
                $where";
                    
        $stmt = mysqli_prepare($this->connection, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);
            return (int)$row['totale'];
        }
        return 0;
    }
    public function getAssignedAnimalsFilteredPaged(string $type, array $filters, int $limit, int $offset, string $adminEmail): array {
        $results = [];
        $where = "WHERE A.Email = ? AND R.IDanimale IS NULL";
        $params = [$adminEmail];
        $types = "s";

        $this->applyFilters($type, $filters, $where, $params, $types);

        $query = "SELECT 
                    A.IDanimale AS id, 
                    A.Nome AS nome, 
                    A.Colore AS colore, 
                    A.Sesso AS sesso, 
                    A.Tipo AS tipo,
                    A.ImgPath AS immagine,
                    A.DataNascita AS eta 
                FROM ANIMALI A 
                LEFT JOIN RICHIESTE_ADOZIONI R ON A.IDanimale = R.IDanimale AND R.Stato = 'Accettata'
                $where 
                ORDER BY A.DataRegistrazione DESC 
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = mysqli_prepare($this->connection, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $animali = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $animali[] = [
                'nome'     => $row['nome'],
                'sesso'    => $row['sesso'],
                'colore'    => $row['colore'],
                'eta'      => calcolaEta($row['eta']),
                'immagine' => $row['immagine'],
                'tipo'     => $row['tipo'],
                'id'     => $row['id']
            ];
        }
            mysqli_stmt_close($stmt);
        }
        return $animali;
    }


    private function applyFilters(string $type, array $filters, string &$where, array &$params, string &$types): void {
        if ($type !== 'tutti') {
            $where .= " AND A.Tipo = ? ";
            $params[] = $type;
            $types .= "s";
        }

        if (!empty($filters['name-animal'])) {
            $where .= " AND A.Nome LIKE ? ";
            $params[] = "%" . $filters['name-animal'] . "%";
            $types .= "s";
        }

        if (!empty($filters['taglia'])) {
            $where .= " AND A.Taglia = ? ";
            $params[] = $filters['taglia'];
            $types .= "s";
        }

        if (!empty($filters['sesso'])) {
            $where .= " AND A.Sesso = ? ";
            $params[] = strtoupper(substr($filters['sesso'], 0, 1)); 
            $types .= "s";
        }

        if (!empty($filters['eta_min'])) {
            $where .= " AND TIMESTAMPDIFF(YEAR, A.DataNascita, CURDATE()) >= ? ";
            $params[] = (int)$filters['eta_min'];
            $types .= "i";
        }
        if (!empty($filters['eta_max'])) {
            $where .= " AND TIMESTAMPDIFF(YEAR, A.DataNascita, CURDATE()) <= ? ";
            $params[] = (int)$filters['eta_max'];
            $types .= "i";
        }
    }
    
    public function getAnimalsFilteredPaged(string $type, array $filters, int $limit, int $offset): array {

        if (!$this->connection) return [];

        $where = [];
        $params = [];
        $types = '';

        /* ---------- FILTRO TIPO ---------- */
        if ($type !== 'tutti') {
            $where[] = 'Tipo = ?';
            $params[] = $type;
            $types .= 's';
        }

        /* ---------- FILTRO NOME ---------- */
        if (!empty($filters['name-animal'])) {
            $where[] = 'Nome LIKE ?';
            $params[] = '%' . $filters['name-animal'] . '%';
            $types .= 's';
        }

        /* ---------- FILTRO TAGLIA ---------- */
        if (!empty($filters['taglia'])) {
            $where[] = 'Taglia = ?';
            $params[] = $filters['taglia'];
            $types .= 's';
        }

        /* ---------- FILTRO SESSO ---------- */
        if (!empty($filters['sesso'])) {
            $where[] = 'Sesso = ?';
            $params[] = strtoupper(substr($filters['sesso'], 0, 1)); // M / F
            $types .= 's';
        }

        /* ---------- FILTRO ETÀ ---------- */
        if (!empty($filters['eta_min'])) {
            $where[] = 'TIMESTAMPDIFF(YEAR, DataNascita, CURDATE()) >= ?';
            $params[] = (int)$filters['eta_min'];
            $types .= 'i';
        }

        if (!empty($filters['eta_max'])) {
            $where[] = 'TIMESTAMPDIFF(YEAR, DataNascita, CURDATE()) <= ?';
            $params[] = (int)$filters['eta_max'];
            $types .= 'i';
        }
        $visibilityInTransport="IN ('Accettata', 'Da trasportare')";
        if(isset($filters['assegnati'])) {
            $visibilityInTransport="='Accettata'";
        }

        $query = "
            SELECT a.Nome, a.Sesso, a.DataNascita, a.ImgPath, a.Tipo, a.Colore, a.IDanimale AS Id
            FROM ANIMALI a
            LEFT JOIN RICHIESTE_ADOZIONI r 
            ON a.IDanimale = r.IDanimale AND r.Stato $visibilityInTransport
            WHERE r.IDanimale IS NULL
        ";

        if ($where) {
            $query .= ' AND ' . implode(' AND ', $where);
        }

        $query .= " ORDER BY Id ASC LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = mysqli_prepare($this->connection, $query);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);

        $res = mysqli_stmt_get_result($stmt);
        if (!$res) return [];

        $animali = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $animali[] = [
                'nome'     => $row['Nome'],
                'sesso'    => $row['Sesso'],
                'colore'    => $row['Colore'],
                'eta'      => calcolaEta($row['DataNascita']),
                'immagine' => $row['ImgPath'],
                'tipo'     => $row['Tipo'],
                'id'     => $row['Id']
            ];
        }

        mysqli_stmt_close($stmt);
        return $animali;
    }

    function isAnimalInFavorites(string $email, int $id): bool {
        $stmt = $this->connection->prepare("SELECT 1 FROM PREFERITI WHERE Email=? AND IDanimale=?");
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    function addToFavorites(string $email, int $id): void {
        $stmt = $this->connection->prepare("INSERT IGNORE INTO PREFERITI (Email, IDanimale) VALUES (?, ?)");
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
    }

    function removeFromFavorites(string $email, int $id): void {
        $stmt = $this->connection->prepare("DELETE FROM PREFERITI WHERE Email=? AND IDanimale=?");
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
    }

    public function hasActiveAdoptionRequest(int $idAnimale): bool {
        $sql = "SELECT 1 FROM RICHIESTE_ADOZIONI 
                WHERE IDanimale = ? AND Stato IN ('Nuova', 'In valutazione', 'Da trasportare') 
                LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param('i', $idAnimale);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    public function isAnimalAdopted(int $idAnimale): bool {
        $sql = "SELECT 1 FROM RICHIESTE_ADOZIONI 
                WHERE IDanimale = ? AND Stato IN ('Accettata')
                LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param('i', $idAnimale);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return $exists;
    }


    function getNRequestByStatusUser($email): array {
        $counts = [
            'Nuova' => 0,
            'In valutazione' => 0,
            'Da trasportare' => 0,
            'Accettata' => 0,
            'Annullata' => 0,
            'Respinta' => 0
        ];

        $query = "SELECT Stato, COUNT(*) AS totale
                FROM RICHIESTE_ADOZIONI
                WHERE Email = ?
                GROUP BY Stato";
        $stmt = mysqli_prepare($this->connection, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 's', $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            while ($row = mysqli_fetch_assoc($result)) {
                $counts[$row['Stato']] = $row['totale'];
            }
            
            mysqli_stmt_close($stmt);
        }
        return $counts;
    }  
    public function getEventsFilteredPaged(array $filters, int $limit, int $offset=0): array {

        if (!$this->connection) return [];

        $where = [];
        $params = [];
        $types = '';

        /* ---------- FILTRO TITOLO ---------- */
        if (!empty($filters['search'])) {
            $where[] = 'Titolo LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
            $types .= 's';
        }

        /* ---------- FILTRO DATA EVENTO (MIN) ---------- */
        if (!empty($filters['data_inizio'])) {
            $where[] = 'DataEvento >= ?';
            $params[] = $filters['data_inizio'];
            $types .= 's';
        }

        /* ---------- FILTRO DATA EVENTO (MAX) ---------- */
        if (!empty($filters['data_fine'])) {
            $where[] = 'DataEvento <= ?';
            $params[] = $filters['data_fine'];
            $types .= 's';
        }

        /* ---------- FILTRO CITTÀ ---------- */
        if (!empty($filters['citta'])) {
            $where[] = 'Citta = ?';
            $params[] = $filters['citta'];
            $types .= 's';
        }

        // ---------- FILTRO TITOLO NO VISUALIZZARE ---------
        if (!empty($filters['nomeNO'])) {
            $where[] = 'Titolo != ?';
            $params[] = $filters['nomeNO'];
            $types .= 's';
        }
        /* ---------- FILTRO TIPO (prossimi / terminati) ---------- */
        if (!empty($filters['tipo'])) {
            $today = date('Y-m-d');

            if ($filters['tipo'] === 'prossimi') {
                $where[] = 'DataEvento >= ?';
                $params[] = $today;
                $types .= 's';
            } elseif ($filters['tipo'] === 'terminati') {
                $where[] = 'DataEvento < ?';
                $params[] = $today;
                $types .= 's';
            }
        }

        $query = "
            SELECT 
                Titolo,
                DataEvento,
                DescrEvento,
                ImgPath,
                Via,
                Citta
            FROM EVENTI
            WHERE 1=1
        ";

        if ($where) {
            $query .= ' AND ' . implode(' AND ', $where);
        }

        $query .= " ORDER BY DataEvento ASC LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = mysqli_prepare($this->connection, $query);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);

        $res = mysqli_stmt_get_result($stmt);
        if (!$res) return [];

        $eventi = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $eventi[] = [
                'titolo'      => $row['Titolo'],
                'data_evento' => $row['DataEvento'],
                'descrizione' => $row['DescrEvento'],
                'immagine'    => $row['ImgPath'],
                'via'         => $row['Via'],
                'citta'       => $row['Citta']
            ];
        }

        mysqli_stmt_close($stmt);
        return $eventi;
    }

    public function countEventsFiltered(array $filters): int {

        if (!$this->connection) return 0;

        $where = [];
        $params = [];
        $types = '';

        /* ---------- FILTRO TITOLO ---------- */
        if (!empty($filters['search'])) {
            $where[] = 'Titolo LIKE ?';
            $params[] = '%' . $filters['search'] . '%';
            $types .= 's';
        }

        /* ---------- FILTRO DATA EVENTO (MIN) ---------- */
        if (!empty($filters['data_inizio'])) {
            $where[] = 'DataEvento >= ?';
            $params[] = $filters['data_inizio'];
            $types .= 's';
        }

        /* ---------- FILTRO DATA EVENTO (MAX) ---------- */
        if (!empty($filters['data_fine'])) {
            $where[] = 'DataEvento <= ?';
            $params[] = $filters['data_fine'];
            $types .= 's';
        }

        /* ---------- FILTRO CITTÀ ---------- */
        if (!empty($filters['citta'])) {
            $where[] = 'Citta = ?';
            $params[] = $filters['citta'];
            $types .= 's';
        }

        /* ---------- FILTRO TIPO (prossimi / terminati) ---------- */
        if (!empty($filters['tipo'])) {
            $today = date('Y-m-d');

            if ($filters['tipo'] === 'prossimi') {
                $where[] = 'DataEvento >= ?';
                $params[] = $today;
                $types .= 's';
            } elseif ($filters['tipo'] === 'terminati') {
                $where[] = 'DataEvento < ?';
                $params[] = $today;
                $types .= 's';
            }
        }

        $query = "SELECT COUNT(*) AS totale FROM EVENTI";

        if ($where) {
            $query .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = mysqli_prepare($this->connection, $query);

        if ($params) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }

        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        mysqli_stmt_close($stmt);
        return (int)$row['totale'];
    }

    public function countFavourites(string $type, string $email): int {
        if (!$this->connection) return 0;

        $params = [$email];
        $types  = 's';

        $where = "
            p.Email = ?
        ";

        if ($type !== 'tutti') {
            $where .= ' AND a.Tipo = ?';
            $params[] = $type;
            $types   .= 's';
        }

        $query = "
            SELECT COUNT(*) AS totale
            FROM PREFERITI p
            INNER JOIN ANIMALI a ON p.IDanimale = a.IDanimale
            WHERE $where
        ";

        $stmt = mysqli_prepare($this->connection, $query);
        if (!$stmt) {
            return 0;
        }

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);

        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        mysqli_stmt_close($stmt);

        return (int)($row['totale'] ?? 0);
    }
    public function countGuestFavourites(string $type): int {
        if (!$this->connection) return 0;

        $guestFavs = getGuestFavorites();
        if (empty($guestFavs)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($guestFavs), '?'));
        $params = $guestFavs;
        $types  = str_repeat('i', count($guestFavs));

        $where = "
            a.IDanimale IN ($placeholders)
        ";

        if ($type !== 'tutti') {
            $where .= ' AND a.Tipo = ?';
            $params[] = $type;
            $types   .= 's';
        }

        $query = "
            SELECT COUNT(*) AS totale
            FROM ANIMALI a
            WHERE $where
        ";

        $stmt = mysqli_prepare($this->connection, $query);
        if (!$stmt) {
            return 0;
        }

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);

        $res = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($res);

        mysqli_stmt_close($stmt);

        return (int)($row['totale'] ?? 0);
    }

    public function getFavouritesPaged(
        string $type,
        int $perPagina,
        int $offset,
        string $email
    ): array {
        if (!$this->connection) return [];

        $params = [$email];
        $types  = 's';
        $where = 'p.Email = ?';

        if ($type !== 'tutti') {
            $where .= ' AND a.Tipo = ?';
            $params[] = $type;
            $types   .= 's';
        }

        $query = "
            SELECT
                a.Nome,
                a.Sesso,
                a.Colore,
                a.DataNascita,
                a.ImgPath,
                a.Tipo,
                a.IDanimale AS Id,
                CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM RICHIESTE_ADOZIONI r
                        WHERE r.IDanimale = a.IDanimale
                        AND r.Stato IN ('Accettata', 'Da trasportare')
                    ) THEN 1
                    ELSE 0
                END AS adottato
            FROM PREFERITI p
            INNER JOIN ANIMALI a ON p.IDanimale = a.IDanimale
            WHERE $where
            ORDER BY a.IDanimale ASC
            LIMIT ? OFFSET ?
        ";

        $params[] = $perPagina;
        $params[] = $offset;
        $types   .= 'ii';

        $stmt = mysqli_prepare($this->connection, $query);
        if (!$stmt) return [];

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);

        $res = mysqli_stmt_get_result($stmt);
        if (!$res) return [];

        $animali = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $animali[] = [
                'nome'     => $row['Nome'],
                'sesso'    => $row['Sesso'],
                'eta'      => calcolaEta($row['DataNascita']),
                'immagine' => $row['ImgPath'],
                'tipo'     => $row['Tipo'],
                'id'       => $row['Id'],
                'colore'       => $row['Colore'],
                'adottato' => (int)$row['adottato']
            ];
        }

        mysqli_stmt_close($stmt);
        return $animali;
    }

    public function getGuestFavPaged(string $type, int $perPagina, int $offset): array
    {
        if (!$this->connection) return [];

        $guestFavs = getGuestFavorites();
        if (empty($guestFavs)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($guestFavs), '?'));
        $params = $guestFavs;
        $types  = str_repeat('i', count($guestFavs));
        $where = "a.IDanimale IN ($placeholders)";

        if ($type !== 'tutti') {
            $where .= " AND a.Tipo = ?";
            $params[] = $type;
            $types   .= 's';
        }

        $query = "
            SELECT 
                a.Nome,
                a.Sesso,
                a.DataNascita,
                a.ImgPath,
                a.Colore,
                a.Tipo,
                a.IDanimale AS Id,
                EXISTS (
                    SELECT 1
                    FROM RICHIESTE_ADOZIONI r
                    WHERE r.IDanimale = a.IDanimale
                    AND r.Stato = 'Accettata'
                ) AS adottato
            FROM ANIMALI a
            WHERE $where
            ORDER BY a.IDanimale ASC
            LIMIT ? OFFSET ?
        ";

        $params[] = $perPagina;
        $params[] = $offset;
        $types   .= 'ii';

        $stmt = mysqli_prepare($this->connection, $query);
        if (!$stmt) return [];

        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);

        $res = mysqli_stmt_get_result($stmt);
        if (!$res) return [];

        $animali = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $animali[] = [
                'nome'     => $row['Nome'],
                'sesso'    => $row['Sesso'], 
                'eta'      => calcolaEta($row['DataNascita']),
                'immagine' => $row['ImgPath'],
                'tipo'     => $row['Tipo'],
                'id'       => $row['Id'],
                'colore'       => $row['Colore'],
                'adottato' => (int)$row['adottato']
            ];
        }

        mysqli_stmt_close($stmt);
        return $animali;
    }

    public function insertNewEvent(array $EventValues): bool {
        if (!$this->connection){
            return false;
        }

        mysqli_begin_transaction($this->connection);

        try {
        $queryEvento = "INSERT INTO EVENTI (Titolo, DataEvento, DescrEvento, ImgPath, Via, Citta, DataPubblicazione) 
                        VALUES (?, ?, ?, ?, ?, ?, CURRENT_DATE())";
        
        $stmtEvento = mysqli_prepare($this->connection, $queryEvento);
        if ($stmtEvento === false) throw new \Exception("Errore prepare Eventi");

        mysqli_stmt_bind_param($stmtEvento, 'ssssss', 
            $EventValues['titolo'], 
            $EventValues['data'],
            $EventValues['descrizione'],
            $EventValues['foto'],
            $EventValues['via'],
            $EventValues['citta']
        );

        if (!mysqli_stmt_execute($stmtEvento)) throw new \Exception("Errore execute Eventi");
        mysqli_stmt_close($stmtEvento);

        $queryOrg = "INSERT INTO ORGANIZZAZIONE (Titolo, DataEvento, Email) VALUES (?, ?, ?)";
        
        $stmtOrg = mysqli_prepare($this->connection, $queryOrg);
        if ($stmtOrg === false) throw new \Exception("Errore prepare Organizzazione");

        mysqli_stmt_bind_param($stmtOrg, 'sss', 
            $EventValues['titolo'], 
            $EventValues['data'],
            $EventValues['email']
        );

        if (!mysqli_stmt_execute($stmtOrg)) throw new \Exception("Errore execute Organizzazione");
        mysqli_stmt_close($stmtOrg);

        mysqli_commit($this->connection);
        return true;

        } catch (\Exception $e) {
            // Se qualcosa fallisce, rollback
            mysqli_rollback($this->connection);
            return false;
        }
    }
    public function getRequestStatus(string $email, int $idAnimale): ?string {
        $sql = "
            SELECT Stato
            FROM RICHIESTE_ADOZIONI
            WHERE Email = ? AND IDanimale = ?
            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param('si', $email, $idAnimale);
        $stmt->execute();
        $result = $stmt->get_result();

        $stato = null;
        if ($row = $result->fetch_assoc()) {
            $stato = $row['Stato'];
        }

        $stmt->close();
        return $stato;
    }

    public function getAnimalArrivalDate($idAnimale): ?string {
        
        $dataArrivo = null;
        
        $query = "SELECT DataArrivo 
                FROM TRASPORTI 
                WHERE IDanimale = ?";

        $stmt = mysqli_prepare($this->connection, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $idAnimale);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($result)) {
                $dataArrivo = $row['DataArrivo'];
            }
            
            mysqli_stmt_close($stmt);
        }
        
        return $dataArrivo;
    }

    public function getAnimalDetails(int $idAnimale): ?array {
        $sql = "
            SELECT Nome, Sesso, DataNascita, ImgPath, Tipo, Colore, Pelo, Taglia, Razza,
                   DescrFamiglia, DescrComportamentale, CondizioniMediche, Trasporto
            FROM ANIMALI
            WHERE IDanimale = ?
            LIMIT 1
        ";

        $stmt = $this->connection->prepare($sql);
        $stmt->bind_param('i', $idAnimale);
        $stmt->execute();
        $result = $stmt->get_result();

        $animalDetails = null;
        if ($row = $result->fetch_assoc()) {
            $animalDetails = [
                'nome' => $row['Nome'],
                'sesso' => $row['Sesso'],
                'eta' => calcolaEta($row['DataNascita']),
                'imgPath' => $row['ImgPath'],
                'tipo' => $row['Tipo'],
                'colore' => $row['Colore'],
                'pelo' => $row['Pelo'],
                'taglia' => $row['Taglia'],
                'razza' => $row['Razza'],
                'descr_famiglia' => $row['DescrFamiglia'],
                'descr_comportamentale' => $row['DescrComportamentale'],
                'condizioni_mediche' => $row['CondizioniMediche'],
                'trasporto' => (bool)$row['Trasporto']
            ];
        }

        $stmt->close();
        return $animalDetails;
    }

    public function updateUserAddress($email, $datiIndirizzo) {     // per aggiornare solo l'indirizzo dell'utente

        if (!$this->connection) {
            return false;
        }
        $via = $datiIndirizzo['address'] ?? null;
        $citta = $datiIndirizzo['city'] ?? null;
        $cap = $datiIndirizzo['CAP'] ?? null;

        $query = "UPDATE UTENTI 
                  SET Via = ?, Citta = ?, CAP = ? 
                  WHERE Email = ?";

        $stmt = mysqli_prepare($this->connection, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssss", $via, $citta, $cap, $email);        // "ssss" sta per string (Via), string (Citta), string (CAP), string (Email)

            $risultato = mysqli_stmt_execute($stmt);
            
            mysqli_stmt_close($stmt);
            return $risultato;
        }

        return false; }

    
    public function updateEvent(array $EventValues, string $oldTitolo, string $oldData): bool {
        if (!$this->connection) {
            return false;
        }

        mysqli_begin_transaction($this->connection);

        try {
            $queryUpdate = "UPDATE EVENTI SET Titolo = ?, DataEvento = ?, DescrEvento = ?, ImgPath = ?, Via = ?, Citta = ?
                            WHERE Titolo = ? AND DataEvento = ?";

            $stmtUpdate = mysqli_prepare($this->connection, $queryUpdate);
            if ($stmtUpdate === false) throw new \Exception("Errore prepare Update");

            mysqli_stmt_bind_param($stmtUpdate, 'ssssssss', 
                $EventValues['titolo'], 
                $EventValues['data'],
                $EventValues['descrizione'],
                $EventValues['foto'],
                $EventValues['via'],
                $EventValues['citta'],
                $oldTitolo,
                $oldData
            );

            if (!mysqli_stmt_execute($stmtUpdate)) throw new \Exception("Errore execute Update");
            mysqli_stmt_close($stmtUpdate);

            $queryContrib = "INSERT IGNORE INTO ORGANIZZAZIONE (Titolo, DataEvento, Email) VALUES (?, ?, ?)";
            
            $stmtContrib = mysqli_prepare($this->connection, $queryContrib);
            if ($stmtContrib === false) throw new \Exception("Errore prepare Contributor");

            mysqli_stmt_bind_param($stmtContrib, 'sss', 
                $EventValues['titolo'], 
                $EventValues['data'],
                $EventValues['email']
            );

            if (!mysqli_stmt_execute($stmtContrib)) throw new \Exception("Errore execute Contributor");
            mysqli_stmt_close($stmtContrib);

            mysqli_commit($this->connection);
            return true;

        } catch (\Exception $e) {
            // rollback se qualcosa fallisce
            mysqli_rollback($this->connection);
            return false;
        }
    }

    public function insertAdoptionRequest($emailUtente, $idAnimale, $lettera, $trasporto) {
        if (!$this->connection) {
            return false;
        }

        $trasportoInt = $trasporto ? 1 : 0;
        $statoIniziale = 'Nuova';
        $dataOggi = date("Y-m-d");

        $query = "INSERT INTO RICHIESTE_ADOZIONI 
                  (Email, IDanimale, Trasporto, LetteraPresentazione, Stato, DataRichiesta) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($this->connection, $query);
        
        if ($stmt) {

            mysqli_stmt_bind_param($stmt, "siisss", $emailUtente, $idAnimale, $trasportoInt, $lettera, $statoIniziale, $dataOggi);
            

            $risultato = mysqli_stmt_execute($stmt);
            
            mysqli_stmt_close($stmt);
            return $risultato;
        }
        
        return false;
    }

    function checkEventExists(string $title, string $date): bool {
        $requests = [];
        
        $query = "  SELECT COUNT(*)
                    FROM EVENTI
                    WHERE Titolo = ? AND DataEvento = ?";
        $stmt = mysqli_prepare($this->connection, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ss', $title, $date);
            mysqli_stmt_execute($stmt);

            mysqli_stmt_bind_result($stmt, $totale);
            mysqli_stmt_fetch($stmt);

            mysqli_stmt_close($stmt);

            return $totale > 0;
        }
        return false;
    }

    public function getInfoEvent(string $titolo, string $data): ?array { 
        if (!$this->connection) {
            return null;
        }

        $query = "SELECT * FROM EVENTI WHERE Titolo = ? AND DataEvento = ?";

        $stmt = mysqli_prepare($this->connection, $query);
        
        $evento = null;

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ss', $titolo, $data);

            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $evento = mysqli_fetch_assoc($res); 

            mysqli_stmt_close($stmt);
        }

        return $evento;
    }

    public function getImgEvent(string $titolo, string $data): ?string { 
        if (!$this->connection) {
            return null;
        }

        $query = "SELECT ImgPath FROM EVENTI WHERE Titolo = ? AND DataEvento = ?";
        $stmt = mysqli_prepare($this->connection, $query);
        
        $imgPath = null;

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ss', $titolo, $data);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            
            $evento = mysqli_fetch_assoc($res); 
            
            if ($evento) {
                $imgPath = $evento['ImgPath'];
            }

            mysqli_stmt_close($stmt);
        }

        return $imgPath;
    }

    // Recupera il conteggio delle richieste per ogni stato per un SINGOLO ANIMALE
    public function getNRequestByStatusAnimal($idAnimale): array {
        $stati = ['Nuova', 'In valutazione', 'Accettata', 'Respinta', 'Annullata', 'Da trasportare'];
        $risultati = array_fill_keys($stati, 0);
        
        $query = "SELECT Stato, COUNT(*) as totale FROM RICHIESTE_ADOZIONI WHERE IDanimale = ? GROUP BY Stato";
        $stmt = mysqli_prepare($this->connection, $query);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $idAnimale);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($res)) {
                $risultati[$row['Stato']] = $row['totale'];
            }
            mysqli_stmt_close($stmt);
        }
        return $risultati;
    }

    //recupera la richiesta Accettata per un SINGOLO ANIMALE
    public function getAcceptRequestByAnimal($idAnimale,$emailRichiedente): ?array {
        $query = "SELECT ra.*, u.Nome as nome_richiedente, u.Cognome as cognome_richiedente, a.Nome AS nome_animale 
                FROM RICHIESTE_ADOZIONI ra 
                JOIN UTENTI u ON ra.Email = u.Email 
                JOIN ANIMALI a ON ra.IDanimale = a.IDanimale
                WHERE ra.IDanimale = ? AND ra.Stato = 'Accettata' AND ra.Email <> ?
                ORDER BY ra.DataRichiesta DESC
                LIMIT 1";
        $stmt = mysqli_prepare($this->connection, $query);
        $data = null;   
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'is', $idAnimale, $emailRichiedente);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($res)) {
                $data = $row;
            }
            mysqli_stmt_close($stmt);
        }
        return $data;
    }

    // Recupera l'elenco delle richieste per un SINGOLO ANIMALE
    public function getAnimalRequestsId($idAnimale): array {
        $query = "SELECT ra.*, u.Nome, u.Cognome, a.Nome AS NomeAnimale 
                FROM RICHIESTE_ADOZIONI ra 
                JOIN UTENTI u ON ra.Email = u.Email 
                JOIN ANIMALI a ON ra.IDanimale = a.IDanimale
                WHERE ra.IDanimale = ?
                ORDER BY ra.DataRichiesta DESC";
                
        $stmt = mysqli_prepare($this->connection, $query);
        $data = [];
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $idAnimale);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            while ($row = mysqli_fetch_assoc($res)) {
                $data[] = $row;
            }
            mysqli_stmt_close($stmt);
        }
        return $data;
    }

    public function deleteAccount(string $email): bool {
        if (!$this->connection){
            return false;
        }

        $query = "DELETE FROM UTENTI WHERE Email = ?";

        $stmt = mysqli_prepare($this->connection, $query);
        if($stmt === false){
            return false;
        }

        mysqli_stmt_bind_param($stmt, 's', $email);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }

    public function deleteAnimal(int $idAnimale): bool {
        if (!$this->connection) return false;

        // Eliminiamo prima le richieste associate (se presenti)
        $queryRichieste = "DELETE FROM RICHIESTE WHERE IDanimale = ?";
        $stmtR = mysqli_prepare($this->connection, $queryRichieste);
        if ($stmtR) {
            mysqli_stmt_bind_param($stmtR, 'i', $idAnimale);
            mysqli_stmt_execute($stmtR);
            mysqli_stmt_close($stmtR);
        }

        // poi possiamo eliminare l'animale in sicurezza
        $queryAnimale = "DELETE FROM ANIMALI WHERE IDanimale = ?";
        $stmtA = mysqli_prepare($this->connection, $queryAnimale);
        if ($stmtA) {
            mysqli_stmt_bind_param($stmtA, 'i', $idAnimale);
            $res = mysqli_stmt_execute($stmtA);
            mysqli_stmt_close($stmtA);
            return $res;
        }

        return false;
    }

    public function getOrganizzatoriEvento(string $titoloEvento, string $dataEvento): array {
        $query = "SELECT u.Nome, u.Cognome, u.Email, e.Titolo, e.DataEvento, u.ImgPath
                FROM ORGANIZZAZIONE o
                JOIN UTENTI u ON o.Email = u.Email
                JOIN EVENTI e 
                    ON o.Titolo = e.Titolo 
                AND o.DataEvento = e.DataEvento
                WHERE o.Titolo = ?
                    AND o.DataEvento = ?";

        $stmt = mysqli_prepare($this->connection, $query);
        $data = [];

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ss', $titoloEvento, $dataEvento);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);

            while ($row = mysqli_fetch_assoc($res)) {
                $data[] = $row;
            }

            mysqli_stmt_close($stmt);
        }

        return $data;
    }

    public function removeAdminAssignment($idAnimale) {
        $query = "UPDATE ANIMALI SET Email = NULL WHERE IDanimale = ?";
        $stmt = $this->connection->prepare($query);
        if ($stmt === false) return false;

        $stmt->bind_param("i", $idAnimale);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function deleteEvent(string $titolo, string $dataEvento): bool {
        if (!$this->connection) return false;

        $query = "DELETE FROM EVENTI WHERE Titolo = ? AND DataEvento = ?";
        $stmt = mysqli_prepare($this->connection, $query);

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'ss', $titolo, $dataEvento);
            $res = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return $res;
        }

        return false;
    }

}

?>