<?php

/** @var string|null $error */
/** @var string|null $success */
/** @var string $mode */ // 'login' o 'registro'
$mode = $mode ?? 'login';
$isLogin = $mode === 'login';

require_once __DIR__ . '/../../../config/OAuthConfig.php';
$googleConfigured = \App\Config\OAuthConfig::isGoogleConfigured();
$facebookConfigured = \App\Config\OAuthConfig::isFacebookConfigured();
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isLogin ? 'Iniciar Sesión' : 'Crear Cuenta' ?> - NEKO SAC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            color: #e2e8f0;
            overflow-x: hidden;
        }

        /* Fondo decorativo */
        .bg-glow {
            position: fixed;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        .bg-glow::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -25%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(37, 99, 235, 0.15) 0%, transparent 70%);
            filter: blur(80px);
        }

        .bg-glow::after {
            content: '';
            position: absolute;
            bottom: -50%;
            right: -25%;
            width: 75%;
            height: 75%;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.1) 0%, transparent 70%);
            filter: blur(80px);
        }

        /* Contenedor principal */
        .auth-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1000px;
            min-height: 600px;
            background: #1e293b;
            border-radius: 24px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            display: flex;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        /* Panel visual izquierdo */
        .auth-visual {
            position: relative;
            width: 50%;
            background: #0f172a;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem;
            overflow: hidden;
        }

        .auth-visual-bg {
            position: absolute;
            inset: 0;
            z-index: 0;
        }

        .auth-visual-bg img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            opacity: 0.35;
            transition: transform 0.8s ease;
        }

        .auth-container:hover .auth-visual-bg img {
            transform: scale(1.05);
        }

        .auth-visual-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, #0f172a 0%, rgba(15, 23, 42, 0.8) 50%, transparent 100%);
        }

        .auth-visual-content {
            position: relative;
            z-index: 1;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(37, 99, 235, 0.2);
            border: 1px solid rgba(37, 99, 235, 0.3);
            padding: 0.375rem 0.75rem;
            border-radius: 50px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #60a5fa;
            margin-bottom: 1.5rem;
        }

        .brand-badge i {
            animation: spin 8s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .brand-title {
            font-size: 3rem;
            font-weight: 800;
            color: white;
            margin-bottom: 1rem;
            letter-spacing: -0.02em;
        }

        .brand-title span {
            color: #2563eb;
        }

        .brand-description {
            color: #94a3b8;
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            color: #cbd5e1;
        }

        .feature-icon {
            width: 40px;
            height: 40px;
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .feature-icon i {
            font-size: 1rem;
        }

        /* Panel formulario derecho */
        .auth-form-panel {
            width: 50%;
            background: #1e293b;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-header {
            margin-bottom: 2rem;
        }

        .auth-header h2 {
            font-size: 1.875rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
        }

        .auth-header p {
            color: #94a3b8;
            margin: 0;
        }

        /* Toggle Login/Registro */
        .auth-toggle {
            display: flex;
            background: #0f172a;
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .auth-toggle a {
            flex: 1;
            text-align: center;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .auth-toggle a.active-login {
            background: #334155;
            color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .auth-toggle a.active-registro {
            background: #2563eb;
            color: white;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .auth-toggle a:not(.active-login):not(.active-registro) {
            color: #94a3b8;
        }

        .auth-toggle a:not(.active-login):not(.active-registro):hover {
            color: #e2e8f0;
        }

        /* Inputs */
        .form-group {
            position: relative;
            margin-bottom: 1.25rem;
        }

        .form-group .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 1.1rem;
            transition: color 0.3s ease;
            z-index: 2;
        }

        .form-group input {
            width: 100%;
            background: #0f172a;
            border: 1px solid #334155;
            color: white;
            padding: 1rem 1rem 1rem 3rem;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-group input::placeholder {
            color: #475569;
        }

        .form-group input:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }

        .form-group:focus-within .input-icon {
            color: #2563eb;
        }

        .form-group .toggle-password {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #64748b;
            cursor: pointer;
            padding: 0;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }

        .form-group .toggle-password:hover {
            color: #e2e8f0;
        }

        /* Options row */
        .options-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            color: #94a3b8;
        }

        .remember-me:hover {
            color: #cbd5e1;
        }

        .remember-me input[type="checkbox"] {
            appearance: none;
            width: 16px;
            height: 16px;
            background: #0f172a;
            border: 1px solid #475569;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .remember-me input[type="checkbox"]:checked {
            background: #2563eb;
            border-color: #2563eb;
        }

        .remember-me input[type="checkbox"]:checked::after {
            content: '✓';
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 10px;
            font-weight: bold;
        }

        .forgot-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .forgot-link:hover {
            color: #3b82f6;
        }

        /* Botón principal */
        .btn-submit {
            width: 100%;
            background: #2563eb;
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
        }

        .btn-submit i {
            transition: transform 0.3s ease;
        }

        .btn-submit:hover i {
            transform: translateX(4px);
        }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            margin: 2rem 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #334155;
        }

        .divider span {
            padding: 0 1rem;
            color: #64748b;
            font-size: 0.875rem;
        }

        /* Social buttons */
        .social-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .btn-social {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: transparent;
            border: 1px solid #334155;
            border-radius: 10px;
            color: #cbd5e1;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-social:hover {
            background: #0f172a;
            color: white;
        }

        .btn-social.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-social svg,
        .btn-social i {
            font-size: 1.25rem;
        }

        /* Alerts */
        .auth-alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .auth-alert.error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #f87171;
        }

        .auth-alert.success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: #4ade80;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .auth-container {
                flex-direction: column;
                max-width: 100%;
                min-height: auto;
            }

            .auth-visual {
                width: 100%;
                padding: 2rem;
                min-height: 250px;
            }

            .auth-form-panel {
                width: 100%;
                padding: 2rem;
            }

            .brand-title {
                font-size: 2rem;
            }

            .features-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Password strength indicator */
        .password-strength {
            height: 4px;
            background: #1e293b;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        /* Animación de entrada */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .auth-container {
            animation: fadeInUp 0.6s ease-out;
        }
    </style>
</head>

<body>
    <div class="bg-glow"></div>

    <div class="auth-container">
        <!-- Panel Visual Izquierdo -->
        <div class="auth-visual">
            <div class="auth-visual-bg">
                <img src="https://images.unsplash.com/photo-1486262715619-67b85e0b08d3?q=80&w=2000&auto=format&fit=crop"
                    alt="Autopartes">
            </div>
            <div class="auth-visual-content">
                <div class="brand-badge">
                    <i class="bi bi-gear-fill"></i>
                    Tienda Oficial
                </div>

                <h1 class="brand-title">NEKO<span>SAC</span></h1>

                <p class="brand-description">
                    Tu ferretería y autopartes online de confianza.
                    Frenos, embragues, herramientas y más al mejor precio.
                </p>

                <div class="features-grid">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-tools text-primary"></i>
                        </div>
                        <span>Garantía Total</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-truck text-success"></i>
                        </div>
                        <span>Envíos Express</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-shield-check text-info"></i>
                        </div>
                        <span>Pago Seguro</span>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="bi bi-headset text-warning"></i>
                        </div>
                        <span>Soporte 24/7</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel Formulario Derecho -->
        <div class="auth-form-panel">
            <div class="auth-header">
                <h2><?= $isLogin ? 'Bienvenido de nuevo' : 'Crea tu cuenta' ?></h2>
                <p><?= $isLogin
                        ? 'Accede para gestionar tus pedidos y compras.'
                        : 'Únete para obtener descuentos exclusivos.' ?></p>
            </div>

            <!-- Toggle Login/Registro -->
            <div class="auth-toggle">
                <a href="<?= BASE_URL ?>/login" class="<?= $isLogin ? 'active-login' : '' ?>">
                    Iniciar Sesión
                </a>
                <a href="<?= BASE_URL ?>/registro" class="<?= !$isLogin ? 'active-registro' : '' ?>">
                    Registrarse
                </a>
            </div>

            <!-- Mensajes -->
            <?php if (!empty($error)): ?>
                <div class="auth-alert error">
                    <i class="bi bi-exclamation-circle"></i>
                    <span><?= $error ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="auth-alert success">
                    <i class="bi bi-check-circle"></i>
                    <span><?= $success ?></span>
                </div>
            <?php endif; ?>

            <!-- Formulario -->
            <form method="post" action="<?= BASE_URL ?>/<?= $isLogin ? 'login' : 'registro' ?>" id="authForm">

                <?php if (!$isLogin): ?>
                    <!-- Campos solo para registro -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <i class="bi bi-person input-icon"></i>
                                <input type="text" name="nombre" placeholder="Nombre" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <i class="bi bi-person input-icon"></i>
                                <input type="text" name="apellido" placeholder="Apellido">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Email -->
                <div class="form-group">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" name="email" placeholder="correo@ejemplo.com" required>
                </div>

                <?php if (!$isLogin): ?>
                    <!-- Teléfono (solo registro) -->
                    <div class="form-group">
                        <i class="bi bi-phone input-icon"></i>
                        <input type="tel" name="telefono" placeholder="Teléfono (opcional)">
                    </div>
                <?php endif; ?>

                <!-- Contraseña -->
                <div class="form-group">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" name="password" id="password" placeholder="Contraseña" required
                        <?= !$isLogin ? 'minlength="6"' : '' ?>>
                    <button type="button" class="toggle-password" onclick="togglePassword('password')">
                        <i class="bi bi-eye" id="passwordIcon"></i>
                    </button>
                </div>

                <?php if (!$isLogin): ?>
                    <!-- Confirmar contraseña (solo registro) -->
                    <div class="form-group">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input type="password" name="password_confirm" id="password_confirm"
                            placeholder="Confirmar contraseña" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('password_confirm')">
                            <i class="bi bi-eye" id="password_confirmIcon"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength"></div>
                <?php endif; ?>

                <?php if ($isLogin): ?>
                    <!-- Opciones solo para login -->
                    <div class="options-row">
                        <label class="remember-me">
                            <input type="checkbox" name="remember">
                            <span>Recordarme</span>
                        </label>
                        <a href="#" class="forgot-link">¿Olvidaste tu contraseña?</a>
                    </div>
                <?php else: ?>
                    <!-- Términos (solo registro) -->
                    <div class="options-row" style="justify-content: flex-start;">
                        <label class="remember-me">
                            <input type="checkbox" name="terms" required>
                            <span>Acepto los <a href="#" class="forgot-link">términos y condiciones</a></span>
                        </label>
                    </div>
                <?php endif; ?>

                <!-- Botón Submit -->
                <button type="submit" class="btn-submit">
                    <?= $isLogin ? 'Acceder a mi cuenta' : 'Crear mi cuenta' ?>
                    <i class="bi bi-arrow-right"></i>
                </button>
            </form>

            <!-- Divider -->
            <div class="divider">
                <span>O continúa con</span>
            </div>

            <!-- Social Buttons -->
            <div class="social-buttons">
                <a href="<?= BASE_URL ?>/auth/google" class="btn-social <?= !$googleConfigured ? 'disabled' : '' ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" />
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                    </svg>
                    Google
                </a>
                <a href="<?= BASE_URL ?>/auth/facebook" class="btn-social <?= !$facebookConfigured ? 'disabled' : '' ?>">
                    <i class="bi bi-facebook" style="color: #1877F2;"></i>
                    Facebook
                </a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(inputId + 'Icon');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }

        // Password strength indicator (solo para registro)
        const passwordInput = document.getElementById('password');
        const strengthContainer = document.getElementById('passwordStrength');

        if (passwordInput && strengthContainer) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
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
        }

        // Validar contraseñas coinciden
        const confirmInput = document.getElementById('password_confirm');
        if (confirmInput && passwordInput) {
            confirmInput.addEventListener('input', function() {
                if (this.value && passwordInput.value !== this.value) {
                    this.style.borderColor = '#ef4444';
                } else {
                    this.style.borderColor = '#334155';
                }
            });
        }
    </script>
</body>

</html>