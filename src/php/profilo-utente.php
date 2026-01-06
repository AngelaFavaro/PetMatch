<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;
session_start();

//se non sono loggato rimando alla pagina di login
if (!isset($_SESSION['loggato']) || $_SESSION['loggato'] !== true) {
    header("Location: ./accedi");
    exit;    
}

$filtroCorrente = '%';
if (isset($_GET['state'])) {
    $filtroCorrente = htmlspecialchars($_GET['state']);
}

function createMovementList(DBAccess $conn, $filtro = '%'): string {
    $listaMovimenti = '';

    $richieste = $conn->getUserRequests($_SESSION['email'],$filtro);

    if (count($richieste) == 0) {
        return "<p id=\"query-vuota\">Non sono presenti richieste di adozione.</p>";
    }else{
        $listaMovimenti = '<ul id="lista-movimenti" aria-labelledby="ultimi-movimenti">';
        foreach ($richieste as $richiesta) {

            $statoRichiesta = '';

            $nomeAnimale = htmlspecialchars($richiesta['NomeAnimale']);

            switch($richiesta['Stato']){
                case 'In valutazione':
                    $statoRichiesta = 'La tua richesta di adozione per <em>'.$nomeAnimale.'</em> è in <strong>valutazione.</strong>';
                    break;
                case 'Da trasportare':
                    $statoRichiesta = '<em>'.$nomeAnimale.'</em> partità il giorno <em>'.$richiesta['DataPartenza'].'</em> e arriverà il giorno<em>'.$richiesta['DataArrivo'].'</em>!';
                    break;
                case 'Conclusa':
                    $statoRichiesta = 'Complimenti! Hai adottato con successo <em>'.$nomeAnimale.'</em>.';
                    break;
                case 'Respinta':
                    $statoRichiesta = 'Siamo spiacenti di informarti che la tua richiesta di adozione per <em>'.$nomeAnimale.'</em> è stata <strong>respinta.</strong>';
                    break;
                case 'Annullata':   
                    $statoRichiesta = 'Hai annullato la tua richiesta di adozione per <em>'.$nomeAnimale.'</em>.';
                    break;
                case 'Nuova':
                    $statoRichiesta = 'La tua richiesta di adozione per <em>'.$nomeAnimale.'</em> è stata inviata con successo e sarà valutata a breve.';
                    break;
            }

            // TODO: il link "Vedi animale" deve portare alla pagina di dettaglio dell'animale, da fare quando la pagina sarà pronta
            $listaMovimenti .= '<li>
                <article>
                    <p>'.$statoRichiesta.'</p>
                    <a href="">Vedi animale</a>
                </article>
            </li>';
        }

        $listaMovimenti .= "</ul>";
        return $listaMovimenti;
    }
}





$infoUtente = null;
$listaAvvisi = "";

//connesisone al DB
$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
if ($connessioneOK) {
	if (isset($_SESSION['email'])) {
        $infoUtente = $connessione->getUserInfo($_SESSION['email']);
        $listaAvvisi = createMovementList($connessione, $filtroCorrente);
    }
    $connessione->closeConnection();
}else{
	header("Location: ./404");
    exit;    
}

//se non riesco a prendere le info dell'utente rimando alla pagina di login, vuol dire che l'utente non era nel db 
// (impossibile ma meglio essere sicuri)
if ($infoUtente == null) {
    header("Location: ./login"); 
    exit;
}

if($infoUtente['Via'] == null || $infoUtente['Citta'] == null || $infoUtente['CAP'] == null){
    $indirizzoCompleto = "<em>Sconosciuto</em>";
}else{
    $indirizzoCompleto = $infoUtente['Via'] . ', ' . $infoUtente['Citta'] . ' ' . $infoUtente['CAP'];
}

if($infoUtente['Telefono'] == null){
    $infoUtente['Telefono'] = "<em>Sconosciuto</em>";
}



$paginaHTML = file_get_contents('./src/template/layout.html');
if ($paginaHTML === false) {
    $paginaHTML = "<p class='error-form'>Errore: template layout.html non trovato o non leggibile.</p>";
}


$title = '<title>Profilo - PetMatch </title>';
$description = '<meta name="description" content="Profilo di PetMatch">';
$keywords = "";

$nav = buildUserNav($userMenu, './profilo-utente');

$footer = file_get_contents('./src/template/partials/footer.html');

$breadcrumb = getBreadcrumb('profilo-utente', $pagine);

$main = file_get_contents('./src/template/main/profilo-utente.html');

$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);

$paginaHTML = str_replace('[lista avvisi]', $listaAvvisi, $paginaHTML);
//il filtro selezionato nella tendina precedentemente all'invio della form viene mantenuto
$paginaHTML = str_replace( 'value="' . $filtroCorrente . '"', 'value="' . $filtroCorrente . '" selected', $paginaHTML);

$paginaHTML = str_replace('[imgPath]', $infoUtente['ImgPath'], $paginaHTML);
$paginaHTML = str_replace('[nome-utente]', $infoUtente['Nome'], $paginaHTML);
$paginaHTML = str_replace('[cognome-utente]', $infoUtente['Cognome'], $paginaHTML);
$paginaHTML = str_replace('[indirizzo-utente]', $indirizzoCompleto, $paginaHTML);
$paginaHTML = str_replace('[email-utente]', $_SESSION['email'], $paginaHTML);
$paginaHTML = str_replace('[telefono-utente]', $infoUtente['Telefono'], $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

echo $paginaHTML;

?>