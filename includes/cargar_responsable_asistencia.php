<?php
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);
// Incluimos el archivo de funciones y conexi�n a la base de datos
    include($path_root."/acomtus/includes/mainFunctions_conexion.php");
// VARIABLES DE L POSRT
    $codigo_ruta = $_REQUEST["codigo_ruta"];
    $codigo_cargo = $_REQUEST["codigo_cargo"];
// armando el Query.
    if($codigo_cargo == "02"){
        $query = "SELECT p.codigo, p.apellidos, p.nombres, p.codigo_cargo, p.codigo_ruta, p.codigo_departamento_empresa,
        btrim(p.nombres || CAST(' ' AS VARCHAR) || p.apellidos) as nombre_personal,
        cat_cargo.descripcion as descripcion_cargo
        FROM personal p
            INNER JOIN catalogo_cargo cat_cargo ON cat_cargo.codigo = p.codigo_cargo
            INNER JOIN usuarios u ON u.codigo_personal = p.codigo  
                WHERE p.codigo_estatus = '01' and u.codigo_ruta = '$codigo_ruta'";
    }else{
        $query = "SELECT p.codigo, p.apellidos, p.nombres, p.codigo_cargo, p.codigo_ruta, p.codigo_departamento_empresa,
                btrim(p.nombres || CAST(' ' AS VARCHAR) || p.apellidos) as nombre_personal,
                cat_cargo.descripcion as descripcion_cargo
                FROM personal p
                    INNER JOIN catalogo_cargo cat_cargo ON cat_cargo.codigo = p.codigo_cargo
                    INNER JOIN usuarios u ON u.codigo_personal = p.codigo  
                        WHERE p.codigo_estatus = '01' and u.codigo_departamento_empresa = '$codigo_cargo'";
    }
    
// Ejecutamos el Query.
    $consulta = $dblink -> query($query);
// Inicializando el array
    $datos=array(); $fila_array = 0;
// Recorriendo la Tabla con PDO::
    if($consulta -> rowCount() != 0){
        while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
        {
          // Nombres de los campos de la tabla.
            $codigo = trim($listado['codigo']); $nombre_personal = trim($listado['nombre_personal']);
          // Rellenando la array.
            $datos[$fila_array]["CodigoRutaResponsable"] = $codigo . "-" . $nombre_personal;
            $fila_array++;
        }        
    }
    else{
        $datos[$fila_array]["CodigoRutaResponsable"] = "Ninguno";
    }
// Enviando la matriz con Json.
    echo json_encode($datos);	
?>