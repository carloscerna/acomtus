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
$listado = array("0","1","2","3","4","5","6","7");
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
		case 'BuscarTodos':
				// Armamos el query.
				$query = "SELECT tc.id_, tc.codigo_tipo_transporte, tc.numero_equipo, tc.numero_placa, tc.descripcion, tc.codigo_estatus,
							cat_tt.descripcion as nombre_tipo_transporte,
							tc.foto_transporte, tc.foto_tarjeta_frente, tc.foto_tarjeta_vuelto,
							tc.nombre_propietario, tc.año_placa, tc.dui, tc.codigo_departamento, tc.codigo_municipio, tc.vencimiento, tc.emision,
							tc.año, tc.marca, tc.modelo, tc.capacidad, tc.tipo, tc.clase, tc.dominio_ajeno, tc.en_calidad, tc.color, tc.numero_chasis,
							tc.numero_motor, tc.numero_vin,
							btrim(tc.numero_equipo || CAST('-' AS VARCHAR) || tc.numero_placa || CAST('-' AS VARCHAR) || tc.año_placa) AS equipo_placa_year
							FROM transporte_colectivo tc
							INNER JOIN catalogo_tipo_transporte cat_tt ON cat_tt.id_ = tc.codigo_tipo_transporte
								ORDER BY tc.id_ ASC
						";
				// Ejecutamos el Query.
				$consulta = $dblink -> query($query);
				// Validar si hay registros.
				if($consulta -> rowCount() != 0){
					$respuestaOK = true;
					$num = 0;
					// convertimos el objeto
					while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
					{
						$arreglo["data"][] = $listado;						
					}
					$mensajeError = "Si Registro";
				}
				else{
					$respuestaOK = true;
					$contenidoOK = '';
					$mensajeError =  'No Registro';
					$arreglo["data"][] = $listado;						
				}
			break;

			case 'eliminarA':
				// Armamos el query
				//$query = "DELETE FROM alumno WHERE id_alumno = $_POST[id_user]";

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
				$mensajeError = 'Esta acci�n no se encuentra disponible';
			break;
		}
	}
	else{
		$mensajeError = 'No se puede ejecutar la aplicaci�n';}
}
else{
	$mensajeError = 'No se puede establecer conexi�n con la base de datos';}
// Salida de la Array con JSON.
	if($_POST["accion"] === "BuscarTodos" or $_POST["accion"] === "BuscarTodosCodigo"){
		echo json_encode($arreglo);	
	}elseif($_POST["accion"] === "BuscarCodigo" or $_POST["accion"] === "GenerarCodigoNuevo" or $_POST["accion"] === "EditarRegistro"){
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