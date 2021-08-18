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
	// LLAMAR A LA TABLA PERSONAL.
		listar_jornada();
		//istar_tipo_licencia();
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
$('#FechaAsistencia').val(today);
///////////////////////////////////////////////////////////////////////////////	  
// BLOQUE DE BUSQUEDA
///////////////////////////////////////////////////////////////////////////////	  
$("#goBuscarPersonalAsistencia").on('click', function (e) {
	// buscar en la tabla personal
	buscar_personal();
});
///////////////////////////////////////////////////////////////////////////////	  
// SELECCIONAR POR MEDIO DEL RADIO BUTTON PARA LA BUSQUEDA DEL MOTORISTA.
///////////////////////////////////////////////////////////////////////////////	  
$('[name="TipoLicenciaCheck"]').change(function()
{
  if ($(this).is(':checked')) {
	//
	$("#LblAsistencia").text('');
	//
	$("#lstTipoLicencia").prop("disabled", false);
	//
	$("#lstJornada").prop("disabled", true);
	// listar tipo de licencia
		listar_tipo_licencia();
  }else{
	//
	$("#LblAsistencia").text('');
	//
		// limpiar listado tipo de licencia.
		var miselect=$("#lstTipoLicencia");
		miselect.empty();
	//
	$("#lstTipoLicencia").prop("disabled", true);
	//
	$("#lstJornada").prop("disabled", false);

  }

});
///////////////////////////////////////////////////////////////////////////////
/// EVENTOS JQUERY Y para disparar la busqueda. del por nombre motorista.
///////////////////////////////////////////////////////////////////////////////
/*$("#goBuscarProduccionPM").on('click', function(){
	var codigo = $("#lstPersonalPorMotorista option:selected").val();
	$("#formBuscarPorMotorista").submit();
});*/
///////////////////////////////////////////////////////
// Validar Formulario, para la busqueda de un registro por codigo del motorista.
 //////////////////////////////////////////////////////
 $('#formAsistencia').validate({
	ignore:"",
	rules:{
			CodigoPersonal: {required: true},
			NombrePersonal:{required: true},
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
		var str = $('#formAsistencia').serialize();
	//	alert(str);
							// casilla de verificación revisar el valor.
							var TipoLicenciaChecks = "off";
							if ($("#TipoLicenciaCheck").is(':checked')) {
								//
									TipoLicenciaChecks = "on";
							}
							//alert(TipoLicenciaChecks);
		///////////////////////////////////////////////////////////////			
		// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
		///////////////////////////////////////////////////////////////
			$.ajax({
				beforeSend: function(){

				},
				cache: false,
				type: "POST",
				dataType: "json",
				url:"php_libs/soporte/NuevoEditarPersonalAsistencia.php",
				data:str + "&tipochecks=" + TipoLicenciaChecks + "&id=" + Math.random(),
				success: function(response){
					// Validar mensaje de error
					if(response.respuesta == false){
						toastr["error"](response.mensaje, "Sistema");
					}
					else{
						toastr["success"](response.mensaje, "Sistema");
						// remover el atributo checked
						$("#TipoLicenciaCheck").prop('checked', false);
						// LIMPIAR VARIABLES.ab-b-l
						$("#CodigoPersonal").val("");
						$("#NombrePersonal").val("");
						//
						$("#LblAsistencia").text('');
						//
						$("#lstTipoLicencia").prop("disabled", true);
						//
						$("#lstJornada").prop("disabled", false);
						}         
						// focus
						$("#CodigoPersonal").focus();      
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

////////////////////////////////////////////////////////////
// FUNCION LISTAR TABLA perosnal solo motoristas
////////////////////////////////////////////////////////////
function buscar_personal(codigo_personal){
    var codigo_personal = $("#CodigoPersonal").val();
    $.post("php_libs/soporte/NuevoEditarPersonalAsistencia.php", {accion_buscar: 'BuscarPersonalCodigo', codigo_personal: codigo_personal},
        function(data) {
			if(data[0].respuestaOK == true){
				var nombre_empleado = data[0].nombre_empleado;
				$("#NombrePersonal").val(nombre_empleado);
				toastr["success"](data[0].mensajeError, "Sistema");
			}else{
				toastr["error"](data[0].mensajeError, "Sistema");
			}

        }, "json");    
}
// FUNCION LISTAR TABLA catalogo_jornada
////////////////////////////////////////////////////////////
function listar_jornada(codigo_jornada){
    var miselect=$("#lstJornada");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/ProduccionBuscar.php", {accion_buscar: 'BuscarJornada'},
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                if(codigo_jornada == data[i].codigo){
						miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + '</option>');
                }else{
					if(i==1){
						miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + '</option>');
					}else{
						miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
					}
                }
            }
    }, "json");    
}
// FUNCION LISTAR TABLA catalogo_jornada
////////////////////////////////////////////////////////////
function listar_tipo_licencia(codigo_tipo_licencia){
    var miselect=$("#lstTipoLicencia");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/NuevoEditarPersonalAsistencia.php", {accion_buscar: 'BuscarTipoLicencia'},
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                if(codigo_tipo_licencia == data[i].codigo){
                    miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + '</option>');
                }else{
                    miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
                }
            }
    }, "json");    
}
function delimitNumbers(str) { return (str + "").replace(/\b(\d+)((\.\d+)*)\b/g, function(a, b, c) { return (b.charAt(0) > 0 && !(c || ".").lastIndexOf(".") ? b.replace(/(\d)(?=(\d{3})+$)/g, "$1,") : b) + c; }); } 