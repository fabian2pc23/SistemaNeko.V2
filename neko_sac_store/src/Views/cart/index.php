<?php

/** @var array $items */
/** @var float $total */

$igv = $total * 0.18;
$totalConIgv = $total + $igv;
?>

<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Inicio</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/tienda">Tienda</a></li>
        <li class="breadcrumb-item active">Carrito</li>
    </ol>
</nav>

<div class="cart-header mb-4">
    <h1 class="h3 mb-1">
        <i class="bi bi-cart3 me-2"></i>Mi Carrito
    </h1>
    <p class="text-muted mb-0">
        <?= count($items) ?> producto<?= count($items) != 1 ? 's' : '' ?> en tu carrito
    </p>
</div>

<?php if (empty($items)): ?>
    <div class="empty-cart-container text-center py-5">
        <div class="empty-cart-icon mb-4">
            <i class="bi bi-cart-x" style="font-size: 5rem; color: var(--bs-secondary);"></i>
        </div>
        <h3 class="mb-3">Tu carrito está vacío</h3>
        <p class="text-muted mb-4">
            ¡Explora nuestra tienda y encuentra los mejores productos para tu vehículo!
        </p>
        <a href="<?= BASE_URL ?>/tienda" class="btn btn-primary btn-lg rounded-pill px-5">
            <i class="bi bi-shop me-2"></i>Explorar Tienda
        </a>
    </div>
<?php else: ?>
    <div class="row g-4">
        <!-- Lista de productos -->
        <div class="col-lg-8">
            <div class="cart-items">
                <?php foreach ($items as $row): ?>
                    <?php $p = $row['producto']; ?>
                    <div class="cart-item-card mb-3">
                        <div class="row g-0 align-items-center">
                            <!-- Imagen del producto -->
                            <div class="col-3 col-md-2">
                                <div class="cart-item-image">
                                    <img src="<?= getProductImageUrl($p['imagen'] ?? null) ?>"
                                        alt="<?= htmlspecialchars($p['nombre']) ?>"
                                        class="img-fluid rounded"
                                        style="width: 80px; height: 80px; object-fit: contain; background: rgba(255,255,255,0.05); padding: 5px;"
                                        onerror="this.src='<?= BASE_URL ?>/assets/img/placeholder.jpg'">
                                </div>
                            </div>

                            <!-- Info del producto -->
                            <div class="col-9 col-md-10">
                                <div class="row align-items-center">
                                    <div class="col-md-5">
                                        <h6 class="cart-item-title mb-1">
                                            <a href="<?= BASE_URL ?>/producto/<?= (int)$p['idarticulo'] ?>" class="text-decoration-none">
                                                <?= htmlspecialchars($p['nombre']) ?>
                                            </a>
                                        </h6>
                                        <span class="badge bg-secondary bg-opacity-25 text-light small">
                                            <?= htmlspecialchars($p['categoria'] ?? 'General') ?>
                                        </span>
                                    </div>

                                    <div class="col-md-2 text-center mt-2 mt-md-0">
                                        <small class="text-muted d-block d-md-none">Cantidad</small>
                                        <span class="fw-semibold"><?= (int)$row['cantidad'] ?></span>
                                    </div>

                                    <div class="col-md-2 text-md-center mt-2 mt-md-0">
                                        <small class="text-muted d-block d-md-none">Precio unit.</small>
                                        <span>S/ <?= number_format((float)$p['precio_venta'], 2) ?></span>
                                    </div>

                                    <div class="col-md-2 text-md-end mt-2 mt-md-0">
                                        <small class="text-muted d-block d-md-none">Subtotal</small>
                                        <span class="fw-bold text-primary">S/ <?= number_format((float)$row['subtotal'], 2) ?></span>
                                    </div>

                                    <div class="col-md-1 text-end mt-2 mt-md-0">
                                        <form method="post" action="<?= BASE_URL ?>/carrito/eliminar" class="d-inline">
                                            <input type="hidden" name="id" value="<?= (int)$p['idarticulo'] ?>">
                                            <button class="btn btn-sm btn-outline-danger rounded-circle" type="submit" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Continuar comprando -->
            <div class="mt-4">
                <a href="<?= BASE_URL ?>/tienda" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Seguir comprando
                </a>
            </div>
        </div>

        <!-- Resumen del pedido -->
        <div class="col-lg-4">
            <div class="cart-summary sticky-top" style="top: 100px;">
                <h5 class="mb-4">
                    <i class="bi bi-receipt me-2"></i>Resumen del Pedido
                </h5>

                <div class="summary-details">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal (<?= count($items) ?> productos)</span>
                        <span>S/ <?= number_format($total, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">IGV (18%)</span>
                        <span>S/ <?= number_format($igv, 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Envío</span>
                        <span class="text-success"><i class="bi bi-truck me-1"></i>Por calcular</span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-4">
                        <span class="h5 mb-0 fw-bold">Total</span>
                        <span class="h5 mb-0 fw-bold text-primary">S/ <?= number_format($totalConIgv, 2) ?></span>
                    </div>
                </div>

                <a href="<?= BASE_URL ?>/checkout" class="btn btn-primary btn-lg w-100 rounded-pill fw-semibold">
                    <i class="bi bi-lock-fill me-2"></i>Finalizar Compra
                </a>

                <div class="payment-badges mt-4 text-center">
                    <small class="text-muted d-block mb-2">Métodos de pago aceptados</small>
                    <div class="d-flex justify-content-center gap-3">
                        <span class="badge bg-success bg-opacity-25 text-success px-3 py-2">
                            <i class="bi bi-phone me-1"></i>Yape
                        </span>
                        <span class="badge bg-primary bg-opacity-25 text-primary px-3 py-2">
                            <i class="bi bi-credit-card me-1"></i>Tarjeta
                        </span>
                    </div>
                </div>

                <div class="security-badges mt-4">
                    <div class="d-flex align-items-center gap-2 text-muted small mb-2">
                        <i class="bi bi-shield-check text-success"></i>
                        <span>Pago 100% seguro</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 text-muted small">
                        <i class="bi bi-arrow-counterclockwise text-info"></i>
                        <span>Devolución en 30 días</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
    .cart-item-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        padding: 1rem;
        transition: all 0.3s ease;
    }

    .cart-item-card:hover {
        background: rgba(255, 255, 255, 0.06);
        border-color: rgba(255, 255, 255, 0.15);
    }

    .cart-item-title a {
        color: inherit;
    }

    .cart-item-title a:hover {
        color: var(--bs-primary);
    }

    .cart-summary {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 1.5rem;
    }

    .empty-cart-container {
        background: rgba(255, 255, 255, 0.02);
        border-radius: 16px;
        padding: 3rem;
    }

    @media (max-width: 768px) {
        .cart-item-image img {
            width: 60px !important;
            height: 60px !important;
        }
    }
</style>