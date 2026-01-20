<?php
include './src/utils.php';
include './src/DBconnection.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use DB\DBAccess;

function buildEventsCards($events): string {
    $html='';
    foreach ($events as $e) {
        $img='';
        if (!empty($e['immagine']) && file_exists($e['immagine'])) {
            $img = $e['immagine'];
        } else {
            $img = 'assets/images/animals/defaultCane.jpg';
        }
        $citta=$e['citta'];
        $data=$e['data_evento'];
        $titolo=$e['titolo'];
        $descrEvento=$e['descrizione'];
        $html .= "<li>
                    <article class='evento' aria-labelledby='evento-titolo'>
                        <!-- Immagine dell'evento -->
                        <img class='immagine-evento' src=$img alt=''>

                        <!-- Posizione dell'evento -->
                        <p class='posizione-evento'>
                            <!-- icona decorativa -->
                            <img src='assets/icons/position-cat.svg' alt='' aria-hidden='true' class='icon-position'>
                            <!-- data semantica -->
                            $citta
                        </p>
                            

                        <!-- Data dell'evento -->
                        <p class='data-evento'>
                            <!-- icona decorativa -->
                            <img src='assets/icons/calendar.svg' alt='' aria-hidden='true' class='icon-calendar'>
                            <!-- data semantica -->
                            <time datetime=$data>$data</time>
                        </p>

                        <p class='posizione-data-abbr'>
                            $citta, $data
                        </p>

                        <!-- Descrizione dell'evento -->
                        <div class='descrizione-evento'>
                            <h2 id='evento-titolo-$titolo'>$titolo</h2>
                            <p>$descrEvento</p>
                        </div>
                        <div class='dettagli-evento-bottone'>
                        <a href='visualizzazione-evento?titolo=$titolo&data=$data'>Vedi dettagli</a>
                    </div>
                    </article>
                </li>";
    }
    return $html;
}



$perPagina = 6;
$pagina = max(1, (int)($_GET['page'] ?? 1));
$offset = ($pagina - 1) * $perPagina;

$eventi='';

$filters = [
    'name-event'   => $_GET['name-event']    ?? '',
    'data_inizio'       => $_GET['data_inizio'] ?? '',
    'data_fine'       => $_GET['data_fine'] ?? '',
    'citta'       => $_GET['citta'] ?? ''
];

$replaceFilters = [
    '[NAME]' => htmlspecialchars($filters['name-event']),
    '[DATA_INIZIO]' => htmlspecialchars($filters['data_inizio']),
    '[DATA_FINE]' => htmlspecialchars($filters['data_fine']),
    '[CITTA]' => htmlspecialchars($filters['citta'])
];

$userEmail = $_SESSION['email'] ?? null;



$cancelFiltriId='';
if($filters['name-event']||$filters['data_inizio']||$filters['data_fine']||$filters['citta']) {
    $cancelFiltriId="cancel-filter-visible";
} else {
    $cancelFiltriId="cancel-filter-invisible";
}

// DB CONNECTION
$connessione = new DBAccess();
if ($connessione->openDBConnection()) {
    $cities = $connessione->getEventCities();
    $eventi = $connessione->getEventsFilteredPaged($filters, $perPagina, $offset);
    $totale = $connessione->countEventsFiltered($filters);
    $connessione->closeConnection();
}

$pagineTotali = max(1, ceil($totale / $perPagina));

$options = '';
foreach ($cities as $city) {
    $options .= '<option value="' . htmlspecialchars($city) . '"></option>';
}

$eventiCards= $eventi ? buildEventsCards($eventi) : "<p class='errore'>Per ora non ci sono eventi in programma. Ritorna tra qualche giorno a controllare $totale</p>";
$linkPagine=buildPagination($pagina, $pagineTotali, $filters);







$paginaHTML = file_get_contents('./src/template/layout.html');
$main = file_get_contents('./src/template/main/eventi.html');
$footer = buildFooter($footerMenu,  './eventi');

$main = str_replace('[CITY_OPTIONS]', $options, $main);
$main = str_replace('[CITTA]', htmlspecialchars($_GET['citta'] ?? ''), $main);
$main = str_replace('[EVENTI]', $eventiCards, $main);



$main = str_replace('[LINKPAGINE]', $linkPagine, $main);

// $main = str_replace('[URL-RESETFILTRI]', $resetUrl, $main);
$main = str_replace('[VISIBILITA-FILTRO]', $cancelFiltriId, $main);
$main = str_replace(array_keys($replaceFilters), array_values($replaceFilters), $main);

$title = '<title>Eventi - PetMatch</title>';
$description = '<meta name="description" content="Eventi prossimi qui da PetMatch!">';
$keywords = '';

$nav = buildUserNav($userMenu, './eventi', $_SESSION['email'] ?? false, $_SESSION['admin']===true);
$breadcrumb = getBreadcrumb('eventi', $pagine);

$paginaHTML = str_replace(
    ['[title]', '[description]', '[keywords]', '[breadcrumb]', '[nav]', '[main]', '[footer]'],
    [$title, $description, $keywords, $breadcrumb, $nav, $main, $footer],
    $paginaHTML
);

echo $paginaHTML;
?>