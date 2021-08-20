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
$fila_array = 0;
$datos = array();
$arreglo = array("id_proveedores","codigo","nombres","nombre_empresa");
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);
    
// Incluimos el archivo de funciones y conexión a la base de datos
include($path_root."/sistema_facturacion/includes/funciones.php");
include($path_root."/sistema_facturacion/includes/mainFunctions_conexion.php");

// Validar conexión con la base de datos
if($errorDbConexion == false){
	// Validamos qe existan las variables post
	if(isset($_POST) && !empty($_POST)){
		if(!empty($_POST['accion_proveedor_buscar'])){
			$_POST['accion'] = $_POST['accion_proveedor_buscar'];
		}
		// Verificamos las variables de acción
		switch ($_POST['accion']) {
		case 'GenerarCodigoNuevo':
				$ann_ = substr(trim($_POST['ann']),2,2);	// Año en Curso. pasar a dos digitos.

				$query = "SELECT id_proveedores, codigo, substring(codigo from 1 for 3)::int as codigo_empleado_numero_entero,substring(codigo from 1 for 3) as codigo_empleado,
							substring(codigo from 4 for 2) as codigo_empleado_ann, nombre, nombre_empresa FROM proveedores WHERE substring(codigo from 4 for 2) = '$ann_'
							ORDER BY codigo_empleado_numero_entero DESC LIMIT 1";
				// Ejecutamos el Query.
				$consulta = $dblink -> query($query);
				// Verificar si existen registros.
				if($consulta -> rowCount() != 0){
					$respuestaOK = true;
					// convertimos el objeto
					while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
					{
						$codigo_entero = trim($listado['codigo_empleado_numero_entero']) + 1;
						$codigo_string = (string) $codigo_entero;
						$codigo_nuevo = codigos_nuevos_proveedor($codigo_string);
						// Generar el Código
						$codigo_nuevo = $codigo_nuevo . $ann_;
						// retornar variable que contendrá el nuevo código.
						$datos[$fila_array]["codigo_nuevo"] = $codigo_nuevo;
						
					}
					$mensajeError = "Si Registro";
				}
				else{
						// Generar el Código
						$codigo_nuevo = "001" . $ann_;
						// retornar variable que contendrá el nuevo código.
						$datos[$fila_array]["codigo_nuevo"] = $codigo_nuevo;
						
					$respuestaOK = true;
					$contenidoOK = '';
					$mensajeError =  'No Registro';
				}
			break;
		
		case 'BuscarTodos':
				// Armamos el query.
				$query = "SELECT id_proveedores, codigo, nombre_empresa, nombre, telefono_uno, telefono_dos FROM proveedores ORDER BY nombre_empresa ASC";
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
				}
			break;			
            
            case 'BuscarProveedor':
				// Armamos el query.
				$query = "SELECT id_proveedores, codigo, nombre_empresa FROM proveedores ORDER BY nombre_empresa ASC";
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
				}
			break;
        
			case 'GuardarRegistro':
				$nombres = pg_escape_string(trim($_POST['txtNombre']));
				$nombre_empresa = pg_escape_string(trim($_POST['txtNombreEmpresa']));
				$direccion = pg_escape_string(trim($_POST['txtDireccion']));
				$telefono_residencia = trim($_POST['txtTelefonoResidencia']);
				$telefono_dos = trim($_POST['txtTelefonoOficina']);
				$telefono_celular = trim($_POST['txtTelefonoCelular']);
	
				$nit = trim($_POST['txtNit']);
                $dui = trim($_POST['txtDui']);
				$numero_registro = trim($_POST['txtNumeroRegistro']);
				$giro = pg_escape_string(trim($_POST['txtGiro']));
				$codigo_departamento = trim($_POST['lstDepartamento']);
				$codigo_municipio = trim($_POST['lstMunicipio']);
				
				$codigo_estatus = trim($_POST['lstEstatusProveedor']);
				$codigo = trim($_POST['txtCodigoProveedor']);
			
				$fecha_creacion_proveedor = trim($_POST['txtFechaCreacionProveedor']);
				//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                //  VERIFICAR EL CODIGO ANTES DE GUARDARLO.
                //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                    $ann_ = substr(trim($_POST['ann']),2,2);	// Año en Curso. pasar a dos digitos.
    
                    $query = "SELECT id_proveedores, codigo, substring(codigo from 1 for 3)::int as codigo_empleado_numero_entero,substring(codigo from 1 for 3) as codigo_empleado,
                                substring(codigo from 4 for 2) as codigo_empleado_ann, nombre, nombre_empresa FROM proveedores WHERE substring(codigo from 4 for 2) = '$ann_'
                                ORDER BY codigo_empleado_numero_entero DESC LIMIT 1";
                    // Ejecutamos el Query.
                    $consulta = $dblink -> query($query);
                    // Verificar si existen registros.
                    if($consulta -> rowCount() != 0){
                        // convertimos el objeto
                        while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                        {
                            $codigo_entero = trim($listado['codigo_empleado_numero_entero']) + 1;
                            $codigo_string = (string) $codigo_entero;
                            $codigo_nuevo = codigos_nuevos_proveedor($codigo_string);
                            // Generar el Código
                            $codigo_nuevo = $codigo_nuevo . $ann_;
                            $codigo = $codigo_nuevo;
                        }
                    }
                    else{
                            // Generar el Código
                            $codigo = "001" . $ann_;
                    }                
                //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                // Armar query.
                //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
				$query = "INSERT INTO proveedores (nombre, nombre_empresa, direccion, telefono_uno, telefono_dos, telefono_celular, codigo, nit, codigo_departamento, codigo_municipio,
								numero_registro, giro, codigo_estatus, fecha_creacion, dui)
						VALUES ('$nombres','$nombre_empresa','$direccion','$telefono_residencia','$telefono_dos','$telefono_celular','$codigo','$nit','$codigo_departamento','$codigo_municipio',
								'$numero_registro','$giro','$codigo_estatus','$fecha_creacion_proveedor','$dui')";

				// Ejecutamos el Query.
				$consulta = $dblink -> query($query);
				// Verificar la consulta
				if($consulta == true){
					$respuestaOK = true;
					$contenidoOK = $query;
					$mensajeError =  'Si Registro';
				}else{
					$respuestaOK = false;
					$contenidoOK = $query;
					$mensajeError =  'No Registro';					
				}				
			break;
		
			case 'EditarRegistro':
				$id_ = trim($_POST['id_']);
				// Armar Query.
				$query = "SELECT id_proveedores, codigo, nombre, nombre_empresa, direccion, telefono_uno, telefono_dos,
						numero_registro, nit, telefono_celular, codigo_estatus, numero_registro, giro, telefono_celular,
						codigo_departamento, codigo_municipio, fecha_creacion, dui
					FROM proveedores
					WHERE id_proveedores = '$id_'
					";
				// Ejecutamos el Query.
				$consulta = $dblink -> query($query);
				// Inicializando el array
				   //$datos=array(); $fila_array = 0;
				// Recorriendo la Tabla con PDO::
					$num = 1;
					    if($consulta -> rowCount() != 0){		
						while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
						  {
						      // recopilar los valores de los campos.
						      $id_proveedores = trim($listado['id_proveedores']);
						      $codigo = trim($listado['codigo']);
							  $nombre = trim($listado['nombre']);
							  $nombre_empresa = trim($listado['nombre_empresa']);
							  $direccion = (trim($listado['direccion']));
							  $telefono_uno = trim($listado['telefono_uno']);
							  $telefono_dos = trim($listado['telefono_dos']);
							  $telefono_celular = trim($listado['telefono_celular']);
							  $numero_registro = trim($listado['numero_registro']);
							  $nit = trim($listado['nit']);
                              $dui = trim($listado['dui']);
							  $giro = trim($listado['giro']);
							  $codigo_departamento = trim($listado['codigo_departamento']);
							  $codigo_municipio = trim($listado['codigo_municipio']);
							  $codigo_estatus = trim($listado['codigo_estatus']);						  
						      $fecha_creacion_proveedor = trim($listado['fecha_creacion']);
                              // pasar a la matriz.
						      $datos[$fila_array]["id_proveedores"] = $id_proveedores;
							  $datos[$fila_array]["codigo"] = $codigo;
							  $datos[$fila_array]["nombre"] = $nombre;
							  $datos[$fila_array]["nombre_empresa"] = $nombre_empresa;
							  $datos[$fila_array]["codigo_departamento"] = $codigo_departamento;
							  $datos[$fila_array]["codigo_municipio"] = $codigo_municipio;
							  $datos[$fila_array]["telefono_residencia"] = $telefono_uno;
							  $datos[$fila_array]["telefono_dos"] = $telefono_dos;
							  $datos[$fila_array]["telefono_celular"] = $telefono_celular;
							  $datos[$fila_array]["nit"] = $nit;
                              $datos[$fila_array]["dui"] = $dui;
							  $datos[$fila_array]["numero_registro"] = $numero_registro;
							  $datos[$fila_array]["giro"] = $giro;
							  $datos[$fila_array]["direccion"] = $direccion;
                              $datos[$fila_array]["fecha_creacion_proveedor"] = $fecha_creacion_proveedor;
						   // Incrementar el valor del array.
						     $fila_array++; $num++;
						  }
					    }
					    else{
							$datos[$fila_array]["no_registros"] = '<tr><td> No se encontraron registros.</td>';
					    }
			break;
		
			case 'ActualizarRegistro':
				$id_ = trim($_POST['id_']);
				$codigo_estatus = trim($_POST['lstEstatusProveedor']);
				$codigo = trim($_POST['txtCodigoProveedor']);
				$nombres = pg_escape_string(trim($_POST['txtNombre']));
				$nombre_empresa = pg_escape_string(trim($_POST['txtNombreEmpresa']));
				$telefono_residencia = trim($_POST['txtTelefonoResidencia']);
				$telefono_dos = trim($_POST['txtTelefonoOficina']);
				$telefono_celular = trim($_POST['txtTelefonoCelular']);
				$codigo_departamento = trim($_POST['lstDepartamento']);
				$codigo_municipio = trim($_POST['lstMunicipio']);
				$nit = trim($_POST['txtNit']);
                $dui = trim($_POST['txtDui']);
				$numero_registro = trim($_POST['txtNumeroRegistro']);
				$giro = pg_escape_string(trim($_POST['txtGiro']));
				$direccion = pg_escape_string(trim($_POST['txtDireccion']));
                $fecha_creacion_proveedor = trim($_POST['txtFechaCreacionProveedor']);
								
				// Armar query para actualizar.
				$query = "UPDATE proveedores SET nombre = '$nombres', nombre_empresa = '$nombre_empresa', direccion = '$direccion', telefono_uno = '$telefono_residencia', telefono_dos = '$telefono_dos', telefono_celular = '$telefono_celular', codigo_estatus = '$codigo_estatus',
							nit = '$nit', dui = '$dui',numero_registro = '$numero_registro', giro = '$giro', codigo_departamento = '$codigo_departamento', codigo_municipio = '$codigo_municipio', fecha_creacion = '$fecha_creacion_proveedor'
						WHERE id_proveedores = '$id_'
				";
				// Ejecutamos el Query.
				$consulta = $dblink -> query($query);
				// Verificar la consulta
				if($consulta == true){
					$respuestaOK = true;
					$contenidoOK = $query;
					$mensajeError =  'Si Registro';
				}else{
					$respuestaOK = false;
					$contenidoOK = $query;
					$mensajeError =  'No Registro';					
				}
			break;
		
			case 'EliminarRegistro':
				$id_ = trim($_POST['id_']);
                $codigo_proveedor = trim($_POST['codigo_proveedor']);
                $query_proveedor = "SELECT * FROM facturas_compras WHERE codigo_proveedor = '$codigo_proveedor' LIMIT 1";
                // Ejecutamos el Query.
                    $consulta_proveedor = $dblink -> query($query_proveedor);				
                        if($consulta_proveedor -> rowCount() > 0){
                            $respuestaOK = false;
                            $contenidoOK = $query_proveedor;
                            $mensajeError = "No Eliminar";
                            }
                        else{                
                            // Armar query.
                            $query = "DELETE FROM proveedores WHERE id_proveedores = '$id_'";
                            // Ejecutamos el Query.
                            $consulta = $dblink -> query($query);				
                            // Verificar la consulta
                            if($consulta == true){
                                $respuestaOK = true;
                                $contenidoOK = $query;
                                $mensajeError =  'Si Registro';
                            }else{
                                $respuestaOK = false;
                                $contenidoOK = $query;
                                $mensajeError =  'No Registro';					
                            }
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
	if($_POST["accion"] === "BuscarTodos" or $_POST["accion"] === "BuscarTodosCodigo" or $_POST["accion"] === "BuscarProveedor"){
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