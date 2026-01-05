<?php 

include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;

$paginaHTML = file_get_contents('./src/template/layout-admin.html');
if ($paginaHTML === false) {
	$paginaHTML = "<p>Errore: template layout-admin.html non trovato o non leggibile.</p>";
}

$title = '<title>Area Admin PetMatch - Nuovo Animale</title>';
$description = '<meta name="description" content="Inserisci un nuovo animale nel database di PetMatch.">';

$messaggiPerForm = "";

$nome = '';
$dataNascita = ''; 
$razza = '';
$pelo = ''; 
$taglia = ''; 
$colore = '';
$sesso = '';
$condizioni = '';
$carattere = '';
$famigliaIdeale = '';
$foto = null;


$tagPermessi ='<em><strong><ul><li>';


function pulisciInput($value){
 	$value = trim($value);
  	$value = strip_tags($value);

	$value = htmlentities($value);
  	return $value;
}

function pulisciNote($value){
	global $tagPermessi;
 	$value = trim($value);
  	$value = strip_tags($value,$tagPermessi);
  	return $value;
}


if (isset($_POST['submit'])) {

	$nome = pulisciInput($_POST['nome']);
	if (strlen($nome)==0) {
		$messaggiPerForm .= "Inserire il nome";
	} else {
		if(preg_match("/\d/", $nome)) {
			$messaggiPerForm .= "Nome non può contenere numeri";
		}
	}
	
    $dataNascita = pulisciInput($_POST['dataNascita']);
	if(strlen($dataNascita)==0){
		$messaggiPerForm .= "Inserire la data di nascita";
	}else{
		if(!preg_match("/^\d{4}-\d{2}-\d{2}$/", $dataNascita)){
			$messaggiPerForm .= "La data di nascita deve essere nel formato AAAA-MM-GG";
		}
        
        $d = DateTime::createFromFormat('Y-m-d', $dataNascita);
        if (!$d || $d->format('Y-m-d') !== $dataNascita) {
            $messaggiPerForm .= "Data non valida o inesistente";
        } else {
            $oggi = new DateTime();
            if ($d > $oggi) {
                $messaggiPerForm .= "La data di nascita non può essere nel futuro";
            }
        }
	}

    $razza = pulisciInput($_POST['razza']);
    if (strlen($razza)==0) {
        $messaggiPerForm .= "Inserire la razza";
    } else {
        if(preg_match("/\d/", $razza)) {
            $messaggiPerForm .= "La razza non può contenere numeri";
        }
    }

    $pelo = pulisciInput($_POST['pelo']);
    if ($pelo !== 'Corto' && $pelo !== 'Lungo' && $pelo !== 'Misto') {
        $messaggiPerForm .= "Selezione tipo di pelo non valida (tentata manipolazione).";
    }

    $taglia = pulisciInput($_POST['taglia']);
    if ($taglia !== 'Piccola' && $taglia !== 'Media' && $taglia !== 'Grande') {
        $messaggiPerForm .= "Selezione taglia non valida (tentata manipolazione).";
    }

    $colore = pulisciInput($_POST['colore']);
    if (strlen($colore)==0) {
        $messaggiPerForm .= "Inserire il colore";
    } else {
        if(preg_match("/\d/", $colore)) {
            $messaggiPerForm .= "Il colore non può contenere numeri";
        }
    }

	$sesso = pulisciInput($_POST['sesso']);
    if ($sesso !== 'Maschio' && $sesso !== 'Femmina') {
        $messaggiPerForm .= "Selezione sesso non valida (tentata manipolazione).";
    }

	$condizioni = pulisciNote($_POST['condizioni']);
    $carattere = pulisciNote($_POST['carattere']);
    $famigliaIdeale = pulisciNote($_POST['famigliaIdeale']);  

    $foto = $_FILES['foto'];
    if($_FILES['foto']['error'] === UPLOAD_ERR_OK) {

        $nomeFile = basename($_FILES['foto']['name']);
        $destinazione = '../../assets/images/animals/' . $nomeFile;

        if (!move_uploaded_file($_FILES['foto']['tmp_name'], $destinazione)) {
            echo "Errore nel salvataggio";
    }

	if($messaggiPerForm == ""){
    
    $connessione = new DBAccess();
    $connessioneOK = $connessione->openDBConnection();

    if($connessioneOK){
	    $resultInsert = $connessione->insertNewElement($nome, $capitano, $dataNascita, $luogo, $squadra, $ruolo, $altezza, $maglia, $magliaNazionale, $punti, $riconoscimenti, $note, $genere);
        if($resultInsert){
            $messaggiPerForm = "<div id=\"greetings\"><p>Animale inserito correttamente.</p></div>";
        } else {
            $messaggiPerForm = "<div id=\"messageErrors\"><p>Animale non inserito. Controlla i dati.</p></div>";
        }
    } else {
        $messaggiPerForm = "<div id=\"messageErrors\"><p>Errore nella connessione al database.</p></div>";
    }
}

}


$nome = '';
$dataNascita = ''; 
$razza = '';
$pelo = ''; 
$taglia = ''; 
$colore = '';
$sesso = '';
$condizioni = '';
$carattere = '';
$famigliaIdeale = '';
$foto = null;


$main = file_get_contents('./src/template/main/admin/nuovo-animale.html');
$main = str_replace('[messaggiForm]', $messaggiPerForm, $main);
$main = str_replace('[valoreNome]', $nome, $main);
$main = str_replace('[valData]', $dataNascita, $main);
$main = str_replace('[valRazza]', $razza, $main);
$main = str_replace('[valorePelo]', $pelo, $main);
$main = str_replace('[valoreTaglia]', $taglia, $main);
$main = str_replace('[valoreColore]', $colore, $main);
$main = str_replace('[valoreSesso]', $sesso, $main);
$main = str_replace('[valoreCondizioni]', $condizioni, $main);
$main = str_replace('[valoreCarattere]', $carattere, $main);
$main = str_replace('[valoreFamigliaIdeale]', $famigliaIdeale, $main);

/**All'invio i dati rimangono nel form (così l'utente non deve riscriverli se sbaglia!) */

echo $paginaHTML;

?>

