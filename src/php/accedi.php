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

function checkCredential(DBAccess $conn, &$emailValue) {
    $message = '';

    if (isset($_SESSION['form_status'])) {
        
        if($_SESSION['form_status'] === 'error'){
            $savedErrors = $_SESSION['form_errors'] ?? [];
            
            if (isset($savedErrors['generic'])) {
                $message = "<p class='error-form'>" . $savedErrors['generic'] . "</p>";
            }
            
            $savedInputs = $_SESSION['form_inputs'] ?? [];
            $emailValue   = $savedInputs['email'] ?? '';

            // Pulizia sessione
            unset($_SESSION['form_status']);
            unset($_SESSION['form_errors']);
            unset($_SESSION['form_inputs']);
        }
    }

    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) { 

        $email   = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        $email = mb_strtolower($email, "UTF-8");

        $emailValue   = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

        $errors = '';

        //controllo le credenziali
        $UserCredentialValid = $conn->getCredentials($email);

        if (empty($UserCredentialValid) || !password_verify($password, $UserCredentialValid['Password']) || $UserCredentialValid['Email'] !== $email) {
            $errors = "Email o password non sono corretti.";
        }

        // Azioni
        if (empty($errors)) {
            $_SESSION['loggato'] = true;
            $_SESSION['email'] = $email;

            $role = $conn->getRole($email);

            if ($role === 'Admin') {
                $_SESSION['admin'] = true;
                header("Location: ./area-riservata");
            } else if($role === 'User'){
                $_SESSION['admin'] = false;
                header("Location: ./profilo-utente"); 
            }
            exit;
    
        } else {
            $_SESSION['form_status'] = 'error';
            $_SESSION['form_errors'] = ['generic' => $errors];
            $_SESSION['form_inputs'] = ['email' => $email];
            header("Location: ./accedi");
            exit;
        }
    }   
    return $message;
}

$emailValue = '';
$messageForm = '';

//connesisone al DB
$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
if ($connessioneOK) {
	
	$messageForm = checkCredential($connessione, $emailValue);
	$connessione->closeConnection();
}else{
	$messageForm['generic'] = "<p class='error'>Impossibile completare l'operazione, riprova più tardi.</p>";
}

$paginaHTML = file_get_contents('./src/template/layout.html');
if ($paginaHTML === false) {
	$paginaHTML = "<p>Errore: template layout.html non trovato o non leggibile.</p>";
}

$title = '<title>Accedi - PetMatch </title>';
$description = '<meta name="description" content="Accedi a PetMatch">';
$keywords = "";

$nav = buildUserNav($userMenu, './accedi');
$footer = file_get_contents('./src/template/partials/footer.html');

$breadcrumb = getBreadcrumb('accedi', $pagine);

$main = file_get_contents('./src/template/main/accedi.html');

$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);

$paginaHTML = str_replace('[emailValue]', $emailValue, $paginaHTML);
$paginaHTML = str_replace('[erroriLogin]', $messageForm, $paginaHTML);

$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

echo $paginaHTML;
?>