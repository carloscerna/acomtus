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
$fila_array = 0;
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);
    
// Incluimos el archivo de funciones y conexi�n a la base de datos

include($path_root."/acomtus/includes/mainFunctions_conexion.php");

// Validar conexi�n con la base de datos
if($errorDbConexion == false){
	// Validamos qe existan las variables post
	if(isset($_POST) && !empty($_POST)){
		if(!empty($_POST['accion_buscar'])){
			$_POST['accion'] = $_POST['accion_buscar'];
		}
		// Verificamos las variables de acci�n
		switch ($_POST['accion']) {
		case 'BuscarPorId':
			$id_x = trim($_POST['id_x']);
				// Armamos el query.
				$query = "SELECT id_, descripcion, fecha
							FROM asuetos
                                WHERE id_ = " . $id_x
						;
				// Ejecutamos el Query.
				$consulta = $dblink -> query($query);
				// Validar si hay registros.
				if($consulta -> rowCount() != 0){
					$respuestaOK = true;
					$num = 0;
					// convertimos el objeto
					while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
					{
                        // Nombres de los campos de la tabla.
                            $fecha = trim($listado['fecha']);
							$descripcion = trim($listado['descripcion']);
                        // Rellenando la array.
                            $datos[$fila_array]["fecha"] = $fecha;
							$datos[$fila_array]["descripcion"] = $descripcion;
					}
					$mensajeError = "Si Registro";
				}
				else{
					$respuestaOK = true;
					$contenidoOK = '';
					$mensajeError =  'No Registro';
				}
			break;

			case 'AgregarNuevo':		
    				$year =trim($_POST['year']);
				// VALIDAR LA FECHA.
					$fecha = trim($_POST['txtFecha']);
                    $descripcion = (trim($_POST['txtDescripcion']));
                 //   $codigo_estatus = trim($_POST['lstAsuetos']);
					$fechas = explode("-",$fecha);
					$dia = $fechas[2];
					$mes = $fechas[1];
					$ann = $fechas[0];
					
					if(checkdate($mes, $dia, $ann)){
					//echo "fecha valida";
					}else{
					//echo "fecha no válida";
						$mensajeError = "Fecha No Válida $dia . $mes . $ann";
                        	break;
					}
				///////////////////////////////////////////////////////////////////////////////////////
					// Query
					    $query = "INSERT INTO asuetos (fecha, descripcion) VALUES ('$fecha','$descripcion')";
					// Ejecutamos el query
						$resultadoQuery = $dblink -> query($query);              
                        ///////////////////////////////////////////////////////////////////////////////////////
                        ///////////////////////////////////////////////////////////////////////////////////////
					if($resultadoQuery == true){
						$respuestaOK = true;
						$mensajeError = "Se ha agregado el registro correctamente";
						$contenidoOK = '';
					}
					else{
						$mensajeError = "No se puede guardar el registro en la base de datos ";
					}
			break;
			
			case 'EditarRegistro':
                $id_ = $_POST['id_user'];
				$descripcion = trim($_POST['txtDescripcion']);
				$fecha = trim($_POST['txtFecha']);	
				//
               	$query_ = sprintf("UPDATE asuetos SET descripcion = '%s', fecha = '%s' WHERE id_ = %d",
						$descripcion, $fecha, $id_);
                // Ejecutamos el query guardar los datos en la tabla alumno..
                    $resultadoQuery = $dblink -> query($query_);				

					if($resultadoQuery == true){
						$respuestaOK = true;
						$mensajeError = "Se ha Actualizado el registro correctamente";
						$contenidoOK = $query_;
					}
					else{
						$mensajeError = "No se puede Actualizar el registro en la base de datos ";
						$contenidoOK = $query_;
					}
			break;
		
			case 'EliminarRegistro':
				$id_ = $_POST['id_user'];
				// Armamos el query
				$query = "DELETE FROM asuetos WHERE id_ = $id_";

				// Ejecutamos el query
					$count = $dblink -> exec($query);
				
				// Validamos que se haya actualizado el registro
				if($count != 0){
					$respuestaOK = true;
					$mensajeError = 'Se ha Eliminado '.$count.' Registro(s).';

					$contenidoOK = '';

				}else{
					$mensajeError = 'No se ha eliminado el registro';
				}
			break;
            default:
				$mensajeError = 'Esta acción no se encuentra disponible';
			break;
		}
	}
	else{
		$mensajeError = 'No se puede ejecutar la aplicación';}
}
else{
	$mensajeError = 'No se puede establecer conexión con la base de datos';}
// Salida de la Array con JSON.
	if($_POST["accion"] === "" or $_POST["accion"] === "BuscarTodosCodigo"){
		echo json_encode($arreglo);	
	}elseif($_POST["accion"] === "BuscarCodigo" or $_POST["accion"] === "GenerarCodigoNuevo" or $_POST["accion"] === "BuscarPorId"){
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