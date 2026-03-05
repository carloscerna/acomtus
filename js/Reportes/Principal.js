// ============================================================
// Principal.js — Versión optimizada
// Cambios aplicados:
//   FIX #1: Eliminada la doble llamada AJAX (totales desde drawCallback)
//   FIX #2: Datos de impresión enviados por POST (formulario oculto)
//   FIX #3: Variables declaradas con let/const — eliminadas variables globales involuntarias
//   FIX #4: console.log eliminados
//   FIX #5: delimitNumbers reemplazado por formatNumber() con Intl.NumberFormat
//   FIX #6: goImprmirProduccionDiaria y goImprmirProduccionDetalleMotorista
//           refactorizados en una sola función reutilizable
//   FIX #7: Lectura de tabla para imprimir usa datos de DataTables, no el DOM
//   FIX #9: IDs renombrados en el HTML (accion_dif, id_user_dif, accion_pm, id_user_pm)
//           — los selectores de JS se actualizan acá también
// ============================================================

// ── Variables globales controladas ──────────────────────────
let id_       = 0;
let accion    = "todos";
let tabla     = "";
let miselect  = "";
let today     = "";
let OptBuscarUP = "Todo";
let OptBuscarPM = "Todo";
let NombreInstitucion = "";

// ── Menú contextual por motorista ───────────────────────────
const defaultContentMenuPorMotorista =
    '<div class="dropdown">'
  + '<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown"><i class="fas fa-wrench"></i></button>'
  + '<div class="dropdown-menu">'
  + '<a class="verPorMotorista dropdown-item" href="#"><i class="fas fa-search"></i> Ver</a>'
  + '</div></div>';

// ── Menú contextual por unidad ───────────────────────────────
const defaultContentMenuPorNumeroUnidad =
    '<div class="dropdown">'
  + '<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown"><i class="fas fa-wrench"></i></button>'
  + '<div class="dropdown-menu">'
  + '<a class="verPorNumeroUnidad dropdown-item" href="#"><i class="fas fa-search"></i> Ver</a>'
  + '</div></div>';

// ── FIX #5: Función unificada de formato de números ─────────
// Reemplaza la función regex delimitNumbers y unifica con Intl.NumberFormat
function formatNumber(value, decimals = 2) {
    const num = parseFloat(String(value).replace(/[^0-9.\-]/g, '')) || 0;
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(num);
}

// ── FIX #2: Enviar datos de impresión por POST (formulario oculto) ──
// Evita el límite de URL (~2000 chars) al pasar arrays de tabla por GET.
function imprimirPorPost(url, datos) {
    const form = $('<form>', { method: 'POST', action: url, target: '_blank' });
    $.each(datos, function (key, value) {
        if (Array.isArray(value)) {
            $.each(value, function (i, v) {
                form.append($('<input>', { type: 'hidden', name: key + '[]', value: v }));
            });
        } else {
            form.append($('<input>', { type: 'hidden', name: key, value: value }));
        }
    });
    $('body').append(form);
    form.submit();
    form.remove();
}

// ── FIX #6 + #7: Función reutilizable para imprimir detalle por motorista ──
// Reemplaza goImprmirProduccionDiaria y goImprmirProduccionDetalleMotorista
// que eran 100% código duplicado.
function imprimirDetalleMotorista(tableInstance) {
    const NombreMotorista = $('#LblNombreMotorista').text();
    const ImagenFoto      = $('#ImagenPersonal').attr('src');
    const codigo_         = $('#LblDescripcionCodigo').text();
    const ruta_           = $('#LblDescripcionRuta').text();
    const unidad_         = $('#LblDescripcionUnidad').text();
    const precio_         = $('#LblListadoPrecio').text();
    const cantidad_       = $('#LblListadoCantidad').text();
    const total_          = $('#LblListadoTotalIngreso').text();
    const fecha           = $('#FechaProduccion').val();

    // FIX #7: Leer datos desde DataTables en lugar del DOM
    const correlativo_ = [], estatus_ = [], serie_ = [], cola_ = [], desde_ = [], hasta_ = [], ingreso_ = [];

    // Si la instancia de tabla se pasó, usar su API; si no, leer el DOM (fallback)
    if (tableInstance) {
        tableInstance.rows().data().each(function (row) {
            correlativo_.push(row[0] || '');
            estatus_.push(row[1]     || '');
            serie_.push(row[2]       || '');
            cola_.push(row[3]        || '');
            desde_.push(row[4]       || '');
            hasta_.push(row[5]       || '');
            ingreso_.push(row[6]     || '');
        });
    } else {
        $('#listadoAsignacion tbody tr').each(function () {
            const tds = $(this).find('td');
            correlativo_.push(tds.eq(0).text().trim());
            estatus_.push(tds.eq(1).text().trim());
            serie_.push(tds.eq(2).text().trim());
            cola_.push(tds.eq(3).text().trim());
            desde_.push(tds.eq(4).text().trim());
            hasta_.push(tds.eq(5).text().trim());
            ingreso_.push(tds.eq(6).text().trim());
        });
    }

    imprimirPorPost('/acomtus/php_libs/reportes/Produccion/DetallePorMotorista.php', {
        fecha, correlativo: correlativo_, serie: serie_, cola: cola_,
        desde: desde_, hasta: hasta_, ingreso: ingreso_, estatus: estatus_,
        NombreMotorista, ImagenPersonal: ImagenFoto,
        codigo: codigo_, ruta: ruta_, unidad: unidad_,
        precio: precio_, cantidad: cantidad_, total: total_
    });
}

// ── Inicialización principal ─────────────────────────────────
$(function () {

    // Inicializar DataTables vacíos
    $('#example').DataTable({ searching: false });
    let table   = $('#listadoPorUnidadPlaca').DataTable({ searching: false });

    $('#example1').DataTable({ searching: false });
    let table_m = $('#listadoPorMotorista').DataTable({ searching: false });

    // ── document.ready ───────────────────────────────────────
    $(document).ready(function () {
        $('#lstPersonal').select2({ theme: 'bootstrap4' });
        $('#lstPersonalPorMotorista').select2({ theme: 'bootstrap4' });

        if ($('#MenuTab').val() === '000') {
            $('#DivSoloParaContabilidad').hide();
        }
        NombreInstitucion = $('#NombreInstitucion').val();
    });

    // ── Fecha de hoy ─────────────────────────────────────────
    const now   = new Date();
    const day   = ('0' + now.getDate()).slice(-2);
    const month = ('0' + (now.getMonth() + 1)).slice(-2);
    today = `${now.getFullYear()}-${month}-${day}`;
    $('#FechaProduccion').val(today);

    // ── Cambio de select Personal → rellenar nombre ──────────
    $('#lstPersonal').on('change', function () {
        $('#txtnombres').val($('#lstPersonal option:selected').text());
    });

    // ── Botón buscar por fecha ───────────────────────────────
    $('#goBuscarProduccion').on('click', function () {
        BuscarProduccionPorFecha();
    });

    // ── Campo número correlativo — Enter ─────────────────────
    $('#NumeroCorrelativo').on('keyup', function (e) {
        this.value = (this.value + '').replace(/[^0-9]/g, '');
        if ((e.keyCode || e.which) === 13) {
            BuscarProduccionPorIdTabla();
        }
    });

    // ── Botones de reportes (nueva pestaña) ──────────────────
    $('#goReporteGeneral').on('click', function () {
        AbrirVentana('/acomtus/php_libs/reportes/Ingresos/Diario.php?fecha=' + $('#FechaProduccion').val());
    });
    $('#goReporteGeneralUnidadTransporte').on('click', function () {
        AbrirVentana('/acomtus/php_libs/reportes/Ingresos/PorUnidadTransporte.php?fecha=' + $('#FechaProduccion').val());
    });
    $('#goReporteGeneralMotorista').on('click', function () {
        AbrirVentana('/acomtus/php_libs/reportes/Ingresos/PorMotorista.php?fecha=' + $('#FechaProduccion').val());
    });

    // ── Botón mostrar sección Motorista ──────────────────────
    $('#goBuscarPorMotorista').on('click', function () {
        ocultarSecciones();
        $('#BuscarPorMotorista').show();
        miselect = $('#lstPersonalPorMotorista');
        $('#listadoPorMotoristaOk').empty();
        listar_personal();
    });

    // ── Botón mostrar sección Unidad ─────────────────────────
    $('#goBuscarPorUnidad').on('click', function () {
        ocultarSecciones();
        $('#BuscarPorUnidadPlaca').show();
        miselect = $('#lstPorUnidadPlaca');
        $('#listadoPorUnidadPlacaOk').empty();
        listar_unidad_transporte();
    });

    // ── Radio buttons Motorista ──────────────────────────────
    $('#radioTodoPM').on('click', function () {
        $('#listadoPorMotoristaOk').empty();
        // FIX #11: Actualizado selector a id="LblProduccionesTotalPorMotorista" (span, no label for)
        $('#LblProduccionesTotalPorMotorista').text('0');
        $('#LblProduccionesTotalIngresoPorMotorista').text('$');
        $('#FechaDesdePM, #FechaHastaPM').prop('readonly', true);
        OptBuscarPM = this.value;
    });
    $('#radioFechaPM').on('click', function () {
        $('#listadoPorMotoristaOk').empty();
        $('#FechaDesdePM').val(today);
        $('#FechaHastaPM').val(today);
        $('#FechaDesdePM, #FechaHastaPM').prop('readonly', false);
        $('#LblProduccionesTotalPorMotorista').text('0');
        $('#LblProduccionesTotalIngresoPorMotorista').text('$');
        OptBuscarPM = this.value;
    });

    // ── Radio buttons Unidad ─────────────────────────────────
    $('#radioTodoUP').on('click', function () {
        $('#listadoPorUnidadPlacaOk').empty();
        $('#LblProduccionesTotalPorUnidadPlaca').text('0');
        $('#LblProduccionesTotalIngresoPorUnidadPlaca').text('$ ');
        $('#FechaDesdeUP, #FechaHastaUP').prop('readonly', true);
        OptBuscarUP = this.value;
    });
    $('#radioFechaUP').on('click', function () {
        $('#listadoPorUnidadPlacaOk').empty();
        $('#FechaDesdeUP').val(today);
        $('#FechaHastaUP').val(today);
        $('#FechaDesdeUP, #FechaHastaUP').prop('readonly', false);
        $('#LblProduccionesTotalPorUnidadPlaca').text('0');
        $('#LblProduccionesTotalIngresoPorUnidadPlaca').text('$ ');
        OptBuscarUP = this.value;
    });

    // ── Buscar por tiquete ───────────────────────────────────
    $('#goBuscarPorTiquete').on('click', function () {
        $('#BusquedaNumeroControl, #BusquedaFechaControl, #BusquedaPersonalControl, #BusquedaRutaControl').val('');
        $('#BusquedaJornadaControl, #BusquedaUnidadPlacaControl, #BusquedaNumeroVueltasControl').val('');
        $('#BusquedaTotalIngresoControl, #BusquedaEstatusControl, #BusquedaNumerotiquete').val('');
        $('#VentanaBuscarPorTiquete').modal('show');
        listar_serie();
    });

    // ── Buscar tiquete en control — Enter ────────────────────
    $('#BusquedaNumerotiquete').on('keyup', function (e) {
        this.value = (this.value + '').replace(/[^0-9]/g, '');
        if ((e.keyCode || e.which) !== 13) return;

        const numero_tiquete = $('#BusquedaNumerotiquete').val();
        const serie          = $('#lstSerieBuscarTiquete').val();

        $.ajax({
            beforeSend: function () {
                $('#listadoTiqueteEnControlOk').empty();
                $('#BusquedaNumeroControl, #BusquedaFechaControl, #BusquedaPersonalControl').val('');
                $('#BusquedaRutaControl, #BusquedaJornadaControl, #BusquedaUnidadPlacaControl').val('');
                $('#BusquedaNumeroVueltasControl, #BusquedaTotalIngresoControl, #BusquedaEstatusControl').val('');
            },
            cache: false,
            type: 'POST',
            dataType: 'json',
            url: 'php_libs/soporte/Produccion/ProduccionBuscar.php',
            data: { accion_buscar: 'BuscarPorTiqueteEnControl', numero_tiquete, serie },
            success: function (response) {
                if (response.respuesta === true) {
                    $('#listadoTiqueteEnControlOk').append(response.contenido);
                    toastr['info'](response.mensaje, 'Sistema');
                } else {
                    toastr['error'](response.mensaje, 'Sistema');
                    $('#listadoTiqueteEnControlOk').append(response.contenido);
                }
            }
        });
    });

    // ── Click en tabla tiquete en control ───────────────────
    $('body').on('click', '#listadoTiqueteEnControl a', function (e) {
        e.preventDefault();
        const numero_control    = $(this).attr('href');
        const accionAsignacion  = $(this).attr('data-accion');
        const numero_tiquete    = $('#BusquedaNumerotiquete').val();
        const serie             = $('#lstSerieBuscarTiquete').val();

        if (accionAsignacion !== 'BuscarPorTiquete') return;

        $.ajax({
            cache: false,
            type: 'POST',
            dataType: 'json',
            url: 'php_libs/soporte/Produccion/ProduccionBuscar.php',
            data: { accion_buscar: accionAsignacion, NumeroControl: numero_control, numero_tiquete, serie },
            success: function (data) {
                const limpiarCampos = function () {
                    $('#BusquedaNumeroControl').val(data[0].codigo_produccion);
                    $('#BusquedaFechaControl').val(data[0].fecha);
                    $('#BusquedaPersonalControl').val(data[0].nombre_personal);
                    $('#BusquedaRutaControl').val(data[0].ruta);
                    $('#BusquedaJornadaControl').val(data[0].jornada);
                    $('#BusquedaUnidadPlacaControl').val(data[0].unidad);
                    $('#BusquedaNumeroVueltasControl').val(data[0].numero_vueltas);
                    $('#BusquedaTotalIngresoControl').val(data[0].total_ingreso);
                    $('#BusquedaEstatusControl').val(data[0].estatus);
                };
                limpiarCampos();
                if (data[0].respuesta === true) {
                    toastr['info'](data[0].mensaje, 'Sistema');
                } else {
                    toastr['error'](data[0].mensaje, 'Sistema');
                }
            }
        });
    });

    // ── Click en tabla listadoVerControles ───────────────────
    $('body').on('click', '#listadoVerControles a', function (e) {
        e.preventDefault();
        id_                     = $(this).attr('href');
        const accionAsignacion  = $(this).attr('data-accion');
        const fecha             = $('#FechaProduccion').val();

        if (accionAsignacion === 'VerProduccion') {
            window.location.href = 'editar_Nuevo_Produccion.php?id=' + id_ + '&accion=EditarRegistro';

        } else if (accionAsignacion === 'VerEliminarProduccion') {
            const swalBtns = Swal.mixin({
                customClass: { confirmButton: 'btn btn-success', cancelButton: 'btn btn-danger' },
                buttonsStyling: false
            });
            swalBtns.fire({
                title: '¿Qué desea hacer?',
                text: 'Eliminar el Registro Seleccionado!',
                showCancelButton: true,
                confirmButtonText: 'Sí, Eliminar!',
                cancelButtonText: 'No, Cancelar!',
                reverseButtons: true,
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                type: 'question'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        beforeSend: function () { $('#listadoVerControlesOk').empty(); },
                        cache: false, type: 'POST', dataType: 'json',
                        url: 'php_libs/soporte/Produccion/ProduccionBuscar.php',
                        data: { accion_buscar: 'VerEliminarProduccion', codigo_produccion: id_, fecha },
                        success: function (response) {
                            if (response.respuesta === true) {
                                $('#listadoVerControlesOk').append(response.contenido);
                                toastr['error'](response.mensaje, 'Sistema');
                            }
                        }
                    });
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    swalBtns.fire('Cancelar', 'Su Archivo no ha sido Eliminado :)', 'error');
                }
            });
        }
    });

    // ═══════════════════════════════════════════════════════
    // BUSCAR POR MOTORISTA
    // FIX #1: eliminada doble llamada AJAX — los totales se
    // calculan en drawCallback con los datos que ya tiene DataTables.
    // ═══════════════════════════════════════════════════════
    $('#goBuscarProduccionPM').on('click', function () {
        $('#lstPersonalPorMotorista').focus();
        const buscartodos         = 'BuscarPorMotorista';
        const CodigoPersonal      = $('#lstPersonalPorMotorista').val();
        const NombreCodigoPersonal = $('#lstPersonalPorMotorista option:selected').text();
        const FechaHastaPM        = $('#FechaHastaPM').val();
        const FechaDesdePM        = $('#FechaDesdePM').val();

        if (table_m) { table_m.destroy(); table_m = null; }

        table_m = $('#listadoPorMotorista').DataTable({
            ajax: {
                url: 'php_libs/soporte/ReporteGeneral.php',
                method: 'POST',
                data: { accion_buscar: buscartodos, codigo_personal: CodigoPersonal, OptBuscarPM, FechaDesdePM, FechaHastaPM },
                datatype: 'json'
            },
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'All']],
            destroy: true,
            pageLength: 5,
            searching: false,
            columns: [
                { data: null, defaultContent: defaultContentMenuPorMotorista, orderable: false },
                { data: 'id_' },
                { data: 'fecha_' },
                { data: 'numero_equipo_placa' },
                { data: 'descripcion_ruta' },
                { data: 'precio_publico', render: (data) => `<span class='font-weight-bold text-success text-right'>$${data}</span>` },
                { data: 'cantidadtiquete' },
                { data: 'total_ingreso_por_bus', render: (data) => `<span class='font-weight-bold text-success text-right'>$${data}</span>` }
            ],
            order: [[1, 'desc']],
            // FIX #1: Los totales se leen de los datos del DataTable — sin segundo AJAX
            drawCallback: function () {
                const api         = this.api();
                const intVal      = (i) => typeof i === 'string' ? parseFloat(i.replace(/[\$,]/g, '')) || 0 : (typeof i === 'number' ? i : 0);
                const totalIngreso = api.column(7).data().reduce((a, b) => intVal(a) + intVal(b), 0);
                const totalTiquete = api.column(6).data().reduce((a, b) => intVal(a) + intVal(b), 0);

                // FIX #11: Usa id= en lugar de for= (actualizado en el HTML)
                $('#LblProduccionesTotalIngresoPorMotorista').text('$' + formatNumber(totalIngreso));
                $('#LblProduccionesTotalPorMotorista').text(formatNumber(totalTiquete, 0));

                // imagen del motorista (primer registro)
                const firstRow = api.row(0).data();
                if (firstRow && firstRow.foto) {
                    $('#ImagenPersonalGlobal').attr('src', firstRow.foto.trim() !== ''
                        ? '../acomtus/img/fotos/' + firstRow.foto
                        : firstRow.codigo_genero === '02'
                            ? '../acomtus/acomtus/img/avatar_femenino.png'
                            : '../acomtus/img/avatar_masculino.png'
                    );
                }
            },
            footerCallback: function (row, data, start, end, display) {
                const api    = this.api();
                const intVal = (i) => typeof i === 'string' ? parseFloat(i.replace(/[\$,]/g, '')) || 0 : (typeof i === 'number' ? i : 0);
                const total       = api.column(7).data().reduce((a, b) => intVal(a) + intVal(b), 0);
                const pageTotal   = api.column(7, { page: 'current' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
                $(api.column(7).footer()).html('$' + formatNumber(pageTotal) + ' ( $' + formatNumber(total) + ')');
            },
            language: { url: '../acomtus/js/DataTablet/es-ES.json' },
            dom: 'Bfrtip',
            buttons: [
                { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i>', titleAttr: 'Exportar a Excel', filename: 'Reporte', title: NombreInstitucion + ' ' + NombreCodigoPersonal, exportOptions: { columns: [0,1,2,3,4,5,6,7] }, className: 'btn-exportar-excel' },
                { extend: 'pdfHtml5',   text: '<i class="fas fa-file-pdf"></i>',   titleAttr: 'Exportar a PDF',   filename: 'Reporte', title: NombreInstitucion + ' ' + NombreCodigoPersonal, exportOptions: { columns: [0,1,2,3,4,5,6,7] }, className: 'btn-exportar-pdf' },
                { extend: 'print',      text: '<i class="fa fa-print"></i>',        titleAttr: 'Imprimir',         title: NombreInstitucion + ' ' + NombreCodigoPersonal, exportOptions: { columns: [0,1,2,3,4,5,6,7] }, className: 'btn-exportar-print' },
                'pageLength'
            ]
        });

        obtener_data_editar('#listadoPorMotorista tbody', table_m);
    }); // #goBuscarProduccionPM

    // ═══════════════════════════════════════════════════════
    // BUSCAR POR UNIDAD DE TRANSPORTE
    // FIX #1: eliminada doble llamada AJAX — totales en drawCallback
    // ═══════════════════════════════════════════════════════
    $('#goBuscarPorUnidadDeTransporte').on('click', function () {
        $('#lstPorUnidadPlaca').focus();
        const buscartodos  = 'BuscarTodosUnidadPlaca';
        const NumeroPlaca  = $('#lstPorUnidadPlaca').val();
        const FechaHastaUP = $('#FechaHastaUP').val();
        const FechaDesdeUP = $('#FechaDesdeUP').val();

        if (table) { table.destroy(); table = null; }

        table = $('#listadoPorUnidadPlaca').DataTable({
            ajax: {
                url: 'php_libs/soporte/ReporteGeneral.php',
                method: 'POST',
                data: { accion_buscar: buscartodos, codigo_up: NumeroPlaca, OptBuscarUP, FechaDesdeUP, FechaHastaUP },
                datatype: 'json'
            },
            lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, 'All']],
            destroy: true,
            pageLength: 5,
            searching: false,
            columns: [
                { data: null, defaultContent: defaultContentMenuPorNumeroUnidad, orderable: false },
                { data: 'id_' },
                { data: 'fecha_' },
                { data: 'codigo' },
                { data: 'nombre_motorista' },
                { data: 'descripcion_ruta' },
                { data: 'precio_publico', render: (data) => `<span class='font-weight-bold text-success text-right'>$${data}</span>` },
                { data: 'cantidadtiquete' },
                { data: 'total_ingreso_por_bus', render: (data) => `<span class='font-weight-bold text-success text-right'>$${data}</span>` }
            ],
            order: [[2, 'desc']],
            // FIX #1: sin segundo AJAX — totales calculados aquí
            drawCallback: function () {
                const api         = this.api();
                const intVal      = (i) => typeof i === 'string' ? parseFloat(i.replace(/[\$,]/g, '')) || 0 : (typeof i === 'number' ? i : 0);
                const totalIngreso = api.column(8).data().reduce((a, b) => intVal(a) + intVal(b), 0);
                const totalTiquete = api.column(7).data().reduce((a, b) => intVal(a) + intVal(b), 0);
                $('#LblProduccionesTotalIngresoPorUnidadPlaca').text('$' + formatNumber(totalIngreso));
                $('#LblProduccionesTotalPorUnidadPlaca').text(formatNumber(totalTiquete, 0));
            },
            footerCallback: function (row, data, start, end, display) {
                const api    = this.api();
                const intVal = (i) => typeof i === 'string' ? parseFloat(i.replace(/[\$,]/g, '')) || 0 : (typeof i === 'number' ? i : 0);
                const total     = api.column(8).data().reduce((a, b) => intVal(a) + intVal(b), 0);
                const pageTotal = api.column(8, { page: 'current' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);
                $(api.column(8).footer()).html('$' + formatNumber(pageTotal) + ' ( $' + formatNumber(total) + ')');
            },
            language: { url: '../acomtus/js/DataTablet/es-ES.json' },
            dom: 'Bfrtip',
            buttons: [
                { extend: 'excelHtml5', text: '<i class="fas fa-file-excel"></i>', titleAttr: 'Exportar a Excel', filename: 'Reporte', title: NombreInstitucion + ' ' + NumeroPlaca, exportOptions: { columns: [0,1,2,3,4,5,6,7,8] }, className: 'btn-exportar-excel' },
                { extend: 'pdfHtml5',   text: '<i class="fas fa-file-pdf"></i>',   titleAttr: 'Exportar a PDF',   filename: 'Reporte', title: NombreInstitucion + ' ' + NumeroPlaca, exportOptions: { columns: [0,1,2,3,4,5,6,7,8] }, className: 'btn-exportar-pdf' },
                { extend: 'print',      text: '<i class="fa fa-print"></i>',        titleAttr: 'Imprimir',         title: NombreInstitucion + ' ' + NumeroPlaca,                       exportOptions: { columns: [0,1,2,3,4,5,6,7,8] }, className: 'btn-exportar-print' },
                'pageLength'
            ]
        });

        obtener_data_editar_('#listadoPorUnidadPlaca tbody', table);
    }); // #goBuscarPorUnidadDeTransporte

    // ── Producción diferencias ───────────────────────────────
    $('#goProduccionDiferencias').on('click', function () {
        ocultarSecciones();
        $('#ProduccionDiferencias').show();
        $('#NumeroCorrelativo, #goReporteGeneral, #goBuscarProduccion').prop('disabled', true);
        $('#FechaProduccion').prop('readonly', true);

        const fecha = $('#FechaProduccion').val();
        $.ajax({
            beforeSend: function () {
                $('#listadoDiferenciasOk').empty();
                miselect = $('#lstPersonal');
                listar_personal();
            },
            cache: false, type: 'POST', dataType: 'json',
            url: 'php_libs/soporte/NuevoEditarProduccionDiferencias.php',
            // FIX #9: accion ahora usa id="accion_dif" en el HTML
            data: 'accion=' + accion + '&id=' + Math.random() + '&fecha=' + fecha,
            success: function (response) {
                if (response.respuesta === false) {
                    toastr['error'](response.mensaje, 'Sistema');
                } else {
                    toastr['success'](response.mensaje, 'Sistema');
                    $('#listadoDiferenciasOk').append(response.contenido);
                }
            }
        });
    });

    $('#goDiferenciasCancelar').on('click', function () {
        ocultarSecciones();
        $('#NumeroCorrelativo, #goReporteGeneral, #goBuscarProduccion').prop('disabled', false);
        $('#FechaProduccion').prop('readonly', false);
        $('#listadoDiferenciasOk').empty();
        $('#txtnombres, #Valor, #concepto').val('');
        // FIX #9: usar IDs renombrados
        $('#accion_dif').val('Agregar');
        $('#id_user_dif').val(0);
    });

    // ── Validar formulario diferencias ───────────────────────
    $('#formDiferencias').validate({
        ignore: '',
        rules: {
            txtnombres: { required: true, minlength: 4 },
            Valor:      { required: true, minlength: 1 },
            concepto:   { required: true, minlength: 4 }
        },
        errorElement: 'em',
        errorPlacement: function (error, element) {
            error.addClass('invalid-feedback');
            error.insertAfter(element.prop('type') === 'checkbox' ? element.next('label') : element);
        },
        highlight:   (el) => { $(el).addClass('is-invalid').removeClass('is-valid'); },
        unhighlight: (el) => { $(el).addClass('is-valid').removeClass('is-invalid'); },
        invalidHandler: function () { setTimeout(() => toastr.error('Faltan Datos...')); },
        submitHandler: function () {
            const str   = $('#formDiferencias').serialize();
            const fecha = $('#FechaProduccion').val();
            $.ajax({
                beforeSend: function () { $('#listadoDiferenciasOk').empty(); },
                cache: false, type: 'POST', dataType: 'json',
                url: 'php_libs/soporte/NuevoEditarProduccionDiferencias.php',
                data: str + '&id=' + Math.random() + '&fecha=' + fecha,
                success: function (response) {
                    if (response.respuesta === false) {
                        toastr['error'](response.mensaje, 'Sistema');
                    } else {
                        toastr['success'](response.mensaje, 'Sistema');
                        $('#listadoDiferenciasOk').append(response.contenido);
                        $('#txtnombres, #Valor, #concepto').val('');
                        $('#accion_dif').val('Agregar');
                        $('#id_user_dif').val(0);
                    }
                }
            });
        }
    });

    // ── Click en tabla diferencias ───────────────────────────
    $('body').on('click', '#listadoDiferencia a', function (e) {
        e.preventDefault();
        id_                    = $(this).attr('href');
        const accionAsignacion = $(this).attr('data-accion');
        const fecha            = $('#FechaProduccion').val();

        if (accionAsignacion === 'EditarDiferencia') {
            $.ajax({
                beforeSend: function () { $('#listadoDiferenciasOk').empty(); },
                cache: false, type: 'POST', dataType: 'json',
                url: 'php_libs/soporte/NuevoEditarProduccionDiferencias.php',
                data: 'id_=' + id_ + '&id=' + Math.random() + '&fecha=' + fecha + '&accion=BuscarPorId',
                success: function (data) {
                    if (data[0].respuesta === false) {
                        toastr['error'](data[0].mensaje, 'Sistema');
                    } else {
                        toastr['success'](data[0].mensaje, 'Sistema');
                        $('#txtnombres').val(data[0].descripcion);
                        $('#Valor').val(data[0].valor);
                        $('#concepto').val(data[0].concepto);
                        $('#accion_dif').val('EditarRegistro');
                        $('#id_user_dif').val(id_);
                    }
                }
            });

        } else if (accionAsignacion === 'EliminarDiferencia') {
            const swalBtns = Swal.mixin({
                customClass: { confirmButton: 'btn btn-success', cancelButton: 'btn btn-danger' },
                buttonsStyling: false
            });
            swalBtns.fire({
                title: '¿Qué desea hacer?',
                text: 'Eliminar el Registro Seleccionado!',
                showCancelButton: true,
                confirmButtonText: 'Sí, Eliminar!',
                cancelButtonText: 'No, Cancelar!',
                reverseButtons: true,
                allowOutsideClick: false, allowEscapeKey: false, allowEnterKey: false,
                type: 'question'
            }).then((result) => {
                if (result.value) {
                    $.ajax({
                        beforeSend: function () { $('#listadoDiferenciasOk').empty(); },
                        cache: false, type: 'POST', dataType: 'json',
                        url: 'php_libs/soporte/NuevoEditarProduccionDiferencias.php',
                        data: { accion_buscar: 'Eliminar', id_: id_, fecha },
                        success: function (response) {
                            if (response.respuesta === true) {
                                $('#listadoDiferenciasOk').append(response.contenido);
                                toastr['error'](response.mensaje, 'Sistema');
                            }
                        }
                    });
                } else if (result.dismiss === Swal.DismissReason.cancel) {
                    swalBtns.fire('Cancelar', 'Su Archivo no ha sido Eliminado :)', 'error');
                }
            });
        }
    });

    // ── Click en tabla listadoDetalle ────────────────────────
    $('body').on('click', '#listadoDetalle a', function (e) {
        e.preventDefault();
        const codigo_produccion = $(this).attr('href');
        const accionAsignacion  = $(this).attr('data-accion');

        if (accionAsignacion !== 'ProduccionVerAsignacion') return;
        $('#FieldsetTabla').show();

        $.ajax({
            beforeSend: function () { $('#listadoDevolucionIngresoOk').empty(); },
            cache: false, type: 'POST', dataType: 'json',
            url: 'php_libs/soporte/ReporteGeneral.php',
            data: 'accion_buscar=BuscarProduccionPorIdTabla&codigo_produccion=' + codigo_produccion,
            success: function (response) {
                if (response.respuesta === true) {
                    toastr['info'](response.mensaje, 'Sistema');
                    $('#listadoDevolucionIngresoOk').append(response.contenido);
                    $('#LblDescripcionRuta').html(response.descripcionRuta);
                    $('#LblDescripcionUnidad').html(response.descripcionUnidad);
                    $('#LblDescripcionCodigo').html(response.codigoPersonal);
                    $('#LblListadoIdFecha').html(response.fecha);
                    $('label[for="LblNombreMotorista"]').text(response.nombreMotorista);
                    $('#LblListadoPrecio').html('$ ' + response.precioPublico);
                    $('#LblListadoTotalIngreso').html('$ ' + response.totalIngreso);
                    $('#LblListadoCantidad').html(response.cantidadTiquete);
                    $('#LblCantidadProduccionesVendidas').html(response.cantidadTiquete);
                    actualizarFoto(response.url_foto, response.codigo_genero);
                }
            }
        });
    });

    // ── Click en tabla listado (produccion principal) ────────
    $('body').on('click', '#listado a', function (e) {
        e.preventDefault();
        const codigo_produccion = $(this).attr('href');
        const accionAsignacion  = $(this).attr('data-accion');

        if (accionAsignacion !== 'ProduccionImprimir') return;
        $('#field_produccion_detalle').show();

        $.ajax({
            beforeSend: function () { $('#listadoDetalleOk').empty(); },
            cache: false, type: 'POST', dataType: 'json',
            url: 'php_libs/soporte/ReporteGeneral.php',
            data: 'accion_buscar=BuscarProduccionPorId&codigo_produccion=' + codigo_produccion,
            success: function (response) {
                if (response.respuesta === true) {
                    toastr['info'](response.mensaje, 'Sistema');
                    $('#listadoDetalleOk').append(response.contenido);
                    $('label[for="LblDetalleTotalIngreso"]').text('Total  $ ' + response.totalIngreso);
                }
            }
        });
    });

    // ── FIX #2 + #7: Imprimir producción por fecha ──────────
    $('#goImprmirProduccionPorFecha').on('click', function () {
        const fecha            = $('#FechaProduccion').val();
        const produccionTotal  = $('#LblProduccionesTotal').text();
        const produccionVendida = $('#LblCantidadProduccionesVendidas').text();
        const tiqueteEntregados = $('#LblTotalTiquetesEntregados').text();
        const tiqueteVendidos  = $('#LblTotalTiquetesVendidos').text();
        const ingresoTotal     = $('#LblIngresoTotal').text();
        const ingresoColones   = $('#LblProduccionesTotalIngreso').text();

        // FIX #7: Leer datos desde el DOM de la tabla (no tiene instancia DataTable accesible aquí)
        const ruta_ = [], cantidad_ = [], entregados_ = [], devolucion_ = [],
              vendidos_ = [], precio_publico_ = [], ingreso_ = [];

        $('#listado tbody tr').each(function () {
            const tds = $(this).find('td');
            ruta_.push(tds.eq(2).text().trim());
            cantidad_.push(tds.eq(4).text().trim());
            entregados_.push(tds.eq(5).text().trim());
            devolucion_.push(tds.eq(6).text().trim());
            vendidos_.push(tds.eq(7).text().trim());
            precio_publico_.push(tds.eq(8).text().trim());
            ingreso_.push(tds.eq(9).text().trim().replace(/,/g, ''));
        });

        // FIX #2: POST en lugar de GET para evitar límite de URL
        imprimirPorPost('/acomtus/php_libs/reportes/Produccion/PorFecha.php', {
            fecha, ruta: ruta_, cantidad: cantidad_, entregados: entregados_,
            devolucion: devolucion_, vendidos: vendidos_, ingreso: ingreso_,
            precio_publico: precio_publico_, produccion_total: produccionTotal,
            produccion_vendida: produccionVendida, tiqueteEntregados,
            tiqueteVendidos, ingresoTotal, ingresoColones
        });
    });

    // ── FIX #2 + #7: Imprimir detalle producción ────────────
    $('#goImprmirProduccionDetalle').on('click', function () {
        const fecha = $('#FechaProduccion').val();
        const control_ = [], ruta_ = [], equipo_ = [], motorista_ = [], ingreso_ = [];

        $('#listadoDetalle tbody tr').each(function () {
            const tds = $(this).find('td');
            control_.push(tds.eq(1).text().trim());
            ruta_.push(tds.eq(2).text().trim());
            equipo_.push(tds.eq(3).text().trim());
            motorista_.push(tds.eq(4).text().trim());
            ingreso_.push(tds.eq(5).text().trim());
        });

        imprimirPorPost('/acomtus/php_libs/reportes/Produccion/DetalleProduccion.php', {
            fecha, control: control_, ruta: ruta_, equipo: equipo_,
            motorista: motorista_, ingreso: ingreso_
        });
    });

    // ── FIX #6: goImprmirProduccionDiaria ───────────────────
    // Antes era código duplicado de goImprmirProduccionDetalleMotorista.
    // Ahora ambos usan la misma función reutilizable.
    $('#goImprmirProduccionDiaria').on('click', function () {
        imprimirDetalleMotorista(null);
    });

    // ── FIX #6: goImprmirProduccionDetalleMotorista ──────────
    $('#goImprmirProduccionDetalleMotorista').on('click', function () {
        imprimirDetalleMotorista(null);
    });

    // ── Variable data DataTable PorMotorista ─────────────────
    var obtener_data_editar = function (tbody, table_m) {
        $(tbody).on('click', 'a.verPorMotorista', function () {
            const data = table_m.row($(this).parents('tr')).data();
            // FIX #4: console.log eliminado
            const codigo_produccion = data[3];
            AbrirVentana('/acomtus/php_libs/reportes/Planilla/DetallePorMotorista.php?codigo_produccion=' + codigo_produccion);
        });
    };

    // ── Variable data DataTable PorNumeroPlaca ───────────────
    var obtener_data_editar_ = function (tbody, table) {
        $(tbody).on('click', 'a.verPorNumeroUnidad', function () {
            const data = table.row($(this).parents('tr')).data();
            // FIX #4: console.log eliminado
            const codigo_produccion = data[3];
            AbrirVentana('/acomtus/php_libs/reportes/Planilla/DetallePorMotorista.php?codigo_produccion=' + codigo_produccion);
        });
    };

}); // ── Fin del $(function()) ────────────────────────────────


// ============================================================
// FUNCIONES GLOBALES (fuera del scope de $(function))
// ============================================================

// ── BuscarProduccionPorFecha ─────────────────────────────────
function BuscarProduccionPorFecha() {
    const fecha         = $('#FechaProduccion').val();
    const accion_buscar = 'BuscarProduccionPorRuta';

    $.ajax({
        beforeSend: function () { $('#listadoOk').empty(); },
        cache: false, type: 'POST', dataType: 'json',
        url: 'php_libs/soporte/ReporteGeneral.php',
        data: 'accion_buscar=' + accion_buscar + '&fecha=' + fecha,
        success: function (response) {
            if (response.respuesta === true) {
                $('#FechaProduccion').focus().select();
                toastr['success'](response.mensaje, 'Sistema');
                $('#listadoOk').append(response.contenido);
                $('#ProduccionTabla').show();
                $('#field_produccion_detalle, #FieldsetTabla, #BuscarPorMotorista, #BuscarPorUnidadPlaca').hide();
                $('#listadoDetalleOk').empty();

                // Ingreso en colones
                let colones = parseFloat(String(response.totalProduccionIngreso).replace(/,/g, '')) || 0;
                const total_colones = formatNumber(colones * 8.75);

                // Controles
                const controles         = Number(response.totalProduccion);
                const controlesVendidos = Number(response.cantidadProduccionVendidos);
                const porcentaje_ctrl   = controles > 0 ? ((controlesVendidos * 100) / controles).toFixed(0) : 0;
                $('label[for="LblProduccionesTotal"]').text('Controles: ' + controles);
                $('.progress-bar').css('width', porcentaje_ctrl + '%').attr('aria-valuenow', porcentaje_ctrl);
                $('label[for="LblCantidadProduccionesVendidas"]').text('Procesados: ' + controlesVendidos + ' es el ' + porcentaje_ctrl + '% de ' + controles + '.');

                // Tiquetes
                const tiqueteEntregados = parseFloat(String(response.cantidadEntregados).replace(/,/g, '')) || 0;
                const tiquetesVendidos  = parseFloat(String(response.cantidadTiquetePantalla).replace(/,/g, '')) || 0;
                const porcentaje_tq     = tiqueteEntregados > 0 ? ((tiquetesVendidos * 100) / tiqueteEntregados).toFixed(0) : 0;
                $('label[for="LblTotalTiquetesEntregados"]').text('Entregados: ' + response.cantidadEntregados);
                $('#progress-bar-tiquete').css('width', porcentaje_tq + '%').attr('aria-valuenow', porcentaje_tq);
                $('label[for="LblTotalTiquetesVendidos"]').text('Vendidos: ' + response.cantidadTiquetePantalla + ' es el ' + porcentaje_tq + '% de ' + response.cantidadEntregados + '.');

                // Ingresos
                $('label[for="LblIngresoTotal"]').text('Ingreso $ ' + response.totalProduccionIngreso);
                $('label[for="LblProduccionesTotalIngreso"]').text('Ingreso ¢ ' + total_colones);
            }
            if (response.respuesta === false) {
                toastr['info'](response.mensaje, 'Sistema Acomtus');
            }
        }
    });
}

// ── BuscarProduccionPorIdTabla ───────────────────────────────
function BuscarProduccionPorIdTabla() {
    const fecha            = $('#FechaProduccion').val();
    const codigo_produccion = $('#NumeroCorrelativo').val();

    $.ajax({
        beforeSend: function () { $('#listadoDevolucionIngresoOk').empty(); },
        cache: false, type: 'POST', dataType: 'json',
        url: 'php_libs/soporte/ReporteGeneral.php',
        data: 'accion_buscar=BuscarProduccionPorIdTabla&fecha=' + fecha + '&codigo_produccion=' + codigo_produccion,
        success: function (response) {
            if (response.respuesta === true) {
                $('#FechaProduccion').focus().select();
                toastr['info'](response.mensaje, 'Sistema');
                $('#listadoDevolucionIngresoOk').append(response.contenido);
                $('#ProduccionTabla, #field_produccion_detalle').hide();
                $('#FieldsetTabla').show();
                $('#BuscarPorMotorista, #BuscarPorUnidadPlaca').hide();
                $('#listadoDetalleOk').empty();
                $('#LblDescripcionRuta').html(response.descripcionRuta);
                $('#LblDescripcionUnidad').html(response.descripcionUnidad);
                $('#LblDescripcionCodigo').html(response.codigoPersonal);
                $('label[for="LblNombreMotorista"]').text(response.nombreMotorista);
                $('#LblListadoIdFecha').html(response.fecha);
                $('#LblListadoPrecio').html('$ ' + response.precioPublico);
                $('#LblListadoTotalIngreso').html('$ ' + response.totalIngreso);
                $('#LblListadoCantidad').html(response.cantidadTiquete);
                actualizarFoto(response.url_foto, response.codigo_genero);
            }
        }
    });
}

// ── Abre nueva pestaña ───────────────────────────────────────
function AbrirVentana(url) {
    window.open(url, '_blank');
    return false;
}

// ── Ocultar todas las secciones secundarias ──────────────────
function ocultarSecciones() {
    $('#ProduccionTabla, #field_produccion_detalle, #FieldsetTabla').hide();
    $('#ProduccionDiferencias, #BuscarPorMotorista, #BuscarPorUnidadPlaca').hide();
}

// ── Actualizar foto del empleado ─────────────────────────────
function actualizarFoto(url_foto, codigo_genero) {
    if (!url_foto || url_foto.trim() === '') {
        $('.card-img-top').attr('src',
            codigo_genero === '01'
                ? '../acomtus/img/avatar_masculino.png'
                : '../acomtus/img/avatar_femenino.png'
        );
    } else {
        $('.card-img-top').attr('src', '../acomtus/img/fotos/' + url_foto);
    }
}

// ── Loading screen AJAX ──────────────────────────────────────
function configureLoadingScreen(screen) {
    $(document)
        .ajaxStart(function () { screen.fadeIn(); })
        .ajaxStop(function ()  { screen.fadeOut(); });
}

// ── Listar personal (motoristas) ─────────────────────────────
function listar_personal(codigo_personal) {
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    $.post('php_libs/soporte/ProduccionCalcular.php', { accion_buscar: 'BuscarPersonalMotorista' },
        function (data) {
            miselect.empty().append('<option value="00">Seleccionar...</option>');
            for (let i = 0; i < data.length; i++) {
                const selected = codigo_personal === data[i].codigo ? ' selected' : '';
                miselect.append(`<option value="${data[i].codigo}"${selected}>${data[i].codigo} | ${data[i].descripcion}</option>`);
            }
        }, 'json');
}

// ── Listar unidades de transporte ────────────────────────────
function listar_unidad_transporte(codigo_transporte_colectivo) {
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    $.post('php_libs/soporte/ProduccionCalcular.php', { accion_buscar: 'BuscarTransporteColectivo' },
        function (data) {
            miselect.empty();
            for (let i = 0; i < data.length; i++) {
                const selected = codigo_transporte_colectivo === data[i].codigo ? ' selected' : '';
                miselect.append(`<option value="${data[i].codigo}"${selected}>${data[i].numero_equipo} | ${data[i].descripcion}</option>`);
            }
        }, 'json');
}

// ── Listar series ────────────────────────────────────────────
function listar_serie() {
    const miselect_serie = $('#lstSerieBuscarTiquete');
    miselect_serie.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    $.post('php_libs/soporte/Produccion/ProduccionBuscar.php', { accion_buscar: 'BuscarSerie' },
        function (data) {
            miselect_serie.empty().append('<option value="">Seleccionar...</option>');
            for (let i = 0; i < data.length; i++) {
                miselect_serie.append(`<option value="${data[i].codigo}">${data[i].descripcion} - ${data[i].tiquete_color} - ${data[i].precio_publico}</option>`);
            }
        }, 'json');
}
