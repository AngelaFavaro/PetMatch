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
        /*'params' => [
            'tipo' => 'tutti'
        ]*/
    ],

    'lavora-con-noi' => [
        'file' => __DIR__ . '/src/php/lavora-con-noi.php'
    ],
    'registrati' => [
        'file' => __DIR__ . '/src/php/registrati.php'
    ],



    /* decommentare quando si vogliono aggiungere le altre pagine
    'accedi' => [
        'file' => __DIR__ . '/src/php/accedi.php'
    'richieste-adozione' => [
        'file' => __DIR__ . '/src/php/admin/richieste-adozione.php'
    ],
    
    'profilo' => [
        'file' => __DIR__ . '/src/php/profilo.php'
    ],
    'preferiti' => [
        'file' => __DIR__ . '/src/php/preferiti.php'
    ],
    'come-funziona' => [
        'file' => __DIR__ . '/src/php/come-funziona.php'
    ],
    'lavora-con-noi' => [
        'file' => __DIR__ . '/src/php/lavora-con-noi.php'
    ],
    



    'animali/cani' => [
        'file' => __DIR__ . '/src/php/animali.php',
        'params' => [ //in questo modo è come se l'utente avesse scritto animali.php?tipo=cani
            'tipo' => 'cani'
        ]
    ],
    'animali/gatti' => [
        'file' => __DIR__ . '/src/php/animali.php',
        'params' => [//in questo modo è come se l'utente avesse scritto animali.php?tipo=gatti
            'tipo' => 'gatti'
        ]
    ],
    'animali-preferiti' => [
        'file' => __DIR__ . '/src/php/animali.php',
        //  non so come fare qui, forse fa pagina animali.php posso controllare l'url completo per vedere
        // se l'utente ha chiesto la pagina di preferiti, altrimenti si può aggiungere un parametro pagina=preferiti ma non so se è il massimo
    ],
    'animali-preferiti/cani' => [
        'file' => __DIR__ . '/src/php/animali.php',
        'params' => [ //in questo modo è come se l'utente avesse scritto animali.php?pagina=preferiti&tipo=cani
            'tipo' => 'cani'
        ]
    ],
    'animali-preferiti/gatti' => [
        'file' => __DIR__ . '/src/php/animali.php',
        'params' => [//in questo modo è come se l'utente avesse scritto animali.php?pagina=preferiti&tipo=gatti
            'tipo' => 'gatti'
        ]
    ]*/
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