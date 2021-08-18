/*----------Funcion para obtener la edad------------*/
function calcular_edad(fecha) {
var fechaActual = new Date();
var diaActual = fechaActual.getDate();
var mmActual = fechaActual.getMonth() + 1;
var yyyyActual = fechaActual.getFullYear();
FechaNac = fecha.split("-");
var diaCumple = FechaNac[2];
var mmCumple = FechaNac[1];
var yyyyCumple = FechaNac[0];
//alert(FechaNac);
//retiramos el primer cero de la izquierda
if (mmCumple.substr(0,1) === 0) {
mmCumple= mmCumple.substring(1, 2);
}
//retiramos el primer cero de la izquierda
if (diaCumple.substr(0, 1) === 0) {
diaCumple = diaCumple.substring(1, 2);
}
var edad = yyyyActual - yyyyCumple;

//validamos si el mes de cumpleaños es menor al actual
//o si el mes de cumpleaños es igual al actual
//y el dia actual es menor al del nacimiento
//De ser asi, se resta un año
if ((mmActual < mmCumple) || (mmActual == mmCumple && diaActual < diaCumple)) {
edad--;
}
//return edad;
//Calcular edad más mes.
var edad_mes =  mmActual - mmCumple;
var text_edad_mes = "";

if (edad_mes > 0)
	{
		text_edad_mes = "Usted tiene " + edad + " años y " + edad_mes + " mes(es)";
	}
else
	{
		if (edad_mes < 0)
		{
			text_edad_mes = "Usted tiene " + (edad) + " años y " + ((12-mmCumple)+mmActual) + " mes(es)";
		}
		else
		{
			text_edad_mes = "Usted tiene " + edad + " años exactos";
		}
	}
// Pasar los valores al tabs-2. PN.
	$("label[for='lbl_edad_y_mes']").text(text_edad_mes);
	$('#txtedad').val(edad);
	$('#edad_enviar').val(edad);
}