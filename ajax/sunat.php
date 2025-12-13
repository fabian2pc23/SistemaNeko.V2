<?php
// ajax/sunat.php
declare(strict_types=1);

// Siempre JSON
header('Content-Type: application/json; charset=utf-8');

/**
 * Construye una dirección legible a partir de string u objeto con partes.
 * Acepta claves como: direccion, domicilio_fiscal, domiciliado, via, calle, nro, distrito, provincia, departamento, etc.
 */
function build_address($dom): string
{
  if (is_string($dom)) {
    return trim($dom);
  }
  if (!is_array($dom)) return '';

  // Intento directo por claves comunes
  foreach (['direccion', 'domicilio_fiscal', 'domicilio', 'calle', 'via'] as $k) {
    if (!empty($dom[$k]) && is_string($dom[$k])) {
      return trim((string)$dom[$k]);
    }
  }

  // Componer por partes cuando viene desglosado
  $parts = [];
  foreach (
    [
      'via',
      'tipo_via',
      'nombre_via',
      'calle',
      'jr',
      'avenida',
      'mz',
      'lote',
      'numero',
      'nro',
      'km',
      'interior',
      'dpto',
      'piso',
      'referencia'
    ] as $k
  ) {
    if (!empty($dom[$k])) $parts[] = trim((string)$dom[$k]);
  }
  $base = trim(implode(' ', $parts));

  // Agregar zona geográfica si está presente
  $geo = [];
  foreach (['distrito', 'provincia', 'departamento'] as $k) {
    if (!empty($dom[$k])) $geo[] = trim((string)$dom[$k]);
  }
  if ($geo) {
    return $base ? ($base . ', ' . implode(' - ', $geo)) : implode(' - ', $geo);
  }
  return $base;
}

/* ---------- Validación del parámetro ---------- */
$ruc_raw = (string)($_GET['ruc'] ?? '');
$ruc     = preg_replace('/\D+/', '', $ruc_raw);

if (strlen($ruc) !== 11) {
  http_response_code(400);
  echo json_encode(['success' => false, 'message' => 'RUC inválido (11 dígitos).']);
  exit;
}

/* ---------- Modo DEMO si no hay cURL (o para pruebas locales) ---------- */
if (!function_exists('curl_init')) {
  echo json_encode([
    'success'        => true,
    'ruc'            => $ruc,
    'razon_social'   => 'EMPRESA DEMO S.A.C.',
    'estado'         => 'ACTIVO',
    'condicion'      => 'HABIDO',
    'direccion'      => 'Av. Siempre Viva 123, SAN BORJA - LIMA - LIMA',
    'ubigeo'         => '150130',
  ]);
  exit;
}

/* ---------- Token del proveedor (usa variable de entorno si es posible) ---------- */
/* ---------- Token del proveedor (miapi.cloud) ---------- */
$TOKEN = getenv('MIAPI_CLOUD_TOKEN')
  ?: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjo1OTQsImV4cCI6MTc2NDM3MTI5Nn0.S77Gbw2fasSyyw-Awri_PazhpnXKyKcPUvACV5KkNoo';

/* ---------- Llamada al proveedor ---------- */
$url = "https://miapi.cloud/v1/ruc/{$ruc}";
$ch  = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT        => 15,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_SSL_VERIFYPEER => true,
  CURLOPT_HTTPHEADER     => [
    "Authorization: Bearer {$TOKEN}",
    "Accept: application/json",
    "Content-Type: application/json",
    "User-Agent: neko-proveedores/1.0",
  ],
]);
$body = curl_exec($ch);
$errn = curl_errno($ch);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

/* ---------- Fallback demo si falla la red/proveedor ---------- */
if ($errn || $code < 200 || $code >= 300 || !$body) {
  echo json_encode([
    'success'        => true,
    'ruc'            => $ruc,
    'razon_social'   => 'EMPRESA DEMO S.A.C.',
    'estado'         => 'ACTIVO',
    'condicion'      => 'HABIDO',
    'direccion'      => 'Av. Siempre Viva 123, SAN BORJA - LIMA - LIMA',
    'ubigeo'         => '150130',
  ]);
  exit;
}

/* ---------- Parseo de la respuesta ---------- */
$j = json_decode($body, true);
if (!is_array($j)) {
  http_response_code(502);
  echo json_encode(['success' => false, 'message' => 'Respuesta inválida del proveedor.']);
  exit;
}

/* ---------- Parseo flexible de la respuesta ---------- */
$j = json_decode($body, true);

if (!is_array($j)) {
  http_response_code(502);
  echo json_encode(['success' => false, 'message' => 'Respuesta inválida del proveedor.']);
  exit;
}

// Intentar detectar dónde están los datos
$d = [];
if (isset($j['razonSocial']) || isset($j['ruc'])) {
  $d = $j; // Estructura plana (ApisPeru v1 a veces)
} elseif (isset($j['data']) && is_array($j['data'])) {
  $d = $j['data']; // Estructura con 'data'
} elseif (isset($j['datos']) && is_array($j['datos'])) {
  $d = $j['datos']; // Estructura con 'datos' (miapi.cloud)
}

if (empty($d) || empty($d['ruc'])) {
  // Si no se encontró RUC válido en la respuesta
  http_response_code(404);
  // Mensaje de error si la API devolvió uno
  $msg = $j['message'] ?? $j['error'] ?? 'RUC no encontrado.';
  echo json_encode(['success' => false, 'message' => $msg]);
  exit;
}

/* ---------- Normalización de la razón social ---------- */
$razon = trim((string)($d['razon_social'] ?? $d['razonSocial'] ?? $d['name'] ?? ''));

/* ---------- Resolución de dirección ---------- */
$direccion = '';
// 1) Si viene como string directo
if (isset($d['direccion'])) {
  $direccion = build_address($d['direccion']);
}
// 2) Si viene como objeto en 'domicilio_fiscal'
if (!$direccion && isset($d['domicilio_fiscal'])) {
  $direccion = build_address($d['domicilio_fiscal']);
}
// 3) Si viene como objeto en 'domicilio'
if (!$direccion && isset($d['domicilio'])) {
  $direccion = build_address($d['domicilio']);
}
// 4) **CLAVE QUE FALTABA**: 'domiciliado'
if (!$direccion && isset($d['domiciliado'])) {
  $direccion = build_address($d['domiciliado']);
}

/* ---------- Respuesta normalizada al front ---------- */
echo json_encode([
  'success'        => true,
  'ruc'            => $ruc,
  'razon_social'   => $razon,
  'estado'         => $d['estado']     ?? null,
  'condicion'      => $d['condicion']  ?? null,
  'direccion'      => $direccion ?: null,
  'ubigeo'         => $d['ubigeo']     ?? ($d['domiciliado']['ubigeo'] ?? null),
]);
