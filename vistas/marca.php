<?php 
// vistas/marca.php — Estilo corporativo moderno con KPIs
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/_requires_auth.php';
require 'header.php';

// Permiso (módulo almacen/marcas)
$canAlmacen = !empty($_SESSION['almacen']) && (int)$_SESSION['almacen'] === 1;

// Paleta corporativa
$nekoPrimary = '#1565c0';
$nekoPrimaryDark = '#0d47a1';
?>
<?php if ($canAlmacen): ?>
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

  /* ==================== KPI CARDS ==================== */
  .kpi-container{
    display:grid; 
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
    margin-bottom:12px;
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
  
  /* KPI específicos por color */
  .kpi-card--primary .kpi-card__icon{ background:#dbeafe; color:#1e40af; }
  .kpi-card--primary .kpi-card__value{ color:#1e3a8a; }
  
  .kpi-card--success .kpi-card__icon{ background:#d1fae5; color:#059669; }
  .kpi-card--success .kpi-card__value{ color:#065f46; }
  
  .kpi-card--danger .kpi-card__icon{ background:#fee2e2; color:#dc2626; }
  .kpi-card--danger .kpi-card__value{ color:#991b1b; }
  
  .kpi-card--warning .kpi-card__icon{ background:#fef3c7; color:#d97706; }
  .kpi-card--warning .kpi-card__value{ color:#92400e; }
  
  .kpi-card--info .kpi-card__icon{ background:#dbeafe; color:#0284c7; }
  .kpi-card--info .kpi-card__value{ color:#075985; }
  
  .kpi-card--purple .kpi-card__icon{ background:#e9d5ff; color:#9333ea; }
  .kpi-card--purple .kpi-card__value{ color:#6b21a8; }

  /* Dual value para Activas/Inactivas */
  .kpi-card__dual{
    display:flex; 
    gap:16px; 
    align-items:center;
  }
  
  .kpi-dual-item{
    flex:1;
  }
  
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

  /* ==================== FILTROS MODERNOS ==================== */
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
  
  .filter-btn i{
    font-size:0.9rem;
  }
  
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

  /* Botones de exportación modernos */
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
  
  .export-btn i{
    font-size:1rem;
  }

  /* Buscador moderno */
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

  /* Tabla */
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
  
  /* Badges de estado modernos */
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
</style>

<div class="content-wrapper">
  <section class="content">
    <div class="row">
      <div class="col-md-12">

        <div class="neko-card">
          <!-- Topbar -->
          <div class="neko-card__header">
            <h1 class="neko-card__title"><i class="fa fa-industry"></i> Marcas</h1>
            <div class="neko-actions">
              <a href="../reportes/rptmarcas.php" target="_blank" class="btn btn-light" style="background:#e3f2fd;border:0;color:#0d47a1;">
                <i class="fa fa-clipboard"></i> Reporte
              </a>
              <button class="btn btn-success" id="btnagregar" onclick="mostrarform(true)">
                <i class="fa fa-plus-circle"></i> Agregar
              </button>
            </div>
          </div>

          <!-- LISTADO -->
          <div class="neko-card__body" id="listadoregistros">
            
            <!-- ==================== KPIs ==================== -->
            <div class="kpi-container">
              <!-- Total Marcas -->
              <div class="kpi-card kpi-card--primary" style="cursor: pointer;">
                <div class="kpi-card__title">Total Marcas</div>
                <div class="kpi-card__header">
                  <div>
                    <h2 class="kpi-card__value" id="kpi-total">0</h2>
                  </div>
                  <div class="kpi-card__icon">
                    <i class="fa fa-industry"></i>
                  </div>
                </div>
              </div>

              <!-- Activas / Inactivas -->
              <div class="kpi-card kpi-card--success" style="cursor: pointer;">
                <div class="kpi-card__title">Estado de Marcas</div>
                <div class="kpi-card__dual">
                  <div class="kpi-dual-item">
                    <div class="kpi-dual-item__label">Activas</div>
                    <div class="kpi-dual-item__value" id="kpi-activas" style="color:#059669;">0</div>
                  </div>
                  <div class="kpi-dual-divider"></div>
                  <div class="kpi-dual-item">
                    <div class="kpi-dual-item__label">Inactivas</div>
                    <div class="kpi-dual-item__value" id="kpi-inactivas" style="color:#dc2626;">0</div>
                  </div>
                </div>
              </div>

              <!-- Sin Artículos -->
              <div class="kpi-card kpi-card--warning" id="card-sin-articulos" style="cursor: pointer;">
                <div class="kpi-card__title">Sin Artículos</div>
                <div class="kpi-card__header">
                  <div>
                    <h2 class="kpi-card__value" id="kpi-sin-articulos">0</h2>
                  </div>
                  <div class="kpi-card__icon">
                    <i class="fa fa-inbox"></i>
                  </div>
                </div>
              </div>

              <!-- Stock Crítico -->
              <div class="kpi-card kpi-card--danger" id="card-stock-critico" style="cursor: pointer;">
                <div class="kpi-card__title">Stock Crítico</div>
                <div class="kpi-card__header">
                  <div>
                    <h2 class="kpi-card__value" id="kpi-stock-critico">0</h2>
                  </div>
                  <div class="kpi-card__icon">
                    <i class="fa fa-exclamation-triangle"></i>
                  </div>
                </div>
              </div>

              <!-- Nuevas (30 días) -->
              <div class="kpi-card kpi-card--purple" id="card-nuevas" style="cursor: pointer;">
                <div class="kpi-card__title">Nuevas (30 días)</div>
                <div class="kpi-card__header">
                  <div>
                    <h2 class="kpi-card__value" id="kpi-nuevas">0</h2>
                  </div>
                  <div class="kpi-card__icon">
                    <i class="fa fa-star"></i>
                  </div>
                </div>
              </div>
            </div>

            <!-- ==================== FILTROS MODERNOS ==================== -->
            <div class="filter-bar">
              <div class="filter-group">
                <button class="filter-btn active" data-filter="all" onclick="filtrarTabla('all')">
                  <i class="fa fa-th"></i> Todos
                </button>
                <button class="filter-btn" data-filter="activos" onclick="filtrarTabla('activos')">
                  <i class="fa fa-circle"></i> Solo activos
                </button>
                <button class="filter-btn" data-filter="inactivos" onclick="filtrarTabla('inactivos')">
                  <i class="fa fa-circle-o"></i> Solo desactivados
                </button>
              </div>

              <!-- Búsqueda -->
              <div class="search-input-wrapper">
                <i class="fa fa-search"></i>
                <input type="text" class="search-input" id="searchInput" placeholder="Buscar por nombre...">
              </div>

              <!-- Selector de registros por página -->
              <div class="filter-group" style="background: transparent; border: none; padding: 0;">
                <label style="display: flex; align-items: center; gap: 8px; color: #64748b; font-size: 0.875rem; font-weight: 600; margin: 0;">
                  <span>Mostrar:</span>
                  <select id="page-length-selector" class="form-control" style="
                    width: auto;
                    display: inline-block;
                    padding: 8px 32px 8px 12px;
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    font-size: 0.875rem;
                    font-weight: 600;
                    color: #475569;
                    background: white;
                    cursor: pointer;
                  ">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                  </select>
                  <span>registros</span>
                </label>
              </div>

              <!-- Botones de exportación -->
              <div class="export-group">
                <button class="export-btn" onclick="exportarTabla('copy')" title="Copiar tabla al portapapeles">
                  <i class="fa fa-copy"></i> Copiar
                </button>
                <button class="export-btn" onclick="exportarTabla('excel')" title="Exportar a Excel">
                  <i class="fa fa-file-excel-o"></i> Excel
                </button>
                <button class="export-btn" onclick="exportarTabla('csv')" title="Exportar a CSV">
                  <i class="fa fa-file-text-o"></i> CSV
                </button>
                <button class="export-btn" onclick="exportarTabla('pdf')" title="Exportar a PDF">
                  <i class="fa fa-file-pdf-o"></i> PDF
                </button>
              </div>
            </div>

            <!-- Tabla -->
            <div class="table-responsive">
              <table id="tbllistado" class="table table-striped table-bordered table-condensed table-hover" style="width:100%">
                <thead>
                  <th>Opciones</th>
                  <th>Nombre</th>
                  <th>Descripción</th>
                  <th>Estado</th>
                </thead>
                <tbody></tbody>
                <tfoot>
                  <th>Opciones</th>
                  <th>Nombre</th>
                  <th>Descripción</th>
                  <th>Estado</th>
                </tfoot>
              </table>
            </div>
          </div>

          <!-- FORMULARIO -->
          <div class="neko-card__body panel-body" id="formularioregistros" style="display:none;">
            <form name="formulario" id="formulario" method="POST" autocomplete="off">
              <input type="hidden" name="idmarca" id="idmarca">

              <div class="row">
                <div class="form-group col-lg-5 col-md-6">
                  <label>Nombre(*):</label>
                  <input type="text" class="form-control" name="nombre" id="nombre" maxlength="100" placeholder="Nombre de la marca" required>
                </div>

                <div class="form-group col-lg-7 col-md-6">
                  <label>Descripción:</label>
                  <input type="text" class="form-control" name="descripcion" id="descripcion" maxlength="256" placeholder="Descripción de la marca">
                </div>
              </div>

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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript" src="scripts/marca.js"></script>
<?php ob_end_flush(); ?>