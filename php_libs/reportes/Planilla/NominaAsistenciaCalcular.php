<?php
/*
TABLA: catalogo_departamento_empresa
    codigo	descripcion
    01	Oficina
    02	Motorista
    03	Revisador
    04	Aseo/Otros
    05	Taller
    06	Microbuseros
    07	Accionista
    08	Vigilancia
    09	Mantenimiento
*/
//
// Establecer formato para la fecha.
//
date_default_timezone_set('America/El_Salvador');
setlocale(LC_TIME,'es_SV');
//	Hora Actual.
$hora_actual = date("h:i:s a");
$hoy = getdate();
$mes = $hoy["mon"];     // mes
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);
// Archivos que se incluyen.
    include($path_root."/acomtus/includes/mainFunctions_conexion.php");
// Llamar a la libreria fpdf
    include($path_root."/acomtus/php_libs/fpdf/fpdf.php");
// cambiar a utf-8.
    header("Content-Type: text/html; charset=UTF-8");
// variables y consulta a la tabla.
    $fecha_mes = $_REQUEST["fechaMes"];
    $fecha_ann = $_REQUEST["fechaAnn"];
    $quincena = $_REQUEST["quincena"];
    $NombreRuta = $_REQUEST["ruta"];
    $RutaText = $_REQUEST["RutaText"];
    $calcular = $_REQUEST["chkCalcular"]; // Valor para si se realiza el cálculo (enviado desde JS)
    $DepartamentoEmpresa = $_REQUEST["DepartamentoEmpresa"]; // Valor para el departamento/empresa
    $departamentoEmpresaTexto = $_REQUEST["DepartamentoText"]; // Texto para el departamento/empresa

// Validar que las variables de fecha y quincena estén presentes
if (!isset($fecha_mes) || !isset($fecha_ann) || !isset($quincena)) {
    die("Faltan parámetros de fecha o quincena.");
}

// Determinar el rango de fechas para la quincena
$fecha_periodo_inicio = '';
$fecha_periodo_fin = '';

if ($quincena == 'Q1') {
    $fecha_periodo_inicio = $fecha_ann . '-' . $fecha_mes . '-01';
    $fecha_periodo_fin = $fecha_ann . '-' . $fecha_mes . '-15';
} elseif ($quincena == 'Q2') {
    $fecha_periodo_inicio = $fecha_ann . '-' . $fecha_mes . '-16';
    // Para el fin de mes, calculamos el último día del mes
    $fecha_periodo_fin = date('Y-m-t', strtotime($fecha_ann . '-' . $fecha_mes . '-01'));
} else {
    die("Valor de quincena inválido.");
}

// Generar el rango de fechas para iteración
$period = new DatePeriod(
    new DateTime($fecha_periodo_inicio),
    new DateInterval('P1D'),
    new DateTime(date('Y-m-d', strtotime($fecha_periodo_fin . ' +1 day'))) // +1 day para incluir la fecha fin
);

$rango_fechas = [];
foreach ($period as $date) {
    $rango_fechas[] = $date->format('Y-m-d');
}

// ARRAY PARA ALMACENAR LOS DATOS DE ASISTENCIA PRE-CARGADOS
$asistencia_por_empleado_y_fecha = [];

// Query principal para obtener los empleados y sus datos relevantes (incluyendo cargo y ruta)
$query = "SELECT p.codigo, p.nombres, p.apellidos, p.salario, p.codigo_ruta, p.codigo_departamento_empresa, cc.descripcion as cargo_descripcion ";
$query .= "FROM personal p ";
$query .= "LEFT JOIN catalogo_cargo cc ON cc.codigo = p.codigo_cargo "; // ASUMIMOS catalogo_cargos Y p.codigo_cargo
$query .= "WHERE 1=1";
if ($NombreRuta != '00') {
    $query .= " AND p.codigo_ruta = '$NombreRuta'";
}
if ($DepartamentoEmpresa != '00') {
    $query .= " AND p.codigo_departamento_empresa = '$DepartamentoEmpresa'";
}
$query .= " ORDER BY p.codigo_departamento_empresa, p.apellidos, p.nombres";

try {
    $stmt_codigos_personal = $dblink->query($query);
} catch (PDOException $e) {
    error_log("ERROR PDOException al consultar códigos de personal: " . $e->getMessage());
    die("Error interno del servidor al obtener datos de personal. Revise los logs del servidor.");
}

if ($stmt_codigos_personal === false) {
    $error_info = $dblink->errorInfo();
    error_log("ERROR: La consulta SQL para códigos de personal falló y regresó FALSE. SQLSTATE: " . $error_info[0] . ", Código: " . $error_info[1] . ", Mensaje: " . $error_info[2]);
    die("Error crítico al obtener la lista de personal. Contacte a soporte.");
}

$codigos_personal_a_consultar = [];
$datos_empleado_principal = []; // Asegúrate de inicializar este array
while ($row_codigo = $stmt_codigos_personal->fetch(PDO::FETCH_ASSOC)) {
    $codigos_personal_a_consultar[] = $row_codigo['codigo'];
    // Guardar también los datos del empleado para no volver a consultarlos
    $datos_empleado_principal[$row_codigo['codigo']] = $row_codigo;
}

if (empty($codigos_personal_a_consultar)) {
    die("No se encontraron empleados para los criterios seleccionados.");
}

$codigos_personal_str = "'" . implode("','", $codigos_personal_a_consultar) . "'";

// 2. Consulta única para obtener TODOS los datos de asistencia relevantes
try {
        $stmt_all_asistencia = $dblink->prepare("
        SELECT pa.codigo_personal, pa.fecha, pa.hora_extra, pa.observacion,
                pa.codigo_jornada, cat_j.descripcion as descripcion_jornada, cat_j.horas as horas_jornada_regular,
                pa.codigo_tipo_licencia, cat_lp.descripcion as descripcion_licencia, cat_lp.horas as horas_licencia,
                pa.codigo_jornada_descanso, cat_jd.descripcion as descripcion_descanso,
                pa.codigo_jornada_vacaciones, cat_jv.descripcion as descripcion_vacacion,
                pa.codigo_jornada_nocturna, cat_jn.descripcion as descripcion_nocturna,
                pa.codigo_jornada_e_4h, cat_j4.descripcion as descripcion_e_4h,
                pa.codigo_jornada_asueto, cat_ja.descripcion as descripcion_asueto
        FROM personal_asistencia pa
        LEFT JOIN catalogo_jornada cat_j ON cat_j.id_ = pa.codigo_jornada
        LEFT JOIN catalogo_jornada cat_jd ON cat_jd.id_ = pa.codigo_jornada_descanso
        LEFT JOIN catalogo_jornada cat_jv ON cat_jv.id_ = pa.codigo_jornada_vacaciones
        LEFT JOIN catalogo_jornada cat_j4 ON cat_j4.id_ = pa.codigo_jornada_e_4h
        LEFT JOIN catalogo_jornada cat_jn ON cat_jn.id_ = pa.codigo_jornada_nocturna
        LEFT JOIN catalogo_jornada cat_ja ON cat_ja.id_ = pa.codigo_jornada_asueto
        LEFT JOIN catalogo_tipo_licencia_o_permiso cat_lp ON cat_lp.id_ = pa.codigo_tipo_licencia
        WHERE pa.codigo_personal IN ($codigos_personal_str)
        AND pa.fecha BETWEEN :fecha_inicio AND :fecha_fin
        ORDER BY pa.fecha
        ");
        $stmt_all_asistencia->bindParam(':fecha_inicio', $fecha_periodo_inicio);
        $stmt_all_asistencia->bindParam(':fecha_fin', $fecha_periodo_fin);
        $stmt_all_asistencia->execute();
    
        // 3. Organizar los datos de asistencia en el array asociativo
        while ($row_asistencia = $stmt_all_asistencia->fetch(PDO::FETCH_ASSOC)) {
            $codigo_p = $row_asistencia['codigo_personal'];
            $fecha_a = $row_asistencia['fecha'];
            $asistencia_por_empleado_y_fecha[$codigo_p][$fecha_a] = $row_asistencia;
        }
    } catch (PDOException $e) {
        error_log("Error al pre-cargar datos de asistencia detallados: " . $e->getMessage());
        die("Error al obtener datos de asistencia detallados. Por favor, intente más tarde.");
    }

// Preparar datos para las consultas iniciales (esto probablemente ya lo tenías)
$NombresCodigoDE = [];
$consultaDE = $dblink->query("SELECT codigo, descripcion FROM catalogo_departamento_empresa");
while ($row = $consultaDE->fetch(PDO::FETCH_ASSOC)) {
    $NombresCodigoDE[$row['descripcion']] = $row['codigo'];
}

$NombresCodigoLicenciaPermiso = [];
$consultaLic = $dblink->query("SELECT id_, descripcion, horas FROM catalogo_tipo_licencia_o_permiso"); // Asegúrate de obtener las horas
while ($row = $consultaLic->fetch(PDO::FETCH_ASSOC)) {
    $NombresCodigoLicenciaPermiso[$row['id_']] = ['descripcion' => $row['descripcion'], 'horas' => $row['horas']]; // Usar id_ como clave
}

$FechaDescripcionAsueto = [];
$consultaAsueto = $dblink->query("SELECT fecha, descripcion FROM asuetos");
while ($row = $consultaAsueto->fetch(PDO::FETCH_ASSOC)) {
    $FechaDescripcionAsueto[$row['fecha']] = $row['descripcion'];
}

// --- NUEVA FUNCIÓN PARA PROCESAR DATOS DE ASISTENCIA Y CALCULAR TOTALES ---
// Esta función NO imprime, solo calcula y retorna.
function processEmployeeAttendanceData($rango_fechas, $codigo_personal, $salario_mensual, $jornada_base_default, $asistencia_data_precargada, $NombresCodigoLicenciaPermiso) {
    $total_salario_devengado_empleado = 0;
    $total_monto_horas_extra_empleado = 0;
    $total_descuentos_empleado = 0;
    $total_otras_deducciones_empleado = 0; // Para futuras deducciones no calculadas aquí
    $total_dias_asuetos_empleado = 0;
    $total_dias_licencias_empleado = 0; // Contar días de licencia
    $total_horas_trabajadas_regulares_empleado = 0;
    
    $salario_diario = $salario_mensual / 30; // Salario diario basado en 30 días

    $daily_rows_formatted = []; // Para almacenar las filas diarias ya formateadas

    foreach ($rango_fechas as $fecha_actual) {
        $row_asistencia = $asistencia_data_precargada[$codigo_personal][$fecha_actual] ?? null;

        $horas_extra_registradas = 0;
        $observacion = '';
        $salario_dia_actual = 0;
        $monto_horas_extra_dia_actual = 0;
        $descuento_dia_actual = 0;
        $horas_jornada_para_este_dia = $jornada_base_default; // Horas por defecto
        
        // Inicializar display values para asegurar exclusividad
        $jornada_display_value = '';
        $descanso_display_value = '';
        $vacaciones_display_value = '';
        $nocturna_display_value = '';
        $e_4h_display_value = '';
        $asueto_display_value = '';
        $licencia_display_value = '';

        $es_dia_pagado = false; // Flag para determinar si el día se paga para cálculos de salario y HE
        
        if ($row_asistencia) {
            $horas_extra_registradas = (float)($row_asistencia['hora_extra'] ?? 0);
            $observacion = utf8_decode($row_asistencia['observacion'] ?? '');

            // Lógica para el pago del día y determinar el tipo de asistencia principal (mutuamente excluyente)
            // Prioridad: Asueto > Licencia (código jornada 4) > Jornada Regular > Descanso > Vacaciones > Nocturna > E_4H > Otras Licencias

            // 1. Asueto
            if (isset($row_asistencia['codigo_jornada_asueto']) && !empty($row_asistencia['codigo_jornada_asueto'])) {
                $asueto_display_value = 'A'; // Carácter para Asueto
                $es_dia_pagado = true;
                $total_dias_asuetos_empleado++;
                $salario_dia_actual = $salario_diario;
                $horas_jornada_para_este_dia = (float)($row_asistencia['horas_jornada_regular'] ?? $jornada_base_default);
            } 
            // 2. Licencia (con codigo_jornada = 4 y tipo_licencia = 13 para el punto '.')
            else if (isset($row_asistencia['codigo_jornada']) && $row_asistencia['codigo_jornada'] == '4') {
                $jornada_display_value = '.'; // Carácter de punto para este tipo de licencia
                $es_dia_pagado = true;
                $total_dias_licencias_empleado++; // Se cuenta como día de licencia
                $licencia_info = $NombresCodigoLicenciaPermiso[$row_asistencia['codigo_tipo_licencia']] ?? ['horas' => 0, 'descripcion' => 'N/A'];
                $horas_licencia_dia = (float)($licencia_info['horas'] ?? 0);
                
                $valor_hora_normal = ($jornada_base_default > 0) ? ($salario_diario / $jornada_base_default) : 0;
                $salario_dia_actual = $valor_hora_normal * $horas_licencia_dia;
                $horas_jornada_para_este_dia = $horas_licencia_dia;
            }
            // 3. Jornada Regular (si no es el código 4 de licencia)
            else if (isset($row_asistencia['codigo_jornada']) && !empty($row_asistencia['codigo_jornada'])) {
                $jornada_display_value = str_replace(' ', '.', trim($row_asistencia['descripcion_jornada'] ?? ''));
                $es_dia_pagado = true;
                $salario_dia_actual = $salario_diario;
                $horas_jornada_para_este_dia = (float)($row_asistencia['horas_jornada_regular'] ?? $jornada_base_default);
            }
            // 4. Descanso
            else if (isset($row_asistencia['codigo_jornada_descanso']) && !empty($row_asistencia['codigo_jornada_descanso'])) {
                $descanso_display_value = 'Ds'; // Carácter para Descanso
                $es_dia_pagado = true;
                $salario_dia_actual = $salario_diario;
            }
            // 5. Vacaciones
            else if (isset($row_asistencia['codigo_jornada_vacaciones']) && !empty($row_asistencia['codigo_jornada_vacaciones'])) {
                $vacaciones_display_value = 'V'; // Carácter para Vacaciones
                $es_dia_pagado = true;
                $salario_dia_actual = $salario_diario;
            }
            // 6. Nocturna
            else if (isset($row_asistencia['codigo_jornada_nocturna']) && !empty($row_asistencia['codigo_jornada_nocturna'])) {
                $nocturna_display_value = 'N'; // Carácter para Nocturna
                $es_dia_pagado = true;
                $salario_dia_actual = $salario_diario;
            }
            // 7. E_4H
            else if (isset($row_asistencia['codigo_jornada_e_4h']) && !empty($row_asistencia['codigo_jornada_e_4h'])) {
                $e_4h_display_value = '4H'; // Carácter para E_4H
                $es_dia_pagado = true;
                $salario_dia_actual = $salario_diario;
            }
            // 8. Otras Licencias (si no fue manejada por codigo_jornada=4)
            else if (isset($row_asistencia['codigo_tipo_licencia']) && !empty($row_asistencia['codigo_tipo_licencia'])) {
                $licencia_info = $NombresCodigoLicenciaPermiso[$row_asistencia['codigo_tipo_licencia']] ?? ['horas' => 0, 'descripcion' => 'N/A'];
                $horas_licencia_dia = (float)($licencia_info['horas'] ?? 0);
                
                if ($horas_licencia_dia > 0) {
                    $es_dia_pagado = true;
                    $total_dias_licencias_empleado++;
                    $valor_hora_normal = ($jornada_base_default > 0) ? ($salario_diario / $jornada_base_default) : 0;
                    $salario_dia_actual = $valor_hora_normal * $horas_licencia_dia;
                    $horas_jornada_para_este_dia = $horas_licencia_dia;
                }
                $licencia_display_value = 'L'; // Carácter para otras licencias
            }

            // Calcular monto de horas extras (solo si el día se considera pagado)
            if ($es_dia_pagado) {
                $valor_hora_normal_base = ($jornada_base_default > 0) ? ($salario_diario / $jornada_base_default) : 0;
                $monto_horas_extra_dia_actual = $horas_extra_registradas * ($valor_hora_normal_base * 2); // Ejemplo: Doble pago
            }
        } // Cierre if ($row_asistencia)
        
        // Acumulación de totales de salario devengado, horas extra, y descuentos
        $total_salario_devengado_empleado += $salario_dia_actual;
        $total_monto_horas_extra_empleado += $monto_horas_extra_dia_actual;
        $total_descuentos_empleado += $descuento_dia_actual; // (Si tienes lógica para descuento_dia_actual)

        // Acumular horas regulares, excluyendo días de asueto y licencia completa
        if ($es_dia_pagado && empty($asueto_display_value) && empty($licencia_display_value) && ($jornada_display_value != '.')) {
             $total_horas_trabajadas_regulares_empleado += $horas_jornada_para_este_dia;
        }

        // Preparar la fila diaria formateada con los valores de display específicos
        $daily_rows_formatted[] = [
            'fecha' => date('d/m/Y', strtotime($fecha_actual)),
            'jornada' => $jornada_display_value,
            'descanso' => $descanso_display_value,
            'vacaciones' => $vacaciones_display_value,
            'nocturna' => $nocturna_display_value,
            'e_4h' => $e_4h_display_value,
            'asueto' => $asueto_display_value,
            'licencia' => $licencia_display_value,
            'horas_extra' => number_format($horas_extra_registradas, 2),
            'salario_dia' => number_format($salario_dia_actual, 2),
            'descuento_dia' => number_format($descuento_dia_actual, 2),
            'observacion' => $observacion,
        ];
    } // Cierre del foreach ($rango_fechas as $fecha_actual)

    // Cálculo del salario líquido
    $salario_liquido_empleado = $total_salario_devengado_empleado + $total_monto_horas_extra_empleado - $total_descuentos_empleado - $total_otras_deducciones_empleado;

    return [
        'totals' => [
            'total_salario_devengado' => $total_salario_devengado_empleado,
            'total_dias_asuetos' => $total_dias_asuetos_empleado,
            'total_dias_licencias' => $total_dias_licencias_empleado,
            'total_monto_horas_extra' => $total_monto_horas_extra_empleado,
            'total_descuentos' => $total_descuentos_empleado,
            'salario_liquido' => $salario_liquido_empleado,
            'total_horas_regulares' => $total_horas_trabajadas_regulares_empleado,
        ],
        'daily_rows' => $daily_rows_formatted,
    ];
}

// --- FIN DE LA FUNCIÓN processEmployeeAttendanceData ---


// INICIO DE LA CLASE PDF (deberías tener una estructura similar)
class PDF extends FPDF
{
    // ... tu código de la clase PDF (Header, Footer, etc.) ...
    function Header() {
        global $fecha_periodo_inicio, $fecha_periodo_fin, $departamentoEmpresaTexto, $RutaText, $quincena;
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 5, utf8_decode('Reporte de Planilla - '. $departamentoEmpresaTexto . ' (' . $RutaText . ') - Quincena: ' . $quincena), 0, 1, 'C');
        $this->Cell(0, 5, utf8_decode('Período: ' . date('d/m/Y', strtotime($fecha_periodo_inicio)) . ' al ' . date('d/m/Y', strtotime($fecha_periodo_fin))), 0, 1, 'C');
        $this->Ln(5);
    }
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ').$this->PageNo().'/{nb}', 0, 0, 'C');
    }
}

// Creación del objeto PDF
$pdf = new PDF('L', 'mm', 'Letter'); // 'L' para horizontal
$pdf->SetMargins(10, 10, 10);
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',9);

// Aseguramos el orden de los empleados según la consulta inicial
$sorted_datos_empleado_principal = [];
foreach ($codigos_personal_a_consultar as $codigo) {
    if (isset($datos_empleado_principal[$codigo])) {
        $sorted_datos_empleado_principal[$codigo] = $datos_empleado_principal[$codigo];
    }
}
$datos_empleado_principal = $sorted_datos_empleado_principal; // Reemplazar con el array ordenado


// Bucle principal para rellenar el PDF con los datos de cada empleado
$i=1; // Número correlativo de empleado
$jornada_base_default = 8; // Define un valor de jornada por defecto (ej. 8 horas)

// Anchos para la LÍNEA PRINCIPAL DEL EMPLEADO (incluyendo cálculos)
$w_main_employee_row = [
    7,  // No.
    15, // CODIGO
    50, // NOMBRE
    30, // CARGO
    20, // RUTA
    22, // SALARIO
    18, // TOTAL ASUETOS (días)
    18, // TOTAL LICENCIAS (días)
    22, // TOTAL HORAS EXTRAS (monto)
    22, // TOTAL DESCUENTO (monto)
    26  // SALARIO LIQUIDO
];

// Anchos para la TABLA DE DETALLES DIARIOS (12 columnas)
$w_daily_details = [
    18, // Fecha
    20, // Jornada
    18, // Descanso
    18, // Vacaciones
    18, // Nocturna
    18, // E_4H
    18, // Asueto
    18, // Licencia
    18, // Horas Extra (cantidad)
    18, // Salario (del día)
    18, // Descuento (del día)
    35  // Observacion
];


// --- INICIO DEL BUCLE PRINCIPAL QUE ITERA A TRAVÉS DE CADA EMPLEADO ---
foreach ($datos_empleado_principal as $row_empleado) {
    $codigo_personal = TRIM($row_empleado['codigo']);
    $nombres_empleado = TRIM($row_empleado['nombres']);
    $apellidos_empleado = TRIM($row_empleado['apellidos']);
    $salario_mensual = (float)$row_empleado['salario'];
    $cargo_empleado = utf8_decode($row_empleado['cargo_descripcion'] ?? 'N/A');
    $ruta_empleado = utf8_decode($row_empleado['codigo_ruta'] ?? 'N/A');

    $nombre_completo = trim(utf8_decode($nombres_empleado . ' ' . $apellidos_empleado));

    // 1. PROCESAR DATOS Y OBTENER TOTALES PARA EL EMPLEADO ACTUAL
    $employee_data = processEmployeeAttendanceData(
        $rango_fechas,
        $codigo_personal,
        $salario_mensual,
        $jornada_base_default,
        $asistencia_por_empleado_y_fecha,
        $NombresCodigoLicenciaPermiso
    );

    $totals = $employee_data['totals'];
    $daily_rows_to_print = $employee_data['daily_rows'];

    // 2. IMPRIMIR LA LÍNEA PRINCIPAL DEL EMPLEADO CON CÁLCULOS INTEGRADOS
    $pdf->SetFont('Arial', 'B', 8); // Negrita para la línea principal
    $pdf->SetFillColor(234, 236, 238); // Color de fondo claro para la línea principal
    $pdf->SetDrawColor(0,0,0); // Borde negro

    $pdf->SetX(10); // Reiniciar posición X

    // COLUMNAS FIJAS INICIALES
    $pdf->Cell($w_main_employee_row[0], 7, $i, 1, 0, 'C', true); // No.
    $pdf->Cell($w_main_employee_row[1], 7, $codigo_personal, 1, 0, 'L', true); // CÓDIGO
    $pdf->Cell($w_main_employee_row[2], 7, $nombre_completo, 1, 0, 'L', true); // NOMBRE
    $pdf->Cell($w_main_employee_row[3], 7, $cargo_empleado, 1, 0, 'L', true); // CARGO
    $pdf->Cell($w_main_employee_row[4], 7, $ruta_empleado, 1, 0, 'L', true); // RUTA

    // COLUMNAS DE CÁLCULOS EN LA MISMA LÍNEA
    $pdf->Cell($w_main_employee_row[5], 7, number_format($totals['total_salario_devengado'], 2, '.', ','), 1, 0, 'R', true); // SALARIO
    $pdf->Cell($w_main_employee_row[6], 7, number_format($totals['total_dias_asuetos'], 0, '.', ','), 1, 0, 'C', true); // TOTAL ASUETOS (días)
    $pdf->Cell($w_main_employee_row[7], 7, number_format($totals['total_dias_licencias'], 0, '.', ','), 1, 0, 'C', true); // TOTAL LICENCIAS (días)
    $pdf->Cell($w_main_employee_row[8], 7, number_format($totals['total_monto_horas_extra'], 2, '.', ','), 1, 0, 'R', true); // TOTAL HORAS EXTRAS (monto)
    $pdf->Cell($w_main_employee_row[9], 7, number_format($totals['total_descuentos'], 2, '.', ','), 1, 0, 'R', true); // TOTAL DESCUENTO (monto)
    $pdf->Cell($w_main_employee_row[10], 7, number_format($totals['salario_liquido'], 2, '.', ','), 1, 1, 'R', true); // SALARIO LIQUIDO (1,1 para nueva línea)

    // 3. IMPRIMIR LOS ENCABEZADOS DE LA TABLA DE DETALLES DIARIOS
    $pdf->SetFont('Arial', 'B', 7); // Fuente más pequeña para encabezados diarios
    $pdf->SetFillColor(200, 220, 255); // Color de fondo para encabezados
    $pdf->SetX(10); // Alinea con la primera columna de datos diarios
    $pdf->Cell($w_daily_details[0], 6, 'Fecha', 1, 0, 'C', true);
    // REMOVED: Entrada, Salida
    $pdf->Cell($w_daily_details[1], 6, 'Jornada', 1, 0, 'C', true);
    $pdf->Cell($w_daily_details[2], 6, 'Descanso', 1, 0, 'C', true);
    $pdf->Cell($w_daily_details[3], 6, 'Vacaciones', 1, 0, 'C', true);
    $pdf->Cell($w_daily_details[4], 6, 'Nocturna', 1, 0, 'C', true);
    $pdf->Cell($w_daily_details[5], 6, 'E_4H', 1, 0, 'C', true);
    $pdf->Cell($w_daily_details[6], 6, 'Asueto', 1, 0, 'C', true);
    $pdf->Cell($w_daily_details[7], 6, 'Licencia', 1, 0, 'C', true);
    $pdf->Cell($w_daily_details[8], 6, 'Hrs Extra', 1, 0, 'C', true);
    $pdf->Cell($w_daily_details[9], 6, 'Salario Dia', 1, 0, 'C', true);
    $pdf->Cell($w_daily_details[10], 6, 'Descuento', 1, 0, 'C', true);
    $pdf->Cell($w_daily_details[11], 6, utf8_decode('Observación'), 1, 1, 'C', true);

    // 4. IMPRIMIR LAS FILAS DE DETALLES DIARIOS
    $pdf->SetFont('Arial', '', 7); // Fuente para los datos diarios
    foreach ($daily_rows_to_print as $daily_row) {
        $pdf->SetX(10);
        $pdf->Cell($w_daily_details[0], 5, $daily_row['fecha'], 1, 0, 'C');
        // REMOVED: Entrada, Salida
        $pdf->Cell($w_daily_details[1], 5, $daily_row['jornada'], 1, 0, 'C');
        $pdf->Cell($w_daily_details[2], 5, $daily_row['descanso'], 1, 0, 'C');
        $pdf->Cell($w_daily_details[3], 5, $daily_row['vacaciones'], 1, 0, 'C');
        $pdf->Cell($w_daily_details[4], 5, $daily_row['nocturna'], 1, 0, 'C');
        $pdf->Cell($w_daily_details[5], 5, $daily_row['e_4h'], 1, 0, 'C');
        $pdf->Cell($w_daily_details[6], 5, $daily_row['asueto'], 1, 0, 'C');
        $pdf->Cell($w_daily_details[7], 5, $daily_row['licencia'], 1, 0, 'C');
        $pdf->Cell($w_daily_details[8], 5, $daily_row['horas_extra'], 1, 0, 'C');
        $pdf->Cell($w_daily_details[9], 5, $daily_row['salario_dia'], 1, 0, 'R');
        $pdf->Cell($w_daily_details[10], 5, $daily_row['descuento_dia'], 1, 0, 'R');
        $pdf->Cell($w_daily_details[11], 5, $daily_row['observacion'], 1, 1, 'L');
    }

    // SALTO DE LÍNEA GRANDE ANTES DEL PRÓXIMO EMPLEADO
    $pdf->Ln(10); // Ajusta este valor para la separación deseada

    $i++; // Incrementar el contador de empleado
}
// --- FIN DEL BUCLE PRINCIPAL QUE CAMBIA DE EMPLEADO ---

// Salida del pdf.
$modo = "I"; // Envia al navegador (I), Descarga el archivo (D), Guardar el fichero en un local(F).
$print_nombre = mb_convert_encoding("Planilla: $departamentoEmpresaTexto - $quincena - $mes.pdf","ISO-8859-1");
$pdf->Output($print_nombre,$modo);

?>