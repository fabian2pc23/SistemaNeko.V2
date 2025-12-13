<?php

/** @var array $producto */
$stockClass = $producto['stock'] > 10 ? 'text-success' : ($producto['stock'] > 0 ? 'text-warning' : 'text-danger');
$stockText = $producto['stock'] > 10 ? 'En stock' : ($producto['stock'] > 0 ? 'Pocas unidades' : 'Agotado');
?>
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/">Inicio</a></li>
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/tienda">Tienda</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($producto['nombre']) ?></li>
    </ol>
</nav>

<div class="row g-4">
    <!-- Imagen del Producto -->
    <div class="col-lg-5">
        <div class="product-detail-image">
            <div class="image-main-container">
                <img src="<?= getProductImageUrl($producto['imagen'] ?? null) ?>"
                    alt="<?= htmlspecialchars($producto['nombre']) ?>"
                    class="img-fluid rounded-4 shadow-lg"
                    style="width: 100%; max-height: 450px; object-fit: contain; background: linear-gradient(145deg, rgba(255,255,255,0.05), rgba(0,0,0,0.1)); padding: 1rem;"
                    onerror="this.src='<?= BASE_URL ?>/assets/img/placeholder.jpg'">
            </div>

            <!-- Badges de estado -->
            <div class="product-badges mt-3 d-flex gap-2 flex-wrap">
                <span class="badge bg-primary rounded-pill px-3 py-2">
                    <i class="bi bi-tag-fill me-1"></i><?= htmlspecialchars($producto['categoria']) ?>
                </span>
                <span class="badge <?= str_replace('text-', 'bg-', $stockClass) ?> rounded-pill px-3 py-2">
                    <i class="bi bi-box-seam me-1"></i><?= $stockText ?> (<?= $producto['stock'] ?>)
                </span>
            </div>
        </div>
    </div>

    <!-- Información del Producto -->
    <div class="col-lg-7">
        <div class="product-detail-info">
            <span class="text-uppercase text-muted small fw-semibold letter-spacing-1">
                <?= htmlspecialchars($producto['categoria']) ?>
            </span>

            <h1 class="h2 fw-bold mt-2 mb-3">
                <?= htmlspecialchars($producto['nombre']) ?>
            </h1>

            <!-- Precio destacado -->
            <div class="price-box mb-4 p-3 rounded-3" style="background: linear-gradient(135deg, rgba(37, 99, 235, 0.1), rgba(37, 99, 235, 0.05));">
                <div class="d-flex align-items-baseline gap-2">
                    <span class="h1 fw-bold text-primary mb-0">
                        S/ <?= number_format((float)$producto['precio_venta'], 2) ?>
                    </span>
                    <span class="text-muted small">IGV incluido</span>
                </div>
            </div>

            <!-- Descripción -->
            <div class="product-description mb-4">
                <h6 class="fw-semibold mb-2"><i class="bi bi-file-text me-2"></i>Descripción</h6>
                <p class="text-muted">
                    <?= htmlspecialchars($producto['descripcion'] ?? 'Producto de alta calidad disponible en nuestra tienda. Consulte características adicionales.') ?>
                </p>
            </div>

            <!-- Formulario de compra -->
            <form class="add-to-cart-form mb-4" method="post" action="<?= BASE_URL ?>/carrito/agregar">
                <input type="hidden" name="id" value="<?= (int)$producto['idarticulo'] ?>">

                <div class="row g-3 align-items-end">
                    <div class="col-auto">
                        <label for="cantidad" class="form-label small fw-semibold">Cantidad</label>
                        <div class="input-group" style="width: 130px;">
                            <button type="button" class="btn btn-outline-secondary" onclick="decrementQty()">
                                <i class="bi bi-dash"></i>
                            </button>
                            <input type="number" name="cantidad" id="cantidad" value="1" min="1"
                                max="<?= $producto['stock'] ?>" class="form-control text-center">
                            <button type="button" class="btn btn-outline-secondary" onclick="incrementQty()">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col">
                        <button class="btn btn-primary btn-lg w-100 rounded-pill fw-semibold" type="submit"
                            <?= $producto['stock'] == 0 ? 'disabled' : '' ?>>
                            <i class="bi bi-cart-plus me-2"></i>
                            <?= $producto['stock'] == 0 ? 'Sin Stock' : 'Añadir al Carrito' ?>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Información adicional -->
            <div class="product-features">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="feature-item d-flex align-items-center gap-2 p-2 rounded">
                            <i class="bi bi-truck text-primary fs-5"></i>
                            <div>
                                <small class="fw-semibold d-block">Envío rápido</small>
                                <small class="text-muted">A todo Lambayeque</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-item d-flex align-items-center gap-2 p-2 rounded">
                            <i class="bi bi-shield-check text-success fs-5"></i>
                            <div>
                                <small class="fw-semibold d-block">Garantía</small>
                                <small class="text-muted">Productos originales</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-item d-flex align-items-center gap-2 p-2 rounded">
                            <i class="bi bi-credit-card text-info fs-5"></i>
                            <div>
                                <small class="fw-semibold d-block">Pago seguro</small>
                                <small class="text-muted">Yape, tarjeta y más</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="feature-item d-flex align-items-center gap-2 p-2 rounded">
                            <i class="bi bi-arrow-counterclockwise text-warning fs-5"></i>
                            <div>
                                <small class="fw-semibold d-block">Devoluciones</small>
                                <small class="text-muted">30 días para cambios</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function incrementQty() {
        const input = document.getElementById('cantidad');
        const max = parseInt(input.max) || 999;
        if (parseInt(input.value) < max) {
            input.value = parseInt(input.value) + 1;
        }
    }

    function decrementQty() {
        const input = document.getElementById('cantidad');
        if (parseInt(input.value) > 1) {
            input.value = parseInt(input.value) - 1;
        }
    }
</script>