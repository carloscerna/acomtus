// id de user global
var id_ = 0;
var codigo_personal = "";
var accionFianzaPrestamo = 'noAccion';
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
				$('#txtFecha').val(today);
//////////////////////////////////////////////////////////////////////////////////
/* INICIO DE LA FUNCION PARA MOSTRAR LOS DATOS DEL ALUMNO */
//////////////////////////////////////////////////////////////////////////////////

///////////////////////////////////////////////////////
// Validar Formulario, para posteriormente Guardar o Modificarlo.
 //////////////////////////////////////////////////////
$('#formFianzaPrestamo').validate({
		ignore:"",
		rules:{
				txtFecha: {required: true,},
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
			var str = $('#formFianzaPrestamo').serialize();
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
		            url:"php_libs/soporte/EditarFianzasPrestamos.php",
		            data: str,
		            success: function(response){
		            	// Validar mensaje de error PORTAFOLIO.
		            	if(response.respuesta == false){
                            toastr["error"](response.mensaje, "Sistema");
		            	}
		            	else{
							toastr["success"](response.mensaje, "Sistema");
						//	Actualizar Saldos.
							ActuaizarSaldos();
                            }               
		            },
		        });
		    },
});
// ventana modal. GENERAR NUEVO REGISTRO PARA LA DETERMINA QUE SE GUARDA LA FIANZA.
///////////////////////////////////////////////////////////////////////////////	  
$('#goNuevoFianza').on( 'click', function () {
	// Variables accion para guardar datos.
		if($("#goNuevoFianza").val()== "Fianza"){
			$("#accionFianzaPrestamo").val("AgregarFianza");	// accion
			$("label[for='LblFianzaPrestamo']").text("Fianza - Nuevo Registro...");	// Id
			$("#SpanFianzaPrestamo").text("Fianza -> $");					// Text fianza y prestamo
			$("#SpanDevolucionDescuento").text("Devolución -> $");					// Text devolución y descuento.
			$("#CodigoPersonal").val($("#LblCodigo").text());
		//	Cambiando Css de Heder y Footer.
			$("#CardHeader").css('background-color',"#b3e5fc");
			$("#CardFooter").css('background-color',"#b3e5fc");
			//$("#CardFooter").css("opacity", 0.5);
			PasarFoco();
			LimpiarCampos();
		}
    // Form Visible
        $("#EditarNuevoFianzaPrestamo").css("display","block");
    // Fianza Invisible
		$("#ListarFianzaPrestamo").css("display","none");
	// Mostrar y Ocultar Botones.
		$("#goNuevoPrestamo").css("display","block");		// Botón Nuevo
		$("#goNuevoFianza").css("display","none");			// Botón Nuevo.
	// Pasar foco.
		$("#txtFecha").focus();
	// 	lIMPIAR SECTION QUE CONTIENE EL PORTAFOLIO.
		$('#ListarFianza').empty();
});	  
// ventana modal. GENERAR NUEVO REGISTRO DEL PORTAFOLIO.
///////////////////////////////////////////////////////////////////////////////	  
$('#goVerFianza').on( 'click', function () {
	//	LblFianza.
		$("label[for='LblFianza']").text("Fianza");
    // Form Visible
        $("#EditarNuevoFianza").css("display","none");
    // Fianza Invisible
		$("#ListarFianzaPrestamo").css("display","block");
	// Mostrar y Ocultar Botones.
		$("#goVerFianza").css("display","none");		// Botón Ver
		$("#goNuevoFianza").css("display","block");			// Botón Nuevo.
	//	LIMPIAR VARIABLES.
		$('#TituloFianza').val('');
		$('#txtComentarioFianza').val('');
	// 	VER PORTAFOLIO.
		VerFianzaPrestamo();
});
// ventana modal. GENERAR NUEVO REGISTRO PARA LA DETERMINA QUE SE GUARDA LA FIANZA.
///////////////////////////////////////////////////////////////////////////////	  
$('#goNuevoPrestamo').on( 'click', function () {
	// Variables accion para guardar datos.
		if($("#goNuevoPrestamo").val()== "Prestamo"){
			$("#accionFianzaPrestamo").val("AgregarPrestamo");	// accion
			$("label[for='LblFianzaPrestamo']").text("Prestamo - Nuevo Registro...");	// Id
			$("#CodigoPersonal").val($("#LblCodigo").text());
			$("#SpanFianzaPrestamo").text("Prestamo -> $");					// Text fianza y prestamo
			$("#SpanDevolucionDescuento").text("Descuento -> $");					// Text devolución y descuento.

		//	Cambiando Css de Heder y Footer.
			$("#CardHeader").css('background-color',"#c8e6c9");
			$("#CardFooter").css('background-color',"#c8e6c9");
			//$("#CardFooter").css("opacity", 0.5);
			PasarFoco();
			LimpiarCampos();
		}
    // Form Visible
        $("#EditarNuevoFianzaPrestamo").css("display","block");
    // Fianza Invisible
		$("#ListarFianzaPrestamo").css("display","none");
	// Mostrar y Ocultar Botones.
		$("#goNuevoPrestamo").css("display","none");		// Botón Nuevo
		$("#goNuevoFianza").css("display","block");			// Botón Nuevo.
	// Pasar foco.
		$("#txtFecha").focus();
	// 	lIMPIAR SECTION QUE CONTIENE EL PORTAFOLIO.
		$('#ListarFianza').empty();
});	 
// ventana modal. GENERAR NUEVO REGISTRO PARA LA DETERMINA QUE SE GUARDA LA FIANZA.
///////////////////////////////////////////////////////////////////////////////	  
$('#goGuardar').on( 'click', function () {
	$("#formFianzaPrestamo").submit();
});	 
// ventana modal. GENERAR NUEVO REGISTRO DEL PORTAFOLIO.
///////////////////////////////////////////////////////////////////////////////	  
// BLOQUE EXTRAER INFORMACIÓN DEL REGISTROS (SALDOS)
$('body').on('click','#InfoBoxFianzaPrestamo a',function (e){
	e.preventDefault();
// DATA-ACCION Y HREF
	codigo_personal = $("#LblCodigo").text();
	accionFianzaPrestamo = $(this).attr('data-accion');
	pagina = $(this).attr('href');
	//alert(codigo_personal+" "+accionFianzaPrestamo);
// EDTIAR REGISTRO.
	if(accionFianzaPrestamo  == 'BuscarTodosFianza'){
		//	Registros correspondietnes a Fianza.
			VerFianzaPrestamo();
	}else if(accionFianzaPrestamo  == 'BuscarTodosPrestamo'){
		//	Registros correspondietnes a Prestamo.
			VerFianzaPrestamo();
	}else if(accionFianzaPrestamo  == 'ImprimirFianza'){
		//	Registros correspondietnes a Prestamo.
			//alert(codigo_personal+" "+accionFianzaPrestamo);
			varenviar = "/acomtus/php_libs/reportes/fianzas.php?codigo_personal="+codigo_personal;
		// Ejecutar la función abre otra pestaña.
		 	AbrirVentana(varenviar);                        
	}else if(accionFianzaPrestamo  == 'ImprimirPrestamo'){
		//	Registros correspondietnes a Prestamo.
			//alert(codigo_personal+" "+accionFianzaPrestamo);
			varenviar = "/acomtus/php_libs/reportes/prestamos.php?codigo_personal="+codigo_personal;
		// Ejecutar la función abre otra pestaña.
		 	AbrirVentana(varenviar);             
	}
});  

// BLOQUE EXTRAER INFORMACIÓN DEL REGISTROS (LISTADO QUE PROVIENE DE FIANZAS Y PRESTAMOS)
$('body').on('click','#ListarFianzaPrestamo a',function (e){
	e.preventDefault();
	pagina = $(this).attr('href');
	accionFianzaPrestamo = $(this).attr('data-accion');
	//alert(pagina + " " + accionFianzaPrestamo);
// EDTIAR REGISTRO.
	if(accionFianzaPrestamo  == 'BuscarPorIdFianza'){
		Id_Editar_Eliminar = $(this).attr('href');
		//alert(pagina + " " + accionFianzaPrestamo);
		LimpiarCampos();
		///////////////////////////////////////////////////////////////			
		// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
		///////////////////////////////////////////////////////////////
		// DETARMINAR QUE SE VA EJECUTAR.	
		$.post("php_libs/soporte/EditarFianzasPrestamos.php",  {accionFianzaPrestamo: accionFianzaPrestamo, id_p_p: Id_Editar_Eliminar},
			function(data){
				$("#txtFecha").val(data[0].fecha);	
				$("#FianzaPrestamo").val(data[0].fianzaprestamo);	
				$("#DevolucionDescuento").val(data[0].devoluciondescuento);	
				$("#Descripcion").val(data[0].descripcion);	
			// Variables accion para guardar datos.
				$("#IdFianzaPrestamo").val(data[0].id_);	// accionFianzasPrestamos.
				$("#accionFianzaPrestamo").val("ActualizarFianza");	// accionFianzasPrestamos.
				$("label[for='LblFianzaPrestamo']").text("Fianza - Actualización de Datos...");	// Id
				$("#SpanFianzaPrestamo").text("Fianza -> $");					// Text fianza y prestamo
				$("#SpanDevolucionDescuento").text("Devolución -> $");					// Text devolución y descuento.
				$("#CodigoPersonal").val($("#LblCodigo").text());
			//	Cambiando Css de Heder y Footer.
				$("#CardHeader").css('background-color',"#b3e5fc");
				$("#CardFooter").css('background-color',"#b3e5fc");
					PasarFoco();
			// Form Visible
				$("#EditarNuevoFianzaPrestamo").css("display","block");
			// Fianza Invisible
				$("#ListarFianzaPrestamo").css("display","none");
			// Mostrar y Ocultar Botones.
				$("#goNuevoPrestamo").css("display","block");		// Botón Nuevo
				$("#goNuevoFianza").css("display","none");			// Botón Nuevo.
			// Pasar foco.
				$("#txtFecha").focus();
			// 	lIMPIAR SECTION QUE CONTIENE EL PORTAFOLIO.
				$('#ListarFianza').empty();
		}, "json");
	}else if(accionFianzaPrestamo  == 'BuscarPorIdPrestamo'){
			Id_Editar_Eliminar = $(this).attr('href');
		//	alert(pagina + " " + accionFianzaPrestamo);
			LimpiarCampos();
			///////////////////////////////////////////////////////////////			
			// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
			///////////////////////////////////////////////////////////////
			// DETARMINAR QUE SE VA EJECUTAR.	
			$.post("php_libs/soporte/EditarFianzasPrestamos.php",  {accionFianzaPrestamo: accionFianzaPrestamo, id_p_p: Id_Editar_Eliminar},
				function(data){
					$("#txtFecha").val(data[0].fecha);	
					$("#FianzaPrestamo").val(data[0].fianzaprestamo);	
					$("#DevolucionDescuento").val(data[0].devoluciondescuento);	
					$("#Descripcion").val(data[0].descripcion);	
				// Variables accion para guardar datos.
					$("#IdFianzaPrestamo").val(data[0].id_);	// accionFianzasPrestamos.
					$("#accionFianzaPrestamo").val("ActualizarPrestamo");	// accion
					$("label[for='LblFianzaPrestamo']").text("Prestamo - Actualizar Registro...");	// Id
					$("#SpanFianzaPrestamo").text("Prestamo -> $");					// Text fianza y prestamo
					$("#SpanDevolucionDescuento").text("Descuento -> $");					// Text devolución y descuento.
					$("#CodigoPersonal").val($("#LblCodigo").text());
				//	Cambiando Css de Heder y Footer.
					$("#CardHeader").css('background-color',"#c8e6c9");
					$("#CardFooter").css('background-color',"#c8e6c9");
					//$("#CardFooter").css("opacity", 0.5);
						PasarFoco();
				// Form Visible
					$("#EditarNuevoFianzaPrestamo").css("display","block");
				// Fianza Invisible
					$("#ListarFianzaPrestamo").css("display","none");
				// Mostrar y Ocultar Botones.
					$("#goNuevoPrestamo").css("display","none");		// Botón Nuevo
					$("#goNuevoFianza").css("display","block");			// Botón Nuevo.
				// Pasar foco.
					$("#txtFecha").focus();
				// 	lIMPIAR SECTION QUE CONTIENE EL PORTAFOLIO.
					$('#ListarFianza').empty();
			}, "json");
	}else if(accionFianzaPrestamo == "EliminarRegistroFianza"){
		Id_Editar_Eliminar = $(this).attr('href');
			//alert(pagina + " " + accionFianzaPrestamo);
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
				url:"php_libs/soporte/EditarFianzasPrestamos.php",
				data: "id_p_p=" + Id_Editar_Eliminar + "&accionFianzaPrestamo=" + accionFianzaPrestamo,
				success: function(response){
					// Validar mensaje de error proporcionado por el response. contenido.
					if(response.respuesta == false){
						toastr["error"](response.mensaje, "Sistema");
					}
					else{
						toastr["info"](response.mensaje, "Sistema");
							$('#accionFianzaPrestamo').val('BuscarTodosFianza');
							accionFianzaPrestamo = $('#accionFianzaPrestamo').val();
								ActuaizarSaldos();
								VerFianzaPrestamo();
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
	}else if(accionFianzaPrestamo == "EliminarRegistroPrestamo"){
		Id_Editar_Eliminar = $(this).attr('href');
			//alert(pagina + " " + accionFianzaPrestamo);
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
				url:"php_libs/soporte/EditarFianzasPrestamos.php",
				data: "id_p_p=" + Id_Editar_Eliminar + "&accionFianzaPrestamo=" + accionFianzaPrestamo,
				success: function(response){
					// Validar mensaje de error proporcionado por el response. contenido.
					if(response.respuesta == false){
						toastr["error"](response.mensaje, "Sistema");
					}
					else{
						toastr["info"](response.mensaje, "Sistema");
							$('#accionFianzaPrestamo').val('BuscarTodosFianza');
							accionFianzaPrestamo = $('#accionFianzaPrestamo').val();
								ActuaizarSaldos();
								VerFianzaPrestamo();
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
	}else if(accionFianzaPrestamo == "BuscarTodosFianza" || accionFianzaPrestamo == "BuscarTodosPrestamo"){
		// paginación buscar por cóidog Personal.
		// 	lIMPIAR SECTION QUE CONTIENE EL PORTAFOLIO.
			$('#ListarFianzaPrestamo').empty();
			VerFianzaPaginacion();
	}
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
       $('#txtFecha').focus();
   }
function LimpiarCampos(){
	$('#FianzaPrestamo').val('0');
	$('#DevolucionDescuento').val('0');
	$('#Descripcion').val('');
}
function VerFianzaPrestamo() {
	// Oculta o muestra dstos dependiendo si es Fianza o Prestamo.
	if(accionFianzaPrestamo  == 'BuscarTodosFianza'){
		// Form Visible
			$("#EditarNuevoFianzaPrestamo").css("display","none");
		// Fianza Invisible
			$("#ListarFianzaPrestamo").css("display","block");
		// Mostrar y Ocultar Botones.
			$("#goNuevoPrestamo").css("display","block");		// Botón Nuevo
			$("#goNuevoFianza").css("display","block");			// Botón Nuevo.
		// 	lIMPIAR SECTION QUE CONTIENE EL PORTAFOLIO.
			$('#ListarFianzaPrestamo').empty();
			//$("#ListarFianzaPrestamo").append(codigo_personal + " " + accionFianzaPrestamo);
	}
	if(accionFianzaPrestamo  == 'BuscarTodosPrestamo'){
		// Form Visible
			$("#EditarNuevoFianzaPrestamo").css("display","none");
		// Fianza Invisible
			$("#ListarFianzaPrestamo").css("display","block");
		// Mostrar y Ocultar Botones.
			$("#goNuevoPrestamo").css("display","block");		// Botón Nuevo
			$("#goNuevoFianza").css("display","block");			// Botón Nuevo.
		// 	lIMPIAR SECTION QUE CONTIENE EL PORTAFOLIO.
			$('#ListarFianzaPrestamo').empty();
			//$("#ListarFianzaPrestamo").append(codigo_personal + " " + accionFianzaPrestamo);
	}
	// Ejecutar en el caso de Fianza o Prestamo.
	$.ajax({
		cache: false,
		type: "POST",
		dataType: "json",
		url:"php_libs/soporte/EditarFianzasPrestamos.php",
		data:"accionFianzaPrestamo=" + accionFianzaPrestamo + "&codigo_personal=" + codigo_personal,
		success: function(response){
			// Validar mensaje de error PORTAFOLIO.
			if(response.respuesta == false){
				toastr["error"](response.mensaje, "Sistema");
				$("#ListarFianzaPrestamo").append(response.contenido);
			}
			else{
				// Ver el Fianza.
				$("#ListarFianzaPrestamo").append(response.contenido);
				toastr["info"](response.mensaje, "Sistema");
				}               
		},
	});
}
function VerFianzaPaginacion() {
		$.ajax({
			cache: false,
			type: "POST",
			dataType: "json",
			url:"php_libs/soporte/EditarFianzasPrestamos.php",
			data:"accionFianzaPrestamo=" + accionFianzaPrestamo + "&codigo_personal=" + codigo_personal + "&page=" + pagina,
			success: function(response){
				// Validar mensaje de error PORTAFOLIO.
				if(response.respuesta == false){
					toastr["error"](response.mensaje, "Sistema");
					$("#ListarFianzaPrestamo").append(response.contenido);
				}
				else{
					// Ver el Fianza.
					$("#ListarFianzaPrestamo").append(response.contenido);
					$('#ListarFianzaPrestamo').fadeIn(2000);
					$('.pagination li').removeClass('active');
					$('.pagination li a[href="'+pagina+'"]').parent().addClass('active');
					}               
			},
		});
}
function ActuaizarSaldos() {
	id_personal = $("#id_personal").val();
    accion = $("#accion_buscar").val();
    $.post("php_libs/soporte/EditarFianzasPrestamos.php",  { id_personal: id_personal, accion: accion },
    function(data){
    // Cargar valores a los objetos Llenar el formulario con los datos del registro seleccionado.
        $("label[for='LblFianza']").text('$ ' + data[0].saldo_fianza);
		$("label[for='LblPrestamo']").text('$ ' + data[0].saldo_prestamo);
	}, "json");     
}