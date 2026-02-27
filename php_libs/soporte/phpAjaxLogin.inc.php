<?php
// 1. LIMPIEZA DE BÚFER: Vital para que no salga "JSON Inválido"
ob_start();

// Limpiar caché de estado de archivos
clearstatcache();

// 2. CABECERA CORRECTA: Debe ser JSON, no HTML
header("Content-Type: application/json;charset=utf-8");

// Inicializamos variables
$errorDbConexion = false;
$respuestaOK = false;
$mensajeError = "No se puede ejecutar la aplicación";
$contenidoOK = "";

// Ruta raíz
$path_root = trim($_SERVER['DOCUMENT_ROOT']);

// 3. INCLUDE SEGURO: Usamos include_once para evitar re-declaraciones
// Asumimos que este archivo conecta a la BD general
include_once($path_root."/acomtus/includes/mainFunctions_login.php");

global $dblink;

// Validar conexión inicial
if (isset($errorDbConexion) && $errorDbConexion == false && isset($dblink)) {
    
    // Validar datos de entrada con filtros modernos
    $accion = $_POST['accion_buscar'] ?? $_POST['accion'] ?? '';
    
    if (!empty($accion)) {
        switch ($accion) {
            case 'BuscarUser':
                // 4. SANITIZACIÓN PHP 8
                $nombre = filter_input(INPUT_POST, 'txtnombre', FILTER_SANITIZE_SPECIAL_CHARS);
                $password_usuario = $_POST['txtpassword'] ?? '';
                
                // Limpieza básica
                $nombre = trim($nombre);
                $password_usuario = trim($password_usuario);

                if (empty($nombre) || empty($password_usuario)) {
                    $mensajeError = "Usuario y contraseña requeridos.";
                    break;
                }

                try {
                    // --- CASO USUARIO ROOT ---
                    if ($nombre === 'root') {
                        // Consulta Segura
                        $query = "SELECT * FROM usuarios WHERE nombre = :nombre LIMIT 1";
                        $stmt = $dblink->prepare($query);
                        $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
                        $stmt->execute();
                        
                        $usuarioRoot = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($usuarioRoot && password_verify($password_usuario, trim($usuarioRoot['password']))) {
                            $respuestaOK = true;
                            $mensajeError = "Se ha iniciado el Sistema.";

                            $_SESSION['userNombre'] = $nombre;
                            $_SESSION['codigo_perfil'] = trim($usuarioRoot['codigo_perfil']);
                            $_SESSION['dbname'] = trim($usuarioRoot['base_de_datos']);
                            
                            // Valores Hardcodeados para Root
                            $_SESSION['logo_uno'] = "no.jpg";
                            $_SESSION['codigo_personal'] = "00";
                            $_SESSION['nombre_institucion'] = "Configuración Inicial";
                            $_SESSION['direccion'] = "Configuración Inicial";
                            $_SESSION['nombre_perfil'] = "ROOT";
                            $_SESSION['codigo_institucion'] = "ROOT";
                            $_SESSION['userID'] = "00";
                            $_SESSION['nombre_personal'] = "ROOT";
                            $_SESSION['userLogin'] = true;
                            $_SESSION["autentica"] = "SI";
                            $_SESSION['foto_personal'] = './img/nofoto.jpg';
                        } else {
                            $mensajeError = 'Este usuario no existe o contraseña incorrecta.';
                        }

                    } else {
                        // --- CASO USUARIO NORMAL ---
                        // Consulta optimizada y SEGURA (sin variables directas en el string)
                        $query = "SELECT u.nombre, u.id_usuario, u.base_de_datos, u.codigo_perfil, u.password, u.codigo_personal, u.codigo_institucion,
                                    TRIM(p.nombres) || ' ' || TRIM(p.apellidos) as nombre_personal,
                                    u.codigo_departamento_empresa, p.foto as foto_personal, p.codigo_genero,
                                    cat_perfil.descripcion as nombre_perfil
                                  FROM usuarios u
                                  INNER JOIN personal p ON p.codigo = u.codigo_personal
                                  INNER JOIN catalogo_perfil cat_perfil ON cat_perfil.codigo = u.codigo_perfil
                                  WHERE u.nombre = :nombre LIMIT 1";
                        
                        $stmt = $dblink->prepare($query);
                        $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
                        $stmt->execute();
                        
                        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($usuario) {
                            // Verificar Password
                            if (password_verify($password_usuario, trim($usuario['password']))) {
                                $respuestaOK = true;
                                $mensajeError = "Se ha consultado el registro correctamente";

                                // Guardar variables de sesión iniciales
                                $_SESSION['userLogin'] = true;
                                $_SESSION['userNombre'] = trim($usuario['nombre']);
                                $_SESSION['userID'] = $usuario['id_usuario'];
                                $_SESSION['dbname'] = trim($usuario['base_de_datos']);
                                $_SESSION['codigo_perfil'] = trim($usuario['codigo_perfil']);
                                $_SESSION['codigo_personal'] = trim($usuario['codigo_personal']);
                                $_SESSION['nombre_personal'] = trim($usuario['nombre_personal']);
                                $_SESSION['CodigoDepartamentoEmpresa'] = trim($usuario['codigo_departamento_empresa']);
                                $_SESSION['nombre_perfil'] = trim($usuario['nombre_perfil']);
                                $_SESSION['codigo_institucion'] = trim($usuario['codigo_institucion']);
                                $_SESSION['nombre_institucion'] = 'ROOT'; // Valor temporal antes de conectar a la otra BD

                                // Lógica de Fotos
                                $foto = trim($usuario['foto_personal'] ?? '');
                                $genero = trim($usuario['codigo_genero'] ?? '');
                                
                                if (empty($foto)) {
                                    $_SESSION['foto_personal'] = ($genero === '02') ? './img/avatar_femenino.png' : './img/avatar_masculino.png';
                                } else {
                                    $_SESSION['foto_personal'] = "./img/" . $foto;
                                }

                                // 5. CAMBIO DE CONTEXTO DE BASE DE DATOS
                                // Aquí se conecta a la base específica del usuario (acomtus/includes/mainFunctions_.php)
                                
                                if (file_exists($path_root."/acomtus/includes/mainFunctions_.php")) {
                                    // IMPORTANTE: Si mainFunctions_.php tiene las mismas funciones que mainFunctions_login.php,
                                    // esto fallará. Asumimos que manejan variables distintas ($dblink).
                                    include($path_root."/acomtus/includes/mainFunctions_.php");
                                    include_once($path_root."/acomtus/includes/funciones.php");
                                    
                                    // Verificamos la NUEVA conexión ($errorDbConexion se sobrescribe en el include anterior)
                                    if ($errorDbConexion == false) {
                                        // Consulta de Institución
                                        $queryInst = "SELECT inf.id_, inf.nombre, inf.direccion, inf.telefono_fijo, p.foto, p.codigo_genero,
                                                        depa.codigo, depa.nombre as nombre_departamento, 
                                                        mu.codigo, mu.codigo_departamento, mu.nombre as nombre_municipio, 
                                                        TRIM(p.nombres) || ' ' || TRIM(p.apellidos) as nombre_personal,
                                                        inf.logo_uno
                                                      FROM informacion_institucion inf
                                                      INNER JOIN personal p ON p.codigo = :cod_personal
                                                      INNER JOIN catalogo_departamento depa ON depa.codigo = inf.codigo_departamento
                                                      INNER JOIN catalogo_municipio mu ON mu.codigo = inf.codigo_municipio 
                                                        AND mu.codigo_departamento = inf.codigo_departamento
                                                      WHERE inf.id_ = :cod_inst LIMIT 1";

                                        $stmtInst = $dblink->prepare($queryInst);
                                        $stmtInst->bindValue(':cod_personal', $_SESSION['codigo_personal'], PDO::PARAM_STR);
                                        $stmtInst->bindValue(':cod_inst', $_SESSION['codigo_institucion'], PDO::PARAM_STR);
                                        $stmtInst->execute();
                                        
                                        $instData = $stmtInst->fetch(PDO::FETCH_ASSOC);

                                        if ($instData) {
                                            // Asignación final de sesión
                                            $_SESSION['nombre_institucion'] = trim($instData['nombre']);
                                            $_SESSION['direccion'] = trim($instData['direccion']);
                                            $_SESSION['telefono'] = trim($instData['telefono_fijo']);
                                            
                                            // Corrección UTF-8 (Si la BD está en LATIN1, usa mb_convert_encoding, si es UTF8, quítalo)
                                            $_SESSION['nombre_municipio'] = trim($instData['nombre_municipio']);
                                            $_SESSION['nombre_departamento'] = trim($instData['nombre_departamento']);
                                            
                                            $_SESSION['nombre_personal'] = trim($instData['nombre_personal']);
                                            $_SESSION['logo_uno'] = trim($instData['logo_uno']);
                                            $_SESSION["autentica"] = "SI";
                                            
                                            // Refuerzo de foto por si cambió en esta consulta
                                            if (!empty(trim($instData['foto'] ?? ''))) {
                                                 $_SESSION['foto_personal'] = "./img/fotos/" . trim($instData['foto']);
                                            }

                                        } else {
                                            $respuestaOK = false;
                                            $mensajeError = "No existen datos de la institución asociada.";
                                        }
                                    } else {
                                        $respuestaOK = false;
                                        $mensajeError = "Error al conectar a la base de datos de la institución.";
                                    }
                                }
                            } else {
                                $mensajeError = 'Contraseña incorrecta.';
                            }
                        } else {
                            $mensajeError = 'Usuario no encontrado.';
                        }
                    }
                } catch (PDOException $e) {
                    $mensajeError = "Error de Base de Datos: Verifique logs."; // No mostrar detalle al usuario
                }
                break;
                
            default:
                $mensajeError = 'Acción no disponible.';
                break;
        }
    } else {
        $mensajeError = 'No se especificó ninguna acción.';
    }
} else {
    $mensajeError = "No hay conexión con el servidor de base de datos.";
}

// Armamos array JSON
$salidaJson = array(
    "respuesta" => $respuestaOK,
    "mensaje" => $mensajeError,
    "contenido" => $contenidoOK
);

// 6. ENVIO LIMPIO
ob_end_clean(); // Borramos cualquier warning previo
echo json_encode($salidaJson);
exit;
?>