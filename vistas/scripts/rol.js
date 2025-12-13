var tabla;

// Permitir solo letras (con acentos) y espacios; compacta espacios
function soloLetras(el) {
  el.value = el.value
    .replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]/g, "")
    .replace(/\s{2,}/g, " ")
    .replace(/^\s+/, "");
}

// Validar nombre con criterio avanzado
function esNombreValido(txt) {
  txt = txt.trim();
  if (!/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]{3,50}$/.test(txt)) return false;
  if (/^(.)\1{2,}$/.test(txt)) return false;
  if (!/[AEIOUÁÉÍÓÚaeiouáéíóú]/.test(txt)) return false;
  const invalidos = ["xxx", "wewqeq", "asdf", "qwe", "test", "rol", "role", "prueba"];
  if (invalidos.some(p => txt.toLowerCase().includes(p))) return false;
  return true;
}

//Función que se ejecuta al inicio
function init() {
  mostrarform(false);
  listar();
  setupSearchInput(); // Configurar búsqueda global

  $("#formulario").on("submit", function (e) {
    guardaryeditar(e);
  });

  $("#mAcceso").addClass("treeview active");
  $("#lRoles").addClass("active");
}

//Función limpiar
function limpiar() {
  $("#idrol").val("");
  $("#nombre").val("");

  // Resetear estados de edición (por si se bloqueó por ser Admin)
  $("#nombre").prop('disabled', false);
  $("#btnGuardar").show();
  $("#btnGuardar").prop("disabled", false);

  // Desmarcar todos los permisos y habilitarlos
  $("#permisos_rol input[type='checkbox']").prop("checked", false);
  $("#permisos_rol input[type='checkbox']").prop("disabled", false);
}

// ========== CARGAR PERMISOS (nuevo rol o editar) ==========
function cargarPermisos(idrol = 0, esReadOnly = false) {
  $.post("../ajax/rol.php?op=permisos&id=" + idrol + "&t=" + new Date().getTime(), function (r) {
    $("#permisos_rol").html(r);
    if (esReadOnly) {
      $("#permisos_rol input[type='checkbox']").prop("disabled", true);
    }
  }).fail(function () {
    bootbox.alert("❌ Error al cargar los permisos.");
  });
}

//Función mostrar formulario
function mostrarform(flag) {
  limpiar();
  if (flag) {
    $("#listadoregistros").hide();
    $("#formularioregistros").show();
    $("#btnGuardar").prop("disabled", false);
    $("#btnagregar").hide();

    // ✅ CARGAR PERMISOS AL ABRIR FORMULARIO (nuevo rol → id=0)
    cargarPermisos(0);
  } else {
    $("#listadoregistros").show();
    $("#formularioregistros").hide();
    $("#btnagregar").show();
  }
}

//Función cancelar formulario
function cancelarform() {
  limpiar();
  mostrarform(false);
}

// ======= FUNCIÓN LISTAR (MODERNIZADA) =======
function listar() {
  tabla = $('#tbllistado').DataTable({
    "aProcessing": true,
    "aServerSide": true,
    // Ocultamos controles nativos (f, l) para usar los nuestros
    dom: 'Brtip',
    buttons: [
      { extend: 'copyHtml5', className: 'buttons-copy' },
      { extend: 'excelHtml5', className: 'buttons-excel' },
      { extend: 'csvHtml5', className: 'buttons-csv' },
      { extend: 'pdf', className: 'buttons-pdf' }
    ],
    "ajax": {
      url: '../ajax/rol.php?op=listar',
      type: "get",
      dataType: "json",
      error: function (e) { console.log(e.responseText); }
    },
    // Mapea las 4 columnas visibles a los índices correctos del backend
    "columns": [
      { "data": 0 }, // Opciones
      { "data": 2 }, // Nombre (saltamos el 1, que es el ID)
      { "data": 3 }, // Estado
      { "data": 4 }  // Creado
    ],
    // Opciones no ordenable/buscable
    "columnDefs": [
      { "targets": 0, "orderable": false, "searchable": false }
    ],
    "bDestroy": true,
    "iDisplayLength": 10, // Coincide con el selector por defecto
    "order": [[1, "asc"]]   // Ordenar por Nombre (la 2da columna visible)
  });
}

function guardaryeditar(e) {
  e.preventDefault();
  $("#btnGuardar").prop("disabled", true);

  // --- VALIDACIONES FRONTEND (Igual que Marcas) ---
  var nombreInput = $("#nombre").val();

  // 1. Trim y Normalización de espacios
  var nombre = nombreInput.trim().replace(/\s+/g, ' ');
  $("#nombre").val(nombre); // Actualizar el input con el valor limpio

  // 2. Longitud mínima
  if (nombre.length < 2) {
    bootbox.alert("⚠️ El nombre del rol debe tener al menos 2 caracteres.");
    $("#nombre").focus();
    $("#btnGuardar").prop("disabled", false);
    return;
  }

  // 3. Caracteres permitidos (Alfanuméricos y espacios)
  var regex = /^[a-zA-Z0-9\u00C0-\u00FF\s]+$/;
  if (!regex.test(nombre)) {
    bootbox.alert("⚠️ El nombre contiene caracteres no válidos (solo letras, números y espacios).");
    $("#nombre").focus();
    $("#btnGuardar").prop("disabled", false);
    return;
  }

  // 4. Validación de permisos
  if ($("#permisos_rol input[type='checkbox']:checked").length === 0) {
    bootbox.alert("⚠️ Debe seleccionar al menos un permiso para el rol.");
    $("#btnGuardar").prop("disabled", false);
    return;
  }

  // Enviar datos
  var formData = new FormData($("#formulario")[0]);

  $.ajax({
    url: "../ajax/rol.php?op=guardaryeditar",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function (datos) {
      // Detectar si es error o advertencia
      if (datos.includes("❌") || datos.includes("⚠️") || datos.includes("⛔")) {
        bootbox.alert(datos); // Mostrar como alerta
      } else {
        bootbox.alert(datos); // Mostrar éxito
        mostrarform(false);
        tabla.ajax.reload();
      }
      $("#btnGuardar").prop("disabled", false);
    },
    error: function () {
      bootbox.alert("❌ Error de comunicación con el servidor.");
      $("#btnGuardar").prop("disabled", false);
    }
  });

  limpiar();
}

//Función para mostrar los datos de un registro (EDITAR)
function mostrar(idrol) {
  $.post("../ajax/rol.php?op=mostrar", { idrol: idrol }, function (data, status) {
    data = JSON.parse(data);
    mostrarform(true);
    $("#idrol").val(data.id_rol);
    $("#nombre").val(data.nombre);

    // Verificar si es Admin para bloquear edición
    var esAdmin = (data.nombre.toLowerCase() === 'administrador' || data.nombre.toLowerCase() === 'admin');

    if (esAdmin) {
      $("#nombre").prop('disabled', true);
      $("#btnGuardar").hide();
      bootbox.alert("🔒 El rol de Administrador es fundamental para el sistema y no puede ser modificado.");
    } else {
      $("#nombre").prop('disabled', false);
      $("#btnGuardar").show();
    }

    // ✅ CARGAR PERMISOS DEL ROL EXISTENTE (con flag de solo lectura si es admin)
    cargarPermisos(idrol, esAdmin);
  });
}

//Función para desactivar registros
function desactivar(idrol) {
  bootbox.confirm("¿Está seguro de desactivar el rol?", function (result) {
    if (result) {
      $.post("../ajax/rol.php?op=desactivar", { idrol: idrol }, function (e) {
        // Detectar si es error
        if (e.includes("❌") || e.includes("⛔")) {
          bootbox.alert(e);
        } else {
          bootbox.alert(e);
          tabla.ajax.reload();
        }
      });
    }
  });
}

//Función para activar registros
function activar(idrol) {
  bootbox.confirm("¿Está seguro de activar el rol?", function (result) {
    if (result) {
      $.post("../ajax/rol.php?op=activar", { idrol: idrol }, function (e) {
        bootbox.alert(e);
        tabla.ajax.reload();
      });
    }
  });
}

/* ==================== NUEVAS FUNCIONES FILTROS ==================== */
function filtrarEstado(estado) {
  // Reset botones
  $('.status-btn').removeClass('active');

  // Columna 2 es Estado (índice 2 en columns array: Opciones, Nombre, Estado, Creado)
  // Pero ojo: en columns definition: 0=Opciones, 1=Nombre(data:2), 2=Estado(data:3), 3=Creado(data:4)
  // Entonces la columna visual es la índice 2.
  var colEstado = tabla.column(2);

  if (estado === 'todos') {
    $('#filter-todos').addClass('active');
    colEstado.search('').draw();
  } else if (estado === 'activos') {
    $('#filter-activos').addClass('active');
    // Buscamos "Activo" (clase .bg-green tiene texto "Activo")
    colEstado.search('Activo', true, false).draw();
  } else if (estado === 'bloqueados') {
    $('#filter-bloqueados').addClass('active');
    // Buscamos "Inactivo" (clase .bg-red tiene texto "Inactivo")
    colEstado.search('Inactivo', true, false).draw();
  }
}

function setupSearchInput() {
  $('#search-input').on('keyup', function () {
    tabla.search(this.value).draw();
  });
}

function cambiarLongitud(len) {
  tabla.page.len(len).draw();
}

function exportarTabla(type) {
  if (type === 'copy') tabla.button('.buttons-copy').trigger();
  if (type === 'excel') tabla.button('.buttons-excel').trigger();
  if (type === 'csv') tabla.button('.buttons-csv').trigger();
  if (type === 'pdf') tabla.button('.buttons-pdf').trigger();
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
    url: '../ajax/rol.php?op=kpi_detalle&tipo=' + tipo,
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
          title: '<i class="fa fa-shield" style="color: #1565c0; margin-right: 8px;"></i>' + resp.titulo,
          html: tablaHtml,
          width: '750px',
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

init();