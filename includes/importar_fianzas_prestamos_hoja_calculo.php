<?php
header ('Content-type: text/html; charset=utf-8');
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);
// Incluimos el archivo de funciones y conexi�n a la base de datos
include($path_root."/acomtus/includes/mainFunctions_conexion.php");
include($path_root."/acomtus/includes/funciones.php");

    set_time_limit(0);
    ini_set("memory_limit","2000M");
// variables. del post.
	$codigo_institucion = $_SESSION['codigo_institucion'];
	$ruta = '../files/' . $codigo_institucion . "/" . trim($_REQUEST["nombre_archivo_"]);
	  $nombre_archivo = trim($_REQUEST["nombre_archivo_"]);
// variable de la conexi�n dbf.
    $db_link = $dblink;
// Inicializando el array
	$datos=array(); $fila_array = 0;
	$fila_excel = 2;
	$respuestaOK = true;
    $mensajeError = "Información recuperada.";
    $contenidoOK = "";
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////  
// iniciar PhpSpreadsheet
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// call the autoload
    require $path_root."/acomtus/vendor/autoload.php";
    use PhpOffice\PhpSpreadsheet\Shared\Date;
// load phpspreadsheet class using namespaces.
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
// call xlsx weriter class to make an xlsx file
    use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
// Creamos un objeto Spreadsheet object
    $objPHPExcel = new Spreadsheet();
// Time zone.
    //echo date('H:i:s') . " Set Time Zone"."<br />";
    date_default_timezone_set('America/El_Salvador');
// set codings.
    $objPHPExcel->_defaultEncoding = 'ISO-8859-1';
// Set default font
    //echo date('H:i:s') . " Set default font"."<br />";
    $objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
    $objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
// Leemos un archivo Excel 2007
    //$objReader = PHPExcel_IOFactory::createReader('Excel2007');
		$objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
    $origen = $ruta;
    $objPHPExcel = $objReader->load($origen);
// N�mero de hoja.
   //$numero_de_hoja = 0;
   $total_de_hojas = $objPHPExcel->getSheetCount();

    for($numero_de_hoja=0;$numero_de_hoja<$total_de_hojas;$numero_de_hoja++)
    {	        
       $objPHPExcel->setActiveSheetIndex($numero_de_hoja);

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // consulta a la tabla para optener la informaciòn para revisiòn y guardar.
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// ELIMINAR REGISTROS DE LA TABLA.
    $query_borrar = "DELETE FROM fianzas_prestamos_importar";
// Ejecutamos el Query.
    $consulta_borrar = $dblink -> query($query_borrar);
//	codigo de la asignatura. modalidad, docente
   $fila = 2; $num = 1;
			  while($objPHPExcel->getActiveSheet()->getCell("C".$fila)->getValue() != "")
			   {	
				  $fecha = $objPHPExcel->getActiveSheet()->getCell("C".$fila)->getFormattedValue();
                  $referencia = $objPHPExcel->getActiveSheet()->getCell("D".$fila)->getValue();
                  $jornal = $objPHPExcel->getActiveSheet()->getCell("E".$fila)->getValue();
                  $nombre = $objPHPExcel->getActiveSheet()->getCell("F".$fila)->getValue();
                  $debito = $objPHPExcel->getActiveSheet()->getCell("G".$fila)->getValue();
                  $credito = $objPHPExcel->getActiveSheet()->getCell("H".$fila)->getValue();
                  if(empty($debito)){$debito = 0;}
                  if(empty($credito)){$credito = 0;}
                  $codigo = $objPHPExcel->getActiveSheet()->getCell("J".$fila)->getValue();
                  // CONDICIONAR QUE JOURNAL SOLO SEA PRJ.
                  if($jornal == "PRJ"){
                      /*
                        // PREGUNTAR SI EL ARCHIVO YA ESTA GUARDADO SEGUN FECHA Y CODIGO.
                            $query_buscar_fecha = "SELECT * FROM fianzas_prestamos_importar WHERE fecha = '$fecha' and codigo = '$codigo'";
                        // Ejecutamos el Query.
                            $consulta_buscar = $dblink -> query($query_buscar_fecha);
                            // Validar si hay registros.
                            if($consulta_buscar -> rowCount() != 0){
                            }
                            else{*/
                                // Query cuando son notas num�ricas.
                                    $query_guardar = "INSERT INTO fianzas_prestamos_importar (fecha, codigo, nombre, referencia, jornal, debito, credito)
                                        VALUES ('$fecha', '$codigo', '$nombre', '$referencia', '$jornal', '$debito', '$credito')";												 
                                // ejecutamos el query de la nota final. 
                                    $result = $db_link -> query($query_guardar);
                        //    }
                  }
				// INCREMENTAR I PARA LA COLUMNA de excel.
					$fila++; $num++;
				}
                // Leer la información guardada.
                $query_buscar = "SELECT * FROM fianzas_prestamos_importar";
                // Ejecutamos el Query.
                    $consulta_buscar = $dblink -> query($query_buscar);
                    // Validar si hay registros.
                    if($consulta_buscar -> rowCount() != 0){
                        while($listado = $consulta_buscar -> fetch(PDO::FETCH_BOTH))
                        {
                            // CAMPOS Y VARIALES DE LA TABLA.
                            $id_ = trim($listado['id_']);						// id_						0
                            $fecha = cambiaf_a_normal(trim($listado['fecha']));	// fecha					1
                            $codigo = trim($listado['codigo']);				// descripcion				2
                            $nombre = trim($listado['nombre']);						// fianza o prestamo		3
                            $referencia = trim($listado['referencia']);					// devolucion o descuento	4
                            $jornal = trim($listado['jornal']);			// codigo_personal			5
                            $debito = trim($listado['debito']);						// id_						0
                            $credito = trim($listado['credito']);						// id_						0

                            $contenidoOK .= "<tr>
                            <td>
							<td>$id_
                            <td>$fecha
                            <td>$codigo
                            <td>$nombre
                            <td>$referencia
                            <td>$jornal
                            <td>$debito
                            <td>$credito

                            ";
                        }
                        
                    }
}	// el for que recorre segun el numero de hojas que existan.
// Enviando la matriz con Json.
// Armamos array para convertir a JSON
$salidaJson = array("respuesta" => $respuestaOK,
    "mensaje" => $mensajeError,
    "contenido" => $contenidoOK,
    "nombre_archivo" => $nombre_archivo);
echo json_encode($salidaJson);?>