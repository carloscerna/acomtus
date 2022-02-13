<?php
header ('Content-type: text/html; charset=utf-8');
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);
// Incluimos el archivo de funciones y conexi�n a la base de datos
include($path_root."/acomtus/includes/mainFunctions_conexion.php");
    set_time_limit(0);
    ini_set("memory_limit","2000M");
// variables. del post.
	$codigo_institucion = $_SESSION['codigo_institucion'];
	$ruta = '../files/' . $codigo_institucion . "/" . trim($_REQUEST["nombre_archivo_"]);
	  $trimestre = trim($_REQUEST["periodo_"]);
	  $nombre_archivo = trim($_REQUEST["nombre_archivo_"]);
// variable de la conexi�n dbf.
    $db_link = $dblink;
// Inicializando el array
	$datos=array(); $fila_array = 0;
	$fila_excel = 2;
	$datos[$fila_array]["registro"] = 'Si_registro';
	$datos[$fila_array]["nombre_archivo"] = $nombre_archivo;
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
//	codigo de la asignatura. modalidad, docente
   $fila = 2; $num = 1;
			  while($objPHPExcel->getActiveSheet()->getCell("C".$fila)->getValue() != "")
			   {	
				  $codigo_nie = $objPHPExcel->getActiveSheet()->getCell("B".$fila)->getValue();

					// Query cuando son notas num�ricas.
						$query_guardar = "INSERT INTO fianzas_prestamos_importar (fecha,) VALUES ()";												 
					// ejecutamos el query de la nota final. 
					 $result = $db_link -> query($query_guardar);
				// INCREMENTAR I PARA LA COLUMNA de excel.
					$fila++; $num++;
				}
}	// el for que recorre segun el numero de hojas que existan.
// Enviando la matriz con Json.
	echo json_encode($datos);
?>