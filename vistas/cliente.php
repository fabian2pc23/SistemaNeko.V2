<?php
// vistas/cliente.php
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

require_once __DIR__ . '/_requires_auth.php';

// (Opcional pero recomendado) helper con can('Permiso')
$canHelper = false;
$authzPath = __DIR__ . '/_authz.php';
if (file_exists($authzPath)) {
  require_once $authzPath;
  if (function_exists('can')) { $canHelper = true; }
}

/* =========================
   AUTORIZACIÓN PARA CLIENTES
   =========================
*/
$canClientes = $canHelper ? can('Ventas')
                          : (!empty($_SESSION['ventas']) && (int)$_SESSION['ventas'] === 1);

require 'header.php';

if ($canClientes):
    require_once "../config/Conexion.php";

    // ==================== KPIs ====================
    // 1. Total Clientes
    $sqlTotal = "SELECT COUNT(*) as total FROM persona WHERE tipo_persona='Cliente'";
    $rsTotal = ejecutarConsultaSimpleFila($sqlTotal);
    $kpiTotal = $rsTotal ? (int)$rsTotal['total'] : 0;

    // 2. Activos
    $sqlActivos = "SELECT COUNT(*) as total FROM persona WHERE tipo_persona='Cliente' AND condicion=1";
    $rsActivos = ejecutarConsultaSimpleFila($sqlActivos);
    $kpiActivos = $rsActivos ? (int)$rsActivos['total'] : 0;

    // 3. Inactivos
    $sqlInactivos = "SELECT COUNT(*) as total FROM persona WHERE tipo_persona='Cliente' AND condicion=0";
    $rsInactivos = ejecutarConsultaSimpleFila($sqlInactivos);
    $kpiInactivos = $rsInactivos ? (int)$rsInactivos['total'] : 0;

    // 4. Cliente Destacado (Más compras aceptadas)
    $sqlTop = "SELECT p.nombre, COUNT(v.idventa) as total_compras 
               FROM persona p 
               LEFT JOIN venta v ON p.idpersona = v.idcliente 
               WHERE p.tipo_persona='Cliente' AND v.estado='Aceptado'
               GROUP BY p.idpersona 
               ORDER BY total_compras DESC 
               LIMIT 1";
    $rsTop = ejecutarConsultaSimpleFila($sqlTop);
    $kpiTopNombre = $rsTop ? $rsTop['nombre'] : 'N/A';
    $kpiTopCompras = $rsTop ? (int)$rsTop['total_compras'] : 0;

    $nekoPrimary = '#1565c0';
    $nekoPrimaryDark = '#0d47a1';
?>
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

  /* ==================== KPI CARDS ==================== */
  .kpi-container{
    display:grid; 
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap:16px; 
    margin-bottom:20px;
  }
  .kpi-card{
    background:#fff; border-radius:14px; padding:18px; 
    box-shadow:0 2px 8px rgba(0,0,0,.08);
    border:1px solid rgba(0,0,0,.06);
    transition: transform 0.2s ease;
    height: 100%;
    display: flex; flex-direction: column; justify-content: center;
  }
  .kpi-card:hover{ transform: translateY(-2px); }
  
  .kpi-card__header{ display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px; }
  .kpi-card__title{ font-size:0.75rem; color:#64748b; text-transform:uppercase; font-weight:700; letter-spacing:0.5px; }
  .kpi-card__icon{ 
    width:40px; height:40px; border-radius:10px; 
    display:flex; align-items:center; justify-content:center; font-size:18px; 
  }
  .kpi-card__value{ font-size:1.6rem; font-weight:800; color:#1e293b; line-height:1.2; }
  .kpi-card__sub{ font-size:0.8rem; color:#64748b; margin-top:4px; display:flex; align-items:center; gap:4px; }
  
  /* Variaciones de color KPI */
  .kpi-blue .kpi-card__icon{ background:#eff6ff; color:#2563eb; }
  .kpi-green .kpi-card__icon{ background:#ecfdf5; color:#059669; }
  .kpi-orange .kpi-card__icon{ background:#fffbeb; color:#d97706; }
  .kpi-purple .kpi-card__icon{ background:#f3e8ff; color:#9333ea; }
  .kpi-red .kpi-card__icon{ background:#fef2f2; color:#dc2626; }

  /* ==================== FILTROS MODERNOS ==================== */
  .filter-bar {
    display: flex; align-items: center; gap: 12px; margin-bottom: 20px;
    flex-wrap: wrap; background: #fff; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0;
  }
  
  /* Status Pills */
  .status-group { display: flex; background: #f1f5f9; padding: 4px; border-radius: 8px; gap: 4px; }
  .status-btn {
    border: none; background: transparent; padding: 6px 12px; border-radius: 6px;
    font-size: 0.85rem; font-weight: 600; color: #64748b; cursor: pointer;
    display: flex; align-items: center; gap: 6px; transition: all 0.2s;
  }
  .status-btn:hover { color: #334155; }
  .status-btn.active { background: #fff; color: var(--neko-primary); box-shadow: 0 1px 3px rgba(0,0,0,0.1); }

  /* Selects */
  .filter-select {
    padding: 6px 24px 6px 10px; border: 1px solid #e2e8f0; border-radius: 6px;
    font-size: 0.85rem; color: #334155; outline: none; cursor: pointer; background-color: #fff;
    appearance: none;
    background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%2364748b%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
    background-repeat: no-repeat; background-position: right 8px center; background-size: 8px auto;
  }

  /* Search */
  .search-container { flex: 1; min-width: 200px; position: relative; }
  .search-container i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
  .search-input {
    width: 100%; padding: 8px 12px 8px 36px; border: 1px solid #e2e8f0; border-radius: 8px;
    font-size: 0.9rem; outline: none; transition: border-color 0.2s;
  }
  .search-input:focus { border-color: var(--neko-primary); }

  /* Export */
  .export-actions { display: flex; gap: 6px; }
  .btn-export {
    padding: 6px 12px; border: 1px solid #e2e8f0; background: #fff; border-radius: 6px;
    color: #64748b; font-size: 0.85rem; cursor: pointer; display: flex; align-items: center; gap: 6px;
  }
  .btn-export:hover { background: #f8fafc; color: #334155; border-color: #cbd5e1; }

  /* Tabla */
  #tbllistado thead th{ background: linear-gradient(135deg, #1e293b, #334155); color:#fff; font-weight:600; text-transform:uppercase; font-size:0.75rem; padding:12px; }
  #tbllistado tbody tr:hover{ background:#f8fafc; }
  
  /* Ocultar controles nativos DT */
  #tbllistado_wrapper .dataTables_filter, #tbllistado_wrapper .dataTables_length, #tbllistado_wrapper .dt-buttons { display: none !important; }

  /* Labels */
  .label{ padding:6px 12px; border-radius:6px; font-weight:600; font-size:0.75rem; }
  .bg-green{ background:#d1fae5 !important; color:#065f46 !important; }
  .bg-red{ background:#fee2e2 !important; color:#991b1b !important; }

  /* Formulario */
  .section-title{ font-weight:600; color:#0b2752; margin:16px 0 10px; display:flex; align-items:center; gap:8px; }
  .section-title .dot{ width:8px; height:8px; border-radius:999px; background:var(--neko-primary); }
  input[readonly].disabled{ background:#f3f4f6 !important; cursor:not-allowed; }

  @media (max-width: 992px) {
    .filter-bar { flex-direction: column; align-items: stretch; }
    .status-group, .search-container, .export-actions { width: 100%; }
    .kpi-container { grid-template-columns: 1fr; }
  }
</style>

<div class="content-wrapper">
  <section class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="neko-card">
          
          <!-- Header -->
          <div class="neko-card__header">
            <h1 class="neko-card__title"><i class="fa fa-users"></i> Gestión de Clientes</h1>
            <div class="neko-actions">
              <a href="../reportes/rptclientes.php" target="_blank" class="btn btn-light" style="background:#e3f2fd;border:0;color:#0d47a1;">
                <i class="fa fa-print"></i> Reporte General
              </a>
              <button class="btn btn-success" id="btnagregar" onclick="mostrarform(true)">
                <i class="fa fa-plus-circle"></i> Nuevo Cliente
              </button>
            </div>
          </div>

          <div class="neko-card__body panel-body" id="listadoregistros">
            
            <!-- KPIs -->
            <div class="kpi-container">
              <!-- 1. Total Clientes -->
              <div class="kpi-card kpi-blue">
                <div class="kpi-card__header">
                  <div class="kpi-card__title">Total Clientes</div>
                  <div class="kpi-card__icon"><i class="fa fa-users"></i></div>
                </div>
                <div class="kpi-card__value"><?= number_format($kpiTotal) ?></div>
                <div class="kpi-card__sub">Registrados</div>
              </div>

              <!-- 2. Activos -->
              <div class="kpi-card kpi-green">
                <div class="kpi-card__header">
                  <div class="kpi-card__title">Activos</div>
                  <div class="kpi-card__icon"><i class="fa fa-check-circle"></i></div>
                </div>
                <div class="kpi-card__value"><?= number_format($kpiActivos) ?></div>
                <div class="kpi-card__sub">Habilitados</div>
              </div>

              <!-- 3. Inactivos -->
              <div class="kpi-card kpi-red">
                <div class="kpi-card__header">
                  <div class="kpi-card__title">Inactivos</div>
                  <div class="kpi-card__icon"><i class="fa fa-times-circle"></i></div>
                </div>
                <div class="kpi-card__value"><?= number_format($kpiInactivos) ?></div>
                <div class="kpi-card__sub">Deshabilitados</div>
              </div>

              <!-- 4. Cliente Destacado -->
              <div class="kpi-card kpi-orange">
                <div class="kpi-card__header">
                  <div class="kpi-card__title">Cliente Destacado</div>
                  <div class="kpi-card__icon"><i class="fa fa-trophy"></i></div>
                </div>
                <div class="kpi-card__value" style="font-size:1.1rem; margin-top:4px;"><?= strlen($kpiTopNombre) > 18 ? substr($kpiTopNombre,0,18).'...' : $kpiTopNombre ?></div>
                <div class="kpi-card__sub"><?= $kpiTopCompras ?> compras aceptadas</div>
              </div>
            </div>

            <!-- Filtros -->
            <div class="filter-bar">
              <!-- Estado -->
              <div class="status-group">
                <button type="button" class="status-btn active" id="filter-todos" onclick="filtrarEstado('todos')">Todos</button>
                <button type="button" class="status-btn" id="filter-activos" onclick="filtrarEstado('activos')"><i class="fa fa-check"></i> Activos</button>
                <button type="button" class="status-btn" id="filter-inactivos" onclick="filtrarEstado('inactivos')"><i class="fa fa-times"></i> Inactivos</button>
              </div>

              <!-- Buscador -->
              <div class="search-container">
                <i class="fa fa-search"></i>
                <input type="text" id="search-input" class="search-input" placeholder="Buscar cliente, documento...">
              </div>

              <!-- Mostrar filas -->
              <div style="display:flex; align-items:center; gap:8px; font-size:0.85rem; color:#64748b;">
                <span>Mostrar:</span>
                <select id="length-select" class="filter-select" style="width:auto; padding-right:24px;" onchange="cambiarLongitud(this.value)">
                  <option value="5">5</option>
                  <option value="10" selected>10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
              </div>

              <!-- Exportar -->
              <div class="export-actions">
                <button type="button" class="btn-export" onclick="exportarTabla('copy')" title="Copiar"><i class="fa fa-copy"></i> Copiar</button>
                <button type="button" class="btn-export" onclick="exportarTabla('excel')" title="Excel"><i class="fa fa-file-excel-o"></i> Excel</button>
                <button type="button" class="btn-export" onclick="exportarTabla('csv')" title="CSV"><i class="fa fa-file-text-o"></i> CSV</button>
                <button type="button" class="btn-export" onclick="exportarTabla('pdf')" title="PDF"><i class="fa fa-file-pdf-o"></i> PDF</button>
              </div>
            </div>

            <!-- Tabla -->
            <div class="table-responsive" style="padding:0;">
              <table id="tbllistado" class="table table-striped table-hover" style="width:100%; margin:0;">
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
              </table>
            </div>
          </div>

          <!-- FORMULARIO -->
          <div class="neko-card__body panel-body" id="formularioregistros" style="display:none;">
            <form name="formulario" id="formulario" method="POST">
              <!-- Necesarios para guardado -->
              <input type="hidden" id="idpersona" name="idpersona">
              <input type="hidden" id="tipo_persona" name="tipo_persona" value="Cliente">
              <!-- espejo para cuando el select esté disabled -->
              <input type="hidden" id="tipo_documento_hidden" name="tipo_documento" value="DNI">

              <h4 class="section-title"><span class="dot"></span> Datos del documento</h4>

              <div class="row">
                <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                  <label>Tipo Documento:</label>
                  <select class="form-control selectpicker" id="tipo_documento_view">
                    <option value="DNI" selected>DNI</option>
                  </select>
                  <small class="text-muted">DNI (8 dígitos)</small>
                </div>

                <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12" id="wrap-numdoc">
                  <label>Número Documento:</label>
                  <div class="input-group">
                    <span class="input-group-btn">
                      <button type="button" id="btnBuscarDoc" class="btn btn-default" title="Consultar RENIEC">
                        <i class="fa fa-search"></i>
                      </button>
                    </span>
                    <input type="text" class="form-control" name="num_documento" id="num_documento"
                           placeholder="DNI" maxlength="8" inputmode="numeric" pattern="\d{8}" autocomplete="off">
                  </div>
                  <small id="estadoDoc" style="display:block;margin-top:6px;color:#374151;font-weight:600;">Esperando número…</small>
                </div>
              </div>

              <h4 class="section-title"><span class="dot"></span> Datos básicos</h4>

              <div class="row">
                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                  <label>Nombre (autocompletado):</label>
                  <input type="text" class="form-control disabled" name="nombre" id="nombre" maxlength="100" placeholder="Nombre" readonly>
                  <small class="text-muted">Se llena automáticamente desde RENIEC (DNI).</small>
                </div>
              </div>

              <div class="row">
                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                  <label>Dirección (autocompletada):</label>
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-map-marker"></i></span>
                    <input type="text" class="form-control disabled" name="direccion" id="direccion" placeholder="Dirección" readonly>
                  </div>
                </div>
              </div>

              <h4 class="section-title"><span class="dot"></span> Contacto</h4>

              <div class="row">
                <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                  <label>Teléfono:</label>
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                    <input type="text" class="form-control" name="telefono" id="telefono" maxlength="20" placeholder="Teléfono">
                  </div>
                  <small class="text-muted">Solo números; opcional.</small>
                </div>

                <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                  <label>Email:</label>
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-envelope-o"></i></span>
                    <input type="email" class="form-control" name="email" id="email" maxlength="50" placeholder="Email">
                  </div>
                </div>
              </div>

              <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" style="margin-top:12px;">
                <button class="btn btn-primary" type="submit" id="btnGuardar">
                  <i class="fa fa-save"></i> Guardar
                </button>
                <button id="btnCancelar" class="btn btn-danger" type="button" onclick="cancelarform()">
                  <i class="fa fa-arrow-circle-left"></i> Cancelar
                </button>
              </div>
            </form>
          </div>
          <!-- /FORMULARIO -->

        </div>
      </div>
    </div>
  </section>
</div>

<?php
else:
  // Sin permiso
  require 'noacceso.php';
endif;

require 'footer.php';
?>
<script type="text/javascript" src="scripts/cliente.js"></script>
<?php
ob_end_flush();
