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

  // Desmarcar todos los permisos
  $("#permisos_rol input[type='checkbox']").prop("checked", false);
}

// ========== CARGAR PERMISOS (nuevo rol o editar) ==========
function cargarPermisos(idrol = 0) {
  $.post("../ajax/rol.php?op=permisos&id=" + idrol + "&t=" + new Date().getTime(), function (r) {
    $("#permisos_rol").html(r);
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
// =========================================

function guardaryeditar(e) {
  e.preventDefault();
  $("#btnGuardar").prop("disabled", true);

  const nom = $("#nombre").val().trim();
  if (!esNombreValido(nom)) {
    bootbox.alert("⚠️ Elija un nombre válido. Evite letras repetidas o nombres muy cortos.");
    $("#nombre").focus();
    $("#btnGuardar").prop("disabled", false);
    return;
  }

  // === VALIDACIÓN: al menos un permiso marcado ===
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
      bootbox.alert(datos);
      mostrarform(false);
      tabla.ajax.reload();
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

    // ✅ CARGAR PERMISOS DEL ROL EXISTENTE
    cargarPermisos(idrol);
  });
}

//Función para desactivar registros
function desactivar(idrol) {
  bootbox.confirm("¿Está seguro de desactivar el rol?", function (result) {
    if (result) {
      $.post("../ajax/rol.php?op=desactivar", { idrol: idrol }, function (e) {
        bootbox.alert(e);
        tabla.ajax.reload();
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

init();