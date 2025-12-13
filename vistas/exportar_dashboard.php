<?php
// =====================================================
// EXPORTAR DASHBOARD - ERP AUTOPARTES
// Genera reportes en Excel, PDF y CSV del dashboard
// =====================================================

require_once __DIR__ . '/_requires_auth.php';
require_once "../config/Conexion.php";
require_once "../fpdf181/fpdf.php";

// Obtener filtros del POST
$filtroFechaInicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : date('Y-m-01');
$filtroFechaFin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : date('Y-m-t');
$formato = isset($_POST['formato']) ? $_POST['formato'] : 'csv';
$filtroRangoMeses = isset($_POST['rango_meses']) ? (int)$_POST['rango_meses'] : 6;
$filtroTopProductos = isset($_POST['top_productos']) ? (int)$_POST['top_productos'] : 10;
$filtroTopClientes = isset($_POST['top_clientes']) ? (int)$_POST['top_clientes'] : 5;
$filtroCategoria = isset($_POST['categoria']) ? (int)$_POST['categoria'] : 0;

// Construcción de WHERE clauses
$whereVentasFiltro = "v.estado = 'Aceptado' 
  AND DATE(v.fecha_hora) BETWEEN '$filtroFechaInicio' AND '$filtroFechaFin'";

if ($filtroCategoria > 0) {
  $whereVentasFiltro .= " AND a.idcategoria = $filtroCategoria";
}

// ============================================================
// RECOPILAR DATOS PARA TODOS LOS FORMATOS
// ============================================================

// Métricas principales
$sql = "SELECT 
          IFNULL(SUM(dv.cantidad * dv.precio_venta), 0) AS ingresos_totales,
          IFNULL(SUM(dv.cantidad * a.precio_compra), 0) AS costos_totales
        FROM detalle_venta dv
        INNER JOIN venta v ON dv.idventa = v.idventa
        INNER JOIN articulo a ON dv.idarticulo = a.idarticulo
        WHERE $whereVentasFiltro";
$rs = ejecutarConsulta($sql);
$metricas = $rs->fetch_object();

$ingresos = (float)$metricas->ingresos_totales;
$costos = (float)$metricas->costos_totales;
$margen = $ingresos - $costos;
$porcentaje = $ingresos > 0 ? (($margen / $ingresos) * 100) : 0;

// Top Productos
$sql = "SELECT 
          a.nombre,
          c.nombre AS categoria,
          a.precio_venta,
          SUM(dv.cantidad) AS unidades_vendidas,
          SUM(dv.cantidad * dv.precio_venta) AS ingresos_generados
        FROM detalle_venta dv
        INNER JOIN venta v ON dv.idventa = v.idventa
        INNER JOIN articulo a ON dv.idarticulo = a.idarticulo
        INNER JOIN categoria c ON a.idcategoria = c.idcategoria
        WHERE $whereVentasFiltro
        GROUP BY a.idarticulo, a.nombre, c.nombre, a.precio_venta
        ORDER BY unidades_vendidas DESC
        LIMIT $filtroTopProductos";
$rsProductos = ejecutarConsulta($sql);
$productos = [];
if ($rsProductos) {
  while ($prod = $rsProductos->fetch_object()) {
    $productos[] = $prod;
  }
}

// Top Clientes
$sql = "SELECT 
          p.nombre AS cliente,
          COUNT(DISTINCT v.idventa) AS num_compras,
          SUM(v.total_venta) AS total_gastado
        FROM venta v
        INNER JOIN persona p ON v.idcliente = p.idpersona
        WHERE $whereVentasFiltro
        GROUP BY v.idcliente, p.nombre
        ORDER BY total_gastado DESC
        LIMIT $filtroTopClientes";
$rsClientes = ejecutarConsulta($sql);
$clientes = [];
if ($rsClientes) {
  while ($cli = $rsClientes->fetch_object()) {
    $clientes[] = $cli;
  }
}

// Productos Rentables
$sql = "SELECT 
          a.nombre,
          c.nombre AS categoria,
          SUM(dv.cantidad) AS unidades_vendidas,
          ROUND(((SUM(dv.cantidad * dv.precio_venta) - SUM(dv.cantidad * a.precio_compra)) / 
                 SUM(dv.cantidad * dv.precio_venta) * 100), 2) AS margen_porcentaje,
          (SUM(dv.cantidad * dv.precio_venta) - SUM(dv.cantidad * a.precio_compra)) AS ganancia
        FROM detalle_venta dv
        INNER JOIN venta v ON dv.idventa = v.idventa
        INNER JOIN articulo a ON dv.idarticulo = a.idarticulo
        INNER JOIN categoria c ON a.idcategoria = c.idcategoria
        WHERE $whereVentasFiltro
        GROUP BY a.idarticulo, a.nombre, c.nombre
        HAVING unidades_vendidas >= 2
        ORDER BY margen_porcentaje DESC
        LIMIT 5";
$rsMejores = ejecutarConsulta($sql);
$rentables = [];
if ($rsMejores) {
  while ($prod = $rsMejores->fetch_object()) {
    $rentables[] = $prod;
  }
}

// Evolución Mensual (Ventas vs Compras)
$fechaInicioGrafico = date('Y-m-01', strtotime($filtroFechaFin . " -$filtroRangoMeses months"));
$sql = "SELECT 
          DATE_FORMAT(v.fecha_hora, '%b %Y') AS mes,
          YEAR(v.fecha_hora) AS anio,
          MONTH(v.fecha_hora) AS mes_num,
          SUM(v.total_venta) AS ventas
        FROM venta v
        WHERE v.estado = 'Aceptado'
          AND DATE(v.fecha_hora) >= '$fechaInicioGrafico'
          AND DATE(v.fecha_hora) <= '$filtroFechaFin'
        GROUP BY YEAR(v.fecha_hora), MONTH(v.fecha_hora)
        ORDER BY YEAR(v.fecha_hora) ASC, MONTH(v.fecha_hora) ASC";
$rsEvolucion = ejecutarConsulta($sql);
$evolucion = [];
if ($rsEvolucion) {
  while ($reg = $rsEvolucion->fetch_object()) {
    $evolucion[] = $reg;
  }
}

// Ventas por Día de la Semana
$diasEspanol = [
  'Monday' => 'Lunes',
  'Tuesday' => 'Martes',
  'Wednesday' => 'Miércoles',
  'Thursday' => 'Jueves',
  'Friday' => 'Viernes',
  'Saturday' => 'Sábado',
  'Sunday' => 'Domingo'
];
$sql = "SELECT 
          DAYNAME(v.fecha_hora) AS dia_semana,
          DAYOFWEEK(v.fecha_hora) AS dia_num,
          COUNT(*) AS num_ventas,
          SUM(v.total_venta) AS total
        FROM venta v
        WHERE v.estado = 'Aceptado'
          AND DATE(v.fecha_hora) BETWEEN '$filtroFechaInicio' AND '$filtroFechaFin'
        GROUP BY DAYOFWEEK(v.fecha_hora), DAYNAME(v.fecha_hora)
        ORDER BY DAYOFWEEK(v.fecha_hora)";
$rsDias = ejecutarConsulta($sql);
$ventasDias = [];
if ($rsDias) {
  while ($reg = $rsDias->fetch_object()) {
    $reg->dia_esp = isset($diasEspanol[$reg->dia_semana]) ? $diasEspanol[$reg->dia_semana] : $reg->dia_semana;
    $ventasDias[] = $reg;
  }
}

// Ventas por Categoría
$sql = "SELECT 
          c.nombre AS categoria,
          SUM(dv.cantidad * dv.precio_venta) AS total_ventas,
          COUNT(DISTINCT dv.idventa) AS num_operaciones
        FROM detalle_venta dv
        INNER JOIN venta v ON dv.idventa = v.idventa
        INNER JOIN articulo a ON dv.idarticulo = a.idarticulo
        INNER JOIN categoria c ON a.idcategoria = c.idcategoria
        WHERE v.estado = 'Aceptado'
          AND DATE(v.fecha_hora) BETWEEN '$filtroFechaInicio' AND '$filtroFechaFin'
        GROUP BY c.idcategoria, c.nombre
        ORDER BY total_ventas DESC";
$rsCategorias = ejecutarConsulta($sql);
$categorias = [];
if ($rsCategorias) {
  while ($reg = $rsCategorias->fetch_object()) {
    $categorias[] = $reg;
  }
}

// ============================================================
// GENERAR SEGÚN EL FORMATO
// ============================================================

switch ($formato) {
  case 'excel':
    generarExcel($filtroFechaInicio, $filtroFechaFin, $ingresos, $costos, $margen, $porcentaje, $productos, $clientes, $rentables, $evolucion, $ventasDias, $categorias, $filtroRangoMeses);
    break;
  case 'pdf':
    generarPDF($filtroFechaInicio, $filtroFechaFin, $ingresos, $costos, $margen, $porcentaje, $productos, $clientes, $rentables, $evolucion, $ventasDias, $categorias, $filtroRangoMeses);
    break;
  case 'csv':
  default:
    generarCSV($filtroFechaInicio, $filtroFechaFin, $ingresos, $costos, $margen, $porcentaje, $productos, $clientes, $rentables, $evolucion, $ventasDias, $categorias, $filtroRangoMeses);
    break;
}

// ============================================================
// FUNCIÓN: GENERAR EXCEL (usando HTML con mime type de Excel)
// ============================================================
function generarExcel($fechaIni, $fechaFin, $ingresos, $costos, $margen, $porcentaje, $productos, $clientes, $rentables, $evolucion, $ventasDias, $categorias, $rangoMeses)
{
  $filename = 'Dashboard_ERP_' . date('Ymd_His') . '.xls';

  header("Content-Type: application/vnd.ms-excel; charset=utf-8");
  header("Content-Disposition: attachment; filename=\"$filename\"");
  header("Pragma: no-cache");
  header("Expires: 0");

  echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
  echo '<head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <style>
    body { font-family: Arial, sans-serif; }
    h1 { background: #1565c0; color: white; padding: 10px; margin: 0; }
    h2 { background: #e0f2fe; color: #0284c7; padding: 8px; margin: 20px 0 10px 0; }
    table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
    th { background: #334155; color: white; padding: 10px; text-align: left; border: 1px solid #94a3b8; }
    td { padding: 8px; border: 1px solid #e2e8f0; }
    tr:nth-child(even) { background: #f8fafc; }
    .metric-value { font-weight: bold; color: #1565c0; }
    .currency { text-align: right; }
    .number { text-align: center; }
    .positive { color: #059669; }
    .header-info { background: #f1f5f9; padding: 10px; margin-bottom: 20px; }
  </style>
  </head><body>';

  echo '<h1>📊 DASHBOARD EJECUTIVO - ERP AUTOPARTES</h1>';
  echo '<div class="header-info">';
  echo '<strong>Periodo:</strong> ' . date('d/m/Y', strtotime($fechaIni)) . ' al ' . date('d/m/Y', strtotime($fechaFin)) . '<br>';
  echo '<strong>Generado:</strong> ' . date('d/m/Y H:i:s');
  echo '</div>';

  // Métricas
  echo '<h2>📈 MÉTRICAS PRINCIPALES</h2>';
  echo '<table>';
  echo '<tr><th>Métrica</th><th>Valor</th></tr>';
  echo '<tr><td>Ingresos Totales</td><td class="currency metric-value">S/ ' . number_format($ingresos, 2) . '</td></tr>';
  echo '<tr><td>Costos Totales</td><td class="currency">S/ ' . number_format($costos, 2) . '</td></tr>';
  echo '<tr><td>Margen Bruto</td><td class="currency positive">S/ ' . number_format($margen, 2) . '</td></tr>';
  echo '<tr><td>% Rentabilidad</td><td class="number positive">' . number_format($porcentaje, 2) . '%</td></tr>';
  echo '</table>';

  // Top Productos
  echo '<h2>🏆 TOP 10 PRODUCTOS MÁS VENDIDOS</h2>';
  echo '<table>';
  echo '<tr><th>#</th><th>Producto</th><th>Categoría</th><th>Unidades</th><th>Ingresos</th><th>Precio Unit.</th></tr>';
  $pos = 1;
  foreach ($productos as $prod) {
    echo '<tr>';
    echo '<td class="number">' . $pos . '</td>';
    echo '<td>' . htmlspecialchars($prod->nombre) . '</td>';
    echo '<td>' . htmlspecialchars($prod->categoria) . '</td>';
    echo '<td class="number">' . $prod->unidades_vendidas . '</td>';
    echo '<td class="currency">S/ ' . number_format((float)$prod->ingresos_generados, 2) . '</td>';
    echo '<td class="currency">S/ ' . number_format((float)$prod->precio_venta, 2) . '</td>';
    echo '</tr>';
    $pos++;
  }
  echo '</table>';

  // Top Clientes
  echo '<h2>⭐ TOP 5 CLIENTES</h2>';
  echo '<table>';
  echo '<tr><th>#</th><th>Cliente</th><th>Compras</th><th>Total</th></tr>';
  $pos = 1;
  foreach ($clientes as $cli) {
    echo '<tr>';
    echo '<td class="number">' . $pos . '</td>';
    echo '<td>' . htmlspecialchars($cli->cliente) . '</td>';
    echo '<td class="number">' . $cli->num_compras . '</td>';
    echo '<td class="currency">S/ ' . number_format((float)$cli->total_gastado, 2) . '</td>';
    echo '</tr>';
    $pos++;
  }
  echo '</table>';

  // Productos Rentables
  echo '<h2>💰 PRODUCTOS MÁS RENTABLES</h2>';
  echo '<table>';
  echo '<tr><th>#</th><th>Producto</th><th>Categoría</th><th>Unidades</th><th>Margen %</th><th>Ganancia</th></tr>';
  $pos = 1;
  foreach ($rentables as $prod) {
    echo '<tr>';
    echo '<td class="number">' . $pos . '</td>';
    echo '<td>' . htmlspecialchars($prod->nombre) . '</td>';
    echo '<td>' . htmlspecialchars($prod->categoria) . '</td>';
    echo '<td class="number">' . $prod->unidades_vendidas . '</td>';
    echo '<td class="number positive">' . number_format((float)$prod->margen_porcentaje, 2) . '%</td>';
    echo '<td class="currency positive">S/ ' . number_format((float)$prod->ganancia, 2) . '</td>';
    echo '</tr>';
    $pos++;
  }
  echo '</table>';

  // Evolución Mensual
  echo '<h2>📈 EVOLUCIÓN MENSUAL (ÚLTIMOS ' . $rangoMeses . ' MESES)</h2>';
  echo '<table>';
  echo '<tr><th>Mes</th><th>Ventas</th></tr>';
  foreach ($evolucion as $mes) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($mes->mes) . '</td>';
    echo '<td class="currency">S/ ' . number_format((float)$mes->ventas, 2) . '</td>';
    echo '</tr>';
  }
  echo '</table>';

  // Ventas por Día de la Semana
  echo '<h2>📅 VENTAS POR DÍA DE LA SEMANA</h2>';
  echo '<table>';
  echo '<tr><th>Día</th><th>Operaciones</th><th>Total</th></tr>';
  foreach ($ventasDias as $dia) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($dia->dia_esp) . '</td>';
    echo '<td class="number">' . $dia->num_ventas . '</td>';
    echo '<td class="currency">S/ ' . number_format((float)$dia->total, 2) . '</td>';
    echo '</tr>';
  }
  echo '</table>';

  // Ventas por Categoría
  echo '<h2>🏷️ VENTAS POR CATEGORÍA</h2>';
  echo '<table>';
  echo '<tr><th>Categoría</th><th>Operaciones</th><th>Total</th></tr>';
  foreach ($categorias as $cat) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($cat->categoria) . '</td>';
    echo '<td class="number">' . $cat->num_operaciones . '</td>';
    echo '<td class="currency">S/ ' . number_format((float)$cat->total_ventas, 2) . '</td>';
    echo '</tr>';
  }
  echo '</table>';

  echo '</body></html>';
  exit;
}

// ============================================================
// FUNCIÓN: GENERAR PDF
// ============================================================
function generarPDF($fechaIni, $fechaFin, $ingresos, $costos, $margen, $porcentaje, $productos, $clientes, $rentables, $evolucion, $ventasDias, $categorias, $rangoMeses)
{
  $filename = 'Dashboard_ERP_' . date('Ymd_His') . '.pdf';

  // Crear PDF
  $pdf = new FPDF('P', 'mm', 'A4');
  $pdf->AddPage();
  $pdf->SetAutoPageBreak(true, 15);

  // Header
  $pdf->SetFillColor(21, 101, 192);
  $pdf->SetTextColor(255, 255, 255);
  $pdf->SetFont('Arial', 'B', 16);
  $pdf->Cell(0, 12, utf8_decode('DASHBOARD EJECUTIVO - ERP AUTOPARTES'), 0, 1, 'C', true);

  $pdf->SetFillColor(241, 245, 249);
  $pdf->SetTextColor(51, 65, 85);
  $pdf->SetFont('Arial', '', 10);
  $pdf->Cell(0, 8, utf8_decode('Periodo: ' . date('d/m/Y', strtotime($fechaIni)) . ' al ' . date('d/m/Y', strtotime($fechaFin)) . ' | Generado: ' . date('d/m/Y H:i:s')), 0, 1, 'C', true);

  $pdf->Ln(5);

  // Métricas
  $pdf->SetFillColor(224, 242, 254);
  $pdf->SetTextColor(2, 132, 199);
  $pdf->SetFont('Arial', 'B', 12);
  $pdf->Cell(0, 8, utf8_decode('MÉTRICAS PRINCIPALES'), 0, 1, 'L', true);

  $pdf->SetTextColor(0, 0, 0);
  $pdf->SetFont('Arial', '', 10);
  $pdf->Cell(95, 7, utf8_decode('Ingresos Totales:'), 1, 0, 'L');
  $pdf->SetFont('Arial', 'B', 10);
  $pdf->Cell(95, 7, 'S/ ' . number_format($ingresos, 2), 1, 1, 'R');

  $pdf->SetFont('Arial', '', 10);
  $pdf->Cell(95, 7, utf8_decode('Costos Totales:'), 1, 0, 'L');
  $pdf->Cell(95, 7, 'S/ ' . number_format($costos, 2), 1, 1, 'R');

  $pdf->Cell(95, 7, utf8_decode('Margen Bruto:'), 1, 0, 'L');
  $pdf->SetTextColor(5, 150, 105);
  $pdf->SetFont('Arial', 'B', 10);
  $pdf->Cell(95, 7, 'S/ ' . number_format($margen, 2), 1, 1, 'R');

  $pdf->SetTextColor(0, 0, 0);
  $pdf->SetFont('Arial', '', 10);
  $pdf->Cell(95, 7, utf8_decode('% Rentabilidad:'), 1, 0, 'L');
  $pdf->SetTextColor(5, 150, 105);
  $pdf->SetFont('Arial', 'B', 10);
  $pdf->Cell(95, 7, number_format($porcentaje, 2) . '%', 1, 1, 'R');

  $pdf->Ln(5);

  // Top Productos
  $pdf->SetTextColor(2, 132, 199);
  $pdf->SetFillColor(224, 242, 254);
  $pdf->SetFont('Arial', 'B', 12);
  $pdf->Cell(0, 8, utf8_decode('TOP 10 PRODUCTOS MÁS VENDIDOS'), 0, 1, 'L', true);

  $pdf->SetFillColor(51, 65, 85);
  $pdf->SetTextColor(255, 255, 255);
  $pdf->SetFont('Arial', 'B', 9);
  $pdf->Cell(10, 7, '#', 1, 0, 'C', true);
  $pdf->Cell(70, 7, 'Producto', 1, 0, 'L', true);
  $pdf->Cell(40, 7, utf8_decode('Categoría'), 1, 0, 'L', true);
  $pdf->Cell(25, 7, 'Unidades', 1, 0, 'C', true);
  $pdf->Cell(45, 7, 'Ingresos', 1, 1, 'R', true);

  $pdf->SetTextColor(0, 0, 0);
  $pdf->SetFont('Arial', '', 9);
  $pos = 1;
  foreach ($productos as $prod) {
    $fill = $pos % 2 == 0;
    $pdf->SetFillColor(248, 250, 252);
    $pdf->Cell(10, 6, $pos, 1, 0, 'C', $fill);
    $nombre = strlen($prod->nombre) > 35 ? substr($prod->nombre, 0, 35) . '...' : $prod->nombre;
    $pdf->Cell(70, 6, utf8_decode($nombre), 1, 0, 'L', $fill);
    $pdf->Cell(40, 6, utf8_decode($prod->categoria), 1, 0, 'L', $fill);
    $pdf->Cell(25, 6, $prod->unidades_vendidas, 1, 0, 'C', $fill);
    $pdf->Cell(45, 6, 'S/ ' . number_format((float)$prod->ingresos_generados, 2), 1, 1, 'R', $fill);
    $pos++;
  }

  $pdf->Ln(5);

  // Top Clientes
  $pdf->SetTextColor(2, 132, 199);
  $pdf->SetFillColor(224, 242, 254);
  $pdf->SetFont('Arial', 'B', 12);
  $pdf->Cell(0, 8, 'TOP 5 CLIENTES', 0, 1, 'L', true);

  $pdf->SetFillColor(51, 65, 85);
  $pdf->SetTextColor(255, 255, 255);
  $pdf->SetFont('Arial', 'B', 9);
  $pdf->Cell(10, 7, '#', 1, 0, 'C', true);
  $pdf->Cell(100, 7, 'Cliente', 1, 0, 'L', true);
  $pdf->Cell(30, 7, 'Compras', 1, 0, 'C', true);
  $pdf->Cell(50, 7, 'Total', 1, 1, 'R', true);

  $pdf->SetTextColor(0, 0, 0);
  $pdf->SetFont('Arial', '', 9);
  $pos = 1;
  foreach ($clientes as $cli) {
    $fill = $pos % 2 == 0;
    $pdf->SetFillColor(248, 250, 252);
    $pdf->Cell(10, 6, $pos, 1, 0, 'C', $fill);
    $pdf->Cell(100, 6, utf8_decode($cli->cliente), 1, 0, 'L', $fill);
    $pdf->Cell(30, 6, $cli->num_compras, 1, 0, 'C', $fill);
    $pdf->Cell(50, 6, 'S/ ' . number_format((float)$cli->total_gastado, 2), 1, 1, 'R', $fill);
    $pos++;
  }

  // Nueva página para gráficos
  $pdf->AddPage();

  // Evolución Mensual
  $pdf->SetTextColor(2, 132, 199);
  $pdf->SetFillColor(224, 242, 254);
  $pdf->SetFont('Arial', 'B', 12);
  $pdf->Cell(0, 8, utf8_decode('EVOLUCIÓN MENSUAL (ÚLTIMOS ' . $rangoMeses . ' MESES)'), 0, 1, 'L', true);

  $pdf->SetFillColor(51, 65, 85);
  $pdf->SetTextColor(255, 255, 255);
  $pdf->SetFont('Arial', 'B', 9);
  $pdf->Cell(95, 7, 'Mes', 1, 0, 'L', true);
  $pdf->Cell(95, 7, 'Ventas', 1, 1, 'R', true);

  $pdf->SetTextColor(0, 0, 0);
  $pdf->SetFont('Arial', '', 9);
  $pos = 1;
  foreach ($evolucion as $mes) {
    $fill = $pos % 2 == 0;
    $pdf->SetFillColor(248, 250, 252);
    $pdf->Cell(95, 6, utf8_decode($mes->mes), 1, 0, 'L', $fill);
    $pdf->Cell(95, 6, 'S/ ' . number_format((float)$mes->ventas, 2), 1, 1, 'R', $fill);
    $pos++;
  }

  $pdf->Ln(5);

  // Ventas por Día de la Semana
  $pdf->SetTextColor(2, 132, 199);
  $pdf->SetFillColor(224, 242, 254);
  $pdf->SetFont('Arial', 'B', 12);
  $pdf->Cell(0, 8, utf8_decode('VENTAS POR DÍA DE LA SEMANA'), 0, 1, 'L', true);

  $pdf->SetFillColor(51, 65, 85);
  $pdf->SetTextColor(255, 255, 255);
  $pdf->SetFont('Arial', 'B', 9);
  $pdf->Cell(60, 7, utf8_decode('Día'), 1, 0, 'L', true);
  $pdf->Cell(60, 7, 'Operaciones', 1, 0, 'C', true);
  $pdf->Cell(70, 7, 'Total', 1, 1, 'R', true);

  $pdf->SetTextColor(0, 0, 0);
  $pdf->SetFont('Arial', '', 9);
  $pos = 1;
  foreach ($ventasDias as $dia) {
    $fill = $pos % 2 == 0;
    $pdf->SetFillColor(248, 250, 252);
    $pdf->Cell(60, 6, utf8_decode($dia->dia_esp), 1, 0, 'L', $fill);
    $pdf->Cell(60, 6, $dia->num_ventas, 1, 0, 'C', $fill);
    $pdf->Cell(70, 6, 'S/ ' . number_format((float)$dia->total, 2), 1, 1, 'R', $fill);
    $pos++;
  }

  $pdf->Ln(5);

  // Ventas por Categoría
  $pdf->SetTextColor(2, 132, 199);
  $pdf->SetFillColor(224, 242, 254);
  $pdf->SetFont('Arial', 'B', 12);
  $pdf->Cell(0, 8, utf8_decode('VENTAS POR CATEGORÍA'), 0, 1, 'L', true);

  $pdf->SetFillColor(51, 65, 85);
  $pdf->SetTextColor(255, 255, 255);
  $pdf->SetFont('Arial', 'B', 9);
  $pdf->Cell(90, 7, utf8_decode('Categoría'), 1, 0, 'L', true);
  $pdf->Cell(50, 7, 'Operaciones', 1, 0, 'C', true);
  $pdf->Cell(50, 7, 'Total', 1, 1, 'R', true);

  $pdf->SetTextColor(0, 0, 0);
  $pdf->SetFont('Arial', '', 9);
  $pos = 1;
  foreach ($categorias as $cat) {
    $fill = $pos % 2 == 0;
    $pdf->SetFillColor(248, 250, 252);
    $pdf->Cell(90, 6, utf8_decode($cat->categoria), 1, 0, 'L', $fill);
    $pdf->Cell(50, 6, $cat->num_operaciones, 1, 0, 'C', $fill);
    $pdf->Cell(50, 6, 'S/ ' . number_format((float)$cat->total_ventas, 2), 1, 1, 'R', $fill);
    $pos++;
  }

  // Footer
  $pdf->Ln(10);
  $pdf->SetFont('Arial', 'I', 8);
  $pdf->SetTextColor(148, 163, 184);
  $pdf->Cell(0, 5, utf8_decode('Reporte generado automáticamente por ERP Autopartes'), 0, 0, 'C');

  // Output
  $pdf->Output('D', $filename);
  exit;
}

// ============================================================
// FUNCIÓN: GENERAR CSV
// ============================================================
function generarCSV($fechaIni, $fechaFin, $ingresos, $costos, $margen, $porcentaje, $productos, $clientes, $rentables, $evolucion, $ventasDias, $categorias, $rangoMeses)
{
  $filename = 'Dashboard_ERP_' . date('Ymd_His') . '.csv';

  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="' . $filename . '"');
  header('Pragma: no-cache');
  header('Expires: 0');

  $output = fopen('php://output', 'w');

  // BOM para UTF-8
  fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

  // Título
  fputcsv($output, ['DASHBOARD EJECUTIVO - ERP AUTOPARTES'], ';');
  fputcsv($output, [''], ';');
  fputcsv($output, ['Periodo:', date('d/m/Y', strtotime($fechaIni)) . ' al ' . date('d/m/Y', strtotime($fechaFin))], ';');
  fputcsv($output, ['Fecha Generación:', date('d/m/Y H:i:s')], ';');
  fputcsv($output, [''], ';');

  // Métricas principales
  fputcsv($output, ['=== MÉTRICAS PRINCIPALES ==='], ';');
  fputcsv($output, [''], ';');
  fputcsv($output, ['Métrica', 'Valor'], ';');
  fputcsv($output, ['Ingresos Totales', 'S/ ' . number_format($ingresos, 2)], ';');
  fputcsv($output, ['Costos Totales', 'S/ ' . number_format($costos, 2)], ';');
  fputcsv($output, ['Margen Bruto', 'S/ ' . number_format($margen, 2)], ';');
  fputcsv($output, ['% Margen', number_format($porcentaje, 2) . '%'], ';');
  fputcsv($output, [''], ';');

  // Top Productos
  fputcsv($output, ['=== TOP 10 PRODUCTOS MÁS VENDIDOS ==='], ';');
  fputcsv($output, [''], ';');
  fputcsv($output, ['#', 'Producto', 'Categoría', 'Unidades', 'Ingresos', 'Precio Unit.'], ';');
  $pos = 1;
  foreach ($productos as $prod) {
    fputcsv($output, [
      $pos,
      $prod->nombre,
      $prod->categoria,
      $prod->unidades_vendidas,
      'S/ ' . number_format((float)$prod->ingresos_generados, 2),
      'S/ ' . number_format((float)$prod->precio_venta, 2)
    ], ';');
    $pos++;
  }
  fputcsv($output, [''], ';');

  // Top Clientes
  fputcsv($output, ['=== TOP 5 CLIENTES DEL PERIODO ==='], ';');
  fputcsv($output, [''], ';');
  fputcsv($output, ['#', 'Cliente', 'Compras', 'Total Gastado'], ';');
  $pos = 1;
  foreach ($clientes as $cli) {
    fputcsv($output, [
      $pos,
      $cli->cliente,
      $cli->num_compras,
      'S/ ' . number_format((float)$cli->total_gastado, 2)
    ], ';');
    $pos++;
  }
  fputcsv($output, [''], ';');

  // Análisis de Rentabilidad
  fputcsv($output, ['=== PRODUCTOS MÁS RENTABLES ==='], ';');
  fputcsv($output, [''], ';');
  fputcsv($output, ['#', 'Producto', 'Categoría', 'Unidades', 'Margen %', 'Ganancia'], ';');
  $pos = 1;
  foreach ($rentables as $prod) {
    fputcsv($output, [
      $pos,
      $prod->nombre,
      $prod->categoria,
      $prod->unidades_vendidas,
      number_format((float)$prod->margen_porcentaje, 2) . '%',
      'S/ ' . number_format((float)$prod->ganancia, 2)
    ], ';');
    $pos++;
  }
  fputcsv($output, [''], ';');

  // Evolución Mensual
  fputcsv($output, ['=== EVOLUCIÓN MENSUAL (ÚLTIMOS ' . $rangoMeses . ' MESES) ==='], ';');
  fputcsv($output, [''], ';');
  fputcsv($output, ['Mes', 'Ventas'], ';');
  foreach ($evolucion as $mes) {
    fputcsv($output, [
      $mes->mes,
      'S/ ' . number_format((float)$mes->ventas, 2)
    ], ';');
  }
  fputcsv($output, [''], ';');

  // Ventas por Día de la Semana
  fputcsv($output, ['=== VENTAS POR DÍA DE LA SEMANA ==='], ';');
  fputcsv($output, [''], ';');
  fputcsv($output, ['Día', 'Operaciones', 'Total'], ';');
  foreach ($ventasDias as $dia) {
    fputcsv($output, [
      $dia->dia_esp,
      $dia->num_ventas,
      'S/ ' . number_format((float)$dia->total, 2)
    ], ';');
  }
  fputcsv($output, [''], ';');

  // Ventas por Categoría
  fputcsv($output, ['=== VENTAS POR CATEGORÍA ==='], ';');
  fputcsv($output, [''], ';');
  fputcsv($output, ['Categoría', 'Operaciones', 'Total'], ';');
  foreach ($categorias as $cat) {
    fputcsv($output, [
      $cat->categoria,
      $cat->num_operaciones,
      'S/ ' . number_format((float)$cat->total_ventas, 2)
    ], ';');
  }
  fputcsv($output, [''], ';');

  fputcsv($output, ['=== FIN DEL REPORTE ==='], ';');

  fclose($output);
  exit;
}
