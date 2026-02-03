<?php

if($_SESSION['admin'] !== true){
    header("Location: ./visualizzazione-evento?titolo=".$_GET['titolo']."&data=".$_GET['data']);
    exit;
}
require 'nuovo-evento.php';
?>