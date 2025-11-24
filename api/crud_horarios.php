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



// --- 3. Lógica de la API (CRUD) ---
$metodo = $_SERVER['REQUEST_METHOD'];

switch ($metodo) {
    // --- ACCIÓN: LEER (Read) ---
    case 'GET':
        // Comprobar si nos piden horarios para una ruta específica
        if (!isset($_GET['ruta_id'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'No se especificó un ID de ruta.']);
            exit;
        }
        $ruta_id = $_GET['ruta_id'];

        $sql = "SELECT id, dia_semana, hora_inicio, hora_fin, frecuencia_minutos 
                FROM horarios 
                WHERE ruta_id = :ruta_id 
                ORDER BY dia_semana, hora_inicio";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':ruta_id', $ruta_id, PDO::PARAM_INT);
        $stmt->execute();
        $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($horarios); // Devolver la lista de horarios
        break;

    // --- ACCIÓN: CREAR (Create) ---
    case 'POST':
        // Leer los datos JSON enviados desde el admin.js
        $datos = json_decode(file_get_contents('php://input'), true);

        // Validar que tengamos todos los datos
        if (!isset($datos['ruta_id']) || !isset($datos['dia_semana']) || !isset($datos['hora_inicio']) || !isset($datos['hora_fin']) || !isset($datos['frecuencia'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Datos incompletos.']);
            exit;
        }

        // Insertar los datos en la base de datos
        $sql = "INSERT INTO horarios (ruta_id, dia_semana, hora_inicio, hora_fin, frecuencia_minutos) 
                VALUES (:ruta_id, :dia_semana, :hora_inicio, :hora_fin, :frecuencia)";
        
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':ruta_id', $datos['ruta_id'], PDO::PARAM_INT);
        $stmt->bindParam(':dia_semana', $datos['dia_semana']);
        $stmt->bindParam(':hora_inicio', $datos['hora_inicio']);
        $stmt->bindParam(':hora_fin', $datos['hora_fin']);
        $stmt->bindParam(':frecuencia', $datos['frecuencia'], PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Horario guardado con éxito.']);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al guardar el horario.']);
        }
        break;
        
    // (Aquí podríamos añadir 'PUT' para Actualizar y 'DELETE' para Borrar más adelante)
    default:
        http_response_code(405); // Método no permitido
        echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
        break;
}
?>