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
$Id = $_REQUEST['id'];
$accion = $_REQUEST['accion'];
$_SESSION['Id_A'] = $_REQUEST['id'];
    include('includes/templateEngine.inc.php');

    $twig->display('Produccion/NuevoEditar.html',array(
        "userName" => $_SESSION['userNombre'],
        "userID" => $_SESSION['userID'],
        "codigo_perfil" => $_SESSION['codigo_perfil'],
        "codigo_personal" => $_SESSION['codigo_personal'],
        "logo_uno" => $_SESSION['logo_uno'],
        "nombre_institucion" => $_SESSION['nombre_institucion'],
        "nombre_personal" => $_SESSION['nombre_personal'],
        "nombre_perfil" => $_SESSION['nombre_perfil'],
        "codigo_institucion" => $_SESSION['codigo_institucion'],
        "foto_personal" => $_SESSION['foto_personal'],
        "id" => $Id,
        "accion" => $accion
    ));
}
?>