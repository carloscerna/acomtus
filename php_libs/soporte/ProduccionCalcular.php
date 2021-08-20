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
//
// Establecer formato para la fecha.
// 
date_default_timezone_set('America/El_Salvador');
setlocale(LC_TIME,'es_SV');
//	Hora Actual.
$hora_actual = date("h:i:s a"); 
// Inicializamos variables de mensajes y JSON
$respuestaOK = false;
$mensajeError = "No se puede ejecutar la aplicaci�n";
$contenidoOK = "";
$totalIngresoOK = 0;
$totalIngresoOKPantalla = 0;
$codigo_produccion = 0;
$CantidadtiqueteOK = 0;
$lista = "";
$arreglo = array();
$datos = array();
//$codigo_estatus = array('','Activo','Inactivo','Entregado','Devolución','Vendido');
$codigo_estatus = array('','01','02','03','04','05');
$listado = array("0","1","2","3","4","5","6","7");
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
		    case 'BuscarTodos':
				// Armamos el query.
				$query = "SELECT pro.id_, pro.fecha, pro.hora, to_char(pro.fecha,'dd/mm/yyyy') as fecha,
                        btrim(p.nombres  || CAST(' ' AS VARCHAR) || p.apellidos) as nombre_personal, p.codigo,
                        cat_t_c.numero_placa as numero_placa,
                        cat_j.descripcion as nombre_jornada,
                        cat_r.descripcion as nombre_ruta
                        FROM produccion pro
                            INNER JOIN personal p ON p.codigo = pro.codigo_personal
                            INNER JOIN transporte_colectivo cat_t_c ON cat_t_c.id_ = pro.codigo_transporte_colectivo
                            INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = pro.codigo_jornada
                            INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = pro.codigo_ruta
                            ORDER BY pro.fecha ASC
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
            ///////////////////////////////////
            // PROVIENEN DE main_produccion.js
            ///////////////////////////////////
            case 'BuscarProduccionPorFecha':
				// TABS-1 - tabla produccion.
                $fecha_produccion = trim($_POST['fecha']);
                $id_produccion = trim($_POST['IdProduccion']);
				// 	validar la fecha de la producción.
					$fechas = explode("-",$fecha_produccion);
					$dia = $fechas[2];
					$mes = $fechas[1];
					$ann = $fechas[0];
					
					if(checkdate($mes, $dia, $ann)){
					//echo "fecha valida";
					}else{
					//echo "fecha no válida";
						$mensajeError = "Fecha No Válida $dia . $mes . $ann";
                        	break;
					}
				// Armamos el query.
				    $query_p = "SELECT pro.id_, pro.fecha, pro.hora, to_char(pro.fecha,'dd/mm/yyyy') as fecha, pro.codigo_inventario_tiquete, pro.numero_vueltas,
                        cat_j.id_ as id_jornada, pro.codigo_jornada, pro.codigo_personal, pro.codigo_estatus, pro.codigo_transporte_colectivo, pro.codigo_ruta,
                        cat_r.descripcion as nombre_ruta,
                        it.precio_publico
                        FROM produccion pro
                            INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = pro.codigo_jornada
                            INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = pro.codigo_ruta
                            INNER JOIN inventario_tiquete it ON it.id_ = pro.codigo_inventario_tiquete
                                WHERE pro.fecha = '$fecha_produccion' and pro.id_ = '$id_produccion'
						";
                    // Ejecutamos el Query.
                        $consulta = $dblink -> query($query_p);
                    // Inicializando el array
                        $datos=array(); $fila_array = 0;
                        if($consulta -> rowCount() != 0){
                        // Recorriendo la Tabla con PDO::
                            $respuestaOK = true;
                            $mensajeError = "Producción Encontrada";                            
                            $datos[$fila_array]["respuesta"] = $respuestaOK;
                            $datos[$fila_array]["mensaje"] = $mensajeError;
                        //
                            while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                            {
                                // Nombres de los campos de la tabla.
                                    $id_produccion = $listado['id_']; 
                                    $jornada = $listado['id_jornada'];
                                    $ruta = trim($listado['nombre_ruta']); 
                                    $codigo_ruta = $listado['codigo_ruta']; 
                                    $codigo_jornada = trim($listado['codigo_jornada']);
                                    $codigo_personal = trim($listado['codigo_personal']); 
                                    $codigo_estatus = $listado['codigo_estatus']; 
                                    $codigo_transporte_colectivo = $listado['codigo_transporte_colectivo']; 
                                    $codigo_inventario_tiquete = $listado['codigo_inventario_tiquete'];
                                    $precio_publico = $listado['precio_publico'];
                                    $TotalVueltas = $listado['numero_vueltas'];
                                // Rellenando la array.
                                    $datos[$fila_array]["idproduccion"] = $id_produccion;
                                    $datos[$fila_array]["jornada"] = $jornada;
                                    $datos[$fila_array]["ruta"] = $ruta;
                                    $datos[$fila_array]["codigo_ruta"] = $codigo_ruta;
                                    $datos[$fila_array]["codigo_jornada"] = $codigo_jornada;
                                    $datos[$fila_array]["codigo_estatus"] = $codigo_estatus;
                                    $datos[$fila_array]["codigo_personal"] = $codigo_personal;
                                    $datos[$fila_array]["codigo_transporte_colectivo"] = $codigo_transporte_colectivo;
                                    $datos[$fila_array]["codigo_inventario_tiquete"] = $codigo_inventario_tiquete;
                                    $datos[$fila_array]["precio_publico"] = $precio_publico;
                                    $datos[$fila_array]["TotalVueltas"] = $TotalVueltas;
                                        $fila_array++;
                            }   // fin dle while.
                        }else{
                            $respuestaOK = false;
                            $mensajeError = "Producción No Encontrada";                            
                            // Rellenando la array.
                                $datos[$fila_array]["respuesta"] = $respuestaOK;
                                $datos[$fila_array]["mensaje"] = $mensajeError;
                        }   // IF DEL COUNT DE LA TABLA.
            break;            
            case 'BuscarProduccionPorId':
                $IdProduccion = $_POST['IdProduccion'];
                // Armamos el query.
                $query = "SELECT p.id_ AS id_produccion, p.fecha, p.codigo_inventario_tiquete, 
                    p.codigo_personal, 
                    p.codigo_transporte_colectivo, cat_ts.descripcion as nombre_serie, 
                    pa.id_ as id_produccion_asignado, pa.tiquete_desde, pa.tiquete_hasta, pa.total, 
                    btrim(cat_j.descripcion || CAST(': ' AS VARCHAR) || cat_j.hora_desde || CAST(' - ' AS VARCHAR) || cat_j.hora_hasta) as descripcion_jornada, 
                    cat_r.descripcion as descripcion_ruta 
                        FROM produccion p 
                            INNER JOIN produccion_asignado pa ON pa.codigo_produccion = p.id_ 
                            INNER JOIN inventario_tiquete it ON it.id_ = p.codigo_inventario_tiquete 
                            INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie 
                            INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = p.codigo_jornada 
                            INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = p.codigo_ruta 
                                WHERE pa.codigo_produccion = '$IdProduccion'";
                    // Ejecutamos el Query.
                    $consulta = $dblink -> query($query_p);
                    // Inicializando el array
                        $datos=array(); $fila_array = 0;
                    // Recorriendo la Tabla con PDO::
                        while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                        {
                        // Nombres de los campos de la tabla.
                            $jornada = $listado['descripcion_jornada']; 
                            $numero_placa = $listado['numero_placa']; 
                            $ruta = $listado['descripcion_ruta']; 
                            //$ = $listado['']; 
                        // Rellenando la array.
                            $datos[$fila_array]["jornada"] = $jornada;
                            $datos[$fila_array]["numero_placa"] = $numero_placa;
                            $datos[$fila_array]["ruta"] = $ruta;
                            //$datos[$fila_array][""] = $;
                                $fila_array++;
                        }
                break;
            case 'BuscarProduccionPorIdTabla':
                # code...
                $codigo_produccion = trim($_POST['IdProduccion']);
                $respuestaOK = true;
                $mensajeError = "Producción Encontrada";
                ListadoAsignado();
            break;
            case 'CalcularListadoTabla':
                $CantidadTiquete = 0; $TotalIngreso = 0; $numero_vueltas = 0;
                $id_todos = array();
                // TABS-1 - tabla produccion asignación 
                    $fila = trim($_POST['fila']);
                    $calcular_chk[] = $_POST['calcular_chk'];
                    $id_todos[] = $_POST['calcular_val'];
                    $true = true;
                    $codigo_personal = $_POST["codigo_personal"];
                    $codigo_transporte_colectivo = $_POST["codigo_transporte_colectivo"];
                    $CodigoInventarioTiquete = $_POST["CodigoInventarioTiquete"];
                    $numero_vueltas = $_POST['TotalVueltas'];
                    if(empty($numero_vueltas)){$numero_vueltas = 0;}
                    $codigo_ruta = $_POST['codigo_ruta'];
                // VARIABLES QUE CONTROLA EL BOORRADO DEL CORRELATIVO.
                    $CalculoFinal = trim($_POST['CalculoFinal']);
                // recorrer matriz con los datos del chk (val y true)
                    $fila = $fila - 1;
				// recorrer la array para extraer los datos.
				    for($i=0;$i<=$fila;$i++){
                        // Asignar valor a varinbles que las respectivas tablas.
                            $id_todos_ = $id_todos[0][$i];
                            $chk_ = $calcular_chk[0][$i];
                        if($chk_ == 'false')
                        {
                            // 	validar la fecha de la producción.
                            $partial = explode("#",$id_todos_);
                            $true = true;
                            // separar valores del array
                                $id_produccion_asignacion = $partial[0];
                                $id_produccion = $partial[1];
                                $codigo_produccion = $partial[1];
                                $tiquete_desde = $partial[2];
                                $tiquete_hasta = $partial[3];
                                $fecha = $partial[4];
                                $precio_publico = $partial[5];
                                $cantidad = $partial[6];
                                $total = $partial[7];
                                $tiquete_cola = $partial[8];
                            // procesar Talonario - tiquete cola.
                                if($tiquete_cola > 0){
                                    // Procesar vendios y precio total.
                                        $cantidad_tiquete_venta = $tiquete_cola - $tiquete_desde;
                                        $ActualizarNuevaVentaTalonario = $cantidad_tiquete_venta * $precio_publico;
                                    // Nuevo Rango
                                        //$tiquete_desde = $tiquete_cola;
                                    // Calcular cantidad de Tiquete para hacer afectado en el inventario.
                                        $CantidadTiquete = $CantidadTiquete + $cantidad_tiquete_venta;
                                    // Calcular total Ingreso.
                                        $TotalIngreso = $TotalIngreso + $ActualizarNuevaVentaTalonario;                          
                                }else{
                                    // Calcular cantidad de Tiquete para hacer afectado en el inventario.
                                        $CantidadTiquete = $CantidadTiquete + $cantidad;
                                    // Calcular total Ingreso.
                                        $TotalIngreso = $TotalIngreso + $total;                          
                                }

                            // ACTUALIZAR TABLA PRODUCCIÓN ASIGNADO.
                                if($tiquete_cola > 0){
                                    // ACTUALIZAR TIQEUTE HASTA.
                                        $tiquete_hasta_cola = $tiquete_cola - 1;
                                    $query_pa = "UPDATE produccion_asignado SET hora = '$hora_actual', procesado = '$true', codigo_estatus = '$codigo_estatus[5]', total = '$ActualizarNuevaVentaTalonario', cantidad = '$cantidad_tiquete_venta'
                                        WHERE codigo_produccion = '$id_produccion' and fecha = '$fecha' and id_ = '$id_produccion_asignacion'";
                                    // Ejecutamos el query_pc
                                        $consulta = $dblink -> query($query_pa);              
                                    // ACTULIAR TABLA PRODUCCION CORRELATIVO.
                                      $query_pc_u = "UPDATE produccion_correlativo SET procesado = '$true' 
                                            WHERE codigo_produccion_asignacion = '$id_produccion_asignacion' and fecha = '$fecha' and correlativo >= '$tiquete_desde' and correlativo <= '$tiquete_hasta_cola'";
                                    // Ejecutamos el query_pc
                                        $consulta = $dblink -> query($query_pc_u);              
                                    // ACTULIAR TABLA PRODUCCION CORRELATIVO. LA CUAL SE ELIMINA PORQUE ES DEVUELTO
                                        if($CalculoFinal == "FinCalculo"){
                                        // ACTULIAR TABLA PRODUCCION CORRELATIVO.
                                            $query_pc_u = "UPDATE produccion_correlativo SET procesado = 'false' 
                                                WHERE codigo_produccion_asignacion = '$id_produccion_asignacion' and fecha = '$fecha' and correlativo >= '$tiquete_cola' and correlativo <= '$tiquete_hasta'";
                                            /*$query_pc = "DELETE FROM  produccion_correlativo  
                                            WHERE codigo_produccion_asignacion = '$id_produccion_asignacion' and fecha = '$fecha' and correlativo >= '$tiquete_cola' and correlativo <= '$tiquete_hasta'";*/
                                            // Ejecutamos el query_pc
                                               // $consulta = $dblink -> query($query_pc_u);              
                                        }
                                    ///////////////////////////////////////////////////////////////////////////
                                }else{
                                    $query_pa = "UPDATE produccion_asignado SET hora = '$hora_actual', procesado = '$true', codigo_estatus = '$codigo_estatus[5]'
                                        WHERE codigo_produccion = '$id_produccion' and fecha = '$fecha' and tiquete_desde >= '$tiquete_desde' and tiquete_hasta <= '$tiquete_hasta'";
                                    // Ejecutamos el query_pa
                                        $consulta = $dblink -> query($query_pa);              
                                    // ACTULIAR TABLA PRODUCCION CORRELATIVO.
                                        $query_pc = "UPDATE produccion_correlativo SET procesado = '$true' 
                                        WHERE codigo_produccion_asignacion = '$id_produccion_asignacion' and fecha = '$fecha' and correlativo >= '$tiquete_desde' and correlativo <= '$tiquete_hasta'";
                                    // Ejecutamos el query_pc
                                       // $consulta = $dblink -> query($query_pc);              
                                }
                        }else{
                            // 	validar la fecha de la producción.
                            // CUANDO HA SIDO DEVUELTO
                            $partial = explode("#",$id_todos_);
                            $true = true;  // LA PRODUCCIÓN A QUEDADO COMO FALSE PORQUE SE HA DEVUELTO.
                            // separar valores del array
                                $id_produccion_asignacion = $partial[0];
                                $id_produccion = $partial[1];
                                $codigo_produccion = $partial[1];
                                $tiquete_desde = $partial[2];
                                $tiquete_hasta = $partial[3];
                                $fecha = $partial[4];
                                $precio_publico = $partial[5];
                                $cantidad = $partial[6];
                                $total = $partial[7];
                                $tiquete_cola = $partial[8];
                            // ACTUALIZAR TABLA PRODUCCIÓN ASIGNADO.
                                $query_pa = "UPDATE produccion_asignado SET hora = '$hora_actual', procesado = '$true', codigo_estatus = '$codigo_estatus[4]'
                                    WHERE codigo_produccion = '$id_produccion' and fecha = '$fecha' and tiquete_desde >= '$tiquete_desde' and tiquete_hasta <= '$tiquete_hasta'";
                            // Ejecutamos el query_pa
                                $consulta = $dblink -> query($query_pa);              
                            // ACTULIAR TABLA PRODUCCION CORRELATIVO. LA CUAL SE ELIMINA PORQUE ES DEVUELTO
                            if($CalculoFinal == "FinCalculo"){
                                        // ACTULIAR TABLA PRODUCCION CORRELATIVO.
                                        $query_pc_u = "UPDATE produccion_correlativo SET procesado = 'false' 
                                        WHERE codigo_produccion_asignacion = '$id_produccion_asignacion' and fecha = '$fecha' and correlativo >= '$tiquete_cola' and correlativo <= '$tiquete_hasta'";
                                    /*$query_pc = "DELETE FROM  produccion_correlativo  
                                    WHERE codigo_produccion_asignacion = '$id_produccion_asignacion' and fecha = '$fecha' and correlativo >= '$tiquete_cola' and correlativo <= '$tiquete_hasta'";*/
                                    // Ejecutamos el query_pc
                                        $consulta = $dblink -> query($query_pc_u);              
                                /*$query_pc = "DELETE FROM  produccion_correlativo  
                                WHERE codigo_produccion_asignacion = '$id_produccion_asignacion' and fecha = '$fecha' and correlativo >= '$tiquete_cola' and correlativo <= '$tiquete_hasta'";
                                // Ejecutamos el query_pc
                                    $consulta = $dblink -> query($query_pc);              */
                            }
                        ///////////////////////////////////////////////////////////////////////////
                        }
                    }   // FIN DLE FOR.
                    // DATA PARA ACTUALIZAR LA EXISTENCIA.
                    // ACTUALIZAR LA TABLA PRODUCCIÓN.
                       $query_p = "UPDATE produccion SET codigo_personal = '$codigo_personal', codigo_transporte_colectivo = '$codigo_transporte_colectivo', total_ingreso = '$TotalIngreso', codigo_estatus = '02', numero_vueltas = '$numero_vueltas', codigo_ruta = '$codigo_ruta'
                            WHERE id_ = '$id_produccion'";
                    // Ejecutamos el query_pc
                        $consulta = $dblink -> query($query_p);
                    // validar
                    if($CalculoFinal == "FinCalculo"){
                        if($chk_ == 'false')
                        {
                            // ACTUALIZAR LA TABla inventario tiquete.
                                $query_it = "UPDATE inventario_tiquete SET existencia = (existencia - $CantidadTiquete)
                                    WHERE id_ = '$CodigoInventarioTiquete'";
                            // Ejecutamos el query_pc
                                $consulta = $dblink -> query($query_it);
                        }else{
                            // ACTUALIZAR LA TABla inventario tiquete.
                                $query_it = "UPDATE inventario_tiquete SET existencia = (existencia + $CantidadTiquete)
                                    WHERE id_ = '$CodigoInventarioTiquete'";
                            // Ejecutamos el query_pc
                                $consulta = $dblink -> query($query_it);
                        }
                    }
                    // Ver el listado produccion asigando.
                        $respuestaOK = true;
                        //$mensajeError = "Producción Calculada...";
                    // Llamar a la tabla
                        ListadoAsignado();
            break;
            case 'CompletoAsignacion':
                // TABS-1 - tabla produccion asignación 
                    $id_todos = trim($_POST['id_']);
                    $true = true;
                // 	validar la fecha de la producción.
                    $partial = explode("#",$id_todos);
                // separar valores del array
                    $id_produccion_asignacion = $partial[0];
                    $id_produccion = $partial[1];
                    $codigo_produccion = $partial[1];
                    $tiquete_desde = $partial[2];
                    $tiquete_hasta = $partial[3];
                    $fecha = $partial[4];
                    $cantidad = $partial[5];
                    //$precio_publico = $partial[6];
                // ACTUALIZAR TABLA PRODUCCIÓN ASIGNADO.
                    $query_pa = "UPDATE produccion_asignado SET procesado = '$true' 
                        WHERE codigo_produccion = '$id_produccion' and fecha = '$fecha' and tiquete_desde >= '$tiquete_desde' and tiquete_hasta <= '$tiquete_hasta'";
                // Ejecutamos el query_pa
                    $consulta = $dblink -> query($query_pa);              
                // ACTULIAR TABLA PRODUCCION CORRELATIVO.
                    $query_pc = "UPDATE produccion_correlativo SET procesado = '$true' 
                        WHERE codigo_produccion_asignacion = '$id_produccion_asignacion' and fecha = '$fecha' and correlativo >= '$tiquete_desde' and correlativo <= '$tiquete_hasta'";
                // Ejecutamos el query_pc
                    $consulta = $dblink -> query($query_pc);              
                    ListadoAsignado();
            break;
            case 'EditarAsignacion':
                // TABS-1 - tabla produccion asignación 
                $codigo_produccion = trim($_POST['IdProduccion']);
                $id_todos = trim($_POST['id_']);
                $true = true;
                // 	validar la fecha de la producción.
                    $partial = explode("#",$id_todos);
                // separar valores del array
                    $id_produccion_asignacion = $partial[0];
                    $id_produccion = $partial[1];
                    $codigo_produccion = $partial[1];
                    $tiquete_desde = $partial[2];
                    $tiquete_hasta = $partial[3];
                    $fecha = $partial[4];
                    $cantidad = $partial[5];

            break;
            case 'ActualizarTalonario': // Salida por el response.
                $Id_ = trim($_POST['IdProduccionAsignado']);
                $DesdeCola = intval(str_replace(",","",$_POST['DesdeCola']));
                $DesdeTabla = intval(str_replace(",","",$_POST['DesdeTabla']));
                $codigo_produccion = trim($_POST['IdProduccion']);
                $precio_publico = trim($_POST['PrecioPublico']);
                // validar si son iguales y regresar la cola a 0.
                if($DesdeTabla == $DesdeCola){
                    $DesdeCola = 0;
                // Actualizar Talonario en Producción Asignación.
                    $TotalIngreso = round($precio_publico * 100,2);
                    $query_at = "UPDATE produccion_asignado SET tiquete_cola = '$DesdeCola', cantidad = '100', total = '$TotalIngreso'
                        WHERE codigo_produccion = '$codigo_produccion' and id_ = '$Id_'";
                }else{
                // Actualizar Talonario en Producción Asignación.

                    $query_at = "UPDATE produccion_asignado SET tiquete_cola = '$DesdeCola'
                        WHERE codigo_produccion = '$codigo_produccion' and id_ = '$Id_'";
                }

                // Ejecutamos el query_pa
                    $consulta = $dblink -> query($query_at);              
                // Ver el listado produccion asigando.
                    $respuestaOK = true;
                    $mensajeError = "Cola Agregada...";
                // Llamar a la tabla
                    ListadoAsignado();
            break;
            ///////////////////////////////////
            // PROVIENEN DE main-nuevo-editar-produccion.js
            ///////////////////////////////////
            case 'BuscarPersonalMotorista':
                # Buscar de personal sólo motoristas.
                $query_p = "SELECT p.codigo, btrim(p.nombres || CAST(' ' AS VARCHAR) || p.apellidos) AS nombre_completo
                FROM personal p
                        ORDER BY p.codigo";
                                            //WHERE p.codigo_estatus = '01'
                    // Ejecutamos el Query.
                        $consulta = $dblink -> query($query_p);
                        // Inicializando el array
                            $datos=array(); $fila_array = 0;
                        // Recorriendo la Tabla con PDO::
                            while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                            {
                                // Nombres de los campos de la tabla.
                                $codigo = $listado['codigo']; $descripcion = trim($listado['nombre_completo']);
                            // Rellenando la array.
                                $datos[$fila_array]["codigo"] = $codigo;
                                $datos[$fila_array]["descripcion"] = ($descripcion);
                                $fila_array++;
                            }
            break;
            case 'BuscarJornada':
                # Buscar en tabla catalogo_jornada.
                // armando el Query.
                    $query = "SELECT id_, descripcion, hora_desde, hora_hasta from catalogo_jornada ORDER BY id_";
                    // Ejecutamos el Query.
                    $consulta = $dblink -> query($query);
                    // Inicializando el array
                    $datos=array(); $fila_array = 0;
                    // Recorriendo la Tabla con PDO::
                        while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                        {
                            // Nombres de los campos de la tabla.
                        $codigo = trim($listado['id_']); $descripcion = trim($listado['hora_desde']) . ' ' . trim($listado['hora_hasta']);
                        // Rellenando la array.
                           $datos[$fila_array]["codigo"] = $codigo;
                            $datos[$fila_array]["descripcion"] = $descripcion;
                                $fila_array++;
                            }
            break;
            case 'BuscarTransporteColectivo':
                # buscar en la tabla transporte_colectivo.
                    // armando el Query.
                    $query = "SELECT tc.id_, tc.codigo_tipo_transporte, tc.numero_equipo, tc.numero_placa,
                                cat_tt.descripcion as nombre_tipo_transporte
                                FROM transporte_colectivo tc
                                    INNER JOIN catalogo_tipo_transporte cat_tt ON cat_tt.id_ = tc.codigo_tipo_transporte
                                        ORDER BY id_";
                    // Ejecutamos el Query.
                    $consulta = $dblink -> query($query);
                    // Inicializando el array
                    $datos=array(); $fila_array = 0;
                    // Recorriendo la Tabla con PDO::
                        while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                        {
                            // Nombres de los campos de la tabla.
                        $codigo = trim($listado['id_']);
                        $descripcion = trim($listado['numero_placa']);
                        $numero_equipo = trim($listado['numero_equipo']);
                        // Rellenando la array.
                            $datos[$fila_array]["codigo"] = $codigo;
                            $datos[$fila_array]["descripcion"] = $descripcion;
                            $datos[$fila_array]["numero_equipo"] = $numero_equipo;
                                $fila_array++;
                            }
            break;
            case 'BuscarSerie':
                # buscar en la tabla catalogo_serie
                // armando el Query.
                $query = "SELECT it.id_, it.codigo_serie, it.precio_publico, it.existencia,
                        cat_ts.descripcion as nombre_serie
                        FROM inventario_tiquete it
                        INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie
                        WHERE it.existencia > 0";
                        // Ejecutamos el Query.
                        $consulta = $dblink -> query($query);
                        // Inicializando el array
                        $datos=array(); $fila_array = 0;
                        // Recorriendo la Tabla con PDO::
                            while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                            {
                                // Nombres de los campos de la tabla.
                            $codigo = trim($listado['id_']); $descripcion = trim($listado['nombre_serie']);
                            $precio_publico = trim($listado['precio_publico']); $existencia = trim($listado['existencia']);
                            // Rellenando la array.
                                $datos[$fila_array]["codigo"] = $codigo;
                                $datos[$fila_array]["descripcion"] = $descripcion;
                                $datos[$fila_array]["precio_publico"] = $precio_publico;
                                $datos[$fila_array]["existencia"] = $existencia;
                                    $fila_array++;
                                }
                break;
                case 'BuscarSerieId':
                    $id_ = trim($_POST['id_']);
                    # buscar en la tabla catalogo_serie
                    // armando el Query.
                    $query = "SELECT it.id_, it.codigo_serie, it.precio_publico, it.existencia,
                            cat_ts.descripcion as nombre_serie
                            FROM inventario_tiquete it
                            INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie
                            WHERE it.existencia > 0 and it.id_ = '$id_'";
                            // Ejecutamos el Query.
                            $consulta = $dblink -> query($query);
                            // Inicializando el array
                            $datos=array(); $fila_array = 0;
                            // Recorriendo la Tabla con PDO::
                                while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                                {
                                    // Nombres de los campos de la tabla.
                                $codigo = trim($listado['id_']); $descripcion = trim($listado['nombre_serie']);
                                $precio_publico = trim($listado['precio_publico']); $existencia = trim($listado['existencia']);
                                // Rellenando la array.
                                    $datos[$fila_array]["codigo"] = $codigo;
                                    $datos[$fila_array]["descripcion"] = $descripcion;
                                    $datos[$fila_array]["precio_publico"] = $precio_publico;
                                    $datos[$fila_array]["existencia"] = $existencia;
                                        $fila_array++;
                                    }
                    break;
            case 'BuscarRuta':
                # buscar en la tabla catalogo_ruta.
                // armando el Query.
                $query = "SELECT codigo, descripcion from catalogo_ruta ORDER BY codigo";
                // Ejecutamos el Query.
                $consulta = $dblink -> query($query);
                // Inicializando el array
                $datos=array(); $fila_array = 0;
                // Recorriendo la Tabla con PDO::
                    while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                    {
                        // Nombres de los campos de la tabla.
                    $codigo = trim($listado['codigo']); $descripcion = trim($listado['descripcion']);
                    // Rellenando la array.
                        $datos[$fila_array]["codigo"] = $codigo;
                    $datos[$fila_array]["descripcion"] = $descripcion;
                    $fila_array++;
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
	}elseif(
        $_POST["accion"] === "BuscarCodigo" or $_POST["accion"] === "BuscarPersonalMotorista" or $_POST["accion"] === "EditarRegistro"
        or $_POST["accion"] === 'BuscarJornada' or $_POST['accion'] === 'BuscarTransporteColectivo' or $_POST['accion'] === 'BuscarRuta'
        or $_POST["accion"] === 'BuscarSerie' or $_POST['accion'] === 'BuscarProduccionPorFecha' or $_POST['accion'] === 'BuscarProduccionPorId'
        or $_POST["accion"] === "BuscarSerieId"
        ){
		echo json_encode($datos);
		}
	else{
		// Armamos array para convertir a JSON
		$salidaJson = array("respuesta" => $respuestaOK,
			"mensaje" => $mensajeError,
            "contenido" => $contenidoOK,
            "totalIngreso" => $totalIngresoOKPantalla,
            "cantidadTiquete" => $CantidadtiqueteOK);
		echo json_encode($salidaJson);
    }
    function ListadoAsignado(){
        global $id_produccion, $dblink, $contenidoOK, $codigo_produccion, $totalIngresoOK, $respuestaOK, $mensajeError, $CantidadtiqueteOK, $totalIngresoOKPantalla;
        // consulta.
          $query_c = "SELECT p.id_ AS id_produccion, p.fecha, pa.codigo_inventario_tiquete, 
                cat_ts.descripcion as nombre_serie, 
                pa.id_ as id_produccion_asignado, pa.tiquete_desde, pa.tiquete_hasta, pa.total, pa.procesado, pa.cantidad, pa.total, pa.codigo_estatus, pa.tiquete_cola,
                btrim(cat_j.descripcion || CAST(': ' AS VARCHAR) || cat_j.hora_desde || CAST(' - ' AS VARCHAR) || cat_j.hora_hasta) as descripcion_jornada, 
                cat_r.descripcion as descripcion_ruta,
                it.precio_publico,
                cat_e.descripcion as descripcion_estatus
                    FROM produccion p 
                        INNER JOIN produccion_asignado pa ON pa.codigo_produccion = p.id_ 
                        INNER JOIN inventario_tiquete it ON it.id_ = pa.codigo_inventario_tiquete 
                        INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie 
                        INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = p.codigo_jornada 
                        INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = p.codigo_ruta 
                        INNER JOIN catalogo_estatus cat_e ON cat_e.codigo = pa.codigo_estatus
                            WHERE pa.codigo_produccion = '$codigo_produccion'
                            ORDER BY pa.id_, p.codigo_inventario_tiquete";
        // Ejecutamos el query
            $consulta = $dblink -> query($query_c);   
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // VERIFICAR Y ACTUALIZAR EL PRECIO PUBLICO.
            $query_update_pa = "UPDATE produccion_asignado SET
                total = cantidad * (SELECT it.precio_publico FROM inventario_tiquete it WHERE it.id_ = codigo_inventario_tiquete)
                    where codigo_produccion = '$codigo_produccion'";
                        $consulta_update_pa = $dblink -> query($query_update_pa);   
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
            
        // obtener el último dato en este caso el Id_
            while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
            {
                $id_pro_a = trim($listado['id_produccion_asignado']);		// dato de la tabla produccion.
                $pa_codigo_produccion = trim($listado['id_produccion']);    // dato de la tabla produccion_asignacion
                $nombre_serie = trim($listado['nombre_serie']);		// produccion correlativo.
                $tiquete_cola = (trim($listado['tiquete_cola']));   // produccion correlativo
                $tiquete_desde = codigos_nuevos(trim($listado['tiquete_desde']));   // produccion correlativo
                $tiquete_hasta = codigos_nuevos(trim($listado['tiquete_hasta']));   // produccion correlativo
                $total = trim($listado['total']);
                $cantidad = trim($listado['cantidad']);
                $fecha = trim($listado['fecha']);
                $precio_publico = trim($listado['precio_publico']);
                $procesado = trim($listado['procesado']);
                $codigo_estatus = trim($listado['codigo_estatus']);
                $descripcion_estatus = trim($listado['descripcion_estatus']);
                $estilo = ""; // definimos el estilo de cada elmento encontrado en codigo_esttratus.
    
                // variable armanda para posteriormente actualizar en <produccion_correlativo.
                    $todos = $id_pro_a . "#" . $pa_codigo_produccion . "#" . $tiquete_desde . "#" . $tiquete_hasta . "#" . $fecha . "#" . $precio_publico . "#" . $cantidad . "#" . $total . "#" . $tiquete_cola;                // Variables que pasa  a la tabla.s
                    $estilo_l = 'style="padding: 0px; font-size: large; color:blue; text-align: left;"';
                    $estilo_c = 'style="padding: 0px; font-size: large; color:blue; text-align: center;"';
                    $estilo_r = 'style="padding: 0px; font-size: large; color:blue; text-align: right;"';
                    $estilo_cola = 'style="padding: 0px; font-size: large; color:green; text-align: right; font-weight: bold;"';
                    //"flat-red" checked="" style="position: absolute; opacity: 0;"><ins class="iCheck-helper" style="position: absolute; top: 0%; left: 0%; display: block; width: 100%; height: 100%; margin: 0px; padding: 0px; background: rgb(255, 255, 255); border: 0px; opacity: 0;"';

                // cambiar color al estatus 04= Devolución , y 05= Vendido.
                    if($codigo_estatus == "04"){
                        $estilo = 'class="text-danger font-weight-bold" ' . 'style="padding: 0px; font-size: x-small; color:blue; text-align: right;"';
                    }
                    if($codigo_estatus == "05"){
                        $estilo = 'class="text-primary font-weight-bold" ' . 'style="padding: 0px; font-size: x-small; color:blue; text-align: right;"';
                    }
                //

                    // Calcular total ingresal. Sólo lo vendido.
                    if($codigo_estatus == '05' or $codigo_estatus == '03'){
                        $totalIngresoOK = $totalIngresoOK + $total;
                        $totalIngresoOKPantalla = number_format($totalIngresoOK,2);
                        $CantidadtiqueteOK = $CantidadtiqueteOK + $cantidad;    // esto servirá para restar de la existencia.
                    }
                    // Verificar si es Vendido o Devolución.
                    if($codigo_estatus == '05'){
                        $linea = "<td style='padding: 0px; zoom: 1.5'><input type=checkbox id=chk_$id_pro_a value=$id_pro_a data-toggle=tooltip data-placement=left title=Entregado> <span $estilo> $descripcion_estatus</span>";
                        //$descripcion_estatus = substr($descripcion_estatus,0,1);
                    }else if($codigo_estatus == '04'){
                        $linea = "<td style='padding: 0px; zoom: 1.5'><input type=checkbox id=chk_$id_pro_a value=$id_pro_a checked data-toggle=tooltip data-placement=left title=Entregado> <span $estilo> $descripcion_estatus</span>";
                    }else{
                        $linea = "<td style='padding: 0px; zoom: 1.5'><input type=checkbox id=chk_$id_pro_a value=$id_pro_a data-toggle=tooltip data-placement=left title=Entregado> <span $estilo> $descripcion_estatus</span>";
                    }
                    // verificar el estatus para cambiar mensaje.
                        if($procesado == '1'){
                            $mensajeError = "Efectivo a Entregar:";
                        }
                    // armando el contenio de la tabla.
                    $contenidoOK .= "<tr>
                    <td $estilo_l><a data-accion=EditarAsignacion data-toggle=tooltip data-placement=left title='Modificar Talonario' href='$todos'><i class='far fa-money-check-edit-alt'></i></a>
                    <td $estilo_l><input type=hidden value=$todos name=CalcularA>
                    $linea
                    <td $estilo_c>$nombre_serie
                    <td $estilo_r>$tiquete_desde
                    <td $estilo_r>$tiquete_hasta
                    <td $estilo_cola>$tiquete_cola
                    <td $estilo_r>$ $total"
                    ;         
            }
    }
/*
    if($procesado == '1'){  // si el estatus es verdadero
        // CUANDO YA SE HA CALCULADO LA PRODUCCIÓN ASIGNADA.
        $contenidoOK .= "<tr>
        <td $estilo_c>$pa_codigo_produccion-$id_pro_a
        <td $estilo>$descripcion_estatus
        <td $estilo_l>
        <td $estilo_c>$nombre_serie
        <td $estilo_cola>$tiquete_cola
        <td $estilo_r>$tiquete_desde
        <td $estilo_r>$tiquete_hasta
        <td $estilo_r>$ $total"
        ;
        // Calcular total ingresal. Sólo lo vendido.
        if($codigo_estatus == '05'){
            $totalIngresoOK = $totalIngresoOK + $total;
            $CantidadtiqueteOK = $CantidadtiqueteOK + $cantidad;    // esto servirá para restar de la existencia.
        }
        // Ver el listado produccion asigando.
        $respuestaOK = true;
        $mensajeError = "Cálculo realizado";
    }else{
        // EN DONDE NO SE HA CALCULADO LA PRODUCCIÓN ASIGNADA.
        $contenidoOK .= "<tr>
        <td $estilo_l><a data-accion=EditarAsignacion data-toggle=tooltip data-placement=left title=Modificar href='$todos'>Editar</a>
        <td $estilo_l><input type=hidden value=$todos name=CalcularA>
        <td style='padding: 0px; zoom: 1.5'><input type=checkbox  data-toggle=tooltip data-placement=left title=Entregado>
        <td $estilo_c>$nombre_serie
        <td $estilo_cola>$tiquete_cola
        <td $estilo_r>$tiquete_desde
        <td $estilo_r>$tiquete_hasta
        <td $estilo_r>$ $total"
        ;
        $totalIngresoOK = $totalIngresoOK + $total;
        $CantidadtiqueteOK = $CantidadtiqueteOK + $cantidad;    // esto servirá para restar de la existencia.
        // Ver el listado produccion asigando.
        $respuestaOK = true;
        $mensajeError = "Producción Encontrada";
    }*/
?>