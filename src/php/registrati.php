<?php
include './src/utils.php';

$paginaHTML = file_get_contents('./src/template/layout.html');
if ($paginaHTML === false) {
	$paginaHTML = "<p>Errore: template layout.html non trovato o non leggibile.</p>";
}

$title = '<title>Registrati - PetMatch </title>';
$description = '<meta name="description" content="Registrati su PetMatch">';
$keywords = "";

$nav = buildUserNav($userMenu, './registrati');

$footer = file_get_contents('./src/template/partials/footer.html');

$breadcrumb = getBreadcrumb('registrati', $pagine);

$main = file_get_contents('./src/template/main/registrati.html');

$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

echo $paginaHTML;
?>