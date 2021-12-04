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
     $Calcular = $_REQUEST["chkCalcular"];
     $db_link = $dblink;
     $total_dias_quincena = 0;
     $reporte_trabajo = "";
     $InicioFinDia = 0;
     $pago_diario = 0;
     $horas_jornada = 0;
     $total_lineas = 1;
     $contar_4H = 1;
     $fecha_inicio_adb = array();
     // Nocturnidad.
     $NocturnaValorUnitario = 0.57;
     $NocturnaCantidad = 0;
     $NocturnaValor = 0;
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
    global $nombreDia_a, $numeroDia_a, $InicioFinDia, $DepartamentoEmpresa;
    //Colores, ancho de línea y fuente en negrita
    $this->SetFillColor(255,255,255);
    $this->SetTextColor(0);
    $this->SetDrawColor(0,0,0);
    $this->SetLineWidth(.3);
    $this->SetFont('','B');
    //Cabecera
    $w=array(5,13,75,6,14,7,13,7,3); //determina el ancho de las columnas
    $w1=array(5.66); //determina el ancho de las columnas
    

    // PRIMER BLOQUE DE INFORMACION #, CODIGO, EMPLEADO.
    for($i=0;$i<count($header);$i++){
        $this->Cell($w[$i],7,utf8_decode($header[$i]),1,0,'C',1);   // crea encabezado apartir del header fancy
    }
        // Coloca las lineas de los cuadros. los 15 d{ias de la semana}
        $this->SetFillColor(255,255,255);
        for($j=$InicioFinDia;$j<=(count($nombreDia_a))-1;$j++){
            if($DepartamentoEmpresa == '09' || $DepartamentoEmpresa  == '08' || $DepartamentoEmpresa  == '05'){
                $this->Cell($w[3],7,$nombreDia_a[$j],'1',0,'C',1);
            }else{
                $this->Cell($w[7],7,$nombreDia_a[$j],'1',0,'C',1);
            }
        }
            // ESPACIO PARA EL TERCER BLOQUE
            $this->Cell($w[8],7,'','L',0,'C',1);
            //
            $this->SetFillColor(255);
            if($DepartamentoEmpresa == '09' || $DepartamentoEmpresa  == '08' || $DepartamentoEmpresa  == '05'){                $this->SetFont('Arial','',5);
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
                if($DepartamentoEmpresa == '09' || $DepartamentoEmpresa  == '08' || $DepartamentoEmpresa  == '05'){ 
                                       $this->Cell($w[3],7,$numeroDia_a[$j],'1',0,'C',1);
                }else{
                    $this->Cell($w[7],7,$numeroDia_a[$j],'1',0,'C',1);
                }
            }
    //                $this->Ln();  /// salto de linea
    // ESPACIO PARA EL TERCER BLOQUE
            $this->Cell($w[8],7,'','L',0,'C',1);
            //
            $this->SetFillColor(255);
            if($DepartamentoEmpresa == '09' || $DepartamentoEmpresa  == '08' || $DepartamentoEmpresa  == '05'){
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
    $pdf->SetMargins(5, 15, 5);
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
    if($DepartamentoEmpresa == '09' || $DepartamentoEmpresa  == '08' || $DepartamentoEmpresa  == '05'){
        $w=array(5,13,75,6,14,7,13,7,3); //determina el ancho de las columnas
    }else{
        $w=array(5,13,75,7,14,7,13,7,3); //determina el ancho de las columnas
    }
    $w1=array(5.66); //determina el ancho de las columnas

    // ARMAR LA CONSULTA
    // DE ACUERDO AL CODIGO DEL DEPARTAMENTO EMPRESA
    if($DepartamentoEmpresa == '02'){
       $query = "SELECT codigo, btrim(nombres || CAST(' ' AS VARCHAR) || apellidos) AS nombre_completo, pago_diario 
        FROM personal WHERE codigo_ruta = '$ruta' and codigo_estatus = '01' ORDER BY codigo";
    }else{
       $query = "SELECT codigo, btrim(nombres || CAST(' ' AS VARCHAR) || apellidos) AS nombre_completo, pago_diario 
        FROM personal WHERE codigo_departamento_empresa = '$DepartamentoEmpresa' and codigo_estatus = '01' ORDER BY codigo";
    }
    // EJECUTAR LA CONSULTA
    $consulta = $dblink -> query($query);
    // OBTENER EL TOTAL DE LINEAS
        $total_lineas = $consulta -> rowCount();
    //
    $fill=false; $i=1; $m = 0; $f = 0; $suma = 0;
        while($row = $consulta -> fetch(PDO::FETCH_BOTH))
            {
                // Variables 
                $NocturnaCantidad = 0;
            // variable para verificar que tipo de permiso o días trabajados.
            $codigo = $row['codigo'];
            $pago_diario = round($row['pago_diario'],2);
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
    global $pdf, $fill, $w, $header, $i, $total_dias_quincena, $total_lineas;
    // SALTO DE PAGINA QUE DEPENDE DEL NUMERO DE LINEAS.
    if($i==25 || $i == 51 || $i == 65){
        $pdf->Cell(array_sum($w),0,'','T');
        if($total_lineas > 25){
            $pdf->AddPage();
            $pdf->FancyTable($header);
        }
    }    
}

function rellenar_datos($linea){
    global $i, $pdf, $w, $total_dias_quincena, $fill;
        // EVALUAR SI $I ES MENOR DE 25.
        if($i<=25){
            //
           // $pdf->Cell($w[0],6,$i,'LR',0,'C',$fill);    // núermo correlativo
           // $pdf->Cell($w[1],6,'','LR',0,'L',$fill);    // codigo empleado
           // $pdf->Cell($w[2],6,'','LR',0,'L',$fill);    // Nombre + apellido_materno + apellido_paterno
            //
          //  rellenar($total_dias_quincena);
        }
}

function rellenar($total_dias_quincena){
    global $pdf, $fill, $w, $codigo, $fecha_periodo, $dblink, $pago_diario, $Calcular, $nombresDias, $DepartamentoEmpresa, $NocturnaCantidad, $NocturnaValor, $NocturnaValorUnitario, $contar_4H;
    //
    // crear las matrices para el calculo del salario
    // presentar el calculo de SALARIO + ((ASUETOS, EXTRA, BONI) = TOTAL TIEMPO EXTRA) = TOTAL.
    $salario = 0; $asuetos = 0; $extra = 0; $boni = 0; $total_tiempo_extra = 0; $total = 0; $pago_diario_hora = round($pago_diario / 8,5); $asueto = 0; $horas_jornadas = 0;
    $total_horas_jornada = 0;
     // DECLARACI{ON DE AMTRICES}
        $fecha_descanso = array(); $descripcion_jornada_a_P2 = array(); $fecha_inicio_adb = array();
    $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
    // SEGUNDO BLOQUE DE LINEAS PARA EL NOMBRE Y NUMERO DEL DIA.
    for($j=0;$j<=$total_dias_quincena-1;$j++){

        // armanr query para buscar si existe la fecha en el perido seleccionar en la tabla personal asisitencia.
          $query_asistencia = "SELECT pa.fecha, pa.codigo_jornada, pa.codigo_tipo_licencia, pa.codigo_jornada_asueto, pa.codigo_jornada_vacaciones,
                                    pa.codigo_jornada_descanso, pa.codigo_jornada_e_4h, pa.codigo_jornada_nocturna,
                                    cat_j.descripcion as descripcion_jornada, cat_j.horas,
                                    cat_jn.descripcion as descripcion_jornada_nocturna,
                                    cat_lp.descripcion as descripcion_licencia, cat_lp.horas as horas_licencia
                            FROM personal_asistencia pa 
                            INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = pa.codigo_jornada
                            INNER JOIN catalogo_jornada cat_jn ON cat_jn.id_ = pa.codigo_jornada_nocturna 
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
                $codigo_jornada_asueto = trim($row['codigo_jornada_asueto']);
                $codigo_jornada_vacaciones = trim($row['codigo_jornada_vacaciones']);
                $codigo_jornada_descanso = trim($row['codigo_jornada_descanso']);
                $codigo_jornada_nocturna = trim($row['codigo_jornada_nocturna']);
                $codigo_jornada_extra_4H = trim($row['codigo_jornada_e_4h']);
                $descripcion_jornada = trim($row['descripcion_jornada']);
                $descripcion_jornada_nocturna = trim($row['descripcion_jornada_nocturna']);
                $fecha_db = trim($row['fecha']);
                $fechats = strtotime($fecha_db); //fecha en yyyy-mm-dd_
                //$NombreDiasArray[] = $nombresDias[date('w', $fechats)];
                $horas_jornada = trim($row['horas']);
                $horas_licencia = trim($row['horas_licencia']);
                $codigo_tipo_licencia = trim($row['codigo_tipo_licencia']);
                $descripcion_licencia = trim($row['descripcion_licencia']);
                $fecha_asistencia = trim($row['fecha']);
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
                    //
                        $pdf->Cell($w[3],6,$descripcion_licencia,'1',0,'C',$fill);
                        $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
                    // matriz seleccionar cual semana llenar. FECHA DE INICIO Y FINAL DE SEMANA
                            $fecha_inicio_adb[] = $fecha_db;
                    //    }
                        // CALCULO DEL SALARIO CUANDO HAY PERMISOS
                        switch ($descripcion_licencia) {
                            case 'ISSS':
                                // CUANDO PIDE PERMISO POR ENFERMEDAD. Una tanda 8 horas
                                //$salario = $salario + ($horas_licencia * $pago_diario_hora);
                                break;
                            case 'PP':
                                // PERMISO PERSONAL Una tanda 8 horas
                                //$salario = $salario + ($horas_licencia * $pago_diario_hora);
                                break;
                            case 'F':
                                // CUANDO SEA FALTA QUE ACCIÓN REALIZAR
                                break;
                            case 'C':
                                // CUANDO SEA CASTIGO QUE ACCIÓN REALIZAR
                                break;
                            case 'V':
                                // CUANDO SEA VACACION QUE ACCIÓN REALIZAR
                                //s$salario = $salario + ($horas_licencia * $pago_diario_hora);
                                break;
                            case 'TV':
                                // CUANDO SEA TRABAJO VACACION QUE ACCIÓN REALIZAR
                                //$salario = $salario + ($horas_licencia * $pago_diario_hora);
                                // ARMAR LA CONSULTA PARA REVISAR SI TRABAJÓ EN VACACIÓN
                                $query_jv = "SELECT * FROM catalogo_jornada WHERE id_ = '$codigo_jornada_vacaciones'";
                                $consulta_jv = $dblink -> query($query_jv);
                                // validar si existen archivos en la consulta segun la fecha.
                                // Verificar si existen registros.
                                if($consulta_jv -> rowCount() != 0){
                                    while($row = $consulta_jv -> fetch(PDO::FETCH_BOTH))
                                    {
                                        $horas_jornadas = trim($row['horas']);
                                        $descripcion_vacacion = trim($row['descripcion']);
                                    }
                                                //  impimir DESCRIPCION DEL DESCANSO
                                                $x = $pdf->GetX() -5 ; $y = $pdf->GetY() + 5.5;
                                                $pdf->SetFont('Arial','',5); // I : Italica; U: Normal;
                                                    $pdf->RotatedText($x,$y,$descripcion_vacacion,0);
                                                $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;                                    
                                        // CALCULO DEL SALARIO CUANDO HAY PERMISOS
                                        switch ($codigo_jornada_vacaciones) {
                                            case '1':   // 4 horas
                                                // CUANDO PIDE PERMISO POR ENFERMEDAD. Una tanda 4 horas
                                                $extra = $extra + ($horas_jornadas * $pago_diario_hora);
                                                $total_tiempo_extra =  $extra;
                                                break;
                                            case '2':   // 1 tanda
                                                // PERMISO PERSONAL Una tanda 8 horas
                                                $extra = $extra + ($horas_jornadas * $pago_diario_hora);
                                                $total_tiempo_extra =  $extra;
                                                break;
                                            case '3':   //1 tanda y media
                                                // PERMISO PERSONAL Una tanda 12 horas
                                                $extra = $extra + ($horas_jornadas * $pago_diario_hora);
                                                $total_tiempo_extra =  $extra;
                                                break;
                                            default:
                                                # code...
                                                break;
                                            }
                                }
                                break;
                            case 'D':
                                // CUANDO SEA DESCANSO QUE ACCIÓN REALIZAR
                                $salario = $salario + ($horas_licencia * $pago_diario_hora);
                                $fecha_descanso[] = $fecha_db;
                                break;
                            case 'TD':
                                // CUANDO SEA TRABAJO DESCANSO QUE ACCIÓN REALIZAR
                                $salario = $salario + ($horas_licencia * $pago_diario_hora);
                                // ARMAR LA CONSULTA PARA REVISAR SI TRABAJÓ EN VACACIÓN
                                $query_jv = "SELECT * FROM catalogo_jornada WHERE id_ = '$codigo_jornada_descanso'";
                                $consulta_jv = $dblink -> query($query_jv);
                                // validar si existen archivos en la consulta segun la fecha.
                                // Verificar si existen registros.
                                if($consulta_jv -> rowCount() != 0){
                                    while($row = $consulta_jv -> fetch(PDO::FETCH_BOTH))
                                    {
                                        $horas_jornadas = trim($row['horas']);
                                        $descripcion_descanso = trim($row['descripcion']);
                                    }        
                                        // CALCULO DEL SALARIO CUANDO HAY PERMISOS
                                        switch ($codigo_jornada_descanso) {
                                            case '1':   // 4 horas
                                                //  impimir DESCRIPCION DEL DESCANSO
                                                $x = $pdf->GetX() -5 ; $y = $pdf->GetY() + 5.5;
                                                $pdf->SetFont('Arial','',5); // I : Italica; U: Normal;
                                                    $pdf->RotatedText($x,$y,$descripcion_descanso,0);
                                                $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
                                                // CUANDO PIDE PERMISO POR ENFERMEDAD. Una tanda 4 horas
                                                $extra = $extra + ($horas_jornadas * $pago_diario_hora);
                                                $total_tiempo_extra =  $extra;
                                                break;
                                            case '2':   // 1 tanda
                                                //  impimir DESCRIPCION DEL DESCANSO
                                                $x = $pdf->GetX() -5 ; $y = $pdf->GetY() + 5.5;
                                                $pdf->SetFont('Arial','',5); // I : Italica; U: Normal;
                                                    $pdf->RotatedText($x,$y,$descripcion_descanso,0);
                                                $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
                                                // PERMISO PERSONAL Una tanda 8 horas
                                                $extra = $extra + ($horas_jornadas * $pago_diario_hora);
                                                $total_tiempo_extra =  $extra;
                                                break;
                                            case '3':   //1 tanda y media
                                                //  impimir DESCRIPCION DEL DESCANSO
                                                $x = $pdf->GetX() -5 ; $y = $pdf->GetY() + 5.5;
                                                $pdf->SetFont('Arial','',5); // I : Italica; U: Normal;
                                                $pdf->RotatedText($x,$y,$descripcion_descanso,0);
                                                $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
                                                // PERMISO PERSONAL Una tanda 12 horas
                                                $extra = $extra + ($horas_jornadas * $pago_diario_hora);
                                                $total_tiempo_extra =  $extra;
                                                break;
                                            default:
                                                # code...
                                                break;
                                            }
                                }
                                break;
                            default:
                                # code...
                                break;
                            }                        
                }else{
                    // CUANDO SEA IGUAL SOLO A  CODIGO DE LA JORNADA
                    $pdf->SetTextColor(0);
                    //
                    // REVISAR Y CALCULAR SI LA FECHA PERTENECIA A UN DÍA DE ASUETO
                    $fecha_partial = explode("-",$fecha_asistencia);
                    // matriz seleccionar cual semana llenar.
                            $fecha_inicio_adb[] = $fecha_db;
                    //
                        $asueto = false;
                        //print_r($fecha_partial);
                        $asueto_mes = (int)$fecha_partial[1];    // mes 
                        $asueto_dia = (int)$fecha_partial[2];    // dia
                         // ARMAR LA CONSULTA
                           $query_asueto = "SELECT * FROM catalogo_asuetos WHERE mes = '$asueto_mes' and dia = '$asueto_dia'";
                            // EJECUTAR LA CONSULTA
                            $consulta_asueto = $dblink -> query($query_asueto);
                            if($consulta_asueto -> rowCount() != 0){
                                $pdf->Cell($w[3],6,'A','1',0,'C',$fill);   
                             }else{ 
                             // VALIDAR EL FORMATO O PRESENTACI{ON DE LA DESCRIPCION LICENCIA}
                                if($descripcion_jornada == '1T'){
                                    $pdf->SetFont('Arial','B',20); // I : Italica; U: Normal;
                                        $pdf->Cell($w[3],6,'','LTR',0,'C',$fill);
                                        $x = $pdf->GetX() -4 ; $y = $pdf->GetY() + 3.5;
                                        $pdf->RotatedText($x,$y,'.',0);
                                    $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
                                }else{
                                    $pdf->Cell($w[3],6,$descripcion_jornada,'1',0,'C',$fill);    
                                }
                            }   // find ela primera consulta sobre el asueto
                            // segunda condición del asueto para realizar calculos.
                            if($consulta_asueto -> rowCount() != 0){
                                // Es asueto
                                $asueto = true;
                                // CALCULO DEL SALARIO CUANDO HAY PERMISOS
                                switch ($codigo_jornada_asueto) {
                                    case '1':   // 4 horas
                                        //  impimir DESCRIPCION DEL DESCANSO
                                        $x = $pdf->GetX() -5 ; $y = $pdf->GetY() + 5.5;
                                        $pdf->SetFont('Arial','',5); // I : Italica; U: Normal;
                                            $pdf->RotatedText($x,$y,'4h',0);
                                        $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
                                        // CUANDO PIDE PERMISO POR ENFERMEDAD. Una tanda 4 horas
                                       //$salario = $salario + ($horas_jornada * $pago_diario_hora);
                                        $asuetos = $asuetos + ($horas_jornada * $pago_diario_hora);
                                        $extra = $extra + ($horas_jornada * $pago_diario_hora);
                                        $total_tiempo_extra =  $extra;
                                        break;
                                    case '2':   // 1 tanda
                                        // PERMISO PERSONAL Una tanda 8 horas
                                         //  impimir DESCRIPCION DEL DESCANSO
                                         $x = $pdf->GetX() -5 ; $y = $pdf->GetY() + 5.5;
                                         $pdf->SetFont('Arial','',5); // I : Italica; U: Normal;
                                             $pdf->RotatedText($x,$y,'1T',0);
                                         $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
                                       // $salario = $salario + ($horas_jornada * $pago_diario_hora);
                                        $asuetos = $asuetos + ($horas_jornada * $pago_diario_hora);
                                        $extra = $extra + ($horas_jornada * $pago_diario_hora);                                        
                                        $total_tiempo_extra =  $extra;
                                        break;
                                    case '3':   //1 tanda y media
                                         //  impimir DESCRIPCION DEL DESCANSO
                                         $x = $pdf->GetX() -5 ; $y = $pdf->GetY() + 5.5;
                                         $pdf->SetFont('Arial','',5); // I : Italica; U: Normal;
                                             $pdf->RotatedText($x,$y,'1.5T',0);
                                         $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
                                        // PERMISO PERSONAL Una tanda 12 horas
                                        //$salario = $salario + (8 * $pago_diario_hora);
                                        $extra = $extra + (4 * $pago_diario_hora);
                                        $total_tiempo_extra =  $extra;
                                       // $salario = $salario + ($horas_jornada * $pago_diario_hora);
                                        $asuetos = $asuetos + ($horas_jornada * $pago_diario_hora);
                                        break;
                                    case '4':
                                       $salario = $salario + ($horas_jornada * $pago_diario_hora);
                                        //$asuetos = $asuetos + ($horas_jornada * $pago_diario_hora);
                                        break;
                                    default:
                                        # code...
                                        $asuetos = 0;
                                        break;
                                    }
                            }else{
                                // CUANDO EL DIA NO ES ASUETO.
                                $asueto = false;
                                // calcular el salario CON DESCRIPCION JORNADA
                                switch ($descripcion_jornada) {
                                    case '4H':
                                     //   if($contar_4H == 0){
                                            // Media Tanda.
                                            $salario = $salario + ($horas_jornada * $pago_diario_hora);
                                            $salario = $salario + ($horas_jornada * $pago_diario_hora);
                                            // contador de cuantas veces 4h en una semana.
                                         /*          $contar_4H++;
                                        }else{
                                            $salario = $salario + ($horas_jornada * $pago_diario_hora);
                                            // contador de cuantas veces 4h en una semana.
                                            $contar_4H++;
                                        }*/
                                            // CUANDO SEA TRABAJO DESCANSO QUE ACCIÓN REALIZAR
                                            //$salario = $salario + ($horas_licencia * $pago_diario_hora);
                                            // ARMAR LA CONSULTA PARA REVISAR SI TRABAJÓ EN VACACIÓN
                                            $query_jv = "SELECT * FROM catalogo_jornada WHERE id_ = '$codigo_jornada_extra_4H'";
                                            $consulta_jv = $dblink -> query($query_jv);
                                            // validar si existen archivos en la consulta segun la fecha.
                                            // Verificar si existen registros.
                                            if($consulta_jv -> rowCount() != 0){
                                                while($row = $consulta_jv -> fetch(PDO::FETCH_BOTH))
                                                {
                                                    $horas_jornadas = trim($row['horas']);
                                                    $descripcion_e_4h = trim($row['descripcion']);
                                                }        
                                                    // CALCULO DEL SALARIO CUANDO HAY PERMISOS
                                                    switch ($codigo_jornada_extra_4H) {
                                                        case '1':   // 4 horas
                                                            //  impimir DESCRIPCION DEL DESCANSO
                                                            $x = $pdf->GetX() -2 ; $y = $pdf->GetY() + 5.5;
                                                            $pdf->SetFont('Arial','',5); // I : Italica; U: Normal;
                                                                $pdf->RotatedText($x,$y,$descripcion_e_4h,0);
                                                            $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
                                                            // CUANDO PIDE PERMISO POR ENFERMEDAD. Una tanda 4 horas
                                                            $extra = $extra + ($horas_jornadas * $pago_diario_hora);
                                                            $total_tiempo_extra =  $extra;
                                                            break;
                                                        case '2':   // 1 tanda
                                                            //  impimir DESCRIPCION DEL DESCANSO
                                                            $x = $pdf->GetX() -2 ; $y = $pdf->GetY() + 5.5;
                                                            $pdf->SetFont('Arial','',5); // I : Italica; U: Normal;
                                                                $pdf->RotatedText($x,$y,$descripcion_e_4h,0);
                                                            $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
                                                            // PERMISO PERSONAL Una tanda 8 horas
                                                            $extra = $extra + ($horas_jornadas * $pago_diario_hora);
                                                            $total_tiempo_extra =  $extra;
                                                            break;
                                                        case '3':   //1 tanda y media
                                                            //  impimir DESCRIPCION DEL DESCANSO
                                                            $x = $pdf->GetX() -2 ; $y = $pdf->GetY() + 5.5;
                                                            $pdf->SetFont('Arial','',5); // I : Italica; U: Normal;
                                                            $pdf->RotatedText($x,$y,$descripcion_e_4h,0);
                                                            $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
                                                            // PERMISO PERSONAL Una tanda 12 horas
                                                            $extra = $extra + ($horas_jornadas * $pago_diario_hora);
                                                            $total_tiempo_extra =  $extra;
                                                            break;
                                                        default:
                                                            # code...
                                                            break;
                                                        }
                                            }
                                        break;
                                    case '1T':
                                        // Una tanda 8 horas
                                        $salario = $salario + ($horas_jornada * $pago_diario_hora);
                                        break;
                                    case '1.5T':
                                        /// una tanda 8 horas m{as 4 horas extras}
                                            $salario = $salario + (8 * $pago_diario_hora);
                                            $extra = $extra + (4 * $pago_diario_hora);
                                            $total_tiempo_extra =  $extra;
                                        break;
                                    default:
                                        # code...
                                        break;
                                }
                                    // CALCULO DEL SALARIO CUANDO HAY PERMISOS
                                    switch ($codigo_jornada_nocturna) {
                                        case '5':
                                            //  impimir DESCRIPCION DEL DESCANSO
                                            $x = $pdf->GetX() -5 ; $y = $pdf->GetY() + 5.5;
                                            $pdf->SetFont('Arial','',5); // I : Italica; U: Normal;
                                                $pdf->RotatedText($x,$y,$descripcion_jornada_nocturna,0);
                                            $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
                                                $NocturnaCantidad++;
                                        break;
                                        }
                            }   // CONDICIÓN DEL DIA DE ASUETO.
                }   // FIN DEL IF DESCRIPCION JORNADA
            }   // FIN DEL WHILE QUE BUSCA SI HAY REGISTRO GUARDADOS DE CADA EMPLEADO.
        }else{
            // rellenar con valores según consulta.
            if($DepartamentoEmpresa == '09' || $DepartamentoEmpresa  == '08' || $DepartamentoEmpresa  == '05'){
                $pdf->Cell($w[3],6,'','1',0,'C',$fill);
            }else{
                $pdf->Cell($w[7],6,'','1',0,'C',$fill);
            }
        }   // FIN DEL WHILE QUE BUSCA SI HAY REGISTRO GUARDADOS.
     
        //  CALCULAR EL SALARIO DE ESTE CODIGO DE EMPLEADO.
            $total_salario = $salario + $total_tiempo_extra + $asuetos;
        //  NOCTURNIDAD
           // $NocturnaValor = $NocturnaValorUnitario * $NocturnaCantidad;
    }

    // ESPACIO PARA EL TERCER, se asigna una separación para las columnas.
        $pdf->SetFillColor(255,255,255);
        $pdf->Cell($w[8],6,'','L',0,'C',$fill);
        $pdf->SetFillColor(233, 224, 222);
/////////////////////////////////////////////////////////////////////////////////////////////////////////
//  proceso para el descuento.
/////////////////////////////////////////////////////////////////////////////////////////////////////////
// si es motorista catalogo_departamento-empresa 02
if($DepartamentoEmpresa == '02' || $DepartamentoEmpresa == '04')
{
    $primerDias = array(); $ultimoDias = array(); $BuscarFechaInicio = array(); $BuscarFechaFin = array(); $ll = 0;
    foreach ($fecha_descanso as $fecha_dd) {
        $fecha_actual =$fecha_dd;
        if($ll == 0){
            //sumo 7 día
            $primerDia = date("d-m-Y",strtotime($fecha_actual."- 6 days")); 
            $primerDias[] = $primerDia;
            $ultimoDias[] = $fecha_dd;
            //resto 1 día
            $ultimoDia = date("d-m-Y",strtotime($fecha_actual."+ 7 days")); 
                $primerDias[] = date("d-m-Y",strtotime($fecha_actual."+ 1 days")); ;
                $ultimoDias[] = $ultimoDia;
        }else{
            $primerDias[] = date("d-m-Y",strtotime($fecha_actual."+ 1 days")); ;
            $ultimoDia = date("d-m-Y",strtotime($fecha_actual."+ 7 days")); 
            $ultimoDias[] = $ultimoDia;
        }
        
        $ll++;
    }
    // pasar valores unico a nuevas matrices para posterioremente buscar en dbf.
   $BuscarFechaInicio = array_merge(array_unique($primerDias));
   $BuscarFechaFin = array_merge(array_unique($ultimoDias));
 // recorrer la matriz con la fecha inicio y fin
     for ($bb=0; $bb < count($BuscarFechaInicio) ; $bb++) { 
         //print "Fecha Inicio: ". $BuscarFechaInicio[$bb] . " Fecha fin: " . $BuscarFechaFin[$bb];
         // armanr query para buscar si existe la fecha en el perido seleccionar en la tabla personal asisitencia.
          $query_asistencia_buscar_db = "SELECT pa.fecha, pa.codigo_jornada, pa.codigo_tipo_licencia, pa.codigo_jornada_asueto, pa.codigo_jornada_vacaciones,
                 pa.codigo_jornada_descanso,
                 cat_j.descripcion as descripcion_jornada, cat_j.horas,
                 cat_lp.descripcion as descripcion_licencia, cat_lp.horas as horas_licencia
                     FROM personal_asistencia pa 
                     INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = pa.codigo_jornada
                     INNER JOIN catalogo_tipo_licencia_o_permiso cat_lp ON cat_lp.id_ = pa.codigo_tipo_licencia
                     WHERE pa.codigo_personal = '$codigo' and pa.fecha >= '$BuscarFechaInicio[$bb]' and pa.fecha <= '$BuscarFechaFin[$bb]'";
             $consulta_asistencia_buscar_db = $dblink -> query($query_asistencia_buscar_db);
             // validar si existen archivos en la consulta segun la fecha.
             $cantidad_registros = $consulta_asistencia_buscar_db -> rowCount();
             // Verificar si existen registros.
                 if($consulta_asistencia_buscar_db -> rowCount() != 0 and $cantidad_registros == 7){
                     while($rows = $consulta_asistencia_buscar_db -> fetch(PDO::FETCH_BOTH))
                     {
                         // variable para verificar que tipo de permiso o días trabajados.
                         $codigo_jornada = trim($rows['codigo_jornada']);
                         $codigo_jornada_asueto = trim($rows['codigo_jornada_asueto']);
                         $codigo_jornada_vacaciones = trim($rows['codigo_jornada_vacaciones']);
                         $codigo_jornada_descanso = trim($rows['codigo_jornada_descanso']);
                         $descripcion_jornada = trim($rows['descripcion_jornada']);
                         $horas_jornada = trim($rows['horas']);
                        // hOras
                        $total_horas_jornada = $total_horas_jornada + $horas_jornada;
                         $horas_licencia = trim($rows['horas_licencia']);
                         $codigo_tipo_licencia = trim($rows['codigo_tipo_licencia']);
                         $descripcion_licencia = trim($rows['descripcion_licencia']);
                         $fecha_asistencia = trim($rows['fecha']);
                         // 
                         if($descripcion_jornada == "0H"){
                              // CALCULO DEL SALARIO CUANDO HAY PERMISOS
                             switch ($descripcion_licencia) {
                                 case 'F':
                                     // CUANDO SEA FALTA QUE ACCIÓN REALIZAR
                                    $salario = $salario - (8 * $pago_diario_hora);
                                    //$total_salario =  $salario + $extra;
                                     break;
                                 case 'C':
                                     // CUANDO SEA CASTIGO QUE ACCIÓN REALIZAR
                                     $salario = $salario - (8 * $pago_diario_hora);
                                 }   // LAZO SWICTH...
                         } // LAZO IF...;
                     }   // LAZO WHILE
                     //  CALCULAR EL SALARIO DE ESTE CODIGO DE EMPLEADO.
                     $total_salario = $salario + $total_tiempo_extra + $asuetos;
                 }   // LAZO IF....
     } // LAZO FOR.
}else{
 // Array que contiene el nombre del d{ia. apartir de la fecha}
 $primerDias = array(); $ultimoDias = array(); $BuscarFechaInicio = array(); $BuscarFechaFin = array();
 foreach ($fecha_inicio_adb as $fecha_dd) {
     //echo "<br>". $fecha_dd  . "<br>";
     $fecha = $fecha_dd;
     $fecha_partial = explode("-",$fecha);
     $year=$fecha_partial[0];
     $month=$fecha_partial[1];
     $day=(int)$fecha_partial[2];
     //print "Dia: $day Mes: $month Año: $year";
         # Obtenemos el numero de la semana
         $semana=date("W",mktime(0,0,0,$month,$day,$year));
         # Obtenemos el día de la semana de la fecha dada
         $diaSemana=date("w",mktime(0,0,0,$month,$day,$year));
         # el 0 equivale al domingo...
         if($diaSemana==0)
             $diaSemana=7;
         # A la fecha recibida, le restamos el dia de la semana y obtendremos el lunes
         $primerDia = date("d-m-Y",mktime(0,0,0,$month,$day-$diaSemana+1,$year));
         # A la fecha recibida, le sumamos el dia de la semana menos siete y obtendremos el domingo
         $ultimoDia = date("d-m-Y",mktime(0,0,0,$month,$day+(7-$diaSemana),$year));
         $primerDias[] = $primerDia;
         $ultimoDias[] = $ultimoDia;
     }
 // pasar valores unico a nuevas matrices para posterioremente buscar en dbf.
   $BuscarFechaInicio = array_merge(array_unique($primerDias));
   $BuscarFechaFin = array_merge(array_unique($ultimoDias));
 // recorrer la matriz con la fecha inicio y fin
     for ($bb=0; $bb < count($BuscarFechaInicio) ; $bb++) { 
         //print "Fecha Inicio: ". $BuscarFechaInicio[$bb] . " Fecha fin: " . $BuscarFechaFin[$bb];
         // armanr query para buscar si existe la fecha en el perido seleccionar en la tabla personal asisitencia.
          $query_asistencia_buscar_db = "SELECT pa.fecha, pa.codigo_jornada, pa.codigo_tipo_licencia, pa.codigo_jornada_asueto, pa.codigo_jornada_vacaciones,
                 pa.codigo_jornada_descanso,
                 cat_j.descripcion as descripcion_jornada, cat_j.horas,
                 cat_lp.descripcion as descripcion_licencia, cat_lp.horas as horas_licencia
                     FROM personal_asistencia pa 
                     INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = pa.codigo_jornada
                     INNER JOIN catalogo_tipo_licencia_o_permiso cat_lp ON cat_lp.id_ = pa.codigo_tipo_licencia
                     WHERE pa.codigo_personal = '$codigo' and pa.fecha >= '$BuscarFechaInicio[$bb]' and pa.fecha <= '$BuscarFechaFin[$bb]'
                     ORDER BY pa.fecha";
             $consulta_asistencia_buscar_db = $dblink -> query($query_asistencia_buscar_db);
             // validar si existen archivos en la consulta segun la fecha.
             $cantidad_registros = $consulta_asistencia_buscar_db -> rowCount();
             // Verificar si existen registros.
                 if($consulta_asistencia_buscar_db -> rowCount() != 0 and $cantidad_registros == 7){
                     while($rows = $consulta_asistencia_buscar_db -> fetch(PDO::FETCH_BOTH))
                     {
                         // variable para verificar que tipo de permiso o días trabajados.
                         $codigo_jornada = trim($rows['codigo_jornada']);
                         $codigo_jornada_asueto = trim($rows['codigo_jornada_asueto']);
                         $codigo_jornada_vacaciones = trim($rows['codigo_jornada_vacaciones']);
                         $codigo_jornada_descanso = trim($rows['codigo_jornada_descanso']);
                         $descripcion_jornada = trim($rows['descripcion_jornada']);
                         $horas_jornada = trim($rows['horas']);
                         // hOras
                         //$total_horas_jornada = $total_horas_jornada + $horas_jornada;
                         $horas_licencia = trim($rows['horas_licencia']);
                         $codigo_tipo_licencia = trim($rows['codigo_tipo_licencia']);
                         $descripcion_licencia = trim($rows['descripcion_licencia']);
                         $fecha_asistencia = trim($rows['fecha']);
                         // 
                         if($descripcion_jornada == "0H"){
                              // CALCULO DEL SALARIO CUANDO HAY PERMISOS
                             switch ($descripcion_licencia) {
                                 case 'F':
                                     // CUANDO SEA FALTA QUE ACCIÓN REALIZAR
                                    $salario = $salario - (8 * $pago_diario_hora);

                                     break;
                                 case 'C':
                                     // CUANDO SEA CASTIGO QUE ACCIÓN REALIZAR
                                   $salario = $salario - (8 * $pago_diario_hora);
                                 }   // LAZO SWICTH...
                         } // LAZO IF...
                     }   // LAZO WHILE
                        //  CALCULAR EL SALARIO DE ESTE CODIGO DE EMPLEADO.
                        $total_salario = $salario + $total_tiempo_extra + $asuetos;
                 }   // LAZO IF....
     } // LAZO FOR.
}   // lazo if para saber como buscar la fecha de descanso.
       
/////////////////////////////////////////////////////////////////////////////////////////////////////////        
    // VERIFICAR SI SE DESEA CALCULAR, que aparezca impreso los salarios,extras etc.
    if($Calcular == "si"){
        for($j=0;$j<=6;$j++){
            switch ($j) {
                case '0':
                    # PRESENTAR SALARIO
                    $salario_pantalla = number_format($salario,2,'.',',');
                    $pdf->Cell($w[1],6,'$' . $salario_pantalla,'1',0,'C',$fill);
                    break;
                case '1':
                    # PRESENTAR ASUETOS
                    $asueto_pantalla = number_format($asuetos,2,'.',',');
                    $pdf->Cell($w[1],6,$asueto_pantalla,'1',0,'C',$fill);
                    break;
                case '2':
                    # PRESENTAR EXTRA
                    $extra_pantalla = number_format($extra,2,'.',',');
                    $pdf->Cell($w[1],6,'$' . $extra_pantalla,'1',0,'C',$fill);
                    break;
                case '3':
                    # PRESENTAR Cantidad de días Nocturna
                    if($DepartamentoEmpresa == '09' || $DepartamentoEmpresa  == '08' || $DepartamentoEmpresa  == '05'){
                        $pdf->Cell($w[5],6,$NocturnaCantidad,'1',0,'C',$fill);
                    }
                    break;
                case '4':
                    # PRESENTAR Valor $$ de días Nocturna
                    if($DepartamentoEmpresa == '09' || $DepartamentoEmpresa  == '08' || $DepartamentoEmpresa  == '05'){
                        $NocturnaValor = round($NocturnaCantidad * $NocturnaValorUnitario,2);
                        $SalidaPantallaNocturnaValor = number_format($NocturnaValor,2,'.',',');
                        $pdf->Cell($w[5],6,$SalidaPantallaNocturnaValor,'1',0,'C',$fill);
                        // Recalcular total tiempo extra y total salario
                        $total_tiempo_extra = $total_tiempo_extra + $NocturnaValor;
                        $total_salario = $total_salario + $total_tiempo_extra;
                    }
                    break;
                case '5':
                    # PRESENTAR TOTAL TIEMPO EXTRA
                    $total_tiempo_extra_pantalla = number_format($total_tiempo_extra,2,'.',',');
                    $pdf->Cell($w[1],6,'$' . $total_tiempo_extra_pantalla,'1',0,'C',$fill);
                    break;
                case '6':
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
    }else{
        for($j=0;$j<=6;$j++){
            switch ($j) {
                case '0':
                    # PRESENTAR SALARIO
                    $pdf->Cell($w[1],6,'','1',0,'C',$fill);
                    break;
                case '1':
                    # PRESENTAR ASUETOS
                    $pdf->Cell($w[1],6,'','1',0,'C',$fill);
                    break;
                case '2':
                    # PRESENTAR EXTRA
                    $pdf->Cell($w[1],6,'','1',0,'C',$fill);
                    break;
                case '3':
                    # PRESENTAR Cantidad de días Nocturna
                    if($DepartamentoEmpresa == '09' || $DepartamentoEmpresa  == '08' || $DepartamentoEmpresa  == '05'){
                        $pdf->Cell($w[5],6,'','1',0,'C',$fill);
                    }
                    break;
                case '4':
                    # PRESENTAR Valor $$ de días Nocturna
                    if($DepartamentoEmpresa == '09' || $DepartamentoEmpresa  == '08' || $DepartamentoEmpresa  == '05'){
                        $pdf->Cell($w[5],6,'','1',0,'C',$fill);
                    }
                    break;
                case '5':
                    # PRESENTAR TOTAL TIEMPO EXTRA
                    $pdf->Cell($w[1],6,'','1',0,'C',$fill);
                    break;
                case '6':
                    # PRESENTAR TOTAL SALARIO
                    $pdf->Cell($w[1],6,'','1',0,'C',$fill);
                    break;
                default:
                    # code...
                    $pdf->Cell($w[1],6,'','1',0,'C',$fill);
                    break;
            }
        }
    }
    //$pdf->Cell($w[1],6,$total_horas_jornada,'1',0,'C',$fill);
    // SALTO DE LINEA Y FILL.
    $pdf->Ln();   
    // SET TAMAÑO DE LETRA
    $pdf->SetFont('Arial','',9); // I : Italica; U: Norm
    $pdf->SetTextColor(0);
}
?>