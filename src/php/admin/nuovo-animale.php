<?php
include './src/utils.php';
include './src/DBconnection.php';

use DB\DBAccess;

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) { //il primo controlla se esiste la variabile admin in session, la seconda controlla che sia affettivamente admin
    header("Location: ./accedi");
    exit;
}

$NewAnimalInfo = [
    'tipologia' => '', 'nome' => '', 'razza' => '', 'taglia' => '',
    'sesso' => '', 'foto' => '', 'dataNascita' => '', 'pelo' => '',
    'colore' => '', 'condMediche' => '', 'carattere' => '', 'famiglia' => '',
    'trasporto' => '', 'createMore'=>'' // Corretto refuso 'trasposrto'
];

function createInfoAnimale(DBAccess $conn, &$NewAnimalValues): array {
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
        $fotoPath = $NewAnimalValues['foto'] ?? ''; 
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK && $_FILES['foto']['name'] != "") {
            $path = uploadImage($_FILES['foto'], 'animals');
            if ($path !== null) {
                $fotoPath = $path; 
            } else {
                $errors['generic'] = "Errore nel caricamento dell'immagine. Riprova con un altro file.";
            }
        } 
        elseif (empty($fotoPath)) {
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
                'condMediche' => trim($condMediche),  
                'famiglia' => trim($famiglia),        
                'foto' => $fotoPath,
                'trasporto' => $trasporto
            ];

            // RECUPERO EMAIL DALLA SESSIONE
            $email_admin = $_SESSION['email'] ?? null; 

            if ($email_admin) {
                $result = $conn->addAnimal($dataDB, $email_admin);
                if ($result) {
                    unset($_SESSION['form_status_info']);
                    unset($_SESSION['form_errors_info']);
                    unset($_SESSION['form_inputs']);

                    if($createMoreValue){
                        header("Location: ./nuovo-animale?createMore=1");
                    }else{
                        header("Location: ./area-riservata?success=1");
                    }
                    exit;
                } else {
                    $errors['generic'] = "Errore database: " . htmlspecialchars($conn->getConnectionError());                }
            } else {
                $errors['generic'] = "Errore: Sessione amministratore non trovata. Effettua il login.";
            }
        }

        $_SESSION['form_status_info'] = 'error';
        $_SESSION['form_errors_info'] = $errors;

        $inputsToSave = $_POST;
        $inputsToSave['foto'] = $fotoPath; 
        $_SESSION['form_inputs'] = $inputsToSave; 

        header("Location: ./nuovo-animale");
        exit;
    }
    return $message;
}

// CONNESSIONE AL DB
$connessione = new DBAccess();
$messageInfoForm = [];
if ($connessione->openDBConnection()) {
    $messageInfoForm = createInfoAnimale($connessione, $NewAnimalInfo);
    $connessione->closeConnection();
} else {
    $messageInfoForm['generic'] = "<p class='error'>Connessione al database fallita, riprovare più tardi.</p>";
}


// COSTRUZIONE PAGINA HTML
$paginaHTML = file_get_contents('./src/template/layout-admin.html');
$main = file_get_contents('./src/template/main/admin/nuovo-animale.html');
$breadcrumb = getBreadcrumb('nuovo-animale', $pagine);
$nav = buildAdminNav($adminMenu,'./nuovo-animale');
$keywords = "<meta name='keywords' content='aggiungi, animale, adozione, amministratore'>";
$title = "<title>Aggiungi un animale - PetMatch</title>";
$description = "<meta name='description' content='Aggiungi un animale al database di PetMatch per poterlo visualizzare nel sito.'>";

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

if (!empty($NewAnimalInfo['foto'])) {
    $nomeFile = basename($NewAnimalInfo['foto']);
    $fotoInfo = "<p class='success-form'>Immagine già caricata: <strong>$nomeFile</strong></p>";
    $fotoInfo .= "<img src='{$NewAnimalInfo['foto']}' alt='Anteprima immagine caricata' style='max-width:200px;'>";
    $inputHiddenFoto = "<input type='hidden' name='foto' value='{$NewAnimalInfo['foto']}'>";
}

$paginaHTML = str_replace('[infoFotoCaricata]', $fotoInfo, $paginaHTML);
$paginaHTML = str_replace('[input-hidden-foto]', $inputHiddenFoto, $paginaHTML);

$paginaHTML = str_replace('[erroriGeneric]', $messageInfoForm['generic'] ?? '', $paginaHTML);

$paginaHTML = str_replace('[sessoM_checked]', ($NewAnimalInfo['sesso'] === '0' ? 'checked="checked"' : ''), $paginaHTML);
$paginaHTML = str_replace('[sessoF_checked]', ($NewAnimalInfo['sesso'] === '1' ? 'checked="checked"' : ''), $paginaHTML);

$paginaHTML = str_replace('[tipoCane_checked]', ($NewAnimalInfo['tipologia'] === '0' ? 'checked="checked"' : ''), $paginaHTML);
$paginaHTML = str_replace('[tipoGatto_checked]', ($NewAnimalInfo['tipologia'] === '1' ? 'checked="checked"' : ''), $paginaHTML);

$paginaHTML = str_replace('[tagliaVuota_selected]', (empty($NewAnimalInfo['taglia']) ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[tagliaPiccola_selected]', ($NewAnimalInfo['taglia'] === 'Piccolo' ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[tagliaMedia_selected]', ($NewAnimalInfo['taglia'] === 'Medio' ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[tagliaGrande_selected]', ($NewAnimalInfo['taglia'] === 'Grande' ? 'selected' : ''), $paginaHTML);

$paginaHTML = str_replace('[peloVuoto_selected]', (empty($NewAnimalInfo['pelo']) ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[peloCorto_selected]', ($NewAnimalInfo['pelo'] === 'Corto' ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[peloLungo_selected]', ($NewAnimalInfo['pelo'] === 'Lungo' ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[peloMedio_selected]', ($NewAnimalInfo['pelo'] === 'Medio' ? 'selected' : ''), $paginaHTML);

$trasporto_val = $NewAnimalInfo['trasporto'];
$paginaHTML = str_replace('[trasporto_checked]', ($trasporto_val == 1 || $trasporto_val === 'on' ? 'checked="checked"' : ''), $paginaHTML);

if(isset($_GET['createMore']) && $_GET['createMore'] == 1){
    $paginaHTML = str_replace('[checkCreateMore]', 'checked', $paginaHTML);
}else{
    $paginaHTML = str_replace('[checkCreateMore]', ($NewAnimalInfo['createMore']? 'checked':''), $paginaHTML);
}

foreach ($NewAnimalInfo as $key => $value) {
    $paginaHTML = str_replace('[' . $key . ']', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $paginaHTML);
}

echo $paginaHTML;

?>
