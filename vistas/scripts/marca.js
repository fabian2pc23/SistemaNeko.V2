var tabla;
var filtroActual = 'all'; // Variable global para mantener el filtro activo

//Función que se ejecuta al inicio
function init() {
	mostrarform(false);
	listar();

	$("#formulario").on("submit", function (e) {
		guardaryeditar(e);
	});

	$('#mAlmacen').addClass("treeview active");
	$('#lMarcas').addClass("active");

	// Eventos KPI interactivos
	$('#card-sin-articulos').on('click', function () {
		mostrarDetalleKPI('sin_articulos', 'Marcas sin artículos');
	});

	$('#card-stock-critico').on('click', function () {
		mostrarDetalleKPI('stock_critico', 'Marcas con stock crítico');
	});

	$('#card-nuevas').on('click', function () {
		mostrarDetalleKPI('nuevas', 'Marcas nuevas (últimos registros)');
	});
}

//Función limpiar
function limpiar() {
	$("#idmarca").val("");
	$("#nombre").val("");
	$("#descripcion").val("");
}

//Función mostrar formulario
function mostrarform(flag) {
	limpiar();
	if (flag) {
		$("#listadoregistros").hide();
		$("#formularioregistros").show();
		$("#btnGuardar").prop("disabled", false);
		$("#btnagregar").hide();
	}
	else {
		$("#listadoregistros").show();
		$("#formularioregistros").hide();
		$("#btnagregar").show();
	}
}

//Función cancelarform
function cancelarform() {
	limpiar();
	mostrarform(false);
}

//Función Listar optimizada
function listar() {
	// Registrar el filtro permanente de DataTables
	$.fn.dataTable.ext.search.push(
		function (settings, data, dataIndex) {
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
		"lengthMenu": [5, 10, 25, 75, 100],
		"processing": false,
		"serverSide": false,
		"dom": 'rtip', // r=processing, t=table, i=info, p=pagination (sin l=length ni f=filter)
		"ajax": {
			"url": '../ajax/marca.php?op=listar',
			"type": "GET",
			"dataType": "json",
			"dataSrc": function (json) {
				if (json && json.aaData) {
					return json.aaData;
				}
				return [];
			}
		},
		"columns": [
			{ "data": "0" },
			{
				"data": "1",
				"render": function (data, type, row) {
					// Title Case: Primera letra mayúscula de cada palabra
					if (data) {
						return data.toLowerCase().split(' ').map(function (word) {
							return word.charAt(0).toUpperCase() + word.slice(1);
						}).join(' ');
					}
					return data;
				}
			},
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
		"order": [[1, "asc"]],
		"drawCallback": function () {
			actualizarKPIs();
		}
	});

	// Selector de cantidad de registros personalizado
	$('#page-length-selector').off('change').on('change', function () {
		var length = parseInt($(this).val());
		tabla.page.len(length).draw();
	});

	// Búsqueda personalizada
	$('#searchInput').off('keyup').on('keyup', function () {
		var searchValue = this.value;
		console.log('🔍 Buscando:', searchValue);
		tabla.search(searchValue).draw();
	});

	// Cargar KPIs iniciales
	setTimeout(function () {
		actualizarKPIs();
		cargarKPIsAdicionales();
	}, 500);
}

// ==================== ACTUALIZAR KPIs ====================
function actualizarKPIs() {
	if (!tabla) return;

	var data = tabla.rows({ search: 'applied' }).data();
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
	// KPI: Marcas sin artículos
	$.ajax({
		url: '../ajax/marca.php?op=marcas_sin_articulos',
		type: 'GET',
		dataType: 'json',
		success: function (data) {
			if (data && data.success) {
				$('#kpi-sin-articulos').text(data.total || 0);
			} else {
				$('#kpi-sin-articulos').text('0');
			}
		}
	});

	// KPI: Marcas con stock crítico
	$.ajax({
		url: '../ajax/marca.php?op=marcas_stock_critico',
		type: 'GET',
		dataType: 'json',
		success: function (data) {
			if (data && data.success) {
				$('#kpi-stock-critico').text(data.total || 0);
			} else {
				$('#kpi-stock-critico').text('0');
			}
		}
	});

	// KPI: Marcas nuevas
	$.ajax({
		url: '../ajax/marca.php?op=marcas_nuevas',
		type: 'GET',
		dataType: 'json',
		success: function (data) {
			if (data && data.success) {
				$('#kpi-nuevas').text(data.total || 0);
			} else {
				$('#kpi-nuevas').text('0');
			}
		}
	});
}

// ==================== MOSTRAR DETALLE KPI (INTERACTIVO) ====================
function mostrarDetalleKPI(tipo, titulo) {
	Swal.fire({
		title: 'Cargando...',
		didOpen: () => { Swal.showLoading() }
	});

	$.ajax({
		url: '../ajax/marca.php?op=marcas_' + tipo,
		type: 'GET',
		dataType: 'json',
		success: function (data) {
			Swal.close();
			if (data && data.success && data.marcas && data.marcas.length > 0) {
				let lista = '<ul style="text-align:left; max-height:300px; overflow-y:auto; padding-left:20px;">';
				data.marcas.forEach(item => {
					lista += `<li>${item}</li>`;
				});
				lista += '</ul>';

				Swal.fire({
					title: titulo,
					html: lista,
					icon: 'info',
					confirmButtonText: 'Cerrar'
				});
			} else {
				Swal.fire('Información', 'No hay registros para mostrar en esta categoría.', 'info');
			}
		},
		error: function () {
			Swal.close();
			mostrarNotificacion('Error al cargar detalles', 'error');
		}
	});
}

// ==================== NOTIFICACIONES MODERNAS (TOAST) ====================
function mostrarNotificacion(mensaje, tipo) {
	const Toast = Swal.mixin({
		toast: true,
		position: 'top-end',
		showConfirmButton: false,
		timer: 3000,
		timerProgressBar: true,
		didOpen: (toast) => {
			toast.addEventListener('mouseenter', Swal.stopTimer)
			toast.addEventListener('mouseleave', Swal.resumeTimer)
		}
	})

	Toast.fire({
		icon: tipo,
		title: mensaje
	})
}

// ==================== FILTROS DE TABLA ====================
function filtrarTabla(filtro) {
	filtroActual = filtro;
	$('.filter-btn').removeClass('active');
	$('.filter-btn[data-filter="' + filtro + '"]').addClass('active');
	tabla.draw();
	setTimeout(function () {
		actualizarKPIs();
	}, 200);
}

// ==================== EXPORTAR TABLA ====================
function exportarTabla(tipo) {
	switch (tipo) {
		case 'copy': copiarTablaAlPortapapeles(); break;
		case 'excel': exportarAExcel(); break;
		case 'csv': exportarACSV(); break;
		case 'pdf': exportarAPDF(); break;
	}
}

function copiarTablaAlPortapapeles() {
	var data = tabla.rows({ search: 'applied' }).data();
	var texto = "NOMBRE\tDESCRIPCIÓN\tESTADO\n";
	for (var i = 0; i < data.length; i++) {
		var row = data[i];
		var nombre = $('<div>').html(row[1]).text();
		var descripcion = $('<div>').html(row[2]).text();
		var estado = $('<div>').html(row[3]).text();
		texto += nombre + "\t" + descripcion + "\t" + estado + "\n";
	}
	if (navigator.clipboard && window.isSecureContext) {
		navigator.clipboard.writeText(texto).then(function () {
			mostrarNotificacion('Tabla copiada al portapapeles', 'success');
		});
	} else {
		mostrarNotificacion('No se pudo copiar automáticamente', 'error');
	}
}

function exportarAExcel() {
	var data = tabla.rows({ search: 'applied' }).data();
	var html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40"><head><meta charset="UTF-8"></head><body>';
	html += '<table border="1"><thead><tr style="background-color:#1565c0;color:white;"><th>NOMBRE</th><th>DESCRIPCIÓN</th><th>ESTADO</th></tr></thead><tbody>';
	for (var i = 0; i < data.length; i++) {
		var row = data[i];
		html += '<tr><td>' + $('<div>').html(row[1]).text() + '</td><td>' + $('<div>').html(row[2]).text() + '</td><td>' + $('<div>').html(row[3]).text() + '</td></tr>';
	}
	html += '</tbody></table></body></html>';
	var blob = new Blob(['\ufeff', html], { type: 'application/vnd.ms-excel' });
	var url = window.URL.createObjectURL(blob);
	var a = document.createElement('a');
	a.href = url;
	a.download = 'marcas_' + obtenerFechaHora() + '.xls';
	document.body.appendChild(a);
	a.click();
	document.body.removeChild(a);
	mostrarNotificacion('Archivo Excel descargado', 'success');
}

function exportarACSV() {
	var data = tabla.rows({ search: 'applied' }).data();
	var csv = '\ufeffNOMBRE,DESCRIPCIÓN,ESTADO\n';
	for (var i = 0; i < data.length; i++) {
		var row = data[i];
		csv += '"' + $('<div>').html(row[1]).text().replace(/"/g, '""') + '","' + $('<div>').html(row[2]).text().replace(/"/g, '""') + '","' + $('<div>').html(row[3]).text().replace(/"/g, '""') + '"\n';
	}
	var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
	var url = window.URL.createObjectURL(blob);
	var a = document.createElement('a');
	a.href = url;
	a.download = 'marcas_' + obtenerFechaHora() + '.csv';
	document.body.appendChild(a);
	a.click();
	document.body.removeChild(a);
	mostrarNotificacion('Archivo CSV descargado', 'success');
}

function exportarAPDF() {
	var link = document.createElement('a');
	link.href = '../reportes/rptmarcas.php?mode=download';
	link.target = '_blank';
	document.body.appendChild(link);
	link.click();
	document.body.removeChild(link);
	mostrarNotificacion('Descargando reporte PDF...', 'success');
}

function obtenerFechaHora() {
	var ahora = new Date();
	return ahora.toISOString().slice(0, 19).replace(/[-T:]/g, "");
}

//Función para guardar o editar
function guardaryeditar(e) {
	e.preventDefault();
	$("#btnGuardar").prop("disabled", true);
	var formData = new FormData($("#formulario")[0]);

	$.ajax({
		url: "../ajax/marca.php?op=guardaryeditar",
		type: "POST",
		data: formData,
		contentType: false,
		processData: false,
		success: function (datos) {
			if (datos == "duplicado") {
				mostrarNotificacion('⚠️ Esta marca ya existe en el sistema', 'warning');
				$("#btnGuardar").prop("disabled", false);
			} else {
				if (datos.indexOf("registrada") > -1) {
					mostrarNotificacion('Marca registrada exitosamente', 'success');
				} else if (datos.indexOf("actualizada") > -1) {
					mostrarNotificacion('Marca actualizada exitosamente', 'success');
				} else {
					mostrarNotificacion('❌ ' + datos, 'error');
				}

				mostrarform(false);
				tabla.ajax.reload();
				cargarKPIsAdicionales();
			}
		},
		error: function () {
			mostrarNotificacion('Error al procesar la solicitud', 'error');
			$("#btnGuardar").prop("disabled", false);
		}
	});
	limpiar();
}

function mostrar(idmarca) {
	$.post("../ajax/marca.php?op=mostrar", { idmarca: idmarca }, function (data, status) {
		data = JSON.parse(data);
		mostrarform(true);

		$("#idmarca").val(data.idmarca);
		$("#nombre").val(data.nombre);
		$("#descripcion").val(data.descripcion);
	});
}

function desactivar(idmarca) {
	Swal.fire({
		title: '¿Está seguro?',
		text: "¿Desea desactivar la marca?",
		icon: 'warning',
		showCancelButton: true,
		confirmButtonColor: '#d33',
		cancelButtonColor: '#3085d6',
		confirmButtonText: 'Sí, desactivar',
		cancelButtonText: 'Cancelar'
	}).then((result) => {
		if (result.isConfirmed) {
			$.post("../ajax/marca.php?op=desactivar", { idmarca: idmarca }, function (e) {
				if (e.indexOf("Desactivada") > -1) {
					mostrarNotificacion(' ' + e, 'success');
				} else {
					mostrarNotificacion(' ' + e, 'error');
				}
				tabla.ajax.reload();
				cargarKPIsAdicionales();
			});
		}
	});
}

function activar(idmarca) {
	Swal.fire({
		title: '¿Está seguro?',
		text: "¿Desea activar la marca?",
		icon: 'question',
		showCancelButton: true,
		confirmButtonColor: '#00a65a',
		cancelButtonColor: '#d33',
		confirmButtonText: 'Sí, activar',
		cancelButtonText: 'Cancelar'
	}).then((result) => {
		if (result.isConfirmed) {
			$.post("../ajax/marca.php?op=activar", { idmarca: idmarca }, function (e) {
				if (e.indexOf("activada") > -1) {
					mostrarNotificacion(' ' + e, 'success');
				} else {
					mostrarNotificacion(' ' + e, 'error');
				}
				tabla.ajax.reload();
				cargarKPIsAdicionales();
			});
		}
	});
}

init();