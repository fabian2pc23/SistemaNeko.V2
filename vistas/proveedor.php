<?php
// vistas/proveedor.php
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/_requires_auth.php';
require 'header.php';

$canCompras = !empty($_SESSION['compras']) && (int)$_SESSION['compras'] === 1;

if ($canCompras) {
    require_once "../config/Conexion.php";

    // ==================== KPIs ====================
    // 1. Total Proveedores
    $sqlTotal = "SELECT COUNT(*) as total FROM persona WHERE tipo_persona='Proveedor'";
    $rsTotal = ejecutarConsultaSimpleFila($sqlTotal);
    $kpiTotal = $rsTotal ? (int)$rsTotal['total'] : 0;

    // 2. Activos
    $sqlActivos = "SELECT COUNT(*) as total FROM persona WHERE tipo_persona='Proveedor' AND condicion=1";
    $rsActivos = ejecutarConsultaSimpleFila($sqlActivos);
    $kpiActivos = $rsActivos ? (int)$rsActivos['total'] : 0;

    // 3. Inactivos
    $sqlInactivos = "SELECT COUNT(*) as total FROM persona WHERE tipo_persona='Proveedor' AND condicion=0";
    $rsInactivos = ejecutarConsultaSimpleFila($sqlInactivos);
    $kpiInactivos = $rsInactivos ? (int)$rsInactivos['total'] : 0;

    // 4. Proveedor con más compras
    $sqlTopProveedor = "SELECT p.nombre, COUNT(i.idingreso) as total_compras 
                        FROM persona p 
                        LEFT JOIN ingreso i ON p.idpersona = i.idproveedor 
                        WHERE p.tipo_persona='Proveedor' AND i.estado='Aceptado'
                        GROUP BY p.idpersona 
                        ORDER BY total_compras DESC 
                        LIMIT 1";
    $rsTopProveedor = ejecutarConsultaSimpleFila($sqlTopProveedor);
    $kpiTopNombre = $rsTopProveedor ? $rsTopProveedor['nombre'] : 'N/A';
    $kpiTopCompras = $rsTopProveedor ? (int)$rsTopProveedor['total_compras'] : 0;

    $nekoPrimary = '#1565c0';
    $nekoPrimaryDark = '#0d47a1';
}
?>
<?php if ($canCompras): ?>
<!-- ====== Estilos específicos de la vista ====== -->
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
  .neko-card .neko-card__header{
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

  /* Cinta de secciones del formulario */
  .section-title{
    font-weight:600; color:#0b2752; margin:16px 0 10px; display:flex; align-items:center; gap:8px;
  }
  .section-title .dot{ width:8px; height:8px; border-radius:999px; background:var(--neko-primary); display:inline-block; }

  /* Inputs con icono */
  .input-group-addon{
    background:#eef3fb; border-color:#d8e2f1; color:#1b3350; font-weight:600;
  }

  /* Ayudas y chips de estado */
  .help-hint{ color:#64748b; font-size:.85rem; margin-top:4px; }
  .chip{
    display:inline-flex; align-items:center; gap:6px;
    padding:4px 8px; border-radius:999px; font-size:.78rem; font-weight:600;
  }
  .chip.info{ background:#e3f2fd; color:#0d47a1; }
  .chip.ok{ background:#e8f5e9; color:#1b5e20; }
  .chip.warn{ background:#fff3e0; color:#e65100; }
  .chip.err{ background:#ffebee; color:#b71c1c; }

  .readonly{ background:#f3f4f6 !important; color:#475569 !important; }
  
  /* Botones primarios con gradiente */
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

  /* Tabla con headers oscuros */
  #tbllistado thead th{ 
    background: linear-gradient(135deg, #1e293b, #334155);
    color:#fff; font-weight:600; text-transform:uppercase;
    font-size:0.75rem; letter-spacing:0.5px; padding:14px 12px;
  }
  #tbllistado tfoot th{ background:#f8fafc; font-weight:600; }
  #tbllistado tbody tr:hover{ background:#f8fafc; }

  /* Labels de estado */
  .label{ padding:6px 12px; border-radius:6px; font-weight:600; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.3px; }
  .bg-green{ background:#d1fae5 !important; color:#065f46 !important; }
  .bg-red{ background:#fee2e2 !important; color:#991b1b !important; }

  /* ==================== KPI CARDS ==================== */
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
    display:flex; align-items:center; justify-content:space-between; margin-bottom:4px;
  }
  .kpi-card__icon{
    width:48px; height:48px; border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    font-size:22px;
  }
  .kpi-card__title{
    font-size:0.75rem; color:#64748b; text-transform:uppercase; 
    font-weight:600; letter-spacing:0.5px; margin:0 0 8px 0;
  }
  .kpi-card__value{
    font-size:1.8rem; font-weight:700; margin:0; line-height:1;
  }
  .kpi-card__sub{
    font-size:0.85rem; color:#64748b; margin-top:4px; font-weight:500;
  }

  /* Colores KPI */
  .kpi-card--primary .kpi-card__icon{ background:#dbeafe; color:#1e40af; }
  .kpi-card--primary .kpi-card__value{ color:#1e3a8a; }

  .kpi-card--success .kpi-card__icon{ background:#d1fae5; color:#059669; }
  .kpi-card--success .kpi-card__value{ color:#065f46; }

  .kpi-card--info .kpi-card__icon{ background:#e0f2fe; color:#0284c7; }
  .kpi-card--info .kpi-card__value{ color:#0c4a6e; }

  .kpi-card--purple .kpi-card__icon{ background:#f3e8ff; color:#9333ea; }
  .kpi-card--purple .kpi-card__value{ color:#581c87; }

  /* Dual KPI */
  .kpi-card__dual{ display:flex; gap:16px; align-items:center; }
  .kpi-dual-item{ flex:1; }
  .kpi-dual-item__label{ font-size:0.7rem; color:#64748b; margin-bottom:4px; text-transform:uppercase; font-weight:600; }
  .kpi-dual-item__value{ font-size:1.5rem; font-weight:700; }
  .kpi-dual-divider{ width:1px; height:40px; background:#e2e8f0; }

  /* ==================== FILTROS MODERNOS (Referencia Imagen) ==================== */
  .filter-bar {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    background: #fff;
    padding: 10px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
  }

  /* Grupo de Estado (Segmented Control) */
  .status-group {
    display: flex;
    background: #f1f5f9;
    padding: 4px;
    border-radius: 8px;
    gap: 4px;
  }
  .status-btn {
    border: none;
    background: transparent;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.85rem;
    font-weight: 600;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .status-btn:hover { color: #334155; }
  .status-btn.active {
    background: #fff;
    color: var(--neko-primary);
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }
  .status-btn i { font-size: 0.8rem; }

  /* Buscador */
  .search-container {
    flex: 1;
    min-width: 250px;
    position: relative;
  }
  .search-container i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
  }
  .search-input {
    width: 100%;
    padding: 8px 12px 8px 36px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 0.9rem;
    outline: none;
    transition: border-color 0.2s;
  }
  .search-input:focus {
    border-color: var(--neko-primary);
  }

  /* Selects (Mostrar, Tipo) */
  .filter-select-group {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.85rem;
    color: #64748b;
  }
  .filter-select {
    padding: 6px 24px 6px 10px; /* Extra padding for arrow */
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    font-size: 0.85rem;
    color: #334155;
    outline: none;
    cursor: pointer;
    background-color: #fff;
    /* Custom arrow */
    appearance: none;
    background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2364748b%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    background-size: 8px auto;
  }
  .filter-select:focus { border-color: var(--neko-primary); }

  /* Export Buttons */
  .export-actions {
    display: flex;
    gap: 6px;
  }
  .btn-export {
    padding: 6px 12px;
    border: 1px solid #e2e8f0;
    background: #fff;
    border-radius: 6px;
    color: #64748b;
    font-size: 0.85rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
  }
  .btn-export:hover {
    background: #f8fafc;
    color: #334155;
    border-color: #cbd5e1;
  }
  .btn-export i { font-size: 0.9rem; }

  /* Ocultar controles nativos de DataTables */
  #tbllistado_wrapper .dataTables_filter,
  #tbllistado_wrapper .dataTables_length,
  #tbllistado_wrapper .dt-buttons { display: none !important; }

  /* ==================== RESPONSIVE ==================== */
  @media (max-width: 992px) {
    .filter-bar { flex-direction: column; align-items: stretch; gap: 12px; }
    .status-group { justify-content: center; width: 100%; }
    .status-btn { flex: 1; justify-content: center; }
    .search-container { width: 100%; }
    .filter-select-group { justify-content: space-between; }
    .export-actions { justify-content: center; width: 100%; }
    .btn-export { flex: 1; justify-content: center; }
    
    .kpi-container { grid-template-columns: 1fr; }
    .dataTables_wrapper { overflow-x: auto; }
  }
</style>

<!-- ====== Contenido ====== -->
<div class="content-wrapper">
  <section class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="neko-card">

          <div class="neko-card__header">
            <h1 class="neko-card__title">
              <i class="fa fa-truck"></i> Proveedores
            </h1>
            <div class="neko-actions">
              <a href="../reportes/rptproveedores.php" target="_blank" class="btn btn-light" style="background:#e3f2fd;border:0;color:#0d47a1;">
                <i class="fa fa-clipboard"></i> Reporte
              </a>
              <button class="btn btn-success" id="btnagregar" onclick="mostrarform(true)">
                <i class="fa fa-plus-circle"></i> Agregar
              </button>
            </div>
          </div>

          <!-- LISTADO -->
          <div class="neko-card__body panel-body table-responsive" id="listadoregistros">
            
            <!-- KPIs -->
            <div class="kpi-container">
              <!-- 1. Total Proveedores -->
              <div class="kpi-card kpi-card--primary">
                <div class="kpi-card__title">Total Proveedores</div>
                <div class="kpi-card__header">
                  <div>
                    <h2 class="kpi-card__value"><?= $kpiTotal ?></h2>
                    <div class="kpi-card__sub">Registrados</div>
                  </div>
                  <div class="kpi-card__icon"><i class="fa fa-users"></i></div>
                </div>
              </div>

              <!-- 2. Estado Activos/Inactivos -->
              <div class="kpi-card kpi-card--success">
                <div class="kpi-card__title">Estado Proveedores</div>
                <div class="kpi-card__dual">
                  <div class="kpi-dual-item">
                    <div class="kpi-dual-item__label">Activos</div>
                    <div class="kpi-dual-item__value" style="color:#059669;"><?= $kpiActivos ?></div>
                  </div>
                  <div class="kpi-dual-divider"></div>
                  <div class="kpi-dual-item">
                    <div class="kpi-dual-item__label">Inactivos</div>
                    <div class="kpi-dual-item__value" style="color:#dc2626;"><?= $kpiInactivos ?></div>
                  </div>
                </div>
              </div>

              <!-- 3. Proveedor Top -->
              <div class="kpi-card kpi-card--purple">
                <div class="kpi-card__title">Proveedor Destacado</div>
                <div class="kpi-card__header">
                  <div>
                    <h2 class="kpi-card__value"><?= $kpiTopCompras ?></h2>
                    <div class="kpi-card__sub"><?= strlen($kpiTopNombre) > 25 ? substr($kpiTopNombre, 0, 25) . '...' : $kpiTopNombre ?></div>
                  </div>
                  <div class="kpi-card__icon"><i class="fa fa-star"></i></div>
                </div>
              </div>
            </div>

            <!-- FILTROS NUEVOS -->
            <div class="filter-bar">
              <!-- 1. Estado (Pills) -->
              <div class="status-group">
                <button type="button" class="status-btn active" id="filter-todos" onclick="filtrarEstado('todos')">
                  <i class="fa fa-th-large"></i> Todos
                </button>
                <button type="button" class="status-btn" id="filter-activos" onclick="filtrarEstado('activos')">
                  <i class="fa fa-check-circle"></i> Solo activos
                </button>
                <button type="button" class="status-btn" id="filter-inactivos" onclick="filtrarEstado('inactivos')">
                  <i class="fa fa-times-circle"></i> Solo desactivados
                </button>
              </div>

              <!-- 2. Buscador -->
              <div class="search-container">
                <i class="fa fa-search"></i>
                <input type="text" id="search-input" class="search-input" placeholder="Buscar por nombre, documento o email...">
              </div>

              <!-- 3. Mostrar X registros -->
              <div class="filter-select-group">
                <span>Mostrar:</span>
                <select id="length-select" class="filter-select" onchange="cambiarLongitud(this.value)">
                  <option value="5">5</option>
                  <option value="10" selected>10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
                <span>registros</span>
              </div>

              <!-- 4. Exportar -->
              <div class="export-actions">
                <button type="button" class="btn-export" onclick="exportarTabla('copy')"><i class="fa fa-copy"></i> Copiar</button>
                <button type="button" class="btn-export" onclick="exportarTabla('excel')"><i class="fa fa-file-excel-o"></i> Excel</button>
                <button type="button" class="btn-export" onclick="exportarTabla('csv')"><i class="fa fa-file-text-o"></i> CSV</button>
                <button type="button" class="btn-export" onclick="exportarTabla('pdf')"><i class="fa fa-file-pdf-o"></i> PDF</button>
              </div>
            </div>

            <table id="tbllistado" class="table table-striped table-bordered table-condensed table-hover">
              <thead>
                <th>Opciones</th>
                <th>Nombre</th>
                <th>Documento</th>
                <th>Número</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Estado</th>
              </thead>
              <tbody></tbody>
              <tfoot>
                <th>Opciones</th>
                <th>Nombre</th>
                <th>Documento</th>
                <th>Número</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Estado</th>
              </tfoot>
            </table>
          </div>

          <!-- FORMULARIO -->
          <div class="neko-card__body panel-body" id="formularioregistros" style="display:none;">
            <form name="formulario" id="formulario" method="POST" autocomplete="off">
              <input type="hidden" name="idpersona" id="idpersona">
              <input type="hidden" name="tipo_persona" id="tipo_persona" value="Proveedor">

              <h4 class="section-title"><span class="dot"></span> Datos del documento</h4>

              <div class="row">
                <div class="form-group col-lg-3 col-md-4 col-sm-6 col-xs-12">
                  <label>Tipo Documento:</label>
                  <select class="form-control selectpicker" name="tipo_documento" id="tipo_documento" required>
                    <option value="RUC">RUC</option>
                    <!-- Si quisieras habilitar DNI más adelante:
                    <option value="DNI">DNI</option> -->
                  </select>
                </div>

                <div class="form-group col-lg-4 col-md-6 col-sm-6 col-xs-12">
                  <label>Número Documento:</label>
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-id-card-o"></i></span>
                    <input type="text" class="form-control" name="num_documento" id="num_documento"
                           placeholder="Documento" required>
                  </div>
                  <div class="help-hint" id="ayuda_doc">RUC: 11 dígitos</div>
                </div>

                <div class="form-group col-lg-5 col-md-12 col-sm-12 col-xs-12">
                  <label>Estado de verificación:</label><br>
                  <span id="docStatus" class="chip info"><i class="fa fa-info-circle"></i> Esperando número…</span>
                </div>
              </div>

              <h4 class="section-title"><span class="dot"></span> Datos básicos</h4>

              <div class="row">
                <div class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
                  <label>Nombre (autocompletado):</label>
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-building"></i></span>
                    <input type="text" class="form-control readonly" name="nombre" id="nombre"
                           placeholder="Nombre del proveedor" readonly required>
                  </div>
                </div>

                <div class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
                  <label>Dirección (autocompletada):</label>
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-map-marker"></i></span>
                    <input type="text" class="form-control readonly" name="direccion" id="direccion"
                           placeholder="Dirección" readonly>
                  </div>
                </div>
              </div>

              <h4 class="section-title"><span class="dot"></span> Contacto</h4>

              <div class="row">
                <div class="form-group col-lg-3 col-md-4 col-sm-6 col-xs-12">
                  <label>Teléfono:</label>
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                    <input type="text" class="form-control" name="telefono" id="telefono"
                           placeholder="Teléfono (9 dígitos)" maxlength="9" inputmode="numeric" pattern="\d{9}" required>
                  </div>
                  <div class="help-hint">Solo números, exactamente 9 dígitos.</div>
                </div>

                <div class="form-group col-lg-4 col-md-6 col-sm-6 col-xs-12">
                  <label>Email:</label>
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-envelope-o"></i></span>
                    <input type="email" class="form-control" name="email" id="email" maxlength="50" placeholder="Email">
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" style="margin-top:6px;">
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
<script type="text/javascript" src="scripts/proveedor.js"></script>
<?php ob_end_flush(); ?>
