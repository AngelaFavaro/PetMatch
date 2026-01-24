<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// session_start();

define('ADMIN_ANIMALI', true);

if(!isset($_SESSION['admin']) || $_SESSION['admin'] !== true){
    header("Location: ./accedi");
    exit; 
}

require './src/php/animali.php';
?>