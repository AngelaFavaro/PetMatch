<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;

$NewAnimalInfo = [
    'tipologia' => '', 'nome' => '', 'razza' => '', 'taglia' => '',
    'sesso' => '', 'foto' => '', 'dataNascita' => '', 'pelo' => '',
    'colore' => '', 'condMediche' => '', 'carattere' => '', 'famiglia' => '',
    'trasporto' => '' // Corretto refuso 'trasposrto'
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
        foreach ($NewAnimalValues as $key => $val) {
            $NewAnimalValues[$key] = $savedInputs[$key] ?? '';
        }
        unset($_SESSION['form_status_info'], $_SESSION['form_errors_info'], $_SESSION['form_inputs']);
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
        $sesso_val = $_POST['sesso'] ?? '';
        $sesso_db = ($sesso_val === '0') ? 'M' : (($sesso_val === '1') ? 'F' : '');
        $sesso_txt = ($sesso_val === '0') ? 'Maschio' : (($sesso_val === '1') ? 'Femmina' : '');

        $regexData = '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/'; 
        $regexTxt = '/^[a-zA-Z\x{00C0}-\x{017F}]+(?:[\'\s][a-zA-Z\x{00C0}-\x{017F}]+)*$/u';

        // Validazione
        if (empty($tipologia)) $errors['tipologia'] = "Seleziona una tipologia.";
        if (strlen($nome) < 2 || !preg_match($regexTxt, $nome)) $errors['nome'] = "Nome non valido.";
        if (strlen($razza) < 2 || !preg_match($regexTxt, $razza)) $errors['razza'] = "Razza non valida.";
        if (strlen($colore) < 2 || !preg_match($regexTxt, $colore)) $errors['colore'] = "Colore non valido.";
        if (!in_array($taglia, ['Piccola', 'Media', 'Grande'])) $errors['taglia'] = "Seleziona una taglia.";
        if (!in_array($sesso_txt, ['Maschio', 'Femmina'])) $errors['sesso'] = "Seleziona il sesso.";
        if (!preg_match($regexData, $dataNascita)) $errors['dataNascita'] = "Data non valida.";
        if (!in_array($pelo, ['Lungo', 'Corto', 'Misto'])) $errors['pelo'] = "Seleziona tipo pelo.";
        
        // Gestione Foto
        $fotoPath = (!empty($NewAnimalValues['foto'])) ? $NewAnimalValues['foto'] : '../../assets/images/animals/default.png';
        if(isset($_FILES['foto']) && $_FILES['foto']['name'] != "") {
            $path = uploadImage($_FILES['foto'], 'animals');
            if ($path !== null) {
                $fotoPath = $path;
            }
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
                'carattere' => $carattere,
                'condMediche' => $condMediche,
                'famiglia' => $famiglia,
                'foto' => $fotoPath,
                'trasporto' => $trasporto
            ];

            // RECUPERO EMAIL DALLA SESSIONE
            $email_admin = $_SESSION['email'] ?? null; 

            if ($email_admin) {
                if ($conn->addAnimal($dataDB, $email_admin)) {
                    unset($_SESSION['form_inputs'], $_SESSION['form_errors_info']);
                    header("Location: ./area-riservata?success=1");
                    exit;
                } else {
                    $errors['generic'] = "Errore durante l'inserimento nel database.";
                }
            } else {
                $errors['generic'] = "Errore: Sessione amministratore non trovata. Effettua il login.";
            }
        }

        $_SESSION['form_status_info'] = 'error';
        $_SESSION['form_errors_info'] = $errors;
        $_SESSION['form_inputs'] = $_POST; 
        $inputsToSave = $_POST;
        $inputsToSave['foto'] = $fotoPath; // Fondamentale: salviamo il path della foto caricata
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
    $messageInfoForm['generic'] = "<p class='error'>Connessione al database fallita.</p>";
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
if (!empty($NewAnimalInfo['foto']) && $NewAnimalInfo['foto'] !== '../../assets/images/animals/default.png') {
    $nomeFile = basename($NewAnimalInfo['foto']);
    $fotoInfo = "<p class='success-form'>Foto già caricata, correggi i dati, non serve caricarla nuovamente: <strong>$nomeFile</strong></p>";
    $fotoInfo .= "<img src='{$NewAnimalInfo['foto']}' alt='Anteprima' style='width:100px; height:auto; display:block; margin-top:5px;'>";
}
$paginaHTML = str_replace('[infoFotoCaricata]', $fotoInfo, $paginaHTML);

$paginaHTML = str_replace('[erroriGeneric]', $messageInfoForm['generic'] ?? '', $paginaHTML);

$paginaHTML = str_replace('[sessoM_checked]', ($NewAnimalInfo['sesso'] === '0' ? 'checked="checked"' : ''), $paginaHTML);
$paginaHTML = str_replace('[sessoF_checked]', ($NewAnimalInfo['sesso'] === '1' ? 'checked="checked"' : ''), $paginaHTML);

$paginaHTML = str_replace('[tipoCane_checked]', ($NewAnimalInfo['tipologia'] === '0' ? 'checked="checked"' : ''), $paginaHTML);
$paginaHTML = str_replace('[tipoGatto_checked]', ($NewAnimalInfo['tipologia'] === '1' ? 'checked="checked"' : ''), $paginaHTML);

$paginaHTML = str_replace('[tagliaVuota_selected]', (empty($NewAnimalInfo['taglia']) ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[tagliaPiccola_selected]', ($NewAnimalInfo['taglia'] === 'Piccola' ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[tagliaMedia_selected]', ($NewAnimalInfo['taglia'] === 'Media' ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[tagliaGrande_selected]', ($NewAnimalInfo['taglia'] === 'Grande' ? 'selected' : ''), $paginaHTML);

$paginaHTML = str_replace('[peloVuoto_selected]', (empty($NewAnimalInfo['pelo']) ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[peloCorto_selected]', ($NewAnimalInfo['pelo'] === 'Corto' ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[peloLungo_selected]', ($NewAnimalInfo['pelo'] === 'Lungo' ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[peloMisto_selected]', ($NewAnimalInfo['pelo'] === 'Misto' ? 'selected' : ''), $paginaHTML);

$trasporto_val = $NewAnimalInfo['trasporto'];
$paginaHTML = str_replace('[trasporto_checked]', ($trasporto_val == 1 || $trasporto_val === 'on' ? 'checked="checked"' : ''), $paginaHTML);

foreach ($NewAnimalInfo as $key => $value) {
    $paginaHTML = str_replace('[' . $key . ']', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $paginaHTML);
}

echo $paginaHTML;

?>
