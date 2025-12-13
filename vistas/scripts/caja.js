// scripts/caja.js
var tabla;
var idcaja_actual = null;

// Función que se ejecuta al inicio
function init() {
    mostrarForm(false);
    listarCajas();
    verificarEstadoCaja();

    // Actualizar estadísticas cada 30 segundos
    setInterval(actualizarEstadisticas, 30000);

    // Calcular diferencia en cierre
    $("#monto_final").on('input', function () {
        calcularDiferencia();
    });
}

// Verificar si hay caja abierta
function verificarEstadoCaja() {
    $.ajax({
        url: "../ajax/caja.php?op=verificarCajaAbierta",
        type: "POST",
        dataType: "json",
        success: function (data) {
            if (data.success) {
                idcaja_actual = data.idcaja;
                mostrarCajaAbierta();
                actualizarEstadisticas();
            } else {
                mostrarCajaCerrada();
            }
        },
        error: function () {
            mostrarCajaCerrada();
        }
    });
}

// Mostrar panel de caja cerrada
function mostrarCajaCerrada() {
    $("#cajaCerrada").show();
    $("#cajaAbierta").hide();
    idcaja_actual = null;
}

// Mostrar panel de caja abierta
function mostrarCajaAbierta() {
    $("#cajaCerrada").hide();
    $("#cajaAbierta").show();
    obtenerDatosCajaAbierta();
}

// Obtener datos de la caja abierta
function obtenerDatosCajaAbierta() {
    $.ajax({
        url: "../ajax/caja.php?op=obtenerCajaAbierta",
        type: "POST",
        dataType: "json",
        success: function (data) {
            if (data.success) {
                var caja = data.data;
                $("#cajaUsuario").text(caja.usuario);
                $("#cajaFechaApertura").text(formatearFechaHora(caja.fecha_apertura));
                $("#cajaMontoInicial").text(parseFloat(caja.monto_inicial).toFixed(2));
            }
        }
    });
}

// Actualizar estadísticas en tiempo real
function actualizarEstadisticas() {
    if (!idcaja_actual) return;

    $.ajax({
        url: "../ajax/caja.php?op=obtenerEstadisticas",
        type: "POST",
        dataType: "json",
        success: function (data) {
            if (data.success) {
                var stats = data.data;
                $("#totalVentas").text("S/. " + parseFloat(stats.total_ventas).toFixed(2));
                $("#totalCompras").text("S/. " + parseFloat(stats.total_compras).toFixed(2));
                $("#saldoCaja").text("S/. " + parseFloat(stats.saldo_calculado).toFixed(2));
                $("#numTransacciones").text(parseInt(stats.num_ventas) + parseInt(stats.num_compras));
            }
        }
    });
}

// Mostrar formulario de apertura
function mostrarFormApertura() {
    $("#formApertura")[0].reset();
    $("#modalApertura").modal('show');
}

// Abrir caja
function abrirCaja() {
    var monto_inicial = $("#monto_inicial").val();
    var observaciones = $("#observaciones_apertura").val();

    if (monto_inicial === '' || parseFloat(monto_inicial) < 0) {
        bootbox.alert("Debe ingresar un monto inicial válido");
        return;
    }

    bootbox.confirm("¿Está seguro de abrir la caja con un monto inicial de S/. " + parseFloat(monto_inicial).toFixed(2) + "?", function (result) {
        if (result) {
            $.ajax({
                url: "../ajax/caja.php?op=abrirCaja",
                type: "POST",
                data: {
                    idusuario: idusuario_session,
                    monto_inicial: monto_inicial,
                    observaciones: observaciones
                },
                dataType: "json",
                success: function (data) {
                    if (data.success) {
                        bootbox.alert(data.message, function () {
                            $("#modalApertura").modal('hide');
                            verificarEstadoCaja();
                            listarCajas();
                        });
                    } else {
                        bootbox.alert(data.message);
                    }
                },
                error: function () {
                    bootbox.alert("Error al abrir la caja");
                }
            });
        }
    });
}

// Mostrar formulario de cierre
function mostrarFormCierre() {
    if (!idcaja_actual) {
        bootbox.alert("No hay caja abierta");
        return;
    }

    // Obtener estadísticas actuales
    $.ajax({
        url: "../ajax/caja.php?op=obtenerEstadisticas",
        type: "POST",
        dataType: "json",
        success: function (data) {
            if (data.success) {
                var stats = data.data;
                $("#idcaja_cierre").val(stats.idcaja);
                $("#cierreMontoInicial").text(parseFloat(stats.monto_inicial).toFixed(2));
                $("#cierreTotalVentas").text(parseFloat(stats.total_ventas).toFixed(2));
                $("#cierreTotalCompras").text(parseFloat(stats.total_compras).toFixed(2));
                $("#cierreSaldoCalculado").text(parseFloat(stats.saldo_calculado).toFixed(2));

                $("#formCierre")[0].reset();
                $("#idcaja_cierre").val(stats.idcaja);
                $("#diferencia_cierre").val("S/. 0.00");
                $("#modalCierre").modal('show');
            }
        }
    });
}

// Calcular diferencia en cierre
function calcularDiferencia() {
    var monto_final = parseFloat($("#monto_final").val()) || 0;
    var saldo_calculado = parseFloat($("#cierreSaldoCalculado").text()) || 0;
    var diferencia = monto_final - saldo_calculado;

    var color = diferencia === 0 ? 'green' : (diferencia > 0 ? 'blue' : 'red');
    $("#diferencia_cierre").val("S/. " + diferencia.toFixed(2));
    $("#diferencia_cierre").css('color', color);
    $("#diferencia_cierre").css('font-weight', 'bold');
}

// Cerrar caja
function cerrarCaja() {
    var idcaja = $("#idcaja_cierre").val();
    var monto_final = $("#monto_final").val();
    var observaciones = $("#observaciones_cierre").val();

    if (monto_final === '' || parseFloat(monto_final) < 0) {
        bootbox.alert("Debe ingresar el monto final contado en caja");
        return;
    }

    bootbox.confirm("¿Está seguro de cerrar la caja? Esta acción no se puede deshacer.", function (result) {
        if (result) {
            $.ajax({
                url: "../ajax/caja.php?op=cerrarCaja",
                type: "POST",
                data: {
                    idcaja: idcaja,
                    monto_final: monto_final,
                    observaciones: observaciones
                },
                dataType: "json",
                success: function (data) {
                    if (data.success) {
                        bootbox.alert(data.message, function () {
                            $("#modalCierre").modal('hide');
                            verificarEstadoCaja();
                            listarCajas();
                        });
                    } else {
                        bootbox.alert(data.message);
                    }
                },
                error: function () {
                    bootbox.alert("Error al cerrar la caja");
                }
            });
        }
    });
}

// Listar cajas
function listarCajas() {
    var fecha_inicio = $("#fecha_inicio").val();
    var fecha_fin = $("#fecha_fin").val();
    var estado = $("#estado_filtro").val();

    tabla = $('#tbllistado').dataTable({
        "aProcessing": true,
        "aServerSide": true,
        "destroy": true,
        ajax: {
            url: '../ajax/caja.php?op=listar',
            type: "post",
            dataType: "json",
            data: {
                fecha_inicio: fecha_inicio,
                fecha_fin: fecha_fin,
                estado: estado
            },
            error: function (e) {
                console.log(e.responseText);
            }
        },
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]],
        "language": {
            "sProcessing": "Procesando...",
            "sLengthMenu": "Mostrar _MENU_ registros",
            "sZeroRecords": "No se encontraron resultados",
            "sEmptyTable": "Ningún dato disponible en esta tabla",
            "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
            "sInfoPostFix": "",
            "sSearch": "Buscar:",
            "sUrl": "",
            "sInfoThousands": ",",
            "sLoadingRecords": "Cargando...",
            "oPaginate": {
                "sFirst": "Primero",
                "sLast": "Último",
                "sNext": "Siguiente",
                "sPrevious": "Anterior"
            },
            "oAria": {
                "sSortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sSortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        }
    }).DataTable();
}

// Mostrar resumen de caja
function mostrarResumen(idcaja) {
    $.ajax({
        url: "../ajax/caja.php?op=obtenerResumen",
        type: "POST",
        data: { idcaja: idcaja },
        dataType: "json",
        success: function (data) {
            if (data.success) {
                var html = '<div class="row">';

                // Información de la caja
                console.log("Resumen Caja Data:", data);
                var caja = data.caja;
                html += '<div class="col-md-12">';
                html += '<h4><strong>Información de Caja #' + caja.idcaja + '</strong></h4>';
                html += '<p><strong>Usuario:</strong> ' + caja.usuario + ' - <strong>' + (caja.cargo || '') + '</strong></p>';
                html += '<p><strong>Apertura:</strong> ' + formatearFechaHora(caja.fecha_apertura) + '</p>';
                if (caja.fecha_cierre) {
                    html += '<p><strong>Cierre:</strong> ' + formatearFechaHora(caja.fecha_cierre) + '</p>';
                }
                html += '<p><strong>Monto Inicial:</strong> S/. ' + parseFloat(caja.monto_inicial).toFixed(2) + '</p>';
                if (caja.monto_final) {
                    html += '<p><strong>Monto Final:</strong> S/. ' + parseFloat(caja.monto_final).toFixed(2) + '</p>';
                }
                html += '<p><strong>Total Ventas:</strong> S/. ' + parseFloat(caja.total_ventas).toFixed(2) + '</p>';
                html += '<p><strong>Total Compras:</strong> S/. ' + parseFloat(caja.total_compras).toFixed(2) + '</p>';
                html += '</div>';

                // Ventas
                html += '<div class="col-md-6">';
                html += '<h4><strong>Ventas (' + data.ventas.length + ')</strong></h4>';
                if (data.ventas.length > 0) {
                    html += '<table class="table table-condensed table-bordered">';
                    html += '<thead><tr><th>Comprobante</th><th>Cliente</th><th>Monto</th></tr></thead><tbody>';
                    data.ventas.forEach(function (v) {
                        html += '<tr>';
                        html += '<td>' + v.tipo_comprobante + ' ' + v.serie_comprobante + '-' + v.num_comprobante + '</td>';
                        html += '<td>' + v.cliente + '</td>';
                        html += '<td>S/. ' + parseFloat(v.total_venta).toFixed(2) + '</td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                } else {
                    html += '<p class="text-muted">No hay ventas registradas</p>';
                }
                html += '</div>';

                // Compras
                html += '<div class="col-md-6">';
                html += '<h4><strong>Compras (' + data.compras.length + ')</strong></h4>';
                if (data.compras.length > 0) {
                    html += '<table class="table table-condensed table-bordered">';
                    html += '<thead><tr><th>Comprobante</th><th>Proveedor</th><th>Monto</th></tr></thead><tbody>';
                    data.compras.forEach(function (c) {
                        html += '<tr>';
                        html += '<td>' + c.tipo_comprobante + ' ' + c.serie_comprobante + '-' + c.num_comprobante + '</td>';
                        html += '<td>' + c.proveedor + '</td>';
                        html += '<td>S/. ' + parseFloat(c.total_compra).toFixed(2) + '</td>';
                        html += '</tr>';
                    });
                    html += '</tbody></table>';
                } else {
                    html += '<p class="text-muted">No hay compras registradas</p>';
                }
                html += '</div>';

                html += '</div>';

                $("#contenidoResumen").html(html);
                $("#modalResumen").modal('show');
            } else {
                bootbox.alert(data.message);
            }
        },
        error: function () {
            bootbox.alert("Error al obtener el resumen");
        }
    });
}

// Imprimir cierre de caja (PDF)
function imprimirCierre(idcaja) {
    window.open("../reportes/CierreCaja.php?id=" + idcaja, '_blank');
}

// Formatear fecha y hora
function formatearFechaHora(fechaHora) {
    if (!fechaHora) return '-';
    var fecha = new Date(fechaHora);
    var dia = ("0" + fecha.getDate()).slice(-2);
    var mes = ("0" + (fecha.getMonth() + 1)).slice(-2);
    var anio = fecha.getFullYear();
    var hora = ("0" + fecha.getHours()).slice(-2);
    var min = ("0" + fecha.getMinutes()).slice(-2);
    return dia + '/' + mes + '/' + anio + ' ' + hora + ':' + min;
}

// Mostrar/ocultar formularios
function mostrarForm(flag) {
    // No se usa en esta vista
}

// Activar menú
$("#mCaja").addClass("active");

// Inicializar
init();
