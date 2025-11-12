<?php
// src/login.php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer_smtp.php'; // PHPMailer (SMTP)

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $identity = trim($_POST['email'] ?? '');   // puede ser email o num_documento
  $password = (string)($_POST['password'] ?? '');

  if ($identity === '' || $password === '') {
    $error = 'Las credenciales ingresadas son incorrectas';
  } else {
    // LEFT JOIN para permitir usuarios SIN ROL (registro pendiente)
    $sql = '
      SELECT 
        u.idusuario       AS id_usuario,
        u.nombre          AS nombre,
        u.email,
        u.clave,          
        u.imagen,
        u.condicion       AS estado_usuario,  -- 0 inactivo, 1 activo, 3 pendiente
        u.id_tipodoc,
        u.num_documento,
        u.id_rol          AS id_rol,
        r.nombre          AS nombre_rol,
        r.estado          AS estado_rol
      FROM usuario u
      LEFT JOIN rol_usuarios r ON u.id_rol = r.id_rol
      WHERE u.email = ?
         OR u.num_documento = ?
      LIMIT 1
    ';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$identity, $identity]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
      $error = 'Las credenciales ingresadas son incorrectas.';
    } else {
      // --- Verificar contraseña primero ---
      $hashDb = (string)$user['clave'];
      $userId = (int)$user['id_usuario'];
      $email  = (string)$user['email'];
      $name   = trim((string)$user['nombre']);

      $ok = false;
      $info = password_get_info($hashDb);
      if (!empty($info['algo'])) {
        $ok = password_verify($password, $hashDb);
        if ($ok && password_needs_rehash($hashDb, PASSWORD_BCRYPT)) {
          $newHash = password_hash($password, PASSWORD_BCRYPT);
          $pdo->prepare('UPDATE usuario SET clave = ? WHERE idusuario = ?')->execute([$newHash, $userId]);
        }
      } else {
        $inputSha = hash('sha256', $password);
        $ok = hash_equals(strtolower($hashDb), strtolower($inputSha));
        if ($ok) {
          $newHash = password_hash($password, PASSWORD_BCRYPT);
          $pdo->prepare('UPDATE usuario SET clave = ? WHERE idusuario = ?')->execute([$newHash, $userId]);
        }
      }

      if (!$ok) {
        // Contraseña mala -> mensaje genérico
        $error = 'Las credenciales ingresadas son incorrectas.';
      } else {
        // Credenciales correctas -> revisar estado
        $estadoUsuario = (int)$user['estado_usuario'];           // 0,1,3
        $idRol         = $user['id_rol'] !== null ? (int)$user['id_rol'] : null;
        $estadoRol     = $user['estado_rol'] !== null ? (int)$user['estado_rol'] : null;

        if ($estadoUsuario === 0) {
          $error = 'Usuario inactivo. Contacte al administrador.';
        } elseif ($estadoUsuario === 3 || $idRol === null) {
          // pendiente de aprobación o sin rol asignado
          $error = 'Tu cuenta está pendiente de aprobación. Espera la activación por parte del administrador.';
        } elseif ($estadoRol === 0) {
          $error = 'Rol desactivado. Contacte al administrador del sistema.';
        } else {
          // === Generar OTP y enviarlo por correo ===
          try {
            $otp      = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpHash  = hash('sha256', $otp);
            $expires  = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');

            $pdo->prepare('DELETE FROM user_otp WHERE user_id = ?')->execute([$userId]);
            $pdo->prepare('INSERT INTO user_otp (user_id, code_hash, expires_at) VALUES (?, ?, ?)')
                ->execute([$userId, $otpHash, $expires]);

            $mailOk = sendAuthCode($email, $otp);
            if (!$mailOk) {
              $error = 'No se pudo enviar el correo: revisa tu configuración SMTP en includes/mailer_smtp.php';
            } else {
              // Sesión temporal para OTP
              $_SESSION['otp_uid']       = $userId;
              $_SESSION['otp_name']      = $name;
              $_SESSION['otp_email']     = $email;
              $_SESSION['otp_sent']      = time();
              $_SESSION['imagen']        = $user['imagen'] ?: 'default.png';
              $_SESSION['otp_role_id']   = $idRol ?? 0;
              $_SESSION['otp_role_name'] = (string)($user['nombre_rol'] ?? '');
              $_SESSION['otp_cargo']     = $_SESSION['otp_role_name'];

              header('Location: verify.php');
              exit;
            }
          } catch (Throwable $e) {
            $error = 'No se pudo generar/enviar el código de verificación. Inténtalo nuevamente.';
          }
        }
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>Login - Neko SAC</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="css/estilos.css?v=<?= time() ?>">
  <style>
    .input-password-wrapper {
      position: relative;
      display: flex;
      align-items: center;
    }
    
    .toggle-password {
      position: absolute;
      right: 40px;
      cursor: pointer;
      padding: 5px;
      display: flex;
      align-items: center;
      justify-content: center;
      user-select: none;
      z-index: 2;
    }
    
    .toggle-password svg {
      width: 20px;
      height: 20px;
      fill: #666;
      transition: fill 0.2s;
    }
    
    .toggle-password:hover svg {
      fill: #333;
    }
    
    .input.has-toggle input {
      padding-right: 70px;
    }
  </style>
</head>
<body class="auth-body">
  <div class="auth-wrapper">
    <section class="auth-card">
      <div class="auth-left">
        <div class="brand-wrap">
          <img src="assets/logo.png" alt="Logo Empresa" class="brand-logo">
          <h1 class="brand-title">Hola, ¡bienvenido!</h1>
          <p class="brand-sub">¿No tienes una cuenta?</p>
          <a class="btn btn-outline" href="register.php">Register</a>
        </div>
      </div>

      <div class="auth-right">
        <h2 class="auth-title">Login</h2>

        <?php if ($error): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="login.php" class="auth-form" autocomplete="off" novalidate>
          <label class="field">
            <span class="field-label">Email o N° de documento:</span>
            <div class="input">
              <input type="text" name="email" placeholder="tucorreo@empresa.com o tu N° de documento" required autocomplete="username">
              <span class="icon">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4.08 0-8 2.06-8 5v1h16v-1c0-2.94-3.92-5-8-5Z"/></svg>
              </span>
            </div>
          </label>

          <label class="field">
            <span class="field-label">Contraseña</span>
            <div class="input has-toggle">
              <input type="password" id="passwordInput" name="password" placeholder="••••••••" required autocomplete="current-password">
              <span class="toggle-password" id="togglePassword" title="Mostrar/Ocultar contraseña">
                <!-- Ícono ojo cerrado (password oculta) -->
                <svg id="eyeOff" viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 7c2.76 0 5 2.24 5 5 0 .65-.13 1.26-.36 1.83l2.92 2.92c1.51-1.26 2.7-2.89 3.43-4.75-1.73-4.39-6-7.5-11-7.5-1.4 0-2.74.25-3.98.7l2.16 2.16C10.74 7.13 11.35 7 12 7zM2 4.27l2.28 2.28.46.46A11.804 11.804 0 0 0 1 12c1.73 4.39 6 7.5 11 7.5 1.55 0 3.03-.3 4.38-.84l.42.42L19.73 22 21 20.73 3.27 3 2 4.27zM7.53 9.8l1.55 1.55c-.05.21-.08.43-.08.65 0 1.66 1.34 3 3 3 .22 0 .44-.03.65-.08l1.55 1.55c-.67.33-1.41.53-2.2.53-2.76 0-5-2.24-5-5 0-.79.2-1.53.53-2.2zm4.31-.78 3.15 3.15.02-.16c0-1.66-1.34-3-3-3l-.17.01z"/>
                </svg>
                <!-- Ícono ojo abierto (password visible) -->
                <svg id="eyeOn" viewBox="0 0 24 24" aria-hidden="true" style="display: none;">
                  <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                </svg>
              </span>
              <span class="icon">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-7-2a2 2 0 0 1 4 0v2H10Zm7 12H7v-8h10Z"/></svg>
              </span>
            </div>
          </label>

          <div class="row-between">
            <a class="link-muted" href="forgot_password.php">¿Olvidaste tu contraseña?</a>
          </div>

          <button type="submit" class="btn btn-primary w-full">Login</button>

          <p class="small text-center m-top">
            ¿No tienes cuenta? <a href="register.php" class="link-strong">Regístrate</a>
          </p>
        </form>
      </div>
    </section>
  </div>

  <script>
    // Toggle password visibility
    const togglePassword = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('passwordInput');
    const eyeOff = document.getElementById('eyeOff');
    const eyeOn = document.getElementById('eyeOn');

    togglePassword.addEventListener('click', function() {
      // Cambiar el tipo de input
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);
      
      // Cambiar el ícono
      if (type === 'text') {
        eyeOff.style.display = 'none';
        eyeOn.style.display = 'block';
      } else {
        eyeOff.style.display = 'block';
        eyeOn.style.display = 'none';
      }
    });
  </script>
</body>
</html>