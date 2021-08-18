// id de user global
var id_ = 0;

$(function(){ // iNICIO DEL fUNCTION.
///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
$(document).ready(function(){
    //  Activar Tooltip
        $('[data-toggle="tooltip"]').tooltip();
        listarSaldos();   
});		
///////////////////////////////////////////////////////////////////////////////
//	FUNCION LISTAR BUSQUEDA DE LOS REGISTROS
/////////////////////////////////////////////////////////////////////////////// 
});	// final de FUNCTION.
function listarSaldos(){
    id_personal = $("#id_personal").val();
    accion = $("#accion_buscar").val();
    $.post("php_libs/soporte/EditarFianzasPrestamos.php",  { id_personal: id_personal, accion: accion },
    function(data){
    // Cargar valores a los objetos Llenar el formulario con los datos del registro seleccionado.
    // Modificar label en la tabs-8.
        $("label[for='LblNombre']").text(data[0].nombre_empleado);
        $("label[for='LblCodigo']").text('Código: ' + data[0].codigo);
    // INFORMACIÓN DE FIANZAS
        $("label[for='LblFianza']").text('$ ' + data[0].saldo_fianza);
        $("label[for='LblSumaFianzas']").text('$ ' + data[0].suma_fianzas);
        $("label[for='LblSumaDevoluciones']").text('$ ' + data[0].suma_devoluciones);
    // INFORMACIÓN DE PRESTAMOS
        $("label[for='LblPrestamo']").text('$ ' + data[0].saldo_prestamo);
        $("label[for='LblSumaPrestamos']").text('$ ' + data[0].suma_prestamos);
        $("label[for='LblSumaDescuentos']").text('$ ' + data[0].suma_descuentos);
    // datos para el card TITLE - INFORMACIÓN GENERAL
        
        //$('#txtfechanacimiento').val(data[0].fecha_nacimiento);
       // $('#txtedad').val(data[0].edad);
        if(data[0].edad==''){
            var edades = 0;
            $('#txtedad').val(edades);    
        }
        //$('#lstgenero').val(data[0].codigo_genero);
       
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
}
function AbrirVentana(url)
{
    window.open(url, '_blank');
    return false;
}
