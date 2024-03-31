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
$NumeroMes = array("1","2","3","4","5","6","7","8","9","10","11","12");
$meses = array("Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
$fecha_graficos = array();
$ingresos = array();
$ingresoPorMes = array();
$ingresoPorDia = 0;
$TotalEmpleados = 0;
$GraficoYear = 0;
$TotalActivos = 0;
$TotalActivosMasculino = 0;
$TotalActivosFemenino = 0;
$nombre_departamento_empresa = array();
$totalActivos = array();
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);
// Incluimos el archivo de funciones y conexi�n a la base de datos
include($path_root."/acomtus/includes/mainFunctions_conexion.php");
include($path_root."/acomtus/includes/funciones.php");
// Validar conexi�n con la base de datos
if($errorDbConexion == false){
	// Validamos qe existan las variables post
	if(isset($_POST) && !empty($_POST)){
		if(!empty($_POST['accion_buscar'])){
			$_POST['accion'] = $_POST['accion_buscar'];
		}
		// Verificamos las variables de acci�n
		switch ($_POST['accion']) {
		case 'BuscarProduccionDiaria':
            $FechaDesde = $_REQUEST["FechaDesdePD"];
            $FechaHasta = $_REQUEST["FechaHastaPD"];
			$mostrarDias = $_REQUEST["mostrarDias"];
			$fechaHoy = cambiaf_a_normal($_REQUEST["FechaHoy"]);
			$fechaHoy = explode("/", $fechaHoy);
			$GraficoYear = $fechaHoy[2];
			
            	//	Estilos
                $estilo_l = 'style="padding: 0px; font-size: large; color:black; text-align: left;"';
                $estilo_c = 'style="padding: 0px; font-size: large; color:black; text-align: center;"';
                $estilo_r = 'style="padding: 0px; font-size: medium; color:black; text-align: right;"';                                                                         
				// Armamos el query. validar dís de la
				if($mostrarDias == 1){
					$query = "SELECT * FROM produccion_diaria WHERE fecha >= '$FechaDesde' and fecha <= '$FechaHasta'
					ORDER BY fecha";
				}else{
					$query = "SELECT * FROM produccion_diaria ORDER BY fecha desc limit 7";
				}

				// Ejecutamos el Query.
				$consulta = $dblink -> query($query);
				// Validar si hay registros.
				if($consulta -> rowCount() != 0){
					$respuestaOK = true;
					$num = 0;
					// convertimos el objeto
					while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
					{
                        $fecha = cambiaf_a_normal(trim($listado['fecha']));
                        $dolares = number_format(trim($listado['total_dolares']),0,".",",");
                        $colones = number_format(trim($listado['total_colones']),0,".",",");
						// datos para el grafico
                        $fecha_graficos[] = cambiaf_a_normal(trim($listado['fecha']));
						$ingresos[] = ($listado['total_dolares']);
						// datos para la tabla.
                        $contenidoOK .= "<tr>
							<td $estilo_c>$fecha
							<td $estilo_c>$ $dolares
							<td $estilo_c>&#162 $colones"
                        ;				
					}
					$mensajeError = "Si Registro";
				}
				else{
					$respuestaOK = true;
					$contenidoOK = '';
					$mensajeError =  'No Registro';					
				}
				// crear matriz para el calculo del ingreso por mes y año
				for ($i=0; $i < count($NumeroMes) ; $i++) { 
					$query_consulta_ = "SELECT SUM(total_dolares) as ingresoPorMes FROM produccion_diaria
										WHERE EXTRACT(MONTH FROM fecha) = '$NumeroMes[$i]' and EXTRACT(YEAR FROM fecha) = '$GraficoYear'";
						// Ejecutamos el Query.
						$consulta = $dblink -> query($query_consulta_);
						// Validar si hay registros.
						if($consulta -> rowCount() != 0){
							// convertimos el objeto
							while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
							{
								$ingresoPorMes[] = $listado['ingresopormes'];
							}
						}	// fin del if.
					// datos para el gráfico.
				}	// fin del for.
			break;
			case 'BuscarTodosPorId':
				$Id_ = $_REQUEST["Id_"];
				$query_ = "SELECT pro.id_ as codigo_produccion, to_char(pro.fecha,'dd/mm/yyyy') as fecha_, pro.codigo_inventario_tiquete, 
					trim(tiq_color.descripcion) as descripcion_tiquete,
					pro.codigo_ruta, pro.total_ingreso,
					inv_tiq.precio_publico, inv_tiq.existencia, inv_tiq.tiraje,
					ROUND(pro.total_ingreso/inv_tiq.precio_publico,0) as total_tiquete_utilizados,
					ROUND(sum(pro.total_ingreso)/inv_tiq.precio_publico,0) as sumas
						FROM produccion pro 
							INNER JOIN inventario_tiquete inv_tiq ON inv_tiq.id_ = pro.codigo_inventario_tiquete
							INNER JOIN catalogo_tiquete_color tiq_color ON tiq_color.id_ = inv_tiq.codigo_tiquete_color
							WHERE pro.codigo_inventario_tiquete = '$Id_' and pro.codigo_estatus ='02'
							GROUP BY pro.id_, pro.fecha, pro.codigo_inventario_tiquete, pro.codigo_ruta, pro.total_ingreso,
							inv_tiq.precio_publico, inv_tiq.existencia, tiq_color.descripcion, inv_tiq.tiraje
							ORDER BY pro.id_";
				// Ejecutamos el query
				$consulta = $dblink -> query($query_);              
				// obtener el último dato en este caso el Id_
					// Validar si hay registros.
				if($consulta -> rowCount() != 0){  
					while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
					{
						$arreglo["data"][] = $listado;	
					}
				}
				break;
				case "actualizarExistencia":
					$Id_ = $_REQUEST["Id_"];
					$totalTiquetes = $_REQUEST["totalTiquetes"];
					$query_actualizar = "UPDATE inventario_tiquete SET existencia = (tiraje - $totalTiquetes) WHERE id_ = '$Id_'";
					// Ejecutamos el query
						$consulta = $dblink -> query($query_actualizar);           
				break;
				case "BuscarEmpleados":
					$query_buscar = "SELECT p.codigo_estatus, cat_est.descripcion as descripcion_estatus, cat_gen.descripcion as descripcion_genero
								, count(*) FILTER (WHERE p.codigo_estatus = '01') AS activos
								, count(*) FILTER (WHERE p.codigo_estatus = '02') AS inactivos
								, count(*) FILTER  (WHERE codigo_genero = '01' and codigo_estatus = '01')AS masculino
								, count(*) FILTER  (WHERE codigo_genero = '02' and codigo_estatus = '01')AS femenino
									FROM   personal p
										INNER JOIN catalogo_estatus cat_est ON cat_est.codigo = p.codigo_estatus
										INNER JOIN catalogo_genero cat_gen ON cat_gen.codigo = p.codigo_genero
										GROUP  BY p.codigo_estatus, cat_est.descripcion, p.codigo_genero, cat_gen.descripcion
										ORDER BY p.codigo_estatus, p.codigo_genero;";
				// ejecutar consulta.
					$consulta = $dblink -> query($query_buscar);    
				// Validar si hay registros.
				$fila_array = 0;
				if($consulta -> rowCount() != 0){
					$respuestaOK = true;
					$num = 0;
					// convertimos el objeto
					while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
					{
						// Nombres de los campos de la tabla.
							$activos = trim($listado['activos']);
							$inactivos = trim($listado['inactivos']);
							$estatus = trim($listado['descripcion_estatus']);
							$genero = trim($listado['descripcion_genero']);
						// total empleados.
							$TotalEmpleados = $TotalEmpleados + ($activos + $inactivos);
						// total activos
							$TotalActivos = $TotalActivos + $activos;
						// total activos masculino
							if($estatus == "Activo" && $genero == "Masculino"){
								$TotalActivosMasculino = $activos;
							}
						// total activos femenino
							if($estatus == "Activo" && $genero == "Femenino"){
								$TotalActivosFemenino = $activos;
							}
					}
						// Rellenando la array.
						$datos[$fila_array]["TotalEmpleados"] = $TotalEmpleados;
						$datos[$fila_array]["TotalActivos"] = $TotalActivos;
						$datos[$fila_array]["TotalActivosMasculino"] = $TotalActivosMasculino;
						$datos[$fila_array]["TotalActivosFemenino"] = $TotalActivosFemenino;
					$mensajeError = "";
				}
				else{
					$respuestaOK = true;
					$contenidoOK = '';
					$mensajeError =  '';
				}      
				break;
			case "BuscarEmpleadosPorDepartamento":
				$respuestaOK = true;
				$contenidoOK = '';
				$mensajeError =  '';			
				//
				$query_departamento_empresa = "SELECT * FROM catalogo_departamento_empresa";
				// ejecutar consulta.
				$consulta = $dblink -> query($query_departamento_empresa);    
				// Validar si hay registros.
				$fila_array = 0; $codigo_departamento_empresa = array(); $nombre_departamento_empresa = array();
				if($consulta -> rowCount() != 0){
					// convertimos el objeto
					while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
					{
						$codigo_departamento_empresa[] = trim($listado["codigo"]);
						$nombre_departamento_empresa[] = trim($listado["descripcion"]);
					}
				}
				// recorrer for
				for ($i=0; $i < count($codigo_departamento_empresa); $i++) { 
					$query = "SELECT p.codigo_estatus, cat_dep.descripcion as descripcion_departamento_empresa, cat_est.descripcion as descripcion_estatus, cat_gen.descripcion as descripcion_genero
					, count(*) FILTER (WHERE p.codigo_estatus = '01') AS activos
					, count(*) FILTER (WHERE p.codigo_estatus = '02') AS inactivos
					, count(*) FILTER  (WHERE codigo_genero = '01' and codigo_estatus = '01')AS masculino
					, count(*) FILTER  (WHERE codigo_genero = '02' and codigo_estatus = '01')AS femenino
						FROM   personal p
						INNER JOIN catalogo_estatus cat_est ON cat_est.codigo = p.codigo_estatus
						INNER JOIN catalogo_genero cat_gen ON cat_gen.codigo = p.codigo_genero
						INNER JOIN catalogo_departamento_empresa cat_dep ON cat_dep.codigo = p.codigo_departamento_empresa
						WHERE p.codigo_departamento_empresa = '$codigo_departamento_empresa[$i]'
						GROUP  BY p.codigo_estatus, cat_est.descripcion, p.codigo_genero, cat_gen.descripcion, cat_dep.descripcion
						ORDER BY p.codigo_estatus, p.codigo_genero;";
					// ejecutar consulta.
					$consulta = $dblink -> query($query);    
					//
					$TotalActivosMasculino = 0; $TotalActivosFemenino = 0; $TotalActivos = 0; 
					if($consulta -> rowCount() != 0){
						// convertimos el objeto
						while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
						{
							// Nombres de los campos de la tabla.
							$activos = trim($listado['activos']);
							$inactivos = trim($listado['inactivos']);
							$estatus = trim($listado['descripcion_estatus']);
							$genero = trim($listado['descripcion_genero']);
							// total empleados.
								$TotalEmpleados = $TotalEmpleados + ($activos + $inactivos);
							// total activos
								$TotalActivos = $TotalActivos + $activos;
							// total activos masculino
								if($estatus == "Activo" && $genero == "Masculino"){
									$TotalActivosMasculino = $activos;
								}
							// total activos femenino
								if($estatus == "Activo" && $genero == "Femenino"){
									$TotalActivosFemenino = $activos;
								}
						}	// FIN DEL WHILE()
						// información para la matriz.
						$totalActivos[] = $TotalActivos;
						// tabla
						$contenidoOK .= "<tr>
							<td>
							<td>$nombre_departamento_empresa[$i]
							<td>$TotalActivosMasculino
							<td>$TotalActivosFemenino
							<td>$TotalActivos
							";			
					} // FIN DEL ROWCOUNT();	
				}	// FIN DEL FOR();
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
	if($_POST["accion"] === "BuscarTodos" or $_POST["accion"] === "BuscarTodosCodigo" or $_POST["accion"] == "BuscarTodosPorId"){
		echo json_encode($arreglo);	
	}elseif($_POST["accion"] === "BuscarCodigo" or $_POST["accion"] === "GenerarCodigoNuevo" or $_POST["accion"] === "EditarRegistro" or $_POST["accion"] === "actualizarExistencia"){
		echo json_encode($datos);
		}
	else{
		// Armamos array para convertir a JSON
		$salidaJson = array("respuesta" => $respuestaOK,
			"mensaje" => $mensajeError,
			"contenido" => $contenidoOK,
			"fecha" => $fecha_graficos,
			"ingresos" => $ingresos,
			"GraficoYear" => $GraficoYear,
			"NombreMes" => $meses,
			"IngresoPorMes" => $ingresoPorMes,
			"TotalEmpleados" => $TotalEmpleados,
			"TotalActivos" => $TotalActivos,
			"TotalActivosMasculino" => $TotalActivosMasculino,
			"TotalActivosFemenino" => $TotalActivosFemenino,
			"NombreDepartamentoEmpresa" => $nombre_departamento_empresa,
			"CantidadDepartamentoEmpresa" => $totalActivos
		);
		echo json_encode($salidaJson);
	}
?>