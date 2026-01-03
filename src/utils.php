<?php

/**da fare (vedi ItaVolley):
    - array in cui vengono definite le pagine esistenti (utili per nav, footer e breadcrumb)
    - funzione che crea la nav per admin e per utente normale (credo)
    - funzione che crea il footer (ossia da modificare solo la parte del link circolare alla home se l'utente è già in quella pagina)
*/

/* Definizione delle pagine esistenti, aggiungerne altre quando possibile*/
$pagine = [
    'home' => [
        'label' => 'Home', //la label e' quella che viene mostrata nella breadcrumb
        'url' => './home',
        'parent' => null 
    ],
    'area-riservata' => [
        'label' => 'Area personale',
        'url' => './area-riservata',
        'parent' => 'home'
    ],
    'richieste-adozione' => [
        'label' => 'Richieste di adozione',
        'url' => './richieste-adozione',
        'parent' => 'home'
    ],
    'dettagli-richiesta' => [
        'label' => 'Dettagli richiesta',
        'url' => './dettagli-richiesta',
        'parent' => 'richieste-adozione'
    ]
];

$adminMenu = [
    'principale' => [
        ['href' => './area-riservata', 'text' => 'AREA PERSONALE'],
        ['href' => './richieste-adozione', 'text' => 'RICHIESTE DI ADOZIONE'],
        ['href' => './eventi', 'text' => 'EVENTI'],
    ],
    'animali' => [
        ['href' => './tuoi-animali', 'text' => 'ASSEGNATI A TE'],
        ['href' => './animali-senza-amministratore', 'text' => 'SENZA AMMINISTRATORE'],
        ['href' => './adottati', 'text' => 'ADOTTATI'],
        ['href' => './nuove-accoglienze', 'text' => 'NUOVE ACCOGLIENZE'],
    ]
];


/**
 * Carica un file e ritorna un fallback in caso di errore
 * TO DO qui sarebbe utile inserire come path default il layout base ma prima bisognerebbe unificare i layout di admin e utente normale
 */
function loadTemplate(string $path, string $default = ''): string {
    $content = @file_get_contents($path);
    return $content === false ? $default : $content;
}

/**
 * Genera la nav menù admin dinamicamente
 */
function buildAdminNav(array $menuGroups, string $currentHref): string {
    // Parte iniziale fissa
    $html = '
    <button class="menu-toggle" id="mobile-menu" aria-label="Apri o chiudi menu di navigazione">
        ☰
    </button>
    <nav id="menu-admin" aria-label="Menù">
        <a class="navigationHelp" href="#content"> Salta il menù di navigazione</a>
        <a href="./home">
            <img src="./assets/icons/logo.svg" id="logo" alt="Home" lang="en">
        </a>
        <a class="orange-button" href="./nuovo-animale">+ Aggiungi animale</a>';

    foreach ($menuGroups as $key => $items) {

        if ($key === 'animali') {
            $html .= '<span>ANIMALI</span>';
        }

        $html .= '<ul>';
        foreach ($items as $item) {
            $active = ($item['href'] === $currentHref) ? ' id="currentLink"' : '';
            
            // In questa versione, anche il link corrente rimane cliccabile 
            // come nel tuo esempio HTML ( <li id="currentLink"><a href="...">...</a></li> )
            $html .= '<li'.$active.'><a href="'.$item['href'].'">'.$item['text'].'</a></li>';
        }
        $html .= '</ul>';
    }

    // Parte finale fissa
    $html .= '
        <a id="logout" class="orange-button" href="./home">← logout</a>
    </nav>';

    return $html;
}

function getBreadcrumb($currentPageKey, $pagine) {
    if (!isset($pagine[$currentPageKey])) {
        return ""; 
    }

    $path = [];
    $tempKey = $currentPageKey;

    while ($tempKey !== null && isset($pagine[$tempKey])) {
        $path[] = $pagine[$tempKey];
        $tempKey = $pagine[$tempKey]['parent'];
    }

    $path = array_reverse($path);

    $html = '<nav id="breadcrumb" aria-label="percorso">' . PHP_EOL;
    $html .= '    <p>Ti trovi in: ';

    $links = [];
    foreach ($path as $index => $info) {
        // Se è l'ultima pagina (quella attuale), non mettiamo il link
        if ($index === count($path) - 1) {
            $links[] = $info['label'];
        } else {
            $links[] = '<a href="' . $info['url'] . '">' . $info['label'] . '</a>';
        }
    }

    $html .= implode(' &gt;&gt; ', $links);

    $html .= '</p>' . PHP_EOL;
    $html .= '</nav>';

    return $html;
}

/** dentro a dettagli-richiesta.php ho lasciato un blocco commentato che richiama questa funzione,
 * guardate li per capire come usarla (cerca 'SCRIPT DI TEST'), l'echo che si trova in basso al blocco commentato è il form da cui vengono presi i dati
 * NOTA: possibile che l'estensione di vscode non vi faccia vedere l'immagine caricata, guardate dal terminale ssh
*/
// se $_FILES['foto'] non esiste o è vuoto, la funzione ritorna false
function uploadImage($file, $folder) {

    $basePath = dirname(__DIR__) . '/assets/images/' . $folder . '/';
    $dbPathPrefix = 'assets/images/' . $folder . '/';
    
    if (!file_exists($basePath)) {
        echo "La cartella non esiste. Provo a crearla...<br>";
        if (!mkdir($basePath, 0755, true)) {
            echo "ERRORE: Impossibile creare la cartella. Controlla i permessi di sistema.<br>";
            return false;
        }
    }

    if (!is_writable($basePath)) {
        echo "ERRORE: La cartella esiste ma NON è scrivibile (permessi negati).<br>";
        return false;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo "ERRORE PHP nel file: Codice " . $file['error'] . "<br>";
        return false;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = $folder . "_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $extension;
    $targetFile = $basePath . $fileName;

    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        echo "SUCCESSO: File spostato correttamente!<br>";
        return $dbPathPrefix . $fileName;
    } else {
        echo "ERRORE: move_uploaded_file è fallito. Possibile causa: file temporaneo sparito o restrizioni del server.<br>";
        return false;
    }
}