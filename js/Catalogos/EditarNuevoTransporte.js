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

        $("#lstTipoTransporte").change(function() {
            var codigo_tipo_transporte  = (this.value);
                // DETARMINAR QUE SE VA EJECUTAR.	
                $.post("php_libs/soporte/Catalogos/EditarNuevoTransporte.php",  { codigo_tipo_transporte: codigo_tipo_transporte, accion: 'BuscarTipoTransporte' },
                function(data){
                // datos para el card TITLE - INFORMACIÓN GENERAL
                    $('#txtNumeroEquipo').val(data[0].numero_equipo);
                }, "json");   
          });

		if($("#accion").val() == "EditarRegistro"){
			// Variables Principales.
			id_ = $("#id_user").val();
			accion = $("#accion").val();
			// cambiar texto de label y enlace.
			$("label[for='txtEdicionNuevo']").text("Actualizar - Tipo Transporte");
            $("label[for='iEdicionNuevo']").text("Edición");
            $("#txtNumeroEquipo").prop('readonly', true);
            $("#lstTipoTransporte").prop('disabled', true);
            // Llamar a la función listar.
                listar();
                listar_estatus();
		}
		if($("#accion").val() == "AgregarNuevo"){
            NuevoRegistro();
			// Variables accion para guardar datos.
			accion = $("#accion").val();
			// cambiar texto de label y enlace.
			$("label[for='txtEdicionNuevo']").text("Nuevo - Tipo Transporte");
			$("label[for='iEdicionNuevo']").text("Agregar");
		}				
	});
//////////////////////////////////////////////////////////////////////////////////
/* INICIO DE LA FUNCION PARA NUEVO REGISTRO */
//////////////////////////////////////////////////////////////////////////////////
var NuevoRegistro = function(){
    LimpiarCampos();
    PasarFoco();
    listar_estatus();
};
//////////////////////////////////////////////////////////////////////////////////
/* INICIO DE LA FUNCION PARA MOSTRAR LOS DATOS DEL ALUMNO */
//////////////////////////////////////////////////////////////////////////////////
	var listar = function(){
		// DETARMINAR QUE SE VA EJECUTAR.	
			$.post("php_libs/soporte/Catalogos/EditarNuevoTransporte.php",  { id_x: id_, accion: 'BuscarPorId' },
				function(data){
				// Cargar valores a los objetos Llenar el formulario con los datos del registro seleccionado.
				// Modificar label en la tabs-8.
                    $("label[for='NombreUser']").text(data[0].numero_placa);
                // datos para el card TITLE - INFORMACIÓN GENERAL
                    $('#txtId').val(id_);
                    $('#txtDescripcion').val(data[0].descripcion);
                    $('#txtNumeroPlaca').val(data[0].numero_placa);
                    $('#txtNumeroEquipo').val(data[0].numero_equipo);
                    $('#lstTipoTransporte').val(data[0].codigo_tipo_transporte);
                    NombreTipoTransporte = data[0].nombre_tipo_transporte;
                    $('#lstEstatus').val(data[0].codigo_estatus).change();
                //
                    $("label[for='txtEdicionNuevo']").text("Actualizar - Tipo Transporte: " + NombreTipoTransporte +  " # " + data[0].numero_equipo + " " + data[0].numero_placa);
                }, "json");                				
	}; /* FINAL DE LA FUNCION LISTAR(); */
///////////////////////////////////////////////////////
// Validar Formulario, para posteriormente Guardar o Modificarlo.
 //////////////////////////////////////////////////////
	$('#formUsers').validate({
		ignore:"",
		rules:{
                txtNumeroPlaca: {required: true, minlength: 1, maxlength: 8},
                txtNumeroEquipo: {required: true, minlength:1, number: true},
                lstTipoTransporte: {required: true},
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
		            url:"php_libs/soporte/Catalogos/EditarNuevoTransporte.php",
		            data:str + "&id=" + Math.random(),
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
// ventana modal. GENERAR BUSCAR REGISTROS
///////////////////////////////////////////////////////////////////////////////	  
$('#goBuscar').on( 'click', function () {
    window.location.href = "Transporte.php";
});	  
}); // fin de la funcion principal ************************************/

// Pasar foco cuando seleccionar un encargado.
function PasarFoco()
   {
       $('#lstTipoTransporte').focus();
   }
function LimpiarCampos(){
    $('#txtNumeroEquipo').val('');
    $('#txtNumeroPlaca').val('');
    $('#txtDescripcion').val('');
}
///////////////////////////////////////////////////////////
// Convertir a mayúsculas cuando abandone el input.
////////////////////////////////////////////////////////////
   function conMayusculas(field)
   {
        field.value = field.value.toUpperCase();
   }
// FUNCION LISTAR CATALOGO TIPO TRANSPORTE
////////////////////////////////////////////////////////////
function listar_tipo_transporte(){
    var miselect=$("#lstTipoTransporte");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_tipo_transporte.php",
        function(data) {
            miselect.empty();
            miselect.append('<option value="">Seleccionar...</option>');
            for (var i=0; i<data.length; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
// FUNCION LISTAR CATALOGO ESTATUS
////////////////////////////////////////////////////////////
function listar_estatus(){
    var miselect=$("#lstEstatus");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_estatus.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length -3; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}