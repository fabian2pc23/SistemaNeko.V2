<?php

/**
 * ajax/articulo.php – Respuestas JSON limpias para DataTables
 */
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', '0');

function json_ok($payload, $code = 200)
{
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload);
  exit;
}
function json_msg($ok, $msg, $code = 200)
{
  json_ok(["success" => $ok, "message" => $msg], $code);
}

if (!isset($_SESSION["idusuario"])) {
  json_msg(false, "No autenticado", 401);
}

$hasAlmacen = !empty($_SESSION['almacen']) && (int)$_SESSION['almacen'] === 1;

require_once "../modelos/Articulo.php";
require_once "../modelos/HistorialPrecio.php";

$articulo = new Articulo();

$idarticulo     = isset($_POST["idarticulo"])     ? limpiarCadena($_POST["idarticulo"])     : "";
$idcategoria    = isset($_POST["idcategoria"])    ? limpiarCadena($_POST["idcategoria"])    : "";
$idmarca        = isset($_POST["idmarca"])        ? limpiarCadena($_POST["idmarca"])        : "";
$codigo         = isset($_POST["codigo"])         ? limpiarCadena($_POST["codigo"])         : "";
$nombre         = isset($_POST["nombre"])         ? limpiarCadena($_POST["nombre"])         : "";
$stock          = isset($_POST["stock"])          ? limpiarCadena($_POST["stock"])          : "0";
$precio_compra  = isset($_POST["precio_compra"])  ? limpiarCadena($_POST["precio_compra"])  : "0";
$precio_venta   = isset($_POST["precio_venta"])   ? limpiarCadena($_POST["precio_venta"])   : "0";
$descripcion    = isset($_POST["descripcion"])    ? limpiarCadena($_POST["descripcion"])    : "";
$imagen         = isset($_POST["imagen"])         ? limpiarCadena($_POST["imagen"])         : "";

$op = $_GET["op"] ?? '';

try {
  switch ($op) {

    case 'guardaryeditar':
      if (!$hasAlmacen) json_msg(false, "Sin permiso (almacen)", 403);

      if (!file_exists($_FILES['imagen']['tmp_name']) || !is_uploaded_file($_FILES['imagen']['tmp_name'])) {
        $imagen = $_POST["imagenactual"] ?? "";
      } else {
        $mime = @mime_content_type($_FILES["imagen"]["tmp_name"]);
        $permitidos = ["image/jpg", "image/jpeg", "image/png"];
        if (in_array($mime, $permitidos, true)) {
          $ext = strtolower(pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION));
          $imagen = 'art_' . date('Ymd_His') . '_' . mt_rand(1000, 9999) . '.' . $ext;
          @move_uploaded_file($_FILES["imagen"]["tmp_name"], "../files/articulos/" . $imagen);
        } else {
          $imagen = $_POST["imagenactual"] ?? "";
        }
      }

      if (empty($idarticulo)) {
        $rspta = $articulo->insertar($idcategoria, $idmarca, $codigo, $nombre, $stock, $precio_compra, $precio_venta, $descripcion, $imagen);
        if ($rspta === "duplicado") json_msg(false, "duplicado", 409);

        if ($rspta) {
          echo json_encode(["success" => true, "message" => "Artículo registrado", "idarticulo" => $rspta]);
          exit;
        } else {
          json_msg(false, "Artículo no se pudo registrar");
        }
      } else {
        // Obtener precio anterior para historial
        $oldData = $articulo->mostrar($idarticulo);
        $oldPrice = $oldData ? (float)$oldData['precio_venta'] : 0.0;

        $rspta = $articulo->editar($idarticulo, $idcategoria, $idmarca, $codigo, $nombre, $stock, $precio_compra, $precio_venta, $descripcion, $imagen);

        if ($rspta) {
          $newPrice = (float)$precio_venta;
          // Registrar en historial si hubo cambio de precio
          if (abs($oldPrice - $newPrice) > 0.001) {
            $hist = new HistorialPrecios();
            $iduser = $_SESSION['idusuario'] ?? null;
            $hist->insertar($idarticulo, $oldPrice, $newPrice, 'Actualización en módulo Artículos', 'manual', null, $iduser);
          }
        }

        if ($rspta === "duplicado") json_msg(false, "duplicado", 409);
        json_msg((bool)$rspta, $rspta ? "Artículo actualizado" : "Artículo no se pudo actualizar");
      }
      break;

    case 'desactivar':
      if (!$hasAlmacen) json_msg(false, "Sin permiso (almacen)", 403);
      $rspta = $articulo->desactivar($idarticulo);
      json_msg((bool)$rspta, $rspta ? "Artículo desactivado" : "Artículo no se puede desactivar");
      break;

    case 'activar':
      if (!$hasAlmacen) json_msg(false, "Sin permiso (almacen)", 403);
      $rspta = $articulo->activar($idarticulo);
      json_msg((bool)$rspta, $rspta ? "Artículo activado" : "Artículo no se puede activar");
      break;

    case 'mostrar':
      if (!$hasAlmacen) json_msg(false, "Sin permiso (almacen)", 403);
      $rspta = $articulo->mostrar($idarticulo);
      json_ok($rspta ?: []);
      break;

    case 'listar':
      if (!$hasAlmacen) json_msg(false, "Sin permiso (almacen)", 403);

      $rspta = $articulo->listar();
      $rows = [];
      $thumbStyle   = "width:52px;height:52px;object-fit:cover;border-radius:10px;border:2px solid #e5e7eb;box-shadow:0 2px 8px rgba(0,0,0,.1)";
      $placeholder  = "../public/img/no-image.png";

      while ($reg = $rspta->fetch_object()) {
        $img = !empty($reg->imagen) ? "../files/articulos/" . $reg->imagen : $placeholder;

        // Botones modernos más grandes
        $btns =
          '<button class="btn btn-action btn-edit" data-id="' . (int)$reg->idarticulo . '" title="Editar">' .
          '<i class="fa fa-pencil"></i>' .
          '</button>' .
          (
            $reg->condicion
            ? '<button class="btn btn-action btn-off" data-id="' . (int)$reg->idarticulo . '" title="Desactivar">' .
            '<i class="fa fa-ban"></i>' .
            '</button>'
            : '<button class="btn btn-action btn-on" data-id="' . (int)$reg->idarticulo . '" title="Activar">' .
            '<i class="fa fa-check"></i>' .
            '</button>'
          );

        // Formato solicitado: Nombre Capitalizado, Stock Rojo si <= 0
        $nombreFmt = ucfirst(strtolower($reg->nombre ?? ''));
        $stockVal  = (int)($reg->stock ?? 0);
        $stockFmt  = $stockVal <= 0
          ? '<span style="color:red;font-weight:bold;">' . $stockVal . '</span>'
          : (string)$stockVal;

        $rows[] = [
          $btns,
          htmlspecialchars($nombreFmt, ENT_QUOTES, 'UTF-8'),
          htmlspecialchars($reg->categoria ?? '', ENT_QUOTES, 'UTF-8'),
          htmlspecialchars($reg->marca ?? '', ENT_QUOTES, 'UTF-8'),
          htmlspecialchars($reg->codigo ?? '', ENT_QUOTES, 'UTF-8'),
          $stockFmt,
          number_format((float)($reg->precio_compra ?? 0), 2, '.', ''),
          number_format((float)($reg->precio_venta ?? 0), 2, '.', ''),
          '<img src="' . $img . '" style="' . $thumbStyle . '">',
          ($reg->condicion
            ? '<span class="label label-status bg-green">Activado</span>'
            : '<span class="label label-status bg-red">Desactivado</span>'
          ),
          $reg->idarticulo // Columna 10: ID oculto para ordenamiento
        ];
      }

      $draw  = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;
      $total = count($rows);

      json_ok([
        "draw"            => $draw,
        "recordsTotal"    => $total,
        "recordsFiltered" => $total,
        "data"            => $rows
      ]);
      break;

    case 'selectMarca':
      require_once "../modelos/Marca.php";
      $marca = new Marca();
      $rspta = $marca->select();
      while ($reg = $rspta->fetch_object()) {
        echo '<option value="' . htmlspecialchars($reg->nombre, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($reg->nombre, ENT_QUOTES, 'UTF-8') . '</option>';
      }
      break;

    case "selectCategoria":
      require_once "../modelos/Categoria.php";
      $categoria = new Categoria();
      $rspta = $categoria->select();
      header('Content-Type: text/html; charset=utf-8');
      while ($reg = $rspta->fetch_object()) {
        echo '<option value="' . $reg->idcategoria . '">' . htmlspecialchars($reg->nombre, ENT_QUOTES, 'UTF-8') . '</option>';
      }
      exit;

    case 'selectActivos':
      $rspta = $articulo->selectActivosParaHistorial();
      header('Content-Type: text/html; charset=utf-8');
      echo '<option value="">Seleccione artículo</option>';
      while ($reg = $rspta->fetch_object()) {
        $text = htmlspecialchars(($reg->nombre ?? '') . ' — ' . ($reg->codigo ?? ''), ENT_QUOTES, 'UTF-8');
        echo '<option value="' . $reg->idarticulo . '">' . $text . '</option>';
      }
      exit;

    case 'selectArticulos':
      $rspta = $articulo->listarActivosVenta();
      header('Content-Type: text/html; charset=utf-8');
      while ($reg = $rspta->fetch_object()) {
        $text = htmlspecialchars(($reg->nombre ?? ''), ENT_QUOTES, 'UTF-8');
        echo '<option value="' . $reg->idarticulo . '">' . $text . '</option>';
      }
      exit;

    case 'articulos_stock_bajo':
      header('Content-Type: application/json; charset=utf-8');

      $sql = "SELECT nombre, stock 
              FROM articulo 
              WHERE condicion = 1 AND stock > 0 AND stock < 5 
              ORDER BY stock ASC, nombre ASC";

      $rspta = ejecutarConsulta($sql);
      $articulos = array();
      $total = 0;

      if ($rspta) {
        while ($reg = $rspta->fetch_object()) {
          $articulos[] = $reg->nombre . ' (' . $reg->stock . ')';
          $total++;
        }
      }

      echo json_encode(array(
        'success' => true,
        'total' => $total,
        'articulos' => $articulos
      ));
      exit;

    case 'articulos_sin_stock':
      header('Content-Type: application/json; charset=utf-8');

      $sql = "SELECT nombre 
              FROM articulo 
              WHERE condicion = 1 AND stock <= 0 
              ORDER BY nombre ASC";

      $rspta = ejecutarConsulta($sql);
      $articulos = array();
      $total = 0;

      if ($rspta) {
        while ($reg = $rspta->fetch_object()) {
          $articulos[] = $reg->nombre;
          $total++;
        }
      }

      echo json_encode(array(
        'success' => true,
        'total' => $total,
        'articulos' => $articulos
      ));
      exit;

    case 'kpi_detalle':
      header('Content-Type: application/json; charset=utf-8');
      $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

      $result = array('success' => true, 'tipo' => $tipo, 'titulo' => '', 'descripcion' => '', 'datos' => array());

      switch ($tipo) {
        case 'total':
          $result['titulo'] = 'Total de Artículos';
          $result['descripcion'] = 'Listado de todos los artículos registrados en el sistema';
          $sql = "SELECT a.nombre, a.codigo, c.nombre as categoria, m.nombre as marca, a.stock, a.precio_venta,
                         CASE WHEN a.condicion = 1 THEN 'Activo' ELSE 'Inactivo' END as estado
                  FROM articulo a
                  LEFT JOIN categoria c ON a.idcategoria = c.idcategoria
                  LEFT JOIN marca m ON a.idmarca = m.idmarca
                  ORDER BY a.nombre ASC LIMIT 50";
          $rspta = ejecutarConsulta($sql);
          while ($reg = $rspta->fetch_object()) {
            $result['datos'][] = array(
              'nombre' => $reg->nombre,
              'codigo' => $reg->codigo,
              'categoria' => $reg->categoria,
              'marca' => $reg->marca,
              'stock' => $reg->stock,
              'precio' => 'S/ ' . number_format($reg->precio_venta, 2),
              'estado' => $reg->estado
            );
          }
          $result['columnas'] = ['Nombre', 'Código', 'Categoría', 'Marca', 'Stock', 'Precio', 'Estado'];
          break;

        case 'activos':
          $result['titulo'] = 'Artículos Activos';
          $result['descripcion'] = 'Artículos habilitados para venta y operaciones';
          $sql = "SELECT a.nombre, a.codigo, c.nombre as categoria, a.stock, a.precio_venta
                  FROM articulo a
                  LEFT JOIN categoria c ON a.idcategoria = c.idcategoria
                  WHERE a.condicion = 1
                  ORDER BY a.nombre ASC LIMIT 50";
          $rspta = ejecutarConsulta($sql);
          while ($reg = $rspta->fetch_object()) {
            $result['datos'][] = array(
              'nombre' => $reg->nombre,
              'codigo' => $reg->codigo,
              'categoria' => $reg->categoria,
              'stock' => $reg->stock,
              'precio' => 'S/ ' . number_format($reg->precio_venta, 2)
            );
          }
          $result['columnas'] = ['Nombre', 'Código', 'Categoría', 'Stock', 'Precio'];
          break;

        case 'inactivos':
          $result['titulo'] = 'Artículos Inactivos';
          $result['descripcion'] = 'Artículos deshabilitados que no aparecen en ventas';
          $sql = "SELECT a.nombre, a.codigo, c.nombre as categoria, a.stock
                  FROM articulo a
                  LEFT JOIN categoria c ON a.idcategoria = c.idcategoria
                  WHERE a.condicion = 0
                  ORDER BY a.nombre ASC";
          $rspta = ejecutarConsulta($sql);
          while ($reg = $rspta->fetch_object()) {
            $result['datos'][] = array(
              'nombre' => $reg->nombre,
              'codigo' => $reg->codigo,
              'categoria' => $reg->categoria,
              'stock' => $reg->stock
            );
          }
          $result['columnas'] = ['Nombre', 'Código', 'Categoría', 'Stock'];
          break;

        case 'con_stock':
          $result['titulo'] = 'Artículos con Stock Disponible';
          $result['descripcion'] = 'Artículos que tienen unidades disponibles para venta';
          $sql = "SELECT a.nombre, a.codigo, c.nombre as categoria, a.stock, a.precio_venta
                  FROM articulo a
                  LEFT JOIN categoria c ON a.idcategoria = c.idcategoria
                  WHERE a.condicion = 1 AND a.stock > 0
                  ORDER BY a.stock DESC LIMIT 50";
          $rspta = ejecutarConsulta($sql);
          while ($reg = $rspta->fetch_object()) {
            $result['datos'][] = array(
              'nombre' => $reg->nombre,
              'codigo' => $reg->codigo,
              'categoria' => $reg->categoria,
              'stock' => $reg->stock,
              'precio' => 'S/ ' . number_format($reg->precio_venta, 2)
            );
          }
          $result['columnas'] = ['Nombre', 'Código', 'Categoría', 'Stock', 'Precio'];
          break;

        case 'stock_total':
          $result['titulo'] = 'Distribución de Stock por Categoría';
          $result['descripcion'] = 'Resumen del inventario total agrupado por categoría';
          $sql = "SELECT c.nombre as categoria, 
                         COUNT(a.idarticulo) as productos,
                         SUM(a.stock) as unidades,
                         SUM(a.stock * a.precio_venta) as valor_inventario
                  FROM articulo a
                  LEFT JOIN categoria c ON a.idcategoria = c.idcategoria
                  WHERE a.condicion = 1
                  GROUP BY c.idcategoria, c.nombre
                  ORDER BY unidades DESC";
          $rspta = ejecutarConsulta($sql);
          while ($reg = $rspta->fetch_object()) {
            $result['datos'][] = array(
              'categoria' => $reg->categoria ?? 'Sin categoría',
              'productos' => $reg->productos,
              'unidades' => $reg->unidades,
              'valor' => 'S/ ' . number_format($reg->valor_inventario, 2)
            );
          }
          $result['columnas'] = ['Categoría', 'Productos', 'Unidades', 'Valor Inventario'];
          break;

        default:
          $result['success'] = false;
          $result['mensaje'] = 'Tipo de KPI no válido';
      }

      echo json_encode($result);
      exit;

    default:
      json_msg(false, "Operación no válida", 400);
  }
} catch (Throwable $e) {
  json_msg(false, "Error: " . $e->getMessage(), 500);
}

ob_end_flush();
