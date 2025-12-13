<?php

/** @var string|null $error */
require_once __DIR__ . '/../../../config/OAuthConfig.php';
$googleConfigured = \App\Config\OAuthConfig::isGoogleConfigured();
$facebookConfigured = \App\Config\OAuthConfig::isFacebookConfigured();
?>

<div class="auth-container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="auth-card">
                <!-- Logo y título -->
                <div class="auth-header text-center mb-4">
                    <div class="auth-logo mb-3">
                        <i class="bi bi-person-plus"></i>
                    </div>
                    <h2 class="h4 mb-1">Crear Cuenta</h2>
                    <p class="text-muted small">Únete a NEKO SAC Store</p>
                </div>

                <!-- Mensaje de error -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Botones de OAuth -->
                <div class="oauth-buttons mb-4">
                    <a href="<?= BASE_URL ?>/auth/google" class="btn btn-google w-100 mb-2 <?= !$googleConfigured ? 'disabled' : '' ?>">
                        <svg class="me-2" width="18" height="18" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                        </svg>
                        Registrarse con Google
                    </a>

                    <a href="<?= BASE_URL ?>/auth/facebook" class="btn btn-facebook w-100 <?= !$facebookConfigured ? 'disabled' : '' ?>">
                        <i class="bi bi-facebook me-2"></i>
                        Registrarse con Facebook
                    </a>
                </div>

                <!-- Separador -->
                <div class="auth-separator">
                    <span>o regístrate con email</span>
                </div>

                <!-- Formulario de registro -->
                <form method="post" action="<?= BASE_URL ?>/registro" class="auth-form" id="registroForm">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nombre" name="nombre"
                                placeholder="Tu nombre" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="apellido" class="form-label">Apellido</label>
                            <input type="text" class="form-control" id="apellido" name="apellido"
                                placeholder="Tu apellido">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email"
                                placeholder="tu@email.com" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-phone"></i></span>
                            <input type="tel" class="form-control" id="telefono" name="telefono"
                                placeholder="987 654 321">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password"
                                placeholder="Mínimo 6 caracteres" required minlength="6">
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password', 'toggleIcon1')">
                                <i class="bi bi-eye" id="toggleIcon1"></i>
                            </button>
                        </div>
                        <div class="password-strength mt-2" id="passwordStrength"></div>
                    </div>

                    <div class="mb-3">
                        <label for="password_confirm" class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                                placeholder="Repite tu contraseña" required>
                            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password_confirm', 'toggleIcon2')">
                                <i class="bi bi-eye" id="toggleIcon2"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback" id="passwordMatchError">Las contraseñas no coinciden</div>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label small" for="terms">
                                Acepto los <a href="#">Términos y Condiciones</a> y la
                                <a href="#">Política de Privacidad</a>
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-lg rounded-pill">
                        <i class="bi bi-person-plus me-2"></i>Crear Cuenta
                    </button>
                </form>

                <!-- Link a login -->
                <div class="auth-footer text-center mt-4">
                    <p class="mb-0">
                        ¿Ya tienes cuenta?
                        <a href="<?= BASE_URL ?>/login" class="fw-semibold">Inicia sesión</a>
                    </p>
                </div>
            </div>

            <!-- Volver a la tienda -->
            <div class="text-center mt-3">
                <a href="<?= BASE_URL ?>/tienda" class="text-muted small">
                    <i class="bi bi-arrow-left me-1"></i>Volver a la tienda
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .auth-container {
        min-height: 80vh;
        display: flex;
        align-items: center;
        padding: 2rem 0;
    }

    .auth-card {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.05), rgba(255, 255, 255, 0.02));
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 2rem;
        backdrop-filter: blur(10px);
    }

    .auth-logo {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #10b981, #059669);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        font-size: 2.5rem;
        color: white;
    }

    .oauth-buttons .btn {
        padding: 0.75rem 1rem;
        font-weight: 500;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
    }

    .btn-google {
        background: white;
        color: #333;
        border: 1px solid #ddd;
    }

    .btn-google:hover {
        background: #f8f9fa;
        border-color: #ccc;
        color: #333;
    }

    .btn-facebook {
        background: #1877F2;
        color: white;
        border: none;
    }

    .btn-facebook:hover {
        background: #166FE5;
        color: white;
    }

    .auth-separator {
        display: flex;
        align-items: center;
        margin: 1.5rem 0;
    }

    .auth-separator::before,
    .auth-separator::after {
        content: '';
        flex: 1;
        height: 1px;
        background: rgba(255, 255, 255, 0.1);
    }

    .auth-separator span {
        padding: 0 1rem;
        color: rgba(255, 255, 255, 0.5);
        font-size: 0.85rem;
    }

    .auth-form .input-group-text {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.5);
    }

    .auth-form .form-control {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.1);
        color: white;
    }

    .auth-form .form-control:focus {
        background: rgba(255, 255, 255, 0.08);
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        color: white;
    }

    .auth-form .form-control::placeholder {
        color: rgba(255, 255, 255, 0.3);
    }

    .password-strength {
        height: 4px;
        border-radius: 2px;
        background: rgba(255, 255, 255, 0.1);
        overflow: hidden;
    }

    .password-strength-bar {
        height: 100%;
        transition: width 0.3s ease, background 0.3s ease;
    }
</style>

<script>
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    }

    // Validar contraseñas
    document.getElementById('password_confirm').addEventListener('input', function() {
        const password = document.getElementById('password').value;
        const confirm = this.value;
        const error = document.getElementById('passwordMatchError');

        if (confirm && password !== confirm) {
            this.classList.add('is-invalid');
            error.style.display = 'block';
        } else {
            this.classList.remove('is-invalid');
            error.style.display = 'none';
        }
    });

    // Indicador de fuerza de contraseña
    document.getElementById('password').addEventListener('input', function() {
        const password = this.value;
        const strengthContainer = document.getElementById('passwordStrength');

        let strength = 0;
        if (password.length >= 6) strength++;
        if (password.length >= 10) strength++;
        if (/[A-Z]/.test(password)) strength++;
        if (/[0-9]/.test(password)) strength++;
        if (/[^A-Za-z0-9]/.test(password)) strength++;

        const colors = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#10b981'];
        const width = (strength / 5) * 100;

        strengthContainer.innerHTML = `<div class="password-strength-bar" style="width: ${width}%; background: ${colors[strength - 1] || '#ef4444'}"></div>`;
    });
</script>