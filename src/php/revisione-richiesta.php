<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;


function cancelRequest(DBAccess $conn) {

    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete-request'])) { 
        $risultato = $conn->updateRequestState($_SESSION['email'], $_GET['id-animale'], 'Annullata');
        if($risultato){
            header('Location: ./revisione-richiesta?id-animale='.$_GET['id-animale']);
            exit;
        }else{
            return "<p class='error'>Impossibile completare l\'operazione, riprova più tardi.</p>";
        }
    } 
}


if (isset($_SESSION['email'])) {//se non sono loggato rimando alla pagina di accedi
    if(!isset($_GET['id-animale'])){ // se non ho un id settato, rimando alla pagina del profilo
        header("Location: ./profilo-utente?state-richieste=all");
        exit;
    }else if(isset($_SESSION['admin']) && $_SESSION['admin'] === true){ //se sono admin, rimando a richieste-adozioni dell'admin
        header("Location: ./richieste-adozione");
        exit;    
    }
} else{
    header("Location: ./accedi");
    exit;    
}

$infoRequest = "";
$messageForm = "";
//connesisone al DB
$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
if ($connessioneOK) {
	$infoRequest = $connessione->getAnimalRequest($_SESSION['email'],$_GET['id-animale']);
    $messageForm = cancelRequest($connessione);
    $getState = $connessione->getStateRequest($_SESSION['email'],$_GET['id-animale']);
}else{
	$messageForm['generic'] = "<p class='error'>Impossibile completare l'operazione, riprova più tardi.</p>";
}

$fineRichiesta = $infoRequest['DataFineValutazione']?'<dt>Data fine valutazione:</dt><dd>'.date("d/m/Y", strtotime($infoRequest['DataFineValutazione'])).'</dd>':'';

if($infoRequest['DataNascita']){
    $etaAnimale = calcolareEta($infoRequest['DataNascita']);
}

if($infoRequest['DataPartenza'] && $infoRequest['DataPartenza']){
    $dataPartenza = '<dt>Data di partenza:</dt><dd><em>'. date("d/m/Y",strtotime($infoRequest['DataPartenza'])).'</em></dd>';
    $dataArrivo = '<dt>Data di partenza:</dt><dd><em>'. date("d/m/Y",strtotime($infoRequest['DataArrivo'])).'</em></dd>';
}else{
    $dataPartenza = '';
    $dataArrivo = '';
}

$isDisabled = ($getState == 'Nuova' || $getState == 'In valutazione')?'':'disabled'; 



$paginaHTML = file_get_contents('./src/template/layout.html');
if ($paginaHTML === false) {
	$paginaHTML = "<p>Errore: template layout.html non trovato o non leggibile.</p>";
}

$title = '<title>Revisione richiesta - PetMatch </title>';
$description = '<meta name="description" content="Rivedi richiesta di addozione">';
$keywords = "";

$nav = buildUserNav($userMenu, './revisione-richiesta', $_SESSION['email'] ?? false);
$footer = buildFooter($footerMenu,  './revisione-richiesta');

$breadcrumb = getBreadcrumb('revisione-richiesta', $pagine);

$main = file_get_contents('./src/template/main/revisione-richiesta.html');

$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

$paginaHTML = str_replace('[cardAnimal]', getCardAnimal(), $paginaHTML);

$paginaHTML = str_replace('[isDisabled]', $isDisabled, $paginaHTML);
$paginaHTML = str_replace('[messaggiForm]', $messageForm, $paginaHTML);
$paginaHTML = str_replace('[imgAnimale]', $infoRequest['ImgPath'], $paginaHTML);
$paginaHTML = str_replace('[nomeAnimale]', $infoRequest['NomeAnimale'], $paginaHTML);
$paginaHTML = str_replace('[dataRichiesta]', date("d/m/Y", strtotime($infoRequest['DataRichiesta'])), $paginaHTML);
$paginaHTML = str_replace('[StatoRichiesta]', $infoRequest['Stato'], $paginaHTML);
$paginaHTML = str_replace('[DataInizio]', $infoRequest['DataInizioValutazione']? date("d/m/Y", strtotime($infoRequest['DataInizioValutazione'])):'<em>La richiesta non è ancora stata presa in carico.</em>', $paginaHTML);
$paginaHTML = str_replace('[DataFineRichiesta]', $fineRichiesta, $paginaHTML);
$paginaHTML = str_replace('[dataPartenza]', $dataPartenza, $paginaHTML);
$paginaHTML = str_replace('[dataArrivo]', $dataArrivo, $paginaHTML);
$paginaHTML = str_replace('[letteraPresentazione]', $infoRequest['LetteraPresentazione'], $paginaHTML);
$paginaHTML = str_replace('[richiestaTrasporto]', $infoRequest['Trasporto']?'Si':'No', $paginaHTML);
$paginaHTML = str_replace('[RazzaAnimale]', $infoRequest['Razza'], $paginaHTML);
$paginaHTML = str_replace('[SessoAnimale]', $infoRequest['Sesso']=='M'?'Maschio':'Femmina', $paginaHTML);
$paginaHTML = str_replace('[EtàAnimale]', $etaAnimale, $paginaHTML);
$paginaHTML = str_replace('[TrasportoAnimale]', $infoRequest['TrasportoAnimale']?'Si':'No', $paginaHTML);
$paginaHTML = str_replace('[FamigliaIdealeAnimale]', $infoRequest['DescrFamiglia'], $paginaHTML);
$paginaHTML = str_replace('[CondizioniMedicheAnimale]', $infoRequest['CondizioniMediche']?$infoRequest['CondizioniMediche']:'Sano', $paginaHTML);
$paginaHTML = str_replace('[DescrizioneCaratterialeAnimale]', $infoRequest['DescrComportamentale'], $paginaHTML);


echo $paginaHTML;
?>