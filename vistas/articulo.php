<?php  
// vistas/articulo.php - Formulario reorganizado + KPIs optimizados
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/_requires_auth.php';
require 'header.php';

$canAlmacen = !empty($_SESSION['almacen']) && (int)$_SESSION['almacen'] === 1;
$nekoPrimary     = '#1565c0';
$nekoPrimaryDark = '#0d47a1';
?>
<?php if ($canAlmacen): ?>
<?php
  // ==================== KPIs OPTIMIZADOS ====================
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
    background:#fff;
    border:1px solid rgba(2,24,54,.06);
    border-radius:14px;
    box-shadow:0 8px 24px rgba(2,24,54,.06);
    overflow:hidden;
    margin-top:10px;
  }

  .neko-card__header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    background: linear-gradient(90deg, var(--neko-primary-dark), var(--neko-primary));
    color:#fff;
    padding:14px 18px;
  }

  .neko-card__title{
    font-size:1.1rem;
    font-weight:600;
    letter-spacing:.2px;
    margin:0;
    display:flex;
    gap:10px;
    align-items:center;
  }

  .neko-actions .btn{ border-radius:10px; }

  .neko-card__body{ padding:18px; }

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

  .form-group{ margin-bottom:16px; }

  /* ==================== KPI CARDS (5 tarjetas optimizadas) ==================== */
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
    display:flex; 
    align-items:center; 
    justify-content:space-between;
    margin-bottom:4px;
  }
  .kpi-card__icon{
    width:48px; 
    height:48px; 
    border-radius:12px;
    display:flex; 
    align-items:center; 
    justify-content:center;
    font-size:22px;
  }
  .kpi-card__title{
    font-size:0.75rem; 
    color:#64748b; 
    text-transform:uppercase; 
    font-weight:600; 
    letter-spacing:0.5px;
    margin:0 0 8px 0;
  }
  .kpi-card__value{
    font-size:2rem; 
    font-weight:700; 
    margin:0;
    line-height:1;
  }

  .kpi-card--primary .kpi-card__icon{ background:#dbeafe; color:#1e40af; }
  .kpi-card--primary .kpi-card__value{ color:#1e3a8a; }

  .kpi-card--success .kpi-card__icon{ background:#d1fae5; color:#059669; }
  .kpi-card--success .kpi-card__value{ color:#065f46; }

  .kpi-card--danger .kpi-card__icon{ background:#fee2e2; color:#dc2626; }
  .kpi-card--danger .kpi-card__value{ color:#991b1b; }

  .kpi-card--warning .kpi-card__icon{ background:#fef3c7; color:#d97706; }
  .kpi-card--warning .kpi-card__value{ color:#92400e; }

  .kpi-card--purple .kpi-card__icon{ background:#e9d5ff; color:#9333ea; }
  .kpi-card--purple .kpi-card__value{ color:#6b21a8; }

  .kpi-card__dual{
    display:flex; 
    gap:16px; 
    align-items:center;
  }
  .kpi-dual-item{ flex:1; }
  .kpi-dual-item__label{
    font-size:0.7rem; 
    color:#64748b; 
    margin-bottom:4px;
    text-transform:uppercase;
    font-weight:600;
  }
  .kpi-dual-item__value{
    font-size:1.5rem; 
    font-weight:700;
  }
  .kpi-dual-divider{
    width:1px; 
    height:40px; 
    background:#e2e8f0;
  }

  /* ==================== FILTROS + EXPORTS ==================== */
  .filter-bar{
    display:flex; 
    align-items:center; 
    gap:12px; 
    margin-bottom:20px;
    flex-wrap:wrap;
  }
  .filter-group{
    display:flex; 
    gap:8px;
    background:#f8fafc; 
    padding:6px; 
    border-radius:12px;
    border:1px solid #e2e8f0;
  }

  .filter-btn{
    padding:8px 18px; 
    border:none; 
    background:transparent;
    border-radius:8px; 
    font-size:0.85rem; 
    font-weight:600;
    cursor:pointer; 
    transition: all 0.2s ease;
    color:#64748b;
    display:flex;
    align-items:center;
    gap:6px;
  }
  .filter-btn i{ font-size:0.9rem; }
  .filter-btn:hover{
    background:#e2e8f0;
    color:#334155;
  }
  .filter-btn.active{
    background: linear-gradient(135deg, var(--neko-primary-dark), var(--neko-primary));
    color:#fff;
    box-shadow:0 2px 8px rgba(21,101,192,.25);
  }
  .filter-btn.active:hover{
    background: linear-gradient(135deg, var(--neko-primary), var(--neko-primary-dark));
  }

  .search-input-wrapper{
    position:relative;
    flex:1;
    max-width:350px;
  }
  .search-input-wrapper i{
    position:absolute;
    left:14px;
    top:50%;
    transform:translateY(-50%);
    color:#94a3b8;
    font-size:1rem;
  }
  .search-input{
    width:100%;
    padding:10px 16px 10px 42px;
    border:1px solid #e2e8f0;
    border-radius:10px;
    font-size:0.88rem;
    transition: all 0.2s ease;
  }
  .search-input:focus{
    outline:none;
    border-color:var(--neko-primary);
    box-shadow:0 0 0 3px rgba(21,101,192,.1);
  }

  .filter-bar .records-wrapper{
    display:flex;
    align-items:center;
    gap:8px;
    color:#64748b;
    font-size:0.875rem;
    font-weight:600;
  }
  .filter-bar .records-wrapper select{
    width:auto;
    display:inline-block;
    padding:8px 32px 8px 12px;
    border:1px solid #e2e8f0;
    border-radius:8px;
    font-size:0.875rem;
    font-weight:600;
    color:#475569;
    background:white;
    cursor:pointer;
  }

  .category-select{
    border-radius:10px;
    border:1px solid #e2e8f0;
    padding:9px 12px;
    font-size:0.88rem;
    min-width:200px;
    background:#ffffff;
    font-weight:500;
  }

  .export-group{
    display:flex; 
    gap:8px;
    margin-left:auto;
  }
  .export-btn{
    padding:8px 16px; 
    border:1px solid #e2e8f0; 
    background:#fff;
    border-radius:8px; 
    font-size:0.82rem; 
    font-weight:600;
    cursor:pointer; 
    transition: all 0.2s ease;
    color:#475569;
    display:flex;
    align-items:center;
    gap:6px;
  }
  .export-btn:hover{
    background:#f8fafc;
    border-color:#cbd5e1;
    transform:translateY(-1px);
  }
  .export-btn i{ font-size:1rem; }

  /* ==================== TABLA ==================== */
  #tbllistado thead th{ 
    background: linear-gradient(135deg, #1e293b, #334155);
    color:#fff;
    font-weight:600;
    text-transform:uppercase;
    font-size:0.75rem;
    letter-spacing:0.5px;
    padding:14px 12px;
  }
  #tbllistado tfoot th{ 
    background:#f8fafc; 
    font-weight:600;
  }
  #tbllistado tbody tr:hover{
    background:#f8fafc;
  }
  #tbllistado tbody td:nth-child(8) img{
    width:56px;
    height:56px;
    object-fit:cover;
    border-radius:10px;
    border:2px solid #e2e8f0;
  }

  .label{
    padding:6px 12px;
    border-radius:6px;
    font-weight:600;
    font-size:0.75rem;
    text-transform:uppercase;
    letter-spacing:0.3px;
  }
  .bg-green{
    background:#d1fae5 !important;
    color:#065f46 !important;
  }
  .bg-red{
    background:#fee2e2 !important;
    color:#991b1b !important;
  }

  .section-title{
    font-weight:600;
    color:#0b2752;
    margin:6px 0 16px;
    display:flex;
    align-items:center;
    gap:8px;
  }
  .section-title .dot{
    width:8px;
    height:8px;
    border-radius:999px;
    background:var(--neko-primary);
    display:inline-block;
  }
  .help-hint{
    color:#64748b;
    font-size:.85rem;
    margin-top:4px;
  }

  /* Ocultar controles nativos de DataTables */
  #tbllistado_wrapper .dataTables_filter,
  #tbllistado_wrapper .dataTables_length,
  #tbllistado_wrapper .dt-buttons{
    display:none !important;
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

            <!-- KPIs OPTIMIZADOS (5 tarjetas) -->
            <div class="kpi-container">
              <!-- 1. Total artículos -->
              <div class="kpi-card kpi-card--primary">
                <div class="kpi-card__title">Total artículos</div>
                <div class="kpi-card__header">
                  <div>
                    <h2 class="kpi-card__value" id="kpi-total-articulos">
                      <?php echo $kpiTotalArticulos; ?>
                    </h2>
                  </div>
                  <div class="kpi-card__icon">
                    <i class="fa fa-cubes"></i>
                  </div>
                </div>
              </div>

              <!-- 2. Activos / Inactivos -->
              <div class="kpi-card kpi-card--success">
                <div class="kpi-card__title">Estado de artículos</div>
                <div class="kpi-card__dual">
                  <div class="kpi-dual-item">
                    <div class="kpi-dual-item__label">Activos</div>
                    <div class="kpi-dual-item__value" id="kpi-articulos-activos" style="color:#059669;">
                      <?php echo $kpiArticulosActivos; ?>
                    </div>
                  </div>
                  <div class="kpi-dual-divider"></div>
                  <div class="kpi-dual-item">
                    <div class="kpi-dual-item__label">Inactivos</div>
                    <div class="kpi-dual-item__value" id="kpi-articulos-inactivos" style="color:#dc2626;">
                      <?php echo $kpiArticulosInactivos; ?>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 3. Con/Sin stock FUSIONADO con tooltip -->
              <div class="kpi-card kpi-card--warning">
                <div class="kpi-card__title">Gestión de stock</div>
                <div class="kpi-card__dual">
                  <div class="kpi-dual-item">
                    <div class="kpi-dual-item__label">Con stock</div>
                    <div class="kpi-dual-item__value" id="kpi-con-stock" style="color:#059669;">
                      <?php echo $kpiConStock; ?>
                    </div>
                  </div>
                  <div class="kpi-dual-divider"></div>
                  <div class="kpi-dual-item">
                    <div class="kpi-dual-item__label">Sin stock</div>
                    <div class="kpi-dual-item__value" id="kpi-sin-stock" style="color:#dc2626;">
                      <?php echo $kpiSinStock; ?>
                    </div>
                  </div>
                </div>
              </div>

              <!-- 4. Stock bajo con tooltip -->
              <div class="kpi-card kpi-card--danger">
                <div class="kpi-card__title">Stock bajo (&lt; 5)</div>
                <div class="kpi-card__header">
                  <div>
                    <h2 class="kpi-card__value" id="kpi-stock-bajo">
                      <?php echo $kpiStockBajo; ?>
                    </h2>
                  </div>
                  <div class="kpi-card__icon">
                    <i class="fa fa-exclamation-triangle"></i>
                  </div>
                </div>
              </div>

              <!-- 5. Stock total -->
              <div class="kpi-card kpi-card--purple">
                <div class="kpi-card__title">Stock total</div>
                <div class="kpi-card__header">
                  <div>
                    <h2 class="kpi-card__value" id="kpi-stock-total">
                      <?php echo $kpiStockTotal; ?>
                    </h2>
                  </div>
                  <div class="kpi-card__icon">
                    <i class="fa fa-layer-group"></i>
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
                <button type="button" class="filter-btn" id="filter-activos">
                  <i class="fa fa-circle"></i> Solo activos
                </button>
                <button type="button" class="filter-btn" id="filter-desactivos">
                  <i class="fa fa-circle-o"></i> Solo desactivados
                </button>
              </div>

              <div class="search-input-wrapper">
                <i class="fa fa-search"></i>
                <input type="text" id="search-input" class="search-input" placeholder="Buscar por nombre, código o categoría...">
              </div>

              <div class="filter-group" style="background: transparent; border: none; padding: 0;">
                <div class="records-wrapper">
                  <span>Mostrar:</span>
                  <select id="page-length-selector" class="form-control">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                  </select>
                  <span>registros</span>
                </div>
              </div>

              <select id="filter-categoria" class="category-select">
                <option value="">Todas las categorías</option>
                <?php
                  $cat = ejecutarConsulta("SELECT idcategoria, nombre FROM categoria WHERE condicion=1");
                  while ($c = $cat->fetch_object()) {
                    echo "<option value=\"{$c->nombre}\">{$c->nombre}</option>";
                  }
                ?>
              </select>

              <div class="export-group">
                <button type="button" class="export-btn" onclick="exportarTabla('copy')" title="Copiar tabla al portapapeles">
                  <i class="fa fa-copy"></i> Copiar
                </button>
                <button type="button" class="export-btn" onclick="exportarTabla('excel')" title="Exportar a Excel">
                  <i class="fa fa-file-excel-o"></i> Excel
                </button>
                <button type="button" class="export-btn" onclick="exportarTabla('csv')" title="Exportar a CSV">
                  <i class="fa fa-file-text-o"></i> CSV
                </button>
                <button type="button" class="export-btn" onclick="exportarTabla('pdf')" title="Exportar a PDF">
                  <i class="fa fa-file-pdf-o"></i> PDF
                </button>
              </div>
            </div>

            <div class="table-responsive">
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
          </div>

          <!-- FORMULARIO REORGANIZADO -->
          <div class="neko-card__body panel-body" id="formularioregistros" style="display:none;">
            <form name="formulario" id="formulario" method="POST" enctype="multipart/form-data" autocomplete="off">
              <input type="hidden" name="idarticulo" id="idarticulo">

              <h4 class="section-title"><span class="dot"></span> Datos del artículo</h4>

              <!-- Fila 1: Nombre + Categoría -->
              <div class="row">
                <div class="form-group col-lg-6">
                  <label>Nombre(*):</label>
                  <input type="text" class="form-control" name="nombre" id="nombre"
                         maxlength="100" placeholder="Nombre del artículo" required>
                  <div class="help-hint">Usa un nombre claro y único.</div>
                </div>

                <div class="form-group col-lg-6">
                  <label>Categoría(*):</label>
                  <select id="idcategoria" name="idcategoria" class="form-control selectpicker"
                          data-live-search="true" required></select>
                </div>
              </div>

              <!-- Fila 2: Stock + Precio compra + Precio venta -->
              <div class="row">
                
                <div class="form-group col-lg-4">
                  <label>Precio compra(*):</label>
                  <input type="text" class="form-control" name="precio_compra" id="precio_compra"
                         placeholder="0.00" inputmode="decimal" required>
                  <div class="help-hint">Base para calcular precio de venta.</div>
                </div>

                <div class="form-group col-lg-4">
                  <label>Precio venta(*):</label>
                  <input type="text" class="form-control" name="precio_venta" id="precio_venta"
                         placeholder="0.00" inputmode="decimal" required>
                  <div class="help-hint" id="pv_sugerido_hint">Sugerido: —</div>
                </div>
              </div>

              <!-- Fila 3: Descripción + Código de barras -->
              <div class="row">
                <div class="form-group col-lg-6">
                  <label>Descripción:</label>
                  <textarea class="form-control" name="descripcion" id="descripcion" rows="3"
                            maxlength="256" placeholder="Detalle o especificación del artículo"></textarea>
                </div>

                <div class="form-group col-lg-6">
                  <label>Código de barras:</label>
                  <div class="input-group">
                    <input type="text" class="form-control" name="codigo" id="codigo"
                           placeholder="EAN/UPC (8 a 13 dígitos)" inputmode="numeric">
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

              <!-- Fila 4: Imagen -->
              <div class="row">
                <div class="form-group col-lg-12">
                  <label>Imagen:</label>
                  <input type="file" class="form-control" name="imagen" id="imagen"
                         accept="image/x-png,image/gif,image/jpeg,image/jpg,image/png">
                  <input type="hidden" name="imagenactual" id="imagenactual">
                  <div class="help-hint">JPG/PNG. Recomendado 600×480 px.</div>
                  <img src="" id="imagenmuestra" style="width:150px;height:120px;object-fit:cover;border:1px solid #e5e7eb;border-radius:6px;margin-top:8px;display:none;">
                </div>
              </div>

              <!-- Botones -->
              <div class="row" style="margin-top:16px;">
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

        </div>
      </div>
    </div>
  </section>
</div>
<?php else: ?>
  <?php require 'noacceso.php'; ?>
<?php endif; ?>

<?php require 'footer.php'; ?>
<script type="text/javascript" src="../public/js/JsBarcode.all.min.js"></script>
<script type="text/javascript" src="../public/js/jquery.PrintArea.js"></script>
<script type="text/javascript" src="scripts/articulo.js"></script>

<?php ob_end_flush(); ?>