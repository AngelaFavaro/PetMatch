<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;



$paginaHTML = file_get_contents('./src/template/layout-admin.html');
if ($paginaHTML === false) {
	$paginaHTML = "<p>Errore: template layout.html non trovato o non leggibile.</p>";
}

// questi valori verranno presi con GET in futuro (ho commentato)
$email = 'lindorlinor@gmail.com';
$idAnimale = 1;
// $email = $_GET['email-richiedente'];
// $idAnimale = $_GET['id-animale'];



//prova connessione

$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
$richiesta = '';
if ($connessioneOK) {
	$richiesta = $connessione->getRichiestaDettagli($email, $idAnimale);
	if (isset($_POST['accetta_richiesta'])) {
		$connessione->accettaValutazione($email, $idAnimale);
		header("Location: dettagli-richiesta");
		exit;
	}

	if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salva_note'])) {
		$note = trim($_POST['note'] ?? '');
		$connessione->aggiornaNote($email, $idAnimale, $note);
		// risposta silenziosa per fetch
		http_response_code(204);
		exit;
	}
	$connessione->closeConnection();
}


$title = '<title>Area riservata admin - PetMatch </title>';
$description = '<meta name="description" content="Area riservata per gli amministratori di PetMatch">';
$keywords = "";

$scarta_richiesta = '<a data-id-richiedente="' . $richiesta['email-richiedente'] . '" data-id-animale="' . $richiesta['id-animale'] . '" class="orange-button" >Scarta richiesta</a>';
$nav = file_get_contents('./src/template/partials/nav-admin.html');

$breadcrumb = getBreadcrumb('dettagli-richiesta', $pagine);

$main = file_get_contents('./src/template/main/admin/dettagli-richiesta.html');

// $paginaHTML = str_replace('[description]', $description, $paginaHTML);
// $paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$main = str_replace('[data]', htmlspecialchars($richiesta['data-richiesta']), $main);
$main = str_replace('[contenutoLettera]', htmlspecialchars($richiesta['lettera-di-presentazione']), $main);
if ($richiesta['trasporto-richiesta'] === 1) {
	$main = str_replace('[trasporto]', 'Sì', $main);
} else {
	$main = str_replace('[trasporto]', 'No', $main);
}
$main = str_replace('[scarta-richiesta]', $scarta_richiesta, $main);
$main = str_replace('[nome]', $richiesta['nome-richiedente'], $main);
$main = str_replace('[cognome]', $richiesta['cognome-richiedente'], $main);
$main = str_replace('[telefono]', $richiesta['telefono-richiedente'], $main);
$main = str_replace('[email]', $richiesta['email-richiedente'], $main);
$main = str_replace('[indirizzo]', $richiesta['indirizzo-richiedente'], $main);
$main = str_replace('[nomeAnimale]', $richiesta['nome-animale'], $main);
$main = str_replace('[sessoAnimale]', $richiesta['sesso-animale'], $main);
$main = str_replace('[etaAnimale]', $richiesta['eta-animale'], $main);
$main = str_replace('[razzaAnimale]', $richiesta['razza-animale'], $main);
if ($richiesta['trasporto-animale'] === 1) {
	$main = str_replace('[trasportoAnimale]', 'Sì', $main);
} else {
	$main = str_replace('[trasportoAnimale]', 'No', $main);
}
$main = str_replace('[famigliaIdeale]', $richiesta['famiglia-ideale'], $main);
if (!$richiesta['condizioni-mediche']) {
	$main = str_replace('[condizioniMediche]', 'Nessuna', $main);
}

$stato_trasporto = '';
$pulsanti_azioni_richiesta = '';
if ($richiesta['stato'] === 'Da trasportare') {
	$stato_trasporto = '<article id="stato-trasporto">
			<p><strong>Stato della richiesta:</strong> ' . $richiesta['stato'] . '</p>
			<p><strong>Data di arrivo:</strong> [dataDiArrivo]</p>
			<a href="./">
				<img src="./assets/icons/edit-pencil.svg" alt="Modificare le informazioni">
			</a>
		</article>';
} elseif ($richiesta['stato'] === 'Nuova') {
	$pulsanti_azioni_richiesta = '
		<form method="POST">
			<input type="hidden" name="id_animale" value="' . $richiesta['id-animale'] . '">
			<input type="hidden" name="email_richiedente" value="' . $richiesta['email-richiedente'] . '">
			<button type="submit" name="accetta_richiesta" class="orange-button">Accetta valutazione</button>
		</form>';

} else {
	$pulsanti_azioni_richiesta = '<a href="mailto:' . $richiesta['email-richiedente'] . '" class="orange-button" target="_blank">Contatta candidato</a>';
}
$main = str_replace('[stato-trasporto]', $stato_trasporto, $main);
$main = str_replace('[pulsanti-azioni-richiesta]', $pulsanti_azioni_richiesta, $main);

$annotazioni = '<div class="note">
            <div class="header-note">
                <h2>LE TUE ANNOTAZIONI</h2>
                <!-- TODO da mettere collegamento-->
                <a href="#" id="edit-note">
                    <img src="./assets/icons/edit-pencil.svg" alt="Modificare le informazioni">
                </a>
            </div>
            <p id="note-text">'.$richiesta['appunti'].'</p>
        </div>';
$main = str_replace('[annotazioni]', $annotazioni, $main);





$main = str_replace('[descrizioneCaratteriale]', $richiesta['descrizione-caratteriale'], $main);


// $connessioneOK = $connessione->openDBConnection();
// if($connessioneOK){
// 	if (isset($_POST['accetta_richiesta'])) {
// 		$connessione->accettaValutazione( $email, $idAnimale);

// 	}
// 	$connessione->closeConnection();
// }


$paginaHTML = str_replace('[main]', $main, $paginaHTML);
echo $paginaHTML;
?>