<?php
include './src/utils.php';
$paginaHTML = file_get_contents('./src/template/layout.html');
if ($paginaHTML === false) {
	$paginaHTML = "<p>Errore: template layout.html non trovato o non leggibile.</p>";
}

$title = '<title>Animali - PetMatch </title>';

$description = '<meta name="description" content="Tutti gli animali in adozione qui da PetMatch!!">';
$keywords = "";


//$nav = file_get_contents('./src/template/partials/nav-admin.html');

$breadcrumb = getBreadcrumb('animali', $pagine);

$main = file_get_contents('./src/template/main/animali.html');

$footer= file_get_contents('./src/template/partials/footer.html');

$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
//$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

echo $paginaHTML;
?>