<?php
include './src/utils.php';
include './src/db.php';

$paginaHTML = file_get_contents('./src/template/layout.html');

$idAnimale = $_GET['id'] ?? null;
$nomeAnimale = 'Animale';

if ($idAnimale) {
    $stmt = $pdo->prepare("SELECT nome FROM animali WHERE id = ?");
    $stmt->execute([$idAnimale]);
    $animale = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($animale) {
        $nomeAnimale = $animale['nome'];
    }
}

$title = "<title>$nomeAnimale - PetMatch</title>";
$description = "<meta name='description' content='Scheda di $nomeAnimale disponibile per adozione'>";
$keywords = '<meta name="keywords" content=>$nomeAnimale, $razza, adozione, PetMatch">';

$breadcrumb = getBreadcrumb([
    ['label' => 'Home', 'url' => '/'],
    ['label' => 'Animali', 'url' => '/animali.php'],
    ['label' => $nomeAnimale, 'url' => '']
]);

$nav = buildUserNav($userMenu, './animale');
$main = file_get_contents('./src/template/main/animale.html');
$footer = file_get_contents('./src/template/partials/footer.html');

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
