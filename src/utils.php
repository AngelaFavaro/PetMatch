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
    ],



    'animali' => [
        // 'file' => __DIR__ . '/src/php/animali.php',
        'label' => 'Animali',
        'url' => './animali',
        'parent' => 'home'
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
?>