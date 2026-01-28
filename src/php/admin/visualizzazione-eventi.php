<?php
define('ADMIN_EVENTI', true);
if(!isset($_SESSION['admin']) && !$_SESSION['admin'] === true){
    header("Location: ./accedi");
    exit; 
}
require __DIR__ . '/../eventi.php';


?>