<?php
// Muestra errores mientras desarrollas (opcional, puedes quitar luego)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Activamos el almacenamiento en buffer
ob_start();

require_once __DIR__ . '/_requires_auth.php';
require 'header.php';

// Solo escritorio si tiene permiso
if (!empty($_SESSION['escritorio']) && (int)$_SESSION['escritorio'] === 1) {

  require_once "../config/Conexion.php";   // para ejecutarConsulta
  require_once "../modelos/Consultas.php";
  $consulta = new Consultas();

  /* ============================================================
     TOTALES HOY (usa tu modelo original)
     ============================================================ */
  $rsptac = $consulta->totalcomprahoy();
  $regc   = $rsptac ? $rsptac->fetch_object() : null;
  $totalc = $regc->total_compra ?? 0;

  $rsptav = $consulta->totalventahoy();
  $regv   = $rsptav ? $rsptav->fetch_object() : null;
  $totalv = $regv->total_venta ?? 0;

  /* ============================================================
     KPIs ADICIONALES PARA EL DASHBOARD
     (ventas acumuladas, mes, pedidos, etc.)
     ============================================================ */

  // Ventas acumuladas (histórico)
  $sql = "SELECT IFNULL(SUM(total_venta),0) AS total
          FROM venta
          WHERE estado = 'Aceptado'";
  $rs = ejecutarConsulta($sql);
  $row = $rs ? $rs->fetch_object() : null;
  $totalVentasAcumuladas = $row ? (float)$row->total : 0;

  // Compras acumuladas (histórico)
  $sql = "SELECT IFNULL(SUM(total_compra),0) AS total
          FROM ingreso
          WHERE estado = 'Aceptado'";
  $rs = ejecutarConsulta($sql);
  $row = $rs ? $rs->fetch_object() : null;
  $totalComprasAcumuladas = $row ? (float)$row->total : 0;

  // Ventas del mes actual
  $sql = "SELECT IFNULL(SUM(total_venta),0) AS total
          FROM venta
          WHERE estado = 'Aceptado'
            AND MONTH(fecha_hora) = MONTH(CURDATE())
            AND YEAR(fecha_hora)  = YEAR(CURDATE())";
  $rs = ejecutarConsulta($sql);
  $row = $rs ? $rs->fetch_object() : null;
  $totalVentasMes = $row ? (float)$row->total : 0;

  // Compras del mes actual
  $sql = "SELECT IFNULL(SUM(total_compra),0) AS total
          FROM ingreso
          WHERE estado = 'Aceptado'
            AND MONTH(fecha_hora) = MONTH(CURDATE())
            AND YEAR(fecha_hora)  = YEAR(CURDATE())";
  $rs = ejecutarConsulta($sql);
  $row = $rs ? $rs->fetch_object() : null;
  $totalComprasMes = $row ? (float)$row->total : 0;

  // Pedidos (ventas) del mes actual
  $sql = "SELECT COUNT(*) AS cant
          FROM venta
          WHERE estado = 'Aceptado'
            AND MONTH(fecha_hora) = MONTH(CURDATE())
            AND YEAR(fecha_hora)  = YEAR(CURDATE())";
  $rs = ejecutarConsulta($sql);
  $row = $rs ? $rs->fetch_object() : null;
  $totalPedidosMes = $row ? (int)$row->cant : 0;

  // Ticket promedio del mes
  $ticketPromedioMes = $totalPedidosMes > 0 ? ($totalVentasMes / $totalPedidosMes) : 0;

  /* ============================================================
     SERIES PARA LOS GRÁFICOS (usa tu modelo original)
     ============================================================ */

  // Compras últimos 10 días
  $compras10 = $consulta->comprasultimos_10dias();
  $fechasc = ''; $totalesc = '';
  if ($compras10) {
    while ($reg = $compras10->fetch_object()) {
      $fechasc  .= '"' . $reg->fecha . '",';
      $totalesc .= (isset($reg->total) ? (float)$reg->total : 0) . ',';
    }
  }
  $fechasc  = rtrim($fechasc, ',');
  $totalesc = rtrim($totalesc, ',');

  // Ventas últimos 12 meses
  $ventas12 = $consulta->ventasultimos_12meses();
  $fechasv = ''; $totalesv = '';
  if ($ventas12) {
    while ($reg = $ventas12->fetch_object()) {
      $fechasv  .= '"' . $reg->fecha . '",';
      $totalesv .= (isset($reg->total) ? (float)$reg->total : 0) . ',';
    }
  }
  $fechasv  = rtrim($fechasv, ',');
  $totalesv = rtrim($totalesv, ',');
  ?>
  <style>
    :root{
      --neko-primary:#1565c0;
      --neko-primary-dark:#0d47a1;
      --neko-bg:#f5f7fb;
      --card-border:1px solid rgba(2,24,54,.06);
      --shadow:0 8px 24px rgba(2,24,54,.06);
    }
    .content-wrapper{ background:var(--neko-bg); }
    .neko-card{
      background:#fff; border:var(--card-border);
      border-radius:14px; box-shadow:var(--shadow); overflow:hidden; margin-top:10px;
    }
    .neko-card__header{
      display:flex; align-items:center; justify-content:space-between;
      background:linear-gradient(90deg, var(--neko-primary-dark), var(--neko-primary));
      color:#fff; padding:14px 18px;
    }
    .neko-card__title{
      font-size:1.1rem; font-weight:600; letter-spacing:.2px; margin:0;
      display:flex; gap:10px; align-items:center;
    }
    .neko-card__body{ padding:18px; }

    /* Tarjetas KPI */
    .kpi{
      display:flex; align-items:center; gap:14px;
      background:#fff; border:var(--card-border); border-radius:12px; box-shadow:var(--shadow);
      padding:14px 16px; height:100%;
    }
    .kpi__icon{
      width:46px; height:46px; display:grid; place-items:center; border-radius:10px;
      background:#e3f2fd; color:#0d47a1; font-size:20px;
    }
    .kpi__label{ color:#334155; margin:0; font-size:.95rem; }
    .kpi__value{ margin:0; font-weight:700; color:#0b2752; font-size:1.25rem; }
    .kpi__sub{
      margin:2px 0 0; font-size:.78rem; color:#6b7280;
    }

    /* Contenedor de gráfico */
    .chart-card{
      background:#fff; border:var(--card-border); border-radius:12px; box-shadow:var(--shadow);
      padding:14px 16px;
    }
    .chart-card h4{
      margin:0 0 10px; font-size:1rem; color:#0b2752; font-weight:600;
    }
    .chart-holder{
      position: relative;
      height: 280px;
      width: 100%;
    }

    .mb-16{ margin-bottom:16px; }
  </style>

  <div class="content-wrapper">
    <section class="content">
      <div class="row">
        <div class="col-md-12">

          <div class="neko-card">
            <div class="neko-card__header">
              <h1 class="neko-card__title">
                <i class="fa fa-dashboard"></i> Escritorio
              </h1>
              <div class="neko-actions"><!-- reservado --></div>
            </div>

            <div class="neko-card__body">
              <!-- ================== Fila 1: Hoy ================== -->
              <div class="row mb-16">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 mb-16">
                  <div class="kpi">
                    <div class="kpi__icon"><i class="ion ion-ios-download"></i></div>
                    <div>
                      <p class="kpi__label">Compras de hoy</p>
                      <h3 class="kpi__value">S/ <?php echo number_format((float)$totalc, 2, '.', ''); ?></h3>
                      <p class="kpi__sub">Movimiento de abastecimiento del día.</p>
                      <a href="ingreso.php" class="small text-primary">
                        Ir a Compras <i class="fa fa-arrow-circle-right"></i>
                      </a>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 mb-16">
                  <div class="kpi">
                    <div class="kpi__icon"><i class="ion ion-ios-cart"></i></div>
                    <div>
                      <p class="kpi__label">Ventas de hoy</p>
                      <h3 class="kpi__value">S/ <?php echo number_format((float)$totalv, 2, '.', ''); ?></h3>
                      <p class="kpi__sub">Ingresos generados en la jornada actual.</p>
                      <a href="venta.php" class="small text-primary">
                        Ir a Ventas <i class="fa fa-arrow-circle-right"></i>
                      </a>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ================== Fila 2: KPIs del negocio ================== -->
              <div class="row mb-16">
                <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12 mb-16">
                  <div class="kpi">
                    <div class="kpi__icon"><i class="fa fa-line-chart"></i></div>
                    <div>
                      <p class="kpi__label">Ventas acumuladas</p>
                      <h3 class="kpi__value">S/ <?php echo number_format($totalVentasAcumuladas, 2, '.', ''); ?></h3>
                      <p class="kpi__sub">Todo lo facturado a la fecha.</p>
                    </div>
                  </div>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12 mb-16">
                  <div class="kpi">
                    <div class="kpi__icon"><i class="fa fa-calendar"></i></div>
                    <div>
                      <p class="kpi__label">Ventas del mes</p>
                      <h3 class="kpi__value">S/ <?php echo number_format($totalVentasMes, 2, '.', ''); ?></h3>
                      <p class="kpi__sub">Mes actual (ventas aceptadas).</p>
                    </div>
                  </div>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12 mb-16">
                  <div class="kpi">
                    <div class="kpi__icon"><i class="fa fa-file-text-o"></i></div>
                    <div>
                      <p class="kpi__label">Pedidos del mes</p>
                      <h3 class="kpi__value"><?php echo number_format($totalPedidosMes, 0, '.', ''); ?></h3>
                      <p class="kpi__sub">N.º de comprobantes emitidos.</p>
                    </div>
                  </div>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12 mb-16">
                  <div class="kpi">
                    <div class="kpi__icon"><i class="fa fa-money"></i></div>
                    <div>
                      <p class="kpi__label">Ticket promedio (mes)</p>
                      <h3 class="kpi__value">S/ <?php echo number_format($ticketPromedioMes, 2, '.', ''); ?></h3>
                      <p class="kpi__sub">Venta promedio por operación.</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ================== Gráficos ================== -->
              <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 mb-16">
                  <div class="chart-card">
                    <h4>Compras de los últimos 10 días</h4>
                    <div class="chart-holder">
                      <canvas id="compras"></canvas>
                    </div>
                  </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 mb-16">
                  <div class="chart-card">
                    <h4>Ventas de los últimos 12 meses</h4>
                    <div class="chart-holder">
                      <canvas id="ventas"></canvas>
                    </div>
                  </div>
                </div>
              </div>
            </div> <!-- /body -->
          </div> <!-- /card -->

        </div>
      </div>
    </section>
  </div>

  <?php require 'footer.php'; ?>

  <!-- Chart.js (ya lo usabas) -->
  <script src="../public/js/Chart.bundle.min.js"></script>

  <script>
    var chartCompras, chartVentas;

    // --------- Compras (10 días) ----------
    (function(){
      var el = document.getElementById("compras");
      if (!el) return;
      var ctx = el.getContext('2d');
      if (chartCompras) { chartCompras.destroy(); }
      chartCompras = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: [<?php echo $fechasc ?: ''; ?>],
          datasets: [{
            label: 'Compras en S/ (últimos 10 días)',
            data: [<?php echo $totalesc ?: ''; ?>],
            backgroundColor: 'rgba(21, 101, 192, 0.25)',
            borderColor: 'rgba(21, 101, 192, 1)',
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            yAxes: [{ ticks: { beginAtZero: true } }]
          },
          legend: { display: true }
        }
      });
    })();

    // --------- Ventas (12 meses) ----------
    (function(){
      var el = document.getElementById("ventas");
      if (!el) return;
      var ctx = el.getContext('2d');
      if (chartVentas) { chartVentas.destroy(); }
      chartVentas = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: [<?php echo $fechasv ?: ''; ?>],
          datasets: [{
            label: 'Ventas en S/ (últimos 12 meses)',
            data: [<?php echo $totalesv ?: ''; ?>],
            backgroundColor: 'rgba(13, 71, 161, 0.25)',
            borderColor: 'rgba(13, 71, 161, 1)',
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            yAxes: [{ ticks: { beginAtZero: true } }]
          },
          legend: { display: true }
        }
      });
    })();
  </script>

  <?php
} else {
  require 'noacceso.php';
  require 'footer.php';
}

ob_end_flush();
