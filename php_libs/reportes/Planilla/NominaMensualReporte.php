<?php
// Incluir el archivo de conexión a la base de datos
include($_SERVER['DOCUMENT_ROOT']."/acomtus/includes/mainFunctions_conexion.php");

header('Content-Type: application/json; charset=utf-8');

$fecha_mes = $_GET['fechaMes'] ?? null;
$fecha_ann = $_GET['fechaAnn'] ?? null;
$departamento_empresa = $_GET['DepartamentoEmpresa'] ?? null; // Nuevo parámetro
$ruta = $_GET['ruta'] ?? null; // Nuevo parámetro

$response = ['data' => []];

if ($fecha_mes && $fecha_ann) {
    try {
        // Consulta base para obtener los datos mensuales de la tabla
        $query = "SELECT 
                    psd.codigo_personal,
                    p.nombres || ' ' || p.apellidos AS nombre_completo,
                    psd.fecha_mes,
                    psd.fecha_ann,
                    psd.salario_bruto_mensual,
                    psd.isss_empleado_mensual,
                    psd.afp_empleado_mensual,
                    psd.renta_empleado_mensual,
                    psd.isss_patronal_mensual,
                    psd.afp_patronal_mensual,
                    psd.salario_neto_mensual,
                    p.codigo_departamento_empresa,
                    cde.descripcion AS departamento_descripcion,
                    p.codigo_ruta,
                    cr.descripcion AS ruta_descripcion
                  FROM personal_salario_deducciones_mensual psd
                  JOIN personal p ON psd.codigo_personal = p.codigo
                  LEFT JOIN catalogo_departamento_empresa cde ON p.codigo_departamento_empresa = cde.codigo
                  LEFT JOIN catalogo_ruta cr ON p.codigo_ruta = cr.codigo
                  WHERE psd.fecha_mes = :fecha_mes AND psd.fecha_ann = :fecha_ann";
        
        // Añadir filtro por departamento si se seleccionó uno (y no es '00' que significa "Todos")
        if ($departamento_empresa && $departamento_empresa != '00') {
            $query .= " AND p.codigo_departamento_empresa = :departamento_empresa";
        }

        // Añadir filtro por ruta SOLO si el departamento es '02' (Motoristas) y se seleccionó una ruta específica
        // Asegúrate de que $ruta no sea '00' o 'Seleccionar...'
        if ($departamento_empresa == '02' && $ruta && $ruta != '00' && $ruta != 'Seleccionar...') {
            $query .= " AND p.codigo_ruta = :ruta";
        }

        // Ordenar los resultados por código de empleado
        $query .= " ORDER BY psd.codigo_personal";
        
        $stmt = $dblink->prepare($query);
        $stmt->bindParam(':fecha_mes', $fecha_mes);
        $stmt->bindParam(':fecha_ann', $fecha_ann);

        if ($departamento_empresa && $departamento_empresa != '00') {
            $stmt->bindParam(':departamento_empresa', $departamento_empresa);
        }
        if ($departamento_empresa == '02' && $ruta && $ruta != '00' && $ruta != 'Seleccionar...') {
            $stmt->bindParam(':ruta', $ruta);
        }

        $stmt->execute();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Asegurarse de que los valores numéricos sean floats y redondear a 2 decimales
            $row['salario_bruto_mensual'] = round((float)$row['salario_bruto_mensual'], 2);
            $row['isss_empleado_mensual'] = round((float)$row['isss_empleado_mensual'], 2);
            $row['afp_empleado_mensual'] = round((float)$row['afp_empleado_mensual'], 2);
            $row['renta_empleado_mensual'] = round((float)$row['renta_empleado_mensual'], 2);
            $row['isss_patronal_mensual'] = round((float)$row['isss_patronal_mensual'], 2);
            $row['afp_patronal_mensual'] = round((float)$row['afp_patronal_mensual'], 2);
            $row['salario_neto_mensual'] = round((float)$row['salario_neto_mensual'], 2);
            
            $response['data'][] = $row;
        }
        
    } catch (PDOException $e) {
        error_log("ERROR en NominaMensualReporte.php: " . $e->getMessage());
        $response = ['error' => 'Error al obtener datos: ' . $e->getMessage()];
    }
} else {
    $response = ['error' => 'Parámetros de fecha (mes y año) son requeridos.'];
}

echo json_encode($response);
?>
