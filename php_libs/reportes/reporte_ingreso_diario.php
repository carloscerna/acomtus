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
        $fecha_inicio = trim($_REQUEST['fecha_inicio']);
        $fecha_final = trim($_REQUEST['fecha_final']);
        $fecha_ = cambiaf_a_normal($_REQUEST["fecha_inicio"]);
        $fecha_f = cambiaf_a_normal($_REQUEST["fecha_final"]);
        $fecha_partial = explode("-",$fecha_inicio);
        $fecha_partial_f = explode("-",$fecha_final);
        $db_link = $dblink;
//  imprimir datos del bachillerato.
           //
	    // Establecer formato para la fecha.
	    // 
		date_default_timezone_set('America/El_Salvador');
		setlocale(LC_TIME,'es_SV');
	    //
		    $nombresDias = array("Domingo","Lunes","Martes","Miercoles","Jueves","Viernes","Sábado");
            $meses = array("","enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre");
        // definimos 2 array uno para los nombre de los dias y otro para los nombres de los meses
                $nombresMeses = array(1=>"Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
        //Salida: Viernes 24 de Febrero del 2012		
		//Crear una línea. Fecha.
		$dias_numero = strftime("%d");		// El Día.
        //$mes = $meses[date('n')-1];     // El Mes.
        //$año = strftime("%Y");		// El Año.
        $dia = (int)$fecha_partial[2];		// El Día.
        $mes = $meses[(int)$fecha_partial[1]];     // El Mes.
		$año = $fecha_partial[0];		// El Año.
        //
        $dia_f = (int)$fecha_partial_f[2];		// El Día.
        $mes_f = $meses[(int)$fecha_partial_f[1]];     // El Mes.
		$año_f = $fecha_partial_f[0];		// El Año.
		setlocale(LC_MONETARY,"es_ES");
// establecemos la fecha de inicio
$inicio =  DateTime::createFromFormat('Y-m-d', $fecha_inicio, new DateTimeZone('America/El_Salvador'));
// establecemos la fecha final (fecha de inicio + dias que queramos)
$fin =  DateTime::createFromFormat('Y-m-d', $fecha_final, new DateTimeZone('America/El_Salvador'));
// definier el número de días dependiendo de la quincena.
    //$modify_dias = $fecha_final - $fecha_inicio;
    //$fin->modify($modify_dias);
// creamos el periodo de fechas
$periodo = new DatePeriod($inicio, new DateInterval('P1D') ,$fin);

// Crear Matriz para el # de dia y nombre del dia.
$nombreDia_a = array(); $numeroDia_a = array();

// recorremos las dechas del periodo
foreach($periodo as $date){
    // definimos la variables para verlo mejor
    $nombreDia = $nombresDias[$date->format("w")];
    $nombreMes = $nombresMeses[$date->format("n")];
    $numeroDia = $date->format("j");
    $anyo = $date->format("Y");
    // mostramos los datos
    //echo $nombreDia.' '.$numeroDia.' de '.$nombreMes.' de '.$anyo.'<br>';
    $nombreDia_a[] = $nombreDia;
    $numeroDia_a[] = $numeroDia;
    
    //echo $nombreDia.' '.$numeroDia.'<br>';
}
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
    //Posición: a 1,5 cm del final
    $this->SetY(-10);
    //Arial italic 8
    $this->SetFont('Arial','I',9);

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
    $header=array('');	
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
	$pdf->RotatedText(130,40,'Santa Ana, ' . (int)$dias_numero . ' de ' . $mes . ' de ' . $año,0);
	// estado de cuenta
	$pdf->RoundedRect(15, 45, 160, 8, 2, '1234', 'DF');
	$pdf->RotatedText(18,50,'Ingreso del  '. (int)$dia . ' de ' . $mes . ' de ' . $año . " al " . (int)$dia_f . ' de ' . $mes_f . ' de ' . $año_f,0);
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetFont('Arial','',11); // I : Italica; U: Normal;
//  mostrar los valores de la consulta
    $w=array(50); // RUTA, PASAJES, PRECIO UNITARIO, INGRESOS CANTIDAD DE BUSES. DESCRIPCION, VALOR, CONCEPTO//determina el ancho de las columnas
    $h=array(10); //determina el ALTO de las columnas
// Variables.
    $fill = false; $i=1;
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetXY(10,60);
//  Encabezando.
    // armando el Query. PARA LA TABLA CATALOGO RUTA.
    $query = "SELECT * FROM produccion_diaria WHERE fecha >= '$fecha_inicio' and fecha<= '$fecha_final' ORDER BY fecha";
    // crear varialbes array();
        $fecha_a = array(); $total_dolares_a = array(); $total_colones_a = array(); $salto_linea = 0; $dia_numero = 0; $yy = 60; $xx = 10; $y_linea = 0; $yyy = 0;
       
    // Ejecutamos el Query.
    $consulta = $dblink -> query($query);
    // recorrer consulta
    if($consulta -> rowCount() != 0){
        while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
        {
            // VARIABLES DE LA CONSULTA CATALOGO RUTA.
            $fecha_a[] = ($listado['fecha']);
            $total_dolares_a[] = $listado['total_dolares'];
            $total_colones_a[] = $listado['total_colones'];
            //
        }   // fin del while principal.
        // IMPRIMIR VALORES EN PANTALLA PROVENIENTE DE LA MATRIZ.
            $pdf->SetFont('Arial','',16); // I : Italica; U: Normal;pri
//print count($fecha_a);
                for($jji=0;$jji<count($nombreDia_a);$jji++){
                        // armar texto
                        $direccion = utf8_decode($nombreDia_a[$jji]) . " " . $numeroDia_a[$jji] . "\n" . utf8_decode(" ¢ ") . number_format($total_colones_a[$jji],2,".",",");
                    if($salto_linea == 3){
                        $pdf->SetXY($xx,$yy);
                        $pdf->MultiCell($w[0],$h[0],$direccion,'LRTB','C',$fill);
                        $pdf->ln();
                        $xx = 10;
                        $yy = ($yy + 20);
                        $salto_linea = 0;
                        //$pdf->SetXY(10,$yy);

                        //$pdf->MultiCell($w[0],$h[0],$direccion . $yy . "-".$salto_linea,'LRTB','C',$fill);
                     }else{
                        // Producción
                        $pdf->SetXY($xx,$yy);
                        //$pdf->SetX($xx);
                        $pdf->MultiCell($w[0],$h[0],$direccion,'LRTB','C',$fill,2);
                        $xx = $xx + 50;
                        //$pdf->MultiCell($w[0],$h[0],$direccion . $yy . "-".$salto_linea,'LRTB','C',$fill,2);
                        $salto_linea++;
                    }
                    
                }
            $pdf->SetFont('Arial','',11); // I : Italica; U: Normal;
    }else{
        $pdf->Cell($w[0],$h[0],'NO HAY REGISTROS...','R',0,'C',$fill);
    }
/****************************************************************************************** */
    // RESUMEN
    $pdf->ln();
/****************************************************************************************** */
// Salida del pdf.
	$modo = 'I'; // Envia al navegador (I), Descarga el archivo (D), Guardar el fichero en un local(F).
	$print_nombre = "Reporte Ingreso diario" . $fecha_ . '.pdf';
	$pdf->Output($print_nombre,$modo);
?>