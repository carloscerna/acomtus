<?php
// session_name('demoUI');
// session_start();
// limpiar cache.
clearstatcache();
// Script para ejecutar AJAX
// cambiar a utf-8.
header("Content-Type: text/html;charset=iso-8859-1");
// Insertar y actualizar tabla de usuarios
sleep(0);

// Inicializamos variables de mensajes y JSON
$respuestaOK = false;
$mensajeError = "No se puede ejecutar la aplicación";
$contenidoOK = "";
$lista = "";
$arreglo = array();
$datos = array();
$fila_array = 0;

// ruta de los archivos con su carpeta
$path_root = trim($_SERVER['DOCUMENT_ROOT']);

// Incluimos el archivo de funciones y conexión a la base de datos
include($path_root . "/acomtus/includes/mainFunctions_conexion.php");

// Validar conexión con la base de datos
if ($errorDbConexion == false) {
    // Validamos que existan las variables post
    if (isset($_POST) && !empty($_POST)) {
        
        if (!empty($_POST['accion_buscar'])) {
            $_POST['accion'] = $_POST['accion_buscar'];
        }

        $accion = $_POST['accion'] ?? '';

        // ACCIÓN 1: Obtener catálogo de unidades para el select
        if ($accion === 'obtener_unidades') {
            $query = "SELECT id_, numero_equipo, numero_placa, marca, modelo 
                      FROM public.transporte_colectivo 
                      ORDER BY numero_equipo ASC";
            
            // Ejecutamos el Query con $dblink
            $consulta = $dblink->query($query);

            if ($consulta) {
                // Maneja tanto PDO (fetch) como la API nativa de pg_query
                while ($row = (is_object($consulta) && method_exists($consulta, 'fetch')) ? $consulta->fetch(PDO::FETCH_ASSOC) : pg_fetch_assoc($consulta)) {
                    $arreglo[] = $row;
                }
                $respuestaOK = true;
                $mensajeError = "Unidades cargadas correctamente";
            } else {
                $mensajeError = "Error al consultar el catálogo de unidades";
            }

        // ACCIÓN 2: Consultar la producción/recorrido filtrado por mes y unidad
        } elseif ($accion === 'consultar_recorrido') {
            $periodo = $_POST['periodo'] ?? date('Y-m');
            $id_transporte = $_POST['id_transporte'] ?? 'TODAS';
            $meta_vueltas = isset($_POST['meta_vueltas']) ? (float)$_POST['meta_vueltas'] : 84;

            list($anio, $mes) = explode('-', $periodo);

            $where_unidad = "";
            if ($id_transporte !== 'TODAS' && !empty($id_transporte)) {
                // Sanitización básica para entero
                $id_transporte_clean = (int)$id_transporte;
                $where_unidad = " AND t.id_ = {$id_transporte_clean}";
            }

            // Cruce de información entre transporte_colectivo y produccion
            $query = "SELECT 
                        t.id_ AS id_transporte,
                        t.numero_equipo,
                        t.numero_placa,
                        t.marca,
                        t.modelo,
                        COALESCE(COUNT(DISTINCT p.fecha), 0) AS dias_trabajados,
                        COALESCE(SUM(p.numero_vueltas), 0) AS total_vueltas_mes
                    FROM public.transporte_colectivo t
                    LEFT JOIN public.produccion p 
                           ON p.codigo_transporte_colectivo = t.id_ 
                          AND EXTRACT(YEAR FROM p.fecha) = {$anio} 
                          AND EXTRACT(MONTH FROM p.fecha) = {$mes}
                    WHERE 1=1 {$where_unidad}
                    GROUP BY t.id_, t.numero_equipo, t.numero_placa, t.marca, t.modelo
                    ORDER BY total_vueltas_mes DESC, t.numero_equipo ASC";

            // Ejecutamos el Query con $dblink
            $consulta = $dblink->query($query);

            if ($consulta) {
                while ($row = (is_object($consulta) && method_exists($consulta, 'fetch')) ? $consulta->fetch(PDO::FETCH_ASSOC) : pg_fetch_assoc($consulta)) {
                    $vueltas_actuales = (float)$row['total_vueltas_mes'];
                    $porcentaje = ($meta_vueltas > 0) ? round(($vueltas_actuales / $meta_vueltas) * 100, 1) : 0;
                    
                    $faltante_vueltas = $meta_vueltas - $vueltas_actuales;
                    if ($faltante_vueltas < 0) $faltante_vueltas = 0;
                    
                    $faltante_porcentaje = round(100 - $porcentaje, 1);
                    if ($faltante_porcentaje < 0) $faltante_porcentaje = 0;

                    $row['meta_vueltas'] = $meta_vueltas;
                    $row['porcentaje'] = $porcentaje;
                    $row['faltante_vueltas'] = $faltante_vueltas;
                    $row['faltante_porcentaje'] = $faltante_porcentaje;

                    $arreglo[] = $row;
                }
                $respuestaOK = true;
                $mensajeError = "Consulta ejecutada con éxito";
            } else {
                $mensajeError = "Error al consultar la información de producción";
            }
        }
    } else {
        $mensajeError = 'No se recibieron datos POST';
    }
} else {
    $mensajeError = 'No se puede establecer conexión con la base de datos';
}

// Salida de Array con JSON según estructura nativa ACOMTUS
if (isset($_POST["accion"]) && ($_POST["accion"] === "" or $_POST["accion"] === "BuscarTodosCodigo")) {
    echo json_encode($arreglo); 
} elseif (isset($_POST["accion"]) && ($_POST["accion"] === "BuscarCodigo" or $_POST["accion"] === "GenerarCodigoNuevo" or $_POST["accion"] === "BuscarPorId")) {
    echo json_encode($datos);
} else {
    $salidaJson = array(
        "respuesta" => $respuestaOK,
        "mensaje"   => $mensajeError,
        "contenido" => $arreglo
    );
    echo json_encode($salidaJson);
}
?>