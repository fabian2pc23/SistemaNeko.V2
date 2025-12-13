<?php
// ======= Arranque de sesión + guardas =======
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (empty($_SESSION['idusuario'])) { header('Location: ../login.php'); exit; }

// ======= Valores seguros =======
$sesNombre = htmlspecialchars($_SESSION['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');
$sesImagen = htmlspecialchars($_SESSION['imagen'] ?? 'default.png', ENT_QUOTES, 'UTF-8');

/*
 * Rol real del usuario logueado:
 * 1) cargo        (muchos proyectos lo guardan así: "Admin", "Vendedor", etc.)
 * 2) rol / rol_nombre / nombre_rol / role
 * 3) si solo hay id_rol/idrol => muestra “Rol #<id>” como fallback visual
 */
$rolId  = $_SESSION['id_rol'] ?? $_SESSION['idrol'] ?? null;
$rolRaw =
    $_SESSION['cargo']
 ?? $_SESSION['rol']
 ?? $_SESSION['rol_nombre']
 ?? $_SESSION['nombre_rol']
 ?? $_SESSION['role']
 ?? ($rolId ? ('Rol #'.(string)$rolId) : 'Usuario');

$sesRol = htmlspecialchars((string)$rolRaw, ENT_QUOTES, 'UTF-8');

// Helper: flags de permisos
function flag($k){ return !empty($_SESSION[$k]) && (int)$_SESSION[$k] === 1; }
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Ferretería Neko | Panel</title>
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

  <!-- Core CSS -->
  <link rel="stylesheet" href="../public/css/bootstrap.min.css">
  <link rel="stylesheet" href="../public/css/font-awesome.css">
  <link rel="stylesheet" href="../public/css/AdminLTE.min.css">
  <link rel="stylesheet" href="../public/css/_all-skins.min.css">
  <link rel="stylesheet" href="../public/css/neko-corporate.css">
  <link rel="stylesheet" href="../public/css/neko-responsive.css">
  <link rel="apple-touch-icon" href="../public/img/apple-touch-icon.png">
  <link rel="shortcut icon" href="../public/img/favicon.ico">

  <!-- DATATABLES -->
  <link rel="stylesheet" href="../public/datatables/jquery.dataTables.min.css">
  <link rel="stylesheet" href="../public/datatables/buttons.dataTables.min.css"/>
  <link rel="stylesheet" href="../public/datatables/responsive.dataTables.min.css"/>
  <link rel="stylesheet" href="../public/css/bootstrap-select.min.css">

  <!-- Fuente -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

  <style>
    :root{
      --neko-primary:#1565c0;
      --neko-primary-dark:#0d47a1;
      --neko-primary-600:#1976d2;
      --sidebar-w:230px;
      --header-h:50px;
    }
    html,body{ font-family:"Inter",system-ui,-apple-system,"Segoe UI",Roboto,Arial,sans-serif; }
    body { background:#f5f7fb; }

    /* Skin */
    .skin-neko-blue .main-header .logo{
      background: linear-gradient(90deg,var(--neko-primary-dark),var(--neko-primary));
      color:#fff!important; border-right:0; font-weight:600; letter-spacing:.3px;
    }
    .skin-neko-blue .main-header .navbar{
      background: linear-gradient(90deg,var(--neko-primary),var(--neko-primary-600));
      box-shadow:0 2px 8px rgba(0,0,0,.15);
    }
    .skin-neko-blue .main-header .navbar .nav>li>a{ color:#eaf2ff; }
    .skin-neko-blue .main-header .navbar .nav>li>a:hover{ background:rgba(255,255,255,.08); color:#fff; }

    .navbar .user-image{ box-shadow:0 0 0 2px rgba(255,255,255,.35); }

    /* Layout fijo */
    .main-header{ position:fixed; top:0; left:0; right:0; height:var(--header-h); z-index:1030; }
    .main-sidebar{
      position:fixed; top:var(--header-h); left:0; width:var(--sidebar-w);
      height:calc(100vh - var(--header-h)); overflow-y:auto; overflow-x:hidden;
      background:#0b3a7a;
    }
    
    /* Desktop Layout */
    @media (min-width: 768px) {
      .content-wrapper, .right-side, .main-footer{ margin-left:var(--sidebar-w); }
    }
    
    .content-wrapper{ padding-top:var(--header-h); min-height:calc(100vh - var(--header-h)); }

    /* Sidebar colores */
    .skin-neko-blue .sidebar a{ color:#dbeafe; }
    .skin-neko-blue .sidebar-menu>li>a{ border-left:3px solid transparent; }
    .skin-neko-blue .sidebar-menu>li:hover>a{ background:rgba(255,255,255,.06); color:#fff; }
    .skin-neko-blue .sidebar-menu>li.active>a{
      background:rgba(255,255,255,.12); color:#fff; border-left-color:#90caf9;
    }
    .skin-neko-blue .sidebar-menu .treeview-menu{ background:rgba(0,0,0,.12); }
    .skin-neko-blue .sidebar-menu .treeview-menu>li>a{ color:#e3f2fd; }
    .skin-neko-blue .sidebar-menu .treeview-menu>li>a:hover{ color:#fff; }

    /* Dropdown */
    .skin-neko-blue .main-header .navbar .dropdown-menu{
      border:0; box-shadow:0 10px 20px rgba(2,31,77,.15); border-radius:10px; overflow:hidden; width:280px;
    }
    .skin-neko-blue .main-header li.user-header{
      background: linear-gradient(90deg,var(--neko-primary-dark),var(--neko-primary));
      color:#fff;
    }

    /* ===== Identidad en header (nombre + rol debajo) ===== */
    .user-identity{
      display:flex; flex-direction:column; line-height:1.15; margin-left:8px;
    }
    .user-identity .name{
      font-weight:600; font-size:1.05rem; color:#fff;   /* + */
      max-width:260px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    .user-identity .role{
      font-size:.85rem; color:#eaf2ff;                  /* + */
      max-width:260px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    }
    @media (max-width:640px){
      .user-identity .name{ font-size:1rem; max-width:180px; }
      .user-identity .role{ font-size:.8rem; max-width:180px; }
    }
    .user-anchor{ display:flex; align-items:center; gap:.55rem; }

    /* ==================== RESPONSIVE MOBILE (AdminLTE Compatible) ==================== */
    @media (max-width: 768px) {
      /* Mejoras para touch en sidebar */
      .sidebar-menu > li > a {
        padding: 14px 12px;
        font-size: 0.95rem;
      }
      .sidebar-menu .treeview-menu > li > a {
        padding: 12px 12px 12px 30px;
      }

      /* Dropdown de usuario más ancho */
      .skin-neko-blue .main-header .navbar .dropdown-menu {
        width: calc(100vw - 20px);
        max-width: 320px;
        right: 10px;
        left: auto;
      }

      /* Botón toggle más grande */
      .sidebar-toggle {
        padding: 15px;
      }
    }

    @media (max-width: 480px) {
      /* Usuario compacto */
      .user-image {
        width: 28px;
        height: 28px;
      }
      
      /* Sidebar más ancho en móviles pequeños */
      .sidebar-collapse .main-sidebar {
        width: 85vw;
        max-width: 280px;
      }
    }
  </style>
  <!-- Custom Styles -->
  <style>
    /* Highlighting for modified articles */
    table.dataTable tbody tr.table-success {
      background-color: #d1e7dd !important;
    }
    table.dataTable tbody tr.table-success > td {
      background-color: #d1e7dd !important;
      transition: background-color 0.5s ease;
    }
  </style>
</head>

<body class="hold-transition skin-neko-blue sidebar-mini">
<div class="wrapper">

<!-- ===== ÚNICO HEADER ===== -->
<header class="main-header">
  <a href="escritorio.php" class="logo" title="Inicio">
    <span class="logo-mini"><i class="fa fa-wrench"></i></span>
    <span class="logo-lg"><i class="fa fa-industry" style="margin-right:6px;"></i>Ferretería <strong>Neko</strong></span>
  </a>

  <nav class="navbar navbar-static-top" role="navigation" aria-label="Barra principal">
    <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button" aria-label="Mostrar/ocultar menú">
      <span class="sr-only">Navegación</span>
    </a>

    <div class="navbar-custom-menu">
      <ul class="nav navbar-nav">

        <!-- Soporte -->
        <li>
          <a href="https://api.whatsapp.com/send?phone=51940367492&text=TE%20CONTACTAS%20CON%20EL%20SCRUM%20MASTER"
             target="_blank" rel="noopener" title="Soporte vía WhatsApp">
            <i class="fa fa-whatsapp"></i><span class="hidden-xs"> Soporte</span>
          </a>
        </li>

        <!-- Usuario: nombre + rol debajo (siempre visible) -->
        <li class="dropdown user user-menu">
          <a href="#" class="dropdown-toggle user-anchor" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <img src="../files/usuarios/<?= $sesImagen ?>" class="user-image" alt="Foto de usuario">
            <span class="hidden-xs user-identity">
              <span class="name"><?= $sesNombre ?></span>
              <span class="role"><?= $sesRol ?></span>
            </span>
          </a>

          <!-- Dropdown -->
          <ul class="dropdown-menu">
            <li class="user-header">
              <img src="../files/usuarios/<?= $sesImagen ?>" class="img-circle" alt="Foto de usuario">
              <p style="margin-top:6px;">
                <strong><?= $sesNombre ?></strong><br>
                <small>Rol: <?= $sesRol ?></small>
              </p>
            </li>
            <li class="user-footer" style="display:flex;gap:10px;justify-content:center;">
              <a href="../ajax/usuario.php?op=salir" class="btn btn-default btn-flat" style="width:80%;">
                <i class="fa fa-sign-out"></i> Cerrar sesión
              </a>
            </li>
          </ul>
        </li>

      </ul>
    </div>
  </nav>
</header>

<!-- ===== SIDEBAR ===== -->
<aside class="main-sidebar">
  <section class="sidebar" style="padding-top:0 !important; margin-top:0 !important;">
    <ul class="sidebar-menu" style="margin-top:0 !important;">

      <?php if (flag('escritorio')): ?>
        <li id="mEscritorio"><a href="escritorio.php"><i class="fa fa-tasks"></i> <span>Escritorio</span></a></li>
      <?php endif; ?>

      <?php if (flag('almacen')): ?>
        <li id="mAlmacen" class="treeview">
          <a href="#"><i class="fa fa-laptop"></i><span>Almacén</span><i class="fa fa-angle-left pull-right"></i></a>
          <ul class="treeview-menu">
            <li id="lArticulos"><a href="articulo.php"><i class="fa fa-circle-o"></i> Artículos</a></li>
            <li id="lCategorias"><a href="categoria.php"><i class="fa fa-circle-o"></i> Categorías</a></li>
            <li id="lMarcas"><a href="marca.php"><i class="fa fa-circle-o"></i> Marcas</a></li>
            <li id="lHistorial"><a href="historial_precios.php"><i class="fa fa-tags"></i> Historial Precios</a></li>
          </ul>
        </li>
      <?php endif; ?>

      <?php if (flag('compras')): ?>
        <li id="mCompras" class="treeview">
          <a href="#"><i class="fa fa-th"></i><span>Compras</span><i class="fa fa-angle-left pull-right"></i></a>
          <ul class="treeview-menu">
            <li id="lIngresos"><a href="ingreso.php"><i class="fa fa-circle-o"></i> Ingresos</a></li>
            <li id="lProveedores"><a href="proveedor.php"><i class="fa fa-circle-o"></i> Proveedores</a></li>
          </ul>
        </li>
      <?php endif; ?>

      <?php if (flag('ventas')): ?>
        <li id="mVentas" class="treeview">
          <a href="#"><i class="fa fa-shopping-cart"></i><span>Ventas</span><i class="fa fa-angle-left pull-right"></i></a>
          <ul class="treeview-menu">
            <li id="lVentas"><a href="venta.php"><i class="fa fa-circle-o"></i> Ventas</a></li>
            <li id="lClientes"><a href="cliente.php"><i class="fa fa-circle-o"></i> Clientes</a></li>
          </ul>
        </li>
      <?php endif; ?>

      <?php if (flag('ventas')): ?>
        <li id="mCaja">
          <a href="caja.php"><i class="fa fa-money"></i> <span>Caja</span></a>
        </li>
      <?php endif; ?>

      <?php if (flag('acceso')): ?>
        <li id="mAcceso" class="treeview">
          <a href="#"><i class="fa fa-folder"></i> <span>Acceso</span><i class="fa fa-angle-left pull-right"></i></a>
          <ul class="treeview-menu">
            <li id="lUsuarios"><a href="usuario.php"><i class="fa fa-circle-o"></i> Usuarios</a></li>
            <li id="lRol"><a href="rol.php"><i class="fa fa-circle-o"></i> Roles Usuario</a></li>
          </ul>
        </li>
      <?php endif; ?>

      <?php if (flag('consultac')): ?>
        <li id="mConsultaC" class="treeview">
          <a href="#"><i class="fa fa-bar-chart"></i><span>Consulta Compras</span><i class="fa fa-angle-left pull-right"></i></a>
          <ul class="treeview-menu">
            <li id="lConsulasC"><a href="comprasfecha.php"><i class="fa fa-circle-o"></i> Consulta Compras</a></li>
          </ul>
        </li>
      <?php endif; ?>

      <?php if (flag('consultav')): ?>
        <li id="mConsultaV" class="treeview">
          <a href="#"><i class="fa fa-bar-chart"></i><span>Consulta Ventas</span><i class="fa fa-angle-left pull-right"></i></a>
          <ul class="treeview-menu">
            <li id="lConsulasV"><a href="ventasfechacliente.php"><i class="fa fa-circle-o"></i> Consulta Ventas</a></li>
          </ul>
        </li>
      <?php endif; ?>

    </ul>
  </section>
</aside>
