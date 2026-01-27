<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;
session_start();


if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header("Location: ./accedi");
    exit;
}

$idAnimale = $_GET['id-animale'] ?? null;
$fromEmail = $_GET['from_email'] ?? null; 
if (!$idAnimale) {
    header("Location: ./area-riservata");
    exit;
}



// Gestione gerarchia breadcrumb
// if ($fromEmail) {
//     $pagine['dettagli-animale']['parent'] = 'dettagli-richiesta';
    
//     $pagine['dettagli-richiesta']['url'] = "./richieste-adozione?email=" . urlencode($fromEmail) . "&id-animale=" . urlencode($idAnimale);
// } else {
//     $pagine['dettagli-animale']['parent'] = 'assegnati-a-te';
// }

$pagine['dettagli-animale']['url'] .= "?id-animale=" . urlencode($idAnimale);

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
    echo "";
    return '<div id="start-requests" class="requests-container">' . $htmlOutput . '</div>';
}

$breadcrumb = getBreadcrumb('dettagli-animale', $pagine);

$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
$richiesta = null; 

if ($connessioneOK) {
    $richiesta = $connessione->getAnimalById($idAnimale);

    $elencoRichiesteDati = $connessione->getAnimalRequestsId($idAnimale);

    if (empty($elencoRichiesteDati)) {
            $listRequestHTML = "<p>Debug: Il database non ha restituito richieste per l'ID $idAnimale</p>";
    } else {
        $listRequestHTML = createAnimalRequestList($elencoRichiesteDati);
    }

    $NRequestsByStatus = $connessione->getNRequestByStatusAnimal($idAnimale);
    
    $listRequestHTML = createAnimalRequestList($elencoRichiesteDati);

    $connessione->closeConnection();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete-account'])) {
    if ($connessioneOK) {
        $successo = $connessione->deleteAnimal($idAnimale); // Assicurati che esista questo metodo in DBAccess
        if ($successo) {
            header("Location: ./area-riservata?msg=eliminato");
            exit;
        } else {
            $erroreEliminazione = "Errore durante l'eliminazione dell'animale.";
        }
    }
}

if (!$richiesta) {
    die("Errore: Animale non trovato o ID non valido.");
}

$paginaHTML = loadTemplate('./src/template/layout-admin.html', '<p>Errore caricamento layout.</p>');
$main = loadTemplate('./src/template/main/admin/dettagli-animale.html');

$title = '<title>Dettagli ' . e($richiesta['nome'] ?? 'Animale') . ' - Admin PetMatch</title>';
$description = '<meta name="description" content="Visualizzazione dettagliata dell\'animale nel sistema gestionale">';

$activeNav = $fromEmail ? './richieste-adozione' : './area-riservata';
$nav = buildAdminNav($adminMenu, $activeNav);
$paginaHTML = str_replace(['[breadcrumb]', '[title]', '[nav]', '[description]', '[keywords]'], 
                         [$breadcrumb, $title, $nav, $description, ""], 
                         $paginaHTML);

$main = str_replace('[nomeAnimale]', e($richiesta['Nome'] ?? 'Non specificato'), $main);

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
$main = str_replace('[etaAnimale]', e($etaCalcolata !== null ? $etaCalcolata . " anni" : "N/D"), $main);

$main = str_replace('[razzaAnimale]', e($richiesta['Razza'] ?? 'N/D'), $main);
$main = str_replace('[peloAnimale]', e($richiesta['Pelo'] ?? 'N/D'), $main);
$main = str_replace('[tagliaAnimale]', e($richiesta['Taglia'] ?? 'N/D'), $main);
$main = str_replace('[coloreAnimale]', e($richiesta['Colore'] ?? 'N/D'), $main);
$main = str_replace('[trasportoAnimale]', siNo($richiesta['Trasporto'] ?? 0), $main);
$main = str_replace('[famigliaIdeale]', e($richiesta['DescrFamiglia'] ?? 'N/D'), $main);
$main = str_replace('[descrizioneCaratteriale]', e($richiesta['DescrComportamentale'] ?? 'N/D'), $main);
$main = str_replace('[condizioniMediche]', e($richiesta['CondizioniMediche'] ?? 'Nessuna'), $main);

$urlModifica = $pagine['modifica-animale']['url'] . "?id-animale=" . urlencode($idAnimale);
if (isset($_GET['from_email'])) {
    $urlModifica .= "&from_email=" . urlencode($_GET['from_email']);
}

$btnModifica = '
<div class="edit-btn-container">
    <a href="' . e($urlModifica) . '" class="pencil">
        <img src="./assets/icons/edit-pencil.svg" alt="Modifica scheda animale">
    </a>
</div>';
$main = str_replace('[pulsanti-modifica-animale]', $btnModifica, $main);

$main = str_replace('[n-nuove]', $NRequestsByStatus['Nuova'] ?? 0, $main);
$main = str_replace('[n-valutazione]', $NRequestsByStatus['In valutazione'] ?? 0, $main);
$main = str_replace('[n-accettate]', $NRequestsByStatus['Accettata'] ?? 0, $main);
$main = str_replace('[n-respinte]', $NRequestsByStatus['Respinta'] ?? 0, $main);
$main = str_replace('[n-annullate]', $NRequestsByStatus['Annullata'] ?? 0, $main);
$main = str_replace('[n-trasporto]', $NRequestsByStatus['Da trasportare'] ?? 0, $main);

$main = str_replace('[elencoRichieste]', $listRequestHTML, $main);

// Infine unisci tutto al layout
$paginaHTML = str_replace('[main]', $main, $paginaHTML);
echo $paginaHTML;
?>