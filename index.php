<?php
session_name('Sistema2020');
session_start();
$_SESSION['path_root'] = trim($_SERVER['DOCUMENT_ROOT']);
// Es utilizando en templateEngine.inc.php
$root = '';

if(!empty($_SESSION) && $_SESSION['userLogin'] == true){
    include('includes/templateEngine.inc.php');

    $twig->display('layout_index.html',array(
        "userName" => $_SESSION['userNombre'],
        "userID" => $_SESSION['userID'],
        "dbname" => $_SESSION['dbname'],
        "codigo_perfil" => $_SESSION['codigo_perfil'],
        "codigo_personal" => $_SESSION['codigo_personal'],
        "logo_uno" => $_SESSION['logo_uno'],
        "nombre_personal" => $_SESSION['nombre_personal'],
        "nombre_perfil" => $_SESSION['nombre_perfil'],
        "nombre_institucion" => $_SESSION['nombre_institucion'],
        "foto_personal" => $_SESSION['foto_personal']
        ));    
}else{
    header("Location:login.php");
}
?>