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
//  $ruta = $path_root.'/sgp_web/formatos_hoja_de_calculo/fianzas.xls';
// $ruta = $path_root.'/sgp_web/formatos_hoja_de_calculo/prestamos.xls';
	$ruta = $path_root.'/acomtus/Hoja_de_Calculo/LISTADO POR RUTAS.xlsx';
  //$trimestre = trim($_REQUEST["periodo_"]);
// variable de la conexi�n dbf.
    $db_link = $dblink;
// Inicializando el array
$datos=array(); $fila_array = 0;
// call the autoload
    require $path_root."/acomtus/vendor/autoload.php";
// load phpspreadsheet class using namespaces.
    use PhpOffice\PhpSpreadsheet\Spreadsheet;
// call xlsx weriter class to make an xlsx file
    use PhpOffice\PhpSpreadsheet\Read\Xlsx;
// Creamos un objeto Spreadsheet object
    $objPHPExcel = new Spreadsheet();
// Time zone.
    //echo date('H:i:s') . " Set Time Zone"."<br />";
    //date_default_timezone_set('America/El_Salvador');
// set codings.
    $objPHPExcel->_defaultEncoding = 'ISO-8859-1';
// Set default font
    //echo date('H:i:s') . " Set default font"."<br />";
    $objPHPExcel->getDefaultStyle()->getFont()->setName('Arial');
    $objPHPExcel->getDefaultStyle()->getFont()->setSize(10);
// Leemos un archivo Excel 2007
   $objReader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader("Xlsx");
    $origen = $ruta;
	 $fila = 2;
    $objPHPExcel = $objReader->load($origen);

// N�mero de hoja.
   $numero_de_hoja = 5;
	$numero = 5;	
// 	Recorre el numero de hojas que contenga el libro
       $objPHPExcel->setActiveSheetIndex($numero_de_hoja);
		//	BUCLE QUE RECORRE TODA LA CUADRICULA DE LA HOJA DE CALCULO.
		while($objPHPExcel->getActiveSheet()->getCell("A".$fila)->getValue() != "")
		  {
			 //  DATOS GENERALES.
				$codigo = $objPHPExcel->getActiveSheet()->getCell("A".$fila)->getValue();
				$nombre_empleado = $objPHPExcel->getActiveSheet()->getCell("B".$fila)->getValue();
				$nombres = $objPHPExcel->getActiveSheet()->getCell("G".$fila)->getCalculatedValue();
				$apellidos = $objPHPExcel->getActiveSheet()->getCell("H".$fila)->getCalculatedValue();
				$dui = $objPHPExcel->getActiveSheet()->getCell("C".$fila)->getValue();
				$nit = $objPHPExcel->getActiveSheet()->getCell("D".$fila)->getValue();
				
				$pago_diario = 12.17;
				$salario = $pago_diario * 30;
				$codigo_ruta = 9;
				$codigo_clasificacion_empresa = '09';
				$codigo_cargo = '32';
				$codigo_departamento_empresa = '02';
				$codigo_socio = '02';
				// Armar query para guardar en la tabla CATALOGO_PRODUCTOS.
				//$query = "UPDATE personal SET dui='$dui', nit='$nit', pago_diario = '$pago_diario', salario = '$salario', codigo_ruta = '$codigo_ruta' WHERE codigo = $codigo";
					$query = "UPDATE personal SET  codigo_clasificacion_empresa = '$codigo_clasificacion_empresa', codigo_cargo = '$codigo_cargo', codigo_departamento_empresa = '$codigo_departamento_empresa',
											pago_diario = '$pago_diario', salario = '$salario', codigo_ruta = '$codigo_ruta', codigo_cargo_empresa = '$codigo_clasificacion_empresa',
											codigo_socio = '$codigo_socio', nombres = '$nombres', apellidos = '$apellidos'
											 WHERE codigo = '$codigo'";
						$consulta = $dblink -> query($query);
			
         	$fila++;
			print $codigo .  ' - ' . $pago_diario . ' - ' . $salario . ' - ' . $codigo_ruta .  ' -  ' . $codigo_cargo . ' - ' . $codigo_clasificacion_empresa;
			print "<br>";
		}	// FIN DEL WHILE PRINCIPAL DE L AHOJA DE CALCULO.
/*
		///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
						///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
								$query_p = "SELECT id_productos, codigo, substring(codigo from 1 for 3)::int as codigo_cargo_numero_entero
											FROM catalogo_productos ORDER BY codigo_cargo_numero_entero DESC LIMIT 1";
							// Ejecutamos el Query.
									$consulta_p = $dblink -> query($query_p);
									// Verificar si existen registros.
									if($consulta_p -> rowCount() != 0){
										// convertimos el objeto
										while($listados = $consulta_p -> fetch(PDO::FETCH_BOTH))
										{
											$codigo_entero_p = trim($listados['codigo_cargo_numero_entero']) + 1;
											$codigo_string_p = (string) $codigo_entero_p;
											$codigo_nuevo_p = codigos_nuevos($codigo_string_p);
										}
										// Armar query para guardar en la tabla CATALOGO_PRODUCTOS.
										$query_cat = "INSERT INTO catalogo_productos (codigo, descripcion, existencia, codigo_categoria) VALUES ('$codigo_nuevo_p','$nombre','$cantidad','$codigo_nuevo')";
										$consulta_cat = $dblink -> query($query_cat);
									}
									else{
											$codigo_nuevo_p = "001";
										// Armar query para guardar en la tabla CATALOGO_PRODUCTOS.
										$query_cat = "INSERT INTO catalogo_productos (codigo, descripcion, existencia, codigo_categoria) VALUES ('$codigo_nuevo_p','$nombre','$cantidad','$codigo_nuevo')";
										$consulta_cat = $dblink -> query($query_cat);}
										
										
													// condici�n
			if((int) $codigo_categoria === $numero){
				$codigo_producto = $codigo_producto + 1;
				
			}else{
				$codigo_producto = 1;
				$numero = $numero + 1;
			}
			$objPHPExcel->getActiveSheet()->SetCellValue("B".$fila, $codigo_producto);
										
										*/