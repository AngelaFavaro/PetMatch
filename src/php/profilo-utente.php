<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;
session_start();

//se non sono loggato rimando alla pagina di login
if (!isset($_SESSION['loggato']) || $_SESSION['loggato'] !== true) {
    header("Location: ./accedi");
    exit;    
}

$filtroCorrente = '%';
if (isset($_GET['state'])) {
    $filtroCorrente = htmlspecialchars($_GET['state']);
}

function createMovementList(DBAccess $conn, $filtro = '%'): string {
    $listaMovimenti = '';

    $richieste = $conn->getUserRequests($_SESSION['email'],$filtro);

    if (count($richieste) === 0) {
        return "<p id=\"query-vuota\">Non sono presenti richieste di adozione.</p>";
    }else{
        $listaMovimenti = '<ul id="lista-movimenti" aria-labelledby="ultimi-movimenti">';
        foreach ($richieste as $richiesta) {

            $statoRichiesta = '';

            $nomeAnimale = htmlspecialchars($richiesta['NomeAnimale']);

            switch($richiesta['Stato']){
                case 'In valutazione':
                    $statoRichiesta = 'La tua richesta di adozione per <em>'.$nomeAnimale.'</em> è in <strong>valutazione.</strong>';
                    break;
                case 'Da trasportare':
                    $statoRichiesta = '<em>'.$nomeAnimale.'</em> partità il giorno <em>'.$richiesta['DataPartenza'].'</em> e arriverà il giorno<em>'.$richiesta['DataArrivo'].'</em>!';
                    break;
                case 'Conclusa':
                    $statoRichiesta = 'Complimenti! Hai adottato con successo <em>'.$nomeAnimale.'</em>.';
                    break;
                case 'Respinta':
                    $statoRichiesta = 'Siamo spiacenti di informarti che la tua richiesta di adozione per <em>'.$nomeAnimale.'</em> è stata <strong>respinta.</strong>';
                    break;
                case 'Annullata':   
                    $statoRichiesta = 'Hai annullato la tua richiesta di adozione per <em>'.$nomeAnimale.'</em>.';
                    break;
                case 'Nuova':
                    $statoRichiesta = 'La tua richiesta di adozione per <em>'.$nomeAnimale.'</em> è stata inviata con successo e sarà valutata a breve.';
                    break;
            }

            // TODO: il link "Vedi animale" deve portare alla pagina di dettaglio dell'animale, da fare quando la pagina sarà pronta
            $listaMovimenti .= '<li>
                <article>
                    <p>'.$statoRichiesta.'</p>
                    <a href="">Vedi animale</a>
                </article>
            </li>';
        }

        $listaMovimenti .= "</ul>";
        return $listaMovimenti;
    }
}

$NewUserValues = [
    'email' => '',
    'name' => '',
    'surname' => '',
    'OldPassword' => '',
    'Newpassword' => '',
    'phoneNumber' => '',
    'address' => '',
    'city' => '',
    'CAP' => '',
    'profilePic' => ''
];

function editAccount(DBAccess $conn, &$NewUserValues, &$infoUtente): array {
	
	$message = [
        'generic' => '',
        'name' => '',
        'surname' => '',
        'email' => '',
        'password' => '',
        'address' => '',
        'city' => '',
        'CAP' => '',
        'phoneNumber' => '',
        'indirizzo-totale' => ''
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

            if (isset($savedErrors['address'])) {
                $message['address'] = $savedErrors['address'] ;
            }

            if (isset($savedErrors['city'])) {
                $message['city'] = $savedErrors['city'] ;
            }

            if (isset($savedErrors['CAP'])) {
                $message['CAP'] = $savedErrors['CAP'] ;
            }

            if(!isset($savedErrors['address']) && !isset($savedErrors['city']) && !isset($savedErrors['CAP'])){
                if(isset($savedErrors['indirizzo-totale'])){
                    $message['indirizzo-totale'] = $savedErrors['indirizzo-totale'];
                }
            }

            if (isset($savedErrors['phoneNumber'])) {
                $message['phoneNumber'] = $savedErrors['phoneNumber'] ;
            }

            if (isset($savedErrors['password'])) {
                $message['password'] = $savedErrors['password'] ;
            }
			
            $savedInputs = $_SESSION['form_inputs'] ?? [];
            $NewUserValues['name'] = $savedInputs['name'] ?? '';
            $NewUserValues['surname'] = $savedInputs['surname'] ?? '';
            $NewUserValues['email'] = $savedInputs['email'] ?? '';
            $NewUserValues['address'] = $savedInputs['address'] ?? '';
            $NewUserValues['city'] = $savedInputs['city'] ?? '';
            $NewUserValues['CAP'] = $savedInputs['CAP'] ?? '';
            $NewUserValues['phoneNumber'] = $savedInputs['phoneNumber'] ?? '';

            // Pulizia sessione
            unset($_SESSION['form_status']);
            unset($_SESSION['form_errors']);
            unset($_SESSION['form_inputs']);
        }
    }

    // GESTIONE INVIO FORM (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit-profile'])) { 

        $name    = trim($_POST['new-name'] ?? '');
        $surname = trim($_POST['new-surname'] ?? '');
        $email   = trim($_POST['new-email'] ?? '');
        $address = trim($_POST['new-address'] ?? '');
        $city    = trim($_POST['new-city'] ?? '');
        $CAP     = trim($_POST['new-cap'] ?? '');
        $phoneNumber = trim($_POST['new-number'] ?? '');
        $oldPassword = trim($_POST['old-pw'] ?? '');
        $newPassword = trim($_POST['new-pw'] ?? '');
        $confirmPassword = trim($_POST['new-pw-Confirmed'] ?? '');

        $name = mb_convert_case($name, MB_CASE_TITLE, "UTF-8");
        $surname = mb_convert_case($surname, MB_CASE_TITLE, "UTF-8");
        $email = mb_strtolower($email, "UTF-8");
        $address = mb_convert_case($address, MB_CASE_TITLE, "UTF-8");
        $city = mb_convert_case($city, MB_CASE_TITLE, "UTF-8");
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber); // rimuove tutti i caratteri non numerici

        // Sanitizzazione per redisplay
        $NewUserValues['name'] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $NewUserValues['surname'] = htmlspecialchars($surname, ENT_QUOTES, 'UTF-8');
        $NewUserValues['email'] = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $NewUserValues['address'] = htmlspecialchars($address, ENT_QUOTES, 'UTF-8');
        $NewUserValues['city'] = htmlspecialchars($city, ENT_QUOTES, 'UTF-8');
        $NewUserValues['CAP'] = htmlspecialchars($CAP, ENT_QUOTES, 'UTF-8');
        $NewUserValues['phoneNumber'] = htmlspecialchars($phoneNumber, ENT_QUOTES, 'UTF-8');
        $NewUserValues['profilePic'] = $_FILES['new-pic'] ?? null;

        $errors = [];

        $regexNome = "/^(?=.*[\p{L}]{2})[\p{L}\s']+$/u"; 
        $regexEmail = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,10})$/i";
        $regexPassword = "/^(?=.*\d)(?=.*[A-Z])(?=.*[a-z])(?=.*[!@+?\/,.\-$_=])[a-zA-Z0-9!@+?\/,.\-$_=]{8,32}$/";
        $regexPhone = "/^[0-9]{10}$/";
        $regex_indirizzo = '/^[a-zA-Z\.\']{3,}\s+.+\s+(?:n\.?\s?)?\d+[a-zA-Z]?$/';
        $regex_citta = '/^[a-zA-Z\s\.\']{2,}$/';
        $regex_cap = '/^\d{5}$/';

        /* VALIDAZIONE CAMPI */
        if (strlen($NewUserValues['name']) < 2) {
            $errors['name'] = "Il nome è troppo corto.";
        } elseif (!preg_match($regexNome, $NewUserValues['name'])) {
            $errors['name'] = "Il nome contiene caratteri non validi.";
        }

        if (strlen($NewUserValues['surname']) < 2) {
            $errors['surname'] = "Il cognome è troppo corto.";
        } elseif (!preg_match($regexNome, $NewUserValues['surname'])) {
            $errors['surname'] = "Il cognome contiene caratteri non validi.";
        }

        if (!preg_match($regexEmail, $NewUserValues['email'])) {
            $errors['email'] = "Formato email non valido.";
        } else {
            if($conn->checkEmailExists($NewUserValues['email']) && $NewUserValues['email'] !== $_SESSION['email']){ 
                $errors['email'] = "L'email è già in uso.";
            }
        }

        if(strlen($NewUserValues['address']) === 0){
            $NewUserValues['address'] = null;
        }else if (strlen($NewUserValues['address']) < 3) {
            $errors['address'] = "L'indirizzo è troppo corto.";
        } elseif (!preg_match($regex_indirizzo, $NewUserValues['address'])) {
            $errors['address'] = "L'indirizzo non è valido.";
        }

        if(strlen($NewUserValues['city']) === 0){
            $NewUserValues['city'] = null;
        }else if (strlen($NewUserValues['city']) < 2) {
            $errors['city'] = "La città è troppo corta.";
        } elseif (!preg_match($regex_citta, $NewUserValues['city'])) {
            $errors['city'] = "La città contiene caratteri non validi.";
        }

        if(strlen($NewUserValues['CAP']) === 0){
            $NewUserValues['CAP'] = null;
        }else if (!preg_match($regex_cap, $NewUserValues['CAP'])) {
            $errors['CAP'] = "Il CAP non è valido.";
        }

        $hasAddress = ($NewUserValues['address'] !== null);
        $hasCity    = ($NewUserValues['city'] !== null);
        $hasCAP     = ($NewUserValues['CAP'] !== null);

        if (!($hasAddress === $hasCity && $hasCity === $hasCAP)) {
            $errors['indirizzo-totale'] = "L'indirizzo è incompleto: devi compilare Via, Città e CAP insieme.";
        }

        if(strlen($NewUserValues['phoneNumber']) === 0){
            $NewUserValues['phoneNumber'] = null;
        }else if (strlen($NewUserValues['phoneNumber']) !== 10 || !preg_match($regexPhone, $NewUserValues['phoneNumber'])) {
            $errors['phoneNumber'] = "Il numero di telefono non è valido.";
        }

        if(strlen($oldPassword) === 0 && strlen($newPassword) === 0 && strlen($confirmPassword) === 0){
            // nessun cambiamento di password
        } else if (!password_verify($oldPassword, $infoUtente['UtentePW'])) {
            $errors['password'] = "La vecchia password non è corretta.";
        } else if (strlen($newPassword) < 8 || strlen($confirmPassword) < 8) {
            $errors['password'] = "La password deve essere di almeno 8 caratteri.";
        } else if(strlen($newPassword) > 32 || strlen($confirmPassword) > 32){
            $errors['password'] = "La password deve essere al massimo di 32 caratteri.";
        }else if (!preg_match($regexPassword, $newPassword) || $newPassword !== $confirmPassword) {
            $errors['password'] = "La password non rispetta i criteri richiesti o non coincide.";
        }else if (password_verify($newPassword, $infoUtente['UtentePW'])) {
            $errors['password'] = "La nuova password deve essere diversa dalla vecchia.";
        }

        /* AZIONI */
        if (empty($errors)) {
            if(strlen($newPassword) !== 0){
                $NewUserValues['Newpassword'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }else{
                $NewUserValues['Newpassword'] = $infoUtente['UtentePW'];
            }

            //la foto del profilo avrà sempre qualcosa anche se non si selezionano immagini, bisogna controllarlo con empty
            if(isset($_FILES['new-pic']) && !empty($_FILES['new-pic']['name'])){
                $NewUserValues['profilePic'] = uploadImage($_FILES['new-pic'], 'users');
            }else{
                $NewUserValues['profilePic'] = $infoUtente['ImgPath']; 
            }

            $EditResult = $conn->updateUserInfo($_SESSION['email'], $NewUserValues);
            
            if ($EditResult) {
				$_SESSION['loggato'] = true;
				$_SESSION['email'] = $email;

            } else {
                $_SESSION['form_status'] = 'error';
                $_SESSION['form_errors'] = ['generic' => "Sistema momentaneamente non disponibile."];
                $_SESSION['form_inputs'] = ['name' => $NewUserValues['name'], 'surname' => $NewUserValues['surname'], 
                'email' => $NewUserValues['email'], 'address' => $NewUserValues['address'], 'city' => $NewUserValues['city'],
                'CAP' => $NewUserValues['CAP'], 'phoneNumber' => $NewUserValues['phoneNumber'] ];
            }

            header("Location: ./profilo-utente");
            exit;
            
        } else {
            $_SESSION['form_status'] = 'error';
            $_SESSION['form_errors'] = $errors; 
            $_SESSION['form_inputs'] = ['name' => $NewUserValues['name'], 'surname' => $NewUserValues['surname'], 
                'email' => $NewUserValues['email'], 'address' => $NewUserValues['address'], 'city' => $NewUserValues['city'],
                'CAP' => $NewUserValues['CAP'], 'phoneNumber' => $NewUserValues['phoneNumber'] ];
            header("Location: ./profilo-utente?mode=edit");
            exit;
        }
    }

    return $message;
}



// --- HTML VISUALIZZAZIONE ---

$htmlView =
'<div class="view-mode">
    <span>
        <h2>Le tue informazioni</h2>
        <a href="?mode=edit" aria-label="Modifica profilo">
            <img src="./assets/icons/edit-pencil.svg" alt="modifica profilo">
        </a>
    </span>
    <img src="[imgPath]" alt="foto profilo" class="circle-foto"/>
    <dl aria-label="informazioni dell\'utente">
        <dt>Nome: </dt> <dd>[nome-utente]</dd>
        <dt>Cognome: </dt> <dd>[cognome-utente]</dd>
        <dt>Indirizzo: </dt> <dd>[indirizzo-utente]</dd>
        <dt>Email: </dt> <dd>[email-utente]</dd>
        <dt>Telefono: </dt> <dd>+39 [telefono-utente-view]</dd>
    </dl>
</div>';

// --- HTML MODIFICA ---

$htmlEdit = '
    <div class="edit-mode">
        <h2>Modifica Profilo</h2>
        <form class="edit-mode" method="POST" action="profilo-utente" enctype="multipart/form-data">
            <fieldset>
                <legend>Informazioni personali</legend>
                <div>
                    <label for="new-pic">Cambia Foto:</label>
                    <input type="file" id="new-pic" name="new-pic" accept="image/*">
                </div>
                <div>
                    <label for="new-name">Nome:</label>
                    <input type="text" id="new-name" name="new-name" autocomplete="name" value="[nome-utente]" placeholder="Nome">
                    <p class="error-form">[erroriNome]</p>
                </div>
                <div>
                    <label for="new-surname">Cognome:</label>
                    <input type="text" id="new-surname" name="new-surname" autocomplete="family-name" value="[cognome-utente]" placeholder="Cognome">
                    <p class="error-form">[erroriCognome]</p>
                </div>
                <div>
                    <label for="new-address">Via e numero civico:</label>
                    <input type="text" id="new-address" name="new-address" autocomplete="street-address" value="[via-utente]" placeholder="Via L. Da Vinci n.10">
                    <p class="error-form">[erroriIndirizzo]</p>
                </div>
                <div>
                    <label for="new-city">Città:</label>
                    <input type="text" id="new-city" name="new-city" autocomplete="address-level2" value="[citta-utente]" placeholder="Roma">
                    <p class="error-form">[erroriCitta]</p>
                </div>
                <div id="new-cap">
                    <label for="new-cap">CAP:</label>
                    <input type="text" id="new-cap" name="new-cap" autocomplete="postal-code" value="[cap-utente]" placeholder="00000">
                    <p class="error-form">[erroriCAP]</p>
                    <p class="error-form">[erroriIndirizzoTotale]</p>
                </div>
                <div>
                    <label for="new-email">Email:</label>
                    <input type="email" id="new-email" name="new-email" autocomplete="email" value="[email-utente]" placeholder="esempio@gmail.com">
                    <p class="error-form">[erroriEmail]</p>
                </div> 
                <div class="edit-number">
                    <label for="new-number">Telefono:</label>
                    <div>
                        <span>+39 </span>
                        <input type="tel" id="new-number" name="new-number" autocomplete="tel" value="[telefono-utente]" placeholder="000 000 0000">
                    </div>
                    <p class="error-form">[erroriTelefono]</p>
                </div>
            </fieldset>
            <fieldset class="fieldset-edit-pw">
                <legend>Modifica password</legend>
                <label for="old-pw">Vecchia password:</label>
                <div class="password-container">
                    <input type="password" id="old-pw" name="old-pw" placeholder="Vecchia password">
                    <i class="fas fa-eye"></i>
                </div>
                <label for="new-pw">Nuova password:</label>
                <div class="password-container">
                    <input type="password" id="new-pw" name="new-pw" placeholder="Nuova password">
                    <i class="fas fa-eye"></i>
                </div>
                <label for="new-pw-Confirmed">Conferma la password:</label>
                <div class="password-container">
                    <input type="password" id="new-pw-Confirmed" name="new-pw-Confirmed" placeholder="Conferma la password">
                    <i class="fas fa-eye"></i>
                </div>
            </fieldset>
            <p class="error-form">[erroriPassword]</p>
            [messaggiForm]
            <p id="infoPassword">La password deve essere diversa dalla precedente e deve contenere:
                <ul aria-labelledby="infoPassord">
                    <li>Minimo 8 caratteri, massimo 32</li>
                    <li>Almeno un numero</li>
                    <li>Una lettera maiuscola</li>
                    <li>Una lettera minuscola</li>
                    <li>Almenu un carattere speciale (! @ + ? / , - . $ _ =)</li>
                </ul>
            </p>
            <span>
                <a href="profilo-utente" class="cancel-edit">Annulla</a>
                <button type="submit" name="edit-profile">Salva</button>
            </span>
        </form>
    </div>';

if (isset($_GET['mode']) && $_GET['mode'] === 'edit') {
    $contenutoScelto = $htmlEdit;
} else {
    $contenutoScelto = $htmlView;
    if(isset($_SESSION['form_inputs'])){
        unset($_SESSION['form_inputs']);
    }
}




$infoUtente = null;
$listaAvvisi = "";

//connesisone al DB
$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
if ($connessioneOK) {
	if (isset($_SESSION['email'])) {
        $infoUtente = $connessione->getUserInfo($_SESSION['email']);
        $listaAvvisi = createMovementList($connessione, $filtroCorrente);
        $messageForm = editAccount($connessione, $NewUserValues, $infoUtente);
    }else{
        header("Location: ./login"); 
        exit;
    }
    $connessione->closeConnection();
}else{
	header("Location: ./404");
    exit;    
}

//se non riesco a prendere le info dell'utente rimando alla pagina di login, vuol dire che l'utente non era nel db 
// (impossibile ma meglio essere sicuri)
if ($infoUtente == null) {
    header("Location: ./login"); 
    exit;
}

if($infoUtente['Via'] === null || $infoUtente['Citta'] === null || $infoUtente['CAP'] === null){
    $indirizzoCompleto = "<em>Sconosciuto</em>";
}else{
    $indirizzoCompleto = $infoUtente['Via'] . ', ' . $infoUtente['Citta'] . ' ' . $infoUtente['CAP'];
}

$paginaHTML = file_get_contents('./src/template/layout.html');
if ($paginaHTML === false) {
    $paginaHTML = "<p class='error-form'>Errore: template layout.html non trovato o non leggibile.</p>";
}


$title = '<title>Profilo - PetMatch </title>';
$description = '<meta name="description" content="Profilo di PetMatch">';
$keywords = "";

$nav = buildUserNav($userMenu, './profilo-utente');

$footer = file_get_contents('./src/template/partials/footer.html');

$breadcrumb = getBreadcrumb('profilo-utente', $pagine);

$main = file_get_contents('./src/template/main/profilo-utente.html');

$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);

$paginaHTML = str_replace('[lista avvisi]', $listaAvvisi, $paginaHTML);
$paginaHTML = str_replace('[UserInfoSostituzione]', $contenutoScelto, $paginaHTML);
//il filtro selezionato nella tendina precedentemente all'invio della form viene mantenuto
$paginaHTML = str_replace( 'value="' . $filtroCorrente . '"', 'value="' . $filtroCorrente . '" selected', $paginaHTML);

$paginaHTML = str_replace('[erroriNome]', $messageForm['name'], $paginaHTML);
$paginaHTML = str_replace('[erroriCognome]', $messageForm['surname'], $paginaHTML);
$paginaHTML = str_replace('[erroriEmail]', $messageForm['email'], $paginaHTML);
$paginaHTML = str_replace('[erroriPassword]', $messageForm['password'], $paginaHTML);
$paginaHTML = str_replace('[erroriIndirizzo]', $messageForm['address'], $paginaHTML);
$paginaHTML = str_replace('[erroriCitta]', $messageForm['city'], $paginaHTML);
$paginaHTML = str_replace('[erroriCAP]', $messageForm['CAP'], $paginaHTML);
$paginaHTML = str_replace('[erroriTelefono]', $messageForm['phoneNumber'], $paginaHTML);
$paginaHTML = str_replace('[messaggiForm]', $messageForm['generic'], $paginaHTML);
$paginaHTML = str_replace('[erroriIndirizzoTotale]', $messageForm['indirizzo-totale'], $paginaHTML);

$paginaHTML = str_replace('[imgPath]', $infoUtente['ImgPath'] ? $infoUtente['ImgPath'] : './assets/images/users/default-pic.png', $paginaHTML);
$paginaHTML = str_replace('[nome-utente]', $NewUserValues['name'] ? $NewUserValues['name'] : $infoUtente['Nome'], $paginaHTML);
$paginaHTML = str_replace('[cognome-utente]', $NewUserValues['surname'] ? $NewUserValues['surname'] : $infoUtente['Cognome'], $paginaHTML);
$paginaHTML = str_replace('[indirizzo-utente]', $indirizzoCompleto, $paginaHTML);
$paginaHTML = str_replace('[email-utente]', $NewUserValues['email'] ? $NewUserValues['email'] : $_SESSION['email'], $paginaHTML);
$paginaHTML = str_replace('[via-utente]', $NewUserValues['address'] ? $NewUserValues['address'] : $infoUtente['Via'], $paginaHTML);
$paginaHTML = str_replace('[citta-utente]', $NewUserValues['city'] ? $NewUserValues['city'] : $infoUtente['Citta'], $paginaHTML);
$paginaHTML = str_replace('[cap-utente]', $NewUserValues['CAP'] ? $NewUserValues['CAP'] : $infoUtente['CAP'], $paginaHTML);
$paginaHTML = str_replace('[telefono-utente]', $NewUserValues['phoneNumber'] ? $NewUserValues['phoneNumber'] : $infoUtente['Telefono'], $paginaHTML);
$paginaHTML = str_replace('[telefono-utente-view]', $infoUtente['Telefono'] ? $infoUtente['Telefono'] : "<em>Sconosciuto</em>", $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

echo $paginaHTML;

?>