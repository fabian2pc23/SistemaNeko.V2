<?php
// =====================================================
// EXPORTAR DASHBOARD - ERP AUTOPARTES
// Genera reportes en Excel del dashboard
// =====================================================

require_once __DIR__ . '/_requires_auth.php';
require_once "../config/Conexion.php";

// Requerir PHPExcel (debes instalarlo: composer require phpoffice/phpexcel)
// O usar PHPSpreadsheet (versión moderna)
// require_once "../vendor/autoload.php";
// use PhpOffice\PhpSpreadsheet\Spreadsheet;
// use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Obtener filtros del POST
$filtroFechaInicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : date('Y-m-01');
$filtroFechaFin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : date('Y-m-t');
$filtroCategoria = isset($_POST['categoria']) ? (int)$_POST['categoria'] : 0;

// Construcción de WHERE clauses
$whereVentasFiltro = "v.estado = 'Aceptado' 
  AND DATE(v.fecha_hora) BETWEEN '$filtroFechaInicio' AND '$filtroFechaFin'";

if ($filtroCategoria > 0) {
  $whereVentasFiltro .= " AND a.idcategoria = $filtroCategoria";
}

/* ============================================================
   EJEMPLO DE IMPLEMENTACIÓN CON PHPSpreadsheet
   ============================================================ */

/*
// Crear nuevo spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar propiedades
$spreadsheet->getProperties()
    ->setCreator("ERP Autopartes")
    ->setTitle("Dashboard Ejecutivo")
    ->setDescription("Reporte generado desde el dashboard");

// Título principal
$sheet->setCellValue('A1', 'DASHBOARD EJECUTIVO - ERP AUTOPARTES');
$sheet->mergeCells('A1:F1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

// Información del periodo
$sheet->setCellValue('A3', 'Periodo:');
$sheet->setCellValue('B3', date('d/m/Y', strtotime($filtroFechaInicio)) . ' al ' . date('d/m/Y', strtotime($filtroFechaFin)));
$sheet->getStyle('A3')->getFont()->setBold(true);

// Métricas principales
$row = 5;
$sheet->setCellValue('A'.$row, 'MÉTRICAS PRINCIPALES');
$sheet->mergeCells('A'.$row.':F'.$row);
$sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A'.$row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
      ->getStartColor()->setARGB('FF1565C0');
$sheet->getStyle('A'.$row)->getFont()->getColor()->setARGB('FFFFFFFF');

$row++;
$row++;

// Consulta métricas
$sql = "SELECT 
          IFNULL(SUM(dv.cantidad * dv.precio_venta), 0) AS ingresos_totales,
          IFNULL(SUM(dv.cantidad * a.precio_compra), 0) AS costos_totales
        FROM detalle_venta dv
        INNER JOIN venta v ON dv.idventa = v.idventa
        INNER JOIN articulo a ON dv.idarticulo = a.idarticulo
        WHERE $whereVentasFiltro";
$rs = ejecutarConsulta($sql);
$metricas = $rs->fetch_object();

$sheet->setCellValue('A'.$row, 'Métrica');
$sheet->setCellValue('B'.$row, 'Valor');
$sheet->getStyle('A'.$row.':B'.$row)->getFont()->setBold(true);
$row++;

$sheet->setCellValue('A'.$row, 'Ingresos Totales');
$sheet->setCellValue('B'.$row, 'S/ ' . number_format($metricas->ingresos_totales, 2));
$row++;

$sheet->setCellValue('A'.$row, 'Costos Totales');
$sheet->setCellValue('B'.$row, 'S/ ' . number_format($metricas->costos_totales, 2));
$row++;

$margen = $metricas->ingresos_totales - $metricas->costos_totales;
$sheet->setCellValue('A'.$row, 'Margen Bruto');
$sheet->setCellValue('B'.$row, 'S/ ' . number_format($margen, 2));
$row++;

// TOP 10 PRODUCTOS
$row++;
$row++;
$sheet->setCellValue('A'.$row, 'TOP 10 PRODUCTOS MÁS VENDIDOS');
$sheet->mergeCells('A'.$row.':F'.$row);
$sheet->getStyle('A'.$row)->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A'.$row)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
      ->getStartColor()->setARGB('FF1565C0');
$sheet->getStyle('A'.$row)->getFont()->getColor()->setARGB('FFFFFFFF');

$row++;
$row++;

// Headers
$sheet->setCellValue('A'.$row, '#');
$sheet->setCellValue('B'.$row, 'Producto');
$sheet->setCellValue('C'.$row, 'Categoría');
$sheet->setCellValue('D'.$row, 'Unidades');
$sheet->setCellValue('E'.$row, 'Ingresos');
$sheet->setCellValue('F'.$row, 'Precio Unit.');
$sheet->getStyle('A'.$row.':F'.$row)->getFont()->setBold(true);
$row++;

// Consulta productos
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
        LIMIT 10";

$rsProductos = ejecutarConsulta($sql);
$pos = 1;

if ($rsProductos) {
  while ($prod = $rsProductos->fetch_object()) {
    $sheet->setCellValue('A'.$row, $pos);
    $sheet->setCellValue('B'.$row, $prod->nombre);
    $sheet->setCellValue('C'.$row, $prod->categoria);
    $sheet->setCellValue('D'.$row, $prod->unidades_vendidas);
    $sheet->setCellValue('E'.$row, 'S/ ' . number_format($prod->ingresos_generados, 2));
    $sheet->setCellValue('F'.$row, 'S/ ' . number_format($prod->precio_venta, 2));
    $row++;
    $pos++;
  }
}

// Ajustar anchos de columna
foreach(range('A','F') as $col) {
  $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Generar archivo
$writer = new Xlsx($spreadsheet);
$filename = 'Dashboard_ERP_' . date('Ymd_His') . '.xlsx';

// Headers para descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer->save('php://output');
exit;
*/

/* ============================================================
   ALTERNATIVA: EXPORTACIÓN SIMPLE EN CSV
   ============================================================ */

// Preparar nombre del archivo
$filename = 'Dashboard_ERP_' . date('Ymd_His') . '.csv';

// Headers para descarga
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Crear output
$output = fopen('php://output', 'w');

// BOM para UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Título
fputcsv($output, ['DASHBOARD EJECUTIVO - ERP AUTOPARTES'], ';');
fputcsv($output, [''], ';');
fputcsv($output, ['Periodo:', date('d/m/Y', strtotime($filtroFechaInicio)) . ' al ' . date('d/m/Y', strtotime($filtroFechaFin))], ';');
fputcsv($output, ['Fecha Generación:', date('d/m/Y H:i:s')], ';');
fputcsv($output, [''], ';');

// Métricas principales
fputcsv($output, ['=== MÉTRICAS PRINCIPALES ==='], ';');
fputcsv($output, [''], ';');

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
        LIMIT 10";

$rsProductos = ejecutarConsulta($sql);
$pos = 1;

if ($rsProductos) {
  while ($prod = $rsProductos->fetch_object()) {
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
}

fputcsv($output, [''], ';');

// Top Clientes
fputcsv($output, ['=== TOP 5 CLIENTES DEL PERIODO ==='], ';');
fputcsv($output, [''], ';');
fputcsv($output, ['#', 'Cliente', 'Compras', 'Total Gastado'], ';');

$sql = "SELECT 
          p.nombre AS cliente,
          COUNT(DISTINCT v.idventa) AS num_compras,
          SUM(v.total_venta) AS total_gastado
        FROM venta v
        INNER JOIN persona p ON v.idcliente = p.idpersona
        WHERE $whereVentasFiltro
        GROUP BY v.idcliente, p.nombre
        ORDER BY total_gastado DESC
        LIMIT 5";

$rsClientes = ejecutarConsulta($sql);
$pos = 1;

if ($rsClientes) {
  while ($cli = $rsClientes->fetch_object()) {
    fputcsv($output, [
      $pos,
      $cli->cliente,
      $cli->num_compras,
      'S/ ' . number_format((float)$cli->total_gastado, 2)
    ], ';');
    $pos++;
  }
}

fputcsv($output, [''], ';');

// Análisis de Rentabilidad
fputcsv($output, ['=== PRODUCTOS MÁS RENTABLES ==='], ';');
fputcsv($output, [''], ';');
fputcsv($output, ['#', 'Producto', 'Categoría', 'Unidades', 'Margen %', 'Ganancia'], ';');

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
$pos = 1;

if ($rsMejores) {
  while ($prod = $rsMejores->fetch_object()) {
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
}

fputcsv($output, [''], ';');
fputcsv($output, ['=== FIN DEL REPORTE ==='], ';');

fclose($output);
exit;
?>