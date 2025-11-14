<?php
// vistas/rol.php - VERSIÓN CORREGIDA FINAL
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

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
<!-- ====== Estilos modernos (alineados a articulo.php) ====== -->
<style>
  :root{
    --neko-primary:#1565c0;
    --neko-primary-dark:#0d47a1;
    --neko-bg:#f5f7fb;
  }
  .content-wrapper{ background:var(--neko-bg); }

  .neko-card{
    background:#fff; border:1px solid rgba(2,24,54,.06);
    border-radius:14px; box-shadow:0 8px 24px rgba(2,24,54,.06);
    overflow:hidden; margin-top:10px;
  }
  .neko-card__header{
    display:flex; align-items:center; justify-content:space-between;
    background:linear-gradient(90deg,var(--neko-primary-dark),var(--neko-primary));
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

  /* Tabla principal */
  #tbllistado thead th{ 
    background: linear-gradient(135deg, #1e293b, #334155);
    color:#fff;
    font-weight:600;
    text-transform:uppercase;
    font-size:0.75rem;
    letter-spacing:0.5px;
    padding:14px 12px;
  }
  #tbllistado tfoot th{ background:#f8fafc; font-weight:600; }
  #tbllistado tbody tr:hover{ background:#f8fafc; }

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

  .form-group{ margin-bottom:16px; }

  /* Pequeños helpers */
  .section-title{
    font-weight:600; color:#0b2752; margin:6px 0 14px; display:flex; align-items:center; gap:8px;
  }
  .section-title .dot{ width:8px; height:8px; border-radius:999px; background:var(--neko-primary); display:inline-block; }

  /* ===== PERMISOS MEJORADOS ===== */
  .nk-permisos{
    background:#f8fafc;
    border:1px solid #e2e8f0;
    border-radius:10px;
    padding:16px;
    max-height:400px;
    overflow-y:auto;
  }
  .nk-ul-permisos{
    list-style:none;
    padding:0;
    margin:0;
  }
  .nk-ul-permisos li{
    padding:10px 12px;
    border-bottom:1px solid #e2e8f0;
    transition: background 0.2s ease;
  }
  .nk-ul-permisos li:last-child{
    border-bottom:none;
  }
  .nk-ul-permisos li:hover{
    background:#e3f2fd;
  }
  .nk-ul-permisos label{
    display:flex;
    align-items:center;
    gap:10px;
    cursor:pointer;
    margin:0;
    font-weight:500;
    color:#334155;
  }
  .nk-ul-permisos input[type="checkbox"]{
    width:18px;
    height:18px;
    cursor:pointer;
  }
  .help-hint{
    color:#64748b;
    font-size:.85rem;
    margin-top:4px;
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
</style>

<!--Contenido-->
<div class="content-wrapper">
  <section class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="neko-card">

          <!-- Header visual -->
          <div class="neko-card__header">
            <h1 class="neko-card__title"><i class="fa fa-shield"></i> Roles</h1>
            <div class="neko-actions">
              <button class="btn btn-success" id="btnagregar" onclick="mostrarform(true)">
                <i class="fa fa-plus-circle"></i> Agregar
              </button>
            </div>
          </div>

          <!-- LISTADO -->
          <div class="neko-card__body panel-body table-responsive" id="listadoregistros">
            
            <!-- KPIs OPTIMIZADOS -->
            <div class="kpi-container">
              <!-- 1. Total roles -->
              <div class="kpi-card kpi-card--primary">
                <div class="kpi-card__title">Total roles</div>
                <div class="kpi-card__header">
                  <div>
                    <h2 class="kpi-card__value" id="kpi-total-roles">
                      <?php echo $kpiTotalRoles; ?>
                    </h2>
                  </div>
                  <div class="kpi-card__icon">
                    <i class="fa fa-shield"></i>
                  </div>
                </div>
              </div>

              <!-- 2. Activos / Inactivos -->
              <div class="kpi-card kpi-card--success">
                <div class="kpi-card__title">Estado de roles</div>
                <div class="kpi-card__dual">
                  <div class="kpi-dual-item">
                    <div class="kpi-dual-item__label">Activos</div>
                    <div class="kpi-dual-item__value" id="kpi-roles-activos" style="color:#059669;">
                      <?php echo $kpiRolesActivos; ?>
                    </div>
                  </div>
                  <div class="kpi-dual-divider"></div>
                  <div class="kpi-dual-item">
                    <div class="kpi-dual-item__label">Inactivos</div>
                    <div class="kpi-dual-item__value" id="kpi-roles-inactivos" style="color:#dc2626;">
                      <?php echo $kpiRolesInactivos; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <table id="tbllistado" class="table table-striped table-bordered table-condensed table-hover">
              <thead>
                <th>Opciones</th>
                <th>Nombre</th>
                <th>Estado</th>
                <th>Creado</th>
              </thead>
              <tbody></tbody>
              <tfoot>
                <th>Opciones</th>
                <th>Nombre</th>
                <th>Estado</th>
                <th>Creado</th>
              </tfoot>
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
                    required
                  >
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

<!-- Helpers mínimos (no interfieren con rol.js) -->
<script>
  // Restringe a letras y espacios (mantiene tu comportamiento actual)
  function soloLetras(el){
    if(!el) return;
    el.value = el.value.replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+/g, ' ').replace(/\s{2,}/g, ' ').trimStart();
  }
</script>
<?php
ob_end_flush();
?>