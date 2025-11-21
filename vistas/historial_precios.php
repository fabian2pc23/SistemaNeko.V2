<?php
// vistas/historial_precios.php — Modernizado con Chart.js
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/_requires_auth.php';
require 'header.php';

$canVerHistorial = ( !empty($_SESSION['almacen']) && (int)$_SESSION['almacen']===1 )
                 || ( !empty($_SESSION['compras']) && (int)$_SESSION['compras']===1 );

$nekoPrimary     = '#1565c0';
$nekoPrimaryDark = '#0d47a1';
?>
<?php if ($canVerHistorial): ?>
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

  /* Filter Bar */
  .filter-bar {
    display: flex; align-items: center; gap: 12px; margin-bottom: 20px;
    flex-wrap: wrap; background: #fff; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0;
  }
  .filter-bar label { font-weight: 600; color: #334155; margin: 0; }

  /* Chart Container */
  #chart-container {
    background: #fff; border-radius: 12px; padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,.08); margin-bottom: 20px;
    border: 1px solid #e2e8f0; display: none;
  }
  #chart-container h4 {
    margin: 0 0 16px 0; color: #1e293b; font-weight: 700;
    display: flex; align-items: center; gap: 8px;
  }
  #priceChart { max-height: 350px; }

  /* Tabs */
  .nav-tabs {
    border-bottom: 2px solid #e2e8f0;
  }
  .nav-tabs > li > a {
    color: #64748b; font-weight: 600;
  }
  .nav-tabs > li.active > a {
    color: var(--neko-primary); border-bottom: 2px solid var(--neko-primary);
  }

  /* Tabla */
  #tbl_vigentes thead th,
  #tbl_mov thead th { 
    background: linear-gradient(135deg, #1e293b, #334155); 
    color:#fff; font-weight:600; text-transform:uppercase; 
    font-size:0.75rem; padding:12px;
  }
  #tbl_vigentes tbody tr:hover,
  #tbl_mov tbody tr:hover { background:#f8fafc; }
  
  /* Ocultar controles nativos DT */
  #tbl_vigentes_wrapper .dataTables_filter, 
  #tbl_vigentes_wrapper .dataTables_length, 
  #tbl_vigentes_wrapper .dt-buttons,
  #tbl_mov_wrapper .dataTables_filter, 
  #tbl_mov_wrapper .dataTables_length, 
  #tbl_mov_wrapper .dt-buttons { 
    display: none !important; 
  }

  /* Export Buttons */
  .export-actions { display: flex; gap: 6px; margin-bottom: 12px; }
  .btn-export {
    padding: 6px 12px; border: 1px solid #e2e8f0; background: #fff; border-radius: 6px;
    color: #64748b; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 6px;
  }
  .btn-export:hover { background: #f8fafc; color: #334155; border-color: #cbd5e1; }

  .text-muted-small{ color:#64748b; font-size:.85rem; margin-top: 4px; }

  @media (max-width: 992px) {
    .filter-bar { flex-direction: column; align-items: stretch; }
    #chart-container { padding: 12px; }
  }
</style>

<div class="content-wrapper">
  <section class="content">
    <div class="row">
      <div class="col-md-12">

        <div class="neko-card">
          <!-- Header -->
          <div class="neko-card__header">
            <h1 class="neko-card__title">
              <i class="fa fa-line-chart"></i> Historial de Precios
            </h1>
            <div class="neko-actions">
              <button class="btn btn-light" id="btnRecargar" style="background:#e3f2fd;border:0;color:#0d47a1;">
                <i class="fa fa-refresh"></i> Recargar
              </button>
              <button class="btn btn-success" id="btnAbrirModal">
                <i class="fa fa-money"></i> Actualizar precio
              </button>
            </div>
          </div>

          <!-- Filtros -->
          <div class="neko-card__body" style="padding-bottom:8px;">
            <div class="filter-bar">
              <label>Filtrar por artículo:</label>
              <select id="filtro_articulo" class="form-control selectpicker" data-live-search="true" title="Seleccione artículo" data-size="8" style="flex: 1; min-width: 250px;"></select>
              <div class="text-muted-small" style="width: 100%;">Selecciona un artículo para ver su historial de precios y gráfico de tendencia.</div>
            </div>
          </div>

          <!-- Chart Container -->
          <div class="neko-card__body" style="padding-top:0;">
            <div id="chart-container">
              <h4><i class="fa fa-area-chart"></i> Tendencia de Precios</h4>
              <canvas id="priceChart"></canvas>
            </div>
          </div>

          <!-- Pestañas -->
          <ul class="nav nav-tabs" role="tablist" style="margin: 0 18px;">
            <li role="presentation" class="active">
              <a href="#vigentes" aria-controls="vigentes" role="tab" data-toggle="tab">
                <i class="fa fa-check-circle"></i> Precios vigentes
              </a>
            </li>
            <li role="presentation">
              <a href="#movimientos" aria-controls="movimientos" role="tab" data-toggle="tab">
                <i class="fa fa-history"></i> Movimientos
              </a>
            </li>
          </ul>

          <div class="tab-content neko-card__body">
            <!-- Vigentes -->
            <div role="tabpanel" class="tab-pane active" id="vigentes">
              <!-- Controles de búsqueda y paginación -->
              <div class="filter-bar" style="margin-bottom: 16px;">
                <div class="search-container" style="flex: 1; min-width: 200px; position: relative;">
                  <i class="fa fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                  <input type="text" id="search-vigentes" class="search-input" placeholder="Buscar en precios vigentes..." style="width: 100%; padding: 8px 12px 8px 36px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; outline: none;">
                </div>
                
                <div style="display:flex; align-items:center; gap:8px;">
                  <span style="font-size:0.85rem; font-weight:600; color:#64748b;">Mostrar:</span>
                  <select id="entries-vigentes" class="filter-select" style="padding: 6px 24px 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.85rem; color: #334155;">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                  </select>
                </div>

                <div class="export-actions">
                  <button class="btn-export" onclick="exportarTabla('copy', 'vigentes')" title="Copiar"><i class="fa fa-copy"></i> Copiar</button>
                  <button class="btn-export" onclick="exportarTabla('excel', 'vigentes')" title="Excel"><i class="fa fa-file-excel-o"></i> Excel</button>
                  <button class="btn-export" onclick="exportarTabla('csv', 'vigentes')" title="CSV"><i class="fa fa-file-text-o"></i> CSV</button>
                  <button class="btn-export" onclick="exportarTabla('pdf', 'vigentes')" title="PDF"><i class="fa fa-file-pdf-o"></i> PDF</button>
                </div>
              </div>

              <div class="table-responsive">
                <table id="tbl_vigentes" class="table table-striped table-bordered table-condensed table-hover" style="width:100%">
                  <thead>
                    <th>ID</th>
                    <th>Artículo</th>
                    <th>Precio venta</th>
                    <th>Precio compra</th>
                    <th>Stock</th>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>

            <!-- Movimientos -->
            <div role="tabpanel" class="tab-pane" id="movimientos">
              <!-- Controles de búsqueda y paginación -->
              <div class="filter-bar" style="margin-bottom: 16px;">
                <div class="search-container" style="flex: 1; min-width: 200px; position: relative;">
                  <i class="fa fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                  <input type="text" id="search-movimientos" class="search-input" placeholder="Buscar en movimientos..." style="width: 100%; padding: 8px 12px 8px 36px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 0.9rem; outline: none;">
                </div>
                
                <div style="display:flex; align-items:center; gap:8px;">
                  <span style="font-size:0.85rem; font-weight:600; color:#64748b;">Mostrar:</span>
                  <select id="entries-movimientos" class="filter-select" style="padding: 6px 24px 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 0.85rem; color: #334155;">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                  </select>
                </div>

                <div class="export-actions">
                  <button class="btn-export" onclick="exportarTabla('copy', 'movimientos')" title="Copiar"><i class="fa fa-copy"></i> Copiar</button>
                  <button class="btn-export" onclick="exportarTabla('excel', 'movimientos')" title="Excel"><i class="fa fa-file-excel-o"></i> Excel</button>
                  <button class="btn-export" onclick="exportarTabla('csv', 'movimientos')" title="CSV"><i class="fa fa-file-text-o"></i> CSV</button>
                  <button class="btn-export" onclick="exportarTabla('pdf', 'movimientos')" title="PDF"><i class="fa fa-file-pdf-o"></i> PDF</button>
                </div>
              </div>

              <div class="table-responsive">
                <table id="tbl_mov" class="table table-striped table-bordered table-condensed table-hover" style="width:100%">
                  <thead>
                    <th>#</th>
                    <th>Artículo</th>
                    <th>Código</th>
                    <th>Precio anterior</th>
                    <th>Precio nuevo</th>
                    <th>Motivo</th>
                    <th>Fuente</th>
                    <th>Usuario</th>
                    <th>Fecha</th>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>
</div>

<!-- Modal: Actualizar Precio -->
<div class="modal fade" id="mdlPrecio" tabindex="-1" role="dialog" aria-labelledby="lblMdlPrecio">
  <div class="modal-dialog" role="document">
    <form id="frmPrecio" method="post" autocomplete="off">
      <div class="modal-content">
        <div class="modal-header" style="background:var(--neko-primary);color:#fff;">
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
          <h4 class="modal-title" id="lblMdlPrecio">
            <i class="fa fa-money"></i> Actualizar precio de artículo
          </h4>
        </div>

        <div class="modal-body">
          <input type="hidden" id="idarticulo_mdl" name="idarticulo">

          <div class="form-group">
            <label>Artículo:</label>
            <select id="sel_articulo_mdl" class="form-control selectpicker" data-live-search="true" title="Seleccione artículo" data-size="8" required></select>
          </div>

          <div class="row">
            <div class="form-group col-sm-6">
              <label>Precio actual:</label>
              <input type="text" class="form-control" id="precio_actual" name="precio_actual" readonly>
            </div>
            <div class="form-group col-sm-6">
              <label>Precio nuevo (*):</label>
              <input type="number" class="form-control" step="0.01" min="0" id="precio_nuevo" name="precio_nuevo" required>
            </div>
          </div>

          <div class="form-group">
            <label>Motivo / comentario:</label>
            <input type="text" class="form-control" id="motivo" name="motivo" maxlength="120" placeholder="Ej. Ajuste por proveedor, corrección, etc.">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">
            <i class="fa fa-times"></i> Cancelar
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-save"></i> Actualizar precio
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php else: ?>
  <?php require 'noacceso.php'; ?>
<?php endif; ?>

<?php require 'footer.php'; ?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<!-- Script dedicado -->
<script type="text/javascript" src="scripts/historial_precios.js"></script>

<?php ob_end_flush(); ?>
