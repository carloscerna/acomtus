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
				$codigoCliente = $_POST['codigoCliente'];
				// Armamos el query.
					$query = "SELECT cA.id_, cA.codigo_cliente, cA.codigo_alerta,
						cat_c_clientes.descripcion as alerta_descripcion, cat_c_clientes.codigo_clasificacion_riesgo,
						cat_c_riesgos.descripcion as riesgo_descripcion, cat_c_riesgos.color
							FROM clientes_alertas cA 
								INNER JOIN catalogo_conductas_clientes_proveedores cat_c_clientes ON cA.codigo_alerta = cat_c_clientes.codigo
								INNER JOIN catalogo_clasificacion_riesgos cat_c_riesgos ON cat_c_riesgos.codigo = cat_c_clientes.codigo_clasificacion_riesgo
									WHERE cA.codigo_cliente = '$codigoCliente'";
				// Ejecutamos el Query.
					$consulta = $dblink -> query($query);
				// color por defecto para las filas
					$colorFila = "#ABEBC6";
				if($consulta -> rowCount() != 0){
					$mensajeError = "Alertas Encontradas.";
					$respuestaOK = true;
					$num = 0;
					// convertimos el objeto
					while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
					{
						// variablesç
						$id_ = trim($listado['id_']);
						$codigo_empleado = trim($listado['codigo_cliente']);
						$nombre_alerta = trim($listado['alerta_descripcion']);
						$nombre_riesgo = trim($listado['riesgo_descripcion']);
						$color_riesgo = trim($listado['color']);
						$num++;
						// cambiar el color de la fila.
						//	verde, rojo y amarillo.
							switch ($color_riesgo) {
								case 'rojo':
									$colorFila = "#F1948A";
									break;
								case 'amarillo':
									$colorFila = "#F9E79F";
									break;
								default:
									# code...
									break;
							}
					// variables Json
						$contenidoOK .= "<tr style='background: $colorFila'>
							<td><input type=checkbox class=case name=chk$id_ id=chk$id_>
							<td>$num
							<td>$id_
							<td>$nombre_alerta
							<td>$nombre_riesgo
							<td><a data-accion=eliminarAlertas class='btn btn-xs btn-warning' data-toggle='tooltip' data-placement='top' title='Eliminar' href=$id_><i class='fas fa-trash'></i></a>"
						;
					}
				}else{
					$mensajeError = "No se encontraron Alertas...";
					$respuestaOK = false;
				}
			break;
			case 'guardarAlertas':
				// consultar el registro antes de agregarlo.
					// Armamos el query y iniciamos variables.
					$codigoEmpleado = $_POST['codigoCliente'];
					$codigoAlerta = $_POST['lstClienteAlertas'];
						$query = "SELECT * FROM clientes_alertas WHERE codigo_cliente = '$codigoEmpleado' and codigo_alerta = '$codigoAlerta' ";
				// Ejecutamos el Query.
				$consulta = $dblink -> query($query);
				//
				if($consulta -> rowCount() != 0){
					$respuestaOK = false;
					$contenidoOK = "Este registro ya Existe";
					$mensajeError = "Alerta ya Existe.";
				}else{
				// proceso para grabar el registro
					$query = "INSERT INTO clientes_alertas (codigo_cliente, codigo_alerta) 
						VALUES ('$codigoEmpleado','$codigoAlerta')";
				// Ejecutamos el Query.
				$consulta = $dblink -> query($query);
					$respuestaOK = true;
					$contenidoOK = "Registro Agregado";
					$mensajeError = "Registro Guardado.";
				}
			break;
			case 'eliminarAlertas':
				// Armamos el query
				$id_ = $_REQUEST["id_"];
					$query = "DELETE FROM clientes_alertas WHERE id_ = '$id_'";
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
if($CodigoAlertas == "BuscarAlertas" || $CodigoAlertas == "guardarAlertas" || $CodigoAlertas == "eliminarAlertas")
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