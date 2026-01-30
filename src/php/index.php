<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;

$messaggiForm ='';
$nameValue ='';
$emailValue ='';

function createCardEvents(DBAccess $conn){
	$events = $conn->getLastEvents();

    $mesi=[
        1 => 'gennaio',  2 => 'febbraio', 3 => 'marzo', 4 => 'aprile',
        5 => 'maggio',   6 => 'giugno',   7 => 'luglio', 8 => 'agosto',
        9 => 'settembre',10 => 'ottobre', 11 => 'novembre',12 => 'dicembre'
    ];

    $lastEvents = "";
    for ($i = 0 ; $i<4; $i++){

        if (isset($events[$i])) {
        $event = $events[$i];
        
        $timestamp = strtotime($event["DataEvento"]);
        
        $giorno = date('d', $timestamp);
        $mese = date('n', $timestamp); 
        $anno = date('Y', $timestamp);
    
        $dataEstesa = $giorno . ' ' . $mesi[$mese] . ' ' . $anno;
        $dataMobile = date('d/m/Y', $timestamp);

        if (empty($event['ImgPath']) || !file_exists($event['ImgPath'])) {
            $event['ImgPath'] = 'assets/images/events/eventi-default.jpg';
        }
        
        $titolo = $event["Titolo"];
        $img = $event["ImgPath"];
        $citta = $event["Citta"];
        $link = "./visualizzazione-evento?titolo=".urlencode($titolo)."&data=".urlencode($dataEstesa);
        $ariaLabel = "evento " . $titolo. ': '.$dataEstesa.', '.$citta ;
        
    } else {
        
        // Qui metti quello che vuoi mostrare se mancano eventi
        $titolo = "Prossimamente";
        $img = "./assets/images/eventi-default.jpg"; // Immagine di default
        $dataEstesa = ""; 
        $dataMobile = "";
        $citta = "";
        $link = "./eventi";
        $ariaLabel = "Nessun evento programmato";
    }

    $lastEvents .= '
        <a href="' . $link . '" class="polaroid" aria-label="' . $ariaLabel . '">
            
            <article aria-hidden="true">
                <img src="' . $img . '" alt="copertina dell\'evento'.$titolo.'"/>
                
                <h4>' . $titolo . '</h4>
                <p class="vDesk">' . $dataEstesa . '</p>
                <p class="vMobile">' . $dataMobile . '</p>
                <p>' . $citta . '</p>
            </article>
            
        </a>';
    }

    return $lastEvents;
}


function sendReportForm(DBAccess $conn, &$nameValue, &$emailValue, &$animalValue){

    $message = '';

	if (isset($_SESSION['form_status']) && $_SESSION['form_status'] === 'ok') {
        
        $message = "<p class='success-form'>Segnalazione inviata con successo! Ti contatteremo presto.</p>";
        
        $nameValue = '';
        $emailValue = '';
        $animalValue = '';
        
        unset($_SESSION['form_status']); //rimuovo la variabile della sessione, se la pagina viene ricaricata non mostro di nuovo il messaggio
    }else if(isset($_SESSION['form_status']) && $_SESSION['form_status'] === 'error'){
		
        $showErrors = $_SESSION['form_errors'] ?? [];
        
        $message = "<div class='error-container'>";
        $message .= "<p><strong>Errori nel modulo di segnalazione:</strong></p>";
        $message .= "<ul class='error-form'>";
        
        foreach ($showErrors as $err) {
            $message .= "<li>$err</li>";
        }
        $message .= "</ul></div>";

        if (isset($_SESSION['form_inputs'])) {
            $nameValue = $_SESSION['form_inputs']['name'];
            $emailValue = $_SESSION['form_inputs']['email'];
            $animalValue = $_SESSION['form_inputs']['animal'];
        }

        unset($_SESSION['form_status']);
        unset($_SESSION['form_errors']);
        unset($_SESSION['form_inputs']);
	
	}else if(!isset($_SESSION['form_status'])){
		$nameValue = '';
        $emailValue = '';
        $animalValue = '';
		$message = '';
	}
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-report-form'])) { 

        $name = trim($_POST['name-surname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $animalValue = $_POST['type-animal'];

        $nameValue = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $emailValue = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

        $errors = [];

        $regexNome = "/^(?=.*[\p{L}]{2})[\p{L}\s']+$/u"; 
        $regexEmail = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,10})$/i";

		$words = array_filter(explode(' ', $name));
        
        if($animalValue === null){
            $errors[] = "Indica il tipo di animale.";
        }

        if (count($words) < 2) {
            $errors[] = "Inserisci sia il nome che il cognome.";
        }

		if(count($words)>=2 && (strlen($words[0])<2 || strlen($words[1])<2)){
            $errors[] = "Il nome e/o il cognome è troppo corto.";
		}else if(!preg_match($regexNome, $name)){
            $errors[] = "Il nome e/o il cognome contiene caratteri non validi (sono ammessi solo lettere e apostrofi).";
        }

        if (!preg_match($regexEmail, $email)) {
            $errors[] = "Email non valida.";
        }

        if (empty($errors)) {
            $send = $conn->insertReportForm($name, $email, $animalValue);
            
            if ($send) {
				$_SESSION['form_status'] = 'ok';
                header("Location: ./home");
                exit;
            } else {
				$_SESSION['form_status'] = 'error';
                $_SESSION['form_errors'] = ["Impossibile inviare la richiesta, riprova più tardi."];
                $_SESSION['form_inputs'] = ['name' => $nameValue, 'email' => $emailValue, 'animal' => $animalValue];
                $message = "<p class='error-form'>Impossibile inviare la richiesta, riprova più tardi.</p>";
            }
        }else{
			$_SESSION['form_status'] = 'error';
			$_SESSION['form_errors'] = $errors;
			$_SESSION['form_inputs'] = ['name' => $nameValue, 'email' => $emailValue, 'animal' => $animalValue];
			header("Location: ./home");
			exit;
		}
    }

    return $message;
}



$InfoEvents = "";

$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
if ($connessioneOK) {
    $InfoEvents = createCardEvents($connessione);
	$messaggiForm = sendReportForm($connessione, $nameValue, $emailValue, $animalValue);
	$connessione->closeConnection();
}else{
	$messaggiForm = "<p class='error-form'>Impossibile inviare la richiesta, riprova più tardi.</p>";
}

$paginaHTML = file_get_contents('./src/template/layout.html');
if ($paginaHTML === false) {
	$paginaHTML = "<p class='error-form'>Errore: template layout.html non trovato o non leggibile.</p>";
}

$title = '<title lang="en">Home - PetMatch </title>';
$description = '<meta name="description" lang="en" content="Home di PetMatch">';
$keywords = "<meta name='keywords' content='Adotta, eventi, sostenitori, trovare casa a un animale, come si adotta, adotta anche a distanza'>";

$nav = buildNav($userMenu, './home');

$footer = buildFooter($footerMenu,  './home');

$breadcrumb = getBreadcrumb('home', $pagine);

$main = file_get_contents('./src/template/main/index.html');

$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);
$paginaHTML = str_replace('[messaggiForm]', $messaggiForm, $paginaHTML);

$paginaHTML = str_replace('[UltimiEventi]', $InfoEvents, $paginaHTML);

// SOSTITUZIONE DEI VALORI INPUT
// htmlspecialchars() con ENT_QUOTES converte gli apici singoli e doppi.
// Se uno scrive: <script>alert('ciao')</script>
// Diventa: &lt;script&gt;alert(&#039;ciao&#039;)&lt;/script&gt; -> testo innocuo
$paginaHTML = str_replace('value="[nameValue]"', $nameValue!==''?'value="'.$nameValue.'"': '', $paginaHTML);
$paginaHTML = str_replace('value="[emailValue]"', $emailValue!==''?'value="'.$emailValue.'"': '', $paginaHTML);
$paginaHTML = str_replace( 'value="' . $animalValue . '"', 'value="' . $animalValue . '" checked', $paginaHTML);


$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

echo $paginaHTML;
?>