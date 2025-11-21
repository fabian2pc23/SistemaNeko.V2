<?php
// vistas/ingreso.php - Diseño unificado con articulo.php + KPIs
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

// Header del layout
require 'header.php';

// === Permiso del módulo (COMPRAS) ===
$canCompras = !empty($_SESSION['compras']) && (int)$_SESSION['compras'] === 1;

if ($canCompras) {
    require_once "../config/Conexion.php";

    // ==================== KPIs ====================
    // 1. Compras Hoy
    $sqlHoy = "SELECT IFNULL(SUM(total_compra),0) as total, COUNT(*) as cantidad 
               FROM ingreso 
               WHERE DATE(fecha_hora) = CURDATE() AND estado='Aceptado'";
    $rsHoy  = ejecutarConsultaSimpleFila($sqlHoy);
    $kpiHoyTotal = $rsHoy ? (float)$rsHoy['total'] : 0;
    $kpiHoyCant  = $rsHoy ? (int)$rsHoy['cantidad'] : 0;

    // 2. Compras Mes
    $sqlMes = "SELECT IFNULL(SUM(total_compra),0) as total 
               FROM ingreso 
               WHERE MONTH(fecha_hora) = MONTH(CURDATE()) AND YEAR(fecha_hora) = YEAR(CURDATE()) AND estado='Aceptado'";
    $rsMes  = ejecutarConsultaSimpleFila($sqlMes);
    $kpiMesTotal = $rsMes ? (float)$rsMes['total'] : 0;

    // 3. Total Histórico
    $sqlTotal = "SELECT IFNULL(SUM(total_compra),0) as total FROM ingreso WHERE estado='Aceptado'";
    $rsTotal  = ejecutarConsultaSimpleFila($sqlTotal);
    $kpiTotalHist = $rsTotal ? (float)$rsTotal['total'] : 0;

    // 4. Estado (Aceptados vs Anulados)
    $sqlEstado = "SELECT 
                    SUM(CASE WHEN estado='Aceptado' THEN 1 ELSE 0 END) as aceptados,
                    SUM(CASE WHEN estado='Anulado' THEN 1 ELSE 0 END) as anulados
                  FROM ingreso";
    $rsEstado = ejecutarConsultaSimpleFila($sqlEstado);
    $kpiAceptados = $rsEstado ? (int)$rsEstado['aceptados'] : 0;
    $kpiAnulados  = $rsEstado ? (int)$rsEstado['anulados'] : 0;

    $nekoPrimary     = '#1565c0';
    $nekoPrimaryDark = '#0d47a1';
?>
<!-- ====== Estilos unificados (basado en articulo.php) ====== -->
<style>
  :root{
    --neko-primary: <?= $nekoPrimary ?>;
    --neko-primary-dark: <?= $nekoPrimaryDark ?>;
    --neko-bg:#f5f7fb;
  }
  .content-wrapper{ background:var(--neko-bg); }

  .neko-card{
    background:#fff; border:1px solid rgba(2,24,54,.06);
    border-radius:14px; box-shadow:0 8px 24px rgba(2,24,54,.06);
    overflow:hidden; margin-top:10px;
  }
  .neko-card__header{
    display:flex; align-items:center; justify-content:space-between;
    background:linear-gradient(90deg,var(--neko-primary-dark),var(--neko-primary));
    color:#fff; padding:14px 18px;
  }
  .neko-card__title{
    font-size:1.1rem; font-weight:600; letter-spacing:.2px; margin:0;
    display:flex; gap:10px; align-items:center;
  }
  .neko-actions .btn{ border-radius:10px; }
  .neko-card__body{ padding:18px; }

  /* Botones primarios */
  .btn-primary{ 
    background: linear-gradient(135deg, var(--neko-primary-dark), var(--neko-primary));
    border:none;
    box-shadow:0 2px 8px rgba(21,101,192,.25);
  }
  .btn-primary:hover{ 
    background: linear-gradient(135deg, var(--neko-primary), var(--neko-primary-dark));
    box-shadow:0 4px 12px rgba(21,101,192,.35);
    transform:translateY(-1px);
  }

  /* ==================== KPI CARDS ==================== */
  .kpi-container{
    display:grid; 
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap:16px; 
    margin-bottom:20px;
  }
  .kpi-card{
    background:#fff; 
    border-radius:14px; 
    padding:18px; 
    box-shadow:0 2px 8px rgba(0,0,0,.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    border:1px solid rgba(0,0,0,.06);
  }
  .kpi-card:hover{
    transform: translateY(-2px);
    box-shadow:0 4px 16px rgba(0,0,0,.12);
  }
  .kpi-card__header{
    display:flex; align-items:center; justify-content:space-between; margin-bottom:4px;
  }
  .kpi-card__icon{
    width:48px; height:48px; border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    font-size:22px;
  }
  .kpi-card__title{
    font-size:0.75rem; color:#64748b; text-transform:uppercase; 
    font-weight:600; letter-spacing:0.5px; margin:0 0 8px 0;
  }
  .kpi-card__value{
    font-size:1.8rem; font-weight:700; margin:0; line-height:1;
  }
  .kpi-card__sub{
    font-size:0.85rem; color:#64748b; margin-top:4px; font-weight:500;
  }

  /* Colores KPI */
  .kpi-card--primary .kpi-card__icon{ background:#dbeafe; color:#1e40af; }
  .kpi-card--primary .kpi-card__value{ color:#1e3a8a; }

  .kpi-card--success .kpi-card__icon{ background:#d1fae5; color:#059669; }
  .kpi-card--success .kpi-card__value{ color:#065f46; }

  .kpi-card--info .kpi-card__icon{ background:#e0f2fe; color:#0284c7; }
  .kpi-card--info .kpi-card__value{ color:#0c4a6e; }

  .kpi-card--purple .kpi-card__icon{ background:#f3e8ff; color:#9333ea; }
  .kpi-card--purple .kpi-card__value{ color:#581c87; }

  /* Dual KPI */
  .kpi-card__dual{ display:flex; gap:16px; align-items:center; }
  .kpi-dual-item{ flex:1; }
  .kpi-dual-item__label{ font-size:0.7rem; color:#64748b; margin-bottom:4px; text-transform:uppercase; font-weight:600; }
  .kpi-dual-item__value{ font-size:1.5rem; font-weight:700; }
  .kpi-dual-divider{ width:1px; height:40px; background:#e2e8f0; }

  /* ==================== FILTROS + EXPORTS ==================== */
  .filter-bar{
    display:flex; align-items:center; gap:12px; margin-bottom:20px; flex-wrap:wrap;
  }
  .filter-group{
    display:flex; gap:8px; background:#f8fafc; padding:6px; border-radius:12px; border:1px solid #e2e8f0;
  }
  .filter-btn{
    padding:8px 18px; border:none; background:transparent;
    border-radius:8px; font-size:0.85rem; font-weight:600;
    cursor:pointer; transition: all 0.2s ease; color:#64748b;
    display:flex; align-items:center; gap:6px;
  }
  .filter-btn:hover{ background:#e2e8f0; color:#334155; }
  .filter-btn.active{
    background: linear-gradient(135deg, var(--neko-primary-dark), var(--neko-primary));
    color:#fff; box-shadow:0 2px 8px rgba(21,101,192,.25);
  }

  .search-input-wrapper{ position:relative; flex:1; max-width:350px; }
  .search-input-wrapper i{ position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:1rem; }
  .search-input{
    width:100%; padding:10px 16px 10px 42px; border:1px solid #e2e8f0;
    border-radius:10px; font-size:0.88rem; transition: all 0.2s ease;
  }
  .search-input:focus{ outline:none; border-color:var(--neko-primary); box-shadow:0 0 0 3px rgba(21,101,192,.1); }

  .date-range-group{
    display:flex; align-items:center; gap:8px; background:#fff; 
    padding:6px 12px; border:1px solid #e2e8f0; border-radius:10px;
  }
  .date-input{
    border:none; font-size:0.85rem; color:#475569; font-weight:500; outline:none; width:130px;
  }

  .export-group{ display:flex; gap:8px; margin-left:auto; }
  .export-btn{
    padding:8px 16px; border:1px solid #e2e8f0; background:#fff;
    border-radius:8px; font-size:0.82rem; font-weight:600;
    cursor:pointer; transition: all 0.2s ease; color:#475569;
    display:flex; align-items:center; gap:6px;
  }
  .export-btn:hover{ background:#f8fafc; border-color:#cbd5e1; transform:translateY(-1px); }

  /* Tabla */
  #tbllistado thead th{ 
    background: linear-gradient(135deg, #1e293b, #334155);
    color:#fff; font-weight:600; text-transform:uppercase;
    font-size:0.75rem; letter-spacing:0.5px; padding:14px 12px;
  }
  #tbllistado tfoot th{ background:#f8fafc; font-weight:600; }
  #tbllistado tbody tr:hover{ background:#f8fafc; }

  /* Labels */
  .label{ padding:6px 12px; border-radius:6px; font-weight:600; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.3px; }
  .bg-green{ background:#d1fae5 !important; color:#065f46 !important; }
  .bg-red{ background:#fee2e2 !important; color:#991b1b !important; }

  /* Formulario */
  .section-title{
    font-weight:600; color:#0b2752; margin:16px 0 10px; display:flex; align-items:center; gap:8px;
  }
  .section-title .dot{ width:8px; height:8px; border-radius:999px; background:var(--neko-primary); display:inline-block; }
  
  /* Ocultar controles nativos de DataTables */
  #tbllistado_wrapper .dataTables_filter,
  #tbllistado_wrapper .dataTables_length,
  #tbllistado_wrapper .dt-buttons{ display:none !important; }

  /* ==================== RESPONSIVE ==================== */
  @media (max-width: 768px) {
    .neko-card__header{ flex-direction:column; align-items:stretch; gap:12px; text-align:center; }
    .neko-card__title{ justify-content:center; font-size:1.3rem; }
    .neko-actions{ display:flex; flex-direction:column; gap:8px; }
    .neko-actions .btn{ width:100%; display:flex; justify-content:center; align-items:center; gap:6px; }
    .neko-actions .btn-group{ display:flex; flex-direction:column; width:100%; }
    .neko-actions .btn-group .btn{ border-radius:8px !important; margin-bottom:4px; }

    .filter-bar{ flex-direction:column; align-items:stretch; gap:12px; }
    .filter-group{ overflow-x:auto; padding-bottom:4px; justify-content:center; }
    .search-input-wrapper{ max-width:100%; }
    .date-range-group{ flex-direction:column; width:100%; }
    .date-input{ width:100%; text-align:center; border-bottom:1px solid #f1f5f9; padding:4px 0; }
    .date-input:last-child{ border-bottom:none; }
    
    .export-group{ justify-content:center; margin-left:0; }
    .export-btn{ flex:1; justify-content:center; }

    .kpi-container{ grid-template-columns: 1fr; }
    
    /* Ajuste tabla */
    .dataTables_wrapper{ overflow-x:auto; }
  }
</style>

<!--Contenido-->
<div class="content-wrapper">
  <section class="content">
    <div class="row">
      <div class="col-md-12">

        <div class="neko-card">

          <!-- Header visual -->
          <div class="neko-card__header">
            <h1 class="neko-card__title"><i class="fa fa-truck"></i> Ingresos</h1>
            <div class="neko-actions">
              <a href="../reportes/rptingresos.php" target="_blank" class="btn btn-light" style="background:#e3f2fd;border:0;color:#0d47a1;">
                <i class="fa fa-clipboard"></i> Reporte
              </a>
              <div class="btn-group" id="btnagregar">
                <button class="btn btn-success" id="btnIngresoExistente" onclick="nuevoIngresoExistente()">
                  <i class="fa fa-plus-circle"></i> Reposición
                </button>
                <button class="btn btn-primary" id="btnIngresoNuevo" onclick="nuevoIngresoNuevo()">
                  <i class="fa fa-star"></i> Nuevo producto
                </button>
                <a href="articulo.php?msg=update_price" class="btn btn-info">
                  <i class="fa fa-tags"></i> Listar precio de venta
                </a>
              </div>
            </div>
          </div>

          <!-- LISTADO -->
          <div class="neko-card__body panel-body table-responsive" id="listadoregistros">

            <!-- KPIs -->
            <div class="kpi-container">
              <!-- 1. Compras Hoy -->
              <div class="kpi-card kpi-card--primary">
                <div class="kpi-card__title">Compras Hoy</div>
                <div class="kpi-card__header">
                  <div>
                    <h2 class="kpi-card__value">S/. <?= number_format($kpiHoyTotal, 2) ?></h2>
                    <div class="kpi-card__sub"><?= $kpiHoyCant ?> ingresos hoy</div>
                  </div>
                  <div class="kpi-card__icon"><i class="fa fa-calendar-check-o"></i></div>
                </div>
              </div>

              <!-- 2. Compras Mes -->
              <div class="kpi-card kpi-card--info">
                <div class="kpi-card__title">Compras Mes</div>
                <div class="kpi-card__header">
                  <div>
                    <h2 class="kpi-card__value">S/. <?= number_format($kpiMesTotal, 2) ?></h2>
                  </div>
                  <div class="kpi-card__icon"><i class="fa fa-line-chart"></i></div>
                </div>
              </div>

              <!-- 3. Total Histórico -->
              <div class="kpi-card kpi-card--purple">
                <div class="kpi-card__title">Total Histórico</div>
                <div class="kpi-card__header">
                  <div>
                    <h2 class="kpi-card__value">S/. <?= number_format($kpiTotalHist, 2) ?></h2>
                  </div>
                  <div class="kpi-card__icon"><i class="fa fa-money"></i></div>
                </div>
              </div>

              <!-- 4. Estado -->
              <div class="kpi-card kpi-card--success">
                <div class="kpi-card__title">Estado Ingresos</div>
                <div class="kpi-card__dual">
                  <div class="kpi-dual-item">
                    <div class="kpi-dual-item__label">Aceptados</div>
                    <div class="kpi-dual-item__value" style="color:#059669;"><?= $kpiAceptados ?></div>
                  </div>
                  <div class="kpi-dual-divider"></div>
                  <div class="kpi-dual-item">
                    <div class="kpi-dual-item__label">Anulados</div>
                    <div class="kpi-dual-item__value" style="color:#dc2626;"><?= $kpiAnulados ?></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- FILTROS + EXPORT -->
            <div class="filter-bar">
              <div class="filter-group">
                <button type="button" class="filter-btn active" id="filter-todos">
                  <i class="fa fa-th"></i> Todos
                </button>
                <button type="button" class="filter-btn" id="filter-aceptados">
                  <i class="fa fa-check-circle"></i> Aceptados
                </button>
                <button type="button" class="filter-btn" id="filter-anulados">
                  <i class="fa fa-times-circle"></i> Anulados
                </button>
              </div>

              <div class="date-range-group">
                <i class="fa fa-calendar" style="color:#94a3b8;"></i>
                <input type="date" id="filtro_desde" class="date-input" placeholder="Desde">
                <span style="color:#cbd5e1;">|</span>
                <input type="date" id="filtro_hasta" class="date-input" placeholder="Hasta">
                <button type="button" id="btnLimpiarFiltro" class="btn btn-xs btn-default" title="Limpiar fechas">
                  <i class="fa fa-eraser"></i>
                </button>
              </div>

              <div class="search-input-wrapper">
                <i class="fa fa-search"></i>
                <input type="text" id="search-input" class="search-input" placeholder="Buscar proveedor, usuario, documento...">
              </div>

              <div class="export-group">
                <button type="button" class="export-btn" onclick="exportarTabla('copy')"><i class="fa fa-copy"></i></button>
                <button type="button" class="export-btn" onclick="exportarTabla('excel')"><i class="fa fa-file-excel-o"></i></button>
                <button type="button" class="export-btn" onclick="exportarTabla('csv')"><i class="fa fa-file-text-o"></i></button>
                <button type="button" class="export-btn" onclick="exportarTabla('pdf')"><i class="fa fa-file-pdf-o"></i></button>
              </div>
            </div>

            <table id="tbllistado" class="table table-striped table-bordered table-condensed table-hover" style="width:100%">
              <thead>
                <th>Opciones</th>
                <th>Fecha</th>
                <th>Proveedor</th>
                <th>Usuario</th>
                <th>Documento</th>
                <th>Número</th>
                <th>Total Compra</th>
                <th>Estado</th>
              </thead>
              <tbody></tbody>
              <tfoot>
                <th>Opciones</th>
                <th>Fecha</th>
                <th>Proveedor</th>
                <th>Usuario</th>
                <th>Documento</th>
                <th>Número</th>
                <th>Total Compra</th>
                <th>Estado</th>
              </tfoot>
            </table>
          </div>

          <!-- FORMULARIO -->
          <div class="neko-card__body panel-body" style="height: 100%;" id="formularioregistros">
            <form name="formulario" id="formulario" method="POST">
              <h4 class="section-title"><span class="dot"></span> Datos del ingreso</h4>

              <div class="row">
                <div class="form-group col-lg-8 col-md-8 col-sm-8 col-xs-12">
                  <label>Proveedor(*):</label>
                  <input type="hidden" name="idingreso" id="idingreso">
                  <input type="hidden" name="modo_ingreso" id="modo_ingreso" value="existente">
                  <select id="idproveedor" name="idproveedor" class="form-control selectpicker" data-live-search="true" required></select>
                </div>

                <div class="form-group col-lg-4 col-md-4 col-sm-4 col-xs-12">
                  <label>Fecha(*):</label>
                  <input type="date" class="form-control" name="fecha_hora" id="fecha_hora" required value="<?= $valToday ?>" min="<?= $valMin ?>" max="<?= $valMax ?>">
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

                <div class="form-group col-lg-2 col-md-2 col-sm-6 col-xs-12">
                  <label>Serie:</label>
                  <input type="input" class="form-control" name="serie_comprobante" id="serie_comprobante" maxlength="7" onkeypress='return event.charCode >= 48 && event.charCode <= 57' placeholder="Serie">
                </div>

                <div class="form-group col-lg-2 col-md-2 col-sm-6 col-xs-12">
                  <label>Número:</label>
                  <input type="input" class="form-control" name="num_comprobante" id="num_comprobante" maxlength="10" onkeypress='return event.charCode >= 48 && event.charCode <= 57' placeholder="Número" required>
                </div>

                <div class="form-group col-lg-2 col-md-2 col-sm-6 col-xs-12">
                  <label>Impuesto:</label>
                  <input type="text" class="form-control" name="impuesto" id="impuesto" required value="18" onchange="modificarSubototales()">
                </div>
              </div>

              <div class="row">
                <div class="form-group col-lg-3 col-md-3 col-sm-6 col-xs-12">
                  <a data-toggle="modal" href="#myModal">
                    <button id="btnAgregarArt" type="button" class="btn btn-primary">
                      <span class="fa fa-plus"></span> Agregar Artículos
                    </button>
                  </a>
                </div>
              </div>

              <h4 class="section-title"><span class="dot"></span> Detalle del ingreso</h4>

              <div class="col-lg-12 col-sm-12 col-md-12 col-xs-12 table-responsive">
                <table id="detalles" class="table table-striped table-bordered table-condensed table-hover">
                  <thead>
                    <th>Opciones</th>
                    <th>Artículo</th>
                    <th>Cantidad</th>
                    <th>Precio Compra</th>
                    <th>Subtotal</th>
                  </thead>
                  <tfoot>
                    <tr>
                      <th colspan="4" style="text-align:right">SUBTOTAL (Neto)</th>
                      <th>
                        <h4 id="total_neto_h4">S/. 0.00</h4>
                        <input type="hidden" name="total_neto" id="total_neto">
                      </th>
                    </tr>
                    <tr>
                      <th colspan="4" style="text-align:right">
                        <span id="mostrar_impuesto">IGV (18%)</span>
                      </th>
                      <th>
                        <h4 id="total_impuesto_h4">S/. 0.00</h4>
                        <input type="hidden" name="monto_impuesto" id="monto_impuesto" value="0.00">
                      </th>
                    </tr>
                    <tr>
                      <th colspan="4" style="text-align:right">TOTAL A PAGAR (Bruto)</th>
                      <th>
                        <h4 id="total">S/. 0.00</h4>
                        <input type="hidden" name="total_compra" id="total_compra">
                      </th>
                    </tr>
                  </tfoot>
                  <tbody></tbody>
                </table>
              </div>

              <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" style="margin-top:12px;">
                <button class="btn btn-primary" type="submit" id="btnGuardar">
                  <i class="fa fa-save"></i> Guardar
                </button>
                <button id="btnCancelar" class="btn btn-danger" onclick="cancelarform()" type="button">
                  <i class="fa fa-arrow-circle-left"></i> Cancelar
                </button>
              </div>
            </form>
          </div>

        </div><!-- /neko-card -->
      </div><!-- /.col -->
    </div><!-- /.row -->
  </section><!-- /.content -->
</div><!-- /.content-wrapper -->

<!-- Modal selección de artículos -->
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
              <th>Precio Compra</th>
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

<!-- Modal Nuevo Artículo (Quick Add) -->
<div class="modal fade modal-modern" id="modalNuevoArticulo" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width: 85%; max-width: 900px;">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">
          <i class="fa fa-plus-circle"></i> Nuevo Artículo
        </h4>
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
      </div>
      
      <div class="modal-body">
        <form name="formularioArticulo" id="formularioArticulo" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="idarticulo" id="idarticulo_new">
          <!-- Precios ocultos por defecto en creación rápida -->
          <input type="hidden" name="precio_compra" id="precio_compra_new" value="0.00">
          <input type="hidden" name="precio_venta" id="precio_venta_new" value="0.00">
          <input type="hidden" name="stock" id="stock_new" value="0">

          <div class="row">
            <div class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
              <label>Nombre(*):</label>
              <input type="text" class="form-control" name="nombre" id="nombre_new" maxlength="100" placeholder="Nombre del artículo" required>
            </div>
            <div class="form-group col-lg-3 col-md-3 col-sm-12 col-xs-12">
              <label>Categoría(*):</label>
              <select id="idcategoria_new" name="idcategoria" class="form-control selectpicker" data-live-search="true" required></select>
            </div>
            <div class="form-group col-lg-3 col-md-3 col-sm-12 col-xs-12">
              <label>Marca:</label>
              <select id="idmarca_new" name="idmarca" class="form-control selectpicker" data-live-search="true"></select>
            </div>
          </div>

          <div class="row">
            <div class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
              <label>Descripción:</label>
              <textarea class="form-control" name="descripcion" id="descripcion_new" rows="3" maxlength="256" placeholder="Detalle o especificación del artículo"></textarea>
            </div>
            <div class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
              <label>Código de barras:</label>
              <div class="input-group">
                <input type="text" class="form-control" name="codigo" id="codigo_new" placeholder="EAN/UPC (8 a 13 dígitos)">
                <span class="input-group-btn">
                  <button class="btn btn-success" type="button" onclick="generarbarcodeNew()">
                    <i class="fa fa-barcode"></i> Generar
                  </button>
                  <button class="btn btn-info" type="button" onclick="imprimirNew()">
                    <i class="fa fa-print"></i> Imprimir
                  </button>
                </span>
              </div>
              <div id="print_new" style="margin-top:8px; display:none;">
                <svg id="barcode_new"></svg>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
              <label>Imagen:</label>
              <input type="file" class="form-control" name="imagen" id="imagen_new" accept="image/x-png,image/gif,image/jpeg,image/jpg,image/png">
              <input type="hidden" name="imagenactual" id="imagenactual_new">
              <img src="" id="imagenmuestra_new" style="width:150px;height:120px;object-fit:cover;border:1px solid #e5e7eb;border-radius:6px;margin-top:8px;display:none;">
            </div>
          </div>

          <div class="row">
            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12" style="text-align: right;">
              <button class="btn btn-primary" type="submit" id="btnGuardarArticulo">
                <i class="fa fa-save"></i> Guardar
              </button>
              <button type="button" class="btn btn-danger" data-dismiss="modal">
                <i class="fa fa-arrow-circle-left"></i> Cancelar
              </button>
            </div>
          </div>
        </form>
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
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
<script type="text/javascript" src="scripts/ingreso.js"></script>
<?php
ob_end_flush();
?>
