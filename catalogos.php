<?php
session_name('Sistema2020');
session_start();

// Comprobar si existen las variables de SESSION.
if(empty($_SESSION['userNombre']))
{
    header('Location: /acomtus');
}else{
// Es utilizando en templateEngine.inc.php
$root = '';
if(isset($_REQUEST['accionCatalogo'])){
    $accionCatalogo = $_REQUEST['accionCatalogo'];}
else{
    $accionCatalogo = "BuscarTodos";
}
if(isset($_REQUEST['CodigoTabla'])){
    $CodigoTabla = $_REQUEST['CodigoTabla'];}
else{
    $CodigoTabla = '0';
}
    include('includes/templateEngine.inc.php');

    $twig->display('layout-catalogos.html',array(
        "userName" => $_SESSION['userNombre'],
        "userID" => $_SESSION['userID'],
        "codigo_perfil" => $_SESSION['codigo_perfil'],
        "codigo_personal" => $_SESSION['codigo_personal'],
        "logo_uno" => $_SESSION['logo_uno'],
        "nombre_personal" => $_SESSION['nombre_personal'],
        "nombre_perfil" => $_SESSION['nombre_perfil'],
        "nombre_institucion" => $_SESSION['nombre_institucion'],
        "foto_personal" => $_SESSION['foto_personal'],
        "accionCatalogo" => $accionCatalogo,
        "CodigoTabla" => $CodigoTabla
    ));
}
?>