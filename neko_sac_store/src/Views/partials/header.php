<?php
$cartCount = 0;
if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    $cartCount = array_sum($_SESSION['cart']);
}

$isLoggedIn = isset($_SESSION['cliente']) && !empty($_SESSION['cliente']['id']);
$clienteNombre = $isLoggedIn ? ($_SESSION['cliente']['nombre'] ?? 'Usuario') : '';
$clienteAvatar = $isLoggedIn ? ($_SESSION['cliente']['avatar'] ?? null) : null;
?>
<div class="topbar py-1">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="small">
            NEKO SAC · Especialistas en frenos, embragues y autopartes.
        </div>
        <div class="d-flex gap-3 small align-items-center">
            <span><i class="bi bi-telephone"></i> Ventas: +51 999 999 999</span>
            <span><i class="bi bi-geo-alt"></i> Lambayeque, Perú</span>
            <?php if ($isLoggedIn): ?>
                <span class="text-success"><i class="bi bi-person-check"></i> Hola, <?= htmlspecialchars($clienteNombre) ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>

<nav class="navbar navbar-expand-lg navbar-neko sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>/">
            <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Neko SAC Logo" style="height: 40px; width: auto; object-fit: contain;"
                onerror="this.style.display='none'">
            <span class="fw-bold text-primary">NEKO SAC</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarNeko" aria-controls="navbarNeko"
            aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNeko">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= BASE_URL ?>/tienda">Tienda</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Ofertas</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Sucursales</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Contacto</a>
                </li>
            </ul>

            <form class="d-flex me-3" role="search" method="get" action="<?= BASE_URL ?>/tienda" data-search-form>
                <input class="form-control form-control-sm me-2" type="search"
                    placeholder="Buscar pastillas, discos, tambores..." aria-label="Buscar"
                    name="q">
                <button class="btn btn-sm btn-outline-primary" type="submit">
                    <i class="bi bi-search"></i>
                </button>
            </form>

            <div class="d-flex align-items-center gap-2">
                <button class="nav-icon-btn" id="themeToggle" type="button" title="Cambiar tema">
                    <i class="bi bi-moon-stars fs-5"></i>
                </button>

                <!-- Menú de usuario -->
                <?php if ($isLoggedIn): ?>
                    <div class="dropdown">
                        <button class="nav-icon-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false" title="Mi cuenta">
                            <?php if ($clienteAvatar): ?>
                                <img src="<?= htmlspecialchars($clienteAvatar) ?>" alt="Avatar"
                                    class="rounded-circle" style="width: 24px; height: 24px; object-fit: cover;">
                            <?php else: ?>
                                <i class="bi bi-person-circle fs-5"></i>
                            <?php endif; ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="dropdown-header">
                                <strong><?= htmlspecialchars($clienteNombre) ?></strong>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/mi-cuenta">
                                    <i class="bi bi-person me-2"></i>Mi Cuenta
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= BASE_URL ?>/mi-cuenta#pedidos">
                                    <i class="bi bi-bag me-2"></i>Mis Pedidos
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout">
                                    <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="nav-icon-btn" href="<?= BASE_URL ?>/login" title="Iniciar sesión">
                        <i class="bi bi-person fs-5"></i>
                    </a>
                <?php endif; ?>

                <!-- Carrito -->
                <a class="nav-icon-btn position-relative" href="<?= BASE_URL ?>/carrito" title="Carrito">
                    <i class="bi bi-cart3 fs-5"></i>
                    <span class="badge rounded-pill bg-danger cart-count <?= $cartCount == 0 ? 'd-none' : '' ?>">
                        <?= $cartCount ?>
                    </span>
                </a>
            </div>
        </div>
    </div>
</nav>