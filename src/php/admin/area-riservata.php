<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;
//se non sono loggato rimando alla pagina di login
// if (!isset($_SESSION['email'])) {
//     header("Location: ./accedi");
//     exit;    
// }else if(isset($_SESSION['admin']) && $_SESSION['admin'] === true){
//     header("Location: ./area-riservata");
//     exit; 
// }
function buildToDoList(DBAccess $conn): string {
	$html = '<ul id="to-do-list">';
	$tasks =$conn->createAdminTasks($_SESSION['user'] ?? '');
	$links= [
		['href' => '', 'type' => 'ANIMALI SENZA ADMIN'],
		['href' => '', 'type' => 'APPUNTI DA PRENDERE'],
		['href' => '', 'type' => 'ACCOGLIENZE'],
		['href' => '', 'type' => 'ADOZIONI DA VALUTARE'],
		['href' => '', 'type' => 'TRASPORTI DA ORGANIZZARE'],
	];
	foreach ($links as $index => $link) {
		$nQuery = $tasks[$index] ?? 0;
		$html .= '
		<li>
			<a href="' . $link['href'] . '">
				<p class="n-query">' . $nQuery . '</p>
				<p class="query-type">' . $link['type'] . '</p>
			</a>
		</li>
		';
	}
	$html .= '</ul>';
	return $html;
}


function buildStatisticsArea(DBAccess $conn): string{
	$html = '<ul id="statistics-list">';
	$stats = $conn->createAdminStats($_SESSION['user'] ?? '');
	$types= [
		'ADOZIONI COMPLETATE',
		'RICHIESTE VISIONATE',
		'IN CORSO DI ADOZIONE',
	];
	foreach ($types as $index => $type) {
		$html .= '
			<li>
				<p class="n-query">' . ($stats[$index] ?? 0) . '</p>
				<p class="query-type">' . $type . '</p>
			</li>
		';
	}
	$html .= '</ul>';
	return $html;
}


function buildInfoAdmin(): array{
	$html = '';
	$titolo = '';
	if (isset($_GET['mode']) && $_GET['mode'] === 'edit') {
		$titolo = '<h2>Modifica le tue informazioni</h2>';

	}else if (isset($_GET['mode']) && $_GET['mode'] === 'management') {
		$titolo = '<h2>Gestione dell\'account</h2>';
	} else {
		$titolo = '<h2>Le tue informazioni</h2>';
		$html =
			'<div class="text-details"><dl aria-label="informazioni dell\'utente">
				<dt>Nome</dt> <dd>[nomeAdmin]</dd>
				<dt>Cognome</dt> <dd>[cognomeAdmin]</dd>
				<dt>Indirizzo</dt> <dd>[indirizzoAdmin]</dd>
				<dt>Email</dt> <dd>[emailAdmin]</dd>
				<dt>Telefono</dt> <dd>[telefonoAdmin]</dd>
			</dl></div>';
	}

	return ['contenuto' => $html, 'titolo' => $titolo];
}
function editInfoAdmin(DBAccess $conn, $adminInfo) {
	
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-edit'])) {
	$newName = $_POST['nome'] ?? '';
	$newSurname = $_POST['cognome'] ?? '';

	$newImg = false;
	if(isset($_FILES['foto'])) {
		// qui dentro ci va la logica per caricare l'immagine
		$newImg = uploadImage($_FILES['foto'], 'admins');
	}
	
	// se $newImg è false, significa che non è stata caricata nessuna nuova immagine, quindi mantengo quella vecchia, ma così non funziona
	$newImg = $newImg ?: $adminInfo['imgPath'];
	$conn->updateAdminInfo($_SESSION['user'] ?? '', $newName, $newSurname, $newImg);
	$conn->closeConnection();

	header("Location: ./area-riservata");
	exit();
	
}
}

$stats = "";
$todolist = "";
$adminInfo ='';
$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
if ($connessioneOK) {
    // centralizzo le operazioni che richiedono la connessione, così non spreco risorse
	
	$todolist = buildToDoList($connessione);
	$stats = buildStatisticsArea($connessione);

	$adminInfo = $connessione->getUserInfo($_SESSION['email']);
	$adminInfoSection = buildInfoAdmin($connessione, $adminInfo);
	// editInfoAdmin($connessione, $adminInfo);
	$connessione->closeConnection();
}


$paginaHTML = loadTemplate('./src/template/layout-admin.html', '<p>Errore: template layout.html non trovato o non leggibile.</p>');

$title = '<title>Area riservata admin - PetMatch </title>';
$description = '<meta name="description" content="Area riservata per gli amministratori di PetMatch">';
$keywords = ""; //TO DO


$nav = buildAdminNav($adminMenu, './area-riservata');
$footer = loadTemplate('./src/template/partials/footer.html', '<p>Errore: template footer.html non trovato o non leggibile.</p>');
$breadcrumb = getBreadcrumb('area-riservata', $pagine);

$main = loadTemplate('./src/template/main/admin/area-riservata.html', '<p>Errore: template area-riservata.html non trovato o non leggibile.</p>');
$main = str_replace('[to-do-list]', $todolist, $main);
$main = str_replace('[stats]', $stats, $main);
$main = str_replace('[admin-info]', $adminInfoSection['contenuto'], $main);
$main = str_replace('[imgPath]', $adminInfo['ImgPath'], $main);
$main = str_replace('[nomeAdmin]', $adminInfo['Nome'], $main);
$main = str_replace('[cognomeAdmin]', $adminInfo['Cognome'], $main);
if(isset($adminInfo['Telefono']))
	$main = str_replace('[telefonoAdmin]', $adminInfo['Telefono'], $main);
else
	$main = str_replace('[telefonoAdmin]', '<em>Sconosciuto</em>', $main);
$main = str_replace('[emailAdmin]', $_SESSION['email'], $main);
$main = str_replace('[]', $_SESSION['email'], $main);
if(isset($adminInfo['Via']) && isset($adminInfo['Citta']) && isset($adminInfo['CAP']))
	$main = str_replace('[indirizzoAdmin]', $adminInfo['Via'].', '.$adminInfo['Citta'].', '.$adminInfo['CAP'], $main);
else
	$main = str_replace('[indirizzoAdmin]', '<em>Sconosciuto</em>', $main);


$main = str_replace('[titolo]', $adminInfoSection['titolo'], $main);
$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

echo $paginaHTML;
?>