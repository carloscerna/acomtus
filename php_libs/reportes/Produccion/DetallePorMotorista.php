<?php
// ============================================================
// DetallePorMotorista.php — Versión optimizada
// Cambios aplicados:
//   FIX #1: ob_start() / ob_end_clean() — evita PDF corrupto
//   FIX #2: $_REQUEST → $_POST con validación ?? ''
//   FIX #3: Arrays con leerArrayPost() — soporta POST array
//           y CSV legacy
//   FIX #4: substr($ingreso,1) → ltrim + floatval (robusto)
//   FIX #5: $db_link y variables sin uso eliminadas
//           ($NombreDia, $NombreMes, $nombresDias,
//            $total_de_dias, $data, $i, $meses, $NombreMes)
//   FIX #6: mb_convert_encoding centralizado en enc()
//   FIX #7: Ciudad dinámica desde $_SESSION['ciudad']
//   FIX #8: globals innecesarios eliminados en Footer()
//           y FancyTable()
//   FIX #9: Content-Type corregido a application/pdf
//   FIX #10: Imagen del empleado con verificación de existencia
//            — evita error fatal de FPDF si la ruta no existe
//   FIX #11: Total solo suma filas con estatus "Vendido"
//            (comportamiento original conservado y documentado)
// ============================================================
ob_start(); // FIX #1

$path_root = trim($_SERVER['DOCUMENT_ROOT']);
include($path_root . "/acomtus/includes/funciones.php");
include($path_root . "/acomtus/includes/mainFunctions_conexion.php");
include($path_root . "/acomtus/php_libs/fpdf/fpdf.php");

// FIX #9: Content-Type correcto
header("Content-Type: application/pdf; charset=UTF-8");

// ── FIX #6: Encoding centralizado ────────────────────────────
function enc(string $str): string {
    return mb_convert_encoding($str, "ISO-8859-1", "UTF-8");
}

// ── FIX #3: Leer arrays desde POST (nativo o CSV legacy) ─────
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
$fecha          = trim($_POST['fecha']          ?? '');
$NombreMotorista = trim($_POST['NombreMotorista'] ?? '');
$ImagenFoto     = trim($_POST['ImagenPersonal'] ?? '');
$codigo         = trim($_POST['codigo']         ?? '');
$ruta           = trim($_POST['ruta']           ?? '');
$unidad         = trim($_POST['unidad']         ?? '');
$precio         = trim($_POST['precio']         ?? '');
$cantidad       = trim($_POST['cantidad']       ?? '');
$total          = trim($_POST['total']          ?? '');

$correlativo    = leerArrayPost('correlativo');
$estatus        = leerArrayPost('estatus');
$serie          = leerArrayPost('serie');
$cola           = leerArrayPost('cola');
$desde          = leerArrayPost('desde');
$hasta          = leerArrayPost('hasta');
$ingreso        = leerArrayPost('ingreso');

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

// FIX #10: Verificar existencia de la imagen del empleado
// Si no existe o la ruta está vacía, usar avatar por defecto
$avatarMasculino = $path_root . '/acomtus/img/avatar_masculino.png';
if (!empty($ImagenFoto)) {
    $rutaImagen = $path_root . $ImagenFoto;
    // Verificar que el archivo exista y sea una imagen válida
    if (!file_exists($rutaImagen) || !@getimagesize($rutaImagen)) {
        $rutaImagen = $avatarMasculino;
    }
} else {
    $rutaImagen = $avatarMasculino;
}

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
        // FIX #8: globals innecesarios eliminados
        $w = [25, 25, 10, 20, 20, 20, 20]; // correlativo, estatus, serie, cola, desde, hasta, ingreso

        $this->SetFillColor(133, 146, 158);
        $this->SetTextColor(255);
        $this->SetDrawColor(128, 0, 0);
        $this->SetLineWidth(.3);
        $this->SetFont('', 'B');
        $this->SetXY(30, 95);

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

$header = ['Correlativo', 'Estatus', 'Serie', 'Cola', 'Desde', 'Hasta', 'Ingreso'];

$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetY(20);
$pdf->SetX(20);
$pdf->SetFillColor(224);

// FIX #7: Ciudad dinámica
$pdf->RotatedText(140, 40,
    $ciudad . ', ' . $dia . ' de ' . $nombresMeses[$mes] . ' de ' . $año, 0);

$pdf->RoundedRect(15, 45, 140, 8, 2, '1234', 'DF');
$pdf->RotatedText(18, 50, enc('EMPLEADO: ' . $NombreMotorista), 0);

$pdf->SetFont('Arial', '', 9);

$w  = [25, 25, 10, 20, 20, 20, 20]; // correlativo, estatus, serie, cola, desde, hasta, ingreso
$w1 = [55];
$w2 = [5, 7];

$fill         = false;
$totalIngreso = 0.0;

$pdf->FancyTable($header);
$pdf->SetXY(30, 55);

// ── FIX #10: Imagen del empleado con ruta verificada ─────────
$pdf->Image($rutaImagen, 30, 55, 30, 35);

// ── Bloque de información del empleado ───────────────────────
$pdf->SetX(70);
$pdf->Cell($w1[0], $w2[1], enc('Código: ')         . $codigo,   1, 0, 'L', $fill);
$pdf->SetX(70 + $w1[0] + 10);
$pdf->Cell($w1[0], $w2[1], enc('Precio Público: ') . $precio,   1, 1, 'L', $fill);

$pdf->SetX(70);
$pdf->Cell($w1[0], $w2[1], 'Ruta: '               . $ruta,     1, 0, 'L', $fill);
$pdf->SetX(70 + $w1[0] + 10);
$pdf->Cell($w1[0], $w2[1], 'Cantidad Vendida: '   . $cantidad, 1, 1, 'L', $fill);

$pdf->SetX(70);
$pdf->Cell($w1[0], $w2[1], 'Unidad: '             . $unidad,   1, 0, 'L', $fill);
$pdf->SetX(70 + $w1[0] + 10);
$pdf->Cell($w1[0], $w2[1], 'Total: '              . $total,    1, 1, 'L', $fill);

$pdf->SetX(70);
$pdf->Cell($w1[0], $w2[1], 'Fecha: '              . cambiaf_a_normal($fecha), 1, 1, 'L', $fill);

$pdf->SetXY(30, 100);

// ── Tabla de datos ────────────────────────────────────────────
$totalFilas = count($correlativo);
for ($Hj = 0; $Hj < $totalFilas; $Hj++) {
    $pdf->SetX(30);
    $pdf->Cell($w[0], $w2[1], $correlativo[$Hj],             1, 0, 'C', $fill);
    $pdf->Cell($w[1], $w2[1], enc($estatus[$Hj]),            1, 0, 'L', $fill);
    $pdf->Cell($w[2], $w2[1], $serie[$Hj],                   1, 0, 'C', $fill);
    $pdf->Cell($w[3], $w2[1], $cola[$Hj],                    1, 0, 'C', $fill);
    $pdf->Cell($w[4], $w2[1], $desde[$Hj],                   1, 0, 'C', $fill);
    $pdf->Cell($w[5], $w2[1], $hasta[$Hj],                   1, 0, 'C', $fill);
    $pdf->Cell($w[6], $w2[1], $ingreso[$Hj],                 1, 0, 'R', $fill);
    $pdf->Ln();

    $fill = !$fill;

    // FIX #11: Solo sumar estatus "Vendido" (comportamiento original conservado)
    // FIX #4: ltrim + floatval en lugar de substr($ingreso,1)
    if (trim($estatus[$Hj]) === 'Vendido') {
        $totalIngreso += floatval(ltrim(trim($ingreso[$Hj]), '$'));
    }
}

// ── Fila de total ─────────────────────────────────────────────
$pdf->SetX(30);
$pdf->Cell($w[0], $w2[1], '',                                               1, 0, 'C', $fill);
$pdf->Cell($w[1], $w2[1], '',                                               1, 0, 'C', $fill);
$pdf->Cell($w[2], $w2[1], '',                                               1, 0, 'C', $fill);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell($w[3] + $w[4] + $w[5], $w2[1], 'TOTAL PRODUCCION',              1, 0, 'R', $fill);
$pdf->Cell($w[6],                  $w2[1], '$' . number_format($totalIngreso, 2, '.', ','), 1, 0, 'C', $fill);

// ── Salida ────────────────────────────────────────────────────
$nombre_archivo = enc('Producción: ' . $fecha . '.pdf');
ob_end_clean(); // FIX #1: limpia output antes de enviar el PDF
$pdf->Output($nombre_archivo, 'I');
?>
