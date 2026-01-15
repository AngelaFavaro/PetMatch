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


    function renderTabs(): string{
        $html = '';
        if(isset($_GET['stato'])){
            //se ha valore Nuova, In valutazione, Da trasportare, Annullata, Respinta
            $stato = $_GET['stato'];
            $selected = [
                'Nuove' => '',
                'In valutazione' => '',
                'Da trasportare' => '',
                'Annullata' => '',
                'Respinta' => ''
            ];
            $checked = [
                'Nuove' => '',
                'In valutazione' => '',
                'Da trasportare' => '',
                'Annullata' => '',
                'Respinta' => ''
            ];
            if(array_key_exists($stato, $selected)){
                $checked[$stato] = 'checked';
                $selected[$stato] = 'selected';
            }
            $html = '
            <select id="mobile-select" name="tab-group">
                <option value="tab1" '.$selected['Nuove'].'>
                    Nuove ([n-nuove])
                </option>
                <option value="tab2" '.$selected['In valutazione'].'>
                    In valutazione ([n-in-valutazione])
                </option>
                <option value="tab3" '.$selected['Da trasportare'].'>
                    Da trasportare ([n-da-trasportare])
                </option>
                <option value="tab4" '.$selected['Annullata'].'>
                    Annullate ([n-annullate])
                </option>
                <option value="tab5" '.$selected['Respinta'].'>
                    Respinte ([n-respinte])
                </option>
            </select>
            <input class="sr-only" type="radio" id="tab1" name="tab-group" '.$checked['Nuove'].'>
            <label for="tab1"><h2>Nuove ([n-nuove])</h2></label>
            <input class="sr-only" type="radio" id="tab2" name="tab-group" '.$checked['In valutazione'].'>
            <label for="tab2"><h2>In valutazione ([n-in-valutazione])</h2></label>
            <input class="sr-only" type="radio" id="tab3" name="tab-group" '.$checked['Da trasportare'].'>
            <label for="tab3"><h2>Da trasportare ([n-da-trasportare])</h2></label>
            <input class="sr-only" type="radio" id="tab4" name="tab-group" '.$checked['Annullata'].'>
            <label for="tab4"><h2>Annullate ([n-annullate])</h2></label>
            <input class="sr-only" type="radio" id="tab5" name="tab-group" '.$checked['Respinta'].'>
            <label for="tab5"><h2>Respinte ([n-respinte])</h2></label> '; 
        }else{
            $html = '
            <select id="mobile-select" name="tab-group">
                <option value="tab1" selected>
                    Nuove ([n-nuove])
                </option>
                <option value="tab2">
                    In valutazione ([n-in-valutazione])
                </option>
                <option value="tab3">
                    Da trasportare ([n-da-trasportare])
                </option>
                <option value="tab4">
                    Annullate ([n-annullate])
                </option>
                <option value="tab5">
                    Respinte ([n-respinte])
                </option>
            </select>

            <input class="sr-only" type="radio" id="tab1" name="tab-group" checked>
            <label for="tab1"><h2>Nuove ([n-nuove])</h2></label>
            <input class="sr-only" type="radio" id="tab2" name="tab-group">
            <label for="tab2"><h2>In valutazione ([n-in-valutazione])</h2></label>
            <input class="sr-only" type="radio" id="tab3" name="tab-group">
            <label for="tab3"><h2>Da trasportare ([n-da-trasportare])</h2></label>
            <input class="sr-only" type="radio" id="tab4" name="tab-group">
            <label for="tab4"><h2>Annullate ([n-annullate])</h2></label>
            <input class="sr-only" type="radio" id="tab5" name="tab-group">
            <label for="tab5"><h2>Respinte ([n-respinte])</h2></label> ';
        }

        return $html;
        
    }
    function renderTbodyNuove(DBAccess $conn, array $NRequestsByStatus): string {
        if($NRequestsByStatus['Nuova'] == 0){
            return '<tbody><tr><td colspan="4">Non ci sono nuove richieste di adozione.</td></tr></tbody>';
        }else{
            $html = '<tbody>';
            $richieste = $conn->getNewRequests($_SESSION['email'] ?? '');
            foreach($richieste as $richiesta){
                $html .= '
                    <tr>
                        <th data-title="Nome Animale" scope="row">'.htmlspecialchars($richiesta['nome_animale']).'</th>
                        <td data-title="Email Richiedente">'.htmlspecialchars($richiesta['email_richiedente']).'</td>
                        <td data-title="Data Richiesta"><time datetime="'.htmlspecialchars($richiesta['data_richiesta']).'">'.htmlspecialchars(date('d/m/Y', strtotime($richiesta['data_richiesta']))).'</time></td>
                        <td class="col-dettagli"><a href="?email='.urlencode($richiesta['email_richiedente']).'&id-animale='.urlencode($richiesta['id_animale']).'" class="orange-button">Vai ai dettagli</a></td>
                    </tr>
                ';
            }
            $html .= '</tbody>';
            return $html;
        }
    }

    function renderTbodyInValutazione(DBAccess $conn){
        $html = '<tbody>';
        $richieste = $conn->getInEvaluationRequests($_SESSION['email'] ?? '');

        foreach($richieste as $richiesta){
            $html .= '
                <tr>
                    <th data-title="Nome Animale" scope="row">'.htmlspecialchars($richiesta['nome_animale']).'</th>
                    <td data-title="Email Richiedente">'.htmlspecialchars($richiesta['email_richiedente']).'</td>
                    <td data-title="Data Inizio Valutazione"><time datetime="'.htmlspecialchars($richiesta['data_inizio_valutazione']).'">'.htmlspecialchars(date('d/m/Y', strtotime($richiesta['data_inizio_valutazione']))).'</time></td>
                    <td data-title="Appunti">'.($richiesta['appunti'] ? 'Sì' : 'No').'</td>
                    <td class="col-dettagli"><a href="?email='.urlencode($richiesta['email_richiedente']).'&id-animale='.urlencode($richiesta['id_animale']).'" class="orange-button">Vai ai dettagli</a></td>
                </tr>
            ';
        }
        $html .= '</tbody>';
        return $html;
    }

    function renderTbodyDaTrasportare(DBAccess $conn){
        $html = '<tbody>';
        $richieste = $conn->getTransportRequests($_SESSION['email'] ?? '');

        foreach($richieste as $richiesta){
            // se data_arrivo è null, mostra una stringa vuota
            $html .= '
                <tr>
                    <th data-title="Nome Animale" scope="row">'.htmlspecialchars($richiesta['nome_animale']).'</th>
                    <td data-title="Email Richiedente">'.htmlspecialchars($richiesta['email_richiedente']).'</td>
                    <td data-title="Data accettazione"><time datetime="'.htmlspecialchars($richiesta['data_fine_valutazione']).'">'.htmlspecialchars(date('d/m/Y', strtotime($richiesta['data_fine_valutazione']))).'</time></td>
                    <td data-title="Data arrivo">'.($richiesta['data_arrivo'] ? '<time datetime="'.htmlspecialchars($richiesta['data_arrivo']).'">'.date('d/m/Y', strtotime($richiesta['data_arrivo'])).'</time>' : '<span>Da pianificare</span>').'</td>
                    <td class="col-dettagli"><a href="?email='.urlencode($richiesta['email_richiedente']).'&id-animale='.urlencode($richiesta['id_animale']).'" class="orange-button">Vai ai dettagli</a></td>
                </tr>
            ';
        }
        $html .= '</tbody>';
        return $html;

    }

    function renderTbodyAnnullate(DBAccess $conn){
        $html = '<tbody>';
        $richieste = $conn->getCancelledRequests($_SESSION['email'] ?? '');

        foreach($richieste as $richiesta){
            $html .= '
                <tr>
                    <th data-title="Nome Animale" scope="row">'.htmlspecialchars($richiesta['nome_animale']).'</th>
                    <td data-title="Email Richiedente">'.htmlspecialchars($richiesta['email_richiedente']).'</td>
                    
                    <td data-title="Data annullamento"><time datetime="'.htmlspecialchars($richiesta['data_fine_valutazione']).'">'.htmlspecialchars(date('d/m/Y', strtotime($richiesta['data_fine_valutazione']))).'</time></td>
                    <td class="col-dettagli"><a href="?email='.urlencode($richiesta['email_richiedente']).'&id-animale='.urlencode($richiesta['id_animale']).'" class="orange-button">Vai ai dettagli</a></td>
                </tr>
            ';
        }
        $html .= '</tbody>';
        return $html;
    }

    //ora per le respinte, che hanno solo nome animale e email richiedente
    function renderTbodyRespinte(DBAccess $conn){
        $html = '<tbody>';
        $richieste = $conn->getRejectedRequests($_SESSION['email'] ?? '');

        foreach($richieste as $richiesta){
            $html .= '
                <tr>
                    <th data-title="Nome Animale" scope="row">'.htmlspecialchars($richiesta['nome_animale']).'</th>
                    <td data-title="Email Richiedente">'.htmlspecialchars($richiesta['email_richiedente']).'</td>
                    <td class="col-dettagli"><a href="?email='.urlencode($richiesta['email_richiedente']).'&id-animale='.urlencode($richiesta['id_animale']).'" class="orange-button">Vai ai dettagli</a></td>
                </tr>
            ';
        }
        $html .= '</tbody>';
        return $html;
    }
    function renderNRequestsByStatus(array $NRequestsByStatus, string $main): string {
        $main = str_replace('[n-nuove]', $NRequestsByStatus['Nuova'], $main);
        $main = str_replace('[n-in-valutazione]', $NRequestsByStatus['In valutazione'], $main);
        $main = str_replace('[n-da-trasportare]', $NRequestsByStatus['Da trasportare'], $main);
        $main = str_replace('[n-annullate]', $NRequestsByStatus['Annullata'], $main);
        $main = str_replace('[n-respinte]', $NRequestsByStatus['Respinta'], $main);
        return $main;
    }

    
    $tbody_nuove_richieste = "";
    $tbody_in_valutazione_richieste = "";
    $tbody_da_trasportare_richieste = "";
    $tbody_annullate_richieste = "";
    $tbody_respinte_richieste = "";
    $NRequestsByStatus = [];
    
    $connessione = new DBAccess();
    $connessioneOK = $connessione->openDBConnection();
    
    if ($connessioneOK) {
        $richiesta = $connessione->getRequestDetails($email, $idAnimale);

        // Gestione POST centralizzata (esegue redirect dove necessario)
        $NRequestsByStatus = $connessione->getNRequestByStatus($_SESSION['email'] ?? '');
        $tbody_nuove_richieste = renderTbodyNuove($connessione, $NRequestsByStatus);
        $tbody_in_valutazione_richieste = renderTbodyInValutazione($connessione);
        $tbody_da_trasportare_richieste = renderTbodyDaTrasportare($connessione);
        $tbody_annullate_richieste = renderTbodyAnnullate($connessione);
        $tbody_respinte_richieste = renderTbodyRespinte($connessione);
        $connessione->closeConnection();
    }

    
    
    // echo 'Qui ci va la pagina delle richieste di adozione, quando metti "<h1>?email=lindorlinor@gmail.com&id-animale=1</h1>" ti apre la singola richiesta (obv metti i valori che vuoi nei parametri)';
    
    $paginaHTML = loadTemplate('./src/template/layout-admin.html', '<p>Errore: template layout.html non trovato o non leggibile.</p>');
    $breadcrumb = getBreadcrumb('richieste-adozione', $pagine);
    $nav = buildAdminNav($adminMenu,'./richieste-adozione');
    
    $main = loadTemplate('./src/template/main/admin/richieste-adozione.html');
    $main = str_replace('[tab-richieste-adozione]', renderTabs(), $main);
    $main = str_replace('[tbody-nuove-richieste]', $tbody_nuove_richieste, $main);
    $main = str_replace('[tbody-in-valutazione-richieste]', $tbody_in_valutazione_richieste, $main);
    $main = str_replace('[tbody-da-trasportare-richieste]', $tbody_da_trasportare_richieste, $main);
    $main = str_replace('[tbody-annullate-richieste]', $tbody_annullate_richieste, $main);
    $main = str_replace('[tbody-respinte-richieste]', $tbody_respinte_richieste, $main);
    $main = renderNRequestsByStatus($NRequestsByStatus, $main);
    $title = "<title>Richieste di Adozione - PetMatch</title>";
    $description = "<meta name='description' content='Visualizza e gestisci le richieste di adozione degli animali presenti su PetMatch.'>";
    $keywords = "<meta name='keywords' content='richieste, adozione, animali, PetMatch'>";
    $paginaHTML = str_replace('[title]', $title, $paginaHTML);
    $paginaHTML = str_replace('[description]', $description, $paginaHTML);
    $paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
    $paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
    $paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
    $paginaHTML = str_replace('[main]', $main, $paginaHTML);
    
    echo $paginaHTML;
}

?>