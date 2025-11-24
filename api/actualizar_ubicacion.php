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

// --- 3. Leer los datos JSON que envía el celular ---
$datos = json_decode(file_get_contents('php://input'), true);

// 4. Validar datos
if (!isset($datos['ruta_id']) || !isset($datos['lat']) || !isset($datos['lng'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Datos incompletos. Se esperaba ruta_id, lat y lng.']);
    exit;
}

$ruta_id = $datos['ruta_id'];
$latitud = $datos['lat'];
$longitud = $datos['lng'];


// --- 6. Guardar la ubicación (Lógica "UPSERT") ---
// Intenta INSERTAR. Si la 'ruta_id' ya existe (ON CONFLICT), 
// entonces actualiza (DO UPDATE) la lat, lng y la hora.
$sql = "INSERT INTO ubicacion_en_vivo (ruta_id, latitud, longitud, ultima_actualizacion) 
        VALUES (:ruta_id, :lat, :lng, NOW())
        ON CONFLICT (ruta_id) 
        DO UPDATE SET 
            latitud = EXCLUDED.latitud, 
            longitud = EXCLUDED.longitud, 
            ultima_actualizacion = NOW()";

try {
    $stmt = $conexion->prepare($sql);
    $stmt->bindParam(':ruta_id', $ruta_id, PDO::PARAM_INT);
    $stmt->bindParam(':lat', $latitud);
    $stmt->bindParam(':lng', $longitud);
    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Ubicación actualizada.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error al guardar en BD: ' . $e->getMessage()]);
}
?>