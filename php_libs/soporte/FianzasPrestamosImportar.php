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
// COLOCAR UN LIMITE A LA MEMORIA PARA LA CREACIÓN DE LA HOJA DE CÁLCULO.
set_time_limit(0);
ini_set("memory_limit","1024M");
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
					$descripcion = htmlspecialchars(trim($_POST['descripcion']),ENT_QUOTES,'UTF-8');
					$checkbox[] = explode(",", $_POST['checkbox']);
					$fila = $_POST['fila'];
					$fila_ = 0;
					$check_v = array();
					for ($i=0; $i <$fila ; $i++) { 
						$check_v[] = $checkbox[0][$i];
						//print $check_v[$i] . " v $i <br>";
					}
				// RECORRER LA TABLA DE LOS DATOS A IMPORTAR
					$query_leer = "SELECT * FROM fianzas_prestamos_importar";
				// Ejecutamos el Query.
					$consulta_leer = $dblink -> query($query_leer);
				// Validar si hay registros.
				if($consulta_leer -> rowCount() != 0){
					// Respusta y mensaje		
					$respuestaOK = true;
					$mensajeError = "Se ha Actualizado el registro correctamente";
					$contenidoOK = 'FIANZA ACTUALIZADA.';
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
							/////////////////////////////////////////////////////////////////////////////////////////////////////ç
							// VERIFICAR SI EL REGISTRO YA EXISTE.
							/////////////////////////////////////////////////////////////////////////////////////////////////////
								$query_buscar = "SELECT * FROM fianzas WHERE codigo = '$codigo' and fecha = '$fecha'";	
									$consulta_buscar = $dblink -> query($query_buscar);
										// Validar si hay registros.
											if($consulta_buscar -> rowCount() != 0){
												// Evauar si el checkbox está activo
													if($check_v[$fila_] == 'false'){
														// no va ser nada...
													}else{
														// Actualizar registro
														$query_update = "UPDATE fianzas SET fianza = '$fianza', devolucion = '$devolucion', descripcion = '$descripcion'
														WHERE codigo = '$codigo' and fecha = '$fecha'";
														// Ejecutar query insertar
														$resultadoQueryUpdate = $dblink -> query($query_update);    

													}
												
											}else{
												// Agregar Registro
													$query_insertar = "INSERT INTO fianzas (fecha, codigo, fianza, devolucion, descripcion) VALUES ('$fecha','$codigo','$fianza','$devolucion','$descripcion')";
												// Ejecutar query insertar
													$resultadoQuery = $dblink -> query($query_insertar);    
											}
					// VARIABLE DE LA MATRIZ CHECKBOX FILA
						$fila_ = $fila_ + 1;
					}
				}else{
					// SI LA TABLA ESTA VACIA DE FIANZAS PRESTAMOS IMPORTAR.
					$respuestaOK = false;
					$mensajeError = "La Tabla Importar Fianzas y Prestamos está vacía.";
					$contenidoOK = '<tr><td>No hay Registros...</tr></td>';
				}
			break;
			case 'AgregarPrestamos':	
				// DESCRIPCION
					$descripcion = htmlspecialchars(trim($_POST['descripcion']),ENT_QUOTES,'UTF-8');
					$checkbox[] = explode(",", $_POST['checkbox']);
					$fila = $_POST['fila'];
					$fila_ = 0;
					$check_v = array();
					for ($i=0; $i <$fila ; $i++) { 
						$check_v[] = $checkbox[0][$i];
						//print $check_v[$i] . " v $i <br>";
					}
				// RECORRER LA TABLA DE LOS DATOS A IMPORTAR
					$query_leer = "SELECT * FROM fianzas_prestamos_importar";
				// Ejecutamos el Query.
					$consulta_leer = $dblink -> query($query_leer);
				// Validar si hay registros.
				if($consulta_leer -> rowCount() != 0){
					// Respusta y mensaje		
					$respuestaOK = true;
					$mensajeError = "Se ha Actualizado el registro correctamente";
					$contenidoOK = 'FIANZA ACTUALIZADA.';
					// convertimos el objeto
					while($listado = $consulta_leer -> fetch(PDO::FETCH_BOTH))
					{
						//	INICIO DEL ID PARA GUARDAR JUNTO CON LA IMAGEN.
							$descuentos = trim($listado['credito']);
							$prestamos = trim($listado['debito']);
						// Nombres de los campos de la tabla.
							$codigo = trim($listado['codigo']);	
							$fecha = trim($listado['fecha']);
							//$descripcion = trim($listado['descripcion']);
							/////////////////////////////////////////////////////////////////////////////////////////////////////ç
							// VERIFICAR SI EL REGISTRO YA EXISTE.
							/////////////////////////////////////////////////////////////////////////////////////////////////////
								$query_buscar = "SELECT * FROM prestamos WHERE codigo = '$codigo' and fecha = '$fecha'";	
									$consulta_buscar = $dblink -> query($query_buscar);
										// Validar si hay registros.
											if($consulta_buscar -> rowCount() != 0){
												// Evauar si el checkbox está activo
													if($check_v[$fila_] == 'false'){
														// no va ser nada...
													}else{
														// Actualizar registro
														$query_update = "UPDATE prestamos SET prestamos = '$prestamos', descuentos = '$descuentos', descripcion = '$descripcion'
														WHERE codigo = '$codigo' and fecha = '$fecha'";
														// Ejecutar query insertar
														$resultadoQueryUpdate = $dblink -> query($query_update);    

													}
												
											}else{
													// Evauar si el checkbox está activo
													if($check_v[$fila_] == 'false'){
														// no va ser nada...
													}else{
													// Agregar Registro
														$query_insertar = "INSERT INTO prestamos (fecha, codigo, prestamos, descuentos, descripcion) VALUES ('$fecha','$codigo','$prestamos','$descuentos','$descripcion')";
													// Ejecutar query insertar
														$resultadoQuery = $dblink -> query($query_insertar);    
													}
											}
					// VARIABLE DE LA MATRIZ CHECKBOX FILA
						$fila_ = $fila_ + 1;
					}
				}else{
					// SI LA TABLA ESTA VACIA DE FIANZAS PRESTAMOS IMPORTAR.
					$respuestaOK = false;
					$mensajeError = "La Tabla Importar Fianzas y Prestamos está vacía.";
					$contenidoOK = '<tr><td>No hay Registros...</tr></td>';
				}
			break;
			case 'BorrarRegistrosTabla':		
				// RECORRER LA TABLA DE LOS DATOS A IMPORTAR
				$query_borrar = "DELETE FROM fianzas_prestamos_importar";
					
				// Ejecutamos el Query.
					$consulta_borrar = $dblink -> query($query_borrar);
				// 
					$respuestaOK = true;
					$mensajeError = "";
					$contenidoOK = '';
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
?>