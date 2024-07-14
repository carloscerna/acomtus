<?php
session_name('Sistema2020');
session_start();

// Comprobar si existen las variables de SESSION.
if(empty($_SESSION['userNombre']))
{
    header('Location: /acomtus');
}else{
    if(isset($_REQUEST['fecha'])){
        $fecha = $_REQUEST['fecha'];
    }else{
          //
	    // Establecer formato para la fecha.
	    // 
		date_default_timezone_set('America/El_Salvador');
		setlocale(LC_TIME,'es_SV');
	    //
		//$dias = array("Domingo","Lunes","Martes","Miercoles","Jueves","Viernes","Sábado");
                $meses = array("enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre");
                //Salida: Viernes 24 de Febrero del 2012		
		//Crear una línea. Fecha.
		$dia = strftime("%d");		// El Día.
        $mes = date('m');     // El Mes.
		$año = strftime("%Y");		// El Año.
        // fecha
        $fecha = $año ."-".$mes."-".$dia;
    }
// Es utilizando en templateEngine.inc.php
$root = '';
    include('includes/templateEngine.inc.php');

    $twig->display('/Produccion/BuscarProduccion.html',array(
        "userName" => $_SESSION['userNombre'],
        "userID" => $_SESSION['userID'],
        "codigo_perfil" => $_SESSION['codigo_perfil'],
        "codigo_personal" => $_SESSION['codigo_personal'],
        "logo_uno" => $_SESSION['logo_uno'],
        "nombre_personal" => $_SESSION['nombre_personal'],
        "nombre_perfil" => $_SESSION['nombre_perfil'],
        "nombre_institucion" => $_SESSION['nombre_institucion'],
        "foto_personal" => $_SESSION['foto_personal'],
        "fecha" => $fecha
    ));
}