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
        if(isset($_GET['tipo'])){
            //se ha valore Nuova, In valutazione, Da trasportare, Annullata, Respinta
            $stato = $_GET['tipo'];
            $selected = [
                'Cani' => '',
                'Gatti' => '',
            ];
            $checked = [
                'Cani' => '',
                'Gatti' => ''
            ];
            if(array_key_exists($stato, $selected)){
                $checked[$stato] = 'checked';
                $selected[$stato] = 'selected';
            }
            $html = '
            <select id="mobile-select" name="tab-group">
                <option value="tab1" '.$selected['Cani'].'>
                    Cani ([n-cani])
                </option>
                <option value="tab2" '.$selected['Gatti'].'>
                    Gatti ([n-gatti])
                </option>
            </select>
            <input class="sr-only" type="radio" id="tab1" name="tab-group" '.$checked['Cani'].'>
            <label for="tab1"><h2>Cani ([n-cani])</h2></label>
            <input class="sr-only" type="radio" id="tab2" name="tab-group" '.$checked['Gatti'].'>
            <label for="tab2"><h2>Gatti ([n-gatti])</h2></label>';
        }else{
            $html = '
            <select id="mobile-select" name="tab-group">
                <option value="tab1" selected>
                    Cani ([n-cani])
                </option>
                <option value="tab2">
                    Gatti ([n-gatti])
                </option>
            </select>

            <input class="sr-only" type="radio" id="tab1" name="tab-group" checked>
            <label for="tab1"><h2>Cani ([n-cani])</h2></label>
            <input class="sr-only" type="radio" id="tab2" name="tab-group">
            <label for="tab2"><h2>Gatti ([n-gatti])</h2></label>';
        }

        return $html;
        
    }
    function renderCaniContent(array $CaniNonAdmin, array $NNonAdminByType): string {
        if($NNonAdminByType['Cane'] == 0){
            return '<p class="nessuna-richiesta-message">Nessun cane senza amministratore</p>';
        }else{
            $html = '
            <span id="sumTabellaCani" class="navigationHelp">In questa tabella vengono elencate le nuove richieste di adozione e i loro dettagli: email utente, nome animale e data di richiesta.</span>
            <table aria-describedby="sumTabellaCani">
                <caption>Nuove Richieste di Adozione</caption>
                    <thead>
                        <tr>
                            <th scope="col"><abbr title="Identificativo animale">ID</abbr></th>
                            <th scope="col">Nome</th>
                            <th scope="col">Data registrazione</th>
                            <th scope="col">Trasporto</th>
                            <th scope="col">Razza</th>
                            <th scope="col">Età</th>
                            <th scope="col" class="col-dettagli"></th>
                        </tr>
                    </thead>
                    <tbody>';
            foreach($CaniNonAdmin as $caneNonAdmin){
                //calcolo età da data di nascita
                $eta = date_diff(date_create($caneNonAdmin['data_nascita']), date_create('today'))->y;
                $html .= '
                    <tr>
                        <th data-title="Identificativo animale" scope="row">'.htmlspecialchars($caneNonAdmin['id_animale']).'</th>
                        <td data-title="Nome animale">'.htmlspecialchars($caneNonAdmin['nome_animale']).'</td>
                        <td data-title="Data registrazione"><time datetime="'.htmlspecialchars($caneNonAdmin['data_registrazione']).'">'.htmlspecialchars(date('d/m/Y', strtotime($caneNonAdmin['data_registrazione']))).'</time></td>
                        <td data-title="Idoneo al trasporto">'.htmlspecialchars($caneNonAdmin['trasporto_animale']).'</td>
                        <td data-title="Razza animale">'.htmlspecialchars($caneNonAdmin['razza_animale']).'</td>
                        <td data-title="Età animale">'.htmlspecialchars($eta).'</td>
                        <td class="col-dettagli"><a href="animali?id='.htmlspecialchars($caneNonAdmin['id_animale']).'" class="brown-button">Vai all\'animale</a></td>
                    </tr>
                ';
            }
            $html .= '
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6">Totale cani senza admin</td>
                        <td>[n-cani]</td>
                    </tr>
                </tfoot>
            </table>';
            return $html;
        }
    }

    function renderGattiContent(array $GattiNonAdmin,array $NNonAdminByType){
        if($NNonAdminByType['Gatto'] == 0){
            return '<p class="nessuna-richiesta-message">Nessun gatto senza amministratore</p>';
        }else{
            $html = '
            <span id="sumTabellaInValutazione" class="navigationHelp">In questa tabella vengono elencate le richieste di adozione in valutazione e i loro dettagli: email utente, nome animale, data di inizio valutazione e presenza di appunti.</span>
            <table aria-describedby="sumTabellaInValutazione">
                <caption>Richieste di Adozione in Valutazione</caption>
                <thead>
                    <tr>
                        <th scope="col"><abbr title="Identificativo animale">ID</abbr></th>
                        <th scope="col">Nome</th>
                        <th scope="col">Data registrazione</th>
                        <th scope="col">Trasporto</th>
                        <th scope="col">Razza</th>
                        <th scope="col">Età</th>
                        <th scope="col" class="col-dettagli"></th>
                    </tr>
                </thead>
                <tbody>';

            foreach($GattiNonAdmin as $GattoNonAdmin){
                $eta = date_diff(date_create($GattoNonAdmin['data_nascita']), date_create('today'))->y;

                $html .= '
                    <tr>
                        <th data-title="Identificativo animale" scope="row">'.htmlspecialchars($GattoNonAdmin['id_animale']).'</th>
                        <td data-title="Nome animale">'.htmlspecialchars($GattoNonAdmin['nome_animale']).'</td>
                        <td data-title="Data registrazione"><time datetime="'.htmlspecialchars($GattoNonAdmin['data_registrazione']).'">'.htmlspecialchars(date('d/m/Y', strtotime($GattoNonAdmin['data_registrazione']))).'</time></td>
                        <td data-title="Idoneo al trasporto">'.htmlspecialchars($GattoNonAdmin['trasporto_animale']).'</td>
                        <td data-title="Razza animale">'.htmlspecialchars($GattoNonAdmin['razza_animale']).'</td>
                        <td data-title="Età animale">'.htmlspecialchars($eta).'</td>
                        <td class="col-dettagli"><a href="animali?id='.htmlspecialchars($GattoNonAdmin['id_animale']).'" class="brown-button">Vai all\'animale</a></td>
                    </tr>
                ';
            }
        
            $html .= '
            </tbody>
                <tfoot>
                    <tr>
                        <td colspan="6">Totale gatti senza admin</td>
                        <td>[n-gatti]</td>
                    </tr>
                </tfoot>
            </table>';
            return $html;
        }
    }

    function renderNNonAdminByType(array $NNonAdminByType, string $main): string {
        $main = str_replace('[n-cani]', $NNonAdminByType['Cane'], $main);
        $main = str_replace('[n-gatti]', $NNonAdminByType['Gatto'], $main);
        return $main;
    }

    
    $cani_content = "";
    $gatti_content = "";
    $NNonAdminByType = [];
    $linkAttivi = '';
    $perPagina = 8; //8 per pagina? a me sembra un buon numero
    $tipoAttivo = $_GET['tipo'] ?? 'Cani';
    $paginaCorrente = max(1, (int)($_GET['page'] ?? 1));
    $offset = ($paginaCorrente - 1) * $perPagina;
    $connessione = new DBAccess();
    $connessioneOK = $connessione->openDBConnection();
    
    if ($connessioneOK) {
        $NNonAdminByType = $connessione->getNNonAdminByType(); 

        $offCani = ($tipoAttivo === 'Cani') ? $offset : 0;
        $offGatti = ($tipoAttivo === 'Gatti') ? $offset : 0;

        $animali = $connessione->getDetailsNonAdminAnimalsPaged($perPagina, $offCani, $offGatti);

        
        $connessione->closeConnection();
    }

    $pagineCani = (int)ceil($NNonAdminByType['Cane'] / $perPagina);
    $pagineGatti = (int)ceil($NNonAdminByType['Gatto'] / $perPagina);
    
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

    $cani_content = renderCaniContent($animali['Cane'], $NNonAdminByType);
    $gatti_content = renderGattiContent($animali['Gatto'],$NNonAdminByType);
    
    
    $paginaHTML = loadTemplate('./src/template/layout-admin.html', '<p>Errore: template layout.html non trovato o non leggibile.</p>');
    $breadcrumb = getBreadcrumb('senza-amministratore', $pagine);
    $nav = buildAdminNav($adminMenu,'./senza-amministratore');
    
    $main = loadTemplate('./src/template/main/admin/senza-amministratore.html');
    $main = str_replace('[tabs-animali]', renderTabs(), $main);
    $main = str_replace('[contenuto-cani]', $cani_content, $main);
    $main = str_replace('[contenuto-gatti]', $gatti_content, $main);
    $main = renderNNonAdminByType($NNonAdminByType, $main);
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