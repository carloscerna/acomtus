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
//
// Establecer formato para la fecha.
//
date_default_timezone_set('America/El_Salvador');
setlocale(LC_TIME,'es_SV');
//	Hora Actual.
$hora_actual = date("h:i:s a");
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
    $CalcularDatos = $_REQUEST["chkCalcular"];
    $reporte_persona_responsable = $_REQUEST["persona_responsable"] ?? '';

    $db_link = $dblink; // Asumiendo que $dblink viene de mainFunctions_conexion.php
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
    $link = "/acomtus/php_libs/reportes/Planilla/DetallePorMotorista.php?codigo_produccion=" . $codigo_produccion;
    $codigo_cargo = "";
// Nocturnidad.
    $NocturnaValorUnitario = 0.57;
    $NocturnaCantidad = 0;
    $NocturnaValor = 0;

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
// ARMAR FECHA DEPENDIENDO DE LA QUINCENA
    $fecha_inicio_str = '';
    if($quincena == "Q1"){
        $fecha_inicio_str = $año . '-' . $fecha_mes . '-01';
    }
    if($quincena == "Q2"){
        $fecha_inicio_str = $año . '-' . $fecha_mes . '-16';
    }

// establecemos la fecha de inicio
    $inicio =  DateTime::createFromFormat('Y-m-d', $fecha_inicio_str, new DateTimeZone('America/El_Salvador'));
// establecemos la fecha final (fecha de inicio + dias que queramos)
    $fin =  clone $inicio; // Clonar para no modificar la fecha de inicio

// definir el número de días dependiendo de la quincena.
    if($quincena == "Q1"){
        $total_dias_quincena = 15;
    }
    if($quincena == "Q2"){
        $total_dias_quincena = $total_de_dias - 15;
    }
    $fin->modify('+'.$total_dias_quincena.' day');

// creamos el periodo de fechas
    $periodo = new DatePeriod($inicio, new DateInterval('P1D') ,$fin);
// Crear Matriz para el # de dia y nombre del dia. Y VARIABLES GLOBALES
//
    $nombreDia_a = array(); $numeroDia_a = array(); $fecha_periodo = array(); $FechaDDT = array();
    $FechaDescripcionAsueto = array();
    $FechaAsistencia = null; $codigo_personal_actual = 0; // Renombrar para evitar conflicto
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
// MEJORA: Cargar catálogos una sola vez y usar sentencias preparadas.
// CREAR ARRAY ASOCIATIVA DE LA TABLA: CATALOGO_DEPARTAMENTO_EMPRESA.
    $NombresCodigoDE = [];
    $stmt_de = $dblink->prepare("SELECT codigo, descripcion FROM catalogo_departamento_empresa ORDER BY codigo");
    $stmt_de->execute();
    while($listado = $stmt_de->fetch(PDO::FETCH_ASSOC)) { // Usar FETCH_ASSOC para nombres de columna
        $NombresCodigoDE[trim($listado["descripcion"])] = $listado["codigo"];
    }

// CREAR ARRAY ASOCIATIVA DE LA TABLA: CATALOGO_TIPO_LICENCIA_O_PERMISO
    $NombresCodigoLicenciaPermiso = [];
    $stmt_lp = $dblink->prepare("SELECT id_, descripcion FROM catalogo_tipo_licencia_o_permiso ORDER BY codigo");
    $stmt_lp->execute();
    while($listado = $stmt_lp->fetch(PDO::FETCH_ASSOC)) {
        $NombresCodigoLicenciaPermiso[trim($listado["descripcion"])] = $listado["id_"];
    }

// CREAR ARRAY ASOCIATIVA DE LA TABLA: asuetos
    $FechaDescripcionAsueto = ['Fecha' => [], 'Descripcion' => []];
    $stmt_asu = $dblink->prepare("SELECT fecha, descripcion FROM asuetos ORDER BY fecha");
    $stmt_asu->execute();
    while($listado = $stmt_asu->fetch(PDO::FETCH_ASSOC)) {
        $FechaDescripcionAsueto["Fecha"][] = $listado["fecha"];
        $FechaDescripcionAsueto["Descripcion"][] = trim($listado["descripcion"]);
    }

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
    $this->Cell(130,6,mb_convert_encoding("Responsable del Punteo: " . $reporte_persona_responsable,"ISO-8859-1"),0,0,"L",false);
    $this->Cell(4,6,"",0,0,"L",false);
    if($DepartamentoEmpresa == $NombresCodigoDE["Motorista"]){
        // SIN CONTROL
        $this->SetFillColor(255,100,100);   // CORAL CLARO
            $this->Cell(4,4,"",1,0,"L",true);   // cuadro
        $this->SetFillColor(255,100,100);   //RGB(255,100,100)
        $this->SetFont('Arial','B',7);
            $this->Cell(25,6,mb_convert_encoding("Sin Nº Control","ISO-8859-1"),0,0,"L",false);
        // JEFE DE LINEA
        $this->SetFillColor(208, 236, 231);   // CORAL CLARO
        $this->Cell(4,4,"",1,0,"L",true);   // cuadro
            $this->SetFillColor(176,242,194);   //RGB(176,242,194)
            $this->SetFont('Arial','B',7);
                $this->Cell(25,6,mb_convert_encoding("Jefe de línea","ISO-8859-1"),0,0,"L",false);
        // DESPACHO
        $this->SetFillColor(141,255,74);   // CORAL CLARO
        $this->Cell(4,4,"",1,0,"L",true);   // cuadro
            $this->SetFillColor(141,255,74);   //RGB(141,255,74)
            $this->SetFont('Arial','B',7);
                $this->Cell(25,6,mb_convert_encoding("Despacho","ISO-8859-1"),0,0,"L",false);
        $this->SetFont('Arial','B',9);
        // SIN PUNTEO
        $this->SetFillColor(255,255,100);   // CORAL CLARO
        $this->Cell(4,4,"",1,0,"L",true);   // cuadro
            $this->SetFillColor(235,235,164);   //RGB(235,235,164)
            $this->Cell(25,6,mb_convert_encoding("Sin Punteo","ISO-8859-1"),0,1,"L",false);
    }else{
        // SIN PUNTEO
        $this->SetFillColor(255,255,100);   // CORAL CLARO
            $this->Cell(4,4,"",1,0,"L",true);   // cuadro
        $this->SetFillColor(235,235,164);   //RGB(#ecec53)
        $this->SetFont('Arial','B',7);
            $this->Cell(25,6,mb_convert_encoding("Sin Punteo","ISO-8859-1"),0,0,"L",false);
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
            if(in_array($DepartamentoEmpresa, [$NombresCodigoDE['Mantenimiento'], $NombresCodigoDE['Vigilancia'], $NombresCodigoDE['Taller']])){
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
        if(in_array($DepartamentoEmpresa, [$NombresCodigoDE['Mantenimiento'], $NombresCodigoDE['Vigilancia'], $NombresCodigoDE['Taller']])){
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
                if(in_array($DepartamentoEmpresa, [$NombresCodigoDE['Mantenimiento'], $NombresCodigoDE['Vigilancia'], $NombresCodigoDE['Taller']])){
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
        if(in_array($DepartamentoEmpresa, [$NombresCodigoDE['Mantenimiento'], $NombresCodigoDE['Vigilancia'], $NombresCodigoDE['Taller']])){
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
    if(in_array($DepartamentoEmpresa, [$NombresCodigoDE['Mantenimiento'], $NombresCodigoDE['Vigilancia'], $NombresCodigoDE['Taller']])){
        $w=array(5,13,75,6,14,7,13,7,3); //determina el ancho de las columnas
    }else{
        $w=array(5,13,75,7,14,7,13,7,3); //determina el ancho de las columnas
    }
    $w1=array(5.66); //determina el ancho de las columnas de cada dia.
//////////////////////////////////////////////////////////////////////////////////////////
// ARMAR LA CONSULTA // DE ACUERDO AL CODIGO DEL DEPARTAMENTO EMPRESA
//////////////////////////////////////////////////////////////////////////////////////////
    $query_personal = "";
    if($DepartamentoEmpresa == $NombresCodigoDE["Motorista"]){
        $query_personal = "SELECT codigo, btrim(nombres || ' ' || apellidos) AS nombre_completo, pago_diario, salario
            FROM personal WHERE codigo_ruta = :ruta AND codigo_estatus = '01' ORDER BY codigo";
        $stmt_personal = $dblink->prepare($query_personal);
        $stmt_personal->bindParam(':ruta', $ruta, PDO::PARAM_STR);
    }else{
        $query_personal = "SELECT codigo, btrim(nombres || ' ' || apellidos) AS nombre_completo, pago_diario, salario
            FROM personal WHERE codigo_departamento_empresa = :departamento AND codigo_estatus = '01' ORDER BY codigo";
        $stmt_personal = $dblink->prepare($query_personal);
        $stmt_personal->bindParam(':departamento', $DepartamentoEmpresa, PDO::PARAM_STR);
    }
    $stmt_personal->execute();

    // OBTENER EL TOTAL DE LINEAS
    $total_lineas = $stmt_personal->rowCount();

    // determinar cual es el primer valor de la fecha y el ultimo
    $fecha_periodo_inicio = reset($fecha_periodo);
    $fecha_periodo_fin = end($fecha_periodo);

    $fill=false; $fillFecha = true; $i=1; $fillaFila = false;
        while($row = $stmt_personal->fetch(PDO::FETCH_ASSOC))
            {
                // Variables
                    $NocturnaCantidad = 0;
                // variable para verificar que tipo de permiso o días trabajados.
                    $codigo_personal_actual = trim($row['codigo']); // Usar el nombre renombrado
                    $nombre_completo = mb_convert_encoding(trim($row['nombre_completo']),"ISO-8859-1");
                //  CALCULO DEL SALARIO MENSUAL, DIARIO Y POR HORA.
                    $pago_mensual = $row['salario'];
                    $pago_diario = round($row['salario'] / 30, 10);
                    $pago_diario_hora = round($pago_diario / 8, 10);
                    $pago4Horas = round($pago_diario_hora * 4, 10);
                    $pago_diario_extra_4H = round($pago_diario_hora * 4, 10);
                    $pago_diario_extra_1T = round($pago_diario_hora * 8, 10);
                    $pago_diario_extra_1_5T = round($pago_diario_hora * 12, 10);
                // CREAR ARRAY ASOCIATIVA. SALARIO.
                    $salario = [
                        "Mensual" => $pago_mensual,
                        "PorDia" => $pago_diario,
                        "PorHora" => $pago_diario_hora,
                        "Por4Horas" => $pago4Horas,
                        "Extra4H" => $pago_diario_extra_4H,
                        "Extra1T" => $pago_diario_extra_1T,
                        "Extra15T" => $pago_diario_extra_1_5T,
                        "Nominal" => 0,
                        "Extra" => 0,
                        "TotalExtra" => 0,
                        "Total" => 0,
                        "SalarioQuincena" => round($total_dias_quincena * $pago_diario, 4),
                        "Descuento4HFC" => 0,
                        "Descuento4H" => 0,
                        "DescuentoFaltas" => 0,
                        "DescuentoCastigo" => 0,
                        "DescuentoISSS" => 0,
                        "DescuentoPP" => 0,
                        "SinPunteo" => 0,
                        "TotalDiasQuincena" => $total_dias_quincena
                    ];
                // DATOS AL INFORME
                    $pdf->SetFillColor(234, 236, 238);   // CORAL CLARO// rgb(234, 236, 238); SIN PUNTEO
                    $pdf->SetDrawColor(0,0,0);
                    $pdf->Cell($w[0],6,$i,1,0,'C',$fillaFila);        // núermo correlativo
                    $pdf->Cell($w[1],6,$codigo_personal_actual,1,0,'L',$fillaFila);   // codigo empleado
                    $pdf->SetFont('Arial','',8);
                        $pdf->Cell($w[2],6,$nombre_completo,1,0,'L',$fillaFila); // Nombre, Salario Nominal y días.
                    $pdf->SetFont('Arial','',9);
                    // ACUMULAR EL VALOR DE $I y establece el fondo de la caja de texto Cell();
                    $fillaFila=!$fillaFila;
                    $pdf->SetFillColor(255,255,255);   // CORAL CLARO// rgb(255,255,255); SIN PUNTEO
                // Rellenar los cuadros segun el numero de dias. CALCULANDO DIAS COMPLETOS, MEDIO TIEMPO, ISSS, VACACIONES.
                    rellenar($total_dias_quincena);
                // VALIDAR EL RELLENAR $I.
                    rellenar_i($i);
                // INCREMENTAR EL VALOR DE LA FILA
                    $i=$i+1;
            }
            // RELLENAR DATOS SI ES MENOR A 25 SEGUN $I
                rellenar_datos($i);
    // Salida del pdf.
        $modo = "I"; // Envia al navegador (I), Descarga el archivo (D), Guardar el fichero en un local(F).
        $print_nombre = mb_convert_encoding("Planilla: $DepartamentoEmpresaText - $quincena - $NombreMes.pdf","ISO-8859-1"); // Usar $NombreMes
        $pdf->Output($print_nombre,$modo);
/////////////////////////////////////////////////////////////////////////////////////
//************* */ FUNCIONES.*******************************************************
/////////////////////////////////////////////////////////////////////////////////////
function rellenar_i($i){
    global $pdf, $fill, $w, $w1, $header, $i, $total_dias_quincena, $total_lineas;
    // SALTO DE PAGINA QUE DEPENDE DEL NUMERO DE LINEAS.
    if($i==25 || $i == 51 || $i == 65){ // Mantener la lógica existente para los saltos de página
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
            // Rellenar las celdas de cálculo
            RellenarSinCalculos(); // Llamar a la función existente
        }
}

// MEJORA: Pasar las variables necesarias como parámetros a las funciones
function rellenar($total_dias_quincena){
    // VARIABLES GLOBALES
        global $dblink, $pdf, $salario, $w, $fill, $fecha_periodo_inicio, $fecha_periodo_fin, $codigo_personal_actual, $CalcularDatos,
            $DepartamentoEmpresa, $NombresCodigoDE, $fillFecha, $codigo_produccion, $link, $NocturnaValorUnitario, $FechaDescripcionAsueto,
            $fecha_periodo, $fillaFila;

    // VARIABLES LOCALES
        $CodigoNombreJornada = array(); // Asegurarse de que este array esté vacío al inicio de cada llamada a `rellenar`

    // BLOQUE EXPERIMENTAL, EXTRAER LOS VALORES DE CADA JORNDAD, PERMISO, ETC. Y PASARLOS A UNA MATRIZ ASOCIATIVA.
    // MEJORA: Usar sentencia preparada para la consulta de asistencia
        $query_asistencia = "SELECT pa.codigo_personal, pa.fecha, pa.codigo_jornada, cat_j.descripcion as descripcion_jornada,
                pa.codigo_tipo_licencia, cat_lp.descripcion as descripcion_licencia,
                pa.codigo_jornada_descanso, cat_jd.descripcion as descripcion_descanso,
                pa.codigo_jornada_vacaciones,  cat_jv.descripcion as descripcion_vacacion,
                pa.codigo_jornada_nocturna, cat_jn.descripcion as descripcion_nocturna,
                pa.codigo_jornada_e_4h, cat_j4.descripcion as descripcion_e_4h,
                pa.codigo_personal_encargado,
                cat_lp.horas as horas_licencia, cat_j.horas,
                pa.codigo_jornada_asueto,
                cat_ja.descripcion as descripcion_jornada_asueto
                    FROM personal_asistencia pa
                        INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = pa.codigo_jornada
                        INNER JOIN catalogo_jornada cat_jd ON cat_jd.id_ = pa.codigo_jornada_descanso
                        INNER JOIN catalogo_jornada cat_jv ON cat_jv.id_ = pa.codigo_jornada_vacaciones
                        INNER JOIN catalogo_jornada cat_j4 ON cat_j4.id_ = pa.codigo_jornada_e_4h
                        INNER JOIN catalogo_jornada cat_jn ON cat_jn.id_ = pa.codigo_jornada_nocturna
                        INNER JOIN catalogo_jornada cat_ja ON cat_ja.id_ = pa.codigo_jornada_asueto
                        INNER JOIN catalogo_tipo_licencia_o_permiso cat_lp ON cat_lp.id_ = pa.codigo_tipo_licencia
                            WHERE pa.codigo_personal = :codigo_personal AND pa.fecha BETWEEN :fecha_inicio AND :fecha_fin
                                ORDER BY pa.fecha";

    $stmt_asistencia = $dblink->prepare($query_asistencia);
    $stmt_asistencia->bindParam(':codigo_personal', $codigo_personal_actual, PDO::PARAM_STR);
    $stmt_asistencia->bindParam(':fecha_inicio', $fecha_periodo_inicio, PDO::PARAM_STR);
    $stmt_asistencia->bindParam(':fecha_fin', $fecha_periodo_fin, PDO::PARAM_STR);
    $stmt_asistencia->execute();

    // validar si existen archivos en la consulta segun la fecha.
    $count_asistencia = $stmt_asistencia->rowCount();

    // Verificar si existen registros.
    if($count_asistencia > 0){
        while($listado = $stmt_asistencia->fetch(PDO::FETCH_ASSOC))
            {
                // varloes para las matrices asociativas
                    $CodigoNombreJornada['DescripcionJornada'][] = trim($listado['descripcion_jornada']);
                    $CodigoNombreJornada['DescripcionLicencia'][] = trim($listado['descripcion_licencia']);
                    $CodigoNombreJornada['DescripcionDescanso'][] = trim($listado['descripcion_descanso']);
                    $CodigoNombreJornada['DescripcionVacacion'][] = trim($listado['descripcion_vacacion']);
                    $CodigoNombreJornada['DescripcionNocturna'][] = trim($listado['descripcion_nocturna']);
                    $CodigoNombreJornada['DescripcionExtra4H'][] = trim($listado['descripcion_e_4h']);
                    $CodigoNombreJornada['DescripcionAsueto'][] = trim($listado['descripcion_jornada_asueto']);
                    $CodigoNombreJornada['FechaAsistencia'][] = trim($listado['fecha']);
            }   // WHILE QUE RECORRE LA CONSULTA, CUANDO HAY REGISTROS.
                $fila_array = 0;
                foreach ($CodigoNombreJornada['DescripcionJornada'] as $valor => $Jornada)
                {
                    // VALIDAR SI EXISTE NUMERO DE CONTROL CON FECHA Y CODIGO PERSONAL.
                        $FechaAsistencia = $CodigoNombreJornada["FechaAsistencia"][$fila_array];
                        if($DepartamentoEmpresa == $NombresCodigoDE["Motorista"]){
                            VerificarControl($FechaAsistencia, $codigo_personal_actual);
                        }
                    // REVISAR SI ANTES HAY DESCANSO EN ASUETO.
                        $JornadaLicenciaPermiso = $CodigoNombreJornada["DescripcionLicencia"][$fila_array]; // VARIABLES CUANDO ES DIFERENTE DE 1T. (1 TANDA)
                            if($JornadaLicenciaPermiso != "TDA" && $JornadaLicenciaPermiso != "DA"){
                            // REVISAR SI LA FECHA ES DE ASUETO.
                                $buscar_asueto = array_search($CodigoNombreJornada["FechaAsistencia"][$fila_array], $FechaDescripcionAsueto['Fecha']);
                                if($buscar_asueto !== false){
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
                            if($JornadaLicenciaPermiso == "P"){
                                $JornadaLicenciaPermiso = "SP";
                            }
                            // CAMBIAR EL COLOR
                            // VERDE: TV, TD, V, D, TDA.
                            // AZUL: PP, ISSS.
                            // ROJO: F Y C.
                            // VERIFICAR Y ASIGNAR A VARIABLE LA FECHA DE DESCANSO "D" Y TRABAJO DESCANSO "DT"
                                CambiarJornadaColor($JornadaLicenciaPermiso, $FechaAsistencia, $codigo_personal_actual);
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
                                    case 'A':  // descanso asueto
                                        // Revisar si hay jornada Extra TRABAJO VACACION
                                        $JornadaCodigoExtra = $CodigoNombreJornada["DescripcionAsueto"][$fila_array];
                                            JornadaExtra($JornadaCodigoExtra);
                                    break;
                                    case 'TA':  // descanso asueto
                                        // Revisar si hay jornada Extra TRABAJO VACACION
                                        $JornadaCodigoExtra = $CodigoNombreJornada["DescripcionAsueto"][$fila_array];
                                            JornadaExtra($JornadaCodigoExtra);
                                    break;
                                    case 'DA':  // descanso asueto
                                        // Revisar si hay jornada Extra TRABAJO VACACION
                                        $JornadaCodigoExtra = $CodigoNombreJornada["DescripcionAsueto"][$fila_array];
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
                                    CambiarJornadaColor($JornadaAsueto, $FechaAsistencia, $codigo_personal_actual);
                                    // Revisar si hay jornada Extra TRABAJO VACACION
                                        $JornadaCodigoExtra = $CodigoNombreJornada["DescripcionAsueto"][$fila_array];
                                            JornadaExtra($JornadaCodigoExtra);
                        break;
                        case "VACIO":
                            // rellenar con valores según consulta.
                            if(in_array($DepartamentoEmpresa, [$NombresCodigoDE['Mantenimiento'], $NombresCodigoDE['Vigilancia'], $NombresCodigoDE['Taller']])){
                                $pdf->Cell($w[3],6,'','1',0,'C',$fill);
                            }else{
                                $pdf->Cell($w[7],6,'','1',0,'C',$fill);
                            }
                        break;
                        default:
                            if($DepartamentoEmpresa == $NombresCodigoDE["Motorista"]){
                                $link = "/acomtus/php_libs/reportes/Planilla/DetallePorMotorista.php?codigo_produccion=" . $codigo_produccion;
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
                                // Revisar si hay LICENCIA O PERMISO EN 4H. (C,F,PP,ISSS)
                                    $JornadaCodigoExtra = $CodigoNombreJornada["DescripcionLicencia"][$fila_array];
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
                // MEJORA: Unificar los conteos de extras para evitar repeticiones y errores de lógica.
                $ValoresCount = [
                    'CantidadDescripcionJornada' => array_count_values($CodigoNombreJornada['DescripcionJornada'] ?? []),
                    'CantidadDescripcionDescanso' => array_count_values($CodigoNombreJornada['DescripcionDescanso'] ?? []),
                    'CantidadDescripcion4H' => array_count_values($CodigoNombreJornada['DescripcionExtra4H'] ?? []),
                    'CantidadDescripcionAsueto' => array_count_values($CodigoNombreJornada['DescripcionAsueto'] ?? []),
                    'CantidadDescripcionVacacion' => array_count_values($CodigoNombreJornada['DescripcionVacacion'] ?? [])
                ];

                // Calcular extras
                $salario["Extra"] = 0; // Resetear antes de calcular
                foreach ($ValoresCount as $key => $counts) {
                    foreach ($counts as $jornada_tipo => $cantidad) {
                        switch ($jornada_tipo) {
                            case "4H":
                                $salario["Extra"] += ($salario["Extra4H"] * $cantidad);
                                break;
                            case "1T":
                                $salario["Extra"] += ($salario["Extra1T"] * $cantidad);
                                break;
                            case "1.5T":
                                $salario["Extra"] += ($salario["Extra15T"] * $cantidad);
                                break;
                        }
                    }
                }
            // IMPRIMIR EN PANTALLA SALARIO QUINCENA
            //
                $pdf->Cell($w[8],7,'','L',0,'C',false);    // ES LA DIVISIÓN ENTRO EL TOTAL DE DIAS Y LOS CALCULOS (SALARIO, ASUETOS, EXTRA, TOTAL-EXTRA, TOTAL)
            // VERIFICAR EL DESCUENTO.
                VerificarFechaDescuento($codigo_personal_actual);
            //  CONDICIONAR PARA RELLENAR CON DATOS O SIN DATOS DE CALCULO.
                if($CalcularDatos == "si"){
                    $pdf->SetFillColor(234, 236, 238);   // CORAL CLARO// rgb(234, 236, 238); SIN PUNTEO
                    $fillaFila=!$fillaFila;
                    # PRESENTAR SALARIO
                    // VERIFICAR SI ESTA TODA LA ASISTENCIA COMPLETA, PARA DAR UN BUEN DATO DE SALARIO.
                       $salario["SalarioQuincena"] = $salario["SalarioQuincena"] - ($salario["PorDia"] * ($total_dias_quincena - $count_asistencia));
                    // CON EL DESCUENTO
                        $salario["SalarioQuincena"] = $salario["SalarioQuincena"] - $salario["Descuento4HFC"];
                    // SALARIO EN PANTALLA
                        $salario_pantalla = number_format($salario["SalarioQuincena"],2,'.',',');
                        $pdf->SetTextColor(72,61,139);   // COLOR AZUL OSCURO rgb(72,61,139)
                            $pdf->Cell($w[1],6,'$' . $salario_pantalla,1,0,'C',$fillaFila);
                            $pdf->SetTextColor(0,0,0);   // COLOR NEGRO rgb(0,0,0)
                    # PRESENTAR ASUETO.1
                        $asueto_pantalla = "";
                        $pdf->Cell($w[1],6,$asueto_pantalla,1,0,'C',$fillaFila);
                    # PRESENTAR SALARIO EXTRA
                        $salario_extra_pantalla = number_format($salario["Extra"],2,'.',',');
                        $pdf->Cell($w[1],6,'$' . $salario_extra_pantalla,1,0,'C',$fillaFila);
                    # CALCULO DE NOCTURNA EN EL CASO DE VIGILANCIA, MANTENIMIENTO Y Taller.
                        if(in_array($DepartamentoEmpresa, [$NombresCodigoDE["Mantenimiento"], $NombresCodigoDE["Vigilancia"], $NombresCodigoDE["Taller"]])){
                            $CantidadNocturnidad = count(array_keys($CodigoNombreJornada["DescripcionNocturna"] ?? [], "N"));
                            $NocturnaValor = round($CantidadNocturnidad * $NocturnaValorUnitario,2);
                            $SalidaPantallaNocturnaValor = number_format($NocturnaValor,2,'.',',');
                            $pdf->Cell($w[5],6,$CantidadNocturnidad,'1',0,'C',$fillaFila);   // Cantidad
                            $pdf->Cell($w[5],6,$SalidaPantallaNocturnaValor,'1',0,'C',$fillaFila);   // Valor
                            $salario["Extra"] = $salario["Extra"] + $NocturnaValor; // Incrementar el valor de Total Extra.
                        }
                    # PRESENTAR SALARIO TOTAL EXTRA
                        $salario["TotalExtra"] = $salario["Extra"];
                        $salario_total_extra_pantalla = number_format($salario["TotalExtra"],2,'.',',');
                        $pdf->Cell($w[1],6,'$' . $salario_total_extra_pantalla,1,0,'C',$fillaFila);
                    # PRESENTAR SALARIO TOTAL
                        $pdf->SetFont('Arial','B',8); // I : Italica; U: Normal;
                        $pdf->SetTextColor(72,61,139);   // COLOR AZUL OSCURO rgb(72,61,139)
                            $salario["Total"] = $salario["SalarioQuincena"] + $salario["TotalExtra"];
                            $salario_total_pantalla = number_format($salario["Total"],2,'.',',');
                            $pdf->Cell($w[1],6,'$' . $salario_total_pantalla,1,0,'C',$fillaFila);
                        $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
                        $pdf->SetTextColor(0,0,0);   // COLOR NEGRO rgb(0,0,0)
                        $pdf->SetFillColor(255,255,255);   // CORAL CLARO// rgb(255,255,255); SIN PUNTEO
                        $fillaFila=!$fillaFila;
                    // linea en blanco
                        $pdf->ln();
                }else{
                    RellenarSinCalculos();
                }
            // VACIAR LA VARIABLE DE LA FECHA PARA EL DESCUENTO
                unset($FechaDDT);
                unset($salario);
        }
        else{
            /// RELLENAR CON VALRIOS IS HACEN FALTA
                $columnas = $total_dias_quincena;
                CuadrosFaltantes($columnas);
            // linea en blanco
                $pdf->ln();
                RellenarSinCalculos();
        }
}
/////////////////////////////////////////////////////////////////////////////////////
//************* */ FUNCIONES.*******************************************************
/////////////////////////////////////////////////////////////////////////////////////
function CambiarJornadaColor($JornadaLicenciaPermiso, $Fecha, $codigo_personal){
    global $pdf, $fill, $w, $salario, $FechaDDT;
        // CAMBIAR TAMAÑO
        $pdf->SetFont('Arial','B',8); // I : Italica; U: Normal;
        if($JornadaLicenciaPermiso == "PP" || $JornadaLicenciaPermiso == "ISSS" || $JornadaLicenciaPermiso == "SP"){
            $pdf->SetTextColor(0,0,255); // COLOR azul rgb(0,0,255)
            if($JornadaLicenciaPermiso == "SP"){
                $pdf->SetFillColor(235,235,164);   // CORAL CLARO// rgb(235,235,164); SIN PUNTEO
                $pdf->SetTextColor(0,0,0);   // COLOR VERDE rgb(0,0,0)
                $fill = true;
                // Acumular el valor de un día. "Descuento"
                $salario["SinPunteo"] += $salario["PorDia"];
            }
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
        if(in_array($JornadaLicenciaPermiso, ["A", "TDA", "DA", "TA", "VDA"])){
            // Acumular la Fecha ASistencia para luego verificar F, C, para el descuento del septimo.
                if($JornadaLicenciaPermiso == "TDA" || $JornadaLicenciaPermiso == "DA"){
                    $FechaDDT[] = $Fecha;
                }
            // MARCAR EL ASUETO CON UN COLOR.
                $pdf->SetFillColor(255, 255, 100);   // CORAL CLARO// rgb(255, 255, 100);
                $pdf->SetTextColor(144,12,63);   // COLOR VERDE rgb(144, 12, 63)
                $fill = true;
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
            global $DepartamentoEmpresa, $NombresCodigoDE;
            if(in_array($DepartamentoEmpresa, [$NombresCodigoDE['Mantenimiento'], $NombresCodigoDE['Vigilancia'], $NombresCodigoDE['Taller']])){
                $pdf->Cell($w[3],6,'',1,0,'C',$fill);
            } else {
                $pdf->Cell($w[7],6,'',1,0,'C',$fill);
            }
        }

}
function Punto1T(){
    global $pdf, $fillFecha, $w, $codigo_produccion, $DepartamentoEmpresa, $NombresCodigoDE, $link;
    if($DepartamentoEmpresa == $NombresCodigoDE["Motorista"]){
        $link = "/acomtus/php_libs/reportes/Planilla/DetallePorMotorista.php?codigo_produccion=" . $codigo_produccion;
    // Establce un punto en media (.) si se establece el valor como una 1T (1 Tanda).
        $pdf->SetDrawColor(0,0,0);
        $pdf->SetFont('Arial','B',20); // I : Italica; U: Normal;
        $x = $pdf->GetX(); $y = $pdf->GetY();
        $pdf->Rect($x,$y,7,6,"DF");
        $pdf->Cell($w[3],3.5,'.','LTR',0,'C',$fillFecha, $link);
        $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
    }else{
        // Establce un punto en media (.) si se establece el valor como una 1T (1 Tanda).
        $pdf->SetFont('Arial','B',20); // I : Italica; U: Normal;
        $x = $pdf->GetX(); $y = $pdf->GetY();
            if(in_array($DepartamentoEmpresa, [$NombresCodigoDE["Mantenimiento"], $NombresCodigoDE["Vigilancia"]])){
                    $pdf->Rect($x,$y,6,6,"DF");
            }else{
                $pdf->Rect($x,$y,7,6,"DF");
            }
        $pdf->Cell($w[3],3.5,'.','LTR',0,'C',$fillFecha);
        $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
    }

}
function JornadaExtra($DescripcionJornadaExtra){
    global $pdf;
    //  DESCRIPCION DEL DESCANSO
        if($DescripcionJornadaExtra != '0H'){
            $x = $pdf->GetX() -5 ; $y = $pdf->GetY() + 5.5;
            $pdf->SetFont('Arial','',5); // I : Italica; U: Normal;
                $pdf->RotatedText($x,$y,$DescripcionJornadaExtra,0);
            $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
        }
}
function JornadaExtra4H($DescripcionJornadaExtra){
    global $pdf;
    //  DESCRIPCION DEL DESCANSO
        if($DescripcionJornadaExtra != '0H' && $DescripcionJornadaExtra != 'P'){
            $x = $pdf->GetX() -3 ; $y = $pdf->GetY() + 5.5;
            $pdf->SetFont('Arial','',4); // I : Italica; U: Normal;
                $pdf->RotatedText($x,$y,$DescripcionJornadaExtra,0);
            $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
        }
}
function JornadaExtraNocturnidad($DescripcionJornadaNocturna){
    global $pdf;
        //  IMPRIMIR NOCTURNIDAD SI EXISTE.
        if($DescripcionJornadaNocturna != '0H'){
            $x = $pdf->GetX() -5 ; $y = $pdf->GetY() + 5.5;
            $pdf->SetFont('Arial','',5); // I : Italica; U: Normal;
                $pdf->RotatedText($x,$y,$DescripcionJornadaNocturna,0);
            $pdf->SetFont('Arial','',8); // I : Italica; U: Normal;
        }
}
function VerificarControl($fecha, $codigo_personal){
    global $pdf, $dblink, $fillFecha, $fill, $codigo_produccion, $codigo_cargo;
    // Armar consulta para ir a buscar con la fecha y codigo_personal
        $query_busqueda = "SELECT id_ FROM produccion WHERE fecha = :fecha AND codigo_personal = :codigo_personal";
    // EJECUTAR CONSULTA
        $stmt_busqueda = $dblink->prepare($query_busqueda);
        $stmt_busqueda->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt_busqueda->bindParam(':codigo_personal', $codigo_personal, PDO::PARAM_STR);
        $stmt_busqueda->execute();

    //
    $result = $stmt_busqueda->fetch(PDO::FETCH_ASSOC);

    // Verificar si existen registros.
        if($result){
            $codigo_produccion = $result["id_"];
            $pdf->SetFillColor(255,255,255);
            $fillFecha = false;
            $fill = true;
        }else{
            $query_busqueda_p = "SELECT p.codigo_cargo FROM personal p WHERE p.codigo = :codigo_personal";
            // EJECUTAR CONSULTA
                $stmt_busqueda_p = $dblink->prepare($query_busqueda_p);
                $stmt_busqueda_p->bindParam(':codigo_personal', $codigo_personal, PDO::PARAM_STR);
                $stmt_busqueda_p->execute();
                $cargo_result = $stmt_busqueda_p->fetch(PDO::FETCH_ASSOC);

            if($cargo_result){
                $codigo_cargo = $cargo_result["codigo_cargo"];

                if($codigo_cargo == "32"){
                    $pdf->SetFillColor(255,100,100);
                    $fillFecha = true;
                    $fill = false;
                    $codigo_produccion = 0;
                }
                if($codigo_cargo == "28"){
                    $pdf->SetFillColor(208, 236, 231);
                    $fillFecha = true;
                    $fill = false;
                    $codigo_produccion = 0;
                }
                if($codigo_cargo == "17"){
                    $pdf->SetFillColor(141,255,74);
                    $fillFecha = true;
                    $fill = false;
                    $codigo_produccion = 0;
                }
            } else {
                // Si no se encuentra el cargo, establecer valores por defecto
                $pdf->SetFillColor(255,255,255);
                $fillFecha = false;
                $fill = true;
                $codigo_produccion = 0;
            }
        }
}
function VerificarFechaDescuento($codigo_personal){
    global $FechaDDT, $dblink, $salario, $fecha_periodo_inicio, $fecha_periodo_fin;

    $FechaDDT_local = $FechaDDT;
    $FechaDDT = [];

    // Generar los bloques de semanas
    $semanas = [];
    $current_date = new DateTime($fecha_periodo_inicio);
    $end_date_period = new DateTime($fecha_periodo_fin);

    while ($current_date <= $end_date_period) {
        $start_of_week = clone $current_date;
        $end_of_week = clone $current_date;
        $end_of_week->modify('+6 days');

        // Asegurarse de que el final de la semana no exceda el final del periodo
        if ($end_of_week > $end_date_period) {
            $end_of_week = $end_date_period;
        }
        $semanas[] = [
            'inicio' => $start_of_week->format('Y-m-d'),
            'fin' => $end_of_week->format('Y-m-d')
        ];
        $current_date->modify('+7 days');
    }

    foreach ($semanas as $semana) {
        $fecha_semana_inicio = $semana['inicio'];
        $fecha_semana_fin = $semana['fin'];

        // Consulta de asistencia por semana
        $queryAsistenciaPorSemana = "SELECT pa.fecha, pa.codigo_jornada, pa.codigo_tipo_licencia,
                cat_j.descripcion AS descripcion_jornada,
                cat_lp.descripcion AS descripcion_licencia
            FROM personal_asistencia pa
            INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = pa.codigo_jornada
            INNER JOIN catalogo_tipo_licencia_o_permiso cat_lp ON cat_lp.id_ = pa.codigo_tipo_licencia
            WHERE pa.codigo_personal = :codigo_personal
                AND pa.fecha BETWEEN :fecha_semana_inicio AND :fecha_semana_fin
            ORDER BY pa.fecha";

        $stmtAsistenciaPorSemana = $dblink->prepare($queryAsistenciaPorSemana);
        $stmtAsistenciaPorSemana->bindParam(':codigo_personal', $codigo_personal, PDO::PARAM_STR);
        $stmtAsistenciaPorSemana->bindParam(':fecha_semana_inicio', $fecha_semana_inicio, PDO::PARAM_STR);
        $stmtAsistenciaPorSemana->bindParam(':fecha_semana_fin', $fecha_semana_fin, PDO::PARAM_STR);
        $stmtAsistenciaPorSemana->execute();

        $dias_asistencia_semana = [];
        while ($row = $stmtAsistenciaPorSemana->fetch(PDO::FETCH_ASSOC)) {
            $dias_asistencia_semana[] = $row;
        }

        // Procesar los datos de asistencia para esta semana
        $jornadas_semana = array_column($dias_asistencia_semana, 'descripcion_jornada');
        $licencias_semana = array_column($dias_asistencia_semana, 'descripcion_licencia');

        // CALCULO PARA EL DESCUENTO, CUANDO SOLO HA TRABAJADO 4HORAS
        $cantidad4Horas = count(array_keys($jornadas_semana, "4H"));
        if($cantidad4Horas > 1){
            $salario["Descuento4H"] += ($salario["Por4Horas"] * ($cantidad4Horas - 1));
        }

        // CALCULO PARA LAS FALTAS, CASTIGO, ISSS Y PP.
        $cantidadFaltas = count(array_keys($licencias_semana, "F"));
        $cantidadCastigo = count(array_keys($licencias_semana, "C"));
        $cantidadISSS = count(array_keys($licencias_semana, "ISSS"));
        $cantidadPP = count(array_keys($licencias_semana, "PP"));

        // CÁLCULOS POR LAS FALTAS.
        if($cantidadFaltas == 1){
            $salario["DescuentoFaltas"] += $salario["PorDia"] * 2;
        }elseif($cantidadFaltas >= 2){
            $salario["DescuentoFaltas"] += $salario["PorDia"] * (1 + $cantidadFaltas);
        }
        // CÁLCULO PARA LOS CASTIGOS.
        if($cantidadCastigo >= 1){
            $salario["DescuentoCastigo"] += $cantidadCastigo * $salario["PorDia"];
        }
        // CÁLCULO PARA LOS ISSS.
        if($cantidadISSS >= 1){
            $salario["DescuentoISSS"] += $cantidadISSS * $salario["PorDia"];
        }
        // CÁLCULO PARA LOS PP.
        if($cantidadPP >= 1){
            $salario["DescuentoPP"] += $cantidadPP * $salario["PorDia"];
        }
    }

    /// PASAR EL DATO DE DESCUENTOS A SALARIO["$DESCUENTO4HFC"].
    $salario["Descuento4HFC"] = $salario["Descuento4H"] + $salario["DescuentoFaltas"] + $salario["DescuentoCastigo"] + $salario["DescuentoISSS"] + $salario["DescuentoPP"] + $salario["SinPunteo"];
}
function RellenarSinCalculos(){
    global $salario, $pdf, $DepartamentoEmpresa, $NombresCodigoDE, $fill, $w;
            # PRESENTAR SALARIO
            // SALARIO EN PANTALLA
                $pdf->Cell($w[1],6,'',1,0,'C',$fill);
            # PRESENTAR ASUETO.1
                $pdf->Cell($w[1],6,'',1,0,'C',$fill);
            # PRESENTAR SALARIO EXTRA
                $pdf->Cell($w[1],6,'',1,0,'C',$fill);
            # CALCULO DE NOCTURNA EN EL CASO DE VIGILANCIA, MANTENIMIENTO Y Taller.
                if(in_array($DepartamentoEmpresa, [$NombresCodigoDE["Mantenimiento"], $NombresCodigoDE["Vigilancia"], $NombresCodigoDE["Taller"]])){
                    $pdf->Cell($w[5],6,'','1',0,'C',$fill);
                    $pdf->Cell($w[5],6,'','1',0,'C',$fill);
                }
            # PRESENTAR SALARIO TOTAL EXTRA
                $pdf->Cell($w[1],6,'',1,0,'C',$fill);
            # PRESENTAR SALARIO TOTAL
                $pdf->Cell($w[1],6,'',1,0,'C',$fill);
            // linea en blanco
                $pdf->ln();
}