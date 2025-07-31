<?php
// Incluir tu archivo de conexión existente
$path_root = $_SERVER['DOCUMENT_ROOT']; // Ajusta esto según cómo esté definido $path_root en tu sistema
include($path_root . "/acomtus/includes/mainFunctions_conexion.php"); // Asegúrate de que esta inclusión defina $dblink

// --- Funciones de la Base de Datos (usando $dblink) ---

// Función para obtener los datos de catalogo_jornada
function getCatalogoJornada($dblink) {
    $stmt = $dblink->query("SELECT id_, TRIM(descripcion) AS descripcion, TRIM(descripcion_completa) AS descripcion_completa FROM public.catalogo_jornada ORDER BY id_ ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener los datos de catalogo_tipo_licencia_o_permiso
function getCatalogoTipoLicenciaPermiso($dblink) {
    $stmt = $dblink->query("SELECT id_, TRIM(descripcion) AS descripcion, TRIM(descripcion_completa) AS descripcion_completa FROM public.catalogo_tipo_licencia_o_permiso ORDER BY id_ ASC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para obtener todas las imágenes de jornada (usando $dblink)
function getJornadaImagenes($dblink) {
    $stmt = $dblink->query("SELECT id_, codigo, TRIM(descripcion) AS descripcion FROM catalogo_jornada_imagenes ORDER BY codigo");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// --- Manejo de peticiones AJAX ---
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        // --- ACCIONES PARA CARGAR OPCIONES EN SELECTS VÍA AJAX ---
        case 'getJornadasOptions':
        case 'getAsuetosOptions': // Nuevo: para cargar opciones de Asueto desde catalogo_jornada
        case 'getVacacionesOptions': // Nuevo: para cargar opciones de Vacaciones desde catalogo_jornada
        case 'getDescansosOptions': // Nuevo: para cargar opciones de Descanso desde catalogo_jornada
        case 'getExtra4hOptions': // Nuevo: para cargar opciones de Extra 4h desde catalogo_jornada
        case 'getNocturnidadOptions': // Nuevo: para cargar opciones de Nocturnidad desde catalogo_jornada
            echo json_encode(getCatalogoJornada($dblink));
            exit();
        case 'getLicenciasOptions':
            echo json_encode(getCatalogoTipoLicenciaPermiso($dblink));
            exit();
        // --- FIN ACCIONES ---

        case 'create':
            $response = ['success' => false, 'message' => ''];
            $codigo = $_POST['codigo'] ?? '';
            $descripcion = trim($_POST['descripcion'] ?? '');

            if (empty($codigo) || empty($descripcion)) {
                $response['message'] = 'El código y la descripción son obligatorios.';
                echo json_encode($response);
                exit();
            }

            $target_dir = $path_root . "/acomtus/img/Catalogo Jornada/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $uploaded_file = $_FILES['imagen_file'] ?? null;
            if ($uploaded_file && $uploaded_file['error'] == UPLOAD_ERR_OK) {
                $file_name = basename($descripcion);
                $target_file = $target_dir . $file_name;

                if (file_exists($target_file)) {
                    $response['message'] = 'Ya existe un archivo con ese nombre. Por favor, use un nombre diferente.';
                    echo json_encode($response);
                    exit();
                }

                if (move_uploaded_file($uploaded_file['tmp_name'], $target_file)) {
                    try {
                        $dblink->beginTransaction();
                        $stmt = $dblink->prepare("INSERT INTO catalogo_jornada_imagenes (codigo, descripcion) VALUES (:codigo, :descripcion)");
                        $stmt->execute([':codigo' => $codigo, ':descripcion' => $file_name]);
                        $dblink->commit();
                        $response['success'] = true;
                        $response['message'] = 'Imagen y registro creados exitosamente.';
                    } catch (PDOException $e) {
                        $dblink->rollBack();
                        $response['message'] = 'Error al guardar en la base de datos: ' . $e->getMessage();
                        if (file_exists($target_file)) {
                            unlink($target_file);
                        }
                    }
                } else {
                    $response['message'] = 'Error al subir la imagen.';
                }
            } else {
                 $response['message'] = 'No se recibió ninguna imagen o hubo un error en la subida. Error: ' . ($uploaded_file['error'] ?? 'N/A');
            }
            echo json_encode($response);
            exit();

        case 'read':
            $id = $_POST['id'] ?? null;
            if ($id) {
                $stmt = $dblink->prepare("SELECT id_, codigo, TRIM(descripcion) AS descripcion FROM catalogo_jornada_imagenes WHERE id_ = :id");
                $stmt->execute([':id' => $id]);
                $data = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($data);
            } else {
                echo json_encode(getJornadaImagenes($dblink));
            }
            exit();

        case 'update':
            $response = ['success' => false, 'message' => ''];
            $id = $_POST['id_'] ?? '';
            $codigo = $_POST['codigo'] ?? '';
            $descripcion = trim($_POST['descripcion'] ?? '');

            if (empty($id) || empty($codigo) || empty($descripcion)) {
                $response['message'] = 'Todos los campos son obligatorios para la actualización.';
                echo json_encode($response);
                exit();
            }

            $stmt_old_desc = $dblink->prepare("SELECT TRIM(descripcion) AS descripcion FROM catalogo_jornada_imagenes WHERE id_ = :id");
            $stmt_old_desc->execute([':id' => $id]);
            $old_descripcion = $stmt_old_desc->fetchColumn();

            $target_dir = $path_root . "/acomtus/img/Catalogo Jornada/";
            $uploaded_file = $_FILES['imagen_file'] ?? null;

            if ($uploaded_file && $uploaded_file['error'] == UPLOAD_ERR_OK) {
                $new_file_name = basename($descripcion);
                $target_file = $target_dir . $new_file_name;

                if (file_exists($target_file)) {
                    $response['message'] = 'Ya existe un archivo con ese nombre. Por favor, use un nombre diferente.';
                    echo json_encode($response);
                    exit();
                }

                if ($old_descripcion && $old_descripcion !== $new_file_name) {
                    $old_file_path = $target_dir . $old_descripcion;
                    if (file_exists($old_file_path)) {
                        unlink($old_file_path);
                    }
                } else if ($old_descripcion && $old_descripcion === $new_file_name && file_exists($target_file)) {
                    unlink($target_file);
                }

                if (move_uploaded_file($uploaded_file['tmp_name'], $target_file)) {
                    try {
                        $dblink->beginTransaction();
                        $stmt = $dblink->prepare("UPDATE catalogo_jornada_imagenes SET codigo = :codigo, descripcion = :descripcion WHERE id_ = :id");
                        $stmt->execute([':codigo' => $codigo, ':descripcion' => $new_file_name, ':id' => $id]);
                        $dblink->commit();
                        $response['success'] = true;
                        $response['message'] = 'Registro e imagen actualizados exitosamente.';
                    } catch (PDOException $e) {
                        $dblink->rollBack();
                        $response['message'] = 'Error al actualizar en la base de datos: ' . $e->getMessage();
                        if (file_exists($target_file)) {
                            unlink($target_file);
                        }
                    }
                } else {
                    $response['message'] = 'Error al subir la nueva imagen.';
                }
            } else {
                try {
                    if ($old_descripcion && $old_descripcion !== $descripcion) {
                        $old_file_path = $target_dir . $old_descripcion;
                        $new_file_path = $target_dir . basename($descripcion);
                        if (file_exists($old_file_path)) {
                            rename($old_file_path, $new_file_path);
                        }
                    }

                    $dblink->beginTransaction();
                    $stmt = $dblink->prepare("UPDATE catalogo_jornada_imagenes SET codigo = :codigo, descripcion = :descripcion WHERE id_ = :id");
                    $stmt->execute([':codigo' => $codigo, ':descripcion' => basename($descripcion), ':id' => $id]);
                    $dblink->commit();
                    $response['success'] = true;
                    $response['message'] = 'Registro actualizado exitosamente.';
                } catch (PDOException $e) {
                    $dblink->rollBack();
                    $response['message'] = 'Error al actualizar en la base de datos: ' . $e->getMessage();
                }
            }
            echo json_encode($response);
            exit();

        case 'delete':
            $response = ['success' => false, 'message' => ''];
            $id = $_POST['id'] ?? '';

            if (empty($id)) {
                $response['message'] = 'ID no proporcionado para la eliminación.';
                echo json_encode($response);
                exit();
            }

            $stmt_desc = $dblink->prepare("SELECT TRIM(descripcion) AS descripcion FROM catalogo_jornada_imagenes WHERE id_ = :id");
            $stmt_desc->execute([':id' => $id]);
            $image_name = $stmt_desc->fetchColumn();

            try {
                $dblink->beginTransaction();

                $stmt = $dblink->prepare("DELETE FROM catalogo_jornada_imagenes WHERE id_ = :id");
                $stmt->execute([':id' => $id]);

                $target_dir = $path_root . "/acomtus/img/Catalogo Jornada/";
                $file_to_delete = $target_dir . $image_name;
                if (file_exists($file_to_delete)) {
                    unlink($file_to_delete);
                }

                $dblink->commit();
                $response['success'] = true;
                $response['message'] = 'Registro e imagen eliminados exitosamente.';
            } catch (PDOException $e) {
                $dblink->rollBack();
                $response['message'] = 'Error al eliminar: ' . $e->getMessage();
            }
            echo json_encode($response);
            exit();
    }
}
?>