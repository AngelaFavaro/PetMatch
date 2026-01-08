<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;

if ((isset($_SESSION['loggato']) || $_SESSION['loggato'] === true)) {
    if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true){
        header("Location: ./home");
        exit;    
    }
}else{
    header("Location: ./home");
    exit;    
}

$NewAnimalInfo = [
    'tipologia' => '',
    'nome' => '',
    'razza' => '',
    'taglia' => '',
    'sesso' => '',
    'foto' => '',
    'dataNascita' => '',
    'pelo' => '',
    'colore' => '',
    'condMediche' => '',
    'carattere' => '',
    'famiglia' => ''
];

function editInfoAccount(DBAccess $conn, &$NewAnimalValues, $infoAnimale): array {
	
	$message = [
        'generic' => '',
        'tipologia' => '',
        'nome' => '',
        'razza' => '',
        'taglia' => '',
        'sesso' => '',
        'dataNascita' => '',
        'colore' => '',
        'pelo' => '',
        'condMediche' => '',
        'carattere' => '',
        'famiglia' => ''
    ];

    if (isset($_SESSION['form-add-animal'])) {
        
        if($_SESSION['form-add-animal'] === 'error'){
            $savedErrors = $_SESSION['form_errors_info'] ?? [];
            
            if (isset($savedErrors['generic'])) {
                $message['generic'] = "<p class='error-form'>" . $savedErrors['generic'] . "</p>";
            }

            if (isset($savedErrors['tipologia'])) {
                $message['tipologia'] = $savedErrors['tipologia'];
            }

            if (isset($savedErrors['nome'])) {
                $message['nome'] = $savedErrors['nome'] ;
            }
            
            if (isset($savedErrors['razza'])) {
                $message['razza'] = $savedErrors['razza'] ;
            }

            if (isset($savedErrors['taglia'])) {
                $message['taglia'] = $savedErrors['taglia'];
            }

            if (isset($savedErrors['sesso'])) {
                $message['sesso'] = $savedErrors['sesso'];
            }

            if (isset($savedErrors['dataNascita'])) {
                $message['dataNascita'] = $savedErrors['dataNascita'] ;
            }

            if (isset($savedErrors['colore'])) {
                $message['colore'] = $savedErrors['colore'];
            }

            if (isset($savedErrors['pelo'])) {
                $message['pelo'] = $savedErrors['pelo'];
            }

            if (isset($savedErrors['colore'])) {
                $message['condMediche'] = $savedErrors['condMediche'] ;
            }

            if (isset($savedErrors['carattere'])) {
                $message['carattere'] = $savedErrors['carattere'] ;
            }

            if (isset($savedErrors['condMediche'])) {
                $message['condMediche'] = $savedErrors['condMediche'] ;
            }

            if(isset($savedErrors['famiglia'] )){
                $message['famiglia'] = $savedErrorsù['famiglia']
            }

			
            $savedInputs = $_SESSION['form_inputs'] ?? [];
            $NewAnimalValues['tipologia'] = $savedInputs['tipologia'] ?? '';
            $NewAnimalValues['nome'] = $savedInputs['nome'] ?? '';
            $NewAnimalValues['razza'] = $savedInputs['razza'] ?? '';
            $NewAnimalValues['taglia'] = $savedInputs['taglia'] ?? '';
            $NewAnimalValues['sesso'] = $savedInputs['sesso'] ?? '';
            $NewAnimalValues['dataNascita'] = $savedInputs['dataNascita'] ?? '';
            $NewAnimalValues['pelo'] = $savedInputs['pelo'] ?? '';
            $NewAnimalValues['colore'] = $savedInputs['colore'] ?? '';
            $NewAnimalValues['carattere'] = $savedInputs['carattere'] ?? '';
            $NewAnimalValues['condMediche'] = $savedInputs['condMediche'] ?? '';
            $NewAnimalValues['famiglia'] = $savedInputs['famiglia'] ?? '';

            // Pulizia sessione
            unset($_SESSION['form_status_info']);
            unset($_SESSION['form_errors_info']);
            unset($_SESSION['form_inputs']);
        }
    }

    // GESTIONE INVIO FORM (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-animal'])) { 

        $tipologia    = trim($_POST['new-tipologia'] ?? '');
        $nome    = trim($_POST['new-nome'] ?? '');
        $razza = trim($_POST['new-razza'] ?? '');
        $taglia    = trim($_POST['new-taglia'] ?? '');
        $sesso    = trim($_POST['new-sesso'] ?? '');
        $dataNascita = trim($_POST['new-dataNascita'] ?? '');
        $pelo    = trim($_POST['new-pelo'] ?? '');
        $colore    = trim($_POST['new-colore'] ?? '');
        $carattere     = trim($_POST['new-carattere'] ?? '');
        $condMediche     = trim($_POST['new-condMediche'] ?? '');
        $famiglia = trim($_POST['new-famiglia'] ?? '');

        $nome = mb_convert_case($nome, MB_CASE_TITLE, "UTF-8");
        $razza = mb_convert_case($razza, MB_CASE_TITLE, "UTF-8");
        $colore = mb_convert_case($colore, MB_CASE_TITLE, "UTF-8");
        $carattere = mb_convert_case($carattere, MB_CASE_TITLE, "UTF-8");
        $condMediche = mb_convert_case($condMediche, MB_CASE_TITLE, "UTF-8");
        $famiglia = mb_convert_case($famiglia, MB_CASE_TITLE, "UTF-8");


        // Sanitizzazione per redisplay
        $NewAnimalValues['tipologia'] = htmlspecialchars($tipologia, ENT_QUOTES, 'UTF-8');
        $NewAnimalValues['nome'] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $NewAnimalValues['razza'] = htmlspecialchars($razza, ENT_QUOTES, 'UTF-8');
        $NewAnimalValues['taglia'] = htmlspecialchars($taglia, ENT_QUOTES, 'UTF-8');
        $NewAnimalValues['sesso'] = htmlspecialchars($sesso, ENT_QUOTES, 'UTF-8');
        $NewAnimalValues['foto'] = $_FILES['new-foto'] ?? null;
        $NewAnimalValues['dataNascita'] = htmlspecialchars($dataNascita, ENT_QUOTES, 'UTF-8');
        $NewAnimalValues['pelo'] = htmlspecialchars($pelo, ENT_QUOTES, 'UTF-8');
        $NewAnimalValues['colore'] = htmlspecialchars($colore, ENT_QUOTES, 'UTF-8');
        $NewAnimalValues['condMediche'] = htmlspecialchars($condMediche, ENT_QUOTES, 'UTF-8');
        $NewAnimalValues['carattere'] = htmlspecialchars($carattere, ENT_QUOTES, 'UTF-8');
        $NewAnimalValues['famiglia'] = htmlspecialchars($famiglia, ENT_QUOTES, 'UTF-8');

        $errors = [];

        $tipologieConsentite = ['Cane', 'Gatto'];
        $taglieConsentite = ['Piccola', 'Media', 'Grande'];
        $sessoConsentito = ['Mascio', 'Femmina'];
        $peloConsentito = ['Lungo', 'Corto', 'Misto'];

        $regexData = '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/'; 
        $regexTxt = '/^[a-zA-Z\x{00C0}-\x{017F}]+(?:[\'\s][a-zA-Z\x{00C0}-\x{017F}]+)*$/u';
        $regexTextArea = '/^[a-zA-Z0-9\x{00C0}-\x{017F}\s\.,\!\?\(\)\'\"\-]+$/u';

        /* VALIDAZIONE CAMPI */
        if (!preg_match(!in_array($tipologia, $tipologieConsentite))) {
            $errors['tipologia'] = "Tipologia non consentita.";
        }

        if (strlen($NewAnimalValues['nome']) < 2) {
            $errors['nome'] = "Il nome è troppo corto.";
        } elseif (!preg_match($regexTxt, $NewAnimalValues['nome'])) {
            $errors['nome'] = "Il nome contiene caratteri non validi.";
        }

        if (strlen($NewAnimalValues['razza']) < 2) {
            $errors['razza'] = "Razza troppo corta.";
        } elseif (!preg_match($regexTxt, $NewAnimalValues['razza'])) {
            $errors['razza'] = "Il campo razza contiene caratteri non validi.";
        }

        if (!in_array($taglia, $taglieConsentite)) {
            $errors['taglia'] = "Taglia non consentita.";
        }

        if (!in_array($sesso, $sessoConsentito)) {
            $errors['sesso'] = "Sesso non consentito.";
        }

        if (!preg_match($regexData, $NewAnimalValues['dataNascita'])) {
            $errors['dataNascita'] = "La data non è valida.";
        }

        if (!in_array($pelo, $peloConsentito)) {
            $errors['pelo'] = "Tipologia di pelo non consentito.";
        }

        if (strlen($NewAnimalValues['colore']) < 2) {
            $errors['colore'] = "Nome colore troppo corta.";
        } elseif (!preg_match($regexTxt, $NewAnimalValues['colore'])) {
            $errors['colore'] = "Il campo colore contiene caratteri non validi.";
        }

        if (!preg_match($regexTextArea, $NewAnimalValues['carattere'])) {
            $errors['carattere'] = "Il campo 'Descrizione Caratteriale' contiene caratteri non validi.";
        }

        if (!preg_match($regexTextArea, $NewAnimalValues['condMediche'])) {
            $errors['condMediche'] = "Il campo 'Condizioni Mediche' contiene caratteri non validi.";
        }

        if (!preg_match($regexTextArea, $NewAnimalValues['famiglia'])) {
            $errors['famiglia'] = "Il campo 'Famiglia Ideale' contiene caratteri non validi.";
        }


        /* AZIONI */
        if (empty($errors)) {

            //la foto del profilo avrà sempre qualcosa anche se non si selezionano immagini, bisogna controllarlo con empty
            if(isset($_FILES['new-foto']) && !empty($_FILES['new-foto']['name'])){
                $NewAnimalValues['foto'] = uploadImage($_FILES['new-foto'], 'animals'); 
            }else{
                $NewAnimalValues['foto'] = $infoAnimale['ImgPath']; 
            }

            $InsertResult = $conn->addAnimal($NewAnimalValues);
            
            if ($InsertResult) {
                header('./area-personale');
                exit;

            } else {
                $_SESSION['form_status_info'] = 'error';
                $_SESSION['form_errors_info'] = ['generic' => "Sistema momentaneamente non disponibile."];
                $_SESSION['form_inputs'] = ['tipologia' => $NewAnimalValues['tipologia'], 
                                            'nome' => $NewAnimalValues['nome'], 
                                            'razza' => $NewAnimalValues['razza'], 
                                            'taglia' => $NewAnimalValues['taglia'],
                                            'sesso' => $NewAnimalValues['sesso'], 
                                            'foto' => $NewAnimalValues['foto']
                                            'dataNascita' => $NewAnimalValues['dataNascita']
                                            'pelo' => $NewAnimalValues['pelo']
                                            'colore' => $NewAnimalValues['colore']
                                            'condMediche' => $NewAnimalValues['condMediche']
                                            'carattere' => $NewAnimalValues['carattere']
                                            'famiglia' => $NewAnimalValues['famiglia']
                                             ];
            }

            header("Location: ./nuovo-animale");
            exit;
            
        } else {
            $_SESSION['form_status_info'] = 'error';
            $_SESSION['form_errors_info'] = $errors; 
            $_SESSION['form_inputs'] = ['tipologia' => $NewAnimalValues['tipologia'], 
                                        'nome' => $NewAnimalValues['nome'], 
                                        'razza' => $NewAnimalValues['razza'], 
                                        'taglia' => $NewAnimalValues['taglia'],
                                        'sesso' => $NewAnimalValues['sesso'], 
                                        'foto' => $NewAnimalValues['foto']
                                        'dataNascita' => $NewAnimalValues['dataNascita']
                                        'pelo' => $NewAnimalValues['pelo']
                                        'colore' => $NewAnimalValues['colore']
                                        'condMediche' => $NewAnimalValues['condMediche']
                                        'carattere' => $NewAnimalValues['carattere']
                                        'famiglia' => $NewAnimalValues['famiglia'] ];
            header("Location: ./nuovo-animale");
            exit;
        }
    }

    return $message;
}

//connesisone al DB
$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
if ($connessioneOK) {
	if (isset($_SESSION['email'])) {
        // qui non sono sicura sul da farsi
        checkRole($connessione);
        $infoAnimale = $connessione->getAnimalInfo($_SESSION['email']);
        $messageInfoForm = addAnimal($connessione, $);
    }else{
        header("Location: ./nuovo-animale"); 
        exit;
    }
    $connessione->closeConnection();
    $messaggiGenerici = $messageInfoForm['generic'] . $messageManagementForm['generic'];
}else{
	header("Location: ./404");
    exit;    
}

$paginaHTML = file_get_contents('./src/template/layout-admin.html');
if ($paginaHTML === false) {
    $paginaHTML = "<p class='error-form'>Errore: template layout.html non trovato o non leggibile.</p>";
}


$title = '<title>Aggiungi animale - PetMatch </title>';
$description = '<meta name="description" content="Aggiunta di un animale il Pet Match">';
$keywords = "";

$nav = buildUserNav($userMenu, './profilo-utente');

$footer = file_get_contents('./src/template/partials/footer.html');

$breadcrumb = getBreadcrumb('aggiungi-animale', $pagine);

$main = file_get_contents('./src/template/main/admin/nuovo-animale.html');

$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);

//il filtro selezionato nella tendina precedentemente all'invio della form viene mantenuto
$paginaHTML = str_replace( 'value="' . $filtroCorrente . '"', 'value="' . $filtroCorrente . '" selected', $paginaHTML);

$paginaHTML = str_replace('[erroriTipologia]', $messageInfoForm['tipologia'], $paginaHTML);
$paginaHTML = str_replace('[erroriNome]', $messageInfoForm['nome'], $paginaHTML);
$paginaHTML = str_replace('[erroriRazza]', $messageInfoForm['razza'], $paginaHTML);
$paginaHTML = str_replace('[erroriTaglia]', $messageManagementForm['taglia'], $paginaHTML);
$paginaHTML = str_replace('[erroriSesso]', $messageManagementForm['sessp'], $paginaHTML);
$paginaHTML = str_replace('[erroriDataNascita]', $messageInfoForm['dataNascita'], $paginaHTML);
$paginaHTML = str_replace('[erroriPelo]', $messageInfoForm['pelo'], $paginaHTML);
$paginaHTML = str_replace('[erroriColore]', $messageInfoForm['colore'], $paginaHTML);
$paginaHTML = str_replace('[erroriCondMediche]', $messageInfoForm['condMediche'], $paginaHTML);
$paginaHTML = str_replace('[erroriCarattere]', $messageInfoForm['carattere'], $paginaHTML);
$paginaHTML = str_replace('[erroriFamiglia]', $messageInfoForm['famiglia'], $paginaHTML);
$paginaHTML = str_replace('[messaggiForm]', $messaggiGenerici, $paginaHTML);

// COME FARE?? Chiediamo ad Angels

$paginaHTML = str_replace('[imgPath]', $infoAnimale['ImgPath'] ? $infoAnimale['ImgPath'] : './assets/images/animals/default-pic.png', $paginaHTML);
$paginaHTML = str_replace('[nome]', $NewUserInfo['nome'] ? $NewAnimalInfo['nome'] : $infoAnimale['Nome'], $paginaHTML);
$paginaHTML = str_replace('[razza]', $NewUserInfo['razza'] ? $NewUserInfo['razza'] : $infoAnimale['Razza'], $paginaHTML);
$paginaHTML = str_replace('[indirizzo-utente]', $indirizzoCompleto, $paginaHTML);
$paginaHTML = str_replace('[email-utente]', $NewUserManagement['email'] ? $NewUserManagement['email'] : $_SESSION['email'], $paginaHTML);
$paginaHTML = str_replace('[via-utente]', $NewUserInfo['address'] ? $NewUserInfo['address'] : $infoAnimale['Via'], $paginaHTML);
$paginaHTML = str_replace('[citta-utente]', $NewUserInfo['city'] ? $NewUserInfo['city'] : $infoAnimale['Citta'], $paginaHTML);
$paginaHTML = str_replace('[cap-utente]', $NewUserInfo['CAP'] ? $NewUserInfo['CAP'] : $infoAnimale['CAP'], $paginaHTML);
$paginaHTML = str_replace('[telefono-utente]', $NewUserInfo['phoneNumber'] ? $NewUserInfo['phoneNumber'] : $infoAnimale['Telefono'], $paginaHTML);
$paginaHTML = str_replace('[telefono-utente-view]', $infoAnimale['Telefono'] ? '+39 ' . $infoAnimale['Telefono'] : "<em>Sconosciuto</em>", $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

echo $paginaHTML;

?>