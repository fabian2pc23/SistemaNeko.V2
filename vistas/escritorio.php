<?php
// =====================================================
// DASHBOARD EJECUTIVO MEJORADO - ERP AUTOPARTES
// Version 2.0 - Con filtros avanzados y análisis comparativo
// =====================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();

require_once __DIR__ . '/_requires_auth.php';
require 'header.php';

if (!empty($_SESSION['escritorio']) && (int)$_SESSION['escritorio'] === 1) {

  require_once "../config/Conexion.php";
  require_once "../modelos/Consultas.php";
  $consulta = new Consultas();

  /* ============================================================
     SISTEMA DE FILTROS AVANZADOS
     ============================================================ */
  
  // Calcular trimestre actual por defecto
  $mesActual = (int)date('m');
  $anioActual = (int)date('Y');
  $primerMesTrimestre = floor(($mesActual - 1) / 3) * 3 + 1;
  $fechaInicioTrimestreDefault = date('Y-m-01', mktime(0, 0, 0, $primerMesTrimestre, 1, $anioActual));
  $fechaFinTrimestreDefault = date('Y-m-t', mktime(0, 0, 0, $primerMesTrimestre + 2, 1, $anioActual));
  
  // Obtener parámetros de filtro
  $filtroFechaInicio = isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : $fechaInicioTrimestreDefault;
  $filtroFechaFin = isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : $fechaFinTrimestreDefault;
  $filtroCategoria = 0; // Eliminado
  $filtroComparativo = 0; // Eliminado // 0=no, 1=mes anterior, 2=año anterior
  
  // Calcular periodo comparativo si está activado
  $fechaInicioComp = '';
  $fechaFinComp = '';
  $labelComparativo = '';
  
  if ($filtroComparativo == 1) {
    // Mes anterior
    $fechaInicioComp = date('Y-m-01', strtotime($filtroFechaInicio . ' -1 month'));
    $fechaFinComp = date('Y-m-t', strtotime($filtroFechaInicio . ' -1 month'));
    $labelComparativo = 'vs Mes Anterior';
  } elseif ($filtroComparativo == 2) {
    // Año anterior
    $fechaInicioComp = date('Y-m-d', strtotime($filtroFechaInicio . ' -1 year'));
    $fechaFinComp = date('Y-m-d', strtotime($filtroFechaFin . ' -1 year'));
    $labelComparativo = 'vs Año Anterior';
  }
  
  // Construcción de WHERE clauses para filtros
  $whereVentasFiltro = "v.estado = 'Aceptado' 
    AND DATE(v.fecha_hora) BETWEEN '$filtroFechaInicio' AND '$filtroFechaFin'";
  
  $whereComprasFiltro = "i.estado = 'Aceptado' 
    AND DATE(i.fecha_hora) BETWEEN '$filtroFechaInicio' AND '$filtroFechaFin'";
  
  if ($filtroCategoria > 0) {
    $whereVentasFiltro .= " AND a.idcategoria = $filtroCategoria";
    $whereComprasFiltro .= " AND a.idcategoria = $filtroCategoria";
  }
  
  // WHERE para periodo comparativo
  $whereVentasComp = "v.estado = 'Aceptado' 
    AND DATE(v.fecha_hora) BETWEEN '$fechaInicioComp' AND '$fechaFinComp'";
  
  if ($filtroCategoria > 0) {
    $whereVentasComp .= " AND a.idcategoria = $filtroCategoria";
  }

  /* ============================================================
     TOTALES DEL DÍA DE HOY (NO MODIFICAR - según especificación)
     ============================================================ */
  $rsptac = $consulta->totalcomprahoy();
  $regc   = $rsptac ? $rsptac->fetch_object() : null;
  $totalc = $regc->total_compra ?? 0;

  $rsptav = $consulta->totalventahoy();
  $regv   = $rsptav ? $rsptav->fetch_object() : null;
  $totalv = $regv->total_venta ?? 0;

  /* ============================================================
     MÉTRICAS EJECUTIVAS - PERIODO FILTRADO
     ============================================================ */

  // 1. RENTABILIDAD Y MÁRGENES
  $sql = "SELECT 
            IFNULL(SUM(dv.cantidad * dv.precio_venta), 0) AS ingresos_totales,
            IFNULL(SUM(dv.cantidad * a.precio_compra), 0) AS costos_totales
          FROM detalle_venta dv
          INNER JOIN venta v ON dv.idventa = v.idventa
          INNER JOIN articulo a ON dv.idarticulo = a.idarticulo
          WHERE $whereVentasFiltro";
  $rs = ejecutarConsulta($sql);
  $row = $rs ? $rs->fetch_object() : null;
  $ingresosPeriodo = $row ? (float)$row->ingresos_totales : 0;
  $costosPeriodo = $row ? (float)$row->costos_totales : 0;
  $margenBrutoPeriodo = $ingresosPeriodo - $costosPeriodo;
  $porcentajeMargen = $ingresosPeriodo > 0 ? (($margenBrutoPeriodo / $ingresosPeriodo) * 100) : 0;

  // Comparativo si está activado
  $ingresosPeriodoComp = 0;
  $variacionIngresos = 0;
  $variacionPorcentaje = 0;
  
  if ($filtroComparativo > 0) {
    $sqlComp = "SELECT 
                  IFNULL(SUM(dv.cantidad * dv.precio_venta), 0) AS ingresos_totales
                FROM detalle_venta dv
                INNER JOIN venta v ON dv.idventa = v.idventa
                INNER JOIN articulo a ON dv.idarticulo = a.idarticulo
                WHERE $whereVentasComp";
    $rsComp = ejecutarConsulta($sqlComp);
    $rowComp = $rsComp ? $rsComp->fetch_object() : null;
    $ingresosPeriodoComp = $rowComp ? (float)$rowComp->ingresos_totales : 0;
    
    $variacionIngresos = $ingresosPeriodo - $ingresosPeriodoComp;
    $variacionPorcentaje = $ingresosPeriodoComp > 0 ? (($variacionIngresos / $ingresosPeriodoComp) * 100) : 0;
  }

  // 2. ROTACIÓN DE INVENTARIO
  $whereInventario = "a.condicion = 1";
  if ($filtroCategoria > 0) {
    $whereInventario .= " AND a.idcategoria = $filtroCategoria";
  }
  
  $sql = "SELECT 
            COUNT(DISTINCT a.idarticulo) AS total_productos,
            IFNULL(SUM(a.stock), 0) AS stock_total,
            IFNULL(SUM(a.stock * a.precio_compra), 0) AS valor_inventario
          FROM articulo a
          WHERE $whereInventario";
  $rs = ejecutarConsulta($sql);
  $row = $rs ? $rs->fetch_object() : null;
  $totalProductos = $row ? (int)$row->total_productos : 0;
  $stockTotal = $row ? (int)$row->stock_total : 0;
  $valorInventario = $row ? (float)$row->valor_inventario : 0;
  
  // Cálculo de rotación de inventario (días)
  $diasRotacion = 0;
  if ($valorInventario > 0 && $costosPeriodo > 0) {
    $diasPeriodo = (strtotime($filtroFechaFin) - strtotime($filtroFechaInicio)) / 86400 + 1;
    $costoDiario = $costosPeriodo / $diasPeriodo;
    $diasRotacion = $costoDiario > 0 ? round($valorInventario / $costoDiario, 1) : 0;
  }
  
  // Productos sin movimiento
  $sql = "SELECT COUNT(*) as productos_sin_venta
          FROM articulo a
          WHERE a.condicion = 1
          AND a.idarticulo NOT IN (
            SELECT DISTINCT dv.idarticulo 
            FROM detalle_venta dv
            INNER JOIN venta v ON dv.idventa = v.idventa
            WHERE v.estado = 'Aceptado'
            AND DATE(v.fecha_hora) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
          )
          " . ($filtroCategoria > 0 ? "AND a.idcategoria = $filtroCategoria" : "");
  $rs = ejecutarConsulta($sql);
  $row = $rs ? $rs->fetch_object() : null;
  $productosSinVenta = $row ? (int)$row->productos_sin_venta : 0;

  // 3. ANÁLISIS DE CLIENTES (CORREGIDO)
  $sql = "SELECT COUNT(DISTINCT v.idcliente) AS total_clientes
          FROM venta v
          WHERE $whereVentasFiltro";
  $rs = ejecutarConsulta($sql);
  $row = $rs ? $rs->fetch_object() : null;
  $clientesActivos = $row ? (int)$row->total_clientes : 0;

  // 4. TICKET PROMEDIO
  $sql = "SELECT 
            COUNT(*) AS num_ventas,
            IFNULL(SUM(total_venta), 0) AS total
          FROM venta v
          WHERE $whereVentasFiltro";
  $rs = ejecutarConsulta($sql);
  $row = $rs ? $rs->fetch_object() : null;
  $numVentasPeriodo = $row ? (int)$row->num_ventas : 0;
  $totalVentasPeriodo = $row ? (float)$row->total : 0;
  $ticketPromedio = $numVentasPeriodo > 0 ? ($totalVentasPeriodo / $numVentasPeriodo) : 0;

  /* ============================================================
     TOP 10 PRODUCTOS MÁS VENDIDOS (CORREGIDO - CON IMAGEN)
     ============================================================ */
  $sql = "SELECT 
            a.idarticulo,
            a.nombre,
            a.imagen,
            a.precio_venta,
            c.nombre AS categoria,
            SUM(dv.cantidad) AS unidades_vendidas,
            SUM(dv.cantidad * dv.precio_venta) AS ingresos_generados
          FROM detalle_venta dv
          INNER JOIN venta v ON dv.idventa = v.idventa
          INNER JOIN articulo a ON dv.idarticulo = a.idarticulo
          INNER JOIN categoria c ON a.idcategoria = c.idcategoria
          WHERE $whereVentasFiltro
          GROUP BY a.idarticulo, a.nombre, a.imagen, a.precio_venta, c.nombre
          ORDER BY unidades_vendidas DESC
          LIMIT 10";
  $topProductos = ejecutarConsulta($sql);

  /* ============================================================
     ANÁLISIS DE CATEGORÍAS (PARA DONUT CHART)
     ============================================================ */
  $sql = "SELECT 
            c.nombre AS categoria,
            SUM(dv.cantidad * dv.precio_venta) AS total_ventas
          FROM detalle_venta dv
          INNER JOIN venta v ON dv.idventa = v.idventa
          INNER JOIN articulo a ON dv.idarticulo = a.idarticulo
          INNER JOIN categoria c ON a.idcategoria = c.idcategoria
          WHERE $whereVentasFiltro
          GROUP BY c.idcategoria, c.nombre
          ORDER BY total_ventas DESC
          LIMIT 8";
  $rsCategorias = ejecutarConsulta($sql);
  $labelsCateg = '';
  $dataCateg = '';
  if ($rsCategorias) {
    while ($reg = $rsCategorias->fetch_object()) {
      $labelsCateg .= '"' . htmlspecialchars($reg->categoria, ENT_QUOTES, 'UTF-8') . '",';
      $dataCateg .= number_format((float)$reg->total_ventas, 2, '.', '') . ',';
    }
  }
  $labelsCateg = rtrim($labelsCateg, ',');
  $dataCateg = rtrim($dataCateg, ',');

  /* ============================================================
     TOP 5 CLIENTES (CORREGIDO - ANÁLISIS DE LEALTAD)
     ============================================================ */
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
  $topClientes = ejecutarConsulta($sql);

  /* ============================================================
     EVOLUCIÓN VENTAS VS COMPRAS (ÚLTIMOS 6 MESES O PERSONALIZADO)
     ============================================================ */
  // Calcular rango dinámico basado en filtros
  $rangoMeses = 6;
  $fechaInicioGrafico = date('Y-m-01', strtotime($filtroFechaFin . " -$rangoMeses months"));
  
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
  $rsVentas6 = ejecutarConsulta($sql);
  $meses6 = '';
  $ventas6 = '';
  if ($rsVentas6) {
    while ($reg = $rsVentas6->fetch_object()) {
      $meses6 .= '"' . $reg->mes . '",';
      $ventas6 .= number_format((float)$reg->ventas, 2, '.', '') . ',';
    }
  }
  $meses6 = rtrim($meses6, ',');
  $ventas6 = rtrim($ventas6, ',');

  $sql = "SELECT 
            DATE_FORMAT(i.fecha_hora, '%b %Y') AS mes,
            YEAR(i.fecha_hora) AS anio,
            MONTH(i.fecha_hora) AS mes_num,
            SUM(i.total_compra) AS compras
          FROM ingreso i
          WHERE i.estado = 'Aceptado'
            AND DATE(i.fecha_hora) >= '$fechaInicioGrafico'
            AND DATE(i.fecha_hora) <= '$filtroFechaFin'
          GROUP BY YEAR(i.fecha_hora), MONTH(i.fecha_hora)
          ORDER BY YEAR(i.fecha_hora) ASC, MONTH(i.fecha_hora) ASC";
  $rsCompras6 = ejecutarConsulta($sql);
  $compras6 = '';
  if ($rsCompras6) {
    while ($reg = $rsCompras6->fetch_object()) {
      $compras6 .= number_format((float)$reg->compras, 2, '.', '') . ',';
    }
  }
  $compras6 = rtrim($compras6, ',');

  /* ============================================================
     PRODUCTOS CON STOCK CRÍTICO (ALERTAS)
     ============================================================ */
  $sql = "SELECT 
            a.nombre,
            a.stock,
            c.nombre AS categoria
          FROM articulo a
          INNER JOIN categoria c ON a.idcategoria = c.idcategoria
          WHERE a.condicion = 1 
            AND a.stock > 0 
            AND a.stock < 5
            " . ($filtroCategoria > 0 ? "AND a.idcategoria = $filtroCategoria" : "") . "
          ORDER BY a.stock ASC
          LIMIT 5";
  $stockCritico = ejecutarConsulta($sql);

  /* ============================================================
     ANÁLISIS DE RENTABILIDAD POR PRODUCTO (TOP 5 MEJORES Y PEORES)
     ============================================================ */
  $sql = "SELECT 
            a.nombre,
            c.nombre AS categoria,
            SUM(dv.cantidad) AS unidades_vendidas,
            SUM(dv.cantidad * dv.precio_venta) AS ingresos,
            SUM(dv.cantidad * a.precio_compra) AS costos,
            (SUM(dv.cantidad * dv.precio_venta) - SUM(dv.cantidad * a.precio_compra)) AS ganancia,
            ROUND(((SUM(dv.cantidad * dv.precio_venta) - SUM(dv.cantidad * a.precio_compra)) / 
                   SUM(dv.cantidad * dv.precio_venta) * 100), 2) AS margen_porcentaje
          FROM detalle_venta dv
          INNER JOIN venta v ON dv.idventa = v.idventa
          INNER JOIN articulo a ON dv.idarticulo = a.idarticulo
          INNER JOIN categoria c ON a.idcategoria = c.idcategoria
          WHERE $whereVentasFiltro
          GROUP BY a.idarticulo, a.nombre, c.nombre
          HAVING unidades_vendidas >= 2
          ORDER BY margen_porcentaje DESC
          LIMIT 5";
  $mejoresMargenesProductos = ejecutarConsulta($sql);

  $sql = "SELECT 
            a.nombre,
            c.nombre AS categoria,
            SUM(dv.cantidad) AS unidades_vendidas,
            SUM(dv.cantidad * dv.precio_venta) AS ingresos,
            SUM(dv.cantidad * a.precio_compra) AS costos,
            (SUM(dv.cantidad * dv.precio_venta) - SUM(dv.cantidad * a.precio_compra)) AS ganancia,
            ROUND(((SUM(dv.cantidad * dv.precio_venta) - SUM(dv.cantidad * a.precio_compra)) / 
                   SUM(dv.cantidad * dv.precio_venta) * 100), 2) AS margen_porcentaje
          FROM detalle_venta dv
          INNER JOIN venta v ON dv.idventa = v.idventa
          INNER JOIN articulo a ON dv.idarticulo = a.idarticulo
          INNER JOIN categoria c ON a.idcategoria = c.idcategoria
          WHERE $whereVentasFiltro
          GROUP BY a.idarticulo, a.nombre, c.nombre
          HAVING unidades_vendidas >= 2
          ORDER BY margen_porcentaje ASC
          LIMIT 5";
  $peoresMargenesProductos = ejecutarConsulta($sql);

  /* ============================================================
     VENTAS POR DÍA DE LA SEMANA (HEATMAP)
     ============================================================ */
  $sql = "SELECT 
            DAYNAME(v.fecha_hora) AS dia_semana,
            DAYOFWEEK(v.fecha_hora) AS dia_num,
            COUNT(*) AS num_ventas,
            SUM(v.total_venta) AS total
          FROM venta v
          WHERE $whereVentasFiltro
          GROUP BY DAYOFWEEK(v.fecha_hora), DAYNAME(v.fecha_hora)
          ORDER BY DAYOFWEEK(v.fecha_hora)";
  $rsDias = ejecutarConsulta($sql);
  $diasSemana = '';
  $ventasDias = '';
  if ($rsDias) {
    while ($reg = $rsDias->fetch_object()) {
      $diasSemana .= '"' . $reg->dia_semana . '",';
      $ventasDias .= number_format((float)$reg->total, 2, '.', '') . ',';
    }
  }
  $diasSemana = rtrim($diasSemana, ',');
  $ventasDias = rtrim($ventasDias, ',');

  /* ============================================================
     OBTENER CATEGORÍAS PARA FILTRO
     ============================================================ */
  $sql = "SELECT idcategoria, nombre FROM categoria WHERE condicion = 1 ORDER BY nombre ASC";
  $rsCategoriasFiltro = ejecutarConsulta($sql);

  ?>
  <style>
    :root{
      --neko-primary:#1565c0;
      --neko-primary-dark:#0d47a1;
      --neko-bg:#f5f7fb;
      --card-border:1px solid rgba(2,24,54,.06);
      --shadow:0 8px 24px rgba(2,24,54,.06);
      --success:#10b981;
      --warning:#f59e0b;
      --danger:#ef4444;
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

    /* Resumen Ejecutivo */
    .executive-summary{
      background:linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius:12px;
      padding:20px;
      margin-bottom:24px;
      box-shadow:0 10px 30px rgba(102,126,234,0.3);
    }
    .executive-summary__header{
      color:#fff;
      font-size:1.1rem;
      font-weight:700;
      margin-bottom:16px;
      display:flex;
      align-items:center;
      gap:8px;
    }
    .executive-summary__content{
      display:grid;
      grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));
      gap:16px;
    }
    .summary-stat{
      background:rgba(255,255,255,0.95);
      border-radius:10px;
      padding:14px;
      display:flex;
      align-items:center;
      gap:12px;
      transition:all 0.3s ease;
    }
    .summary-stat:hover{
      transform:translateY(-4px);
      box-shadow:0 8px 20px rgba(0,0,0,0.15);
    }
    .summary-stat__icon{
      width:50px;
      height:50px;
      border-radius:10px;
      display:grid;
      place-items:center;
      font-size:1.3rem;
    }
    .summary-stat__info{
      flex:1;
    }
    .summary-stat__label{
      font-size:0.75rem;
      color:#64748b;
      font-weight:600;
      text-transform:uppercase;
      letter-spacing:0.5px;
      margin-bottom:4px;
    }
    .summary-stat__value{
      font-size:1.3rem;
      font-weight:800;
      color:#0f172a;
    }

    /* Tooltip helper */
    .tooltip-icon{
      display:inline-block;
      width:16px;
      height:16px;
      background:#94a3b8;
      color:#fff;
      border-radius:50%;
      text-align:center;
      line-height:16px;
      font-size:11px;
      cursor:help;
      margin-left:4px;
    }
    .tooltip-icon:hover{
      background:var(--neko-primary);
    }

    /* Accesos Rápidos */
    .quick-access{
      background:#fff;
      border:var(--card-border);
      border-radius:12px;
      padding:16px;
      margin-bottom:24px;
      box-shadow:var(--shadow);
    }
    .quick-access__title{
      font-size:0.95rem;
      font-weight:700;
      color:#0b2752;
      margin-bottom:12px;
      display:flex;
      align-items:center;
      gap:8px;
    }
    .quick-access__grid{
      display:grid;
      grid-template-columns:repeat(auto-fit, minmax(140px, 1fr));
      gap:12px;
    }
    .quick-access-card{
      display:flex;
      flex-direction:column;
      align-items:center;
      gap:8px;
      padding:14px;
      background:#f8fafc;
      border:1px solid #e2e8f0;
      border-radius:10px;
      text-decoration:none;
      transition:all 0.3s ease;
    }
    .quick-access-card:hover{
      transform:translateY(-4px);
      box-shadow:0 8px 20px rgba(0,0,0,0.1);
      border-color:var(--neko-primary);
      text-decoration:none;
    }
    .quick-access-card__icon{
      width:48px;
      height:48px;
      border-radius:12px;
      display:grid;
      place-items:center;
      font-size:1.4rem;
    }
    .quick-access-card__label{
      font-size:0.85rem;
      font-weight:600;
      color:#475569;
      text-align:center;
    }
    .quick-access-card:hover .quick-access-card__label{
      color:var(--neko-primary);
    }

    /* Panel de Filtros */
    .filter-panel{
      background:#fff;
      border:var(--card-border);
      border-radius:12px;
      box-shadow:var(--shadow);
      padding:14px 18px;
      margin-bottom:20px;
    }
    .filter-panel__title{
      font-size:0.9rem;
      font-weight:700;
      color:#0b2752;
      margin:0 0 10px;
      display:flex;
      align-items:center;
      gap:8px;
    }
    .filter-panel__title i{
      color:var(--neko-primary);
    }
    .filter-row{
      display:flex;
      gap:10px;
      flex-wrap:nowrap;
      align-items:flex-end;
    }
    .filter-group{
      flex:0 0 auto;
      min-width:160px;
    }
    .filter-group label{
      display:block;
      font-size:0.75rem;
      font-weight:600;
      color:#475569;
      margin-bottom:5px;
      text-transform:uppercase;
      letter-spacing:0.3px;
    }
    .filter-group input,
    .filter-group select{
      width:100%;
      padding:8px 10px;
      border:1px solid #e2e8f0;
      border-radius:6px;
      font-size:0.85rem;
      transition:all 0.3s ease;
    }
    .filter-group input:focus,
    .filter-group select:focus{
      outline:none;
      border-color:var(--neko-primary);
      box-shadow:0 0 0 3px rgba(21,101,192,0.1);
    }
    .filter-actions{
      display:flex;
      gap:8px;
      align-items:flex-end;
      margin-left:auto;
    }
    .btn-filter{
      padding:8px 16px;
      border:none;
      border-radius:6px;
      font-weight:600;
      font-size:0.85rem;
      cursor:pointer;
      transition:all 0.3s ease;
      display:inline-flex;
      align-items:center;
      gap:5px;
      white-space:nowrap;
    }
    .btn-filter--primary{
      background:var(--neko-primary);
      color:#fff;
    }
    .btn-filter--primary:hover{
      background:var(--neko-primary-dark);
      transform:translateY(-2px);
      box-shadow:0 4px 12px rgba(21,101,192,0.3);
    }
    .btn-filter--secondary{
      background:#f1f5f9;
      color:#475569;
    }
    .btn-filter--secondary:hover{
      background:#e2e8f0;
    }
    .btn-filter--export{
      background:#10b981;
      color:#fff;
    }
    .btn-filter--export:hover{
      background:#059669;
      transform:translateY(-2px);
      box-shadow:0 4px 12px rgba(16,185,129,0.3);
    }

    /* Presets más compactos */
    .quick-presets{
      display:flex;
      gap:6px;
      flex-wrap:wrap;
      margin-top:10px;
    }
    .preset-btn{
      padding:5px 10px;
      background:#f8fafc;
      border:1px solid #e2e8f0;
      border-radius:5px;
      font-size:0.75rem;
      font-weight:600;
      color:#475569;
      cursor:pointer;
      transition:all 0.3s ease;
    }
    .preset-btn:hover{
      background:var(--neko-primary);
      color:#fff;
      border-color:var(--neko-primary);
    }
    .kpi{
      display:flex; align-items:center; gap:14px;
      background:#fff; border:var(--card-border); border-radius:12px; box-shadow:var(--shadow);
      padding:14px 16px; height:100%; transition: all 0.3s ease;
    }
    .kpi:hover{
      transform: translateY(-2px);
      box-shadow:0 12px 28px rgba(2,24,54,.12);
    }
    .kpi__icon{
      width:46px; height:46px; display:grid; place-items:center; border-radius:10px;
      background:#e3f2fd; color:#0d47a1; font-size:20px;
    }
    .kpi__icon--success{ background:#d1fae5; color:#059669; }
    .kpi__icon--warning{ background:#fef3c7; color:#d97706; }
    .kpi__icon--danger{ background:#fee2e2; color:#dc2626; }
    .kpi__icon--purple{ background:#e9d5ff; color:#9333ea; }
    
    .kpi__label{ color:#64748b; margin:0; font-size:.85rem; font-weight:600; text-transform:uppercase; letter-spacing:0.5px; }
    .kpi__value{ margin:0; font-weight:700; color:#0b2752; font-size:1.35rem; }
    .kpi__sub{
      margin:4px 0 0; font-size:.78rem; color:#94a3b8;
    }
    .kpi__badge{
      display:inline-block;
      padding:3px 8px;
      border-radius:6px;
      font-size:0.7rem;
      font-weight:600;
      margin-top:4px;
    }
    .badge--success{ background:#d1fae5; color:#065f46; }
    .badge--warning{ background:#fef3c7; color:#92400e; }
    .badge--danger{ background:#fee2e2; color:#991b1b; }

    /* Contenedor de gráfico */
    .chart-card{
      background:#fff; border:var(--card-border); border-radius:12px; box-shadow:var(--shadow);
      padding:14px 16px; height:100%;
    }
    .chart-card h4{
      margin:0 0 10px; font-size:1rem; color:#0b2752; font-weight:600;
      display:flex;
      align-items:center;
      gap:8px;
    }
    .chart-card h4 i{
      color:var(--neko-primary);
      font-size:1.1rem;
    }
    .chart-holder{
      position: relative;
      height: 280px;
      width: 100%;
    }
    .chart-holder--small{
      height: 220px;
    }

    /* Tabla Top Productos */
    .top-table{
      width:100%;
      border-collapse:collapse;
    }
    .top-table th{
      background:#f8fafc;
      color:#475569;
      font-size:0.75rem;
      font-weight:600;
      text-transform:uppercase;
      padding:10px 12px;
      text-align:left;
      border-bottom:2px solid #e2e8f0;
    }
    .top-table td{
      padding:10px 12px;
      border-bottom:1px solid #f1f5f9;
      font-size:0.875rem;
    }
    .top-table tr:hover{
      background:#f8fafc;
    }
    .top-table__img{
      width:40px;
      height:40px;
      border-radius:8px;
      object-fit:cover;
      border:2px solid #e2e8f0;
    }
    .top-table__product{
      display:flex;
      align-items:center;
      gap:10px;
    }
    .top-table__name{
      font-weight:600;
      color:#0f172a;
      margin:0;
    }
    .top-table__cat{
      font-size:0.75rem;
      color:#94a3b8;
      margin:2px 0 0;
    }
    .top-table__metric{
      font-weight:700;
      color:#0d47a1;
    }

    /* Alertas Stock */
    .alert-item{
      display:flex;
      align-items:center;
      justify-content:space-between;
      padding:10px 12px;
      background:#fef2f2;
      border-left:3px solid #ef4444;
      border-radius:6px;
      margin-bottom:8px;
    }
    .alert-item__name{
      font-weight:600;
      color:#0f172a;
      font-size:0.875rem;
    }
    .alert-item__cat{
      font-size:0.75rem;
      color:#64748b;
    }
    .alert-item__stock{
      background:#dc2626;
      color:#fff;
      padding:4px 10px;
      border-radius:6px;
      font-weight:700;
      font-size:0.875rem;
    }

    /* Badges Cliente */
    .client-item{
      display:flex;
      justify-content:space-between;
      align-items:center;
      padding:10px 0;
      border-bottom:1px solid #f1f5f9;
    }
    .client-item:last-child{
      border-bottom:none;
    }
    .client-item__name{
      font-weight:600;
      color:#0f172a;
      font-size:0.875rem;
    }
    .client-item__metric{
      text-align:right;
    }
    .client-item__value{
      font-weight:700;
      color:#0d47a1;
      font-size:0.9rem;
    }
    .client-item__label{
      font-size:0.7rem;
      color:#94a3b8;
    }

    /* Items de rentabilidad */
    .rentabilidad-item{
      display:flex;
      justify-content:space-between;
      align-items:center;
      padding:12px;
      border-radius:8px;
      margin-bottom:10px;
      background:#f8fafc;
      transition:all 0.3s ease;
    }
    .rentabilidad-item:hover{
      background:#f1f5f9;
      transform:translateX(4px);
    }
    .rentabilidad-item__name{
      font-weight:600;
      color:#0f172a;
      font-size:0.875rem;
      margin-bottom:4px;
    }
    .rentabilidad-item__cat{
      font-size:0.75rem;
      color:#64748b;
    }
    .rentabilidad-item__metrics{
      text-align:right;
    }
    .rentabilidad-item__margen{
      font-weight:800;
      font-size:1.2rem;
      margin-bottom:2px;
    }
    .rentabilidad-item__ganancia{
      font-size:0.8rem;
      color:#64748b;
      font-weight:600;
    }

    .mb-16{ margin-bottom:16px; }
    .mb-20{ margin-bottom:20px; }
    
    /* Sección separadora */
    .section-divider{
      margin:24px 0;
      border-top:2px solid #e2e8f0;
      padding-top:24px;
    }
    .section-title{
      font-size:1.1rem;
      font-weight:700;
      color:#0b2752;
      margin:0 0 16px;
      display:flex;
      align-items:center;
      gap:8px;
    }
    .section-title i{
      color:var(--neko-primary);
    }

    /* Presets rápidos */
    .quick-presets{
      display:flex;
      gap:8px;
      flex-wrap:wrap;
      margin-top:12px;
    }
    .preset-btn{
      padding:6px 12px;
      background:#f8fafc;
      border:1px solid #e2e8f0;
      border-radius:6px;
      font-size:0.8rem;
      font-weight:600;
      color:#475569;
      cursor:pointer;
      transition:all 0.3s ease;
    }
    .preset-btn:hover{
      background:var(--neko-primary);
      color:#fff;
      border-color:var(--neko-primary);
    }
  </style>

  <div class="content-wrapper">
    <section class="content">
      <div class="row">
        <div class="col-md-12">

          <div class="neko-card">
            <div class="neko-card__header">
              <h1 class="neko-card__title">
                <i class="fa fa-dashboard"></i> Dashboard Ejecutivo - ERP Autopartes
              </h1>
              <div class="neko-actions">
                <span style="font-size:0.85rem;opacity:0.9;">
                  <i class="fa fa-calendar"></i> <?php echo date('d/m/Y H:i'); ?>
                </span>
              </div>
            </div>

            <div class="neko-card__body">
              
              <!-- ================== PANEL DE FILTROS AVANZADOS ================== -->
              <div class="filter-panel">
                <h3 class="filter-panel__title">
                  <i class="fa fa-filter"></i> Filtros Avanzados
                </h3>
                
                <form method="GET" action="" id="formFiltros">
                  <div class="filter-row">
                    
                    <div class="filter-group">
                      <label for="fecha_inicio">Fecha Inicio</label>
                      <input type="date" id="fecha_inicio" name="fecha_inicio" 
                             value="<?php echo $filtroFechaInicio; ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="filter-group">
                      <label for="fecha_fin">Fecha Fin</label>
                      <input type="date" id="fecha_fin" name="fecha_fin" 
                             value="<?php echo $filtroFechaFin; ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>

                    <div class="filter-actions">
                      <button type="submit" class="btn-filter btn-filter--primary">
                        <i class="fa fa-search"></i> Aplicar
                      </button>
                      <button type="button" onclick="limpiarFiltros()" class="btn-filter btn-filter--secondary">
                        <i class="fa fa-refresh"></i> Limpiar
                      </button>
                      <button type="button" onclick="guardarConfiguracion()" class="btn-filter btn-filter--secondary" title="Guardar configuración actual">
                        <i class="fa fa-bookmark"></i>
                      </button>
                      <button type="button" onclick="exportarDatos()" class="btn-filter btn-filter--export">
                        <i class="fa fa-file-excel-o"></i> Exportar
                      </button>
                    </div>

                  </div>

                  <!-- Presets rápidos -->
                  <div class="quick-presets">
                    <button type="button" class="preset-btn" onclick="aplicarPreset('hoy')">
                      <i class="fa fa-clock-o"></i> Hoy
                    </button>
                    <button type="button" class="preset-btn" onclick="aplicarPreset('semana')">
                      <i class="fa fa-calendar"></i> Semana
                    </button>
                    <button type="button" class="preset-btn" onclick="aplicarPreset('mes')">
                      <i class="fa fa-calendar-o"></i> Mes
                    </button>
                    <button type="button" class="preset-btn" onclick="aplicarPreset('trimestre')">
                      <i class="fa fa-calendar-check-o"></i> Trimestre
                    </button>
                    <button type="button" class="preset-btn" onclick="aplicarPreset('anio')">
                      <i class="fa fa-calendar"></i> Año
                    </button>
                  </div>
                </form>
              </div>

              <!-- ================== RESUMEN EJECUTIVO RÁPIDO ================== -->
              <div class="executive-summary">
                <div class="executive-summary__header">
                  <i class="fa fa-bolt"></i> Resumen Ejecutivo
                </div>
                <div class="executive-summary__content">
                  <div class="summary-stat">
                    <div class="summary-stat__icon" style="background:#d1fae5;color:#059669;">
                      <i class="fa fa-shopping-cart"></i>
                    </div>
                    <div class="summary-stat__info">
                      <div class="summary-stat__label">Total Ventas</div>
                      <div class="summary-stat__value">S/ <?php echo number_format($ingresosPeriodo, 2); ?></div>
                    </div>
                  </div>
                  
                  <div class="summary-stat">
                    <div class="summary-stat__icon" style="background:#fee2e2;color:#dc2626;">
                      <i class="fa fa-truck"></i>
                    </div>
                    <div class="summary-stat__info">
                      <div class="summary-stat__label">Total Compras</div>
                      <div class="summary-stat__value">S/ <?php echo number_format($costosPeriodo, 2); ?></div>
                    </div>
                  </div>
                  
                  <div class="summary-stat">
                    <div class="summary-stat__icon" style="background:#fef3c7;color:#d97706;">
                      <i class="fa fa-line-chart"></i>
                    </div>
                    <div class="summary-stat__info">
                      <div class="summary-stat__label">Margen Neto</div>
                      <div class="summary-stat__value"><?php echo number_format($porcentajeMargen, 1); ?>%</div>
                    </div>
                  </div>
                  
                  <div class="summary-stat">
                    <div class="summary-stat__icon" style="background:#e9d5ff;color:#9333ea;">
                      <i class="fa fa-repeat"></i>
                    </div>
                    <div class="summary-stat__info">
                      <div class="summary-stat__label">Transacciones</div>
                      <div class="summary-stat__value"><?php echo number_format($numVentasPeriodo); ?></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- ================== ACCESOS RÁPIDOS ================== -->
              <div class="quick-access">
                <div class="quick-access__title">
                  <i class="fa fa-flash"></i> Accesos Rápidos
                </div>
                <div class="quick-access__grid">
                  <a href="venta.php" class="quick-access-card">
                    <div class="quick-access-card__icon" style="background:#e0f2fe;color:#0284c7;">
                      <i class="fa fa-cart-plus"></i>
                    </div>
                    <div class="quick-access-card__label">Nueva Venta</div>
                  </a>
                  
                  <a href="ingreso.php" class="quick-access-card">
                    <div class="quick-access-card__icon" style="background:#fef3c7;color:#d97706;">
                      <i class="fa fa-download"></i>
                    </div>
                    <div class="quick-access-card__label">Nueva Compra</div>
                  </a>
                  
                  <a href="articulo.php" class="quick-access-card">
                    <div class="quick-access-card__icon" style="background:#e9d5ff;color:#9333ea;">
                      <i class="fa fa-cube"></i>
                    </div>
                    <div class="quick-access-card__label">Productos</div>
                  </a>
                  
                  <a href="persona.php" class="quick-access-card">
                    <div class="quick-access-card__icon" style="background:#d1fae5;color:#059669;">
                      <i class="fa fa-users"></i>
                    </div>
                    <div class="quick-access-card__label">Clientes</div>
                  </a>
                  
                  <a href="#" onclick="window.print();return false;" class="quick-access-card">
                    <div class="quick-access-card__icon" style="background:#fee2e2;color:#dc2626;">
                      <i class="fa fa-print"></i>
                    </div>
                    <div class="quick-access-card__label">Imprimir</div>
                  </a>
                  
                  <a href="#" onclick="mostrarAyuda();return false;" class="quick-access-card">
                    <div class="quick-access-card__icon" style="background:#f1f5f9;color:#475569;">
                      <i class="fa fa-question-circle"></i>
                    </div>
                    <div class="quick-access-card__label">Ayuda</div>
                  </a>
                </div>
              </div>

              <!-- ================== SECCIÓN 1: HOY (NO MODIFICAR) ================== -->
              <h3 class="section-title">
                <i class="fa fa-clock-o"></i> Operaciones de Hoy
              </h3>
              <div class="row mb-20">
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

              <div class="section-divider"></div>

              <!-- ================== SECCIÓN 2: MÉTRICAS EJECUTIVAS ================== -->
              <h3 class="section-title">
                <i class="fa fa-line-chart"></i> Análisis Financiero del Periodo Seleccionado
              </h3>
              <div class="row mb-20">
                <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12 mb-16">
                  <div class="kpi">
                    <div class="kpi__icon kpi__icon--success">
                      <i class="fa fa-money"></i>
                    </div>
                    <div style="width:100%;">
                      <p class="kpi__label">
                        Margen Bruto
                        <span class="tooltip-icon" title="Ingresos menos costos de productos vendidos">?</span>
                      </p>
                      <h3 class="kpi__value">S/ <?php echo number_format($margenBrutoPeriodo, 2, '.', ''); ?></h3>
                      <span class="kpi__badge badge--success">
                        <?php echo number_format($porcentajeMargen, 1); ?>% de rentabilidad
                      </span>
                    </div>
                  </div>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12 mb-16">
                  <div class="kpi">
                    <div class="kpi__icon kpi__icon--warning">
                      <i class="fa fa-cubes"></i>
                    </div>
                    <div style="width:100%;">
                      <p class="kpi__label">
                        Valor Inventario
                        <span class="tooltip-icon" title="Valor total del stock actual a precio de compra">?</span>
                      </p>
                      <h3 class="kpi__value">S/ <?php echo number_format($valorInventario, 2, '.', ''); ?></h3>
                      <p class="kpi__sub">
                        <?php echo number_format($stockTotal); ?> unidades • 
                        <?php echo $totalProductos; ?> productos
                      </p>
                      <?php if ($diasRotacion > 0): ?>
                      <span class="kpi__badge <?php echo $diasRotacion <= 30 ? 'badge--success' : ($diasRotacion <= 60 ? 'badge--warning' : 'badge--danger'); ?>">
                        <?php echo $diasRotacion; ?> días rotación
                      </span>
                      <?php endif; ?>
                      <?php if ($productosSinVenta > 0): ?>
                      <p class="kpi__sub" style="color:#ef4444;margin-top:6px;">
                        <i class="fa fa-exclamation-circle"></i> <?php echo $productosSinVenta; ?> sin venta
                      </p>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12 mb-16">
                  <div class="kpi">
                    <div class="kpi__icon kpi__icon--purple">
                      <i class="fa fa-users"></i>
                    </div>
                    <div style="width:100%;">
                      <p class="kpi__label">
                        Clientes Activos
                        <span class="tooltip-icon" title="Clientes únicos que realizaron compras en el periodo">?</span>
                      </p>
                      <h3 class="kpi__value"><?php echo $clientesActivos; ?></h3>
                      <p class="kpi__sub">Compraron en el periodo</p>
                    </div>
                  </div>
                </div>

                <div class="col-lg-3 col-md-6 col-sm-6 col-xs-12 mb-16">
                  <div class="kpi">
                    <div class="kpi__icon">
                      <i class="fa fa-shopping-cart"></i>
                    </div>
                    <div style="width:100%;">
                      <p class="kpi__label">
                        Ticket Promedio
                        <span class="tooltip-icon" title="Valor promedio por transacción de venta">?</span>
                      </p>
                      <h3 class="kpi__value">S/ <?php echo number_format($ticketPromedio, 2, '.', ''); ?></h3>
                      <p class="kpi__sub"><?php echo $numVentasPeriodo; ?> operaciones</p>
                    </div>
                  </div>
                </div>
              </div>

              <div class="section-divider"></div>

              <!-- ================== SECCIÓN 3: TOP PRODUCTOS + CATEGORÍAS ================== -->
              <h3 class="section-title">
                <i class="fa fa-star"></i> Rendimiento de Productos
              </h3>
              <div class="row mb-20">
                <div class="col-lg-6 col-md-12 mb-16">
                  <div class="chart-card">
                    <h4><i class="fa fa-trophy"></i> Top 10 Productos Más Vendidos (Periodo Seleccionado)</h4>
                    <div style="max-height:450px;overflow-y:auto;">
                      <table class="top-table">
                        <thead>
                          <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th style="text-align:right;">Unidades</th>
                            <th style="text-align:right;">Ingresos</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          $pos = 1;
                          if ($topProductos && $topProductos->num_rows > 0) {
                            while ($prod = $topProductos->fetch_object()) {
                              $imgSrc = !empty($prod->imagen) ? "../files/articulos/" . $prod->imagen : "../public/img/no-image.png";
                              $unidades = (int)$prod->unidades_vendidas;
                              $ingresos = (float)$prod->ingresos_generados;
                              ?>
                              <tr>
                                <td style="font-weight:700;color:#64748b;"><?php echo $pos; ?></td>
                                <td>
                                  <div class="top-table__product">
                                    <img src="<?php echo $imgSrc; ?>" class="top-table__img" alt="<?php echo htmlspecialchars($prod->nombre); ?>">
                                    <div>
                                      <p class="top-table__name"><?php echo htmlspecialchars($prod->nombre); ?></p>
                                      <p class="top-table__cat"><?php echo htmlspecialchars($prod->categoria); ?></p>
                                    </div>
                                  </div>
                                </td>
                                <td style="text-align:right;">
                                  <span class="top-table__metric"><?php echo $unidades; ?></span>
                                </td>
                                <td style="text-align:right;">
                                  <span class="top-table__metric">S/ <?php echo number_format($ingresos, 2); ?></span>
                                </td>
                              </tr>
                              <?php
                              $pos++;
                            }
                          } else {
                            echo '<tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:40px;">
                                  <i class="fa fa-inbox" style="font-size:3rem;opacity:0.3;display:block;margin-bottom:10px;"></i>
                                  No hay productos vendidos en el periodo seleccionado
                                  </td></tr>';
                          }
                          ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6 col-md-12 mb-16">
                  <div class="chart-card">
                    <h4><i class="fa fa-pie-chart"></i> Ventas por Categoría (Periodo Seleccionado)</h4>
                    <div class="chart-holder chart-holder--small">
                      <?php if (!empty($labelsCateg)): ?>
                      <canvas id="chartCategorias"></canvas>
                      <?php else: ?>
                      <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#94a3b8;text-align:center;">
                        <div>
                          <i class="fa fa-pie-chart" style="font-size:3rem;opacity:0.3;display:block;margin-bottom:10px;"></i>
                          No hay datos de categorías para mostrar
                        </div>
                      </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>

              <div class="section-divider"></div>

              <!-- ================== SECCIÓN 4: EVOLUCIÓN + TENDENCIAS ================== -->
              <h3 class="section-title">
                <i class="fa fa-area-chart"></i> Análisis Temporal
              </h3>
              <div class="row mb-20">
                <div class="col-lg-6 col-md-12 mb-16">
                  <div class="chart-card">
                    <h4><i class="fa fa-bar-chart"></i> Evolución Ventas vs Compras (Últimos 6 Meses)</h4>
                    <div class="chart-holder">
                      <canvas id="chartEvolucion"></canvas>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6 col-md-12 mb-16">
                  <div class="chart-card">
                    <h4><i class="fa fa-calendar-o"></i> Ventas por Día de la Semana (Periodo)</h4>
                    <div class="chart-holder">
                      <?php if (!empty($diasSemana)): ?>
                      <canvas id="chartDias"></canvas>
                      <?php else: ?>
                      <div style="display:flex;align-items:center;justify-content:center;height:100%;color:#94a3b8;text-align:center;">
                        <div>
                          <i class="fa fa-calendar" style="font-size:3rem;opacity:0.3;display:block;margin-bottom:10px;"></i>
                          No hay datos de días para mostrar
                        </div>
                      </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>

              <div class="section-divider"></div>

              <!-- ================== SECCIÓN 5: CLIENTES + ALERTAS ================== -->
              <h3 class="section-title">
                <i class="fa fa-users"></i> Clientes & Alertas de Inventario
              </h3>
              <div class="row mb-20">
                <div class="col-lg-6 col-md-12 mb-16">
                  <div class="chart-card">
                    <h4><i class="fa fa-star"></i> Top 5 Clientes del Periodo</h4>
                    <div style="padding:10px 0;">
                      <?php
                      if ($topClientes && $topClientes->num_rows > 0) {
                        while ($cli = $topClientes->fetch_object()) {
                          ?>
                          <div class="client-item">
                            <div>
                              <div class="client-item__name"><?php echo htmlspecialchars($cli->cliente); ?></div>
                              <div class="client-item__label"><?php echo (int)$cli->num_compras; ?> compras realizadas</div>
                            </div>
                            <div class="client-item__metric">
                              <div class="client-item__value">S/ <?php echo number_format((float)$cli->total_gastado, 2); ?></div>
                              <div class="client-item__label">Total invertido</div>
                            </div>
                          </div>
                          <?php
                        }
                      } else {
                        echo '<p style="color:#94a3b8;text-align:center;padding:20px;">
                              <i class="fa fa-inbox" style="font-size:2rem;opacity:0.3;display:block;margin-bottom:10px;"></i>
                              No hay clientes en el periodo seleccionado
                              </p>';
                      }
                      ?>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6 col-md-12 mb-16">
                  <div class="chart-card">
                    <h4><i class="fa fa-exclamation-triangle"></i> Productos con Stock Crítico (&lt; 5 unidades)</h4>
                    <div style="padding:10px 0;">
                      <?php
                      if ($stockCritico && $stockCritico->num_rows > 0) {
                        while ($alert = $stockCritico->fetch_object()) {
                          ?>
                          <div class="alert-item">
                            <div>
                              <div class="alert-item__name"><?php echo htmlspecialchars($alert->nombre); ?></div>
                              <div class="alert-item__cat"><?php echo htmlspecialchars($alert->categoria); ?></div>
                            </div>
                            <div class="alert-item__stock">
                              <?php echo (int)$alert->stock; ?> und.
                            </div>
                          </div>
                          <?php
                        }
                      } else {
                        echo '<p style="color:#10b981;text-align:center;padding:20px;"><i class="fa fa-check-circle"></i> No hay productos con stock crítico</p>';
                      }
                      ?>
                    </div>
                  </div>
                </div>
              </div>

              <div class="section-divider"></div>

              <!-- ================== SECCIÓN 6: ANÁLISIS DE RENTABILIDAD ================== -->
              <h3 class="section-title">
                <i class="fa fa-bar-chart"></i> Análisis de Rentabilidad por Producto
              </h3>
              <div class="row mb-20">
                <div class="col-lg-6 col-md-12 mb-16">
                  <div class="chart-card">
                    <h4 style="color:#059669;">
                      <i class="fa fa-arrow-up"></i> Top 5 Productos Más Rentables
                    </h4>
                    <div style="padding:10px 0;">
                      <?php
                      if ($mejoresMargenesProductos && $mejoresMargenesProductos->num_rows > 0) {
                        while ($prod = $mejoresMargenesProductos->fetch_object()) {
                          $margen = (float)$prod->margen_porcentaje;
                          $colorMargen = $margen >= 40 ? '#059669' : ($margen >= 25 ? '#f59e0b' : '#64748b');
                          ?>
                          <div class="rentabilidad-item">
                            <div>
                              <div class="rentabilidad-item__name"><?php echo htmlspecialchars($prod->nombre); ?></div>
                              <div class="rentabilidad-item__cat"><?php echo htmlspecialchars($prod->categoria); ?> • <?php echo (int)$prod->unidades_vendidas; ?> unidades</div>
                            </div>
                            <div class="rentabilidad-item__metrics">
                              <div class="rentabilidad-item__margen" style="color:<?php echo $colorMargen; ?>">
                                <?php echo number_format($margen, 1); ?>%
                              </div>
                              <div class="rentabilidad-item__ganancia">
                                S/ <?php echo number_format((float)$prod->ganancia, 2); ?>
                              </div>
                            </div>
                          </div>
                          <?php
                        }
                      } else {
                        echo '<p style="color:#94a3b8;text-align:center;padding:20px;">
                              <i class="fa fa-inbox" style="font-size:2rem;opacity:0.3;display:block;margin-bottom:10px;"></i>
                              No hay datos suficientes
                              </p>';
                      }
                      ?>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6 col-md-12 mb-16">
                  <div class="chart-card">
                    <h4 style="color:#dc2626;">
                      <i class="fa fa-arrow-down"></i> Top 5 Productos Menos Rentables
                    </h4>
                    <div style="padding:10px 0;">
                      <?php
                      if ($peoresMargenesProductos && $peoresMargenesProductos->num_rows > 0) {
                        while ($prod = $peoresMargenesProductos->fetch_object()) {
                          $margen = (float)$prod->margen_porcentaje;
                          $colorMargen = $margen < 10 ? '#dc2626' : ($margen < 20 ? '#f59e0b' : '#64748b');
                          ?>
                          <div class="rentabilidad-item">
                            <div>
                              <div class="rentabilidad-item__name"><?php echo htmlspecialchars($prod->nombre); ?></div>
                              <div class="rentabilidad-item__cat"><?php echo htmlspecialchars($prod->categoria); ?> • <?php echo (int)$prod->unidades_vendidas; ?> unidades</div>
                            </div>
                            <div class="rentabilidad-item__metrics">
                              <div class="rentabilidad-item__margen" style="color:<?php echo $colorMargen; ?>">
                                <?php echo number_format($margen, 1); ?>%
                              </div>
                              <div class="rentabilidad-item__ganancia">
                                S/ <?php echo number_format((float)$prod->ganancia, 2); ?>
                              </div>
                            </div>
                          </div>
                          <?php
                        }
                      } else {
                        echo '<p style="color:#94a3b8;text-align:center;padding:20px;">
                              <i class="fa fa-inbox" style="font-size:2rem;opacity:0.3;display:block;margin-bottom:10px;"></i>
                              No hay datos suficientes
                              </p>';
                      }
                      ?>
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

  <!-- Chart.js -->
  <script src="../public/js/Chart.bundle.min.js"></script>

  <script>
    // Colores corporativos
    const nekoBlue = 'rgba(21, 101, 192, 0.8)';
    const nekoBlueLight = 'rgba(21, 101, 192, 0.2)';
    const nekoDark = 'rgba(13, 71, 161, 0.8)';
    const nekoDarkLight = 'rgba(13, 71, 161, 0.2)';
    const nekoSuccess = 'rgba(16, 185, 129, 0.8)';
    const nekoWarning = 'rgba(245, 158, 11, 0.8)';
    const nekoDanger = 'rgba(239, 68, 68, 0.8)';
    const nekoPurple = 'rgba(147, 51, 234, 0.8)';

    // ==================== FUNCIONES DE FILTROS ====================
    function limpiarFiltros() {
      window.location.href = window.location.pathname;
    }

    function aplicarPreset(preset) {
      const hoy = new Date();
      let fechaInicio, fechaFin;

      switch(preset) {
        case 'hoy':
          fechaInicio = fechaFin = formatDate(hoy);
          break;
        case 'semana':
          const primerDia = new Date(hoy.setDate(hoy.getDate() - hoy.getDay() + 1));
          fechaInicio = formatDate(primerDia);
          fechaFin = formatDate(new Date());
          break;
        case 'mes':
          fechaInicio = formatDate(new Date(hoy.getFullYear(), hoy.getMonth(), 1));
          fechaFin = formatDate(new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0));
          break;
        case 'trimestre':
          const mesActual = hoy.getMonth();
          const primerMesTrimestre = Math.floor(mesActual / 3) * 3;
          fechaInicio = formatDate(new Date(hoy.getFullYear(), primerMesTrimestre, 1));
          fechaFin = formatDate(new Date(hoy.getFullYear(), primerMesTrimestre + 3, 0));
          break;
        case 'anio':
          fechaInicio = formatDate(new Date(hoy.getFullYear(), 0, 1));
          fechaFin = formatDate(new Date(hoy.getFullYear(), 11, 31));
          break;
      }

      document.getElementById('fecha_inicio').value = fechaInicio;
      document.getElementById('fecha_fin').value = fechaFin;
      document.getElementById('formFiltros').submit();
    }

    function formatDate(date) {
      const year = date.getFullYear();
      const month = String(date.getMonth() + 1).padStart(2, '0');
      const day = String(date.getDate()).padStart(2, '0');
      return `${year}-${month}-${day}`;
    }

    function exportarDatos() {
      // Obtener parámetros actuales
      const params = new URLSearchParams(window.location.search);
      
      // Crear formulario temporal para exportar
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = 'exportar_dashboard.php';
      
      // Agregar campos
      ['fecha_inicio', 'fecha_fin'].forEach(name => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = name;
        input.value = params.get(name) || document.querySelector(`[name="${name}"]`).value;
        form.appendChild(input);
      });
      
      document.body.appendChild(form);
      form.submit();
      document.body.removeChild(form);
      
      // Nota: Necesitarás crear el archivo exportar_dashboard.php para generar Excel/PDF
      alert('Función de exportación en desarrollo. Crea el archivo exportar_dashboard.php para implementarla.');
    }

    function guardarConfiguracion() {
      const config = {
        fecha_inicio: document.getElementById('fecha_inicio').value,
        fecha_fin: document.getElementById('fecha_fin').value
      };
      
      localStorage.setItem('dashboard_config', JSON.stringify(config));
      
      alert('✓ Configuración guardada exitosamente\n\nLa próxima vez que ingreses, se cargarán estos filtros automáticamente.');
    }

    function cargarConfiguracionGuardada() {
      const saved = localStorage.getItem('dashboard_config');
      if (saved && !window.location.search) {
        const config = JSON.parse(saved);
        document.getElementById('fecha_inicio').value = config.fecha_inicio;
        document.getElementById('fecha_fin').value = config.fecha_fin;
      }
    }

    function mostrarAyuda() {
      const ayuda = `
═══════════════════════════════════════════
      GUÍA RÁPIDA - DASHBOARD ERP
═══════════════════════════════════════════

📊 FILTROS AVANZADOS:
   • Selecciona rangos de fecha personalizados
   • Filtra por categoría específica
   • Compara con periodos anteriores
   • Usa presets rápidos para análisis común

💡 INDICADORES CLAVE:
   • Margen Bruto: Rentabilidad general
   • Valor Inventario: Capital inmovilizado
   • Días Rotación: Velocidad de venta
   • Ticket Promedio: Valor por operación

📈 ANÁLISIS:
   • Top Productos: Mejores vendedores
   • Categorías: Distribución de ventas
   • Clientes: Análisis de lealtad
   • Rentabilidad: Márgenes por producto

⚙️ FUNCIONES:
   • 🔖 Guardar: Memoriza tu configuración
   • 📥 Exportar: Descarga reportes
   • 🖨️ Imprimir: Genera informes físicos

═══════════════════════════════════════════
Para soporte técnico, contacta al administrador
      `;
      
      alert(ayuda);
    }

    // Cargar configuración guardada al iniciar
    window.addEventListener('DOMContentLoaded', cargarConfiguracionGuardada);

    // ==================== GRÁFICO CATEGORÍAS (DONUT) ====================
    (function(){
      const el = document.getElementById("chartCategorias");
      if (!el) return;
      const ctx = el.getContext('2d');
      
      new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: [<?php echo $labelsCateg ?: ''; ?>],
          datasets: [{
            data: [<?php echo $dataCateg ?: '0'; ?>],
            backgroundColor: [
              nekoBlue, nekoDark, nekoSuccess, nekoWarning, 
              nekoDanger, nekoPurple, 'rgba(99, 102, 241, 0.8)', 'rgba(236, 72, 153, 0.8)'
            ],
            borderWidth: 2,
            borderColor: '#fff'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          legend: {
            position: 'right',
            labels: {
              fontSize: 11,
              padding: 10,
              boxWidth: 12
            }
          },
          tooltips: {
            callbacks: {
              label: function(item, data) {
                const label = data.labels[item.index];
                const value = data.datasets[0].data[item.index];
                return label + ': S/ ' + parseFloat(value).toFixed(2);
              }
            }
          }
        }
      });
    })();

    // ==================== EVOLUCIÓN VENTAS VS COMPRAS ====================
    (function(){
      const el = document.getElementById("chartEvolucion");
      if (!el) return;
      const ctx = el.getContext('2d');
      
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: [<?php echo $meses6 ?: ''; ?>],
          datasets: [{
            label: 'Ventas S/',
            data: [<?php echo $ventas6 ?: '0'; ?>],
            backgroundColor: nekoBlueLight,
            borderColor: nekoBlue,
            borderWidth: 3,
            fill: true,
            tension: 0.4
          },{
            label: 'Compras S/',
            data: [<?php echo $compras6 ?: '0'; ?>],
            backgroundColor: 'rgba(239, 68, 68, 0.1)',
            borderColor: nekoDanger,
            borderWidth: 3,
            fill: true,
            tension: 0.4
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            yAxes: [{
              ticks: {
                beginAtZero: true,
                callback: function(value) {
                  return 'S/ ' + value.toLocaleString();
                }
              }
            }]
          },
          legend: {
            display: true,
            position: 'top'
          },
          tooltips: {
            mode: 'index',
            intersect: false,
            callbacks: {
              label: function(item, data) {
                const label = data.datasets[item.datasetIndex].label;
                const value = item.yLabel;
                return label + ': S/ ' + value.toLocaleString();
              }
            }
          }
        }
      });
    })();

    // ==================== VENTAS POR DÍA DE LA SEMANA ====================
    (function(){
      const el = document.getElementById("chartDias");
      if (!el) return;
      const ctx = el.getContext('2d');
      
      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: [<?php echo $diasSemana ?: ''; ?>],
          datasets: [{
            label: 'Ventas S/',
            data: [<?php echo $ventasDias ?: '0'; ?>],
            backgroundColor: [
              nekoBlue, nekoDark, nekoSuccess, nekoWarning, 
              nekoPurple, 'rgba(99, 102, 241, 0.6)', nekoDanger
            ],
            borderWidth: 0
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            yAxes: [{
              ticks: {
                beginAtZero: true,
                callback: function(value) {
                  return 'S/ ' + value.toLocaleString();
                }
              }
            }]
          },
          legend: {
            display: false
          },
          tooltips: {
            callbacks: {
              label: function(item) {
                return 'Total: S/ ' + item.yLabel.toLocaleString();
              }
            }
          }
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