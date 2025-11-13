/* vistas/scripts/ingreso.js
 * Ingresos sin precio_venta — actualizado 2025
 */

var tabla;
var tablaArticulos;
var tasa_igv = 18;
var cont = 0;
var detalles = 0;

// ===============================
// Init
// ===============================
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

  // ELIMINAR LA LÍNEA: $("#tipo_comprobante").change(marcarImpuesto);

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
  $("#impuesto").val(tasa_igv); // Establece el 18% por defecto (Válido para Factura y Boleta)
  $("#total_compra").val("");
  $(".filas").remove();
  
  // --- NUEVO: Limpiar los nuevos totales desglosados (asumiendo cambios en ingreso.php) ---
  $("#total").html("S/. 0.00");
  $("#total_neto_h4").html("S/. 0.00");
  $("#total_impuesto_h4").html("S/. 0.00");
  $("#total_neto").val("0.00");
  // ---------------------------------------------------------------------------------------

  detalles = 0;
  autoprepararFecha();
  // Se ELIMINA la llamada a marcarImpuesto() para que el valor de IGV (tasa_igv) se mantenga, 
  // ya que la Boleta ahora también debe llevar el desglose del 18%.
  // La lógica de cálculo total se basará solo en el valor numérico del campo #impuesto.
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
  var formData = new FormData($("#formulario")[0]);

  $.ajax({
    url: "../ajax/ingreso.php?op=guardaryeditar",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function (datos) {
      bootbox.alert(datos);
      if (/registrado|actualizado/i.test(datos)) {
        mostrarform(false);
        tabla.ajax.reload(null, false);
      }
    }
  });

  limpiar();
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
    $("#fecha_hora").val(data.fecha_hora);
    
    // 1. Cargamos el impuesto en el campo con el ID corregido
    $("#impuesto").val(data.impuesto); 
    
    $("#idingreso").val(data.idingreso);

    $("#btnGuardar").hide();
    $("#btnCancelar").show();
    $("#btnAgregarArt").hide();
  });

  // 2. Lógica para el detalle: Se mantiene la carga del detalle
  $.get("../ajax/ingreso.php?op=listarDetalle&id=" + idingreso, function (r) {
    // CORRECCIÓN CLAVE: Solo actualizamos el cuerpo (tbody) de la tabla #detalles.
    // Usamos el selector "#detalles tbody" para no borrar el nuevo tfoot.
    $("#detalles tbody").html(r);
    
    // 3. LLAMADA CLAVE: Calculamos los totales inmediatamente después de cargar las filas.
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
        fila +=   '<td><input type="number" name="precio_compra[]" value="' + Number(pcompra).toFixed(2) + '" step="0.01" min="0" oninput="modificarSubototales()"></td>';
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
  
  // Obtener el porcentaje de impuesto del campo. 
  // Si el usuario pone "0" (por ejemplo, para un Ticket o una operación exonerada), el IGV será 0.
  var impuesto_porcentaje = parseFloat($("#impuesto").val() || 0); 

  // 1. Sumar todos los Subtotales (Base Imponible/Neto)
  for (var i = 0; i < sub.length; i++) {
    totalSinIGV += parseFloat(sub[i].innerHTML || "0");
  }

  var igv_total = 0.0;
  var totalConIGV = totalSinIGV;
  
  // --- LÓGICA MODIFICADA: Si hay un porcentaje de impuesto > 0, se aplica a cualquier comprobante. ---
  if (impuesto_porcentaje > 0) {
    igv_total = totalSinIGV * (impuesto_porcentaje / 100);
    totalConIGV = totalSinIGV + igv_total;
  }
  
  // --- 2. Actualizar la interfaz con el desglose (ESTE DESGLOSE APLICA A TODOS) ---

  // a) Actualizar el Subtotal Neto (Base Imponible)
  $("#total_neto_h4").text("S/. " + totalSinIGV.toFixed(2));
  $("#total_neto").val(totalSinIGV.toFixed(2));

  // b) Actualizar el IGV Total y el texto de la etiqueta
  $("#total_impuesto_h4").text("S/. " + igv_total.toFixed(2));
  $("#mostrar_impuesto").text('IGV (' + impuesto_porcentaje.toFixed(0) + '%)'); // Muestra el %

  // c) Actualizar el TOTAL FINAL (Total Compra Bruto)
  $("#total").text("S/. " + totalConIGV.toFixed(2));
  $("#total_compra").val(totalConIGV.toFixed(2)); // Este es el valor que se guardará en la BD

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

window.agregarDetalle = agregarDetalle;
window.mostrar = mostrar;
window.anular = anular;

init();
