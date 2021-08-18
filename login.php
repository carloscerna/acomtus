<?php
session_name('Sistema2020');
session_start();

// Directorio Ra�z de la app

// Es utilizando en templateEngine.inc.php
            
$root = '';

    include('includes/templateEngine.inc.php');

    $twig->display('layout_login.html');    
?>