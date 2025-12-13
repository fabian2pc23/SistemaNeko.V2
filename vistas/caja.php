<?php
// vistas/caja.php
ob_start();
session_start();

if (!isset($_SESSION["nombre"])) {
    header("Location: ../login.php");
} else {
    require 'header.php';
    
    if ($_SESSION['ventas'] == 1) {
?>
<!--Contenido-->
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="box">
                    <div class="box-header with-border">
                        <h1 class="box-title"><i class="fa fa-money"></i> Control de Caja</h1>
                        <div class="box-tools pull-right">
                        </div>
                    </div>
                    <!-- /.box-header -->
                    
                    <!-- Panel de Estado de Caja -->
                    <div class="panel panel-body" id="panelEstadoCaja">
                        <div class="row">
                            <div class="col-lg-12">
                                <div id="cajaCerrada" style="display:none;">
                                    <div class="alert alert-warning">
                                        <h4><i class="fa fa-exclamation-triangle"></i> Caja Cerrada</h4>
                                        <p>No hay caja abierta actualmente. Debe abrir la caja para poder registrar ventas y compras.</p>
                                        <button type="button" class="btn btn-success" onclick="mostrarFormApertura()">
                                            <i class="fa fa-unlock"></i> Abrir Caja
                                        </button>
                                    </div>
                                </div>
                                
                                <div id="cajaAbierta" style="display:none;">
                                    <div class="alert alert-success">
                                        <h4><i class="fa fa-check-circle"></i> Caja Abierta</h4>
                                        <div class="row">
                                            <div class="col-md-3">
                                                <strong>Usuario:</strong> <span id="cajaUsuario"></span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Apertura:</strong> <span id="cajaFechaApertura"></span>
                                            </div>
                                            <div class="col-md-3">
                                                <strong>Monto Inicial:</strong> S/. <span id="cajaMontoInicial"></span>
                                            </div>
                                            <div class="col-md-3">
                                                <button type="button" class="btn btn-danger btn-sm" onclick="mostrarFormCierre()">
                                                    <i class="fa fa-lock"></i> Cerrar Caja
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Resumen en tiempo real -->
                                    <div class="row" style="margin-top: 15px;">
                                        <div class="col-lg-3 col-xs-6">
                                            <div class="small-box bg-aqua">
                                                <div class="inner">
                                                    <h3 id="totalVentas">S/. 0.00</h3>
                                                    <p>Total Ventas</p>
                                                </div>
                                                <div class="icon">
                                                    <i class="fa fa-shopping-cart"></i>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-3 col-xs-6">
                                            <div class="small-box bg-red">
                                                <div class="inner">
                                                    <h3 id="totalCompras">S/. 0.00</h3>
                                                    <p>Total Compras</p>
                                                </div>
                                                <div class="icon">
                                                    <i class="fa fa-truck"></i>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-3 col-xs-6">
                                            <div class="small-box bg-green">
                                                <div class="inner">
                                                    <h3 id="saldoCaja">S/. 0.00</h3>
                                                    <p>Saldo en Caja</p>
                                                </div>
                                                <div class="icon">
                                                    <i class="fa fa-money"></i>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="col-lg-3 col-xs-6">
                                            <div class="small-box bg-yellow">
                                                <div class="inner">
                                                    <h3><span id="numTransacciones">0</span></h3>
                                                    <p>Transacciones</p>
                                                </div>
                                                <div class="icon">
                                                    <i class="fa fa-exchange"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pestañas -->
                    <div class="box-body">
                        <ul class="nav nav-tabs" id="myTab">
                            <li class="active"><a data-toggle="tab" href="#historial">
                                <i class="fa fa-history"></i> Historial de Cajas
                            </a></li>
                        </ul>
                        
                        <div class="tab-content">
                            <!-- Tab Historial -->
                            <div id="historial" class="tab-pane fade in active">
                                <div class="row" style="margin-top: 15px;">
                                    <div class="col-md-12">
                                        <div class="form-group col-md-3">
                                            <label>Fecha Inicio:</label>
                                            <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio">
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>Fecha Fin:</label>
                                            <input type="date" class="form-control" name="fecha_fin" id="fecha_fin">
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>Estado:</label>
                                            <select class="form-control" name="estado_filtro" id="estado_filtro">
                                                <option value="todos">Todos</option>
                                                <option value="Abierta">Abierta</option>
                                                <option value="Cerrada">Cerrada</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label>&nbsp;</label><br>
                                            <button type="button" class="btn btn-primary" onclick="listarCajas()">
                                                <i class="fa fa-search"></i> Buscar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table id="tbllistado" class="table table-striped table-bordered table-condensed table-hover">
                                        <thead>
                                            <th>ID</th>
                                            <th>Fecha</th>
                                            <th>Usuario</th>
                                            <th>Apertura</th>
                                            <th>Cierre</th>
                                            <th>Monto Inicial</th>
                                            <th>Monto Final</th>
                                            <th>Total Ventas</th>
                                            <th>Total Compras</th>
                                            <th>Saldo</th>
                                            <th>Diferencia</th>
                                            <th># Ventas</th>
                                            <th># Compras</th>
                                            <th>Estado</th>
                                            <th>Opciones</th>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                        <tfoot>
                                            <th>ID</th>
                                            <th>Fecha</th>
                                            <th>Usuario</th>
                                            <th>Apertura</th>
                                            <th>Cierre</th>
                                            <th>Monto Inicial</th>
                                            <th>Monto Final</th>
                                            <th>Total Ventas</th>
                                            <th>Total Compras</th>
                                            <th>Saldo</th>
                                            <th>Diferencia</th>
                                            <th># Ventas</th>
                                            <th># Compras</th>
                                            <th>Estado</th>
                                            <th>Opciones</th>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- /.box-body -->
                </div>
                <!-- /.box -->
            </div>
            <!-- /.col -->
        </div>
        <!-- /.row -->
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->

<!-- Modal Apertura de Caja -->
<div class="modal fade" id="modalApertura" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-unlock"></i> Apertura de Caja</h4>
            </div>
            <div class="modal-body">
                <form name="formApertura" id="formApertura" method="POST">
                    <div class="form-group">
                        <label>Monto Inicial (S/.):</label>
                        <input type="number" class="form-control" name="monto_inicial" id="monto_inicial" 
                               step="0.01" min="0" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>Observaciones:</label>
                        <textarea class="form-control" name="observaciones_apertura" id="observaciones_apertura" 
                                  rows="3" placeholder="Observaciones opcionales"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" onclick="abrirCaja()">
                    <i class="fa fa-unlock"></i> Abrir Caja
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cierre de Caja -->
<div class="modal fade" id="modalCierre" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-lock"></i> Cierre de Caja</h4>
            </div>
            <div class="modal-body">
                <div class="alert alert-info">
                    <strong>Resumen de Caja:</strong><br>
                    Monto Inicial: S/. <span id="cierreMontoInicial">0.00</span><br>
                    Total Ventas: S/. <span id="cierreTotalVentas">0.00</span><br>
                    Total Compras: S/. <span id="cierreTotalCompras">0.00</span><br>
                    <strong>Saldo Calculado: S/. <span id="cierreSaldoCalculado">0.00</span></strong>
                </div>
                
                <form name="formCierre" id="formCierre" method="POST">
                    <input type="hidden" name="idcaja_cierre" id="idcaja_cierre">
                    <div class="form-group">
                        <label>Monto Final Real (S/.):</label>
                        <input type="number" class="form-control" name="monto_final" id="monto_final" 
                               step="0.01" min="0" required placeholder="0.00">
                        <small class="help-block">Ingrese el monto real contado en caja</small>
                    </div>
                    <div class="form-group">
                        <label>Diferencia:</label>
                        <input type="text" class="form-control" id="diferencia_cierre" readonly>
                    </div>
                    <div class="form-group">
                        <label>Observaciones:</label>
                        <textarea class="form-control" name="observaciones_cierre" id="observaciones_cierre" 
                                  rows="3" placeholder="Observaciones del cierre"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" onclick="cerrarCaja()">
                    <i class="fa fa-lock"></i> Cerrar Caja
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Resumen -->
<div class="modal fade" id="modalResumen" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title"><i class="fa fa-file-text"></i> Resumen de Caja</h4>
            </div>
            <div class="modal-body" id="contenidoResumen">
                <!-- Se llenará dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php
    } else {
        require 'noacceso.php';
    }
    require 'footer.php';
?>
<script>
    var idusuario_session = "<?php echo $_SESSION['idusuario']; ?>";
</script>
<script type="text/javascript" src="scripts/caja.js"></script>
<?php
}
ob_end_flush();
?>
