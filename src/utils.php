<?php

/**da fare (vedi ItaVolley):
    - array in cui vengono definite le pagine esistenti (utili per nav, footer e breadcrumb)
    - funzione che crea la nav per admin e per utente normale (credo)
    - funzione che crea il footer (ossia da modificare solo la parte del link circolare alla home se l'utente è già in quella pagina)
*/

/* Definizione delle pagine esistenti, aggiungerne altre quando possibile*/
$pagine = [
    'home' => [
        // 'file' => __DIR__ . '/src/php/index.php',
        'label' => 'Home', //la label e' quella che viene mostrata nella breadcrumb
        'url' => './home',
        'parent' => null 
    ],
    'area-riservata' => [
        // 'file' => __DIR__ . '/src/php/admin/area-riservata.php',
        'label' => 'Area personale',
        'url' => './area-riservata',
        'parent' => 'home'
    ],
    'richieste-adozione' => [
        // 'file' => __DIR__ . '/src/php/admin/richieste-adozione.php',
        'label' => 'Richieste di adozione',
        'url' => './richieste-adozione',
        'parent' => 'home'
    ],
    'dettagli-richiesta' => [
        // 'file' => __DIR__ . '/src/php/admin/dettagli-richiesta.php',
        'label' => 'Dettagli richiesta',
        'url' => './dettagli-richiesta',
        'parent' => 'richieste-adozione'
    ]
];

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