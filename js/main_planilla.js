// =========================================================================
// VARIABLES GLOBALES
// =========================================================================
var id_ = 0;
var accion = "todos";
var tabla = "";
var miselect = "";
var today = "";
var value_d = "";
var value = "";
var RutaText = "";
var DepartamentoText = "";
var reporteMensualDataTable; // Variable global para DataTables
var revisionMotoristaDataTable; // Variable global para DataTables (Revisión)

$(function(){ // INICIO DEL FUNCTION PRINCIPAL

    // =========================================================================
    // INICIALIZACIÓN (DOCUMENT READY)
    // =========================================================================
    var now = new Date();                
    var year = now.getFullYear();

    $(document).ready(function(){
        // Configuración inicial de UI
        $("#DivRuta").hide();
        $("#DivResponsableInfo").hide();
        $("#goRevisionMotoristas").hide();

        // Configurar Select2
        $('#lstPersonal, #lstPersonalPorMotorista').select2({
            theme: "bootstrap4"
        });

        if($('#MenuTab').val() == '06'){
            $("#DivSoloParaContabilidad").hide();
        }

        // Cargar Listas Desplegables
        listar_ruta();
        listar_ann(year);
        listar_departamento_cargo();
        
        // ---------------------------------------------------------
        // GENERADOR DE MESES (ESTILO BOTÓN APP)
        // ---------------------------------------------------------
        const monthNames = ["ENE", "FEB", "MAR", "ABR", "MAY", "JUN", "JUL", "AGO", "SEP", "OCT", "NOV", "DIC"];
        const d = new Date();
        const currentMonth = d.getMonth() + 1; 

        let mesHtml = '';
        for (let i = 1; i <= 12; i++) {
            const monthValue = ("0" + i).slice(-2);
            // Si es el mes actual, lo marcamos activo (Azul), si no, borde gris
            const btnClass = i === currentMonth ? 'btn-info shadow' : 'btn-outline-secondary border-0';
            
            mesHtml += `<button type="button" class="btn ${btnClass} rounded-pill font-weight-bold" data-value="${monthValue}" style="min-width: 60px;">${monthNames[i - 1]}</button>`;
        }
        $('#mesSelector').html(mesHtml);
        $('#lstFechaMes').val(("0" + currentMonth).slice(-2)); // Valor por defecto

        // Evento Click en Meses
        $('#mesSelector button').on('click', function() {
            // Resetear todos
            $('#mesSelector button').removeClass('btn-info shadow').addClass('btn-outline-secondary border-0');
            // Activar el clickeado
            $(this).removeClass('btn-outline-secondary border-0').addClass('btn-info shadow');
            // Guardar valor
            $('#lstFechaMes').val($(this).data('value'));
        });

        // Evento Click en Quincena
        $('#quincenaSelector button').on('click', function() {
            $('#quincenaSelector button').removeClass('active');
            $(this).addClass('active');
            $('#lstQuincena').val($(this).data('value'));
        });
    });

    // =========================================================================
    // EVENTOS DE CAMBIO (CHANGE)
    // =========================================================================

    // CAMBIO DE DEPARTAMENTO
    $("#lstDepartamentoEmpresa").change(function ()
    {
        var codigo_cargo = $(this).val();
        var codigo_ruta = "999"; // Valor por defecto para no-rutas
        
        // 1. Mostrar/Ocultar Ruta y Botón Revisión (Solo para Motoristas '02')
        if(codigo_cargo === '02') {
            $("#DivRuta").slideDown();
            $("#goRevisionMotoristas").fadeIn();
        } else {
            $("#DivRuta").slideUp();
            $("#goRevisionMotoristas").hide();
            $("#lstRuta").val('00'); // Resetear ruta
        }

        // 2. Cargar Responsable
        if(codigo_cargo != "00"){
            cargarResponsable(codigo_ruta, codigo_cargo);
        } else {
            $("#DivResponsableInfo").slideUp();
        }
    });
    
    // CAMBIO DE RUTA
    $("#lstRuta").change(function () {
        var codigo_ruta = $(this).val();
        var codigo_cargo = $("#lstDepartamentoEmpresa").val();
        
        // Solo cargar si se seleccionó una ruta válida
        if(codigo_ruta != "00"){
            cargarResponsable(codigo_ruta, codigo_cargo);
        }
    });

    // =========================================================================
    // FUNCIÓN LÓGICA: CARGAR RESPONSABLE
    // =========================================================================
    function cargarResponsable(codigo_ruta, codigo_cargo) {
        // Efecto visual de carga
        $("#lblNombreResponsable").text("Cargando...");
        
        $.post("includes/cargar_responsable_asistencia.php", { 
            codigo_ruta: codigo_ruta, 
            codigo_cargo: codigo_cargo 
        }, function(data){
            // 1. Mostrar Nombre en Pantalla
            var nombreResponsable = data[0].CodigoRutaResponsable || "Sin Asignar";
            $("#lblNombreResponsable").text(nombreResponsable);
            
            // 2. Guardar Nombre en Input Oculto (Vital para el PDF)
            $("#txtNombreResponsableHidden").val(nombreResponsable);
            
            // 3. Mostrar Total Empleados
            $("#badgeTotalEmpleados").text(data[0].TotalEmpleados || 0);

            // 4. Estilo Visual de la Tarjeta
            // Si es Motorista (02), ponemos borde verde (Revisador)
            if(codigo_cargo === '02') {
                $(".responsable-card").addClass("revisador"); 
            } else {
                $(".responsable-card").removeClass("revisador"); 
            }

            // Mostrar la tarjeta
            $("#DivResponsableInfo").slideDown(); 

        }, "json");
    }

    // =========================================================================
    // EVENTOS BOTONES DE ACCIÓN (PDFs)
    // =========================================================================

    // BOTONES: CREAR Y CALCULAR PLANILLA
    $("#goCrearPlanilla, #goCalcularPlanilla, #goCalcularPlanilla2, #goCalcularPlanilla3").on('click', function (e) {
        
        // Recolección de Datos
        var fechaMes = $("#lstFechaMes").val();
        var fechaAnn = $("#lstFechaAño").val();
        var quincena = $("#lstQuincena").val();
        var calcular = $('#chkCalcular').is(':checked') ? "no" : "si";
        
        var DepartamentoEmpresa = $("#lstDepartamentoEmpresa").val();
        var DepartamentoText = $("#lstDepartamentoEmpresa option:selected").text();
        
        // IMPORTANTE: Tomamos el nombre del input hidden que llenamos en cargarResponsable
        var persona_responsable = $("#txtNombreResponsableHidden").val(); 
        
        var ruta = $("#lstRuta").val();
        var RutaText = $("#lstRuta option:selected").text();

        // Validaciones
        if(DepartamentoEmpresa == "02" && (ruta == "00" || ruta == null)){
            toastr["error"]("Para Motoristas, debe seleccionar una RUTA específica.", "Atención");
            $("#lstRuta").focus();
            return;
        }

        // Determinar qué script llamar según el botón
        var scriptName = "";
        if (this.id === 'goCrearPlanilla') scriptName = 'NominaAsistencia.php';
        if (this.id === 'goCalcularPlanilla') scriptName = 'NominaAsistenciaCalcular.php'; // El reporte principal
        if (this.id === 'goCalcularPlanilla2') scriptName = 'NominaAsistenciaCalcularRevisar.php';
        if (this.id === 'goCalcularPlanilla3') scriptName = 'NominaAsistenciaCalcularNew.php';

        // Construir URL
        var varenviar = `/acomtus/php_libs/reportes/Planilla/${scriptName}?fechaMes=${fechaMes}&fechaAnn=${fechaAnn}&quincena=${quincena}&DepartamentoEmpresa=${DepartamentoEmpresa}&DepartamentoText=${DepartamentoText}&ruta=${ruta}&RutaText=${RutaText}&chkCalcular=${calcular}&persona_responsable=${persona_responsable}`;
        
        // Abrir PDF
        AbrirVentana(varenviar);   
    });

    // BOTÓN: REPORTE MENSUAL (Sin cambios mayores)
    $("#goReporteMensual").on('click', function (e) {
        e.preventDefault(); 
        // Lógica existente de tu sistema...
        var fechaMes = $("#lstFechaMes").val();
        var fechaAnn = $("#lstFechaAño").val();
        var DepartamentoEmpresa = $("#lstDepartamentoEmpresa").val();
        // ... (resto de lógica DataTables) ...
    });

    // BOTÓN: REVISIÓN MOTORISTAS (DataTable)
    $("#goRevisionMotoristas").on('click', function (e) {
        e.preventDefault(); 

        var fechaMes = $("#lstFechaMes").val();
        var fechaAnn = $("#lstFechaAño").val();
        var quincena = $("#lstQuincena").val();
        var DepartamentoEmpresa = $("#lstDepartamentoEmpresa").val();
        var DepartamentoText = $("#lstDepartamentoEmpresa option:selected").text();
        var ruta = $("#lstRuta").val();
        var RutaText = $("#lstRuta option:selected").text();

        if (DepartamentoEmpresa !== '02') {
            toastr.error("Esta revisión es solo para el departamento de Motoristas.", "Sistema");
            return;
        }

        $('#motoristaRevisionTitle').text(`Revisión de Controles | ${DepartamentoText} - ${RutaText} | Q${quincena} ${fechaMes}/${fechaAnn}`);

        $.ajax({
            url: "php_libs/soporte/Asistencia/RevisionControlesMotorista.php",
            type: "GET",
            dataType: "json",
            data: { fechaMes, fechaAnn, quincena, DepartamentoEmpresa, ruta },
            beforeSend: function() {
                toastr.info("Cargando reporte...", "Sistema");
            },
            success: function(response) {
                if (response.data && response.data.length > 0) {
                    $('#motoristaRevisionModal').modal('show');

                    if ($.fn.DataTable.isDataTable('#revisionMotoristaTable')) {
                        $('#revisionMotoristaTable').DataTable().destroy();
                        $('#revisionMotoristaTable tbody').empty();
                    }

                    revisionMotoristaDataTable = $('#revisionMotoristaTable').DataTable({
                        data: response.data,
                        columns: [
                            { data: 'codigo_personal' },
                            { data: 'nombre_completo' },
                            { data: 'fecha' },
                            { data: 'dia_semana' },
                            { 
                                data: 'asistencia_punteada',
                                render: function(data, type) {
                                    return type === 'display' ? data : $(data).attr('alt') || '';
                                }
                            },
                            { data: 'razon_no_control' },
                            { data: 'accion', orderable: false }
                        ],
                        dom: 'Bfrtip',
                        buttons: [ 'copy', 'excelHtml5', 'pdf', 'print'],
                        responsive: true
                    });
                } else {
                    toastr.success("Todo en orden: No hay discrepancias de controles.", "Sistema");
                }
            },
            error: function() {
                toastr.error("Error al cargar datos.", "Sistema");
            }
        });
    });

    // Evento Click DENTRO del Modal de Revisión
    $('#revisionMotoristaTable tbody').on('click', '.btn-revisar-control', function() {
        var codigo = $(this).data('codigo');
        var fecha = $(this).data('fecha');
        var url = `/acomtus/php_libs/reportes/Planilla/GenerarDetalleMotorista.php?codigo=${codigo}&fecha=${fecha}`;
        AbrirVentana(url);
    });

    // Ajuste de DataTables al abrir Modales
    $('#motoristaRevisionModal').on('shown.bs.modal', function () {
        if (revisionMotoristaDataTable) revisionMotoristaDataTable.columns.adjust().responsive.recalc();
    });

}); // FIN DEL FUNCTION PRINCIPAL

// =========================================================================
// FUNCIONES AUXILIARES (FUERA DEL SCOPE)
// =========================================================================

function AbrirVentana(url) {
    window.open(url, '_blank');
    return false;
}

function listar_ruta(codigo_ruta){
    var miselect=$("#lstRuta");
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/Produccion/ProduccionBuscar.php", {accion_buscar: 'BuscarRuta'}, function(data) {
        miselect.empty().append("<option value='00'>Seleccionar...</option>");
        data.forEach(item => {
            miselect.append(`<option value="${item.codigo}" ${codigo_ruta == item.codigo ? 'selected' : ''}>${item.descripcion}</option>`);
        });
    }, "json");    
}

function listar_ann(codigo_ann){
    var miselect=$("#lstFechaAño");
    $.post("includes/cargar_ann.php", function(data) {
        miselect.empty();
        data.forEach(item => {
            miselect.append(`<option value="${item.codigo}" ${codigo_ann == item.codigo ? 'selected' : ''}>${item.descripcion}</option>`);
        });
        // Seleccionar año actual si existe
        const currentYear = new Date().getFullYear().toString();
        if ($('#lstFechaAño option[value="'+currentYear+'"]').length > 0) {
             $('#lstFechaAño').val(currentYear);
        }
    }, "json");    
}

function listar_departamento_cargo(){
    var miselect=$("#lstDepartamentoEmpresa");
    $.post("includes/cargar_departamento_cargo.php", function(data) {
        miselect.empty().append("<option value='00'>Todos</option>");
        data.forEach(item => {
            miselect.append(`<option value="${item.codigo}">${item.descripcion}</option>`);
        });
    }, "json");    
}