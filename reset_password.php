<?php 
// src/reset_password.php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

session_start();

$error = '';
$message = '';
$validToken = false;
$userId = null;
$userEmail = '';
$userNombre = '';
$attempts = 0;
$MAX_ATTEMPTS = 5;

// Conserva el token actual (GET o POST)
$currentToken = (string)($_POST['token'] ?? $_GET['token'] ?? '');
$currentTokenHash = $currentToken !== '' ? hash('sha256', $currentToken) : '';

/* ============== Validador robusto (idéntico a register.php) ============== */
function validar_password_robusta(string $pwd, string $email='', string $nombres='', string $apellidos=''): ?string {
  if (strlen($pwd) < 10 || strlen($pwd) > 64) return 'La contraseña debe tener entre 10 y 64 caracteres.';
  if (preg_match('/\s/', $pwd)) return 'La contraseña no debe contener espacios.';
  if (!preg_match('/[A-Z]/', $pwd)) return 'Debe incluir al menos una letra mayúscula (A-Z).';
  if (!preg_match('/[a-z]/', $pwd)) return 'Debe incluir al menos una letra minúscula (a-z).';
  if (!preg_match('/[0-9]/', $pwd)) return 'Debe incluir al menos un dígito (0-9).';
  if (!preg_match('/[!@#$%^&*()_\+\=\-\[\]{};:,.?]/', $pwd)) return 'Debe incluir al menos un caracter especial: !@#$%^&*()_+=-[]{};:,.?';

  $lowerPwd = mb_strtolower($pwd,'UTF-8'); 
  $prohibidos = [];
  if ($email){ 
    $local = mb_strtolower((string)strtok($email,'@'),'UTF-8'); 
    if ($local) $prohibidos[] = $local; 
  }
  foreach (preg_split('/\s+/', trim($nombres.' '.$apellidos)) as $pieza){
    $pieza = mb_strtolower($pieza,'UTF-8'); 
    if (mb_strlen($pieza,'UTF-8')>=4) $prohibidos[] = $pieza; 
  }
  foreach ($prohibidos as $p){ 
    if($p!=='' && mb_strpos($lowerPwd,$p,0,'UTF-8')!==false) 
      return 'No debe contener partes de tu correo, nombres o apellidos.'; 
  }

  $comunes=['123456','123456789','12345678','12345','qwerty','password','111111','abc123','123123','iloveyou','admin','welcome','monkey','dragon','qwertyuiop','000000'];
  if (in_array($lowerPwd, $comunes, true)) return 'La contraseña es demasiado común. Elige otra.';
  return null;
}

/* ===== Helpers intentos (DB si existe attempts; sino sesión) ===== */
function pr_has_attempts_column(PDO $pdo): bool {
  static $cache = null;
  if ($cache !== null) return $cache;
  try { $stmt = $pdo->query("SHOW COLUMNS FROM password_reset LIKE 'attempts'"); $cache = (bool)$stmt->fetch(); }
  catch (Exception $e) { $cache = false; }
  return $cache;
}
function pr_get_attempts(PDO $pdo, string $tokenHash): int {
  if (!$tokenHash) return 0;
  if (pr_has_attempts_column($pdo)) {
    $s = $pdo->prepare('SELECT attempts FROM password_reset WHERE token_hash = ? LIMIT 1');
    $s->execute([$tokenHash]);
    $row = $s->fetch(PDO::FETCH_ASSOC);
    return (int)($row['attempts'] ?? 0);
  }
  return (int)($_SESSION['pr_attempts'][$tokenHash] ?? 0);
}
function pr_inc_attempts(PDO $pdo, string $tokenHash): int {
  if (!$tokenHash) return 0;
  if (pr_has_attempts_column($pdo)) {
    $u = $pdo->prepare('UPDATE password_reset SET attempts = attempts + 1 WHERE token_hash = ?');
    $u->execute([$tokenHash]);
    return pr_get_attempts($pdo, $tokenHash);
  }
  if (!isset($_SESSION['pr_attempts'])) $_SESSION['pr_attempts'] = [];
  $_SESSION['pr_attempts'][$tokenHash] = (int)($_SESSION['pr_attempts'][$tokenHash] ?? 0) + 1;
  return (int)$_SESSION['pr_attempts'][$tokenHash];
}
function pr_reset_attempts(PDO $pdo, string $tokenHash): void {
  if (!$tokenHash) return;
  if (pr_has_attempts_column($pdo)) {
    $u = $pdo->prepare('UPDATE password_reset SET attempts = 0 WHERE token_hash = ?');
    $u->execute([$tokenHash]);
  } else {
    if (isset($_SESSION['pr_attempts'][$tokenHash])) unset($_SESSION['pr_attempts'][$tokenHash]);
  }
}
function pr_invalidate_token(PDO $pdo, string $tokenHash, ?int $userId = null): void {
  if ($tokenHash) {
    $pdo->prepare('UPDATE password_reset SET used = 1 WHERE token_hash = ?')->execute([$tokenHash]);
  }
  if ($userId !== null) {
    $pdo->prepare('DELETE FROM password_reset WHERE user_id = ?')->execute([$userId]);
  }
}
function set_generic_expired_msg(): string {
  return 'Has excedido el limite de ingresos, Solicita de nuevo el cambio de Credenciales.';
}

/* ================== Verificar token (GET) ================== */
if ($currentToken !== '' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
  $stmt = $pdo->prepare(
    'SELECT pr.user_id, u.nombre, u.email, pr.expires_at, pr.used 
     FROM password_reset pr
     INNER JOIN usuario u ON pr.user_id = u.idusuario
     WHERE pr.token_hash = ? 
       AND pr.expires_at > NOW() 
       AND pr.used = 0
       AND u.condicion = 1
     LIMIT 1'
  );
  $stmt->execute([$currentTokenHash]);
  $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($tokenData) {
    $validToken = true;
    $userId     = (int)$tokenData['user_id'];
    $userNombre = (string)$tokenData['nombre'];
    $userEmail  = (string)$tokenData['email'];
    $attempts   = pr_get_attempts($pdo, $currentTokenHash);
  } else {
    $error = set_generic_expired_msg();
  }
}

/* ================== Procesar POST (cambio de contraseña) ================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $currentToken !== '') {
  $newPassword     = (string)($_POST['password'] ?? '');
  $confirmPassword = (string)($_POST['confirm_password'] ?? '');

  // Revalidar token
  $stmt = $pdo->prepare(
    'SELECT pr.user_id, u.nombre, u.email, pr.expires_at, pr.used 
     FROM password_reset pr
     INNER JOIN usuario u ON pr.user_id = u.idusuario
     WHERE pr.token_hash = ? 
       AND pr.expires_at > NOW() 
       AND pr.used = 0
       AND u.condicion = 1
     LIMIT 1'
  );
  $stmt->execute([$currentTokenHash]);
  $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$tokenData) {
    $error = set_generic_expired_msg();
    $validToken = false;
  } else {
    $userId     = (int)$tokenData['user_id'];
    $userNombre = (string)$tokenData['nombre'];
    $userEmail  = (string)$tokenData['email'];
    $attempts   = pr_get_attempts($pdo, $currentTokenHash);

    if ($attempts >= $MAX_ATTEMPTS) {
      pr_invalidate_token($pdo, $currentTokenHash, $userId);
      $error = set_generic_expired_msg();
      $validToken = false;
    } else {
      $terminal = false;

      if ($newPassword === '' || $confirmPassword === '') {
        $attempts = pr_inc_attempts($pdo, $currentTokenHash);
        if ($attempts >= $MAX_ATTEMPTS) {
          pr_invalidate_token($pdo, $currentTokenHash, $userId);
          $error = set_generic_expired_msg();
          $terminal = true;
        } else {
          $restantes = $MAX_ATTEMPTS - $attempts;
          $error = 'Ingresa una contraseña válida. Debes completar los campos. Intentos restantes: ' . $restantes . '.';
        }
      } elseif ($newPassword !== $confirmPassword) {
        $attempts = pr_inc_attempts($pdo, $currentTokenHash);
        if ($attempts >= $MAX_ATTEMPTS) {
          pr_invalidate_token($pdo, $currentTokenHash, $userId);
          $error = set_generic_expired_msg();
          $terminal = true;
        } else {
          $restantes = $MAX_ATTEMPTS - $attempts;
          $error = 'Las contraseñas no coinciden. Intentos restantes: ' . $restantes . '.';
        }
      } else {
        $errPwd = validar_password_robusta($newPassword, $userEmail, $userNombre, '');
        if ($errPwd !== null) {
          $attempts = pr_inc_attempts($pdo, $currentTokenHash);
          if ($attempts >= $MAX_ATTEMPTS) {
            pr_invalidate_token($pdo, $currentTokenHash, $userId);
            $error = set_generic_expired_msg();
            $terminal = true;
          } else {
            $restantes = $MAX_ATTEMPTS - $attempts;
            $error = 'Ingresa una contraseña válida: ' . $errPwd . ' Intentos restantes: ' . $restantes . '.';
          }
        } else {
          try {
            $pdo->beginTransaction();
            $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE usuario SET clave = ? WHERE idusuario = ?')->execute([$newPasswordHash, $userId]);
            $pdo->prepare('UPDATE password_reset SET used = 1 WHERE token_hash = ?')->execute([$currentTokenHash]);
            $pdo->prepare('DELETE FROM password_reset WHERE user_id = ?')->execute([$userId]);
            $pdo->commit();
            pr_reset_attempts($pdo, $currentTokenHash);
            $message = 'Tu contraseña ha sido actualizada correctamente. Ahora puedes iniciar sesión.';
            $validToken = false;
          } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'No se pudo actualizar la contraseña. Intenta nuevamente.';
          }
        }
      }

      if (!$message) {
        $validToken = !$terminal;
      }
    }
  }

  if ($validToken) {
    $attempts = pr_get_attempts($pdo, $currentTokenHash);
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>Restablecer Contraseña - Neko SAC</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="css/estilos.css?v=<?= time() ?>">
  <style>
    /* ========== ESTILOS PROFESIONALES Y CORPORATIVOS ========== */
    
    /* Variables CSS para modo claro/oscuro */
    :root {
      --text-error: #dc2626;
      --text-success: #059669;
      --text-info: #3b82f6;
      --text-muted: #64748b;
      --text-label: #1e293b;
      
      --bg-rules: #f8fafc;
      --bg-req-error: rgba(220, 38, 38, 0.05);
      --bg-req-success: rgba(5, 150, 105, 0.05);
      
      --eye-bg: rgba(148, 163, 184, 0.1);
      --eye-bg-hover: rgba(148, 163, 184, 0.18);
      --eye-border: rgba(148, 163, 184, 0.2);
      --eye-border-hover: rgba(148, 163, 184, 0.35);
      --eye-icon: #64748b;
      --eye-icon-hover: #475569;
      --eye-icon-active: #2563eb;
    }
    
    /* Detección automática de modo oscuro */
    @media (prefers-color-scheme: dark) {
      :root {
        --text-error: #fca5a5;
        --text-success: #6ee7b7;
        --text-info: #93c5fd;
        --text-muted: #94a3b8;
        --text-label: #e2e8f0;
        --bg-rules: rgba(30, 41, 59, 0.4);
        --bg-req-error: rgba(220, 38, 38, 0.15);
        --bg-req-success: rgba(5, 150, 105, 0.15);
        
        --eye-bg: rgba(148, 163, 184, 0.15);
        --eye-bg-hover: rgba(148, 163, 184, 0.25);
        --eye-border: rgba(148, 163, 184, 0.25);
        --eye-border-hover: rgba(148, 163, 184, 0.4);
        --eye-icon: #94a3b8;
        --eye-icon-hover: #cbd5e1;
        --eye-icon-active: #60a5fa;
      }
    }
    
    /* Forzar modo oscuro */
    body.dark,
    .dark-mode,
    [data-theme="dark"] {
      --text-error: #fca5a5;
      --text-success: #6ee7b7;
      --text-info: #93c5fd;
      --text-muted: #94a3b8;
      --text-label: #e2e8f0;
      --bg-rules: rgba(30, 41, 59, 0.4);
      --bg-req-error: rgba(220, 38, 38, 0.15);
      --bg-req-success: rgba(5, 150, 105, 0.15);
      
      --eye-bg: rgba(148, 163, 184, 0.15);
      --eye-bg-hover: rgba(148, 163, 184, 0.25);
      --eye-border: rgba(148, 163, 184, 0.25);
      --eye-border-hover: rgba(148, 163, 184, 0.4);
      --eye-icon: #94a3b8;
      --eye-icon-hover: #cbd5e1;
      --eye-icon-active: #60a5fa;
    }
    
    /* Iconos de validación */
    .validation-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      font-size: 11px;
      font-weight: 600;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      flex-shrink: 0;
    }
    
    .req {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 0.875rem;
      margin: 6px 0;
      padding: 6px 10px;
      border-radius: 6px;
      transition: all 0.25s ease;
      font-weight: 500;
    }
    
    .req.bad {
      color: var(--text-error);
      background: var(--bg-req-error);
    }
    
    .req.bad .validation-icon {
      background: linear-gradient(135deg, #dc2626, #b91c1c);
      color: white;
      box-shadow: 0 2px 4px rgba(220, 38, 38, 0.3);
    }
    
    .req.bad .validation-icon::before {
      content: '✕';
      font-size: 12px;
    }
    
    .req.ok {
      color: var(--text-success);
      background: var(--bg-req-success);
    }
    
    .req.ok .validation-icon {
      background: linear-gradient(135deg, #059669, #047857);
      color: white;
      box-shadow: 0 2px 4px rgba(5, 150, 105, 0.3);
    }
    
    .req.ok .validation-icon::before {
      content: '✓';
      font-size: 12px;
      font-weight: 700;
    }

    /* Botón de ojo */
    .input-eye {
      position: absolute;
      right: 6px;
      top: 50%;
      transform: translateY(-50%);
      width: 18px;
      height: 18px;
      min-width: 30px;
      border-radius: 6px;
      background: var(--eye-bg);
      border: 1px solid var(--eye-border);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      user-select: none;
      flex-shrink: 0;
    }
    
    .input-eye:hover {
      background: var(--eye-bg-hover);
      border-color: var(--eye-border-hover);
      transform: translateY(-50%) scale(1.05);
    }
    
    .input-eye:active {
      transform: translateY(-50%) scale(0.95);
    }

    /* Icono del ojo */
    .eye-icon {
      width: 16px;
      height: 16px;
      position: relative;
      transition: all 0.3s ease;
    }
    
    .eye-icon::before {
      content: '';
      position: absolute;
      width: 16px;
      height: 9px;
      border: 2px solid var(--eye-icon);
      border-radius: 50% 50% 50% 50% / 100% 100% 0 0;
      top: 3px;
      left: 0;
      transition: all 0.3s ease;
    }
    
    .eye-icon::after {
      content: '';
      position: absolute;
      width: 4px;
      height: 4px;
      background: var(--eye-icon);
      border-radius: 50%;
      top: 6px;
      left: 6px;
      transition: all 0.3s ease;
    }
    
    .input-eye:hover .eye-icon::before,
    .input-eye:hover .eye-icon::after {
      border-color: var(--eye-icon-hover);
      background: var(--eye-icon-hover);
    }
    
    .input-eye.active {
      background: rgba(59, 130, 246, 0.15);
      border-color: rgba(59, 130, 246, 0.4);
    }
    
    .input-eye.active .eye-icon::before,
    .input-eye.active .eye-icon::after {
      border-color: var(--eye-icon-active);
      background: var(--eye-icon-active);
    }
    
    .input-eye.active .eye-icon::before {
      animation: blink 0.3s ease;
    }
    
    @keyframes blink {
      0%, 100% { height: 9px; }
      50% { height: 2px; }
    }

    .input-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }
    
    .input-wrap input {
      width: 100%;
      padding-right: 44px !important;
    }

    .hint {
      display: block;
      margin-top: 6px;
      font-size: 0.8125rem;
      color: var(--text-muted);
      transition: all 0.3s ease;
      font-weight: 500;
    }

    #rules {
      margin-top: 12px;
      padding: 12px;
      background: var(--bg-rules);
      border-radius: 8px;
      border: 1px solid rgba(148, 163, 184, 0.2);
    }

    .field {
      margin-bottom: 1.25rem;
    }
    
    .field-label {
      display: block;
      margin-bottom: 6px;
      font-weight: 600;
      color: var(--text-label);
      font-size: 0.9rem;
    }

    .alert {
      padding: 14px 16px;
      margin-bottom: 18px;
      border-radius: 10px;
      font-weight: 500;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    }
    
    .alert-error {
      background: rgba(220, 38, 38, 0.15);
      color: var(--text-error);
      border-left: 4px solid #dc2626;
      border: 1px solid rgba(220, 38, 38, 0.3);
    }
    
    .alert-success {
      background: rgba(5, 150, 105, 0.15);
      color: var(--text-success);
      border-left: 4px solid #059669;
      border: 1px solid rgba(5, 150, 105, 0.3);
    }

    .auth-form {
      max-height: 70vh;
      overflow-y: auto;
      padding-right: 10px;
    }
    
    .auth-form::-webkit-scrollbar {
      width: 8px;
    }
    
    .auth-form::-webkit-scrollbar-track {
      background: rgba(148, 163, 184, 0.1);
      border-radius: 4px;
    }
    
    .auth-form::-webkit-scrollbar-thumb {
      background: linear-gradient(180deg, #94a3b8, #64748b);
      border-radius: 4px;
    }
    
    .auth-form::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(180deg, #64748b, #475569);
    }

    input {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    input:focus {
      outline: none;
      border-color: #60a5fa;
      box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.15);
    }

    /* Banner de intentos */
    .tries-wrap {
      margin: 0 0 16px;
      padding: 12px 14px;
      border-radius: 10px;
      background: rgba(59, 130, 246, 0.1);
      border: 1px solid rgba(59, 130, 246, 0.3);
      box-shadow: 0 2px 4px rgba(59, 130, 246, 0.1);
    }
    
    .tries-top {
      display: flex;
      justify-content: space-between;
      font-size: 0.875rem;
      margin-bottom: 8px;
      color: var(--text-label);
      font-weight: 500;
    }
    
    .tries-top strong {
      color: #3b82f6;
      font-weight: 700;
    }
    
    .tries-bar {
      height: 8px;
      width: 100%;
      background: rgba(148, 163, 184, 0.2);
      border-radius: 999px;
      overflow: hidden;
      box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
    }
    
    .tries-fill {
      height: 100%;
      width: 0;
      background: linear-gradient(90deg, #3b82f6, #2563eb);
      transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 0 8px rgba(59, 130, 246, 0.5);
    }

    @media (max-width: 768px) {
      .input-eye {
        width: 28px;
        height: 28px;
        min-width: 28px;
        right: 5px;
      }
      
      .eye-icon {
        width: 14px;
        height: 14px;
      }
      
      .eye-icon::before {
        width: 14px;
        height: 8px;
      }
      
      .eye-icon::after {
        width: 3px;
        height: 3px;
        left: 5.5px;
        top: 5px;
      }
      
      .input-wrap input {
        padding-right: 40px !important;
      }
    }
    
    @media (max-width: 480px) {
      .input-eye {
        width: 26px;
        height: 26px;
        right: 4px;
      }
      
      .input-wrap input {
        padding-right: 36px !important;
      }
    }
  </style>
</head>
<body class="auth-body">
  <div class="auth-wrapper">
    <section class="auth-card">
      <div class="auth-left">
        <div class="brand-wrap">
          <img src="assets/logo.png" alt="Logo Empresa" class="brand-logo">
          <h1 class="brand-title">Nueva contraseña</h1>
          <p class="brand-sub">Ingresa tu nueva contraseña segura</p>
          <a class="btn btn-outline" href="login.php">Volver al Login</a>
        </div>
      </div>

      <div class="auth-right">
        <h2 class="auth-title">Restablecer contraseña</h2>

        <?php if ($message): ?>
          <div class="alert alert-success">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            <br><br>
            <a href="login.php" class="btn btn-primary w-full">Ir al Login</a>
          </div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="alert alert-error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            <?php if (!$validToken): ?>
              <br><br>
              <a href="forgot_password.php" class="btn btn-primary w-full">Solicitar nuevo enlace</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php
          $restantesUI = null;
          if ($validToken && !$message && $currentToken !== '') {
            $restantesUI = max(0, $MAX_ATTEMPTS - (int)$attempts);
          }
        ?>

        <?php if ($validToken && !$message): ?>
          <?php if ($restantesUI !== null): 
            $used = min($MAX_ATTEMPTS, (int)$attempts);
            $percent = ($used / $MAX_ATTEMPTS) * 100;
          ?>
            <div class="tries-wrap">
              <div class="tries-top">
                <span>Intentos: <strong><?= ($MAX_ATTEMPTS - $attempts) ?>/<?= $MAX_ATTEMPTS ?></strong></span>
                <span>Usados: <?= $used ?></span>
              </div>
              <div class="tries-bar"><div class="tries-fill" style="width: <?= (int)$percent ?>%"></div></div>
            </div>
          <?php endif; ?>

          <form id="resetForm" method="post" action="reset_password.php" class="auth-form" autocomplete="off" novalidate>
            <input type="hidden" name="token" value="<?= htmlspecialchars($currentToken, ENT_QUOTES, 'UTF-8') ?>">

            <label class="field">
              <span class="field-label">Nueva contraseña</span>
              <div class="input-wrap">
                <input id="pwd" type="password" name="password" placeholder="••••••••" required autocomplete="new-password" aria-describedby="pwdHelp">
                <button type="button" class="input-eye" id="togglePwd" title="Ver/Ocultar contraseña">
                  <span class="eye-icon"></span>
                </button>
              </div>
              <small id="pwdHelp" class="hint">Debe cumplir todos los requisitos:</small>
              <div id="rules">
                <div class="req bad" id="r-len"><span class="validation-icon"></span> 10–64 caracteres</div>
                <div class="req bad" id="r-up"><span class="validation-icon"></span> Al menos 1 mayúscula (A-Z)</div>
                <div class="req bad" id="r-low"><span class="validation-icon"></span> Al menos 1 minúscula (a-z)</div>
                <div class="req bad" id="r-num"><span class="validation-icon"></span> Al menos 1 número (0-9)</div>
                <div class="req bad" id="r-spe"><span class="validation-icon"></span> Al menos 1 especial (!@#$%^&*)</div>
                <div class="req bad" id="r-spc"><span class="validation-icon"></span> Sin espacios</div>
                <div class="req bad" id="r-pii"><span class="validation-icon"></span> No contiene correo/nombres</div>
                <div class="req bad" id="r-common"><span class="validation-icon"></span> No es contraseña común</div>
              </div>
            </label>

            <label class="field">
              <span class="field-label">Confirmar contraseña</span>
              <div class="input-wrap">
                <input id="pwd2" type="password" name="confirm_password" placeholder="••••••••" required autocomplete="new-password">
                <button type="button" class="input-eye" id="togglePwd2" title="Ver/Ocultar contraseña">
                  <span class="eye-icon"></span>
                </button>
              </div>
            </label>

            <button id="submitBtn" type="submit" class="btn btn-primary w-full">Restablecer contraseña</button>
          </form>
        <?php endif; ?>
      </div>
    </section>
  </div>

<script>
// Ver/ocultar con animación
function togglePass(id, btnId){
  const input = document.getElementById(id);
  const btn = document.getElementById(btnId);
  if (input && btn) {
    btn.addEventListener('click', (e)=>{
      e.preventDefault();
      if(input.type === 'password'){
        input.type = 'text';
        btn.classList.add('active');
      } else {
        input.type = 'password';
        btn.classList.remove('active');
      }
    });
  }
}
togglePass('pwd','togglePwd'); 
togglePass('pwd2','togglePwd2');

// Evitar doble envío
(function(){
  const form = document.getElementById('resetForm');
  const btn  = document.getElementById('submitBtn');
  if (!form || !btn) return;
  form.addEventListener('submit', function(){
    btn.disabled = true;
    btn.textContent = 'Procesando...';
  });
})();

// Validación en vivo
(function(){
  const pwd = document.getElementById('pwd');
  const pwd2 = document.getElementById('pwd2');
  const common = new Set(['123456','123456789','12345678','12345','qwerty','password','111111','abc123','123123','iloveyou','admin','welcome','monkey','dragon','qwertyuiop','000000']);
  
  function mark(id, ok){ 
    const el = document.getElementById(id); 
    if(!el) return; 
    el.classList.toggle('ok', ok); 
    el.classList.toggle('bad', !ok); 
  }
  
  function strongCheck(v){
    const len = v.length>=10 && v.length<=64,
          up=/[A-Z]/.test(v), 
          low=/[a-z]/.test(v),
          num=/[0-9]/.test(v), 
          spe=/[!@#$%^&*()_\+\=\-\[\]{};:,.?]/.test(v),
          spc=!/\s/.test(v);
    const notCommon = !common.has(v.toLowerCase());
    mark('r-len',len); 
    mark('r-up',up); 
    mark('r-low',low); 
    mark('r-num',num);
    mark('r-spe',spe); 
    mark('r-spc',spc); 
    mark('r-pii',true); 
    mark('r-common',notCommon);
    return len&&up&&low&&num&&spe&&spc&&notCommon;
  }
  
  function syncValidity(){
    const ok = strongCheck(pwd.value);
    pwd.setCustomValidity(ok ? '' : 'La contraseña no cumple los requisitos mínimos.');
    if (pwd2.value && pwd2.value !== pwd.value) pwd2.setCustomValidity('Las contraseñas no coinciden.');
    else pwd2.setCustomValidity('');
  }
  
  if (pwd)  pwd.addEventListener('input', syncValidity);
  if (pwd2) pwd2.addEventListener('input', syncValidity);
})();
</script>
</body>
</html>