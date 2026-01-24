<?php
session_start();
define('ADMIN_ANIMALI', true);

if(!isset($_SESSION['admin']) || $_SESSION['admin'] !== true){
    header("Location: ./accedi");
    exit; 
}

require 'animali.php';
?>