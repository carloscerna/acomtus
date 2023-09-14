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
				$query = 'SELECT u.id_usuario, TRIM(u.nombre) AS nombre, TRIM(u.password) AS password, u.codigo_perfil, TRIM(u.base_de_datos) AS base_de_datos, TRIM(u.codigo_estatus) AS codigo_estatus,
								u.codigo_personal, u.codigo_institucion, u.codigo_departamento_empresa,
								cat_est.descripcion as nombre_estatus
									FROM usuarios u
										INNER JOIN catalogo_estatus cat_est ON cat_est.codigo = u.codigo_estatus
											WHERE u.id_usuario = ' . $id_x
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
							$password = trim($listado['password']);
							$codigo_perfil = trim($listado['codigo_perfil']);
							$codigo_estatus = trim($listado['codigo_estatus']);
							$codigo_personal = trim($listado['codigo_personal']);
							$codigo_institucion = trim($listado['codigo_institucion']);
							$codigo_departamento_empresa= trim($listado['codigo_departamento_empresa']);
						// Rellenando la array.
							$datos[$fila_array]["nombre"] = $nombre;
							$datos[$fila_array]["password"] = $password;
							$datos[$fila_array]["codigo_perfil"] = $codigo_perfil;
							$datos[$fila_array]["codigo_estatus"] = $codigo_estatus;
							$datos[$fila_array]["codigo_personal"] = $codigo_personal;
							$datos[$fila_array]["codigo_institucion"] = $codigo_institucion;
							$datos[$fila_array]["codigo_departamento_empresa"] = $codigo_departamento_empresa;
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
				// TABS-1
					$nombre = trim($_POST['txtnombres']);
					$password = trim($_POST['Password']);
					$confirmapassword = trim($_POST['ConfirmaPassword']);
					$codigo_empresa = trim($_POST['lstempresa']);
					$codigo_departamento_empresa = trim($_POST['lstDepartamentoEmpresa']);
					$codigo_perfil = trim($_POST['lstperfil']);
					$codigo_personal = trim($_POST['lstpersonal']);
					$codigo_estatus = trim($_POST['lstestatus']);	
					$base_de_datos = $_SESSION['dbname'];		
					// VERIFICAR SI LAS CONTRASEÑAS SON IGUALES.
					if($password != $confirmapassword){
						$mensajeError = "Las contreseñas deben de ser Iguales";
						$contenidoOK = $password . " " . $confirmapassword;
							break;
					}else{
						$password = password_hash($_POST['Password'],PASSWORD_DEFAULT,['cost'=>12]);
					}
					//VERIFICAR QUE NO EXISTA EL NOMBRE DE USUARIO.
						$query_verificar = "SELECT * FROM usuarios WHERE nombre = '$nombre' LIMIT 1";
							$resultado = $dblink -> query($query_verificar);              	
							$num_ = $resultado -> rowCount();
								if($num_ != 0){
									$mensajeError = "El nombre de usuario YA EXISTE...";
									$contenidoOK = $query_verificar . ' ' . $num_;
										break;
								}
					// Query
					$query = "INSERT INTO usuarios (nombre, password, codigo_perfil, codigo_institucion, codigo_personal, codigo_estatus, base_de_datos, codigo_departamento_empresa)
						VALUES ('$nombre','$password','$codigo_perfil','$codigo_empresa','$codigo_personal','$codigo_estatus', '$base_de_datos','$codigo_departamento_empresa')";
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
				$nombre = trim($_POST['txtnombres']);
				$codigo_empresa = trim($_POST['lstempresa']);
				$codigo_departamento_empresa = trim($_POST['lstDepartamentoEmpresa']);
				$codigo_perfil = trim($_POST['lstperfil']);
				$codigo_personal = trim($_POST['lstpersonal']);
				$codigo_estatus = trim($_POST['lstestatus']);			
				// VERIFICAR EL CAMBIO DE CONTRASEÑA.
					$cambiopassword = trim($_POST['cambiopassword']);
				//	CONDICOINAR SI SE VA CAMBIAR.
					if($cambiopassword == "yes"){
						$password = trim($_POST['Password']);
						$confirmapassword = trim($_POST['ConfirmaPassword']);					
						if($password != $confirmapassword){
						// VERIFICAR SI LAS CONTRASEÑAS SON IGUALES.
							$mensajeError = "Las contreseñas deben de ser iguales";
							$contenidoOK = $password . " " . $confirmapassword;
								break;
						}else{
								$password = password_hash($_POST['Password'],PASSWORD_DEFAULT,['cost'=>12]);
							}
					}
					// QUERY UPDATE.
					if($cambiopassword == "yes"){
						$query_usuario = sprintf("UPDATE usuarios SET nombre = '%s', password = '%s', codigo_perfil = '%s', codigo_institucion = '%s',
						codigo_personal = '%s', codigo_estatus = '%s' 
						WHERE id_usuario = %d",
						$nombre, $password,  $codigo_perfil, $codigo_empresa, $codigo_personal, $codigo_estatus, $_POST['id_user']);	
					}else{
						$query_usuario = sprintf("UPDATE usuarios SET nombre = '%s', codigo_perfil = '%s', codigo_institucion = '%s',
						codigo_personal = '%s', codigo_estatus = '%s', codigo_departamento_empresa = '%s' 
						WHERE id_usuario = %d",
						$nombre, $codigo_perfil, $codigo_empresa, $codigo_personal, $codigo_estatus, $codigo_departamento_empresa, $_POST['id_user']);	
					}
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
				$nombre = trim($_POST['nombre']);
				// COMPARAR QUE ELUSUARIO ROOT NO PUEDA SER ELIMINADO.
				if($nombre == 'root'){
					$mensajeError = "El Usuario <b> root </b> no se puede Eliminar";
						break;
				}
				// Armamos el query
					$query = "DELETE FROM usuarios WHERE id_usuario = $_POST[id_user]";
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