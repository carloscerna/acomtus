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
				// Armamos el query.
				$query = "SELECT p.id_personal, p.codigo, TRIM(p.nombres) as nombre, TRIM(p.apellidos) as apellido, btrim(p.nombres || CAST(' ' AS VARCHAR) || p.apellidos) AS nombre_empleado
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

						// Rellenando la array.
							$datos[$fila_array]["id_"] = $id_;
							$datos[$fila_array]["codigo"] = $codigo;
							$datos[$fila_array]["nombre_empleado"] = $nombre_personal;
					}
					$datos[$fila_array]["mensajeError"] = 'El código si Existe...';
					$datos[$fila_array]["respuestaOK"] = true;
				}
				else{
					$contenidoOK = '';
					$datos[$fila_array]["respuestaOK"] = false;
					$datos[$fila_array]["mensajeError"] = 'El código no Existe...';
				}
			break;
			case 'BuscarTipoLicencia':
                # Buscar en tabla catalogo_jornada.
                // armando el Query.
                    $query = "SELECT id_, descripcion from catalogo_tipo_licencia_o_permiso WHERE id_ > '1' ORDER BY id_";
                    // Ejecutamos el Query.
                    $consulta = $dblink -> query($query);
                    // Inicializando el array
                    $datos=array(); $fila_array = 0;
                    // Recorriendo la Tabla con PDO::
                        while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                        {
                            // Nombres de los campos de la tabla.
                        $codigo = trim($listado['id_']); $descripcion = trim($listado['descripcion']);
                        //. ' ' . trim($listado['hora_hasta']);
                        // Rellenando la array.
                           $datos[$fila_array]["codigo"] = $codigo;
                            $datos[$fila_array]["descripcion"] = $descripcion;
                                $fila_array++;
                            }
			break;			
			case 'GuardarAsistencia':
				$codigo_personal = trim($_POST['CodigoPersonal']);
				$fecha = trim($_POST['FechaAsistencia']);
				$tipolicenciacheck = trim($_POST['tipochecks']);
				// VALIDAR VALORES PARA TIPO LICENCIA JORNADA.
				if($tipolicenciacheck == "on"){
					$codigo_tipo_licencia = trim($_POST['lstTipoLicencia']);
					$codigo_jornada = '4';
				}else{
					$codigo_jornada = trim($_POST['lstJornada']);
					$codigo_tipo_licencia = "1";
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
					$query = "INSERT INTO personal_asistencia (codigo_personal, fecha, hora, codigo_jornada, codigo_tipo_licencia) VALUES('$codigo_personal','$fecha','$hora_actual','$codigo_jornada','$codigo_tipo_licencia')";
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