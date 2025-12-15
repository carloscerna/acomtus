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

// --- FUNCIÓN DE DEPURACIÓN (Borrar o comentar al terminar) ---
// --- FUNCIÓN DE DEPURACIÓN FINANCIERA (Actualizada) ---
function debugCalculoSeptimoDia($dblink, $codigo_personal, $codigo_depto, $fecha_periodo_inicio, $fecha_periodo_fin, $asistencia_por_empleado_y_fecha, $FechaDescripcionAsueto, $deductible_events_carry_over, $salario_mensual) {
    
    // Calculamos el valor real del día
    $salario_diario_real = $salario_mensual / 30;

    echo "<div style='background:#fff; border:2px solid #333; padding:15px; margin:10px; font-family:monospace; color:#000;'>";
    echo "<h3 style='margin-top:0'>AUDITORÍA DE DESCUENTOS: Empleado $codigo_personal</h3>";
    echo "<strong>Depto:</strong> $codigo_depto | <strong>Rango:</strong> $fecha_periodo_inicio al $fecha_periodo_fin<br>";
    echo "<strong>Salario Mensual:</strong> $" . number_format($salario_mensual, 2) . "<br>";
    echo "<strong>Valor Día (Mensual/30):</strong> <span style='background:yellow'>$" . number_format($salario_diario_real, 4) . "</span><br><hr>";

    // Acumuladores de Dinero
    $dinero_descontado_por_faltas_directas = 0; // Días no trabajados
    $dinero_descontado_por_septimos = 0;        // Penalización 7mo
    
    // Códigos que descuentan
    $deductible_codes = ['41044444', '4444444', 'FALTA_GENERICA', '4144444']; 
    
    // Deptos Flexibles
    $flexible_week_depts = ['02', '03', '04', '06', '08', '09'];
    $is_flexible_week_employee = in_array($codigo_depto, $flexible_week_depts);
    
    $semanas_a_revisar = [];

    // --- 1. RECONSTRUCCIÓN DE SEMANAS (Igual a la lógica principal) ---
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
        
        $period = new DatePeriod(new DateTime($fecha_periodo_inicio), new DateInterval('P1D'), (new DateTime($fecha_periodo_fin))->modify('+1 day'));
        foreach ($period as $date) {
            $fecha_actual = $date->format('Y-m-d');
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

    // --- 2. ANÁLISIS ---
    $indice_semana = 0;
    foreach ($semanas_a_revisar as $semana) {
        if (new DateTime($semana['start']) > new DateTime($fecha_periodo_fin)) continue;

        echo "<strong>Semana #$indice_semana:</strong> " . $semana['start'] . " al " . $semana['end'] . "<br>";
        
        $falta_en_la_semana = false;
        
        // CARRY OVER
        if ($indice_semana === 0) {
            foreach ($deductible_events_carry_over as $k => $carry_evt) {
                if ($carry_evt['has_deductible_event']) {
                    $falta_en_la_semana = true;
                    echo "&nbsp;&nbsp;<span style='color:orange'>[!] Arrastra falta previa de la semana ($k). Aplica para 7mo.</span><br>";
                    break; 
                }
            }
        }

        // RECORRIDO DE DÍAS (Aquí calculamos el dinero directo de la falta)
        $period = new DatePeriod(new DateTime($semana['start']), new DateInterval('P1D'), (new DateTime($semana['end']))->modify('+1 day'));
        foreach ($period as $dia) {
            $fecha_dia_str = $dia->format('Y-m-d');
            
            // Solo contamos dinero si está DENTRO de la quincena actual de pago
            if ($fecha_dia_str > $fecha_periodo_fin || $fecha_dia_str < $fecha_periodo_inicio) {
                // Si el día está fuera de rango, no suma descuento DIRECTO al pago actual,
                // PERO si es falta, sí cuenta para perder el 7mo.
                
                // Chequeo rápido solo para marcar la semana
                $codigo_del_dia = buscarCodigoDeAsistencia($dblink, $codigo_personal, $fecha_dia_str, $asistencia_por_empleado_y_fecha);
                if (in_array($codigo_del_dia, $deductible_codes)) {
                    $falta_en_la_semana = true;
                }
                continue; 
            }

            $codigo_del_dia = buscarCodigoDeAsistencia($dblink, $codigo_personal, $fecha_dia_str, $asistencia_por_empleado_y_fecha);
            
            if (empty($codigo_del_dia) && !isset($FechaDescripcionAsueto[$fecha_dia_str])) {
                $codigo_del_dia = 'FALTA_GENERICA';
            }

            if (in_array($codigo_del_dia, $deductible_codes)) {
                $falta_en_la_semana = true;
                $dinero_descontado_por_faltas_directas += $salario_diario_real; // Sumamos $$$
                echo "&nbsp;&nbsp;<span style='color:red'>[X] Día $fecha_dia_str: FALTA ($codigo_del_dia). Se descuenta: $" . number_format($salario_diario_real, 2) . "</span><br>";
            } else {
               // echo "&nbsp;&nbsp;[OK] Día $fecha_dia_str: Asistió ($codigo_del_dia)<br>";
            }
        }

        // CALCULO DEL SÉPTIMO
        if ($falta_en_la_semana) {
            if (new DateTime($semana['end']) <= new DateTime($fecha_periodo_fin)) {
                $deptos_con_descuento_7mo = ['02', '03', '06'];
                if (in_array($codigo_depto, $deptos_con_descuento_7mo)) {
                    $dinero_descontado_por_septimos += $salario_diario_real; // Sumamos $$$
                    echo "&nbsp;&nbsp;<strong style='background:#ffcccc; color:red'>[7mo] ¡PERDIDA DE SÉPTIMO! Se descuenta adicional: $" . number_format($salario_diario_real, 2) . "</strong><br>";
                } else {
                    echo "&nbsp;&nbsp;[INFO] Tiene falta pero Depto $codigo_depto no descuenta 7mo.<br>";
                }
            } else {
                echo "&nbsp;&nbsp;[PENDIENTE] La semana termina en la próxima quincena. El 7mo se descontará allá.<br>";
            }
        }
        echo "<br>";
        $indice_semana++;
    }

    $total_descuento_proyectado = $dinero_descontado_por_faltas_directas + $dinero_descontado_por_septimos;

    echo "<hr><table border='1' cellpadding='5' style='border-collapse:collapse; width:100%'>";
    echo "<tr><th align='left'>Concepto</th><th align='right'>Monto</th></tr>";
    echo "<tr><td>Descuento por Días no trabajados (Faltas)</td><td align='right'>$ " . number_format($dinero_descontado_por_faltas_directas, 2) . "</td></tr>";
    echo "<tr><td>Descuento por Séptimos Días (Penalización)</td><td align='right'>$ " . number_format($dinero_descontado_por_septimos, 2) . "</td></tr>";
    echo "<tr style='background:#ddd; font-weight:bold;'><td>TOTAL DESCUENTO A APLICAR</td><td align='right'>$ " . number_format($total_descuento_proyectado, 2) . "</td></tr>";
    echo "</table>";
    echo "</div>";
}

// --- FUNCIÓN OFICIAL CORREGIDA ---
// Devuelve ÚNICAMENTE el valor monetario del Séptimo Día.
function calcularSeptimoDia($dblink, $codigo_personal, $codigo_depto, $fecha_periodo_inicio, $fecha_periodo_fin, $asistencia_por_empleado_y_fecha, $FechaDescripcionAsueto, $deductible_events_carry_over, $salario_mensual) {
    
    $salario_diario_real = $salario_mensual / 30;
    $dinero_descontado_por_septimos = 0; 
    
    $deductible_codes = ['41044444', '4444444', 'FALTA_GENERICA', '4144444']; 
    $flexible_week_depts = ['02', '03', '04', '06', '08', '09'];
    $is_flexible_week_employee = in_array($codigo_depto, $flexible_week_depts);
    
    $semanas_a_revisar = [];

    // 1. RECONSTRUCCIÓN DE SEMANAS
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
        $period = new DatePeriod(new DateTime($fecha_periodo_inicio), new DateInterval('P1D'), (new DateTime($fecha_periodo_fin))->modify('+1 day'));
        foreach ($period as $date) {
            $fecha_actual = $date->format('Y-m-d');
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

    // 2. CÁLCULO
    $indice_semana = 0;
    foreach ($semanas_a_revisar as $semana) {
        if (new DateTime($semana['start']) > new DateTime($fecha_periodo_fin)) continue;
        $falta_en_la_semana = false;
        
        if ($indice_semana === 0) {
            foreach ($deductible_events_carry_over as $carry_evt) {
                if ($carry_evt['has_deductible_event']) {
                    $falta_en_la_semana = true;
                    break; 
                }
            }
        }

        $period = new DatePeriod(new DateTime($semana['start']), new DateInterval('P1D'), (new DateTime($semana['end']))->modify('+1 day'));
        foreach ($period as $dia) {
            $fecha_dia_str = $dia->format('Y-m-d');
            if ($fecha_dia_str > $fecha_periodo_fin) continue;
            $codigo_del_dia = buscarCodigoDeAsistencia($dblink, $codigo_personal, $fecha_dia_str, $asistencia_por_empleado_y_fecha);
            if (empty($codigo_del_dia) && !isset($FechaDescripcionAsueto[$fecha_dia_str])) {
                $codigo_del_dia = 'FALTA_GENERICA';
            }
            if (in_array($codigo_del_dia, $deductible_codes)) {
                $falta_en_la_semana = true;
                // No break, seguimos revisando por si hay más días (aunque para 7mo basta uno)
            }
        }

        if ($falta_en_la_semana) {
            if (new DateTime($semana['end']) <= new DateTime($fecha_periodo_fin)) {
                $deptos_con_descuento_7mo = ['02', '03', '06'];
                if (in_array($codigo_depto, $deptos_con_descuento_7mo)) {
                    $dinero_descontado_por_septimos += $salario_diario_real; 
                }
            }
        }
        $indice_semana++;
    }
    // IMPORTANTE: Solo devolvemos el séptimo
    return $dinero_descontado_por_septimos;
}


function dibujarCeldaAsistencia($pdf, $x, $y, $w, $h, $codigo, $link = '') {
    // --- 1. CONFIGURACIÓN BASE ---
    $borde_color = [50, 50, 200];     // Azul Borde
    $relleno_color = [255, 255, 255]; // Blanco Fondo
    
    // Configuración Central por defecto
    $fuente_actual = 'ZapfDingbats'; 
    $simbolo_central = 'l';           // Punto
    $texto_color = [0, 0, 0];         // Negro
    $tamano_fuente_central = 6;      // Tamaño estándar para el punto
    $ajuste_y_simbolo = 1;            

    // Configuración Esquinas
    $tamano_fuente_esquinas = 6.5;    
    $texto_sup_der = ''; 
    $texto_inf_der = ''; 
    $texto_inf_izq = ''; 

    // --- 2. ANÁLISIS DEL CÓDIGO (SWITCH MAESTRO) ---

    switch ($codigo) {
        // ---------------------------------------------------------
        // GRUPO 1: CELDAS VACÍAS O ERRORES
        // ---------------------------------------------------------
        case '': case null: case 'VACIO': 
        case '4144444': case '21444440': // Sin Jornada (Gris)
            $fuente_actual = 'Arial'; $simbolo_central = '0H'; 
            $relleno_color = [225, 225, 225]; // Gris Suave
            $tamano_fuente_central = 10; $ajuste_y_simbolo = 2;
            break;

        // ---------------------------------------------------------
        // GRUPO 2: FALTAS Y CASTIGOS (ROJO)
        // ---------------------------------------------------------
        case '4444444': case 'FALTA': case 'FALTA_GENERICA':
            $fuente_actual = 'Arial'; $simbolo_central = 'F';
            $texto_color = [200, 0, 0]; 
            $tamano_fuente_central = 10; $ajuste_y_simbolo = 3;
            break;
            
        case '41044444': // Castigo
            $fuente_actual = 'Arial'; $simbolo_central = 'C';
            $texto_color = [200, 0, 0]; 
            $tamano_fuente_central = 10; $ajuste_y_simbolo = 3;
            break;

        // ---------------------------------------------------------
        // GRUPO 3: DESCANSOS (VERDE)
        // ---------------------------------------------------------
        case '41344444': // Descanso Puro
             $fuente_actual = 'Arial'; $simbolo_central = 'D';
             $texto_color = [0, 128, 0]; 
             $tamano_fuente_central = 10; $ajuste_y_simbolo = 3;
             break;

        case '41444144': // TD 4H
        case '41444244': // TD 1T
        case '41444344': // TD 1.5T
        // --- NUEVOS: TRABAJO DESCANSO CON NOCTURNIDAD ---
        case '41444145': // TD 4H + N
        case '41444245': // TD 1T + N
        case '41444345': // TD 1.5T + N
                $fuente_actual = 'Arial'; $simbolo_central = 'TD';
                $texto_color = [0, 128, 0]; 
                $tamano_fuente_central = 10; $ajuste_y_simbolo = 3;
                
                // Cantidades a la derecha
                if ($codigo == '41444144' || $codigo == '41444145') $texto_inf_der = '4H';
                elseif ($codigo == '41444244' || $codigo == '41444245') $texto_inf_der = '1T';
                elseif ($codigo == '41444344' || $codigo == '41444345') $texto_inf_der = '1.5T';

                // Nocturnidad a la izquierda (Solo para los terminados en 5)
                if (in_array($codigo, ['41444145', '41444245', '41444345'])) {
                    $texto_inf_izq = 'N';
                }
                break;

        case '41744444': // Descanso Asueto (DA) - AMARILLO
            $fuente_actual = 'Arial'; $simbolo_central = 'DA';
            $relleno_color = [255, 255, 0]; $texto_color = [0, 128, 0]; 
            $tamano_fuente_central = 10; $ajuste_y_simbolo = 1;
            break;

        case '41544444': // TDA Genérico
        case '41514444': // TDA 4H
        case '41524444': // TDA 1T
        case '41534444': // TDA 1.5T
            $fuente_actual = 'Arial'; $simbolo_central = 'TDA';
            $relleno_color = [255, 255, 0]; $texto_color = [200, 0, 0]; 
            $tamano_fuente_central = 9; $ajuste_y_simbolo = 1;

            if ($codigo == '41514444') $texto_inf_der = '4H';
            elseif ($codigo == '41524444') $texto_inf_der = '1T';
            elseif ($codigo == '41534444') $texto_inf_der = '1.5T';
            break;

        // ---------------------------------------------------------
        // GRUPO 4: VACACIONES (VERDE)
        // ---------------------------------------------------------
        case '41144444': // V Puro
            $fuente_actual = 'Arial'; $simbolo_central = 'V';
            $texto_color = [0, 180, 60]; 
            $tamano_fuente_central = 10; $ajuste_y_simbolo = 3;
            break;

        case '41241444': // TV 4H
        case '41242444': // TV 1T
        case '41243444': // TV 1.5T
        case '41242445': // TV 1T + N
        case '41243445': // TV 4H + N
             $fuente_actual = 'Arial'; $simbolo_central = 'TV';
             $texto_color = [0, 180, 60];
             $tamano_fuente_central = 10; $ajuste_y_simbolo = 2;

             if ($codigo == '41241444' || $codigo == '41243445') $texto_inf_der = '4H';
             elseif ($codigo == '41242444' || $codigo == '41242445') $texto_inf_der = '1T';
             elseif ($codigo == '41243444') $texto_inf_der = '1.5T';
             
             // Nocturnidad explícita
             if (in_array($codigo, ['41242445', '41243445'])) $texto_inf_izq = 'N';
             break;

        case '41944444': // Vacación Descanso Asueto (VDA)
            $fuente_actual = 'Arial'; $simbolo_central = 'VDA';
            $relleno_color = [255, 255, 0]; $texto_color = [200, 0, 0]; 
            $tamano_fuente_central = 8; $ajuste_y_simbolo = 1;
            break;

        // ---------------------------------------------------------
        // GRUPO 5: ASUETOS (AMARILLO)
        // ---------------------------------------------------------
        case '41644444': // Asueto Puro
            $fuente_actual = 'Arial'; $simbolo_central = 'A';
            $relleno_color = [255, 255, 0]; $texto_color = [200, 0, 0]; 
            $tamano_fuente_central = 10; $ajuste_y_simbolo = 3;
            break;

            case '41614444': // TA 4H
                case '41624444': // TA 1T
                case '41634444': // TA 1.5T
                case '41944445': // TA 4H + N (Formato antiguo 19)
                case '41924445': // TA 1T + N (Formato antiguo 19)
                case '41934445': // TA 1.5T + N (Formato antiguo 19)
                // --- NUEVOS: ASUETO NOCTURNIDAD CON CÓDIGO 416 ---
                case '41614445': // TA 4H + N
                case '41624445': // TA 1T + N
                case '41634445': // TA 1.5T + N
                case '41644445': // TA + N (Sin duración específica)
                    $fuente_actual = 'Arial'; $simbolo_central = 'TA';
                    $relleno_color = [255, 255, 0]; // Amarillo
                    $texto_color = [200, 0, 0];     // Rojo
                    $tamano_fuente_central = 9; $ajuste_y_simbolo = 2; // Ajustado para que quepa bien
        
                    // Lógica de texto Derecho (Duración)
                    if (in_array($codigo, ['41614444', '41944445', '41614445'])) $texto_inf_der = '4H';
                    elseif (in_array($codigo, ['41624444', '41924445', '41624445'])) $texto_inf_der = '1T';
                    elseif (in_array($codigo, ['41634444', '41934445', '41634445'])) $texto_inf_der = '1.5T';
                    
                    // Lógica de texto Izquierdo (Nocturnidad)
                    if (in_array($codigo, ['41944445', '41924445', '41934445', '41614445', '41624445', '41634445', '41644445'])) {
                        $texto_inf_izq = 'N';
                    }
                    break;

        // ---------------------------------------------------------
        // GRUPO 6: PERMISOS (AZUL)
        // ---------------------------------------------------------
        case '4244444': // ISSS
            $fuente_actual = 'Arial'; $simbolo_central = 'ISSS';
            $texto_color = [0, 0, 200]; 
            $tamano_fuente_central = 6; $ajuste_y_simbolo = 1.5;
            break;
        case '4344444': // Permiso
            $fuente_actual = 'Arial'; $simbolo_central = 'PP';
            $texto_color = [0, 0, 200]; 
            $tamano_fuente_central = 9; $ajuste_y_simbolo = 3;
            break;

        // ---------------------------------------------------------
        // GRUPO 7: MEDIA TANDA Y COMPLEJOS (BLANCO)
        // ---------------------------------------------------------
        case '1144444':  // 4H sola
        case '1144424':  // 4H + 1T (Sin Noche)
        case '1144425':  // 4H + 1T + NOCHE (El difícil)
        case '11444344': // 4H + 1.5T
        case '11444144': // 4H + 4HE
        case '11444244': // 4H + 1T + 4HE
        case '11444243': // 4H + 1T + 3HE
        case '11444242': // 4H + 1T + 2HE
        case '11444241': // 4H + 1T + 1HE
            
            $fuente_actual = 'Arial'; 
            $simbolo_central = '4H';
            $tamano_fuente_central = 9; 
            $ajuste_y_simbolo = 2; 

            // Tanda Extra (Abajo Derecha)
            if ($codigo == '11444344') $texto_inf_der = '1.5T';
            elseif (in_array($codigo, ['1144424', '1144425', '11444244', '11444243', '11444242', '11444241'])) {
                $texto_inf_der = '1T';
            }

            // Horas Extras (Arriba Derecha)
            if ($codigo == '11444244' || $codigo == '11444144') $texto_sup_der = '4 HE';
            elseif ($codigo == '11444243') $texto_sup_der = '3 HE';
            elseif ($codigo == '11444242') $texto_sup_der = '2 HE';
            elseif ($codigo == '11444241') $texto_sup_der = '1 HE';

            // Nocturnidad especial (Abajo Izquierda)
            if ($codigo == '1144425') $texto_inf_izq = 'N';
            break;

        // --- UNA TANDA Y MEDIA (1.5T) ---
        case '3144444': 
        case '3144445': // Con Noche
            $fuente_actual = 'Arial'; $simbolo_central = '1.5T';
            $tamano_fuente_central = 9; $ajuste_y_simbolo = 2;
            if ($codigo == '3144445') $texto_inf_izq = 'N';
            break;

        // --- PUNTO / UNA TANDA (1T) ---
        case '21444444': // 4 HE
             $texto_sup_der = '4 HE'; break;
        case '21444443': 
             $texto_sup_der = '3 HE'; break;
        case '21444442': 
             $texto_sup_der = '2 HE'; break;
        case '21444441': 
             $texto_sup_der = '1 HE'; break;
        case '2144445': // 1T + Noche
             $texto_inf_izq = 'N'; break;

        default: break; // Punto por defecto (2144444)
    }

    // --- 3. LÓGICA FINAL Y DIBUJO ---
    
    // Si viene HE dinámico del array (_HE)
    if (strpos($codigo, '_HE') !== false) {
        $partes = explode('_HE', $codigo);
        $texto_sup_der = end($partes) . " HE";
    }

    // Nocturnidad Genérica para cualquier otro código terminado en 5
    // que NO esté en la lista de los que ya manejamos manualmente
    // Agrega los nuevos códigos a este array:
    $excepciones_nocturnidad = [
        '1144425', '41944445', '41924445', '41242445', '41243445', '3144445', '2144445', '41934445',
        // --- NUEVOS AGREGADOS ---
        '41444145', '41444245', '41444345', // TD Nocturnos
        '41614445', '41624445', '41634445', '41644445' // TA Nocturnos
    ];

    if (substr($codigo, -1) == '5' && !in_array($codigo, $excepciones_nocturnidad)) {
        // Si no tiene texto a la izquierda, ponemos la N ahí
        if(empty($texto_inf_izq)) $texto_inf_izq = 'N';
        // Si ya tiene texto a la izquierda, la ponemos a la derecha
        else $texto_inf_der = trim($texto_inf_der . " N");
    }

  // --- 4. DIBUJO FINAL (RENDERIZADO) ---

    // Fondo y Borde
    $pdf->SetFillColor($relleno_color[0], $relleno_color[1], $relleno_color[2]);
    $pdf->SetDrawColor($borde_color[0], $borde_color[1], $borde_color[2]);
    $pdf->SetLineWidth(0.3);
    $pdf->Rect($x, $y, $w, $h, 'DF'); 

    // Símbolo Central
    if (!empty($simbolo_central)) {
        $pdf->SetTextColor($texto_color[0], $texto_color[1], $texto_color[2]);
        $style = ($fuente_actual == 'Arial') ? 'B' : '';
        $pdf->SetFont($fuente_actual, $style, $tamano_fuente_central);
        
        $pos_y_centro = $y + ($h / 2) - ($tamano_fuente_central / 4) + $ajuste_y_simbolo;
        if ($fuente_actual == 'ZapfDingbats') $pos_y_centro += 1; 

        $pdf->SetXY($x, $pos_y_centro);
        $pdf->Cell($w, 0, ($simbolo_central), 0, 0, 'C');
    }

    // =========================================================
    // CONTROL DE ESQUINAS (PRECISIÓN MILIMÉTRICA)
    // =========================================================
    $pdf->SetTextColor(0, 0, 0); 
    
    // Usamos fuente 5 para que "1.5T" y "N" quepan sin tocarse
    $pdf->SetFont('Arial', 'B', 6); 

    // Márgenes internos
    $margen_x = -0.3; // Separación del borde lateral
    $pos_y_inf = $y + $h - 1; // Altura desde abajo (0.8mm del fondo)
    $pos_y_sup = $y + 2;        // Altura desde arriba

    // 1. Esquina SUPERIOR DERECHA (HE)
    if (!empty($texto_sup_der)) {
        $pdf->SetXY($x, $pos_y_sup); 
        $pdf->Cell($w - $margen_x, 0, $texto_sup_der, 0, 0, 'R'); 
    }

    // 2. Esquina INFERIOR DERECHA (Duración: 1T, 4H, 1.5T)
    if (!empty($texto_inf_der)) {
        // Establecemos X al inicio de la celda
        // Usamos Cell con ancho completo y align 'R' para pegar a la derecha
        $pdf->SetXY($x, $pos_y_inf); 
        $pdf->Cell($w - $margen_x, 0, $texto_inf_der, 0, 0, 'R');
    }

    // 3. Esquina INFERIOR IZQUIERDA (Nocturnidad: N)
    if (!empty($texto_inf_izq)) {
        // Establecemos X con un pequeño margen izquierdo
        $pdf->SetXY($x + $margen_x, $pos_y_inf); 
        // Usamos align 'L'
        $pdf->Cell($w, 0, $texto_inf_izq, 0, 0, 'L'); 
    }
// 4. INDICADOR VISUAL (PESTAÑA NARANJA)
    // Lógica: Solo mostramos la pestaña si el link apunta a un control REAL (id > 0).
    // Si el link termina en "=0", es un link de "crear/alerta", no de "ver", así que no ponemos triángulo.
    
    $tiene_control_real = (!empty($link) && strpos($link, 'codigo_produccion=0') === false);

    if ($tiene_control_real) {
        $pdf->SetFillColor(255, 140, 0); // Naranja "Safety"
        
        // DIBUJAR TRIÁNGULO (Usando Polygon si agregaste la función, o Rect si no)
        // Opción A: Si agregaste la función Polygon a la clase PDF:
        $puntos = [$x, $y, $x + 2.0, $y, $x, $y + 2.0];
        $pdf->Polygon($puntos, 'F');
        
        // Opción B (Si prefieres el cuadrito seguro):
        // $pdf->Rect($x, $y, 2.5, 2.5, 'F'); 
    }

    // 5. LINK TOTAL (CAPA FINAL INVISIBLE)
    // El link sigue funcionando siempre que la variable no esté vacía
    if (!empty($link)) {
        $pdf->Link($x, $y, $w, $h, $link);
    }
}

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


// --- NUEVO: EXTRAER CÓDIGO DEL RESPONSABLE (Para resaltar en el reporte) ---
// Formato esperado: "11600-JULIO ERNESTO..."
$codigo_responsable_target = 0;
if (!empty($persona_responsable) && strpos($persona_responsable, '-') !== false) {
    $partes_resp = explode('-', $persona_responsable);
    // Tomamos la primera parte (11600) y la convertimos a entero para quitar ceros extras
    $codigo_responsable_target = intval($partes_resp[0]); 
}
// --------------------------------------------------------------------------

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

$query = "SELECT p.codigo, p.nombres, p.apellidos, p.salario, p.codigo_ruta, p.codigo_departamento_empresa, p.codigo_cargo, cc.descripcion as cargo_descripcion ";
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

// =========================================================================
// PRE-CARGA DE PRODUCCIÓN (OPTIMIZADA Y NORMALIZADA)
// =========================================================================
$produccion_registrada = []; 

if ($DepartamentoEmpresa == '02' && !empty($codigos_personal_a_consultar)) {
    $codigos_str = "'" . implode("','", $codigos_personal_a_consultar) . "'";
    
    $sql_prod = "SELECT codigo_personal, fecha, id_ FROM produccion 
                 WHERE codigo_personal IN ($codigos_str) 
                 AND fecha BETWEEN '$fecha_periodo_inicio' AND '$fecha_periodo_fin'";
                 
    $stmt_prod = $dblink->query($sql_prod);
    while ($row = $stmt_prod->fetch(PDO::FETCH_ASSOC)) {
        // TRUCO: Convertimos a entero (intval) para quitar ceros a la izquierda (00924 -> 924)
        // Esto asegura que coincida sin importar cómo esté guardado.
        $codigo_limpio = intval($row['codigo_personal']); 
        $fecha_limpia = trim($row['fecha']);
        
        $produccion_registrada[$codigo_limpio][$fecha_limpia] = $row['id_'];
    }
}

// =========================================================
// OPTIMIZACIÓN: CARGA MASIVA DE ARRASTRE (15 días atrás)
// =========================================================
$asistencia_historica = [];
if (!empty($codigos_personal_a_consultar)) {
    // Calculamos 15 días antes del inicio para cubrir cualquier "semana anterior"
    $fecha_inicio_carry = date('Y-m-d', strtotime($fecha_periodo_inicio . ' -15 days'));
    
    // Reutilizamos la lista de códigos
    $codigos_personal_str = "'" . implode("','", $codigos_personal_a_consultar) . "'";
    
    $stmt_carry = $dblink->prepare("
        SELECT codigo_personal, fecha, codigo_jornada, codigo_tipo_licencia, codigo_jornada_asueto 
        FROM personal_asistencia 
        WHERE codigo_personal IN ($codigos_personal_str)
        AND fecha BETWEEN :fecha_carry AND :fecha_inicio_menos_1
    ");
    
    $fecha_fin_carry = date('Y-m-d', strtotime($fecha_periodo_inicio . ' -1 day'));
    
    $stmt_carry->bindParam(':fecha_carry', $fecha_inicio_carry);
    $stmt_carry->bindParam(':fecha_inicio_menos_1', $fecha_fin_carry);
    $stmt_carry->execute();
    
    while ($row_h = $stmt_carry->fetch(PDO::FETCH_ASSOC)) {
        $cp = $row_h['codigo_personal'];
        $f = $row_h['fecha'];
        // Guardamos el código combinado que es lo que nos interesa para detectar faltas
        $asistencia_historica[$cp][$f] = trim($row_h['codigo_jornada']) . trim($row_h['codigo_tipo_licencia']) . trim($row_h['codigo_jornada_asueto']);
    }
}
// =========================================================

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

// --- CORRECCIÓN: Se agrega el parámetro $deductible_events_carry_over a la función ---
function processEmployeeAttendanceData($rango_fechas, $codigo_personal, $salario_mensual, $jornada_base_default, &$asistencia_por_empleado_y_fecha, $NombresCodigoLicenciaPermiso, $jornada_imagenes_map, $FechaDescripcionAsueto, $codigo_departamento_empleado, $initial_isss_days = 0, $deductible_events_carry_over = []) {
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
    $dias_isss_acumulados = 0; 

    // --- LÓGICA PRE-CÁLCULO ---
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

    $non_contributory_codes_display_only = ['4344444'];
    $deduction_codes = ['41044444', '4444444', '4144444']; 
    $asueto_worked_codes = ['41614444' => $salario_diario / 2, '41624444' => $salario_diario, '41634444' => $salario_diario + ($salario_diario / 2),
        // --- NUEVOS: TRABAJO ASUETO CON NOCTURNIDAD ---
        '41614445' => $salario_diario / 2, // TA 4H + N
        '41624445' => $salario_diario,     // TA 1T + N
        '41634445' => $salario_diario + ($salario_diario / 2), // TA 1.5T + N
        '41644445' => $salario_diario      // TA + N (Sin duración definida, asumimos 1T)
        ];
    $trabajo_descanso_codes = ['41444144' => $salario_diario / 2, '41444244' => $salario_diario, '41444344' => $salario_diario + ($salario_diario / 2),
        '11444144' => $salario_diario * 2, 
        // --- NUEVOS: TRABAJO DESCANSO CON NOCTURNIDAD ---
        // Se paga igual que el descanso normal, la nocturnidad se suma aparte
        '41444145' => $salario_diario / 2, // TD 4H + N
        '41444245' => $salario_diario,     // TD 1T + N
        '41444345' => $salario_diario + ($salario_diario / 2) // TD 1.5T + N
    ];
    $trabajo_vacacion_codes = ['41241444' => $salario_diario / 2, '41242444' => $salario_diario, '41243444' => $salario_diario + ($salario_diario / 2)];
    $trabajo_descanso_asueto_codes = [
        '41744444' => $salario_diario, '41514444' => $salario_diario / 2, '41524444' => $salario_diario, '41534444' => $salario_diario + ($salario_diario / 2)];
    $nocturnidad_base_value = 0.57;
    $nocturnidad_codes_specific = [
        '2144445' => true, '1144445' => true, '1144425' => true, '11444450' => true, '2124445' => true, '41242445' => true, '41241445' => true,
        // --- NUEVOS: TRABAJO DESCANSO NOCTURNO ---
        '41444145' => true, // TD 4H N
        '41444245' => true, // TD 1T N
        '41444345' => true, // TD 1.5T N
        
        // --- NUEVOS: TRABAJO ASUETO NOCTURNO ---
        '41614445' => true, // TA 4H N
        '41624445' => true, // TA 1T N
        '41634445' => true, // TA 1.5T N
        '41644445' => true, // TA N Genérico
        '41944445' => true, // TA N (Código antiguo 19)
        '41924445' => true, // TA N 1T (Código antiguo 19)
        '41934445' => true  // TA N 1.5T (Código antiguo 19)
    ];
    $fixed_extra_codes = [
        '1144424' => $salario_diario, '3144444' => $salario_diario / 2,
        '1144425' => $salario_diario,       // NUEVO: Agrega 1 Tanda (8H) a Extras
        // El caso complejo: Media Tanda + 1 Tanda Extra + Noche
    ];
    $weekly_four_h_count = [];

    // --- BUCLE PRINCIPAL DE CÁLCULO DIARIO ---
    foreach ($rango_fechas as $fecha_actual) {
        $row_asistencia = $asistencia_por_empleado_y_fecha[$codigo_personal][$fecha_actual] ?? null;
        $CodigoJornadaTodas = $row_asistencia ? buscarCodigoDeAsistencia($dblink, $codigo_personal, $fecha_actual, $asistencia_por_empleado_y_fecha) : '';
        
        // --- INICIALIZACIÓN DE VARIABLES DIARIAS ---
        $salario_dia_actual = 0;
        $descuento_dia_actual = 0;
        $bono_dia_actual = 0;
        $nocturnidad_dia_actual = 0;
        $es_dia_pagado_para_hora_extra = false;

        // --- 1. DETERMINAR EL ESTADO BASE DEL DÍA (PAGO, SIN PAGO O DESCUENTO) ---
        if ($row_asistencia) {
            if ($CodigoJornadaTodas == '1144444') {
                $date_obj_media = new DateTime($fecha_actual);
                $week_start_date_media = ($date_obj_media->format('N') == 1) ? $date_obj_media->format('Y-m-d') : (clone $date_obj_media)->modify('last monday')->format('Y-m-d');
                $weekly_four_h_count[$week_start_date_media] = ($weekly_four_h_count[$week_start_date_media] ?? 0) + 1;
                $salario_dia_actual = ($weekly_four_h_count[$week_start_date_media] > 1) ? $salario_diario / 2 : $salario_diario;
                $es_dia_pagado_para_hora_extra = true;
            }
            else if ($CodigoJornadaTodas == '4244444') {
                $dias_isss_acumulados++; 
                if ($dias_isss_acumulados === 3) {
                    $salario_dia_actual = $salario_diario * 3; 
                } elseif($dias_isss_acumulados >= 1 && $dias_isss_acumulados < 3) {
                    $salario_dia_actual = 0; 
                }
                else {
                    $salario_dia_actual = 0; 
                }
                $es_dia_pagado_para_hora_extra = false;
                $monto_horas_extra_dia_actual = 0;
                $descuento_dia_actual = 0;
            }
            else if (in_array($CodigoJornadaTodas, $deduction_codes)) {
                $descuento_dia_actual = $salario_diario;
            }
            else if (in_array($CodigoJornadaTodas, $non_contributory_codes_display_only)) {
                $salario_dia_actual = 0;
            }
            else if (trim($row_asistencia['codigo_jornada'] ?? '') == '4') {
                 $licencia_info = $NombresCodigoLicenciaPermiso[$row_asistencia['codigo_tipo_licencia']] ?? ['horas' => $jornada_base_default];
                 $valor_hora_normal = ($jornada_base_default > 0) ? ($salario_diario / $jornada_base_default) : 0;
                 $salario_dia_actual = $valor_hora_normal * (float)($licencia_info['horas'] ?? 0);
                 $es_dia_pagado_para_hora_extra = true;
            }
            else {
                $salario_dia_actual = $salario_diario;
                $es_dia_pagado_para_hora_extra = true;
            }
        } else { // No hay registro de asistencia
            if (isset($FechaDescripcionAsueto[$fecha_actual])) { // Es asueto no trabajado
                $salario_dia_actual = $salario_diario;
                $total_salario_asuetos += $salario_diario; 
                $CodigoJornadaTodas = 'AS';
            } else { // Es una falta genérica
                $descuento_dia_actual = $salario_diario;
                $CodigoJornadaTodas = 'FALTA_GENERICA';
            }
        }

        // --- 2. CALCULAR BONIFICACIONES ADICIONALES (EXTRAS) ---
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
                if ($CodigoJornadaTodas != '41744444') { 
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
        
        $total_salario_devengado_empleado += $salario_dia_actual;
        $total_descuentos_empleado += $descuento_dia_actual;
        $total_trabajo_extra_empleado += $bono_dia_actual;
        $total_monto_nocturnidad_empleado += $nocturnidad_dia_actual;
        $total_monto_horas_extra_empleado += $monto_horas_extra_dia_actual;

        $image_filename = $jornada_imagenes_map[trim($CodigoJornadaTodas)] ?? '';
        $daily_attendance_details[$fecha_actual] = ['image_filename' => $image_filename];


        // --- INICIO: RASTREADOR (Solo para el empleado 03722) ---
        if ($codigo_personal == '022101') {
            echo "<div style='font-family:monospace; border-bottom:1px solid #ccc;'>";
            echo "Fecha: <strong>$fecha_actual</strong> | ";
            echo "Código: $CodigoJornadaTodas | ";
            
            if ($salario_dia_actual > 0) {
                echo "<span style='color:green;'>SUMA: +$" . number_format($salario_dia_actual, 2) . "</span>";
            } else {
                echo "<span style='color:red;'>NO PAGA ($0.00)</span>";
            }
            
            // Ver si generó descuento en esta etapa
            if ($descuento_dia_actual > 0) {
                 echo " | <span style='color:red; font-weight:bold;'>GENERÓ DESCUENTO: -$" . number_format($descuento_dia_actual, 2) . "</span>";
            }
            echo "</div>";
        }
        // --- FIN RASTREADOR ---
    }
    
// =======================================================================
    // CÁLCULO FINAL DE DESCUENTOS
    // =======================================================================
    
    // 1. Obtenemos SOLO el valor del séptimo día
    $total_deduccion_7mo = calcularSeptimoDia(
        $dblink, 
        $codigo_personal, 
        $codigo_departamento_empleado, 
        $fecha_periodo_inicio, 
        $fecha_periodo_fin, 
        $asistencia_por_empleado_y_fecha, 
        $FechaDescripcionAsueto, 
        $deductible_events_carry_over, 
        $salario_mensual
    );

// OPCIÓN 1: RESTAR DIRECTO AL SA
    // Restamos el castigo directamente al acumulado de días trabajados
    $total_salario_devengado_empleado -= $total_deduccion_7mo; 
    
    // Importante: Ponemos los descuentos en 0 para que no se resten dos veces en la fórmula final
    $total_descuentos_empleado = 0; 

    // --- CÁLCULO DE TOTALES FINALES ---
    // Ahora el Gross será más bajo, y el líquido cuadrará perfecto
    $total_extra_empleado = $total_salario_asuetos + $total_monto_horas_extra_empleado + $total_trabajo_extra_empleado + $total_monto_nocturnidad_empleado;
    $total_salario_gross_empleado = $total_salario_devengado_empleado + $total_extra_empleado;
    $salario_liquido_final_empleado = $total_salario_gross_empleado; // Ya no restamos descuentos aquí porque lo hicimos arriba

    return [
        'total_salario_devengado' => $total_salario_devengado_empleado, 
        'total_salario_asuetos' => $total_salario_asuetos, 
        'total_monto_horas_extra' => $total_monto_horas_extra_empleado, 
        'total_descuentos' => $total_descuentos_empleado, 
        'salario_liquido_final' => $salario_liquido_final_empleado, 
        'total_extra_general' => $total_extra_empleado, 
        'total_horas_extra_cantidad' => $total_horas_extra_cantidad, 
        'total_salario_gross' => $total_salario_gross_empleado, 
        'daily_details' => $daily_attendance_details, 
        'total_trabajo_extra_empleado' => $total_trabajo_extra_empleado, 
        'total_monto_nocturnidad' => $total_monto_nocturnidad_empleado, 
        'total_nocturnidad_cantidad' => $total_nocturnidad_cantidad_empleado
    ];

    if ($codigo_personal == '022101') { // <--- TU EMPLEADO DE PRUEBA
        print $total_deduccion_7mo;
        $total_extra_empleado = $total_salario_asuetos + $total_monto_horas_extra_empleado + $total_trabajo_extra_empleado + $total_monto_nocturnidad_empleado;
        $total_salario_gross_empleado = $total_salario_devengado_empleado + $total_extra_empleado;
        $salario_liquido_final_empleado = $total_salario_gross_empleado - $total_descuentos_empleado;

        print "<hr>DEBUG FINAL EMPLEADO :$codigo_personal<br>";
        print "Total Salario Devengado: " . number_format($total_salario_devengado_empleado, 2) . "<br>";
        print "Total Salario Asuetos: " . number_format($total_salario_asuetos, 2) . "<br>";
        print "Total Monto Horas Extra: " . number_format($total_monto_horas_extra_empleado, 2) . "<br>";
        print "Total Trabajo Extra: " . number_format($total_trabajo_extra_empleado, 2) . "<br>";
        print "Total Monto Nocturnidad: " . number_format($total_monto_nocturnidad_empleado, 2) . "<br>";
        print "Total Descuentos Empleado: " . number_format($total_descuentos_empleado, 2) . "<br>";
        print "Salario Líquido Final: " . number_format($salario_liquido_final_empleado, 2) . "<br>";

        exit;
    }
    // =======================================================================
}

class PDF extends FPDF
{

// --- AGREGAR ESTA FUNCIÓN PARA PODER DIBUJAR TRIÁNGULOS ---
    function Polygon($points, $style='D') {
        if($style=='F') $op='f'; elseif($style=='FD' || $style=='DF') $op='b'; else $op='s';
        $h = $this->h;
        $k = $this->k;
        $points_string = '';
        for($i=0; $i<count($points); $i+=2){
            $points_string .= sprintf('%.2F %.2F', $points[$i]*$k, ($h-$points[$i+1])*$k);
            if($i==0) $points_string .= ' m '; else $points_string .= ' l ';
        }
        $this->_out($points_string . ' h ' . $op);
    }
    // -----------------------------------------------------------



    
    private $day_names_spanish = [
        'Sun' => 'Dom', 'Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mié', 'Thu' => 'Jue', 'Fri' => 'Vie', 'Sat' => 'Sáb'
    ];

    function Header() {
        global $fecha_periodo_inicio, $fecha_periodo_fin, $departamentoEmpresaTexto, $RutaText, $quincena, $rango_fechas, $DepartamentoEmpresa;
        global $_SESSION, $persona_responsable; 

// 1. LOGO Y TÍTULOS (IZQUIERDA)
        $img = $_SERVER['DOCUMENT_ROOT'].'/acomtus/img/'.$_SESSION['logo_uno'];
        if (file_exists($img)) {
            $this->Image($img,5,4,24,24);
        }
        
        $this->SetFont('Arial','B',14);
        $this->SetXY(30,5);
        $this->Cell(100,7,mb_convert_encoding($_SESSION["nombre_institucion"],"ISO-8859-1"),0,1,"L",false);
        
        $this->SetFont('Arial','B',11);
        $this->SetX(30);
        $this->Cell(100,6, 'Reporte del ' . date('d/m/Y', strtotime($fecha_periodo_inicio)) . ' al ' . date('d/m/Y', strtotime($fecha_periodo_fin)),0,1,"L",false);
        
        $this->SetX(30);
        $texto_ruta = $departamentoEmpresaTexto . (!empty($RutaText) && $RutaText!='Seleccionar...' ? " (Ruta: $RutaText)" : "");
        $this->Cell(100,6, $texto_ruta,0,1,"L",false);
        
        $this->SetFont('Arial','B',9);
        $this->SetX(30);
        $this->Cell(130,6,mb_convert_encoding("Responsable: " . ($persona_responsable ?? 'N/A'),"ISO-8859-1"),0,0,"L",false);

        // --- 2. LEYENDA (MOVIDA A LA DERECHA) ---
        // Guardamos la posición Y actual para no perder el flujo, pero dibujamos a la derecha
        $y_actual = $this->GetY();
        
        // Nos movemos a la derecha (X=200) y subimos un poco (Y=15) para que quede alineado bonito
        $this->SetXY(200, 15); 
        $this->SetFont('Arial','',7);
        
        // A. Responsable (Azul)
        $this->SetFillColor(0, 50, 150); 
        $this->Cell(4,4,'',0,0,'C'); 
        $x_tri = $this->GetX() - 4; $y_tri = $this->GetY();
        $this->Polygon([$x_tri, $y_tri, $x_tri+3, $y_tri, $x_tri, $y_tri+3], 'F');
        $this->Cell(18,4,' Responsable',0,0,'L');
        
        // B. Revisador y Ticket (Solo Motoristas)
        if ($DepartamentoEmpresa == '02') {
            // Salto de línea manual a la derecha
            $this->SetXY(200, 20); 
            
            $this->SetFillColor(204, 255, 204); // Verde pastel

            $this->Cell(4,4,'',0,0,'C');
            $x_tri = $this->GetX() - 4; $y_tri = $this->GetY();
            $this->Polygon([$x_tri, $y_tri, $x_tri+3, $y_tri, $x_tri, $y_tri+3], 'F');
            $this->Cell(18,4,' Revisador',0,0,'L');
            
            // Ticket (Rojo)
            $this->SetXY(200, 25);
            $this->SetFillColor(255, 204, 204); 
            $this->Cell(4,4,'',1,0,'C',true); 
            $this->Cell(18,4,' Sin Ticket',0,0,'L');
        }

        // Regresamos el cursor abajo para dibujar la tabla correctamente
        $this->SetXY(10, $y_actual + 10); 

        // --- 3. ENCABEZADOS DE TABLA ---
        $this->SetFont('Arial', 'B', 7);
        $this->SetFillColor(200, 220, 255);
        $this->SetDrawColor(0,0,0);

        $w_initial_fixed = [7, 15, 50]; 
        $daily_col_width = 7.0; 
        $w_financial_fixed = [10, 8, 10, 10, 17, 14, 15]; 

        $header_height = 12; 
        $half_header_height = $header_height / 2; 

        $x_start = 10;
        $y_start_table_headers = $this->GetY(); 

        $this->SetY($y_start_table_headers);
        $this->SetX($x_start);

        $this->Cell($w_initial_fixed[0], $header_height, 'No.', 1, 0, 'C', true); 
        $this->Cell($w_initial_fixed[1], $header_height, 'CODIGO', 1, 0, 'C', true); 
        $this->Cell($w_initial_fixed[2], $header_height, 'NOMBRE', 1, 0, 'C', true); 
        
        $x_after_initial_fixed = $x_start + array_sum($w_initial_fixed);
        $this->SetX($x_after_initial_fixed);

        foreach ($rango_fechas as $fecha_actual) {
            $english_day_name = date('D', strtotime($fecha_actual)); 
            $spanish_day_name = $this->day_names_spanish[$english_day_name] ?? $english_day_name; 
            if ($english_day_name == 'Sat' || $english_day_name == 'Sun') {
                $this->SetFillColor(180, 200, 230); 
            } else {
                $this->SetFillColor(200, 220, 255); 
            }
            $this->Cell($daily_col_width, $half_header_height, (strtoupper($spanish_day_name)), 1, 0, 'C', true);
        }

        $this->SetFillColor(180, 200, 230); 
        $this->Cell($w_financial_fixed[0], $header_height, 'SA', 1, 0, 'C', true); 
        $this->SetFillColor(200, 220, 255); 
        $this->Cell($w_financial_fixed[1], $header_height, 'AS', 1, 0, 'C', true); 
        $this->SetFillColor(180, 200, 230); 
        $this->Cell($w_financial_fixed[2], $half_header_height, ('EXTRA'), 1, 0, 'C', true); 
        $this->SetFillColor(200, 220, 255); 
        if ($DepartamentoEmpresa == '08' || $DepartamentoEmpresa == '09') {
        $this->Cell($w_financial_fixed[3], $half_header_height, ('Nocturnidad'), 1, 0, 'C', true); 
        }
        $this->SetFillColor(180, 200, 230); 
        $this->Cell($w_financial_fixed[4], $half_header_height, ('Hora Extra'), 1, 0, 'C', true); 
        $this->SetFillColor(200, 220, 255); 
        $this->Cell($w_financial_fixed[5], $half_header_height, 'Total', 1, 0, 'C', true); 
        $this->SetFillColor(180, 200, 230); 
        $this->Cell($w_financial_fixed[6], $half_header_height, 'Salario', 1, 0, 'C', true); 

        $this->Ln(); 

        $this->SetY($y_start_table_headers + $half_header_height); 
        $this->SetX($x_after_initial_fixed); 

        foreach ($rango_fechas as $fecha_actual) {
            $english_day_name = date('D', strtotime($fecha_actual)); 
            $day_number = date('d', strtotime($fecha_actual)); 
            if ($english_day_name == 'Sat' || $english_day_name == 'Sun') {
                $this->SetFillColor(180, 200, 230); 
            } else {
                $this->SetFillColor(200, 220, 255); 
            }
            $this->Cell($daily_col_width, $half_header_height, $day_number, 1, 0, 'C', true);
        }

        $this->SetFillColor(200, 220, 255); 
        $this->Cell($w_financial_fixed[0], $half_header_height, '', 0, 0, 'C', false); 
        $this->Cell($w_financial_fixed[1], $half_header_height, '', 0, 0, 'C', false); 
        $this->Cell($w_financial_fixed[2], $half_header_height, '', 0, 0, 'C', false); 
        if ($DepartamentoEmpresa == '08' || $DepartamentoEmpresa == '09') {
        $this->Cell($w_financial_fixed[3]/2, $half_header_height, 'C', 1, 0, 'C', true); 
        $this->Cell($w_financial_fixed[3]/2, $half_header_height, 'V', 1, 0, 'C', true); 
        }
        $this->Cell($w_financial_fixed[4]/2, $half_header_height, 'C', 1, 0, 'C', true); 
        $this->Cell($w_financial_fixed[4]/2, $half_header_height, 'V', 1, 0, 'C', true); 
        $this->SetFillColor(180, 200, 230); 
        $this->Cell($w_financial_fixed[5], $half_header_height, 'Extra', 1, 0, 'C', true); 
        $this->SetFillColor(200, 220, 255); 
        $this->Cell($w_financial_fixed[6], $half_header_height, 'Liquido', 1, 0, 'C', true); 

        $this->Ln(); 
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, ('Página ').$this->PageNo().'/{nb}', 0, 0, 'C');
    }
}

$pdf = new PDF('L', 'mm', 'Letter'); 
$pdf->SetMargins(10, 10, 10);
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',9);

$sorted_datos_empleado_principal = [];
foreach ($codigos_personal_a_consultar as $codigo) {
    if (isset($datos_empleado_principal[$codigo])) {
        $sorted_datos_empleado_principal[$codigo] = $datos_empleado_principal[$codigo];
    }
}
$datos_empleado_principal = $sorted_datos_empleado_principal; 

$i=1; 
$jornada_base_default = 8; 

$w_initial_fixed = [7, 15, 50]; 
$daily_col_width = 7.0; 
$w_financial_fixed = [10, 8, 10, 10, 17, 14, 15]; 

$row_color_even = [234, 236, 238]; 
$row_color_odd = [255, 255, 255];  
$row_fill_flag = false; 

foreach ($datos_empleado_principal as $row_empleado) {
    $codigo_personal = TRIM($row_empleado['codigo']);
    $nombres_empleado = TRIM($row_empleado['nombres']);
    $apellidos_empleado = TRIM($row_empleado['apellidos']);
    $salario_mensual = (float)$row_empleado['salario'];
    $codigo_departamento_empleado = TRIM($row_empleado['codigo_departamento_empresa']);
    
       // --- 1. CÁLCULO DE ARRASTRE (CARRY OVER) - OPTIMIZADO ---
   $deductible_events_carry_over = [];
   $fecha_inicio_dt = new DateTime($fecha_periodo_inicio);
   $day_of_week_num = (int)$fecha_inicio_dt->format('N'); 

   if ($day_of_week_num > 1) {
       $week_start_dt = (clone $fecha_inicio_dt)->modify('last monday');
       $week_start_str = $week_start_dt->format('Y-m-d');
       $check_date_dt = (clone $fecha_inicio_dt)->modify('-1 day');

        while ($check_date_dt >= $week_start_dt) {
           $fecha_str = $check_date_dt->format('Y-m-d');
           
           // --- CAMBIO: BÚSQUEDA EN ARRAY (MEMORIA) EN VEZ DE SQL ---
           $codigo_anterior_combinado = '';
           
           // Verificamos si existe en nuestro array precargado
           if (isset($asistencia_historica[$codigo_personal][$fecha_str])) {
               $codigo_anterior_combinado = $asistencia_historica[$codigo_personal][$fecha_str];
           }
           // ---------------------------------------------------------

           $deductible_codes_check = ['41044444', '4444444'];
           if (in_array(trim($codigo_anterior_combinado), $deductible_codes_check)) {
               $deductible_events_carry_over[$week_start_str] = [
                   'has_deductible_event' => true,
                   'has_descanso' => false, // (Simplificado para el ejemplo)
                   'deducted_7mo' => false
               ];
               break; 
           }
           $check_date_dt->modify('-1 day');
       }
   }

    // AGREGAR ESTO: Necesitamos el salario para el debug
    $salario_mensual_debug = (float)$row_empleado['salario']; 

    if ($codigo_personal == '0372211') { // <--- TU EMPLEADO DE PRUEBA
        
        // Recalculo rápido de carry over para el debug (igual que antes)
        $debug_carry_over = [];
        $fecha_inicio_dt = new DateTime($fecha_periodo_inicio);
        if ((int)$fecha_inicio_dt->format('N') > 1) {
             $week_start_dt = (clone $fecha_inicio_dt)->modify('last monday');
             $week_start_str = $week_start_dt->format('Y-m-d');
             $check_date_dt = (clone $fecha_inicio_dt)->modify('-1 day');
             while ($check_date_dt >= $week_start_dt) {
                // ... (misma lógica de query pequeño que te di antes, 
                // o simplemente pasar el array vacío si confías en que ese empleado no tiene carry over) ...
                // Para efectos prácticos, usa la variable $deductible_events_carry_over
                // que calculamos unas líneas más abajo en el código real,
                // PERO MUEVE EL CÁLCULO DE 'deductible_events_carry_over' 
                // ANTES DEL DEBUG.
                $check_date_dt->modify('-1 day');
             }
        }

        // *** IMPORTANTE: MUEVE EL CÁLCULO DE $deductible_events_carry_over ***
        // *** QUE TIENES EN TU CÓDIGO ACTUAL PARA QUE OCURRA ANTES DE ESTE IF ***
        // (O simplemente copia y pega el bloque de cálculo de carry over aquí arriba)

        debugCalculoSeptimoDia(
            $dblink, 
            $codigo_personal, 
            $codigo_departamento_empleado, 
            $fecha_periodo_inicio, 
            $fecha_periodo_fin, 
            $asistencia_por_empleado_y_fecha, 
            $FechaDescripcionAsueto,
            $deductible_events_carry_over, // Asegúrate que esta variable ya esté calculada
            $salario_mensual_debug // <--- NUEVO PARÁMETRO
        );
        exit;
    }

    // --- CORRECCIÓN: Lógica de arrastre de faltas previas ---
    $deductible_events_carry_over = [];
    $fecha_inicio_dt = new DateTime($fecha_periodo_inicio);
    $day_of_week_num = (int)$fecha_inicio_dt->format('N'); 

    if ($day_of_week_num > 1) {
        $week_start_dt = (clone $fecha_inicio_dt)->modify('last monday');
        $week_start_str = $week_start_dt->format('Y-m-d');
        $check_date_dt = (clone $fecha_inicio_dt)->modify('-1 day');

         while ($check_date_dt >= $week_start_dt) {
            $fecha_str = $check_date_dt->format('Y-m-d');

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
                $codigo_anterior_combinado = 
                    trim($asistencia_anterior['codigo_jornada'] ?? '') .
                    trim($asistencia_anterior['codigo_tipo_licencia'] ?? '') .
                    trim($asistencia_anterior['codigo_jornada_asueto'] ?? '') .
                    trim($asistencia_anterior['codigo_jornada_vacaciones'] ?? '') .
                    trim($asistencia_anterior['codigo_jornada_descanso'] ?? '') .
                    trim($asistencia_anterior['codigo_jornada_e_4h'] ?? '') .
                    trim($asistencia_anterior['codigo_jornada_nocturna'] ?? '');
            }

            $deductible_codes_check = ['41044444', '4444444'];
            if (in_array(trim($codigo_anterior_combinado), $deductible_codes_check)) {
                $deductible_events_carry_over[$week_start_str] = [
                    'has_deductible_event' => true,
                    'has_descanso' => false,
                    'descanso_date' => null,
                    'deducted_7mo' => false,
                    'four_h_count' => 0 
                ];
                break;
            }
            $check_date_dt->modify('-1 day');
        }
    }

    $carry_over_isss_days = 0;
    $fecha_busqueda_isss = new DateTime($fecha_periodo_inicio);
    for ($k=0; $k<15; $k++) { 
        $fecha_busqueda_isss->modify('-1 day');
        $codigo_anterior = buscarCodigoDeAsistencia($dblink, $codigo_personal, $fecha_busqueda_isss->format('Y-m-d'), $asistencia_por_empleado_y_fecha);
        if ($codigo_anterior === '4244444') {
            $carry_over_isss_days++;
        } else {
            break; 
        }
    }

    // --- CORRECCIÓN: Pasar $deductible_events_carry_over a la función ---
    $results = processEmployeeAttendanceData(
        $rango_fechas,
        $codigo_personal,
        $salario_mensual,
        $jornada_base_default,
        $asistencia_por_empleado_y_fecha,
        $NombresCodigoLicenciaPermiso,
        $jornada_imagenes_map,
        $FechaDescripcionAsueto, 
        $codigo_departamento_empleado, 
        $carry_over_isss_days,
        $deductible_events_carry_over // Nuevo parámetro
    );

    $total_salarios_a_mostrar = round($results['total_salario_devengado'] ?? 0, 2);
    $total_salario_asuetos_a_mostrar = round($results['total_salario_asuetos'] ?? 0, 2); 
    $total_monto_horas_extra_a_mostrar = round($results['total_monto_horas_extra'] ?? 0, 2); 
    $total_extra_general_a_mostrar = $results['total_extra_general'] ?? 0; 
    $total_salario_gross_a_mostrar = round($results['total_salario_gross'] ?? 0, 2); 
    $total_horas_extra_cantidad_a_mostrar = $results['total_horas_extra_cantidad'] ?? 0; 
    $salario_liquido_final_a_mostrar = round($results['salario_liquido_final'] ?? 0, 2); 
    $total_trabajo_extra_empleado_a_mostrar = round($results['total_trabajo_extra_empleado'] ?? 0, 2); 
    $total_monto_nocturnidad_a_mostrar = round($results['total_monto_nocturnidad'] ?? 0, 2); 
    $total_nocturnidad_cantidad_a_mostrar = $results['total_nocturnidad_cantidad'] ?? 0; 

    $daily_attendance_details = $results['daily_details'] ?? []; 

    $current_row_fill_color = $row_fill_flag ? $row_color_odd : $row_color_even;
    $pdf->SetFillColor($current_row_fill_color[0], $current_row_fill_color[1], $current_row_fill_color[2]); 

    $pdf->SetFont('Arial', '', 7); 
    $pdf->SetDrawColor(0,0,0); 

    $pdf->SetX(10); 

    // Define la altura antes del bucle (puedes probar con 8, 9 o 10)
        $h_fila = 9;   
        $nombre_completo = trim(($nombres_empleado . ' ' . $apellidos_empleado));

        $codigo_personal_int = intval($codigo_personal); // Convertimos a entero para comparar (11600 == 0011600)
        $codigo_cargo = trim($row_empleado['codigo_cargo']); 
    
        // 1. DETERMINAR SI ES RESPONSABLE O REVISADOR
        // Es Responsable si su código coincide con el seleccionado en el menú anterior
        $es_responsable = ($codigo_responsable_target > 0 && $codigo_personal_int == $codigo_responsable_target);
        
        // Es Revisador si es cargo 17 (Despacho) y estamos en Motoristas (02)
        $es_revisador = ($codigo_cargo == '17' && $DepartamentoEmpresa == '02');
    
        // 2. DEFINIR COLOR DE FONDO DE LA FILA (NOMBRE/CÓDIGO)
        if ($es_responsable) {
            // AZUL SUAVE (Highlight para el Responsable)
            $pdf->SetFillColor(225, 240, 255); 
        } elseif ($es_revisador) {
            // VERDE SUAVE (Opcional, para el revisador, o lo dejas blanco)
            $pdf->SetFillColor(240, 255, 240); 
        } else {
            // COLOR CEBRA NORMAL (Blanco Humo / Blanco)
            $pdf->SetFillColor(($i%2==0)?248:255, ($i%2==0)?250:255, ($i%2==0)?252:255);
        }
    
        // 3. DIBUJAR COLUMNA "No."
        // Dibujamos la celda con el color de fondo definido arriba
        $pdf->Cell($w_initial_fixed[0], 9, $i, 1, 0, 'C', true);
        
        // Guardamos posición para el triángulo
        $x_num = $pdf->GetX() - $w_initial_fixed[0]; 
        $y_num = $pdf->GetY();
        
        // 4. DIBUJAR TRIÁNGULOS IDENTIFICADORES (ENCIMA)
        if ($es_responsable) { 
            // TRIÁNGULO AZUL OSCURO (Responsable)
            $pdf->SetFillColor(0, 50, 150); 
            $pdf->Polygon([$x_num, $y_num, $x_num+3, $y_num, $x_num, $y_num+3], 'F');
            // Restauramos el color de fondo suave para las siguientes celdas
            $pdf->SetFillColor(225, 240, 255); 
        } 
        elseif ($es_revisador) { 
            // TRIÁNGULO VERDE OSCURO (Revisador)
            $pdf->SetFillColor(0, 100, 0); 
            $pdf->Polygon([$x_num, $y_num, $x_num+3, $y_num, $x_num, $y_num+3], 'F');
            // Restauramos color (si definiste uno especial para revisador, sino usa el cebra)
            if($es_revisador) $pdf->SetFillColor(240, 255, 240); 
            else $pdf->SetFillColor(($i%2==0)?248:255, ($i%2==0)?250:255, ($i%2==0)?252:255);
        }
    
        // 5. DIBUJAR CÓDIGO Y NOMBRE (Con el color de fondo activo)
        $pdf->Cell($w_initial_fixed[1], 9, $codigo_personal, 1, 0, 'L', true);
        $pdf->Cell($w_initial_fixed[2], 9, $nombre_completo, 1, 0, 'L', true);

foreach ($rango_fechas as $fecha_actual) {
        // -----------------------------------------------------------
        // PASO 1: OBTENER DATOS Y POSICIÓN
        // -----------------------------------------------------------
        // Guardamos la posición exacta antes de hacer nada
        $x_actual = $pdf->GetX();
        $y_actual = $pdf->GetY();

        // Buscamos el código de asistencia para el dibujo
        $codigo_dia_actual = buscarCodigoDeAsistencia($dblink, $codigo_personal, $fecha_actual, $asistencia_por_empleado_y_fecha);
        if (empty($codigo_dia_actual) && !isset($FechaDescripcionAsueto[$fecha_actual])) {
             $codigo_dia_actual = ''; 
        }

        // Lógica de Sufijo HE (Horas Extras visuales)
        if ($codigo_departamento_empleado == '02' || $codigo_departamento_empleado == '03') {
            $dato_asistencia = $asistencia_por_empleado_y_fecha[$codigo_personal][$fecha_actual] ?? null;
            if ($dato_asistencia) {
                $horas_extra = (float)($dato_asistencia['hora_extra'] ?? 0);
                if ($horas_extra > 0) $codigo_dia_actual .= '_HE' . $horas_extra;
            }
        }
            // --- INICIO MODIFICACIÓN: ALERTA DE PRODUCCIÓN ---
                
                // 1. Definir Color Base (Fin de semana o Fila Normal)
                $english_day = date('D', strtotime($fecha_actual));
                if ($english_day == 'Sat' || $english_day == 'Sun') {
                    $pdf->SetFillColor(180, 200, 230); // Azul fin de semana
                } else {
                    // Usamos el color de la fila actual (Cebra)
                    $pdf->SetFillColor($current_row_fill_color[0], $current_row_fill_color[1], $current_row_fill_color[2]);
                }

        // 2. Lógica de Link y Alerta Roja
        $link = ""; 
        $fondo_alerta = false;

        if ($DepartamentoEmpresa == '02') { 
            // Convertir a entero para asegurar coincidencia
            $codigo_key = intval($codigo_personal);
            $id_prod = $produccion_registrada[$codigo_key][$fecha_actual] ?? 0;
            
            // Generamos el link
            $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
            $host = $_SERVER['HTTP_HOST'];
            $link = "$protocol://$host/acomtus/php_libs/reportes/Planilla/DetallePorMotorista.php?codigo_produccion=" . $id_prod;
            
            // --- LISTA DE CÓDIGOS QUE NO REQUIEREN PRODUCCIÓN ---
            $codigos_exentos = [
                '4144444',  // Sin Punteo (Neutro)
                '4444444',  // Falta (F)
                '41044444', // Castigo (C)
                '4244444',  // Incapacidad (ISSS)
                '4344444',  // Permiso (PP)
                '41344444', // Descanso Puro (D)
                '41644444', // Asueto Puro (A)
                '41744444', // Descanso Asueto (DA)
                '41944444', // Vacación Descanso Asueto (VDA)
                '41144444'  // Vacación Pura (V)
            ];
            
            // REGLA 1: SI TIENE CONTROL (>0), TODO BIEN.
            // REGLA 2: SI NO TIENE CONTROL (0), SOLO ALERTAR SI ES DÍA LABORAL.
            if ($id_prod == 0) {
                if (!in_array($codigo_dia_actual, $codigos_exentos)) {
                    // Es un día de trabajo (1T, 4H, TD, TA, etc.) y falta el control -> ALERTA
                    $pdf->SetFillColor(255, 204, 204); // Rojo Pastel
                    $fondo_alerta = true;
                } else {
                    // Es día libre o falta, no necesita control -> QUITAMOS EL LINK PARA NO CONFUNDIR
                    $link = ""; 
                }
            }
        }

                // 3. Dibujar Fondo (Capa 1)
                $pdf->Cell($daily_col_width, $h_fila, '', 0, 0, 'C', true); 

                // 4. Dibujar Símbolos (Capa 2)
                if (!empty($codigo_dia_actual)) {
                    // Pasamos el link a tu función de dibujo actualizada
                    dibujarCeldaAsistencia($pdf, $x_actual, $y_actual, $daily_col_width, $h_fila, $codigo_dia_actual, $link);
                } else {
                    // Si está vacía, ponemos el link transparente manualmente
                    if (!empty($link)) {
                        $pdf->SetXY($x_actual, $y_actual);
                        $pdf->Cell($daily_col_width, $h_fila, '', 0, 0, '', false, $link);
                    }
                    // Restaurar borde si estaba vacía
                    $pdf->SetDrawColor(0,0,0);
                    $pdf->Rect($x_actual, $y_actual, $daily_col_width, $h_fila);
                }

                // Mover cursor para la siguiente fecha
                $pdf->SetXY($x_actual + $daily_col_width, $y_actual);
                
                // --- FIN MODIFICACIÓN ---
    }

    $pdf->SetFillColor($current_row_fill_color[0], $current_row_fill_color[1], $current_row_fill_color[2]); 

    $format_num = function($value, $decimals = 2) {
        return ($value == 0) ? '' : number_format($value, $decimals, '.', ',');
    };

// --- CAMBIO AQUÍ: AUMENTAR TAMAÑO DE FUENTE ---
    // Cambiamos de tamaño 7 a tamaño 9 (o 10 si cabe) solo para los montos
    $pdf->SetFont('Arial', '', 7); 
    // ----------------------------------------------

    $pdf->Cell($w_financial_fixed[0], $h_fila, $format_num($total_salarios_a_mostrar, 2), 1, 0, 'R', true); 
    $pdf->Cell($w_financial_fixed[1], $h_fila, $format_num($total_salario_asuetos_a_mostrar, 2), 1, 0, 'R', true); 
    $pdf->Cell($w_financial_fixed[2], $h_fila, $format_num($total_trabajo_extra_empleado_a_mostrar, 2), 1, 0, 'R', true); 

    if ($DepartamentoEmpresa == '08' || $DepartamentoEmpresa == '09') {
        $noct_display_string_C = $format_num($total_nocturnidad_cantidad_a_mostrar, 0); 
        $noct_display_string_V = $format_num($total_monto_nocturnidad_a_mostrar, 2); 
        $pdf->Cell($w_financial_fixed[3]/2, $h_fila, $noct_display_string_C, 1, 0, 'C', true); 
        $pdf->Cell($w_financial_fixed[3]/2, $h_fila, $noct_display_string_V, 1, 0, 'C', true); 
    } else {
        // Nada
    }

    $he_display_string_C = $format_num($total_horas_extra_cantidad_a_mostrar, 0); 
    $he_display_string_V = $format_num($total_monto_horas_extra_a_mostrar, 2); 
    $pdf->Cell($w_financial_fixed[4]/2, $h_fila, $he_display_string_C, 1, 0, 'C', true); 
    $pdf->Cell($w_financial_fixed[4]/2, $h_fila, $he_display_string_V, 1, 0, 'C', true); 
    $pdf->SetFont('Arial', '', 8); 
    $pdf->Cell($w_financial_fixed[5], $h_fila, $format_num($total_extra_general_a_mostrar, 2), 1, 0, 'R', true); 
    $pdf->Cell($w_financial_fixed[6], $h_fila, $format_num($salario_liquido_final_a_mostrar, 2), 1, 1, 'R', true); 
    
    $row_fill_flag = !$row_fill_flag;
    $pdf->Ln(2); 

    $i++; 
}

$modo = "I"; 
$print_nombre = mb_convert_encoding("Planilla: $departamentoEmpresaTexto - $quincena - $mes.pdf","ISO-8859-1");
$pdf->Output($print_nombre,$modo);
?> 