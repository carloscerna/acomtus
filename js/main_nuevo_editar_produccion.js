// id de user global
var id_ = 0;
var buscartodos = "";
var accion = 'noAccion';
var tableA = "";
var GlobalDesde = 0; 
var GlobalHasta = 0;
var GlobalDesdeM = 0; 
var GlobalHastaM = 0;
var codigo_produccion = 0;
var IdEditarTiqueteDesde = 0;
var TiqueteHasta = 0;
var TiqueteDesde = 0;
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
	////CUANDO CAMBIA LA SERIE. ////////////////////////////////////////////////////	  
	$('#lstSerie').on('change', function () {
        var id_ = $("#lstSerie").val();
        
        $.post("php_libs/soporte/ProduccionBuscar.php", {accion_buscar: 'BuscarSerieId', id_: id_},
            function(data) {
                $('#PrecioPublico').val(data[0].precio_publico);
                $('#Existencia').val(data[0].existencia);
                $('#CodigoTiqueteColor').val(data[0].codigo_tiquete_color);
        }, "json");    
    });	
    //// ACCION . ////////////////////////////////////////////////////	  
		if($("#accion").val() == "EditarRegistro"){
			// Variables Principales.
			id_ = $("#id_user").val();
			accion = "BuscarControlIngreso";
            codigo_personal = $("#cp").val();
			// cambiar texto de label y enlace.
			$("label[for='txtEdicionNuevo']").text("Producción | Devolución ");
            $("label[for='iEdicionNuevo']").text("Edición");
            // Llamar a la función listar.
            // desactivar fieldset ingreso o devolucion.
            $('#FieldTiquete').show();
            //
            ///////////////////////////////////////////////////////////////			
			// Inicio del Ajax. BUSCAR VALORES PARA FECHA, RUTA, ID, JORNADA Y RUTA..
            ///////////////////////////////////////////////////////////////
            // DETARMINAR QUE SE VA EJECUTAR.	
            $.post("php_libs/soporte/NuevoEditarProduccion.php",  { id_x: id_, accion: 'BuscarPorId', codigo_personal: codigo_personal },
            function(data){
            // Cargar valores a los objetos Llenar el formulario con los datos del registro seleccionado.
            // datos para el card TITLE - INFORMACIÓN GENERAL
                $('#id_user').val(data[0].id_);
                $('#NumeroCorrelativo').val(data[0].id_);
                $('#FechaProduccion').val(data[0].fecha);
                listar_ruta(data[0].codigo_ruta);
                listar_jornada(data[0].codigo_jornada);
                listar_serie(data[0].codigo_inventario_tiquete);
                
            }, "json");    
            ///////////////////////////////////////////////////////////////			
			// Inicio del Ajax. MOSTRAR TABLA PRODUCCIÓN.
            ///////////////////////////////////////////////////////////////
            $.ajax({
                beforeSend: function(){
                    $('#listadoAsignacionOk').empty();
                },
                cache: false,
                type: "POST",
                dataType: "json",
                url:"php_libs/soporte/NuevoEditarProduccion.php",
                data: "accion=" + accion + "&NumeroCorrelativo=" + id_ + "&codigo_personal=" + codigo_personal + "&id__=" +  Math.random(),
                success: function(response){
                    // Validar mensaje de error
                    if(response.respuesta == false){
                        toastr["error"](response.mensaje, "Sistema");
                        $('#listadoAsignacionOk').append(response.contenido);
                        $("#DesdeAsignado").focus().select();
                    }
                    else{
                        toastr["success"](response.mensaje, "Sistema");
                        $('#listadoAsignacionOk').append(response.contenido);
                        // pasar valores a variables.
                        //$("#NumeroCorrelativo").val(response.id_produccion);
                        $("#DesdeAsignado").val(GlobalDesde);
                        // pasar foco.
                        $("#DesdeAsignado").focus().select();
                        $("#HastaAsignado").val("");
                        // cambiar el valor del ingreso.
                        $("label[for='LblIngreso']").text('Total Entregado $ ' + response.totalIngreso);
                        $("label[for='LblCantidad']").text('Total Tiquete: ' + response.cantidad_tiquete);
                        $("label[for='LblCantidadTalonarios']").text('Total Talonarios: ' + response.CantidadTalonarios);
                        // desactivar sólo readonly fecha y select.
                        $("#FechaProduccion").prop("readonly", true);
                        $("#lstRuta").prop("readonly", true);
                        $("#lstJornada").prop("readonly", true);
                        $("#lstSerie").prop("readonly", true);
                        }               
                },
            });
            // Cambiar a agregar nuevo. accion.
                accion = "ActualizarTemp";
                $("#accion").val(accion);
                $("#NumeroCorrelativo").val(id_);
                $("#id_user").val(id_);
            //
            //$("#DesdeAsignado").focus().select();
		}
		if($("#accion").val() == "AgregarNuevoTemp"){
            NuevoRegistro();
			// Variables accion para guardar datos.
			accion = $("#accion").val();
			// cambiar texto de label y enlace.
			$("label[for='txtEdicionNuevo']").text("Producción | Control de Tiquete Entregado ");
            $("label[for='iEdicionNuevo']").text("Agregar");
            // desactivar fieldset ingreso o devolucion.
            $('#FieldsetDevolucion').prop("disabled", true);
            //
            $("#FechaProduccion").focus().select();
        }
        // configurar el Select2
            $('#lstPersonal').select2({
                theme: "classic"
            });
            $('#lstUnidadTransporte').select2({
                theme: "classic"
            });
        // LLAMAR A LA TABLA PERSONAL.
            listar_jornada();
            listar_ruta();
            listar_serie();
	});
//////////////////////////////////////////////////////////////////////////////////
/* INICIO DE LA FUNCION PARA NUEVO REGISTRO */
//////////////////////////////////////////////////////////////////////////////////
var NuevoRegistro = function(){
    // codigo personal que servirá como filtro para eliminar, actulizar o guardar.
    codigo_personal = $("#cp").val();
    LimpiarCampos();
    PasarFoco();
    // ejecutar Ajax.. ACTUALIZA5 INDICADORES DE MATRICULA.
    $.ajax({
    cache: false,                     
    type: "POST",                     
    dataType: "json",                     
    url:"php_libs/soporte/NuevoEditarProduccion.php",                     
    data: {                     
            accion_buscar: 'UltimoRegistro', codigo_personal: codigo_personal,
            },                     
    success: function(response) {                     
            if (response.respuesta === true) {                     
                // Pasar valor a CodigoProduccion					                  
                $("#NumeroCorrelativo").val(response.contenido);
            }
    }                     
    });
};
/// EVENTOS JQUERY Y BOTON NUEVO REGISTRO. CALCULO Y OTROS
// CUANDO EL REGISTRO SE VA EDITAR UN SOLO TALONARIO.
$("#DesdeAsignadoPartial01").on('keyup', function (e) {
    var keycode = e.keyCode || e.which;
    console.log(keycode);

      // CALCULOS -tecla escape
      if (keycode == 27) {
        // Pasar foco.
            $("#Partial").hide();
        //
            $("#DesdeAsignado").show();
            $("#DesdeAsignado").focus().select();
      }
        // CALCULOS -tecla enter
        // Solamente actualizar tabla desde.
            if (keycode == 13) {
                ///////////////////////////////////////////////////////////////			
                // Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
                ///////////////////////////////////////////////////////////////
                // Valor
                   // var TiqueteDesde = $("#DesdeAsignadoPartial01").val();
                    codigo_produccion = $("#NumeroCorrelativo").val();
                    CalcularIncrementoModificarTalonario(this.value);
                    CalcularDesdeHastaAsignadoModificarTalonario(this.value);
                    CantidadTiqueteAsignado = $("#CantidadTiqueteAsignado").val();
                    TotalAsignado = $("#TotalAsignado").val();
                    TiqueHasta = $("#HastaAsignado").val();
                    TiqueteDesde = this.value;
                        // VALIDAR EL VALOR.VALUE NO SEA MAYOR QUE GLOBAL DESDE Y GLOBAL HASTA
                    if(this.value < parseInt(GlobalDesdeM)){
                        toastr["warning"]("Desde es MENOR", "Sistema");
                        $("#DesdeAsignadoPartial01").focus().select();
                    }else if(this.value > parseInt(GlobalHastaM)){
                        toastr["warning"]("Hasta es MAYOR", "Sistema");
                        $("#DesdeAsignadoPartial01").focus().select();
                    }else{
                        // Ajax
                        $.ajax({
                            beforeSend: function(){
                                $('#listadoAsignacionOk').empty();
                            },
                            cache: false,
                            type: "POST",
                            dataType: "json",
                            url:"php_libs/soporte/NuevoEditarProduccion.php",
                            data: "TotalAsignado=" + TotalAsignado + "&CantidadTiqueteAsignado=" + CantidadTiqueteAsignado + "&TiqueteHasta=" + TiqueteHasta + "&IdEditarTiqueteDesde=" + IdEditarTiqueteDesde + "&accion_buscar=" + 'ActualizarTalonario' + "&TiqueteDesde=" + TiqueteDesde + "&iid="  + Math.random() + "&codigo_produccion=" + codigo_produccion,
                            success: function(response){
                                // Validar mensaje de error
                                if(response.respuesta == true){
                                    // Pasar foco.
                                        $("#Partial").hide();
                                    //
                                        $("#DesdeAsignado").show();
                                        $("#DesdeAsignado").focus().select();
                                    //
                                        toastr["success"](response.mensaje, "Sistema");
                                    //
                                        $('#listadoAsignacionOk').append(response.contenido);
                                }
                                if(response.respuesta == false){
                                // Pasar foco.
                                $("#Partial").hide();
                                //
                                    $("#DesdeAsignado").show();
                                    $("#DesdeAsignado").focus().select();
                                }
                            },
                        });
                    }
            }
});      

/// EVENTOS JQUERY Y BOTON NUEVO REGISTRO. CALCULO Y OTROS
$("#DesdeAsignado").on('keyup', function (e) {
    var keycode = e.keyCode || e.which;
    console.log(keycode);

    

      // CUANDO EL VALOR ESTE VACIO O SEA IGUAL A CERO
    /*  if(contar.trim() == 2){
        if (keycode == 48 || keycode == 32 || $("#DesdeAsignado").val()== null) {
            $("#DesdeAsignado").val("");
            $("#DesdeAsignado").focus();
            return;
         }
      }*/
      
      // CALCULOS -tecla enter
      if (keycode == 13) {
          //validar si el valor solo es cero.
          if(this.value == 0){
            $("#DesdeAsignado").focus();
          }else{
            CalcularIncremento(this.value);
            CalcularDesdeHastaAsignado(this.value);
            $("#formUsers").submit();
          }
      }
      // IMPRIMIR. f12
    if (keycode == 123) {
        // Limpiar datos
        codigo_produccion = $('#NumeroCorrelativo').val();
            if(codigo_produccion != 0){
            // Ejecutar Informe
            varenviar = "/acomtus/php_libs/reportes/control_tiquete_ingresos.php?codigo_produccion="+codigo_produccion;
            // Ejecutar la función abre otra pestaña.
                AbrirVentana(varenviar);   
            }
    }
      // LIMPIAR ACCION Y CORRELATIVO PARA UNO NUEVO. flecha hacia arriba.
      if (keycode === 38){
        var r = confirm("Guardar Control de Ingresos");
        if (r == true) {
         // NUEVA PRODUCCIÓN.
			 // Limpiar datos
             codigo_produccion = $('#NumeroCorrelativo').val();
             codigo_ruta = $("#lstRuta").val();
             codigo_jornada = $("#lstJornada").val();
             codigo_serie = $("#lstSerie").val();
             codigo_tiquete_color = $("#CodigoTiqueteColor").val();
             fecha = $("#FechaProduccion").val();
             codigo_personal = $("#cp").val();
            //
            $("#DesdeAsignado").val("0");

             if($("#accion").val() == "AgregarNuevoTemp"){
                accion = "GuardarControlIngreso";
             }else{
                accion = "ActualizarControlIngreso";    
             }
             
             if(codigo_produccion != 0){
                ///////////////////////////////////////////////////////////////			
                // Inicio del Ajax. guarda o Actualiza los datos del Formualrio.
                ///////////////////////////////////////////////////////////////
                $.ajax({
                    beforeSend: function(){
                        //$('#myModal').modal('show');
                    },
                    cache: false,
                    type: "POST",
                    dataType: "json",
                    url:"php_libs/soporte/NuevoEditarProduccion.php",
                    data: "NumeroCorrelativo=" + codigo_produccion + "&accion=" + accion + "&codigo_tiquete_color=" + codigo_tiquete_color + "&codigo_ruta="+ codigo_ruta + "&codigo_jornada="+ codigo_jornada + "&codigo_serie="+ codigo_serie +  "&FechaProduccion=" + fecha + "&codigo_personal=" + codigo_personal + "&id=" + Math.random(),
                    success: function(response){
                        // Validar mensaje de error
                        if(response.respuesta == true){
                           // NuevoRegistro();
                           //$('#myModal').modal('hide');
                        }
                        if(response.respuesta == false){
                            //$('#myModal').modal('hide');
                         }
                    },
                });
                //
                    NuevoRegistro();
                 // Limpiar datos
                 $('#listadoAsignacionOk').empty();
                 //$("#DesdeAsignado").val("");
                 $("#DesdeAsignado").focus().select();
                 $("#HastaAsignado").val("");
             // activar readonly fecha y select.
                $("#FechaProduccion").prop("disabled", false);
                 $("#FechaProduccion").prop("readonly", false);
                 $("#lstRuta").prop("readonly", false);
                 $("#lstJornada").prop("readonly", false);
                 $("#lstSerie").prop("readonly", false);
                 GlobalDesde = 0; 
                 GlobalHasta = 0;
 
                 accion = "AgregarNuevoTemp";	// variable global
                 id_ = 0;
                 //$("#NumeroCorrelativo").val(id_);
                 $("#accion").val(accion);
                 $("#id_user").val(id_);
                 toastr["info"]("Control de Ingresos, Guardado.", "Sistema");
                 // cambiar el valor del ingreso.
                 $("label[for='LblIngreso']").text('Total Entregado $ ');
                 $("label[for='LblCantidad']").text('Total Tiquete: ' );
                 $("label[for='LblCantidadTalonarios']").text('Total Talonarios: ');
                 //
                // pasar foco.
                $("#DesdeAsignado").focus().select();
                $("#HastaAsignado").val("");
             }
        } else {
          txt = "You pressed Cancel!";
        }
      }
});
  
$('#goFinaliar').on('click', function(){
    // activar readonly fecha y select.
        $("#FechaProduccion").prop("readonly", false);
        $("#lstRuta").prop("readonly", false);
        $("#lstJornada").prop("readonly", false);
        $("#lstSerie").prop("readonly", false);
        GlobalDesde = 0; 
        GlobalHasta = 0;

        accion = "AgregarNuevoTemp";	// variable global
        id_ = 0;
        $("#NumeroCorrelativo").val(id_);
        $("#accion").val(accion);
        $("#id_user").val(id_);
        // cambiar el valor del ingreso.
        $("label[for='LblIngreso']").text('Total Entregado $ ');
        $("label[for='LblCantidad']").text('Total Tiquete: ');
        $("label[for='LblCantidadTalonarios']").text('Total Talonarios: ');
        // ejecutar Ajax.. ACTUALIZAR ULITMO REGISTROS
        $.ajax({
            cache: false,                     
            type: "POST",                     
            dataType: "json",                     
            url:"php_libs/soporte/NuevoEditarProduccion.php",                     
            data: {                     
                    accion_buscar: 'UltimoRegistro',
                    },                     
            success: function(response) {                     
                    if (response.respuesta === true) {                     
                        // Pasar valor a CodigoProduccion					                  
                        $("#NumeroCorrelativo").val(response.contenido);
                    }                
            }                     
            });
        // Limpiar datos
        $('#listadoAsignacionOk').empty();
        //$("#DesdeAsignado").val("");
        $("#DesdeAsignado").focus().select();
        $("#HastaAsignado").val("");
});
$('#goImprimir').on('click', function(){
    // Limpiar datos
        codigo_produccion = $('#NumeroCorrelativo').val();
    // Ejecutar Informe
        varenviar = "/acomtus/php_libs/reportes/control_tiquete_ingresos.php?codigo_produccion="+codigo_produccion;
    // Ejecutar la función abre otra pestaña.
        AbrirVentana(varenviar);   
});
//	FUNCION que al dar clic buscar el registro para posterior mente abri una
// IMPRIMIR PRODUCCIÓN
///////////////////////////////////////////////////////////////////////////////	  
$('#goVerImprimirControles').on( 'click', function () {
	window.location.href = 'ProduccionImprimir.php';
});
//////////////////////////////////////////////////////////////////////////////////
/* VER #CONTROLES CREADOS */
//////////////////////////////////////////////////////////////////////////////////
$('#goUltimoControles').on('click', function(){
        fecha = $('#FechaProduccion').val();
        // ejecutar Ajax.. ACTUALIZA5 INDICADORES DE MATRICULA.
        $.ajax({
            beforeSend: function(){  
                $('#listadoVerControlesOk').empty();
            },
        cache: false,                     
        type: "POST",                     
        dataType: "json",                     
        url:"php_libs/soporte/ProduccionBuscar.php",                     
        data: {                     
                accion_buscar: 'VerUltimasProducciones', fecha: fecha,
                },                     
        success: function(response) {                     
                if (response.respuesta === true) {                     
                    // lIMPIAR LOS VALORES DE LAS TABLAS.                     
                    $('#listadoVerControlesOk').append(response.contenido);						                  
                        // Abrimos el Formulario Modal y Rellenar For.
                            $('#VentanaVerProduccion').modal("show");
                }                
        }                     
        });
});

///////////////////////////////////////////////////////
// Validar Formulario, para posteriormente Guardar o Modificarlo.
 //////////////////////////////////////////////////////
	$('#formUsers').validate({
		ignore:"",
		rules:{
                lstSerie: {required: true},
                DesdeAsignado: {required: true},
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
                            $("#DesdeAsignado").focus().select();
		            	}
		            	else{
                            toastr["success"](response.mensaje, "Sistema");
                            $('#listadoAsignacionOk').append(response.contenido);
                            // pasar valores a variables.
                            $("#NumeroCorrelativo").val(response.id_produccion);
                            $("#DesdeAsignado").val(GlobalDesde);
                            // cambiar el valor del ingreso.
                            $("label[for='LblIngreso']").text('Total Entregado $ ' + response.totalIngreso);
                            $("label[for='LblCantidad']").text('Total Tiquete: ' + response.cantidad_tiquete);
                            $("label[for='LblCantidadTalonarios']").text('Total Talonarios: ' + response.CantidadTalonarios);
                            // desactivar sólo readonly fecha y select.
                            $("#FechaProduccion").prop("readonly", true);
                            $("#lstRuta").prop("readonly", true);
                            $("#lstJornada").prop("readonly", true);
                            $("#lstSerie").prop("readonly", true);
                            // pasar foco.
                            $("#HastaAsignado").val("");
                            $("#DesdeAsignado").focus().select();
                            }               
		            },
		        });
		    },
   });

///////////////////////////////////////////////////////////////////////////////	  
// BLOQUE EXTRAER INFORMACIÓN DEL REGISTROS (PRODUCCION ASIGNADO)
$('body').on('click','#listadoVerControles a',function (e){
	e.preventDefault();
// DATA-ACCION Y HREF
    id_ = $(this).attr('href');
    accionAsignacion = $(this).attr('data-accion');
// EDTIAR REGISTRO.
	if(accionAsignacion  == 'VerProduccion'){	
        accion = 'BuscarControlIngreso';
        codigo_personal = $("#cp").val();
        $("#accion").val(accion);
    // Variables Principales.
    //accion = $("#accion").val();
    // cambiar texto de label y enlace.
    $("label[for='txtEdicionNuevo']").text("Producción | Actualizar Control de Tickets Entregado");
    $("label[for='iEdicionNuevo']").text("Edición");
    // Llamar a la función listar.
    // desactivar fieldset ingreso o devolucion.
    $('#FieldTiquete').show();
    //
    ///////////////////////////////////////////////////////////////			
    // Inicio del Ajax. BUSCAR VALORES PARA FECHA, RUTA, ID, JORNADA Y RUTA..
    ///////////////////////////////////////////////////////////////
    // DETARMINAR QUE SE VA EJECUTAR.	
    $.post("php_libs/soporte/NuevoEditarProduccion.php",  { id_x: id_, accion: 'BuscarPorId' },
    function(data){
    // Cargar valores a los objetos Llenar el formulario con los datos del registro seleccionado.
    // datos para el card TITLE - INFORMACIÓN GENERAL
        $('#id_user').val(data[0].id_);
        $('#NumeroCorrelativo').val(data[0].id_);
        $('#FechaProduccion').val(data[0].fecha);
        listar_ruta(data[0].codigo_ruta);
        listar_jornada(data[0].codigo_jornada);
        listar_serie(data[0].codigo_inventario_tiquete);
        
    }, "json");    
    ///////////////////////////////////////////////////////////////			
    // Inicio del Ajax. MOSTRAR TABLA PRODUCCIÓN.
    ///////////////////////////////////////////////////////////////
    $.ajax({
        beforeSend: function(){
            $('#listadoAsignacionOk').empty();
        },
        cache: false,
        type: "POST",
        dataType: "json",
        url:"php_libs/soporte/NuevoEditarProduccion.php",
        data: "accion=" + accion + "&NumeroCorrelativo=" + id_ + "&id__=" +  Math.random() + "&codigo_personal=" + codigo_personal,
        success: function(response){
            // Validar mensaje de error
            if(response.respuesta == false){
                toastr["error"](response.mensaje, "Sistema");
                $('#listadoAsignacionOk').append(response.contenido);
                $("#DesdeAsignado").focus().select();
            }
            else{
                toastr["success"](response.mensaje, "Sistema");
                $('#listadoAsignacionOk').append(response.contenido);
                // pasar valores a variables.
                //$("#NumeroCorrelativo").val(response.id_produccion);
                $("#DesdeAsignado").val(GlobalDesde);
                // pasar foco.
                $("#DesdeAsignado").focus().select();
                $("#HastaAsignado").val("");
                // cambiar el valor del ingreso.
                $("label[for='LblIngreso']").text('Total Entregado $ ' + response.totalIngreso);
                $("label[for='LblCantidad']").text('Total Tiquete: ' + response.cantidad_tiquete);
                $("label[for='LblCantidadTalonarios']").text('Total Talonarios: ' + response.CantidadTalonarios);
                // desactivar sólo readonly fecha y select.
                $("#FechaProduccion").prop("readonly", true);
                $("#lstRuta").prop("readonly", true);
                $("#lstJornada").prop("readonly", true);
                $("#lstSerie").prop("readonly", true);
                }               
        },
    });
    // Cambiar a agregar nuevo. accion.
        accion = "ActualizarTemp";
        $("#accion").val(accion);
        $("#NumeroCorrelativo").val(id_);
        $("#id_user").val(id_);
    //
    // Abrimos el Formulario Modal y Rellenar For.
        $('#VentanaVerProduccion').modal("hide");
	}else if(accionAsignacion  == 'VerEliminarProduccion'){	
        fecha = $('#FechaProduccion').val();
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
                    // ejecutar Ajax.. 
                    $.ajax({
                        beforeSend: function(){  
                            $('#listadoVerControlesOk').empty();
                        },
                    cache: false,                     
                    type: "POST",                     
                    dataType: "json",                     
                    url:"php_libs/soporte/ProduccionBuscar.php",                     
                    data: {                     
                            accion_buscar: 'VerEliminarProduccion', codigo_produccion: id_, fecha: fecha,
                            },                     
                    success: function(response) {                     
                            if (response.respuesta === true) {                     
                                // lIMPIAR LOS VALORES DE LAS TABLAS.                     
                                $('#listadoVerControlesOk').append(response.contenido);		
                                    toastr["error"](response.mensaje, "Sistema");				                  
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
                'Su Archivo no ha sido Eliminado :)',
                'error'
            )
            }
        })
    }   // FINAL DEL IF DE LA CONSULTA QUE PROVIENE DE LA TABLA
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
		// separar cadena
		//console.log(Id_Editar_Eliminar);
		var partial = Id_Editar_Eliminar.split("#");
		// DAR LOS VALORES A LOS RESPECTIVOS OBJETOS
        IdEditarTiqueteDesde = partial[0];  // VALOR DEL ID DE PRODUCCION ASIGNADO TEMP
		//$("#CodigoProduccionAsignacion").val(partial[0]);
		$("#CodigoProduccion").val(partial[1]);
		// pasar valores AL PARTIAL.
			var partial_desde = partial[2];
			var partial_1 = partial_desde.substr(partial_desde.length-2);
			var partial_2 = partial_desde.length - 2;	// le quito siempre dos al numero total de caracteres.
			var partial_3 = partial_desde.substr(0, partial_2);
        // convertir a number.
            partial_desde = Number(partial_desde);
			partial_1 = Number(partial_1);
			partial_2 = Number(partial_2);
			partial_3 = Number(partial_3);
            IdEditarTiqueteHasta = Number(partial_3);
        //
            $("#DesdeAsignado").hide();
		// Definir Rango para Editar sólamente el rango seleccionado.
		    GlobalDesde = partial[2];
		    GlobalHasta = partial[3];
        //  validar valores a modificar.
            GlobalDesdeM = partial[2];
		    GlobalHastaM = partial[3];
        // cambiar valores del spam
            $("#TextoModificarTalonario").show();
        // Pasar foco.
            $("#Partial").show();
        //
			$("#DesdeAsignadoPartial01").val(partial_desde);	// los primero digitos del 0 al length - 2
        //
            $("#DesdeAsignadoPartial01").focus().select();
        //
            toastr["success"]("Editar Talonario.", "Sistema.");
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
				url:"php_libs/soporte/NuevoEditarProduccion.php",
				data: "id_=" + Id_Editar_Eliminar + "&accion_buscar=" + accionAsignacion,
				success: function(response){
					// Validar mensaje de error proporcionado por el response. contenido.
					if(response.respuesta == false){
                        toastr["error"](response.mensaje, "Sistema");
                            $('#listadoAsignacionOk').append(response.contenido);
					}
					else{
						toastr["info"](response.mensaje, "Sistema");
                            $('#listadoAsignacionOk').append(response.contenido);
                                // pasar foco.
                                $("#DesdeAsignado").focus().select();
                                $("#HastaAsignado").val("");
                                // cambiar el valor del ingreso.
                                $("label[for='LblIngreso']").text('Total Entregado $ ' + response.totalIngreso);
                                $("label[for='LblCantidad']").text('Total Tiquete: ' + response.cantidad_tiquete);
                                $("label[for='LblCantidadTalonarios']").text('Total Talonarios: ' + response.CantidadTalonarios);
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
	}else if(accionPortafolio == ""){
		// paginación buscar por cóidog Personal.
			
	}
});

//////////////////////////////////////////////////////////////////////////////////
/* BUSQUEDA DE CONTROL POR NUMERO DE TIQUETE. */
//////////////////////////////////////////////////////////////////////////////////
$('#goBuscarPorTiquete').on('click', function(){
	//
	$("#BusquedaNumeroControl").val('');
	$("#BusquedaFechaControl").val('');
	$("#BusquedaPersonalControl").val('');
	$("#BusquedaRutaControl").val('');
	$("#BusquedaJornadaControl").val('');
	$("#BusquedaUnidadPlacaControl").val('');
	$("#BusquedaNumeroVueltasControl").val('');
	$("#BusquedaTotalIngresoControl").val('');
	$("#BusquedaEstatusControl").val('');
	//
	$("#BusquedaNumerotiquete").val('');
	//
    $('#VentanaBuscarPorTiquete').modal("show");
    // listar serie tiquete.
    listar_serie_tiquete();
});
//
// RELLENAR LA TABLA TIENEN EN CONTROL
// fecha y N.º Control.
$('#BusquedaNumerotiquete').on('keyup', function(e){
	var keycode = e.keyCode || e.which;
	// Limipiar variables inputl text.
	this.value = (this.value + '').replace(/[^0-9]/g, '');
	// Al presionar la tecla Enter.
	if (keycode == 13) {
	  // Limpiar datos
		  numero_tiquete = $('#BusquedaNumerotiquete').val();
		  serie = $("#lstSerieBuscarTiquete").val();
		// ejecutar Ajax.. ACTUALIZA5 INDICADORES DE MATRICULA.
			$.ajax({
				beforeSend: function(){  
					$('#listadoTiqueteEnControlOk').empty();
					//
						$("#BusquedaNumeroControl").val('');
						$("#BusquedaFechaControl").val('');
						$("#BusquedaPersonalControl").val('');
						$("#BusquedaRutaControl").val('');
						$("#BusquedaJornadaControl").val('');
						$("#BusquedaUnidadPlacaControl").val('');
						$("#BusquedaNumeroVueltasControl").val('');
						$("#BusquedaTotalIngresoControl").val('');
						$("#BusquedaEstatusControl").val('');
				},
			cache: false,                     
			type: "POST",                     
			dataType: "json",                     
			url:"php_libs/soporte/ProduccionBuscar.php",                     
			data: {                     
					accion_buscar: 'BuscarPorTiqueteEnControl', numero_tiquete: numero_tiquete, serie: serie,
					},                     
			success: function(response) {                     
				if (response.respuesta === true) {                     
					// lIMPIAR LOS VALORES DE LAS TABLAS.                     
					$('#listadoTiqueteEnControlOk').append(response.contenido);		
						toastr["info"](response.mensaje, "Sistema");				                  
				}else{
                    toastr["error"](response.mensaje, "Sistema");				
                    $('#listadoTiqueteEnControlOk').append(response.contenido);                  
				}  
			}                     
			});
		//
	}
});
// BUSCAR DENTRO DE LA TABLA TIQUETE EN CONTROL.
$('body').on('click','#listadoTiqueteEnControl a',function (e){
	e.preventDefault();
	  // Limpiar datos
		  numero_control = $(this).attr('href');
		  accionAsignacion = $(this).attr('data-accion');
		  numero_tiquete = $('#BusquedaNumerotiquete').val();
		  serie = $("#lstSerieBuscarTiquete").val();
		// Concionales del accion.
		  if(accionAsignacion  == 'BuscarPorTiquete'){	
			// ejecutar Ajax.. ACTUALIZA5 INDICADORES DE MATRICULA.
			$.ajax({
				cache: false,                     
				type: "POST",                     
				dataType: "json",                     
				url:"php_libs/soporte/ProduccionBuscar.php",                     
				data: {                     
						accion_buscar: accionAsignacion, NumeroControl: numero_control, numero_tiquete: numero_tiquete, serie: serie,
						},                     
				success: function(data) {                     
						if (data[0].respuesta === true) {                     
							//
								$("#BusquedaNumeroControl").val(data[0].codigo_produccion);
								$("#BusquedaFechaControl").val(data[0].fecha);
								$("#BusquedaPersonalControl").val(data[0].nombre_personal);
								$("#BusquedaRutaControl").val(data[0].ruta);
								$("#BusquedaJornadaControl").val(data[0].jornada);
								$("#BusquedaUnidadPlacaControl").val(data[0].unidad);
								$("#BusquedaNumeroVueltasControl").val(data[0].numero_vueltas);
								$("#BusquedaTotalIngresoControl").val(data[0].total_ingreso);
								$("#BusquedaEstatusControl").val(data[0].estatus);
								//$("#").val(data[0].);
								toastr["info"](data[0].mensaje, "Sistema");				                  
						}                
						if (data[0].respuesta === false) {                     
							$("#BusquedaNumeroControl").val('');
							$("#BusquedaFechaControl").val('');
							$("#BusquedaPersonalControl").val('');
							$("#BusquedaRutaControl").val('');
							$("#BusquedaJornadaControl").val('');
							$("#BusquedaUnidadPlacaControl").val('');
							$("#BusquedaNumeroVueltasControl").val('');
							$("#BusquedaTotalIngresoControl").val('');
							$("#BusquedaEstatusControl").val('');
							//
							$("#BusquedaNumeroControl").val(data[0].codigo_produccion);
							$("#BusquedaFechaControl").val(data[0].fecha);
							$("#BusquedaPersonalControl").val(data[0].nombre_personal);
							$("#BusquedaRutaControl").val(data[0].ruta);
							$("#BusquedaJornadaControl").val(data[0].jornada);
							$("#BusquedaUnidadPlacaControl").val(data[0].unidad);
							$("#BusquedaNumeroVueltasControl").val(data[0].numero_vueltas);
							$("#BusquedaTotalIngresoControl").val(data[0].total_ingreso);
							$("#BusquedaEstatusControl").val(data[0].estatus);
							//$("#").val(data[0].);
							toastr["error"](data[0].mensaje, "Sistema");				   
						}
				}                     
				});
		  }
});
}); // fin de la funcion principal ************************************/
// Calcular desde - hasta con precio.
function CalcularDesdeHastaAsignadoModificarTalonario(valor) {
    var desde = valor;
    var hasta = $("#HastaAsignado").val();
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
// CALCULAR INCREMENTO.
function CalcularIncrementoModificarTalonario(valor) {
    var constante99 = 99;
    var constante100 = 100;
    var hasta = 0;
    var desde = ($("#DesdeAsignadoPartial01").val());
    // quitar comas
    desde = desde.replace(/,/g,"");
    // obtener los últimos dos valores
    lastDesde = desde.substr(desde.length - 2);
    //alert(lastDesde);
    // pasar la cantidad calculada a hasta
    if(lastDesde == '01'){
        hasta = Number(desde) + Number(constante99);
    }else if(lastDesde == "1"){
        hasta = Number(desde) + Number(constante99);
    }else if(lastDesde == "00"){
        hasta = Number(desde);
    }else{
        constante99 = constante100 - lastDesde;
        hasta = Number(desde) + Number(constante99);
    }
    // pasar la cantidad calculada a hasta
    $("#HastaAsignado").val(hasta); 
    // sumar 1, y cuando guarde pasarlo a desde.
    GlobalDesde = hasta + 1;

}
// Calcular desde - hasta con precio.
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
// CALCULAR INCREMENTO.
function CalcularIncremento(valor) {
    var constante99 = 99;
    var constante100 = 100;
    var hasta = 0;
    var desde = ($("#DesdeAsignado").val());
    // quitar comas
    desde = desde.replace(/,/g,"");
    // obtener los últimos dos valores
    lastDesde = desde.substr(desde.length - 2);
    //alert(lastDesde);
    // pasar la cantidad calculada a hasta
    if(lastDesde == '01'){
        hasta = Number(desde) + Number(constante99);
    }else if(lastDesde == "1"){
        hasta = Number(desde) + Number(constante99);
    }else if(lastDesde == "00"){
        hasta = Number(desde);
    }else{
        constante99 = constante100 - lastDesde;
        hasta = Number(desde) + Number(constante99);
    }
    // pasar la cantidad calculada a hasta
    $("#HastaAsignado").val(hasta); 
    // sumar 1, y cuando guarde pasarlo a desde.
    GlobalDesde = hasta + 1;

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
                if(codigo_jornada == data[i].codigo){
                    miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].codigo + '</option>');
                }else{
                    miselect.append('<option value="' + data[i].codigo + '">' + data[i].codigo + '</option>');
                }
            }
    }, "json");    
}
// TODAS LAS TABLAS VAN HA ESTAR EN PRODUCCIONBUSCAR.*******************
// FUNCION LISTAR TABLA catalogo_ruta
////////////////////////////////////////////////////////////
function listar_ruta(codigo_ruta){
    var miselect=$("#lstRuta");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/ProduccionBuscar.php", {accion_buscar: 'BuscarRuta'},
        function(data) {
            miselect.empty();
            for (var i=0; i<data.length; i++) {
                if(codigo_ruta == data[i].codigo){
                    miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + '</option>');
                }else{
                    miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + '</option>');
                }
            }
    }, "json");    
}
// TODAS LAS TABLAS VAN HA ESTAR EN PRODUCCIONBUSCAR.*******************
// FUNCION LISTAR TABLA catalogo_ruta
////////////////////////////////////////////////////////////
function listar_serie(codigo_inventario_tiquete){
    var miselect=$("#lstSerie");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/ProduccionBuscar.php", {accion_buscar: 'BuscarSerie'},
        function(data) {
            miselect.empty();
            miselect.append('<option value="">Seleccionar...</option>');
            for (var i=0; i<data.length; i++) {
                if(codigo_inventario_tiquete == data[i].codigo){
                    miselect.append('<option value="' + data[i].codigo + '" selected>' + data[i].descripcion + " - " + data[i].tiquete_color + " - " + data[i].precio_publico + '</option>');
                    $('#PrecioPublico').val(data[i].precio_publico);
                    $('#Existencia').val(data[i].existencia);
                    $('#CodigoTiqueteColor').val(data[i].codigo_tiquete_color);
                }else{
                    miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + " - " + data[i].tiquete_color + " - " + data[i].precio_publico +'</option>');
                    //$('#PrecioPublico').val(data[0].precio_publico);
                    //$('#Existencia').val(data[0].existencia);
                }
            }
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
// TODAS LAS TABLAS VAN HA ESTAR EN PRODUCCIONBUSCAR.*******************
// FUNCION LISTAR TABLA catalogo_ruta
////////////////////////////////////////////////////////////
function listar_serie_tiquete(){
    var miselect=$("#lstSerieBuscarTiquete");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/ProduccionBuscar.php", {accion_buscar: 'BuscarSerie'},
        function(data) {
            miselect.empty();
            miselect.append('<option value="">Seleccionar...</option>');
            for (var i=0; i<data.length; i++) {
                    miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + " - " + data[i].tiquete_color + " - " + data[i].precio_publico + '</option>');
                    //$('#PrecioPublico').val(data[0].precio_publico);
                    //$('#Existencia').val(data[0].existencia);
            }
    }, "json");    
}    
    function AbrirVentana(url)
    {
        window.open(url, '_blank');
        return false;
    }