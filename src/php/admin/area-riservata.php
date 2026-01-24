<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;
session_start();

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) { //il primo controlla se esiste la variabile admin in session, la seconda controlla che sia affettivamente admin
    header("Location: ./accedi");
    exit;
}


if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])){ 
	logout();
}
function buildToDoList(DBAccess $conn): string {
	$html = '<ul id="to-do-list" aria-label="compiti da completare">';
	$tasks =$conn->createAdminTasks($_SESSION['email'] ?? '');
	$links= [
		['href' => './senza-amministratore', 'type' => 'Animali senza admin'],
		['href' => './richieste-adozione?stato=In+valutazione&appunti=0', 'type' => 'Appunti da prendere'],
		['href' => './nuove-accoglienze', 'type' => 'Accoglienze'],
		['href' => './richieste-adozione?stato=Nuove', 'type' => 'Adozioni da valutare'],
		['href' => 'richieste-adozione?stato=Da+trasportare&trasporto=0', 'type' => 'Trasporti da organizzare'],
	];
	foreach ($links as $index => $link) {
		$nQuery = $tasks[$index] ?? 0;
		$html .= '
		<li>
			<a href="' . $link['href'] . '" aria-label="' . $nQuery . $link['type'].'">
				<p class="n-query" aria-hidden="true">' . $nQuery . '</p>
				<p class="query-type" aria-hidden="true">' . $link['type'] . '</p>
			</a>
		</li>
		';
	}
	$html .= '</ul>';
	return $html;
}


function buildStatisticsArea(DBAccess $conn): string{
	$html = '<ul id="statistics-list" aria-labelledby="title-statistiche">';
	$stats = $conn->createAdminStats($_SESSION['email'] ?? '');
	$types= [
		'Adozioni completate',
		'Richieste visionate',
		'In corso di adozione',
	];
	foreach ($types as $index => $type) {
		$html .= '
			<li aria-label="' . ($stats[$index] ?? 0). $type . '">
				<p class="n-query" aria-hidden="true">' . ($stats[$index] ?? 0) . '</p>
				<p class="query-type" aria-hidden="true">' . $type . '</p>
			</li>
		';
	}
	$html .= '</ul>';
	return $html;
}


function buildInfoAdmin(): array{
	$html = '';
	$titolo = '';
	if (isset($_GET['mode']) && $_GET['mode'] === 'edit') {
		$titolo = '<h2>Modifica le tue informazioni</h2>';
		$html = '
			<div id="informazioni-admin" class="edit-mode">
				<form class="edit-mode" method="POST" action="area-riservata#informazioni-admin" enctype="multipart/form-data">
					<fieldset>
						<legend class="sr-only">Informazioni personali</legend>
						<div>
							<label class="sr-only" for="new-pic">Cambia Foto</label>
							<input type="file" id="new-pic" name="new-pic" accept="image/*">
							<label class="checkbox-container-pic" for="delete-pic">
								<input type="checkbox" id="delete-pic" name="delete-pic">
								Rimuovi foto profilo
							</label>
						</div>
						<div>
							<label for="new-name">Nome*</label>
							<input type="text" id="new-name" name="new-name" autocomplete="name" value="[nomeAdmin]" placeholder="Nome">
							<p class="error-form">[erroriNome]</p>
						</div>
						<div>
							<label for="new-surname">Cognome*</label>
							<input type="text" id="new-surname" name="new-surname" autocomplete="family-name" value="[cognomeAdmin]" placeholder="Cognome">
							<p class="error-form">[erroriCognome]</p>
						</div>
						<div class="edit-number">
							<label for="new-number">Telefono con prefisso</label>
							<div>
								<input type="tel" id="new-number" name="new-number" autocomplete="tel" value="[telefono-Admin]" placeholder="+39 000 000 0000">
							</div>
							<p class="error-form">[erroriTelefono]</p>
						</div>
					</fieldset>
					<span>
						<a href="area-riservata#informazioni-admin" class="cancel-edit">Annulla</a>
						<button type="submit" name="edit-profile">Salva</button>
					</span>
				</form>
			</div>';

	} else {
		$titolo = '<h2>Le tue informazioni</h2>';
		$html =
			'<div id="informazioni-admin" class="text-details"><dl aria-label="informazioni dell\'utente">
				<dt>Nome</dt> <dd>[nomeAdmin]</dd>
				<dt>Cognome</dt> <dd>[cognomeAdmin]</dd>
				<dt>Email</dt> <dd>[emailAdmin]</dd>
				<dt>Telefono</dt> <dd>[telefonoAdmin]</dd>
			</dl></div>';
	}

	return ['contenuto' => $html, 'titolo' => $titolo];
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
function editInfoAdmin(DBAccess $conn, &$NewUserValues, $adminInfo): array {
    
    $message = [
        'generic' => '',
        'name' => '',
        'surname' => '',
        'phoneNumber' => ''
    ];

    if (isset($_SESSION['form_status_info'])) {
        
        if($_SESSION['form_status_info'] === 'error'){
            $savedErrors = $_SESSION['form_errors_info'] ?? [];
            
            if (isset($savedErrors['generic'])) {
                $message['generic'] = "<p class='error-form'>" . $savedErrors['generic'] . "</p>";
            }

            if (isset($savedErrors['name'])) {
                $message['name'] = $savedErrors['name'];
            }
            
            if (isset($savedErrors['surname'])) {
                $message['surname'] = $savedErrors['surname'];
            }

            if (isset($savedErrors['phoneNumber'])) {
                $message['phoneNumber'] = $savedErrors['phoneNumber'];
            }
            
            $savedInputs = $_SESSION['form_inputs'] ?? [];
            $NewUserValues['name'] = $savedInputs['name'] ?? '';
            $NewUserValues['surname'] = $savedInputs['surname'] ?? '';
            $NewUserValues['phoneNumber'] = $savedInputs['phoneNumber'] ?? '';
            $NewUserValues['address'] = null;
            $NewUserValues['city'] = null;
            $NewUserValues['CAP'] = null;

            unset($_SESSION['form_status_info'], $_SESSION['form_errors_info'], $_SESSION['form_inputs']);
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit-profile'])) { 

        $name    = trim($_POST['new-name'] ?? '');
        $surname = trim($_POST['new-surname'] ?? '');
        $phoneNumber = trim($_POST['new-number'] ?? '');

        $name = mb_convert_case($name, MB_CASE_TITLE, "UTF-8");
        $surname = mb_convert_case($surname, MB_CASE_TITLE, "UTF-8");
        $phoneNumber = str_replace(' ','', $phoneNumber);

        $NewUserValues['name'] = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $NewUserValues['surname'] = htmlspecialchars($surname, ENT_QUOTES, 'UTF-8');
        $NewUserValues['phoneNumber'] = htmlspecialchars($phoneNumber, ENT_QUOTES, 'UTF-8');
        $NewUserValues['address'] = null;
        $NewUserValues['city'] = null;
        $NewUserValues['CAP'] = null;
        $NewUserValues['profilePic'] = $_FILES['new-pic'] ?? null;

        $errors = [];
        $regexNome = "/^(?=.*[\p{L}]{2})[\p{L}\s']+$/u"; 
        $regexPhone = "/^(\+[0-9]{1,3}\s?)[0-9]{10}$/";

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

        if(strlen($NewUserValues['phoneNumber']) === 0){
            $NewUserValues['phoneNumber'] = null;
        }else if (strlen($NewUserValues['phoneNumber']) >= 14 || !preg_match($regexPhone, $NewUserValues['phoneNumber'])) {
            $errors['phoneNumber'] = "Il numero di telefono non è valido.";
        }

        if (empty($errors)) {

            if(isset($_FILES['new-pic']) && !empty($_FILES['new-pic']['name']) && $_POST['delete-pic'] !== 'on'){
                $uploadedPicPath = uploadImage($_FILES['new-pic'], 'admins');
                if ($uploadedPicPath === false) {
                    $_SESSION['form_status_info'] = 'error';
                    $_SESSION['form_errors_info'] = ['profilePic' => "Errore durante il caricamento dell'immagine del profilo."];
                    $_SESSION['form_inputs'] = [
                        'name' => $NewUserValues['name'],
                        'surname' => $NewUserValues['surname'],
                        'phoneNumber' => $NewUserValues['phoneNumber']
                    ];
                    header("Location: ./area-riservata?mode=edit");
                    exit;
                }
                $NewUserValues['profilePic'] = $uploadedPicPath;
            }elseif($_POST['delete-pic'] === 'on'){
                $NewUserValues['profilePic'] = "./assets/images/admins/default-pic.png";
            }else{
                $NewUserValues['profilePic'] = $adminInfo['ImgPath'] ?? null; 
            }

            $EditResult = $conn->updateUserInfo($_SESSION['email'], $NewUserValues);
            
            if (!$EditResult){
                $_SESSION['form_status_info'] = 'error';
                $_SESSION['form_errors_info'] = ['generic' => "Sistema momentaneamente non disponibile."];
                $_SESSION['form_inputs'] = [
                    'name' => $NewUserValues['name'], 
                    'surname' => $NewUserValues['surname'], 
                    'phoneNumber' => $NewUserValues['phoneNumber'] 
                ];
                header("Location: ./area-riservata?mode=edit");
                exit;
            }
            header("Location: ./area-riservata");
            exit;
            
        } else {
            $_SESSION['form_status_info'] = 'error';
            $_SESSION['form_errors_info'] = $errors; 
            $_SESSION['form_inputs'] = [
                'name' => $NewUserValues['name'], 
                'surname' => $NewUserValues['surname'], 
                'phoneNumber' => $NewUserValues['phoneNumber'] 
            ];
            header("Location: ./area-riservata?mode=edit");
            exit;
        }
    }

    return $message;
}

$stats = "";
$todolist = "";
$adminInfo ='';
$messageInfoForm ='';
$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
if ($connessioneOK) {
    // centralizzo le operazioni che richiedono la connessione, così non spreco risorse
	
	$todolist = buildToDoList($connessione);
	$stats = buildStatisticsArea($connessione);
	
	$adminInfo = $connessione->getUserInfo($_SESSION['email']);
	$adminInfoSection = buildInfoAdmin();
	
	
	$messageInfoForm = editInfoAdmin($connessione, $NewUserInfo, $adminInfo);
	

	$connessione->closeConnection();
}

if($adminInfo['Telefono']){
    $telefonoGrezzo = $adminInfo['Telefono'];
    $numero = substr($telefonoGrezzo, -10);
    $prefisso = substr($telefonoGrezzo, 0, -10);
    $printTelefono = trim($prefisso . ' ' . $numero);
}

if (empty($adminInfo['ImgPath']) || !file_exists($adminInfo['ImgPath'])) {
    $adminInfo['ImgPath'] = 'assets/images/admins/default-pic.png';
}

$msgSuccesso = '';
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $msgSuccesso = '<p class="success-message" role="alert">Animale aggiunto con successo!</p>';
}

$paginaHTML = loadTemplate('./src/template/layout-admin.html', '<p>Errore: template layout.html non trovato o non leggibile.</p>');

$title = '<title>Area riservata admin - PetMatch </title>';
$description = '<meta name="description" content="Area riservata per gli amministratori di PetMatch">';
$keywords = ""; //TO DO


$nav = buildAdminNav($adminMenu, './area-riservata');
$breadcrumb = getBreadcrumb('area-riservata', $pagine);

$main = loadTemplate('./src/template/main/admin/area-riservata.html', '<p>Errore: template area-riservata.html non trovato o non leggibile.</p>');
$main = str_replace('[messaggioSuccesso]', $msgSuccesso, $main);
$main = str_replace('[to-do-list]', $todolist, $main);
$main = str_replace('[stats]', $stats, $main);
$main = str_replace('[titolo]', $adminInfoSection['titolo'], $main);
$main = str_replace('[admin-info]', $adminInfoSection['contenuto'], $main);
$main = str_replace('[imgPath]', $adminInfo['ImgPath'], $main);
$main = str_replace('[nomeAdmin]', $adminInfo['Nome'], $main);
$main = str_replace('[cognomeAdmin]', $adminInfo['Cognome'], $main);
if(isset($adminInfo['Telefono'])){
	$main = str_replace('[telefonoAdmin]', $printTelefono, $main);
	$main = str_replace('[telefono-Admin]', $printTelefono, $main);
}else{
	$main = str_replace('[telefonoAdmin]', '<em>Sconosciuto</em>', $main);
	$main = str_replace('[telefono-Admin]', '', $main);
}
$main = str_replace('[emailAdmin]', $_SESSION['email'], $main);
$main = str_replace('[erroriNome]', $messageInfoForm['name'], $main);
$main = str_replace('[erroriCognome]', $messageInfoForm['surname'], $main);
$main = str_replace('[erroriTelefono]', $messageInfoForm['phoneNumber'], $main);


$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);

echo $paginaHTML;
?>