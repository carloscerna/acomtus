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
    $persona_responsable = $_REQUEST["persona_responsable"] ?? 'No Definido'; // Asegurarse de que esta variable llegue

// Validar que las variables de fecha o quincena estén presentes
if (!isset($fecha_mes) || !isset($quincena)) {
    die("Faltan parámetros de fecha o quincena.");
}
if (!isset($fecha_ann) || !is_numeric($fecha_ann)) {
    // Si $fecha_ann no está definido o no es numérico, usa el año actual
    $fecha_ann = date('Y');
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

// Define la ruta base para las imágenes de jornada
$image_base_path = $_SERVER['DOCUMENT_ROOT'] . "/acomtus/img/Catalogo Jornada/"; // Usa $_SERVER['DOCUMENT_ROOT'] para ruta absoluta

// ARRAY PARA ALMACENAR LOS DATOS DE ASISTENCIA PRE-CARGADOS
$asistencia_por_empleado_y_fecha = [];

// Query principal para obtener los empleados y sus datos relevantes (incluyendo cargo y ruta)
$query = "SELECT p.codigo, p.nombres, p.apellidos, p.salario, p.codigo_ruta, p.codigo_departamento_empresa, cc.descripcion as cargo_descripcion ";
$query .= "FROM personal p ";
$query .= "LEFT JOIN catalogo_cargo cc ON cc.codigo = p.codigo_cargo "; // CORRECCIÓN: catalogo_cargo
$query .= "WHERE 1=1 AND p.codigo_estatus = '01'"; // AÑADIDA: Condición para codigo_estatus

// Condición para filtrar por ruta SOLO si el departamento es '02' (Motorista)
if ($DepartamentoEmpresa == '02' && $NombreRuta != '00') {
    $query .= " AND p.codigo_ruta = '$NombreRuta'";
}
// Condición para filtrar por departamento (siempre aplica si no es '00')
if ($DepartamentoEmpresa != '00') {
    $query .= " AND p.codigo_departamento_empresa = '$DepartamentoEmpresa'";
}
$query .= " ORDER BY p.codigo_departamento_empresa, p.apellidos, p.nombres, p.codigo"; // AÑADIDA: p.codigo al ORDER BY

try {
    $stmt_codigos_personal = $dblink->query($query);
} catch (PDOException $e) {
    die("Error interno del servidor al obtener datos de personal. Revise los logs del servidor.");
}

if ($stmt_codigos_personal === false) {
    $error_info = $dblink->errorInfo();
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
        SELECT pa.codigo_personal, pa.fecha, pa.hora_extra,
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

// NUEVA CONSULTA: Cargar el mapeo de códigos a nombres de archivo de imagen
$jornada_imagenes_map = [];
try {
    $consulta_imagenes = $dblink->query("SELECT codigo, descripcion FROM catalogo_jornada_imagenes");
    while ($row_img = $consulta_imagenes->fetch(PDO::FETCH_ASSOC)) {
        $jornada_imagenes_map[trim($row_img['codigo'])] = trim($row_img['descripcion']);
    }
} catch (PDOException $e) {
    die("Error al obtener datos de imágenes de jornada. Por favor, intente más tarde.");
}

// --- NUEVA FUNCIÓN PARA PROCESAR DATOS DE ASISTENCIA Y CALCULAR TOTALES ---
// Esta función NO imprime, solo calcula y retorna.
function processEmployeeAttendanceData($rango_fechas, $codigo_personal, $salario_mensual, $jornada_base_default, $asistencia_data_precargada, $NombresCodigoLicenciaPermiso, $jornada_imagenes_map, $FechaDescripcionAsueto, $codigo_departamento_empleado) { // Added $codigo_departamento_empleado
    $total_salario_devengado_empleado = 0; // Para jornadas normales, descanso, vacaciones, licencias
    $total_salario_asuetos = 0; // Salario específico de asuetos (solo el adicional por trabajar asueto)
    $total_monto_horas_extra_empleado = 0;
    $total_descuentos_empleado = 0; // Descuentos por faltas, etc.
    $total_otras_deducciones_empleado = 0; // Para futuras deducciones no calculadas aquí
    $total_horas_extra_cantidad = 0; // Cantidad total de horas extra
    $total_deduccion_7mo = 0; // Inicializar deducción del 7mo día
    
    // Nueva variable para acumular el valor de Trabajo Descanso
    $total_trabajo_extra_empleado = 0; // Renombrado de total_trabajo_descanso_extra_empleado
    
    // Contadores de días por tipo de asistencia (estos ya no se usarán para imprimir en columnas, pero sí para los cálculos de devengado)
    $total_dias_jornada_empleado = 0;
    $total_dias_descanso_empleado = 0;
    $total_dias_vacaciones_empleado = 0;
    $total_dias_nocturna_empleado = 0;
    $total_dias_e4h_empleado = 0;
    $total_dias_asuetos_empleado_count = 0; 
    $total_dias_licencias_empleado = 0;

    $daily_attendance_details = []; // Almacenará los códigos e imágenes para cada día

    $salario_diario = $salario_mensual / 30; // Salario diario basado en 30 días

    // Códigos que no deben sumar al salario (solo visualización)
    $non_contributory_codes_display_only = ['4144444', '4344444', '4244444']; 
    // Códigos que implican doble descuento y pueden activar 7mo día
    $double_deduction_codes = ['41044444', '4444444']; // 'C' (Castigo), 'F' (Falta)

    // Códigos de asueto que implican pago adicional por trabajar
    $asueto_worked_codes = [
        '41614444' => $salario_diario / 2, // 4 horas adicionales (medio día de salario)
        '41624444' => $salario_diario,     // 8 horas adicionales (día completo de salario)
        '41634444' => $salario_diario + ($salario_diario / 2) // 12 horas adicionales (día y medio de salario)
    ];

    // Códigos de Trabajo Descanso (TD) que implican pago adicional
    $trabajo_descanso_codes = [
        '3144444' => $salario_diario / 2, // TD 4 horas adicionales
        '41444144' => $salario_diario / 2, // TD 4 horas adicionales
        '41444244' => $salario_diario,     // TD 8 horas adicionales
        '41444344' => $salario_diario + ($salario_diario / 2) // TD 12 horas adicionales
    ];

    // Nuevos códigos de Trabajo Descanso Asueto (TDA)
    $trabajo_descanso_asueto_codes = [
        '41744444' => $salario_diario, // DA - Descanso Asueto (se paga el día normal)
        '41514444' => $salario_diario / 2, // TDA 4 horas adicionales
        '41524444' => $salario_diario,     // TDA 8 horas adicionales
        '41534444' => $salario_diario + ($salario_diario / 2) // TDA 12 horas adicionales
    ];

    // Weekly tracking for 7mo day deduction
    $weekly_tracking = []; // Key: week_start_date (Monday), Value: ['has_deductible_event' => bool, 'has_descanso' => bool, 'descanso_date' => date_string]

    foreach ($rango_fechas as $fecha_actual) {
        $row_asistencia = $asistencia_data_precargada[$codigo_personal][$fecha_actual] ?? null;

        $horas_extra_registradas = 0;
        $salario_dia_actual = 0; // Salario para este día (jornada, descanso, vacación, licencia)
        $monto_horas_extra_dia_actual = 0;
        $descuento_dia_actual = 0; // Descuento por este día (si es falta o castigo)
        $horas_jornada_para_este_dia = $jornada_base_default; // Horas por defecto
        
        $es_dia_pagado_para_hora_extra = false; // Flag para determinar si el día se paga para cálculos de HE
        $is_activity_recorded = false; // Flag: Indica si hay un registro de actividad (pagada o no)
        
        // Inicializar códigos para la concatenación
        $CodigoJornada = '';
        $CodigoLicencia = '';
        $CodigoJornadaAsueto = '';
        $CodigoJornadaVacaciones = '';
        $CodigoJornadaDescanso = '';
        $CodigoJornadaE4H = '';
        $CodigoJornadaNocturna = '';
        $CodigoJornadaTodas = ''; // Initialize for each day

        // Determine the start of the week for this date (Monday)
        $date_obj = new DateTime($fecha_actual);
        $day_of_week_num = (int)$date_obj->format('N'); // 1 (Mon) through 7 (Sun)
        $week_start_date_obj = clone $date_obj;
        // Move to Monday of the current week (N=1 for Monday)
        if ($day_of_week_num != 1) { // If not Monday, go back to previous Monday
            $week_start_date_obj->modify('last monday');
        }
        $week_start_date = $week_start_date_obj->format('Y-m-d');


        // Initialize weekly tracking for this week if not already
        if (!isset($weekly_tracking[$week_start_date])) {
            $weekly_tracking[$week_start_date] = [
                'has_deductible_event' => false, // F or C
                'has_descanso' => false,        // Descanso day (41344444)
                'descanso_date' => null,        // Date of Descanso
                'deducted_7mo' => false         // Flag to prevent multiple 7mo deductions for same week
            ];
        }

        if ($row_asistencia) {
            $is_activity_recorded = true;
            $horas_extra_registradas = (float)($row_asistencia['hora_extra'] ?? 0);

            // Extraer códigos para la concatenación, asegurando que sean cadenas vacías si son nulos
            $CodigoJornada = trim($row_asistencia['codigo_jornada'] ?? '');
            $CodigoLicencia = trim($row_asistencia['codigo_tipo_licencia'] ?? '');
            $CodigoJornadaAsueto = trim($row_asistencia['codigo_jornada_asueto'] ?? '');
            $CodigoJornadaVacaciones = trim($row_asistencia['codigo_jornada_vacaciones'] ?? '');
            $CodigoJornadaDescanso = trim($row_asistencia['codigo_jornada_descanso'] ?? '');
            $CodigoJornadaE4H = trim($row_asistencia['codigo_jornada_e_4h'] ?? '');
            $CodigoJornadaNocturna = trim($row_asistencia['codigo_jornada_nocturna'] ?? '');

            // Formar el CodigoJornadaTodas temporal para la primera validación
            $CodigoJornadaTodas_temp = $CodigoJornada.$CodigoLicencia.$CodigoJornadaAsueto.$CodigoJornadaVacaciones.$CodigoJornadaDescanso.$CodigoJornadaE4H.$CodigoJornadaNocturna;
            $CodigoJornadaTodas = $CodigoJornadaTodas_temp; // Asignar al final, podría ser sobreescrito por FALTA/AS


            // --- Lógica para códigos que NO SUMAN AL SALARIO (solo visualización) ---
            if (in_array(trim($CodigoJornadaTodas_temp), $non_contributory_codes_display_only)) {
                $salario_dia_actual = 0; // NO se suma al salario
                $monto_horas_extra_dia_actual = 0; // No hay pago de HE
                $descuento_dia_actual = 0; // No es una "falta" deducible
                $es_dia_pagado_para_hora_extra = false;
            } 
            // --- Lógica para DÍAS DE CASTIGO 'C' o FALTA 'F' (doble descuento) ---
            else if (in_array(trim($CodigoJornadaTodas_temp), $double_deduction_codes)) {
                $salario_dia_actual = $salario_diario; // <<--- CAMBIO CLAVE: El día SÍ se paga para el cálculo del bruto.
                $monto_horas_extra_dia_actual = 0; // No hay pago de HE
                $descuento_dia_actual = $salario_diario * 2; // Doble descuento
                $es_dia_pagado_para_hora_extra = false;
                
                // Marcar que hubo un evento deducible en esta semana para el 7mo día
                $weekly_tracking[$week_start_date]['has_deductible_event'] = true;
            }
            // --- Lógica NORMAL para días que SÍ PUEDEN SUMAR AL SALARIO ---
            else { 
                $es_dia_pagado_para_hora_extra = true; // Potencialmente contribuye al salario

                // Priority: Asueto (con pago adicional) > Asueto (normal) > Trabajo Descanso Asueto > Trabajo Descanso > Jornada > Descanso > Vacaciones > Nocturna > E_4H > Licencias (otras)
                // --- Lógica para Asuetos con pago adicional por trabajar (41614444, 41624444, 41634444) ---
                if (isset($asueto_worked_codes[trim($CodigoJornadaTodas_temp)])) {
                    $total_dias_asuetos_empleado_count++; // Contar como día de asueto
                    $total_salario_asuetos += $asueto_worked_codes[trim($CodigoJornadaTodas_temp)]; // Suma el valor ADICIONAL
                    $salario_dia_actual = $salario_diario; // El día base del asueto se paga aquí
                    $es_dia_pagado_para_hora_extra = true;
                    $horas_jornada_para_este_dia = (float)($row_asistencia['horas_jornada_regular'] ?? $jornada_base_default); // Usa jornada_regular si aplica
                }
                // --- Asueto normal (2144444) ---
                else if (!empty($CodigoJornadaAsueto) && $CodigoJornadaAsueto == '2144444') { 
                    $total_dias_asuetos_empleado_count++;
                    $salario_dia_actual = $salario_diario; // Se paga el día normal de asueto
                    $es_dia_pagado_para_hora_extra = true; 
                    $horas_jornada_para_este_dia = (float)($row_asistencia['horas_jornada_regular'] ?? $jornada_base_default); 
                } 
                // --- Trabajo Descanso Asueto (TDA) con pago adicional (41514444, 41524444, 41534444, 41744444) ---
                else if (isset($trabajo_descanso_asueto_codes[trim($CodigoJornadaTodas_temp)])) {
                    // Si es 41744444 (DA), se paga el día normal, no hay adicional extra para asuetos
                    if (trim($CodigoJornadaTodas_temp) == '41744444') {
                        $salario_dia_actual = $salario_diario; // Se paga el día normal
                        $total_dias_descanso_empleado++; // Cuenta como día de descanso
                        $total_dias_asuetos_empleado_count++; // Cuenta como día de asueto
                    } else {
                        // Para 41514444, 41524444, 41534444 (TDA), se paga el día normal + adicional
                        $total_salario_asuetos += $trabajo_descanso_asueto_codes[trim($CodigoJornadaTodas_temp)]; // Suma el valor ADICIONAL a asuetos
                        $salario_dia_actual = $salario_diario; // El día base del descanso asueto se paga aquí
                        $total_dias_asuetos_empleado_count++; // Cuenta como día de asueto
                    }
                    $es_dia_pagado_para_hora_extra = true;
                    $horas_jornada_para_este_dia = (float)($row_asistencia['horas_jornada_regular'] ?? $jornada_base_default); // Usa jornada_regular si aplica
                }
                // --- Trabajo Descanso (TD) con pago adicional (41444144, 41444244, 41444344) ---
                else if (isset($trabajo_descanso_codes[trim($CodigoJornadaTodas_temp)])) {
                    $total_trabajo_extra_empleado += $trabajo_descanso_codes[trim($CodigoJornadaTodas_temp)]; // Suma el valor ADICIONAL
                    $salario_dia_actual = $salario_diario; // El día base del descanso trabajado se paga aquí
                    $es_dia_pagado_para_hora_extra = true;
                    $horas_jornada_para_este_dia = (float)($row_asistencia['horas_jornada_regular'] ?? $jornada_base_default); // Usa jornada_regular si aplica
                }
                else if (!empty($CodigoJornada) && $CodigoJornada == '4') { // Jornada tipo 4 es una licencia
                    $total_dias_licencias_empleado++;
                    $es_dia_pagado_para_hora_extra = true;
                    $licencia_info = $NombresCodigoLicenciaPermiso[$row_asistencia['codigo_tipo_licencia']] ?? ['horas' => 0, 'descripcion' => 'N/A'];
                    $horas_licencia_dia = (float)($licencia_info['horas'] ?? 0);
                    
                    $valor_hora_normal = ($jornada_base_default > 0) ? ($salario_diario / $jornada_base_default) : 0;
                    $salario_dia_actual = $valor_hora_normal * $horas_licencia_dia;
                    $horas_jornada_para_este_dia = $horas_licencia_dia;
                }
                else if (!empty($CodigoJornada)) {
                    $total_dias_jornada_empleado++;
                    $es_dia_pagado_para_hora_extra = true;
                    $salario_dia_actual = $salario_diario;
                    $horas_jornada_para_este_dia = (float)($row_asistencia['horas_jornada_regular'] ?? $jornada_base_default);
                }
                else if (!empty($CodigoJornadaDescanso)) {
                    $total_dias_descanso_empleado++;
                    $es_dia_pagado_para_hora_extra = true;
                    $salario_dia_actual = $salario_diario;
                    // Marcar que hubo un descanso en esta semana para el 7mo día
                    $weekly_tracking[$week_start_date]['has_descanso'] = true;
                    $weekly_tracking[$week_start_date]['descanso_date'] = $fecha_actual;
                }
                else if (!empty($CodigoJornadaVacaciones)) {
                    $total_dias_vacaciones_empleado++;
                    $es_dia_pagado_para_hora_extra = true;
                    $salario_dia_actual = $salario_diario;
                }
                else if (!empty($CodigoJornadaNocturna)) {
                    $total_dias_nocturna_empleado++;
                    $es_dia_pagado_para_hora_extra = true;
                    $salario_dia_actual = $salario_diario;
                }
                else if (!empty($CodigoJornadaE4H)) {
                    $total_dias_e4h_empleado++;
                    $es_dia_pagado_para_hora_extra = true;
                    $salario_dia_actual = $salario_diario;
                }
                else if (!empty($CodigoLicencia)) { 
                    $total_dias_licencias_empleado++;
                    $es_dia_pagado_para_hora_extra = true;
                    $licencia_info = $NombresCodigoLicenciaPermiso[$row_asistencia['codigo_tipo_licencia']] ?? ['horas' => 0, 'descripcion' => 'N/A'];
                    $horas_licencia_dia = (float)($licencia_info['horas'] ?? 0);
                    $valor_hora_normal = ($jornada_base_default > 0) ? ($salario_diario / $jornada_base_default) : 0;
                    $salario_dia_actual = $valor_hora_normal * $horas_licencia_dia;
                    $horas_jornada_para_este_dia = $horas_licencia_dia;
                }

                // Calcular monto de horas extras (solo si el día se considera pagado)
                if ($es_dia_pagado_para_hora_extra) {
                    // Factor de hora extra: 2 para departamentos 02 y 03, 1 para otros
                    $factor_hora_extra = ($codigo_departamento_empleado == '02' || $codigo_departamento_empleado == '03') ? 2 : 1;
                    $valor_hora_normal_base = ($jornada_base_default > 0) ? ($salario_diario / $jornada_base_default) : 0;
                    $monto_horas_extra_dia_actual = $horas_extra_registradas * ($valor_hora_normal_base * $factor_hora_extra); 
                    $total_horas_extra_cantidad += $horas_extra_registradas; 
                }
            } // Cierre ELSE de la lógica de códigos no contributivos y doble descuento
        } // Cierre if ($row_asistencia)
        
        // --- Lógica para cuando NO HAY REGISTRO DE ASISTENCIA (posible FALTA genérica o Asueto de calendario sin registro) ---
        else { 
            $es_asueto_calendario = isset($FechaDescripcionAsueto[$fecha_actual]);
            if (!$es_asueto_calendario) { // Es una FALTA genérica (no es asueto de calendario)
                $descuento_dia_actual = $salario_diario; // Se deduce el día completo
                $CodigoJornadaTodas = 'FALTA_GENERICA'; // Código para imagen de FALTA GENÉRICA
                // Marcar como evento deducible en esta semana para el 7mo día si aplica (asumiendo que faltas genéricas también lo activan)
                $weekly_tracking[$week_start_date]['has_deductible_event'] = true;
            } else { // Es un ASUETo del calendario (pero no hay registro específico de asistencia)
                $CodigoJornadaTodas = 'AS'; // Código para imagen de Asueto de calendario
                $salario_dia_actual = $salario_diario; // Se paga el día de asueto
                $total_salario_asuetos += $salario_diario; // Se suma al total de asuetos
            }
        }

        // Acumulación de totales
        $total_salario_devengado_empleado += $salario_dia_actual;
        $total_monto_horas_extra_empleado += $monto_horas_extra_dia_actual;
        $total_descuentos_empleado += $descuento_dia_actual; 
        
        // FINALIZAR FORMACIÓN DEL CODIGO ALL PARA LA IMAGEN (añadir _HE si aplica)
        if ($horas_extra_registradas > 0 && strpos($CodigoJornadaTodas, '_HE') === false) { 
            $CodigoJornadaTodas .= str_replace('.', '', (string)$horas_extra_registradas); 
        }
        
        // Buscar el nombre del archivo de imagen en el mapa
        $image_filename = $jornada_imagenes_map[trim($CodigoJornadaTodas)] ?? '';

        $daily_attendance_details[$fecha_actual] = [
            'code_all' => $CodigoJornadaTodas,
            'image_filename' => $image_filename,
            'horas_extra_dia' => $horas_extra_registradas, // Store daily extra hours for potential sub-rows
        ];

    } // Cierre del foreach ($rango_fechas as $fecha_actual)

    // --- CÁLCULO DE DEDUCCIONES DEL 7mo DÍA (DESPUÉS DE PROCESAR TODOS LOS DÍAS) ---
    foreach ($weekly_tracking as $week_start => $week_data) {
        if ($week_data['has_deductible_event'] && $week_data['has_descanso'] && !$week_data['deducted_7mo']) {
            // Deduce el salario diario del día de descanso
            $total_deduccion_7mo += $salario_diario; // El valor del 7mo es el salario de un día normal
            $weekly_tracking[$week_start]['deducted_7mo'] = true; // Marca como deducido
        }
    }
    // Sumar la deducción del 7mo día al total de descuentos
    $total_descuentos_empleado += $total_deduccion_7mo;


    // El "Total Extra" es la suma del valor monetario de Asuetos (adicionales) y Horas Extra y Trabajo Descanso
    $total_extra_empleado = $total_salario_asuetos + $total_monto_horas_extra_empleado + $total_trabajo_extra_empleado; 

    // Cálculo del Total Salario (Gross): Salario Devengado (normal + asueto base) + Valor Horas Extra + Valor Asuetos Adicionales + Valor Trabajo Descanso
    $total_salario_gross_empleado = $total_salario_devengado_empleado + $total_monto_horas_extra_empleado + $total_salario_asuetos + $total_trabajo_extra_empleado; 

    // Cálculo del Salario Líquido Final: Total Salario (Gross) - Total Descuentos
    $salario_liquido_final_empleado = $total_salario_gross_empleado - $total_descuentos_empleado - $total_otras_deducciones_empleado;

    return [
        'total_salario_devengado' => $total_salario_devengado_empleado, 
        'total_salario_asuetos' => $total_salario_asuetos, // Este es el valor monetario ADICIONAL de asuetos
        'total_monto_horas_extra' => $total_monto_horas_extra_empleado, 
        'total_descuentos' => $total_descuentos_empleado, 
        'salario_liquido_final' => $salario_liquido_final_empleado, 
        'total_extra_general' => $total_extra_empleado, 
        'total_horas_extra_cantidad' => $total_horas_extra_cantidad, 
        'total_salario_gross' => $total_salario_gross_empleado, 
        'daily_details' => $daily_attendance_details, 
        'total_trabajo_extra_empleado' => $total_trabajo_extra_empleado // Añadido al retorno
    ];
}

// --- FIN DE LA FUNCIÓN processEmployeeAttendanceData ---


// INICIO DE LA CLASE PDF (deberías tener una estructura similar)
class PDF extends FPDF
{
    // Mapeo manual de días de la semana (para asegurar el español)
    private $day_names_spanish = [
        'Sun' => 'Dom',
        'Mon' => 'Lun',
        'Tue' => 'Mar',
        'Wed' => 'Mié',
        'Thu' => 'Jue',
        'Fri' => 'Vie',
        'Sat' => 'Sáb'
    ];

    function Header() {
        global $fecha_periodo_inicio, $fecha_periodo_fin, $departamentoEmpresaTexto, $RutaText, $quincena, $rango_fechas;
        // Variables adicionales para el Header (asegurarse de que estén definidas en el ámbito global del script principal)
        global $_SESSION, $persona_responsable; 

        // --- INICIO CÓDIGO DEL USUARIO PARA EL HEADER ---
        // Logo
        $img = $_SERVER['DOCUMENT_ROOT'].'/acomtus/img/'.$_SESSION['logo_uno'];
        // Asegurarse de que el archivo de imagen del logo existe
        if (file_exists($img)) {
            $this->Image($img,5,4,24,24);
        } else {
            // Si el logo no existe, puedes imprimir un texto o dejar un espacio en blanco
            $this->SetXY(5,4);
            $this->Cell(24,24,'[LOGO]',1,0,'C');
        }
        
        // Arial bold 14
        $this->SetFont('Arial','B',14);
        // Título de la Institución
        $this->SetXY(30,5);
        $this->Cell(100,7,mb_convert_encoding($_SESSION["nombre_institucion"],"ISO-8859-1"),0,1,"L",false);
        
        // Contenido para Reporte de Trabajo y Reporte de Ruta
        $reporte_trabajo_display = utf8_decode('Reporte de trabajo correspondiente a la quincena del ');
        $reporte_trabajo_display .= date('d', strtotime($fecha_periodo_inicio)) . ' al ' . date('d', strtotime($fecha_periodo_fin)) . ' de ' . utf8_decode(strftime('%B', strtotime($fecha_periodo_inicio))) . ' de ' . date('Y', strtotime($fecha_periodo_inicio));
        
        $reporte_ruta_display = utf8_decode($departamentoEmpresaTexto);
        if (!empty($RutaText) && $RutaText != '00' && $RutaText != 'Seleccionar...') { // Si se seleccionó una ruta específica
            $reporte_ruta_display .= utf8_decode(' (Ruta: ') . utf8_decode($RutaText) . utf8_decode(')');
        }

        // Arial bold 11
        $this->SetFont('Arial','B',11);
        $this->SetX(30);
        $this->Cell(100,6, $reporte_trabajo_display,0,1,"L",false);
        $this->SetX(30);
        $this->Cell(100,6, $reporte_ruta_display,0,1,"L",false);
        
        // Persona Responsable del Punteo.
        $this->SetFont('Arial','B',9);
        $this->SetX(30);
        $this->Cell(130,6,mb_convert_encoding("Responsable del Punteo: " . ($persona_responsable ?? 'N/A'),"ISO-8859-1"),0,0,"L",false);
        $this->Cell(4,6,"",0,0,"L",false); 
        $this->Ln(5); // Espacio después de la información de la empresa/responsable
        // --- FIN CÓDIGO DEL USUARIO PARA EL HEADER ---

        // Encabezados de la tabla principal
        $this->SetFont('Arial', 'B', 7);
        $this->SetFillColor(200, 220, 255);
        $this->SetDrawColor(0,0,0); // Set border color to black

        // Anchos para las COLUMNAS FIJAS INICIALES: No., CODIGO, NOMBRE
        $w_initial_fixed = [7, 15, 50]; // Total 72mm
        // Ancho para cada columna de día
        $daily_col_width = 7.5; // Aproximadamente 16 días * 7.5mm = 120mm

        // Anchos para las COLUMNAS FINANCIERAS PRINCIPALES: Salarios, Asuetos, EXTRA, Hora Extra, Total Extra, Salario Líquido
        $w_financial_fixed = [11, 8, 10, 17, 14, 15]; // Suma a 75mm. Ajustados.

        $header_height = 12; // Altura total para el encabezado de dos filas
        $half_header_height = $header_height / 2; // 6mm para cada sub-fila de encabezado

        $x_start = 10;
        $y_start_table_headers = $this->GetY(); // Obtener la Y actual después del bloque de información de la empresa

        // 1. Imprimir encabezados de la primera fila
        $this->SetY($y_start_table_headers);
        $this->SetX($x_start);

        $this->Cell($w_initial_fixed[0], $header_height, 'No.', 1, 0, 'C', true); // No.
        $this->Cell($w_initial_fixed[1], $header_height, 'CODIGO', 1, 0, 'C', true); // CODIGO
        $this->Cell($w_initial_fixed[2], $header_height, 'NOMBRE', 1, 0, 'C', true); // NOMBRE
        
        // Mover X a donde comienzan los encabezados de días
        $x_after_initial_fixed = $x_start + array_sum($w_initial_fixed);
        $this->SetX($x_after_initial_fixed);

        // Encabezados superiores de días (Nombre del día)
        foreach ($rango_fechas as $fecha_actual) {
            $english_day_name = date('D', strtotime($fecha_actual)); 
            $spanish_day_name = $this->day_names_spanish[$english_day_name] ?? $english_day_name; 
            // Color de fondo para Sábados y Domingos
            if ($english_day_name == 'Sat' || $english_day_name == 'Sun') {
                $this->SetFillColor(180, 200, 230); // Color más oscuro
            } else {
                $this->SetFillColor(200, 220, 255); // Color normal
            }
            $this->Cell($daily_col_width, $half_header_height, utf8_decode(strtoupper($spanish_day_name)), 1, 0, 'C', true);
        }

        // Encabezados financieros de la primera fila (padres o que abarcan dos filas)
        // Fondo más oscuro para SA y Total Extra
        $this->SetFillColor(180, 200, 230); // Color más oscuro para SA
        $this->Cell($w_financial_fixed[0], $header_height, 'SA', 1, 0, 'C', true); // Salarios -> SA
        $this->SetFillColor(200, 220, 255); // Resetear color
        $this->Cell($w_financial_fixed[1], $header_height, 'AS', 1, 0, 'C', true); // Asuetos -> AS
        $this->SetFillColor(180, 200, 230); // Color más oscuro para EXTRA
        $this->Cell($w_financial_fixed[2], $half_header_height, utf8_decode('EXTRA'), 1, 0, 'C', true); // Nueva columna EXTRA
        $this->SetFillColor(200, 220, 255); // Resetear color
        $this->Cell($w_financial_fixed[3], $half_header_height, utf8_decode('Hora Extra'), 1, 0, 'C', true); // Encabezado padre de Hora Extra
        $this->SetFillColor(180, 200, 230); // Color más oscuro para Total Extra
        $this->Cell($w_financial_fixed[4], $half_header_height, 'Total', 1, 0, 'C', true); // Encabezado padre de Total Extra
        $this->SetFillColor(200, 220, 255); // Resetear color
        $this->Cell($w_financial_fixed[5], $half_header_height, 'Salario', 1, 0, 'C', true); // Encabezado padre de Salario Liquido

        $this->Ln(); // Nueva línea para la segunda fila de encabezados

        // Segunda fila de encabezados (Números de día y sub-encabezados C, V, Extra, Liquido)
        $this->SetY($y_start_table_headers + $half_header_height); // Mover Y hacia abajo a la mitad del encabezado
        $this->SetX($x_after_initial_fixed); // Resetear X al inicio de las columnas diarias

        // Números de día (con fondo más oscuro para Sáb y Dom)
        foreach ($rango_fechas as $fecha_actual) {
            $english_day_name = date('D', strtotime($fecha_actual)); 
            $day_number = date('d', strtotime($fecha_actual)); 
            // Color de fondo para Sábados y Domingos
            if ($english_day_name == 'Sat' || $english_day_name == 'Sun') {
                $this->SetFillColor(180, 200, 230); // Color más oscuro
            } else {
                $this->SetFillColor(200, 220, 255); // Color normal
            }
            $this->Cell($daily_col_width, $half_header_height, $day_number, 1, 0, 'C', true);
        }

        // Sub-encabezados para columnas financieras
        $this->SetFillColor(200, 220, 255); // Resetear color
        $this->Cell($w_financial_fixed[0], $half_header_height, '', 0, 0, 'C', false); // Celda vacía bajo SA
        $this->Cell($w_financial_fixed[1], $half_header_height, '', 0, 0, 'C', false); // Celda vacía bajo AS
        $this->Cell($w_financial_fixed[2], $half_header_height, '', 0, 0, 'C', false); // Celda vacía bajo EXTRA
        $this->Cell($w_financial_fixed[3]/2, $half_header_height, 'C', 1, 0, 'C', true); // Sub-encabezado C de Hora Extra
        $this->Cell($w_financial_fixed[3]/2, $half_header_height, 'V', 1, 0, 'C', true); // Sub-encabezado V de Hora Extra
        $this->SetFillColor(180, 200, 230); // Color más oscuro para Total Extra
        $this->Cell($w_financial_fixed[4], $half_header_height, 'Extra', 1, 0, 'C', true); // Sub-encabezado Extra de Total Extra
        $this->SetFillColor(200, 220, 255); // Resetear color
        $this->Cell($w_financial_fixed[5], $half_header_height, 'Liquido', 1, 0, 'C', true); // Sub-encabezado Liquido de Salario Liquido

        $this->Ln(); // Mover a la siguiente línea después de completar todos los encabezados.
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

// Anchos para las COLUMNAS FIJAS INICIALES: No., CODIGO, NOMBRE
$w_initial_fixed = [7, 15, 50]; // Total 72mm
// Ancho para cada columna de día
$daily_col_width = 7.5;
// Anchos para las COLUMNAS FINANCIERAS PRINCIPALES: Salarios, Asuetos, EXTRA, Hora Extra (padre), Total Extra, Salario Líquido
$w_financial_fixed = [11, 8, 10, 17, 14, 15]; // Suma a 75mm. Ajustados.

// --- INICIO DEL BUCLE PRINCIPAL QUE ITERA A TRAVÉS DE CADA EMPLEADO ---
// Definir colores de fila alternos para los datos de los empleados
$row_color_even = [234, 236, 238]; // Color gris claro
$row_color_odd = [255, 255, 255];  // Color blanco
$row_fill_flag = false; // Bandera para alternar colores

foreach ($datos_empleado_principal as $row_empleado) {
    $codigo_personal = TRIM($row_empleado['codigo']);
    $nombres_empleado = TRIM($row_empleado['nombres']);
    $apellidos_empleado = TRIM($row_empleado['apellidos']);
    $salario_mensual = (float)$row_empleado['salario'];
    $codigo_departamento_empleado = TRIM($row_empleado['codigo_departamento_empresa']);
    
    $nombre_completo = trim(utf8_decode($nombres_empleado . ' ' . $apellidos_empleado));

    // 1. PROCESAR DATOS Y OBTENER TOTALES PARA EL EMPLEADO ACTUAL (incluyendo detalles diarios)
    $results = processEmployeeAttendanceData(
        $rango_fechas,
        $codigo_personal,
        $salario_mensual,
        $jornada_base_default,
        $asistencia_por_empleado_y_fecha,
        $NombresCodigoLicenciaPermiso,
        $jornada_imagenes_map,
        $FechaDescripcionAsueto, // Pass Asueto dates here
        $codigo_departamento_empleado // Pass department code
    );

    // Nuevas variables para los totales (usando el operador null coalescing para evitar "Undefined index" si la clave falta)
    $total_salarios_a_mostrar = $results['total_salario_devengado'] ?? 0;
    $total_salario_asuetos_a_mostrar = $results['total_salario_asuetos'] ?? 0;
    $total_monto_horas_extra_a_mostrar = $results['total_monto_horas_extra'] ?? 0;
    $total_extra_general_a_mostrar = $results['total_extra_general'] ?? 0; 
    $total_salario_gross_a_mostrar = $results['total_salario_gross'] ?? 0;
    $total_horas_extra_cantidad_a_mostrar = $results['total_horas_extra_cantidad'] ?? 0;
    $salario_liquido_final_a_mostrar = $results['salario_liquido_final'] ?? 0;
    $total_trabajo_extra_empleado_a_mostrar = $results['total_trabajo_extra_empleado'] ?? 0; // Nueva variable para mostrar

    $daily_attendance_details = $results['daily_details'] ?? []; // Asegurar que daily_details sea un array

    // Determinar el color de fondo de la fila actual
    $current_row_fill_color = $row_fill_flag ? $row_color_odd : $row_color_even;
    $pdf->SetFillColor($current_row_fill_color[0], $current_row_fill_color[1], $current_row_fill_color[2]); 

    // 2. IMPRIMIR LA LÍNEA PRINCIPAL DEL EMPLEADO CON CÁLCULOS INTEGRADOS
    $pdf->SetFont('Arial', '', 7); // Fuente para la línea principal
    $pdf->SetDrawColor(0,0,0); // Borde negro

    $pdf->SetX(10); // Reiniciar posición X

    // COLUMNAS FIJAS INICIALES (No., CODIGO, NOMBRE)
    $pdf->Cell($w_initial_fixed[0], 6, $i, 1, 0, 'C', true); // No.
    $pdf->Cell($w_initial_fixed[1], 6, $codigo_personal, 1, 0, 'L', true); // CÓDIGO
    $pdf->Cell($w_initial_fixed[2], 6, $nombre_completo, 1, 0, 'L', true); // NOMBRE

    // Imprimir las imágenes diarias
    foreach ($rango_fechas as $fecha_actual) {
        $daily_detail = $daily_attendance_details[$fecha_actual] ?? ['image_filename' => ''];
        $image_filename = $daily_detail['image_filename'];
        
        $image_full_path = $image_base_path . $image_filename;

        // Ensure image file exists before attempting to insert it
        // And that the filename is not empty
        if (!empty($image_filename) && file_exists($image_full_path)) {
            $pdf->Cell($daily_col_width, 6, '', 1, 0, 'C', true); // Celda de fondo para la imagen
            // Posicionar la imagen dentro de la celda. Ajustar +0.5 para un pequeño margen.
            $pdf->Image($image_full_path, $pdf->GetX() - $daily_col_width + 0.5, $pdf->GetY() + 0.5, $daily_col_width - 1, 5); // Ancho y alto de la imagen
        } else {
            $pdf->Cell($daily_col_width, 6, '', 1, 0, 'C', true); // Celda vacía si no hay imagen o archivo
        }
    }

    // IMPRIMIR COLUMNAS FIJAS FINALES (Salarios, Asuetos, EXTRA, Hora Extra C:V, Total Extra, Salario Líquido)
    // Determinar el color de fondo para las celdas de datos financieros
    // Usar los colores definidos previamente para las filas alternas
    $pdf->SetFillColor($current_row_fill_color[0], $current_row_fill_color[1], $current_row_fill_color[2]); 

    // Helper para formatear números o dejar vacío si es cero
    $format_num = function($value, $decimals = 2) {
        return ($value == 0) ? '' : number_format($value, $decimals, '.', ',');
    };

    // Salarios
    $pdf->Cell($w_financial_fixed[0], 6, $format_num($total_salarios_a_mostrar), 1, 0, 'R', true); 
    
    // Asuetos
    $pdf->Cell($w_financial_fixed[1], 6, $format_num($total_salario_asuetos_a_mostrar), 1, 0, 'R', true); 
    
    // EXTRA
    $pdf->Cell($w_financial_fixed[2], 6, $format_num($total_trabajo_extra_empleado_a_mostrar), 1, 0, 'R', true); 

    // Columna de HORAS EXTRA (C:V)
    $he_display_string_C = $format_num($total_horas_extra_cantidad_a_mostrar, 0); // Cantidad entera
    $he_display_string_V = $format_num($total_monto_horas_extra_a_mostrar, 2); // Valor con 2 decimales

    // Ahora se imprimen en dos celdas separadas (C y V)
    $pdf->Cell($w_financial_fixed[3]/2, 6, $he_display_string_C, 1, 0, 'C', true); // Celda C
    $pdf->Cell($w_financial_fixed[3]/2, 6, $he_display_string_V, 1, 0, 'C', true); 

    // Total Extra (TE)
    $pdf->Cell($w_financial_fixed[4], 6, $format_num($total_extra_general_a_mostrar), 1, 0, 'R', true); 
    
    // Salario Liquido
    $pdf->Cell($w_financial_fixed[5], 6, $format_num($salario_liquido_final_a_mostrar), 1, 1, 'R', true); 
    
    // Alternar el color de fondo para la próxima fila
    $row_fill_flag = !$row_fill_flag;

    // SALTO DE LÍNEA PEQUEÑO ENTRE EMPLEADOS (opcional, ajusta si es necesario)
    $pdf->Ln(2); 

    $i++; // Incrementar el contador de empleado
}
// --- FIN DEL BUCLE PRINCIPAL QUE CAMBIA DE EMPLEADO ---

// Output PDF.
$modo = "I"; // Envia al navegador (I), Descarga el archivo (D), Guardar el fichero en un local(F).
$print_nombre = mb_convert_encoding("Planilla: $departamentoEmpresaTexto - $quincena - $mes.pdf","ISO-8859-1");
$pdf->Output($print_nombre,$modo);

?>
