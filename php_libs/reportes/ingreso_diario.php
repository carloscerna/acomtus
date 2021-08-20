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
        $fecha_ = cambiaf_a_normal($_REQUEST["fecha"]);
        $fecha_partial = explode("-",$fecha);
        $resumen = array('0.20','0.25','0.35');
        $resumen_pasajes = array();
        $resumen_ingresos = array();
        $resumen_pasajes_020 = array();
        $resumen_pasajes_025 = array();
        $resumen_pasajes_035 = array();
        $resumen_ingreso_020 = array();
        $resumen_ingreso_025 = array();
        $resumen_ingreso_035 = array();
        $tiquete_20 = 0;
          $db_link = $dblink;
          $salto = 0;
          $total_general_ingresos = 0;
          $total_unidades = 0;
          $total_tiquetes_vendidos = 0;
          $print_no_header = 0;
          $total_buses = 0;
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
        $dia = (int)$fecha_partial[2];		// El Día.
        $mes = $meses[(int)$fecha_partial[1]];     // El Mes.
		$año = $fecha_partial[0];		// El Año.

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
        $w=array(65,25,35,30,35); // RUTA, PASAJES, PRECIO UNITARIO, INGRESOS CANTIDAD DE BUSES.//determina el ancho de las columnas
        $h=array(5,12); //determina el ALTO de las columnas
        for($i=0;$i<count($header);$i++)
            $this->Cell($w[$i],7,utf8_decode($header[$i]),1,0,'C',1);
        $this->Ln();
        //Restauración de colores y fuentes
        $this->SetFillColor(255,255,255);
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
    $header=array('Ruta','Pasajes','Precio Unitario','Ingresos','Cantidad Controles');	
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
	$pdf->RoundedRect(15, 45, 80, 8, 2, '1234', 'DF');
	$pdf->RotatedText(18,50,'REPORTE DE INGRESO DIARIO',0);
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetFont('Arial','',11); // I : Italica; U: Normal;
//  mostrar los valores de la consulta
    $w=array(65,25,35,30,35,70,15,40); // RUTA, PASAJES, PRECIO UNITARIO, INGRESOS CANTIDAD DE BUSES. DESCRIPCION, VALOR, CONCEPTO//determina el ancho de las columnas
    $h=array(5,7); //determina el ALTO de las columnas
// Variables.
    $fill = false; $i=1;
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetXY(10,60);
//  Encabezando.
    //$pdf->FancyTable($header); // Solo carge el encabezado de la tabla porque medaba error el cargas los datos desde la consulta.		
//  DATOS NECESARIOS PARA CATALOGO RUTA
    // armando el Query. PARA LA TABLA CATALOGO RUTA.
    $query = "SELECT id_ruta, codigo, descripcion FROM catalogo_ruta ORDER BY codigo";
    // Ejecutamos el Query.
    $consulta = $dblink -> query($query);
    // crear matriz
    while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
    {
        // VARIABLES DE LA CONSULTA CATALOGO RUTA.
        $codigo_ruta = $listado['id_ruta'];
        $descripcion_ruta = $listado['descripcion'];
        /****************************************************************************************** */
            // armando el Query. PARA LA TABLA CATALOGO TIQUETE COLOR.
            $query_tc = "SELECT id_, precio_publico FROM catalogo_tiquete_color ORDER BY precio_publico";
            // Ejecutamos el Query.
            $consulta_tc = $dblink -> query($query_tc);
            // controlar el encabezado
            $fila = 0; $total_por_ruta = 0; $fila_precio = 0; $resumen_precio = array();
            // recorrer los registros
            while($listado_tc = $consulta_tc -> fetch(PDO::FETCH_BOTH))
            {
                // VARIABLES DE LA CONSULTA CATALOGO RUTA.
                $codigo_tiquete_color = $listado_tc['id_'];
                $precio_publico = $listado_tc['precio_publico'];    // ontrnho un precio que puede ser 0.20, 0.25, 0.35
                /****************************************************************************************** */
                    // REVISAR SI HUBO MOVIMIENTO EN LA PRODUCCION.
                    // CANTIDAD DE CONTROLES VENDIDOS.
                    $query_v = "SELECT * FROM produccion where codigo_estatus = '02' and fecha = '$fecha' and codigo_ruta = '$codigo_ruta' and codigo_tiquete_color = '$codigo_tiquete_color'";
                    $consulta_v = $dblink -> query($query_v);
                    // crear matriz
                    // Validar si hay registros.
                    if($consulta_v -> rowCount() != 0)
                    {
                        // OBTENER LA CANTIDAD DE TIQUETES VENDIDOS.
                        // recorrer los registros
                            $codigo_produccion_ = array();
                            while($listado_codigo_produccion = $consulta_v -> fetch(PDO::FETCH_BOTH))
                            {
                                // VARIABLES DE LA CONSULTA CATALOGO RUTA.
                                $codigo_produccion_[] = $listado_codigo_produccion['id_'];
                            }
                            // CANTIDAD DE TIQUETES VENDIDOS CON CODIGO '04'
                            $tiquete_vendido = 0; $total_vendido = 0;
                            for ($Xh=0; $Xh < count($codigo_produccion_); $Xh++)
                            {
                                $query_vendidos_04 = "select sum(cantidad) as tiquete_vendido from produccion_asignado where codigo_produccion = $codigo_produccion_[$Xh] and codigo_estatus = '05'";
                                $consulta_vendidos_04 = $dblink -> query($query_vendidos_04);
                                    // recorrer los registros
                                    while($listado_vendidos_04 = $consulta_vendidos_04 -> fetch(PDO::FETCH_BOTH))
                                    {
                                        // VARIABLES DE LA CONSULTA CATALOGO RUTA.
                                        $tiquete_vendido = $listado_vendidos_04['tiquete_vendido'];
                                        $total_vendido = $total_vendido + $tiquete_vendido;
                                    }
                            }   // CANTIDAD DE TIQUETES VENDIDOS CON CODIGO '04'
                            /******************************************************************************************************************************** /*/
                            for ($Xxh=0; $Xxh < count($codigo_produccion_); $Xxh++)
                            {
                                $query_vendidos_05 = "select sum(cantidad) as tiquete_vendido from produccion_asignado where codigo_produccion = $codigo_produccion_[$Xxh] and codigo_estatus = '04' and tiquete_cola > 0";
                                $consulta_vendidos_05 = $dblink -> query($query_vendidos_05);
                                    // recorrer los registros
                                    while($listado_vendidos_05 = $consulta_vendidos_05 -> fetch(PDO::FETCH_BOTH))
                                    {
                                        // VARIABLES DE LA CONSULTA CATALOGO RUTA.
                                        $tiquete_vendido = $listado_vendidos_05['tiquete_vendido'];
                                        $total_vendido = $total_vendido + $tiquete_vendido;
                                    }
                            }   // CANTIDAD DE TIQUETES VENDIDOS CON CODIGO '05'
                                //  Detectar el precio del tiquete
                                switch ($precio_publico) {
                                    case '0.20':
                                        $resumen_pasajes_020[] = $total_vendido; 
                                        break;
                                    case '0.25':
                                        $resumen_pasajes_025[] = $total_vendido; 
                                        break;
                                    case '0.35':
                                        $resumen_pasajes_035[] = $total_vendido; 
                                        break;
                                    default:
                                        # code...
                                        break;
                                    }  
                        // OBTENER EL INGRESO DE LA RUTA y CATNIDAD DE VUELTAS.
                        $query_ingreso = "SELECT sum(total_ingreso) as total_ingreso, count(id_) as cantidad_buses FROM produccion where codigo_estatus = '02' and fecha = '$fecha' and codigo_ruta = '$codigo_ruta' and codigo_tiquete_color = '$codigo_tiquete_color'";
                        $consulta_ingreso = $dblink -> query($query_ingreso);
                            // recorrer los registros
                            while($listado_ingreso = $consulta_ingreso -> fetch(PDO::FETCH_BOTH))
                            {
                                // VARIABLES DE LA CONSULTA CATALOGO RUTA.
                                $total_ingreso = $listado_ingreso['total_ingreso'];
                                $cantidad_buses = $listado_ingreso['cantidad_buses'];
                            }
                                // SUMAS
                                $total_por_ruta = $total_por_ruta + $total_ingreso;
                                $total_unidades = $total_unidades + $cantidad_buses;
                                // A Pantalla
                                $pdf->SetX(10);
                                $fila++;
                                /// IMPRIMIR A PANTALLA. el encabezado una sola vez.
                                if($fila == 1){
                                    // Encabezado
                                    $pdf->FancyTable($header);  
                                    // convertir a . y ,
                                    $ingresos = number_format($total_ingreso,2,'.',',');
                                    // A pantalla
                                    $pdf->Cell($w[0],$h[0],$descripcion_ruta,0,0,'L',$fill);    // Nombre ruta
                                    $pdf->Cell($w[1],$h[0],$total_vendido,0,0,'C',$fill);                   // pasajes
                                    $pdf->Cell($w[2],$h[0],$precio_publico,0,0,'C',$fill);      // Precio Publico
                                    $pdf->Cell($w[3],$h[0],$total_ingreso,0,0,'R',$fill);       // Ingresos
                                    $pdf->Cell($w[4],$h[0],$cantidad_buses,0,0,'C',$fill);      // Cantidad Buses
                                }else{
                                    // CUANDO EXISTA MÁS DE UN PRECIO
                                    $pdf->Cell($w[0],$h[0],'',0,0,'C',$fill);                   // Nombre ruta
                                    $pdf->Cell($w[1],$h[0],$total_vendido,0,0,'C',$fill);                   // pasajes
                                    $pdf->Cell($w[2],$h[0],$precio_publico,0,0,'C',$fill);      // Precio Publico
                                    $pdf->Cell($w[3],$h[0],number_format($total_ingreso,2,'.',','),0,0,'R',$fill);       // Ingresos
                                    $pdf->Cell($w[4],$h[0],$cantidad_buses,0,0,'C',$fill);      // Cantidad Buses
                                }
                                    //$pdf->Cell($w[2],$h[0],''.$query_v,0,0,'C',$fill);
                                        $pdf->ln();   
                    } 
            } // WHILE DE CATALOGO TIQUETE COLOR
                // Imprimir subtotales.
                if($total_por_ruta > 0)
                {
                    $pdf->SetX(10);
                    $pdf->Cell($w[0],$h[0],'',0,0,'L',$fill);
                    $pdf->SetFont('Arial','B',9); // I : Italica; U: Normal;
                        $pdf->Cell($w[1],$h[0],'Total Ruta: ' . $descripcion_ruta,0,0,'R',$fill);
                    $pdf->SetFont('Arial','',11); // I : Italica; U: Normal;

                    $pdf->Cell($w[2],$h[0],'',0,0,'C',$fill);
                    $pdf->Cell($w[3],$h[0],'$ '.number_format($total_por_ruta,2,'.',','),'TB',0,'R',$fill);
                    $pdf->Cell($w[4],$h[0],'',0,1,'C',$fill);
                    $pdf->ln();
                    // TOTALES GENERALES
                    $total_general_ingresos = $total_general_ingresos + $total_por_ruta;
                    $total_tiquetes_vendidos = $total_tiquetes_vendidos + $total_vendido;
                }   
    }   // fin del while principal.
//print_r($resumen_pasajes_020);
////////////////////////////////////////////////////
/// SEGUNDA PAGINA.
////////////////////////////////////////////////////
    $pdf->AddPage();
    $pdf->SetXY(10,50);
// sumar valores de la matriz.
    $resumen_pasajes[] = array_sum($resumen_pasajes_020);
    $resumen_pasajes[] = array_sum($resumen_pasajes_025);
    $resumen_pasajes[] = array_sum($resumen_pasajes_035);

    $resumen_ingresos[] = $resumen_pasajes[0] * $resumen[0];
    $resumen_ingresos[] = $resumen_pasajes[1] * $resumen[1];
    $resumen_ingresos[] = $resumen_pasajes[2] * $resumen[2];
//
    // A pantalla - TOTAL AL DIA.
    $pdf->SetFont('Arial','B',9); // I : Italica; U: Normal;
        $pdf->Cell($w[0],$h[1],'',1,0,'L',$fill);    // Nombre ruta
        $pdf->Cell($w[1],$h[1],'Pasajes',1,0,'C',$fill);                   // pasajes
        $pdf->Cell($w[3],$h[1],'Ingresos',1,0,'C',$fill);       // Ingresos
        $pdf->Cell($w[4],$h[1],'Total de Controles',1,1,'C',$fill);      // Cantidad Buses
    $pdf->SetFont('Arial','',9); // I : Italica; U: Normal;
    $pdf->SetX(10);
    $pdf->SetFont('Arial','B',10); // I : Italica; U: Normal;
        $pdf->Cell($w[0],$h[1],'TOTAL DEL DIA',0,0,'L',$fill);    // Nombre ruta
        $pdf->Cell($w[1],$h[1],array_sum($resumen_pasajes),0,0,'C',$fill);                   // pasajes
        $pdf->Cell($w[3],$h[1],'$ '.number_format($total_general_ingresos,2,'.',','),1,0,'R',$fill);       // Ingresos
        $pdf->Cell($w[4],$h[1],$total_unidades,0,1,'C',$fill);      // Cantidad Buses
    $pdf->SetFont('Arial','',9); // I : Italica; U: Normal;
/****************************************************************************************** */
    // RESUMEN
    $pdf->ln(); $pdf->ln(); 
    $pdf->SetX(10);
    $pdf->SetFont('Arial','B',10); // I : Italica; U: Normal;
        $pdf->Cell($w[0],$h[1],'Resumen:',0,1,'L',$fill);    // Nombre ruta
    $pdf->SetFont('Arial','',9); // I : Italica; U: Normal;
/****************************************************************************************** */

    for ($Xh=0; $Xh < count($resumen); $Xh++)
    {
        $pdf->Cell($w[0],$h[1],'Total pasajes $' . $resumen[$Xh],0,0,'L',$fill);    // precio
        $pdf->Cell($w[1],$h[1],$resumen_pasajes[$Xh],0,0,'R',$fill);    // cantidad pasajes
        $pdf->Cell($w[2],$h[1],'$' . $resumen[$Xh],0,0,'R',$fill);    // precio
        $pdf->Cell($w[3],$h[1],'$ '.number_format($resumen_ingresos[$Xh],2,'.',','),0,1,'R',$fill);    // cantidad pasajes
    }
    // TOTALES DEL RESUMEN
    $pdf->SetFont('Arial','B',10); // I : Italica; U: Normal;
        $pdf->Cell($w[0],$h[1],'',0,0,'L',$fill);    // precio
        $pdf->Cell($w[1],$h[1],array_sum($resumen_pasajes),'TB',0,'R',$fill);    // cantidad pasajes
        $pdf->Cell($w[2],$h[1],'',0,0,'L',$fill);    // precio
        $pdf->Cell($w[3],$h[1],'$ '.number_format(array_sum($resumen_ingresos),2,'.',','),'TB',1,'R',$fill);    // cantidad pasajes
    $pdf->SetFont('Arial','',9); // I : Italica; U: Normal;
/****************************************************************************************** */
    //  DIFERENCIAS
        $pdf->ln(); $pdf->ln(); 
        $pdf->SetX(10);
        $pdf->SetFont('Arial','B',10); // I : Italica; U: Normal;
            $pdf->Cell($w[0],$h[1],'Diferencias:',0,0,'L',$fill);    // Nombre ruta
        $pdf->SetFont('Arial','',9); // I : Italica; U: Normal;
        $pdf->ln(); 
        // consulta.
        $query_c = "SELECT * FROM produccion_diferencias
        WHERE fecha = '$fecha'
            ORDER BY id_";
        // Ejecutamos el query
        $consulta = $dblink -> query($query_c);              
        // obtener el último dato en este caso el Id_
        if($consulta -> rowCount() != 0){
            while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
            {
                $id_ = trim($listado['id_']);		
                $nombre = trim($listado['descripcion']);
                $valor = trim($listado['valor']);		
                $concepto = trim($listado['concepto']);		
                //
                $pdf->Cell($w[5],$h[0],$nombre,0,0,'L',$fill);    // descripcion
                $pdf->Cell($w[6],$h[0],'$ ' . $valor,0,0,'L',$fill);    // valor
                $pdf->Cell($w[7],$h[0],$concepto,0,1,'L',$fill);    // concepto
            }		
        }
/****************************************************************************************** */
    // REVISADO POR
    $pdf->ln(); $pdf->ln(); $pdf->ln(); $pdf->ln(); 
    $pdf->SetX(10);
        $pdf->Cell($w[0],$h[1],'Revisado por:',0,0,'L',$fill);    // Nombre ruta
    $pdf->ln(); $pdf->ln(); 
    $pdf->SetX(10);
        $pdf->Cell($w[0],$h[1],'_______________________________________',0,0,'L',$fill);    // Nombre ruta

// Salida del pdf.
	$modo = 'I'; // Envia al navegador (I), Descarga el archivo (D), Guardar el fichero en un local(F).
	$print_nombre = "Ingreso diario" . $fecha_ . '.pdf';
	$pdf->Output($print_nombre,$modo);
?>