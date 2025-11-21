var tabla;
var ventasChart; // Gráfico principal
var chartClientes, chartVendedores, chartCategoria, productosChart; // Gráficos adicionales

function init() {
  listar();

  // Cargar clientes y luego construir la tabla
  $.post("../ajax/venta.php?op=selectCliente", function (r) {
    $("#idcliente").html('<option value="">Todos los Clientes</option>' + r);
    try { $('#idcliente').selectpicker('refresh'); } catch (e) { }
  });

  // Recargar al cambiar filtros (sin reinit)
  $('#fecha_inicio, #fecha_fin').on('change input', function () {
    if (tabla) tabla.ajax.reload(null, false);
    cargarKPIs();
    cargarGraficoPrincipal();
    cargarGraficosAdicionales();
  });
  $('#idcliente').on('changed.bs.select change', function () {
    if (tabla) tabla.ajax.reload(null, false);
    cargarKPIs();
    cargarGraficoPrincipal();
    cargarGraficosAdicionales();
  });

  // Inicializar gráficos vacíos
  initCharts();

  // Cargar datos iniciales (Por defecto Trimestre)
  aplicarFiltro('trimestre');
  // Nota: aplicarFiltro dispara 'change', lo que llama a reload, cargarKPIs, etc.

  // Eventos para detalles de KPIs
  $('.kpi-card').on('click', function () {
    let type = $(this).data('kpi-type');
    verDetalleKPI(type);
  });
}

function listar() {
  var fecha_inicio = $("#fecha_inicio").val();
  var fecha_fin = $("#fecha_fin").val();
  var idcliente = $("#idcliente").val();

  tabla = $('#tbllistado').dataTable({
    "aProcessing": true,
    "aServerSide": true,
    "dom": 'rt<"bottom"p>', // Solo tabla y paginación
    "language": {
      "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
    },
    "ajax": {
      url: '../ajax/consultas.php?op=ventasfechacliente',
      data: function (d) {
        d.fecha_inicio = $("#fecha_inicio").val();
        d.fecha_fin = $("#fecha_fin").val();
        d.idcliente = $("#idcliente").val();
      },
      type: "get",
      dataType: "json",
      error: function (e) { console.log(e.responseText); }
    },
    "bDestroy": true,
    "iDisplayLength": 5, // Default 5
    "order": [[0, "desc"]]
  }).DataTable();

  // Custom Toolbar Events
  $('#customLength').on('change', function () {
    tabla.page.len($(this).val()).draw();
  });
  $('#customSearch').on('keyup', function () {
    tabla.search($(this).val()).draw();
  });
}

/* ---------------------------------------------------
   KPIs
--------------------------------------------------- */
function cargarKPIs() {
  var fecha_inicio = $("#fecha_inicio").val();
  var fecha_fin = $("#fecha_fin").val();
  var idcliente = $("#idcliente").val();

  $.post("../ajax/consultas.php?op=ventas_kpis", {
    fecha_inicio: fecha_inicio,
    fecha_fin: fecha_fin,
    idcliente: idcliente
  }, function (data) {
    var kpi = JSON.parse(data);
    if (kpi) {
      $("#kpi_total_ventas").text('S/. ' + parseFloat(kpi.total_venta).toFixed(2));
      $("#kpi_num_transacciones").text(kpi.num_transacciones);
      $("#kpi_ticket_promedio").text('S/. ' + parseFloat(kpi.ticket_promedio).toFixed(2));
      $("#kpi_venta_maxima").text('S/. ' + parseFloat(kpi.venta_maxima).toFixed(2));
      $("#kpi_venta_minima").text('S/. ' + parseFloat(kpi.venta_minima).toFixed(2));
    }
  });
}

function verDetalleKPI(type) {
  var fecha_inicio = $("#fecha_inicio").val();
  var fecha_fin = $("#fecha_fin").val();
  var idcliente = $("#idcliente").val();

  if (type === 'total' || type === 'transacciones' || type === 'ticket') {
    // Mostrar últimas 10 ventas
    $.ajax({
      url: "../ajax/consultas.php?op=ventasfechacliente",
      data: { fecha_inicio: fecha_inicio, fecha_fin: fecha_fin, idcliente: idcliente },
      type: "GET",
      dataType: "json",
      success: function (resp) {
        var html = '<table class="table table-sm table-bordered"><thead><tr><th>Fecha</th><th>Cliente</th><th>Total</th></tr></thead><tbody>';
        // resp.aaData es el array
        var count = 0;
        if (resp.aaData && resp.aaData.length > 0) {
          // Ordenar por fecha desc si no viene ordenado (aunque el backend lo manda por fecha asc/desc segun query, aqui asumimos que queremos ver las ultimas)
          // El backend de ventasfechacliente no tiene LIMIT, trae todas.
          // Tomamos las ultimas 10.
          var ventas = resp.aaData; // Array de arrays
          // ventas[i][0] es fecha, [2] es cliente, [5] es total

          // Invertir para ver las mas recientes primero si vienen asc, o tomar las primeras si vienen desc.
          // Asumamos que queremos las 10 primeras del array (si el array viene ordenado por fecha desc en la query principal? No, la query principal no tiene ORDER BY explicito en Consultas.php, pero DataTable ordena.
          // Mejor ordenamos aqui por fecha (index 0) desc
          ventas.sort(function (a, b) {
            return new Date(b[0]) - new Date(a[0]);
          });

          for (var i = 0; i < ventas.length && i < 10; i++) {
            html += '<tr><td>' + ventas[i][0] + '</td><td>' + ventas[i][2] + '</td><td>S/. ' + ventas[i][5] + '</td></tr>';
          }
        } else {
          html += '<tr><td colspan="3">No hay registros en este periodo.</td></tr>';
        }
        html += '</tbody></table>';

        Swal.fire({
          title: 'Últimas 10 Ventas',
          html: html,
          width: '600px',
          confirmButtonText: 'Cerrar',
          confirmButtonColor: '#696cff'
        });
      }
    });
  } else if (type === 'max' || type === 'min') {
    $.post("../ajax/consultas.php?op=ventas_detalle_kpi", {
      fecha_inicio: fecha_inicio,
      fecha_fin: fecha_fin,
      idcliente: idcliente,
      tipo: type
    }, function (data) {
      var reg = JSON.parse(data);
      if (reg) {
        Swal.fire({
          title: (type === 'max' ? 'Venta Máxima' : 'Venta Mínima'),
          html: `
            <div style="text-align:left; font-size:1rem;">
              <p><strong>Fecha:</strong> ${reg.fecha_hora}</p>
              <p><strong>Cliente:</strong> ${reg.cliente}</p>
              <p><strong>Comprobante:</strong> ${reg.serie_comprobante}-${reg.num_comprobante}</p>
              <p><strong>Total:</strong> S/. ${parseFloat(reg.total_venta).toFixed(2)}</p>
            </div>
          `,
          icon: 'info',
          confirmButtonText: 'Cerrar',
          confirmButtonColor: '#696cff'
        });
      } else {
        Swal.fire('Info', 'No hay datos para este periodo', 'info');
      }
    });
  }
}

/* ---------------------------------------------------
   Gráficos
--------------------------------------------------- */
function initCharts() {
  // Main Chart
  var ctx = document.getElementById('ventasChart').getContext('2d');
  ventasChart = new Chart(ctx, {
    type: 'bar',
    data: { labels: [], datasets: [] },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: { right: 20, left: 10, top: 20, bottom: 10 } },
      plugins: {
        legend: { display: false },
        datalabels: {
          anchor: 'end', align: 'start',
          offset: 10, // Move further inside
          formatter: function (value) { return 'S/. ' + value; },
          font: { weight: 'bold' }, color: '#666',
          clip: false
        }
      },
      scales: {
        y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
        x: { grid: { display: false }, ticks: { autoSkip: true, maxRotation: 0 } }
      }
    },
    plugins: [ChartDataLabels]
  });

  // Top Clientes
  chartClientes = new Chart(document.getElementById('chartClientes'), {
    type: 'bar',
    data: { labels: [], datasets: [] },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: { right: 50, left: 10, top: 10, bottom: 10 } },
      plugins: {
        legend: { display: false },
        datalabels: {
          anchor: 'end', align: 'start', // Inside
          offset: 10, // Move further inside
          formatter: function (value) { return 'S/. ' + value; },
          font: { weight: 'bold' }, color: '#fff',
          display: function (context) { return context.dataset.data[context.dataIndex] > 0; },
          clip: false
        }
      },
      scales: {
        x: { ticks: { autoSkip: true } },
        y: { ticks: { autoSkip: false } }
      }
    },
    plugins: [ChartDataLabels]
  });

  // Top Vendedores
  chartVendedores = new Chart(document.getElementById('chartVendedores'), {
    type: 'bar',
    data: { labels: [], datasets: [] },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: { right: 50, left: 10, top: 10, bottom: 10 } },
      plugins: {
        legend: { display: false },
        datalabels: {
          anchor: 'end', align: 'start',
          offset: 10, // Move further inside
          formatter: function (value) { return 'S/. ' + value; },
          font: { weight: 'bold' }, color: '#fff',
          clip: false
        }
      },
      scales: {
        x: { ticks: { autoSkip: true } },
        y: { ticks: { autoSkip: false } }
      }
    },
    plugins: [ChartDataLabels]
  });

  // Categoría
  chartCategoria = new Chart(document.getElementById('chartCategoria'), {
    type: 'pie',
    data: { labels: [], datasets: [] },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: 20 },
      plugins: {
        legend: { position: 'right' },
        datalabels: { display: false }
      }
    }
  });

  // Top Productos (Antes Comprobante)
  productosChart = new Chart(document.getElementById('productosChart'), {
    type: 'bar',
    data: { labels: [], datasets: [] },
    options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: { right: 50, left: 10, top: 10, bottom: 10 } },
      plugins: {
        legend: { display: false },
        datalabels: {
          anchor: 'end', align: 'start',
          offset: 10, // Move further inside
          formatter: function (value) { return 'S/. ' + value; },
          font: { weight: 'bold' }, color: '#fff',
          clip: false
        }
      },
      scales: {
        x: { ticks: { autoSkip: true } },
        y: { ticks: { autoSkip: false } }
      }
    },
    plugins: [ChartDataLabels]
  });
}

function cargarGraficoPrincipal() {
  var fecha_inicio = $("#fecha_inicio").val();
  var fecha_fin = $("#fecha_fin").val();
  var idcliente = $("#idcliente").val();

  $.post("../ajax/consultas.php?op=ventas_grafico_dias", {
    fecha_inicio: fecha_inicio,
    fecha_fin: fecha_fin,
    idcliente: idcliente
  }, function (data) {
    var datos = JSON.parse(data);
    ventasChart.data.labels = datos.fechas;
    ventasChart.data.datasets = [{
      label: 'Ventas',
      data: datos.totales,
      backgroundColor: 'rgba(54, 162, 235, 0.6)',
      borderColor: 'rgba(54, 162, 235, 1)',
      borderWidth: 1,
      borderRadius: 4
    }];
    ventasChart.update();
  });
}

function cambiarGrafico(tipo) {
  if (ventasChart) {
    ventasChart.config.type = tipo;
    $('#btnChartBars').removeClass('active');
    $('#btnChartLine').removeClass('active');
    if (tipo === 'bar') $('#btnChartBars').addClass('active');
    else $('#btnChartLine').addClass('active');
    ventasChart.update();
  }
}

function cargarGraficosAdicionales() {
  var fecha_inicio = $("#fecha_inicio").val();
  var fecha_fin = $("#fecha_fin").val();
  var idcliente = $("#idcliente").val();

  // Top Clientes
  $.post("../ajax/consultas.php?op=ventas_clientes_top", {
    fecha_inicio: fecha_inicio, fecha_fin: fecha_fin
  }, function (data) {
    var datos = JSON.parse(data);
    var limit = parseInt($("#filterClientes").val());
    var labels = datos.labels.slice(0, limit);
    var values = datos.data.slice(0, limit);

    chartClientes.data.labels = labels;
    chartClientes.data.datasets = [{
      label: 'Total Comprado',
      data: values,
      backgroundColor: '#10b981'
    }];
    chartClientes.update();
  });

  // Top Vendedores
  $.post("../ajax/consultas.php?op=ventas_vendedores_top", {
    fecha_inicio: fecha_inicio, fecha_fin: fecha_fin
  }, function (data) {
    var datos = JSON.parse(data);
    var limit = parseInt($("#filterVendedores").val());
    var labels = datos.labels.slice(0, limit);
    var values = datos.data.slice(0, limit);

    chartVendedores.data.labels = labels;
    chartVendedores.data.datasets = [{
      label: 'Total Vendido',
      data: values,
      backgroundColor: '#8b5cf6'
    }];
    chartVendedores.update();
  });

  // Categoría
  $.post("../ajax/consultas.php?op=ventas_categoria", {
    fecha_inicio: fecha_inicio, fecha_fin: fecha_fin, idcliente: idcliente
  }, function (data) {
    var datos = JSON.parse(data);
    var limit = $("#filterCategoria").val();
    var labels = datos.labels;
    var values = datos.data;

    if (limit !== 'all') {
      labels = labels.slice(0, parseInt(limit));
      values = values.slice(0, parseInt(limit));
    }

    chartCategoria.data.labels = labels;
    chartCategoria.data.datasets = [{
      data: values,
      backgroundColor: ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6']
    }];
    chartCategoria.update();
  });

  // Top Productos (Antes Comprobante)
  $.post("../ajax/consultas.php?op=ventas_productos_top", {
    fecha_inicio: fecha_inicio, fecha_fin: fecha_fin, idcliente: idcliente
  }, function (data) {
    var datos = JSON.parse(data);
    var limit = parseInt($("#filterProductos").val());
    var labels = datos.labels.slice(0, limit);
    var values = datos.data.slice(0, limit);

    productosChart.data.labels = labels;
    productosChart.data.datasets = [{
      label: 'Total Vendido',
      data: values,
      backgroundColor: '#f59e0b'
    }];
    productosChart.update();
  });
}

function aplicarFiltro(periodo) {
  var hoy = new Date();
  var inicio, fin;

  function formatDate(d) {
    var month = '' + (d.getMonth() + 1), day = '' + d.getDate(), year = d.getFullYear();
    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;
    return [year, month, day].join('-');
  }

  if (periodo === 'hoy') {
    inicio = fin = formatDate(hoy);
  } else if (periodo === 'semana') {
    var first = hoy.getDate() - hoy.getDay();
    var last = first + 6;
    inicio = formatDate(new Date(hoy.setDate(first)));
    fin = formatDate(new Date(hoy.setDate(last)));
  } else if (periodo === 'mes') {
    inicio = formatDate(new Date(hoy.getFullYear(), hoy.getMonth(), 1));
    fin = formatDate(new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0));
  } else if (periodo === 'trimestre') {
    var q = Math.floor(hoy.getMonth() / 3);
    inicio = formatDate(new Date(hoy.getFullYear(), q * 3, 1));
    fin = formatDate(new Date(hoy.getFullYear(), (q + 1) * 3, 0));
  } else if (periodo === 'anio') {
    inicio = formatDate(new Date(hoy.getFullYear(), 0, 1));
    fin = formatDate(new Date(hoy.getFullYear(), 11, 31));
  }

  $("#fecha_inicio").val(inicio);
  $("#fecha_fin").val(fin);

  // Trigger change
  $("#fecha_inicio").trigger('change');
}

init();
