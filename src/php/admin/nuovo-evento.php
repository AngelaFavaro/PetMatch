<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;

if (session_status() === PHP_SESSION_NONE) { session_start(); }

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) { 
    header("Location: ./eventi");
    exit;
}

$NewEventInfo= [
    'Titolo' => '', 
	'DataEvento' => '', 
	'DescrEvento' => '', 
	'ImgPath' => '',
	'Via' => '',
	'Citta' => '',
    'createMore' => ''
];

function createNewEvent(DBAccess $conn, &$newEventValues, bool $isModified): array {

    $message = [
        'generic' => '', 'titolo' => '', 'data' => '', 'descrizione' => '',
        'foto' => '', 'via' => '','citta' => '', 'existEvent' => ''
    ];

    $oldTitle = $_GET['titolo'] ?? '';
    $oldData = $_GET['data'] ?? '';

    if (isset($_SESSION['form_status_info']) && $_SESSION['form_status_info'] === 'error') {
        $savedErrors = $_SESSION['form_errors_info'] ?? [];
        foreach ($savedErrors as $key => $val) {
            $message[$key] = ($key === 'generic') ? $val : "<p class='error-form'>$val</p>";
        }
        $savedInputs = $_SESSION['form_inputs'] ?? [];

		$newEventValues['Titolo']      = $savedInputs['title-event'] ?? '';
        $newEventValues['DataEvento']        = $savedInputs['day-event'] ?? '';
        $newEventValues['DescrEvento'] = $savedInputs['desc-event'] ?? '';
        $newEventValues['Via']         = $savedInputs['address-event'] ?? '';
        $newEventValues['Citta']       = $savedInputs['city-event'] ?? '';
        $newEventValues['ImgPath']        = $savedInputs['foto'] ?? '';
        $newEventValues['createMore']  = $savedInputs['createMore'] ?? '';
		
        unset($_SESSION['form_status_info'], $_SESSION['form_errors_info'], $_SESSION['form_inputs']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-event'])) { 
        $errors = [];
        $fotoPath = "";
        
        // Recupero campi testo
        $titoloValue = $_POST['title-event'] ?? '';
        $dayValue = trim($_POST['day-event'] ?? '');
        $descValue = trim($_POST['desc-event'] ?? '');
        $addressValue = trim($_POST['address-event'] ?? '');
        $cityValue = trim($_POST['city-event'] ?? '');
        $createMoreValue = isset($_POST['createMore'])?1:0;

		$titoloValue = htmlspecialchars($titoloValue, ENT_QUOTES, 'UTF-8');
		$dayValue = htmlspecialchars($dayValue, ENT_QUOTES, 'UTF-8');
		$descValue = htmlspecialchars($descValue, ENT_QUOTES, 'UTF-8');
		$addressValue = htmlspecialchars($addressValue, ENT_QUOTES, 'UTF-8');
		$cityValue = htmlspecialchars($cityValue, ENT_QUOTES, 'UTF-8');

        $regexData = '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/';
		$regex_indirizzo = '/^[a-zA-Z\.\']{3,}\s+.+\s+(?:n\.?\s?)?\d+[a-zA-Z]?$/';
        $regex_citta = '/^[\p{L}\s\.\']{2,}$/u';

        if (empty($titoloValue)){
            $errors['titolo'] = "Inserisci un titolo.";
        }
        else if (strlen($titoloValue) < 2 ){
            $errors['titolo'] = "Il titolo è troppo corto.";
        } else if(strlen($titoloValue)>40){
            $errors['titolo'] = "Il titolo è troppo lungo.";
        }

        if (empty($descValue)){
            $errors['descrizione'] = "Inserisci una descrizione.";
        }else if (strlen($descValue) < 5 ){
            $errors['descrizione'] = "La descrizione è troppo corta.";
        }else if(strlen($descValue)>255){
            $errors['descrizione'] = "La descrizione è troppo lunga.";
        }

        if(empty($addressValue)){
            $errors['via'] = "Inserisci la via.";
        } 
        else if (strlen($addressValue) < 3 ){
            $errors['via'] = "La via è troppo corta.";
        }else if(strlen($addressValue) > 255){
            $errors['via'] = "La via è troppo lunga.";
        }
        else if(!preg_match($regex_indirizzo, $addressValue)){
            $errors['via'] = "La via non è valida.";
        } 


        if (empty($cityValue)) {
            $errors['citta'] = "Inserisci la città.";
        }else if (strlen($cityValue) < 2 ){
            $errors['citta'] = "La città è troppo corto.";
        }else if(strlen($cityValue) >100){
            $errors['citta'] = "La città è troppo lunga.";
        }else if(!preg_match($regex_citta, $cityValue)){
            $errors['citta'] = "La città non è valida.";
        } 

        if (empty($dayValue)){
            $errors['data'] = "Inserisci il giorno.";
        } else if ($dayValue < date('Y-m-d')) {
            $errors['data'] = "L'evento non può essere nel passato.";
        }

        if($isModified){
            //altrimenti per nomi con gli apostri da problemi
            $oldTitle = isset($_GET['titolo'])? $_GET['titolo'] : '';
            $oldData = isset($_GET['data'])? $_GET['data'] : '';
        }

        if($conn -> checkEventExists($titoloValue, $dayValue)){
            if(!($oldTitle!=='' && $oldData!=='' && $titoloValue === $oldTitle && $dayValue === $oldData))
                $errors['existEvent'] ='Un evento con il titolo '.$titoloValue.' e data '.date("d/m/Y",strtotime($dayValue)).' esiste già.';
        }
        
        // Gestione Foto
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $path = uploadImage($_FILES['foto'], 'events');
            if ($path) { $fotoPath = $path; }
        } elseif (!empty($_POST['old-foto'])) {
            $fotoPath = $_POST['old-foto'];
        } else {
            $errors['foto'] = "Inserisci una foto.";
        }

        if (empty($errors)) {
            $infoDB = [
                'titolo' => $titoloValue, 
                'data' => $dayValue, 
                'descrizione' => $descValue,
                'via' => $addressValue, 
                'citta' => $cityValue, 
                'foto' => $fotoPath,
                'email' => $_SESSION['email']
            ];

            $result = $isModified ? $conn->updateEvent($infoDB, $oldTitle, $oldData) : $conn->insertNewEvent($infoDB);

            if($result){
                if (!$isModified && $createMoreValue) { header("Location: ./nuovo-evento?createMore=1"); }
                else { header("Location: ./dettagli-evento?titolo=".urlencode($titoloValue)."&data=".urlencode($dayValue)); }
                exit;
            } else {
                $errors['generic'] = "Errore durante il salvataggio nel database.";
            }
		}

        $_SESSION['form_status_info'] = 'error';
        $_SESSION['form_errors_info'] = $errors;
        $_SESSION['form_inputs'] = $_POST;
        $_SESSION['form_inputs']['foto'] = $fotoPath;

        $redirect = $isModified ? "./modifica-evento?titolo=".urlencode($oldTitle)."&data=".urlencode($oldData) : "./nuovo-evento";
        header("Location: $redirect");
        exit;
    }
    return $message;
}


$currentUri = $_SERVER['REQUEST_URI'];
$isModifiedEvent=false;

if(strpos($currentUri, 'modifica-evento') !== false){
    if(isset($_GET['data']) && isset($_GET['titolo'])){
        $dataEvento = $_GET['data'];
        $titoloEvento = $_GET['titolo'];
        $isModifiedEvent=true;
    }else{
        header("Location: ./nuovo-evento");
        exit;
    }
}

$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
if ($connessioneOK) {
    if($isModifiedEvent){
        $NewEventInfo = $connessione->getInfoEvent($titoloEvento, $dataEvento);
    }
    $messaggiForm = createNewEvent($connessione, $NewEventInfo, $isModifiedEvent);
}else{
	$messaggiForm['generic'] = "<p class='error-form'>Impossibile inserire nuovi dati, riprovare più tardi.</p>";
}

$paginaHTML = file_get_contents('./src/template/layout-admin.html');
$main = file_get_contents('./src/template/main/admin/nuovo-evento.html');

$breadcrumb = $isModifiedEvent?getBreadcrumb('modifica-evento', $pagine): getBreadcrumb('nuovo-evento', $pagine);;

$nav = buildAdminNav($adminMenu,'./nuovo-evento');
$keywords = $isModifiedEvent? "<meta name='keywords' content='modifica, evento, PetMatch, amministratore'>":"<meta name='keywords' content='nuovo, evento, PetMatch, amministratore'>";
$title = $isModifiedEvent? "<title>Modifica evento - PetMatch</title>" : "<title>Nuovo evento - PetMatch</title>";
$description = $isModifiedEvent? "<meta name='description' content='Modifica un evento presente nel sito di PetMatch.'>" : "<meta name='description' content='Organizza e pubblica un nuovo evento nel sito di PetMatch.'>";

$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);

$fotoInfo = "";
if (!empty($NewEventInfo['ImgPath']) && $NewEventInfo['ImgPath'] !== '../../assets/images/events/eventi-default.jpg') {
    $nomeFile = basename($NewEventInfo['ImgPath']);
    $fotoInfo = "<p class='success-form'>Immagine caricata: <strong>$nomeFile</strong></p>";
    $fotoInfo .= "<img src='{$NewEventInfo['ImgPath']}' alt='Anteprima immagine caricata'>";
    $inputHiddenFoto = "<input type='hidden' name='old-foto' value='{$NewEventInfo['ImgPath']}'/>";
}
$paginaHTML = str_replace('[foto-event-upload]', $fotoInfo, $paginaHTML);
$paginaHTML = str_replace('[input-hidden-foto]', $inputHiddenFoto??'', $paginaHTML);

$paginaHTML = str_replace('[title-event-value]', ($NewEventInfo['Titolo']?? ''), $paginaHTML);
$paginaHTML = str_replace('[day-event-value]', ($NewEventInfo['DataEvento']?? ''), $paginaHTML);
$paginaHTML = str_replace('[desc-event-place]', ($NewEventInfo['DescrEvento']?? ''), $paginaHTML);
$paginaHTML = str_replace('[address-event-value]', ($NewEventInfo['Via']?? ''), $paginaHTML);
$paginaHTML = str_replace('[city-event-value]', ($NewEventInfo['Citta']?? ''), $paginaHTML);

if($isModifiedEvent){
     $paginaHTML = str_replace('id="createMore-container"', 'id="ModifiedMode"', $paginaHTML);
     $paginaHTML = str_replace('[Action-modified]', 'Modifica', $paginaHTML);
     $paginaHTML = str_replace('[Action-modified-legend]', 'Modifica l\'organizzazione dell\'evento', $paginaHTML);
     $paginaHTML = str_replace('[urlCancel]', './dettagli-evento?titolo='.$_GET['titolo'].'&data='.$_GET['data'], $paginaHTML);
}else{
    $paginaHTML = str_replace('[Action-modified]', 'Aggiungi', $paginaHTML);
    $paginaHTML = str_replace('[Action-modified-legend]', 'Organizza il nuovo evento', $paginaHTML);
    $paginaHTML = str_replace('[urlCancel]', './visualizzazione-eventi', $paginaHTML);
    if(isset($_GET['createMore']) && $_GET['createMore'] == 1){
        $paginaHTML = str_replace('[checkCreateMore]', 'checked', $paginaHTML);
    }else{
        $paginaHTML = str_replace('[checkCreateMore]', ($NewEventInfo['createMore']? 'checked':''), $paginaHTML);
    }
}

$campi_errori = [
    'title'   => 'titolo',
    'day'     => 'data',
    'desc'    => 'descrizione',
    'address' => 'via',
    'city'    => 'citta',
    'foto'    => 'foto',
    'generic'    => 'generic',
    'existEvent'    => 'existEvent'
];

foreach ($campi_errori as $placeholder => $error) {
    $placeholder = '[error-' . $placeholder . ']'; 
    $valore_errore = $messaggiForm[$error] ?? '';
    
    if ($error === 'generic' || $error === 'existEvent') {
        $html_errore = $valore_errore ? "<p class='error-form'>$valore_errore</p>" : '';
    } else {
        // Sempre presente, ma nascosto se vuoto
        $display = $valore_errore ? 'block' : 'none';
        $html_errore = "<p class='error-form' style='display: $display;'>$valore_errore</p>";
    }
    
    $paginaHTML = str_replace($placeholder, $html_errore, $paginaHTML);
}

echo $paginaHTML;
?>