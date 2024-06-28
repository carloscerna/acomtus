// id de user global
var id_ = 0;
var MenuEmergente = "";
var tabla = "";
//	ARMAR ITEM DE MENU DEPENDIENDO DEL CODIGO DEL USUARIO.
	// GESTION PERSONAL
	var defaultContentMenu = '<div class="dropdown">'
			+'<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown"><i class="fas fa-wrench"></i></button>'
			+'<div class="dropdown-menu">'
				+'<a class="editar dropdown-item" href="#"><i class="fas fa-edit"></i> Editar</a>'
				+'<a class="FianzasPrestamos dropdown-item" href="#"><i class="fas fa-dollar-sign"> Fianzas y Prestamos</i></a>'
				+'<a class="eliminar dropdown-item" href="#"><i class="fas fa-trash-alt"> Eliminar</i></a>'
				+'</div></div>';
$(function(){ // iNICIO DEL fUNCTION.
///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
		$(document).ready(function(){
			// Cambiar el Menú Contextual que se cuentra a la par de cada empleado.
			MenuEmergente = $('#MenuContextual').val();
			if(MenuEmergente == '0'){
				defaultContentMenu = '<div class="dropdown">'
				+'<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown"><i class="fas fa-wrench"></i></button>'
				+'<div class="dropdown-menu">'
				+'<a class="editar dropdown-item" href="#"><i class="fas fa-edit"></i> Editar</a>'
				+'</div></div>';
			}
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
				"pageLength": 5,
				"async": false ,
				"processing": true,
				"ajax":{
					method:"POST",
					url:"php_libs/soporte/Personal/PersonalBuscar.php",
					data: {"accion_buscar": buscartodos}
				},
				"columns":[
					{
						data: null,
						defaultContent: defaultContentMenu,
						orderable: false
					},
					{"data":"id_personal"},
                    {"data":"codigo"},
                    {"data":"nombre_empleado"},
					{"data":"telefono_celular"},
                    {"data":"saldo_fianza"},
                    {"data":"saldo_prestamo"},
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
		});
			obtener_data_editar("#listado tbody", tabla);
		};
// 
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
			window.location.href = 'editar_Nuevo_Personal.php?id='+id_+"&accion="+accion;
	});
///////////////////////////////////////////////////////////////////////////////
//	FUNCION que al dar clic buscar el registro para posterior mente abri una
// ventana modal. EDITAR REGISTRO
///////////////////////////////////////////////////////////////////////////////
$(tbody).on("click","a.FianzasPrestamos",function(){
	var data = tabla.row($(this).parents("tr")).data();
	console.log(data); console.log(data[0]);
	
	id_ = data[0];
	accion = "BuscarFianzasPrestamos";	// variable global
		window.location.href = 'editar_Fianzas_Prestamos.php?id_personal='+id_+"&accion="+accion;
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
				url:"php_libs/soporte/NuevoEditarPersonal.php",
				data: "id_user=" + id_ + "&accion=" + accion + "&nombre=" + nombre,
				success: function(response){
					// Validar mensaje de error proporcionado por el response. contenido.
					if(response.respuesta == false){
						toastr["error"](response.mensaje, "Sistema");
					}
					else{
						toastr["success"](response.mensaje, "Sistema");
							window.location.href = 'personal.php';
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
		accion = "AgregarNuevoPersonal";	// variable global
		id_ = 0;
			window.location.href = 'editar_Nuevo_Personal.php?id='+id_+"&accion="+accion;
});	  
///////////////////////////////////////////////////////////////////////////////
//	FUNCION que al dar clic buscar el registro para posterior mente abri una
// ventana modal. GIANZAS Y PRESTAMOS IMPORTAR.
///////////////////////////////////////////////////////////////////////////////	  
$('#goFianzasPrestamosImportar').on( 'click', function () {
	accion = "FianzasPrestamosImportar";	// variable global
	id_ = 0;
		window.location.href = 'fianzas_prestamos_importar.php?';
});	
});	// final de FUNCTION.
// Abrir Ventana
function AbrirVentana(url)
{
    window.open(url, '_blank');
    return false;
}