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
$contenidoOKNoGuardados = "";
$totalIngresoOK = 0;
$totalIngresoOKPantalla = 0;
$IdProduccionOK = 0;
$cantidadTiqueteOK = 0;
$lista = "";
$arreglo = array();
$datos = array();
$fila_array = 0;
$fila = 0;
$NoGuardado = 0;
$QueryLista = "";
$vendido = false;
$CreadoTemp = false;
$Entregado = false;
$AccionBuscar = "";
$desde_asignado = 0;
$hasta_asignado = 0;
$codigo_inventario_tiquete = 0;
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
			$AccionBuscar = $_POST['accion'];
		switch ($AccionBuscar) {
			case 'BuscarPorId':
				$id_x = trim($_POST['id_x']);
				// Armamos el query.
				$query = "SELECT * FROM produccion WHERE id_ = " . $id_x
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
                            $id_ = trim($listado['id_']);
							$fecha = trim($listado['fecha']);
							$codigo_ruta = trim($listado['codigo_ruta']);
							$codigo_jornada= trim($listado['codigo_jornada']);
							$codigo_inventario_tiquete = trim($listado['codigo_inventario_tiquete']);
						//	CODIGO ESTATUS. SERVIRA PARA VERIFICAR SI YA FUE PROCESADO EN ESTE CASO EL CONTRO TICKET E INGRESO.
							$codigo_estatus = trim($listado['codigo_estatus']);

						// Rellenando la array.
							$datos[$fila_array]["id_"] = $id_;
							$datos[$fila_array]["fecha"] = $fecha;
							$datos[$fila_array]["codigo_ruta"] = $codigo_ruta;
							$datos[$fila_array]["codigo_jornada"] = $codigo_jornada;
							$datos[$fila_array]["codigo_inventario_tiquete"] = $codigo_inventario_tiquete;
					}
					$mensajeError = "Si Registro";
					//
					$IdProduccionOK = $id_;
				}
				else{
					$respuestaOK = true;
					$contenidoOK = '';
					$mensajeError =  'No Registro';
				}
			break;
			//
			//	PROCESADO DE GUARDADO DE CONTROL DE INGRESO DE TICKETS
			//
			case 'UltimoRegistro':
				$codigo_personal = trim($_POST['codigo_personal']);
				// Query - LIMPIAR TABLA produccion-asignado_temp
				$query_eliminar = "DELETE FROM produccion_asignado_temp WHERE codigo_personal = '$codigo_personal'";
				// Ejecutamos el query
					$consulta = $dblink -> query($query_eliminar);
				// Query - VALIDAR SI LA PRODUCCIÓN YA EXISTE.
				$query_ = "SELECT id_ FROM produccion ORDER BY id_ DESC LIMIT 1";
				// Ejecutamos el query
					$consulta = $dblink -> query($query_);
				// Validar si hay registros.
					if($consulta -> rowCount() != 0){
						while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
						{
							$codigo_produccion = $listado['id_'] + 1;
						}
						$mensajeError = "Último Registro.";
						$contenidoOK = $codigo_produccion;
						$respuestaOK = true;
					}
			break;
			case ($AccionBuscar == "AgregarNuevoTemp" || $AccionBuscar == "ActualizarTemp"):
				// TABS-1 - tabla produccion.
				$fecha_produccion = trim($_POST['FechaProduccion']);
				$NumeroCorrelativo = trim($_POST['NumeroCorrelativo']);
				$codigo_produccion = trim($_POST['NumeroCorrelativo']);
				$IdProduccionOK = trim($_POST['NumeroCorrelativo']);
				$codigo_personal = trim($_POST['cp']);
				// 	validar la fecha de la producción.
				$fechas = explode("-",$fecha_produccion);
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
				//$codigo_personal = trim($_POST['lstPersonal']);
				$codigo_jornada = trim($_POST['lstJornada']);
				//$codigo_transporte_colectivo = trim($_POST['lstUnidadTransporte']);
				$codigo_ruta = trim($_POST['lstRuta']);
				// SERIE DE DATOS
				$codigo_inventario_tiquete = trim($_POST['lstSerie']);
				$codigo_tiquete_color = trim($_POST['CodigoTiqueteColor']);
				//$existencia = trim($_POST['Existencia']);
				$existencia = intval(str_replace(",","",$_POST['Existencia']));
				$PrecioPublico = intval(str_replace("$","",$_POST['PrecioPublico']));
				// TABS-1 - tabla produccion-asignado.
				// fecha.
				$fecha_produccion_asignado = $fecha_produccion;
				$desde_asignado = intval(str_replace(",","",$_POST['DesdeAsignado']));
				$hasta_asignado = intval(str_replace(",","",$_POST['HastaAsignado']));

				//$desde_asignado = trim($_POST['DesdeAsignado']);
				//$hasta_asignado = trim($_POST['HastaAsignado']);
				$costo = trim($_POST['CantidadTiqueteAsignado']);
				$cantidad_asignado = preg_replace("/[$,]/","",trim($_POST['CantidadTiqueteAsignado']));
				$total = preg_replace("/[$,]/","",trim($_POST['TotalAsignado']));
				$total = trim($_POST['TotalAsignado']);
				///////////////////////////////////////////////////////////////////////////////////////
				// validar dese y hasta en asignado. a < 0.
					if($desde_asignado < 0 or $hasta_asignado < 0){
						$mensajeError = "Desde o Hasta | Tiquete No pueden ser Menor a 0.";
						break;
					}
				// validar dese y hasta en asignado. a < 0.
				if($desde_asignado > $hasta_asignado){
					$mensajeError = "Desde '$desde_asignado' no puede ser mayor a Hasta '$hasta_asignado' | Tiquete.";
					break;
				}
				// validar desde en asignado. a < 0.
				if($desde_asignado > $existencia){
					$mensajeError = "Desde no puede ser mayor que la Existencia  | Tiquete.";
					//break;
				}
				// validar hasta en asignado. a < 0.
				if($hasta_asignado > $existencia){
					$mensajeError = "Desde no puede ser mayor que la Existencia  | Tiquete.";
					//break;
				}
				/*	
					///////////////////////////////////////////////////////////////////////////////////////
					// TABLA PRODUCCION ASIGNADO. BUSCAR SI YA FUE PROCESADO (ENTREGADO).
					buscarProduccionEntregado();
					// validar $vendido
						if($Entregado == true){
							ListadoAsignadoTemp();
							break;
						}
					///////////////////////////////////////////////////////////////////////////////////////
					// TABLA PRODUCCION ASIGNADO. BUSCAR SI YA FUE PROCESADO (VENDIDO).
						buscarProduccionAsignadoVendido();
					// validar $vendido
						if($vendido == true){
							ListadoAsignadoTemp();
							break;
						}
						*/
					///////////////////////////////////////////////////////////////////////////////////////
					// CONSULTAR ANTES SI EXISTE TALONARIO EN TEMP.
					///////////////////////////////////////////////////////////////////////////////////////
					// TABLA PRODUCCION ASIGNADO. BUSCAR SI YA FUE PROCESADO (VENDIDO).
						buscarProduccionAsignadoTemp();
					// validar $vendido
						if($CreadoTemp == true){
							ListadoAsignadoTemp();
							break;
						}else{
							// query armar.
								$queryGuardarTemp = "INSERT INTO produccion_asignado_temp (fecha, tiquete_desde, tiquete_hasta, cantidad, total, codigo_produccion, codigo_inventario_tiquete, codigo_personal)
									VALUES ('$fecha_produccion_asignado','$desde_asignado','$hasta_asignado','$cantidad_asignado','$total','$codigo_produccion','$codigo_inventario_tiquete','$codigo_personal')";
							// Ejecutamos el query
									$resultadoGuardarTemp = $dblink -> query($queryGuardarTemp);              
						}	
					// MOSTRAR MENSAJE A LA HORA QUE GUARDO EL DATO.
					if($resultadoGuardarTemp == true){
						$respuestaOK = true;
						$mensajeError = "Producción | Talonario Guardado.";
						$contenidoOK = '';		
						ListadoAsignadoTemp();
					}
			break;
			case 'AgregarNuevo':		
				// TABS-1 - tabla produccion.
					$fecha_produccion = trim($_POST['FechaProduccion']);
					$NumeroCorrelativo = trim($_POST['NumeroCorrelativo']);
				// 	validar la fecha de la producción.
					$fechas = explode("-",$fecha_produccion);
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
					//$codigo_personal = trim($_POST['lstPersonal']);
					$codigo_jornada = trim($_POST['lstJornada']);
					//$codigo_transporte_colectivo = trim($_POST['lstUnidadTransporte']);
					$codigo_ruta = trim($_POST['lstRuta']);
				// SERIE DE DATOS
					$codigo_inventario_tiquete = trim($_POST['lstSerie']);
					$codigo_tiquete_color = trim($_POST['CodigoTiqueteColor']);
					//$existencia = trim($_POST['Existencia']);
					$existencia = intval(str_replace(",","",$_POST['Existencia']));
				// TABS-1 - tabla produccion-asignado.
					// fecha.
					$fecha_produccion_asignado = $fecha_produccion;
					$desde_asignado = intval(str_replace(",","",$_POST['DesdeAsignado']));
					$hasta_asignado = intval(str_replace(",","",$_POST['HastaAsignado']));

					//$desde_asignado = trim($_POST['DesdeAsignado']);
					//$hasta_asignado = trim($_POST['HastaAsignado']);
					$costo = trim($_POST['CantidadTiqueteAsignado']);
					$cantidad_asignado = preg_replace("/[$,]/","",trim($_POST['CantidadTiqueteAsignado']));
					$total = preg_replace("/[$,]/","",trim($_POST['TotalAsignado']));
					$total = trim($_POST['TotalAsignado']);
					///////////////////////////////////////////////////////////////////////////////////////
					// validar dese y hasta en asignado. a < 0.
						if($desde_asignado < 0 or $hasta_asignado < 0){
							$mensajeError = "Desde o Hasta | Tiquete No pueden ser Menor a 0.";
							break;
						}
					// validar dese y hasta en asignado. a < 0.
					if($desde_asignado > $hasta_asignado){
						$mensajeError = "Desde '$desde_asignado' no puede ser mayor a Hasta '$hasta_asignado' | Tiquete.";
						break;
					}
					// validar desde en asignado. a < 0.
					if($desde_asignado > $existencia){
						$mensajeError = "Desde no puede ser mayor que la Existencia  | Tiquete.";
						//break;
					}
					// validar hasta en asignado. a < 0.
					if($hasta_asignado > $existencia){
						$mensajeError = "Desde no puede ser mayor que la Existencia  | Tiquete.";
						//break;
					}
					///////////////////////////////////////////////////////////////////////////////////////
					///////////////////////////////////////////////////////////////////////////////////////
					// Query - VALIDAR SI LA PRODUCCIÓN YA EXISTE.
						$query_ = "SELECT * FROM produccion
							WHERE codigo_jornada = '$codigo_jornada' and codigo_ruta = '$codigo_ruta'
								and fecha = '$fecha_produccion' and id_ = '$NumeroCorrelativo'";
							// Ejecutamos el query
								$consulta = $dblink -> query($query_);
							// Validar si hay registros.
								if($consulta -> rowCount() != 0){
									while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
									{
										$codigo_produccion = $listado['id_'];
									}
									//$mensajeError = "La Producción Ya fue Asignada.";
									//$contenidoOK = '';
									//break;
									$guardar_produccion = false;
								}else{
									$guardar_produccion = true;
								}
					
							// Query
							if($guardar_produccion == true){
								$query = "INSERT INTO produccion (fecha, codigo_jornada, codigo_ruta, codigo_inventario_tiquete, hora, codigo_tiquete_color)
								VALUES ('$fecha_produccion','$codigo_jornada','$codigo_ruta','$codigo_inventario_tiquete', '$hora_actual', '$codigo_tiquete_color')";
								// Ejecutamos el query
									$resultadoQuery = $dblink -> query($query);              
								// obtener el último dato en este caso el Id_
									$query = "SELECT lastval()";
									$consulta = $dblink -> query($query);              
									while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
									{
										$codigo_produccion = $listado['lastval'];
									}
							}else{
								// ACTULIZAR CIERTOS CAMPOS DE LA PRODUCCIÓN.
									$query_update_p = "UPDATE produccion SET codigo_inventario_tiquete = '$codigo_inventario_tiquete', codigo_tiquete_color = '$codigo_tiquete_color, 'codigo_ruta = '$codigo_ruta', codigo_jornada = '$codigo_jornada'
														WHERE id_ = '$codigo_produccion'";
								// Ejecutamos el query
									$resultadoQuery = $dblink -> query($query_update_p);              	
							}
					///////////////////////////////////////////////////////////////////////////////////////
					// TABLA PRODUCCION ASIGNADO.
					// CONSULTAR ANTES SI EXISTE EL RANGO DESDE Y HASTA.
						$query_ = "SELECT * FROM produccion_asignado
							WHERE codigo_produccion = '$codigo_produccion' and tiquete_desde >= '$desde_asignado' and tiquete_hasta <= '$hasta_asignado'
							and codigo_inventario_tiquete = '$codigo_inventario_tiquete'
								";
					// Ejecutamos el query
						$consulta = $dblink -> query($query_);
					// Validar si hay registros.
						if($consulta -> rowCount() != 0){
							$mensajeError = "La Producción | Correlativo Ya fueron Asignados.";
							$contenidoOK = '';
							ListadoAsignado();
								break;
						}else{
							// query.
								$query_pa = "INSERT INTO produccion_asignado (fecha, tiquete_desde, tiquete_hasta, cantidad, total, codigo_produccion, codigo_inventario_tiquete)
									VALUES ('$fecha_produccion_asignado','$desde_asignado','$hasta_asignado','$cantidad_asignado','$total','$codigo_produccion','$codigo_inventario_tiquete')";
							// Ejecutamos el query
									$resultadoQuery = $dblink -> query($query_pa);              
									// obtener el último dato en este caso el Id_
									$query = "SELECT lastval()";
									$consulta = $dblink -> query($query);              
									while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
									{
										$codigo_produccion_asignado = $listado['lastval'];
									}
						}
					///////////////////////////////////////////////////////////////////////////////////////
					// TABLA PRODUCCION CORRELACTIVO.
					// VERIFICAR SI YA EXISTE.
						$query_v_c = "SELECT * FROM produccion_correlativo WHERE correlativo >= '$desde_asignado' and correlativo <= '$hasta_asignado'
							and codigo_inventario_tiquete = '$codigo_inventario_tiquete'";
							$consulta_ = $dblink -> query($query_v_c);                  
						//
						if($consulta_ -> rowCount() != 0){
							$query_update = "UPDATE produccion_correlativo SET fecha = '$fecha_produccion', codigo_produccion_asignacion = '$codigo_produccion_asignado'
							WHERE correlativo >= '$desde_asignado' and correlativo <= '$hasta_asignado'	and codigo_inventario_tiquete = '$codigo_inventario_tiquete'";
								$resultadoQuery_up = $dblink -> query($query_update);    
						}else{
							// CREANDO EL CORRELATIVO.
							for ($i=$desde_asignado; $i<=$hasta_asignado; $i++) { 
								//
							$query_pc = "INSERT INTO produccion_correlativo (fecha, codigo_inventario_tiquete, codigo_produccion_asignacion, correlativo)
											VALUES ('$fecha_produccion','$codigo_inventario_tiquete','$codigo_produccion_asignado','$i')";
								// Ejecutamos el query
								$resultadoQuery = $dblink -> query($query_pc);              
							}
						}
                     ///////////////////////////////////////////////////////////////////////////////////////
					if($resultadoQuery == true){
						$respuestaOK = true;
						$mensajeError = "Se ha agregado el registro correctamente";
						$contenidoOK = '';
						$IdProduccionOK = $codigo_produccion;
						// listar registros
						ListadoAsignado();
					}
					else{
						$mensajeError = "No se puede guardar el registro en la base de datos ";
					}
			break;
			case ($AccionBuscar == 'GuardarControlIngreso' || $AccionBuscar == "ActualizarControlIngreso"):
				# VARIABLES.
					$CountAgregados = 0;
					$fecha_produccion = trim($_POST['FechaProduccion']);
					$codigo_produccion = trim($_POST['NumeroCorrelativo']);
					$codigo_ruta = trim($_POST['codigo_ruta']);
					$codigo_jornada = trim($_POST['codigo_jornada']);
					$codigo_inventario_tiquete = trim($_POST['codigo_serie']);
					$codigo_tiquete_color = trim($_POST['codigo_tiquete_color']);
					$codigo_personal = trim($_POST['codigo_personal']);
					//
					// Evaluar si es GUARDAR o ACTUALIZAR.
						if($AccionBuscar == "GuardarControlIngreso"){
							///////////////////////////////////////////////////////////////////////////////////////
							// TABLA PRODUCCION ASIGNADO. BUSCAR SI YA FUE PROCESADO (ENTREGADO).
								buscarProduccionEntregado();
							///////////////////////////////////////////////////////////////////////////////////////
							// TABLA PRODUCCION ASIGNADO. BUSCAR SI YA FUE PROCESADO (VENDIDO).
								buscarProduccionAsignadoVendido();
							// validar $vendido
								if($Entregado == true || $vendido == true){
									
								}else{
									# GUARAR DATOS EN LA TABLA PRODUCCIÓN.
										$query = "INSERT INTO produccion (fecha, codigo_jornada, codigo_ruta, codigo_inventario_tiquete, hora, codigo_tiquete_color)
											VALUES ('$fecha_produccion','$codigo_jornada','$codigo_ruta','$codigo_inventario_tiquete', '$hora_actual', '$codigo_tiquete_color')";
									// Ejecutamos el query
										$resultadoQuery = $dblink -> query($query);              
									// obtener el último dato en este caso el Id_
										$query = "SELECT lastval()";
										$consulta = $dblink -> query($query);              
										while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
										{
											$codigo_produccion_last = $listado['lastval'];
										}
										## evaluar si son iguales.
											if($codigo_produccion != $codigo_produccion_last){
												$query_actualizar_codigo_temp = "UPDATE produccion_asignado_temp SET codigo_produccion = '$codigo_produccion_last'
														WHERE codigo_produccion = '$codigo_produccion'";
															$consulta_actualizar_codigo_temp = $dblink -> query($query_actualizar_codigo_temp);              
												## Cambiar el valor de <codigo_produccion class="
													$codigo_produccion = $codigo_produccion_last;
											}// if si son iguales
								} // IF SI SON ENTREGADOS O VENDIDOS.
						}
					// Evaluar Actualizar control ingreso.
						if($AccionBuscar == "ActualizarControlIngreso"){
							// ACTULIZAR CIERTOS CAMPOS DE LA PRODUCCIÓN.
								$query_update_p = "UPDATE produccion SET codigo_inventario_tiquete = '$codigo_inventario_tiquete', codigo_tiquete_color = '$codigo_tiquete_color', codigo_ruta = '$codigo_ruta', codigo_jornada = '$codigo_jornada'
									WHERE id_ = '$codigo_produccion'";
							// Ejecutamos el query
								$resultadoQuery = $dblink -> query($query_update_p);              	             
						}
				# RECORRER LA TABLA PRODUCCION ASIGNADO TEMP.
					$query_temp = "SELECT tiquete_desde, tiquete_hasta, total, cantidad, codigo_inventario_tiquete, codigo_personal, codigo_produccion_asignado
						FROM produccion_asignado_temp WHERE codigo_produccion = '$codigo_produccion' and codigo_personal = '$codigo_personal' ORDER BY id_";
				// Ejecutamos el query
					$resultadoQuery_temp = $dblink -> query($query_temp);
					while($listado_temp = $resultadoQuery_temp -> fetch(PDO::FETCH_BOTH))
						{
							$desde_asignado = $listado_temp['tiquete_desde'];
							$hasta_asignado = $listado_temp['tiquete_hasta'];
							$total = $listado_temp['total'];
							$cantidad = $listado_temp['cantidad'];
							$codigo_inventario_tiquete = $listado_temp['codigo_inventario_tiquete'];
							$codigo_personal = $listado_temp['codigo_personal'];
							$codigo_produccion_asignado = $listado_temp['codigo_produccion_asignado'];
							// guardar solo cuando sea GUARDARCONTROLINGRESO.
								if($AccionBuscar == "GuardarControlIngreso"){
									///////////////////////////////////////////////////////////////////////////////////////
									// TABLA PRODUCCION ASIGNADO. BUSCAR SI YA FUE PROCESADO (ENTREGADO).
										buscarProduccionEntregado();
									///////////////////////////////////////////////////////////////////////////////////////
									// TABLA PRODUCCION ASIGNADO. BUSCAR SI YA FUE PROCESADO (VENDIDO).
										buscarProduccionAsignadoVendido();
									// validar $vendido
										if($Entregado == true || $vendido == true){
											
										}else{
											// Armar query QueryGuardarProduccionAsignado
												$queryGuardarProduccionAsignado = "INSERT INTO produccion_asignado (fecha, tiquete_desde, tiquete_hasta, cantidad, total, codigo_produccion, codigo_inventario_tiquete)
													VALUES ('$fecha_produccion','$desde_asignado','$hasta_asignado','$cantidad','$total','$codigo_produccion','$codigo_inventario_tiquete')";
											// Ejecutamos el query
												$queryAgregado = $dblink -> query($queryGuardarProduccionAsignado);    			
											// $agregados.
												$CountAgregados = $CountAgregados + $queryAgregado -> rowCount();
										}
								}
							// Evaluar Actualizar control ingreso.
								if($AccionBuscar == "ActualizarControlIngreso"){
									// Armar query.
										$queryBuscarProduccionAsignado = "SELECT * FROM produccion_asignado	WHERE  id_ = '$codigo_produccion_asignado' and codigo_inventario_tiquete = '$codigo_inventario_tiquete'";
									// Ejecutamos el query
										$consultaBuscarProduccionAsignado = $dblink -> query($queryBuscarProduccionAsignado);	
										$ValorBusqueda = $consultaBuscarProduccionAsignado -> rowCount();
									// Validar si hay registros.
										if($ValorBusqueda == 0){        
											///////////////////////////////////////////////////////////////////////////////////////
											// TABLA PRODUCCION ASIGNADO. BUSCAR SI YA FUE PROCESADO (ENTREGADO).
													buscarProduccionEntregado();
											///////////////////////////////////////////////////////////////////////////////////////
											// TABLA PRODUCCION ASIGNADO. BUSCAR SI YA FUE PROCESADO (VENDIDO).
												buscarProduccionAsignadoVendido();
											// validar $vendido
												if($Entregado == true || $vendido == true){
													
												}else{
													// Armar query QueryGuardarProduccionAsignado
															$queryGuardarProduccionAsignado = "INSERT INTO produccion_asignado (fecha, tiquete_desde, tiquete_hasta, cantidad, total, codigo_produccion, codigo_inventario_tiquete)
															VALUES ('$fecha_produccion','$desde_asignado','$hasta_asignado','$cantidad','$total','$codigo_produccion','$codigo_inventario_tiquete')";
													// Ejecutamos el query
														$queryAgregado = $dblink -> query($queryGuardarProduccionAsignado);    			
													// $agregados.
													$CountAgregados = $CountAgregados + $queryAgregado -> rowCount();
												}
										}
								}	// if acceionBsucar
							}	// while asignacion temp.
							// Validamos que se haya actualizado el registro
						if($CountAgregados != 0){
							$respuestaOK = true;
							$mensajeError = "Se ha Agregado ".$CountAgregados." Talonario(s).";
						}else{
							$mensajeError = 'El Control no ha sido Modificado.';
						}
				break;
			case 'BuscarControlIngreso':
				$codigo_produccion = trim($_POST['NumeroCorrelativo']);
				$codigo_personal = trim($_POST['codigo_personal']);
				# ELIMINAR DATOS QUE SE ENCUENTRE EN TEMP.
					//$query_pa_temp = "DELETE FROM produccion_asignado_temp WHERE codigo_produccion = '$codigo_produccion' and codigo_personal = '$codigo_personal";
					$query_pa_temp = "DELETE FROM produccion_asignado_temp WHERE codigo_personal = '$codigo_personal'";
				// Ejecutamos el query
					$count_pa_temp = $dblink -> exec($query_pa_temp);
				// Buscar Número Control en produccion_asignado.
					$query_buscar_a = "SELECT * FROM produccion_asignado WHERE codigo_produccion = '$codigo_produccion' ORDER BY id_";
				// Ejecutamos el query
					$resultadoQuery_buscar_a = $dblink -> query($query_buscar_a);              	
				//
					if($resultadoQuery_buscar_a -> rowCount() != 0){
						while($listado_b_a = $resultadoQuery_buscar_a -> fetch(PDO::FETCH_BOTH))
						{
							$id_produccion_asignado = trim($listado_b_a['id_']);		
							$fecha = trim($listado_b_a['fecha']);		
							$tiquete_desde = codigos_nuevos(trim($listado_b_a['tiquete_desde']));		
							$tiquete_hasta = codigos_nuevos(trim($listado_b_a['tiquete_hasta']));		
							$total = trim($listado_b_a['total']);
							$cantidad = $listado_b_a['cantidad'];
							$procesado = $listado_b_a['procesado'];
							$codigo_estatus = $listado_b_a['codigo_estatus'];
							$codigo_inventario_tiquete = $listado_b_a['codigo_inventario_tiquete'];
							//	guardar en produccion asigando temp.
								$query_pa_t = "INSERT INTO produccion_asignado_temp (fecha, tiquete_desde, tiquete_hasta, cantidad, total, codigo_produccion, codigo_inventario_tiquete, codigo_produccion_asignado, codigo_personal)
									VALUES ('$fecha','$tiquete_desde','$tiquete_hasta','$cantidad','$total','$codigo_produccion','$codigo_inventario_tiquete','$id_produccion_asignado','$codigo_personal')";
							// Ejecutamos el query
								$resultadoQuery = $dblink -> query($query_pa_t);              
						}	
					}
					# respuesta y Mostrar listado Temp.
					$respuestaOK = true;
					$mensajeError = "Edición...";	
					ListadoAsignadoTemp();
			break;	
			case 'EditarRegistro':
				// TABS-1
					$codigo_produccion = $_REQUEST['codigo_produccion'];
					$mensajeError = 'Editar Producción.';	
					$respuestaOK = true;
					ListadoAsignado();
			break;
			case 'EliminarAsignacion':
				// id_ de la asignacion.
				$id_ = trim($_POST['id_']);
				$id_ = explode("-",$id_);
				$id_produccion_asignacion = $id_[0];	// array 0
				$codigo_produccion = $id_[1];	// array 1
				$codigo_produccion_asignado = $id_[2]; // array 2, el cual permite borrar del la produccion asfignado.
				$count_pc = 0;
				// Armamos el query
				$query_pa = "DELETE FROM produccion_asignado_temp WHERE id_ = $id_produccion_asignacion";
				// Ejecutamos el query
				$count_pa = $dblink -> exec($query_pa);
				// condiconar que se ejecute cuando codigo_produccion_asignado sea diferente de 0.
				if($codigo_produccion_asignado != 0)
				{
					// Armamos el query para eliminar de produccion asignado.
					$query_pc = "DELETE FROM produccion_asignado WHERE id_ = $codigo_produccion_asignado";
					// Ejecutamos el query
					$count_pc = $dblink -> exec($query_pc);
				}
				// Validamos que se haya actualizado el registro
				if($count_pa != 0 or $count_pc != 0){
					//$count_eliminados = $count_pc + $count_pa;
					$count_eliminados = $count_pa + $count_pc;
					$respuestaOK = true;
					$mensajeError = 'Se ha Eliminado '.$count_eliminados.' Registro(s).';
				}else{
					$mensajeError = 'No se ha eliminado el registro';
				}
				// Ver nuevamente el listado
					ListadoAsignadoTemp();
			break;	
			case 'ActualizarTalonario':
				// id_ de la asignacion.
				$IdEditarTiquete = trim($_POST['IdEditarTiqueteDesde']);
				$tiquete_desde = intval(str_replace(",","",$_POST['TiqueteDesde']));
				$codigo_produccion = trim($_POST['codigo_produccion']);
				$costo = trim($_POST['CantidadTiqueteAsignado']);
				$cantidad_asignado = preg_replace("/[$,]/","",trim($_POST['CantidadTiqueteAsignado']));
				$total = preg_replace("/[$,]/","",trim($_POST['TotalAsignado']));
				$total = trim($_POST['TotalAsignado']);
				$id_produccion_asignacion = trim($_POST['IdProduccionAsignado']);
				// Verificar si talonario Inicial es diferente alnuevo. o sea < o mayor que Hasta.
					$query_consultar = "SELECT * from produccion_asignado WHERE id_ = '$id_produccion_asignacion'";
					// Ejecutamos el Query.
					$consulta = $dblink -> query($query_consultar);
					while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
					{
                        // Nombres de los campos de la tabla.
                            $tiquete_desde_asignado = trim($listado['tiquete_desde']);
							$tiquete_hasta = trim($listado['tiquete_hasta']);
					}
				// condicion tiquete desde < tiquete desde asignado
					if($tiquete_desde < $tiquete_desde_asignado){
						$mensajeError = 'El Tiquete no puede ser Menor.';
					}else if($tiquete_desde > $tiquete_hasta){
						$mensajeError = 'El Tiquete no puede ser Mayor.';
					}else{
						// query actualizar
						$query = "UPDATE produccion_asignado_temp 
						SET tiquete_desde = '$tiquete_desde',
							cantidad = '$cantidad_asignado',
							total = '$total' 
						WHERE id_ = '$IdEditarTiquete'";
						// Ejecutamos el query
							$resultadoQuery = $dblink -> query($query);              
								$respuestaOK = true;
								$mensajeError = 'Talonario Actualizado.';
						// Ver nuevamente el listado
					}
					// Ver nuevamente el listado.
					ListadoAsignadoTemp();
			break;
			case 'EliminarUltimoRegistroProduccion':
				//  variables para el count.
					$count_pa = 0; $count_pc = 0;
				// ELIMINAR CODIGO PRODUCCION.
				$codigo_produccion = $_POST['codigo_produccion'];
				//
				$query_s = "SELECT * FROM produccion_asignado_temp WHERE codigo_produccion = '$codigo_produccion' ORDER BY id_ DESC LIMIT 1";
					// Ejecutamos el query
					$consulta = $dblink -> query($query_s);
				//
					if($consulta -> rowCount() != 0){
					// obtener el último dato en este caso el Id_
						while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
						{
							$codigo_id = trim($listado['id_']);
							$id_ = trim($listado['id_']);
							$codigo_produccion_asignado = trim($listado['codigo_produccion_asignado']);
						// ELIMINAR CODIGO PRODUCCION ASIGNADO.
						}
						// armar queri produccion asignado temp.
						$query_pa = "DELETE FROM produccion_asignado_temp WHERE codigo_produccion = '$codigo_produccion' and id_ = '$codigo_id'";
							$count_pa = $dblink -> exec($query_pa);
						//;
						// condiconar que se ejecute cuando codigo_produccion_asignado sea diferente de 0.
						if($codigo_produccion_asignado != 0)
						{
							// Armamos el query para eliminar de produccion asignado.
							$query_pc = "DELETE FROM produccion_asignado WHERE id_ = $codigo_produccion_asignado";
							// Ejecutamos el query
							$count_pc = $dblink -> exec($query_pc);
						}
					}
					// Validamos que se haya actualizado el registro
						if($count_pa != 0 or $count_pc != 0){
							//$count_eliminados = $count_pc + $count_pa;
							$count_eliminados = $count_pa + $count_pc;
							$respuestaOK = true;
							$mensajeError = 'Se ha Eliminado '.$count_eliminados.' Registro(s).';
						}else{
							$mensajeError = 'No se ha eliminado el registro';
						}
					// Ver nuevament el listado.
					ListadoAsignadoTemp();
					break; 	
				case "RegistrosTemp":
					//  variables para el count.
					$count_temp = 0;
					// ELIMINAR CODIGO PRODUCCION.
					$codigo_produccion = $_POST['codigo_produccion'];
					//
					$query_s = "SELECT * FROM produccion_asignado_temp WHERE codigo_produccion = '$codigo_produccion' ORDER BY id_ DESC";
						// Ejecutamos el query
						$consulta = $dblink -> query($query_s);
					//
						if($consulta -> rowCount() != 0){
							$respuestaOK = true;
							$mensajeError = 'Control se puede Guardar '. strval($codigo_produccion);
						}else{
							$respuestaOK = false;
							$mensajeError = 'El control no se puede guardar '. strval($codigo_produccion);
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
	if($AccionBuscar === "" or $AccionBuscar === "BuscarTodosCodigo"){
		echo json_encode($arreglo);	
	}elseif($AccionBuscar === "BuscarTipoTransporte" or $AccionBuscar === "1UltimoRegistro" or $AccionBuscar === "BuscarPorId"){
		echo json_encode($datos);
		}
	else{
		// Armamos array para convertir a JSON
		$salidaJson = array("respuesta" => $respuestaOK,
			"mensaje" => $mensajeError,
			"contenido" => $contenidoOK,
			"contenidoNoGuardado" => $contenidoOKNoGuardados,
			"id_produccion" => $IdProduccionOK,
			"totalIngreso" => $totalIngresoOKPantalla,
			"cantidad_tiquete" => $cantidadTiqueteOK,
			"CantidadTalonarios" => $fila,
			"NoGuardado" => $NoGuardado,
			"QueryLista" => $QueryLista);
		echo json_encode($salidaJson);
	}
function ListadoAsignadoTemp(){
	global $totalIngresoOK, $dblink, $contenidoOK, $codigo_produccion, $cantidadTiqueteOK, $totalIngresoOKPantalla, $fila;
	$estilo_l = 'style="padding: 0px; font-size: large; color:blue; text-align: left;"';
	$estilo_c = 'style="padding: 0px; font-size: large; color:blue; text-align: center;"';
	$estilo_r = 'style="padding: 0px; font-size: large; color:blue; text-align: right;"';
	$fila = 0;
	// consulta.
		$query_c = "SELECT pa.fecha, pa.codigo_inventario_tiquete, pa.codigo_produccion, pa.codigo_produccion_asignado,
			cat_ts.descripcion as nombre_serie, 
			pa.id_ as id_produccion_asignado, pa.tiquete_desde, pa.tiquete_hasta, pa.total, pa.cantidad
			FROM produccion_asignado_temp pa
			INNER JOIN inventario_tiquete it ON it.id_ = pa.codigo_inventario_tiquete
			INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie
			WHERE pa.codigo_produccion = '$codigo_produccion'
				ORDER BY pa.id_, pa.codigo_inventario_tiquete";
	// Ejecutamos el query
		$consulta = $dblink -> query($query_c);              
	// obtener el último dato en este caso el Id_
		while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
		{
			$fila++;
			$id_pro_a = trim($listado['id_produccion_asignado']);		
			$pa_codigo_produccion = trim($listado['codigo_produccion']);
			$nombre_serie = trim($listado['nombre_serie']);		
			$tiquete_desde = codigos_nuevos(trim($listado['tiquete_desde']));		
			$tiquete_hasta = codigos_nuevos(trim($listado['tiquete_hasta']));		
			$total = trim($listado['total']);
			$fecha = trim($listado['fecha']);
			$cantidad = $listado['cantidad'];
			$codigo_produccion_asignado = trim($listado['codigo_produccion_asignado']);
			// Calcular totoal ingresal
			$totalIngresoOK = $totalIngresoOK + $total;
			$totalIngresoOKPantalla = number_format($totalIngresoOK,2);
			// Calcular Cantidad Tiqeute.
			$cantidadTiqueteOK = $cantidadTiqueteOK + $cantidad;
			//
			$todos = $id_pro_a . "#" . $pa_codigo_produccion . "#" . $tiquete_desde . "#" . $tiquete_hasta . "#" . $fecha . "#" . $cantidad . "#" . $total . "#" . $codigo_produccion_asignado;                // Variables que pasa  a la tabla.s
			//
			$contenidoOK .= "<tr> 
			<td $estilo_l><a data-accion=EditarAsignacion data-toggle=tooltip data-placement=left title='Modificar Talonario' href='$todos'><i class='far fa-money-check-edit-alt'></i></a>
			<td $estilo_c><a data-accion=EliminarAsignacion data-toggle=tooltip data-placement=left title=Eliminar href=$id_pro_a-$pa_codigo_produccion-$codigo_produccion_asignado><i class='fad fa-trash-alt fa-lg'></i></a>
			<td $estilo_c>$nombre_serie
			<td $estilo_r>$tiquete_desde
			<td $estilo_r>$tiquete_hasta
			<td $estilo_r>$ $total"
			;
		}		
}
function ListadoAsignado(){
	global $totalIngresoOK, $dblink, $contenidoOK, $codigo_produccion, $cantidadTiqueteOK, $totalIngresoOKPantalla;
	$estilo_l = 'style="padding: 0px; font-size: large; color:blue; text-align: left;"';
	$estilo_c = 'style="padding: 0px; font-size: large; color:blue; text-align: center;"';
	$estilo_r = 'style="padding: 0px; font-size: large; color:blue; text-align: right;"';
	// consulta.
		$query_c = "SELECT p.id_ AS id_produccion, p.fecha, pa.codigo_inventario_tiquete, p.codigo_personal,
			cat_ts.descripcion as nombre_serie,
			pa.id_ as id_produccion_asignado, pa.tiquete_desde, pa.tiquete_hasta, pa.total, pa.cantidad
			FROM produccion p
			INNER JOIN produccion_asignado pa ON pa.codigo_produccion = p.id_
			INNER JOIN inventario_tiquete it ON it.id_ = pa.codigo_inventario_tiquete
			INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie
			WHERE pa.codigo_produccion = '$codigo_produccion'
				ORDER BY pa.id_, p.codigo_inventario_tiquete";
	// Ejecutamos el query
		$consulta = $dblink -> query($query_c);              
	// obtener el último dato en este caso el Id_
		while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
		{
			$id_pro_a = trim($listado['id_produccion_asignado']);		
			$pa_codigo_produccion = trim($listado['id_produccion']);
			$nombre_serie = trim($listado['nombre_serie']);		
			$tiquete_desde = codigos_nuevos(trim($listado['tiquete_desde']));		
			$tiquete_hasta = codigos_nuevos(trim($listado['tiquete_hasta']));		
			$total = trim($listado['total']);
			$cantidad = $listado['cantidad'];
			// Calcular totoal ingresal
			$totalIngresoOK = $totalIngresoOK + $total;
			$totalIngresoOKPantalla = number_format($totalIngresoOK,2);
			// Calcular Cantidad Tiqeute.
			$cantidadTiqueteOK = $cantidadTiqueteOK + $cantidad;
			//
			$contenidoOK .= "<tr>
			<td $estilo_c><a data-accion=EliminarAsignacion data-toggle=tooltip data-placement=left title=Eliminar href=$id_pro_a-$pa_codigo_produccion><i class='fad fa-trash-alt fa-lg'></i></a>
			<td $estilo_c>$nombre_serie
			<td $estilo_r>$tiquete_desde
			<td $estilo_r>$tiquete_hasta
			<td $estilo_r>$ $total"
			;
		}		
}
function buscarProduccionEntregado(){
	// Variables globales del sistema.
		global $desde_asignado, $hasta_asignado, $codigo_inventario_tiquete, $dblink, $mensajeError, $contenidoOK, $Entregado;
	// validar desde_asignado cuando no sea 01 al terminar.
		$colaEntregado = 0; $desde_asignadoNuevo = 0;
		$valor01 = ($hasta_asignado - $desde_asignado);
			if($valor01 != 99){
				$quitoDesde = 99 - $valor01;
				$desde_asignadoNuevo = $desde_asignado - $quitoDesde;
			}
	// armar query
			if($valor01 != 99){
				$query_buscarAsignado = "SELECT * from produccion_asignado 
				WHERE tiquete_desde >= '$desde_asignadoNuevo' and tiquete_hasta <=  '$hasta_asignado' and 
					codigo_inventario_tiquete = '$codigo_inventario_tiquete' and codigo_estatus = '03' and procesado = 'false'
						ORDER BY fecha DESC LIMIT 1";
			}else{
				$query_buscarAsignado = "SELECT * from produccion_asignado 
					WHERE tiquete_desde >= '$desde_asignado' and tiquete_hasta <=  '$hasta_asignado' and 
						codigo_inventario_tiquete = '$codigo_inventario_tiquete' and codigo_estatus = '03' and procesado = 'false'
							ORDER BY fecha DESC LIMIT 1";
			}
	// Ejecutamos el query
		$consultaBuscarAsignado = $dblink -> query($query_buscarAsignado);	
	// buscar si tiene cola el talonario vendido.
		while($listado = $consultaBuscarAsignado -> fetch(PDO::FETCH_BOTH))
		{
			$colaEntregado = $listado['tiquete_cola'];
	
		}	
		// validar la cola sea diferente de 0.
		if($colaEntregado != 0){
			if($colaEntregado + 1 === intval($desde_asignado)){
				$Entregado = true;
				$mensajeError = "La Producción | " . $colaEntregado . " < que DESDE";
				$contenidoOK = '';
			}
		}else{
			// Validar si hay registros.
			if($consultaBuscarAsignado -> rowCount() != 0){
				$mensajeError = "La Producción | Talonario Ya está en Producción.";
				$contenidoOK = '';
				$Entregado = true;
			}
		}
				return $Entregado;
	}
function buscarProduccionAsignadoVendido(){
// Variables globales del sistema.
	global $desde_asignado, $hasta_asignado, $codigo_inventario_tiquete, $dblink, $mensajeError, $contenidoOK, $vendido;
// validar desde_asignado cuando no sea 01 al terminar.
	$colaVendido = 0; $desde_asignadoNuevo = 0;
	$valor01 = ($hasta_asignado - $desde_asignado);
		if($valor01 != 99){
			$quitoDesde = 99 - $valor01;
			$desde_asignadoNuevo = $desde_asignado - $quitoDesde;
		}
// armar query
		if($valor01 != 99){
			$query_buscarAsignado = "SELECT * from produccion_asignado 
			WHERE tiquete_desde >= '$desde_asignadoNuevo' and tiquete_hasta <=  '$hasta_asignado' and 
				codigo_inventario_tiquete = '$codigo_inventario_tiquete' and codigo_estatus = '05' and procesado = 'true'
					ORDER BY fecha DESC LIMIT 1";
		}else{
			$query_buscarAsignado = "SELECT * from produccion_asignado 
				WHERE tiquete_desde >= '$desde_asignado' and tiquete_hasta <=  '$hasta_asignado' and 
					codigo_inventario_tiquete = '$codigo_inventario_tiquete' and codigo_estatus = '05' and procesado = 'true'
						ORDER BY fecha DESC LIMIT 1";
		}
// Ejecutamos el query
	$consultaBuscarAsignado = $dblink -> query($query_buscarAsignado);	
// buscar si tiene cola el talonario vendido.
	while($listado = $consultaBuscarAsignado -> fetch(PDO::FETCH_BOTH))
	{
		$colaVendido = $listado['tiquete_cola'];

	}	
	// validar la cola sea diferente de 0.
	if($colaVendido != 0){
		if($colaVendido + 1 === intval($desde_asignado)){
			$vendido = true;
			$mensajeError = "La Producción | Cola " . $colaVendido . " < que DESDE";
			$contenidoOK = '';
		}
	}else{
		// Validar si hay registros.
		if($consultaBuscarAsignado -> rowCount() != 0){
			$mensajeError = "La Producción | Talonario Ya fue Vendido.";
			$contenidoOK = '';
			$vendido = true;
		}
	}
			return $vendido;
}
function buscarProduccionAsignadoTemp(){
// Variables globales del sistema.
	global $desde_asignado, $hasta_asignado, $codigo_inventario_tiquete, $dblink, $mensajeError, $contenidoOK, $CreadoTemp, $codigo_produccion;
// armar query
	$queryBuscarTemp = "SELECT * FROM produccion_asignado_temp
		WHERE codigo_produccion = '$codigo_produccion' and tiquete_desde >= '$desde_asignado' and tiquete_hasta <= '$hasta_asignado'
			and codigo_inventario_tiquete = '$codigo_inventario_tiquete'";
// Ejecutamos el query
	$consultaBuscarTemp = $dblink -> query($queryBuscarTemp);								
// Validar si hay registros.
	if($consultaBuscarTemp -> rowCount() != 0){
		$mensajeError = "La Producción | Talonario Ya fue Creado (Temp).";
		$contenidoOK = '';
		$CreadoTemp = true;
	}
	return $CreadoTemp;
}