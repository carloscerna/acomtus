<?php
/*
TABLA: catalogo_departamento_empresa
    codigo	descripcion
    01	Oficina                       
    02	Motorista                     
    03	Revisador                     
    04	Aseo/Otros                    
    05	Taller                        
    06	Microbuseros                  
    07	Accionista                    
    08	Vigilancia                    
    09	Mantenimiento                 
*/
// ruta de los archivos con su carpeta
    $path_root=trim($_SERVER['DOCUMENT_ROOT']);
// Archivos que se incluyen.
    include($path_root."/acomtus/includes/mainFunctions_conexion.php");
// Llamar a la libreria fpdf
    include($path_root."/acomtus/php_libs/fpdf186/fpdf.php");
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
    $CalcularDatos = $_REQUEST["chkCalcular"];
    if(isset($_REQUEST["persona_responsable"])){
        $reporte_persona_responsable = $_REQUEST["persona_responsable"];
    }
    $db_link = $dblink;
    $total_dias_quincena = 0;
    $reporte_trabajo = "";
    $InicioFinDia = 0;
    $pago_diario = 0;
    $horas_jornada = 0;
    $total_lineas = 1;
    $contar_4H = 1;
    $fecha_inicio_adb = array();
    $DescripcionJornada = array();
    $codigo_produccion = 0;
    $pase = 0;
    $link = "/acomtus/php_libs/reportes/control_tiquete_ingresos.php?codigo_produccion=" . $codigo_produccion;
// Nocturnidad.
    $NocturnaValorUnitario = 0.57;
    $NocturnaCantidad = 0;
    $NocturnaValor = 0;
// Establecer formato para la fecha.
    date_default_timezone_set('America/El_Salvador');
	setlocale(LC_TIME,'es_SV');
// CREAR MATRIZ DE MESES Y FECH.
    $meses = array("enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre");
//Crear una línea. Fecha con getdate();
    $hoy = getdate();
    $NombreDia = $hoy["wday"];  // dia de la semana Nombre.
    $dia = $hoy["mday"];    // dia de la semana
    $mes = $hoy["mon"];     // mes
    $año = $fecha_ann;//$hoy["year"];    // año
    $total_de_dias = cal_days_in_month(CAL_GREGORIAN, (int)$fecha_mes, $año);
    $NombreMes = $meses[(int)$fecha_mes - 1];
// definimos 2 array uno para los nombre de los dias y otro para los nombres de los meses
    $nombresDias = array("D", "L", "Ma", "Mi", "J", "V", "S" );
    $nombresMeses = array(1=>"Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre");
//  arrays()
    $w = array();
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
// Crear Matriz para el # de dia y nombre del dia. Y VARIABLES GLOBALES
//
    $nombreDia_a = array(); $numeroDia_a = array(); $fecha_periodo = array(); $FechaDDT = array();
    $FechaDescripcionAsueto = array();
    $FechaAsistencia = null; $codigo_personal = 0;
//
// recorremos las dechas del periodo
    foreach($periodo as $date){
    // definimos la variables para verlo mejor
        $nombreDia = $nombresDias[$date->format("w")];
        $nombreMes = $nombresMeses[$date->format("n")];
        $numeroDia = $date->format("j");
        $numeroDiaDosDigitos = $date->format("d");
        $anyo = $fecha_ann;
        $fecha_periodo[] = $anyo.'-'.$fecha_mes.'-'.$numeroDiaDosDigitos;
        $nombreDia_a[] = $nombreDia;
        $numeroDia_a[] = $numeroDia;
    // fecha periodo fin y fecha periodo inicio
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
// CREAR ARRAY ASOCIATIVA DE LA TABLA: CATALOGO_DEPARTAMENTO_EMPRESA.
    $query_departamento_empresa = "SELECT * FROM catalogo_departamento_empresa ORDER BY codigo";    // consulta
        $resultado_de = $dblink -> query($query_departamento_empresa); // ejecuciónd ela consult.a
        // while
        while($listado = $resultado_de -> fetch(PDO::FETCH_BOTH))
            {
                $codigo_de = $listado["codigo"];
                $descripcion_de = trim($listado["descripcion"]);
                // CREAR ARRAY ASOCIATIVA
                    $NombresCodigoDE[$descripcion_de] = $codigo_de;
            }
// CREAR ARRAY ASOCIATIVA DE LA TABLA: CATALOGO_TIPO_LICENCIA_O_PERMISO
    $query_licencia_permiso = "SELECT * FROM catalogo_tipo_licencia_o_permiso ORDER BY codigo";    // consulta
    $resultado_lp = $dblink -> query($query_licencia_permiso); // ejecuciónd ela consult.a
    // while
    while($listado = $resultado_lp -> fetch(PDO::FETCH_BOTH))
        {
            $codigo_lp = $listado["id_"];
            $descripcion_lp = trim($listado["descripcion"]);
            // CREAR ARRAY ASOCIATIVA
                $NombresCodigoLicenciaPermiso[$descripcion_lp] = $codigo_lp;
        }
// CREAR ARRAY ASOCIATIVA DE LA TABLA: asuetos
    $query_asuetos = "SELECT * FROM asuetos ORDER BY fecha";    // consulta
    $resultado_asuetos = $dblink -> query($query_asuetos); // ejecuciónd ela consult.a
    // while
    while($listado = $resultado_asuetos -> fetch(PDO::FETCH_BOTH))
        {
            $fecha_asueto = $listado["fecha"];
            $descripcion_asueto = trim($listado["descripcion"]);
            // CREAR ARRAY ASOCIATIVA
                $FechaDescripcionAsueto["Fecha"][] = $fecha_asueto;
                $FechaDescripcionAsueto["Descripcion"][] = $descripcion_asueto;
        }
        //var_dump($FechaDescripcionAsueto);
        $buscar = array_search("2023-05-10", $FechaDescripcionAsueto['Fecha']);
        if(!empty($buscar)){
            $Fecha = $FechaDescripcionAsueto['Fecha'][$buscar];
            $Descripcion = $FechaDescripcionAsueto['Descripcion'][$buscar];
        }

        //print $buscar . "<br>";
        //echo 'Fecah ' . $Fecha. ' Descripcion ' . $Descripcion;
        //exit;
class PDF extends FPDF
{
//Cabecera de página
function Header()
{
    global $reporte_trabajo, $reporte_ruta, $reporte_persona_responsable, $DepartamentoEmpresa, $NombresCodigoDE;
    //Logo
    $img = $_SERVER['DOCUMENT_ROOT'].'/acomtus/img/'.$_SESSION['logo_uno'];
    $this->Image($img,5,4,24,24);
    //Arial bold 14
    $this->SetFont('Arial','B',14);
    //Título
    $this->SetXY(30,5);
    $this->Cell(100,7,mb_convert_encoding($_SESSION["nombre_institucion"],"ISO-8859-1"),0,1,"L",false);
    //$this->RotatedText(30,10,mb_convert_encoding($_SESSION['nombre_institucion'],"ISO-8859-1"),0);
    //Arial bold 13
    $this->SetFont('Arial','B',11);
    $this->SetX(30);
    $this->Cell(100,6,mb_convert_encoding($reporte_trabajo,"ISO-8859-1"),0,1,"L",false);
    $this->SetX(30);
    $this->Cell(100,6,mb_convert_encoding($reporte_ruta,"ISO-8859-1"),0,1,"L",false);
    // Persona REsponsable del Punteo.
    $this->SetFont('Arial','B',9);
    $this->SetX(30);
    $this->Cell(180,6,mb_convert_encoding("Responsable del Punteo: " . $reporte_persona_responsable,"ISO-8859-1"),0,0,"L",false);
    $this->Cell(4,6,"",0,0,"L",false);
    if($DepartamentoEmpresa == $NombresCodigoDE["Motorista"]){
        $this->SetFillColor(255,100,100);   // CORAL CLARO
            $this->Cell(4,4,"",1,0,"L",true);   // cuadro
        $this->SetFillColor(255,100,100);   //RGB(255,100,100)
        $this->SetFont('Arial','B',7);
            $this->Cell(15,6,mb_convert_encoding("Sin Nº Control","ISO-8859-1"),0,1,"L",false);
        $this->SetFont('Arial','B',9);
    }
    // Posición en donde va iniciar el texto.
    $this->SetY(25);
}
//Pie de página
function Footer()
{
  // Establecer formato para la fecha.
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
    global $nombreDia_a, $numeroDia_a, $InicioFinDia, $DepartamentoEmpresa, $NombresCodigoDE, $ColorDias;
    //Colores, ancho de línea y fuente en negrita
        $this->SetFillColor(255,255,255);
        $this->SetTextColor(0);
        $this->SetDrawColor(0,0,0);
        $this->SetLineWidth(.3);
        $this->SetFont('','B');
    //Cabecera
        $w=array(5,13,75,6,14,7,13,7,3); //determina el ancho de las columnas
        $w1=array(5.66); //determina el ancho de las columnas
    // PRIMER BLOQUE DE INFORMACION #, CODIGO, EMPLEADO, TOTAL
        for($i=0;$i<count($header);$i++){
            $this->Cell($w[$i],7,mb_convert_encoding($header[$i],"ISO-8859-1"),1,0,'C',1);   // crea encabezado apartir del header fancy
        }
        // Coloca las lineas de los cuadros. los 15 d{ias de la semana}
        $this->SetFillColor(255,255,255); // rgb(255,255,255)
        for($j=$InicioFinDia;$j<=(count($nombreDia_a))-1;$j++){
            if($DepartamentoEmpresa == $NombresCodigoDE['Mantenimiento'] || $DepartamentoEmpresa  == $NombresCodigoDE['Vigilancia'] || $DepartamentoEmpresa  == $NombresCodigoDE['Taller']){
                if($nombreDia_a[$j] == "S" || $nombreDia_a[$j] == "D"){
                    $this->SetFillColor(213, 216, 220);
                        $this->Cell($w[3],7,$nombreDia_a[$j],'1',0,'C',1);
                }else{
                    $this->SetFillColor(255,255,255);
                        $this->Cell($w[3],7,$nombreDia_a[$j],'1',0,'C',1);
                }
            }else{
                if($nombreDia_a[$j] == "S" || $nombreDia_a[$j] == "D"){
                    $this->SetFillColor(213, 216, 220);
                        $this->Cell($w[7],7,$nombreDia_a[$j],'1',0,'C',1);
                }else{
                    $this->SetFillColor(255,255,255);
                        $this->Cell($w[7],7,$nombreDia_a[$j],'1',0,'C',1);
                }
            }
        }
        // reset color y draw
        $this->SetTextColor(0);
        $this->SetDrawColor(0,0,0);
        $this->SetFillColor(255,255,255);
    /////////////////////////////////////////////////////////////////////////////////////////////
    // ESPACIO PARA SEGUNDA LINEA ULTIMO BLOQUE
    /////////////////////////////////////////////////////////////////////////////////////////////
        // CELDA DE SEPARACIÓN RGB(130, 224, 170)
        $this->SetFillColor(130,224,170);
        $this->Cell($w[8],7,'','L',0,'C',1);    // ES LA DIVISIÓN ENTRO EL TOTAL DE DIAS Y LOS CALCULOS (SALARIO, ASUETOS, EXTRA, TOTAL-EXTRA, TOTAL)
        // CAMBIAR EL COLOR DEL FONDO
        $this->SetFillColor(255);
        // DEPEDEN DEL CODIGO DEL DEPARTAMENTO EMPRESA ASI CAMBIA LOS TITULOS DE LA ULTIMA PARTE.
        if($DepartamentoEmpresa == $NombresCodigoDE['Mantenimiento'] || $DepartamentoEmpresa  == $NombresCodigoDE['Vigilancia'] || $DepartamentoEmpresa  == $NombresCodigoDE['Taller']){
            $this->SetFont('Arial','',5);
                $header2=array('','','','Nocturno','Total','');
            $this->SetFont('Arial','',9);
            // recrrorer matriz
            for($j=0;$j<count($header2);$j++){
                if($j== 3){
                    $this->Cell($w[4],7,mb_convert_encoding($header2[$j],"ISO-8859-1"),'LRTB',0,'C',1);
                }else{
                    $this->Cell($w[1],7,mb_convert_encoding($header2[$j],"ISO-8859-1"),'LRT',0,'C',1);    
                }
            }
        }else{
            $header2=array('','','','Total','');
            // recrrorer matriz
            for($j=0;$j<count($header2);$j++){
                    $this->Cell($w[1],7,mb_convert_encoding($header2[$j],"ISO-8859-1"),'LRT',0,'C',1);    
            }
        }
            $this->Ln();  /// salto de linea
            $this->Cell($w[0],7,'','LBR',0,'C',1);  // #
            $this->Cell($w[1],7,'','LBR',0,'C',1);  // codigo  
            $this->Cell($w[2],7,'','LBR',0,'C',1);  // nombre
        // RESET COLOR DE FONOD A BLANCO #FFFFFF
            $this->SetFillColor(255,255,255);
            for($j=$InicioFinDia;$j<=count($nombreDia_a)-1;$j++){
                if($DepartamentoEmpresa == $NombresCodigoDE['Mantenimiento'] || $DepartamentoEmpresa  == $NombresCodigoDE['Vigilancia'] || $DepartamentoEmpresa  == $NombresCodigoDE['Taller']){
                    if($nombreDia_a[$j] == "S" || $nombreDia_a[$j] == "D"){
                        $this->SetFillColor(192,192,192);
                            $this->Cell($w[3],7,$numeroDia_a[$j],'1',0,'C',1);
                    }else{
                        $this->SetFillColor(255,255,255);
                            $this->Cell($w[3],7,$numeroDia_a[$j],'1',0,'C',1);
                    }
                }else{
                    if($nombreDia_a[$j] == "S" || $nombreDia_a[$j] == "D"){
                        $this->SetFillColor(192,192,192);
                            $this->Cell($w[7],7,$numeroDia_a[$j],'1',0,'C',1);
                    }else{
                        $this->SetFillColor(255,255,255);
                            $this->Cell($w[7],7,$numeroDia_a[$j],'1',0,'C',1);
                    }
                }
                
            }

    /////////////////////////////////////////////////////////////////////////////////////////////
    // ESPACIO PARA TERCERA LINEA ULTIMO BLOQUE
    /////////////////////////////////////////////////////////////////////////////////////////////
        $this->Cell($w[8],7,'','L',0,'C',1);
        // RESET COLOR A BLANCO.
        $this->SetFillColor(255);
        if($DepartamentoEmpresa == $NombresCodigoDE['Mantenimiento'] || $DepartamentoEmpresa  == $NombresCodigoDE['Vigilancia'] || $DepartamentoEmpresa  == $NombresCodigoDE['Taller']){
            $this->SetFont('Arial','',5);
                $header2=array('Salario','Asuetos','Extra','C','V','Extra','TOTAL');
            $this->SetFont('Arial','',9);
                // recrrorer matriz
                for($j=0;$j<count($header2);$j++){
                    if($j == 3 || $j == 4){
                        $this->Cell($w[5],7,mb_convert_encoding($header2[$j],"ISO-8859-1"),'LRBT',0,'C',1);
                    }else{
                        $this->Cell($w[1],7,mb_convert_encoding($header2[$j],"ISO-8859-1"),'LRB',0,'C',1);
                    }
                }
        }else{
            $header2=array('Salario','Asuetos','Extra','Extra','TOTAL');
            // recrrorer matriz
            for($j=0;$j<count($header2);$j++){
                    $this->Cell($w[1],7,mb_convert_encoding($header2[$j],"ISO-8859-1"),'LRB',0,'C',1);
            }
        }
        $this->Ln();  /// salto de linea
    /////////////////////////////////////////////////////////////////////////////////////////////
    /////////////////////////////////////////////////////////////////////////////////////////////
    //Restauración de colores y fuentes rgb(213,213,213)
        $this->SetFillColor(213, 213, 213);
        $this->SetTextColor(0);
        $this->SetFont('');
    // FALSO O VERDADERO DEL COLOR DE FONDO DE CELL();
        $fill=false;
}
}
//************************************************************************************************************************
// Creando el Informe.
//************************************************************************************************************************
    $pdf=new PDF('L','mm','Letter');
    $data = array();
    #Establecemos los márgenes izquierda, arriba y derecha:
    $pdf->SetMargins(5, 15, 5);
    #Establecemos el margen inferior: 
    $pdf->SetAutoPageBreak(true,10);
//Títulos de las columnas
    $header=array('Nº','Código','Empleado');
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',9);
    $pdf->AddPage();
// Aqui mandamos texto a imprimir o al documento.
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetFont('Arial','B',9); // I : Italica; U: Normal;
    $pdf->ln();
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetFont('Arial','',9); // I : Italica; U: Normal;
//
    $pdf->FancyTable($header); // Solo carge el encabezado de la tabla porque medaba error el cargas los datos desde la consulta.
    if($DepartamentoEmpresa == $NombresCodigoDE['Mantenimiento'] || $DepartamentoEmpresa  == $NombresCodigoDE['Vigilancia'] || $DepartamentoEmpresa  == $NombresCodigoDE['Taller']){
        $w=array(5,13,75,6,14,7,13,7,3); //determina el ancho de las columnas
    }else{
        $w=array(5,13,75,7,14,7,13,7,3); //determina el ancho de las columnas
    }
    $w1=array(5.66); //determina el ancho de las columnas de cada dia.
//////////////////////////////////////////////////////////////////////////////////////////    
// ARMAR LA CONSULTA // DE ACUERDO AL CODIGO DEL DEPARTAMENTO EMPRESA
//////////////////////////////////////////////////////////////////////////////////////////
    if($DepartamentoEmpresa == $NombresCodigoDE["Motorista"]){
        // CONSULTA EN BASE AL AREA DE MOTORISTA Y LO FILTRA POR EL CODIGO DE LA RUTA.
        $query = "SELECT codigo, btrim(nombres || CAST(' ' AS VARCHAR) || apellidos) AS nombre_completo, pago_diario, salario 
            FROM personal WHERE codigo_ruta = '$ruta' and codigo_estatus = '01' ORDER BY codigo";
    }else{
        // CONSULTA LOS OTROS DEPARTAMENTO Y SOLO LO FILTRA CON EL CODIGO DELDEPARTAMAENTO.
        $query = "SELECT codigo, btrim(nombres || CAST(' ' AS VARCHAR) || apellidos) AS nombre_completo, pago_diario, salario 
            FROM personal WHERE codigo_departamento_empresa = '$DepartamentoEmpresa' and codigo_estatus = '01' ORDER BY codigo";
    }
    // EJECUTAR LA CONSULTA
        $consulta = $dblink -> query($query);
    // OBTENER EL TOTAL DE LINEAS
        $total_lineas = $consulta -> rowCount();
    // determinar cual es el primer valor de la fecha y el ultimo
        $fecha_periodo_inicio = ""; $fecha_periodo_fin = "";
        $fecha_periodo_inicio = reset($fecha_periodo);
        $fecha_periodo_fin = end($fecha_periodo);
/*
    print_r($inicio);// "fecha Inicio: $inicio Fecha fin: $fin";
    print_r($fin);
    print_r($fecha_periodo);
    print_r($fecha_periodo_inicio);
    print "<br>";
    print_r($fecha_periodo_fin);
exit;
*/
    $fill=false; $fillFecha = false; $i=1; $m = 0; $f = 0; $suma = 0;
        while($row = $consulta -> fetch(PDO::FETCH_BOTH))
            {
                // Variables 
                    $NocturnaCantidad = 0;
                // variable para verificar que tipo de permiso o días trabajados.
                    $codigo = trim($row['codigo']);
                    $nombre_completo = mb_convert_encoding(trim($row['nombre_completo']),"ISO-8859-1");
                //  CALCULO DEL SALARIO MENSUAL, DIARIO Y POR HORA.
                    $pago_mensual = ($row['salario']);
                    $pago_diario = round($row['salario'] / 30,10);      // CONVIERTE A PAGO DIARIO
                    $pago_diario_hora = round($pago_diario / 8,10);     // CONVIERTE A PAGO DIARIO POR HORA
                    $pago_diario_extra_4H = round($pago_diario_hora * 4,10);     // CONVIERTE A PAGO DIARIO POR HORA 1T
                    $pago_diario_extra_1T = round($pago_diario_hora * 8,10);     // CONVIERTE A PAGO DIARIO POR HORA 1T
                    $pago_diario_extra_1_5T = round($pago_diario_hora * 12,10);     // CONVIERTE A PAGO DIARIO POR HORA 1.5T
                // CREAR ARRAY ASOCIATIVA. SALARIO.Ç
                    $salario["Mensual"] = $pago_mensual;
                    $salario["PorDia"] = $pago_diario;
                    $salario["PorHora"] = $pago_diario_hora;
                    $salario["Extra4H"] = $pago_diario_extra_4H;
                    $salario["Extra1T"] = $pago_diario_extra_1T;
                    $salario["Extra15T"] = $pago_diario_extra_1_5T;
                    $salario["Nominal"] = 0;
                    $salario["Extra"] = 0;
                    $salario["TotalExtra"] = 0;
                    $salario["Total"] = 0;
                    $salario["SalarioQuincena"] = round($total_dias_quincena * $pago_diario,2);
                    $salario["Descuento4HFC"] = 0;
                // DATOS AL INFORME
                    $pdf->Cell($w[0],6,$i,1,0,'C',$fill);        // núermo correlativo
                    $pdf->Cell($w[1],6,$codigo,1,0,'L',$fill);   // codigo empleado
                    $pdf->SetFont('Arial','',8);
                        $pdf->Cell($w[2],6,$nombre_completo . ' -$ ' . $salario['Mensual'] . ' - ' . $total_dias_quincena,1,0,'L',$fill); // Nombre, Salario Nominal y días.
                    $pdf->SetFont('Arial','',9);
                // Rellenar los cuadros segun el numero de dias. CALCULANDO DIAS COMPLETOS, MEDIO TIEMPO, ISSS, VACACIONES.
                    rellenar($total_dias_quincena);
                // VALIDAR EL RELLENAR $I.
                    rellenar_i($i);
                // ACUMULAR EL VALOR DE $I y establece el fondo de la caja de texto Cell();
                    //$fill=!$fill;
                    $i=$i+1;
                    //$pdf->SetFillColor(213, 213, 213);    // SIN FONDO
            }
            // RELLENAR DATOS SI ES MENOR A 25 SEGUN $I
                rellenar_datos($i);    
    // RELLENAR LINEA DE ABAJO
       // $pdf->Cell(array_sum($w) + $total_dias_quincena,0,'','T');
    // Salida del pdf.
        $modo = "I"; // Envia al navegador (I), Descarga el archivo (D), Guardar el fichero en un local(F).
        $print_nombre = mb_convert_encoding("Planilla: $DepartamentoEmpresaText - $quincena - $mes.pdf","ISO-8859-1");
        $pdf->Output($print_nombre,$modo);
/////////////////////////////////////////////////////////////////////////////////////
//************* */ FUNCIONES.*******************************************************
/////////////////////////////////////////////////////////////////////////////////////
function rellenar_i($i){
    global $pdf, $fill, $w, $w1, $header, $i, $total_dias_quincena, $total_lineas;
    // SALTO DE PAGINA QUE DEPENDE DEL NUMERO DE LINEAS.
    if($i==25 || $i == 51 || $i == 65){
        $pdf->Cell($w[0],6,'','T',0,'C',$fill);    // núermo correlativo
        $pdf->Cell($w[1],6,'','T',0,'L',$fill);    // codigo empleado
        $pdf->Cell($w[2],6,'','T',0,'L',$fill);    // Nombre + apellido_materno + apellido_paterno
        $pdf->Cell($w1[0]*$total_dias_quincena,6,'','T',0,'L',$fill);    // Total de dias
        if($total_lineas > 25){
            $pdf->AddPage();
            // Posición en donde va iniciar el texto.
            $pdf->SetY(30);
            $pdf->FancyTable($header);
        }
    }    
}
// POR EL MOMENTO NO EJECUTA NADA.
function rellenar_datos($linea){
    global $i, $pdf, $w, $w1, $total_dias_quincena, $fill;
        // EVALUAR SI $I ES MENOR DE 25.
        if($i<=25){
            //
            $pdf->Cell($w[0],6,'','T',0,'C',false);    // núermo correlativo
            $pdf->Cell($w[1],6,'','T',0,'L',false);    // codigo empleado
            $pdf->Cell($w[2],6,'','T',0,'L',false);    // Nombre + apellido_materno + apellido_paterno
            $pdf->Cell($w1[0]*$total_dias_quincena,6,'','T',0,'L',false);    // Total de dias
        }
}
function rellenar($total_dias_quincena){
    // VARIABLES GLOBALES
        global $dblink, $pdf, $salario, $w, $fill, $fecha_periodo_inicio, $fecha_periodo_fin, $codigo, $CalcularDatos,
            $DepartamentoEmpresa, $NombresCodigoDE, $fillFecha, $codigo_produccion, $link, $NocturnaValorUnitario, $JornadaLicenciaPermiso, $FechaDDT,
            $CodigoNombreJornadaDDT, $FechaDescripcionAsueto;
    // VARIABLES LOCALES
        $CodigoNombreJornada = array(); $ValoresCount = array();
     // DECLARACI{ON DE AMTRICES}
        $FechaDDT = array(); //$descripcion_jornada_a_P2 = array(); $fecha_inicio_adb = array();
            $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
    // BLOQUE EXPERIMENTAL, EXTRAER LOS VALORES DE CADA JORNDAD, PERMISO, ETC. Y PASARLOS A UNA MATRIZ ASOCIATIVA.
            $query_asistencia = "SELECT pa.codigo_personal, pa.fecha, pa.codigo_jornada, cat_j.descripcion as descripcion_jornada, 
                pa.codigo_tipo_licencia, cat_lp.descripcion as descripcion_licencia, 
                pa.codigo_jornada_descanso, cat_jd.descripcion as descripcion_descanso,
                pa.codigo_jornada_vacaciones,  cat_jv.descripcion as descripcion_vacacion,
                pa.codigo_jornada_nocturna, cat_jn.descripcion as descripcion_nocturna,
                pa.codigo_jornada_e_4h, cat_j4.descripcion as descripcion_e_4h,
                cat_lp.horas as horas_licencia, cat_j.horas,
                pa.codigo_jornada_asueto, 
                cat_jn.descripcion as descripcion_jornada_nocturna,
                cat_ja.descripcion as descripcion_asueto
                    FROM personal_asistencia pa 
                        INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = pa.codigo_jornada
                        INNER JOIN catalogo_jornada cat_jd ON cat_jd.id_ = pa.codigo_jornada_descanso 
                        INNER JOIN catalogo_jornada cat_jv ON cat_jv.id_ = pa.codigo_jornada_vacaciones 
                        INNER JOIN catalogo_jornada cat_j4 ON cat_j4.id_ = pa.codigo_jornada_e_4h 
                        INNER JOIN catalogo_jornada cat_jn ON cat_jn.id_ = pa.codigo_jornada_nocturna 
                        INNER JOIN catalogo_jornada cat_ja ON cat_ja.id_ = pa.codigo_jornada_asueto 
                        INNER JOIN catalogo_tipo_licencia_o_permiso cat_lp ON cat_lp.id_ = pa.codigo_tipo_licencia
                            WHERE pa.codigo_personal = '$codigo' and pa.fecha >= '$fecha_periodo_inicio' and pa.fecha <= '$fecha_periodo_fin'
                                ORDER BY pa.fecha";
    // EJECUTAR CONSULTA
        $consulta_asistencia = $dblink -> query($query_asistencia);
        // validar si existen archivos en la consulta segun la fecha.
            $count_asistencia = $consulta_asistencia -> rowCount();
        // Verificar si existen registros.
        if($consulta_asistencia -> rowCount() != 0){
            while($listado = $consulta_asistencia -> fetch(PDO::FETCH_BOTH))
                {
                    // varloes para las matrices asociativas
                        $codigo_jornada = trim($listado['codigo_jornada']);
                        $codigo_personal = trim($listado['codigo_personal']);
                        $codigo_jornada_asueto = trim($listado['codigo_jornada_asueto']);
                        $descripcion_jornada = trim($listado['descripcion_jornada']);
                        $descripcion_licencia = trim($listado['descripcion_licencia']);
                        $descripcion_descanso = trim($listado['descripcion_descanso']);
                        $descripcion_vacacion = trim($listado['descripcion_vacacion']);
                        $descripcion_nocturna = trim($listado['descripcion_nocturna']);
                        $descripcion_asueto = trim($listado['descripcion_asueto']);
                        $descripcion_4h = trim($listado['descripcion_e_4h']);
                        $fecha_asistencia = trim($listado['fecha']);
                        //
                        $CodigoNombreJornada['DescripcionJornada'][] = $descripcion_jornada;
                        $CodigoNombreJornada['DescripcionLicencia'][] = $descripcion_licencia;
                        $CodigoNombreJornada['DescripcionDescanso'][] = $descripcion_descanso;
                        $CodigoNombreJornada['DescripcionVacacion'][] = $descripcion_vacacion;
                        $CodigoNombreJornada['DescripcionNocturna'][] = $descripcion_nocturna;
                        $CodigoNombreJornada['DescripcionExtra4H'][] = $descripcion_4h;
                        $CodigoNombreJornada['DescripcionAsueto'][] = $descripcion_asueto;
                        $CodigoNombreJornada['FechaAsistencia'][] = $fecha_asistencia;
                        
                }   // WHILEE QUE RECORRER LA CONSULTA, CUANDO HAY REGISTROS.
                    /////////////////////////////////////////////////////////////////////////////////////////////////
                    // IMPRIMIR VALORES EN PANTALLA
                    /////////////////////////////////////////////////////////////////////////////////////////////////
                    $fila_array = 0;
                    foreach ($CodigoNombreJornada['DescripcionJornada'] as $valor => $Jornada)
                    {
                        // VALIDAR SI EXISTE NUMERO DE CONTROL CON FECHA Y CODIGO PERSONAL.
                            $FechaAsistencia = $CodigoNombreJornada["FechaAsistencia"][$fila_array];
                            if($DepartamentoEmpresa == $NombresCodigoDE["Motorista"]){
                                VerificarControl($FechaAsistencia, $codigo_personal);
                            }
                        // REVISAR SI ANTES HAY DESCANSO EN ASUETO.
                            $JornadaLicenciaPermiso = $CodigoNombreJornada["DescripcionLicencia"][$fila_array]; // VARIABLES CUANDO ES DIFERENTE DE 1T. (1 TANDA)
                                if($JornadaLicenciaPermiso != "TDA"){
                                // REVISAR SI LA FECHA ES DE ASUETO.
                                    $buscar = array_search($CodigoNombreJornada["FechaAsistencia"][$fila_array], $FechaDescripcionAsueto['Fecha']);
                                    if(!empty($buscar)){
                                        $Fecha = $FechaDescripcionAsueto['Fecha'][$buscar];
                                        $Descripcion = $FechaDescripcionAsueto['Descripcion'][$buscar];
                                        //
                                            $Jornada = "A";  
                                    }
                                }

                        //
                        $pdf->SetTextColor(0);
                        // VALIDAR LA JORNADAA
                        switch ($Jornada) {
                            case "1T":  // CAMBIAR EL 1T POR (.)
                                    Punto1T();  // CUANDO LA JORNADA ES NORMAL 1T.
                                break;
                            case "0H":  // CUANDO TIENE DESCANSO, PP, F, ISSS, C, V, TV, P, TD.
                                $JornadaLicenciaPermiso = $CodigoNombreJornada["DescripcionLicencia"][$fila_array]; // VARIABLES CUANDO ES DIFERENTE DE 1T. (1 TANDA)
                                // CAMBIAR EL COLOR
                                // VERDE: TV, TD, V, D, TDA.
                                // AZUL: PP, ISSS.
                                // ROJO: F Y C.
                                // VERIFICAR Y ASIGNAR A VARIABLE LA FECHA DE DESCANSO "D" Y TRABAJO DESCANSO "DT"
                                    CambiarJornadaColor($JornadaLicenciaPermiso, $FechaAsistencia, $codigo_personal);
                                // BUSCAR ELTIPO DE JORNADA QUE HIZO EN DESCANSO O VACACION
                                    switch ($JornadaLicenciaPermiso){                            
                                        case "TD":
                                            // Revisar si hay jornada Extra TRABAJO DESCANSO.
                                                $JornadaCodigoExtra = $CodigoNombreJornada["DescripcionDescanso"][$fila_array];
                                                    JornadaExtra($JornadaCodigoExtra);
                                        break;
                                        case 'TV':
                                            // Revisar si hay jornada Extra TRABAJO VACACION
                                            $JornadaCodigoExtra = $CodigoNombreJornada["DescripcionVacacion"][$fila_array];
                                            JornadaExtra($JornadaCodigoExtra);
                                        break;
                                        case 'TDA':
                                            // Revisar si hay jornada Extra TRABAJO VACACION
                                            $JornadaCodigoExtra = $CodigoNombreJornada["DescripcionAsueto"][$fila_array];
                                                JornadaExtra($JornadaCodigoExtra);
                                        break;
                                        default:
                                        break;
                                    }
                                break;
                            case "A":
                                // colocar la A de ASueto
                                    $JornadaAsueto = "A";
                                        CambiarJornadaColor($JornadaAsueto, $FechaAsistencia, $codigo_personal);
                                        // Revisar si hay jornada Extra TRABAJO VACACION
                                            $JornadaCodigoExtra = $CodigoNombreJornada["DescripcionAsueto"][$fila_array];
                                                JornadaExtra($JornadaCodigoExtra);
                            break;
                            default:
                                if($DepartamentoEmpresa == $NombresCodigoDE["Motorista"]){
                                    $link = "/acomtus/php_libs/reportes/control_tiquete_ingresos.php?codigo_produccion=" . $codigo_produccion;
                                    $pdf->Cell($w[3],6,$Jornada,'1',0,'C',$fillFecha, $link);   // CUALQUIER VALOR DE LA JORNADA
                                }else{
                                    if($Jornada == "4H"){
                                        $pdf->SetTextColor(0,0,128);   // COLOR ROJO rgb(0,0,128)
                                    }else{
                                        $pdf->SetTextColor(0,0,0);   // COLOR ROJO rgb(0,0,0)
                                    }
                                    $pdf->Cell($w[3],6,$Jornada,'1',0,'C',$fillFecha);   // CUALQUIER VALOR DE LA JORNADA
                                    $pdf->SetTextColor(0,0,0);   // COLOR ROJO rgb(0,0,0)
                                }
                                    // Revisar si hay jornada Extra TRABAJO EN 4H
                                        $JornadaCodigoExtra = $CodigoNombreJornada["DescripcionExtra4H"][$fila_array];
                                        JornadaExtra4H($JornadaCodigoExtra);
                            break;
                        }
                        // 
                        // BUSCAR SI TIENE NOCTURNIDAD. (N)
                            $JornadaNocturnidad = $CodigoNombreJornada["DescripcionNocturna"][$fila_array]; // VARIABLES CUANDO ES DIFERENTE DE 1T. (1 TANDA)
                            JornadaExtraNocturnidad($JornadaNocturnidad);
                        // fila_array
                            $fila_array++;
                    }
                    /// RELLENAR CON VALORES SI HACEN FALTA
                    $columnas = $total_dias_quincena - count($CodigoNombreJornada['DescripcionJornada']);
                    CuadrosFaltantes($columnas);
                    // CALCULO DE LA JORNADA DESPUES DE 8 HORAS DE TRABAJO (1.5T), Descanso y Asueto.
                        $ValoresCount['CantidadDescripcionJornada'] = array_count_values($CodigoNombreJornada['DescripcionJornada']);
                        $ValoresCount['CantidadDescripcionDescanso'] = array_count_values($CodigoNombreJornada['DescripcionDescanso']);
                        $ValoresCount['CantidadDescripcion4H'] = array_count_values($CodigoNombreJornada['DescripcionExtra4H']);
                        $ValoresCount['CantidadDescripcionAsueto'] = array_count_values($CodigoNombreJornada['DescripcionAsueto']);
                    //
                        //print_r($ValoresCount);
                        //exit;
                    // BUSCAR LA CANTIDAD DE 1.5T PARA OBTENER EL EXTRA. DE UNA JORNADA NORMAL.
                        foreach ($ValoresCount['CantidadDescripcion4H'] as $Jornada => $cantidad) {
                            if($Jornada == "4H"){
                                $salario["Extra"] = $salario["Extra"] + ($salario["Extra4H"] * $cantidad);
                            }
                            if($Jornada == "1T"){
                                $salario["Extra"] = $salario["Extra"] + ($salario["Extra1T"] * $cantidad);
                            }
                            if($Jornada == "1.5T"){
                                $salario["Extra"] = $salario["Extra"] + ($salario["Extra15T"] * $cantidad);
                            }
                        }
                    // BUSCAR LA CANTIDAD DE 1T Ó 1.5T PARA OBTENER EL EXTRA. DE UNA JORNADA DE DESCANSO.
                        foreach ($ValoresCount['CantidadDescripcionJornada'] as $Jornada => $cantidad) {
                            if($Jornada == "1.5T"){
                                $salario["Extra"] = $salario["Extra"] + ($salario["Extra4H"] * $cantidad);
                            }
                        }
                    // BUSCAR LA CANTIDAD DE 1T Ó 1.5T PARA OBTENER EL EXTRA. DE UNA JORNADA DE DESCANSO.
                        foreach ($ValoresCount['CantidadDescripcionDescanso'] as $Jornada => $cantidad) {
                            if($Jornada == "4H"){
                                $salario["Extra"] = $salario["Extra"] + ($salario["Extra4H"] * $cantidad);
                            }
                            if($Jornada == "1T"){
                                $salario["Extra"] = $salario["Extra"] + ($salario["Extra1T"] * $cantidad);
                            }
                            if($Jornada == "1.5T"){
                                $salario["Extra"] = $salario["Extra"] + ($salario["Extra15T"] * $cantidad);
                            }
                        }
                    // BUSCAR LA CANTIDAD DE 1T Ó 1.5T PARA OBTENER EL EXTRA. DE UNA JORNADA DE DESCANSO.
                        foreach ($ValoresCount['CantidadDescripcionAsueto'] as $Jornada => $cantidad) {
                            if($Jornada == "4H"){
                                $salario["Extra"] = $salario["Extra"] + ($salario["Extra4H"] * $cantidad);
                            }
                            if($Jornada == "1T"){
                                $salario["Extra"] = $salario["Extra"] + ($salario["Extra1T"] * $cantidad);
                            }
                            if($Jornada == "1.5T"){
                                $salario["Extra"] = $salario["Extra"] + ($salario["Extra15T"] * $cantidad);
                            }
                        }
            // IMPRIMIR EN PANTALLA SALARIO QUINCENA
            //
                $pdf->Cell($w[8],7,'','L',0,'C',false);    // ES LA DIVISIÓN ENTRO EL TOTAL DE DIAS Y LOS CALCULOS (SALARIO, ASUETOS, EXTRA, TOTAL-EXTRA, TOTAL)
            // VERIFICAR EL DESCUENTO.
                //print $codigo_personal . "<br>"; var_dump($FechaDDT);
                VerificarFechaDescuento($codigo_personal);
            //  CONDICIONAR PARA RELLENAR CON DATOS O SIN DATOS DE CALCULO.
                if($CalcularDatos == "si"){
                    # PRESENTAR SALARIO
                    // VERIFICAR SI ESTA TODA LA ASISTENCIA COMPLETA, PARA DAR UN BUEN DATO DE SALARIO.
                        $salario["SalarioQuincena"] = $salario["SalarioQuincena"] - ($salario["PorDia"] * ($total_dias_quincena - $count_asistencia));
                    // CON EL DESCUENTO
                        $salario["SalarioQuincena"] = $salario["SalarioQuincena"] - $salario["Descuento4HFC"];
                    // SALARIO EN PANTALLA
                        $salario_pantalla = number_format($salario["SalarioQuincena"],2,'.',',');
                        $pdf->SetTextColor(72,61,139);   // COLOR AZUL OSCURO rgb(72,61,139)
                            $pdf->Cell($w[1],6,'$' . $salario_pantalla,1,0,'C',$fill);
                            $pdf->SetTextColor(0,0,0);   // COLOR NEGRO rgb(0,0,0)
                    # PRESENTAR ASUETO.1
                        $asueto_pantalla = "";
                        $pdf->Cell($w[1],6,$asueto_pantalla,1,0,'C',$fill);
                    # PRESENTAR SALARIO EXTRA
                        $salario_extra_pantalla = number_format($salario["Extra"],2,'.',',');
                        $pdf->Cell($w[1],6,'$' . $salario_extra_pantalla,1,0,'C',$fill);
                    # CALCULO DE NOCTURNA EN EL CASO DE VIGILANCIA, MANTENIMIENTO Y Taller.
                        if($DepartamentoEmpresa == $NombresCodigoDE["Mantenimiento"] || $DepartamentoEmpresa  == $NombresCodigoDE["Vigilancia"] || $DepartamentoEmpresa  == $NombresCodigoDE["Taller"]){
                            $CantidadNocturnidad = count(array_keys($CodigoNombreJornada["DescripcionNocturna"], "N"));
                            $NocturnaValor = round($CantidadNocturnidad * $NocturnaValorUnitario,2);
                            $SalidaPantallaNocturnaValor = number_format($NocturnaValor,2,'.',',');
                            $pdf->Cell($w[5],6,$CantidadNocturnidad,'1',0,'C',$fill);   // Cantidad
                            $pdf->Cell($w[5],6,$SalidaPantallaNocturnaValor,'1',0,'C',$fill);   // Valor
                            $salario["Extra"] = $salario["Extra"] + $NocturnaValor; // Incrementar el valor de Total Extra.
                        }
                    # PRESENTAR SALARIO TOTAL EXTRA
                        $salario["TotalExtra"] = $salario["Extra"];
                        $salario_total_extra_pantalla = number_format($salario["TotalExtra"],2,'.',',');
                        $pdf->Cell($w[1],6,'$' . $salario_total_extra_pantalla,1,0,'C',$fill);
                    # PRESENTAR SALARIO TOTAL
                        $pdf->SetFont('Arial','B',8); // I : Italica; U: Normal;
                        $pdf->SetTextColor(72,61,139);   // COLOR AZUL OSCURO rgb(72,61,139)
                            $salario["Total"] = $salario["SalarioQuincena"] + $salario["TotalExtra"];
                            $salario_total_pantalla = number_format($salario["Total"],2,'.',',');
                            $pdf->Cell($w[1],6,'$' . $salario_total_pantalla,1,0,'C',$fill);
                        $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
                        $pdf->SetTextColor(0,0,0);   // COLOR NEGRO rgb(0,0,0)
                    // linea en blanco
                        $pdf->ln();
                }else{
                    RellenarSinCalculos();
                }
            // VACIAR LA VARIABLE DE LA FECHA PARA EL DESCUENTO
                unset($FechaDDT); unset($CodigoNombreJornadaDDT);
        } // FIN DEL IF QUE DETERMINA SI HAY REGISTROS.
        else{
            /// RELLENAR CON VALRIOS IS HACEN FALTA
                $columnas = $total_dias_quincena;
                CuadrosFaltantes($columnas);
            // linea en blanco
                $pdf->ln();
        }
}
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/* FUNCIONES FINALES*/
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function CambiarJornadaColor($JornadaLicenciaPermiso, $Fecha, $codigo_personal){
    global $pdf, $fill, $w, $salario, $FechaDDT;
        // CAMBIAR TAMAÑO
        $pdf->SetFont('Arial','B',8); // I : Italica; U: Normal;
        if($JornadaLicenciaPermiso == "PP" || $JornadaLicenciaPermiso == "ISSS"){
            $pdf->SetTextColor(0,0,255); // COLOR azul rgb(0,0,255)
            $salario["SalarioQuincena"] = $salario["SalarioQuincena"] - $salario["PorDia"];
        }
        if($JornadaLicenciaPermiso == "F" || $JornadaLicenciaPermiso == "C"){
            $pdf->SetTextColor(255,0,0);   // COLOR ROJO rgb(255,0,0)
        }
        if($JornadaLicenciaPermiso == "V" || $JornadaLicenciaPermiso == "D" || $JornadaLicenciaPermiso == "TV" || $JornadaLicenciaPermiso == "TD"){
            $pdf->SetTextColor(0,128,0);   // COLOR VERDE rgb(0,128,0)
        }
        if($JornadaLicenciaPermiso === "D" || $JornadaLicenciaPermiso == "TD"){
            // Acumular la Fecha ASistencia para luego verificar F, C, para el descuento del septimo.
                $FechaDDT[] = $Fecha;
        }
        if($JornadaLicenciaPermiso == "A" || $JornadaLicenciaPermiso == "TDA"){
            // Acumular la Fecha ASistencia para luego verificar F, C, para el descuento del septimo.
                if($JornadaLicenciaPermiso == "TDA"){
                    $FechaDDT[] = $Fecha;
                }
            // MARCAR EL ASUETO CON UN COLOR.
                $pdf->SetFillColor(255, 255, 100);   // CORAL CLARO// rgb(255, 255, 100);
                $pdf->SetTextColor(144,12,63);   // COLOR VERDE rgb(144, 12, 63)
                $fill = true;
        }else{
            
        }
        // IMPRIMIRVALORES
        $pdf->Cell($w[3],6,$JornadaLicenciaPermiso,'1',0,'C',$fill);   // VALOR TIPO LICENCIA O PERMISO
        // REESTABLECER COLOR Y FONT
        $pdf->SetTextColor(0);  // rgb(0)
        $pdf->SetFillColor(255,255,255);   // CORAL CLARO// rgb(255,255,255);
        $fill = false;
        $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
}
function CuadrosFaltantes($columnas){
    global $pdf, $fill, $w;
    // Establce un punto en media (.) si se establece el valor como una 1T (1 Tanda).
        for ($i=0; $i < $columnas ; $i++) { 
            $pdf->Cell($w[3],6,'',1,0,'C',$fill);
        }
        
}
function Punto1T(){
    global $pdf, $fillFecha, $w, $codigo_produccion, $DepartamentoEmpresa, $Jornada, $NombresCodigoDE;
    if($DepartamentoEmpresa == $NombresCodigoDE["Motorista"]){
        $link = "/acomtus/php_libs/reportes/control_tiquete_ingresos.php?codigo_produccion=" . $codigo_produccion;
    // Establce un punto en media (.) si se establece el valor como una 1T (1 Tanda).
        $pdf->SetFont('Arial','B',20); // I : Italica; U: Normal;
        $x = $pdf->GetX(); $y = $pdf->GetY();
        $pdf->Rect($x,$y,7,6,"DF");
        $pdf->Cell($w[3],3.5,'.',0,0,'C',$fillFecha, $link);        
        $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
    }else{
        // Establce un punto en media (.) si se establece el valor como una 1T (1 Tanda).
        $pdf->SetFont('Arial','B',20); // I : Italica; U: Normal;
        $x = $pdf->GetX(); $y = $pdf->GetY();
            if($DepartamentoEmpresa == $NombresCodigoDE["Mantenimiento"] ||
                $DepartamentoEmpresa == $NombresCodigoDE["Vigilancia"]){  // DIFERENCIA EL ANCHO DE LA LINEA SEGUN EL DEPARTAMENTO
                    $pdf->Rect($x,$y,6,6,"DF");
            }else{
                $pdf->Rect($x,$y,7,6,"DF");
            }
        $pdf->Cell($w[3],3.5,'.',0,0,'C',$fillFecha);        
        $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
    }

}
function JornadaExtra($DescripcionJornadaExtra){
    global $pdf, $x, $y;
    //  DESCRIPCION DEL DESCANSO
        if($DescripcionJornadaExtra != '0H'){
            $x = $pdf->GetX() -5 ; $y = $pdf->GetY() + 5.5;
            $pdf->SetFont('Arial','',5); // I : Italica; U: Normal;
                $pdf->RotatedText($x,$y,$DescripcionJornadaExtra,0);
            $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;                                    
        }
}
function JornadaExtra4H($DescripcionJornadaExtra){
    global $pdf, $x, $y;
    //  DESCRIPCION DEL DESCANSO
        if($DescripcionJornadaExtra != '0H'){
            $x = $pdf->GetX() -3 ; $y = $pdf->GetY() + 5.5;
            $pdf->SetFont('Arial','',5); // I : Italica; U: Normal;
                $pdf->RotatedText($x,$y,$DescripcionJornadaExtra,0);
            $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;                                    
        }
}
function JornadaExtraNocturnidad($DescripcionJornadaNocturna){
    global $pdf, $x, $y;
        //  IMPRIMIR NOCTURNIDAD SI EXISTE.
        if($DescripcionJornadaNocturna != '0H'){
            $x = $pdf->GetX() -5 ; $y = $pdf->GetY() + 5.5;
            $pdf->SetFont('Arial','',5); // I : Italica; U: Normal;
                $pdf->RotatedText($x,$y,$DescripcionJornadaNocturna,0);
            $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
        }
}
function VerificarControl($fecha, $codigo_personal){
    global $pdf, $dblink, $fillFecha, $fill, $codigo_produccion;
    // Armar consulta para ir a buscar con la fecha y codigo_personal 
        $query_busqueda = "SELECT * from produccion where fecha = '$fecha' and codigo_personal = '$codigo_personal'";
    // EJECUTAR CONSULTA
        $consulta_ = $dblink -> query($query_busqueda);
    // validar si existen archivos en la consulta segun la fecha.
        $count_asistencia = $consulta_ -> rowCount();        
    //  
    while($listado = $consulta_ -> fetch(PDO::FETCH_BOTH))
    {
        $codigo_produccion = $listado["id_"];
    }
    // Verificar si existen registros.
        if($consulta_ -> rowCount() != 0){
            $pdf->SetFillColor(255,255,255);    // SIN FONDO rgb(255,255,255)
            $fillFecha = false;
            $fill = true;
        }else{
            $pdf->SetFillColor(255,100,100);    // CORAL CLARO rgb(240,252,192); rgb(255,100,100)
            $fillFecha = true;
            $fill = false;
            $codigo_produccion = 0;
        }
}
function VerificarFechaDescuento($codigo_personal){
    global $FechaDDT, $dblink, $salario;
    $CodigoNombreJornadaDDT = array(); $CantidadC = 0; $CantidadF = 0; $CantidadH = 0;
   //print $codigo_personal . "<br>"; var_dump($FechaDDT);
    //
    // si es motorista catalogo_departamento-empresa 02
        $primerDias = array(); $ultimoDias = array(); $BuscarFechaInicio = array(); $BuscarFechaFin = array(); $ll = 0;
        foreach ($FechaDDT as $fecha_dd) {
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
    //
    // pasar valores unico a nuevas matrices para posterioremente buscar en dbf.
    $BuscarFechaInicio = array_merge(array_unique($primerDias));
    $BuscarFechaFin = array_merge(array_unique($ultimoDias));
    //    var_dump($BuscarFechaInicio);
    //    var_dump($BuscarFechaFin);
    // recorrer la matriz con la fecha inicio y fin
    // PARA DETERMINAR SI EXISTE:
        /*  F - FALTA.
            C - CASTIGO Ö
            4H - MAS...
        */
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
                            WHERE pa.codigo_personal = '$codigo_personal' and pa.fecha >= '$BuscarFechaInicio[$bb]' and pa.fecha <= '$BuscarFechaFin[$bb]' order by pa.fecha";
            $consulta_asistencia_buscar_db = $dblink -> query($query_asistencia_buscar_db);
            // validar si existen archivos en la consulta segun la fecha.
            $cantidad_registros = $consulta_asistencia_buscar_db -> rowCount();
            // regrear la variable conteo a 0
                $conteo_4h = 0; $descuento = 0;
            // Verificar si existen registros.
                if($consulta_asistencia_buscar_db -> rowCount() != 0 and $cantidad_registros == 7){
                    while($rows = $consulta_asistencia_buscar_db -> fetch(PDO::FETCH_BOTH))
                    {
                        $descripcion_jornada = trim($rows['descripcion_jornada']);
                        $descripcion_licencia = trim($rows['descripcion_licencia']);
                        //
                        $CodigoNombreJornadaDDT['DescripcionJornada'][] = $descripcion_jornada;
                        $CodigoNombreJornadaDDT['DescripcionLicencia'][] = $descripcion_licencia;
                    }   // LAZO WHILE
                    //
                    $CantidadH = count(array_keys($CodigoNombreJornadaDDT["DescripcionJornada"], "4H"));
                    $CantidadF = count(array_keys($CodigoNombreJornadaDDT["DescripcionLicencia"], "F"));
                    $CantidadC = count(array_keys($CodigoNombreJornadaDDT["DescripcionLicencia"], "C"));
                }   // LAZO IF....
                // PASAR A MATRIZ F - FALTA Y C - CASTIGO Y 4H MAS DE 4H'S.
                    //var_dump($CodigoNombreJornadaDDT["DescripcionJornada"]);
                    //var_dump($CodigoNombreJornadaDDT["DescripcionLicencia"]);

                    //print "<br>" . "4h " . $CantidadH;
                    //print "<br>" . "F " . $CantidadF;
                    //print "<br>" . "C " . $CantidadC;
                    // CALCULOS...
                    if($CantidadH > 1 ){
                        $salario["Descuento4HFC"] = $salario["Descuento4HFC"] + $salario["Extra4H"];
                    }
                    //
                    if($CantidadF == 1 ){
                        $salario["Descuento4HFC"] = $salario["Descuento4HFC"] + ($salario["PorDia"] * 2);
                    }
                    //var_dump($salario["Descuento4HFC"]);

                    unset($CodigoNombreJornadaDDT);
                    $CodigoNombreJornadaDDT = array();
                    $CantidadC = 0; $CantidadF = 0; $CantidadH = 0;
    } // LAZO FOR.
}
function VerificarDescuento($codigo_personal){
    global $FechaDDT;
    /*
    // si es motorista catalogo_departamento-empresa 02
        $primerDias = array(); $ultimoDias = array(); $BuscarFechaInicio = array(); $BuscarFechaFin = array(); $ll = 0;
        foreach ($FechaDDT as $fecha_dd) {
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
    // pasar valores unico a nuevas matrices para posterioremente buscar en dbf.
    $BuscarFechaInicio = array_merge(array_unique($primerDias));
    $BuscarFechaFin = array_merge(array_unique($ultimoDias));
    */
       // if($codigo_personal == "00611"){
         //   $pase++;
           // if($pase == 2){
//                print $codigo_personal . "<br>"; var_dump($FechaDDT);
                print "<br>";
               // var_dump($BuscarFechaInicio);
                print "<br>";
                //var_dump($BuscarFechaFin);
                $pase = 0;
              //  exit;
            //}
   //}
}
function RellenarSinCalculos(){
    global $salario, $pdf, $DepartamentoEmpresa, $NombresCodigoDE, $fill, $w, $CodigoNombreJornada, $NocturnaValorUnitario;
            # PRESENTAR SALARIO
            // SALARIO EN PANTALLA
                $pdf->Cell($w[1],6,'',1,0,'C',$fill);
            # PRESENTAR ASUETO.1
                $pdf->Cell($w[1],6,'',1,0,'C',$fill);
            # PRESENTAR SALARIO EXTRA
                $pdf->Cell($w[1],6,'',1,0,'C',$fill);
            # CALCULO DE NOCTURNA EN EL CASO DE VIGILANCIA, MANTENIMIENTO Y Taller.
                if($DepartamentoEmpresa == $NombresCodigoDE["Mantenimiento"] || $DepartamentoEmpresa  == $NombresCodigoDE["Vigilancia"] || $DepartamentoEmpresa  == $NombresCodigoDE["Taller"]){
                    $pdf->Cell($w[5],6,'','1',0,'C',$fill);   // Cantidad
                    $pdf->Cell($w[5],6,'','1',0,'C',$fill);   // Valor
                }
            # PRESENTAR SALARIO TOTAL EXTRA
                $pdf->Cell($w[1],6,'',1,0,'C',$fill);
            # PRESENTAR SALARIO TOTAL
                $pdf->Cell($w[1],6,'',1,0,'C',$fill);
            // linea en blanco
                $pdf->ln();

}
?>

/*********************************************************************************************************************************************************** */

    /*
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
                        WHERE pa.codigo_personal = '$codigo' and pa.fecha >= '$BuscarFechaInicio[$bb]' and pa.fecha <= '$BuscarFechaFin[$bb]' order by pa.fecha";
                $consulta_asistencia_buscar_db = $dblink -> query($query_asistencia_buscar_db);
                // validar si existen archivos en la consulta segun la fecha.
                $cantidad_registros = $consulta_asistencia_buscar_db -> rowCount();
                // regrear la variable conteo a 0
                    $conteo_4h = 0; $descuento = 0;
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
                                    // 
                                    if($descripcion_jornada == "4H"){
                                        $conteo_4h++;

                                        switch ($conteo_4h) {
                                            case '1':
                                            // $salario = $salario + (8 * $pago_diario_hora);
                                                # code...
                                                break;
                                                case (($conteo_4h >= 3) && ($conteo_4h <= 7)):
                                                    $descuento = $descuento + (4 * $pago_diario_hora);
                                                        break;
                                            default:
                                                # code...
                                                break;
                                        }
                                } // LAZO IF...;
                        }   // LAZO WHILE
                        //  CALCULAR EL SALARIO DE ESTE CODIGO DE EMPLEADO.
                        $salario = $salario - $descuento;
                        $total_salario = ($salario + $total_tiempo_extra + $asuetos);
                    }   // LAZO IF....

                    if($codigo == '001200'){
                        print "<br>Codigo: " . $codigo;
                        print "<br>Nombre Completo: " . $nombre_completo;
                        print "<br>Pago mensual: " . $pago_mensual;
                        print "<br>Pago diario: $ " . $pago_diario_hora;
                        print "<br>Descuento: $ " . $descuento;
                        print "<br>conteo 4h # " . $conteo_4h;
                        print "<br>Salario: $ " . $salario;
                        print "<br>Tiempo Extra: $ ". $total_tiempo_extra;
                        print "<br>Total Salario: $ " . $total_salario;
                        
                        print "<br>Consulta: <br>";
                        print  $query_asistencia_buscar_db;
                        exit;
                    }

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
                // regrear la variable conteo a 0
                    $conteo_4h = 0; $descuento = 0;
                // Verificar si existen registros.
                    if($consulta_asistencia_buscar_db -> rowCount() != 0 and $cantidad_registros == 7)
                    {
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
                            // 
                            if($descripcion_jornada == "4H"){
                                $conteo_4h++;

                                switch ($conteo_4h) {
                                    case '1':
                                    // $salario = $salario + (8 * $pago_diario_hora);
                                        # code...
                                        break;
                                    case (($conteo_4h >= 3) && ($conteo_4h <= 7)):
                                            $descuento = $descuento + (4 * $pago_diario_hora);
                                                break;
                                    default:
                                        # code...
                                        break;
                                    }
                                } // LAZO IF...;
                        }   // LAZO WHILE
                            //  CALCULAR EL SALARIO DE ESTE CODIGO DE EMPLEADO.
                            $total_salario = $salario + $total_tiempo_extra + $asuetos;
                    }   // LAZO IF....
        } // LAZO FOR.
    }   // lazo if para saber como buscar la fecha de descanso.
    */