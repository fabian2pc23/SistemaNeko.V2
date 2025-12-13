<?php
//Activamos el almacenamiento en el buffer
ob_start();
session_start();

if (!isset($_SESSION["nombre"])) {
  header("Location: login.html");
} else {
  require 'header.php';

  if ($_SESSION['consultav'] == 1) {
    $hoy = date("Y-m-d");
?>
    <style>
      /* Estilos Neko Dashboard */
      .neko-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        border: none;
        margin-bottom: 20px;
      }

      .neko-card__header {
        background: #0d47a1;
        /* Blue header from image */
        color: #fff;
        padding: 15px 25px;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      .neko-card__title {
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #fff;
      }

      .neko-card__title i {
        color: #fff;
      }

      .neko-card__body {
        padding: 25px;
        background: #fdfdfd;
      }

      /* KPI Cards */
      .kpi-container {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 20px;
        margin-bottom: 25px;
      }

      .kpi-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
        border: 1px solid #eef2f6;
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
        position: relative;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
      }

      .kpi-content {
        display: flex;
        flex-direction: column;
        justify-content: center;
      }

      .kpi-label {
        font-size: 0.8rem;
        font-weight: 500;
        color: #697a8d;
        margin-bottom: 5px;
      }

      .kpi-value {
        font-size: 1.2rem;
        font-weight: 700;
        color: #364152;
        margin: 0;
      }

      .kpi-icon-box {
        width: 45px;
        height: 45px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
      }

      /* Colores KPI */
      .kpi-blue .kpi-icon-box {
        background: #e3f2fd;
        color: #2196f3;
      }

      .kpi-green .kpi-icon-box {
        background: #e8f5e9;
        color: #4caf50;
      }

      .kpi-purple .kpi-icon-box {
        background: #f3e5f5;
        color: #9c27b0;
      }

      .kpi-orange .kpi-icon-box {
        background: #fff3e0;
        color: #ff9800;
      }

      .kpi-red .kpi-icon-box {
        background: #ffebee;
        color: #f44336;
      }

      /* Chart Grid */
      .chart-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 25px;
      }

      .chart-box {
        background: #fff;
        border-radius: 12px;
        padding: 15px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        height: 320px;
        display: flex;
        flex-direction: column;
      }

      .chart-header-box {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
      }

      .chart-header-box h5 {
        margin: 0;
        font-weight: 600;
        color: #334155;
        font-size: 1rem;
      }

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
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        margin-bottom: 20px;
        height: 400px;
        position: relative;
      }

      /* Filtros */
      .filters-row {
        display: grid;
        grid-template-columns: 1fr 1fr 1.5fr;
        gap: 18px;
        margin-bottom: 14px;
      }

      .filters-row label {
        font-weight: 600;
        color: #0b2752;
        display: block;
        margin-bottom: 6px;
      }

      .filters-row .form-control {
        height: 40px;
        padding: 6px 12px;
        border: 1px solid #dbe3ef;
        border-radius: 8px;
        background: #fff;
      }

      .bootstrap-select .dropdown-toggle {
        border-radius: 8px;
        border: 1px solid #dbe3ef;
        height: 40px;
      }

      /* Quick Filters Toolbar */
      .quick-filters {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
      }

      .quick-filters .btn {
        border-radius: 50px;
        /* Pill shape */
        font-weight: 500;
        padding: 8px 20px;
        border: 1px solid #e0e0e0;
        background: #f5f5f5;
        color: #666;
        transition: all 0.2s;
      }

      .quick-filters .btn:hover,
      .quick-filters .btn.active {
        background: #e3f2fd;
        color: #1976d2;
        border-color: #bbdefb;
      }

      /* Toolbar */
      .dt-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        flex-wrap: nowrap;
        margin-bottom: 10px;
      }

      .dt-left {
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .dt-right {
        display: flex;
        align-items: center;
        gap: 8px;
      }

      .dt-right label {
        margin: 0;
        font-weight: 600;
        color: #0b2752;
      }

      .dt-right .form-control {
        height: 32px;
        padding: 4px 8px;
        border: 1px solid #dbe3ef;
        border-radius: 10px;
      }

      /* Buttons */
      .dt-buttons-holder {
        display: flex;
        align-items: center;
        gap: 8px;
        min-height: 38px;
      }

      .dt-buttons {
        display: flex;
        gap: 8px;
      }

      .dt-buttons .dt-button {
        margin: 0;
      }

      /* Tabla */
      #tbllistado thead th {
        background: linear-gradient(135deg, #1e293b, #334155);
        color: #fff;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        padding: 14px 12px;
      }

      #tbllistado tfoot th {
        background: #f8fafc;
      }

      #tbllistado tbody tr:hover {
        background: #f8fafc;
      }

      #tbllistado_wrapper .dataTables_length,
      #tbllistado_wrapper .dataTables_filter {
        display: none !important;
      }

      @media (max-width: 1400px) {
        .chart-grid {
          grid-template-columns: repeat(2, 1fr);
        }
      }

      @media (max-width: 992px) {
        .dt-toolbar {
          flex-wrap: wrap;
        }

        .kpi-container {
          grid-template-columns: 1fr 1fr;
        }

        .chart-grid {
          grid-template-columns: 1fr;
        }

        .filters-row {
          grid-template-columns: 1fr;
        }
      }
    </style>

    <div class="content-wrapper">
      <section class="content">
        <div class="row">
          <div class="col-md-12">

            <div class="neko-card">
              <div class="neko-card__header">
                <h1 class="neko-card__title">
                  <i class="fa fa-line-chart"></i> Dashboard de Ventas
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
                  <div>
                    <label for="idcliente">Cliente</label>
                    <select name="idcliente" id="idcliente" class="form-control selectpicker" data-live-search="true">
                      <option value="">Todos los Clientes</option>
                    </select>
                  </div>
                </div>

                <!-- KPIs Modernos -->
                <div class="kpi-container">
                  <div class="kpi-card kpi-blue" data-kpi-type="total">
                    <div class="kpi-content">
                      <span class="kpi-label">Total Ventas</span>
                      <h3 class="kpi-value" id="kpi_total_ventas">S/. 0.00</h3>
                    </div>
                    <div class="kpi-icon-box"><i class="fa fa-money"></i></div>
                  </div>
                  <div class="kpi-card kpi-green" data-kpi-type="transacciones">
                    <div class="kpi-content">
                      <span class="kpi-label">Transacciones</span>
                      <h3 class="kpi-value" id="kpi_num_transacciones">0</h3>
                    </div>
                    <div class="kpi-icon-box"><i class="fa fa-shopping-cart"></i></div>
                  </div>
                  <div class="kpi-card kpi-purple" data-kpi-type="ticket">
                    <div class="kpi-content">
                      <span class="kpi-label">Ticket Promedio</span>
                      <h3 class="kpi-value" id="kpi_ticket_promedio">S/. 0.00</h3>
                    </div>
                    <div class="kpi-icon-box"><i class="fa fa-line-chart"></i></div>
                  </div>
                  <div class="kpi-card kpi-orange" data-kpi-type="max">
                    <div class="kpi-content">
                      <span class="kpi-label">Venta Máxima</span>
                      <h3 class="kpi-value" id="kpi_venta_maxima">S/. 0.00</h3>
                    </div>
                    <div class="kpi-icon-box"><i class="fa fa-arrow-up"></i></div>
                  </div>
                  <div class="kpi-card kpi-red" data-kpi-type="min">
                    <div class="kpi-content">
                      <span class="kpi-label">Venta Mínima</span>
                      <h3 class="kpi-value" id="kpi_venta_minima">S/. 0.00</h3>
                    </div>
                    <div class="kpi-icon-box"><i class="fa fa-arrow-down"></i></div>
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
                  <canvas id="ventasChart"></canvas>
                </div>

                <!-- Gráficos Adicionales -->
                <div class="chart-grid">
                  <div class="chart-box">
                    <div class="chart-header-box">
                      <h5>Top Clientes</h5>
                      <select class="chart-filter" id="filterClientes" onchange="cargarGraficosAdicionales()">
                        <option value="5">Top 5</option>
                        <option value="10">Top 10</option>
                      </select>
                    </div>
                    <canvas id="chartClientes"></canvas>
                  </div>
                  <div class="chart-box">
                    <div class="chart-header-box">
                      <h5>Top Vendedores</h5>
                      <select class="chart-filter" id="filterVendedores" onchange="cargarGraficosAdicionales()">
                        <option value="5">Top 5</option>
                        <option value="10">Top 10</option>
                      </select>
                    </div>
                    <canvas id="chartVendedores"></canvas>
                  </div>
                  <div class="chart-box">
                    <div class="chart-header-box">
                      <h5>Ventas por Categoría</h5>
                      <select class="chart-filter" id="filterCategoria" onchange="cargarGraficosAdicionales()">
                        <option value="5">Top 5</option>
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
                      </select>
                    </div>
                    <canvas id="productosChart"></canvas>
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
                    <input id="customSearch" class="form-control input-sm" style="width:240px;" placeholder="Cliente, usuario, comprobante...">
                  </div>
                </div>

                <!-- Tabla -->
                <div class="panel-body table-responsive" id="listadoregistros">
                  <table id="tbllistado" class="table table-striped table-bordered table-condensed table-hover" style="width:100%;">
                    <thead>
                      <th>Fecha</th>
                      <th>Usuario</th>
                      <th>Cliente</th>
                      <th>Comprobante</th>
                      <th>Número</th>
                      <th>Total Venta</th>
                      <th>Impuesto</th>
                      <th>Estado</th>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                      <th>Fecha</th>
                      <th>Usuario</th>
                      <th>Cliente</th>
                      <th>Comprobante</th>
                      <th>Número</th>
                      <th>Total Venta</th>
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
  } else {
    require 'noacceso.php';
  }

  require 'footer.php';
}
?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Chart.js Datalabels Plugin -->
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
<!-- JS específico de la vista -->
<script type="text/javascript" src="scripts/ventasfechacliente.js?v=<?php echo time(); ?>"></script>
<?php ob_end_flush(); ?>