<?php
// vistas/venta.php
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// Validador central
require_once __DIR__ . '/_requires_auth.php';

$tz     = new DateTimeZone('America/Lima');
$today  = new DateTime('today', $tz);
$minDT  = (clone $today)->modify('-2 days');
$maxDT  = (clone $today)->modify('+2 days');

$valToday = $today->format('Y-m-d');
$valMin   = $minDT->format('Y-m-d');
$valMax   = $maxDT->format('Y-m-d');

require 'header.php';

// === Permiso del módulo (VENTAS) ===
$canVentas = !empty($_SESSION['ventas']) && (int)$_SESSION['ventas'] === 1;

if ($canVentas) {
    require_once "../config/Conexion.php";

    // ==================== KPIs (Ingeniero Experto en Ventas) ====================
    
    // 1. Ventas del Día (S/.)
    $sqlHoy = "SELECT IFNULL(SUM(total_venta),0) as total FROM venta WHERE DATE(fecha_hora) = CURDATE() AND estado='Aceptado'";
    $rsHoy = ejecutarConsultaSimpleFila($sqlHoy);
    $kpiVentasHoy = $rsHoy ? (float)$rsHoy['total'] : 0.00;

    // 2. Ventas del Mes (S/.)
    $sqlMes = "SELECT IFNULL(SUM(total_venta),0) as total FROM venta WHERE MONTH(fecha_hora) = MONTH(CURRENT_DATE()) AND YEAR(fecha_hora) = YEAR(CURRENT_DATE()) AND estado='Aceptado'";
    $rsMes = ejecutarConsultaSimpleFila($sqlMes);
    $kpiVentasMes = $rsMes ? (float)$rsMes['total'] : 0.00;

    // 3. Ticket Promedio (Mes Actual)
    $sqlAvg = "SELECT IFNULL(AVG(total_venta),0) as promedio FROM venta WHERE MONTH(fecha_hora) = MONTH(CURRENT_DATE()) AND YEAR(fecha_hora) = YEAR(CURRENT_DATE()) AND estado='Aceptado'";
    $rsAvg = ejecutarConsultaSimpleFila($sqlAvg);
    $kpiTicketPromedio = $rsAvg ? (float)$rsAvg['promedio'] : 0.00;

    // 4. Tasa de Éxito (Aceptadas vs Total Mes)
    $sqlTotalMes = "SELECT COUNT(*) as total FROM venta WHERE MONTH(fecha_hora) = MONTH(CURRENT_DATE()) AND YEAR(fecha_hora) = YEAR(CURRENT_DATE())";
    $rsTotalMes = ejecutarConsultaSimpleFila($sqlTotalMes);
    $totalOps = $rsTotalMes ? (int)$rsTotalMes['total'] : 0;

    $sqlAceptadasMes = "SELECT COUNT(*) as total FROM venta WHERE MONTH(fecha_hora) = MONTH(CURRENT_DATE()) AND YEAR(fecha_hora) = YEAR(CURRENT_DATE()) AND estado='Aceptado'";
    $rsAceptadasMes = ejecutarConsultaSimpleFila($sqlAceptadasMes);
    $totalAceptadas = $rsAceptadasMes ? (int)$rsAceptadasMes['total'] : 0;

    $kpiTasaExito = ($totalOps > 0) ? round(($totalAceptadas / $totalOps) * 100, 1) : 0;

    // 5. Cliente Destacado (Top Comprador Mes)
    $sqlTopCliente = "SELECT p.nombre, SUM(v.total_venta) as total_comprado 
                      FROM venta v 
                      JOIN persona p ON v.idcliente = p.idpersona 
                      WHERE MONTH(v.fecha_hora) = MONTH(CURRENT_DATE()) AND YEAR(v.fecha_hora) = YEAR(CURRENT_DATE()) AND v.estado='Aceptado' 
                      GROUP BY v.idcliente 
                      ORDER BY total_comprado DESC 
                      LIMIT 1";
    $rsTopCliente = ejecutarConsultaSimpleFila($sqlTopCliente);
    $kpiTopCliente = $rsTopCliente ? $rsTopCliente['nombre'] : 'N/A';
    $kpiTopMonto = $rsTopCliente ? (float)$rsTopCliente['total_comprado'] : 0.00;

    $nekoPrimary = '#1565c0';
    $nekoPrimaryDark = '#0d47a1';
?>
<!-- ====== Estilos Modernos ====== -->
<style>
  :root{
    --neko-primary: <?= $nekoPrimary ?>;
    --neko-primary-dark: <?= $nekoPrimaryDark ?>;
    --neko-bg:#f5f7fb;
    --neko-success: #059669;
    --neko-warning: #d97706;
    --neko-danger: #dc2626;
  }
  .content-wrapper{ background: var(--neko-bg); }
  
  /* Cards */
  .neko-card{
    background:#fff; border:1px solid rgba(2,24,54,.06);
    border-radius:14px; box-shadow:0 8px 24px rgba(2,24,54,.06);
    overflow:hidden; margin-top:10px;
  }
  .neko-card .neko-card__header{
    display:flex; align-items:center; justify-content:space-between;
    background: linear-gradient(90deg, var(--neko-primary-dark), var(--neko-primary));
    color:#fff; padding:14px 18px;
  }
  .neko-card__title{
    font-size:1.1rem; font-weight:600; letter-spacing:.2px; margin:0;
    display:flex; gap:10px; align-items:center;
  }
  .neko-card__body{ padding:18px; }

  /* Botones */
  .neko-actions .btn{ border-radius:10px; }
  .btn-primary{ 
    background: linear-gradient(135deg, var(--neko-primary-dark), var(--neko-primary));
    border:none; box-shadow:0 2px 8px rgba(21,101,192,.25);
  }
  .btn-primary:hover{ 
    background: linear-gradient(135deg, var(--neko-primary), var(--neko-primary-dark));
    transform:translateY(-1px);
  }

  /* ==================== KPI CARDS ==================== */
  .kpi-container{
    display:grid; 
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap:16px; 
    margin-bottom:20px;
  }
  .kpi-card{
    background:#fff; border-radius:14px; padding:18px; 
    box-shadow:0 2px 8px rgba(0,0,0,.08);
    border:1px solid rgba(0,0,0,.06);
    transition: transform 0.2s ease;
  }
  .kpi-card:hover{ transform: translateY(-2px); }
  
  .kpi-card__header{ display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px; }
  .kpi-card__title{ font-size:0.75rem; color:#64748b; text-transform:uppercase; font-weight:700; letter-spacing:0.5px; }
  .kpi-card__icon{ 
    width:40px; height:40px; border-radius:10px; 
    display:flex; align-items:center; justify-content:center; font-size:18px; 
  }
  .kpi-card__value{ font-size:1.6rem; font-weight:800; color:#1e293b; line-height:1.2; }
  .kpi-card__sub{ font-size:0.8rem; color:#64748b; margin-top:4px; display:flex; align-items:center; gap:4px; }
  
  /* Variaciones de color KPI */
  .kpi-blue .kpi-card__icon{ background:#eff6ff; color:#2563eb; }
  .kpi-green .kpi-card__icon{ background:#ecfdf5; color:#059669; }
  .kpi-orange .kpi-card__icon{ background:#fffbeb; color:#d97706; }
  .kpi-purple .kpi-card__icon{ background:#f3e8ff; color:#9333ea; }
  .kpi-red .kpi-card__icon{ background:#fef2f2; color:#dc2626; }

  /* ==================== FILTROS MODERNOS ==================== */
  .filter-bar {
    display: flex; align-items: center; gap: 12px; margin-bottom: 20px;
    flex-wrap: wrap; background: #fff; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0;
  }
  
  /* Status Pills */
  .status-group { display: flex; background: #f1f5f9; padding: 4px; border-radius: 8px; gap: 4px; }
  .status-btn {
    border: none; background: transparent; padding: 6px 12px; border-radius: 6px;
    font-size: 0.85rem; font-weight: 600; color: #64748b; cursor: pointer;
    display: flex; align-items: center; gap: 6px; transition: all 0.2s;
  }
  .status-btn:hover { color: #334155; }
  .status-btn.active { background: #fff; color: var(--neko-primary); box-shadow: 0 1px 3px rgba(0,0,0,0.1); }

  /* Date Range */
  .date-range-group { display:flex; align-items:center; gap:8px; background:#f8fafc; padding:4px 8px; border-radius:8px; border:1px solid #e2e8f0; }
  .date-input { border:none; background:transparent; font-size:0.85rem; color:#334155; width:110px; outline:none; }
  .date-separator { color:#94a3b8; font-size:0.8rem; }

  /* Selects */
  .filter-select {
    padding: 6px 24px 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px;
    font-size: 0.85rem; color: #334155; outline: none; cursor: pointer; background-color: #fff;
    appearance: none;
    background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2364748b%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
    background-repeat: no-repeat; background-position: right 8px center; background-size: 8px auto;
  }

  /* Search */
  .search-container { flex: 1; min-width: 200px; position: relative; }
  .search-container i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
  .search-input {
    width: 100%; padding: 8px 12px 8px 36px; border: 1px solid #e2e8f0; border-radius: 8px;
    font-size: 0.9rem; outline: none; transition: border-color 0.2s;
  }
  .search-input:focus { border-color: var(--neko-primary); }

  /* Export */
  .export-actions { display: flex; gap: 6px; }
  .btn-export {
    padding: 6px 12px; border: 1px solid #e2e8f0; background: #fff; border-radius: 6px;
    color: #64748b; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 6px;
  }
  .btn-export:hover { background: #f8fafc; color: #334155; border-color: #cbd5e1; }

  /* Tabla */
  #tbllistado thead th{ background: linear-gradient(135deg, #1e293b, #334155); color:#fff; font-weight:600; text-transform:uppercase; font-size:0.75rem; padding:12px; }
  #tbllistado tbody tr:hover{ background:#f8fafc; }
  
  /* Ocultar controles nativos DT */
  #tbllistado_wrapper .dataTables_filter, #tbllistado_wrapper .dataTables_length, #tbllistado_wrapper .dt-buttons { display: none !important; }

  /* Labels */
  .label{ padding:6px 12px; border-radius:6px; font-weight:600; font-size:0.75rem; }
  .bg-green{ background:#d1fae5 !important; color:#065f46 !important; }
  .bg-red{ background:#fee2e2 !important; color:#991b1b !important; }

  @media (max-width: 992px) {
    .filter-bar { flex-direction: column; align-items: stretch; }
    .status-group, .date-range-group, .search-container, .export-actions { width: 100%; }
    .kpi-container { grid-template-columns: 1fr; }
  }
</style>

<div class="content-wrapper">
  <section class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="neko-card">
          
          <!-- Header -->
          <div class="neko-card__header">
            <h1 class="neko-card__title"><i class="fa fa-shopping-cart"></i> Gestión de Ventas</h1>
            <div class="neko-actions">
              <a href="../reportes/rptventas.php" target="_blank" class="btn btn-light" style="background:#e3f2fd;border:0;color:#0d47a1;">
                <i class="fa fa-print"></i> Reporte General
              </a>
              <button class="btn btn-success" id="btnagregar" onclick="mostrarform(true)">
                <i class="fa fa-plus-circle"></i> Nueva Venta
              </button>
            </div>
          </div>

          <div class="neko-card__body panel-body" id="listadoregistros">
            
            <!-- KPIs -->
            <div class="kpi-container">
              <!-- 1. Ventas Hoy -->
              <div class="kpi-card kpi-green">
                <div class="kpi-card__header">
                  <div class="kpi-card__title">Ventas Hoy</div>
                  <div class="kpi-card__icon"><i class="fa fa-calendar-check-o"></i></div>
                </div>
                <div class="kpi-card__value">S/. <?= number_format($kpiVentasHoy, 2) ?></div>
                <div class="kpi-card__sub">Ingresos del día</div>
              </div>

              <!-- 2. Ventas Mes -->
              <div class="kpi-card kpi-blue">
                <div class="kpi-card__header">
                  <div class="kpi-card__title">Ventas Mes</div>
                  <div class="kpi-card__icon"><i class="fa fa-bar-chart"></i></div>
                </div>
                <div class="kpi-card__value">S/. <?= number_format($kpiVentasMes, 2) ?></div>
                <div class="kpi-card__sub">Acumulado mensual</div>
              </div>

              <!-- 3. Ticket Promedio -->
              <div class="kpi-card kpi-orange">
                <div class="kpi-card__header">
                  <div class="kpi-card__title">Ticket Promedio</div>
                  <div class="kpi-card__icon"><i class="fa fa-ticket"></i></div>
                </div>
                <div class="kpi-card__value">S/. <?= number_format($kpiTicketPromedio, 2) ?></div>
                <div class="kpi-card__sub">Gasto promedio/cliente</div>
              </div>

              <!-- 4. Tasa Éxito -->
              <div class="kpi-card kpi-purple">
                <div class="kpi-card__header">
                  <div class="kpi-card__title">Tasa de Éxito</div>
                  <div class="kpi-card__icon"><i class="fa fa-check-circle"></i></div>
                </div>
                <div class="kpi-card__value"><?= $kpiTasaExito ?>%</div>
                <div class="kpi-card__sub"><?= $totalAceptadas ?> de <?= $totalOps ?> ventas</div>
              </div>

              <!-- 5. Cliente Top -->
              <div class="kpi-card kpi-red">
                <div class="kpi-card__header">
                  <div class="kpi-card__title">Cliente Destacado</div>
                  <div class="kpi-card__icon"><i class="fa fa-trophy"></i></div>
                </div>
                <div class="kpi-card__value" style="font-size:1.1rem; margin-top:4px;"><?= strlen($kpiTopCliente) > 18 ? substr($kpiTopCliente,0,18).'...' : $kpiTopCliente ?></div>
                <div class="kpi-card__sub">S/. <?= number_format($kpiTopMonto, 2) ?> comprados</div>
              </div>
            </div>

            <!-- Filtros -->
            <div class="filter-bar">
              <!-- Estado -->
              <div class="status-group">
                <button type="button" class="status-btn active" id="filter-todos" onclick="filtrarEstado('todos')">Todos</button>
                <button type="button" class="status-btn" id="filter-aceptados" onclick="filtrarEstado('aceptados')"><i class="fa fa-check"></i> Aceptados</button>
                <button type="button" class="status-btn" id="filter-anulados" onclick="filtrarEstado('anulados')"><i class="fa fa-ban"></i> Anulados</button>
              </div>

              <!-- Fechas -->
              <div class="date-range-group">
                <i class="fa fa-calendar" style="color:#94a3b8;"></i>
                <input type="date" id="fecha_inicio" class="date-input" placeholder="Desde">
                <span class="date-separator">a</span>
                <input type="date" id="fecha_fin" class="date-input" placeholder="Hasta">
                <button class="btn btn-sm btn-primary" onclick="filtrarFecha()" style="padding:2px 8px; border-radius:4px;"><i class="fa fa-filter"></i></button>
                <button class="btn btn-sm btn-default" onclick="limpiarFecha()" style="padding:2px 8px; border-radius:4px;"><i class="fa fa-times"></i></button>
              </div>

              <!-- Comprobante -->
              <select id="tipo_comprobante_filter" class="filter-select" onchange="filtrarComprobante(this.value)">
                <option value="">Todos los comprobantes</option>
                <option value="Boleta">Boleta</option>
                <option value="Factura">Factura</option>
                <option value="Ticket">Ticket</option>
              </select>

              <!-- Buscador -->
              <div class="search-container">
                <i class="fa fa-search"></i>
                <input type="text" id="search-input" class="search-input" placeholder="Buscar cliente, usuario, serie...">
              </div>

              <!-- Mostrar filas -->
              <div style="display:flex; align-items:center; gap:8px; font-size:0.85rem; color:#64748b;">
                <span>Mostrar:</span>
                <select id="length-select" class="filter-select" style="width:auto; padding-right:24px;" onchange="cambiarLongitud(this.value)">
                  <option value="5">5</option>
                  <option value="10" selected>10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
              </div>

              <!-- Exportar -->
              <div class="export-actions">
                <button type="button" class="btn-export" onclick="exportarTabla('copy')" title="Copiar"><i class="fa fa-copy"></i> Copiar</button>
                <button type="button" class="btn-export" onclick="exportarTabla('excel')" title="Excel"><i class="fa fa-file-excel-o"></i> Excel</button>
                <button type="button" class="btn-export" onclick="exportarTabla('csv')" title="CSV"><i class="fa fa-file-text-o"></i> CSV</button>
                <button type="button" class="btn-export" onclick="exportarTabla('pdf')" title="PDF"><i class="fa fa-file-pdf-o"></i> PDF</button>
              </div>
            </div>

            <div class="table-responsive">
              <table id="tbllistado" class="table table-striped table-bordered table-condensed table-hover">
                <thead>
                  <th>Opciones</th>
                  <th>Fecha</th>
                  <th>Cliente</th>
                  <th>Usuario</th>
                  <th>Comprobante</th>
                  <th>Número</th>
                  <th>Total</th>
                  <th>Estado</th>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

          <!-- FORMULARIO (Mantenemos lógica original) -->
          <div class="neko-card__body panel-body" style="height: 100%; display:none;" id="formularioregistros">
            <form name="formulario" id="formulario" method="POST">
              <h4 class="section-title"><span class="dot"></span> Datos de la venta</h4>
              
              <div class="row">
                <div class="form-group col-lg-8 col-md-8 col-sm-8 col-xs-12">
                  <label>Cliente(*):</label>
                  <input type="hidden" name="idventa" id="idventa">
                  <select id="idcliente" name="idcliente" class="form-control selectpicker" data-live-search="true" required></select>
                </div>
                <div class="form-group col-lg-4 col-md-4 col-sm-4 col-xs-12">
                  <label>Fecha(*):</label>
                  <input type="date" class="form-control" name="fecha_hora" id="fecha_hora" required value="<?= $valToday ?>">
                </div>
              </div>

              <div class="row">
                <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                  <label>Tipo Comprobante(*):</label>
                  <select name="tipo_comprobante" id="tipo_comprobante" class="form-control selectpicker" required>
                    <option value="Boleta">Boleta</option>
                    <option value="Factura">Factura</option>
                    <option value="Ticket">Ticket</option>
                  </select>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                  <label>Serie:</label>
                  <input type="text" class="form-control" name="serie_comprobante" id="serie_comprobante" readonly>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                  <label>Número:</label>
                  <input type="text" class="form-control" name="num_comprobante" id="num_comprobante" readonly>
                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">
                  <label>Impuesto:</label>
                  <input type="number" class="form-control" name="impuesto" id="impuesto" step="0.01" readonly>
                </div>
              </div>

              <div class="row">
                <div class="form-group col-lg-3 col-md-3 col-sm-6 col-xs-12">
                  <a data-toggle="modal" href="#myModal">
                    <button id="btnAgregarArt" type="button" class="btn btn-primary"><span class="fa fa-plus"></span> Agregar Artículos</button>
                  </a>
                </div>
              </div>

              <h4 class="section-title"><span class="dot"></span> Detalle de la venta</h4>
              <div class="col-lg-12 col-sm-12 col-md-12 col-xs-12 table-responsive">
                <table id="detalles" class="table table-striped table-bordered table-condensed table-hover">
                  <thead>
                    <th>Opciones</th>
                    <th>Artículo</th>
                    <th>Cantidad</th>
                    <th>Precio Venta</th>
                    <th>Descuento</th>
                    <th>Subtotal</th>
                  </thead>
                  <tfoot>
                    <th>TOTAL</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th>
                      <h4 id="total">S/. 0.00</h4>
                      <input type="hidden" name="total_venta" id="total_venta">
                    </th>
                  </tfoot>
                  <tbody></tbody>
                </table>
              </div>

              <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" style="margin-top:12px;">
                <button class="btn btn-primary" type="submit" id="btnGuardar"><i class="fa fa-save"></i> Guardar</button>
                <button id="btnCancelar" class="btn btn-danger" onclick="cancelarform()" type="button"><i class="fa fa-arrow-circle-left"></i> Cancelar</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- Modal Artículos Moderno -->
<style>
  /* Estilos para el Modal Moderno */
  .modal-modern .modal-content {
    border: none;
    border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    overflow: hidden;
  }
  .modal-modern .modal-header {
    background: linear-gradient(135deg, var(--neko-primary-dark), var(--neko-primary));
    padding: 12px 20px;
    border-bottom: none;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .modal-modern .modal-title {
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .modal-modern .close {
    color: white;
    opacity: 0.8;
    text-shadow: none;
    font-size: 1.5rem;
    transition: opacity 0.2s;
  }
  .modal-modern .close:hover {
    opacity: 1;
    color: #fff;
  }
  .modal-modern .modal-body {
    padding: 16px;
    background: #f8fafc;
  }
  .modal-modern .modal-footer {
    background: #fff;
    border-top: 1px solid #e2e8f0;
    padding: 10px 20px;
  }
  
  /* Ajuste para tabla dentro del modal */
  #tblarticulos_wrapper .dataTables_filter input {
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 8px 12px;
    outline: none;
    transition: all 0.2s;
  }
  #tblarticulos_wrapper .dataTables_filter input:focus {
    border-color: var(--neko-primary);
    box-shadow: 0 0 0 3px rgba(21, 101, 192, 0.1);
  }
</style>

<div class="modal fade modal-modern" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width: 90%; max-width: 1100px;">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">
          <i class="fa fa-cubes"></i> Seleccionar Artículo
        </h4>
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      </div>
      
      <div class="modal-body">
        <div class="alert alert-info" style="border-radius: 8px; background: #eff6ff; border: 1px solid #dbeafe; color: #1e40af; margin-bottom: 16px;">
          <i class="fa fa-info-circle"></i> <strong>Tip:</strong> Utiliza los filtros o el buscador para encontrar productos rápidamente.
        </div>

        <!-- Filtros -->
        <div class="row" style="margin-bottom: 16px;">
          <div class="col-md-4 col-sm-6">
            <div class="form-group">
              <label>Categoría</label>
              <select id="filtro_categoria" class="form-control selectpicker" data-live-search="true">
                <option value="">Todas</option>
              </select>
            </div>
          </div>
          <div class="col-md-4 col-sm-6">
            <div class="form-group">
              <label>Marca</label>
              <select id="filtro_marca" class="form-control selectpicker" data-live-search="true">
                <option value="">Todas</option>
              </select>
            </div>
          </div>
        </div>

        <div class="table-responsive" style="background: white; padding: 16px; border-radius: 12px; border: 1px solid #e2e8f0;">
          <table id="tblarticulos" class="table table-striped table-bordered table-hover" style="width:100%">
            <thead>
              <th>Opciones</th>
              <th>Nombre</th>
              <th>Categoría</th>
              <th>Marca</th>
              <th>Código</th>
              <th>Stock</th>
              <th>Precio Venta</th>
              <th>Imagen</th>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
      
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal" style="border-radius: 8px; font-weight: 600;">Cerrar</button>
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
<script type="text/javascript" src="scripts/venta.js"></script>
<?php ob_end_flush(); ?>
