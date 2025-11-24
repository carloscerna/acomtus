<?php
// ¡INICIO DE SEGURIDAD!
// 1. Iniciar la misma sesión que tu sistema
session_name('Sistema2020');
session_start();

// ruta de los archivos con su carpeta
$path_root=trim($_SERVER['DOCUMENT_ROOT']);
// Incluimos el archivo de funciones y conexi�n a la base de datos
	include($path_root."/acomtus/includes/mainFunctions_conexion.php");

// 2. Comprobar si el usuario está logueado Y tiene el perfil correcto
// (Basado en tu layout-menu.html, '01' es el Administrador)
if (!isset($_SESSION['codigo_perfil']) || $_SESSION['codigo_perfil'] != '01') {
    // Si no es admin, bloquear el acceso
    http_response_code(403); // 403 = Prohibido
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Acceso no autorizado.']);
    exit; // Detener el script
}
// ¡FIN DE SEGURIDAD!
// --- 3. Leer los datos JSON ---
$datos_json = file_get_contents('php://input');
$datos = json_decode($datos_json, true);

// 4. Validar datos (¡MODIFICADO!)
// Ahora también validamos que llegue 'distancia_km'
if (!isset($datos['ruta_id']) || !isset($datos['coordenadas']) || !isset($datos['distancia_km'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos incompletos. Se esperaba ruta_id, coordenadas y distancia_km.']);
    exit;
}

// ¡MODIFICADO! Obtener la nueva variable
$ruta_id = $datos['ruta_id'];
$coordenadas = $datos['coordenadas'];
$distancia_km = $datos['distancia_km']; // <-- ¡NUEVA VARIABLE!


// --- 6. Transacción (¡MODIFICADO!) ---
try {
    $conexion->beginTransaction();

    // -- Paso A: Borrar coordenadas antiguas (igual que antes) --
    $sql_delete = "DELETE FROM trazado_ruta WHERE ruta_id = :ruta_id";
    $stmt_delete = $conexion->prepare($sql_delete);
    $stmt_delete->bindParam(':ruta_id', $ruta_id, PDO::PARAM_INT);
    $stmt_delete->execute();

    // -- Paso B: ¡NUEVO! Actualizar la distancia en la tabla 'catalogo_ruta' --
    $sql_update = "UPDATE catalogo_ruta 
                   SET distancia_km = :distancia 
                   WHERE id_ruta = :ruta_id";
    $stmt_update = $conexion->prepare($sql_update);
    $stmt_update->bindParam(':distancia', $distancia_km);
    $stmt_update->bindParam(':ruta_id', $ruta_id, PDO::PARAM_INT);
    $stmt_update->execute();


    // -- Paso C: Insertar coordenadas nuevas (igual que antes) --
    $sql_insert = "INSERT INTO trazado_ruta (ruta_id, latitud, longitud, orden) 
                   VALUES (:ruta_id, :latitud, :longitud, :orden)";
    $stmt_insert = $conexion->prepare($sql_insert);
    
    $orden = 1;
    foreach ($coordenadas as $coord) {
        $lat = $coord[0];
        $lng = $coord[1];
        $stmt_insert->bindParam(':ruta_id', $ruta_id, PDO::PARAM_INT);
        $stmt_insert->bindParam(':latitud', $lat);
        $stmt_insert->bindParam(':longitud', $lng);
        $stmt_insert->bindParam(':orden', $orden, PDO::PARAM_INT);
        $stmt_insert->execute();
        $orden++;
    }

    // -- Paso D: Confirmar los cambios --
    $conexion->commit();
    
    // 7. Enviar respuesta de éxito (¡MODIFICADO!)
    $num_puntos = $orden - 1;
    echo json_encode([
        'success' => true, 
        'message' => "¡Éxito! Trazado guardado con $num_puntos puntos y distancia actualizada a $distancia_km km."
    ]);

} catch (Exception $e) {
    // -- Paso E: Si algo falló, deshacer cambios --
    $conexion->rollBack();
    
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al guardar la ruta: ' . $e->getMessage()]);
}

?>