<?php

/** @var array $cliente */
/** @var string|null $success */
/** @var string|null $error */
?>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="account-sidebar">
                <div class="account-user text-center mb-4">
                    <div class="account-avatar mb-3">
                        <?php if (!empty($cliente['avatar_url'])): ?>
                            <img src="<?= htmlspecialchars($cliente['avatar_url']) ?>" alt="Avatar" class="rounded-circle">
                        <?php else: ?>
                            <div class="avatar-placeholder">
                                <?= strtoupper(substr($cliente['nombre'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h5 class="mb-1"><?= htmlspecialchars($cliente['nombre'] . ' ' . ($cliente['apellido'] ?? '')) ?></h5>
                    <p class="text-muted small mb-0"><?= htmlspecialchars($cliente['email']) ?></p>
                    <?php if ($cliente['oauth_provider'] !== 'local'): ?>
                        <span class="badge bg-info mt-2">
                            <i class="bi bi-<?= $cliente['oauth_provider'] ?>"></i>
                            <?= ucfirst($cliente['oauth_provider']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <nav class="account-nav">
                    <a href="#perfil" class="nav-link active" data-bs-toggle="pill">
                        <i class="bi bi-person me-2"></i>Mi Perfil
                    </a>
                    <a href="#pedidos" class="nav-link" data-bs-toggle="pill">
                        <i class="bi bi-bag me-2"></i>Mis Pedidos
                    </a>
                    <a href="#direcciones" class="nav-link" data-bs-toggle="pill">
                        <i class="bi bi-geo-alt me-2"></i>Direcciones
                    </a>
                    <hr>
                    <a href="<?= BASE_URL ?>/logout" class="nav-link text-danger">
                        <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                    </a>
                </nav>
            </div>
        </div>

        <!-- Contenido -->
        <div class="col-lg-9">
            <!-- Mensajes -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="tab-content">
                <!-- Perfil -->
                <div class="tab-pane fade show active" id="perfil">
                    <div class="account-card">
                        <div class="account-card-header">
                            <h5 class="mb-0"><i class="bi bi-person-gear me-2"></i>Información Personal</h5>
                        </div>
                        <div class="account-card-body">
                            <form method="post" action="<?= BASE_URL ?>/mi-cuenta/actualizar">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="nombre"
                                            value="<?= htmlspecialchars($cliente['nombre']) ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Apellido</label>
                                        <input type="text" class="form-control" name="apellido"
                                            value="<?= htmlspecialchars($cliente['apellido'] ?? '') ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" value="<?= htmlspecialchars($cliente['email']) ?>"
                                        disabled readonly>
                                    <small class="text-muted">El email no se puede cambiar</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Teléfono</label>
                                    <input type="tel" class="form-control" name="telefono"
                                        value="<?= htmlspecialchars($cliente['telefono'] ?? '') ?>"
                                        placeholder="987 654 321">
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">Dirección Principal</label>
                                    <textarea class="form-control" name="direccion" rows="2"
                                        placeholder="Tu dirección de entrega"><?= htmlspecialchars($cliente['direccion'] ?? '') ?></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check2 me-2"></i>Guardar Cambios
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Info de cuenta -->
                    <div class="account-card mt-4">
                        <div class="account-card-header">
                            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Información de la Cuenta</h5>
                        </div>
                        <div class="account-card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Fecha de registro:</strong></p>
                                    <p class="text-muted"><?= date('d/m/Y H:i', strtotime($cliente['fecha_registro'])) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-2"><strong>Último acceso:</strong></p>
                                    <p class="text-muted">
                                        <?= $cliente['ultimo_acceso'] ? date('d/m/Y H:i', strtotime($cliente['ultimo_acceso'])) : 'N/A' ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pedidos -->
                <div class="tab-pane fade" id="pedidos">
                    <div class="account-card">
                        <div class="account-card-header">
                            <h5 class="mb-0"><i class="bi bi-bag me-2"></i>Mis Pedidos</h5>
                        </div>
                        <div class="account-card-body">
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-bag-x" style="font-size: 3rem;"></i>
                                <p class="mt-3">Aún no tienes pedidos</p>
                                <a href="<?= BASE_URL ?>/tienda" class="btn btn-primary btn-sm">
                                    <i class="bi bi-shop me-2"></i>Explorar Tienda
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Direcciones -->
                <div class="tab-pane fade" id="direcciones">
                    <div class="account-card">
                        <div class="account-card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Mis Direcciones</h5>
                            <button class="btn btn-primary btn-sm">
                                <i class="bi bi-plus me-1"></i>Agregar
                            </button>
                        </div>
                        <div class="account-card-body">
                            <?php if (!empty($cliente['direccion'])): ?>
                                <div class="address-card">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <span class="badge bg-primary mb-2">Principal</span>
                                            <p class="mb-0"><?= nl2br(htmlspecialchars($cliente['direccion'])) ?></p>
                                        </div>
                                        <div>
                                            <button class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-geo-alt" style="font-size: 3rem;"></i>
                                    <p class="mt-3">No tienes direcciones guardadas</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .account-sidebar {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        padding: 1.5rem;
    }

    .account-avatar img,
    .avatar-placeholder {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
    }

    .avatar-placeholder {
        background: linear-gradient(135deg, var(--bs-primary), #6366f1);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: bold;
        color: white;
        margin: 0 auto;
    }

    .account-nav .nav-link {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        color: rgba(255, 255, 255, 0.7);
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .account-nav .nav-link:hover,
    .account-nav .nav-link.active {
        background: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .account-card {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 16px;
        overflow: hidden;
    }

    .account-card-header {
        padding: 1rem 1.5rem;
        background: rgba(255, 255, 255, 0.03);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .account-card-body {
        padding: 1.5rem;
    }

    .address-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        padding: 1rem;
    }
</style>