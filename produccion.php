<?php
declare(strict_types=1); // Activa chequeo estricto de tipos en PHP 8

session_name('Sistema2020');
session_start();

// Comprobar si existen las variables de SESSION.
if (empty($_SESSION['userNombre'])) {
    header('Location: /acomtus');
    exit; // Importante: detener ejecución después de redirección
}

$fecha = $_REQUEST['fecha'] ?? null;

if ($fecha === null) {
    // Establecer formato para la fecha.
    date_default_timezone_set('America/El_Salvador');
    setlocale(LC_TIME, 'es_SV');

    // Arrays de meses (si los necesitas para mostrar texto)
    $meses = [
        "enero","febrero","marzo","abril","mayo","junio",
        "julio","agosto","septiembre","octubre","noviembre","diciembre"
    ];

    // Crear fecha en formato YYYY-MM-DD
    $dia = date('d');   // Día
    $mes = date('m');   // Mes
    $año = date('Y');   // Año

    $fecha = "{$año}-{$mes}-{$dia}";
}

// Es utilizado en templateEngine.inc.php
$root = '';
include 'includes/templateEngine.inc.php';

// Renderizar plantilla con Twig
$twig->display('/Produccion/BuscarProduccion.html', [
    "userName"          => $_SESSION['userNombre'] ?? '',
    "userID"            => $_SESSION['userID'] ?? '',
    "codigo_perfil"     => $_SESSION['codigo_perfil'] ?? '',
    "codigo_personal"   => $_SESSION['codigo_personal'] ?? '',
    "logo_uno"          => $_SESSION['logo_uno'] ?? '',
    "nombre_personal"   => $_SESSION['nombre_personal'] ?? '',
    "nombre_perfil"     => $_SESSION['nombre_perfil'] ?? '',
    "nombre_institucion"=> $_SESSION['nombre_institucion'] ?? '',
    "foto_personal"     => $_SESSION['foto_personal'] ?? '',
    "fecha"             => $fecha
]);