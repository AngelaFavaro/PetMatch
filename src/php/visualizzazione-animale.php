<?php
include './src/utils.php';
include './src/DBconnection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use DB\DBAccess;

$idAnimale = $_GET['id'] ?? null;


$utenteAccesso = isset($_SESSION['email']);
$emailUtente = $utenteAccesso ? $_SESSION['email'] : null;

$richiesta = null;
$connection = new DBAccess();
if ($connection->openDBConnection()){
if ($utenteAccesso) {
    $richiesta=$connection->getRequestStatus($emailUtente, $idAnimale);
}
$connection->closeDBConnection();
}

if (!$utenteAccesso) {
        $statoPagina = file_get_contents('./src/template/partials/animale-no-accesso.html');
} else if ($richiesta === false || $richiesta === null) {
        $statoPagina = file_get_contents('./src/template/partials/animale-form.html');
} else {
    switch ($richiesta['Stato']) {
        case 'Nuova':
        $statoPagina = file_get_contents('./src/template/partials/animale-pendente.html');
            break;
        case 'In valutazione':
        $statoPagina = file_get_contents('./src/template/partials/animale-pendente.html');
            break;
        case 'Da trasportare':
        $statoPagina = file_get_contents('./src/template/partials/animale-in-trasporto.html');
            break;
        case 'Respinta':
        $statoPagina = file_get_contents('./src/template/partials/animale-negata.html');
            break;
        case 'Annullata':
        $statoPagina = file_get_contents('./src/template/partials/animale-form.html');
            break;
        case 'Accettata':
        $statoPagina = file_get_contents('./src/template/partials/animale-in-trasporto.html');
            break;
        default:
        $statoPagina = file_get_contents('./src/template/partials/animale-form.html');
    }
}

/*if ($idAnimale) {
    $stmt = $pdo->prepare("SELECT nome FROM animali WHERE id = ?");
    $stmt->execute([$idAnimale]);
    $animale = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($animale) {
        $nomeAnimale = $animale['nome'];
    }
} */




/*

$stmt = $pdo->prepare("
    SELECT *
    FROM ANIMALI
    WHERE IDanimale = ?");
$stmt->execute([$idAnimale]);
$animale = $stmt->fetch(PDO::FETCH_ASSOC);


$nomeAnimale = $animale['Nome'];
//età
$sesso= $animale['Sesso'];
$colore = $animale['Colore'];
$pelo = $animale['Pelo'];
$taglia = $animale['Taglia'];
$razza = $animale['Razza'];
$famiglia = $animale['DescrFamiglia'];
$comportamento = $animale['DescrComportamentale'];
$condizioniMediche= $animale['CondizioniMediche'];
$trasporto = $animale['Trasporto'];
$img= $animale['Immagine']; */


$paginaHTML = file_get_contents('./src/template/layout.html');


$title = "<title>nomeAnimale - PetMatch</title>"; //mettere $ in nomeAnimale qui e nelle 2 righe sotto 
$description = "<meta name='description' content='Scheda di nomeAnimale disponibile per adozione'>";
$keywords = "<meta name='keywords' content='nome, adozione, PetMatch, razza'>";
$breadcrumb = getBreadcrumb('visualizzazione-animale', $pagine);

$nav = buildUserNav($userMenu, './visualizzazione-animale', $_SESSION['email'] ?? false);
$main = file_get_contents('./src/template/main/visualizzazione-animale.html');
$footer = file_get_contents('./src/template/partials/footer.html');

$main = str_replace('[ADOZIONE_STATUS]', $statoPagina, $main);




/* replace */
$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

echo $paginaHTML;

?>
