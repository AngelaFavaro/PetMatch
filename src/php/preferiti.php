<?php
if(isset($_SESSION['admin']) && $_SESSION['admin'] === true){
    header("Location: ./home");
    exit; 
}
require 'animali.php';
?>