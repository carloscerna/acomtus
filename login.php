<?php
session_name('Sistema2020');
session_start();

// Directorio Ra�z de la app

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
        $mensaje = "  Buenas Noches"; 
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

    include('includes/templateEngine.inc.php');

    $twig->display('LoginUser.html',array(
        "mensaje" => $mensaje
        ));
?>