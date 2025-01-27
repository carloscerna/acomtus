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
    $DepartamentoEmpresa = $_REQUEST["DepartamentoEmpresa"];
    $DepartamentoEmpresaText = $_REQUEST["DepartamentoText"];
    if(isset($_REQUEST["persona_responsable"])){
        $reporte_persona_responsable = $_REQUEST["persona_responsable"];
    }
    $db_link = $dblink;
    $total_dias_quincena = 0;
    $reporte_trabajo = "";
    $InicioFinDia = 0;
//  imprimir datos del bachillerato.
// Establecer formato para la fecha.
// 
    date_default_timezone_set('America/El_Salvador');
    setlocale(LC_TIME,'es_SV');
//
    $meses = array("enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre");
//Salida: Viernes 24 de Febrero del 2012		
//Crear una línea. Fecha con getdate();
    $hoy = getdate();
    $NombreDia = $hoy["wday"];  // dia de la semana Nombre.
    $dia = $hoy["mday"];    // dia de la semana
    $mes = $hoy["mon"];     // mes
    $año = $fecha_ann;//$hoy["year"];    // año
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
}
    // ARMAR EL NOMBRE DLE REPORTE CON NOMBRE QUINCE DE TAL DIA A TAL DIA.
    if($quincena == "Q1"){
        $reporte_trabajo = "Reporte de trabajo correspondiente a la quincena del 1 al 15 de $NombreMes de $anyo";
        // Validar texto 
        if($DepartamentoEmpresa == '02'){
            $reporte_ruta = "NOMBRE DE MOTORISTAS ($RutaText)";
        }else{
            $reporte_ruta = "NOMBRE EMPLEADOS ($DepartamentoEmpresaText)";
        }
    }
    if($quincena == "Q2"){
        $reporte_trabajo = "Reporte de trabajo correspondiente a la quincena del 16 al $total_de_dias de $NombreMes de $anyo";
        // Validar texto 
        if($DepartamentoEmpresa == '02'){
            $reporte_ruta = "NOMBRE DE MOTORISTAS ($RutaText)";
        }else{
            $reporte_ruta = "NOMBRE EMPLEADOS ($DepartamentoEmpresaText)";
        }
    }
class PDF extends FPDF
{
//Cabecera de página
function Header()
{
    global $reporte_trabajo, $reporte_ruta, $reporte_persona_responsable;
    //Logo
    $img = $_SERVER['DOCUMENT_ROOT'].'/acomtus/img/'.$_SESSION['logo_uno'];
    $this->Image($img,5,4,24,24);
    //Arial bold 14
    $this->SetFont('Arial','B',14);
    //Título
    //$0titulo1 = utf8_decode("Educación Parvularia - Básica - Tercer Ciclo y Bachillerato.");
    $this->RotatedText(30,10,mb_convert_encoding($_SESSION['nombre_institucion'],"ISO-8859-1"),0);
    //Arial bold 13
    $this->SetFont('Arial','B',11);
    $this->RotatedText(30,17,mb_convert_encoding($reporte_trabajo,"ISO-8859-1"),0);
    $this->RotatedText(30,22,mb_convert_encoding($reporte_ruta,"ISO-8859-1"),0);
    // Persona REsponsable del Punteo.
    $this->SetFont('Arial','B',9);
    $this->RotatedText(30,32,mb_convert_encoding("Responsable del Punteo: " . $reporte_persona_responsable,"ISO-8859-1"),0);
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
    global $nombreDia_a, $numeroDia_a, $InicioFinDia, $DepartamentoEmpresa;
    //Colores, ancho de línea y fuente en negrita
    $this->SetFillColor(255,255,255);
    $this->SetTextColor(0);
    $this->SetDrawColor(0,0,0);
    $this->SetLineWidth(.3);
    $this->SetFont('','B');
    //Cabecera
    $w=array(5,13,75,6,14,7,13,7,3); //determina el ancho de las columnas
    $w1=array(5.66,10); //determina el ancho de las columnas
    

    // PRIMER BLOQUE DE INFORMACION #, CODIGO, EMPLEADO.
    for($i=0;$i<count($header);$i++){
        $this->Cell($w[$i],7,utf8_decode($header[$i]),1,0,'C',1);   // crea encabezado apartir del header fancy
    }
        // Coloca las lineas de los cuadros. los 15 d{ias de la semana}
            $this->SetFillColor(255,255,255);
            for($j=$InicioFinDia;$j<=(count($nombreDia_a))-1;$j++){
                if($DepartamentoEmpresa == '09' || $DepartamentoEmpresa  == '08'){
                    $this->Cell($w[3],7,$nombreDia_a[$j],'1',0,'C',1);
                }else{
                    $this->Cell($w[7],7,$nombreDia_a[$j],'1',0,'C',1);
                }
            }
            // ESPACIO PARA EL TERCER BLOQUE
            $this->Cell($w[8],7,'','L',0,'C',1);
            //
            $this->SetFillColor(255);
            if($DepartamentoEmpresa == '09' || $DepartamentoEmpresa  == '08'){
                $this->SetFont('Arial','',5);
                $header2=array('','','','Nocturno','Total','');
                $this->SetFont('Arial','',9);
                // recrrorer matriz
                for($j=0;$j<count($header2);$j++){
                    if($j== 3){
                        $this->Cell($w[4],7,utf8_decode($header2[$j]),'LRTB',0,'C',1);
                    }else{
                        $this->Cell($w[1],7,utf8_decode($header2[$j]),'LRT',0,'C',1);    
                    }
                }
            }else{
                $header2=array('','','','Total','');
                // recrrorer matriz
                for($j=0;$j<count($header2);$j++){
                        $this->Cell($w[1],7,utf8_decode($header2[$j]),'LRT',0,'C',1);    
                }
            }
              $this->Ln();  /// salto de linea
            $this->Cell($w[0],7,'','LBR',0,'C',1);  // #
            $this->Cell($w[1],7,'','LBR',0,'C',1);  // codigo  
            $this->Cell($w[2],7,'','LBR',0,'C',1);  // nombre
            $this->SetFillColor(255,255,255);
            for($j=$InicioFinDia;$j<=count($nombreDia_a)-1;$j++){
                if($DepartamentoEmpresa == '09' || $DepartamentoEmpresa  == '08'){
                    $this->Cell($w[3],7,$numeroDia_a[$j],'1',0,'C',1);
                }else{
                    $this->Cell($w[7],7,$numeroDia_a[$j],'1',0,'C',1);
                }
            }
    //                $this->Ln();  /// salto de linea
    // ESPACIO PARA EL TERCER BLOQUE
            $this->Cell($w[8],7,'','L',0,'C',1);
    // ULTIMO BLOQUE SALARIO ASUETOS EXTRA TOTAL EXTRA, TOTAL.
            $this->SetFillColor(255);
            if($DepartamentoEmpresa == '09' || $DepartamentoEmpresa  == '08'){
                $this->SetFont('Arial','',5);
                $header2=array('Salario','Asuetos','Extra','C','V','Extra','TOTAL');
                $this->SetFont('Arial','',9);
                    // recrrorer matriz
                    for($j=0;$j<count($header2);$j++){
                        if($j == 3 || $j == 4){
                            $this->Cell($w[5],7,utf8_decode($header2[$j]),'LRBT',0,'C',1);
                        }else{
                            $this->Cell($w[1],7,utf8_decode($header2[$j]),'LRB',0,'C',1);
                        }
                    }
            }else{
                $header2=array('Salario','Asuetos','Extra','Extra','TOTAL');
                // recrrorer matriz
                for($j=0;$j<count($header2);$j++){
                        $this->Cell($w[1],7,utf8_decode($header2[$j]),'LRB',0,'C',1);
                }
            }

            $this->Ln();  /// salto de linea
    //Restauración de colores y fuentes
    $this->SetFillColor(233, 224, 222);
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
    $pdf->SetMargins(5, 25, 5);
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
    $w=array(5,13,75,6,14,7,13,7,3); //determina el ancho de las columnas
    $w1=array(5.66); //determina el ancho de las columnas
    // ARMAR LA CONSULTA
    // DE ACUERDO AL CODIGO DEL DEPARTAMENTO EMPRESA
        if($DepartamentoEmpresa == '02'){
            $query = "SELECT codigo, btrim(nombres || CAST(' ' AS VARCHAR) || apellidos) AS nombre_completo 
            FROM personal WHERE codigo_ruta = '$ruta' and codigo_estatus = '01' ORDER BY codigo";
        }else{
            $query = "SELECT codigo, btrim(nombres || CAST(' ' AS VARCHAR) || apellidos) AS nombre_completo 
            FROM personal WHERE codigo_departamento_empresa = '$DepartamentoEmpresa' and codigo_estatus = '01' ORDER BY codigo";
        }
    // EJECUTAR LA CONSULTA
    $consulta = $dblink -> query($query);
    //
    $fill=false; $i=1; $m = 0; $f = 0; $suma = 0;
        while($row = $consulta -> fetch(PDO::FETCH_BOTH))
            {
                // variable para verificar que tipo de permiso o días trabajados.
                $codigo = trim($row['codigo']);
                $nombre_completo = mb_convert_encoding(trim($row['nombre_completo']),"ISO-8859-1");
                $pdf->Cell($w[0],6,$i,'LR',0,'C',$fill);        // núermo correlativo
                $pdf->Cell($w[1],6,$codigo,'LR',0,'L',$fill); // codigo empleado
                $pdf->Cell($w[2],6,$nombre_completo,'LR',0,'L',$fill); // Nombre + apellido_materno + apellido_paterno
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
    $modo = "I"; // Envia al navegador (I), Descarga el archivo (D), Guardar el fichero en un local(F).
    $print_nombre = mb_convert_encoding("Planilla: $DepartamentoEmpresaText - $quincena - $mes.pdf","ISO-8859-1");
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
    global $pdf, $fill, $w, $DepartamentoEmpresa;

    // SEGUNDO BLOQUE DE LINEAS PARA EL NOMBRE Y NUMERO DEL DIA.
    for($j=0;$j<=$total_dias_quincena-1;$j++){
        if($DepartamentoEmpresa == '09' || $DepartamentoEmpresa  == '08'){
            $pdf->Cell($w[3],6,'','1',0,'C',$fill);
        }else{
            $pdf->Cell($w[7],6,'','1',0,'C',$fill);
        }
    }
    // ESPACIO PARA EL TERCER 
        $pdf->SetFillColor(255,255,255);
            $pdf->Cell($w[8],6,'','L',0,'C',$fill);
        $pdf->SetFillColor(233, 224, 222);
    // TERCER BLOQUE DE LINEAS PARA. SALARIO, EXTRA TOTAL EXTRA TOTAL NOCTGURNIDAD
    if($DepartamentoEmpresa == '09' || $DepartamentoEmpresa  == '08'){
        for($j=0;$j<=6;$j++){
            if($j == 3 || $j == 4){
                $pdf->Cell($w[5],6,'','1',0,'C',$fill);
            }else{
                $pdf->Cell($w[1],6,'','1',0,'C',$fill);
            }
        }
    }else{
        for($j=0;$j<=4;$j++){
                $pdf->Cell($w[6],6,'','1',0,'C',$fill);
        }
    }
    // SALTO DE LINEA Y FILL.
    $pdf->Ln();   
}
?>