<?php
// Ruta de los archivos con su carpeta
$path_root = trim($_SERVER['DOCUMENT_ROOT']);

// Archivos que se incluyen
include($path_root . "/acomtus/includes/funciones.php");
include($path_root . "/acomtus/includes/mainFunctions_conexion.php");
// Llamar a la librería FPDF
include($path_root . "/acomtus/php_libs/fpdf/fpdf.php");

header("Content-Type: text/html; charset=UTF-8");
date_default_timezone_set('America/El_Salvador');
setlocale(LC_MONETARY, "es_ES");

// --- PARÁMETROS DE ENTRADA ---
$reimprimir = false;
if (isset($_REQUEST['reimprimir'])) {
    $reimprimir = filter_var($_REQUEST['reimprimir'], FILTER_VALIDATE_BOOLEAN);
}

$codigo_produccion = isset($_REQUEST['codigo_produccion']) ? trim((string)$_REQUEST['codigo_produccion']) : '';
$fecha_raw = isset($_REQUEST['fecha']) ? trim((string)$_REQUEST['fecha']) : '';

// --- CORRECCIÓN DE FECHA PARA PHP 8 ---
$timestamp = ($fecha_raw !== '') ? strtotime($fecha_raw) : false;
if ($timestamp === false) {
    $timestamp = time();
}
$fecha_sql = date('Y-m-d', $timestamp);

$meses = array(
    "enero", "febrero", "marzo", "abril", "mayo", "junio",
    "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"
);

$dia = date("d", $timestamp);
$mes = $meses[(int)date("n", $timestamp) - 1];
$año = date("Y", $timestamp);

$cantidadTiquete = 0;

class PDF extends FPDF
{
}

// --- CREACIÓN DEL INFORME ---
$pdf = new PDF('P', 'mm', 'Letter');
$pdf->SetMargins(20, 20);
$pdf->SetAutoPageBreak(true, 5);
$pdf->AliasNbPages();

$pdf->SetY(20.5);
$pdf->SetX(15);
$pdf->SetFont('Arial', '', 9);

$w = array(20, 50);       // serie y número desde
$h = array(6.5, 11, 5, 4); // alto de columnas

$pdf->SetXY(15, 52);

// --- RESOLUCIÓN DE CÓDIGOS DE PRODUCCIÓN ---
$codigos_a_procesar = array();

if ($codigo_produccion !== '' && $codigo_produccion !== '0') {
    $tiene_coma = (strpos($codigo_produccion, ",") !== false);
    $tiene_guion = (strpos($codigo_produccion, "-") !== false);

    if ($tiene_guion && $tiene_coma) {
        print "Hay guión y coma";
        exit;
    }

    if ($tiene_coma) {
        $partes = explode(",", $codigo_produccion);
        foreach ($partes as $item) {
            $item = trim($item);
            if ($item !== '' && ctype_digit($item)) {
                $codigos_a_procesar[] = (int)$item;
            }
        }
    } elseif ($tiene_guion) {
        $rango = explode("-", $codigo_produccion);
        $inicio = isset($rango[0]) ? trim($rango[0]) : '';
        $fin = isset($rango[1]) ? trim($rango[1]) : '';
        if (ctype_digit($inicio) && ctype_digit($fin)) {
            $n_inicio = (int)$inicio;
            $n_fin = (int)$fin;
            if ($n_inicio <= $n_fin) {
                for ($jj = $n_inicio; $jj <= $n_fin; $jj++) {
                    $codigos_a_procesar[] = $jj;
                }
            }
        }
    } else {
        if (ctype_digit($codigo_produccion)) {
            $codigos_a_procesar[] = (int)$codigo_produccion;
        }
    }
} else {
    $stmt_p = $dblink->prepare("SELECT id_ FROM produccion WHERE fecha = :fecha ORDER BY id_ ASC");
    $stmt_p->execute([':fecha' => $fecha_sql]);

    while ($listado = $stmt_p->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($listado['id_']) && is_numeric($listado['id_'])) {
            $codigos_a_procesar[] = (int)$listado['id_'];
        }
    }
}

// --- SENTENCIA PREPARADA ÚNICA PARA TODO EL REPORTE ---
$sql_detalle = "SELECT p.id_ AS id_produccion, p.fecha, p.codigo_inventario_tiquete, p.codigo_personal, p.codigo_ruta,
    cat_ts.descripcion AS nombre_serie,
    pa.id_ AS id_produccion_asignado, pa.tiquete_desde, pa.tiquete_hasta, pa.total, pa.cantidad,
    pa.codigo_inventario_tiquete AS codigo_serie_id,
    it.precio_publico,
    cat_r.descripcion AS nombre_ruta,
    cat_j.id_ AS id_jornada
    FROM produccion p
    INNER JOIN produccion_asignado pa ON pa.codigo_produccion = p.id_
    INNER JOIN inventario_tiquete it ON it.id_ = pa.codigo_inventario_tiquete
    INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie
    INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = p.codigo_jornada
    INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = p.codigo_ruta
    WHERE pa.codigo_produccion = :codigo
    ORDER BY pa.id_, p.codigo_inventario_tiquete";

$stmt_detalle = $dblink->prepare($sql_detalle);

foreach ($codigos_a_procesar as $codigo_id) {
    $codigo_produccion_i = $codigo_id;
    VerImprimir($codigo_id);
}

// Salida del PDF
$modo = 'I';
$print_nombre = 'Control de Ingresos.pdf';
$pdf->Output($print_nombre, $modo);

// ========================================================================================
function VerImprimir($codigo_id = null)
{
    global $pdf, $codigo_produccion_i, $stmt_detalle, $cantidadTiquete, $w, $h, $reimprimir;

    $id_produccion_param = ($codigo_id !== null) ? (int)$codigo_id : (int)$codigo_produccion_i;
    if ($id_produccion_param <= 0) {
        return;
    }

    // 1. ÚNICA CONSULTA A POSTGRESQL POR CADA PRODUCCIÓN
    $stmt_detalle->execute([':codigo' => $id_produccion_param]);
    $filas = $stmt_detalle->fetchAll(PDO::FETCH_ASSOC);

    if (empty($filas)) {
        return;
    }

    // 2. AGRUPAR REGISTROS POR SERIE EN MEMORIA
    $grupos_por_serie = array();
    foreach ($filas as $fila) {
        $serie_id = trim((string)$fila['codigo_serie_id']);
        $grupos_por_serie[$serie_id][] = $fila;
    }

    $nombre_ruta = trim((string)$filas[0]['nombre_ruta']);
    $es_cobradores_multiserie = (count($grupos_por_serie) > 1 && $nombre_ruta === 'Cobradores');

    $colx_1 = 13;
    $linea = 0;

    if ($es_cobradores_multiserie) {
        $pdf->AddPage();
        $pdf->SetY(52);

        $suma_serie = 0.0;
        $pa_codigo_produccion = '';
        $fecha_fmt = '';
        $id_jornada = '';

        $lineaX = 12;
        $lineaX1 = 35;
        $lineaXEspacio = 7;
        $lineaXAncho = 23;
        $col_index = 0;

        foreach ($grupos_por_serie as $registros_serie) {
            if ($col_index > 0) {
                $colx_1 += 30;
                $lineaX += $lineaXEspacio + $lineaXAncho;
                $lineaX1 += $lineaXAncho + $lineaXEspacio;
                $pdf->SetY(52);
            }

            $suma_ingreso_ok = 0.0;
            $precio_publico_num = 0.0;

            foreach ($registros_serie as $listado) {
                $pa_codigo_produccion = trim((string)$listado['id_produccion']);
                $nombre_serie = trim((string)$listado['nombre_serie']);
                $tiquete_desde = trim((string)$listado['tiquete_desde']);
                $total_fila = (float)$listado['total'];
                $cantidad_fila = (float)$listado['cantidad'];
                $cantidadTiquete += $cantidad_fila;
                $precio_publico_num = (float)$listado['precio_publico'];
                $fecha_fmt = cambiaf_a_normal(trim((string)$listado['fecha']));
                $nombre_ruta = trim((string)$listado['nombre_ruta']);
                $id_jornada = trim((string)$listado['id_jornada']);

                $suma_ingreso_ok += $total_fila;
                $suma_serie += $total_fila;

                $pdf->SetX($colx_1);
                $pdf->SetFont('Arial', '', 12);
                $pdf->cell($w[0], $h[0], $nombre_serie . "    " . codigos_nuevos($tiquete_desde), 0, 1, 'L');
            }

            // Total por columna/serie
            $pdf->SetX($colx_1);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->cell($w[0], $h[3], "___________", 0, 1, 'L');

            $pdf->SetX($colx_1);
            $pdf->cell($w[0], $h[2], "$   " . number_format($suma_ingreso_ok, 2), 0, 1, 'L');

            $pdf->SetFont('Arial', 'B', 14);
            $pdf->RotatedText($colx_1, 50, "$ " . number_format($precio_publico_num, 2), 0);

            $pdf->SetLineWidth(0.4);
            $pdf->line($lineaX, 52, $lineaX1, 52);

            $col_index++;
        }

        // Encabezado y total general
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->RotatedText(190, 18, $pa_codigo_produccion, 0);
        $pdf->SetFont('Arial', '', 11);
        $pdf->RotatedText(155, 26, $fecha_fmt, 0);
        $pdf->SetFont('Arial', '', 14);
        $pdf->RotatedText(26, 33, $nombre_ruta, 0);
        $pdf->RotatedText(128, 33, $id_jornada, 0);

        $pdf->SetFont('Arial', '', 9);
        $pdf->ln();
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->RotatedText(15, 120, "Total Entregado: $ ", 0);
        $pdf->RotatedText(58, 120, number_format($suma_serie, 2), 0);
    } else {
        $suma_ingreso_ok = 0.0;
        $precio_publico_num = 0.0;
        $pa_codigo_produccion = '';
        $fecha_fmt = '';
        $nombre_ruta_det = '';
        $id_jornada = '';

        $pdf->AddPage();
        $pdf->SetXY(15, 52);

        foreach ($filas as $listado) {
            if ($linea == 8) {
                $colx_1 += 30;
                $linea = 0;
                $pdf->SetY(52);
            }
            $pdf->SetX($colx_1);
            $linea++;

            $pa_codigo_produccion = trim((string)$listado['id_produccion']);
            $nombre_serie = trim((string)$listado['nombre_serie']);
            $tiquete_desde = trim((string)$listado['tiquete_desde']);
            $tiquete_hasta = trim((string)$listado['tiquete_hasta']);
            $precio_publico_num = (float)$listado['precio_publico'];

            if ($reimprimir == true) {
                $cantidad = (((float)$tiquete_hasta - (float)$tiquete_desde) + 1);
                $total_fila = round($precio_publico_num * $cantidad, 2);
            } else {
                $total_fila = (float)$listado['total'];
                $cantidad = (float)$listado['cantidad'];
            }

            $cantidadTiquete += $cantidad;
            $fecha_fmt = cambiaf_a_normal(trim((string)$listado['fecha']));
            $nombre_ruta_det = trim((string)$listado['nombre_ruta']);
            $id_jornada = trim((string)$listado['id_jornada']);

            $suma_ingreso_ok += $total_fila;

            $pdf->SetFont('Arial', '', 12);
            $pdf->cell($w[0], $h[0], $nombre_serie . "    " . codigos_nuevos($tiquete_desde), 0, 1, 'L');
        }

        // Total ingreso
        $pdf->SetX($colx_1);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->cell($w[0], $h[3], "___________", 0, 1, 'L');
        $pdf->SetX($colx_1);
        $pdf->cell($w[0], $h[2], "$   " . number_format($suma_ingreso_ok, 2), 0, 1, 'L');

        // Encabezado y total general
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->RotatedText(190, 18, $pa_codigo_produccion, 0);
        $pdf->SetFont('Arial', '', 11);
        $pdf->RotatedText(155, 26, $fecha_fmt, 0);
        $pdf->SetFont('Arial', '', 14);
        $pdf->RotatedText(26, 33, $nombre_ruta_det, 0);
        $pdf->RotatedText(128, 33, $id_jornada, 0);

        $pdf->SetFont('Arial', 'B', 14);
        $pdf->RotatedText(15, 50, "$ " . number_format($precio_publico_num, 2), 0);
        $pdf->SetLineWidth(0.4);
        $pdf->line(12, 52, 35, 52);

        $pdf->SetFont('Arial', '', 9);
        $pdf->ln();
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->RotatedText(15, 120, "Total Entregado: $ ", 0);
        $pdf->RotatedText(58, 120, number_format($suma_ingreso_ok, 2), 0);
    }
}