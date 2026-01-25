<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;

$userInfo = "";
$userRequest = "";

//se non sono loggato rimando alla pagina di login
if (!isset($_SESSION['email'])) {
    header("Location: ./accedi");
    exit;    
}else if(isset($_SESSION['admin']) && !$_SESSION['admin'] === true){
    header("Location: ./profilo-utente");
    exit; 
}

function createRequestList(DBAccess $conn): string {
    $stati = ['Nuova', 'In valutazione', 'Accettata', 'Respinta', 'Annullata','Da trasportare',];
    
    $gruppi = array_fill_keys($stati, '');

    $sendFiltro = "'Nuova','In valutazione','Accettata','Respinta','Annullata','Da trasportare'";
    
    $richieste = $conn->getUserRequests($_GET['email'], $sendFiltro);

    foreach ($richieste as $richiesta) {
        $nomeAnimale = htmlspecialchars($richiesta['NomeAnimale']);
        $statoAttuale = $richiesta['Stato'];
        $statoText = '';

        switch($statoAttuale){
            case 'In valutazione':
                $statoText = 'Ha <strong>in valutazione</strong> <em>'.$nomeAnimale.'</em>.';
                break;
            case 'Da trasportare':
                $statoText = 'Ha un <strong>trasporto</strong> per <em>'.$nomeAnimale.'</em>.';
                break;
            case 'Accettata':
                $statoText = 'Ha <strong>adottato</strong> <em>'.$nomeAnimale.'</em>.';
                break;
            case 'Respinta':
                $statoText = 'È stato <strong>respinto</strong> per <em>'.$nomeAnimale.'</em>.';
                break;
            case 'Annullata':   
                $statoText = 'La richiesta per <em>'.$nomeAnimale.'</em> è stata <strong>annullata</strong>.';
                break;
            case 'Nuova':
                $statoText = 'Ha <strong>fatto richiesta</strong> per <em>'.$nomeAnimale.'</em>.';
                break;
        }

        $li = '<li>
                <article>
                    <p>'.$statoText.'</p>
                    <a href="./richieste-adozione?email='.urlencode($_GET['email']).'&id-animale='.urlencode($richiesta['IDanimale']).'" class="brown-button">Vedi richiesta</a>
                </article>
               </li>';

        if (isset($gruppi[$statoAttuale])) {
            $gruppi[$statoAttuale] .= $li;
        }
    }

    foreach ($stati as $stato) {
        $content = $gruppi[$stato];
        if (empty($content)) {
            $content = '<li class="empty-message">L\'utente non ha ancora richieste.</li>';
        }
        $htmlOutput .= '<ul class="tab-content" aria-label="Richieste di tipo: '.$stato.'">' . $content . '</ul>';
    }
    $htmlOutput = '<div id="start-requests" tabindex="-1" >'.$htmlOutput.'</div>';

    return $htmlOutput;
}

$listRequest = "";

$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
if ($connessioneOK) {
    $listRequest = createRequestList($connessione);
    $NRequestsByStatus = $connessione->getNRequestByStatusUser($_GET['email']);
    $infoUtente = $connessione->getUserInfo($_GET['email']);
}else{
	$messaggiForm = "<p class='error-form'>Impossibile inviare la richiesta, riprova più tardi.</p>";
}

if (empty($infoUtente['ImgPath']) || !file_exists($infoUtente['ImgPath'])) {
    $infoUtente['ImgPath'] = 'assets/images/users/default-pic.png';
}

if($infoUtente['Via'] === null || $infoUtente['Citta'] === null || $infoUtente['CAP'] === null){
    $indirizzoCompleto = "<em>Sconosciuto</em>";
}else{
    $indirizzoCompleto = $infoUtente['Via'] . ', ' . $infoUtente['Citta'] . ' ' . $infoUtente['CAP'];
}

if($infoUtente['Telefono']){
    $telefonoGrezzo = $infoUtente['Telefono'];
    $numero = substr($telefonoGrezzo, -10);
    $prefisso = substr($telefonoGrezzo, 0, -10);
    $printTelefono = trim($prefisso . ' ' . $numero);
}

$paginaHTML = file_get_contents('./src/template/layout-admin.html');
$main = file_get_contents('./src/template/main/admin/profilo-richiedente.html');
$breadcrumb = getBreadcrumb('profilo-richiedente', $pagine);
$nav = buildAdminNav($adminMenu,'./profilo-richiedente');
$keywords = "<meta name='keywords' content='profilo richiedente, informazioni utente'>";
$title = "<title>Visualizza profilo candidato - PetMatch</title>";
$description = "<meta name='description' content='Visualizza il profilo candidato per poterne gestire le richieste.'>";

$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);

$paginaHTML = str_replace('[elencoRichieste]', $listRequest, $paginaHTML);
$paginaHTML = str_replace('[n-nuove]', $NRequestsByStatus['Nuova'], $paginaHTML);
$paginaHTML = str_replace('[n-valutazione]', $NRequestsByStatus['In valutazione'], $paginaHTML);
$paginaHTML = str_replace('[n-accettate]', $NRequestsByStatus['Accettata'], $paginaHTML);
$paginaHTML = str_replace('[n-respinte]', $NRequestsByStatus['Respinta'], $paginaHTML);
$paginaHTML = str_replace('[n-annullate]', $NRequestsByStatus['Annullata'], $paginaHTML);
$paginaHTML = str_replace('[n-trasporto]', $NRequestsByStatus['Da trasportare'], $paginaHTML);

$paginaHTML = str_replace('[imgRichiedente]', $infoUtente['ImgPath'], $paginaHTML);
$paginaHTML = str_replace('[NomeRichiedente]', $infoUtente['Nome'], $paginaHTML);
$paginaHTML = str_replace('[CognomeRichiedente]', $infoUtente['Cognome'], $paginaHTML);
$paginaHTML = str_replace('[IndirizzoRichiedente]', $indirizzoCompleto, $paginaHTML);
$paginaHTML = str_replace('[EmailRichiedente]', $_GET['email'], $paginaHTML);
$paginaHTML = str_replace('[TelefonoRichiedente]', $printTelefono, $paginaHTML);

echo $paginaHTML;
?>