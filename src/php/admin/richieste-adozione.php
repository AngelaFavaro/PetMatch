<?php

    if(isset($_GET['id-animale']) && isset($_GET['email']) ) {
        require './src/php/admin/dettagli-richiesta.php';
        
    }else{
        include './src/utils.php';
        include './src/DBconnection.php';
        // use DB\DBAccess;
        $_SESSION['user'] = 'lindorlinor@gmail.com';


        // echo 'Qui ci va la pagina delle richieste di adozione, quando metti "<h1>?email=lindorlinor@gmail.com&id-animale=1</h1>" ti apre la singola richiesta (obv metti i valori che vuoi nei parametri)';
        
        $paginaHTML = loadTemplate('./src/template/layout-admin.html', '<p>Errore: template layout.html non trovato o non leggibile.</p>');
        $breadcrumb = getBreadcrumb('richieste-adozione', $pagine);
        $nav = buildAdminNav($adminMenu,'./richieste-adozione');

        $main = loadTemplate('./src/template/main/admin/richieste-adozione.html');

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