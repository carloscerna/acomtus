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

$(function(){ // iNICIO DEL fUNCTION.
///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
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
			$("#lstFechaMes").prop('selectedIndex', mes);
	// onchange de lstruta Y lstDepartamentoEmpresa
		$("#lstRuta").change(function ()
		{
			$("#CodigoRutaResponsable").css("display", "none");
			$("label[for=CodigoRutaResponsable]").text("");
		});
	// Parametros para el lstDepartamentoEmpresa. si el valor cambia.
	$("#lstDepartamentoEmpresa").change(function ()
	{
		//
		$("#CodigoRutaResponsable").css("display", "block");	
		//
		$("#lstDepartamentoEmpresa option:selected").each(function () {
			codigo_cargo = $(this).val();
			codigo_ruta = "999"
			if(codigo_cargo != "00"){
				$.post("includes/cargar_responsable_asistencia.php", { codigo_ruta: codigo_ruta, codigo_cargo: codigo_cargo },
				function(data){
					$("label[for=CodigoRutaResponsable]").text(data[0].CodigoRutaResponsable);
				}, "json");			
			}

		});
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
				}, "json");			
			}
			
		});
	});
});		
///////////////////////////////////////////////////////////////////////////////
//	FUNCION LISTAR BUSQUEDA DE LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
// Escribir la fecha actual.
	var now = new Date();                
	var day = ("0" + now.getDate()).slice(-2);
	var month = ("0" + (now.getMonth() + 1)).slice(-2);
	var year = now.getFullYear();
	today = now.getFullYear()+"-"+(month)+"-"+(day) ;
	// PARA SELECCIONA REL MES ACTUAL.
	const d = new Date();
	var mes = d.getMonth();

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

    // Evento para el botón de Reporte Mensual
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

        $.ajax({
            url: "php_libs/reportes/Planilla/NominaMensualReporte.php",
            type: "GET",
            dataType: "json",
            data: { fechaMes, fechaAnn, DepartamentoEmpresa, ruta },
            beforeSend: function() {
                toastr.info("Cargando reporte mensual...", "Sistema");
            },
            success: function(response) {
                if (response.data && response.data.length > 0) {
                    toastr.success("Reporte mensual cargado exitosamente.", "Sistema");
                    
                    $('#reporteMensualModal').modal('show');

                    // Destruye la tabla si ya existe para evitar errores
                    if ($.fn.DataTable.isDataTable('#reporteMensualTable')) {
                        $('#reporteMensualTable').DataTable().destroy();
                        $('#reporteMensualTable tbody').empty();
                    }

                    reporteMensualDataTable = $('#reporteMensualTable').DataTable({
                        data: response.data,
                        columns: [
                            { data: 'codigo_personal', render: (data, type) => type === 'display' ? String(data) : data },
                            { data: 'nombre_completo' },
                            { data: 'salario_bruto_mensual', render: (data, type) => type === 'display' ? formatCurrency(data) : data },
                            { data: 'isss_empleado_mensual', render: (data, type) => type === 'display' ? formatCurrency(data) : data },
                            { data: 'afp_empleado_mensual', render: (data, type) => type === 'display' ? formatCurrency(data) : data },
                            { data: 'renta_empleado_mensual', render: (data, type) => type === 'display' ? formatCurrency(data) : data },
                            { data: 'isss_patronal_mensual', render: (data, type) => type === 'display' ? formatCurrency(data) : data },
                            { data: 'afp_patronal_mensual', render: (data, type) => type === 'display' ? formatCurrency(data) : data },
                            { data: 'salario_neto_mensual', render: (data, type) => type === 'display' ? formatCurrency(data) : data }
                        ],
                        dom: 'Bfrtip',
                        buttons: [
                            'copy', 
                            'csv',
                            {
                                extend: 'excelHtml5',
                                footer: true,
                                title: () => `Reporte Mensual Planilla - ${$('#lstFechaMes option:selected').text()} ${$("#lstFechaAño").val()}`,
                                messageTop: () => {
                                    let depto = $("#lstDepartamentoEmpresa option:selected").text();
                                    let ruta = $("#lstRuta option:selected").text();
                                    let codDepto = $("#lstDepartamentoEmpresa").val();
                                    if (codDepto && codDepto !== '00') {
                                        if (codDepto === '02' && $("#lstRuta").val() !== '00') {
                                            return `Departamento: ${depto} | Ruta: ${ruta}`;
                                        }
                                        return `Departamento: ${depto}`;
                                    }
                                    return 'Reporte General';
                                }
                            },
                            'pdf', 
                            'print'
                        ],
                        paging: true,
                        lengthChange: true,
                        searching: true,
                        ordering: true,
                        info: true,
                        autoWidth: false,
                        responsive: true,
                        footerCallback: function (row, data, start, end, display) {
                            var api = this.api();
                            const intVal = (i) => typeof i === 'string' ? i.replace(/[\$,]/g, '') * 1 : typeof i === 'number' ? i : 0;
                            
                            const pageTotal = (colIndex) => api.column(colIndex, { page: 'current' }).data().reduce((a, b) => intVal(a) + intVal(b), 0);

                            $(api.column(2).footer()).html(formatCurrency(pageTotal(2)));
                            $(api.column(3).footer()).html(formatCurrency(pageTotal(3)));
                            $(api.column(4).footer()).html(formatCurrency(pageTotal(4)));
                            $(api.column(5).footer()).html(formatCurrency(pageTotal(5)));
                            $(api.column(6).footer()).html(formatCurrency(pageTotal(6)));
                            $(api.column(7).footer()).html(formatCurrency(pageTotal(7)));
                            $(api.column(8).footer()).html(formatCurrency(pageTotal(8)));

                            $('#totalSalarioBrutoMensual').text(formatCurrency(pageTotal(2)));
                            $('#totalIsssEmpleado').text(formatCurrency(pageTotal(3)));
                            $('#totalAfpEmpleado').text(formatCurrency(pageTotal(4)));
                            $('#totalRentaEmpleado').text(formatCurrency(pageTotal(5)));
                            $('#totalIsssPatronal').text(formatCurrency(pageTotal(6)));
                            $('#totalAfpPatronal').text(formatCurrency(pageTotal(7)));
                            $('#totalSalarioNetoMensual').text(formatCurrency(pageTotal(8)));
                        }
                    });
                } else {
                    toastr.warning("No se encontraron datos para el mes y año seleccionados.", "Sistema");
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                toastr.error("Error al cargar el reporte mensual: " + textStatus + " - " + errorThrown, "Sistema");
                $('#reporteMensualModal').modal('hide');
            }
        });
    });

    // Ajusta las columnas de la tabla cuando el modal se muestra
    $('#reporteMensualModal').on('shown.bs.modal', function () {
        if (reporteMensualDataTable) {
            reporteMensualDataTable.columns.adjust().responsive.recalc();
        }
    });

    // Lógica para el botón que muestra y oculta el panel lateral de totales
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