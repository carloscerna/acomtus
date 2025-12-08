<?php
// Script optimizado para Asistencia
// header("Content-Type: text/html;charset=iso-8859-1"); // Si usas UTF-8 en DB, mejor usa utf-8 aquí
header("Content-Type: text/html;charset=utf-8");

// Configuración Regional
date_default_timezone_set('America/El_Salvador');
setlocale(LC_TIME,'es_SV');
$hora_actual = date("h:i:s a"); 
$fecha_nomina = date("d/m/Y");

// Variables Iniciales
$respuestaOK = false;
$mensajeError = ":(";
$MensajeAsueto = "";
$contenidoOK = "";
$datos = array();
$fila_array = 0;

// Rutas
$path_root = trim($_SERVER['DOCUMENT_ROOT']);
$url_fotos = "/acomtus/img/fotos/";
$url_sin_foto = "/acomtus/img/";
$url_cat_img = "/acomtus/img/Catalogo Jornada/";

// Inclusiones
include($path_root."/acomtus/includes/mainFunctions_conexion.php");
include($path_root."/acomtus/includes/funciones.php");

// =================================================================================
// 1. OPTIMIZACIÓN DE CATÁLOGO DE IMÁGENES (Carga en Memoria Hash Map)
// =================================================================================
// Creamos un array asociativo [ 'CODIGO' => 'NOMBRE_IMAGEN' ] para búsqueda instantánea O(1)
$imagenesMap = [];
$query_j_img = "SELECT codigo, descripcion FROM catalogo_jornada_imagenes";
$resultado_j_img = $dblink->query($query_j_img);

while($row = $resultado_j_img->fetch(PDO::FETCH_ASSOC)) {
    // La clave es el código, el valor es la descripción (nombre archivo)
    // Aseguramos que el código sea string para evitar problemas
    $imagenesMap[(string)$row["codigo"]] = trim($row["descripcion"]);
}

// =================================================================================
// 2. LÓGICA PRINCIPAL
// =================================================================================

if($errorDbConexion == false){
    if(isset($_POST) && !empty($_POST)){
        if(!empty($_POST['accion_buscar'])){
            $_POST['accion'] = $_POST['accion_buscar'];
        }

        switch ($_POST['accion']) {
            
            // ---------------------------------------------------------------------
            // CASO 1: BUSCAR EMPLEADO INDIVIDUAL (Por Código)
            // ---------------------------------------------------------------------
            case 'BuscarPersonalCodigo':
                $codigo_personal = trim($_POST['codigo_personal']);
                $fecha = trim($_POST['fecha']);
                $CodigoDepartamentoEmpresa = trim($_POST['codigo_departamento_empresa']);
                $codigo_personal_encargado = trim($_POST['codigo_personal_encargado'] ?? '');

                // Consulta Principal
                $query = "SELECT p.id_personal, p.codigo, TRIM(p.nombres) as nombre, TRIM(p.apellidos) as apellido, 
                            btrim(p.nombres || CAST(' ' AS VARCHAR) || p.apellidos) AS nombre_empleado,
                            p.foto, p.codigo_genero, p.codigo_departamento_empresa
                          FROM personal p 
                          WHERE codigo = :cod AND codigo_departamento_empresa = :depto";
                
                $stmt = $dblink->prepare($query);
                $stmt->bindParam(':cod', $codigo_personal);
                $stmt->bindParam(':depto', $CodigoDepartamentoEmpresa);
                $stmt->execute();

                if($stmt->rowCount() != 0){
                    $respuestaOK = true;
                    while($listado = $stmt->fetch(PDO::FETCH_ASSOC)){
                        $datos[$fila_array]["id_"] = trim($listado['id_personal']);
                        $datos[$fila_array]["codigo"] = trim($listado['codigo']);
                        $datos[$fila_array]["codigo_departamento_empresa"] = trim($listado['codigo_departamento_empresa']);
                        $datos[$fila_array]["nombre_empleado"] = trim($listado['nombre_empleado']);
                        $datos[$fila_array]["url_foto"] = trim($listado['foto']);
                        $datos[$fila_array]["codigo_genero"] = trim($listado['codigo_genero']);
                    }
                    $datos[$fila_array]["mensajeError"] = 'Código Encontrado.';
                    $datos[$fila_array]["respuestaOK"] = true;

                    // Validar Asueto (Consulta simple)
                    $query_asueto = "SELECT descripcion FROM asuetos WHERE fecha = :fecha LIMIT 1";
                    $stmtA = $dblink->prepare($query_asueto);
                    $stmtA->bindParam(':fecha', $fecha);
                    $stmtA->execute();
                    
                    if($stmtA->rowCount() > 0){
                        $rowA = $stmtA->fetch(PDO::FETCH_ASSOC);
                        $datos[$fila_array]["descripcion"] = $rowA['descripcion'];
                        $datos[$fila_array]["asueto"] = "si";
                    } else {
                        $datos[$fila_array]["descripcion"] = "--";
                        $datos[$fila_array]["asueto"] = "no";
                    }
                } else {
                    $datos[$fila_array]["respuestaOK"] = false;
                    $datos[$fila_array]["mensajeError"] = 'Código No Existe o No Pertenece a este Departamento.';
                }

                // Buscar Asistencia Existente
                // NOTA: Usamos los mismos parámetros ya limpios
                $query_asis = "SELECT * FROM personal_asistencia 
                               WHERE codigo_personal = :cod AND fecha = :fecha";
                // Opcional: Filtrar por encargado si es estricto
                // AND codigo_personal_encargado = :encargado
                
                $stmtAsis = $dblink->prepare($query_asis);
                $stmtAsis->bindParam(':cod', $codigo_personal);
                $stmtAsis->bindParam(':fecha', $fecha);
                $stmtAsis->execute();

                $imgJornada = "#";
                $HoraExtra = 0;

                if($stmtAsis->rowCount() > 0){
                    $row = $stmtAsis->fetch(PDO::FETCH_ASSOC);
                    $HoraExtra = trim($row['hora_extra']);
                    
                    // Armado del código (Optimizada con ternario)
                    $CodigoJornadaTodas = trim($row['codigo_jornada']) . trim($row['codigo_tipo_licencia']) . 
                                          trim($row['codigo_jornada_asueto']) . trim($row['codigo_jornada_vacaciones']) . 
                                          trim($row['codigo_jornada_descanso']) . trim($row['codigo_jornada_e_4h']) . 
                                          trim($row['codigo_jornada_nocturna']);
                    
                    if($HoraExtra != 0) $CodigoJornadaTodas .= $HoraExtra;

                    // Búsqueda directa en el Mapa (Mucho más rápido)
                    if(isset($imagenesMap[$CodigoJornadaTodas])){
                        $imgJornada = $url_cat_img . $imagenesMap[$CodigoJornadaTodas];
                    }
                }
                
                $fila_array++;
                $datos[$fila_array]["imgJornada"] = $imgJornada;
                $datos[$fila_array]["HoraExtra"] = $HoraExtra;
                break;

            // ---------------------------------------------------------------------
            // CASO 2: INFORMACIÓN DE RUTA / DEPARTAMENTO
            // ---------------------------------------------------------------------
            case "BuscarPersonalRutaCodigo":
                $codigo_personal = trim($_POST['codigo_personal']);
                $CodigoDepartamentoEmpresa = trim($_POST['codigo_departamento_empresa']);
                
                if($CodigoDepartamentoEmpresa == "02"){ // Motoristas
                    $query = "SELECT u.codigo_ruta as codigo, cat_ruta.descripcion 
                              FROM usuarios u 
                              INNER JOIN catalogo_ruta cat_ruta ON cat_ruta.id_ruta = TO_NUMBER(u.codigo_ruta,'99')
                              WHERE u.codigo_personal = '$codigo_personal'";
                } else { // Otros
                    $query = "SELECT u.codigo_departamento_empresa as codigo, cat_empresa.descripcion 
                              FROM usuarios u 
                              INNER JOIN catalogo_departamento_empresa cat_empresa ON cat_empresa.id_departamento_empresa = TO_NUMBER(u.codigo_departamento_empresa,'99')
                              WHERE u.codigo_personal = '$codigo_personal'";
                }

                $consulta = $dblink->query($query);

                if($consulta->rowCount() != 0){
                    $listado = $consulta->fetch(PDO::FETCH_ASSOC);
                    $CodigoUbicacion = trim($listado['codigo']);
                    
                    $datos[$fila_array]["Descripcion"] = trim($listado['descripcion']);
                    $datos[$fila_array]["Codigo"] = $CodigoUbicacion;
                    $datos[$fila_array]["mensajeError"] = 'Código Encontrado.';
                    $datos[$fila_array]["respuestaOK"] = true;

                    // Contar empleados (Optimizado count)
                    $campoFiltro = ($CodigoDepartamentoEmpresa == "02") ? "codigo_ruta" : "codigo_departamento_empresa";
                    $query_count = "SELECT count(*) as totalempleados FROM personal WHERE $campoFiltro = '$CodigoUbicacion' AND codigo_estatus = '01'";
                    
                    $stmtCount = $dblink->query($query_count);
                    $resCount = $stmtCount->fetch(PDO::FETCH_ASSOC);
                    $datos[$fila_array]["TotalEmpleados"] = $resCount['totalempleados'];

                } else {
                    $datos[$fila_array]["respuestaOK"] = false;
                    $datos[$fila_array]["mensajeError"] = 'Ruta/Depto no asignado.';
                }
                break;

            // ---------------------------------------------------------------------
            // CASO 3: LISTADO DE EMPLEADOS POR RUTA (GRAN OPTIMIZACIÓN AQUÍ)
            // ---------------------------------------------------------------------
            case "BuscarEmpleadosPorRuta":
                $codigo_ruta = trim($_POST['CodigoRuta']);
                $CodigoDepartamentoEmpresa = trim($_POST['CodigoDepartamentoEmpresa']);
                $fecha = trim($_POST['fecha']);
                
                // A. Verificar Asueto (Una sola vez para todos)
                $MensajeAsueto = "";
                $query_asueto = "SELECT descripcion FROM asuetos WHERE fecha = '$fecha' LIMIT 1";
                $resAsueto = $dblink->query($query_asueto);
                if($resAsueto->rowCount() > 0){
                    $filaA = $resAsueto->fetch(PDO::FETCH_ASSOC);
                    $MensajeAsueto = $filaA['descripcion'];
                }

                // B. QUERY OPTIMIZADO (JOIN)
                // Traemos Empleado + Asistencia en UNA sola consulta.
                // Usamos COALESCE para manejar nulos si no hay asistencia.
                
                $filtroUbicacion = ($CodigoDepartamentoEmpresa == "02") 
                                    ? "p.codigo_ruta = '$codigo_ruta'" 
                                    : "p.codigo_departamento_empresa = '$CodigoDepartamentoEmpresa'";

                $query = "SELECT 
                            p.id_personal, p.codigo as codigo_emp, p.nombres, p.apellidos, p.foto,
                            pa.id_ as id_asistencia, pa.codigo_jornada, pa.codigo_tipo_licencia,
                            pa.codigo_jornada_asueto, pa.codigo_jornada_vacaciones, pa.codigo_jornada_descanso,
                            pa.codigo_jornada_e_4h, pa.codigo_jornada_nocturna, pa.hora_extra
                          FROM personal p
                          LEFT JOIN personal_asistencia pa 
                                 ON p.codigo = pa.codigo_personal AND pa.fecha = '$fecha'
                          WHERE $filtroUbicacion AND p.codigo_estatus = '01'
                          ORDER BY p.codigo ASC";

                $consulta = $dblink->query($query);

                if($consulta->rowCount() != 0){
                    $respuestaOK = true;
                    $mensajeError = "Registros Encontrados...";
                    
                    while($row = $consulta->fetch(PDO::FETCH_ASSOC)){
                        // Datos Personales
                        $codigo_personal = trim($row['codigo_emp']);
                        $nombre_completo = trim($row['nombres']) . " " . trim($row['apellidos']);
                        $foto = trim($row['foto']);
                        
                        // Procesar Foto
                        $rutaFoto = empty($foto) ? $url_sin_foto . 'avatar_masculino.png' : $url_fotos . $foto;

                        // Datos Asistencia (Si existen gracias al LEFT JOIN)
                        $id_asistencia = $row['id_asistencia'] ?? 0;
                        $imgJornada = $url_cat_img . "SinJornada.jpg"; // Default visual
                        
                        // Variables por defecto para el Código Separador
                        $CJ = '4'; $CTL = '1'; $CJA = '4'; $CJV = '4'; $CJD = '4'; $CJE4H = '4'; $CJN = '4'; $HE = '0';
                        $CodigoJornadaTodas = "4144444"; // Default visual

                        if($id_asistencia > 0){
                            // Si hay registro, usamos los valores de la BD
                            $CJ = trim($row['codigo_jornada']);
                            $CTL = trim($row['codigo_tipo_licencia']);
                            $CJA = trim($row['codigo_jornada_asueto']);
                            $CJV = trim($row['codigo_jornada_vacaciones']);
                            $CJD = trim($row['codigo_jornada_descanso']);
                            $CJE4H = trim($row['codigo_jornada_e_4h']);
                            $CJN = trim($row['codigo_jornada_nocturna']);
                            $HE = trim($row['hora_extra']);

                            // Construir Código para buscar Imagen
                            $CodigoJornadaTodas = $CJ . $CTL . $CJA . $CJV . $CJD . $CJE4H . $CJN;
                            if($HE != 0) $CodigoJornadaTodas .= $HE;

                            // Buscar imagen en el Mapa (Súper rápido)
                            if(isset($imagenesMap[$CodigoJornadaTodas])){
                                $imgJornada = $url_cat_img . $imagenesMap[$CodigoJornadaTodas];
                            } else {
                                // Fallback si no encuentra imagen: SinJornada
                                $imgJornada = $url_cat_img . "SinJornada.jpg";
                            }
                        }

                        // Construir Código Separador para JS (Siempre con HE al final)
                        $CodigoJornadaTodasSeparador = "$CJ.$CTL.$CJA.$CJV.$CJD.$CJE4H.$CJN.$HE";

                        // Generar HTML de la fila
                        $datos_codificados = "$rutaFoto#$imgJornada#$id_asistencia#$codigo_personal#$nombre_completo#$CodigoJornadaTodas#$CodigoJornadaTodasSeparador";
                        
                        $contenidoOK .= "<tr>
                            <td class='mx-auto text-center' style='width: 80px;'>
                                <img src='$rutaFoto' class='rounded border shadow-sm' alt='#' width='60' height='70'>
                            </td>
                            <td class='align-middle'>
                                <div class='font-weight-bold text-dark' style='font-size:1.1rem;'>$nombre_completo</div>
                                <div class='text-muted small'>Cod: <span class='font-weight-bold'>$codigo_personal</span></div>
                            </td>
                            <td class='align-middle text-right'>
                                <a data-accion='editarAsistencia' href='$datos_codificados' class='btn btn-light border shadow-sm'>
                                    <img src='$imgJornada' class='rounded' alt='' width='50' height='55'>
                                </a>
                            </td>
                        </tr>";
                    }
                } else {
                    $respuestaOK = false;
                    $mensajeError = "No hay empleados en esta ruta/departamento.";
                }
                break;

            // ---------------------------------------------------------------------
            // CASO 4: GUARDAR / ACTUALIZAR / ELIMINAR
            // ---------------------------------------------------------------------
            
            case 'EliminarAsistencia':
                $id_asistencia = trim($_POST['id_asistencia']);
                if(!empty($id_asistencia)){
                    $query = "DELETE FROM personal_asistencia WHERE id_ = :id";
                    $stmt = $dblink->prepare($query);
                    $stmt->bindParam(':id', $id_asistencia);
                    if($stmt->execute()){
                        $respuestaOK = true;
                        $mensajeError = "Asistencia reiniciada correctamente.";
                    } else {
                        $mensajeError = "Error al eliminar.";
                    }
                }
                break;

            case 'GuardarAsistencia':
            case 'ActualizarJornada':
                // Recoger variables (Optimizadas con operador coalescente ??)
                $id_ = trim($_POST["Id_"] ?? 0);
                $codigo_personal = trim($_POST["CodigoPersonal"]);
                $fecha = trim($_POST['FechaAsistencia'] ?? date("Y-m-d")); // Ojo, en actualizar viene implícita si no se envía
                $codigo_personal_encargado = trim($_POST['CodigoPersonal'] ?? ''); // Usuario logueado

                $CJ = trim($_POST["CJ"]);
                $CTL = trim($_POST["CTL"]);
                $CJA = trim($_POST["CJA"]);
                $CJV = trim($_POST["CJV"]);
                $CJD = trim($_POST["CJD"]);
                $CJE4H = trim($_POST["CJE4H"]);
                $CJN = trim($_POST["CJN"]);
                $HE = trim($_POST["lstHoraExtra"] ?? 0);

                // 1. VALIDACIÓN DE INTEGRIDAD
                // Armamos el código tal cual lo haría el sistema
                $CodigoValidar = $CJ . $CTL . $CJA . $CJV . $CJD . $CJE4H . $CJN;
                if($HE != 0) $CodigoValidar .= $HE;

                // Verificamos en el Mapa que cargamos al principio (¡Súper eficiente!)
                if(!array_key_exists($CodigoValidar, $imagenesMap)){
                    $respuestaOK = false;
                    $mensajeError = "Error Crítico: El código generado ($CodigoValidar) no existe en el catálogo. Contacte a Soporte.";
                    break; 
                }

                // 2. GUARDAR EN BASE DE DATOS
                if($id_ > 0){
                    // UPDATE
                    $query = "UPDATE personal_asistencia SET
                                codigo_jornada = '$CJ', codigo_tipo_licencia = '$CTL',
                                codigo_jornada_asueto = '$CJA', codigo_jornada_vacaciones = '$CJV',
                                codigo_jornada_descanso = '$CJD', codigo_jornada_e_4h = '$CJE4H',
                                codigo_jornada_nocturna = '$CJN', hora_extra = '$HE',
                                codigo_personal_encargado = '$codigo_personal_encargado'
                              WHERE id_ = '$id_'";
                } else {
                    // INSERT (Nuevo registro, necesitamos la fecha del listado)
                    // Nota: En tu flujo actual, PorNomina.js maneja fechas globales. 
                    // Asegúrate de enviar la fecha correcta en $_POST['FechaListadoEmpleados'] o similar.
                    // Para este ejemplo asumo que ya validaste que existe la fecha.
                    $fechaInsert = trim($_POST['FechaListadoEmpleados'] ?? date("Y-m-d"));
                    $hora = date("h:i:s a");
                    
                    $query = "INSERT INTO personal_asistencia 
                              (codigo_personal, fecha, hora, codigo_jornada, codigo_tipo_licencia, codigo_jornada_asueto, 
                               codigo_jornada_vacaciones, codigo_jornada_descanso, codigo_jornada_e_4h, 
                               codigo_jornada_nocturna, hora_extra, codigo_personal_encargado)
                              VALUES 
                              ('$codigo_personal', '$fechaInsert', '$hora', '$CJ', '$CTL', '$CJA', 
                               '$CJV', '$CJD', '$CJE4H', '$CJN', '$HE', '$codigo_personal_encargado')";
                }

                if($dblink->query($query)){
                    $respuestaOK = true;
                    $mensajeError = "Registro guardado correctamente.";
                } else {
                    $mensajeError = "Error SQL al guardar.";
                }
                break;
			// ---------------------------------------------------------------------
            // CASO FALTANTE: PROCESAR DATOS PARA EDITAR (DESARMAR EL STRING #)
            // ---------------------------------------------------------------------
            case 'EditarJornada':
                // Recibimos la cadena larga separada por #
                $Todos = $_POST['Id_'];
                
                // La separamos en un array
                $VariablesTabla = explode("#", $Todos);
                
                // Verificamos que venga completa (mínimo 7 posiciones)
                if(count($VariablesTabla) >= 7) {
                    $datos[$fila_array]["Foto"] = $VariablesTabla[0];
                    $datos[$fila_array]["ImgJornada"] = $VariablesTabla[1];
                    $datos[$fila_array]["Id_"] = $VariablesTabla[2];
                    $datos[$fila_array]["CodigoPersonal"] = $VariablesTabla[3];
                    $datos[$fila_array]["NombreCompleto"] = $VariablesTabla[4];
                    $datos[$fila_array]["CodigoJornadaTodas"] = $VariablesTabla[5];
                    $datos[$fila_array]["CodigoJornadaTodasSeparador"] = $VariablesTabla[6];
                    
                    // Incrementamos fila (importante para que el JSON salga como array [0])
                    $fila_array++;
                } else {
                    // Si el string viene roto o incompleto
                    $respuestaOK = false;
                    $mensajeError = "Error: Datos del empleado incompletos.";
                }
                break;
            case 'BuscarJornada':
            case 'BuscarTipoLicencia':
                // Estos casos simples los puedes dejar o convertirlos a JSON directo si no tienen lógica compleja
                break;
        }
    }
} else {
    $mensajeError = 'No se puede establecer conexión con la base de datos';
}

// Salida JSON
if(isset($_POST['accion']) && ($_POST['accion'] == 'BuscarPersonalCodigo' || $_POST['accion'] == 'BuscarPersonalRutaCodigo' || $_POST['accion'] == 'EditarJornada')) {
    echo json_encode($datos);
} else {
    $salidaJson = array(
        "respuesta" => $respuestaOK,
        "mensaje" => $mensajeError,
        "contenido" => $contenidoOK,
        "mensajeAsueto" => $MensajeAsueto
    );
    echo json_encode($salidaJson);
}
?>