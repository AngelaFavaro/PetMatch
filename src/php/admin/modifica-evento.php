<?php

if($_SESSION['admin'] !== true){
    // TO DO: cambiare con l'id dell'evento
    header("Location: ./visualizzazione-evento?titolo=".$_GET['titolo']."&data=".$_GET['data']);
    exit;
}
require 'nuovo-evento.php';
?>