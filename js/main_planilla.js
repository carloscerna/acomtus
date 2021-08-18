// id de user global
var id_ = 0;
var accion = "todos";
var tabla = "";
var miselect = "";
var today = "";
$(function(){ // iNICIO DEL fUNCTION.
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

		if($('#MenuTab').val() == '06'){
			$("#DivSoloParaContabilidad").hide();
		}
		//
			listar_ruta();
			listar_ann(year);
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


///////////////////////////////////////////////////////////////////////////////
// CUANDO CAMBIA LA FECHA. BUSCAR LA PRODUCCIÓN EN LA TABLA
/// EVENTOS JQUERY IMPRIMIR TODA LA PRODUCCIÓN O POR RANGO.
///////////////////////////////////////////////////////////////////////////////	  
$("#goCrearPlanilla").on('click', function (e) {
		// Limpiar datos
		fechaMes = $("#lstFechaMes").val();
		fechaAnn = $("#lstFechaAño").val();
		quincena = $("#lstQuincena").val();
		ruta = $("#lstRuta").val();
		var value = $("#lstRuta option:selected");
		var RutaText = value.text();
		// Ejecutar Informe
			varenviar = "/acomtus/php_libs/reportes/nomina_asistencia.php?fechaMes="+fechaMes+"&fechaAnn="+fechaAnn+"&quincena="+quincena+"&ruta="+ruta+"&RutaText="+RutaText;
		// Ejecutar la función abre otra pestaña.
			AbrirVentana(varenviar);   
  });
///////////////////////////////////////////////////////////////////////////////	  
$("#goCalcularPlanilla").on('click', function (e) {
	// Limpiar datos
	fechaMes = $("#lstFechaMes").val();
	fechaAnn = $("#lstFechaAño").val();
	quincena = $("#lstQuincena").val();
	ruta = $("#lstRuta").val();
	var value = $("#lstRuta option:selected");
	var RutaText = value.text();
	// Ejecutar Informe
		varenviar = "/acomtus/php_libs/reportes/nomina_asistencia_calcular.php?fechaMes="+fechaMes+"&fechaAnn="+fechaAnn+"&quincena="+quincena+"&ruta="+ruta+"&RutaText="+RutaText;
	// Ejecutar la función abre otra pestaña.
		AbrirVentana(varenviar);   
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
		//if($('#chkcambiopassword').is(":checked")) {chkcambiopassword = 'yes';}else{chkcambiopassword = 'no';}                        
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
    
    $.post("php_libs/soporte/ProduccionBuscar.php", {accion_buscar: 'BuscarRuta'},
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