// id de user global
var id_ = 0;
var NIE = 0;
var tabla = "";
$(function(){ // iNICIO DEL fUNCTION.
///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
		$(document).ready(function(){
			listar();
		});		
///////////////////////////////////////////////////////////////////////////////
//	FUNCION LISTAR BUSQUEDA DE LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
var listar = function(){
		// Varaible de Entornos.php
			var buscartodos = "BuscarTodos";
		// Tabla que contrendrá los registros.
			tabla = jQuery("#listado").DataTable({
				"lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
				"destroy": true,
				"pageLength": 10,
				"bLengthChange":false,
				"searching":{"regex": true},
				"async": false ,
				"processing": true,
				"ajax":{
					method:"POST",
					url:"php_libs/soporte/ProveedoresBuscar.php",
					data: {"accion_buscar": buscartodos}
				},
				"columns":[
					{
						data: null,
						defaultContent: '<div class="dropdown"><button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown"><i class="fas fa-wrench"></i></button><div class="dropdown-menu"><a class="editar dropdown-item" href="#"><i class="fas fa-edit"></i> Editar</a><a class="eliminar dropdown-item" href="#"><i class="fas fa-trash-alt"> Eliminar</i></a></div></div>',
						orderable: false
					},
                    {"data":"id_"},
                    {"data":"nombre"},
					{"data":"telefono_uno"},
                    {"data":"telefono_celular"}
				],
				// LLama a los diferentes mensajes que están en español.
				"language": idioma_espanol
		});
			obtener_data_editar("#listado tbody", tabla);
			// CONFIGURAR EL FILTER A OTRO OBJETO.
			document.getElementById("listado_filter").style.display="none";
			$('input.global_filter').on( 'keyup click', function () {
				 filterGlobal();
			 } );
			 $('input.column_filter').on( 'keyup click', function () {
				 filterColumn( $(this).parents('tr').attr('data-column') );
			 });
		};
///////////////////////////////////////////////////////////////////////////////
// CONFIGURACIÓN DEL IDIOMA AL ESPAÑOL.
///////////////////////////////////////////////////////////////////////////////	
var idioma_espanol = {
			"sProcessing":     "Procesando...",
			"sLengthMenu":     "Mostrar _MENU_ registros",
			"sZeroRecords":    "No se encontraron resultados",
			"sEmptyTable":     "Ningún dato disponible en esta tabla",
			"sInfo":           "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
			"sInfoEmpty":      "Mostrando registros del 0 al 0 de un total de 0 registros",
			"sInfoFiltered":   "(filtrado de un total de _MAX_ registros)",
			"sInfoPostFix":    "",
			"sSearch":         "Buscar:",
			"sUrl":            "",
			"sInfoThousands":  ",",
			"sLoadingRecords": "Cargando...",
			"oPaginate": {
			"sFirst":    "Primero",
			"sLast":     "Último",
			"sNext":     "Siguiente",
			"sPrevious": "Anterior"
			},
			"oAria": {
			    "sSortAscending":  ": Activar para ordenar la columna de manera ascendente",
			    "sSortDescending": ": Activar para ordenar la columna de manera descendente"
			}
		 };	  

var obtener_data_editar = function(tbody, tabla){
///////////////////////////////////////////////////////////////////////////////
//	FUNCION que al dar clic buscar el registro para posterior mente abri una
// ventana modal. EDITAR REGISTRO
///////////////////////////////////////////////////////////////////////////////
	$(tbody).on("click","a.editar",function(){
		var data = tabla.row($(this).parents("tr")).data();
		console.log(data); console.log(data[0]);
		
		id_ = data[0];
		accion = "EditarRegistro";	// variable global
			window.location.href = 'editar_Nuevo_Proveedores.php?id='+id_+"&accion="+accion;
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
// ventana modal. GENERAR NUEVO ESTUDIANTE
///////////////////////////////////////////////////////////////////////////////	  
$('#goNuevoUser').on( 'click', function () {
		accion = "AgregarNuevo";	// variable global
		id_ = 0;
			window.location.href = 'editar_Nuevo_Proveedores.php?id='+id_+"&accion="+accion;
});	  
});	// final de FUNCTION.

function AbrirVentana(url)
{
    window.open(url, '_blank');
    return false;
}
///////////////////////////////////////////////////////////////////////////////
//	FUNCION PARA CAMBIAR LA FORMA DE BUSQUEDA
function filterGlobal() {
    $('#listado').DataTable().search(
        $('#global_filter').val(),
    ).draw();
}