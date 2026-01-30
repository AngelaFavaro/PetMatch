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
$isAdopted = false;
//connesisone al DB
$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
if ($connessioneOK) {
	$infoRequest = $connessione->getAnimalRequest($_SESSION['email'],$_GET['id-animale']);
    $messageForm = cancelRequest($connessione);
    $getState = $connessione->getStateRequest($_SESSION['email'],$_GET['id-animale']);
    $isAdopted = $connessione->isAnimalAdopted($_GET['id-animale']);
}else{
	$messageForm['generic'] = "<p class='error'>Impossibile completare l'operazione, riprova più tardi.</p>";
}


if($infoRequest['DataFineValutazione']) $screenFineValutazione= date("Y-m-d", strtotime($infoRequest['DataFineValutazione']));

$fineRichiesta = $infoRequest['DataFineValutazione']?'<dt>Data fine valutazione:</dt><dd><time datetime="'.$screenFineValutazione.'">'.date("d/m/Y", strtotime($infoRequest['DataFineValutazione'])).'</time></dd>':'';

if (empty($infoRequest['ImgPath']) || !file_exists($infoRequest['ImgPath'])) {
    $infoRequest['ImgPath'] = $infoRequest['tipo']=='Cane'? 'assets/images/animals/defaultCane.jpg':'assets/images/animals/defaultGatto.jpg';
}

if($infoRequest['DataNascita']){
    $etaAnimale = calcolaEta($infoRequest['DataNascita']);
}

if($infoRequest['DataPartenza'] && $infoRequest['DataArrivo']){

    $screenArrivo = date("Y-m-d", strtotime($infoRequest['DataArrivo']));
    $screenPartenza = date("Y-m-d", strtotime($infoRequest['DataPartenza']));

    $dataPartenza = '<dt>Data di partenza:</dt><dd><em><time datetime="'.$screenPartenza.'">'. date("d/m/Y",strtotime($infoRequest['DataPartenza'])).'</time></em></dd>';
    $dataArrivo = '<dt>Data di partenza:</dt><dd><em><time datetime="'.$screenPartenza.'">'. date("d/m/Y",strtotime($infoRequest['DataArrivo'])).'</time></em></dd>';
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
$keywords = "<meta name='keywords' content='Adozione, La tua domanda, Stato della richiesta, Animale interessato'>";

$nav = buildNav($userMenu, './revisione-richiesta');
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

$paginaHTML = str_replace('[cardAnimal]', getCardAnimal($_GET['id-animale'], $_SESSION['admin'], $isAdopted), $paginaHTML);

$paginaHTML = str_replace('[isDisabled]', $isDisabled, $paginaHTML);
$paginaHTML = str_replace('[messaggiForm]', $messageForm, $paginaHTML);
$paginaHTML = str_replace('[imgAnimale]', htmlspecialchars($infoRequest['ImgPath'], ENT_QUOTES, 'UTF-8'), $paginaHTML);
$paginaHTML = str_replace('[nomeAnimale]', htmlspecialchars($infoRequest['NomeAnimale'], ENT_QUOTES, 'UTF-8'), $paginaHTML);

$screenRichiesta= date("Y-m-d", strtotime($infoRequest['DataRichiesta']));
if($infoRequest['DataInizioValutazione']) $screenInizioValutazione= date("Y-m-d", strtotime($infoRequest['DataInizioValutazione']));

$paginaHTML = str_replace('[dataRichiesta]', '<time datetime ="'.$screenRichiesta.'">'.date("d/m/Y", strtotime($infoRequest['DataRichiesta'])).'</time>', $paginaHTML);
$paginaHTML = str_replace('[StatoRichiesta]', htmlspecialchars($infoRequest['Stato'], ENT_QUOTES, 'UTF-8'), $paginaHTML);
$paginaHTML = str_replace('[DataInizio]', $infoRequest['DataInizioValutazione']? '<time datetime ="'.$screenInizioValutazione.'">'.date("d/m/Y", strtotime($infoRequest['DataInizioValutazione'])):'</time><em>La richiesta non è ancora stata presa in carico.</em>', $paginaHTML);
$paginaHTML = str_replace('[DataFineRichiesta]', $fineRichiesta, $paginaHTML);
$paginaHTML = str_replace('[dataPartenza]', $dataPartenza, $paginaHTML);
$paginaHTML = str_replace('[dataArrivo]', $dataArrivo, $paginaHTML);
$paginaHTML = str_replace('[letteraPresentazione]', htmlspecialchars($infoRequest['LetteraPresentazione'], ENT_QUOTES, 'UTF-8'), $paginaHTML);
$paginaHTML = str_replace('[richiestaTrasporto]', $infoRequest['Trasporto']?'Si':'No', $paginaHTML);
$paginaHTML = str_replace('[RazzaAnimale]', htmlspecialchars($infoRequest['Razza'], ENT_QUOTES, 'UTF-8'), $paginaHTML);
$paginaHTML = str_replace('[SessoAnimale]', $infoRequest['Sesso']=='M'?'Maschio':'Femmina', $paginaHTML);
$paginaHTML = str_replace('[EtàAnimale]', htmlspecialchars($etaAnimale, ENT_QUOTES, 'UTF-8'), $paginaHTML);
$paginaHTML = str_replace('[TrasportoAnimale]', $infoRequest['TrasportoAnimale']?'Si':'No', $paginaHTML);
$paginaHTML = str_replace('[FamigliaIdealeAnimale]', htmlspecialchars($infoRequest['DescrFamiglia'], ENT_QUOTES, 'UTF-8'), $paginaHTML);
$paginaHTML = str_replace('[CondizioniMedicheAnimale]', $infoRequest['CondizioniMediche']?htmlspecialchars($infoRequest['CondizioniMediche'], ENT_QUOTES, 'UTF-8'):'Sano', $paginaHTML);
$paginaHTML = str_replace('[DescrizioneCaratterialeAnimale]', htmlspecialchars($infoRequest['DescrComportamentale'], ENT_QUOTES, 'UTF-8'), $paginaHTML);

$showModal = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['show-dialog']);
$closeModal = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close-dialog']);
$paginaHTML = str_replace('[openDialog]', $showModal?'open':'', $paginaHTML);
$paginaHTML = str_replace('[openDialog]', $closeModal?'':'', $paginaHTML);

echo $paginaHTML;
?>