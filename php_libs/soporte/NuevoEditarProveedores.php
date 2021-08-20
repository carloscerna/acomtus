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
				$query = "SELECT p.id_, p.nombre, p.telefono_uno, p.telefono_dos, p.telefono_celular, p.nombre_contacto, p.direccion, p.nombre_contacto
                            FROM proveedores p
                                WHERE p.id_ = " . $id_x
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
                            $nombre_proveedor = trim($listado['nombre']);
                            $telefono_uno = trim($listado['telefono_uno']);
                            $telefono_dos = trim($listado['telefono_dos']);
							$telefono_celular = trim($listado['telefono_celular']);
							$nombre_contacto = trim($listado['nombre_contacto']);
							$direccion = trim($listado['direccion']);

                        // Rellenando la array.
                            $datos[$fila_array]["nombre_proveedor"] = $nombre_proveedor;
                            $datos[$fila_array]["telefono_uno"] = $telefono_uno;
                            $datos[$fila_array]["telefono_dos"] = $telefono_dos;
							$datos[$fila_array]["telefono_celular"] = $telefono_celular;
							$datos[$fila_array]["nombre_contacto"] = $nombre_contacto;
							$datos[$fila_array]["direccion"] = $direccion;
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
                    $nombre_proveedor = trim($_POST['txtNombreProveedor']);
                    $telefono_uno = trim($_POST['txtTelefonoUno']);
                    $telefono_dos = trim($_POST['txtTelefonoDos']);
					$telefono_celular = trim($_POST['txtTelefonoCelular']);
					$nombre_contacto = trim($_POST['txtNombreContacto']);
					$direccion = trim($_POST['txtDireccion']);
                    ///////////////////////////////////////////////////////////////////////////////////////
					// Query
					$query = "INSERT INTO proveedores (nombre, telefono_uno, telefono_dos, telefono_celular, nombre_contacto, direccion)
						VALUES ('$nombre_proveedor','$telefono_uno','$telefono_dos','$telefono_celular','$nombre_contacto','$direccion')";
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
				// TABS-1
				$nombre_proveedor = trim($_POST['txtNombreProveedor']);
				$telefono_uno = trim($_POST['txtTelefonoUno']);
				$telefono_dos = trim($_POST['txtTelefonoDos']);
				$telefono_celular = trim($_POST['txtTelefonoCelular']);
				$nombre_contacto = trim($_POST['txtNombreContacto']);
				$direccion = trim($_POST['txtDireccion']);	
                // query de actulizar.
                $query_ = sprintf("UPDATE proveedores SET nombre = '%s',  telefono_uno = '%s', telefono_dos = '%s', telefono_celular = '%s', nombre_contacto = '%s',
						direccion = '%s'
						WHERE id_ = %d",
                        $nombre_proveedor, $telefono_uno, $telefono_dos, $telefono_celular, $nombre_contacto, $direccion, $_POST['id_user']);	
                        
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
				$query = "DELETE FROM usuarios WHERE id_usuario = $_POST[id_user]";

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
	}elseif($_POST["accion"] === "BuscarTipoTransporte" or $_POST["accion"] === "GenerarCodigoNuevo" or $_POST["accion"] === "BuscarPorId"){
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