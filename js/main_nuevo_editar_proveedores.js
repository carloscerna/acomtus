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
// ASIGNAR FECHA ACTUAL A LOS DATE CORRESPONDIENTES.
    $('#txtFecha').val(today);
///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
	$(document).ready(function(){
		if($("#accion").val() == "EditarRegistro"){
			// Variables Principales.
			id_ = $("#id_user").val();
			accion = $("#accion").val();
			// cambiar texto de label y enlace.
			$("label[for='txtEdicionNuevo']").text("Actualizar - Empresa o Persona ");
            $("label[for='iEdicionNuevo']").text("Edición");
            // Llamar a la función listar.
                listar();
		}
		if($("#accion").val() == "AgregarNuevo"){
            NuevoRegistro();
			// Variables accion para guardar datos.
			accion = $("#accion").val();
			// cambiar texto de label y enlace.
			$("label[for='txtEdicionNuevo']").text("Nuevo - Empresa o Persona ");
			$("label[for='iEdicionNuevo']").text("Agregar");
		}				
	});
//////////////////////////////////////////////////////////////////////////////////
/* INICIO DE LA FUNCION PARA NUEVO REGISTRO */
//////////////////////////////////////////////////////////////////////////////////
var NuevoRegistro = function(){
            LimpiarCampos();
            PasarFoco();
};
//////////////////////////////////////////////////////////////////////////////////
/* INICIO DE LA FUNCION PARA MOSTRAR LOS DATOS DEL ALUMNO */
//////////////////////////////////////////////////////////////////////////////////
	var listar = function(){
		// DETARMINAR QUE SE VA EJECUTAR.	
			$.post("php_libs/soporte/NuevoEditarProveedores.php",  { id_x: id_, accion: 'BuscarPorId' },
				function(data){
				// Cargar valores a los objetos Llenar el formulario con los datos del registro seleccionado.
				// Modificar label en la tabs-8.
                    $("label[for='NombreUser']").text(data[0].nombre_proveedor);
                // datos para el card TITLE - INFORMACIÓN GENERAL
                    $('#txtId').val(id_);
                    $('#txtNombreProveedor').val(data[0].nombre_proveedor);
                    $('#txtTelefonoUno').val(data[0].telefono_uno);
                    $('#txtTelefonoDos').val(data[0].telefono_dos);
                    $('#txtTelefonoCelular').val(data[0].telefono_celular);
                    $('#txtNombreContacto').val(data[0].nombre_contacto);
                    $('#txtDireccion').val(data[0].direccion);
                }, "json");                				
	}; /* FINAL DE LA FUNCION LISTAR(); */
///////////////////////////////////////////////////////
// Validar Formulario, para posteriormente Guardar o Modificarlo.
 //////////////////////////////////////////////////////
	$('#formUsers').validate({
		ignore:"",
		rules:{
                txtNombreProveedor: {required: true, minlength: 1, maxlength: 80},
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
			//alert(str);
			///////////////////////////////////////////////////////////////			
			// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
			///////////////////////////////////////////////////////////////
		        $.ajax({
		            cache: false,
		            type: "POST",
		            dataType: "json",
		            url:"php_libs/soporte/NuevoEditarProveedores.php",
		            data:str + "&id=" + Math.random(),
		            success: function(response){
		            	// Validar mensaje de error
		            	if(response.respuesta == false){
                            toastr["error"](response.mensaje, "Sistema");
		            	}
		            	else{
                            toastr["success"](response.mensaje, "Sistema");
							window.location.href = 'proveedores.php';
                            }               
		            },
		        });
		    },
   });

}); // fin de la funcion principal ************************************/

// Pasar foco cuando seleccionar un encargado.
function PasarFoco()
   {
       $('#txtNombreProveedor').focus();
   }
function LimpiarCampos(){
    $('#txtNombreProveedor').val('');
    $('#txtTelefonoUno').val('');
    $('#txtTelefonoDos').val('');
    $('#txtTelefonoCelular').val('');
    $('#txtNombreContacto').val('');
    $('#txtDireccion').val('');
}
///////////////////////////////////////////////////////////
// Convertir a mayúsculas cuando abandone el input.
////////////////////////////////////////////////////////////
   function conMayusculas(field)
   {
        field.value = field.value.toUpperCase();
   }