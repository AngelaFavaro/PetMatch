<?php
include './src/utils.php';
$paginaHTML = file_get_contents('./src/template/layout.html');

if ($paginaHTML === false) {
	$paginaHTML = "<p>Errore: template layout.html non trovato o non leggibile.</p>";
}

$title = '<title>Come funziona? - PetMatch </title>';
$description = '<meta name="description" content="Pagina per avere informazioni su come funziona il nostro sito di adozioni di PetMatch">';
$keywords = "";

$breadcrumb = getBreadcrumb('come-funziona', $pagine);

$nav = buildNav($userMenu, './come-funziona');

$main = file_get_contents('./src/template/main/come-funziona.html');

$footer = buildFooter($footerMenu,  './come-funziona');

$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

echo $paginaHTML;
?>