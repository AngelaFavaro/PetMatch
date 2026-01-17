<?php

use DB\DBAccess;
session_start();

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
        $tipoMinuscoloPlurale = ($tipo === 'Cane') ? 'cani' : 'gatti'; //fa un po caca ma va bene per ora
        
        if (($NSegnalazioniByType ?? 0) == 0) {
            return '<p class="nessuna-richiesta-message">Nessuna segnalazione per ' . $tipoMinuscoloPlurale . '.</p>';
        }

        $idTabella = "sumTabella" . $tipo;
        $html = '
            <span id="' . $idTabella . '" class="navigationHelp">In questa tabella vengono elencate le nuove richieste di adozione per ' . $tipoMinuscoloPlurale . ' e i loro dettagli: email utente, nome animale e data di richiesta.</span>
            <table aria-describedby="' . $idTabella . '">
                <caption>Nuove Richieste di Adozione (' . $tipo . ')</caption>
                <thead>
                    <tr>
                        <th scope="col"><abbr title="Identificativo segnalazione">ID</abbr></th>
                        <th scope="col">Data segnalazione</th>
                        <th scope="col">Nominativo segnalante</th>
                        <th scope="col">Email segnalante</th>
                        <th scope="col" class="col-dettagli"></th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($animaliSegnalati as $animale) {
            $subject= rawurlencode('PetMatch - Hai bisogno di trovare casa al tuo amico a quattro zampe?');

            $messaggio = "Ciao! Ho visto la tua segnalazione su PetMatch per un " . strtolower($tipo) . " e siamo interessati a raccogliere maggiori informazioni riguardo al tuo animale.\n\n" .
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
            "Amministratore PetMatch";

            $object =rawurlencode($messaggio);
            $html .='
                <tr>
                    <th data-title="Identificativo segnalazione" scope="row">' . htmlspecialchars($animale['id_segnalazione']) . '</th>
                    <td data-title="Data segnalazione"><time datetime="' . htmlspecialchars($animale['data_segnalazione']) . '">' . htmlspecialchars(date('d/m/Y', strtotime($animale['data_segnalazione']))) . '</time></td>
                    <td data-title="Nominativo segnalante">' . htmlspecialchars($animale['nominativo_segnalante']) . '</td>
                    <td data-title="Email segnalante">' . htmlspecialchars($animale['email_segnalante']) . '</td>
                    <td class="col-dettagli"><a href="mailto:' . htmlspecialchars($animale['email_segnalante']) . '?subject=' . $subject . '&body=' . $object . '" class="brown-button">Chiedi informazioni</a></td>
                </tr>';
        }

        $html .= '
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4">Totale ' . $tipoMinuscoloPlurale . ' senza admin</td>
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
    $perPagina = 8; //8 per pagina? a me sembra un buon numero
    $tipoAttivo = $_GET['tipo'] ?? 'Cani';
    $paginaCorrente = max(1, (int)($_GET['page'] ?? 1));
    $offset = ($paginaCorrente - 1) * $perPagina;
    $connessione = new DBAccess();
    $connessioneOK = $connessione->openDBConnection();
    
    if ($connessioneOK) {
        $NSegnalazioniByType = $connessione->getNSegnalazioni($_SESSION['email']); 
        $NSegnalazioniCani = $NSegnalazioniByType['no-admin-cani'];
        $NSegnalazioniGatti = $NSegnalazioniByType['no-admin-gatti'];

        $offCani = ($tipoAttivo === 'Cani') ? $offset : 0;
        $offGatti = ($tipoAttivo === 'Gatti') ? $offset : 0;

        $animaliSegnalati = $connessione->getDetailsSegnalazioniAnimalsPaged($perPagina, $offCani, $offGatti);
        $nome_admin= ($connessione->findAdminByEmail($_SESSION['email']))['nome']; //per prendere il nome dell'admin da mettere nella mail!!

        
        $connessione->closeConnection();
    }

    $pagineCani = (int)ceil($NSegnalazioniCani / $perPagina);
    $pagineGatti = (int)ceil($NSegnalazioniGatti / $perPagina);
    
    $linkCani = buildPagination(
        ($tipoAttivo === 'Cani' ? $paginaCorrente : 1), 
        $pagineCani, 
        'Cani'
    );

    $linkGatti = buildPagination(
        ($tipoAttivo === 'Gatti' ? $paginaCorrente : 1), 
        $pagineGatti, 
        'Gatti'
    );
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

    /* ---- da completare quando aggiungerò i filtri (SE)---*/
    /*$rawFilters = [
        'appunti'   => $_GET['appunti'] ?? '',
        'trasporto' => $_GET['trasporto'] ?? '',
    ];


    $replaceFilters = [
        '[APPUNTI_SELECTED_TUTTI]'   => $rawFilters['appunti'] === '' ? 'selected' : '',
        '[APPUNTI_SELECTED_SI]' => $rawFilters['appunti'] === '1' ? 'selected' : '',
        '[APPUNTI_SELECTED_NO]'   => $rawFilters['appunti'] === '0' ? 'selected' : '',
        '[TRASPORTO_SELECTED_TUTTI]' => $rawFilters['trasporto'] === '' ? 'selected' : '',
        '[TRASPORTO_SELECTED_ORGANIZZATO]' => $rawFilters['trasporto'] === '1' ? 'selected' : '',
        '[TRASPORTO_SELECTED_DA_ORGANIZZARE]'   => $rawFilters['trasporto'] === '0' ? 'selected' : '',
    ];

    $main = str_replace(array_keys($replaceFilters), array_values($replaceFilters), $main);*/

    $title = "<title>Animali senza admin - PetMatch</title>";
    $description = "<meta name='description' content='Pagina di gestione delle richieste di adozione per animali senza amministratore in PetMatch.'>";
    $keywords = "<meta name='keywords' content='richieste, amministratore, animali, PetMatch'>";
    $paginaHTML = str_replace('[title]', $title, $paginaHTML);
    $paginaHTML = str_replace('[description]', $description, $paginaHTML);
    $paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
    $paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
    $paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
    $paginaHTML = str_replace('[main]', $main, $paginaHTML);
    
    echo $paginaHTML;
}

?>