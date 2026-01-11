<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;

// if (!isset($_SESSION['loggato']) || $_SESSION['loggato'] !== true || !isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
//     header("Location: ./home");
//     exit;    
// }

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

function createInfoAnimale(DBAccess $conn, &$NewAnimalValues): array {
	
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
        
        $tipologia   = trim($_POST['new-tipologia'] ?? '');
        $nome        = trim($_POST['nome'] ?? '');
        $razza       = trim($_POST['new-razza'] ?? '');
        $taglia      = trim($_POST['new-taglia'] ?? '');
        $sesso       = trim($_POST['new-sesso'] ?? '');
        $dataNascita = trim($_POST['new-dataNascita'] ?? '');
        $pelo        = trim($_POST['new-pelo'] ?? '');
        $colore      = trim($_POST['new-colore'] ?? '');
        $carattere   = trim($_POST['new-carattere'] ?? '');
        $condMediche = trim($_POST['new-condMediche'] ?? '');
        $famiglia    = trim($_POST['new-famiglia'] ?? '');

        $regexData = '/^\d{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/'; 
        $regexTxt = '/^[a-zA-Z\x{00C0}-\x{017F}]+(?:[\'\s][a-zA-Z\x{00C0}-\x{017F}]+)*$/u';

        if (!in_array($tipologia, ['Cane', 'Gatto'])) $errors['tipologia'] = "Tipologia non valida.";
        if (strlen($nome) < 2 || !preg_match($regexTxt, $nome)) $errors['nome'] = "Nome non valido.";
        if (strlen($razza) < 2 || !preg_match($regexTxt, $razza)) $errors['razza'] = "Razza non valida.";
        if (!in_array($taglia, ['Piccola', 'Media', 'Grande'])) $errors['taglia'] = "Seleziona una taglia.";
        if (!in_array($sesso, ['Maschio', 'Femmina'])) $errors['sesso'] = "Seleziona il sesso.";
        if (!preg_match($regexData, $dataNascita)) $errors['dataNascita'] = "Data non valida.";
        if (!in_array($pelo, ['Lungo', 'Corto', 'Misto'])) $errors['pelo'] = "Seleziona tipo pelo.";
        
        $fotoPath = null;
        if(isset($_FILES['new-foto']) && $_FILES['new-foto']['name'] != "") {
            $path = uploadImage($_FILES['new-foto'], 'animals');
            if ($path === null) {
                $errors['foto'] = "Immagine non valida o troppo grande.";
            } else {
                $fotoPath = $path;
            }
        }

        if (empty($errors)) {
            $dataDB = [
                'tipologia' => $tipologia,
                'nome' => mb_convert_case($nome, MB_CASE_TITLE, "UTF-8"),
                'razza' => mb_convert_case($razza, MB_CASE_TITLE, "UTF-8"),
                'taglia' => $taglia,
                'sesso' => $sesso,
                'dataNascita' => $dataNascita,
                'pelo' => $pelo,
                'colore' => mb_convert_case($colore, MB_CASE_TITLE, "UTF-8"),
                'carattere' => $carattere,
                'condMediche' => $condMediche,
                'famiglia' => $famiglia,
                'foto' => $fotoPath
            ];

            if ($conn->addAnimal($dataDB)) {
                // svuota eventuali sessioni vecchie e vai alla lista animali
                unset($_SESSION['form_inputs'], $_SESSION['form_errors_info']);
                header("Location: ./area-riservata?success=1");
                exit;
            } else {
                $errors['generic'] = "Errore durante l'inserimento nel database.";
            }
        }

        $_SESSION['form_status_info'] = 'error';
        $_SESSION['form_errors_info'] = $errors;
        $_SESSION['form_inputs'] = $_POST; 
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

foreach ($NewAnimalInfo as $key => $value) {
    $paginaHTML = str_replace('[' . $key . ']', htmlspecialchars($value, ENT_QUOTES, 'UTF-8'), $paginaHTML);
}

echo $paginaHTML;

?>
