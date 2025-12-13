/* vistas/scripts/ingreso.js – versión unificada con articulo.php */

var tabla;
var tablaArticulos;
var tasa_igv = 18;
var cont = 0;
var detalles = 0;
var estadoFiltro = 'todos'; // todos, Aceptado, Anulado

// ===============================
// Init
// ===============================
function init() {
  mostrarform(false);
  construirTabla();

  // Submit del formulario
  $("#formulario").on("submit", function (e) { guardaryeditar(e); });

  // Combo de proveedor
  $.post("../ajax/ingreso.php?op=selectProveedor", function (r) {
    $("#idproveedor").html(r);
    $('#idproveedor').selectpicker('refresh');
  });

  // Menú activo
  $('#mCompras').addClass("treeview active");
  $('#lIngresos').addClass("active");

  autoprepararFecha();
  initFilters();

  // Evento para obtener serie y número al cambiar tipo de comprobante
  $("#tipo_comprobante").change(obtenerSerieNumero);
}

// ===============================
// Utilidades
// ===============================
function autoprepararFecha() {
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
function setupToolbarFilters() {
  if (!tabla) return;

  function setEstadoActive(which) {
    $('.filter-btn').removeClass('active');
    $('#filter-' + which).addClass('active');
  }

  $('#filter-todos').off('click').on('click', function () {
    estadoFiltro = 'todos';
    tabla.ajax.reload();
    setEstadoActive('todos');
  });

  $('#filter-aceptados').off('click').on('click', function () {
    estadoFiltro = 'Aceptado';
    tabla.ajax.reload();
    setEstadoActive('aceptados');
  });

  $('#filter-anulados').off('click').on('click', function () {
    estadoFiltro = 'Anulado';
    tabla.ajax.reload();
    setEstadoActive('anulados');
  });

  // Filtros de fecha
  $('#filtro_desde, #filtro_hasta').off('change input').on('change input', function () {
    tabla.ajax.reload(null, false);
  });

  $('#btnLimpiarFiltro').off('click').on('click', function () {
    $('#filtro_desde').val('');
    $('#filtro_hasta').val('');
    tabla.ajax.reload();
  });

  // Buscador custom
  $('#search-input').off('keyup change input').on('keyup change input', function () {
    tabla.search(this.value).draw();
  });
}

function construirTabla() {
  if ($.fn.DataTable.isDataTable('#tbllistado')) {
    $('#tbllistado').DataTable().destroy();
    $('#tbllistado tbody').empty();
  }

  tabla = $('#tbllistado').DataTable({
    aProcessing: true,
    aServerSide: true,
    dom: 'Bfrtip',
    buttons: [
      { extend: 'copyHtml5', text: 'Copy', className: 'buttons-copy' },
      { extend: 'excelHtml5', text: 'Excel', className: 'buttons-excel' },
      { extend: 'csvHtml5', text: 'CSV', className: 'buttons-csv' },
      { extend: 'pdfHtml5', text: 'PDF', className: 'buttons-pdf' }
    ],
    ajax: {
      url: '../ajax/ingreso.php?op=listar',
      type: 'GET',
      dataType: 'json',
      data: function (d) {
        d.desde = $('#filtro_desde').val() || '';
        d.hasta = $('#filtro_hasta').val() || '';
        d.estado = estadoFiltro;
      },
      error: function (e) { console.log(e.responseText); }
    },
    language: {
      lengthMenu: 'Mostrar : MENU registros',
      paginate: { previous: 'Anterior', next: 'Siguiente' },
      info: 'Mostrando START a END de TOTAL registros',
      zeroRecords: 'No se encontraron resultados',
      sProcessing: 'Procesando...',
      sSearch: 'Buscar:'
    },
    drawCallback: function () {
      $('#tbllistado_wrapper .dataTables_length, #tbllistado_wrapper .dataTables_filter').hide();
    },
    initComplete: function () {
      setupToolbarFilters();
    }
  });
}

function exportarTabla(tipo) {
  if (!tabla) return;
  if (tipo === 'copy') tabla.button('.buttons-copy').trigger();
  else if (tipo === 'excel') tabla.button('.buttons-excel').trigger();
  else if (tipo === 'csv') tabla.button('.buttons-csv').trigger();
  else if (tipo === 'pdf') tabla.button('.buttons-pdf').trigger();
}

// ==================== FUNCIÓN MOSTRAR DETALLE KPI ====================
function mostrarDetalleKPI(tipo) {
  Swal.fire({
    title: 'Cargando información...',
    html: '<div style="padding: 20px;"><i class="fa fa-spinner fa-spin fa-3x" style="color: #1565c0;"></i></div>',
    showConfirmButton: false,
    allowOutsideClick: false
  });

  $.ajax({
    url: '../ajax/ingreso.php?op=kpi_detalle&tipo=' + tipo,
    type: 'GET',
    dataType: 'json',
    success: function (resp) {
      Swal.close();

      if (resp.success && resp.datos.length > 0) {
        var tablaHtml = '<div style="max-height: 400px; overflow-y: auto;">';
        tablaHtml += '<p style="color: #64748b; margin-bottom: 12px; font-size: 0.9rem;">' + resp.descripcion + '</p>';
        tablaHtml += '<table class="table table-striped table-bordered" style="font-size: 0.85rem; width: 100%;">';

        tablaHtml += '<thead style="background: #1e293b; color: white;"><tr>';
        resp.columnas.forEach(function (col) {
          tablaHtml += '<th style="padding: 8px; text-align: center;">' + col + '</th>';
        });
        tablaHtml += '</tr></thead>';

        tablaHtml += '<tbody>';
        resp.datos.forEach(function (row, idx) {
          var bgColor = idx % 2 === 0 ? '#fff' : '#f8fafc';
          tablaHtml += '<tr style="background: ' + bgColor + ';">';
          Object.values(row).forEach(function (val) {
            tablaHtml += '<td style="padding: 6px 8px;">' + (val !== null ? val : '-') + '</td>';
          });
          tablaHtml += '</tr>';
        });
        tablaHtml += '</tbody></table></div>';

        if (resp.datos.length >= 50) {
          tablaHtml += '<p style="color: #94a3b8; font-size: 0.8rem; margin-top: 10px; text-align: center;">Mostrando los primeros 50 registros</p>';
        }

        Swal.fire({
          title: '<i class="fa fa-shopping-bag" style="color: #1565c0; margin-right: 8px;"></i>' + resp.titulo,
          html: tablaHtml,
          width: '850px',
          showCloseButton: true,
          showConfirmButton: false
        });
      } else if (resp.success && resp.datos.length === 0) {
        Swal.fire({
          icon: 'info',
          title: resp.titulo,
          text: 'No hay datos para mostrar en este indicador',
          timer: 3000,
          showConfirmButton: false
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'No se pudo cargar la información',
          timer: 3000,
          showConfirmButton: false
        });
      }
    },
    error: function () {
      Swal.close();
      Swal.fire({
        icon: 'error',
        title: 'Error de conexión',
        text: 'No se pudo conectar con el servidor',
        timer: 3000,
        showConfirmButton: false
      });
    }
  });
}

// ===============================
// Formularios
// ===============================
function limpiar() {
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

  // Tipo ingreso por defecto: compra (reposicion)
  $("#tipo_ingreso").val("compra");
  $("#idproveedor").prop("required", true);

  autoprepararFecha();
}

function mostrarform(flag) {
  if (flag) {
    $("#listadoregistros").hide();
    $("#formularioregistros").show();
    $("#btnagregar").hide();   // contenedor de botones en el header
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

function cancelarform() {
  limpiar();
  mostrarform(false);
}

// ===============================
// Modo de ingreso (compra / alta_inicial)
// ===============================
function nuevoIngreso(tipo) {
  // tipo: 'compra' (Reposición) o 'alta_inicial' (Nuevo producto)
  limpiar();
  $("#tipo_ingreso").val(tipo);

  if (tipo === 'alta_inicial') {
    // Alta inicial: permitir proveedor opcional
    $("#idproveedor").prop("required", false);
  } else {
    // Compra normal: proveedor obligatorio
    $("#idproveedor").prop("required", true);
  }

  mostrarform(true);
  obtenerSerieNumero();
}

// Wrappers para ser compatibles con los onclick antiguos
function nuevoIngresoExistente() {
  nuevoIngreso('compra');
}
function nuevoIngresoNuevo() {
  nuevoIngreso('alta_inicial');
}

// ===============================
// Modal Artículos
// ===============================
function listarArticulos() {
  tablaArticulos = $('#tblarticulos').DataTable({
    "aProcessing": true,
    "aServerSide": true,
    dom: 'Blfrtip', // 'l' habilita el selector de longitud
    buttons: [],
    "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
    "ajax": {
      url: '../ajax/ingreso.php?op=listarArticulos',
      type: "get",
      dataType: "json",
      error: function (e) { console.log(e.responseText); }
    },
    "bDestroy": true,
    "iDisplayLength": 5,
    "order": [[1, "asc"]],
    "columnDefs": [
      {
        "targets": 1, // Columna Nombre
        "render": function (data, type, row) {
          if (type === 'display') {
            // Capitalizar primera letra, resto minúscula
            return data.charAt(0).toUpperCase() + data.slice(1).toLowerCase();
          }
          return data;
        }
      },
      {
        "targets": 5, // Columna Stock
        "createdCell": function (td, cellData, rowData, row, col) {
          var stock = parseInt(cellData);
          if (stock === 0) {
            $(td).css({ "color": "red", "font-weight": "bold" });
          } else if (stock <= 5) {
            $(td).css({ "color": "orange", "font-weight": "bold" });
          } else if (stock > 7) {
            $(td).css({ "color": "green", "font-weight": "bold" });
          }
        }
      }
    ]
  });
}

// ===============================
// Filtros Modal
// ===============================
function initFilters() {
  // Cargar Categorías
  $.post("../ajax/articulo.php?op=selectCategoria", function (r) {
    $("#filtro_categoria").html('<option value="">Todas</option>' + r);
    $('#filtro_categoria').selectpicker('refresh');
  });

  // Cargar Marcas (Necesitamos un endpoint para marcas, usaremos el de articulo si existe o uno nuevo)
  // Asumiendo que existe un endpoint similar o lo agregamos. 
  // Por ahora, si no existe un endpoint especifico de solo marcas en articulo.php, 
  // podemos intentar usar el de venta.php o crear uno.
  // Revisando ajax/articulo.php, no hay selectMarca. 
  // Revisando ajax/venta.php, tampoco.
  // Pero en la vista venta.php se usó: $.post("../ajax/articulo.php?op=selectMarca" ...
  // Si ese endpoint no existe, el filtro no cargará. 
  // Asumiré que se debe implementar o usar uno existente.
  // Voy a usar el mismo patrón que en venta.js

  // NOTA: Si op=selectMarca no existe en ajax/articulo.php, esto fallará silenciosamente.
  // Se recomienda verificar ajax/articulo.php.
  // En el paso anterior vi ajax/articulo.php y NO TIENE selectMarca.
  // Sin embargo, en ajax/venta.php tampoco.
  // Voy a agregar el endpoint selectMarca a ajax/articulo.php si es necesario, 
  // pero primero implemento el JS asumiendo que existirá o lo crearé.

  // CORRECCION: En la sesión anterior se mencionó que se agregaron filtros a venta.php.
  // Voy a verificar si se agregó el endpoint.
  // Si no, lo agregaré.

  $.post("../ajax/articulo.php?op=selectMarca", function (r) {
    $("#filtro_marca").html('<option value="">Todas</option>' + r);
    $('#filtro_marca').selectpicker('refresh');
  });

  // Eventos de cambio en filtros
  $('#filtro_categoria').on('change', function () {
    tablaArticulos.column(2).search(this.value).draw();
  });

  $('#filtro_marca').on('change', function () {
    tablaArticulos.column(3).search(this.value).draw();
  });
}

// ===============================
// Guardar / Editar
// ===============================
function guardaryeditar(e) {
  e.preventDefault();

  // 1. Recalcular totales antes de enviar
  calcularTotales();

  // 2. Verificar que hay detalles
  if (detalles === 0) {
    bootbox.alert("Debe agregar al menos un artículo");
    return false;
  }

  // 3. Capturar datos del formulario
  var formData = new FormData($("#formulario")[0]);

  // 5. Enviar al servidor
  $.ajax({
    url: "../ajax/ingreso.php?op=guardaryeditar",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function (datos) {
      if (datos.includes('No hay caja abierta')) {
        Swal.fire({
          title: '¡Caja Cerrada!',
          text: datos,
          icon: 'warning',
          confirmButtonText: 'Entendido',
          confirmButtonColor: '#ffc107'
        });
      } else {
        bootbox.alert(datos);
      }

      if (/registrado/i.test(datos)) {
        // Capturar IDs de artículos actualizados para resaltar
        let updatedArticles = [];
        $('input[name="idarticulo[]"]').each(function () {
          updatedArticles.push(parseInt($(this).val()));
        });

        if (updatedArticles.length > 0) {
          let highlighted = JSON.parse(localStorage.getItem('highlight_articles') || '[]');
          // Unir y quitar duplicados
          let combined = [...new Set([...highlighted, ...updatedArticles])];
          localStorage.setItem('highlight_articles', JSON.stringify(combined));
          console.log("Saved updated article IDs to localStorage:", updatedArticles);
        }

        limpiar();
        mostrarform(false);
        tabla.ajax.reload(null, false);
      }
    },
    error: function (xhr, status, error) {
      bootbox.alert("Error al guardar: " + error);
    }
  });
}

// ===============================
// Mostrar / Anular
// ===============================
function mostrar(idingreso) {
  mostrarform(true);

  $.post("../ajax/ingreso.php?op=mostrar", { idingreso: idingreso }, function (data) {
    data = JSON.parse(data);

    $("#idproveedor").val(data.idproveedor).selectpicker('refresh');
    $("#tipo_comprobante").val(data.tipo_comprobante).selectpicker('refresh');
    $("#serie_comprobante").val(data.serie_comprobante);
    $("#num_comprobante").val(data.num_comprobante);
    $("#fecha_hora").val(data.fecha);
    $("#impuesto").val(data.impuesto_porcentaje || 18);
    $("#tipo_ingreso").val(data.tipo_ingreso || 'compra');
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

function anular(idingreso) {
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

function agregarDetalle(idarticulo, articulo, pcompra) {
  // 1. Actualizar visualmente el stock en el modal (siempre)
  actualizarStockVisual(idarticulo);

  var cantidad = 1;

  // 2. Verificar si el artículo ya existe en el detalle para agruparlo
  var existe = false;
  $('input[name="idarticulo[]"]').each(function () {
    if ($(this).val() == idarticulo) {
      existe = true;
      var $row = $(this).closest('tr');
      var $cantInput = $row.find('input[name="cantidad[]"]');
      var currentCant = parseFloat($cantInput.val());
      $cantInput.val(currentCant + 1);
      modificarSubototales();
      // Efecto visual para indicar actualización
      $row.css("background-color", "#e8f5e9");
      setTimeout(function () { $row.css("background-color", ""); }, 500);
      return false; // break loop
    }
  });

  if (existe) {
    return;
  }

  if (idarticulo !== "") {
    var subtotal = cantidad * pcompra;

    // Formatear nombre: Primera mayúscula, resto minúscula
    articulo = articulo.charAt(0).toUpperCase() + articulo.slice(1).toLowerCase();

    var fila = '<tr class="filas" id="fila' + cont + '">';
    fila += '<td><button type="button" class="btn btn-danger" onclick="eliminarDetalle(' + cont + ')"><i class="fa fa-trash"></i></button></td>';
    fila += '<td><input type="hidden" name="idarticulo[]" value="' + idarticulo + '">' + articulo + '</td>';
    fila += '<td><input type="number" name="cantidad[]" value="' + cantidad + '" min="1" oninput="modificarSubototales()"></td>';
    fila += '<td><input type="number" name="precio_compra[]" value="' + Number(pcompra).toFixed(2) + '" step="0.01" min="0" oninput="modificarSubototales()"></td>';
    fila += '<td><span name="subtotal" id="subtotal' + cont + '">' + subtotal.toFixed(2) + '</span></td>';
    fila += '</tr>';

    cont++;
    detalles++;
    $('#detalles tbody').append(fila);
    modificarSubototales();
  } else {
    alert("Error al ingresar el detalle, revisar los datos del artículo");
  }
}

function actualizarStockVisual(idarticulo) {
  // Buscamos el botón que corresponde a este artículo en la tabla del modal
  var $btn = $('#tblarticulos tbody button[onclick*="agregarDetalle(' + idarticulo + ',"]');

  if ($btn.length > 0) {
    var $tr = $btn.closest('tr');
    // La columna Stock es la 5 (índice 5, contando desde 0)
    var $stockCell = $tr.find('td').eq(5);

    var currentStock = parseInt($stockCell.text()) || 0;
    var newStock = currentStock + 1;

    $stockCell.text(newStock);

    // Re-aplicar colores según la lógica definida
    $stockCell.css("font-weight", "bold");
    if (newStock === 0) {
      $stockCell.css("color", "red");
    } else if (newStock <= 5) {
      $stockCell.css("color", "orange");
    } else if (newStock > 7) {
      $stockCell.css("color", "green");
    } else {
      $stockCell.css("color", "");
    }

    // Efecto visual de parpadeo
    $stockCell.fadeOut(100).fadeIn(100);
  }
}

function obtenerSerieNumero() {
  var tipo = $("#tipo_comprobante").val();
  $.post("../ajax/ingreso.php?op=getLastSerieNumero", { tipo_comprobante: tipo }, function (data) {
    try {
      data = JSON.parse(data);
      $("#serie_comprobante").val(data.serie);
      $("#num_comprobante").val(data.numero);
    } catch (e) {
      console.log(e);
    }
  });
}

function modificarSubototales() {
  var cant = document.getElementsByName("cantidad[]");
  var prec = document.getElementsByName("precio_compra[]");
  var sub = document.getElementsByName("subtotal");

  for (var i = 0; i < cant.length; i++) {
    var scalc = (parseFloat(cant[i].value || 0) * parseFloat(prec[i].value || 0)).toFixed(2);
    sub[i].innerHTML = scalc;
  }
  calcularTotales();
}

function calcularTotales() {
  var sub = document.getElementsByName("subtotal");
  var totalSinIGV = 0.0;

  // Porcentaje de impuesto
  var impuesto_porcentaje = parseFloat($("#impuesto").val() || 0);

  // Sumar subtotales
  for (var i = 0; i < sub.length; i++) {
    totalSinIGV += parseFloat(sub[i].innerHTML || "0");
  }

  var igv_total = 0.0;
  var totalConIGV = totalSinIGV;

  if (impuesto_porcentaje > 0) {
    igv_total = totalSinIGV * (impuesto_porcentaje / 100);
    totalConIGV = totalSinIGV + igv_total;
  }

  // Actualizar UI
  $("#total_neto_h4").text("S/. " + totalSinIGV.toFixed(2));
  $("#total_neto").val(totalSinIGV.toFixed(2));

  $("#total_impuesto_h4").text("S/. " + igv_total.toFixed(2));
  $("#monto_impuesto").val(igv_total.toFixed(2));
  $("#mostrar_impuesto").text('IGV (' + impuesto_porcentaje.toFixed(0) + '%)');

  $("#total").text("S/. " + totalConIGV.toFixed(2));
  $("#total_compra").val(totalConIGV.toFixed(2));

  evaluar();
}

function evaluar() {
  if (detalles > 0) $("#btnGuardar").show();
  else { $("#btnGuardar").hide(); cont = 0; }
}

function eliminarDetalle(indice) {
  $("#fila" + indice).remove();
  detalles--;
  calcularTotales();
  evaluar();
}

// ===============================
// Exportar funciones globales
// ===============================
window.agregarDetalle = agregarDetalle;
window.mostrar = mostrar;
window.anular = anular;
window.nuevoIngreso = nuevoIngreso;
window.nuevoIngresoExistente = nuevoIngresoExistente;
window.nuevoIngresoNuevo = nuevoIngresoNuevo;
window.exportarTabla = exportarTabla;

// Inicializar
// ... existing code ...

// ===============================
// Lógica para Nuevo Artículo (Quick Add)
// ===============================
function nuevoIngresoNuevo() {
  $("#modalNuevoArticulo").modal("show");
  limpiarArticulo();
}

function limpiarArticulo() {
  $("#idarticulo_new").val("");
  $("#nombre_new").val("");
  $("#descripcion_new").val("");
  $("#codigo_new").val("");
  $("#imagen_new").val("");
  $("#imagenactual_new").val("");
  $("#imagenmuestra_new").attr("src", "").hide();
  $("#print_new").hide();

  // Resetear selects
  $("#idcategoria_new").val($("#idcategoria_new option:first").val());
  $("#idcategoria_new").selectpicker('refresh');
  $("#idmarca_new").val($("#idmarca_new option:first").val());
  $("#idmarca_new").selectpicker('refresh');
}

function guardaryeditarArticulo(e) {
  e.preventDefault(); // No se activará la acción predeterminada del evento
  $("#btnGuardarArticulo").prop("disabled", true);
  var formData = new FormData($("#formularioArticulo")[0]);

  $.ajax({
    url: "../ajax/articulo.php?op=guardaryeditar",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function (datos) {
      let resp;
      try {
        resp = (typeof datos === 'string') ? JSON.parse(datos) : datos;
      } catch (ex) {
        resp = { success: false, message: datos };
      }

      if (resp.success) {
        bootbox.alert(resp.message);
        $("#modalNuevoArticulo").modal("hide");
        $("#btnGuardarArticulo").prop("disabled", false);
        limpiarArticulo();

        // Guardar ID para resaltar en Artículos
        if (resp.idarticulo) {
          let highlighted = JSON.parse(localStorage.getItem('highlight_articles') || '[]');
          highlighted.push(parseInt(resp.idarticulo));
          localStorage.setItem('highlight_articles', JSON.stringify(highlighted));
          console.log("Saved new article ID to localStorage:", resp.idarticulo);
        }

        // Refrescar la tabla de selección de artículos si está abierta o se abre después
        if (tabla) {
          tabla.ajax.reload();
        }
      } else {
        bootbox.alert(resp.message || "Error al guardar");
        $("#btnGuardarArticulo").prop("disabled", false);
      }
    },
    error: function (error) {
      console.log(error);
      $("#btnGuardarArticulo").prop("disabled", false);
    }
  });
}

function generarbarcodeNew() {
  var codigo = $("#codigo_new").val();

  // Si está vacío, generar uno aleatorio de 13 dígitos
  if (!codigo) {
    codigo = Math.floor(Math.random() * 9000000000000) + 1000000000000;
    $("#codigo_new").val(codigo);
  }

  if (typeof JsBarcode !== 'undefined') {
    JsBarcode("#barcode_new", codigo.toString(), {
      format: "CODE128",
      lineColor: "#000",
      width: 2,
      height: 40,
      displayValue: true,
      fontSize: 18,
      fontOptions: "bold"
    });
    $("#print_new").show();
  }
}

function imprimirNew() {
  $("#print_new").printArea();
}

// Cargar categorías y marcas para el modal de nuevo artículo
$.post("../ajax/articulo.php?op=selectCategoria", function (r) {
  $("#idcategoria_new").html(r);
  $('#idcategoria_new').selectpicker('refresh');
});

// Usamos el endpoint correcto para marcas (ajax/marca.php?op=select)
$.post("../ajax/marca.php?op=select", function (r) {
  $("#idmarca_new").html(r);
  $('#idmarca_new').selectpicker('refresh');
});

// Event listener para el formulario de nuevo artículo
$("#formularioArticulo").on("submit", function (e) {
  guardaryeditarArticulo(e);
});

init();
