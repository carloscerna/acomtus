<?php
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);
// Incluimos el archivo de funciones y conexin a la base de datos
    include($path_root."/acomtus/includes/mainFunctions_conexion.php");
// VARIABLES DE L POSRT
    $codigo_ruta = $_REQUEST["codigo_ruta"];
    $codigo_cargo = $_REQUEST["codigo_cargo"]; // Código del departamento
//
// Inicializando el array para la respuesta
    $datos = array();
    $fila_array = 0;
    
// --- 1. CONSULTA PARA OBTENER EL RESPONSABLE (SE MANTIENE) ---
    if($codigo_cargo == "02"){
        $query_responsable = "SELECT p.codigo, p.apellidos, p.nombres,
        btrim(p.nombres || CAST(' ' AS VARCHAR) || p.apellidos) as nombre_personal
        FROM personal p
            INNER JOIN usuarios u ON u.codigo_personal = p.codigo  
                WHERE p.codigo_estatus = '01' and u.codigo_ruta = '$codigo_ruta' 
                LIMIT 1"; // Limitar a 1 responsable por ruta/depto para este campo.
    }else{
        $query_responsable = "SELECT p.codigo, p.apellidos, p.nombres,
                btrim(p.nombres || CAST(' ' AS VARCHAR) || p.apellidos) as nombre_personal
                FROM personal p
                    INNER JOIN usuarios u ON u.codigo_personal = p.codigo  
                        WHERE p.codigo_estatus = '01' and u.codigo_departamento_empresa = '$codigo_cargo'
                        LIMIT 1"; // Limitar a 1 responsable por depto.
    }
    
// Ejecutamos el Query del Responsable.
    $consulta_responsable = $dblink -> query($query_responsable);
    $codigo_responsable = "Ninguno";
    $nombre_responsable = "";

    if($consulta_responsable -> rowCount() != 0){
        $listado = $consulta_responsable -> fetch(PDO::FETCH_BOTH);
        $codigo_responsable = trim($listado['codigo']); 
        $nombre_responsable = trim($listado['nombre_personal']);
        $CodigoRutaResponsable = $codigo_responsable . "-" . $nombre_responsable;
    }
    else{
        $CodigoRutaResponsable = "Ninguno";
    }

// --- 2. CONSULTA PARA OBTENER EL TOTAL DE EMPLEADOS EN EL FILTRO ---
    $query_total_empleados = "SELECT COUNT(codigo) AS total FROM personal WHERE codigo_estatus = '01' ";
    
    if ($codigo_cargo == '02' && $codigo_ruta != '999' && $codigo_ruta != '00') {
        // Si es Motorista y hay ruta específica (cambio de lstRuta)
        $query_total_empleados .= " AND codigo_departamento_empresa = '02' AND codigo_ruta = '$codigo_ruta'";
    } elseif ($codigo_cargo != '00' && $codigo_cargo != '999') {
        // Si es cambio de lstDepartamentoEmpresa (muestra todos los de ese depto.)
        $query_total_empleados .= " AND codigo_departamento_empresa = '$codigo_cargo'";
    } else {
        // Si es "Todos" (codigo_cargo = 00), no se añade filtro de departamento.
    }

    $consulta_total_empleados = $dblink->query($query_total_empleados);
    $total_empleados = $consulta_total_empleados->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// --- 3. CONSULTA PARA OBTENER EL TOTAL DE ENCARGADOS ASIGNADOS ---
    // Asumimos que los "Encargados" son usuarios con permisos de ruta/depto. 
    $query_total_encargados = "SELECT COUNT(u.id_usuario) AS total FROM usuarios u INNER JOIN personal p ON p.codigo = u.codigo_personal WHERE p.codigo_estatus = '01'";
    
    if ($codigo_cargo == '02' && $codigo_ruta != '999' && $codigo_ruta != '00') {
        // Si es Motorista y hay ruta específica
        $query_total_encargados .= " AND u.codigo_ruta = '$codigo_ruta'";
    } elseif ($codigo_cargo != '00' && $codigo_cargo != '999') {
        // Si es cambio de lstDepartamentoEmpresa (muestra encargados de ese depto.)
        $query_total_encargados .= " AND u.codigo_departamento_empresa = '$codigo_cargo'";
    }
    $consulta_total_encargados = $dblink->query($query_total_encargados);
    $total_encargados = $consulta_total_encargados->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// --- 4. CONSTRUYENDO LA RESPUESTA JSON ---
    // Rellenando la array con los tres valores
    $datos[0]["CodigoRutaResponsable"] = $CodigoRutaResponsable;
    $datos[0]["TotalEmpleados"] = (int)$total_empleados;
    $datos[0]["TotalEncargados"] = (int)$total_encargados;
    
// Enviando la matriz con Json.
    echo json_encode($datos);	
?>