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
  // Mensaje EXACTO como tu segunda captura
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
    $validToken = false; // ocultar formulario
  } else {
    $userId     = (int)$tokenData['user_id'];
    $userNombre = (string)$tokenData['nombre'];
    $userEmail  = (string)$tokenData['email'];
    $attempts   = pr_get_attempts($pdo, $currentTokenHash);

    if ($attempts >= $MAX_ATTEMPTS) {
      // Límite alcanzado previamente
      pr_invalidate_token($pdo, $currentTokenHash, $userId);
      $error = set_generic_expired_msg();
      $validToken = false;
    } else {
      $terminal = false; // si se vuelve true, ocultamos el formulario

      // 1) Campos vacíos → cuenta intento
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

      // 2) No coinciden → cuenta intento
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

      // 3) Política no cumplida → cuenta intento
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
          // 4) OK → actualiza contraseña
          try {
            $pdo->beginTransaction();

            $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE usuario SET clave = ? WHERE idusuario = ?')->execute([$newPasswordHash, $userId]);

            // marcar usado e invalidar tokens
            $pdo->prepare('UPDATE password_reset SET used = 1 WHERE token_hash = ?')->execute([$currentTokenHash]);
            $pdo->prepare('DELETE FROM password_reset WHERE user_id = ?')->execute([$userId]);

            $pdo->commit();

            pr_reset_attempts($pdo, $currentTokenHash);

            $message = 'Tu contraseña ha sido actualizada correctamente. Ahora puedes iniciar sesión.';
            $validToken = false; // ocultar formulario
          } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'No se pudo actualizar la contraseña. Intenta nuevamente.';
          }
        }
      }

      // Mostrar u ocultar el formulario según si el token quedó válido
      if (!$message) {
        $validToken = !$terminal; // si terminal=true, ocultamos todo
      }
    }
  }

  // Relee intentos para el banner si aún es válido
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
    .req { display:flex; align-items:center; gap:8px; font-size:.85rem; margin:4px 0; }
    .req i{ width:16px; text-align:center; font-style:normal; }
    .req.bad{ color:#ef4444; } .req.bad i::before { content:'✗'; }
    .req.ok{ color:#10b981; }  .req.ok i::before { content:'✓'; }
    .input-eye { position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; opacity:.7; user-select:none; font-size:1.2rem; }
    .input-eye:hover { opacity:1; }
    .input-wrap { position:relative; }
    .alert { padding: 12px; margin-bottom: 16px; border-radius: 8px; }
    .alert-error { background: #fed7d7; color: #742a2a; border-left: 4px solid #f56565; }
    .alert-success { background: #c6f6d5; color: #22543d; border-left: 4px solid #48bb78; }
    .auth-form { max-height: 70vh; overflow-y: auto; padding-right: 10px; }

    /* Minibanner intentos */
    .tries-wrap{margin:0 0 12px; padding:8px 10px; border-radius:8px; background:#111827; color:#e5e7eb; border:1px solid #374151}
    .tries-top{display:flex; justify-content:space-between; font-size:.85rem; margin-bottom:6px}
    .tries-bar{height:6px; width:100%; background:#1f2937; border-radius:999px; overflow:hidden}
    .tries-fill{height:100%; width:0; background:#3b82f6; transition:width .3s ease}
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
                <input id="pwd" type="password" name="password" placeholder="••••••••" required autocomplete="new-password" aria-describedby="pwdHelp" style="width:90%;">
                <span class="input-eye" id="togglePwd" title="Ver/Ocultar">👁️</span>
              </div>
              <small id="pwdHelp" class="hint">Debe cumplir todos los requisitos:</small>
              <div id="rules" style="margin-top:8px;">
                <div class="req bad" id="r-len"><i></i> 10–64 caracteres</div>
                <div class="req bad" id="r-up"><i></i> Al menos 1 mayúscula (A-Z)</div>
                <div class="req bad" id="r-low"><i></i> Al menos 1 minúscula (a-z)</div>
                <div class="req bad" id="r-num"><i></i> Al menos 1 número (0-9)</div>
                <div class="req bad" id="r-spe"><i></i> Al menos 1 especial (!@#$%^&*)</div>
                <div class="req bad" id="r-spc"><i></i> Sin espacios</div>
                <div class="req bad" id="r-pii"><i></i> No contiene correo/nombres</div>
                <div class="req bad" id="r-common"><i></i> No es contraseña común</div>
              </div>
            </label>

            <label class="field">
              <span class="field-label">Confirmar contraseña</span>
              <div class="input-wrap">
                <input id="pwd2" type="password" name="confirm_password" placeholder="••••••••" required autocomplete="new-password" style="width:90%;">
                <span class="input-eye" id="togglePwd2" title="Ver/Ocultar">👁️</span>
              </div>
            </label>

            <button id="submitBtn" type="submit" class="btn btn-primary w-full">Restablecer contraseña</button>
          </form>
        <?php endif; ?>
      </div>
    </section>
  </div>

<script>
// Ver/ocultar
function togglePass(id, btnId){
  const input = document.getElementById(id);
  const btn = document.getElementById(btnId);
  if (input && btn) btn.addEventListener('click', ()=>{ input.type = (input.type==='password'?'text':'password'); });
}
togglePass('pwd','togglePwd'); togglePass('pwd2','togglePwd2');

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

// Validación en vivo (cliente)
(function(){
  const pwd = document.getElementById('pwd');
  const pwd2 = document.getElementById('pwd2');
  const common = new Set(['123456','123456789','12345678','12345','qwerty','password','111111','abc123','123123','iloveyou','admin','welcome','monkey','dragon','qwertyuiop','000000']);
  function mark(id, ok){ const el=document.getElementById(id); if(!el) return; el.classList.toggle('ok', ok); el.classList.toggle('bad', !ok); }
  function strongCheck(v){
    const len = v.length>=10 && v.length<=64,
          up=/[A-Z]/.test(v), low=/[a-z]/.test(v),
          num=/[0-9]/.test(v), spe=/[!@#$%^&*()_\+\=\-\[\]{};:,.?]/.test(v),
          spc=!/\s/.test(v);
    const notCommon = !common.has(v.toLowerCase());
    mark('r-len',len); mark('r-up',up); mark('r-low',low); mark('r-num',num);
    mark('r-spe',spe); mark('r-spc',spc); mark('r-pii',true); mark('r-common',notCommon);
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
