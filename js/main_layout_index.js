// id de user global
var idUser_ok = 0;
var accion_ok = 'noAccion';
   
$(function(){   
// Escribir la fecha actual.
var now = new Date();                
var day = ("0" + now.getDate()).slice(-2);
var month = ("0" + (now.getMonth() + 1)).slice(-2);
var year = now.getFullYear();

today = now.getFullYear()+"-"+(month)+"-"+(day) ;
$('#FechaDesdePD').val(today);
$('#FechaHastaPD').val(today);

                // Validar Formulario para la buscque de registro segun el criterio.   
		$('#formProduccionDiaria').validate({
                rules:{
                    FechaDesdePD: {required: true},
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
                //alert(str);
		        $.ajax({
		            beforeSend: function(){
                        $('#listadoVerProduccionDiaria').empty();
		            },
		            cache: false,
		            type: "POST",
		            dataType: "json",
		            url:"php_libs/soporte/layout-Index.php",
		            data:str + "&id=" + Math.random(),
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
		            },
		        });	// cierre de ajax
                    return false;
		    },
		});
///////////////////////////////////////////////////////////////////////////////
/// EVENTOS JQUERY Y para disparar la busqueda. del por nombre motorista.
///////////////////////////////////////////////////////////////////////////////
$("#goBuscarProduccionDiaria").on('click', function(){
	$("#formProduccionDiaria").submit();
});

}); // FINAL DELA FUNCION

