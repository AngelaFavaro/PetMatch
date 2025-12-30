<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;
$_SESSION['user'] = 'lindorlinor@gmail.com';
/**
 * Carica un file e ritorna un fallback in caso di errore
 */
function loadTemplate(string $path, string $default = ''): string {
    $content = @file_get_contents($path);
    return $content === false ? $default : $content;
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
    $dataRifiuto = '';
    if (($r['stato'] ?? '') === 'Da trasportare') {
        $dataInizio = '<p><strong>Data inizio valutazione:</strong> ' . e($r['data_inizio_valutazione'] ?? '') . '</p>';
    }
	if(($r['stato'] ?? '') === 'In valutazione') {
		$dataInizio = '<p><strong>Data inizio valutazione:</strong> ' . e($r['data_inizio_valutazione'] ?? '') . '</p>';
	}
	if(($r['stato'] ?? '') === 'Annullata') {
		$dataInizio = '<p><strong>Data inizio valutazione:</strong> ' . e($r['data_inizio_valutazione'] ?? '') . '</p>';
		$dataRifiuto = '<p><strong>Data annullamento:</strong> ' . e($r['data_fine_valutazione']) . '</p>';
	}
    return [$dataInizio, $dataRifiuto];
}

/**
 * Gestione delle azioni POST che modificano lo stato (eseguono redirect quando previsto)
 */
function handlePostActions(DBAccess $conn, array $r, string $email, int $idAnimale): array {

    if (isset($_POST['inizia_valutazione'])) {
        $conn->startEvaluation($email, $idAnimale);
        header("Location: dettagli-richiesta");
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
        header("Location: dettagli-richiesta");
        exit;
    }

    // Salva note via fetch (risposta 204)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salva_note'])) {
        $note = trim($_POST['note'] ?? '');
        $conn->updateNote($email, $idAnimale, $note);
        exit;
    }

    // Scarta richiesta
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scarta_richiesta'])) {
        $conn->rejectRequest($email, $idAnimale,$r['stato']);
        $r = $conn->getRequestDetails($email, $idAnimale);
        header("Location: dettagli-richiesta");
        exit;
    }

    // Apri richiesta (riapre richiesta respinta)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apri_richiesta'])) {
        $conn->openRequest($email, $idAnimale);
        header("Location: dettagli-richiesta");
        exit;
    }

	if (isset($_POST['salva_data_arrivo'])) {
		$nuovaData = $_POST['data_arrivo'];
		$conn->setArrivalDate($email, $idAnimale, $nuovaData);
		header("Location: dettagli-richiesta");
		exit; // Importante per non restituire l'intera pagina HTML nella fetch
	}

    return $r;
}

function controlAccess(): bool{
	//controlla se l'utente è loggato e se è un admin
	if(!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin'){
		return false;
	}
	return true;
}

function imTheAdmin($r): bool{
	if(($r['email-admin'] ?? '') === ($_SESSION['user'] ?? '')){
		return true;
	}
	return false;
}
/* -------------------- inizio script -------------------- */

$paginaHTML = loadTemplate('./src/template/layout-admin.html', '<p>Errore: template layout.html non trovato o non leggibile.</p>');

// valori temporanei (in futuro verranno presi con GET)
$email = 'lindorlinor@gmail.com';
$idAnimale = 1;
// $email = $_GET['email-richiedente'];
// $idAnimale = $_GET['id-animale'];

$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
$richiesta = [];
$dataRichiestaRespinta = '';
$dataInizioValutazione = '';

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
$nav = loadTemplate('./src/template/partials/nav-admin.html');
$breadcrumb = getBreadcrumb('dettagli-richiesta', $pagine);
$main = loadTemplate('./src/template/main/admin/dettagli-richiesta.html');

$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);

$di_chi = '';
if(!imTheAdmin($richiesta)){
	$di_chi = '<h2>Richiesta di adozione assegnata a '.e($richiesta['nome-admin'] ?? '').' '.e($richiesta['cognome-admin'] ?? '').'</h2>';
}
$main = str_replace('[di chi]', $di_chi, $main);
$main = str_replace('[data]', e($richiesta['data-richiesta'] ?? ''), $main);
$main = str_replace('[contenutoLettera]', e($richiesta['lettera-di-presentazione'] ?? ''), $main);
$main = str_replace('[paginaAnimale]', './animale?id=' . e($richiesta['id-animale'] ?? ''), $main);
$main = str_replace('[paginaRichiedente]', './profilo-utente?email=' . e($richiesta['email-richiedente'] ?? ''), $main);
$main = str_replace('[trasporto]', siNo($richiesta['trasporto-richiesta'] ?? 0), $main);
$main = str_replace('[scarta-richiesta]', $scarta_richiesta, $main);
$main = str_replace('[stato]', e($richiesta['stato'] ?? ''), $main);
$main = str_replace('[nome]', e($richiesta['nome-richiedente'] ?? ''), $main);
$main = str_replace('[cognome]', e($richiesta['cognome-richiedente'] ?? ''), $main);
$main = str_replace('[telefono]', e($richiesta['telefono-richiedente'] ?? ''), $main);
$main = str_replace('[email]', e($richiesta['email-richiedente'] ?? ''), $main);
$main = str_replace('[indirizzo]', e($richiesta['indirizzo-richiedente'] ?? ''), $main);
$main = str_replace('[nomeAnimale]', e($richiesta['nome-animale'] ?? ''), $main);
$main = str_replace('[sessoAnimale]', e($richiesta['sesso-animale'] ?? ''), $main);
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

list($dataInizioValutazione, $dataRichiestaRespinta) = buildDateInfo($richiesta);

$stato_trasporto = '';
if (($richiesta['stato'] ?? '') === 'Da trasportare') {
    $stato_trasporto = '
		<article id="stato-trasporto">
			<p>
				<strong>Data di arrivo:</strong> 
				<span id="data-arrivo-text">
					' . e($richiesta['data_arrivo'] ?? '[dataDiArrivo]') . '
				</span>
			</p>
			<a href="#" id="edit-data">
				<img src="./assets/icons/edit-pencil.svg" alt="Modificare la data">
			</a>
		</article>';
}

$main = str_replace('[stato-trasporto]', $stato_trasporto, $main);
$main = str_replace('[dataInizioValutazione]', $dataInizioValutazione, $main);
$main = str_replace('[dataRichiestaRespinta]', $dataRichiestaRespinta, $main);
$main = str_replace('[pulsanti-azioni-richiesta]', renderPulsantiAzioni($richiesta), $main);

// Annotazioni
$annotazioni = '';
if($richiesta['stato']!=='Annullata' || ($richiesta['stato']==='Annullata' && $richiesta['appunti'] !== '')){
	$annotazioni = '<div class="note">
						<div class="header-note">
							<h2>LE TUE ANNOTAZIONI</h2>
							<a href="#" id="edit-note">
								<img src="./assets/icons/edit-pencil.svg" alt="Modificare le informazioni">
							</a>
						</div>
						<p id="note-text">' . e($richiesta['appunti'] ?? '') . '</p>
					</div>';
}
$main = str_replace('[annotazioni]', $annotazioni, $main);

$main = str_replace('[descrizioneCaratteriale]', e($richiesta['descrizione-caratteriale'] ?? ''), $main);

$paginaHTML = str_replace('[main]', $main, $paginaHTML);
echo $paginaHTML;
?>