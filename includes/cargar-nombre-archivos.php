<?php
session_name('Sistema2020');
session_start();
$codigo_institucion = $_SESSION['codigo_institucion'];
$directorio = opendir('../files/'.$codigo_institucion);
// Inicializando el array
$datos=array(); $fila_array = 0;
// Iniciar Bucle que recorre el directorio
    while ($elemento = utf8_decode(readdir($directorio)))
    {
        if($elemento !='.' &&  $elemento != '..'){
            if(is_dir('../files/'.$codigo_institucion . "/" . $elemento))
            {
                $datos[$fila_array]["archivo"] = '<tr><td>'.$elemento.'</td></tr>';
            }else{
                $datos[$fila_array]["archivo"] = '<tr><td>'.$elemento.'</td><td><a data-accion=goBuscarOk class="btn btn-sm btn-success" href='."$elemento".'><i class="fad fa-file-search"></i> Buscar</a></td>'
                    .'</td><td><a data-accion=goEliminarOk class="btn btn-sm btn-warning" alt="Eliminar"href='."$elemento".'><i class="fal fa-trash-alt"></i></a></td></tr>';
                $fila_array++;
            }
        }
    }
    if($fila_array == 0){
        $datos[$fila_array]["archivo"] = '<tr><td>No hay elementos...</td><td></td></tr>';
    }
// Enviando la matriz con Json.
echo json_encode($datos);	
?>