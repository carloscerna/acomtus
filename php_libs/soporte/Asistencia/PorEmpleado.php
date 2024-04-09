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
$url_cat_img = "/acomtus/img/Catalogo Jornada/";
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);
// Incluimos el archivo de funciones y conexi�n a la base de datos
	include($path_root."/acomtus/includes/mainFunctions_conexion.php");
	include($path_root."/acomtus/includes/funciones.php");
// OBTENER VALORES DE LA TABLA CATALOGO_JORNADA_IMAGENES.
// CREAR ARRAY ASOCIATIVA DE LA TABLA: asuetos
	$query_j_img = "SELECT * FROM catalogo_jornada_imagenes ORDER BY id_";    // consulta
	$resultado_j_img = $dblink -> query($query_j_img); // ejecuciónd ela consult.a
// while
	$Codigo = ""; $Descripcion = ""; $codigo_img = ""; $descripcion_img = "";
	while($listado_j_img = $resultado_j_img -> fetch(PDO::FETCH_BOTH))
		{
			$codigo = $listado_j_img["codigo"];
			$descripcion = trim($listado_j_img["descripcion"]);
			// CREAR ARRAY ASOCIATIVA
				$CodigoJornadaImagen["codigo"][] = $codigo;
				$CodigoJornadaImagen["descripcion"][] = $descripcion;
		}
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
				$CodigoDepartamentoEmpresa = trim($_POST['codigo_departamento_empresa']);
				$codigo_personal_encargado = trim($_POST['codigo_personal_encargado']);
				// Armamos el query.
				$query = "SELECT p.id_personal, p.codigo, TRIM(p.nombres) as nombre, TRIM(p.apellidos) as apellido, btrim(p.nombres || CAST(' ' AS VARCHAR) || p.apellidos) AS nombre_empleado,
							p.foto, p.codigo_genero, p.codigo_departamento_empresa
								FROM personal p 
									WHERE codigo = '$codigo_personal' and codigo_departamento_empresa = '$CodigoDepartamentoEmpresa'";
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
							$codigo_departamento_empresa = trim($listado['codigo_departamento_empresa']);
							$nombre_personal = trim($listado['nombre_empleado']);
							$url_foto = trim($listado['foto']);
							$codigo_genero = trim($listado['codigo_genero']);

						// Rellenando la array.
							$datos[$fila_array]["id_"] = $id_;
							$datos[$fila_array]["codigo"] = $codigo;
							$datos[$fila_array]["codigo_departamento_empresa"] = $codigo_departamento_empresa;
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
					   $query_asueto = "SELECT * FROM asuetos WHERE fecha = '$fecha'";
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
				else{	// verificar si el codigo no es el mismo de la empresa del Usuario que ingresa la asistencia.
					$contenidoOK = '';
					$datos[$fila_array]["respuestaOK"] = false;
					$datos[$fila_array]["mensajeError"] = 'Código No Existe o No Pertenece a este Departamento.';
				}
				// BUSCAR EN PERSONAL ASISTENCIA.
				// BUACAR EL REGISTRO ANTES DE GUARDARLO PARA QUE NO SE REPITA CON RESPECTO A LA FECHA
				$query_buscar_i = "SELECT * FROM personal_asistencia WHERE codigo_personal = '$codigo_personal' and fecha = '$fecha' and codigo_personal_encargado = '$codigo_personal_encargado'";
				// Ejecutamos el Query.
					$consulta_b = $dblink -> query($query_buscar_i);
					// Validar si hay registros.
					$imgJornada = "#";
					if($consulta_b -> rowCount() != 0){
						while($listado_b = $consulta_b -> fetch(PDO::FETCH_BOTH))
						{
							// Variables.
								$Id_ = trim($listado_b['id_']);
								$CodigoJornada = trim($listado_b['codigo_jornada']);
								$CodigoLicencia = trim($listado_b['codigo_tipo_licencia']);
								$CodigoJornadaAsueto = trim($listado_b['codigo_jornada_asueto']);
								$CodigoJornadaVacaciones = trim($listado_b['codigo_jornada_vacaciones']);
								$CodigoJornadaDescanso = trim($listado_b['codigo_jornada_descanso']);
								$CodigoJornadaE4H = trim($listado_b['codigo_jornada_e_4h']);
								$CodigoJornadaNocturna = trim($listado_b['codigo_jornada_nocturna']);
							//	FORMAR EL CODIGO ALL PARA LA IMAGEN.
								$CodigoJornadaTodas = $CodigoJornada.$CodigoLicencia.$CodigoJornadaAsueto.
													$CodigoJornadaVacaciones.$CodigoJornadaDescanso.$CodigoJornadaE4H.
													$CodigoJornadaNocturna;
							//	FORMAR EL CODIGO ALL PARA LA IMAGEN.
								$CodigoJornadaTodasSeparador = $CodigoJornada.".".$CodigoLicencia.".".$CodigoJornadaAsueto.
															".".$CodigoJornadaVacaciones.".".$CodigoJornadaDescanso.".".$CodigoJornadaE4H.
															".".$CodigoJornadaNocturna;
							// Condiciones para la Imagen.
								$buscar = array_search($CodigoJornadaTodas, $CodigoJornadaImagen['codigo']);
								if(!empty($buscar)){
									$codigo_img = $CodigoJornadaImagen['codigo'][$buscar];
									$descripcion_img = $CodigoJornadaImagen['descripcion'][$buscar];
									$imgJornada = $CodigoJornadaImagen['descripcion'][$buscar];
									$imgJornada = $url_cat_img . $imgJornada;
								}	// FIN DEL IF DE BUSQUEDA
						}	// FIN DEL WHILE DE SELECT PERSONAL ASISTENCIA
						// PASAR EL IMGJORNADA.
							$fila_array++;
							$datos[$fila_array]["imgJornada"] = $imgJornada;
					}	// FIN DEL IF ROWCOUNT():
			break;
			case "BuscarPersonalRutaCodigo":
				$codigo_personal = trim($_POST['codigo_personal']);
				// Armamos el query.
				$query = "SELECT u.codigo_ruta, cat_ruta.descripcion as descripcion_ruta, u.codigo_ruta
						FROM usuarios u 
							INNER JOIN catalogo_ruta cat_ruta ON cat_ruta.id_ruta = TO_NUMBER(u.codigo_ruta,'99')
								WHERE u.codigo_personal = '$codigo_personal'";
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
                            $DescripcionRuta = trim($listado['descripcion_ruta']);
							$CodigoRuta = trim($listado['codigo_ruta']);

						// Rellenando la array.
							$datos[$fila_array]["DescripcionRuta"] = $DescripcionRuta;
							$datos[$fila_array]["CodigoRuta"] = $CodigoRuta;
					}
					$datos[$fila_array]["mensajeError"] = 'Código Encontrado.';
					$datos[$fila_array]["respuestaOK"] = true;
				}
				else{	// verificar si el codigo no es el mismo de la empresa del Usuario que ingresa la asistencia.
					$contenidoOK = '';
					$datos[$fila_array]["respuestaOK"] = false;
					$datos[$fila_array]["mensajeError"] = 'Ruta no asignada.';
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
				$BooleanNocturnidad = trim($_POST['Nocturnidad']);
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
				// GUARDAR NOCTURNIDAD.
				if($BooleanNocturnidad == "si"){
					$codigo_jornada_nocturnidad = '5';
				}else{
					$codigo_jornada_nocturnidad = '4';
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
					$codigo_jornada_4_extra = '4';
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
				if($codigo_perfil == '01' || $codigo_perfil == '02' || $codigo_perfil == '05' || $codigo_perfil == '07' || $codigo_perfil == '08' || $codigo_perfil == '09' || $codigo_perfil == '10' || $codigo_perfil == '11'){
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
												codigo_jornada_e_4h = '$codigo_jornada_4_extra',
												codigo_jornada_nocturna = '$codigo_jornada_nocturnidad'
													WHERE id_ = '$id_'
										";
							// Ejecutamos el Query.
								$consulta_update = $dblink -> query($query_update);
							//
								$respuestaOK = true;
								$mensajeError = "Se ha Actualizado el registro correctamente";
								$contenidoOK = $query_update;
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
									$query = "INSERT INTO personal_asistencia (codigo_personal, fecha, hora, codigo_jornada, codigo_tipo_licencia, codigo_jornada_asueto, codigo_personal_encargado, codigo_jornada_vacaciones, codigo_jornada_descanso, codigo_jornada_e_4h, codigo_jornada_nocturna) 
													VALUES('$codigo_personal','$fecha','$hora_actual','$codigo_jornada','$codigo_tipo_licencia','$codigo_jornada_asueto','$codigo_personal_usuario', '$codigo_jornada_vacaciones', '$codigo_jornada_descanso','$codigo_jornada_4_extra', '$codigo_jornada_nocturnidad')";
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
								$query = "INSERT INTO personal_asistencia (codigo_personal, fecha, hora, codigo_jornada, codigo_tipo_licencia, codigo_jornada_asueto, codigo_personal_encargado, codigo_jornada_vacaciones, codigo_jornada_descanso, codigo_jornada_e_4h, codigo_jornada_nocturna) 
												VALUES('$codigo_personal','$fecha','$hora_actual','$codigo_jornada','$codigo_tipo_licencia','$codigo_jornada_asueto','$codigo_personal_usuario','$codigo_jornada_vacaciones', '$codigo_jornada_descanso','$codigo_jornada_4_extra','$codigo_jornada_nocturnidad')";
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
	}elseif($_POST["accion"] === "BuscarPersonalCodigo" or $_POST["accion"] === "BuscarTipoLicencia" or $_POST["accion"] === "BuscarPersonalRutaCodigo"){
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