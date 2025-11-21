/* vistas/scripts/comprasfecha.js */
var tabla;
var comprasChart = null;
var chartCategoria = null;
var chartProductos = null;
var chartComprobante = null;
var chartUsuario = null;
var currentChartType = 'bar';

// Register the plugin to all charts:
Chart.register(ChartDataLabels);

function init() {
  // Por defecto mostrar el trimestre actual
  aplicarFiltro('trimestre');

  listar();
  cargarGraficoPrincipal();
  cargarGraficosAdicionales();
  cargarKPIs();

  // Al cambiar fechas, recarga todo
  $('#fecha_inicio, #fecha_fin').on('change', function () {
    listar();
    cargarGraficoPrincipal();
    cargarGraficosAdicionales();
    cargarKPIs();
  });

  // KPI Click Listener (Delegated)
  $(document).on('click', '.kpi-card', function () {
    var tipo = $(this).data('kpi-type');
    if (tipo) verDetalleKPI(tipo);
  });

  // Marcar menú activo
  $('#mConsultaC').addClass('treeview active');
  $('#lConsulasC').addClass('active');

  // Custom Length
  $('#customLength').on('change', function () {
    if (tabla) tabla.page.len(this.value).draw();
  });

  // Custom Search
  $('#customSearch').on('keyup change', function () {
    if (tabla) tabla.search(this.value).draw();
  });
}

function aplicarFiltro(periodo) {
  var hoy = new Date();
  var fechaInicio = new Date();
  var fechaFin = new Date();

  if (periodo === 'hoy') {
    // Ya están seteados a hoy
  } else if (periodo === 'semana') {
    var day = hoy.getDay() || 7;
    if (day !== 1) fechaInicio.setHours(-24 * (day - 1));
  } else if (periodo === 'mes') {
    fechaInicio = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
    fechaFin = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
  } else if (periodo === 'trimestre') {
    var quarter = Math.floor((hoy.getMonth() + 3) / 3);
    fechaInicio = new Date(hoy.getFullYear(), (quarter - 1) * 3, 1);
    fechaFin = new Date(hoy.getFullYear(), quarter * 3, 0);
  } else if (periodo === 'anio') {
    fechaInicio = new Date(hoy.getFullYear(), 0, 1);
    fechaFin = new Date(hoy.getFullYear(), 11, 31);
  }

  $("#fecha_inicio").val(fechaInicio.toISOString().split('T')[0]);
  $("#fecha_fin").val(fechaFin.toISOString().split('T')[0]);

  // Trigger change
  $("#fecha_inicio").trigger('change');
}

function cambiarGrafico(tipo) {
  currentChartType = tipo;
  $('.btn-group .btn').removeClass('active');
  if (tipo === 'bar') $('#btnChartBars').addClass('active');
  else if (tipo === 'line') $('#btnChartLine').addClass('active');
  cargarGraficoPrincipal();
}

function listar() {
  var fecha_inicio = $("#fecha_inicio").val();
  var fecha_fin = $("#fecha_fin").val();

  if ($.fn.DataTable.isDataTable('#tbllistado')) {
    tabla.ajax.reload();
    return;
  }

  tabla = $('#tbllistado').DataTable({
    "aProcessing": true,
    "aServerSide": true,
    dom: 'Brtip',
    buttons: [
      { extend: 'copyHtml5', text: '<i class="fa fa-copy"></i> Copy', className: 'btn btn-default' },
      { extend: 'excelHtml5', text: '<i class="fa fa-file-excel-o"></i> Excel', className: 'btn btn-success' },
      { extend: 'csvHtml5', text: '<i class="fa fa-file-text-o"></i> CSV', className: 'btn btn-info' },
      { extend: 'pdfHtml5', text: '<i class="fa fa-file-pdf-o"></i> PDF', className: 'btn btn-danger' }
    ],
    "ajax": {
      url: '../ajax/consultas.php?op=comprasfecha',
      data: function (d) {
        d.fecha_inicio = $("#fecha_inicio").val();
        d.fecha_fin = $("#fecha_fin").val();
      },
      type: "get",
      dataType: "json",
      error: function (e) { console.log(e.responseText); }
    },
    "language": {
      "lengthMenu": "Mostrar : _MENU_ registros",
      "buttons": {
        "copyTitle": "Tabla Copiada",
        "copySuccess": { _: '%d líneas copiadas', 1: '1 línea copiada' }
      }
    },
    "bDestroy": true,
    "iDisplayLength": 5,
    "order": [[0, "desc"]],
    initComplete: function () {
      var $holder = $('.dt-buttons-holder');
      if ($holder.length && !$holder.find('.dt-buttons').length) {
        $holder.append(tabla.buttons().container());
      }
    }
  });
}

function cargarKPIs() {
  var fecha_inicio = $("#fecha_inicio").val();
  var fecha_fin = $("#fecha_fin").val();

  $.ajax({
    url: "../ajax/consultas.php?op=compras_kpis",
    type: "GET",
    data: { fecha_inicio: fecha_inicio, fecha_fin: fecha_fin },
    dataType: "json",
    success: function (data) {
      if (data) {
        $("#kpi_total_compras").text("S/. " + parseFloat(data.total_compra).toFixed(2));
        $("#kpi_num_transacciones").text(data.num_transacciones);
        $("#kpi_ticket_promedio").text("S/. " + parseFloat(data.ticket_promedio).toFixed(2));
        $("#kpi_compra_maxima").text("S/. " + parseFloat(data.compra_maxima).toFixed(2));
        $("#kpi_compra_minima").text("S/. " + parseFloat(data.compra_minima).toFixed(2));
      }
    }
  });
}

// Configuración común para datalabels
const datalabelsConfig = {
  color: '#fff',
  font: { weight: 'bold', size: 11 },
  formatter: function (value, context) {
    return value > 0 ? value : ''; // Solo mostrar si es mayor a 0
  }
};

function cargarGraficoPrincipal() {
  var fecha_inicio = $("#fecha_inicio").val();
  var fecha_fin = $("#fecha_fin").val();

  if (comprasChart) comprasChart.destroy();

  $.ajax({
    url: "../ajax/consultas.php?op=comprasfecha_grafico",
    type: "GET",
    data: { fecha_inicio: fecha_inicio, fecha_fin: fecha_fin },
    dataType: "json",
    success: function (data) {
      var ctx = document.getElementById('comprasChart').getContext('2d');
      comprasChart = new Chart(ctx, {
        type: currentChartType,
        data: {
          labels: data.fechas,
          datasets: [{
            label: 'Total Compras (S/.)',
            data: data.totales,
            backgroundColor: 'rgba(21, 101, 192, 0.6)',
            borderColor: 'rgba(21, 101, 192, 1)',
            borderWidth: 2,
            borderRadius: 4,
            tension: 0.3,
            fill: (currentChartType === 'line')
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          layout: { padding: 10 },
          plugins: {
            legend: { display: false },
            datalabels: {
              anchor: 'end', align: 'top', color: '#333',
              formatter: function (value) { return 'S/. ' + parseFloat(value).toFixed(2); }
            },
            tooltip: {
              callbacks: { label: function (context) { return 'S/. ' + context.parsed.y.toFixed(2); } }
            }
          },
          scales: {
            y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
            x: { grid: { display: false } }
          }
        }
      });
    }
  });
}

function cargarGraficosAdicionales() {
  var fecha_inicio = $("#fecha_inicio").val();
  var fecha_fin = $("#fecha_fin").val();

  // Obtener valores de filtros
  var limitCategoria = $("#filterCategoria").val() || '5';
  var limitProductos = $("#filterProductos").val() || '5';
  var tipoComprobante = $("#filterComprobante").val() || 'monto';
  var limitUsuario = $("#filterUsuario").val() || '5';

  var commonData = { fecha_inicio: fecha_inicio, fecha_fin: fecha_fin };

  // 1. Categoría (Pie)
  $.ajax({
    url: "../ajax/consultas.php?op=compras_categoria",
    type: "GET",
    data: { ...commonData, limit: limitCategoria },
    dataType: "json",
    success: function (data) {
      if (chartCategoria) chartCategoria.destroy();

      var labels = data.labels;
      var values = data.data;

      if (limitCategoria !== 'all') {
        var limit = parseInt(limitCategoria);
        if (labels.length > limit) {
          labels = labels.slice(0, limit);
          values = values.slice(0, limit);
        }
      }

      var ctx = document.getElementById('chartCategoria').getContext('2d');
      chartCategoria = new Chart(ctx, {
        type: 'pie',
        data: {
          labels: labels,
          datasets: [{
            data: values,
            backgroundColor: ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#6366f1', '#ec4899', '#14b8a6'],
            hoverOffset: 4
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          layout: { padding: 10 },
          plugins: {
            legend: { position: 'right', labels: { boxWidth: 12, font: { size: 10 } } },
            datalabels: { color: '#fff', font: { weight: 'bold' } }
          }
        }
      });
    }
  });

  // 2. Top Productos (Horizontal Bar)
  $.ajax({
    url: "../ajax/consultas.php?op=compras_productos_top",
    type: "GET",
    data: { ...commonData, limit: limitProductos },
    dataType: "json",
    success: function (data) {
      if (chartProductos) chartProductos.destroy();

      var limit = parseInt(limitProductos);
      var labels = data.labels;
      var values = data.data;

      if (labels.length > limit) {
        labels = labels.slice(0, limit);
        values = values.slice(0, limit);
      }

      var ctx = document.getElementById('chartProductos').getContext('2d');
      chartProductos = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Total (S/.)',
            data: values,
            backgroundColor: '#10b981',
            borderRadius: 4
          }]
        },
        options: {
          indexAxis: 'y',
          responsive: true, maintainAspectRatio: false,
          layout: { padding: 10 },
          plugins: {
            legend: { display: false },
            datalabels: { anchor: 'end', align: 'right', color: '#333' }
          }
        }
      });
    }
  });

  // 3. Tipo Comprobante (Doughnut)
  $.ajax({
    url: "../ajax/consultas.php?op=compras_comprobante",
    type: "GET",
    data: { ...commonData, tipo: tipoComprobante },
    dataType: "json",
    success: function (data) {
      if (chartComprobante) chartComprobante.destroy();

      var ctx = document.getElementById('chartComprobante').getContext('2d');
      chartComprobante = new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: data.labels,
          datasets: [{
            data: data.data,
            backgroundColor: ['#f59e0b', '#6366f1', '#ec4899'],
            hoverOffset: 4
          }]
        },
        options: {
          responsive: true, maintainAspectRatio: false,
          layout: { padding: 10 },
          plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } },
            datalabels: { color: '#fff', font: { weight: 'bold' } }
          }
        }
      });
    }
  });

  // 4. Usuarios (Horizontal Bar - Changed from Bar)
  $.ajax({
    url: "../ajax/consultas.php?op=compras_usuario",
    type: "GET",
    data: { ...commonData, limit: limitUsuario },
    dataType: "json",
    success: function (data) {
      if (chartUsuario) chartUsuario.destroy();

      var limit = parseInt(limitUsuario);
      var labels = data.labels;
      var values = data.data;

      if (labels.length > limit) {
        labels = labels.slice(0, limit);
        values = values.slice(0, limit);
      }

      var ctx = document.getElementById('chartUsuario').getContext('2d');
      chartUsuario = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [{
            label: 'Compras (S/.)',
            data: values,
            backgroundColor: '#8b5cf6',
            borderRadius: 4
          }]
        },
        options: {
          indexAxis: 'y', // Changed to horizontal
          responsive: true, maintainAspectRatio: false,
          layout: { padding: 10 },
          plugins: {
            legend: { display: false },
            datalabels: { anchor: 'end', align: 'right', color: '#333' }
          },
          scales: { x: { beginAtZero: true } } // Changed y to x for horizontal
        }
      });
    }
  });
}

function verDetalleKPI(tipo) {
  console.log('Click en KPI:', tipo);
  var fecha_inicio = $("#fecha_inicio").val();
  var fecha_fin = $("#fecha_fin").val();

  // Si es Ticket Promedio, mostrar info simple
  if (tipo === 'ticket') {
    Swal.fire({
      title: 'Información',
      text: 'Esta métrica se calcula dividiendo el Total de Compras entre el número de Transacciones.',
      icon: 'info'
    });
    return;
  }

  Swal.fire({ title: 'Cargando...', didOpen: () => { Swal.showLoading() } });

  // Si es Total o Transacciones, mostrar lista de últimas 10 compras
  if (tipo === 'total' || tipo === 'transacciones') {
    $.ajax({
      url: "../ajax/consultas.php?op=comprasfecha", // Reusamos el endpoint de lista
      type: "GET",
      data: { fecha_inicio: fecha_inicio, fecha_fin: fecha_fin },
      dataType: "json",
      success: function (data) {
        Swal.close();
        // data.aaData contiene los registros. Mostramos los últimos 10.
        var registros = data.aaData || [];
        var html = '<div style="text-align:left; max-height:300px; overflow-y:auto;"><ul class="list-group">';

        // Tomar máximo 10
        var limit = Math.min(registros.length, 10);
        if (limit === 0) {
          html += '<li class="list-group-item">No hay registros en este periodo.</li>';
        } else {
          for (var i = 0; i < limit; i++) {
            var reg = registros[i];
            // reg: [fecha, usuario, proveedor, tipo, numero, total, impuesto, estado]
            html += `
                  <li class="list-group-item" style="font-size:0.9em;">
                    <strong>${reg[2]}</strong> - ${reg[0]}<br>
                    <small>${reg[3]} ${reg[4]}</small>
                    <span class="badge pull-right" style="background:#1565c0;">S/. ${reg[5]}</span>
                  </li>
                `;
          }
        }
        html += '</ul></div>';

        Swal.fire({
          title: 'Últimas 10 Transacciones',
          html: html,
          width: 600,
          confirmButtonText: 'Cerrar'
        });
      },
      error: function () {
        Swal.close();
        Swal.fire('Error', 'No se pudo cargar la lista.', 'error');
      }
    });
    return;
  }

  // Para Max y Min
  $.ajax({
    url: "../ajax/consultas.php?op=compras_detalle_kpi",
    type: "GET",
    data: { fecha_inicio: fecha_inicio, fecha_fin: fecha_fin, tipo: tipo },
    dataType: "json",
    success: function (data) {
      Swal.close();
      if (data) {
        var titulo = (tipo === 'max') ? 'Compra Máxima' : 'Compra Mínima';
        var html = `
                    <div style="text-align:left; padding:10px;">
                        <p><strong>Proveedor:</strong> ${data.proveedor}</p>
                        <p><strong>Fecha:</strong> ${data.fecha_hora}</p>
                        <p><strong>Comprobante:</strong> ${data.serie_comprobante} - ${data.num_comprobante}</p>
                        <p><strong>Total:</strong> <span style="font-size:1.2em; font-weight:bold;">S/. ${parseFloat(data.total_compra).toFixed(2)}</span></p>
                    </div>
                `;
        Swal.fire({
          title: titulo,
          html: html,
          icon: 'info',
          confirmButtonText: 'Cerrar'
        });
      } else {
        Swal.fire('Información', 'No se encontraron datos para este periodo.', 'info');
      }
    },
    error: function (e) {
      Swal.close();
      Swal.fire('Error', 'No se pudo cargar el detalle.', 'error');
    }
  });
}

// Expose function to global scope
window.verDetalleKPI = verDetalleKPI;

init();
