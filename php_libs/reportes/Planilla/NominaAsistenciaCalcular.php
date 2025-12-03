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

function dibujarCeldaAsistencia($pdf, $x, $y, $w, $h, $codigo) {
    // --- 1. CONFIGURACIÓN BASE ---
    $borde_color = [50, 50, 200];     // Azul
    $relleno_color = [255, 255, 255]; // Blanco
    
    // Configuración del Símbolo Central (Por defecto: Punto Negro)
    $fuente_actual = 'ZapfDingbats'; 
    $simbolo_central = 'l';           // 'l' es el punto en ZapfDingbats
    $texto_color = [0, 0, 0];         // Negro
    $tamano_fuente_central = 5;      // Tamaño original
    $ajuste_y_simbolo = 1;            // Para subir/bajar

// --- NUEVA VARIABLE DE CONFIGURACIÓN ---
    $tamano_fuente_esquinas = 6; // Valor por defecto (pequeño)

    // Variables de Esquinas
    $texto_sup_der = ''; // Arriba Derecha
    $texto_inf_der = ''; // Abajo Derecha
    $texto_inf_izq = ''; // --- NUEVO: Abajo Izquierda ---

    // --- 2. ANÁLISIS DEL CÓDIGO (Tu Catálogo) ---

    switch ($codigo) {
        // GRUPO: FALTAS Y CASTIGOS (Rojo)
        case '4444444': 
        case 'FALTA':
        case 'FALTA_GENERICA':
        case '41044444': 
            $fuente_actual = 'Arial'; 
            $simbolo_central = ($codigo == '41044444') ? 'C' : 'F';
            $texto_color = [200, 0, 0]; // Rojo
            $tamano_fuente_central = 10; 
            $ajuste_y_simbolo = 3;
            break;

        // GRUPO: DESCANSOS, VACACIONES (Verde)
        case '41344444': // Descanso
        case '41144444': // Vacacion
        case '41241444': // Trabajo Vacacion
             $fuente_actual = 'Arial';
             if ($codigo == '41344444') $simbolo_central = 'D';
             else $simbolo_central = 'V'; // O TV
             $texto_color = [0, 128, 0]; // Verde
             $tamano_fuente_central = 10; 
             $ajuste_y_simbolo = 3;
             break;

        // GRUPO: ISSS y PERMISOS (Azul)
        case '4244444': // ISSS
        case '4344444': // Permiso
            $fuente_actual = 'Arial';
            $texto_color = [0, 0, 200]; // Azul
            if ($codigo == '4244444') {
                $simbolo_central = 'ISSS';
                $tamano_fuente_central = 6;
                $ajuste_y_simbolo = 1.5;
            } else {
                $simbolo_central = 'PP';
                $tamano_fuente_central = 9;
                $ajuste_y_simbolo = 3;
            }
            break;
// =========================================================
        // GRUPO: TRABAJO ASUETO (TA) - VARIAS TANDAS
        // =========================================================
        // Estilo: Fondo Amarillo, Texto "TA" Rojo
        
        case '41614444': // Trabajo Asueto Media Tanda (4H)
        case '41624444': // Trabajo Asueto Una Tanda (1T)
        case '41634444': // Trabajo Asueto Una Tanda y Media (1.5T)
            $fuente_actual = 'Arial';
            $simbolo_central = 'TA';
            
            // Colores: Fondo Amarillo [255, 255, 0], Texto Rojo [200, 0, 0]
            $relleno_color = [255, 255, 0]; 
            $texto_color = [200, 0, 0];     
            
            $tamano_fuente_central = 12; 
            $ajuste_y_simbolo = 2;

            // Diferenciamos qué texto poner en la esquina inferior derecha
            if ($codigo == '41614444') {
                $texto_inf_der = '4H';   // Media Tanda
            } elseif ($codigo == '41624444') {
                $texto_inf_der = '1T';   // Una Tanda
            } elseif ($codigo == '41634444') {
                $texto_inf_der = '1.5T'; // Tanda y Media
            }
            break;
        // GRUPO: ASUETOS (Fondo Gris)
        // =========================================================
        // GRUPO: TRABAJO ASUETO (TA) CON NOCTURNIDAD (Amarillo)
        // =========================================================
        case '41944445': // Asueto + Nocturnidad (TA + N)
        case '41924445': // Asueto + 1T + Nocturnidad (TA + N + 1T)
            $fuente_actual = 'Arial';
            $simbolo_central = 'TA';
            
            // Colores: Fondo Amarillo, Texto Rojo
            $relleno_color = [255, 255, 0]; // Amarillo
            $texto_color = [200, 0, 0];     // Rojo
            
            $tamano_fuente_central = 10; 
            $ajuste_y_simbolo = 1;

            // Configuración de Esquinas
            $texto_inf_izq = 'N'; // La "N" va a la Izquierda (como en tu imagen)

            // Si es el código que lleva Tanda Extra, agregamos "1T" a la derecha
            if ($codigo == '41924445') {
                $texto_inf_der = '1T';
            }
            break;
        case '41644444':
            $fuente_actual = 'Arial'; 
            $simbolo_central = 'A';
            // Colores: Fondo Amarillo, Texto Rojo
            $relleno_color = [255, 255, 0]; // Amarillo
            $texto_color = [200, 0, 0];     // Rojo

            $tamano_fuente_central = 10; 
            $ajuste_y_simbolo = 3;
            break;

        // GRUPO: MEDIAS TANDAS (4H)
        case '1144444': 
            $fuente_actual = 'Arial'; 
            $simbolo_central = '4H';
            $tamano_fuente_central = 9; 
            $ajuste_y_simbolo = 3;
            break;

        // =========================================================
        // GRUPO: TRABAJO DESCANSO ASUETO (TDA)
        // =========================================================
        // Estilo: Fondo Amarillo, Texto "TDA" Rojo
        
        case '41544444': // TDA (Genérico)
        case '41514444': // TDA Media Tanda (4H)
        case '41524444': // TDA Una Tanda (1T)
        case '41534444': // TDA Una Tanda y Media (1.5T)
            $fuente_actual = 'Arial';
            $simbolo_central = 'TDA';
            
            // Colores: Fondo Amarillo [255, 255, 0], Texto Rojo [200, 0, 0]
            $relleno_color = [255, 255, 0]; 
            $texto_color = [200, 0, 0];     
            
            // Ajustamos fuente un poco más chica para que quepan las 3 letras
            $tamano_fuente_central = 9; 
            $ajuste_y_simbolo = 1;

            // Diferenciamos la esquina inferior derecha
            if ($codigo == '41514444') {
                $texto_inf_der = '4H';
            } elseif ($codigo == '41524444') {
                $texto_inf_der = '1T';
            } elseif ($codigo == '41534444') {
                $texto_inf_der = '1.5T';
            }
            break;

        // =========================================================
        // GRUPO: DESCANSO ASUETO (DA)
        // =========================================================
        // Estilo: Fondo Amarillo, Texto "DA" Verde (Para diferenciar del TA rojo)
        
        case '41744444': // Descanso Asueto
            $fuente_actual = 'Arial';
            $simbolo_central = 'DA';
            
            // Colores: Fondo Amarillo
            $relleno_color = [255, 255, 0]; 
            // Usamos Verde Oscuro para la "D" predominante
            $texto_color = [0, 128, 0];      
            
            $tamano_fuente_central = 10; 
            $ajuste_y_simbolo = 1;
            break;
        // =========================================================
        // GRUPO: PUNTO NORMAL + HORAS EXTRA (HE)
        // =========================================================
        // Nota: No cambiamos $simbolo_central porque ya es el Punto (ZapfDingbats) por defecto.
        // Solo definimos el texto de la esquina.

        case '21444444': // ID 41: Punto + 4HE
            $texto_sup_der = '4 HE';
            break;

        case '21444443': // ID 42: Punto + 3HE (Por si lo usas)
            $texto_sup_der = '3 HE';
            break;

        case '21444442': // ID 43: Punto + 2HE
            $texto_sup_der = '2 HE';
            break;

        case '21444441': // ID 44: Punto + 1HE
            $texto_sup_der = '1 HE';
            break;

        // =========================================================
        // GRUPO: SIN JORNADA / SIN PUNTEO (0H)
        // =========================================================
        // Estilo: Fondo Blanco, Texto "0H" Negro
        // Representa la imagen "0H SIN PUNTEO ASISTENCIA"
        
        case '4144444': // Sin Jornada
            $fuente_actual = 'Arial';
            $simbolo_central = '0H';
            
            // Colores: 
            // Fondo Gris Claro [225, 225, 225] (Diferente al blanco y al amarillo)
            $relleno_color = [225, 225, 225]; 
            $texto_color = [0, 0, 0];     // Negro
            
            $tamano_fuente_central = 10; 
            $ajuste_y_simbolo = 2;
            
            // Opcional: Si quieres ser más específico, puedes agregar "S/J" (Sin Jornada)
            // en la esquina inferior derecha descomentando la siguiente línea:
            // $texto_inf_der = 'S/J'; 
            break;
        default:
            // Por defecto se queda con ZapfDingbats (Punto)
            break;
    }

    // --- 3. LÓGICA ESPECÍFICA (Ajustes de posición) ---
    
    // Detectar HE genérico
    if (strpos($codigo, '_HE') !== false) {
        $partes = explode('_HE', $codigo);
        $texto_sup_der = end($partes) . " HE";
    } 
    // EL CASO ESPECIAL QUE PEDISTE: 4H + N + 1T
    elseif ($codigo == '1144425') {
         $fuente_actual = 'Arial'; 
         $simbolo_central = '4H';
         $tamano_fuente_central = 9;
         
         // A. SUBIR EL CENTRO (Valor negativo sube)
         $ajuste_y_simbolo = 2; 

         // B. SEPARAR LAS ESQUINAS
         $texto_inf_izq = 'N';  // Izquierda
         $texto_inf_der = '1T'; // Derecha

         // --- AQUÍ AUMENTAS EL TAMAÑO SOLO PARA ESTE CÓDIGO ---
         $tamano_fuente_esquinas = 6; // Antes era 4.5, ahora será más grande
    }
    
    // Lógica de Nocturnidad genérica (para otros códigos que no sean el especial)

// Lógica de Nocturnidad genérica
    // Excluimos los códigos que ya configuramos manualmente dentro del switch
    $excepciones_nocturnidad = ['1144425', '41944445', '41924445'];

    if (substr($codigo, -1) == '5' && !in_array($codigo, $excepciones_nocturnidad)) {
        $tamano_fuente_esquinas = 6; // Antes era 4.5, ahora será más grande
        $texto_inf_izq = trim($texto_inf_izq . " N");
    }

    // --- 4. DIBUJO FINAL ---

    // Fondo y Borde
    $pdf->SetFillColor($relleno_color[0], $relleno_color[1], $relleno_color[2]);
    $pdf->SetDrawColor($borde_color[0], $borde_color[1], $borde_color[2]);
    $pdf->SetLineWidth(0.3);
    $pdf->Rect($x, $y, $w, $h, 'DF'); 

    // Símbolo Central
    if (!empty($simbolo_central)) {
        $pdf->SetTextColor($texto_color[0], $texto_color[1], $texto_color[2]);
        
        // Configurar fuente
        $style = ($fuente_actual == 'Arial') ? 'B' : '';
        $pdf->SetFont($fuente_actual, $style, $tamano_fuente_central);
        
        // Cálculo de posición Y
        $pos_y_centro = $y + ($h / 2) - ($tamano_fuente_central / 4) + $ajuste_y_simbolo;
        
        // Ajuste extra si es el punto de ZapfDingbats (suele quedar alto)
        if ($fuente_actual == 'ZapfDingbats') {
            $pos_y_centro += 1; 
        }

        $pdf->SetXY($x, $pos_y_centro);
        $pdf->Cell($w, 0, ($simbolo_central), 0, 0, 'C');
    }

    // Textos de Esquinas (Siempre Arial Negro Pequeño)
    $pdf->SetTextColor(0, 0, 0); 
    $pdf->SetFont('Arial', 'B', 4.5);

    // Esquina Superior Derecha
    if (!empty($texto_sup_der)) {
        // 1. CAMBIAR TAMAÑO
        // Aquí cambias el tamaño solo para este texto.
        // Antes era 4.5 (que es el default de abajo). Prueba con 5, 5.5 o 6.
        $pdf->SetFont('Arial', 'B', 6); 

        // 2. AJUSTAR POSICIÓN VERTICAL (Y)
        // El + 1 es qué tan abajo está del borde superior.
        // Si lo quieres más pegado al techo, pon + 0.5. Si lo quieres más abajo, pon + 1.5
        $pdf->SetXY($x, $y + 1.5); 
        
        // 3. MOVER MÁS A LA DERECHA (X)
        // El truco aquí es el ancho de la celda ($w).
        // Actualmente dice ($w - 0.5). El 0.5 es el margen derecho.
        // Para pegarlo MÁS a la derecha, reduce ese número (ej: $w - 0.2).
        $pdf->Cell($w - 0.2, 0, $texto_sup_der, 0, 0, 'R'); 
    }

// --- AQUÍ USAMOS LA VARIABLE ---
    $pdf->SetFont('Arial', 'B', $tamano_fuente_esquinas); 
    // -------------------------------

// --- CONFIGURACIÓN DE MÁRGENES ---
    // Entre más pequeño este número, más pegado al borde estará el texto.
    // 0.5 = Normal
    // 0.2 = Muy pegado (Lo que buscas)
    $margen_lateral = 0.1; 
    // ---------------------------------

    // Esquina Inferior Derecha (Para el "1T")
    if (!empty($texto_inf_der)) {
        $pdf->SetXY($x, $y + $h - 1); 
        // Restamos el margen para que se pegue al borde derecho
        $pdf->Cell($w - $margen_lateral, 0, $texto_inf_der, 0, 0, 'R');
    }

    // Esquina Inferior Izquierda (Para la "N")
    if (!empty($texto_inf_izq)) {
        // Sumamos el margen a X para separarlo apenas del borde izquierdo
        $pdf->SetXY($x + $margen_lateral, $y + $h - 1); 
        $pdf->Cell($w - $margen_lateral, 0, $texto_inf_izq, 0, 0, 'L'); 
    }
}


// --- FUNCIÓN AUXILIAR ---
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
    $asueto_worked_codes = ['41614444' => $salario_diario / 2, '41624444' => $salario_diario, '41634444' => $salario_diario + ($salario_diario / 2)];
    $trabajo_descanso_codes = ['41444144' => $salario_diario / 2, '41444244' => $salario_diario, '41444344' => $salario_diario + ($salario_diario / 2),
        '11444144' => $salario_diario * 2 
    ];
    $trabajo_vacacion_codes = ['41241444' => $salario_diario / 2, '41242444' => $salario_diario, '41243444' => $salario_diario + ($salario_diario / 2)];
    $trabajo_descanso_asueto_codes = [
        '41744444' => $salario_diario, '41514444' => $salario_diario / 2, '41524444' => $salario_diario, '41534444' => $salario_diario + ($salario_diario / 2)];
    $nocturnidad_base_value = 0.57;
    $nocturnidad_codes_specific = [
        '2144445' => true, '1144445' => true, '1144425' => true, '11444450' => true, '2124445' => true, '41242445' => true, '41241445' => true
    ];
    $fixed_extra_codes = [
        '1144424' => $salario_diario, '3144444' => $salario_diario / 2,
        '1144425' => $salario_diario       // NUEVO: Agrega 1 Tanda (8H) a Extras
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
    private $day_names_spanish = [
        'Sun' => 'Dom', 'Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mié', 'Thu' => 'Jue', 'Fri' => 'Vie', 'Sat' => 'Sáb'
    ];

    function Header() {
        global $fecha_periodo_inicio, $fecha_periodo_fin, $departamentoEmpresaTexto, $RutaText, $quincena, $rango_fechas, $DepartamentoEmpresa;
        global $_SESSION, $persona_responsable; 

        $img = $_SERVER['DOCUMENT_ROOT'].'/acomtus/img/'.$_SESSION['logo_uno'];
        if (file_exists($img)) {
            $this->Image($img,5,4,24,24);
        } else {
            $this->SetXY(5,4);
            $this->Cell(24,24,'[LOGO]',1,0,'C');
        }
        
        $this->SetFont('Arial','B',14);
        $this->SetXY(30,5);
        $this->Cell(100,7,mb_convert_encoding($_SESSION["nombre_institucion"],"ISO-8859-1"),0,1,"L",false);
        
        $reporte_trabajo_display = ('Reporte de trabajo correspondiente a la quincena del ');
        $reporte_trabajo_display .= date('d', strtotime($fecha_periodo_inicio)) . ' al ' . date('d', strtotime($fecha_periodo_fin)) . ' de ' . (strftime('%B', strtotime($fecha_periodo_inicio))) . ' de ' . date('Y', strtotime($fecha_periodo_inicio));
        
        $reporte_ruta_display = ($departamentoEmpresaTexto);
        if (!empty($RutaText) && $RutaText != '00' && $RutaText != 'Seleccionar...' && $departamentoEmpresaTexto == 'Motorista') { 
            $reporte_ruta_display .= (' (Ruta: ') . ($RutaText) . (')');
        }else{
            $reporte_ruta_display = $departamentoEmpresaTexto;
        }

        $this->SetFont('Arial','B',11);
        $this->SetX(30);
        $this->Cell(100,6, $reporte_trabajo_display,0,1,"L",false);
        
        $this->SetX(30);
        $this->Cell(100,6, $reporte_ruta_display,0,1,"L",false);
        
        $this->SetFont('Arial','B',9);
        $this->SetX(30);
        $this->Cell(130,6,mb_convert_encoding("Responsable del Punteo: " . ($persona_responsable ?? 'N/A'),"ISO-8859-1"),0,0,"L",false);
        $this->Cell(4,6,"",0,0,"L",false); 
        $this->Ln(5); 

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
    
    $nombre_completo = trim(($nombres_empleado . ' ' . $apellidos_empleado));

    // --- 1. CÁLCULO DE ARRASTRE (CARRY OVER) - UNA SOLA VEZ ---
    $deductible_events_carry_over = [];
    $fecha_inicio_dt = new DateTime($fecha_periodo_inicio);
    $day_of_week_num = (int)$fecha_inicio_dt->format('N'); 

    if ($day_of_week_num > 1) {
        $week_start_dt = (clone $fecha_inicio_dt)->modify('last monday');
        $week_start_str = $week_start_dt->format('Y-m-d');
        $check_date_dt = (clone $fecha_inicio_dt)->modify('-1 day');

         while ($check_date_dt >= $week_start_dt) {
            $fecha_str = $check_date_dt->format('Y-m-d');
            
            // Consulta optimizada
            $stmt_check = $dblink->prepare("SELECT codigo_jornada, codigo_tipo_licencia, codigo_jornada_asueto FROM personal_asistencia WHERE codigo_personal = :codigo AND fecha = :fecha");
            $stmt_check->bindParam(':codigo', $codigo_personal);
            $stmt_check->bindParam(':fecha', $fecha_str);
            $stmt_check->execute();
            $asistencia_anterior = $stmt_check->fetch(PDO::FETCH_ASSOC);

            $codigo_anterior_combinado = '';
            if ($asistencia_anterior) {
                $codigo_anterior_combinado = trim($asistencia_anterior['codigo_jornada'] ?? '') . trim($asistencia_anterior['codigo_tipo_licencia'] ?? '') . trim($asistencia_anterior['codigo_jornada_asueto'] ?? '');
            }

            $deductible_codes_check = ['41044444', '4444444'];
            if (in_array(trim($codigo_anterior_combinado), $deductible_codes_check)) {
                $deductible_events_carry_over[$week_start_str] = [
                    'has_deductible_event' => true
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
    $pdf->Cell($w_initial_fixed[0], $h_fila, $i, 1, 0, 'C', true); 
    // --- CAMBIO AQUÍ: AUMENTAR TAMAÑO DE FUENTE ---
    // Cambiamos de tamaño 7 a tamaño 9 (o 10 si cabe) solo para los montos
    $pdf->SetFont('Arial', '', 8); 
    // ----------------------------------------------
    $pdf->Cell($w_initial_fixed[1], $h_fila, $codigo_personal, 1, 0, 'L', true); 
    // --- CAMBIO AQUÍ: AUMENTAR TAMAÑO DE FUENTE ---
    // Cambiamos de tamaño 7 a tamaño 9 (o 10 si cabe) solo para los montos
    $pdf->SetFont('Arial', '', 7); 
    // ----------------------------------------------
    $pdf->Cell($w_initial_fixed[2], $h_fila, $nombre_completo, 1, 0, 'L', true); 

    foreach ($rango_fechas as $fecha_actual) {
        // Necesitamos recuperar el código del día para saber qué dibujar
        // Como 'daily_details' solo guardaba la imagen, recuperamos el código nuevamente
        // O mejor aún, guarda el código en $daily_attendance_details cuando lo procesas arriba.

        // OPCIÓN RÁPIDA: Recalcularlo aquí (o usa la variable si la guardaste)
        $codigo_dia_actual = buscarCodigoDeAsistencia($dblink, $codigo_personal, $fecha_actual, $asistencia_por_empleado_y_fecha);
        if (empty($codigo_dia_actual) && !isset($FechaDescripcionAsueto[$fecha_actual])) {
             $codigo_dia_actual = 'FALTA'; // O vacío
        }

        // =========================================================
        // INYECCIÓN VISUAL DE HORAS EXTRA (Solo para Dept 02 y 03)
        // =========================================================
        // Verificamos si es Motorista (02) o Revisador (03)
        if ($codigo_departamento_empleado == '02' || $codigo_departamento_empleado == '03') {
            
            // Buscamos el dato crudo en el array de asistencia
            $dato_asistencia = $asistencia_por_empleado_y_fecha[$codigo_personal][$fecha_actual] ?? null;
            
            if ($dato_asistencia) {
                $horas_extra = (float)($dato_asistencia['hora_extra'] ?? 0);
                
                // Si tiene horas extra, modificamos el código SOLO PARA EL DIBUJO
                // Le agregamos el sufijo "_HE" + la cantidad.
                // Ejemplo: si el código era "2144444" y tiene 4 horas, se convierte en "2144444_HE4"
                if ($horas_extra > 0) {
                    $codigo_dia_actual .= '_HE' . $horas_extra;
                }
            }
        }
        // =========================================================

        // Guardamos posición actual
        $x_actual = $pdf->GetX();
        $y_actual = $pdf->GetY();
        
        // 1. Dibujamos la celda de fondo (blanca o gris si es fin de semana)
        $pdf->Cell($daily_col_width, $h_fila, '', 0, 0, 'C', true);
        
        // 2. Llamamos a nuestra función de dibujo vectorial sobre la celda
        // Solo dibujamos si hay código y no es un día vacío futuro
        if (!empty($codigo_dia_actual)) {
                dibujarCeldaAsistencia($pdf, $x_actual, $y_actual, $daily_col_width, $h_fila, $codigo_dia_actual);
            } else {
                $pdf->SetDrawColor(0,0,0);
                $pdf->Rect($x_actual, $y_actual, $daily_col_width, $h_fila);
            }

        // Movemos el cursor para la siguiente celda manualmente porque usamos Rect y SetXY
        $pdf->SetXY($x_actual + $daily_col_width, $y_actual);
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