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
//alert(mes);
///////////////////////////////////////////////////////////////////////////////
// CUANDO CAMBIA LA FECHA. BUSCAR LA PRODUCCIÓN EN LA TABLA
/// EVENTOS JQUERY IMPRIMIR TODA LA PRODUCCIÓN O POR RANGO.
///////////////////////////////////////////////////////////////////////////////	  
	$("#goCrearPlanilla").on('click', function (e) {
			// Limpiar datos
			fechaMes = $("#lstFechaMes").val();
			fechaAnn = $("#lstFechaAño").val();
			quincena = $("#lstQuincena").val();
			// LstDepartmaentoEmpresa
			DepartamentoEmpresa = $("#lstDepartamentoEmpresa").val();
			value_d = $("#lstDepartamentoEmpresa option:selected");
			DepartamentoText = value_d.text();
			persona_responsable = $("label[for=CodigoRutaResponsable]").html();
			// lstruta
			ruta = $("#lstRuta").val();
			value = $("#lstRuta option:selected");
			RutaText = value.text();
			// validar lstRuta == 00
			codigo_ruta = $("#lstRuta").val();
			if(codigo_ruta == "00" && DepartamentoEmpresa == "02"){
				toastr["error"]("Debe seleccionar una ruta.", "Sistema");
				return
			}
			//Validar que información llevara el informe 
			// Cìdog 02 corresponde a los motoristas
			if(DepartamentoEmpresa == '02'){
			// Ejecutar Informe
				varenviar = "/acomtus/php_libs/reportes/Planilla/NominaAsistencia.php?fechaMes="+fechaMes+"&fechaAnn="+fechaAnn+"&quincena="+quincena+"&ruta="+ruta+"&RutaText="+RutaText+"&DepartamentoEmpresa="+DepartamentoEmpresa+"&DepartamentoText="+DepartamentoText+"&persona_responsable="+persona_responsable;
			}else{
			// Ejecutar Informe
				varenviar = "/acomtus/php_libs/reportes/Planilla/NominaAsistencia.php?fechaMes="+fechaMes+"&fechaAnn="+fechaAnn+"&quincena="+quincena+"&DepartamentoEmpresa="+DepartamentoEmpresa+"&DepartamentoText="+DepartamentoText+"&ruta="+ruta+"&RutaText="+RutaText+"&persona_responsable="+persona_responsable;;
			}
			// Ejecutar la función abre otra pestaña.
				AbrirVentana(varenviar);   
	});
///////////////////////////////////////////////////////////////////////////////	  
	$("#goCalcularPlanilla").on('click', function (e) {
		// Limpiar datos
		fechaMes = $("#lstFechaMes").val();
		fechaAnn = $("#lstFechaAño").val();
		quincena = $("#lstQuincena").val();
		if($('#chkCalcular').is(':checked') ) {
			//alert('Seleccionado Dolares');
			var calcular = "no";
		}else{
			var calcular = "si";
		}
		// LstDepartmaentoEmpresa
		DepartamentoEmpresa = $("#lstDepartamentoEmpresa").val();
		value_d = $("#lstDepartamentoEmpresa option:selected");
		DepartamentoText = value_d.text();
		persona_responsable = $("label[for=CodigoRutaResponsable]").html();
		// lstruta
		ruta = $("#lstRuta").val();
		var value = $("#lstRuta option:selected");
		var RutaText = value.text();
		// validar lstRuta == 00
		codigo_ruta = $("#lstRuta").val();
		if(codigo_ruta == "00" && DepartamentoEmpresa == "02"){
			toastr["error"]("Debe seleccionar una ruta.", "Sistema");
			return
		}
			//Validar que información llevara el informe 
			// Cìdog 02 corresponde a los motoristas
			if(DepartamentoEmpresa == '02'){
				// Ejecutar Informe
					varenviar = "/acomtus/php_libs/reportes/Planilla/NominaAsistenciaCalcular.php?fechaMes="+fechaMes+"&fechaAnn="+fechaAnn+"&quincena="+quincena+"&ruta="+ruta+"&RutaText="+RutaText+"&DepartamentoEmpresa="+DepartamentoEmpresa+"&DepartamentoText="+DepartamentoText+"&chkCalcular="+calcular+"&persona_responsable="+persona_responsable;
				}else{
				// Ejecutar Informe
					varenviar = "/acomtus/php_libs/reportes/Planilla/NominaAsistenciaCalcular.php?fechaMes="+fechaMes+"&fechaAnn="+fechaAnn+"&quincena="+quincena+"&DepartamentoEmpresa="+DepartamentoEmpresa+"&DepartamentoText="+DepartamentoText+"&ruta="+ruta+"&RutaText="+RutaText+"&chkCalcular="+calcular+"&persona_responsable="+persona_responsable;
				}
			AbrirVentana(varenviar);   
	});
	$("#goCalcularPlanilla2").on('click', function (e) {
		// Limpiar datos
		fechaMes = $("#lstFechaMes").val();
		fechaAnn = $("#lstFechaAño").val();
		quincena = $("#lstQuincena").val();
		if($('#chkCalcular').is(':checked') ) {
			//alert('Seleccionado Dolares');
			var calcular = "no";
		}else{
			var calcular = "si";
		}
		// LstDepartmaentoEmpresa
		DepartamentoEmpresa = $("#lstDepartamentoEmpresa").val();
		value_d = $("#lstDepartamentoEmpresa option:selected");
		DepartamentoText = value_d.text();
		persona_responsable = $("label[for=CodigoRutaResponsable]").html();
		// lstruta
		ruta = $("#lstRuta").val();
		var value = $("#lstRuta option:selected");
		var RutaText = value.text();
		// validar lstRuta == 00
		codigo_ruta = $("#lstRuta").val();
		if(codigo_ruta == "00" && DepartamentoEmpresa == "02"){
			toastr["error"]("Debe seleccionar una ruta.", "Sistema");
			return
		}
			//Validar que información llevara el informe 
			// Cìdog 02 corresponde a los motoristas
			if(DepartamentoEmpresa == '02'){
				// Ejecutar Informe
					varenviar = "/acomtus/php_libs/reportes/Planilla/NominaAsistenciaCalcularRevisar.php?fechaMes="+fechaMes+"&fechaAnn="+fechaAnn+"&quincena="+quincena+"&ruta="+ruta+"&RutaText="+RutaText+"&DepartamentoEmpresa="+DepartamentoEmpresa+"&DepartamentoText="+DepartamentoText+"&chkCalcular="+calcular+"&persona_responsable="+persona_responsable;
				}else{
				// Ejecutar Informe
					varenviar = "/acomtus/php_libs/reportes/Planilla/NominaAsistenciaCalcularRevisar.php?fechaMes="+fechaMes+"&fechaAnn="+fechaAnn+"&quincena="+quincena+"&DepartamentoEmpresa="+DepartamentoEmpresa+"&DepartamentoText="+DepartamentoText+"&ruta="+ruta+"&RutaText="+RutaText+"&chkCalcular="+calcular+"&persona_responsable="+persona_responsable;
				}
			AbrirVentana(varenviar);   
	});
	$("#goCalcularPlanilla3").on('click', function (e) {
		// Limpiar datos
		fechaMes = $("#lstFechaMes").val();
		fechaAnn = $("#lstFechaAño").val();
		quincena = $("#lstQuincena").val();
		if($('#chkCalcular').is(':checked') ) {
			//alert('Seleccionado Dolares');
			var calcular = "no";
		}else{
			var calcular = "si";
		}
		// LstDepartmaentoEmpresa
		DepartamentoEmpresa = $("#lstDepartamentoEmpresa").val();
		value_d = $("#lstDepartamentoEmpresa option:selected");
		DepartamentoText = value_d.text();
		persona_responsable = $("label[for=CodigoRutaResponsable]").html();
		// lstruta
		ruta = $("#lstRuta").val();
		var value = $("#lstRuta option:selected");
		var RutaText = value.text();
		// validar lstRuta == 00
		codigo_ruta = $("#lstRuta").val();
		if(codigo_ruta == "00" && DepartamentoEmpresa == "02"){
			toastr["error"]("Debe seleccionar una ruta.", "Sistema");
			return
		}
			//Validar que información llevara el informe 
			// Cìdog 02 corresponde a los motoristas
			if(DepartamentoEmpresa == '02'){
				// Ejecutar Informe
					varenviar = "/acomtus/php_libs/reportes/Planilla/NominaAsistenciaCalcularNew.php?fechaMes="+fechaMes+"&fechaAnn="+fechaAnn+"&quincena="+quincena+"&ruta="+ruta+"&RutaText="+RutaText+"&DepartamentoEmpresa="+DepartamentoEmpresa+"&DepartamentoText="+DepartamentoText+"&chkCalcular="+calcular+"&persona_responsable="+persona_responsable;
				}else{
				// Ejecutar Informe
					varenviar = "/acomtus/php_libs/reportes/Planilla/NominaAsistenciaCalcularNew.php?fechaMes="+fechaMes+"&fechaAnn="+fechaAnn+"&quincena="+quincena+"&DepartamentoEmpresa="+DepartamentoEmpresa+"&DepartamentoText="+DepartamentoText+"&ruta="+ruta+"&RutaText="+RutaText+"&chkCalcular="+calcular+"&persona_responsable="+persona_responsable;
				}
			AbrirVentana(varenviar);   
	});

    // NUEVO: Evento para el botón de Reporte Mensual
    $("#goReporteMensual").on('click', function (e) {
        e.preventDefault(); // Prevenir el comportamiento por defecto del botón

        // Obtener valores de los selectores
        var fechaMes = $("#lstFechaMes").val();
        var fechaAnn = $("#lstFechaAño").val();
        var DepartamentoEmpresa = $("#lstDepartamentoEmpresa").val();
        var DepartamentoText = $("#lstDepartamentoEmpresa option:selected").text(); // Texto del departamento
        var ruta = $("#lstRuta").val();
        var RutaText = $("#lstRuta option:selected").text(); // Texto de la ruta

        // Construir el título de la tarjeta
        var cardTitle = 'Reporte Mensual de Planilla - ' + $('#lstFechaMes option:selected').text() + ' ' + fechaAnn;
        if (DepartamentoEmpresa && DepartamentoEmpresa !== '00') {
            cardTitle += ' | ' + DepartamentoText;
            if (DepartamentoEmpresa === '02' && ruta && ruta !== '00' && ruta !== 'Seleccionar...') {
                cardTitle += ' (' + RutaText + ')';
            }
        }
        $('#reporteMensualCardTitle').text(cardTitle); // Establecer el título de la tarjeta

        // Destruir la instancia existente de DataTables si la hay
        if ($.fn.DataTable.isDataTable('#reporteMensualTable')) {
            reporteMensualDataTable.destroy();
            $('#reporteMensualTable tbody').empty(); // Limpiar el cuerpo de la tabla
        }

        // Realizar la llamada AJAX para obtener los datos mensuales
        $.ajax({
            url: "php_libs/reportes/Planilla/NominaMensualReporte.php", // Nuevo archivo PHP
            type: "GET",
            dataType: "json",
            data: {
                fechaMes: fechaMes,
                fechaAnn: fechaAnn,
                DepartamentoEmpresa: DepartamentoEmpresa,
                ruta: ruta
            },
            beforeSend: function() {
                toastr.info("Cargando reporte mensual...", "Sistema");
                // Ocultar la sección principal y mostrar la tarjeta de reporte
                $("#PantallaPrincipal").hide();
                $("#reporteMensualCardContainer").show();
            },
            success: function(response) {
                if (response.data && response.data.length > 0) {
                    toastr.success("Reporte mensual cargado exitosamente.", "Sistema");
                    
                    // Inicializar DataTables con los datos recibidos
                    reporteMensualDataTable = $('#reporteMensualTable').DataTable({
                        data: response.data,
                        columns: [
                            { data: 'codigo_personal' },
                            { data: 'nombre_completo' },
                            { data: 'fecha_mes' },
                            { data: 'fecha_ann' },
                            { data: 'salario_bruto_mensual', render: $.fn.dataTable.render.number(',', '.', 2, '$') },
                            { data: 'isss_empleado_mensual', render: $.fn.dataTable.render.number(',', '.', 2, '$') },
                            { data: 'afp_empleado_mensual', render: $.fn.dataTable.render.number(',', '.', 2, '$') },
                            { data: 'renta_empleado_mensual', render: $.fn.dataTable.render.number(',', '.', 2, '$') },
                            { data: 'isss_patronal_mensual', render: $.fn.dataTable.render.number(',', '.', 2, '$') },
                            { data: 'afp_patronal_mensual', render: $.fn.dataTable.render.number(',', '.', 2, '$') },
                            { data: 'salario_neto_mensual', render: $.fn.dataTable.render.number(',', '.', 2, '$') }
                        ],
					// ... dentro de la inicialización de reporteMensualDataTable
					dom: 'Bfrtip', // La 'B' es necesaria para los botones
					buttons: [
						'copy', 
						'csv',
						{
							extend: 'excelHtml5', // Usamos la extensión de Excel
							footer: true, // ¡IMPORTANTE! Esto incluye el <tfoot> en la exportación.
							title: function() {
								// Título dinámico para el archivo Excel.
								var fechaAnn = $("#lstFechaAño").val();
								var mesTexto = $('#lstFechaMes option:selected').text();
								return 'Reporte Mensual Planilla - ' + mesTexto + ' ' + fechaAnn;
							},
							messageTop: function() {
								// Mensaje dinámico que aparecerá como subtítulo en el Excel.
								var departamento = $("#lstDepartamentoEmpresa option:selected").text();
								var ruta = $("#lstRuta option:selected").text();
								var codigoDepto = $("#lstDepartamentoEmpresa").val();

								if (codigoDepto && codigoDepto !== '00') {
									if (codigoDepto === '02' && $("#lstRuta").val() !== '00' && $("#lstRuta").val() !== 'Seleccionar...') {
										return 'Departamento: ' + departamento + ' | Ruta: ' + ruta;
									}
									return 'Departamento: ' + departamento;
								}
								return 'Reporte General (Todos los departamentos)';
							}
						},
						'pdf', 
						'print'
					],
					// ... el resto de la configuración sigue igual
                        "paging": true,
                        "lengthChange": true,
                        "searching": true,
                        "ordering": true,
                        "info": true,
                        "autoWidth": false,
                        "responsive": true, // Mantener responsive para ajuste automático
                        "footerCallback": function ( row, data, start, end, display ) {
                            var api = this.api();
                
                            // Función para sumar una columna
                            var intVal = function ( i ) {
                                return typeof i === 'string' ?
                                    i.replace(/[\$,]/g, '')*1 :
                                    typeof i === 'number' ?
                                        i : 0;
                            };
                
                            // Sumas para el pie de tabla
                            var total_salario_bruto_mensual_table = api
                                .column( 4, { page: 'current'} )
                                .data()
                                .reduce( function (a, b) { return intVal(a) + intVal(b); }, 0 );
                
                            var total_isss_empleado_table = api
                                .column( 5, { page: 'current'} )
                                .data()
                                .reduce( function (a, b) { return intVal(a) + intVal(b); }, 0 );
                            
                            var total_afp_empleado_table = api
                                .column( 6, { page: 'current'} )
                                .data()
                                .reduce( function (a, b) { return intVal(a) + intVal(b); }, 0 );

                            var total_renta_empleado_table = api
                                .column( 7, { page: 'current'} )
                                .data()
                                .reduce( function (a, b) { return intVal(a) + intVal(b); }, 0 );

                            var total_isss_patronal_table = api
                                .column( 8, { page: 'current'} )
                                .data()
                                .reduce( function (a, b) { return intVal(a) + intVal(b); }, 0 );

                            var total_afp_patronal_table = api
                                .column( 9, { page: 'current'} )
                                .data()
                                .reduce( function (a, b) { return intVal(a) + intVal(b); }, 0 );

                            var total_salario_neto_mensual_table = api
                                .column( 10, { page: 'current'} )
                                .data()
                                .reduce( function (a, b) { return intVal(a) + intVal(b); }, 0 );
                
                            // Actualizar el pie de página de la tabla
                            $( api.column( 4 ).footer() ).html( '$' + total_salario_bruto_mensual_table.toFixed(2) );
                            $( api.column( 5 ).footer() ).html( '$' + total_isss_empleado_table.toFixed(2) );
                            $( api.column( 6 ).footer() ).html( '$' + total_afp_empleado_table.toFixed(2) );
                            $( api.column( 7 ).footer() ).html( '$' + total_renta_empleado_table.toFixed(2) );
                            $( api.column( 8 ).footer() ).html( '$' + total_isss_patronal_table.toFixed(2) );
                            $( api.column( 9 ).footer() ).html( '$' + total_afp_patronal_table.toFixed(2) );
                            $( api.column( 10 ).footer() ).html( '$' + total_salario_neto_mensual_table.toFixed(2) );

                            // Actualizar el recuadro de totales
                            $('#totalSalarioBrutoMensual').text('$' + total_salario_bruto_mensual_table.toFixed(2));
                            $('#totalIsssEmpleado').text('$' + total_isss_empleado_table.toFixed(2));
                            $('#totalAfpEmpleado').text('$' + total_afp_empleado_table.toFixed(2));
                            $('#totalRentaEmpleado').text('$' + total_renta_empleado_table.toFixed(2));
                            $('#totalIsssPatronal').text('$' + total_isss_patronal_table.toFixed(2));
                            $('#totalAfpPatronal').text('$' + total_afp_patronal_table.toFixed(2));
                            $('#totalSalarioNetoMensual').text('$' + total_salario_neto_mensual_table.toFixed(2));
                        }
                    });
                    // Ajustar columnas y redibujar la tabla después de la carga inicial
                    // Esto es importante para que DataTables se ajuste al ancho de la tarjeta.
                    reporteMensualDataTable.columns.adjust().responsive.recalc();
                } else {
                    toastr.warning("No se encontraron datos para el mes y año seleccionados.", "Sistema");
                    $("#reporteMensualCardContainer").hide(); // Ocultar si no hay datos
                    $("#PantallaPrincipal").show(); // Volver a mostrar el formulario
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                toastr.error("Error al cargar el reporte mensual: " + textStatus + " - " + errorThrown, "Sistema");
                console.error("AJAX Error: ", textStatus, errorThrown, jqXHR);
                $("#reporteMensualCardContainer").hide(); // Ocultar si hay error
                $("#PantallaPrincipal").show(); // Volver a mostrar el formulario
            }
        });
    });

    // Removido: El evento shown.bs.modal ya no es necesario ya que no usamos modal
    // $('#reporteMensualModal').on('shown.bs.modal', function () {
    //     if (reporteMensualDataTable) {
    //         reporteMensualDataTable.columns.adjust().responsive.recalc();
    //     }
    // });

// CUANDO SE ENCUENTRA EL CAMBIO DEL DEPARTAMENTO EN LA EMPRESA
	$("#lstDepartamentoEmpresa").change(function () {
		var miselect=$("#lstDepartamentoEmpresa");

		$("#lstDepartamentoEmpresa option:selected").each(function () {
				elegido=$(this).val();
				if(elegido == '02'){
					$("#DivRuta").show();
				}else{
					$("#DivRuta").hide();
				}
			});
	});
///////////////////////////////////////////////////////
// Validar Formulario, para la busqueda de un registro por codigo del motorista.
 //////////////////////////////////////////////////////
	$('#form').validate({
		ignore:"",
		rules:{
				lstPersonalPorMotorista: {required: true},
				},
				errorElement: "em",
				errorPlacement: function ( error, element ) {
					// Add the `invalid-feedback` class to the error element
					error.addClass( "invalid-feedback" );
					if ( element.prop( "type" ) === "checkbox" ) {
						error.insertAfter( element.next( "label" ) );
					} else {
						error.insertAfter( element );
					}
				},
					highlight: function ( element, errorClass, validClass ) {
								$( element ).addClass( "is-invalid" ).removeClass( "is-valid" );
							},
					unhighlight: function (element, errorClass, validClass) {
								$( element ).addClass( "is-valid" ).removeClass( "is-invalid" );
							},
					invalidHandler: function() {
						setTimeout(function() {
							toastr.error("Faltan Datos...");
					});            
				},
			submitHandler: function(){	
			var str = $('#formBuscarPorMotorista').serialize();
			// VALIDAR CONDICIÓN DE CONTRASEÑA.
			fecha = $("#FechaProduccion").val();
			//alert(str);

			///////////////////////////////////////////////////////////////			
			// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
			///////////////////////////////////////////////////////////////
				$.ajax({
					beforeSend: function(){
						$('#listadoPorMotoristaOk').empty();
					},
					cache: false,
					type: "POST",
					dataType: "json",
					url:"php_libs/soporte/ReporteGeneral.php",
					data:str + "&id=" + Math.random() + "&fecha=" + fecha,
					success: function(response){
						// Validar mensaje de error
						if(response.respuesta == false){
							toastr["error"](response.mensaje, "Sistema");
						}
						else{
							toastr["success"](response.mensaje, "Sistema");
							$("label[for='LblProduccionesTotalPorMotorista']").text('Cantidad Tiquetes Vendidos ' + response.cantidadTiquete);
							$("label[for='LblProduccionesTotalIngresoPorMotorista']").text('Total Ingresos $ ' + response.totalIngreso);
							//
							$('#listadoPorMotoristaOk').append(response.contenido);
							}               
					},
				});
			},
	});
});	// final de FUNCTION.
// ABRE OTRA PESTAÑA	
function AbrirVentana(url)
{
    window.open(url, '_blank');
    return false;
}
///////////////////////////////////////////////////////////////////////////////
//	FUNCION PARA CAMBIAR LA FORMA DE BUSQUEDA
function filterGlobal() {
    $('#listado').DataTable().search(
        $('#global_filter').val(),
    ).draw();
}
// TODAS LAS TABLAS VAN HA ESTAR EN PRODUCCIONBUSCAR.*******************
// FUNCION LISTAR TABLA catalogo_ruta
////////////////////////////////////////////////////////////
function listar_ruta(codigo_ruta){
    var miselect=$("#lstRuta");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/Produccion/ProduccionBuscar.php", {accion_buscar: 'BuscarRuta'},
        function(data) {
            miselect.empty();
			miselect.append("<option value='00'>Seleccionar...</option>");
            for (var i=0; i<data.length; i++) {
                if(codigo_ruta == data[i].codigo){
                    miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + '</option>');
                }else{
                    miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
                }
            }
    }, "json");    
}
// TODAS LAS TABLAS VAN HA ESTAR EN PRODUCCIONBUSCAR.*******************
// FUNCION LISTAR TABLA catalogo_ruta
////////////////////////////////////////////////////////////
function listar_ann(codigo_ann){
    var miselect=$("#lstFechaAño");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_ann.php", 
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                if(codigo_ann == data[i].codigo){
                    miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + '</option>');
                }else{
                    miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
                }
            }
    }, "json");    
}
// FUNCION LISTAR DEPARTAMENTO CARGO
////////////////////////////////////////////////////////////
function listar_departamento_cargo(){
    var miselect=$("#lstDepartamentoEmpresa");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_departamento_cargo.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
