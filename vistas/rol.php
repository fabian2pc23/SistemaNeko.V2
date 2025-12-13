<?php
// vistas/rol.php - VERSIÓN MODERNIZADA (Estilo Venta)
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

require 'header.php';

// === Permiso del módulo (ACCESO/ROLES) ===
$canAcceso = !empty($_SESSION['acceso']) && (int)$_SESSION['acceso'] === 1;

if ($canAcceso) {
  // ==================== KPIs OPTIMIZADOS ====================
  require_once "../config/Conexion.php";

  $sqlKpi = "
    SELECT
      COUNT(*)                                     AS total_roles,
      SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END) AS roles_activos,
      SUM(CASE WHEN estado = 0 THEN 1 ELSE 0 END) AS roles_inactivos
    FROM rol_usuarios
  ";
  $rsKpi  = ejecutarConsulta($sqlKpi);
  $rowKpi = $rsKpi ? $rsKpi->fetch_object() : null;

  $kpiTotalRoles     = $rowKpi ? (int)$rowKpi->total_roles     : 0;
  $kpiRolesActivos   = $rowKpi ? (int)$rowKpi->roles_activos   : 0;
  $kpiRolesInactivos = $rowKpi ? (int)$rowKpi->roles_inactivos : 0;
?>
  <!-- ====== Estilos Modernos (Igual a Venta.php) ====== -->
  <style>
    :root {
      --neko-primary: #1565c0;
      --neko-primary-dark: #0d47a1;
      --neko-bg: #f5f7fb;
      --neko-success: #059669;
      --neko-warning: #d97706;
      --neko-danger: #dc2626;
    }

    .content-wrapper {
      background: var(--neko-bg);
    }

    /* Cards */
    .neko-card {
      background: #fff;
      border: 1px solid rgba(2, 24, 54, .06);
      border-radius: 14px;
      box-shadow: 0 8px 24px rgba(2, 24, 54, .06);
      overflow: hidden;
      margin-top: 10px;
    }

    .neko-card .neko-card__header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: linear-gradient(90deg, var(--neko-primary-dark), var(--neko-primary));
      color: #fff;
      padding: 14px 18px;
    }

    .neko-card__title {
      font-size: 1.1rem;
      font-weight: 600;
      letter-spacing: .2px;
      margin: 0;
      display: flex;
      gap: 10px;
      align-items: center;
    }

    .neko-card__body {
      padding: 18px;
    }

    /* Botones */
    .neko-actions .btn {
      border-radius: 10px;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--neko-primary-dark), var(--neko-primary));
      border: none;
      box-shadow: 0 2px 8px rgba(21, 101, 192, .25);
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, var(--neko-primary), var(--neko-primary-dark));
      transform: translateY(-1px);
    }

    /* ==================== KPI CARDS ==================== */
    .kpi-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      margin-bottom: 20px;
    }

    .kpi-card {
      background: #fff;
      border-radius: 14px;
      padding: 18px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
      border: 1px solid rgba(0, 0, 0, .06);
      transition: transform 0.2s ease;
    }

    .kpi-card:hover {
      transform: translateY(-2px);
    }

    .kpi-card__header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 8px;
    }

    .kpi-card__title {
      font-size: 0.75rem;
      color: #64748b;
      text-transform: uppercase;
      font-weight: 700;
      letter-spacing: 0.5px;
    }

    .kpi-card__icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
    }

    .kpi-card__value {
      font-size: 1.6rem;
      font-weight: 800;
      color: #1e293b;
      line-height: 1.2;
    }

    .kpi-card__sub {
      font-size: 0.8rem;
      color: #64748b;
      margin-top: 4px;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    /* Variaciones de color KPI */
    .kpi-blue .kpi-card__icon {
      background: #eff6ff;
      color: #2563eb;
    }

    .kpi-green .kpi-card__icon {
      background: #ecfdf5;
      color: #059669;
    }

    .kpi-red .kpi-card__icon {
      background: #fef2f2;
      color: #dc2626;
    }

    /* ==================== FILTROS MODERNOS ==================== */
    .filter-bar {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 20px;
      flex-wrap: wrap;
      background: #fff;
      padding: 12px;
      border-radius: 12px;
      border: 1px solid #e2e8f0;
    }

    /* Status Pills */
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
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }

    .status-btn:hover {
      color: #334155;
    }

    .status-btn.active {
      background: #fff;
      color: var(--neko-primary);
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    /* Search */
    .search-container {
      flex: 1;
      min-width: 200px;
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

    /* Selects */
    .filter-select {
      padding: 6px 24px 6px 10px;
      border: 1px solid #e2e8f0;
      border-radius: 6px;
      font-size: 0.85rem;
      color: #334155;
      outline: none;
      cursor: pointer;
      background-color: #fff;
      appearance: none;
      background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2364748b%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
      background-repeat: no-repeat;
      background-position: right 8px center;
      background-size: 8px auto;
    }

    /* Export */
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
    }

    .btn-export:hover {
      background: #f8fafc;
      color: #334155;
      border-color: #cbd5e1;
    }

    /* Tabla */
    #tbllistado thead th {
      background: linear-gradient(135deg, #1e293b, #334155);
      color: #fff;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.75rem;
      padding: 12px;
    }

    #tbllistado tbody tr:hover {
      background: #f8fafc;
    }

    /* Ocultar controles nativos DT */
    #tbllistado_wrapper .dataTables_filter,
    #tbllistado_wrapper .dataTables_length,
    #tbllistado_wrapper .dt-buttons {
      display: none !important;
    }

    /* Labels */
    .label {
      padding: 6px 12px;
      border-radius: 6px;
      font-weight: 600;
      font-size: 0.75rem;
    }

    .bg-green {
      background: #d1fae5 !important;
      color: #065f46 !important;
    }

    .bg-red {
      background: #fee2e2 !important;
      color: #991b1b !important;
    }

    /* Formulario */
    .form-group {
      margin-bottom: 16px;
    }

    .section-title {
      font-weight: 600;
      color: #0b2752;
      margin: 6px 0 14px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .section-title .dot {
      width: 8px;
      height: 8px;
      border-radius: 999px;
      background: var(--neko-primary);
      display: inline-block;
    }

    .nk-permisos {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 16px;
      max-height: 400px;
      overflow-y: auto;
    }

    .nk-ul-permisos {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .nk-ul-permisos li {
      padding: 10px 12px;
      border-bottom: 1px solid #e2e8f0;
      transition: background 0.2s ease;
    }

    .nk-ul-permisos li:last-child {
      border-bottom: none;
    }

    .nk-ul-permisos li:hover {
      background: #e3f2fd;
    }

    .nk-ul-permisos label {
      display: flex;
      align-items: center;
      gap: 10px;
      cursor: pointer;
      margin: 0;
      font-weight: 500;
      color: #334155;
    }

    .nk-ul-permisos input[type="checkbox"] {
      width: 18px;
      height: 18px;
      cursor: pointer;
    }

    .help-hint {
      color: #64748b;
      font-size: .85rem;
      margin-top: 4px;
    }

    @media (max-width: 992px) {
      .filter-bar {
        flex-direction: column;
        align-items: stretch;
      }

      .status-group,
      .search-container,
      .export-actions {
        width: 100%;
      }

      .kpi-container {
        grid-template-columns: 1fr;
      }
    }
  </style>

  <!--Contenido-->
  <div class="content-wrapper">
    <section class="content">
      <div class="row">
        <div class="col-md-12">
          <div class="neko-card">

            <!-- Header visual (Igual a Venta) -->
            <div class="neko-card__header">
              <h1 class="neko-card__title"><i class="fa fa-shield"></i> Gestión de Roles</h1>
              <div class="neko-actions">
                <a href="../reportes/rptroles.php" target="_blank" class="btn btn-light" style="background:#e3f2fd;border:0;color:#0d47a1;margin-right:8px;">
                  <i class="fa fa-file-pdf-o"></i> Reporte
                </a>
                <button class="btn btn-success" id="btnagregar" onclick="mostrarform(true)">
                  <i class="fa fa-plus-circle"></i> Nuevo Rol
                </button>
              </div>
            </div>

            <!-- LISTADO -->
            <div class="neko-card__body" id="listadoregistros">

              <!-- KPIs OPTIMIZADOS (Estilo Venta) -->
              <div class="kpi-container">
                <!-- 1. Total roles -->
                <div class="kpi-card kpi-blue" onclick="mostrarDetalleKPI('total')" style="cursor:pointer;">
                  <div class="kpi-card__header">
                    <div class="kpi-card__title">Total Roles <i class="fa fa-info-circle" style="opacity:0.5;font-size:0.7rem;"></i></div>
                    <div class="kpi-card__icon"><i class="fa fa-users"></i></div>
                  </div>
                  <div class="kpi-card__value" id="kpi-total-roles"><?php echo $kpiTotalRoles; ?></div>
                  <div class="kpi-card__sub">Registrados en sistema</div>
                </div>

                <!-- 2. Activos -->
                <div class="kpi-card kpi-green" onclick="mostrarDetalleKPI('activos')" style="cursor:pointer;">
                  <div class="kpi-card__header">
                    <div class="kpi-card__title">Roles Activos <i class="fa fa-info-circle" style="opacity:0.5;font-size:0.7rem;"></i></div>
                    <div class="kpi-card__icon"><i class="fa fa-check-circle"></i></div>
                  </div>
                  <div class="kpi-card__value" id="kpi-roles-activos"><?php echo $kpiRolesActivos; ?></div>
                  <div class="kpi-card__sub">Habilitados</div>
                </div>

                <!-- 3. Inactivos -->
                <div class="kpi-card kpi-red" onclick="mostrarDetalleKPI('inactivos')" style="cursor:pointer;">
                  <div class="kpi-card__header">
                    <div class="kpi-card__title">Roles Inactivos <i class="fa fa-info-circle" style="opacity:0.5;font-size:0.7rem;"></i></div>
                    <div class="kpi-card__icon"><i class="fa fa-times-circle"></i></div>
                  </div>
                  <div class="kpi-card__value" id="kpi-roles-inactivos"><?php echo $kpiRolesInactivos; ?></div>
                  <div class="kpi-card__sub">Deshabilitados</div>
                </div>
              </div>

              <!-- BARRA DE FILTROS (Estilo Venta) -->
              <div class="filter-bar">
                <!-- Grupo 1: Estado -->
                <div class="status-group">
                  <button type="button" class="status-btn active" id="filter-todos" onclick="filtrarEstado('todos')">Todos</button>
                  <button type="button" class="status-btn" id="filter-activos" onclick="filtrarEstado('activos')"><i class="fa fa-check"></i> Activos</button>
                  <button type="button" class="status-btn" id="filter-bloqueados" onclick="filtrarEstado('bloqueados')"><i class="fa fa-ban"></i> Bloqueados</button>
                </div>

                <!-- Grupo 2: Búsqueda -->
                <div class="search-container">
                  <i class="fa fa-search"></i>
                  <input type="text" id="search-input" class="search-input" placeholder="Buscar rol...">
                </div>

                <!-- Grupo 3: Mostrar -->
                <div style="display:flex; align-items:center; gap:8px;">
                  <span style="font-size:0.85rem; font-weight:600; color:#64748b;">Mostrar:</span>
                  <select id="entries-select" class="filter-select" onchange="cambiarLongitud(this.value)">
                    <option value="5">5</option>
                    <option value="10" selected>10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                  </select>
                </div>

                <!-- Grupo 4: Exportar -->
                <div class="export-actions">
                  <button class="btn-export" onclick="exportarTabla('copy')" title="Copiar"><i class="fa fa-copy"></i> Copiar</button>
                  <button class="btn-export" onclick="exportarTabla('excel')" title="Excel"><i class="fa fa-file-excel-o"></i> Excel</button>
                  <button class="btn-export" onclick="exportarTabla('csv')" title="CSV"><i class="fa fa-file-text-o"></i> CSV</button>
                  <button class="btn-export" onclick="exportarTabla('pdf')" title="PDF"><i class="fa fa-file-pdf-o"></i> PDF</button>
                </div>
              </div>

              <table id="tbllistado" class="table table-hover">
                <thead>
                  <th>Opciones</th>
                  <th>Nombre</th>
                  <th>Estado</th>
                  <th>Creado</th>
                </thead>
                <tbody></tbody>
              </table>
            </div>

            <!-- FORMULARIO -->
            <div class="neko-card__body panel-body" id="formularioregistros" style="display:none;">
              <form name="formulario" id="formulario" method="POST" novalidate>
                <h4 class="section-title"><span class="dot"></span> Datos del rol</h4>

                <div class="row">
                  <div class="form-group col-lg-6 col-md-6 col-sm-8 col-xs-12">
                    <label>Nombre (*):</label>
                    <input type="hidden" name="idrol" id="idrol">
                    <input
                      type="text"
                      class="form-control"
                      name="nombre"
                      id="nombre"
                      maxlength="50"
                      autocomplete="off"
                      pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]{3,50}$"
                      title="Solo letras y espacios (3 a 50 caracteres)"
                      oninput="soloLetras(this)"
                      placeholder="Ej. Supervisor"
                      required>
                    <div class="help-hint">Usa un nombre claro y único para identificar el rol.</div>
                  </div>
                </div>

                <div class="row">
                  <div class="form-group col-lg-8 col-md-10 col-sm-12 col-xs-12">
                    <label>Permisos del rol (*):</label>
                    <div class="nk-permisos">
                      <ul id="permisos_rol" class="nk-ul-permisos">
                        <!-- Aquí se cargarán los checkboxes dinámicamente -->
                      </ul>
                    </div>
                    <div class="help-hint">
                      <i class="fa fa-info-circle"></i> Selecciona los módulos a los que tendrá acceso este rol.
                    </div>
                  </div>
                </div>

                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" style="margin-top:6px;">
                  <button class="btn btn-primary" type="submit" id="btnGuardar">
                    <i class="fa fa-save"></i> Guardar
                  </button>
                  <button class="btn btn-danger" type="button" onclick="cancelarform()">
                    <i class="fa fa-arrow-circle-left"></i> Cancelar
                  </button>
                </div>
              </form>
            </div>
            <!--/FORMULARIO-->

          </div><!-- /neko-card -->
        </div>
      </div>
    </section>
  </div>
  <!--Fin-Contenido-->

<?php
} else {
  require 'noacceso.php';
}

require 'footer.php';
?>
<script type="text/javascript" src="scripts/rol.js"></script>

<!-- Helpers mínimos -->
<script>
  function soloLetras(el) {
    if (!el) return;
    el.value = el.value.replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+/g, ' ').replace(/\s{2,}/g, ' ').trimStart();
  }
</script>
<?php
ob_end_flush();
?>