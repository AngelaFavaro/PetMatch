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
    'lavora-con-noi' => [
        'file' => __DIR__ . '/src/php/lavora-con-noi.php'
    ],
    'registrati' => [
        'file' => __DIR__ . '/src/php/registrati.php'
    ],
    'accedi' => [
        'file' => __DIR__ . '/src/php/accedi.php'
    ],
    'profilo-utente' => [
        'file' => __DIR__ . '/src/php/profilo-utente.php'
    ],
    'revisione-richiesta' => [
        'file' => __DIR__ . '/src/php/revisione-richiesta.php'
    ]
    // 'animali' => [
    //     'file' => __DIR__ . '/src/php/animali.php',
    // ],
    // 'animali/cani' => [
    //     'file' => __DIR__ . '/src/php/animali.php',
    //     'params' => [
    // ],
    // 'animali/gatti' => [
    //     'file' => __DIR__ . '/src/php/animali.php',
    // ]
];

// inizio della logica del routing
if (isset($routes[$url])) {
    $route = $routes[$url];

    // controllo i parametri obbligatori (tipo in dettagli-richiesta non voglio che manchi email o id-animale)
    // Se la rotta richiede parametri che non sono presenti in $_GET, mandiamo al 404
    if (isset($route['required_params'])) {
        foreach ($route['required_params'] as $param) {
            if (!isset($_GET[$param]) || trim($_GET[$param]) === '') {
                handle404();
            }
        }
    }

    // lo lascio ma forse non serve piu
    if (isset($route['params'])) {
        foreach ($route['params'] as $key => $value) {
            if (!isset($_GET[$key])) {
                $_GET[$key] = $value;
            }
        }
    }
    if (file_exists($route['file'])) {
        require $route['file'];
        exit;
    }
}
handle404();

function handle404() {
    http_response_code(404);
    // Assicurati che questo file esista!
   
    require __DIR__ . '/404.php';
    exit;
}
?>