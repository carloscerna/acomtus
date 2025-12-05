// =========================================================================
// VARIABLES GLOBALES
// =========================================================================
var id_ = 0;
var accion = "todos";
var fecha = "";
var codigo_personal = "";
var codigo_departamento_empresa = "";
var CodigoRuta = "";

// ESTADO DE LA INTERFAZ (Tu "Memoria RAM" visual)
var estadoUI = {
    tipo: 'laborado', // laborado, asueto, vacacion, descanso, permiso, falta
    duracion: '1T',   // 1T, 4H, 1.5T
    nocturnidad: false,
    tandaExtra: false, 
    horasExtras: 0
};

// =========================================================================
// INICIO (DOCUMENT READY)
// =========================================================================
$(function(){ 
    
    // Configuración inicial de fechas
    var now = new Date();                
    var day = ("0" + now.getDate()).slice(-2);
    var month = ("0" + (now.getMonth() + 1)).slice(-2);
    fecha = now.getFullYear()+"-"+(month)+"-"+(day);
    $('#FechaListadoEmpleados').val(fecha);

    // Cargar tabla al iniciar
    $(document).ready(function(){
        codigo_personal = $("#CodigoPersonal").val();
        codigo_departamento_empresa = $("#CodigoDepartamentoEmpresa").val();
        
        $('#listadoEmpleadosNomina').append("<tr><td>Buscando Registros... Por Favor Espere.</td></tr>"); 
        
        buscar_personal(codigo_personal);
        CodigoRuta = $("#CodigoRuta").val();
    });     

    // Cambio de fecha
    $("#FechaListadoEmpleados").change(function(){
        fecha = $('#FechaListadoEmpleados').val();
        $('#listadoEmpleadosNomina').empty().append("<tr><td>Buscando Registros...</td></tr>"); 
        buscar_personal(codigo_personal);
    });

    // =========================================================================
    // EVENTOS DEL MODAL (NUEVA LÓGICA TÁCTIL)
    // =========================================================================
    
    // 1. ABRIR MODAL Y CARGAR DATOS (EditarJornada)
    $('body').on('click','#listadoEmpleadosNomina a',function (e){
        e.preventDefault();
        
        if($(this).attr('data-accion') == 'editarAsistencia'){
            accion = 'EditarJornada';
            Id_Editar_Eliminar = $(this).attr('href'); // Viene en formato codificado
            $('#VentanaPunteo').modal("show");
            
            // Llamada AJAX para traer los datos guardados
            $.post("php_libs/soporte/Asistencia/PorNomina.php", { Id_: Id_Editar_Eliminar, accion: accion},
                function(data) {
                    // A. Llenar Datos Visuales Básicos
                    $("label[for=CodigoNombreEmpleado]").text(data[0].CodigoPersonal + " - " + data[0].NombreCompleto);
                    $("#FotoEmpleado").attr("src", data[0].Foto);
                    $("#ImagenJornada").attr("src", data[0].ImgJornada);
                    $('#Id_').val(data[0].Id_);

                    // B. Botón Borrar/Reiniciar
                    if (data[0].Id_ > 0) {
                        $("#btnEliminarPunteo").show().attr("data-id", data[0].Id_);
                    } else {
                        $("#btnEliminarPunteo").hide();
                    }

                    // C. "HIDRATAR" EL ESTADO UI DESDE LA BD
                    // Aquí leemos los códigos extraños y encendemos los botones correctos
                    let sep = data[0].CodigoJornadaTodasSeparador.split(".");
                    // Índices: 0=CJ, 1=CTL, 2=CJA, 3=CJV, 4=CJD, 5=CJE4H, 6=CJN, 7=HE
                    
                    mapearEstadoDesdeBD(sep);
                    
                    // D. NUEVO: APLICAR PERMISOS DE DEPARTAMENTO
                        aplicarPermisosDepartamento(); // <--- AGREGAR AQUÍ

                    calcularYActualizar();

                },"json");
        }
    });

    // 2. BOTÓN GUARDAR
    $("#goGuardarPunteo").on('click', function(){
        $("#formPunteo").submit();
    });

    // 3. BOTÓN REINICIAR / ELIMINAR (NUEVO)
    $("#btnEliminarPunteo").on("click", function() {
        let idAsistencia = $(this).attr("data-id");
        
        Swal.fire({
            title: '¿Reiniciar Asistencia?',
            text: "Se borrará todo lo ingresado hoy para este empleado.",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, borrar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.value) {
                $.post("php_libs/soporte/Asistencia/PorNomina.php", {
                    accion: 'EliminarAsistencia',
                    id_asistencia: idAsistencia
                }, function(response) {
                    if(response.respuesta) {
                        toastr.success("Registro reiniciado.");
                        $('#VentanaPunteo').modal("hide");
                        buscar_personal($("#CodigoPersonal").val()); // Recargar tabla
                    } else {
                        toastr.error(response.mensaje);
                    }
                }, "json");
            }
        })
    });

    // 4. VALIDACIÓN Y ENVÍO DEL FORMULARIO
    $('#formPunteo').validate({
        submitHandler: function(){  
            var str = $('#formPunteo').serialize();
            $.ajax({
                type: "POST",
                dataType: "json",
                url:"php_libs/soporte/Asistencia/PorNomina.php",
                data: str + "&id=" + Math.random(),
                success: function(response){
                    if(response.respuesta){
                        toastr["success"](response.mensaje, "Guardado");
                        $('#VentanaPunteo').modal("hide");
                        buscar_personal($("#CodigoPersonal").val());
                    } else {
                        toastr["error"](response.mensaje, "Error");
                    }      
                }
            });
        }
    });

}); // Fin Function

// =========================================================================
// LÓGICA DE NEGOCIO (EL CEREBRO DE LA ASISTENCIA)
// =========================================================================

// --- 1. INTERACCIÓN UI (Clics en botones) ---
// --- 1. FUNCIÓN AL HACER CLIC EN LOS BOTONES PRINCIPALES ---
function seleccionarTipo(tipo) {
    estadoUI.tipo = tipo;
    
    // UI: Marcar tarjeta activa visualmente
    $(".opcion-card").removeClass("active bg-primary text-white border-primary shadow");
    $(`[data-tipo='${tipo}']`).addClass("active bg-primary text-white border-primary shadow");
    
    // UI: Mostrar/Ocultar paneles
    // Para Motoristas (02), ocultamos la configuración al inicio si no es Laborado,
    // pero la mostramos si el usuario quiere agregar detalles (ej: Trabajo en Asueto).
    if(tipo === 'falta' || tipo === 'permiso') {
        $("#panel-configuracion").slideUp();
    } else {
        $("#panel-configuracion").slideDown();
    }

    // --- REINICIO DE ESTADO (CRUCIAL PARA TU SOLICITUD) ---
    // Si cambio de botón, limpio la duración para mostrar la imagen "Default" primero.
    if(tipo === 'laborado') {
        // Si es trabajo, por defecto marcamos 1 Tanda
        if(estadoUI.duracion === '' || estadoUI.duracion === null) setDuracion('1T');
    } else {
        // Para Asueto, Descanso, Vacación: LIMPIAMOS duración para ver la imagen base
        estadoUI.duracion = ''; 
        // Limpiamos visualmente los botones de duración
        $("[data-dur]").removeClass("active btn-secondary").addClass("btn-outline-secondary");
    }

    // Resetear extras
    estadoUI.tandaExtra = false;
    $("#btn-tanda-extra").hide();
    
    // Calcular inmediatamente para mostrar la imagen base
    calcularYActualizar();
}

// --- 2. EL MOTOR DE CÓDIGOS (AJUSTADO A TUS CÓDIGOS) ---
function calcularYActualizar() {
    // Valores Neutros (Base: 4.1.4.4.4.4.4)
    let CJ='4', CTL='1', CJA='4', CJV='4', CJD='4', CJE4H='4', CJN='4';
    let urlImagen = "../acomtus/img/Catalogo Jornada/Ninguno.jpg"; 
    let resumen = estadoUI.tipo.toUpperCase();

    // Nocturnidad General
    if(estadoUI.nocturnidad) CJN = '5';

    switch (estadoUI.tipo) {
        // ------------------------------------------------------------------
        // CASO: TRABAJO (Laborado)
        // ------------------------------------------------------------------
        case 'laborado':
            // Por defecto si no hay duración, asumimos 1T o esperamos selección
            if(estadoUI.duracion === '1T' || estadoUI.duracion === '') {
                CJ = '2'; // Código: 2144444
                urlImagen = "../acomtus/img/Catalogo Jornada/PuntoUnaTanda.jpg";
                
                if(estadoUI.horasExtras > 0) urlImagen = "../acomtus/img/Catalogo Jornada/PuntoUnaTanda" + estadoUI.horasExtras + "HE.jpg";
                if(estadoUI.nocturnidad) urlImagen = "../acomtus/img/Catalogo Jornada/PuntoUnaTandaYNocturnidad.jpg";
            }
            else if(estadoUI.duracion === '1.5T') {
                CJ = '3'; // Código: 3144444
                urlImagen = "../acomtus/img/Catalogo Jornada/UnaTandaYMedia.jpg";
                if(estadoUI.nocturnidad) urlImagen = "../acomtus/img/Catalogo Jornada/UnaTandaYMediaYNocturnidad.jpg";
            }
            else if(estadoUI.duracion === '4H') {
                CJ = '1'; // Código: 1144444
                urlImagen = "../acomtus/img/Catalogo Jornada/MediaTanda.jpg";
                
                // Variantes complejas de Media Tanda
                if(estadoUI.tandaExtra) {
                    CJE4H = '2'; // Media Tanda + 1T
                    urlImagen = "../acomtus/img/Catalogo Jornada/MediaTandaExtraUnaTanda.jpg";
                    if(estadoUI.horasExtras == 4) urlImagen = "../acomtus/img/Catalogo Jornada/MediaTandaExtraUnaTanda4HE.jpg";
                } 
                else if(estadoUI.horasExtras > 0) {
                    // Solo HE (Asumiendo nombre imagen, valida si existe)
                    urlImagen = "../acomtus/img/Catalogo Jornada/MediaTanda" + estadoUI.horasExtras + "HE.jpg"; 
                }
            }
            break;

        // ------------------------------------------------------------------
        // CASO: ASUETO (A)
        // ------------------------------------------------------------------
        case 'asueto':
            // DEFAULT: Código 41644444 (Asueto.jpg)
            if(estadoUI.duracion === '') {
                CJA = '6'; // El '6' en la 3ra posición genera el 416...
                urlImagen = "../acomtus/img/Catalogo Jornada/Asueto.jpg";
            }
            // SI TRABAJA EN ASUETO (Desglose posterior)
            else if(estadoUI.duracion === '1T') { 
                CJA = '2'; // Trabajo Asueto 1T
                urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoAsuetoUnaTanda.jpg";
            }
            else if(estadoUI.duracion === '4H') { 
                CJA = '1'; // Trabajo Asueto Media
                urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoAsuetoMediaTanda.jpg";
            }
            else if(estadoUI.duracion === '1.5T') { 
                CJA = '3'; // Trabajo Asueto 1.5
                urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoAsuetoUnaTandaYMedia.jpg";
            }
            
            if(estadoUI.nocturnidad && estadoUI.duracion !== '') {
                urlImagen = "../acomtus/img/Catalogo Jornada/AsuetoYNocturnidad.jpg";
            }
            break;

        // ------------------------------------------------------------------
        // CASO: DESCANSO (D)
        // ------------------------------------------------------------------
        case 'descanso':
            // DEFAULT: Código 41344444 (Descanso.jpg)
            if(estadoUI.duracion === '') {
                CJA = '3'; // El '3' en la 3ra posición genera el 413...
                urlImagen = "../acomtus/img/Catalogo Jornada/Descanso.jpg";
            }
            // SI TRABAJA EN DESCANSO (Desglose posterior)
            // Nota: Aquí usamos CJD (Posición 5) para trabajo en descanso según lógica anterior?
            // Ojo: En tus códigos anteriores TrabajoDescansoUnaTanda era 41444244 (CJD=2)
            else if(estadoUI.duracion === '1T') { 
                CJD = '2'; 
                urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoDescansoUnaTanda.jpg"; 
            }
            else if(estadoUI.duracion === '4H') { 
                CJD = '1'; 
                urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoDescansoMediaTanda.jpg"; 
            }
            else if(estadoUI.duracion === '1.5T') { 
                CJD = '3'; 
                urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoDescansoUnaTandaYMedia.jpg"; 
            }
            break;

        // ------------------------------------------------------------------
        // CASO: VACACIÓN (V)
        // ------------------------------------------------------------------
        case 'vacacion':
            // DEFAULT: Código 41144444 (Vacacion.jpg)
            if(estadoUI.duracion === '') {
                CJA = '1'; // El '1' en la 3ra posición genera el 411...
                urlImagen = "../acomtus/img/Catalogo Jornada/Vacacion.jpg";
            }
            // SI TRABAJA EN VACACIÓN
            else if(estadoUI.duracion === '1T') { 
                CJV = '2'; 
                urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoVacacionUnaTanda.jpg"; 
            }
            else if(estadoUI.duracion === '4H') { 
                CJV = '1'; 
                urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoVacacionMediaTanda.jpg"; 
            }
            else if(estadoUI.duracion === '1.5T') { 
                CJV = '3'; 
                urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoVacacionUnaTandaYMedia.jpg"; 
            }
            
            if(estadoUI.nocturnidad && estadoUI.duracion !== '') {
                urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoVacacionUnaTandaNocturnidad.jpg";
            }
            break;

        // ------------------------------------------------------------------
        // CASOS SIMPLES
        // ------------------------------------------------------------------
        case 'permiso':
            // Código: 4344444
            CTL = '3'; // El '3' en la 2da posición genera 4.3...
            urlImagen = "../acomtus/img/Catalogo Jornada/Permiso.jpg";
            break;

        case 'falta':
            // Código: 4444444
            CTL = '4'; // El '4' en la 2da posición genera 4.4...
            urlImagen = "../acomtus/img/Catalogo Jornada/Falta.jpg";
            break;
    }

    // Actualizar Inputs Ocultos
    $("#CJ").val(CJ); $("#CTL").val(CTL); $("#CJA").val(CJA); 
    $("#CJV").val(CJV); $("#CJD").val(CJD); $("#CJE4H").val(CJE4H); 
    $("#CJN").val(CJN); $("#lstHoraExtra").val(estadoUI.horasExtras);

    // Actualizar Visuales
    $("#ImagenJornada").attr("src", urlImagen);
    $("#lblResumenCodigo").text(resumen);
    
    // Debug Código (Opcional)
    let debugCode = `${CJ}.${CTL}.${CJA}.${CJV}.${CJD}.${CJE4H}.${CJN}.${estadoUI.horasExtras}`;
    $("#debug-codigo-bd").text(debugCode);
}

function setDuracion(val) {
    estadoUI.duracion = val;
    
    // UI Botones
    $("[data-dur]").removeClass("active btn-secondary").addClass("btn-outline-secondary");
    $(`[data-dur='${val}']`).addClass("active btn-secondary").removeClass("btn-outline-secondary");
    
    // Lógica especial: Botón "+1 Tanda Extra" solo visible en Media Tanda (4H) Laborada
    if(estadoUI.tipo === 'laborado' && val === '4H') {
        $("#btn-tanda-extra").fadeIn();
    } else {
        estadoUI.tandaExtra = false; // Reset
        $("#btn-tanda-extra").hide().removeClass("active");
    }
    
    calcularYActualizar();
}

function toggleNocturnidad() {
    estadoUI.nocturnidad = !estadoUI.nocturnidad;
    // UI Botón
    if(estadoUI.nocturnidad) $("#btn-nocturnidad").addClass("active btn-dark text-white").removeClass("btn-outline-dark");
    else $("#btn-nocturnidad").removeClass("active btn-dark text-white").addClass("btn-outline-dark");
    
    calcularYActualizar();
}

function toggleTandaExtra() {
    estadoUI.tandaExtra = !estadoUI.tandaExtra;
    // UI Botón
    if(estadoUI.tandaExtra) $("#btn-tanda-extra").addClass("active btn-primary text-white").removeClass("btn-outline-primary");
    else $("#btn-tanda-extra").removeClass("active btn-primary text-white").addClass("btn-outline-primary");
    
    calcularYActualizar();
}

function setHE(num) {
    estadoUI.horasExtras = parseInt(num);
    $("[data-he]").removeClass("active btn-secondary").addClass("btn-outline-secondary");
    $(`[data-he='${num}']`).addClass("active btn-secondary").removeClass("btn-outline-secondary");
    calcularYActualizar();
}


// --- 3. HIDRATACIÓN (BD -> UI) ---
// Esta función lee los códigos guardados (ej: 2.1.4.4...) y enciende los botones al abrir el modal
// --- 3. HIDRATACIÓN (BD -> UI) ---
function mapearEstadoDesdeBD(codigos) {
    // Array: 0=CJ, 1=CTL, 2=CJA, 3=CJV, 4=CJD, 5=CJE4H, 6=CJN, 7=HE
    // Nota: Gracias al cambio en PHP, codigos[7] siempre existirá.
    
    let cj = codigos[0];
    let ctl = codigos[1];
    let cja = codigos[2];
    let cjv = codigos[3];
    let cjd = codigos[4];
    let cje4h = codigos[5];
    let cjn = codigos[6];
    
    // CORRECCIÓN: Leer HE de forma segura. Si viene vacío o undefined, es 0.
    let he = parseInt(codigos[7] || 0);

    // Reiniciamos UI visualmente antes de aplicar lógica
    $(".opcion-card").removeClass("active bg-primary text-white border-primary shadow");
    
    // 1. Lógica para determinar el TIPO DE ACCIÓN
    if (cja != '4') {
        seleccionarTipo('asueto');
    }
    else if (cjv != '4') {
        seleccionarTipo('vacacion');
    }
    else if (cjd != '4') {
        seleccionarTipo('descanso');
    }
    else if (ctl != '1') {
        // Si tiene licencia (diferente de 1) es Permiso
        seleccionarTipo('permiso');
    }
    else if (cj != '4') {
        // Si jornada es 1, 2 o 3, es Laborado
        seleccionarTipo('laborado');
    }
    else {
        // CASO: "SIN JORNADA" O "FALTA" (4.1.4.4.4.4.4)
        // Aquí decidimos: ¿Queremos que salga marcado como 'Falta'?
        // O ¿Queremos que salga 'Limpio' para que el usuario elija?
        
        // OPCIÓN A: Marcar como FALTA (Si tu sistema considera Sin Jornada = Falta)
        //seleccionarTipo('falta'); 
        
        // OPCIÓN B (Si prefieres neutro): Descomenta las siguientes líneas
        
        estadoUI.tipo = 'ninguno';
        $("#panel-configuracion").hide();
        $("#lblResumenCodigo").text("SIN PUNTEO");
        return; // Salimos para no configurar nada más
        
    }

    // 2. Determinar Duración (Solo si no es falta/permiso)
    let codigoActivo = '4';
    if(estadoUI.tipo == 'laborado') codigoActivo = cj;
    if(estadoUI.tipo == 'asueto') codigoActivo = cja;
    if(estadoUI.tipo == 'vacacion') codigoActivo = cjv;
    if(estadoUI.tipo == 'descanso') codigoActivo = cjd;

    if(codigoActivo == '1') setDuracion('4H');
    else if(codigoActivo == '2') setDuracion('1T');
    else if(codigoActivo == '3') setDuracion('1.5T');

    // 3. Extras (Nocturnidad)
    if(cjn == '5') {
        estadoUI.nocturnidad = true;
        $("#btn-nocturnidad").addClass("active btn-dark text-white").removeClass("btn-outline-dark");
    } else {
        estadoUI.nocturnidad = false;
        $("#btn-nocturnidad").removeClass("active btn-dark text-white").addClass("btn-outline-dark");
    }

    // 4. Tanda Extra (Caso especial Media Tanda + 1T)
    if(cje4h == '2') { 
        estadoUI.tandaExtra = true;
        $("#btn-tanda-extra").addClass("active btn-primary text-white").removeClass("btn-outline-primary").show();
    } else {
        estadoUI.tandaExtra = false;
        $("#btn-tanda-extra").removeClass("active btn-primary text-white").addClass("btn-outline-primary");
        if(estadoUI.duracion !== '4H') $("#btn-tanda-extra").hide();
    }

    // 5. Horas Extras (Ahora sí se lee correctamente)
    setHE(he);
}
// =========================================================================
// FUNCIONES AUXILIARES (Existentes que no tocamos mucho)
// =========================================================================

function buscar_personal(codigo_personal){
    // Tu función original de búsqueda de empleados (Resumida para no repetir todo el bloque si ya lo tienes)
    var codigo_depto = $("#CodigoDepartamentoEmpresa").val();
    
    $.post("php_libs/soporte/Asistencia/PorNomina.php", {
        accion_buscar: 'BuscarPersonalRutaCodigo', 
        codigo_personal: codigo_personal, 
        fecha: fecha, 
        codigo_departamento_empresa: codigo_depto
    }, function(data) {
        if(data[0].respuestaOK == true){
            $("#LblDescripcion").html((codigo_depto=="02"?"Ruta: ":"Departamento: ") + data[0].Descripcion + " - Empleados: " + data[0].TotalEmpleados);
            if(codigo_depto=="02") $("#CodigoRuta").val(data[0].Codigo);
            
            // Cargar Lista
            $.ajax({
                type: "POST",
                dataType: "json",
                url:"php_libs/soporte/Asistencia/PorNomina.php",
                data: { 
                    accion_buscar: 'BuscarEmpleadosPorRuta', 
                    CodigoRuta: data[0].Codigo, 
                    fecha: fecha, 
                    codigo_personal_encargado: codigo_personal, 
                    CodigoDepartamentoEmpresa: codigo_depto
                },  
                success: function(response){
                    $('#listadoEmpleadosNomina').empty().append(response.contenido);
                    if(response.mensajeAsueto !== "") {
                        $("#MostrarMensajes").show().find("label").text("Asueto: " + response.mensajeAsueto);
                    } else {
                        $("#MostrarMensajes").hide();
                    }
                }
            });   
        } else {
            $("#LblDescripcion").html(data[0].mensajeError);
        }
    }, "json");
}

function configureLoadingScreen(screen){
    $(document).ajaxStart(function () { screen.fadeIn(); }).ajaxStop(function () { screen.fadeOut(); });
}

// --- FUNCION PARA FILTRAR BOTONES SEGÚN DEPARTAMENTO ---
function aplicarPermisosDepartamento() {
    // Obtenemos el código del departamento (asegúrate que esta variable tenga valor)
    // Puede venir del input hidden global o pasarlo como parámetro
    var depto = $("#CodigoDepartamentoEmpresa").val(); 
    console.log("Aplicando permisos para departamento: " + depto);
    // 1. REGLA NOCTURNIDAD (Solo Vigilancia 08 y Mantenimiento 09)
    var deptosNocturnidad = ['08', '09'];
    
    if (deptosNocturnidad.includes(depto)) {
        $("#bloque-nocturnidad").show();
    } else {
        $("#bloque-nocturnidad").hide();
        // Importante: Si se oculta, apagamos la variable para que no guarde basura
        estadoUI.nocturnidad = false;
        $("#btn-nocturnidad").removeClass("active btn-dark text-white").addClass("btn-outline-dark");
    }

    // 2. REGLA HORAS EXTRAS (Solo Motoristas 02 y Revisadores 03)
    var deptosHE = ['02', '03'];
    
    if (deptosHE.includes(depto)) {
        $("#bloque-horas-extras").show();
    } else {
        $("#bloque-horas-extras").hide();
        // Si se oculta, reseteamos a 0
        setHE(0);
    }
}