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
//
// Establecer formato para la fecha.
// 
	date_default_timezone_set('America/El_Salvador');
	setlocale(LC_TIME,'es_SV');
//	Hora Actual.
    $hora_actual = date("h:i:s a"); 
// Inicializamos variables de mensajes y JSON
$guardar_produccion = false;
$respuestaOK = false;
$codigo_produccion = 0;
$mensajeError = ":(";
$contenidoOK = "";
$totalIngresoOK = 0;
$totalIngresoOKPantalla = 0;
$IdProduccionOK = 0;
$cantidadTiqueteOK = 0;
$lista = "";
$arreglo = array();
$datos = array();
$fila_array = 0;
$fila = 0;
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
			case 'BuscarPersonalCodigo':
				$codigo_personal = trim($_POST['codigo_personal']);
				$fecha = trim($_POST['fecha']);
				// Armamos el query.
				$query = "SELECT p.id_personal, p.codigo, TRIM(p.nombres) as nombre, TRIM(p.apellidos) as apellido, btrim(p.nombres || CAST(' ' AS VARCHAR) || p.apellidos) AS nombre_empleado,
							p.foto, p.codigo_genero
                         		FROM personal p WHERE codigo = '$codigo_personal'";
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
                            $id_ = trim($listado['id_personal']);
							$codigo = trim($listado['codigo']);
							$nombre_personal = trim($listado['nombre_empleado']);
							$url_foto = trim($listado['foto']);
							$codigo_genero = trim($listado['codigo_genero']);

						// Rellenando la array.
							$datos[$fila_array]["id_"] = $id_;
							$datos[$fila_array]["codigo"] = $codigo;
							$datos[$fila_array]["nombre_empleado"] = $nombre_personal;
							$datos[$fila_array]["codigo_genero"] = $codigo_genero;
							$datos[$fila_array]["url_foto"] = $url_foto;
					}
					$datos[$fila_array]["mensajeError"] = 'Código Encontrado.';
					$datos[$fila_array]["respuestaOK"] = true;
				
					// BUSCAR SI LA FECHA ES D{IA DE ASUETO}
					//
					// REVISAR Y CALCULAR SI LA FECHA PERTENECIA A UN DÍA DE ASUETO
					$fecha_partial = explode("-",$fecha);
					$asueto = false;
					//print_r($fecha_partial);
					$asueto_mes = (int)$fecha_partial[1];    // mes 
					$asueto_dia = (int)$fecha_partial[2];    // dia
					//print 'Mes ' .$asueto_mes;
					//print 'Día ' . $asueto_dia;
					 // ARMAR LA CONSULTA
					   $query_asueto = "SELECT * FROM catalogo_asuetos WHERE mes = '$asueto_mes' and dia = '$asueto_dia'";
						// EJECUTAR LA CONSULTA
						$consulta_asueto = $dblink -> query($query_asueto);
						// convertimos el objeto
						if($consulta_asueto -> rowCount() != 0){
							while($listado = $consulta_asueto -> fetch(PDO::FETCH_BOTH))
							{
								// Es asueto
								$descripcion = trim($listado['descripcion']);
								$datos[$fila_array]["descripcion"] = $descripcion;
								$datos[$fila_array]["asueto"] = "si";
							}
						}else{
							$datos[$fila_array]["descripcion"] = "--";
							$datos[$fila_array]["asueto"] = "no";
						}
				}
				else{
					$contenidoOK = '';
					$datos[$fila_array]["respuestaOK"] = false;
					$datos[$fila_array]["mensajeError"] = 'Código No Existe.';
				}
			break;
			case 'BuscarTipoLicencia':
                # Buscar en tabla catalogo_jornada.
                // armando el Query.
                    $query = "SELECT id_, descripcion, descripcion_completa from catalogo_tipo_licencia_o_permiso WHERE id_ > '1' ORDER BY id_";
                    // Ejecutamos el Query.
                    $consulta = $dblink -> query($query);
                    // Inicializando el array
                    $datos=array(); $fila_array = 0;
                    // Recorriendo la Tabla con PDO::
                        while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                        {
                            // Nombres de los campos de la tabla.
                        $codigo = trim($listado['id_']); $descripcion_completa = trim($listado['descripcion_completa']);
						$descripcion = trim($listado['descripcion']);
                        //. ' ' . trim($listado['hora_hasta']);
                        // Rellenando la array.
                           $datos[$fila_array]["codigo"] = $codigo;
                            $datos[$fila_array]["descripcion"] = $descripcion;
							$datos[$fila_array]["descripcion_completa"] = $descripcion_completa;
                                $fila_array++;
                            }
			break;			
			case 'GuardarAsistencia':
				$codigo_personal = trim($_POST['CodigoPersonal']);
				$codigo_personal_usuario = trim($_POST['codigo_personal_usuario']);
				$fecha = trim($_POST['FechaAsistencia']);
				$tipolicenciacheck = trim($_POST['tipochecks']);
				$boolean_asueto = trim($_POST['BooleanAsueto']);
				$boolean_vacaciones = trim($_POST['BooleanTV']);
				$boolean_descanso = trim($_POST['BooleanDescanso']) ;
				$codigo_perfil = trim($_POST['codigo_perfil']);
				$boolean_RadiosE = trim($_POST['RadiosE']);

				// CALULAR CUANDO SEA EXTRA EL 4 HORAS.
				if($boolean_RadiosE == "si"){
					$codigo_jornada_4_extra = trim($_POST['lstJornadaExtraCuatroHoras']);
				}else{
					$codigo_jornada_4_extra = '4';
				}
				
				// VALIDAR VALORES PARA TIPO LICENCIA JORNADA.
				if($tipolicenciacheck == "on"){
					$codigo_tipo_licencia = trim($_POST['lstTipoLicencia']);
					$codigo_jornada = '4';

						// VALIDAR VALORES CUANDO TRABAJO VACACIONES SEA IGUAL A "SI"
						//
						if($boolean_vacaciones == "si"){
							$codigo_jornada_vacaciones = trim($_POST['lstJornadaTV']);
						}else{
							$codigo_jornada_vacaciones = '4';
						}
						// VALIDAR VALORES CUANDO DESCANSO SEA IGUAL A "SI"
						if($boolean_descanso == "si"){
							$codigo_jornada_descanso = trim($_POST['lstJornadaDescanso']);
						}else{
							$codigo_jornada_descanso = '4';
						}
				}else{
					$codigo_jornada = trim($_POST['lstJornada']);
					$codigo_tipo_licencia = "1";
					$codigo_jornada_vacaciones = '4';
					$codigo_jornada_descanso = '4';
				}
				// VALIDAR VALORES CUANDO ASUETO SEA IGUAL A "SI"
				if($boolean_asueto == "si"){
					$codigo_jornada_asueto = trim($_POST['lstJornadaAsueto']);
				}else{
					$codigo_jornada_asueto = trim($_POST['lstJornadaAsueto']);
				}
				// 	validar la fecha de la producción.
				$fechas = explode("-",$fecha);
				$dia = $fechas[2];
				$mes = $fechas[1];
				$ann = $fechas[0];

				if(strlen($ann) > 4){
					$mensajeError = "Fecha No Válida $dia . $mes . $ann";
				break;
				}
				if(checkdate($mes, $dia, $ann)){
				//echo "fecha valida";
				}else{
				//echo "fecha no válida";
					$mensajeError = "Fecha No Válida $dia . $mes . $ann";
						break;
				}
				// 	VERIFICAR QUE TIPO DE USUARIO DESEA MODIFICAR EL REGISTRO DEL PUNTEADO.
				// SOLO EL ADMINISTRADOR, GERSION PERSONAL Y RECURSOS HUMANOS PUEDEN MODIFICARLO
					if($codigo_perfil == '01' || $codigo_perfil == '02' || $codigo_perfil == '05'){
						// BUACAR EL REGISTRO ANTES DE GUARDARLO PARA QUE NO SE REPITA CON RESPECTO A LA FECHA
						$query_buscar = "SELECT * FROM personal_asistencia WHERE codigo_personal = '$codigo_personal' and fecha = '$fecha'";
						// Ejecutamos el Query.
							$consulta_b = $dblink -> query($query_buscar);
							// Validar si hay registros.
							if($consulta_b -> rowCount() != 0){
								// convertimos el objeto
								while($listado_b = $consulta_b -> fetch(PDO::FETCH_BOTH))
									{
									// Nombres de los campos de la tabla.
										$id_ = trim($listado_b['id_']);
									}
								//$mensajeError = "El Código Empleado ya fue Ingresado...";
									 $query_update = "UPDATE personal_asistencia SET
													codigo_jornada = '$codigo_jornada',
													codigo_tipo_licencia = '$codigo_tipo_licencia',
													codigo_jornada_asueto = '$codigo_jornada_asueto',
													codigo_personal_encargado = '$codigo_personal_usuario',
													codigo_jornada_vacaciones = '$codigo_jornada_vacaciones',
													codigo_jornada_descanso = '$codigo_jornada_descanso',
													codigo_jornada_e_4h = '$codigo_jornada_4_extra'
														WHERE id_ = '$id_'
											";
								// Ejecutamos el Query.
									$consulta_update = $dblink -> query($query_update);
								//
									$respuestaOK = true;
									$mensajeError = "Se ha Actualizado el registro correctamente";
									$contenidoOK = '';
									break;
							}else{
								// SI NO EXISTE AGREGARLO
								//
								//$mensajeError = "El Código Empleado NO EXISTE...";
								//$respuestaOK = false;
									// BUACAR EL REGISTRO ANTES DE GUARDARLO PARA QUE NO SE REPITA CON RESPECTO A LA FECHA
									$query_buscar = "SELECT * FROM personal_asistencia WHERE codigo_personal = '$codigo_personal' and fecha = '$fecha'";
									// Ejecutamos el Query.
										$consulta_b = $dblink -> query($query_buscar);
										// Validar si hay registros.
										if($consulta_b -> rowCount() != 0){
											$mensajeError = "El Código Empleado ya fue Ingresado...";
												break;
										}
								// GUARDAR DATOS SIN VALIDAR
									$query = "INSERT INTO personal_asistencia (codigo_personal, fecha, hora, codigo_jornada, codigo_tipo_licencia, codigo_jornada_asueto, codigo_personal_encargado, codigo_jornada_vacaciones, codigo_jornada_descanso, codigo_jornada_e_4h) 
													VALUES('$codigo_personal','$fecha','$hora_actual','$codigo_jornada','$codigo_tipo_licencia','$codigo_jornada_asueto','$codigo_personal_usuario', '$codigo_jornada_vacaciones', '$codigo_jornada_descanso','$codigo_jornada_4_extra')";
								// Ejecutamos el Query.
									$consulta = $dblink -> query($query);
								// Linea de mensajes.

								///////////////////////////////////////////////////////////////////////////////////////
								// VALIDAR SI SE GUARDO BIEN LA INFORMACI{ON.}
								if($consulta == true){
									$respuestaOK = true;
									$mensajeError = "Se ha Guardado el registro correctamente";
									$contenidoOK = '';
								}
								else{
									$respuestaOK = false;
									$mensajeError = "No se puede guardar el registro en la base de datos ";
								}
									break;
							}
					}else{
						// CUANDO NO ES EL ADMINISTRADOR U OTROS
						// BUSCAR EL REGISTRO ANTES DE GUARDARLO PARA QUE NO SE REPITA CON RESPECTO A LA FECHA
						$query_buscar = "SELECT * FROM personal_asistencia WHERE codigo_personal = '$codigo_personal' and fecha = '$fecha'";
						// Ejecutamos el Query.
							$consulta_b = $dblink -> query($query_buscar);
							// Validar si hay registros.
							if($consulta_b -> rowCount() != 0){
								$mensajeError = "El Código Empleado ya fue Ingresado...";
									break;
							}
						// GUARDAR DATOS SIN VALIDAR
							$query = "INSERT INTO personal_asistencia (codigo_personal, fecha, hora, codigo_jornada, codigo_tipo_licencia, codigo_jornada_asueto, codigo_personal_encargado, codigo_jornada_vacaciones, codigo_jornada_descanso, codigo_jornada_e_4h) 
											VALUES('$codigo_personal','$fecha','$hora_actual','$codigo_jornada','$codigo_tipo_licencia','$codigo_jornada_asueto','$codigo_personal_usuario','$codigo_jornada_vacaciones', '$codigo_jornada_descanso','$codigo_jornada_4_extra')";
						// Ejecutamos el Query.
							$consulta = $dblink -> query($query);
						// Linea de mensajes.

						///////////////////////////////////////////////////////////////////////////////////////
						// VALIDAR SI SE GUARDO BIEN LA INFORMACI{ON.}
						if($consulta == true){
							$respuestaOK = true;
							$mensajeError = "Se ha Guardado el registro correctamente";
							$contenidoOK = '';
						}
						else{
							$respuestaOK = false;
							$mensajeError = "No se puede guardar el registro en la base de datos ";
						}
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
	}elseif($_POST["accion"] === "BuscarPersonalCodigo" or $_POST["accion"] === "BuscarTipoLicencia"){
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