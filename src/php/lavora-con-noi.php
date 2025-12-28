<?php
include './src/utils.php';
$paginaHTML = file_get_contents('./src/template/layout.html');

if ($paginaHTML === false) {
	$paginaHTML = "<p>Errore: template layout.html non trovato o non leggibile.</p>";
}

$title = '<title>Pagina Lavora con Noi - PetMatch </title>';
$description = '<meta name="description" content="Pagina per avere informazioni su come lavorare o fare volontariato o diventare un sostenitore di PetMatch">';
$keywords = " volontariato, lavoro, canile, gattile, veterinari, sostenitore, donazione";

$header = file_get_contents('./src/template/partials/header.html')

$breadcrumb = getBreadcrumb('lavora-con-noi', $pagine);

$main = file_get_contents('./src/template/main/admin/area-riservata.html');

$footer = file_get_contents('./src/template/partials/footer.html');

$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[header]', $header, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

echo $paginaHTML;
?>