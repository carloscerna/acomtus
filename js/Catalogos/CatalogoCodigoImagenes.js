// js/Catalogos/CatalogoCodigoImagenes.js

let jornadaImagenesDataTable; // Variable global para la instancia de DataTables

$(document).ready(function() {
    // Inicializar DataTables al cargar la página
    jornadaImagenesDataTable = $('#jornadaImagenesDataTable').DataTable({
        "processing": true,
        "serverSide": false, // Cambiar a true si implementas procesamiento del lado del servidor
        "ajax": {
            "url": "php_libs/soporte/Catalogos/CatalogoCodigoImagenes.php",
            "type": "POST",
            "data": { action: 'read' },
            "dataSrc": function (json) {
                const basePath = "img/Catalogo Jornada/"; // Ruta base sin codificar

                return json.map(item => {
                    const encodedImageName = encodeURIComponent(item.descripcion);
                    item.imageUrl = `${basePath}${encodedImageName}`;
                    return item;
                });
            }
        },
        "columns": [
            { "data": "id_" },
            { "data": "codigo" },
            { "data": "descripcion" },
            {
                "data": "imageUrl",
                "render": function(data, type, row) {
                    if (data) {
                        return `<img src="${data}" alt="${row.descripcion}" width="50">`;
                    }
                    return '';
                }
            },
            {
                "data": null,
                "render": function(data, type, row) {
                    return `
                        <button class="btn btn-warning btn-sm edit-btn" data-id="${row.id_}" data-toggle="modal" data-target="#jornadaImagenesModal">Editar</button>
                        <button class="btn btn-danger btn-sm delete-btn" data-id="${row.id_}" data-imagen-nombre="${row.descripcion}">Eliminar</button>
                    `;
                }
            }
        ],
        "language": {
            "url": "php_libs/idioma/es_es.json" // Traducción al español
        }
    });

    // Función para generar el código dinámicamente (AHORA SOLO CON ID_ NUMÉRICOS)
    function generateCode() {
        // Obtenemos los ID_ seleccionados, o "" si no hay selección
        let idJornada = $('#codigo_jornada').val() || '';
        let idLicencia = $('#codigo_licencia').val() || '';
        let idAsueto = $('#codigo_asueto').val() || '';
        let idVacaciones = $('#codigo_vacaciones').val() || '';
        let idDescanso = $('#codigo_descanso').val() || '';
        let idExtra4h = $('#codigo_extra4h').val() || '';
        let idNocturnidad = $('#codigo_nocturnidad').val() || '';
        let cantidadHorasExtras = $('#cantidad_horas_extras').val() || '';

        // Construir el código concatenando los ID_ numéricos.
        let generatedCodeParts = [];

        if (idJornada) {
            generatedCodeParts.push(idJornada);
        }
        if (idLicencia) {
            generatedCodeParts.push(idLicencia);
        }
        if (idAsueto) {
            generatedCodeParts.push(idAsueto);
        }
        if (idVacaciones) {
            generatedCodeParts.push(idVacaciones);
        }
        if (idDescanso) {
            generatedCodeParts.push(idDescanso);
        }
        if (idExtra4h) {
            generatedCodeParts.push(idExtra4h);
        }
        if (idNocturnidad) {
            generatedCodeParts.push(idNocturnidad);
        }
        // Las horas extras se añaden directamente, sin prefijo especial ya que es numérico
        if (cantidadHorasExtras && cantidadHorasExtras >= 1 && cantidadHorasExtras <= 4) {
            generatedCodeParts.push(cantidadHorasExtras);
        }

        let generatedCode = generatedCodeParts.join('');

        $('#codigo').val(generatedCode);
    }

    // Eventos para regenerar el código al cambiar los selects o input
    $('#codigo_jornada, #codigo_licencia, #codigo_asueto, #codigo_vacaciones, #codigo_descanso, #codigo_extra4h, #codigo_nocturnidad, #cantidad_horas_extras').on('change keyup', generateCode);

    // Previsualización de la imagen
    $('#imagen_file').on('change', function() {
        const [file] = this.files;
        if (file) {
            $('#image_preview').attr('src', URL.createObjectURL(file)).show();
            $('#descripcion').val(file.name);
        } else {
            $('#image_preview').hide();
            $('#descripcion').val('');
        }
    });

    // --- CRUD Operations ---

    // CREATE / UPDATE
    $('#jornadaImagenesForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const action = $('#jornada_imagen_id').val() ? 'update' : 'create';
        formData.append('action', action);

        if (action === 'create' && (!formData.get('imagen_file') || formData.get('imagen_file').name === '')) {
            Swal.fire('Error', 'Debe seleccionar un archivo de imagen.', 'error');
            return;
        }

        if (formData.get('descripcion') === '') {
            Swal.fire('Error', 'La descripción (nombre del archivo de imagen) no puede estar vacía.', 'error');
            return;
        }

        $.ajax({
            url: 'php_libs/soporte/Catalogos/CatalogoCodigoImagenes.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                const res = JSON.parse(response);
                if (res.success) {
                    Swal.fire('Éxito', res.message, 'success');
                    $('#jornadaImagenesModal').modal('hide'); // Ocultar el modal
                    jornadaImagenesDataTable.ajax.reload(null, false); // Recargar DataTables sin resetear la paginación
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                Swal.fire('Error', 'Ocurrió un error en la comunicación con el servidor: ' + error, 'error');
            }
        });
    });

    // EDIT (llenar formulario con datos para edición)
    $(document).on('click', '.edit-btn', function() {
        const id = $(this).data('id');
        // Limpiar el formulario y resetear para un nuevo llenado
        $('#jornadaImagenesForm')[0].reset();
        $('#jornada_imagen_id').val(''); // Asegurarse de que el ID esté limpio antes de cargar
        $('#image_preview').hide().attr('src', '');
        $('#jornadaImagenesModalLabel').text('Editar Imagen de Jornada'); // Cambiar título del modal

        $.ajax({
            url: 'php_libs/soporte/Catalogos/CatalogoCodigoImagenes.php',
            type: 'POST',
            data: { action: 'read', id: id },
            success: function(response) {
                const data = JSON.parse(response);
                if (data) {
                    $('#jornada_imagen_id').val(data.id_);
                    $('#codigo').val(data.codigo);
                    $('#descripcion').val(data.descripcion);
                    const basePath = "img/Catalogo Jornada/";
                    const encodedImageName = encodeURIComponent(data.descripcion);
                    const imageUrl = `${basePath}${encodedImageName}`;
                    $('#image_preview').attr('src', imageUrl).show();

                    // Deshabilitar TODOS los selects y el input de cantidad de horas al editar
                    $('#codigo_jornada, #codigo_licencia, #codigo_asueto, #codigo_vacaciones, #codigo_descanso, #codigo_extra4h, #codigo_nocturnidad, #cantidad_horas_extras').prop('disabled', true);
                    $('#generate_code_section').hide(); // Ocultar la sección de generación de código
                }
            },
            error: function(xhr, status, error) {
                Swal.fire('Error', 'Error al obtener datos para editar: ' + error, 'error');
            }
        });
    });

    // DELETE
    $(document).on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        const imagenNombre = $(this).data('imagen-nombre');

        Swal.fire({
            title: '¿Estás seguro?',
            text: `¡No podrás revertir esto! Se eliminará el registro y la imagen "${imagenNombre}".`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'php_libs/soporte/Catalogos/CatalogoCodigoImagenes.php',
                    type: 'POST',
                    data: { action: 'delete', id: id },
                    success: function(response) {
                        const res = JSON.parse(response);
                        if (res.success) {
                            Swal.fire('Eliminado', res.message, 'success');
                            jornadaImagenesDataTable.ajax.reload(null, false); // Recargar DataTables
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        Swal.fire('Error', 'Ocurrió un error al eliminar: ' + error, 'error');
                    }
                });
            }
        });
    });

    // --- FUNCIONES PARA CARGAR OPCIONES DE SELECTS VÍA AJAX ---

    // Función genérica para cargar selects desde catalogo_jornada
    function loadJornadaRelatedOptions(selectId, actionName, defaultValue = '') {
        $.ajax({
            url: "php_libs/soporte/Catalogos/CatalogoCodigoImagenes.php",
            type: "POST",
            data: { action: actionName },
            dataType: "json",
            success: function(data) {
                let currentSelect = $(selectId);
                currentSelect.find('option:not(:first)').remove(); // Limpiar opciones existentes (excepto la primera "Seleccione...")
                $.each(data, function(index, item) {
                    currentSelect.append(
                        $('<option>', {
                            value: item.id_,
                            'data-id': item.id_,
                            text: `${item.descripcion} - ${item.descripcion_completa}`
                        })
                    );
                });
                // Establecer valor por defecto si se proporciona
                if (defaultValue) {
                    currentSelect.val(defaultValue);
                } else {
                    currentSelect.val(''); // Asegurarse de que no haya selección si no hay default
                }
                generateCode(); // Regenerar código después de cargar
            },
            error: function(xhr, status, error) {
                console.error(`Error loading ${selectId} options from ${actionName}:`, error);
                Swal.fire('Error', `No se pudieron cargar las opciones para ${selectId}.`, 'error');
            }
        });
    }

    // Función para cargar opciones de Licencia/Permiso
    function loadLicenciasOptions(defaultValue = '') {
        $.ajax({
            url: "php_libs/soporte/Catalogos/CatalogoCodigoImagenes.php",
            type: "POST",
            data: { action: 'getLicenciasOptions' },
            dataType: "json",
            success: function(data) {
                let licenciaSelect = $('#codigo_licencia');
                licenciaSelect.find('option:not(:first)').remove();
                $.each(data, function(index, licencia) {
                    licenciaSelect.append(
                        $('<option>', {
                            value: licencia.id_,
                            'data-id': licencia.id_,
                            text: `${licencia.descripcion} - ${licencia.descripcion_completa}`
                        })
                    );
                });
                if (defaultValue) {
                    licenciaSelect.val(defaultValue);
                } else {
                    licenciaSelect.val('');
                }
                generateCode();
            },
            error: function(xhr, status, error) {
                console.error("Error loading Licencias options:", error);
                Swal.fire('Error', 'No se pudieron cargar las opciones de Licencia/Permiso.', 'error');
            }
        });
    }

    // --- EVENTOS DEL MODAL Y BOTÓN NUEVO ---

    // Este evento se dispara al cargar la página por primera vez
    // y al cerrar el modal (para resetearlo para un posible nuevo registro)
    $('#jornadaImagenesModal').on('hidden.bs.modal', function () {
        $('#jornadaImagenesForm')[0].reset();
        $('#jornada_imagen_id').val('');
        $('#image_preview').hide().attr('src', '');
        $('#jornadaImagenesModalLabel').text('Formulario de Imagen de Jornada');
        // Habilitar y mostrar los selects y el input de generación de código
        $('#codigo_jornada, #codigo_licencia, #codigo_asueto, #codigo_vacaciones, #codigo_descanso, #codigo_extra4h, #codigo_nocturnidad, #cantidad_horas_extras').prop('disabled', false);
        $('#generate_code_section').show();

        // Cargar opciones y preseleccionar por defecto ID_ = 4 para los nuevos
        loadJornadaRelatedOptions('#codigo_jornada', 'getJornadasOptions', '2'); // Jornada por defecto 2
        loadLicenciasOptions('1'); // Licencia por defecto 1
        loadJornadaRelatedOptions('#codigo_asueto', 'getAsuetosOptions', '4');
        loadJornadaRelatedOptions('#codigo_vacaciones', 'getVacacionesOptions', '4');
        loadJornadaRelatedOptions('#codigo_descanso', 'getDescansosOptions', '4');
        loadJornadaRelatedOptions('#codigo_extra4h', 'getExtra4hOptions', '4');
        loadJornadaRelatedOptions('#codigo_nocturnidad', 'getNocturnidadOptions', '4');
    });

    // Evento para el botón "Nuevo Registro"
    $('#newRecordBtn').on('click', function() {
        $('#jornadaImagenesForm')[0].reset();
        $('#jornada_imagen_id').val('');
        $('#image_preview').hide().attr('src', '');
        $('#jornadaImagenesModalLabel').text('Crear Nueva Imagen de Jornada');
        // Habilitar y mostrar los selects y el input de generación de código
        $('#codigo_jornada, #codigo_licencia, #codigo_asueto, #codigo_vacaciones, #codigo_descanso, #codigo_extra4h, #codigo_nocturnidad, #cantidad_horas_extras').prop('disabled', false);
        $('#generate_code_section').show();

        // Cargar opciones y preseleccionar por defecto ID_ = 4 para los nuevos
        loadJornadaRelatedOptions('#codigo_jornada', 'getJornadasOptions', '2');
        loadLicenciasOptions('1');
        loadJornadaRelatedOptions('#codigo_asueto', 'getAsuetosOptions', '4');
        loadJornadaRelatedOptions('#codigo_vacaciones', 'getVacacionesOptions', '4');
        loadJornadaRelatedOptions('#codigo_descanso', 'getDescansosOptions', '4');
        loadJornadaRelatedOptions('#codigo_extra4h', 'getExtra4hOptions', '4');
        loadJornadaRelatedOptions('#codigo_nocturnidad', 'getNocturnidadOptions', '4');
    });

    // Llamar a las funciones de carga de opciones al cargar el documento por primera vez
    loadJornadaRelatedOptions('#codigo_jornada', 'getJornadasOptions', '2');
    loadLicenciasOptions('1');
    loadJornadaRelatedOptions('#codigo_asueto', 'getAsuetosOptions', '4');
    loadJornadaRelatedOptions('#codigo_vacaciones', 'getVacacionesOptions', '4');
    loadJornadaRelatedOptions('#codigo_descanso', 'getDescansosOptions', '4');
    loadJornadaRelatedOptions('#codigo_extra4h', 'getExtra4hOptions', '4');
    loadJornadaRelatedOptions('#codigo_nocturnidad', 'getNocturnidadOptions', '4');
});