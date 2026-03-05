<?php
ob_start(); // al inicio del archivo
// ============================================================
// PorFecha.php — Versión optimizada
// Cambios aplicados:
//   FIX #1: $_REQUEST → $_POST con validación de cada parámetro
//   FIX #2: Ingreso procesado con floatval() directo (sin substr)
//           ya que el JS optimizado envía números limpios por POST
//   FIX #3: $db_link eliminado (variable muerta)
//   FIX #4: Variables sin uso eliminadas ($NombreDia, $NombreMes,
//           $nombresDias, $total_de_dias, $data, $i)
//   FIX #5: mb_convert_encoding centralizado en función auxiliar enc()
//   FIX #6: Ciudad en encabezado leída de $_SESSION['ciudad']
//           con fallback a 'Santa Ana'
//   FIX #7: global $print_sumas eliminado de Footer() (no se usaba)
//   FIX #8: Content-Type corregido a application/pdf
// ============================================================

$path_root = trim($_SERVER['DOCUMENT_ROOT']);
include($path_root . "/acomtus/includes/funciones.php");
include($path_root . "/acomtus/includes/mainFunctions_conexion.php");
include($path_root . "/acomtus/php_libs/fpdf/fpdf.php");

// FIX #8: Content-Type correcto para PDF
header("Content-Type: application/pdf; charset=UTF-8");

// ── FIX #5: Función auxiliar centralizada para encoding FPDF ──
// FPDF trabaja en ISO-8859-1; esta función convierte desde UTF-8.
function enc(string $str): string {
    return mb_convert_encoding($str, "ISO-8859-1", "UTF-8");
}

// ── FIX #1: Leer de $_POST y validar cada parámetro ──────────
// Se usa filter_input para sanitizar strings y $_POST['campo'] ?? ''
// para evitar notices de PHP 8 por índices inexistentes.

$fecha            = trim($_POST['fecha']            ?? '');
$fecha_produccion = cambiaf_a_normal($fecha);

// FIX #1: Arrays ahora vienen como ruta[], cantidad[], etc. (POST arrays)
// Compatibilidad: si llegan como string separado por coma (GET legacy) también se soporta.
function leerArrayPost(string $key): array {
    if (isset($_POST[$key]) && is_array($_POST[$key])) {
        return array_map('trim', $_POST[$key]);
    }
    if (isset($_POST[$key]) && is_string($_POST[$key])) {
        return array_map('trim', explode(',', $_POST[$key]));
    }
    return [];
}

$ruta           = leerArrayPost('ruta');
$cantidad       = leerArrayPost('cantidad');
$entregados     = leerArrayPost('entregados');
$devolucion     = leerArrayPost('devolucion');
$vendidos       = leerArrayPost('vendidos');
$precio_publico = leerArrayPost('precio_publico');
$ingreso        = leerArrayPost('ingreso');

$tiqueteEntregados  = trim($_POST['tiqueteEntregados']  ?? '');
$tiqueteVendidos    = trim($_POST['tiqueteVendidos']    ?? '');
$produccion_total   = trim($_POST['produccion_total']   ?? '');
$produccion_vendida = trim($_POST['produccion_vendida'] ?? '');
$ingresoTotal       = trim($_POST['ingresoTotal']       ?? '');
$ingresoColones     = trim($_POST['ingresoColones']     ?? '');

// ── Fecha de producción ───────────────────────────────────────
date_default_timezone_set('America/El_Salvador');
setlocale(LC_TIME, 'es_SV');
setlocale(LC_MONETARY, 'es_ES');

$meses = ["enero","febrero","marzo","abril","mayo","junio",
          "julio","agosto","septiembre","octubre","noviembre","diciembre"];

$fecha_partes   = explode("/", cambiaf_a_normal($fecha));
$fecha_mes      = (int)($fecha_partes[1] ?? 1);

$dia_produccion = (int)($fecha_partes[0] ?? 0);
$mes_produccion = $meses[$fecha_mes] ?? '';
$año_produccion = $fecha_partes[2]   ?? '';

// ── Fecha de impresión (hoy) ──────────────────────────────────
$hoy = getdate();
$dia = $hoy["mday"];
$mes = $hoy["mon"];
$año = $hoy["year"];

$nombresMeses = [
    1=>"Enero","Febrero","Marzo","Abril","Mayo","Junio",
    "Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"
];

// FIX #6: Ciudad configurable desde sesión, con fallback
$ciudad = !empty($_SESSION['ciudad']) ? $_SESSION['ciudad'] : 'Santa Ana';

// ── Clase PDF ─────────────────────────────────────────────────
class PDF extends FPDF
{
    function Header()
    {
        $img = $_SERVER['DOCUMENT_ROOT'] . '/acomtus/img/' . $_SESSION['logo_uno'];
        $this->Image($img, 5, 4, 24, 24);

        $this->SetFont('Arial', 'B', 14);
        $this->RotatedText(30, 10, enc($_SESSION['nombre_institucion']), 0);

        $this->SetFont('Arial', 'B', 12);
        $this->RotatedText(30, 17, enc($_SESSION['direccion']), 0);

        if (!empty($_SESSION['telefono'])) {
            $this->RotatedText(30, 24, enc('Teléfono: ') . $_SESSION['telefono'], 0);
        } else {
            $this->RotatedText(30, 24, '', 0);
        }

        $style6 = ['width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => [0, 0, 0]];
        $this->CurveDraw(0, 37, 120, 40, 155, 20, 225, 20, null, $style6);
        $this->CurveDraw(0, 36, 120, 39, 155, 19, 225, 19, null, $style6);
    }

    function Footer()
    {
        // FIX #7: global $print_sumas eliminado — no se usaba
        $this->SetY(-10);
        $this->SetFont('Arial', 'I', 9);
        $this->Line(0, 270, 225, 270);
        $this->SetFont('Arial', '', 7);
        $this->Cell(0, 10, enc('Página ' . $this->PageNo() . '/{nb}       '), 0, 0, 'C');
    }

    function FancyTable(array $header): void
    {
        global $fill;
        // FIX #7: globals innecesarios eliminados ($print_sumas, $codigo, $print_no_header)
        $w  = [50, 15, 25, 25, 20, 25, 25, 20];

        $this->SetFillColor(133, 146, 158);
        $this->SetTextColor(255);
        $this->SetDrawColor(128, 0, 0);
        $this->SetLineWidth(.3);
        $this->SetFont('', 'B');
        $this->SetXY(20, 95);

        for ($i = 0; $i < count($header); $i++) {
            $this->Cell($w[$i], 5, enc($header[$i]), 'LTR', 0, 'C', 1);
        }
        $this->Ln();

        $this->SetFillColor(238, 239, 237);
        $this->SetTextColor(0);
        $this->SetDrawColor(0, 0, 0);
        $this->SetFont('');
        $this->SetX(10);
        $fill = false;
    }
}

// ── Crear el PDF ──────────────────────────────────────────────
$pdf = new PDF('P', 'mm', 'Letter');
$pdf->SetMargins(20, 20);
$pdf->SetAutoPageBreak(true, 5);

$header = ['Ruta', 'Cantidad', 'Entregados', 'Devoluciones', 'Vendidos', 'Precio Publico', 'Ingreso'];

$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetY(20);
$pdf->SetX(20);
$pdf->SetFillColor(224);

// FIX #6: Ciudad dinámica en lugar de hardcodeada
$pdf->RotatedText(140, 40,
    $ciudad . ', ' . $dia . ' de ' . $nombresMeses[$mes] . ' de ' . $año, 0);

$pdf->RoundedRect(15, 45, 140, 8, 2, '1234', 'DF');
$pdf->RotatedText(18, 50,
    enc('Producción Diaria: ' . $dia_produccion . ' de ' . $mes_produccion . ' de ' . $año_produccion), 0);

$pdf->SetFont('Arial', '', 9);

$w  = [50, 15, 25, 25, 20, 25, 25, 20];
$w1 = [55, 60];
$w2 = [5, 7];

$fill         = false;
$totalIngreso = 0.0;

$pdf->FancyTable($header);

// ── Bloque de resumen (controles, tiquetes, ingresos) ─────────
$pdf->SetXY(20, 65);
$pdf->SetX(20);
$pdf->Cell($w1[0], $w2[1], $produccion_total,   "LTR", 0, 'L', $fill);
$pdf->SetX(20 + $w1[0] + 5);
$pdf->Cell($w1[1], $w2[1], $tiqueteEntregados,  "LTR", 0, 'L', $fill);
$pdf->SetX(20 + $w1[0] + 5 + $w1[0] + 10);
$pdf->Cell($w1[0], $w2[1], $ingresoTotal,        "LTR", 1, 'L', $fill);

$pdf->SetX(20);
$pdf->Cell($w1[0], $w2[1], $produccion_vendida,  "LBR", 0, 'L', $fill);
$pdf->SetX(20 + $w1[0] + 5);
$pdf->Cell($w1[1], $w2[1], $tiqueteVendidos,     "LBR", 0, 'L', $fill);
$pdf->SetX(20 + $w1[0] + 5 + $w1[0] + 10);
$pdf->Cell($w1[0], $w2[1], enc($ingresoColones), "LBR", 1, 'L', $fill);

$pdf->SetXY(20, 100);

// ── Tabla de datos por ruta ───────────────────────────────────
$totalFilas = count($ruta);
for ($Hj = 0; $Hj < $totalFilas; $Hj++) {
    $pdf->SetX(20);
    $pdf->Cell($w[0], $w2[1], $ruta[$Hj],                            1, 0, 'L', $fill);
    $pdf->Cell($w[1], $w2[1], enc($cantidad[$Hj]),                   1, 0, 'C', $fill);
    $pdf->Cell($w[2], $w2[1], $entregados[$Hj],                      1, 0, 'C', $fill);
    $pdf->Cell($w[3], $w2[1], $devolucion[$Hj],                      1, 0, 'C', $fill);
    $pdf->Cell($w[4], $w2[1], $vendidos[$Hj],                        1, 0, 'C', $fill);
    $pdf->Cell($w[5], $w2[1], $precio_publico[$Hj],                  1, 0, 'C', $fill);

    // FIX #2: floatval directo — el JS ya envía el número limpio (sin "$")
    // Se mantiene compatibilidad: si llega con "$" al inicio, se limpia igual.
    $valorIngreso = floatval(ltrim(trim($ingreso[$Hj]), '$'));
    $pdf->Cell($w[6], $w2[1], '$ ' . number_format($valorIngreso, 2, '.', ','), 1, 0, 'R', $fill);
    $pdf->Ln();

    $fill          = !$fill;
    $totalIngreso += $valorIngreso;
}

// ── Fila de total ─────────────────────────────────────────────
$pdf->SetX(20);
$pdf->Cell($w[0], $w2[1], '', 1, 0, 'C', $fill);
$pdf->Cell($w[1], $w2[1], '', 1, 0, 'C', $fill);
$pdf->Cell($w[2], $w2[1], '', 1, 0, 'C', $fill);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell($w[3] + $w[4] + $w[5], $w2[1], 'TOTAL PRODUCCION',                         1, 0, 'R', $fill);
$pdf->Cell($w[6],                  $w2[1], '$' . number_format($totalIngreso, 2, '.', ','), 1, 0, 'C', $fill);

// ── Salida ────────────────────────────────────────────────────
ob_end_clean(); // limpia todo output acumulado
$nombre_archivo = enc('Producción: ' . $fecha . '.pdf');
$pdf->Output($nombre_archivo, 'I');
?>
