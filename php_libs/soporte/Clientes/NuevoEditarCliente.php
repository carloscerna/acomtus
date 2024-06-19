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
				$id_ = trim($_POST['id_x']);
				// Armamos el query.
				$query = "SELECT *FROM clientes c
							WHERE codigo <> '' and id_ = '$id_'"
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
							$codigo = trim($listado['codigo']);	
							$nombre_cliente = trim($listado['nombres']) . ' ' . trim($listado['primer_apellido']) . ' ' . trim($listado['segundo_apellido']);
							$nombre = trim($listado['nombres']);
							$primer_apellido = trim($listado['primer_apellido']);
                            $segundo_apellido = trim($listado['segundo_apellido']);
                            $codigo_nacionalidad = trim($listado['codigo_nacionalidad']);

                            $dui = trim($listado['dui']);
                            $pasaporte = trim($listado['pasaporte']);
							
                            $codigo_municipio_nacimiento = trim($listado['codigo_municipio_nacimiento']);
                            $codigo_departamento_nacimiento = trim($listado['codigo_departamento_nacimiento']);
                            $fecha_nacimiento = trim($listado['fecha_nacimiento']);
							$edad = trim($listado['edad']);

							$codigo_genero = trim($listado['codigo_genero']);
							$codigo_estado_civil = trim($listado['codigo_estado_civil']);
							$codigo_estudio = trim($listado['codigo_estudio']);
							$codigo_vivienda = trim($listado['codigo_vivienda']);

							$nombre_conyuge = trim($listado['nombre_conyuge']);
                            $primer_apellido_conyuge = trim($listado['primer_apellido_conyuge']);
                            $segundo_apellido_conyuge = trim($listado['segundo_apellido_conyuge']);

                            $dui_conyuge = trim($listado['dui_conyuge']);
                            $pasaporte_conyuge = trim($listado['pasaporte_conyuge']);
                            $telefono_conyuge = trim($listado['telefono_conyuge']);
                            $codigo_nacionalidad_conyuge = trim($listado['codigo_nacionalidad_conyuge']);

							$codigo_municipio = trim($listado['codigo_municipio']);
                            $codigo_departamento = trim($listado['codigo_departamento']);
							$direccion = trim($listado['direccion']);
                            $telefono_domicilio = trim($listado['telefono_domicilio']);
                            $telefono_movil = trim($listado['telefono_celular']);
							$correo_electronico = trim($listado['correo_electronico']);

							//$ = trim($listado['']);
							//$ = trim($listado['']);
						// Rellenando la array.
							$datos[$fila_array]["codigo"] = $codigo;
							$datos[$fila_array]["nombre_cliente"] = $nombre_cliente;
							$datos[$fila_array]["nombre"] = $nombre;
							$datos[$fila_array]["primer_apellido"] = $primer_apellido;
                            $datos[$fila_array]["segundo_apellido"] = $segundo_apellido;
                            $datos[$fila_array]["codigo_nacionalidad"] = $codigo_nacionalidad;

							$datos[$fila_array]["codigo_municipio_nacimiento"] = $codigo_municipio_nacimiento;
							$datos[$fila_array]["codigo_departamento_nacimiento"] = $codigo_departamento_nacimiento;
							$datos[$fila_array]["fecha_nacimiento"] = $fecha_nacimiento;
							$datos[$fila_array]["edad"] = $edad;	
                            
                            $datos[$fila_array]["dui"] = $dui;	
                            $datos[$fila_array]["pasaporte"] = $pasaporte;	

							$datos[$fila_array]["codigo_genero"] = $codigo_genero;
                            $datos[$fila_array]["codigo_estado_civil"] = $codigo_estado_civil;
							$datos[$fila_array]["codigo_estudio"] = $codigo_estudio;
							$datos[$fila_array]["codigo_vivienda"] = $codigo_vivienda;

							$datos[$fila_array]["nombre_conyuge"] = $nombre_conyuge;
                            $datos[$fila_array]["primer_apellido_conyuge"] = $primer_apellido_conyuge;
                            $datos[$fila_array]["segundo_apellido_conyuge"] = $segundo_apellido_conyuge;
                            $datos[$fila_array]["codigo_nacionalidad_conyuge"] = $codigo_nacionalidad_conyuge;

                            $datos[$fila_array]["telefono_conyuge"] = $telefono_conyuge;	
                            $datos[$fila_array]["dui_conyuge"] = $dui_conyuge;	
                            $datos[$fila_array]["pasaporte_conyuge"] = $pasaporte_conyuge;	
                            
							$datos[$fila_array]["codigo_municipio"] = $codigo_municipio;
							$datos[$fila_array]["codigo_departamento"] = $codigo_departamento;
							$datos[$fila_array]["direccion"] = $direccion;
                            $datos[$fila_array]["telefono_domicilio"] = $telefono_domicilio;
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

			case 'AgregarNuevoCliente':		
				// armar variables.
					// INFO 1
					$codigo_cliente = trim($_POST['txtcodigo']);
					$codigo_estatus = trim($_POST['lstestatus']);
					//
					$nombres = trim($_POST['txtnombres']);
					$primer_apellido = trim($_POST['txtprimerapellido']);
                    $segundo_apellido = trim($_POST['txtsegundoapellido']);
                    $codigo_genero = trim($_POST['lstgenero']);
//
                    $dui = trim($_POST['txtDui']);
                    $pasaporte = trim($_POST['txtPasaporte']);
                    $nacionalidad = trim($_POST['lstNacionalidad']);
					$codigo_estado_civil = trim($_POST['lstEstadoCivil']);
//
					$codigo_departamento_nacimiento = trim($_POST['lstdepartamentoNacimiento']);
					$codigo_municipio_nacimiento = trim($_POST['lstmunicipioNacimiento']);
					$fecha_nacimiento = trim($_POST['fechanacimiento']);
					$edad = trim($_POST['txtedad']);
//
					$codigo_departamento = trim($_POST['lstdepartamento']);
					$codigo_municipio = trim($_POST['lstmunicipio']);
					$direccion = htmlspecialchars(trim($_POST['direccion']),ENT_QUOTES,'UTF-8');
                    $codigo_tipo_vivienda = trim($_POST['lstResidencia']);
//
                    $telefono_fijo = trim($_POST['telefono_fijo']);
					$telefono_movil = trim($_POST['telefono_movil']);
                    $email = trim($_POST['correo_electronico']);
					$codigo_estudios = trim($_POST['lstEstudios']);
//					
					$nombre_conyuge = htmlspecialchars(trim($_POST['txtnombresConyuge']),ENT_QUOTES,'UTF-8');
					$primer_apellido_conyuge = trim($_POST['txtprimerapellidoConyuge']);
                    $segundo_apellido_conyuge = trim($_POST['txtsegundoapellidoConyuge']);
                    $dui_conyuge = trim($_POST['txtDuiConyuge']);
//                    
                    $pasaporte_conyuge = trim($_POST['txtPasaporteConyuge']);
                    $nacionalidad_conyuge = trim($_POST['lstNacionalidadConyuge']);
                    $telefono_movil_conyuge = trim($_POST['txtTelefonoCelularConyuge']);
					

				// Query
					$query = "INSERT INTO clientes (codigo, codigo_estatus, nombres, primer_apellido, segundo_apellido,
                        codigo_genero, dui, pasaporte, codigo_nacionalidad, codigo_estado_civil, codigo_departamento,
                        codigo_municipio, direccion, codigo_vivienda, telefono_domicilio, telefono_celular, correo_electronico,
                        codigo_estudio, nombre_conyuge, primer_apellido_conyuge, segundo_apellido_conyuge, dui_conyuge, pasaporte_conyuge,
                        codigo_nacionalidad_conyuge, telefono_conyuge, edad, codigo_departamento_nacimiento, codigo_municipio_nacimiento,
                        fecha_nacimiento
                        )
						VALUES ('$codigo_cliente','$codigo_estatus','$nombres','$primer_apellido','$segundo_apellido',
                        '$codigo_genero','$dui','$pasaporte','$nacionalidad','$codigo_estado_civil','$codigo_departamento','$codigo_municipio',
                        '$direccion','$codigo_tipo_vivienda','$telefono_fijo','$telefono_movil','$email',
                        '$codigo_estudios','$nombre_conyuge','$primer_apellido_conyuge','$segundo_apellido_conyuge','$dui_conyuge','$pasaporte_conyuge',
                        '$nacionalidad_conyuge','$telefono_movil_conyuge','$edad','$codigo_departamento_nacimiento','$codigo_municipio_nacimiento',
                        '$fecha_nacimiento'
                        )";
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
                $id_ = trim($_POST['id_user']);
                $codigo_estatus = trim($_POST['lstestatus']);
                //
                $nombres = trim($_POST['txtnombres']);
                $primer_apellido = trim($_POST['txtprimerapellido']);
                $segundo_apellido = trim($_POST['txtsegundoapellido']);
                $codigo_genero = trim($_POST['lstgenero']);
//
                $dui = trim($_POST['txtDui']);
                $pasaporte = trim($_POST['txtPasaporte']);
                $codigo_nacionalidad = trim($_POST['lstNacionalidad']);
                $codigo_estado_civil = trim($_POST['lstEstadoCivil']);
//
                $codigo_departamento_nacimiento = trim($_POST['lstdepartamentoNacimiento']);
                $codigo_municipio_nacimiento = trim($_POST['lstmunicipioNacimiento']);
                $fecha_nacimiento = trim($_POST['fechanacimiento']);
                $edad = trim($_POST['txtedad']);
//
                $codigo_departamento = trim($_POST['lstdepartamento']);
                $codigo_municipio = trim($_POST['lstmunicipio']);
                $direccion = htmlspecialchars(trim($_POST['direccion']),ENT_QUOTES,'UTF-8');
                $codigo_tipo_vivienda = trim($_POST['lstResidencia']);
//
                $telefono_fijo = trim($_POST['telefono_fijo']);
                $telefono_movil = trim($_POST['telefono_movil']);
                $email = trim($_POST['correo_electronico']);
                $codigo_estudios = trim($_POST['lstEstudios']);
//					
                $nombre_conyuge = htmlspecialchars(trim($_POST['txtnombresConyuge']),ENT_QUOTES,'UTF-8');
                $primer_apellido_conyuge = trim($_POST['txtprimerapellidoConyuge']);
                $segundo_apellido_conyuge = trim($_POST['txtsegundoapellidoConyuge']);
                $dui_conyuge = trim($_POST['txtDuiConyuge']);
//                    
                $pasaporte_conyuge = trim($_POST['txtPasaporteConyuge']);
                $codigo_nacionalidad_conyuge = trim($_POST['lstNacionalidadConyuge']);
                $telefono_movil_conyuge = trim($_POST['txtTelefonoCelularConyuge']);
                // Query
					// QUERY UPDATE.
						$query_usuario = "UPDATE clientes SET codigo_estatus = '$codigo_estatus',
                        nombres = '$nombres', primer_apellido = '$primer_apellido', segundo_apellido = '$segundo_apellido',
                        codigo_genero = '$codigo_genero', dui = '$dui', pasaporte = '$pasaporte', codigo_nacionalidad = '$codigo_nacionalidad',
                        codigo_estado_civil = '$codigo_estado_civil', codigo_departamento = '$codigo_departamento', codigo_municipio = '$codigo_municipio',
                        direccion = '$direccion', codigo_vivienda= '$codigo_tipo_vivienda', telefono_domicilio = '$telefono_fijo', telefono_celular = '$telefono_movil', correo_electronico = '$email',
                        codigo_estudio = '$codigo_estudios', nombre_conyuge = '$nombre_conyuge', primer_apellido_conyuge = '$primer_apellido_conyuge', segundo_apellido_conyuge = '$segundo_apellido_conyuge', 
                        dui_conyuge = '$dui_conyuge', pasaporte_conyuge = '$pasaporte_conyuge',
                        codigo_nacionalidad_conyuge = '$codigo_nacionalidad_conyuge', telefono_conyuge= '$telefono_movil_conyuge', edad = '$edad', 
                        codigo_departamento_nacimiento = '$codigo_departamento_nacimiento', codigo_municipio_nacimiento = '$codigo_municipio_nacimiento',
                        fecha_nacimiento = '$fecha_nacimiento'
                        WHERE id_ = '$id_'
                        ";

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