<?php
// limpiar cache.
clearstatcache();
// cambiar a utf-8.
header("Content-Type: text/html;charset=iso-8859-1");

// Inicializamos variables de mensajes y JSON
$respuestaOK = false;
$mensajeError = "No se puede ejecutar la aplicación";
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
$ProduccionDesdeHasta = [];
$codigo_personal = "";
$numero_equipo = "";
$numero_placa = "";
$precio_publico_ = [];
$lista = "";
$nombre_motorista = "";
$arreglo = [];
$datos = [];
$listado = ["0","1","2","3","4","5","6","7"];
$fecha_desde = "";
$fecha_hasta = "";
$OptBuscarPM = "";
$descripcion_ruta_pm = "";
$descripcion_ruta_rg = "";
$url_foto = "";
$codigo_genero = "";

// ruta de los archivos con su carpeta
$path_root = trim($_SERVER['DOCUMENT_ROOT'] ?? '');    

// Incluimos el archivo de funciones y conexión a la base de datos
include($path_root."/acomtus/includes/mainFunctions_conexion.php");
include($path_root."/acomtus/includes/funciones.php");

// Validar conexión con la base de datos
if(isset($errorDbConexion) && $errorDbConexion == false){
	
	if($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_POST) || !empty($_REQUEST)){
        // Usar operador coalescente para PHP 8
        $accion = $_POST['accion_buscar'] ?? $_POST['accion'] ?? $_REQUEST['accion'] ?? '';
		
		// Verificamos las variables de acción
		switch ($accion) {
		    case 'BuscarProduccionPorRuta':
                $fecha = $_POST["fecha"] ?? '';
                $fecha_ = cambiaf_a_normal($fecha);
                
                // Sentencia preparada para contar vendidos
                $stmt_v = $dblink->prepare("SELECT count(*) as total_vendidos FROM produccion where codigo_estatus = '02' and fecha = :fecha");
                $stmt_v->execute([':fecha' => $fecha]);
                
                $cantidadVendidosProduccion = 0;
                if($stmt_v->rowCount() != 0) {
                    while($listado_v = $stmt_v->fetch(PDO::FETCH_BOTH)) {
                        $cantidadVendidosProduccion = (int)$listado_v['total_vendidos'];
                    }
                    
                    if($cantidadVendidosProduccion != 0){
                        // CATALOGO RUTA
                        $query_ruta = "SELECT id_ruta, codigo, descripcion FROM catalogo_ruta ORDER BY codigo";
                        $consulta_ruta = $dblink->query($query_ruta);
                        $codigo_ruta = []; $descripcion_ruta = [];
                        
                        while($listado = $consulta_ruta->fetch(PDO::FETCH_BOTH)) {
                            $codigo_ruta[] = $listado['id_ruta'];
                            $descripcion_ruta[] = $listado['descripcion'];
                        }
                        
                        // INVENTARIO TIQUETE
                        $query_tiq = "SELECT DISTINCT cat_tc.id_ as id_tiquete_color, cat_tc.descripcion as tiquete_color, it.precio_publico
                                      FROM catalogo_tiquete_color cat_tc
                                      INNER JOIN inventario_tiquete it ON cat_tc.id_ = it.codigo_tiquete_color
                                      ORDER BY it.precio_publico";
                        $consulta_tiq = $dblink->query($query_tiq);
                        
                        $id_tiquete_color = []; $precio_publico = []; $tiquete_color = [];
                        while($listado = $consulta_tiq->fetch(PDO::FETCH_BOTH)) {
                            $id_tiquete_color[] = $listado['id_tiquete_color'];
                            $precio_publico[] = (float)$listado['precio_publico'];
                            $tiquete_color[] = $listado['tiquete_color'];
                        }
                        
                        // CREAR MATRIZ RUTA-PRECIO
                        $codigo_ruta_precio = []; $descripcion_ruta_ = []; $precio_publico_ = [];
                        for ($Hj=0; $Hj < count($codigo_ruta); $Hj++) { 
                            for ($jj=0; $jj < count($precio_publico); $jj++) {   
                                $codigo_ruta_precio[] = $codigo_ruta[$Hj] . $id_tiquete_color[$jj];
                                $descripcion_ruta_[] = $descripcion_ruta[$Hj];
                                $precio_publico_[] = $precio_publico[$jj];
                            }
                        }
                        
                        // Preparar consultas fuera del bucle para máximo rendimiento
                        $stmt_pro = $dblink->prepare("SELECT pro.id_, pro.total_ingreso, pro.codigo_ruta 
                                                      FROM produccion pro 
                                                      WHERE fecha = :fecha and concat(codigo_ruta,codigo_tiquete_color) = :codigo_ruta_precio 
                                                      ORDER BY pro.codigo_ruta, pro.id_ ASC");
                        
                        $stmt_td1 = $dblink->prepare("SELECT * FROM produccion_asignado 
                                                      WHERE codigo_estatus = '05' and fecha = :fecha and tiquete_cola > 0 and codigo_produccion = :codigo_produccion 
                                                      ORDER by id_");
                                                      
                        $stmt_td2 = $dblink->prepare("SELECT sum(cantidad) as cantidad FROM produccion_asignado 
                                                      WHERE codigo_estatus = '03' and fecha = :fecha and codigo_produccion = :codigo_produccion");
                                                      
                        $stmt_td3 = $dblink->prepare("SELECT sum(cantidad) as cantidad FROM produccion_asignado 
                                                      WHERE codigo_estatus = '04' and fecha = :fecha and codigo_produccion = :codigo_produccion");
                                                      
                        $stmt_td4 = $dblink->prepare("SELECT tiquete_desde, tiquete_hasta, cantidad, (tiquete_hasta-tiquete_desde)+1 as entregados 
                                                      FROM produccion_asignado 
                                                      WHERE fecha = :fecha and codigo_produccion = :codigo_produccion");

                        // ESTILOS
                        $estilo_l = 'style="padding: 0px; font-size: medium; text-align: left;"';
                        $estilo_c = 'style="padding: 0px; font-size: medium; text-align: center;"';
                        $estilo_r = 'style="padding: 0px; font-size: medium; text-align: right;"';
                        $estilo_r_green = 'style="padding: 0px; font-size: medium; color:green; text-align: right;"';

                        for ($Hj=0; $Hj < count($codigo_ruta_precio); $Hj++) {
                            $stmt_pro->execute([':fecha' => $fecha, ':codigo_ruta_precio' => $codigo_ruta_precio[$Hj]]);
                            
                            $ProduccionIngresoOk = 0; $cantidadTiquete = 0;
                            
                            if($stmt_pro->rowCount() != 0) {
                                $ProduccionDesdeHasta = []; 
                                
                                while($listado = $stmt_pro->fetch(PDO::FETCH_BOTH)) {
                                    $ProduccionDesdeHasta[] = $listado["id_"];
                                    $ProduccionIngresoOk += (float)$listado["total_ingreso"];
                                }   
                                
                                $cantidadTiquete = round($ProduccionIngresoOk / $precio_publico_[$Hj], 2);
                                $cantidadTiquetePantalla += $cantidadTiquete;
                                $ProduccionCantidad = count($ProduccionDesdeHasta);
                                $totalProduccionOK += $ProduccionCantidad;
                                $ProduccionTotalIngresoOk = round($ProduccionTotalIngresoOk + $ProduccionIngresoOk, 2);
                                
                                $ProduccionIngresoOkPantalla = number_format($ProduccionIngresoOk,2);
                                $ProduccionTotalIngresoOkPantalla = number_format($ProduccionTotalIngresoOk,2);
                                
                                $cantidadTiqueteDevolucion = 0; $cantidadTiqueteEntregados = 0; 
                                
                                for ($ab=0; $ab < count($ProduccionDesdeHasta); $ab++) {
                                    $c_prod = $ProduccionDesdeHasta[$ab];
                                    
                                    // 05
                                    $stmt_td1->execute([':fecha' => $fecha, ':codigo_produccion' => $c_prod]);
                                    while($listados = $stmt_td1->fetch(PDO::FETCH_BOTH)) {
                                        $cantidadTiqueteDevolucion += ($listados["tiquete_hasta"] - $listados["tiquete_cola"]) + 1;
                                    }
                                    
                                    // 03
                                    $stmt_td2->execute([':fecha' => $fecha, ':codigo_produccion' => $c_prod]);
                                    if($listados_ = $stmt_td2->fetch(PDO::FETCH_BOTH)) {
                                        $cantidadTiqueteDevolucion += (float)$listados_["cantidad"];
                                    }
                                    
                                    // 04
                                    $stmt_td3->execute([':fecha' => $fecha, ':codigo_produccion' => $c_prod]);
                                    if($listados_ = $stmt_td3->fetch(PDO::FETCH_BOTH)) {
                                        $cantidadTiqueteDevolucion += (float)$listados_["cantidad"];
                                    }
                                    
                                    // ENTREGADOS
                                    $stmt_td4->execute([':fecha' => $fecha, ':codigo_produccion' => $c_prod]);
                                    while($listados_c = $stmt_td4->fetch(PDO::FETCH_BOTH)) {
                                        $cantidadTiqueteEntregados += (float)$listados_c["entregados"];
                                    }
                                } 
                                
                                $separado_por_comas = implode(",", $ProduccionDesdeHasta);
                                $contenidoOK .= "<tr>
                                <td $estilo_c><a data-accion=ProduccionImprimir data-toggle=tooltip title=Imprimir href=$separado_por_comas><i class='fad fa-search fa-lg'></i></a>
                                <td $estilo_l>
                                <td $estilo_l>{$descripcion_ruta_[$Hj]}
                                <td $estilo_r><input type=button class='btn btn-info btn-md' value='#' title='$separado_por_comas'>
                                <td $estilo_c>$ProduccionCantidad
                                <td $estilo_c>$cantidadTiqueteEntregados
                                <td $estilo_c>$cantidadTiqueteDevolucion
                                <td $estilo_c>$cantidadTiquete
                                <td $estilo_c>$ {$precio_publico_[$Hj]}
                                <td $estilo_r_green>$ $ProduccionIngresoOkPantalla
                                <td>";
                                
                                $cantidadTiqueteEntregadosPantalla += $cantidadTiqueteEntregados;
                            }
                        }
                        
                        // GUARDAR EN LA TABLA PRODUCCION_DIARIO
                        $stmt_ps = $dblink->prepare("SELECT id_ FROM produccion_diaria WHERE fecha = :fecha");
                        $stmt_ps->execute([':fecha' => $fecha]);
                        
                        $total_colones = round($ProduccionTotalIngresoOk * 8.75, 2);
                        
                        if($stmt_ps->rowCount() != 0){  
                            $stmt_pd = $dblink->prepare("UPDATE produccion_diaria SET total_dolares = :tdol, total_colones = :tcol WHERE fecha = :fecha");
                            $stmt_pd->execute([':tdol' => $ProduccionTotalIngresoOk, ':tcol' => $total_colones, ':fecha' => $fecha]);
                        }else{
                            $stmt_pd = $dblink->prepare("INSERT INTO produccion_diaria (fecha, total_dolares, total_colones) VALUES (:fecha, :tdol, :tcol)");
                            $stmt_pd->execute([':fecha' => $fecha, ':tdol' => $ProduccionTotalIngresoOk, ':tcol' => $total_colones]);
                        }
                        
                        $respuestaOK = true;
                        $mensajeError = "Producción Encontrada.";
                    } else {
                        $respuestaOK = false;
                        $mensajeError = "Producción Vendida no Encontrada.";
                    }
                } else {
                    $respuestaOK = false;
                    $mensajeError = "Producción Vendida no Encontrada.";
                }
                break;   

            case "BuscarTodosUnidadPlaca":
                $codigo_up = trim($_REQUEST['codigo_up'] ?? '');
                $fecha_desde = trim($_REQUEST['FechaDesdeUP'] ?? '');
                $fecha_hasta = trim($_REQUEST['FechaHastaUP'] ?? '');
                $OptBuscarUP = trim($_REQUEST['OptBuscarUP'] ?? '');
                ListadoPorUnidadTransporte();
                break;

            case 'BuscarPorMotorista':
                $codigo_personal = trim($_REQUEST['codigo_personal'] ?? '');
                $fecha_desde = trim($_REQUEST['FechaDesdePM'] ?? '');
                $fecha_hasta = trim($_REQUEST['FechaHastaPM'] ?? '');
                $OptBuscarPM = trim($_REQUEST['OptBuscarPM'] ?? '');
                ListadoPorCodigoPersonal();
                break;

            case 'BuscarProduccionPorId':
                $codigo_produccion = trim($_POST['codigo_produccion'] ?? '');
                $numero_control = explode(",", $codigo_produccion);
                
                // Usando sentencia preparada
                $stmt_prod = $dblink->prepare("SELECT p.id_ AS id_produccion, p.fecha, p.codigo_inventario_tiquete, p.id_, p.total_ingreso, p.codigo_jornada,
                    p.codigo_personal, p.codigo_transporte_colectivo, cat_ts.descripcion as nombre_serie, 
                    btrim(cat_j.descripcion || CAST(': ' AS VARCHAR) || cat_j.hora_desde || CAST(' - ' AS VARCHAR) || cat_j.hora_hasta) as descripcion_jornada, 
                    cat_r.descripcion as descripcion_ruta, btrim(per.nombres || CAST(' ' AS VARCHAR) || per.apellidos) as nombre_motorista,
                    tc.numero_equipo, tc.numero_placa, cat_estatus.descripcion as descripcion_estatus
                    FROM produccion p
                    INNER JOIN personal per ON per.codigo = p.codigo_personal
                    INNER JOIN inventario_tiquete it ON it.id_ = p.codigo_inventario_tiquete 
                    INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie 
                    INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = p.codigo_jornada 
                    INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = p.codigo_ruta 
                    INNER JOIN transporte_colectivo tc ON tc.id_ = p.codigo_transporte_colectivo
                    INNER JOIN catalogo_estatus cat_estatus ON cat_estatus.codigo = p.codigo_estatus
                    WHERE p.id_ = :id_prod ORDER BY p.id_");

                $estilo_l = 'style="padding: 0px; font-size: medium; text-align: left;"';
                $estilo_c = 'style="padding: 0px; font-size: medium; text-align: center;"';
                $estilo_r_green = 'style="padding: 0px; font-size: medium; color:green; text-align: right;"';

                for ($ab=0; $ab < count($numero_control); $ab++) { 
                    $stmt_prod->execute([':id_prod' => $numero_control[$ab]]);
                    
                    while($listado = $stmt_prod->fetch(PDO::FETCH_BOTH)) {
                        $fecha = $listado['fecha'];
                        $fecha_ = cambiaf_a_normal($fecha);
                        $c_prod = $listado['id_'];
                        $descripcion_ruta = $listado['descripcion_ruta'];
                        $numero_equipo = $listado['numero_equipo'];
                        $numero_placa = $listado['numero_placa'];
                        $codigo_personal_v = $listado['codigo_personal'];
                        $nombre_motorista_v = $listado['nombre_motorista'];
                        $total_ingreso = (float)$listado['total_ingreso'];
                        
                        $totalIngresoOK += $total_ingreso;
                        $totalIngresoOKPantalla = $totalIngresoOK;

                        $contenidoOK .= "<tr>
                        <td $estilo_c><a data-accion=ProduccionVerAsignacion data-toggle=tooltip title=Imprimir href=$c_prod><i class='fad fa-search fa-lg'></i></a>
                        <td $estilo_l>$c_prod
                        <td $estilo_l>$descripcion_ruta
                        <td $estilo_c>$numero_equipo | $numero_placa
                        <td $estilo_l>$codigo_personal_v | $nombre_motorista_v
                        <td $estilo_r_green>$ $total_ingreso
                        <td>";
                    }   
                }   
                $respuestaOK = true;
                $mensajeError = "Producción | Detalle Encontrada.";
                break;  
                
            case 'BuscarProduccionPorIdTabla':
                $codigo_produccion = trim($_POST['codigo_produccion'] ?? '');
                $respuestaOK = true;
                $mensajeError = "Producción Encontrada";
                ListadoAsignado();
                break;
                
			default:
				$mensajeError = 'Esta acción no se encuentra disponible';
			    break;
		}
	} else {
		$mensajeError = 'No se puede ejecutar la aplicación';
    }
} else {
	$mensajeError = 'No se puede establecer conexión con la base de datos';
}

// Salida JSON
$AccionBuscar = $_POST["accion"] ?? '';
if(in_array($AccionBuscar, ["BuscarTodos", "", "BuscarTodosUnidadPlaca", "BuscarPorMotorista"])){
    echo json_encode($arreglo);	
} elseif(in_array($AccionBuscar, ["BuscarCodigo", "BuscarPersonalMotorista", "EditarRegistro"])){
    echo json_encode($datos);
} else {
    $salidaJson = [
        "respuesta" => $respuestaOK,
        "mensaje" => $mensajeError,
        "contenido" => $contenidoOK,
        "totalProduccion" => $totalProduccionOK,
        "cantidadTiquete" => $CantidadtiqueteOK,
        "totalIngreso" => number_format($totalIngresoOKPantalla, 2),
        "totalProduccionIngreso" => $ProduccionTotalIngresoOkPantalla,
        "cantidadTiquetePantalla" => number_format((float)$cantidadTiquetePantalla, 0),
        "nombreMotorista" => $nombre_motorista,
        "codigoPersonal" => $codigo_personal,
        "url_foto" => $url_foto,
        "codigo_genero" => $codigo_genero,
        "descripcionRuta" => $descripcion_ruta_rg,
        "descripcionUnidad" => $numero_equipo . ' | ' . $numero_placa,
        "precioPublico" => $precio_publico_,
        "cantidadProduccionVendidos" => $cantidadVendidosProduccion,
        "fecha" => $fecha ?? '',
        "cantidadEntregados" => number_format((float)$cantidadTiqueteEntregadosPantalla, 0, ".", ",")
    ];
    echo json_encode($salidaJson);
}

// FUNCIONES

function ListadoAsignado(){
    global $dblink, $contenidoOK, $codigo_produccion, $totalIngresoOK, $respuestaOK, $mensajeError, $CantidadtiqueteOK, $totalIngresoOKPantalla, $nombre_motorista, $codigo_personal,
    $descripcion_ruta_rg, $numero_equipo, $numero_placa, $precio_publico_, $fecha, $url_foto, $codigo_genero; 
    
    $stmt = $dblink->prepare("SELECT p.id_ AS id_produccion, p.fecha, p.codigo_inventario_tiquete, 
            cat_ts.descripcion as nombre_serie, 
            pa.id_ as id_produccion_asignado, pa.tiquete_desde, pa.tiquete_hasta, pa.total, pa.procesado, pa.cantidad, pa.codigo_estatus, pa.tiquete_cola,
            btrim(cat_j.descripcion || CAST(': ' AS VARCHAR) || cat_j.hora_desde || CAST(' - ' AS VARCHAR) || cat_j.hora_hasta) as descripcion_jornada, 
            cat_r.descripcion as descripcion_ruta, it.precio_publico, cat_e.descripcion as descripcion_estatus,
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
            WHERE pa.codigo_produccion = :cod_prod ORDER BY pa.id_, p.codigo_inventario_tiquete");
            
    $stmt->execute([':cod_prod' => $codigo_produccion]);              
    
    $estilo_l = 'style="padding: 0px; font-size: medium; text-align: left;"';
    $estilo_c = 'style="padding: 0px; font-size: medium; text-align: center;"';
    $estilo_r = 'style="padding: 0px; font-size: medium; text-align: right;"';
    $estilo_cola = 'style="padding: 0px; font-size: medium; text-align: right; font-weight: bold;"';

    while($listado = $stmt->fetch(PDO::FETCH_BOTH)) {
        $id_pro_a = trim($listado['id_produccion_asignado']);		
        $pa_codigo_produccion = trim($listado['id_produccion']);    
        $nombre_serie = trim($listado['nombre_serie']);		
        $tiquete_cola = trim($listado['tiquete_cola']);   
        $tiquete_desde = trim($listado['tiquete_desde']);   
        $tiquete_hasta = trim($listado['tiquete_hasta']);   
        $total = (float)trim($listado['total']);
        $cantidad = (float)trim($listado['cantidad']);
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
        $estilo = ""; 

        $todos = "$id_pro_a#$pa_codigo_produccion#$tiquete_desde#$tiquete_hasta#$fecha#$precio_publico#$cantidad#$total#$tiquete_cola";                

        if($codigo_estatus == "04"){
            $estilo = 'class="text-danger font-weight-bold" style="padding: 0px; font-size: medium; color:black; text-align: right;"';
        } elseif($codigo_estatus == "05"){
            $estilo = 'class="text-primary font-weight-bold" style="padding: 0px; font-size: medium; color:black; text-align: right;"';
        }

        if($procesado == '1'){  
            $contenidoOK .= "<tr>
            <td $estilo_c>$pa_codigo_produccion-$id_pro_a
            <td $estilo>$descripcion_estatus
            <td $estilo_c>$nombre_serie
            <td $estilo_cola>$tiquete_cola
            <td $estilo_r>$tiquete_desde
            <td $estilo_r>$tiquete_hasta
            <td $estilo_r>$ $total";
            
            if($codigo_estatus == '05'){
                $totalIngresoOK += $total;
                $CantidadtiqueteOK += $cantidad;    
                $totalIngresoOKPantalla = number_format($totalIngresoOK, 2);
            }
        } else {
            $contenidoOK .= "<tr>
            <td $estilo_l><a data-accion=EditarAsignacion data-toggle=tooltip title=Modificar href='$todos'>Editar</a>
            <td $estilo_l><input type=hidden value='$todos' name=CalcularA>
            <td style='padding: 0px; zoom: 1.5'><input type=checkbox checked data-toggle=tooltip title=Entregado>
            <td $estilo_c>$nombre_serie
            <td $estilo_cola>$tiquete_cola
            <td $estilo_r>$tiquete_desde
            <td $estilo_r>$tiquete_hasta
            <td $estilo_r>$ $total";
            
            $totalIngresoOK += $total;
            $CantidadtiqueteOK += $cantidad;    
        }
    }
}   

function ListadoPorCodigoPersonal(){
    global $dblink, $totalIngresoOK, $CantidadtiqueteOK, $codigo_personal, $fecha_desde, $fecha_hasta, $OptBuscarPM, $arreglo; 
    
    if($OptBuscarPM == "Fecha"){
        $stmt = $dblink->prepare("SELECT SUM(pro.total_ingreso) AS total_ingreso_por_bus, pro.codigo_ruta, pro.codigo_transporte_colectivo, pro.id_,
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
                WHERE per.codigo = :codigo and fecha >= :f_desde and fecha <= :f_hasta
                GROUP BY per.codigo, pro.codigo_tiquete_color, cat_tc.precio_publico, pro.codigo_ruta, pro.codigo_transporte_colectivo, pro.fecha, descripcion_ruta,
                tc.numero_placa, tc.numero_equipo, pro.id_, per.nombres, per.apellidos, per.foto, per.codigo_genero
                ORDER BY pro.id_ ASC");
        $stmt->execute([':codigo' => $codigo_personal, ':f_desde' => $fecha_desde, ':f_hasta' => $fecha_hasta]);
    } else {
        $stmt = $dblink->prepare("SELECT SUM(pro.total_ingreso) AS total_ingreso_por_bus, pro.codigo_ruta, pro.codigo_transporte_colectivo, pro.id_,
                to_char(pro.fecha,'dd/mm/yyyy') as fecha_, per.foto, per.codigo_genero, per.codigo, pro.codigo_tiquete_color, cat_tc.precio_publico, 
                btrim(per.nombres || CAST(' ' AS VARCHAR) || per.apellidos) as nombre_motorista, cat_r.descripcion as descripcion_ruta, tc.numero_placa, tc.numero_equipo, btrim(tc.numero_equipo || CAST(' | ' AS VARCHAR) || tc.numero_placa) as numero_equipo_placa,
                CAST(SUM(pro.total_ingreso)/cat_tc.precio_publico AS INTEGER) as cantidadTiquete
                FROM produccion pro
                INNER JOIN personal per ON per.codigo = pro.codigo_personal
                INNER JOIN catalogo_tiquete_color cat_tc ON cat_tc.id_ = pro.codigo_tiquete_color
                INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = pro.codigo_ruta
                INNER JOIN transporte_colectivo tc ON tc.id_ = pro.codigo_transporte_colectivo
                WHERE per.codigo = :codigo
                GROUP BY pro.id_ ,per.codigo, pro.codigo_tiquete_color, cat_tc.precio_publico, pro.codigo_ruta, pro.codigo_transporte_colectivo, pro.fecha, descripcion_ruta, tc.numero_placa, tc.numero_equipo, per.nombres, per.apellidos, per.foto, per.codigo_genero
                ORDER BY pro.id_ ASC");
        $stmt->execute([':codigo' => $codigo_personal]);
    }
            
    if($stmt->rowCount() != 0){  
        while($listado = $stmt->fetch(PDO::FETCH_BOTH)) {
            $arreglo["data"][] = $listado;						
            
            $precio_publico = (float)trim($listado['precio_publico']);
            $IngresoDiario = (float)trim($listado['total_ingreso_por_bus']);
            $foto = trim($listado["foto"] ?? '');
            $codigo_genero = trim($listado["codigo_genero"]);
            $CantidadTiquete = round($IngresoDiario / $precio_publico, 0);
            
            $totalIngresoOK += $IngresoDiario;
            $CantidadtiqueteOK += $CantidadTiquete;    
            
            $arreglo[1]["dataTotalIngreso"] = number_format($totalIngresoOK, 2, ".", ",");			
            $arreglo[1]["dataTotalTiquete"] = number_format($CantidadtiqueteOK, 0, ".", ",");
            $arreglo[1]["codigo_genero"] = $codigo_genero;
            
            $arreglo[1]["foto"] = '../acomtus/img/avatar_masculino.png';
            if($foto == "" || $foto == " "){
                if($codigo_genero == '02'){	
                    $arreglo[1]["foto"] = '../acomtus/acomtus/img/avatar_femenino.png';
                }
            } else {
                $arreglo[1]["foto"] = "../acomtus/img/fotos/".$foto;
            }
        }
    } else {              
        $arreglo["data"] = ["Fecha" => "", "Control" => "", "NumeroEquipoYPlaca" => "", "Ruta" => "", "PU" => "", "Tiquete" => "", "Ingresos" => ""];
        $arreglo[1]["dataTotalIngreso"] = "";			
        $arreglo[1]["dataTotalTiquete"] = "";
        $arreglo[1]["foto"] = "";
        $arreglo[1]["codigo_genero"] = "";
    }   
}   

function ListadoPorUnidadTransporte(){
    global $dblink, $totalIngresoOK, $CantidadtiqueteOK, $codigo_up, $fecha_desde, $fecha_hasta, $OptBuscarUP, $arreglo; 
    
    if($OptBuscarUP == "Fecha"){
        $stmt = $dblink->prepare("SELECT SUM(pro.total_ingreso) AS total_ingreso_por_bus, pro.codigo_ruta, pro.codigo_transporte_colectivo, pro.id_, to_char(pro.fecha,'dd/mm/yyyy') as fecha_,
                per.codigo, pro.codigo_tiquete_color, cat_tc.precio_publico, btrim(per.nombres || CAST(' ' AS VARCHAR) || per.apellidos) as nombre_motorista, cat_r.descripcion as descripcion_ruta, tc.numero_placa, tc.numero_equipo,
                CAST(SUM(pro.total_ingreso)/cat_tc.precio_publico AS INTEGER) as cantidadTiquete
                FROM produccion pro
                INNER JOIN personal per ON per.codigo = pro.codigo_personal
                INNER JOIN catalogo_tiquete_color cat_tc ON cat_tc.id_ = pro.codigo_tiquete_color
                INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = pro.codigo_ruta
                INNER JOIN transporte_colectivo tc ON tc.id_ = pro.codigo_transporte_colectivo
                WHERE pro.codigo_transporte_colectivo = :codigo and fecha >= :f_desde and fecha <= :f_hasta
                GROUP BY per.codigo, pro.codigo_tiquete_color, cat_tc.precio_publico, pro.codigo_ruta, pro.codigo_transporte_colectivo, pro.fecha, descripcion_ruta, tc.numero_placa, tc.numero_equipo, pro.id_, per.nombres, per.apellidos
                ORDER BY pro.id_ ASC");
        $stmt->execute([':codigo' => $codigo_up, ':f_desde' => $fecha_desde, ':f_hasta' => $fecha_hasta]);
    } else {
        $stmt = $dblink->prepare("SELECT SUM(pro.total_ingreso) AS total_ingreso_por_bus, pro.codigo_ruta, pro.codigo_transporte_colectivo, pro.id_, to_char(pro.fecha,'dd/mm/yyyy') as fecha_,
                per.codigo, pro.codigo_tiquete_color, cat_tc.precio_publico, btrim(per.nombres || CAST(' ' AS VARCHAR) || per.apellidos) as nombre_motorista, cat_r.descripcion as descripcion_ruta, tc.numero_placa, tc.numero_equipo,
                CAST(SUM(pro.total_ingreso)/cat_tc.precio_publico AS INTEGER) as cantidadTiquete
                FROM produccion pro
                INNER JOIN personal per ON per.codigo = pro.codigo_personal
                INNER JOIN catalogo_tiquete_color cat_tc ON cat_tc.id_ = pro.codigo_tiquete_color
                INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = pro.codigo_ruta
                INNER JOIN transporte_colectivo tc ON tc.id_ = pro.codigo_transporte_colectivo
                WHERE pro.codigo_transporte_colectivo = :codigo 
                GROUP BY pro.id_ , pro.fecha, per.codigo, pro.codigo_tiquete_color, cat_tc.precio_publico, pro.codigo_ruta, pro.codigo_transporte_colectivo, descripcion_ruta, tc.numero_placa, tc.numero_equipo, per.nombres, per.apellidos
                ORDER BY pro.id_ ASC");
        $stmt->execute([':codigo' => $codigo_up]);
    }
            
    if($stmt->rowCount() != 0){  
        while($listado = $stmt->fetch(PDO::FETCH_BOTH)) {
            $arreglo["data"][] = $listado;						
            
            $precio_publico = (float)trim($listado['precio_publico']);
            $IngresoDiario = (float)trim($listado['total_ingreso_por_bus']);
            $CantidadTiquete = round($IngresoDiario / $precio_publico, 0);
            
            $totalIngresoOK += $IngresoDiario;
            $CantidadtiqueteOK += $CantidadTiquete;    
            
            $arreglo[1]["dataTotalIngreso"] = number_format($totalIngresoOK, 2, ".", ",");			
            $arreglo[1]["dataTotalTiquete"] = number_format($CantidadtiqueteOK, 0, ".", ",");
        }
    } else {
        $arreglo["data"] = ["Fecha" => "", "Control" => "", "NombreEmpleado" => "", "Ruta" => "", "PU" => "", "Tiquete" => "", "Ingresos" => ""];
        $arreglo[1]["dataTotalIngreso"] = "";			
        $arreglo[1]["dataTotalTiquete"] = "";
    }   
}   
?>