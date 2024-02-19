<?php
//session_name('demoUI');
//session_start();
// limpiar cache.
clearstatcache();
// Script para ejecutar AJAX
// cambiar a utf-8.
header("Content-Type: text/html;charset=iso-8859-1");
// Insertar y actualizar tabla de usuarios
sleep(0);
// Inicializamos variables de mensajes y JSON
$respuestaOK = false;
$mensajeError = "No se puede ejecutar la aplicaci�n";
$contenidoOK = "";
$lista = "";
$arreglo = array();
$datos = array();
$listado = array("0","1","2","3","4","5","6","7");
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);
// Incluimos el archivo de funciones y conexi�n a la base de datos
include($path_root."/acomtus/includes/mainFunctions_conexion.php");
include($path_root."/acomtus/includes/funciones.php");
// Validar conexi�n con la base de datos
if($errorDbConexion == false){
	// Validamos qe existan las variables post
	if(isset($_POST) && !empty($_POST)){
		if(!empty($_POST['accion_buscar'])){
			$_POST['accion'] = $_POST['accion_buscar'];
		}
		// Verificamos las variables de acci�n
		switch ($_POST['accion']) {
		case 'BuscarProduccionDiaria':
            $FechaDesde = $_REQUEST["FechaDesdePD"];
            $FechaHasta = $_REQUEST["FechaHastaPD"];
			$mostrarDias = $_REQUEST["mostrarDias"];
            	//	Estilos
                $estilo_l = 'style="padding: 0px; font-size: large; color:black; text-align: left;"';
                $estilo_c = 'style="padding: 0px; font-size: large; color:black; text-align: center;"';
                $estilo_r = 'style="padding: 0px; font-size: medium; color:black; text-align: right;"';                                                                         
				// Armamos el query. validar dís de la
				if($mostrarDias == 0){
					$query = "SELECT * FROM produccion_diaria WHERE fecha >= '$FechaDesde' and fecha <= '$FechaHasta'
					ORDER BY fecha";
				}else{
					$query = "SELECT * FROM produccion_diaria ORDER BY fecha desc limit 7";
				}

				// Ejecutamos el Query.
				$consulta = $dblink -> query($query);
				// Validar si hay registros.
				if($consulta -> rowCount() != 0){
					$respuestaOK = true;
					$num = 0;
					// convertimos el objeto
					while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
					{
                        $fecha = cambiaf_a_normal(trim($listado['fecha']));
                        $dolares = number_format(trim($listado['total_dolares']),0,".",",");
                        $colones = number_format(trim($listado['total_colones']),0,".",",");
                        
                        $contenidoOK .= "<tr>
                        <td $estilo_c>$fecha
                        <td $estilo_c>$ $dolares
                        <td $estilo_c>&#162 $colones"
                        ;				
					}
					$mensajeError = "Si Registro";
				}
				else{
					$respuestaOK = true;
					$contenidoOK = '';
					$mensajeError =  'No Registro';					
				}
			break;
			default:
				$mensajeError = 'Esta acci�n no se encuentra disponible';
			break;
		}
	}
	else{
		$mensajeError = 'No se puede ejecutar la aplicaci�n';}
}
else{
	$mensajeError = 'No se puede establecer conexi�n con la base de datos';}
// Salida de la Array con JSON.
	if($_POST["accion"] === "BuscarTodos" or $_POST["accion"] === "BuscarTodosCodigo"){
		echo json_encode($arreglo);	
	}elseif($_POST["accion"] === "BuscarCodigo" or $_POST["accion"] === "GenerarCodigoNuevo" or $_POST["accion"] === "EditarRegistro"){
		echo json_encode($datos);
		}
	else{
		// Armamos array para convertir a JSON
		$salidaJson = array("respuesta" => $respuestaOK,
			"mensaje" => $mensajeError,
			"contenido" => $contenidoOK);
		echo json_encode($salidaJson);
	}
?>