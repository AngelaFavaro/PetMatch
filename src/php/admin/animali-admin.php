<?php

if(!isset($_SESSION['admin']) || $_SESSION['admin'] !== true){
    header("Location: ./animali");
    exit; 
}

require __DIR__ . '/../animali.php';

?>