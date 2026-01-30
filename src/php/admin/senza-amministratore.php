<?php

use DB\DBAccess;
session_start();

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) { //il primo controlla se esiste la variabile admin in session, la seconda controlla che sia affettivamente admin
    header("Location: ./accedi");
    exit;
}

include './src/utils.php';
include './src/DBconnection.php';

function renderCaniContent(array $CaniNonAdmin, array $NNonAdminByType): string {
    if($NNonAdminByType['Cane'] == 0){
        return '<p class="nessun-risultato-message">Nessun cane senza amministratore.</p>';
    }else{
        $html = '
        <span id="sumTabellaCaniNoAdmin" class="sr-only" aria-hidden="true">In questa tabella vengono elencate Identificativo cane, Nome cane, Data di registrazione, se è idoneo al trasporto, Razza cane, Età cane e per ogni cane un link alla scheda dettagli dell\'animale.</span>
        <table aria-describedby="sumTabellaCaniNoAdmin">
            <caption>Cani senza amministratore</caption>
                <thead>
                    <tr>
                        <th scope="col"><abbr title="Identificativo cane"><span lang="en">Id</span></abbr></th>
                        <th scope="col">Nome</th>
                        <th scope="col">Data registrazione</th>
                        <th scope="col">Trasporto</th>
                        <th scope="col">Razza</th>
                        <th scope="col">Età</th>
                        <th scope="col" class="col-dettagli"><span class="sr-only">Assegna a te</span></th>
                        <th scope="col" class="col-dettagli"><span class="sr-only">Dettagli cane</span></th>
                    </tr>
                </thead>
                <tbody>';
        foreach($CaniNonAdmin as $caneNonAdmin){
            //calcolo età da data di nascita
            $eta = date_diff(date_create($caneNonAdmin['data_nascita']), date_create('today'))->y;

            $id = htmlspecialchars($caneNonAdmin['id_animale']);
            $urlDettagli = "dettagli-animale?id-animale=" . $id . "&from=senza-admin";
            $trasportoSiNo = $caneNonAdmin['trasporto_animale']==1? 'Sì' : 'No';
            $html .= '
                <tr>
                    <th scope="row">'.htmlspecialchars($caneNonAdmin['id_animale']).'</th>
                    <td data-title="Nome">'.htmlspecialchars($caneNonAdmin['nome_animale']).'</td>
                    <td data-title="Data"><time datetime="'.htmlspecialchars($caneNonAdmin['data_registrazione']).'">'.htmlspecialchars(date('d/m/Y', strtotime($caneNonAdmin['data_registrazione']))).'</time></td>
                    <td data-title="Trasporto">'.htmlspecialchars($trasportoSiNo).'</td>
                    <td data-title="Razza">'.htmlspecialchars($caneNonAdmin['razza_animale']).'</td>
                    <td data-title="Età">'.htmlspecialchars($eta).'</td>
                    <td class="col-dettagli">
                        <form method="post" action="senza-amministratore" >
                            <input type="hidden" name="tipo" value="Cani"/>
                            <input type="hidden" name="id_animale" value="' . htmlspecialchars($caneNonAdmin['id_animale']) . '"/>
                            <button type="submit" name="assegnami_animale" class="orange-button">Assegna a me<span class="sr-only"> numero' . htmlspecialchars($caneNonAdmin['id_animale']) . '</span></button>
                        </form>
                    </td>
                            <td class="col-dettagli">
                        <a href="' . $urlDettagli . '" class="brown-button"> 
                            Vai all\'animale<span class="sr-only">'.htmlspecialchars($caneNonAdmin['nome_animale']).' </span>
                        </a>
                    </td>
                </tr>
            ';
        }
        $html .= '
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7">Totale cani senza admin</td>
                    <td>[n-cani]</td>
                </tr>
            </tfoot>
        </table>';
        return $html;
    }
}

function renderGattiContent(array $GattiNonAdmin,array $NNonAdminByType){
    if($NNonAdminByType['Gatto'] == 0){
        return '<p role="status" class="nessun-risultato-message">Nessun gatto senza amministratore.</p>';
    }else{
        
        $html = '
        <span id="sumTabellaGattiNoAdmin" class="sr-only" aria-hidden="true">In questa tabella vengono elencate Identificativo gatto, Nome gatto, Data di registrazione, se è idoneo al trasporto, Razza gatto, Età gatto e per ogni gatto un link alla scheda dettagli dell\'animale.</span>
        <table aria-describedby="sumTabellaGattiNoAdmin">
            <caption>Gatti senza amministratore</caption>
            <thead>
                <tr>
                    <th scope="col"><abbr title="Identificativo gatto"><span lang="en">Id</span></abbr></th>
                    <th scope="col">Nome</th>
                    <th scope="col">Data registrazione</th>
                    <th scope="col">Trasporto</th>
                    <th scope="col">Razza</th>
                    <th scope="col">Età</th>
                    <th scope="col" class="col-dettagli"><span class="sr-only">Assegna a te</span></th>
                    <th scope="col" class="col-dettagli"><span class="sr-only">Dettagli animale</span></th>
                </tr>
            </thead>
            <tbody>';

        foreach($GattiNonAdmin as $GattoNonAdmin){
            $eta = date_diff(date_create($GattoNonAdmin['data_nascita']), date_create('today'))->y;

            $id = htmlspecialchars($GattoNonAdmin['id_animale']);
            $urlDettagli = "dettagli-animale?id-animale=" . $id . "&from=senza-admin";
            $trasportoSiNo = $GattoNonAdmin['trasporto_animale']==1? 'Sì' : 'No';
            $html .= '
                <tr>
                    <th scope="row">'.htmlspecialchars($GattoNonAdmin['id_animale']).'</th>
                    <td data-title="Nome">'.htmlspecialchars($GattoNonAdmin['nome_animale']).'</td>
                    <td data-title="Data di registrazione"><time datetime="'.htmlspecialchars($GattoNonAdmin['data_registrazione']).'">'.htmlspecialchars(date('d/m/Y', strtotime($GattoNonAdmin['data_registrazione']))).'</time></td>
                    <td data-title="Trasporto">'.htmlspecialchars($trasportoSiNo).'</td>
                    <td data-title="Razza">'.htmlspecialchars($GattoNonAdmin['razza_animale']).'</td>
                    <td data-title="Età">'.htmlspecialchars($eta).'</td>
                    <td class="col-dettagli">
                        <form method="post" action="senza-amministratore" >
                            <input type="hidden" name="tipo" value="Gatti"/>
                            <input type="hidden" name="id_animale" value="' . htmlspecialchars($GattoNonAdmin['id_animale']) . '"/>
                            <button type="submit" name="assegnami_animale" class="orange-button">Assegna a me<span class="sr-only"> numero' . htmlspecialchars($GattoNonAdmin['id_animale']) . '</span></button>
                        </form>
                    </td>
                    <td class="col-dettagli">
                        <a href="' . $urlDettagli . '" class="brown-button"> 
                            Vai all\'animale<span class="sr-only">'.htmlspecialchars($GattoNonAdmin['nome_animale']).' </span>
                        </a>
                    </td>
                </tr>
            ';
        }
    
        $html .= '
        </tbody>
            <tfoot>
                <tr>
                    <td colspan="7">Totale gatti senza admin</td>
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

    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assegnami_animale'])){
        $connessione->assignAnimalToAdmin((int)$_POST['id_animale'], $_SESSION['email']);
        $tipoAttivo = $_POST['tipo'] === 'Cani' ? 'Cani' : 'Gatti';
        header("Location: ./senza-amministratore?tipo=$tipoAttivo&page=$paginaCorrente");
        exit;
    }
    
    $connessione->closeConnection();
}

$pagineCani = (int)ceil($NNonAdminByType['Cane'] / $perPagina);
$pagineGatti = (int)ceil($NNonAdminByType['Gatto'] / $perPagina);

$linkCani = $pagineCani>1 ? "<nav class='next-page-links' aria-label='Pagine cani'>
                <ul>".buildPagination(
    ($tipoAttivo === 'Cani' ? $paginaCorrente : 1), 
    $pagineCani, 
    'Cani'
)."</ul></nav>" : '';

$linkGatti = $pagineGatti>1 ? "<nav class='next-page-links' aria-label='Pagine gatti'>
                <ul>".buildPagination(
    ($tipoAttivo === 'Gatti' ? $paginaCorrente : 1), 
    $pagineGatti, 
    'Gatti'
)."</ul></nav>" : '';
$linkAttivi = ($tipoAttivo === 'Gatti') ? $linkGatti : $linkCani;

$cani_content = renderCaniContent($animali['Cane'], $NNonAdminByType);
$gatti_content = renderGattiContent($animali['Gatto'],$NNonAdminByType);


$paginaHTML = loadTemplate('./src/template/layout-admin.html', '<p>Errore: template layout.html non trovato o non leggibile.</p>');
$breadcrumb = getBreadcrumb('senza-amministratore', $pagine);
$nav = buildAdminNav($adminMenu,'./senza-amministratore');

$main = loadTemplate('./src/template/main/admin/senza-amministratore.html');
$main = str_replace('[tabs-animali]', renderCaniGattiTabs(), $main);
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

?>