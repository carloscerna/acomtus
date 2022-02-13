<?php
header ('Content-type: text/html; charset=utf-8');
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);
// Incluimos el archivo de funciones y conexión a la base de datos
include($path_root."/acomtus/includes/mainFunctions_conexion.php");
    set_time_limit(0);
    ini_set("memory_limit","2000M");
// variables. del post.
  $codigo_institucion = $_SESSION['codigo_institucion'];
  $ruta = '../files/' . $codigo_institucion . "/" . trim($_REQUEST["nombre_archivo_"]);
  $valor_check = trim($_REQUEST["valor_check"]);
// variable de la conexión dbf.
    $db_link = $dblink;
// Inicializando el array
  $datos=array(); $fila_array = 0;
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
    date_default_timezone_set('America/El_Salvador');
// set codings.
    $objPHPExcel->_defaultEncoding = 'ISO-8859-1';
// Set default font
    $objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
    $objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
// Leemos un archivo Excel 2007
    //$objReader = PHPExcel_IOFactory::createReader('Excel2007');
		$objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
    $origen = $ruta;
    $objPHPExcel = $objReader->load($origen);
// Número de hoja.
   $numero_de_hoja = 0;
   $objPHPExcel->setActiveSheetIndex($numero_de_hoja);
   $celda_j1 = $objPHPExcel->getActiveSheet()->getCell("J1")->getValue();
   /*
   /* CONCIONAR QUE TIPO DE VALOR TIENE QUE TENER A1.
   */
   // sON LOS ARCHIVOS QUE TIENE VARIAS ASIGNATURAS Y CALCULA EL PROMEDIO.
       if($valor_check === "Fianzas" || $valor_check === "Prestamos"){
          if($celda_j1 != "Employee ID"){
            $datos[$fila_array]["registro"] = 'No_registro';
            $fila_array++;
            $datos[$fila_array]["registro"] = $celda_j1;
            // Enviando la matriz con Json.
              echo json_encode($datos);
              return;
         } 
       }
    // fin del proceso.
	// condicion para determinar si es de primer ciclo.
    $datos[$fila_array]["registro"] = 'Si_registro';
// Enviando la matriz con Json.
    echo json_encode($datos);
?>