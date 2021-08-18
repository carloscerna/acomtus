// id de user global
var id_ = 0;
var buscartodos = "";
var accion = 'noAccion';
var tableA = "";
$(function(){ // INICIO DEL FUNCTION.
            // Escribir la fecha actual.
                var now = new Date();                
                var day = ("0" + now.getDate()).slice(-2);
                var month = ("0" + (now.getMonth() + 1)).slice(-2);
                var today = now.getFullYear()+"-"+(month)+"-"+(day) ;
				
				var day_M = ("20");
				var today_M = now.getFullYear()+"-"+(month)+"-"+(day_M) ;
///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
	$(document).ready(function(){
		if($("#accion").val() == "EditarRegistro"){
			// Variables Principales.
			id_ = $("#id_user").val();
			accion = $("#accion").val();
			// cambiar texto de label y enlace.
			$("label[for='txtEdicionNuevo']").text("Edición");
			$("label[for='iEdicionNuevo']").text("Edición");
            // Llamar a la función listar.
                listar();
		}
		if($("#accion").val() == "AgregarNuevoUsuario"){
			NuevoRegistro();
			// Variables accion para guardar datos.
			accion = $("#accion").val();
			// cambiar texto de label y enlace.
			$("label[for='txtEdicionNuevo']").text("Agregar Usuario");
			$("label[for='iEdicionNuevo']").text("Agregar");
		}				
	});
//////////////////////////////////////////////////////////////////////////////////
/* INICIO DE LA FUNCION PARA NUEVO REGISTRO */
//////////////////////////////////////////////////////////////////////////////////
var NuevoRegistro = function(){
    // Desactivar ChkConfiraPassword;
        $("#chk1").hide();
            LimpiarCampos();
            PasarFoco();
};
//////////////////////////////////////////////////////////////////////////////////
/* INICIO DE LA FUNCION PARA MOSTRAR LOS DATOS DEL ALUMNO */
//////////////////////////////////////////////////////////////////////////////////
	var listar = function(){
		// DETARMINAR QUE SE VA EJECUTAR.	
			$.post("php_libs/soporte/NuevoEditarUsuarios.php",  { id_x: id_, accion: 'BuscarPorId' },
				function(data){
				// Cargar valores a los objetos Llenar el formulario con los datos del registro seleccionado.
				// Modificar label en la tabs-8.
                    $("label[for='NombreUser']").text(data[0].nombre);
                // datos para el card TITLE - INFORMACIÓN GENERAL
					$('#txtId').val(id_);
                    $('#txtnombres').val(data[0].nombre);
                    $('#Password').val(data[0].password);
                    $('#ConfirmaPassword').val(data[0].password);
                    $('#lstempresa').val(data[0].codigo_institucion);
                    $('#lstperfil').val(data[0].codigo_perfil);
                    $('#lstestatus').val(data[0].codigo_estatus);
                    listar_personal(data[0].codigo_personal);
                // Desactivar contraseñas.
                    $("#Password").prop('disabled', true);
                    $("#ConfirmaPassword").prop('disabled', true);
                }, "json");                				
	}; /* FINAL DE LA FUNCION LISTAR(); */
///////////////////////////////////////////////////////
// Validar Formulario, para posteriormente Guardar o Modificarlo.
 //////////////////////////////////////////////////////
	$('#formUsers').validate({
		ignore:"",
		rules:{
                txtnombres: {required: true, minlength: 4},
                Password: {required: true, minlength: 4},
				ConfirmaPassword: {required: true,minlength: 4},
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
            var str = $('#formUsers').serialize();
            // VALIDAR CONDICIÓN DE CONTRASEÑA.
            if($('#chkcambiopassword').is(":checked")) {chkcambiopassword = 'yes';}else{chkcambiopassword = 'no';}                        
			//alert(str);
			///////////////////////////////////////////////////////////////			
			// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
			///////////////////////////////////////////////////////////////
		        $.ajax({
		            cache: false,
		            type: "POST",
		            dataType: "json",
		            url:"php_libs/soporte/NuevoEditarUsuarios.php",
		            data:str + "&id=" + Math.random() + "&cambiopassword="+chkcambiopassword,
		            success: function(response){
		            	// Validar mensaje de error
		            	if(response.respuesta == false){
                            toastr["error"](response.mensaje, "Sistema");
		            	}
		            	else{
                            toastr["success"](response.mensaje, "Sistema");
                            }               
		            },
		        });
		    },
   });

}); // fin de la funcion principal ************************************/

// Pasar foco cuando seleccionar un encargado.
function PasarFoco()
   {
       $('#txtnombres').focus();
   }
function LimpiarCampos(){
    $('#txtnombres').val('');
    $('#password').val('');
}
///////////////////////////////////////////////////////////
// Convertir a mayúsculas cuando abandone el input.
////////////////////////////////////////////////////////////
   function conMayusculas(field)
   {
        field.value = field.value.toUpperCase();
   }
///////////////////////////////////////////////////////////
// cargar diferentes lsitados.
// FUNCION LISTAR CATALOGO PERFIL.
////////////////////////////////////////////////////////////
function listar_perfil(){
    var miselect=$("#lstperfil");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_perfil.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
// FUNCION LISTAR CATALOGO ESTATUS
////////////////////////////////////////////////////////////
function listar_estatus(){
    var miselect=$("#lstestatus");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_estatus.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
// FUNCION LISTAR TABLA PERSONAL
////////////////////////////////////////////////////////////
function listar_personal(codigo_personal){
    var miselect=$("#lstpersonal");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_nombre_personal.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                if(codigo_personal == data[i].codigo){
                    miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + '</option>');    
                }else{
                    miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
                }
            }
    }, "json");    
}
// FUNCION LISTAR CATALOGO ESTATUS
////////////////////////////////////////////////////////////
function listar_empresa(){
    var miselect=$("#lstempresa");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_empresa.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
function Activar_Password(){
    if($('#chkcambiopassword').is(":checked")) {
        // Activar contraseñas.
        $("#Password").prop('disabled', false);
        $("#ConfirmaPassword").prop('disabled', false);   
    }else{
        // Desactivar contraseñas.
        $("#Password").prop('disabled', true);
        $("#ConfirmaPassword").prop('disabled', true);   
    }
}