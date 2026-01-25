<?php

// Avvia la sessione se non è già attiva (necessaria per il controllo admin)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$url = $_GET['url'] ?? 'home';

/**
 * legendina di definizione delle rotte.
 * 'file' => il percorso fisico dello script
 * 'params' => parametri di default se non presenti nell'URL
 * 'required_params' => parametri che DEVONO esserci, altrimenti scatta il 404
 */
$routes = [
    'home' => [
        'file' => __DIR__ . '/src/php/index.php'
    ],
    'area-riservata' => [
        'file' => __DIR__ . '/src/php/admin/area-riservata.php'
    ],
    'richieste-adozione' => [
        'file' => __DIR__ . '/src/php/admin/richieste-adozione.php'
    ],
    'animali' => [
        'file' => __DIR__ . '/src/php/animali.php',
    ],
    'lavora-con-noi' => [
        'file' => __DIR__ . '/src/php/lavora-con-noi.php'
    ],
    'come-funziona' => [
        'file' => __DIR__ . '/src/php/come-funziona.php'
    ],
    'registrati' => [
        'file' => __DIR__ . '/src/php/registrati.php'
    ],
    'preferiti' => [
        'file' => __DIR__ . '/src/php/preferiti.php'
    ],
    'senza-amministratore' => [
        'file' => __DIR__ . '/src/php/admin/senza-amministratore.php'
    ],
    'eventi' => [
        'file' => __DIR__ . '/src/php/eventi.php'
    ],
    'nuove-accoglienze' => [
        'file' => __DIR__ . '/src/php/admin/nuove-accoglienze.php'
    ],
    'accedi' => [
        'file' => __DIR__ . '/src/php/accedi.php'
    ],
    'profilo-utente' => [
        'file' => __DIR__ . '/src/php/profilo-utente.php'
    ],
    'chi-siamo' => [
        'file' => __DIR__ . '/src/php/chi-siamo.php'
    ], 
    'revisione-richiesta' => [
        'file' => __DIR__ . '/src/php/revisione-richiesta.php'
    ],
    'nuovo-animale' => [
        'file' => __DIR__ . '/src/php/admin/nuovo-animale.php'
    ],
    'modifica-animale' => [
        'file' => __DIR__ . '/src/php/admin/modifica-animale.php'
    ],
    'profilo-richiedente' => [
        'file' => __DIR__ . '/src/php/admin/profilo-richiedente.php'
    ],
    'adottati' => [
        'file' => __DIR__ . '/src/php/admin/adottati.php'
    ],
    'nuovo-evento' => [
        'file' => __DIR__ . '/src/php/admin/nuovo-evento.php'
    ],
    'assegnati-a-te' => [
        'file' => __DIR__ . '/src/php/admin/assegnati-a-te.php'
    ],
    'modifica-evento' => [
        'file' => __DIR__ . '/src/php/admin/modifica-evento.php'
    ]
];

// inizio della logica del routing
if (isset($routes[$url])) {
    $route = $routes[$url];
    if (file_exists($route['file'])) {
        require $route['file'];
        exit;
    }
}
handle404();

function handle404() {
    http_response_code(404);
   
    require __DIR__ . '/404.php';
    exit;
}
?>