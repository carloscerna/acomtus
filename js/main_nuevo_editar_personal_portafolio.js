// id de user global
var id_ = 0;
var buscartodos = "";
var accionPortafolio = 'noAccion';
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
				$('#txtFechaPortafolio').val(today);
//////////////////////////////////////////////////////////////////////////////////
/* INICIO DE LA FUNCION PARA MOSTRAR LOS DATOS DEL ALUMNO */
//////////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////
// Validar Formulario, para posteriormente Guardar o Modificarlo.
 //////////////////////////////////////////////////////
	$('#formPortafolio').validate({
		ignore:"",
		rules:{
				txtFechaPortafolio: {required: true,},
				TituloPortafolio: {required: true,},
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
			var str = $('#formPortafolio').serialize();
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
		            url:"php_libs/soporte/NuevoEditarPersonalPortafolio.php",
		            data:str + "&id_user=" + id_personal,
		            success: function(response){
		            	// Validar mensaje de error PORTAFOLIO.
		            	if(response.respuesta == false){
                            toastr["error"](response.mensaje, "Sistema");
		            	}
		            	else{
							toastr["success"](response.mensaje, "Sistema");
							$("#fileupPortafolio").attr("disabled",false);		// Botón Subir Imagen Portafolio
							$("#SubirImagenPortafolio").attr("disabled",false);		// Botón Subir Imagen Portafolio
							$("#IdPortafolio").val(response.id_portafolio);
                            }               
		            },
		        });
		    },
   });
// ventana modal. GENERAR NUEVO REGISTRO DEL PORTAFOLIO.
///////////////////////////////////////////////////////////////////////////////	  
$('#goNuevoPortafolio').on( 'click', function () {
    // Variables accion para guardar datos.
		$("#accionPortafolio").val( "AgregarNuevo");
		$("label[for='LblPortafolio']").text("Nuevo Contenido.");
    // Form Visible
        $("#EditarNuevoPortafolio").css("display","block");
    // Portafolio Invisible
		$("#ListarPortafolio").css("display","none");
	// Mostrar y Ocultar Botones.
		$("#goVerPortafolio").css("display","block");		// Botón Ver
		$("#goNuevoPortafolio").css("display","none");			// Botón Nuevo.
	// Pasar foco.
		$("#txtFechaPortafolio").focus();
	// Desactivar botón subri imagen
		$("#fileupPortafolio").attr("disabled",true);		// Botón Subir Imagen Portafolio
		$("#SubirImagenPortafolio").attr("disabled",true);		// Botón Subir Imagen Portafolio
	// 	lIMPIAR SECTION QUE CONTIENE EL PORTAFOLIO.
		$('#ListarPortafolio').empty();
	//	ELIMINAR SCR DE LA IMAGEN
		$('.card-img-top-Portafolio').attr('src','../acomtus/img/NoDisponible.jpg');
});	  

// ventana modal. GENERAR NUEVO REGISTRO DEL PORTAFOLIO.
///////////////////////////////////////////////////////////////////////////////	  
$('#goVerPortafolio').on( 'click', function () {
	//	LblPortafolio.
		$("label[for='LblPortafolio']").text("Portafolio");
    // Form Visible
        $("#EditarNuevoPortafolio").css("display","none");
    // Portafolio Invisible
		$("#ListarPortafolio").css("display","block");
	// Mostrar y Ocultar Botones.
		$("#goVerPortafolio").css("display","none");		// Botón Ver
		$("#goNuevoPortafolio").css("display","block");			// Botón Nuevo.
	//	LIMPIAR VARIABLES.
		$('#TituloPortafolio').val('');
		$('#txtComentarioPortafolio').val('');
	// 	VER PORTAFOLIO.
		VerPortafolio();
	//	ELIMINAR SCR DE LA IMAGEN
		$('.card-img-top-Portafolio').attr('src','../acomtus/img/NoDisponible.jpg');
	//	Limpiar namefilePortafolio
		$('#namefilePortafolio').html("<p>Sólo Imagénes!</p>");
});
// ventana modal. GENERAR NUEVO REGISTRO DEL PORTAFOLIO.
///////////////////////////////////////////////////////////////////////////////	  
// BLOQUE EXTRAER INFORMACIÓN DEL REGISTROS (año lectivo)
$('body').on('click','#ListarPortafolio a',function (e){
	e.preventDefault();
// DATA-ACCION Y HREF
	Id_Editar_Eliminar = $(this).attr('href');
	accionPortafolio = $(this).attr('data-accion');
	pagina = $(this).attr('href');
	//alert(Id_Editar_Eliminar+" "+accionPortafolio);
// EDTIAR REGISTRO.
	if(accionPortafolio  == 'EditarRegistro'){
		// ID PERSONAL
			accionPortafolio = "BuscarIdPortafolio";
		// DETARMINAR QUE SE VA EJECUTAR.	
			///////////////////////////////////////////////////////////////			
			// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
			///////////////////////////////////////////////////////////////
		// DETARMINAR QUE SE VA EJECUTAR.	
		$.post("php_libs/soporte/NuevoEditarPersonalPortafolio.php",  {accionPortafolio: accionPortafolio, id_p_p: Id_Editar_Eliminar},
			function(data){
				$("#txtFechaPortafolio").val(data[0].fecha);	
				$("#TituloPortafolio").val(data[0].titulo);	
				$("#txtComentarioPortafolio").val(data[0].descripcion);	
				$("#accionPortafolio").val('EditarRegistro');
				$("#IdPortafolio").val(data[0].id_);	
				var ruta_imagen = data[0].nombre_imagen
				// Cambiar imagen
				//$('.card-img-top-Portafolio').removeAttr('src');
				$('#ImagenPortafolio').attr('src', ruta_imagen);
				//$("#").val(data[0].);	
				// Form Visible
				$("#EditarNuevoPortafolio").css("display","block");
				// Portafolio Invisible
					$("#ListarPortafolio").css("display","none");
				// Mostrar y Ocultar Botones.
					$("#goVerPortafolio").css("display","block");		// Botón Ver
					$("#goNuevoPortafolio").css("display","none");			// Botón Nuevo.
				// Desactivar botón subri imagen
					$("#fileupPortafolio").attr("disabled",false);		// Botón Subir Imagen Portafolio
					$("#SubirImagenPortafolio").attr("disabled",true);		// Botón Subir Imagen Portafolio
				// Limpiar EL PORTAFOLIO.
					$('#ListarPortafolio').empty();
					toastr["success"]("Registro Encontrado", "Sistema");
				// Regresar valor de accionPortafolio.
					accionPortafolio = "EditarRegistro";
				//	LblPortafolio.
					$("label[for='LblPortafolio']").text("Editar Contenido.");
		}, "json");
	}else if(accionPortafolio == "EliminarRegistro"){
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
				url:"php_libs/soporte/NuevoEditarPersonalPortafolio.php",
				data: "id_p_p=" + Id_Editar_Eliminar + "&accionPortafolio=" + accionPortafolio,
				success: function(response){
					// Validar mensaje de error proporcionado por el response. contenido.
					if(response.respuesta == false){
						toastr["error"](response.mensaje, "Sistema");
					}
					else{
						toastr["info"](response.mensaje, "Sistema");
							VerPortafolio();
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
/* */
 /* SCRIPT PARA SUBIR LA FOTO.*/
 /* */
 	$('#fileupPortafolio').change(function(){
  //here we take the file extension and set an array of valid extensions
	  var res=$('#fileupPortafolio').val();
	  var arr = res.split("\\");
	  var filename=arr.slice(-1)[0];
	  filextension=filename.split(".");
	  filext="."+filextension.slice(-1)[0];
	  valid=[".jpg",".png",".jpeg",".bmp"];
  //if file is not valid we show the error icon, the red alert, and hide the submit button
	  if (valid.indexOf(filext.toLowerCase())==-1){
		  $( ".imguploadPortafolio.ok" ).hide("slow");
		  $( ".imguploadPortafolio.stop" ).show("slow");
		
		  $('#namefilePortafolio').css({"color":"red","font-weight":700});
		  $('#namefilePortafolio').html("No es una Imagen!");
	  }else{
		  //if file is valid we show the green alert and show the valid submit
		  $( ".imguploadPortafolio.stop" ).hide("slow");
		  $( ".imguploadPortafolio.ok" ).show("slow");
		
		  $('#namefilePortafolio').css({"color":"green","font-weight":700});
		  $('#namefilePortafolio').html('Imagen!');

		  $("#SubirImagenPortafolio").attr("disabled",false);		// Botón Subir Imagen Portafolio
	  }
  });
  // CARGAR DEL ARCHIVO A LA TABLA CORRESPONDIENTE.
	  $(".uploadPortafolio").on('click', function() {
		  var formData = new FormData();
		  var files = $('#fileupPortafolio')[0].files[0];
		  id_portafolio = $("#IdPortafolio").val();
		  formData.append('file',files);
		  $.ajax({
			  url: 'php_libs/soporte/upload_foto_personal_portafolio.php',
			  type: 'post',
			  data: formData,
			  contentType: false,
			  processData: false,
			  success: function(response) {
				  if (response != 0) {
					  //$('.card-img-top-Portafolio').removeAttr('src');
					  $(".card-img-top-Portafolio").attr("src", response);
					  toastr["success"]('Imagen Cargada...', "Sistema");
				  } else {
					toastr["error"]('Formato de imagen incorrecto.', "Sistema");
				  }
			  }
		  });
		  return false;
	  });	  
//************************************/
}); // fin de la funcion principal ************************************/
//************************************/		
// Pasar foco cuando seleccionar un encargado.
function PasarFoco()
   {
       $('#txtFechaPortafolio').focus();
   }
function LimpiarCampos(){
	$('#TituloPortafolio').val('');
	$('#txtComentarioPortafolio').val('');
}
function VerPortafolio() {
///////////////////////////////////////////////////////////////			
// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
///////////////////////////////////////////////////////////////
	// 	lIMPIAR SECTION QUE CONTIENE EL PORTAFOLIO.
		$('#ListarPortafolio').empty();
	// Variables accion para guardar datos.
        accionPortafolio = "BuscarPorCodigoPersonal";
	
	$.ajax({
		cache: false,
		type: "POST",
		dataType: "json",
		url:"php_libs/soporte/NuevoEditarPersonalPortafolio.php",
		data:"accionPortafolio=" + accionPortafolio,
		success: function(response){
			// Validar mensaje de error PORTAFOLIO.
			if(response.respuesta == false){
				toastr["error"](response.mensaje, "Sistema");
				$("#ListarPortafolio").append(response.contenido);
			}
			else{
				// Ver el Portafolio.
				$("#ListarPortafolio").append(response.contenido);
				toastr["info"](response.mensaje, "Sistema");
				}               
		},
	});
}
function VerPortafolioPaginacion() {
	///////////////////////////////////////////////////////////////			
	// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
	///////////////////////////////////////////////////////////////
		// 	lIMPIAR SECTION QUE CONTIENE EL PORTAFOLIO.
			$('#ListarPortafolio').empty();
		// Variables accion para guardar datos.
			accionPortafolio = "BuscarPorCodigoPersonal";
		// Ajax
		$.ajax({
			cache: false,
			type: "POST",
			dataType: "json",
			url:"php_libs/soporte/NuevoEditarPersonalPortafolio.php",
			data:"accionPortafolio=" + accionPortafolio + "&page=" + pagina,
			success: function(response){
				// Validar mensaje de error PORTAFOLIO.
				if(response.respuesta == false){
					toastr["error"](response.mensaje, "Sistema");
					$("#ListarPortafolio").append(response.contenido);
				}
				else{
					// Ver el Portafolio.
					$("#ListarPortafolio").append(response.contenido);
					$('#ListarPortafolio').fadeIn(2000);
					$('.pagination li').removeClass('active');
					$('.pagination li a[href="'+pagina+'"]').parent().addClass('active');
					}               
			},
		});
	}