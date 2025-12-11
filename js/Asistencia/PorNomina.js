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
    subTipoId: '0',   // ID específico para Permisos (3,2) o Faltas (4,10)
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
    // EVENTOS DEL MODAL (INTERACCIÓN)
    // =========================================================================
    
    // 1. ABRIR MODAL Y CARGAR DATOS
    $('body').on('click','#listadoEmpleadosNomina a',function (e){
        e.preventDefault();
        
        if($(this).attr('data-accion') == 'editarAsistencia'){
            accion = 'EditarJornada';
            Id_Editar_Eliminar = $(this).attr('href'); // Viene codificado
            $('#VentanaPunteo').modal("show");
            
            // Llamada AJAX para traer los datos
            $.post("php_libs/soporte/Asistencia/PorNomina.php", { Id_: Id_Editar_Eliminar, accion: accion},
                function(data) {
                    // A. Datos Visuales Básicos
                    $("label[for=CodigoNombreEmpleado]").text(data[0].CodigoPersonal + " - " + data[0].NombreCompleto);
                    $("#FotoEmpleado").attr("src", data[0].Foto);
                    $("#ImagenJornada").attr("src", data[0].ImgJornada);
                    $('#Id_').val(data[0].Id_);

                    // B. Botón Reiniciar (Solo si ya existe registro)
                    if (data[0].Id_ > 0) {
                        $("#btnEliminarPunteo").show().attr("data-id", data[0].Id_);
                    } else {
                        $("#btnEliminarPunteo").hide();
                    }

                    // C. Configurar visibilidad según DEPARTAMENTO
                    aplicarPermisosDepartamento();

                    // D. Leer el código guardado y encender los botones correctos
                    let sep = data[0].CodigoJornadaTodasSeparador.split(".");
                    mapearEstadoDesdeBD(sep);
                    
                },"json");
        }
    });

    // 2. BOTÓN GUARDAR (Dispara el submit del form)
    $("#goGuardarPunteo").on('click', function(){
        $("#formPunteo").submit();
    });

    // 3. BOTÓN REINICIAR / ELIMINAR
    $("#btnEliminarPunteo").on("click", function() {
        let idAsistencia = $(this).attr("data-id");
        
        Swal.fire({
            title: '¿Reiniciar Asistencia?',
            text: "Se borrará el registro de este día.",
            type: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Sí, reiniciar'
        }).then((result) => {
            if (result.value) {
                $.post("php_libs/soporte/Asistencia/PorNomina.php", {
                    accion: 'EliminarAsistencia',
                    id_asistencia: idAsistencia
                }, function(response) {
                    if(response.respuesta) {
                        toastr.success("Registro reiniciado.");
                        $('#VentanaPunteo').modal("hide");
                        buscar_personal($("#CodigoPersonal").val());
                    } else {
                        toastr.error(response.mensaje);
                    }
                }, "json");
            }
        })
    });

    // 4. VALIDACIÓN Y ENVÍO (GUARDADO FINAL)
    $('#formPunteo').validate({
        submitHandler: function(){  
            // Serializamos datos del modal
            var str = $('#formPunteo').serialize();
            
            // AGREGAMOS VARIABLES DEL FORMULARIO PRINCIPAL (Corrige el error Undefined index)
            var codigoPerfil = $("#CodigoPerfil").val();
            var codigoPersonalUsuario = $("#CodigoPersonal").val(); 
            str += "&CodigoPerfil=" + codigoPerfil + "&CodigoPersonal=" + codigoPersonalUsuario;

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
                        // Error de validación de código (SweetAlert)
                        Swal.fire({
                            type: 'error',
                            title: '¡Atención!',
                            text: response.mensaje,
                            footer: 'El código generado no existe en el catálogo.',
                            confirmButtonText: 'Entendido',
                            confirmButtonColor: '#d33'
                        });
                    }      
                }
            });
        }
    });

}); // Fin Function

// =========================================================================
// LÓGICA DE INTERFAZ (UI)
// =========================================================================
function seleccionarTipo(tipo) {
    estadoUI.tipo = tipo;
    
    // UI Visual
    $("#grupo-tipo .opcion-card").removeClass("active bg-primary text-white border-primary shadow");
    $(`[data-tipo='${tipo}']`).addClass("active bg-primary text-white border-primary shadow");
    
    // Ocultar todos los subpaneles primero
    $("#subpanel-permisos, #subpanel-faltas, #subpanel-asueto, #subpanel-vacacion").slideUp();
    
    // Configuración base (se muestra por defecto, luego se ajusta)
    $("#panel-configuracion").slideDown(); 

    // --- LÓGICA DE SUB-PANELES ---
    if(tipo === 'permiso') {
        $("#subpanel-permisos").slideDown();
        $("#panel-configuracion").slideUp();
        seleccionarSubTipo('3'); 
    } 
    else if(tipo === 'falta') {
        $("#subpanel-faltas").slideDown();
        $("#panel-configuracion").slideUp();
        seleccionarSubTipo('4'); 
    }
    else if(tipo === 'asueto') {
        $("#subpanel-asueto").slideDown();
        // Por defecto Asueto Normal (16)
        seleccionarSubTipo('16'); 
    }
    else if(tipo === 'vacacion') {
        $("#subpanel-vacacion").slideDown();
        // Por defecto Vacación Normal (11)
        seleccionarSubTipo('11');
    }
    else if(tipo === 'laborado') {
        estadoUI.subTipoId = '0';
        if(estadoUI.duracion === '') setDuracion('1T');
    }
    else { // Descanso
        estadoUI.subTipoId = '0';
    }

    // Resetear Extras
    if(tipo !== 'laborado' && tipo !== 'asueto') { // Asueto (TDA) puede tener extras
        estadoUI.tandaExtra = false;
        $("#btn-tanda-extra").hide();
    }

    calcularYActualizar();
}
function seleccionarSubTipo(id) {
    estadoUI.subTipoId = id;
    
    // UI Sub-botones
    $("[data-sub]").removeClass("active bg-info text-white border-info");
    $(`[data-sub='${id}']`).addClass("active bg-info text-white border-info");
    
    // --- LÓGICA ESPECIAL PARA MOSTRAR/OCULTAR DURACIÓN ---
    
    // CASO TDA (Trabajo Descanso Asueto - ID 15): Necesita duración
    if (id === '15') {
        $("#panel-configuracion").slideDown();
        if(estadoUI.duracion === '') setDuracion('1T');
    }
    // CASOS PUROS (DA, VDA, Asueto Puro): No necesitan duración
    else if (id === '17' || id === '19_vda' || id === '16') {
        // Ojo: Asueto normal (16) puede tener duración si es trabajado, 
        // pero visualmente "limpiamos" al inicio.
        
        if (id === '16') {
             $("#panel-configuracion").slideDown(); // Asueto normal permite elegir trabajo
             // Limpiamos duración visualmente para que se vea la imagen base
             estadoUI.duracion = ''; 
             $("[data-dur]").removeClass("active btn-secondary").addClass("btn-outline-secondary");
        } else {
             // DA y VDA son descansados
             $("#panel-configuracion").slideUp();
             estadoUI.duracion = ''; 
        }
    }
    
    calcularYActualizar();
}

function setDuracion(val) {
    estadoUI.duracion = val;
    
    // UI Botones Duración
    $("[data-dur]").removeClass("active btn-secondary").addClass("btn-outline-secondary");
    $(`[data-dur='${val}']`).addClass("active btn-secondary").removeClass("btn-outline-secondary");
    
    // Botón Tanda Extra solo en 4H Laborado
    if(estadoUI.tipo === 'laborado' && val === '4H') {
        $("#btn-tanda-extra").fadeIn();
    } else {
        estadoUI.tandaExtra = false;
        $("#btn-tanda-extra").hide().removeClass("active");
    }
    
    calcularYActualizar();
}

function toggleNocturnidad() {
    estadoUI.nocturnidad = !estadoUI.nocturnidad;
    if(estadoUI.nocturnidad) $("#btn-nocturnidad").addClass("active btn-dark text-white").removeClass("btn-outline-dark");
    else $("#btn-nocturnidad").removeClass("active btn-dark text-white").addClass("btn-outline-dark");
    calcularYActualizar();
}

function toggleTandaExtra() {
    estadoUI.tandaExtra = !estadoUI.tandaExtra;
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

// =========================================================================
// MOTOR DE CÁLCULO DE CÓDIGOS (LÓGICA CENTRAL)
// =========================================================================

function calcularYActualizar() {
    // Valores Neutros (Base: 4.1.4.4.4.4.4)
    let CJ='4', CTL='1', CJA='4', CJV='4', CJD='4', CJE4H='4', CJN='4';
    let urlImagen = "../acomtus/img/Catalogo Jornada/Ninguno.jpg"; 
    let resumen = estadoUI.tipo.toUpperCase();

    if(estadoUI.nocturnidad) CJN = '5';

    switch (estadoUI.tipo) {
    // ------------------------------------------------------------------
        // CASO: TRABAJO (Laborado)
        // ------------------------------------------------------------------
        case 'laborado':
            // --- A. UNA TANDA (1T) ---
            if(estadoUI.duracion === '1T' || estadoUI.duracion === '') {
                CJ = '2'; // Base: 2144444
                urlImagen = "../acomtus/img/Catalogo Jornada/PuntoUnaTanda.jpg";
                
                // Prioridad Nocturnidad
                if(estadoUI.nocturnidad) {
                    urlImagen = "../acomtus/img/Catalogo Jornada/PuntoUnaTandaYNocturnidad.jpg"; // 2144445
                }
                // Horas Extras (Si no es nocturno, o si tienes imagen combinada)
                else if(estadoUI.horasExtras > 0) {
                    urlImagen = "../acomtus/img/Catalogo Jornada/PuntoUnaTanda" + estadoUI.horasExtras + "HE.jpg";
                }
            }
            // --- B. TANDA Y MEDIA (1.5T) ---
            else if(estadoUI.duracion === '1.5T') {
                CJ = '3'; // Base: 3144444
                urlImagen = "../acomtus/img/Catalogo Jornada/UnaTandaYMedia.jpg";
                
                if(estadoUI.nocturnidad) {
                    urlImagen = "../acomtus/img/Catalogo Jornada/UnaTandaYMediaYNocturnidad.jpg"; // 3144445
                }
            }
            // --- C. MEDIA TANDA (4H) - AQUÍ ESTÁ EL 1144425 ---
            else if(estadoUI.duracion === '4H') {
                CJ = '1'; // Base: 1144444
                urlImagen = "../acomtus/img/Catalogo Jornada/MediaTanda.jpg";
                
                // 1. Caso: Media Tanda + Tanda Extra (El más complejo)
                if(estadoUI.tandaExtra) {
                    CJE4H = '2'; // Activa Tanda Extra
                    
                    if(estadoUI.nocturnidad) {
                        // CÓDIGO: 1144425 (Media + Extra + Noche)
                        urlImagen = "../acomtus/img/Catalogo Jornada/MediaTandaExtraUnaTandaYNocturnidad.jpg"; 
                    } 
                    else if(estadoUI.horasExtras == 4) {
                        // CÓDIGO: 11444244
                        urlImagen = "../acomtus/img/Catalogo Jornada/MediaTandaExtraUnaTanda4HE.jpg";
                    }
                    else {
                        // CÓDIGO: 1144424 (Solo Media + Extra)
                        urlImagen = "../acomtus/img/Catalogo Jornada/MediaTandaExtraUnaTanda.jpg";
                    }
                } 
                // 2. Caso: Media Tanda + Nocturnidad (Sin Tanda Extra)
                else if(estadoUI.nocturnidad) {
                    // CÓDIGO: 1144445
                    urlImagen = "../acomtus/img/Catalogo Jornada/MediaTandaYNocturnidad.jpg";
                }
                // 3. Caso: Media Tanda + HE
                else if(estadoUI.horasExtras > 0) {
                    // CÓDIGO: 11444144 (Ejemplo)
                    urlImagen = "../acomtus/img/Catalogo Jornada/MediaTanda" + estadoUI.horasExtras + "HE.jpg"; 
                }
            }
            break;

    // ------------------------------------------------------------------
        // CASO: ASUETO (Incluye Normal, DA y TDA)
        // ------------------------------------------------------------------
        case 'asueto':
            // Leemos el subtipo seleccionado (16=Normal, 17=DA, 15=TDA)
            let sub = estadoUI.subTipoId;
            
            // --- CASO 1: TDA (Trabajo Descanso Asueto) ---
            if (sub === '15') {
                CTL = '15'; // Código Licencia 15
                // La duración se guarda en CJA (Posición 3)
                if(estadoUI.duracion === '1T') { 
                    CJA = '2'; urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoDescansoAsuetoUnaTanda.jpg"; // 41524444
                }
                else if(estadoUI.duracion === '4H') { 
                    CJA = '1'; urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoDescansoAsuetoMediaTanda.jpg"; // 41514444
                }
                else if(estadoUI.duracion === '1.5T') { 
                    CJA = '3'; urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoDescansoAsuetoUnaTandaYMedia.jpg"; // 41534444
                }
            }
            // --- CASO 2: DA (Descanso Asueto) ---
            else if (sub === '17') {
                CTL = '17'; // Código Licencia 17
                CJA = '4';  // Sin jornada
                urlImagen = "../acomtus/img/Catalogo Jornada/DescansoAsueto.jpg"; // 41744444
            }
            // --- CASO 3: ASUETO NORMAL O TRABAJADO ---
            else {
                CTL = '16'; // Por defecto
                
                // Lógica de Asueto Nocturno (Mantiene CTL 19)
                if(estadoUI.nocturnidad && estadoUI.duracion !== '') CTL = '16';

                if(estadoUI.duracion === '') {
                    CJA = '4'; urlImagen = "../acomtus/img/Catalogo Jornada/Asueto.jpg";
                }
                else if(estadoUI.duracion === '1T') { 
                    CJA = '2'; urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoAsuetoUnaTanda.jpg";
                }
                else if(estadoUI.duracion === '4H') { 
                    CJA = '1'; urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoAsuetoMediaTanda.jpg";
                }
                else if(estadoUI.duracion === '1.5T') { 
                    CJA = '3'; urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoAsuetoUnaTandaYMedia.jpg";
                }
                
                // Imagen nocturnidad
                if(estadoUI.nocturnidad && estadoUI.duracion !== '') {
                    if(estadoUI.duracion === '1T'){
                        urlImagen = "../acomtus/img/Catalogo Jornada/AsuetoTandaYNocturnidad.jpg";
                    }else if(estadoUI.duracion === '4H'){
                        urlImagen = "../acomtus/img/Catalogo Jornada/AsuetoMediaTandaNocturnidad.jpg";
                    }else if(estadoUI.duracion === '1.5T'){
                        urlImagen = "../acomtus/img/Catalogo Jornada/AsuetoUnaTandaYMediaNocturnidad.jpg";
                    }
                }
            }
            break;

        // ------------------------------------------------------------------
        // CASO: DESCANSO (D)
        // Puro: CTL=13 | Trabajado (TD): CTL=14
        // ------------------------------------------------------------------
        case 'descanso':
            // 1. DESCANSO PURO
            if(estadoUI.duracion === '') {
                CTL = '13'; // Código 13 para D
                CJA = '4';  // Limpieza (antes era 3)
                CJD = '4';  // Limpieza
                urlImagen = "../acomtus/img/Catalogo Jornada/Descanso.jpg"; // 41344444
            }
            // 2. TRABAJO EN DESCANSO (TD)
            else {
                CTL = '14'; // CAMBIO: Licencia 14 es "Trabajo en Descanso"
                
                // Asignamos duración a CJD (Posición 5)
                if(estadoUI.duracion === '1T') { 
                    CJD = '2'; // 4.14.4.4.2...
                    urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoDescansoUnaTanda.jpg"; 
                }
                else if(estadoUI.duracion === '4H') { 
                    CJD = '1'; // 4.14.4.4.1...
                    urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoDescansoMediaTanda.jpg"; 
                }
                else if(estadoUI.duracion === '1.5T') { 
                    CJD = '3'; // 4.14.4.4.3...
                    urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoDescansoUnaTandaYMedia.jpg"; 
                }
            }

                // =========================================================
                // LÓGICA FUTURA: NOCTURNIDAD EN DESCANSO (TD + N)
                // =========================================================
                // Descomentar esto cuando tengas las imágenes y los códigos en BD.
                // Los códigos generados terminarán en 5 (ej: 4.14.4.4.2.4.5.0)
                
                
                if(estadoUI.nocturnidad) {
                    // Nota: CJN se pone en 5 automáticamente por la regla global al final de la función.
                    
                    if(estadoUI.duracion === '1T') {
                        // Código esperado: 41444245
                        urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoDescansoUnaTandaNocturnidad.jpg";
                    }
                    else if(estadoUI.duracion === '4H') {
                        // Código esperado: 41444145
                        urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoDescansoMediaTandaNocturnidad.jpg";
                    }
                    else if(estadoUI.duracion === '1.5T') {
                        // Código esperado: 41444345
                        urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoDescansoUnaTandaYMediaNocturnidad.jpg";
                    }
                }
                

            break;

    // ------------------------------------------------------------------
        // CASO: VACACIÓN (V) 
        // Pura: CTL=11 | Trabajada (TV): CTL=12
        // ------------------------------------------------------------------
        case 'vacacion':
            // 1. SUBTIPO VDA (Vacación Descanso Asueto)
            if (estadoUI.subTipoId === '19_vda') {
                CTL = '19'; 
                CJA = '4'; CJN = '4'; // Limpieza
                urlImagen = "../acomtus/img/Catalogo Jornada/VacacionDescansoAsueto.jpg";
            }
            else {
                // 2. VACACIÓN PURA (Sin duración)
                if(estadoUI.duracion === '') {
                    CTL = '11'; // Código 11 para V
                    CJA = '4';  // CORRECCIÓN: Estaba en 1, debe ser 4 (Neutro)
                    CJV = '4';  // CORRECCIÓN: Estaba en 1, debe ser 4 (Neutro)
                    urlImagen = "../acomtus/img/Catalogo Jornada/Vacacion.jpg"; // 41144444
                }
                // 3. TRABAJO EN VACACIÓN (TV)
                else {
                    CTL = '12'; // CAMBIO: Licencia 12 es "Trabajo en Vacación"
                    CJA = '4';
                    
                    // Asignamos duración a CJV (Posición 4)
                    if(estadoUI.duracion === '1T') { 
                        CJV = '2'; // 4.12.4.2...
                        urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoVacacionUnaTanda.jpg"; 
                    }
                    else if(estadoUI.duracion === '4H') { 
                        CJV = '1'; // 4.12.4.1...
                        urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoVacacionMediaTanda.jpg"; 
                    }
                    else if(estadoUI.duracion === '1.5T') { 
                        CJV = '3'; // 4.12.4.3...
                        urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoVacacionUnaTandaYMedia.jpg"; 
                    }
                    
                    // NOCTURNIDAD EN TV
                    if(estadoUI.nocturnidad) {
                        if(estadoUI.duracion === '1T') {
                            urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoVacacionUnaTandaNocturnidad.jpg"; // 41242445
                        }
                        else if(estadoUI.duracion === '4H') {
                            // Nota: Asumo que Media Tanda Noc sigue el patrón CJV=1. 
                            // Si tu código DB es 41243445, cambia este CJV a '3', pero por lógica debería ser '1'.
                            urlImagen = "../acomtus/img/Catalogo Jornada/TrabajoVacacionMediaTandaNocturnidad.jpg"; 
                        }
                    }
                }
            }
            break;
        // ------------------------------------------------------------------
        // OTROS CASOS (Permiso y Falta ya usan CTL correctamente)
        // ------------------------------------------------------------------
        case 'permiso':
            CTL = estadoUI.subTipoId; // 2 (ISSS) o 3 (PP)
            if(CTL == '2') {
                urlImagen = "../acomtus/img/Catalogo Jornada/ISSS.jpg"; 
                resumen = "INCAPACIDAD (ISSS)";
            } else {
                urlImagen = "../acomtus/img/Catalogo Jornada/Permiso.jpg"; 
                resumen = "PERMISO PERSONAL";
            }
            break;

        case 'falta':
            CTL = estadoUI.subTipoId; // 4 (Falta) o 10 (Castigo)
            if(CTL == '10') {
                urlImagen = "../acomtus/img/Catalogo Jornada/Castigo.jpg"; 
                resumen = "CASTIGO";
            } else {
                urlImagen = "../acomtus/img/Catalogo Jornada/Falta.jpg";
                resumen = "FALTA INJUSTIFICADA";
            }
            break;
    }

    // Actualizar Inputs Hidden
    $("#CJ").val(CJ); $("#CTL").val(CTL); $("#CJA").val(CJA); 
    $("#CJV").val(CJV); $("#CJD").val(CJD); $("#CJE4H").val(CJE4H); 
    $("#CJN").val(CJN); $("#lstHoraExtra").val(estadoUI.horasExtras);

    // Actualizar Visuales
    $("#ImagenJornada").attr("src", urlImagen);
    $("#lblResumenCodigo").text(resumen);
    $("#debug-codigo-bd").text(`${CJ}.${CTL}.${CJA}.${CJV}.${CJD}.${CJE4H}.${CJN}.${estadoUI.horasExtras}`);
}
// =========================================================================
// FUNCIONES AUXILIARES E INICIALIZACIÓN
// =========================================================================
function mapearEstadoDesdeBD(codigos) {
    // Array: 0=CJ, 1=CTL, 2=CJA, 3=CJV, 4=CJD, 5=CJE4H, 6=CJN, 7=HE
    let cj = codigos[0];
    let ctl = codigos[1];
    let cja = codigos[2];
    let cjv = codigos[3];
    let cjd = codigos[4];
    let cje4h = codigos[5];
    let cjn = codigos[6];
    let he = parseInt(codigos[7] || 0);

    // Reset Visual Inicial
    $(".opcion-card").removeClass("active bg-primary text-white border-primary shadow");
    
    // =========================================================
    // 1. DETERMINAR EL TIPO DE ASISTENCIA
    // =========================================================
    
    // --- GRUPO 1: CASOS ESPECIALES (TDA, DA, VDA) ---
    // Estos usan códigos de Licencia (CTL) específicos que definimos antes
    
    if (ctl == '15') { 
        // TDA (Trabajo Descanso Asueto)
        seleccionarTipo('asueto');
        seleccionarSubTipo('15');
    }
    else if (ctl == '17') { 
        // DA (Descanso Asueto)
        seleccionarTipo('asueto');
        seleccionarSubTipo('17');
    }
    else if (ctl == '19') {
        // CONFLICTO: CTL 19 se usa para "Asueto Nocturno" y para "VDA".
        // Diferencia: Asueto Nocturno tiene CJN='5'. VDA tiene CJN='4'.
        if(cjn == '5') {
            // Es Asueto Trabajado Nocturno -> Lo tratamos como Asueto Normal
            seleccionarTipo('asueto');
            seleccionarSubTipo('16'); 
        } else {
            // Es VDA (Vacación Descanso Asueto)
            seleccionarTipo('vacacion');
            seleccionarSubTipo('19_vda');
        }
    }

    // --- GRUPO 2: PERMISOS Y FALTAS ---
    else if (ctl == '2' || ctl == '3') { 
        seleccionarTipo('permiso');
        seleccionarSubTipo(ctl);
    }
    else if (ctl == '4' || ctl == '10') { 
        seleccionarTipo('falta');
        seleccionarSubTipo(ctl);
    }
    
    // --- GRUPO 3: CASOS PUROS (CTL Específicos Nuevos) ---
    else if (ctl == '16') seleccionarTipo('asueto');   // Asueto Normal
    else if (ctl == '13') seleccionarTipo('descanso'); // Descanso Normal
    else if (ctl == '11') seleccionarTipo('vacacion'); // Vacación Normal
    
    // --- GRUPO 4: CASOS PUROS (Formato Antiguo/DB CJA) ---
    else if (cja == '3' && cj == '4') seleccionarTipo('descanso');
    else if (cja == '1' && cj == '4') seleccionarTipo('vacacion');
    else if (cja == '6' && cj == '4') seleccionarTipo('asueto');

    // --- GRUPO 5: MIXTOS Y TRABAJO ---
    else if (cjv != '4') seleccionarTipo('vacacion'); // Trabajo Vacación
    else if (cjd != '4') seleccionarTipo('descanso'); // Trabajo Descanso
    else if (cja != '4') seleccionarTipo('asueto');   // Trabajo Asueto
    else if (cj != '4')  seleccionarTipo('laborado'); // Trabajo Normal
    
    // --- GRUPO 6: SIN PUNTEO (Default) ---
    else {
        // Estado Neutro
        estadoUI.tipo = 'ninguno';
        estadoUI.duracion = '';
        estadoUI.subTipoId = '0';
        estadoUI.nocturnidad = false;
        estadoUI.horasExtras = 0;

        // Limpieza UI
        $(".opcion-card").removeClass("active bg-primary text-white border-primary shadow");
        $("#panel-configuracion").hide(); 
        $("#subpanel-permisos, #subpanel-faltas, #subpanel-asueto, #subpanel-vacacion").hide();
        $("#btn-nocturnidad").removeClass("active btn-dark text-white").addClass("btn-outline-dark");
        $("#btn-tanda-extra").hide();
        $("[data-he]").removeClass("active btn-secondary").addClass("btn-outline-secondary");
        $("[data-he='0']").addClass("active btn-secondary").removeClass("btn-outline-secondary");

        $("#ImagenJornada").attr("src", "../acomtus/img/Catalogo Jornada/SinJornada.jpg");
        $("#lblResumenCodigo").text("SIN PUNTEO");
        
        // Reset Inputs Hidden
        $("#CJ").val('4'); $("#CTL").val('1'); $("#CJA").val('4');
        $("#CJV").val('4'); $("#CJD").val('4'); $("#CJE4H").val('4');
        $("#CJN").val('4'); $("#lstHoraExtra").val('0');
        $("#debug-codigo-bd").text("4.1.4.4.4.4.4.0");
        return; 
    }

  // =========================================================
    // 2. RECUPERAR DURACIÓN Y EXTRAS
    // =========================================================

    // A. Determinar Duración Activa
    let codigoActivo = '4';
    
    if(estadoUI.tipo == 'laborado') codigoActivo = cj;
    else if(estadoUI.tipo == 'asueto' && cja != '6' && cja != '4') codigoActivo = cja; 
    else if(estadoUI.tipo == 'vacacion' && cja != '1' && cja != '4') {
        if(cjv != '4') codigoActivo = cjv; 
        else codigoActivo = '4'; 
    }
    else if(estadoUI.tipo == 'descanso' && cja != '3' && cja != '4') {
        if(cjd != '4') codigoActivo = cjd;
        else codigoActivo = '4';
    }

    // B. Activar Botón de Duración
    if(codigoActivo == '1') setDuracion('4H');
    else if(codigoActivo == '2') setDuracion('1T');
    else if(codigoActivo == '3') setDuracion('1.5T');
    else {
        estadoUI.duracion = '';
        $("[data-dur]").removeClass("active btn-secondary").addClass("btn-outline-secondary");
    }

    // C. Recuperar NOCTURNIDAD (CJN=5)
    // Esto encenderá el botón "Noche" si el código termina en 5
    estadoUI.nocturnidad = (cjn == '5');
    if(estadoUI.nocturnidad) {
        $("#btn-nocturnidad").addClass("active btn-dark text-white").removeClass("btn-outline-dark");
    } else {
        $("#btn-nocturnidad").removeClass("active btn-dark text-white").addClass("btn-outline-dark");
    }

    // D. Recuperar TANDA EXTRA (CJE4H=2)
    // Esto es VITAL para el código 1144425. Si CJE4H es 2, encendemos el botón.
    if(cje4h == '2') { 
        estadoUI.tandaExtra = true;
        // Forzamos mostrar el botón aunque la lógica visual a veces lo oculte
        $("#btn-tanda-extra").show().addClass("active btn-primary text-white").removeClass("btn-outline-primary");
    } else {
        estadoUI.tandaExtra = false;
        $("#btn-tanda-extra").removeClass("active btn-primary text-white").addClass("btn-outline-primary");
        // Solo lo ocultamos si no estamos en 4H Laborado (para mantener limpieza visual)
        if(estadoUI.duracion !== '4H') $("#btn-tanda-extra").hide();
    }

    // E. Recuperar Horas Extras
    setHE(he);
}

function aplicarPermisosDepartamento() {
    var depto = $("#CodigoDepartamentoEmpresa").val(); 
    
    // REGLA NOCTURNIDAD (Solo Vigilancia 08 y Mantenimiento 09)
    if (['08', '09'].includes(depto)) {
        $("#bloque-nocturnidad").show();
    } else {
        $("#bloque-nocturnidad").hide();
        estadoUI.nocturnidad = false;
        $("#btn-nocturnidad").removeClass("active btn-dark text-white").addClass("btn-outline-dark");
    }

    // REGLA HORAS EXTRAS (Solo Motoristas 02 y Revisadores 03)
    if (['02', '03'].includes(depto)) {
        $("#bloque-horas-extras").show();
    } else {
        $("#bloque-horas-extras").hide();
        setHE(0);
    }
}

function buscar_personal(codigo_personal){
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
            
            $.ajax({
                type: "POST", dataType: "json", url:"php_libs/soporte/Asistencia/PorNomina.php",
                data: { 
                    accion_buscar: 'BuscarEmpleadosPorRuta', 
                    CodigoRuta: data[0].Codigo, fecha: fecha, 
                    codigo_personal_encargado: codigo_personal, 
                    CodigoDepartamentoEmpresa: codigo_depto
                },  
                success: function(response){
                    $('#listadoEmpleadosNomina').empty().append(response.contenido);
                    if(response.mensajeAsueto !== "") $("#MostrarMensajes").show().find("label").text("Asueto: " + response.mensajeAsueto);
                    else $("#MostrarMensajes").hide();
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