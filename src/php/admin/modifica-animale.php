<?php

if($_SESSION['admin'] !== true){
    if(isset($_GET['id'])){
        header("Location: ./animali?id=".urlencode($_GET['id']));
        exit; 
    }else{
        header("Location: ./animali");
        exit; 
    }
}
require 'nuovo-animale.php';
?>
