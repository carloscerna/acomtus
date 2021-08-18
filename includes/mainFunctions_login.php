<?php
// iniciar sesssion.
session_name('Sistema2020');
session_start();
// omitir errores.
ini_set("display_error", true);
// variables/conexion.
    $host = 'localhost';
    $port = 5432;
    $database = 'acomtus';
    $username = 'postgres';
    $password = 'Orellana';
//Construimos el DSN//
try{
    $dsn = "pgsql:host=$host;port=$port;dbname=$database";
}catch(PDOException $e) {
         echo  $e->getMessage();
         $errorDbConexion = true;   
     }
// Creamos el objeto
    $dblink = new PDO($dsn, $username, $password);
// Validar la conexión.
    if(!$dblink){
     // Variable que indica el status de la conexión a la base de datos
        $errorDbConexion = true;   
    };
?>