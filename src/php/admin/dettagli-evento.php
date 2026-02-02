<?php
define('ADMIN_EVENTO', true);
if(!isset($_SESSION['admin']) && !$_SESSION['admin'] === true){
    if(isset($_GET['titolo']) && isset($_GET['data'])){
        header("Location: ./visualizzazione-evento?titolo=".$_GET['titolo']."&data=".$_GET['data']);
    }else{
        header("Location: ./eventi");
    }
    exit;
}
require __DIR__ . '/../visualizzazione-evento.php';


?>