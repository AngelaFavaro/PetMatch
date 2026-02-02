<?php
include './src/utils.php';
include './src/DBconnection.php';

use DB\DBAccess;
$isAdmin=0;
$action='eventi';
if (defined('ADMIN_EVENTI')) {
    $isAdmin=1;
}
if($isAdmin) {
    $action='visualizzazione-eventi';
}

$url=$isAdmin?'visualizzazione-eventi':'eventi'; 

function buildFilterNav(array $filters): string {
    $types = [
        'prossimi' => 'Prossimi eventi',
        'terminati' => 'Eventi terminati'
    ];

    $html = '<nav id="nav-event-type">
                <ul aria-label="Filtri sulla tipologia">';

    foreach ($types as $tipo => $label) {
        $queryFilters = $filters;
        $queryFilters['tipo'] = $tipo;

        $queryString = http_build_query($queryFilters);

        if (($filters['tipo'] ?? 'prossimi') === $tipo) {
            $html .= "<li class='currentType'>{$label}</li>";
        } else {
            $html .= "<li><a href='./visualizzazione-eventi?{$queryString}'>{$label}</a></li>";
        }
    }

    $html .= '    </ul>
            </nav>';

    return $html;
}




function buildEventsCards($events, $filtro, $isFromAdmin): string {
    $html='';
    foreach ($events as $e) {
        $img='';
        if (!empty($e['immagine']) && file_exists($e['immagine'])) {
            $img = $e['immagine'];
        } else {
            $img = 'assets/images/events/eventi-default.jpg';
        }
        $link=$isFromAdmin?'dettagli-evento':'visualizzazione-evento';
        $citta=htmlspecialchars($e['citta']);
        $data=formattaDataItaliana($e['data_evento']);
        $dataAbbr=date("d/m/Y", strtotime($e['data_evento']));
        $titolo=htmlspecialchars($e['titolo']);
        $titoloRaw=$e['titolo'];
        $descrEvento=htmlspecialchars($e['descrizione']);
        $html .= "<li>
                    <article class='evento'>
                        <img class='immagine-evento' src=".htmlspecialchars($img)." alt='' />

                        <p class='posizione-evento'>
                            <img src='assets/icons/position-cat.svg' alt='' class='icon-position' />
                            $citta
                        </p>
                            
                        <p class='data-evento'>
                            <img src='assets/icons/calendar.svg' alt='' class='icon-calendar' />
                            <time datetime=".htmlspecialchars($e['data_evento']).">$data</time>
                        </p>

                        <p class='posizione-data-abbr'>
                            $citta, ".htmlspecialchars($dataAbbr)."
                        </p>

                        <div class='descrizione-evento'>
                            <h2>$titolo</h2>
                            <p>$descrEvento</p>
                        </div>
                        <div class='dettagli-evento-bottone'>
                        <a href='./$link?titolo=".urlencode($titoloRaw)."&data=".htmlspecialchars($e['data_evento'])."'>Vedi dettagli $titolo</a>
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
$typefilter=$isAdmin? "<input type='hidden' name='tipo' value='[TYPE]'/>" : '';


$rawfilters= [
    'search'   => $_GET['search']    ?? '',
    'data_inizio'       => $_GET['data_inizio'] ?? '',
    'data_fine'       => $_GET['data_fine'] ?? '',
    'tipo'       => $_GET['tipo']?? 'prossimi'
    ];

$filtersPerTitle = [
    'search'   => $_GET['search']    ?? '',
    'data_inizio'       => $_GET['data_inizio'] ?? '',
    'data_fine'       => $_GET['data_fine'] ?? '',
    'citta'       => '',
    'tipo'       => $_GET['tipo']?? 'prossimi'
    ];
    
$filtersPercity = [
    'search'   => '',
    'data_inizio'       => $_GET['data_inizio'] ?? '',
    'data_fine'       => $_GET['data_fine'] ?? '',
    'citta'       => $_GET['search'] ?? '',
    'tipo'       => $_GET['tipo']?? 'prossimi'
];

$replaceFilters = [ //DA CAMBIARE
    '[NAME]' => htmlspecialchars($filtersPerTitle['search']),
    '[DATA_INIZIO]' => htmlspecialchars($filtersPerTitle['data_inizio']),
    '[DATA_FINE]' => htmlspecialchars($filtersPerTitle['data_fine']),

    '[TYPE]' => htmlspecialchars($_GET['tipo']?? 'terminati')
];

$userEmail = $_SESSION['email'] ?? null;



$cancelFiltriId='';
if($filtersPerTitle['search']||$filtersPerTitle['data_inizio']||$filtersPerTitle['data_fine']||$filtersPercity['citta']) {
    $cancelFiltriId="cancel-filter-visible";
} else {
    $cancelFiltriId="cancel-filter-invisible";
}


$filtro = 'terminati';
if($isAdmin){
    $filtro = isset($_GET['tipo'])?$_GET['tipo'] : 'prossimi';
    $navEvent = buildFilterNav($rawfilters);
    $filtersPercity=array_merge($filtersPercity, ['tipo' => $filtro]);
    $filtersPerTitle=array_merge($filtersPerTitle, ['tipo' => $filtro]);
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



$eventiCards= $eventi ? buildEventsCards($eventi, $filtro, $isAdmin) : "<p class='errore'>Per ora non ci sono eventi in programma. Torna a controllare tra qualche giorno!</p>";
$linkPagine=$pagineTotali>1? "<nav class='next-page-links' tabindex='-1' aria-label='Tutte le pagine'>
        <ul aria-label='Pagine di navigazione'>".buildPagination($pagina, $pagineTotali, $filtersPerTitle)."</ul>
    </nav>" : '';


$paginaHTML = $isAdmin? file_get_contents('./src/template/layout-admin.html') : file_get_contents('./src/template/layout.html');
$main = file_get_contents('./src/template/main/eventi.html');
$footer = $isAdmin? '' : buildFooter($footerMenu,  './eventi');

$main = str_replace('[EVENTI]', $eventiCards, $main);

$main = str_replace('[LINKPAGINE]', $linkPagine, $main);

// $main = str_replace('[URL-RESETFILTRI]', $resetUrl, $main);
$main = str_replace('[VISIBILITA-FILTRO]', $cancelFiltriId, $main);
$main = str_replace(array_keys($replaceFilters), array_values($replaceFilters), $main);

$title = '<title>Eventi - PetMatch</title>';
$description = $isAdmin? '<meta name="description" content="Pagina di amministrazione per organizzare tutti gli eventi di PetMatch">': '<meta name="description" content="Visualizzazione degli eventi prossimi organizzati da PetMatch!">';
$keywords = "<meta name='keywords' content='Prossimi eventi, eventi, cani, gatti, adozioni, PetMatch, rifugio'>";

$nav = $isAdmin? buildAdminNav($adminMenu, './visualizzazione-eventi') :  buildNav($userMenu, './eventi');
$breadcrumb = $isAdmin? getBreadcrumb('visualizzazione-eventi', $pagine) : getBreadcrumb('eventi', $pagine);

$paginaHTML = str_replace(
    ['[title]', '[description]', '[keywords]', '[breadcrumb]', '[nav]', '[main]', '[footer]'],
    [$title, $description, $keywords, $breadcrumb, $nav, $main, $footer],
    $paginaHTML
);

$paginaHTML = str_replace('id="new-event-button"', $isAdmin?'id="new-event-button"':'id="AdminMode"', $paginaHTML);
$paginaHTML = str_replace('[TYPEFILTER]', $typefilter, $paginaHTML);
$paginaHTML = str_replace('[ACTION]', $action, $paginaHTML);
if($isAdmin){
$paginaHTML = str_replace('[TYPE]', $filtro, $paginaHTML);
}
$paginaHTML = str_replace('[TitoloEventi]', $isAdmin?'Eventi':'Prossimi eventi', $paginaHTML);
$paginaHTML = str_replace('[nav-admin-events]', $isAdmin? $navEvent:'', $paginaHTML);


echo $paginaHTML;
?>