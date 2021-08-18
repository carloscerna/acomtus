<?php
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
//Construimos el DSN//
try{
    $dsn = "pgsql:host=$host;port=$port;dbname=$database";
    // Creamos el objeto
    $dblink = new PDO($dsn, $username, $password);

    // Validar la conexi�n.
    if(!$dblink){
     // Variable que indica el status de la conexi�n a la base de datos
        $errorDbConexion = true;   
    }
}catch(PDOException $e) {
         echo  $e->getMessage();
         $errorDbConexion = true;   
     }
?>