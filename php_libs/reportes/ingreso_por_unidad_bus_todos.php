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
        $fecha = trim($_REQUEST['fecha']);
        $fecha_inicio = '2020-12-01';
        $fecha_ = cambiaf_a_normal($_REQUEST["fecha"]);
        $fecha_partial = explode("/",$fecha_);
        //print_r($fecha_partial);
        $numero_dias = 0;
        $db_link = $dblink;
        $salto = 1;
        $print_no_header = 0;
        $total_general = 0;
        $total_general_tiquete = 0;
//  imprimir datos del bachillerato.
           //
	    // Establecer formato para la fecha.
	    // 
		date_default_timezone_set('America/El_Salvador');
		setlocale(LC_TIME,'es_SV');
	    //
		//$dias = array("Domingo","Lunes","Martes","Miercoles","Jueves","Viernes","Sábado");
            $meses = array("","enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre");
        //Salida: Viernes 24 de Febrero del 2012		
		//Crear una línea. Fecha.
		//$dia = strftime("%d");		// El Día.
        //$mes = $meses[date('n')-1];     // El Mes.
        //$año = strftime("%Y");		// El Año.
        $dia = $fecha_partial[0];		// El Día.
        $mesentero = intval($fecha_partial[1]);
        $mes = $meses[$mesentero];
        //$mes = $meses[$fecha_partial[1]];     // El Mes.
        $año = $fecha_partial[2];		// El Año.
        $numero_dias = cal_days_in_month(CAL_GREGORIAN, $fecha_partial[1], $fecha_partial[2]); // 31

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
	$this->RotatedText(15,260, utf8_decode('Revisado por:'), 0);    // Nombre1
	//$this->RotatedText(15,265, utf8_decode('Presidente'), 0);    // Cargo1
    //Crear una línea de la primera firma.
	//$this->Line(115,255,200,255);
	//$this->RotatedText(115,260, utf8_decode('Gladis Marisol López'), 0);    // Nombre2
	//$this->RotatedText(115,265, utf8_decode('Contador'), 0);    // Cargo2
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
        global $suma_p, $suma_d, $saldo_p;
        $this->SetX(10);
        $this->Cell(40,7,'SUMAS  ',1,0,'C',$fill);
        $this->Cell(20,7,'FIANZAS $',0,0,'C',$fill);
        $this->Cell(25,7,$suma_p,1,0,'C',$fill);
        $this->Cell(30,7,'DEVOLUCIONES $',0,0,'C',$fill);
        $this->Cell(25,7,$suma_d,1,0,'C',$fill);
        $this->Cell(25,7,'SALDO $',0,0,'C',$fill);
        $this->Cell(25,7,$saldo_p,1,1,'C',$fill);
    }

    if($print_no_header === 0){
        //Colores, ancho de línea y fuente en negrita
        $this->SetFillColor(255,255,255);
        $this->SetTextColor(0);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont('','B');
        //  mostrar los valores de la consulta
        $w=array(25,25,35,40,40); // RUTA, PASAJES, PRECIO UNITARIO, INGRESOS CANTIDAD DE BUSES. DESCRIPCION, VALOR, CONCEPTO//determina el ancho de las columnas
        $h=array(5,12); //determina el ALTO de las columnas
        for($i=0;$i<count($header);$i++)
            $this->Cell($w[$i],7,utf8_decode($header[$i]),1,0,'C',1);
        $this->Ln();
        //Restauración de colores y fuentes
        $this->SetFillColor(224,235,255);
        $this->SetTextColor(0);
        $this->SetFont('');
        // Ubicación del eje X.
        $this->SetX(15);	
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
    $header=array('N.º Unidad','N.º Placa','Precio Unitario','Tiquetes Vendidos','Ingresos');	
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
	$pdf->RotatedText(130,40,'Santa Ana, ' . $dia . ' de ' . $mes . ' de ' . $año,0);
	// estado de cuenta
	$pdf->RoundedRect(15, 45, 130, 8, 2, '1234', 'DF');
	$pdf->RotatedText(18,50,"REPORTE DE INGRESO POR UNIDAD DEL 1 AL $numero_dias/$fecha_partial[1]/$fecha_partial[2]",0);
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetFont('Arial','',11); // I : Italica; U: Normal;
//  mostrar los valores de la consulta
    $w=array(25,25,35,40,40); // RUTA, PASAJES, PRECIO UNITARIO, INGRESOS CANTIDAD DE BUSES. DESCRIPCION, VALOR, CONCEPTO//determina el ancho de las columnas
    $h=array(5,7); //determina el ALTO de las columnas
// Variables.
    $fill = false; $i=1;
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetXY(15,60);
//  Encabezando.
    $pdf->FancyTable($header); // Solo carge el encabezado de la tabla porque medaba error el cargas los datos desde la consulta.		
//  DATOS NECESARIOS PARA CATALOGO RUTA
    // armando el Query. PARA LA TABLA CATALOGO RUTA.
    $query = "SELECT id_, numero_equipo, numero_placa FROM transporte_colectivo ORDER BY numero_placa";
    // Ejecutamos el Query.
    $consulta = $dblink -> query($query);
    // crear matriz
    while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
    {
        // VARIABLES DE LA CONSULTA CATALOGO RUTA.
        $codigo_tc = $listado['id_'];
        $numero_equipo = trim($listado['numero_equipo']);
        $numero_placa = trim($listado['numero_placa']);
        /****************************************************************************************** */
            // armando el Query. PARA LA TABLA CATALOGO TIQUETE COLOR.
           $query_tc = "SELECT pro.codigo_transporte_colectivo, SUM(pro.total_ingreso) AS total_ingreso_por_bus,
                tc.numero_equipo, tc.numero_placa, pro.codigo_tiquete_color, cat_tc.precio_publico
                    FROM produccion pro
                        INNER JOIN transporte_colectivo tc ON tc.id_ = pro.codigo_transporte_colectivo
                        INNER JOIN catalogo_tiquete_color cat_tc ON cat_tc.id_ = pro.codigo_tiquete_color
                            WHERE pro.codigo_transporte_colectivo = '$codigo_tc' AND pro.fecha >= '$fecha_partial[2]-$fecha_partial[1]-01' AND pro.fecha <= '$fecha_partial[2]-$fecha_partial[1]-$numero_dias'
                                GROUP BY pro.codigo_transporte_colectivo, tc.numero_equipo, tc.numero_placa, pro.codigo_tiquete_color, cat_tc.precio_publico";
            // Ejecutamos el Query.
            $consulta_tc = $dblink -> query($query_tc);
            // controlar el encabezado
            $fila = 0; $sub_total_ingreso = 0; $sub_total_tiquete = 0;
            // recorrer los registros
            while($listado_tc = $consulta_tc -> fetch(PDO::FETCH_BOTH))
            {
                $fila++;
                    $total_ingreso_por_bus = trim($listado_tc['total_ingreso_por_bus']);
                    $precio_publico = trim($listado_tc['precio_publico']);
                    $cantidad_tiquete_vendido = round($total_ingreso_por_bus / $precio_publico,0);
                $sub_total_ingreso = $sub_total_ingreso + $total_ingreso_por_bus;
                $sub_total_tiquete = $sub_total_tiquete + $cantidad_tiquete_vendido;

                    if($consulta_tc -> rowCount() != 0)
                    {
                        $pdf->SetX(15);
                        $pdf->Cell($w[0],$h[0],$numero_equipo,0,0,'C',$fill);    // N.º Unidad
                        $pdf->Cell($w[1],$h[0],$numero_placa,0,0,'C',$fill);    // N.º Placa
                        $pdf->Cell($w[2],$h[0],'$ ' . $precio_publico,0,0,'R',$fill);    // Precio Público
                        $pdf->Cell($w[3],$h[0],$cantidad_tiquete_vendido,0,0,'R',$fill);    // Cantidad Tiquetes Vendidos
                        $pdf->Cell($w[4],$h[0],'$ ' . number_format($total_ingreso_por_bus,2,'.',','),0,1,'R',$fill);    // Total ingreso por bus
                        $salto++;
                        $fill=!$fill;
                            ////////////////////////////////////////////////////
                            /// Linea para colocar el PIe de Página y los Saldos
                            ////////////////////////////////////////////////////
								if($salto > 38){
									$salto = 1;
									$pdf->AddPage();
									$pdf->SetXY(15,45);
									$pdf->FancyTable($header); // Solo carge el encabezado de la tabla porque medaba error el cargas los datos desde la consulta.		
                                }
                    // TOTALES INGRESOS Y TIQUETES VENDIDOS.
                        $total_general = $total_general + $total_ingreso_por_bus;
                        $total_general_tiquete = $total_general_tiquete + $cantidad_tiquete_vendido;
                    }else{
                        $total_ingreso_por_bus = 0; $total_general_tiquete = 0;
                    } 
            } // WHILE DE CATALOGO TIQUETE COLOR
                if($fila > 1){
                    $pdf->SetX(15);
                    $pdf->Cell($w[0],$h[1],'',0,0,'C',$fill);    // N.º Unidad
                    $pdf->Cell($w[1],$h[1],'',0,0,'C',$fill);    // N.º Placa
                    $pdf->Cell($w[2],$h[1],'',0,0,'R',$fill);    // Precio Público
                    $pdf->SetFont('Arial','B',11); // I : Italica; U: Normal;			
                    $pdf->Cell($w[3],$h[1],$sub_total_tiquete,'BT',0,'R',$fill);    // Cantidad Tiquetes Vendidos
                    $pdf->Cell($w[4],$h[1],'$ ' . number_format($sub_total_ingreso,2,'.',','),'BT',1,'R',$fill);    // Total ingreso por bus
                    $pdf->SetFont('Arial','',11); // I : Italica; U: Normal;			
                    $salto++;
                    $fill=!$fill;
                        ////////////////////////////////////////////////////
                        /// Linea para colocar el PIe de Página y los Saldos
                        ////////////////////////////////////////////////////
                            if($salto > 38){
                                $salto = 1;
                                $pdf->AddPage();
                                $pdf->SetXY(15,45);
                                $pdf->FancyTable($header); // Solo carge el encabezado de la tabla porque medaba error el cargas los datos desde la consulta.		
                            }
                }
    }   // fin del while principal.

// DESPUES DE RECORRER EL TRANSPORTE COLECTIVO.
    $pdf->ln();
    $pdf->SetFont('Arial','B',12); // I : Italica; U: Normal;			
    $pdf->Cell($w[0],$h[0],'',0,0,'C',$fill);    // N.º Unidad
    $pdf->Cell($w[1],$h[0],'',0,0,'C',$fill);    // N.º Placa
    $pdf->Cell($w[2],$h[0],'TOTALES',0,0,'R',$fill);    // Precio Público
    $pdf->Cell($w[3],$h[0],$total_general_tiquete,0,0,'C',$fill);    // Cantidad Tiquetes Vendidos
    $pdf->Cell($w[4],$h[0],'$ ' . number_format($total_general,2,'.',','),1,1,'C',$fill);    // Total ingreso por bus    
/****************************************************************************************** */
    // REVISADO POR
/*
    $pdf->ln(); $pdf->ln(); $pdf->ln(); $pdf->ln(); 

    $pdf->SetX(10);
        $pdf->Cell($w[0],$h[1],'Revisado por:',0,0,'L',$fill);    // Nombre ruta
    $pdf->ln(); $pdf->ln(); 
    $pdf->SetX(10);
        $pdf->Cell($w[0],$h[1],'_______________________________________',0,0,'L',$fill);    // Nombre ruta
        */
// Salida del pdf.
	$modo = 'I'; // Envia al navegador (I), Descarga el archivo (D), Guardar el fichero en un local(F).
	$print_nombre = "Ingreso diario por unidad" . $fecha_ . '.pdf';
	$pdf->Output($print_nombre,$modo);
?>