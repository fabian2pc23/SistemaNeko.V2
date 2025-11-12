<?php
ob_start();
if (strlen(session_id()) < 1) { session_start(); }

require_once "../modelos/Usuario.php";
require_once "../config/Conexion.php"; // ejecutarConsulta para selectRol

$usuario = new Usuario();

/* ===== Helper local: avatar por rol ===== */
function avatar_por_rol($cargo){
  $k = mb_strtolower(trim((string)$cargo),'UTF-8');
  if ($k === 'administrador') return 'administrador.png';
  if ($k === 'almacenero')   return 'almacenero.png';
  if ($k === 'vendedor')     return 'vendedor.png';
  return 'usuario.png';
}

/* ===== Helper: validar email existente (server-side) usando ajax/validate_email.php ===== */
function validar_email_externo($email){
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
  $base   = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'); // ej: /ajax
  $url    = $scheme . '://' . $host . $base . '/validate_email.php?email=' . urlencode($email);

  // Preferir cURL si está disponible (timeouts más finos)
  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CONNECTTIMEOUT => 5,
      CURLOPT_TIMEOUT        => 10,
      CURLOPT_SSL_VERIFYPEER => false, // si usas https local sin cert
    ]);
    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code === 200 && $resp) {
      $j = json_decode($resp, true);
      return (is_array($j) && !empty($j['valid'])) ? [true, $j] : [false, $j];
    }
    return [false, ['message' => 'HTTP '.$code.' al verificar email']];
  } else {
    $ctx = stream_context_create(['http'=>['timeout'=>10]]);
    $resp = @file_get_contents($url, false, $ctx);
    if ($resp !== false) {
      $j = json_decode($resp, true);
      return (is_array($j) && !empty($j['valid'])) ? [true, $j] : [false, $j];
    }
    return [false, ['message' => 'No se pudo contactar verificador']];
  }
}

/* ===== Inputs comunes ===== */
$idusuario       = isset($_POST["idusuario"])       ? limpiarCadena($_POST["idusuario"])       : "";
$nombre          = isset($_POST["nombre"])          ? limpiarCadena($_POST["nombre"])          : "";
$tipo_documento  = isset($_POST["tipo_documento"])  ? limpiarCadena($_POST["tipo_documento"])  : "";
$num_documento   = isset($_POST["num_documento"])   ? limpiarCadena($_POST["num_documento"])   : "";
$direccion       = isset($_POST["direccion"])       ? limpiarCadena($_POST["direccion"])       : "";
$telefono        = isset($_POST["telefono"])        ? limpiarCadena($_POST["telefono"])        : "";
$email           = isset($_POST["email"])           ? limpiarCadena($_POST["email"])           : "";
$cargo           = isset($_POST["cargo"])           ? limpiarCadena($_POST["cargo"])           : "";
$clave           = isset($_POST["clave"])           ? limpiarCadena($_POST["clave"])           : "";
$imagen          = isset($_POST["imagen"])          ? limpiarCadena($_POST["imagen"])          : "";

$id_rol          = isset($_POST["id_rol"])          ? limpiarCadena($_POST["id_rol"])          : "";
$modo_permisos   = isset($_POST["modo_permisos"])   ? limpiarCadena($_POST["modo_permisos"])   : ""; // 'rol'|'personalizado'|''
$mantener_clave  = isset($_POST["mantener_clave"])  ? limpiarCadena($_POST["mantener_clave"])  : "0";

switch ($_GET["op"]) {

/* ============================================================
   GUARDAR / EDITAR
   ============================================================ */
case 'guardaryeditar':
  if (!isset($_SESSION["nombre"])) { header("Location: ../vistas/login.html"); }
  else if ($_SESSION['acceso'] != 1) { require 'noacceso.php'; }
  else {

    // Validaciones de unicidad y formato
    if ($usuario->verificarEmailExiste($email, (int)$idusuario)) { echo "Error: Este correo electrónico ya está registrado por otro usuario."; break; }
    if ($usuario->verificarDocumentoExiste($tipo_documento, $num_documento, (int)$idusuario)) { echo "Error: Este documento ya está registrado por otro usuario."; break; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { echo "Error: El formato del correo electrónico no es válido."; break; }

    // ✅ Validación fuerte de existencia de correo (server-side)
    list($okMail, $mailInfo) = validar_email_externo($email);
    if (!$okMail) {
      $detalle = is_array($mailInfo) && !empty($mailInfo['message']) ? $mailInfo['message'] : 'verificación fallida';
      echo "Error: El correo no pudo verificarse como existente. Detalle: " . $detalle;
      break;
    }

    // Permisos requeridos si NO es modo='rol'
    if ($modo_permisos !== 'rol') {
      if (!isset($_POST['permiso']) || !is_array($_POST['permiso']) || count($_POST['permiso']) == 0) {
        echo "Error: Debes seleccionar al menos un permiso para el usuario.";
        break;
      }
    }

    // Imagen subida
    if (!file_exists($_FILES['imagen']['tmp_name']) || !is_uploaded_file($_FILES['imagen']['tmp_name'])) {
      $imagen = $_POST["imagenactual"] ?? $imagen; // puede venir vacío
    } else {
      $ext  = explode(".", $_FILES["imagen"]["name"]);
      $mime = $_FILES['imagen']['type'] ?? '';
      if (in_array($mime, ["image/jpg","image/jpeg","image/png"])) {
        $imagen = round(microtime(true)) . '.' . end($ext);
        @move_uploaded_file($_FILES["imagen"]["tmp_name"], "../files/usuarios/" . $imagen);
      }
    }

    // Password
    $clavehash = null;
    if (empty($idusuario)) {
      if ($clave === "" || strlen($clave) < 10 || strlen($clave) > 64) { echo "Error: La contraseña debe tener entre 10 y 64 caracteres."; break; }
      $clavehash = hash("SHA256", $clave);
    } else {
      if ($mantener_clave === "1" || $clave === "") {
        $fila = $usuario->mostrar($idusuario);
        $hashActual = "";
        if (is_array($fila) && isset($fila['clave']))    $hashActual = $fila['clave'];
        elseif (is_object($fila) && isset($fila->clave)) $hashActual = $fila->clave;
        $clavehash = $hashActual;
      } else {
        if (strlen($clave) < 10 || strlen($clave) > 64) { echo "Error: La contraseña debe tener entre 10 y 64 caracteres."; break; }
        $clavehash = hash("SHA256", $clave);
      }
    }

    $permisos = (isset($_POST['permiso']) && is_array($_POST['permiso'])) ? $_POST['permiso'] : array();

    /* Avatar automático por rol si no hay imagen */
    if (empty($idusuario)) {
      if ($imagen === null || $imagen === '') { $imagen = avatar_por_rol($cargo); }
    } else {
      $defaults  = ['administrador.png','almacenero.png','vendedor.png','usuario.png'];
      $imgActual = $_POST["imagenactual"] ?? '';
      if (($imagen === null || $imagen === '') && ($imgActual === '' || in_array($imgActual, $defaults, true))) {
        $imagen = avatar_por_rol($cargo);
      }
    }

    // Insertar / Editar
    if (empty($idusuario)) {
      $rspta = $usuario->insertar(
        $nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email,
        $cargo,$clavehash,$imagen,$permisos,
        $id_rol,$modo_permisos
      );
      echo $rspta ? "Usuario registrado exitosamente. Puede iniciar sesión con su correo: $email"
                  : "No se pudieron registrar todos los datos del usuario";
    } else {
      $rspta = $usuario->editar(
        $idusuario,$nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email,
        $cargo,$clavehash,$imagen,$permisos,
        $id_rol,$modo_permisos, ($mantener_clave === "1")
      );
      echo $rspta ? "Usuario actualizado correctamente" : "Usuario no se pudo actualizar";
    }
  }
break;

/* ============================================================
   Activar / Desactivar
   ============================================================ */
case 'desactivar':
  if (!isset($_SESSION["nombre"])) { header("Location: ../vistas/login.html"); }
  else if ($_SESSION['acceso'] != 1) { require 'noacceso.php'; }
  else { echo $usuario->desactivar($idusuario) ? "Usuario Desactivado" : "Usuario no se puede desactivar"; }
break;

case 'activar':
  if (!isset($_SESSION["nombre"])) { header("Location: ../vistas/login.html"); }
  else if ($_SESSION['acceso'] != 1) { require 'noacceso.php'; }
  else { echo $usuario->activar($idusuario) ? "Usuario activado" : "Usuario no se puede activar"; }
break;

/* ============================================================
   Mostrar
   ============================================================ */
case 'mostrar':
  if (!isset($_SESSION["nombre"])) { header("Location: ../vistas/login.html"); }
  else if ($_SESSION['acceso'] != 1) { require 'noacceso.php'; }
  else { echo json_encode($usuario->mostrar($idusuario)); }
break;

/* ============================================================
   Listar (incluye Pendiente=3)
   ============================================================ */
case 'listar':
  if (!isset($_SESSION["nombre"])) { header("Location: ../vistas/login.html"); }
  else if ($_SESSION['acceso'] != 1) { require 'noacceso.php'; }
  else {
    $rspta = $usuario->listar();
    $data = array();
    while ($reg = $rspta->fetch_object()) {
      if ((string)$reg->condicion === '3') {
        $botones =
          '<button class="btn btn-warning" title="Editar" onclick="mostrar('.$reg->idusuario.')"><i class="fa fa-pencil"></i></button>'.
          '<div class="text-info" style="font-size:11px;margin-top:4px;">Pendiente asignación de rol</div>';
        $estado = '<span class="label bg-blue">Pendiente</span>';
      } else if ((string)$reg->condicion === '1') {
        $botones =
          '<button class="btn btn-warning" title="Editar" onclick="mostrar('.$reg->idusuario.')"><i class="fa fa-pencil"></i></button> '.
          '<button class="btn btn-danger"  title="Desactivar" onclick="desactivar('.$reg->idusuario.')"><i class="fa fa-close"></i></button>';
        $estado = '<span class="label bg-green">Activado</span>';
      } else {
        $botones =
          '<button class="btn btn-warning" title="Editar" onclick="mostrar('.$reg->idusuario.')"><i class="fa fa-pencil"></i></button> '.
          '<button class="btn btn-primary" title="Activar" onclick="activar('.$reg->idusuario.')"><i class="fa fa-check"></i></button>';
        $estado = '<span class="label bg-red">Desactivado</span>';
      }

      $data[] = array(
        "0" => $botones,
        "1" => $reg->nombre,
        "2" => $reg->tipo_documento,
        "3" => $reg->num_documento,
        "4" => $reg->telefono,
        "5" => $reg->email,
        "6" => $reg->cargo,
        "7" => "<img src='../files/usuarios/".$reg->imagen."' height='50' width='50'>",
        "8" => $estado
      );
    }
    echo json_encode(array(
      "sEcho" => 1,
      "iTotalRecords" => count($data),
      "iTotalDisplayRecords" => count($data),
      "aaData" => $data
    ));
  }
break;

/* ============================================================
   Permisos / Roles / Login / Salir
   ============================================================ */
case 'permisos':
  require_once "../modelos/Permiso.php";
  $permiso = new Permiso();
  $rspta   = $permiso->listar();
  $id      = $_GET['id'];
  $marcados= $usuario->listarmarcados($id);
  $valores = array();
  while ($per = $marcados->fetch_object()) { $valores[] = $per->idpermiso; }
  while ($reg = $rspta->fetch_object()) {
    $sw = in_array($reg->idpermiso, $valores) ? 'checked' : '';
    echo '<li><label style="font-weight:normal;"><input type="checkbox" '.$sw.' name="permiso[]" value="'.$reg->idpermiso.'"> '.$reg->nombre.'</label></li>';
  }
break;

case 'selectRol':
  if (!isset($_SESSION["nombre"])) { header("Location: ../vistas/login.html"); exit; }
  if ($_SESSION['acceso'] != 1) { echo '<option value="">Sin acceso</option>'; break; }
  $sql = "SELECT id_rol, nombre FROM rol_usuarios WHERE estado = 1 OR estado IS NULL ORDER BY nombre ASC";
  $rspta = ejecutarConsulta($sql);
  echo '<option value="">Seleccione...</option>';
  while ($reg = $rspta->fetch_object()) { echo '<option value="'.$reg->id_rol.'">'.$reg->nombre.'</option>'; }
break;

case 'permisos_por_rol':
  header('Content-Type: application/json; charset=utf-8');
  if (!isset($_SESSION["nombre"]) || $_SESSION['acceso'] != 1) { echo json_encode([]); break; }
  $id_rol_q = isset($_GET['id_rol']) ? intval($_GET['id_rol']) : 0;
  echo json_encode($usuario->permisos_por_rol($id_rol_q));
break;

case 'verificar':
  $logina = $_POST['logina']; // email
  $clavea = $_POST['clavea'];
  $clavehash = hash("SHA256",$clavea);
  $rspta = $usuario->verificar($logina, $clavehash);
  $fetch = $rspta->fetch_object();
  if (isset($fetch)) {
    $_SESSION['idusuario'] = $fetch->idusuario;
    $_SESSION['nombre']    = $fetch->nombre;
    $_SESSION['imagen']    = $fetch->imagen;
    $_SESSION['email']     = $fetch->email;
    // ✅ Guarda también el id_rol para que el header pueda mostrar el rol real o usar fallback
    if (isset($fetch->id_rol)) { $_SESSION['id_rol'] = (int)$fetch->id_rol; }

    $marcados = $usuario->listarmarcados($fetch->idusuario);
    $valores  = array();
    while ($per = $marcados->fetch_object()) { $valores[] = $per->idpermiso; }

    in_array(1,$valores)?$_SESSION['escritorio']=1:$_SESSION['escritorio']=0;
    in_array(2,$valores)?$_SESSION['almacen']=1:$_SESSION['almacen']=0;
    in_array(3,$valores)?$_SESSION['compras']=1:$_SESSION['compras']=0;
    in_array(4,$valores)?$_SESSION['ventas']=1:$_SESSION['ventas']=0;
    in_array(5,$valores)?$_SESSION['acceso']=1:$_SESSION['acceso']=0;
    in_array(6,$valores)?$_SESSION['consultac']=1:$_SESSION['consultac']=0;
    in_array(7,$valores)?$_SESSION['consultav']=1:$_SESSION['consultav']=0;
  }
  echo json_encode($fetch);
break;

case 'salir':
  $_SESSION = array();
  if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
  }
  session_destroy();
  header("Location: ../index.php");
  exit();
break;
}

ob_end_flush();
