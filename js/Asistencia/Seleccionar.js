// id de user global
var id_ = 0;
var accion = "todos";
var tabla = "";
var miselect = "";
var today = "";
var codigo_personal = "";
var codigo_departamento_empresa = "";

$(function(){ // iNICIO DEL fUNCTION.
///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
	$(document).ready(function(){
		// LLAMAR A LA TABLA PERSONAL.
		codigo_personal = $("#CodigoPersonal").val();
		codigo_departamento_empresa = $("#CodigoDepartamentoEmpresa").val();
			buscar_personal(codigo_personal);
	});		
});		

////////////////////////////////////////////////////////////
// FUNCION LISTAR TABLA perosnal solo motoristas
////////////////////////////////////////////////////////////
function buscar_personal(codigo_personal){
    codigo_personal = $("#CodigoPersonal").val();
	codigo_departamento_empresa = $("#CodigoDepartamentoEmpresa").val();
    $.post("php_libs/soporte/Asistencia/PorNomina.php", {
		accion_buscar: 'BuscarPersonalRutaCodigo', codigo_personal: codigo_personal, codigo_departamento_empresa: codigo_departamento_empresa},
        function(data) {
			if(data[0].respuestaOK == true){
				// CUANDO SEA OTRO DEPARTAMENTO
				if(codigo_departamento_empresa == "02"){
					$("#LblDescripcion").html("Ruta: " + data[0].Descripcion + " - Empleados: " + data[0].TotalEmpleados)
				}else{
					$("#LblDescripcion").html("Departamento: " + data[0].Descripcion + " - Empleados: " + data[0].TotalEmpleados)
				}
				$("#Codigo").val(data[0].Codigo)
				//	MENSAJE DEL SISEMA
			}else{
				$("#LblDescripcion").html(data[0].mensajeError)
			}
        }, "json");
}
