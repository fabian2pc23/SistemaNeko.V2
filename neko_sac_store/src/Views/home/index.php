<?php

/** @var array $destacados */
/** @var array $categorias */
?>
<div class="row g-4">
    <div class="col-lg-8">
        <!-- CARRUSEL DE PRODUCTOS DESTACADOS -->
        <section class="hero-carousel-section mb-4">
            <div class="carousel-wrapper">
                <div class="swiper heroProductsSwiper">
                    <div class="swiper-wrapper">
                        <?php foreach ($destacados as $index => $art): ?>
                            <div class="swiper-slide">
                                <div class="hero-carousel-card">
                                    <div class="row align-items-center h-100">
                                        <div class="col-md-6">
                                            <div class="carousel-product-image">
                                                <img src="<?= getProductImageUrl($art['imagen'] ?? null) ?>"
                                                    alt="<?= htmlspecialchars($art['nombre']) ?>"
                                                    class="img-fluid"
                                                    onerror="this.src='<?= BASE_URL ?>/assets/img/placeholder.jpg'">
                                                <div class="carousel-badge">
                                                    <i class="bi bi-star-fill"></i> Destacado
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="carousel-product-info">
                                                <span class="carousel-category">
                                                    <i class="bi bi-tag-fill"></i> <?= htmlspecialchars($art['categoria']) ?>
                                                </span>
                                                <h2 class="carousel-product-title">
                                                    <?= htmlspecialchars($art['nombre']) ?>
                                                </h2>
                                                <p class="carousel-product-description">
                                                    Producto de alta calidad disponible en nuestro inventario.
                                                    Stock en tiempo real sincronizado con sistema NEKO SAC.
                                                </p>
                                                <div class="carousel-price-section">
                                                    <div class="carousel-price">
                                                        <span class="price-label">Precio:</span>
                                                        <span class="price-value">S/ <?= number_format((float)$art['precio_venta'], 2) ?></span>
                                                    </div>
                                                </div>
                                                <div class="carousel-actions">
                                                    <a href="<?= BASE_URL ?>/producto/<?= (int)$art['idarticulo'] ?>"
                                                        class="btn btn-primary btn-lg carousel-btn-primary">
                                                        <i class="bi bi-cart-plus-fill"></i> Comprar ahora
                                                    </a>
                                                    <a href="<?= BASE_URL ?>/producto/<?= (int)$art['idarticulo'] ?>"
                                                        class="btn btn-outline-light btn-lg carousel-btn-outline">
                                                        <i class="bi bi-info-circle"></i> Ver detalles
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Controles del carrusel -->
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-pagination"></div>
                </div>

                <!-- Contador de productos -->
                <div class="carousel-counter">
                    <i class="bi bi-box-seam"></i>
                    <span><?= count($destacados) ?> productos destacados</span>
                </div>
            </div>
        </section>

        <!-- BANNER INFORMATIVO -->
        <section class="info-banner mb-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="bi bi-lightning-charge-fill"></i>
                        </div>
                        <div class="info-content">
                            <h5>Stock en tiempo real</h5>
                            <p>Inventario sincronizado con sistema NEKO SAC</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="bi bi-truck"></i>
                        </div>
                        <div class="info-content">
                            <h5>Entrega rápida</h5>
                            <p>Envíos a todo Lambayeque, Perú</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="info-card">
                        <div class="info-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="info-content">
                            <h5>Productos garantizados</h5>
                            <p>Calidad certificada en autopartes</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="destacados" class="mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="section-title mb-1">Más productos destacados</h2>
                    <p class="text-muted small mb-0">Explora nuestra selección completa</p>
                </div>
                <a href="<?= BASE_URL ?>/tienda" class="btn btn-sm btn-outline-primary">
                    Ver catálogo completo <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="row g-3">
                <?php foreach ($destacados as $art): ?>
                    <div class="col-6 col-md-4 col-xl-3">
                        <div class="product-card">
                            <div class="product-card-badge">
                                <i class="bi bi-star-fill"></i>
                            </div>
                            <img src="<?= getProductImageUrl($art['imagen'] ?? null) ?>"
                                alt="<?= htmlspecialchars($art['nombre']) ?>"
                                onerror="this.src='<?= BASE_URL ?>/assets/img/placeholder.jpg'">
                            <div class="product-card-body">
                                <div class="product-card-category">
                                    <i class="bi bi-tag"></i> <?= htmlspecialchars($art['categoria']) ?>
                                </div>
                                <div class="product-card-title">
                                    <?= htmlspecialchars($art['nombre']) ?>
                                </div>
                                <div class="product-card-price">
                                    S/ <?= number_format((float)$art['precio_venta'], 2) ?>
                                </div>
                                <div class="product-card-actions">
                                    <a href="<?= BASE_URL ?>/producto/<?= (int)$art['idarticulo'] ?>"
                                        class="btn btn-sm btn-primary w-100">
                                        <i class="bi bi-cart-plus"></i> Ver producto
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($destacados)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i>
                            No hay productos activos en la base de datos.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <div class="col-lg-4">
        <aside class="category-sidebar">
            <h6>Categorías</h6>
            <ul class="list-group list-group-flush">
                <?php foreach ($categorias as $cat): ?>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <a href="<?= BASE_URL ?>/tienda?categoria=<?= (int)$cat['idcategoria'] ?>"
                            class="link-dark text-decoration-none">
                            <?= htmlspecialchars($cat['nombre']) ?>
                        </a>
                        <i class="bi bi-chevron-right small text-muted"></i>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="small-muted mt-3 mb-0">
                Este panel lateral replica la experiencia de filtro por categoría típica de tiendas como Renusa.
            </p>
        </aside>
    </div>
</div>