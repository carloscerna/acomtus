<?php
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);
// URL PARA GUARDAR LAS IMAGENES.
    $url_ = "/acomtus/img/portafolio/";
    $small = 'thumbails/';
    $large = 'large/';
    $random = rand();
// verificar si existe la imagen.
if (is_array($_FILES) && count($_FILES) > 0) {
    if (($_FILES["file"]["type"] == "image/pjpeg")
        || ($_FILES["file"]["type"] == "image/jpeg")
        || ($_FILES["file"]["type"] == "image/png")
        || ($_FILES["file"]["type"] == "image/gif")) {
        if (move_uploaded_file($_FILES["file"]["tmp_name"], $path_root . $url_ . $_FILES['file']['name'])) {
            // Incluimos el archivo de funciones y conexión a la base de datos
                include($path_root."/acomtus/includes/mainFunctions_conexion.php");
            // Variables de la imagen.
                $id_personal = $_SESSION["id_personal"];
                $id_portafolio = $_SESSION['id_portafolio'];
            // Armamos el query PARA ELIMINAR LA IMAGEN O SEA EL ARCHIVO.
                $query_file = "SELECT * FROM personal_portafolio WHERE id_ = $id_portafolio";
            // Ejecutamos el query
                $resultadoQuery = $dblink -> query($query_file);				
                while($listado = $resultadoQuery -> fetch(PDO::FETCH_BOTH))
                {
                    $nombreArchivo = trim($listado['url_imagen']);
                    $id_personal = trim($listado['id_personal']);
                }
            // REGISTRO CON UNLINK(). 
                if(!empty($nombreArchivo)){
                    if(file_exists($path_root.$url_.$id_personal."/".$nombreArchivo)){
                        unlink($path_root.$url_.$id_personal."/".$nombreArchivo);				// imagen original.
                    }
                    if(file_exists($path_root.$url_.$id_personal."/".$small."/".$nombreArchivo)){
                        unlink($path_root.$url_.$id_personal."/".$small."/".$nombreArchivo);	// imagen small
                    }
                    if(file_exists($path_root.$url_.$id_personal."/".$large."/".$nombreArchivo)){
                        unlink($path_root.$url_.$id_personal."/".$large."/".$nombreArchivo);	// imagen large	
                    }
                }
            //  Captura extensión del archivo tempral.
                $extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
                $nombreArchivo = "Portafolio"."-".$id_personal."-".$id_portafolio."-".$random.".".$extension;
            //  VERIFICAR SI EXISTE EL DIRECTORIO POR EL ID PERSONAL.
                if(!file_exists($path_root.$url_.$id_personal)){
                    // Crear el Directorio Principal Archvos...
                        mkdir ($path_root.$url_.$id_personal."/");
                        chmod($path_root.$url_.$id_personal."/",07777);
                }
            //  VERIFICAR SI EXISTE EL DIRECTORIO. SMALL
                if(!file_exists($path_root.$url_.$id_personal."/".$small)){
                    // Crear el Directorio Principal Archvos...
                        mkdir ($path_root.$url_.$id_personal."/".$small."/");
                        chmod($path_root.$url_.$id_personal."/".$small."/",07777);
                }
            //  VERIFICAR SI EXISTE EL DIRECTORIO. SMALL
                if(!file_exists($path_root.$url_.$id_personal."/".$large)){
                    // Crear el Directorio Principal Archvos...
                        mkdir ($path_root.$url_.$id_personal."/".$large."/");
                        chmod($path_root.$url_.$id_personal."/".$large."/",07777);
                }
            //  MOVER A LA CARPETA QUE SE VA CREANDO DE CADA USUARIO.
                rename($path_root.$url_."/".$_FILES['file']['name'],$path_root.$url_.$id_personal."/".$nombreArchivo);
            
            // UTILIZACIÓN DE LAS HERRAMIENTAS GD CON IMAGE.
                //  Abrir foto original
                    if($_FILES["file"]["type"] == "image/jpeg"){
                        $original = imagecreatefromjpeg($path_root.$url_.$id_personal."/".$nombreArchivo);
                    }else if($_FILES["file"]["type"] == "image/png"){
                        $original = imagecreatefrompng($path_root.$url_.$id_personal."/".$nombreArchivo);
                    }
                //  OBETNER COORD3ENADAS ANCHO Y ALTO.
                    $ancho_original = imagesx( $original );
                    $alto_original = imagesy( $original );
                        //  ****************************************************************************************************************
                        //  ****************************************************************************************************************
                        //  Crear un lienzo vacio (foto destino Small)
                            $ancho_nuevo = 168; // $small.
                            $alto_nuevo = round( $ancho_nuevo * $alto_original / $ancho_original );

                            $copia = imagecreatetruecolor( $ancho_nuevo , $alto_nuevo );
                        //  Copiar orignal --> copia
                            imagecopyresampled($copia, $original, 0,0,0,0, $ancho_nuevo, $alto_nuevo, $ancho_original, $alto_original );
                        //  Exportar y guardar imagen.
                            imagejpeg( $copia, $path_root.$url_.$id_personal."/".$small."/".$nombreArchivo, 100);
                        //  ****************************************************************************************************************
                        //  ****************************************************************************************************************

                        //  ****************************************************************************************************************
                        //  ****************************************************************************************************************
                        //  Crear un lienzo vacio (foto destino $large)
                            $ancho_nuevo = 1024; // $large.
                            $alto_nuevo = round( $ancho_nuevo * $alto_original / $ancho_original );

                            $copia = imagecreatetruecolor( $ancho_nuevo , $alto_nuevo );
                        //  Copiar orignal --> copia
                            imagecopyresampled($copia, $original, 0,0,0,0, $ancho_nuevo, $alto_nuevo, $ancho_original, $alto_original );
                        //  Exportar y guardar imagen.
                            imagejpeg( $copia, $path_root.$url_.$id_personal."/".$large."/".$nombreArchivo, 100);
                        //  ****************************************************************************************************************
                        //  ****************************************************************************************************************
                // UTILIZACIÓN DE LAS HERRAMIENTAS GD CON IMAGE.

            // Guardar el nombre de la imagen. en la tabla.            
            // Armar query. para actualizar el nombre del archivo de la ruta foto.
                $query = "UPDATE personal_portafolio SET url_imagen = '".$nombreArchivo."' WHERE id_ = ". $id_portafolio;
            // Ejecutamos el Query.
                $consulta = $dblink -> query($query);
                    echo "..".$url_.$id_personal."/".$nombreArchivo;
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