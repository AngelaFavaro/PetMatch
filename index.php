<?php

$url = $_GET['url'] ?? 'home';


// messe solo alcune, le altre sono da aggiungere
$routes = [
    'home' => [
        'file' => __DIR__ . '/src/php/index.php'
    ],
    'area-riservata' => [
        'file' => __DIR__ . '/src/php/admin/area-riservata.php'
        //qui penso che servano altri parametri ma al momento sto facendo la pagina statica -linor
    ],
    'dettagli-richiesta' => [
        'file' => __DIR__ . '/src/php/admin/dettagli-richiesta.php'
    ],
    /* decommentare quando si vogliono aggiungere le altre pagine
    'accedi' => [
        'file' => __DIR__ . '/src/php/accedi.php'
    ],
    'registrati' => [
        'file' => __DIR__ . '/src/php/registrati.php'
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
    'animali' => [
        'file' => __DIR__ . '/src/php/animali.php',
        'params' => [
            'tipo' => 'tutti'
        ]
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
];


if (isset($routes[$url])) {
    $route = $routes[$url];

    if (isset($route['params'])) {
        foreach ($route['params'] as $key => $value) {
            $_GET[$key] = $value;
        }
    }

    require $route['file'];
    exit;
}

http_response_code(404);
require __DIR__ . '/404.php';

exit;
?>