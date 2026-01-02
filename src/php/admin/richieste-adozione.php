<?php

    if(isset($_GET['id-animale']) && isset($_GET['email']) ) {
        require './src/php/admin/dettagli-richiesta.php';

    }else{
        echo 'Qui ci va la pagina delle richieste di adozione, quando metti "<h1>?email=lindorlinor@gmail.com&id-animale=1</h1>" ti apre la singola richiesta (obv metti i valori che vuoi nei parametri)';

    }

?>