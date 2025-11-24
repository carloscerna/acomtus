<?php
// Script INTERMEDIARIO para buscar el codigo_produccion y redirigir al PDF
$path_root=trim($_SERVER['DOCUMENT_ROOT']);
include($path_root."/acomtus/includes/mainFunctions_conexion.php");
include($path_root."/acomtus/includes/funciones.php"); // Asumiendo que cambiaf_a_normal está aquí

$codigo_personal = $_GET['codigo'] ?? null;
$fecha_pdf = $_GET['fecha'] ?? null; // Fecha en formato YYYY-MM-DD

if (!$codigo_personal || !$fecha_pdf) {
    die("Error: Faltan parámetros (código de personal o fecha).");
}

// 1. Buscar el codigo_produccion (id_) en la tabla produccion
$query_busqueda = "SELECT id_ FROM produccion WHERE fecha = '$fecha_pdf' AND codigo_personal = '$codigo_personal' LIMIT 1";

$consulta = $dblink->query($query_busqueda);
$codigo_produccion = null;

if ($consulta && $consulta->rowCount() > 0) {
    $listado = $consulta->fetch(PDO::FETCH_ASSOC);
    $codigo_produccion = $listado["id_"];
}

// 2. Determinar la URL final
$url_base = "/acomtus/php_libs/reportes/Planilla/DetallePorMotorista.php";

if ($codigo_produccion) {
    // Si encontramos el código, redirigimos al PDF con el ID
    $redirect_url = $url_base . "?codigo_produccion=" . $codigo_produccion;
} else {
    // Si NO encontramos el código, redirigimos al PDF con una bandera o valores por defecto
    // Nota: DetallePorMotorista.php debe estar preparado para manejar codigo_produccion=0 o null
    $redirect_url = $url_base . "?codigo_produccion=0&codigo_personal=" . urlencode($codigo_personal) . "&fecha=" . urlencode($fecha_pdf);
}

// Redirigir a una nueva ventana
echo "<script>window.open('{$redirect_url}', '_blank');</script>";
?>