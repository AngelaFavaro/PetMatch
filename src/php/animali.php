<?php
include './src/utils.php';
include './src/DBconnection.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use DB\DBAccess;

$animali ='';
$symbol='';
$queryResult='';
$result='';
$type = $_GET['type'] ?? "tutti";
$linkNavAnimali='';

$perPagina = 12;
$pagina = max(1, (int)($_GET['page'] ?? 1));
$offset = ($pagina - 1) * $perPagina;
$linkPagine='';
$totale=0;




function buildPagination(int $currentPage, int $totalPages, string $type = 'tutti'): string {
    if ($totalPages <= 1) {
        return '<li id="currentLink">1</li>';
    }

    // costruzione base URL
    $baseUrl = 'animali';
    $typeParam = ($type !== 'tutti') ? '&type=' . urlencode($type) : '';

    $html = '';

    /*  freccia indietro */
    if ($currentPage > 1) {
        $prev = $currentPage - 1;
        $html .= '
        <li id="scrollLinksBackward">
            <a href="' . $baseUrl . '?page=' . $prev . $typeParam . '">
                <img src="./assets/icons/arrow-sx-green.svg" class="next-link" alt="Pagina precedente" />
            </a>
        </li>';
    }

    /* numeri di pagina */
    if ($currentPage <= 2) {
        $start = 1;
    } elseif ($currentPage >= $totalPages - 1) {
        $start = max(1, $totalPages - 2);
    } else {
        $start = $currentPage - 1;
    }

    $end = min($totalPages, $start + 2);

    for ($i = $start; $i <= $end; $i++) {
        if ($i === $currentPage) {
            $html .= '<li id="currentLink">' . $i . '</li>';
        } else {
            $html .= '<li><a href="' . $baseUrl . '?page=' . $i . $typeParam . '">' . $i . '</a></li>';
        }
    }

    /* freccia avanti */
    if ($currentPage < $totalPages) {
        $next = $currentPage + 1;
        $html .= '
        <li id="scrollLinksForward">
            <a href="' . $baseUrl . '?page=' . $next . $typeParam . '">
                <img src="./assets/icons/arrow-dx-green.svg" class="next-link" alt="Pagina successiva" />
            </a>
        </li>';
    }

    // $html .= '</ul></div>';

    return $html;
}



function buildNavAnimali(string $type): string {
    if ($type === "tutti") {
        return '
        <ul>
            <li class="currentType">Tutti</li>
            <li><a href="animali?type=Gatto">Gatti</a></li>
            <li><a href="animali?type=Cane">Cani</a></li>
        </ul>';
    }

    if ($type === "Cane") {
        return '
        <ul>
            <li><a href="animali">Tutti</a></li>
            <li><a href="animali?type=Gatto">Gatti</a></li>
            <li class="currentType">Cani</li>
        </ul>';
    }

    if ($type === "Gatto") {
        return '
        <ul>
            <li><a href="animali">Tutti</a></li>
            <li class="currentType">Gatti</li>
            <li><a href="animali?type=Cane">Cani</a></li>
        </ul>';
    }

    return '';
}


function buildAnimalCards(array $animali): string {
    $html = '';

    foreach ($animali as $animale) {

        $nome = htmlspecialchars($animale['nome']);
        $sesso = ($animale['sesso'] === 'M') ? 'Maschio' : 'Femmina';
        $eta = $animale['eta'];
        // Se l'immagine esiste la usiamo, altrimenti scegliamo default in base al tipo
        if (!empty($animale['immagine'])) {
            $imgPath = htmlspecialchars($animale['immagine']);
        } else {
            switch ($animale['Tipo']) {
                case 'Gatto':
                    $imgPath = 'assets/images/animals/defaultGatto.png';
                    break;
                case 'Cane':
                    $imgPath = 'assets/images/animals/defaultCane.png';
                    break;
                default:
                    $imgPath = 'assets/images/animals/defaultGatto.png';
                    break;
            }
        }


        // se NON hai l'id, puoi metterne uno fittizio
        $id = $animale['id'] ?? 0;

        $html .= '
        <li class="card">
            <ul>
                <li class="immagine">
                    <img src="' . $imgPath . '" alt="Foto di ' . $nome . '">
                </li>
                <li class="nome">' . $nome . '</li>
                <li class="sesso-eta">' . $sesso . ' - ' . $eta . ' anni</li>
                <li class="interessamento">Già Interessato</li>

                <li class="preferiti-bottone">
                    <form method="post" action="animali.php">
                        <input type="hidden" name="id_elemento" value="' . $id . '">
                        <button type="submit" class="preferiti">
                            <img src="./assets/icons/active-like.svg" class="heart-hover" alt="" />
                            <img src="./assets/icons/inactive-like.svg" class="heart-normal" alt="" />
                        </button>
                    </form>
                </li>

                <li class="dettagli-animale-bottone">
                    <form method="post" action="animali.php">
                        <input type="hidden" name="id_elemento_cliccato" value="' . $id . '">
                        <button type="submit" class="bottone-dettagli"></button>
                    </form>
                </li>
            </ul>
        </li>';
    }

    return $html;
}

$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
if ($connessioneOK) {
    // $animali = $connessione->getListAnimalsByType($type);
    $totale = $connessione->countAnimalsByType($type);
    $pagineTotali = ceil($totale / $perPagina);

    $animali = $connessione->getAnimalsByTypePaged($type, $perPagina, $offset);

    if ($animali === null) {
        // Messaggio di errore dentro la stessa variabile che verrà sostituita nel template
        $cardAnimali = '<p class="errore">Errore durante il recupero degli animali</p>';
    } else {
        // Genera l'HTML per tutti gli animali
        $cardAnimali = buildAnimalCards($animali);
    }
} else {
    $cardAnimali = '<p class="errore">Errore di connessione al database</p>';
}

$linkNavAnimali=buildNavAnimali($type);
$linkPagine=buildPagination($pagina, $pagineTotali, $type);

$paginaHTML = file_get_contents('./src/template/layout.html');
if ($paginaHTML === false) {
	$paginaHTML = "<p>Errore: template layout.html non trovato o non leggibile.</p>";
}

$title = '<title>Animali - PetMatch </title>';

$description = '<meta name="description" content="Tutti gli animali in adozione qui da PetMatch!!">';
$keywords = "";


$nav = buildUserNav($userMenu, './animali', $_SESSION['email'] ?? false);

$breadcrumb = getBreadcrumb('animali', $pagine);

$main = file_get_contents('./src/template/main/animali.html');
$main = str_replace('[ANIMALI]', $cardAnimali, $main);
$main = str_replace('[NAVTYPE]', $linkNavAnimali, $main);
$main = str_replace('[LINKPAGINE]', $linkPagine, $main);

$footer= file_get_contents('./src/template/partials/footer.html');


$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

echo $paginaHTML;
?>