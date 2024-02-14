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
	$("#NumeroCorrelativo").val('');
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
		imprimir = "todos";
		// Ejecutar Informe
			varenviar = "/acomtus/php_libs/reportes/Produccion/TiqueteTodos.php?codigo_produccion="+codigo_produccion+"&fecha="+fecha;
		// Ejecutar la función abre otra pestaña.
			AbrirVentana(varenviar);   
      }
  });
///////////////////////////////////////////////////////////////////////////////
// CUANDO CAMBIA LA FECHA. BUSCAR LA PRODUCCIÓN EN LA TABLA
/// EVENTOS JQUERY IMPRIMIR TODA LA PRODUCCIÓN O POR RANGO.
///////////////////////////////////////////////////////////////////////////////	  
$("#goImprimirProduccionCompleta").on('click', function (e) {
   // Información de la Página 1.   IMPRIMIR TODO LA PRODUCCIÓN.                            
   $("#NumeroCorrelativo").val('');
   codigo_produccion = $('#NumeroCorrelativo').val();
		fecha = $("#FechaProduccion").val();
		imprimir = "todos";
		// Ejecutar Informe
			varenviar = "/acomtus/php_libs/reportes/Produccion/TiqueteTodos.php?codigo_produccion="+codigo_produccion+"&fecha="+fecha;
		// Ejecutar la función abre otra pestaña.
			AbrirVentana(varenviar);   
  });
  ///////////////////////////////////////////////////////////////////////////////	  
  // 	reportres producción COMPLETA
  //////////////////////////////////////////////////////////////////////////////
$("#goImprmirProduccion").on('click', function (e) {
	fecha = $("#FechaProduccion").val();	  
	// Ejecutar Informe
		varenviar = "/acomtus/php_libs/reportes/Produccion/Completa.php?fecha="+fecha;
	// Ejecutar la función abre otra pestaña.
		AbrirVentana(varenviar);   
   });
///////////////////////////////////////////////////////////////////////////////	  
// BLOQUE EXTRAER INFORMACIÓN DEL REGISTROS (PRODUCCION ASIGNADO)
$('body').on('click','#TablaProduccion a',function (e){
	e.preventDefault();
// DATA-ACCION Y HREF
	codigo_produccion = $(this).attr('href');
	alert(codigo_produccion);
	accionAsignacion = $(this).attr('data-accion');
	//alert(Id_Editar_Eliminar+" "+accionAsignacion);
// EDTIAR REGISTRO.
	if(accionAsignacion  == 'ProduccionImprimir'){
		// Limpiar datos
		fecha = $("#FechaProduccion").val();
		imprimir = "todos";
		// Ejecutar Informe
			varenviar = "/acomtus/php_libs/reportes/Produccion/TiqueteTodos.php?codigo_produccion="+codigo_produccion+"&fecha="+fecha+"&imprimir="+imprimir;
		// Ejecutar la función abre otra pestaña.
			AbrirVentana(varenviar);   
	}
});



});	// final de FUNCTION.
///////////////////////////////////////////////////////////////			
// Inicio del Ajax. Buscar, Actualizar Producción Devolución e Ingreso.
///////////////////////////////////////////////////////////////
function BuscarProduccionPorFecha() {
	// Variables accion para guardar datos.
		var fecha = $("#FechaProduccion").val();
		$("#NumeroCorrelativo").val('');
		accion_buscar = "BuscarProduccionPorRuta";
	/// BUSCAR SI EXISTE PRODUCCIÓN ENLA FECHA DIGITADA.	
		$.ajax({
			beforeSend: function(){
				$('#listadoOk').empty();
			},
			cache: false,
			type: "POST",
			dataType: "json",
			url:"php_libs/soporte/ProduccionImprimir.php",
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
					// cambiar el valor del ingreso.
						$("label[for='LblProduccionesTotal']").text('Total Produccion: ' + response.totalProduccion);
					// Mostrar fieldset
						$("#ProduccionTabla").show();
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