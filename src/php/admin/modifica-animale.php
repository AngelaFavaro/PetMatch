<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if($_SESSION['admin'] !== true){
    if(isset($_GET['id-animale'])){
        header("Location: ./animali?id-animale=".urlencode($_GET['id-animale']));
        exit; 
    }else{
        header("Location: ./animali");
        exit; 
    }
}
require 'nuovo-animale.php';
?>
