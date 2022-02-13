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
// GO GUARDAR PARA ALMACENAR DATOS FINALES EN LA TABLA FIANZAS O PRESTAMO
$('#goGuardar').on('click',function(){
	// Elminar mensaje de Actualizar Archivo.
	$('#MensajeImportar').empty();
		toastr.info("Guardar");
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
		// ASIGNATURAS PENDIENTES
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
				url: "includes/verificar_importar_notas_hoja_calculo.php",		
				data: "nombre_archivo_=" + nombre_archivo + "&valor_check=" + valor_check + "&id=" +Math.random(),		
				success: function(data){		
				// validar		
					if (data[0].registro == "No_registro") {		
						toastr.error("Archivo Incorrecto...");
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
										toastr.success("Hoja de Calculo Actualizada.");
										$('#MensajeImportar').empty();
                                        $('#MensajeImportar').append("<label 'class=text-black bg-default'>Archivo Actualizado: "+response.nombre_archivo+"</label>");

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