<?php

if(isset($_SESSION['admin']) && $_SESSION['admin'] !== true){
    // TO DO: cambiare con l'id dell'evento
    header("Location: ./eventi");
    exit; 
}
require 'nuovo-evento.php';
?>