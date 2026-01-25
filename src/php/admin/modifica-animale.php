<?php
include './src/utils.php';
include './src/DBconnection.php';

use DB\DBAccess;

$idAnimale = filter_var($_GET['id'], FILTER_VALIDATE_INT);
if (!$idAnimale) {
    header("Location: ./area-riservata");
    exit;
}

$AnimalInfo = [
    'idAnimale' => $idAnimale, 'tipologia' => '', 'nome' => '', 'razza' => '', 'taglia' => '',
    'sesso' => '', 'foto' => '', 'dataNascita' => '', 'pelo' => '',
    'colore' => '', 'condMediche' => '', 'carattere' => '', 'famiglia' => '',
    'trasporto' => ''
];

function handleEditAnimal(DBAccess $conn, &$AnimalValues, $idAnimale): array {
    $message = [
        'generic' => '', 'tipologia' => '', 'nome' => '', 'razza' => '',
        'taglia' => '', 'sesso' => '', 'dataNascita' => '', 'colore' => '',
        'pelo' => '', 'condMediche' => '', 'carattere' => '', 'famiglia' => '',
        'trasporto' => ''
    ];

    $currentData = $conn->getAnimalById($idAnimale);
    if (!$currentData) {
        header("Location: ./area-riservata?error=notfound");
        exit;
    }

    foreach ($currentData as $key => $val) {
        if (array_key_exists($key, $AnimalValues)) {
            $AnimalValues[$key] = $val;
        }
    }
    $AnimalValues['sesso'] = ($currentData['sesso'] === 'M') ? '0' : '1';
    $AnimalValues['tipologia'] = ($currentData['tipologia'] === 'Cane') ? '0' : '1';

    if (isset($_SESSION['form_status_info']) && $_SESSION['form_status_info'] === 'error') {
        $savedErrors = $_SESSION['form_errors_info'] ?? [];
        foreach ($savedErrors as $key => $val) {
            $message[$key] = ($key === 'generic') ? $val : "<p class='error-form'>$val</p>";
        }
        $savedInputs = $_SESSION['form_inputs'] ?? [];
        foreach ($AnimalValues as $key => $val) {
            if(isset($savedInputs[$key])) $AnimalValues[$key] = $savedInputs[$key];
        }
        unset($_SESSION['form_status_info'], $_SESSION['form_errors_info'], $_SESSION['form_inputs']);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-edit-animal'])) { 
        $errors = [];
        
        $tipologia = $currentData['tipologia'];
        $sesso_db = $currentData['sesso'];

        $nome = trim($_POST['nome'] ?? '');
        $razza = trim($_POST['razza'] ?? '');
        $taglia = trim($_POST['taglia'] ?? '');
        $colore = trim($_POST['colore'] ?? '');
        $dataNascita = trim($_POST['dataNascita'] ?? '');
        $pelo = trim($_POST['pelo'] ?? '');
        $carattere = trim($_POST['carattere'] ?? '');
        $condMediche = trim($_POST['condMediche'] ?? '');
        $famiglia = trim($_POST['famiglia'] ?? '');
        $trasporto = isset($_POST['trasporto']) ? 1 : 0;

        $regexData = '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/'; 
        $regexTxt = '/^[a-zA-Z\x{00C0}-\x{017F}\s\',]+$/u';        
        
        if (strlen($nome) < 2 || !preg_match($regexTxt, $nome)) $errors['nome'] = "Nome non valido.";
        if (strlen($razza) < 2 || !preg_match($regexTxt, $razza)) $errors['razza'] = "Razza non valida.";
        if (strlen($colore) < 2 || !preg_match($regexTxt, $colore)) $errors['colore'] = "Colore non valido.";
        if (!in_array($taglia, ['Piccolo', 'Medio', 'Grande'])) $errors['taglia'] = "Seleziona una taglia.";
        if (!preg_match($regexData, $dataNascita)) $errors['dataNascita'] = "Data non valida.";
        if (!in_array($pelo, ['Lungo', 'Corto', 'Medio'])) $errors['pelo'] = "Seleziona tipo pelo.";
        
        $fotoPath = $currentData['foto']; 
        if(isset($_FILES['foto']) && $_FILES['foto']['name'] != "") {
            $path = uploadImage($_FILES['foto'], 'animals');
            if ($path !== null) {
                $fotoPath = $path;
            } else {
                $errors['generic'] = "Errore nel caricamento della nuova immagine.";
            }
        }

        if (empty($errors)) {
            $dataUpdate = [
                'id' => $idAnimale,
                'nome' => mb_convert_case($nome, MB_CASE_TITLE, "UTF-8"),
                'razza' => mb_convert_case($razza, MB_CASE_TITLE, "UTF-8"),
                'taglia' => $taglia,
                'dataNascita' => $dataNascita,
                'pelo' => $pelo,
                'colore' => mb_convert_case($colore, MB_CASE_TITLE, "UTF-8"),
                'carattere' => trim($carattere),      
                'condMediche' => trim($condMediche),  
                'famiglia' => trim($famiglia),        
                'foto' => $fotoPath,
                'trasporto' => $trasporto
            ];

            if ($conn->updateAnimal($dataUpdate)) {
                header("Location: ./area-riservata?success=edit");
                exit;
            } else {
                $errors['generic'] = "Errore durante l'aggiornamento: " . htmlspecialchars($conn->getConnectionError());
            }
        }

        $_SESSION['form_status_info'] = 'error';
        $_SESSION['form_errors_info'] = $errors;
        $_SESSION['form_inputs'] = $_POST; 
        header("Location: ./modifica-animale.php?id=$idAnimale");
        exit;
    }
    return $message;
}

$connessione = new DBAccess();
$messageInfoForm = [];
if ($connessione->openDBConnection()) {
    $messageInfoForm = handleEditAnimal($connessione, $AnimalInfo, $idAnimale);
    $connessione->closeConnection();
}

// ... (codice precedente dopo la chiusura di handleEditAnimal)

// COSTRUZIONE PAGINA HTML
$paginaHTML = file_get_contents('./src/template/layout-admin.html');
$main = file_get_contents('./src/template/main/admin/modifica-animale.html'); // Il nuovo template
$breadcrumb = getBreadcrumb('modifica-animale', $pagine); // Assicurati che 'modifica-animale' esista in $pagine
$nav = buildAdminNav($adminMenu, './area-riservata'); // Evidenziamo l'area riservata
$keywords = "<meta name='keywords' content='modifica, animale, adozione, gestione'>";
$title = "<title>Modifica Animale - PetMatch</title>";
$description = "<meta name='description' content='Pagina di modifica per le informazioni dell'animale selezionato.'>";

$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);

// Sostituzione ERRORI
$campi_errori = ['tipologia', 'nome', 'razza', 'taglia', 'sesso', 'dataNascita', 'pelo', 'colore', 'condMediche', 'carattere', 'famiglia'];
foreach ($campi_errori as $campo) {
    $placeholder = '[errori' . ucfirst($campo) . ']';
    $valore_errore = $messageInfoForm[$campo] ?? '';
    $paginaHTML = str_replace($placeholder, $valore_errore, $paginaHTML);
}
$paginaHTML = str_replace('[erroriGeneric]', $messageInfoForm['generic'] ?? '', $paginaHTML);

// Gestione dell'immagine attuale
$fotoInfo = "";
if (!empty($AnimalInfo['foto'])) {
    $nomeFile = basename($AnimalInfo['foto']);
    $fotoInfo = "File attuale: <strong>$nomeFile</strong>";
}
$paginaHTML = str_replace('[nomeFotoAttuale]', $fotoInfo, $paginaHTML);

$paginaHTML = str_replace('[sessoM_checked]', ($AnimalInfo['sesso'] === '0' ? 'checked="checked"' : ''), $paginaHTML);
$paginaHTML = str_replace('[sessoF_checked]', ($AnimalInfo['sesso'] === '1' ? 'checked="checked"' : ''), $paginaHTML);
$paginaHTML = str_replace('[valoreSesso]', $AnimalInfo['sesso'], $paginaHTML); // Per l'input hidden

$paginaHTML = str_replace('[tipoCane_checked]', ($AnimalInfo['tipologia'] === '0' ? 'checked="checked"' : ''), $paginaHTML);
$paginaHTML = str_replace('[tipoGatto_checked]', ($AnimalInfo['tipologia'] === '1' ? 'checked="checked"' : ''), $paginaHTML);
$paginaHTML = str_replace('[valoreTipologia]', $AnimalInfo['tipologia'], $paginaHTML); // Per l'input hidden

$paginaHTML = str_replace('[tagliaPiccola_selected]', ($AnimalInfo['taglia'] === 'Piccolo' ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[tagliaMedia_selected]', ($AnimalInfo['taglia'] === 'Medio' ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[tagliaGrande_selected]', ($AnimalInfo['taglia'] === 'Grande' ? 'selected' : ''), $paginaHTML);

$paginaHTML = str_replace('[peloCorto_selected]', ($AnimalInfo['pelo'] === 'Corto' ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[peloLungo_selected]', ($AnimalInfo['pelo'] === 'Lungo' ? 'selected' : ''), $paginaHTML);
$paginaHTML = str_replace('[peloMedio_selected]', ($AnimalInfo['pelo'] === 'Medio' ? 'selected' : ''), $paginaHTML);

$trasporto_val = $AnimalInfo['trasporto'];
$paginaHTML = str_replace('[trasporto_checked]', ($trasporto_val == 1 ? 'checked="checked"' : ''), $paginaHTML);

foreach ($AnimalInfo as $key => $value) {
    $paginaHTML = str_replace('[' . $key . ']', htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'), $paginaHTML);
}

echo $paginaHTML;
?>