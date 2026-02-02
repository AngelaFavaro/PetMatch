<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;


if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) { 
    header("Location: ./accedi");
    exit;
}

/** DA TOGLIERE, NON NECESSARIO TODO
 * Genera gli input nascosti usati nei form (id_animale + email_richiedente)
*/
function hiddenInputsFrom(array $r): string {
    $id = e($r['id-animale'] ?? '');
    $email = e($r['email-richiedente'] ?? '');
    return '<input type="hidden" name="id_animale" value="' . $id . '"/>
            <input type="hidden" name="email_richiedente" value="' . $email . '"/>';
}

/**
 * Renderizza il blocco "scarta/apri richiesta"
 */
function renderRejectRequest(array $r, $AcceptRequestDetails): string {
    if (($r['stato'] ?? '') !== 'Respinta' && ($r['stato'] ?? '') !== 'Annullata') {
        return '<form method="post">' .
            hiddenInputsFrom($r) .
            '<button type="submit" name="scarta_richiesta" class="db-button">Scarta richiesta</button>
        </form>';
    }
    
    if (empty($AcceptRequestDetails)) {
        return '<form method="post">' .
            hiddenInputsFrom($r) .
            '<button type="submit" name="apri_richiesta" class="db-button">Apri richiesta</button>
        </form>';
    }
    
    return '';
}

/**
 * Renderizza i pulsanti di azione in base allo stato della richiesta
 */
function renderPulsantiAzioni(array $r): string {
    $stato = $r['stato'] ?? '';
    $html = '';
    if ($stato === 'Da trasportare') {
        $subject = rawurlencode('Richiesta informazioni per adozione di ' . ($r['nome-animale'] ?? ''));
        $html = '<a href="mailto:' . ($r['email-richiedente'] ?? '') . '?subject=' . $subject . '" class="link-button" target="_blank">Contatta candidato</a>';
    } elseif ($stato === 'Nuova') {
        $html = '<form method="post">' .
            hiddenInputsFrom($r) .
            '<button type="submit" name="inizia_valutazione" class="db-button">Inizia valutazione</button>
        </form>';
    } elseif ($stato === 'In valutazione') {
        $html = '<form method="post">' .
            hiddenInputsFrom($r) .
            '<button type="submit" name="accetta_richiesta" class="db-button">Accetta richiesta</button>
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
 * Gestione delle azioni POST che modificano lo stato 
 */
function handlePostActions(DBAccess $conn, array $r, string $emailRichiedente, int $idAnimale, &$messaggiForm): array {
    

    if (isset($_POST['inizia_valutazione'])) {
        $conn->startEvaluation($emailRichiedente, $idAnimale);
        header("Location: richieste-adozione?email=$emailRichiedente&id-animale=$idAnimale");
        exit;
    }

    if (isset($_POST['accetta_richiesta'])) {
        if ($r['trasporto-richiesta'] == 1) {
            $conn->setToTransport($emailRichiedente, $idAnimale);
            $r = $conn->getRequestDetails($emailRichiedente, $idAnimale);
        } else {
            $conn->acceptRequest($emailRichiedente, $idAnimale);
        }
        header("Location: richieste-adozione?email=$emailRichiedente&id-animale=$idAnimale");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salva_annotazioni'])) {
        $note = trim($_POST['note'] ?? '');
        $conn->updateNote($emailRichiedente, $idAnimale, $note);
        header("Location: richieste-adozione?email=$emailRichiedente&id-animale=$idAnimale");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scarta_richiesta'])) {
        $conn->rejectRequest($emailRichiedente, $idAnimale,$r['stato']);
        $r = $conn->getRequestDetails($emailRichiedente, $idAnimale);
        header("Location: richieste-adozione?email=$emailRichiedente&id-animale=$idAnimale");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apri_richiesta'])) {
        $conn->openRequest($emailRichiedente, $idAnimale);
        header("Location: richieste-adozione?email=$emailRichiedente&id-animale=$idAnimale");
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
                header("Location: richieste-adozione?email=" . urlencode($emailRichiedente) . "&id-animale=" . urlencode($idAnimale) . "&mode=edit-data#stato-trasporto");
                exit;
            }
        }

        $conn->setTransportDates($emailRichiedente, $idAnimale, $dataPartenza, $dataArrivo);
        header("Location: richieste-adozione?email=" . urlencode($emailRichiedente) . "&id-animale=" . urlencode($idAnimale));
        exit;
    }
    if (isset($_POST['trasporto_effettuato'])) {
        $conn->markTransportCompleted($emailRichiedente, $idAnimale);
        $r = $conn->getRequestDetails($emailRichiedente, $idAnimale);
        header("Location: richieste-adozione?email=$emailRichiedente&id-animale=$idAnimale");
        exit;
    }

    return $r;
}

function imTheAdmin($r): bool{
	if(($r['email-admin'] ?? '') === ($_SESSION['email'] ?? '')){
		return true;
	}
	return false;
}

$paginaHTML = loadTemplate('./src/template/layout-admin.html', '<p>Errore: template layout.html non trovato o non leggibile.</p>');

$emailRichiedente = $_GET['email'];
$idAnimale = $_GET['id-animale'];

$richiesta = [];
$dataRichiestaRespinta = '';
$dataInizioValutazione = '';
$dataFineValutazione = '';
$nRichiesteRichiedente = '';
$noteTrasportoRichiesta = '';
$AcceptRequestDetails = '';
$messaggiForm = '';
$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();

if ($connessioneOK) {
    $richiesta = $connessione->getRequestDetails($emailRichiedente, $idAnimale);

    $richiesta = handlePostActions($connessione, $richiesta, $emailRichiedente, $idAnimale, $messaggiForm);
    $nRichiesteRichiedente =  $connessione->countActiveRequestsForUser($emailRichiedente ?? '');
    $AcceptRequestDetails = $connessione->getAcceptRequestByAnimal($idAnimale, $emailRichiedente);
    $connessione->closeConnection();
}

if (isset($_SESSION['error_msg'])) {
    $messaggiForm = $_SESSION['error_msg'];
    unset($_SESSION['error_msg']);
}

$title = '<title>Visualizzazione dettaglio richiesta di adozione  - Amministratore PetMatch </title>';
$description = '<meta name="description" content="Area riservata per gli amministratori in cui possono controllare nel dettaglio una richiesta di adozione ricevuta per un animale a loro assegnato.">';
$keywords = "<meta name='keywords' content='amministratore, dettaglio, richiesta, adozione, assegnato, animale, PetMatch'>";

$scarta_richiesta = renderRejectRequest($richiesta,$AcceptRequestDetails);
$nav = buildAdminNav($adminMenu,'./dettagli-richiesta');
$breadcrumb = getBreadcrumb('dettagli-richiesta', $pagine);
$main = loadTemplate('./src/template/main/admin/dettagli-richiesta.html');

$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);

$di_chi = '';
$imTheAdmin = imTheAdmin($richiesta);
if(!$imTheAdmin){
    if (($richiesta['nome-admin'] ?? '') === '' && ($richiesta['cognome-admin'] ?? '') === '') {
        $di_chi = '<h2 id="responsabile">Nessun responsabile al momento</h2>';
    } else {
        $di_chi = '<h2 id="responsabile">Responsabile: '.e($richiesta['nome-admin'] ?? '').' '.e($richiesta['cognome-admin'] ?? '').'</h2>';
    }
}
$main = str_replace('[di chi]', $di_chi, $main);
$dataRichiesta='<time datetime="' . ($richiesta['data-richiesta'] ?? '') . '">' . displayDateItalianFormat($richiesta['data-richiesta'] ?? '') . '</time>';

$main = str_replace('[data]', $dataRichiesta, $main);
$main = str_replace('[contenutoLettera]', e($richiesta['lettera-di-presentazione'] ?? ''), $main);

$urlDettaglioAnimale = './dettagli-animale?id-animale=' . e($richiesta['id-animale'] ?? '');
$main = str_replace('[paginaAnimale]', $urlDettaglioAnimale, $main);

$main = str_replace('[paginaRichiedente]', './profilo-richiedente?email=' . urlencode($_GET['email']) ?? '', $main);
$main = str_replace('[trasporto]', siNo($richiesta['trasporto-richiesta'] ?? 0), $main);
$main = str_replace('[scarta-richiesta]', $imTheAdmin?$scarta_richiesta:'', $main);
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
    $main = str_replace('[sessoAnimale]', 'Femmina', $main);
elseif($richiesta['sesso-animale'] === 'M')
    $main = str_replace('[sessoAnimale]', 'Maschio', $main);


$dataNascita = $richiesta['data-nascita'] ?? null;
$testoEta = $dataNascita ? formattaEta($dataNascita) : 'Età sconosciuta';

$main = str_replace('[etaAnimale]', e($testoEta), $main);
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
    
if($imTheAdmin && isset($_GET['mode']) && $_GET['mode'] === 'edit-data'){ //come per le annotazioni, proteggo anche il mode per evitare che dall'url un altro admin possa arrivarci (non è sufficiente togliere solo il pulsante)
        $data_per_input_arrivo = ($richiesta['data-arrivo'] === null) ? '' : date('Y-m-d', strtotime($richiesta['data-arrivo']));
        $data_per_input_partenza = ($richiesta['data-partenza'] === null) ? '' : date('Y-m-d', strtotime($richiesta['data-partenza']));

        $stato_trasporto .= '
        <article id="stato-trasporto" class="note">
            <div class="header-article">
                    <h2>Modifica le date del trasporto</h2>
                    <a href="' . $url_base . '#stato-trasporto" class="pencil">
                        <img src="./assets/icons/edit-pencil.svg" alt="Annulla modifica" />
                    </a>
             </div>
            <form method="post" action="' . $url_base . '#stato-trasporto">
                <label for="input-data-partenza" >Data di partenza:</label>
                <input type="date" name="data_partenza" id="input-data-partenza" value="' . $data_per_input_partenza . '"/>
                <label for="input-data-arrivo" >Data di arrivo:</label>
                <input type="date" name="data_arrivo" id="input-data-arrivo" value="' . $data_per_input_arrivo . '"/>
                
                <input type="hidden" name="email_richiedente" value="' . htmlspecialchars($richiesta['email-richiedente']) . '"/>
                <input type="hidden" name="id_animale" value="' . htmlspecialchars($richiesta['id-animale']) . '"/>
                <div class="button-group">
                <button type="reset" class="db-button">Elimina modifica</button>
                <button type="submit" name="salva_date_trasporto" class="db-button">Salva date</button>
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
                    <h2>Informazioni sul trasporto</h2>' . 
                    ($imTheAdmin ? '
                    <a href="' . $url_base . '&mode=edit-data#stato-trasporto" class="pencil">
                        <img src="./assets/icons/edit-pencil.svg" alt="Modifica date di trasporto" />
                    </a>' : '') . '
                </div>
                <dl>
                    <dt>Data di partenza</dt>
                    <dd>' . $contenuto_data_partenza . '</dd>
                    <dt>Data di arrivo</dt>
                    <dd>' . $contenuto_data_arrivo . '</dd>
                </dl>';

        $data_odierna = date('Y-m-d');
        
        if($imTheAdmin && $data_raw_arrivo !== '' && $data_raw_arrivo!==null && $data_raw_arrivo<=$data_odierna){
            $stato_trasporto .= '
                <form method="post" action="' . $url_base . '#stato-trasporto">
                    <button type="submit" name="trasporto_effettuato" class="db-button">Segna trasporto come effettuato</button>
                </form>';
        }

        $stato_trasporto .= '</article>';
    }
}


$main = str_replace('[stato-trasporto]', $stato_trasporto, $main);
$main = str_replace('[dataInizioValutazione]', $dataInizioValutazione, $main);
$main = str_replace('[dataFineValutazione]', $dataFineValutazione, $main);
$main = str_replace('[dataRichiestaRespinta]', $dataRichiestaRespinta, $main);
$main = str_replace('[pulsanti-azioni-richiesta]', $imTheAdmin?renderPulsantiAzioni($richiesta):'', $main);


// Controllo se mostrare la sezione: 
// Stato non Nuova/Annullata OPPURE (Stato Annullata E appunti non vuoti)
$annotazioni = '';
if(($richiesta['stato']!=='Annullata' && $richiesta['stato']!=='Nuova'  )|| ($richiesta['stato']==='Annullata' && ($richiesta['appunti'] !== '' || $richiesta['appunti'] !== NULL))){
    $annotazioni = '';

    if ($imTheAdmin && isset($_GET['mode']) && $_GET['mode'] === 'note') { //proteggo anche il mode per evitare che dall'url un altro admin possa arrivarci (non è sufficiente togliere solo il pulsante)
        $annotazioni .= '
                <article id="sezione-note" class="note">
                    <div class="header-article">
                        <h2>'.($imTheAdmin ? 'Le tue annotazioni' : 'Annotazioni').'</h2>
                        <a href="?email=' . urlencode($richiesta['email-richiedente'] ?? '') . '&id-animale=' . urlencode($richiesta['id-animale'] ?? '') . '#sezione-note" id="edit-note" class="pencil" aria-label="Modifica le annotazioni">
                            <img src="./assets/icons/edit-pencil.svg" alt="" />
                        </a>
                    </div>
                    <div id="note-container">
                        <form id="form-note" action="richieste-adozione?email=' . urlencode($richiesta['email-richiedente'] ?? '') . '&id-animale=' . urlencode($richiesta['id-animale'] ?? '') .'" method="post">
                            <label for="input-note" class="sr-only">Modifica annotazioni:</label>
                            <textarea id="input-note" name="note" rows="4">' . htmlspecialchars($richiesta['appunti'] ?? '', ENT_QUOTES, 'UTF-8') . '</textarea>
                            <button name="salva_annotazioni" type="submit" class="db-button">Salva annotazioni</button>
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
                    <h2>'.($imTheAdmin ? 'Le tue annotazioni' : 'Annotazioni').'</h2>' . 
                    ($imTheAdmin ? '
                    <a href="?mode=note&email=' . urlencode($richiesta['email-richiedente'] ?? '') . '&id-animale=' . urlencode($richiesta['id-animale'] ?? '') . '#sezione-note" id="edit-note" class="pencil" aria-label="Modifica le annotazioni">
                        <img src="./assets/icons/edit-pencil.svg" alt="" />
                    </a>' : '') . '
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
$main = str_replace('[note-su-richieste-richiedente]', $nRichiesteRichiedente==0?'':($nRichiesteRichiedente==1?'<em id="note-richiesta">Ha un\'altra richiesta attiva</em>':'<em id="note-richiesta">Ha altre <strong>'.$nRichiesteRichiedente.'</strong> richieste attive</em>'), $main);
$main = str_replace('[note-su-richieste-animale]', $AcceptRequestDetails ? '<em id="note-richiesta">'.$AcceptRequestDetails['nome_richiedente'].' '.$AcceptRequestDetails['cognome_richiedente'].' ha adottato questo animale</em> ' : '', $main);
$main = str_replace('[annotazioni]', $annotazioni, $main);

$main = str_replace('[descrizioneCaratteriale]', e($richiesta['descrizione-caratteriale'] ?? ''), $main);

$paginaHTML = str_replace('[main]', $main, $paginaHTML);
echo $paginaHTML;
?>