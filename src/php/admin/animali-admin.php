<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if(!isset($_SESSION['admin']) || $_SESSION['admin'] !== true){
    header("Location: ./animali");
    exit; 
}

require __DIR__ . '/../animali.php';

?>