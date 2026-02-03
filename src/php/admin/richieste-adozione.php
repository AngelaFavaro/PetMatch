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
            <input class="sr-only" type="radio" id="tab1" name="tab-group" '.$checked['Nuove'].'/>
            <label for="tab1">Nuove ([n-nuove])</label>
            <input class="sr-only" type="radio" id="tab2" name="tab-group" '.$checked['In valutazione'].'/>
            <label for="tab2">In valutazione ([n-in-valutazione])</label>
            <input class="sr-only" type="radio" id="tab3" name="tab-group" '.$checked['Da trasportare'].'/>
            <label for="tab3">Da trasportare ([n-da-trasportare])</label>
            <input class="sr-only" type="radio" id="tab4" name="tab-group" '.$checked['Annullata'].'/>
            <label for="tab4">Annullate ([n-annullate])</label>
            <input class="sr-only" type="radio" id="tab5" name="tab-group" '.$checked['Respinta'].'/>
            <label for="tab5">Respinte ([n-respinte])</label> '; 
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

            <input class="sr-only" type="radio" id="tab1" name="tab-group" checked/>
            <label for="tab1">Nuove ([n-nuove])</label>
            <input class="sr-only" type="radio" id="tab2" name="tab-group"/>
            <label for="tab2">In valutazione ([n-in-valutazione])</label>
            <input class="sr-only" type="radio" id="tab3" name="tab-group"/>
            <label for="tab3">Da trasportare ([n-da-trasportare])</label>
            <input class="sr-only" type="radio" id="tab4" name="tab-group"/>
            <label for="tab4">Annullate ([n-annullate])</label>
            <input class="sr-only" type="radio" id="tab5" name="tab-group"/>
            <label for="tab5">Respinte ([n-respinte])</label> ';
        }

        return $html;
        
    }
    function renderNuoveContent(DBAccess $conn, array $NRequestsByStatus): string {
        if($NRequestsByStatus['Nuova'] == 0){
            return '<p class="nessun-risultato-message">Non ci sono nuove richieste di adozione.</p>';
        }else{
            $html = '
            <span id="sumTabellaNuove" aria-hidden="true" class="navigationHelp">In questa tabella vengono elencate le nuove richieste di adozione e i loro dettagli: email utente, nome animale e data di richiesta.</span>
            <table aria-describedby="sumTabellaNuove">
                <caption>Nuove Richieste di Adozione</caption>
                    <thead>
                        <tr>
                            <th scope="col">Animale</th>
                            <th scope="col">Utente</th>
                            <th scope="col">Data richiesta</th>
                            <th scope="col" class="col-dettagli" > <span class="sr-only">Dettagli</span></th>
                        </tr>
                    </thead>
                    <tbody>';
            $richieste = $conn->getNewRequests($_SESSION['email'] ?? '');
            foreach($richieste as $richiesta){
                $html .= '
                    <tr>
                        <th data-title="Nome Animale" scope="row">'.htmlspecialchars($richiesta['nome_animale']).'</th>
                        <td data-title="Email Richiedente">'.htmlspecialchars($richiesta['email_richiedente']).'</td>
                        <td data-title="Data Richiesta"><time datetime="'.htmlspecialchars($richiesta['data_richiesta']).'">'.htmlspecialchars(date('d/m/Y', strtotime($richiesta['data_richiesta']))).'</time></td>
                        <td class="col-dettagli"><a href="?email='.urlencode($richiesta['email_richiedente']).'&id-animale='.urlencode($richiesta['id_animale']).'" class="link-button">Vai ai dettagli</a></td>
                    </tr>
                ';
            }
            $html .= '
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3">Totale nuove richieste</td>
                        <td>[n-nuove]</td>
                    </tr>
                </tfoot>
            </table>';
            return $html;
        }
    }

    function renderInValutazioneContent(DBAccess $conn,array $NRequestsByStatus){
        if($NRequestsByStatus['In valutazione'] == 0){
            return '<p class="nessun-risultato-message">Non ci sono richieste di adozione in valutazione.</p>';
        }else{
            $html = '
            <span id="sumTabellaInValutazione" class="navigationHelp">In questa tabella vengono elencate le richieste di adozione in valutazione e i loro dettagli: <span lang="en">email</span> utente, nome animale, data di inizio valutazione e presenza di appunti.</span>
            <table aria-describedby="sumTabellaInValutazione">
                <caption>Richieste di Adozione in Valutazione</caption>
                <thead>
                    <tr>
                        <th scope="col">Animale</th>
                        <th scope="col">Utente</th>
                        <th scope="col">Data inizio valutazione</th>
                        <th scope="col">Appunti</th>
                        <th scope="col" class="col-dettagli"><span class="sr-only">Dettagli</span></th>
                    </tr>
                </thead>
                <tbody>';

            
            $richieste = $conn->getInEvaluationRequests($_SESSION['email'] ?? '',$_GET['appunti'] ?? null);

            if(!empty($richieste)){
                foreach($richieste as $richiesta){
                    $html .= '
                        <tr>
                            <th data-title="Nome Animale" scope="row">'.htmlspecialchars($richiesta['nome_animale']).'</th>
                            <td data-title="Email Richiedente">'.htmlspecialchars($richiesta['email_richiedente']).'</td>
                            <td data-title="Inizio Valutazione"><time datetime="'.htmlspecialchars($richiesta['data_inizio_valutazione']).'">'.htmlspecialchars(date('d/m/Y', strtotime($richiesta['data_inizio_valutazione']))).'</time></td>
                            <td data-title="Appunti">'.($richiesta['appunti'] ? 'Sì' : 'No').'</td>
                            <td class="col-dettagli"><a href="?email='.urlencode($richiesta['email_richiedente']).'&id-animale='.urlencode($richiesta['id_animale']).'" class="link-button">Vai ai dettagli</a></td>
                        </tr>
                    ';
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
                        <td colspan="4">Totale richieste in valutazione</td>
                        <td>[n-in-valutazione]</td>
                    </tr>
                </tfoot>
            </table>';
            return $html;
        }
    }

    function renderDaTrasportareContent(DBAccess $conn,array $NRequestsByStatus){
        if($NRequestsByStatus['Da trasportare'] == 0){
            return '<p class="nessun-risultato-message">Non ci sono richieste di adozione da trasportare.</p>';
        }else{
            $html = '
            <span id="sumTabellaDaTrasportare" aria-hidden="true" class="navigationHelp">In questa tabella vengono elencate le richieste di adozione da trasportare e i loro dettagli: <span lang="en">email</span> utente, nome animale, data di fine valutazione.</span>
            <table aria-describedby="sumTabellaDaTrasportare">
            <caption>Richieste di Adozione da Trasportare</caption>
                <thead>
                    <tr>
                        <th scope="col">Animali</th>
                        <th scope="col">Utente</th>
                        <th scope="col">Data accettazione</th>
                        <th scope="col">Data arrivo</th>
                        <th scope="col" class="col-dettagli"><span class="sr-only">Dettagli</span></th>
                    </tr>
                </thead>
            <tbody>';
            $richieste = $conn->getTransportRequests($_SESSION['email'] ?? '',$_GET['trasporto'] ?? null);

            if(!empty($richieste)){
                foreach($richieste as $richiesta){
                    $html .= '
                        <tr>
                            <th data-title="Nome Animale" scope="row">'.htmlspecialchars($richiesta['nome_animale']).'</th>
                            <td data-title="Email Richiedente">'.htmlspecialchars($richiesta['email_richiedente']).'</td>
                            <td data-title="Data accettazione"><time datetime="'.htmlspecialchars($richiesta['data_fine_valutazione']).'">'.htmlspecialchars(date('d/m/Y', strtotime($richiesta['data_fine_valutazione']))).'</time></td>
                            <td data-title="Data arrivo">'.($richiesta['data_arrivo'] ? '<time datetime="'.htmlspecialchars($richiesta['data_arrivo']).'">'.date('d/m/Y', strtotime($richiesta['data_arrivo'])).'</time>' : '<span>Da organizzare</span>').'</td>
                            <td class="col-dettagli"><a href="?email='.urlencode($richiesta['email_richiedente']).'&id-animale='.urlencode($richiesta['id_animale']).'" class="link-button">Vai ai dettagli</a></td>
                        </tr>';
                }
            }else{
                $html .= '
                    <tr>
                        <td colspan="5" class="nessun-risultato-message">Nessuna richiesta trovata con i filtri selezionati.</td>
                    </tr>
                ';
            }
            $html .= '
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4">Totale richieste da trasportare</td>
                        <td>[n-da-trasportare]</td>
                    </tr>
                </tfoot>
            </table>';
            return $html;
        }
    }

    function renderAnnullateContent(DBAccess $conn,array $NRequestsByStatus){
        if($NRequestsByStatus['Annullata'] == 0){
            return '<p class="nessun-risultato-message">Non ci sono richieste di adozione annullate.</p>';
        }else{
            $html = '
            <span id="sumTabellaAnnullate" aria-hidden="true" class="navigationHelp">In questa tabella vengono elencate le richieste di adozione annullate e i loro dettagli: <span lang="en">email</span> utente, nome animale e data di annullamento.</span>
                <table aria-describedby="sumTabellaAnnullate">
                    <caption>Richieste di Adozione Annullate</caption>
                    <thead>
                        <tr>
                            <th scope="col">Animali</th>
                            <th scope="col">Utente</th>
                            <th scope="col">DATA ANNULLAMENTO</th>
                            <th scope="col" class="col-dettagli"><span class="sr-only">Dettagli</span></th>
                        </tr>
                    </thead>
                    <tbody>';
            $richieste = $conn->getCancelledRequests($_SESSION['email'] ?? '');

            foreach($richieste as $richiesta){
                $html .= '
                    <tr>
                        <th data-title="Nome Animale" scope="row">'.htmlspecialchars($richiesta['nome_animale']).'</th>
                        <td data-title="Email Richiedente">'.htmlspecialchars($richiesta['email_richiedente']).'</td>
                        
                        <td data-title="Data annullamento"><time datetime="'.htmlspecialchars($richiesta['data_fine_valutazione']).'">'.htmlspecialchars(date('d/m/Y', strtotime($richiesta['data_fine_valutazione']))).'</time></td>
                        <td class="col-dettagli"><a href="?email='.urlencode($richiesta['email_richiedente']).'&id-animale='.urlencode($richiesta['id_animale']).'" class="link-button">Vai ai dettagli</a></td>
                    </tr>
                ';
            }
            $html .= '
                </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">Totale richieste annullate</td>
                            <td>[n-annullate]</td>
                        </tr>
                    </tfoot>
                </table>';
            return $html;
        }
    }

    //ora per le respinte, che hanno solo nome animale e email richiedente
    function renderRespinteContent(DBAccess $conn,array $NRequestsByStatus){
        if($NRequestsByStatus['Respinta'] == 0){
            return '<p class="nessun-risultato-message">Non ci sono richieste di adozione respinte.</p>';
        }else{
            $html = '
            <span id="sumTabellaRespinte" aria-hidden="true" class="navigationHelp">In questa tabella vengono elencate le richieste di adozione respinte e i loro dettagli: <span lang="en">email</span> utente, nome animale e data di respinta.</span>
            <table aria-describedby="sumTabellaRespinte">
                <caption>Richieste di Adozione Respinte</caption>
                <thead>
                    <tr>
                        <th scope="col">Animali</th>
                        <th scope="col">Utente</th>
                        <th scope="col" class="col-dettagli"><span class="sr-only">Dettagli</span></th>
                    </tr>
                </thead>
            <tbody>';
            $richieste = $conn->getRejectedRequests($_SESSION['email'] ?? '');

            foreach($richieste as $richiesta){
                $html .= '
                    <tr>
                        <th data-title="Nome Animale" scope="row">'.htmlspecialchars($richiesta['nome_animale']).'</th>
                        <td data-title="Email Richiedente">'.htmlspecialchars($richiesta['email_richiedente']).'</td>
                        <td class="col-dettagli"><a href="?email='.urlencode($richiesta['email_richiedente']).'&id-animale='.urlencode($richiesta['id_animale']).'" class="link-button">Vai ai dettagli</a></td>
                    </tr>
                ';
            }
            $html .= '
            </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">Totale richieste respinte</td>
                        <td>[n-respinte]</td>
                    </tr>
                </tfoot>
            </table>';
            return $html;
        }
    }
    function renderNRequestsByStatus(array $NRequestsByStatus, string $main): string {
        $main = str_replace('[n-nuove]', $NRequestsByStatus['Nuova'], $main);
        $main = str_replace('[n-in-valutazione]', $NRequestsByStatus['In valutazione'], $main);
        $main = str_replace('[n-da-trasportare]', $NRequestsByStatus['Da trasportare'], $main);
        $main = str_replace('[n-annullate]', $NRequestsByStatus['Annullata'], $main);
        $main = str_replace('[n-respinte]', $NRequestsByStatus['Respinta'], $main);
        return $main;
    }

    
    $nuove_richieste_content = "";
    $richieste_in_valutazione_content = "";
    $richieste_da_trasportare_content = "";
    $richieste_annullate_content = "";
    $richieste_respinte_content = "";
    $NRequestsByStatus = [];
    
    $connessione = new DBAccess();
    $connessioneOK = $connessione->openDBConnection();
    
    if ($connessioneOK) {
        $richiesta = $connessione->getRequestDetails($email, $idAnimale);
        $NRequestsByStatus = $connessione->getNRequestByStatus($_SESSION['email'] ?? '');
        $nuove_richieste_content = renderNuoveContent($connessione, $NRequestsByStatus);
        $richieste_in_valutazione_content = renderInValutazioneContent($connessione, $NRequestsByStatus);
        $richieste_da_trasportare_content = renderDaTrasportareContent($connessione, $NRequestsByStatus);
        $richieste_annullate_content = renderAnnullateContent($connessione, $NRequestsByStatus);
        $richieste_respinte_content = renderRespinteContent($connessione, $NRequestsByStatus);
        $connessione->closeConnection();
    }

    
    $paginaHTML = loadTemplate('./src/template/layout-admin.html', '<p>Errore: template layout.html non trovato o non leggibile.</p>');
    $breadcrumb = getBreadcrumb('richieste-adozione', $pagine);
    $nav = buildAdminNav($adminMenu,'./richieste-adozione');
    
    $main = loadTemplate('./src/template/main/admin/richieste-adozione.html');
    $main = str_replace('[tabs-richieste-adozione]', renderTabs(), $main);
    $main = str_replace('[contenuto-nuove-richieste]', $nuove_richieste_content, $main);
    $main = str_replace('[contenuto-in-valutazione-richieste]', $richieste_in_valutazione_content, $main);
    $main = str_replace('[contenuto-da-trasportare-richieste]', $richieste_da_trasportare_content, $main);
    $main = str_replace('[contenuto-annullate-richieste]', $richieste_annullate_content, $main);
    $main = str_replace('[contenuto-respinte-richieste]', $richieste_respinte_content, $main);
    $main = renderNRequestsByStatus($NRequestsByStatus, $main);


    $rawFilters = [
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




    $main = str_replace(array_keys($replaceFilters), array_values($replaceFilters), $main);
    $title = "<title>Richieste di Adozione - PetMatch</title>";
    $description = "<meta name='description' content='Un amministratore visualizza e gestisce le richieste di adozione degli animali presenti su PetMatch.'>";
    $keywords = "<meta name='keywords' content='visualizzazione, gestione, amministratore, richieste, adozione, animali, PetMatch'>";
    $paginaHTML = str_replace('[title]', $title, $paginaHTML);
    $paginaHTML = str_replace('[description]', $description, $paginaHTML);
    $paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
    $paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
    $paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
    $paginaHTML = str_replace('[main]', $main, $paginaHTML);
    
    echo $paginaHTML;
}

?>