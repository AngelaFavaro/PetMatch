<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;
session_start();

// 1. Controllo Accesso
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: ./accedi");
    exit;
}

// 2. Parametri e Inizializzazione
$idAnimale = $_GET['id-animale'] ?? null;
$emailLoggato = $_SESSION['email'] ?? '';
$fromEmail = $_GET['from_email'] ?? null; 
$from = $_GET['from'] ?? null;

if (!$idAnimale) {
    header("Location: ./area-riservata");
    exit;
}

function createAnimalRequestList(array $richieste): string {
    $stati = ['Nuova', 'In valutazione', 'Accettata', 'Respinta', 'Annullata', 'Da trasportare'];
    $gruppi = array_fill_keys($stati, '');

     foreach ($richieste as $r) {
        $nomeCandidato = htmlspecialchars($r['Nome'] . ' ' . $r['Cognome']);
        $nomeAnimale = htmlspecialchars($r['NomeAnimale']);
        $statoAttuale = $r['Stato'];

        $testi = [
            'Nuova' => "<strong>$nomeCandidato</strong> ha fatto richiesta per <em>" . htmlspecialchars($nomeAnimale) . "</em>",
            'In valutazione' => "Candidatura di <strong>$nomeCandidato</strong> in valutazione.",
            'Accettata' => "Richiesta di <strong>$nomeCandidato</strong> accettata!",
            'Respinta' => "Richiesta di <strong>$nomeCandidato</strong> respinta.",
            'Annullata' => "Richiesta di <strong>$nomeCandidato</strong> annullata.",
            'Da trasportare' => "<strong>$nomeCandidato</strong> è in attesa del trasporto."
        ];

        $li = '<li class="richiesta-card">
                <div class="card-content">
                    <p>' . ($testi[$statoAttuale] ?? "Richiesta da $nomeCandidato") . '</p>
                    <a href="./richieste-adozione?email=' . urlencode($r['Email']) . '&id-animale=' . urlencode($r['IDanimale']) . '" class="btn-vedi-richiesta">Vedi richiesta</a>
                </div>
               </li>';
        if (isset($gruppi[$statoAttuale])) {
            $gruppi[$statoAttuale] .= $li;
        }
    }

    $htmlOutput = '';
    $i = 1; 
    foreach ($stati as $stato) {
        $content = $gruppi[$stato];
        if (empty($content)) {
            $content = '<li class="empty-message">Nessuna richiesta in questo stato.</li>';
        }
        $htmlOutput .= '<ul class="tab-content content-tab' . $i . '" aria-label="Richieste di tipo: ' . $stato . '">' . $content . '</ul>';
        $i++;
    }
    return '<div id="start-requests" class="requests-container">' . $htmlOutput . '</div>';
}

// 4. Connessione al Database e Logica Dati
$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
$richiesta = null; 
$btnModifica = '';
$btnEliminaHTML = '';
$btnAssegnazione = '';
$listRequestHTML = '';
$NRequestsByStatus = [];

if ($connessioneOK) {
    $richiesta = $connessione->getAnimalById($idAnimale);

    if ($richiesta) {
        $isOwner = (isset($richiesta['EmailAdmin']) && $richiesta['EmailAdmin'] === $emailLoggato);

        // --- GESTIONE AZIONI POST ---
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete-assignment'])) {
            if ($isOwner) {
                if ($connessione->removeAdminAssignment($idAnimale)) {
                    $connessione->closeConnection();
                    header("Location: ./senza-amministratore");
                     exit;
                }
            } 
        }

        // --- GENERAZIONE URL E BOTTONI ---
        $urlModifica = $pagine['modifica-animale']['url'] . "?id-animale=" . urlencode($idAnimale);
        if ($from) $urlModifica .= "&from=" . urlencode($from);
        if ($fromEmail) $urlModifica .= "&from_email=" . urlencode($fromEmail);

        if ($isOwner) {
            // Tasto Modifica
            $btnModifica = '
            <div class="edit-btn-container">
                <a href="' . e($urlModifica) . '" class="pencil">
                    <img src="./assets/icons/edit-pencil.svg" alt="Modifica scheda animale" />
                </a>
            </div>';
            
            // Tasto Elimina - NOTA: L'input checkbox NON deve avere 'checked'
            $btnEliminaHTML = '
                <input type="checkbox" id="delete-animal-check" class="popup-checkbox" hidden/>
                <label for="delete-animal-check" id="button-cancel">
                    <img src="./assets/icons/delete-trash.svg" alt="" />Elimina animale
                </label>
                <div class="overlay-content">
                    <div class="dialog-box">
                        <h3>Conferma eliminazione</h3>
                        <p>L\'eliminazione di <strong>' . e($richiesta['Nome']) . '</strong> è irreversibile. Vuoi continuare?</p>
                        <div class="dialog-buttons">
                            <label for="delete-animal-check">No, annulla</label>
                            <form method="post">
                                <button type="submit" name="delete-animale">Sì, elimina</button>
                            </form>
                        </div>
                    </div>
                </div>';
            
            $btnAssegnazione = '<form method="post">
                                    <button type="submit" name="delete-assignment" class="orange-button">Toglimi dall\'assegnazione</button>
                                </form>';
        } else {
             $btnEliminaHTML = '<p class="read-only-badge">Sola lettura: l\'animale non è assegnato a te in questo momento.</p>';
        }

        // Dati Richieste e Conteggi
        $elencoRichiesteDati = $connessione->getAnimalRequestsId($idAnimale);
        $listRequestHTML = empty($elencoRichiesteDati) ? "<p>Nessuna richiesta per questo animale.</p>" : createAnimalRequestList($elencoRichiesteDati);
        $NRequestsByStatus = $connessione->getNRequestByStatusAnimal($idAnimale);
    }
    $connessione->closeConnection();
}

if (!$richiesta) {
    die("Errore: Animale non trovato o ID non valido.");
}

// 5. CARICAMENTO LAYOUT E SOSTITUZIONI
$breadcrumb = getBreadcrumb('dettagli-animale', $pagine);
$paginaHTML = loadTemplate('./src/template/layout-admin.html');
$main = loadTemplate('./src/template/main/admin/dettagli-animale.html');

$title = '<title>Dettagli ' . e($richiesta['Nome'] ?? 'Animale') . ' - Admin PetMatch</title>';
$description = '<meta name="description" content="Visualizzazione dettagliata dell\'animale">';

// Navigazione attiva
$activeNav = $fromEmail ? 'richieste-adozione' : ($from === 'senza-admin' ? 'senza-amministratore' : 'assegnati-a-te');
$nav = buildAdminNav($adminMenu, $activeNav, $pagine);
// Sostituzioni Header
$paginaHTML = str_replace(['[breadcrumb]', '[title]', '[nav]', '[description]', '[keywords]'], 
                         [$breadcrumb, $title, $nav, $description, ""], 
                         $paginaHTML);
// Sostituzioni Main (Info Base)
$main = str_replace('[nomeAnimale]', e($richiesta['Nome'] ?? 'N/D'), $main);
$imgPath = $richiesta['ImgPath'] ?? '';
if (!$imgPath || !file_exists($imgPath)) {
    $imgPath = (isset($richiesta['Tipo']) && $richiesta['Tipo'] === 'Gatto') 
               ? './assets/images/animals/defaultGatto.jpg' 
               : './assets/images/animals/defaultCane.jpg';
}
$main = str_replace('[animalImgPath]', e($imgPath), $main);
$sesso = $richiesta['Sesso'] ?? '';
$sessoHTML = ($sesso === 'F') ? '<abbr title="Femmina">F</abbr>' : (($sesso === 'M') ? '<abbr title="Maschio">M</abbr>' : e($sesso));
$main = str_replace('[sessoAnimale]', $sessoHTML, $main);

$etaCalcolata = calcolaEta($richiesta['DataNascita'] ?? null);
$main = str_replace('[etaAnimale]', e($etaCalcolata !== null ? $etaCalcolata : "N/D"), $main);

$main = str_replace([
    '[razzaAnimale]', '[peloAnimale]', '[tagliaAnimale]', '[coloreAnimale]', 
    '[trasportoAnimale]', '[famigliaIdeale]', '[descrizioneCaratteriale]'
], [
    e($richiesta['Razza'] ?? 'N/D'), e($richiesta['Pelo'] ?? 'N/D'), e($richiesta['Taglia'] ?? 'N/D'), e($richiesta['Colore'] ?? 'N/D'),
    siNo($richiesta['Trasporto'] ?? 0), e($richiesta['DescrFamiglia'] ?? 'N/D'), e($richiesta['DescrComportamentale'] ?? 'N/D')
], $main);

$condizioni = ($richiesta['CondizioniMediche'] == '0' || empty(trim($richiesta['CondizioniMediche']))) ? 'Nessuna' : $richiesta['CondizioniMediche'];
$main = str_replace('[condizioniMediche]', e($condizioni), $main);
// Iniezione Pulsanti Dinamici
$main = str_replace('[pulsanti-modifica-animale]', $btnModifica, $main);
$main = str_replace('[pulsante-elimina-animale]', $btnEliminaHTML, $main); 
$main = str_replace('[pulsante-assegnazione]', $btnAssegnazione, $main);

// Conteggi Tab e Lista Richieste
$main = str_replace([
    '[n-nuove]', '[n-valutazione]', '[n-accettate]', '[n-respinte]', '[n-annullate]', '[n-trasporto]'
], [
    $NRequestsByStatus['Nuova'] ?? 0, $NRequestsByStatus['In valutazione'] ?? 0, $NRequestsByStatus['Accettata'] ?? 0,
    $NRequestsByStatus['Respinta'] ?? 0, $NRequestsByStatus['Annullata'] ?? 0, $NRequestsByStatus['Da trasportare'] ?? 0
], $main);

$main = str_replace('[elencoRichieste]', $listRequestHTML, $main);

$paginaHTML = str_replace('[main]', $main, $paginaHTML);
echo $paginaHTML;
?>