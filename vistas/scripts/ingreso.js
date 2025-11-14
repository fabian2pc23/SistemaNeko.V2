/* vistas/scripts/ingreso.js - VERSIÓN CORREGIDA */

var tabla;
var tablaArticulos;
var tasa_igv = 18;
var cont = 0;
var detalles = 0;

// ===============================
// Init
// ===============================
function init () {
  mostrarform(false);
  construirTabla();

  $("#formulario").on("submit", function (e) { guardaryeditar(e); });

  $.post("../ajax/ingreso.php?op=selectProveedor", function (r) {
    $("#idproveedor").html(r);
    $('#idproveedor').selectpicker('refresh');
  });

  $('#mCompras').addClass("treeview active");
  $('#lIngresos').addClass("active");

  autoprepararFecha();
}

// ===============================
// Utilidades
// ===============================
function autoprepararFecha () {
  var f = document.getElementById('fecha_hora');
  if (!f) return;
  if (!f.value) {
    var now = new Date();
    var day = ("0" + now.getDate()).slice(-2);
    var month = ("0" + (now.getMonth() + 1)).slice(-2);
    f.value = now.getFullYear() + "-" + month + "-" + day;
  }
}

// ===============================
// Listado principal
// ===============================
function construirTabla () {
  if ($.fn.DataTable.isDataTable('#tbllistado')) {
    $('#tbllistado').DataTable().destroy();
    $('#tbllistado tbody').empty();
  }

  tabla = $('#tbllistado').DataTable({
    aProcessing: true,
    aServerSide: true,
    iDisplayLength: 5,
    lengthMenu: [5, 10, 25, 50, 100],
    order: [[1, 'desc']],
    dom: 'Brtip',
    buttons: [
      { extend: 'copyHtml5', text: 'Copy' },
      { extend: 'excelHtml5', text: 'Excel' },
      { extend: 'csvHtml5',  text: 'CSV' },
      { extend: 'pdfHtml5',  text: 'PDF' }
    ],
    ajax: {
      url: '../ajax/ingreso.php?op=listar',
      type: 'GET',
      dataType: 'json',
      data: function (d) {
        d.desde = $('#filtro_desde').val() || '';
        d.hasta = $('#filtro_hasta').val() || '';
      },
      error: function (e) { console.log(e.responseText); }
    },
    language: {
      lengthMenu: 'Mostrar : MENU registros',
      paginate: { previous: 'Anterior', next: 'Siguiente' },
      info: 'Mostrando START a END de TOTAL registros',
      zeroRecords: 'No se encontraron resultados'
    },
    drawCallback: function(){
      $('#tbllistado_wrapper .dataTables_length, #tbllistado_wrapper .dataTables_filter').hide();
    }
  });

  var $holder = $('.dt-buttons-holder');
  if ($holder.length && !$holder.find('.dt-buttons').length) {
    $holder.append(tabla.buttons().container());
  }

  $('#customLength').val(tabla.page.len()).off('change').on('change', function () {
    tabla.page.len(parseInt(this.value, 10) || 5).draw();
  });

  $('#customSearch').off('keyup change input').on('keyup change input', function () {
    tabla.search(this.value).draw();
  });

  $('#filtro_desde, #filtro_hasta').off('change input').on('change input', function(){
    tabla.ajax.reload(null, false);
  });
  $('#btnFiltrar').off('click').on('click', function(){ tabla.ajax.reload(); });
  $('#btnLimpiarFiltro').off('click').on('click', function(){
    $('#filtro_desde').val(''); $('#filtro_hasta').val('');
    tabla.ajax.reload();
  });
}

// ===============================
// Formularios
// ===============================
function limpiar () {
  $("#idingreso").val("");
  $("#idproveedor").val("").selectpicker('refresh');
  $("#tipo_comprobante").val("Boleta").selectpicker('refresh');
  $("#serie_comprobante").val("");
  $("#num_comprobante").val("");
  $("#impuesto").val(tasa_igv);
  $("#total_compra").val("");
  $(".filas").remove();
  
  $("#total").html("S/. 0.00");
  $("#total_neto_h4").html("S/. 0.00");
  $("#total_impuesto_h4").html("S/. 0.00");
  $("#total_neto").val("0.00");
  $("#monto_impuesto").val("0.00");
  $("#total_compra").val("0.00");

  detalles = 0;
  cont = 0;
  autoprepararFecha();
}

function mostrarform (flag) {
  if (flag) {
    $("#listadoregistros").hide();
    $("#formularioregistros").show();
    $("#btnagregar").hide();
    listarArticulos();
    $("#btnGuardar").hide();
    $("#btnCancelar").show();
    $("#btnAgregarArt").show();
    detalles = 0;
  } else {
    $("#listadoregistros").show();
    $("#formularioregistros").hide();
    $("#btnagregar").show();
  }
}

function cancelarform () {
  limpiar();
  mostrarform(false);
}

// ===============================
// Modal Artículos
// ===============================
function listarArticulos () {
  tablaArticulos = $('#tblarticulos').dataTable({
    aProcessing: true,
    aServerSide: true,
    dom: 'Bfrtip',
    buttons: [],
    ajax: {
      url: '../ajax/ingreso.php?op=listarArticulos',
      type: "get",
      dataType: "json",
      error: function (e) { console.log(e.responseText); }
    },
    bDestroy: true,
    iDisplayLength: 5,
    order: [[1, "asc"]]
  }).DataTable();
}

// ===============================
// Guardar / Editar
// ===============================
function guardaryeditar (e) {
  e.preventDefault();
  
  // ⭐ PASO 1: Recalcular totales antes de enviar
  calcularTotales();
  
  // ⭐ PASO 2: Verificar que hay detalles
  if (detalles === 0) {
    bootbox.alert("Debe agregar al menos un artículo");
    return false;
  }
  
  // ⭐ PASO 3: Capturar datos del formulario
  var formData = new FormData($("#formulario")[0]);
  
  // ⭐ PASO 4: DEBUG - Ver qué se va a enviar
  console.log("=== DATOS A ENVIAR ===");
  console.log("Subtotal (Neto):", formData.get("total_neto"));
  console.log("IGV (Monto):", formData.get("monto_impuesto"));
  console.log("Total:", formData.get("total_compra"));
  console.log("Impuesto %:", formData.get("impuesto"));
  console.log("=====================");

  // ⭐ PASO 5: Enviar al servidor
  $.ajax({
    url: "../ajax/ingreso.php?op=guardaryeditar",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function (datos) {
      console.log("Respuesta del servidor:", datos);
      bootbox.alert(datos);
      
      if (/registrado/i.test(datos)) {
        // ⭐ SOLO limpiar DESPUÉS de guardar exitosamente
        limpiar();
        mostrarform(false);
        tabla.ajax.reload(null, false);
      }
    },
    error: function(xhr, status, error) {
      console.error("Error AJAX:", error);
      console.error("Respuesta completa:", xhr.responseText);
      bootbox.alert("Error al guardar: " + error);
    }
  });
  
  // ❌ NO llamar limpiar() aquí
}

// ===============================
// Mostrar / Anular
// ===============================
function mostrar (idingreso) {
  mostrarform(true);

  $.post("../ajax/ingreso.php?op=mostrar", { idingreso: idingreso }, function (data) {
    data = JSON.parse(data);

    $("#idproveedor").val(data.idproveedor).selectpicker('refresh');
    $("#tipo_comprobante").val(data.tipo_comprobante).selectpicker('refresh');
    $("#serie_comprobante").val(data.serie_comprobante);
    $("#num_comprobante").val(data.num_comprobante);
    $("#fecha_hora").val(data.fecha);
    $("#impuesto").val(data.impuesto_porcentaje || 18);
    $("#idingreso").val(data.idingreso);

    $("#btnGuardar").hide();
    $("#btnCancelar").show();
    $("#btnAgregarArt").hide();
  });

  $.get("../ajax/ingreso.php?op=listarDetalle&id=" + idingreso, function (r) {
    $("#detalles tbody").html(r);
    calcularTotales();
  });
}

function anular (idingreso) {
  bootbox.confirm("¿Está Seguro de anular el ingreso?", function (result) {
    if (result) {
      $.post("../ajax/ingreso.php?op=anular", { idingreso: idingreso }, function (e) {
        bootbox.alert(e);
        tabla.ajax.reload(null, false);
      });
    }
  });
}

// ===============================
// Detalle
// ===============================
$("#btnGuardar").hide();

function agregarDetalle (idarticulo, articulo, pcompra) {
  var cantidad = 1;
  if (idarticulo !== "") {
    var subtotal = cantidad * pcompra;

    var fila  = '<tr class="filas" id="fila' + cont + '">';
        fila +=   '<td><button type="button" class="btn btn-danger" onclick="eliminarDetalle(' + cont + ')">X</button></td>';
        fila +=   '<td><input type="hidden" name="idarticulo[]" value="' + idarticulo + '">' + articulo + '</td>';
        fila +=   '<td><input type="number" name="cantidad[]" value="' + cantidad + '" min="1" oninput="modificarSubototales()"></td>';
        fila +=   '<td><input type="number" name="precio_compra[]" value="' + Number(pcompra).toFixed(2) + '" step="0.01" min="0" oninput="modificarSubototales()"></td>';
        fila +=   '<td><span name="subtotal" id="subtotal' + cont + '">' + subtotal.toFixed(2) + '</span></td>';
        fila += '</tr>';

    cont++;
    detalles++;
    $('#detalles tbody').append(fila);
    modificarSubototales();
  } else {
    alert("Error al ingresar el detalle, revisar los datos del artículo");
  }
}

function modificarSubototales () {
  var cant = document.getElementsByName("cantidad[]");
  var prec = document.getElementsByName("precio_compra[]");
  var sub  = document.getElementsByName("subtotal");

  for (var i = 0; i < cant.length; i++) {
    var scalc = (parseFloat(cant[i].value || 0) * parseFloat(prec[i].value || 0)).toFixed(2);
    sub[i].innerHTML = scalc;
  }
  calcularTotales();
}

function calcularTotales () {
  var sub = document.getElementsByName("subtotal");
  var totalSinIGV = 0.0;
  
  // Obtener el porcentaje de impuesto
  var impuesto_porcentaje = parseFloat($("#impuesto").val() || 0);

  // 1. Sumar todos los Subtotales
  for (var i = 0; i < sub.length; i++) {
    totalSinIGV += parseFloat(sub[i].innerHTML || "0");
  }

  var igv_total = 0.0;
  var totalConIGV = totalSinIGV;
  
  // 2. Calcular IGV si el porcentaje es mayor a 0
  if (impuesto_porcentaje > 0) {
    igv_total = totalSinIGV * (impuesto_porcentaje / 100);
    totalConIGV = totalSinIGV + igv_total;
  }
  
  // 3. Actualizar interfaz
  $("#total_neto_h4").text("S/. " + totalSinIGV.toFixed(2));
  $("#total_neto").val(totalSinIGV.toFixed(2));
  
  $("#total_impuesto_h4").text("S/. " + igv_total.toFixed(2));
  $("#monto_impuesto").val(igv_total.toFixed(2)); // ⭐ CRÍTICO
  $("#mostrar_impuesto").text('IGV (' + impuesto_porcentaje.toFixed(0) + '%)');
  
  $("#total").text("S/. " + totalConIGV.toFixed(2));
  $("#total_compra").val(totalConIGV.toFixed(2));
  
  // 4. DEBUG
  console.log("Cálculo de totales:");
  console.log("- Subtotal:", totalSinIGV.toFixed(2));
  console.log("- IGV (" + impuesto_porcentaje + "%):", igv_total.toFixed(2));
  console.log("- Total:", totalConIGV.toFixed(2));
  console.log("- Campo #monto_impuesto:", $("#monto_impuesto").val());

  evaluar();
}

function evaluar () {
  if (detalles > 0) $("#btnGuardar").show();
  else { $("#btnGuardar").hide(); cont = 0; }
}

function eliminarDetalle (indice) {
  $("#fila" + indice).remove();
  detalles--;
  calcularTotales();
  evaluar();
}

// Exportar funciones al scope global
window.agregarDetalle = agregarDetalle;
window.mostrar = mostrar;
window.anular = anular;

// Inicializar
init();