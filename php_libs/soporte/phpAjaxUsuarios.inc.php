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
$mensajeError = "No se puede ejecutar la aplicación";
$contenidoOK = "";
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);
    
// Incluimos el archivo de funciones y conexión a la base de datos

include($path_root."/sgp_web/includes/mainFunctions_conexion.php");

// Validar conexión con la base de datos
if($errorDbConexion == false){
	// Validamos qe existan las variables post
	if(isset($_POST) && !empty($_POST)){
		if(!empty($_POST['accion_buscar'])){
			$_POST['accion'] = $_POST['accion_buscar'];
		}
		// Verificamos las variables de acción
		switch ($_POST['accion']) {
			case 'BuscarUsuario':
				// Armar Colores
				$statusTipo = array ("01" => "btn-success", "02" => "btn-warning", "03" => "btn-danger");
				// Armamos el query.
				$codigo_perfil = trim($_POST['codigo_perfil']);
				
				if(empty($nombres)){
					$query = "SELECT u.id_usuario, u.nombre, u.password, u.codigo_perfil, u.base_de_datos, u.codigo_escuela, u.codigo_personal,
					cat_perfil.codigo as codigo_perfil, cat_perfil.descripcion as descripcion_perfil
					FROM usuarios u
					INNER JOIN catalogo_perfil cat_perfil ON cat_perfil.codigo = u.codigo_perfil
					WHERE u.codigo_perfil = '".$codigo_perfil."' ORDER BY u.nombre ASC";
				}
				
				
				// Ejecutamos el Query.
				$consulta = $dblink -> query($query);

				if($consulta -> rowCount() != 0){
					$respuestaOK = true;
					$num = 0;
					// convertimos el objeto
					while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
					{
						$num++;
						$id_usuario = $listado['id_usuario'];
						$nombre = $listado['nombre'];
						$password = $listado['password'];
						$descripcion_perfil = $listado['descripcion_perfil'];
						$base_de_datos = $listado['base_de_datos'];
						$codigo_escuela = $listado['codigo_escuela'];
						$codigo_personal = $listado['codigo_personal'];
						
						$contenidoOK .= '<tr><td class=centerTXT>'.$num
							.'<td class=centerTXT>'.$id_usuario
							.'<td class=centerTXT>'.$nombre
							.'<td class=centerTXT>'.$codigo_escuela
							.'<td class=centerTXT>'.$codigo_personal
							.'<td class = centerTXT><a data-accion=editar class="btn btn-xs btn-primary" href='.$listado['id_usuario'].'>Editar</a>'
							.'<td class = centerTXT><a data-accion=eliminar class="btn btn-xs btn-warning" href='.$listado['id_usuario'].'>Eliminar</a>'
							;
					}
					$mensajeError = "Si Registro";
				}
				else{
					$respuestaOK = false;
					$contenidoOK = '';
					$mensajeError =  'No Registro';
				}
			break;

			case 'AgregarUsuario':		
				// armar variables.
				$usuario = trim($_POST['Usuario']);
				$password = trim($_POST['password']);
				$dbname = trim($_POST['dbname']);
				$codigo_perfil = trim($_POST['lstPerfilNuevo']);
				$codigo_insfraestructura = trim($_POST['lstCodigoInfraEstructura']);
				$codigo_personal = trim($_POST['lstCodigoPersonal']);
				
				// Armar query.				
					$query = "INSERT INTO usuarios (nombre, password, codigo_perfil, base_de_datos, codigo_escuela, codigo_personal)
					VALUES ('$usuario','$password','$codigo_perfil','$dbname','$codigo_insfraestructura','$codigo_personal')";
	
					// Ejecutamos el query
					$resultadoQuery = $dblink -> query($query);
	
					if($resultadoQuery == true){
						$respuestaOK = true;
						$mensajeError = "Si Save";
						$contenidoOK = 'Se ha agregado el registro correctamente';
					}
					else{
						$mensajeError = "No se puede guardar el registro en la base de datos ".$query;
					}
			break;
			
			case 'EditarUsuario':
				// TABS-1
				$nombre = trim($_POST['Usuario']);
				$password = trim($_POST['password']);
				$dbname = trim($_POST['dbname']);
				$codigo_perfil = trim($_POST['lstPerfilNuevo']);
				$codigo_escuela = trim($_POST['lstCodigoInfraEstructura']);
				$codigo_personal = trim($_POST['lstCodigoPersonal']);
					
				//$ = trim($_POST['']);
				// armar consulta para guardar datos del alumno.
				$query = sprintf("UPDATE usuarios SET nombre='%s', password='%s', base_de_datos='%s', codigo_perfil='%s', codigo_escuela='%s', codigo_personal='%s'
							WHERE id_usuario=%d",
							$nombre,$password,$dbname,$codigo_perfil,$codigo_escuela,$codigo_personal
							,$_POST['id_usuario']);

							
				// Ejecutamos el query guardar los datos en la tabla alumno..
				$resultadoQuery = $dblink -> query($query);

				// Validamos que se haya actualizado el registro
				   if($resultadoQuery == true){					
						$respuestaOK = true;
						$mensajeError = 'Si Update';
						$contenidoOK = 'Se ha Afectado '.' Registro(s).<br>'.'Se ha afectado ';
					}else{
						$mensajeError = 'No se ha actualizado el registro'.$query;
					}
			break;
		
			case 'eliminar':
				// Armamos el query
				$query = "DELETE FROM usuarios WHERE id_usuario = $_POST[id_usuario]";

				// Ejecutamos el query
					$count = $dblink -> exec($query);
				
				// Validamos que se haya actualizado el registro
				if($count != 0){
					$respuestaOK = true;
					$mensajeError = 'Se ha eliminado el registro correctamente'.$query;

					$contenidoOK = 'Se ha Eliminado '.$count.' Registro(s).';

				}else{
					$mensajeError = 'No se ha eliminado el registro'.$query;
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

// Armamos array para convertir a JSON
$salidaJson = array("respuesta" => $respuestaOK,
		"mensaje" => $mensajeError,
		"contenido" => $contenidoOK);

echo json_encode($salidaJson);
?>