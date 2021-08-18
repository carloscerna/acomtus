// id de user global
var id_ = 0;
var buscartodos = "";
var accionHistorial = 'noAccion';
var pagina = 1;
$(function(){ // INICIO DEL FUNCTION.
            // Escribir la fecha actual.
                var now = new Date();                
                var day = ("0" + now.getDate()).slice(-2);
                var month = ("0" + (now.getMonth() + 1)).slice(-2);
                var today = now.getFullYear()+"-"+(month)+"-"+(day) ;
				
				var day_M = ("20");
                var today_M = now.getFullYear()+"-"+(month)+"-"+(day_M) ;
                // ASIGNAR FECHA ACTUAL A LOS DATE CORRESPONDIENTES.
				$('#txtFechaHistorial').val(today);
//////////////////////////////////////////////////////////////////////////////////
/* INICIO DE LA FUNCION PARA MOSTRAR LOS DATOS DEL ALUMNO */
//////////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////
// Validar Formulario, para posteriormente Guardar o Modificarlo.
 //////////////////////////////////////////////////////
	$('#formHistorial').validate({
		ignore:"",
		rules:{
				txtFechaHistorial: {required: true,},
				TituloHistorial: {required: true,},
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
			var str = $('#formHistorial').serialize();
			var id_personal = 0;
			id_personal = $("#id_user").val();
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
		            url:"php_libs/soporte/NuevoEditarPersonalHistorial.php",
		            data:str + "&id_user=" + id_personal,
		            success: function(response){
		            	// Validar mensaje de error PORTAFOLIO.
		            	if(response.respuesta == false){
                            toastr["error"](response.mensaje, "Sistema");
		            	}
		            	else{
							toastr["success"](response.mensaje, "Sistema");
							$("#IdHistorial").val(response.id_historial);
                            }               
		            },
		        });
		    },
   });
// ventana modal. GENERAR NUEVO REGISTRO DEL PORTAFOLIO.
///////////////////////////////////////////////////////////////////////////////	  
$('#goNuevoHistorial').on( 'click', function () {
    // Variables accion para guardar datos.
        $("#accionHistorial").val("AgregarNuevo");
        $("#IdHistorial").val('0');
		$("label[for='LblHistorial']").text("Nuevo Contenido.");
    // Form Visible
        $("#EditarNuevoHistorial").css("display","block");
    // Historial Invisible
		$("#ListarHistorial").css("display","none");
	// Mostrar y Ocultar Botones.
		$("#goVerHistorial").css("display","block");		// Botón Ver
		$("#goNuevoHistorial").css("display","none");			// Botón Nuevo.
	// Pasar foco.
		$("#txtFechaHistorial").focus();
	// 	lIMPIAR SECTION QUE CONTIENE EL PORTAFOLIO.
		$('#ListarHistorial').empty();
});	  

// ventana modal. GENERAR NUEVO REGISTRO DEL PORTAFOLIO.
///////////////////////////////////////////////////////////////////////////////	  
$('#goVerHistorial').on( 'click', function () {
	//	LblHistorial.
		$("label[for='LblHistorial']").text("Historial");
    // Form Visible
        $("#EditarNuevoHistorial").css("display","none");
    // Historial Invisible
		$("#ListarHistorial").css("display","block");
	// Mostrar y Ocultar Botones.
		$("#goVerHistorial").css("display","none");		// Botón Ver
		$("#goNuevoHistorial").css("display","block");			// Botón Nuevo.
	//	LIMPIAR VARIABLES.
		$('#TituloHistorial').val('');
		$('#txtComentarioHistorial').val('');
	// 	VER PORTAFOLIO.
		VerHistorial();
});
// ventana modal. GENERAR NUEVO REGISTRO DEL PORTAFOLIO.
///////////////////////////////////////////////////////////////////////////////	  
// BLOQUE EXTRAER INFORMACIÓN DEL REGISTROS (año lectivo)
$('body').on('click','#ListarHistorial a',function (e){
	e.preventDefault();
// DATA-ACCION Y HREF
	Id_Editar_Eliminar = $(this).attr('href');
	accionHistorial = $(this).attr('data-accion');
	pagina = $(this).attr('href');
	//alert(Id_Editar_Eliminar+" "+accionHistorial);
// EDTIAR REGISTRO.
	if(accionHistorial  == 'EditarRegistro'){
		// ID PERSONAL
			accionHistorial = "BuscarIdHistorial";
		// DETARMINAR QUE SE VA EJECUTAR.	
			///////////////////////////////////////////////////////////////			
			// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
			///////////////////////////////////////////////////////////////
		// DETARMINAR QUE SE VA EJECUTAR.	
		$.post("php_libs/soporte/NuevoEditarPersonalHistorial.php",  {accionHistorial: accionHistorial, id_p_p: Id_Editar_Eliminar},
			function(data){
				$("#txtFechaHistorial").val(data[0].fecha);	
				$("#TituloHistorial").val(data[0].titulo);	
				$("#txtComentarioHistorial").val(data[0].descripcion);	
				$("#accionHistorial").val('EditarRegistro');
				$("#IdHistorial").val(data[0].id_);	
				var ruta_imagen = data[0].nombre_imagen
				// Cambiar imagen
				//$('.card-img-top-Historial').removeAttr('src');
				$('#ImagenHistorial').attr('src', ruta_imagen);
				//$("#").val(data[0].);	
				// Form Visible
				$("#EditarNuevoHistorial").css("display","block");
				// Historial Invisible
					$("#ListarHistorial").css("display","none");
				// Mostrar y Ocultar Botones.
					$("#goVerHistorial").css("display","block");		// Botón Ver
					$("#goNuevoHistorial").css("display","none");			// Botón Nuevo.
				// Desactivar botón subri imagen
					$("#fileupHistorial").attr("disabled",false);		// Botón Subir Imagen Historial
					$("#SubirImagenHistorial").attr("disabled",true);		// Botón Subir Imagen Historial
				// Limpiar EL PORTAFOLIO.
					$('#ListarHistorial').empty();
					toastr["success"]("Registro Encontrado", "Sistema");
				// Regresar valor de accionHistorial.
					accionHistorial = "EditarRegistro";
				//	LblHistorial.
					$("label[for='LblHistorial']").text("Editar Contenido.");
		}, "json");
	}else if(accionHistorial == "EliminarRegistro"){
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
				cache: false,
				type: "POST",
				dataType: "json",
				url:"php_libs/soporte/NuevoEditarPersonalHistorial.php",
				data: "id_p_p=" + Id_Editar_Eliminar + "&accionHistorial=" + accionHistorial,
				success: function(response){
					// Validar mensaje de error proporcionado por el response. contenido.
					if(response.respuesta == false){
						toastr["error"](response.mensaje, "Sistema");
					}
					else{
						toastr["info"](response.mensaje, "Sistema");
							VerHistorial();
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
	}else if(accionHistorial == "PaginacionHistorial"){
		// paginación buscar por cóidog Personal.
			VerHistorialPaginacion();
	}
});  
//************************************/
}); // fin de la funcion principal ************************************/
//************************************/		
// Pasar foco cuando seleccionar un encargado.
function PasarFoco()
   {
       $('#txtFechaHistorial').focus();
   }
function LimpiarCampos(){
	$('#TituloHistorial').val('');
	$('#txtComentarioHistorial').val('');
}
function VerHistorial() {
///////////////////////////////////////////////////////////////			
// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
///////////////////////////////////////////////////////////////
	// 	lIMPIAR SECTION QUE CONTIENE EL PORTAFOLIO.
		$('#ListarHistorial').empty();
	// Variables accion para guardar datos.
        accionHistorial = "BuscarPorCodigoPersonal";
	
	$.ajax({
		cache: false,
		type: "POST",
		dataType: "json",
		url:"php_libs/soporte/NuevoEditarPersonalHistorial.php",
		data:"accionHistorial=" + accionHistorial,
		success: function(response){
			// Validar mensaje de error PORTAFOLIO.
			if(response.respuesta == false){
				toastr["error"](response.mensaje, "Sistema");
				$("#ListarHistorial").append(response.contenido);
			}
			else{
				// Ver el Historial.
				$("#ListarHistorial").append(response.contenido);
				toastr["info"](response.mensaje, "Sistema");
				}               
		},
	});
}
function VerHistorialPaginacion() {
	///////////////////////////////////////////////////////////////			
	// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
	///////////////////////////////////////////////////////////////
		// 	lIMPIAR SECTION QUE CONTIENE EL PORTAFOLIO.
			$('#ListarHistorial').empty();
		// Variables accion para guardar datos.
			accionHistorial = "BuscarPorCodigoPersonal";
		// Ajax
		$.ajax({
			cache: false,
			type: "POST",
			dataType: "json",
			url:"php_libs/soporte/NuevoEditarPersonalHistorial.php",
			data:"accionHistorial=" + accionHistorial + "&page=" + pagina,
			success: function(response){
				// Validar mensaje de error PORTAFOLIO.
				if(response.respuesta == false){
					toastr["error"](response.mensaje, "Sistema");
					$("#ListarHistorial").append(response.contenido);
				}
				else{
					// Ver el Historial.
					$("#ListarHistorial").append(response.contenido);
					$('#ListarHistorial').fadeIn(2000);
					$('.pagination li').removeClass('active');
					$('.pagination li a[href="'+pagina+'"]').parent().addClass('active');
					}               
			},
		});
	}