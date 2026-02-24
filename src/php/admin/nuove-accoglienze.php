<?php

use DB\DBAccess;

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) { //il primo controlla se esiste la variabile admin in session, la seconda controlla che sia affettivamente admin
    header("Location: ./accedi");
    exit;
}

if(isset($_GET['id-animale']) && isset($_GET['email']) ) {
    require './src/php/admin/dettagli-richiesta.php';
    
}else{
    include './src/utils.php';
    include './src/DBconnection.php';
    
    function renderAnimalContent(string $tipo, array $animaliSegnalati, string $NSegnalazioniByType, string $nome_admin): string {
        $tipoMinuscoloPlurale = ($tipo === 'Cane') ? 'cani' : 'gatti'; 
        
        if (($NSegnalazioniByType ?? 0) == 0) {
            return '<p role="status" class="nessun-risultato-message">Nessuna segnalazione per ' . $tipoMinuscoloPlurale . '.</p>';
        }

        $idTabella = "sumTabella" . $tipo;
        $html = '
            <span id="' . htmlspecialchars($idTabella) . '" class="sr-only" aria-hidden="true">In questa tabella vengono elencate le segnalazioni per nuove accoglienze per ' . $tipoMinuscoloPlurale . ' e i loro dettagli: identificativo segnalazione, data segnalazione, nominativo del segnalante (nome e cognome), email segnalante. Infine per ogni segnalazione un link alla gestione della segnalazione e eventualmente un pulsante per contattare il segnalante.</span>
            <table aria-describedby="' . htmlspecialchars($idTabella) . '">
                <caption>Segnalazioni di accoglienze per ' . htmlspecialchars($tipo) . '</caption>
                <thead>
                    <tr>
                        <th scope="col"><abbr title="Identificativo segnalazione">Id</abbr></th>
                        <th scope="col">Data segnalazione</th>
                        <th scope="col">Segnalante</th>
                        <th scope="col">Email segnalante</th>
                        <th scope="col"><span class= "sr-only">Gestione segnalazione</span></th>
                        <th scope="col"><span class= "sr-only">Contatto</span></th>
                    </tr>
                </thead>
                <tbody>';
        if(!empty($animaliSegnalati)){
            foreach ($animaliSegnalati as $animale) {
                $subject= rawurlencode('Segnalazione numero ' . $animale['id_segnalazione'] . ' - PetMatch - Hai bisogno di trovare casa al tuo animale?');

                $messaggio = "Ciao! Ho visto la tua segnalazione per un " . strtolower($tipo) . " e siamo interessati a raccogliere maggiori informazioni riguardo al tuo animale.\n\n" .
                "Potresti raccontarci un po' di più? Non ti preoccupare, ecco alcune domande che ci aiuterebbero molto (se non conosci la risposta ad alcune, scrivi pure 'non so'):\n\n" .
                "- Qual è la sua storia? (È cresciuto in famiglia o è stato trovato per strada?)\n" .
                "- Com'è di carattere? (È socievole, timido o un po' timoroso?)\n" .
                "- Come si comporta con gli altri? (Va d'accordo con cani, gatti o bambini?)\n" .
                "- Ha qualche problema di salute o assume farmaci?\n" .
                "- È già sterilizzato/a e microchippato/a?\n\n" .
                "Se non conosci il suo passato perché lo hai appena trovato, descrivici pure come lo hai visto in questi primi giorni.\n\n" .
                "Attendo un tuo riscontro. Grazie!\n\n".
                "Un caro saluto,\n" .
                $nome_admin. "\n".
                "Amministrazione PetMatch";

                $object =rawurlencode($messaggio);
                $html .='
                    <tr>
                        <th data-title="Id" scope="row">' . htmlspecialchars($animale['id_segnalazione']) . '</th>
                        <td data-title="Data segnalazione"><time datetime="' . htmlspecialchars($animale['data_segnalazione']) . '">' . htmlspecialchars(date('d/m/Y', strtotime($animale['data_segnalazione']))) . '</time></td>
                        <td data-title="Segnalante">' . htmlspecialchars($animale['nominativo_segnalante']) . '</td>
                        <td data-title="Email segnalante">' . htmlspecialchars($animale['email_segnalante']) . '</td>';

                    if($animale['email_admin']==null)
                        $html .='
                            <td colspan="2" class="col-dettagli">
                                <form method="post" action="nuove-accoglienze">
                                    <input type="hidden" name="tipo" value="' . htmlspecialchars($tipo) . '"/>
                                    <input type="hidden" name="id_segnalazione" value="' . htmlspecialchars($animale['id_segnalazione']) . '"/>
                                    <button type="submit" name="assegna_segnalazione" class="db-button">Assegna a me<span class="sr-only"> numero' . htmlspecialchars($animale['id_segnalazione']) . '</span></button>
                                </form>
                            </td>';
                    else{
                         $html .='
                            <td class="col-dettagli">
                                <form method="post" action="nuove-accoglienze">
                                    <input type="hidden" name="tipo" value="' . htmlspecialchars($tipo) . '"/>
                                    <input type="hidden" name="id_segnalazione" value="' . htmlspecialchars($animale['id_segnalazione']) . '"/>
                                    <button type="submit" name="elimina_segnalazione" class="db-button">Elimina<span class="sr-only"> numero' . htmlspecialchars($animale['id_segnalazione']) . '</span></button>
                                </form>
                            </td>
                            <td class="col-dettagli"><a target="_blank" href="mailto:' . htmlspecialchars($animale['email_segnalante']) . '?subject=' . $subject . '&body=' . $object . '" class="link-button"><img src="assets/icons/mail.svg" alt="chiedi informazioni" /></a></td>';
                    }
                    $html .='</tr>';
                }
            }else{
                $html .= '
                    <tr>
                        <td colspan="5" class="nessun-risultato-message" >Nessuna richiesta trovata con i filtri selezionati.</td>
                    </tr>
                ';
            
            }

        $html .= '
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5">Totale ' . $tipoMinuscoloPlurale . ' segnalati</td>
                    <td>' . htmlspecialchars($NSegnalazioniByType) . '</td>
                </tr>
            </tfoot>
        </table>';
        return $html;
    }


    $cani_content = "";
    $gatti_content = "";
    $linkAttivi = '';
    $NSegnalazioniCani = '';
    $NSegnalazioniGatti = '';
    $perPagina = 8; 
    $tipoAttivo = $_GET['tipo'] ?? 'Cani';
    $paginaCorrente = max(1, (int)($_GET['page'] ?? 1));
    $offset = ($paginaCorrente - 1) * $perPagina;
    $connessione = new DBAccess();
    $connessioneOK = $connessione->openDBConnection();
    $filtroCorrente = (isset($_GET['assegnate']) && $_GET['assegnate'] !== '') ? $_GET['assegnate'] : 'tutte';

    if ($connessioneOK) {
        $NSegnalazioniByType = $connessione->getNSegnalazioni($_SESSION['email']); 
        $NSegnalazioniCani = $NSegnalazioniByType['Cane'];
        $NSegnalazioniGatti = $NSegnalazioniByType['Gatto'];

        $offCani = ($tipoAttivo === 'Cani') ? $offset : 0;
        $offGatti = ($tipoAttivo === 'Gatti') ? $offset : 0;
        $animaliSegnalati = $connessione->getDetailsSegnalazioniAnimalsPaged(
            $perPagina, 
            $offCani, 
            $offGatti, 
            ($filtroCorrente === 'mie' || $filtroCorrente === 'tutte') ? $_SESSION['email'] : null, 
            $filtroCorrente
        );
        //per prendere il nome dell'admin da mettere nella mail!!
        $nome_admin= ($connessione->findAdminByEmail($_SESSION['email']))['nome']; 

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assegna_segnalazione'])) {
            $connessione->assignAdminToSegnalazione($_POST['id_segnalazione'], $_SESSION['email']);
            $tipoAttivo = $_POST['tipo']=='Cane'?'Cani':'Gatti';
            header("Location: ./nuove-accoglienze?tipo=$tipoAttivo&page=$paginaCorrente");
            exit;
        }

        if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['elimina_segnalazione'])) {
            $connessione->deleteSegnalazione($_POST['id_segnalazione']);
            $tipoAttivo = $_POST['tipo']=='Cane'?'Cani':'Gatti';
            header("Location: ./nuove-accoglienze?tipo=$tipoAttivo&page=$paginaCorrente");
            exit;
        }
        
        $connessione->closeConnection();
    }

    $pagineCani = (int)ceil($NSegnalazioniCani / $perPagina);
    $pagineGatti = (int)ceil($NSegnalazioniGatti / $perPagina);
    
    $linkCani = $pagineCani>1 ? "<nav class='next-page-links' aria-label='Pagine cani' id='nav-sotto'>
                <ul>".buildPagination(
    ($tipoAttivo === 'Cani' ? $paginaCorrente : 1), 
    $pagineCani, 
    'Cani'
)."</ul></nav>" : '';

    $linkGatti = $pagineGatti>1 ? "<nav class='next-page-links' aria-label='Pagine gatti' id='nav-sotto'>
                <ul>".buildPagination(
    ($tipoAttivo === 'Gatti' ? $paginaCorrente : 1), 
    $pagineGatti, 
    'Gatti'
)."</ul></nav>" : '';
    $linkAttivi = ($tipoAttivo === 'Gatti') ? $linkGatti : $linkCani;

    $cani_content = renderAnimalContent('Cane',$animaliSegnalati['Cane'], $NSegnalazioniCani, $nome_admin);
    $gatti_content = renderAnimalContent('Gatto',$animaliSegnalati['Gatto'],$NSegnalazioniGatti, $nome_admin);
    
    
    $paginaHTML = loadTemplate('./src/template/layout-admin.html', '<p>Errore: template layout.html non trovato o non leggibile.</p>');
    $breadcrumb = getBreadcrumb('nuove-accoglienze', $pagine);
    $nav = buildAdminNav($adminMenu,'./nuove-accoglienze');
    
    $main = loadTemplate('./src/template/main/admin/nuove-accoglienze.html');
    $main = str_replace('[tabs-animali]', renderCaniGattiTabs(), $main);
    $main = str_replace('[contenuto-cani]', $cani_content, $main);
    $main = str_replace('[contenuto-gatti]', $gatti_content, $main);
    $main = str_replace('[n-cani]', $NSegnalazioniCani, $main);
    $main = str_replace('[n-gatti]', $NSegnalazioniGatti, $main);

    $main = str_replace('[LINKPAGINE-CANI]', $linkCani, $main);
    $main = str_replace('[LINKPAGINE-GATTI]', $linkGatti, $main);

    $rawFilters = [
        'assegnate' => (isset($_GET['assegnate']) && $_GET['assegnate'] !== '') ? $_GET['assegnate'] : 'tutte',
    ];

    $replaceFilters = [
        '[ASSEGNATE_SELECTED_TUTTE]'   => $rawFilters['assegnate'] === 'tutte' ? 'selected' : '',
        '[ASSEGNATE_SELECTED_MIE]'     => $rawFilters['assegnate'] === 'mie' ? 'selected' : '',
        '[ASSEGNATE_SELECTED_NESSUNO]' => $rawFilters['assegnate'] === 'nessuno' ? 'selected' : '',
    ];

    $main = str_replace(array_keys($replaceFilters), array_values($replaceFilters), $main);

    $title = "<title>Segnalazioni accoglienze - Amministratore PetMatch</title>";
    $description = "<meta name='description' content='Pagina di gestione delle segnalazioni per nuove accoglienze di animali effettuate dagli utenti registrati'>";
    $keywords = "<meta name='keywords' content='signalazioni, amministratore, animali, rifugio, accoglienze, PetMatch'>";
    $paginaHTML = str_replace('[title]', $title, $paginaHTML);
    $paginaHTML = str_replace('[description]', $description, $paginaHTML);
    $paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
    $paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
    $paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
    $paginaHTML = str_replace('[main]', $main, $paginaHTML);
    
    echo $paginaHTML;
}

?>