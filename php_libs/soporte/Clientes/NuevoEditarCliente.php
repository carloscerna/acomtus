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
	include($path_root."/acomtus/includes/funciones.php");
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
			case 'GenerarCodigoNuevo':
				$ann_ = substr(trim($_POST['ann']),2,2);	// Año en Curso. pasar a dos digitos.

				$query = "SELECT id_, codigo, substring(codigo from 1 for 3)::int as codigo_empleado_numero_entero,substring(codigo from 1 for 3) as codigo_empleado,
							substring(codigo from 4 for 2) as codigo_empleado_ann, nombres, primer_apellido 
                            FROM clientes WHERE substring(codigo from 4 for 2) = '$ann_'
							ORDER BY codigo_empleado_numero_entero DESC LIMIT 1";
				// Ejecutamos el Query.
				$consulta = $dblink -> query($query);
				// Verificar si existen registros.
				if($consulta -> rowCount() != 0){
					$respuestaOK = true;
					// convertimos el objeto
					while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
					{
						$codigo_entero = trim($listado['codigo_empleado_numero_entero']) + 1;
						$codigo_string = (string) $codigo_entero;
						$codigo_nuevo = codigos_nuevos_personal($codigo_string);
						// Generar el Código
						$codigo_nuevo = $codigo_nuevo . $ann_;
						// retornar variable que contendrá el nuevo código.
						$datos[$fila_array]["codigo_nuevo"] = $codigo_nuevo;
						
					}
					$mensajeError = "Nuevo Código: " . $codigo_nuevo . "Generado.";
				}
				else{
						// Generar el Código
						$codigo_nuevo = "001" . $ann_;
						// retornar variable que contendrá el nuevo código.
						$datos[$fila_array]["codigo_nuevo"] = $codigo_nuevo;
						
					$respuestaOK = true;
					$contenidoOK = '';
					$mensajeError =  'No hay Generación de Código.';
				}
			break;
			case 'BuscarCodigo':
				$codigo = trim($_POST['codigo']);
				// Armamos el query.
				$query = "SELECT id_, codigo FROM clientes WHERE codigo = '$codigo'";
				// Ejecutamos el Query.
				$consulta = $dblink -> query($query);
				// Verificar la consulta
					if($consulta -> rowCount() != 0){
						$respuestaOK = true;
						$contenidoOK = "";
						$mensajeError =  'El Código Ya Existe.';
					}else{
						$respuestaOK = false;
						$contenidoOK = "";
						$mensajeError =  'El Código, no existe puede Continuar...';					
						}
			break;
			case 'BuscarPorId':
				$id_x = trim($_POST['id_x']);
				// Armamos el query.
				$query = "SELECT c.id_, c.codigo, TRIM(c.nombres) as nombre, TRIM(c.primer_apellido) as apellido, btrim(c.nombres || CAST(' ' AS VARCHAR) || c.primer_apellido || CAST(' ' AS VARCHAR) || c.segundo_apellido) AS nombre_empleado,
                    c.telefono_domicilio, c.telefono_celular,
						FROM clientes c
							WHERE codigo <> '' and id_ = '$id_x'
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
							$prestamo = trim($listado['saldo_prestamo']);
						// Nombres de los campos de la tabla.
							$codigo = trim($listado['codigo']);	
							$nombre_empleado = trim($listado['nombre_empleado']);
							$nombre = trim($listado['nombre']);
							$apellido = trim($listado['apellido']);
							
                            $fecha_nacimiento = trim($listado['fecha_nacimiento']);
							$edad = trim($listado['edad']);
							$codigo_genero = trim($listado['codigo_genero']);
							$codigo_estado_civil = trim($listado['codigo_estado_civil']);
							$tipo_sangre = trim($listado['tipo_sangre']);
							$codigo_estudio = trim($listado['codigo_estudio']);
							$codigo_vivienda = trim($listado['codigo_vivienda']);
							$codigo_afp = trim($listado['codigo_afp']);
							$nombre_conyuge = trim($listado['nombre_conyuge']);


							$codigo_municipio = trim($listado['codigo_municipio']);
                            $codigo_departamento = trim($listado['codigo_departamento']);
							$direccion = trim($listado['direccion']);
                            $telefono_fijo = trim($listado['telefono_residencia']);
                            $telefono_movil = trim($listado['telefono_celular']);
							$correo_electronico = trim($listado['correo_electronico']);

							//$ = trim($listado['']);
							//$ = trim($listado['']);
						//	Logo.
							$url_foto = trim($listado['foto']);
						// Rellenando la array.
							$datos[$fila_array]["codigo"] = $codigo;
							$datos[$fila_array]["nombre_empleado"] = $nombre_empleado;
							$datos[$fila_array]["nombre"] = $nombre;
							$datos[$fila_array]["apellido"] = $apellido;
							
							$datos[$fila_array]["fecha_nacimiento"] = $fecha_nacimiento;
							$datos[$fila_array]["edad"] = $edad;							
							$datos[$fila_array]["codigo_genero"] = $codigo_genero;
                            $datos[$fila_array]["codigo_estado_civil"] = $codigo_estado_civil;
							$datos[$fila_array]["tipo_sangre"] = $tipo_sangre;
							$datos[$fila_array]["codigo_estudio"] = $codigo_estudio;
							$datos[$fila_array]["codigo_vivienda"] = $codigo_vivienda;
							$datos[$fila_array]["codigo_afp"] = $codigo_afp;
							$datos[$fila_array]["nombre_conyuge"] = $nombre_conyuge;

							$datos[$fila_array]["codigo_municipio"] = $codigo_municipio;
							$datos[$fila_array]["codigo_departamento"] = $codigo_departamento;
							$datos[$fila_array]["direccion"] = $direccion;
                            $datos[$fila_array]["telefono_fijo"] = $telefono_fijo;
                            $datos[$fila_array]["telefono_movil"] = $telefono_movil;
							$datos[$fila_array]["correo_electronico"] = $correo_electronico;

							//$datos[$fila_array][""] = $;

					}
					$mensajeError = "Si Registro";
				}
				else{
					$respuestaOK = true;
					$contenidoOK = '';
					$mensajeError =  'No Registro';
				}
			break;

			case 'AgregarNuevoPersonal':		
				// armar variables.
					// INFO 1
					$codigo_personal = trim($_POST['txtcodigo']);
					$codigo_estatus = trim($_POST['lstestatus']);
					
					$nombre = trim($_POST['txtnombres']);
					$apellido = trim($_POST['txtapellido']);
					$fecha_nacimiento = trim($_POST['fechanacimiento']);
					$edad = trim($_POST['txtedad']);
					$codigo_genero = trim($_POST['lstgenero']);
					$codigo_estado_civil = trim($_POST['lstEstadoCivil']);
					$tipo_sangre = htmlspecialchars(trim($_POST['txtTipoSangre']),ENT_QUOTES,'UTF-8');
					$codigo_estudios = trim($_POST['lstEstudios']);
					$codigo_tipo_vivienda = trim($_POST['lstVivienda']);
					$codigo_afp = trim($_POST['lstAfp']);
					$nombre_conyuge = htmlspecialchars(trim($_POST['txtConyuge']),ENT_QUOTES,'UTF-8');

					$codigo_departamento = trim($_POST['lstdepartamento']);
					$codigo_municipio = trim($_POST['lstmunicipio']);
					$direccion = htmlspecialchars(trim($_POST['direccion']),ENT_QUOTES,'UTF-8');

					$telefono_fijo = trim($_POST['telefono_fijo']);
					$telefono_movil = trim($_POST['telefono_movil']);
					$correo_electronico = trim($_POST['correo_electronico']);	

				// Query
					$query = "INSERT INTO personal (nombres, apellidos, fecha_nacimiento, edad, codigo_genero,
					codigo_estado_civil, tipo_sangre, codigo_estudio, codigo_vivienda, codigo_afp, nombre_conyuge,
					codigo_municipio, codigo_departamento, direccion,
					telefono_residencia, telefono_celular, correo_electronico,
					codigo_cargo, fecha_ingreso, fecha_retiro, codigo_departamento_empresa, codigo_clasificacion_empresa,
					codigo_ruta, codigo_socio, numero_cuenta, pago_diario, salario,
					codigo_tipo_licencia, licencia, dui, nit, isss, afp,
					comentario, codigo, codigo_estatus)
						VALUES ('$nombre', '$apellido', '$fecha_nacimiento', '$edad', '$codigo_genero', '$codigo_estado_civil', '$tipo_sangre', '$codigo_estudios', '$codigo_tipo_vivienda', '$codigo_afp', '$nombre_conyuge',
							'$codigo_municipio', '$codigo_departamento', '$direccion',
							'$telefono_fijo', '$telefono_movil', '$correo_electronico',
							'$codigo_cargo', '$fecha_ingreso', '$fecha_retiro', '$codigo_departamento_empresa', '$codigo_clasificacion_empresa',
							'$codigo_ruta', '$codigo_socio', '$numero_cuenta', '$pago_diario','$salario',
							'$codigo_licencia', '$numero_licencia', '$dui', '$nit', '$isss', '$afp',
							'$comentario','$codigo_personal','$codigo_estatus')";
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
			break;
			
			case 'EditarRegistro':
				// INFO 1
					$codigo_personal_2 = trim($_POST['txtId']);
					$codigo_personal = trim($_POST['id_user']);
					$codigo_estatus = trim($_POST['lstestatus']);
					$nombre = trim($_POST['txtnombres']);
					$apellido = trim($_POST['txtapellido']);
					$fecha_nacimiento = trim($_POST['fechanacimiento']);
					$edad = trim($_POST['txtedad']);
					$codigo_genero = trim($_POST['lstgenero']);
					$codigo_estado_civil = trim($_POST['lstEstadoCivil']);
					$tipo_sangre = htmlspecialchars(trim($_POST['txtTipoSangre']),ENT_QUOTES,'UTF-8');
					$codigo_estudios = trim($_POST['lstEstudios']);
					$codigo_tipo_vivienda = trim($_POST['lstVivienda']);
					$codigo_afp = trim($_POST['lstAfp']);
					$nombre_conyuge = htmlspecialchars(trim($_POST['txtConyuge']),ENT_QUOTES,'UTF-8');

					$codigo_departamento = trim($_POST['lstdepartamento']);
					$codigo_municipio = trim($_POST['lstmunicipio']);
					$direccion = htmlspecialchars(trim($_POST['direccion']),ENT_QUOTES,'UTF-8');
                
                    $telefono_fijo = trim($_POST['telefono_fijo']);
                    $telefono_movil = trim($_POST['telefono_movil']);
					$correo_electronico = trim($_POST['correo_electronico']);	

					$comentario = htmlspecialchars(trim($_POST['txtComentario']));
					//$ = htmlspecialchars(trim($_POST['']),ENT_QUOTES,'UTF-8');
					//$ = trim($_POST['']);
                // Query
					// QUERY UPDATE.
						$query_usuario = sprintf("UPDATE personal SET nombres = '%s', apellidos = '%s', fecha_nacimiento = '%s', edad = '%s', codigo_genero = '%s',
							codigo_estado_civil = '%s', tipo_sangre = '%s', codigo_estudio = '%s', codigo_vivienda = '%s', codigo_afp = '%s', nombre_conyuge = '%s',
						    codigo_municipio = '%s', codigo_departamento = '%s', direccion = '%s',
							telefono_residencia = '%s', telefono_celular = '%s', correo_electronico = '%s',
							codigo_cargo = '%s', fecha_ingreso = '%s', fecha_retiro = '%s', codigo_departamento_empresa = '%s', codigo_clasificacion_empresa = '%s',
							codigo_ruta = '%s', codigo_socio = '%s', numero_cuenta = '%s', pago_diario = '%s', salario = '%s',
							codigo_tipo_licencia = '%s', licencia = '%s', dui = '%s', nit = '%s', isss = '%s', afp = '%s',
							comentario = '%s', codigo_estatus = '%s'
							WHERE id_personal = %d",
							$nombre, $apellido, $fecha_nacimiento, $edad, $codigo_genero, $codigo_estado_civil, $tipo_sangre, $codigo_estudios, $codigo_tipo_vivienda, $codigo_afp, $nombre_conyuge,
							$codigo_municipio, $codigo_departamento, $direccion,
							$telefono_fijo, $telefono_movil, $correo_electronico,
							$codigo_cargo, $fecha_ingreso, $fecha_retiro, $codigo_departamento_empresa, $codigo_clasificacion_empresa,
							$codigo_ruta, $codigo_socio, $numero_cuenta, $pago_diario, $salario,
							$codigo_licencia, $numero_licencia, $dui, $nit, $isss, $afp,
							$comentario, $codigo_estatus,
                            $codigo_personal);	

						// Ejecutamos el query guardar los datos en la tabla alumno..
						$resultadoQuery = $dblink -> query($query_usuario);				
						
					if($resultadoQuery == true){
						$respuestaOK = true;
						$mensajeError = "Se ha Actualizado el registro correctamente";
						$contenidoOK = $query_usuario.$codigo_personal;
					}
					else{
						$mensajeError = "No se puede Actualizar el registro en la base de datos ";
						$contenidoOK = $query_usuario.$codigo_personal;
					}
			break;
			case 'EliminarRegistro':
				// Armamos el query
				// pendiente de crear....
				$query = "DELETE FROM clientes WHERE id_ = $_POST[id_user]";

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
	}elseif($_POST["accion"] === "BuscarCodigo1" or $_POST["accion"] === "GenerarCodigoNuevo" or $_POST["accion"] === "BuscarPorId"){
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