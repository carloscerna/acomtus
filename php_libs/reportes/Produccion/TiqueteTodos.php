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
    $reimprimir = false;
    $codigo_produccion = trim($_REQUEST['codigo_produccion']);
    $fecha = ($_REQUEST['fecha']);
    if(isset($_REQUEST['reimprimir'])){
        $reimprimir = $_REQUEST['reimprimir'];
    }
	$db_link = $dblink;
    $totalIngresoOK = 0;
    $total = 0;	
	$cantidadTiquete = 0;
    $saldos = 0; $print_sumas = 0; $print_no_header = 0;

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

		setlocale(LC_MONETARY,"es_ES");

class PDF extends FPDF
{
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
    $header=array();	
//Títulos de las columnas
    $pdf->AliasNbPages();
  //  $pdf->AddPage();
// Aqui mandamos texto a imprimir o al documento.
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetY(20.5);
    $pdf->SetX(15);
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetFont('Arial','',9); // I : Italica; U: Normal;
//  mostrar los valores de la consulta
    $w=array(20,50); // serie y numero desde
    $h=array(6.5,11,5,4); //determina el ALTO de las columnas
// Variables.
    $fill = false; $i=1;
// Definimos el tipo de fuente, estilo y tamaño.
    $pdf->SetXY(15,52);
//
//  crear matriz. para el cambio de columna
$colx_1 = 13; $colx_2 = 15;
$linea = 0; $salto_columna = 0;
//
    if($codigo_produccion != 0){
        // que examine si la cadena tiene coma.
            $coma = ""; $guion = "";
            $guion = strpos($codigo_produccion,"-");
            $coma = strpos($codigo_produccion,",");
        // verificar si existe guión y coma.
            if(!empty($guion) && !empty($coma)){
                // HAY GUION Y COMA.
                    print "Hay quión y coma";
                        exit;
            }
            if(!empty($coma)){
//                print "Hay Coma";
                // Convertir String a Matriz (Conitiene los N.º Correlativos de los Controles Creados.)
                    $numero_control = explode(",",$codigo_produccion);
                    $numero_elemento = count($numero_control);
                    $codigo_produccion_ = explode(",",$codigo_produccion);
                // RECORRER LA MATRIZ.
                //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                for ($jj=0; $jj < count($codigo_produccion_); $jj++) { 
                    // función para imprimir.
                    $codigo_produccion_i = $codigo_produccion_[$jj];
                        VerImprimir();                        
                }   // FIN DEL FOR.

            }else{
               // print "No Coma";
                // Convertir String a Matriz (Conitiene los N.º Correlativos de los Controles Creados.)
                $numero_control = explode("-",$codigo_produccion);
                $numero_elemento = count($numero_control);
                $codigo_produccion_ = explode("-",$codigo_produccion);
            // EXAMINAR SI HAY GUION.
                $guion = strpos($codigo_produccion,"-");
                if(!empty($guion)){
                    // HAY GUION
                    // cambiar valor de $jj
                    $codigo_partial_01 = $codigo_produccion_[0];
                    $codigo_partial_02 = $codigo_produccion_[1];
                    // RECORRER LA MATRIZ.
                    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                    for ($jj=$codigo_partial_01; $jj <= $codigo_partial_02; $jj++) { 
                        // función para imprimir.
                        $codigo_produccion_i = $jj;
                            VerImprimir();
                        }   // FIN DEL FOR.
                }else{
                    // NO HAY GUION
                    // función para imprimir.
                    $codigo_produccion_i = $codigo_produccion;
                        VerImprimir();
                }   // IF QUE EXAMINA EL GUIÓN.
            }   // IF INICIAL QUE EXAMINA LA COMA.
    //
    }else{
        $query_p = "SELECT id_ FROM produccion WHERE fecha = '$fecha' ORDER BY id_ ASC";
            $consulta_p = $dblink -> query($query_p);      
            // Validar si hay registros.
                if($consulta_p -> rowCount() != 0){  
                   // obtener el último dato en este caso el Id_
                        while($listado = $consulta_p -> fetch(PDO::FETCH_BOTH))
                        {
                            $codigo_produccion_[] = $listado['id_'];
                        }
                }                    
                // RECORRER LA MATRIZ.
                //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                for ($jj=0; $jj < count($codigo_produccion_); $jj++) { 
                    // función para imprimir.
                    $codigo_produccion_i = $codigo_produccion_[$jj];
                        VerImprimir();                        
                }   // FIN DEL FOR.
    }
// Salida del pdf.
	$modo = 'I'; // Envia al navegador (I), Descarga el archivo (D), Guardar el fichero en un local(F).
	$print_nombre = 'Control de Ingresos.pdf';
    $pdf->Output($print_nombre,$modo);
    
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function VerImprimir(){
    global $pdf, $codigo_produccion_i, $dblink, $cantidadTiquete, $w, $h, $reimprimir;
// Variables.
    $fill = false; $i=1;
//  crear matriz. para el cambio de columna
    $colx_1 = 13; $colx_2 = 15;
    $linea = 0; $salto_columna = 0;
// consulta.
    $query_c = "SELECT p.id_ AS id_produccion, p.fecha, p.codigo_inventario_tiquete, p.codigo_personal, p.fecha, p.codigo_ruta,
    cat_ts.descripcion as nombre_serie,
    pa.id_ as id_produccion_asignado, pa.tiquete_desde, pa.tiquete_hasta, pa.total, pa.cantidad, pa.codigo_inventario_tiquete as codigo_serie_id,
    it.precio_publico, 
    cat_r.descripcion as nombre_ruta,
    cat_j.id_ as id_jornada
        FROM produccion p
        INNER JOIN produccion_asignado pa ON pa.codigo_produccion = p.id_
        INNER JOIN inventario_tiquete it ON it.id_ = pa.codigo_inventario_tiquete
        INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie
        INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = p.codigo_jornada
        INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = p.codigo_ruta
            WHERE pa.codigo_produccion = '$codigo_produccion_i'
                ORDER BY pa.id_, p.codigo_inventario_tiquete";
// verficar cuantas series hay y asi poder dividirlas
// Validar si hay registros.ç
$consulta_serie = $dblink -> query($query_c);      
$consulta = $dblink -> query($query_c);         
$nombre_serie_ = array();
if($consulta_serie -> rowCount() != 0){
    $nombre_serie_ = array(); $nombre_serie_contar_valores = array();
    while($listado_serie = $consulta_serie -> fetch(PDO::FETCH_BOTH))
    {
        $nombre_serie_[] = trim($listado_serie['codigo_serie_id']);
        $nombre_ruta = trim($listado_serie['nombre_ruta']);
    }
}
// unica array validar si no está vacia o nula.
    if($nombre_serie_ != null){
        $nombre_serie_unique = array_unique($nombre_serie_);
        $nombre_serie_contar_valores = array_count_values($nombre_serie_);        
    }else{
        $nombre_serie_ = array();
        $nombre_serie_unique = array();
        $nombre_serie_contar_valores = 0;
    }

//	print_r($nombre_serie_contar_valores);
	if(count($nombre_serie_unique) > 1 && $nombre_ruta == 'Cobradores'){
        // SALTO DE PAGINA.
        $pdf->AddPage(); 
        //
        // VARIABLES DE COLUMNA, FILA Y TOTAL INGRESO OK
            $pdf->SetY(52);
            $totalIngresoOK = 0; $totalserie = 0;
            $lineaX = 12; $lineaX1 = 35; $lineaXEspacio = 7; $lineaXAncho = 23;
            $pdf->SetX($colx_1); $linea++;
    //
        foreach($nombre_serie_contar_valores as $key=>$value) {
			// consulta.
			$query_c = "SELECT p.id_ AS id_produccion, p.fecha, p.codigo_inventario_tiquete, p.codigo_personal, p.fecha, p.codigo_ruta,
			cat_ts.descripcion as nombre_serie,
			pa.id_ as id_produccion_asignado, pa.tiquete_desde, pa.tiquete_hasta, pa.total, pa.cantidad, pa.codigo_inventario_tiquete as codigo_serie_id,
			it.precio_publico, 
			cat_r.descripcion as nombre_ruta,
			cat_j.id_ as id_jornada
				FROM produccion p
				INNER JOIN produccion_asignado pa ON pa.codigo_produccion = p.id_
				INNER JOIN inventario_tiquete it ON it.id_ = pa.codigo_inventario_tiquete
				INNER JOIN catalogo_tiquete_serie cat_ts ON cat_ts.id_ = it.codigo_serie
				INNER JOIN catalogo_jornada cat_j ON cat_j.id_ = p.codigo_jornada
				INNER JOIN catalogo_ruta cat_r ON cat_r.id_ruta = p.codigo_ruta
					WHERE pa.codigo_produccion = '$codigo_produccion_i' and pa.codigo_inventario_tiquete = '$key'
						ORDER BY pa.id_";	
			$consulta = $dblink -> query($query_c);              
				// RECORRER LA CONSULTA
				while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
				{
					// PASAR VALORES DE LOS CAMPOS A LAS VARIABLES.
					$id_pro_a = trim($listado['id_produccion_asignado']);		
					$pa_codigo_produccion = trim($listado['id_produccion']);
					$codigo_serie_tabla = trim($listado['codigo_serie_id']);		
					$nombre_serie = trim($listado['nombre_serie']);		
					$tiquete_desde = trim($listado['tiquete_desde']);		
					$tiquete_hasta = trim($listado['tiquete_hasta']);		
					$total = trim($listado['total']);
					$cantidad = trim($listado['cantidad']);
					$cantidadTiquete = $cantidadTiquete + $cantidad;
					$precio_publico = trim($listado['precio_publico']);
					$fecha = cambiaf_a_normal(trim($listado['fecha']));
					$nombre_ruta = trim($listado['nombre_ruta']);	
					$id_jornada = trim($listado['id_jornada']);	
					// Calcular totoal ingresal
					$precio_publico = number_format($precio_publico,2);
					$totalIngresoOK = number_format($totalIngresoOK + $total,2);
					$totalserie = number_format($totalserie + $total,2);

					$pdf->SetX($colx_1);
					$pdf->SetFont('Arial','',12); // I : Italica; U: Normal;										
					$pdf->cell($w[0],$h[0],$nombre_serie . "    " . codigos_nuevos($tiquete_desde),0,1,'L');
				}	// validar el while FIN DEL WHILE
					// 	TOTAL INGRESO
					$pdf->SetX($colx_1);
						$pdf->SetFont('Arial','B',12); // I : Italica; U: Normal;										
						$pdf->cell($w[0],$h[3],"___________",0,1,'L');
					$pdf->SetX($colx_1);
						$pdf->cell($w[0],$h[2],"$   " . $totalIngresoOK,0,1,'L');
						$pdf->SetFont('Arial','B',14); // I : Italica; U: Normal;										
					$pdf->RotatedText($colx_1,50,"$ " . $precio_publico,0);
					$pdf->SetLineWidth(0.4);
						$pdf->line($lineaX,52,$lineaX1,52);
						//$pdf->line(42,52,65,52);
					//
					if($salto_columna = 0){
						$colx_1 = 13; $colx_2 = 15;
						
						$totalIngresoOK = 0;
					}else{
						$salto_columna++;
						$colx_1 = $colx_1 + 30;											//
						$pdf->SetY(52);
						$totalIngresoOK = 0;
						$lineaX = $lineaX + $lineaXEspacio + $lineaXAncho;
						$lineaX1 = $lineaX1 + $lineaXAncho + $lineaXEspacio;
					}
            } //FIN DEL FOR EEACH
                //	IMPRIMIR FECHA, RUTA Y JORNADA.
                $pdf->SetFont('Arial','B',13); // I : Italica; U: Normal;										
                $pdf->RotatedText(190,18,$pa_codigo_produccion,0);
                $pdf->SetFont('Arial','',11); // I : Italica; U: Normal;										
                $pdf->RotatedText(155,26,$fecha,0);
                $pdf->SetFont('Arial','',14); // I : Italica; U: Normal;										
                $pdf->RotatedText(26,33,$nombre_ruta,0);
                $pdf->RotatedText(128,33,$id_jornada,0);
                $pdf->SetFont('Arial','B',14); // I : Italica; U: Normal;										
                //$pdf->RotatedText(15,50,"$ " . $precio_publico,0);
            // Imprimir Total a Entregar.
                $pdf->SetFont('Arial','',9); // I : Italica; U: Normal;		
                $pdf->ln();
                $pdf->SetFont('Arial','B',13); // I : Italica; U: Normal;		
                $pdf->RotatedText(15,120,"Total Entregado: $ ",0);
                $pdf->RotatedText(58,120,$totalserie,0);
	}else{
        // Ejecutamos el query
            $totalIngresoOK = 0;
            $consulta = $dblink -> query($query_c);      
        // Validar si hay registros.
            if($consulta -> rowCount() != 0){
                // SALTO DE PAGINA.
                    $pdf->AddPage();        
                // Definimos el tipo de fuente, estilo y tamaño.
                    $pdf->SetXY(15,52);
                // obtener el último dato en este caso el Id_
                while($listado = $consulta -> fetch(PDO::FETCH_BOTH))
                {
                    if($linea == 8){
                        $colx_1 = $colx_1 + 30;
                        $linea = 0;
                        $salto_columna = 1;
                        $pdf->SetY(52);
                    }
                    $pdf->SetX($colx_1); $linea++;

                    $id_pro_a = trim($listado['id_produccion_asignado']);		
                    $pa_codigo_produccion = trim($listado['id_produccion']);
                    $nombre_serie = trim($listado['nombre_serie']);		
                    $tiquete_desde = trim($listado['tiquete_desde']);		
                    $tiquete_hasta = trim($listado['tiquete_hasta']);	
                    $precio_publico = $listado['precio_publico'];
                    // reimprimir el n.º control.
                    if($reimprimir == true){
                        $cantidad = (($tiquete_hasta - $tiquete_desde) + 1);
                        $total = round($precio_publico * $cantidad,2);
                    }else{
                        $total = ($listado['total']);
                        $cantidad = ($listado['cantidad']);
                    }
                    //
                    $cantidadTiquete = $cantidadTiquete + $cantidad;
                    $fecha = cambiaf_a_normal(trim($listado['fecha']));
                    $nombre_ruta = trim($listado['nombre_ruta']);	
                    $id_jornada = trim($listado['id_jornada']);	
                // Calcular totoal ingresal
                    $precio_publico = number_format($precio_publico,2);
                    $totalIngresoOK = number_format($totalIngresoOK + $total,2);
                //
                    $pdf->SetFont('Arial','',12); // I : Italica; U: Normal;										
                    $pdf->cell($w[0],$h[0],$nombre_serie . "    " . codigos_nuevos($tiquete_desde),0,1,'L');
                }	// FIN DEL WHILE
                    // 	TOTAL INGRESO
                    $pdf->SetX($colx_1);
                    $pdf->SetFont('Arial','B',12); // I : Italica; U: Normal;										
                    $pdf->cell($w[0],$h[3],"___________",0,1,'L');
                    $pdf->SetX($colx_1);
                    $pdf->cell($w[0],$h[2],"$   " . $totalIngresoOK,0,1,'L');
                // CATNIDAD TIQUETE.
                    //$pdf->cell($w[1],$h[2],"Cantidad Tiquete: " . $cantidadTiquete,0,1,'L');
                //	IMPRIMIR FECHA, RUTA Y JORNADA.
                    $pdf->SetFont('Arial','B',13); // I : Italica; U: Normal;										
                    $pdf->RotatedText(190,18,$pa_codigo_produccion,0);
                    $pdf->SetFont('Arial','',11); // I : Italica; U: Normal;										
                    $pdf->RotatedText(155,26,$fecha,0);
                    $pdf->SetFont('Arial','',14); // I : Italica; U: Normal;										
                    $pdf->RotatedText(26,33,$nombre_ruta,0);
                    $pdf->RotatedText(128,33,$id_jornada,0);
                    $pdf->SetFont('Arial','B',14); // I : Italica; U: Normal;										
                    $pdf->RotatedText(15,50,"$ " . $precio_publico,0);
                    $pdf->SetLineWidth(0.4);
                    $pdf->line(12,52,35,52);
                // Imprimir Total a Entregar.
                    $pdf->SetFont('Arial','',9); // I : Italica; U: Normal;		
                    $pdf->ln();
                    $pdf->SetFont('Arial','B',13); // I : Italica; U: Normal;		
                    $pdf->RotatedText(15,120,"Total Entregado: $ ",0);
                    $pdf->RotatedText(58,120,$totalIngresoOK,0);
            }
        }
}
?>