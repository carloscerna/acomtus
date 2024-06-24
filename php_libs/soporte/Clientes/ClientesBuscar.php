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
				$query = "SELECT c.id_, c.codigo, btrim(c.nombres || CAST(' ' AS VARCHAR) || c.primer_apellido || CAST(' ' AS VARCHAR) || c.segundo_apellido) AS nombre_cliente, c.telefono_domicilio, c.telefono_celular,
                            to_char(c.fecha_nacimiento,'dd/mm/yyyy') as fecha_nacimiento, c.codigo_estatus
                                FROM clientes c
                                        ORDER BY c.id_ DESC, c.codigo_estatus ASC";
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
					$arreglo["data"][] = '{"sEcho":1,
											"sEcho":1,
											"iTotalRecords":"0",
											"iTotalDisplayRecords":"0",
											"aaData":[]
										}';						
				}
			break;

			case 'EliminarRegistro':
					// Armamos el query
					$id_ = $_POST["id_user"];
					$codigo_cliente = trim($_POST["codigo_cliente"]);
					$query = "DELETE FROM clientes WHERE id_ = '$id_'";
					$query_clientes_alertas = "DELETE FROM clientes_alertas WHERE codigo_cliente = '$codigo_cliente'";
	
	
					// Ejecutamos el query
						$count = $dblink -> exec($query);
						$count_1 = $dblink -> exec($query_clientes_alertas);
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