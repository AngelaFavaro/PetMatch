<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;

$NewEventInfo= [
    'titolo' => '', 
	'data' => '', 
	'descrizione' => '', 
	'foto' => '',
	'via' => '',
	'citta' => ''
];

function createNewEvent(DBAccess $conn, &$newEventValues): array {
    $message = [
        'generic' => '', 'titolo' => '', 'data' => '', 'descrizione' => '',
        'foto' => '', 'via' => '','citta' => ''
    ];

    if (isset($_SESSION['form_status_info']) && $_SESSION['form_status_info'] === 'error') {
        $savedErrors = $_SESSION['form_errors_info'] ?? [];
        foreach ($savedErrors as $key => $val) {
            $message[$key] = ($key === 'generic') ? $val : "<p class='error-form'>$val</p>";
        }
        $savedInputs = $_SESSION['form_inputs'] ?? [];

		$newEventValues['titolo']      = $savedInputs['title-event'] ?? '';
        $newEventValues['data']        = $savedInputs['day-event'] ?? '';
        $newEventValues['descrizione'] = $savedInputs['desc-event'] ?? '';
        $newEventValues['via']         = $savedInputs['address-event'] ?? '';
        $newEventValues['citta']       = $savedInputs['city-event'] ?? '';
        $newEventValues['foto']        = $savedInputs['foto'] ?? '';
		
        unset($_SESSION['form_status_info'], $_SESSION['form_errors_info'], $_SESSION['form_inputs']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-event'])) { 
        $errors = [];
        
        // Recupero campi testo
        $titoloValue = $_POST['title-event'] ?? '';
        $dayValue = trim($_POST['day-event'] ?? '');
        $descValue = trim($_POST['desc-event'] ?? '');
        $addressValue = trim($_POST['address-event'] ?? '');
        $cityValue = trim($_POST['city-event'] ?? '');

		$titoloValue = htmlspecialchars($titoloValue, ENT_QUOTES, 'UTF-8');
		$dayValue = htmlspecialchars($dayValue, ENT_QUOTES, 'UTF-8');
		$descValue = htmlspecialchars($descValue, ENT_QUOTES, 'UTF-8');
		$addressValue = htmlspecialchars($addressValue, ENT_QUOTES, 'UTF-8');
		$cityValue = htmlspecialchars($cityValue, ENT_QUOTES, 'UTF-8');

        $regexData = '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/';
		$regex_indirizzo = '/^[a-zA-Z\.\']{3,}\s+.+\s+(?:n\.?\s?)?\d+[a-zA-Z]?$/';
        $regex_citta = '/^[a-zA-Z\s\.\']{2,}$/';

        // Validazione
        if (empty($titoloValue)){
            $errors['titolo'] = "Inserisci un titolo.";
        }
        else if (strlen($titoloValue) < 2 ){
            $errors['titolo'] = "Il titolo è troppo corto.";
        } 

        if (empty($descValue)){
            $errors['descrizione'] = "Inserisci una descrizione.";
        }else if (strlen($descValue) < 5 ){
            $errors['descrizione'] = "La descrizione è troppo corta.";
        }

        if(empty($addressValue)){
            $errors['via'] = "Inserisci la via.";
        } 
        else if (strlen($addressValue) < 3 ){
            $errors['via'] = "La via è troppo corta.";
        }else if(!preg_match($regex_indirizzo, $addressValue)){
            $errors['via'] = "La via non è valida.";
        } 


        if (empty($cityValue)) {
            $errors['citta'] = "Inserisci la città.";
        }else if (strlen($cityValue) < 2 ){
            $errors['citta'] = "La città è troppo corto.";
        }else if(!preg_match($regex_citta, $cityValue)){
            $errors['citta'] = "La città non è valida.";
        } 

        if (empty($dayValue)){
            $errors['data'] = "Inserisci il giorno.";
        } else if ($dayValue < date('Y-m-d')) {
            $errors['data'] = "L'evento non può essere nel passato.";
        }


        if($conn -> checkEventExists($titoloValue, $dayValue)){
            $errors['existEvent'] ='Un evento con il titolo '.$titoloValue.' e data '.date("d/m/Y",strtotime($dayValue)).' esiste già.';
        }
        
        // Gestione Foto
        if(isset($_FILES['foto']) && $_FILES['foto']['name'] != "") {
            $path = uploadImage($_FILES['foto'], 'events');
            if ($path !== null) {
                $fotoPath = $path;
            }
        }else if(isset($_POST['old-foto']) && !empty($_POST['old-foto'])) {
            $fotoPath = $_POST['old-foto'];
        }else{
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
            ];

			if ($conn->insertNewEvent($infoDB)) {
				unset($_SESSION['form_inputs'], $_SESSION['form_errors_info']);
				header("Location: ./eventi");
				exit;
			} else {
				$errors['generic'] = "L'inserimenti dell'evento non è andato a buon fine, riprovare più tardi.";
			}
		}

        $_SESSION['form_status_info'] = 'error';
        $_SESSION['form_errors_info'] = $errors;
        $inputsToSave['title-event'] = $titoloValue; 
        $inputsToSave['day-event'] = $dayValue; 
        $inputsToSave['desc-event'] = $descValue; 
        $inputsToSave['address-event'] = $addressValue; 
        $inputsToSave['city-event'] = $cityValue; 
        $inputsToSave['foto'] = $fotoPath; 
        $_SESSION['form_inputs'] = $inputsToSave; 

        header("Location: ./nuovo-evento");
        exit;
    }
    return $message;
}

$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
if ($connessioneOK) {
    $messaggiForm = createNewEvent($connessione, $NewEventInfo);
}else{
	$messaggiForm['generic'] = "<p class='error-form'>Impossibile inserire nuovi dati, riprovare più tardi.</p>";
}

$paginaHTML = file_get_contents('./src/template/layout-admin.html');
$main = file_get_contents('./src/template/main/admin/nuovo-evento.html');
$breadcrumb = getBreadcrumb('nuovo-evento', $pagine);
$nav = buildAdminNav($adminMenu,'./nuovo-evento');
$keywords = "<meta name='keywords' content='nuovo evento, PetMatch'>";
$title = "<title>Nuovo evento - PetMatch</title>";
$description = "<meta name='description' content='Organizza e pubblica un nuovo evento nel sito di PetMatch.'>";

$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);

$fotoInfo = "";
if (!empty($NewEventInfo['foto']) && $NewEventInfo['foto'] !== '../../assets/images/events/eventi-default.jpg') {
    $nomeFile = basename($NewEventInfo['foto']);
    $fotoInfo = "<p class='success-form'>Immagine caricata: <strong>$nomeFile</strong></p>";
    $fotoInfo .= "<img src='{$NewEventInfo['foto']}' alt='Anteprima immagine caricata'>";
    $inputHiddenFoto = "<input type='hidden' name='old-foto' value='{$NewEventInfo['foto']}'>";
}
$paginaHTML = str_replace('[foto-event-upload]', $fotoInfo, $paginaHTML);
$paginaHTML = str_replace('[input-hidden-foto]', $inputHiddenFoto, $paginaHTML);

$paginaHTML = str_replace('[title-event-value]', ($NewEventInfo['titolo']?? ''), $paginaHTML);
$paginaHTML = str_replace('[day-event-value]', ($NewEventInfo['data']?? ''), $paginaHTML);
$paginaHTML = str_replace('[desc-event-place]', ($NewEventInfo['descrizione']?? ''), $paginaHTML);
$paginaHTML = str_replace('[address-event-value]', ($NewEventInfo['via']?? ''), $paginaHTML);
$paginaHTML = str_replace('[city-event-value]', ($NewEventInfo['citta']?? ''), $paginaHTML);

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
    $paginaHTML = str_replace($placeholder, $valore_errore, $paginaHTML);
}

echo $paginaHTML;
?>