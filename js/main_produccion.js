// id de user global
var id_ = 0;
var NIE = 0;
var tabla = "";
var accion = "";
var today = "";
var accionAsignacion = "";
var reload = false;
var fecha_year = "";
var fecha_month = "";
//	ARMAR ITEM DE MENU DEPENDIENDO DEL CODIGO DEL USUARIO.
	// GESTION PRODUCCION
	var defaultContentMenu = '<div class="dropdown">'
			+'<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown"><i class="fas fa-wrench"></i></button>'
			+'<div class="dropdown-menu">'
				+'<a class="editar dropdown-item" href="#"><i class="fas fa-edit"></i> Editar</a>'
				+'<a class="devolucion dropdown-item" href="#"><i class="fas fa-undo-alt"> Devolución</i></a>'
				+'<a class="eliminar dropdown-item" href="#"><i class="fas fa-trash"> Eliminar</i></a>'
				+'<a class="agregarTalonario dropdown-item" href="#"><i class="fas fa-edit"> Agregar Talonario</i></a>'
				+'</div></div>';
$(function(){ // iNICIO DEL fUNCTION.
///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
		$(document).ready(function(){
			listar();
            listar_serie();
		///////////////////////////////////////////////////
		// funcionalidad del botón que abre el formulario
		///////////////////////////////////////////////////
	    $("#VentanaGuardarProduccionDevolucion").on('hidden.bs.modal', function () {
            // Limpiar variables Text, y textarea
				$("#formProduccionDevolucion")[0].reset();
				$("label.error").remove();
				// Desabilitar Fecha Creación Proveedor.
					$("#txtFechaCreacion").prop("readonly",true);
		});
		///////////////////////////////////////////////////
		// funcionalidad del botón que abre el formulario
		///////////////////////////////////////////////////
	    $("#VentanaAgregarPorTalonario").on('hidden.bs.modal', function () {
            // Limpiar variables Text, y textarea
				$("#formAgregarTalonario")[0].reset();
				$("label.error").remove();
		});
		///////////////////////////////////////////////////
		// FOCUS en la Ventana Modal Cliente/Empresa
		///////////////////////////////////////////////////
			$('#VentanaGuardarProduccionDevolucion').on('shown.bs.modal', function() {
			  $(this).find('[autofocus]').focus();
			});

			////CUANDO CAMBIA LA SERIE. ////////////////////////////////////////////////////	  
			$('#lstSerieAgregarTiquete').on('change', function () {
				var id_ = $("#lstSerieAgregarTiquete").val();
				
				$.post("php_libs/soporte/ProduccionBuscar.php", {accion_buscar: 'BuscarSerieId', id_: id_},
					function(data) {
						$('#AgregarPrecioSerie').val(data[0].precio_publico);
						$("#lstSerieAgregarTiquete").focus();
				}, "json");    
			});	

		});		
///////////////////////////////////////////////////////////////////////////////
//	FUNCION LISTAR BUSQUEDA DE LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
var listar = function(){
// Escribir la hora actual.
	var hora = new Date();
	var hora_formato_24 = hora.getHours();
	var hora_formato_12 = hora.getHours();
	var minutos = hora.getMinutes();
// Agregar el AM O PM
	var formato_12 = hora_formato_24 > 12 ? " p.m." : " a.m.";
	var hora_actual = hora_formato_12 + ':' + minutos + formato_12;
// Asignar la input Producción:
// Escribir la fecha actual.
	var now = new Date();                
	var day = ("0" + now.getDate()).slice(-2);
	var month = ("0" + (now.getMonth() + 1)).slice(-2);
	var year = now.getFullYear();

	today = now.getFullYear()+"-"+(month)+"-"+(day) ;
//	RELACIONADO CON LA FECHA PARA LA DEVOLCUIÓN.
// Variables.
	$("#FechaProduccionCreacion").val(today);
	$("#FechaProduccionDevolucion").val(today);
// ASIGNAR FECHA ACTUAL A LOS DATE CORRESPONDIENTES.
if(reload == true){
	var buscartodos = "BuscarTodos";
	var fecha_ =  $("#FechaProduccion").val();
	var fecha_partial = fecha_.split("-");
	fecha_year = (fecha_partial[0]) // año
	fecha_month = (fecha_partial[1])	// mes
	console.log(fecha_partial[0])	// dia
	console.log(fecha_partial[1])	// dia
	console.log(fecha_partial[2])	// dia
	reload = false;
}else{
	$('#FechaProduccion').val(today);
		// Varaible de Entornos.php
		var buscartodos = "BuscarTodos";
		fecha_year = year;
		fecha_month = month;
}
		// Tabla que contrendrá los registros.
			tabla = jQuery("#listado").DataTable({
				"lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
				"destroy": true,
				"pageLength": 5,
				"bLengthChange":false,
				"searching":{"regex": true},
				"async": false ,
				"processing": true,
				"ajax":{
					method:"POST",
					url:"php_libs/soporte/ProduccionBuscar.php",
					data: {"accion_buscar": buscartodos, "year": fecha_year, "month": fecha_month}
				},
				"columns":[
					{
						data: null,
						defaultContent: defaultContentMenu,
						orderable: false
					},
                    {"data":"id_"},
                    {"data":"fecha"},
					{"data":"id_jornada"},
                    {"data":"nombre_ruta"},
					{"data":"codigo_estatus",
						render: function(data, type, row){
							if(data == '01'){
								return "<span class='font-weight-bold text-success'>Activo</span>";
							}else{
								return "<span class='font-weight-bold text-danger'>Inactivo</span>";
							}
						}
					},
				],
				// LLama a los diferentes mensajes que están en español.
				"language": idioma_espanol
		});
			obtener_data_editar("#listado tbody", tabla);
			// CONFIGURAR EL FILTER A OTRO OBJETO.
			document.getElementById("listado_filter").style.display="none";
			$('input.global_filter').on( 'keyup click', function () {
				 filterGlobal();
			 } );
			 $('input.column_filter').on( 'keyup click', function () {
				 filterColumn( $(this).parents('tr').attr('data-column') );
			 });
		};
///////////////////////////////////////////////////////////////////////////////
// CONFIGURACIÓN DEL IDIOMA AL ESPAÑOL.
///////////////////////////////////////////////////////////////////////////////	
var idioma_espanol = {
			"sProcessing":     "Procesando...",
			"sLengthMenu":     "Mostrar _MENU_ registros",
			"sZeroRecords":    "No se encontraron resultados",
			"sEmptyTable":     "Ningún dato disponible en esta tabla",
			"sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
			"sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
			"sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
			"sInfoPostFix":    "",
			"sSearch":         "Buscar:",
			"sUrl":            "",
			"sInfoThousands":  ",",
			"sLoadingRecords": "Cargando...",
			"oPaginate": {
			"sFirst":    "Primero",
			"sLast":     "Último",
			"sNext":     "Siguiente",
			"sPrevious": "Anterior"
			},
			"oAria": {
			    "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
			    "sSortDescending": ": Activar para ordenar la columna de manera descendente"
			}
		 };	  

var obtener_data_editar = function(tbody, tabla){
///////////////////////////////////////////////////////////////////////////////
//	FUNCION que al dar clic buscar el registro para posterior mente abri una
// ventana modal. EDITAR REGISTRO
///////////////////////////////////////////////////////////////////////////////
	$(tbody).on("click","a.editar",function(){
		var data = tabla.row($(this).parents("tr")).data();
		//console.log(data); console.log(data[0]);
		//console.log(data['codigo_estatus']);

		///////////////////////////////////////////////////////////////////////
		// VALIDAR SI CODIGO ESTATUS ES IGUAL A '02' - INACTIVO EL CUAL QUIERE DECIR
		// QUE YA PROCESADA EL CONTROL DE TICKET E INGRESO.
		///////////////////////////////////////////////////////////////////////
		if(data['codigo_estatus'] == '00'){
			toastr["info"]("El control de Ticket e Ingreso, Ya Fue Procesado.", "Sistema");
			$("#global_filter").focus().select();
		}else{
			id_ = data[0];
			accion = "EditarRegistro";	// variable global
			window.location.href = 'editar_Nuevo_Produccion.php?id='+id_+"&accion="+accion;
		}
		///////////////////////////////////////////////////////////////////////

	});
	///////////////////////////////////////////////////////////////////////////////
//	FUNCION que al dar clic buscar el registro para posterior mente abri una
// ventana modal. ELIMINAR REGISTRO
///////////////////////////////////////////////////////////////////////////////
$(tbody).on("click","a.eliminar",function(){
	var data = tabla.row($(this).parents("tr")).data();
	console.log(data); console.log(data[0]);
	id_ = data[0];
	fecha = data[1];
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
						cache: false,                     
						type: "POST",                     
						dataType: "json",                     
						url:"php_libs/soporte/ProduccionBuscar.php",                     
						data: {                     
								accion_buscar: 'VerEliminarProduccion', codigo_produccion: id_, fecha: fecha,
								},                     
						success: function(response) {                     
								if (response.respuesta === true) {                     		
									toastr["info"](response.mensaje, "Sistema");
									window.location.href = 'produccion.php';				                  
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
});
///////////////////////////////////////////////////////////////////////////////
//	FUNCION que al dar clic buscar el registro para posterior mente abri una
// ventana modal. EDITAR REGISTRO
///////////////////////////////////////////////////////////////////////////////
$(tbody).on("click","a.agregarTalonario",function(){
	var data = tabla.row($(this).parents("tr")).data();
	id_ = data[0];
	fecha = data[1];
	accion = "AgregarTalonario";	// variable global
	$("#AgregarFechaControl").val(fecha);
	$("#AgregarNumeroControl").val(id_);
	//
	listar_serie_agregar_talonario();
	//
	$('#VentanaAgregarPorTalonario').modal("show");
	$("#lstSerieAgregarTiquete").focus();
	///////////////////////////////////////////////////////////////////////

});
///////////////////////////////////////////////////////////////////////////////
//	FUNCION que al dar clic buscar el registro para posterior mente abri una
// ventana modal. DEVOLUCIONES REGISTRO
///////////////////////////////////////////////////////////////////////////////
	$(tbody).on("click","a.devolucion",function(){
		var data = tabla.row($(this).parents("tr")).data();
		console.log(data); console.log(data[0]);
		id_ = data[0];
		fecha = data[1];
		accion = "DevolucionRegistro";	// variable global
		$("#FechaProduccionCreacion").val(fecha);
		$("#FechaProduccionDevolucion").val(fecha);
		$("#IdProduccionDevolucion").val(id_);
		$("#accion").val(accion);
		// Abrimos el Formulario Modal y Rellenar For.
			$('#VentanaGuardarProduccionDevolucion').modal("show");

					/*
			  // PROCESO PARA ELIMINAR REGISTRO.
			  $.ajax({
				cache: false,
				type: "POST",
				dataType: "json",
				url:"php_libs/soporte/.php",
				data: "id_user=" + id_ + "&accion=" + accion,
				success: function(response){
					// Validar mensaje de error proporcionado por el response. contenido.
					if(response.respuesta == false){
						toastr["error"](response.mensaje, "Sistema");
					}
					else{
						toastr["success"](response.mensaje, "Sistema");
						}               
				},
			});*/
			//////////////////////////////////////
	});
}; // Funcion principal dentro del DataTable.
//	FUNCION que al dar clic buscar el registro para posterior mente abri una
//
// CREAR EL PROCESO, PARA CAMBIAR LA FECHA DE PRODUCCIÓN A UNA NUEVA DEVOLUCIÓN
///////////////////////////////////////////////////////////////////////////////	  
$('#goActualizarTabla').on( 'click', function () {
	// DAR VALOR A LA FECHA.
	listar();
	reload = true;
	//$('#listado').DataTable().ajax.reload();
});
$('#goDevolucionesProduccion').on( 'click', function () {
	// DAR VALOR A LA FECHA.
		$("#FechaProduccionCreacion").val(today);
	// MOSTRA EL MODAL
		$('#VentanaGuardarProduccionDevolucion').modal("show");
});
//
$('#goBuscarCancelar').on( 'click', function () {
	// lIMPIAR LOS VALORES DE LAS TABLAS.                     
		$('#listadoVerControlesDevolucionOk').empty();
	// DAR VALOR A LA FECHA. DESACTIVAR
		$("#FechaProduccionCreacion").prop("disabled",false);
	// MOSTRAR FIELD SET FECHA NUEVA
		$("#FieldsetFechaNueva").hide();
	// Activar botón guardar!
		$("#goGuardarDevolucion").prop("disabled", true);
});	
//
$('#goBuscarProduccionDevolucion').on( 'click', function () {
	fecha = $('#FechaProduccionCreacion').val();
	// ejecutar Ajax.. 
	$.ajax({
		beforeSend: function(){  
			$('#listadoVerControlesDevolucionOk').empty();
		},
	cache: false,                     
	type: "POST",                     
	dataType: "json",                     
	url:"php_libs/soporte/ProduccionBuscar.php",                     
	data: {                     
			accion_buscar: 'BuscarProduccionDevolucion', fecha: fecha,
			},                     
	success: function(response) {                     
			if (response.respuesta === true) {                     
				// lIMPIAR LOS VALORES DE LAS TABLAS.                     
					$('#listadoVerControlesDevolucionOk').append(response.contenido);						                  
				// DAR VALOR A LA FECHA. DESACTIVAR
					$("#FechaProduccionCreacion").prop("disabled",true);
				// MOSTRAR FIELD SET FECHA NUEVA
					$("#FieldsetFechaNueva").show();
				// Activar botón guardar!
					$("#goGuardarDevolucion").prop("disabled", false);
			}                
	}                     
	});
});
///////////////////////////////////////////////////////////////////////////////
// CUANDO CAMBIA LA FECHA. BUSCAR LA PRODUCCIÓN EN LA TABLA
/// EVENTOS JQUERY IMPRIMIR TODA LA PRODUCCIÓN O POR RANGO.
///////////////////////////////////////////////////////////////////////////////	  
$("#NumeroCorrelativoDevolucion").on('keyup', function (e) {
	var keycode = e.keyCode || e.which;
      if (keycode == 13) {
		// Limpiar datos
			fecha_produccion = $('#FechaProduccionCreacion').val();
			fecha_nueva = $("#FechaProduccionDevolucion").val(); 
		//
      }
  });
// IMPRIMIR PRODUCCIÓN
///////////////////////////////////////////////////////////////////////////////	  
$('#goVerImprimirControles').on( 'click', function () {
	window.location.href = 'ProduccionImprimir.php';
});
///////////////////////////////////////////////////////////////////////////////
//	FUNCION que al dar clic buscar el registro para posterior mente abri una
// NUEVA PRODUCCION
///////////////////////////////////////////////////////////////////////////////	  
$('#goNuevoControl').on( 'click', function () {
		accion = "AgregarNuevoTemp";	// variable global
		id_ = 0;
			window.location.href = 'editar_Nuevo_Produccion.php?id='+id_+"&accion="+accion;
});	  
//	FUNCION que al dar clic buscar el registro para posterior mente abri una
// IMPRIMIR PRODUCCIÓN
///////////////////////////////////////////////////////////////////////////////	  
//////////////////////////////////////////////////////////////////////////////////
/* VER #CONTROLES CREADOS */
//////////////////////////////////////////////////////////////////////////////////
$('#goVerProduccion').on('click', function(){
	fecha = $('#FechaProduccion').val();
	// ejecutar Ajax.. ACTUALIZA5 INDICADORES DE MATRICULA.
	$.ajax({
		beforeSend: function(){  
			$('#listadoVerControlesOk').empty();
		},
	cache: false,                     
	type: "POST",                     
	dataType: "json",                     
	url:"php_libs/soporte/ProduccionBuscar.php",                     
	data: {                     
			accion_buscar: 'VerUltimasProducciones', fecha: fecha,
			},                     
	success: function(response) {                     
			if (response.respuesta === true) {                     
				// lIMPIAR LOS VALORES DE LAS TABLAS.                     
				$('#listadoVerControlesOk').append(response.contenido);						                  
					// Abrimos el Formulario Modal y Rellenar For.
						$('#VentanaVerProduccion').modal("show");
			}                
	}                     
	});
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
/// EVENTOS JQUERY Y BOTON NUEVO REGISTRO. CALCULO Y OTROS
$("#NumeroCorrelativo").on('keyup', function (e) {
	var keycode = e.keyCode || e.which;
      if (keycode == 13) {
		// Limpiar datos
		codigo_produccion = $('#NumeroCorrelativo').val();
		fecha = $("#FechaProduccion").val();
		imprimir = "todos";
		// Ejecutar Informe
			varenviar = "/acomtus/php_libs/reportes/control_tiquete_ingresos_todos.php?codigo_produccion="+codigo_produccion+"&fecha="+fecha+"&imprimir="+imprimir;
		// Ejecutar la función abre otra pestaña.
			AbrirVentana(varenviar);   
      }
  });
///////////////////////////////////////////////////////////////////////////////
//	FUNCION que al dar clic buscar el registro para posterior mente abri una
// NUEVA PRODUCCION
///////////////////////////////////////////////////////////////////////////////	  
$('#goGuardarDevolucion').on( 'click', function () {
	CambiarFechaProduccion();
});	  
//////////////////////////////////////////////////////
// Validar Formulario, para posteriormente Guardar o Modificarlo.
 //////////////////////////////////////////////////////
 $('#formProduccionDevolucion').validate({
	ignore:"",
	rules:{
			FechaProduccionCreacion: {required: true},
			FechaProduccionDevolucion: {required: true},
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
		var str = $('#formProduccionDevolucion').serialize();
		//alert(str);
		///////////////////////////////////////////////////////////////			
		// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
		///////////////////////////////////////////////////////////////
		// vARIABLES QUE CONTROLA LO QUE SE PUEDE ACTUALIZAR Y BORRAR.
	
	   // Información de la Página 1.                               
		  var $objCuerpoTabla=$("#listadoVerControlesDevoluciones").children().prev().parent();          
			var calcular_chk_ = []; var calcular_val_ = [];
					
		  var fila = 0;          
	   // recorre el contenido de la tabla.
		  $objCuerpoTabla.find("tbody tr").each(function(){
			// input text
				var calcular_val =$(this).find('td').eq(1).find("input[name=CalcularA]").val();
				 var calcular_chk =$(this).find('td').eq(0).find('input[type="checkbox"]').is(':checked');
			// Color de filas.                                
				 //$(this).css("background-color", "#ECF8E0");                       
			// dar valor a las arrays.
				 calcular_val_[fila] = calcular_val;           		 
				 calcular_chk_[fila] = calcular_chk;               
	
				fila = fila + 1;            
		  });
		// ejecutar Ajax.. ACTUALIZA5 INDICADORES DE MATRICULA.
		  $.ajax({
				beforeSend: function(){  
					$('#listadoVerControlesDevolucionOk').empty();
				},
			   cache: false,                     
			   type: "POST",                     
			   dataType: "json",                     
			   url:"php_libs/soporte/ProduccionBuscar.php",                     
			   data: {                     
					accion_buscar: accionAsignacion,
					fila: fila, calcular_val: calcular_val_, calcular_chk: calcular_chk_, str: str
					},                     
			   success: function(response) {                     
					if (response.respuesta === true) {                     
						// lIMPIAR LOS VALORES DE LAS TABLAS.                     
						$('#listadoVerControlesDevolucionOk').append(response.contenido);						                  
					}                
			   }                     
		  });


			/*$.ajax({
				cache: false,
				type: "POST",
				dataType: "json",
				url:"php_libs/soporte/ProduccionBuscar.php",
				data:str + "&id=" + Math.random(),
				success: function(response){
					// Validar mensaje de error
					if(response.respuesta == true){
						toastr["success"](response.mensaje, "Sistema");
						$("#FechaProduccionCreacion").focus().select();
					}
					if(response.respuesta == false){
						toastr["error"](response.mensaje, "Sistema");
						$("#FechaProduccionCreacion").focus().select();
					}
				},
			});*/
		},
});



///////////////////////////////////////////////////////////////////////////////
//	FUNCION que al dar clic buscar el registro para posterior mente abri una
// NUEVA PRODUCCION
///////////////////////////////////////////////////////////////////////////////	  
$('#goGuardar').on( 'click', function () {
	// acción
		accionAsignacion = "AgregarTalonarioEnControl";
		CalcularDesdeHastaAsignadoAgregar();
		$('#formAgregarTalonario').submit();
});	  
//////////////////////////////////////////////////////
// Validar Formulario, para posteriormente Guardar o Modificarlo.
 //////////////////////////////////////////////////////
 $('#formAgregarTalonario').validate({
	ignore:"",
	rules:{
			AgregarTiqueteDesde: {required: true},
			AgregarTiqueteHasta: {required: true},
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
		var str = $('#formAgregarTalonario').serialize();
		//alert(str);
		///////////////////////////////////////////////////////////////			
		// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
		///////////////////////////////////////////////////////////////

		// ejecutar Ajax.. ACTUALIZA5 INDICADORES DE MATRICULA.
		  $.ajax({
			   cache: false,                     
			   type: "POST",                     
			   dataType: "json",                     
			   url:"php_libs/soporte/ProduccionBuscar.php",                     
			   data: str + "&accion_buscar=" + accionAsignacion + "&id=" + Math.random(),                     
			   success: function(response) {                     
					if (response.respuesta === true) {                     
						// lIMPIAR LOS VALORES DE LAS TABLAS.      
						toastr["info"](response.mensaje, "Sistema");	
						$("#AgregarTiqueteDesde").val("");
						$("#AgregarTiqueteDesde").focus();			                                 
					}                
			   }                     
		  });
		},
});




///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////
$('#AgregarTiqueteDesde').on('focusout',function(){
	CalcularIncremento(this.value);
});



///////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////


});	// final de FUNCTION.

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

///////////////////////////////////////////////////////////////////////////////
//	FUNCION PARA CALCULAR ANTES DE GUARDAR
///////////////////////////////////////////////////////////////////////////////
function CambiarFechaProduccion() {
	fecha_produccion = $("#FechaProduccionCreacion").val();
	fecha_nueva = $("#FechaProduccionDevolucion").val();
	accionAsignacion = "DevolucionRegistro";
	// vARIABLES QUE CONTROLA LO QUE SE PUEDE ACTUALIZAR Y BORRAR.

   // Información de la Página 1.                               
      var $objCuerpoTabla=$("#listadoVerControlesDevoluciones").children().prev().parent();          
		var calcular_chk_ = []; var calcular_val_ = [];
                
      var fila = 0;          
   // recorre el contenido de la tabla.
      $objCuerpoTabla.find("tbody tr").each(function(){
		// input text
			var calcular_val =$(this).find('td').eq(1).find("input[name=CalcularA]").val();
		 	var calcular_chk =$(this).find('td').eq(0).find('input[type="checkbox"]').is(':checked');
		// Color de filas.                                
        	 //$(this).css("background-color", "#ECF8E0");                       
		// dar valor a las arrays.
             calcular_val_[fila] = calcular_val;           		 
             calcular_chk_[fila] = calcular_chk;               

            fila = fila + 1;            
      });
	// ejecutar Ajax.. ACTUALIZA5 INDICADORES DE MATRICULA.
      $.ajax({
			beforeSend: function(){  
				$('#listadoVerControlesDevolucionOk').empty();
			},
           cache: false,                     
           type: "POST",                     
           dataType: "json",                     
           url:"php_libs/soporte/ProduccionBuscar.php",                     
           data: {                     
				accion_buscar: accionAsignacion, FechaProduccionCreacion: fecha_produccion, FechaProduccionDevolucion: fecha_nueva,
				fila: fila, calcular_val: calcular_val_, calcular_chk: calcular_chk_,
                },                     
           success: function(response) {                     
				if (response.respuesta === true) {                     
					// lIMPIAR LOS VALORES DE LAS TABLAS.                     
					$('#listadoVerControlesDevolucionOk').append(response.contenido);		
					toastr["success"](response.mensaje, "Sistema");				                  
				}                
           }                     
      });
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
// TODAS LAS TABLAS VAN HA ESTAR EN PRODUCCIONBUSCAR.*******************
// FUNCION LISTAR TABLA catalogo_serie
////////////////////////////////////////////////////////////
function listar_serie_agregar_talonario(){
    var miselect=$("#lstSerieAgregarTiquete");
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
// Calcular desde - hasta con precio.
function CalcularDesdeHastaAsignadoAgregar(valor) {
    var desde = ($("#AgregarTiqueteDesde").val());
    var hasta = ($("#AgregarTiqueteHasta").val());
    var precio_publico = $("#AgregarPrecioSerie").val();
    desde = desde.replace(/,/g,"");
    hasta = hasta.replace(/,/g,"");
    precio_publico = precio_publico.substring(1);
    var cantidad = 0;
    var i = 0;
    
    for (i = Number(desde); i <= Number(hasta); i++) {
      cantidad++;
    }
        var valor_estimado = (cantidad) * precio_publico
        $('#AgregarCantidadTiquete').val(cantidad);
        $('#AgregarValorTalonario').val(valor_estimado);
}

// CALCULAR INCREMENTO.
function CalcularIncremento(valor) {
    var constante99 = 99;
    var constante100 = 100;
    var hasta = 0;
    var desde = ($("#AgregarTiqueteDesde").val());
    // quitar comas
    desde = desde.replace(/,/g,"");
    // obtener los últimos dos valores
    lastDesde = desde.substr(desde.length - 2);
    //alert(lastDesde);
    // pasar la cantidad calculada a hasta
    if(lastDesde == '01'){
        hasta = Number(desde) + Number(constante99);
    }else if(lastDesde == "1"){
        hasta = Number(desde) + Number(constante99);
    }else if(lastDesde == "00"){
        hasta = Number(desde);
    }else{
        constante99 = constante100 - lastDesde;
        hasta = Number(desde) + Number(constante99);
    }
    // pasar la cantidad calculada a hasta
    $("#AgregarTiqueteHasta").val(hasta); 
    // sumar 1, y cuando guarde pasarlo a desde.
    GlobalDesde = hasta + 1;

}
// Pasar foco cuando seleccionar un encargado.
function PasarFoco()
   {
       $('#lstannlectivo').focus();
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