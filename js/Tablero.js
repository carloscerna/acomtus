// id de user global
var idUser_ok = 0;
var accion_ok = 'noAccion';
var fecha_desde = "";
var fecha_hasta = "";
var mostrarDias = 0;
var CodigoPerfil = "";
var year = 0;
//	ARMAR ITEM DE MENU DEPENDIENDO DEL CODIGO DEL USUARIO.
	// GESTION PRODUCCION
	var defaultContentMenu = '<div class="dropdown">'
			+'<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown"><i class="fas fa-wrench"></i></button>'
			+'<div class="dropdown-menu">'
				+'<a class="ver dropdown-item" href="#"><i class="fas fa-search"></i> Ver</a>'
				+'</div></div>';
// FUNCION RINCIPAL
$(function(){   
		///////////////////////////////////////////////////////////////////////////////
// INICIALIZARA DATATABLE. 
///////////////////////////////////////////////////////////////////////////////
$('#example').DataTable( { searching: false} );
var table = $('#listadoPorTiquete').DataTable( {
searching: false
});
///////////////////////////////////////////////////////////////////////////////
// INICIALIZARA DATATABLE. 
///////////////////////////////////////////////////////////////////////////////
$('#example').DataTable( { searching: false} );
var table_personal = $('#listadoPorPersonal').DataTable( {
searching: false
});
// Escribir la fecha actual.
	var now = new Date();                
	var day = ("0" + now.getDate()).slice(-2);
	var month = ("0" + (now.getMonth() + 1)).slice(-2);
	year = now.getFullYear();

//
	var today = now.getFullYear()+"-"+(month)+"-"+(day) ;
	$('#FechaDesdePD').val(today);
	$('#FechaHastaPD').val(today);
///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
$(document).ready(function(){
		//  NOMBRE INSTITUCIÓN
		NombreInstitucion = $("#NombreInstitucion").val();
		// CODIGO PERFIL
		CodigoPerfil = $("#CodigoPerfil").val();
		//
	fecha_desde = $("#FechaDesdePD").val();
	mostrarDias = 7;
	// LISTAR DATOS DEL PERSONAL DE LA EMRPESA.
	if(CodigoPerfil == "05"){
		GraficoPorDepartamentoEmpresa();		// muestra datos de los departamento de la empresa (cantidad de empleados masculino y femenino.).
		listar_empleados();
	}else{
		GraficoIngresosPorAño();	// datos de ingresos por mes del año.
		GraficoIngresos7dias();		// muestra los últimos 7 días.
		//
		listar_serie_tiquete()
	}
	
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
						myChartIngresosPorAño.config.data.label = "$ Ingresos Por Mes - año: " + response.GraficoYear;
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
///////////////////////////////////////////////////////
// Validar Formulario, para la busqueda de un registro por codigo del motorista.
// INCORPORACION DE DATATABLE.
 //////////////////////////////////////////////////////
// boton
$('#lstSerieBuscarTiquete').on('change', function(){
	// focus
	  var buscartodos = "BuscarTodosPorId";
	  var id_tiquete = $("#lstSerieBuscarTiquete").val();
   
	  ///////////////////////////////////////////////////////////////////////////////	  
	//
	   if (table != undefined && table != null) 
	   {
	   table.destroy();
	   table = null;
	   } 
	   
	// CONFIGURACIÓN DE LA TABLA CON DATATABLE.
	table = jQuery("#listadoPorTiquete").DataTable( {
		"ajax":{
			lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
			destroy: true,
			pageLength: 5,
			searching: false,
			url:"php_libs/soporte/Tablero.php",
			method:"POST",
			data: {"accion_buscar": buscartodos, Id_: id_tiquete
				},
			complete: function(data){
				var strJson = JSON.stringify(data["responseJSON"]); // convertir el objeto
				var jsonParseado = JSON.parse(strJson);	// pasearlo
				var totalTiquetes = 0; totalIngreso = 0;	// variales a 0
				var tiraje = Number(jsonParseado["data"][0].tiraje);
				var color = jsonParseado["data"][0].descripcion_tiquete;
				// CAMBIAR COLOR AL TIQUETE.
				$("#ColorTiquete").css({"color": "black"}); // <i class="fal fa-ticket-alt" style="color: #ffc0ff;"></i>
				switch (color) {
					case "Rosado":
						$("#FondoTiquete").css({"background": "#ffc0ff"}); // <i class="fal fa-ticket-alt" style="color: #ffc0ff;"></i>
						break;
					case "Amarillo":
						$("#FondoTiquete").css({"background": "#ffff00"}); // <i class="fal fa-ticket-alt" style="color: #ffc0ff;"></i>
						break;
				
					default:
						$("#FondoTiquete").css({"background": "white"}); // <i class="fal fa-ticket-alt" style="color: #ffc0ff;"></i>
						break;
				}
				//
				for (let index = 0; index < jsonParseado["data"].length; index++) {
					const element = jsonParseado["data"][index].total_tiquete_utilizados;
					const elementTotalIngreso = jsonParseado["data"][index].total_ingreso;
					totalTiquetes += parseFloat(element);
					totalIngreso += parseFloat(elementTotalIngreso);
				}
					// TOTAL DE TIQUETES
					//console.log(totalTiquetes);
						var totalTiqueteString = totalTiquetes.toLocaleString('es-SV'); // 0.00
						$("label[for='LblTotalTiquetes']").text(totalTiqueteString);
					// TOTAL DE INGRESO POR TIQUETE.
						var totalIngresoString = totalIngreso.toLocaleString('en-US'); // $0.000
						$("label[for='LblTotalIngreso']").text('$' + totalIngresoString);
				//	porcentaje // cambiaar css progress bar.
					var porcentaje_tiquetes = ((totalTiquetes * 100) / tiraje);
					porcentaje_tiquetes = (porcentaje_tiquetes.toFixed(0));
				// TIQUETES
					var totalTiquetesPantalla = totalTiquetes.toLocaleString("en-US");
					var tirajePantalla = tiraje.toLocaleString("en-US");
					$("label[for='LblTotalTiraje']").text('Tiraje: ' + tiraje.toLocaleString("en-US"));
						$('#progress-bar-tiquete').css('width', porcentaje_tiquetes +'%').attr('aria-valuenow', porcentaje_tiquetes);
					$("label[for='LblTotalTiquetes']").text('Utilizados: ' + totalTiquetesPantalla + " es el " + porcentaje_tiquetes + "% de " + tirajePantalla +".");
				// ACTUALIZAR EXISTENCIA DE TIQUETE
					$.post("php_libs/soporte/Tablero.php", {accion_buscar: 'actualizarExistencia', Id_: id_tiquete, totalTiquetes: totalTiquetes},
					//
						function(data) {
					}, "json");    
			},
			datatype: "json"
		},
		columns:[
			{
				data: null,
				defaultContent: defaultContentMenu,
				orderable: false
			},
		  {data:"codigo_produccion"},
		  {data:"fecha_"},
		  {data:"codigo_inventario_tiquete"},
		  {data:"descripcion_tiquete"},
		  {data:"precio_publico",
			   render: function(data, type, row){
				   return `<span class='font-weight-bold text-success text-right'>$`+data+`</span>`;
			   }
		   },	// Preico Púyblic.
		  {data:"total_ingreso",
			   render: function(data, type, row){
					   return `<span class='font-weight-bold text-success text-right'>$`+data+`</span>`;
			   }
		   }	/// total ingresos
		],
		"footerCallback": function ( row, data, start, end, display ) {
		   var api = this.api(), data;
   
		   // Remove the formatting to get integer data for summation
		   var intVal = function ( i ) {
			   return typeof i === 'string' ?
				   i.replace(/[\$,]/g, '')*1 :
				   typeof i === 'number' ?
					   i : 0;
		   };
   
		   // Total over all pages
		   total = api
			   .column( 6 )
			   .data()
			   .reduce( function (a, b) {
				   return intVal(a) + intVal(b);
			   }, 0 );
			   total = total.toFixed(2),
			   TotalAllPagina = new Intl.NumberFormat('en-US').format(total)
		   // Total over this page
		   pageTotal = api
			   .column( 6, { page: 'current'} )
			   .data()
			   .reduce( function (a, b) {
				   return intVal(a) + intVal(b);
			   }, 0 );
			   pagina = pageTotal.toFixed(2),
			   TotalPagina = new Intl.NumberFormat('en-US').format(pagina)
		   // Update footer
		   $( api.column( 6 ).footer() ).html(
			   '$'+ TotalPagina +' ( $'+ TotalAllPagina +')'
		   );
	   },
		language:{
		   url: "../acomtus/js/DataTablet/es-ES.json"
		},
		dom: "Bfrtip",
		  buttons:[
			//'excel',
			{
			  extend: 'excelHtml5',
			  text: '<i class="fas fa-file-excel"></i>',
			  titleAttr: 'Exportar a Excel',
			  className: 'btn btn-success',
			  filename: 'Reporte',
			  title: NombreInstitucion, 
			  exportOptions: {
				  columns: [1,2,3,4,5,6 ]
			  },
			  className: 'btn-exportar-excel',
		  },
		  //'pdf',
		  {
			  extend: 'pdfHtml5',
			  text: '<i class="fas fa-file-pdf"></i>',
			  titleAttr: 'Exportar a PDF',
			  className: 'btn btn-danger',
			  filename: 'Reporte',
			  title: NombreInstitucion, 
			  exportOptions: {
				columns: [1,2,3,4,5,6 ]
			  },
			  className: 'btn-exportar-pdf',
		  },
		  //'print'
		  {
			  extend: 'print',
			  text: '<i class="fa fa-print"></i>',
			  titleAttr: 'Imprimir',
			  className: 'btn btn-md btn-info',
			  title: NombreInstitucion, 
			  exportOptions: {
				columns: [1,2,3,4,5,6 ]
			  },
			  className:'btn-exportar-print'
		  },
		  //extra. Cantidad de registros.
		  'pageLength'
		  ],
	  }); // FINAL DEL DATATABLE.
	  obtener_data_editar("#listadoPorTiquete tbody", table);
}); // FINAL DEL botón.
	var obtener_data_editar = function(tbody, table){
		///////////////////////////////////////////////////////////////////////////////
		//	FUNCION que al dar clic buscar el registro para posterior mente abri una
		// ventana modal. EDITAR REGISTRO
		///////////////////////////////////////////////////////////////////////////////
		$(tbody).on("click","a.ver",function(){
			var data = table.row($(this).parents("tr")).data();
			console.log(data); console.log(data[0]);
			codigo_produccion = data[0];
			// Ejecutar Informe
			varenviar = "/acomtus/php_libs/reportes/Planilla/DetallePorMotorista.php?codigo_produccion="+codigo_produccion;
			// Ejecutar la función abre otra pestaña.
				AbrirVentana(varenviar);	
		});
	}; // Funcion principal dentro del DataTable.

///////////////////////////////////////////////////////////////////////////////	  
// BLOQUE EXTRAER INFORMACIÓN DEL REGISTROS (PRODUCCION ASIGNADO)
$('body').on('click','#listadoPorDepartamentoEmpresa a',function (e){
	e.preventDefault();
// DATA-ACCION Y HREF
	id_ = $(this).attr('href');
	accionAsignacion = $(this).attr('data-accion');
	
	// EDTIAR REGISTRO.
		if(accionAsignacion  == 'BuscarPorDepartamentoEmpresa'){	
			//
			buscartodos = "BuscarPorDepartamento";	// variable global
			if (table_personal != undefined && table_personal != null) 
			{
			table_personal.destroy();
			table_personal = null;
			} 
			
		 // CONFIGURACIÓN DE LA TABLA CON DATATABLE.
		 table_personal = jQuery("#listadoPorPersonal").DataTable( {
			 "ajax":{
				 lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
				 destroy: true,
				 pageLength: 5,
				 searching: false,
				 url:"php_libs/soporte/Tablero.php",
				 method:"POST",
				 data: {"accion_buscar": buscartodos, codigo_departamento: id_
					 },
				 datatype: "json"
			 },
			 columns:[
				{data:"codigo"},
				{data:"nombre_empleado"},
				{data:"nombre_departamento_empresa"},
				{data:"nombre_cargo"},
				{data:"nombre_genero",
				render: function(data, type, row){
					var genero = data;

					if(genero.trim() === 'Masculino'){
						return `<span class='font-weight-bold text-primary text-right'>`+data+`</span>`;
					}else{
						return `<span class='font-weight-bold text-danger text-right'>`+data+`</span>`;
					}
					
				}
				},
			   {data:"salario",
					render: function(data, type, row){
						return `<span class='font-weight-bold text-success text-right'>$`+data+`</span>`;
					}
				},	// Preico Púyblic.
			 ],
			 "footerCallback": function ( row, data, start, end, display ) {
				var api = this.api(), data;
		
				// Remove the formatting to get integer data for summation
				var intVal = function ( i ) {
					return typeof i === 'string' ?
						i.replace(/[\$,]/g, '')*1 :
						typeof i === 'number' ?
							i : 0;
				};
		
				// Total over all pages
				total = api
					.column( 5 )
					.data()
					.reduce( function (a, b) {
						return intVal(a) + intVal(b);
					}, 0 );
					total = total.toFixed(2),
					TotalAllPagina = new Intl.NumberFormat('en-US').format(total)
				// Total over this page
				pageTotal = api
					.column( 5, { page: 'current'} )
					.data()
					.reduce( function (a, b) {
						return intVal(a) + intVal(b);
					}, 0 );
					pagina = pageTotal.toFixed(2),
					TotalPagina = new Intl.NumberFormat('en-US').format(pagina)
				// Update footer
				$( api.column( 5 ).footer() ).html(
					'$'+ TotalPagina +' ( $'+ TotalAllPagina +')'
				);
			},
			 language:{
				url: "../acomtus/js/DataTablet/es-ES.json"
			 },
			 dom: "Bfrtip",
			   buttons:[
				 //'excel',
				 {
				   extend: 'excelHtml5',
				   text: '<i class="fas fa-file-excel"></i>',
				   titleAttr: 'Exportar a Excel',
				   className: 'btn btn-success',
				   filename: 'Reporte',
				   title: NombreInstitucion, 
				   exportOptions: {
					columns: [0,1,2,3,4,5 ]
				   },
				   className: 'btn-exportar-excel',
			   },
			   //'pdf',
			   {
				   extend: 'pdfHtml5',
				   text: '<i class="fas fa-file-pdf"></i>',
				   titleAttr: 'Exportar a PDF',
				   className: 'btn btn-danger',
				   filename: 'Reporte',
				   title: NombreInstitucion, 
				   exportOptions: {
					columns: [0,1,2,3,4,5 ]
				   },
				   className: 'btn-exportar-pdf',
			   },
			   //'print'
			   {
				   extend: 'print',
				   text: '<i class="fa fa-print"></i>',
				   titleAttr: 'Imprimir',
				   className: 'btn btn-md btn-info',
				   title: NombreInstitucion, 
				   exportOptions: {
					 columns: [0,1,2,3,4,5 ]
				   },
				   className:'btn-exportar-print'
			   },
			   //extra. Cantidad de registros.
			   'pageLength'
			   ],
		   }); // FINAL DEL DATATABLE.
			//
		}
});   // FINAL DEL IF DE LA CONSULTA QUE PROVIENE DE LA TABLA




}); // FINAL DELA FUNCION ///////////////////////
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
					beginAtZero: true,
					ticks: {
						// Include a dollar sign in the ticks
						callback: function(value, index, values) {
							return '$' + value.toFixed(2);
						}
					}					
				},
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
		label: 'Año ' + year,
		data: datas_array,
		borderWidth: 1,
		backgroundColor: [
			'RGBA(48,51,80,0.87)',
			'RGBA(144,213,27,0.87)',
			'RGBA(48,193,80,0.87)',
			'RGBA(144,213,234,0.87)',
			'RGBA(144,156,234,0.87)',
			'RGBA(91,156,234,0.87)',
			'RGBA(25,105,234,0.87)',
			'RGBA(214,105,234,0.87)',
			'RGBA(214,255,234,0.87)',
			'RGBA(214,255,75,0.87)',
			'RGBA(214,45,75,0.87)',
			'RGBA(47,88,75,0.87)'
			],
			borderColor: [
			'rgb(0,0,0)',
			'rgb(0,0,0)',
			'rgb(0,0,0)',
			'rgb(0,0,0)',
			'rgb(0,0,0)',
			'rgb(0,0,0)',
			'rgb(0,0,0)',
			'rgb(0,0,0)',
			'rgb(0,0,0)',
			'rgb(0,0,0)',
			'rgb(0,0,0)',
			'rgb(0,0,0)'
			],
		}]
	},
	});
}
function GraficoPorDepartamentoEmpresa(){
	/* datas */
	const company = ["Departamentos de la Empresa"];
	const datas_array = [2];

	/* create our chart */
	myChartPorDepartamentoEmpresa = new Chart('GraficoPorDepartamentoEmpresa', {
	type: 'pie',
	data: {
		labels: company,
		datasets: [{
		label: [
			'Cantidad'
		],
		backgroundColor: 'orange',
		data: datas_array,
		borderWidth: 1,
				backgroundColor: [
					'rgb(105, 99, 1, 0.2)',
					'rgba(25, 159, 64, 0.9)',
					'rgba(255, 205, 86, 0.8)',
					'rgba(75, 192, 192, 0.7)',
					'rgba(254, 262, 225, 0.7)',
					'rgba(153, 102, 255, 0.9)',
					'rgba(203, 207, 0.2)',
					'rgba(23, 217, 0.2)',
					'rgba(203, 247, 0.2)',
					'rgba(201,20, 0.2)'
				  ],
				  borderColor: [
					'rgb(0, 0, 0)',
					'rgb(0, 0, 0)',
					'rgb(0, 0, 0)',
					'rgb(0, 0, 0)',
					'rgb(0, 0, 0)',
					'rgb(0, 0, 0)',
					'rgb(0, 0, 0)',
					'rgb(0, 0, 0)',
					'rgb(0, 0, 0)',
					'rgb(0, 0, 0)'
				  ],
		}]
	},
	});
}
// TODAS LAS TABLAS VAN HA ESTAR EN PRODUCCIONBUSCAR.*******************
// FUNCION LISTAR TABLA catalogo_tiquete_color, inventario_tiquete
////////////////////////////////////////////////////////////
function listar_serie_tiquete(){
    var miselect=$("#lstSerieBuscarTiquete");
    /* VACIAMOS EL SELECT Y PONEMOS UNA OPCION QUE DIGA CARGANDO... */
    miselect.find('option').remove().end().append('<option value="">Cargando...</option>').val('');
    
    $.post("php_libs/soporte/Produccion/ProduccionBuscar.php", {accion_buscar: 'BuscarSerie'},
        function(data) {
            miselect.empty();
            miselect.append('<option value="">0-Seleccionar...</option>');
            for (var i=0; i<data.length; i++) {
                    miselect.append('<option value="' + data[i].codigo + '">' + data[i].descripcion + " - " + data[i].tiquete_color + " - " + data[i].precio_publico + '</option>');
            }
    }, "json");    
}    
// TODAS LAS TABLAS VAN HA ESTAR EN PRODUCCIONBUSCAR.*******************
// FUNCION LISTAR TABLA catalogo_tiquete_color, inventario_tiquete
////////////////////////////////////////////////////////////
function listar_empleados(){
			  // PROCESO PARA ELIMINAR REGISTRO.
			  $.ajax({
				cache: false,
				type: "POST",
				dataType: "json",
				url:"php_libs/soporte/Tablero.php",
				data: "accion=" + "BuscarEmpleados",
				success: function(response){
					// Validar mensaje de error proporcionado por el response. contenido.
					if(response.respuesta == false){
						toastr["error"](response.mensaje, "Sistema ACOMTUS");
					}
					else{
						$("label[for='LblTotalEmpleados']").text(response.TotalEmpleados);
						$("#TotalActivos").text("Activos: " + response.TotalActivos);
						//
						$("label[for='LblTotalActivosMasculino']").text(response.TotalActivosMasculino);
						$("label[for='LblTotalActivosFemenino']").text(response.TotalActivosFemenino);
						toastr["success"](response.mensaje, "Sistema ACOMTUS");

						}               
				},
			});
			// DEPARTAMENTO EMPRESA
			$.ajax({
			cache: false,
			type: "POST",
			dataType: "json",
			url:"php_libs/soporte/Tablero.php",
			data: "accion=" + "BuscarEmpleadosPorDepartamento",
			success: function(response){
				// Validar mensaje de error proporcionado por el response. contenido.
				if(response.respuesta == false){
					toastr["error"](response.mensaje, "Sistema");
				}
				else{
					$("#listadoPorDepartamentoEmpresaOk").empty();
					$("#listadoPorDepartamentoEmpresaOk").append(response.contenido);
					toastr["success"](response.mensaje, "Sistema Acomtus");
					// MUESTRA LA INFOMRACIÓN DEl DEPARTAMENTO POR EMPRESA EMPLEADOS
					console.log(myChartPorDepartamentoEmpresa); // check the console to see different properities of the current Chart object.. which of course we can set to new values and then update with the .update() function
					myChartPorDepartamentoEmpresa.config.data.labels = response.NombreDepartamentoEmpresa; //newLabelsArray; // silo down and replace current property with new value
					myChartPorDepartamentoEmpresa.config.data.datasets[0].data = response.CantidadDepartamentoEmpresa; //newDatasArray;
					/* update chart */
					myChartPorDepartamentoEmpresa.update();
				}               
			},
		});
}    