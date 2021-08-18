// id de user global
var id_ = 0;
var accionCatalogo = 'noAccion';
var CodigoTabla = '';
var tabla = '';
var pagina = 1;
var NombreTabla = '';
var MenuEmergente = '<div class="dropdown">'+
                        '<button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">'+
                            '<i class="fas fa-wrench"></i>'+
                        '</button>'+
                            '<div class="dropdown-menu">'+
                                '<a class="editar dropdown-item" href="#"><i class="fas fa-edit"></i> Editar</a>'+
                                '<a class="eliminar dropdown-item" href="#"><i class="fas fa-trash-alt"> Eliminar</i></a>'+
                            '</div>'+
                    '</div>';

$(function(){ // INICIO DEL FUNCTION.
    ///////////////////////////////////////////////////////////////////////////////
// FUNCION QUE CARGA LA TABLA COMPLETA CON LOS REGISTROS
///////////////////////////////////////////////////////////////////////////////
		$(document).ready(function(){
            accionCatalogo = $('#accionCatalogo').val();
			CodigoTabla = $('#CodigoTabla').val();
			$('#lstCatalogo').val(CodigoTabla);
            $("label[for='LblCatalogo']").text('Tabla: ' + $('#lstCatalogo option:selected').text());
			VerTablaCatalogo();
		});	
// ventana modal. GENERAR NUEVO REGISTRO PARA LA DETERMINA QUE SE GUARDA LA FIANZA.
///////////////////////////////////////////////////////////////////////////////	  
$('#goNuevoCatalogo').on( 'click', function () {
	// Variables accion para guardar datos.
	// Seleccionar el accion para la tabla correspondiente.
		switch ($("#lstCatalogo").val()) {
			case '0':  ///// Cargo
				accionCatalogo = "AgregarNuevo";	// accion
				NombreTabla = $('#lstCatalogo option:selected').text();
				id_ = $("#IdCatalogo").val();
			break;
			default:
				accionCatalogo = "AgregarNuevo";	// accion
				NombreTabla = $('#lstCatalogo option:selected').text();
				id_ = $("#IdCatalogo").val();
				break;
		}
	// Enviar url con variables.
		window.location.href = 'editar_Nuevo_Catalogos.php?id_='+id_+"&accionCatalogo="+accionCatalogo+"&CodigoTabla="+CodigoTabla+"&NombreTabla="+NombreTabla;
});	  
// ventana modal. GENERAR NUEVO REGISTRO PARA LA DETERMINA QUE SE GUARDA LA FIANZA.
///////////////////////////////////////////////////////////////////////////////	  
$('#goBuscarTodos').on( 'click', function () {
    // Variables accion para guardar datos.

        // Seleccionar el accion para la tabla correspondiente.
            switch ($("#lstCatalogo").val()) {
                case '0':  ///// Cargo
                    $("#accionCatalogo").val("BuscarTodos");	// accion
                    $("label[for='LblCatalogo']").text($('#lstCatalogo').html() + "Lista");	// label
					accionCatalogo = $('#accionCatalogo').val();
					
                break;
				
                default:
					$("#accionCatalogo").val("BuscarTodos");	// accion    
					accionCatalogo = $('#accionCatalogo').val();
					CodigoTabla = $("#lstCatalogo").val();
                    break;
            }
	//	Destroy datatable.
    //	VerTablaCatalogo();
        window.location.href = 'catalogos.php?accionCatalogo='+accionCatalogo+"&CodigoTabla="+CodigoTabla;
});	 
//************************************/
}); // fin de la funcion principal ************************************/
//************************************/
///////////////////////////////////////////////////////////////////////////
//FUNCION ABRE OTRA PESTAÑA.
///////////////////////////////////////////////////////////////////////////
function AbrirVentana(url)
{
    window.open(url, '_blank');
    return false;
}		
// Pasar foco cuando seleccionar un encargado.
function PasarFoco()
   {
       $('#Descripcion').focus();
   }
function LimpiarCampos(){
	$('#Descripcion').val('');
}
var VerTablaCatalogo = function () {
// Tabla que contrendrá los registros.
	var tabla = jQuery("#listado").DataTable({
		"lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "All"]],
		"autoWidth": true,
		"destroy": true,
		"pageLength": 5,
		"bLengthChange":false,
		"searching":{"regex": true},
		"async": false ,
        "processing": true,
		"autoWidth": true,
		"ajax":{
			method:"POST",
			url:"php_libs/soporte/EditarCatalogo.php",
			data: {"accionCatalogo": accionCatalogo, "CodigoTabla": CodigoTabla}
		},
		"columns":[
			{
				data: null,
				defaultContent: MenuEmergente,
				orderable: false
			},
			{"data":"id_"},
			{"data":"codigo"},
            {"data":"descripcion"},
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
	// Capturar el id_, accionCatalogo, TablaCatalogo
		id_ = data[0];
		accionCatalogo = 'EditarRegistro';
		NombreTabla = $('#lstCatalogo option:selected').text();
		CodigoTabla = $('#lstCatalogo').val();
	// Pasar el control por accionCatalogo.
			window.location.href = 'editar_Nuevo_Catalogos.php?id_='+id_+"&accionCatalogo="+accionCatalogo+"&NombreTabla="+NombreTabla+"&CodigoTabla="+CodigoTabla;
});
}
///////////////////////////////////////////////////////////////////////////////
//	FUNCION PARA CAMBIAR LA FORMA DE BUSQUEDA
function filterGlobal() {
    $('#listado').DataTable().search(
        $('#global_filter').val(),
    ).draw();
}