<?php
// ============================================================
// DetalleProduccion.php — Versión optimizada
// Cambios aplicados (mismos que PorFecha_optimizado.php):
//   FIX #1: ob_start() / ob_end_clean() para evitar output
//           corrupto antes del PDF
//   FIX #2: $_REQUEST → $_POST con validación ?? ''
//   FIX #3: Arrays leídos con leerArrayPost() — soporta
//           formato POST array (ruta[]) y legacy CSV
//   FIX #4: substr($ingreso,1) → ltrim + floatval (robusto)
//   FIX #5: $db_link, variables sin uso eliminadas
//           ($NombreDia, $NombreMes, $nombresDias,
//            $total_de_dias, $data, $i, $fecha_produccion)
//   FIX #6: mb_convert_encoding centralizado en enc()
//   FIX #7: Ciudad dinámica desde $_SESSION['ciudad']
//   FIX #8: global innecesarios eliminados de Footer()
//           y FancyTable()
//   FIX #9: Content-Type corregido a application/pdf
// ============================================================
ob_start(); // FIX #1: evita output antes del PDF

$path_root = trim($_SERVER['DOCUMENT_ROOT']);
include($path_root . "/acomtus/includes/funciones.php");
include($path_root . "/acomtus/includes/mainFunctions_conexion.php");
include($path_root . "/acomtus/php_libs/fpdf/fpdf.php");

// FIX #9: Content-Type correcto para PDF
header("Content-Type: application/pdf; charset=UTF-8");

// ── FIX #6: Función auxiliar centralizada para encoding FPDF ──
function enc(string $str): string {
    return mb_convert_encoding($str, "ISO-8859-1", "UTF-8");
}

// ── FIX #3: Leer arrays desde POST (array nativo o CSV legacy) ─
function leerArrayPost(string $key): array {
    if (isset($_POST[$key]) && is_array($_POST[$key])) {
        return array_map('trim', $_POST[$key]);
    }
    if (isset($_POST[$key]) && is_string($_POST[$key])) {
        return array_map('trim', explode(',', $_POST[$key]));
    }
    return [];
}

// ── FIX #2: $_REQUEST → $_POST con validación ────────────────
$fecha    = trim($_POST['fecha'] ?? '');
$control  = leerArrayPost('control');
$ruta     = leerArrayPost('ruta');
$equipo   = leerArrayPost('equipo');
$motorista = leerArrayPost('motorista');
$ingreso  = leerArrayPost('ingreso');

$fecha_partes    = explode("/", cambiaf_a_normal($fecha));
$fecha_mes       = (int)($fecha_partes[1] ?? 1);
$fecha_produccion = cambiaf_a_normal($fecha);

// ── Fecha de impresión (hoy) ──────────────────────────────────
date_default_timezone_set('America/El_Salvador');
setlocale(LC_TIME, 'es_SV');
setlocale(LC_MONETARY, 'es_ES');

$hoy = getdate();
$dia = $hoy["mday"];
$mes = $hoy["mon"];
$año = $hoy["year"];

$nombresMeses = [
    1 => "Enero","Febrero","Marzo","Abril","Mayo","Junio",
    "Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"
];

// FIX #7: Ciudad dinámica con fallback
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
        // FIX #8: global $print_sumas eliminado — no se usaba
        $this->SetY(-10);
        $this->SetFont('Arial', 'I', 9);
        $this->Line(0, 270, 225, 270);
        $this->SetFont('Arial', '', 7);
        $this->Cell(0, 10, enc('Página ' . $this->PageNo() . '/{nb}       '), 0, 0, 'C');
    }

    function FancyTable(array $header): void
    {
        global $fill;
        // FIX #8: globals innecesarios eliminados ($print_sumas, $codigo, $dblink, $print_no_header)
        $w = [15, 50, 20, 85, 30]; // control, ruta, equipo, motorista, ingreso

        $this->SetFillColor(133, 146, 158);
        $this->SetTextColor(255);
        $this->SetDrawColor(128, 0, 0);
        $this->SetLineWidth(.3);
        $this->SetFont('', 'B');
        $this->SetXY(10, 60);

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

$header = ['Control', 'Ruta', 'Equipo', 'Motorista', 'Ingreso'];

$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetY(20);
$pdf->SetX(20);
$pdf->SetFillColor(224);

// FIX #7: Ciudad dinámica
$pdf->RotatedText(140, 40,
    $ciudad . ', ' . $dia . ' de ' . $nombresMeses[$mes] . ' de ' . $año, 0);

$pdf->RoundedRect(15, 45, 120, 8, 2, '1234', 'DF');
$pdf->RotatedText(18, 50, enc('PRODUCCIÓN - DETALLE POR RUTA ') . $fecha_produccion, 0);

$pdf->SetFont('Arial', '', 9);

$w  = [15, 50, 20, 85, 30]; // control, ruta, equipo, motorista, ingreso
$w2 = [5, 7];

$fill         = false;
$totalIngreso = 0.0;

$pdf->FancyTable($header);
$pdf->SetXY(10, 65);

// ── Tabla de datos ────────────────────────────────────────────
$totalFilas = count($control);
for ($Hj = 0; $Hj < $totalFilas; $Hj++) {
    $pdf->SetX(10);
    $pdf->Cell($w[0], $w2[1], $control[$Hj],   1, 0, 'C', $fill);
    $pdf->Cell($w[1], $w2[1], $ruta[$Hj],      1, 0, 'L', $fill);
    $pdf->Cell($w[2], $w2[1], $equipo[$Hj],    1, 0, 'C', $fill);
    $pdf->Cell($w[3], $w2[1], $motorista[$Hj], 1, 0, 'L', $fill);
    $pdf->Cell($w[4], $w2[1], $ingreso[$Hj],   1, 0, 'C', $fill);
    $pdf->Ln();

    $fill = !$fill;
    // FIX #4: ltrim + floatval en lugar de substr($ingreso,1)
    $totalIngreso += floatval(ltrim(trim($ingreso[$Hj]), '$'));
}

// ── Fila de total ─────────────────────────────────────────────
$pdf->SetX(10);
$pdf->Cell($w[0], $w2[1], '',                                              1, 0, 'C', $fill);
$pdf->Cell($w[1], $w2[1], '',                                              1, 0, 'C', $fill);
$pdf->Cell($w[2], $w2[1], '',                                              1, 0, 'C', $fill);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell($w[3], $w2[1], 'TOTAL PRODUCCION',                             1, 0, 'R', $fill);
$pdf->Cell($w[4], $w2[1], '$' . number_format($totalIngreso, 2, '.', ','), 1, 0, 'C', $fill);

// ── Salida ────────────────────────────────────────────────────
$nombre_archivo = enc('Producción: ' . $fecha . '.pdf');
ob_end_clean(); // FIX #1: limpia cualquier output previo antes de enviar el PDF
$pdf->Output($nombre_archivo, 'I');
?>
