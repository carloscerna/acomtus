// id de user global
var id_ = 0;
var NIE = 0;
var tabla = "";
$(function(){ // iNICIO DEL fUNCTION.
	///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
$(document).ready(function(){
	//  NOMBRE INSTITUCIÓN
		NombreInstitucion = $("#NombreInstitucion").val();
		listar();
});		
///////////////////////////////////////////////////////////////////////////////
// INICIALIZARA DATATABLE. POR PLACA y NOMBRE MOTORISTA.
///////////////////////////////////////////////////////////////////////////////
$('#example').DataTable( { searching: false} );
var table = $('#listadoTransporte').DataTable( {
  searching: false
  });

   ///////////////////////////////////////////////////////////////////////////////	  
 //
 if (table != undefined && table != null) 
 {
 table.destroy();
 table = null;
 } 
 
 var listar = function(){
	// Varaible de Entornos.php
		var buscartodos = "BuscarTodos";
	// CONFIGURACIÓN DE LA TABLA CON DATATABLE.
	table = jQuery("#listadoTransporte").DataTable({
	"ajax":{
		lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
		destroy: true,
		pageLength: 5,
		searching: false,
		url:"php_libs/soporte/Catalogos/TransporteColectivoBuscar.php",
		method:"POST",
		data: {"accion_buscar": buscartodos},
		datatype: "json"
	},
	columns:[
		{
			data: null,
			defaultContent: '<div class="dropdown"><button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown"><i class="fas fa-wrench"></i></button><div class="dropdown-menu"><a class="editar dropdown-item" href="#"><i class="fas fa-edit"></i> Editar</a><a class="eliminar dropdown-item" href="#"><i class="fas fa-trash-alt"> Eliminar</i></a></div></div>',
			orderable: false
		},
		{data:"id_"},
		{data:"nombre_tipo_transporte"},
		{data:"equipo_placa_year"},
		{data:"vencimiento"},
		{data:"emision"},
		{data:"año"},
		{data:"marca"},
		{data:"modelo"},
		{"data":"codigo_estatus",
		render: function(data, type, row){
			if(data == '01'){
				return "<span class='font-weight-bold text-success'>Activo</span>";
			}else{
				return "<span class='font-weight-bold text-danger'>Inactivo</span>";
			}
		}
	},
	],
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
			title: NombreInstitucion + " Unidades de Transporte", 
			exportOptions: {
				columns: [2,3,4,5,6,7,8]
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
			title: NombreInstitucion + " Unidades de Transporte", 
			exportOptions: {
				columns: [2,3,4,5,6,7,8]
			},
			className: 'btn-exportar-pdf',
		},
		//'print'
		{
			extend: 'print',
			text: '<i class="fa fa-print"></i>',
			titleAttr: 'Imprimir',
			className: 'btn btn-md btn-info',
			title: NombreInstitucion + " Unidades de Transporte", 
			exportOptions: {
				columns: [2,3,4,5,6,7,8]
			},
			className:'btn-exportar-print'
		},
		//extra. Cantidad de registros.
		'pageLength'
		],
	}); // FINAL DEL DATATABLE.
	obtener_data_editar("#listadoTransporte tbody", table);
}; // fin de la función del datatablet.

///////////////////////////////////////////////////////////////////////////////
//	FUNCION LISTAR BUSQUEDA DE LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
var obtener_data_editar = function(tbody, table){
///////////////////////////////////////////////////////////////////////////////
//	FUNCION que al dar clic buscar el registro para posterior mente abri una
// ventana modal. EDITAR REGISTRO
///////////////////////////////////////////////////////////////////////////////
	$(tbody).on("click","a.editar",function(){
		var data = table.row($(this).parents("tr")).data();
		console.log(data); console.log(data[0]);
		
		id_ = data[0];
		accion = "EditarRegistro";	// variable global
			window.location.href = 'EditarNuevoTransporte.php?id='+id_+"&accion="+accion;
	});
///////////////////////////////////////////////////////////////////////////////
//	FUNCION que al dar clic buscar el registro para posterior mente abri una
// ventana modal. ELIMINAR REGISTRO
///////////////////////////////////////////////////////////////////////////////
	$(tbody).on("click","a.eliminar",function(){
		var data = tabla.row($(this).parents("tr")).data();
		console.log(data); console.log(data[0]);
		
		id_ = data[0];
		nombre = data[1];
		accion = "EliminarRegistro";	// variable global
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
				cache: false,
				type: "POST",
				dataType: "json",
				url:"php_libs/soporte/.php",
				data: "id_user=" + id_ + "&accion=" + accion + "&nombre=" + nombre,
				success: function(response){
					// Validar mensaje de error proporcionado por el response. contenido.
					if(response.respuesta == false){
						toastr["error"](response.mensaje, "Sistema");
					}
					else{
						toastr["success"](response.mensaje, "Sistema");
							window.location.href = 'usuarios.php';
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
	});
}; // Funcion principal dentro del DataTable.

///////////////////////////////////////////////////////////////////////////////
//	FUNCION que al dar clic buscar el registro para posterior mente abri una
// ventana modal. GENERAR NUEVO REGISTRO
///////////////////////////////////////////////////////////////////////////////	  
$('#goNuevoUser').on( 'click', function () {
	accion = "AgregarNuevo";	// variable global
	id_ = 0;
		window.location.href = 'EditarNuevoTransporte.php?id='+id_+"&accion="+accion;
});	  

});	// final de FUNCTION.

function AbrirVentana(url)
{
    window.open(url, '_blank');
    return false;
}