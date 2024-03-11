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
    $codigo_produccion = $_REQUEST["codigo_produccion"];
	$totalProduccionOK = 0;
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
	$año = $hoy["year"];    // año
//	$total_de_dias = cal_days_in_month(CAL_GREGORIAN, (int)$fecha_mes, $año);
	//$NombreMes = $meses[(int)$fecha_mes - 1];///8
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
	$w=array(25,25,10,20,20,20,20); // correlativo, serie, cola, desde, ingreso. estatus
    $w2=array(6,12); //determina el ALTO de las columnas
	$this->SetXY(30,95);
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
    $header=array('Correlativo','Estatus','Serie','Cola','Desde','Hasta','Ingreso');	
//Títulos de las columnas
    $pdf->AliasNbPages();
    $pdf->AddPage();
// Aqui mandamos texto a imprimir o al documento.
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetY(20);
    $pdf->SetX(20);
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetFont('Arial','',9); // I : Italica; U: Normal;
//  mostrar los valores de la consulta
    $w=array(25,25,10,20,20,20,20); // correlativo, serie, cola, desde, ingreso. estatus
	$w1 = array(55);
    $w2=array(5,7); //determina el ALTO de las columnas
// Variables.
    $fill = false; $fila=1; $totalIngreso = 0; $totalIngresoOK = 0;
//  Encabezando.
    $pdf->FancyTable($header); // Solo carge el encabezado de la tabla porque medaba error el cargas los datos desde la consulta.		
//
    $query = "SELECT p.id_ AS id_produccion, p.fecha, p.codigo_inventario_tiquete, p.total_ingreso, per.codigo_genero,
                cat_ts.descripcion as nombre_serie, 
                pa.id_ as id_produccion_asignado, pa.tiquete_desde, pa.tiquete_hasta, pa.total, pa.procesado, pa.cantidad, pa.total, pa.codigo_estatus, pa.tiquete_cola,
                btrim(cat_j.descripcion || CAST(': ' AS VARCHAR) || cat_j.hora_desde || CAST(' - ' AS VARCHAR) || cat_j.hora_hasta) as descripcion_jornada, 
                cat_r.descripcion as descripcion_ruta,
                it.precio_publico,
                cat_e.descripcion as descripcion_estatus,
                btrim(per.nombres || CAST(' ' AS VARCHAR) || per.apellidos) as nombre_motorista, per.codigo as codigo_personal, per.foto, per.codigo_genero,
                cat_t_c.numero_placa as numero_placa, cat_t_c.numero_equipo as numero_equipo
                    FROM produccion p 
                        INNER JOIN personal per ON per.codigo = p.codigo_personal
                        INNER JOIN produccion_asignado pa ON pa.codigo_produccion = p.id_ 
                        INNER JOIN inventario_tiquete it ON it.id_ = p.codigo_inventario_tiquete 
                        INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie 
                        INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = p.codigo_jornada 
                        INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = p.codigo_ruta 
                        INNER JOIN transporte_colectivo cat_t_c ON cat_t_c.id_ = p.codigo_transporte_colectivo
                        INNER JOIN catalogo_estatus cat_e ON cat_e.codigo = pa.codigo_estatus
                            WHERE pa.codigo_produccion = '$codigo_produccion'
                            ORDER BY pa.id_, p.codigo_inventario_tiquete";
        $consulta = $dblink -> query($query);
    // Validar si hay registros.
    if($consulta -> rowCount() != 0){  
    // obtener el último dato en este caso el Id_
    while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
    {
        $id_pro_a = trim($listado['id_produccion_asignado']);		// dato de la tabla produccion.
        $pa_codigo_produccion = trim($listado['id_produccion']);    // dato de la tabla produccion_asignacion
        $nombre_serie = trim($listado['nombre_serie']);		// produccion correlativo.
        $tiquete_cola = trim($listado['tiquete_cola']);   // produccion correlativo
        $tiquete_desde = trim($listado['tiquete_desde']);   // produccion correlativo
        $tiquete_hasta = trim($listado['tiquete_hasta']);   // produccion correlativo
        $total_ingreso = trim($listado['total']);
        $total_ingreso_control = trim($listado['total_ingreso']);
        $cantidad = trim($listado['cantidad']);
        $fecha = cambiaf_a_normal(trim($listado['fecha']));
        $precio_publico = trim($listado['precio_publico']);
        $precio_publico_ = trim($listado['precio_publico']);
        $procesado = trim($listado['procesado']);
        $codigo_estatus = trim($listado['codigo_estatus']);
        $descripcion_estatus = trim($listado['descripcion_estatus']);
        $descripcion_ruta_rg = trim($listado['descripcion_ruta']);
        $NombreMotorista = trim($listado['nombre_motorista']);
        $codigo_personal = trim($listado['codigo_personal']);
        $Foto = trim($listado['foto']);
        $codigo_genero = trim($listado['codigo_genero']);
        $numero_equipo = trim($listado['numero_equipo']);
        $numero_placa = trim($listado['numero_placa']);
        $codigo_genero = trim($listado['codigo_genero']);
        $estilo = ""; // definimos el estilo de cada elmento encontrado en codigo_esttratus.
        // cantidad de tiquetes.
            $cantidad_tiquete_vendido = round($total_ingreso_control / $precio_publico,0);
        // encabezados.
        if($fila == 1){
            //	default imagen masculino
                $ImagenFoto = '/acomtus/img/avatar_masculino.png';
            // validar
                if(is_null($Foto) || $Foto == "" || $Foto == " "){
                    if($codigo_genero == '02'){	//	femenino
                        $ImagenFoto = '/acomtus/img/avatar_femenino.png';
                    }
                }else{
                    // foto del empleado.
                    $ImagenFoto = "/acomtus/img/fotos/".$Foto;
                }
            // Diseño de Lineas y Rectangulos.
            $pdf->SetFillColor(224);
            $pdf->SetFont('Arial','B',13); // I : Italica; U: Normal;
            // FECHA.
            $pdf->RotatedText(120,40,'Santa Ana, ' . $dia . ' de ' . $nombresMeses[$mes] . ' de ' . $año,0);
            // estado de cuenta
            $pdf->RoundedRect(15, 45, 140, 8, 2, '1234', 'DF');
            $pdf->RotatedText(18,50,mb_convert_encoding('EMPLEADO: ' . $NombreMotorista,"ISO-8859-1","UTF-8"),0);
            $pdf->SetFont('Arial','',9); // I : Italica; U: Normal;
            // Definimos el tipo de fuente, estilo y tamaño.
            $pdf->SetXY(30,55);
            //	INFORMACIÓN DEL EMPLEADO.
            //Logo
            $img = $_SERVER['DOCUMENT_ROOT'].$ImagenFoto;
            $pdf->Image($img,30,55,30,35);
            $pdf->SetX(70);
            $pdf->Cell($w1[0],$w2[1],mb_convert_encoding("Código: ","ISO-8859-1","UTF-8") . $codigo_personal,1,0,'L',$fill);
            $pdf->SetX(70+$w1[0]+10);
            $pdf->Cell($w1[0],$w2[1],mb_convert_encoding("Precio Público: $ ","ISO-8859-1","UTF-8") . $precio_publico,1,1,'L',$fill);
            $pdf->SetX(70);
            $pdf->Cell($w1[0],$w2[1],"Ruta: " . $descripcion_ruta_rg,1,0,'L',$fill);
            $pdf->SetX(70+$w1[0]+10);
            $pdf->Cell($w1[0],$w2[1],"Cantidad Vendida: " . $cantidad_tiquete_vendido,1,1,'L',$fill);
            $pdf->SetX(70);
            $pdf->Cell($w1[0],$w2[1],"Unidad: " . $numero_equipo . " | " . $numero_placa,1,0,'L',$fill);
            $pdf->SetX(70+$w1[0]+10);
            $pdf->Cell($w1[0],$w2[1],"Total: $ " . $total_ingreso_control,1,1,'L',$fill);
            $pdf->SetX(70);
            $pdf->Cell($w1[0],$w2[1],"Fecha: " . $fecha,1,1,'L',$fill);
            $pdf->SetXY(30,100);
        }
        // TOTAL INGRESO
            $totalIngresoOK = $totalIngresoOK + $total_ingreso;
            $totalIngresoOKPantalla = $totalIngresoOK;
        // DAR VALORES A VARIABLES.
		// RECORRER LA ARRAY
            $pdf->SetX(30);
            // cambiar el color de la fila.
            $pdf->Cell($w[0],$w2[1],$pa_codigo_produccion."-".$id_pro_a,1,0,'C',$fill);
        // cambiar color al estatus 04= Devolución , y 05= Vendido.
            if($codigo_estatus == "04"){
                // Diseño de Lineas y Rectangulos.
                $pdf->SetTextColor(199, 0, 57);
                $pdf->Cell($w[1],$w2[1],mb_convert_encoding($descripcion_estatus,"ISO-8859-1","UTF-8"),1,0,'L',$fill);
                $pdf->SetTextColor(0);
            }
            if($codigo_estatus == "05"){
                $pdf->SetTextColor(0, 43, 255);
                $pdf->Cell($w[1],$w2[1],mb_convert_encoding($descripcion_estatus,"ISO-8859-1","UTF-8"),1,0,'L',$fill);
                $pdf->SetTextColor(0);
            }
            $pdf->Cell($w[2],$w2[1],$nombre_serie,1,0,'C',$fill);
            // cambiar de color a la cola
            if($tiquete_cola > 0){
                $pdf->SetTextColor(0, 43, 255); // rgb(255, 87, 51)
            }else{
                $pdf->SetTextColor(255,255,255); // rgb(255,255,255)
            }
            $pdf->Cell($w[3],$w2[1],$tiquete_cola,1,0,'C',$fill);
            $pdf->SetTextColor(0);  // rgb(0,0,0)
            $pdf->Cell($w[4],$w2[1],$tiquete_desde,1,0,'C',$fill);
            $pdf->Cell($w[5],$w2[1],$tiquete_hasta,1,0,'C',$fill);
            $pdf->Cell($w[6],$w2[1],"$".$total_ingreso,1,0,'R',$fill);
            $pdf->ln();
            $fill=!$fill;
            // solo sumar si estatus es igual a vendido.
            if($descripcion_estatus == "Vendido"){
                $totalIngreso = $totalIngreso + floatval(substr($total_ingreso,1));
            }
            //
            $fila++;
    }   // WHILE DE LA CONSULTA PRODUCCIÓN.
            ////////////////////////////////////////////////////
            /// Linea para colocar totoal producción.
            ////////////////////////////////////////////////////
            $pdf->SetX(30);
            $totalProduccionOK = $totalIngreso;
            $pdf->Cell($w[0],$w2[1],"",1,0,'C',$fill);
            $pdf->Cell($w[1],$w2[1],"",1,0,'C',$fill);
            $pdf->Cell($w[2],$w2[1],"",1,0,'C',$fill);
            $pdf->SetFont('Arial','B',12); // I : Italica; U: Normal;
            $pdf->Cell($w[3]+$w[4]+$w[5],$w2[1],"TOTAL PRODUCCION",1,0,'R',$fill);
            $pdf->Cell($w[6],$w2[1],"$". number_format($total_ingreso_control,2,".",","),1,0,'C',$fill);
    }else{
        $pdf->SetX(30);
        $pdf->SetFont('Courier','B',15); // I : Italica; U: Normal;
        $pdf->SetTextColor(255, 87, 51); // rgb(255, 87, 51)
        $pdf->Cell($w[0],$w2[1],"El control no ha sido creado o asignado :(",0,0,'L',$fill);
        $fecha = $NombreDia;
    } // si condicional.

// Salida del pdf.
	$modo = 'I'; // Envia al navegador (I), Descarga el archivo (D), Guardar el fichero en un local(F).
	$nombre_archivo  = mb_convert_encoding("Producción: " . $fecha . '.pdf',"ISO-8859-1","UTF-8");
	$pdf->Output($nombre_archivo,$modo);
?>