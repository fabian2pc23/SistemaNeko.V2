// vistas/scripts/articulo.js - FILTRO CORREGIDO
var tabla;
var estadoFiltro = 'all';

/* ======================= Toast ======================= */
function mostrarNotificacion(mensaje, tipo) {
	if ($('#toast-container').length === 0) {
		$('body').append('<div id="toast-container" style="position: fixed;top: 20px;right: 20px;z-index: 9999;"></div>');
	}
	
	var colores = {
		'success': { bg: '#10b981', icon: 'fa-check-circle' },
		'error': { bg: '#ef4444', icon: 'fa-times-circle' },
		'warning': { bg: '#f59e0b', icon: 'fa-exclamation-triangle' },
		'info': { bg: '#3b82f6', icon: 'fa-info-circle' }
	};
	
	var color = colores[tipo] || colores['info'];
	var toastId = 'toast-' + Date.now();
	var toast = $('<div id="'+toastId+'" style="background:'+color.bg+';color:white;padding:16px 20px;border-radius:12px;margin-bottom:10px;box-shadow:0 10px 25px rgba(0,0,0,0.2);display:flex;align-items:center;gap:12px;min-width:300px;animation:slideIn 0.3s ease-out;font-size:14px;font-weight:500;"><i class="fa '+color.icon+'" style="font-size:20px;"></i><span style="flex:1;">'+mensaje+'</span><i class="fa fa-times" style="cursor:pointer;opacity:0.8;" onclick="$(\'#'+toastId+'\').fadeOut(200,function(){$(this).remove();});"></i></div>');
	
	if ($('#toast-animation').length === 0) {
		$('head').append('<style id="toast-animation">@keyframes slideIn{from{transform:translateX(400px);opacity:0}to{transform:translateX(0);opacity:1}}@keyframes slideOut{from{transform:translateX(0);opacity:1}to{transform:translateX(400px);opacity:0}}</style>');
	}
	
	$('#toast-container').append(toast);
	
	setTimeout(function() {
		$('#' + toastId).css('animation', 'slideOut 0.3s ease-in');
		setTimeout(function() { $('#' + toastId).fadeOut(200, function() { $(this).remove(); }); }, 300);
	}, 3000);
}

function showAlert(tipo, mensaje) { mostrarNotificacion(mensaje, tipo); }

/* ======================= Validación ======================= */
function esNombreValido(nombre) {
  const txt = (nombre || "").trim();
  if (!/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]{3,50}$/.test(txt)) return false;
  if (/([A-Za-z])\1{2,}/.test(txt)) return false;
  if (!/[AEIOUaeiouÁÉÍÓÚáéíóúüÜ]/.test(txt)) return false;
  const invalidos = ["xxx", "asdf", "test", "prueba", "rol", "role"];
  if (invalidos.some(p => txt.toLowerCase().includes(p))) return false;
  return true;
}

function esPrecioValido(v) { return /^\d{1,7}(\.\d{1,2})?$/.test((v || "").trim()); }
function esCodigoValido(v) { return /^\d{8,13}$/.test((v || "").trim()); }

function setValidity(el, ok, msg) {
  if (!el) return;
  el.setCustomValidity(ok ? "" : msg);
  if (!ok) el.reportValidity();
}

/* ======================= EAN ======================= */
function ean13Checksum(d12) {
  const s = String(d12).replace(/\D/g, "").padStart(12, "0").slice(0, 12);
  let sumOdd = 0, sumEven = 0;
  for (let i = 0; i < 12; i++) {
    const n = s.charCodeAt(i) - 48;
    if ((i + 1) % 2 === 0) sumEven += n; else sumOdd += n;
  }
  const total = sumOdd + sumEven * 3;
  return (10 - (total % 10)) % 10;
}

function generarEAN13() {
  let base = "77";
  for (let i = base.length; i < 12; i++) base += Math.floor(Math.random() * 10);
  return base + String(ean13Checksum(base));
}

/* ======================= Precio sugerido ======================= */
const IGV = 0.18;
const MARGEN_SUGERIDO = 0.30;
let precioVentaEditadoManualmente = false;

function f2(n){ return (Math.round(parseFloat(n||0)*100)/100).toFixed(2); }

function calcularPV(compra){
  const c = parseFloat(String(compra).replace(',', '.'));
  if (isNaN(c) || c <= 0) return "";
  const sugerido = c * (1 + IGV) * (1 + MARGEN_SUGERIDO);
  return f2(sugerido);
}

function actualizarSugerido(){
  const pc = $("#precio_compra").val();
  const sug = calcularPV(pc);
  const hint = document.getElementById('pv_sugerido_hint');
  if (hint) hint.textContent = sug ? `Sugerido: S/ ${sug}` : "Sugerido: —";
  if (sug && !precioVentaEditadoManualmente) $("#precio_venta").val(sug);
}

/* ===================== FILTRO CORREGIDO ===================== */
function registrarFiltroEstado(){
  $.fn.dataTable.ext.search.push(function(settings, data, dataIndex){
    if (settings.nTable.id !== 'tbllistado') return true;
    
    if (estadoFiltro === 'all') return true;
    
    // Columna 8 (Estado) - HTML del estado
    var estadoHTML = (data[8] || '').toString().toLowerCase();
    
    console.log('🔍 Filtro aplicado:', estadoFiltro, '| Estado en fila:', estadoHTML);
    
    if (estadoFiltro === 'activos') {
      // SOLO muestra si contiene "activado" y NO contiene "desactivado"
      var esActivado = estadoHTML.indexOf('activado') !== -1;
      var esDesactivado = estadoHTML.indexOf('desactivado') !== -1;
      return esActivado && !esDesactivado;
    }
    
    if (estadoFiltro === 'desactivos') {
      // SOLO muestra si contiene "desactivado"
      return estadoHTML.indexOf('desactivado') !== -1;
    }
    
    return true;
  });
  
  console.log('✅ Filtro permanente registrado');
}

/* =========================== Init =========================== */
function init() {
  if ($.fn.dataTable) $.fn.dataTable.ext.errMode = 'none';

  mostrarform(false);
  construirTabla();

  $("#formulario").on("submit", function (e) { guardaryeditar(e); });

  $.get("../ajax/articulo.php?op=selectCategoria", function (r) {
    $("#idcategoria").html(r);
    try { $("#idcategoria").selectpicker('refresh'); } catch(_){}
  });

  $("#imagenmuestra").hide();
  $("#mAlmacen").addClass("treeview active");
  $("#lArticulos").addClass("active");

  cargarKPIStockBajo();
  cargarKPISinStock();

  $("#precio_compra").on("input", function(){
    const ok = esPrecioValido(this.value);
    setValidity(this, ok, "Precio inválido");
    actualizarSugerido();
  });

  $("#precio_venta").on("input", function(){
    precioVentaEditadoManualmente = true;
    const ok = esPrecioValido(this.value);
    setValidity(this, ok, "Precio inválido");
  });

  $("#precio_venta, #precio_compra").on("blur", function(){
    if (esPrecioValido(this.value)) this.value = f2(this.value);
  });

  $("#stock").on("input", function () {
    this.value = this.value.replace(/[^\d]/g, "").slice(0, 5);
    this.setCustomValidity(this.value === "" || parseInt(this.value) < 0 ? "Stock >= 0" : "");
  });

  $("#codigo").on("input", function () {
    this.value = this.value.replace(/\D+/g, "").slice(0, 13);
    const ok = esCodigoValido(this.value);
    setValidity(this, ok, "8-13 dígitos");
    if (ok) renderBarcode(this.value);
  });

  $(document).on('click', '#tbllistado .btn-edit', function () {
    const id = $(this).data('id');
    if (id) mostrar(id);
  });

  $(document).on('click', '#tbllistado .btn-off', function () {
    const id = $(this).data('id');
    bootbox.confirm("¿Desactivar artículo?", function (ok) {
      if (!ok) return;
      $.post("../ajax/articulo.php?op=desactivar", { idarticulo: id }, function (e) {
        try {
          var j = JSON.parse(e);
          mostrarNotificacion(j.message || '✅ Artículo desactivado', j.success === false ? 'error' : 'success');
        } catch (_) {
          mostrarNotificacion(e || '✅ Artículo desactivado', 'success');
        }
        if (tabla) tabla.ajax.reload(null, false);
        cargarKPIStockBajo();
        cargarKPISinStock();
      });
    });
  });

  $(document).on('click', '#tbllistado .btn-on', function () {
    const id = $(this).data('id');
    bootbox.confirm("¿Activar artículo?", function (ok) {
      if (!ok) return;
      $.post("../ajax/articulo.php?op=activar", { idarticulo: id }, function (e) {
        try {
          var j = JSON.parse(e);
          mostrarNotificacion(j.message || '✅ Artículo activado', j.success === false ? 'error' : 'success');
        } catch (_) {
          mostrarNotificacion(e || '✅ Artículo activado', 'success');
        }
        if (tabla) tabla.ajax.reload(null, false);
        cargarKPIStockBajo();
        cargarKPISinStock();
      });
    });
  });
}

/* ======================= KPI tooltips ======================= */
function cargarKPIStockBajo() {
  $.ajax({
    url: '../ajax/articulo.php?op=articulos_stock_bajo',
    type: 'GET',
    dataType: 'json',
    success: function(data) {
      if (data && data.success) {
        var total = parseInt(data.total) || 0;
        var articulos = data.articulos || [];
        
        $('#kpi-stock-bajo').text(total);
        
        if (articulos.length > 0) {
          var tooltipText = 'Artículos: ' + articulos.join(', ');
          $('#kpi-stock-bajo')
            .attr('title', tooltipText)
            .css({'cursor': 'help', 'text-decoration': 'underline dotted'});
          if (typeof $.fn.tooltip !== 'undefined') $('#kpi-stock-bajo').tooltip();
          console.log('✅ KPI stock bajo:', tooltipText);
        }
      }
    },
    error: function(xhr, status, error) {
      console.error('❌ Error KPI stock bajo:', error);
    }
  });
}

function cargarKPISinStock() {
  $.ajax({
    url: '../ajax/articulo.php?op=articulos_sin_stock',
    type: 'GET',
    dataType: 'json',
    success: function(data) {
      if (data && data.success) {
        var total = parseInt(data.total) || 0;
        var articulos = data.articulos || [];
        
        $('#kpi-sin-stock').text(total);
        
        if (articulos.length > 0) {
          var tooltipText = 'Artículos: ' + articulos.join(', ');
          $('#kpi-sin-stock')
            .attr('title', tooltipText)
            .css({'cursor': 'help', 'text-decoration': 'underline dotted'});
          if (typeof $.fn.tooltip !== 'undefined') $('#kpi-sin-stock').tooltip();
          console.log('✅ KPI sin stock:', tooltipText);
        }
      }
    }
  });
}

/* ============================== Vistas =============================== */
function limpiar() {
  $("#codigo, #nombre, #descripcion, #stock, #precio_compra, #precio_venta").val("");
  $("#imagenmuestra").attr("src", "").hide();
  $("#imagenactual, #idarticulo").val("");
  $("#print").hide();
  precioVentaEditadoManualmente = false;
  const hint = document.getElementById('pv_sugerido_hint');
  if (hint) hint.textContent = "Sugerido: —";
  ["#precio_compra", "#precio_venta", "#codigo"].forEach(sel => {
    const el = document.querySelector(sel);
    if (el) el.setCustomValidity("");
  });
}

function mostrarform(flag) {
  if (flag) {
    $("#listadoregistros").hide();
    $("#formularioregistros").show();
    $("#btnGuardar").prop("disabled", false);
    $("#btnagregar").hide();
  } else {
    $("#formularioregistros").hide();
    $("#listadoregistros").show();
    $("#btnagregar").show();
  }
}

function cancelarform() { limpiar(); mostrarform(false); }

/* ======================= DataTable ======================= */
function setupToolbarFilters() {
  if (!tabla) return;

  function setEstadoActive(which){
    $('.filter-btn').removeClass('active');
    $('#filter-'+which).addClass('active');
  }

  $('#filter-todos').off('click').on('click', function(){
    console.log('🔽 Click TODOS');
    estadoFiltro = 'all';
    tabla.draw();
    setEstadoActive('todos');
  });

  $('#filter-activos').off('click').on('click', function(){
    console.log('🔽 Click ACTIVOS - estableciendo filtro');
    estadoFiltro = 'activos';
    tabla.draw();
    setEstadoActive('activos');
  });

  $('#filter-desactivos').off('click').on('click', function(){
    console.log('🔽 Click DESACTIVOS');
    estadoFiltro = 'desactivos';
    tabla.draw();
    setEstadoActive('desactivos');
  });

  $('#filter-categoria').off('change').on('change', function(){
    tabla.column(2).search(this.value || '', false, false).draw();
  });

  $('#search-input').off('keyup').on('keyup', function(){
    tabla.search(this.value).draw();
  });

  $('#page-length-selector').off('change').on('change', function(){
    tabla.page.len(parseInt(this.value, 10) || 10).draw();
  });
}

function construirTabla() {
  if ($.fn.DataTable.isDataTable('#tbllistado')) {
    $('#tbllistado').DataTable().destroy();
    $('#tbllistado tbody').empty();
  }

  registrarFiltroEstado();

  tabla = $('#tbllistado').DataTable({
    processing: true,
    serverSide: false,
    deferRender: true,
    responsive: true,
    autoWidth: false,
    destroy: true,
    dom: 'Bfrtip',
    buttons: [
      { extend:'copyHtml5',  text:'Copiar',  className: 'buttons-copy'  },
      { extend:'excelHtml5', text:'Excel',  className: 'buttons-excel' },
      { extend:'csvHtml5',   text:'CSV',    className: 'buttons-csv'   },
      { extend:'pdf',        text:'PDF',    className: 'buttons-pdf'   }
    ],
    ajax: {
      url: '../ajax/articulo.php?op=listar',
      type: 'GET',
      dataType: 'json',
      dataSrc: function (json) {
        if (json && json.success === false) {
          mostrarNotificacion(json.message || 'Error al listar', 'error');
          return [];
        }
        if (json && Array.isArray(json.data)) return json.data;
        if (json && Array.isArray(json.aaData)) return json.aaData;
        try {
          if (typeof json === 'string') {
            var j = JSON.parse(json);
            if (Array.isArray(j.data)) return j.data;
            if (Array.isArray(j.aaData)) return j.aaData;
          }
        } catch (_){}
        return [];
      },
      error: function(xhr){
        console.error('listar FAIL:', xhr.status, xhr.responseText);
        mostrarNotificacion('No se pudo cargar el listado', 'error');
      }
    },
    columns: [
      { data: 0, orderable:false, searchable:false },
      { data: 1 },
      { data: 2 },
      { data: 3 },
      { data: 4, className:'text-right' },
      { data: 5, className:'text-right' },
      { data: 6, className:'text-right' },
      { data: 7, orderable:false, searchable:false },
      { data: 8, orderable:false, searchable:true }
    ],
    pageLength: 10,
    order: [[1, "asc"]],
    language: {
      "sProcessing": "Procesando...",
      "sLengthMenu": "Mostrar _MENU_ registros",
      "sZeroRecords": "No se encontraron resultados",
      "sEmptyTable": "Ningún dato disponible",
      "sInfo": "Mostrando _START_ al _END_ de _TOTAL_",
      "sInfoEmpty": "Mostrando 0 al 0 de 0",
      "sInfoFiltered": "(filtrado de _MAX_)",
      "sLoadingRecords": "Cargando...",
      "oPaginate": { "sFirst": "Primero", "sLast": "Último", "sNext": "Siguiente", "sPrevious": "Anterior" }
    },
    initComplete: function(){
      setupToolbarFilters();
      console.log('📊 DataTable inicializada. Estado filtro:', estadoFiltro);
    }
  });
}

/* ========================= Exportar ========================== */
function exportarTabla(tipo) {
  if (!tabla) return;
  try {
    if (tipo === 'copy') {
      tabla.button('.buttons-copy').trigger();
      mostrarNotificacion('✅ Tabla copiada', 'success');
    } else if (tipo === 'excel') {
      tabla.button('.buttons-excel').trigger();
      mostrarNotificacion('✅ Excel descargado', 'success');
    } else if (tipo === 'csv') {
      tabla.button('.buttons-csv').trigger();
      mostrarNotificacion('✅ CSV descargado', 'success');
    } else if (tipo === 'pdf') {
      tabla.button('.buttons-pdf').trigger();
      mostrarNotificacion('✅ Descargando PDF...', 'success');
    }
  } catch (e) {
    console.error('exportar error', e);
    mostrarNotificacion('❌ No se pudo exportar', 'error');
  }
}

/* ========================= Guardar ========================== */
function guardaryeditar(e) {
  e.preventDefault();
  $("#btnGuardar").prop("disabled", true);

  const nombre = $("#nombre").val();
  if (!esNombreValido(nombre)) {
    mostrarNotificacion("⚠️ Nombre inválido", 'warning');
    $("#btnGuardar").prop("disabled", false);
    return;
  }

  const precioCompraEl = document.querySelector("#precio_compra");
  if (!esPrecioValido(precioCompraEl.value)) {
    setValidity(precioCompraEl, false, "Precio inválido");
    $("#btnGuardar").prop("disabled", false);
    return;
  } else {
    setValidity(precioCompraEl, true, "");
  }

  const precioVentaEl = document.querySelector("#precio_venta");
  if (!esPrecioValido(precioVentaEl.value)) {
    setValidity(precioVentaEl, false, "Precio inválido");
    $("#btnGuardar").prop("disabled", false);
    return;
  } else {
    setValidity(precioVentaEl, true, "");
  }

  const stockEl = document.querySelector("#stock");
  const stockVal = parseInt(stockEl.value);
  if (isNaN(stockVal) || stockVal < 0) {
    stockEl.setCustomValidity("Stock >= 0");
    stockEl.reportValidity();
    $("#btnGuardar").prop("disabled", false);
    return;
  } else {
    stockEl.setCustomValidity("");
  }

  var formData = new FormData($("#formulario")[0]);

  $.ajax({
    url: "../ajax/articulo.php?op=guardaryeditar",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false
  })
  .done(function (resp) {
    const msg = String(resp || '').replace(/\uFEFF/g, '').trim();
    if (/duplicado/i.test(msg)) {
      mostrarNotificacion("⚠️ Nombre duplicado", 'warning');
    } else {
      try {
        var j = JSON.parse(msg);
        if (j.message && j.message.indexOf('registrado') > -1) {
          mostrarNotificacion('✅ Artículo registrado', 'success');
        } else if (j.message && j.message.indexOf('actualizado') > -1) {
          mostrarNotificacion('✅ Artículo actualizado', 'success');
        } else {
          mostrarNotificacion(j.message || msg, j.success === false ? 'error' : 'success');
        }
      } catch (_) {
        if (msg.indexOf('registrado') > -1) {
          mostrarNotificacion('✅ Artículo registrado', 'success');
        } else if (msg.indexOf('actualizado') > -1) {
          mostrarNotificacion('✅ Artículo actualizado', 'success');
        } else {
          mostrarNotificacion(msg, 'success');
        }
      }
    }
    mostrarform(false);
    limpiar();
    if (tabla) tabla.ajax.reload(null,false);
    cargarKPIStockBajo();
    cargarKPISinStock();
  })
  .fail(function(xhr){
    console.error('guardar FAIL:', xhr.status, xhr.responseText);
    mostrarNotificacion('❌ Error al guardar', 'error');
  })
  .always(function(){
    $("#btnGuardar").prop("disabled", false);
  });
}

/* ============================= Mostrar =============================== */
function mostrar(idarticulo) {
  $.post("../ajax/articulo.php?op=mostrar", { idarticulo: idarticulo }, function (data) {
    var d = (typeof data === 'string') ? JSON.parse(data) : data;
    mostrarform(true);

    $("#idcategoria").val(d.idcategoria);
    try { $('#idcategoria').selectpicker('refresh'); } catch(_){}

    $("#codigo").val(d.codigo || "");
    $("#nombre").val(d.nombre || "");
    $("#stock").val(d.stock || "");
    $("#precio_compra").val(d.precio_compra || "");
    $("#precio_venta").val(d.preccio_venta || d.precio_venta || "");
    $("#descripcion").val(d.descripcion || "");

    if (d.imagen) {
      $("#imagenmuestra").attr("src", "../files/articulos/" + d.imagen).show();
    } else {
      $("#imagenmuestra").attr("src", "").hide();
    }
    $("#imagenactual").val(d.imagen || "");
    $("#idarticulo").val(d.idarticulo);

    precioVentaEditadoManualmente = false;
    actualizarSugerido();

    if (d.codigo && /^\d{8,13}$/.test(String(d.codigo))) {
      renderBarcode(String(d.codigo));
    } else {
      $("#print").hide();
    }
  }).fail(function(xhr){
    console.error('mostrar FAIL:', xhr.status, xhr.responseText);
    mostrarNotificacion('❌ No se pudo cargar', 'error');
  });
}

/* =================== Barcode =================== */
function renderBarcode(code) {
  const clean = String(code).replace(/\D/g, "");
  const fmt = clean.length === 8 ? "EAN8" : "EAN13";
  JsBarcode("#barcode", clean, {
    format: fmt,
    displayValue: true,
    fontSize: 18,
    textMargin: 6,
    width: 2,
    height: 110,
    margin: 0
  });
  $("#print").show();
}

function generarbarcode() {
  let code = $("#codigo").val().replace(/\D/g, "");
  if (/^\d{12}$/.test(code)) {
    code = code + ean13Checksum(code);
  } else if (!/^\d{8}$/.test(code) && !/^\d{13}$/.test(code)) {
    code = generarEAN13();
  }
  $("#codigo").val(code);
  renderBarcode(code);
}

function imprimir() { $("#print").printArea(); }

init();