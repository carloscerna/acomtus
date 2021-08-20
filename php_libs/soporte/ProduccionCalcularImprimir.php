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
$ProduccionDesdeHasta = "";
$lista = "";
$arreglo = array();
$datos = array();
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
		    case 'BuscarProduccionPorRuta':
                # buscar en la tabla catalogo_ruta.
                    $fecha = $_POST["fecha"];
                    $fecha_ = cambiaf_a_normal($_POST["fecha"]);
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
               /* $query = "SELECT it.id_, it.codigo_serie, it.precio_publico, it.existencia,
                        cat_ts.descripcion as nombre_serie,
                        cat_tc.descripcion as tiquete_color
                        FROM inventario_tiquete it
                        INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie
                        INNER JOIN catalogo_tiquete_color cat_tc On cat_tc.id_ = it.codigo_tiquete_color
                            ORDER BY precio_publico";*/
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
                        /*  $query = "SELECT pro.id_, pro.total_ingreso, pro.codigo_ruta
                        FROM produccion pro WHERE fecha = '$fecha' and concat(codigo_ruta,codigo_inventario_tiquete) = '$codigo_ruta_precio[$Hj]'
                        ORDER BY pro.codigo_ruta, pro.id_ ASC";*/
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
                                $query_t_d_c = "SELECT * FROM produccion_asignado 
                                    WHERE fecha = '$fecha' and codigo_produccion = '$ProduccionDesdeHasta[$ab]'
                                        ORDER by id_";
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
                            }

                            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                            /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                        }   // IF DE LA CONSULTA PRODUCCION POR RUTA..
                    // DAR VALORES A VARIABLES.
                        // ESTILO
                        $estilo_l = 'style="padding: 0px; font-size: medium; color:blue; text-align: left;"';
                        $estilo_c = 'style="padding: 0px; font-size: medium; color:blue; text-align: center;"';
                        $estilo_r = 'style="padding: 0px; font-size: medium; color:blue; text-align: right;"';
                        $estilo_r_green = 'style="padding: 0px; font-size: medium; color:green; text-align: right;"';
                        // CUANDO YA SE HA CALCULADO LA PRODUCCIÓN ASIGNADA.
                        // Validar si hay registros.
						if($consulta -> rowCount() != 0){
                            // convertir la matriz $ProduccionDesdeHasta
                            $separado_por_comas = implode(",", $ProduccionDesdeHasta);

                            $contenidoOK .= "<tr>
                            <td $estilo_c><a data-accion=ProduccionImprimir data-toggle=tooltip data-placement=left title=Imprimir href=$separado_por_comas><i class='fad fa-search fa-lg'></i></a>
                            <td $estilo_l>$fecha_
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
                    // 
                    $respuestaOK = true;
                    $mensajeError = "Producción Encontrada.";
                break;   

                case 'BuscarProduccionPorId':
                    // TABS-1 - tabla produccion.
                        $codigo_produccion = trim($_POST['codigo_produccion']);
                    // 	validar la fecha de la producción.
                        $numero_control = explode(",",$codigo_produccion);
                        //$codigo_produccion_desde = $fechas[0];
                        //$codigo_produccion_hasta = $fechas[1];
                    // convertir string a matriz.
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
                                            $totalIngresoOKPantalla = number_format($totalIngresoOK,2);
                                    // DAR VALORES A VARIABLES.
                                        // ESTILO
                                        $estilo_l = 'style="padding: 0px; font-size: medium; color:blue; text-align: left;"';
                                        $estilo_c = 'style="padding: 0px; font-size: medium; color:blue; text-align: center;"';
                                        $estilo_r = 'style="padding: 0px; font-size: medium; color:blue; text-align: right;"';
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
	if($_POST["accion"] === "BuscarTodos" or $_POST["accion"] === ""){
		echo json_encode($arreglo);	
	}elseif(
        $_POST["accion"] === "BuscarCodigo" or $_POST["accion"] === "BuscarPersonalMotorista" or $_POST["accion"] === "EditarRegistro"
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
            "totalIngreso" => $totalIngresoOKPantalla,
            "totalProduccionIngreso" => $ProduccionTotalIngresoOkPantalla,
            "cantidadTiquetePantalla" => number_format($cantidadTiquetePantalla,0),
            "produccionDesdeHasta" => $ProduccionDesdeHasta
        );
		echo json_encode($salidaJson);
    }



    function ListadoAsignado(){
        global $id_produccion, $dblink, $contenidoOK, $codigo_produccion, $totalIngresoOK, $respuestaOK, $mensajeError, $CantidadtiqueteOK, $totalIngresoOKPantalla;
        // consulta.
     $query_c = "SELECT p.id_ AS id_produccion, p.fecha, p.codigo_inventario_tiquete, 
                cat_ts.descripcion as nombre_serie, 
                pa.id_ as id_produccion_asignado, pa.tiquete_desde, pa.tiquete_hasta, pa.total, pa.procesado, pa.cantidad, pa.total, pa.codigo_estatus, pa.tiquete_cola,
                btrim(cat_j.descripcion || CAST(': ' AS VARCHAR) || cat_j.hora_desde || CAST(' - ' AS VARCHAR) || cat_j.hora_hasta) as descripcion_jornada, 
                cat_r.descripcion as descripcion_ruta,
                it.precio_publico,
                cat_e.descripcion as descripcion_estatus
                    FROM produccion p 
                        INNER JOIN produccion_asignado pa ON pa.codigo_produccion = p.id_ 
                        INNER JOIN inventario_tiquete it ON it.id_ = p.codigo_inventario_tiquete 
                        INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie 
                        INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = p.codigo_jornada 
                        INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = p.codigo_ruta 
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
                        $estilo = 'class="text-danger font-weight-bold" ' . 'style="padding: 0px; font-size: large; color:blue; text-align: right;"';
                    }
                    if($codigo_estatus == "05"){
                        $estilo = 'class="text-primary font-weight-bold" ' . 'style="padding: 0px; font-size: large; color:blue; text-align: right;"';
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
                    $mensajeError = "Cálculo realizado";
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
?>
