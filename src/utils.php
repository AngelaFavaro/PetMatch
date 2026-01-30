<?php

/**da fare (vedi ItaVolley):
    - array in cui vengono definite le pagine esistenti (utili per nav, footer e breadcrumb)
    - funzione che crea la nav per admin e per utente normale (per utente è l'header)
    - funzione che crea il footer (ossia da modificare solo la parte del link circolare alla home se l'utente è già in quella pagina)
*/

if (isset($_GET['email']) && isset($_POST['view-profile'])){
    $richiesteAdozioneHref = './richieste-adozione?email='.urlencode($_GET['email']).'&id-animale='.urlencode($_POST['id-animale']);
}else{
    $richiesteAdozioneHref = './richieste-adozione';
}

$inputJSON = file_get_contents('php://input');
$inputData = json_decode($inputJSON, true);
if (isset($inputData['toggle_theme'])) {
    
    $theme = $inputData['toggle_theme']; 
    setcookie('theme', $theme, time() + (86400 * 30), "/");
    echo json_encode(['status' => 'ok', 'theme' => $theme]);
    exit; 
}

/* Definizione delle pagine esistenti PER LA BREADCRUMB , aggiungerne altre quando possibile*/
$pagine = [
    'home' => [
        'label' => '<span lang="en">Home</span>', //la label e' quella che viene mostrata nella breadcrumb
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
        'parent' => 'area-riservata'
    ],
    'dettagli-richiesta' => [
        'label' => 'Dettagli richiesta',
        'url' => $richiesteAdozioneHref,
        'parent' => 'richieste-adozione'
    ],
    'nuovo-animale' => [
        'label' => 'Aggiungi animale',
        'url' => './nuovo-animale',
        'parent' => 'area-riservata'
    ],
    'modifica-animale' => [
        'label' => 'Modifica animale',
        'url' => './modifica-animale', 
        'parent' => 'dettagli-animale'  
    ],
    'animali' => [
        'label' => 'Animali',
        'url' => './animali',
        'parent' => 'home'
    ],
    'come-funziona' => [
        'label' => 'Come Funziona',
        'url' => './come-funziona',
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
     'chi-siamo' => [
        'label' => 'Chi Siamo',
        'url' => './chi-siamo',
        'parent' => 'home'
    ],
    'revisione-richiesta' => [
        'label' => 'Revisione richiesta',
        'url' => './revisione-richiesta',
        'parent' => 'profilo-utente'
    ],
    'preferiti' => [
        'label' => 'Preferiti',
        'url' => './preferiti',
        'parent' => 'home'
    ],
    'lavora-con-noi' => [
        'label' => 'Lavora con noi',
        'url' => './lavora-con-noi',
        'parent' => 'home'
    ],
    'visualizzazione-animale' => [
        'label' => 'Visualizzazione animale',
        'url' => './animali', 
        'parent' => 'animali'
    ],
    'senza-amministratore' => [
        'label' => 'Animali senza amministratore',
        'url' => './senza-amministratore',
        'parent' => 'area-riservata'
    ],
    'eventi' => [
        'label' => 'Eventi',
        'url' => './eventi',
        'parent' => 'home'
    ],
        'visualizzazione-evento' => [
        'label' => 'Visualizzazione evento',
        'url' => './visualizzazione-evento', 
        'parent' => 'eventi'
    ], 
    'nuove-accoglienze' => [
        'label' => 'Nuove accoglienze',
        'url' => './nuove-accoglienze',
        'parent' => 'area-riservata'
    ],
    'profilo-richiedente' => [
        'label' => 'Profilo richiedente',
        'url' => './profilo-richiedente',
        'parent' => 'dettagli-richiesta'
    ],
    'adottati' => [
        'label' => 'Adottati',
        'url' => './adottati',
        'parent' => 'area-riservata'
    ],
    'nuovo-evento' => [
        'label' => 'Nuovo evento',
        'url' => './nuovo-evento',
        'parent' => 'visualizzazione-eventi'
    ],
    'assegnati-a-te' => [
        'label' => 'Assegnati a te',
        'url' => './assegnati-a-te',
        'parent' => 'area-riservata'
    ],
    'dettagli-animale' => [
        'label' => 'Dettagli animale',
        'url' => './dettagli-animale', 
        'parent' => 'assegnati-a-te' 
    ],
    'modifica-evento' => [
        'label' => 'Modifica evento',
        'url' => './modifica-evento',
        'parent' => 'visualizzazione-eventi'
    ],
    'visualizzazione-eventi' => [
        'label' => 'Visualizzazione eventi',
        'url' => './visualizzazione-eventi',
        'parent' => 'home'
    ],
];

$adminMenu = [
    'principale' => [
        ['href' => './area-riservata', 'text' => 'Area personale'],
        ['href' => './richieste-adozione', 'text' => 'Richieste di adozione'],
        ['href' => './visualizzazione-eventi', 'text' => 'Eventi'],
    ],
    'animali' => [
        ['href' => './assegnati-a-te', 'text' => 'Assegnati a te'],
        ['href' => './senza-amministratore', 'text' => 'Senza amministratore'],
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

$footerMenu = [
    'Su di noi' => [
        ['href' => './animali', 'text' => 'I nostri animali'],
        ['href' => './eventi', 'text' => 'I nostri eventi'],
        ['href' => './come-funziona', 'text' => 'Come funziona'],
        ['href' => './chi-siamo', 'text' => 'Chi siamo']],
    'Vuoi lavorare con noi?'=>[
        ['href' => './lavora-con-noi#inizio-volontari', 'text' => 'Diventa un nostro volontario'],
        ['href' => './lavora-con-noi#inizio-sostenitori', 'text' => 'Diventa un nostro sostenitore'],]
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

    $isDark = (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark');
    // Parte iniziale: Checkbox e Label (Hamburger)
    $html = '<header>
    <input type="checkbox" id="menu-toggle-checkbox" class="sr-only" />

    <div id="log-theme">
    <input type="checkbox" id="theme-toggle" class="sr-only"';
    $html .= $isDark? ' checked />':'/>';
    $html .= '
        <label for="theme-toggle" id="theme-switch">
            <span class="sr-only">Cambia tema</span>
            <span id="slider">
                <img src="./assets/icons/sun.svg" id="sun" alt=""/>
                <img src="./assets/icons/moon.svg" id="moon" alt=""/>
            </span>
        </label>
    </div>
    <label for="menu-toggle-checkbox" class="menu-toggle">
    <span class="sr-only">Apri o chiudi menu di navigazione</span> </label>
    
    <nav id="menu-admin" aria-label="Menù">
        <a class="navigationHelp" href="#content"> Salta il menù di navigazione</a>
        <a id="logo-link" href="./home">
            <img src="./assets/icons/light-mode-logo.svg" id="logo" alt="Home" lang="en" />
        </a>
        <div id="solo-stampa" lang="en">PetMatch</div>
        ';
        $html .= $currentHref==='./nuovo-animale' ? '<p class="orange-button" id="currentLink" href="./nuovo-animale">+ Aggiungi animale</p>' : '<a class="orange-button" href="./nuovo-animale">+ Aggiungi animale</a>';

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
        <form action="./area-riservata" method="post">
            <button type="submit" name="logout" class="logout-btn">Disconnettiti</button>
        </form>
    </nav></header>';

    return $html;
}


/**
 * Genera la nav menù utente dinamicamente
 */
function buildNav(array $items, string $currentHref): string {

    global $noNav;
    $isDark = (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark');

    $homeHref = './home';
    $logoAttributes = ($currentHref === $homeHref)? ' id="currentLink"' : '';
    $isLogoActive =  ($currentHref === $homeHref)?                    
    
    '<div' . $logoAttributes . '>
        <img src="./assets/icons/light-mode-logo.svg" id="logo-header" alt="PetMatch Home" />
        <span id="name-site" lang="en">Pet<span class="not-bold">Match</span></span>
    </div>' :
    
    '<a href="' . $homeHref . '"' . $logoAttributes . '>
        <img src="./assets/icons/light-mode-logo.svg" id="logo-header" alt="PetMatch Home" />
        <span id="name-site">Pet<span class="not-bold">Match</span></span>
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
                
                <input type="checkbox" id="menu-toggle-checkbox" class="sr-only"/>
    
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
                <input type="checkbox" id="theme-toggle" class="sr-only"';
                $html .= $isDark? ' checked />':'/>';
                $html .= '
                    <label for="theme-toggle" id="theme-switch">
                        <span class="sr-only">Cambia tema</span>
                        <span id="slider">
                            <img src="./assets/icons/sun.svg" id="sun" alt=""/>
                            <img src="./assets/icons/moon.svg" id="moon" alt=""/>
                        </span>
                    </label>
    
                    <nav aria-label="Area personale">
                        <ul id="personal-area">';
                        if(!isset($_SESSION['admin']) || $_SESSION['admin']===false){
                            if($currentHref==='./preferiti'){
                               $html .= '<li><p id="preferiti"><img src="./assets/icons/heart-hover.svg" id="heart-hover-currentLink" alt="" /></p></li>';
                            }else{
                               $html .= '                            
                               <li>
                                   <a href="./preferiti" id="preferiti" aria-label="Preferiti">
                                       <img src="./assets/icons/heart-normal.svg" id="heart-normal" alt="" />
                                       <img src="./assets/icons/heart-hover.svg" id="heart-hover" alt="" />
                                   </a>
                               </li>';
                            }
                        }

                            $html .='
                            <li>';
                                if($currentHref==='./profilo-utente'){
                                    $html.= '<p class="white-button" href="./accedi" id="currentLink">';
                                }else if(isset($_SESSION['email'])){
                                    $html.= '<a class="white-button" href="./profilo-utente">';
                                }else{
                                    $html.= '<a class="white-button" href="./accedi">';
                                }
                                
                                $html .= isset($_SESSION['email']) ? '<span id="text-accedi">Profilo</span>' : '<span id="text-accedi">Accedi</span>';
                                
                                if($currentHref==='./profilo-utente'){
                                    $html .='<img src="./assets/icons/account-hover.svg" id="account-hover-currentLink" alt="" />';
                                }else{
                                    $html .= '
                                    <img src="./assets/icons/account-normal.svg" id="account-normal" alt="" />
                                    <img src="./assets/icons/account-hover.svg" id="account-hover" alt="" />';
                                }

                                $html.= ($currentHref==='./profilo-utente')?'</p>':'</a>';
                                $html .= '
                            </li>
                        </ul> 
                    </nav>
                    
                    <label for="menu-toggle-checkbox" class="menu-toggle">
                        <span class="sr-only">Apri il menù</span>
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
                            <img src="./assets/icons/light-mode-logo.svg" id="logo-header" alt="PetMatch Home" />
                            <span id="name-site">Pet<span class="not-bold">Match</span></span>
                        </a>
                    </h1>
                </nav>
                <div id="log-theme">
                    <input type="checkbox" id="theme-toggle" class="sr-only"';
                    $html .= $isDark? ' checked />':'/>';
                    $html .= '
                        <label for="theme-toggle" id="theme-switch" >
                            <span class="sr-only">Cambia tema</span>
                            <span id="slider">
                                <img src="./assets/icons/sun.svg" id="sun" alt=""/>
                                <img src="./assets/icons/moon.svg" id="moon" alt=""/>
                            </span>
                        </label>
                </div>
            </div>
        </header>';
    }

    return $html;
}


function buildFooter(array $menuGroups, string $currentHref): string {

    $homeHref = './home';
    $logoAttributes = ($currentHref === $homeHref)? ' id="currentLinkFooter"' : '';
    $isLogoActive =  ($currentHref === $homeHref)?   
    '<div' . $logoAttributes . '>
        <img src="./assets/icons/light-mode-logo.svg" id="logo-footer" alt="PetMatch Home" />
        <span id="name-site-footer" lang="en">Pet<span class="not-bold">Match</span></span>
    </div>' :
    
    '<div><a href="' . $homeHref . '"' . $logoAttributes . '>
        <img src="./assets/icons/light-mode-logo.svg" id="logo-footer" alt="PetMatch Home" />
        <span id="name-site-footer" lang="en">Pet<span class="not-bold">Match</span></span>
    </a></div>';

    $html = '
    <div id="grass"></div>
    <footer>
        <div class="container">
            <ul id="footer-menu" aria-label="menù di fine pagina">';
                foreach ($menuGroups as $key => $items) {

                    if ($key === 'Su di noi') {
                        $html .= '  <li aria-labelledby="su-di-noi-footer">
                                    <nav aria-labelledby="su-di-noi-footer">
                                        <a class="navigationHelp" href="#lavora-con-noi" > Salta il contenuto</a>
                                        <p aria-hidden="true" id="su-di-noi-footer">Su di noi</p>
                                        <ul class="footer-submenu" aria-labelledby="su-di-noi-footer">';
                    }else{
                        $html .= '  <li aria-labelledby="lavora-con-noi-footer">    
                                    <nav id="lavora-con-noi" tabindex="-1" aria-labelledby="lavora-con-noi-footer">
                                        <a class="navigationHelp" href="#contattaci"> Salta il contenuto</a>
                                        <p id="lavora-con-noi-footer" aria-hidden="true">Vuoi lavorare con noi?</p>
                                        <ul class="footer-submenu">';
                    }

                    foreach ($items as $item) {
                        $pathItem = strtok($item['href'], '#');
                        $active = ($pathItem === $currentHref) ? ' class="currentLinkFooter"' : '';
                        $linkHref = ($pathItem=== $currentHref) ? '<li'.$active.' aria-label="pagina attuale:'.$item['text'].'">'.$item['text'].'</li>' : '<li'.$active.'><a href="'.$item['href'].'">'.$item['text'].'</a></li>';
                        
                        $html .= $linkHref;
                    }
                    $html .= '</ul></nav></li>';
                }
                $html .= '
                <li aria-labelledby="contattaci-footer">
                    <nav id="contattaci" tabindex="-1" aria-labelledby="contattaci-footer">
                        <a class="navigationHelp" href="#seguici-su"> Salta il contenuto</a>
                        <p id="contattaci-footer" aria-hidden="true" class="vcard"><span class="fn">Contattaci</span></p>
                        
                        <ul class="footer-submenu">
                            <li>
                                <address>
                                    <a class="email" href="mailto:matchpet48@gmail.com" target="_blank">matchpet48@gmail.com</a>
                                </address>
                            </li>
                            <li>
                                <address>
                                    <a class="tel" href="tel:+390000000000"> +39 000 000 0000</a>
                                </address>
                            </li>
                        </ul>
                    </nav>
                </li>
                <li aria-labelledby="seguici-footer">
                    <nav id="seguici-su" tabindex="-1" aria-labelledby="seguici-footer">
                        <a class="navigationHelp" href="#copyright"> Salta il contenuto</a>
                        <p id="seguici-footer" aria-hidden="true">Seguici su</p>
                        <ul class="footer-submenu">
                            <li class="social-media-links">
                                <address>
                        <a id="insta-link" href="https://www.instagram.com/petmatch_shelter" target="_blank" aria-label="Instagram: @petmatch_shelter">
                                        <img src="./assets/icons/Instagram.svg" id="instagram" alt="" />
                                        @petmatch_shelter
                                    </a>
                                </address>
                            </li>
                        </ul>
                    </nav>
                </li>
            </ul>
        </div>
        '.$isLogoActive.'
        <small id="copyright" tabindex="-1">
            &copy; 2025 PetMatch. Diritti, illustrazioni e foto riservate, giù le zampe!
        </small>
    </footer>';

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

// se $_FILES['foto'] non esiste o è vuoto, la funzione ritorna null
//NOTA: il server è stato impostato per avere un max upload di 8MB in POST
function uploadImage($file, $folder) {
    $basePath = dirname(__DIR__) . '/assets/images/' . $folder . '/';
    $dbPathPrefix = 'assets/images/' . $folder . '/';
    
    if (!file_exists($basePath)) mkdir($basePath, 0755, true);
    if (!is_writable($basePath) || $file['error'] !== UPLOAD_ERR_OK) return null;
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $fileName = $folder . "_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $extension;
    $targetFile = $basePath . $fileName;

    $info = getimagesize($file['tmp_name']);
    if (!$info) return null;

    switch ($info[2]) {
        case IMAGETYPE_JPEG: $sourceImage = imagecreatefromjpeg($file['tmp_name']); break;
        case IMAGETYPE_PNG:  $sourceImage = imagecreatefrompng($file['tmp_name']);  break;
        case IMAGETYPE_WEBP: $sourceImage = imagecreatefromwebp($file['tmp_name']); break;
        default: return move_uploaded_file($file['tmp_name'], $targetFile) ? $dbPathPrefix . $fileName : null;
    }

    $quality = 80;
    $maxSize = 40 * 1024; // SOGLIA 40KB
    $width = imagesx($sourceImage);
    $height = imagesy($sourceImage);

    do {
        ob_start();
        if ($extension === 'png') {
            imagepng($sourceImage, null, (int)(($quality / 100) * 9));
        } else {
            imagejpeg($sourceImage, null, $quality);
        }
        
        $imageData = ob_get_clean();
        $currentSize = strlen($imageData);

        if ($currentSize > $maxSize) {
            $quality -= 15;
            if ($quality < 40) { 
                $width = (int)($width * 0.75);
                $height = (int)($height * 0.75);
                $canvas = imagecreatetruecolor($width, $height);
                imagealphablending($canvas, false);
                imagesavealpha($canvas, true);
                imagecopyresampled($canvas, $sourceImage, 0, 0, 0, 0, $width, $height, imagesx($sourceImage), imagesy($sourceImage));
                imagedestroy($sourceImage);
                $sourceImage = $canvas;
                $quality = 70;
            }
        }
    } while ($currentSize > $maxSize && $width > 50);

    $success = file_put_contents($targetFile, $imageData);
    imagedestroy($sourceImage);

    return $success ? $dbPathPrefix . $fileName : null;
}

// elimina un'immagine dal server
function deleteStoredFile($filename) {
    //lista di immagini di default da non cancellare
    $protected_files = ['default-pic.png', 'defaultCane.jpg','defaultGatto.jpg', 'eventi-default.jpg'];

    // controllo che il file che sto passando abbia un nome e che non faccia parte di quelli di default
   $pureName = basename($filename);

    if (empty($filename) || in_array($pureName, $protected_files)) {
        return true; 
    }

    // 2. Costruzione del percorso. 
    $path = dirname(__DIR__) .'/'. $filename;

    if (file_exists($path) && is_file($path)) {
        return unlink($path);
    }

    return false;
}


function getCardAnimal(int $idanimale, bool $isAdmin, bool $isAdopted):string{
    $html = '<section id=\'info-animal\'>
            <h2>Animale interessato</h2>
            <div class=\'details-card-animale\'>
                <div>
                    <div>
                        <img src="[imgAnimale]" alt="" />';
                    $html .= ($isAdopted&&!$isAdmin)?'<p class="nonDisponibile"><em>Animale adottato</em></p>':'<a href="./animali?id='.urlencode($idanimale).'" class="brown-button">Vedi animale</a>';
                    $html.='
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
    
    // meglio mandarlo ad accedi che alla home, così sa che è andato tutto bene
    header("Location: ./accedi");
    exit;
}

function calcolaEta(?string $dataNascita): ?string {
    if (!$dataNascita) {
        return null;
    }

    try {
        $nascita = new DateTime($dataNascita);
        $oggi = new DateTime();
        $diff = $oggi->diff($nascita);

        if ($diff->y > 0) {
            return $diff->y . ' anni';
        }

        return $diff->m . ' mesi';
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



function buildPagination(int $currentPage, int $totalPages, array|string $params = []): string {
    // NORMALIZZA FILTRI
    if (is_string($params) && $params !== '') {
        // stringa semplice → tipo
        $params = ['tipo' => $params];
    }

    if (!is_array($params)) {
        $params = [];
    }

// rimuove valori vuoti
    // $params = array_filter($params, fn($v) => $v !== '');
	    if ($totalPages <= 1) return '';
unset($params['page']);

    $html = '';

    if ($currentPage > 1) {
        $params['page'] = $currentPage - 1;
        $html .= '<li><a href="?' . http_build_query($params) . '"><img src="./assets/icons/arrow-sx-green.svg" alt="precedente" /></a></li>';
    }

    $maxVisible = 10;
    $start = max(1, $currentPage - 4);
    $end = min($totalPages, $start + $maxVisible - 1);

    if ($end - $start + 1 < $maxVisible) {
        $start = max(1, $end - $maxVisible + 1);
    }

    for ($i = $start; $i <= $end; $i++) {
        if ($i === $currentPage) {
            $html .= '<li class="currentLinkPagination" aria-label="pagina attuale">'.$i.'</li>';
        } else {
            $params['page'] = $i;
            $html .= '<li><a href="?' . http_build_query($params) . '" aria-label="vai alla pagina '.$i.'">'.$i.'</a></li>';
        }
    }

    if ($currentPage < $totalPages) {
        $params['page'] = $currentPage + 1;
        $html .= '<li><a href="?' . http_build_query($params) . '"><img src="./assets/icons/arrow-dx-green.svg" alt="successiva"/></a></li>';
    }

    return $html;
}


function renderCaniGattiTabs(): string{
    $html = '';
    if(isset($_GET['tipo'])){
        //se ha valore Nuova, In valutazione, Da trasportare, Annullata, Respinta
        $stato = $_GET['tipo'];
        $selected = [
            'Cani' => '',
            'Gatti' => '',
        ];
        $checked = [
            'Cani' => '',
            'Gatti' => ''
        ];
        if(array_key_exists($stato, $selected)){
            $checked[$stato] = 'checked';
            $selected[$stato] = 'selected';
        }
        $html = '
        <label for="mobile-select" class="sr-only">Scegli una categoria:</label>
        <select id="mobile-select" name="tab-group">
            <option value="tab1" '.$selected['Cani'].'>
                Cani ([n-cani])
            </option>
            <option value="tab2" '.$selected['Gatti'].'>
                Gatti ([n-gatti])
            </option>
        </select>
        <input class="sr-only" type="radio" id="tab1" name="tab-group"'.$checked['Cani'].'/>
        <label for="tab1">Cani ([n-cani])</label>
        <input class="sr-only" type="radio" id="tab2" name="tab-group" '.$checked['Gatti'].'/>
        <label for="tab2">Gatti ([n-gatti])</label>';
    }else{
        $html = '
        <label for="mobile-select" class="sr-only">Scegli una categoria:</label>
        <select id="mobile-select" name="tab-group">
            <option value="tab1" selected>
                Cani ([n-cani])
            </option>
            <option value="tab2">
                Gatti ([n-gatti])
            </option>
        </select>

        <input class="sr-only" type="radio" id="tab1" name="tab-group" checked/>
        <label for="tab1">Cani ([n-cani])</label>
        <input class="sr-only" type="radio" id="tab2" name="tab-group"/>
        <label for="tab2">Gatti ([n-gatti])</label>';
    }

    return $html;
    
}

function formattaDataItaliana(string $data): string {
    $mesi = [
        1 => 'Gennaio',
        2 => 'Febbraio',
        3 => 'Marzo',
        4 => 'Aprile',
        5 => 'Maggio',
        6 => 'Giugno',
        7 => 'Luglio',
        8 => 'Agosto',
        9 => 'Settembre',
        10 => 'Ottobre',
        11 => 'Novembre',
        12 => 'Dicembre'
    ];

    $timestamp = strtotime($data);

    $giorno = date('d', $timestamp);
    $mese   = $mesi[(int)date('n', $timestamp)];
    $anno   = date('Y', $timestamp);

    return "$giorno $mese $anno";
}

/**
 * Escape stringa per output HTML serve a prevenire XSS ossia Cross Site Scripting ossia l'inserimento di codice malevolo in pagine web visualizzate da altri utenti
 */
function e(string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Ritorna "Sì" o "No" in base a valore booleano/intero
 */
function siNo($val): string {
    return ($val === 1 || $val === '1' || $val === true) ? 'Sì' : 'No';
}

function displayDateItalianFormat(string $dateStr): string {
    $timestamp = strtotime($dateStr);
    if ($timestamp === false) {
        return '';
    }
    return date('d/m/Y', $timestamp);
}

function formattaEta($dataNascita) {
    if (!$dataNascita) return "Età sconosciuta";

    try {
        $nascita = new DateTime($dataNascita);
        $oggi = new DateTime();
        $diff = $nascita->diff($oggi);

        $parti = [];

        if ($diff->y > 0) {
            $parti[] = $diff->y . ($diff->y == 1 ? " anno" : " anni");
        }

        // Gestione Mesi
        if ($diff->m > 0) {
            $parti[] = $diff->m . ($diff->m == 1 ? " mese" : " mesi");
        }

        return implode(" e ", $parti);
    } catch (Exception $e) {
        return "Data non valida";
    }
}
