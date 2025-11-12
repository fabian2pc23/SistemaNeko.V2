<?php
// ajax/validate_email.php - Validación BALANCEADA (Producción)
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// ============================================================
// CONFIGURACIÓN
// ============================================================
$ABSTRACT_API_KEY = 'd14097ba1a3e48d585e0f0a395deab55'; // Tu API key real

$ALLOW_SMTP_BYPASS_DOMAINS = [
  'gmail.com', 'googlemail.com', 'hotmail.com', 'outlook.com', 'live.com', 'msn.com',
  'yahoo.com', 'icloud.com', 'me.com', 'proton.me', 'protonmail.com', 'utp.edu.pe'
];

// ============================================================
// PARÁMETROS BALANCEADOS
// ============================================================
$VALIDATION_RULES = [
  'min_local_length'        => 5,    // Mínimo 5 caracteres
  'max_local_length'        => 64,
  'min_numeric_length'      => 8,
  'max_consecutive_chars'   => 2,
  'require_letter'          => true,
  'min_different_chars'     => 3,
  
  'block_keyboard_patterns' => true,
  'block_sequential'        => true,
  'block_common_test'       => true,
  'block_repeated_pattern'  => true,
  'block_inappropriate'     => true,  // Bloqueo inteligente
  
  'block_disposable'        => true,
  'require_mx_or_a'         => true,
  'block_suspicious_tlds'   => true,
  'block_role_emails'       => false, // Desactivado para permitir algunos genéricos
  'min_domain_length'       => 4,
  
  'allow_plus_addressing'   => true,
  'block_consecutive_dots'  => true,
  'block_special_start_end' => true,
  'max_dots_in_local'       => 4,     // Aumentado para nombres como cristian.manfredy
  
  'min_quality_score'       => 0.6,
  'enable_api_validation'   => true,
];

// ============================================================
// LISTAS NEGRAS - MÁS INTELIGENTES
// ============================================================

// Solo palabras COMPLETAS inapropiadas (no buscar dentro)
$BLACKLIST_INAPPROPRIATE_EXACT = [
  // Palabras completas obvias
  'pene', 'pito', 'verga', 'pija', 'chota', 'polla', 'picha', 'pichula', 'pichulon',
  'concha', 'cono', 'chocho', 'vagina', 'sexo', 'sex', 'porn', 'porno', 'xxx',
  'culo', 'ass', 'coño', 'pussy', 'dick', 'cock', 'tits', 'boobs', 'puta', 'puto',
  'mierda', 'shit', 'fuck', 'cunt', 'bitch', 'whore', 'slut',
  'nude', 'naked', 'desnudo', 'anal', 'oral', 'dildo',
  'sexx', 'sexxo', 'sexy', 'p0rn', 'pr0n', 'fck', 'fuk',
  'gamp', // Específico de tus ejemplos
];

// Genéricos y prueba
$BLACKLIST_GENERIC = [
  'test', 'testing', 'demo', 'admin', 'user', 'usuario', 
  'correo', 'email', 'example', 'sample', 'fake', 'temp', 
  'spam', 'dummy', 'prueba', 'noresponder', 'noreply',
  'asdf', 'qwerty', 'abc', 'xyz', 'xxx', 'aaa', 'zzz',
  'foo', 'bar', 'baz', 'qux', 'root',
];

$BLACKLIST_SHORT_SUSPICIOUS = [
  'gay', 'hot', 'wtf', 'lol', 'omg', 'win', 'fail',
];

$BLACKLIST_COMBINED = array_merge(
  $BLACKLIST_INAPPROPRIATE_EXACT,
  $BLACKLIST_GENERIC,
  $BLACKLIST_SHORT_SUSPICIOUS
);

$SUSPICIOUS_TLDS = [
  'tk', 'ml', 'ga', 'cf', 'gq', 'top', 'xyz', 'club', 'work', 'link', 
  'click', 'date', 'racing', 'download', 'stream', 'win', 'bid'
];

$DISPOSABLE_DOMAINS = [
  'tempmail.com', 'guerrillamail.com', '10minutemail.com', 'throwaway.email',
  'mailinator.com', 'trashmail.com', 'yopmail.com', 'maildrop.cc', 'temp-mail.org',
  'fakeinbox.com', 'sharklasers.com', 'getnada.com', 'mohmal.com', 'mintemail.com',
  'dispostable.com', 'emailondeck.com', 'tempinbox.com', 'spamgourmet.com',
];

// ============================================================
// INPUT
// ============================================================
$email = trim($_GET['email'] ?? '');
if ($email === '') {
  respondError('Email vacío');
}

// ============================================================
// VALIDACIÓN 1: FORMATO BÁSICO
// ============================================================
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  respondError('Formato de correo inválido');
}

if (strpos($email, '@') === false) {
  respondError('Email debe contener @');
}

[$local, $domain] = explode('@', $email, 2);
$local_original = $local;
$local  = strtolower(trim($local));
$domain = strtolower(trim($domain));

// ============================================================
// VALIDACIÓN 2: LONGITUD LOCAL-PART
// ============================================================
$local_len = strlen($local);
if ($local_len < $VALIDATION_RULES['min_local_length']) {
  respondError("El nombre de usuario es muy corto (mínimo {$VALIDATION_RULES['min_local_length']} caracteres)");
}
if ($local_len > $VALIDATION_RULES['max_local_length']) {
  respondError("El nombre de usuario es muy largo (máximo {$VALIDATION_RULES['max_local_length']} caracteres)");
}

// ============================================================
// VALIDACIÓN 3: CONTENIDO INAPROPIADO (MEJORADO - SOLO EXACTO)
// ============================================================
if ($VALIDATION_RULES['block_inappropriate']) {
  $local_clean = explode('+', $local)[0]; // Remover +tag
  
  // Solo verificación EXACTA (no buscar dentro para evitar falsos positivos)
  if (in_array($local_clean, $BLACKLIST_COMBINED, true)) {
    respondError('Este correo no es válido para registro');
  }
  
  // Verificación con variaciones leetspeak solo para palabras COMPLETAS
  $local_normalized = str_replace(
    ['0', '1', '3', '4', '5', '7', '8'], 
    ['o', 'i', 'e', 'a', 's', 't', 'b'], 
    $local_clean
  );
  
  if (in_array($local_normalized, $BLACKLIST_INAPPROPRIATE_EXACT, true)) {
    respondError('Este correo contiene contenido no permitido');
  }
}

// ============================================================
// VALIDACIÓN 4: CARACTERES REPETIDOS
// ============================================================
if ($VALIDATION_RULES['max_consecutive_chars'] > 0) {
  $max = $VALIDATION_RULES['max_consecutive_chars'];
  $pattern = '/(.)\1{' . $max . ',}/';
  if (preg_match($pattern, $local)) {
    respondError('Demasiados caracteres repetidos consecutivos');
  }
}

// ============================================================
// VALIDACIÓN 5: DIVERSIDAD DE CARACTERES
// ============================================================
if ($VALIDATION_RULES['min_different_chars'] > 0) {
  $unique_chars = count(array_unique(str_split($local)));
  if ($unique_chars < $VALIDATION_RULES['min_different_chars']) {
    respondError('El correo es demasiado simple (necesita más variedad de caracteres)');
  }
}

// ============================================================
// VALIDACIÓN 6: REQUIERE LETRA
// ============================================================
if ($VALIDATION_RULES['require_letter']) {
  if (!preg_match('/[a-z]/', $local)) {
    respondError('El email debe contener al menos una letra');
  }
}

// ============================================================
// VALIDACIÓN 7: SOLO NÚMEROS
// ============================================================
if (preg_match('/^\d+$/', $local)) {
  if ($local_len < $VALIDATION_RULES['min_numeric_length']) {
    respondError("Email numérico poco confiable (mínimo {$VALIDATION_RULES['min_numeric_length']} dígitos)");
  }
}

// ============================================================
// VALIDACIÓN 8: PUNTOS INVÁLIDOS
// ============================================================
if ($VALIDATION_RULES['block_special_start_end']) {
  if ($local[0] === '.' || substr($local, -1) === '.') {
    respondError('El email no puede empezar ni terminar con punto');
  }
}
if ($VALIDATION_RULES['block_consecutive_dots']) {
  if (strpos($local, '..') !== false) {
    respondError('El email no puede contener puntos consecutivos');
  }
}

if (isset($VALIDATION_RULES['max_dots_in_local'])) {
  $dot_count = substr_count($local, '.');
  if ($dot_count > $VALIDATION_RULES['max_dots_in_local']) {
    respondError('Demasiados puntos en el nombre de usuario');
  }
}

// ============================================================
// VALIDACIÓN 9: PATRONES DE TECLADO
// ============================================================
if ($VALIDATION_RULES['block_keyboard_patterns']) {
  $keyboard_exact = ['asdf', 'qwerty', 'zxcvb', 'password', 'admin123', 'root123'];
  if (in_array($local, $keyboard_exact, true)) {
    respondError('Patrón de teclado no permitido');
  }
}

// ============================================================
// VALIDACIÓN 10: SECUENCIAS
// ============================================================
if ($VALIDATION_RULES['block_sequential']) {
  if (preg_match('/abcd|bcde|cdef|defg|wxyz|stuv/i', $local)) {
    respondError('Secuencia alfabética no permitida');
  }
  if (preg_match('/1234|2345|3456|4567|5678|6789|9876|8765|7654|6543|5432|4321/', $local)) {
    respondError('Secuencia numérica no permitida');
  }
}

// ============================================================
// VALIDACIÓN 11: PATRÓN REPETIDO
// ============================================================
if ($VALIDATION_RULES['block_repeated_pattern']) {
  if (preg_match('/^(.{2,4})\1+$/', $local)) {
    respondError('Patrón repetitivo no permitido');
  }
}

// ============================================================
// VALIDACIÓN 12: MUCHAS X, Y, Z
// ============================================================
if (preg_match('/x{3,}|y{3,}|z{3,}/i', $local)) {
  respondError('Demasiadas letras repetidas (placeholder detectado)');
}

// ============================================================
// VALIDACIÓN 13: DOMINIO - LONGITUD
// ============================================================
if (isset($VALIDATION_RULES['min_domain_length'])) {
  if (strlen($domain) < $VALIDATION_RULES['min_domain_length']) {
    respondError('Dominio demasiado corto');
  }
}

// ============================================================
// VALIDACIÓN 14: TLDs SOSPECHOSOS
// ============================================================
if ($VALIDATION_RULES['block_suspicious_tlds']) {
  $domain_parts = explode('.', $domain);
  $tld = end($domain_parts);
  if (in_array($tld, $SUSPICIOUS_TLDS, true)) {
    respondError('Dominio con extensión no confiable');
  }
}

// ============================================================
// VALIDACIÓN 15: CORREOS DESECHABLES
// ============================================================
if ($VALIDATION_RULES['block_disposable']) {
  if (in_array($domain, $DISPOSABLE_DOMAINS, true)) {
    respondError('Correo desechable no permitido');
  }
}

// ============================================================
// VALIDACIÓN 16: DNS DEL DOMINIO
// ============================================================
if ($VALIDATION_RULES['require_mx_or_a']) {
  $has_mx = @checkdnsrr($domain, 'MX');
  $has_a  = @checkdnsrr($domain, 'A');
  if (!$has_mx && !$has_a) {
    respondError('Dominio sin registros DNS válidos (MX/A)');
  }
}

// ============================================================
// VALIDACIÓN 17: API EXTERNA
// ============================================================
$usar_api = $VALIDATION_RULES['enable_api_validation'] 
            && !empty($ABSTRACT_API_KEY) 
            && $ABSTRACT_API_KEY !== 'd14097ba1a3e48d585e0f0a395deab55';

if ($usar_api) {
  $api_result = validateWithAPI($email, $ABSTRACT_API_KEY, $ALLOW_SMTP_BYPASS_DOMAINS, $VALIDATION_RULES['min_quality_score']);
  
  if ($api_result !== null) {
    echo json_encode($api_result);
    exit;
  }
}

// ============================================================
// RESPUESTA EXITOSA
// ============================================================
respondSuccess([
  'verified' => in_array($domain, $ALLOW_SMTP_BYPASS_DOMAINS, true) ? null : true,
  'message'  => 'Correo válido (validación local superada)'
]);

// ============================================================
// FUNCIONES AUXILIARES
// ============================================================

function respondError(string $message): void {
  echo json_encode([
    'success' => false,
    'valid'   => false,
    'message' => $message
  ]);
  exit;
}

function respondSuccess(array $extra = []): void {
  echo json_encode(array_merge([
    'success' => true,
    'valid'   => true,
  ], $extra));
  exit;
}

function validateWithAPI(string $email, string $apiKey, array $bypassDomains, float $minQuality): ?array {
  $api_url = "https://emailvalidation.abstractapi.com/v1/?api_key={$apiKey}&email=" . urlencode($email);
  $data = null;

  if (function_exists('curl_init')) {
    $ch = curl_init($api_url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_TIMEOUT        => 12,
      CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($code === 200 && $resp) {
      $data = json_decode($resp, true);
    }
  } else {
    $resp = @file_get_contents($api_url);
    if ($resp !== false) {
      $data = json_decode($resp, true);
    }
  }

  if (!is_array($data)) {
    return null;
  }

  $is_valid_format = $data['is_valid_format']['value']       ?? false;
  $is_mx_found     = $data['is_mx_found']['value']           ?? false;
  $is_smtp_valid   = $data['is_smtp_valid']['value']         ?? false;
  $is_disposable   = $data['is_disposable_email']['value']   ?? false;
  $quality_score   = (float)($data['quality_score']          ?? 0);

  if (!$is_valid_format) {
    return ['success' => false, 'valid' => false, 'message' => 'Formato inválido según API'];
  }
  
  if ($is_disposable) {
    return ['success' => false, 'valid' => false, 'message' => 'Correo desechable detectado por API'];
  }
  
  if (!$is_mx_found) {
    return ['success' => false, 'valid' => false, 'message' => 'Dominio sin registros MX según API'];
  }

  [$local, $domain] = explode('@', $email, 2);
  $smtp_bypass = in_array(strtolower($domain), $bypassDomains, true);

  if (!$smtp_bypass && !$is_smtp_valid) {
    return ['success' => false, 'valid' => false, 'message' => 'Servidor SMTP no acepta este correo'];
  }

  if ($quality_score < $minQuality) {
    return ['success' => false, 'valid' => false, 'message' => "Baja confiabilidad (score: {$quality_score})"];
  }

  return [
    'success'       => true,
    'valid'         => true,
    'verified'      => $smtp_bypass ? null : $is_smtp_valid,
    'quality_score' => $quality_score,
    'message'       => 'Correo verificado como válido por API'
  ];
}