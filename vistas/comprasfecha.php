<?php
// vistas/comprasfecha.php
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

if (!isset($_SESSION["nombre"])) { header("Location: login.html"); exit; }

require 'header.php';

if (!empty($_SESSION['consultac']) && (int)$_SESSION['consultac'] === 1) {
  $hoy = date("Y-m-d");
?>
<style>
  :root{
    --neko-primary:#1565c0;
    --neko-primary-dark:#0d47a1;
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
    font-size:1.05rem; font-weight:600; letter-spacing:.2px; margin:0;
    display:flex; gap:10px; align-items:center;
  }
  .neko-card__body{ padding:18px; }

  /* KPI Cards Modernos */
  .kpi-container {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
  }
  .kpi-card {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border: 1px solid #e2e8f0;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.2s;
  }
  .kpi-card:hover { transform: translateY(-2px); }
  .kpi-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
  .kpi-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
  .kpi-value { font-size: 1.6rem; font-weight: 700; color: #1e293b; margin: 0; }
  .kpi-label { font-size: 0.85rem; color: #64748b; font-weight: 500; }
  
  .kpi-blue .kpi-icon { background: #eff6ff; color: #3b82f6; }
  .kpi-green .kpi-icon { background: #f0fdf4; color: #22c55e; }
  .kpi-purple .kpi-icon { background: #f3e8ff; color: #a855f7; }
  .kpi-orange .kpi-icon { background: #fff7ed; color: #f97316; }
  .kpi-red .kpi-icon { background: #fef2f2; color: #ef4444; }

  /* Chart Grid */
  .chart-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 15px;
    margin-bottom: 20px;
  }
  .chart-box {
    background: #fff;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border: 1px solid #e2e8f0;
    height: 350px;
    position: relative;
    display: flex;
    flex-direction: column;
  }
  .chart-header-box {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
  }
  .chart-header-box h5 { margin: 0; font-weight: 600; color: #334155; font-size: 1rem; }
  .chart-filter {
    border: 1px solid #dbe3ef;
    border-radius: 6px;
    padding: 2px 6px;
    font-size: 0.8rem;
    color: #64748b;
    background: #fff;
  }

  /* Chart Container Main */
  .chart-container-main {
    background: #fff;
    border-radius: 14px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    border: 1px solid #e2e8f0;
    margin-bottom: 20px;
    height: 400px;
    position: relative;
  }

  /* Filtros */
  .filters-row{
    display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-bottom:14px;
  }
  .filters-row label{ font-weight:600; color:#0b2752; display:block; margin-bottom:6px; }
  .filters-row .form-control{ height:36px; padding:6px 10px; border:1px solid #dbe3ef; border-radius:10px; }

  /* Quick Filters Toolbar */
  .quick-filters { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
  .quick-filters .btn { border-radius: 20px; font-weight: 500; padding: 6px 16px; }

  /* Toolbar */
  .dt-toolbar{
    display:flex; align-items:center; justify-content:space-between;
    gap:14px;
    flex-wrap:nowrap;
    margin-bottom:10px;
  }
  .dt-left{ display:flex; align-items:center; gap:8px; }
  .dt-right{ display:flex; align-items:center; gap:8px; }
  .dt-right label{ margin:0; font-weight:600; color:#0b2752; }
  .dt-right .form-control{ height:32px; padding:4px 8px; border:1px solid #dbe3ef; border-radius:10px; }

  /* Buttons */
  .dt-buttons-holder{ display:flex; align-items:center; gap:8px; min-height:38px; }
  .dt-buttons{ display:flex; gap:8px; }
  .dt-buttons .dt-button{ margin:0; }

  /* Tabla */
  #tbllistado thead th{ background: linear-gradient(135deg, #1e293b, #334155); color:#fff; font-weight:600; text-transform:uppercase; font-size:0.75rem; padding:14px 12px; }
  #tbllistado tfoot th{ background:#f8fafc; }
  #tbllistado tbody tr:hover{ background:#f8fafc; }

  #tbllistado_wrapper .dataTables_length,
  #tbllistado_wrapper .dataTables_filter{ display:none !important; }

  @media (max-width: 1400px){
     .chart-grid { grid-template-columns: repeat(2, 1fr); }
  }
  @media (max-width: 1200px){
    .chart-grid { grid-template-columns: repeat(2, 1fr); }
  }
  @media (max-width: 992px){
    .dt-toolbar{ flex-wrap:wrap; }
    .kpi-container { grid-template-columns: 1fr 1fr; }
    .chart-grid { grid-template-columns: 1fr; }
  }
</style>

<div class="content-wrapper">
  <section class="content">
    <div class="row">
      <div class="col-md-12">

        <div class="neko-card">
          <div class="neko-card__header">
            <h1 class="neko-card__title">
              <i class="fa fa-calendar-check-o"></i> Dashboard de Compras
            </h1>
          </div>

          <div class="neko-card__body">
            <!-- Quick Filters -->
            <div class="quick-filters">
              <button class="btn btn-default btn-sm" onclick="aplicarFiltro('hoy')">Hoy</button>
              <button class="btn btn-default btn-sm" onclick="aplicarFiltro('semana')">Esta Semana</button>
              <button class="btn btn-default btn-sm" onclick="aplicarFiltro('mes')">Este Mes</button>
              <button class="btn btn-default btn-sm" onclick="aplicarFiltro('trimestre')">Este Trimestre</button>
              <button class="btn btn-default btn-sm" onclick="aplicarFiltro('anio')">Este Año</button>
            </div>

            <!-- Filtros Manuales -->
            <div class="filters-row">
              <div>
                <label for="fecha_inicio">Fecha Inicio</label>
                <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio" value="<?php echo $hoy; ?>">
              </div>
              <div>
                <label for="fecha_fin">Fecha Fin</label>
                <input type="date" class="form-control" name="fecha_fin" id="fecha_fin" value="<?php echo $hoy; ?>">
              </div>
            </div>

            <!-- KPIs Modernos -->
            <div class="kpi-container">
              <div class="kpi-card kpi-blue" data-kpi-type="total" style="cursor:pointer;">
                <div class="kpi-header">
                  <span class="kpi-label">Total Compras</span>
                  <div class="kpi-icon"><i class="fa fa-money"></i></div>
                </div>
                <h3 class="kpi-value" id="kpi_total_compras">S/. 0.00</h3>
              </div>
              <div class="kpi-card kpi-green" data-kpi-type="transacciones" style="cursor:pointer;">
                <div class="kpi-header">
                  <span class="kpi-label">Transacciones</span>
                  <div class="kpi-icon"><i class="fa fa-shopping-cart"></i></div>
                </div>
                <h3 class="kpi-value" id="kpi_num_transacciones">0</h3>
              </div>
              <div class="kpi-card kpi-purple" data-kpi-type="ticket" style="cursor:pointer;">
                <div class="kpi-header">
                  <span class="kpi-label">Ticket Promedio</span>
                  <div class="kpi-icon"><i class="fa fa-line-chart"></i></div>
                </div>
                <h3 class="kpi-value" id="kpi_ticket_promedio">S/. 0.00</h3>
              </div>
              <div class="kpi-card kpi-orange" data-kpi-type="max" style="cursor:pointer;">
                <div class="kpi-header">
                  <span class="kpi-label">Compra Máxima</span>
                  <div class="kpi-icon"><i class="fa fa-arrow-up"></i></div>
                </div>
                <h3 class="kpi-value" id="kpi_compra_maxima">S/. 0.00</h3>
              </div>
              <div class="kpi-card kpi-red" data-kpi-type="min" style="cursor:pointer;">
                <div class="kpi-header">
                  <span class="kpi-label">Compra Mínima</span>
                  <div class="kpi-icon"><i class="fa fa-arrow-down"></i></div>
                </div>
                <h3 class="kpi-value" id="kpi_compra_minima">S/. 0.00</h3>
              </div>
            </div>

            <!-- Gráfico Principal -->
            <div class="chart-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
              <h4 style="margin:0; font-weight:600; color:#0b2752;">Evolución Temporal</h4>
              <div class="btn-group">
                <button type="button" class="btn btn-default btn-sm active" id="btnChartBars" onclick="cambiarGrafico('bar')">
                  <i class="fa fa-bar-chart"></i> Barras
                </button>
                <button type="button" class="btn btn-default btn-sm" id="btnChartLine" onclick="cambiarGrafico('line')">
                  <i class="fa fa-line-chart"></i> Líneas
                </button>
              </div>
            </div>
            <div class="chart-container-main">
              <canvas id="comprasChart"></canvas>
            </div>

            <!-- Gráficos Adicionales -->
            <div class="chart-grid">
              <div class="chart-box">
                <div class="chart-header-box">
                  <h5>Compras por Categoría</h5>
                  <select class="chart-filter" id="filterCategoria" onchange="cargarGraficosAdicionales()">
                    <option value="5">Top 5</option>
                    <option value="10">Top 10</option>
                    <option value="all">Todas</option>
                  </select>
                </div>
                <canvas id="chartCategoria"></canvas>
              </div>
              <div class="chart-box">
                <div class="chart-header-box">
                  <h5>Top Productos</h5>
                  <select class="chart-filter" id="filterProductos" onchange="cargarGraficosAdicionales()">
                    <option value="5">Top 5</option>
                    <option value="10">Top 10</option>
                    <option value="20">Top 20</option>
                  </select>
                </div>
                <canvas id="chartProductos"></canvas>
              </div>
              <div class="chart-box">
                <div class="chart-header-box">
                  <h5>Por Tipo Comprobante</h5>
                  <select class="chart-filter" id="filterComprobante" onchange="cargarGraficosAdicionales()">
                    <option value="monto">Por Monto</option>
                    <option value="cantidad">Por Cantidad</option>
                  </select>
                </div>
                <canvas id="chartComprobante"></canvas>
              </div>
              <div class="chart-box">
                <div class="chart-header-box">
                  <h5>Top Usuarios</h5>
                  <select class="chart-filter" id="filterUsuario" onchange="cargarGraficosAdicionales()">
                    <option value="5">Top 5</option>
                    <option value="10">Top 10</option>
                  </select>
                </div>
                <canvas id="chartUsuario"></canvas>
              </div>
            </div>

            <!-- Toolbar custom -->
            <div class="dt-toolbar">
              <div class="dt-left">
                <div class="dt-buttons-holder"></div>
              </div>
              <div class="dt-right">
                <label>Mostrar :</label>
                <select id="customLength" class="form-control input-sm" style="width:auto;">
                  <option value="5">5</option>
                  <option value="10">10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
                <label>registros</label>

                <label style="margin-left:16px;">Buscar:</label>
                <input id="customSearch" class="form-control input-sm" style="width:240px;" placeholder="Proveedor, usuario, comprobante...">
              </div>
            </div>

            <!-- Tabla -->
            <div class="panel-body table-responsive" id="listadoregistros">
              <table id="tbllistado" class="table table-striped table-bordered table-condensed table-hover" style="width:100%;">
                <thead>
                  <th>Fecha</th>
                  <th>Usuario</th>
                  <th>Proveedor</th>
                  <th>Comprobante</th>
                  <th>Número</th>
                  <th>Total Compra</th>
                  <th>Impuesto</th>
                  <th>Estado</th>
                </thead>
                <tbody></tbody>
                <tfoot>
                  <th>Fecha</th>
                  <th>Usuario</th>
                  <th>Proveedor</th>
                  <th>Comprobante</th>
                  <th>Número</th>
                  <th>Total Compra</th>
                  <th>Impuesto</th>
                  <th>Estado</th>
                </tfoot>
              </table>
            </div>
          </div><!-- /body -->
        </div><!-- /card -->

      </div>
    </div>
  </section>
</div>

<?php
} else { require 'noacceso.php'; }

require 'footer.php';
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Chart.js Datalabels Plugin -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- JS específico de la vista -->
<script type="text/javascript" src="scripts/comprasfecha.js?v=<?php echo time(); ?>"></script>
<?php ob_end_flush(); ?>
