// vistas/scripts/venta.js

var tabla;
var cont = 0;
var detalles = 0;

// ==================== TOAST SIMPLE (estilo moderno) ====================
function mostrarToast(mensaje, tipo) {
    var idToast = 'neko-toast-global';
    var $toast = $('#' + idToast);

    if ($toast.length === 0) {
        $toast = $('<div>', { id: idToast }).appendTo('body');
        $toast.css({
            position: 'fixed', right: '20px', bottom: '20px',
            minWidth: '260px', maxWidth: '360px', padding: '12px 16px',
            borderRadius: '10px', color: '#fff', fontSize: '14px',
            boxShadow: '0 10px 25px rgba(15,23,42,.35)', zIndex: 9999, display: 'none'
        });
    }

    var bg = '#0f766e'; // success
    if (tipo === 'error') bg = '#dc2626';
    if (tipo === 'info') bg = '#2563eb';

    $toast.css('background', bg);
    $toast.text(mensaje);
    $toast.stop(true, true).fadeIn(200);

    setTimeout(function () { $toast.fadeOut(200); }, 3000);
}

// ==================== FILTROS MODERNOS ====================

// 1. Filtro de Estado (Regex)
function filtrarEstado(estado) {
    // UI
    $('.status-btn').removeClass('active');
    $('#filter-' + estado).addClass('active');

    // Lógica DataTables (Columna 7: Estado)
    if (estado === 'todos') {
        tabla.column(7).search('').draw();
    } else if (estado === 'aceptados') {
        tabla.column(7).search('^Aceptado$', true, false).draw();
    } else if (estado === 'anulados') {
        tabla.column(7).search('^Anulado$', true, false).draw();
    }
}

// 2. Filtro de Comprobante
function filtrarComprobante(tipo) {
    // Columna 4: Comprobante
    tabla.column(4).search(tipo).draw();
}

// 3. Filtro de Fecha (Custom)
$.fn.dataTable.ext.search.push(
    function (settings, data, dataIndex) {
        var min = $('#fecha_inicio').val();
        var max = $('#fecha_fin').val();

        // Columna 1: Fecha (Formato YYYY-MM-DD o similar)
        var fechaStr = data[1] || "";
        var fecha = fechaStr.substring(0, 10);

        if (min == "" && max == "") return true;
        if (min == "" && fecha <= max) return true;
        if (min <= fecha && max == "") return true;
        if (min <= fecha && fecha <= max) return true;

        return false;
    }
);

function filtrarFecha() {
    tabla.draw();
}

function limpiarFecha() {
    $('#fecha_inicio').val('');
    $('#fecha_fin').val('');
    tabla.draw();
}

// 4. Buscador Global
function setupSearchInput() {
    $('#search-input').on('keyup', function () {
        tabla.search(this.value).draw();
    });
}

// 5. Exportar
function exportarTabla(type) {
    if (type === 'excel') $('.buttons-excel').click();
    if (type === 'pdf') $('.buttons-pdf').click();
    if (type === 'csv') $('.buttons-csv').click();
    if (type === 'copy') $('.buttons-copy').click();
}

// 6. Cambiar Longitud de Página
function cambiarLongitud(len) {
    tabla.page.len(len).draw();
}

// ==================== CORRELATIVO ====================
function actualizarSerieNumeroImpuesto() {
    var tipo = $("#tipo_comprobante").val();
    if (!tipo) return;

    $.post("../ajax/venta.php?op=obtenerCorrelativo", { tipo_comprobante: tipo }, function (data) {
        try {
            var resp = (typeof data === 'string') ? JSON.parse(data) : data;
            if (!resp.success) {
                mostrarToast(resp.message || 'No se pudo obtener serie y número.', 'error');
                $("#serie_comprobante").val('');
                $("#num_comprobante").val('');
                return;
            }
            $("#serie_comprobante").val(resp.serie);
            $("#num_comprobante").val(resp.numero);
            $("#impuesto").val(resp.impuesto);
        } catch (e) {
            mostrarToast('Error inesperado al obtener correlativo.', 'error');
        }
    });
}

// ==================== STOCK VISUAL ====================
function actualizarStockVisual(idarticulo, stockOriginal, nuevaCantidad) {
    var restante = stockOriginal - nuevaCantidad;
    if (restante < 0) restante = 0;
    $("#stock_disp_" + idarticulo).text(restante);
}

// ==================== INICIALIZACIÓN ====================
function init() {
    mostrarform(false);
    listar();
    setupSearchInput();
    initFilters(); // Inicializar filtros del modal

    $("#formulario").on("submit", function (e) { guardaryeditar(e); });

    $.post("../ajax/venta.php?op=selectCliente", function (r) {
        $("#idcliente").html(r);
        $('#idcliente').selectpicker('refresh');
    });

    $('#mVentas').addClass("treeview active");
    $('#lVentas').addClass("active");

    $("#tipo_comprobante").on("change", function () { actualizarSerieNumeroImpuesto(); });

    // Event listeners de cantidad movidos a oninput en HTML dinámico
}

// 7. Filtro de Producto
function filtrarProducto(idarticulo) {
    tabla.ajax.url('../ajax/venta.php?op=listar&idarticulo=' + idarticulo).load();
}

function initFilters() {
    // Cargar Categorías
    $.post("../ajax/articulo.php?op=selectCategoria", function (r) {
        $("#filtro_categoria").html('<option value="">Todas</option>' + r);
        $('#filtro_categoria').selectpicker('refresh');
    });

    // Cargar Marcas
    $.post("../ajax/marca.php?op=select", function (r) {
        $("#filtro_marca").html('<option value="">Todas</option>' + r);
        $('#filtro_marca').selectpicker('refresh');
    });

    // Cargar Productos para el filtro
    $.post("../ajax/articulo.php?op=selectArticulos", function (r) {
        $("#idarticulo_filter").html('<option value="">Todos los productos</option>' + r);
        $('#idarticulo_filter').selectpicker('refresh');
    });

    // Eventos de cambio
    $("#filtro_categoria").change(function () {
        var cat = $(this).find("option:selected").text();
        if (this.value == "") cat = "";
        // Columna 2: Categoría
        tablaArticulos.column(2).search(cat).draw();
    });

    $("#filtro_marca").change(function () {
        var marca = $(this).find("option:selected").text();
        if (this.value == "") marca = "";
        // Columna 3: Marca
        tablaArticulos.column(3).search(marca).draw();
    });
}

function limpiar() {
    $("#idventa").val("");
    $("#idcliente").val("");
    $('#idcliente').selectpicker('refresh');
    $("#serie_comprobante").val("");
    $("#num_comprobante").val("");
    $("#impuesto").val("0");
    $("#total_venta").val("");
    $(".filas").remove();
    $("#total").html("S/. 0.00");

    var now = new Date();
    var day = ("0" + now.getDate()).slice(-2);
    var month = ("0" + (now.getMonth() + 1)).slice(-2);
    var today = now.getFullYear() + "-" + (month) + "-" + (day);
    $('#fecha_hora').val(today);

    $("#tipo_comprobante").val("Boleta");
    $("#tipo_comprobante").selectpicker('refresh');
    detalles = 0;
    cont = 0;
    $("#btnGuardar").hide();
}

function mostrarform(flag) {
    if (flag) {
        limpiar();
        $("#listadoregistros").hide();
        $("#formularioregistros").show();
        $("#btnagregar").hide();
        listarArticulos();
        $("#btnGuardar").hide();
        $("#btnCancelar").show();
        $("#btnAgregarArt").show();
        detalles = 0;
        actualizarSerieNumeroImpuesto();
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
    var idarticulo = $("#idarticulo_filter").val();
    tabla = $('#tbllistado').dataTable({
        "aProcessing": true,
        "aServerSide": true,
        dom: 'Bfrtip',
        buttons: ['copyHtml5', 'excelHtml5', 'csvHtml5', 'pdf'],
        "ajax": {
            url: '../ajax/venta.php?op=listar&idarticulo=' + idarticulo,
            type: "get",
            dataType: "json",
            error: function (e) { console.log(e.responseText); }
        },
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]]
    }).DataTable();
}

function listarArticulos() {
    tablaArticulos = $('#tblarticulos').DataTable({
        "aProcessing": true,
        "aServerSide": true,
        dom: 'Blfrtip', // 'l' habilita el selector de longitud
        buttons: [],
        "lengthMenu": [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
        "ajax": {
            url: '../ajax/venta.php?op=listarArticulosVenta',
            type: "get",
            dataType: "json",
            error: function (e) { console.log(e.responseText); }
        },
        "bDestroy": true,
        "iDisplayLength": 5,
        "order": [[0, "desc"]],
        "columnDefs": [
            {
                "targets": 1, // Columna Nombre
                "render": function (data, type, row) {
                    // Primera letra mayúscula, resto minúscula
                    if (data) {
                        return data.charAt(0).toUpperCase() + data.slice(1).toLowerCase();
                    }
                    return data;
                }
            }
        ],
        "createdRow": function (row, data, dataIndex) {
            // Columna Stock es la 5 (índice 5)
            // data[5] es el HTML del span, necesitamos extraer el número o usar el valor original si viniera limpio
            // Pero como viene con HTML <span id="...">10</span>, usamos jQuery para parsear
            var stockHtml = data[5];
            var stockVal = parseInt($(stockHtml).text());

            if (stockVal < 5) {
                $('td', row).eq(5).css({
                    'color': '#f97316', // Naranja
                    'font-weight': 'bold'
                });
            }
        }
    });
}

function guardaryeditar(e) {
    e.preventDefault();
    var formData = new FormData($("#formulario")[0]);

    $.ajax({
        url: "../ajax/venta.php?op=guardaryeditar",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (resp) {
            try {
                var data = (typeof resp === 'string') ? JSON.parse(resp) : resp;
                if (data.success) {
                    mostrarToast(data.message || 'Venta registrada correctamente.', 'success');
                    mostrarform(false);
                    tabla.ajax.reload();
                } else {
                    mostrarToast(data.message || 'No se pudo registrar la venta.', 'error');
                }
            } catch (err) {
                mostrarToast('Error inesperado al registrar la venta.', 'error');
            }
        },
        error: function (xhr) {
            mostrarToast('Error de comunicación con el servidor.', 'error');
        }
    });
    limpiar();
}

function mostrar(idventa) {
    $.post("../ajax/venta.php?op=mostrar", { idventa: idventa }, function (data, status) {
        data = JSON.parse(data);
        mostrarform(true);

        $("#idcliente").val(data.idcliente);
        $("#idcliente").selectpicker('refresh');
        $("#tipo_comprobante").val(data.tipo_comprobante);
        $("#tipo_comprobante").selectpicker('refresh');
        $("#serie_comprobante").val(data.serie_comprobante);
        $("#num_comprobante").val(data.num_comprobante);
        $("#fecha_hora").val(data.fecha);
        $("#impuesto").val(data.impuesto);
        $("#idventa").val(data.idventa);

        $("#btnGuardar").hide();
        $("#btnCancelar").show();
        $("#btnAgregarArt").hide();
    });

    $.post("../ajax/venta.php?op=listarDetalle&id=" + idventa, function (r) {
        $("#detalles").html(r);
    });
}

function anular(idventa) {
    if (confirm("¿Está Seguro de anular la venta?")) {
        $.post("../ajax/venta.php?op=anular", { idventa: idventa }, function (e) {
            mostrarToast(e, 'info');
            tabla.ajax.reload();
        });
    }
}

// ==================== DETALLES ====================
function agregarDetalle(idarticulo, articulo, precio_venta, stockDisponible) {
    var cantidad = 1;
    var descuento = 0;

    if (idarticulo != "") {
        var ids = document.getElementsByName("idarticulo[]");
        var cantInputs = document.getElementsByName("cantidad[]");
        var subSpans = document.getElementsByName("subtotal");

        for (var i = 0; i < ids.length; i++) {
            if (parseInt(ids[i].value) === parseInt(idarticulo)) {
                var cActual = parseInt(cantInputs[i].value || 0);
                if (cActual >= stockDisponible) {
                    mostrarToast('Stock insuficiente para agregar más.', 'error');
                    return;
                }
                var c = cActual + cantidad;
                cantInputs[i].value = c;

                // Actualizar subtotal inmediatamente
                modificarSubototales();
                actualizarStockVisual(idarticulo, stockDisponible, c);
                return;
            }
        }

        // Formatear nombre: Primera mayúscula, resto minúscula
        articulo = articulo.charAt(0).toUpperCase() + articulo.slice(1).toLowerCase();

        // Calculo inicial (descuento 0%)
        var subtotalNuevo = cantidad * precio_venta;

        var fila = '<tr class="filas" id="fila' + cont + '">' +
            '<td><button type="button" class="btn btn-danger" onclick="eliminarDetalle(' + cont + ')"><i class="fa fa-trash"></i></button></td>' +
            '<td><input type="hidden" name="idarticulo[]" value="' + idarticulo + '"><input type="hidden" name="stock_detalle[]" value="' + stockDisponible + '">' + articulo + '</td>' +
            '<td><input type="number" name="cantidad[]" value="' + cantidad + '" min="1" max="' + stockDisponible + '" data-stock="' + stockDisponible + '" style="width:120px;" oninput="modificarSubototales()"></td>' +
            '<td><input type="number" name="precio_venta[]" value="' + precio_venta + '" readonly style="width:150px; background-color: #f3f4f6;"></td>' +
            '<td><div class="input-group" style="width:150px;"><input type="number" name="descuento[]" value="' + descuento + '" min="0" max="50" step="1" placeholder="0" oninput="modificarSubototales()"><span class="input-group-addon">%</span></div></td>' +
            '<td><span name="subtotal" id="subtotal' + cont + '">' + subtotalNuevo.toFixed(2) + '</span></td>' +
            '</tr>';

        cont++;
        detalles++;
        $('#detalles').append(fila);
        modificarSubototales();
        actualizarStockVisual(idarticulo, stockDisponible, 1);
    } else {
        alert("Error al ingresar el detalle, revisar los datos del artículo");
    }
}

// validarYActualizarCantidad eliminada, lógica movida a modificarSubototales

function modificarSubototales() {
    var cant = document.getElementsByName("cantidad[]");
    var prec = document.getElementsByName("precio_venta[]");
    var desc = document.getElementsByName("descuento[]");
    var sub = document.getElementsByName("subtotal");

    for (var i = 0; i < cant.length; i++) {
        var inpC = cant[i];
        var inpP = prec[i];
        var inpD = desc[i];
        var inpS = sub[i];

        var c = parseInt(inpC.value);
        var p = parseFloat(inpP.value);
        var d = parseFloat(inpD.value);

        // Validar cantidad
        var stock = parseInt(inpC.getAttribute('data-stock') || 0);
        if (c > stock) {
            c = stock;
            inpC.value = stock;
            mostrarToast('Cantidad supera el stock disponible.', 'error');
        }
        if (c < 1) {
            c = 1;
            inpC.value = 1;
        }

        // Validar descuento (max 50%)
        if (d > 50) {
            d = 50;
            inpD.value = 50;
            mostrarToast('El descuento máximo permitido es 50%.', 'warning');
        }
        if (d < 0) {
            d = 0;
            inpD.value = 0;
        }

        // Calculo: (Precio * Cantidad) - (Descuento%)
        // Descuento es porcentaje sobre el total de la línea
        // subtotal = (c * p) * (1 - d/100)

        var montoBruto = c * p;
        var montoDescuento = montoBruto * (d / 100);
        var subtotal = montoBruto - montoDescuento;

        inpS.value = subtotal.toFixed(2);
        document.getElementsByName("subtotal")[i].innerHTML = inpS.value;
    }
    calcularTotales();
}

function calcularTotales() {
    var sub = document.getElementsByName("subtotal");
    var total = 0.0;
    for (var i = 0; i < sub.length; i++) {
        total += parseFloat(document.getElementsByName("subtotal")[i].value);
    }
    $("#total").html("S/. " + total.toFixed(2));
    $("#total_venta").val(total.toFixed(2));
    evaluar();
}

function evaluar() {
    if (detalles > 0) $("#btnGuardar").show();
    else {
        $("#btnGuardar").hide();
        cont = 0;
    }
}

function eliminarDetalle(indice) {
    var $tr = $("#fila" + indice);
    if ($tr.length) {
        var idarticulo = $tr.find("input[name='idarticulo[]']").val();
        var stockOriginal = $tr.find("input[name='stock_detalle[]']").val();
        actualizarStockVisual(idarticulo, stockOriginal, 0);
    }
    $("#fila" + indice).remove();
    calcularTotales();
    detalles--;
    evaluar();
}

init();
