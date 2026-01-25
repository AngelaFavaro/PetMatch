<?php
include './src/utils.php';
include './src/DBconnection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use DB\DBAccess;

// QUI SI GESTISCE IL METTERE TOGLIERE NEI PREFERITI
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['id-animale-preferito'])
) {
    $idAnimale = (int)$_POST['id-animale-preferito'];

    if (isset($_SESSION['email'])) {
        // Utente LOGGATO → DB
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
        // Utente NON LOGGATO = COOKIE
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
$type = $_GET['tipo'] ?? 'tutti';
$perPagina = 12;
$pagina = max(1, (int)($_GET['page'] ?? 1));
$offset = ($pagina - 1) * $perPagina;
$isPreferiti=0;
if (defined('PAGINA_PREFERITI')) {
    $isPreferiti=1;
}

$titolo = 'Animali';
if($isPreferiti) {
    $titolo.=' preferiti';
}
$messaggioNoAnimali=$isPreferiti ? 'Non hai ancora salvato nessun animale.' : 'Non abbiamo ancora animali disponibili.';



/* ------------------ FILTRI GET ------------------ */
$rawFilters = [
    'name-animal'   => $_GET['name-animal']    ?? '',
    'taglia'        => $_GET['taglia']  ?? '',
    'sesso'         => $_GET['sesso']   ?? '',
    'eta_min'       => $_GET['eta_min'] ?? '',
    'eta_max'       => $_GET['eta_max'] ?? ''
];

// per DB + paginazione

$filters = $isPreferiti ? null : array_filter(
    $rawFilters,
    fn($value) => $value !== ''
);

$cancelFiltriId='';
if($rawFilters['name-animal']||$rawFilters['taglia']||$rawFilters['sesso']||$rawFilters['eta_min']||$rawFilters['eta_max']) {
    $cancelFiltriId="cancel-filter-visible";
} else {
    $cancelFiltriId="cancel-filter-invisible";
}



/* ------------------ PREPARAZIONE REPLACE ------------------ */
// Il placeholder rimane fisso
$replaceFilters = [
    '[NAME]' => htmlspecialchars($rawFilters['name-animal']),
    '[ETA_MIN]' => htmlspecialchars($rawFilters['eta_min']),
    '[ETA_MAX]' => htmlspecialchars($rawFilters['eta_max']),

    '[TAGLIA_SELECTED_EMPTY]'   => $rawFilters['taglia'] === '' ? 'selected' : '',
    '[TAGLIA_SELECTED_PICCOLA]' => $rawFilters['taglia'] === 'Piccolo' ? 'selected' : '',
    '[TAGLIA_SELECTED_MEDIA]'   => $rawFilters['taglia'] === 'Medio' ? 'selected' : '',
    '[TAGLIA_SELECTED_GRANDE]'  => $rawFilters['taglia'] === 'Grande' ? 'selected' : '',

    '[SESSO_SELECTED_EMPTY]'    => $rawFilters['sesso'] === '' ? 'selected' : '',
    '[SESSO_SELECTED_MASCHIO]' => $rawFilters['sesso'] === 'maschio' ? 'selected' : '',
    '[SESSO_SELECTED_FEMMINA]'  => $rawFilters['sesso'] === 'femmina' ? 'selected' : '',

    '[TYPE]' => htmlspecialchars($type)
];


/* ------------------ DB ------------------ */
$cardAnimali = '';
$linkPagine  = '';


/* ------------------ NAV TIPO ------------------ */
function buildNavAnimali(
    string $type,
    bool $isPreferiti,
    array $filters = []
): string {

    // funzione che genera il link giusto
    $buildLink = function (string $t) use ($filters, $isPreferiti) {
        if ($isPreferiti) {
            // niente filtri, solo type
            return 'preferiti?tipo=' . urlencode($t);
        }

        // pagina animali: mantieni i filtri
        $params = array_merge($filters, ['tipo' => $t]);
        return '?' . http_build_query($params);
    };

    // helper per ogni voce
    $item = function (string $t, string $label) use ($type, $buildLink) {
        if ($type === $t) {
            return "<li class='currentType'>$label</li>";
        }

        return "<li><a href='{$buildLink($t)}'>$label</a></li>";
    };

    return "
    <ul aria-label='Filtri sulla tipologia'>
        {$item('tutti', 'Tutti')}
        {$item('Gatto', 'Gatti')}
        {$item('Cane', 'Cani')}
    </ul>";
}


/* ------------------ CARD ANIMALI ------------------ */
function buildAnimalCards(array $animali, ?string $email): string {
    $html = '';
    $conn = new DBAccess();

    if ($conn->openDBConnection()) {
        foreach ($animali as $a) {

            $nome  = htmlspecialchars($a['nome']);
            $sesso = $a['sesso'] === 'M' ? 'Maschio' : 'Femmina';
            $sessoAbbr = $a['sesso'] === 'M'
                ? '<abbr title="Maschio" aria-label="Maschio">M</abbr>'
                : '<abbr title="Femmina" aria-label="Femmina">F</abbr>';

            $eta = $a['eta'];
            $id  = $a['id'];

            /* -------- ADOTTATO (opzionale) -------- */
            $adottato = isset($a['adottato']) && (int)$a['adottato'] === 1;
            $cardClass = $adottato ? 'dark-card' : 'card';
            $giàInteressato= $adottato ? 'Non disponibile' : '';
            $classeInteressato= $adottato ? 'adottato' : 'interessamento';

            /* -------- INTERESSAMENTO -------- */
            
            if ($conn->hasActiveAdoptionRequest($id)) {
                $giàInteressato = $adottato ? 'Non disponibile' : 'Già Interessato';
            }

            /* -------- PREFERITI -------- */
            if ($email) {
                $inPreferiti = $conn->isAnimalInFavorites($email, $id);
            } else {
                $guestFavs = getGuestFavorites();
                $inPreferiti = in_array($id, $guestFavs);
            }

            // se adottato → niente interazione
            $classePreferito = $inPreferiti ? 'is-favorite' : 'not-favorite';
            $statusPreferiti = $inPreferiti ? 'Rimuovi dai preferiti' : 'Aggiungi ai preferiti';
            $heartNormal = $inPreferiti ? 'active-like.svg' : 'inactive-like.svg';
            $heartHover  = $inPreferiti ? 'inactive-like.svg' : 'active-like.svg';

            /* -------- IMMAGINE -------- */
            if (!empty($a['immagine']) && file_exists($a['immagine'])) {
                $img = $a['immagine'];
            } else {
                $img = ($a['tipo'] === 'Cane')
                    ? 'assets/images/animals/defaultCane.jpg'
                    : 'assets/images/animals/defaultGatto.jpg';
            }

            /* -------- HTML -------- */
            $html .= "
            <li class='$cardClass' aria-labelledby='nome-animale-$id'>
                <article aria-label='descrizione:'>
                    <div class='immagine'>
                        <img src='$img' alt=''>
                    </div>

                    <h3 class='nome' id='nome-animale-$id'>$nome</h3>";
            if(!$adottato) {
                $html.="
                    <p class='sesso-etaDesk'>$sesso - $eta anni</p>
                    <p class='sesso-etaMob'>$sessoAbbr - $eta anni</p>";
            }
            $html.="
                    <div class='cuore'>
                        <form method='post' action='animali' class='preferiti-form'>
                            <input type='hidden' name='id-animale-preferito' value='$id'>
                            <button type='submit'
                                    class='$classePreferito'
                                    aria-label='$statusPreferiti'>
                                <img class='heart-normal' src='./assets/icons/$heartNormal' alt=''>
                                <img class='heart-hover' src='./assets/icons/$heartHover' alt=''>
                            </button>
                        </form>
                    </div>

                    <p class='$classeInteressato'>$giàInteressato</p>

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
    $resetUrl .= '?tipo=' . urlencode($type);
}
$userEmail = $_SESSION['email'] ?? null;

/* ------------------ QUERY ------------------ */
$totale=0;
$connessione = new DBAccess();
if($isPreferiti) {
    if ($connessione->openDBConnection()) {
        if($userEmail) {
        $totale = $connessione->countFavourites($type, $userEmail);
        } else {
            $totale=$connessione->countGuestFavourites($type, $filters);
        }
        $pagineTotali = max(1, ceil($totale / $perPagina));
        if ($pagina > $pagineTotali) {
            $pagina = $pagineTotali;
            $offset = ($pagina - 1) * $perPagina;
        }
        if($userEmail) {
        $animali = $connessione->getFavouritesPaged($type, $perPagina, $offset, $userEmail);
        } else {
            $animali = $connessione->getGuestFavPaged($type, $perPagina, $offset);
        }
        $cardAnimali = $animali ? buildAnimalCards($animali, $userEmail) : "<p class='errore'>$messaggioNoAnimali</p>";
        $linkPagine = buildPagination($pagina, $pagineTotali, $type);
        $connessione->closeConnection();
    }
} else {
    if ($connessione->openDBConnection()) {

        $totale = $connessione->countAnimalsFiltered($type, $filters);
        $pagineTotali = max(1, ceil($totale / $perPagina));

        if ($pagina > $pagineTotali) {
            $pagina = $pagineTotali;
            $offset = ($pagina - 1) * $perPagina;
        }
        
        $animali = $connessione->getAnimalsFilteredPaged($type, $filters, $perPagina, $offset);
        $userEmail = $_SESSION['email'] ?? null;
    $cardAnimali = $animali ? buildAnimalCards($animali, $userEmail) : "<p class='errore'>$messaggioNoAnimali</p>";
        if($filters) {
        $params= array_merge(['tipo' => $type], $filters);
        } else {
            $params=$type;
        }
        $linkPagine = buildPagination($pagina, $pagineTotali, $params);
        $connessione->closeConnection();
    }
}
//  BANNER
$banneraccedi='';
if($isPreferiti&&!$userEmail&&$totale!==0) {
    $banneraccedi="<section id='invitoAdAccedere' aria-labelledby='invito-accedi-title'>
    <p id='invito-accedi-content'>
        Accedi per sincronizzare i tuoi preferiti in tutti i tuoi dispositivi!
    </p>
    <a class='orange-button' href='./accedi'>
        Accedi
    </a>
</section>";
}

/* ------------------ TEMPLATE ------------------ */


$linkNavAnimali = $isPreferiti ? buildNavAnimali($type, $isPreferiti) : buildNavAnimali($type, $isPreferiti, $filters);

$paginaHTML = file_get_contents('./src/template/layout.html');
$main = file_get_contents('./src/template/main/animali.html');
$footer = buildFooter($footerMenu,  './animali');

$main = str_replace(array_keys($replaceFilters), array_values($replaceFilters), $main);
$main = str_replace('[TITOLO]', $titolo, $main);
$main = str_replace('[ANIMALI]', $cardAnimali, $main);
$main = str_replace('[NAVTYPE]', $linkNavAnimali, $main);
$main = str_replace('[LINKPAGINE]', $linkPagine, $main);
$main = str_replace('[BANNERACCEDI]', $banneraccedi, $main);
$stringaFiltri='';
if (!$isPreferiti) {
    $stringaFiltri="<form class='filtri' method='get' action='animali'>
        <!-- rotta gestita dal router -->
        
        <input type='hidden' name='tipo' value='[TYPE]'>

        <ul aria-label='Filtri di ricerca'>
            <li class='capsula-filtro' id='searchName'>
                <label for='name-animal'>Nome</label>
                <input type='text' id='name-animal' name='name-animal' value='[NAME]' placeholder='Cerca...'>
            </li>

            <li class='capsula-filtro' id='searchSize' role='presentation'>
                <label for='taglia'>Taglia</label>
                <select id='taglia' name='taglia'>
                    <option value='' [TAGLIA_SELECTED_EMPTY]>Tutti</option>
                    <option value='Piccola' [TAGLIA_SELECTED_PICCOLA]>Piccola</option>
                    <option value='Media'   [TAGLIA_SELECTED_MEDIA]>Media</option>
                    <option value='Grande'  [TAGLIA_SELECTED_GRANDE]>Grande</option>
                </select>
            </li>

            <li class='capsula-filtro' id='searchSex' role='presentation'>
                <label for='sesso'>Sesso</label>
                <select id='sesso' name='sesso'>
                    <option value='' [SESSO_SELECTED_EMPTY]>Tutti</option>
                    <option value='maschio' [SESSO_SELECTED_MASCHIO]>Maschio</option>
                    <option value='femmina' [SESSO_SELECTED_FEMMINA]>Femmina</option>
                </select>
            </li>

            <li class='capsula-filtro' id='searchEta' role='presentation'>
                <label>Età</label>
                <div class='eta-range'>
                    <input type='number' name='eta_min' 
                           value='[ETA_MIN]' 
                           placeholder='Da' min='0' aria-label='Età minima'>
                    <span aria-hidden=true>–</span>
                    <input type='number' name='eta_max' 
                           value='[ETA_MAX]' 
                           placeholder='A' min='0' aria-label='Età massima'>
                </div>
            </li>
        </ul>

        <div id='content-filter-button'>
            <a href='[URL-RESETFILTRI]' id='[VISIBILITA-FILTRO]' aria-label='elimina i filtri'>X</a>
            <button type='submit' class='orange-button'>Cerca</button>
        </div>
    </form>";
    $stringaFiltri = str_replace(array_keys($replaceFilters), array_values($replaceFilters), $stringaFiltri);
    $main = str_replace('[FILTRI]', $stringaFiltri, $main);
    $main = str_replace('[URL-RESETFILTRI]', $resetUrl, $main);
    $main = str_replace('[VISIBILITA-FILTRO]', $cancelFiltriId, $main);

$title = '<title>Animali - PetMatch</title>';
$description = '<meta name="description" content="Animali in adozione su PetMatch">';
} else {
$main = str_replace('[FILTRI]', $stringaFiltri, $main);
$title = '<title>Animali preferiti - PetMatch</title>';
$description = '<meta name="description" content="i tuoi animali preferiti in adozione su PetMatch">';

}
$keywords = '';

$nav = $isPreferiti ? buildNav($userMenu, './preferiti') : buildNav($userMenu, './animali');
$breadcrumb = $isPreferiti ? getBreadcrumb('preferiti', $pagine) : getBreadcrumb('animali', $pagine);

$paginaHTML = str_replace(
    ['[title]', '[description]', '[keywords]', '[breadcrumb]', '[nav]', '[main]', '[footer]'],
    [$title, $description, $keywords, $breadcrumb, $nav, $main, $footer],
    $paginaHTML
);

echo $paginaHTML;
?>