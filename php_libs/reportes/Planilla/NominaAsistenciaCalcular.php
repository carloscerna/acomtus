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
date_default_timezone_set('America/El_Salvador');
setlocale(LC_TIME,'es_SV');
$hora_actual = date("h:i:s a");
$hoy = getdate();
$mes = $hoy["mon"];
$path_root=trim($_SERVER['DOCUMENT_ROOT']);
include($path_root."/acomtus/includes/mainFunctions_conexion.php");
include($path_root."/acomtus/php_libs/fpdf/fpdf.php");
header("Content-Type: text/html; charset=UTF-8");

// --- FUNCIÓN AUXILIAR (MOVIDA AL INICIO PARA EVITAR ERRORES) ---
function buscarCodigoDeAsistencia($dblink, $codigo_personal, $fecha_str, &$datos_precargados) {
    if (isset($datos_precargados[$codigo_personal][$fecha_str])) {
        $asistencia_dia = $datos_precargados[$codigo_personal][$fecha_str];
    } else {
        $stmt_check = $dblink->prepare("
            SELECT pa.codigo_jornada, pa.codigo_tipo_licencia, pa.codigo_jornada_asueto, 
                   pa.codigo_jornada_vacaciones, pa.codigo_jornada_descanso, 
                   pa.codigo_jornada_e_4h, pa.codigo_jornada_nocturna
            FROM personal_asistencia pa
            WHERE pa.codigo_personal = :codigo AND pa.fecha = :fecha
        ");
        $stmt_check->bindParam(':codigo', $codigo_personal);
        $stmt_check->bindParam(':fecha', $fecha_str);
        $stmt_check->execute();
        $asistencia_dia = $stmt_check->fetch(PDO::FETCH_ASSOC);
        $datos_precargados[$codigo_personal][$fecha_str] = $asistencia_dia;
    }

    if ($asistencia_dia) {
        return trim($asistencia_dia['codigo_jornada'] ?? '') .
               trim($asistencia_dia['codigo_tipo_licencia'] ?? '') .
               trim($asistencia_dia['codigo_jornada_asueto'] ?? '') .
               trim($asistencia_dia['codigo_jornada_vacaciones'] ?? '') .
               trim($asistencia_dia['codigo_jornada_descanso'] ?? '') .
               trim($asistencia_dia['codigo_jornada_e_4h'] ?? '') .
               trim($asistencia_dia['codigo_jornada_nocturna'] ?? '');
    }
    return '';
}

$fecha_mes = $_REQUEST["fechaMes"];
$fecha_ann = $_REQUEST["fechaAnn"];
$quincena = $_REQUEST["quincena"];
$NombreRuta = $_REQUEST["ruta"];
$RutaText = $_REQUEST["RutaText"];
$calcular = $_REQUEST["chkCalcular"];
$DepartamentoEmpresa = $_REQUEST["DepartamentoEmpresa"];
$departamentoEmpresaTexto = $_REQUEST["DepartamentoText"];
$persona_responsable = $_REQUEST["persona_responsable"] ?? 'No Definido';

if (!isset($fecha_mes) || !isset($quincena)) {
    die("Faltan parámetros de fecha o quincena.");
}
if (!isset($fecha_ann) || !is_numeric($fecha_ann)) {
    $fecha_ann = date('Y');
}

$fecha_periodo_inicio = '';
$fecha_periodo_fin = '';
if ($quincena == 'Q1') {
    $fecha_periodo_inicio = $fecha_ann . '-' . $fecha_mes . '-01';
    $fecha_periodo_fin = $fecha_ann . '-' . $fecha_mes . '-15';
} elseif ($quincena == 'Q2') {
    $fecha_periodo_inicio = $fecha_ann . '-' . $fecha_mes . '-16';
    $fecha_periodo_fin = date('Y-m-t', strtotime($fecha_ann . '-' . $fecha_mes . '-01'));
} else {
    die("Valor de quincena inválido.");
}

$period = new DatePeriod(new DateTime($fecha_periodo_inicio), new DateInterval('P1D'), (new DateTime($fecha_periodo_fin))->modify('+1 day'));
$rango_fechas = [];
foreach ($period as $date) {
    $rango_fechas[] = $date->format('Y-m-d');
}

$image_base_path = $_SERVER['DOCUMENT_ROOT'] . "/acomtus/img/Catalogo Jornada/";
$asistencia_por_empleado_y_fecha = [];

$query = "SELECT p.codigo, p.nombres, p.apellidos, p.salario, p.codigo_ruta, p.codigo_departamento_empresa, cc.descripcion as cargo_descripcion ";
$query .= "FROM personal p ";
$query .= "LEFT JOIN catalogo_cargo cc ON cc.codigo = p.codigo_cargo ";
$query .= "WHERE 1=1 AND p.codigo_estatus = '01'";
if ($DepartamentoEmpresa == '02' && $NombreRuta != '00') {
    $query .= " AND p.codigo_ruta = '$NombreRuta'";
}
if ($DepartamentoEmpresa != '00') {
    $query .= " AND p.codigo_departamento_empresa = '$DepartamentoEmpresa'";
}
$query .= " ORDER BY p.codigo";

$stmt_codigos_personal = $dblink->query($query);
$codigos_personal_a_consultar = [];
$datos_empleado_principal = [];
while ($row_codigo = $stmt_codigos_personal->fetch(PDO::FETCH_ASSOC)) {
    $codigos_personal_a_consultar[] = $row_codigo['codigo'];
    $datos_empleado_principal[$row_codigo['codigo']] = $row_codigo;
}

if (!empty($codigos_personal_a_consultar)) {
    $codigos_personal_str = "'" . implode("','", $codigos_personal_a_consultar) . "'";
    $stmt_all_asistencia = $dblink->prepare("
        SELECT pa.codigo_personal, pa.fecha, pa.hora_extra,
               pa.codigo_jornada, pa.codigo_tipo_licencia, pa.codigo_jornada_descanso, 
               pa.codigo_jornada_vacaciones, pa.codigo_jornada_nocturna, pa.codigo_jornada_e_4h, pa.codigo_jornada_asueto
        FROM personal_asistencia pa
        WHERE pa.codigo_personal IN ($codigos_personal_str)
        AND pa.fecha BETWEEN :fecha_inicio AND :fecha_fin
        ORDER BY pa.fecha
    ");
    $stmt_all_asistencia->bindParam(':fecha_inicio', $fecha_periodo_inicio);
    $stmt_all_asistencia->bindParam(':fecha_fin', $fecha_periodo_fin);
    $stmt_all_asistencia->execute();
    while ($row_asistencia = $stmt_all_asistencia->fetch(PDO::FETCH_ASSOC)) {
        $codigo_p = $row_asistencia['codigo_personal'];
        $fecha_a = $row_asistencia['fecha'];
        $asistencia_por_empleado_y_fecha[$codigo_p][$fecha_a] = $row_asistencia;
    }
}

$NombresCodigoLicenciaPermiso = [];
$consultaLic = $dblink->query("SELECT id_, descripcion, horas FROM catalogo_tipo_licencia_o_permiso");
while ($row = $consultaLic->fetch(PDO::FETCH_ASSOC)) {
    $NombresCodigoLicenciaPermiso[$row['id_']] = ['descripcion' => $row['descripcion'], 'horas' => $row['horas']];
}

$FechaDescripcionAsueto = [];
$consultaAsueto = $dblink->query("SELECT fecha, descripcion FROM asuetos");
while ($row = $consultaAsueto->fetch(PDO::FETCH_ASSOC)) {
    $FechaDescripcionAsueto[$row['fecha']] = $row['descripcion'];
}

$jornada_imagenes_map = [];
$consulta_imagenes = $dblink->query("SELECT codigo, descripcion FROM catalogo_jornada_imagenes");
while ($row_img = $consulta_imagenes->fetch(PDO::FETCH_ASSOC)) {
    $jornada_imagenes_map[trim($row_img['codigo'])] = trim($row_img['descripcion']);
}

function processEmployeeAttendanceData($rango_fechas, $codigo_personal, $salario_mensual, $jornada_base_default, &$asistencia_por_empleado_y_fecha, $NombresCodigoLicenciaPermiso, $jornada_imagenes_map, $FechaDescripcionAsueto, $codigo_departamento_empleado, $initial_isss_days = 0) {
    global $dblink, $fecha_periodo_inicio, $fecha_periodo_fin;
    // --- INICIALIZACIÓN DE TOTALES PARA EL EMPLEADO ---
    $total_salario_devengado_empleado = 0;
    $total_salario_asuetos = 0;
    $total_monto_horas_extra_empleado = 0;
    $total_descuentos_empleado = 0;
    $total_trabajo_extra_empleado = 0;
    $total_monto_nocturnidad_empleado = 0;
    $total_nocturnidad_cantidad_empleado = 0;
    $total_horas_extra_cantidad = 0;
    $daily_attendance_details = [];
    $salario_diario = round($salario_mensual / 30, 4);
    $dias_isss_acumulados = 0; // Usaremos esta variable

    // --- LÓGICA PRE-CÁLCULO (Se mantienen igual) ---
    // Conteo de rachas de ISSS
    $isss_day_info = [];
    $temp_streak_counter = $initial_isss_days;
    $streak_dates = [];
    foreach ($rango_fechas as $fecha_actual) {
        $codigo_dia = buscarCodigoDeAsistencia($dblink, $codigo_personal, $fecha_actual, $asistencia_por_empleado_y_fecha);
        if ($codigo_dia === '4244444') {
            $temp_streak_counter++;
            $streak_dates[] = $fecha_actual;
        } else {
            if (!empty($streak_dates)) {
                 foreach ($streak_dates as $date_in_streak) {$isss_day_info[$date_in_streak] = ['total_length' => $temp_streak_counter];}
            }
            $temp_streak_counter = 0;
            $streak_dates = [];
        }
    }
    if (!empty($streak_dates)) {
        foreach ($streak_dates as $date_in_streak) {$isss_day_info[$date_in_streak] = ['total_length' => $temp_streak_counter];}
    }

    // Definición de códigos (Se mantienen igual)
    $non_contributory_codes_display_only = ['4344444'];
    $deduction_codes = ['41044444', '4444444', '4144444']; // Unificamos todos los que descuentan
    $asueto_worked_codes = ['41614444' => $salario_diario / 2, '41624444' => $salario_diario, '41634444' => $salario_diario + ($salario_diario / 2)];
    $trabajo_descanso_codes = ['41444144' => $salario_diario / 2, '41444244' => $salario_diario, '41444344' => $salario_diario + ($salario_diario / 2),
        // --- NUEVA LÓGICA PARA 11444144 ---
        // Paga 1 Día Base (en la lógica normal) + (1 Tanda + 4HE) = 2 días de salario adicional.
        '11444144' => $salario_diario * 2 // Monto adicional a sumar a la base.
    ];
    $trabajo_vacacion_codes = ['41241444' => $salario_diario / 2, '41242444' => $salario_diario, '41243444' => $salario_diario + ($salario_diario / 2)];
    $trabajo_descanso_asueto_codes = [
        '41744444' => $salario_diario, '41514444' => $salario_diario / 2, '41524444' => $salario_diario, '41534444' => $salario_diario + ($salario_diario / 2)];
    $nocturnidad_base_value = 0.57;
    $nocturnidad_codes_specific = [
        '2144445' => true, '1144445' => true, '1144425' => true, '11444450' => true, '2124445' => true, '41242445' => true, '41241445' => true
    ];
    $fixed_extra_codes = [
        '1144424' => $salario_diario, '3144444' => $salario_diario / 2
    ];
    $weekly_four_h_count = [];

  //  error_log("INICIANDO CÁLCULO PARA EMPLEADO: $codigo_personal");

    // --- BUCLE PRINCIPAL DE CÁLCULO DIARIO (REESTRUCTURADO) ---
    foreach ($rango_fechas as $fecha_actual) {
        $row_asistencia = $asistencia_por_empleado_y_fecha[$codigo_personal][$fecha_actual] ?? null;
        $CodigoJornadaTodas = $row_asistencia ? buscarCodigoDeAsistencia($dblink, $codigo_personal, $fecha_actual, $asistencia_por_empleado_y_fecha) : '';
        
        // --- INICIALIZACIÓN DE VARIABLES DIARIAS ---
        $salario_dia_actual = 0;
        $descuento_dia_actual = 0;
        $bono_dia_actual = 0; // Para asuetos, descansos, etc.
        $nocturnidad_dia_actual = 0;
        $es_dia_pagado_para_hora_extra = false;

        // --- 1. DETERMINAR EL ESTADO BASE DEL DÍA (PAGO, SIN PAGO O DESCUENTO) ---
        if ($row_asistencia) {
            // Regla de Media Jornada (4h)
            if ($CodigoJornadaTodas == '1144444') {
                $date_obj_media = new DateTime($fecha_actual);
                $week_start_date_media = ($date_obj_media->format('N') == 1) ? $date_obj_media->format('Y-m-d') : (clone $date_obj_media)->modify('last monday')->format('Y-m-d');
                $weekly_four_h_count[$week_start_date_media] = ($weekly_four_h_count[$week_start_date_media] ?? 0) + 1;
                $salario_dia_actual = ($weekly_four_h_count[$week_start_date_media] > 1) ? $salario_diario / 2 : $salario_diario;
                $es_dia_pagado_para_hora_extra = true;
            }
            // --- Lógica para CÓDIGO 4244444 (ISSS) ---
            else if ($CodigoJornadaTodas == '4244444') {
                // Incrementamos el contador. Este es el N° de día en la racha continua.
                $dias_isss_acumulados++; 

                // Regla: Pagar solo los días 1, 2 y 3 de la racha. Días 4 en adelante no se pagan.
                if ($dias_isss_acumulados === 3) {
                    $salario_dia_actual = $salario_diario * 3; // Pagar el día (Días 1, 2 y 3)
                } elseif($dias_isss_acumulados >= 1 && $dias_isss_acumulados < 3) {
                    // Día 1,: No pagar ni descontar
                    $salario_dia_actual = 0; 
                }
                else {
                    $salario_dia_actual = 0; // No pagar el día (Días 4 en adelante)
                }

                $es_dia_pagado_para_hora_extra = false;
                $monto_horas_extra_dia_actual = 0;
                $descuento_dia_actual = 0;
                // No se necesitan horas de jornada, ya que es incapacidad.
            }
            // Regla de Códigos que descuentan el día
            else if (in_array($CodigoJornadaTodas, $deduction_codes)) {
                $descuento_dia_actual = $salario_diario;
            }
            // Regla de Códigos que no pagan ni descuentan
            else if (in_array($CodigoJornadaTodas, $non_contributory_codes_display_only)) {
                $salario_dia_actual = 0;
            }
            // Regla de Licencias Parciales
            else if (trim($row_asistencia['codigo_jornada'] ?? '') == '4') {
                 $licencia_info = $NombresCodigoLicenciaPermiso[$row_asistencia['codigo_tipo_licencia']] ?? ['horas' => $jornada_base_default];
                 $valor_hora_normal = ($jornada_base_default > 0) ? ($salario_diario / $jornada_base_default) : 0;
                 $salario_dia_actual = $valor_hora_normal * (float)($licencia_info['horas'] ?? 0);
                 $es_dia_pagado_para_hora_extra = true;
            }
            // Regla por defecto: Día normal pagado
            else {
                $salario_dia_actual = $salario_diario;
                $es_dia_pagado_para_hora_extra = true;
            }
        } else { // No hay registro de asistencia
            if (isset($FechaDescripcionAsueto[$fecha_actual])) { // Es asueto no trabajado
                $salario_dia_actual = $salario_diario;
                $total_salario_asuetos += $salario_diario; // Se suma aquí como informativo, pero el pago ya está en el salario
                $CodigoJornadaTodas = 'AS';
            } else { // Es una falta genérica
                $descuento_dia_actual = $salario_diario;
                $CodigoJornadaTodas = 'FALTA_GENERICA';
            }
        }

        // --- 2. CALCULAR BONIFICACIONES ADICIONALES (EXTRAS) ---
        // Estas bonificaciones se suman al salario base del día
        if ($row_asistencia) {
            if (isset($asueto_worked_codes[$CodigoJornadaTodas])) {
                $bono_dia_actual += $asueto_worked_codes[$CodigoJornadaTodas];
            }
            if (isset($trabajo_descanso_codes[$CodigoJornadaTodas])) {
                $bono_dia_actual += $trabajo_descanso_codes[$CodigoJornadaTodas];
            }
            if (isset($trabajo_vacacion_codes[$CodigoJornadaTodas])) {
                $bono_dia_actual += $trabajo_vacacion_codes[$CodigoJornadaTodas];
            }
            if (isset($trabajo_descanso_asueto_codes[$CodigoJornadaTodas])) {
                if ($CodigoJornadaTodas != '41744444') { // Este código paga doble salario, no bono.
                     $bono_dia_actual += $trabajo_descanso_asueto_codes[$CodigoJornadaTodas];
                }
            }
            if (isset($fixed_extra_codes[$CodigoJornadaTodas])) {
                $bono_dia_actual += $fixed_extra_codes[$CodigoJornadaTodas];
            }
            
            // Cálculo de Nocturnidad
            if (isset($nocturnidad_codes_specific[$CodigoJornadaTodas]) && ($codigo_departamento_empleado == '08' || $codigo_departamento_empleado == '09')) {
                $nocturnidad_dia_actual = $nocturnidad_base_value;
                $total_nocturnidad_cantidad_empleado++;
            }
        }

        // --- 3. CÁLCULO DE HORAS EXTRA REGISTRADAS ---
        $monto_horas_extra_dia_actual = 0;
        $horas_extra_registradas = (float)($row_asistencia['hora_extra'] ?? 0);

        // Las horas extra solo se pagan si el día fue considerado "pagado"
        if ($es_dia_pagado_para_hora_extra && $horas_extra_registradas > 0) {
            $factor_hora_extra = ($codigo_departamento_empleado == '02' || $codigo_departamento_empleado == '03') ? 2 : 1;
            $valor_hora_normal_base = ($jornada_base_default > 0) ? ($salario_diario / $jornada_base_default) : 0;
            $monto_horas_extra_dia_actual = $horas_extra_registradas * ($valor_hora_normal_base * $factor_hora_extra);
            $total_horas_extra_cantidad += $horas_extra_registradas;

            if (strpos($CodigoJornadaTodas, '_HE') === false) {
                 $CodigoJornadaTodas .= str_replace('.', '', (string)$horas_extra_registradas);
            }
        }

        if ($CodigoJornadaTodas !== '4244444' && $row_asistencia) {
            $dias_isss_acumulados = 0;
        }
        // --- 4. SUMAS FINALES PARA EL DÍA ---
        $total_salario_devengado_empleado += $salario_dia_actual;
        $total_descuentos_empleado += $descuento_dia_actual;
        $total_trabajo_extra_empleado += $bono_dia_actual;
        $total_monto_nocturnidad_empleado += $nocturnidad_dia_actual;
        $total_monto_horas_extra_empleado += $monto_horas_extra_dia_actual;

        
        // --- NUEVO BLOQUE DE DEPURACIÓN DIARIA ---
        $log_diario = "Día: $fecha_actual | Código: $CodigoJornadaTodas | Devengado: " . round($salario_dia_actual, 2) . " | Bono: " . round($bono_dia_actual, 2) . " | Noct: " . round($nocturnidad_dia_actual, 2) . " | H.Extra: " . round($monto_horas_extra_dia_actual, 2);
       //error_log($log_diario);

        // Guardar detalles para el PDF
        $image_filename = $jornada_imagenes_map[trim($CodigoJornadaTodas)] ?? '';
        $daily_attendance_details[$fecha_actual] = ['image_filename' => $image_filename];
    }
    
    // --- LÓGICA POST-CÁLCULO (DEDUCCIÓN 7MO DÍA - Se mantiene igual) ---
    $total_deduccion_7mo = 0;
    $deductible_codes = ['41044444', '4444444', 'FALTA_GENERICA', '4144444']; 
    $flexible_week_depts = ['02', '03', '04', '06', '08', '09'];
    $is_flexible_week_employee = in_array($codigo_departamento_empleado, $flexible_week_depts);
    $semanas_a_revisar = [];

    if ($is_flexible_week_employee) {
        $rest_day_codes = ['41344444', '41444144', '41444244', '41444344'];
        $dias_de_descanso = [];
        $fecha_busqueda = new DateTime($fecha_periodo_inicio);
        for ($i = 0; $i < 15; $i++) {
            $fecha_busqueda->modify('-1 day');
            $fecha_str = $fecha_busqueda->format('Y-m-d');
            $codigo_del_dia = buscarCodigoDeAsistencia($dblink, $codigo_personal, $fecha_str, $asistencia_por_empleado_y_fecha);
            if (in_array($codigo_del_dia, $rest_day_codes)) {
                $dias_de_descanso[] = $fecha_str;
                break;
            }
        }
        foreach ($rango_fechas as $fecha_actual) {
            $codigo_dia = buscarCodigoDeAsistencia($dblink, $codigo_personal, $fecha_actual, $asistencia_por_empleado_y_fecha);
            if (in_array($codigo_dia, $rest_day_codes)) { $dias_de_descanso[] = $fecha_actual; }
        }
        sort($dias_de_descanso);
        for ($i = 0; $i < count($dias_de_descanso) - 1; $i++) {
            $start_date = (new DateTime($dias_de_descanso[$i]))->modify('+1 day')->format('Y-m-d');
            $end_date = $dias_de_descanso[$i + 1];
            $semanas_a_revisar[] = ['start' => $start_date, 'end' => $end_date];
        }
        if (!empty($dias_de_descanso)) {
            $ultimo_descanso = end($dias_de_descanso);
            $start_date_ultima_semana = (new DateTime($ultimo_descanso))->modify('+1 day');
            if ($start_date_ultima_semana->format('Y-m-d') <= $fecha_periodo_fin) {
                $end_date_ultima_semana = (clone $start_date_ultima_semana)->modify('+6 days');
                $semanas_a_revisar[] = ['start' => $start_date_ultima_semana->format('Y-m-d'), 'end' => $end_date_ultima_semana->format('Y-m-d')];
            }
        }
    } else {
        $start_range = new DateTime($fecha_periodo_inicio);
        $end_range = new DateTime($fecha_periodo_fin);
        if ($start_range->format('N') != 1) { $start_range->modify('last monday'); }
        while ($start_range <= $end_range) {
            $end_of_week = (clone $start_range)->modify('next sunday');
            $semanas_a_revisar[] = ['start' => $start_range->format('Y-m-d'), 'end' => $end_of_week->format('Y-m-d')];
            $start_range->modify('next monday');
        }
    }

  foreach ($semanas_a_revisar as $semana) {
   //     error_log("====== EMPLEADO: $codigo_personal ======");
     //   error_log("🔍 Revisando semana del " . $semana['start'] . " al " . $semana['end']);

        // Omitir semanas que ni siquiera han comenzado en el período de pago
        if (new DateTime($semana['start']) > new DateTime($fecha_periodo_fin)) {
       //     error_log("  -> Semana omitida (comienza después del período de pago).");
            continue;
        }

        $falta_en_la_semana = false;
        $period = new DatePeriod(new DateTime($semana['start']), new DateInterval('P1D'), (new DateTime($semana['end']))->modify('+1 day'));
        
        foreach ($period as $dia) {
            $fecha_dia_str = $dia->format('Y-m-d');

            // Solo nos interesan los días que están dentro de la quincena actual para encontrar faltas
            if (new DateTime($fecha_dia_str) > new DateTime($fecha_periodo_fin)) {
                continue;
            }

            $codigo_del_dia = buscarCodigoDeAsistencia($dblink, $codigo_personal, $fecha_dia_str, $asistencia_por_empleado_y_fecha);
            
            if (empty($codigo_del_dia) && !isset($FechaDescripcionAsueto[$fecha_dia_str])) {
                $codigo_del_dia = 'FALTA_GENERICA';
            }

          //  error_log("  -> Día: $fecha_dia_str, Código: $codigo_del_dia");

            if (in_array($codigo_del_dia, $deductible_codes)) {
                $falta_en_la_semana = true;
             //   error_log("    ❌ ¡FALTA O CASTIGO ENCONTRADO! El código '$codigo_del_dia' activa el descuento.");
                break; 
            }
        }

        // === INICIO DE LA CORRECCIÓN CLAVE ===
        // Verificamos si hubo falta Y si la semana TERMINÓ dentro de la quincena
        if ($falta_en_la_semana) {
            if (new DateTime($semana['end']) <= new DateTime($fecha_periodo_fin)) {
                $deptos_con_descuento_7mo = ['02', '03', '06'];
                if (in_array($codigo_departamento_empleado, $deptos_con_descuento_7mo)) {
                    $total_deduccion_7mo += $salario_diario;
                   // error_log("  💰 ¡DESCUENTO 7MO APLICADO! La semana terminó en la quincena. Total 7mo ahora: $" . round($total_deduccion_7mo, 2));
                } else {
                  //  error_log("  -> Falta encontrada, pero el depto '$codigo_departamento_empleado' no aplica a descuento de 7mo día.");
                }
            } else {
                // Si la semana no ha terminado, lo indicamos en el log y no hacemos nada.
               // error_log("  ⏳ SÉPTIMO PENDIENTE. La semana termina el " . $semana['end'] . ", fuera de esta quincena. No se descuenta el 7mo ahora.");
            }
        } else {
         //   error_log("  ✅ Semana sin faltas. No se aplica descuento de 7mo.");
        }
        // === FIN DE LA CORRECCIÓN CLAVE ===
    }
    $total_descuentos_empleado = $total_deduccion_7mo;

    // --- CÁLCULO DE TOTALES FINALES (Se mantienen igual) ---
    $total_extra_empleado = $total_salario_asuetos + $total_monto_horas_extra_empleado + $total_trabajo_extra_empleado + $total_monto_nocturnidad_empleado;
    $total_salario_gross_empleado = $total_salario_devengado_empleado + $total_extra_empleado;
    $salario_liquido_final_empleado = $total_salario_gross_empleado - $total_descuentos_empleado;
/*
    error_log(" "); error_log("💰====== RESUMEN FINAL PARA EMPLEADO: $codigo_personal ======");
    error_log("  (+) Salario Devengado (Suma de días pagados): $" . round($total_salario_devengado_empleado, 2));
    error_log("  (+) [EXTRAS] Asuetos($" . round($total_salario_asuetos, 2) . ") + H.Extra($" . round($total_monto_horas_extra_empleado, 2) . ") + Trab.Extra($" . round($total_trabajo_extra_empleado, 2) . ") + Noct.($" . round($total_monto_nocturnidad_empleado, 2) . ") = Total Extras: $" . round($total_extra_empleado, 2));
    error_log("  (=) Salario Bruto (Devengado + Extras): $" . round($total_salario_gross_empleado, 2));
    error_log("  (-) Descuentos (Solo Séptimos Días): $" . round($total_descuentos_empleado, 2));
    error_log("  💵 (=) SALARIO LÍQUIDO FINAL (Bruto - Descuentos): $" . round($salario_liquido_final_empleado, 2));
    error_log("====================================================="); error_log(" ");
*/

    return [
        'total_salario_devengado' => $total_salario_devengado_empleado, 'total_salario_asuetos' => $total_salario_asuetos, 'total_monto_horas_extra' => $total_monto_horas_extra_empleado, 'total_descuentos' => $total_descuentos_empleado, 'salario_liquido_final' => $salario_liquido_final_empleado, 'total_extra_general' => $total_extra_empleado, 'total_horas_extra_cantidad' => $total_horas_extra_cantidad, 'total_salario_gross' => $total_salario_gross_empleado, 'daily_details' => $daily_attendance_details, 'total_trabajo_extra_empleado' => $total_trabajo_extra_empleado, 'total_monto_nocturnidad' => $total_monto_nocturnidad_empleado, 'total_nocturnidad_cantidad' => $total_nocturnidad_cantidad_empleado
    ];
}

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
        global $fecha_periodo_inicio, $fecha_periodo_fin, $departamentoEmpresaTexto, $RutaText, $quincena, $rango_fechas, $DepartamentoEmpresa;
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
        if (!empty($RutaText) && $RutaText != '00' && $RutaText != 'Seleccionar...' && $departamentoEmpresaTexto == 'Motorista') { // Si se seleccionó una ruta específica
            $reporte_ruta_display .= utf8_decode(' (Ruta: ') . utf8_decode($RutaText) . utf8_decode(')');
        }else{
            $reporte_ruta_display = $departamentoEmpresaTexto;
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
        $daily_col_width = 7.0; // Ajustado a 7.0mm

        // Anchos para las COLUMNAS FINANCIERAS PRINCIPALES: Salarios, Asuetos, EXTRA, Nocturnidad, Hora Extra, Total Extra, Salario Líquido
        $w_financial_fixed = [10, 8, 10, 10, 17, 14, 15]; // Suma a 84mm. Ajustados.

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
        if ($DepartamentoEmpresa == '08' || $DepartamentoEmpresa == '09') {
        $this->Cell($w_financial_fixed[3], $half_header_height, utf8_decode('Nocturnidad'), 1, 0, 'C', true); // Nueva columna Nocturnidad
        }
        $this->SetFillColor(180, 200, 230); // Color más oscuro para Hora Extra
        $this->Cell($w_financial_fixed[4], $half_header_height, utf8_decode('Hora Extra'), 1, 0, 'C', true); // Encabezado padre de Hora Extra
        $this->SetFillColor(200, 220, 255); // Resetear color
        $this->Cell($w_financial_fixed[5], $half_header_height, 'Total', 1, 0, 'C', true); // Encabezado padre de Total Extra
        $this->SetFillColor(180, 200, 230); // Color más oscuro para Salario Liquido (para que se vea el fondo oscuro)
        $this->Cell($w_financial_fixed[6], $half_header_height, 'Salario', 1, 0, 'C', true); // Encabezado padre de Salario Liquido

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
        if ($DepartamentoEmpresa == '08' || $DepartamentoEmpresa == '09') {
        $this->Cell($w_financial_fixed[3]/2, $half_header_height, 'C', 1, 0, 'C', true); // Sub-encabezado C de Nocturnidad
        $this->Cell($w_financial_fixed[3]/2, $half_header_height, 'V', 1, 0, 'C', true); // Sub-encabezado V de Nocturnidad
        }
        $this->Cell($w_financial_fixed[4]/2, $half_header_height, 'C', 1, 0, 'C', true); // Sub-encabezado C de Hora Extra
        $this->Cell($w_financial_fixed[4]/2, $half_header_height, 'V', 1, 0, 'C', true); // Sub-encabezado V de Hora Extra
        $this->SetFillColor(180, 200, 230); // Color más oscuro para Total Extra
        $this->Cell($w_financial_fixed[5], $half_header_height, 'Extra', 1, 0, 'C', true); // Sub-encabezado Extra de Total Extra
        $this->SetFillColor(200, 220, 255); // Resetear color
        $this->Cell($w_financial_fixed[6], $half_header_height, 'Liquido', 1, 0, 'C', true); // Sub-encabezado Liquido de Salario Liquido

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
$daily_col_width = 7.0; // Ajustado a 7.0mm
// Anchos para las COLUMNAS FINANCIERAS PRINCIPALES: Salarios, Asuetos, EXTRA, Nocturnidad, Hora Extra, Total Extra, Salario Líquido
$w_financial_fixed = [10, 8, 10, 10, 17, 14, 15]; // Suma a 84mm. Ajustados.

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

   // <<< INICIA NUEVO BLOQUE DE CÓDIGO >>>
    // Criterio 1: Revisar si un evento deducible (falta/castigo) ocurrió en la
    // parte de la semana que corresponde a la quincena anterior.
    $deductible_events_carry_over = [];
    $fecha_inicio_dt = new DateTime($fecha_periodo_inicio);
    $day_of_week_num = (int)$fecha_inicio_dt->format('N'); // 1 = Lunes, 7 = Domingo

    // Si la quincena no empieza un lunes, revisamos los días previos de esa misma semana.
    if ($day_of_week_num > 1) {
        $week_start_dt = (clone $fecha_inicio_dt)->modify('last monday');
        $week_start_str = $week_start_dt->format('Y-m-d');
        $check_date_dt = (clone $fecha_inicio_dt)->modify('-1 day');

         // Consultamos la BD para los días anteriores
         while ($check_date_dt >= $week_start_dt) {
            $fecha_str = $check_date_dt->format('Y-m-d');

            // 1. Preparamos y ejecutamos la consulta para obtener los códigos de asistencia del día.
            $stmt_check = $dblink->prepare("
                SELECT pa.codigo_jornada, pa.codigo_tipo_licencia, pa.codigo_jornada_asueto, 
                       pa.codigo_jornada_vacaciones, pa.codigo_jornada_descanso, 
                       pa.codigo_jornada_e_4h, pa.codigo_jornada_nocturna
                FROM personal_asistencia pa
                WHERE pa.codigo_personal = :codigo AND pa.fecha = :fecha
            ");
            $stmt_check->bindParam(':codigo', $codigo_personal);
            $stmt_check->bindParam(':fecha', $fecha_str);
            $stmt_check->execute();
            $asistencia_anterior = $stmt_check->fetch(PDO::FETCH_ASSOC);

            $codigo_anterior_combinado = '';
            if ($asistencia_anterior) {
                // 2. Concatenamos todos los campos de código en una sola cadena.
                $codigo_anterior_combinado = 
                    trim($asistencia_anterior['codigo_jornada'] ?? '') .
                    trim($asistencia_anterior['codigo_tipo_licencia'] ?? '') .
                    trim($asistencia_anterior['codigo_jornada_asueto'] ?? '') .
                    trim($asistencia_anterior['codigo_jornada_vacaciones'] ?? '') .
                    trim($asistencia_anterior['codigo_jornada_descanso'] ?? '') .
                    trim($asistencia_anterior['codigo_jornada_e_4h'] ?? '') .
                    trim($asistencia_anterior['codigo_jornada_nocturna'] ?? '');
            }

            // 3. Verificamos si el código combinado es un Castigo ('41044444') o una Falta ('4444444').
            $deductible_codes = ['41044444', '4444444'];
            if (in_array(trim($codigo_anterior_combinado), $deductible_codes)) {
                // 4. Si hay coincidencia, marcamos la semana como que tiene un evento deducible.
                $deductible_events_carry_over[$week_start_str] = [
                    'has_deductible_event' => true,
                    'has_descanso' => false,
                    'descanso_date' => null,
                    'deducted_7mo' => false,
                    'four_h_count' => 0 // Se incluye para mantener la estructura consistente
                ];
                // 5. Rompemos el bucle porque ya encontramos la falta; no necesitamos seguir buscando hacia atrás.
                break;
            }
            
            // Pasamos al día anterior.
            $check_date_dt->modify('-1 day');
        }
    }
    // <<< FINALIZA NUEVO BLOQUE DE CÓDIGO >>>

        // --- LÓGICA DE ARRASTRE PARA ISSS (CORREGIDA Y ÚNICA) ---
        $carry_over_isss_days = 0;
        $fecha_busqueda_isss = new DateTime($fecha_periodo_inicio);
        for ($k=0; $k<15; $k++) { // Buscamos más atrás por si acaso
            $fecha_busqueda_isss->modify('-1 day');
            $codigo_anterior = buscarCodigoDeAsistencia($dblink, $codigo_personal, $fecha_busqueda_isss->format('Y-m-d'), $asistencia_por_empleado_y_fecha);
            if ($codigo_anterior === '4244444') {
                $carry_over_isss_days++;
            } else {
                break; // Se rompe la racha, dejamos de contar
            }
        }

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
        $codigo_departamento_empleado, // Pass department code
        $carry_over_isss_days // <<< MODIFICADO: Se pasa el nuevo parámetro
    );

    // Nuevas variables para los totales (usando el operador null coalescing para evitar "Undefined index" si la clave falta)
    // MODIFICACIÓN: Todos los totales monetarios se mantienen con dos decimales.
    $total_salarios_a_mostrar = round($results['total_salario_devengado'] ?? 0, 2);
    $total_salario_asuetos_a_mostrar = round($results['total_salario_asuetos'] ?? 0, 2); 
    $total_monto_horas_extra_a_mostrar = round($results['total_monto_horas_extra'] ?? 0, 2); 
    $total_extra_general_a_mostrar = $results['total_extra_general'] ?? 0; 
    $total_salario_gross_a_mostrar = round($results['total_salario_gross'] ?? 0, 2); 
    $total_horas_extra_cantidad_a_mostrar = $results['total_horas_extra_cantidad'] ?? 0; // Cantidad, no monetario
    $salario_liquido_final_a_mostrar = round($results['salario_liquido_final'] ?? 0, 2); 
    $total_trabajo_extra_empleado_a_mostrar = round($results['total_trabajo_extra_empleado'] ?? 0, 2); 
    $total_monto_nocturnidad_a_mostrar = round($results['total_monto_nocturnidad'] ?? 0, 2); 
    $total_nocturnidad_cantidad_a_mostrar = $results['total_nocturnidad_cantidad'] ?? 0; // Cantidad, no monetario

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

    // IMPRIMIR COLUMNAS FIJAS FINALES (Salarios, Asuetos, EXTRA, Nocturnidad C:V, Hora Extra C:V, Total Extra, Salario Líquido)
    // Determinar el color de fondo para las celdas de datos financieros
    // Usar los colores definidos previamente para las filas alternas
    $pdf->SetFillColor($current_row_fill_color[0], $current_row_fill_color[1], $current_row_fill_color[2]); 

    // Helper para formatear números o dejar vacío si es cero
    // MODIFICACIÓN: Se usa $decimals para controlar el redondeo en la presentación
    $format_num = function($value, $decimals = 2) {
        return ($value == 0) ? '' : number_format($value, $decimals, '.', ',');
    };

    // Salarios (ahora con 2 decimales)
    $pdf->Cell($w_financial_fixed[0], 6, $format_num($total_salarios_a_mostrar, 2), 1, 0, 'R', true); 
    
    // Asuetos (mantiene 2 decimales)
    $pdf->Cell($w_financial_fixed[1], 6, $format_num($total_salario_asuetos_a_mostrar, 2), 1, 0, 'R', true); 
    
    // EXTRA (Trabajo Descanso y Trabajo Vacación - mantiene 2 decimales)
    $pdf->Cell($w_financial_fixed[2], 6, $format_num($total_trabajo_extra_empleado_a_mostrar, 2), 1, 0, 'R', true); 

    // Columna de NOCTURNIDAD (C:V) - SOLO PARA DEPARTAMENTOS 08 Y 09
    if ($DepartamentoEmpresa == '08' || $DepartamentoEmpresa == '09') {
        $noct_display_string_C = $format_num($total_nocturnidad_cantidad_a_mostrar, 0); // Cantidad entera
        $noct_display_string_V = $format_num($total_monto_nocturnidad_a_mostrar, 2); // Valor con 2 decimales
        $pdf->Cell($w_financial_fixed[3]/2, 6, $noct_display_string_C, 1, 0, 'C', true); // Celda C de Nocturnidad
        $pdf->Cell($w_financial_fixed[3]/2, 6, $noct_display_string_V, 1, 0, 'C', true); // Celda V de Nocturnidad
    } else {
        // Si no es departamento 08 o 09, estas celdas están vacías
       // $pdf->Cell($w_financial_fixed[3]/2, 6, '', 1, 0, 'C', true); // Celda C de Nocturnidad vacía
        //$pdf->Cell($w_financial_fixed[3]/2, 6, '', 1, 0, 'C', true); // Celda V de Nocturnidad vacía
    }

    // Columna de HORA EXTRA (C:V)
    $he_display_string_C = $format_num($total_horas_extra_cantidad_a_mostrar, 0); // Cantidad entera
    $he_display_string_V = $format_num($total_monto_horas_extra_a_mostrar, 2); // Valor con 2 decimales
    $pdf->Cell($w_financial_fixed[4]/2, 6, $he_display_string_C, 1, 0, 'C', true); // Celda C de Hora Extra
    $pdf->Cell($w_financial_fixed[4]/2, 6, $he_display_string_V, 1, 0, 'C', true); 

    // Total Extra (TE) (ahora con 2 decimales)
    $pdf->Cell($w_financial_fixed[5], 6, $format_num($total_extra_general_a_mostrar, 2), 1, 0, 'R', true); 
    
    // Salario Liquido (ahora con 2 decimales)
    $pdf->Cell($w_financial_fixed[6], 6, $format_num($salario_liquido_final_a_mostrar, 2), 1, 1, 'R', true); 
    
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
// archivo funcionando correctamente.
?>