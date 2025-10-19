<?php
// Configuración y conexión
date_default_timezone_set('America/El_Salvador');
setlocale(LC_TIME,'es_SV');
$path_root=trim($_SERVER['DOCUMENT_ROOT']);
include($path_root."/acomtus/includes/mainFunctions_conexion.php");

header('Content-Type: application/json');

// Variables de entrada
$fechaMes = $_GET["fechaMes"] ?? null;
$fechaAnn = $_GET["fechaAnn"] ?? null;
$quincena = $_GET["quincena"] ?? null;
$DepartamentoEmpresa = $_GET["DepartamentoEmpresa"] ?? null;
$ruta = $_GET["ruta"] ?? null;

$response = ["data" => []];

if ($DepartamentoEmpresa !== '02' || !$fechaMes || !$fechaAnn || !$quincena) {
    echo json_encode($response);
    exit;
}

// ===================================================================
// LISTA A: CÓDIGOS DE ASISTENCIA QUE NO REQUIEREN CONTROL (ACTUALIZADA)
// ===================================================================
$codigos_excluidos_produccion = [
    '41344444', // Descanso (D)
    '4344444',  // Permiso Personal (PP)
    '4244444',  // Seguro Social (ISSS)
    '4144444',  // Sin Punteo (SP) - NUEVO
    '41144444', // Vacación (Parciales) - NUEVO
    '41044444', // Castigo (C)
    '4444444',  // Falta (F)
    '41444144', // Trabajo Descanso 4h (TD 4h)
    // Se excluye 2144444 (Asueto Normal) en la lógica de discrepancia.
];

// ===================================================================
// LISTA B: CÓDIGOS DE CARGO QUE NO REQUIEREN CONTROL
// ===================================================================
$cargos_excluidos_produccion = [
    '28', // Jefe de Línea
    '17', // Despacho
];

// ===================================================================
// PRECARGA 1: MAPA DE CÓDIGOS DE ASISTENCIA A NOMBRES DE IMAGEN
// ===================================================================
$jornada_imagenes_map = [];
$consulta_imagenes = $dblink->query("SELECT codigo, descripcion FROM catalogo_jornada_imagenes");
while ($row_img = $consulta_imagenes->fetch(PDO::FETCH_ASSOC)) {
    $jornada_imagenes_map[trim($row_img['codigo'])] = trim($row_img['descripcion']);
}
$image_base_url = "/acomtus/img/Catalogo Jornada/"; // URL base para JS


// Determinar el rango de fechas para la quincena
$fecha_periodo_inicio = '';
$fecha_periodo_fin = '';

if ($quincena == 'Q1') {
    $fecha_periodo_inicio = $fechaAnn . '-' . $fechaMes . '-01';
    $fecha_periodo_fin = $fechaAnn . '-' . $fechaMes . '-15';
} elseif ($quincena == 'Q2') {
    $fecha_periodo_inicio = $fechaAnn . '-' . $fechaMes . '-16';
    $fecha_periodo_fin = date('Y-m-t', strtotime($fechaAnn . '-' . $fechaMes . '-01'));
}

// 1. Obtener todos los Motoristas (filtrados por ruta si aplica) E INCLUIR SU CÓDIGO DE CARGO
$query_motoristas = "SELECT p.codigo, p.nombres, p.apellidos, p.codigo_cargo 
                     FROM personal p 
                     WHERE p.codigo_departamento_empresa = '02' AND p.codigo_estatus = '01'";
if ($ruta != '00') {
    $query_motoristas .= " AND p.codigo_ruta = '$ruta'";
}
$stmt_motoristas = $dblink->query($query_motoristas);
$motoristas = $stmt_motoristas->fetchAll(PDO::FETCH_ASSOC);
$codigos_motoristas = array_column($motoristas, 'codigo');

if (empty($codigos_motoristas)) {
    echo json_encode($response);
    exit;
}

$codigos_motoristas_str = "'" . implode("','", $codigos_motoristas) . "'";

// 2. Obtener los códigos de ASISTENCIA PUNTEADA (personal_asistencia)
$query_asistencia = "
    SELECT 
        pa.codigo_personal, 
        pa.fecha,
        -- Construir el código combinado sin la hora extra todavía
        COALESCE(TRIM(pa.codigo_jornada::TEXT), '') || COALESCE(TRIM(pa.codigo_tipo_licencia::TEXT), '') || 
        COALESCE(TRIM(pa.codigo_jornada_asueto::TEXT), '') || COALESCE(TRIM(pa.codigo_jornada_vacaciones::TEXT), '') || 
        COALESCE(TRIM(pa.codigo_jornada_descanso::TEXT), '') || COALESCE(TRIM(pa.codigo_jornada_e_4h::TEXT), '') || 
        COALESCE(TRIM(pa.codigo_jornada_nocturna::TEXT), '') AS codigo_base,
        pa.hora_extra
    FROM 
        personal_asistencia pa
    WHERE 
        pa.codigo_personal IN ($codigos_motoristas_str)
        AND pa.fecha BETWEEN '$fecha_periodo_inicio' AND '$fecha_periodo_fin'
";
$stmt_asistencia = $dblink->query($query_asistencia);
$asistencia_punteada = [];
while ($row = $stmt_asistencia->fetch(PDO::FETCH_ASSOC)) {
    // Lógica para construir el código combinado FINAL (incluyendo hora extra si existe)
    $codigo_combinado_final = trim($row['codigo_base']);
    $hora_extra = (float)($row['hora_extra'] ?? 0);
    
    if ($hora_extra > 0) {
        // Asumiendo la lógica de NominaAsistenciaCalcular para adjuntar HE
        $codigo_combinado_final .= str_replace('.', '', (string)$hora_extra); 
    }
    
    $asistencia_punteada[trim($row['codigo_personal'])][trim($row['fecha'])] = $codigo_combinado_final;
}

// 3. Obtener los registros de CONTROL DE PRODUCCIÓN
$query_produccion = "
    SELECT 
        codigo_personal, 
        fecha
    FROM 
        produccion
    WHERE 
        codigo_personal IN ($codigos_motoristas_str)
        AND fecha BETWEEN '$fecha_periodo_inicio' AND '$fecha_periodo_fin'
";
$stmt_produccion = $dblink->query($query_produccion);
$controles_registrados = [];
while ($row = $stmt_produccion->fetch(PDO::FETCH_ASSOC)) {
    $controles_registrados[trim($row['codigo_personal'])][trim($row['fecha'])] = true;
}

// 4. Lógica de Discrepancia (Asistencia SÍ pero Producción NO)
$dias_semana_es = ['Sun' => 'Domingo', 'Mon' => 'Lunes', 'Tue' => 'Martes', 'Wed' => 'Miércoles', 'Thu' => 'Jueves', 'Fri' => 'Viernes', 'Sat' => 'Sábado'];
$motoristas_map = array_column($motoristas, null, 'codigo');

foreach ($asistencia_punteada as $codigo_personal => $fechas_asistencia) {
    
    $empleado_info = $motoristas_map[$codigo_personal] ?? null;
    $codigo_cargo = $empleado_info['codigo_cargo'] ?? null;
    $nombre_completo = trim($empleado_info['nombres']) . ' ' . trim($empleado_info['apellidos']);

    // Exclusión por CARGO
    if (in_array($codigo_cargo, $cargos_excluidos_produccion)) {
        continue;
    }

    foreach ($fechas_asistencia as $fecha => $codigo_asistencia) {
        $codigo_asistencia = trim($codigo_asistencia);
        
        // Criterio 1: Excluir por Código de Asistencia (Descanso, ISSS, Falta, etc.)
        if (in_array($codigo_asistencia, $codigos_excluidos_produccion) || strpos($codigo_asistencia, '2144444') !== false) {
             continue; // Saltar este día.
        }

        // Criterio 2: Si el día tiene una asistencia que IMPLICA TRABAJO (y NO fue excluido) Y NO tiene Control
        if (!isset($controles_registrados[$codigo_personal][$fecha])) {
            $dia_semana_en = date('D', strtotime($fecha));
            
            // Obtener el nombre del archivo de imagen para el código
            $image_filename = $jornada_imagenes_map[$codigo_asistencia] ?? 'default.png'; // Usar default si no se encuentra
            
            // Construir la etiqueta HTML de la imagen
            $imagen_tag = "<img src='{$image_base_url}{$image_filename}' alt='{$codigo_asistencia}' style='width: 30px; height: 30px; border: 1px solid #ccc; border-radius: 3px;'>";
            
            $response["data"][] = [
                'codigo_personal' => $codigo_personal,
                'nombre_completo' => $nombre_completo,
                'fecha' => date('d/m/Y', strtotime($fecha)), // Formato dd/mm/yyyy
                'dia_semana' => $dias_semana_es[$dia_semana_en],
                'asistencia_punteada' => $imagen_tag, // Se envía la etiqueta HTML de la imagen
                'razon_no_control' => 'Falta Control de Producción'
            ];
        }
    }
}

echo json_encode($response);
?>