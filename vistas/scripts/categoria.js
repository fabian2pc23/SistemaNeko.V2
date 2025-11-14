var tabla;
var filtroActual = 'all'; // Variable global para mantener el filtro activo

//Función que se ejecuta al inicio
function init(){
	mostrarform(false);
	listar();

	$("#formulario").on("submit",function(e)
	{
		guardaryeditar(e);	
	});

	$('#mAlmacen').addClass("treeview active");
    $('#lCategorias').addClass("active");
}

//Función limpiar
function limpiar()
{
	$("#idcategoria").val("");
	$("#nombre").val("");
	$("#descripcion").val("");
}

//Función mostrar formulario
function mostrarform(flag)
{
	limpiar();
	if (flag)
	{
		$("#listadoregistros").hide();
		$("#formularioregistros").show();
		$("#btnGuardar").prop("disabled",false);
		$("#btnagregar").hide();
	}
	else
	{
		$("#listadoregistros").show();
		$("#formularioregistros").hide();
		$("#btnagregar").show();
	}
}

//Función cancelarform
function cancelarform()
{
	limpiar();
	mostrarform(false);
}

//Función Listar optimizada
function listar()
{
	// Registrar el filtro permanente de DataTables
	$.fn.dataTable.ext.search.push(
		function(settings, data, dataIndex) {
			// Si no hay filtro o es "all", mostrar todo
			if (filtroActual === 'all') {
				return true;
			}
			
			var estadoHTML = data[3] || ''; // Columna 3 es Estado
			
			if (filtroActual === 'activos') {
				// Verificar que contiene "Activado" pero NO "Desactivado"
				return estadoHTML.indexOf('Activado') !== -1 && 
				       estadoHTML.indexOf('Desactivado') === -1;
			} else if (filtroActual === 'inactivos') {
				// Verificar que contiene "Desactivado"
				return estadoHTML.indexOf('Desactivado') !== -1;
			}
			
			return true;
		}
	);

	tabla = $('#tbllistado').DataTable({
		"lengthMenu": [ 5, 10, 25, 75, 100],
		"processing": false,
	    "serverSide": false,
	    "dom": 'rtip', // r=processing, t=table, i=info, p=pagination (sin l=length ni f=filter)
	    "ajax": {
			"url": '../ajax/categoria.php?op=listar',
			"type": "GET",
			"dataType": "json",
			"dataSrc": function(json) {
				if (json && json.aaData) {
					return json.aaData;
				}
				return [];
			}
		},
		"columns": [
			{ "data": "0" },
			{ "data": "1" },
			{ "data": "2" },
			{ "data": "3" }
		],
		"language": {
            "lengthMenu": "Mostrar _MENU_ registros",
            "sProcessing": "Procesando...",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en la tabla",
            "sInfo": "Mostrando del _START_ al _END_ de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando del 0 al 0 de 0 registros",
            "sInfoFiltered": "(filtrado de _MAX_ registros totales)",
            "sSearch": "Buscar:",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            }
        },
		"destroy": true,
		"pageLength": 10,
	    "order": [[ 1, "asc" ]],
	    "drawCallback": function() {
	    	actualizarKPIs();
	    }
	});

	// Selector de cantidad de registros personalizado
	$('#page-length-selector').off('change').on('change', function() {
		var length = parseInt($(this).val());
		tabla.page.len(length).draw();
	});

	// Búsqueda personalizada
	$('#searchInput').off('keyup').on('keyup', function() {
		var searchValue = this.value;
		console.log('🔍 Buscando:', searchValue);
		tabla.search(searchValue).draw();
	});

	// Cargar KPIs iniciales
	setTimeout(function() {
		actualizarKPIs();
		cargarKPIsAdicionales();
	}, 500);
}

// ==================== ACTUALIZAR KPIs ====================
function actualizarKPIs() {
	if (!tabla) return;

	var data = tabla.rows({search: 'applied'}).data();
	var total = data.length;
	var activas = 0;
	var inactivas = 0;

	for (var i = 0; i < data.length; i++) {
		var row = data[i];
		var estadoHTML = row["3"] || row[3];
		
		if (estadoHTML && estadoHTML.indexOf('Activado') > -1) {
			activas++;
		} else if (estadoHTML && estadoHTML.indexOf('Desactivado') > -1) {
			inactivas++;
		}
	}

	$('#kpi-total').text(total);
	$('#kpi-activas').text(activas);
	$('#kpi-inactivas').text(inactivas);
}

// ==================== CARGAR KPIs ADICIONALES ====================
function cargarKPIsAdicionales() {
	// KPI: Categorías sin artículos (con nombres)
	$.ajax({
		url: '../ajax/categoria.php?op=categorias_sin_articulos',
		type: 'GET',
		dataType: 'json',
		success: function(data) {
			if (data && data.success) {
				var sinArticulos = parseInt(data.total) || 0;
				var categorias = data.categorias || [];
				
				$('#kpi-sin-articulos').text(sinArticulos);
				
				// Si hay categorías sin artículos, agregar tooltip
				if (categorias.length > 0) {
					var tooltipText = 'Categorías: ' + categorias.join(', ');
					$('#kpi-sin-articulos')
						.attr('title', tooltipText)
						.attr('data-toggle', 'tooltip')
						.attr('data-placement', 'top')
						.css({
							'cursor': 'help',
							'text-decoration': 'underline',
							'text-decoration-style': 'dotted'
						});
					
					// Inicializar tooltip de Bootstrap si existe
					if (typeof $.fn.tooltip !== 'undefined') {
						$('#kpi-sin-articulos').tooltip();
					}
					
					console.log('✅ KPI sin artículos con tooltip:', tooltipText);
				} else {
					$('#kpi-sin-articulos').attr('title', 'Todas las categorías tienen artículos');
				}
			} else {
				$('#kpi-sin-articulos').text('0');
			}
		},
		error: function(xhr, status, error) {
			console.error('❌ Error al cargar categorías sin artículos:', error);
			$('#kpi-sin-articulos').text('0');
		}
	});

	// KPI: Categorías con stock crítico
	$.ajax({
		url: '../ajax/categoria.php?op=categorias_stock_critico',
		type: 'GET',
		dataType: 'json',
		success: function(data) {
			if (data && data.success) {
				var stockCritico = parseInt(data.total) || 0;
				$('#kpi-stock-critico').text(stockCritico);
			} else {
				$('#kpi-stock-critico').text('0');
			}
		},
		error: function() {
			$('#kpi-stock-critico').text('0');
		}
	});

	// KPI: Categorías nuevas
	$.ajax({
		url: '../ajax/categoria.php?op=categorias_nuevas',
		type: 'GET',
		dataType: 'json',
		success: function(data) {
			if (data && data.success) {
				var nuevas = parseInt(data.total) || 0;
				$('#kpi-nuevas').text(nuevas);
			} else {
				$('#kpi-nuevas').text('0');
			}
		},
		error: function() {
			$('#kpi-nuevas').text('0');
		}
	});
}

// ==================== FILTROS DE TABLA (VERSIÓN FINAL CORREGIDA) ====================
function filtrarTabla(filtro) {
	console.log('🔽 Filtrar tabla:', filtro);
	
	// Actualizar variable global
	filtroActual = filtro;
	
	// Actualizar botones activos
	$('.filter-btn').removeClass('active');
	$('.filter-btn[data-filter="' + filtro + '"]').addClass('active');

	// Simplemente redibujar - el filtro permanente se encargará del resto
	tabla.draw();

	// Actualizar KPIs después del filtro
	setTimeout(function() {
		actualizarKPIs();
		
		// Debug: mostrar cuántas filas quedaron después del filtro
		var rowsVisible = tabla.rows({search: 'applied'}).count();
		console.log('📊 Filas visibles después del filtro:', rowsVisible);
	}, 200);
}

// ==================== EXPORTAR TABLA PROFESIONAL ====================
function exportarTabla(tipo) {
	switch(tipo) {
		case 'copy':
			copiarTablaAlPortapapeles();
			break;
		case 'excel':
			exportarAExcel();
			break;
		case 'csv':
			exportarACSV();
			break;
		case 'pdf':
			exportarAPDF();
			break;
	}
}

// Copiar tabla al portapapeles
function copiarTablaAlPortapapeles() {
	var data = tabla.rows({search: 'applied'}).data();
	var texto = "NOMBRE\tDESCRIPCIÓN\tESTADO\n";
	
	for (var i = 0; i < data.length; i++) {
		var row = data[i];
		// Extraer texto sin HTML
		var nombre = $('<div>').html(row[1]).text();
		var descripcion = $('<div>').html(row[2]).text();
		var estado = $('<div>').html(row[3]).text();
		
		texto += nombre + "\t" + descripcion + "\t" + estado + "\n";
	}
	
	// Copiar al portapapeles
	if (navigator.clipboard && window.isSecureContext) {
		navigator.clipboard.writeText(texto).then(function() {
			mostrarNotificacion('✅ Tabla copiada al portapapeles', 'success');
		}, function() {
			copiarAlPortapapelesLegacy(texto);
		});
	} else {
		copiarAlPortapapelesLegacy(texto);
	}
}

// Método legacy para copiar (navegadores antiguos)
function copiarAlPortapapelesLegacy(texto) {
	var textarea = document.createElement('textarea');
	textarea.value = texto;
	textarea.style.position = 'fixed';
	textarea.style.opacity = '0';
	document.body.appendChild(textarea);
	textarea.select();
	
	try {
		document.execCommand('copy');
		mostrarNotificacion('✅ Tabla copiada al portapapeles', 'success');
	} catch (err) {
		mostrarNotificacion('❌ No se pudo copiar. Usa Ctrl+C manualmente', 'error');
	}
	
	document.body.removeChild(textarea);
}

// Exportar a Excel (formato profesional)
function exportarAExcel() {
	var data = tabla.rows({search: 'applied'}).data();
	
	// Crear contenido HTML para Excel
	var html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
	html += '<head><meta charset="UTF-8"><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet>';
	html += '<x:Name>Categorías</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet>';
	html += '</x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>';
	
	html += '<table border="1">';
	html += '<thead><tr style="background-color:#1565c0;color:white;font-weight:bold;">';
	html += '<th>NOMBRE</th><th>DESCRIPCIÓN</th><th>ESTADO</th></tr></thead><tbody>';
	
	for (var i = 0; i < data.length; i++) {
		var row = data[i];
		var nombre = $('<div>').html(row[1]).text();
		var descripcion = $('<div>').html(row[2]).text();
		var estado = $('<div>').html(row[3]).text();
		
		html += '<tr>';
		html += '<td>' + nombre + '</td>';
		html += '<td>' + descripcion + '</td>';
		html += '<td>' + estado + '</td>';
		html += '</tr>';
	}
	
	html += '</tbody></table></body></html>';
	
	// Crear blob y descargar
	var blob = new Blob(['\ufeff', html], {
		type: 'application/vnd.ms-excel'
	});
	
	var url = window.URL.createObjectURL(blob);
	var a = document.createElement('a');
	a.href = url;
	a.download = 'categorias_' + obtenerFechaHora() + '.xls';
	document.body.appendChild(a);
	a.click();
	document.body.removeChild(a);
	window.URL.revokeObjectURL(url);
	
	mostrarNotificacion('✅ Archivo Excel descargado exitosamente', 'success');
}

// Exportar a CSV (formato profesional)
function exportarACSV() {
	var data = tabla.rows({search: 'applied'}).data();
	
	// BOM UTF-8 para que Excel reconozca los caracteres especiales
	var csv = '\ufeff';
	csv += 'NOMBRE,DESCRIPCIÓN,ESTADO\n';
	
	for (var i = 0; i < data.length; i++) {
		var row = data[i];
		var nombre = $('<div>').html(row[1]).text().replace(/"/g, '""');
		var descripcion = $('<div>').html(row[2]).text().replace(/"/g, '""');
		var estado = $('<div>').html(row[3]).text().replace(/"/g, '""');
		
		csv += '"' + nombre + '","' + descripcion + '","' + estado + '"\n';
	}
	
	// Crear blob y descargar
	var blob = new Blob([csv], {
		type: 'text/csv;charset=utf-8;'
	});
	
	var url = window.URL.createObjectURL(blob);
	var a = document.createElement('a');
	a.href = url;
	a.download = 'categorias_' + obtenerFechaHora() + '.csv';
	document.body.appendChild(a);
	a.click();
	document.body.removeChild(a);
	window.URL.revokeObjectURL(url);
	
	mostrarNotificacion('✅ Archivo CSV descargado exitosamente', 'success');
}
// Exportar a PDF (descarga directa con el archivo existente)
// Exportar a PDF (descarga directa con el archivo existente)
function exportarAPDF() {
    // Usamos el modo "download" para forzar descarga
    var link = document.createElement('a');
    link.href = '../reportes/rptcategorias.php?mode=download';
    link.target = '_blank'; // opcional, pero no molesta
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    
    mostrarNotificacion('✅ Descargando reporte PDF...', 'success');
}



// Obtener fecha y hora para nombre de archivo
function obtenerFechaHora() {
	var ahora = new Date();
	var anio = ahora.getFullYear();
	var mes = ('0' + (ahora.getMonth() + 1)).slice(-2);
	var dia = ('0' + ahora.getDate()).slice(-2);
	var hora = ('0' + ahora.getHours()).slice(-2);
	var min = ('0' + ahora.getMinutes()).slice(-2);
	var seg = ('0' + ahora.getSeconds()).slice(-2);
	
	return anio + mes + dia + '_' + hora + min + seg;
}

// Mostrar notificación moderna tipo Toast
function mostrarNotificacion(mensaje, tipo) {
	// Crear el toast si no existe
	if ($('#toast-container').length === 0) {
		$('body').append(`
			<div id="toast-container" style="
				position: fixed;
				top: 20px;
				right: 20px;
				z-index: 9999;
			"></div>
		`);
	}
	
	// Colores según tipo
	var colores = {
		'success': { bg: '#10b981', icon: 'fa-check-circle' },
		'error': { bg: '#ef4444', icon: 'fa-times-circle' },
		'warning': { bg: '#f59e0b', icon: 'fa-exclamation-triangle' },
		'info': { bg: '#3b82f6', icon: 'fa-info-circle' }
	};
	
	var color = colores[tipo] || colores['info'];
	
	// Crear el toast
	var toastId = 'toast-' + Date.now();
	var toast = $(`
		<div id="${toastId}" style="
			background: ${color.bg};
			color: white;
			padding: 16px 20px;
			border-radius: 12px;
			margin-bottom: 10px;
			box-shadow: 0 10px 25px rgba(0,0,0,0.2);
			display: flex;
			align-items: center;
			gap: 12px;
			min-width: 300px;
			animation: slideIn 0.3s ease-out;
			font-size: 14px;
			font-weight: 500;
		">
			<i class="fa ${color.icon}" style="font-size: 20px;"></i>
			<span style="flex: 1;">${mensaje}</span>
			<i class="fa fa-times" style="cursor: pointer; opacity: 0.8;" onclick="$('#${toastId}').fadeOut(200, function(){ $(this).remove(); });"></i>
		</div>
	`);
	
	// Agregar animación CSS
	if ($('#toast-animation').length === 0) {
		$('head').append(`
			<style id="toast-animation">
				@keyframes slideIn {
					from {
						transform: translateX(400px);
						opacity: 0;
					}
					to {
						transform: translateX(0);
						opacity: 1;
					}
				}
				@keyframes slideOut {
					from {
						transform: translateX(0);
						opacity: 1;
					}
					to {
						transform: translateX(400px);
						opacity: 0;
					}
				}
			</style>
		`);
	}
	
	// Agregar al contenedor
	$('#toast-container').append(toast);
	
	// Auto-remover después de 3 segundos
	setTimeout(function() {
		$('#' + toastId).css('animation', 'slideOut 0.3s ease-in');
		setTimeout(function() {
			$('#' + toastId).fadeOut(200, function() {
				$(this).remove();
			});
		}, 300);
	}, 3000);
}

//Función para guardar o editar (CON ALERTAS MODERNIZADAS)
function guardaryeditar(e)
{
	e.preventDefault();
	$("#btnGuardar").prop("disabled",true);
	var formData = new FormData($("#formulario")[0]);

	$.ajax({
		url: "../ajax/categoria.php?op=guardaryeditar",
	    type: "POST",
	    data: formData,
	    contentType: false,
	    processData: false,
	    success: function(datos)
	    {
	    	if (datos == "duplicado") {
	    		// Notificación moderna en lugar de bootbox
	    		mostrarNotificacion('⚠️ Esta categoría ya existe en el sistema', 'warning');
	    		$("#btnGuardar").prop("disabled", false);
	    	} else {
		        // Notificación moderna en lugar de bootbox
		        if (datos.indexOf("registrada") > -1) {
		        	mostrarNotificacion('✅ Categoría registrada exitosamente', 'success');
		        } else if (datos.indexOf("actualizada") > -1) {
		        	mostrarNotificacion('✅ Categoría actualizada exitosamente', 'success');
		        } else {
		        	mostrarNotificacion('❌ ' + datos, 'error');
		        }
		        
		        mostrarform(false);
		        tabla.ajax.reload();
		        cargarKPIsAdicionales();
	    	}
	    },
	    error: function() {
	    	mostrarNotificacion('❌ Error al procesar la solicitud', 'error');
	    	$("#btnGuardar").prop("disabled", false);
	    }
	});
	limpiar();
}

function mostrar(idcategoria)
{
	$.post("../ajax/categoria.php?op=mostrar",{idcategoria : idcategoria}, function(data, status)
	{
		data = JSON.parse(data);		
		mostrarform(true);

		$("#idcategoria").val(data.idcategoria);
		$("#nombre").val(data.nombre);
		$("#descripcion").val(data.descripcion);
 	});
}

function desactivar(idcategoria)
{
	bootbox.confirm("¿Está seguro de desactivar la categoría?", function(result){
		if(result)
        {
        	$.post("../ajax/categoria.php?op=desactivar", {idcategoria : idcategoria}, function(e){
        		// Notificación moderna
        		if (e.indexOf("Desactivada") > -1) {
        			mostrarNotificacion('✅ ' + e, 'success');
        		} else {
        			mostrarNotificacion('❌ ' + e, 'error');
        		}
	            tabla.ajax.reload();
	            cargarKPIsAdicionales();
        	});	
        }
	})
}

function activar(idcategoria)
{
	bootbox.confirm("¿Está seguro de activar la categoría?", function(result){
		if(result)
        {
        	$.post("../ajax/categoria.php?op=activar", {idcategoria : idcategoria}, function(e){
        		// Notificación moderna
        		if (e.indexOf("activada") > -1) {
        			mostrarNotificacion('✅ ' + e, 'success');
        		} else {
        			mostrarNotificacion('❌ ' + e, 'error');
        		}
	            tabla.ajax.reload();
	            cargarKPIsAdicionales();
        	});	
        }
	})
}

init();