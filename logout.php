<?php
session_name('Sistema2020');
session_start();

// vaciamos  las variables sesion.
if(!empty($_SESSION)){
    $_SESSION = array();
    session_destroy();
}

header("location:index.php");
?>