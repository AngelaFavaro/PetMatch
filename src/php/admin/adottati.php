<?php

include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) { //il primo controlla se esiste la variabile admin in session, la seconda controlla che sia affettivamente admin
    header("Location: ./accedi");
    exit;
}
 
function renderAnimalContent(string $tipo, array $animaliAdottati, string $NAdoptedAnimal): string {
    $tipoMinuscoloPlurale = ($tipo === 'Cane') ? 'cani' : 'gatti'; //fa un po caca ma va bene per ora

    if (($NAdoptedAnimal ?? 0) == 0) {
        return '<p role="status" class="nessun-risultato-message">Nessun ' . $tipoMinuscoloPlurale . ' adottato.</p>';
    }

    $idTabella = "sumTabella" . $tipo;
    $html = '
        <span id="' . $idTabella . '" class="sr-only" aria-hidden="true">In questa tabella vengono elencati i ' . $tipoMinuscoloPlurale . ' adottati e i dettagli della richiesta di adozione. Per ogni ' . strtolower($tipo) . ' adottato, sono disponibili le seguenti informazioni: identificativo animale, nome animale, nominativo adottante, email adottante, data di chiusura dell\'adozione, numero di giorni di valutazione e dettagli della richiesta nel completo.</span>
        <table aria-describedby="' . $idTabella . '">
            <caption>Dettagli (' . $tipoMinuscoloPlurale . ') adottati e dettagli della richiesta</caption>
            <thead>
                <tr>
                    <th scope="col"><abbr title="Identificativo animale"><span lang="en">Id</span></abbr></th>
                    <th scope="col">Nome</th>
                    <th scope="col">Nominativo adottante</th>
                    <th scope="col">Email adottante</th>
                    <th scope="col">Data chiusura adozione</th>
                    <th scope="col">Admin</th>
                    <th scope="col"><span class= "sr-only">Dettagli richiesta</span></th>
                </tr>
            </thead>
            <tbody>';
    if(!empty($animaliAdottati)){
        foreach ($animaliAdottati as $animaleAdottato) {
            $nome_cognome_admin = htmlspecialchars($animaleAdottato['nome_admin']) . ' ' . htmlspecialchars($animaleAdottato['cognome_admin']);
            $nome_cognome_adottante = htmlspecialchars($animaleAdottato['nome_adottante']) . ' ' . htmlspecialchars($animaleAdottato['cognome_adottante']);

            $html .='
                <tr>
                    <th data-title="Id" scope="row">' . htmlspecialchars($animaleAdottato['id_animale']) . '</th>
                    <td data-title="Nome">' . htmlspecialchars($animaleAdottato['nome_animale']) . '</td>
                    <td data-title="Adottante">' . $nome_cognome_adottante . '</td>
                    <td data-title="Email adottante">' . htmlspecialchars($animaleAdottato['email_adottante']) . '</td>
                    <td data-title="Chiusura adozione"> <time datetime="' . htmlspecialchars($animaleAdottato['data_chiusura']) . '">' . htmlspecialchars(date('d/m/Y', strtotime($animaleAdottato['data_chiusura']))) . '</time></td>
                    <td data-title="Admin">' . $nome_cognome_admin . '</td>
                    <td class="col-dettagli"><a href="richieste-adozione?email=' . htmlspecialchars($animaleAdottato['email_adottante']) . '&id-animale=' . htmlspecialchars($animaleAdottato['id_animale']) . '" class="brown-button">Dettagli richiesta</a></td>';
                $html .='</tr>';
            }
        }else{
            $html .= '
                <tr>
                    <td colspan="6" class="nessun-risultato-message" >Nessun ' . $tipo . ' trovato con i filtri selezionati.</td>
                </tr>
            ';
        
        }

    $html .= '
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6">Totale ' . $tipoMinuscoloPlurale . ' adottati</td>
                <td>' . htmlspecialchars($NAdoptedAnimal) . '</td>
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
    $filtroCorrente = (isset($_GET['assegnate']) && $_GET['assegnate'] !== '') ? $_GET['assegnate'] : 'tutte';
    $connessioneOK = $connessione->openDBConnection();
    $NAdoptedAnimals = ['Cane' => 0, 'Gatto' => 0];
    if ($connessioneOK) {
        $NAdoptedAnimals = $connessione->getNAdoptedAnimals(); 

        $offCani = ($tipoAttivo === 'Cani') ? $offset : 0;
        $offGatti = ($tipoAttivo === 'Gatti') ? $offset : 0;
        $animaliAdottati = $connessione->getAdoptedAnimalsPaged(
            $perPagina, 
            $offCani, 
            $offGatti, 
            $filtroCorrente,
            ($filtroCorrente === 'mie' || $filtroCorrente === 'non-mie') ? $_SESSION['email'] : null
        );
        
        $connessione->closeConnection();
    }

    $pagineCani = (int)ceil($NAdoptedAnimals['Cane'] / $perPagina);
    $pagineGatti = (int)ceil($NAdoptedAnimals['Gatto'] / $perPagina);

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

    $cani_content = renderAnimalContent('Cane',$animaliAdottati['Cane'], $NAdoptedAnimals['Cane']);
    $gatti_content = renderAnimalContent('Gatto',$animaliAdottati['Gatto'],$NAdoptedAnimals['Gatto']);


    $paginaHTML = loadTemplate('./src/template/layout-admin.html', '<p>Errore: template layout.html non trovato o non leggibile.</p>');
    $breadcrumb = getBreadcrumb('adottati', $pagine);
    $nav = buildAdminNav($adminMenu,'./adottati');

    $main = loadTemplate('./src/template/main/admin/adottati.html');
    $main = str_replace('[tabs-animali]', renderCaniGattiTabs(), $main);
    $main = str_replace('[contenuto-cani]', $cani_content, $main);
    $main = str_replace('[contenuto-gatti]', $gatti_content, $main);
    $main = str_replace('[n-cani]', $NAdoptedAnimals['Cane'], $main);
    $main = str_replace('[n-gatti]', $NAdoptedAnimals['Gatto'], $main);

    $main = str_replace('[LINKPAGINE-CANI]', $linkCani, $main);
    $main = str_replace('[LINKPAGINE-GATTI]', $linkGatti, $main);

    $rawFilters = [
        'assegnate' => (isset($_GET['assegnate']) && $_GET['assegnate'] !== '') ? $_GET['assegnate'] : 'tutte',
    ];

    $replaceFilters = [
        '[ASSEGNATE_SELECTED_TUTTE]'   => $rawFilters['assegnate'] === 'tutte' ? 'selected' : '',
        '[ASSEGNATE_SELECTED_MIE]'     => $rawFilters['assegnate'] === 'mie' ? 'selected' : '',
        '[ASSEGNATE_SELECTED_NON_MIE]' => $rawFilters['assegnate'] === 'non-mie' ? 'selected' : '',
    ];

    $main = str_replace(array_keys($replaceFilters), array_values($replaceFilters), $main);

    $title = "<title>Animali adottati - PetMatch</title>";
    $description = "<meta name='description' content='Pagina degli amministratori in cui visualizzano gli animali che sono stati adottati in PetMatch.'>";
    $keywords = "<meta name='keywords' content='amministratore, animali, adottati, PetMatch'>";
    $paginaHTML = str_replace('[title]', $title, $paginaHTML);
    $paginaHTML = str_replace('[description]', $description, $paginaHTML);
    $paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
    $paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
    $paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
    $paginaHTML = str_replace('[main]', $main, $paginaHTML);
    
    echo $paginaHTML;
?>