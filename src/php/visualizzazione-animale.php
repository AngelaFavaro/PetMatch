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
$connection = new DBAccess();
if ($connection->openDBConnection()){
    if( $idAnimale ) {
        $dettagliAnimale = $connection->getAnimalDetails($idAnimale);
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
            $condizioniMediche = $dettagliAnimale['condizioni_mediche']? htmlspecialchars($dettagliAnimale['condizioni_mediche']): 'Nessuna';
            if (!empty($dettagliAnimale['imgPath']) && file_exists($dettagliAnimale['imgPath'])) {
                $img = $dettagliAnimale['imgPath'];
            } else {
                $imgPath = ($dettagliAnimale['tipo']==='Cane') ? 'assets/images/animals/defaultCane.jpg' : 'assets/images/animals/defaultGatto.jpg';
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

            $classePreferito = $inPreferiti ? 'is-favorite' : 'not-favorite';
            $heartNormal = $inPreferiti ? 'active-like.svg' : 'inactive-like.svg';
            $heartHover = $inPreferiti ? 'inactive-like.svg' : 'active-like.svg';
            $statusPreferiti = $inPreferiti ? 'Rimuovi dai preferiti' : 'Aggiungi ai preferiti';
              
            if (!empty($dettagliAnimale['imgPath']) && file_exists($dettagliAnimale['imgPath'])) {
                $img = $dettagliAnimale['imgPath'];
            } else {
                $img = ($dettagliAnimale['tipo']==='Cane') ? 'assets/images/animals/defaultCane.jpg' : 'assets/images/animals/defaultGatto.jpg';
            }



            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id-animale-preferito'])) {
                $idAnimale = (int)$_POST['id-animale-preferito'];
                if ($connection->isAnimalInFavorites($emailUtente, $idAnimale)) {
                    $connection->removeFromFavorites($emailUtente, $idAnimale);
                    $azione = 'rimosso';
                } else {
                        $connection->addToFavorites($emailUtente, $idAnimale);
                    $azione = 'aggiunto';
                }       
             }
    } else {
        // UTENTE NON LOGGATO → COOKIE
        $preferiti = getGuestFavorites();

        if (in_array($idAnimale, $preferiti)) {
            $preferiti = array_diff($preferiti, [$idAnimale]);
            $azione = 'rimosso';
        } else {
            $preferiti[] = $idAnimale;
            $azione = 'aggiunto';
        }

        saveGuestFavorites($preferiti);
   } 

 //  $redirect = $_SERVER['HTTP_REFERER'] ?? 'visualizzazione-animale';
 // header("Location: $redirect");
  //  exit;
} 

            $classePreferito = $inPreferiti ? 'is-favorite' : 'not-favorite';
            $heartNormal = $inPreferiti ? 'active-like.svg' : 'inactive-like.svg';
            $heartHover = $inPreferiti ? 'inactive-like.svg' : 'active-like.svg';
            $statusPreferiti = $inPreferiti ? 'Rimuovi dai preferiti' : 'Aggiungi ai preferiti';

if ($utenteAccesso) {
    $richiesta=$connection->getRequestStatus($emailUtente, $idAnimale);
}
$connection->closeConnection();
$infoAnimale1= "
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
                    <form method='post' action='animali' class='preferiti-form'>
                        <input type='hidden' name='id-animale-preferito' value='$idAnimale'>
                        <button type='submit' class='$classePreferito' aria-label='$statusPreferiti'>
                            <img id='heart-normal' src='./assets/icons/$heartNormal' alt=''>
                            <img id='heart-hover' src='./assets/icons/$heartHover' alt=''>
                        </button>
                    </form>
";

$infoAnimale2= "<div id ='info-aggiuntive'>
                <dl>
                    <dt> Può essere trasportato</dt> <dd> $trasporto </dd>
                    <dt> Condizioni mediche</dt> <dd> $condizioniMediche</dd>
                    <dt> Descrizione carattere</dt> <dd>$comportamento </dd>
                    <dt> Famiglia ideale</dt> <dd>$famiglia</dd>
                </dl>
            </div>";

if (!$utenteAccesso) {
        $contenutoPagina = '
        <aside id="contatta-rifugio">
            <p> Vuoi adottare questo animale? <a href="registrati">Registrati o accedi</a> se hai già un profilo e manda una richiesta!</p>  <!--mettere pagina di accesso dentro href-->
        </aside>';
} else if ($richiesta === false || $richiesta === null) {
        $contenutoPagina = "<div id='richiesta-adozione'>
    <div class='column-container'>
        <div class='column-user'>
            <img id='richiesta-adozione-img' src='./assets/images/adozione.jpg' alt=''/>
        </div>
        <div class='column-user'>
            <form  method='POST' action='#' novalidate> 
                <fieldset>
                    <legend>
                        Invia una richiesta di adozione!
                    </legend>
                    <label for='lettera-presentazione'>Scrivi una breve lettera di presentazione:</label>
                    <textarea id='lettera-presentazione' name='lettera-presentazione' rows='8' cols='50' placeholder='Inserisci presentazione' required></textarea>
                </fieldset>
                <fieldset class='fieldset-indirizzo'>
                    <legend>Indirizzo</legend>
                    <p>Tutti i campi dell\'indirizzo devono essere completi, altrimenti nessuno.</p>
                    <div>
                        <label for='new-address'>Via e numero civico</label>
                        <input type='text' id='new-address' name='new-address' autocomplete='street-address' 
                        value='[via-utente]' placeholder='Via L. Da Vinci n.10' aria-label='Tutti i campi dell\'indirizzo devono essere completi, altrimenti nessuno.'>
                        <p class='error-form'>[erroriIndirizzo]</p>   
                     </div>
                    <div>
                        <label for='new-city'>Città</label>
                        <input type='text' id='new-city' name='new-city' autocomplete='address-level2' 
                        value='[citta-utente]' placeholder='Roma'>
                        <p class='error-form'>[erroriCitta]</p>
                    </div>
                    <div>
                        <label for='new-cap'>CAP</label>
                        <input type='text' id='new-cap' name='new-cap' autocomplete='postal-code' 
                        value='[cap-utente]' placeholder='00000'>
                        <p class='error-form'>[erroriCAP]</p>
                        <p class='error-form'>[erroriIndirizzoTotale]</p>   
                    </div>
                    <div>
                        <label for='trasporto'>
                            <span class='checkbox-title'>
                               Voglio il trasporto dell’animale a casa
                            </span>
                            <span class='checkbox-description'>
                               Spuntando la casella, verrà programmato il trasporto dell’animale. Ci si prende la responsabilità di essere presenti nel domicilio indicato alla data che verrà comunicata per email.
                            </span>
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
    </a>
";
        $errors = $_SESSION['form_errors_adozione'] ?? [];
        $values = $_SESSION['form_values_adozione'] ?? ['lettera'=>'', 'indirizzo'=>''];
        unset($_SESSION['form_errors_adozione'], $_SESSION['form_values_adozione']);

        $contenutoPagina = str_replace('[ERROR_INDIRIZZO]', $errors['indirizzo'] ?? '', $contenutoPagina);
        $contenutoPagina = str_replace('[ERROR_LETTERA]', $errors['lettera'] ?? '', $contenutoPagina);
        $contenutoPagina = str_replace('[VALORE_INDIRIZZO]', htmlspecialchars($values['indirizzo'] ?? $indirizzo), $contenutoPagina);
        $contenutoPagina = str_replace('[VALORE_LETTERA]', htmlspecialchars($values['lettera'] ?? ''), $contenutoPagina);
} else {
    switch ($richiesta) {
        case 'Nuova':
        $contenutoPagina = file_get_contents('./src/template/partials/animale-pendente.html');
            break;
        case 'In valutazione':
        $contenutoPagina = file_get_contents('./src/template/partials/animale-pendente.html');
            break;
        case 'Da trasportare':
        $contenutoPagina = file_get_contents('./src/template/partials/animale-in-trasporto.html');
            break;
        case 'Respinta':
        $contenutoPagina = file_get_contents('./src/template/partials/animale-negata.html');
            break;
        case 'Annullata':
        $contenutoPagina = file_get_contents('./src/template/partials/animale-form.html');
            break;
        case 'Accettata':
        $contenutoPagina = file_get_contents('./src/template/partials/animale-in-trasporto.html');
            break;
        default:
        $contenutoPagina = file_get_contents('./src/template/partials/animale-form.html');
    }
}




$paginaHTML = file_get_contents('./src/template/layout.html');


$title = "<title>nomeAnimale - PetMatch</title>"; //mettere $ in nomeAnimale qui e nelle 2 righe sotto 
$description = "<meta name='description' content='Scheda di nomeAnimale disponibile per adozione'>";
$keywords = "<meta name='keywords' content='nome, adozione, PetMatch, razza'>";
$breadcrumb = getBreadcrumb('visualizzazione-animale', $pagine);

$nav = buildUserNav($userMenu, './visualizzazione-animale', $_SESSION['email'] ?? false);
$main = file_get_contents('./src/template/main/visualizzazione-animale.html');
$footer = file_get_contents('./src/template/partials/footer.html');

$main = str_replace('[ADOZIONE_STATUS]', $contenutoPagina, $main);
$main = str_replace('[INTERESSAMENTO-ANIMALE]', $giàInteressato, $main);
$main = str_replace('[INFOANIMALE1]', $infoAnimale1, $main);
$main = str_replace('[INFOANIMALE2]', $infoAnimale2, $main);





/* replace */
$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

echo $paginaHTML;

?>
