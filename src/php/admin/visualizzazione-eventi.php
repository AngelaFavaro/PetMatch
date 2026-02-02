<?php
if(!isset($_SESSION['admin']) && !$_SESSION['admin'] === true){
    header("Location: ./eventi");
    exit; 
}
require __DIR__ . '/../eventi.php';

?>