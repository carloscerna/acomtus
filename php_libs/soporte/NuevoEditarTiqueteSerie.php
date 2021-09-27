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
				$query = "SELECT it.id_, it.fecha, it.codigo_proveedor, it.codigo_serie, it.tiraje, it.numero_inicio, it.numero_fin, it.descripcion, it.codigo_estatus, it.codigo_tiquete_color,
							it.costo, it.total, it.precio_publico,
							cat_s_t.descripcion as nombre_serie,
                            cat_est.descripcion as nombre_estatus,
							cat_p.nombre as nombre_proveedor
							FROM inventario_tiquete it
							INNER JOIN catalogo_tiquete_serie cat_s_t ON cat_s_t.id_ = it.codigo_serie
							INNER JOIN catalogo_estatus cat_est ON cat_est.codigo = it.codigo_estatus
							INNER JOIN proveedores cat_p ON cat_p.id_ = it.codigo_proveedor
                                WHERE it.id_ = " . $id_x
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
							$codigo_proveedor = trim($listado['codigo_proveedor']);
							$codigo_serie = trim($listado['codigo_serie']);
							$tiraje = trim($listado['tiraje']);
							$numero_inicio = trim($listado['numero_inicio']);
							$numero_fin = trim($listado['numero_fin']);
							$descripcion = trim($listado['descripcion']);
							$codigo_estatus = trim($listado['codigo_estatus']);
							$codigo_tiquete_color = trim($listado['codigo_tiquete_color']);
						//
							$costo = trim($listado['costo']);
							$total = trim($listado['total']);
							$precio_publico = trim($listado['precio_publico']);
							
                        // Rellenando la array.
                            $datos[$fila_array]["fecha"] = $fecha;
							$datos[$fila_array]["codigo_proveedor"] = $codigo_proveedor;
							$datos[$fila_array]["codigo_serie"] = $codigo_serie;
							$datos[$fila_array]["codigo_tiquete_color"] = $codigo_tiquete_color;
							$datos[$fila_array]["tiraje"] = $tiraje;
							$datos[$fila_array]["numero_inicio"] = $numero_inicio;
							$datos[$fila_array]["numero_fin"] = $numero_fin;
							$datos[$fila_array]["codigo_estatus"] = $codigo_estatus;
							$datos[$fila_array]["descripcion"] = $descripcion;
						//
							$datos[$fila_array]["costo"] = $costo;
							$datos[$fila_array]["total"] = $total;
							$datos[$fila_array]["precio_publico"] = $precio_publico;
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
				// armar variables.
				// TABS-1
				$year =trim($_POST['year']);
				// VALIDAR LA FECHA.
					$fecha = trim($_POST['txtFecha']);
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
				//
                $codigo_proveedor = trim($_POST['lstProveedor']);
				$codigo_serie = trim($_POST['lstTiqueteSerie']);
				//
				$tiraje = intval(str_replace(",","",$_POST['txtTiraje']));
				$numero_inicio = intval(str_replace(",","",$_POST['txtNumeroInicio']));
				$numero_fin = intval(str_replace(",","",$_POST['txtNumeroFin']));
				//
				$costo = preg_replace("/[$,]/","",trim($_POST['txtCosto']));
				$total = preg_replace("/[$,]/","",trim($_POST['txtTotal']));
				$precio_publico = preg_replace("/[$,]/","",trim($_POST['txtPrecioPublico']));
				///////////////////////////////////////////////////////////////////////////////////////
					// validar dese y hasta en asignado. a < 0.
					if($numero_inicio < 0 or $numero_fin < 0 or $tiraje < 0 or $costo < 0 or $precio_publico < 0){
						$mensajeError = "Tiraje, Número Inicio, Fin, Costo, Precio Público |  No pueden ser Menor a 0.";
						break;
					}
				// validar dese y hasta en asignado. a < 0.
				if($numero_inicio > $numero_fin){
					$mensajeError = "Número Inicio no puede ser mayor a Número Fin | Inventario.";
					break;
				}
				// tiraja = correlativo hasta.
				if($tiraje != $numero_fin){
					$mensajeError = "Tiraje y Número Inicio Tienen Que ser Iguales | Inventario.";
					break;
				}
				///////////////////////////////////////////////////////////////////////////////////////
				///////////////////////////////////////////////////////////////////////////////////////
				//
				$descripcion = trim($_POST['txtDescripcion']);
				$codigo_estatus = trim($_POST['lstestatus']);
				$codigo_tiquete_color = trim($_POST['lstTiqueteColor']);	
                    // validar la Tiraje.
                        if(!is_integer($tiraje)){
                            $mensajeError = "Existencia Inicial, tiene que ser un Número Entero.";
                                break;
						}
				// validar que la serie no exista para el presente año.
					$query_year = "SELECT *	FROM inventario_tiquete
									WHERE extract(YEAR FROM fecha) = '$year' and codigo_serie = '$codigo_serie' and codigo_tiquete_color = '$codigo_tiquete_color'";
					// Ejecutamos el query
					$consulta = $dblink -> query($query_year);
					// Validar si hay registros.
						if($consulta -> rowCount() != 0){
							$mensajeError = "La Serie Ya fue creada para el Año " . $year;
							$contenidoOK = '';
						break;
						}
					// Query
					$query = "INSERT INTO inventario_tiquete (fecha, codigo_proveedor, codigo_serie, tiraje, numero_inicio, numero_fin, descripcion, codigo_estatus, costo, total, precio_publico, existencia, codigo_tiquete_color)
						VALUES ('$fecha','$codigo_proveedor','$codigo_serie','$tiraje','$numero_inicio','$numero_fin','$descripcion','$codigo_estatus','$costo','$total','$precio_publico','$tiraje', '$codigo_tiquete_color')";
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
                //$fecha = trim($_POST['txtFecha']);
                //$codigo_proveedor = trim($_POST['lstProveedor']);
				//$tiraje = $_POST['txtTiraje'];
				//$tiraje = intval(str_replace(",","",$tiraje));
				//$numero_inicio = $_POST['txtNumeroInicio'];
				//$numero_inicio = intval(str_replace(",","",$numero_inicio));

				//$numero_fin = $_POST['txtNumeroFin'];
				//$numero_fin = intval(str_replace(",","",$numero_fin));
				$descripcion = trim($_POST['txtDescripcion']);
				$codigo_estatus = trim($_POST['lstestatus']);	
				//
				$costo = trim($_POST['txtCosto']);
				$costo = str_replace("$","",$costo);
				$total = trim($_POST['txtTotal']);
				$total = str_replace("$","",$total);
				$precio_publico = $_POST['txtPrecioPublico'];
				$precio_publico = str_replace("$","",$precio_publico);
                // validar la existencia inicial
               /* if(!is_int($tiraje)){
                    $mensajeError = "Existencia Inicial, tiene que ser un Número Entero.";
                        break;
				}*/
               	$query_ = sprintf("UPDATE inventario_tiquete SET descripcion = '%s', costo = '%s', total = '%s', precio_publico = '%s', codigo_estatus = '%s'
						WHERE id_ = %d",
						$descripcion, $costo, $total, $precio_publico, $codigo_estatus,
							$_POST['id_user']);	/*
                $query_ = sprintf("UPDATE inventario_tiquete SET fecha = '%s', codigo_proveedor  = '%s', codigo_serie = '%s', tiraje = '%s', numero_inicio = '%s', numero_fin = '%s', descripcion = '%s', codigo_estatus = '%s',
						costo = '%s', total = '%s', precio_publico = '%s'
						WHERE id_ = %d",
						$fecha, $codigo_proveedor, $codigo_serie, $tiraje, $numero_inicio, $numero_fin, $descripcion, $codigo_estatus,
						$costo, $total, $precio_publico,
							$_POST['id_user']);	*/
                        
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
				$nombre = trim($_POST['nombre']);
				// COMPARAR QUE ELUSUARIO ROOT NO PUEDA SER ELIMINADO.
				if($nombre == 'root'){
					$mensajeError = "El Usuario <b> root </b> no se puede Eliminar";
						break;
				}
				// Armamos el query
				$query = "DELETE FROM  WHERE id_usuario = $_POST[id_user]";

				// Ejecutamos el query
				//	$count = $dblink -> exec($query);
				
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