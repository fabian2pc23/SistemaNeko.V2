<?php
// src/forgot_password.php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mailer_smtp.php';

$message = '';
$error   = '';

function is_valid_email(string $email): bool {
  return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');

  // 1) Validación básica
  if ($email === '') {
    $error = 'Por favor ingresa tu correo electrónico.';
  } elseif (!is_valid_email($email)) {
    $error = 'Formato de correo inválido.';
  }

  if ($error === '') {
    // 2) Verificar si el usuario existe y está ACTIVO
    $stmt = $pdo->prepare(
      'SELECT idusuario, nombre, email
         FROM usuario
        WHERE email = ? AND condicion = 1
        LIMIT 1'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
      // Mensaje explícito, tal como pediste
      $error = 'El correo no está asociado a ningún usuario activo.';
    } else {
      $userId   = (int)$user['idusuario'];
      $userName = trim((string)$user['nombre']);

      // 3) Limpiar tokens anteriores de este usuario (opcional pero recomendable)
      $pdo->prepare('DELETE FROM password_reset WHERE user_id = ?')->execute([$userId]);

      // 4) Generar y guardar nuevo token
      $token     = bin2hex(random_bytes(32));     // token visible
      $tokenHash = hash('sha256', $token);        // sólo guardamos el hash
      $expires   = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

      $ins = $pdo->prepare(
        'INSERT INTO password_reset (user_id, token_hash, expires_at, used, created_at)
         VALUES (?, ?, ?, 0, NOW())'
      );
      $ins->execute([$userId, $tokenHash, $expires]);

      // 5) Construir URL absoluta al reset
      $isHttps  = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
      $scheme   = $isHttps ? 'https' : 'http';
      $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
      $basePath = rtrim(dirname($_SERVER['PHP_SELF'] ?? '/'), '/\\');
      $resetUrl = $scheme . '://' . $host . $basePath . '/reset_password.php?token=' . urlencode($token);

      // 6) Enviar correo
      $mailOk = sendPasswordResetEmail($email, $userName, $resetUrl);

      if ($mailOk) {
        $message = 'Te enviamos un enlace de recuperación a tu correo (válido por 60 minutos). Revisa tu bandeja de entrada y spam.';
      } else {
        $error = 'Error al enviar el correo. Por favor contacta al administrador.';
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>Recuperar Contraseña - Neko SAC</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="css/estilos.css?v=<?= time() ?>">
  <style>
    .alert { padding:12px; margin-bottom:16px; border-radius:8px; }
    .alert-error   { background:#fed7d7; color:#742a2a; border-left:4px solid #f56565; }
    .alert-success { background:#c6f6d5; color:#22543d; border-left:4px solid #48bb78; }
    .hint { font-size:.9rem; opacity:.9; }
  </style>
</head>
<body class="auth-body">
  <div class="auth-wrapper">
    <section class="auth-card">
      <div class="auth-left">
        <div class="brand-wrap">
          <img src="assets/logo.png" alt="Logo Empresa" class="brand-logo">
          <h1 class="brand-title">Recupera tu cuenta</h1>
          <p class="brand-sub">Te enviaremos un enlace para restablecer tu contraseña</p>
          <a class="btn btn-outline" href="login.php">Volver al Login</a>
        </div>
      </div>

      <div class="auth-right">
        <h2 class="auth-title">¿Olvidaste tu contraseña?</h2>
        <p class="auth-subtitle">Ingresa tu correo electrónico y te enviaremos un enlace para recuperar tu cuenta.</p>

        <?php if ($message): ?>
          <div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if (!$message): ?>
        <form method="post" action="forgot_password.php" class="auth-form" autocomplete="off" novalidate>
          <label class="field">
            <span class="field-label">Correo electrónico</span>
            <div class="input">
              <input id="email" type="email" name="email" placeholder="tucorreo@empresa.com" required autocomplete="email">
              <span class="icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
              </span>
            </div>
            <small id="email-hint" class="hint">Debe estar asociado a un usuario activo.</small>
          </label>

          <button type="submit" class="btn btn-primary w-full">Enviar enlace de recuperación</button>

          <p class="small text-center m-top">
            ¿Recordaste tu contraseña? <a href="login.php" class="link-strong">Inicia sesión</a>
          </p>
        </form>
        <?php endif; ?>
      </div>
    </section>
  </div>

<script>
// Validación rápida de formato en cliente (extra UX, el backend ya valida)
(function(){
  const email = document.getElementById('email');
  const hint  = document.getElementById('email-hint');
  if (!email) return;
  function fmtOk(v){ return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); }
  email.addEventListener('input', ()=>{
    const v = email.value.trim();
    if (!v) { email.setCustomValidity(''); hint.textContent='Debe estar asociado a un usuario activo.'; hint.style.color=''; return; }
    if (!fmtOk(v)) { email.setCustomValidity('Formato de correo inválido'); hint.textContent='Formato de correo inválido'; hint.style.color='#ef4444'; }
    else { email.setCustomValidity(''); hint.textContent='Formato correcto'; hint.style.color='#10b981'; }
  });
})();
</script>
</body>
</html>
