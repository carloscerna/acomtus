<?php
// ============================================================
// TiqueteTodos.php — Versión optimizada
// Cambios aplicados:
//   FIX #1: ob_start() / ob_end_clean() — evita PDF corrupto
//   FIX #2: $_REQUEST → $_POST con validación ?? ''
//   FIX #3: SQL injection corregido — prepared statements
//           en ambas queries dentro de VerImprimir()
//   FIX #4: rowCount() no confiable en PostgreSQL para SELECT
//           → reemplazado por fetch() directo
//   FIX #5: PDO::FETCH_BOTH → PDO::FETCH_ASSOC
//   FIX #6: $db_link y variables sin uso eliminadas
//           ($data, $header, $saldos, $print_sumas,
//            $print_no_header, $NombreMes, $nombresDias)
//   FIX #7: Query ejecutada DOS veces ($consulta_serie y
//           $consulta con el mismo SQL) → una sola ejecución
//           con fetchAll() y reutilización del array
//   FIX #8: strftime() deprecado en PHP 8.1
//           → reemplazado por date()
//   FIX #9: Content-Type corregido a application/pdf
//   FIX #10: Bug lógico — if($salto_columna = 0) era asignación
//            no comparación → corregido a if($salto_columna == 0)
//   FIX #11: mb_convert_encoding centralizado en enc()
// ============================================================
ob_start(); // FIX #1

$path_root = trim($_SERVER['DOCUMENT_ROOT']);
include($path_root . "/acomtus/includes/funciones.php");
include($path_root . "/acomtus/includes/mainFunctions_conexion.php");
include($path_root . "/acomtus/php_libs/fpdf/fpdf.php");

// FIX #9
header("Content-Type: application/pdf; charset=UTF-8");

// FIX #11: Encoding centralizado
function enc(string $str): string {
    return mb_convert_encoding($str, "ISO-8859-1", "UTF-8");
}

// FIX #2: $_REQUEST → $_POST con validación
$reimprimir        = isset($_POST['reimprimir']) ? (bool)$_POST['reimprimir'] : false;
$codigo_produccion = trim($_POST['codigo_produccion'] ?? '');
$fecha             = trim($_POST['fecha'] ?? '');

// FIX #6: $db_link eliminado (variable muerta)
$totalIngresoOK = 0;
$total          = 0;
$cantidadTiquete = 0;

// FIX #8: strftime() deprecado en PHP 8.1 → date()
date_default_timezone_set('America/El_Salvador');
setlocale(LC_TIME, 'es_SV');
setlocale(LC_MONETARY, 'es_ES');

$meses = ["enero","febrero","marzo","abril","mayo","junio",
          "julio","agosto","septiembre","octubre","noviembre","diciembre"];

$dia = date('d');           // FIX #8
$mes = $meses[date('n')-1]; // FIX #8
$año = date('Y');           // FIX #8

// ── Clase PDF (sin métodos adicionales — igual al original) ──
class PDF extends FPDF {}

// ── Crear el PDF ──────────────────────────────────────────────
$pdf = new PDF('P', 'mm', 'Letter');
$pdf->SetMargins(20, 20);
$pdf->SetAutoPageBreak(true, 5);
// FIX #6: $data y $header eliminados (no se usaban)
$pdf->AliasNbPages();

$pdf->SetY(20.5);
$pdf->SetX(15);
$pdf->SetFont('Arial', '', 9);

$w = [20, 50];           // serie y numero desde
$h = [6.5, 11, 5, 4];   // alto de columnas

$fill = false;

$pdf->SetXY(15, 52);

$colx_1 = 13; $colx_2 = 15;
$linea  = 0;  $salto_columna = 0;

// ── Lógica de selección de controles ─────────────────────────
if ($codigo_produccion != 0) {
    $guion = strpos($codigo_produccion, "-");
    $coma  = strpos($codigo_produccion, ",");

    if ($guion !== false && $coma !== false) {
        // Hay guión Y coma — caso no soportado
        ob_end_clean();
        exit("Error: el código de producción no puede contener guión y coma a la vez.");
    }

    if ($coma !== false) {
        // Lista separada por comas
        $codigo_produccion_ = array_map('trim', explode(",", $codigo_produccion));
        foreach ($codigo_produccion_ as $codigo_produccion_i) {
            VerImprimir($pdf, $dblink, $codigo_produccion_i, $reimprimir,
                        $cantidadTiquete, $w, $h, $colx_1, $colx_2, $linea, $salto_columna);
        }
    } else {
        if ($guion !== false) {
            // Rango separado por guión: ej. "100-115"
            $partes           = explode("-", $codigo_produccion);
            $codigo_partial_01 = (int)$partes[0];
            $codigo_partial_02 = (int)$partes[1];
            for ($jj = $codigo_partial_01; $jj <= $codigo_partial_02; $jj++) {
                VerImprimir($pdf, $dblink, $jj, $reimprimir,
                            $cantidadTiquete, $w, $h, $colx_1, $colx_2, $linea, $salto_columna);
            }
        } else {
            // Código único
            VerImprimir($pdf, $dblink, $codigo_produccion, $reimprimir,
                        $cantidadTiquete, $w, $h, $colx_1, $colx_2, $linea, $salto_columna);
        }
    }
} else {
    // Sin código específico: buscar todos los controles de la fecha
    // FIX #3: Prepared statement — evita SQL injection
    $stmt_p = $dblink->prepare(
        "SELECT id_ FROM produccion WHERE fecha = :fecha ORDER BY id_ ASC"
    );
    $stmt_p->execute([':fecha' => $fecha]);

    // FIX #4: rowCount() no confiable → fetchAll() directo
    $rows_p = $stmt_p->fetchAll(PDO::FETCH_ASSOC); // FIX #5
    foreach ($rows_p as $row) {
        $codigo_produccion_i = $row['id_'];
        VerImprimir($pdf, $dblink, $codigo_produccion_i, $reimprimir,
                    $cantidadTiquete, $w, $h, $colx_1, $colx_2, $linea, $salto_columna);
    }
}

// ── Salida ────────────────────────────────────────────────────
ob_end_clean(); // FIX #1
$pdf->Output('Control de Ingresos.pdf', 'I');


// ============================================================
// FUNCIÓN: VerImprimir
// FIX #3: Prepared statements en ambas queries
// FIX #4: rowCount() → fetchAll()
// FIX #5: FETCH_BOTH → FETCH_ASSOC
// FIX #7: Query ejecutada una sola vez con fetchAll()
// FIX #10: Bug if($salto_columna = 0) → if($salto_columna == 0)
// ============================================================
function VerImprimir(
    object &$pdf,
    object $dblink,
    $codigo_produccion_i,
    bool $reimprimir,
    int &$cantidadTiquete,
    array $w,
    array $h,
    int &$colx_1,
    int &$colx_2,
    int &$linea,
    int &$salto_columna
): void {
    $fill          = false;
    $colx_1        = 13;
    $colx_2        = 15;
    $linea         = 0;
    $salto_columna = 0;

    // FIX #3: Prepared statement — elimina SQL injection
    $sql = "SELECT p.id_ AS id_produccion, p.fecha, p.codigo_inventario_tiquete,
                p.codigo_personal, p.codigo_ruta,
                cat_ts.descripcion as nombre_serie,
                pa.id_ as id_produccion_asignado, pa.tiquete_desde, pa.tiquete_hasta,
                pa.total, pa.cantidad,
                pa.codigo_inventario_tiquete as codigo_serie_id,
                it.precio_publico,
                cat_r.descripcion as nombre_ruta,
                cat_j.id_ as id_jornada
            FROM produccion p
                INNER JOIN produccion_asignado pa ON pa.codigo_produccion = p.id_
                INNER JOIN inventario_tiquete it ON it.id_ = pa.codigo_inventario_tiquete
                INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie
                INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = p.codigo_jornada
                INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = p.codigo_ruta
            WHERE pa.codigo_produccion = :codigo_produccion
            ORDER BY pa.id_, p.codigo_inventario_tiquete";

    $stmt = $dblink->prepare($sql);
    $stmt->execute([':codigo_produccion' => $codigo_produccion_i]);

    // FIX #7: Una sola ejecución — fetchAll() y reutilizar el array
    // El original ejecutaba $dblink->query($query_c) DOS VECES seguidas
    // con el mismo SQL (para $consulta_serie y $consulta).
    // Ahora se hace una sola consulta y se trabaja sobre el array resultante.
    $filas = $stmt->fetchAll(PDO::FETCH_ASSOC); // FIX #5

    if (empty($filas)) {
        return; // FIX #4: sin rowCount()
    }

    // Construir arrays de series desde el resultado en memoria
    $nombre_serie_      = [];
    $nombre_serie_contar_valores = [];
    $nombre_ruta        = '';

    foreach ($filas as $row) {
        $nombre_serie_[] = trim($row['codigo_serie_id']);
        $nombre_ruta     = trim($row['nombre_ruta']);
    }

    $nombre_serie_unique         = array_unique($nombre_serie_);
    $nombre_serie_contar_valores = array_count_values($nombre_serie_);

    // Inicializar variables que se usan fuera del foreach
    $pa_codigo_produccion = '';
    $fecha_doc            = '';
    $id_jornada           = '';
    $precio_publico       = '0.00';
    $totalIngresoOK       = 0;
    $totalserie           = 0;

    // Agrupar filas por codigo_serie_id para el caso multi-serie
    $filas_por_serie = [];
    foreach ($filas as $row) {
        $filas_por_serie[trim($row['codigo_serie_id'])][] = $row;
    }

    if (count($nombre_serie_unique) > 1 && $nombre_ruta === 'Cobradores') {
        // ── CASO: Múltiples series (Cobradores) ──────────────
        $pdf->AddPage();
        $pdf->SetY(52);
        $totalIngresoOK   = 0;
        $totalserie       = 0;
        $lineaX           = 12;
        $lineaX1          = 35;
        $lineaXEspacio    = 7;
        $lineaXAncho      = 23;
        $pdf->SetX($colx_1);
        $linea++;

        foreach ($nombre_serie_contar_valores as $key => $value) {
            // FIX #3: Prepared statement para query por serie
            $stmt2 = $dblink->prepare(
                "SELECT p.id_ AS id_produccion, p.fecha, p.codigo_inventario_tiquete,
                    p.codigo_personal, p.fecha, p.codigo_ruta,
                    cat_ts.descripcion as nombre_serie,
                    pa.id_ as id_produccion_asignado, pa.tiquete_desde, pa.tiquete_hasta,
                    pa.total, pa.cantidad,
                    pa.codigo_inventario_tiquete as codigo_serie_id,
                    it.precio_publico,
                    cat_r.descripcion as nombre_ruta,
                    cat_j.id_ as id_jornada
                FROM produccion p
                    INNER JOIN produccion_asignado pa ON pa.codigo_produccion = p.id_
                    INNER JOIN inventario_tiquete it ON it.id_ = pa.codigo_inventario_tiquete
                    INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie
                    INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = p.codigo_jornada
                    INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = p.codigo_ruta
                WHERE pa.codigo_produccion = :codigo AND pa.codigo_inventario_tiquete = :serie
                ORDER BY pa.id_"
            );
            $stmt2->execute([
                ':codigo' => $codigo_produccion_i,
                ':serie'  => $key
            ]);

            // FIX #4 + #5
            $filas2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            foreach ($filas2 as $listado) {
                $id_pro_a             = trim($listado['id_produccion_asignado']);
                $pa_codigo_produccion = trim($listado['id_produccion']);
                $nombre_serie         = trim($listado['nombre_serie']);
                $tiquete_desde        = trim($listado['tiquete_desde']);
                $tiquete_hasta        = trim($listado['tiquete_hasta']);
                $total_fila           = trim($listado['total']);
                $cantidad             = trim($listado['cantidad']);
                $cantidadTiquete     += $cantidad;
                $precio_publico       = number_format((float)$listado['precio_publico'], 2);
                $fecha_doc            = cambiaf_a_normal(trim($listado['fecha']));
                $nombre_ruta          = trim($listado['nombre_ruta']);
                $id_jornada           = trim($listado['id_jornada']);
                $totalIngresoOK       = number_format($totalIngresoOK + $total_fila, 2);
                $totalserie           = number_format($totalserie + $total_fila, 2);

                $pdf->SetX($colx_1);
                $pdf->SetFont('Arial', '', 12);
                $pdf->cell($w[0], $h[0],
                    $nombre_serie . "    " . codigos_nuevos($tiquete_desde), 0, 1, 'L');
            }

            // Total por serie
            $pdf->SetX($colx_1);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->cell($w[0], $h[3], "___________", 0, 1, 'L');
            $pdf->SetX($colx_1);
            $pdf->cell($w[0], $h[2], "$   " . $totalIngresoOK, 0, 1, 'L');
            $pdf->SetFont('Arial', 'B', 14);
            $pdf->RotatedText($colx_1, 50, "$ " . $precio_publico, 0);
            $pdf->SetLineWidth(0.4);
            $pdf->line($lineaX, 52, $lineaX1, 52);

            // FIX #10: Bug corregido — era if($salto_columna = 0)
            // (asignación, siempre false) → ahora comparación ==
            if ($salto_columna == 0) {
                $colx_1         = 13;
                $colx_2         = 15;
                $totalIngresoOK = 0;
            } else {
                $salto_columna++;
                $colx_1        += 30;
                $pdf->SetY(52);
                $totalIngresoOK = 0;
                $lineaX        += $lineaXEspacio + $lineaXAncho;
                $lineaX1       += $lineaXAncho + $lineaXEspacio;
            }
        } // FIN foreach series

        // Datos de cabecera de página
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->RotatedText(190, 18, $pa_codigo_produccion, 0);
        $pdf->SetFont('Arial', '', 11);
        $pdf->RotatedText(155, 26, $fecha_doc, 0);
        $pdf->SetFont('Arial', '', 14);
        $pdf->RotatedText(26, 33, $nombre_ruta, 0);
        $pdf->RotatedText(128, 33, $id_jornada, 0);
        $pdf->SetFont('Arial', '', 9);
        $pdf->ln();
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->RotatedText(15, 120, "Total Entregado: $ ", 0);
        $pdf->RotatedText(58, 120, $totalserie, 0);

    } else {
        // ── CASO: Serie única (o ruta normal) ────────────────
        $totalIngresoOK = 0;

        $pdf->AddPage();
        $pdf->SetXY(15, 52);

        foreach ($filas as $listado) {
            if ($linea == 8) {
                $colx_1 += 30;
                $linea   = 0;
                $salto_columna = 1;
                $pdf->SetY(52);
            }
            $pdf->SetX($colx_1);
            $linea++;

            $id_pro_a             = trim($listado['id_produccion_asignado']);
            $pa_codigo_produccion = trim($listado['id_produccion']);
            $nombre_serie         = trim($listado['nombre_serie']);
            $tiquete_desde        = trim($listado['tiquete_desde']);
            $tiquete_hasta        = trim($listado['tiquete_hasta']);
            $precio_publico_raw   = $listado['precio_publico'];
            $fecha_doc            = cambiaf_a_normal(trim($listado['fecha']));
            $nombre_ruta          = trim($listado['nombre_ruta']);
            $id_jornada           = trim($listado['id_jornada']);

            if ($reimprimir) {
                $cantidad   = (($tiquete_hasta - $tiquete_desde) + 1);
                $total_fila = round($precio_publico_raw * $cantidad, 2);
            } else {
                $total_fila = $listado['total'];
                $cantidad   = $listado['cantidad'];
            }

            $cantidadTiquete    += $cantidad;
            $precio_publico      = number_format((float)$precio_publico_raw, 2);
            $totalIngresoOK      = number_format($totalIngresoOK + $total_fila, 2);

            $pdf->SetFont('Arial', '', 12);
            $pdf->cell($w[0], $h[0],
                $nombre_serie . "    " . codigos_nuevos($tiquete_desde), 0, 1, 'L');
        } // FIN foreach filas

        // Total e información de cabecera
        $pdf->SetX($colx_1);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->cell($w[0], $h[3], "___________", 0, 1, 'L');
        $pdf->SetX($colx_1);
        $pdf->cell($w[0], $h[2], "$   " . $totalIngresoOK, 0, 1, 'L');

        $pdf->SetFont('Arial', 'B', 13);
        $pdf->RotatedText(190, 18, $pa_codigo_produccion, 0);
        $pdf->SetFont('Arial', '', 11);
        $pdf->RotatedText(155, 26, $fecha_doc, 0);
        $pdf->SetFont('Arial', '', 14);
        $pdf->RotatedText(26, 33, $nombre_ruta, 0);
        $pdf->RotatedText(128, 33, $id_jornada, 0);
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->RotatedText(15, 50, "$ " . $precio_publico, 0);
        $pdf->SetLineWidth(0.4);
        $pdf->line(12, 52, 35, 52);
        $pdf->SetFont('Arial', '', 9);
        $pdf->ln();
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->RotatedText(15, 120, "Total Entregado: $ ", 0);
        $pdf->RotatedText(58, 120, $totalIngresoOK, 0);
    }
}
?>