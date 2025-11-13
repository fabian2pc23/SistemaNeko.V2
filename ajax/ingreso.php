<?php
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

/* ======= Autenticación / Autorización ======= */
if (!isset($_SESSION["nombre"])) {
    header("Location: ../vistas/login.html");
    exit();
}
if (!isset($_SESSION['compras']) || (int)$_SESSION['compras'] !== 1) {
    require 'noacceso.php';
    ob_end_flush();
    exit();
}

/* ======= Modelos ======= */
require_once "../modelos/Ingreso.php";
$ingreso = new Ingreso();

/* ======= Inputs base ======= */
$idingreso         = isset($_POST["idingreso"])       ? limpiarCadena($_POST["idingreso"])       : "";
$idproveedor       = isset($_POST["idproveedor"])     ? limpiarCadena($_POST["idproveedor"])     : "";
$idusuario         = $_SESSION["idusuario"];
$tipo_comprobante  = isset($_POST["tipo_comprobante"])  ? limpiarCadena($_POST["tipo_comprobante"])  : "";
$serie_comprobante = isset($_POST["serie_comprobante"]) ? limpiarCadena($_POST["serie_comprobante"]) : ""; 
$num_comprobante   = isset($_POST["num_comprobante"])   ? limpiarCadena($_POST["num_comprobante"])   : "";
$fecha_hora        = isset($_POST["fecha_hora"])        ? limpiarCadena($_POST["fecha_hora"])        : "";
// Campo corregido: ahora lee "impuesto"
$impuesto_total    = isset($_POST["impuesto"])          ? limpiarCadena($_POST["impuesto"])          : "0"; 
$total_compra      = isset($_POST["total_compra"])      ? limpiarCadena($_POST["total_compra"])      : "0";
$op = isset($_GET["op"]) ? $_GET["op"] : '';

switch ($op) {

    /* =========================
    * Insertar nuevo ingreso
    * ========================= */
    case 'guardaryeditar':
        if (!empty($idingreso)) {
            echo "Este módulo solo inserta (no edita).";
            break;
        }

        // Validación de fecha
        $tz    = new DateTimeZone('America/Lima');
        $raw   = trim($fecha_hora);
        $fh    = DateTime::createFromFormat('Y-m-d', $raw, $tz);
        if (!$fh) { $fh = DateTime::createFromFormat('d/m/Y', $raw, $tz); }
        if (!$fh) {
            http_response_code(400);
            echo "Fecha inválida.";
            break;
        }
        $fecha_sql = $fh->format('Y-m-d');

        // Inserción
        $rspta = $ingreso->insertar(
            $idproveedor,
            $idusuario,
            $tipo_comprobante,
            $serie_comprobante,
            $num_comprobante,
            $fecha_sql,
            $impuesto_total,
            $total_compra,
            isset($_POST["idarticulo"])      ? $_POST["idarticulo"]      : [],
            isset($_POST["cantidad"])        ? $_POST["cantidad"]        : [],
            isset($_POST["precio_compra"]) ? $_POST["precio_compra"] : []
        );

        echo $rspta ? "Ingreso registrado" : "No se pudieron registrar todos los datos del ingreso";
    break;

    /* =========================
    * Anular ingreso
    * ========================= */
    case 'anular':
        $rspta = $ingreso->anular($idingreso);
        echo $rspta ? "Ingreso anulado" : "Ingreso no se puede anular";
    break;

    /* =========================
    * Mostrar cabecera por id
    * ========================= */
    case 'mostrar':
        $id = isset($_POST['idingreso']) ? (int)$_POST['idingreso'] : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

        header('Content-Type: application/json; charset=utf-8');
        if ($id <= 0) {
            echo json_encode(['error' => 'ID de ingreso requerido']);
            exit;
        }

        $raw = $ingreso->mostrar($id);
        echo json_encode($raw);
    break;

    /* =========================
    * Listar detalle HTML
    * ========================= */
    case 'listarDetalle':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id <= 0) {
            // Si el ID es inválido, no retornamos nada para el cuerpo de la tabla.
            echo ''; 
            break;
        }

        $rspta = $ingreso->listarDetalle($id);

        // Solo se imprimen las filas (<tr>) que forman el <tbody>
        while ($reg = $rspta->fetch_object()) {
            $pc  = (float)$reg->precio_compra;
            $qty = (float)$reg->cantidad;
            $sub = $pc * $qty;

            // Incluimos los inputs en modo solo lectura (readonly) y el <td> vacío para opciones.
            echo '<tr class="filas">
                      <td></td> 
                      <td><input type="hidden" name="idarticulo[]" value="'.(int)$reg->idarticulo.'">'.htmlspecialchars($reg->nombre,ENT_QUOTES,'UTF-8').'</td>
                      <td><input type="number" name="cantidad[]" value="'.$qty.'" min="1" readonly></td>
                      <td><input type="number" name="precio_compra[]" value="'.number_format($pc,2,'.','').'" step="0.01" min="0" readonly></td>
                      <td><span name="subtotal" class="subtotal">'.number_format($sub,2,'.','').'</span></td>
                  </tr>';
        }

    break;

    /* =========================
    * Listado general (DataTables)
    * ========================= */
    case 'listar':
        $desde = isset($_GET['desde']) ? trim($_GET['desde']) : '';
        $hasta = isset($_GET['hasta']) ? trim($_GET['hasta']) : '';

        $rspta = $ingreso->listar($desde, $hasta);

        $data = array();
        while ($reg = $rspta->fetch_object()) {
            $btnVer   = '<button class="btn btn-warning" title="Ver" onclick="mostrar('.$reg->idingreso.')"><i class="fa fa-eye"></i></button>';
            $btnAnular= '<button class="btn btn-danger"  title="Anular" onclick="anular('.$reg->idingreso.')"><i class="fa fa-close"></i></button>';
            $btnPdf   = '<a target="_blank" href="../reportes/exIngreso.php?id='.$reg->idingreso.'">
                             <button class="btn btn-info" title="Comprobante"><i class="fa fa-file"></i></button>
                           </a>';

            $ops = ($reg->estado=='Aceptado') ? ($btnVer.' '.$btnAnular.' '.$btnPdf) : ($btnVer.' '.$btnPdf);

            $data[] = array(
                "0" => $ops,
                "1" => $reg->fecha,
                "2" => $reg->proveedor,
                "3" => $reg->usuario,
                "4" => $reg->tipo_comprobante,
                "5" => $reg->serie_comprobante.'-'.$reg->num_comprobante,
                "6" => $reg->total_compra,
                "7" => ($reg->estado=='Aceptado')
                         ? '<span class="label bg-green">Aceptado</span>'
                         : '<span class="label bg-red">Anulado</span>'
            );
        }

        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($results);
    break;

    /* =========================
    * Select de Proveedor
    * ========================= */
    case 'selectProveedor':
        require_once "../modelos/Persona.php";
        $persona = new Persona();
        $rspta = $persona->selectProveedoresActivos();

        header('Content-Type: text/html; charset=utf-8');
        while ($reg = $rspta->fetch_object()) {
            echo '<option value="' . $reg->idpersona . '">' . htmlspecialchars($reg->nombre, ENT_QUOTES, 'UTF-8') . '</option>';
        }
        exit;

    /* =========================
    * Listar artículos (modal)
    * ========================= */
    case 'listarArticulos':
        require_once "../modelos/Articulo.php";
        $articulo = new Articulo();
        $rspta = $articulo->listarActivos();
        $data  = array();

        while ($reg = $rspta->fetch_object()) {
            $btn = '<button class="btn btn-warning" '.
                   'onclick="agregarDetalle('.(int)$reg->idarticulo.',\''.
                     addslashes($reg->nombre).'\','.
                     number_format((float)$reg->precio_compra,2,'.','').')">'.
                     '<span class="fa fa-plus"></span></button>';

            // CORRECCIÓN 404: Lógica para usar default.png si el campo está vacío
            $nombre_imagen = empty($reg->imagen) ? "default.png" : htmlspecialchars($reg->imagen,ENT_QUOTES,'UTF-8');
            
            $img = "<img src=\"../files/articulos/".$nombre_imagen."\" " .
                     "style=\"width:46px;height:46px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb\">";

            $data[] = array(
                "0" => $btn,
                "1" => htmlspecialchars($reg->nombre,ENT_QUOTES,'UTF-8'),
                "2" => htmlspecialchars($reg->categoria,ENT_QUOTES,'UTF-8'),
                "3" => htmlspecialchars($reg->codigo,ENT_QUOTES,'UTF-8'),
                "4" => (int)$reg->stock,
                "5" => number_format((float)$reg->precio_compra, 2, '.', ''),
                "6" => $img
            );
        }

        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($results);
    break;

    default:
        http_response_code(400);
        echo "Operación no válida";
    break;
}

ob_end_flush();
?>