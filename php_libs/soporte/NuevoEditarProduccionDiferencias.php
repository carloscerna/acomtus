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
$fecha = "";
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
        case 'todos':
            $fecha = trim($_POST['fecha']);
            ListadoDiferencia();
        break;
		case 'BuscarPorId':
			$id_ = trim($_POST['id_']);
				// Armamos el query.
				$query = 'SELECT * FROM produccion_diferencias 
                            WHERE id_ = ' . $id_
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
							$nombre = trim($listado['descripcion']);
							$valor = trim($listado['valor']);
							$concepto = trim($listado['concepto']);
							$fecha = trim($listado['fecha']);
						// Rellenando la array.
							$datos[$fila_array]["descripcion"] = $nombre;
							$datos[$fila_array]["valor"] = $valor;
							$datos[$fila_array]["concepto"] = $concepto;
							$datos[$fila_array]["fecha"] = $fecha;
					}
					$datos[$fila_array]["mensaje"] = "Registro Encontrado, listo para Editar.";
					$datos[$fila_array]["respuesta"] = true;					
				}
				else{
					$datos[$fila_array]["respuesta"] = false;
					$mensajeError =  '';
				}
			break;

			case 'Agregar':		
				// armar variables.
                // TABS-1
                    $fecha = trim($_POST['fecha']);
					$nombre = trim($_POST['txtnombres']);
					$valor = trim($_POST['Valor']);
					$concepto = trim($_POST['concepto']);
					// Query
					$query = "INSERT INTO produccion_diferencias (fecha, descripcion, valor, concepto)
						VALUES ('$fecha','$nombre','$valor','$concepto')";
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
                    ListadoDiferencia();
			break;
			
			case 'EditarRegistro':
				$fecha = trim($_POST['fecha']);
                $id_ = trim($_POST['id_user']);
				$nombre = trim($_POST['txtnombres']);
				$valor = trim($_POST['Valor']);
				$concepto = trim($_POST['concepto']);	
					// QUERY UPDATE.
						$query_ = sprintf("UPDATE produccion_diferencias SET descripcion = '%s', valor = '%s', concepto = '%s'
						WHERE id_ = %d",
						$nombre, $valor, $concepto, $_POST['id_user']);	
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
					ListadoDiferencia();
			break;
		
            case 'Eliminar':
                $fecha = trim($_POST['fecha']);
				$id_ = trim($_POST['id_']);
				// Armamos el query
				$query = "DELETE FROM produccion_diferencias WHERE id_ = $_POST[id_]";

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
                ListadoDiferencia();
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
    
    function ListadoDiferencia(){
        global $dblink, $contenidoOK, $fecha, $respuestaOK, $mensajeError;
        $estilo_l = 'style="padding: 0px; font-size: large; color:blue; text-align: left;"';
        $estilo_c = 'style="padding: 0px; font-size: large; color:blue; text-align: center;"';
        $estilo_r = 'style="padding: 0px; font-size: large; color:blue; text-align: right;"';
        // consulta.
            $query_c = "SELECT * FROM produccion_diferencias
                WHERE fecha = '$fecha'
                    ORDER BY id_";
        // Ejecutamos el query
            $consulta = $dblink -> query($query_c);              
        // obtener el último dato en este caso el Id_
        if($consulta -> rowCount() != 0){
            while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
            {
                $id_ = trim($listado['id_']);		
                $nombre = trim($listado['descripcion']);
                $valor = trim($listado['valor']);		
                $concepto = trim($listado['concepto']);		
                //
                $contenidoOK .= "<tr>
                <td $estilo_l>$id_
                <td $estilo_l>$nombre
                <td $estilo_c>$valor
                <td $estilo_l>$concepto
                <td $estilo_l><a data-accion=EditarDiferencia data-toggle=tooltip data-placement=left title=Editar href=$id_><i class='fal fa-money-check-edit-alt fa-lg'></i></a>
                <td $estilo_r><a data-accion=EliminarDiferencia data-toggle=tooltip data-placement=left title=Eliminar href=$id_><i class='fad fa-trash-alt fa-lg'></i></a>"
                ;
            }		
            $respuestaOK = true;
			//$mensajeError = 'Si hay Registro(s).';
        }else{
            $respuestaOK = false;
			$mensajeError = 'No hay Registro(s).';
        }
    }
?>