// id de user global
var id_ = 0;
var accion = "todos";
var tabla = "";
var miselect = "";
var today = "";
// variables para la busqueda por numero de placa de la unidad.
var OptBuscarUP = "Todo"; var NombreInstitucion = ""; var OptBuscarPM = "Todo";
$(function(){ // iNICIO DEL fUNCTION.
///////////////////////////////////////////////////////////////////////////////
// INICIALIZARA DATATABLE. POR PLACA
///////////////////////////////////////////////////////////////////////////////
	$('#example').DataTable( { searching: false} );
    var table = $('#listadoPorUnidadPlaca').DataTable( {
      searching: false
  	});

// POR MOTORISTA
  $('#example1').DataTable( { searching: false} );
  var table_m = $('#listadoPorMotorista').DataTable( {
	searching: false
});
///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
$(document).ready(function(){
		//
		// configurar el Select2
		$('#lstPersonal').select2({
			theme: "bootstrap4"
		});
		// configurar el Select2
		$('#lstPersonalPorMotorista').select2({
			theme: "bootstrap4"
		});
		//
		if($('#MenuTab').val() == '000'){
			$("#DivSoloParaContabilidad").hide();
		}
		//  NOMBRE INSTITUCIÓN
		NombreInstitucion = $("#NombreInstitucion").val();
});		
///////////////////////////////////////////////////////////////////////////////
/// EVENTOS JQUERY Y BOTON NUEVO REGISTRO. CALCULO Y OTROS
///////////////////////////////////////////////////////////////////////////////
$("#lstPersonal").on('change', function(){
	var nombre = $("#lstPersonal option:selected").text();
	//var partial = nombre.split("|");
	//$("#txtnombres").val(partial[1].trim());
	$("#txtnombres").val(nombre);

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
$('#FechaProduccion').val(today);
///////////////////////////////////////////////////////////////////////////////
//	FUNCION que al dar clic buscar el registro para posterior mente abri una
// 	PRODUCCION BUSCAR POR FECHA.
///////////////////////////////////////////////////////////////////////////////	  
$('#goBuscarProduccion').on( 'click', function () {
	BuscarProduccionPorFecha();
});
///////////////////////////////////////////////////////////////////////////////
// CUANDO CAMBIA LA FECHA. BUSCAR LA PRODUCCIÓN EN LA TABLA
/// EVENTOS JQUERY IMPRIMIR TODA LA PRODUCCIÓN O POR RANGO.
///////////////////////////////////////////////////////////////////////////////	  
$("#NumeroCorrelativo").on('keyup', function (e) {
	var keycode = e.keyCode || e.which;
	this.value = (this.value + '').replace(/[^0-9]/g, '');
		if (keycode == 13) {
			// Sólo muestra la Producción.
				BuscarProduccionPorIdTabla();
      	}
  });
///////////////////////////////////////////////////////////////////////////////
// CUANDO CAMBIA LA FECHA. BUSCAR LA PRODUCCIÓN EN LA TABLA
/// EVENTOS JQUERY IMPRIMIR TODA LA PRODUCCIÓN O POR RANGO.
///////////////////////////////////////////////////////////////////////////////	  
$("#goReporteGeneral").on('click', function (e) {
		// Limpiar datos
		fecha = $("#FechaProduccion").val();
		// Ejecutar Informe
			varenviar = "/acomtus/php_libs/reportes/Ingresos/Diario.php?fecha="+fecha;
		// Ejecutar la función abre otra pestaña.
			AbrirVentana(varenviar);   
  });
///////////////////////////////////////////////////////////////////////////////	  
$("#goReporteGeneralUnidadTransporte").on('click', function (e) {
	// Limpiar datos
	fecha = $("#FechaProduccion").val();
	// Ejecutar Informe
		varenviar = "/acomtus/php_libs/reportes/Ingresos/PorUnidadTransporte.php?fecha="+fecha;
	// Ejecutar la función abre otra pestaña.
		AbrirVentana(varenviar);   
});
///////////////////////////////////////////////////////////////////////////////	  
$("#goReporteGeneralMotorista").on('click', function (e) {
	// Limpiar datos
	fecha = $("#FechaProduccion").val();
	// Ejecutar Informe
		varenviar = "/acomtus/php_libs/reportes/Ingresos/PorMotorista.php?fecha="+fecha;
	// Ejecutar la función abre otra pestaña.
		AbrirVentana(varenviar);   
});
///////////////////////////////////////////////////////////////////////////////	  
// BLOQUE DE BUSQUEDA
///////////////////////////////////////////////////////////////////////////////	  
$("#goBuscarPorMotorista").on('click', function (e) {
	// Ocultar Field
	$("#ProduccionTabla").hide();
	$("#field_produccion_detalle").hide();
	$("#FieldsetTabla").hide();
	//
	$("#ProduccionDiferencias").hide();
	// fieldset buscar por motorista
	$("#BuscarPorMotorista").show();
	// fieldset buscar por N.º UNIDAD Y PLACA.
	$("#BuscarPorUnidadPlaca").hide();
	//
	miselect=$("#lstPersonalPorMotorista");
	//
	$('#listadoPorMotoristaOk').empty();
	//
	listar_personal();
});
$("#goBuscarPorUnidad").on('click', function (e) {
	// Ocultar Field
	$("#ProduccionTabla").hide();
	$("#field_produccion_detalle").hide();
	$("#FieldsetTabla").hide();
	//
	$("#ProduccionDiferencias").hide();
	// fieldset buscar por motorista
	$("#BuscarPorMotorista").hide();
	// fieldset buscar por N.º UNIDAD Y PLACA.
	$("#BuscarPorUnidadPlaca").show();
	//
	miselect=$("#lstPorUnidadPlaca");
	//
	$('#listadoPorUnidadPlacaOk').empty();
	//
	listar_unidad_transporte();
});
///////////////////////////////////////////////////////////////////////////////	  
// SELECCIONAR POR MEDIO DEL RADIO BUTTON PARA LA BUSQUEDA DEL MOTORISTA.
///////////////////////////////////////////////////////////////////////////////	  
$("#radioTodoPM").on('click', function (e) {
	// LImpiar Tabla
	$('#listadoPorMotoristaOk').empty();
	//
	$("label[for='LblProduccionesTotalPorMotorista']").text('0');
	$("label[for='LblProduccionesTotalIngresoPorMotorista']").text('$');
	//
	$("#FechaDesdePM").prop("readonly", true);
	$("#FechaHastaPM").prop("readonly", true);
	//
	OptBuscarPM = this.value;
});
$("#radioFechaPM").on('click', function (e) {
	// LImpiar Tabla
	$('#listadoPorMotoristaOk').empty();
	//
	$('#FechaDesdePM').val(today);
	$('#FechaHastaPM').val(today);
	//
	$("#FechaDesdePM").prop("readonly", false);
	$("#FechaHastaPM").prop("readonly", false);
	//
	$("label[for='LblProduccionesTotalPorMotorista']").text('0');
	$("label[for='LblProduccionesTotalIngresoPorMotorista']").text('$');
	//
	OptBuscarPM = this.value;
});
///////////////////////////////////////////////////////////////////////////////	  
// SELECCIONAR POR MEDIO DEL RADIO BUTTON PARA LA BUSQUEDA DEL N.º DE UNIDAD Y PLACA.
///////////////////////////////////////////////////////////////////////////////	  
$("#radioTodoUP").on('click', function (e) {
	// LImpiar Tabla
	$('#listadoPorUnidadPlacaOk').empty();
	//
	$("label[for='LblProduccionesTotalPorUnidadPlaca']").text('0');
	$("label[for='LblProduccionesTotalIngresoPorUnidadPlaca']").text('$ ');
	//
	$("#FechaDesdeUP").prop("readonly", true);
	$("#FechaHastaUP").prop("readonly", true);
	//
	//alert(this.value);
	OptBuscarUP = this.value;
});
$("#radioFechaUP").on('click', function (e) {
	// LImpiar Tabla
	$('#listadoPorUnidadPlacaOk').empty();
	//
	$('#FechaDesdeUP').val(today);
	$('#FechaHastaUP').val(today);
	//
	$("#FechaDesdeUP").prop("readonly", false);
	$("#FechaHastaUP").prop("readonly", false);
	//
	$("label[for='LblProduccionesTotalPorUnidadPlaca']").text('0');
	$("label[for='LblProduccionesTotalIngresoPorUnidadPlaca']").text('$ ');
	//
	//alert(this.value);
	OptBuscarUP = this.value;
});
//////////////////////////////////////////////////////////////////////////////////
/* BUSQUEDA DE CONTROL POR NUMERO DE TIQUETE. */
//////////////////////////////////////////////////////////////////////////////////
$('#goBuscarPorTiquete').on('click', function(){
	//
	$("#BusquedaNumeroControl").val('');
	$("#BusquedaFechaControl").val('');
	$("#BusquedaPersonalControl").val('');
	$("#BusquedaRutaControl").val('');
	$("#BusquedaJornadaControl").val('');
	$("#BusquedaUnidadPlacaControl").val('');
	$("#BusquedaNumeroVueltasControl").val('');
	$("#BusquedaTotalIngresoControl").val('');
	$("#BusquedaEstatusControl").val('');
	//
	$("#BusquedaNumerotiquete").val('');
	//
	$('#VentanaBuscarPorTiquete').modal("show");
	//
	listar_serie();
});
// RELLENAR LA TABLA TIENEN EN CONTROL
// fecha y N.º Control.
$('#BusquedaNumerotiquete').on('keyup', function(e){
	var keycode = e.keyCode || e.which;
	// Limipiar variables inputl text.
	this.value = (this.value + '').replace(/[^0-9]/g, '');
	// Al presionar la tecla Enter.
	if (keycode == 13) {
	  // Limpiar datos
		  numero_tiquete = $('#BusquedaNumerotiquete').val();
		  serie = $("#lstSerieBuscarTiquete").val();
		// ejecutar Ajax.. ACTUALIZA5 INDICADORES DE MATRICULA.
			$.ajax({
				beforeSend: function(){  
					$('#listadoTiqueteEnControlOk').empty();
					//
						$("#BusquedaNumeroControl").val('');
						$("#BusquedaFechaControl").val('');
						$("#BusquedaPersonalControl").val('');
						$("#BusquedaRutaControl").val('');
						$("#BusquedaJornadaControl").val('');
						$("#BusquedaUnidadPlacaControl").val('');
						$("#BusquedaNumeroVueltasControl").val('');
						$("#BusquedaTotalIngresoControl").val('');
						$("#BusquedaEstatusControl").val('');
				},
			cache: false,                     
			type: "POST",                     
			dataType: "json",                     
			url:"php_libs/soporte/ProduccionBuscar.php",                     
			data: {                     
					accion_buscar: 'BuscarPorTiqueteEnControl', numero_tiquete: numero_tiquete, serie: serie,
					},                     
			success: function(response) {                     
				if (response.respuesta === true) {                     
					// lIMPIAR LOS VALORES DE LAS TABLAS.                     
					$('#listadoTiqueteEnControlOk').append(response.contenido);		
						toastr["info"](response.mensaje, "Sistema");				                  
				}else{
					toastr["error"](response.mensaje, "Sistema");		
					$('#listadoTiqueteEnControlOk').append(response.contenido);		                  
				}  
			}                     
			});
		//
	}
});
// BUSCAR DENTRO DE LA TABLA TIQUETE EN CONTROL.
$('body').on('click','#listadoTiqueteEnControl a',function (e){
	e.preventDefault();
	  // Limpiar datos
		  numero_control = $(this).attr('href');
		  accionAsignacion = $(this).attr('data-accion');
		  numero_tiquete = $('#BusquedaNumerotiquete').val();
		  serie = $("#lstSerieBuscarTiquete").val();
		// Concionales del accion.
		  if(accionAsignacion  == 'BuscarPorTiquete'){	
			// ejecutar Ajax.. ACTUALIZA5 INDICADORES DE MATRICULA.
			$.ajax({
				cache: false,                     
				type: "POST",                     
				dataType: "json",                     
				url:"php_libs/soporte/ProduccionBuscar.php",                     
				data: {                     
						accion_buscar: accionAsignacion, NumeroControl: numero_control, numero_tiquete: numero_tiquete, serie: serie,
						},                     
				success: function(data) {                     
						if (data[0].respuesta === true) {                     
							//
								$("#BusquedaNumeroControl").val(data[0].codigo_produccion);
								$("#BusquedaFechaControl").val(data[0].fecha);
								$("#BusquedaPersonalControl").val(data[0].nombre_personal);
								$("#BusquedaRutaControl").val(data[0].ruta);
								$("#BusquedaJornadaControl").val(data[0].jornada);
								$("#BusquedaUnidadPlacaControl").val(data[0].unidad);
								$("#BusquedaNumeroVueltasControl").val(data[0].numero_vueltas);
								$("#BusquedaTotalIngresoControl").val(data[0].total_ingreso);
								$("#BusquedaEstatusControl").val(data[0].estatus);
								//$("#").val(data[0].);
								toastr["info"](data[0].mensaje, "Sistema");				                  
						}                
						if (data[0].respuesta === false) {                     
							$("#BusquedaNumeroControl").val('');
							$("#BusquedaFechaControl").val('');
							$("#BusquedaPersonalControl").val('');
							$("#BusquedaRutaControl").val('');
							$("#BusquedaJornadaControl").val('');
							$("#BusquedaUnidadPlacaControl").val('');
							$("#BusquedaNumeroVueltasControl").val('');
							$("#BusquedaTotalIngresoControl").val('');
							$("#BusquedaEstatusControl").val('');
							//
							$("#BusquedaNumeroControl").val(data[0].codigo_produccion);
							$("#BusquedaFechaControl").val(data[0].fecha);
							$("#BusquedaPersonalControl").val(data[0].nombre_personal);
							$("#BusquedaRutaControl").val(data[0].ruta);
							$("#BusquedaJornadaControl").val(data[0].jornada);
							$("#BusquedaUnidadPlacaControl").val(data[0].unidad);
							$("#BusquedaNumeroVueltasControl").val(data[0].numero_vueltas);
							$("#BusquedaTotalIngresoControl").val(data[0].total_ingreso);
							$("#BusquedaEstatusControl").val(data[0].estatus);
							//$("#").val(data[0].);
							toastr["error"](data[0].mensaje, "Sistema");				   
						}
				}                     
				});
		  }
});
///////////////////////////////////////////////////////////////////////////////	  
// BLOQUE EXTRAER INFORMACIÓN DEL REGISTROS (PRODUCCION ASIGNADO)
$('body').on('click','#listadoVerControles a',function (e){
	e.preventDefault();
// DATA-ACCION Y HREF
	id_ = $(this).attr('href');
	accionAsignacion = $(this).attr('data-accion');
	// EDTIAR REGISTRO.
		if(accionAsignacion  == 'VerProduccion'){	
			//
			accion = "EditarRegistro";	// variable global
			window.location.href = 'editar_Nuevo_Produccion.php?id='+id_+"&accion="+accion;
			//
		}else if(accionAsignacion  == 'VerEliminarProduccion'){	
			fecha = $('#FechaProduccion').val();
			//	ENVIAR MENSAJE CON SWEETALERT 2, PARA CONFIRMAR SI ELIMINA EL REGISTRO.
			const swalWithBootstrapButtons = Swal.mixin({
				customClass: {
				confirmButton: 'btn btn-success',
				cancelButton: 'btn btn-danger'
				},
				buttonsStyling: false
			})
	
			swalWithBootstrapButtons.fire({
				title: '¿Qué desea hacer?',
				text: 'Eliminar el Registro Seleccionado!',
				showCancelButton: true,
				confirmButtonText: 'Sí, Eliminar!',
				cancelButtonText: 'No, Cancelar!',
				reverseButtons: true,
				allowOutsideClick: false,
				allowEscapeKey: false,
				allowEnterKey: false,
				stopKeydownPropagation: false,
				closeButtonAriaLabel: 'Cerrar Alerta',
				type: 'question'
			}).then((result) => {
				if (result.value) {
				// PROCESO PARA ELIMINAR REGISTRO.
						// ejecutar Ajax.. 
						$.ajax({
							beforeSend: function(){  
								$('#listadoVerControlesOk').empty();
							},
						cache: false,                     
						type: "POST",                     
						dataType: "json",                     
						url:"php_libs/soporte/ProduccionBuscar.php",                     
						data: {                     
								accion_buscar: 'VerEliminarProduccion', codigo_produccion: id_, fecha: fecha,
								},                     
						success: function(response) {                     
								if (response.respuesta === true) {                     
									// lIMPIAR LOS VALORES DE LAS TABLAS.                     
									$('#listadoVerControlesOk').append(response.contenido);		
										toastr["error"](response.mensaje, "Sistema");				                  
								}                
						}                     
						});
				//////////////////////////////////////
				} else if (
				/* Read more about handling dismissals below */
				result.dismiss === Swal.DismissReason.cancel
				) {
				swalWithBootstrapButtons.fire(
					'Cancelar',
					'Su Archivo no ha sido Eliminado :)',
					'error'
				)
				}
			})
		}   // FINAL DEL IF DE LA CONSULTA QUE PROVIENE DE LA TABLA
});
///////////////////////////////////////////////////////////////////////////////
/// EVENTOS JQUERY Y para disparar la busqueda. del por nombre motorista.
///////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////
// Validar Formulario, para la busqueda de un registro por codigo del motorista.
 //////////////////////////////////////////////////////
 $('#goBuscarProduccionPM').on('click', function(){
	// focus
	  $("#lstPersonalPorMotorista").focus()
	  var buscartodos = "BuscarPorMotorista";
	  var CodigoPersonal = $("#lstPersonalPorMotorista").val();
	  var NombreCodigoPersonal = $("#lstPersonalPorMotorista option:selected").text();
	  var FechaHastaPM = $("#FechaHastaPM").val();
	  var FechaDesdePM = $("#FechaDesdePM").val();
   
	  ///////////////////////////////////////////////////////////////////////////////	  
	//
	   if (table_m != undefined && table_m != null) 
	   {
	   table_m.destroy();
	   table_m = null;
	   } 
	// CONFIGURACIÓN DE LA TABLA CON DATATABLE.
	table_m = jQuery("#listadoPorMotorista").DataTable( {
		"ajax":{
		  lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
		  destroy: true,
		  pageLength: 5,
		  searching: false,
		  url:"php_libs/soporte/ReporteGeneral.php",
		  method:"POST",
		  data: {"accion_buscar": buscartodos, codigo_personal: CodigoPersonal, OptBuscarPM: OptBuscarPM, 
			  FechaDesdePM: FechaDesdePM, FechaHastaPM: FechaHastaPM
			},
		  datatype: "json"
		},
		columns:[
		  {data:"fecha_"},
		  {data:"id_"},
		  {data:"numero_equipo_placa"},
		  {data:"descripcion_ruta"},
		  {data:"precio_publico",
				render: function(data, type, row){
					return `<span class='font-weight-bold text-success text-right'>$`+data+`</span>`;
				}
			},	// Preico Púyblic.
		  {data:"cantidadtiquete"},
		  {data:"total_ingreso_por_bus",
				render: function(data, type, row){
						return `<span class='font-weight-bold text-success text-right'>$`+data+`</span>`;
				}
			}	/// total ingresos
		],
		"footerCallback": function ( row, data, start, end, display ) {
			var api = this.api(), data;

			// Remove the formatting to get integer data for summation
			var intVal = function ( i ) {
				return typeof i === 'string' ?
					i.replace(/[\$,]/g, '')*1 :
					typeof i === 'number' ?
						i : 0;
			};

			// Total over all pages
			total = api
				.column( 6 )
				.data()
				.reduce( function (a, b) {
					return intVal(a) + intVal(b);
				}, 0 );
				total = total.toFixed(2),
				TotalAllPagina = new Intl.NumberFormat('en-US').format(total)
			// Total over this page
			pageTotal = api
				.column( 6, { page: 'current'} )
				.data()
				.reduce( function (a, b) {
					return intVal(a) + intVal(b);
				}, 0 );
				pagina = pageTotal.toFixed(2),
				TotalPagina = new Intl.NumberFormat('en-US').format(pagina)
			// Update footer
			$( api.column( 6 ).footer() ).html(
				'$'+ TotalPagina +' ( $'+ TotalAllPagina +')'
			);
		},
		language:{
		  url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json"
		},
		dom: "Bfrtip",
		  buttons:[
			//'excel',
			{
			  extend: 'excelHtml5',
			  text: '<i class="fas fa-file-excel"></i>',
			  titleAttr: 'Exportar a Excel',
			  className: 'btn btn-success',
			  filename: 'Reporte',
			  title: NombreInstitucion,
			  exportOptions: {
				  columns: [0,1,2,3,4,5,6,]
			  },
			  className: 'btn-exportar-excel',
		  },
		  //'pdf',
		  {
			  extend: 'pdfHtml5',
			  text: '<i class="fas fa-file-pdf"></i>',
			  titleAttr: 'Exportar a PDF',
			  className: 'btn btn-danger',
			  filename: 'Reporte',
			  title: NombreInstitucion + " " + NombreCodigoPersonal,
			  exportOptions: {
				  columns: [0,1,2,3,4,5,6,]
			  },
			  className: 'btn-exportar-pdf',
		  },
		  //'print'
		  {
			  extend: 'print',
			  text: '<i class="fa fa-print"></i>',
			  titleAttr: 'Imprimir',
			  className: 'btn btn-md btn-info',
			  title: NombreInstitucion,
			  exportOptions: {
				  columns: [0, 1,2,3,4,5,6,]
			  },
			  className:'btn-exportar-print'
		  },
		  //extra. Cantidad de registros.
		  'pageLength'
		  ],
	  }); // FINAL DEL DATATABLE.
		///////////////////////////////////////////////////////////////			
		// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
		///////////////////////////////////////////////////////////////
		   $.ajax({
		   beforeSend: function(){
		   },
		   cache: false,
		   type: "POST",
		   dataType: "json",
		   url:"php_libs/soporte/ReporteGeneral.php",
			data: {"accion_buscar": buscartodos, codigo_personal: CodigoPersonal, OptBuscarPM: OptBuscarPM, 
				FechaDesdePM: FechaDesdePM, FechaHastaPM: FechaHastaPM
			},
		   success: function(data){
			   // Validar mensaje de error
			   $("label[for='LblProduccionesTotalPorMotorista']").text(' ' + data[1].dataTotalTiquete );
			   $("label[for='LblProduccionesTotalIngresoPorMotorista']").text('$' + data[1].dataTotalIngreso);
			   // imagen
			   // FOTO DEL EMPLEADO.
					$("#ImagenPersonalGlobal").attr("src", data[1].foto);	
		   },
		   });	// fin del ajax
   }); // boton
///////////////////////////////////////////////////////
// Validar Formulario, para la busqueda de un registro por codigo del motorista.
// INCORPORACION DE DATATABLE.
 //////////////////////////////////////////////////////
// boton
$('#goBuscarPorUnidadDeTransporte').on('click', function(){
 // focus
   $("#lstPorUnidadPlaca").focus()
   var buscartodos = "BuscarTodosUnidadPlaca";
   var NumeroPlaca = $("#lstPorUnidadPlaca").val();
   var FechaHastaUP = $("#FechaHastaUP").val();
   var FechaDesdeUP = $("#FechaDesdeUP").val();

   ///////////////////////////////////////////////////////////////////////////////	  
 //
	if (table != undefined && table != null) 
	{
	table.destroy();
	table = null;
	} 
	
 // CONFIGURACIÓN DE LA TABLA CON DATATABLE.
 table = jQuery("#listadoPorUnidadPlaca").DataTable( {
	 "ajax":{
	   lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
	   destroy: true,
	   pageLength: 5,
	   searching: false,
	   url:"php_libs/soporte/ReporteGeneral.php",
	   method:"POST",
	   data: {"accion_buscar": buscartodos, codigo_up: NumeroPlaca, OptBuscarUP: OptBuscarUP, 
	   FechaDesdeUP: FechaDesdeUP, FechaHastaUP: FechaHastaUP
			 },
	   datatype: "json"
	 },
	 columns:[
	   {data:"fecha_"},
	   {data:"id_"},
	   {data:"codigo"},
	   {data:"nombre_motorista"},
	   {data:"descripcion_ruta"},
	   {data:"precio_publico",
			render: function(data, type, row){
				return `<span class='font-weight-bold text-success text-right'>$`+data+`</span>`;
			}
		},	// Preico Púyblic.
	   {data:"cantidadtiquete"},
	   {data:"total_ingreso_por_bus",
			render: function(data, type, row){
					return `<span class='font-weight-bold text-success text-right'>$`+data+`</span>`;
			}
		}	/// total ingresos
	 ],
	 "footerCallback": function ( row, data, start, end, display ) {
		var api = this.api(), data;

		// Remove the formatting to get integer data for summation
		var intVal = function ( i ) {
			return typeof i === 'string' ?
				i.replace(/[\$,]/g, '')*1 :
				typeof i === 'number' ?
					i : 0;
		};

		// Total over all pages
		total = api
			.column( 7 )
			.data()
			.reduce( function (a, b) {
				return intVal(a) + intVal(b);
			}, 0 );
			total = total.toFixed(2),
			TotalAllPagina = new Intl.NumberFormat('en-US').format(total)
		// Total over this page
		pageTotal = api
			.column( 7, { page: 'current'} )
			.data()
			.reduce( function (a, b) {
				return intVal(a) + intVal(b);
			}, 0 );
			pagina = pageTotal.toFixed(2),
			TotalPagina = new Intl.NumberFormat('en-US').format(pagina)
		// Update footer
		$( api.column( 7 ).footer() ).html(
			'$'+ TotalPagina +' ( $'+ TotalAllPagina +')'
		);
	},
	 language:{
	   url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json"
	 },
	 dom: "Bfrtip",
	   buttons:[
		 //'excel',
		 {
		   extend: 'excelHtml5',
		   text: '<i class="fas fa-file-excel"></i>',
		   titleAttr: 'Exportar a Excel',
		   className: 'btn btn-success',
		   filename: 'Reporte',
		   title: NombreInstitucion,
		   exportOptions: {
			   columns: [0,1,2,3,4,5,6,7 ]
		   },
		   className: 'btn-exportar-excel',
	   },
	   //'pdf',
	   {
		   extend: 'pdfHtml5',
		   text: '<i class="fas fa-file-pdf"></i>',
		   titleAttr: 'Exportar a PDF',
		   className: 'btn btn-danger',
		   filename: 'Reporte',
		   title: NombreInstitucion,
		   exportOptions: {
			   columns: [0,1,2,3,4,5,6,7 ]
		   },
		   className: 'btn-exportar-pdf',
	   },
	   //'print'
	   {
		   extend: 'print',
		   text: '<i class="fa fa-print"></i>',
		   titleAttr: 'Imprimir',
		   className: 'btn btn-md btn-info',
		   title: NombreInstitucion,
		   exportOptions: {
			   columns: [0, 1,2,3,4,5,6,7 ]
		   },
		   className:'btn-exportar-print'
	   },
	   //extra. Cantidad de registros.
	   'pageLength'
	   ],
   }); // FINAL DEL DATATABLE.
 	///////////////////////////////////////////////////////////////			
	 // Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
	 ///////////////////////////////////////////////////////////////
		$.ajax({
		beforeSend: function(){
		},
		cache: false,
		type: "POST",
		dataType: "json",
		url:"php_libs/soporte/ReporteGeneral.php",
		data: {
			accion_buscar: buscartodos, codigo_up: NumeroPlaca, OptBuscarUP: OptBuscarUP, FechaDesdeUP: FechaDesdeUP, FechaHastaUP: FechaHastaUP
		},
		success: function(data){
			// Validar mensaje de error
			$("label[for='LblProduccionesTotalPorUnidadPlaca']").text(' ' + data[1].dataTotalTiquete );
			$("label[for='LblProduccionesTotalIngresoPorUnidadPlaca']").text('$' + data[1].dataTotalIngreso);
			//
		},
		});	// fin del ajax
}); // boton

///////////////////////////////////////////////////////////////////////////////
// PROCESO PARA EL INFORME INGRESOS DIARIOS.
// PRODUCCION DIFERENCIAS.
///////////////////////////////////////////////////////////////////////////////	  
$("#goProduccionDiferencias").on('click', function (e) {
	// Ocultar Field
	$("#ProduccionTabla").hide();
	$("#field_produccion_detalle").hide();
	$("#FieldsetTabla").hide();
	//
	$("#ProduccionDiferencias").show();
	// fieldset buscar por motorista
	$("#BuscarPorMotorista").hide();
	$("#BuscarPorUnidadPlaca").hide();
	$("#NumeroCorrelativo").prop('disabled', true);
	$("#goReporteGeneral").prop('disabled', true);
	$("#goBuscarProduccion").prop('disabled', true);
	//
	$("#FechaProduccion").prop('readonly', true);
	///////////////////////////////////////////////////////////////			
		// Inicio del Ajax. ver todos por fecha.
	///////////////////////////////////////////////////////////////
		fecha = $("#FechaProduccion").val();
	//
		$.ajax({
			beforeSend: function(){
				$('#listadoDiferenciasOk').empty();
				miselect=$("#lstPersonal");
				listar_personal();
			},
			cache: false,
			type: "POST",
			dataType: "json",
			url:"php_libs/soporte/NuevoEditarProduccionDiferencias.php",
			data:"accion=" + accion + "&id=" + Math.random() + "&fecha=" + fecha,
			success: function(response){
				// Validar mensaje de error
				if(response.respuesta == false){
					toastr["error"](response.mensaje, "Sistema");
				}
				else{
					toastr["success"](response.mensaje, "Sistema");
					$('#listadoDiferenciasOk').append(response.contenido);
					}               
			},
		});
});
//
$("#goDiferenciasCancelar").on('click', function (e) {
	// Ocultar Field
	$("#ProduccionTabla").hide();
	$("#field_produccion_detalle").hide();
	$("#FieldsetTabla").hide();
	//
	$("#ProduccionDiferencias").hide();
	//
	$("#NumeroCorrelativo").prop('disabled', false);
	$("#goReporteGeneral").prop('disabled', false);
	$("#goBuscarProduccion").prop('disabled', false);
	//
	$("#FechaProduccion").prop('readonly', false);
	// Limpiar Tabla.
	$('#listadoDiferenciasOk').empty();
	// limpiar campos
	$("#txtnombres").val('');
	$("#Valor").val('');
	$("#concepto").val('');
	$("#accion").val('Agregar');
	$("#id_user").val(0);
});
///////////////////////////////////////////////////////
// Validar Formulario, para posteriormente Guardar o Modificarlo.
 //////////////////////////////////////////////////////
 $('#formDiferencias').validate({
	ignore:"",
	rules:{
			txtnombres: {required: true, minlength: 4},
			Valor: {required: true, minlength: 4},
			concepto: {required: true,minlength: 4},
			//lstempresa: {required: true},
			lstperfil: {required: true},
			//lstpersonal: {required: true},
			lstestatus: {required: true},
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
		var str = $('#formDiferencias').serialize();
		// VALIDAR CONDICIÓN DE CONTRASEÑA.
		//if($('#chkcambiopassword').is(":checked")) {chkcambiopassword = 'yes';}else{chkcambiopassword = 'no';}                        
		fecha = $("#FechaProduccion").val();
		//alert(str);
		///////////////////////////////////////////////////////////////			
		// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
		///////////////////////////////////////////////////////////////
			$.ajax({
				beforeSend: function(){
					$('#listadoDiferenciasOk').empty();
				},
				cache: false,
				type: "POST",
				dataType: "json",
				url:"php_libs/soporte/NuevoEditarProduccionDiferencias.php",
				data:str + "&id=" + Math.random() + "&fecha=" + fecha,
				success: function(response){
					// Validar mensaje de error
					if(response.respuesta == false){
						toastr["error"](response.mensaje, "Sistema");
					}
					else{
						toastr["success"](response.mensaje, "Sistema");
						$('#listadoDiferenciasOk').append(response.contenido);
						// limpiar campos
						$("#txtnombres").val('');
						$("#Valor").val('');
						$("#concepto").val('');
						$("#accion").val('Agregar');
						$("#id_user").val(0);
						}               
				},
			});
		},
});
///////////////////////////////////////////////////////////////////////////////	  
// BLOQUE EXTRAER INFORMACIÓN DEL REGISTROS (PRODUCCION ASIGNADO)
$('body').on('click','#listadoDiferencia a',function (e){
	e.preventDefault();
// DATA-ACCION Y HREF
	id_ = $(this).attr('href');
	accionAsignacion = $(this).attr('data-accion');
	// EDTIAR REGISTRO.
		if(accionAsignacion  == 'EditarDiferencia'){	
			//
			accion = "BuscarPorId";
		///////////////////////////////////////////////////////////////			
		// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
		///////////////////////////////////////////////////////////////
		$.ajax({
			beforeSend: function(){
				$('#listadoDiferenciasOk').empty();
			},
			cache: false,
			type: "POST",
			dataType: "json",
			url:"php_libs/soporte/NuevoEditarProduccionDiferencias.php",
			data: "id_=" + id_ + "&id=" + Math.random() + "&fecha=" + fecha + "&accion=" + accion,
			success: function(data){
				// Validar mensaje de error
				if(data[0].respuesta == false){
					toastr["error"](data[0].mensaje, "Sistema");
				}
				else{
					toastr["success"](data[0].mensaje, "Sistema");
					// Rellenar los campos con el DATA.
					$("#txtnombres").val(data[0].descripcion);
					$("#Valor").val(data[0].valor);
					$("#concepto").val(data[0].concepto);
					$("#accion").val('EditarRegistro');
					$("#id_user").val(id_);
					}               
			},
		});
			//
		}else if(accionAsignacion  == 'EliminarDiferencia'){	
			fecha = $('#FechaProduccion').val();
			//	ENVIAR MENSAJE CON SWEETALERT 2, PARA CONFIRMAR SI ELIMINA EL REGISTRO.
			const swalWithBootstrapButtons = Swal.mixin({
				customClass: {
				confirmButton: 'btn btn-success',
				cancelButton: 'btn btn-danger'
				},
				buttonsStyling: false
			})
	
			swalWithBootstrapButtons.fire({
				title: '¿Qué desea hacer?',
				text: 'Eliminar el Registro Seleccionado!',
				showCancelButton: true,
				confirmButtonText: 'Sí, Eliminar!',
				cancelButtonText: 'No, Cancelar!',
				reverseButtons: true,
				allowOutsideClick: false,
				allowEscapeKey: false,
				allowEnterKey: false,
				stopKeydownPropagation: false,
				closeButtonAriaLabel: 'Cerrar Alerta',
				type: 'question'
			}).then((result) => {
				if (result.value) {
				// PROCESO PARA ELIMINAR REGISTRO.
						// ejecutar Ajax.. 
						$.ajax({
							beforeSend: function(){  
								$('#listadoDiferenciasOk').empty();
							},
						cache: false,                     
						type: "POST",                     
						dataType: "json",                     
						url:"php_libs/soporte/NuevoEditarProduccionDiferencias.php",                     
						data: {                     
								accion_buscar: 'Eliminar', id_: id_, fecha: fecha,
								},                     
						success: function(response) {                     
								if (response.respuesta === true) {                     
									// lIMPIAR LOS VALORES DE LAS TABLAS.                     
									$('#listadoDiferenciasOk').append(response.contenido);		
										toastr["error"](response.mensaje, "Sistema");				                  
								}                
						}                     
						});
				//////////////////////////////////////
				} else if (
				/* Read more about handling dismissals below */
				result.dismiss === Swal.DismissReason.cancel
				) {
				swalWithBootstrapButtons.fire(
					'Cancelar',
					'Su Archivo no ha sido Eliminado :)',
					'error'
				)
				}
			})
		}   // FINAL DEL IF DE LA CONSULTA QUE PROVIENE DE LA TABLA
});
///////////////////////////////////////////////////////////////////////////////	  
// BLOQUE EXTRAER INFORMACIÓN DEL REGISTROS (PRODUCCION ASIGNACIÓN)
$('body').on('click','#listadoDetalle a',function (e){
	e.preventDefault();
// DATA-ACCION Y HREF
	codigo_produccion = $(this).attr('href');
	accionAsignacion = $(this).attr('data-accion');
	//alert(Id_Editar_Eliminar+" "+accionAsignacion);
// EDTIAR REGISTRO.
	if(accionAsignacion  == 'ProduccionVerAsignacion'){
		// show FIELDSET  PRODUCCION DETALLE
			$("#FieldsetTabla").show();
	///////////////////////////////////////////////////////////////			
	// Inicio del Ajax. Buscar, Actualizar Producción Devolución e Ingreso.
	///////////////////////////////////////////////////////////////
	// Variables accion para guardar datos.
		accion_buscar = "BuscarProduccionPorIdTabla";
	/// BUSCAR SI EXISTE PRODUCCIÓN ENLA FECHA DIGITADA.	
		$.ajax({
			beforeSend: function(){
				$('#listadoDevolucionIngresoOk').empty();
			},
			cache: false,
			type: "POST",
			dataType: "json",
			url:"php_libs/soporte/ReporteGeneral.php",
			data:"accion_buscar=" + accion_buscar + "&codigo_produccion=" + codigo_produccion,
			success: function(response){
			// VERIFICAR SI HAY DATOS EN LA DATA.
				if(response.respuesta == true){
					// focus
						//$("#FechaProduccion").focus().select();
					// vAR DATA
						toastr["info"](response.mensaje, "Sistema");
					// RELLENAR TABLA.
						$('#listadoDevolucionIngresoOk').append(response.contenido);
					//
					// cambiar el valor del ingreso.
					$("#LblDescripcionRuta").html(response.descripcionRuta);
					$("#LblDescripcionUnidad").html(response.descripcionUnidad);
					$("#LblDescripcionCodigo").html(response.codigoPersonal);
					$("#LblListadoIdFecha").html(response.fecha);
					$("label[for='LblNombreMotorista']").text(response.nombreMotorista);
					//
					$("#LblListadoPrecio").html("$ " + response.precioPublico);
					$("#LblListadoTotalIngreso").html("$ " + response.totalIngreso);
					$("#LblListadoCantidad").html(response.cantidadTiquete);
					$("#LblCantidadProduccionesVendidas").html(response.cantidadTiquete);
					// FOTO DEL EMPLEADO.
						if(response.url_foto == "")
						{
							if(response.codigo_genero == "01"){
								$(".card-img-top").attr("src", "../acomtus/img/avatar_masculino.png");
							}else{
								$(".card-img-top").attr("src", "../acomtus/img/avatar_femenino.png");
							}
						}else{
							$(".card-img-top").attr("src", "../acomtus/img/fotos/" + response.url_foto);	
						}
				}
			},	// DATA.
		});
	}
});
//////////////////////////////////////////////////////////////////////////////	  
// BLOQUE EXTRAER INFORMACIÓN DEL REGISTROS (PRODUCCION)
$('body').on('click','#listado a',function (e){
	e.preventDefault();
// DATA-ACCION Y HREF
	codigo_produccion = $(this).attr('href');
	accionAsignacion = $(this).attr('data-accion');
	//alert(Id_Editar_Eliminar+" "+accionAsignacion);
// EDTIAR REGISTRO.
	if(accionAsignacion  == 'ProduccionImprimir'){
		// show FIELDSET  PRODUCCION DETALLE
			$("#field_produccion_detalle").show();

	///////////////////////////////////////////////////////////////			
	// Inicio del Ajax. Buscar, Actualizar Producción Devolución e Ingreso.
	///////////////////////////////////////////////////////////////
	// Variables accion para guardar datos.
		accion_buscar = "BuscarProduccionPorId";
	/// BUSCAR SI EXISTE PRODUCCIÓN ENLA FECHA DIGITADA.	
		$.ajax({
			beforeSend: function(){
				$('#listadoDetalleOk').empty();
			},
			cache: false,
			type: "POST",
			dataType: "json",
			url:"php_libs/soporte/ReporteGeneral.php",
			data:"accion_buscar=" + accion_buscar + "&codigo_produccion=" + codigo_produccion,
			success: function(response){
			// VERIFICAR SI HAY DATOS EN LA DATA.
				if(response.respuesta == true){
					// focus
						//$("#FechaProduccion").focus().select();
					// vAR DATA
						toastr["info"](response.mensaje, "Sistema");
					// RELLENAR TABLA.
						$('#listadoDetalleOk').append(response.contenido);
					//
					$("label[for='LblDetalleTotalIngreso']").text('Total  $ ' + response.totalIngreso);
				}
			},	// DATA.
		});
	}
});
///////////////////////////////////////////////////////
// BOTONES - IMPRIMIR DETALLE PRODUCCIÓN.
 //////////////////////////////////////////////////////
 $("#goImprmirProduccionDiaria").on('click', function (e) {
	// Información.
	var codigo = $('#LblDescripcionCodigo');
	codigo_ = codigo.text();
	var ruta = $('#LblDescripcionRuta');
	ruta_ = ruta.text();
	var unidad = $('#LblDescripcionUnidad');
	unidad_ = unidad.text();
	//	Valores
	var precio = $('#LblListadoPrecio');
	precio_ = precio.text();
	var cantidad = $('#LblListadoCantidad');
	cantidad_ = cantidad.text();
	var total = $('#LblListadoTotalIngreso');
	total_ = total.text();
	//	Tabla.
	var $objCuerpoTabla=$("#listadoAsignacion").children().prev().parent();
	var correlativo_ = [];  var serie_ = []; var cola_ = []; var desde_ = []; var hasta_ = [];
	var ingreso_ = []; var estatus_ = [];
	var fila = 0;
	///////////////////////////////////////////////////////////////////////////////////////////////////
	// recorre el contenido de la tabla.
	///////////////////////////////////////////////////////////////////////////////////////////////////
	$objCuerpoTabla.find("tbody tr").each(function(){
		var correlativo =$(this).find('td').eq(0).html();
		var estatus =$(this).find('td').eq(1).html();
		var serie =$(this).find('td').eq(2).html();
		var cola =$(this).find('td').eq(3).html();
		var desde =$(this).find('td').eq(4).html();
		var hasta =$(this).find('td').eq(5).html();
		var ingreso =$(this).find('td').eq(6).html();
	///////////////////////////////////////////////////////////////////////////////////////////////////
	// VALORES DE LA MATRIZ QUE VIAJAN POR EL POST
	///////////////////////////////////////////////////////////////////////////////////////////////////
		correlativo_[fila] = correlativo.trim();
		estatus_[fila] = estatus.trim();
		serie_[fila] = serie.trim();
		cola_[fila] = cola.trim();
		desde_[fila] = desde.trim();
		hasta_[fila] = hasta.trim();
		ingreso_[fila] = ingreso.trim();
			fila = fila + 1;
	});
	// Limpiar datos
		fecha = $("#FechaProduccion").val();
	// Ejecutar Informe
		varenviar = "/acomtus/php_libs/reportes/Produccion/DetallePorMotorista.php?fecha="+fecha+
					"&correlativo="+correlativo_+"&serie="+serie_+"&cola="+cola_+"&desde="+desde_+"&hasta="+hasta_+"&ingreso="+ingreso_
					+"&estatus="+estatus_+"&NombreMotorista="+NombreMotorista+"&ImagenPersonal="+ImagenFoto
					+"&codigo="+codigo_+"&ruta="+ruta_+"&unidad="+unidad_+"&precio="+precio_+"&cantidad="+cantidad_+"&total="+total_
					;
	// Ejecutar la función abre otra pestaña.
		AbrirVentana(varenviar);


});
///////////////////////////////////////////////////////
// BOTONES - IMPRIMIR DETALLE PRODUCCIÓN.
 //////////////////////////////////////////////////////
$("#goImprmirProduccionDetalle").on('click', function (e) {
	var $objCuerpoTabla=$("#listadoDetalle").children().prev().parent();
	var control_ = [];  var ruta_ = []; var equipo_ = []; var motorista_ = []; var ingreso_ = [];
	var fila = 0;
	///////////////////////////////////////////////////////////////////////////////////////////////////
	// recorre el contenido de la tabla.
	///////////////////////////////////////////////////////////////////////////////////////////////////
	$objCuerpoTabla.find("tbody tr").each(function(){
		var control =$(this).find('td').eq(1).html();
		var ruta =$(this).find('td').eq(2).html();
		var equipo =$(this).find('td').eq(3).html();
		var motorista =$(this).find('td').eq(4).html();
		var ingreso =$(this).find('td').eq(5).html();
	///////////////////////////////////////////////////////////////////////////////////////////////////
	// VALORES DE LA MATRIZ QUE VIAJAN POR EL POST
	///////////////////////////////////////////////////////////////////////////////////////////////////
		control_[fila] = control.trim();
		ruta_[fila] = ruta.trim();
		equipo_[fila] = equipo.trim();
		motorista_[fila] = motorista.trim();
		ingreso_[fila] = ingreso.trim();
			fila = fila + 1;
	});
	// Limpiar datos
		fecha = $("#FechaProduccion").val();
	// Ejecutar Informe
		varenviar = "/acomtus/php_libs/reportes/Produccion/DetalleProduccion.php?fecha="+fecha+
					"&control="+control_+"&ruta="+ruta_+"&equipo="+equipo_+"&motorista="+motorista_+"&ingreso="+ingreso_;
	// Ejecutar la función abre otra pestaña.
		AbrirVentana(varenviar);
});
///////////////////////////////////////////////////////
// BOTONES - IMPRIMIR DETALLE PRODUCCIÓN.
 //////////////////////////////////////////////////////
 $("#goImprmirProduccionDetalleMotorista").on('click', function (e) {
	var LabelNombreMotorista = $('#LblNombreMotorista');
	NombreMotorista = LabelNombreMotorista.text();
	let ImagenFoto = $("#ImagenPersonal").attr("src");
	// Información Empleado.
	var codigo = $('#LblDescripcionCodigo');
	codigo_ = codigo.text();
	var ruta = $('#LblDescripcionRuta');
	ruta_ = ruta.text();
	var unidad = $('#LblDescripcionUnidad');
	unidad_ = unidad.text();
	//	Valores
	var precio = $('#LblListadoPrecio');
	precio_ = precio.text();
	var cantidad = $('#LblListadoCantidad');
	cantidad_ = cantidad.text();
	var total = $('#LblListadoTotalIngreso');
	total_ = total.text();
	//	Tabla.
	var $objCuerpoTabla=$("#listadoAsignacion").children().prev().parent();
	var correlativo_ = [];  var serie_ = []; var cola_ = []; var desde_ = []; var hasta_ = [];
	var ingreso_ = []; var estatus_ = [];
	var fila = 0;
	///////////////////////////////////////////////////////////////////////////////////////////////////
	// recorre el contenido de la tabla.
	///////////////////////////////////////////////////////////////////////////////////////////////////
	$objCuerpoTabla.find("tbody tr").each(function(){
		var correlativo =$(this).find('td').eq(0).html();
		var estatus =$(this).find('td').eq(1).html();
		var serie =$(this).find('td').eq(2).html();
		var cola =$(this).find('td').eq(3).html();
		var desde =$(this).find('td').eq(4).html();
		var hasta =$(this).find('td').eq(5).html();
		var ingreso =$(this).find('td').eq(6).html();
	///////////////////////////////////////////////////////////////////////////////////////////////////
	// VALORES DE LA MATRIZ QUE VIAJAN POR EL POST
	///////////////////////////////////////////////////////////////////////////////////////////////////
		correlativo_[fila] = correlativo.trim();
		estatus_[fila] = estatus.trim();
		serie_[fila] = serie.trim();
		cola_[fila] = cola.trim();
		desde_[fila] = desde.trim();
		hasta_[fila] = hasta.trim();
		ingreso_[fila] = ingreso.trim();
			fila = fila + 1;
	});
	// Limpiar datos
		fecha = $("#FechaProduccion").val();
	// Ejecutar Informe
		varenviar = "/acomtus/php_libs/reportes/Produccion/DetallePorMotorista.php?fecha="+fecha+
					"&correlativo="+correlativo_+"&serie="+serie_+"&cola="+cola_+"&desde="+desde_+"&hasta="+hasta_+"&ingreso="+ingreso_
					+"&estatus="+estatus_+"&NombreMotorista="+NombreMotorista+"&ImagenPersonal="+ImagenFoto
					+"&codigo="+codigo_+"&ruta="+ruta_+"&unidad="+unidad_+"&precio="+precio_+"&cantidad="+cantidad_+"&total="+total_
					;
	// Ejecutar la función abre otra pestaña.
		AbrirVentana(varenviar);


});


});	// final de FUNCTION.
///////////////////////////////////////////////////////////////			
// Inicio del Ajax. Buscar, Actualizar Producción Devolución e Ingreso.
///////////////////////////////////////////////////////////////
function BuscarProduccionPorFecha() {
	// Variables accion para guardar datos.
		var fecha = $("#FechaProduccion").val();
		accion_buscar = "BuscarProduccionPorRuta";
		
	/// BUSCAR SI EXISTE PRODUCCIÓN ENLA FECHA DIGITADA.	
		$.ajax({
			beforeSend: function(){
				$('#listadoOk').empty();
			},
			cache: false,
			type: "POST",
			dataType: "json",
			url:"php_libs/soporte/ReporteGeneral.php",
			data:"accion_buscar=" + accion_buscar + "&fecha=" + fecha,
			success: function(response){
			// VERIFICAR SI HAY DATOS EN LA DATA.
				if(response.respuesta == true){
					// focus
						$("#FechaProduccion").focus().select();
					// vAR DATA
						toastr["success"](response.mensaje, "Sistema");
					// RELLENAR TABLA.
						$('#listadoOk').append(response.contenido);
					//
						$("#ProduccionTabla").show();
						$("#field_produccion_detalle").hide();
						$("#FieldsetTabla").hide();
					// fieldset buscar por motorista
						$("#BuscarPorMotorista").hide();
						$("#BuscarPorUnidadPlaca").hide();
					// limpiar tabla producción detalle.
						$('#listadoDetalleOk').empty();
					// cambiar el valor del ingreso.
						var colones = response.totalProduccionIngreso;
						colones = colones.replace(/,/g,"");
						colones = Number(colones);
						var total_colones = (colones * parseFloat(8.75));
						total_colones = delimitNumbers(total_colones.toFixed(2));
					//	cantidades de controles a y vendidas.
						controles = Number(response.totalProduccion);
						controlesVendidos = Number(response.cantidadProduccionVendidos);
					//	porcentaje // cambiaar css progress bar.
						var porcentaje_controles = ((controlesVendidos * 100) / controles);
						porcentaje_controles = delimitNumbers(porcentaje_controles.toFixed(0));
					//
						$("label[for='LblProduccionesTotal']").text("Controles: " + controles);
							$('.progress-bar').css('width', porcentaje_controles +'%').attr('aria-valuenow', porcentaje_controles);
						$("label[for='LblCantidadProduccionesVendidas']").text("Procesados: " + controlesVendidos + " es el " + porcentaje_controles + "% de " + controles + ".");
					// Etiquetas
						
						$("label[for='LblProduccionesTotalIngreso']").text('Total Ingreso Produccion $ ' + response.totalProduccionIngreso + ' (¢ ' + total_colones +')');
						$("label[for='LblTotalTiquetes']").text('# Tiquetes Vendidos: ' + response.cantidadTiquetePantalla);
						
						
				}
			},	// DATA.
		});
	}	// function BuscarProduccionPorFecha()

///////////////////////////////////////////////////////////////			
// Inicio del Ajax. Buscar, Actualizar Producción Devolución e Ingreso.
///////////////////////////////////////////////////////////////
function BuscarProduccionPorIdTabla() {
	// Variables accion para guardar datos.
		var fecha = $("#FechaProduccion").val();
		codigo_produccion = $("#NumeroCorrelativo").val();
		accion_buscar = "BuscarProduccionPorIdTabla";
	/// BUSCAR SI EXISTE PRODUCCIÓN ENLA FECHA DIGITADA.	
		$.ajax({
			beforeSend: function(){
				$('#listadoDevolucionIngresoOk').empty();
			},
			cache: false,
			type: "POST",
			dataType: "json",
			url:"php_libs/soporte/ReporteGeneral.php",
			data:"accion_buscar=" + accion_buscar + "&fecha=" + fecha + "&codigo_produccion="+codigo_produccion,
			success: function(response){
			// VERIFICAR SI HAY DATOS EN LA DATA.
				if(response.respuesta == true){
					// focus
						$("#FechaProduccion").focus().select();
					// vAR DATA
						toastr["info"](response.mensaje, "Sistema");
					// RELLENAR TABLA.
						$('#listadoDevolucionIngresoOk').append(response.contenido);
					//	
						$("#ProduccionTabla").hide();
						$("#field_produccion_detalle").hide();
						$("#FieldsetTabla").show();
					// fieldset buscar por motorista
						$("#BuscarPorMotorista").hide();
						$("#BuscarPorUnidadPlaca").hide();
					// limpiar tabla producción detalle.
						$('#listadoDetalleOk').empty();
					// cambiar el valor del ingreso.
						$("#LblDescripcionRuta").html(response.descripcionRuta);
						$("#LblDescripcionUnidad").html(response.descripcionUnidad);
						$("#LblDescripcionCodigo").html(response.codigoPersonal);
						$("label[for='LblNombreMotorista']").text(response.nombreMotorista);
						$("#LblListadoIdFecha").html(response.fecha);
						//
						$("#LblListadoPrecio").html("$ " + response.precioPublico);
						$("#LblListadoTotalIngreso").html("$ " + response.totalIngreso);
						$("#LblListadoCantidad").html(response.cantidadTiquete);

						// FOTO DEL EMPLEADO.
						if(response.url_foto == "")
						{
							if(response.codigo_genero == "01"){
								$(".card-img-top").attr("src", "../acomtus/img/avatar_masculino.png");
							}else{
								$(".card-img-top").attr("src", "../acomtus/img/avatar_femenino.png");
							}
						}else{
							$(".card-img-top").attr("src", "../acomtus/img/fotos/" + response.url_foto);	
						}
				}
			},	// DATA.
		});
	}	// function BuscarProduccionPorFecha()
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
////////////////////////////////////////////////////////////
// FUNCION LISTAR TABLA perosnal solo motoristas
////////////////////////////////////////////////////////////
function listar_personal(codigo_personal){
	
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/ProduccionCalcular.php", {accion_buscar: 'BuscarPersonalMotorista'},
        function(data) {
			miselect.empty();
			miselect.append('<option value="00">Seleccionar...</option>');
            for (var i=0; i<data.length; i++) {
				if(codigo_personal == data[i].codigo){
					miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].codigo + ' | ' + data[i].descripcion + '</option>');
                }else{
                    miselect.append('<option value="' + data[i].codigo + '">' + data[i].codigo + ' | ' + data[i].descripcion + '</option>');
                }
            }
    }, "json");    
}
////////////////////////////////////////////////////////////
// LISTAR UNIDAD Y N.º DE PLACA
////////////////////////////////////////////////////////////
function listar_unidad_transporte(codigo_transporte_colectivo){
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/ProduccionCalcular.php", {accion_buscar: 'BuscarTransporteColectivo'},
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
				if(codigo_transporte_colectivo == data[i].codigo){
					miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].numero_equipo + ' | ' + data[i].descripcion + '</option>');
				}else{
					miselect.append('<option value="' + data[i].codigo + '">' + data[i].numero_equipo + ' | ' + data[i].descripcion + '</option>');
				}
            }
    }, "json");    
}
// TODAS LAS TABLAS VAN HA ESTAR EN PRODUCCIONBUSCAR.*******************
// FUNCION LISTAR TABLA catalogo_ruta
////////////////////////////////////////////////////////////
function listar_serie(){
    var miselect=$("#lstSerieBuscarTiquete");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/ProduccionBuscar.php", {accion_buscar: 'BuscarSerie'},
        function(data) {
            miselect.empty();
            miselect.append('<option value="">Seleccionar...</option>');
            for (var i=0; i<data.length; i++) {
                    miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + " - " + data[i].tiquete_color + " - " + data[i].precio_publico + '</option>');
                    //$('#PrecioPublico').val(data[0].precio_publico);
                    //$('#Existencia').val(data[0].existencia);
            }
    }, "json");    
}
// Mensaje de Carga de Ajax.
function configureLoadingScreen(screen){
$(document)
	.ajaxStart(function () {
		screen.fadeIn();
	})
	.ajaxStop(function () {
		screen.fadeOut();
	});
}

function delimitNumbers(str) { return (str + "").replace(/\b(\d+)((\.\d+)*)\b/g, function(a, b, c) { return (b.charAt(0) > 0 && !(c || ".").lastIndexOf(".") ? b.replace(/(\d)(?=(\d{3})+$)/g, "$1,") : b) + c; }); } 

