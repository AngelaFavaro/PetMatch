<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    if(isset($_GET['id-animale'])){
        header("Location: ./animali?id-animale=".$_GET['id-animale']);
    }else{
        header("Location: ./animali");
    }
    exit;
}

$idAnimale = $_GET['id-animale'] ?? null;
$emailLoggato = $_SESSION['email'] ?? '';
$fromEmail = $_GET['from_email'] ?? null; 
$from = $_GET['from'] ?? null;

if (!$idAnimale) {
    header("Location: ./animali-admin");
    exit;
}

function createAnimalRequestList(array $richieste): string {
    $stati = ['Nuova', 
              'In valutazione', 
              'Accettata', 
              'Respinta', 
              'Annullata', 
              'Da trasportare'];
    $gruppi = array_fill_keys($stati, '');

     foreach ($richieste as $r) {
        $nomeCandidato = htmlspecialchars($r['Nome'] . ' ' . $r['Cognome']);
        $nomeAnimale = htmlspecialchars($r['NomeAnimale']);
        $statoAttuale = $r['Stato'];

        $testi = [
            'Nuova' => "<strong>$nomeCandidato</strong> ha fatto richiesta per <em>" . $nomeAnimale . "</em>",
            'In valutazione' => "Candidatura di <strong>$nomeCandidato</strong> in valutazione.",
            'Accettata' => "Richiesta di <strong>$nomeCandidato</strong> accettata!",
            'Respinta' => "Richiesta di <strong>$nomeCandidato</strong> respinta.",
            'Annullata' => "Richiesta di <strong>$nomeCandidato</strong> annullata.",
            'Da trasportare' => "<strong>$nomeCandidato</strong> è in attesa del trasporto."
        ];

        $li = '<li class="richiesta-card">
                <div class="card-content">
                    <p>' . ($testi[$statoAttuale] ?? "Richiesta da $nomeCandidato") . '</p>
                    <a href="./richieste-adozione?email=' . urlencode($r['Email']) . '&id-animale=' . urlencode($r['IDanimale']) . '" class="link-button">Vedi richiesta</a>
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

function buildAnimalButtons(int $idAnimale, array $richiesta, string $emailLoggato, ?string $from, ?string $fromEmail, array $pagine): array {
    $isOwner = (isset($richiesta['EmailAdmin']) && $richiesta['EmailAdmin'] === $emailLoggato);
    
    $buttons = [
        'modifica' => '',
        'elimina' => '',
        'assegnazione' => ''
    ];

    if ($isOwner) {
        $params = ["id-animale" => $idAnimale];
        if ($from) $params['from'] = $from;
        if ($fromEmail) $params['from_email'] = $fromEmail;
        $urlModifica = $pagine['modifica-animale']['url'] . "?" . http_build_query($params);
    
        $buttons['modifica'] = '
            <div class="edit-btn-container">
                <a href="' . e($urlModifica) . '" class="db-button">
                    Modifica
                </a>
            </div>';
        
        $buttons['elimina'] = '
            <form method="post">
                <button type="submit" name="show-dialog" class="button-cancel">
                    <img src="./assets/icons/delete-trash.svg" alt="" />Elimina animale
                </button>
            </form>';
        
        $buttons['assegnazione'] = '
            <form method="post">
                <button type="submit" name="delete-assignment" class="db-button">Toglimi dall\'assegnazione</button>
            </form>';
    } else {
        $buttons['elimina'] = '<p class="read-only-badge">Sola lettura: non sei l\'amministratore assegnato.</p>';
    }
    
    return $buttons;
}

function handleAnimalActions(DBAccess $conn, int $idAnimale): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete-assignment'])) {
        if ($conn->removeAdminAssignment($idAnimale)) {
            $conn->closeConnection();
            header("Location: ./animali-admin");
            exit;
        }
    }
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete-animale'])) {
        deleteStoredFile($conn->getFotoAnimalById($idAnimale));    
        if ($conn->deleteAnimal($idAnimale)) {
            $conn->closeConnection();
            header("Location: ./animali-admin");
            exit;
        }
    }
}

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
        handleAnimalActions($connessione, $idAnimale);

        $buttons = buildAnimalButtons($idAnimale, $richiesta, $emailLoggato, $from, $fromEmail, $pagine);
        $btnModifica = $buttons['modifica'];
        $btnEliminaHTML = $buttons['elimina'];
        $btnAssegnazione = $buttons['assegnazione'];

        $elencoRichiesteDati = $connessione->getAnimalRequestsId($idAnimale);
        $listRequestHTML = empty($elencoRichiesteDati) ? "<p>Nessuna richiesta per questo animale.</p>" : createAnimalRequestList($elencoRichiesteDati);
        $NRequestsByStatus = $connessione->getNRequestByStatusAnimal($idAnimale);
    }
    $connessione->closeConnection();
}

if (!$richiesta) {
    header("Location: ./404");
    exit;
}

$breadcrumb = getBreadcrumb('dettagli-animale', $pagine);
$paginaHTML = loadTemplate('./src/template/layout-admin.html');
$main = loadTemplate('./src/template/main/admin/dettagli-animale.html');

$title = '<title>Dettagli ' . e($richiesta['Nome'] ?? 'Animale') . ' - Admin PetMatch</title>';
$description = '<meta name="description" content="Visualizzazione dettagliata per amministratori di un animale in PetMatch">';
$keywords = "<meta name='keywords' content='amministratore, dettaglio, animale, PetMatch'>";

$activeNav = $fromEmail ? 'richieste-adozione' : ($from === 'senza-admin' ? 'senza-amministratore' : 'animali-admin');
$nav = buildAdminNav($adminMenu, $activeNav);

$paginaHTML = str_replace(['[breadcrumb]', '[title]', '[nav]', '[description]', '[keywords]'], 
                         [$breadcrumb, $title, $nav, $description, $keywords], 
                         $paginaHTML);

$main = str_replace('[idAnimale]', e($idAnimale), $main);
$main = str_replace('[nomeAnimale]', e($richiesta['Nome'] ?? 'N/D'), $main);
$imgPath = $richiesta['ImgPath'] ?? '';
if (empty($imgPath) || !file_exists($imgPath)) {
    $imgPath = (isset($richiesta['Tipo']) && $richiesta['Tipo'] === 'Gatto') 
               ? './assets/images/animals/defaultGatto.jpg' 
               : './assets/images/animals/defaultCane.jpg';
}
$main = str_replace('[animalImgPath]', e($imgPath), $main);
$sesso = $richiesta['Sesso'] ?? '';
$sessoHTML = ($sesso === 'F') ? 'Femmina' : (($sesso === 'M') ? 'Maschio' : e($sesso));
$main = str_replace('[sessoAnimale]', $sessoHTML, $main);

$etaCalcolata = calcolaEta($richiesta['DataNascita'] ?? null);
$main = str_replace('[etaAnimale]', e($etaCalcolata !== null ? $etaCalcolata : "N/D"), $main);

$main = str_replace('[razzaAnimale]', e($richiesta['Razza'] ?? 'N/D'), $main);
$main = str_replace('[peloAnimale]', e($richiesta['Pelo'] ?? 'N/D'), $main);
$main = str_replace('[tagliaAnimale]', e($richiesta['Taglia'] ?? 'N/D'), $main);
$main = str_replace('[coloreAnimale]', e($richiesta['Colore'] ?? 'N/D'), $main);
$main = str_replace('[trasportoAnimale]', siNo($richiesta['Trasporto'] ?? 0), $main);
$main = str_replace('[famigliaIdeale]', e($richiesta['DescrFamiglia'] ?? 'N/D'), $main);
$main = str_replace('[descrizioneCaratteriale]', e($richiesta['DescrComportamentale'] ?? 'N/D'), $main);

$condizioni = (empty(trim($richiesta['CondizioniMediche'] ?? '')) || $richiesta['CondizioniMediche'] === '0') 
    ? '<em class="no-data">Nessuna</em>' 
    : e($richiesta['CondizioniMediche']);
$main = str_replace('[condizioniMediche]', $condizioni, $main);

$main = str_replace('[pulsanti-modifica-animale]', $btnModifica, $main);
$main = str_replace('[pulsante-elimina-animale]', $btnEliminaHTML, $main); 
$main = str_replace('[pulsante-assegnazione]', $btnAssegnazione, $main);


$main = str_replace([
    '[n-nuove]', '[n-valutazione]', '[n-accettate]', '[n-respinte]', '[n-annullate]', '[n-trasporto]'
], [
    $NRequestsByStatus['Nuova'] ?? 0, $NRequestsByStatus['In valutazione'] ?? 0, $NRequestsByStatus['Accettata'] ?? 0,
    $NRequestsByStatus['Respinta'] ?? 0, $NRequestsByStatus['Annullata'] ?? 0, $NRequestsByStatus['Da trasportare'] ?? 0
], $main);
$main = str_replace('[elencoRichieste]', $listRequestHTML, $main);
$showModal = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['show-dialog']);
$main = str_replace('[openDialog]', ($showModal ? 'open' : ''), $main);

$paginaHTML = str_replace('[main]', $main, $paginaHTML);

echo $paginaHTML;
?>