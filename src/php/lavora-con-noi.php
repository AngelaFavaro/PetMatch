<?php
include './src/utils.php';
$paginaHTML = file_get_contents('./src/template/layout.html');

if ($paginaHTML === false) {
	$paginaHTML = "<p>Errore: template layout.html non trovato o non leggibile.</p>";
}

$title = '<title>Lavora con Noi - PetMatch </title>';
$description = '<meta name="description" content="Pagina per avere informazioni su come lavorare o fare volontariato o diventare un sostenitore di PetMatch">';
$keywords = "";

$breadcrumb = getBreadcrumb('lavora-con-noi', $pagine);

$nav = buildUserNav($userMenu, './lavora-con-noi', $_SESSION['email'] ?? false);

$main = file_get_contents('./src/template/main/lavora-con-noi.html');

$footer = file_get_contents('./src/template/partials/footer.html');

$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

echo $paginaHTML;
?>