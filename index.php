<?php
session_name('Sistema2020');
session_start();
$_SESSION['path_root'] = trim($_SERVER['DOCUMENT_ROOT']);
// Es utilizando en templateEngine.inc.php
$root = '';
// Establecer formato para la fecha.
// 
    date_default_timezone_set('America/El_Salvador');
    setlocale(LC_TIME,'es_SV');
// Mensaje para la Hora.
    $hora = date('G'); 
    switch ($hora) {
    case (($hora >= 6) AND ($hora < 12)):
            $mensaje = " Buenos dias";
        //echo $mensaje;
            break;
    case (($hora >= 12) AND ($hora < 18)):
            $mensaje = " Buenas tardes"; 
        //echo $mensaje;
            break;
    case (($hora >= 18) AND ($hora < 24)):
        $mensaje = " Buenas Noches."; 
    //echo $mensaje;
        break;
    case (($hora >= 0) AND ($hora < 6)):
            $mensaje = " ¿Es de madrugada?"; 
        //echo $mensaje;
            break;
    default;
        $mensaje = $hora . " ";
    break;
    }
//
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
        "foto_personal" => $_SESSION['foto_personal'],
        "mensaje" => $mensaje
        ));    
}else{
    header("Location:login.php");
}
?>