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
    "buttons": [],
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

init();