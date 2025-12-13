var tabla;
var precioVentaEditadoManualmente = false;

// Constantes de negocio
const IGV = 0.18;
const MARGEN_SUGERIDO = 0.30;

function init() {
  mostrarform(false);
  listar();

  $("#formulario").on("submit", function (e) {
    guardaryeditar(e);
  });

  // Cargar opciones de selects
  $.post("../ajax/articulo.php?op=selectCategoria", function (r) {
    $("#idcategoria").html(r);
    $('#idcategoria').selectpicker('refresh');
  });

  // Cargar opciones de marcas para el formulario
  $.post("../ajax/marca.php?op=select", function (r) {
    $("#idmarca").html(r);
    $('#idmarca').selectpicker('refresh');
  });

  // Cargar opciones de marcas para el filtro (usando nombres)
  $.post("../ajax/articulo.php?op=selectMarca", function (r) {
    $("#filter-marca").html('<option value="">Todas las marcas</option>' + r);
  });

  $("#imagenmuestra").hide();

  // Eventos de filtros
  $("#filter-todos").on("click", function () {
    setFilterActive(this);
    tabla.column(9).search('').draw();
  });
  $("#filter-activos").on("click", function () {
    setFilterActive(this);
    tabla.column(9).search('Activado').draw();
  });
  $("#filter-desactivos").on("click", function () {
    setFilterActive(this);
    tabla.column(9).search('Desactivado').draw();
  });

  $("#search-input").on("keyup", function () {
    tabla.search(this.value).draw();
  });

  $("#page-length-selector").on("change", function () {
    tabla.page.len(this.value).draw();
  });

  // Filtro Categoría (Columna 2)
  $("#filter-categoria").on("change", function () {
    tabla.column(2).search(this.value).draw();
  });

  // Filtro Marca (Columna 3)
  $("#filter-marca").on("change", function () {
    tabla.column(3).search(this.value).draw();
  });


  $("#nombre").on("input", function () {
    const ok = esNombreValido(this.value);
    setValidity(this, ok, "Nombre inválido (min 3 chars)");
  });

  $("#codigo").on("input", function () {
    const ok = esCodigoValido(this.value);
    setValidity(this, ok, "Código inválido (solo números)");
  });

  // Validar precio de venta al perder el foco
  $("#precio_venta").on("blur", function () {
    validarPrecioVenta(this);
  });

  // Eventos de botones en la tabla (Delegación)
  $('#tbllistado tbody').on('click', 'button.btn-edit', function () {
    var idarticulo = $(this).data("id");
    mostrar(idarticulo);
  });

  $('#tbllistado tbody').on('click', 'button.btn-off', function () {
    var idarticulo = $(this).data("id");
    desactivar(idarticulo);
  });

  $('#tbllistado tbody').on('click', 'button.btn-on', function () {
    var idarticulo = $(this).data("id");
    activar(idarticulo);
  });

  // Verificar si venimos de "Listar precio de venta"
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('msg') === 'update_price') {
    Swal.fire({
      title: 'Actualización de Precios',
      text: 'Utiliza el botón de editar (lápiz) en la lista para modificar el precio de venta de tus productos.',
      icon: 'info',
      confirmButtonText: 'Entendido'
    });
    // Limpiar URL
    window.history.replaceState({}, document.title, window.location.pathname);
  }
}

function setFilterActive(element) {
  $(".filter-btn").removeClass("active");
  $(element).addClass("active");
}

function setValidity(input, isValid, msg) {
  if (isValid) {
    $(input).removeClass("is-invalid").addClass("is-valid");
    input.setCustomValidity("");
  } else {
    $(input).removeClass("is-valid").addClass("is-invalid");
    input.setCustomValidity(msg);
  }
}

function esPrecioValido(precio) {
  const p = parseFloat(precio);
  return !isNaN(p) && p >= 0;
}

function esCodigoValido(codigo) {
  if (!codigo) return true;
  return /^[0-9]+$/.test(codigo);
}

function esNombreValido(nombre) {
  return nombre && nombre.length >= 3;
}

// Lógica de Precios
function f2(num) {
  return num.toFixed(2);
}

function calcularPV(compra) {
  const c = parseFloat(String(compra).replace(',', '.'));
  if (isNaN(c) || c <= 0) return "";
  const sugerido = c * (1 + IGV) * (1 + MARGEN_SUGERIDO);
  return f2(sugerido);
}

function actualizarSugerido() {
  const pc = $("#precio_compra").val();
  const sug = calcularPV(pc);

  const hint = document.getElementById('pv_sugerido_hint');
  if (hint) {
    hint.textContent = sug ? `Sugerido: S/ ${sug}` : "Sugerido: —";
  }

  if (sug && !precioVentaEditadoManualmente) {
    $("#precio_venta").val(sug);
  }
}

function validarPrecioVenta(input) {
  const pv = parseFloat(input.value);
  const pc = $("#precio_compra").val();
  const sug = parseFloat(calcularPV(pc));

  if (!isNaN(pv) && !isNaN(sug) && sug > 0) {
    if (pv < sug) {
      mostrarNotificacion("⚠️ El precio de venta NO puede ser menor al sugerido (S/ " + sug + ")", "error");
      input.value = sug.toFixed(2);
      $(input).addClass("is-invalid");
      setTimeout(() => $(input).removeClass("is-invalid"), 2000);
      return false;
    } else {
      setValidity(input, true, "");
      return true;
    }
  } else {
    setValidity(input, true, "");
    return true;
  }
}

function limpiar() {
  $("#idarticulo").val("");
  $("#nombre").val("");
  $("#descripcion").val("");
  $("#codigo").val("");
  $("#stock").val("0");
  $("#precio_compra").val("0.00");
  $("#precio_venta").val("0.00");
  $("#imagenmuestra").attr("src", "").hide();
  $("#imagenactual").val("");
  $("#print").hide();
  $(".form-control").removeClass("is-valid is-invalid");

  // Resetear estados de campos de precio
  $("#precio_compra").prop("readonly", true);
  $("#precio_venta").prop("disabled", true);

  // Ocultar campos de precio al agregar (se muestran solo en editar)
  $("#precio_compra").closest(".form-group").hide();
  $("#precio_venta").closest(".form-group").hide();
  $("#precio_compra").prop("required", false);
  $("#precio_venta").prop("required", false);
}

function mostrarform(flag) {
  limpiar();
  if (flag) {
    $("#listadoregistros").hide();
    $("#formularioregistros").show();
    $("#btnGuardar").prop("disabled", false);
    $("#btnagregar").hide();
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

function listar() {
  tabla = $('#tbllistado').DataTable({
    "aProcessing": true,
    "aServerSide": true,
    "dom": 'rtip',
    "ajax": {
      url: '../ajax/articulo.php?op=listar',
      type: "get",
      dataType: "json",
      error: function (e) {
        console.log(e.responseText);
      }
    },
    "bDestroy": true,
    "iDisplayLength": 10,
    "order": [[10, "desc"]], // Ordenar por ID (columna oculta)
    "columnDefs": [
      { "targets": [10], "visible": false, "searchable": false }
    ],
    "language": {
      "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
    },
    "drawCallback": function (settings) {
      let highlighted = JSON.parse(localStorage.getItem('highlight_articles') || '[]');
      console.log("Highlighted IDs from localStorage:", highlighted);

      if (highlighted.length > 0) {
        let api = this.api();
        let found = false;
        api.rows().every(function () {
          let data = this.data();
          // ID is now in column 10 (index 10)
          let id = parseInt(data[10]);

          if (highlighted.includes(id)) {
            console.log("Highlighting row for ID:", id);
            $(this.node()).addClass('table-success');
            // Add a custom class for stronger highlighting if needed
            $(this.node()).css('background-color', '#d1e7dd');
            found = true;
          }
        });

        if (found) {
          setTimeout(() => {
            localStorage.removeItem('highlight_articles');
            // Remove inline styles after timeout
            $('#tbllistado tbody tr.table-success').css('background-color', '');
            $('#tbllistado tbody tr').removeClass('table-success');
          }, 5000);
        }
      }
    }
  });
}

// ==================== FUNCIÓN EXPORTAR TABLA ====================
function exportarTabla(tipo) {
  // Obtener datos de la tabla
  var data = [];
  var headers = ['Nombre', 'Categoría', 'Marca', 'Código', 'Stock', 'Precio Compra', 'Precio Venta', 'Estado'];

  // Obtener todas las filas visibles de la tabla
  $('#tbllistado tbody tr').each(function () {
    var row = [];
    row.push($(this).find('td:eq(1)').text().trim()); // Nombre
    row.push($(this).find('td:eq(2)').text().trim()); // Categoría
    row.push($(this).find('td:eq(3)').text().trim()); // Marca
    row.push($(this).find('td:eq(4)').text().trim()); // Código
    row.push($(this).find('td:eq(5)').text().trim()); // Stock
    row.push($(this).find('td:eq(6)').text().trim()); // Precio Compra
    row.push($(this).find('td:eq(7)').text().trim()); // Precio Venta
    row.push($(this).find('td:eq(9)').text().trim()); // Estado
    data.push(row);
  });

  if (data.length === 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Sin datos',
      text: 'No hay datos para exportar',
      timer: 2000
    });
    return;
  }

  switch (tipo) {
    case 'copy':
      copiarAlPortapapeles(headers, data);
      break;
    case 'excel':
      exportarExcel(headers, data);
      break;
    case 'csv':
      exportarCSV(headers, data);
      break;
    case 'pdf':
      exportarPDF(headers, data);
      break;
  }
}

function copiarAlPortapapeles(headers, data) {
  var texto = headers.join('\t') + '\n';
  data.forEach(function (row) {
    texto += row.join('\t') + '\n';
  });

  navigator.clipboard.writeText(texto).then(function () {
    Swal.fire({
      icon: 'success',
      title: '¡Copiado!',
      text: data.length + ' registros copiados al portapapeles',
      timer: 2000,
      showConfirmButton: false
    });
  }).catch(function (err) {
    // Fallback para navegadores antiguos
    var textarea = document.createElement('textarea');
    textarea.value = texto;
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    document.body.removeChild(textarea);
    Swal.fire({
      icon: 'success',
      title: '¡Copiado!',
      text: data.length + ' registros copiados al portapapeles',
      timer: 2000,
      showConfirmButton: false
    });
  });
}

function exportarCSV(headers, data) {
  var csv = '\uFEFF'; // BOM UTF-8
  csv += headers.join(';') + '\n';
  data.forEach(function (row) {
    csv += row.map(function (cell) {
      // Escapar comillas y envolver en comillas si contiene separador
      if (cell.includes(';') || cell.includes('"') || cell.includes('\n')) {
        return '"' + cell.replace(/"/g, '""') + '"';
      }
      return cell;
    }).join(';') + '\n';
  });

  var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  var link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = 'Articulos_' + new Date().toISOString().slice(0, 10) + '.csv';
  link.click();

  Swal.fire({
    icon: 'success',
    title: '¡Exportado!',
    text: 'Archivo CSV descargado correctamente',
    timer: 2000,
    showConfirmButton: false
  });
}

function exportarExcel(headers, data) {
  // Crear tabla HTML para Excel
  var html = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
  html += '<head><meta charset="UTF-8"><style>';
  html += 'table { border-collapse: collapse; width: 100%; }';
  html += 'th { background: #1565c0; color: white; padding: 10px; border: 1px solid #ccc; font-weight: bold; }';
  html += 'td { padding: 8px; border: 1px solid #ccc; }';
  html += 'tr:nth-child(even) { background: #f5f5f5; }';
  html += '</style></head><body>';
  html += '<table>';

  // Headers
  html += '<tr>';
  headers.forEach(function (h) {
    html += '<th>' + h + '</th>';
  });
  html += '</tr>';

  // Data
  data.forEach(function (row) {
    html += '<tr>';
    row.forEach(function (cell) {
      html += '<td>' + cell + '</td>';
    });
    html += '</tr>';
  });

  html += '</table></body></html>';

  var blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8;' });
  var link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = 'Articulos_' + new Date().toISOString().slice(0, 10) + '.xls';
  link.click();

  Swal.fire({
    icon: 'success',
    title: '¡Exportado!',
    text: 'Archivo Excel descargado correctamente',
    timer: 2000,
    showConfirmButton: false
  });
}

function exportarPDF(headers, data) {
  // Verificar si pdfmake está disponible
  if (typeof pdfMake === 'undefined') {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'La librería PDF no está cargada correctamente',
      timer: 3000
    });
    return;
  }

  // Crear documento PDF
  var body = [];

  // Headers
  body.push(headers.map(function (h) {
    return { text: h, style: 'tableHeader' };
  }));

  // Data
  data.forEach(function (row) {
    body.push(row.map(function (cell) {
      return { text: cell, style: 'tableCell' };
    }));
  });

  var docDefinition = {
    pageOrientation: 'landscape',
    pageSize: 'A4',
    content: [
      { text: 'Listado de Artículos - ERP Autopartes', style: 'header' },
      { text: 'Fecha: ' + new Date().toLocaleDateString('es-PE'), style: 'subheader' },
      { text: ' ' },
      {
        table: {
          headerRows: 1,
          widths: ['*', 'auto', 'auto', 'auto', 'auto', 'auto', 'auto', 'auto'],
          body: body
        },
        layout: {
          fillColor: function (rowIndex) {
            return (rowIndex === 0) ? '#1565c0' : (rowIndex % 2 === 0) ? '#f5f5f5' : null;
          }
        }
      }
    ],
    styles: {
      header: {
        fontSize: 18,
        bold: true,
        color: '#1565c0',
        margin: [0, 0, 0, 10]
      },
      subheader: {
        fontSize: 10,
        color: '#666',
        margin: [0, 0, 0, 10]
      },
      tableHeader: {
        bold: true,
        fontSize: 9,
        color: 'white',
        fillColor: '#1565c0',
        margin: [2, 4, 2, 4]
      },
      tableCell: {
        fontSize: 8,
        margin: [2, 3, 2, 3]
      }
    }
  };

  pdfMake.createPdf(docDefinition).download('Articulos_' + new Date().toISOString().slice(0, 10) + '.pdf');

  Swal.fire({
    icon: 'success',
    title: '¡Exportado!',
    text: 'Archivo PDF descargado correctamente',
    timer: 2000,
    showConfirmButton: false
  });
}

function guardaryeditar(e) {
  e.preventDefault();
  $("#btnGuardar").prop("disabled", true);

  // --- 1. VALIDACIÓN DE CAMPOS OBLIGATORIOS ---
  var nombre = $("#nombre").val().trim();
  var idcategoria = $("#idcategoria").val();
  var idmarca = $("#idmarca").val();
  var codigo = $("#codigo").val().trim();

  if (nombre.length == 0 || idcategoria == "" || idcategoria == null || idmarca == "" || idmarca == null || codigo.length == 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Campos incompletos',
      text: 'Por favor, complete todos los campos obligatorios: Nombre, Categoría, Marca y Código.',
      confirmButtonColor: '#f39c12'
    });
    $("#btnGuardar").prop("disabled", false);
    return;
  }

  // --- 2. VALIDACIÓN ESTRICTA DE PRECIO DE VENTA ---
  // Solo si estamos en modo edición (donde el precio venta es visible/editable)
  if ($("#idarticulo").val() !== "") {
    const pv = parseFloat($("#precio_venta").val());
    const pc = $("#precio_compra").val();
    const sug = parseFloat(calcularPV(pc));

    if (!isNaN(pv) && !isNaN(sug) && sug > 0) {
      if (pv < sug) {
        Swal.fire({
          icon: 'error',
          title: 'Precio de Venta Inválido',
          html: `El precio de venta (<b>S/ ${pv.toFixed(2)}</b>) no puede ser menor al sugerido (<b>S/ ${sug.toFixed(2)}</b>).<br>Se ha restablecido al valor sugerido.`,
          confirmButtonColor: '#d33'
        });

        $("#precio_venta").val(sug.toFixed(2)); // Corregir automáticamente
        $("#precio_venta").addClass("is-invalid");
        $("#btnGuardar").prop("disabled", false);
        return; // DETENER GUARDADO
      }
    }
  }
  // ----------------------------------------------

  var formData = new FormData($("#formulario")[0]);

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
        Swal.fire({
          icon: 'success',
          title: '¡Éxito!',
          text: resp.message,
          timer: 2000,
          showConfirmButton: false
        });
        mostrarform(false);
        tabla.ajax.reload();
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: resp.message || "Error al guardar",
          confirmButtonColor: '#d33'
        });
      }
      $("#btnGuardar").prop("disabled", false);
    },
    error: function () {
      Swal.fire({
        icon: 'error',
        title: 'Error de conexión',
        text: 'No se pudo comunicar con el servidor.',
        confirmButtonColor: '#d33'
      });
      $("#btnGuardar").prop("disabled", false);
    }
  });
}

function mostrar(idarticulo) {
  $.post("../ajax/articulo.php?op=mostrar", { idarticulo: idarticulo }, function (data, status) {
    try {
      // Si jQuery ya lo parseó, data es un objeto. Si no, es string.
      if (typeof data === 'string') {
        data = JSON.parse(data);
      }

      mostrarform(true);

      // Mostrar campos de precio en Edición
      $("#precio_compra").closest(".form-group").show();
      $("#precio_venta").closest(".form-group").show();
      $("#precio_compra").prop("required", true);
      $("#precio_venta").prop("required", true);

      $("#idcategoria").val(data.idcategoria);
      $('#idcategoria').selectpicker('refresh');

      $("#idmarca").val(data.idmarca);
      $('#idmarca').selectpicker('refresh');

      $("#codigo").val(data.codigo);
      $("#nombre").val(data.nombre);
      $("#stock").val(data.stock);
      $("#precio_compra").val(data.precio_compra);
      $("#precio_venta").val(data.precio_venta);
      $("#descripcion").val(data.descripcion);
      $("#idarticulo").val(data.idarticulo);

      $("#imagenmuestra").show();
      $("#imagenmuestra").attr("src", "../files/articulos/" + data.imagen);
      $("#imagenactual").val(data.imagen);

      // Permitir editar precio compra
      $("#precio_compra").prop("readonly", true);

      // Habilitar precio venta solo si el precio de compra es mayor a 0
      if (parseFloat(data.precio_compra) > 0) {
        $("#precio_venta").prop("disabled", false);
      } else {
        $("#precio_venta").prop("disabled", true);
      }

      try { generarbarcode(); } catch (e) { console.log("Error barcode", e); }
      try { actualizarSugerido(); } catch (e) { console.log("Error sugerido", e); }

      precioVentaEditadoManualmente = true;
    } catch (e) {
      console.error("Error parsing data:", e);
      mostrarNotificacion("Error al cargar datos del artículo", "error");
    }
  });
}

function desactivar(idarticulo) {
  Swal.fire({
    title: '¿Desactivar artículo?',
    text: "El artículo no aparecerá en ventas activas.",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Sí, desactivar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      $.post("../ajax/articulo.php?op=desactivar", { idarticulo: idarticulo }, function (e) {
        let resp;
        try {
          resp = (typeof e === 'string') ? JSON.parse(e) : e;
        } catch (x) {
          resp = { success: false, message: e };
        }

        if (resp.success) {
          Swal.fire('Desactivado', resp.message, 'success');
          tabla.ajax.reload();
        } else {
          Swal.fire('Error', resp.message, 'error');
        }
      });
    }
  });
}

function activar(idarticulo) {
  Swal.fire({
    title: '¿Activar artículo?',
    text: "El artículo volverá a estar disponible.",
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#059669',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Sí, activar',
    cancelButtonText: 'Cancelar'
  }).then((result) => {
    if (result.isConfirmed) {
      $.post("../ajax/articulo.php?op=activar", { idarticulo: idarticulo }, function (e) {
        let resp;
        try {
          resp = (typeof e === 'string') ? JSON.parse(e) : e;
        } catch (x) {
          resp = { success: false, message: e };
        }

        if (resp.success) {
          Swal.fire('Activado', resp.message, 'success');
          tabla.ajax.reload();
        } else {
          Swal.fire('Error', resp.message, 'error');
        }
      });
    }
  });
}

function generarbarcode() {
  let codigo = $("#codigo").val();

  // Si está vacío, generar uno aleatorio de 13 dígitos
  if (!codigo) {
    codigo = Math.floor(Math.random() * 9000000000000) + 1000000000000;
    $("#codigo").val(codigo);
  }

  if (typeof JsBarcode !== 'undefined') {
    JsBarcode("#barcode", codigo.toString(), {
      format: "CODE128",
      lineColor: "#000",
      width: 2,
      height: 40,
      displayValue: true,
      fontSize: 18,
      fontOptions: "bold"
    });
    $("#print").show();
  }
}

function imprimir() {
  $("#print").printArea();
}

function mostrarNotificacion(mensaje, tipo) {
  // Usamos SweetAlert Toast para notificaciones también, para consistencia
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

function mostrarStockBajo() {
  Swal.fire({
    title: 'Cargando...',
    didOpen: () => { Swal.showLoading() }
  });

  $.get("../ajax/articulo.php?op=articulos_stock_bajo", function (data) {
    Swal.close();
    let resp = (typeof data === 'string') ? JSON.parse(data) : data;

    if (resp.success && resp.articulos.length > 0) {
      let lista = '<ul style="text-align:left; max-height:300px; overflow-y:auto; padding-left:20px;">';
      resp.articulos.forEach(item => {
        lista += `<li>${item}</li>`;
      });
      lista += '</ul>';

      Swal.fire({
        title: 'Artículos con Stock Bajo',
        html: lista,
        icon: 'warning',
        confirmButtonText: 'Cerrar'
      });
    } else {
      Swal.fire('Información', 'No hay artículos con stock bajo.', 'info');
    }
  });
}

function mostrarSinStock() {
  Swal.fire({
    title: 'Cargando...',
    didOpen: () => { Swal.showLoading() }
  });

  $.get("../ajax/articulo.php?op=articulos_sin_stock", function (data) {
    Swal.close();
    let resp = (typeof data === 'string') ? JSON.parse(data) : data;

    if (resp.success && resp.articulos.length > 0) {
      let lista = '<ul style="text-align:left; max-height:300px; overflow-y:auto; padding-left:20px;">';
      resp.articulos.forEach(item => {
        lista += `<li>${item}</li>`;
      });
      lista += '</ul>';

      Swal.fire({
        title: 'Artículos sin Stock',
        html: lista,
        icon: 'error',
        confirmButtonText: 'Cerrar'
      });
    } else {
      Swal.fire('Información', 'No hay artículos sin stock.', 'info');
    }
  });
}

function mostrarHistorial() {
  var idarticulo = $("#idarticulo").val();
  if (!idarticulo) {
    mostrarNotificacion("Guarde el artículo antes de ver su historial", "info");
    return;
  }

  $("#modalHistorial").modal("show");

  // Initialize or reload DataTable
  if ($.fn.DataTable.isDataTable('#tblhistorial')) {
    $('#tblhistorial').DataTable().ajax.url('../ajax/historial_precios.php?op=listar_movimientos&idarticulo=' + idarticulo).load();
  } else {
    $('#tblhistorial').DataTable({
      "aProcessing": true,
      "aServerSide": true,
      "dom": 'Bfrtip',
      "buttons": [
        'copyHtml5',
        'excelHtml5',
        'csvHtml5',
        'pdf'
      ],
      "ajax": {
        url: '../ajax/historial_precios.php?op=listar_movimientos&idarticulo=' + idarticulo,
        type: "get",
        dataType: "json",
        error: function (e) {
          console.log(e.responseText);
        }
      },
      "bDestroy": true,
      "iDisplayLength": 10,
      "order": [[8, "desc"]], // Order by date (column 8)
      "language": {
        "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json"
      }
    });
  }
}

// ==================== FUNCIÓN MOSTRAR DETALLE KPI ====================
function mostrarDetalleKPI(tipo) {
  // Mostrar loading
  Swal.fire({
    title: 'Cargando información...',
    html: '<div style="padding: 20px;"><i class="fa fa-spinner fa-spin fa-3x" style="color: #1565c0;"></i></div>',
    showConfirmButton: false,
    allowOutsideClick: false
  });

  $.ajax({
    url: '../ajax/articulo.php?op=kpi_detalle&tipo=' + tipo,
    type: 'GET',
    dataType: 'json',
    success: function (resp) {
      Swal.close();

      if (resp.success && resp.datos.length > 0) {
        // Construir tabla HTML
        var tablaHtml = '<div style="max-height: 400px; overflow-y: auto;">';
        tablaHtml += '<p style="color: #64748b; margin-bottom: 12px; font-size: 0.9rem;">' + resp.descripcion + '</p>';
        tablaHtml += '<table class="table table-striped table-bordered" style="font-size: 0.85rem; width: 100%;">';

        // Headers
        tablaHtml += '<thead style="background: #1e293b; color: white;"><tr>';
        resp.columnas.forEach(function (col) {
          tablaHtml += '<th style="padding: 8px; text-align: center;">' + col + '</th>';
        });
        tablaHtml += '</tr></thead>';

        // Body
        tablaHtml += '<tbody>';
        resp.datos.forEach(function (row, idx) {
          var bgColor = idx % 2 === 0 ? '#fff' : '#f8fafc';
          tablaHtml += '<tr style="background: ' + bgColor + ';">';
          Object.values(row).forEach(function (val) {
            // Colorear stock bajo en rojo
            var style = 'padding: 6px 8px;';
            if (typeof val === 'number' && val <= 0) {
              style += 'color: #dc2626; font-weight: bold;';
            }
            tablaHtml += '<td style="' + style + '">' + (val !== null ? val : '-') + '</td>';
          });
          tablaHtml += '</tr>';
        });
        tablaHtml += '</tbody></table></div>';

        // Mostrar total si hay más registros
        if (resp.datos.length >= 50) {
          tablaHtml += '<p style="color: #94a3b8; font-size: 0.8rem; margin-top: 10px; text-align: center;">Mostrando los primeros 50 registros</p>';
        }

        Swal.fire({
          title: '<i class="fa fa-chart-bar" style="color: #1565c0; margin-right: 8px;"></i>' + resp.titulo,
          html: tablaHtml,
          width: '800px',
          showCloseButton: true,
          showConfirmButton: false,
          customClass: {
            popup: 'swal-kpi-popup'
          }
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
          text: resp.mensaje || 'No se pudo cargar la información',
          timer: 3000
        });
      }
    },
    error: function (xhr, status, error) {
      Swal.fire({
        icon: 'error',
        title: 'Error de conexión',
        text: 'No se pudo conectar con el servidor',
        timer: 3000
      });
    }
  });
}

init();