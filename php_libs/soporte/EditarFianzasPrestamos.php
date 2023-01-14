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
			case 'BuscarFianzasPrestamos':
				$id_personal = trim($_SESSION['id_personal']);
				// Armamos el query.
				$query = "SELECT p.id_personal, p.codigo, TRIM(p.nombres) as nombre, TRIM(p.apellidos) as apellido, btrim(p.nombres || CAST(' ' AS VARCHAR) || p.apellidos) AS nombre_empleado, p.telefono_residencia, p.telefono_celular,
				p.fecha_nacimiento, p.edad, p.codigo_estatus, p.codigo_municipio, p.codigo_departamento, p.telefono_residencia, p.direccion, p.foto, p.codigo_genero, p.codigo_estado_civil, p.correo_electronico,
				p.tipo_sangre, p.codigo_estudio, p.codigo_vivienda, p.codigo_afp, p.nombre_conyuge,
					(SELECT SUM(fianza)-SUM(devolucion) as saldo_fianza from fianzas where codigo = p.codigo),
					(SELECT ROUND(SUM(fianza),2) as suma_fianzas from fianzas where codigo = p.codigo),
					(SELECT ROUND(SUM(devolucion),2) as suma_devoluciones from fianzas where codigo = p.codigo),
						(SELECT SUM(prestamos)-SUM(descuentos) as saldo_prestamo from prestamos where codigo = p.codigo),
						(SELECT ROUND(SUM(prestamos),2) as suma_prestamos from prestamos where codigo = p.codigo),
						(SELECT ROUND(SUM(descuentos),2) as suma_descuentos from prestamos where codigo = p.codigo)
						FROM personal p
							WHERE codigo <> '' and id_personal = '$id_personal'
								ORDER BY nombre_empleado"
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
						//	INICIO DEL ID PARA GUARDAR JUNTO CON LA IMAGEN.
							$fianza = trim($listado['saldo_fianza']);
							$suma_fianzas = trim($listado['suma_fianzas']);
							$suma_devoluciones = trim($listado['suma_devoluciones']);

							$suma_prestamos = trim($listado['suma_prestamos']);
							$suma_descuentos = trim($listado['suma_descuentos']);
							$prestamo = trim($listado['saldo_prestamo']);
							
						// Nombres de los campos de la tabla.
							$codigo = trim($listado['codigo']);	
							$nombre_empleado = trim($listado['nombre_empleado']);
														
                            $fecha_nacimiento = trim($listado['fecha_nacimiento']);
							$edad = trim($listado['edad']);
							$codigo_genero = trim($listado['codigo_genero']);							
							//$ = trim($listado['']);
							//$ = trim($listado['']);
						//	Logo.
							$url_foto = trim($listado['foto']);
						// Rellenando la array.
							$datos[$fila_array]["codigo"] = $codigo;
							$datos[$fila_array]["nombre_empleado"] = $nombre_empleado;
							
							$datos[$fila_array]["fecha_nacimiento"] = $fecha_nacimiento;
							$datos[$fila_array]["edad"] = $edad;							
							$datos[$fila_array]["codigo_genero"] = $codigo_genero;
							$datos[$fila_array]["saldo_fianza"] = $fianza;
							$datos[$fila_array]["suma_fianzas"] = $suma_fianzas;
							$datos[$fila_array]["suma_devoluciones"] = $suma_devoluciones;

							$datos[$fila_array]["saldo_prestamo"] = $prestamo;
							$datos[$fila_array]["suma_prestamos"] = $suma_prestamos;
							$datos[$fila_array]["suma_descuentos"] = $suma_descuentos;
                            //$datos[$fila_array][""] = $;
                            
							$datos[$fila_array]["url_foto"] = $url_foto;
					}
					$mensajeError = "Si Registro";
				}
				else{
					$respuestaOK = true;
					$contenidoOK = '';
					$mensajeError =  'No Registro';
				}
			break;
			/*  *********** ****************************************** ******************************************/
			case 'BuscarTodosFianza':
				$tabla_array = array('fianzas');
				$campos_array = array('id_fianza', 'fecha', 'descripcion', 'fianza', 'devolucion', 'codigo');
				$data_accion = array('BuscarPorIdFianza','EliminarRegistroFianza','BuscarTodosFianza');
				$header_estilo = array("style='background-color: #b3e5fc;'",'Fianza','Devolución');
					VerListado($tabla_array,$campos_array,$data_accion, $header_estilo);
			break;
			case 'BuscarTodosPrestamo':
				$tabla_array = array('prestamos');
				$campos_array = array('id_prestamos', 'fecha', 'descripcion', 'prestamos', 'descuentos', 'codigo');
				$data_accion = array('BuscarPorIdPrestamo','EliminarRegistroPrestamo','BuscarTodosPrestamo');
				$header_estilo = array("style='background-color: #c8e6c9;'",'Prestamos','Descuentos');
					VerListado($tabla_array,$campos_array,$data_accion, $header_estilo);
			break;
			/******************************************************************************************** */
			case 'AgregarFianza':		
				$tabla_array = array('fianzas');
				$campos_array = array('id_fianza', 'fecha',  'fianza', 'devolucion', 'descripcion', 'codigo');
				$data_accion = array('txtFecha','FianzaPrestamo','DevolucionDescuento','Descripcion','CodigoPersonal');
					AgregarFianzaPrestamo($tabla_array,$campos_array,$data_accion);
			break;
			
			case 'AgregarPrestamo':		
				$tabla_array = array('prestamos');
				$campos_array = array('id_prestamos', 'fecha',  'prestamos', 'descuentos', 'descripcion', 'codigo');
				$data_accion = array('txtFecha','FianzaPrestamo','DevolucionDescuento','Descripcion','CodigoPersonal');
					AgregarFianzaPrestamo($tabla_array,$campos_array,$data_accion);
			break;
			/******************************************************************************************** */
			case 'BuscarPorIdFianza':
				$tabla_array = array('fianzas');
				$campos_array = array('id_fianza', 'fecha',  'fianza', 'devolucion', 'descripcion', 'codigo');
				$data_accion = array('txtFecha','FianzaPrestamo','DevolucionDescuento','Descripcion','CodigoPersonal');
					BuscarPorId($tabla_array, $campos_array, $data_accion);

			break;
			case 'ActualizarFianza':
				$tabla_array = array('fianzas');
				$campos_array = array('id_fianza', 'fecha',  'fianza', 'devolucion', 'descripcion', 'codigo');
				$data_accion = array('txtFecha','FianzaPrestamo','DevolucionDescuento','Descripcion','IdFianzaPrestamo');
					ActualizarFianzaPrestamo($tabla_array,$campos_array,$data_accion);				
			break;
			/******************************************************************************************** */
			case 'BuscarPorIdPrestamo':
				$tabla_array = array('prestamos');
				$campos_array = array('id_prestamos', 'fecha',  'prestamos', 'descuentos', 'descripcion', 'codigo');
				$data_accion = array('txtFecha','FianzaPrestamo','DevolucionDescuento','Descripcion','CodigoPersonal');
					BuscarPorId($tabla_array, $campos_array, $data_accion);

			break;
			case 'ActualizarPrestamo':
				$tabla_array = array('prestamos');
				$campos_array = array('id_prestamos', 'fecha',  'prestamos', 'descuentos', 'descripcion', 'codigo');
				$data_accion = array('txtFecha','FianzaPrestamo','DevolucionDescuento','Descripcion','IdFianzaPrestamo');
					ActualizarFianzaPrestamo($tabla_array,$campos_array,$data_accion);				
			break;
			case 'EliminarRegistroFianza':
				$tabla_array = array('fianzas');
				$campos_array = array('id_fianza', 'fecha',  'fianza', 'devolucion', 'descripcion', 'codigo');
				EliminarPorId($tabla_array, $campos_array);
			break;
			case 'EliminarRegistroPrestamo':
				$tabla_array = array('prestamos');
				$campos_array = array('id_prestamos', 'fecha',  'prestamos', 'descuentos', 'descripcion', 'codigo');
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
/* FUNCION PARA VISUALIZAR FIANZAS Y PRESTAMOS*/
function VerListado($tabla_array,$campos_array,$data_accion,$header_estilo){
	global $dblink, $respuestaOK, $mensajeError, $contenidoOK;
		$codigo_personal = substr(trim($_POST["codigo_personal"]),9,5);
		if(!isset($_POST['page'])){$page = 1;}else{$page = $_POST['page'];}
		$rowsPerPage = 6;
		$offset = ($page - 1) * $rowsPerPage;
		//	Total de registros.
			$query_total = "SELECT * FROM $tabla_array[0] WHERE codigo = '$codigo_personal'";
		// Ejecutamos el Query.
			$consulta_rows = $dblink -> query($query_total);
		// Prepara paginación.
			$total_historial = $consulta_rows -> rowCount();
			$paginas = $total_historial / $rowsPerPage;
			$numero_paginas = ceil($paginas);
		// Armamos el query.
			$query = "SELECT $campos_array[0], $campos_array[1], $campos_array[2], $campos_array[3], $campos_array[4], $campos_array[5] FROM $tabla_array[0]
					WHERE $campos_array[5] = '$codigo_personal'
						ORDER BY $campos_array[1] DESC LIMIT $rowsPerPage OFFSET $offset"
				;
		// Ejecutamos el Query.
		$consulta = $dblink -> query($query);
		// Validar si hay registros.
		if($consulta -> rowCount() != 0){
			$respuestaOK = true;
			$num = 0;
			// convertimos el objeto
			// crear fila inicial.
			$contenidoOK .="<div class='row'>";
			$contenidoOK .="<h2><strong>Historial - " . ucfirst($tabla_array[0]) . "</strong></h2>";
			$contenidoOK .="<div class='card-columns'>";
			while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
			{
				// CAMPOS Y VARIALES DE LA TABLA.
					$id_ = trim($listado[$campos_array[0]]);						// id_						0
					$fecha = cambiaf_a_normal(trim($listado[$campos_array[1]]));	// fecha					1
					$descripcion = trim($listado[$campos_array[2]]);				// descripcion				2
					$fianza = trim($listado[$campos_array[3]]);						// fianza o prestamo		3
					$devolucion = trim($listado[$campos_array[4]]);					// devolucion o descuento	4
					$codigo_personal = trim($listado[$campos_array[5]]);			// codigo_personal			5
				// ENVIAR AL CONTENIDO AL SECTION.
					$contenidoOK .="
							<div class='card'>
								<div class='card-header text-right' " . $header_estilo[0] . ">
									<div class='row'>
										<div class='col-6 col-md-6 d-flex justify-content-start p-0 mt-0'>
											<a data-accion=$data_accion[0] class='btn btn-md' data-toggle='tooltip' data-placement='left' title='Editar' href='$id_-$codigo_personal'><i class='fas fa-edit'></i></a>
											<a data-accion=$data_accion[1] class='btn btn-md' data-toggle='tooltip' data-placement='left' title='Eliminar' href='$id_-$codigo_personal'><i class='fas fa-trash'></i></a>
										</div>
										<div class='col-6 col-md-6'>
											<b>Fecha: $fecha</b>
										</div>
									</div>
								</div>
								<div class='card-body'>
									<div class='row'>
										<div class='col-6 col-md-6 col-lg-6'>
											<div class='p-1 mb-1 bg-primary text-white text-right'>$header_estilo[1]: $ $fianza</div>
										</div>
										<div class='col-6 col-md-6 col-lg-6'>
											<div class='p-1 mb-1 bg-success text-white text-right'>$header_estilo[2]: $ $devolucion</div>
										</div>
									</div>
										<p class='note note-secondary'>
											<strong>Descripción:</strong>
												$descripcion
										</p>
								</div>
								<div class='card-footer text-center'>

								</div>
							</div>
					";
			}	// FIN DEL WHILE.
			//	cierre de fila.
				$contenidoOK .="</div></div>";
			// PAGINACIÓN.
			// APERTURA
				$contenidoOK .="<div class='row'><div class='col-lg-12'>";
				$contenidoOK .="<nav>";
				$contenidoOK .="<ul class='pagination pagination-circle pg-teal'>";

				for($jj=1;$jj<=$numero_paginas;$jj++){
					$class_active = '';
					if ($jj == 1) {
						$class_active = 'active';
					}
						$contenidoOK .= "<li class='page-item $class_active'><a class='page-link' href='$jj' data-accion=$data_accion[2]>$jj</a></li>";
				}
				$contenidoOK .= "</ul>";
				$contenidoOK .="</nav>";
				$contenidoOK .="</div></div>";
			// CIERRE
			// PAGINACIÓN.
				// json array.
					$mensajeError = ucfirst($tabla_array[0]) . " Encontradas";
		}
		else{
			$respuestaOK = false;
			$contenidoOK = '<h4>No hay Elementos que mostrar.</h4>';
			$mensajeError =  'No Hay Historial';
		}
}
/* FUCION PARA AGREGAR O GUADAR INFORMACIÓN EN FIANZAS Y PRESTAMOS */
function AgregarFianzaPrestamo($tabla_array,$campos_array,$data_accion){
	global $dblink, $respuestaOK, $mensajeError, $contenidoOK;
	// VALORES DEL POST
		$fecha = trim($_POST[$data_accion[0]]);
		$descripcion = htmlspecialchars(trim($_POST[$data_accion[3]]));
		$fianzaprestamos = (str_replace("$","",$_POST[$data_accion[1]]));
		$fianzaprestamo = (str_replace(",","",$fianzaprestamos));
		$devoluciondescuentos = (str_replace("$","",$_POST[$data_accion[2]]));
		$devoluciondescuento = (str_replace(",","",$devoluciondescuentos));
		$codigo = substr(trim($_POST[$data_accion[4]]),9,5);
		//$codigo = substr(trim($_POST[$data_accion[4]]),9,5);
		// Query
		$query = "INSERT INTO $tabla_array[0] ($campos_array[1],$campos_array[2],$campos_array[3],$campos_array[4],$campos_array[5])
				VALUES ('$fecha','$fianzaprestamo','$devoluciondescuento','$descripcion','$codigo')";
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
				$mensajeError = "No se puede guardar el registro en la base de datos: ".$query;
			}
}
/* FUNCION PARA EDITAR O ACTULIZAR INFORMACIÓN EN FIANZAS Y PRESTAMOS */
function ActualizarFianzaPrestamo($tabla_array,$campos_array,$data_accion){
	global $dblink, $respuestaOK, $mensajeError, $contenidoOK;
		// VALORES DEL POST
		$id_ = trim($_POST[$data_accion[4]]);
		$fecha = trim($_POST[$data_accion[0]]);
		$fianzaprestamos = (str_replace("$","",$_POST[$data_accion[1]]));
		$fianzaprestamo = (str_replace(",","",$fianzaprestamos));
		$devoluciondescuento = (str_replace("$","",$_POST[$data_accion[2]]));
		$devoluciondescuento = (str_replace(",","",$devoluciondescuento));
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
/* FUNCION PARA BUSCAR POR ID EDITAR O ACTULIAZAR INFORMACIÓN EN FIANZAS Y PRESTAMOS */
function BuscarPorId($tabla_array,$campos_array,$data_accion){
	global $dblink, $respuestaOK, $mensajeError, $contenidoOK, $datos, $fila_array;
		// VALORES DEL POST
		$id_p_p = trim($_POST['id_p_p']);
		$new = explode("-",$id_p_p);
		$id_ = $new[0];
		$id_personal = $new[1];
		// Armamos el query.
			$query = "SELECT * FROM $tabla_array[0]	WHERE $campos_array[0] = '$id_'";
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
						$fecha = trim($listado[$campos_array[1]]);
						$fianzaprestamo = trim($listado[$campos_array[2]]);
						$devoluciondescuento = trim($listado[$campos_array[3]]);
						$descripcion = trim($listado[$campos_array[4]]);

					//	 ENVIAR DATA ARRAY
						$datos[$fila_array]["id_"] = $id_;
						$datos[$fila_array]["fecha"] = $fecha;
						$datos[$fila_array]["fianzaprestamo"] = $fianzaprestamo;
						$datos[$fila_array]["devoluciondescuento"] = $devoluciondescuento;
						$datos[$fila_array]["descripcion"] = $descripcion;
				}
				$mensajeError = "Historial Encontrado.";
			}
			else{
				$respuestaOK = false;
				$contenidoOK = '<h4>No hay Elementos que mostrar.</h4>';
				$mensajeError =  'No Hay Historial';
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