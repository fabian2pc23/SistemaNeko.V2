<?php
// vistas/usuario.php
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

// Validador central
require_once __DIR__ . '/_requires_auth.php';

// Header del layout
require 'header.php';

// === Permiso del módulo (ACCESO/USUARIOS) ===
$canAcceso = !empty($_SESSION['acceso']) && (int)$_SESSION['acceso'] === 1;

if ($canAcceso) {
  require_once "../config/Conexion.php";

  // ==================== KPIs (Experto en Gestión de Usuarios) ====================

  // 1. Total Usuarios
  $sqlTotal = "SELECT COUNT(*) as total FROM usuario";
  $rsTotal = ejecutarConsultaSimpleFila($sqlTotal);
  $kpiTotal = $rsTotal ? (int)$rsTotal['total'] : 0;

  // 2. Usuarios Activos
  $sqlActivos = "SELECT COUNT(*) as total FROM usuario WHERE condicion=1";
  $rsActivos = ejecutarConsultaSimpleFila($sqlActivos);
  $kpiActivos = $rsActivos ? (int)$rsActivos['total'] : 0;

  // 3. Usuarios Bloqueados
  $sqlBloqueados = "SELECT COUNT(*) as total FROM usuario WHERE condicion=0";
  $rsBloqueados = ejecutarConsultaSimpleFila($sqlBloqueados);
  $kpiBloqueados = $rsBloqueados ? (int)$rsBloqueados['total'] : 0;

  // 4. Top Vendedor (Mes Actual)
  $sqlTop = "SELECT u.nombre, SUM(v.total_venta) as total_vendido 
               FROM venta v 
               JOIN usuario u ON v.idusuario = u.idusuario 
               WHERE MONTH(v.fecha_hora) = MONTH(CURRENT_DATE()) AND YEAR(v.fecha_hora) = YEAR(CURRENT_DATE()) AND v.estado='Aceptado' 
               GROUP BY v.idusuario 
               ORDER BY total_vendido DESC 
               LIMIT 1";
  $rsTop = ejecutarConsultaSimpleFila($sqlTop);
  $kpiTopNombre = $rsTop ? $rsTop['nombre'] : 'N/A';
  $kpiTopMonto = $rsTop ? (float)$rsTop['total_vendido'] : 0.00;

  // 5. Personal de Ventas (Activos)
  $sqlVentas = "SELECT COUNT(*) as total FROM usuario WHERE cargo='Vendedor' AND condicion=1";
  $rsVentas = ejecutarConsultaSimpleFila($sqlVentas);
  $kpiVentas = $rsVentas ? (int)$rsVentas['total'] : 0;

  $nekoPrimary = '#1565c0';
  $nekoPrimaryDark = '#0d47a1';
?>
  <!-- ====== Estilos Modernos (Match Venta/Cliente) ====== -->
  <style>
    :root {
      --neko-primary: <?= $nekoPrimary ?>;
      --neko-primary-dark: <?= $nekoPrimaryDark ?>;
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

    /* ==================== MODERN ROLE SELECTOR ==================== */
    /* Contenedor de badges */
    #roles-badges {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 10px;
      min-height: 38px;
      /* Evita saltos de layout */
      padding: 5px;
      border-radius: 8px;
      background-color: #f8f9fa;
      border: 1px dashed #ced4da;
      transition: all 0.3s ease;
    }

    #roles-badges:empty {
      display: none;
      /* Ocultar si no hay roles */
    }

    /* Estilo Chip/Badge */
    .role-badge {
      display: inline-flex;
      align-items: center;
      padding: 6px 12px;
      border-radius: 50px;
      /* Pill shape */
      font-size: 0.9rem;
      font-weight: 500;
      color: #fff;
      background: linear-gradient(135deg, var(--neko-primary), var(--neko-primary-dark));
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      transition: transform 0.2s, box-shadow 0.2s;
      user-select: none;
      animation: fadeIn 0.3s ease-out;
    }

    .role-badge:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    /* Badge Admin (Diferente color) */
    .role-badge.admin {
      background: linear-gradient(135deg, var(--neko-danger), #b91c1c);
    }

    /* Icono de Rol Principal */
    .role-badge .principal-icon {
      margin-right: 6px;
      color: #fcd34d;
      /* Amarillo dorado */
      font-size: 1rem;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }

    /* Botón de eliminar (x) */
    .role-badge .remove-role {
      margin-left: 8px;
      cursor: pointer;
      opacity: 0.7;
      transition: opacity 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.2);
    }

    .role-badge .remove-role:hover {
      opacity: 1;
      background: rgba(255, 255, 255, 0.4);
    }

    /* Animación de entrada */
    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: scale(0.9);
      }

      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    /* Mejoras Selectpicker */
    .bootstrap-select .dropdown-toggle {
      border-radius: 8px;
      border: 1px solid #ced4da;
      background-color: #fff;
      padding: 6px 12px;
      /* Reduced padding */
      font-size: 0.9rem;
      /* Smaller font */
      transition: all 0.2s;
    }

    .bootstrap-select .dropdown-toggle:focus,
    .bootstrap-select .dropdown-toggle:hover {
      outline: none !important;
      border-color: var(--neko-primary) !important;
      box-shadow: 0 0 0 0.2rem rgba(21, 101, 192, 0.15) !important;
    }

    /* Dropdown Menu */
    .bootstrap-select .dropdown-menu {
      border: none;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
      padding: 5px;
      /* Reduced padding */
      margin-top: 5px;
      animation: slideDown 0.2s ease-out;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Items de la lista */
    .bootstrap-select .dropdown-menu li a {
      padding: 8px 12px;
      /* Reduced padding for compact look */
      border-radius: 6px;
      margin-bottom: 2px;
      transition: background-color 0.2s;
      /* Only animate background */
      position: relative;
      display: flex;
      align-items: center;
      justify-content: space-between;
      min-height: auto;
    }

    .bootstrap-select .dropdown-menu li a:hover {
      background-color: #f1f5f9;
      color: var(--neko-primary-dark);
      /* Removed transform to stop blinking */
    }

    /* Item seleccionado */
    .bootstrap-select .dropdown-menu li.selected a {
      background: linear-gradient(135deg, var(--neko-primary-dark), var(--neko-primary));
      color: #fff !important;
      box-shadow: 0 4px 10px rgba(21, 101, 192, 0.3);
    }

    /* Checkmark personalizado - HIDE DEFAULT */
    .bootstrap-select .dropdown-menu li a span.check-mark,
    .bootstrap-select .dropdown-menu li a span.glyphicon {
      display: none !important;
    }

    .bootstrap-select .dropdown-menu li a::after {
      content: '\f111';
      /* fa-circle-thin */
      font-family: 'FontAwesome';
      font-size: 1.1rem;
      color: #cbd5e1;
      transition: all 0.2s;
    }

    .bootstrap-select .dropdown-menu li.selected a::after {
      content: '\f058';
      /* fa-check-circle */
      color: #fff;
      transform: scale(1.1);
    }

    /* Buscador dentro del select */
    .bs-searchbox .form-control {
      border-radius: 6px;
      border: 1px solid #e2e8f0;
      padding: 8px 12px;
    }

    /* Botones de acción (Select All / Deselect All) */
    .bs-actionsbox .btn-group {
      display: flex !important;
      width: 100% !important;
    }

    .bs-actionsbox .btn-group button {
      flex: 1;
      border: 1px solid #e2e8f0;
      background: #fff;
      color: #64748b;
      font-size: 0.85rem;
      padding: 8px 12px;
      border-radius: 0;
      margin: 0;
      transition: all 0.2s;
    }

    .bs-actionsbox .btn-group button:first-child {
      border-top-left-radius: 6px;
      border-bottom-left-radius: 6px;
      margin-right: -1px;
    }

    .bs-actionsbox .btn-group button:last-child {
      border-top-right-radius: 6px;
      border-bottom-right-radius: 6px;
    }

    .bs-actionsbox .btn-group button:hover {
      background: #f1f5f9;
      color: var(--neko-primary);
      border-color: var(--neko-primary);
      z-index: 1;
    }

    .kpi-card {
      background: #fff;
      border-radius: 14px;
      padding: 18px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
      border: 1px solid rgba(0, 0, 0, .06);
      transition: transform 0.2s ease;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: center;
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

    .kpi-orange .kpi-card__icon {
      background: #fffbeb;
      color: #d97706;
    }

    .kpi-purple .kpi-card__icon {
      background: #f3e8ff;
      color: #9333ea;
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

    .bg-blue {
      background: #dbeafe !important;
      color: #1e40af !important;
    }

    /* Formulario */
    .section-title {
      font-weight: 600;
      color: #0b2752;
      margin: 16px 0 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .section-title .dot {
      width: 8px;
      height: 8px;
      border-radius: 999px;
      background: var(--neko-primary);
    }

    input[readonly].disabled {
      background: #f3f4f6 !important;
      cursor: not-allowed;
    }

    /* Panel permisos */
    .nk-permisos {
      max-height: 220px;
      overflow: auto;
      margin-bottom: 0;
    }

    .nk-ul-permisos {
      list-style: none;
      padding-left: 0;
      margin: 0;
    }

    .read-only-permisos input[type="checkbox"] {
      pointer-events: none !important;
      cursor: not-allowed !important;
      opacity: 0.6;
      accent-color: #5353ec;
    }

    .read-only-permisos label {
      cursor: not-allowed !important;
      color: #555;
    }

    .read-only-permisos input[type="checkbox"]:checked~label {
      color: #1565c0 !important;
      font-weight: 600 !important;
    }

    .nk-avatar {
      border: 2px solid #e5e7eb;
      border-radius: 10px;
      object-fit: cover;
    }

    .input-eye {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      cursor: pointer;
      opacity: .75;
      user-select: none;
      font-size: 1.2rem;
    }

    .input-eye:hover {
      opacity: 1;
    }

    /* Password Requirements */
    .pwd-req {
      font-size: 0.85rem;
      margin-bottom: 4px;
      display: flex;
      align-items: center;
      gap: 6px;
      color: #64748b;
      transition: color 0.2s;
    }

    .pwd-req i {
      width: 16px;
      text-align: center;
      font-size: 0.9rem;
    }

    .pwd-req.valid {
      color: var(--neko-success);
    }

    .pwd-req.invalid {
      color: var(--neko-danger);
    }

    /* Role Badges */
    #roles-badges {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 8px;
      min-height: 28px;
      align-items: center;
    }

    .role-badge {
      background: #f1f5f9;
      color: #475569;
      padding: 5px 12px;
      border-radius: 50px;
      font-size: 0.85rem;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border: 1px solid #e2e8f0;
      transition: all 0.2s;
      width: auto;
      /* Force auto width */
      max-width: 100%;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    }

    .role-badge:hover {
      background: #e2e8f0;
      transform: translateY(-1px);
    }

    .role-badge.admin {
      background: #fee2e2;
      color: #991b1b;
      border-color: #fecaca;
    }

    .role-badge.admin:hover {
      background: #fecaca;
    }

    .role-badge i {
      cursor: pointer;
      opacity: 0.6;
      font-size: 0.9rem;
      transition: opacity 0.2s;
    }

    .role-badge i:hover {
      opacity: 1;
      color: #ef4444;
    }

    .role-badge .principal-icon {
      color: #f59e0b;
      opacity: 1;
      cursor: default;
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

  <div class="content-wrapper">
    <section class="content">
      <div class="row">
        <div class="col-md-12">
          <div class="neko-card">

            <!-- Header -->
            <div class="neko-card__header">
              <h1 class="neko-card__title"><i class="fa fa-users"></i> Gestión de Usuarios</h1>
              <div class="neko-actions">
                <a href="../reportes/rptusuarios.php" target="_blank" class="btn btn-light" style="background:#e3f2fd;border:0;color:#0d47a1;">
                  <i class="fa fa-print"></i> Reporte General
                </a>
                <button class="btn btn-success" id="btnagregar" onclick="mostrarform(true)">
                  <i class="fa fa-plus-circle"></i> Nuevo Usuario
                </button>
              </div>
            </div>

            <div class="neko-card__body panel-body" id="listadoregistros">

              <!-- KPIs -->
              <div class="kpi-container">
                <!-- 1. Total Usuarios -->
                <div class="kpi-card kpi-blue" onclick="mostrarDetalleKPI('total')" style="cursor:pointer;">
                  <div class="kpi-card__header">
                    <div class="kpi-card__title">Total Usuarios <i class="fa fa-info-circle" style="opacity:0.5;font-size:0.7rem;"></i></div>
                    <div class="kpi-card__icon"><i class="fa fa-users"></i></div>
                  </div>
                  <div class="kpi-card__value"><?= number_format($kpiTotal) ?></div>
                  <div class="kpi-card__sub">Registrados</div>
                </div>

                <!-- 2. Activos -->
                <div class="kpi-card kpi-green" onclick="mostrarDetalleKPI('activos')" style="cursor:pointer;">
                  <div class="kpi-card__header">
                    <div class="kpi-card__title">Activos <i class="fa fa-info-circle" style="opacity:0.5;font-size:0.7rem;"></i></div>
                    <div class="kpi-card__icon"><i class="fa fa-check-circle"></i></div>
                  </div>
                  <div class="kpi-card__value"><?= number_format($kpiActivos) ?></div>
                  <div class="kpi-card__sub">Habilitados</div>
                </div>

                <!-- 3. Bloqueados -->
                <div class="kpi-card kpi-red" onclick="mostrarDetalleKPI('bloqueados')" style="cursor:pointer;">
                  <div class="kpi-card__header">
                    <div class="kpi-card__title">Bloqueados <i class="fa fa-info-circle" style="opacity:0.5;font-size:0.7rem;"></i></div>
                    <div class="kpi-card__icon"><i class="fa fa-ban"></i></div>
                  </div>
                  <div class="kpi-card__value"><?= number_format($kpiBloqueados) ?></div>
                  <div class="kpi-card__sub">Sin acceso</div>
                </div>

                <!-- 4. Top Vendedor -->
                <div class="kpi-card kpi-orange" onclick="mostrarDetalleKPI('top')" style="cursor:pointer;">
                  <div class="kpi-card__header">
                    <div class="kpi-card__title">Top Vendedor <i class="fa fa-info-circle" style="opacity:0.5;font-size:0.7rem;"></i></div>
                    <div class="kpi-card__icon"><i class="fa fa-trophy"></i></div>
                  </div>
                  <div class="kpi-card__value" style="font-size:1.1rem; margin-top:4px;"><?= strlen($kpiTopNombre) > 18 ? substr($kpiTopNombre, 0, 18) . '...' : $kpiTopNombre ?></div>
                  <div class="kpi-card__sub">S/. <?= number_format($kpiTopMonto, 2) ?> (Mes)</div>
                </div>

                <!-- 5. Personal Ventas -->
                <div class="kpi-card kpi-purple" onclick="mostrarDetalleKPI('vendedores')" style="cursor:pointer;">
                  <div class="kpi-card__header">
                    <div class="kpi-card__title">Personal Ventas <i class="fa fa-info-circle" style="opacity:0.5;font-size:0.7rem;"></i></div>
                    <div class="kpi-card__icon"><i class="fa fa-briefcase"></i></div>
                  </div>
                  <div class="kpi-card__value"><?= number_format($kpiVentas) ?></div>
                  <div class="kpi-card__sub">Vendedores Activos</div>
                </div>
              </div>

              <!-- Filtros -->
              <div class="filter-bar">
                <!-- Estado -->
                <div class="status-group">
                  <button type="button" class="status-btn active" id="filter-todos" onclick="filtrarEstado('todos')">Todos</button>
                  <button type="button" class="status-btn" id="filter-activos" onclick="filtrarEstado('activos')"><i class="fa fa-check"></i> Activos</button>
                  <button type="button" class="status-btn" id="filter-bloqueados" onclick="filtrarEstado('bloqueados')"><i class="fa fa-ban"></i> Bloqueados</button>
                </div>

                <!-- Buscador -->
                <div class="search-container">
                  <i class="fa fa-search"></i>
                  <input type="text" id="search-input" class="search-input" placeholder="Buscar usuario, documento, email...">
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
                    <th>Tipo Doc.</th>
                    <th>Número</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Cargo</th>
                    <th>Foto</th>
                    <th>Estado</th>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>

            <!-- FORMULARIO -->
            <div class="neko-card__body panel-body" id="formularioregistros" style="display:none;">
              <form name="formulario" id="formulario" method="POST" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="idusuario" id="idusuario">

                <h4 class="section-title"><span class="dot"></span> Paso 1: Identificación</h4>
                <div class="row">
                  <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <label>Tipo Documento (*):</label>
                    <select class="form-control" name="tipo_documento" id="tipo_documento" required>
                      <option value="">Seleccione...</option>
                      <option value="DNI">DNI</option>
                      <option value="RUC">RUC</option>
                      <option value="Carnet de Extranjería">Carnet de Extranjería</option>
                    </select>
                    <small class="text-muted" id="hint_tipo">Selecciona el tipo de documento</small>
                  </div>
                  <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <label>Número de Documento (*):</label>
                    <input type="text" class="form-control" name="num_documento" id="num_documento" required>
                    <small class="text-muted" id="hint_numero">Ingresa el número de documento</small>
                  </div>
                </div>

                <div class="row">
                  <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <label>Nombre Completo / Razón Social (*):</label>
                    <input type="text" class="form-control" name="nombre" id="nombre" maxlength="100" placeholder="Se autocompletará con RENIEC/SUNAT" readonly required>
                    <small class="text-info"><i class="fa fa-info-circle"></i> Este campo se llena automáticamente al validar el documento</small>
                  </div>
                </div>

                <h4 class="section-title"><span class="dot"></span> Paso 2: Datos de Contacto</h4>
                <div class="row">
                  <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <label>Email (*):</label>
                    <div style="position:relative;">
                      <input type="email" class="form-control" name="email" id="email" maxlength="50" placeholder="ejemplo@dominio.com" required>
                      <span id="email-status" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:1.1rem;opacity:.8;"></span>
                    </div>
                    <small class="text-muted" id="email-hint">Se usará como usuario de acceso</small>
                  </div>
                  <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <label>Teléfono (*):</label>
                    <input type="text" class="form-control" name="telefono" id="telefono" maxlength="15" placeholder="Número de teléfono" required>
                    <small class="text-muted">Solo números, guiones y espacios</small>
                  </div>
                </div>

                <div class="row">
                  <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <label>Dirección:</label>
                    <input type="text" class="form-control" name="direccion" id="direccion" maxlength="70" placeholder="Se autocompletará con RENIEC/SUNAT">
                    <small class="text-info"><i class="fa fa-map-marker"></i> La dirección se obtiene automáticamente al validar el documento</small>
                  </div>
                  <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <label>Roles (*):</label>
                    <select class="form-control selectpicker" name="cargo[]" id="cargo" data-live-search="true" multiple data-selected-text-format="count > 2" data-count-selected-text="{0} roles seleccionados" data-actions-box="true" data-width="100%" title="Seleccione roles..." data-select-all-text="Seleccionar Todo" data-deselect-all-text="Deseleccionar Todo" required>
                    </select>
                    <div id="roles-badges"></div>
                    <small class="text-muted">Rol del usuario en el sistema</small>
                  </div>
                </div>

                <h4 class="section-title"><span class="dot"></span> Paso 3: Seguridad y Accesos</h4>
                <div class="row">
                  <div class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <label>Contraseña (*):</label>
                    <div style="position:relative;">
                      <input type="password" class="form-control" name="clave" id="clave" maxlength="64" placeholder="Mínimo 10 caracteres" required style="padding-right:45px;">
                      <span class="input-eye" id="toggleClave">👁️</span>
                    </div>
                    <div id="pwd-strength" style="margin-top:8px; display:none;">
                      <div class="pwd-req" id="r-len"><i class="fa fa-times text-danger"></i> 10-64 caracteres</div>
                      <div class="pwd-req" id="r-up"><i class="fa fa-times text-danger"></i> 1 mayúscula</div>
                      <div class="pwd-req" id="r-low"><i class="fa fa-times text-danger"></i> 1 minúscula</div>
                      <div class="pwd-req" id="r-num"><i class="fa fa-times text-danger"></i> 1 número</div>
                      <div class="pwd-req" id="r-spe"><i class="fa fa-times text-danger"></i> 1 especial</div>
                    </div>
                  </div>

                  <!-- PERMISOS SOLO VISUALES (POR ROL) -->
                  <div class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
                    <label>Permisos del Rol:</label>
                    <div class="well well-sm nk-permisos">
                      <ul id="permisos" class="nk-ul-permisos read-only-permisos">
                        <!-- Se llenan dinámicamente -->
                      </ul>
                    </div>
                    <small class="text-info">Los permisos se asignan según el Rol. Aquí solo se visualizan.</small>
                  </div>
                </div>

                <h4 class="section-title"><span class="dot"></span> Paso 4: Foto de Perfil (Opcional)</h4>
                <div class="row">
                  <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                    <label>Imagen:</label>
                    <input type="file" class="form-control" name="imagen" id="imagen" accept="image/jpeg,image/png,image/gif">
                    <input type="hidden" name="imagenactual" id="imagenactual">
                    <small class="text-muted">JPG/PNG/GIF (máx. 2MB)</small>
                  </div>
                  <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12 text-center">
                    <img src="" width="150" height="150" id="imagenmuestra" class="nk-avatar" style="display:none;">
                  </div>
                </div>

                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" style="margin-top:12px;">
                  <button class="btn btn-primary btn-lg" type="submit" id="btnGuardar">
                    <i class="fa fa-save"></i> Guardar Usuario
                  </button>
                  <button class="btn btn-danger btn-lg" onclick="cancelarform()" type="button">
                    <i class="fa fa-arrow-circle-left"></i> Cancelar
                  </button>
                </div>
              </form>
            </div>
            <!--/FORMULARIO-->

          </div><!-- /neko-card -->
        </div><!-- /.col -->
      </div><!-- /.row -->
    </section><!-- /.content -->
  </div><!-- /.content-wrapper -->
  <!--Fin-Contenido-->

<?php
} else {
  require 'noacceso.php';
}

// Footer del layout
require 'footer.php';
?>
<!-- Scripts específicos de esta vista -->
<script type="text/javascript" src="scripts/usuario.js"></script>

<?php
ob_end_flush();
