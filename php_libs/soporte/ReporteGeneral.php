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
$totalProduccionOK = 0;
$codigo_produccion = 0;
$CantidadtiqueteOK = 0;
$ProduccionIngresoOk = 0;
$ProduccionTotalIngresoOkPantalla = 0;
$ProduccionTotalIngresoOk = 0;
$totalIngresoOK = 0;
$totalIngresoOKPantalla = 0;
$cantidadTiquetePantalla = 0;
$cantidadTiqueteDevolucion = 0;
$cantidadTiqueteEntregados = 0;
$cantidadTiqueteEntregadosPantalla = 0;
$cantidadVendidosProduccion = 0;
$ProduccionDesdeHasta = "";
$codigo_personal = "";
$numero_equipo = "";
$numero_placa = "";
$precio_publico_ = 0;
$lista = "";
$nombre_motorista = "";
$arreglo = array();
$datos = array();
$listado = array("0","1","2","3","4","5","6","7");
$fecha_desde = "";
$fecha_hasta = "";
$OptBuscarPM = "";
$descripcion_ruta_pm = "";
$descripcion_ruta_rg = "";
$url_foto = "";
$codigo_genero = "";
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
		    case 'BuscarProduccionPorRuta':
                # buscar en la tabla catalogo_ruta.
                    $fecha = $_POST["fecha"];
                    $fecha_ = cambiaf_a_normal($_POST["fecha"]);
                // CANTIDAD DE CONTROLES VENDIDOS.
                   $query_v = "SELECT count(*) as total_vendidos FROM produccion where codigo_estatus = '02' and fecha = '$fecha'";
                        $consulta_v = $dblink -> query($query_v);
                // crear matriz
                    while($listado_v = $consulta_v -> fetch(PDO::FETCH_BOTH))
                    {
                        $cantidadVendidosProduccion = $listado_v['total_vendidos'];
                    }
                // armando el Query. PARA LA TABLA CATALOGO RUTA.
                    $query = "SELECT id_ruta, codigo, descripcion FROM catalogo_ruta ORDER BY codigo";
                // Ejecutamos el Query.
                    $codigo_ruta = array();
                    $consulta = $dblink -> query($query);
                // crear matriz
                    while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                    {
                        $codigo_ruta[] = $listado['id_ruta'];
                        $descripcion_ruta[] = $listado['descripcion'];
                    }
                     // armando el Query. PARA LA TABLA INVENTARIO TIQUETE..
                        $query = "SELECT DISTINCT cat_tc.id_ as id_tiquete_color, cat_tc.descripcion as tiquete_color, it.precio_publico
                        FROM catalogo_tiquete_color cat_tc
                            INNER JOIN inventario_tiquete it ON cat_tc.id_ = it.codigo_tiquete_color
                                ORDER BY it.precio_publico";
                        // Ejecutamos el Query.
                            $id_tiquete_color = array(); $precio_publico_ = array(); $nombre_serie = array(); $tiquete_color = array();
                            $consulta = $dblink -> query($query);
                        // crear matriz
                        while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                        {
                            $id_tiquete_color[] = $listado['id_tiquete_color'];
                            $precio_publico[] = $listado['precio_publico'];
                            //$nombre_serie[] = $listado['nombre_serie'];
                            $tiquete_color[] = $listado['tiquete_color'];
                        }
                        // CREAR MATRIZ UNIENDO LA RUTA CON EL PRECIO PUBLICO DE CADA TIQUETE.
                        $codigo_ruta_precio = array(); $descripcion_ruta_ = array(); $precio_publico_ = array(); $nombre_serie_ = array(); $tiquete_color_ = array();
                        for ($Hj=0; $Hj < count($codigo_ruta); $Hj++) { // tabla CATALOGO RUTA.
                            for ($jj=0; $jj < count($precio_publico); $jj++) {   // TABLA CATALOGO INVENTARIO TIQUETE.
                                // Verificar que sólo se genere ruta y único precio público.
                                //$codigo_ruta_precio[] = $codigo_ruta[$Hj] . $id_it[$jj];
                                $codigo_ruta_precio[] = $codigo_ruta[$Hj] . $id_tiquete_color[$jj];
                                $descripcion_ruta_[] = $descripcion_ruta[$Hj];
                                $precio_publico_[] = $precio_publico[$jj];
                                //$nombre_serie_[] = $nombre_serie[$jj];
                                $tiquete_color_[] = $tiquete_color[$jj];
                            }
                        }
                        // print_r($codigo_ruta_precio);
                        // CONSULTA FOR INVENTARIO TIQUETE.
                        for ($Hj=0; $Hj < count($codigo_ruta_precio); $Hj++) { // MATRIZ CATALOGO RUTA Y INVENTARIO TIQUETE..
                            $query = "SELECT pro.id_, pro.total_ingreso, pro.codigo_ruta
                                FROM produccion pro WHERE fecha = '$fecha' and concat(codigo_ruta,codigo_tiquete_color) = '$codigo_ruta_precio[$Hj]'
                                    ORDER BY pro.codigo_ruta, pro.id_ ASC";
                                $consulta = $dblink -> query($query);
                            // Variable ProduccionIngreso Ok a cero.
                                $ProduccionIngresoOk = 0; $cantidadTiquete = 0;
                            // Validar si hay registros.
                                if($consulta -> rowCount() != 0){
                                    // Crear matriz para poder tomar dos valores
                                        $ProduccionDesdeHasta = array(); $ProduccionDesde = 0; $ProduccionHasta = 0;
                                        // Recorriendo la Tabla con PDO::
                                        while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                                        {
                                            $ProduccionDesdeHasta[] = $listado["id_"];
                                            $ProduccionIngresoOk = $ProduccionIngresoOk + $listado["total_ingreso"];
                                        }   // WHILE DE LA CONSULTA PRODUCCIÓN.
                                        // CATNIDAD DE TIQUETES.
                                            $cantidadTiquete = round($ProduccionIngresoOk / $precio_publico_[$Hj],2);
                                            $cantidadTiquetePantalla = $cantidadTiquetePantalla + $cantidadTiquete;
                                        // PASAR VALOR PRIMERO Y ÚLTIMO DE LA MATRIZ A LAS VARIALBES.
                                            $ProduccionDesde = reset($ProduccionDesdeHasta);
                                            $ProduccionHasta = end($ProduccionDesdeHasta);
                                            $ProduccionCantidad = count($ProduccionDesdeHasta);
                                        //
                                        $totalProduccionOK = $totalProduccionOK + $ProduccionCantidad;
                                        if(is_numeric($ProduccionIngresoOk)){
                                            $ProduccionTotalIngresoOk = round($ProduccionTotalIngresoOk + $ProduccionIngresoOk,2);
                                        }
                                        //
                                        $ProduccionIngresoOkPantalla = number_format($ProduccionIngresoOk,2);
                                        $ProduccionTotalIngresoOkPantalla = number_format($ProduccionTotalIngresoOk,2);
                                        //
                                    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                    //////// CALCULAR LA CANTIDAD DE TIQUETES DEVUELTOS/////////////////////////////////////////////////////////////////////////////////////////////////////////
                                        // Variable cantidad tiquete devuelto.
                                        $cantidadTiqueteDevolucion = 0; $cantidadTiqueteEntregados = 0; $TiqueteCola = 0;
                                            for ($ab=0; $ab < count($ProduccionDesdeHasta); $ab++) { // MATRIZ CATALOGO RUTA Y INVENTARIO TIQUETE..
                                                                                        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////                                    
                                                // VENDIDO DE TIQUETE CODIGO 05
                                                /////////////////////////////////////////////////////////////////////////////////////////////////////////////////                                    
                                                $query_t_d = "SELECT * FROM produccion_asignado 
                                                    WHERE codigo_estatus = '05' and fecha = '$fecha' and tiquete_cola > 0 and codigo_produccion = '$ProduccionDesdeHasta[$ab]'
                                                    ORDER by id_";
                                                $consultas = $dblink -> query($query_t_d);

                                                // Validar si hay registros.
                                                    if($consultas -> rowCount() != 0){
                                                    // Recorriendo la Tabla con PDO::
                                                        while($listados = $consultas -> fetch(PDO::FETCH_BOTH))
                                                        {
                                                            $ctd = ($listados["tiquete_hasta"] - $listados["tiquete_cola"]) + 1;
                                                            $TiqueteCola = $ctd;
                                                            $cantidadTiqueteDevolucion = $cantidadTiqueteDevolucion + $ctd;
                                                        }
                                                    }   // if del query d t
                                                /////////////////////////////////////////////////////////////////////////////////////////////////////////////////                                    
                                                // DEVOLUCION DE TIQUETE CODIGO 04
                                                /////////////////////////////////////////////////////////////////////////////////////////////////////////////////                                    
                                                $query_t_d_ = "SELECT * FROM produccion_asignado 
                                                    WHERE codigo_estatus = '04' and fecha = '$fecha' and codigo_produccion = '$ProduccionDesdeHasta[$ab]'
                                                    ORDER by id_";
                                                $consultas_ = $dblink -> query($query_t_d_);
                                                // Validar si hay registros.
                                                    if($consultas_ -> rowCount() != 0){
                                                    // Recorriendo la Tabla con PDO::
                                                        while($listados_ = $consultas_ -> fetch(PDO::FETCH_BOTH))
                                                        {
                                                            $ctd = $listados_["cantidad"];
                                                            $cantidadTiqueteDevolucion = $cantidadTiqueteDevolucion + $ctd;
                                                        }
                                                    }   // if del query d t
                                                /////////////////////////////////////////////////////////////////////////////////////////////////////////////////  
                                                // TIQUETES ENTREGADOS.
                                                /////////////////////////////////////////////////////////////////////////////////////////////////////////////////  
                                                $query_t_d_c = "SELECT * FROM produccion_asignado WHERE fecha = '$fecha' and codigo_produccion = '$ProduccionDesdeHasta[$ab]' ORDER by id_";
                                                    $consultas_c = $dblink -> query($query_t_d_c);
                                                        // Validar si hay registros.
                                                        if($consultas_c -> rowCount() != 0){
                                                        // Recorriendo la Tabla con PDO::
                                                            while($listados_c = $consultas_c -> fetch(PDO::FETCH_BOTH))
                                                            {
                                                                $ctd = $listados_c["cantidad"];
                                                                $cantidadTiqueteEntregados = $cantidadTiqueteEntregados + $ctd;
                                                            }
                                                            // SUMARLE LA COLA.
                                                            $cantidadTiqueteEntregados = $cantidadTiqueteEntregados + $TiqueteCola;
                                                            $cantidadTiqueteEntregadosPantalla = number_format($cantidadTiqueteEntregadosPantalla,0);
                                                        }   // if del query d t
                                            }   // FINALIZA EL CALCULO DE TIQUETES DEVUELTOS VENDIDOS, ENTREGADOS.
                                    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                                }   // IF DE LA CONSULTA PRODUCCION POR RUTA..
                                // DAR VALORES A VARIABLES.
                                    // ESTILO
                                    $estilo_l = 'style="padding: 0px; font-size: medium; text-align: left;"';
                                    $estilo_c = 'style="padding: 0px; font-size: medium; text-align: center;"';
                                    $estilo_r = 'style="padding: 0px; font-size: medium; text-align: right;"';
                                    $estilo_r_green = 'style="padding: 0px; font-size: medium; color:green; text-align: right;"';
                                    // CUANDO YA SE HA CALCULADO LA PRODUCCIÓN ASIGNADA.
                                    // Validar si hay registros.
                                    if($consulta -> rowCount() != 0){
                                        // convertir la matriz $ProduccionDesdeHasta
                                            $separado_por_comas = implode(",", $ProduccionDesdeHasta);
                                        $contenidoOK .= "<tr>
                                        <td $estilo_c><a data-accion=ProduccionImprimir data-toggle=tooltip data-placement=left title=Imprimir href=$separado_por_comas><i class='fad fa-search fa-lg'></i></a>
                                        <td $estilo_l>
                                        <td $estilo_l>$descripcion_ruta_[$Hj]
                                        <td $estilo_r><input type=button class='btn btn-info btn-md' value='#' data-toggle=tooltip data-placement=left title='$separado_por_comas'>
                                        <td $estilo_c>$ProduccionCantidad
                                        <td $estilo_c>$cantidadTiqueteEntregados
                                        <td $estilo_c>$cantidadTiqueteDevolucion
                                        <td $estilo_c>$cantidadTiquete
                                        <td $estilo_c>$ $precio_publico_[$Hj]
                                        <td $estilo_r_green>$ $ProduccionIngresoOkPantalla
                                        <td>
                                        ";
                        }
            }   // FOR DE LA TABLA INVENTARIO TIQUETE..
            // GUARDAR EN LA TABLA PRODUCCION_DIARIO
            // id_, fecha, total_dolares, total_colones
                $query_p_s = "SELECT * FROM produccion_diaria WHERE fecha = '$fecha'";
                        // Ejecutamos el query
                    $consulta_p_s = $dblink -> query($query_p_s);              
                    // obtener el último dato en este caso el Id_
                        // Validar si hay registros.
                    if($consulta_p_s -> rowCount() != 0){  
                       // ACTUALIZAR VALOREStotal_colones
                       $total_colones = round($ProduccionTotalIngresoOk * 8.75,2);
                       $query_p_d = "UPDATE produccion_diaria SET total_dolares = '$ProduccionTotalIngresoOk', total_colones = '$total_colones' WHERE fecha = '$fecha'";
                       $consultas_p_d = $dblink -> query($query_p_d);
                    }else{
                        // GUARDAR
                        $total_colones = round($ProduccionTotalIngresoOk * 8.75,2);
                        $query_p_d = "INSERT INTO produccion_diaria (fecha, total_dolares, total_colones) VALUES ('$fecha','$ProduccionTotalIngresoOk','$total_colones')";
                        $consultas_p_d = $dblink -> query($query_p_d);
                    }
                    // 
                    $respuestaOK = true;
                    $mensajeError = "Producción Encontrada.";
                break;   
            case "BuscarTodosUnidadPlaca":
                $codigo_up = trim($_REQUEST['codigo_up']);
                $fecha_desde = trim($_REQUEST['FechaDesdeUP']);
                $fecha_hasta = trim($_REQUEST['FechaHastaUP']);
                $OptBuscarUP = trim($_REQUEST['OptBuscarUP']);
                    ListadoPorUnidadTransporte();
            break;
            case 'BuscarPorMotorista':
                $codigo_personal = trim($_REQUEST['codigo_personal']);
                $fecha_desde = trim($_REQUEST['FechaDesdePM']);
                $fecha_hasta = trim($_REQUEST['FechaHastaPM']);
                $OptBuscarPM = trim($_REQUEST['OptBuscarPM']);
                    ListadoPorCodigoPersonal();
                break;
            case 'BuscarProduccionPorId':
                // TABS-1 - tabla produccion.
                    $codigo_produccion = trim($_POST['codigo_produccion']);
                // 	validar la fecha de la producción.
                    $numero_control = explode(",",$codigo_produccion);
                    //$codigo_produccion_desde = $fechas[0];
                    //$codigo_produccion_hasta = $fechas[1];
                for ($ab=0; $ab < count($numero_control); $ab++) { // MATRIZ CATALOGO RUTA Y INVENTARIO TIQUETE..
                    $query = "SELECT p.id_ AS id_produccion, p.fecha, p.codigo_inventario_tiquete, p.id_, p.total_ingreso, p.codigo_jornada,
                        p.codigo_personal, 
                        p.codigo_transporte_colectivo, cat_ts.descripcion as nombre_serie, 
                        btrim(cat_j.descripcion || CAST(': ' AS VARCHAR) || cat_j.hora_desde || CAST(' - ' AS VARCHAR) || cat_j.hora_hasta) as descripcion_jornada, 
                        cat_r.descripcion as descripcion_ruta,
                        btrim(per.nombres || CAST(' ' AS VARCHAR) || per.apellidos) as nombre_motorista,
                        tc.numero_equipo, tc.numero_placa,
                        cat_estatus.descripcion as descripcion_estatus
                            FROM produccion p
                                INNER JOIN personal per ON per.codigo = p.codigo_personal
                                INNER JOIN inventario_tiquete it ON it.id_ = p.codigo_inventario_tiquete 
                                INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie 
                                INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = p.codigo_jornada 
                                INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = p.codigo_ruta 
                                INNER JOIN transporte_colectivo tc ON tc.id_ = p.codigo_transporte_colectivo
                                INNER JOIN catalogo_estatus cat_estatus ON cat_estatus.codigo = p.codigo_estatus
                                WHERE p.id_ = '$numero_control[$ab]'
                                        ORDER BY p.id_";
                                $consulta = $dblink -> query($query);
                        // Crear matriz para poder tomar dos valores

                        // Recorriendo la Tabla con PDO::
                            while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                            {
                                $fecha = $listado['fecha'];
                                $fecha_ = cambiaf_a_normal($listado["fecha"]);
                                $codigo_produccion = $listado['id_'];

                                $descripcion_ruta = $listado['descripcion_ruta'];
                                $numero_equipo = $listado['numero_equipo'];
                                $codigo_jornada = $listado['codigo_jornada'];
                                $numero_placa = $listado['numero_placa'];
                                $nombre_serie = $listado['nombre_serie'];
                                $codigo_personal = $listado['codigo_personal'];
                                $nombre_motorista = $listado['nombre_motorista'];
                                $total_ingreso = $listado['total_ingreso'];
                                // TOTAL INGRESO
                                    $totalIngresoOK = $totalIngresoOK + $total_ingreso;
                                    $totalIngresoOKPantalla = $totalIngresoOK;
                            // DAR VALORES A VARIABLES.
                                // ESTILO
                                $estilo_l = 'style="padding: 0px; font-size: medium; text-align: left;"';
                                $estilo_c = 'style="padding: 0px; font-size: medium; text-align: center;"';
                                $estilo_r = 'style="padding: 0px; font-size: medium;  text-align: right;"';
                                $estilo_r_green = 'style="padding: 0px; font-size: medium; color:green; text-align: right;"';
                                // CUANDO YA SE HA CALCULADO LA PRODUCCIÓN ASIGNADA.
                                // Validar si hay registros.
                                    $contenidoOK .= "<tr>
                                    <td $estilo_c><a data-accion=ProduccionVerAsignacion data-toggle=tooltip data-placement=left title=Imprimir href=$codigo_produccion><i class='fad fa-search fa-lg'></i></a>
                                    <td $estilo_l>$codigo_produccion
                                    <td $estilo_l>$descripcion_ruta
                                    <td $estilo_c>$numero_equipo | $numero_placa
                                    <td $estilo_l>$codigo_personal | $nombre_motorista
                                    <td $estilo_r_green>$ $total_ingreso
                                    <td>
                                    ";
                            }   // WHILE DE LA CONSULTA PRODUCCIÓN.
                        }   // forDEL NUMERO CONTROL PARA OBETENER EL DATO CORRECTO DEL CONSOLIDADO DETALLES DE LA RUTA.
                        $respuestaOK = true;
                        $mensajeError = "Producción | Detalle Encontrada.";
                break;  
                case 'BuscarProduccionPorIdTabla':
                    $codigo_produccion = trim($_POST['codigo_produccion']);
                    $respuestaOK = true;
                    $mensajeError = "Producción Encontrada";
                    ListadoAsignado();
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
    $AccionBuscar = $_POST["accion"];
	if($AccionBuscar === "BuscarTodos" or $AccionBuscar === "" or $AccionBuscar === "BuscarTodosUnidadPlaca" or $AccionBuscar === "BuscarPorMotorista"){
		echo json_encode($arreglo);	
	}elseif(
        $AccionBuscar === "BuscarCodigo" or $AccionBuscar === "BuscarPersonalMotorista" or $AccionBuscar === "EditarRegistro" 
        ){
		echo json_encode($datos);
		}
	else{
		// Armamos array para convertir a JSON
		$salidaJson = array("respuesta" => $respuestaOK,
			"mensaje" => $mensajeError,
            "contenido" => $contenidoOK,
            "totalProduccion" => $totalProduccionOK,
            "cantidadTiquete" => $CantidadtiqueteOK,
            "totalIngreso" => number_format($totalIngresoOKPantalla,2),
            "totalProduccionIngreso" => $ProduccionTotalIngresoOkPantalla,
            "cantidadTiquetePantalla" => number_format($cantidadTiquetePantalla,0),
            "nombreMotorista" => $nombre_motorista,
            "codigoPersonal" => $codigo_personal,
            "url_foto" => $url_foto,
            "codigo_genero" => $codigo_genero,
            "descripcionRuta" => $descripcion_ruta_rg,
            "descripcionUnidad" => $numero_equipo . ' | ' . $numero_placa,
            "precioPublico" => $precio_publico_,
            "cantidadProduccionVendidos" => $cantidadVendidosProduccion,
            "fecha" => $fecha
        );
		echo json_encode($salidaJson);
    }



    function ListadoAsignado(){
        global $id_produccion, $dblink, $contenidoOK, $codigo_produccion, $totalIngresoOK, $respuestaOK, $mensajeError, $CantidadtiqueteOK, $totalIngresoOKPantalla, $nombre_motorista, $codigo_personal,
        $descripcion_ruta_rg, $numero_equipo, $numero_placa, $precio_publico, $precio_publico_, $fecha, $url_foto, $codigo_genero; 
        // consulta.
        $query_c = "SELECT p.id_ AS id_produccion, p.fecha, p.codigo_inventario_tiquete, 
                cat_ts.descripcion as nombre_serie, 
                pa.id_ as id_produccion_asignado, pa.tiquete_desde, pa.tiquete_hasta, pa.total, pa.procesado, pa.cantidad, pa.total, pa.codigo_estatus, pa.tiquete_cola,
                btrim(cat_j.descripcion || CAST(': ' AS VARCHAR) || cat_j.hora_desde || CAST(' - ' AS VARCHAR) || cat_j.hora_hasta) as descripcion_jornada, 
                cat_r.descripcion as descripcion_ruta,
                it.precio_publico,
                cat_e.descripcion as descripcion_estatus,
                btrim(per.nombres || CAST(' ' AS VARCHAR) || per.apellidos) as nombre_motorista, per.codigo as codigo_personal, per.foto, per.codigo_genero,
                cat_t_c.numero_placa as numero_placa, cat_t_c.numero_equipo as numero_equipo
                    FROM produccion p 
                        INNER JOIN personal per ON per.codigo = p.codigo_personal
                        INNER JOIN produccion_asignado pa ON pa.codigo_produccion = p.id_ 
                        INNER JOIN inventario_tiquete it ON it.id_ = p.codigo_inventario_tiquete 
                        INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie 
                        INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = p.codigo_jornada 
                        INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = p.codigo_ruta 
                        INNER JOIN transporte_colectivo cat_t_c ON cat_t_c.id_ = p.codigo_transporte_colectivo
                        INNER JOIN catalogo_estatus cat_e ON cat_e.codigo = pa.codigo_estatus
                            WHERE pa.codigo_produccion = '$codigo_produccion'
                            ORDER BY pa.id_, p.codigo_inventario_tiquete";
        // Ejecutamos el query
            $consulta = $dblink -> query($query_c);              
        // obtener el último dato en este caso el Id_
            while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
            {
                $id_pro_a = trim($listado['id_produccion_asignado']);		// dato de la tabla produccion.
                $pa_codigo_produccion = trim($listado['id_produccion']);    // dato de la tabla produccion_asignacion
                $nombre_serie = trim($listado['nombre_serie']);		// produccion correlativo.
                $tiquete_cola = trim($listado['tiquete_cola']);   // produccion correlativo
                $tiquete_desde = trim($listado['tiquete_desde']);   // produccion correlativo
                $tiquete_hasta = trim($listado['tiquete_hasta']);   // produccion correlativo
                $total = trim($listado['total']);
                $cantidad = trim($listado['cantidad']);
                $fecha = cambiaf_a_normal(trim($listado['fecha']));
                $precio_publico = trim($listado['precio_publico']);
                $precio_publico_ = trim($listado['precio_publico']);
                $procesado = trim($listado['procesado']);
                $codigo_estatus = trim($listado['codigo_estatus']);
                $descripcion_estatus = trim($listado['descripcion_estatus']);
                $descripcion_ruta_rg = trim($listado['descripcion_ruta']);
                $nombre_motorista = trim($listado['nombre_motorista']);
                $codigo_personal = trim($listado['codigo_personal']);
                $url_foto = trim($listado['foto']);
                $codigo_genero = trim($listado['codigo_genero']);
                $numero_equipo = trim($listado['numero_equipo']);
                $numero_placa = trim($listado['numero_placa']);
                $estilo = ""; // definimos el estilo de cada elmento encontrado en codigo_esttratus.
    
                // variable armanda para posteriormente actualizar en <produccion_correlativo.
                    $todos = $id_pro_a . "#" . $pa_codigo_produccion . "#" . $tiquete_desde . "#" . $tiquete_hasta . "#" . $fecha . "#" . $precio_publico . "#" . $cantidad . "#" . $total . "#" . $tiquete_cola;                // Variables que pasa  a la tabla.s
                    $estilo_l = 'style="padding: 0px; font-size: medium; text-align: left;"';
                    $estilo_c = 'style="padding: 0px; font-size: medium; text-align: center;"';
                    $estilo_r = 'style="padding: 0px; font-size: medium; text-align: right;"';
                    $estilo_cola = 'style="padding: 0px; font-size: medium; text-align: right; font-weight: bold;"';
                    //"flat-red" checked="" style="position: absolute; opacity: 0;"><ins class="iCheck-helper" style="position: absolute; top: 0%; left: 0%; display: block; width: 100%; height: 100%; margin: 0px; padding: 0px; background: rgb(255, 255, 255); border: 0px; opacity: 0;"';

                // cambiar color al estatus 04= Devolución , y 05= Vendido.
                    if($codigo_estatus == "04"){
                        $estilo = 'class="text-danger font-weight-bold" ' . 'style="padding: 0px; font-size: medium; color:black; text-align: right;"';
                    }
                    if($codigo_estatus == "05"){
                        $estilo = 'class="text-primary font-weight-bold" ' . 'style="padding: 0px; font-size: medium; color:black; text-align: right;"';
                    }
                //
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
                        $totalIngresoOKPantalla = number_format($totalIngresoOK,2);
                    }
                    // Ver el listado produccion asigando.
                    $respuestaOK = true;
                    $mensajeError = "Producción Encontrada.";
                }else{
                    // EN DONDE NO SE HA CALCULADO LA PRODUCCIÓN ASIGNADA.
                    $contenidoOK .= "<tr>
                    <td $estilo_l><a data-accion=EditarAsignacion data-toggle=tooltip data-placement=left title=Modificar href='$todos'>Editar</a>
                    <td $estilo_l><input type=hidden value=$todos name=CalcularA>
                    <td style='padding: 0px; zoom: 1.5'><input type=checkbox checked data-toggle=tooltip data-placement=left title=Entregado>
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
                }
            }
    }   
    
    function ListadoPorCodigoPersonal(){
        global $dblink, $contenidoOK, $codigo_personal, $totalIngresoOK, $respuestaOK, $mensajeError, $CantidadtiqueteOK, $totalIngresoOKPantalla, $codigo_personal,
        $descripcion_ruta_pm, $numero_equipo, $numero_placa, $precio_publico, $fecha_desde, $fecha_hasta, $OptBuscarPM, $fecha, $arreglo; 
        // Condicionar la consulta.
        if($OptBuscarPM == "Fecha"){
        // consulta. sólo código personal
            $query_c = "SELECT SUM(pro.total_ingreso) AS total_ingreso_por_bus, pro.codigo_ruta, pro.codigo_transporte_colectivo, pro.id_,
                    to_char(pro.fecha,'dd/mm/yyyy') as fecha_, per.foto, per.codigo_genero,
                    per.codigo, pro.codigo_tiquete_color, cat_tc.precio_publico, 
                    btrim(per.nombres || CAST(' ' AS VARCHAR) || per.apellidos) as nombre_motorista,
                    cat_r.descripcion as descripcion_ruta, tc.numero_placa, tc.numero_equipo, btrim(tc.numero_equipo || CAST(' | ' AS VARCHAR) || tc.numero_placa) as numero_equipo_placa,
                    CAST(SUM(pro.total_ingreso)/cat_tc.precio_publico AS INTEGER) as cantidadTiquete
                        FROM produccion pro
                            INNER JOIN personal per ON per.codigo = pro.codigo_personal
                            INNER JOIN catalogo_tiquete_color cat_tc ON cat_tc.id_ = pro.codigo_tiquete_color
                            INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = pro.codigo_ruta
                            INNER JOIN transporte_colectivo tc ON tc.id_ = pro.codigo_transporte_colectivo
                                WHERE per.codigo = '$codigo_personal' and fecha >= '$fecha_desde' and fecha <= '$fecha_hasta'
                                    GROUP BY per.codigo, pro.codigo_tiquete_color, 
                                    cat_tc.precio_publico, pro.codigo_ruta, pro.codigo_transporte_colectivo, pro.fecha, descripcion_ruta,
                                    tc.numero_placa, tc.numero_equipo, pro.id_, per.nombres, per.apellidos, per.foto, per.codigo_genero
                                    ORDER BY pro.fecha, pro.codigo_ruta, pro.id_ asc";
        }else if($OptBuscarPM == "Todo"){
            // consulta. sólo código personal
           $query_c = "SELECT SUM(pro.total_ingreso) AS total_ingreso_por_bus, pro.codigo_ruta, pro.codigo_transporte_colectivo, pro.id_,
            to_char(pro.fecha,'dd/mm/yyyy') as fecha_, per.foto, per.codigo_genero,
            per.codigo, pro.codigo_tiquete_color, cat_tc.precio_publico, 
            btrim(per.nombres || CAST(' ' AS VARCHAR) || per.apellidos) as nombre_motorista,
            cat_r.descripcion as descripcion_ruta, tc.numero_placa, tc.numero_equipo, btrim(tc.numero_equipo || CAST(' | ' AS VARCHAR) || tc.numero_placa) as numero_equipo_placa,
            CAST(SUM(pro.total_ingreso)/cat_tc.precio_publico AS INTEGER) as cantidadTiquete
                FROM produccion pro
                    INNER JOIN personal per ON per.codigo = pro.codigo_personal
                    INNER JOIN catalogo_tiquete_color cat_tc ON cat_tc.id_ = pro.codigo_tiquete_color
                    INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = pro.codigo_ruta
                    INNER JOIN transporte_colectivo tc ON tc.id_ = pro.codigo_transporte_colectivo
                        WHERE per.codigo = '$codigo_personal' 
                            GROUP BY pro.id_ ,per.codigo, pro.codigo_tiquete_color, 
                            cat_tc.precio_publico, pro.codigo_ruta, pro.codigo_transporte_colectivo, pro.fecha, descripcion_ruta,
                            tc.numero_placa, tc.numero_equipo,  per.nombres, per.apellidos, per.foto, per.codigo_genero
                            ORDER BY pro.fecha, pro.codigo_ruta, pro.id_ asc";
        }
        // Ejecutamos el query
            $consulta = $dblink -> query($query_c);              
        // obtener el último dato en este caso el Id_
        	// Validar si hay registros.
		if($consulta -> rowCount() != 0){  
            while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
            {
                $arreglo["data"][] = $listado;						
                //
                $precio_publico = trim($listado['precio_publico']);
                $IngresoDiario = trim($listado['total_ingreso_por_bus']);
                $foto = trim($listado["foto"]);
                $codigo_genero = trim($listado["codigo_genero"]);
                $CantidadTiquete = round($IngresoDiario / $precio_publico,0);
                $totalIngresoOK = $totalIngresoOK + $IngresoDiario;
                $CantidadtiqueteOK = $CantidadtiqueteOK + $CantidadTiquete;    // esto servirá para restar de la existencia.
                //  variables a pantalla
                $arreglo[1]["dataTotalIngreso"] = number_format($totalIngresoOK,2,".",",");			
                $arreglo[1]["dataTotalTiquete"] = number_format($CantidadtiqueteOK,0,".",",");
                $arreglo[1]["codigo_genero"] = trim($listado["codigo_genero"]);
                //	default imagen masculino
                $arreglo[1]["foto"] = '../acomtus/img/avatar_masculino.png';
                // validar
                    if(is_null($foto) || $foto == "" || $foto == " "){
                        if($codigo_genero == '02'){	//	femenino
                            $arreglo[1]["foto"] = '../acomtus/acomtus/img/avatar_femenino.png';
                        }
                    }else{
                        // foto del empleado.
                        $arreglo[1]["foto"] = "../acomtus/img/fotos/".$foto;
                    }
            }   // FIN DEL WHILE.
        }else{
                    // Ver el listado produccion asigando.
                // Inicializando el array                
                $arreglo["data"]["Fecha"] = "";
                $arreglo["data"]["Control"] = "";
                $arreglo["data"]["NumeroEquipoYPlaca"] = "";
                $arreglo["data"]["Ruta"] = "";
                $arreglo["data"]["PU"] = "";
                $arreglo["data"]["Tiquete"] = "";
                $arreglo["data"]["Ingresos"] = "";
                //  variables a pantalla
                $arreglo[1]["dataTotalIngreso"] = "";			
                $arreglo[1]["dataTotalTiquete"] = "";
                $arreglo[1]["foto"] = "";
                $arreglo[1]["codigo_genero"] = "";
        }   // FIN DEL IF QUE COMPRUEBA SI HAY REGISTROS EN LA CONSULTA.
    }   
    function ListadoPorUnidadTransporte(){
        global $dblink, $contenidoOK, $codigo_up, $totalIngresoOK, $respuestaOK, $mensajeError, $CantidadtiqueteOK, $totalIngresoOKPantalla, $codigo_up,
        $descripcion_ruta_pm, $numero_equipo, $numero_placa, $precio_publico, $fecha_desde, $fecha_hasta, $OptBuscarUP, $fecha, $datos, $arreglo; 
        // Condicionar la consulta.
        if($OptBuscarUP == "Fecha"){
        // consulta. sólo código personal
        $query_c = "SELECT SUM(pro.total_ingreso) AS total_ingreso_por_bus, pro.codigo_ruta, pro.codigo_transporte_colectivo, pro.id_,
                    to_char(pro.fecha,'dd/mm/yyyy') as fecha_,
                    per.codigo, pro.codigo_tiquete_color, cat_tc.precio_publico, 
                    btrim(per.nombres || CAST(' ' AS VARCHAR) || per.apellidos) as nombre_motorista,
                    cat_r.descripcion as descripcion_ruta, tc.numero_placa, tc.numero_equipo,
                    CAST(SUM(pro.total_ingreso)/cat_tc.precio_publico AS INTEGER) as cantidadTiquete
                        FROM produccion pro
                            INNER JOIN personal per ON per.codigo = pro.codigo_personal
                            INNER JOIN catalogo_tiquete_color cat_tc ON cat_tc.id_ = pro.codigo_tiquete_color
                            INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = pro.codigo_ruta
                            INNER JOIN transporte_colectivo tc ON tc.id_ = pro.codigo_transporte_colectivo
                                WHERE pro.codigo_transporte_colectivo = '$codigo_up' and fecha >= '$fecha_desde' and fecha <= '$fecha_hasta'
                                    GROUP BY per.codigo, pro.codigo_tiquete_color, 
                                    cat_tc.precio_publico, pro.codigo_ruta, pro.codigo_transporte_colectivo, pro.fecha, descripcion_ruta,
                                    tc.numero_placa, tc.numero_equipo, pro.id_, per.nombres, per.apellidos
                                    ORDER BY pro.fecha, pro.codigo_ruta, pro.id_ asc";
        }else if($OptBuscarUP == "Todo"){
        // consulta. sólo código personal
            $query_c = "SELECT SUM(pro.total_ingreso) AS total_ingreso_por_bus, pro.codigo_ruta, pro.codigo_transporte_colectivo, pro.id_,
                    to_char(pro.fecha,'dd/mm/yyyy') as fecha_,
                    per.codigo, pro.codigo_tiquete_color, cat_tc.precio_publico, 
                    btrim(per.nombres || CAST(' ' AS VARCHAR) || per.apellidos) as nombre_motorista,
                    cat_r.descripcion as descripcion_ruta, tc.numero_placa, tc.numero_equipo,
                    CAST(SUM(pro.total_ingreso)/cat_tc.precio_publico AS INTEGER) as cantidadTiquete
                        FROM produccion pro
                            INNER JOIN personal per ON per.codigo = pro.codigo_personal
                            INNER JOIN catalogo_tiquete_color cat_tc ON cat_tc.id_ = pro.codigo_tiquete_color
                            INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = pro.codigo_ruta
                            INNER JOIN transporte_colectivo tc ON tc.id_ = pro.codigo_transporte_colectivo
                                WHERE pro.codigo_transporte_colectivo = '$codigo_up' 
                                    GROUP BY pro.id_ ,per.codigo, pro.codigo_tiquete_color, 
                                    cat_tc.precio_publico, pro.codigo_ruta, pro.codigo_transporte_colectivo, pro.fecha, descripcion_ruta,
                                    tc.numero_placa, tc.numero_equipo,  per.nombres, per.apellidos
                                    ORDER BY pro.fecha, pro.codigo_ruta, pro.id_ asc";
        }
        // Ejecutamos el query
            $consulta = $dblink -> query($query_c);  
        //
            $datos=array(); $fila_array = 0;            
        // obtener el último dato en este caso el Id_
        	// Validar si hay registros.
		if($consulta -> rowCount() != 0){  
            while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
            {
                $arreglo["data"][] = $listado;						
                //
                    $precio_publico = trim($listado['precio_publico']);
                    $IngresoDiario = trim($listado['total_ingreso_por_bus']);
                    $CantidadTiquete = round($IngresoDiario / $precio_publico,0);
                    $totalIngresoOK = $totalIngresoOK + $IngresoDiario;
                    $CantidadtiqueteOK = $CantidadtiqueteOK + $CantidadTiquete;    // esto servirá para restar de la existencia.
                    //  variables a pantalla
                    $arreglo[1]["dataTotalIngreso"] = number_format($totalIngresoOK,2,".",",");			
                    $arreglo[1]["dataTotalTiquete"] = number_format($CantidadtiqueteOK,0,".",",");
            }   // FIN DEL WHILE.
        }else{
                    // Ver el listado produccion asigando.
                // Inicializando el array                
                $arreglo["data"]["Fecha"] = "";
                $arreglo["data"]["Control"] = "";
                $arreglo["data"]["NombreEmpleado"] = "";
                $arreglo["data"]["Ruta"] = "";
                $arreglo["data"]["PU"] = "";
                $arreglo["data"]["Tiquete"] = "";
                $arreglo["data"]["Ingresos"] = "";
                //  variables a pantalla
                $arreglo[1]["dataTotalIngreso"] = "";			
                $arreglo[1]["dataTotalTiquete"] = "";
        }   // FIN DEL IF QUE COMPRUEBA SI HAY REGISTROS EN LA CONSULTA.
    }   
?>
