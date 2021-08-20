<?php
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);
// Archivos que se incluyen.
     include($path_root."/acomtus/includes/mainFunctions_conexion.php");
// Llamar a la libreria fpdf
     include($path_root."/acomtus/php_libs/fpdf/fpdf.php");
// cambiar a utf-8.
     header("Content-Type: text/html; charset=UTF-8");    
// variables y consulta a la tabla.
     $fecha_mes = $_REQUEST["fechaMes"];
     $fecha_ann = $_REQUEST["fechaAnn"];
     $quincena = $_REQUEST["quincena"];
     $ruta = $_REQUEST["ruta"];
     $RutaText = $_REQUEST["RutaText"];
     $db_link = $dblink;
     $total_dias_quincena = 0;
     $reporte_trabajo = "";
     $InicioFinDia = 0;
//  imprimir datos del bachillerato.
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
//        $total_de_dias = date('t');    // total de dias.
        $total_de_dias = cal_days_in_month(CAL_GREGORIAN, (int)$fecha_mes, $año);
        $NombreMes = $meses[(int)$fecha_mes - 1];

// definimos 2 array uno para los nombre de los dias y otro para los nombres de los meses
$nombresDias = array("D", "L", "M", "M", "J", "V", "S" );
$nombresMeses = array(1=>"Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
// ARMANR FECHA DEPENDIENDO DE LA QUINCENA
    if($quincena == "Q1"){
        $fecha_inicio = $año . '-' . $fecha_mes . '-01'; 
    }
    if($quincena == "Q2"){
        $fecha_inicio = $año . '-' . $fecha_mes . '-16'; 
    }
// establecemos la fecha de inicio
$inicio =  DateTime::createFromFormat('Y-m-d', $fecha_inicio, new DateTimeZone('America/El_Salvador'));
// establecemos la fecha final (fecha de inicio + dias que queramos)
$fin =  DateTime::createFromFormat('Y-m-d', $fecha_inicio, new DateTimeZone('America/El_Salvador'));
// definier el número de días dependiendo de la quincena.
if($quincena == "Q1"){
    $total_dias_quincena = 15;
    $modify_dias = '+'.$total_dias_quincena.' day';
    $fin->modify($modify_dias);
}
if($quincena == "Q2"){
    $total_dias_quincena = $total_de_dias - 15;
    $InicioFinDia = 0;
    $modify_dias = '+'.$total_dias_quincena.' day';
    $fin->modify($modify_dias);
}
// creamos el periodo de fechas
$periodo = new DatePeriod($inicio, new DateInterval('P1D') ,$fin);

// Crear Matriz para el # de dia y nombre del dia.
$nombreDia_a = array(); $numeroDia_a = array(); $fecha_periodo = array();

// recorremos las dechas del periodo
foreach($periodo as $date){
    // definimos la variables para verlo mejor
    $nombreDia = $nombresDias[$date->format("w")];
    $nombreMes = $nombresMeses[$date->format("n")];
    $numeroDia = $date->format("j");
    $numeroDiaDosDigitos = $date->format("d");
    $anyo = $date->format("Y");
    // mostramos los datos
    //echo $nombreDia.' '.$numeroDia.' de '.$nombreMes.' de '.$anyo.'<br>';
    //$fecha_periodo[] = $numeroDia.'-'.$nombreMes.'-'.$anyo;
    $fecha_periodo[] = $anyo.'-'.$fecha_mes.'-'.$numeroDiaDosDigitos;
    $nombreDia_a[] = $nombreDia;
    $numeroDia_a[] = $numeroDia;
    
    //echo $nombreDia.' '.$numeroDia.'<br>';
}
    //print_r($fecha_periodo);
    //exit;
    // ARMAR EL NOMBRE DLE REPORTE CON NOMBRE QUINCE DE TAL DIA A TAL DIA.
    if($quincena == "Q1"){
        $reporte_trabajo = "Reporte de trabajo correspondiente a la quincena del 1 al 15 de $NombreMes de $anyo";
        $reporte_ruta = "NOMBRE DE MOTORISTAS ($RutaText)";
    }
    if($quincena == "Q2"){
        $reporte_trabajo = "Reporte de trabajo correspondiente a la quincena del 16 al $total_de_dias de $NombreMes de $anyo";
        $reporte_ruta = "NOMBRE DE MOTORISTAS ($RutaText)";
    }
class PDF extends FPDF
{
//Cabecera de página
function Header()
{
    global $reporte_trabajo, $reporte_ruta;
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
    $this->RotatedText(30,17,utf8_decode($reporte_trabajo),0);
    $this->RotatedText(30,22,utf8_decode($reporte_ruta),0);
// Posición en donde va iniciar el texto.
    $this->SETY(35);
}

//Pie de página
function Footer()
{
//
  // Establecer formato para la fecha.
  // 
   date_default_timezone_set('America/El_Salvador');
   setlocale(LC_TIME, 'spanish');
    //Posición: a 1,5 cm del final
    $this->SetY(-15);
    //Arial italic 8
    $this->SetFont('Arial','I',8);
    //Crear ubna línea
    $this->Line(10,285,200,285);
    //Número de página
    $fecha = date("l, F jS Y ");
    $this->Cell(0,10,'Page '.$this->PageNo().'/{nb} '.$fecha,0,0,'C');
}

//Tabla coloreada
function FancyTable($header)
{
    global $nombreDia_a, $numeroDia_a, $InicioFinDia;
    //Colores, ancho de línea y fuente en negrita
    $this->SetFillColor(255,255,255);
    $this->SetTextColor(0);
    $this->SetDrawColor(0,0,0);
    $this->SetLineWidth(.3);
    $this->SetFont('','B');
    //Cabecera
    $w=array(5,13,75,5.66); //determina el ancho de las columnas
    $w1=array(5.66); //determina el ancho de las columnas
    

    // PRIMER BLOQUE DE INFORMACION #, CODIGO, EMPLEADO.
    for($i=0;$i<count($header);$i++){
        $this->Cell($w[$i],7,utf8_decode($header[$i]),1,0,'C',1);   // crea encabezado apartir del header fancy
    }
        // Coloca las lineas de los cuadros.
            $this->SetFillColor(255,255,255);
            for($j=$InicioFinDia;$j<=(count($nombreDia_a))-1;$j++){
                $this->Cell($w1[0],7,$nombreDia_a[$j],'1',0,'C',1);
            }
            // ESPACIO PARA EL TERCER BLOQUE
            $this->Cell($w[3],7,'','L',0,'C',1);
            //
            $this->SetFillColor(255);
            $header2=array('','','','Total','');
            for($j=0;$j<count($header2);$j++){
                $this->Cell($w[1],7,utf8_decode($header2[$j]),'LRT',0,'C',1);
            }

              $this->Ln();  /// salto de linea
        
            $this->Cell($w[0],7,'','LBR',0,'C',1);  // #
            $this->Cell($w[1],7,'','LBR',0,'C',1);  // codigo  
            $this->Cell($w[2],7,'','LBR',0,'C',1);  // nombre

            $this->SetFillColor(255,255,255);
            for($j=$InicioFinDia;$j<=count($nombreDia_a)-1;$j++){
                $this->Cell($w1[0],7,$numeroDia_a[$j],'1',0,'C',1);
            }
//                $this->Ln();  /// salto de linea
    // ESPACIO PARA EL TERCER BLOQUE
            $this->Cell($w[3],7,'','L',0,'C',1);
            //
            $this->SetFillColor(255);
            $header2=array('Salario','Asuetos','Extra','Extra','TOTAL');
            for($j=0;$j<count($header2);$j++){
                $this->Cell($w[1],7,utf8_decode($header2[$j]),'LRB',0,'C',1);
            }
            $this->Ln();  /// salto de linea
    //Restauración de colores y fuentes
    $this->SetFillColor(213, 213, 213);
    $this->SetTextColor(0);
    $this->SetFont('');
    //Datos
    $fill=false;
}
}
//************************************************************************************************************************
// Creando el Informe.
    $pdf=new PDF('L','mm','Letter');
    $data = array();
    #Establecemos los márgenes izquierda, arriba y derecha:
    $pdf->SetMargins(15, 15, 5);
    #Establecemos el margen inferior: 
    $pdf->SetAutoPageBreak(true,10);
//Títulos de las columnas
    $header=array('Nº','Código','Empleado');
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',12);
    $pdf->AddPage();
// Aqui mandamos texto a imprimir o al documento.
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetFont('Arial','B',13); // I : Italica; U: Normal;
    $pdf->ln();
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetFont('Arial','',9); // I : Italica; U: Normal;

    $pdf->FancyTable($header); // Solo carge el encabezado de la tabla porque medaba error el cargas los datos desde la consulta.
    $w=array(5,13,75,5.66); //determina el ancho de las columnas
    $w1=array(5.66); //determina el ancho de las columnas

    // ARMAR LA CONSULTA
   $query = "SELECT codigo, btrim(nombres || CAST(' ' AS VARCHAR) || apellidos) AS nombre_completo, pago_diario 
                FROM personal WHERE codigo_ruta = '$ruta' and codigo_estatus = '01' ORDER BY codigo";
    // EJECUTAR LA CONSULTA
    $consulta = $dblink -> query($query);
    //
    $fill=false; $i=1; $m = 0; $f = 0; $suma = 0;
        while($row = $consulta -> fetch(PDO::FETCH_BOTH))
            {
            // variable para verificar que tipo de permiso o días trabajados.
            $codigo = $row['codigo'];
            $pago_diario = $row['pago_diario'];
            //
            $pdf->Cell($w[0],6,$i,'LR',0,'C',$fill);        // núermo correlativo
            $pdf->Cell($w[1],6,utf8_decode(trim($row['codigo'])),'LR',0,'L',$fill); // codigo empleado
            $pdf->Cell($w[2],6,utf8_decode(trim($row['nombre_completo'])),'LR',0,'L',$fill); // Nombre + apellido_materno + apellido_paterno
            // Rellenar los cuadros segun el numero de dias.
                rellenar($total_dias_quincena);
            // VALIDAR EL RELLENAR $I.
                rellenar_i($i);
                // ACUMULAR EL VALOR DE $I
                $fill=!$fill;
                $i=$i+1;
            }
            // RELLENAR DATOS SI ES MENOR A 25 SEGUN $I
                rellenar_datos($i);    
    // RELLENAR LINEA DE ABAJO
        $pdf->Cell(array_sum($w),0,'','T');
// Salida del pdf.
    $modo = 'I'; // Envia al navegador (I), Descarga el archivo (D), Guardar el fichero en un local(F).
    $print_nombre = 'PLANILLA PERSONAL.pdf';
    $pdf->Output($print_nombre,$modo);
/////////////////////////////////////////////////////////////////////////////////////
    // FUNCIONES.
/////////////////////////////////////////////////////////////////////////////////////
function rellenar_i($i){
    global $pdf, $fill, $w, $header, $i, $total_dias_quincena;
    // SALTO DE PAGINA QUE DEPENDE DEL NUMERO DE LINEAS.
    if($i==25 || $i == 51 || $i == 65){
        $pdf->Cell(array_sum($w),0,'','T');
        $pdf->AddPage();
        $pdf->FancyTable($header);
    }    
}

function rellenar_datos($linea){
    global $i, $pdf, $w, $total_dias_quincena, $fill;
        // EVALUAR SI $I ES MENOR DE 25.
        if($i<=25){
            //
            $pdf->Cell($w[0],6,$i,'LR',0,'C',$fill);    // núermo correlativo
            $pdf->Cell($w[1],6,'','LR',0,'L',$fill);    // codigo empleado
            $pdf->Cell($w[2],6,'','LR',0,'L',$fill);    // Nombre + apellido_materno + apellido_paterno
            //
            rellenar($total_dias_quincena);
        }
}

function rellenar($total_dias_quincena){
    global $pdf, $fill, $w, $codigo, $fecha_periodo, $dblink, $pago_diario;
    //
    // crear las matrices para el calculo del salario
    // presentar el calculo de SALARIO + ((ASUETOS, EXTRA, BONI) = TOTAL TIEMPO EXTRA) = TOTAL.
    $salario = 0; $asuetos = 0; $extra = 0; $boni = 0; $total_tiempo_extra = 0; $total = 0; $pago_diario_hora = round($pago_diario / 8,2);
    $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
    // SEGUNDO BLOQUE DE LINEAS PARA EL NOMBRE Y NUMERO DEL DIA.
    for($j=0;$j<=$total_dias_quincena-1;$j++){
        // armanr query para buscar si existe la fecha en el perido seleccionar en la tabla personal asisitencia.
            $query_asistencia = "SELECT pa.fecha, pa.codigo_jornada, pa.codigo_tipo_licencia,
                                    cat_j.descripcion as descripcion_jornada, cat_j.horas,
                                    cat_lp.descripcion as descripcion_licencia
                            FROM personal_asistencia pa 
                            INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = pa.codigo_jornada
                            INNER JOIN catalogo_tipo_licencia_o_permiso cat_lp ON cat_lp.id_ = pa.codigo_tipo_licencia
                             WHERE pa.codigo_personal = '$codigo' and pa.fecha = '$fecha_periodo[$j]'";
            $consulta_asistencia = $dblink -> query($query_asistencia);
        // validar si existen archivos en la consulta segun la fecha.
        // Verificar si existen registros.
        if($consulta_asistencia -> rowCount() != 0){
            while($row = $consulta_asistencia -> fetch(PDO::FETCH_BOTH))
            {
            // variable para verificar que tipo de permiso o días trabajados.
                $codigo_jornada = trim($row['codigo_jornada']);
                $descripcion_jornada = trim($row['descripcion_jornada']);
                $horas_jornada = trim($row['horas']);
                $codigo_tipo_licencia = trim($row['codigo_tipo_licencia']);
                $descripcion_licencia = trim($row['descripcion_licencia']);
            // rellenar con valores según consulta.
                if($descripcion_jornada == "0H"){
                    // CUANDO ESTÁ VACÍO EL CODIGO PERTENECE A UNA LICENCIA.
                    $pdf->SetFont('Arial','B',7); // I : Italica; U: Normal;
                    //CAMBIAR EL COLOR DEL TEXTO SI ES UNA FALTA.
                        if($descripcion_licencia == 'F'){
                            $pdf->SetTextColor(199,0,57);
                        }else{
                            $pdf->SetTextColor(0);
                        }
                        
                        $pdf->Cell($w[3],6,$descripcion_licencia,'1',0,'C',$fill);
                    $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
                }else{
                    // CUANDO SEA IGUAL SOLO A  CODIGO DE LA JORNADA
                    $pdf->SetTextColor(0);
                    // CAMBIAR EL TIPO DE CARACTER SI ES NECESARIOW.
                    switch ($descripcion_jornada) {
                        case '':
                            # code...
                            break;
                        case '1T':  // Una Tanda cambiar por el .
                            $pdf->SetFont('Arial','B',16); // I : Italica; U: Normal;
                            $descripcion_jornada = ".";
                            $pdf->Cell($w[3],6,$descripcion_jornada,'1',0,'C',$fill);
                            $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
                            break;
                        default:
                            $pdf->Cell($w[3],6,$descripcion_jornada,'1',0,'C',$fill);
                            break;
                    }

                    
                    // calcular el salario
                    switch ($descripcion_jornada) {
                        case '4H':
                            // Media Tanda.
                            $salario = $salario + ($horas_jornada * $pago_diario_hora);
                            break;
                        case '1T':
                            $salario = $salario + ($horas_jornada * $pago_diario_hora);
                            break;
                        case '1.5T':
                                $salario = $salario + (8 * $pago_diario_hora);
                                $extra = $extra + (4 * $pago_diario_hora);
                                $total_tiempo_extra = $total_tiempo_extra + $extra;
                            break;
                        default:
                            # code...
                            break;
                    }
                }   // FIN DEL IF DESCRIPCION JORNADA
                
            }   // FIN DEL WHILE QUE BUSCA SI HAY REGISTRO GUARDADOS.
        }else{
            // rellenar con valores según consulta.
            $pdf->Cell($w[3],6,'','1',0,'C',$fill);
        }   // FIN DEL WHILE QUE BUSCA SI HAY REGISTRO GUARDADOS.

        //  CALCULAR EL SALARIO DE ESTE CODIGO DE EMPLEADO.
            $total_salario = $salario + $total_tiempo_extra;
    }
    // ESPACIO PARA EL TERCER 
        $pdf->SetFillColor(255,255,255);
            $pdf->Cell($w[3],6,'','L',0,'C',$fill);
        $pdf->SetFillColor(213, 213, 213);
    // TERCER BLOQUE DE LINEAS PARA.
    // presentar el calculo de SALARIO + ((ASUETOS, EXTRA, BONI) = TOTAL TIEMPO EXTRA) = TOTAL.
    for($j=0;$j<=4;$j++){
        switch ($j) {
            case '0':
                # PRESENTAR SALARIO
                $salario_pantalla = number_format($salario,2,'.',',');
                $pdf->Cell($w[1],6,'$' . $salario_pantalla,'1',0,'C',$fill);
                break;
            case '1':
                $pdf->Cell($w[1],6,'','1',0,'C',$fill);
                break;
            case '2':
                # PRESENTAR EXTRA
                $extra_pantalla = number_format($extra,2,'.',',');
                $pdf->Cell($w[1],6,'$' . $extra_pantalla,'1',0,'C',$fill);
                break;
            case '3':
                # PRESENTAR TOTAL TIEMPO EXTRA
                $total_tiempo_extra_pantalla = number_format($total_tiempo_extra,2,'.',',');
                $pdf->Cell($w[1],6,'$' . $total_tiempo_extra_pantalla,'1',0,'C',$fill);
                break;
            case '4':
                # PRESENTAR TOTAL SALARIO
                $total_salario_pantalla = number_format($total_salario,2,'.',',');
                $pdf->Cell($w[1],6,'$' . $total_salario_pantalla,'1',0,'C',$fill);
                break;
            default:
                # code...
                $pdf->Cell($w[1],6,'','1',0,'C',$fill);
                break;
        }
        
    }
    // SALTO DE LINEA Y FILL.
    $pdf->Ln();   
    // SET TAMAÑO DE LETRA
    $pdf->SetFont('Arial','',9); // I : Italica; U: Norm
    $pdf->SetTextColor(0);
}
?>