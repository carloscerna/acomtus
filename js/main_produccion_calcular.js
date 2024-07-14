// id de user global
var id_ = 0;
var IdProduccion = 0;
var accion_buscar = "";
var accionAsignacion = "";
var tabla = "";
var CalculoFinal = "";
var GlobalDesde = 0; 
var GlobalHasta = 0;
$(function(){ // iNICIO DEL fUNCTION.
	// Escribir la fecha actual.
    var now = new Date();                
    var day = ("0" + now.getDate()).slice(-2);
    var month = ("0" + (now.getMonth() + 1)).slice(-2);
    var year = now.getFullYear();

    var today = now.getFullYear()+"-"+(month)+"-"+(day) ;
// ASIGNAR FECHA ACTUAL A LOS DATE CORRESPONDIENTES.
    $('#FechaBuscarProduccion').val(today);
///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
$(document).ready(function(){
	///////////////////////////////////////////////////////////////////////////////
	// CUANDO CAMBIA LA FECHA. BUSCAR LA PRODUCCIÓN EN LA TABLA
	// PRODUCCION ASIGNNADO.
	///////////////////////////////////////////////////////////////////////////////	  
	//$('#lstPersonal').on('change', function () {
	//	BuscarProduccionPorId();
	//});
	// FECHA FOCUS-3
	$('#FechaBuscarProduccion').focus();
						
	//
	// configurar el Select2
	$('#lstPersonal').select2({
		theme: "bootstrap4"
	});

	// configurar el Select2
	$('#lstBusNumero').select2({
		theme: "bootstrap4"
	});
	//
	listar_personal();
	listar_unidad_transporte();
	listar_ruta();
});
/////////////////////////////////////////////////////////////////////////////




///////////////////////////////////////////////////////////////////////////////
/// EVENTOS JQUERY Y BOTON NUEVO REGISTRO. CALCULO Y OTROS
///////////////////////////////////////////////////////////////////////////////
$("#DesdeAsignadoPartial02").on('keyup', function (e) {
    var keycode = e.keyCode || e.which;
	// Limipiar variables inputl text.
	this.value = (this.value + '').replace(/[^0-9]/g, '');
	console.log(this.value);

	if (keycode == 13) {
		var partial =  $("#DesdeAsignadoPartial02").val();
		var desde_completo = $("#DesdeAsignadoPartial01").val() + $("#DesdeAsignadoPartial02").val();
		var hasta_asignado = $("#HastaTablaAsignado").val();

		console.log(desde_completo);
		console.log(partial);
		
		if(partial == '00'){
			$("#DesdeAsignado").val(hasta_asignado);
			console.log(hasta_asignado);
			ActualizarTalonario();
		//}
		//else if(partial == '01'){
		//			$("#DesdeAsignadoPartial02").focus().select();
			//console.log("Incorrecto!");
		}else if(partial.length > 2 || partial.length == 1){
			$("#DesdeAsignadoPartial02").focus().select();
			//console.log("Incorrecto!");
		}else if(partial < 1){
			$("#DesdeAsignadoPartial02").focus().select();
			//console.log("Incorrecto!");
		}
		else{
			$("#DesdeAsignadoPartial02").focus().select();	
			//console.log("Correcto!");
			//
			$("#DesdeAsignado").val(desde_completo);
			//
			//
			var desde = ($("#DesdeAsignado").val());
			var hasta = ($("#HastaAsignado").val());
			desde = desde.replace(/,/g,"");
			hasta = hasta.replace(/,/g,"");
			//console.log("GlobalDesde: " + GlobalDesde + " GlobalHasta: " + GlobalHasta + " Desde: " + desde + " Hasta: " + hasta);
			  // Validar Rango desde  - Hasta.
			  if(Number(desde) < GlobalDesde || Number(desde) > GlobalHasta){
				toastr["error"]("Talonario Fuera de Rango", "Sistema");
			  }//else if(Number(desde) == GlobalDesde || Number(desde) == GlobalHasta){
				//toastr["error"]("Talonario es Igual", "Sistema");		  }
				else{
				ActualizarTalonario();
			  }	// if comparar rango de desde y hasta
		}
      }	// Tecla enter al presionar.
});

$("#DesdeAsignado").on('keyup', function (e) {
    var keycode = e.keyCode || e.which;
      if (keycode == 13) {
		var desde = ($("#DesdeAsignado").val());
		var hasta = ($("#HastaAsignado").val());
		desde = desde.replace(/,/g,"");
		hasta = hasta.replace(/,/g,"");
		//alert("GlobalDesde: " + GlobalDesde + "GlobalHasta: " + GlobalHasta + "Desde: " + desde + "Hasta: " + hasta);
		  // Validar Rango desde  - Hasta.
		  if(Number(desde) < GlobalDesde || Number(desde) > GlobalHasta){
			toastr["error"]("Talonario Fuera de Rango", "Sistema");
		  }//else if(Number(desde) == GlobalDesde || Number(desde) == GlobalHasta){
			//toastr["error"]("Talonario es Igual", "Sistema");		  }
			else{
			ActualizarTalonario();
		  }	// if comparar rango de desde y hasta
      }	// Tecla enter al presionar.
});
$("#HastaAsignado").blur(function(){
    $(this).css("background-color", "#FFFFCC");
    //this.focus();
});		
///////////////////////////////////////////////////////////////////////////////
//	FUNCION LISTAR BUSQUEDA DE LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
var listar = function(){
		// Varaible de Entornos.php
			var buscartodos = "BuscarTodos";
		// Tabla que contrendrá los registros.
			tabla = jQuery("#listado").DataTable({
				"lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
				"destroy": true,
				"pageLength": 10,
				"bLengthChange":false,
				"searching":{"regex": true},
				"async": false ,
				"processing": true,
				"ajax":{
					method:"POST",
					url:"php_libs/soporte/ProduccionCalcular.php",
					data: {"accion_buscar": buscartodos}
				},
				"columns":[
					{
						data: null,
						defaultContent: '<div class="dropdown"><button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown"><i class="fas fa-wrench"></i></button><div class="dropdown-menu"><a class="editar dropdown-item" href="#"><i class="fas fa-edit"></i> Editar</a><a class="eliminar dropdown-item" href="#"><i class="fas fa-trash-alt"> Eliminar</i></a></div></div>',
						orderable: false
					},
                    {"data":"id_"},
                    {"data":"fecha"},
					{"data":"hora"},
					{"data":"codigo"},
					{"data":"nombre_personal"},
					{"data":"nombre_jornada"},
					{"data":"nombre_ruta"},
					{"data":"numero_placa"}
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
		
		id_ = data[0];
		accion = "EditarRegistro";	// variable global
			window.location.href = '.php?id='+id_+"&accion="+accion;
	});
///////////////////////////////////////////////////////////////////////////////
//	FUNCION que al dar clic buscar el registro para posterior mente abri una
// ventana modal. ELIMINAR REGISTRO
///////////////////////////////////////////////////////////////////////////////
	$(tbody).on("click","a.eliminar",function(){
		var data = tabla.row($(this).parents("tr")).data();
		//console.log(data); console.log(data[0]);
		
		id_ = data[0];
		nombre = data[1];
		accion = "EliminarRegistro";	// variable global
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
			  $.ajax({
				cache: false,
				type: "POST",
				dataType: "json",
				url:"php_libs/soporte/.php",
				data: "id_user=" + id_ + "&accion=" + accion + "&nombre=" + nombre,
				success: function(response){
					// Validar mensaje de error proporcionado por el response. contenido.
					if(response.respuesta == false){
						toastr["error"](response.mensaje, "Sistema");
					}
					else{
						toastr["success"](response.mensaje, "Sistema");
							window.location.href = 'usuarios.php';
						}               
				},
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
}; // Funcion principal dentro del DataTable.

///////////////////////////////////////////////////////////////////////////////
//	FUNCION que al dar clic buscar el registro para posterior mente abri una
// ventana modal. GENERAR NUEVO ESTUDIANTE
///////////////////////////////////////////////////////////////////////////////	  
///////////////////////////////////////////////////////////////////////////////
// CUANDO CAMBIA LA FECHA. BUSCAR LA PRODUCCIÓN EN LA TABLA
// PRODUCCION ASIGNNADO.
///////////////////////////////////////////////////////////////////////////////	  
$('#goBuscarProduccion').on( 'click', function () {
	BuscarProduccionPorFecha();
	//$("#goCalcular").prop("disabled", true);
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
			url:"php_libs/soporte/Produccion/ProduccionBuscar.php",                     
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
				url:"php_libs/soporte/Produccion/ProduccionBuscar.php",                     
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
						url:"php_libs/soporte/Produccion/ProduccionBuscar.php",                     
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
// ACTULIAR EN LA TABLA PRODUCCION
// PRODUCCION ASIGNNADO.
///////////////////////////////////////////////////////////////////////////////	  
$('#goActualizarTalonario').on( 'click', function () {
	ActualizarTalonario();
	$("#goActualizarTalonario").prop("disabled", true);
});
//	FUNCION que al dar clic buscar el registro para posterior mente abri una
// IMPRIMIR PRODUCCIÓN CALCULAR VER PRIMERO EL TOTAL DE TODAS LAS PRODUCCIONES
///////////////////////////////////////////////////////////////////////////////	  
$('#goImprimir').on( 'click', function () {
	window.location.href = 'ProduccionCalcularImprimir.php';
});
///////////////////////////////////////////////////////////////////////////////	  
// BLOQUE EXTRAER INFORMACIÓN DEL REGISTROS (PRODUCCION ASIGNADO)
$('body').on('click','#listadoAsignacion a',function (e){
	e.preventDefault();
// DATA-ACCION Y HREF
	Id_Editar_Eliminar = $(this).attr('href');
	accionAsignacion = $(this).attr('data-accion');
	//alert(Id_Editar_Eliminar+" "+accionAsignacion);
// EDTIAR REGISTRO.
	if(accionAsignacion  == 'EditarAsignacion'){
		// separar cadena
		//console.log(Id_Editar_Eliminar);
		var partial = Id_Editar_Eliminar.split("#");
		// DAR LOS VALORES A LOS RESPECTIVOS OBJETOS
		$("#CodigoProduccionAsignacion").val(partial[0]);
		$("#CodigoProduccion").val(partial[1]);
		$("#DesdeAsignado").val(partial[2]);
		// pasar valores AL PARTIAL.
			var partial_desde = partial[2];
			var partial_1 = partial_desde.substr(partial_desde.length-2);
			var partial_2 = partial_desde.length - 2;	// le quito siempre dos al numero total de caracteres.
			var partial_3 = partial_desde.substr(0, partial_2);
		// convertir a number.
			partial_1 = Number(partial_1);
			partial_2 = Number(partial_2);
			partial_3 = Number(partial_3);
		//
			$("#DesdeAsignadoPartial01").val(partial_3);	// los primero digitos del 0 al length - 2.
			$("#DesdeAsignadoPartial02").val(partial_1);	// Los últimos dos digitos.
		//
			//console.log(partial_1 + " : " + partial_2);
			//console.log(partial.substr(0,partial_2));
			//console.log(partial.substring());
		//
		$("#DesdeTablaAsignado").val(partial[2]);

		$("#HastaAsignado").val(partial[3]);
		$("#HastaTablaAsignado").val(partial[3]);
		//$("#").val(partial[4]);
		$("#PrecioPublico").val(partial[5]); // Precio Público
		$("#CantidadTiqueteAsignado").val(partial[6]); // Cantidad Tiquete
		//$("#").val(partial[7]);
		toastr["success"]("Editar Talonario.", "Sistema");
		
		$("#goActualizarTalonario").prop("disabled", false);
		// Definir Rango para Editar sólamente el rango seleccionado.
		 GlobalDesde = partial[2];
		 GlobalHasta = partial[3];
		// Pasar foco.
		$("#DesdeAsignadoPartial02").focus().select();
	}
});

// funcionalidad del botón SOLOCALCULO EN EL CUAL NO SE ELIMINAN LOS DATOS DEL CORRELATIVO.
$('#goCalcular').on('click',function(){
	accionAsignacion = "CalcularListadoTabla";
	CalculoFinal = "SoloCalculo";
	PreCalcular();
});

// funcionalidad del botón FINALIZAR CALCULO QUE SIGNIFCA BORRAR DE LA TABLA CORRELATIVO Y ACTUALIZAR INVENTARIO
$('#goFinalizar').on('click',function(){
	accionAsignacion = "CalcularListadoTabla";
	CalculoFinal = "FinCalculo";
	//
	//$("#goCalcular").prop("disabled", true);
	//
	PreCalcular();
});

/// EVENTOS JQUERY Y BOTON NUEVO REGISTRO. CALCULO Y OTROS
$("#IdProduccion").on('keyup', function (e) {
    var keycode = e.keyCode || e.which;
      // CALCULOS -tecla enter
      if (keycode == 13) {
          BuscarProduccionPorFecha();
      }
  });

});	// final de FUNCTION.
///////////////////////////////////////////////////////////////////////////////	  
///////////////////////////////////////////////////////////////////////////////	  

///////////////////////////////////////////////////////////////			
// Inicio del Ajax. Buscar, Actualizar Producción Devolución e Ingreso.
///////////////////////////////////////////////////////////////
function BuscarProduccionPorFecha() {
// Variables accion para guardar datos.
	var fecha = $("#FechaBuscarProduccion").val();
	var IdProduccion = $("#IdProduccion").val();
	accion_buscar = "BuscarProduccionPorFecha";
/// BUSCAR SI EXISTE PRODUCCIÓN ENLA FECHA DIGITADA.	
	$.ajax({
		beforeSend: function(){
			$('#listadoDevolucionIngresoOk').empty();
		},
		cache: false,
		type: "POST",
		dataType: "json",
		url:"php_libs/soporte/ProduccionCalcular.php",
		data:"accion_buscar=" + accion_buscar + "&fecha=" + fecha + "&IdProduccion="+IdProduccion,
		success: function(data){
		// VERIFICAR SI HAY DATOS EN LA DATA.
			if(data[0].respuesta == false){
				// listar personal motorista.
					listar_personal();
					listar_unidad_transporte();
				// Visibles Fieldset
					$("#NoProduccion").show();
				// Ocultar fieldset.
					$("#FieldInformacion").hide();
					$("#FieldsetTabla").hide();
					$("#FieldsetDevolucion").hide();
				// focus
					$("#IdProduccion").focus().select();
				// vAR DATA
					toastr["error"](data[0].mensaje, "Sistema");
			}
			if(data[0].respuesta == true){
				
				// listar personal motorista.
					//listar_personal();
					//listar_unidad_transporte();
					
				// Ruta y Jornada
					$("#Jornada").html(data[0].jornada);
					$("#Ruta").html(data[0].ruta);
				//
					//$("#lstRuta").val(data[0].codigo_ruta);
				//
					$("#PrecioPublico").html("$ " + data[0].precio_publico);
				// verficar el codigo_estatus de la producción.
				//if(data[0].codigo_estatus == '01'){	// Activo
					$("#CodigoInventarioTiquete").val(data[0].codigo_inventario_tiquete);
					$("#TotalVueltas").val(data[0].TotalVueltas);
				//
				if(data[0].mensaje == "Producción Encontrada"){
					var codigo_personal = data[0].codigo_personal;
					var codigo_unidad = data[0].codigo_transporte_colectivo;
					// Set the value, creating a new option if necessary. EL CUAL HA SIDO CREADA CON SELECT2
					//console.log($('#lstPersonal').find("option[value='" + codigo_personal + "']").length);
					if ($('#lstPersonal').find("option[value='" + codigo_personal + "']").length) {
						console.log($('#lstPersonal').find("option[value='" + codigo_personal + "']").length);
						$('#lstPersonal').val(codigo_personal).trigger('change');
					}
					if ($('#lstBusNumero').find("option[value='" + codigo_unidad + "']").length) {
						console.log($('#lstBusNumero').find("option[value='" + codigo_unidad + "']").length);
						$('#lstBusNumero').val(codigo_unidad).trigger('change');
					}
					//
					//$("#lstPersonal").prop("disabled", true);
					//$("#lstBusNumero").prop("disabled", true);
					//
					// activar botón calcular.
					//$("#goCalcular").prop("disabled", true);
					//$("#goFinalizar").prop("disabled", true);
					// Ocultar fieldset NoProduccion.
					$("#NoProduccion").hide();
					// Ocultar fieldset FieldInformacion.
					$("#FieldInformacion").show();
					$("#FieldsetTabla").show();
					$("#FieldsetDevolucion").show();
					// focus();
					$("#lstPersonal").focus().select();
					//
					listar_tabla();	// revisar la tabla .
					listar_ruta(data[0].codigo_ruta)
				}/*else{
				}*/
					// listar tab
					//
					// Ocultar fieldset.
				//}
			/*	if(data[0].codigo_estatus == '02'){	// Inactivo
				// activar botón calcular.
					$("#goCalcular").prop("disabled", true);
				// Desactivar select y dar el valor.
					$("#lstPersonal").prop("readonly", true);
					$("#lstBusNumero").prop("readonly", true);
				// Desactivar select y dar el valor.
					listar_personal(data[0].codigo_personal);
					listar_unidad_transporte(data[0].codigo_transporte_colectivo);
				// 
					$("#lstPersonal").prop("disabled", true);
					$("#lstBusNumero").prop("disabled", true);
				// Ocultar fieldset FieldInformacion.
					$("#FieldInformacion").show();
				// lsitar tabla
					listar_tabla();
				}*/

			}	// IF DE LA RESPUESTA DE BUSQUEDA
		},	// DATA.
	});
}	// function BuscarProduccionPorFecha()
///////////////////////////////////////////////////////////////			
// Inicio del Ajax. Buscar, Actualizar Producción Devolución e Ingreso.
///////////////////////////////////////////////////////////////
function ActualizarTalonario() {
	// Variables accion para guardar datos.
		var DesdeCola = $("#DesdeAsignado").val();
		var IdProduccionAsignado = $("#CodigoProduccionAsignacion").val();
		var IdProduccion = $("#CodigoProduccion").val();
		var DesdeTabla = $("#DesdeTablaAsignado").val();
		var PrecioPublico = $("#PrecioPublico").val();
		accion_buscar = "ActualizarTalonario";
	/// BUSCAR SI EXISTE PRODUCCIÓN ENLA FECHA DIGITADA.	
		$.ajax({
			beforeSend: function(){
				$('#listadoDevolucionIngresoOk').empty();
			},
			cache: false,
			type: "POST",
			dataType: "json",
			url:"php_libs/soporte/ProduccionCalcular.php",
			data:"accion_buscar=" + accion_buscar + "&DesdeTabla=" + DesdeTabla + "&DesdeCola=" + DesdeCola + "&IdProduccionAsignado=" + IdProduccionAsignado + "&IdProduccion=" + IdProduccion
			+ "&PrecioPublico=" +  PrecioPublico,
			
			success: function(response){
			// VERIFICAR SI HAY DATOS EN LA DATA.
				if(response.respuesta == false){
					toastr["error"](response.mensaje, "Sistema");
					$('#listadoDevolucionIngresoOk').append(response.contenido);
					// focus
						$("#IdProduccion").focus().select();
					// vAR DATA
						toastr["error"](data[0].mensaje, "Sistema");
				}
				if(response.respuesta == true){
					toastr["success"](response.mensaje, "Sistema");
					$('#listadoDevolucionIngresoOk').append(response.contenido);
				}	// IF DE LA RESPUESTA DE ACTULIZACIÓN TALONARIO.
			},	// DATA.
		});
	}	// function BuscarProduccionPorFecha()
////////////////////////////////////////////////////////////
// FUNCION LISTAR TABLA perosnal solo motoristas
////////////////////////////////////////////////////////////
function listar_personal(codigo_personal){
	var miselect=$("#lstPersonal");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/ProduccionCalcular.php", {accion_buscar: 'BuscarPersonalMotorista'},
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
				if(codigo_personal == data[i].codigo){
					miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].codigo + ' | ' + data[i].descripcion + '</option>');
                }else{
                    miselect.append('<option value="' + data[i].codigo + '">' + data[i].codigo + ' | ' + data[i].descripcion + '</option>');
                }
            }
    }, "json");    
}

// FUNCION LISTAR TABLA perosnal solo motoristas
////////////////////////////////////////////////////////////
function listar_unidad_transporte(codigo_transporte_colectivo){
    var miselect=$("#lstBusNumero");
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


function listar_tabla(){
	// Rellenar Tabla | Tiquete Asignados..
	var fecha = $("#FechaBuscarProduccion").val();
	var IdProduccion = $("#IdProduccion").val();
	accion_buscar = "BuscarProduccionPorIdTabla";
	$.ajax({
		beforeSend: function(){
			$('#listadoDevolucionIngresoOk').empty();
		},
		cache: false,
		type: "POST",
		dataType: "json",
		url:"php_libs/soporte/ProduccionCalcular.php",
		data:"accion_buscar=" + accion_buscar + "&IdProduccion=" + IdProduccion,
		success: function(response){
			// Validar mensaje de error
			if(response.respuesta == false){
				toastr["error"](response.mensaje, "Sistema");
				$('#listadoDevolucionIngresoOk').append(response.contenido);
				// cambiar el valor del ingreso.
				$("label[for='LblIngreso']").text('Efectivo a Entregar $ ' + response.totalIngreso);
				$("#FieldsetDevolucion").hide();
				$("#FieldsetTabla").show();
				// Ocultar botón Calcular
				//$("#goCalcular").prop("disabled", true);
			}
			if(response.respuesta == true){
				toastr["success"](response.mensaje, "Sistema");
					$('#listadoDevolucionIngresoOk').append(response.contenido);
				// cambiar el valor del ingreso.
					if(response.mensaje == 'Efectivo a Entregar:'){
						// Visible Fieldset
					/*	$("#NoProduccion").hide();
						$("#FieldInformacion").show();
						$("#FieldsetTabla").show();
						$("#FieldsetDevolucion").show();
						// 
						$("#lstPersonal").prop("disabled", true);
						$("#lstBusNumero").prop("disabled", true);
						$("#goCalcular").prop("disabled", true); 
						$("#goFinalizar").prop("disabled", true);*/	
						//
						//
						$("label[for='LblIngreso']").text(response.mensaje + ' ' + response.totalIngreso);
						//
						$("#lstPersonal").focus().select();
					}else{
						$("label[for='LblIngreso']").text('Total Entregado $ ' + response.totalIngreso);
					}
					
				// Cálculo Realizado
				if(response.mensaje == "Cálculo realizado"){
				// Visible Fieldset
					$("#NoProduccion").hide();
					$("#FieldsetTabla").show();
					$("#FieldsetDevolucion").hide();
				// focus
					$("#IdProduccion").focus().select();
				}
				// Producción Encontrada
				if(response.mensaje == "Producción Encontrada"){
				// Visible Fieldset
					$("#NoProduccion").hide();
					$("#FieldInformacion").show();
					$("#FieldsetTabla").show();
					$("#FieldsetDevolucion").show();
				// 
					$("#lstPersonal").prop("disabled", false);
					$("#lstBusNumero").prop("disabled", false);
					$("#goCalcular").prop("disabled", false); 
					$("#goFinalizar").prop("disabled", false);
				// 
					$("#lstPersonal").focus().select();
				}
			}               	
		},
	});	// RELLENAR LA TABLA SI EXISTE PRODUCCIÓN
}
//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////
function CalcularDesdeHastaAsignado(valor) {
    var desde = ($("#DesdeAsignado").val());
    var hasta = ($("#HastaAsignado").val());
    var precio_publico = $("#PrecioPublico").val();
    desde = desde.replace(/,/g,"");
    hasta = hasta.replace(/,/g,"");
    precio_publico = precio_publico.substring(1);
    var cantidad = 0;
    var i = 0;
    
    for (i = Number(desde); i <= Number(hasta); i++) {
      cantidad++;
    }
        var valor_estimado = (cantidad) * precio_publico
        $('#CantidadTiqueteAsignado').val(cantidad);
        $('#TotalAsignado').val(valor_estimado);
}
///////////////////////////////////////////////////////////////////////////////
// CALCULAR INCREMENTO.
//////////////////////////////////////////////////////////////////////////////
function CalcularIncremento(valor) {
    var constante99 = 99;
    var constante100 = 100;
    var hasta = 0;
    var desde = ($("#DesdeAsignado").val());
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
    $("#HastaAsignado").val(hasta); 
    // sumar 1, y cuando guarde pasarlo a desde.
    GlobalDesde = hasta + 1;

}
///////////////////////////////////////////////////////////////////////////////
function AbrirVentana(url)
{
    window.open(url, '_blank');
    return false;
}
///////////////////////////////////////////////////////////////////////////////
//	FUNCION PARA CAMBIAR LA FORMA DE BUSQUEDA
///////////////////////////////////////////////////////////////////////////////
function filterGlobal() {
    $('#listado').DataTable().search(
        $('#global_filter').val(),
    ).draw();
}
///////////////////////////////////////////////////////////////////////////////
//	FUNCION PARA CALCULAR ANTES DE GUARDAR
///////////////////////////////////////////////////////////////////////////////
function PreCalcular() {
	codigo_personal = $("#lstPersonal").val();
	codigo_transporte_colectivo = $("#lstBusNumero").val();
	CodigoInventarioTiquete = $("#CodigoInventarioTiquete").val();
	TotalVueltas = $("#TotalVueltas").val();
	codigo_ruta = $("#lstRuta").val();
	// vARIABLES QUE CONTROLA LO QUE SE PUEDE ACTUALIZAR Y BORRAR.

   // Información de la Página 1.                               
      var $objCuerpoTabla=$("#listadoAsignacion").children().prev().parent();          
		var calcular_chk_ = []; var calcular_val_ = [];
                
      var fila = 0;          
   // recorre el contenido de la tabla.
      $objCuerpoTabla.find("tbody tr").each(function(){
		// input text
			var calcular_val =$(this).find('td').eq(1).find("input[name=CalcularA]").val();
		 	var calcular_chk =$(this).find('td').eq(2).find('input[type="checkbox"]').is(':checked');
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
				$('#listadoDevolucionIngresoOk').empty();
			},
           cache: false,                     
           type: "POST",                     
           dataType: "json",                     
           url:"php_libs/soporte/ProduccionCalcular.php",                     
           data: {                     
				accion_buscar: accionAsignacion,
				fila: fila, calcular_val: calcular_val_, calcular_chk: calcular_chk_,
				codigo_personal: codigo_personal, codigo_transporte_colectivo: codigo_transporte_colectivo,
				CodigoInventarioTiquete: CodigoInventarioTiquete, CalculoFinal: CalculoFinal, TotalVueltas: TotalVueltas, codigo_ruta: codigo_ruta
                },                     
           success: function(response) {                     
				if (response.respuesta === true) {                     
					// lIMPIAR LOS VALORES DE LAS TABLAS.                     
					$('#listadoDevolucionIngresoOk').append(response.contenido);						                  
					// cambiar el valor del ingreso.
					$("label[for='LblIngreso']").text('Efectivo a Entregar $ ' + response.totalIngreso);
					//
					$("#DesdeAsignadoPartial01").val('');
					$("#DesdeAsignadoPartial02").val('');
					//
					if(CalculoFinal == "FinCalculo"){
						$("#FieldInformacion").hide();
						$("#FieldsetDevolucion").hide();
						$("#FieldsetTabla").hide();
						$('#listadoDevolucionIngresoOk').empty();
					}
					//					
						$("#IdProduccion").focus().select();
				}                
           }                     
      });
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
            for (var i=0; i<data.length; i++) {
                if(codigo_ruta == data[i].codigo){
                    miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + '</option>');
                }else{
                    miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
                }
            }
    }, "json");    
}

// TODAS LAS TABLAS VAN HA ESTAR EN CATALOGO TIQUETE..*******************
// FUNCION LISTAR TABLA catalogo buscar tiquete.
////////////////////////////////////////////////////////////
function listar_serie(){
    var miselect=$("#lstSerieBuscarTiquete");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/Produccion/ProduccionBuscar.php", {accion_buscar: 'BuscarSerie'},
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