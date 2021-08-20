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
				$query = 'SELECT TRIM(nombre) AS nombre, TRIM(direccion) as direccion, codigo_municipio, codigo_departamento, telefono_fijo, telefono_movil, telefono_fax,
							correo_electronico, nit, nrc, logo_uno
								FROM informacion_institucion
									WHERE id_ = ' . $id_x
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
							$nombre = trim($listado['nombre']);
							$direccion = trim($listado['direccion']);
                            $codigo_municipio = trim($listado['codigo_municipio']);
                            $codigo_departamento = trim($listado['codigo_departamento']);

                            $telefono_fijo = trim($listado['telefono_fijo']);
                            $telefono_movil = trim($listado['telefono_movil']);
                            $telefono_fax = trim($listado['telefono_fax']);
                            $correo_electronico = trim($listado['correo_electronico']);
                            $nit = trim($listado['nit']);
							$nrc = trim($listado['nrc']);
						//	Logo.
							$url_foto = trim($listado['logo_uno']);
						// Rellenando la array.
							$datos[$fila_array]["nombre"] = $nombre;
							$datos[$fila_array]["direccion"] = $direccion;
                            $datos[$fila_array]["codigo_municipio"] = $codigo_municipio;
                            $datos[$fila_array]["codigo_departamento"] = $codigo_departamento;
                            $datos[$fila_array]["telefono_fijo"] = $telefono_fijo;
                            $datos[$fila_array]["telefono_movil"] = $telefono_movil;
                            $datos[$fila_array]["telefono_fax"] = $telefono_fax;
                            $datos[$fila_array]["correo_electronico"] = $correo_electronico;
                            $datos[$fila_array]["nit"] = $nit;
							$datos[$fila_array]["nrc"] = $nrc;
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

			case 'AgregarNuevoUsuario':		
				// armar variables.
				// INFO 1
					$nombre = trim($_POST['txtnombres']);
					$direccion = trim($_POST['direccion']);
					$codigo_departamento = trim($_POST['lstdepartamento']);
                    $codigo_municipio = trim($_POST['lstmunicipio']);
                // INFO 2
					$telefono_fijo = trim($_POST['telefono_fijo']);
					$telefono_movil = trim($_POST['telefono_movil']);
                    $telefono_fax = trim($_POST['telefono_fax']);			
                    $correo_electronico = trim($_POST['correo_electronico']);			
                // INFO 3
                    $nit = trim($_POST['nit']);			
                    $nrc = trim($_POST['nrc']);			
				// Query
					$query = "INSERT INTO informacion_institucion (nombre, direccion, codigo_municipio, codigo_departamento, telefono_fijo, telefono_movil, telefono_fax, correo_electronico, nit, nrc)
						VALUES ('$nombre','$direccion','$codigo_municipio','$codigo_departamento','$telefono_fijo','$telefono_movil','$telefono_fax','$correo_electronico','$nit','$nrc')";
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
                // INFO 1
                    $nombre = trim($_POST['txtnombres']);
                    $direccion = trim($_POST['direccion']);
                    $codigo_departamento = trim($_POST['lstdepartamento']);
                    $codigo_municipio = trim($_POST['lstmunicipio']);
                // INFO 2
                    $telefono_fijo = trim($_POST['telefono_fijo']);
                    $telefono_movil = trim($_POST['telefono_movil']);
                    $telefono_fax = trim($_POST['telefono_fax']);			
                    $correo_electronico = trim($_POST['correo_electronico']);			
                // INFO 3
                    $nit = trim($_POST['nit']);			
                    $nrc = trim($_POST['nrc']);			
                // Query
					// QUERY UPDATE.
						$query_usuario = sprintf("UPDATE informacion_institucion SET nombre = '%s', direccion = '%s', codigo_municipio = '%s', codigo_departamento = '%s',
							telefono_fijo = '%s', telefono_movil = '%s', telefono_fax = '%s', correo_electronico = '%s', nit = '%s', nrc = '%s'
							WHERE id_ = %d",
                            $nombre, $direccion, $codigo_municipio, $codigo_departamento, 
                            $telefono_fijo, $telefono_movil, $telefono_fax, $correo_electronico, $nit, $nrc,
                            $_POST['id_user']);	

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
				$query = "DELETE FROM informacion_institucion WHERE id_ = $_POST[id_user]";

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