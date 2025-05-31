<?php
/**
 * Función para detectar el sistema operativo, navegador y versión del mismo
 */
$info=detect();
 
/*echo "Sistema operativo: ".$info["os"];
echo "Navegador: ".$info["browser"];
echo "Versión: ".$info["version"];
echo $_SERVER['HTTP_USER_AGENT'];
*/


/*
echo "Sistema Operativo: " . $sistemaOperativo . "<br>";
echo "Versión: " . $version . "<br>";
echo "Sistema Operativo: " . $version_[1] . "<br>";
echo "Release: " . $release . "<br>";
echo "Arquitectura: " . $arquitectura . "<br>";
echo "Nombre del Host: " . $nombreHost . "<br>";
*/
/**
 * Funcion que devuelve un array con los valores:
 *	os => sistema operativo
 *	browser => navegador
 *	version => version del navegador
 */
function detect()
{
	$version_ = [];
	$respuestaOK = false;
$mensajeError = "No se puede ejecutar la aplicaci�n";
$contenidoOK = "";
	// Obtener información detallada del sistema operativo
$sistemaOperativo = php_uname('s'); // Nombre del sistema operativo
$version = php_uname('v'); // Versión del sistema operativo
$version_ = explode("(",$version);
$release = php_uname('r'); // Release del sistema operativo
$arquitectura = php_uname('m'); // Arquitectura del sistema
$nombreHost = php_uname('n'); // Nombre del host
$sistema = explode("(",$sistemaOperativo);

//var_dump($version_);
$contenidoOK = $version_[1];
//print "El sistema no puede Ejecutarse en Windows 7, actualice la version del sistema.";
//exit("Fin :(");

		// Armamos array para convertir a JSON
		$salidaJson = array("respuesta" => $respuestaOK,
			"mensaje" => $mensajeError,
			"contenido" => $contenidoOK);
		echo json_encode($salidaJson);
/*
	$browser=array("IE","OPERA","MOZILLA","NETSCAPE","FIREFOX","SAFARI","CHROME");
	$os=array("WIN","MAC","LINUX");
 
	# definimos unos valores por defecto para el navegador y el sistema operativo
	$info['browser'] = "OTHER";
	$info['os'] = "OTHER";
 
	# buscamos el navegador con su sistema operativo
	foreach($browser as $parent)
	{
		$s = strpos(strtoupper($_SERVER['HTTP_USER_AGENT']), $parent);
		$f = $s + strlen($parent);
		$version = substr($_SERVER['HTTP_USER_AGENT'], $f, 15);
		$version = preg_replace('/[^0-9,.]/','',$version);
		if ($s)
		{
			$info['browser'] = $parent;
			$info['version'] = $version;
		}
	}
 
	# obtenemos el sistema operativo
	foreach($os as $val)
	{
		if (strpos(strtoupper($_SERVER['HTTP_USER_AGENT']),$val)!==false)
			$info['os'] = $val;
	}
 
	# devolvemos el array de valores
	return $info;
	*/
}