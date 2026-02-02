<?php

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
