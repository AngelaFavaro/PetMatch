<?php
include './src/utils.php';
include './src/DBconnection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use DB\DBAccess;

$idAnimale = $_GET['id'] ?? null;

$utenteAccesso = isset($_SESSION['email']);
$emailUtente = $utenteAccesso ? $_SESSION['email'] : null;

$richiesta = null;
$infoUtente = null; // Variabile per dati utente

$infoAggiuntive = '';

// Variabili per il form (inizializzate vuote o con default)
$messaggiErrore = [
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

$connection = new DBAccess();
if ($connection->openDBConnection()) {

    // 1. Recupero Dettagli Animale
    if ($idAnimale) {
        $dettagliAnimale = $connection->getAnimalDetails($idAnimale);
        
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

        $giàInteressato = '';
        if ($connection->hasActiveAdoptionRequest($idAnimale)) {
            $giàInteressato = "<div id='interessamento-animale'>Qualcuno è già interessato a questo animale</div>";
        }

        // Gestione Preferiti (Visualizzazione)
        if ($emailUtente) {
            $inPreferiti = $connection->isAnimalInFavorites($emailUtente, $idAnimale);
        } else {
            $guestFavs = getGuestFavorites();
            $inPreferiti = in_array($idAnimale, $guestFavs);
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
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id-animale-preferito'])) {
             $idAnimalePost = (int)$_POST['id-animale-preferito'];
             if ($utenteAccesso) {
                 if ($connection->isAnimalInFavorites($emailUtente, $idAnimalePost)) {
                     $connection->removeFromFavorites($emailUtente, $idAnimalePost);
                 } else {
                     $connection->addToFavorites($emailUtente, $idAnimalePost);
                 }
             } else {
                 $preferiti = getGuestFavorites();
                 if (in_array($idAnimalePost, $preferiti)) {
                     $preferiti = array_diff($preferiti, [$idAnimalePost]);
                 } else {
                     $preferiti[] = $idAnimalePost;
                 }
                 saveGuestFavorites($preferiti);
             }
             // Refresh per aggiornare l'icona
             header("Location: " . $_SERVER['REQUEST_URI']);
             exit;
        }

        // 3. GESTIONE LOGICA UTENTE LOGGATO (Info e Form Adozione)
        if ($utenteAccesso) {
            
            // Recupero info utente base per popolare il form (se non è un POST di errore)
            $infoUtente = $connection->getUserInfo($emailUtente);
            
            // Stato richiesta attuale
            $richiesta = $connection->getRequestStatus($emailUtente, $idAnimale);
            

            // GESTIONE POST RICHIESTA ADOZIONE
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-adoption-request'])) {
                
                // Dati in ingresso
                $lettera = trim($_POST['lettera-presentazione'] ?? '');
                $address = mb_convert_case(trim($_POST['new-address'] ?? ''), MB_CASE_TITLE, "UTF-8");
                $city    = mb_convert_case(trim($_POST['new-city'] ?? ''), MB_CASE_TITLE, "UTF-8");
                $CAP     = trim($_POST['new-cap'] ?? '');
                $trasportoRichiesto = isset($_POST['trasporto']); // checkbox

                // Aggiorno variabili valore per il form in caso di errore
                $valLettera = htmlspecialchars($lettera);
                $valVia = htmlspecialchars($address);
                $valCitta = htmlspecialchars($city);
                $valCap = htmlspecialchars($CAP);

                // Validazione Lettera
                if (strlen($lettera) < 10) {
                    $messaggiErrore['lettera'] = "La lettera di presentazione è troppo breve.";
                }

                // Validazione Indirizzo
                $regex_indirizzo = '/^[a-zA-Z\.\']{3,}\s+.+\s+(?:n\.?\s?)?\d+[a-zA-Z]?$/';
                $regex_citta = '/^[a-zA-Z\s\.\']{2,}$/';
                $regex_cap = '/^\d{5}$/';

                $hasAddress = strlen($address) > 0;
                $hasCity    = strlen($city) > 0;
                $hasCAP     = strlen($CAP) > 0;
                
                $isIndirizzoValido = false;

                if (!($hasAddress && $hasCity && $hasCAP) && ($hasAddress || $hasCity || $hasCAP)) {
                    $messaggiErrore['indirizzo_totale'] = "Indirizzo incompleto: compila tutto o nulla.";
                } else {
                    if ($hasAddress) {
                        if (!preg_match($regex_indirizzo, $address)) $messaggiErrore['indirizzo'] = "Formato via non valido.";
                        if (!preg_match($regex_citta, $city)) $messaggiErrore['citta'] = "Città non valida.";
                        if (!preg_match($regex_cap, $CAP)) $messaggiErrore['cap'] = "CAP non valido (5 cifre).";
                    }
                    if (empty($messaggiErrore['indirizzo']) && empty($messaggiErrore['citta']) && empty($messaggiErrore['cap']) && empty($messaggiErrore['indirizzo_totale'])) {
                        $isIndirizzoValido = true;
                    }
                }

                if ($isIndirizzoValido && empty($messaggiErrore['lettera'])) {
                    
                    // Aggiorna indirizzo utente se inserito
                    if ($hasAddress) {
                        $datiUpdate = ['address' => $address, 'city' => $city, 'CAP' => $CAP];
                        // Assumiamo esista questa funzione o una simile updateUserInfo
                        // Se non esiste updateUserInfo parziale, dovrai usare quella completa passando gli altri dati vecchi
                        $connection->updateUserAddress($emailUtente, $datiUpdate); 
                    }

$success = $connection->insertAdoptionRequest($emailUtente, $idAnimale, $lettera, $trasportoRichiesto);

                   if ($success) {
                        header("Location: visualizzazione-animale?id=" . $idAnimale);
                        exit;
                    } else {
                        $messaggiErrore['lettera'] = "Errore durante il salvataggio della richiesta.";
                    }
                }
            } else {
                // Se non è POST, precarichiamo i dati dal DB
                $valVia   = htmlspecialchars($infoUtente['Via'] ?? '');
                $valCitta = htmlspecialchars($infoUtente['Citta'] ?? '');
                $valCap   = htmlspecialchars($infoUtente['CAP'] ?? '');
            }
        }
    }
    $connection->closeConnection();
} else {
    // Gestione errore connessione DB
    echo "Errore connessione database";
    exit;
}



// DEFINIZIONE CONTENUTO PAGINA (Form o Stato)
$contenutoPagina = "";

if (!$utenteAccesso) {
    $contenutoPagina = "
    <aside id='contatta-rifugio'>
        <p> Vuoi adottare questo animale? <a href='registrati'>Registrati o accedi</a> se hai già un profilo e manda una richiesta!</p>
    </aside>";
    $infoAggiuntive='info-aggiuntive-separate';
} else if ($richiesta === false || $richiesta === null) {
    // FORM ADOZIONE
    $infoAggiuntive='info-aggiuntive-separate';
    $contenutoPagina = "<div id='richiesta-adozione'>
        <div class='column-container'>
            <div class='column-user'>
                <img id='richiesta-adozione-img' src='./assets/images/adozione.jpg' alt=''/>
            </div>
            <div class='column-user'>
                <form method='POST' action='' novalidate> 
                    <fieldset>
                        <legend id='legenda-richiesta-adozione'>Invia una richiesta di adozione!</legend>
                        <label for='lettera-presentazione'>Scrivi una breve lettera di presentazione:</label>
                        <textarea id='lettera-presentazione' name='lettera-presentazione' rows='7' cols='50' placeholder='Inserisci presentazione' required>[VALORE_LETTERA]</textarea>
                        <p class='error-form'>[ERROR_LETTERA]</p>
                    </fieldset>
                    <fieldset class='fieldset-indirizzo'>
                        <legend>Indirizzo</legend>
                        <p>Il profilo utente verrà aggiornato con l'indirizzo inserito.</p>
                        <div>
                            <label for='new-address'>Via e numero civico</label>
                            <input type='text' id='new-address' name='new-address' autocomplete='street-address' 
                            value='[via-utente]' placeholder='Via L. Da Vinci n.10' required>
                            <p class='error-form'>[erroriIndirizzo]</p>   
                        </div>
                        <div id='indirizzo-row'>
                            <div id='citta-container'>
                                <label for='new-city'>Città</label>
                                <input type='text' id='new-city' name='new-city' autocomplete='address-level2' 
                                value='[citta-utente]' placeholder='Roma' required>
                                <p class='error-form'>[erroriCitta]</p>
                            </div>
                            <div id='cap-container'>
                                <label for='new-cap'>CAP</label>
                                <input type='text' id='new-cap' name='new-cap' autocomplete='postal-code' 
                                value='[cap-utente]' placeholder='00000' required>
                                <p class='error-form'>[erroriCAP]</p>
                                <p class='error-form'>[erroriIndirizzoTotale]</p>   
                            </div>
                        </div>
                        <div id='trasporto-container'>
                            <label for='trasporto' class='column-container'>
                                <input type='checkbox' id='trasporto' name='trasporto'>
                                <div class='checkbox-text'>
                                    <span class='checkbox-title'>Voglio il trasporto dell’animale a casa</span>
                                    <span class='checkbox-description'>Spuntando la casella, verrà programmato il trasporto dell’animale. Ci si prende la responsibilità di essere presenti nel domicilio indicato alla data che verrà comunicata per email.</span>
                                </div>
                            </label>
                        </div>
                        <button class='orange-button' name='submit-adoption-request' type='submit'>Invia il Form</button>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
    <a href = '#top-page' class='torna-su-button'>
        <img src='./assets/icons/torna-su.svg' class='static' alt='torna su'/>
        <img src='./assets/icons/torna-su.gif' class='active' alt=''/>
    </a>";

    // Replacement Placeholders nel Form
    $contenutoPagina = str_replace('[VALORE_LETTERA]', $valLettera, $contenutoPagina);
    $contenutoPagina = str_replace('[via-utente]', $valVia, $contenutoPagina);
    $contenutoPagina = str_replace('[citta-utente]', $valCitta, $contenutoPagina);
    $contenutoPagina = str_replace('[cap-utente]', $valCap, $contenutoPagina);

    $contenutoPagina = str_replace('[ERROR_LETTERA]', $messaggiErrore['lettera'], $contenutoPagina);
    $contenutoPagina = str_replace('[erroriIndirizzo]', $messaggiErrore['indirizzo'], $contenutoPagina);
    $contenutoPagina = str_replace('[erroriCitta]', $messaggiErrore['citta'], $contenutoPagina);
    $contenutoPagina = str_replace('[erroriCAP]', $messaggiErrore['cap'], $contenutoPagina);
    $contenutoPagina = str_replace('[erroriIndirizzoTotale]', $messaggiErrore['indirizzo_totale'], $contenutoPagina);

} else {
    // STATI RICHIESTA ESISTENTE
    switch ($richiesta) {
        case 'Nuova': {
                $infoAggiuntive='info-aggiuntive-unite';

        }
        case 'In valutazione':
            {
                $infoAggiuntive='info-aggiuntive-unite';

         
            $contenutoPagina = file_get_contents('./src/template/partials/animale-pendente.html');
            }
            break;
        case 'Da trasportare':{
            $infoAggiuntive='info-aggiuntive-unite';
            $contenutoPagina = file_get_contents('./src/template/partials/animale-in-trasporto.html');
            break;
        }
        case 'Accettata':{
            $infoAggiuntive='info-aggiuntive-unite';

            break;
        }
        case 'Respinta':{
            $contenutoPagina = file_get_contents('./src/template/partials/animale-negata.html');
            break;
        }
        case 'Annullata':{
                $infoAggiuntive='info-aggiuntive-separate';

        }
        
        default:
        {    
            $infoAggiuntive='info-aggiuntive-separate';
            $contenutoPagina = file_get_contents('./src/template/partials/animale-form.html'); }
    }
}
// COSTRUZIONE BLOCCHI HTML

$CARDANIMALE1 = "
    <img id='foto-animale' src='$img' alt='Foto di $nome'>  
    <div id= 'info-generiche-testo'>
        <dl>
            <dt> Nome</dt> <dd> $nome </dd>
            <dt> Sesso</dt> <dd> $sesso </dd>
            <dt> Età</dt> <dd>$eta anni</dd>
            <dt> Razza</dt> <dd>$razza </dd>
            <dt> Pelo</dt> <dd> $pelo </dd>
            <dt> Taglia</dt> <dd>$taglia </dd>
            <dt> Colore</dt> <dd>$colore </dd>
        </dl>
    </div>
    <form method='post' action='' class='preferiti-form'>
        <input type='hidden' name='id-animale-preferito' value='$idAnimale'>
        <button type='submit' class='$classePreferito' aria-label='$statusPreferiti'>
            <img id='heart-normal' src='./assets/icons/$heartNormal' alt=''>
            <img id='heart-hover' src='./assets/icons/$heartHover' alt=''>
        </button>
    </form>
";

$CARDANIMALE2 = $infoAggiuntive==='info-aggiuntive-separate' ? "
    <h2> Altre informazioni</h2>" : '';
    $CARDANIMALE2 .= "
    <div id ='$infoAggiuntive'>
    <dl>
        <dt> Può essere trasportato</dt> <dd> $trasporto </dd>
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

$nav = buildUserNav($userMenu, './visualizzazione-animale', $_SESSION['email'] ?? false);
$main = file_get_contents('./src/template/main/visualizzazione-animale.html');
$footer = file_get_contents('./src/template/partials/footer.html');

$infoAggUnite='';
$main = str_replace('[ADOZIONE_STATUS]', $contenutoPagina, $main);
$main = str_replace('[INTERESSAMENTO-ANIMALE]', $giàInteressato, $main);
if($infoAggiuntive==='info-aggiuntive-separate'){
    $main = str_replace('[CARDANIMALE1]', $CARDANIMALE1, $main);
    $main = str_replace('[CARDANIMALE2]', $CARDANIMALE2, $main);
} else{
    $infoAggUnite=$CARDANIMALE2;
    $main = str_replace('[CARDANIMALE1]', $CARDANIMALE1, $main);
    $main = str_replace('[CARDANIMALE2]', '', $main);
}
$main = str_replace('[INFO-AGGIUNTIVE]', $infoAggUnite, $main);


$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

echo $paginaHTML;
?>