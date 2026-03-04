<?php
// Script optimizado con Inicialización Masiva - Compatible con PHP 8.x
header("Content-Type: text/html;charset=utf-8");

date_default_timezone_set('America/El_Salvador');
setlocale(LC_TIME,'es_SV');
$hora_actual = date("h:i:s a"); 
$fecha_nomina = date("d/m/Y");

$respuestaOK = false;
$mensajeError = ":(";
$MensajeAsueto = "";
$contenidoOK = "";
$datos = array();
$fila_array = 0;

$path_root = trim($_SERVER['DOCUMENT_ROOT']);
$url_fotos = "/acomtus/img/fotos/";
$url_sin_foto = "/acomtus/img/";
$url_cat_img = "/acomtus/img/Catalogo Jornada/";

include($path_root."/acomtus/includes/mainFunctions_conexion.php");
include($path_root."/acomtus/includes/funciones.php");

// 1. CARGA MAPA DE IMÁGENES (Memoria)
$imagenesMap = [];
$query_j_img = "SELECT codigo, descripcion FROM catalogo_jornada_imagenes";
$resultado_j_img = $dblink->query($query_j_img);
while($row = $resultado_j_img->fetch(PDO::FETCH_ASSOC)) {
    $imagenesMap[(string)$row["codigo"]] = trim($row["descripcion"]);
}

if($errorDbConexion == false){
    if(isset($_POST) && !empty($_POST)){
        
        $accion = trim($_POST['accion_buscar'] ?? $_POST['accion'] ?? '');

        switch ($accion) {
            
            // ---------------------------------------------------------------------
            // CASO 1: BUSCAR EMPLEADO INDIVIDUAL
            // ---------------------------------------------------------------------
            case 'BuscarPersonalCodigo':
                $codigo_personal = trim($_POST['codigo_personal'] ?? '');
                $fecha = trim($_POST['fecha'] ?? '');
                $CodigoDepartamentoEmpresa = trim($_POST['codigo_departamento_empresa'] ?? '');
                
                // Buscar Datos Personales
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
                        $datos[$fila_array]["id_"] = trim((string)$listado['id_personal']);
                        $datos[$fila_array]["codigo"] = trim((string)$listado['codigo']);
                        $datos[$fila_array]["codigo_departamento_empresa"] = trim((string)$listado['codigo_departamento_empresa']);
                        $datos[$fila_array]["nombre_empleado"] = trim((string)$listado['nombre_empleado']);
                        $datos[$fila_array]["url_foto"] = trim((string)$listado['foto']);
                        $datos[$fila_array]["codigo_genero"] = trim((string)$listado['codigo_genero']);
                    }
                    $datos[$fila_array]["mensajeError"] = 'Código Encontrado.';
                    $datos[$fila_array]["respuestaOK"] = true;

                    // Validar Asueto
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

                // Buscar Asistencia
                $query_asis = "SELECT * FROM personal_asistencia WHERE codigo_personal = :cod AND fecha = :fecha";
                $stmtAsis = $dblink->prepare($query_asis);
                $stmtAsis->bindParam(':cod', $codigo_personal);
                $stmtAsis->bindParam(':fecha', $fecha);
                $stmtAsis->execute();

                $imgJornada = "#";
                $HoraExtra = 0;
                $IdAsistencia = 0;

                if($stmtAsis->rowCount() > 0){
                    $row = $stmtAsis->fetch(PDO::FETCH_ASSOC);
                    $HoraExtra = trim((string)$row['hora_extra']);
                    $IdAsistencia = trim((string)$row['id_']);
                    
                    $CodigoJornadaTodas = trim((string)$row['codigo_jornada']) . trim((string)$row['codigo_tipo_licencia']) . 
                                          trim((string)$row['codigo_jornada_asueto']) . trim((string)$row['codigo_jornada_vacaciones']) . 
                                          trim((string)$row['codigo_jornada_descanso']) . trim((string)$row['codigo_jornada_e_4h']) . 
                                          trim((string)$row['codigo_jornada_nocturna']);
                    if($HoraExtra != 0) $CodigoJornadaTodas .= $HoraExtra;

                    if(isset($imagenesMap[$CodigoJornadaTodas])){
                        $imgJornada = $url_cat_img . $imagenesMap[$CodigoJornadaTodas];
                    }
                }
                
                $fila_array++;
                $datos[$fila_array]["imgJornada"] = $imgJornada;
                $datos[$fila_array]["HoraExtra"] = $HoraExtra;
                break;

            // ---------------------------------------------------------------------
            // CASO 2: INFO RUTA
            // ---------------------------------------------------------------------
            case "BuscarPersonalRutaCodigo":
                $codigo_personal = trim($_POST['codigo_personal'] ?? '');
                $CodigoDepartamentoEmpresa = trim($_POST['codigo_departamento_empresa'] ?? '');
                
                if($CodigoDepartamentoEmpresa == "02"){
                    $query = "SELECT u.codigo_ruta as codigo, cat_ruta.descripcion 
                              FROM usuarios u 
                              INNER JOIN catalogo_ruta cat_ruta ON cat_ruta.id_ruta = TO_NUMBER(u.codigo_ruta,'99')
                              WHERE u.codigo_personal = '$codigo_personal'";
                } else {
                    $query = "SELECT u.codigo_departamento_empresa as codigo, cat_empresa.descripcion 
                              FROM usuarios u 
                              INNER JOIN catalogo_departamento_empresa cat_empresa ON cat_empresa.id_departamento_empresa = TO_NUMBER(u.codigo_departamento_empresa,'99')
                              WHERE u.codigo_personal = '$codigo_personal'";
                }

                $consulta = $dblink->query($query);

                if($consulta->rowCount() != 0){
                    $listado = $consulta->fetch(PDO::FETCH_ASSOC);
                    $CodigoUbicacion = trim((string)$listado['codigo']);
                    $datos[$fila_array]["Descripcion"] = trim((string)$listado['descripcion']);
                    $datos[$fila_array]["Codigo"] = $CodigoUbicacion;
                    $datos[$fila_array]["mensajeError"] = 'Código Encontrado.';
                    $datos[$fila_array]["respuestaOK"] = true;

                    $campoFiltro = ($CodigoDepartamentoEmpresa == "02") ? "codigo_ruta" : "codigo_departamento_empresa";
                    $query_count = "SELECT count(*) as totalempleados FROM personal WHERE $campoFiltro = '$CodigoUbicacion' AND codigo_estatus = '01'";
                    $stmtCount = $dblink->query($query_count);
                    $resCount = $stmtCount->fetch(PDO::FETCH_ASSOC);
                    $datos[$fila_array]["TotalEmpleados"] = $resCount['totalempleados'];
                } else {
                    $datos[$fila_array]["respuestaOK"] = false;
                    $datos[$fila_array]["mensajeError"] = 'Ruta no asignada.';
                }
                break;

            // ---------------------------------------------------------------------
            // CASO 3: LISTADO DE EMPLEADOS
            // ---------------------------------------------------------------------
            case "BuscarEmpleadosPorRuta":
                $codigo_ruta = trim($_POST['CodigoRuta'] ?? '');
                $CodigoDepartamentoEmpresa = trim($_POST['CodigoDepartamentoEmpresa'] ?? '');
                $fecha = trim($_POST['fecha'] ?? '');
                $codigo_personal_encargado = trim($_POST['codigo_personal_encargado'] ?? '');
                
                $MensajeAsueto = "";
                $esAsueto = false;
                $def_CTL = '1'; 
                $def_CJA = '4'; 

                $query_asueto = "SELECT descripcion FROM asuetos WHERE fecha = '$fecha' LIMIT 1";
                $resAsueto = $dblink->query($query_asueto);
                if($resAsueto->rowCount() > 0){
                    $filaA = $resAsueto->fetch(PDO::FETCH_ASSOC);
                    $MensajeAsueto = $filaA['descripcion'];
                    $esAsueto = true;
                    $def_CTL = '16'; 
                    $def_CJA = '4'; 
                }

                $filtroInsert = ($CodigoDepartamentoEmpresa == "02") 
                                ? "codigo_ruta = '$codigo_ruta'" 
                                : "codigo_departamento_empresa = '$CodigoDepartamentoEmpresa'";

                $queryInsertMasivo = "
                    INSERT INTO personal_asistencia 
                    (codigo_personal, fecha, hora, codigo_jornada, codigo_tipo_licencia, 
                     codigo_jornada_asueto, codigo_jornada_vacaciones, codigo_jornada_descanso, 
                     codigo_jornada_e_4h, codigo_jornada_nocturna, hora_extra, codigo_personal_encargado)
                    SELECT 
                        p.codigo, 
                        '$fecha', 
                        '$hora_actual', 
                        '4', '$def_CTL', '$def_CJA', '4', '4', '4', '4', 0, 
                        '$codigo_personal_encargado'
                    FROM personal p
                    WHERE $filtroInsert 
                      AND p.codigo_estatus = '01'
                      AND NOT EXISTS (
                          SELECT 1 FROM personal_asistencia pa 
                          WHERE pa.codigo_personal = p.codigo AND pa.fecha = '$fecha'
                      )
                ";
                $dblink->query($queryInsertMasivo);

                $filtroSelect = ($CodigoDepartamentoEmpresa == "02") 
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
                          WHERE $filtroSelect AND p.codigo_estatus = '01'
                          ORDER BY p.codigo ASC";

                $consulta = $dblink->query($query);

                if($consulta->rowCount() != 0){
                    $respuestaOK = true;
                    $mensajeError = "Registros Encontrados...";
                    
                    while($row = $consulta->fetch(PDO::FETCH_ASSOC)){
                        $codigo_personal = trim((string)$row['codigo_emp']);
                        $nombre_completo = trim((string)$row['nombres']) . " " . trim((string)$row['apellidos']);
                        $foto = trim((string)$row['foto']);
                        $rutaFoto = empty($foto) ? $url_sin_foto . 'avatar_masculino.png' : $url_fotos . $foto;

                        $id_asistencia = $row['id_asistencia']; 
                        $CJ = trim((string)$row['codigo_jornada']);
                        $CTL = trim((string)$row['codigo_tipo_licencia']);
                        $CJA = trim((string)$row['codigo_jornada_asueto']);
                        $CJV = trim((string)$row['codigo_jornada_vacaciones']);
                        $CJD = trim((string)$row['codigo_jornada_descanso']);
                        $CJE4H = trim((string)$row['codigo_jornada_e_4h']);
                        $CJN = trim((string)$row['codigo_jornada_nocturna']);
                        $HE = trim((string)$row['hora_extra']);

                        $CodigoJornadaTodas = $CJ . $CTL . $CJA . $CJV . $CJD . $CJE4H . $CJN;
                        if($HE != 0) $CodigoJornadaTodas .= $HE;

                        if(isset($imagenesMap[$CodigoJornadaTodas])){
                            $imgJornada = $url_cat_img . $imagenesMap[$CodigoJornadaTodas];
                        } else {
                            $imgJornada = $url_cat_img . "SinJornada.jpg";
                        }

                        $CodigoJornadaTodasSeparador = "$CJ.$CTL.$CJA.$CJV.$CJD.$CJE4H.$CJN.$HE";
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
                    $mensajeError = "No hay empleados activos en esta ruta/departamento.";
                }
                break;

            // ---------------------------------------------------------------------
            // CASO 4: PROCESAR DATOS PARA EDITAR
            // ---------------------------------------------------------------------
            case 'EditarJornada':
                $Todos = $_POST['Id_'] ?? '';
                $VariablesTabla = explode("#", $Todos);
                if(count($VariablesTabla) >= 7) {
                    $datos[$fila_array]["Foto"] = $VariablesTabla[0];
                    $datos[$fila_array]["ImgJornada"] = $VariablesTabla[1];
                    $datos[$fila_array]["Id_"] = $VariablesTabla[2];
                    $datos[$fila_array]["CodigoPersonal"] = $VariablesTabla[3];
                    $datos[$fila_array]["NombreCompleto"] = $VariablesTabla[4];
                    $datos[$fila_array]["CodigoJornadaTodas"] = $VariablesTabla[5];
                    $datos[$fila_array]["CodigoJornadaTodasSeparador"] = $VariablesTabla[6];
                    $fila_array++;
                } else {
                    $respuestaOK = false;
                    $mensajeError = "Error: Datos incompletos.";
                }
                break;

            // ---------------------------------------------------------------------
            // CASO 5: GUARDAR / ACTUALIZAR / ELIMINAR
            // ---------------------------------------------------------------------
            case 'EliminarAsistencia':
                $id_asistencia = trim($_POST['id_asistencia'] ?? '');
                if(!empty($id_asistencia)){
                    $query = "DELETE FROM personal_asistencia WHERE id_ = :id";
                    $stmt = $dblink->prepare($query);
                    $stmt->bindParam(':id', $id_asistencia);
                    if($stmt->execute()){
                        $respuestaOK = true;
                        $mensajeError = "Asistencia reiniciada (se regenerará al recargar).";
                    } else {
                        $mensajeError = "Error al eliminar.";
                    }
                }
                break;

            case 'GuardarAsistencia':
            case 'ActualizarJornada':
                // Aplicamos el operador ?? '' para evitar el error de PHP 8.x
                $id_ = trim($_POST["Id_"] ?? '0');
                $codigo_personal = trim($_POST["CodigoPersonal"] ?? '');
                $fecha = trim($_POST['FechaAsistencia'] ?? date("Y-m-d"));
                $codigo_personal_encargado = trim($_POST['CodigoPersonal'] ?? '');

                $CJ = trim($_POST["CJ"] ?? '');
                $CTL = trim($_POST["CTL"] ?? '');
                $CJA = trim($_POST["CJA"] ?? '');
                $CJV = trim($_POST["CJV"] ?? '');
                $CJD = trim($_POST["CJD"] ?? '');
                $CJE4H = trim($_POST["CJE4H"] ?? '');
                $CJN = trim($_POST["CJN"] ?? '');
                $HE = trim($_POST["lstHoraExtra"] ?? '0');

                // VALIDACIÓN
                $CodigoValidar = $CJ . $CTL . $CJA . $CJV . $CJD . $CJE4H . $CJN;
                if($HE != 0) $CodigoValidar .= $HE;

                if(!array_key_exists($CodigoValidar, $imagenesMap)){
                    $respuestaOK = false;
                    $mensajeError = "Error Crítico: El código generado ($CodigoValidar) no existe en el catálogo.";
                    break; 
                }

                // GUARDAR (UPDATE o INSERT)
                if($id_ > 0){
                    $query = "UPDATE personal_asistencia SET
                                codigo_jornada = '$CJ', codigo_tipo_licencia = '$CTL',
                                codigo_jornada_asueto = '$CJA', codigo_jornada_vacaciones = '$CJV',
                                codigo_jornada_descanso = '$CJD', codigo_jornada_e_4h = '$CJE4H',
                                codigo_jornada_nocturna = '$CJN', hora_extra = '$HE',
                                codigo_personal_encargado = '$codigo_personal_encargado'
                              WHERE id_ = '$id_'";
                } else {
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
                    $mensajeError = "Registro guardado.";
                } else {
                    $mensajeError = "Error SQL.";
                }
                break;
        }
    }
} else {
    $mensajeError = 'Sin conexión BD';
}

$accion_final = trim($_POST['accion_buscar'] ?? $_POST['accion'] ?? '');

if($accion_final == 'BuscarPersonalCodigo' || $accion_final == 'BuscarPersonalRutaCodigo' || $accion_final == 'EditarJornada') {
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