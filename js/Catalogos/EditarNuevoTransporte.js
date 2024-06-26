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
    $('#txtVencimiento').val(today);
    $('#txtEmision').val(today);
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
            $("#fileup").attr("disabled",false);		// Botón Subir Imagen Portafolio
            $("#SubirImagen").attr("disabled",false);		// Botón Subir Imagen Portafolio
            $("#fileup_tc_frente").attr("disabled",false);		// Botón Subir Imagen Portafolio
            $("#SubirImagenTCFrente").attr("disabled",false);		// Botón Subir Imagen Portafolio
            $("#fileup_tc_vuelto").attr("disabled",false);		// Botón Subir Imagen Portafolio
            $("#SubirImagenTCVuelto").attr("disabled",false);		// Botón Subir Imagen Portafolio
            // Llamar a la función listar.
                listar();
                listar_estatus();
                listar_departamento();  // LLENAR CATALOGO PERFIL.
		}
		if($("#accion").val() == "AgregarNuevo"){
            NuevoRegistro();
			// Variables accion para guardar datos.
			accion = $("#accion").val();
			// cambiar texto de label y enlace.
			$("label[for='txtEdicionNuevo']").text("Nuevo - Tipo Transporte");
			$("label[for='iEdicionNuevo']").text("Agregar");
            //  Botones de la imagen o foto Unidad de Transporte.
            $("#fileup").attr("disabled",true);		// Botón Subir Imagen Portafolio
            $("#SubirImagen").attr("disabled",true);		// Botón Subir Imagen Portafolio
            $("#fileup_tc_frente").attr("disabled",true);		// Botón Subir Imagen Portafolio
            $("#SubirImagenTCFrente").attr("disabled",true);		// Botón Subir Imagen Portafolio
            // IMAGEN
            $(".card-img-top").attr("src", "../acomtus/img/NoDisponible.jpg");	
            $("#Imagen_tc_frente").attr("src", "../acomtus/img/NoDisponible.jpg");	
            $("#Imagen_tc_vuelto").attr("src", "../acomtus/img/NoDisponible.jpg");	
		}				
	});
//////////////////////////////////////////////////////////////////////////////////
/* INICIO DE LA FUNCION PARA NUEVO REGISTRO */
//////////////////////////////////////////////////////////////////////////////////
var NuevoRegistro = function(){
    LimpiarCampos();
    PasarFoco();
    listar_estatus();
    var codigo_departamento = '02';
    listar_departamento();  // LLENAR CATALOGO PERFIL.
    listar_municipio(codigo_departamento,''); // LLENAR CATALOGO ESTATUS.
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
                // DATOS TARJETA DE CIRCULACION FRENTE.
                    $('#txtNombrePropietario').val(data[0].nombre_propietario);
                    $('#txtYearPlaca').val(data[0].año_placa);
                    $('#txtDui').val(data[0].dui);
                        listar_municipio(data[0].codigo_departamento,data[0].codigo_municipio); // LLENAR CATALOGO ESTATUS.
                        $('#lstdepartamento').val(data[0].codigo_departamento);
                        $('#lstmunicipio').val(data[0].codigo_municipio);
                        $('#txtVencimiento').val(data[0].vencimiento);
                        $('#txtEmision').val(data[0].emision);  
                // DATOS TARJETA DE CIRCULACION VUELTO.
                $('#txtYear').val(data[0].año);
                $('#txtMarca').val(data[0].marca);
                $('#txtModelo').val(data[0].modelo);
                $('#txtCapacidad').val(data[0].capacidad);
                $('#txtTipo').val(data[0].tipo);
                $('#txtClase').val(data[0].clase);
                $('#txtDominioAjeno').val(data[0].dominio_ajeno);
                $('#txtEnCalidad').val(data[0].en_calidad);
                $('#txtColor').val(data[0].color);
                $('#txtNumeroChasis').val(data[0].numero_chasis);
                $('#txtNumeroMotor').val(data[0].numero_motor);
                $('#txtNumeroVin').val(data[0].numero_vin);
                    //$('#').val(data[0].);
                // DATOS TARJETA DE CIRCULACION FRENTE.

                // FOTO DE LA UNIDAD DE TRANSPORTE.
					if(data[0].foto_transporte == "")
					{
                        $(".card-img-top").attr("src", "../acomtus/img/NoDisponible.jpg");	
					}else{
						$(".card-img-top").attr("src", "../acomtus/img/Unidad de Transporte/" + data[0].foto_transporte);	
					}
                // FOTO DE LA UNIDAD DE TRANSPORTE.
                if(data[0].foto_tarjeta_frente == "")
                {
                    $(".card-img-top-TC-Frente").attr("src", "../acomtus/img/NoDisponible.jpg");	
                }else{
                    $(".card-img-top-TC-Frente").attr("src", "../acomtus/img/Unidad de Transporte/" + data[0].foto_tarjeta_frente);	
                }
                //
                    $("label[for='txtEdicionNuevo']").text("Actualizar - Tipo Transporte: " + NombreTipoTransporte +  " # " + data[0].numero_equipo + " " + data[0].numero_placa);
                // FOTO DE TARJETA DE CIRCULACION VUELTO
                if(data[0].foto_tarjeta_vuelto == "")
                {
                    $(".card-img-top-TC-Vuelto").attr("src", "../acomtus/img/NoDisponible.jpg");	
                }else{
                    $(".card-img-top-TC-Vuelto").attr("src", "../acomtus/img/Unidad de Transporte/" + data[0].foto_tarjeta_vuelto);	
                }
                }, "json");                				
	}; /* FINAL DE LA FUNCION LISTAR(); */
///////////////////////////////////////////////////////
// Validar Formulario, para posteriormente Guardar o Modificarlo.
 //////////////////////////////////////////////////////
	$('#formTransporte').validate({
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
            var str = $('#formTransporte').serialize();
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
////////////////////////////////////////////////////////////
function listar_departamento(){
    var miselect=$("#lstdepartamento");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_departamento.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
// FUNCION LISTAR CATALOGO ESTATUS
////////////////////////////////////////////////////////////
function listar_municipio(departamento,municipio){
	//console.log(municipio);
    var miselect=$("#lstmunicipio");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
        miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');

            $.post("includes/cargar_municipio.php", { departamento: departamento },
                   function(data){
                miselect.empty();
                for (var i=0; i<data.length; i++) {
					if(municipio == data[i].codigo){
						miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + '</option>');
					}else{
						miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
					}
                }
            }, "json");			
}