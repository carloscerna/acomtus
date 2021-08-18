<?php
// iniciar sesssion.
session_name('Sistema2020');
session_start();

// Nombre de la base de datos.
	$dataname = $_SESSION['dbname'];
// omitir errores.
	ini_set("display_error", true);
// variables para la conexion.
    $host = 'localhost';
    $port = 5432;
    $database = $dataname;
    $username = 'postgres';
    $password = 'Orellana';
/*Construimos el DSN (Data Source Name). Esta cadena indicará la información de nuestro servidor. */
    $dsn = "pgsql:host=$host;port=$port;dbname=$database";
// Creamos el objeto
    $dblink = new PDO($dsn, $username, $password);
// Variable que indica el status de la conexión a la base de datos
	$errorDbConexion = false;
?>