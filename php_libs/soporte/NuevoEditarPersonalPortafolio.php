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
$id_portafolio = 0;
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
		if(!empty($_POST['accionPortafolio'])){
			$_POST['accion'] = $_POST['accionPortafolio'];
		}
		// Verificamos las variables de acci�n
		switch ($_POST['accion']) {
			case 'BuscarIdPortafolio':
				$Id_personal = $_SESSION["id_personal"];
				$id_p_p = trim($_POST['id_p_p']);
				
				$new = explode("-",$id_p_p);
				$id_portafolio = $new[0];
				$id_personal = $new[1];
				// ARVAR EL URL DEPENDIENDO DEL CODIGO PERSONAL
					$url_ = "../acomtus/img/portafolio/" . $Id_personal . "/";
					$url_no = "../acomtus/img/NoDisponible.jpg";
					// Armamos el query.
					$query = "SELECT * FROM personal_portafolio
								WHERE id_personal = '$Id_personal' and id_ = '$id_portafolio'
									ORDER BY fecha DESC"
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
							// CAMPOS Y VARIALES DE LA TABLA.
								$id_portafolio = trim($listado['id_']);
								$fecha = trim($listado['fecha']);
								$titulo = trim($listado['titulo']);
								$descripcion = trim($listado['descripcion']);
								$nombre_imagen = trim($listado['url_imagen']);
								$id_personal = trim($listado['id_personal']);
							//	PASAR A VARIABLES PUBLICA
								$_SESSION['id_portafolio'] = trim($listado['id_']);
							//	VERIFICAR SI NO EXISTE LA IMAGEN.
								if (empty($nombre_imagen)) {
									$ruta_imagen = $url_no;
								}else{
									$ruta_imagen = $url_ . $nombre_imagen;
								}
							//	 ENVIAR DATA ARRAY
								$datos[$fila_array]["id_"] = $id_portafolio;
								$datos[$fila_array]["fecha"] = $fecha;
								$datos[$fila_array]["titulo"] = $titulo;
								$datos[$fila_array]["descripcion"] = $descripcion;
								$datos[$fila_array]["nombre_imagen"] = $ruta_imagen;
								$datos[$fila_array]["id_personal"] = $id_personal;
						}
						$mensajeError = "Portafolios Encontrado.";
					}
					else{
						$respuestaOK = false;
						$contenidoOK = '<h4>No hay Elementos que mostrar.</h4>';
						$mensajeError =  'No Hay Portafolio';
					}
				break;
		case 'BuscarPorCodigoPersonal':
			$Id_personal = $_SESSION["id_personal"];
			$html = '';
			if(!isset($_POST['page'])){$page = 1;}else{$page = $_POST['page'];}
			$rowsPerPage = 3;
			$offset = ($page - 1) * $rowsPerPage;
			// ARVAR EL URL DEPENDIENDO DEL CODIGO PERSONAL
				$url_ = "../acomtus/img/portafolio/" . $Id_personal . "/" . "thumbails/";
				$url_large = "../acomtus/img/portafolio/" . $Id_personal . "/" . "large/";
				$url_no = "../acomtus/img/NoDisponible.jpg";
				//	Total de registros.
					$query_total = "SELECT * FROM personal_portafolio WHERE id_personal = '$Id_personal'";
				// Ejecutamos el Query.
					$consulta_rows = $dblink -> query($query_total);
				// Prepara paginación.
					$total_portafolio = $consulta_rows -> rowCount();
					$paginas = $total_portafolio / $rowsPerPage;
					$numero_paginas = ceil($paginas);
				// Armamos el query.
					$query = "SELECT id_, to_char(fecha,'dd/MM/yyyy') as fecha, titulo, descripcion, url_imagen, id_personal FROM personal_portafolio
							WHERE id_personal = '$Id_personal'
								ORDER BY fecha DESC LIMIT '$rowsPerPage' OFFSET '$offset'"
						;
				// Ejecutamos el Query.
				$consulta = $dblink -> query($query);
				// Validar si hay registros.
				if($consulta -> rowCount() != 0){
					$respuestaOK = true;
					$num = 0;
					// convertimos el objeto
					// crear fila inicial.
					$contenidoOK .="<div class='row'><div class='card-deck'>";
					while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
					{
						// CAMPOS Y VARIALES DE LA TABLA.
							$id_portafolio = trim($listado['id_']);
							$fecha = trim($listado['fecha']);
							$titulo = trim($listado['titulo']);
							$descripcion = trim($listado['descripcion']);
							$nombre_imagen = trim($listado['url_imagen']);
							$id_personal = trim($listado['id_personal']);
						//	VERIFICAR SI NO EXISTE LA IMAGEN.
							if (empty($nombre_imagen)) {
								$ruta_imagen = $url_no;
								$ruta_imagen_large = $url_no;
							}else{
								$ruta_imagen = $url_ . $nombre_imagen;
								$ruta_imagen_large = $url_large . $nombre_imagen;
							}
						// ENVIAR AL CONTENIDO AL SECTION.
							$contenidoOK .="
									<div class='card'>
										<div class='card-header text-right'>$fecha</div>
										<div class='text-center'>
											<a href='#' data-toggle='modal' data-target='#myModal$id_portafolio'>
												<img class='img-fluid rounded' src='$ruta_imagen' alt='Portafolio style='width=auto; height=94;'>
											</a>
										</div>
										<div class='card-body'>
											<h4 class='card-title'><a>$titulo</a></h4>
											<p class='card-text'>$descripcion</p>
										</div>
										<div class='card-footer text-center'>
											<a data-accion=EditarRegistro class='btn btn-info btn-sm text-dark' alt='Editar' href='$id_portafolio-$id_personal'>Editar</a>
											<a data-accion=EliminarRegistro class='btn btn-warning btn-sm text-dark' alt='Editar' href='$id_portafolio-$id_personal'>Eliminar</a>
										</div>
									</div>
							";
							//<!-- The Modal -->
								$contenidoOK .= "
									<div class='modal fade' id='myModal$id_portafolio' tabaindex='-1' role='dialog' aria-labelledby='myModalLabel$id_portafolio' aria-hidden='true'>
										<!-- Modal button close -->
											<button type='button' class='close mr-2' data-dismiss='modal' aria-label='Close'>
												<span aria-hidden='true'>&times;</span>
											</button>
										<!-- Modal body -->
											<div class='modal-dialog modal-lg modal-dialog-centered' role='document'>
												<img class='img-fluid rounded' src='$ruta_imagen_large'>
											</div>
									</div>";
							//<!-- The Modal -->
					}	// FIN DEL WHILE.
					//	cierre de fila.
						$contenidoOK .="</div></div>";
					// PAGINACIÓN.
					// APERTURA
						$contenidoOK .="<div class='row'><div class='col-lg-12'>";
						$contenidoOK .="<ul class='pagination'>";

						for($jj=1;$jj<=$numero_paginas;$jj++){
							$class_active = '';
							if ($jj == 1) {
								$class_active = 'active';
							}
								$contenidoOK .= "<li class='page-item $class_active'><a class='page-link' href='$jj' data-accion='PaginacionPortafolio'>$jj</a></li>";
						}
						$contenidoOK .= "</ul>";
						$contenidoOK .="</div></div>";
					// CIERRE
					// PAGINACIÓN.
						// json array.
							$mensajeError = "Portafolios Encontrado.";
				}
				else{
					$respuestaOK = false;
					$contenidoOK = '<h4>No hay Elementos que mostrar.</h4>';
					$mensajeError =  'No Hay Portafolio';
				}
			break;

			case 'AgregarNuevo':		
				// armar variables.
				// PORTAFOLIO
					$id_user = trim($_POST['id_user']);
					$fecha = trim($_POST['txtFechaPortafolio']);
					$titulo = trim($_POST['TituloPortafolio']);
					$descripcion = htmlspecialchars(trim($_POST['txtComentarioPortafolio']));
						
				// Query
					$query = "INSERT INTO personal_portafolio (fecha, titulo, descripcion, id_personal)
						VALUES ('$fecha','$titulo','$descripcion','$id_user')";
					// Ejecutamos el query
						$resultadoQuery = $dblink -> query($query);              
                        ///////////////////////////////////////////////////////////////////////////////////////
                        ///////////////////////////////////////////////////////////////////////////////////////
					if($resultadoQuery == true){

						$query = "SELECT lastval()";
							$consulta = $dblink -> query($query);              
							while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
							{
								$_SESSION['id_portafolio'] = $listado['lastval'];
								$id_portafolio = $listado['lastval'];
							}
						$respuestaOK = true;
						$mensajeError = "Se ha agregado el registro correctamente (Portafolio)";
						$contenidoOK = '';
					}
					else{
						$mensajeError = "No se puede guardar el registro en la base de datos ";
					}
			break;
			
			case 'EditarRegistro':
				// PORTAFOLIO
				$id_portafolio = trim($_POST['IdPortafolio']);
				$Id_personal = $_SESSION["id_personal"];
				$fecha = trim($_POST['txtFechaPortafolio']);
				$titulo = trim($_POST['TituloPortafolio']);
				$descripcion = htmlspecialchars(trim($_POST['txtComentarioPortafolio']));
					//$ = htmlspecialchars(trim($_POST['']),ENT_QUOTES,'UTF-8');
					//$ = trim($_POST['']);
                // Query
					// QUERY UPDATE.
						$query_usuario = sprintf("UPDATE personal_portafolio SET fecha = '%s', titulo = '%s', descripcion = '%s'
							WHERE id_ = %d and id_personal = '%d'",
							$fecha, $titulo, $descripcion,
                            $id_portafolio, $Id_personal);	

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
				$id_p_p = trim($_POST['id_p_p']);
				$small = 'thumbails/';
				$large = 'large/';

				$new = explode("-",$id_p_p);
				$id_portafolio = $new[0];
				$id_personal = $new[1];
				// URL PARA GUARDAR LAS IMAGENES.
					$url_ = "/acomtus/img/portafolio/";
				// Armamos el query PARA ELIMINAR LA IMAGEN O SEA EL ARCHIVO.
					$query_file = "SELECT * FROM personal_portafolio WHERE id_ = $id_portafolio";
				// Ejecutamos el query
					$resultadoQuery = $dblink -> query($query_file);				
					while($listado = $resultadoQuery -> fetch(PDO::FETCH_BOTH))
					{
						$nombreArchivo = trim($listado['url_imagen']);
						$id_personal = trim($listado['id_personal']);
					}
				// Armamos el query PARA ELIMINAR EL REGISTRO
					$query = "DELETE FROM personal_portafolio WHERE id_ = $id_portafolio";
				// Ejecutamos el query
					$count = $dblink -> exec($query);
				// REGISTRO CON UNLINK().
					if(!empty($nombreArchivo)){
						unlink($path_root.$url_.$id_personal."/".$nombreArchivo);				// imagen original.
						unlink($path_root.$url_.$id_personal."/".$small."/".$nombreArchivo);	// imagen small
						unlink($path_root.$url_.$id_personal."/".$large."/".$nombreArchivo);	// imagen large	
					}

				// Validamos que se haya actualizado el registro
				if($count != 0){
					$respuestaOK = true;
					$mensajeError = 'Se ha Eliminado '.$count.' Registro(s). Imagen: ' . $nombreArchivo . 'Código Personal: ' . $id_personal;

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
	}elseif($_POST["accion"] === "BuscarCodigo" or $_POST["accion"] === "BuscarIdPortafolio" or $_POST["accion"] === "BuscarPorId"){
		echo json_encode($datos);
		}
	else{
		// Armamos array para convertir a JSON
		$salidaJson = array("respuesta" => $respuestaOK,
			"mensaje" => $mensajeError,
			"id_portafolio" => $id_portafolio,
			"contenido" => $contenidoOK);
		echo json_encode($salidaJson);
	}
?>