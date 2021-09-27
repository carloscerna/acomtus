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
    $("#year").val(now.getFullYear());
///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
	$(document).ready(function(){
		if($("#accion").val() == "EditarRegistro"){
			// Variables Principales.
			id_ = $("#id_user").val();
			accion = $("#accion").val();
			// cambiar texto de label y enlace.
			$("label[for='txtEdicionNuevo']").text("Actualizar - Tiquete 'Serie'");
            $("label[for='iEdicionNuevo']").text("Edición");
            // Desactivar ciertas opciones
            $('#informacion').prop("disabled", false);
            // Activar la edición de Estatus Tiquete.
            $("#lstestatus").prop("disabled", false)
            // Llamar a la función listar.
                listar();
		}
		if($("#accion").val() == "AgregarNuevo"){
			NuevoRegistro();
			// Variables accion para guardar datos.
			accion = $("#accion").val();
			// cambiar texto de label y enlace.
			$("label[for='txtEdicionNuevo']").text("Nuevo - Tiquete 'Serie'");
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
			$.post("php_libs/soporte/NuevoEditarTiqueteSerie.php",  { id_x: id_, accion: 'BuscarPorId' },
				function(data){
				// Cargar valores a los objetos Llenar el formulario con los datos del registro seleccionado.
				// Modificar label en la tabs-8.
                    $("label[for='NombreUser']").text(data[0].descripcion);
                // datos para el card TITLE - INFORMACIÓN GENERAL
                    $('#txtId').val(id_);
                    $('#txtFecha').val(data[0].fecha);
                    $('#lstProveedor').val(data[0].codigo_proveedor);
                    $('#lstTiqueteSerie').val(data[0].codigo_serie);
                    $('#lstTiqueteColor').val(data[0].codigo_tiquete_color);
                    $('#txtTiraje').val(data[0].tiraje);
                    $('#txtNumeroInicio').val(data[0].numero_inicio);
                    $('#txtNumeroFin').val(data[0].numero_fin);
                    
                    $('#txtDescripcion').val(data[0].descripcion);
                    $('#lstestatus').val(data[0].codigo_estatus);

                    $('#txtCosto').val(data[0].costo);
                    $('#txtTotal').val(data[0].total);
                    $('#txtPrecioPublico').val(data[0].precio_publico);
                }, "json");                				
	}; /* FINAL DE LA FUNCION LISTAR(); */
///////////////////////////////////////////////////////
// Validar Formulario, para posteriormente Guardar o Modificarlo.
 //////////////////////////////////////////////////////
	$('#formUsers').validate({
		ignore:"",
		rules:{
                txtDescripcion: {required: true, minlength: 4, maxlength: 25},
                txtExistenciaInicial: {required: true, minlength:1, number: true},
				//lstTiqueteSerie: {required: true},
                lstTiqueteSerie: {required: true},
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
			//alert(str);
			///////////////////////////////////////////////////////////////			
			// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
			///////////////////////////////////////////////////////////////
		        $.ajax({
		            cache: false,
		            type: "POST",
		            dataType: "json",
		            url:"php_libs/soporte/NuevoEditarTiqueteSerie.php",
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

}); // fin de la funcion principal ************************************/

// Pasar foco cuando seleccionar un encargado.
function PasarFoco()
   {
       $('#txtFecha').focus();
   }
function LimpiarCampos(){
    $('#txtDescripcion').val('');
}
///////////////////////////////////////////////////////////
// Convertir a mayúsculas cuando abandone el input.
////////////////////////////////////////////////////////////
   function conMayusculas(field)
   {
        field.value = field.value.toUpperCase();
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
            for (var i=0; i<=1; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
// FUNCION LISTAR CATALOGO TIQUETE SERIE
////////////////////////////////////////////////////////////
function listar_tiquete_serie(){
    var miselect=$("#lstTiqueteSerie");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_tiquete_serie.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
// FUNCION LISTAR CATALOGO TIQUETE COLOR
////////////////////////////////////////////////////////////
function listar_tiquete_color(){
    var miselect=$("#lstTiqueteColor");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_tiquete_color.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
// FUNCION LISTAR CATALOGO PROVEEDOR
////////////////////////////////////////////////////////////
function listar_proveedor(){
    var miselect=$("#lstProveedor");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_proveedor.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}