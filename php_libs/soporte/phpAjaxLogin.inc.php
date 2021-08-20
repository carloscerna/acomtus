<?php
// limpiar cache.
clearstatcache();
// cambiar a utf-8.
header("Content-Type: text/html;charset=iso-8859-1");
// Variable para la conexión.
$errorDbConexion = false;
// Inicializamos variables de mensajes y JSON
$respuestaOK = false;
$mensajeError = "No se puede ejecutar la aplicación";
$contenidoOK = "";
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);    
// Incluimos el archivo de funciones y conexión a la base de datos
	include($path_root."/acomtus/includes/mainFunctions_login.php");
if($errorDbConexion == false){					// Validar conexión con la base de datos
	// Validamos qe existan las variables post
	if(isset($_POST) && !empty($_POST)){
		if(!empty($_POST['accion_buscar'])){
			$_POST['accion'] = $_POST['accion_buscar'];
		}
		// Verificamos las variables de acción
		switch ($_POST['accion']) {
			case 'BuscarUser':
				// validar si hay datos en los parametros.
					$nombre = trim($_POST['txtnombre']);
					$password_usuario = trim($_POST['txtpassword']);
					//$codigo_infraestructura = trim($_POST['txtcodigoinstitucion']);
				// VERIFICAR SI EL USUARIO ES ROOT.
				if($nombre == 'root'){
					//USUARIO QUE SERVIARÁ PARA LA CREACIÓN DE USUARIOS, PERSONAL Y EMPRESAS.
					// EN EL CASO QUE NO EXISTÁ NADA EN LA BASE DE DATOS.
					$query = "SELECT * FROM usuarios WHERE nombre = '$nombre'";
					$consulta = $dblink -> query($query);
						// convertimos el objeto
						while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
						{
							$hash = trim($listado['password']);
							$codigo_perfil = trim($listado['codigo_perfil']);
							$_SESSION['dbname'] = trim($listado['base_de_datos']);
						}
					// VERIFICAMOS LA CONTRASEÑA
					if(password_verify($password_usuario, $hash)){
							$respuestaOK = true;
							$contenidoOK ="";
							$mensajeError = "Se ha iniciado el Sistema.";
							
							//Guardamos dos variables de sesión que nos auxiliará para saber si se está o no "logueado" un usuario 
							$_SESSION['userNombre'] = $nombre;
							$_SESSION['codigo_perfil'] = $codigo_perfil;
							$_SESSION['logo_uno'] = "no.jpg";
							$_SESSION['codigo_personal'] = "00";
							$_SESSION['nombre_institucion'] = "Configuración Inicial";
							$_SESSION['direccion'] = "Configuración Inicial";
							$_SESSION['nombre_perfil'] = "ROOT";
							$_SESSION['codigo_institucion'] = "ROOT";
							$_SESSION['userID'] = "00";
							$_SESSION['nombre_personal'] = "ROOT";
							$_SESSION['userLogin'] = true;
							$_SESSION["autentica"] = "SI";
							$_SESSION['foto_personal'] = './img/nofoto.jpg';
						}else{
							$respuestaOK = false;
							$contenidoOK = '';
							$mensajeError = 'Este usuario no existe.';
						}
						

				}else{
					/* **************************************************************************************************/
					// SI E SUN PERFIL DIFERENTE A ROOT.
					// armar la consulta.
						$query = "SELECT u.nombre, u.id_usuario, u.base_de_datos, u.codigo_perfil, u.password, u.codigo_personal, btrim(p.nombres || CAST(' ' AS VARCHAR) || p.apellidos) as nombre_personal,
						u.codigo_institucion,
						cat_perfil.descripcion as nombre_perfil
						FROM usuarios u
						INNER JOIN personal p ON p.codigo = u.codigo_personal
						INNER JOIN catalogo_perfil cat_perfil ON cat_perfil.codigo = u.codigo_perfil
						WHERE u.nombre= '$nombre' LIMIT 1";
					// Ejecutamos el Query.
						$consulta = $dblink -> query($query);

					if($consulta -> rowCount() != 0){
						$respuestaOK = true;
						$contenidoOK ="";
						$mensajeError = "Se ha consultado el registro correctamente ";
						
						// convertimos el objeto
						while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
						{
							if(password_verify($password_usuario,trim($listado['password'])))
							{
								$_SESSION['userLogin'] = true;
								$_SESSION['userNombre'] = trim($listado['nombre']);
								$_SESSION['userID'] = $listado['id_usuario'];
								$_SESSION['dbname'] = trim($listado['base_de_datos']);
								$_SESSION['codigo_perfil'] = trim($listado['codigo_perfil']);
								$_SESSION['codigo_personal'] = trim($listado['codigo_personal']);
								$_SESSION['nombre_personal'] = (trim($listado['nombre_personal']));
								$_SESSION['nombre_perfil'] = (trim($listado['nombre_perfil']));
								$_SESSION['nombre_institucion'] = 'ROOT';
								$_SESSION['codigo_institucion'] = (trim($listado['codigo_institucion']));;
								$_SESSION['foto_personal'] = './img/nofoto.jpg';
							}else{
								$respuestaOK = false;
								$contenidoOK = '';
								$mensajeError = 'Este usuario no existe o contreseña Incorrecta.';
									exit;
							}
						}
						
						// Conectarse a la nueva base de datos.
							// ruta de los archivos con su carpeta
								$path_root=trim($_SERVER['DOCUMENT_ROOT']);
							// Incluimos el archivo de funciones y conexión a la base de datos
								include($path_root."/acomtus/includes/mainFunctions_.php");
								include($path_root."/acomtus/includes/funciones.php");
								// Validar conexión.
								if($errorDbConexion == false){
								// Obtener datos de la institución.
									$consulta = "SELECT inf.id_, inf.nombre, inf.direccion, inf.telefono_fijo, p.foto, p.codigo_genero,
												depa.codigo, depa.nombre as nombre_departamento, mu.codigo, mu.codigo_departamento, mu.nombre as nombre_municipio, btrim(p.nombres || cast(' ' as VARCHAR) || p.apellidos) as nombre_personal,
												inf.logo_uno
												from informacion_institucion inf
													INNER JOIN personal p ON p.codigo ='$_SESSION[codigo_personal]'
													INNER JOIN catalogo_departamento depa ON depa.codigo = inf.codigo_departamento
													INNER JOIN catalogo_municipio mu ON mu.codigo = inf.codigo_municipio and mu.codigo = inf.codigo_municipio and mu.codigo_departamento = inf.codigo_departamento
														WHERE inf.codigo_departamento = depa.codigo and inf.id_ = '$_SESSION[codigo_institucion]' LIMIT 1";
								// Ejecutamos la consulta.
									$respuesta = $dblink -> query($consulta);
										if($respuesta -> rowCount() !=0){
											$userData = $respuesta -> fetch(PDO::FETCH_ASSOC);
											// Crear variable global para utilizar con informes y Formularios.
												//$_SESSION['userNombre'] = trim($userData['nombre_institucion']);
												$_SESSION['nombre_institucion'] = trim($userData['nombre']);
												$_SESSION['direccion'] = (trim($userData['direccion']));
												$_SESSION['telefono'] = trim($userData['telefono_fijo']);
												$_SESSION['nombre_municipio'] = utf8_encode(trim($userData['nombre_municipio']));
												$_SESSION['nombre_departamento'] = utf8_encode(trim($userData['nombre_departamento']));
												$_SESSION['nombre_personal'] = (trim($userData['nombre_personal']));
												$_SESSION['logo_uno'] = trim($userData['logo_uno']);

												// Si la foto está vacía.
												if(empty(trim($userData['foto']))){
													if(trim($userData['codigo_genero']) == "01"){
														$_SESSION['foto_personal'] = "./img/avatar_masculino.png" ;
													}else{
														$_SESSION['foto_personal'] = "./img/avatar_femenino.png" ;
													}
												}else{
													$_SESSION['foto_personal'] = trim($userData['foto']);
												}
											//Guardamos dos variables de sesión que nos auxiliará para saber si se está o no "logueado" un usuario 
												$_SESSION["autentica"] = "SI";
										} // Validar si hay archivos.
										else{
											$respuestaOK = false;
											$contenidoOK = "";
											$mensajeError = "No Existen datos de la institución.";}
								} // Validar conexión.
								else{
									$respuestaOK = false;
									$contenidoOK = "";
									$mensajeError = "No Existe la base de datos";}
					}
					else{
						$respuestaOK = false;
						$contenidoOK = '';
						$mensajeError = 'Este usuario no existe o contreseña Incorrecta.';
					}
				}	// COMPRACIÓN DEL USUARIO SI ES ROOT.
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
		$respuestaOK = false;
		$contenidoOK = "";
		$mensajeError = "No Existe la base de datos";}
				

// Armamos array para convertir a JSON
$salidaJson = array("respuesta" => $respuestaOK,
		"mensaje" => $mensajeError,
		"contenido" => $contenidoOK);

echo json_encode($salidaJson);
?>