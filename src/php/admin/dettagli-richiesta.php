<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) { //il primo controlla se esiste la variabile admin in session, la seconda controlla che sia affettivamente admin
    header("Location: ./accedi");
    exit;
}



function displayDateItalianFormat(string $dateStr): string {
    $timestamp = strtotime($dateStr);
    if ($timestamp === false) {
        return '';
    }
    return date('d/m/Y', $timestamp);
}
/**
 * Escape stringa per output HTML serve a prevenire XSS ossia Cross Site Scripting ossia l'inserimento di codice malevolo in pagine web visualizzate da altri utenti
 */
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Ritorna "Sì" o "No" in base a valore booleano/intero
 */
function siNo($val): string {
    return ($val === 1 || $val === '1' || $val === true) ? 'Sì' : 'No';
}

/** DA TOGLIERE, NON NECESSARIO TODO
 * Genera gli input nascosti usati nei form (id_animale + email_richiedente)
 */
function hiddenInputsFrom(array $r): string {
    $id = e($r['id-animale'] ?? '');
    $email = e($r['email-richiedente'] ?? '');
    return '<input type="hidden" name="id_animale" value="' . $id . '">
            <input type="hidden" name="email_richiedente" value="' . $email . '">';
}

/**
 * Renderizza il blocco "scarta/apri richiesta"
 */
function renderRejectRequest(array $r): string {
    if (($r['stato'] ?? '') !== 'Respinta' && ($r['stato'] ?? '') !== 'Annullata') {
        return '<form method="post">' .
            hiddenInputsFrom($r) .
            '<button type="submit" name="scarta_richiesta" class="orange-button">Scarta richiesta</button>
        </form>';
    }
    return '<form method="post">' .
        hiddenInputsFrom($r) .
        '<button type="submit" name="apri_richiesta" class="orange-button">Apri richiesta</button>
    </form>';
}

/**
 * Renderizza i pulsanti di azione in base allo stato della richiesta
 */
function renderPulsantiAzioni(array $r): string {
    $stato = $r['stato'] ?? '';
    $html = '';
    if ($stato === 'Da trasportare') {
        $subject = rawurlencode('Richiesta informazioni per adozione di ' . ($r['nome-animale'] ?? ''));
        $html = '<a href="mailto:' . ($r['email-richiedente'] ?? '') . '?subject=' . $subject . '" class="brown-button" target="_blank">Contatta candidato</a>';
    } elseif ($stato === 'Nuova') {
        $html = '<form method="post">' .
            hiddenInputsFrom($r) .
            '<button type="submit" name="inizia_valutazione" class="orange-button">Inizia valutazione</button>
        </form>';
    } elseif ($stato === 'In valutazione') {
        $html = '<form method="post">' .
            hiddenInputsFrom($r) .
            '<button type="submit" name="accetta_richiesta" class="orange-button">Accetta richiesta</button>
        </form>';
    }
    return $html;
}

/**
 * Costruisce le stringhe relative a date di valutazione / rifiuto
 */
function buildDateInfo(array $r): array {
    $dataInizio = '';
    $dataFine = '';
    $dataRifiuto = '';
    if (($r['stato'] ?? '') === 'Da trasportare') {
        $dataFine = '<dt>Data fine valutazione</dt><dd><time datetime="' . ($r['data_fine_valutazione'] ?? '') . '" >' .displayDateItalianFormat($r['data_fine_valutazione'] ?? ''). '</time></dd>';
        $dataInizio = '<dt>Data inizio valutazione</dt><dd><time datetime="' . ($r['data_inizio_valutazione'] ?? '') . '">' . displayDateItalianFormat($r['data_inizio_valutazione'] ?? '') . '</time></dd>';
    }
	if(($r['stato'] ?? '') === 'In valutazione') {
		$dataInizio = '<dt>Data inizio valutazione</dt><dd><time datetime="' . ($r['data_inizio_valutazione'] ?? '') . '">' . displayDateItalianFormat($r['data_inizio_valutazione'] ?? '') . '</time></dd>';
	}
	if(($r['stato'] ?? '') === 'Annullata' || ($r['stato'] ?? '') === 'Respinta' || ($r['stato'] ?? '') === 'Accettata') {
        $dataInizio = '';
        $dataRifiuto = '';
        if (($r['data_inizio_valutazione'] ?? '') !== '') {
            $dataInizio = '<dt>Data inizio valutazione</dt><dd><time datetime="' . $r['data_inizio_valutazione'] . '">' . displayDateItalianFormat($r['data_inizio_valutazione']) . '</time></dd>';
        } else {
            $dataInizio = '<dt>Data inizio valutazione</dt><dd>Non presente</dd>';
        }
        if (($r['data_fine_valutazione'] ?? '') !== '') {
            $dataRifiuto = '<dt>Data fine valutazione</dt><dd><time datetime="' . $r['data_fine_valutazione'] . '">' . displayDateItalianFormat($r['data_fine_valutazione']) . '</time></dd>';
        } else {
            $dataRifiuto = '<dt>Data fine valutazione</dt><dd>Non presente</dd>';
        }
	}
    return [$dataInizio, $dataFine, $dataRifiuto];
}

/**
 * Gestione delle azioni POST che modificano lo stato (eseguono redirect)
 */
function handlePostActions(DBAccess $conn, array $r, string $email, int $idAnimale, &$messaggiForm): array {
    

    if (isset($_POST['inizia_valutazione'])) {
        $conn->startEvaluation($email, $idAnimale);
        header("Location: richieste-adozione?email=$email&id-animale=$idAnimale");
        exit;
    }

    // Accetta richiesta (potrebbe impostare da trasportare)
    if (isset($_POST['accetta_richiesta'])) {
        if ($r['trasporto-richiesta'] == 1) {
            $conn->setToTransport($email, $idAnimale);
            $r = $conn->getRequestDetails($email, $idAnimale);
        } else {
            $conn->acceptRequest($email, $idAnimale);
        }
        header("Location: richieste-adozione?email=$email&id-animale=$idAnimale");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salva_annotazioni'])) {
        $note = trim($_POST['note'] ?? '');
        $conn->updateNote($email, $idAnimale, $note);
        header("Location: richieste-adozione?email=$email&id-animale=$idAnimale");
        exit;
    }

    // Scarta richiesta
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scarta_richiesta'])) {
        $conn->rejectRequest($email, $idAnimale,$r['stato']);
        $r = $conn->getRequestDetails($email, $idAnimale);
        header("Location: richieste-adozione?email=$email&id-animale=$idAnimale");
        exit;
    }

    // Apri richiesta (riapre richiesta respinta)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apri_richiesta'])) {
        $conn->openRequest($email, $idAnimale);
        header("Location: richieste-adozione?email=$email&id-animale=$idAnimale");
        exit;
    }

	if (isset($_POST['salva_date_trasporto'])) {
        $dataPartenza = $_POST['data_partenza'];
        $dataArrivo = $_POST['data_arrivo'];
        $dataFineValutazione = $r['data_fine_valutazione'] ?? null; 

        if ($dataFineValutazione !== null) {
            $dataFineValutazioneFormatted = date('Y-m-d', strtotime($dataFineValutazione));
            if ($dataPartenza < $dataFineValutazioneFormatted) {
                $_SESSION['error_msg'] = "<em class='error' role='alert' aria-live='polite'>La data di partenza non può essere precedente alla data di fine valutazione (" . displayDateItalianFormat($dataFineValutazioneFormatted) . ").</em>";
                header("Location: richieste-adozione?email=" . urlencode($email) . "&id-animale=" . urlencode($idAnimale) . "&mode=edit-data#stato-trasporto");
                exit;
            }
        }

        $conn->setTransportDates($email, $idAnimale, $dataPartenza, $dataArrivo);
        header("Location: richieste-adozione?email=" . urlencode($email) . "&id-animale=" . urlencode($idAnimale));
        exit;
    }
    if (isset($_POST['trasporto_effettuato'])) {
        // $conn->markTransportCompleted($email, $idAnimale);
        $r = $conn->getRequestDetails($email, $idAnimale);
        header("Location: richieste-adozione?email=$email&id-animale=$idAnimale");
        exit;
    }

    return $r;
}

function controlAccess(): bool{
	//controlla se l'utente è loggato e se è un admin
	if(!isset($_SESSION['email']) || $_SESSION['role'] !== 'admin'){
		return false;
	}
	return true;
}

function imTheAdmin($r): bool{
	if(($r['email-admin'] ?? '') === ($_SESSION['email'] ?? '')){
		return true;
	}
	return false;
}

/* -------------------- inizio script -------------------- */

$paginaHTML = loadTemplate('./src/template/layout-admin.html', '<p>Errore: template layout.html non trovato o non leggibile.</p>');

$email = $_GET['email'];
$idAnimale = $_GET['id-animale'];

$richiesta = [];
$dataRichiestaRespinta = '';
$dataInizioValutazione = '';
$dataFineValutazione = '';
$nRichiesteRichiedente = '';
$noteTrasportoRichiesta = '';
$messaggiForm = '';
$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();

if ($connessioneOK) {
    $richiesta = $connessione->getRequestDetails($email, $idAnimale);

    // Gestione POST centralizzata (esegue redirect dove necessario)
    $richiesta = handlePostActions($connessione, $richiesta, $email, $idAnimale, $messaggiForm);
    // ... dopo $connessioneOK = $connessione->openDBConnection(); ...
    $nRichiesteRichiedente =  $connessione->countActiveRequestsForUser($richiesta['email-richiedente'] ?? '');
    $connessione->closeConnection();
}
//tolgo i messaggiForm
if (isset($_SESSION['error_msg'])) {
    $messaggiForm = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

$title = '<title>Area riservata admin - PetMatch </title>';
$description = '<meta name="description" content="Area riservata per gli amministratori di PetMatch">';
$keywords = "";

// Preparazione parti dinamiche
$scarta_richiesta = renderRejectRequest($richiesta);
$nav = buildAdminNav($adminMenu,'./richieste-adozione');
$breadcrumb = getBreadcrumb('dettagli-richiesta', $pagine);
$main = loadTemplate('./src/template/main/admin/dettagli-richiesta.html');

$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);

$di_chi = '';
if(!imTheAdmin($richiesta)){
	$di_chi = '<h2>Richiesta di adozione assegnata a '.e($richiesta['nome-admin'] ?? '').' '.e($richiesta['cognome-admin'] ?? '').'</h2>';
}
$main = str_replace('[di chi]', $di_chi, $main);
$dataRichiesta='<time datetime="' . ($richiesta['data-richiesta'] ?? '') . '">' . displayDateItalianFormat($richiesta['data-richiesta'] ?? '') . '</time>';

$main = str_replace('[data]', $dataRichiesta, $main);
$main = str_replace('[contenutoLettera]', e($richiesta['lettera-di-presentazione'] ?? ''), $main);
$main = str_replace('[paginaAnimale]', './animale?id=' . e($richiesta['id-animale'] ?? ''), $main);
$main = str_replace('[paginaRichiedente]', './profilo-richiedente?email=' . urlencode($_GET['email']) ?? '', $main);
$main = str_replace('[animalID]', $richiesta['id-animale'] ?? '', $main);
$main = str_replace('[trasporto]', siNo($richiesta['trasporto-richiesta'] ?? 0), $main);
$main = str_replace('[scarta-richiesta]', $scarta_richiesta, $main);
$main = str_replace('[stato]', e($richiesta['stato'] ?? ''), $main);
$main = str_replace('[nome]', e($richiesta['nome-richiedente'] ?? ''), $main);
if(!$richiesta['imgPath'] || !file_exists($richiesta['imgPath'])){
    $main = str_replace('[imgPath]', './assets/images/users/default-pic.png', $main);
}else{
    $main = str_replace('[imgPath]', e($richiesta['imgPath']), $main);
}
$main = str_replace('[cognome]', e($richiesta['cognome-richiedente'] ?? ''), $main);
//telefono e indirizzo sono opzionali
if($richiesta['telefono-richiedente'] !== null){
    $telefonoGrezzo = $richiesta['telefono-richiedente'];
    $numero = substr($telefonoGrezzo, -10);
    $prefisso = substr($telefonoGrezzo, 0, -10);
    $printTelefono = trim($prefisso . ' ' . $numero);

    $telefono_richiedente='<dt>Telefono</dt><dd>' . $printTelefono. '</dd>';
}else{
        $telefono_richiedente='<dt>Telefono</dt><dd> <em>Sconosciuto</em> </dd>';
}

$indirizzo_richiedente='';

$main = str_replace('[telefono-richiedente]', $telefono_richiedente, $main);
$main = str_replace('[email]', e($richiesta['email-richiedente'] ?? ''), $main);
if($richiesta['trasporto-richiesta']===1 && $richiesta['indirizzo-richiedente']){
    $indirizzo_richiedente='<dt>Indirizzo</dt><dd>' . $richiesta['indirizzo-richiedente']. '</dd>';
}elseif($richiesta['trasporto-richiesta']===1 && !$richiesta['indirizzo-richiedente']){
    // per sicurezza aggiuntiva, controllo il caso in cui non sia presente (MA dovrebbe se è stato richiesto il trasporto!)
    $indirizzo_richiedente='<dt class="data-error">Indirizzo</dt><dd>MANCANTE</dd>'; //
}
$main = str_replace('[indirizzo-richiedente]', $indirizzo_richiedente, $main);
$main = str_replace('[idAnimale]', e($richiesta['id-animale'] ?? ''), $main);
$main = str_replace('[nomeAnimale]', e($richiesta['nome-animale'] ?? ''), $main);

if(!$richiesta['animalImgPath'] || !file_exists($richiesta['animalImgPath'])){
    if($richiesta['tipo_animale'] === 'Gatto'){
        $main = str_replace('[animalImgPath]', './assets/images/animals/defaultGatto.jpg', $main);
    }else{
        $main = str_replace('[animalImgPath]', './assets/images/animals/defaultCane.jpg', $main);
    }
}else{
    $main = str_replace('[animalImgPath]', e($richiesta['animalImgPath']), $main);
}

if($richiesta['sesso-animale'] === 'F')
    $main = str_replace('[sessoAnimale]', '<abbr title="Femmina">F</abbr>', $main);
elseif($richiesta['sesso-animale'] === 'M')
    $main = str_replace('[sessoAnimale]', '<abbr title="Maschio">M</abbr>', $main);

$main = str_replace('[etaAnimale]', e($richiesta['eta-animale'] ?? ''), $main);
$main = str_replace('[razzaAnimale]', e($richiesta['razza-animale'] ?? ''), $main);
$main = str_replace('[trasportoAnimale]', siNo($richiesta['trasporto-animale'] ?? 0), $main);
$main = str_replace('[famigliaIdeale]', e($richiesta['famiglia-ideale'] ?? ''), $main);

if (empty($richiesta['condizioni-mediche'])) {
    $main = str_replace('[condizioniMediche]', 'Nessuna', $main);
} else {
    $main = str_replace('[condizioniMediche]', e($richiesta['condizioni-mediche']), $main);
}

list($dataInizioValutazione, $dataFineValutazione,$dataRichiestaRespinta) = buildDateInfo($richiesta);

$stato_trasporto = '';
$email_url = urlencode($richiesta['email-richiedente'] ?? '');
$id_url = urlencode($richiesta['id-animale'] ?? '');
$url_base = "?email=$email_url&id-animale=$id_url";

if (($richiesta['stato'] ?? '') === 'Da trasportare') {
    
if(isset($_GET['mode']) && $_GET['mode'] === 'edit-data'){
        $data_per_input_arrivo = ($richiesta['data-arrivo'] === null) ? '' : date('Y-m-d', strtotime($richiesta['data-arrivo']));
        $data_per_input_partenza = ($richiesta['data-partenza'] === null) ? '' : date('Y-m-d', strtotime($richiesta['data-partenza']));

        $stato_trasporto .= '
        <article id="stato-trasporto" class="note">
            <div class="header-article">
                    <h2>Modifica le date del trasporto</h2>
                    <a href="' . $url_base . '#stato-trasporto" class="pencil">
                        <img src="./assets/icons/edit-pencil.svg" alt="Annulla modifica">
                    </a>
             </div>
            <form method="post" action="' . $url_base . '#stato-trasporto">
                <label for="input-data-partenza" >Data di partenza:</label>
                <input type="date" name="data_partenza" id="input-data-partenza" value="' . $data_per_input_partenza . '">
                <label for="input-data-arrivo" >Data di arrivo:</label>
                <input type="date" name="data_arrivo" id="input-data-arrivo" value="' . $data_per_input_arrivo . '">
                
                <input type="hidden" name="email_richiedente" value="' . htmlspecialchars($richiesta['email-richiedente']) . '">
                <input type="hidden" name="id_animale" value="' . htmlspecialchars($richiesta['id-animale']) . '">
                <div class="button-group">
                <button type="reset" class="orange-button">Elimina modifica</button>
                <button type="submit" name="salva_date_trasporto" class="orange-button">Salva date</button>
                </div>
            </form>
            [messaggiForm]
        </article>';
    } else {
        $data_raw_arrivo = $richiesta['data-arrivo'] ?? '';
        $data_raw_partenza = $richiesta['data-partenza'] ?? '';
        if(($data_raw_partenza === '' || $data_raw_partenza === null) && ($data_raw_arrivo !== '' || $data_raw_arrivo !== null)){
            $contenuto_data_arrivo = 'Ancora nessuna data di arrivo impostata.';
            $contenuto_data_partenza = 'Ancora nessuna data di partenza impostata.';
        } else {
            $contenuto_data_arrivo = '<time datetime="' . $data_raw_arrivo . '">' . displayDateItalianFormat($data_raw_arrivo) . '</time>';
            $contenuto_data_partenza = '<time datetime="' . $data_raw_partenza . '">' . displayDateItalianFormat($data_raw_partenza) . '</time>';
        }
        $stato_trasporto .= '
            <article id="stato-trasporto" class="note">
                <div class="header-article">
                    <h2>Informazioni sul trasporto</h2>
                    <a href="' . $url_base . '&mode=edit-data#stato-trasporto" class="pencil">
                        <img src="./assets/icons/edit-pencil.svg" alt="Modifica data di arrivo">
                    </a>
                </div>
                <dl>
                    <dt>Data di partenza</dt>
                    <dd>' . $contenuto_data_partenza . '</dd>
                    <dt>Data di arrivo</dt>
                    <dd>' . $contenuto_data_arrivo . '</dd>
                </dl>';
            //se la data di arrivo è impostata ed è quella odierna o passata, mostra il bottone per segnare il trasporto come effettuato
        $data_odierna = date('Y-m-d');
        if($data_raw_arrivo !== '' && $data_raw_arrivo !== null && $data_raw_arrivo <= $data_odierna){
                     $stato_trasporto .= '<form method="post" action="' . $url_base . '#stato-trasporto">
                        <button type="submit" name="trasporto_effettuato" class="orange-button">Segna trasporto come effettuato</button>
                    </form> </article>';
        }else{
            $stato_trasporto .= '</article>';
        }
    }
}


$main = str_replace('[stato-trasporto]', $stato_trasporto, $main);
$main = str_replace('[dataInizioValutazione]', $dataInizioValutazione, $main);
$main = str_replace('[dataFineValutazione]', $dataFineValutazione, $main);
$main = str_replace('[dataRichiestaRespinta]', $dataRichiestaRespinta, $main);
$main = str_replace('[pulsanti-azioni-richiesta]', renderPulsantiAzioni($richiesta), $main);


// Controllo se mostrare la sezione: 
// Stato non Nuova/Annullata OPPURE (Stato Annullata E appunti non vuoti)
$annotazioni = '';
if(($richiesta['stato']!=='Annullata' && $richiesta['stato']!=='Nuova'  )|| ($richiesta['stato']==='Annullata' && ($richiesta['appunti'] !== '' || $richiesta['appunti'] !== NULL))){

    $annotazioni = '';

    if (isset($_GET['mode']) && $_GET['mode'] === 'note') {
        $annotazioni .= '
                <article id="sezione-note" class="note">
                    <div class="header-article">
                        <h2>Le tue annotazioni</h2>
                        <a href="?email=' . urlencode($richiesta['email-richiedente'] ?? '') . '&id-animale=' . urlencode($richiesta['id-animale'] ?? '') . '#sezione-note" id="edit-note" class="pencil" aria-label="Modifica le annotazioni">
                            <img src="./assets/icons/edit-pencil.svg" alt="" aria-hidden="true">
                        </a>
                    </div>
                    <div id="note-container">
                        <form id="form-note" action="richieste-adozione?email=' . urlencode($richiesta['email-richiedente'] ?? '') . '&id-animale=' . urlencode($richiesta['id-animale'] ?? '') .'" method="post">
                            <label for="input-note" class="sr-only">Modifica annotazioni:</label>
                            <textarea id="input-note" name="note" rows="4">' . htmlspecialchars($richiesta['appunti'] ?? '', ENT_QUOTES, 'UTF-8') . '</textarea>
                            <button name="salva_annotazioni" type="submit" class="orange-button">Salva annotazioni</button>
                        </form>
                    </div>
                </article>';
    }else{
        if($richiesta['appunti'] === '' || $richiesta['appunti'] === NULL){
            $richiesta['appunti'] = 'Non hai ancora preso appunti per questa richiesta.';
        }
        $annotazioni.= '
            <article id="sezione-note" class="note">
                <div class="header-article">
                    <h2>Le tue annotazioni</h2>
                    <a href="?mode=note&email=' . urlencode($richiesta['email-richiedente'] ?? '') . '&id-animale=' . urlencode($richiesta['id-animale'] ?? '') . '#sezione-note" id="edit-note" class="pencil" aria-label="Annulla le annotazioni">
                        <img src="./assets/icons/edit-pencil.svg" alt="" aria-hidden="true">
                    </a>
                </div>
                <div id="note-container">
                    <pre id="note-text">' . e($richiesta['appunti'] ?? '') . '</pre>
                </div>
            </article>';
    }
}
if($richiesta['trasporto-richiesta']!==$richiesta['trasporto-animale'] && $richiesta['trasporto-richiesta']===1){
    $noteTrasportoRichiesta = '
                    <em id="note-richiesta">Il richiedente ha richiesto il trasporto dell\'animale, ma l\'animale non è idoneo al trasporto.</em>';

}
$main = str_replace('[messaggiForm]', $messaggiForm, $main);
$main = str_replace('[note-trasporto-richiesta]', $noteTrasportoRichiesta, $main);
$main = str_replace('[n]', $nRichiesteRichiedente, $main);
$main = str_replace('[annotazioni]', $annotazioni, $main);

$main = str_replace('[descrizioneCaratteriale]', e($richiesta['descrizione-caratteriale'] ?? ''), $main);

$paginaHTML = str_replace('[main]', $main, $paginaHTML);
echo $paginaHTML;
?>