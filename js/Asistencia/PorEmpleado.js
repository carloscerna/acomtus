// id de user global
var id_ = 0;
var accion = "todos";
var tabla = "";
var miselect = "";
var today = "";
$(function(){ // iNICIO DEL fUNCTION.
$(document).ready(function(){
///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
		// LLAMAR A LA TABLA PERSONAL.
		    codigo_personal = $("#CodigoPersonalEncargado").val();
            codigo_departamento_empresa = $("#CodigoDepartamentoEmpresa").val();
        //
            $('#listadoEmpleadosNomina').append("<tr><td>Buscando Registros... Por Favor Espere.</td></tr>"); 
        //
            buscar_personal_departamento_empresa(codigo_personal);
            CodigoRuta = $("#CodigoRuta").val();
///////////////////////////////////////////////////////////////////////////////
	// LLAMAR A LA TABLA PERSONAL.
///////////////////////////////////////////////////////////////////////////////
		listar_jornada();
	// IMAGEN PREDETERMINADA
		$(".card-img-top").attr("src", "../acomtus/img/NoDisponible.jpg");
	// VALIDAR CUANDO EL CODIGO DEL PERFIL DEL USUARIO SEA IGUAL A 
	// 01, 02 o 05
	// 07 jefe de linea
	// 08 Revisadores
	// 09 Vigilantes
	// 10 Aseo
	// 11 Mantenimiento
		var codigo_perfil = $("#codigo_perfil").val();
			if(codigo_perfil == '01' || codigo_perfil == '02' || codigo_perfil == '05' || codigo_perfil == '07' || codigo_perfil == '08' || codigo_perfil == '09' || codigo_perfil == '10' || codigo_perfil == '11'){
				$("#PantallaPrincipal").show();
				$("#PantallaPrincipalApagado").hide();
				$("#FechaAsistencia").attr("readonly",false);
			}else{
			//	VALIDAR LA HORA QUE PUEDA GUARDAR DE 7:00 A.M. A 5:30 P.M.
				var h = $("#SoloHora").val();
				if(h > 17){
				//alert(h);
					$("#PantallaPrincipal").hide();
					$("#PantallaPrincipalApagado").show();
					$("#FechaAsistencia").attr("readonly",true);
				}else{
					$("#PantallaPrincipal").show();
					$("#PantallaPrincipalApagado").hide();
					$("#FechaAsistencia").attr("readonly",true);
				}
				$("#FechaAsistencia").attr("readonly",false);
			}
});		
///////////////////////////////////////////////////////////////////////////////
/// EVENTOS JQUERY Y BOTON NUEVO REGISTRO. CALCULO Y OTROS
///////////////////////////////////////////////////////////////////////////////
$("#lstPersonal").on('change', function(){
	var nombre = $("#lstPersonal option:selected").text();
	//var partial = nombre.split("|");
	//$("#txtnombres").val(partial[1].trim());
	$("#txtnombres").val(nombre);
});
///////////////////////////////////////////////////////////////////////////////
//	FUNCION LISTAR BUSQUEDA DE LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
// Escribir la fecha actual.
var now = new Date();                
var day = ("0" + now.getDate()).slice(-2);
var month = ("0" + (now.getMonth() + 1)).slice(-2);
var year = now.getFullYear();

today = now.getFullYear()+"-"+(month)+"-"+(day) ;
$('#FechaAsistencia').val(today);
///////////////////////////////////////////////////////////////////////////////	  
// BLOQUE DE BUSQUEDA
///////////////////////////////////////////////////////////////////////////////	  
$("#goBuscarPersonalAsistencia").on('click', function (e) {
	// buscar en la tabla personal
	buscar_personal();
});
///////////////////////////////////////////////////////////////////////////////	  
// SELECCIONAR POR MEDIO DEL RADIO BUTTON PARA LA BUSQUEDA DEL MOTORISTA.
///////////////////////////////////////////////////////////////////////////////	  
$("#Jornada, #Permiso").change(function () {
	if ($("#Jornada").is(":checked")) {
		$('#DivJornada').show();
		$('#DivPermisos').hide();
		//
		$("#JornadaTV").hide();
		$("#JornadaDescanso").hide();
		// VOLVER A COLOCAR EN VALOR "no" AMBAS BOOLEAN
		$("#BooleanTV").val('no');
		$("#BooleanDescanso").val('no');
		listar_jornada();
		// ELIMINAR UN ITEM DE LSTJORNADA
		$("#lstJornada option[value='0H']").remove();
	}
	else if ($("#Permiso").is(":checked")) {
		$('#DivPermisos').show();
		$('#DivJornada').hide();
		// VOLVER A COLOCAR EN VALOR "no" AMBAS BOOLEAN
		$("#BooleanTV").val('no');
		$("#BooleanDescanso").val('no');
		//
		$("#JornadaTV").hide();
		$("#JornadaDescanso").hide();
		// Listar tipo licencia.
			listar_tipo_licencia();
	}
});
///////////////////////////////////////////////////////////////////////////////	  
// SELECCIONAR POR MEDIO DEL RADIO BUTTON PARA LA BUSQUEDA DEL JORNAA 4 HORAS.
///////////////////////////////////////////////////////////////////////////////	  
$("#JornadaExtraSi, #JornadaExtraNo").change(function () {
	if ($("#JornadaExtraSi").is(":checked")) {
		$('#JornadaExtra4Horas').show();
		listar_jornada_cuatro_horas(2);
	}
	else if ($("#JornadaExtraNo").is(":checked")) {
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
			elegido=$(this).val();
			// SE HA SELECCIONADO TRABAJÓ EN VACACIÓN
			if(elegido == '1'){
				// VOLVER A COLOCAR EN VALOR "si"
		//
				$("#JornadaExtra").show();

				listar_jornada_cuatro_horas(2);
			}else{
				$("#JornadaExtra").hide();
				$("#JornadaExtra4Horas").hide();
				// Activar y bloquear Permiso y seleccionar un item.
				$("#JornadaExtraSi").prop("checked", false);
				$("#JornadaExtraNo").prop("checked", true);
			}
		});
});
///////////////////////////////////////////////////////////////////////////////	  
// BUSCA PARA COLOCAR VISIBLE EL EXTRA EN TRABAJO EN VACACIÓN.
$("#lstJornadaTV").change(function () {
	$("#lstJornadaTV option:selected").each(function () {
		// ELEJIR EL VALOR DEL SELECT
			CodigoJornada = $(this).val();
		// SE HA SELECCIONADO TRABAJÓ EN VACACIÓN
			$("#CJV").VAL(CodigoJornada);
	});
});
// CUANDO SE ENCUENTRA EL CAMBIO DEL DEPARTAMENTO EN LA EMPRESA
$("#lstTipoLicencia").change(function () {
	var miselect=$("#lstTipoLicencia");

	$("#lstTipoLicencia option:selected").each(function () {
			// ELEJIR EL VALOR DEL SELECT
			elegido=$(this).val();
			// SE HA SELECCIONADO TRABAJÓ EN VACACIÓN
			if(elegido == '12'){
				// VOLVER A COLOCAR EN VALOR "si"
				$("#BooleanTV").val('si');
				//
				$("#JornadaTV").show();
				$("#JornadaDescanso").hide();
				listar_jornada_vacacion(2);
			}else if(elegido == '14'){	// SE HA SELECCIONADO TRABAJO EN DESCANSO
				// VOLVER A COLOCAR EN VALOR "si"
				$("#BooleanDescanso").val('si');
				//
				$("#JornadaTV").hide();
				$("#JornadaDescanso").show();
				listar_jornada_descanso(2);
			}else{
				// VOLVER A COLOCAR EN VALOR "no" AMBAS BOOLEAN
				$("#BooleanTV").val('no');
				$("#BooleanDescanso").val('no');
				// OCULTAR
				$("#JornadaDescanso").hide();
				$("#JornadaTV").hide();
			}
		});
});
///////////////////////////////////////////////////////////////////////////////
/// EVENTOS JQUERY Y para disparar la busqueda. del por nombre motorista.
///////////////////////////////////////////////////////////////////////////////
$("#goEnviar").on('click', function(){
	$("#formAsistencia").submit();
});
///////////////////////////////////////////////////////
// Validar Formulario, para la busqueda de un registro por codigo del motorista.
 //////////////////////////////////////////////////////
 $('#formAsistencia').validate({
	ignore:"",
	rules:{
			CodigoPersonal: {required: true},
			NombrePersonal: {required: true},
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
		var str = $('#formAsistencia').serialize();
		alert(str);
			// casilla de verificación revisar el valor.
			var TipoLicenciaChecks = "off";
			if ($("#Permiso").is(':checked')) {
				//
					TipoLicenciaChecks = "on";
			}
			//alert(TipoLicenciaChecks);
			// casilla de verificación revisar el valor.
			var chkNocturnidad = "no";
			if ($("#chkNocturnidad").is(':checked')) {
				//
				chkNocturnidad = "si";
			}
			//alert(TipoLicenciaChecks);
		///////////////////////////////////////////////////////////////			
		// Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
		///////////////////////////////////////////////////////////////
			$.ajax({
				beforeSend: function(){

				},
				cache: false,
				type: "POST",
				dataType: "json",
				url:"php_libs/soporte/Asistencia/PorEmpleado.php",
				data:str + "&tipochecks=" + TipoLicenciaChecks + "&Nocturnidad=" + chkNocturnidad + "&id=" + Math.random(),
				success: function(response){
					// Validar mensaje de error
					if(response.respuesta == false){
						toastr["error"](response.mensaje, "Sistema");
					}
					else{
						toastr["success"](response.mensaje, "Sistema");
						// LIMPIAR VARIABLES.ab-b-l
						$("#CodigoPersonal").val("");
						$("#NombrePersonal").val("");
						//
						$("#LblAsistencia").text('');
						// PERMISO Y JORNADA DIV
						$('#DivPermisos').hide();
						$('#DivJornada').hide();
						$("#JornadaTV").hide();
						$("#JornadaDescanso").hide();
						$("input:radio[value='Jornada']").prop('checked',false);
						$("input:radio[value='Permiso']").prop('checked',false);  
						//
						$("#JornadaExtra").hide();
						$("#JornadaExtra4Horas").hide();
						$("#JornadaAsueto").hide();
						// Activar y bloquear Permiso y seleccionar un item.
						$("#JornadaExtraSi").prop("checked", false);
						$("#JornadaExtraNo").prop("checked", true);
						$("#chkNocturnidad").prop("checked", false);
						// limpiar el control de la foto
						$(".card-img-top").attr("src", "../acomtus/img/NoDisponible.jpg");
						// focus
						$("#CodigoPersonal").focus();
					}      
				},
			});
		},
});


});	// final de FUNCTION.
// ABRE OTRA PESTAÑA	
function AbrirVentana(url)
{
    window.open(url, '_blank');
    return false;
}
////////////////////////////////////////////////////////////
// FUNCION LISTAR TABLA perosnal solo motoristas
////////////////////////////////////////////////////////////
function buscar_personal(codigo_personal){
    var codigo_personal = $("#CodigoPersonal").val();
	var fecha_ = $("#FechaAsistencia").val();
	var CodigoDepartamentoEmpresa = $("#codigo_departamento_empresa").val();
    $.post("php_libs/soporte/Asistencia/PorEmpleado.php", {
		accion_buscar: 'BuscarPersonalCodigo', codigo_personal: codigo_personal, fecha: fecha_, codigo_departamento_empresa: CodigoDepartamentoEmpresa},
        function(data) {
			if(data[0].respuestaOK == true){
				var nombre_empleado = data[0].nombre_empleado;
				$("#NombrePersonal").val(nombre_empleado);
				// FOTO DEL EMPELADO.
					if(data[0].url_foto == "")
					{
						if(data[0].codigo_genero == "01"){
							$(".card-img-top").attr("src", "../acomtus/img/avatar_masculino.png");
						}else{
							$(".card-img-top").attr("src", "../acomtus/img/avatar_femenino.png");
						}
					}else{
						$(".card-img-top").attr("src", "../acomtus/img/fotos/" + data[0].url_foto);	
					}
				// presar CHECK BOX NOCTURNIDAD.
				if(data[0].codigo_departamento_empresa == "08" || data[0].codigo_departamento_empresa == "09"){
					$("#NocturnidadSiNo").show();
				}else{
					$("#NocturnidadSiNo").hide();
				}
				// VALIDAR SI EL D{IA SELECCIONADO ES DE ASUETO O NO
					if(data[0].asueto == "si"){
						$("#BooleanAsueto").val(data[0].asueto);
						$("#TextAsuetoDescripcion").text(data[0].descripcion);
						$("#JornadaAsueto").show();
						listar_jornada_asueto(4);
						// Activar y bloquear Permiso y seleccionar un item.
						$("#Jornada").prop("checked", true);
						$('#DivJornada').show();
						listar_jornada(2);
						$("#Permiso").prop("disabled", false);
						$("#lstJornada").prop("readonly", true);
					}else{
						$("#TextAsuetoDescripcion").text("");
						$("#JornadaAsueto").hide();
						listar_jornada_asueto(4);
						// Activar y bloquear Permiso y seleccionar un item.
						$("#Jornada").prop("checked", false);
						$("#Permiso").prop("checked", false);
						$('#DivJornada').hide();
						$('#DivPermiso').hide();
						$("#Permiso").prop("disabled", false);
						$("#lstJornada").prop("readonly", false);
					}
				//	MENSAJE DEL SISEMA
				toastr["success"](data[0].mensajeError, "Sistema");
			}else{
				// IMAGEN PREDETERMINADA
				$(".card-img-top").attr("src", "../acomtus/img/NoDisponible.jpg");
				toastr["error"](data[0].mensajeError, "Sistema");
			}
        }, "json");    
}
// FUNCION LISTAR TABLA catalogo_jornada
////////////////////////////////////////////////////////////
function listar_jornada(codigo_jornada){
    var miselect=$("#lstJornada");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/ProduccionBuscar.php", {accion_buscar: 'BuscarJornada'},
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
				// VALIDAR CODIGO JORNADA IGUAL A 0H Y N
				if(data[i].codigo == '4' || data[i].codigo == '5'){

				}else{
					if(codigo_jornada == data[i].codigo){
						miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + '</option>');
              		  }else{
						if(i==1){
							miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + '</option>');
						}else{
							miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
						}
					}
				}
               
            }
    }, "json");    
}
//LISTAR JORNADA 4 HORAS
function listar_jornada_cuatro_horas(codigo_jornada){
    var miselect=$("#lstJornadaExtraCuatroHoras");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/ProduccionBuscar.php", {accion_buscar: 'BuscarJornada'},
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
						}else{
							miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
						}
					}
				}
            }
    }, "json");    
}
// FUNCION LISTAR TABLA catalogo_jornada trabajo en vaci{on}
////////////////////////////////////////////////////////////
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
				// VALIDAR CODIGO JORNADA IGUAL A 0H
				if(data[i].codigo == '4' || data[i].codigo == '5'){

				}else{
					if(codigo_jornada == data[i].codigo){
						miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + '</option>');
					}else{
						if(i==1){
							miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + '</option>');
						}else{
							miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
						}
					}
				}
            }
    }, "json");    
}
function listar_jornada_vacacion(codigo_jornada){
    var miselect=$("#lstJornadaTV");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/ProduccionBuscar.php", {accion_buscar: 'BuscarJornada'},
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
						}else{
							miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
					}
                }
				}
            }
    }, "json");    
}
// FUNCION LISTAR TABLA catalogo_jornada cuando es asueto
////////////////////////////////////////////////////////////
function listar_jornada_asueto(codigo_jornada){
    var miselect=$("#lstJornadaAsueto");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/ProduccionBuscar.php", {accion_buscar: 'BuscarJornada'},
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
				// VALIDAR CODIGO JORNADA IGUAL A 0H
				if(data[i].codigo == '4' || data[i].codigo == '5'){

					}else{

				}
                if(codigo_jornada == data[i].codigo){
						miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + '</option>');
                }else{
					if(i==1){
						miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + '</option>');
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
    
    $.post("php_libs/soporte/Asistencia/PorEmpleado.php", {accion_buscar: 'BuscarTipoLicencia'},
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                if(codigo_tipo_licencia == data[i].codigo){
                    miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + ' - ' + data[i].descripcion_completa + '</option>');
                }else{
                    miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + ' - ' +  data[i].descripcion_completa + '</option>');
                }
            }
    }, "json");    
}
function delimitNumbers(str) { return (str + "").replace(/\b(\d+)((\.\d+)*)\b/g, function(a, b, c) { return (b.charAt(0) > 0 && !(c || ".").lastIndexOf(".") ? b.replace(/(\d)(?=(\d{3})+$)/g, "$1,") : b) + c; }); } 