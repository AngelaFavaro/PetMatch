<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
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
if ($fromEmail) {
    $pagine['dettagli-animale']['parent'] = 'dettagli-richiesta';
    $pagine['dettagli-richiesta']['url'] .= "?email=" . urlencode($fromEmail) . "&id-animale=" . urlencode($idAnimale);
} else {
    $pagine['dettagli-animale']['parent'] = 'area-riservata';
}

// Aggiorniamo l'URL della pagina corrente per includere l'ID
$pagine['dettagli-animale']['url'] .= "?id-animale=" . urlencode($idAnimale);
$breadcrumb = getBreadcrumb('dettagli-animale', $pagine);

$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
$richiesta = null; 

if ($connessioneOK) {
    $richiesta = $connessione->getAnimalById($idAnimale);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inizia_modifica'])) {
        $connessione->closeConnection();
        header("Location: modifica-animale?id-animale=$idAnimale");
        exit;
    }

    $connessione->closeConnection();
}

if (!$richiesta) {
    die("Errore: Animale non trovato o ID non valido.");
}

$paginaHTML = loadTemplate('./src/template/layout-admin.html', '<p>Errore caricamento layout.</p>');
$main = loadTemplate('./src/template/main/admin/dettagli-animale.html');

$title = '<title>Dettagli ' . e($richiesta['nome'] ?? 'Animale') . ' - Admin PetMatch</title>';
$main = str_replace('[nomeAnimale]', e($richiesta['nome'] ?? ''), $main);
$description = '<meta name="description" content="Visualizzazione dettagliata dell\'animale nel sistema gestionale">';
$nav = buildAdminNav($adminMenu, './area-riservata'); // Evidenzia area admin nel menu

$paginaHTML = str_replace(['[breadcrumb]', '[title]', '[nav]', '[description]', '[keywords]'], 
                         [$breadcrumb, $title, $nav, $description, ""], 
                         $paginaHTML);

$main = str_replace('[nomeAnimale]', e($richiesta['nome-animale'] ?? ''), $main);

$imgPath = $richiesta['foto'] ?? '';
if (!$imgPath || !file_exists($imgPath)) {
    $imgPath = ($richiesta['tipologia'] === 'Gatto') ? './assets/images/animals/defaultGatto.jpg' : './assets/images/animals/defaultCane.jpg';
}
$main = str_replace('[animalImgPath]', e($imgPath), $main);

$sesso = $richiesta['sesso'] ?? '';
$sessoHTML = ($sesso === 'F') ? '<abbr title="Femmina">F</abbr>' : (($sesso === 'M') ? '<abbr title="Maschio">M</abbr>' : e($sesso));
$main = str_replace('[sessoAnimale]', $sessoHTML, $main);

$main = str_replace('[etaAnimale]', e($richiesta['dataNascita'] ?? ''), $main);
$main = str_replace('[razzaAnimale]', e($richiesta['razza'] ?? ''), $main);
$main = str_replace('[peloAnimale]', e($richiesta['pelo'] ?? ''), $main);
$main = str_replace('[tagliaAnimale]', e($richiesta['taglia'] ?? ''), $main);
$main = str_replace('[coloreAnimale]', e($richiesta['colore'] ?? ''), $main);
$main = str_replace('[trasportoAnimale]', siNo($richiesta['trasporto'] ?? 0), $main);
$main = str_replace('[famigliaIdeale]', e($richiesta['famiglia'] ?? ''), $main);
$main = str_replace('[descrizioneCaratteriale]', e($richiesta['carattere'] ?? ''), $main);

$condizioni = empty($richiesta['condMediche']) ? 'Nessuna' : e($richiesta['condMediche']);
$main = str_replace('[condizioniMediche]', $condizioni, $main);

$btnModifica = '
<div class="edit-btn-container">
    <form method="post">
        <button type="submit" name="inizia_modifica" class="btn-edit">
            <i class="fas fa-edit" aria-hidden="true"></i> Modifica Scheda
        </button>
    </form>
</div>';
$main = str_replace('[pulsanti-modifica-animale]', $btnModifica, $main);

// Output Finale
$paginaHTML = str_replace('[main]', $main, $paginaHTML);
echo $paginaHTML;
?>