<?php
/**
 * Diario.php - Corregido y Optimizado para PHP 8.x
 *
 * Cambios realizados:
 * - Operador de fusión null (??) en $_REQUEST y $_SESSION para evitar Warnings.
 * - Array $resumen convertido a valores float numéricos estrictos.
 * - Validación de existencia de imagen de logo para evitar error fatal en FPDF.
 * - Inicialización asegurada en bucles.
 * - Mantenimiento de las funciones utf8_to_latin1() y cstr().
 */

// ruta de los archivos con su carpeta
    $path_root = trim($_SERVER['DOCUMENT_ROOT'] ?? '');
// archivos que se incluyen.
    include($path_root."/acomtus/includes/funciones.php");
    include($path_root."/acomtus/includes/mainFunctions_conexion.php");
// Llamar a la libreria fpdf
    include($path_root."/acomtus/php_libs/fpdf/fpdf.php");
// cambiar a utf-8.
    header("Content-Type: text/html; charset=UTF-8");
// COLOCAR UN LIMITE A LA MEMORIA PARA LA CREACIÓN DE LA HOJA DE CÁLCULO.
    set_time_limit(0);
    ini_set("memory_limit", "1024M");

/**
 * Reemplaza utf8_decode() (deprecada en PHP 8.2).
 * Convierte una cadena UTF-8 a ISO-8859-1 (Latin-1) para FPDF.
 */
function utf8_to_latin1(string $str): string {
    return mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
}

/**
 * Convierte cualquier valor (incluido null proveniente de BD) a string seguro.
 * Evita el error: "Passing null to parameter #1 ($haystack) of type string"
 * que FPDF lanza internamente cuando recibe null en Cell().
 */
// En PHP 7.4 eliminamos el tipo "mixed" para evitar el Fatal Error
// En PHP 8.x funcionará perfectamente sin el tipo declarado o usando mixed (pero para compatibilidad dual es mejor así)
function cstr($val, $default = '') {
    // Si es null o no está definido, devolvemos el default
    if (!isset($val) || $val === null) {
        return $default;
    }
    // Si es string, limpiamos espacios
    if (is_string($val)) {
        return trim($val);
    }
    return $val;
}
// variables y consulta a la tabla.
    // VALORES DEL POST (Con fusión null para PHP 8)
        $fecha          = trim($_REQUEST['fecha'] ?? '');
        $fecha_         = cambiaf_a_normal($_REQUEST["fecha"] ?? '');
        $fecha_partial  = explode("-", $fecha);
        // Valores pasados a float estricto
        $resumen        = array(0.20, 0.25, 0.35); 
        $resumen_pasajes        = array();
        $resumen_ingresos       = array();
        $resumen_pasajes_020    = array();
        $resumen_pasajes_025    = array();
        $resumen_pasajes_035    = array();
        $resumen_ingreso_020    = array();
        $resumen_ingreso_025    = array();
        $resumen_ingreso_035    = array();
        $tiquete_20             = 0;
        $db_link                = $dblink;
        $salto                  = 0;
        $total_general_ingresos = 0;
        $total_unidades         = 0;
        $total_tiquetes_vendidos= 0;
        $print_no_header        = 0;
        $total_buses            = 0;
        // Variables que se usan dentro del bloque de cobradores — se inicializan para evitar warnings
        $codigo_produccion_vv   = array();
        $nombre_serie_          = array();
        $nombre_serie_unique    = array();
        $nombre_ruta            = '';
        $encabezado_precio_publico = array();
        $precio_publico_cobradores = array();
        $tiquete_vendido_cobradores = array();
        $total_ingreso_cobradores   = array();
        $total_vendido_cobradores   = array();
        $cantidad_buses_cobradores  = array();
        $tiquete_               = array();

    // Establecer formato para la fecha.
        date_default_timezone_set('America/El_Salvador');
        setlocale(LC_TIME, 'es_SV');

        $meses = array("","enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre");

        $dia  = (int)($fecha_partial[2] ?? 0);
        $mes  = $meses[(int)($fecha_partial[1] ?? 0)] ?? '';
        $año  = $fecha_partial[0] ?? '';

        setlocale(LC_MONETARY, "es_ES");

class PDF extends FPDF
{
    // Cabecera de página
    public function Header(): void
    {
        // Logo con validación de existencia para evitar Fatal Errors
        $logo = $_SESSION['logo_uno'] ?? 'default.png';
        $img = $_SERVER['DOCUMENT_ROOT'].'/acomtus/img/'.$logo;
        if (file_exists($img) && !is_dir($img)) {
            $this->Image($img, 5, 4, 24, 24);
        }
        
        // Arial bold 14
        $this->SetFont('Arial', 'B', 14);
        
        // Título con manejo de variables nulas
        $institucion = $_SESSION['nombre_institucion'] ?? 'Nombre de Institución no definido';
        $this->RotatedText(30, 10, utf8_to_latin1($institucion), 0);
        
        // Arial bold 12
        $this->SetFont('Arial', 'B', 12);
        $direccion = $_SESSION['direccion'] ?? '';
        $this->RotatedText(30, 17, utf8_to_latin1($direccion), 0);

        // Teléfono.
        $telefono = $_SESSION['telefono'] ?? '';
        if (empty($telefono)) {
            $this->RotatedText(30, 24, '', 0, 1, 'C');
        } else {
            $this->RotatedText(30, 24, utf8_to_latin1('Teléfono: ').$telefono, 0, 1, 'C');
        }
        
        // ARMAR ENCABEZADO.
        $style6 = array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(0,0,0));
        $this->CurveDraw(0, 37, 120, 40, 155, 20, 225, 20, '', $style6);
        $this->CurveDraw(0, 36, 120, 39, 155, 19, 225, 19, '', $style6);
    }

    public function Footer(): void
    {
        global $print_sumas;
        // Posición: a 1,5 cm del final
        $this->SetY(-10);
        $this->SetFont('Arial', 'I', 9);

        if (isset($print_sumas) && $print_sumas === 1) {
            // Crear una línea de la primera firma.
            $this->Line(15, 255, 85, 255);
            $this->RotatedText(15, 260, utf8_to_latin1('Revisado por:'), 0);
            $style6 = array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(0,0,0));
        }
        // Línea pie de página
        $this->Line(0, 270, 225, 270);
        // Número de página
        $this->SetFont('Arial', '', 7);
        $this->Cell(0, 10, utf8_to_latin1('Página '.$this->PageNo().'/{nb}       '), 0, 0, 'C');
    }

    // Tabla coloreada
    public function FancyTable(array $header): void
    {
        global $print_sumas, $codigo, $dblink, $fill, $print_no_header;

        if (isset($print_sumas) && $print_sumas === 1) {
            global $suma_p, $suma_d, $saldo_p;
            $this->SetX(10);
            $this->Cell(40, 7, 'SUMAS  ', 1, 0, 'C', $fill);
            $this->Cell(20, 7, 'FIANZAS $', 0, 0, 'C', $fill);
            $this->Cell(25, 7, $suma_p ?? '', 1, 0, 'C', $fill);
            $this->Cell(30, 7, 'DEVOLUCIONES $', 0, 0, 'C', $fill);
            $this->Cell(25, 7, $suma_d ?? '', 1, 0, 'C', $fill);
            $this->Cell(25, 7, 'SALDO $', 0, 0, 'C', $fill);
            $this->Cell(25, 7, $saldo_p ?? '', 1, 1, 'C', $fill);
        }

        if ($print_no_header === 0) {
            // Colores, ancho de línea y fuente en negrita
            $this->SetFillColor(255, 255, 255);
            $this->SetTextColor(0);
            $this->SetDrawColor(0, 0, 0);
            $this->SetLineWidth(.3);
            $this->SetFont('', 'B');
            // Anchos de columnas
            $w = array(65, 25, 35, 30, 35);
            for ($i = 0; $i < count($header); $i++) {
                $this->Cell($w[$i], 7, utf8_to_latin1($header[$i]), 1, 0, 'C', 1);
            }
            $this->Ln();
            // Restauración de colores y fuentes
            $this->SetFillColor(255, 255, 255);
            $this->SetTextColor(0);
            $this->SetFont('');
            $this->SetX(10);
            $fill = false;
        }
    }
}

//************************************************************************************************************************
// Creando el Informe.
    $pdf = new PDF('P', 'mm', 'Letter');
    // Establecemos los márgenes izquierda, arriba y derecha:
    $pdf->SetMargins(20, 20);
    // Establecemos el margen inferior:
    $pdf->SetAutoPageBreak(true, 5);
    $data = array();
// Títulos de las columnas
    $header = array('Ruta','Pasajes','Precio Unitario','Ingresos','Cantidad Controles');
    $pdf->AliasNbPages();
    $pdf->AddPage();
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetY(20);
    $pdf->SetX(15);
// Diseño de Lineas y Rectangulos.
    $pdf->SetFillColor(224);
    // FECHA.
    $pdf->RotatedText(130, 40, 'Santa Ana, ' . $dia . ' de ' . $mes . ' de ' . $año, 0);
    // estado de cuenta
    $pdf->RoundedRect(15, 45, 80, 8, 2, '1234', 'DF');
    $pdf->RotatedText(18, 50, 'REPORTE DE INGRESO DIARIO', 0);
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetFont('Arial', '', 11);
//  mostrar los valores de la consulta
    $w = array(65, 25, 35, 30, 35, 70, 15, 40); // RUTA, PASAJES, PRECIO UNITARIO, INGRESOS, CANTIDAD BUSES, DESCRIPCION, VALOR, CONCEPTO
    $h = array(5, 7);
// Variables.
    $fill = false;
    $i    = 1;
// Posición inicial.
    $pdf->SetXY(10, 60);

    // DATOS NECESARIOS PARA CATALOGO RUTA
    $query = "SELECT id_ruta, codigo, descripcion FROM catalogo_ruta ORDER BY codigo";
    $consulta_ruta = $dblink->query($query);

    while ($listado = $consulta_ruta->fetch(PDO::FETCH_BOTH))
    {
        $codigo_ruta      = $listado['id_ruta'];
        $descripcion_ruta = $listado['descripcion'];

        // CATALOGO TIQUETE COLOR
        $query_tc    = "SELECT id_, precio_publico FROM catalogo_tiquete_color ORDER BY precio_publico";
        $consulta_tc = $dblink->query($query_tc);

        // controlar el encabezado
        $fila = 0; $total_por_ruta = 0; $fila_precio = 0; $resumen_precio = array();

        while ($listado_tc = $consulta_tc->fetch(PDO::FETCH_BOTH))
        {
            $codigo_tiquete_color = $listado_tc['id_'];
            $precio_publico       = (string)$listado_tc['precio_publico']; // Asegurar que sea string para los switch

            // REVISAR SI HUBO MOVIMIENTO EN LA PRODUCCION.
            $query_v    = "SELECT * FROM produccion WHERE codigo_estatus = '02' AND fecha = '$fecha' AND codigo_ruta = '$codigo_ruta' AND codigo_tiquete_color = '$codigo_tiquete_color'";
            $consulta_v = $dblink->query($query_v);

            if ($consulta_v->rowCount() != 0)
            {
                // SI CODIGO RUTA ES IGUAL A COBRADORES.
                if ($codigo_ruta == 10) {

                    // Reinicializar arrays para este ciclo
                    $codigo_produccion_vv = array();

                    // OBTENER LA CANTIDAD DE TIQUETES VENDIDOS.
                    $query_vv    = "SELECT * FROM produccion WHERE codigo_estatus = '02' AND fecha = '$fecha' AND codigo_ruta = '$codigo_ruta'";
                    $consulta_vv = $dblink->query($query_vv);
                    while ($listado_vv = $consulta_vv->fetch(PDO::FETCH_BOTH)) {
                        $codigo_produccion_vv[] = $listado_vv['id_'];
                    }

                    // Usar sintaxis explícita para interpolación de array en string (PHP 8.2+)
                    $primer_id = $codigo_produccion_vv[0] ?? 0;
                    $query_c   = "SELECT p.id_ AS id_produccion, p.fecha, p.codigo_inventario_tiquete, p.codigo_personal, p.fecha, p.codigo_ruta,
                                    cat_ts.descripcion as nombre_serie,
                                    pa.id_ as id_produccion_asignado, pa.tiquete_desde, pa.tiquete_hasta, pa.total, pa.cantidad, pa.codigo_inventario_tiquete as codigo_serie_id,
                                    it.precio_publico,
                                    cat_r.descripcion as nombre_ruta,
                                    cat_j.id_ as id_jornada
                                        FROM produccion p
                                        INNER JOIN produccion_asignado pa ON pa.codigo_produccion = p.id_
                                        INNER JOIN inventario_tiquete it ON it.id_ = pa.codigo_inventario_tiquete
                                        INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie
                                        INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = p.codigo_jornada
                                        INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = p.codigo_ruta
                                            WHERE p.codigo_estatus = '02' AND p.id_ = '{$primer_id}'
                                                ORDER BY pa.id_, p.codigo_inventario_tiquete";

                    $consulta_serie  = $dblink->query($query_c);
                    $nombre_serie_   = array();
                    $nombre_ruta     = '';

                    if ($consulta_serie->rowCount() != 0) {
                        $nombre_serie_contar_valores = array();
                        while ($listado_serie = $consulta_serie->fetch(PDO::FETCH_BOTH)) {
                            $nombre_serie_[] = trim($listado_serie['codigo_serie_id']);
                            $nombre_ruta     = trim($listado_serie['nombre_ruta']);
                        }
                    }

                    $nombre_serie_unique        = array_unique($nombre_serie_);
                    $nombre_serie_contar_valores = array_count_values($nombre_serie_);

                    // VARIABLES DE COLUMNA, FILA Y TOTAL INGRESO OK
                    $totalIngresoOK            = 0;
                    $totalserie                = 0;
                    $tiquete_vendido_cc        = 0;
                    $tiquete_vendido_cobradores= array();
                    $total_vendido_cobradores  = array();
                    $total_ingreso_cobradores  = array();
                    $cantidad_buses_cobradores = array();
                    $precio_publico_cobradores = array();
                    $total_por_ruta_cobradores = 0;
                    $total_por_tiquete_cobradores = 0;
                    $tiquete_                  = array();
                    $encabezado_precio_publico = array();

                    for ($jj = 0; $jj < count($codigo_produccion_vv); $jj++) {
                        if (count($nombre_serie_unique) > 1 && $nombre_ruta == 'Cobradores') {
                            foreach ($nombre_serie_unique as $key => $value) {
                                // Interpolación explícita del array para PHP 8.2+
                                $id_prod_jj = $codigo_produccion_vv[$jj];
                                $query_vendidos_04 = "SELECT pa.total, pa.codigo_inventario_tiquete, count(pa.id_) as cantidad_buses, sum(pa.total) as total_ingreso, sum(pa.cantidad) as tiquete_vendido,
                                        it.precio_publico
                                        FROM produccion_asignado pa
                                        INNER JOIN inventario_tiquete it ON pa.codigo_inventario_tiquete = it.id_
                                        WHERE pa.codigo_produccion = {$id_prod_jj} AND pa.codigo_estatus = '05' AND pa.codigo_inventario_tiquete = '$value'
                                            GROUP BY pa.codigo_inventario_tiquete, pa.total, it.precio_publico";

                                $consulta_vendidos_04 = $dblink->query($query_vendidos_04);
                                if ($consulta_vendidos_04->rowCount() != 0) {
                                    while ($listado_vendidos_04 = $consulta_vendidos_04->fetch(PDO::FETCH_BOTH)) {
                                        $tiquete_vendido_cobradores[]    = $listado_vendidos_04['tiquete_vendido'];
                                        $total_ingreso_cobradores[]      = $listado_vendidos_04['total_ingreso'];
                                        $precio_publico_cobradores[]     = $listado_vendidos_04['precio_publico'];
                                        $encabezado_precio_publico[]     = $listado_vendidos_04['precio_publico'];
                                        $tiquete_[]["vendido"]           = $listado_vendidos_04['tiquete_vendido'];
                                        $tiquete_[]["ingreso"]           = $listado_vendidos_04['total_ingreso'];
                                        $tiquete_[]["precio"]            = $listado_vendidos_04['precio_publico'];
                                    }
                                }
                            } // FIN DEL FOREACH
                        } // IF NOMBRE RUTA == COBRADORES
                    } // FOR CODIGO_PRODUCCION MATRIZ

                    for ($jjh = 0; $jjh < count($precio_publico_cobradores); $jjh++) {
                        switch ((string)$precio_publico_cobradores[$jjh]) {
                            case '0.20':
                                $resumen_pasajes_020[] = array_sum($tiquete_vendido_cobradores);
                                break;
                            case '0.25':
                                $resumen_pasajes_025[] = array_sum($tiquete_vendido_cobradores);
                                break;
                            case '0.35':
                                $resumen_pasajes_035[] = array_sum($tiquete_vendido_cobradores);
                                break;
                        }
                        // SUMAS
                        $total_por_ruta_cobradores    = $total_por_ruta_cobradores + array_sum($total_ingreso_cobradores);
                        $total_por_tiquete_cobradores = $total_por_tiquete_cobradores + array_sum($tiquete_vendido_cobradores);

                        // A Pantalla
                        $pdf->SetX(10);
                        $fila++;
                        // Precio publico como string para Cell()
                        $precio_pub_str = (string)($precio_publico_cobradores[$jjh] ?? '');

                        if ($fila == 1) {
                            $pdf->FancyTable($header);
                            $total_ingreso = array_sum($total_ingreso_cobradores);
                            $ingresos      = number_format($total_ingreso, 2, '.', ',');

                            $pdf->Cell($w[0], $h[0], cstr($descripcion_ruta), 0, 0, 'L', $fill);
                            $pdf->Cell($w[1], $h[0], cstr(array_sum($tiquete_vendido_cobradores)), 0, 0, 'C', $fill);
                            $pdf->Cell($w[2], $h[0], cstr($precio_pub_str), 0, 0, 'C', $fill);
                            $pdf->Cell($w[3], $h[0], number_format((float)$total_ingreso, 2, '.', ','), 0, 0, 'R', $fill);
                            $pdf->Cell($w[4], $h[0], cstr(count($codigo_produccion_vv)), 0, 0, 'C', $fill);
                            $tiquete_vendido_cobradores = array(); $total_vendido_cobradores = array(); $total_ingreso_cobradores = array(); $cantidad_buses_cobradores = array();
                        } else {
                            $total_ingreso = array_sum($total_ingreso_cobradores);
                            $pdf->Cell($w[0], $h[0], '', 0, 0, 'C', $fill);
                            $pdf->Cell($w[1], $h[0], cstr(array_sum($tiquete_vendido_cobradores)), 0, 0, 'C', $fill);
                            $pdf->Cell($w[2], $h[0], cstr($precio_pub_str), 0, 0, 'C', $fill);
                            $pdf->Cell($w[3], $h[0], number_format((float)$total_ingreso, 2, '.', ','), 0, 0, 'R', $fill);
                            $pdf->Cell($w[4], $h[0], '', 0, 0, 'C', $fill);
                            $tiquete_vendido_cobradores = array(); $total_vendido_cobradores = array(); $total_ingreso_cobradores = array(); $cantidad_buses_cobradores = array();
                        }
                        $pdf->ln();
                    }

                    // Imprimir subtotales cobradores.
                    if ($total_por_ruta_cobradores > 0) {
                        $pdf->SetX(10);
                        $pdf->Cell($w[0], $h[0], '', 0, 0, 'L', $fill);
                        $pdf->SetFont('Arial', 'B', 9);
                            $pdf->Cell($w[1], $h[0], 'Total Ruta: ' . cstr($descripcion_ruta), 0, 0, 'R', $fill);
                        $pdf->SetFont('Arial', '', 11);
                        $pdf->Cell($w[2], $h[0], '', 0, 0, 'C', $fill);
                        $pdf->Cell($w[3], $h[0], '$ '.number_format((float)$total_por_ruta_cobradores, 2, '.', ','), 'TB', 0, 'R', $fill);
                        $pdf->Cell($w[4], $h[0], '', 0, 1, 'C', $fill);
                        $pdf->ln();
                        // TOTALES GENERALES
                        $total_general_ingresos  = $total_general_ingresos + $total_por_ruta_cobradores;
                        $total_tiquetes_vendidos = $total_tiquetes_vendidos + $total_por_tiquete_cobradores;
                    }

                } else {
                    // OBTENER LA CANTIDAD DE TIQUETES VENDIDOS (ruta normal, no cobradores).
                    $codigo_produccion_ = array();
                    while ($listado_codigo_produccion = $consulta_v->fetch(PDO::FETCH_BOTH)) {
                        $codigo_produccion_[] = $listado_codigo_produccion['id_'];
                    }

                    // CANTIDAD DE TIQUETES VENDIDOS CON CODIGO '05'
                    $tiquete_vendido = 0; $total_vendido = 0;
                    for ($Xh = 0; $Xh < count($codigo_produccion_); $Xh++) {
                        $id_prod_Xh = $codigo_produccion_[$Xh];
                        $query_vendidos_04    = "SELECT sum(cantidad) as tiquete_vendido FROM produccion_asignado WHERE codigo_produccion = {$id_prod_Xh} AND codigo_estatus = '05'";
                        $consulta_vendidos_04 = $dblink->query($query_vendidos_04);
                        while ($listado_vendidos_04 = $consulta_vendidos_04->fetch(PDO::FETCH_BOTH)) {
                            $tiquete_vendido  = $listado_vendidos_04['tiquete_vendido'];
                            $total_vendido    = $total_vendido + $tiquete_vendido;
                        }
                    }

                    for ($Xxh = 0; $Xxh < count($codigo_produccion_); $Xxh++) {
                        $id_prod_Xxh = $codigo_produccion_[$Xxh];
                        $query_vendidos_05    = "SELECT sum(cantidad) as tiquete_vendido FROM produccion_asignado WHERE codigo_produccion = {$id_prod_Xxh} AND codigo_estatus = '04' AND tiquete_cola > 0";
                        $consulta_vendidos_05 = $dblink->query($query_vendidos_05);
                        while ($listado_vendidos_05 = $consulta_vendidos_05->fetch(PDO::FETCH_BOTH)) {
                            $tiquete_vendido = $listado_vendidos_05['tiquete_vendido'];
                            $total_vendido   = $total_vendido + $tiquete_vendido;
                        }
                    }

                    // Detectar el precio del tiquete
                    switch ((string)$precio_publico) {
                        case '0.20':
                            $resumen_pasajes_020[] = $total_vendido;
                            break;
                        case '0.25':
                            $resumen_pasajes_025[] = $total_vendido;
                            break;
                        case '0.35':
                            $resumen_pasajes_035[] = $total_vendido;
                            break;
                        default:
                            break;
                    }

                    // OBTENER EL INGRESO DE LA RUTA y CANTIDAD DE VUELTAS.
                    $query_ingreso    = "SELECT sum(total_ingreso) as total_ingreso, count(id_) as cantidad_buses FROM produccion WHERE codigo_estatus = '02' AND fecha = '$fecha' AND codigo_ruta = '$codigo_ruta' AND codigo_tiquete_color = '$codigo_tiquete_color'";
                    $consulta_ingreso = $dblink->query($query_ingreso);
                    $total_ingreso    = 0;
                    $cantidad_buses   = 0;
                    
                    if ($consulta_ingreso->rowCount() != 0) {
                        while ($listado_ingreso = $consulta_ingreso->fetch(PDO::FETCH_BOTH)) {
                            $total_ingreso  = $listado_ingreso['total_ingreso'] ?? 0;
                            $cantidad_buses = $listado_ingreso['cantidad_buses'] ?? 0;
                        }
                    }

                    // SUMAS
                    $total_por_ruta = $total_por_ruta + $total_ingreso;
                    $total_unidades = $total_unidades + $cantidad_buses;

                    // A Pantalla
                    $pdf->SetX(10);
                    $fila++;

                    if ($fila == 1) {
                        $pdf->FancyTable($header);
                        $ingresos = number_format((float)$total_ingreso, 2, '.', ',');

                        $pdf->Cell($w[0], $h[0], cstr($descripcion_ruta), 0, 0, 'L', $fill);
                        $pdf->Cell($w[1], $h[0], cstr($total_vendido, '0'), 0, 0, 'C', $fill);
                        $pdf->Cell($w[2], $h[0], cstr($precio_publico), 0, 0, 'C', $fill);
                        $pdf->Cell($w[3], $h[0], cstr($total_ingreso, '0'), 0, 0, 'R', $fill);
                        $pdf->Cell($w[4], $h[0], cstr($cantidad_buses, '0'), 0, 0, 'C', $fill);
                    } else {
                        $pdf->Cell($w[0], $h[0], '', 0, 0, 'C', $fill);
                        $pdf->Cell($w[1], $h[0], cstr($total_vendido, '0'), 0, 0, 'C', $fill);
                        $pdf->Cell($w[2], $h[0], cstr($precio_publico), 0, 0, 'C', $fill);
                        $pdf->Cell($w[3], $h[0], number_format((float)($total_ingreso ?? 0), 2, '.', ','), 0, 0, 'R', $fill);
                        $pdf->Cell($w[4], $h[0], cstr($cantidad_buses, '0'), 0, 0, 'C', $fill);
                    }
                    $pdf->ln();

                } // IF QUE CONDICIONA SI LA RUTA SON LOS COBRADORES.
            }
        } // WHILE DE CATALOGO TIQUETE COLOR

        // Imprimir subtotales por ruta.
        if ($total_por_ruta > 0) {
            $pdf->SetX(10);
            $pdf->Cell($w[0], $h[0], '', 0, 0, 'L', $fill);
            $pdf->SetFont('Arial', 'B', 9);
                $pdf->Cell($w[1], $h[0], 'Total Ruta: ' . cstr($descripcion_ruta), 0, 0, 'R', $fill);
            $pdf->SetFont('Arial', '', 11);
            $pdf->Cell($w[2], $h[0], '', 0, 0, 'C', $fill);
            $pdf->Cell($w[3], $h[0], '$ '.number_format((float)($total_por_ruta ?? 0), 2, '.', ','), 'TB', 0, 'R', $fill);
            $pdf->Cell($w[4], $h[0], '', 0, 1, 'C', $fill);
            $pdf->ln();
            // TOTALES GENERALES
            $total_general_ingresos  = $total_general_ingresos + $total_por_ruta;
            $total_tiquetes_vendidos = $total_tiquetes_vendidos + ($total_vendido ?? 0);
        }
    } // fin del while principal.

////////////////////////////////////////////////////
/// SEGUNDA PAGINA.
////////////////////////////////////////////////////
    $pdf->AddPage();
    $pdf->SetXY(10, 50);

// sumar valores de la matriz.
    $resumen_pasajes[] = array_sum($resumen_pasajes_020);
    $resumen_pasajes[] = array_sum($resumen_pasajes_025);
    $resumen_pasajes[] = array_sum($resumen_pasajes_035);

    $resumen_ingresos[] = $resumen_pasajes[0] * $resumen[0];
    $resumen_ingresos[] = $resumen_pasajes[1] * $resumen[1];
    $resumen_ingresos[] = $resumen_pasajes[2] * $resumen[2];

    // A pantalla - TOTAL AL DIA.
    $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell($w[0], $h[1], '', 1, 0, 'L', $fill);
        $pdf->Cell($w[1], $h[1], 'Pasajes', 1, 0, 'C', $fill);
        $pdf->Cell($w[3], $h[1], 'Ingresos', 1, 0, 'C', $fill);
        $pdf->Cell($w[4], $h[1], 'Total de Controles', 1, 1, 'C', $fill);
    $pdf->SetFont('Arial', '', 9);
    $pdf->SetX(10);
    $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell($w[0], $h[1], 'TOTAL DEL DIA', 0, 0, 'L', $fill);
        $pdf->Cell($w[1], $h[1], cstr(array_sum($resumen_pasajes)), 0, 0, 'C', $fill);
        $pdf->Cell($w[3], $h[1], '$ '.number_format((float)$total_general_ingresos, 2, '.', ','), 1, 0, 'R', $fill);
        $pdf->Cell($w[4], $h[1], cstr($total_unidades, '0'), 0, 1, 'C', $fill);
    $pdf->SetFont('Arial', '', 9);

    // RESUMEN
    $pdf->ln(); $pdf->ln();
    $pdf->SetX(10);
    $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell($w[0], $h[1], 'Resumen:', 0, 1, 'L', $fill);
    $pdf->SetFont('Arial', '', 9);

    for ($Xh = 0; $Xh < count($resumen); $Xh++) {
        $pdf->Cell($w[0], $h[1], 'Total pasajes $' . number_format($resumen[$Xh], 2), 0, 0, 'L', $fill);
        $pdf->Cell($w[1], $h[1], cstr($resumen_pasajes[$Xh], '0'), 0, 0, 'R', $fill);
        $pdf->Cell($w[2], $h[1], '$' . number_format($resumen[$Xh], 2), 0, 0, 'R', $fill);
        $pdf->Cell($w[3], $h[1], '$ '.number_format((float)($resumen_ingresos[$Xh] ?? 0), 2, '.', ','), 0, 1, 'R', $fill);
    }

    // TOTALES DEL RESUMEN
    $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell($w[0], $h[1], '', 0, 0, 'L', $fill);
        $pdf->Cell($w[1], $h[1], cstr(array_sum($resumen_pasajes)), 'TB', 0, 'R', $fill);
        $pdf->Cell($w[2], $h[1], '', 0, 0, 'L', $fill);
        $pdf->Cell($w[3], $h[1], '$ '.number_format((float)array_sum($resumen_ingresos), 2, '.', ','), 'TB', 1, 'R', $fill);
    $pdf->SetFont('Arial', '', 9);

    // DIFERENCIAS
    $pdf->ln(); $pdf->ln();
    $pdf->SetX(10);
    $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell($w[0], $h[1], 'Diferencias:', 0, 0, 'L', $fill);
    $pdf->SetFont('Arial', '', 9);
    $pdf->ln();

    $query_c = "SELECT * FROM produccion_diferencias
    WHERE fecha = '$fecha'
        ORDER BY id_";
    $consulta = $dblink->query($query_c);

    if ($consulta->rowCount() != 0) {
        while ($listado = $consulta->fetch(PDO::FETCH_BOTH)) {
            $id_    = trim($listado['id_']);
            $nombre = trim($listado['descripcion']);
            $valor  = trim($listado['valor']);
            $concepto = trim($listado['concepto']);

            $pdf->Cell($w[5], $h[0], cstr($nombre), 0, 0, 'L', $fill);
            $pdf->Cell($w[6], $h[0], '$ ' . cstr($valor, '0'), 0, 0, 'L', $fill);
            $pdf->Cell($w[7], $h[0], cstr($concepto), 0, 1, 'L', $fill);
        }
    }

    // REVISADO POR
    $pdf->ln(); $pdf->ln(); $pdf->ln(); $pdf->ln();
    $pdf->SetX(10);
        $pdf->Cell($w[0], $h[1], 'Revisado por:', 0, 0, 'L', $fill);
    $pdf->ln(); $pdf->ln();
    $pdf->SetX(10);
        $pdf->Cell($w[0], $h[1], '_______________________________________', 0, 0, 'L', $fill);

// Salida del pdf.
    $modo         = 'I'; // Envia al navegador (I), Descarga el archivo (D), Guardar el fichero en un local (F).
    $print_nombre = "Ingreso diario" . $fecha_ . '.pdf';
    $pdf->Output($print_nombre, $modo);
?>