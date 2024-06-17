// id de user global
var idUser_ok = 0;
var Id_Editar_Eliminar = 0;
var Accion_Editar_Eliminar = "noAccion";
var accionAlertas = "";
var codigoPersonal = "";
var texto_se = "";
var msjEtiqueta = "";
// INICIO DE LA FUNCION PRINCIPAL.
$(function(){
//
//  INVISILBLE TODOS LOS MENSAJES.
//
    $("#AlertPersonalAlertas").css("display", "none");
    //
//  OPCIONES PARA EL TAB NAV
//
    $(document).ready(function () {
        // BUSCAR EN LA TABLA PERSONAL_ALERTAS.
        $("#alertas-tab").css("background-color", "#00ff00");
        //
        $("#NavPersonalAlertas ul.nav > li > a").on("click", function () {
            $TextoTab = $(this).text();
            if($TextoTab == "Alertas"){
                // Borrar información de la Tabla.
                    $('#listaContenidoPersonalAlertas').empty();
                //
                BuscarPersonalAlertas();
            }else{
                //alert("Nav-Tab " + $TextoTab);
            }
        });
    });
    //
    // FUNCIONALIDAD DE LOS DIFERENTES BOTONES
    //
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // BLOQUE PARA ADMINISTRAR LAS ModalidadS.
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //
        // BLOQUE EXTRAER INFORMACIÓN DEL REGISTROS (ASIGANTURAS)
        //
		$('body').on('click','#listaContenidoPersonalAlertas a',function (e){
			e.preventDefault();
			// Id Usuario
    			Id_Editar_Eliminar = $(this).attr('href');
	    		accion_ok = $(this).attr('data-accion');
                    // EDITAR LA Modalidad
                    if($(this).attr('data-accion') == 'EditarModalidad'){
                        // Valor de la acción
                            $('#accion_Modalidad').val('ActualizarModalidad');
                            accion_modalidad = 'EditarModalidad';
                            
                            // obtener el valor del id.
                            var id_ = $(this).parent().parent().children('td:eq(2)').text();
                            
                            // Llamar al archivo php para hacer la consulta y presentar los datos.
                            $.post("php_libs/soporte/Mantenimiento/Servicio Educativo/phpAjaxServicioEducativo.php",  { id_: id_, accion: accion_modalidad},
                                function(data) {
                                // Llenar el formulario con los datos del registro seleccionado tabs-1
                                // Datos Generales
                                    $('#IdModalidad').val(data[0].id_Modalidad);
                                    $('#CodigoModalidad').val(data[0].codigo);
                                    $('#DescripcionModalidad').val(data[0].nombre);
                                    //
                                    listar_CodigoEstatusModalidad(data[0].codigo_estatus);
                                    // Abrir ventana modal.
                                    $('#VentanaModalidad').modal("show");
                                    $("label[for=LblTituloModalidad]").text("Modalidad | Actualizar");
                                    // reestablecer el accion_modalidad a=ActulizarModalidad.
                                    accion_modalidad = "ActualizarModalidad";
                                },"json");
                    }
                    // ELIMINAR REGISTRO Modalidad.
                    if($(this).attr('data-accion') == 'eliminarAlertas'){
                        //	ENVIAR MENSAJE CON SWEETALERT 2, PARA CONFIRMAR SI ELIMINA EL REGISTRO.
                        const swalWithBootstrapButtons = Swal.mixin({
                            customClass: {
                            confirmButton: 'btn btn-success',
                            cancelButton: 'btn btn-danger'
                            },
                            buttonsStyling: false
                        })
                        //
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
                                    // ejecutar Ajax.. 
                                    $.ajax({
                                    cache: false,                     
                                    type: "POST",                     
                                    dataType: "json",                     
                                    url:"php_libs/soporte/Personal/Alertas.php",                     
                                    data: {                     
                                            accionAlertas: 'eliminarAlertas', id_: Id_Editar_Eliminar,
                                            },                     
                                    success: function(response) {                     
                                            if (response.respuesta === true) {                     		
                                                    // Llamar al archivo php para hacer la consulta y presentar los datos.
                                                BuscarPersonalAlertas();
                                            }
                                            if (response.respuesta === false) {                     		
                                                toastr["info"]('Registro no Eliminado...', "Sistema");
                                            }
                                    }                     
                                    });
                            //////////////////////////////////////
                            } else if (
                            /* Read more about handling dismissals below */
                            result.dismiss === Swal.DismissReason.cancel
                            ) {
                            swalWithBootstrapButtons.fire(
                                'Cancelar',
                                'Su Registro no ha sido Eliminado :)',
                                'error'
                            )
                            }
                        })
                    }
        });
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// ACTIVAR Y DESACTIVAR CHECKBOX DE LA TABLA.
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$("#checkBoxAllPersonalAlertas").on("change", function () {
		$("#listadoContenidoPersonalAlertas tbody input[type='checkbox'].case").prop("checked", this.checked);
	});
	
	$("#listadoContenidoPersonalAlertas tbody").on("change", "input[type='checkbox'].case", function () {
	  if ($("#listadoContenidoPersonalAlertas tbody input[type='checkbox'].case").length == $("#listadoContenidoPersonalAlertas tbody input[type='checkbox'].case:checked").length) {
		  $("#checkBoxAllPersonalAlertas").prop("checked", true);
	  } else {
		  $("#checkBoxAllPersonalAlertas").prop("checked", false);
	  }
	 });	
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// ACTIVAR Y DESACTIVAR CHECKBOX DE LA TABLA.
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////     
            //
            // ENVIO DE DATOS Y VALIDAR INFORMACION DEL FORM
            //
            $('#goGuardarPersonalAlertas').on( 'click', function () {
                // enviar form
                    accionAlertas = "guardarAlertas";
                    codigoPersonal = $("#txtcodigo").val();
                    $('#formPersonalAlertas').submit();
            });
            //	  
            // Validar Formulario para la buscque de registro segun el criterio.   
            // PARA GUARDAR O ACTUALIZAR.
            $('#formPersonalAlertas').validate({
                ignore:"",
                rules:{
                        lstPersonalAlertas: {required: true,},
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
                        var str = $('#formPersonalAlertas').serialize();
                        accionAlertas = "guardarAlertas";
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
                            url:"php_libs/soporte/Personal/Alertas.php",
                            data:str + "&accion=" + accionAlertas + "&id=" + Math.random() + "&codigoPersonal=" + codigoPersonal,
                            success: function(response){
                                // Validar mensaje de error
                                if(response.respuesta == false){
                                    toastr["error"](response.mensaje, "Sistema");
                                }
                                else{
                                    toastr["success"](response.mensaje, "Sistema");
                                    // Llamar al archivo php para hacer la consulta y presentar los datos.
                                        $('#accion_Modalidad').val('BuscarModalidad');
                                        accionAlertas = 'BuscarAlertas';
                                        $.post("php_libs/soporte/Personal/Alertas.php",  {accion: accionAlertas, codigoPersonal: codigoPersonal},
                                            function(response) {
                                                if (response.respuesta === true) {
                                                    toastr["info"]('Registros Encontrados', "Sistema");
                                                }
                                                if (response.respuesta === false) {
                                                    toastr["warning"]('Registros No Encontrados', "Sistema");
                                                }                                                                                    // si es exitosa la operación
                                                    $('#listaContenidoPersonalAlertas').empty();
                                                    $('#listaContenidoPersonalAlertas').append(response.contenido);
                                            },"json");
                                    }               
                            },
                        });
                    },
           });
}); // FIN DEL FUNCTION.
//
// Mensaje de Carga de Ajax.
function configureLoadingScreen(screen){
    $(document)
        .ajaxStart(function () {
            screen.fadeIn();
        })
        .ajaxStop(function () {
            screen.fadeOut();
        });
    }
///////////////////////////////////////////////////////////////////////
// TODAS LAS TABLAS VAN HA ESTAR EN PERSONAL ALERTAS.*******************
// FUNCION LISTAR TABLA catalogo_personal_alertas
////////////////////////////////////////////////////////////
function listar_CodigoAlertas(CodigoPersonalAlertas){
    var miselect2=$("#lstPersonalAlertas");
    $("#lstPersonalAlertas").css("width", "400px");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect2.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_personal_alertas.php",
        function(data) {
            miselect2.empty();
            for (var i=0; i<data.length; i++) {
                    miselect2.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}

    ///////////////////////////////////////////////////////////////			
    // buscar en la tabla personal alertas.
    ///////////////////////////////////////////////////////////////
    function BuscarPersonalAlertas() {

        // 	lIMPIAR SECTION QUE CONTIENE EL PORTAFOLIO.
            $('#listaContenidoPersonalAlertas').empty();
            codigoPersonal = $("#txtcodigo").val();
        // Variables accion para guardar datos.
            accionAlertas = "BuscarAlertas";
        
        $.ajax({
            cache: false,
            type: "POST",
            dataType: "json",
            url:"php_libs/soporte/Personal/Alertas.php",
            data:"accionAlertas=" + accionAlertas + "&codigoPersonal=" + codigoPersonal,
            success: function(response){
                // Validar mensaje de error PORTAFOLIO.
                if(response.respuesta == false){
                    toastr["info"](response.mensaje, "Sistema");
                    $("#listaContenidoPersonalAlertas").empty(response.contenido);
                    $("#AlertPersonalAlertas").css("display", "block");
                    $("#AlertPersonalAlertas").css("alert", "alert-danger");
                    $("#TextoAlertPersonalAlertas").text("No hay Alertas.");
                    $("#alertas-tab").css("background-color", "#00ff00");
                }
                if(response.respuesta == true){
                    // Ver Alertas.
                    $("#listaContenidoPersonalAlertas").append(response.contenido);
                    toastr["warning"](response.mensaje, "Sistema");
                    $("#AlertPersonalAlertas").css("display", "block");
                    $("#TextoAlertPersonalAlertas").text("Alertas encontradas.");
                    $("#alertas-tab").css("background-color", "#F7DC6F");
                    }               
            },
        });
    }