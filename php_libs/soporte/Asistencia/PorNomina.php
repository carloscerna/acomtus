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
//Crear una línea. Fecha con getdate();
    $hoy = getdate();
    $NombreDia = $hoy["wday"];  // dia de la semana Nombre.
    //$dia = $hoy["d"];    // dia de la semana
    //$mes = $hoy["m"];     // mes
    $año = $hoy["year"];    // año
    //$fecha_nomina = $dia . "-" . $mes . "-" . $año;
	$fecha_nomina = date("d/m/Y");
// Inicializamos variables de mensajes y JSON
$guardar_produccion = false;
$respuestaOK = false;
$codigo_produccion = 0;
$mensajeError = ":(";
$MensajeAsueto = "";
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
$CodigoJornadaTodasSeparador = "";
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);
	$url_fotos = "/acomtus/img/fotos/";
	$url_sin_foto = "/acomtus/img/";
	$url_cat_img = "/acomtus/img/Catalogo Jornada/";
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
		//var_dump($FechaDescripcionAsueto);
		$buscar = array_search('4444441', $CodigoJornadaImagen['codigo']);
		if(!empty($buscar)){
			$codigo_img = $CodigoJornadaImagen['codigo'][$buscar];
			$descripcion_img = $CodigoJornadaImagen['descripcion'][$buscar];
		}
		/*
		var_dump($CodigoJornadaImagen);
		print $buscar . "<br>";
        echo 'Código ' . $codigo_img. ' Descripcion ' . $descripcion_img;
        exit;*/
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
			break;
			case "BuscarPersonalRutaCodigo":
				$codigo_personal = trim($_POST['codigo_personal']);
				$CodigoDepartamentoEmpresa = trim($_POST['codigo_departamento_empresa']);
				$CodigoRuta = "00";

				if($CodigoDepartamentoEmpresa == "02"){
					// Armamos el query.
						$query = "SELECT u.codigo_ruta, 
						cat_ruta.descripcion as descripcion, u.codigo_ruta as codigo
							FROM usuarios u 
								INNER JOIN catalogo_ruta cat_ruta ON cat_ruta.id_ruta = TO_NUMBER(u.codigo_ruta,'99')
									WHERE u.codigo_personal = '$codigo_personal'";
				}else{
					// CONSULTA LOS OTROS DEPARTAMENTO Y SOLO LO FILTRA CON EL CODIGO DELDEPARTAMAENTO.
					$query = "SELECT u.codigo_departamento_empresa as codigo, 
					cat_empresa.descripcion as descripcion
						FROM usuarios u 
							INNER JOIN catalogo_departamento_empresa cat_empresa ON cat_empresa.id_departamento_empresa = TO_NUMBER(u.codigo_departamento_empresa,'99')
								WHERE u.codigo_personal = '$codigo_personal'";
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
                        // Nombres de los campos de la tabla.
                            $Descripcion = trim($listado['descripcion']);
							$Codigo = trim($listado['codigo']);

						// Rellenando la array.
							$datos[$fila_array]["Descripcion"] = $Descripcion;
							$datos[$fila_array]["Codigo"] = $Codigo;
					}
					$datos[$fila_array]["mensajeError"] = 'Código Encontrado.';
					$datos[$fila_array]["respuestaOK"] = true;
                        // TOTAL DE EMPLEADOS. POR RUTA U OFICINA
						if($CodigoDepartamentoEmpresa == "02"){
							$query_count = "SELECT count(*) as totalempleados FROM personal WHERE codigo_ruta = '$Codigo' and codigo_estatus = '01'";
						}else{
							$query_count = "SELECT count(*) as totalempleados FROM personal WHERE codigo_departamento_empresa = '$Codigo' and codigo_estatus = '01'";
						}
                        // Ejecutamos el Query.
                            $consulta_count = $dblink -> query($query_count);
                            // Validar si hay registros.
				                if($consulta_count -> rowCount() != 0){
                         			// convertimos el objeto
                                    while($listado = $consulta_count -> fetch(PDO::FETCH_BOTH))
                                    {
                                        // Nombres de los campos de la tabla.
                                            $TotalEmpleados = trim($listado['totalempleados']);
                                        // Rellenando la array.
                                            $datos[$fila_array]["TotalEmpleados"] = $TotalEmpleados;
                                    }

                                }
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
            case "BuscarEmpleadosPorRuta":
                $codigo_ruta = trim($_POST['CodigoRuta']);
				$CodigoDepartamentoEmpresa = trim($_POST['CodigoDepartamentoEmpresa']);
				$fecha = trim($_POST['fecha']);
				$codigo_personal_encargado = trim($_POST['codigo_personal_encargado']);
				$RegistroGuardar = "No";
				$RegistroGuardarCodigoPersonal = array(); $foto = array(); $RegistroGuardarFoto = array(); $RegistroGuardarNombreCompleto = array();
				// CONSULTA PARA VERIFICAR LA EMPLEADOS SEGUN LA RUTA U OFICINA
					if($CodigoDepartamentoEmpresa == "02"){
						$query = "SELECT codigo, btrim(nombres || CAST(' ' AS VARCHAR) || apellidos) AS nombre_completo, foto
							FROM personal 
								WHERE codigo_ruta = '$codigo_ruta' and codigo_estatus = '01' ORDER BY codigo";
					}else{
						$query = "SELECT codigo, btrim(nombres || CAST(' ' AS VARCHAR) || apellidos) AS nombre_completo, foto
							FROM personal WHERE codigo_departamento_empresa = '$CodigoDepartamentoEmpresa' and codigo_estatus = '01' ORDER BY codigo";
					}
				/////////////////////////////////////////////////////////////////////////////////////////////////////	
				// CONSULTAR SI LA FECHA TIENE ASUETO.	
				/////////////////////////////////////////////////////////////////////////////////////////////////////
					$asueto = false;
					$query_asueto = "SELECT * FROM asuetos WHERE fecha = '$fecha'";
					// EJECUTAR LA CONSULTA
					$consulta_asueto = $dblink -> query($query_asueto);
					// convertimos el objeto
					if($consulta_asueto -> rowCount() != 0){
						$asueto = true;
						while($listado = $consulta_asueto -> fetch(PDO::FETCH_BOTH))
						{
							// Es asueto
							$descripcion = trim($listado['descripcion']);
							$MensajeAsueto = $descripcion;
						}
					}
					// BUSCAR LA DESCRIPCIÓN DEL ASUETO Y ASIGNAR VALOR.
					///////////////////////////////////////////////////////////////////////////////////
					if($asueto == true){
						$query_asueto_id = "SELECT * FROM catalogo_tipo_licencia_o_permiso WHERE descripcion = 'A'";
						// EJECUTAR LA CONSULTA
						$consulta_asueto_id = $dblink -> query($query_asueto_id);
						// convertimos el objeto
						if($consulta_asueto_id -> rowCount() != 0){
							while($listado_id = $consulta_asueto_id -> fetch(PDO::FETCH_BOTH))
							{
								// Es asueto
								$CodigoTipoLicencia = trim($listado_id['id_']);
							}
						}	
					}
				// Ejecutamos el Query.
    				$consulta_ = $dblink -> query($query);
				// Validar si hay registros.
                    if($consulta_ -> rowCount() != 0)
					{
                        $respuestaOK = true;
                        $num = 0;
                        // convertimos el objeto
                        while($listado = $consulta_ -> fetch(PDO::FETCH_BOTH))
                        {
							// variables de la tabla personal.
								$codigo_personal = trim($listado['codigo']);
								$nombre_completo = trim($listado['nombre_completo']);
								$RegistroGuardarNombreCompleto[] = $nombre_completo;
								$foto = trim($listado['foto']);
								$RegistroGuardarCodigoPersonal[] = $codigo_personal;
								if(empty($foto)){
									$foto = $url_sin_foto . 'avatar_masculino.png';
									$RegistroGuardarFoto[] = $foto;
								}else{
									$foto = $url_fotos . $foto;
									$RegistroGuardarFoto[] = $foto;
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
													}
												// Bucar Jordana Imagen.
													BuscarJornadaImagen($imgJornada);
											}
										}else{
											///////////////////////////////////////////////////////////////////////////////////////////////////////////
											// GUARDAR DATOS VALIDAR SI LA FECHA TIENE ASUETO.
											///////////////////////////////////////////////////////////////////////////////////////////////////////////
											if ($asueto == true) {
												$query_g = "INSERT INTO personal_asistencia (codigo_personal, fecha, hora, codigo_personal_encargado, codigo_tipo_licencia) 
													VALUES('$codigo_personal','$fecha','$hora_actual','$codigo_personal_encargado','$CodigoTipoLicencia')";
											}else{
												$query_g = "INSERT INTO personal_asistencia (codigo_personal, fecha, hora, codigo_personal_encargado) 
												VALUES('$codigo_personal','$fecha','$hora_actual','$codigo_personal_encargado')";
											}
											// Ejecutamos el Query.
												$consulta = $dblink -> query($query_g);
											// Linea de mensajes.
												$RegistroGuardar = "Si";
											//
												$mensajeError = "Registros Guardados...";
										}
						}	// 	WHILE DE LA CONSULTA PARA BUSCAR EL NOMBRE DEL EMPLEADO.
                        //
                            $respuestaOK = true;
                            $mensajeError = "Registros Encontrados...";
                    }
                    else{
						//
                            $respuestaOK = false;
                            $mensajeError = "";
                            $contenidoOK = '';
                    }


					// SI HA GUARDO REGISTROS.
						if ($RegistroGuardar == "Si") {
							//var_dump($RegistroGuardarCodigoPersonal);
							// recorrer la matriz.
							for ($Fila=0; $Fila < count($RegistroGuardarCodigoPersonal) ; $Fila++) { 
								// BUACAR EL REGISTRO ANTES DE GUARDARLO PARA QUE NO SE REPITA CON RESPECTO A LA FECHA
								$query_buscar = "SELECT * FROM personal_asistencia WHERE codigo_personal = '$RegistroGuardarCodigoPersonal[$Fila]' and fecha = '$fecha' and codigo_personal_encargado = '$codigo_personal_encargado'";
								$codigo_personal = $RegistroGuardarCodigoPersonal[$Fila];
								$foto = $RegistroGuardarFoto[$Fila];
								$nombre_completo = $RegistroGuardarNombreCompleto[$Fila];
								// Ejecutamos el Query.
									$consulta_g = $dblink -> query($query_buscar);
									// Validar si hay registros.
									$imgJornada = "#";
									if($consulta_g -> rowCount() != 0){
										while($listado_g = $consulta_g -> fetch(PDO::FETCH_BOTH))
										{
											// Variables.
												$Id_ = trim($listado_g['id_']);
												$CodigoJornada = trim($listado_g['codigo_jornada']);
												$CodigoLicencia = trim($listado_g['codigo_tipo_licencia']);
												$CodigoJornadaAsueto = trim($listado_g['codigo_jornada_asueto']);
												$CodigoJornadaVacaciones = trim($listado_g['codigo_jornada_vacaciones']);
												$CodigoJornadaDescanso = trim($listado_g['codigo_jornada_descanso']);
												$CodigoJornadaE4H = trim($listado_g['codigo_jornada_e_4h']);
												$CodigoJornadaNocturna = trim($listado_g['codigo_jornada_nocturna']);
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
												}
											// Bucar Jordana Imagen.
												BuscarJornadaImagen($imgJornada);
										}
									}
							}	// FINAL DEL FOR CUANDO LOS REGISTROS HAN SIDO GUARDADOS ANTERIOREMES.
						}	// IF QUE CONDICIONA SI SE VAN A MOSTRAR LOS REGISTROS GUARDADOS.
            break;
			case 'ActualizarJornada':
				// Variables.
					$Id_ = trim($_POST["Id_"]);
					$codigo_jornada = trim($_POST["CJ"]);
					$codigo_tipo_licencia = trim($_POST["CTL"]);
					$codigo_jornada_asueto = trim($_POST["CJA"]);
					$codigo_jornada_vacaciones = trim($_POST["CJV"]);
					$codigo_jornada_descanso = trim($_POST["CJD"]);
					$codigo_jornada_4_extra = trim($_POST["CJE4H"]);
					$codigo_jornada_nocturnidad = trim($_POST["CJN"]);
				//
					$codigo_perfil = trim($_POST["CodigoPerfil"]);
					$codigo_personal_usuario = trim($_POST["CodigoPersonal"]);
				// 	VERIFICAR QUE TIPO DE USUARIO DESEA MODIFICAR EL REGISTRO DEL PUNTEADO.
				if($codigo_perfil == '01' || $codigo_perfil == '02' || $codigo_perfil == '05' || $codigo_perfil == '07' || $codigo_perfil == '09' || $codigo_perfil == '10' || $codigo_perfil == '11'){
					$query_update = "UPDATE personal_asistencia SET
							codigo_jornada = '$codigo_jornada',
							codigo_tipo_licencia = '$codigo_tipo_licencia',
							codigo_jornada_asueto = '$codigo_jornada_asueto',
							codigo_personal_encargado = '$codigo_personal_usuario',
							codigo_jornada_vacaciones = '$codigo_jornada_vacaciones',
							codigo_jornada_descanso = '$codigo_jornada_descanso',
							codigo_jornada_e_4h = '$codigo_jornada_4_extra',
							codigo_jornada_nocturna = '$codigo_jornada_nocturnidad'
								WHERE id_ = '$Id_'
						";
					// ACTUALIZAR QUERY
						$consulta_update = $dblink -> query($query_update);
					//
						$respuestaOK = true;
						$mensajeError = "Punteo Actualizado";
						$contenidoOK = '';
							break;
				}
				break;
				case "EditarJornada":
					//$Todos = base64_decode($_POST['Id_']);
					$Todos = ($_POST['Id_']);
					//
					$VariablesTabla = explode("#",$Todos);
						$Foto = $VariablesTabla[0];
						$ImgJornada = $VariablesTabla[1];
						$Id_ = $VariablesTabla[2];
						$CodigoPersonal = $VariablesTabla[3];
						$NombreCompleto = $VariablesTabla[4];
						$CodigoJornadaTodas = $VariablesTabla[5];
						$CodigoJornadaTodasSeparador = $VariablesTabla[6];
					//
					$fila_array = 0;
					// PASAR AL DATA.
						$datos[$fila_array]["Foto"] = $Foto;
						$datos[$fila_array]["ImgJornada"] = $ImgJornada;
						$datos[$fila_array]["Id_"] = $Id_;
						$datos[$fila_array]["CodigoPersonal"] = $CodigoPersonal;
						$datos[$fila_array]["NombreCompleto"] = $NombreCompleto;
						$datos[$fila_array]["CodigoJornadaTodas"] = $CodigoJornadaTodas;
						$datos[$fila_array]["CodigoJornadaTodasSeparador"] = $CodigoJornadaTodasSeparador;
					// 
						$fila_array++;
				break;
				case 'BuscarJornada':
					# Buscar en tabla catalogo_jornada.
					// armando el Query.
						$query = "SELECT id_, descripcion, hora_desde, hora_hasta, descripcion_completa from catalogo_jornada ORDER BY id_";
						// Ejecutamos el Query.
						$consulta = $dblink -> query($query);
						// Inicializando el array
						$datos=array(); $fila_array = 0;
						// Recorriendo la Tabla con PDO::
							while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
							{
								// Nombres de los campos de la tabla.
							$codigo = trim($listado['id_']); 
							$descripcion = trim($listado['descripcion']);
							$descripcion_completa = trim($listado['descripcion_completa']);
							//. ' ' . trim($listado['hora_hasta']);
							// Rellenando la array.
							   	$datos[$fila_array]["codigo"] = $codigo;
								$datos[$fila_array]["descripcion"] = $descripcion;
								$datos[$fila_array]["descripcion_completa"] = $descripcion_completa;
									$fila_array++;
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
	}elseif($_POST["accion"] === "BuscarPersonalCodigo" or $_POST["accion"] === "BuscarTipoLicencia" or $_POST["accion"] === "BuscarPersonalRutaCodigo"
			or $_POST["accion"] == "EditarJornada" or $_POST["accion"] == "BuscarJornada"){
		echo json_encode($datos);
		}
	else{
		// Armamos array para convertir a JSON
		$salidaJson = array("respuesta" => $respuestaOK,
			"mensaje" => $mensajeError,
			"contenido" => $contenidoOK,
			"mensajeAsueto" => $MensajeAsueto);
		echo json_encode($salidaJson);
	}

function BuscarJornadaImagen($imgJornada){
	global $contenidoOK, $foto, $codigo_personal, $nombre_completo, $imgJornada, $Id_, $CodigoJornadaTodas, $CodigoJornadaTodasSeparador;
	$datos_ = $foto . "#" . $imgJornada . "#". $Id_ ."#". $codigo_personal ."#". $nombre_completo . "#" . $CodigoJornadaTodas . "#" . $CodigoJornadaTodasSeparador;
	$datos_codificados = $datos_;//base64_encode($datos_);
	//8 CREAR FILAS Y COLUMNAS.
	$contenidoOK .= "<tr>
		<td class='mx-auto text-center'>
			<img src='$foto' class='rounded' alt='#' width='80' height='90'>
		</td>
		<td>
			<div class='col col-md-6'>
				<label id='Codigo-$codigo_personal'>Código: $codigo_personal</label>
			</div>
			<div class='col col-md-6'>
				<label class='text-primary text-bold'>$nombre_completo</label>
			</div>
		</td>
		<td>
			<div class='mx-auto text-center'>
				<a data-accion=editarAsistencia class='btn btn-sm btn-info' href='$datos_codificados' tabindex='-1' data-toggle='tooltip' data-placement='top' title='Punteo'>
					<img src='$imgJornada' class='rounded' alt='' width='60' height='65'>
				</a>
			</div>
			<div>
				
			</div>
		</td>
	";
}
?>