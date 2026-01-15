<?php

/**da fare (vedi ItaVolley):
    - array in cui vengono definite le pagine esistenti (utili per nav, footer e breadcrumb)
    - funzione che crea la nav per admin e per utente normale (per utente è l'header)
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
    ],



    'animali' => [
        // 'file' => __DIR__ . '/src/php/animali.php',
        'label' => 'Animali',
        'url' => './animali',
        'parent' => 'home'
    ],
    'registrati' => [
        'label' => 'Registrati',
        'url' => './registrati',
        'parent' => 'home'
    ],
    'accedi' => [
        'label' => 'Accedi',
        'url' => './accedi',
        'parent' => 'home'
    ],
    'profilo-utente' => [
        'label' => 'Profilo',
        'url' => './profilo-utente',
        'parent' => 'home'
    ],
    'revisione-richiesta' => [
        'label' => 'Revisione richiesta',
        'url' => './revisione-richiesta',
        'parent' => 'profilo-utente'
    ],
    'lavora-con-noi' => [
        //'file' => __DIR__ . '/src/php/lavora-con-noi.php',
        'label' => 'Lavora con noi',
        'url' => './lavora-con-noi',
        'parent' => 'home'
    ]



];

$adminMenu = [
    'principale' => [
        ['href' => './area-riservata', 'text' => 'Area personale'],
        ['href' => './richieste-adozione', 'text' => 'Richieste di adozione'],
        ['href' => './eventi', 'text' => 'Eventi'],
    ],
    'animali' => [
        ['href' => './tuoi-animali', 'text' => 'Assegnati a te'],
        ['href' => './animali-senza-amministratore', 'text' => 'Senza amministratore'],
        ['href' => './adottati', 'text' => 'Adottati'],
        ['href' => './nuove-accoglienze', 'text' => 'Nuove accoglienze'],
    ]
];

$userMenu = [
    ['href' => './home', 'text' => 'Home'],
    ['href' => './animali', 'text' => 'Animali'],
    ['href' => './eventi', 'text' => 'Eventi'],
    ['href' => './come-funziona', 'text' => 'Come funziona'],
    ['href' => './chi-siamo', 'text' => 'Chi siamo'],
    ['href' => './lavora-con-noi', 'text' => 'Lavora con noi'],
];

$noNav = [
    ['href' => './registrati'],
    ['href' => './accedi']
];


/**
 * Carica un file e ritorna un fallback in caso di errore
 * TO DO qui sarebbe utile inserire come path default il layout base ma prima bisognerebbe unificare i layout di admin e utente normale
 */
function loadTemplate(string $path, string $default = ''): string {
    $content = @file_get_contents($path);
    return $content === false ? $default : $content;
}


function buildAdminNav(array $menuGroups, string $currentHref): string {
    // Parte iniziale: Checkbox e Label (Hamburger)
    $html = '
    <input type="checkbox" id="menu-toggle-checkbox" class="sr-only">
    <label for="menu-toggle-checkbox" class="menu-toggle" aria-label="Apri o chiudi menu di navigazione">
    <span></span> </label>
    
    <nav id="menu-admin" aria-label="Menù">
        <a class="navigationHelp" href="#content"> Salta il menù di navigazione</a>
        <a href="./home">
            <img src="./assets/icons/logo.svg" id="logo" alt="Home" lang="en">
        </a>
        <a class="orange-button" href="./nuovo-animale">+ Aggiungi animale</a>';

    foreach ($menuGroups as $key => $items) {

        if ($key === 'animali') {
            $html .= '<span class="description-menu" aria-hidden="true">Animali</span>';
            $html .= '<ul aria-label="Menù gestione animali">';
        }else{
            $html .= '<span class="description-menu" aria-hidden="true">Principale</span>';
            $html .= '<ul aria-label="Menù principale">';
        }

        foreach ($items as $item) {
            $active = ($item['href'] === $currentHref) ? ' id="currentLink"' : '';
            $linkHref = ($item['href'] === $currentHref) ? '<li'.$active.' aria-label="pagina attuale:'.$item['text'].'">'.$item['text'].'</li>' : '<li'.$active.'><a href="'.$item['href'].'">'.$item['text'].'</a></li>';
            
            // In questa versione, anche il link corrente rimane cliccabile 
            //ho sistemato - angelac
            $html .= $linkHref;
        }
        $html .= '</ul>';
    }

    $html .= '
        <form action="./area-riservata" method="POST">
            <button type="submit" name="logout" class="logout-btn">Esci</button>
        </form>
    </nav>';

    return $html;
}


/**
 * Genera la nav menù utente dinamicamente
 */
function buildUserNav(array $items, string $currentHref, bool $isLogged): string {

    global $noNav;

    $homeHref = './home';
    $logoAttributes = ($currentHref === $homeHref)? ' id="currentLink"' : '';
    $isLogoActive =  ($currentHref === $homeHref)?                    
    
    '<div' . $logoAttributes . '>
        <img src="./assets/icons/logo.svg" id="logo-header" alt="PetMatch Home">
        <span id="name-site">Pet<span id="not-bold">Match</span></span>
    </div>' :
    
    '<a href="' . $homeHref . '"' . $logoAttributes . '>
        <img src="./assets/icons/logo.svg" id="logo-header" alt="PetMatch Home">
        <span id="name-site">Pet<span id="not-bold">Match</span></span>
    </a>';
    
    $navForm = false;
    foreach ($noNav as $noNavPage) {
        if($noNavPage['href'] === $currentHref) {
            $headerID = 'id="noNavHeader"';
            $navForm = true;
            break;
        }
        $headerID = 'id="NavHeader"';
    }
    

    if(!$navForm) {
        $html = '
        <header ' . $headerID . '>
            <a class="navigationHelp" href="#content">Salta al contenuto principale</a>
            
            <div class="container">
                
                <nav id="header-logo" aria-label="link alla home">
                    <h1>
                        '.$isLogoActive.'
                    </h1>
                </nav>
                
                <input type="checkbox" id="menu-toggle-checkbox" class="sr-only">
    
                <nav aria-label="Menù principale" id="nav-osso">
                    <ul id="osso">';
    
        // 2. parte dinamica: ciclo gli items passati come argomento
        foreach ($items as $item) {
            if($item['href'] !== './home') {
                // Controllo se è la pagina corrente
                // Se l'href corrente corrisponde, aggiungo l'ID active
                $isActive = ($item['href'] === $currentHref) ? ' id="currentLink" ' : '';
                $linkHref = ($item['href'] === $currentHref) ? '<li'.$isActive.'>'.$item['text'].'</li>' : '<li'.$isActive.'><a href="'.$item['href'].'">'.$item['text'].'</a></li>';
            
                
                $html .= $linkHref;
            }
        }
    
        // 3. Parte finale fissa (Chiusura nav, Azioni header: Tema, Preferiti, Login, Hamburger)
        $html .= '
                    </ul>
                </nav>
                
                <div id="header-actions">
                    <input type="checkbox" id="theme-toggle" class="sr-only">
                    <label for="theme-toggle" id="theme-switch" aria-label="Cambia tema">
                        <span id="slider">
                            <img src="./assets/icons/sun.svg" id="sun" alt=""/>
                            <img src="./assets/icons/moon.svg" id="moon" alt=""/>
                        </span>
                    </label>
    
                    <nav aria-label="Area personale">
                        <ul id="personal-area">
                            <li>
                                <a href="./preferiti" id="preferiti" aria-label="Preferiti">
                                    <img src="./assets/icons/heart-normal.svg" id="heart-normal" alt="" />
                                    <img src="./assets/icons/heart-hover.svg" id="heart-hover" alt="" />
                                </a>
                            </li>
                            
                            <li>
                                <a class="white-button" href="./accedi">';
                                $html .= $isLogged ? '<span id="text-accedi">Profilo</span>' : '<span id="text-accedi">Accedi</span>';
                                
                                $html .= '
                                    <img src="./assets/icons/account-normal.svg" id="account-normal" alt="" />
                                    <img src="./assets/icons/account-hover.svg" id="account-hover" alt="" />
                                </a>
                            </li>
                        </ul> 
                    </nav>
                    
                    <label for="menu-toggle-checkbox" class="menu-toggle" aria-label="Apri il menù">
                    </label>
    
                </div>
            </div>
        </header>';
    } else {
        $html = '
        <header ' . $headerID . '>
            <div class="container">
                <nav id="header-logo" aria-label="link alla home">
                    <h1>
                        <a href="./home">
                            <img src="./assets/icons/logo.svg" id="logo-header" alt="PetMatch Home">
                            <span id="name-site">Pet<span id="not-bold">Match</span></span>
                        </a>
                    </h1>
                </nav>
            </div>
        </header>';
    }

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
            return null;
        }
    }

    if (!is_writable($basePath)) {
        echo "ERRORE: La cartella esiste ma NON è scrivibile (permessi negati).<br>";
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo "ERRORE PHP nel file: Codice " . $file['error'] . "<br>";
        return null;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = $folder . "_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $extension;
    $targetFile = $basePath . $fileName;

    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        echo "SUCCESSO: File spostato correttamente!<br>";
        return $dbPathPrefix . $fileName;
    } else {
        echo "ERRORE: move_uploaded_file è fallito. Possibile causa: file temporaneo sparito o restrizioni del server.<br>";
        return null;
    }
}

function getCardAnimal():string{
    $html = '<section id=\'info-animal\'>
            <h2>Animale interessato</h2>
            <div class=\'details-card-animale\'>
                <div>
                    <div>
                        <img src="[imgAnimale]" alt="" />
                        <!-- TODO: aggiungere link alla pagina dell\'animale -->
                        <a href="" class="brown-button">Vedi animale</a>
                    </div>
                    <dl aria-label="Descizione superficiale dell\'animale">
                        <dt>Nome:</dt>
                        <dd>[nomeAnimale]</dd>
                        <dt>Sesso:</dt>
                        <dd>[SessoAnimale]</dd>
                        <dt>Età:</dt>
                        <dd>[EtàAnimale]</dd>
                        <dt>Razza:</dt>
                        <dd>[RazzaAnimale]</dd>
                        <dt>Trasporto:</dt>
                        <dd>[TrasportoAnimale]</dd>
                    </dl>
                </div>
                <dl aria-label="Informazioni approfondite sull\'animale">
                    <dt>Famiglia ideale:</dt>
                    <dd>[FamigliaIdealeAnimale]</dd>
                    <dt>Condizioni mediche:</dt>
                    <dd>[CondizioniMedicheAnimale]</dd>
                    <dt>Descrizione caratteriale:</dt>
                    <dd>[DescrizioneCaratterialeAnimale]</dd>
                </dl>
            </div>
        </section>';
    return $html;
}


function logout(){
    $_SESSION = [];
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    
    header("Location: ./home");
    exit;
}

function calcolareEta(?string $dataNascita): ?int {
    if (!$dataNascita) {
        return null;
    }

    try {
        $nascita = new DateTime($dataNascita);
        return (new DateTime())->diff($nascita)->y;
    } catch (Exception $e) {
        return null;
    }
}

// FUNZIONI PER COOKIES
// Funzione che va a prendere gli animali messi nei preferiti dal guest non loggato
function getGuestFavorites(): array {
    if (isset($_COOKIE['preferiti_guest'])) {
        $data = json_decode($_COOKIE['preferiti_guest'], true);
        return is_array($data) ? $data : [];
    }
    return [];
}

// Funzione che salva in un array cookie i preferiti di un utente non loggato
function saveGuestFavorites(array $ids): void {
    setcookie(
        'preferiti_guest',
        json_encode(array_values(array_unique($ids))),
        time() + 60 * 60 * 24 * 30, // 30 giorni
        '/'
    );
}

