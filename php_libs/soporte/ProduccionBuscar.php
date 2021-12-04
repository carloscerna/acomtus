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
$totalIngresoOK = 0;
$codigo_produccion = 0;
$CantidadtiqueteOK = 0;
$lista = "";
$fecha = "";
$arreglo = array();
$datos = array();
$listado = array(0,"1","2","3","4","5","6","7","8");
//$codigo_estatus = array('','Activo','Inactivo','Entregado','Devolución','Vendido');
$codigo_estatus = array('','01','02','03','04','05');
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
                $fecha_year = $_POST['year'];
                $fecha_month = $_POST['month'];
                // Armamos el query.
                /*$query = "SELECT pro.id_, pro.fecha, pro.hora, to_char(pro.fecha,'dd/mm/yyyy') as fecha, pro.codigo_estatus, 
                cat_j.descripcion as nombre_jornada, cat_j.id_ as id_jornada, cat_r.descripcion as nombre_ruta 
                FROM produccion pro 
                INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = pro.codigo_jornada
                INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = pro.codigo_ruta
                WHERE extract(year from pro.fecha) = '$fecha_year'
                ORDER BY pro.fecha DESC, pro.id_ DESC";*/
                
                $query = "SELECT pro.id_, pro.fecha, pro.hora, to_char(pro.fecha,'dd/mm/yyyy') as fecha, pro.codigo_estatus, 
                    cat_j.descripcion as nombre_jornada, cat_j.id_ as id_jornada, cat_r.descripcion as nombre_ruta 
                    FROM produccion pro 
                    INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = pro.codigo_jornada
                    INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = pro.codigo_ruta
                    WHERE extract(year from pro.fecha) = '$fecha_year' and extract(month from pro.fecha) = '$fecha_month'
                    ORDER BY pro.fecha DESC, pro.id_ DESC";
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
            // PROVIENEN DE main-nuevo-editar-produccion.js
            ///////////////////////////////////
            case 'BuscarPersonalMotorista':
                # Buscar de personal sólo motoristas.
                $query_p = "SELECT p.codigo, btrim(p.nombres || CAST(' ' AS VARCHAR) || p.apellidos) AS nombre_completo
                FROM personal p
                    WHERE p.codigo_estatus = '01'
                        ORDER BY p.codigo";
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
                        $codigo = trim($listado['id_']); $descripcion = trim($listado['descripcion']);
                        //. ' ' . trim($listado['hora_hasta']);
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
                $query = "SELECT it.id_, it.codigo_serie, it.precio_publico, it.existencia, it.codigo_estatus, it.descripcion as descripcion_completa,
                        cat_ts.descripcion as nombre_serie,
                        cat_tc.descripcion as tiquete_color, cat_tc.id_ as codigo_tiquete_color
                        FROM inventario_tiquete it
                        INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie
                        INNER JOIN catalogo_tiquete_color cat_tc On cat_tc.id_ = it.codigo_tiquete_color
                        WHERE it.codigo_estatus = '01'";
                        // Ejecutamos el Query.
                        $consulta = $dblink -> query($query);
                        // Inicializando el array_co
                        $datos=array(); $fila_array = 0;
                        // Recorriendo la Tabla con PDO::
                            while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                            {
                                // Nombres de los campos de la tabla.
                            $codigo = trim($listado['id_']); $descripcion = trim($listado['nombre_serie']); $descripcion_completa = trim($listado['descripcion_completa']);
                            $precio_publico = trim($listado['precio_publico']); $existencia = trim($listado['existencia']);
                            $tiquete_color = trim($listado['tiquete_color']); $codigo_tiquete_color = trim($listado['codigo_tiquete_color']);
                            // Rellenando la array.
                                $datos[$fila_array]["codigo"] = $codigo;
                                $datos[$fila_array]["descripcion"] = $descripcion;
                                $datos[$fila_array]["descripcion_completa"] = $descripcion_completa;
                                $datos[$fila_array]["precio_publico"] = $precio_publico;
                                $datos[$fila_array]["existencia"] = $existencia;
                                $datos[$fila_array]["tiquete_color"] = $tiquete_color;
                                $datos[$fila_array]["codigo_tiquete_color"] = $codigo_tiquete_color;
                                    $fila_array++;
                                }
                break;
                case 'BuscarSerieId':
                    $id_ = trim($_POST['id_']);
                    # buscar en la tabla catalogo_serie
                    // armando el Query.
                    $query = "SELECT it.id_, it.codigo_serie, it.precio_publico, it.existencia,
                            cat_ts.descripcion as nombre_serie,
                            cat_tc.descripcion as tiquete_color, cat_tc.id_ as codigo_tiquete_color
                            FROM inventario_tiquete it
                            INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie
                            INNER JOIN catalogo_tiquete_color cat_tc On cat_tc.id_ = it.codigo_tiquete_color
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
                                $codigo_tiquete_color = trim($listado['codigo_tiquete_color']);
                                // Rellenando la array.
                                    $datos[$fila_array]["codigo"] = $codigo;
                                    $datos[$fila_array]["descripcion"] = $descripcion;
                                    $datos[$fila_array]["precio_publico"] = $precio_publico;
                                    $datos[$fila_array]["existencia"] = $existencia;
                                    $datos[$fila_array]["codigo_tiquete_color"] = $codigo_tiquete_color;
                                        $fila_array++;
                                    }
                    break;
            case 'BuscarRuta':
                # buscar en la tabla catalogo_ruta.
                // armando el Query.
                $query = "SELECT id_ruta, codigo, descripcion from catalogo_ruta WHERE codigo_estatus = '01' ORDER BY codigo";
                // Ejecutamos el Query.
                $consulta = $dblink -> query($query);
                // Inicializando el array
                $datos=array(); $fila_array = 0;
                // Recorriendo la Tabla con PDO::
                    while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                    {
                        // Nombres de los campos de la tabla.
                    $codigo = trim($listado['id_ruta']); $descripcion = trim($listado['descripcion']);
                    // Rellenando la array.
                        $datos[$fila_array]["codigo"] = $codigo;
                    $datos[$fila_array]["descripcion"] = $descripcion;
                    $fila_array++;
                        }
                break;
            ///////////////////////////////////
            // PROCESO RELACIONADO CON LAS DEVOLUCIONES Y
            // ACTULIACIÓN DE FECHAS
            ///////////////////////////////////
            case 'BuscarProduccionDevolucion':
                // Variables
                $fecha = trim($_POST['fecha']);
                    BuscarProduccionDevolucion();
            break;
            case 'DevolucionRegistro':
                // TABS-1 - tabla produccion.
                $fila = trim($_POST['fila']);
                $calcular_chk[] = $_POST['calcular_chk'];
                $id_produccion[] = $_POST['calcular_val'];
                $fecha_produccion = trim($_POST['FechaProduccionCreacion']);
                if(isset($_POST['FechaProduccionDevolucion'])){$fecha_nueva = trim($_POST['FechaProduccionDevolucion']);}
                $cambioFechaRuta = trim($_POST['cambioFechaRuta']);
                if(isset($_POST['lstRuta'])){$nuevaRutaCodigo = $_POST['lstRuta'];}

                ///
                //  VALIDAR SI QUIERO CAMBIAR FECHA O RUTA
                ///
                if($cambioFechaRuta == "si"){
                        // 	validar la fecha de la producción.
                        $fechas = explode("-",$fecha_produccion);
                        $dia = $fechas[2];
                        $mes = $fechas[1];
                        $ann = $fechas[0];
                        //
                        if(checkdate($mes, $dia, $ann)){
                        //echo "fecha valida";
                        }else{
                        //echo "fecha no válida";
                        $mensajeError = "Fecha No Válida $dia . $mes . $ann";
                            break;
                        }
                        // LAS FECHAS NO PUEDEN SER IGUALES.
                        if($fecha_produccion == $fecha_nueva){
                            $mensajeError = "Las fechas no pueden ser iguales";
                            break;
                        }
                        // CRÇEAR CONSULTA EN PRODUCCION DEVOLUCION ANTES DE GUARDAR.
                        // PARA NO REPETIR LA PRIMERA FECHA DE CREACIÓN.
                        $query_fecha = "SELECT * FROM produccion_devolucion where fecha = '$fecha_produccion' and codigo_produccion = '$codigo_produccion'";
                        // Ejecutamos el Query.
                        $consulta = $dblink -> query($query_fecha);
                        // Validar si hay registros.
                        if($consulta -> rowCount() != 0){
                            $respuestaOK = false;
                            $mensajeError = "La Fecha de la Producción ha sido Modificada.";
                            break;
                        }
                        else{
                        //FOR
                        // recorrer matriz con los datos del chk (val y true)
                        $fila = $fila - 1;
                        // recorrer la array para extraer los datos.
                        for($i=0;$i<=$fila;$i++){
                            // Asignar valor a varinbles que las respectivas tablas.
                                $codigo_produccion = $id_produccion[0][$i];
                                $chk_ = $calcular_chk[0][$i];
                            if($chk_ == 'true')
                            {
                            // CREAR CONSULTA PARA GUARDAR LA NUEVA FECHA EN PRODUCCIÓN DEVOLUCIÓN.
                                $query_i = "INSERT INTO produccion_devolucion (codigo_produccion, fecha, codigo_estatus) VALUES ('$codigo_produccion','$fecha_produccion','$codigo_estatus[4]')";
                            // Ejecutamos el Query.
                                $consulta = $dblink -> query($query_i);
                            // ACTULIZAR LA FECHA ANTIGUA CON LA NUEVA FECHA PRODUCCIÓN EN LA TABLA PRODUCCIÓN Y PRODUCCIÓN ASIGNACION Y CORRELATIVO.
                                $query_u_p = "UPDATE produccion SET fecha = '$fecha_nueva' WHERE id_ = '$codigo_produccion' and fecha = '$fecha_produccion'";
                            // Ejecutamos el Query.
                                $consulta = $dblink -> query($query_u_p);
                            // produccion asignacion.
                                // obtener el dato de codigo produccion asignacion.
                                $query = "SELECT * FROM produccion_asignado WHERE codigo_produccion = '$codigo_produccion' and fecha = '$fecha_produccion'";
                                $consulta = $dblink -> query($query);
                                // Recorriendo la Tabla con PDO::
                                while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                                {
                                // Nombres de los campos de la tabla.
                                    $codigo_produccion_asignacion[] = trim($listado['id_']);
                                }
                            // produccion asignacion.
                                $query_u_a = "UPDATE produccion_asignado SET fecha = '$fecha_nueva' WHERE codigo_produccion = '$codigo_produccion' and fecha = '$fecha_produccion'";
                            // Ejecutamos el Query.
                                $consulta = $dblink -> query($query_u_a);

                            // IF VERDADERO DEL CHECKBOX
                            }
                }
               
                    }
                    // END-FOR
					$respuestaOK = true;
					$contenidoOK = '';
                    $mensajeError =  'Fecha Actualizada.';
                    
                   // Mostrar nuevamente los valores de la tabla según fecha.
                    $fecha = $fecha_produccion;
                        BuscarProduccionDevolucion();
				}else{
                    //
                    // CAMBIAR EL CODIGO DE LA RUTA
                    //
                    //FOR
                        // recorrer matriz con los datos del chk (val y true)
                        $fila = $fila - 1;
                        // recorrer la array para extraer los datos.
                        for($i=0;$i<=$fila;$i++){
                            // Asignar valor a varinbles que las respectivas tablas.
                                $codigo_produccion = $id_produccion[0][$i];
                                $chk_ = $calcular_chk[0][$i];
                            if($chk_ == 'true')
                            {
                                // ACTULIZAR LA FECHA ANTIGUA CON LA NUEVA FECHA PRODUCCIÓN EN LA TABLA PRODUCCIÓN Y PRODUCCIÓN ASIGNACION Y CORRELATIVO.
                                $query_u_p = "UPDATE produccion SET codigo_ruta = '$nuevaRutaCodigo' WHERE id_ = '$codigo_produccion' and fecha = '$fecha_produccion'";
                                // Ejecutamos el Query.
                                    $consulta = $dblink -> query($query_u_p);
                            }
                        } // FIN DEL FOR, EN EL CASO DE CAMBIO DE RUTA
                        // END-FOR
					$respuestaOK = true;
					$contenidoOK = '';
                    $mensajeError =  'Ruta Actualizada.';
                    
                   // Mostrar nuevamente los valores de la tabla según fecha.
                    $fecha = $fecha_produccion;
                        BuscarProduccionDevolucion();
                }      
			break;
            ///////////////////////////////////
            // ESTAS OPCIONES SE PODRÁN UTLIZAR EN CUALQUIER PARTE
            // RELACIONADOS CON LA BUSQUEDA DE INFORMACIÓN
            // POR RUTA
            // POR NÚMERO CONTROL
            // POR UNIDAD
            // POR TIQUETE
            ///////////////////////////////////
            case 'BuscarPorTiqueteEnControl':
                $tiquete_desde = 0;
                $NumeroTiquete = trim($_POST['numero_tiquete']);
                $serie = $_POST['serie'];
                $UltimosDos = substr($NumeroTiquete,-2);
                $SumarT = (99 - $UltimosDos) + 1;
                $tiquete_hasta = $NumeroTiquete + $SumarT;
                $LongitudNumero = strlen($NumeroTiquete);
                // Calcular la Longitud para tomar los digitos necesarios.
                switch ($LongitudNumero) {
                    case 6:
                        $tiquete_desde = substr($NumeroTiquete,0,4) . '01';
                        break;
                    case 5:
                        $tiquete_desde = substr($NumeroTiquete,0,3) . '01';
                        break;
                    case 4:
                        $tiquete_desde = substr($NumeroTiquete,0,2) . '01';
                        break;
                    case 3:
                        $tiquete_desde = substr($NumeroTiquete,0,1) . '01';
                        break;
                    default:
                        $tiquete_desde = '1';
                        break;
                }
                // SUMAR Y RESTAR
                // BUSQUEDA POR TIQUETE
                $query_busqueda_tiquete = "SELECT pro_a.tiquete_desde, pro_a.tiquete_hasta, pro_a.tiquete_cola, pro_a.codigo_produccion, to_char(pro.fecha,'dd/mm/yyyy') as fecha,
                        cat_j.id_ as id_jornada, cat_r.descripcion as nombre_ruta,
                        btrim(per.nombres || CAST(' ' AS VARCHAR) || per.apellidos) AS nombre_completo, per.codigo,
                        pro.numero_vueltas, pro.total_ingreso, pro.codigo_transporte_colectivo,
                        cat_est.descripcion as descripcion_estatus,
                        btrim(tc.numero_equipo || CAST(' | ' AS VARCHAR) || tc.numero_placa) AS nombre_unidad
                        FROM produccion_asignado pro_a
                        INNER JOIN produccion pro On pro.id_ = pro_a.codigo_produccion
                        INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = pro.codigo_jornada
                        INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = pro.codigo_ruta
                        INNER JOIN personal per ON per.codigo = pro.codigo_personal
                        INNER JOIN catalogo_estatus cat_est ON cat_est.codigo = pro_a.codigo_estatus
                        INNER JOIN transporte_colectivo tc ON tc.id_ = pro.codigo_transporte_colectivo
                            WHERE tiquete_desde BETWEEN $tiquete_desde and $NumeroTiquete and pro_a.codigo_inventario_tiquete = '$serie'";
                // Llenar variabla arreglo o data.
                    VerTiqueteEnControl();
                break;
            case 'BuscarPorTiquete':
                $tiquete_desde = 0;
                $NumeroTiquete = trim($_POST['numero_tiquete']);
                $serie = $_POST['serie'];
                $NumeroControl = trim($_POST['NumeroControl']);
                $UltimosDos = substr($NumeroTiquete,-2);
                $SumarT = (99 - $UltimosDos) + 1;
                $tiquete_hasta = $NumeroTiquete + $SumarT;
                $LongitudNumero = strlen($NumeroTiquete);
                // Calcular la Longitud para tomar los digitos necesarios.
                switch ($LongitudNumero) {
                    case 6:
                        $tiquete_desde = substr($NumeroTiquete,0,4) . '01';
                        break;
                    case 5:
                        $tiquete_desde = substr($NumeroTiquete,0,3) . '01';
                        break;
                    case 4:
                        $tiquete_desde = substr($NumeroTiquete,0,2) . '01';
                        break;
                    case 3:
                        $tiquete_desde = substr($NumeroTiquete,0,1) . '01';
                        break;
                    default:
                        $tiquete_desde = '1';
                        break;
                }
                 # Buscar por número tiquete. en el caso que ya fue procesado.
                 $query_b_t = "SELECT pro_a.tiquete_desde, pro_a.tiquete_hasta, pro_a.tiquete_cola, pro_a.codigo_produccion, to_char(pro.fecha,'dd/mm/yyyy') as fecha,
                 cat_j.id_ as id_jornada, cat_r.descripcion as nombre_ruta,
                 btrim(per.nombres || CAST(' ' AS VARCHAR) || per.apellidos) AS nombre_completo, per.codigo,
                 pro.numero_vueltas, pro.total_ingreso, pro.codigo_transporte_colectivo,
                 cat_est.descripcion as descripcion_estatus,
                 btrim(tc.numero_equipo || CAST(' | ' AS VARCHAR) || tc.numero_placa) AS nombre_unidad
                 FROM produccion_asignado pro_a
                 INNER JOIN produccion pro On pro.id_ = pro_a.codigo_produccion
                 INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = pro.codigo_jornada
                 INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = pro.codigo_ruta
                 INNER JOIN personal per ON per.codigo = pro.codigo_personal
                 INNER JOIN catalogo_estatus cat_est ON cat_est.codigo = pro.codigo_estatus
                 INNER JOIN transporte_colectivo tc ON tc.id_ = pro.codigo_transporte_colectivo
                     WHERE tiquete_desde BETWEEN '$tiquete_desde' and '$NumeroTiquete' and pro_a.codigo_inventario_tiquete = '$serie' and pro_a.codigo_produccion = '$NumeroControl'";
                     // Ejecutamos el Query.
                         $consulta = $dblink -> query($query_b_t);
                         // Inicializando el array
                             $datos=array(); $fila_array = 0;
                        // If
                             if($consulta -> rowCount() != 0){
                            // Recorriendo la Tabla con PDO::
                                while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                                {
                                    // Nombres de los campos de la tabla.
                                        $codigo_produccion = $listado['codigo_produccion'];
                                        $fecha = trim($listado['fecha']);
                                        $nombre_personal = trim($listado['codigo']) . ' | '. trim($listado['nombre_completo']);
                                        $ruta = trim($listado['nombre_ruta']);
                                        $jornada = trim($listado['id_jornada']);
                                        $unidad = trim($listado['nombre_unidad']);
                                        $numero_vueltas = trim($listado['numero_vueltas']);
                                        $total_ingreso = trim($listado['total_ingreso']);
                                        $estatus = trim($listado['descripcion_estatus']);
                                    // Rellenando la array.
                                        $datos[$fila_array]["codigo_produccion"] = $codigo_produccion;
                                        $datos[$fila_array]["fecha"] = $fecha;
                                        $datos[$fila_array]["nombre_personal"] = $nombre_personal;
                                        $datos[$fila_array]["ruta"] = $ruta;
                                        $datos[$fila_array]["jornada"] = $jornada;
                                        $datos[$fila_array]["unidad"] = $unidad;
                                        $datos[$fila_array]["numero_vueltas"] = $numero_vueltas;
                                        $datos[$fila_array]["total_ingreso"] = $total_ingreso;
                                        $datos[$fila_array]["estatus"] = $estatus;
                                    //
                                        $datos[$fila_array]["respuesta"] = true;
                                        $datos[$fila_array]["mensaje"] = 'N.º Control Encontrado';
                                    //
                                        $fila_array++;
                                }
                             }else{
                                // If - preguntar si ha sido procesado de lo contrario ejecutar otra consulta sin nombre del personal y unidad de transporte.
                                    # Buscar por número tiquete. en el caso que ya fue procesado.
                                    $query_b_t_n = "SELECT pro_a.tiquete_desde, pro_a.tiquete_hasta, pro_a.tiquete_cola, pro_a.codigo_produccion, to_char(pro.fecha,'dd/mm/yyyy') as fecha,
                                    cat_j.id_ as id_jornada, cat_r.descripcion as nombre_ruta,
                                    pro.numero_vueltas, pro.total_ingreso, pro.codigo_transporte_colectivo,
                                    cat_est.descripcion as descripcion_estatus
                                    FROM produccion_asignado pro_a
                                    INNER JOIN produccion pro On pro.id_ = pro_a.codigo_produccion
                                    INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = pro.codigo_jornada
                                    INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = pro.codigo_ruta
                                    INNER JOIN catalogo_estatus cat_est ON cat_est.codigo = pro.codigo_estatus
                                        WHERE tiquete_desde BETWEEN '$tiquete_desde' and '$NumeroTiquete' and pro_a.codigo_inventario_tiquete = '$serie' and pro_a.codigo_produccion = '$NumeroControl'";
                                 //
                                $consulta = $dblink -> query($query_b_t_n);
                                while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                                {
                                    // Nombres de los campos de la tabla.
                                        $codigo_produccion = $listado['codigo_produccion'];
                                        $fecha = trim($listado['fecha']);
                                        $nombre_personal = "NO ASIGNADO";
                                        $ruta = trim($listado['nombre_ruta']);
                                        $jornada = trim($listado['id_jornada']);
                                        $unidad = "NO ASIGNADO";
                                        $numero_vueltas = "NO ASIGNADO";
                                        $total_ingreso = "NO ASIGNADO";
                                        $estatus = trim($listado['descripcion_estatus']);
                                    // Rellenando la array.
                                        $datos[$fila_array]["codigo_produccion"] = $codigo_produccion;
                                        $datos[$fila_array]["fecha"] = $fecha;
                                        $datos[$fila_array]["nombre_personal"] = $nombre_personal;
                                        $datos[$fila_array]["ruta"] = $ruta;
                                        $datos[$fila_array]["jornada"] = $jornada;
                                        $datos[$fila_array]["unidad"] = $unidad;
                                        $datos[$fila_array]["numero_vueltas"] = $numero_vueltas;
                                        $datos[$fila_array]["total_ingreso"] = $total_ingreso;
                                        $datos[$fila_array]["estatus"] = $estatus;
                                    //
                                        $datos[$fila_array]["respuesta"] = true;
                                        $datos[$fila_array]["mensaje"] = 'N.º Control Encontrado';
                                    //
                                        $fila_array++;
                                }

                                $datos[$fila_array]["respuesta"] = false;
                                $datos[$fila_array]["mensaje"] = 'N.º Control No Procesado';
                             }
                break;
			case 'VerUltimasProducciones':
				// Variables.
					$fecha = $_REQUEST['fecha'];
				//
					VerUltimasProducciones();
			    break;
			case 'VerEliminarProduccion':
				// ELIMINAR CODIGO PRODUCCION.
				$codigo_produccion = $_POST['codigo_produccion'];
				$fecha = $_REQUEST['fecha'];
				//
					$query_p = "DELETE FROM produccion WHERE id_ = '$codigo_produccion' and codigo_estatus = '01'";
						$count_p = $dblink -> exec($query_p);
				//
					if($count_p != 0){
						// ELIMINAR CODIGO PRODUCCION ASIGNADO.
						$query_pa = "DELETE FROM produccion_asignado WHERE codigo_produccion = '$codigo_produccion'";
							$count_pa = $dblink -> exec($query_pa);
						//;
						$count_eliminados = $count_pa + $count_p;
						$respuestaOK = true;
						$mensajeError = 'Se ha Eliminado '.$count_eliminados.' Registro(s).';
					}else{
						$mensajeError = 'No se ha eliminado el registro';
					}
					//
					VerUltimasProducciones();
				break;        
            case 'AgregarTalonarioEnControl':
                $fecha_produccion_asignado = trim($_POST['AgregarFechaControl']);
                $codigo_produccion = trim($_POST['AgregarNumeroControl']);
                $codigo_inventario_tiquete = trim($_POST['lstSerieAgregarTiquete']);
                $desde_asignado = trim($_POST['AgregarTiqueteDesde']);
                $hasta_asignado = trim($_POST['AgregarTiqueteHasta']);
                $cantidad_asignado = trim($_POST['AgregarCantidadTiquete']);
                $total = trim($_POST['AgregarValorTalonario']);
					// query.
                            $query_pa = "INSERT INTO produccion_asignado (fecha, tiquete_desde, tiquete_hasta, cantidad, total, codigo_produccion, codigo_inventario_tiquete)
                            VALUES ('$fecha_produccion_asignado','$desde_asignado','$hasta_asignado','$cantidad_asignado','$total','$codigo_produccion','$codigo_inventario_tiquete')";
                    // Ejecutamos el query
                            $resultadoQuery = $dblink -> query($query_pa);              
                    // END-FOR
					$respuestaOK = true;
					$contenidoOK = '';
                    $mensajeError =  'Talonario Agregado...';
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
	if($_POST["accion"] === "BuscarTodos" or $_POST["accion"] === ""){
		echo json_encode($arreglo);	
	}elseif(
        $_POST["accion"] === "BuscarCodigo" or $_POST["accion"] === "BuscarPersonalMotorista" or $_POST["accion"] === "EditarRegistro"
        or $_POST["accion"] === 'BuscarJornada' or $_POST['accion'] === 'BuscarTransporteColectivo' or $_POST['accion'] === 'BuscarRuta'
        or $_POST["accion"] === 'BuscarSerie' or $_POST['accion'] === 'BuscarProduccionPorFecha' or $_POST['accion'] === 'BuscarProduccionPorId'
        or $_POST["accion"] === "BuscarSerieId" or $_POST['accion'] === 'BuscarPorTiquete' or $_POST['accion'] === 'BuscarPorTiqueteEncontrol'
        ){
		echo json_encode($datos);
		}
	else{
		// Armamos array para convertir a JSON
		$salidaJson = array("respuesta" => $respuestaOK,
			"mensaje" => $mensajeError,
            "contenido" => $contenidoOK,
            "totalIngreso" => $totalIngresoOK,
            "cantidadTiquete" => $CantidadtiqueteOK);
		echo json_encode($salidaJson);
    }
///////////////////////////////////////////////////////////////////////////////////////
//*******/ FUNCIONES.*****/////////////////////////////////
// ESTAS OPCIONES SE PODRÁN UTLIZAR EN CUALQUIER PARTE
// RELACIONADOS CON LA BUSQUEDA DE INFORMACIÓN
// POR RUTA
// POR NÚMERO CONTROL
// POR UNIDAD
// POR TIQUETE
///////////////////////////////////
///////////////////////////////////
function VerTiqueteEnControl(){
	global $contenidoOK, $dblink, $fecha, $respuestaOK, $mensajeError, $query_busqueda_tiquete, $tiquete_desde, $tiquete_hasta, $serie, $NumeroTiquete;
	//	Estilos
		$estilo_l = 'style="padding: 0px; font-size: small; color:black; text-align: left;"';
		$estilo_c = 'style="padding: 0px; font-size: small; color:black; text-align: center;"';
        $estilo_r = 'style="padding: 0px; font-size: small; color:black; text-align: right;"';
    //  Ejecutar consulta.
		$consulta_p = $dblink -> query($query_busqueda_tiquete);      
	// Validar si hay registros.
		if($consulta_p -> rowCount() != 0){  
		// obtener el último dato en este caso el Id_
				while($listado = $consulta_p -> fetch(PDO::FETCH_BOTH))
				{
					$codigo_produccion = trim($listado['codigo_produccion']);
                    $fecha = (trim($listado['fecha']));						
                    $tiquete_desde = (trim($listado['tiquete_desde']));		
                    $tiquete_hasta = (trim($listado['tiquete_hasta']));		
                    $tiquete_cola = (trim($listado['tiquete_cola']));	
                    $estatus = (trim($listado['descripcion_estatus']));		

					$contenidoOK .= "<tr>
					<td $estilo_c>
					<a data-accion=BuscarPorTiquete data-toggle=tooltip data-placement=left title='Ver Control' href=$codigo_produccion style='color: black;'><i class='fad fa-search fa-md'></i></a>
					<td $estilo_c>$fecha
                    <td $estilo_c>$codigo_produccion
                    <td $estilo_c>$tiquete_desde
                    <td $estilo_c>$tiquete_hasta
                    <td $estilo_c>$tiquete_cola
                    <td $estilo_c>$estatus"
					;
				}
			// VALIRDAR RESPUESTA SI HAY REGISTROS.
                $respuestaOK = true;
            // Mensaje.
                $mensajeError = 'Se encontro Tiquete en Control.';
		}else{
                            // BUSQUEDA POR TIQUETE
                            $query_busqueda_tiquete = "SELECT pro_a.tiquete_desde, pro_a.tiquete_hasta, pro_a.tiquete_cola, pro_a.codigo_produccion, to_char(pro.fecha,'dd/mm/yyyy') as fecha,
                            cat_j.id_ as id_jornada, cat_r.descripcion as nombre_ruta,
                            pro.numero_vueltas, pro.total_ingreso, pro.codigo_transporte_colectivo,
                            cat_est.descripcion as descripcion_estatus
                            FROM produccion_asignado pro_a
                            INNER JOIN produccion pro On pro.id_ = pro_a.codigo_produccion
                            INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = pro.codigo_jornada
                            INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = pro.codigo_ruta
                            INNER JOIN catalogo_estatus cat_est ON cat_est.codigo = pro_a.codigo_estatus
                                WHERE tiquete_desde BETWEEN $tiquete_desde and $NumeroTiquete and pro_a.codigo_inventario_tiquete = '$serie'";

    //  Ejecutar consulta.
    $consulta_p = $dblink -> query($query_busqueda_tiquete);      
	// Validar si hay registros.
		if($consulta_p -> rowCount() != 0){  
		// obtener el último dato en este caso el Id_
				while($listado = $consulta_p -> fetch(PDO::FETCH_BOTH))
				{
					$codigo_produccion = trim($listado['codigo_produccion']);
                    $fecha = (trim($listado['fecha']));						
                    $tiquete_desde = (trim($listado['tiquete_desde']));		
                    $tiquete_hasta = (trim($listado['tiquete_hasta']));		
                    $tiquete_cola = (trim($listado['tiquete_cola']));	
                    $estatus = (trim($listado['descripcion_estatus']));		

					$contenidoOK .= "<tr>
					<td $estilo_c>
					<a data-accion=BuscarPorTiquete data-toggle=tooltip data-placement=left title='Ver Control' href=$codigo_produccion style='color: black;'><i class='fad fa-search fa-md'></i></a>
					<td $estilo_c>$fecha
                    <td $estilo_c>$codigo_produccion
                    <td $estilo_c>$tiquete_desde
                    <td $estilo_c>$tiquete_hasta
                    <td $estilo_c>$tiquete_cola
                    <td $estilo_c>$estatus"
					;
                }
            }
            // Mensaje.
                $mensajeError = 'Tiquete en Control no Procesado.';
        }   
}
function VerUltimasProducciones(){
	global $contenidoOK, $dblink, $fecha, $respuestaOK;
	$CantidadTalonario = 0;
	//	Estilos
		$estilo_l = 'style="padding: 0px; font-size: medium; color:black; text-align: left;"';
		$estilo_c = 'style="padding: 0px; font-size: medium; color:black; text-align: center;"';
		$estilo_r = 'style="padding: 0px; font-size: medium; color:black; text-align: right;"';
	# VER LOS ULTIMOS ID_ DE PRODUCCION AGREGADOS.
        //$query_p = "SELECT id_, fecha, hora FROM produccion WHERE fecha = '$fecha' ORDER BY id_ DESC LIMIT 15";
        $query_p = "SELECT p.id_, p.fecha, p.hora, p.codigo_ruta, cat_r.descripcion as descripcion_ruta
                    FROM produccion p
                    INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta =  p.codigo_ruta
                        ORDER BY id_ DESC LIMIT 30";
		$consulta_p = $dblink -> query($query_p);      
	// Validar si hay registros.
		if($consulta_p -> rowCount() != 0){  
		// obtener el último dato en este caso el Id_
				while($listado = $consulta_p -> fetch(PDO::FETCH_BOTH))
				{
					$codigo_produccion = trim($listado['id_']);
					$fecha = cambiaf_a_normal(trim($listado['fecha']));
					$hora = trim($listado['hora']);
                    $descripcion_ruta = trim($listado['descripcion_ruta']);

					// contar cuantos talonarios hay en la producción. 
					$query_c = "SELECT count(*) as cantidad from produccion_asignado where codigo_produccion = '$codigo_produccion'";
						$consulta_c = $dblink -> query($query_c);      
						while($listado_ = $consulta_c -> fetch(PDO::FETCH_BOTH))
							{
								$CantidadTalonario = $listado_['cantidad'];
							}
						

					$contenidoOK .= "<tr>
					<td $estilo_c>
					<a data-accion=VerProduccion data-toggle=tooltip data-placement=left title='Ver Controles' href=$codigo_produccion style='color: black;'><i class='fad fa-search fa-md'></i></a>
					<a data-accion=VerEliminarProduccion data-toggle=tooltip data-placement=left title='Eliminar Control' href=$codigo_produccion style='color: black;'><i class='fad fa-trash fa-md'></i></a>
					<td $estilo_c>$codigo_produccion
                    <td $estilo_l>$descripcion_ruta
					<td $estilo_c>$CantidadTalonario
					<td $estilo_r>$fecha
					<td $estilo_r>$hora"
					;
				}
			// VALIRDAR RESPUESTA SI HAY REGISTROS.
				$respuestaOK = true;
		}   
}

//
//  RELACIONADO CON LA BUSQUEDA DE CONTROLES QUE NO SE UTILIZARON EN UNA FECHA DETERMINADA.
//
function BuscarProduccionDevolucion(){
	global $contenidoOK, $dblink, $fecha, $respuestaOK;
	$CantidadTalonario = 0;
	//	Estilos
		$estilo_l = 'style="padding: 0px; font-size: medium; color:black; text-align: left;"';
		$estilo_c = 'style="padding: 0px; font-size: medium; color:black; text-align: center;"';
        $estilo_r = 'style="padding: 0px; font-size: medium; color:black; text-align: right;"';
	# VER LOS ULTIMOS ID_ DE PRODUCCION AGREGADOS.
        //$query_p = "SELECT id_, fecha, hora FROM produccion WHERE fecha = '$fecha' ORDER BY id_ DESC LIMIT 15";
        $query_b_d = "SELECT pro.id_, pro.fecha, pro.codigo_estatus, pro.codigo_ruta,
                            cat_est.descripcion as descripcion_estatus,
                            cat_r.descripcion as descripcion_ruta
                            FROM produccion pro 
                            INNER JOIN catalogo_estatus cat_est ON cat_est.codigo = pro.codigo_estatus
                            INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = pro.codigo_ruta
                            WHERE pro.codigo_estatus = '01' AND pro.fecha = '$fecha'
                                ORDER BY id_ DESC";
		$consulta_p = $dblink -> query($query_b_d);      
	// Validar si hay registros.
		if($consulta_p -> rowCount() != 0){  
		// obtener el último dato en este caso el Id_
				while($listado = $consulta_p -> fetch(PDO::FETCH_BOTH))
				{
					$codigo_produccion = trim($listado['id_']);
					$fecha = cambiaf_a_normal(trim($listado['fecha']));
                    $estatus = trim($listado['descripcion_estatus']);
                    $descripcion_ruta = trim($listado['descripcion_ruta']);

                    // Estilo del checkbox
                    $linea = "<td style='padding: 0px; zoom: 1.5; text-align: center;'><input type=checkbox id=chk_$codigo_produccion value=$codigo_produccion data-toggle=tooltip data-placement=left title=Entregado>";

                    $contenidoOK .= "<tr>
                    $linea
                    <td $estilo_l><input type=hidden value=$codigo_produccion name=CalcularA>
					<td $estilo_c>$codigo_produccion
                    <td $estilo_c>$fecha
                    <td $estilo_l>$descripcion_ruta
					<td $estilo_c>$estatus"
					;
				}
			// VALIRDAR RESPUESTA SI HAY REGISTROS.
				$respuestaOK = true;
		}   
}
//
//  CODIGO A EVALUAR.
//
function ComaYGuion(){
    $codigo_produccion = "25-27,198,25,195,223-230";
    $codigo_produccion_ = array();
        // que examine si la cadena tiene coma.
            $coma = ""; $guion = "";
            $guion = strpos($codigo_produccion,"-");
            $coma = strpos($codigo_produccion,",");
        // verificar si existe guión y coma.
            if(!empty($guion) && !empty($coma)){
                // HAY GUION Y COMA.
                    print "Hay quión y coma";
                    // Primero separar los valores que tienen coma.
                    $numero_control = explode(",",$codigo_produccion);
                    $numero_elemento = count($numero_control);
                    print_r($numero_control);
                    // separar los guiones
                    $numero_control_ = explode("-",$codigo_produccion);
                    print_r($numero_control_);
                    
                    $numero_control = explode("-",$codigo_produccion);
                    print_r($numero_control);
                        exit;
            }
            if(!empty($coma)){
                print "Hay Coma";
                // Convertir String a Matriz (Conitiene los N.º Correlativos de los Controles Creados.)
                    $numero_control = explode(",",$codigo_produccion);
                    $numero_elemento = count($numero_control);
                    $codigo_produccion_ = explode(",",$codigo_produccion);
                // RECORRER LA MATRIZ.
                
                for ($jj=0; $jj < count($codigo_produccion_); $jj++) { 
                    // función para imprimir.
                    $codigo_produccion_i = $codigo_produccion_[$jj];
                	print $codigo_produccion_i . "<br>";
                }   // FIN DEL FOR.
                
             }else{
                ///////////////////////////////////////////////////////////////////////////////////////////////////////////
                               // print "No Coma";
                // Convertir String a Matriz (Conitiene los N.º Correlativos de los Controles Creados.)
                $numero_control = explode("-",$codigo_produccion);
                $numero_elemento = count($numero_control);
                $codigo_produccion_ = explode("-",$codigo_produccion);
            // EXAMINAR SI HAY GUION.
                $guion = strpos($codigo_produccion,"-");
                
                if(!empty($guion)){
                    // HAY GUION
                    // cambiar valor de $jj
                    $codigo_partial_01 = $codigo_produccion_[0];
                    $codigo_partial_02 = $codigo_produccion_[1];
                    // RECORRER LA MATRIZ.
                    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                    for ($jj=$codigo_partial_01; $jj <= $codigo_partial_02; $jj++) { 
                        // función para imprimir.
                        $codigo_produccion_i = $jj;
                          print $codigo_produccion_i . "<br>";
                        }   // FIN DEL FOR.
                }
            }
}
?>