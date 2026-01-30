<?php
define('ADMIN_EVENTO', true);
if(!isset($_SESSION['admin']) && !$_SESSION['admin'] === true){
    header("Location: ./accedi");
    exit; 
}
require __DIR__ . '/../visualizzazione-evento.php';


?>