// id de user global
var id_ = 0;
var NIE = 0;
var tabla = "";
$(function(){ // iNICIO DEL fUNCTION.
///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
		$(document).ready(function(){

		});		
///////////////////////////////////////////////////////////////////////////////
//	FUNCION LISTAR BUSQUEDA DE LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
// Escribir la fecha actual.
var now = new Date();                
var day = ("0" + now.getDate()).slice(-2);
var month = ("0" + (now.getMonth() + 1)).slice(-2);
var year = now.getFullYear();

var today = now.getFullYear()+"-"+(month)+"-"+(day) ;
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
      if (keycode == 13) {
		// Limpiar datos
		codigo_produccion = $('#NumeroCorrelativo').val();
		fecha = $("#FechaProduccion").val();
		reimprimir = true;
		// Ejecutar Informe
			varenviar = "/acomtus/php_libs/reportes/Produccion/TiqueteTodos.php?codigo_produccion="+codigo_produccion+"&fecha="+fecha+"&reimprimir="+reimprimir;
		// Ejecutar la función abre otra pestaña.
			AbrirVentana(varenviar);   
      }
  });
///////////////////////////////////////////////////////////////////////////////
// CUANDO CAMBIA LA FECHA. BUSCAR LA PRODUCCIÓN EN LA TABLA
/// EVENTOS JQUERY IMPRIMIR TODA LA PRODUCCIÓN O POR RANGO.
///////////////////////////////////////////////////////////////////////////////	  
$("#goImprimirProduccionCompleta").on('click', function (e) {
		// Limpiar datos
		codigo_produccion = $('#NumeroCorrelativo').val();
		fecha = $("#FechaProduccion").val();
		imprimir = "todos";
		// Ejecutar Informe
			varenviar = "/acomtus/php_libs/reportes/Produccion/TiqueteTodos.php?codigo_produccion="+codigo_produccion+"&fecha="+fecha+"&imprimir="+imprimir;
		// Ejecutar la función abre otra pestaña.
			AbrirVentana(varenviar);   
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
			url:"php_libs/soporte/ProduccionCalcularImprimir.php",
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
					$("label[for='LblListadoTotalIngreso']").text('Total  $ ' + response.totalIngreso);
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
			url:"php_libs/soporte/ProduccionCalcularImprimir.php",
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
			url:"php_libs/soporte/ProduccionCalcularImprimir.php",
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
						$("#field_produccion_detalle").hide();
						$("#FieldsetTabla").hide();
					// limpiar tabla producción detalle.
						$('#listadoDetalleOk').empty();
					// cambiar el valor del ingreso.
						$("label[for='LblProduccionesTotal']").text('Total Produccion: ' + response.totalProduccion);
						$("label[for='LblProduccionesTotalIngreso']").text('Total Ingreso Produccion $ ' + response.totalProduccionIngreso);
						$("label[for='LblTotalTiquetes']").text('# Tiquetes Vendidos: ' + response.cantidadTiquetePantalla);
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