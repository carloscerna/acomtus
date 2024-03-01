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
	if(isset($_REQUEST) && !empty($_REQUEST)){
		if(!empty($_REQUEST['accion_buscar'])){
			$_REQUEST['accion'] = $_REQUEST['accion_buscar'];
		}
		// Verificamos las variables de acci�n
		switch ($_REQUEST['accion']) {
		case 'BuscarPorId':
			$id_x = trim($_REQUEST['id_x']);
				// Armamos el query.
				$query = "SELECT tc.id_, tc.codigo_tipo_transporte, tc.numero_equipo, tc.numero_placa, tc.descripcion, tc.codigo_estatus,
                            cat_tt.descripcion as nombre_tipo_transporte,
							tc.foto_transporte, tc.foto_tarjeta_frente, tc.foto_tarjeta_vuelto,
							tc.nombre_propietario, tc.año_placa, tc.dui, tc.codigo_departamento, tc.codigo_municipio, tc.vencimiento, tc.emision,
							tc.año, tc.marca, tc.modelo, tc.capacidad, tc.tipo, tc.clase, tc.dominio_ajeno, tc.en_calidad, tc.color, tc.numero_chasis,
							tc.numero_motor, tc.numero_vin
                            FROM transporte_colectivo tc
                            INNER JOIN catalogo_tipo_transporte cat_tt ON cat_tt.id_ = tc.codigo_tipo_transporte
                                WHERE tc.id_ = " . $id_x
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
                            $descripcion = trim($listado['descripcion']);
                            $numero_placa = trim($listado['numero_placa']);
                            $numero_equipo = trim($listado['numero_equipo']);
							$codigo_tipo_transporte = trim($listado['codigo_tipo_transporte']);
							$nombre_tipo_transporte = trim($listado['nombre_tipo_transporte']);
							$codigo_estatus = trim($listado['codigo_estatus']);
							$foto_transporte = trim($listado['foto_transporte']);
							$foto_tarjeta_frente = trim($listado['foto_tarjeta_frente']);
							$foto_tarjeta_vuelto = trim($listado['foto_tarjeta_vuelto']);
							$nombre_propietario = trim($listado['nombre_propietario']);
							$año_placa = trim($listado['año_placa']);
							$dui = trim($listado['dui']);
							$codigo_departamento = trim($listado['codigo_departamento']);
							$codigo_municipio = trim($listado['codigo_municipio']);
							$vencimiento = trim($listado['vencimiento']);
							$emision = trim($listado['emision']);
							//
							$year = trim($listado['año']);
							$marca = trim($listado['marca']);
							$modelo = trim($listado['modelo']);
							$capacidad = trim($listado['capacidad']);
							$tipo = trim($listado['tipo']);
							$clase = trim($listado['clase']);
							$dominioAjeno = trim($listado['dominio_ajeno']);
							$enCalidad = trim($listado['en_calidad']);
							$color = trim($listado['color']);
							$NumeroChasis = trim($listado['numero_chasis']);
							$NumeroMotor = trim($listado['numero_motor']);
							$NumeroVin = trim($listado['numero_vin']);
							//$ = trim($listado['']);
                        // Rellenando la array.
                            $datos[$fila_array]["descripcion"] = $descripcion;
                            $datos[$fila_array]["numero_placa"] = $numero_placa;
                            $datos[$fila_array]["numero_equipo"] = $numero_equipo;
							$datos[$fila_array]["codigo_tipo_transporte"] = $codigo_tipo_transporte;
							$datos[$fila_array]["nombre_tipo_transporte"] = $nombre_tipo_transporte;
							$datos[$fila_array]["codigo_estatus"] = $codigo_estatus;
							$datos[$fila_array]["foto_transporte"] = $foto_transporte;
							$datos[$fila_array]["foto_tarjeta_frente"] = $foto_tarjeta_frente;
							$datos[$fila_array]["foto_tarjeta_vuelto"] = $foto_tarjeta_vuelto;
							//
							$datos[$fila_array]["nombre_propietario"] = $nombre_propietario;
							$datos[$fila_array]["año_placa"] = $año_placa;
							$datos[$fila_array]["dui"] = $dui;
							$datos[$fila_array]["codigo_departamento"] = $codigo_departamento;
							$datos[$fila_array]["codigo_municipio"] = $codigo_municipio;
							$datos[$fila_array]["vencimiento"] = $vencimiento;
							$datos[$fila_array]["emision"] = $emision;
							//							
							$datos[$fila_array]["año"] = $year;
							$datos[$fila_array]["marca"] = $marca;
							$datos[$fila_array]["modelo"] = $modelo;
							$datos[$fila_array]["capacidad"] = $capacidad;
							$datos[$fila_array]["tipo"] = $tipo;
							$datos[$fila_array]["clase"] = $clase;
							$datos[$fila_array]["dominio_ajeno"] = $dominioAjeno;
							$datos[$fila_array]["en_calidad"] = $enCalidad;
							$datos[$fila_array]["color"] = $color;
							$datos[$fila_array]["numero_chasis"] = $NumeroChasis;
							$datos[$fila_array]["numero_motor"] = $NumeroMotor;
							$datos[$fila_array]["numero_vin"] = $NumeroVin;
							//$datos[$fila_array][""] = $;
					}
					$mensajeError = "Si Registro";
				}
				else{
					$respuestaOK = true;
					$contenidoOK = '';
					$mensajeError =  'No Registro';
				}
			break;
            case 'BuscarTipoTransporte':
                $codigo_tipo_transporte = trim($_REQUEST['codigo_tipo_transporte']);
                    // Armamos el query.
                    $query = "SELECT numero_equipo FROM transporte_colectivo WHERE codigo_tipo_transporte = '$codigo_tipo_transporte' ORDER BY numero_equipo DESC LIMIT 1";
                    // Ejecutamos el Query.
                    $consulta = $dblink -> query($query);
                    
                    if($consulta == true){
                        // Validar si hay registros.
                        if($consulta -> rowCount() != 0){
                            $respuestaOK = true;
                            $num = 0;
                            // convertimos el objeto
                            while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                            {
                                // Nombres de los campos de la tabla.
                                    $numero_equipo = intval($listado['numero_equipo']) + 1;
                                // Rellenando la array.
                                    $datos[$fila_array]["numero_equipo"] = $numero_equipo;
                            }
                            $mensajeError = "Si Registro";
                        }
                        else{
                            $numero_equipo = 1;
                            $datos[$fila_array]["numero_equipo"] = $numero_equipo;

                            $respuestaOK = true;
                            $contenidoOK = '';
                            $mensajeError =  'No Registro';
                        }
                    }else{
                            $numero_equipo = 1;
                            $datos[$fila_array]["numero_equipo"] = $numero_equipo;
                    }
                break;
			case 'AgregarNuevo':		
				// armar variables.
				// TABS-1
					$codigo_estatus = trim($_REQUEST['lstEstatus']);
                    $codigo_tipo_transporte = trim($_REQUEST['lstTipoTransporte']);
                    $numero_equipo = intval($_REQUEST['txtNumeroEquipo']);
                    $numero_placa = trim($_REQUEST['txtNumeroPlaca']);
                    $descripcion = trim($_REQUEST['txtDescripcion']);
				//
					$nombre_propietario = trim($_REQUEST['txtNombrePropietario']);
					$YearPlaca = trim($_REQUEST['txtYearPlaca']);
					$dui = trim($_REQUEST['txtDui']);
					$codigo_departamento = trim($_REQUEST['lstdepartamento']);
					$codigo_municipio = trim($_REQUEST['lstmunicipio']);
					$vencimiento = trim($_REQUEST['txtVencimiento']);
					$emision = trim($_REQUEST['txtEmision']);
				//
					$year = trim($_REQUEST['txtYear']);
					$marca = trim($_REQUEST['txtMarca']);
					$modelo = trim($_REQUEST['txtModelo']);
					$capacidad = trim($_REQUEST['txtCapacidad']);
					$tipo = trim($_REQUEST['txtTipo']);
					$clase = trim($_REQUEST['txtClase']);
					$dominioAjeno = trim($_REQUEST['txtDominioAjeno']);
					$enCalidad = trim($_REQUEST['txtEnCalidad']);
					$color = trim($_REQUEST['txtColor']);
					$NumeroChasis = trim($_REQUEST['txtNumeroChasis']);
					$NumeroMotor = trim($_REQUEST['txtNumeroMotor']);
					$NumeroVin = trim($_REQUEST['txtNumeroVin']);
					//$ = trim($_REQUEST['']);
                    // COMPROBAR SI EL REGISTRO YA EXISTE (TIPOTRANSPORTE Y NUMERO EQUIPO)
                        $query_v = "SELECT * FROM transporte_colectivo WHERE codigo_tipo_transporte = '$codigo_tipo_transporte' and numero_equipo = '$numero_equipo'" ;
                            $resultadoQuery = $dblink -> query($query_v); 
                            if($resultadoQuery -> rowCount() != 0){             
                                $mensajeError = "Ya Existe este Registro.";
                                    break;
                            }
                    ///////////////////////////////////////////////////////////////////////////////////////
					// Query
					$query = "INSERT INTO transporte_colectivo (descripcion, codigo_tipo_transporte, numero_equipo, numero_placa, codigo_estatus
							,nombre_propietario, año_placa, dui, codigo_departamento, codigo_municipio, vencimiento, emision
							,año, marca, modelo, capacidad, tipo, clase, dominio_ajeno, en_calidad, color, numero_chasis, numero_motor, numero_vin
							)
						VALUES ('$descripcion','$codigo_tipo_transporte','$numero_equipo','$numero_placa','$codigo_estatus',
							'$nombre_propietario','$YearPlaca','$dui','$codigo_departamento','$codigo_municipio','$vencimiento','$emision',
							'$year','$marca','$modelo','$capacidad','$tipo','$clase','$dominioAjeno','$enCalidad','$color','$NumeroChasis','$NumeroMotor','$NumeroVin'
							)";
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
                //$codigo_tipo_transporte = trim($_POST['lstTipoTransporte']);
                //$numero_equipo = intval($_POST['txtNumeroEquipo']);
                $numero_placa = trim($_REQUEST['txtNumeroPlaca']);
                $descripcion = trim($_REQUEST['txtDescripcion']);	
				$codigo_estatus = trim($_REQUEST['lstEstatus']);
				$Id_ = trim($_REQUEST['id_user']);
				//
				$nombre_propietario = trim($_REQUEST['txtNombrePropietario']);
				$YearPlaca = trim($_REQUEST['txtYearPlaca']);
				$dui = trim($_REQUEST['txtDui']);
				$codigo_departamento = trim($_REQUEST['lstdepartamento']);
				$codigo_municipio = trim($_REQUEST['lstmunicipio']);
				$vencimiento = trim($_REQUEST['txtVencimiento']);
				$emision = trim($_REQUEST['txtEmision']);
				//
				$year = trim($_REQUEST['txtYear']);
				$marca = trim($_REQUEST['txtMarca']);
				$modelo = trim($_REQUEST['txtModelo']);
				$capacidad = trim($_REQUEST['txtCapacidad']);
				$tipo = trim($_REQUEST['txtTipo']);
				$clase = trim($_REQUEST['txtClase']);
				$dominioAjeno = trim($_REQUEST['txtDominioAjeno']);
				$enCalidad = trim($_REQUEST['txtEnCalidad']);
				$color = trim($_REQUEST['txtColor']);
				$NumeroChasis = trim($_REQUEST['txtNumeroChasis']);
				$NumeroMotor = trim($_REQUEST['txtNumeroMotor']);
				$NumeroVin = trim($_REQUEST['txtNumeroVin']);
				//$ = trim($_REQUEST['']);

                // query de actulizar.
				$query_ = "UPDATE transporte_colectivo SET
							codigo_estatus = '$codigo_estatus',
							numero_placa = '$numero_placa',
							descripcion = '$descripcion',
							nombre_propietario = '$nombre_propietario',
							año_placa = '$YearPlaca',
							dui = '$dui',
							codigo_departamento = '$codigo_departamento',
							codigo_municipio = '$codigo_municipio',
							vencimiento = '$vencimiento',
							emision = '$emision',
							año = '$year',
							marca = '$marca',
							modelo = '$modelo',
							capacidad = '$capacidad',
							tipo = '$tipo',
							clase = '$clase',
							dominio_ajeno= '$dominioAjeno',
							en_calidad = '$enCalidad',
							color = '$color',
							numero_chasis = '$NumeroChasis',
							numero_motor = '$NumeroMotor',
							numero_vin= '$NumeroVin'
						WHERE id_ = '$Id_'
						";
					/*ID_,
					CODIGO_TIPO_TRANSPORTE,
					NUMERO_EQUIPO,
					NUMERO_PLACA,
					DESCRIPCION,
					"año_placa",
					DUI,
					CODIGO_DEPARTAMENTO,
					CODIGO_MUNICIPIO,
					VENCIMIENTO,
					EMISION,

					"año",
					MARCA,
					MODELO,
					CAPACIDAD,
					TIPO,
					CLASE,
					DOMINIO_AJENO,
					EN_CALIDAD,
					COLOR,
					NUMERO_CHASIS,
					NUMERO_MOTOR,
					NUMERO_VIN,

					CODIGO_ESTATUS,
					OBSERVACIONES,
					FOTO_TRANSPORTE,
					FOTO_TARJETA_FRENTE,
					FOTO_TARJETA_VUELTO
					*/
                /*$query_ = sprintf("UPDATE transporte_colectivo SET descripcion = '%s',  
						numero_placa = '%s', codigo_estatus =  '%s'
						WHERE id_ = %d",
                        $descripcion, $numero_placa, $codigo_estatus, $_POST['id_user']);	*/
                        
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
				$nombre = trim($_REQUEST['nombre']);
				// COMPARAR QUE ELUSUARIO ROOT NO PUEDA SER ELIMINADO.
				if($nombre == 'root'){
					$mensajeError = "El Usuario <b> root </b> no se puede Eliminar";
						break;
				}
				// Armamos el query
				$query = "DELETE FROM usuarios WHERE id_usuario = $_REQUEST[id_user]";

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
	$accion = $_REQUEST["accion"];
	if($accion === "" or $accion === "BuscarTodosCodigo"){
		echo json_encode($arreglo);	
	}elseif($accion === "BuscarTipoTransporte" or $accion === "GenerarCodigoNuevo" or $accion === "BuscarPorId"){
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