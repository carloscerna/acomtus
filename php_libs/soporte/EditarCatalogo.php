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
$codigo_personal = "";
// ruta de los archivos con su carpeta
	$path_root=trim($_SERVER['DOCUMENT_ROOT']);    
// variable que contiene el nombre de la tabla
	$CodigoTabla = $_REQUEST['CodigoTabla'];
	$tabla_array = array('catalogo_cargo','catalogo_departamento_empresa','catalogo_clasificacion_empresa','catalogo_ruta','catalogo_tipo_licencia','catalogo_tiquete_serie','catalogo_tipo_transporte');	// Creación de matriz para las diferentes tablas.
// Incluimos el archivo de funciones y conexi�n a la base de datos
	include($path_root."/acomtus/includes/funciones.php");
	include($path_root."/acomtus/includes/mainFunctions_conexion.php");
// Validar conexi�n con la base de datos
if($errorDbConexion == false){
	// Validamos qe existan las variables post
	if(isset($_POST) && !empty($_POST)){
		if(!empty($_POST['accionCatalogo'])){
			$_POST['accion'] = $_POST['accionCatalogo'];
		}
		// Verificamos las variables de acci�n
		switch ($_POST['accion']) {
			case 'BuscarTodos':
				switch ($CodigoTabla) {
					case '0':
						$campos_array = array('id_cargo', 'codigo', 'descripcion');
						break;
					case '1':
						$campos_array = array('id_departamento_empresa', 'codigo', 'descripcion');
						break;
					case '2':
						$campos_array = array('id_clasificacion_empresa', 'codigo', 'descripcion');
						break;
					case '3':
						$campos_array = array('id_ruta', 'codigo', 'descripcion');
						break;
					case '4':
						$campos_array = array('id_tipo_licencia', 'codigo', 'descripcion');
						break;
					case '5':
						$campos_array = array('id_', 'codigo', 'descripcion');
						break;
					case '6':
						$campos_array = array('id_', 'codigo', 'descripcion');
						break;
					default:
						$campos_array = array('id_cargo', 'codigo', 'descripcion');
						break;
				}
				//	Enviar a la funció correspondiente.
					VerListado($tabla_array,$campos_array);
			break;
			/******************************************************************************************** */
			case 'AgregarNuevo':
				switch ($CodigoTabla) {
					case '0':
						$campos_array = array('codigo','descripcion');
						$data_accion = array('txtCodigo','Descripcion');
						break;
					case '1':
						$campos_array = array('codigo','descripcion');
						$data_accion = array('txtCodigo','Descripcion');
						break;
					case '2':
						$campos_array = array('codigo','descripcion');
						$data_accion = array('txtCodigo','Descripcion');
						break;
					case '3':
						$campos_array = array('codigo','descripcion');
						$data_accion = array('txtCodigo','Descripcion');
						break;
					case '4':
						$campos_array = array('codigo','descripcion');
						$data_accion = array('txtCodigo','Descripcion');
						break;
					case '5':
						$campos_array = array('codigo','descripcion');
						$data_accion = array('txtCodigo','Descripcion');
						break;
					case '6':
						$campos_array = array('codigo','descripcion');
						$data_accion = array('txtCodigo','Descripcion');
						break;
					default:
						$campos_array = array('codigo','descripcion');
						$data_accion = array('txtCodigo','Descripcion');
						break;
				}
					AgregarRegistroTabla($tabla_array,$campos_array,$data_accion);				
			break;
			/******************************************************************************************** */
			case 'BuscarPorId':
				switch ($CodigoTabla) {
					case '0':
						$campos_array = array('id_cargo', 'codigo', 'descripcion');
						break;
					case '1':
						$campos_array = array('id_departamento_empresa', 'codigo', 'descripcion');
						break;
					case '2':
						$campos_array = array('id_clasificacion_empresa', 'codigo', 'descripcion');
						break;
					case '3':
						$campos_array = array('id_ruta', 'codigo', 'descripcion');
						break;
					case '4':
						$campos_array = array('id_tipo_licencia', 'codigo', 'descripcion');
						break;
					case '5': // Tabla catalogo_tiquete_serie
						$campos_array = array('id_', 'codigo', 'descripcion');
						break;
					case '6': // Tabla catalogo_tipo_transporte
						$campos_array = array('id_', 'codigo', 'descripcion');
						break;
					default:
						$campos_array = array('id_cargo', 'codigo', 'descripcion');
						break;
				}
				//	Enviar a la funció correspondiente.
					BuscarPorId($tabla_array,$campos_array);

			break;
			case 'ActualizarPorId':
				switch ($CodigoTabla) {
					case '0':
						$campos_array = array('id_cargo','descripcion');
						$data_accion = array('IdCatalogo','Descripcion');
						break;
					case '1':
						$campos_array = array('id_departamento_empresa','descripcion');
						$data_accion = array('IdCatalogo','Descripcion');
						break;
					case '2':
						$campos_array = array('id_clasificacion_empresa','descripcion');
						$data_accion = array('IdCatalogo','Descripcion');
						break;
					case '3':
						$campos_array = array('id_ruta','descripcion');
						$data_accion = array('IdCatalogo','Descripcion');
						break;
					case '4':
						$campos_array = array('id_tipo_licencia','descripcion');
						$data_accion = array('IdCatalogo','Descripcion');
						break;
					case '5':
						$campos_array = array('id_','descripcion');
						$data_accion = array('IdCatalogo','Descripcion');
						break;
					case '6':	// catalogo_tipo_transporte
						$campos_array = array('id_','descripcion');
						$data_accion = array('IdCatalogo','Descripcion');
						break;
					default:
						$campos_array = array('id_cargo','descripcion');
						$data_accion = array('IdCatalogo','Descripcion');
						break;
				}
					ActualizarPorId($tabla_array,$campos_array,$data_accion);				
			break;
			/******************************************************************************************** */
			case 'EliminarRegistro':
				$tabla_array = array('fianzas');
				$campos_array = array('id_fianza', 'fecha',  'fianza', 'devolucion', 'descripcion', 'codigo');
				EliminarPorId($tabla_array, $campos_array);
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
	if($_POST["accion"] === "" or $_POST["accion"] === "BuscarTodos"){
		echo json_encode($arreglo);	
	}elseif($_POST["accion"] === "BuscarPorId" or $_POST["accion"] === "" or $_POST["accion"] === ""){
		echo json_encode($datos);
		}
	else{
		// Armamos array para convertir a JSON
		$salidaJson = array("respuesta" => $respuestaOK,
			"mensaje" => $mensajeError,
			"contenido" => $contenidoOK);
		echo json_encode($salidaJson);
	}
/* FUNCION PARA VISUALIZAR FIANZAS Y PRESTAMOS*/
function VerListado($tabla_array,$campos_array){
	global $dblink, $respuestaOK, $mensajeError, $contenidoOK, $arreglo, $datos, $tabla_array, $campos_array, $CodigoTabla;
		//	Total de registros.
$query = "SELECT $campos_array[0] as id_, $campos_array[1], $campos_array[2] FROM $tabla_array[$CodigoTabla] ORDER BY $campos_array[0]";
		// Ejecutamos el Query.
			$consulta = $dblink -> query($query);
		// Validar si hay registros.
			if($consulta -> rowCount() != 0){
				$respuestaOK = true;
				$num = 0;
				// convertimos el objeto
				while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
				{
					$arreglo["data"][] = $listado;						
				}
				$mensajeError = "Si Registro";
			}
			else{
				$respuestaOK = true;
				$contenidoOK = '';
				$mensajeError =  'No Registro';
				$arreglo["data"][] = '{"sEcho":1,
										"sEcho":1,
										"iTotalRecords":"0",
										"iTotalDisplayRecords":"0",
										"aaData":[]
									}';						
			}
}
/* FUCION PARA AGREGAR O GUADAR INFORMACIÓN EN FIANZAS Y PRESTAMOS */
function AgregarRegistroTabla($tabla_array,$campos_array){
	global $dblink, $respuestaOK, $mensajeError, $contenidoOK, $data_accion, $CodigoTabla;
	// VALORES DEL POST
		$descripcion = htmlspecialchars(trim($_POST[$data_accion[1]]));
		$codigo = trim($_POST[$data_accion[0]]);
		// Query
			$query = "INSERT INTO $tabla_array[$CodigoTabla] ($campos_array[0],$campos_array[1])
				VALUES ('$codigo','$descripcion')";
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
function ActualizarPorId($tabla_array,$campos_array){
	global $dblink, $respuestaOK, $mensajeError, $contenidoOK, $data_accion, $CodigoTabla;
		// VALORES DEL POST
		$id_ = trim($_POST[$data_accion[0]]);
		$descripcion = htmlspecialchars(trim($_POST[$data_accion[1]]));

		// QUERY UPDATE.
			$query_usuario = sprintf("UPDATE $tabla_array[$CodigoTabla] SET $campos_array[1] = '%s'
				WHERE $campos_array[0] = %d",
				$descripcion, 
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
/* FUNCION PARA BUSCAR POR ID EDITAR O ACTULIAZAR INFORMACIÓN EN FIANZAS Y PRESTAMOS */
function BuscarPorId($tabla_array,$campos_array){
	global $id_, $dblink, $respuestaOK, $mensajeError, $contenidoOK, $arreglo, $datos, $tabla_array, $campos_array, $CodigoTabla, $fila_array;
		// VALORES DEL POST
		$id_ = trim($_REQUEST['id_']);
		// Armamos el query.
			$query = "SELECT * FROM $tabla_array[$CodigoTabla]	WHERE $campos_array[0] = '$id_'";
			// Ejecutamos el Query.
			$consulta = $dblink -> query($query);
			// Validar si hay registros.
			if($consulta -> rowCount() != 0){
				$respuestaOK = true;
				// convertimos el objeto
				while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
				{
					// CAMPOS Y VARIALES DE LA TABLA.
						$id_ = trim($listado[$campos_array[0]]);
						$codigo = trim($listado[$campos_array[1]]);
						$descripcion = trim($listado[$campos_array[2]]);

					//	 ENVIAR DATA ARRAY
						$datos[$fila_array]["id_"] = $id_;
						$datos[$fila_array]["codigo"] = $codigo;
						$datos[$fila_array]["descripcion"] = $descripcion;
						$datos[$fila_array]["nombre_tabla"] = $tabla_array[$CodigoTabla];
				}
				$datos[$fila_array]["mensaje"] = "Registro Encontrado.";
			}
			else{
				$datos[$fila_array]["mensaje"] = "No hay registros.";
			}
}
/* FUNCION PARA ELIMINAR EL REGISTRO INFORMACIÓN EN FIANZAS Y PRESTAMOS.*/
function EliminarPorId($tabla_array,$campos_array){
	global $dblink, $respuestaOK, $mensajeError, $contenidoOK, $datos, $fila_array;
	// VALORES DEL POST
	$id_p_p = trim($_POST['id_p_p']);
	$new = explode("-",$id_p_p);
	$id_ = $new[0];
	$id_personal = $new[1];	
		// Armamos el query
		$query = "DELETE FROM $tabla_array[0] WHERE $campos_array[0] = $id_";
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
}
?>