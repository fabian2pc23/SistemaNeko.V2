<?php
// src/register.php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

$error = '';
$success = '';

/* ======================= Validadores ======================= */
function validar_dni(string $doc): bool { return (bool)preg_match('/^[0-9]{8}$/', $doc); }
function validar_ruc(string $doc): bool {
  if (!preg_match('/^[0-9]{11}$/', $doc)) return false;
  $factors = [5,4,3,2,7,6,5,4,3,2];
  $sum = 0;
  for ($i=0; $i<10; $i++) { $sum += ((int)$doc[$i]) * $factors[$i]; }
  $resto  = $sum % 11;
  $digito = 11 - $resto;
  if ($digito === 10) $digito = 0;
  elseif ($digito === 11) $digito = 1;
  return $digito === (int)$doc[10];
}
function validar_pasaporte(string $doc): bool { return (bool)preg_match('/^[A-Za-z0-9]{9,12}$/', $doc); }

function validar_patron_email(string $email): ?string {
  $partes = explode('@', $email);
  if (count($partes) !== 2) return 'Formato de email inválido';
  
  $local = strtolower($partes[0]);
  $local_len = strlen($local);
  
  // ============================================================
  // LISTAS NEGRAS - SOLO PALABRAS EXACTAS
  // ============================================================
  
  // Contenido inapropiado - SOLO coincidencia EXACTA
  $blacklist_inappropriate = [
    'pene', 'pito', 'verga', 'pija', 'chota', 'polla', 'picha', 'pichula', 'pichulon',
    'concha', 'cono', 'chocho', 'vagina', 'sexo', 'sex', 'porn', 'porno', 'xxx',
    'culo', 'ass', 'coño', 'pussy', 'dick', 'cock', 'tits', 'boobs', 'puta', 'puto',
    'mierda', 'shit', 'fuck', 'cunt', 'bitch', 'whore', 'slut',
    'nude', 'naked', 'desnudo', 'anal', 'oral', 'dildo',
    'sexx', 'sexxo', 'sexy', 'p0rn', 'pr0n', 'fck', 'fuk',
    'gamp',
  ];
  
  // Genéricos y prueba
  $blacklist_generic = [
    'test', 'testing', 'demo', 'admin', 'user', 'usuario', 
    'correo', 'email', 'example', 'sample', 'fake', 'temp', 
    'spam', 'dummy', 'prueba', 'noresponder', 'noreply',
    'asdf', 'qwerty', 'abc', 'xyz', 'xxx', 'aaa', 'zzz',
    'foo', 'bar', 'baz', 'qux', 'root',
    'gay', 'hot', 'wtf', 'lol', 'omg', 'win', 'fail',
  ];
  
  $blacklist_combined = array_merge($blacklist_inappropriate, $blacklist_generic);
  
  // ============================================================
  // VALIDACIÓN 1: LONGITUD MÍNIMA (5 caracteres)
  // ============================================================
  if ($local_len < 5) {
    return 'El nombre de usuario es muy corto (mínimo 5 caracteres)';
  }
  
  // ============================================================
  // VALIDACIÓN 2: CONTENIDO INAPROPIADO (SOLO EXACTO)
  // ============================================================
  $local_clean = explode('+', $local)[0]; // Remover +tag
  
  // Verificación EXACTA solamente (no buscar dentro)
  if (in_array($local_clean, $blacklist_combined, true)) {
    return 'Este correo no es válido para registro';
  }
  
  // Verificación leetspeak solo para coincidencias EXACTAS
  $local_normalized = str_replace(
    ['0', '1', '3', '4', '5', '7', '8'], 
    ['o', 'i', 'e', 'a', 's', 't', 'b'], 
    $local_clean
  );
  
  if (in_array($local_normalized, $blacklist_inappropriate, true)) {
    return 'Este correo contiene contenido no permitido';
  }
  
  // ============================================================
  // VALIDACIÓN 3: CARACTERES REPETIDOS
  // ============================================================
  if (preg_match('/(.)\1{2,}/', $local)) {
    return 'El correo contiene demasiados caracteres repetitivos consecutivos';
  }
  
  // ============================================================
  // VALIDACIÓN 4: PUNTOS INVÁLIDOS
  // ============================================================
  if ($local[0] === '.' || substr($local, -1) === '.') {
    return 'El correo no puede empezar ni terminar con punto';
  }
  if (strpos($local, '..') !== false) {
    return 'El correo no puede contener puntos consecutivos';
  }
  
  // ============================================================
  // VALIDACIÓN 5: DIVERSIDAD DE CARACTERES (mínimo 3)
  // ============================================================
  $unique_chars = count(array_unique(str_split($local)));
  if ($unique_chars < 3) {
    return 'El correo es demasiado simple (necesita más variedad)';
  }
  
  // ============================================================
  // VALIDACIÓN 6: PATRONES OBVIOS DE PRUEBA
  // ============================================================
  $patrones_obvios = [
    '/^x{3,}$/',           // xxx, xxxx
    '/^a{3,}$/',           // aaa, aaaa
    '/^z{3,}$/',           // zzz, zzzz
    '/^\d{1,3}$/',         // 1, 12, 123
    '/^test\d{1,3}$/i',    // test1, test12
    '/^user\d{1,3}$/i',    // user1, user12
    '/^demo\d{1,3}$/i',    // demo1, demo12
  ];
  
  foreach ($patrones_obvios as $p) {
    if (preg_match($p, $local)) {
      return 'El correo parece ser de prueba';
    }
  }
  
  // ============================================================
  // VALIDACIÓN 7: SOLO NÚMEROS MUY CORTOS
  // ============================================================
  if (preg_match('/^\d+$/', $local) && $local_len < 8) {
    return 'Email numérico poco confiable (mínimo 8 dígitos)';
  }
  
  // ============================================================
  // VALIDACIÓN 8: SECUENCIAS ALFABÉTICAS/NUMÉRICAS
  // ============================================================
  if (preg_match('/abcd|bcde|cdef|defg|wxyz|stuv/i', $local)) {
    return 'El correo contiene secuencias alfabéticas sospechosas';
  }
  
  if (preg_match('/1234|2345|3456|4567|5678|6789|9876|8765|7654|6543|5432|4321/', $local)) {
    return 'El correo contiene secuencias numéricas sospechosas';
  }
  
  // ============================================================
  // VALIDACIÓN 9: PALABRAS DE BASURA
  // ============================================================
  $basura_patterns = [
    '/^temporal\d{0,2}$/i',
    '/^basura\d{0,2}$/i',
    '/^fake\d{0,2}$/i',
    '/^noname\d{0,2}$/i',
    '/^random\d{0,2}$/i',
  ];
  
  foreach ($basura_patterns as $p) {
    if (preg_match($p, $local)) {
      return 'El correo parece ser temporal o de prueba';
    }
  }
  
  // Si pasó todas las validaciones, el correo es válido
  return null;
}

function validar_email_real(string $email): ?string {
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return 'El formato del correo no es válido.';
  $p = validar_patron_email($email); if ($p!==null) return $p;
  [$local,$domain] = explode('@', $email, 2);
  if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) return 'El dominio del correo no existe o no puede recibir emails.';
  $disposable = ['tempmail.com','guerrillamail.com','10minutemail.com','throwaway.email','mailinator.com','trashmail.com','yopmail.com','maildrop.cc','temp-mail.org','fakeinbox.com','sharklasers.com','guerrillamailblock.com','pokemail.net','spam4.me','grr.la','dispostable.com','tempinbox.com','minuteinbox.com','emailondeck.com','mytemp.email','mohmal.com','moakt.com'];
  if (in_array(strtolower($domain), $disposable, true)) return 'No se permiten correos temporales o desechables.';
  return null;
}

function validar_password_robusta(string $pwd, string $email='', string $nombres='', string $apellidos=''): ?string {
  if (strlen($pwd) < 10 || strlen($pwd) > 64) return 'La contraseña debe tener entre 10 y 64 caracteres.';
  if (preg_match('/\s/', $pwd)) return 'La contraseña no debe contener espacios.';
  if (!preg_match('/[A-Z]/', $pwd)) return 'Debe incluir al menos una letra mayúscula (A-Z).';
  if (!preg_match('/[a-z]/', $pwd)) return 'Debe incluir al menos una letra minúscula (a-z).';
  if (!preg_match('/[0-9]/', $pwd)) return 'Debe incluir al menos un dígito (0-9).';
  if (!preg_match('/[!@#$%^&*()_\+\=\-\[\]{};:,.?]/', $pwd)) return 'Debe incluir al menos un caracter especial: !@#$%^&*()_+=-[]{};:,.?';
  $lowerPwd = mb_strtolower($pwd,'UTF-8'); $prohibidos=[];
  if ($email){ $local = mb_strtolower((string)strtok($email,'@'),'UTF-8'); if ($local) $prohibidos[]=$local; }
  foreach (preg_split('/\s+/', trim($nombres.' '.$apellidos)) as $pieza){ $pieza = mb_strtolower($pieza,'UTF-8'); if (mb_strlen($pieza,'UTF-8')>=4) $prohibidos[]=$pieza; }
  foreach ($prohibidos as $p){ if($p!=='' && mb_strpos($lowerPwd,$p,0,'UTF-8')!==false) return 'No debe contener partes de tu correo, nombres o apellidos.'; }
  $comunes=['123456','123456789','12345678','12345','qwerty','password','111111','abc123','123123','iloveyou','admin','welcome','monkey','dragon','qwertyuiop','000000'];
  if (in_array(mb_strtolower($pwd,'UTF-8'), $comunes, true)) return 'La contraseña es demasiado común. Elige otra.';
  return null;
}

/* =============== Catálogos =============== */
$tiposDoc = $pdo->query('SELECT id_tipodoc, nombre FROM tipo_documento ORDER BY id_tipodoc')->fetchAll();

/* =============== Valores del form =============== */
$id_tipodoc    = (int)($_POST['id_tipodoc'] ?? 0);
$nro_documento = trim($_POST['nro_documento'] ?? '');
$nombres       = trim($_POST['nombres'] ?? '');
$apellidos     = trim($_POST['apellidos'] ?? '');
$empresa       = trim($_POST['empresa'] ?? '');
$email         = trim($_POST['email'] ?? '');
$telefono      = trim($_POST['telefono'] ?? '');
$direccion     = trim($_POST['direccion'] ?? '');

/* ===== Normalización defensiva por tipo de documento ===== */
if (in_array($id_tipodoc, [1,2], true)) {
  // DNI o RUC: deja solo dígitos
  $nro_documento = preg_replace('/\D+/', '', $nro_documento ?? '');
}

/* ===== Helpers: resolver dirección con RENIEC/SUNAT ===== */
function try_resolve_direccion(int $id_tipodoc, string $numero): ?string {
  $TOKEN = getenv('MIAPI_CLOUD_TOKEN')
    ?: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjo0MDEsImV4cCI6MTc2MTU0MTQxMn0.5M179k5ws4tayquMwg_yfVdbybQCDkKaTPUu6Dibt_E';
  if (!function_exists('curl_init')) return null;
  $url = $id_tipodoc === 2 ? "https://miapi.cloud/v1/ruc/{$numero}" : "https://miapi.cloud/v1/dni/{$numero}";
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>12, CURLOPT_SSL_VERIFYPEER=>true,
    CURLOPT_HTTPHEADER=>["Authorization: Bearer {$TOKEN}","Accept: application/json"]
  ]);
  $body = curl_exec($ch); $code=(int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
  if (!$body || $code<200 || $code>=300) return null;
  $j = json_decode((string)$body, true);
  if (!is_array($j) || !($j['success'] ?? false) || empty($j['datos'])) return null;
  $d = $j['datos'];
  $take = function($v){
    if (is_string($v)) return trim($v);
    if (!is_array($v)) return '';
    foreach (['direccion','domicilio_fiscal','domicilio'] as $k) if (!empty($v[$k])) return trim((string)$v[$k]);
    $parts=[]; foreach (['via','tipo_via','nombre_via','calle','jr','avenida','mz','lote','numero','nro','km','interior','dpto','piso','referencia'] as $k) if(!empty($v[$k])) $parts[] = trim((string)$v[$k]);
    $base = trim(implode(' ',$parts)); $geo=[];
    foreach (['distrito','provincia','departamento'] as $k) if(!empty($v[$k])) $geo[] = trim((string)$v[$k]);
    return $geo ? ($base ? ($base.', '.implode(' - ',$geo)) : implode(' - ',$geo)) : $base;
  };
  if ($id_tipodoc===2){ $addr = $take($d['direccion'] ?? '') ?: $take($d['domicilio_fiscal'] ?? []) ?: $take($d['domiciliado'] ?? []) ?: $take($d['domicilio'] ?? []); }
  else { $addr = $take($d['domiciliado'] ?? []) ?: $take($d['domicilio'] ?? []) ?: $take($d['direccion'] ?? ''); }
  return $addr ?: null;
}

/* =============== POST =============== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $password = $_POST['password'] ?? '';
  $confirm  = $_POST['confirm'] ?? '';

  if (!$id_tipodoc || !$nro_documento || !$email || !$password || !$confirm) {
    $error = 'Todos los campos obligatorios deben ser completados.';
  }

  if ($error === '') {
    $emailError = validar_email_real($email);
    if ($emailError !== null) {
      $error = $emailError;
    } elseif ($password !== $confirm) {
      $error = 'Las contraseñas no coinciden.';
    } else {
      $okDoc = false;
      if     ($id_tipodoc === 1) $okDoc = validar_dni($nro_documento);
      elseif ($id_tipodoc === 2) $okDoc = validar_ruc($nro_documento);
      elseif ($id_tipodoc === 3) $okDoc = validar_pasaporte($nro_documento);

      if (!$okDoc) {
        $error = 'Número de documento inválido para el tipo seleccionado.';
      } else {
        if ($id_tipodoc === 2) {
          if ($empresa === '') $error = 'La razón social no fue completada. Usa el autocompletado por SUNAT.';
          else { $nombres = $empresa; $apellidos = ''; }
        } else {
          if ($nombres === '' || $apellidos === '') $error = 'Nombres y apellidos son obligatorios (usa el autocompletado).';
        }

        if ($error === '') {
          // TELÉFONO (Perú): 9 dígitos, empieza con 9
          if ($telefono !== '' && !preg_match('/^9\d{8}$/', $telefono)) {
            $error = 'Teléfono no válido. Debe tener exactamente 9 dígitos.';
          } elseif ($direccion !== '' && mb_strlen($direccion,'UTF-8') > 70) {
            $error = 'Dirección demasiado larga (máx 70).';
          }
        }

        if ($error === '') {
          $errPwd = validar_password_robusta($password, $email, $nombres, $apellidos);
          if ($errPwd !== null) $error = $errPwd;
        }

        if ($error === '') {
          // Duplicados
          $dup = $pdo->prepare('
            SELECT 
              CASE 
                WHEN email = ? THEN "email"
                WHEN id_tipodoc = ? AND num_documento = ? THEN "documento"
                ELSE "otro"
              END as tipo_duplicado
            FROM usuario
            WHERE email = ?
               OR (id_tipodoc = ? AND num_documento = ?)
            LIMIT 1
          ');
          $dup->execute([$email,$id_tipodoc,$nro_documento,$email,$id_tipodoc,$nro_documento]);
          $duplicado = $dup->fetch();

          if ($duplicado) {
            if     ($duplicado['tipo_duplicado']==='email')     $error = 'Este correo electrónico ya está registrado. ¿Olvidaste tu contraseña?';
            elseif ($duplicado['tipo_duplicado']==='documento') $error = 'Este documento ya está registrado. Una persona no puede registrarse dos veces.';
            else                                                $error = 'Ya existe una cuenta con estos datos.';
          } else {
            // Resolver dirección si vino vacía
            if ($direccion === '' && ($id_tipodoc===1 || $id_tipodoc===2)) {
              $resolved = try_resolve_direccion($id_tipodoc, $nro_documento);
              if ($resolved) $direccion = $resolved;
            }

            // Denormalizar nombre del tipo de doc
            $qTipo = $pdo->prepare('SELECT nombre FROM tipo_documento WHERE id_tipodoc = ? LIMIT 1');
            $qTipo->execute([$id_tipodoc]);
            $tipo_documento_nombre = ($qTipo->fetch(PDO::FETCH_ASSOC)['nombre'] ?? null);

            // Hash seguro
            $hash = password_hash($password, PASSWORD_BCRYPT);

            // Nombre final
            $nombreFinal = ($id_tipodoc === 2) ? $empresa : trim($nombres . ' ' . $apellidos);

            try {
              $pdo->beginTransaction();

              // id_rol = NULL, condicion = 3 (pendiente de aprobación)
              $ins = $pdo->prepare('
                INSERT INTO usuario
                  (id_tipodoc, tipo_documento, num_documento, id_rol, nombre, email, clave, telefono, direccion, cargo, imagen, condicion)
                VALUES
                  (?,          ?,              ?,        NULL,   ?,      ?,     ?,     ?,        ?,        NULL,  "default.png", 3)
              ');
              $ins->execute([
                $id_tipodoc,
                $tipo_documento_nombre,
                $nro_documento,
                $nombreFinal,
                $email,
                $hash,
                $telefono,
                $direccion
              ]);

              $pdo->commit();
              $success = 'Registro enviado. Tu cuenta está pendiente de aprobación por el administrador.';
              $id_tipodoc = 0;
              $nro_documento = $nombres = $apellidos = $empresa = $email = $telefono = $direccion = '';
            } catch (Exception $e) {
              $pdo->rollBack();
              $error = 'Error al registrar: ' . $e->getMessage();
            }
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
  <title>Registro - Sistema Neko</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="css/estilos.css?v=<?= time() ?>">
  <style>
    /* ========== ESTILOS PROFESIONALES Y CORPORATIVOS ========== */
    
    /* Variables CSS para modo claro/oscuro */
    :root {
      --text-error: #dc2626;
      --text-error-light: #fca5a5;
      --text-success: #059669;
      --text-success-light: #6ee7b7;
      --text-info: #3b82f6;
      --text-info-light: #93c5fd;
      --text-muted: #64748b;
      --text-muted-light: #94a3b8;
      --text-label: #1e293b;
      --text-label-light: #e2e8f0;
      
      --bg-rules: #f8fafc;
      --bg-rules-dark: rgba(30, 41, 59, 0.4);
      --bg-req-error: rgba(220, 38, 38, 0.05);
      --bg-req-error-dark: rgba(220, 38, 38, 0.15);
      --bg-req-success: rgba(5, 150, 105, 0.05);
      --bg-req-success-dark: rgba(5, 150, 105, 0.15);
      
      --eye-bg: rgba(148, 163, 184, 0.1);
      --eye-bg-hover: rgba(148, 163, 184, 0.18);
      --eye-border: rgba(148, 163, 184, 0.2);
      --eye-border-hover: rgba(148, 163, 184, 0.35);
      --eye-icon: #64748b;
      --eye-icon-hover: #475569;
      --eye-icon-active: #2563eb;
      
      --btn-edit-bg: linear-gradient(135deg, #f8fafc, #f1f5f9);
      --btn-edit-border: #cbd5e1;
      --btn-edit-color: #475569;
    }
    
    /* Detección automática de modo oscuro */
    @media (prefers-color-scheme: dark) {
      :root {
        --text-error: #fca5a5;
        --text-success: #6ee7b7;
        --text-info: #93c5fd;
        --text-muted: #94a3b8;
        --text-label: #e2e8f0;
        --bg-rules: var(--bg-rules-dark);
        --bg-req-error: var(--bg-req-error-dark);
        --bg-req-success: var(--bg-req-success-dark);
        
        --eye-bg: rgba(148, 163, 184, 0.15);
        --eye-bg-hover: rgba(148, 163, 184, 0.25);
        --eye-border: rgba(148, 163, 184, 0.25);
        --eye-border-hover: rgba(148, 163, 184, 0.4);
        --eye-icon: #94a3b8;
        --eye-icon-hover: #cbd5e1;
        --eye-icon-active: #60a5fa;
        
        --btn-edit-bg: rgba(148, 163, 184, 0.15);
        --btn-edit-border: rgba(148, 163, 184, 0.3);
        --btn-edit-color: #cbd5e1;
      }
    }
    
    /* Forzar modo oscuro si el body tiene clase dark */
    body.dark,
    .dark-mode,
    [data-theme="dark"] {
      --text-error: #fca5a5;
      --text-success: #6ee7b7;
      --text-info: #93c5fd;
      --text-muted: #94a3b8;
      --text-label: #e2e8f0;
      --bg-rules: var(--bg-rules-dark);
      --bg-req-error: var(--bg-req-error-dark);
      --bg-req-success: var(--bg-req-success-dark);
      
      --eye-bg: rgba(148, 163, 184, 0.15);
      --eye-bg-hover: rgba(148, 163, 184, 0.25);
      --eye-border: rgba(148, 163, 184, 0.25);
      --eye-border-hover: rgba(148, 163, 184, 0.4);
      --eye-icon: #94a3b8;
      --eye-icon-hover: #cbd5e1;
      --eye-icon-active: #60a5fa;
      
      --btn-edit-bg: rgba(148, 163, 184, 0.15);
      --btn-edit-border: rgba(148, 163, 184, 0.3);
      --btn-edit-color: #cbd5e1;
    }
    
    /* Iconos de validación con estilo corporativo */
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

    /* Botón de ojo empresarial - PERFECTAMENTE AJUSTADO */
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

    /* Icono del ojo - MEJORADO Y MÁS VISIBLE */
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
    
    /* Cuando está activo (mostrando contraseña) */
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

    /* Contenedor de input mejorado */
    .input-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }
    
    .input-wrap input {
      width: 100%;
      padding-right: 44px !important;
    }

    /* Estado de validación visual en inputs */
    input.validating {
      border-color: #60a5fa !important;
      box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.15);
    }
    
    input.valid {
      border-color: #34d399 !important;
    }
    
    input.invalid {
      border-color: #f87171 !important;
    }
    
    /* Iconos de validación en inputs */
    @media (prefers-color-scheme: dark), (prefers-color-scheme: no-preference) {
      body.dark input.valid,
      .dark-mode input.valid,
      [data-theme="dark"] input.valid {
        background-image: url("data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M13.5 4.5L6 12L2.5 8.5' stroke='%2334d399' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        background-size: 18px;
      }
      
      body.dark input.invalid,
      .dark-mode input.invalid,
      [data-theme="dark"] input.invalid {
        background-image: url("data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M12 4L4 12M4 4L12 12' stroke='%23f87171' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        background-size: 18px;
      }
    }
    
    /* Modo claro */
    input.valid {
      background-image: url("data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M13.5 4.5L6 12L2.5 8.5' stroke='%23059669' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 14px center;
      background-size: 18px;
    }
    
    input.invalid {
      background-image: url("data:image/svg+xml,%3Csvg width='16' height='16' viewBox='0 0 16 16' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M12 4L4 12M4 4L12 12' stroke='%23dc2626' stroke-width='2' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 14px center;
      background-size: 18px;
    }

    /* Botón de edición mejorado */
    .btn-edit {
      min-width: 90px;
      height: 42px;
      background: var(--btn-edit-bg);
      border: 1.5px solid var(--btn-edit-border);
      border-radius: 8px;
      color: var(--btn-edit-color);
      font-weight: 600;
      font-size: 0.875rem;
      cursor: pointer;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      white-space: nowrap;
      padding: 0 16px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
    
    .btn-edit:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15);
    }
    
    .btn-edit:active {
      transform: translateY(0);
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }
    
    .btn-edit.active {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      border-color: #2563eb;
      color: white;
      box-shadow: 0 4px 8px rgba(59, 130, 246, 0.4);
    }

    /* Estado de carga profesional */
    .loading-spinner {
      display: inline-block;
      width: 16px;
      height: 16px;
      border: 2px solid rgba(148, 163, 184, 0.3);
      border-top-color: var(--text-muted);
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }
    
    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* Indicadores de estado mejorados */
    .status-indicator {
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 18px;
      pointer-events: none;
      transition: all 0.3s ease;
    }
    
    .status-indicator.loading {
      animation: pulse 1.5s ease-in-out infinite;
    }
    
    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.5; }
    }

    /* Hints mejorados - CON VARIABLES */
    .hint {
      display: block;
      margin-top: 6px;
      font-size: 0.8125rem;
      color: var(--text-muted);
      transition: all 0.3s ease;
      font-weight: 500;
    }
    
    .hint.success {
      color: var(--text-success);
    }
    
    .hint.error {
      color: var(--text-error);
    }
    
    .hint.info {
      color: var(--text-info);
    }

    /* Contenedor de reglas de contraseña - CON VARIABLES */
    #rules {
      margin-top: 12px;
      padding: 12px;
      background: var(--bg-rules);
      border-radius: 8px;
      border: 1px solid rgba(148, 163, 184, 0.2);
    }

    /* Ocultar elementos */
    .hidden {
      display: none !important;
    }

    /* Scrollbar personalizado */
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
      transition: background 0.3s ease;
    }
    
    .auth-form::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(180deg, #64748b, #475569);
    }

    /* Campo de formulario */
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

    /* Alertas mejoradas */
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

    /* Mejoras en inputs */
    input, select {
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    input:focus, select:focus {
      outline: none;
      border-color: #60a5fa;
      box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.15);
    }

    /* Responsive */
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
        top: 3px;
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
      
      .btn-edit {
        min-width: 80px;
        height: 38px;
        font-size: 0.8125rem;
      }
    }
    
    /* Fix para que el botón nunca sobresalga en ningún tamaño */
    @media (max-width: 480px) {
      .input-eye {
        width: 26px;
        height: 26px;
        min-width: 26px;
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
          <img src="assets/logo.png" alt="Logo Neko" class="brand-logo">
          <h1 class="brand-title">Registro</h1>
          <p class="brand-sub">¿Ya tienes cuenta?</p>
          <a class="btn btn-outline" href="login.php">Iniciar Sesión</a>
        </div>
      </div>

      <div class="auth-right">
        <h2 class="auth-title">Crear cuenta</h2>

        <?php if ($error): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
          <div class="alert alert-success">
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            <br><br>
            <a href="login.php" class="btn btn-primary w-full">Ir al Login</a>
          </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="post" action="register.php" class="auth-form" autocomplete="off" novalidate>
          <!-- Tipo documento -->
          <label class="field">
            <span class="field-label">Tipo de documento *</span>
            <select id="tipodoc" name="id_tipodoc" required>
              <option value="">Seleccione…</option>
              <?php foreach ($tiposDoc as $td): ?>
                <option value="<?= (int)$td['id_tipodoc'] ?>" <?= ((int)$td['id_tipodoc']===$id_tipodoc?'selected':'') ?>>
                  <?= htmlspecialchars($td['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="field">
            <span class="field-label">Nro. de documento *</span>
            <input
              id="nrodoc"
              type="text"
              name="nro_documento"
              value="<?= htmlspecialchars($nro_documento) ?>"
              required
              autocomplete="off"
            >
            <small id="hintdoc" class="hint"></small>
          </label>

          <!-- Empresa (solo RUC) -->
          <label class="field <?= $id_tipodoc===2 ? '' : 'hidden' ?>" id="wrap-empresa">
            <span class="field-label">Razón social / Nombre de la empresa *</span>
            <input id="empresa" type="text" name="empresa" value="<?= htmlspecialchars($empresa) ?>" placeholder="Autocompletado por SUNAT" readonly>
          </label>

          <!-- Persona (DNI/Pasaporte) -->
          <label class="field <?= $id_tipodoc===2 ? 'hidden' : '' ?>" id="wrap-nombres">
            <span class="field-label">Nombres *</span>
            <input id="nombres" type="text" name="nombres" value="<?= htmlspecialchars($nombres) ?>" placeholder="Autocompletado por RENIEC" readonly>
          </label>
          <label class="field <?= $id_tipodoc===2 ? 'hidden' : '' ?>" id="wrap-apellidos">
            <span class="field-label">Apellidos *</span>
            <input id="apellidos" type="text" name="apellidos" value="<?= htmlspecialchars($apellidos) ?>" placeholder="Autocompletado por RENIEC" readonly>
          </label>

          <!-- EMAIL -->
          <label class="field">
            <span class="field-label">Correo electrónico *</span>
            <div style="position:relative;">
              <input id="email" type="email" name="email" value="<?= htmlspecialchars($email) ?>" style="width:100%;" required>
              <span id="email-status" class="status-indicator"></span>
            </div>
            <small id="email-hint" class="hint">Usarás este correo para iniciar sesión</small>
          </label>

          <!-- TELÉFONO -->
          <label class="field">
            <span class="field-label">Teléfono</span>
            <input
              id="telefono"
              type="text"
              name="telefono"
              value="<?= htmlspecialchars($telefono) ?>"
              placeholder="Teléfono (Opcional)"
              inputmode="numeric"
              maxlength="9"
              pattern="^9\d{8}$"
            >
            <small id="tel-hint" class="hint">Debe ser solo números, 9 dígitos, comenzar con 9</small>
          </label>

          <label class="field">
            <span class="field-label">Dirección (se autocompleta)</span>
            <div style="display:flex; gap:10px; align-items:center;">
              <input id="direccion" type="text" name="direccion" value="<?= htmlspecialchars($direccion) ?>" placeholder="Se llenará con RENIEC/SUNAT" style="flex:1;" readonly>
              <button type="button" id="editDir" class="btn-edit">
                <span class="btn-text">Editar</span>
              </button>
            </div>
            <small class="hint">Si lo prefieres, puedes editar manualmente</small>
          </label>

          <label class="field">
            <span class="field-label">Contraseña *</span>
            <div class="input-wrap">
              <input id="pwd" type="password" name="password" required aria-describedby="pwdHelp">
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
            <span class="field-label">Confirmar contraseña *</span>
            <div class="input-wrap">
              <input id="pwd2" type="password" name="confirm" required>
              <button type="button" class="input-eye" id="togglePwd2" title="Ver/Ocultar contraseña">
                <span class="eye-icon"></span>
              </button>
            </div>
          </label>

          <button type="submit" class="btn btn-primary w-full">Crear cuenta</button>
          <p class="small text-center m-top">¿Ya tienes cuenta? <a href="login.php" class="link-strong">Inicia sesión</a></p>
        </form>
        <?php endif; ?>
      </div>
    </section>
  </div>

<script>
// Toggle edición dirección (con control de "dirty")
const direccionInput = document.getElementById('direccion');
const editDirBtn = document.getElementById('editDir');
if (direccionInput) {
  direccionInput.addEventListener('input', () => { direccionInput.dataset.dirty = '1'; });
}
if (editDirBtn && direccionInput) {
  editDirBtn.addEventListener('click', () => {
    const ro = direccionInput.hasAttribute('readonly');
    if (ro) { 
      direccionInput.removeAttribute('readonly'); 
      editDirBtn.classList.add('active');
      editDirBtn.querySelector('.btn-text').textContent = 'Bloquear'; 
      direccionInput.focus(); 
    } else { 
      direccionInput.setAttribute('readonly','readonly'); 
      editDirBtn.classList.remove('active');
      editDirBtn.querySelector('.btn-text').textContent = 'Editar';
      if (direccionInput.value === (window.lastAutoDir || '')) delete direccionInput.dataset.dirty;
    }
  });
}

// ================= Máscara dinámica por tipo de documento =================
const tipodoc = document.getElementById('tipodoc');
const nrodoc  = document.getElementById('nrodoc');
const hint    = document.getElementById('hintdoc');
const wrapEmp = document.getElementById('wrap-empresa');
const wrapNom = document.getElementById('wrap-nombres');
const wrapApe = document.getElementById('wrap-apellidos');
const nombres = document.getElementById('nombres');
const apellidos = document.getElementById('apellidos');
const empresa = document.getElementById('empresa');

// bloquea teclas no numéricas cuando corresponde
function allowOnlyDigitsKeydown(ev){
  const allowed = ['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End'];
  if (allowed.includes(ev.key)) return;
  if (ev.ctrlKey || ev.metaKey) return;
  if (!/^\d$/.test(ev.key)) ev.preventDefault();
}

function sanitizeDigitsMaxLen(input, maxLen){
  let v = input.value.replace(/\D+/g,'');
  if (v.length > maxLen) v = v.slice(0, maxLen);
  input.value = v;
}

function setupDocMask(){
  const t = parseInt(tipodoc.value || '0', 10);
  nrodoc.onkeydown = null;
  nrodoc.removeAttribute('pattern');
  nrodoc.removeAttribute('maxlength');
  nrodoc.removeAttribute('inputmode');
  nrodoc.placeholder = '';
  nrodoc.classList.remove('valid', 'invalid', 'validating');

  if (t === 1){ // DNI
    nrodoc.setAttribute('pattern','^[0-9]{8}$');
    nrodoc.maxLength = 8;
    nrodoc.setAttribute('inputmode','numeric');
    nrodoc.placeholder = 'DNI: 8 dígitos';
    hint.textContent = 'DNI: 8 dígitos (solo números)';
    hint.className = 'hint';
    nrodoc.onkeydown = allowOnlyDigitsKeydown;
    nrodoc.addEventListener('input', () => sanitizeDigitsMaxLen(nrodoc, 8), { once:false });
    wrapEmp.classList.add('hidden'); wrapNom.classList.remove('hidden'); wrapApe.classList.remove('hidden');
  } else if (t === 2){ // RUC
    nrodoc.setAttribute('pattern','^[0-9]{11}$');
    nrodoc.maxLength = 11;
    nrodoc.setAttribute('inputmode','numeric');
    nrodoc.placeholder = 'RUC: 11 dígitos';
    hint.textContent = 'RUC: 11 dígitos (solo números)';
    hint.className = 'hint';
    nrodoc.onkeydown = allowOnlyDigitsKeydown;
    nrodoc.addEventListener('input', () => sanitizeDigitsMaxLen(nrodoc, 11), { once:false });
    wrapEmp.classList.remove('hidden'); wrapNom.classList.add('hidden'); wrapApe.classList.add('hidden');
  } else if (t === 3){ // Pasaporte
    nrodoc.setAttribute('pattern','^[A-Za-z0-9]{9,12}$');
    nrodoc.maxLength = 12;
    nrodoc.setAttribute('inputmode','text');
    nrodoc.placeholder = 'Pasaporte: 9–12 caracteres';
    hint.textContent = 'Pasaporte: 9–12 caracteres (letras y números)';
    hint.className = 'hint';
    wrapEmp.classList.add('hidden'); wrapNom.classList.remove('hidden'); wrapApe.classList.remove('hidden');
  } else {
    hint.textContent = '';
    hint.className = 'hint';
    wrapEmp.classList.add('hidden'); wrapNom.classList.remove('hidden'); wrapApe.classList.remove('hidden');
  }
}
tipodoc.addEventListener('change', setupDocMask);
document.addEventListener('DOMContentLoaded', setupDocMask);

// ================= Ver/ocultar contraseñas con animación =================
function togglePass(id, btnId){
  const input = document.getElementById(id);
  const btn = document.getElementById(btnId);
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
togglePass('pwd','togglePwd'); 
togglePass('pwd2','togglePwd2');

// ================= Validación en vivo de contraseña =================
(function(){
  const pwd = document.getElementById('pwd');
  const pwd2 = document.getElementById('pwd2');
  const email = document.getElementById('email');
  const common = new Set(['123456','123456789','12345678','12345','qwerty','password','111111','abc123','123123','iloveyou','admin','welcome','monkey','dragon','qwertyuiop','000000']);
  
  function mark(id, ok){ 
    const el = document.getElementById(id); 
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
    const lowers = v.toLowerCase(); 
    let pii = true; 
    const pieces=[];
    if (email && email.value) pieces.push((email.value.split('@')[0]||'').toLowerCase());
    (nombres.value+' '+apellidos.value).split(/\s+/).forEach(p=>{ p=p.toLowerCase(); if(p.length>=4) pieces.push(p); });
    for (const p of pieces){ if(p && lowers.includes(p)){ pii=false; break; } }
    const notCommon = !common.has(lowers);
    mark('r-len',len); mark('r-up',up); mark('r-low',low); mark('r-num',num); mark('r-spe',spe); mark('r-spc',spc); mark('r-pii',pii); mark('r-common',notCommon);
    return len&&up&&low&&num&&spe&&spc&&pii&&notCommon;
  }
  
  function syncValidity(){
    strongCheck(pwd.value);
    if (!strongCheck(pwd.value)) pwd.setCustomValidity('La contraseña no cumple los requisitos mínimos.'); 
    else pwd.setCustomValidity('');
    if (pwd2.value && pwd2.value !== pwd.value) pwd2.setCustomValidity('Las contraseñas no coinciden.'); 
    else pwd2.setCustomValidity('');
  }
  
  pwd.addEventListener('input', syncValidity); 
  pwd2.addEventListener('input', syncValidity); 
  if(email) email.addEventListener('input', syncValidity);
})();

// ====== Corrección: actualización inteligente de dirección (RENIEC/SUNAT) ======
const dirInput = document.getElementById('direccion');
window.lastAutoDir = window.lastAutoDir || '';
function setAutoDireccion(newDir) {
  if (!dirInput) return;
  const wasReadonly = dirInput.hasAttribute('readonly');
  const notManuallyEdited = !dirInput.dataset.dirty || dirInput.value === (window.lastAutoDir || '');
  if (wasReadonly || notManuallyEdited) {
    dirInput.value = newDir || '';
    window.lastAutoDir = dirInput.value;
  }
}

// RENIEC (DNI)
(function(){
  const tip = document.getElementById('tipodoc');
  let t; let inflight;
  function ready(){ return parseInt(tip.value||'0',10)===1 && /^\d{8}$/.test(nrodoc.value); }
  async function consulta(){
    if(!ready()) return;
    if (inflight) inflight.abort(); inflight = new AbortController();
    const prevN=nombres.value, prevA=apellidos.value;
    nrodoc.classList.add('validating');
    nombres.value='Consultando RENIEC...'; apellidos.value='Consultando RENIEC...';
    hint.textContent = 'Consultando RENIEC...';
    hint.className = 'hint info';
    try{
      const res = await fetch(`ajax/reniec.php?dni=${encodeURIComponent(nrodoc.value)}`, { headers:{'X-Requested-With':'fetch'}, cache:'no-store', signal: inflight.signal });
      const data = await res.json();
      if(!res.ok || data.success===false) throw new Error(data.message || 'Error al consultar');
      nombres.value = data.nombres || ''; apellidos.value = data.apellidos || '';
      if (data.direccion) setAutoDireccion(data.direccion);
      nrodoc.classList.remove('validating');
      nrodoc.classList.add('valid');
      hint.textContent = 'Datos verificados correctamente';
      hint.className = 'hint success';
    }catch(e){ 
      if (e.name !== 'AbortError'){ 
        nombres.value=prevN; apellidos.value=prevA; 
        nrodoc.classList.remove('validating');
        nrodoc.classList.add('invalid');
        hint.textContent = e.message || 'Error al consultar RENIEC';
        hint.className = 'hint error';
      } 
    }
  }
  function debounce(){ clearTimeout(t); t=setTimeout(consulta, 450); }
  tip.addEventListener('change', debounce); nrodoc.addEventListener('input', debounce); nrodoc.addEventListener('blur', consulta);
})();

// SUNAT (RUC)
(function(){
  const tip = document.getElementById('tipodoc');
  let t; let inflight;
  function ready(){ return parseInt(tip.value||'0',10)===2 && /^\d{11}$/.test(nrodoc.value); }
  async function consulta(){
    if(!ready()) return;
    if (inflight) inflight.abort(); inflight = new AbortController();
    const prev = empresa.value; 
    nrodoc.classList.add('validating');
    empresa.value = 'Consultando SUNAT...';
    hint.textContent = 'Consultando SUNAT...';
    hint.className = 'hint info';
    try{
      const res = await fetch(`ajax/sunat.php?ruc=${encodeURIComponent(nrodoc.value)}`, { headers:{'X-Requested-With':'fetch'}, cache:'no-store', signal: inflight.signal });
      const data = await res.json();
      if(!res.ok || data.success===false) throw new Error(data.message || 'Error al consultar');
      empresa.value = data.razon_social || data.nombre_o_razon_social || '';
      if (data.direccion) setAutoDireccion(data.direccion);
      nrodoc.classList.remove('validating');
      nrodoc.classList.add('valid');
      hint.textContent = 'Datos verificados correctamente';
      hint.className = 'hint success';
    }catch(e){ 
      if (e.name!=='AbortError'){ 
        empresa.value=prev; 
        nrodoc.classList.remove('validating');
        nrodoc.classList.add('invalid');
        hint.textContent = e.message || 'Error al consultar SUNAT';
        hint.className = 'hint error';
      } 
    }
  }
  function debounce(){ clearTimeout(t); t=setTimeout(consulta, 450); }
  tip.addEventListener('change', debounce); nrodoc.addEventListener('input', debounce); nrodoc.addEventListener('blur', consulta);
})();

// VALIDACIÓN DE EMAIL EN TIEMPO REAL
(function(){
  const emailInput = document.getElementById('email');
  const emailHint = document.getElementById('email-hint');
  const emailStatus = document.getElementById('email-status');
  let timer; let inflight; let lastChecked='';
  
  function isValidFormat(email){ return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email); }
  
  async function validateEmail(){
    const email = emailInput.value.trim();
    if (!email){ 
      emailStatus.textContent=''; 
      emailHint.textContent='Usarás este correo para iniciar sesión'; 
      emailHint.className='hint';
      emailInput.classList.remove('valid', 'invalid', 'validating');
      emailInput.setCustomValidity(''); 
      return; 
    }
    if (email === lastChecked) return;
    if (!isValidFormat(email)){ 
      emailStatus.textContent='❌'; 
      emailHint.textContent='Formato de correo inválido'; 
      emailHint.className='hint error';
      emailInput.classList.remove('valid', 'validating');
      emailInput.classList.add('invalid');
      emailInput.setCustomValidity('Formato inválido'); 
      return; 
    }
    if (inflight) inflight.abort(); inflight = new AbortController();
    emailStatus.innerHTML='<div class="loading-spinner"></div>'; 
    emailHint.textContent='Verificando correo...'; 
    emailHint.className='hint info';
    emailInput.classList.add('validating');
    emailInput.classList.remove('valid', 'invalid');
    try{
      const res = await fetch(`ajax/validate_email.php?email=${encodeURIComponent(email)}`, { headers:{'X-Requested-With':'fetch'}, cache:'no-store', signal: inflight.signal });
      const data = await res.json();
      if (data.success && data.valid){ 
        emailStatus.textContent='✅'; 
        emailHint.textContent = data.verified ? 'Correo verificado y válido' : 'Correo válido (dominio verificado)'; 
        emailHint.className='hint success';
        emailInput.classList.remove('validating', 'invalid');
        emailInput.classList.add('valid');
        emailInput.setCustomValidity(''); 
        lastChecked=email; 
      } else { 
        emailStatus.textContent='❌'; 
        emailHint.textContent = data.message || 'Este correo no es válido'; 
        emailHint.className='hint error';
        emailInput.classList.remove('validating', 'valid');
        emailInput.classList.add('invalid');
        emailInput.setCustomValidity(data.message || 'Email inválido'); 
      }
    } catch(e){ 
      if(e.name!=='AbortError'){ 
        emailStatus.textContent='⚠️'; 
        emailHint.textContent='No se pudo verificar. Asegúrate que sea un correo real.'; 
        emailHint.className='hint';
        emailInput.classList.remove('validating', 'valid', 'invalid');
        emailInput.setCustomValidity(''); 
      } 
    }
  }
  
  function debounce(){ clearTimeout(timer); timer=setTimeout(validateEmail, 800); }
  emailInput.addEventListener('input', debounce); 
  emailInput.addEventListener('blur', validateEmail);
})();

// ====== TELÉFONO: solo números, 9 dígitos, empieza con 9 ======
(function(){
  const tel = document.getElementById('telefono');
  const hint = document.getElementById('tel-hint');
  if (!tel) return;

  function sanitize() {
    let v = tel.value.replace(/\D+/g, '');
    if (v.length > 9) v = v.slice(0, 9);
    tel.value = v;

    const ok = /^9\d{8}$/.test(v);
    if (!v) {
      tel.setCustomValidity('');
      tel.classList.remove('valid', 'invalid');
      hint.textContent = 'Debe ser solo números, 9 dígitos, comenzar con 9';
      hint.className = 'hint';
    } else if (!ok) {
      tel.setCustomValidity('Teléfono inválido: 9 dígitos o formato inválido');
      tel.classList.remove('valid');
      tel.classList.add('invalid');
      hint.textContent = 'Teléfono inválido: debe ser 9 dígitos y comenzar con 9';
      hint.className = 'hint error';
    } else {
      tel.setCustomValidity('');
      tel.classList.remove('invalid');
      tel.classList.add('valid');
      hint.textContent = 'Teléfono válido';
      hint.className = 'hint success';
    }
  }
  tel.addEventListener('input', sanitize);
  tel.addEventListener('blur', sanitize);
  tel.addEventListener('paste', () => setTimeout(sanitize, 0));
})();
</script>
</body>
</html>