/* vistas/scripts/cliente.js
 * Gestión de Clientes con autocompletado RENIEC (solo DNI)
 * Requiere: jQuery, DataTables, Buttons, bootbox, selectpicker
 */

var tabla;

/* ======================== Utilidades ======================== */
function debounce(fn, wait) {
  let t;
  return function () {
    clearTimeout(t);
    const ctx = this, args = arguments;
    t = setTimeout(function () { fn.apply(ctx, args); }, wait || 350);
  };
}

function setEstado(msg, kind) {
  let color = (kind === 'ok') ? 'green'
    : (kind === 'err' ? '#b91c1c'
      : '#374151'); // slate-700
  $("#estadoDoc").remove();
  $("#num_documento").closest('.input-group, .form-group').after(
    '<small id="estadoDoc" style="display:block;margin-top:6px;color:' + color + ';font-weight:600;">' + msg + '</small>'
  );
}

function bloquearCamposFijos() {
  // Nombre y Dirección solo los trae RENIEC
  $("#nombre").prop("readonly", true).addClass("disabled");
  $("#direccion").prop("readonly", true).addClass("disabled");
}
function desbloquearCamposFijos() {
  $("#nombre").prop("readonly", false).removeClass("disabled");
  $("#direccion").prop("readonly", false).removeClass("disabled");
}

/* ======================== Toast (mismo estilo que Ventas) ======================== */
function ensureToastStyles() {
  if (document.getElementById('neko-toast-styles')) return;
  const css = `
    #nekoToastContainer{
      position:fixed; right:18px; bottom:18px; z-index:9999;
      display:flex; flex-direction:column; gap:8px; pointer-events:none;
    }
    .neko-toast{
      min-width:260px; max-width:360px; background:#0f172a; color:#f9fafb;
      padding:10px 12px; border-radius:10px; box-shadow:0 10px 25px rgba(15,23,42,.5);
      display:flex; align-items:flex-start; gap:8px; font-size:.86rem;
      pointer-events:auto; opacity:0.96;
    }
    .neko-toast-success{border-left:4px solid #22c55e;}
    .neko-toast-error{border-left:4px solid #ef4444;}
    .neko-toast-info{border-left:4px solid #3b82f6;}
    .neko-toast-icon{ font-size:1rem; margin-top:1px; }
    .neko-toast-close{ margin-left:auto; cursor:pointer; opacity:.7; }
    .neko-toast-close:hover{opacity:1;}
  `;
  const style = document.createElement('style');
  style.id = 'neko-toast-styles';
  style.textContent = css;
  document.head.appendChild(style);
}

function showToast(type, message) {
  ensureToastStyles();
  let container = document.getElementById('nekoToastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'nekoToastContainer';
    document.body.appendChild(container);
  }

  let icon = 'ℹ️', cls = 'neko-toast-info';
  if (type === 'success') { icon = '✅'; cls = 'neko-toast-success'; }
  else if (type === 'error') { icon = '⚠️'; cls = 'neko-toast-error'; }

  const toast = document.createElement('div');
  toast.className = 'neko-toast ' + cls;
  toast.innerHTML = `
    <span class="neko-toast-icon">${icon}</span>
    <div>${message}</div>
    <span class="neko-toast-close">&times;</span>
  `;

  toast.querySelector('.neko-toast-close').onclick = function () {
    $(toast).fadeOut(150, function () { toast.remove(); });
  };

  container.appendChild(toast);
  $(toast).hide().fadeIn(150);

  setTimeout(function () {
    $(toast).fadeOut(200, function () { toast.remove(); });
  }, 3500);
}

/* ======================== Filtros Modernos ======================== */
// 1. Filtro de Estado (Regex)
function filtrarEstado(estado) {
  $('.status-btn').removeClass('active');
  $('#filter-' + estado).addClass('active');

  // Columna 6: Estado (según ajax/persona.php)
  if (estado === 'todos') {
    tabla.column(6).search('').draw();
  } else if (estado === 'activos') {
    tabla.column(6).search('^Activado$', true, false).draw();
  } else if (estado === 'inactivos') {
    tabla.column(6).search('^Desactivado$', true, false).draw();
  }
}

// 2. Buscador Global
function setupSearchInput() {
  $('#search-input').on('keyup', function () {
    tabla.search(this.value).draw();
  });
}

// 3. Cambiar Longitud
function cambiarLongitud(len) {
  tabla.page.len(len).draw();
}

// 4. Exportar
function exportarTabla(type) {
  if (type === 'excel') $('.buttons-excel').click();
  if (type === 'pdf') $('.buttons-pdf').click();
  if (type === 'csv') $('.buttons-csv').click();
  if (type === 'copy') $('.buttons-copy').click();
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
    url: '../ajax/persona.php?op=kpi_detalle&tipo=' + tipo + '&persona=Cliente',
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
            var style = 'padding: 6px 8px;';
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
          title: '<i class="fa fa-users" style="color: #1565c0; margin-right: 8px;"></i>' + resp.titulo,
          html: tablaHtml,
          width: '750px',
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

/* ======================== Boot ======================== */
function init() {
  mostrarform(false);
  listar();
  setupSearchInput();

  // Guardar
  $("#formulario").on("submit", function (e) { guardaryeditar(e); });

  // Menú activo
  $('#mVentas').addClass("treeview active");
  $('#lClientes').addClass("active");

  // Tipo persona fijo = Cliente
  $("#tipo_persona").val("Cliente");

  // Init Select y lógica de cambio
  try { $("#tipo_documento_view").selectpicker('refresh'); } catch (e) { }

  $("#tipo_documento_view").off("change").on("change", function () {
    let val = $(this).val();
    $("#tipo_documento_hidden").val(val);

    // Limpiar datos previos
    $("#num_documento").val("");
    $("#nombre").val("");
    $("#direccion").val("");
    setEstado("Esperando número...", "info");

    if (val === "RUC") {
      $("#num_documento").attr("maxlength", "11").attr("placeholder", "RUC (11 dígitos)").attr("pattern", ".{11,11}");
      $("#wrap-numdoc small").text("RUC (11 dígitos)");
    } else {
      $("#num_documento").attr("maxlength", "8").attr("placeholder", "DNI (8 dígitos)").attr("pattern", ".{8,8}");
      $("#wrap-numdoc small").text("DNI (8 dígitos)");
    }
    bloquearCamposFijos();

    // Fix visual: refrescar selectpicker para asegurar que muestre la opción seleccionada
    try { $(this).selectpicker('refresh'); } catch (e) { }
  });

  // Handlers Documento (Genérico)
  $("#num_documento")
    .on("input", function () {
      let tipo = $("#tipo_documento_view").val();
      let max = (tipo === "RUC") ? 11 : 8;
      // Solo dígitos y longitud variable
      let v = (this.value || "").replace(/\D/g, '').slice(0, max);
      if (this.value !== v) this.value = v;
    })
    .on("keyup change", debounce(onDocChange, 400));

  // Teléfono: solo dígitos, máx 9 (celular peruano)
  $("#telefono").on("input", function () {
    let v = (this.value || "").replace(/\D/g, '').slice(0, 9);
    if (this.value !== v) this.value = v;
  });

  // Botón buscar (consulta manual)
  $("#btnBuscarDoc").off("click").on("click", function () {
    const doc = ($("#num_documento").val() || "").replace(/\D/g, '');
    let tipo = $("#tipo_documento_view").val();

    if (tipo === "RUC") {
      if (doc.length === 11) consultarSunat(doc);
      else setEstado("El RUC debe tener 11 dígitos", "err");
    } else {
      if (doc.length === 8) consultarReniec(doc);
      else setEstado("El DNI debe tener 8 dígitos", "err");
    }
  });

  // Estado inicial
  setEstado("Esperando número…", "info");
  bloquearCamposFijos();

  // Trigger cambio inicial para setear placeholders
  $("#tipo_documento_view").trigger("change");
}

/* ======================== Limpieza/Form ======================== */
function limpiar() {
  $("#idpersona").val("");
  $("#nombre").val("");
  $("#num_documento").val("");
  $("#telefono").val("");
  $("#email").val("");
  $("#direccion").val("");

  $("#tipo_persona").val("Cliente");
  $("#tipo_documento_view").val("DNI");
  $("#tipo_documento_hidden").val("DNI");
  try { $("#tipo_documento_view").selectpicker('refresh'); } catch (e) { }

  setEstado("Esperando número…", "info");
  bloquearCamposFijos();
}

function mostrarform(flag) {
  limpiar();
  if (flag) {
    $("#listadoregistros").hide();
    $("#formularioregistros").show();
    $("#btnGuardar").prop("disabled", false);
    $("#btnagregar").hide();
    // Ocultar barra de filtros al editar
    $(".filter-bar").hide();
  } else {
    $("#listadoregistros").show();
    $("#formularioregistros").hide();
    $("#btnagregar").show();
    // Mostrar barra de filtros al listar
    $(".filter-bar").show();
  }
}

function cancelarform() {
  limpiar();
  mostrarform(false);
}

/* ======================== Listado ======================== */
function listar() {
  tabla = $('#tbllistado').dataTable({
    lengthMenu: [5, 10, 25, 75, 100],
    aProcessing: true,
    aServerSide: true,
    dom: 'Bfrtip',
    buttons: ['copyHtml5', 'excelHtml5', 'csvHtml5', 'pdf'],
    ajax: {
      url: '../ajax/persona.php?op=listarc',
      type: 'GET',
      dataType: 'json',
      error: function (e) { console.log(e.responseText); }
    },
    language: {
      lengthMenu: 'Mostrar : _MENU_ registros',
      buttons: {
        copyTitle: 'Tabla Copiada',
        copySuccess: { _: '%d líneas copiadas', 1: '1 línea copiada' }
      }
    },
    bDestroy: true,
    iDisplayLength: 10, // Default 10
    order: [[0, 'desc']]
  }).DataTable();
}

/* ======================== Guardar/Editar ======================== */
function guardaryeditar(e) {
  e.preventDefault();
  $("#btnGuardar").prop("disabled", true);

  // -------- VALIDACIONES FRONT --------
  const doc = ($("#num_documento").val() || "").replace(/\D/g, '');
  const tipoDoc = $("#tipo_documento_view").val();
  const tel = ($("#telefono").val() || "").trim();
  const mail = ($("#email").val() || "").trim();

  // Validar longitud según tipo
  if (tipoDoc === "DNI" && doc.length !== 8) {
    showToast('error', 'El DNI debe tener exactamente 8 dígitos.');
    $("#btnGuardar").prop("disabled", false);
    return;
  }
  if (tipoDoc === "RUC" && doc.length !== 11) {
    showToast('error', 'El RUC debe tener exactamente 11 dígitos.');
    $("#btnGuardar").prop("disabled", false);
    return;
  }

  // Al menos un contacto (teléfono o email)
  if (!tel && !mail) {
    showToast('error', 'Debes registrar al menos un dato de contacto: teléfono o email.');
    $("#btnGuardar").prop("disabled", false);
    return;
  }

  // Validar teléfono si viene (celular peruano: 9 dígitos y empieza con 9)
  if (tel) {
    const telDigits = tel.replace(/\D/g, '');
    if (!/^9\d{8}$/.test(telDigits)) {
      showToast('error', 'El celular debe tener 9 dígitos y empezar con 9 (formato peruano).');
      $("#btnGuardar").prop("disabled", false);
      return;
    }
    $("#telefono").val(telDigits); // Guardamos solo dígitos
  }

  // Validar email si viene
  if (mail) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(mail)) {
      showToast('error', 'El correo electrónico no tiene un formato válido.');
      $("#btnGuardar").prop("disabled", false);
      return;
    }
  }

  // -------- PREPARAR Y ENVIAR --------
  $("#tipo_persona").val("Cliente");
  $("#tipo_documento_hidden").val($("#tipo_documento_view").val() || 'DNI');
  bloquearCamposFijos(); // por si acaso

  var formData = new FormData($("#formulario")[0]);
  $.ajax({
    url: "../ajax/persona.php?op=guardaryeditar",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function (datos) {
      const raw = (datos || '').toString();

      if (/Duplicate entry/i.test(raw)) {
        showToast('error', 'Esta persona ya es un cliente de Neko SAC.');
        $("#btnGuardar").prop("disabled", false);
        return;
      }

      let ok = true;
      let msg = '';

      try {
        const resp = JSON.parse(raw);
        ok = !!resp.success;
        msg = resp.message || (ok ? 'Cliente registrado correctamente.' : 'No se pudo registrar el cliente.');
      } catch (e) {
        msg = raw.trim();
        if (!msg) {
          msg = 'Cliente registrado correctamente.';
          ok = true;
        } else {
          ok = !/error/i.test(msg);
        }
      }

      showToast(ok ? 'success' : 'error', msg);

      if (ok) {
        mostrarform(false);
        if (tabla && tabla.ajax && typeof tabla.ajax.reload === 'function') {
          tabla.ajax.reload();
        }
      }
    },
    error: function (xhr) {
      showToast('error', 'Ocurrió un error al guardar el cliente.');
    },
    complete: function () {
      $("#btnGuardar").prop("disabled", false);
    }
  });
}

/* ======================== Mostrar/Eliminar ======================== */
function mostrar(idpersona) {
  $.post("../ajax/persona.php?op=mostrar", { idpersona: idpersona }, function (data) {
    data = JSON.parse(data);
    mostrarform(true);

    $("#idpersona").val(data.idpersona);
    $("#nombre").val(data.nombre);

    $("#tipo_persona").val("Cliente");

    // Detectar Tipo
    let num = (data.num_documento || '').replace(/\D/g, '');
    let tipo = (num.length > 8) ? "RUC" : "DNI";

    $("#tipo_documento_view").val(tipo);
    $("#tipo_documento_hidden").val(tipo);
    try { $("#tipo_documento_view").selectpicker('refresh'); } catch (e) { }

    $("#num_documento").val(num);

    // Ajustar UI sin disparar change completo (que borra datos)
    if (tipo === "RUC") {
      $("#num_documento").attr("maxlength", "11").attr("placeholder", "RUC (11 dígitos)").attr("pattern", ".{11,11}");
    } else {
      $("#num_documento").attr("maxlength", "8").attr("placeholder", "DNI (8 dígitos)").attr("pattern", ".{8,8}");
    }

    $("#telefono").val(data.telefono);
    $("#email").val(data.email);
    $("#direccion").val(data.direccion || '');

    bloquearCamposFijos();
    setEstado("Datos cargados (edición)", "info");
  });
}

function desactivar(idpersona) {
  bootbox.confirm("¿Está Seguro de desactivar el cliente?", function (result) {
    if (result) {
      $.post("../ajax/persona.php?op=desactivar", { idpersona: idpersona }, function (e) {
        showToast('info', e);
        tabla.ajax.reload();
      });
    }
  });
}

function activar(idpersona) {
  bootbox.confirm("¿Está Seguro de activar el cliente?", function (result) {
    if (result) {
      $.post("../ajax/persona.php?op=activar", { idpersona: idpersona }, function (e) {
        showToast('success', e);
        tabla.ajax.reload();
      });
    }
  });
}

/* ======================== CONSULTAS RENIEC / SUNAT ======================== */
function onDocChange() {
  const doc = ($("#num_documento").val() || "").replace(/\D/g, '');
  const tipo = $("#tipo_documento_view").val();

  if (tipo === "DNI") {
    if (doc.length === 8) consultarReniec(doc);
    else {
      $("#nombre").val("");
      $("#direccion").val("");
      setEstado("Esperando DNI...", "info");
    }
  } else if (tipo === "RUC") {
    if (doc.length === 11) consultarSunat(doc);
    else {
      $("#nombre").val("");
      $("#direccion").val("");
      setEstado("Esperando RUC...", "info");
    }
  }
}

function consultarReniec(dni) {
  loading(true);

  $.ajax({
    url: "../ajax/reniec.php",
    type: "GET",
    dataType: "json",
    cache: false,
    data: { dni: dni, _: Date.now() },
    success: function (resp) {
      if (resp && resp.success) {
        const nombre = [resp.nombres || '', resp.apellidos || ''].join(' ').trim();
        $("#nombre").val(nombre);
        $("#direccion").val(resp.direccion || '');
        bloquearCamposFijos();
        setEstado("Datos encontrados (RENIEC)", "ok");
      } else {
        $("#nombre").val('');
        $("#direccion").val('');
        desbloquearCamposFijos(); // Permitir manual
        setEstado("DNI no encontrado. Ingrese manual.", "err");
      }
    },
    error: function () {
      desbloquearCamposFijos();
      setEstado("Error consulta. Ingrese manual.", "err");
    },
    complete: function () {
      loading(false);
    }
  });
}

function consultarSunat(ruc) {
  loading(true);

  $.ajax({
    url: "../ajax/sunat.php",
    type: "GET",
    dataType: "json",
    cache: false,
    data: { ruc: ruc, _: Date.now() },
    success: function (resp) {
      if (resp && resp.success) {
        // Normalizar respuesta
        let nombre = resp.razon_social || resp.razonSocial || '';
        let direccion = resp.direccion || '';

        $("#nombre").val(nombre);
        $("#direccion").val(direccion);
        bloquearCamposFijos();
        setEstado("Datos encontrados (SUNAT)", "ok");
      } else {
        $("#nombre").val('');
        $("#direccion").val('');
        desbloquearCamposFijos();
        setEstado(resp.message || "RUC no encontrado. Ingrese manual.", "err");
      }
    },
    error: function () {
      desbloquearCamposFijos();
      setEstado("Error consulta. Ingrese manual.", "err");
    },
    complete: function () {
      loading(false);
    }
  });
}

function loading(isLoading) {
  const $btn = $("#btnBuscarDoc");
  if (isLoading) {
    $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i>');
    setEstado("Consultando...", "info");
  } else {
    $btn.prop("disabled", false).html('<i class="fa fa-search"></i>');
  }
}

init();
