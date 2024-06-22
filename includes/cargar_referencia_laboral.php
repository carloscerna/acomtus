<?php
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);
// Incluimos el archivo de funciones y conexi�n a la base de datos
  include($path_root."/acomtus/includes/mainFunctions_conexion.php");
// armando el Query.
  $query = "SELECT codigo, descripcion FROM catalogo_referencia_laboral";
// Ejecutamos el Query.
  $consulta = $dblink -> query($query);
// Inicializando el array
  $datos=array(); $fila_array = 0;
// Recorriendo la Tabla con PDO::
  while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
  {
    // Nombres de los campos de la tabla.
    $codigo = trim($listado['codigo']); $descripcion = $listado['descripcion'];
    // Rellenando la array.
    $datos[$fila_array]["codigo"] = $codigo;
    $datos[$fila_array]["descripcion"] = ($descripcion);
    $fila_array++;
  }
// Enviando la matriz con Json.
echo json_encode($datos);	