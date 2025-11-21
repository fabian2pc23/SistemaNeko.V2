var tblVigentes, tblMovimientos, priceChart;

// ==================== INIT ====================
function init() {
  cargarArticulosEnSelect($('#filtro_articulo'));
  initDataTables();
  initEventHandlers();

  // Marcar menú activo
  $("#mAlmacen").addClass("treeview active");
  $('aside .sidebar-menu li > a[href="historial_precios.php"]').parent().addClass('active');
}

// ==================== DATATABLES ====================
function initDataTables() {
  // Tabla de precios vigentes
  tblVigentes = $('#tbl_vigentes').DataTable({
    aProcessing: true,
    aServerSide: true,
    dom: 'Brtip',
    buttons: [
      { extend: 'copyHtml5', className: 'buttons-copy' },
      { extend: 'excelHtml5', className: 'buttons-excel' },
      { extend: 'csvHtml5', className: 'buttons-csv' },
      { extend: 'pdf', className: 'buttons-pdf' }
    ],
    ajax: {
      url: '../ajax/historial_precios.php?op=listar_vigentes',
      type: 'get',
      data: function (d) {
        d.idarticulo = $('#filtro_articulo').val() || 0;
      },
      dataType: 'json',
      error: function (e) { console.error('Error vigentes:', e.responseText); }
    },
    iDisplayLength: 10,
    order: [[1, 'asc']],
    columns: [
      { "data": 0 },
      { "data": 1 },
      { "data": 2 },
      { "data": 3 },
      { "data": 4 }
    ],
    language: {
      "emptyTable": "No hay datos disponibles",
      "processing": "Cargando..."
    }
  });

  // Tabla de movimientos
  tblMovimientos = $('#tbl_mov').DataTable({
    aProcessing: true,
    aServerSide: true,
    dom: 'Brtip',
    buttons: [
      { extend: 'copyHtml5', className: 'buttons-copy' },
      { extend: 'excelHtml5', className: 'buttons-excel' },
      { extend: 'csvHtml5', className: 'buttons-csv' },
      { extend: 'pdf', className: 'buttons-pdf' }
    ],
    ajax: {
      url: '../ajax/historial_precios.php?op=listar_movimientos',
      type: 'get',
      data: function (d) {
        d.idarticulo = $('#filtro_articulo').val() || 0;
      },
      dataType: 'json',
      error: function (e) { console.error('Error movimientos:', e.responseText); }
    },
    iDisplayLength: 10,
    order: [[8, 'desc']],
    columns: [
      { "data": 0 },
      { "data": 1 },
      { "data": 2 },
      { "data": 3 },
      { "data": 4 },
      { "data": 5 },
      { "data": 6 },
      { "data": 7 },
      { "data": 8 }
    ],
    language: {
      "emptyTable": "No hay movimientos registrados",
      "processing": "Cargando..."
    }
  });
}

// ==================== CHART ====================
function initChart() {
  var ctx = document.getElementById('priceChart').getContext('2d');
  priceChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: [],
      datasets: []
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'top'
        },
        title: {
          display: true,
          text: 'Evolución de Precios',
          font: { size: 16, weight: 'bold' }
        }
      },
      scales: {
        y: {
          beginAtZero: false,
          ticks: {
            callback: function (value) {
              return 'S/. ' + value.toFixed(2);
            }
          }
        }
      }
    }
  });
}

function cargarChart(idarticulo) {
  if (!idarticulo || idarticulo == 0) {
    if (priceChart) {
      priceChart.data.labels = [];
      priceChart.data.datasets = [];
      priceChart.update();
    }
    $('#chart-container').hide();
    return;
  }

  $.getJSON('../ajax/historial_precios.php?op=listar_chart&idarticulo=' + idarticulo)
    .done(function (resp) {
      if (!priceChart) initChart();

      if (resp.labels && resp.labels.length > 0) {
        priceChart.data.labels = resp.labels;
        priceChart.data.datasets = resp.datasets;
        priceChart.update();
        $('#chart-container').show();
      } else {
        priceChart.data.labels = [];
        priceChart.data.datasets = [];
        priceChart.update();
        $('#chart-container').hide();
      }
    })
    .fail(function () {
      console.error('Error al cargar datos del gráfico');
      $('#chart-container').hide();
    });
}

// ==================== HELPERS ====================
function cargarArticulosEnSelect($sel) {
  $.post("../ajax/articulo.php?op=selectActivos", function (r) {
    $sel.html(r);
    try { $sel.selectpicker('refresh'); } catch (_) { }
  });
}

// ==================== EVENT HANDLERS ====================
function initEventHandlers() {
  // Filtro de artículo
  $('#filtro_articulo').on('changed.bs.select', function () {
    var idart = $(this).val() || 0;
    tblVigentes.ajax.reload(null, false);
    tblMovimientos.ajax.reload(null, false);
    cargarChart(idart);
  });

  // Botón recargar
  $('#btnRecargar').on('click', function () {
    tblVigentes.ajax.reload(null, false);
    tblMovimientos.ajax.reload(null, false);
    var idart = $('#filtro_articulo').val() || 0;
    if (idart > 0) cargarChart(idart);
  });

  // Búsqueda en tabla vigentes
  $('#search-vigentes').on('keyup', function () {
    tblVigentes.search(this.value).draw();
  });

  // Cambiar longitud de tabla vigentes
  $('#entries-vigentes').on('change', function () {
    tblVigentes.page.len($(this).val()).draw();
  });

  // Búsqueda en tabla movimientos
  $('#search-movimientos').on('keyup', function () {
    tblMovimientos.search(this.value).draw();
  });

  // Cambiar longitud de tabla movimientos
  $('#entries-movimientos').on('change', function () {
    tblMovimientos.page.len($(this).val()).draw();
  });

  // Modal de actualización
  $('#btnAbrirModal').on('click', function () {
    cargarArticulosEnSelect($('#sel_articulo_mdl'));
    $('#precio_actual').val('');
    $('#precio_nuevo').val('');
    $('#motivo').val('');
    $('#idarticulo_mdl').val('');
    $('#mdlPrecio').modal('show');
  });

  // Al elegir artículo en el modal
  $('#sel_articulo_mdl').on('changed.bs.select', function () {
    var idart = $(this).val();
    if (!idart) {
      $('#precio_actual').val('');
      $('#idarticulo_mdl').val('');
      return;
    }

    $.getJSON('../ajax/historial_precios.php?op=ultimo&idarticulo=' + encodeURIComponent(idart))
      .done(function (resp) {
        if (resp && resp.success) {
          var pv = (resp.precio_venta ?? 0);
          $('#precio_actual').val((pv.toFixed ? pv.toFixed(2) : pv));
          $('#idarticulo_mdl').val(idart);
        } else {
          $('#precio_actual').val('');
          $('#idarticulo_mdl').val('');
        }
      })
      .fail(function () {
        $('#precio_actual').val('');
        $('#idarticulo_mdl').val('');
      });
  });

  // Envío del formulario
  $('#frmPrecio').on('submit', function (e) {
    e.preventDefault();
    var formData = new FormData(this);

    $.ajax({
      url: '../ajax/historial_precios.php?op=actualizar_precio',
      type: 'POST',
      data: formData,
      contentType: false,
      processData: false
    })
      .done(function (r) {
        try {
          var j = JSON.parse(r);
          if (j.success) {
            bootbox.alert(j.message || 'Precio actualizado', function () {
              $('#mdlPrecio').modal('hide');
              tblVigentes.ajax.reload(null, false);
              tblMovimientos.ajax.reload(null, false);
              var idart = $('#filtro_articulo').val() || 0;
              if (idart > 0) cargarChart(idart);
            });
          } else {
            bootbox.alert(j.message || 'No se pudo actualizar el precio');
          }
        } catch (_) {
          console.error('Respuesta inesperada:', r);
          bootbox.alert('Respuesta inesperada del servidor');
        }
      })
      .fail(function () {
        bootbox.alert('Error de comunicación');
      });
  });
}

// ==================== EXPORT FUNCTIONS ====================
function exportarTabla(type, tabla) {
  var dt = tabla === 'vigentes' ? tblVigentes : tblMovimientos;
  if (type === 'copy') dt.button('.buttons-copy').trigger();
  if (type === 'excel') dt.button('.buttons-excel').trigger();
  if (type === 'csv') dt.button('.buttons-csv').trigger();
  if (type === 'pdf') dt.button('.buttons-pdf').trigger();
}

init();
