<?php
include './src/utils.php';
$paginaHTML = file_get_contents('./src/template/layout-admin.html');
if ($paginaHTML === false) {
	$paginaHTML = "<p>Errore: template layout.html non trovato o non leggibile.</p>";
}

$title = '<title>Area riservata admin - PetMatch </title>';

// TODO ma ha senso la descrizione qui dentro? e le keywords?
$description = '<meta name="description" content="Area riservata per gli amministratori di PetMatch">';
$keywords = "";

$_richiesta['id']=1; // TODO da DB
$scarta_richiesta = '<a data-id-richiesta="'.$_richiesta['id'].'" class="orange-button" >Scarta richiesta</a>';
$nav = file_get_contents('./src/template/partials/nav-admin.html');

$breadcrumb = getBreadcrumb('dettagli-richiesta', $pagine);

$main = file_get_contents('./src/template/main/admin/dettagli-richiesta.html');

// $paginaHTML = str_replace('[description]', $description, $paginaHTML);
// $paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$main = str_replace('[scarta-richiesta]', $scarta_richiesta, $main);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);

echo $paginaHTML;
?>