<?php
include './src/utils.php';
include './src/DBconnection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use DB\DBAccess;


if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['id-animale-preferito'])
) {
    $idAnimale = (int)$_POST['id-animale-preferito'];

    if (isset($_SESSION['email'])) {
        // UTENTE LOGGATO → DB
        $email = $_SESSION['email'];

        $conn = new DBAccess();
        if ($conn->openDBConnection()) {
            if ($conn->isAnimalInFavorites($email, $idAnimale)) {
                $conn->removeFromFavorites($email, $idAnimale);
                $azione = 'rimosso';
            } else {
                $conn->addToFavorites($email, $idAnimale);
                $azione = 'aggiunto';
            }
            $conn->closeConnection();
        }

    } else {
        // UTENTE NON LOGGATO → COOKIE
        $preferiti = getGuestFavorites();

        if (in_array($idAnimale, $preferiti)) {
            // rimuovi
            $preferiti = array_diff($preferiti, [$idAnimale]);
            $azione = 'rimosso';
        } else {
            // aggiungi
            $preferiti[] = $idAnimale;
            $azione = 'aggiunto';
        }

        saveGuestFavorites($preferiti);
    }

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'azione' => $azione]);
        exit; 
    }

    //torna dov'eri
    $redirect = $_SERVER['HTTP_REFERER'] ?? 'animali';
    header("Location: $redirect");
    exit;
}







/* ------------------ PARAMETRI BASE ------------------ */
$type = $_GET['type'] ?? 'tutti';
$perPagina = 12;
$pagina = max(1, (int)($_GET['page'] ?? 1));
$offset = ($pagina - 1) * $perPagina;

/* ------------------ FILTRI GET ------------------ */
$rawFilters = [
    'name-animal'   => $_GET['name-animal']    ?? '',
    'taglia'        => $_GET['taglia']  ?? '',
    'sesso'         => $_GET['sesso']   ?? '',
    'eta_min'       => $_GET['eta_min'] ?? '',
    'eta_max'       => $_GET['eta_max'] ?? ''
];

// per DB + paginazione
$filters = array_filter(
    $rawFilters,
    fn($value) => $value !== ''
);



/* ------------------ PREPARAZIONE REPLACE ------------------ */
// Il placeholder rimane fisso
$replaceFilters = [
    '[NAME]' => htmlspecialchars($rawFilters['name-animal']),
    '[ETA_MIN]' => htmlspecialchars($rawFilters['eta_min']),
    '[ETA_MAX]' => htmlspecialchars($rawFilters['eta_max']),

    '[TAGLIA_SELECTED_EMPTY]'   => $rawFilters['taglia'] === '' ? 'selected' : '',
    '[TAGLIA_SELECTED_PICCOLA]' => $rawFilters['taglia'] === 'Piccola' ? 'selected' : '',
    '[TAGLIA_SELECTED_MEDIA]'   => $rawFilters['taglia'] === 'Media' ? 'selected' : '',
    '[TAGLIA_SELECTED_GRANDE]'  => $rawFilters['taglia'] === 'Grande' ? 'selected' : '',

    '[SESSO_SELECTED_EMPTY]'    => $rawFilters['sesso'] === '' ? 'selected' : '',
    '[SESSO_SELECTED_MASCCHIO]' => $rawFilters['sesso'] === 'maschio' ? 'selected' : '',
    '[SESSO_SELECTED_FEMMINA]'  => $rawFilters['sesso'] === 'femmina' ? 'selected' : '',

    '[TYPE]' => htmlspecialchars($type)
];


/* ------------------ DB ------------------ */
$cardAnimali = '';
$linkPagine  = '';
// -------------------FUNZIONI--------------------------
/* ------------------ PAGINAZIONE ------------------ */
function buildPagination(int $currentPage, int $totalPages, string $type, array $filters): string {
    if ($totalPages <= 1) return '<li id="currentLink">1</li>';

    $params = array_merge(
    ['type' => $type],
    $filters
);

    unset($params['page']);

    $html = '';

    if ($currentPage > 1) {
        $params['page'] = $currentPage - 1;
        $html .= '<li><a href="?' . http_build_query($params) . '"><img src="./assets/icons/arrow-sx-green.svg" alt="vai alla pagina precedente" /></a></li>';
    }

    for ($i = max(1, $currentPage - 1); $i <= min($totalPages, $currentPage + 1); $i++) {
        if ($i === $currentPage) {
            $html .= '<li id="currentLink" aria-label="pagina attuale">'.$i.'</li>';
        } else {
            $params['page'] = $i;
            $html .= '<li><a href="?' . http_build_query($params) . '" aria-label="vai alla pagina'.$i.'">'.$i.'</a ></li>';
        }
    }

    if ($currentPage < $totalPages) {
        $params['page'] = $currentPage + 1;
        $html .= '<li><a href="?' . http_build_query($params) . '"><img src="./assets/icons/arrow-dx-green.svg" alt="vai alla pagina successiva"/></a></li>';
    }

    return $html;
}
/* ------------------ NAV TIPO ------------------ */
function buildNavAnimali(string $type, array $filters): string {
    $base = $filters;

    $link = fn($t) => '?' . http_build_query(array_merge($base, ['type' => $t]));

    return "
    <ul aria-label='Filtri sulla tipologia'>
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
function buildAnimalCards(array $animali, ?string $email): string {
    $html = '';
    $conn = new DBAccess();
   
    if ($conn->openDBConnection()) {
        foreach ($animali as $a) {
            $nome = htmlspecialchars($a['nome']);
            $sesso = $a['sesso'] === 'M' ? 'Maschio' : 'Femmina';
            $sessoAbbr = $a['sesso'] === 'M' ? '<abbr title="Maschio" aria-label="Maschio">M</abbr>' :  '<abbr title="Femmina" aria-label="Femmina">F</abbr>';
            $eta = $a['eta'];
            $id = $a['id'];
            $giàInteressato = '';
            if ($conn->hasActiveAdoptionRequest($id)) {
                $giàInteressato = 'Già Interessato';
            }
            

            if ($email) {
                $inPreferiti = $conn->isAnimalInFavorites($email, $id);
            } else {
                $guestFavs = getGuestFavorites();
                $inPreferiti = in_array($id, $guestFavs);
            }

            $classePreferito = $inPreferiti ? 'is-favorite' : 'not-favorite';
            $heartNormal = $inPreferiti ? 'active-like.svg' : 'inactive-like.svg';
            $heartHover = $inPreferiti ? 'inactive-like.svg' : 'active-like.svg';
            $statusPreferiti = $inPreferiti ? 'Rimuovi dai preferiti' : 'Aggiungi ai preferiti';

            if (!empty($a['immagine']) && file_exists($a['immagine'])) {
                $img = $a['immagine'];
            } else {
                $img = ($a['tipo']==='Cane') ? 'assets/images/animals/defaultCane.jpg' : 'assets/images/animals/defaultGatto.jpg';
            }

            $html .= "
            <li class='card' aria-labelledby='nome-animale-$id'>
                <article aria-label='descrizione:'>
                    <div class='immagine'><img src='$img' alt=''></div>
                    <h3 class='nome' id='nome-animale-$id'>$nome</h3>
                    <p class='sesso-etaDesk'>$sesso - $eta anni</p>
                    <p class='sesso-etaMob'> $sessoAbbr - $eta anni</p>
                    <div>
                        <form method='post' action='animali' class='preferiti-form'>
                            <input type='hidden' name='id-animale-preferito' value='$id'>
                            <button type='submit' class='$classePreferito' aria-label='$statusPreferiti'>
                                <img id='heart-normal' src='./assets/icons/$heartNormal' alt=''>
                                <img id='heart-hover' src='./assets/icons/$heartHover' alt=''>
                            </button>
                        </form>
                    </div>
                    <p class='interessamento'>$giàInteressato</p>
                    <div class='dettagli-animale-bottone'>
                        <a href='visualizzazione-animale?id=$id'>Vedi dettagli</a>
                    </div>
                </article>
            </li>";
        }
        $conn->closeConnection();
    }
    return $html;
}

// RESET DEI FILTRI
$resetUrl = './animali';
if ($type !== 'tutti') {
    $resetUrl .= '?type=' . urlencode($type);
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
    $userEmail = $_SESSION['email'] ?? null;
$cardAnimali = $animali ? buildAnimalCards($animali, $userEmail) : '<p class="errore">Non abbiamo ancora animali disponibili.</p>';

    $linkPagine = buildPagination($pagina, $pagineTotali, $type, $filters);
}
$connessione->closeConnection();
/* ------------------ TEMPLATE ------------------ */
$linkNavAnimali = buildNavAnimali($type, $filters);
$paginaHTML = file_get_contents('./src/template/layout.html');
$main = file_get_contents('./src/template/main/animali.html');
$footer = file_get_contents('./src/template/partials/footer.html');

$main = str_replace(array_keys($replaceFilters), array_values($replaceFilters), $main);
$main = str_replace('[ANIMALI]', $cardAnimali, $main);
$main = str_replace('[NAVTYPE]', $linkNavAnimali, $main);
$main = str_replace('[LINKPAGINE]', $linkPagine, $main);

$main = str_replace('[URL-RESERFILTRI]', $resetUrl, $main);

$title = '<title>Animali - PetMatch</title>';
$description = '<meta name="description" content="Animali in adozione su PetMatch">';
$keywords = '';

$nav = buildUserNav($userMenu, './animali', $_SESSION['email'] ?? false);
$breadcrumb = getBreadcrumb('animali', $pagine);

$paginaHTML = str_replace(
    ['[title]', '[description]', '[keywords]', '[breadcrumb]', '[nav]', '[main]', '[footer]'],
    [$title, $description, $keywords, $breadcrumb, $nav, $main, $footer],
    $paginaHTML
);

echo $paginaHTML;
