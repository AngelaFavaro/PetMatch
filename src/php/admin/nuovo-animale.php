<?php
include './src/utils.php';
include './src/DBconnection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use DB\DBAccess;

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) { //il primo controlla se esiste la variabile admin in session, la seconda controlla che sia affettivamente admin
    header("Location: ./accedi");
    exit;
}

//controllo che non sono nell'area di modifica animale e non di nuovo animale
$currentUri = $_SERVER['REQUEST_URI'];
$isModified=false;

if(strpos($currentUri, 'modifica-animale') !== false){
    if(isset($_GET['id-animale'])){
        $idAnimaleMod = filter_var($_GET['id-animale'], FILTER_VALIDATE_INT);
        $isModified=true;
    }else{
        header("Location: ./nuovo-animale");
        exit;
    }
}

$NewAnimalInfo = [
    'Tipo' => '', 'Nome' => '', 'Razza' => '', 'Taglia' => '',
    'Sesso' => '', 'ImgPath' => '', 'DataNascita' => '', 'Pelo' => '',
    'Colore' => '', 'CondizioniMediche' => '', 'DescrComportamentale' => '', 'DescrFamiglia' => '',
    'Trasporto' => '', 'assegna_a_me' => ''
];

function createInfoAnimale(DBAccess $conn, &$NewAnimalValues, $isModified): array {
    $message = [
        'generic' => '', 'tipologia' => '', 'nome' => '', 'razza' => '',
        'taglia' => '', 'sesso' => '', 'dataNascita' => '', 'colore' => '',
        'pelo' => '', 'condMediche' => '', 'carattere' => '', 'famiglia' => '',
        'trasporto' => ''
    ];

    if (isset($_SESSION['form_status_info']) && $_SESSION['form_status_info'] === 'error') {
        $savedErrors = $_SESSION['form_errors_info'] ?? [];
        foreach ($savedErrors as $key => $val) {
            $message[$key] = ($key === 'generic') ? $val : "<p class='error-form'>$val</p>";
        }
        
        $savedInputs = $_SESSION['form_inputs'] ?? [];
        // Aggiorniamo NewAnimalValues con quello che l'utente aveva scritto
        foreach ($savedInputs as $key => $val) {
            $NewAnimalValues[$key] = $val;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-animal'])) { 
        $errors = [];
        
        // Recupero e trasformazione Tipologia
        $tipologia_val = $_POST['tipologia'] ?? '';
        $tipologia = ($tipologia_val === '0') ? 'Cane' : (($tipologia_val === '1') ? 'Gatto' : '');
        
        // Recupero campi testo
        $nome = trim($_POST['nome'] ?? '');
        $razza = trim($_POST['razza'] ?? '');
        $taglia = trim($_POST['taglia'] ?? '');
        $colore = trim($_POST['colore'] ?? '');
        $dataNascita = trim($_POST['dataNascita'] ?? '');
        $pelo = trim($_POST['pelo'] ?? '');
        $carattere = trim($_POST['carattere'] ?? '');
        $condMediche = trim($_POST['condMediche'] ?? '');
        $famiglia = trim($_POST['famiglia'] ?? '');
        
        // Trasporto e Sesso (M/F per il DB)
        $trasporto = isset($_POST['trasporto']) ? 1 : 0;
        $createMoreValue = isset($_POST['createMore']) ? 1 : 0;
        $sesso_val = $_POST['sesso'] ?? '';
        $sesso_db = ($sesso_val === '0') ? 'M' : (($sesso_val === '1') ? 'F' : '');
        $sesso_txt = ($sesso_val === '0') ? 'Maschio' : (($sesso_val === '1') ? 'Femmina' : '');

        $regexData = '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/'; 
        $regexTxt = '/^[a-zA-Z\x{00C0}-\x{017F}\s\',]+$/u';        
        
        // Validazione
        if (empty($tipologia)) $errors['tipologia'] = "Seleziona una tipologia.";
        if (strlen($nome) < 2 || !preg_match($regexTxt, $nome)) $errors['nome'] = "Nome non valido.";
        if (strlen($razza) < 2 || !preg_match($regexTxt, $razza)) $errors['razza'] = "Razza non valida.";
        if (strlen($colore) < 2 || !preg_match($regexTxt, $colore)) $errors['colore'] = "Colore non valido.";
        if (!in_array($taglia, ['Piccolo', 'Medio', 'Grande'])) $errors['taglia'] = "Seleziona una taglia.";
        if (!in_array($sesso_txt, ['Maschio', 'Femmina'])) $errors['sesso'] = "Seleziona il sesso.";
        if (!preg_match($regexData, $dataNascita)) {
            $errors['dataNascita'] = "Data non valida.";
        } else {
            $dataInserita = new DateTime($dataNascita);
            $oggi = new DateTime();
            $limitePassato = (new DateTime())->modify('-18 years');
            if ($dataInserita > $oggi) {
                $errors['dataNascita'] = "L'animale non può essere nato nel futuro!";
            } elseif ($dataInserita < $limitePassato) {
                $errors['dataNascita'] = "Data non valida: un animale adottabile non può avere più di 18 anni.";
            }
        }
        if (!in_array($pelo, ['Lungo', 'Corto', 'Medio'])) $errors['pelo'] = "Seleziona tipo pelo.";

        if (empty($carattere)) {
            $errors['carattere'] = "Inserire una descrizione del carattere.";
        } elseif (strlen($carattere) < 10) {
            $errors['carattere'] = "La descrizione del carattere è troppo breve (minimo 10 caratteri).";
        }

        if (empty($famiglia)) {
            $errors['famiglia'] = "Inserire una descrizione della famiglia ideale.";
        } elseif (strlen($famiglia) < 10) {
            $errors['famiglia'] = "La descrizione della famiglia è troppo breve (minimo 10 caratteri).";
        }
        
        // Gestione Foto
        $fotoPath = $NewAnimalValues['ImgPath'] ?? '';

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK && $_FILES['foto']['name'] != "") {
            $path = uploadImage($_FILES['foto'], 'animals');
            if ($path !== null) {
                $fotoPath = $path; 
            } else {
                $errors['generic'] = "Errore nel caricamento dell'immagine. Riprova con un altro file.";
            }
        } 
        elseif (isset($_POST['foto']) && !empty($_POST['foto'])) {
            $fotoPath = $_POST['foto'];
        } 
        if (empty($fotoPath)) {
            $errors['generic'] = "La foto dell'animale è obbligatoria per completare l'inserimento.";
        }
        

        if (empty($errors)) {
            $dataDB = [
                'tipologia' => $tipologia,
                'nome' => mb_convert_case($nome, MB_CASE_TITLE, "UTF-8"),
                'razza' => mb_convert_case($razza, MB_CASE_TITLE, "UTF-8"),
                'taglia' => $taglia,
                'sesso' => $sesso_db,
                'dataNascita' => $dataNascita,
                'pelo' => $pelo,
                'colore' => mb_convert_case($colore, MB_CASE_TITLE, "UTF-8"),
                'carattere' => trim($carattere),      
                'condMediche' => (trim($condMediche) === "" || $condMediche === "0") ? "" : trim($condMediche),                'famiglia' => trim($famiglia),        
                'foto' => $fotoPath,
                'trasporto' => $trasporto
            ];

            $email_loggato = $_SESSION['email'] ?? null; 

            if($isModified){
                $result = $conn->updateAnimal($dataDB, $_GET['id-animale']);
                if($result){
                    unset($_SESSION['form_status_info'], $_SESSION['form_errors_info'], $_SESSION['form_inputs']);
                    header("Location: ./animali?id=".urlencode($_GET['id-animale']));
                    exit;
                }else{
                    $errors['generic']= " La modifica dell'animale non è andato a buon fine, riprovare più tardi.";
                }
            }else{

                $assegna_a_me = isset($_POST['assegna_a_me']);
                $email_da_inserire = $assegna_a_me ? $email_loggato : null;
                $result = $conn->addAnimal($dataDB, $email_da_inserire);
    
                if ($result) {
                    unset($_SESSION['form_status_info'], $_SESSION['form_errors_info'], $_SESSION['form_inputs']);
                    header("Location: ./animali?id=".urlencode($result));
                    exit;
                } else {
                    $errors['generic'] = "Errore database: " . htmlspecialchars($conn->getConnectionError());
                }
            }
            
        }

        $_SESSION['form_status_info'] = 'error';
        $_SESSION['form_errors_info'] = $errors;

        $inputsToSave['Nome'] = $nome;
        $inputsToSave['Tipo'] = $tipologia; 
        $inputsToSave['Razza'] = $razza; 
        $inputsToSave['Taglia'] = $taglia; 
        $inputsToSave['Pelo'] = $pelo; 
        $inputsToSave['DataNascita'] = $dataNascita; 
        $inputsToSave['ImgPath'] = $fotoPath; 
        $inputsToSave['Sesso'] = $sesso_db; 
        $inputsToSave['Colore'] = $colore; 
        $inputsToSave['CondizioniMediche'] = $condMediche; 
        $inputsToSave['DescrComportamentale'] = $carattere; 
        $inputsToSave['DescrFamiglia'] = $famiglia; 
        $inputsToSave['Trasporto'] = $trasporto; 
        $inputsToSave['assegna_a_me'] = isset($_POST['assegna_a_me']) ? 1 : 0;


        $_SESSION['form_inputs'] = $inputsToSave; 

        if($isModified){
            header("Location: ./modifica-animale?id-animale=".urlencode($_GET['id-animale']));
            exit;
        }else{
            header("Location: ./nuovo-animale");
            exit;
        }

    }
    return $message;
}

// CONNESSIONE AL DB
$connessione = new DBAccess();
$messageInfoForm = [];
if ($connessione->openDBConnection()) {

    if($isModified){
        $NewAnimalInfo = $connessione->getAnimalById($idAnimaleMod);
    }
    $messageInfoForm = createInfoAnimale($connessione, $NewAnimalInfo, $isModified);

    $connessione->closeConnection();
} else {
    $messageInfoForm['generic'] = "<p class='error'>Connessione al database fallita, riprovare più tardi.</p>";
}


// COSTRUZIONE PAGINA HTML

$paginaHTML = file_get_contents('./src/template/layout-admin.html');
$main = file_get_contents('./src/template/main/admin/nuovo-animale.html');
$breadcrumb = $isModified? getBreadcrumb('modifica-animale', $pagine) : getBreadcrumb('nuovo-animale', $pagine);
$nav =  $isModified?buildAdminNav($adminMenu,'./modifica-animale'): buildAdminNav($adminMenu,'./nuovo-animale');
$keywords = $isModified? "<meta name='keywords' content='modifica animale'>" : "<meta name='keywords' content='aggiungi animale'>";
$title = $isModified? "<title>Modifica ".$NewAnimalInfo['Nome']." - PetMatch</title>":"<title>Aggiungi un animale - PetMatch</title>";
$description = $isModified? "<meta name='description' content='Modifica un animale al database di PetMatch per aggiornarne la scheda.'>"
                            :"<meta name='description' content='Aggiungi un animale al database di PetMatch per poterlo visualizzare nel sito.'>";

                            
$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);



$campi_errori = ['tipologia', 'nome', 'razza', 'taglia', 'sesso', 'dataNascita', 'pelo', 'colore', 'condMediche', 'carattere', 'famiglia'];
foreach ($campi_errori as $campo) {
    $placeholder = '[errori' . ucfirst($campo) . ']';
    // Se non c'è errore, sostituisce con stringa vuota per "pulire" l'HTML
    $valore_errore = $messageInfoForm[$campo] ?? '';
    $paginaHTML = str_replace($placeholder, $valore_errore, $paginaHTML);
}

$fotoInfo = "";
$inputHiddenFoto = ""; 

if (!empty($NewAnimalInfo['ImgPath'])) {
    $nomeFile = basename($NewAnimalInfo['ImgPath']);
    $fotoInfo = "<p class='success-form'>Immagine già caricata: <strong>$nomeFile</strong></p>";
    $fotoInfo .= "<img src='{$NewAnimalInfo['ImgPath']}' alt='Anteprima immagine caricata' style='max-width:200px;'>";
    $inputHiddenFoto = "<input type='hidden' name='foto' value='{$NewAnimalInfo['ImgPath']}'>";
}

$paginaHTML = str_replace('[infoFotoCaricata]', $fotoInfo, $paginaHTML);
$paginaHTML = str_replace('[input-hidden-foto]', $inputHiddenFoto??'', $paginaHTML);

$paginaHTML = str_replace('[erroriGeneric]', $messageInfoForm['generic'] ?? '', $paginaHTML);

$assegna_val = $NewAnimalInfo['assegna_a_me'] ?? '';
$is_checked = ($assegna_val == 1 || $assegna_val === 'on' || $assegna_val === true) ? 'checked' : '';
$paginaHTML = str_replace('[assegna_checked]', $is_checked, $paginaHTML);

$paginaHTML = str_replace('[sessoM_checked]', ($NewAnimalInfo['Sesso'] === 'M' ? 'checked' : ''), $paginaHTML);
$paginaHTML = str_replace('[sessoF_checked]', ($NewAnimalInfo['Sesso'] === 'F' ? 'checked' : ''), $paginaHTML);

$paginaHTML = str_replace('[tipoCane_checked]', ($NewAnimalInfo['Tipo'] === 'Cane' ? 'checked' : ''), $paginaHTML);
$paginaHTML = str_replace('[tipoGatto_checked]', ($NewAnimalInfo['Tipo'] === 'Gatto' ? 'checked' : ''), $paginaHTML);

$paginaHTML = str_replace('[tagliaVuota_selected]', (empty($NewAnimalInfo['Taglia']) ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[tagliaPiccola_selected]', ($NewAnimalInfo['Taglia'] === 'Piccolo' ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[tagliaMedia_selected]', ($NewAnimalInfo['Taglia'] === 'Medio' ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[tagliaGrande_selected]', ($NewAnimalInfo['Taglia'] === 'Grande' ? 'selected' : ''), $paginaHTML);

$paginaHTML = str_replace('[peloVuoto_selected]', (empty($NewAnimalInfo['Pelo']) ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[peloCorto_selected]', ($NewAnimalInfo['Pelo'] === 'Corto' ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[peloLungo_selected]', ($NewAnimalInfo['Pelo'] === 'Lungo' ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[peloMedio_selected]', ($NewAnimalInfo['Pelo'] === 'Medio' ? 'selected' : ''), $paginaHTML);

//informaizoni diverse a seconda della pagina
if (($NewAnimalInfo['CondizioniMediche'] ?? '') === '0') {
    $NewAnimalInfo['CondizioniMediche'] = '';
}
$paginaHTML = str_replace('[titoloAnimale]', $isModified?'Modifica la scheda di: '.$NewAnimalInfo['Nome']:'Aggiungi animale', $paginaHTML);
$paginaHTML = str_replace('[disabledEdit]', $isModified?'disabled':'', $paginaHTML);

$sessoPlaceholder = $NewAnimalInfo['Sesso']==='M'? '0' : '1';
$tipologiaPlaceholder = $NewAnimalInfo['Tipo']==='Cane'?'0':'1';

$paginaHTML = str_replace('[hiddenPerTipologia]', $isModified?'<input type="hidden" name="tipologia" value="'.$tipologiaPlaceholder.'">':'', $paginaHTML);
$paginaHTML = str_replace('[hiddenPerSesso]', $isModified?'<input type="hidden" name="sesso" value="'.$sessoPlaceholder.'">':'', $paginaHTML);



if($isModified){
    $paginaHTML = str_replace('id="check-assegna-container"', 'id="ModifiedMode"', $paginaHTML);
}

$paginaHTML = str_replace('[trasporto_checked]', ($NewAnimalInfo['Trasporto'] == 1 ? 'checked' : ''), $paginaHTML);

foreach ($NewAnimalInfo as $key => $value) {
    $val = $value ?? ''; 
    $paginaHTML = str_replace('[' . $key . ']', htmlspecialchars($val, ENT_QUOTES, 'UTF-8'), $paginaHTML);
}

echo $paginaHTML;

?>