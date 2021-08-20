<?php
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);
// verificar si existe la imagen.
if (is_array($_FILES) && count($_FILES) > 0) {
    if (($_FILES["file"]["type"] == "image/pjpeg")
        || ($_FILES["file"]["type"] == "image/jpeg")
        || ($_FILES["file"]["type"] == "image/png")
        || ($_FILES["file"]["type"] == "image/gif")) {
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $path_root . "/acomtus/img/".$_FILES['file']['name'])) {
            // Incluimos el archivo de funciones y conexión a la base de datos
                include($path_root."/acomtus/includes/mainFunctions_conexion.php");
            // Variables de la imagen.
                $Id_ = $_SESSION["Id_A"];
                $extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
                $nombreArchivo = "logo"."-".$Id_.".".$extension;
                rename($path_root."/acomtus/img/".$_FILES['file']['name'],$path_root."/acomtus/img/".$nombreArchivo);
            // Guardar el nombre de la imagen. en la tabla.            
            // Armar query. para actualizar el nombre del archivo de la ruta foto.
                $query = "UPDATE informacion_institucion SET logo_uno = '".$nombreArchivo."' WHERE id_ = ". $Id_;
            // Ejecutamos el Query.
                $consulta = $dblink -> query($query);
                    echo "../acomtus/img/".$nombreArchivo;
        } else {
            echo 0;
        }
    } else {
        echo 0;
    }
} else {
    echo 0;
}
?>