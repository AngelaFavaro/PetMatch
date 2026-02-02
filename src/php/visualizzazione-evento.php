<?php
include './src/utils.php';
include './src/DBconnection.php';

use DB\DBAccess;

$currentUri = $_SERVER['REQUEST_URI'];
$isAdmin=0;

if(str_contains($currentUri, 'dettagli-evento')){
    if(isset($_SESSION['admin']) && $_SESSION['admin']===true){
        $isAdmin=1;
    }else{
        header('Location: ./visualizzazione-evento?titolo='.$_GET['titolo']."&data=".$GET['data_evento']);
        exit;
    }
}

$titoloGET = $_GET['titolo'] ?? null;
$dataGET   = $_GET['data'] ?? null;

if(!$titoloGET || !$dataGET){
    header("Location: ./eventi");
    exit;
}

$classCollaboratori='';
if($isAdmin) {
    $classCollaboratori="class='collaboratori'";
}


$errore='';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete-evento'])) {
    $connessione = new DBAccess();
    $connessioneOK = $connessione->openDBConnection();
    if($connessioneOK) {
        deleteStoredFile($connessione->getImgEvent($titoloGET, $dataGET));    
        if ($connessione->deleteEvent($titoloGET, $dataGET)) {
            $connessione->closeConnection();
            header("Location: ./visualizzazione-eventi");
            exit;
        }
    } else {
        $errore="<dialog open class='overlay-content'>
                    <div class='dialog-box'>
                        <h3 id='modal-title'>Errore di connessione</h3>
                        <p>L'eliminazione di <strong>".htmlspecialchars($titoloGET)."</strong> è <strong>fallita</strong>. Riprovare più tardi</p>
                        <div class='dialog-buttons'>
                            <form method='post'>
                                <button type='submit' name='close-dialog' class='button-cancel'>Chiudi</button>
                            </form>
                        </div>
                    </div>
                </dialog>";
    }
}


function buildEventsCards($events): string {
    $html='';
    $altriEventi='';
    foreach ($events as $e) {
        $img='';
        if (!empty($e['immagine']) && file_exists($e['immagine'])) {
            $img = $e['immagine'];
        } else {
            $img = 'assets/images/events/eventi-default.jpg';
        }
        $data=formattaDataItaliana($e['data_evento']);
        $titolo=htmlspecialchars($e['titolo']);
        $altriEventi .= "<li>
                    <article class='evento'>
                        <img class='immagine-evento' src='".htmlspecialchars($img)."' alt='' />
                        <h2>$titolo</h2>
                        
                        <p class='data-evento'>
                            <img src='assets/icons/calendar.svg' alt='' aria-hidden='true' class='icon-calendar' />
                            <time datetime='".htmlspecialchars($e['data_evento'])."'>".htmlspecialchars($data)."</time>
                        </p>
                        <div class='dettagli-evento-bottone'>
                        <a href='visualizzazione-evento?titolo=".urlencode($e['titolo'])."&data=".urlencode($e['data_evento'])."'>Vedi dettagli</a>
                    </div>
                    </article>
                </li>
";
    }
    $html="<h2>Altri eventi nella zona</h2>
            <ul class='cards-container' id='content-animali' tabindex='-1' aria-label='Altri eventi nella zona'>
            $altriEventi
            </ul>
            <a class='link-button' href = './eventi'> Guarda tutti gli eventi →</a>";
    return $html;
}





function buildMainevent(array $dettagliEvento, int $isAdmin=0): string {
    $html = '';
    if($dettagliEvento) {
        $titolo = htmlspecialchars($dettagliEvento['Titolo']);
        $luogo = htmlspecialchars($dettagliEvento['Via'] . ", " . htmlspecialchars($dettagliEvento['Citta']));
        $descrizione = htmlspecialchars($dettagliEvento['DescrEvento']);
        $date=$dettagliEvento['DataEvento'];
        
        $dataFormattata = date("d/m/Y", strtotime($dettagliEvento['DataEvento']));
        
        $img = (!empty($dettagliEvento['ImgPath']) && file_exists($dettagliEvento['ImgPath'])) ? $dettagliEvento['ImgPath'] : 'assets/images/events/eventi-default.jpg';

        $html = "<div id='mainEvent' [COLLABORATORI]>
        <div id='evento'>";
        if ($isAdmin) {
        $html .="<div class='edit-btn-container'>
                <form method='post'>
                    <button type='submit' name='show-dialog' class='button-cancel'>
                        <img src='assets/icons/delete-trash.svg' alt='' />Elimina evento
                    </button>
                </form>";
            if ($date) {
                $dataEvento = DateTime::createFromFormat('Y-m-d', $date);
                $oggi = new DateTime('today');

                if ($dataEvento && $dataEvento > $oggi) {
                    $html.="
                <a class='db-button' href='modifica-evento?titolo=$titolo&data=".htmlspecialchars($date)."'>Modifica<span class='sr-only'> scheda evento</span></a>
                ";
            }
        }
        $html .="</div>";
        }

        
        $html.="
        <img class='square-foto' id='foto-animale' src=".htmlspecialchars($img)." alt='foto del luogo per evento  $titolo' />

        <dialog [openDialog] class='overlay-content'>
                    <div class='dialog-box'>
                        <h3 id='modal-title'>Conferma eliminazione</h3>
                        <p>L'eliminazione di <strong>$titolo</strong> è <strong>irreversibile</strong>. Vuoi continuare?</p>
                        <div class='dialog-buttons'>
                            <form method='post'>
                                <button type='submit' name='close-dialog' class='button-cancel'>No, annulla</button>
                            </form>
                            <form method='post'>
                                <button type='submit' name='delete-evento'>Si, elimina</button>
                            </form>
                        </div>
                    </div>
                </dialog>
            <h1>$titolo</h1>
            <dl>
                <dt>Luogo</dt> <dd> $luogo </dd>
                <dt>Data</dt> <dd> ".htmlspecialchars($dataFormattata)." </dd>
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
            $name=htmlspecialchars($c['Nome']);
            $surname=htmlspecialchars($c['Cognome']);
            $profilePic=htmlspecialchars($c['ImgPath']);
            if (empty($profilePic) || !file_exists($profilePic)) {
                $profilePic = 'assets/images/admins/default-pic.png';
            }
            $emailColl=htmlspecialchars($c['Email']);
            $collaboratoriCards.="<li><img src='".htmlspecialchars($profilePic)."' class='circle-foto' alt='' /> <dl class='collaborator-name'><dt>Nominativo: </dt><dd>$name $surname</dd><dt>Email:</dt><dd>$emailColl</dd></dl></li>";
        }
        $html="<h2>Scritto da:</h2>
            <ul id='content-collaborators' tabindex='-1' aria-label='Organizzatori evento'>
                $collaboratoriCards
            </ul>";
    } else {
        $html = "<p class='errore'>No info</p>";
    }
return $html;

}

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
$eventiAside='';

$connection = new DBAccess();
if ($connection->openDBConnection()) {
    
    if ($titoloGET && $dataGET) {
        $dettagliEvento = $connection->getInfoEvent($titoloGET, $dataGET);
        if(!$dettagliEvento){
            header("Location: ./404");
            exit;
        }

        if ($dettagliEvento) {
            $titoloPagina = "$titolo - PetMatch";
            if($isAdmin) {
                $titoloPagina .='-area riservata';
            }
            $dataFormattata = date("d/m/Y", strtotime($dettagliEvento['DataEvento']));
            $luogo = htmlspecialchars($dettagliEvento['Via'] . ", " . htmlspecialchars($dettagliEvento['Citta']));
            $descrizioneMeta = "Partecipa all'evento $titolo a $luogo il $dataFormattata";

            $contenutoEvento = buildMainevent($dettagliEvento, $isAdmin);

            if(!$isAdmin) {
                if($titoloEncoded) {
                    $citta=['citta' => $dettagliEvento['Citta'],
                            'nomeNO' => $titoloEncoded,
                            'dataNO' => $dataGET];
                } else {
                    $citta=['citta' => $dettagliEvento['Citta'],
                            'nomeNO' => $dettagliEvento['Titolo'],
                            'dataNO' => $dataGET];
                }
                $eventi = $connection->getEventsFilteredPaged($citta, 3);
                $Aside=$eventi?buildEventsCards($eventi) : "<h2>Altri eventi nella zona</h2>
                    <ul class='cards-container' id='content-animali' tabindex='-1' aria-label='Altri eventi nella zona'>
                    <p class='errore'>Per ora non ci sono altri eventi in programma in questa città. Ritorna tra qualche giorno a controllare</p>
                    </ul>
                    <a class='link-button' href = './eventi'> Guarda tutti gli eventi →</a>";
            } else {
                if($titoloEncoded) {
                    $collaboratori=$connection->getOrganizzatoriEvento($titoloEncoded, $dataGET);
                } else {
                    //recupera i collaboratori con la query e mettili nell'aside
                    $collaboratori=$connection->getOrganizzatoriEvento($dettagliEvento['Titolo'], $dataGET);
                }
                $Aside=$collaboratori?buildCollaboratorsCard($collaboratori) : "<p class='errore'>Impossibile accedere ai collaboratori</p>";
            }
        } else {
            $contenutoEvento = "<p class='errore'>Non è stato possibile recuperare l'evento con tale data e nome. Riprovare più tardi</p>";
            $Aside = "<p class='errore'>Per ora non ci sono altri eventi in programma in questa città. Ritorna tra qualche giorno a controllare</p>";
        }
    }
  
    $connection->closeConnection();
} else {
    $contenutoEvento = "<p class='errore'>Impossibile connettersi al database.</p>";
}


$paginaHTML = $isAdmin? file_get_contents('./src/template/layout-admin.html') : file_get_contents('./src/template/layout.html');
if ($paginaHTML === false) {
	$paginaHTML = "<p>Errore: template layout.html non trovato o non leggibile.</p>";
}

$title = $isAdmin ? '<title> Visualizzazione evento - Area Riservata PetMatch </title>' : '<title> Visualizzazione evento - Utente PetMatch</title>';
$description = $isAdmin ? '<meta name="description" content="Pagina di visualizzazione all\'evento organizzato">' : '<meta name="description" content="Pagina dedicata all\'evento organizzato da PetMatch ">'; 
$keywords =  '<meta name="keywords" content= "PetMatch, evento, animali, cani, gatti">';
$showModal = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['show-dialog']);


$breadcrumb = $isAdmin? getBreadcrumb('dettagli-evento', $pagine) :getBreadcrumb('visualizzazione-evento', $pagine);
$nav = $isAdmin? buildAdminNav($adminMenu, './dettagli-evento'): buildNav($userMenu, './visualizzazione-evento');
$main = file_get_contents('./src/template/main/visualizzazione-evento.html');

$footer = $isAdmin? '' : buildFooter($footerMenu,  './visualizzazione-evento');

$main = str_replace('[EVENTO]', $contenutoEvento, $main);
$main = str_replace('[ASIDE]', $Aside, $main);
$main = str_replace('[COLLABORATORI]', $classCollaboratori, $main);
if($errore) {
    $main = str_replace('[openDialog]', $errore, $main);
} else {
    $main = str_replace('[openDialog]', ($showModal ? 'open' : ''), $main);
}

$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);


echo $paginaHTML;
?>