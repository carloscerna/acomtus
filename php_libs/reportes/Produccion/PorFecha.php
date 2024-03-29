<?php
// serie de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);
// archivos que se incluyen.
    include($path_root."/acomtus/includes/funciones.php");
    include($path_root."/acomtus/includes/mainFunctions_conexion.php");
// Llamar a la libreria fpdf
    include($path_root."/acomtus/php_libs/fpdf/fpdf.php");
// cambiar a utf-8.
    header("Content-Type: text/html; charset=UTF-8");
// variables y consulta a la tabla.
    $db_link = $dblink;
	$fecha = $_REQUEST["fecha"];
	$fecha_produccion = cambiaf_a_normal($_REQUEST["fecha"]);
	$ruta = explode(",",$_REQUEST["ruta"]);
	$cantidad = explode(",",$_REQUEST["cantidad"]);
	$entregados = explode(",",$_REQUEST["entregados"]);
	$devolucion = explode(",",$_REQUEST["devolucion"]);
	$vendidos = explode(",",$_REQUEST["vendidos"]);
	$precio_publico = explode(",",$_REQUEST["precio_publico"]);
	$ingreso = explode(",",$_REQUEST["ingreso"]);
	$fecha_ = explode("/",cambiaf_a_normal($fecha));
	$fecha_mes = $fecha_[1];
	//
	$tiqueteEntregados = $_REQUEST["tiqueteEntregados"];
	$tiqueteVendidos = $_REQUEST["tiqueteVendidos"];
	// iNFORMACIÓN DEL EMPLEADO.
	$produccion_total = $_REQUEST["produccion_total"];
	$produccion_vendida = $_REQUEST["produccion_vendida"];
	// valores
	$ingresoTotal = $_REQUEST["ingresoTotal"];
	$ingresoColones = $_REQUEST["ingresoColones"];

	$totalProduccionOK = 0;
// Establecer formato para la fecha.
	date_default_timezone_set('America/El_Salvador');
	setlocale(LC_TIME,'es_SV');
// CREAR MATRIZ DE MESES Y FECH.
	$meses = array("enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre");
	$dia_produccion = intval($fecha_[0]);	// dia.
	$mes_produccion = $meses[intval($fecha_[1])];	// mes.
	$año_produccion = $fecha_[2];	// año.
//Crear una línea. Fecha con getdate();
	$hoy = getdate();
	$NombreDia = $hoy["wday"];  // dia de la semana Nombre.
	$dia = $hoy["mday"];    // dia de la semana
	$mes = $hoy["mon"];     // mes
	$año = $hoy["year"];    // año
	$total_de_dias = cal_days_in_month(CAL_GREGORIAN, (int)$fecha_mes, $año);
	$NombreMes = $meses[(int)$fecha_mes - 1];
// definimos 2 array uno para los nombre de los dias y otro para los nombres de los meses
	$nombresDias = array("D", "L", "Ma", "Mi", "J", "V", "S" );
	$nombresMeses = array(1=>"Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
	setlocale(LC_MONETARY,"es_ES");
class PDF extends FPDF
{
    //Cabecera de página
    function Header()
    {
    //Logo
    $img = $_SERVER['DOCUMENT_ROOT'].'/acomtus/img/'.$_SESSION['logo_uno'];
    $this->Image($img,5,4,24,24);
    //Arial bold 14
        $this->SetFont('Arial','B',14);
    //Título
	//$0titulo1 = utf8_decode("Educación Parvularia - Básica - Tercer Ciclo y Bachillerato.");
        $this->RotatedText(30,10,mb_convert_encoding($_SESSION['nombre_institucion'],"ISO-8859-1","UTF-8"),0);
    //Arial bold 13
        $this->SetFont('Arial','B',12);
	$this->RotatedText(30,17,mb_convert_encoding($_SESSION['direccion'],"ISO-8859-1","UTF-8"),0);
	
    // Teléfono.
	if(empty($_SESSION['telefono'])){
	    $this->RotatedText(30,24,'',0);    
	}else{
	    $this->RotatedText(30,24,mb_convert_encoding('Teléfono: ',"ISO-8859-1","UTF-8").$_SESSION['telefono'],0);
	}
    // ARMAR ENCABEZADO.
	$style6 = array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(0,0,0));
	$this->CurveDraw(0, 37, 120, 40, 155, 20, 225, 20, null, $style6);
	$this->CurveDraw(0, 36, 120, 39, 155, 19, 225, 19, null, $style6);	
    }

function Footer()
{
	global $print_sumas;
//Posición: a 1,5 cm del final
    $this->SetY(-10);
    //Arial italic 8
    $this->SetFont('Arial','I',9);
    //Crear una línea de la primera firma.
    $this->Line(0,270,225,270);
    //Número de página
	$this->SetFont('Arial','','7');
    $fecha = date("l, F jS Y ");
    $this->Cell(0,10,mb_convert_encoding('Página '.$this->PageNo().'/{nb}       ',"ISO-8859-1","UTF-8"),0,0,'C');	
}

//Tabla coloreada
function FancyTable($header)
{
	global $print_sumas, $codigo, $dblink, $fill, $print_no_header;
//////////////////////////////////////////////////////////////////////////////////////
// 	PROCESO PARA CALCULAR SUMAS DE PRESTAMOS, DESCUENTOS Y SALDO.
//////////////////////////////////////////////////////////////////////////////////////	
    //Colores, ancho de línea y fuente en negrita
    $this->SetFillColor(133,146,158);
    $this->SetTextColor(255);
    $this->SetDrawColor(128,0,0);
    $this->SetLineWidth(.3);
    $this->SetFont('','B');
	//  mostrar los valores de la consulta
	$w=array(50,15,25,25,20,25,25,20); // ruta, cantidad, entregados, velocuiones, venvidos, precio public, ingresos.
    $w2=array(6,12); //determina el ALTO de las columnas
	$this->SetXY(20,95);
    for($i=0;$i<count($header);$i++)
        $this->Cell($w[$i],5,mb_convert_encoding($header[$i],"ISO-8859-1","UTF-8"),'LTR',0,'C',1);
    $this->Ln();
    //Restauración de colores y fuentes
    $this->SetFillColor(238, 239, 237);
    $this->SetTextColor(0);
	$this->SetDrawColor(0,0,0);
    $this->SetFont('');
	// Ubicación del eje X.
	$this->SetX(10);	
    //Datos
    $fill=false;
}

}
//************************************************************************************************************************
// Creando el Informe.
    $pdf=new PDF('P','mm','Letter');
    #Establecemos los márgenes izquierda, arriba y derecha: 
    $pdf->SetMargins(20, 20);
    #Establecemos el margen inferior: 
    $pdf->SetAutoPageBreak(true,5);
    $data = array();
//Títulos de las columnas
    $header=array('Ruta','Cantidad','Entregados','Devoluciones','Vendidos','Precio Publico','Ingreso');	
//Títulos de las columnas
    $pdf->AliasNbPages();
    $pdf->AddPage();
// Aqui mandamos texto a imprimir o al documento.
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetY(20);
    $pdf->SetX(20);
// Diseño de Lineas y Rectangulos.
    $pdf->SetFillColor(224);
	// FECHA.
	$pdf->RotatedText(140,40,'Santa Ana, ' . $dia . ' de ' . $nombresMeses[$mes] . ' de ' . $año,0);
	// estado de cuenta
	$pdf->RoundedRect(15, 45, 140, 8, 2, '1234', 'DF');
	$pdf->RotatedText(18,50,mb_convert_encoding('Producción Diaria: ' . $dia_produccion . " de " . $mes_produccion . " de " . $año_produccion,"ISO-8859-1","UTF-8"),0);
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetFont('Arial','',9); // I : Italica; U: Normal;
//  mostrar los valores de la consulta
	$w=array(50,15,25,25,20,25,25,20); // ruta, cantidad, entregados, velocuiones, venvidos, precio public, ingresos.
	$w1 = array(55,60);
    $w2=array(5,7); //determina el ALTO de las columnas
// Variables.
    $fill = false; $i=1; $totalIngreso = 0;
//  Encabezando.
    $pdf->FancyTable($header); // Solo carge el encabezado de la tabla porque medaba error el cargas los datos desde la consulta.		
// Definimos el tipo de fuente, estilo y tamaño.
	$pdf->SetXY(20,65);
//	INFORMACIÓN DEL EMPLEADO.
	$pdf->SetX(20);
	$pdf->Cell($w1[0],$w2[1],$produccion_total,"LTR",0,'L',$fill);	// Controles
	$pdf->SetX(20+$w1[0]+5);
	$pdf->Cell($w1[1],$w2[1],$tiqueteEntregados,"LTR",0,'L',$fill);	// tiquetes entregados
	$pdf->SetX(20+$w1[0]+5+$w1[0]+10);
	$pdf->Cell($w1[0],$w2[1],$ingresoTotal,"LTR",1,'L',$fill);	// ingresoen dólares

	$pdf->SetX(20);
	$pdf->Cell($w1[0],$w2[1],$produccion_vendida,"LBR",0,'L',$fill);	// Procesados
	$pdf->SetX(20+$w1[0]+5);
	$pdf->Cell($w1[1],$w2[1],$tiqueteVendidos,"LBR",0,'L',$fill);	// tiquetes vendidos.
	$pdf->SetX(20+$w1[0]+5+$w1[0]+10);
	$pdf->Cell($w1[0],$w2[1],mb_convert_encoding($ingresoColones,"ISO-8859-1","UTF-8"),"LBR",1,'L',$fill);	// ingreso en colones.
	$pdf->SetXY(20,100);
	
		// RECORRER LA ARRAY
		for ($Hj=0; $Hj < count($ruta); $Hj++) { // MATRIZ CATALOGO RUTA Y INVENTARIO TIQUETE..
			$pdf->SetX(20);
				// cambiar el color de la fila.
				$pdf->Cell($w[0],$w2[1],$ruta[$Hj],1,0,'L',$fill);
				$pdf->Cell($w[1],$w2[1],mb_convert_encoding($cantidad[$Hj],"ISO-8859-1","UTF-8"),1,0,'C',$fill);
				$pdf->Cell($w[2],$w2[1],$entregados[$Hj],1,0,'C',$fill);
				$pdf->Cell($w[3],$w2[1],$devolucion[$Hj],1,0,'C',$fill);
				$pdf->Cell($w[4],$w2[1],$vendidos[$Hj],1,0,'C',$fill);
				$pdf->Cell($w[5],$w2[1],$precio_publico[$Hj],1,0,'C',$fill);
				$Ingreso = floatval(substr($ingreso[$Hj],1));
				$pdf->Cell($w[6],$w2[1],"$ " . number_format($Ingreso,2,".",","),1,0,'R',$fill);
				$pdf->ln();
				$fill=!$fill;
					$totalIngreso = $totalIngreso + floatval(substr($ingreso[$Hj],1));
		}   // FOR DE LA TABLA CATALOGO RUTA Y INVENTARIO TIQUETE..
			////////////////////////////////////////////////////
			/// Linea para colocar totoal producción.
			////////////////////////////////////////////////////
			$pdf->SetX(20);
			$totalProduccionOK = $totalIngreso;
			$pdf->Cell($w[0],$w2[1],"",1,0,'C',$fill);
			$pdf->Cell($w[1],$w2[1],"",1,0,'C',$fill);
			$pdf->Cell($w[2],$w2[1],"",1,0,'C',$fill);
			$pdf->SetFont('Arial','B',12); // I : Italica; U: Normal;
			$pdf->Cell($w[3]+$w[4]+$w[5],$w2[1],"TOTAL PRODUCCION",1,0,'R',$fill);
			$pdf->Cell($w[6],$w2[1],"$". number_format($totalProduccionOK,2,".",","),1,0,'C',$fill);
// Salida del pdf.
	$modo = 'I'; // Envia al navegador (I), Descarga el archivo (D), Guardar el fichero en un local(F).
	$nombre_archivo  = mb_convert_encoding("Producción: " . $fecha . '.pdf',"ISO-8859-1","UTF-8");
	$pdf->Output($nombre_archivo,$modo);
?>