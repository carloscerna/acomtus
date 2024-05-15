<?php
//session_name('demoUI');
//session_start();
// limpiar cache.
clearstatcache();
// Script para ejecutar AJAX
// cambiar a utf-8.
header("Content-Type: text/html;charset=iso-8859-1");
// Insertar y actualizar tabla de usuarios
//sleep(1);

// Inicializamos variables de mensajes y JSON
$datos = array();
$respuestaOK = false;
$mensajeError = "No se encontraron Alertas...";
$contenidoOK = "";
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);
    
// Incluimos el archivo de funciones y conexión a la base de datos

include($path_root."/acomtus/includes/mainFunctions_conexion.php");

// Validar conexión con la base de datos
if($errorDbConexion == false){
	// Validamos qe existan las variables post
	if(isset($_POST) && !empty($_POST)){
		if(!empty($_POST['accionAlertas'])){
			$_POST['accion'] = $_POST['accionAlertas'];
		}
		// Verificamos las variables de acción
		switch ($_POST['accion']) {
			///////////////////////////////////////////////////////////////////////////////////////////////////
			////////////// BLOQUE DE REGISTRO ALERTAS (PERSONAL)
			///////////////////////////////////////////////////////////////////////////////////////////////////
			case 'BuscarAlertas':
				// Armar Colores
				$statusTipo = array ("01" => "btn-success", "02" => "btn-warning", "03" => "btn-danger");
				$codigoPersonal = $_POST['codigoPersonal'];
				// Armamos el query.
					$query = "SELECT pA.id_, pA.codigo_empleado, pA.codigo_alerta  FROM personal_alertas pA WHERE pA.codigo_empleado = '$codigoPersonal'";
				// Ejecutamos el Query.
					$consulta = $dblink -> query($query);
				//
				if($consulta -> rowCount() != 0){
					$mensaje = "Si Registro";
					$respuestaOK = true;
					$num = 0;
					// convertimos el objeto
					while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
					{
						// variablesç
						$id_ = trim($listado['id_']);
						$codigo_empleado = trim($listado['codigo_empleado']);
						$codigo_alerta = trim($listado['codigo_alerta']);
						$num++;
					// variables Json
						$contenidoOK .= "<tr>
							<td><input type=checkbox class=case name=chk$id_ id=chk$id_>
							<td>$num
							<td>$id_
							<td>$codigo_empleado
							<td>$nombre_modalidad
							<td><a data-accion=EliminarModalidad class='btn btn-xs btn-warning' data-toggle='tooltip' data-placement='top' title='Eliminar' href=$id_><i class='fas fa-trash'></i></a>"
						;
					}
				}else{
					$mensaje = "No se encontraron Alertas...";
					$respuestaOK = false;
				}
			break;
			case 'guardarAlertas':
				// consultar el registro antes de agregarlo.
					// Armamos el query y iniciamos variables.
					$codigoEmpleado = $_POST['codigoPersonal'];
					$codigoAlerta = $_POST['lstPersonalAlertas'];
						$query = "SELECT * FROM personal_alertas WHERE codigo_empleado = '$codigoEmpleado' and codigo_alerta = '$codigoAlerta' ";
				// Ejecutamos el Query.
				$consulta = $dblink -> query($query);
				//
				if($consulta -> rowCount() != 0){
					$respuestaOK = false;
					$contenidoOK = "Este registro ya Existe";
					$mensajeError = "Alerta ya Existe.";
				}else{
				// proceso para grabar el registro
					$query = "INSERT INTO personal_alertas (codigo_empleado, codigo_alerta) 
						VALUES ('$codigoEmpleado','$codigoAlerta')";
				// Ejecutamos el Query.
				$consulta = $dblink -> query($query);
					$respuestaOK = true;
					$contenidoOK = "Registro Agregado";
					$mensajeError = "Registro Guardado.";
				}
			break;
			case 'EliminarModalidad':
				// Armamos el query
				$id_ = $_REQUEST["id_"];
					$query = "DELETE FROM organizar_ann_lectivo_ciclos WHERE id_organizar_ann_lectivo_ciclos = '$id_'";
				// Ejecutamos el query
					$count = $dblink -> exec($query);
				// Validamos que se haya actualizado el registro
				if($count != 0){
					$respuestaOK = true;
					$mensajeError = 'Se ha eliminado el registro correctamente';

					$contenidoOK = 'Se ha Eliminado '.$count.' Registro(s).';

				}else{
					$mensajeError = 'No se ha eliminado el registro';
				}
			break;
			
		}
	}
	else{
		$mensajeError = 'No se puede ejecutar la aplicación';}
}
else{
	$mensajeError = 'No se puede establecer conexión con la base de datos';
}	// FIN DE LA CONDICON PRINCRIPAL
// CONDICIONES RESULTADO DEL JSON Y DATA[]
$CodigoAlertas = $_POST['accion'];
if($CodigoAlertas == "BuscarAlertas")
{
// Armamos array para convertir a JSON
	$salidaJson = array("respuesta" => $respuestaOK,
		"mensaje" => $mensajeError,
		"contenido" => $contenidoOK);
			echo json_encode($salidaJson);
}
// data[]
if($CodigoAlertas == "Editar")
{
// Armamos array para convertir a JSON
	echo json_encode($datos);
}

?>