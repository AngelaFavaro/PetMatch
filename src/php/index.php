<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;

$messaggiForm ='';
$nameValue ='';
$emailValue ='';

function sendReportForm(DBAccess $conn, &$nameValue, &$emailValue){

    $message = '';

	if (isset($_SESSION['form_status']) && $_SESSION['form_status'] === 'ok') {
        
        $message = "<p class='success'>Segnalazione inviata con successo! Ti contatteremo presto.</p>";
        
        $nameValue = '';
        $emailValue = '';
        
        unset($_SESSION['form_status']); //rimuovo la variabile della sessione, se la pagina viene ricaricata non mostro di nuovo il messaggio
    }else if(isset($_SESSION['form_status']) && $_SESSION['form_status'] === 'error'){
		
        $showErrors = $_SESSION['form_errors'] ?? [];
        
        $message = "<div class='error-container'>";
        $message .= "<p><strong>Errori nel modulo di segnalazione:</strong></p>";
        $message .= "<ul class='error'>";
        
        foreach ($showErrors as $err) {
            $message .= "<li>$err</li>";
        }
        $message .= "</ul></div>";

        if (isset($_SESSION['form_inputs'])) {
            $nameValue = $_SESSION['form_inputs']['name'];
            $emailValue = $_SESSION['form_inputs']['email'];
        }

        unset($_SESSION['form_status']);
        unset($_SESSION['form_errors']);
        unset($_SESSION['form_inputs']);
	
	}else if(!isset($_SESSION['form_status'])){
		$nameValue = '';
        $emailValue = '';
		$message = '';
	}
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-report-form'])) { 

        $name = trim($_POST['name-surname'] ?? '');
        $email = trim($_POST['email'] ?? '');

        $nameValue = $name;
        $emailValue = $email;

        $errors = [];

        $regexNome = "/^[\p{L}\s']+$/u";
        $regexEmail = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,10})$/i";

		$words = array_filter(explode(' ', $name));
        
        if (count($words) < 2) {
            $errors[] = "Inserisci sia il nome che il cognome.";
        }
        if (!preg_match($regexNome, $name)) {
            $errors[] = "Il nome contiene caratteri non validi (sono ammessi solo lettere e apostrofi).";
        }
		if(count($words)>=2 && (strlen($words[0])<2 || strlen($words[1])<2)){
			$errors[] = "Il nome e/o il cognome è troppo corto.";
		}

        if (!preg_match($regexNome, $name)) {
            $errors[] = "Nome e cognome non validi.";
        }
        if (!preg_match($regexEmail, $email)) {
            $errors[] = "Email non valida.";
        }

        if (empty($errors)) {
            $send = $conn->insertReportForm($name, $email);
            
            if ($send) {
				$_SESSION['form_status'] = 'ok';
                header("Location: ./home");
                exit;
            } else {
				$_SESSION['form_status'] = 'error';
                $_SESSION['form_errors'] = ["Impossibile inviare la richiesta, riprova più tardi."];
                $_SESSION['form_inputs'] = ['name' => $nameValue, 'email' => $emailValue];
                $message = "<p class='error'>Impossibile inviare la richiesta, riprova più tardi.</p>";
            }
        }else{
			$_SESSION['form_status'] = 'error';
			$_SESSION['form_errors'] = $errors;
			$_SESSION['form_inputs'] = ['name' => $nameValue, 'email' => $emailValue];
			header("Location: ./home");
			exit;
		}
    }

    return $message;
}

$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
if ($connessioneOK) {
	
	$messaggiForm = sendReportForm($connessione, $nameValue, $emailValue);
	$connessione->closeConnection();
}else{
	$messaggiForm = "<p class='error'>Impossibile inviare la richiesta, riprova più tardi.</p>";
}

$paginaHTML = file_get_contents('./src/template/layout.html');
if ($paginaHTML === false) {
	$paginaHTML = "<p>Errore: template layout.html non trovato o non leggibile.</p>";
}

$title = '<title>Home - PetMatch </title>';
$description = '<meta name="description" content="Home di PetMatch">';
$keywords = "";

$nav = buildUserNav($userMenu, './home');

$footer = file_get_contents('./src/template/partials/footer.html');

$breadcrumb = getBreadcrumb('home', $pagine);

$main = file_get_contents('./src/template/main/index.html');

$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);
$paginaHTML = str_replace('[messaggiForm]', $messaggiForm, $paginaHTML);

// SOSTITUZIONE DEI VALORI INPUT
// htmlspecialchars() con ENT_QUOTES converte gli apici singoli e doppi.
// Se uno scrive: <script>alert('ciao')</script>
// Diventa: &lt;script&gt;alert(&#039;ciao&#039;)&lt;/script&gt; -> testo innocuo
$paginaHTML = str_replace('[nameValue]', htmlspecialchars($nameValue, ENT_QUOTES, 'UTF-8'), $paginaHTML);
$paginaHTML = str_replace('[emailValue]', htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'), $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

echo $paginaHTML;
?>