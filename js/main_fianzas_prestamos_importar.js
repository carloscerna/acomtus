//Iniciamos nuestra función jquery.
$(function(){
// funcionalidad del botón que actualiza el directorio file.                
	$('#goActualizarDirectorio').on('click',function(){
		// Elminar mensaje de Actualizar Archivo.
		$('#MensajeImportar').empty();
		$.post("includes/cargar-nombre-archivos.php",
				function(data) {
					$('#listaArchivosOK').empty();                                  
					var filas = data.length;                                  
													  
					if (filas != 0 ) {                                  
							for (fila=0;fila<filas;fila++) {                                  
									$('#listaArchivosOK').append(data[fila].archivo);                                  
							}                                                                                  
					}else{                                  
							$('#listaArchivosOK').append(data[fila].archivo);                                  
					}                                  
			}, "json");
		toastr.info("Directorio Actualizado.");
	});
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	// ACTIVAR Y DESACTIVAR CHECKBOX DE LA TABLA.
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$("#checkBoxAll").on("change", function () {
		$("#listadoContenido tbody input[type='checkbox'].case").prop("checked", this.checked);
	});
	
	$("#listadoContenido tbody").on("change", "input[type='checkbox'].case", function () {
	  if ($("#listadoContenido tbody input[type='checkbox'].case").length == $("#listadoContenido tbody input[type='checkbox'].case:checked").length) {
		  $("#checkBoxAll").prop("checked", true);
	  } else {
		  $("#checkBoxAll").prop("checked", false);
	  }
	 });		
// GO GUARDAR PARA ALMACENAR DATOS FINALES EN LA TABLA FIANZAS O PRESTAMO
$('#goGuardar').on('click',function(){
	// Validar la descripcion que no esté vacía.
		var descripcion = $("#txtdescripcion").val();
		if($("#txtdescripcion").val().trim().length < 1){
			toastr["error"]("Descripción está vacía.", "Sistema");
			return;				
		}
	// Validar si está seleccionada Fianza y Prestamos
	valor_check = $('input:radio[name=customHoja]:checked').val();
		if(valor_check == "Fianzas"){
			var descripcion = $("#txtdescripcion").val();
			var agregarFianzaPrestamo = "AgregarFianzas";
		}

		if(valor_check == "Prestamos"){
			var agregarFianzaPrestamo = "AgregarPrestamos";
		}
	if(valor_check == undefined){
		toastr.error(":( Debe Seleccionar Opción Fianzas o Prestamos.");
		return;
	}
	// LEER EL VALOR DE LA TABLA ACTUAL CHECKBO BOX 
// Información de la Página 1.                               
var $objCuerpoTabla=$("#listadoContenido").children().prev().parent();          
var checkbox_ = []; 
		  
var fila = 0;          
// recorre el contenido de la tabla.
	$objCuerpoTabla.find("tbody tr").each(function(){
		// checkbox
		var chk =$(this).find('td').eq(0).find('input[type="checkbox"]').is(':checked');
		console.log(chk);
		// dar valor a las arrays.
			checkbox_[fila] = chk;            
				fila = fila + 1;            
	});
	// Validar si la Tabla fianzas_prestamos_importar tiene datos.
	// Comenzar el proceso del AJAX PARA REVISAR SI LA TABLA FIANZAS_PRESTAMOS_IMPORTAR TIENE DATOS CORRECTOS PARA ACTUALIZAR.
		url_archivo = "php_libs/soporte/FianzasPrestamosImportar.php";
				// mostra rel modal. que contiene el mensaje del nombre del archivo y mensajes de veririvación o actualización.
				$('#myModal').modal('show');
				$("label[for='NombreArchivo']").text(agregarFianzaPrestamo);
		$.ajax({
			cache: false,		
			type: "POST",		
			dataType: "json",		
			url: url_archivo,		
			data: "valor_check=" + valor_check  + "&accionFianzaPrestamo=" + agregarFianzaPrestamo + "&descripcion=" + descripcion + "&checkbox=" + checkbox_ + "&id=" + Math.random() + "&fila=" + fila,		
			success: function(response){		
				// validar GUARDAR REGISTROS
				if (response.respuesta == true) {		
					toastr["error"](response.mensaje, "Sistema");
					$('#MensajeImportar').empty();
					$('#MensajeImportar').append("<label 'class=text-black bg-default'>Archivo Actualizado: "+response.contenido+"</label>");

					$("#listaContenidoOk").empty();

					// Elminar mensaje de Actualizar Archivo.
					toastr["info"](response.mensaje, "Sistema");
					}		
					if (response.respuesta == false) {		
						toastr["error"](response.mensaje, "Sistema");
						$('#MensajeImportar').empty();
						//$('#MensajeImportar').append("<label 'class=text-black bg-default'>Archivo Actualizado: "+response.contenido+"</label>");
	
						$("#listaContenidoOk").empty();
						$("#listaContenidoOk").append(response.contenido);
					}
			},		
			error:function(){		
				toastr.error(":(");		
			}		
		}); // Cierre de Ajax. QUE TIENE EL NOMBRE DEL ARCHIVO A ACTUALIZAR.
});	

// ***************************************************************************************************                
// LLAMAR AL ARCHIVO IMPORTAR HOJA DE CALCULO PARA QUE ACTUALICE LAS NOTAS SEGÚN PERIODO O TRIMESTRE.                
// **************************************************************************************************                
$('body').on('click','#listaArchivosOK a',function (e)                
{                
   // estas dos lineas no cambian                       
	e.preventDefault();                       
	accion_ok = $(this).attr('data-accion');
	valor_check = $('input:radio[name=customHoja]:checked').val();
   // obtener el valor del nombre del archivo.
    var url_archivo = "xxx";
    var url_archivo_data = false;
	var nombre_archivo = $(this).parent().parent().children('td:eq(0)').text();                       
   // condicionar si existe selecciona periodo o trimestre.                      
   // Al seleccionar dentro de la tabla.
    if($(this).attr('data-accion') == 'goBuscarOk'){
		// si no ha se seleccionada nada.
		if(valor_check == undefined){
			toastr.error(":( Debe Seleccionar Opción Fianzas o Prestamos.");
			return;
		}
		// mostra rel modal. que contiene el mensaje del nombre del archivo y mensajes de veririvación o actualización.
		$('#myModal').modal('show');
		// valores a la consola
			console.log("valor: " + valor_check + " Archivo: " + nombre_archivo);
			$("label[for='NombreArchivo']").text(nombre_archivo);
			$("label[for='VerificarActualizar']").text("Verificando...");
		/*/
			CAMBIAR LA URL DEL ARCHIVO
		*/
		// importar fianzas y prestamos
		if(valor_check == "Fianzas"){
			url_archivo = "includes/importar_fianzas_prestamos_hoja_calculo.php";
		}
		//
		if(valor_check == "Prestamos"){
			url_archivo = "includes/importar_fianzas_prestamos_hoja_calculo.php";
		}
		/*
		 *VERIFICAR ARCHIVOS ANTES DE INICAR LA ACTUALIZACIÓN.
		 */
		if(valor_check == "Fianzas" || valor_check == "Prestamos"){
			$.ajax({
				cache: false,		
				type: "POST",		
				dataType: "json",		
				url: "includes/verificar_importar_hoja_calculo.php",		
				data: "nombre_archivo_=" + nombre_archivo + "&valor_check=" + valor_check + "&id=" +Math.random(),		
				success: function(data){		
				// validar		
					if (data[0].registro == "No_registro") {		
						$("#listaContenidoOk").empty();
						toastr.error("Archivo Incorrecto para " + data[0].mensaje);
						url_archivo_data = false;
						return;
					}
					
					if (data[0].registro == "Si_registro") {		
						$("label[for='VerificarActualizar']").text("Actualizando...");
						$("#imagenGif").attr("src","img/ajax-loader.gif");
						url_archivo_data = true;
						console.log(url_archivo_data);
						
						// Comenzar el proceso del AJAX PARA EL NUEVO ARCHIVO.
						// alert(url_archivo);
							$.ajax({
								cache: false,		
								type: "POST",		
								dataType: "json",		
								url: url_archivo,		
								data: "nombre_archivo_=" + nombre_archivo + "&valor_check=" + valor_check  + "&id=" + Math.random(),		
								success: function(response){		
								// validar		
									if (response.respuesta == true) {		
										toastr["info"](response.mensaje, "Sistema");
										$('#MensajeImportar').empty();
                                        $('#MensajeImportar').append("<label 'class=text-black bg-default'>Archivo Actualizado: "+response.nombre_archivo+"</label>");

										$("#listaContenidoOk").empty();
										$("#listaContenidoOk").append(response.contenido);
									}
									if (response.respuesta == false) {		
										toastr["error"](response.mensaje, "Sistema");
										$('#MensajeImportar').empty();
										//$('#MensajeImportar').append("<label 'class=text-black bg-default'>Archivo Actualizado: "+response.contenido+"</label>");
					
										$("#listaContenidoOk").empty();
										$("#listaContenidoOk").append(response.contenido);
									}		
								},		
								error:function(){		
									toastr.error(":(");		
								}		
							}); // Cierre de Ajax. QUE TIENE EL NOMBRE DEL ARCHIVO A ACTUALIZAR.
					}
					
				},		
				error:function(){		
					toastr.error(":(");		
				}		
			}); // Cierre de Ajax.		
		}
	} // If Data-accion - Actualizar.
            // ***************************************************************************************************
			// Mandar datos para eliminar un registro.
            // ***************************************************************************************************
			       if($(this).attr('data-accion') == 'goEliminarOk'){
					// Elminar mensaje de Actualizar Archivo.
						$('#MensajeImportar').empty();
					// Llamar al archivo php para hacer la consulta y presentar los datos.
						$.post("includes/borrar_hoja_calculo.php",  { nombre_archivo_: nombre_archivo},
						function(data_borrar) {
							// validar
							if (data_borrar[0].registro == "Si_registro") {
								toastr.info("Hoja de Calculo Borrada."); 
                                // Volver a cargar la información de los archivos.
                                    $.post("includes/cargar-nombre-archivos.php",
                                        function(data) {
                                            $('#listaArchivosOK').empty();
                                            var filas = data.length;
                                            if (filas !== 0 ) {
                                                for (fila=0;fila<filas;fila++) {
													$('#listaArchivosOK').append(data[fila].archivo);
                                                }                                                
                                            }else{
                                                    $('#listaArchivosOK').append(data[fila].archivo);
                                                }
                                        }, "json");
							}
						}, "json");
			       }                                             	
});
});