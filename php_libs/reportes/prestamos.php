<?php
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);
// archivos que se incluyen.
    include($path_root."/acomtus/includes/funciones.php");
    include($path_root."/acomtus/includes/mainFunctions_conexion.php");

// Llamar a la libreria fpdf
    include($path_root."/acomtus/php_libs/fpdf/fpdf.php");

// cambiar a utf-8.
    header("Content-Type: text/html; charset=UTF-8");
// variables y consulta a la tabla.
	// VALORES DEL POST
		$id_p_p = trim($_REQUEST['codigo_personal']);
		$new = explode(":",$id_p_p);
		$id_ = $new[0];
		$codigo = trim($new[1]);	
      	$db_link = $dblink;
	  	$saldos = 0; $print_sumas = 0; $print_no_header = 0;
        //
	    // Establecer formato para la fecha.
	    // 
		date_default_timezone_set('America/El_Salvador');
		setlocale(LC_TIME,'es_SV');
	    //
		//$dias = array("Domingo","Lunes","Martes","Miercoles","Jueves","Viernes","Sábado");
                $meses = array("enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre");
                //Salida: Viernes 24 de Febrero del 2012		
		//Crear una línea. Fecha.
		$dia = strftime("%d");		// El Día.
        $mes = $meses[date('n')-1];     // El Mes.
		$año = strftime("%Y");		// El Año.
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
        $this->RotatedText(30,10,utf8_decode($_SESSION['nombre_institucion']),0);
    //Arial bold 13
        $this->SetFont('Arial','B',12);
	$this->RotatedText(30,17,utf8_decode($_SESSION['direccion']),0);
	
    // Teléfono.
	if(empty($_SESSION['telefono'])){
	    $this->RotatedText(30,24,'',0,1,'C');    
	}else{
	    $this->RotatedText(30,24,utf8_decode('Teléfono: ').$_SESSION['telefono'],0,1,'C');
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
	
if ($print_sumas === 1){
    //Crear una línea de la primera firma.
    $this->Line(15,255,85,255);    
	$this->RotatedText(15,260, utf8_decode('Julio Salvador Figueroa'), 0);    // Nombre1
	$this->RotatedText(15,265, utf8_decode('Presidente'), 0);    // Cargo1
    //Crear una línea de la primera firma.
	$this->Line(115,255,200,255);
	$this->RotatedText(115,260, utf8_decode('Gladis Marisol López'), 0);    // Nombre2
	$this->RotatedText(115,265, utf8_decode('Contador'), 0);    // Cargo2
    // ARMAR pie de página.
	$style6 = array('width' => 0.3, 'cap' => 'butt', 'join' => 'miter', 'dash' => '0', 'color' => array(0,0,0));
}
    //Crear una línea de la primera firma.
    $this->Line(0,270,225,270);
    //Número de página
	$this->SetFont('Arial','','7');
    $fecha = date("l, F jS Y ");
    $this->Cell(0,10,utf8_decode('Página '.$this->PageNo().'/{nb}       '),0,0,'C');	
}
//Tabla coloreada
function FancyTable($header)
{
	global $print_sumas, $codigo, $dblink, $fill, $print_no_header;
//////////////////////////////////////////////////////////////////////////////////////
// 	PROCESO PARA CALCULAR SUMAS DE PRESTAMOS, DESCUENTOS Y SALDO.
//////////////////////////////////////////////////////////////////////////////////////	
if ($print_sumas === 1){
						// Armar query.
						$query_saldo = "select round(sum(prestamos),2) as suma_prestamos, round(sum(descuentos),2) as suma_descuentos,  round((sum(prestamos) - sum(descuentos)),2) as saldo_prestamos from prestamos where codigo = '$codigo'";	
						// Ejecutamos el Query. PARA LA TABLA EMPLEADOS.
							$consulta_saldo = $dblink -> query($query_saldo);
							$this->ln();
								while($sumas = $consulta_saldo-> fetch(PDO::FETCH_BOTH))
									{
										// recopilar los valores de los campos.
										$suma_p= trim($sumas['suma_prestamos']);
										$suma_d= trim($sumas['suma_descuentos']);
										$saldo_p = trim($sumas['saldo_prestamos']);
										// TOTALES SUMAS Y SALDO
										
										
										
									}
								$this->SetX(10);
								$this->Cell(40,7,'SUMAS  ',1,0,'C',$fill);
								$this->Cell(25,7,'PRESTAMOS $',0,0,'C',$fill);
								$this->Cell(25,7,$suma_p,1,0,'C',$fill);
								
								$this->Cell(25,7,'DESCUENTOS $',0,0,'C',$fill);
								$this->Cell(25,7,$suma_d,1,0,'C',$fill);
								
								$this->Cell(25,7,'SALDO $',0,0,'C',$fill);
								$this->Cell(25,7,$saldo_p,1,1,'C',$fill);
}

if($print_no_header === 0){
    //Colores, ancho de línea y fuente en negrita
    $this->SetFillColor(133,146,158);
    $this->SetTextColor(255);
    $this->SetDrawColor(128,0,0);
    $this->SetLineWidth(.3);
    $this->SetFont('','B');
//  mostrar los valores de la consulta
    $w=array(25,25,25,120); // FECHA, FIANZAS, DEVOLUCION Y DESCRIPCION.//determina el ancho de las columnas
    $w2=array(6,12); //determina el ALTO de las columnas
        
    for($i=0;$i<count($header);$i++)
        $this->Cell($w[$i],5,utf8_decode($header[$i]),'LTR',0,'C',1);
    $this->Ln();
    //Restauración de colores y fuentes
    $this->SetFillColor(224,235,255);
    $this->SetTextColor(0);
    $this->SetFont('');
	// Ubicación del eje X.
	$this->SetX(10);	
    //Datos
    $fill=false;}
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
    $header=array('Fecha','Prestamos $','Descuentos $','Descripción');	
//Títulos de las columnas
    $pdf->AliasNbPages();
    $pdf->AddPage();

// Aqui mandamos texto a imprimir o al documento.
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetY(20);
    $pdf->SetX(15);
// Diseño de Lineas y Rectangulos.
    $pdf->SetFillColor(224);
	// FECHA.
	$pdf->RotatedText(140,40,'Santa Ana, ' . $dia . ' de ' . $mes . ' de ' . $año,0);
	// estado de cuenta
	$pdf->RoundedRect(15, 45, 80, 8, 2, '1234', 'DF');
	$pdf->RotatedText(18,50,'ESTADO DE CUENTA - PRESTAMOS',0);
	// CODIGO
	$pdf->RoundedRect(45,55, 30, 8, 2, '1234', '');
	$pdf->RotatedText(23,60,utf8_decode('CÓDIGO:'),0);
	// NOMBRE	
    $pdf->RoundedRect(45,65, 147, 8, 2, '1234', '');
	$pdf->RotatedText(23,70,utf8_decode('NOMBRE:'),0);
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetFont('Arial','',9); // I : Italica; U: Normal;
//  mostrar los valores de la consulta
    $w=array(25,25,25,120,5); // FECHA, FIANZAS, DEVOLUCION Y DESCRIPCION.//determina el ancho de las columnas
    $w2=array(5,12); //determina el ALTO de las columnas
// Variables.
    $fill = false; $i=1;
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetXY(10,80);
//  Encabezando.
    $pdf->FancyTable($header); // Solo carge el encabezado de la tabla porque medaba error el cargas los datos desde la consulta.		
// armando el Query. PARA LA TABLA HISTORIAL.
	$query_historial = "SELECT p.id_prestamos, p.codigo, p.fecha, p.prestamos, p.descuentos, p.descripcion, per.apellidos, per.nombres
			FROM prestamos p
			INNER JOIN personal per ON per.codigo = p.codigo
			WHERE p.codigo = '$codigo' ORDER BY p.fecha DESC";
		// Ejecutamos el Query. PARA LA TABLA EMPLEADOS.
			$consulta_historial = $dblink -> query($query_historial);	
		// Recorriendo la Tabla con PDO::
			$num_registros = $consulta_historial -> rowCount();
			$num = 0; $salto = 0;

			if($num_registros !=0){
			// cambiar el valor de las variables
				$respuestaOK = true;
				$mensajeError = "Si Registro";
				
				while($listadoHistorial = $consulta_historial -> fetch(PDO::FETCH_BOTH))
					{
					$num++; $salto++;	
						// recopilar los valores de los campos.
						$nombre_completo = utf8_decode(trim($listadoHistorial['nombres']) . ' ' . trim($listadoHistorial['apellidos']));
						$prestamos = trim($listadoHistorial['prestamos']);
						$descuentos = trim($listadoHistorial['descuentos']);
						$fecha = cambiaf_a_normal(trim($listadoHistorial['fecha']));
						$historial = utf8_decode(strtolower((trim($listadoHistorial['descripcion']))));
						// Dar un salto más por la cantidad de caracteres del historial.
						if(strlen($historial) > 75){$salto++;}
					if($num === 1){
						$codigo = trim($listadoHistorial['codigo']);
						$pdf->SetFont('Arial','B',12); // I : Italica; U: Normal;										
						$pdf->RotatedText(48,60,$codigo,0);
						$pdf->RotatedText(48,70,$nombre_completo,0);
						$pdf->SetFont('Arial','',9); // I : Italica; U: Normal;
						// IMPRIMIR VALORES EN PANTALLA
						$pdf->Cell($w[0],$w2[0],$fecha,'R',0,'C',$fill);
						$pdf->Cell($w[0],$w2[0],$prestamos,'R',0,'R',$fill);
						$pdf->Cell($w[0],$w2[0],$descuentos,'R',0,'R',$fill);
						$pdf->MultiCell1($w[3],$w2[0],$historial,0,'J',$fill,2);
					}else{
						// IMPRIMIR VALORES EN PANTALLA
						$pdf->Cell($w[0],$w2[0],$fecha,'R',0,'C',$fill);
						$pdf->Cell($w[0],$w2[0],$prestamos,'R',0,'R',$fill);
						$pdf->Cell($w[0],$w2[0],$descuentos,'R',0,'R',$fill);
						$pdf->MultiCell1($w[3],$w2[0],$historial,0,'J',$fill,2);
					}
							// Ubicación del eje X.
							$pdf->SetX(10);	
							//$pdf->MultiCell(180,10,utf8_decode("$historial"));					
							$fill=!$fill;	
								////////////////////////////////////////////////////
								/// Linea para colocar el PIe de Página y los Saldos
								////////////////////////////////////////////////////
								if($salto >= 35){
									$salto = 1;
									$pdf->AddPage();
									$pdf->SetXY(10,50);
									$pdf->FancyTable($header); // Solo carge el encabezado de la tabla porque medaba error el cargas los datos desde la consulta.		
								}
					} /// FIN DEL WHILE.
			}
			else{
				$contenidoOK = 'No hay registros de este usuario...';
				$mensajeError = "No Registro";
				exit;
			}
			////////////////////////////////////////////////////
			/// Linea para colocar el PIe de Página y los Saldos
			////////////////////////////////////////////////////
			if($salto >= 1 and $salto < 29){
				$print_sumas = 1;
				$print_no_header = 1;
				$pdf->FancyTable($header); // Solo carge el encabezado de la tabla porque medaba error el cargas los datos desde la consulta.
				$pdf->SetXY(10,40);
			}
// Salida del pdf.
	$modo = 'I'; // Envia al navegador (I), Descarga el archivo (D), Guardar el fichero en un local(F).
	$print_nombre = trim($nombre_completo) . '.pdf';
	$pdf->Output($print_nombre,$modo);
?>