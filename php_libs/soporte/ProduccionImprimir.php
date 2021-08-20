<?php
//session_name('demoUI');
//session_start();
// limpiar cache.
clearstatcache();
// Script para ejecutar AJAX
// cambiar a utf-8.
header("Content-Type: text/html;charset=iso-8859-1");
// Insertar y actualizar tabla de usuarios
sleep(0);
// Inicializamos variables de mensajes y JSON
$respuestaOK = false;
$mensajeError = "No se puede ejecutar la aplicaci�n";
$contenidoOK = "";
$totalProduccionOK = 0;
$codigo_produccion = 0;
$CantidadtiqueteOK = 0;
$lista = "";
$arreglo = array();
$datos = array();
$listado = array("0","1","2","3","4","5","6","7");
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);    
// Incluimos el archivo de funciones y conexi�n a la base de datos
    include($path_root."/acomtus/includes/mainFunctions_conexion.php");
    include($path_root."/acomtus/includes/funciones.php");
// Validar conexi�n con la base de datos
if($errorDbConexion == false){
	// Validamos qe existan las variables post
	if(isset($_POST) && !empty($_POST)){
		if(!empty($_POST['accion_buscar'])){
			$_POST['accion'] = $_POST['accion_buscar'];
		}
		// Verificamos las variables de acci�n
		switch ($_POST['accion']) {
		    case 'BuscarProduccionPorRuta':
                # buscar en la tabla catalogo_ruta.
                    $fecha = $_POST["fecha"];
                    $fecha_ = cambiaf_a_normal($_POST["fecha"]);
                // armando el Query.
                    $query = "SELECT id_ruta, codigo, descripcion FROM catalogo_ruta  WHERE codigo_estatus = '01' ORDER BY codigo";
                // Ejecutamos el Query.
                    $codigo_ruta = array();
                    $consulta = $dblink -> query($query);
                // crear matriz
                while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                {
                    $codigo_ruta[] = $listado['id_ruta'];
                    $descripcion_ruta[] = $listado['descripcion'];
                }
                // armando el Query. PARA LA TABLA INVENTARIO TIQUETE..
               /* $query = "SELECT it.id_, it.codigo_serie, it.precio_publico, it.existencia,
                        cat_ts.descripcion as nombre_serie,
                        cat_tc.descripcion as tiquete_color
                        FROM inventario_tiquete it
                        INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie
                        INNER JOIN catalogo_tiquete_color cat_tc On cat_tc.id_ = it.codigo_tiquete_color
                            ORDER BY precio_publico";*/
                 $query = "SELECT DISTINCT cat_tc.id_ as id_tiquete_color, cat_tc.descripcion as tiquete_color, it.precio_publico
                                FROM catalogo_tiquete_color cat_tc
                                    INNER JOIN inventario_tiquete it ON cat_tc.id_ = it.codigo_tiquete_color
                                        ORDER BY it.precio_publico";
                // Ejecutamos el Query.
                    $id_tiquete_color = array(); $precio_publico_ = array(); $nombre_serie = array(); $tiquete_color = array();
                    $consulta = $dblink -> query($query);
                // crear matriz
                while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                {
                    $id_tiquete_color[] = $listado['id_tiquete_color'];
                    $precio_publico[] = $listado['precio_publico'];
                    //$nombre_serie[] = $listado['nombre_serie'];
                    $tiquete_color[] = $listado['tiquete_color'];
                }
                // CREAR MATRIZ UNIENDO LA RUTA CON EL PRECIO PUBLICO DE CADA TIQUETE.
                $codigo_ruta_precio = array(); $descripcion_ruta_ = array(); $precio_publico_ = array(); $nombre_serie_ = array(); $tiquete_color_ = array();
                for ($Hj=0; $Hj < count($codigo_ruta); $Hj++) { // tabla CATALOGO RUTA.
                    for ($jj=0; $jj < count($precio_publico); $jj++) {   // TABLA CATALOGO INVENTARIO TIQUETE.

                        // Verificar que sólo se genere ruta y único precio público.
                        //$codigo_ruta_precio[] = $codigo_ruta[$Hj] . $id_it[$jj];
                        $codigo_ruta_precio[] = $codigo_ruta[$Hj] . $id_tiquete_color[$jj];
                        $descripcion_ruta_[] = $descripcion_ruta[$Hj];
                        $precio_publico_[] = $precio_publico[$jj];
                        //$nombre_serie_[] = $nombre_serie[$jj];
                        $tiquete_color_[] = $tiquete_color[$jj];
                    }
                }
               // print_r($codigo_ruta_precio);
                // CONSULTA FOR INVENTARIO TIQUETE.
                for ($Hj=0; $Hj < count($codigo_ruta_precio); $Hj++) { // MATRIZ CATALOGO RUTA Y INVENTARIO TIQUETE..
                   /*  $query = "SELECT pro.id_, pro.total_ingreso, pro.codigo_ruta
                      FROM produccion pro WHERE fecha = '$fecha' and concat(codigo_ruta,codigo_inventario_tiquete) = '$codigo_ruta_precio[$Hj]'
                      ORDER BY pro.codigo_ruta, pro.id_ ASC";*/
                    $query = "SELECT pro.id_, pro.total_ingreso, pro.codigo_ruta
                      FROM produccion pro WHERE fecha = '$fecha' and concat(codigo_ruta,codigo_tiquete_color) = '$codigo_ruta_precio[$Hj]'
                      ORDER BY pro.codigo_ruta, pro.id_ ASC";
                        $consulta = $dblink -> query($query);
                    // Validar si hay registros.
						if($consulta -> rowCount() != 0){
                        // Crear matriz para poder tomar dos valores
                            $ProduccionDesdeHasta = array(); $ProduccionDesde = 0; $ProduccionHasta = 0;
                        // Recorriendo la Tabla con PDO::
                            while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                            {
                                $ProduccionDesdeHasta[] = $listado["id_"];
                            }   // WHILE DE LA CONSULTA PRODUCCIÓN.
                            // PASAR VALOR PRIMERO Y ÚLTIMO DE LA MATRIZ A LAS VARIALBES.
                                $ProduccionDesde = reset($ProduccionDesdeHasta);
                                $ProduccionHasta = end($ProduccionDesdeHasta);
                                $ProduccionCantidad = count($ProduccionDesdeHasta);
                                $totalProduccionOK = $totalProduccionOK + $ProduccionCantidad;
                        }   // IF DE LA CONSULTA PRODUCCION.
                    // DAR VALORES A VARIABLES.
                        // ESTILO
                        $estilo_l = 'style="padding: 0px; font-size: large; color:black; text-align: left;"';
                        $estilo_c = 'style="padding: 0px; font-size: large; color:black; text-align: center;"';
                        $estilo_r = 'style="padding: 0px; font-size: x-large; color:black; text-align: right;"';
                        // CUANDO YA SE HA CALCULADO LA PRODUCCIÓN ASIGNADA.
                        // Validar si hay registros.
						if($consulta -> rowCount() != 0){
                            // convertir la matriz $ProduccionDesdeHasta
                            $separado_por_comas = implode(",", $ProduccionDesdeHasta);
                            // cambiar el color de la fila.
                                $contenidoOK .= "<tr>
                                <td $estilo_c><a data-accion=ProduccionImprimir data-toggle=tooltip data-placement=left title='Controles' href=$separado_por_comas style='color: black;'><span class='fal fa-print'></span></a>
                                <td $estilo_l>$fecha_
                                <td $estilo_l>$descripcion_ruta_[$Hj]
                                <td $estilo_c>$ProduccionCantidad
                                <td>
                                <td $estilo_c>$tiquete_color_[$Hj]
                                <td $estilo_c>$ $precio_publico_[$Hj]
                                <td $estilo_r><input type=button class='btn btn-info btn-md' value='#' data-toggle=tooltip data-placement=left title='$separado_por_comas'>
                                <td><input type=hidden value=$separado_por_comas id=NControl name=NControl>
                                ";
                        }
                     }   // FOR DE LA TABLA CATALOGO RUTA Y INVENTARIO TIQUETE..
                    // 
                     // quite la serie del la tabla <td $estilo_c>$nombre_serie_[$Hj]
                    //
                    $respuestaOK = true;
                    $mensajeError = "Producción Encontrada.";
                break;       
			default:
				$mensajeError = 'Esta acci�n no se encuentra disponible';
			break;
		}
	}
	else{
		$mensajeError = 'No se puede ejecutar la aplicaci�n';}
}
else{
	$mensajeError = 'No se puede establecer conexi�n con la base de datos';}
// Salida de la Array con JSON.
	if($_POST["accion"] === "BuscarTodos" or $_POST["accion"] === ""){
		echo json_encode($arreglo);	
	}elseif(
        $_POST["accion"] === "BuscarCodigo" or $_POST["accion"] === "BuscarPersonalMotorista" or $_POST["accion"] === "EditarRegistro"
        ){
		echo json_encode($datos);
		}
	else{
		// Armamos array para convertir a JSON
		$salidaJson = array("respuesta" => $respuestaOK,
			"mensaje" => $mensajeError,
            "contenido" => $contenidoOK,
            "totalProduccion" => $totalProduccionOK,
            "cantidadTiquete" => $CantidadtiqueteOK);
		echo json_encode($salidaJson);
    }
?>