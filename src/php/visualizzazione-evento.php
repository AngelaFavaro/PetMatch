<?php
include './src/utils.php';
include './src/DBconnection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use DB\DBAccess;

$titoloGET = $_GET['titolo'] ?? null;
$dataGET   = $_GET['data'] ?? null;


function buildEventsCards($events): string {
    $html='';
    foreach ($events as $e) {
        $img='';
        if (!empty($e['immagine']) && file_exists($e['immagine'])) {
            $img = $e['immagine'];
        } else {
            $img = 'assets/images/events/eventi-default.jpg';
        }
        $data=formattaDataItaliana($e['data_evento']);
        $titolo=$e['titolo'];
        $html .= "
                <li>
                    <article class='evento'>
                        <!-- Immagine dell'evento -->
                        <img class='immagine-evento' src=$img alt=''>
                        <h2 id='evento-titolo-$titolo'>$titolo</h2>
                        
                        <!-- Data dell'evento -->
                        <p class='data-evento'>
                            <!-- icona decorativa -->
                            <img src='assets/icons/calendar.svg' alt='' aria-hidden='true' class='icon-calendar'>
                            <!-- data semantica -->
                            <time datetime=$data>$data</time>
                        </p>
                        <div class='dettagli-evento-bottone'>
                        <a href='visualizzazione-evento?titolo=".urlencode($titolo)."&data=".urlencode($e['data_evento'])."'>Vedi dettagli</a>
                    </div>
                    </article>
                </li>
";
    }
    return $html;
}





function buildMainevent(array $dettagliEvento): string {
    $html = '';
    if($dettagliEvento) {
        $titolo = htmlspecialchars($dettagliEvento['Titolo']);
        $luogo = htmlspecialchars($dettagliEvento['Via'] . ", " . $dettagliEvento['Citta']);
        $descrizione = htmlspecialchars($dettagliEvento['DescrEvento']);
        
        // Formattazione Data
        $dataFormattata = date("d/m/Y", strtotime($dettagliEvento['DataEvento']));
        
        // Gestione Immagine
        $img = (!empty($dettagliEvento['ImgPath']) && file_exists($dettagliEvento['ImgPath'])) ? $dettagliEvento['ImgPath'] : 'assets/images/events/eventi-default.jpg';

        // 3. Creazione del blocco HTML (con le variabili ora piene!)
        $html = "<div id='mainEvent'>
        <div id='evento'>
        <img class='square-foto' id='foto-animale' src='$img' alt='foto del luogo per evento  $titolo'>
            <h1>$titolo</h1>
            <dl>
                <dt>Luogo</dt> <dd> $luogo </dd>
                <dt>Data</dt> <dd> $dataFormattata </dd>
                <dt id='descrizione-evento'>Descrizione</dt> <dd>$descrizione</dd>
            </dl>
        </div>
        </div>";
    } else {
        $html = "<p class='errore'>No info</p>";
    }
    return $html;
}

// PARAMETRI
$luogo = '';
$titolo = '';
$descrizione = '';
$dataFormattata = '';
$img = '';
$titoloPagina = '';
$descrizioneMeta = '';
$contenutoEvento = '';
$titoloEncoded='';
$eventiAside='';


$connection = new DBAccess();
if ($connection->openDBConnection()) {
    
    if ($titoloGET && $dataGET) {
        $dettagliEvento = $connection->getInfoEvent($titoloGET, $dataGET);
        if (!$dettagliEvento) {
            // ENT_QUOTES converte ' in &#039;
            $titoloEncoded = htmlspecialchars($titoloGET, ENT_QUOTES); 
            $dettagliEvento = $connection->getInfoEvent($titoloEncoded, $dataGET);
            if ($dettagliEvento) {
                // html_entity_decode trasforma "c&#039;è" in "c'è"
                $dettagliEvento['Titolo'] = html_entity_decode($dettagliEvento['Titolo'], ENT_QUOTES);
            } else {
                $titoloEncoded = '';
            }
        }

        if ($dettagliEvento) {
            // Assegnazione variabili dal DB

            // Aggiornamento metadati SEO
            $titoloPagina = "$titolo - PetMatch";
            $descrizioneMeta = "Partecipa all'evento $titolo a $luogo il $dataFormattata";

            // 3. Creazione del blocco HTML (con le variabili ora piene!)
            $contenutoEvento = buildMainevent($dettagliEvento);

            if($titoloEncoded) {
                $citta=['citta' => $dettagliEvento['Citta'],
                'nomeNO' => $titoloEncoded];
            } else if($dettagliEvento) {
                $citta=['citta' => $dettagliEvento['Citta'],
                'nomeNO' => $dettagliEvento['Titolo']];
            }
            $eventi = $connection->getEventsFilteredPaged($citta, 3);
            $eventiAside=$eventi?buildEventsCards($eventi) : "<p class='errore'>Per ora non ci sono altri eventi in programma in questa città. Ritorna tra qualche giorno a controllare</p>";
        } else {
            $contenutoEvento = "<p class='errore'>Non è stato possibile recuperare l'evento con tale data e nome. Riprovare più tardi</p>";
            $eventiAside = "<p class='errore'>Per ora non ci sono altri eventi in programma in questa città. Ritorna tra qualche giorno a controllare</p>";
        }
    }
    if($titoloEncoded) {
        $citta=['citta' => $dettagliEvento['Citta'],
                'nomeNO' => $titoloEncoded];
    } else {
        $citta=['citta' => $dettagliEvento['Citta'],
                'nomeNO' => $dettagliEvento['Titolo']];
    }
    $eventi = $connection->getEventsFilteredPaged($citta, 3);
    $eventiAside=$eventi?"<ul class='cards-container' id='content-animali' tabindex='-1' aria-label='Altri eventi nella zona'>" .buildEventsCards($eventi) . "</ul>" : "
    <p class='errore cards-container'>Per ora non ci sono altri eventi in programma in questa città. Ritorna tra qualche giorno a controllare</p>";

    $connection->closeConnection();
} else {
    $contenutoEvento = "<p class='errore'>Impossibile connettersi al database.</p>";
}




//Layout pagina
$paginaHTML = file_get_contents('./src/template/layout.html');
if ($paginaHTML === false) {
	$paginaHTML = "<p>Errore: template layout.html non trovato o non leggibile.</p>";
}

$title = '<title> Visualizzazione evento </title>'; //da cambiare
$description = '<meta name="description" content="Pagina dedicata all\'evento organizzato da PetMatch dedicato a cani e gatti ">';  //da cambiare
$keywords =     '<meta name="keywords" content= "PetMatch, evento, animali, cani, gatti">';//da cambiare
;

$breadcrumb = getBreadcrumb('visualizzazione-evento', $pagine);
$nav = buildNav($userMenu, './visualizzazione-evento');
$main = file_get_contents('./src/template/main/visualizzazione-evento.html');

$footer = file_get_contents('./src/template/partials/footer.html');

$main = str_replace('[EVENTO]', $contenutoEvento, $main);
$main = str_replace('[ALTRI-EVENTI]', $eventiAside, $main);


$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
// $paginaHTML = str_replace('[header]', $header, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);


echo $paginaHTML;
?>