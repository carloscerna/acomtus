// id de user global
var idUser_ok = 0;
var accion_ok = 'noAccion';
var fecha_desde = "";
var fecha_hasta = "";
var mostrarDias = 0;
// FUNCION RINCIPAL
$(function(){   
// Escribir la fecha actual.
	var now = new Date();                
	var day = ("0" + now.getDate()).slice(-2);
	var month = ("0" + (now.getMonth() + 1)).slice(-2);
	var year = now.getFullYear();

//
	var today = now.getFullYear()+"-"+(month)+"-"+(day) ;
	$('#FechaDesdePD').val(today);
	$('#FechaHastaPD').val(today);
///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
$(document).ready(function(){
	fecha_desde = $("#FechaDesdePD").val();
	mostrarDias = 7;
	GraficoIngresosPorAño();	// datos de ingresos por mes del año.
	GraficoIngresos7dias();		// muestra los últimos 7 días.

	$("#formProduccionDiaria").submit();		
});		// documentready
// Validar Formulario para la buscque de registro segun el criterio.   
	$('#formProduccionDiaria').validate({
			rules:{
				FechaDesdePD: {required: true},
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
		submitHandler: function(){
			// Serializar los datos, toma todos los Id del formulario con su respectivo valor.
			var str = $('#formProduccionDiaria').serialize();
			fecha_desde = $("#FechaDesdePD").val();
			//alert(str);
			$.ajax({
				beforeSend: function(){
					$('#listadoVerProduccionDiaria').empty();
				},
				cache: false,
				type: "POST",
				dataType: "json",
				url:"php_libs/soporte/Tablero.php",
				data:str + "&id=" + Math.random() + "&mostrarDias="+ mostrarDias + "&FechaHoy="+fecha_desde,
				success: function(response){
					// Validar mensaje de error proporcionado por el response. contenido.
					if(response.respuesta == false){
						toastr["info"](response.mensaje, "Sistema");
					}
					else{
						toastr["success"](response.mensaje, "Sistema");
							// lIMPIAR LOS VALORES DE LAS TABLAS.                     
						$('#listadoVerProduccionDiaria').append(response.contenido);
						}      
				// 	grafico
					if(mostrarDias == 1){

					}
					// MUESTRA LA INFOMRACIÓN DE INGRESOS POR AÑO.
						console.log(myChartIngresos7dias); // check the console to see different properities of the current Chart object.. which of course we can set to new values and then update with the .update() function
						myChartIngresos7dias.config.data.labels = response.fecha; //newLabelsArray; // silo down and replace current property with new value
						myChartIngresos7dias.config.data.datasets[0].data = response.ingresos; //newDatasArray;
						/* update chart */
						myChartIngresos7dias.update();
					// MUESTRA LA INFOMRACIÓN DE INGRESOS POR AÑO.
						console.log(myChartIngresosPorAño); // check the console to see different properities of the current Chart object.. which of course we can set to new values and then update with the .update() function
						myChartIngresosPorAño.config.data.label = "$ Ingresos Por Mes - año: " + year;
						myChartIngresosPorAño.config.data.labels = response.NombreMes; // // silo down and replace current property with new value
						myChartIngresosPorAño.config.data.datasets[0].data = response.IngresoPorMes; //;
						/* update chart */
						myChartIngresosPorAño.update();
				}, 
			});	// cierre de ajax
				return false;
		},
	});
///////////////////////////////////////////////////////////////////////////////
/// EVENTOS JQUERY Y para disparar la busqueda. del por nombre motorista.
///////////////////////////////////////////////////////////////////////////////
	$("#goBuscarProduccionDiaria").on('click', function(){
		mostrarDias = 1;
		$("#formProduccionDiaria").submit();
	});
// IMPRIMIR REPORTE DE INGRESO DIARIO POR FECHA.
	$("#goBuscarProduccionDiariaImprimir").on('click', function(){
		// Limpiar datos
		fecha_inicio = $("#FechaDesdePD").val();
		fecha_final = $("#FechaHastaPD").val();
		//
		if($('#chkDolares').is(':checked') ) {
			//alert('Seleccionado Dolares');
			var tipo_moneda = "dolares";
		}
		//
		if($('#chkColones').is(':checked') ) {
			//alert('Seleccionado Colones');
			var tipo_moneda = "colones";
		}
		// Ejecutar Informe
			varenviar = "/acomtus/php_libs/reportes/Ingresos/IngresoPorFecha.php?fecha_inicio="+fecha_inicio+"&fecha_final="+fecha_final+"&tipo_moneda="+tipo_moneda+"&mostrarDias="+mostrarDias;
		// Ejecutar la función abre otra pestaña.
			AbrirVentana(varenviar);   
	});
}); // FINAL DELA FUNCION
function AbrirVentana(url)
{
    window.open(url, '_blank');
    return false;
}
function GraficoIngresos7dias() {
		/* datas */
		const months = ["Jan"];
		const datas_array = [23];
	//
	myChartIngresos7dias = new Chart('GraficoIngresos7dias', {
		type: 'bar',
		data: {
				labels: months,
				datasets: [{
				label: '$ Últimos 7 días',
				data: datas_array,
				borderWidth: 1,
				backgroundColor: [
					'rgba(255, 99, 132, 0.2)',
					'rgba(255, 159, 64, 0.2)',
					'rgba(255, 205, 86, 0.2)',
					'rgba(75, 192, 192, 0.2)',
					'rgba(54, 162, 235, 0.2)',
					'rgba(153, 102, 255, 0.2)',
					'rgba(201, 203, 207, 0.2)'
				  ],
				  borderColor: [
					'rgb(255, 99, 132)',
					'rgb(255, 159, 64)',
					'rgb(255, 205, 86)',
					'rgb(75, 192, 192)',
					'rgb(54, 162, 235)',
					'rgb(153, 102, 255)',
					'rgb(201, 203, 207)'
				  ],
				}	// datasets
			]	// datasets
		},	// data
		options: {
			scales: {
			y: {
				beginAtZero: true
			}
			}
		}
		});         // cierre del gráfico.
}

function GraficoIngresosPorAño(){
	/* datas */
	const months = ["Jan"];
	const datas_array = [23];

	/* create our chart */
	myChartIngresosPorAño = new Chart('GraficoIngresosPorAño', {
	type: 'bar',
	data: {
		labels: months,
		datasets: [{
		label: '$',
		backgroundColor: 'orange',
		data: datas_array
		}]
	},
	});
}