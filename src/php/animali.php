<?php
include './src/utils.php';
include './src/DBconnection.php';
use DB\DBAccess;

$animali ='';
$symbol='';
$type = $_GET['type'] ?? "tutti";

if($type=="tutti") {
    $symbol='*';
} else if($type=="cat") {
    $symbol="Gatto";
} else if($type=="dog") {
    $symbol="Cane";
}

public function getList($symbol) {	//legge dati da database
    //semplificata, fingo non c'e squadra maschile e femminile
    $query = "SELECT $symbol FROM ANIMALI WHERE Tipo=$type ORDER BY ID ASC"; /*questa query se uso un GET e viene automaticamente concatenata nell'url: i dati viaggiano in chiaro (problema password):
    ha la chiave=valore devo quindi considerare il caso in cui l'utente scriva qualcos'altro: devo dare i link per quelle che sono le cose giuste. C'e' anche limite lunghezza stringa 256 caratteri*/
    /*POST: vengono passati come valore, quindi non in chiaro*/
    $queryResult = mysql_query($this->connection, $query)	/*avro' in queryResult il risultato della query. Non va molto bene, potrei avere errori, oppure intanto puo' essere 
    caduta la rete (qualsiasi chiamata con la rete deve prevedere il messaggio di errore)*/ or die("Errore in dbConnection: " . mysqli_error($this->connection)); 
    /*qui mi occupo dell'errore, sono sicura sia terminata l'esecuzione non e' detto a buon fine*/

    if(mysqli_num_rows($queryResult)==0) { //come decidere cosa va nell'else e cosa nell'if non e' a caso: nel then (= if) metto la cosa che sia piu' probabile sia eseguita. Qui sbagliato sarebbe al contrario
        return false; //se non ci sono righe
    } else {
        //se e' andato a buon fine, avro' una lista di oggetti che fanno parte della query
        $result = array(); //vuoto 
        while($row=mysqli_fetch_assoc($queryResult)) {	/*prende una riga e la pusha in un array associativo. Considerabile booleano perche ritorna true se ha avuto successo.
            Quando l'array finisce, ritornera' false, quindi usciro' dal ciclo (row vale null). e' cattiva programmazione comunque*/
            array_push($result, $row); //$row contiene la riga intera. Push prende la stringa row e inserita in una array associativo. Ottengo un array le cui righe ho un array associativo con dentro una riga
        }
        $queryResult->free(); //non sono sicura si chiami cosi' e non ho capito perche' e' essenziale
        return $result;
    }
}










$connessione = new DBAccess();
$connessioneOK = $connessione->openDBConnection();
if ($connessioneOK) {
	$animali = sendReportForm($connessione, $nameValue, $emailValue);
	$connessione->closeConnection();
}else{
	$messaggiForm = "<p class='error-form'>Impossibile inviare la richiesta, riprova più tardi.</p>";
}




$paginaHTML = file_get_contents('./src/template/layout.html');
if ($paginaHTML === false) {
	$paginaHTML = "<p>Errore: template layout.html non trovato o non leggibile.</p>";
}

$title = '<title>Animali - PetMatch </title>';

$description = '<meta name="description" content="Tutti gli animali in adozione qui da PetMatch!!">';
$keywords = "";


$nav = buildUserNav($userMenu, './animali');

$breadcrumb = getBreadcrumb('animali', $pagine);

$main = file_get_contents('./src/template/main/animali.html');

$footer= file_get_contents('./src/template/partials/footer.html');

$paginaHTML = str_replace('[title]', $title, $paginaHTML);
$paginaHTML = str_replace('[description]', $description, $paginaHTML);
$paginaHTML = str_replace('[keywords]', $keywords, $paginaHTML);
$paginaHTML = str_replace('[breadcrumb]', $breadcrumb, $paginaHTML);
$paginaHTML = str_replace('[nav]', $nav, $paginaHTML);
$paginaHTML = str_replace('[main]', $main, $paginaHTML);
$paginaHTML = str_replace('[footer]', $footer, $paginaHTML);

echo $paginaHTML;
?>