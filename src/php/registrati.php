<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;

//se sono loggato rimando alla pagina di profilo
if (isset($_SESSION['loggato']) && $_SESSION['loggato'] === true) {
    if(isset($_SESSION['admin']) && $_SESSION['admin'] === true){
        header("Location: ./area-riservata");
    }else{
        header("Location: ./profilo-utente");
    }
    exit; 
}

$paginaHTML = file_get_contents('./src/template/layout.html');
if ($paginaHTML === false) {
	$paginaHTML = "<p>Errore: template layout.html non trovato o non leggibile.</p>";
}

$nameValue = ''; 
$surnameValue = ''; 
$emailValue = '';

$messageForm = [
    'generic' => '',
    'name' => '',
    'surname' => '',
    'email' => '',
    'password' => ''
];


function createNewAccount(DBAccess $conn, &$nameValue, &$surnameValue, &$emailValue ){
	
	$message = [
        'generic' => '',
        'name' => '',
        'surname' => '',
        'email' => '',
        'password' => ''
    ];

    if (isset($_SESSION['form_status'])) {
        
        if($_SESSION['form_status'] === 'error'){
            $savedErrors = $_SESSION['form_errors'] ?? [];
            
            if (isset($savedErrors['generic'])) {
                $message['generic'] = "<p class='error-form'>" . $savedErrors['generic'] . "</p>";
            }

            if (isset($savedErrors['name'])) {
                $message['name'] = $savedErrors['name'] ;
            }
            
            if (isset($savedErrors['surname'])) {
                $message['surname'] = $savedErrors['surname'] ;
            }

            if (isset($savedErrors['email'])) {
                $message['email'] = $savedErrors['email'] ;
            }

            if (isset($savedErrors['password'])) {
                $message['password'] = $savedErrors['password'] ;
            }
			
            $savedInputs = $_SESSION['form_inputs'] ?? [];
            $nameValue    = $savedInputs['name'] ?? '';
            $surnameValue = $savedInputs['surname'] ?? '';
            $emailValue   = $savedInputs['email'] ?? '';

            // Pulizia sessione
            unset($_SESSION['form_status']);
            unset($_SESSION['form_errors']);
            unset($_SESSION['form_inputs']);
        }
    }

    // GESTIONE INVIO FORM (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) { 

        $name    = trim($_POST['name'] ?? '');
        $surname = trim($_POST['surname'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirmPassword = trim($_POST['confirmPassword'] ?? '');

        $name = mb_convert_case($name, MB_CASE_TITLE, "UTF-8");
        $surname = mb_convert_case($surname, MB_CASE_TITLE, "UTF-8");
        $email = mb_strtolower($email, "UTF-8");

        // Sanitizzazione per redisplay
        $nameValue    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $surnameValue = htmlspecialchars($surname, ENT_QUOTES, 'UTF-8');
        $emailValue   = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

        $errors = [];

        $regexNome = "/^(?=.*[\p{L}]{2})[\p{L}\s']+$/u"; 
        $regexEmail = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,10})$/i";
        $regexPassword = "/^(?=.*\d)(?=.*[A-Z])(?=.*[a-z])(?=.*[!@+?\/,.\-$_=])[a-zA-Z0-9!@+?\/,.\-$_=]{8,32}$/";

        /* VALIDAZIONE CAMPI */
        if (strlen($name) < 2) {
            $errors['name'] = "Il nome è troppo corto.";
        } elseif (!preg_match($regexNome, $name)) {
            $errors['name'] = "Il nome contiene caratteri non validi.";
        }

        if (strlen($surname) < 2) {
            $errors['surname'] = "Il cognome è troppo corto.";
        } elseif (!preg_match($regexNome, $surname)) {
            $errors['surname'] = "Il cognome contiene caratteri non validi.";
        }

        if (!preg_match($regexEmail, $email)) {
            $errors['email'] = "Formato email non valido.";
        } else {
            if($conn->checkEmailExists($email)){ 
                $errors['email'] = "L'email è già in uso.";
            }
        }

        if (strlen($password) < 8 || strlen($confirmPassword) < 8   ) {
            $errors['password'] = "La password deve essere di almeno 8 caratteri.";
        } else if(strlen($password) > 32 || strlen($confirmPassword) > 32){
            $errors['password'] = "La password deve essere al massimo di 32 caratteri.";
        }else if (!preg_match($regexPassword, $password) || $password !== $confirmPassword) {
            $errors['password'] = "La password non rispetta i criteri richiesti o non coincide.";
        }

        /* AZIONI */
        if (empty($errors)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertResult = $conn->insertNewUser($email, $name, $surname, $hashedPassword);
            
            if ($insertResult) {
				$_SESSION['loggato'] = true;
				$_SESSION['email'] = $email;
				$_SESSION['admin'] = false;

                header("Location: ./profilo-utente"); 
                exit;
            } else {
                $_SESSION['form_status'] = 'error';
                $_SESSION['form_errors'] = ['generic' => "Sistema momentaneamente non disponibile."];
                $_SESSION['form_inputs'] = ['name' => $nameValue, 'surname' => $surnameValue, 'email' => $emailValue];
                header("Location: ./registrati");
                exit;
            }
        } else {
            $_SESSION['form_status'] = 'error';
            $_SESSION['form_errors'] = $errors; 
            $_SESSION['form_inputs'] = ['name' => $nameValue, 'surname' => $surnameValue, 'email' => $emailValue];
            header("Location: ./registrati");
            exit;
        }
    }

    return $message;
}




//connesisone al DB
$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
if ($connessioneOK) {
	
	$messageForm = createNewAccount($connessione, $nameValue, $surnameValue, $emailValue );
	$connessione->closeConnection();
}else{
	$messageForm['generic'] = "<p class='error'>Impossibile completare l'operazione, riprova più tardi.</p>";
}



$title = '<title>Registrati - PetMatch </title>';
$description = '<meta name="description" content="Registrati su PetMatch">';
$keywords = "";

$nav = buildUserNav($userMenu, './registrati', $_SESSION['loggato'] ?? false);

$footer = file_get_contents('./src/template/partials/footer.html');

$breadcrumb = getBreadcrumb('registrati', $pagine);

$main = file_get_contents('./src/template/main/registrati.html');

$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);

$paginaHTML = str_replace('[nameValue]', $nameValue, $paginaHTML);
$paginaHTML = str_replace('[surnameValue]', $surnameValue, $paginaHTML);
$paginaHTML = str_replace('[emailValue]', $emailValue, $paginaHTML);

$paginaHTML = str_replace('[erroriNome]', $messageForm['name'], $paginaHTML);
$paginaHTML = str_replace('[erroriCognome]', $messageForm['surname'], $paginaHTML);
$paginaHTML = str_replace('[erroriEmail]', $messageForm['email'], $paginaHTML);
$paginaHTML = str_replace('[erroriPassword]', $messageForm['password'], $paginaHTML);
$paginaHTML = str_replace('[messaggiForm]', $messageForm['generic'], $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

echo $paginaHTML;
?>