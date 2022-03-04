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
$mensajeError = ":(";
$contenidoOK = "";
$lista = "";
$arreglo = array();
$datos = array();
$fila_array = 0;
$codigo_personal = "";
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);    
// Incluimos el archivo de funciones y conexi�n a la base de datos
	include($path_root."/acomtus/includes/funciones.php");
	include($path_root."/acomtus/includes/mainFunctions_conexion.php");
// Validar conexi�n con la base de datos
if($errorDbConexion == false){
	// Validamos qe existan las variables post
	if(isset($_POST) && !empty($_POST)){
		if(!empty($_POST['accionFianzaPrestamo'])){
			$_POST['accion'] = $_POST['accionFianzaPrestamo'];
		}
		// Verificamos las variables de acci�n
		switch ($_POST['accion']) {
			case 'AgregarFianzas':	
				// DESCRIPCION
					$descripcion = $_REQUEST["descripcion"];
				// RECORRER LA TABLA DE LOS DATOS A IMPORTAR
					$query_leer = "SELECT * FROM fianzas_prestamos_importar";
					
					// Ejecutamos el Query.
				$consulta_leer = $dblink -> query($query_leer);
				// Validar si hay registros.
				if($consulta_leer -> rowCount() != 0){
					$respuestaOK = true;
					$num = 0;
					// convertimos el objeto
					while($listado = $consulta_leer -> fetch(PDO::FETCH_BOTH))
					{
						//	INICIO DEL ID PARA GUARDAR JUNTO CON LA IMAGEN.
							$fianza = trim($listado['credito']);
							$devolucion = trim($listado['debito']);
						// Nombres de los campos de la tabla.
							$codigo = trim($listado['codigo']);	
							$fecha = trim($listado['fecha']);
							//$descripcion = trim($listado['descripcion']);

						// Almacenar el registro
							$query_insertar = "INSERT INTO fianzas (fecha, codigo, fianza, devolucion, descripcion) VALUES ('$fecha','$codigo','$fianza','$devolucion','$descripcion')";
						// Ejecutar query insertar
							$resultadoQuery = $dblink -> query($query_insertar);    
					}
					// Respusta y mensaje		
						$respuestaOK = true;
						$mensajeError = "Se ha Actualizado el registro correctamente";
						$contenidoOK = 'FIANZA ACTUALIZADA.';
				}
			break;
			
			case 'AgregarPrestamos':		
				$tabla_array = array('prestamos');
				$campos_array = array('id_prestamos', 'fecha',  'prestamos', 'descuentos', 'descripcion', 'codigo');
				$data_accion = array('txtFecha','FianzaPrestamo','DevolucionDescuento','Descripcion','CodigoPersonal');
					AgregarFianzaPrestamo($tabla_array,$campos_array,$data_accion);
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
	}elseif($_POST["accion"] === "BuscarPorIdFianza" or $_POST["accion"] === "BuscarPorIdPrestamo" or $_POST["accion"] === "BuscarFianzasPrestamos"){
		echo json_encode($datos);
		}
	else{
		// Armamos array para convertir a JSON
		$salidaJson = array("respuesta" => $respuestaOK,
			"mensaje" => $mensajeError,
			"contenido" => $contenidoOK);
		echo json_encode($salidaJson);
	}

/* FUCION PARA AGREGAR O GUADAR INFORMACIÓN EN FIANZAS Y PRESTAMOS */
function AgregarFianzaPrestamo($tabla_array,$campos_array,$data_accion){
	global $dblink, $respuestaOK, $mensajeError, $contenidoOK;
	// VALORES DEL POST
		$fecha = trim($_POST[$data_accion[0]]);
		$descripcion = htmlspecialchars(trim($_POST[$data_accion[1]]));
		$fianzaprestamo = trim($_POST[$data_accion[2]]);
		$devoluciondescuento = trim($_POST[$data_accion[3]]);
		$codigo = substr(trim($_POST[$data_accion[4]]),9,5);
		//$codigo = substr(trim($_POST[$data_accion[4]]),9,5);
		// Query
		$query = "INSERT INTO $tabla_array[0] ($campos_array[1],$campos_array[2],$campos_array[3],$campos_array[4],$campos_array[5])
				VALUES ('$fecha','$descripcion','$fianzaprestamo','$devoluciondescuento','$codigo')";
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
				$mensajeError = "No se puede guardar el registro en la base de datos ".$query;
			}
}
/* FUNCION PARA EDITAR O ACTULIZAR INFORMACIÓN EN FIANZAS Y PRESTAMOS */
function ActualizarFianzaPrestamo($tabla_array,$campos_array,$data_accion){
	global $dblink, $respuestaOK, $mensajeError, $contenidoOK;
		// VALORES DEL POST
		$id_ = trim($_POST[$data_accion[4]]);
		$fecha = trim($_POST[$data_accion[0]]);
		$fianzaprestamo = trim($_POST[$data_accion[1]]);
		$devoluciondescuento = trim($_POST[$data_accion[2]]);
		$descripcion = htmlspecialchars(trim($_POST[$data_accion[3]]));

		// QUERY UPDATE.
			$query_usuario = sprintf("UPDATE $tabla_array[0] SET $campos_array[1] = '%s', $campos_array[2] = '%s', $campos_array[3] = '%s', $campos_array[4] = '%s'
				WHERE $campos_array[0] = %d",
				$fecha, $fianzaprestamo, $devoluciondescuento, $descripcion, 
				$id_);	

			// Ejecutamos el query guardar los datos en la tabla alumno..
			$resultadoQuery = $dblink -> query($query_usuario);				
			
		if($resultadoQuery == true){
			$respuestaOK = true;
			$mensajeError = "Se ha Actualizado el registro correctamente";
			$contenidoOK = $query_usuario;
		}
		else{
			$mensajeError = "No se puede Actualizar el registro en la base de datos ";
			$contenidoOK = $query_usuario;
		}
}

?>