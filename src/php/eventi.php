<?php
include './src/utils.php';
include './src/DBconnection.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use DB\DBAccess;

$isAdmin = (isset($_SESSION['admin']) && $_SESSION['admin'] === true);

function buildFilterNav(string $filtro): string {

    $html = '
        <nav id="nav-event-type">
            <ul aria-label=\'Filtri sulla tipologia\'>';
                $html .= $filtro==='prossimi'?'<li class="currentType">':'<li>';
                $html .= $filtro==='terminati'?'<a href="./eventi?tipo='.urlencode('prossimi').'">':'';
                $html .='Prossimi eventi'; 
                $html .= $filtro==='terminati'?'</a></li><li class="currentType">':'</li><li>';
                $html .= $filtro==='prossimi'?'<a href="./eventi?tipo='.urlencode('terminati').'">':'';
                $html .='Eventi terminati'; 
                $html .= $filtro==='prossimi'?'</a>':'';
                $html .= '</li>
            </ul>
        </nav>';
    return $html;
}


function buildEventsCards($events, $filtro): string {
    $html='';
    foreach ($events as $e) {
        $img='';
        if (!empty($e['immagine']) && file_exists($e['immagine'])) {
            $img = $e['immagine'];
        } else {
            $img = 'assets/images/animals/defaultCane.jpg';
        }
        $citta=$e['citta'];
        $data=formattaDataItaliana($e['data_evento']);
        $dataAbbr=date("d/m/Y", strtotime($e['data_evento']));
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
                            $citta, $dataAbbr
                        </p>

                        <!-- Descrizione dell'evento -->
                        <div class='descrizione-evento'>
                            <h2 id='evento-titolo-$titolo'>$titolo</h2>
                            <p>$descrEvento</p>
                        </div>
                        <div class='dettagli-evento-bottone'>
                        <a href='./visualizzazione-evento?titolo=$titolo&data=$data'>Vedi dettagli</a>
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

$filtersPerTitle = [
    'search'   => $_GET['search']    ?? '',
    'data_inizio'       => $_GET['data_inizio'] ?? '',
    'data_fine'       => $_GET['data_fine'] ?? '',
    'citta'       => '',
    'tipo'       => $_GET['tipo']?? 'tutti'
    ];
    
$filtersPercity = [
    'search'   => '',
    'data_inizio'       => $_GET['data_inizio'] ?? '',
    'data_fine'       => $_GET['data_fine'] ?? '',
    'citta'       => $_GET['search'] ?? '',
    'tipo'       => $_GET['tipo']?? 'tutti'
];

$replaceFilters = [ //DA CAMBIARE
    '[NAME]' => htmlspecialchars($filtersPerTitle['search']),
    '[DATA_INIZIO]' => htmlspecialchars($filtersPerTitle['data_inizio']),
    '[DATA_FINE]' => htmlspecialchars($filtersPerTitle['data_fine']),

    '[TYPE]' => htmlspecialchars($_GET['tipo']?? 'tutti')
];

$userEmail = $_SESSION['email'] ?? null;



$cancelFiltriId='';
if($filtersPerTitle['search']||$filtersPerTitle['data_inizio']||$filtersPerTitle['data_fine']||$filtersPercity['citta']) {
    $cancelFiltriId="cancel-filter-visible";
} else {
    $cancelFiltriId="cancel-filter-invisible";
}

// DB CONNECTION
$connessione = new DBAccess();
if ($connessione->openDBConnection()) {
    $eventi = $connessione->getEventsFilteredPaged($filtersPerTitle, $perPagina, $offset);
    if(!$eventi) {
        $eventi=$connessione->getEventsFilteredPaged($filtersPercity, $perPagina, $offset);
        $totale = $connessione->countEventsFiltered($filtersPercity);
    } else{
    $totale = $connessione->countEventsFiltered($filtersPerTitle);
    }
    $connessione->closeConnection();
}

$pagineTotali = max(1, ceil($totale / $perPagina));

$filtro = 'tutti';
if($isAdmin){
    $filtro = isset($_GET['tipo'])?$_GET['tipo'] : 'prossimi';
    $navEvent = buildFilterNav($filtro);
}

$eventiCards= $eventi ? buildEventsCards($eventi, $filtro) : "<p class='errore'>Per ora non ci sono eventi in programma. Torna a controllare tra qualche giorno!</p>";
$linkPagine=buildPagination($pagina, $pagineTotali, $filtersPerTitle);







$paginaHTML = $isAdmin? file_get_contents('./src/template/layout-admin.html') : file_get_contents('./src/template/layout.html');
$main = file_get_contents('./src/template/main/eventi.html');
$footer = $isAdmin? '' : buildFooter($footerMenu,  './eventi');


$main = str_replace('[EVENTI]', $eventiCards, $main);



$main = str_replace('[LINKPAGINE]', $linkPagine, $main);

// $main = str_replace('[URL-RESETFILTRI]', $resetUrl, $main);
$main = str_replace('[VISIBILITA-FILTRO]', $cancelFiltriId, $main);
$main = str_replace(array_keys($replaceFilters), array_values($replaceFilters), $main);

$title = '<title>Eventi - PetMatch</title>';
$description = $isAdmin? '<meta name="description" content="Organizza tutti gli eventi di PetMatch">': '<meta name="description" content="Eventi prossimi qui da PetMatch!">';
$keywords = '';

$nav = $isAdmin? buildAdminNav($adminMenu, './eventi') : buildNav($userMenu, './eventi');
$breadcrumb = getBreadcrumb('eventi', $pagine);

$paginaHTML = str_replace(
    ['[title]', '[description]', '[keywords]', '[breadcrumb]', '[nav]', '[main]', '[footer]'],
    [$title, $description, $keywords, $breadcrumb, $nav, $main, $footer],
    $paginaHTML
);

$paginaHTML = str_replace('id="new-event-button"', $isAdmin?'id="new-event-button"':'id="AdminMode"', $paginaHTML);
$paginaHTML = str_replace('[TitoloEventi]', $isAdmin?'Eventi':'Prossimi eventi', $paginaHTML);
$paginaHTML = str_replace('[nav-admin-events]', $isAdmin? $navEvent:'', $paginaHTML);


echo $paginaHTML;
?>