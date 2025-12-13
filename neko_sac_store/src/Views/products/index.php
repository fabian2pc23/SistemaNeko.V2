<?php

/** @var array $categorias */
/** @var array $paginado */
/** @var int|null $filtroCat */
/** @var string|null $filtroQ */

$items   = $paginado['items'];
$page    = $paginado['page'];
$pages   = $paginado['pages'];
$total   = $paginado['total'];
$perPage = $paginado['per_page'];

function buildUrl(array $params): string
{
    $base = BASE_URL . '/tienda';
    $merged = array_merge($_GET, $params);
    return $base . '?' . http_build_query($merged);
}
?>
<div class="row g-4">
    <div class="col-md-3">
        <aside class="category-sidebar mb-3 mb-md-0">
            <h6>Categorías</h6>
            <ul class="list-group list-group-flush">
                <li class="list-group-item px-0">
                    <a href="<?= buildUrl(['categoria' => null, 'page' => 1]) ?>"
                        class="link-dark text-decoration-none <?= $filtroCat ? '' : 'fw-semibold' ?>">
                        Todas las categorías
                    </a>
                </li>
                <?php foreach ($categorias as $cat): ?>
                    <li class="list-group-item px-0">
                        <a href="<?= buildUrl(['categoria' => (int)$cat['idcategoria'], 'page' => 1]) ?>"
                            class="link-dark text-decoration-none <?= ($filtroCat == $cat['idcategoria']) ? 'fw-semibold' : '' ?>">
                            <?= htmlspecialchars($cat['nombre']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <hr>
            <p class="small-muted mb-1">Búsqueda actual:</p>
            <p class="small mb-0">
                <?= $filtroQ ? htmlspecialchars($filtroQ) : 'Sin filtro de texto'; ?>
            </p>
        </aside>
    </div>

    <div class="col-md-9">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h1 class="h5 mb-0">Tienda NEKO SAC</h1>
                <p class="small-muted mb-0">
                    Mostrando <?= count($items) ?> de <?= $total ?> productos.
                </p>
            </div>

            <form class="d-flex gap-2" method="get" action="<?= BASE_URL ?>/tienda">
                <?php if ($filtroCat): ?>
                    <input type="hidden" name="categoria" value="<?= (int)$filtroCat ?>">
                <?php endif; ?>
                <input type="text" name="q" class="form-control form-control-sm"
                    placeholder="Buscar en catálogo"
                    value="<?= htmlspecialchars($filtroQ ?? '') ?>">
                <button class="btn btn-sm btn-outline-primary" type="submit">
                    <i class="bi bi-search"></i>
                </button>
            </form>
        </div>

        <div class="row g-3">
            <?php foreach ($items as $art): ?>
                <?php
                // Image Logic - Usar imágenes de la aplicación principal
                $imgName = !empty($art['imagen']) ? $art['imagen'] : '';

                // Ruta a las imágenes de la aplicación principal (SistemaNeko.V2/files/articulos/)
                if (!empty($imgName)) {
                    // Ruta relativa desde la tienda hacia files/articulos de la app principal
                    $imgPath = str_replace('/neko_sac_store/public', '', BASE_URL) . '/files/articulos/' . $imgName;
                } else {
                    $imgPath = BASE_URL . '/assets/img/placeholder.jpg';
                }
                ?>
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="product-card glow-border h-100">
                        <!-- Badge (Simulated for demo, or logic based) -->
                        <?php if ($art['stock'] < 5 && $art['stock'] > 0): ?>
                            <div class="product-card-badge bg-warning text-dark" title="Poco stock">
                                <i class="bi bi-exclamation-lg"></i>
                            </div>
                        <?php elseif ($art['stock'] == 0): ?>
                            <div class="product-card-badge bg-danger" title="Agotado">
                                <i class="bi bi-x-lg"></i>
                            </div>
                        <?php endif; ?>

                        <div class="position-relative overflow-hidden">
                            <img src="<?= $imgPath ?>"
                                alt="<?= htmlspecialchars($art['nombre']) ?>"
                                class="card-img-top"
                                style="height: 220px; object-fit: contain; padding: 1rem; background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);"
                                onerror="this.src='<?= BASE_URL ?>/assets/img/placeholder.jpg'">
                        </div>

                        <div class="product-card-body d-flex flex-column">
                            <div class="product-card-category text-uppercase small text-muted mb-1">
                                <?= htmlspecialchars($art['categoria'] ?? 'General') ?>
                            </div>
                            <h3 class="product-card-title h6 fw-bold mb-2 text-truncate-2" style="min-height: 2.5em;">
                                <?= htmlspecialchars($art['nombre']) ?>
                            </h3>

                            <div class="mt-auto">
                                <div class="product-card-price h5 fw-bold text-primary mb-3">
                                    S/ <?= number_format((float)$art['precio_venta'], 2) ?>
                                </div>

                                <div class="d-grid gap-2">
                                    <form method="post" action="<?= BASE_URL ?>/carrito/agregar">
                                        <input type="hidden" name="id" value="<?= (int)$art['idarticulo'] ?>">
                                        <button class="btn btn-primary w-100 rounded-pill btn-sm fw-semibold" type="submit" <?= $art['stock'] == 0 ? 'disabled' : '' ?>>
                                            <i class="bi bi-bag-plus me-1"></i> Agregar
                                        </button>
                                    </form>
                                    <a href="<?= BASE_URL ?>/producto/<?= (int)$art['idarticulo'] ?>"
                                        class="btn btn-outline-light w-100 rounded-pill btn-sm opacity-75 hover-opacity-100">
                                        Ver detalles
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($items)): ?>
                <p class="small-muted">No se encontraron productos con los filtros seleccionados.</p>
            <?php endif; ?>
        </div>

        <?php if ($pages > 1): ?>
            <nav class="mt-3">
                <ul class="pagination pagination-sm">
                    <?php for ($p = 1; $p <= $pages; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= buildUrl(['page' => $p]) ?>">
                                <?= $p ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>