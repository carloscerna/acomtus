<?php
// limpiar cache.
clearstatcache();
// Script para ejecutar AJAX
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
$path_root = trim($_SERVER['DOCUMENT_ROOT']);
// Incluimos el archivo de funciones y conexión a la base de datos
include($path_root."/acomtus/includes/mainFunctions_conexion.php");
include($path_root."/acomtus/includes/funciones.php");

// Validar conexión con la base de datos
if ($errorDbConexion == false) {
    // Validamos que existan las variables post
    if (isset($_POST) && !empty($_POST)) {
        if (!empty($_POST['accion_buscar'])) {
            $_POST['accion'] = $_POST['accion_buscar'];
        }

        // Verificamos las variables de acción
        switch ($_POST['accion']) {

            // =====================================================================
            // CASO: BuscarProduccionPorRuta
            // OPTIMIZACIÓN PRINCIPAL: Se eliminaron las N+1 queries dentro del for.
            // Antes: por cada combinación ruta×tiquete se ejecutaban 4 queries.
            // Ahora: 1 sola query con GROUP BY y CASE trae todo de una vez.
            // =====================================================================
            case 'BuscarProduccionPorRuta':
                $fecha = $_POST["fecha"];
                $fecha_ = cambiaf_a_normal($_POST["fecha"]);

                // FIX: Usar prepared statement para evitar SQL injection
                // FIX: Usar FETCH_ASSOC en lugar de FETCH_BOTH (reduce memoria ~50%)
                $stmt_v = $dblink->prepare(
                    "SELECT count(*) as total_vendidos FROM produccion WHERE codigo_estatus = '02' AND fecha = :fecha"
                );
                $stmt_v->execute([':fecha' => $fecha]);
                $row_v = $stmt_v->fetch(PDO::FETCH_ASSOC);
                $cantidadVendidosProduccion = $row_v ? (int)$row_v['total_vendidos'] : 0;

                if ($cantidadVendidosProduccion != 0) {

                    // ---- Catálogo ruta (se ejecuta UNA sola vez, fuera del loop) ----
                    $stmt_cat_r = $dblink->query(
                        "SELECT id_ruta, codigo, descripcion FROM catalogo_ruta ORDER BY codigo"
                    );
                    $catalogo_rutas = $stmt_cat_r->fetchAll(PDO::FETCH_ASSOC);

                    // ---- Catálogo tiquete color (se ejecuta UNA sola vez) ----
                    $stmt_cat_tc = $dblink->query(
                        "SELECT DISTINCT cat_tc.id_ as id_tiquete_color, cat_tc.descripcion as tiquete_color, it.precio_publico
                         FROM catalogo_tiquete_color cat_tc
                             INNER JOIN inventario_tiquete it ON cat_tc.id_ = it.codigo_tiquete_color
                         ORDER BY it.precio_publico"
                    );
                    $catalogo_tiquetes = $stmt_cat_tc->fetchAll(PDO::FETCH_ASSOC);

                    // =================================================================
                    // QUERY CONSOLIDADO: reemplaza las 4 queries dentro del bucle for.
                    //
                    // Antes se ejecutaban por cada ProduccionDesdeHasta[$ab]:
                    //   query_t_d   → estatus 05 con tiquete_cola > 0
                    //   query_t_d_  → estatus 03 SUM cantidad
                    //   query_t_d_  → estatus 04 SUM cantidad
                    //   query_t_d_c → tiquetes entregados
                    //
                    // Ahora UNA query trae todo agrupado por codigo_produccion usando CASE.
                    // =================================================================
                    $stmt_resumen = $dblink->prepare(
                        "SELECT
                            pa.codigo_produccion,
                            -- Tiquetes vendidos con cola (estatus 05)
                            COALESCE(SUM(CASE WHEN pa.codigo_estatus = '05' AND pa.tiquete_cola > 0
                                THEN (pa.tiquete_hasta - pa.tiquete_cola) + 1 ELSE 0 END), 0) AS total_devolucion_cola,
                            -- Tiquetes devueltos estatus 03
                            COALESCE(SUM(CASE WHEN pa.codigo_estatus = '03'
                                THEN pa.cantidad ELSE 0 END), 0) AS total_devolucion_03,
                            -- Tiquetes devueltos estatus 04
                            COALESCE(SUM(CASE WHEN pa.codigo_estatus = '04'
                                THEN pa.cantidad ELSE 0 END), 0) AS total_devolucion_04,
                            -- Tiquetes entregados (todos los registros)
                            COALESCE(SUM((pa.tiquete_hasta - pa.tiquete_desde) + 1), 0) AS total_entregados
                        FROM produccion_asignado pa
                        WHERE pa.fecha = :fecha
                        GROUP BY pa.codigo_produccion"
                    );
                    $stmt_resumen->execute([':fecha' => $fecha]);
                    // Indexar por codigo_produccion para acceso O(1)
                    $resumen_asignado = [];
                    while ($row = $stmt_resumen->fetch(PDO::FETCH_ASSOC)) {
                        $resumen_asignado[$row['codigo_produccion']] = $row;
                    }

                    // ---- Query producción agrupada por ruta y tiquete_color ----
                    $stmt_pro = $dblink->prepare(
                        "SELECT pro.id_, pro.total_ingreso, pro.codigo_ruta, pro.codigo_tiquete_color,
                                CONCAT(pro.codigo_ruta, pro.codigo_tiquete_color) as ruta_tiquete_key
                         FROM produccion pro
                         WHERE fecha = :fecha
                         ORDER BY pro.codigo_ruta, pro.id_ ASC"
                    );
                    $stmt_pro->execute([':fecha' => $fecha]);

                    // Agrupar producción por clave ruta+tiquete en PHP (una sola pasada)
                    $produccion_agrupada = [];
                    while ($row = $stmt_pro->fetch(PDO::FETCH_ASSOC)) {
                        $key = $row['ruta_tiquete_key'];
                        if (!isset($produccion_agrupada[$key])) {
                            $produccion_agrupada[$key] = ['ids' => [], 'total_ingreso' => 0];
                        }
                        $produccion_agrupada[$key]['ids'][] = $row['id_'];
                        $produccion_agrupada[$key]['total_ingreso'] += $row['total_ingreso'];
                    }

                    // Estilos (se definen una vez, fuera del loop)
                    $estilo_l       = 'style="padding: 0px; font-size: medium; text-align: left;"';
                    $estilo_c       = 'style="padding: 0px; font-size: medium; text-align: center;"';
                    $estilo_r       = 'style="padding: 0px; font-size: medium; text-align: right;"';
                    $estilo_r_green = 'style="padding: 0px; font-size: medium; color:green; text-align: right;"';

                    // Iterar sobre combinaciones ruta × tiquete
                    foreach ($catalogo_rutas as $ruta) {
                        foreach ($catalogo_tiquetes as $tiquete) {
                            $key = $ruta['id_ruta'] . $tiquete['id_tiquete_color'];

                            if (!isset($produccion_agrupada[$key])) {
                                continue; // No hay producción para esta combinación
                            }

                            $grupo           = $produccion_agrupada[$key];
                            $ids_produccion  = $grupo['ids'];
                            $ProduccionIngresoOk = $grupo['total_ingreso'];
                            $ProduccionCantidad  = count($ids_produccion);
                            $precio_pub      = $tiquete['precio_publico'];
                            $descripcion_ruta_nombre = $ruta['descripcion'];

                            // Acumular totales
                            $cantidadTiquete = $precio_pub > 0
                                ? round($ProduccionIngresoOk / $precio_pub, 2)
                                : 0;
                            $cantidadTiquetePantalla        += $cantidadTiquete;
                            $ProduccionTotalIngresoOk        = round($ProduccionTotalIngresoOk + $ProduccionIngresoOk, 2);
                            $ProduccionTotalIngresoOkPantalla = number_format($ProduccionTotalIngresoOk, 2);
                            $totalProduccionOK              += $ProduccionCantidad;

                            // Obtener totales de devolución/entregados del resumen precargado (O(1))
                            $cantidadTiqueteDevolucion  = 0;
                            $cantidadTiqueteEntregados  = 0;

                            foreach ($ids_produccion as $id_pro) {
                                if (isset($resumen_asignado[$id_pro])) {
                                    $res = $resumen_asignado[$id_pro];
                                    $cantidadTiqueteDevolucion += $res['total_devolucion_cola']
                                        + $res['total_devolucion_03']
                                        + $res['total_devolucion_04'];
                                    $cantidadTiqueteEntregados += $res['total_entregados'];
                                }
                            }

                            $ProduccionIngresoOkPantalla = number_format($ProduccionIngresoOk, 2);
                            $separado_por_comas = implode(",", $ids_produccion);

                            $contenidoOK .= "<tr>
                            <td $estilo_c><a data-accion=ProduccionImprimir data-toggle=tooltip data-placement=left title=Imprimir href=$separado_por_comas><i class='fad fa-search fa-lg'></i></a>
                            <td $estilo_l>
                            <td $estilo_l>$descripcion_ruta_nombre
                            <td $estilo_r><input type=button class='btn btn-info btn-md' value='#' data-toggle=tooltip data-placement=left title='$separado_por_comas'>
                            <td $estilo_c>$ProduccionCantidad
                            <td $estilo_c>$cantidadTiqueteEntregados
                            <td $estilo_c>$cantidadTiqueteDevolucion
                            <td $estilo_c>$cantidadTiquete
                            <td $estilo_c>$ $precio_pub
                            <td $estilo_r_green>$ $ProduccionIngresoOkPantalla
                            <td>
                            ";

                            $cantidadTiqueteEntregadosPantalla += $cantidadTiqueteEntregados;
                        }
                    }

                    // Guardar o actualizar en produccion_diaria
                    $stmt_check = $dblink->prepare("SELECT id_ FROM produccion_diaria WHERE fecha = :fecha");
                    $stmt_check->execute([':fecha' => $fecha]);
                    $total_colones = round($ProduccionTotalIngresoOk * 8.75, 2);

                    if ($stmt_check->fetch(PDO::FETCH_ASSOC)) {
                        $stmt_upd = $dblink->prepare(
                            "UPDATE produccion_diaria SET total_dolares = :dolares, total_colones = :colones WHERE fecha = :fecha"
                        );
                        $stmt_upd->execute([
                            ':dolares' => $ProduccionTotalIngresoOk,
                            ':colones' => $total_colones,
                            ':fecha'   => $fecha
                        ]);
                    } else {
                        $stmt_ins = $dblink->prepare(
                            "INSERT INTO produccion_diaria (fecha, total_dolares, total_colones) VALUES (:fecha, :dolares, :colones)"
                        );
                        $stmt_ins->execute([
                            ':fecha'   => $fecha,
                            ':dolares' => $ProduccionTotalIngresoOk,
                            ':colones' => $total_colones
                        ]);
                    }

                    $respuestaOK = true;
                    $mensajeError = "Producción Encontrada.";
                } else {
                    $respuestaOK = false;
                    $mensajeError = "Producción Vendida no Encontrada.";
                }

                break;

            // =====================================================================
            case "BuscarTodosUnidadPlaca":
                $codigo_up    = trim($_REQUEST['codigo_up']);
                $fecha_desde  = trim($_REQUEST['FechaDesdeUP']);
                $fecha_hasta  = trim($_REQUEST['FechaHastaUP']);
                $OptBuscarUP  = trim($_REQUEST['OptBuscarUP']);
                ListadoPorUnidadTransporte();
                break;

            // =====================================================================
            case 'BuscarPorMotorista':
                $codigo_personal = trim($_REQUEST['codigo_personal']);
                $fecha_desde     = trim($_REQUEST['FechaDesdePM']);
                $fecha_hasta     = trim($_REQUEST['FechaHastaPM']);
                $OptBuscarPM     = trim($_REQUEST['OptBuscarPM']);
                ListadoPorCodigoPersonal();
                break;

            // =====================================================================
            case 'BuscarProduccionPorId':
                $codigo_produccion = trim($_POST['codigo_produccion']);
                $numero_control    = explode(",", $codigo_produccion);

                // FIX: una sola query con IN() en lugar de loop de queries individuales
                $placeholders = implode(',', array_fill(0, count($numero_control), '?'));
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
                        WHERE p.id_ IN ($placeholders)
                        ORDER BY p.id_";

                $stmt = $dblink->prepare($query);
                $stmt->execute($numero_control);

                $estilo_l       = 'style="padding: 0px; font-size: medium; text-align: left;"';
                $estilo_c       = 'style="padding: 0px; font-size: medium; text-align: center;"';
                $estilo_r       = 'style="padding: 0px; font-size: medium; text-align: right;"';
                $estilo_r_green = 'style="padding: 0px; font-size: medium; color:green; text-align: right;"';

                while ($listado = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $fecha            = $listado['fecha'];
                    $fecha_           = cambiaf_a_normal($listado["fecha"]);
                    $codigo_produccion = $listado['id_'];
                    $descripcion_ruta = $listado['descripcion_ruta'];
                    $numero_equipo    = $listado['numero_equipo'];
                    $numero_placa     = $listado['numero_placa'];
                    $nombre_serie     = $listado['nombre_serie'];
                    $codigo_personal  = $listado['codigo_personal'];
                    $nombre_motorista = $listado['nombre_motorista'];
                    $total_ingreso    = $listado['total_ingreso'];

                    $totalIngresoOK      += $total_ingreso;
                    $totalIngresoOKPantalla = $totalIngresoOK;

                    $contenidoOK .= "<tr>
                    <td $estilo_c><a data-accion=ProduccionVerAsignacion data-toggle=tooltip data-placement=left title=Imprimir href=$codigo_produccion><i class='fad fa-search fa-lg'></i></a>
                    <td $estilo_l>$codigo_produccion
                    <td $estilo_l>$descripcion_ruta
                    <td $estilo_c>$numero_equipo | $numero_placa
                    <td $estilo_l>$codigo_personal | $nombre_motorista
                    <td $estilo_r_green>$ $total_ingreso
                    <td>
                    ";
                }
                $respuestaOK = true;
                $mensajeError = "Producción | Detalle Encontrada.";
                break;

            // =====================================================================
            case 'BuscarProduccionPorIdTabla':
                $codigo_produccion = trim($_POST['codigo_produccion']);
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

// Salida del Array con JSON.
$AccionBuscar = $_POST["accion"] ?? "";
if ($AccionBuscar === "BuscarTodos" || $AccionBuscar === "" || $AccionBuscar === "BuscarTodosUnidadPlaca" || $AccionBuscar === "BuscarPorMotorista") {
    echo json_encode($arreglo);
} elseif ($AccionBuscar === "BuscarCodigo" || $AccionBuscar === "BuscarPersonalMotorista" || $AccionBuscar === "EditarRegistro") {
    echo json_encode($datos);
} else {
    $salidaJson = array(
        "respuesta"               => $respuestaOK,
        "mensaje"                 => $mensajeError,
        "contenido"               => $contenidoOK,
        "totalProduccion"         => $totalProduccionOK,
        "cantidadTiquete"         => $CantidadtiqueteOK,
        "totalIngreso"            => number_format($totalIngresoOKPantalla, 2),
        "totalProduccionIngreso"  => $ProduccionTotalIngresoOkPantalla,
        "cantidadTiquetePantalla" => number_format($cantidadTiquetePantalla, 0),
        "nombreMotorista"         => $nombre_motorista,
        "codigoPersonal"          => $codigo_personal,
        "url_foto"                => $url_foto,
        "codigo_genero"           => $codigo_genero,
        "descripcionRuta"         => $descripcion_ruta_rg,
        "descripcionUnidad"       => $numero_equipo . ' | ' . $numero_placa,
        "precioPublico"           => $precio_publico_,
        "cantidadProduccionVendidos" => $cantidadVendidosProduccion,
        "fecha"                   => $fecha,
        "cantidadEntregados"      => number_format($cantidadTiqueteEntregadosPantalla, 0, ".", ",")
    );
    echo json_encode($salidaJson);
}


// =====================================================================
// FUNCIÓN: ListadoAsignado
// FIX: Usar FETCH_ASSOC en lugar de FETCH_BOTH
// FIX: Usar prepared statement
// =====================================================================
function ListadoAsignado()
{
    global $id_produccion, $dblink, $contenidoOK, $codigo_produccion, $totalIngresoOK,
           $respuestaOK, $mensajeError, $CantidadtiqueteOK, $totalIngresoOKPantalla,
           $nombre_motorista, $codigo_personal, $descripcion_ruta_rg, $numero_equipo,
           $numero_placa, $precio_publico, $precio_publico_, $fecha, $url_foto, $codigo_genero;

    $stmt_c = $dblink->prepare(
        "SELECT p.id_ AS id_produccion, p.fecha, p.codigo_inventario_tiquete,
            cat_ts.descripcion as nombre_serie,
            pa.id_ as id_produccion_asignado, pa.tiquete_desde, pa.tiquete_hasta, pa.total,
            pa.procesado, pa.cantidad, pa.codigo_estatus, pa.tiquete_cola,
            btrim(cat_j.descripcion || CAST(': ' AS VARCHAR) || cat_j.hora_desde || CAST(' - ' AS VARCHAR) || cat_j.hora_hasta) as descripcion_jornada,
            cat_r.descripcion as descripcion_ruta,
            it.precio_publico,
            cat_e.descripcion as descripcion_estatus,
            btrim(per.nombres || CAST(' ' AS VARCHAR) || per.apellidos) as nombre_motorista,
            per.codigo as codigo_personal, per.foto, per.codigo_genero,
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
                WHERE pa.codigo_produccion = :codigo_produccion
                ORDER BY pa.id_, p.codigo_inventario_tiquete"
    );
    $stmt_c->execute([':codigo_produccion' => $codigo_produccion]);

    $estilo_l    = 'style="padding: 0px; font-size: medium; text-align: left;"';
    $estilo_c    = 'style="padding: 0px; font-size: medium; text-align: center;"';
    $estilo_r    = 'style="padding: 0px; font-size: medium; text-align: right;"';
    $estilo_cola = 'style="padding: 0px; font-size: medium; text-align: right; font-weight: bold;"';

    while ($listado = $stmt_c->fetch(PDO::FETCH_ASSOC)) {
        $id_pro_a            = trim($listado['id_produccion_asignado']);
        $pa_codigo_produccion = trim($listado['id_produccion']);
        $nombre_serie        = trim($listado['nombre_serie']);
        $tiquete_cola        = trim($listado['tiquete_cola']);
        $tiquete_desde       = trim($listado['tiquete_desde']);
        $tiquete_hasta       = trim($listado['tiquete_hasta']);
        $total               = trim($listado['total']);
        $cantidad            = trim($listado['cantidad']);
        $fecha               = cambiaf_a_normal(trim($listado['fecha']));
        $precio_publico      = trim($listado['precio_publico']);
        $precio_publico_     = $precio_publico;
        $procesado           = trim($listado['procesado']);
        $codigo_estatus      = trim($listado['codigo_estatus']);
        $descripcion_estatus = trim($listado['descripcion_estatus']);
        $descripcion_ruta_rg = trim($listado['descripcion_ruta']);
        $nombre_motorista    = trim($listado['nombre_motorista']);
        $codigo_personal     = trim($listado['codigo_personal']);
        $url_foto            = trim($listado['foto']);
        $codigo_genero       = trim($listado['codigo_genero']);
        $numero_equipo       = trim($listado['numero_equipo']);
        $numero_placa        = trim($listado['numero_placa']);
        $estilo              = "";

        $todos = $id_pro_a . "#" . $pa_codigo_produccion . "#" . $tiquete_desde . "#" . $tiquete_hasta
               . "#" . $fecha . "#" . $precio_publico . "#" . $cantidad . "#" . $total . "#" . $tiquete_cola;

        // Cambiar color al estatus 04=Devolución, 05=Vendido
        if ($codigo_estatus == "04") {
            $estilo = 'class="text-danger font-weight-bold" style="padding: 0px; font-size: medium; color:black; text-align: right;"';
        }
        if ($codigo_estatus == "05") {
            $estilo = 'class="text-primary font-weight-bold" style="padding: 0px; font-size: medium; color:black; text-align: right;"';
        }

        if ($procesado == '1') {
            $contenidoOK .= "<tr>
            <td $estilo_c>$pa_codigo_produccion-$id_pro_a
            <td $estilo>$descripcion_estatus
            <td $estilo_c>$nombre_serie
            <td $estilo_cola>$tiquete_cola
            <td $estilo_r>$tiquete_desde
            <td $estilo_r>$tiquete_hasta
            <td $estilo_r>$ $total";

            if ($codigo_estatus == '05') {
                $totalIngresoOK     += $total;
                $CantidadtiqueteOK  += $cantidad;
                $totalIngresoOKPantalla = number_format($totalIngresoOK, 2);
            }
        } else {
            $contenidoOK .= "<tr>
            <td $estilo_l><a data-accion=EditarAsignacion data-toggle=tooltip data-placement=left title=Modificar href='$todos'>Editar</a>
            <td $estilo_l><input type=hidden value=$todos name=CalcularA>
            <td style='padding: 0px; zoom: 1.5'><input type=checkbox checked data-toggle=tooltip data-placement=left title=Entregado>
            <td $estilo_c>$nombre_serie
            <td $estilo_cola>$tiquete_cola
            <td $estilo_r>$tiquete_desde
            <td $estilo_r>$tiquete_hasta
            <td $estilo_r>$ $total";

            $totalIngresoOK    += $total;
            $CantidadtiqueteOK += $cantidad;
        }
        $respuestaOK  = true;
        $mensajeError = "Producción Encontrada";
    }
}


// =====================================================================
// FUNCIÓN: ListadoPorCodigoPersonal
// FIX: Usar prepared statements con parámetros nombrados
// FIX: Usar FETCH_ASSOC en lugar de FETCH_BOTH
// FIX: rowCount() no es confiable en PostgreSQL para SELECT → usar fetch()
// =====================================================================
function ListadoPorCodigoPersonal()
{
    global $dblink, $contenidoOK, $codigo_personal, $totalIngresoOK, $respuestaOK,
           $mensajeError, $CantidadtiqueteOK, $totalIngresoOKPantalla,
           $descripcion_ruta_pm, $numero_equipo, $numero_placa, $precio_publico,
           $fecha_desde, $fecha_hasta, $OptBuscarPM, $fecha, $arreglo;

    if ($OptBuscarPM == "Fecha") {
        $stmt_c = $dblink->prepare(
            "SELECT SUM(pro.total_ingreso) AS total_ingreso_por_bus, pro.codigo_ruta,
                pro.codigo_transporte_colectivo, pro.id_,
                to_char(pro.fecha,'dd/mm/yyyy') as fecha_, per.foto, per.codigo_genero,
                per.codigo, pro.codigo_tiquete_color, cat_tc.precio_publico,
                btrim(per.nombres || CAST(' ' AS VARCHAR) || per.apellidos) as nombre_motorista,
                cat_r.descripcion as descripcion_ruta, tc.numero_placa, tc.numero_equipo,
                btrim(tc.numero_equipo || CAST(' | ' AS VARCHAR) || tc.numero_placa) as numero_equipo_placa,
                CAST(SUM(pro.total_ingreso)/cat_tc.precio_publico AS INTEGER) as cantidadTiquete
                    FROM produccion pro
                        INNER JOIN personal per ON per.codigo = pro.codigo_personal
                        INNER JOIN catalogo_tiquete_color cat_tc ON cat_tc.id_ = pro.codigo_tiquete_color
                        INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = pro.codigo_ruta
                        INNER JOIN transporte_colectivo tc ON tc.id_ = pro.codigo_transporte_colectivo
                    WHERE per.codigo = :codigo AND fecha >= :desde AND fecha <= :hasta
                    GROUP BY per.codigo, pro.codigo_tiquete_color,
                        cat_tc.precio_publico, pro.codigo_ruta, pro.codigo_transporte_colectivo,
                        pro.fecha, descripcion_ruta, tc.numero_placa, tc.numero_equipo,
                        pro.id_, per.nombres, per.apellidos, per.foto, per.codigo_genero
                    ORDER BY pro.id_ ASC"
        );
        $stmt_c->execute([
            ':codigo' => $codigo_personal,
            ':desde'  => $fecha_desde,
            ':hasta'  => $fecha_hasta
        ]);
    } else {
        // OptBuscarPM == "Todo"
        $stmt_c = $dblink->prepare(
            "SELECT SUM(pro.total_ingreso) AS total_ingreso_por_bus, pro.codigo_ruta,
                pro.codigo_transporte_colectivo, pro.id_,
                to_char(pro.fecha,'dd/mm/yyyy') as fecha_, per.foto, per.codigo_genero,
                per.codigo, pro.codigo_tiquete_color, cat_tc.precio_publico,
                btrim(per.nombres || CAST(' ' AS VARCHAR) || per.apellidos) as nombre_motorista,
                cat_r.descripcion as descripcion_ruta, tc.numero_placa, tc.numero_equipo,
                btrim(tc.numero_equipo || CAST(' | ' AS VARCHAR) || tc.numero_placa) as numero_equipo_placa,
                CAST(SUM(pro.total_ingreso)/cat_tc.precio_publico AS INTEGER) as cantidadTiquete
                    FROM produccion pro
                        INNER JOIN personal per ON per.codigo = pro.codigo_personal
                        INNER JOIN catalogo_tiquete_color cat_tc ON cat_tc.id_ = pro.codigo_tiquete_color
                        INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = pro.codigo_ruta
                        INNER JOIN transporte_colectivo tc ON tc.id_ = pro.codigo_transporte_colectivo
                    WHERE per.codigo = :codigo
                    GROUP BY pro.id_, per.codigo, pro.codigo_tiquete_color,
                        cat_tc.precio_publico, pro.codigo_ruta, pro.codigo_transporte_colectivo,
                        pro.fecha, descripcion_ruta, tc.numero_placa, tc.numero_equipo,
                        per.nombres, per.apellidos, per.foto, per.codigo_genero
                    ORDER BY pro.id_ ASC"
        );
        $stmt_c->execute([':codigo' => $codigo_personal]);
    }

    // FIX: No usar rowCount() para SELECT en PostgreSQL — fetch() directamente
    $filas = $stmt_c->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($filas)) {
        foreach ($filas as $listado) {
            $arreglo["data"][] = $listado;
            $precio_publico = trim($listado['precio_publico']);
            $IngresoDiario  = trim($listado['total_ingreso_por_bus']);
            $foto           = trim($listado["foto"]);
            $codigo_genero_row = trim($listado["codigo_genero"]);
            $CantidadTiquete = $precio_publico > 0
                ? round($IngresoDiario / $precio_publico, 0)
                : 0;
            $totalIngresoOK  += $IngresoDiario;
            $CantidadtiqueteOK += $CantidadTiquete;

            $arreglo[1]["dataTotalIngreso"]  = number_format($totalIngresoOK, 2, ".", ",");
            $arreglo[1]["dataTotalTiquete"]  = number_format($CantidadtiqueteOK, 0, ".", ",");
            $arreglo[1]["codigo_genero"]     = $codigo_genero_row;
            $arreglo[1]["foto"]              = '../acomtus/img/avatar_masculino.png';

            if (empty(trim($foto))) {
                if ($codigo_genero_row == '02') {
                    $arreglo[1]["foto"] = '../acomtus/acomtus/img/avatar_femenino.png';
                }
            } else {
                $arreglo[1]["foto"] = "../acomtus/img/fotos/" . $foto;
            }
        }
    } else {
        $arreglo["data"]["Fecha"]           = "";
        $arreglo["data"]["Control"]         = "";
        $arreglo["data"]["NumeroEquipoYPlaca"] = "";
        $arreglo["data"]["Ruta"]            = "";
        $arreglo["data"]["PU"]              = "";
        $arreglo["data"]["Tiquete"]         = "";
        $arreglo["data"]["Ingresos"]        = "";
        $arreglo[1]["dataTotalIngreso"]     = "";
        $arreglo[1]["dataTotalTiquete"]     = "";
        $arreglo[1]["foto"]                 = "";
        $arreglo[1]["codigo_genero"]        = "";
    }
}


// =====================================================================
// FUNCIÓN: ListadoPorUnidadTransporte
// FIX: Usar prepared statements con parámetros nombrados
// FIX: Usar FETCH_ASSOC en lugar de FETCH_BOTH
// FIX: rowCount() no confiable en PostgreSQL para SELECT
// =====================================================================
function ListadoPorUnidadTransporte()
{
    global $dblink, $contenidoOK, $codigo_up, $totalIngresoOK, $respuestaOK, $mensajeError,
           $CantidadtiqueteOK, $totalIngresoOKPantalla, $descripcion_ruta_pm, $numero_equipo,
           $numero_placa, $precio_publico, $fecha_desde, $fecha_hasta, $OptBuscarUP,
           $fecha, $datos, $arreglo;

    if ($OptBuscarUP == "Fecha") {
        $stmt_c = $dblink->prepare(
            "SELECT SUM(pro.total_ingreso) AS total_ingreso_por_bus, pro.codigo_ruta,
                pro.codigo_transporte_colectivo, pro.id_,
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
                    WHERE pro.codigo_transporte_colectivo = :codigo_up
                        AND fecha >= :desde AND fecha <= :hasta
                    GROUP BY per.codigo, pro.codigo_tiquete_color,
                        cat_tc.precio_publico, pro.codigo_ruta, pro.codigo_transporte_colectivo,
                        pro.fecha, descripcion_ruta, tc.numero_placa, tc.numero_equipo,
                        pro.id_, per.nombres, per.apellidos
                    ORDER BY pro.id_ ASC"
        );
        $stmt_c->execute([
            ':codigo_up' => $codigo_up,
            ':desde'     => $fecha_desde,
            ':hasta'     => $fecha_hasta
        ]);
    } else {
        // OptBuscarUP == "Todo"
        $stmt_c = $dblink->prepare(
            "SELECT SUM(pro.total_ingreso) AS total_ingreso_por_bus, pro.codigo_ruta,
                pro.codigo_transporte_colectivo, pro.id_,
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
                    WHERE pro.codigo_transporte_colectivo = :codigo_up
                    GROUP BY pro.id_, pro.fecha, per.codigo, pro.codigo_tiquete_color,
                        cat_tc.precio_publico, pro.codigo_ruta, pro.codigo_transporte_colectivo,
                        descripcion_ruta, tc.numero_placa, tc.numero_equipo, per.nombres, per.apellidos
                    ORDER BY pro.id_ ASC"
        );
        $stmt_c->execute([':codigo_up' => $codigo_up]);
    }

    $filas = $stmt_c->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($filas)) {
        foreach ($filas as $listado) {
            $arreglo["data"][] = $listado;
            $precio_publico  = trim($listado['precio_publico']);
            $IngresoDiario   = trim($listado['total_ingreso_por_bus']);
            $CantidadTiquete = $precio_publico > 0
                ? round($IngresoDiario / $precio_publico, 0)
                : 0;
            $totalIngresoOK  += $IngresoDiario;
            $CantidadtiqueteOK += $CantidadTiquete;
            $arreglo[1]["dataTotalIngreso"] = number_format($totalIngresoOK, 2, ".", ",");
            $arreglo[1]["dataTotalTiquete"] = number_format($CantidadtiqueteOK, 0, ".", ",");
        }
    } else {
        $arreglo["data"]["Fecha"]        = "";
        $arreglo["data"]["Control"]      = "";
        $arreglo["data"]["NombreEmpleado"] = "";
        $arreglo["data"]["Ruta"]         = "";
        $arreglo["data"]["PU"]           = "";
        $arreglo["data"]["Tiquete"]      = "";
        $arreglo["data"]["Ingresos"]     = "";
        $arreglo[1]["dataTotalIngreso"]  = "";
        $arreglo[1]["dataTotalTiquete"]  = "";
    }
}
?>
