<?php
include './src/utils.php';
include './src/DBconnection.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use DB\DBAccess;


$paginaHTML = file_get_contents('./src/template/layout.html');
$main = file_get_contents('./src/template/main/eventi.html');
$footer = buildFooter($footerMenu,  './eventi');



// $main = str_replace('[LINKPAGINE]', $linkPagine, $main);

// $main = str_replace('[URL-RESERFILTRI]', $resetUrl, $main);
// $main = str_replace('[VISIBILITA-FILTRO]', $cancelFiltriId, $main);

$title = '<title>Eventi - PetMatch</title>';
$description = '<meta name="description" content="Eventi prossimi qui da PetMatch!">';
$keywords = '';

$nav = buildUserNav($userMenu, './eventi', $_SESSION['email'] ?? false);
$breadcrumb = getBreadcrumb('eventi', $pagine);

$paginaHTML = str_replace(
    ['[title]', '[description]', '[keywords]', '[breadcrumb]', '[nav]', '[main]', '[footer]'],
    [$title, $description, $keywords, $breadcrumb, $nav, $main, $footer],
    $paginaHTML
);

echo $paginaHTML;
?>