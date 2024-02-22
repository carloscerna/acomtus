// id de user global
var id_ = 0;
var accion = "todos";
var tabla = "";
var miselect = "";
var today = "";
var CodigoRuta = "";
var today = "";
var codigo_personal = "";
var fecha = "";
var codigo_personal = "";
var codigo_departamento_empresa = "";
$(function(){ // iNICIO DEL fUNCTION.
    // Escribir la fecha actual.
        var now = new Date();                
        var day = ("0" + now.getDate()).slice(-2);
        var month = ("0" + (now.getMonth() + 1)).slice(-2);
        var year = now.getFullYear();

        fecha = now.getFullYear()+"-"+(month)+"-"+(day) ;
        $('#FechaListadoEmpleados').val(fecha);
///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
	$(document).ready(function(){
		// LLAMAR A LA TABLA PERSONAL.
		    codigo_personal = $("#CodigoPersonal").val();
            codigo_departamento_empresa = $("#CodigoDepartamentoEmpresa").val();
        //
            $('#listadoEmpleadosNomina').append("<tr><td>Buscando Registros... Por Favor Espere.</td></tr>"); 
        //
            buscar_personal(codigo_personal);
            CodigoRuta = $("#CodigoRuta").val();
	});		
// cuando la fecha cambie.
    $("#FechaListadoEmpleados").change(function(){
        fecha = $('#FechaListadoEmpleados').val();
        $('#listadoEmpleadosNomina').empty();
        $('#listadoEmpleadosNomina').append("<tr><td>Buscando Registros... Por Favor Espere.</td></tr>"); 
            buscar_personal(codigo_personal);
    });
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    // BLOQUE PARA ADMINISTRAR LAS ASIGNATURAS.
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // BLOQUE EXTRAER INFORMACIÓN DEL REGISTROS ()
        //
		$('body').on('click','#listadoEmpleadosNomina a',function (e){
			e.preventDefault();
			// Id Usuario
                Id_Editar_Eliminar = $(this).attr('href');
                accion_ok = $(this).attr('data-accion');
                    // EDITAR LA ASIGNATURA
                    if($(this).attr('data-accion') == 'editarAsistencia'){
                        // Valor de la acción
                            accion = 'EditarJornada';
                            //
                            $('#VentanaPunteo').modal("show");
                            Datos = $(this).find("td:eq(2)").text();
                            // Llamar al archivo php para hacer la consulta y presentar los datos.
                            $.post("php_libs/soporte/Asistencia/PorNomina.php",  { Id_: Id_Editar_Eliminar, accion: accion},
                                function(data) {
                                // Llenar el formulario con los datos del registro seleccionado tabs-1
                                // Datos Generales
                                    $("label[for=CodigoNombreEmpleado]").text(data[0].CodigoPersonal + "-" + data[0].NombreCompleto);
                                    $("#FotoEmpleado").attr("src",data[0].Foto);
                                    $("#ImagenJornada").attr("src",data[0].ImgJornada);
                                    //
                                    $('#Id_').val(data[0].Id_);
                                    $("#CJTodos").val(data[0].CodigoJornadaTodas);
                                    $("#CJTodosSeparador").val(data[0].CodigoJornadaTodasSeparador);
                                    var todosSeparador = data[0].CodigoJornadaTodasSeparador;
                                    let CJTodosSeparador = todosSeparador.split(".");
                                        $('#CJ').val(CJTodosSeparador[0]);  // CODIGO JORNADA
                                        $('#CTL').val(CJTodosSeparador[1]); // CODIGO TIPO LICENCIA
                                        $('#CJA').val(CJTodosSeparador[2]); // CODIGO JORNADA ASUETO
                                        $('#CJV').val(CJTodosSeparador[3]); //  CODIGO JORNADA VACACION
                                        $('#CJD').val(CJTodosSeparador[4]); // CODIGO JORNADA DESCANSO
                                        $('#CJE4H').val(CJTodosSeparador[5]);   //  CODIGO JORNADA EXTRA 4 HORAS
                                        $('#CJN').val(CJTodosSeparador[6]); //  CODIGO JORNADA NOCTURNA+
                                    //  ACTIVAR O DESACTIVAR ELEMENTOS.
                                        // presar CHECK BOX NOCTURNIDAD. MANTENIMIENTO Y VIGILANCIA
                                        if(codigo_departamento_empresa == "08" || codigo_departamento_empresa == "09"){
                                            $("#NocturnidadSiNo").show();
                                            //
                                            $("#chkNocturnidad").prop("checked", false);
                                        }else{
                                            $("#NocturnidadSiNo").hide();
                                        }
                                    // ocultar
                                        $('#JornadaExtra').hide();
                                        $('#JornadaExtra4Horas').hide();
                                    // reestablecer el accion a=ActualizarJOrnada
                                        accion = "ActualizarJornada";
                                },"json");
                    }
                    // ELIMINAR REGISTRO ASIGNATURA.
                    if($(this).attr('data-accion') == 'eliminar_asignatura'){
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
                                    url:"php_libs/soporte/Mantenimiento/Servicio Educativo/phpAjaxServicioEducativo.php",                     
                                    data: {                     
                                            accion_buscar: 'eliminar_asignatura', codigo_id_: Id_Editar_Eliminar,
                                            },                     
                                    success: function(response) {                     
                                            if (response.respuesta === true) {                     		
                                                // Asignamos valor a la variable acción
                                                    $('#accion_asignatura').val('BuscarAsignatura');
                                                    var codigo_se = $("#lstcodigose").val();
                                                    accion = 'BuscarAsignatura';
                                                    //
                                                    //  CONDICONAR EL SELECT SERVICIO EDUCATIVO.
                                                    //
                                                    if(codigo_se == "00"){
                                                        $("#AlertSE").css("display", "block");
                                                        return;
                                                    }
                                                    // Llamar al archivo php para hacer la consulta y presentar los datos.
                                                    $.post("php_libs/soporte/Mantenimiento/Servicio Educativo/phpAjaxServicioEducativo.php",  {accion: accion, codigo_se: codigo_se},
                                                        function(response) {
                                                        if (response.respuesta === true) {
                                                            toastr["info"]('Registros Encontrados', "Sistema");
                                                        }
                                                        if (response.respuesta === false) {
                                                            toastr["warning"]('Registros No Encontrados', "Sistema");
                                                        }                                                                                    // si es exitosa la operación
                                                            $('#listaContenidoSE').empty();
                                                            $('#listaContenidoSE').append(response.contenido);
                                                        },"json");
                                            }
                                            if (response.respuesta === false) {                     		
                                                toastr["info"]('Registro no Eliminado... El còdigo está está activo en la Tabla Notas.', "Sistema");
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
///////////////////////////////////////////////////////////////////////////////	  
// VALIDAR NOCTURNIDAD
///////////////////////////////////////////////////////////////////////////////	          
    $("#chkNocturnidad").on('click',function () {
        if ($("#chkNocturnidad").is(':checked')) {
            //
            $("#CJN").val("5");
        }else{
            $("#CJN").val("4");
        }
    });
///////////////////////////////////////////////////////////////////////////////	  
// VALIDAR NOCTURNIDAD ASUETO
///////////////////////////////////////////////////////////////////////////////	          
$("#chkNocturnidadAsueto").on('click',function () {
    if ($("#chkNocturnidadAsueto").is(':checked')) {
        //
        $("#CJN").val("5");
    }else{
        $("#CJN").val("4");
    }
});
///////////////////////////////////////////////////////////////////////////////	  
// SELECCIONAR POR MEDIO DEL RADIO BUTTON PARA LA BUSQUEDA DEL MOTORISTA.
///////////////////////////////////////////////////////////////////////////////	  
$("#BotonJornada, #BotonLicencia, #BotonCerrar").on('click',function () {
	if (this.value == "Jornada") {
		$('#DivJornada').show();
		$('#DivPermisos').hide();
		//
		$("#JornadaTV").hide();
		$("#JornadaDescanso").hide();
    		listar_jornada();
	}
	else if (this.value == "Licencia") {
        //
		$('#DivPermisos').show();
		$('#DivJornada').hide();
		//
		$("#JornadaTV").hide();
		$("#JornadaDescanso").hide();
		// Listar tipo licencia.
			listar_tipo_licencia();
        // Valor por defecto.
            $('#CTL').val(2);  // CODIGO JORNADA
	}else if (this.value == "Cerrar") {
        $('#DivPermisos').hide();
		$('#DivJornada').hide();
		//
		$("#JornadaTV").hide();
		$("#JornadaDescanso").hide();
        // Activar y bloquear Permiso y seleccionar un item.
        $("#JornadaExtraSi").prop("checked", false);
        $("#JornadaExtraNo").prop("checked", true);
        $("#chkNocturnidad").prop("checked", false);
    }
});
///////////////////////////////////////////////////////////////////////////////	  
// SELECCIONAR POR MEDIO DEL RADIO BUTTON PARA LA BUSQUEDA DEL JORNAA 4 HORAS.
///////////////////////////////////////////////////////////////////////////////	  
$("#JornadaExtraSi, #JornadaExtraNo").change(function () {
	if ($("#JornadaExtraSi").is(":checked")) {
        //  MOSTRAR RADIO BUTTONS.
            $('#JornadaExtra4Horas').show();
            $('#CJE4H').val(2);  // CODIGO JORNADA
    		    listar_jornada_cuatro_horas(2);
        // ELIMINAR UN ITEM DE LSTJORNADA
    		 //   $("#lstJornadaExtraCuatroHoras").find('option')[3].remove();
	}
	else if ($("#JornadaExtraNo").is(":checked")) {
        //  VALORES POR DEFECTO DE LA 
            $('#CJE4H').val(4);  // CODIGO JORNADA
        //  OCULTAR RADIO BUTTONS.
		$('#JornadaExtra4Horas').hide();
	}
});
///////////////////////////////////////////////////////////////////////////////	  
// SELECCIONAR POR MEDIO DEL RADIO BUTTON PARA LA BUSQUEDA DEL MOTORISTA.
///////////////////////////////////////////////////////////////////////////////	  
// BUSCA PARA COLOCAR VISIBLE EL EXTRA DE 4 HORAS
$("#lstJornada").change(function () {
	var miselect=$("#lstJornada");
	$("#lstJornada option:selected").each(function () {
			// ELEJIR EL VALOR DEL SELECT
			    ValorJornada=$(this).val();
            // cabmiar el valor del text o hidden.
                $('#CJ').val(ValorJornada);  // CODIGO JORNADA
			// SE HA SELECCIONADO TRABAJÓ EN VACACIÓN
			if(ValorJornada == '1'){
				// VOLVER A COLOCAR EN VALOR "si"
	    			$("#JornadaExtra").show();
    				//listar_jornada_cuatro_horas(4);
			}else{
                //  VALORES POR DEFECTO DE LA 
                    $('#CJE4H').val(4);  // CODIGO JORNADA
                //
				    $("#JornadaExtra").hide();
				    $("#JornadaExtra4Horas").hide();
				// Activar y bloquear Permiso y seleccionar un item.
    				$("#JornadaExtraSi").prop("checked", false);
	    			$("#JornadaExtraNo").prop("checked", true);
			}
		});
});
// CUANDO SE ENCUENTRA EL CAMBIO DEL DEPARTAMENTO EN LA EMPRESA
$("#lstTipoLicencia").change(function () {
	var miselect=$("#lstTipoLicencia");

	$("#lstTipoLicencia option:selected").each(function () {
			// ELEJIR EL VALOR DEL SELECT
			TipoLicencia = $(this).val();
			// SE HA SELECCIONADO TRABAJÓ EN VACACIÓN
			if(TipoLicencia == '12'){
				//
				$("#JornadaTV").show();
				$("#JornadaDescanso").hide();
                $("#JornadaAsueto").hide();
				    listar_jornada_vacacion(2); // UNA TANDA.
                // licencias o permisos TIEMPO EXTRA Y VALOR PREDETERMINADO.
                    $("#CJ").val(4);    // VALOR PREDETERMINADO.
                    $("#CTL").val(TipoLicencia)
                    $("#CJV").val(2);    // valor actual de lstJornadaTV
			}else if(TipoLicencia == '14' || TipoLicencia == '15'){	// SE HA SELECCIONADO TRABAJO EN DESCANSO
                //
				$("#JornadaTV").hide();
				$("#JornadaDescanso").show();
                $("#JornadaAsueto").hide();
				    listar_jornada_descanso(2); // Una tanda
                        // licencias o permisos TIEMPO EXTRA Y VALOR PREDETERMINADO.
                        $("#CJ").val(4);    // VALOR PREDETERMINADO.
                        $("#CTL").val(TipoLicencia)
                        $("#CJD").val(2);    // valor actual de lstJornadaDescanso
			}else if(TipoLicencia == '19'){	// SE HA SELECCIONADO TRABAJO EN ASUETO
                // presar CHECK BOX NOCTURNIDAD. MANTENIMIENTO Y VIGILANCIA
                if(codigo_departamento_empresa == "08" || codigo_departamento_empresa == "09"){
                    $("#NocturnidadSiNoAsueto").show();
                    //
                    $("#chkNocturnidadAsueto").prop("checked", false);
                    
				    listar_jornada_asueto(4); // cero horas.
                        // licencias o permisos TIEMPO EXTRA Y VALOR PREDETERMINADO.
                        $("#CJ").val(4);    // VALOR PREDETERMINADO.
                        $("#CTL").val(TipoLicencia)
                        $("#CJA").val(4);    // valor actual de lstJornadaAsueto
                }else{
                    $("#NocturnidadSiNoAsueto").hide();
                    $("#chkNocturnidadAsueto").prop("checked", false);
                    
				    listar_jornada_asueto(2); // Una tanda
                        // licencias o permisos TIEMPO EXTRA Y VALOR PREDETERMINADO.
                        $("#CJ").val(4);    // VALOR PREDETERMINADO.
                        $("#CTL").val(TipoLicencia)
                        $("#CJA").val(2);    // valor actual de lstJornadaAsueto
                }
				//
				$("#JornadaTV").hide();
				$("#JornadaDescanso").hide();
                $("#JornadaAsueto").show();
                //
            }else if(TipoLicencia == "16"){
                // licencias o permisos TIEMPO EXTRA Y VALOR PREDETERMINADO.
                $("#CJ").val(4);    // VALOR PREDETERMINADO.
                $("#CTL").val(TipoLicencia)
            }else{
				// OCULTAR
				$("#JornadaDescanso").hide();
				$("#JornadaTV").hide();
                $("#JornadaAsueto").hide();
                // Ocultar check Nocturnidad
                    $("#NocturnidadSiNoAsuetoNormal").hide();
                    $("#chkNocturnidadNormal").prop("checked", false);

                    $("#chkNocturnidad").prop("checked", false);
                    $("#NocturnidadSiNoAsueto").hide();
                // licencias o permisos sin tiempo extra.
                $("#CTL").val(TipoLicencia);
			}
		});
});
// BUSCA PARA COLOCAR VISIBLE EL EXTRA DE 4 HORAS
$("#lstJornadaExtraCuatroHoras").change(function () {
	var miselect=$("#lstJornadaExtraCuatroHoras");
	$("#lstJornadaExtraCuatroHoras option:selected").each(function () {
			// ELEJIR EL VALOR DEL SELECT
			    ValorJornada=$(this).val();
            // cabmiar el valor del text o hidden.
                $('#CJE4H').val(ValorJornada);  // CODIGO JORNADA
		});
});
// BUSCA PARA COLOCAR VISIBLE EL EXTRA EN TRABAJO EN VACACIÓN.
$("#lstJornadaTV").change(function () {
	$("#lstJornadaTV option:selected").each(function () {
		// ELEJIR EL VALOR DEL SELECT
			CodigoJornada = $(this).val();
		// SE HA SELECCIONADO TRABAJÓ EN VACACIÓN
			$("#CJV").val(CodigoJornada);
	});
});
// BUSCA PARA COLOCAR VISIBLE EL EXTRA EN TRABAJO EN DESCANSO.
$("#lstJornadaDescanso").change(function () {
	$("#lstJornadaDescanso option:selected").each(function () {
		// ELEJIR EL VALOR DEL SELECT
			CodigoJornada = $(this).val();
		// SE HA SELECCIONADO TRABAJÓ EN VACACIÓN
			$("#CJD").val(CodigoJornada);
	});
});
// BUSCA PARA COLOCAR VISIBLE EL EXTRA EN TRABAJO EN DESCANSO.
$("#lstJornadaAsueto").change(function () {
	$("#lstJornadaAsueto option:selected").each(function () {
		// ELEJIR EL VALOR DEL SELECT
			CodigoJornada = $(this).val();
		// SE HA SELECCIONADO TRABAJÓ EN VACACIÓN
			$("#CJA").val(CodigoJornada);
	});
});
///////////////////////////////////////////////////////////////////////////////
/// EVENTOS JQUERY Y para disparar la busqueda. del por nombre motorista.
///////////////////////////////////////////////////////////////////////////////
$("#goGuardarPunteo").on('click', function(){
	$("#formPunteo").submit();
});
///////////////////////////////////////////////////////
// Validar Formulario, para la ACTUALIZAR DE PERSONAL ASISTENCIA.
 //////////////////////////////////////////////////////
 $('#formPunteo').validate({
	ignore:"",
	rules:{
			//CodigoPersonal: {required: true},
			//NombrePersonal: {required: true},
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
		var str = $('#formPunteo, #formAsistenciaPorNomina').serialize();
//		alert(str);
		///////////////////////////////////////////////////////////////			
		// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
		///////////////////////////////////////////////////////////////
			$.ajax({
				beforeSend: function(){

				},
				cache: false,
				type: "POST",
				dataType: "json",
				url:"php_libs/soporte/Asistencia/PorNomina.php",
				data: str + "&id=" + Math.random(),
				success: function(response){
					// Validar mensaje de error
					if(response.respuesta == false){
						toastr["error"](response.mensaje, "Sistema");
					}
					else{
						toastr["info"](response.mensaje, "Sistema");
						// LIMPIAR VARIABLES.ab-b-l
                            $('#DivPermisos').hide();
                            $('#DivJornada').hide();
                            $("#JornadaTV").hide();
                            $("#JornadaDescanso").hide();
						//
                            $("#JornadaExtra").hide();
                            $("#JornadaExtra4Horas").hide();
                            $("#JornadaAsueto").hide();
						// Activar y bloquear Permiso y seleccionar un item.
                            $("#JornadaExtraSi").prop("checked", false);
                            $("#JornadaExtraNo").prop("checked", true);
                            $("#chkNocturnidad").prop("checked", false);
						// focus
    					    $("#CodigoPersonal").focus();
                        //
                        // LLAMAR A LA TABLA PERSONAL.
                            codigo_personal = $("#CodigoPersonal").val();
                        // Abrir ventana modal.
                            $('#VentanaPunteo').modal("hide");
                        //
                            $('#listadoEmpleadosNomina').append("<tr><td>Buscando Registros... Por Favor Espere.</td></tr>"); 
                        //
                        // presar CHECK BOX NOCTURNIDAD. MANTENIMIENTO Y VIGILANCIA
                            if(codigo_departamento_empresa == "08" || codigo_departamento_empresa == "09"){
                                $("#NocturnidadSiNo").show();
                                //
                                $("#chkNocturnidad").prop("checked", false);
                            }else{
                                $("#NocturnidadSiNo").hide();
                            }
                        // ocultar
                            $('#JornadaExtra').hide();
                            $('#JornadaExtra4Horas').hide();
                        ///
                            buscar_personal(codigo_personal);
					}      
				},
			});
		},
});

});		

////////////////////////////////////////////////////////////
// FUNCION LISTAR TABLA perosnal solo motoristas
////////////////////////////////////////////////////////////
// FUNCION LISTAR TABLA catalogo_jornada
////////////////////////////////////////////////////////////
function listar_jornada(codigo_jornada){
    var miselect=$("#lstJornada");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/Asistencia/PorNomina.php", {accion_buscar: 'BuscarJornada'},
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
				// VALIDAR CODIGO JORNADA IGUAL A 0H
				if(data[i].codigo == '4' || data[i].codigo == '5'){

				}else{
					if(codigo_jornada == data[i].codigo){
						miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + '</option>');
              		  }else{
						if(i==1){
							miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + '</option>');
                            // cabmiar el valor del text o hidden.
                                $('#CJ').val(data[i].codigo);  // CODIGO JORNADA
						}else{
							miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
						}
					}
				}
            }
    }, "json");    
}

function buscar_personal(codigo_personal){
    codigo_personal = $("#CodigoPersonal").val();
    codigo_departamento_empresa = $("#CodigoDepartamentoEmpresa").val();
    //
    $.post("php_libs/soporte/Asistencia/PorNomina.php", {
		accion_buscar: 'BuscarPersonalRutaCodigo', codigo_personal: codigo_personal, fecha: fecha, codigo_departamento_empresa: codigo_departamento_empresa},
        function(data) {
			if(data[0].respuestaOK == true){
				// CUANDO SEA OTRO DEPARTAMENTO
				if(codigo_departamento_empresa == "02"){
					$("#LblDescripcion").html("Ruta: " + data[0].Descripcion + " - Empleados: " + data[0].TotalEmpleados)
                    $("#CodigoRuta").val(data[0].Codigo)
                    CodigoRuta = data[0].Codigo;
				}else{
					$("#LblDescripcion").html("Departamento: " + data[0].Descripcion + " - Empleados: " + data[0].TotalEmpleados)
				}
				//	MENSAJE DEL SISEMA
			}else{
				$("#LblDescripcion").html(data[0].mensajeError)
			}

            $.ajax({
                beforeSend: function(){

                },
                cache: false,
                type: "POST",
                dataType: "json",
                url:"php_libs/soporte/Asistencia/PorNomina.php",
                data: {                     
                    accion_buscar: 'BuscarEmpleadosPorRuta', CodigoRuta: CodigoRuta, fecha: fecha, codigo_personal_encargado: codigo_personal, CodigoDepartamentoEmpresa: codigo_departamento_empresa
                    },  
                success: function(response){
                    // Validar mensaje de error
                    if(response.respuesta == false){

                    }else{
                        toastr["success"](response.mensaje, "Sistema");
                        $('#listadoEmpleadosNomina').empty();
                        $('#listadoEmpleadosNomina').append(response.contenido);
                        // MostrarMensaje
                        if(response.mensajeAsueto === ""){
                            $("#MostrarMensajes").hide();
                        }else{
                            $("label[for=LblMensaje]").text("Asueto: " + response.mensajeAsueto);
                            $("#MostrarMensajes").show();
                        }

                    }               
                },
            });            
        }, "json");    
}
function buscarPersonalPorRuta(CodigoRuta){
///////////////////////////////////////////////////////////////			
			// BUSCAR REGISTROS EN BASE LA CODIGO DE RUTA.
			///////////////////////////////////////////////////////////////
            $.ajax({
                beforeSend: function(){

                },
                cache: false,
                type: "POST",
                dataType: "json",
                url:"php_libs/soporte/Asistencia/PorNomina.php",
                data: {                     
                    accion_buscar: 'BuscarEmpleadosPorRuta', CodigoRuta: CodigoRuta,
                    },  
                success: function(response){
                    // Validar mensaje de error
                    if(response.respuesta == false){

                    }else{
                        $('#listadoEmpleadosNomina').empty();
                        $('#listadoEmpleadosNomina').append(response.contenido);
                        }               
                },
            });
}
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
//LISTAR JORNADA 4 HORAS
function listar_jornada_cuatro_horas(codigo_jornada){
    var miselect=$("#lstJornadaExtraCuatroHoras");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/Asistencia/PorNomina.php", {accion_buscar: 'BuscarJornada'},
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                if(codigo_jornada == data[i].codigo){
                    miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + '</option>');
                }else{
                    if(data[i].codigo == 4 || data[i].codigo == 5)
                    {

                    }else{
                        miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
                    }
                }
            }
    }, "json");    
}
// FUNCION LISTAR TABLA catalogo_jornada
////////////////////////////////////////////////////////////
function listar_tipo_licencia(codigo_tipo_licencia){
    var miselect=$("#lstTipoLicencia");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/Asistencia/PorNomina.php", {accion_buscar: 'BuscarTipoLicencia'},
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                if(codigo_tipo_licencia == data[i].codigo){
                    miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + " - " + data[i].descripcion_completa + '</option>');
                }else{
                    if(data[i].codigo == 1)
                    {

                    }else{
                        miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + " - " + data[i].descripcion_completa + '</option>');
                    }
                }
            }
    }, "json");    
}
// JORNADA EXTRA TRABAJO EN VACACIÓN.
function listar_jornada_vacacion(codigo_jornada){
    var miselect=$("#lstJornadaTV");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/Asistencia/PorNomina.php", {accion_buscar: 'BuscarJornada'},
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                if(codigo_jornada == data[i].codigo){
                    miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + '</option>');
                }else{
                    if(data[i].codigo == 4 || data[i].codigo == 5)
                    {

                    }else{
                        miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
                    }
                }
            }
    }, "json");    
}
// FUNCION LISTAR TABLA catalogo_jornada descanso
////////////////////////////////////////////////////////////
function listar_jornada_descanso(codigo_jornada){
    var miselect=$("#lstJornadaDescanso");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/ProduccionBuscar.php", {accion_buscar: 'BuscarJornada'},
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                if(codigo_jornada == data[i].codigo){
                    miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + '</option>');
                }else{
                    if(data[i].codigo == 4 || data[i].codigo == 5)
                    {

                    }else{
                        miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
                    }
                }
            }
    }, "json");    
}
// JORNADA EXTRA TRABAJO EN VACACIÓN.
function listar_jornada_asueto(codigo_jornada){
    var miselect=$("#lstJornadaAsueto");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/Asistencia/PorNomina.php", {accion_buscar: 'BuscarJornada'},
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                if(codigo_jornada == data[i].codigo){
                    miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + '</option>');
                }else{
                    if(data[i].codigo == 5)
                    {

                    }else{
                        miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
                    }
                }
            }
    }, "json");    
}