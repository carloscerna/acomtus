// id de user global
var id_ = 0;
var buscartodos = "";
var accion = 'noAccion';
var tableA = "";
$(function(){ // INICIO DEL FUNCTION.
// Escribir la hora actual.
    var hora = new Date();
    var hora_formato_24 = hora.getHours();
    var hora_formato_12 = hora.getHours();
    var minutos = hora.getMinutes();
// Agregar el AM O PM
    var formato_12 = hora_formato_24 > 12 ? " p.m." : " a.m.";
    var hora_actual = hora_formato_12 + ':' + minutos + formato_12;
// Asignar la input Producción:
// Escribir la fecha actual.
    var now = new Date();                
    var day = ("0" + now.getDate()).slice(-2);
    var month = ("0" + (now.getMonth() + 1)).slice(-2);
    var year = now.getFullYear();

    var today = now.getFullYear()+"-"+(month)+"-"+(day) ;
// ASIGNAR FECHA ACTUAL A LOS DATE CORRESPONDIENTES.
    $('#FechaProduccion').val(today);
///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
	$(document).ready(function(){
		if($("#accion").val() == "EditarRegistro"){
			// Variables Principales.
			id_ = $("#id_user").val();
			accion = $("#accion").val();
			// cambiar texto de label y enlace.
			$("label[for='txtEdicionNuevo']").text("Actualizar - Producción | Devolución ");
            $("label[for='iEdicionNuevo']").text("Edición");
            // Llamar a la función listar.
            // desactivar fieldset ingreso o devolucion.
            $('#FieldTiquete').hide();

		}
		if($("#accion").val() == "AgregarNuevo"){
            NuevoRegistro();
			// Variables accion para guardar datos.
			accion = $("#accion").val();
			// cambiar texto de label y enlace.
			$("label[for='txtEdicionNuevo']").text("Nuevo - Producción | Asignado ");
            $("label[for='iEdicionNuevo']").text("Agregar");
            // desactivar fieldset ingreso o devolucion.
            $('#FieldsetDevolucion').prop("disabled", true);
        }
        // configurar el Select2
            $('#lstPersonal').select2({
                theme: "classic"
            });
            $('#lstUnidadTransporte').select2({
                theme: "classic"
            });
        // LLAMAR A LA TABLA PERSONAL.
            listar_personal();
            listar_jornada();
            listar_transporte_colectivo();
            listar_ruta();
            listar_serie();

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
			$.post("php_libs/soporte/NuevoEditarProduccion.php",  { id_x: id_, accion: 'BuscarPorId' },
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
                FechaDiaProduccion: {required: true, maxlength:2, minlength:2},
                FechaMesProduccion: {required: true, maxlength:2, minlength:2},
                FechaYearProduccion: {required: true, maxlength:4, minlength:4},
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
                    beforeSend: function(){
                        $('#listadoAsignacionOk').empty();
                    },
		            cache: false,
		            type: "POST",
		            dataType: "json",
		            url:"php_libs/soporte/NuevoEditarProduccion.php",
		            data:str + "&id=" + Math.random(),
		            success: function(response){
		            	// Validar mensaje de error
		            	if(response.respuesta == false){
                            toastr["error"](response.mensaje, "Sistema");
                            $('#listadoAsignacionOk').append(response.contenido);
		            	}
		            	else{
                            toastr["success"](response.mensaje, "Sistema");
                            $('#listadoAsignacionOk').append(response.contenido);

                            }               
		            },
		        });
		    },
   });

///////////////////////////////////////////////////////////////////////////////	  
// BLOQUE EXTRAER INFORMACIÓN DEL REGISTROS (PRODUCCION ASIGNADO)
$('body').on('click','#listadoAsignacion a',function (e){
	e.preventDefault();
// DATA-ACCION Y HREF
	Id_Editar_Eliminar = $(this).attr('href');
	accionAsignacion = $(this).attr('data-accion');
	pagina = $(this).attr('href');
	//alert(Id_Editar_Eliminar+" "+accionAsignacion);
// EDTIAR REGISTRO.
	if(accionAsignacion  == 'EditarAsignacion'){
		// ID PERSONAL
			accionAsignacion = "BuscarIdPortafolio";
		// DETARMINAR QUE SE VA EJECUTAR.	
			///////////////////////////////////////////////////////////////			
			// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
			///////////////////////////////////////////////////////////////
		// DETARMINAR QUE SE VA EJECUTAR.	
		$.post("php_libs/soporte/.php",  {accion: accionAsignacion, id_: Id_Editar_Eliminar},
			function(data){
				$("#tx").val(data[0].fecha);	

		}, "json");
	}else if(accionAsignacion == "EliminarAsignacion"){
		//	ENVIAR MENSAJE CON SWEETALERT 2, PARA CONFIRMAR SI ELIMINA EL REGISTRO.
		const swalWithBootstrapButtons = Swal.mixin({
			customClass: {
			confirmButton: 'btn btn-success',
			cancelButton: 'btn btn-danger'
			},
			buttonsStyling: false
		})
		
		swalWithBootstrapButtons.fire({
			title: '¿Qué desea hacer?',
			text: 'Eliminar el Registro Seleccionado!',
			showCancelButton: true,
			confirmButtonText: 'Sí, Eliminar!',
			cancelButtonText: 'No, Cancelar!',
			reverseButtons: true,
			allowOutsideClick: false,
			allowEscapeKey: false,
			allowEnterKey: false,
			stopKeydownPropagation: false,
			closeButtonAriaLabel: 'Cerrar Alerta',
			type: 'question'
		}).then((result) => {
			if (result.value) {
			// PROCESO PARA ELIMINAR REGISTRO.
			$.ajax({
                beforeSend: function(){
                    $('#listadoAsignacionOk').empty();
                },
				cache: false,
				type: "POST",
				dataType: "json",
				url:"php_libs/soporte/NuevoEditarTiqueteSerie.php",
				data: "id_=" + Id_Editar_Eliminar + "&accion_buscar=" + accionAsignacion,
				success: function(response){
					// Validar mensaje de error proporcionado por el response. contenido.
					if(response.respuesta == false){
						toastr["error"](response.mensaje, "Sistema");
					}
					else{
						toastr["info"](response.mensaje, "Sistema");
                            $('#listadoAsignacionOk').append(response.contenido);
						}               
				},
			});
			//////////////////////////////////////
			} else if (
			/* Read more about handling dismissals below */
			result.dismiss === Swal.DismissReason.cancel
			) {
			swalWithBootstrapButtons.fire(
				'Cancelar',
				'Su Archivo no ha sido Eliminado :)',
				'error'
			)
			}
		})
	}else if(accionPortafolio == "PaginacionPortafolio"){
		// paginación buscar por cóidog Personal.
			VerPortafolioPaginacion();
	}
});
}); // fin de la funcion principal ************************************/
function CalcularDesdeHastaAsignado(valor) {
    var desde = ($("#DesdeAsignado").val());
    var hasta = ($("#HastaAsignado").val());
    var precio_publico = $("#PrecioPublico").val();
    desde = desde.replace(/,/g,"");
    hasta = hasta.replace(/,/g,"");
    precio_publico = precio_publico.substring(1);
    var cantidad = 0;
    var i = 0;
    
    for (i = Number(desde); i <= Number(hasta); i++) {
      cantidad++;
    }
        var valor_estimado = (cantidad) * precio_publico
        $('#CantidadTiqueteAsignado').val(cantidad);
        $('#TotalAsignado').val(valor_estimado);
}
// Pasar foco cuando seleccionar un encargado.
function PasarFoco()
   {
       $('#FechaProduccion').focus();
   }
function LimpiarCampos(){

}
///////////////////////////////////////////////////////////
// Convertir a mayúsculas cuando abandone el input.
////////////////////////////////////////////////////////////
   function conMayusculas(field)
   {
        field.value = field.value.toUpperCase();
   }
// TODAS LAS TABLAS VAN HA ESTAR EN PRODUCCIONBUSCAR.*******************
// FUNCION LISTAR TABLA PERSONAL
////////////////////////////////////////////////////////////
function listar_personal(){
    var miselect=$("#lstPersonal");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/ProduccionBuscar.php", {accion_buscar: 'BuscarPersonalMotorista'},
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                    miselect.append('<option value="' + data[i].codigo + '">' + data[i].codigo + " - " + data[i].descripcion + '</option>');
            }
    }, "json");    
}
// TODAS LAS TABLAS VAN HA ESTAR EN PRODUCCIONBUSCAR.*******************
// FUNCION LISTAR TABLA catalogo_jornada
////////////////////////////////////////////////////////////
function listar_jornada(){
    var miselect=$("#lstJornada");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/ProduccionBuscar.php", {accion_buscar: 'BuscarJornada'},
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                    miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
// TODAS LAS TABLAS VAN HA ESTAR EN PRODUCCIONBUSCAR.*******************
// FUNCION LISTAR TABLA catalogo_transporte_colectivo
////////////////////////////////////////////////////////////
function listar_transporte_colectivo(){
    var miselect=$("#lstUnidadTransporte");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/ProduccionBuscar.php", {accion_buscar: 'BuscarTransporteColectivo'},
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                    miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
// TODAS LAS TABLAS VAN HA ESTAR EN PRODUCCIONBUSCAR.*******************
// FUNCION LISTAR TABLA catalogo_ruta
////////////////////////////////////////////////////////////
function listar_ruta(){
    var miselect=$("#lstRuta");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/ProduccionBuscar.php", {accion_buscar: 'BuscarRuta'},
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                    miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
// TODAS LAS TABLAS VAN HA ESTAR EN PRODUCCIONBUSCAR.*******************
// FUNCION LISTAR TABLA catalogo_ruta
////////////////////////////////////////////////////////////
function listar_serie(){
    var miselect=$("#lstSerie");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/ProduccionBuscar.php", {accion_buscar: 'BuscarSerie'},
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                    miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
            // pasar valor a input.
            $('#PrecioPublico').val(data[0].precio_publico);
            $('#Existencia').val(data[0].existencia);
    }, "json");    
}
function VerAsignacion() {
    ///////////////////////////////////////////////////////////////			
    // Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
    ///////////////////////////////////////////////////////////////
        // 	lIMPIAR SECTION QUE CONTIENE EL PORTAFOLIO.
            $('#listadoAsignacionOk').empty();
        // Variables accion para guardar datos.
            accionAsignacion = "BuscarPorIdAsignacion";
        
        $.ajax({
            cache: false,
            type: "POST",
            dataType: "json",
            url:"php_libs/soporte/NuevoEditarProduccion.php",
            data:"accion_buscar=" + accionAsignacion,
            success: function(response){
                // Validar mensaje de error PORTAFOLIO.
                if(response.respuesta == false){
                    toastr["error"](response.mensaje, "Sistema");
                    $("#listadoAsignacionOk").append(response.contenido);
                }
                else{
                    // Ver el Portafolio.
                    $("#listadoAsignacionOk").append(response.contenido);
                    toastr["info"](response.mensaje, "Sistema");
                    }               
            },
        });
    }