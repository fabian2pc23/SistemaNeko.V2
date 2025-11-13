<?php  
// vistas/articulo.php  —  Estilo corporativo y formulario ordenado
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/_requires_auth.php';
require 'header.php';

$canAlmacen = !empty($_SESSION['almacen']) && (int)$_SESSION['almacen'] === 1;

// Paleta (igual a proveedores)
$nekoPrimary = '#1565c0';
$nekoPrimaryDark = '#0d47a1';

?>
<?php if ($canAlmacen): ?>
<?php
  // ==================== KPIs DE STOCK ====================
  require_once "../config/Conexion.php";

  $sqlKpi = "
    SELECT
      COUNT(*)                                                                     AS total_articulos,
      SUM(CASE WHEN condicion = 1 THEN 1 ELSE 0 END)                               AS articulos_activos,
      SUM(CASE WHEN condicion = 0 THEN 1 ELSE 0 END)                               AS articulos_inactivos,
      SUM(CASE WHEN condicion = 1 AND stock > 0 THEN 1 ELSE 0 END)                 AS con_stock,
      SUM(CASE WHEN condicion = 1 AND stock <= 0 THEN 1 ELSE 0 END)                AS sin_stock,
      SUM(CASE WHEN condicion = 1 AND stock > 0 AND stock < 5 THEN 1 ELSE 0 END)   AS stock_bajo,
      IFNULL(SUM(CASE WHEN condicion = 1 THEN stock ELSE 0 END),0)                 AS stock_total
    FROM articulo
  ";
  $rsKpi  = ejecutarConsulta($sqlKpi);
  $rowKpi = $rsKpi ? $rsKpi->fetch_object() : null;

  $kpiTotalArticulos      = $rowKpi ? (int)$rowKpi->total_articulos      : 0;
  $kpiArticulosActivos    = $rowKpi ? (int)$rowKpi->articulos_activos    : 0;
  $kpiArticulosInactivos  = $rowKpi ? (int)$rowKpi->articulos_inactivos  : 0;
  $kpiConStock            = $rowKpi ? (int)$rowKpi->con_stock            : 0;
  $kpiSinStock            = $rowKpi ? (int)$rowKpi->sin_stock            : 0;
  $kpiStockBajo           = $rowKpi ? (int)$rowKpi->stock_bajo           : 0;
  $kpiStockTotal          = $rowKpi ? (int)$rowKpi->stock_total          : 0;
?>
<style>
  :root{
    --neko-primary: <?= $nekoPrimary ?>;
    --neko-primary-dark: <?= $nekoPrimaryDark ?>;
    --neko-bg:#f5f7fb;
  }
  .content-wrapper{ background: var(--neko-bg); }
  .neko-card{
    background:#fff; border:1px solid rgba(2,24,54,.06);
    border-radius:14px; box-shadow:0 8px 24px rgba(2,24,54,.06);
    overflow:hidden; margin-top:10px;
  }
  .neko-card__header{
    display:flex; align-items:center; justify-content:space-between;
    background: linear-gradient(90deg, var(--neko-primary-dark), var(--neko-primary));
    color:#fff; padding:14px 18px;
  }
  .neko-card__title{
    font-size:1.1rem; font-weight:600; letter-spacing:.2px; margin:0;
    display:flex; gap:10px; align-items:center;
  }
  .neko-actions .btn{ border-radius:10px; }
  .neko-card__body{ padding:18px; }

  .section-title{
    font-weight:600; color:#0b2752; margin:6px 0 16px; display:flex; align-items:center; gap:8px;
  }
  .section-title .dot{ width:8px; height:8px; border-radius:999px; background:var(--neko-primary); display:inline-block; }

  .help-hint{ color:#64748b; font-size:.85rem; margin-top:4px; }
  .readonly{ background:#f3f4f6 !important; color:#475569 !important; }
  .btn-primary{ background:var(--neko-primary); border-color:var(--neko-primary); }
  .btn-primary:hover{ background:var(--neko-primary-dark); border-color:var(--neko-primary-dark); }

  /* Separación vertical consistente entre grupos */
  .form-group{ margin-bottom:16px; }

  /* ================== KPIs de stock (UNA SOLA FILA CON 6 TARJETAS) ================== */
  .metric-row{
    margin-bottom:25px;
  }
  .metric-card{
    background:#ffffff;
    border-radius:18px;
    box-shadow:0 18px 35px rgba(15,23,42,.09);
    padding:12px 16px;
    display:flex;
    align-items:center;
    gap:14px;
    transition: all 0.3s ease;
  }
  .metric-card:hover{
    transform: translateY(-2px);
    box-shadow:0 20px 40px rgba(15,23,42,.12);
  }
  .metric-card__icon{
    width:40px;
    height:40px;
    border-radius:14px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:18px;
  }
  .metric-card__body{
    display:flex;
    flex-direction:column;
  }
  .metric-card__title{
    font-size:.78rem;
    text-transform:uppercase;
    letter-spacing:.08em;
    color:#64748b;
    margin:0 0 4px;
  }
  .metric-card__value{
    font-size:1.2rem;
    font-weight:700;
    color:#0f172a;
    margin:0;
  }

  .metric-card--blue  .metric-card__icon{ background:#e0ecff; color:#1d4ed8; }
  .metric-card--purple .metric-card__icon{ background:#ede9fe; color:#7c3aed; }
  .metric-card--green .metric-card__icon{ background:#dcfce7; color:#16a34a; }
  .metric-card--red   .metric-card__icon{ background:#fee2e2; color:#dc2626; }
  .metric-card--teal  .metric-card__icon{ background:#ccfbf1; color:#0f766e; }
  .metric-card--amber .metric-card__icon{ background:#fef3c7; color:#d97706; }

  @media (max-width: 991px){
    .metric-row .col-lg-2{ margin-bottom:10px; }
  }

  /* ================== TOOLBAR UNIFICADO (todo en una fila) ================== */
  .unified-toolbar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    flex-wrap:wrap;
    gap:12px;
    margin-bottom:20px;
    padding:16px;
    background:#ffffff;
    border-radius:12px;
    box-shadow:0 2px 8px rgba(15,23,42,.06);
  }
  .unified-toolbar__left{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
  }
  .unified-toolbar__right{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
  }

  /* ============ BOTONES ESTANDARIZADOS REDONDEADOS (filtros y exportación) ============ */
  .toolbar-btn{
    border-radius:24px !important;
    padding:9px 20px !important;
    font-size:.88rem !important;
    font-weight:500 !important;
    border:1px solid #cbd5e1 !important;
    background:#ffffff !important;
    color:#475569 !important;
    transition:all .25s ease !important;
    display:inline-flex !important;
    align-items:center !important;
    gap:7px !important;
    box-shadow:0 1px 3px rgba(0,0,0,.08) !important;
    cursor:pointer;
    white-space:nowrap;
  }
  .toolbar-btn:hover{
    background:#f8fafc !important;
    border-color:#94a3b8 !important;
    transform:translateY(-2px);
    box-shadow:0 4px 8px rgba(0,0,0,.12) !important;
  }
  .toolbar-btn.active{
    background:var(--neko-primary) !important;
    color:#ffffff !important;
    border-color:var(--neko-primary) !important;
    box-shadow:0 2px 6px rgba(21,101,192,.3) !important;
  }

  /* Botones de exportación DataTables - MISMO ESTILO */
  .dt-buttons{
    display:flex !important;
    gap:8px !important;
  }
  .dt-buttons button{
    border-radius:24px !important;
    padding:9px 20px !important;
    font-size:.88rem !important;
    font-weight:500 !important;
    border:1px solid #cbd5e1 !important;
    background:#ffffff !important;
    color:#475569 !important;
    transition:all .25s ease !important;
    display:inline-flex !important;
    align-items:center !important;
    gap:7px !important;
    box-shadow:0 1px 3px rgba(0,0,0,.08) !important;
    white-space:nowrap;
  }
  .dt-buttons button:hover{
    background:#f8fafc !important;
    border-color:#94a3b8 !important;
    transform:translateY(-2px);
    box-shadow:0 4px 8px rgba(0,0,0,.12) !important;
  }

  /* Selector de categoría */
  .category-select{
    border-radius:24px;
    border:1px solid #cbd5e1;
    padding:9px 18px;
    font-size:.88rem;
    min-width:200px;
    background:#ffffff;
    font-weight:500;
    box-shadow:0 1px 3px rgba(0,0,0,.08);
  }

  /* Campo de búsqueda */
  .search-input{
    border-radius:24px;
    border:1px solid #cbd5e1;
    padding:9px 18px;
    font-size:.88rem;
    min-width:220px;
    font-weight:500;
    box-shadow:0 1px 3px rgba(0,0,0,.08);
  }
  .search-input::placeholder{
    color:#94a3b8;
  }

  /* ================== TABLA PREMIUM CON TEMA AZUL Y TEXTO MÁS GRANDE ================== */
  #tbllistado{
    border-collapse: separate;
    border-spacing: 0;
    font-size:1rem;
  }
  #tbllistado thead th{
    background: linear-gradient(135deg, #1565c0 0%, #0d47a1 100%);
    color:#ffffff;
    font-weight:600;
    text-transform:uppercase;
    font-size:.88rem;
    letter-spacing:.08em;
    padding:18px 16px;
    border:none;
    white-space:nowrap;
  }
  #tbllistado thead th:first-child{
    border-top-left-radius:12px;
  }
  #tbllistado thead th:last-child{
    border-top-right-radius:12px;
  }
  #tbllistado tbody tr{
    transition:all .2s ease;
    border-bottom:1px solid #e2e8f0;
  }
  #tbllistado tbody tr:hover{
    background:#f8fafc;
    transform:scale(1.003);
    box-shadow:0 4px 12px rgba(21,101,192,.08);
  }
  #tbllistado tbody td{
    padding:16px;
    border-bottom:1px solid #f1f5f9;
    vertical-align:middle;
    font-size:1rem;
    color:#1e293b;
    font-weight:500;
  }
  #tbllistado tbody td:nth-child(8) img{
    width:56px !important;
    height:56px !important;
    object-fit:cover;
    border-radius:12px;
    border:2px solid #e2e8f0;
    box-shadow:0 4px 10px rgba(0,0,0,.12);
  }
  #tbllistado tfoot th{
    background:#f8fafc;
    color:#64748b;
    font-weight:600;
    padding:16px;
    border-top:2px solid #cbd5e1;
    font-size:.92rem;
  }

  /* ========== BOTONES DE ACCIÓN MÁS GRANDES Y COLORIDOS ========== */
  .btn-action{
    width:40px;
    height:40px;
    border-radius:10px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border:none;
    font-size:16px;
    transition:all .3s ease;
    margin-right:6px;
    cursor:pointer;
  }
  .btn-action:hover{
    transform:translateY(-3px);
    box-shadow:0 6px 16px rgba(0,0,0,.2);
  }
  .btn-action:active{
    transform:translateY(-1px);
  }
  
  /* Botón Editar - Naranja/Ámbar (contrasta con azul) */
  .btn-action.btn-edit{
    background:linear-gradient(135deg, #fb923c 0%, #f97316 100%);
    color:#ffffff;
  }
  .btn-action.btn-edit:hover{
    background:linear-gradient(135deg, #f97316 0%, #ea580c 100%);
    box-shadow:0 6px 16px rgba(249,115,22,.4);
  }
  
  /* Botón Desactivar - Rojo */
  .btn-action.btn-off{
    background:linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color:#ffffff;
  }
  .btn-action.btn-off:hover{
    background:linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    box-shadow:0 6px 16px rgba(220,38,38,.4);
  }
  
  /* Botón Activar - Azul */
  .btn-action.btn-on{
    background:linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    color:#ffffff;
  }
  .btn-action.btn-on:hover{
    background:linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    box-shadow:0 6px 16px rgba(37,99,235,.4);
  }

  /* Estados con estilo badge moderno */
  .label-status{
    padding:8px 18px;
    border-radius:24px;
    font-size:.85rem;
    font-weight:600;
    text-transform:uppercase;
    letter-spacing:.06em;
    display:inline-block;
  }
  .label-status.bg-green{
    background:#dcfce7;
    color:#16a34a;
  }
  .label-status.bg-red{
    background:#fee2e2;
    color:#dc2626;
  }

  /* Ocultar elementos innecesarios de DataTables */
  #tbllistado_wrapper .dataTables_length,
  #tbllistado_wrapper .dataTables_filter{
    display:none !important;
  }
  #tbllistado_wrapper .dataTables_info{
    color:#64748b;
    font-size:.9rem;
    font-weight:500;
  }
  #tbllistado_wrapper .dataTables_paginate{
    margin-top:18px;
  }
  .pagination>.active>a{
    background:var(--neko-primary) !important;
    border-color:var(--neko-primary) !important;
  }
</style>

<div class="content-wrapper">
  <section class="content">
    <div class="row">
      <div class="col-md-12">

        <div class="neko-card">
          <!-- Topbar -->
          <div class="neko-card__header">
            <h1 class="neko-card__title"><i class="fa fa-cubes"></i> Artículos</h1>
            <div class="neko-actions">
              <a href="../reportes/rptarticulos.php" target="_blank" class="btn btn-light" style="background:#e3f2fd;border:0;color:#0d47a1;">
                <i class="fa fa-clipboard"></i> Reporte
              </a>
              <button class="btn btn-success" id="btnagregar" onclick="mostrarform(true)">
                <i class="fa fa-plus-circle"></i> Agregar
              </button>
            </div>
          </div>

          <!-- LISTADO -->
          <div class="neko-card__body panel-body table-responsive" id="listadoregistros">

            <!-- ============ KPIs EN UNA SOLA FILA (6 TARJETAS) ============ -->
            <div class="row metric-row">

              <div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">
                <div class="metric-card metric-card--blue">
                  <div class="metric-card__icon"><i class="fa fa-box"></i></div>
                  <div class="metric-card__body">
                    <p class="metric-card__title">Artículos activos</p>
                    <p class="metric-card__value"><?php echo $kpiArticulosActivos; ?></p>
                  </div>
                </div>
              </div>

              <div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">
                <div class="metric-card metric-card--purple">
                  <div class="metric-card__icon"><i class="fa fa-archive"></i></div>
                  <div class="metric-card__body">
                    <p class="metric-card__title">Artículos inactivos</p>
                    <p class="metric-card__value"><?php echo $kpiArticulosInactivos; ?></p>
                  </div>
                </div>
              </div>

              <div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">
                <div class="metric-card metric-card--green">
                  <div class="metric-card__icon"><i class="fa fa-check-circle"></i></div>
                  <div class="metric-card__body">
                    <p class="metric-card__title">Con stock</p>
                    <p class="metric-card__value"><?php echo $kpiConStock; ?></p>
                  </div>
                </div>
              </div>

              <div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">
                <div class="metric-card metric-card--red">
                  <div class="metric-card__icon"><i class="fa fa-times-circle"></i></div>
                  <div class="metric-card__body">
                    <p class="metric-card__title">Sin stock</p>
                    <p class="metric-card__value"><?php echo $kpiSinStock; ?></p>
                  </div>
                </div>
              </div>

              <div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">
                <div class="metric-card metric-card--amber">
                  <div class="metric-card__icon"><i class="fa fa-exclamation-triangle"></i></div>
                  <div class="metric-card__body">
                    <p class="metric-card__title">Stock bajo (&lt; 5)</p>
                    <p class="metric-card__value"><?php echo $kpiStockBajo; ?></p>
                  </div>
                </div>
              </div>

              <div class="col-lg-2 col-md-4 col-sm-6 col-xs-12">
                <div class="metric-card metric-card--teal">
                  <div class="metric-card__icon"><i class="fa fa-layer-group"></i></div>
                  <div class="metric-card__body">
                    <p class="metric-card__title">Stock total</p>
                    <p class="metric-card__value"><?php echo $kpiStockTotal; ?></p>
                  </div>
                </div>
              </div>

            </div>
            <!-- ================================================ -->

            <!-- ============ TOOLBAR UNIFICADO (TODO EN UNA FILA) ============ -->
            <div class="unified-toolbar">
              <div class="unified-toolbar__left">
                <!-- Filtros de estado CON ESTILO ESTANDARIZADO REDONDEADO -->
                <button type="button" class="toolbar-btn active" id="filter-todos">
                  <i class="fa fa-list"></i> Todos
                </button>
                <button type="button" class="toolbar-btn" id="filter-activos">
                  <i class="fa fa-check-circle"></i> Solo activos
                </button>
                <button type="button" class="toolbar-btn" id="filter-desactivos">
                  <i class="fa fa-ban"></i> Solo desactivados
                </button>

                <!-- Botones de exportación (DataTables los renderizará aquí) -->
                <div id="export-buttons-container"></div>
              </div>

              <div class="unified-toolbar__right">
                <!-- Filtro por categoría -->
                <select id="filter-categoria" class="category-select">
                  <option value="">Todas las categorías</option>
                  <?php
                    $cat = ejecutarConsulta("SELECT idcategoria, nombre FROM categoria WHERE condicion=1");
                    while ($c = $cat->fetch_object()) {
                      echo "<option value='{$c->nombre}'>{$c->nombre}</option>";
                    }
                  ?>
                </select>

                <!-- Búsqueda -->
                <input type="text" id="search-input" class="search-input" placeholder="Buscar por nombre...">
              </div>
            </div>
            <!-- ========================================================= -->

            <table id="tbllistado" class="table table-striped table-bordered table-condensed table-hover" style="width:100%">
              <thead>
                <th>Opciones</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Código</th>
                <th>Stock</th>
                <th>Precio Compra</th>
                <th>Precio Venta</th>
                <th>Imagen</th>
                <th>Estado</th>
              </thead>
              <tbody></tbody>
              <tfoot>
                <th>Opciones</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Código</th>
                <th>Stock</th>
                <th>Precio Compra</th>
                <th>Precio Venta</th>
                <th>Imagen</th>
                <th>Estado</th>
              </tfoot>
            </table>
          </div>

          <!-- FORMULARIO ORDENADO -->
          <div class="neko-card__body panel-body" id="formularioregistros" style="display:none;">
            <form name="formulario" id="formulario" method="POST" enctype="multipart/form-data" autocomplete="off">
              <input type="hidden" name="idarticulo" id="idarticulo">

              <h4 class="section-title"><span class="dot"></span> Datos del artículo</h4>

              <!-- Fila 1: Nombre + Categoría -->
              <div class="row">
                <div class="form-group col-lg-8 col-md-8">
                  <label>Nombre(*):</label>
                  <input type="text" class="form-control" name="nombre" id="nombre"
                         maxlength="100" placeholder="Nombre del artículo" required
                         title="Solo letras y espacios (3 a 50 caracteres)">
                  <div class="help-hint">Usa un nombre claro y único.</div>
                </div>

                <div class="form-group col-lg-4 col-md-4">
                  <label>Categoría(*):</label>
                  <select id="idcategoria" name="idcategoria" class="form-control selectpicker"
                          data-live-search="true" required></select>
                </div>
              </div>

              <!-- Fila 2: Stock + Precio compra + Precio venta -->
              <div class="row">
                <div class="form-group col-lg-3 col-md-3 col-sm-6">
                  <label>Stock(*):</label>
                  <input type="number" class="form-control" name="stock" id="stock"
                         min="0" step="1" required>
                </div>

                <div class="form-group col-lg-3 col-md-3 col-sm-6">
                  <label>Precio compra(*):</label>
                  <input type="text" class="form-control" name="precio_compra" id="precio_compra"
                         placeholder="0.00" inputmode="decimal"
                         pattern="^\d{1,7}(\.\d{1,2})?$"
                         title="Solo números (máx. 2 decimales)" required>
                  <div class="help-hint">Base para sugerir el precio de venta.</div>
                </div>

                <div class="form-group col-lg-3 col-md-3 col-sm-6">
                  <label>Precio venta(*):</label>
                  <input type="text" class="form-control" name="precio_venta" id="precio_venta"
                         placeholder="0.00" inputmode="decimal"
                         pattern="^\d{1,7}(\.\d{1,2})?$"
                         title="Solo números (máx. 2 decimales)" required>
                  <div class="help-hint" id="pv_sugerido_hint">Sugerido: —</div>
                </div>
              </div>

              <!-- Fila 3: Descripción -->
              <div class="row">
                <div class="form-group col-lg-12">
                  <label>Descripción:</label>
                  <input type="text" class="form-control" name="descripcion" id="descripcion"
                         maxlength="256" placeholder="Detalle o especificación">
                </div>
              </div>

              <!-- Fila 4: Imagen (izq) + Código (der) -->
              <div class="row">
                <div class="form-group col-lg-6">
                  <label>Imagen:</label>
                  <input type="file" class="form-control" name="imagen" id="imagen"
                         accept="image/x-png,image/gif,image/jpeg,image/jpg,image/png">
                  <input type="hidden" name="imagenactual" id="imagenactual">
                  <div class="help-hint">JPG/PNG. Recomendado 600×480.</div>
                  <img src="" id="imagenmuestra" style="width:150px;height:120px;object-fit:cover;border:1px solid #e5e7eb;border-radius:6px;margin-top:8px;display:none;">
                </div>

                <div class="form-group col-lg-6">
                  <label>Código de barras:</label>
                  <div class="input-group">
                    <input type="text" class="form-control" name="codigo" id="codigo"
                           placeholder="EAN/UPC (8 a 13 dígitos)"
                           inputmode="numeric" pattern="^\d{8,13}$"
                           title="Solo números (8 a 13 dígitos)">
                    <span class="input-group-btn">
                      <button class="btn btn-success" type="button" onclick="generarbarcode()">
                        <i class="fa fa-barcode"></i> Generar
                      </button>
                      <button class="btn btn-info" type="button" onclick="imprimir()">
                        <i class="fa fa-print"></i> Imprimir
                      </button>
                    </span>
                  </div>
                  <div id="print" style="margin-top:8px; display:none;">
                    <svg id="barcode"></svg>
                  </div>
                </div>
              </div>

              <!-- Botones -->
              <div class="row" style="margin-top:8px;">
                <div class="col-lg-12">
                  <button class="btn btn-primary" type="submit" id="btnGuardar">
                    <i class="fa fa-save"></i> Guardar
                  </button>
                  <button class="btn btn-danger" onclick="cancelarform()" type="button">
                    <i class="fa fa-arrow-circle-left"></i> Cancelar
                  </button>
                </div>
              </div>
            </form>
          </div>
          <!-- /FORMULARIO -->

        </div>
      </div>
    </div>
  </section>
</div>
<?php else: ?>
  <?php require 'noacceso.php'; ?>
<?php endif; ?>

<?php require 'footer.php'; ?>
<!-- Scripts -->
<script type="text/javascript" src="../public/js/JsBarcode.all.min.js"></script>
<script type="text/javascript" src="../public/js/jquery.PrintArea.js"></script>
<script type="text/javascript" src="scripts/articulo.js"></script>

<?php ob_end_flush(); ?>
