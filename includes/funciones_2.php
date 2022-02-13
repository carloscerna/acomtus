<?php
function replace_3($string){
 
	$string = trim($string);
	
	//Reemplazar tildes
	$string = str_replace(
		array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
		array('a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a'),
		$string
	);
	$string = str_replace(
		array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
		array('e', 'e', 'e', 'e', 'e', 'e', 'e', 'e'),
		$string
	);
	$string = str_replace(
		array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
		array('i', 'i', 'i', 'i', 'i', 'i', 'i', 'i'),
		$string
	);
	$string = str_replace(
		array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
		array('o', 'o', 'o', 'o', 'o', 'o', 'o', 'o'),
		$string
	);
	$string = str_replace(
		array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
		array('u', 'u', 'u', 'u', 'u', 'u', 'u', 'u'),
		$string
	);
	
	// Reemplazar Ñ y Ç
	$string = str_replace(
		array('ñ', 'Ñ', 'ç', 'Ç'),
		array('nn', 'nn', 'c', 'c'),
		$string
	);
	
	// Reemplazar Mayúsculas
	/*$string = str_replace(
		array(
			'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M',
			'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'),
		array(
			'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm',
			'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z'),
		$string
	);*/
	
	// Reemplazar caracteres especiales
	$string = str_replace(
		array(
			 "¡", "!", "", "¿", "?", "(", ")", "[", "]", "{", "}", "`", "´",
			 "º", "ª", "|", "@", "·", "#", "~", "$", "%", "&", "€", "¬", "", "'",
			 "^", "*", "+", "¨", ";", ",", ":", "", ""),
			 '',
		$string
	);
	// MODIFCIADO PARA NO COLOCAR EL GUION ENTRE LOS NOMBRES DE LOS ARCHIVOS QUE SE GUADARN LOCALMENTE.
	// Aplicar guiones
	$string = str_replace(
		array('-', ' ', '', '='),
		' ',
		$string
	);
	
	return $string;
}	

function replace_2($s) {
	$s=strtolower($s);
	$s = preg_replace("/á|à|â|ã|ª/","a",$s);
	$s = preg_replace("/Á|À|Â|Ã|/","A",$s);
	$s = preg_replace("/é|è|ê/","e",$s);
	$s = preg_replace("/É|È|Ê/","E",$s);
	$s = preg_replace("/í|ì|î/","i",$s);
	$s = preg_replace("/Í|Ì|Î/","I",$s);
	$s = preg_replace("/ó|ò|ô|õ|º/","o",$s);
	$s = preg_replace("/Ó|Ò|Ô|Õ/","O",$s);
	$s = preg_replace("/ú|ù|û/","u",$s);
	$s = preg_replace("/Ú|Ù|Û/","U",$s);
	$s = str_replace("ñ","n",$s);
	$s = str_replace("Ñ","N",$s);
	//$s = str_replace(“‘”, ”, $s);
	//$s = str_replace(‘’, ‘ ‘, $s);
	$s = str_replace("","-",$s);
	$s = preg_replace("~[^\pL0-9_]+~u", "-", $s);
	$s = trim($s, "-");
	//$s = iconv("utf-8", "us-ascii//TRANSLIT", $s);
	$s = preg_replace("~[^-a-z0-9_]+~", "", $s);
	return $s;
}


function replace_($s) {
		$s = mb_convert_encoding($s, "UTF-8","auto");
		$s = preg_replace("/á|à|â|ã|ª/","a",$s);
		$s = preg_replace("/Á|À|Â|Ã/","A",$s);
		$s = preg_replace("/é|è|ê/","e",$s);
		$s = preg_replace("/É|È|Ê/","E",$s);
		$s = preg_replace("/í|ì|î/","i",$s);
		$s = preg_replace("/Í|Ì|Î/","I",$s);
		$s = preg_replace("/ó|ò|ô|õ|º/","o",$s);
		$s = preg_replace("/Ó|Ò|Ô|Õ/","O",$s);
		$s = preg_replace("/ú|ù|û/","u",$s);
		$s = preg_replace("/Ú|Ù|Û/","U",$s);
		$s = str_replace(" ","_",$s);
		$s = str_replace("ñ","n",$s);
		$s = str_replace("Ñ","N",$s);
		
		$s = preg_replace('/[^a-zA-Z0-9_.-]/', '', $s);
		return $s;
	}
?>
