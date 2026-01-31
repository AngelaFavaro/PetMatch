<?php
require_once './src/utils.php';
require_once './src/DBconnection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use DB\DBAccess;

$idAnimale = $_GET['id'] ?? null;

$utenteAccesso = isset($_SESSION['email']);
$emailUtente = $utenteAccesso ? $_SESSION['email'] : null;
$isAdmin = (isset($_SESSION['admin'])&&$_SESSION['admin']===true);

$richiesta = null;
$infoUtente = null; // Variabile per dati utente

$infoAggiuntive = '';

// Variabili per il form (inizializzate vuote o con default)
$messaggiErrore = [
    'general' => '',
    'lettera' => '',
    'indirizzo' => '',
    'citta' => '',
    'cap' => '',
    'indirizzo_totale' => ''
];
$valVia = '';
$valCitta = '';
$valCap = '';
$valLettera = '';
$valTrasporto = '';
$editAddressPermission = false;

//messaggio per laura quando andrà a mettere tutte le funzioni fuori dalla connessione db:
//dato che ho messo il form dentro un details per poterlo aprire con un pulsante, mi serve che se ci sono degli errori
//allora me lo apre, potrei farlo con js ma se lo disattivi allora potrebbe non essere per NIENTE intuitivo (io non
//stavo capendo perché non andava), quindi questo mi serve per modificare un placeholder, se è true allora metto open sul
//details, altrimenti no
$openDetails = false;

function handleAdoptionRequest(
    DBAccess $connection,
    string $emailUtente,
    int $idAnimale,
    array $infoUtente,
    &$valLettera,
    &$valVia,
    &$valCitta,
    &$valCap,
    &$valTrasporto,
    &$openDetails
) {

    $messaggiErrore = [
        'lettera' => '',
        'indirizzo' => '',
        'citta' => '',
        'cap' => '',
        'indirizzo_totale' => '',
        'generic' => ''
    ];

    /* =====================================================
       RECUPERO ERRORI E INPUT DA SESSIONE (POST → REDIRECT)
       ===================================================== */
    if (isset($_SESSION['form_status']) && $_SESSION['form_status'] === 'error') {

        $savedErrors = $_SESSION['form_errors'] ?? [];
        $savedInputs = $_SESSION['form_inputs'] ?? [];

        foreach ($savedErrors as $key => $value) {
            $messaggiErrore[$key] = $value;
        }

        $valLettera     = $savedInputs['lettera'] ?? '';
        $valVia         = $savedInputs['address'] ?? '';
        $valCitta       = $savedInputs['city'] ?? '';
        $valCap         = $savedInputs['cap'] ?? '';
        $valTrasporto   = $savedInputs['trasporto'] ?? '';

        $openDetails = true;

        unset($_SESSION['form_status'], $_SESSION['form_errors'], $_SESSION['form_inputs']);
    }

    /* ==========================
       GESTIONE SUBMIT FORM
       ========================== */
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-adoption-request'])) {

        $lettera = trim($_POST['lettera-presentazione'] ?? '');
        $address = mb_convert_case(trim($_POST['new-address'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $city    = mb_convert_case(trim($_POST['new-city'] ?? ''), MB_CASE_TITLE, "UTF-8");
        $CAP     = trim($_POST['new-cap'] ?? '');
        $trasportoRichiesto = isset($_POST['trasporto']);

        // valori per redisplay
        $valLettera = htmlspecialchars($lettera, ENT_QUOTES, 'UTF-8');
        $valVia     = htmlspecialchars($address, ENT_QUOTES, 'UTF-8');
        $valCitta   = htmlspecialchars($city, ENT_QUOTES, 'UTF-8');
        $valCap     = htmlspecialchars($CAP, ENT_QUOTES, 'UTF-8');

        $errors = [];

        /* ========= VALIDAZIONE ========= */

        if (strlen($lettera) < 10) {
            $errors['lettera'] = "La lettera di presentazione è troppo breve.";
        }

        $regex_indirizzo = '/^[a-zA-Z\.\']{3,}\s+.+\s+(?:n\.?\s?)?\d+[a-zA-Z]?$/';
        $regex_citta = '/^[a-zA-Z\s\.\']{2,}$/';
        $regex_cap = '/^\d{5}$/';

        $hasAddress = $address !== '';
        $hasCity    = $city !== '';
        $hasCAP     = $CAP !== '';

        if($trasportoRichiesto && !($hasAddress && $hasCity && $hasCAP)){
            $errors['indirizzo_totale'] = "Se richiedi il trasporto, indica l'indirizzo.";
        }else if (($hasAddress || $hasCity || $hasCAP) && !($hasAddress && $hasCity && $hasCAP)) {
            $errors['indirizzo_totale'] = "Indirizzo incompleto: compila tutti i campi o nessuno.";
        } else if ($hasAddress) {

            if (!preg_match($regex_indirizzo, $address)) {
                $errors['indirizzo'] = "Formato indirizzo non valido.";
            }

            if (!preg_match($regex_citta, $city)) {
                $errors['citta'] = "Città non valida.";
            }

            if (!preg_match($regex_cap, $CAP)) {
                $errors['cap'] = "CAP non valido (5 cifre).";
            }
        }

        /* ========= AZIONI ========= */

        if (empty($errors)) {

            if ($hasAddress) {
                $connection->updateUserAddress($emailUtente, [
                    'address' => $address,
                    'city' => $city,
                    'CAP' => $CAP
                ]);
            }

            $success = $connection->insertAdoptionRequest(
                $emailUtente,
                $idAnimale,
                $lettera,
                $trasportoRichiesto
            );

            if ($success) {
                header("Location: animali?id=" . $idAnimale);
                exit;
            }

            $errors['generic'] = "Errore durante il salvataggio della richiesta.";
        }

        /* ========= ERRORI → SESSIONE ========= */
        $_SESSION['form_status'] = 'error';
        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_inputs'] = [
            'lettera' => $valLettera,
            'address' => $valVia,
            'city' => $valCitta,
            'cap' => $valCap,
            'trasporto' =>$trasportoRichiesto
        ];

        header("Location: animali?id=" . $idAnimale . "#content-form");
        exit;
    }

    /* ==========================
       PRECARICAMENTO DA DB
       ========================== */
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $valVia   = $infoUtente['Via']? htmlspecialchars($infoUtente['Via'], ENT_QUOTES, 'UTF-8') : $valVia;
        $valCitta   = $infoUtente['Citta']? htmlspecialchars($infoUtente['Citta'], ENT_QUOTES, 'UTF-8') : $valCitta;
        $valCap   = $infoUtente['CAP']? htmlspecialchars($infoUtente['CAP'], ENT_QUOTES, 'UTF-8') : $valCap;
    }

    return $messaggiErrore;
}


function handleFavorites(
    DBAccess $connection,
    bool $utenteAccesso,
    ?string $emailUtente
) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id-animale-preferito'])) {
        return;
    }

    $idAnimalePost = (int)$_POST['id-animale-preferito'];
    $azione = '';

    if ($utenteAccesso) {
        // UTENTE LOGGATO → DB
        if ($connection->isAnimalInFavorites($emailUtente, $idAnimalePost)) {
            $connection->removeFromFavorites($emailUtente, $idAnimalePost);
            $azione = 'rimosso';
        } else {
            $connection->addToFavorites($emailUtente, $idAnimalePost);
            $azione = 'aggiunto';
        }
    } else {
        // GUEST → COOKIE
        $preferiti = getGuestFavorites();

        if (in_array($idAnimalePost, $preferiti)) {
            $preferiti = array_diff($preferiti, [$idAnimalePost]);
            $azione = 'rimosso';
        } else {
            $preferiti[] = $idAnimalePost;
            $azione = 'aggiunto';
        }

        saveGuestFavorites($preferiti);
    }

    // ===== AJAX =====
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'azione' => $azione
        ]);
        exit;
    }

    // ===== FALLBACK (no JS) =====
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}








$connection = new DBAccess();
if ($connection->openDBConnection()) {
    handleFavorites($connection, $utenteAccesso, $emailUtente);
    $editAddressPermission = $connection ->getAddressPermissionEdit($emailUtente);

    // 1. Recupero Dettagli Animale
    if ($idAnimale) {
        $dettagliAnimale = $connection->getAnimalDetails($idAnimale); 
    }
    $giàInteressato = '';
        if ($connection->hasActiveAdoptionRequest($idAnimale)) {
            $giàInteressato = "<div id='interessamento-animale'>Qualcuno è già interessato a questo animale</div>";
        }

    if ($emailUtente) {
            $inPreferiti = $connection->isAnimalInFavorites($emailUtente, $idAnimale);
        } else {
            $guestFavs = getGuestFavorites();
            $inPreferiti = in_array($idAnimale, $guestFavs);
        }
        
        // ... (Tuoi assegnamenti variabili animale) ...
        $nome = htmlspecialchars($dettagliAnimale['nome']);
        $sesso = $dettagliAnimale['sesso'] === 'M' ? 'Maschio' : 'Femmina';
        $eta = $dettagliAnimale['eta'];
        $razza = htmlspecialchars($dettagliAnimale['razza']);
        $pelo = htmlspecialchars($dettagliAnimale['pelo']);
        $taglia = htmlspecialchars($dettagliAnimale['taglia']);
        $colore = htmlspecialchars($dettagliAnimale['colore']);
        $trasporto = $dettagliAnimale['trasporto'] ? 'Sì' : 'No';
        $famiglia = htmlspecialchars($dettagliAnimale['descr_famiglia']);
        $comportamento = htmlspecialchars($dettagliAnimale['descr_comportamentale']);
        $condizioniMediche = $dettagliAnimale['condizioni_mediche'] ? htmlspecialchars($dettagliAnimale['condizioni_mediche']) : 'Nessuna';
        
        if (!empty($dettagliAnimale['imgPath']) && file_exists($dettagliAnimale['imgPath'])) {
            $img = $dettagliAnimale['imgPath'];
        } else {
            $imgPath = ($dettagliAnimale['tipo'] === 'Cane') ? 'assets/images/animals/defaultCane.jpg' : 'assets/images/animals/defaultGatto.jpg';
        }
        if ($utenteAccesso) {
            
            // Recupero info utente base per popolare il form (se non è un POST di errore)
            $infoUtente = $connection->getUserInfo($emailUtente);            

            // GESTIONE POST RICHIESTA ADOZIONE
            $messaggiErrore = handleAdoptionRequest(
            $connection,
            $emailUtente,
            $idAnimale,
            $infoUtente,
            $valLettera,
            $valVia,
            $valCitta,
            $valCap,
            $valTrasporto,
            $openDetails
            );

            // Stato richiesta attuale
            $richiesta = $connection->getRequestStatus($emailUtente, $idAnimale);

            $richiestaData = $connection->getAnimalArrivalDate($idAnimale);
            $connection->closeConnection();
            $dataArrivo = ''; 

            
        }
        
        
        // ... (Variabili preferiti visuali) ...
        $classePreferito = $inPreferiti ? 'is-favorite' : 'not-favorite';
        $heartNormal = $inPreferiti ? 'active-like.svg' : 'inactive-like.svg';
        $heartHover = $inPreferiti ? 'inactive-like.svg' : 'active-like.svg';
        $statusPreferiti = $inPreferiti ? 'Rimuovi dai preferiti' : 'Aggiungi ai preferiti';
        
        // Gestione Immagine finale
         if (!empty($dettagliAnimale['imgPath']) && file_exists($dettagliAnimale['imgPath'])) {
            $img = $dettagliAnimale['imgPath'];
        } else {
            $img = ($dettagliAnimale['tipo']==='Cane') ? 'assets/images/animals/defaultCane.jpg' : 'assets/images/animals/defaultGatto.jpg';
        }

        // 2. GESTIONE POST PREFERITI

        // 3. GESTIONE LOGICA UTENTE LOGGATO (Info e Form Adozione)
        
        if(isset($richiestaData) && !empty($richiestaData)){
    $dataArrivo =  date("d/m/Y", strtotime($richiestaData));
    } else $dataArrivo="non ancora stabilita";
} else {
    // Gestione errore connessione DB
    $messaggiErrore['generic'] = "<p class='error'>Impossibile completare l'operazione, riprova più tardi.</p>";
}



// DEFINIZIONE CONTENUTO PAGINA (Form o Stato)
$contenutoPagina = "";
$statoRichiesta = "";
$contattaci="";

if (!$utenteAccesso) {
    $contattaci="<a href='mailto:matchpet48@gmail.com' target='_blank' class='brown-button' id='contatta-il-rifugio'>Contatta il rifugio</a>";
    $contenutoPagina = "
    <aside id='contatta-rifugio'>
        <p> Vuoi adottare questo animale? <a href='./registrati'>Registrati</a> o <a href='./accedi'>accedi</a> se hai già un profilo e manda una richiesta!</p>
    </aside>";
    $infoAggiuntive='info-aggiuntive-separate';
} else if (($richiesta === false || $richiesta === null) && $isAdmin === false) {
    $contattaci="<a href='mailto:matchpet48@gmail.com' target='_blank' class='brown-button' id='contatta-il-rifugio'>Contatta il rifugio</a>";
    // FORM ADOZIONE
    $infoAggiuntive='info-aggiuntive-separate';
    $readonlyAttr = $editAddressPermission ? '' : 'readonly';
    $messaggioAddress= $editAddressPermission ? "<p>Il profilo utente verrà aggiornato con l'indirizzo inserito.</p>" : "<p>Il profilo utente non può essere aggiornato con un nuovo indirizzo perché hai almeno una richiesta di adozione con l'animale in trasporto</p>";
    $contenutoPagina = "
    <div class='container'>
    <p class='error-form' id='errore-db'>[erroriGenerici]</p>
        <details id='compila-form-adozione' [openOrNot]>
            <summary>Compila il form di adozione</summary>
        </details>
    </div>
        <div id='richiesta-adozione'>
        <div id='content-form' class='container'>
            <h2>Invia una richiesta di adozione!</h2>
            <div class='column-container'>
                <div class='column-user'>
                    <img id='richiesta-adozione-img' src='./assets/images/adozione.jpg' alt=''/>
                </div>
                <div class='column-user'>
                    <form method='POST' action='' novalidate> 
                        <fieldset>
                            <legend>Prepara la richiesta di adozione</legend>
                            <label for='lettera-presentazione'>Scrivi una breve lettera di presentazione:</label>
                            <textarea id='lettera-presentazione' name='lettera-presentazione' rows='7' cols='50' placeholder='Parlaci di te' required>[VALORE_LETTERA]</textarea>
                            <p class='error-form'>[ERROR_LETTERA]</p>
                        </fieldset>
                        <fieldset class='fieldset-indirizzo'>
                            <legend>Indirizzo</legend>
                                $messaggioAddress

                                <div>
                                    <label for='new-address'>Via e numero civico</label>
                                    <input type='text' id='new-address' name='new-address'
                                        autocomplete='street-address'
                                        value='[via-utente]'
                                        placeholder='Via L. Da Vinci n.10'
                                        $readonlyAttr />
                                    <p class='error-form'>[erroriIndirizzo]</p>
                                </div>

                                <div id='indirizzo-row'>
                                    <div id='citta-container'>
                                        <label for='new-city'>Città</label>
                                        <input type='text' id='new-city' name='new-city'
                                            autocomplete='address-level2'
                                            value='[citta-utente]'
                                            placeholder='Roma'
                                            $readonlyAttr />
                                        <p class='error-form'>[erroriCitta]</p>
                                    </div>

                                    <div id='cap-container'>
                                        <label for='new-cap'>CAP</label>
                                        <input type='text' id='new-cap' name='new-cap'
                                            autocomplete='postal-code'
                                            value='[cap-utente]'
                                            placeholder='00000'
                                            $readonlyAttr />
                                        <p class='error-form'>[erroriCAP]</p>
                                    </div>
                                </div>

                                <p class='error-form' id='indirizzo-incompleto'>[erroriIndirizzoTotale]</p> 
                            <div id='checkbox-trasporto-container'>
                                <input type='checkbox' id='trasporto' name='trasporto' [trasporto-richiesto]/>
                                <label for='trasporto'>
                                    <p class='checkbox-title'>Voglio il trasporto dell’animale a casa</p>
                                    <p class='checkbox-description'>Spuntando la casella, verrà programmato il trasporto dell’animale. Ci si prende la responsibilità di essere presenti nel domicilio indicato alla data che verrà comunicata per email.</p>
                                </label>
                            </div>
                            <button class='orange-button' name='submit-adoption-request' type='submit'>Invia il Form</button>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>
    </div>";

    // Replacement Placeholders nel Form
    $contenutoPagina = str_replace('[VALORE_LETTERA]', $valLettera, $contenutoPagina);
    $contenutoPagina = str_replace('[via-utente]', $valVia, $contenutoPagina);
    $contenutoPagina = str_replace('[citta-utente]', $valCitta, $contenutoPagina);
    $contenutoPagina = str_replace('[cap-utente]', $valCap, $contenutoPagina);
    $contenutoPagina = str_replace('[trasporto-richiesto]', $valTrasporto?'checked':'', $contenutoPagina);

    $contenutoPagina = str_replace('[ERROR_LETTERA]', $messaggiErrore['lettera'], $contenutoPagina);
    $contenutoPagina = str_replace('[erroriIndirizzo]', $messaggiErrore['indirizzo'], $contenutoPagina);
    $contenutoPagina = str_replace('[erroriCitta]', $messaggiErrore['citta'], $contenutoPagina);
    $contenutoPagina = str_replace('[erroriCAP]', $messaggiErrore['cap'], $contenutoPagina);
    $contenutoPagina = str_replace('[erroriIndirizzoTotale]', $messaggiErrore['indirizzo_totale'], $contenutoPagina);
    $contenutoPagina = str_replace('[erroriGenerici]', $messaggiErrore['generic'], $contenutoPagina);

} else {
    // STATI RICHIESTA ESISTENTE
    switch ($richiesta) {
        case 'Nuova': {
                $infoAggiuntive='info-aggiuntive-unite';
                $contenutoPagina = "";
                $statoRichiesta = "
                <h2> Richiesta di adozione </h2>
                    <div id='stato-richiesta'>
                        <p> Stato: <span class='enfatizzato'> richiesta pendente </span> </p>
                        <p> Hai una richiesta di adozione pendente per questo animale, attendi che ti venga comunicato l’esito! </p>
                        <p> Qualche problema o domanda? Valuta di contattarci </p>
                    </div>
                <a href='mailto:matchpet48@gmail.com' class='brown-button' id='contatta-il-rifugio'>Contatta il rifugio</a> "
;   
                break;

        }
        case 'In valutazione':
            {
                $infoAggiuntive='info-aggiuntive-unite';
                $contenutoPagina = "";
                $statoRichiesta = "
                <h2> Richiesta di adozione </h2>
                    <div id='stato-richiesta'>
                        <p> Stato: <span class='enfatizzato'> in valutazione </span> </p>
                        <p> Ti contatteremo nell'email indicata sul tuo profilo per iniziare la tua conoscenza e valutare se sei il giusto candidato per <strong>$nome</strong> </p>
                        <p> Qualche problema o domanda? Valuta di contattarci </p>
                    </div>
                    <a href='mailto:matchpet48@gmail.com' class='brown-button' id='contatta-il-rifugio'>Contatta il rifugio</a>
                ";
                break;
            }
        case 'Da trasportare':{
                $infoAggiuntive='info-aggiuntive-unite';
                $contenutoPagina = "";
                $statoRichiesta = "
                <h2> Richiesta di adozione </h2>
                    <div id='stato-richiesta'>
                        <p>  Stato: <span class='enfatizzato'> pronto per il trasporto </span> </p>
                        <p> Data di arrivo: <span class='enfatizzato'>$dataArrivo</span> </p> 
                        <p> Qualche problema o domanda? Valuta di contattarci </p>
                    </div>
                    <a href='mailto:matchpet48@gmail.com' class='brown-button' id='contatta-il-rifugio'>Contatta il rifugio</a> 
                ";
                break;
        }
        case 'Respinta':{
           $infoAggiuntive='info-aggiuntive-unite';
                $contenutoPagina = "";
                $statoRichiesta = "
                <h2> Richiesta di adozione </h2>
                    <div id='stato-richiesta'>
                        <p>  Stato:  <span class='enfatizzato'>rifiutata </span> </p>
                        <p> Ci dispiace informati che la tua richiesta di adozione di <strong> $nome </strong> è stata rifiutata. </p> 
                        <p> Qualche problema o domanda? Valuta di contattarci </p>
                    </div>
                    <a href='mailto:matchpet48@gmail.com' class='brown-button' id='contatta-il-rifugio'>Contatta il rifugio</a> 
               ";
                break;
        }
        case 'Annullata':{
             $infoAggiuntive='info-aggiuntive-unite';
                $contenutoPagina = "";
                $statoRichiesta = "
                <h2> Richiesta di adozione </h2>
                    <div id='stato-richiesta'>
                        <p>  Stato: <span class='enfatizzato'> annullata </span> </p>
                        <p> Hai annullato la richiesta di adozione per <strong> $nome </strong>.</p> 
                        <p> Qualche problema o domanda? Valuta di contattarci  </p>
                    </div>
                    <a href='mailto:matchpet48@gmail.com' class='brown-button' id='contatta-il-rifugio'>Contatta il rifugio</a> 
                ";
                break;

        }
        
        default:
        {    
            $infoAggiuntive='info-aggiuntive-separate';
            $contenutoPagina = ''; }
    }
}
// COSTRUZIONE BLOCCHI HTML

$CARDANIMALE1 = "
    <img id='foto-animale' class='square-foto' src='$img' alt='Foto di $nome' />  
    <div id= 'info-generiche-testo'>";
    $CARDANIMALE1 .= ($isAdmin)?'':"
                <form method='post' action='' class='preferiti-form'>
        <input type='hidden' name='id-animale-preferito' value='$idAnimale'/>
        <button type='submit' class='$classePreferito' aria-label='$statusPreferiti'>
            <img class='heart-normal' src='./assets/icons/$heartNormal' alt='' />
            <img class='heart-hover' src='./assets/icons/$heartHover' alt='' />
        </button>
    </form>";
    $CARDANIMALE1 .="
        <dl>
            <dt> Nome</dt> <dd> $nome </dd>
            <dt> Sesso</dt> <dd> $sesso </dd>
            <dt> Età</dt> <dd>$eta</dd>
            <dt> Razza</dt> <dd>$razza </dd>
            <dt> Pelo</dt> <dd> $pelo </dd>
            <dt> Taglia</dt> <dd>$taglia </dd>
            <dt> Colore</dt> <dd>$colore </dd>
        </dl>

    </div>

";

$CARDANIMALE2 = $infoAggiuntive==='info-aggiuntive-separate' ? "
    <h2> Altre informazioni</h2>" : '';
    $CARDANIMALE2 .= "
    <div id ='$infoAggiuntive'>
    <dl>
        <dt> Idoneo al trasporto</dt> <dd> $trasporto </dd>
        <dt> Condizioni mediche</dt> <dd> $condizioniMediche</dd>
        <dt> Descrizione carattere</dt> <dd>$comportamento </dd>
        <dt> Famiglia ideale</dt> <dd>$famiglia</dd>
    </dl>
</div>";


// PAGE RENDERING
$paginaHTML = file_get_contents('./src/template/layout.html');

$title = "<title>$nome - PetMatch</title>";
$description = "<meta name='description' content='Scheda di $nome disponibile per adozione'>";
$keywords = "<meta name='keywords' content='$nome, adozione, PetMatch, $razza'>";
$breadcrumb = getBreadcrumb('visualizzazione-animale', $pagine);
$nav = buildNav($userMenu, './visualizzazione-animale');
$main = file_get_contents('./src/template/main/visualizzazione-animale.html');
$footer = buildFooter($footerMenu,  './revisione-richiesta');

$infoAggUnite='';
$main = str_replace('[ADOZIONE_STATUS]', $contenutoPagina, $main);
$main = str_replace('[INTERESSAMENTO-ANIMALE]', $giàInteressato, $main);
if($infoAggiuntive==='info-aggiuntive-separate'){
    $main = str_replace('[CARDANIMALE1]', $CARDANIMALE1, $main);
    $main = str_replace('[CARDANIMALE2]', $CARDANIMALE2, $main);
} else{
    $infoAggUnite=$CARDANIMALE2;
    $main = str_replace('[CARDANIMALE1]', $CARDANIMALE1, $main);
    $main = str_replace('[CARDANIMALE2]', $statoRichiesta, $main);
}
$main = str_replace('[INFO-AGGIUNTIVE]', $infoAggUnite, $main);
$main = str_replace('[CONTATTACI]', $contattaci, $main);

$main = str_replace('[openOrNot]', $openDetails?'open':'', $main);


$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

echo $paginaHTML;
?>