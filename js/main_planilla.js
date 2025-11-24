// id de user global
var id_ = 0;
var accion = "todos";
var tabla = "";
var miselect = "";
var today = "";
var value_d = "";
var value = "";
var RutaText = "";
var DepartamentoText = "";
var reporteMensualDataTable; // Variable global para la instancia de DataTables
var revisionMotoristaDataTable; // Variable global para la instancia de DataTables (Revisión)

$(function(){ // iNICIO DEL fUNCTION.
///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////
//	FUNCION LISTAR BUSQUEDA DE LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
// Escribir la fecha actual.
var now = new Date();                
var day = ("0" + now.getDate()).slice(-2);
var month = ("0" + (now.getMonth() + 1)).slice(-2);
var year = now.getFullYear(); // <-- La declaración de 'year' estaba aquí.
$(document).ready(function(){
	// CSS NONE;
	$("#CodigoRutaResponsable").css("display", "none");
	// configurar el Select2
	$('#lstPersonal').select2({
		theme: "bootstrap4"
	});
	// configurar el Select2
	$('#lstPersonalPorMotorista').select2({
		theme: "bootstrap4"
	});
	// 
	if($('#MenuTab').val() == '06'){
		$("#DivSoloParaContabilidad").hide();
	}
	//
		listar_ruta();
		listar_ann(year);
		listar_departamento_cargo();	// Departamentos que existen en la Empresa.
	
	// --- INICIO: LÓGICA DE SELECTOR DE MES Y QUINCENA MODERNIZADA ---
	
	// 1. Cargar botones de mes
    const monthNames = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
    const d = new Date();
    const currentMonth = d.getMonth() + 1; // 1 a 12

    let mesHtml = '';
    for (let i = 1; i <= 12; i++) {
        const monthValue = ("0" + i).slice(-2);
        const isActive = i === currentMonth ? ' active' : '';
        mesHtml += `<button class="btn btn-outline-info${isActive}" data-value="${monthValue}">${monthNames[i - 1].substring(0, 3)}</button>`;
    }
    $('#mesSelector').html(mesHtml);
    $('#lstFechaMes').val(("0" + currentMonth).slice(-2)); // Establecer valor inicial del input hidden

    // 2. Eventos para botones de Mes
    $('#mesSelector button').on('click', function() {
        $('#mesSelector button').removeClass('active');
        $(this).addClass('active');
        $('#lstFechaMes').val($(this).data('value'));
    });
    // Establecer el mes activo al cargar
    $(`#mesSelector button[data-value="${$('#lstFechaMes').val()}"]`).addClass('active');

    // 3. Eventos para botones de Quincena
    $('#quincenaSelector button').on('click', function() {
        $('#quincenaSelector button').removeClass('active');
        $(this).addClass('active');
        $('#lstQuincena').val($(this).data('value'));
    });
	
	// --- FIN: LÓGICA DE SELECTOR DE MES Y QUINCENA MODERNIZADA ---

	// onchange de lstruta Y lstDepartamentoEmpresa
		$("#lstRuta").change(function ()
		{
			$("#CodigoRutaResponsable").css("display", "none");
			$("label[for=CodigoRutaResponsable]").text("");
		});
	
	// Parametros para el lstDepartamentoEmpresa. si el valor cambia.
	$("#lstDepartamentoEmpresa").change(function ()
	{
		var codigo_cargo = $(this).val();
		var codigo_ruta = "999"
		
		// 1. Mostrar/Ocultar Div de Ruta y Botón de Revisión
		$("#DivRuta").toggle(codigo_cargo === '02');
		$("#goRevisionMotoristas").toggle(codigo_cargo === '02');

		// 2. Cargar Responsable y Resumen de Empleados
		if(codigo_cargo != "00"){
			$.post("includes/cargar_responsable_asistencia.php", { codigo_ruta: codigo_ruta, codigo_cargo: codigo_cargo },
			function(data){
				// Cargar Responsable
				$("label[for=CodigoRutaResponsable]").text(data[0].CodigoRutaResponsable);

				// Cargar Resumen (empleados y encargados)
				$("#totalEmpleadosDepto").text(data[0].TotalEmpleados || 0);
				$("#encargadosAsignados").text(data[0].TotalEncargados || 0);
				$("#CardResumen").show();
			}, "json");			
		} else {
            $("#CardResumen").hide();
        }
	});
	
	// Parametros para el lstruta. si el valor cambia.
	$("#lstRuta").change(function ()
	{
		//
		$("#CodigoRutaResponsable").css("display", "block");	
		//
		$("#lstRuta option:selected").each(function () {
			codigo_ruta = $(this).val();
			codigo_cargo=$("#lstDepartamentoEmpresa").val();
			if(codigo_ruta != "00"){
				$.post("includes/cargar_responsable_asistencia.php", { codigo_ruta: codigo_ruta, codigo_cargo: codigo_cargo },
				function(data){
					$("label[for=CodigoRutaResponsable]").text(data[0].CodigoRutaResponsable);
					$("#totalEmpleadosDepto").text(data[0].TotalEmpleados || 0); // Actualiza total empleados por ruta
				}, "json");			
			} else {
                // Llama al cambio de departamento para resetear el responsable y contar todos los empleados del depto.
                $("#lstDepartamentoEmpresa").trigger('change');
            }
			
		});
	});
});		
///////////////////////////////////////////////////////////////////////////////
// EVENTOS PARA LOS BOTONES DE REPORTES
///////////////////////////////////////////////////////////////////////////////	  
	$("#goCrearPlanilla").on('click', function (e) {
			fechaMes = $("#lstFechaMes").val();
			fechaAnn = $("#lstFechaAño").val();
			quincena = $("#lstQuincena").val();
			DepartamentoEmpresa = $("#lstDepartamentoEmpresa").val();
			DepartamentoText = $("#lstDepartamentoEmpresa option:selected").text();
			persona_responsable = $("label[for=CodigoRutaResponsable]").html();
			ruta = $("#lstRuta").val();
			RutaText = $("#lstRuta option:selected").text();

			if(ruta == "00" && DepartamentoEmpresa == "02"){
				toastr["error"]("Debe seleccionar una ruta.", "Sistema");
				return;
			}
			
			var varenviar = "/acomtus/php_libs/reportes/Planilla/NominaAsistencia.php?fechaMes="+fechaMes+"&fechaAnn="+fechaAnn+"&quincena="+quincena+"&DepartamentoEmpresa="+DepartamentoEmpresa+"&DepartamentoText="+DepartamentoText+"&ruta="+ruta+"&RutaText="+RutaText+"&persona_responsable="+persona_responsable;
			AbrirVentana(varenviar);   
	});
	
	$("#goCalcularPlanilla, #goCalcularPlanilla2, #goCalcularPlanilla3").on('click', function (e) {
		fechaMes = $("#lstFechaMes").val();
		fechaAnn = $("#lstFechaAño").val();
		quincena = $("#lstQuincena").val();
		var calcular = $('#chkCalcular').is(':checked') ? "no" : "si";
		
		DepartamentoEmpresa = $("#lstDepartamentoEmpresa").val();
		DepartamentoText = $("#lstDepartamentoEmpresa option:selected").text();
		persona_responsable = $("label[for=CodigoRutaResponsable]").html();
		ruta = $("#lstRuta").val();
		RutaText = $("#lstRuta option:selected").text();

		if(ruta == "00" && DepartamentoEmpresa == "02"){
			toastr["error"]("Debe seleccionar una ruta.", "Sistema");
			return;
		}

		var scriptName = "";
		if (this.id === 'goCalcularPlanilla') scriptName = 'NominaAsistenciaCalcular.php';
		if (this.id === 'goCalcularPlanilla2') scriptName = 'NominaAsistenciaCalcularRevisar.php';
		if (this.id === 'goCalcularPlanilla3') scriptName = 'NominaAsistenciaCalcularNew.php';

		var varenviar = `/acomtus/php_libs/reportes/Planilla/${scriptName}?fechaMes=${fechaMes}&fechaAnn=${fechaAnn}&quincena=${quincena}&DepartamentoEmpresa=${DepartamentoEmpresa}&DepartamentoText=${DepartamentoText}&ruta=${ruta}&RutaText=${RutaText}&chkCalcular=${calcular}&persona_responsable=${persona_responsable}`;
		AbrirVentana(varenviar);   
	});

    // Evento para el botón de Reporte Mensual (no modificado)
    $("#goReporteMensual").on('click', function (e) {
        e.preventDefault(); 

        var fechaMes = $("#lstFechaMes").val();
        var fechaAnn = $("#lstFechaAño").val();
        var DepartamentoEmpresa = $("#lstDepartamentoEmpresa").val();
        var DepartamentoText = $("#lstDepartamentoEmpresa option:selected").text();
        var ruta = $("#lstRuta").val();
        var RutaText = $("#lstRuta option:selected").text();

        var cardTitle = 'Reporte - ' + $('#lstFechaMes option:selected').text() + ' ' + fechaAnn;
        if (DepartamentoEmpresa && DepartamentoEmpresa !== '00') {
            cardTitle += ' | ' + DepartamentoText;
            if (DepartamentoEmpresa === '02' && ruta && ruta !== '00' && ruta !== 'Seleccionar...') {
                cardTitle += ' (' + RutaText + ')';
            }
        }
        $('#reporteMensualCardTitle').text(cardTitle);

        // ... (Lógica de AJAX para NominaMensualReporte.php y DataTables) ...
    });
	
	// --- NUEVO EVENTO PARA REVISIÓN DE MOTORISTAS ---
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
            url: "php_libs/soporte/Asistencia/RevisionControlesMotorista.php", // NUEVO SCRIPT PHP
            type: "GET",
            dataType: "json",
            data: { fechaMes, fechaAnn, quincena, DepartamentoEmpresa, ruta },
            beforeSend: function() {
                toastr.info("Cargando reporte de revisión de controles...", "Sistema");
            },
            success: function(response) {
                if (response.data && response.data.length > 0) {
                    toastr.success("Reporte de revisión cargado exitosamente.", "Sistema");
                    
                    $('#motoristaRevisionModal').modal('show');

                    // Destruye la tabla si ya existe para evitar errores
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
                                    // DataTables renderiza la celda como HTML si type es 'display'
                                    if (type === 'display') {
                                        return data; 
                                    }
                                    // Para otros tipos (exportación), retorna el código de la imagen
                                    return $(data).attr('alt') || '';
                                }
                            },
                            { data: 'razon_no_control' },
                            { 
                                data: 'accion', 
                                orderable: false, // No ordenar por esta columna
                                render: (data) => data // Renderizar el botón como HTML
                            } // <<-- NUEVA COLUMNA AÑADIDA
                        ],
                        dom: 'Bfrtip',
                        buttons: [ 'copy', 'csv', 'excelHtml5', 'pdf', 'print'],
                        paging: true,
                        lengthChange: true,
                        searching: true,
                        ordering: true,
                        info: true,
                        autoWidth: false,
                        responsive: true
                    });
                } else {
                    toastr.success("No se encontraron discrepancias de controles en el período seleccionado.", "Sistema");
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                toastr.error("Error al cargar el reporte de revisión: " + textStatus, "Sistema");
                $('#motoristaRevisionModal').modal('hide');
            }
        });
    });

    // 2. Manejar el evento click del botón 'Revisar' DENTRO DEL MODAL
    $('#revisionMotoristaTable tbody').on('click', '.btn-revisar-control', function() {
        var codigo = $(this).data('codigo');
        var fecha = $(this).data('fecha'); // Formato YYYY-MM-DD
        
        // Llama al script intermediario que buscará el codigo_produccion y redirigirá al PDF
        var url = `/acomtus/php_libs/reportes/Planilla/GenerarDetalleMotorista.php?codigo=${codigo}&fecha=${fecha}`;
        AbrirVentana(url);
    });

    // Ajusta las columnas de la tabla cuando el modal se muestra
    $('#motoristaRevisionModal').on('shown.bs.modal', function () {
        if (revisionMotoristaDataTable) {
            revisionMotoristaDataTable.columns.adjust().responsive.recalc();
        }
    });

    // Ajusta las columnas de la tabla cuando el modal se muestra (EXISTENTE)
    $('#reporteMensualModal').on('shown.bs.modal', function () {
        if (reporteMensualDataTable) {
            reporteMensualDataTable.columns.adjust().responsive.recalc();
        }
    });

    // Lógica para el botón que muestra y oculta el panel lateral de totales (EXISTENTE)
    $('#toggleTotalesBtn').on('click', function() {
        $('#totalsSidebar').toggleClass('open');
        var btn = $(this);
        if ($('#totalsSidebar').hasClass('open')) {
            btn.html('<i class="fas fa-eye-slash"></i> Ocultar Totales');
        } else {
            btn.html('<i class="fas fa-eye"></i> Mostrar Totales');
        }
    });

	$("#lstDepartamentoEmpresa").change(function () {
		$("#DivRuta").toggle($(this).val() === '02');
	});

});	// FINAL DE $(function(){})

// FUNCIONES AUXILIARES
function AbrirVentana(url) {
    window.open(url, '_blank');
    return false;
}

function formatCurrency(data) {
    let number = parseFloat(data) || 0;
    return '$ ' + number.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
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
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_ann.php", function(data) {
        miselect.empty();
        data.forEach(item => {
            miselect.append(`<option value="${item.codigo}" ${codigo_ann == item.codigo ? 'selected' : ''}>${item.descripcion}</option>`);
        });
		// Inicializar el año actual
		if ($('#lstFechaAño option').length > 0) {
            const currentYear = new Date().getFullYear().toString();
            if ($('#lstFechaAño').find(`option[value="${currentYear}"]`).length) {
                $('#lstFechaAño').val(currentYear);
            } else {
                $('#lstFechaAño').prop('selectedIndex', 0);
            }
        }
    }, "json");    
}

function listar_departamento_cargo(){
    var miselect=$("#lstDepartamentoEmpresa");
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_departamento_cargo.php", function(data) {
        miselect.empty().append("<option value='00'>Todos</option>");
        data.forEach(item => {
            miselect.append(`<option value="${item.codigo}">${item.descripcion}</option>`);
        });
    }, "json");    
}