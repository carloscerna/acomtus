// id de user global
var id_ = 0;
var accionCatalogo = 'noAccion';
var CodigoTabla = '01';
var tabla = '';
var pagina = 1;
var NombreTabla = '';
var MenuEmergente = '<a class="editar btn btn-default" data-accion="Hola!!!" href="#">Editar</a>'+
					'<a class="eliminar btn btn-warning" href="#">Eliminar</a>';
var MenuEmergente1 = '<a class="btn btn-primary dropdown-toggle mr-0" type="button" data-toggle="dropdown" aria-haspopup="true"'+
					'aria-expanded="false">Seleccione</a>'+
					'<div class="dropdown-menu">'+
						'<a class="editar btn btn-default" href="#">Editar</a>'+
						'<a class="eliminar btn btn-warning" href="#">Eliminar</a>'+
					'</div>';
$(function(){ // INICIO DEL FUNCTION.
///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
$(document).ready(function(){
	if($("#accionCatalogo").val() == "EditarRegistro"){
		// Variables Principales.
		id_ = $("#IdCatalogo").val();
		NombreTabla = $("#NombreTabla").val();
		accionCatalogo = "BuscarPorId";
		CodigoTabla = $('#CodigoTabla').val();
		// cambiar texto de label y enlace.
		$("label[for='txtEdicionNuevo']").text("Edición");
		$("label[for='iEdicionNuevo']").text("Edición");
		// Llamar a la función listar.
			listar();
	}
	if($("#accionCatalogo").val() == "AgregarNuevo"){
		// Variables accion para guardar datos.
		accionCatalogo = $("#accionCatalogo").val();
		NombreTabla = $("#NombreTabla").val();
		$("#txtCodigo").prop('disabled', false);
		//$("#txtCodigo").prop('readonly', true);
		// cambiar texto de label y enlace.
		$("label[for='LblNombreTabla']").text("Tabla: " + NombreTabla + " - Agregar Nuevo Registro...");
		PasarFoco();
		LimpiarCampos();
	}				
});


///////////////////////////////////////////////////////
// Validar Formulario, para posteriormente Guardar o Modificarlo.
 //////////////////////////////////////////////////////
$('#formCatalogos').validate({
		rules:{
				txtCodigo: {required: true,},
				Descripcion: {required: true,},
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
			var str = $('#formCatalogos').serialize();
			//alert(str);
			///////////////////////////////////////////////////////////////			
			// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
			///////////////////////////////////////////////////////////////
		        $.ajax({
		            beforeSend: function(){
		                
		            },
		            cache: false,
		            type: "POST",
		            dataType: "json",
		            url:"php_libs/soporte/EditarCatalogo.php",
		            data: str,
		            success: function(response){
		            	// Validar mensaje de error PORTAFOLIO.
		            	if(response.respuesta == false){
                            toastr["error"](response.mensaje, "Sistema");
		            	}
		            	else{
							toastr["success"](response.mensaje, "Sistema");
						//	Actualizar Saldos.
							
                            }               
		            },
		        });
		    },
});
//************************************/
}); // fin de la funcion principal ************************************/
//************************************/
///////////////////////////////////////////////////////////////////////////
//FUNCION ABRE OTRA PESTAÑA.
///////////////////////////////////////////////////////////////////////////
function AbrirVentana(url)
{
    window.open(url, '_blank');
    return false;
}		
// Pasar foco cuando seleccionar un encargado.
function PasarFoco()
   {
       $('#Descripcion').focus();
   }
function LimpiarCampos(){
	$('#Descripcion').val('');
}

//////////////////////////////////////////////////////////////////////////////////
/* INICIO DE LA FUNCION PARA MOSTRAR LOS DATOS DEL ALUMNO */
//////////////////////////////////////////////////////////////////////////////////
var listar = function(){
	// DETARMINAR QUE SE VA EJECUTAR.	
		$.post("php_libs/soporte/EditarCatalogo.php",  { id_: id_, accion: accionCatalogo, CodigoTabla: CodigoTabla },
			function(data){
			// Cargar valores a los objetos Llenar el formulario con los datos del registro seleccionado.
			// Modificar label en la tabs-8.
				$("label[for='txtEdicionNuevo']").text('Editar Tabla: ' + NombreTabla);
			// datos para el card TITLE - INFORMACIÓN GENERAL
				$('#txtCodigo').val(data[0].codigo);
				$('#Descripcion').val(data[0].descripcion);
			// cambiar el accion y la variable.
				accionCatalogo = "ActualizarPorId";
				$('#accionCatalogo').val(accionCatalogo);
					toastr["success"](data[0].mensaje, "Sistema");
			}, "json");                				
}; /* FINAL DE LA FUNCION LISTAR(); */
