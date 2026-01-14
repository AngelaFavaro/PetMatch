<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;
session_start();

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

/**
 * Genera gli input nascosti usati nei form (id_animale + email_richiedente)
 */
function hiddenInputsFrom(array $r): string {
    $id = e($r['id-animale'] ?? '');
    $email = e($r['email-richiedente'] ?? '');
    return '<input type="hidden" name="id_animale" value="' . $id . '">
            <input type="hidden" name="email_richiedente" value="' . $email . '">';
}

/**
 * Rende il blocco "scarta/apri richiesta"
 */
function renderRejectRequest(array $r): string {
    if (($r['stato'] ?? '') !== 'Respinta' && ($r['stato'] ?? '') !== 'Annullata') {
        return '<form method="POST">' .
            hiddenInputsFrom($r) .
            '<button type="submit" name="scarta_richiesta" class="orange-button">Scarta richiesta</button>
        </form>';
    }
    return '<form method="POST">' .
        hiddenInputsFrom($r) .
        '<button type="submit" name="apri_richiesta" class="orange-button">Apri richiesta</button>
    </form>';
}

/**
 * Rende i pulsanti di azione in base allo stato della richiesta
 */
function renderPulsantiAzioni(array $r): string {
    $stato = $r['stato'] ?? '';
    $html = '';
    if ($stato === 'Da trasportare') {
        $subject = rawurlencode('Richiesta informazioni per adozione di ' . ($r['nome-animale'] ?? ''));
        $html = '<a href="mailto:' . ($r['email-richiedente'] ?? '') . '?subject=' . $subject . '" class="orange-button" target="_blank">Contatta candidato</a>';
    } elseif ($stato === 'Nuova') {
        $html = '<form method="POST">' .
            hiddenInputsFrom($r) .
            '<button type="submit" name="inizia_valutazione" class="orange-button">Inizia valutazione</button>
        </form>';
    } elseif ($stato === 'In valutazione') {
        $html = '<form method="POST">' .
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
	if(($r['stato'] ?? '') === 'Annullata') {
		$dataInizio = '<dt>Data inizio valutazione</dt><dd><time datetime="' . ($r['data_inizio_valutazione'] ?? '') . '">' . displayDateItalianFormat($r['data_inizio_valutazione'] ?? '') . '</time></dd>';
		$dataRifiuto = '<dt>Data annullamento</dt><dd><time datetime="' . ($r['data_fine_valutazione'] ?? '') . '">' . displayDateItalianFormat($r['data_fine_valutazione'] ?? '') . '</time></dd>';
	}
    return [$dataInizio, $dataFine, $dataRifiuto];
}

/**
 * Gestione delle azioni POST che modificano lo stato (eseguono redirect)
 */
function handlePostActions(DBAccess $conn, array $r, string $email, int $idAnimale): array {
    

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

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salva_note'])) {
        $note = trim($_POST['note'] ?? '');
        $conn->updateNote($email, $idAnimale, $note);
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

	if (isset($_POST['salva_data_arrivo'])) {
		$newDate = $_POST['data_arrivo'];
		$conn->setArrivalDate($email, $idAnimale, $newDate);
        $r = $conn->getRequestDetails($email, $idAnimale);
		header("Location: richieste-adozione?email=$email&id-animale=$idAnimale");
		exit;
	}

  /* -------------------- SCRIPT DI TEST INSERIMENTO REALE -------------------- */

   /* if (isset($_POST['esegui_test_caricamento'])) {
        
        // 1. CHIAMATA A UPLOAD IMAGE (gestisce il file fisico)
        // 'foto_test' è il nome del campo nel form qui sotto
        $imgPathGenerato = uploadImage($_FILES['foto_test'], 'animals');

        if ($imgPathGenerato) {
            $dbTest = new DBAccess();
            if ($dbTest->openDBConnection()) {
                
                // 2. DATI DA INSERIRE NEL DB
                $testData = [
                    'nome' => 'Test',
                    'data_nascita' => '2024-01-01',
                    'data_reg' => date('Y-m-d'),
                    'sesso' => 'M',
                    'tipo' => 'Gatto',
                    'colore' => 'Nero',
                    'pelo' => 'Corto',
                    'taglia' => 'Piccolo',
                    'razza' => 'Europeo',
                    'descr_famiglia' => 'Test family',
                    'descr_comportamento' => 'Test behavior',
                    'medico' => 'Sano',
                    'trasporto' => 0,
                    'imgPath' => $imgPathGenerato, // Il percorso restituito da uploadImage
                    'email_admin' => 'lindorlinor@gmail.com'
                ];

                // 3. INSERIMENTO NEL DATABASE
                $idNuovo = $dbTest->addAnimal($testData);
                
                if ($idNuovo) {
                    echo "<div style='background:green; color:white; padding:10px;'>SUCCESSO! ID: $idNuovo | File: $imgPathGenerato</div>";
                } else {
                    echo "<div style='background:red; color:white; padding:10px;'>ERRORE DB</div>";
                }
                $dbTest->closeConnection();
            }
        } else {
            echo "<div style='background:orange; padding:10px;'>ERRORE UPLOAD: Controlla permessi cartella o estensione file.</div>";
        }
    }

    // FORM DI TEST DA VISUALIZZARE IN CIMA ALLA PAGINA
    echo '
    <section style="border: 2px dashed #ccc; padding: 10px; margin: 20px;">
        <h3>Test Rapido Inserimento Animale + Immagine</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="foto_test" required>
            <button type="submit" name="esegui_test_caricamento">Carica e Inserisci nel DB</button>
        </form>
    </section>';*/
/* -------------------------------------------------------------------------- */

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
$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();

if ($connessioneOK) {
    $richiesta = $connessione->getRequestDetails($email, $idAnimale);

    // Gestione POST centralizzata (esegue redirect dove necessario)
    $richiesta = handlePostActions($connessione, $richiesta, $email, $idAnimale);

    $connessione->closeConnection();
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
$main = str_replace('[paginaRichiedente]', './profilo-utente?email=' . e($richiesta['email-richiedente'] ?? ''), $main);
$main = str_replace('[trasporto]', siNo($richiesta['trasporto-richiesta'] ?? 0), $main);
$main = str_replace('[scarta-richiesta]', $scarta_richiesta, $main);
$main = str_replace('[stato]', e($richiesta['stato'] ?? ''), $main);
$main = str_replace('[nome]', e($richiesta['nome-richiedente'] ?? ''), $main);
$main = str_replace('[imgPath]', e($richiesta['imgPath'] ?? ''), $main);
$main = str_replace('[cognome]', e($richiesta['cognome-richiedente'] ?? ''), $main);
//telefono e indirizzo sono opzionali
if($richiesta['telefono-richiedente'] ?? ''){
    $telefono_richiedente='<dt>Telefono</dt><dd>' . $richiesta['telefono-richiedente']. '</dd>';
}
$main = str_replace('[telefono-richiedente]', $telefono_richiedente, $main);
$main = str_replace('[email]', e($richiesta['email-richiedente'] ?? ''), $main);
$indirizzo_richiedente='';
if($richiesta['trasporto-richiesta']===1 && $richiesta['indirizzo-richiedente']){
    $indirizzo_richiedente='<dt>Indirizzo</dt><dd>' . $richiesta['indirizzo-richiedente']. '</dd>';
}elseif($richiesta['trasporto-richiesta']===1 && !$richiesta['indirizzo-richiedente']){
    // per sicurezza aggiuntiva, controllo il caso in cui non sia presente (MA dovrebbe se è stato richiesto il trasporto!)
    $indirizzo_richiedente='<dt class="data-error">Indirizzo</dt><dd>MANCANTE</dd>'; //
}
$main = str_replace('[indirizzo-richiedente]', $indirizzo_richiedente, $main);
$main = str_replace('[nomeAnimale]', e($richiesta['nome-animale'] ?? ''), $main);
$main = str_replace('[animalImgPath]', e($richiesta['animalImgPath'] ?? ''), $main);
if($richiesta['sesso-animale'] === 'F')
    $main = str_replace('[sessoAnimale]', '<abbr title="Femmina">F</abbr>', $main);
elseif($richiesta['sesso-animale'] === 'M')
    $main = str_replace('[sessoAnimale]', '<abbr title="Maschio">M</abbr>', $main);

$main = str_replace('[etaAnimale]', e($richiesta['eta-animale'] ?? ''), $main);
$main = str_replace('[razzaAnimale]', e($richiesta['razza-animale'] ?? ''), $main);
$main = str_replace('[trasportoAnimale]', siNo($richiesta['trasporto-animale'] ?? 0), $main);
$main = str_replace('[famigliaIdeale]', e($richiesta['famiglia-ideale'] ?? ''), $main);

// Se non ci sono condizioni mediche, mostra "Nessuna"
if (empty($richiesta['condizioni-mediche'])) {
    $main = str_replace('[condizioniMediche]', 'Nessuna', $main);
} else {
    $main = str_replace('[condizioniMediche]', e($richiesta['condizioni-mediche']), $main);
}

list($dataInizioValutazione, $dataFineValutazione,$dataRichiestaRespinta) = buildDateInfo($richiesta);

$stato_trasporto = '';
if($richiesta['data-arrivo']===NULL){
    $richiesta['data-arrivo'] = 'Ancora nessuna, impostane una';
}
if (($richiesta['stato'] ?? '') === 'Da trasportare') {
    $stato_trasporto = '
    <article id="stato-trasporto">
        <p>
            <strong>Data di arrivo:</strong> 
            <time datetime="' . $richiesta['data-arrivo'] . '" id="data-text">' . displayDateItalianFormat($richiesta['data-arrivo']) . '</time>

            <form id="form-data" class="hidden">
                <label for="input-data">Nuova data di arrivo:</label>
                <input type="date" name="data_arrivo" id="input-data" 
                    value="' . $richiesta['data-arrivo'] . '">
                <button type="submit" class="sr-only" aria-label="Modifica la data di arrivo"></button>
            </form>
        </p>
        <a href="#" id="btn-attiva-modifica" class="edit-btn">
            <img src="./assets/icons/edit-pencil.svg" alt="Modifica la data">
        </a>
    </article>';
}

$main = str_replace('[stato-trasporto]', $stato_trasporto, $main);
$main = str_replace('[dataInizioValutazione]', $dataInizioValutazione, $main);
$main = str_replace('[dataFineValutazione]', $dataFineValutazione, $main);
$main = str_replace('[dataRichiestaRespinta]', $dataRichiestaRespinta, $main);
$main = str_replace('[pulsanti-azioni-richiesta]', renderPulsantiAzioni($richiesta), $main);

// Annotazioni
$annotazioni = '';
if(($richiesta['stato']!=='Annullata' && $richiesta['stato']!=='Nuova'  )|| ($richiesta['stato']==='Annullata' && ($richiesta['appunti'] !== '' || $richiesta['appunti'] !== NULL))){
    
    $annotazioni = '
            <div class="note">
                <div class="header-note">
                    <h2>LE TUE ANNOTAZIONI</h2>
                    <a href="?mode=note&email=' . urlencode($richiesta['email-richiedente'] ?? '') . '&id-animale=' . urlencode($richiesta['id-animale'] ?? '') . '" type="button" id="edit-note" class="edit-btn" aria-label="Modifica le annotazioni">
                        <img src="./assets/icons/edit-pencil.svg" alt="" aria-hidden="true">
                    </a>
                </div>';

    if(isset($_GET['mode']) && $_GET['mode'] === 'note'){
        $annotazioni = '
                <form id="form-note">
                    <label for="input-note" class="sr-only">Modifica annotazioni:</label>
                    <textarea id="input-note" name="note" rows="4">' . e($richiesta['appunti'] ?? '') . '</textarea>
                    <button type="submit" class="sr-only">Salva annotazioni</button>
                </form>
            </div>
        </div>';
    }else{
        $annotazioni .='</div>';
    }
    return $annotazioni;
}
$main = str_replace('[annotazioni]', $annotazioni, $main);

$main = str_replace('[descrizioneCaratteriale]', e($richiesta['descrizione-caratteriale'] ?? ''), $main);

$paginaHTML = str_replace('[main]', $main, $paginaHTML);
echo $paginaHTML;
?>