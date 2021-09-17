// id de user global
var id_ = 0;
var buscartodos = "";
var accion = 'noAccion';
var MenuTab = "";
var tableA = "";
$(function(){ // INICIO DEL FUNCTION.
            // Escribir la fecha actual.
                var now = new Date();                
                var day = ("0" + now.getDate()).slice(-2);
                var month = ("0" + (now.getMonth() + 1)).slice(-2);
                var today = now.getFullYear()+"-"+(month)+"-"+(day) ;
                var ann = now.getFullYear();
				
				var day_M = ("20");
                var today_M = now.getFullYear()+"-"+(month)+"-"+(day_M) ;
                // ASIGNAR FECHA ACTUAL A LOS DATE CORRESPONDIENTES.
                $('#txtfechanacimiento').val(today);
                $('#txtFechaIngreso').val(today);
                $('#txtFechaRetiro').val(today);
///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
	$(document).ready(function(){
		if($("#accion").val() == "EditarRegistro"){
			// Variables Principales.
			id_ = $("#id_user").val();
			accion = $("#accion").val();
            $("#txtEdicionNuevo").text("Edición");
			// cambiar texto de label y enlace.
			//$("label[for='txtEdicionNuevo']").text("Edición");
            //$("label[for='iEdicionNuevo']").text("Edición");
            //  Botones de la imagen o foto personal.
            $("#fileup").attr("disabled",false);		// Botón Subir Imagen Portafolio
            $("#SubirImagen").attr("disabled",false);		// Botón Subir Imagen Portafolio
            // Llamar a la función listar.
                listar();
            //  ver portafolio.
                VerPortafolio();
            //  ver historial
                VerHistorial();
		}
		if($("#accion").val() == "AgregarNuevoPersonal"){
			NuevoRegistro();
			// Variables accion para guardar datos.
			accion = $("#accion").val();
			// cambiar texto de label y enlace.
            $("#txtEdicionNuevo").text("Nuevo");
			//$("label[for='txtEdicionNuevo']").text("Agregar Personal");
            $("label[for='iEdicionNuevo']").text("Agregar");
            //  Botones de la imagen o foto personal.
            $("#fileup").attr("disabled",true);		// Botón Subir Imagen Portafolio
            $("#SubirImagen").attr("disabled",true);		// Botón Subir Imagen Portafolio
            // OCULTAR TAB HISTORIAL Y PORTAFOLIO.
            $("#historial-tab").hide();
            $("#digitalizacion-tab").hide();
        }
        if($('#MenuTab').val() == '0'){
            //$("#").attr('readonly',true);
            $("#txtnombres").attr('readonly',true);
            $("#txtapellido").attr('readonly',true);
            $("#txtfechanacimiento").attr('readonly',true);
            $("#txtTipoSangre").attr('readonly',true);
            $("#txtConyuge").attr('readonly',true);
            $("#direccion").attr('readonly',true);
            $("#telefono_fijo").attr('readonly',true);
            $("#correo_electronico").attr('readonly',true);
            $("#telefono_movil").attr('readonly',true);
            /*$("#").attr('readonly',true);*/
            $("#lstEstudios").attr('disabled',true);
            $("#lstVivienda").attr('disabled',true);
            $("#lstAfp").attr('disabled',true);
            $("#lstEstudios").attr('disabled',true);

            $("#lstestatus").attr('disabled',true);
            $("#lstgenero").attr('disabled',true);
            $("#lstEstadoCivil").attr('disabled',true);
            $("#lstdepartamento").attr('disabled',true);
            $("#lstmunicipio").attr('disabled',true);
            $("#ActivarEditarCodigo").attr('disabled',true);
            $("#Guardar").attr('disabled',true);
            $("#Guardar").hide();
            $("#empresa-tab").hide();
            $("#docuemntos-tab").hide();
        }
	});
//////////////////////////////////////////////////////////////////////////////////
/* INICIO DE LA FUNCION PARA NUEVO REGISTRO */
//////////////////////////////////////////////////////////////////////////////////
var NuevoRegistro = function(){
    // Asignamos valor a la variable acción y asignar valor a accion
        accion = "GenerarCodigoNuevo";
    // Generar el Código Nuevo.
        $.post("php_libs/soporte/NuevoEditarPersonal.php", {accion_buscar: accion, ann: ann},
           function(data){
           // Información de la Tabla Datos Personal.
            $("#txtcodigo").val(data[0].codigo_nuevo);
            toastr["info"]('Nuevo Código: ' + data[0].codigo_nuevo + ' Generado', "Sistema");
        }, "json");
        // IMAGEN
        $(".card-img-top").attr("src", "../acomtus/img/NoDisponible.jpg");	
    LimpiarCampos();
    PasarFoco();
};
//////////////////////////////////////////////////////////////////////////////////
/* INICIO DE LA FUNCION PARA MOSTRAR LOS DATOS DEL ALUMNO */
//////////////////////////////////////////////////////////////////////////////////
	var listar = function(){
		// DETARMINAR QUE SE VA EJECUTAR.	
			$.post("php_libs/soporte/NuevoEditarPersonal.php",  { id_x: id_, accion: 'BuscarPorId' },
				function(data){
				// Cargar valores a los objetos Llenar el formulario con los datos del registro seleccionado.
				// Modificar label en la tabs-8.
					$("label[for='LblNombre']").text(data[0].nombre_empleado);
					$("label[for='LblFianza']").text('$ ' + data[0].saldo_fianza);
					$("label[for='LblPrestamo']").text('$ ' + data[0].saldo_prestamo);
                // datos para el card TITLE - INFORMACIÓN GENERAL
					$('#txtId').val(id_);
					$('#txtcodigo').val(data[0].codigo);
                    $('#txtnombres').val(data[0].nombre);
                    $('#txtapellido').val(data[0].apellido);
					
                    $('#txtfechanacimiento').val(data[0].fecha_nacimiento);
                    $('#txtedad').val(data[0].edad);
                    if(data[0].edad==''){
                        var edades = 0;
                        $('#txtedad').val(edades);    
                    }
					$('#lstgenero').val(data[0].codigo_genero);
					$('#lstEstadoCivil').val(data[0].codigo_estado_civil);
					$('#txtTipoSangre').val(data[0].tipo_sangre);
					$('#lstEstudios').val(data[0].codigo_estudio);
					$('#lstVivienda').val(data[0].codigo_vivienda);
					$('#lstAfp').val(data[0].codigo_afp);
					$('#txtConyuge').val(data[0].nombre_conyuge);

				// ACTUALIZAR DEPARTAMENTO Y MUNICIPIO.
						listar_municipio(data[0].codigo_departamento, data[0].codigo_municipio)
						$('#lstdepartamento').val(data[0].codigo_departamento);
								$('#lstmunicipio').val(data[0].codigo_municipio);
					//
					$('#direccion').val(data[0].direccion);
                    $('#telefono_fijo').val(data[0].telefono_fijo);
                    $('#telefono_movil').val(data[0].telefono_movil);
                    $('#correo_electronico').val(data[0].correo_electronico);
                    $('#lstCargo').val(data[0].codigo_cargo);
					if(data[0].codigo_cargo == ''){
                        var codigo_cargo = 54;
                        $('#lstCargo').val(codigo_cargo);
                    }
					$('#txtFechaIngreso').val(data[0].fecha_ingreso);
					$('#txtFechaRetiro').val(data[0].fecha_retiro);
					$('#lstCargoDepartamento').val(data[0].cargo_departamento);
					$('#lstTaller').val(data[0].cargo_taller);
					if(data[0].cargo_taller == ''){
                        var cargo_taller = '09';
                        $('#lstTaller').val(cargo_taller);
                    }
					$('#lstRuta').val(data[0].codigo_ruta);
					$('#lstSocio').val(data[0].codigo_socio);
                    $('#txtCuentaAhorro').val(data[0].numero_cuenta);
                    
                    $('#PagoDiario').val(data[0].pago_diario);
                    $('#Salario').val(data[0].salario);


					$('#lstClaseLicencia').val(data[0].codigo_licencia);
					$('#txtNumeroLicencia').val(data[0].numero_licencia);
					$('#txtDui').val(data[0].dui);
					$('#txtIsss').val(data[0].isss);
					$('#txtNit').val(data[0].nit);
					$('#txtNup').val(data[0].afp);
					$('#txtComentario').val(data[0].comentario);

                    //$('#').val(data[0].);
				// FOTO DEL ALUMNO.
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
                }, "json");                				
	}; /* FINAL DE LA FUNCION LISTAR(); */
///////////////////////////////////////////////////////
// Validar Formulario, para posteriormente Guardar o Modificarlo.
 //////////////////////////////////////////////////////
	$('#formUsers').validate({
		ignore:"",
		rules:{
                txtnombres: {required: true, minlength: 4},
                txtapellido: {required: true, minlength: 4},
                txtcodigo: {required: true, minlength: 5, maxlength: 5},
				correo_electronico: {email: true},
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
		                
		            },
		            cache: false,
		            type: "POST",
		            dataType: "json",
		            url:"php_libs/soporte/NuevoEditarPersonal.php",
		            data:str + "&id=" + Math.random(),
		            success: function(response){
		            	// Validar mensaje de error
		            	if(response.respuesta == false){
                            toastr["error"](response.mensaje, "Sistema");
		            	}
		            	else{
                            toastr["success"](response.mensaje, "Sistema");
							//window.location.href = 'estudiantes.php';
                            }               
		            },
		        });
		    },
   });
///////////////////////////////////////////////////
// funcionalidad del botón que activa el c´doigo el formulario
///////////////////////////////////////////////////		
$('#ActivarEditarCodigo').click(function () {
	if ($(this).prop('checked')) {
		$("#txtcodigo").prop("readonly",false);
		//$("#txtcodigo").val("");
		$("#txtcodigo").focus();
	} else {
		$("#txtcodigo").prop("readonly",true);				
			// Asignamos valor a la variable acción y asignar valor a accion
				accion = $('#accion_buscar').val();
			// Generar el Código Nuevo.
				$.post("php_libs/soporte/NuevoEditarPersonal.php", {accion_buscar: accion, ann: ann},
							function(data){
									// Información de la Tabla Datos Personal.
										$("#txtcodigo").val(data[0].codigo_nuevo);
					}, "json");
	}
});
///////////////////////////////////////////////////
		// funcionalidad del botón txtcodigo si cambia el código
		///////////////////////////////////////////////////
		$("#txtcodigo").change(function(){
			accion = "BuscarCodigo";
			var codigo = $("#txtcodigo").val();
			var total = codigo.length;
			
			if(total < 5)
			{
                toastr["error"]("Código debe contener 5 caracteres.", "Sistema");
				    $("#txtcodigo").val("");
				    $("#txtcodigo").focus();			
			}
			else{
			// Generar el Código Nuevo.
			$.post("php_libs/soporte/NuevoEditarPersonal.php", {accion_buscar: accion, codigo:codigo},
				       function(response){
						// Si el valor si existe compararlo con mensaje error.
                           if (response.respuesta == false) {
                                toastr["error"](response.mensaje, "Sistema");				
                           }else{
                                toastr["info"](response.mensaje, "Sistema");
                                $("#txtcodigo").val("");
								$("#txtcodigo").focus();
                           }                                                
                                
				}, "json");
			}
		});
}); // fin de la funcion principal ************************************/
		
// Pasar foco cuando seleccionar un encargado.
function PasarFoco()
   {
       $('#txtnombres').focus();
   }
function LimpiarCampos(){
    $('#txtnombres').val('');
    $('#txtapellido').val('');
}
///////////////////////////////////////////////////////////
// Convertir a mayúsculas cuando abandone el input.
////////////////////////////////////////////////////////////
   function conMayusculas(field)
   {
        field.value = field.value.toUpperCase();
   }
///////////////////////////////////////////////////////////
// cargar diferentes lsitados.
// FUNCION LISTAR CATALOGO PERFIL.
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
///////////////////////////////////////////////////////////
// cargar diferentes lsitados.
// FUNCION LISTAR CATALOGO PERFIL.
////////////////////////////////////////////////////////////
function listar_genero(){
    var miselect=$("#lstgenero");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_genero.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
///////////////////////////////////////////////////////////
// cargar diferentes lsitados.
// FUNCION LISTAR CATALOGO PERFIL.
////////////////////////////////////////////////////////////
function listar_estado_civil(){
    var miselect=$("#lstEstadoCivil");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_estado_civil.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
// FUNCION LISTAR CATALOGO ESTATUS
////////////////////////////////////////////////////////////
function listar_estatus(){
    var miselect=$("#lstestatus");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_estatus.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
// FUNCION LISTAR CATALOGO ESTUDIOS
////////////////////////////////////////////////////////////
function listar_estudios(){
    var miselect=$("#lstEstudios");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_nivel_escolaridad.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
// FUNCION LISTAR CATALOGO VIVIENDA
////////////////////////////////////////////////////////////
function listar_vivienda(){
    var miselect=$("#lstVivienda");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_tipo_vivienda.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
// FUNCION LISTAR CATALOGO VIVIENDA
////////////////////////////////////////////////////////////
function listar_afp(){
    var miselect=$("#lstAfp");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_afiliado.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
// FUNCION LISTAR CARGO
////////////////////////////////////////////////////////////
function listar_cargo(){
    var miselect=$("#lstCargo");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_cargo.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
// FUNCION LISTAR DEPARTAMENTO CARGO
////////////////////////////////////////////////////////////
function listar_departamento_cargo(){
    var miselect=$("#lstCargoDepartamento");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_departamento_cargo.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
// FUNCION LISTAR DEPARTAMENTO CARGO
////////////////////////////////////////////////////////////
function listar_taller(){
    var miselect=$("#lstTaller");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_taller.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
// FUNCION LISTAR RUTA
////////////////////////////////////////////////////////////
function listar_ruta(){
    var miselect=$("#lstRuta");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_ruta.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
// FUNCION LISTAR SOCIO
////////////////////////////////////////////////////////////
function listar_socio(){
    var miselect=$("#lstSocio");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_socio.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}
// FUNCION LISTAR SOCIO
////////////////////////////////////////////////////////////
function listar_licencia(){
    var miselect=$("#lstClaseLicencia");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("includes/cargar_clase_licencia.php",
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
            }
    }, "json");    
}