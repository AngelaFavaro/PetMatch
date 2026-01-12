<?php
include './src/utils.php';
include './src/DBconnection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use DB\DBAccess;

/* ------------------ PARAMETRI BASE ------------------ */
$type = $_GET['type'] ?? 'tutti';
$perPagina = 12;
$pagina = max(1, (int)($_GET['page'] ?? 1));
$offset = ($pagina - 1) * $perPagina;

/* ------------------ FILTRI GET ------------------ */
$filters = [
    'name'    => $_GET['name']    ?? '',
    'taglia'  => $_GET['taglia']  ?? '',
    'sesso'   => $_GET['sesso']   ?? '',
    'eta_min' => $_GET['eta_min'] ?? '',
    'eta_max' => $_GET['eta_max'] ?? ''
];

/* ------------------ PREPARAZIONE REPLACE ------------------ */
// Il placeholder rimane fisso
$replaceFilters = [
    '[NAME]' => htmlspecialchars($filters['name']),
    '[ETA_MIN]' => htmlspecialchars($filters['eta_min']),
    '[ETA_MAX]' => htmlspecialchars($filters['eta_max']),

    '[TAGLIA_SELECTED_EMPTY]'   => $filters['taglia'] === '' ? 'selected' : '',
    '[TAGLIA_SELECTED_PICCOLA]' => $filters['taglia'] === 'Piccola' ? 'selected' : '',
    '[TAGLIA_SELECTED_MEDIA]'   => $filters['taglia'] === 'Media' ? 'selected' : '',
    '[TAGLIA_SELECTED_GRANDE]'  => $filters['taglia'] === 'Grande' ? 'selected' : '',

    '[SESSO_SELECTED_EMPTY]'    => $filters['sesso'] === '' ? 'selected' : '',
    '[SESSO_SELECTED_MASCCHIO]' => $filters['sesso'] === 'maschio' ? 'selected' : '',
    '[SESSO_SELECTED_FEMMINA]'  => $filters['sesso'] === 'femmina' ? 'selected' : '',

    '[TYPE]' => htmlspecialchars($type)
];

/* ------------------ DB ------------------ */
$cardAnimali = '';
$linkPagine  = '';

/* ------------------ PAGINAZIONE ------------------ */
function buildPagination(int $currentPage, int $totalPages, string $type, array $filters): string {
    if ($totalPages <= 1) return '<li id="currentLink">1</li>';

    $params = array_merge(['url' => 'animali', 'type' => $type], $filters);
    unset($params['page']);

    $html = '';

    if ($currentPage > 1) {
        $params['page'] = $currentPage - 1;
        $html .= '<li><a href="?' . http_build_query($params) . '"><img src="./assets/icons/arrow-sx-green.svg"></a></li>';
    }

    for ($i = max(1, $currentPage - 1); $i <= min($totalPages, $currentPage + 1); $i++) {
        if ($i === $currentPage) {
            $html .= '<li id="currentLink">'.$i.'</li>';
        } else {
            $params['page'] = $i;
            $html .= '<li><a href="?' . http_build_query($params) . '">'.$i.'</a></li>';
        }
    }

    if ($currentPage < $totalPages) {
        $params['page'] = $currentPage + 1;
        $html .= '<li><a href="?' . http_build_query($params) . '"><img src="./assets/icons/arrow-dx-green.svg"></a></li>';
    }

    return $html;
}

/* ------------------ NAV TIPO ------------------ */
function buildNavAnimali(string $type, array $filters): string {
    $base = array_merge(['url' => 'animali'], $filters);
    $link = fn($t) => '?' . http_build_query(array_merge($base, ['type' => $t]));

    return "
    <ul>
        <li class='".($type==='tutti'?'currentType':'')."'>".
            ($type==='tutti'?'Tutti':'<a href="'.$link('tutti').'">Tutti</a>')."
        </li>
        <li class='".($type==='Gatto'?'currentType':'')."'>".
            ($type==='Gatto'?'Gatti':'<a href="'.$link('Gatto').'">Gatti</a>')."
        </li>
        <li class='".($type==='Cane'?'currentType':'')."'>".
            ($type==='Cane'?'Cani':'<a href="'.$link('Cane').'">Cani</a>')."
        </li>
    </ul>";
}

/* ------------------ CARD ANIMALI ------------------ */
function buildAnimalCards(array $animali): string {
    $html = '';
    foreach ($animali as $a) {
        $nome = htmlspecialchars($a['nome']);
        $sesso = $a['sesso'] === 'M' ? 'Maschio' : 'Femmina';
        $eta = $a['eta'];

        if (!empty($a['immagine']) && file_exists($a['immagine'])) {
            $img = $a['immagine'];
        } else {
            $img = ($a['tipo']==='Cane')
            ? 'assets/images/animals/defaultCane.jpg'
            : 'assets/images/animals/defaultGatto.jpg';
        }

        $html .= "
        <li class='card'>
            <ul>
                <li class='immagine'><img src='$img' alt='$nome'></li>
                <li class='nome'>$nome</li>
                <li class='sesso-eta'>$sesso - $eta anni</li>
            </ul>
        </li>";
    }
    return $html;
}

/* ------------------ QUERY ------------------ */
$connessione = new DBAccess();
if ($connessione->openDBConnection()) {

    $totale = $connessione->countAnimalsFiltered($type, $filters);
    $pagineTotali = max(1, ceil($totale / $perPagina));

    if ($pagina > $pagineTotali) {
        $pagina = $pagineTotali;
        $offset = ($pagina - 1) * $perPagina;
    }

    $animali = $connessione->getAnimalsFilteredPaged($type, $filters, $perPagina, $offset);
    $cardAnimali = $animali ? buildAnimalCards($animali) : '<p class="errore">Nessun animale trovato</p>';
    $linkPagine = buildPagination($pagina, $pagineTotali, $type, $filters);
}

/* ------------------ TEMPLATE ------------------ */
$linkNavAnimali = buildNavAnimali($type, $filters);
$paginaHTML = file_get_contents('./src/template/layout.html');
$main = file_get_contents('./src/template/main/animali.html');
$footer = file_get_contents('./src/template/partials/footer.html');

$main = str_replace(array_keys($replaceFilters), array_values($replaceFilters), $main);
$main = str_replace('[ANIMALI]', $cardAnimali, $main);
$main = str_replace('[NAVTYPE]', $linkNavAnimali, $main);
$main = str_replace('[LINKPAGINE]', $linkPagine, $main);

$title = '<title>Animali - PetMatch</title>';
$description = '<meta name="description" content="Tutti gli animali in adozione su PetMatch">';
$keywords = '';

$nav = buildUserNav($userMenu, './animali', $_SESSION['email'] ?? false);
$breadcrumb = getBreadcrumb('animali', $pagine);

$paginaHTML = str_replace(
    ['[title]', '[description]', '[keywords]', '[breadcrumb]', '[nav]', '[main]', '[footer]'],
    [$title, $description, $keywords, $breadcrumb, $nav, $main, $footer],
    $paginaHTML
);

echo $paginaHTML;
