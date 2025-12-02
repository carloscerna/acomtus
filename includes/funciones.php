<?php
// Calcula la edad (formato: año/mes/dia)
function edad($edad){
date_default_timezone_set('America/Mexico_City');
    list($dia,$mes,$anio) = explode("/",$edad);
    $dia_dif = date("d") - $dia;
    $mes_dif = date("m") - $mes;
    $anio_dif = date("Y") - $anio;
        
    if ($dia_dif < 0 || $mes_dif < 0)
        $anio_dif--;
        return $anio_dif;
}
////////////////////////////////////////////////////
//Convierte fecha de mysql a normal
////////////////////////////////////////////////////
function cambiaf_a_normal($fecha)
{
    $cad = preg_split('/ /',$fecha);
    $sub_cad = preg_split('/-/',$cad[0]);
    $fecha_formateada = $sub_cad[2].'/'.$sub_cad[1].'/'.$sub_cad[0];
    return $fecha_formateada;
}
// fecha año/mes/dia.
function fechaYMD()
{
// Inciar variable global datos y mensajes de error.
    date_default_timezone_set('America/El_Salvador');
    $day=date("d");
    $month=date("m");
    $year=date("Y");
    $date=$day."/".$month."/".$year;
    $fecha=$year."-".$month."-".$day;
    
    return $fecha;
}
//////////////////////////////////////////////////////////////////////////
/// CONVERTRIR STRING A NUMERO
//////////////////////////////////////////////////////////////////////////
function convertir_a_numero($str)
{
  $legalChars = "%[^0-9\\-\\. ]%";

  $str=preg_replace($legalChars,"",$str);
  return $str;
}
/////////////////////////////////////////////////////////////////////////////////////////
// Crear Array para carpetas principales en donde se guardaran los archivos.
/////////////////////////////////////////////////////////////////////////////////////////
function CrearDirectorios($ruta_url,$nombre_ann_lectivo,$codigo_tipo_factura,$codigo_destino,$numero_periodo){
	global $DestinoArchivo;

	$DestinoArchivo = "";
	$nombre_directorios = array("/sgp_web/Archivos/","/sgp_web/Archivos/Saldos/","/sgp_web/Archivos/xx","/sgp_web/Archivos/xxx");
	$nombre_tipo_factura = array("Fianzas/","Prestamos/");
	$nombre_modalidad_escribir = "";
	$nombre_ann_lectivo = $nombre_ann_lectivo . "/";
	$numero_periodo = $numero_periodo . "/";
	
	// Asignarle valor a Nombre Modalidad.
		if($codigo_tipo_factura == "01")
			{
				$nombre_tipo_factura_escribir = $nombre_tipo_factura[0];
			}else{
				$nombre_tipo_factura_escribir = $nombre_tipo_factura[1];
			}
	
	if(!file_exists($ruta_url.$nombre_directorios[0]))
		{
			// Crear el Directorio Principal Archvos...
			mkdir ($ruta_url.$nombre_directorios[0]);
			chmod($ruta_url.$nombre_directorios[0],07777);
				// Crear el Subdirectorio Año Lectivo y Educación Básica o Media.
				// Para Nóminas. Escolanadamente.
					mkdir ($ruta_url.$nombre_directorios[1]);
					chmod($ruta_url.$nombre_directorios[1],07777);
						mkdir ($ruta_url.$nombre_directorios[1].$nombre_tipo_factura_escribir);
						chmod ($ruta_url.$nombre_directorios[1].$nombre_tipo_factura_escribir,07777);
							mkdir ($ruta_url.$nombre_directorios[1].$nombre_tipo_factura_escribir.$nombre_ann_lectivo);
							chmod ($ruta_url.$nombre_directorios[1].$nombre_tipo_factura_escribir.$nombre_ann_lectivo,07777);
				/*// Para Control de Actividades Notas.
					mkdir ($ruta_url.$nombre_directorios[2]);
					chmod($ruta_url.$nombre_directorios[2],07777);
						mkdir ($ruta_url.$nombre_directorios[2].$nombre_modalidad_escribir);
						chmod ($ruta_url.$nombre_directorios[2].$nombre_modalidad_escribir,07777);
							mkdir ($ruta_url.$nombre_directorios[2].$nombre_modalidad_escribir.$nombre_ann_lectivo);
							chmod ($ruta_url.$nombre_directorios[2].$nombre_modalidad_escribir.$nombre_ann_lectivo,07777);
				// Para Exportar Notas SIRAI
					mkdir ($ruta_url.$nombre_directorios[3]);
					chmod($ruta_url.$nombre_directorios[3],07777);
						mkdir ($ruta_url.$nombre_directorios[3].$nombre_modalidad_escribir);
						chmod ($ruta_url.$nombre_directorios[3].$nombre_modalidad_escribir,07777);
							mkdir ($ruta_url.$nombre_directorios[3].$nombre_modalidad_escribir.$nombre_ann_lectivo);
							chmod ($ruta_url.$nombre_directorios[3].$nombre_modalidad_escribir.$nombre_ann_lectivo,07777);*/
		}
		// proceso para las nòminas.
		if($codigo_destino === 1){
			if(!file_exists($ruta_url.$nombre_directorios[1])){
					// Para Nóminas. Escolanadamente.
						mkdir ($ruta_url.$nombre_directorios[1]);
						chmod ($ruta_url.$nombre_directorios[1],07777);
							mkdir ($ruta_url.$nombre_directorios[1].$nombre_tipo_factura_escribir);
							chmod ($ruta_url.$nombre_directorios[1].$nombre_tipo_factura_escribir,07777);
								mkdir ($ruta_url.$nombre_directorios[1].$nombre_tipo_factura_escribir.$nombre_ann_lectivo);
								chmod ($ruta_url.$nombre_directorios[1].$nombre_tipo_factura_escribir.$nombre_ann_lectivo,07777);
				}
			if(!file_exists($ruta_url.$nombre_directorios[1].$nombre_tipo_factura_escribir)){
					// Para Nóminas. Escolanadamente.
							mkdir ($ruta_url.$nombre_directorios[1].$nombre_tipo_factura_escribir);
							chmod ($ruta_url.$nombre_directorios[1].$nombre_tipo_factura_escribir,07777);
								mkdir ($ruta_url.$nombre_directorios[1].$nombre_tipo_factura_escribir.$nombre_ann_lectivo);
								chmod ($ruta_url.$nombre_directorios[1].$nombre_tipo_factura_escribir.$nombre_ann_lectivo,07777);
				}
			if(!file_exists($ruta_url.$nombre_directorios[1].$nombre_tipo_factura_escribir.$nombre_ann_lectivo)){
					// Para Nóminas. Escolanadamente.
								mkdir ($ruta_url.$nombre_directorios[1].$nombre_tipo_factura_escribir.$nombre_ann_lectivo);
								chmod ($ruta_url.$nombre_directorios[1].$nombre_tipo_factura_escribir.$nombre_ann_lectivo,07777);
				}
		}
		/*
		if($codigo_destino === 2){
			// proceso para el control de actividades..
			if(!file_exists($ruta_url.$nombre_directorios[2])){
					// Para Nóminas. Escolanadamente.
						mkdir ($ruta_url.$nombre_directorios[2]);
						chmod ($ruta_url.$nombre_directorios[2],07777);
							mkdir ($ruta_url.$nombre_directorios[2].$nombre_modalidad_escribir);
							chmod ($ruta_url.$nombre_directorios[2].$nombre_modalidad_escribir,07777);
								mkdir ($ruta_url.$nombre_directorios[2].$nombre_modalidad_escribir.$nombre_ann_lectivo);
								chmod ($ruta_url.$nombre_directorios[2].$nombre_modalidad_escribir.$nombre_ann_lectivo,07777);
				}
			if(!file_exists($ruta_url.$nombre_directorios[2].$nombre_modalidad_escribir)){
					// Para Nóminas. Escolanadamente.
							mkdir ($ruta_url.$nombre_directorios[2].$nombre_modalidad_escribir);
							chmod ($ruta_url.$nombre_directorios[2].$nombre_modalidad_escribir,07777);
								mkdir ($ruta_url.$nombre_directorios[2].$nombre_modalidad_escribir.$nombre_ann_lectivo);
								chmod ($ruta_url.$nombre_directorios[2].$nombre_modalidad_escribir.$nombre_ann_lectivo,07777);
				}
			if(!file_exists($ruta_url.$nombre_directorios[2].$nombre_modalidad_escribir.$nombre_ann_lectivo)){
					// Para Nóminas. Escolanadamente.
								mkdir ($ruta_url.$nombre_directorios[2].$nombre_modalidad_escribir.$nombre_ann_lectivo);
								chmod ($ruta_url.$nombre_directorios[2].$nombre_modalidad_escribir.$nombre_ann_lectivo,07777);
				}
		}
	// En el caso que ex EXPORTAR NOTAS SIRAI, se crean los directorios o carpetas respectivas.
		if($codigo_destino === 3){
			// proceso para el control de actividades..
			if(!file_exists($ruta_url.$nombre_directorios[3])){
					// Para Nóminas. Escolanadamente.
						mkdir ($ruta_url.$nombre_directorios[3]);
						chmod ($ruta_url.$nombre_directorios[3],07777);
							mkdir ($ruta_url.$nombre_directorios[3].$nombre_modalidad_escribir);
							chmod ($ruta_url.$nombre_directorios[3].$nombre_modalidad_escribir,07777);
								mkdir ($ruta_url.$nombre_directorios[3].$nombre_modalidad_escribir.$nombre_ann_lectivo);
								chmod ($ruta_url.$nombre_directorios[3].$nombre_modalidad_escribir.$nombre_ann_lectivo,07777);
				}
			if(!file_exists($ruta_url.$nombre_directorios[3].$nombre_modalidad_escribir)){
					// Para Nóminas. Escolanadamente.
							mkdir ($ruta_url.$nombre_directorios[3].$nombre_modalidad_escribir);
							chmod ($ruta_url.$nombre_directorios[3].$nombre_modalidad_escribir,07777);
								mkdir ($ruta_url.$nombre_directorios[3].$nombre_modalidad_escribir.$nombre_ann_lectivo);
								chmod ($ruta_url.$nombre_directorios[3].$nombre_modalidad_escribir.$nombre_ann_lectivo,07777);
				}
			if(!file_exists($ruta_url.$nombre_directorios[3].$nombre_modalidad_escribir.$nombre_ann_lectivo)){
					// Para Nóminas. Escolanadamente.
								mkdir ($ruta_url.$nombre_directorios[3].$nombre_modalidad_escribir.$nombre_ann_lectivo);
								chmod ($ruta_url.$nombre_directorios[3].$nombre_modalidad_escribir.$nombre_ann_lectivo,07777);
				}
			if(!file_exists($ruta_url.$nombre_directorios[3].$nombre_modalidad_escribir.$nombre_ann_lectivo.$numero_periodo)){
					// Para Nóminas. Escolanadamente. PERIODO
								mkdir ($ruta_url.$nombre_directorios[3].$nombre_modalidad_escribir.$nombre_ann_lectivo.$numero_periodo);
								chmod ($ruta_url.$nombre_directorios[3].$nombre_modalidad_escribir.$nombre_ann_lectivo.$numero_periodo,07777);
				}				
		}
			*/
	// Cóndicionar la ruta del archivos destino.
		switch($codigo_destino)
	{
		case 1: // Nóminas
			$DestinoArchivo = $ruta_url.$nombre_directorios[1].$nombre_tipo_factura_escribir.$nombre_ann_lectivo;
			break;
		case 2:	// Control de Actividades.
			$DestinoArchivo = $ruta_url.$nombre_directorios[2].$nombre_tipo_factura_escribir.$nombre_ann_lectivo;
			break;
		case 3: // Notas SIRAI.
			$DestinoArchivo = $ruta_url.$nombre_directorios[3].$nombre_tipo_factura_escribir.$nombre_ann_lectivo.$numero_periodo;
			break;
	}
	
	return $DestinoArchivo;
}

/////////////////////////////////////////////////////////////////////////////////////////
//				**	conversor
/////////////////////////////////////////////////////////////////////////////////////////
function segundosToCadenaD($min)
{

     $dias = floor($min/300);
     $horas = $min%300;
     $residuo_dias = $horas%300;
     $horas = floor($residuo_dias/60);
     $residuo_minutos = $residuo_dias%60;
     $minutos = $residuo_minutos;
    return $dias;
}

function segundosToCadenaH($min)
{

     $dias = floor($min/300);
     $horas = $min%300;
     $residuo_dias = $horas%300;
     $horas = floor($residuo_dias/60);
     $residuo_minutos = $residuo_dias%60;
     $minutos = $residuo_minutos;
    return $horas;
}

function segundosToCadenaM($min)
{

     $dias = floor($min/300);
     $horas = $min%300;
     $residuo_dias = $horas%300;
     $horas = floor($residuo_dias/60);
     $residuo_minutos = $residuo_dias%60;
     $minutos = $residuo_minutos;
    return $minutos;
}

function segundosToCadena($min)
{
  $cadena = '';
     $dias = floor($min/300);
     $horas = $min%300;
     $residuo_dias = $horas%300;
     $horas = floor($residuo_dias/60);
     $residuo_minutos = $residuo_dias%60;
     $minutos = $residuo_minutos;
     $cadena = $dias.'d'.$horas.'h'.$minutos.'m';
    return $cadena;
}

function segundosToCadenaHorasMinustos($min)
{
  $cadena = '';
     $dias = floor($min/300);
     $horas = $min%300;
     $residuo_dias = $horas%300;
     $horas = floor($residuo_dias/60);
     $residuo_minutos = $residuo_dias%60;
     $minutos = $residuo_minutos;
     $cadena = $horas.'h '.$minutos.'m';
    return $cadena;
}

function conversor_segundos($seg_ini) {

$horas = floor($seg_ini/3600);
$minutos = floor(($seg_ini-($horas*3600))/60);
$segundos = $seg_ini-($horas*3600)-($minutos*60);
//echo $horas.?h:?.$minutos.?m:?.$segundos.?s';
}

/////////////////////////////////////////////////////////////////////////////////////////
	function cambiar_de_del($de_por_de)
	{
		$ver = ""; $vere = ""; $nombre = ""; $nuevonombre = ""; $cambiar = 0;	$nuevo_de_del = "";
		$nombre_m = utf8_decode(trim($de_por_de));
		//$nombre = ucwords(mb_strtolower($nombre_m,'ISO-8859-1'));
		$nombre = mb_convert_case($nombre_m, MB_CASE_TITLE, "ISO-8859-1"); 
		//$str = mb_convert_case($str, MB_CASE_UPPER, "UTF-8"); echo $str; // Muestra MARY HAD A LITTLE LAMB AND SHE LOVED IT SO
				
		for($i=0;$i<=strlen($nombre);$i++)
		{
			$ver = substr($nombre,$i,1);
			$nuevonombre = $nuevonombre . $ver;
			
			if(substr($nombre,$i,1) == " ")
			{
				if(substr($nombre,$i,4) == " De ")
					{
						$nuevonombre = $nuevonombre . " de ";
						$i = $i + 3;
							$cambiar = 1;
					}

				if(substr($nombre,$i,4) == " La ")
					{
						$nuevonombre = $nuevonombre . " la ";
						$i = $i + 3;
							$cambiar = 1;
					}

				if(substr($nombre,$i,5) == " Del ")
					{
						$nuevonombre = $nuevonombre . " del ";
						$i = $i + 4;
							$cambiar = 1;
					}
				
				/*if(substr($nombre,$i,1) == " ")
					{
						$nuevonombre = trim($nuevonombre) . "_";
						//$i = $i + 0;
							$cambiar = 1;
					}	*/
			}	// fin del primer if...
		}	// fin del for.
		
		if($cambiar == 1)
			{
				$nuevo_de_del = $nuevonombre;
				$cambiar = 0;
			}
			else
			{
				$nuevo_de_del = $nombre;
			}
		return $nuevo_de_del;
	}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
		function cambiar_de_del_2($de_por_de)
	{
		$ver = ""; $vere = ""; $nombre = ""; $nuevonombre = ""; $cambiar = 0;	$nuevo_de_del = "";
		$nombre = mb_convert_case($de_por_de, MB_CASE_TITLE, "UTF-8");
		//		$nombre = ucwords(strtolower($de_por_de));

		//$nombre = mb_convert_case(trim($de_por_de), MB_CASE_TITLE, "ISO-8859-1");
				
		for($i=0;$i<=strlen($nombre);$i++)
		{
			$ver = substr($nombre,$i,1);
			$nuevonombre = $nuevonombre . $ver;
			
			if(substr($nombre,$i,1) == " ")
			{
				if(substr($nombre,$i,4) == " De ")
					{
						$nuevonombre = $nuevonombre . " de ";
						$i = $i + 3;
							$cambiar = 1;
					}

				if(substr($nombre,$i,4) == " La ")
					{
						$nuevonombre = $nuevonombre . " la ";
						$i = $i + 3;
							$cambiar = 1;
					}

				if(substr($nombre,$i,5) == " Del ")
					{
						$nuevonombre = $nuevonombre . " del ";
						$i = $i + 4;
							$cambiar = 1;
					}
				
				/*if(substr($nombre,$i,1) == " ")
					{
						$nuevonombre = trim($nuevonombre) . "_";
						//$i = $i + 0;
							$cambiar = 1;
					}	*/
			}	// fin del primer if...
		}	// fin del for.
		
		if($cambiar == 1)
			{
				$nuevo_de_del = $nuevonombre;
				$cambiar = 0;
			}
			else
			{
				$nuevo_de_del = $nombre;
			}
		return $nuevo_de_del;
	}
/////////////////////////////////////////////////////////////////////////////////////////
//				**	Bloque de Código AGREGAR CEROS A LA IZQUIERDA..
/////////////////////////////////////////////////////////////////////////////////////////
function codigos_nuevos_personal($tiquete)
	{
	$nuevo_codigo = ""; $codigo_mas = ""; $iv = 0;
	//
		if(strlen(trim($tiquete)) == 1){
			$codigo_mas = "00" . trim($tiquete);}
		if(strlen(trim($tiquete)) == 2){
			$codigo_mas = "0" . trim($tiquete);}
		if(strlen(trim($tiquete)) == 3){
			$codigo_mas = "000" . trim($tiquete);}
		if(strlen(trim($tiquete)) == 4){
			$codigo_mas = "00" . trim($tiquete);}
		if(strlen(trim($tiquete)) == 5){
			$codigo_mas = "0" . trim($tiquete);}
		if(strlen(trim($tiquete)) == 6){
			$codigo_mas = "" . trim($tiquete);}
		if(strlen(trim($tiquete)) == 7){
			$codigo_mas = "" . trim($tiquete);}
		//
		$nuevo_codigo = $codigo_mas;						
			return $nuevo_codigo;
	}
function codigos_nuevos($tiquete)
	{
	$nuevo_codigo = ""; $codigo_mas = ""; $iv = 0;
	//
		if(strlen(trim($tiquete)) == 1){
			$codigo_mas = "00000" . trim($tiquete);}
		if(strlen(trim($tiquete)) == 2){
			$codigo_mas = "0000" . trim($tiquete);}
		if(strlen(trim($tiquete)) == 3){
			$codigo_mas = "000" . trim($tiquete);}
		if(strlen(trim($tiquete)) == 4){
			$codigo_mas = "00" . trim($tiquete);}
		if(strlen(trim($tiquete)) == 5){
			$codigo_mas = "0" . trim($tiquete);}
		if(strlen(trim($tiquete)) == 6){
			$codigo_mas = "" . trim($tiquete);}
			if(strlen(trim($tiquete)) == 7){
				$codigo_mas = "" . trim($tiquete);}
			//
		$nuevo_codigo = $codigo_mas;						
			return $nuevo_codigo;
	}
	function codigos_nuevos_dos($tiquete)
	{
		$nuevo_codigo = ""; $codigo_mas = ""; $iv = 0;
						
					if(strlen(trim($tiquete)) == 1){
						$codigo_mas = "0" . trim($tiquete);}
						
					if(strlen(trim($tiquete)) == 2){
						$codigo_mas = "" . trim($tiquete);}
						
					$nuevo_codigo = $codigo_mas;					
					
					return $nuevo_codigo;
	}
////////////////////////////////////////////////////
//	bloque de año y sección.
////////////////////////////////////////////////////
function cambiar_grado($grado)
	{
	  $nuevo_grado = "";
					
	switch ($grado)
		{
    		case "Primero":
        	$nuevo_grado = "primer"; break;
        	case "Segundo":
        			$nuevo_grado = "segundo"; break;
        		case "Tercero":
        			$nuevo_grado = "tercer"; break;
        		case "Cuarto":
        			$nuevo_grado = "cuarto"; break;
        		case "Quinto":
        			$nuevo_grado = "quinto"; break;
        		case "Sexto":
        			$nuevo_grado = "sexto"; break;
        		case "Séptimo":
        			$nuevo_grado = "séptimo"; break;
        		case "Octavo":
        			$nuevo_grado = "octavo"; break;
        		case "Noveno":
        			$nuevo_grado = "noveno"; break;
      		}
					return $nuevo_grado;
	}
	////////////////////////////////////////////////////
//	Obtener el Grado superior a partir del actual.
////////////////////////////////////////////////////
function grado_superior($grado)
	{
		$nuevo_grado = "";			
	switch ($grado)
	{
		case "Kinder":
   			$nuevo_grado = "preparatoria"; break;
		case "Preparatoria":
       			$nuevo_grado = "primer grado"; break;
		case "Primero":
       			$nuevo_grado = "grado inmediato superior"; break;
       		case "Segundo":
       			$nuevo_grado = "grado inmediato superior"; break;
       		case "Tercero":
       			$nuevo_grado = "grado inmediato superior"; break;
       		case "Cuarto":
       			$nuevo_grado = "grado inmediato superior"; break;
       		case "Quinto":
       			$nuevo_grado = "grado inmediato superior"; break;
       		case "Sexto":
			 //$nuevo_grado = "séptimo"; break;
       			$nuevo_grado = "grado inmediato superior"; break;
       		case "Séptimo":
       			$nuevo_grado = "grado inmediato superior"; break;
       		case "Octavo":
       			$nuevo_grado = "grado inmediato superior"; break;
       		case "Noveno":
       			$nuevo_grado = ""; break;
      		}
					return $nuevo_grado;
	}
////////////////////////////////////////////////////
//contar promovidos 
////////////////////////////////////////////////////

function contar_promovidos_aprobados($x1){
 global $contar_aprobados, $contar_reprobados;
    	if($x1 >= 5){$contar_aprobados++;}
    	if($x1 < 5){$contar_reprobados++;}
    return ;
}
////////////////////////////////////////////////////
//contar promovidos masculino
////////////////////////////////////////////////////

function contar_promovidos($x1, $x2, $nota_e){
 global $contar_p_m, $contar_r_m, $contar_p_f, $contar_r_f, $si_aprobado, $no_aprobado;;
 
    	if($x1 == 'm' && $x2 >= 5){$contar_p_m++;}
    	if($x1 == 'm' && $x2 < 5){$contar_r_m++;}
    
    	if($x1 == 'f' && $x2 >= 5){$contar_p_f++;}
    	if($x1 == 'f' && $x2 < 5){$contar_r_f++;}
	
		
	if($x2 >= $nota_e){$si_aprobado++;}
	if($x2 < $nota_e){$no_aprobado++;}
    return ;
}
////////////////////////////////////////////////////
//Aprobados o Reprobados
////////////////////////////////////////////////////
function cambiar_aprobado_reprobado_m($ap_re){
    $ap_res = '';
    	if($ap_re !=0){
    		if($ap_re >= 6){$ap_res = "A";}else{$ap_res = "R";}}
    	else{
    		$ap_res = '';}
    
    return $ap_res;
}
////////////////////////////////////////////////////
//Aprobados o Reprobados
////////////////////////////////////////////////////
function cambiar_aprobado_reprobado_b($ap_re){
    $ap_res = '';
    	if($ap_re !=0){
    		if($ap_re >= 5){$ap_res = "A";}else{$ap_res = "R";}}
    	else{
    		$ap_res = '';}
    
    return $ap_res;
}

////////////////////////////////////////////////////
//  conceptos bueno, muy bueno y excelente.
////////////////////////////////////////////////////
function cambiar_concepto($concepto){
    $conceptos = '';
	if($concepto == 0){$conceptos = "";}	
	if($concepto >= 1 && $concepto <=5){$conceptos = "R";}
    if($concepto >= 5 && $concepto <= 6){$conceptos = "B";}
    if($concepto >= 7 && $concepto <= 8){$conceptos = "MB";}
    if($concepto >= 9 && $concepto <= 10){$conceptos = "E";}
    
    return $conceptos;
}

function cambiar_concepto_letras($concepto){
    $conceptos = '';
		if($concepto < 5 ){$conceptos = "Regular";}
    if($concepto >= 5 && $concepto <= 6){$conceptos = "Bueno";}
    if($concepto >= 7 && $concepto <= 8){$conceptos = "Muy Bueno";}
    if($concepto >= 9 && $concepto <= 10){$conceptos = "Excelente";}
    
    return $conceptos;
}

function cambiar_concepto_letras_prepa($concepto){
    $conceptos = '';
		if($concepto < 5 ){$conceptos = "";}
    if($concepto >= 5 && $concepto <= 6){$conceptos = "DB";}
    if($concepto >= 7 && $concepto <= 8){$conceptos = "DM";}
    if($concepto >= 9 && $concepto <= 10){$conceptos = "DA";}
    
    return $conceptos;
}
////////////////////////////////////////////////////
//Convierte fecha de mysql a normal
////////////////////////////////////////////////////
/*
function cambiaf_a_normal($fecha)
{
    $cad = preg_split('/ /',$fecha);
    $sub_cad = preg_split('/-/',$cad[0]);
    $fecha_formateada = $sub_cad[2].'/'.$sub_cad[1].'/'.$sub_cad[0];
    return $fecha_formateada;
}

/* antigua funcion
function cambiaf_a_normal($fecha){
    ereg("([0-9]{2,4})-([0-9]{1,2})-([0-9]{1,2})", $fecha, $mifecha);
    $lafecha=$mifecha[3]."/".$mifecha[2]."/".$mifecha[1];
    return $lafecha;
}*/

////////////////////////////////////////////////////
//Convierte fecha de normal a mysql
////////////////////////////////////////////////////
function cambiaf_a_mysql($fecha)
{
    $cad = preg_split('/ /',$fecha);
    $sub_cad = preg_split('/-/',$cad[0]);
    //$cad_hora = preg_split('/:/',$cad[1]);
    //$hora_formateada = $cad[0].':'.$cad_hora[1].':'.$cad_hora[2];
    $fecha_formateada = $sub_cad[0];
    //print $fecha_formateada = $sub_cad[2].$sub_cad[1].$sub_cad[0];
    return $fecha_formateada;
}
