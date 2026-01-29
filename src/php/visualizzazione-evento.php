<?php
include './src/utils.php';
include './src/DBconnection.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use DB\DBAccess;
$isAdmin=0;
$action='visualizzazione-evento';
if (defined('ADMIN_EVENTO')) {
    $isAdmin=1;
}
if($isAdmin) {
    $action='dettagli-evento';
}

$url=$isAdmin?'dettagli-evento':'visualizzazione-evento'; 

$titoloGET = $_GET['titolo'] ?? null;
$dataGET   = $_GET['data'] ?? null;


function buildEventsCards($events): string {
    $html='';
    $altriEventi='';
    foreach ($events as $e) {
        $img='';
        if (!empty($e['immagine']) && file_exists($e['immagine'])) {
            $img = $e['immagine'];
        } else {
            $img = 'assets/images/animals/defaultCane.jpg';
        }
        $data=formattaDataItaliana($e['data_evento']);
        $titolo=$e['titolo'];
        $altriEventi .= "<li>
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
                        <a href='visualizzazione-evento?titolo=$titolo&data=$data'>Vedi dettagli</a>
                    </div>
                    </article>
                </li>";
    }
    $html="<h2>Altri eventi nella zona</h2>
            <ul class='cards-container' id='content-animali' tabindex='-1' aria-label='Altri eventi nella zona'>
            $altriEventi
            </ul>
            <a class='brown-button' href = './eventi'> Guarda tutti gli eventi →</a>";
    return $html;
}





function buildMainevent(array $dettagliEvento, int $isAdmin=0): string {
    $html = '';
    if($dettagliEvento) {
        $titolo = htmlspecialchars($dettagliEvento['Titolo']);
        $luogo = htmlspecialchars($dettagliEvento['Via'] . ", " . $dettagliEvento['Citta']);
        $descrizione = htmlspecialchars($dettagliEvento['DescrEvento']);
        $date=$dettagliEvento['DataEvento'];
        
        // Formattazione Data
        $dataFormattata = date("d/m/Y", strtotime($dettagliEvento['DataEvento']));
        
        // Gestione Immagine
        $img = (!empty($dettagliEvento['ImgPath']) && file_exists($dettagliEvento['ImgPath'])) ? $dettagliEvento['ImgPath'] : 'assets/images/events/eventi-default.jpg';

        // 3. Creazione del blocco HTML (con le variabili ora piene!)
        $html = "<div id='mainEvent'>";
        if($isAdmin) {
            $html.="<div class='edit-btn-container'>
    <a class='orange-button' href='modifica-evento?titolo=$titolo&data=$date'>Modifica<span class='sr-only'> scheda evento</span></a>
</div>";
        }
        $html.="
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

function buildCollaboratorsCard($collaboratori): string {
    $collaboratoriCards='';
    if($collaboratori) {
        foreach ($collaboratori as $c) {
            $name=$c['Nome'];
            $surname=$c['Cognome'];
            $profilePic=$c['ImgPath'];
            $collaboratoriCards.="<li><img src='$profilePic' alt=''> $name $surname</li>";
        }
        $html="<h2>Scritto da:</h2>
            <ul class='cards-container' id='content-animali' tabindex='-1' aria-label='Animali in adozione'>
                $collaboratoriCards
            </ul>";
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
$dettagliEvento='';

$dataIta=convertiDataItalianaInSQL($dataGET);

$connection = new DBAccess();
if ($connection->openDBConnection()) {
    
    if ($titoloGET && $dataGET) {
        $dettagliEvento = $connection->getInfoEvent($titoloGET, $dataIta);
        if (!$dettagliEvento) {
            // ENT_QUOTES converte ' in &#039;
            $titoloEncoded = htmlspecialchars($titoloGET, ENT_QUOTES); 
            $dettagliEvento = $connection->getInfoEvent($titoloEncoded, $dataIta);
            if ($dettagliEvento) {
                // html_entity_decode trasforma "c&#039;è" in "c'è"
                $dettagliEvento['Titolo'] = html_entity_decode($dettagliEvento['Titolo'], ENT_QUOTES);
            }
        }

        if ($dettagliEvento) {
            // Assegnazione variabili dal DB

            // Aggiornamento metadati SEO
            $titoloPagina = "$titolo - PetMatch";
            if($isAdmin) {
                $titoloPagina .='-area riservata';
            }
            $descrizioneMeta = "Partecipa all'evento $titolo a $luogo il $dataFormattata";

            // 3. Creazione del blocco HTML (con le variabili ora piene!)
            $contenutoEvento = buildMainevent($dettagliEvento, $isAdmin);
        } else {
            $contenutoEvento = $titoloGET;
        }
    }

    if(!$isAdmin) {
        if($titoloEncoded) {
            $citta=['citta' => $dettagliEvento['Citta'],
                    'nomeNO' => $titoloEncoded,
                    'dataNO' => $dataIta];
        } else {
            $citta=['citta' => $dettagliEvento['Citta'],
                    'nomeNO' => $dettagliEvento['Titolo'],
                    'dataNO' => $dataIta];
        }
        $eventi = $connection->getEventsFilteredPaged($citta, 3);
        $Aside=$eventi?buildEventsCards($eventi) : "<h2>Altri eventi nella zona</h2>
            <ul class='cards-container' id='content-animali' tabindex='-1' aria-label='Altri eventi nella zona'>
            <p class='errore'>Per ora non ci sono altri eventi in programma in questa città. Ritorna tra qualche giorno a controllare</p>
            </ul>
            <a class='brown-button' href = './eventi'> Guarda tutti gli eventi →</a>";
    } else {
        if($titoloEncoded) {
            $collaboratori=$connection->getOrganizzatoriEvento($titoloEncoded, $dataIta);
        } else {
        //recupera i collaboratori con la query e mettili nell'aside
            $collaboratori=$connection->getOrganizzatoriEvento($dettagliEvento['Titolo'], $dataIta);
        }
        $Aside=$collaboratori?buildCollaboratorsCard($collaboratori) : "<p class='errore'>Impossibile accedere ai collaboratori</p>";
    }
    $connection->closeConnection();
} else {
    $contenutoEvento = "<p class='errore'>Impossibile connettersi al database.</p>";
}




//Layout pagina
$paginaHTML = $isAdmin? file_get_contents('./src/template/layout-admin.html') : file_get_contents('./src/template/layout.html');
if ($paginaHTML === false) {
	$paginaHTML = "<p>Errore: template layout.html non trovato o non leggibile.</p>";
}

$title = '<title> Visualizzazione evento </title>'; //da cambiare
$description = '<meta name="description" content="Pagina dedicata all\'evento organizzato da PetMatch dedicato a cani e gatti ">';  //da cambiare
$keywords =     '<meta name="keywords" content= "PetMatch, evento, animali, cani, gatti">';//da cambiare


$breadcrumb = $isAdmin? getBreadcrumb('dettagli-evento', $pagine) :getBreadcrumb('visualizzazione-evento', $pagine);
$nav = $isAdmin? buildAdminNav($adminMenu, './dettagli-evento'): buildNav($userMenu, './visualizzazione-evento');
$main = file_get_contents('./src/template/main/visualizzazione-evento.html');

$footer = $isAdmin? '' : buildFooter($footerMenu,  './visualizzazione-evento');

$main = str_replace('[EVENTO]', $contenutoEvento, $main);
$main = str_replace('[ASIDE]', $Aside, $main);

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