<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;

//se non sono loggato rimando alla pagina di login
if (!isset($_SESSION['email'])) {
    header("Location: ./accedi");
    exit;    
}else if(isset($_SESSION['admin']) && $_SESSION['admin'] === true){
    header("Location: ./area-riservata");
    exit; 
}

$filtroCorrenteAvvisi = 'all';
$filtroCorrenteRichieste = 'all';
$tabAvvisi= 'checked';
$tabRichieste= '';
if (isset($_GET['state-avvisi'])) {
    $filtroCorrenteAvvisi = urldecode($_GET['state-avvisi']);
}
else if (isset($_GET['state-richieste'])) {
    $filtroCorrenteRichieste = urldecode($_GET['state-richieste']);
    $tabAvvisi= '';
    $tabRichieste= 'checked';
}

function deleteAccount(DBAccess $conn){
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete-account'])) { 
        $risultato = $conn->deleteAccount($_SESSION['email']);
        if($risultato){
            logout();
            header('Location: ./accedi');
            exit;
        }else{
            die("La query di eliminazione è fallita.");
        }
    } 
}

function checkRole(DBAccess $conn) {
    $userRole = $conn->getRole($_SESSION['email']);
    if ($userRole === 'Admin'){
        header("Location: ./area-riservata");
        exit; 
    }
    if ($userRole === null){
        logout();
    }
}

function createMovementList(DBAccess $conn, $filtro = 'all'): string {
    $listaMovimenti = '';

    $sendFiltro = $filtro === 'all' ? "'Nuova','In valutazione','Accettata','Respinta','Annullata','Da trasportare'" : "'".$filtro."'";

    $richieste = $conn->getUserRequests($_SESSION['email'], $sendFiltro);

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
                    $dataFormattataPartenza= date("d/m/Y", strtotime($richiesta['DataPartenza']));
                    $dataFormattataArrivo= date("d/m/Y", strtotime($richiesta['DataArrivo']));

                    $screenPartenza = date("Y-m-d", strtotime($richiesta['DataPartenza']));
                    $screenArrivo = date("Y-m-d", strtotime($richiesta['DataArrivo']));
                    $statoRichiesta = '<em>'.$nomeAnimale.'</em> <strong>partità</strong> il giorno <em><time datetime = "'.$screenPartenza.'">'.$dataFormattataPartenza.'</time></em> e <strong>arriverà</strong> il giorno<em><time datetime="'.$screenArrivo.'">'.$dataFormattataArrivo.'</time>!</em>';
                    break;
                case 'Accettata':
                    $statoRichiesta = 'Complimenti! <strong>Hai adottato</strong> con successo <em>'.$nomeAnimale.'</em>.';
                    break;
                case 'Respinta':
                    $statoRichiesta = 'Siamo spiacenti di informarti che la tua richiesta di adozione per <em>'.$nomeAnimale.'</em> è stata <strong>respinta.</strong>';
                    break;
                case 'Annullata':   
                    $statoRichiesta = 'Hai <strong>annullato</strong> la richiesta di adozione per <em>'.$nomeAnimale.'</em>.';
                    break;
                case 'Nuova':
                    $statoRichiesta = 'La tua richiesta di adozione per <em>'.$nomeAnimale.'</em> è stata <strong>inviata</strong> con successo e sarà valutata a breve.';
                    break;
            }

            $listaMovimenti .= '<li>
                <article>
                    <p>'.$statoRichiesta.'</p>';
                    $listaMovimenti .= $conn->isAnimalAdopted($richiesta['IDanimale'])?'<p class="nonDisponibile"><em>Animale adottato</em></p>':
                    '<a href="./animali?id='.urlencode($richiesta['IDanimale']).'">Vedi animale</a>';
            $listaMovimenti.='        
                </article>
            </li>';
        }

        $listaMovimenti .= "</ul>";
        return $listaMovimenti;
    }
}

function createRequestList(DBAccess $conn, $filtro = 'all'): string {
    $listaMovimenti = '';

    if($filtro ==='Aperte'){
        $sendFiltro = "'Nuova','In valutazione'";
    }else if($filtro ==='Chiuse'){
        $sendFiltro = "'Accettata','Respinta','Annullata','Da trasportare'";
    }else{
        $sendFiltro = "'Nuova','In valutazione','Accettata','Respinta','Annullata','Da trasportare'";
    }

    $richieste = $conn->getUserRequests($_SESSION['email'],$sendFiltro);

    if (count($richieste) === 0) {
        return "<p id=\"query-vuota\">Non sono presenti richieste di adozione.</p>";
    }else{
        $listaRichieste = '<ul id="lista-richieste" aria-labelledby="ultime-richieste">';
        foreach ($richieste as $richiesta) {

            $statoRichiesta = '';

            $nomeAnimale = htmlspecialchars($richiesta['NomeAnimale']);

            $statoRichiesta = 'Richiesta <strong>'.$filtro.'</strong> per <em>'.$nomeAnimale.'</em>.';

            switch($richiesta['Stato']){
                case 'Nuova':
                case 'In valutazione':
                    $statoRichiesta = '<li> <article> <p>Richiesta <strong>aperta</strong> per <em>'.$nomeAnimale.'</em>.';
                    break;
                case 'Da trasportare':
                case 'Accettata':
                case 'Respinta':
                case 'Annullata':   
                    $statoRichiesta = '<li class = "close-request"> <article> <p>Richiesta <strong>chiusa</strong> per <em>'.$nomeAnimale.'</em>.';
                    break;
            }

            $listaRichieste .= $statoRichiesta.'</p>
                    <a href="./revisione-richiesta?id-animale='.$richiesta['IDanimale'].'">Vai alla richiesta</a>
                </article>
            </li>';
        }

        $listaRichieste .= "</ul>";
        return $listaRichieste;
    }
}

$NewUserInfo = [
    'name' => '',
    'surname' => '',
    'phoneNumber' => '',
    'address' => '',
    'city' => '',
    'CAP' => '',
    'profilePic' => ''
];

$NewUserManagement = [
    'email' => '',
    'Newpassword' => ''
];

function editInfoAccount(DBAccess $conn, &$NewUserValues, $infoUtente, $editAddress, $removeAddress): array {
	
	$message = [
        'generic' => '',
        'name' => '',
        'surname' => '',
        'address' => '',
        'city' => '',
        'CAP' => '',
        'phoneNumber' => '',
        'indirizzo-totale' => ''
    ];

    if (isset($_SESSION['form_status_info'])) {
        
        if($_SESSION['form_status_info'] === 'error'){
            $savedErrors = $_SESSION['form_errors_info'] ?? [];
            
            if (isset($savedErrors['generic'])) {
                $message['generic'] = "<p class='error-form'>" . $savedErrors['generic'] . "</p>";
            }

            if (isset($savedErrors['name'])) {
                $message['name'] = $savedErrors['name'] ;
            }
            
            if (isset($savedErrors['surname'])) {
                $message['surname'] = $savedErrors['surname'] ;
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

			
            $savedInputs = $_SESSION['form_inputs'] ?? [];
            $NewUserValues['name'] = $savedInputs['name'] ?? '';
            $NewUserValues['surname'] = $savedInputs['surname'] ?? '';
            $NewUserValues['address'] = $savedInputs['address'] ?? '';
            $NewUserValues['city'] = $savedInputs['city'] ?? '';
            $NewUserValues['CAP'] = $savedInputs['CAP'] ?? '';
            $NewUserValues['phoneNumber'] = $savedInputs['phoneNumber'] ?? '';

            // Pulizia sessione
            unset($_SESSION['form_status_info']);
            unset($_SESSION['form_errors_info']);
            unset($_SESSION['form_inputs']);
        }
    }

    // GESTIONE INVIO FORM (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit-profile'])) { 

        $name    = trim($_POST['new-name'] ?? '');
        $surname = trim($_POST['new-surname'] ?? '');
        $address = trim($_POST['new-address'] ?? '');
        $city    = trim($_POST['new-city'] ?? '');
        $CAP     = trim($_POST['new-cap'] ?? '');
        $phoneNumber = trim($_POST['new-number'] ?? '');

        $name = mb_convert_case($name, MB_CASE_TITLE, "UTF-8");
        $surname = mb_convert_case($surname, MB_CASE_TITLE, "UTF-8");
        $address = mb_convert_case($address, MB_CASE_TITLE, "UTF-8");
        $city = mb_convert_case($city, MB_CASE_TITLE, "UTF-8");
        $phoneNumber = str_replace(' ','', $phoneNumber); // rimuove tutti i caratteri non numerici

        // Sanitizzazione per redisplay
        $NewUserValues['name'] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $NewUserValues['surname'] = htmlspecialchars($surname, ENT_QUOTES, 'UTF-8');
        $NewUserValues['address'] = htmlspecialchars($address, ENT_QUOTES, 'UTF-8');
        $NewUserValues['city'] = htmlspecialchars($city, ENT_QUOTES, 'UTF-8');
        $NewUserValues['CAP'] = htmlspecialchars($CAP, ENT_QUOTES, 'UTF-8');
        $NewUserValues['phoneNumber'] = htmlspecialchars($phoneNumber, ENT_QUOTES, 'UTF-8');
        $NewUserValues['profilePic'] = $_FILES['new-pic'] ?? null;

        $errors = [];

        $regexNome = "/^(?=.*[\p{L}]{2})[\p{L}\s']+$/u"; 
        $regexPhone = "/^(\+[0-9]{1,3}\s?)[0-9]{10}$/";
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

        $hasAddress = strlen($NewUserValues['address']) > 0;
        $hasCity    = strlen($NewUserValues['city']) > 0;
        $hasCAP     = strlen($NewUserValues['CAP']) > 0;

        $modifiedAddress = !(($infoUtente['Via'] === $NewUserValues['address']) && ($infoUtente['Citta'] === $NewUserValues['city']) && ($infoUtente['CAP'] === $NewUserValues['CAP']));

        $isAllEmpty = (!$hasAddress && !$hasCity && !$hasCAP);
        $isAllFull  = ($hasAddress && $hasCity && $hasCAP);
        $isPartial  = !($isAllEmpty || $isAllFull);

        if ($isPartial) {
             $errors['indirizzo-totale'] = "L'indirizzo è incompleto: devi compilare Via, Città e CAP insieme o lasciarli tutti vuoti.";
        } 
        else {
            
            if ($isAllEmpty) {
                if (!$removeAddress) { 
                    $errors['indirizzo-totale'] = "Impossibile rimuovere l'indirizzo: ci sono richieste di adozioni aperte.";
                }
            }
            
            if($isAllEmpty || ($isAllFull && $modifiedAddress)){
                // $errors['address'] = "Ci passo.";
                if (!$editAddress) {
                    $errors['indirizzo-totale'] = "Impossibile modificare l'indirizzo: c'è un trasporto attivo.";
                } 
                else {
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
                }
            }
        }
        


        if(strlen($NewUserValues['phoneNumber']) === 0){
            $NewUserValues['phoneNumber'] = null;
        }else if (strlen($NewUserValues['phoneNumber']) >= 14 || !preg_match($regexPhone, $NewUserValues['phoneNumber'])) {
            $errors['phoneNumber'] = "Il numero di telefono non è valido.";
        }

        $valueforDB = [
            'name' => $name,
            'surname' => $surname,
            'phoneNumber' => $phoneNumber,
            'address' => $address,
            'city' => $city,
            'CAP' => $CAP,
            'profilePic' =>'',
        ];

        /* AZIONI */
        if (empty($errors)) {

            //la foto del profilo avrà sempre qualcosa anche se non si selezionano immagini, bisogna controllarlo con empty
            if(isset($_FILES['new-pic']) && !empty($_FILES['new-pic']['name']) && !isset($_POST['delete-pic'])){
                $risultato = deleteStoredFile($infoUtente['ImgPath'],);
                $valueforDB['profilePic'] = uploadImage($_FILES['new-pic'], 'users');
            }else if(isset($_POST['delete-pic'])){
                deleteStoredFile($infoUtente['ImgPath']);
                $valueforDB['profilePic'] = null;
            }else{
                $valueforDB['profilePic'] = $infoUtente['ImgPath']; 
            }

            $EditResult = $conn->updateUserInfo($_SESSION['email'], $valueforDB);
            
            if (!$EditResult){
                $_SESSION['form_status_info'] = 'error';
                $_SESSION['form_errors_info'] = ['generic' => "Sistema momentaneamente non disponibile."];
                $_SESSION['form_inputs'] = ['name' => $NewUserValues['name'], 'surname' => $NewUserValues['surname'], 
                'address' => $NewUserValues['address'], 'city' => $NewUserValues['city'],
                'CAP' => $NewUserValues['CAP'], 'phoneNumber' => $NewUserValues['phoneNumber'] ];
            }

            header("Location: ./profilo-utente");
            exit;
            
        } else {
            $_SESSION['form_status_info'] = 'error';
            $_SESSION['form_errors_info'] = $errors; 
            $_SESSION['form_inputs'] = ['name' => $NewUserValues['name'], 'surname' => $NewUserValues['surname'], 
                'address' => $NewUserValues['address'], 'city' => $NewUserValues['city'],
                'CAP' => $NewUserValues['CAP'], 'phoneNumber' => $NewUserValues['phoneNumber'] ];
            header("Location: ./profilo-utente?mode=edit");
            exit;
        }
    }

    return $message;
}


function editManagementAccount(DBAccess $conn, &$NewUserValues, $infoUtente): array {
	
	$message = [
        'generic' => '',
        'email' => '',
        'password' => ''
    ];

    if (isset($_SESSION['form_status_management'])) {
        
        if($_SESSION['form_status_management'] === 'error'){
            $savedErrors = $_SESSION['form_errors_management'] ?? [];
            
            if (isset($savedErrors['generic'])) {
                $message['generic'] = "<p class='error-form'>" . $savedErrors['generic'] . "</p>";
            }

            if (isset($savedErrors['email'])) {
                $message['email'] = $savedErrors['email'] ;
            }

            if (isset($savedErrors['password'])) {
                $message['password'] = $savedErrors['password'] ;
            }
			
            $savedInputs = $_SESSION['form_inputs'] ?? [];
            $NewUserValues['email'] = $savedInputs['email'] ?? '';

            // Pulizia sessione
            unset($_SESSION['form_status_management']);
            unset($_SESSION['form_errors_management']);
            unset($_SESSION['form_inputs']);
        }
    }

    // GESTIONE INVIO FORM (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit-profile-management'])) { 

        $email   = trim($_POST['new-email'] ?? '');
        $oldPassword = trim($_POST['old-pw'] ?? '');
        $newPassword = trim($_POST['new-pw'] ?? '');
        $confirmPassword = trim($_POST['new-pw-Confirmed'] ?? '');

        $email = mb_strtolower($email, "UTF-8");

        $NewUserValues['email'] = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');

        $errors = [];

        $regexEmail = "/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,10})$/i";
        $regexPassword = '/^(?=.*\d)(?=.*[A-Z])(?=.*[a-z])(?=.*[!@+?\/,.\-$_=])[a-zA-Z0-9!@+?\/,.\-$_=]{8,32}$/';

        /* VALIDAZIONE CAMPI */

        if (!preg_match($regexEmail, $NewUserValues['email'])) {
            $errors['email'] = "Formato email non valido.";
        } else {
            if($conn->checkEmailExists($NewUserValues['email']) && $NewUserValues['email'] !== $_SESSION['email']){ 
                $errors['email'] = "L'email è già in uso.";
            }
        }

        if(strlen($oldPassword) === 0 && strlen($newPassword) === 0 && strlen($confirmPassword) === 0){
            // nessun cambiamento di password
        } else if (!password_verify($oldPassword, $infoUtente['Password'])) {
            $errors['password'] = "La vecchia password non è corretta.";
        } else if (strlen($newPassword) < 8 || strlen($confirmPassword) < 8) {
            $errors['password'] = "La password deve essere di almeno 8 caratteri.";
        } else if(strlen($newPassword) > 32 || strlen($confirmPassword) > 32){
            $errors['password'] = "La password deve essere al massimo di 32 caratteri.";
        }else if (!preg_match($regexPassword, $newPassword) || $newPassword !== $confirmPassword) {
            $errors['password'] = "La password non rispetta i criteri richiesti o non coincide.";
        }else if (password_verify($newPassword, $infoUtente['Password'])) {
            $errors['password'] = "La nuova password deve essere diversa dalla vecchia.";
        }

        $valueForDB = [
            'email' => '',
            'Newpassword' => '',
        ];

        /* AZIONI */
        if (empty($errors)) {
            if(strlen($newPassword) !== 0){
                $valueForDB['Newpassword'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }else{
                $valueForDB['Newpassword'] = $infoUtente['Password'];
            }

            $valueForDB['email'] = $_POST['new-email'];

            $EditResult = $conn->updateUserManagement($_SESSION['email'], $valueForDB);
            
            if ($EditResult) {
				$_SESSION['email'] = $email;

            } else {
                $_SESSION['form_status_management'] = 'error';
                $_SESSION['form_errors_management'] = ['generic' => "Sistema momentaneamente non disponibile."];
                $_SESSION['form_inputs'] = ['email' => $NewUserValues['email'] ];
            }

            header("Location: ./profilo-utente");
            exit;
            
        } else {
            $_SESSION['form_status_management'] = 'error';
            $_SESSION['form_errors_management'] = $errors; 
            $_SESSION['form_inputs'] = ['email' => $NewUserValues['email'] ];
            header("Location: ./profilo-utente?mode=management");
            exit;
        }
    }

    return $message;
}


if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])){ logout();}


// --- HTML VISUALIZZAZIONE ---

$htmlView =
'<article class="view-mode">
    <span>
        <h2>Le tue informazioni</h2>';
        $htmlView .= $_SESSION['email']==='user'?'':'
        <a href="?mode=management#gestisci-profilo" aria-label="Gestione dell\'account">
            <img src="./assets/icons/setting.svg" alt="" class="normal-icon"/>
            <img src="./assets/icons/setting-hover.svg" alt="" class="hover-icon"/>
        </a>';
    $htmlView .='
    </span>
    <img src="[imgPath]" alt="foto profilo" class="circle-foto"/>
    <a href="?mode=edit#modifica-profilo" class="edit-profile-link">
       <p aria-hidden=true>Modifica profilo</p> <img src="./assets/icons/edit-pencil.svg" alt="" /></a>
    <dl aria-label="informazioni dell\'utente" id="user-information">
        <dt>Nome: </dt> <dd>[nome-utente]</dd>
        <dt>Cognome: </dt> <dd>[cognome-utente]</dd>
        <dt>Indirizzo: </dt> <dd>[indirizzo-utente]</dd>
        <dt>Email: </dt> <dd>[email-utente]</dd>
        <dt>Telefono: </dt> <dd>[telefono-utente-view]</dd>
    </dl>
    <form action="./profilo-utente" method="post">
        <button type="submit" name="logout" class="logout-btn">Disconnettiti</button>
    </form>
</article>';

// --- HTML MODIFICA ---

$htmlEdit = '
    <div class="edit-mode" id="modifica-profilo" tabindex="-1">
        <h2>Modifica il profilo</h2>
        <form class="edit-mode" method="post" action="profilo-utente" enctype="multipart/form-data">
            <fieldset>
                <legend>Informazioni personali</legend>
                <label for="new-pic">Cambia Foto</label>
                <img src="[imgPath]" alt="Foto" id="foto-profilo" class="circle-foto" />
                <div>
                    <input type="file" id="new-pic" name="new-pic" accept=".jpg, .jpeg, .png" aria-label="carica la tua foto profilo."/>
                    <label class="checkbox-container-pic" for="delete-pic">
                        <input type="checkbox" id="delete-pic" name="delete-pic"/>
                        Rimuovi foto profilo
                    </label>
                </div>
                <div>
                    <label for="new-name">Nome*</label>
                    <input type="text" id="new-name" name="new-name" maxlength="100" autocomplete="name" value="[nome-utente]" placeholder="Nome"/>
                    <p class="error-form">[erroriNome]</p>
                </div>
                <div>
                    <label for="new-surname">Cognome*</label>
                    <input type="text" id="new-surname" name="new-surname" maxlength="100" autocomplete="family-name" value="[cognome-utente]" placeholder="Cognome"/>
                    <p class="error-form">[erroriCognome]</p>
                </div>
                <div class="edit-number">
                    <label for="new-number">Telefono con prefisso</label>
                    <div>
                        <input type="tel" id="new-number" name="new-number" maxlength="15" autocomplete="tel" value="[telefono-utente]" placeholder="+39 000 000 0000"/>
                    </div>
                    <p class="error-form">[erroriTelefono]</p>
                </div>
            </fieldset>
            <fieldset class="fieldset-indirizzo">
                <legend>Indirizzo</legend>
                    <p>Tutti i campi dell\'indirizzo devono essere completi, altrimenti nessuno.</p>
                    <div>
                        <label for="new-address">Via e numero civico</label>
                        <input type="text" id="new-address" name="new-address" maxlength="255" autocomplete="street-address" 
                        value="[via-utente]" placeholder="Via Paolotti n.42" aria-label="Tutti i campi dell\'indirizzo devono essere completi, altrimenti nessuno."/>
                        <p class="error-form">[erroriIndirizzo]</p>
                    </div>
                    <div>
                        <label for="new-city">Città</label>
                        <input type="text" id="new-city" name="new-city" maxlength="100" autocomplete="address-level2" 
                        value="[citta-utente]" placeholder="Padova"/>
                        <p class="error-form">[erroriCitta]</p>
                    </div>
                    <div>
                        <label for="new-cap">CAP</label>
                        <input type="text" id="new-cap" name="new-cap" maxlength="5" autocomplete="postal-code" 
                        value="[cap-utente]" placeholder="00000"/>
                        <p class="error-form">[erroriCAP]</p>
                        <p class="error-form">[erroriIndirizzoTotale]</p>
                    </div>
            </fieldset>
            [messaggiForm]
            <div>
                <a href="profilo-utente" class="cancel-edit">Annulla</a>
                <button type="submit" name="edit-profile">Salva</button>
            </div>
        </form>
    </div>';

// --- HTML GESTIONE ---

$htmlManagement = '
    <div class="edit-management" id="gestisci-profilo" tabindex="-1">
        <h2>Gestisci il profilo</h2>
        <form class="edit-mode" method="post" action="profilo-utente" novalidate>
            <fieldset class="fieldset-edit-email">
                <legend>Modifica email</legend>
                <div>
                    <label for="new-email">Email*</label>
                    <input type="email" id="new-email" name="new-email" maxlength="255" autocomplete="email" value="[email-utente]" placeholder="esempio@gmail.com"/>
                    <p class="error-form">[erroriEmail]</p>
                </div> 
            </fieldset>
            <fieldset class="fieldset-edit-pw">
                <legend>Modifica password</legend>
                <label for="old-pw">Vecchia password</label>
                <div class="password-container">
                    <input type="password" id="old-pw" name="old-pw" placeholder="Vecchia password" 
                    onpaste="return false;" 
                    oncopy="return false;"
                    autocomplete="off"/>
                    <i class="fas fa-eye"></i>
                </div>
                <label for="new-pw">Nuova password</label>
                <div class="password-container">
                    <input type="password" id="new-pw" name="new-pw" placeholder="Nuova password"
                    onpaste="return false;" 
                    oncopy="return false;"
                    autocomplete="off"/>
                    <i class="fas fa-eye"></i>
                </div>
                <label for="new-pw-Confirmed">Conferma la password</label>
                <div class="password-container">
                    <input type="password" id="new-pw-Confirmed" name="new-pw-Confirmed" placeholder="Conferma la password"
                    onpaste="return false;" 
                    oncopy="return false;"
                    autocomplete="off"/>
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
                    <li>Almeno un carattere speciale (! @ + ? / , - . $ _ =)</li>
                </ul>
            </p>
            <div>
                <a href="profilo-utente" class="cancel-edit">Annulla</a>
                <button type="submit" name="edit-profile-management">Salva</button>
            </div>
        </form>

        [ButtonEliminaProfilo]

        <dialog [openDialog] class="overlay-content">
                <div class="dialog-box">
                    <h3 id="modal-title">Eliminazione profilo</h3>
                    <p id="modal-desc">L\'eliminazione è <strong>irreversibile</strong>, vuoi continuare?</p>
                    
                    <div class="dialog-buttons">
                        <form method="post" action="#user-info">
                            <button type="submit" name="close-dialog" class="button-cancel">No, annulla</button>
                        </form>
                        
                        <form method="post">
                            <button type="submit" name="delete-account" >Si, elimina</button>
                        </form>
                    </div>
                </div>
            </dialog>
        
        </div>';

if (isset($_GET['mode']) && $_GET['mode'] === 'edit') {
    $contenutoScelto = $htmlEdit;
}else if (isset($_GET['mode']) && $_GET['mode'] === 'management') {
    $contenutoScelto = $htmlManagement;
} else {
    $contenutoScelto = $htmlView;
    if(isset($_SESSION['form_inputs'])){
        unset($_SESSION['form_inputs']);
    }
}





$infoUtente = null;
$listaAvvisi = "";
$listaRichieste = "";
$editAddressPermission = false;
$removeAddressPermission = false;

//connesisone al DB
$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
if ($connessioneOK) {
	if (isset($_SESSION['email'])) {
        checkRole($connessione);
        $editAddressPermission = $connessione ->getAddressPermissionEdit($_SESSION['email']);
        $removeAddressPermission = $connessione ->getAddressPermissionRemove($_SESSION['email']);
        $infoUtente = $connessione->getUserInfo($_SESSION['email']);
        $listaAvvisi = createMovementList($connessione, $filtroCorrenteAvvisi);
        $listaRichieste = createRequestList($connessione, $filtroCorrenteRichieste);
        $messageInfoForm = editInfoAccount($connessione, $NewUserInfo, $infoUtente, $editAddressPermission, $removeAddressPermission);
        $messageManagementForm = editManagementAccount($connessione, $NewUserManagement, $infoUtente);
        deleteAccount($connessione);
    }else{
        header("Location: ./accedi"); 
        exit;
    }
    $connessione->closeConnection();
    $messaggiGenerici = $messageInfoForm['generic'] . $messageManagementForm['generic'];
}else{
    exit;    
}

if (empty($infoUtente['ImgPath']) || !file_exists($infoUtente['ImgPath'])) {
    $infoUtente['ImgPath'] = 'assets/images/users/default-pic.png';
}

//se non riesco a prendere le info dell'utente rimando alla pagina di login, vuol dire che l'utente non era nel db 
// (impossibile ma meglio essere sicuri)
if ($infoUtente == null) {
    header("Location: ./accedi"); 
    exit;
}

if(!$infoUtente['Via'] || !$infoUtente['Citta'] || !$infoUtente['CAP']){
    $indirizzoCompleto = "<em>Sconosciuto</em>";
}else{
    $indirizzoCompleto = htmlspecialchars($infoUtente['Via'], ENT_QUOTES, 'UTF-8') . ', ' . htmlspecialchars($infoUtente['Citta'], ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($infoUtente['CAP'], ENT_QUOTES, 'UTF-8');
}

if($infoUtente['Telefono']){
    $telefonoGrezzo = htmlspecialchars($infoUtente['Telefono'], ENT_QUOTES, 'UTF-8');
    $numero = substr($telefonoGrezzo, -10);
    $prefisso = substr($telefonoGrezzo, 0, -10);
    $printTelefono = trim($prefisso . ' ' . $numero);
}

$paginaHTML = file_get_contents('./src/template/layout.html');
if ($paginaHTML === false) {
    $paginaHTML = "<p class='error-form'>Errore: template layout.html non trovato o non leggibile.</p>";
}


$title = '<title>Profilo - PetMatch </title>';
$description = '<meta name="description" content="Pagina di visualizzazione e modifica profilo di PetMatch">';
$keywords = "<meta name='keywords' content='Profilo, PetMatch, Modifica profilo, Le tue informazioni, Ultimi movimenti, Le tue richieste'>";
$nav = buildNav($userMenu, './profilo-utente');

$footer = buildFooter($footerMenu,  './profilo-utente');

$breadcrumb = getBreadcrumb('profilo-utente', $pagine);

$main = file_get_contents('./src/template/main/profilo-utente.html');

$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);

$paginaHTML = str_replace('[lista richieste]', $listaRichieste, $paginaHTML);
$paginaHTML = str_replace('[lista avvisi]', $listaAvvisi, $paginaHTML);
$paginaHTML = str_replace('[UserInfoSostituzione]', $contenutoScelto, $paginaHTML);
//il filtro selezionato nella tendina precedentemente all'invio della form viene mantenuto
$paginaHTML = str_replace( 'value="' . $filtroCorrenteRichieste . '"', 'value="' . $filtroCorrenteRichieste . '" selected', $paginaHTML);
$paginaHTML = str_replace( 'value="' . $filtroCorrenteAvvisi . '"', 'value="' . $filtroCorrenteAvvisi . '" selected', $paginaHTML);

$paginaHTML = str_replace('[tabAvvisi]', $tabAvvisi, $paginaHTML);
$paginaHTML = str_replace('[tabRichieste]', $tabRichieste, $paginaHTML);

$paginaHTML = str_replace('[erroriNome]', $messageInfoForm['name'], $paginaHTML);
$paginaHTML = str_replace('[erroriCognome]', $messageInfoForm['surname'], $paginaHTML);
$paginaHTML = str_replace('[erroriEmail]', $messageManagementForm['email'], $paginaHTML);
$paginaHTML = str_replace('[erroriPassword]', $messageManagementForm['password'], $paginaHTML);
$paginaHTML = str_replace('[erroriIndirizzo]', $messageInfoForm['address'], $paginaHTML);
$paginaHTML = str_replace('[erroriCitta]', $messageInfoForm['city'], $paginaHTML);
$paginaHTML = str_replace('[erroriCAP]', $messageInfoForm['CAP'], $paginaHTML);
$paginaHTML = str_replace('[erroriTelefono]', $messageInfoForm['phoneNumber'], $paginaHTML);
$paginaHTML = str_replace('[messaggiForm]', $messaggiGenerici, $paginaHTML);
$paginaHTML = str_replace('[erroriIndirizzoTotale]', $messageInfoForm['indirizzo-totale'], $paginaHTML);

$paginaHTML = str_replace('[imgPath]', $infoUtente['ImgPath'] ? htmlspecialchars($infoUtente['ImgPath'], ENT_QUOTES, 'UTF-8') : './assets/images/users/default-pic.png', $paginaHTML);
$paginaHTML = str_replace('[nome-utente]', $NewUserInfo['name'] ? $NewUserInfo['name'] : htmlspecialchars($infoUtente['Nome'], ENT_QUOTES, 'UTF-8'), $paginaHTML);
$paginaHTML = str_replace('[cognome-utente]', $NewUserInfo['surname'] ? $NewUserInfo['surname'] : htmlspecialchars($infoUtente['Cognome'], ENT_QUOTES, 'UTF-8'), $paginaHTML);
$paginaHTML = str_replace('[indirizzo-utente]', $indirizzoCompleto, $paginaHTML);
$paginaHTML = str_replace('[email-utente]', $NewUserManagement['email'] ? $NewUserManagement['email'] : $_SESSION['email'], $paginaHTML);
$paginaHTML = str_replace('[via-utente]', $NewUserInfo['address'] ? $NewUserInfo['address'] : htmlspecialchars($infoUtente['Via'], ENT_QUOTES, 'UTF-8')??'', $paginaHTML);
$paginaHTML = str_replace('[citta-utente]', $NewUserInfo['city'] ? $NewUserInfo['city'] : htmlspecialchars($infoUtente['Citta'], ENT_QUOTES, 'UTF-8')??'', $paginaHTML);
$paginaHTML = str_replace('[cap-utente]', $NewUserInfo['CAP'] ? $NewUserInfo['CAP'] : htmlspecialchars($infoUtente['CAP'], ENT_QUOTES, 'UTF-8')??'', $paginaHTML);
$paginaHTML = str_replace('[telefono-utente]', $NewUserInfo['phoneNumber'] ? $NewUserInfo['phoneNumber'] : htmlspecialchars($infoUtente['Telefono'], ENT_QUOTES, 'UTF-8')??'', $paginaHTML);
$paginaHTML = str_replace('[telefono-utente-view]', $infoUtente['Telefono'] ? $printTelefono : "<em>Sconosciuto</em>", $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

$paginaHTML = str_replace('[ButtonEliminaProfilo]', $_SESSION['email']==='user'?'':'   
            <form method="post" action="#user-info">
                <button type="submit" name="show-dialog" class="button-cancel">Elimina profilo</button>
            </form>', $paginaHTML);


 
$showModal = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['show-dialog']);
$closeModal = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close-dialog']);
$paginaHTML = str_replace('[openDialog]', $showModal?'open':'', $paginaHTML);
$paginaHTML = str_replace('[openDialog]', $closeModal?'':'', $paginaHTML);


echo $paginaHTML;
?>