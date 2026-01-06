<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;
session_start();

if (!isset($_SESSION['loggato']) || $_SESSION['loggato'] !== true) {
    header("Location: ./registrati");
    exit;    
}

$paginaHTML = file_get_contents('./src/template/layout.html');
if ($paginaHTML === false) {
    $paginaHTML = "<p class='error-form'>Errore: template layout.html non trovato o non leggibile.</p>";
}

$prova = "<h1>Benvenuto, " . $_SESSION['email'] . "!</h1>
            <p>Questa è l'area riservata.</p>";

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
$paginaHTML = str_replace('[main]', $prova, $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

echo $paginaHTML;

?>