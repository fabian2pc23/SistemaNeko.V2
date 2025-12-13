<?php
ob_start();

// ► ERRORES (útil en desarrollo)
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', 1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// ──────────────────────────────────────────
// 1. Verificación de sesión / permisos
// ──────────────────────────────────────────
if (!isset($_SESSION["nombre"])) {
    header("Location: ../vistas/login.html");
    exit();
}

if (!isset($_SESSION['compras']) || (int)$_SESSION['compras'] !== 1) {
    echo "Acceso denegado";
    ob_end_flush();
    exit();
}

// ──────────────────────────────────────────
// 2. Modelo
// ──────────────────────────────────────────
require_once "../modelos/Ingreso.php";
$ingreso = new Ingreso();

// ──────────────────────────────────────────
// 3. Variables comunes desde POST / SESSION
// ──────────────────────────────────────────
$idingreso        = isset($_POST["idingreso"])        ? limpiarCadena($_POST["idingreso"])        : "";
$idproveedor      = isset($_POST["idproveedor"])      ? limpiarCadena($_POST["idproveedor"])      : "";
$idusuario        = $_SESSION["idusuario"];
$tipo_comprobante = isset($_POST["tipo_comprobante"]) ? limpiarCadena($_POST["tipo_comprobante"]) : "";
$serie_comprobante = isset($_POST["serie_comprobante"]) ? limpiarCadena($_POST["serie_comprobante"]) : "";
$num_comprobante  = isset($_POST["num_comprobante"])  ? limpiarCadena($_POST["num_comprobante"])  : "";
$fecha_hora       = isset($_POST["fecha_hora"])       ? limpiarCadena($_POST["fecha_hora"])       : "";

// porcentaje de impuesto (ej. 18)
$impuesto_porcentaje = isset($_POST["impuesto"])
    ? (float)str_replace(',', '.', $_POST["impuesto"])
    : 18.00;

// tipo_ingreso viene del hidden del formulario (compra | alta_inicial | ajuste | devolucion)
$tipo_ingreso = isset($_POST["tipo_ingreso"]) ? limpiarCadena($_POST["tipo_ingreso"]) : "compra";
$tipo_ingreso_permitidos = ['compra', 'alta_inicial', 'ajuste', 'devolucion'];
if (!in_array($tipo_ingreso, $tipo_ingreso_permitidos, true)) {
    $tipo_ingreso = 'compra';
}

// Decimales (NO usar limpiarCadena aquí)
$total_neto_guardar = isset($_POST["total_neto"])
    ? (float)str_replace(',', '.', $_POST["total_neto"])
    : 0.00;

$impuesto_total = isset($_POST["monto_impuesto"])
    ? (float)str_replace(',', '.', $_POST["monto_impuesto"])
    : 0.00;

$total_compra = isset($_POST["total_compra"])
    ? (float)str_replace(',', '.', $_POST["total_compra"])
    : 0.00;

// Operación solicitada
$op = isset($_GET["op"]) ? $_GET["op"] : '';

switch ($op) {

    // ───────────────────────────────────────
    // GUARDAR / EDITAR (solo inserta)
    // ───────────────────────────────────────
    case 'guardaryeditar':

        error_log("=== GUARDARYEDITAR - INICIO ===");
        error_log("POST recibido: " . print_r($_POST, true));
        error_log("idproveedor: $idproveedor");
        error_log("idusuario: $idusuario");
        error_log("tipo_ingreso: $tipo_ingreso");
        error_log("total_neto RAW: " . ($_POST["total_neto"] ?? 'NO EXISTE'));
        error_log("total_neto convertido: $total_neto_guardar");
        error_log("monto_impuesto RAW: " . ($_POST["monto_impuesto"] ?? 'NO EXISTE'));
        error_log("monto_impuesto convertido: $impuesto_total");
        error_log("total_compra RAW: " . ($_POST["total_compra"] ?? 'NO EXISTE'));
        error_log("total_compra convertido: $total_compra");
        error_log("impuesto %: " . $impuesto_porcentaje);

        // ► Validaciones básicas
        if (empty($idusuario)) {
            error_log("ERROR: idusuario no está en sesión");
            echo "Error: Usuario no identificado. Inicie sesión nuevamente";
            break;
        }

        // Si es compra normal, exigir proveedor
        if ($tipo_ingreso === 'compra' && empty($idproveedor)) {
            error_log("ERROR: idproveedor vacío en compra");
            echo "Error: Debe seleccionar un proveedor para una compra";
            break;
        }

        if (!empty($idingreso)) {
            echo "Este módulo solo permite registrar nuevos ingresos.";
            break;
        }

        if ($total_neto_guardar <= 0 || $total_compra <= 0) {
            error_log("ERROR: Totales inválidos - Neto: $total_neto_guardar, Total: $total_compra");
            echo "Error: Los totales no pueden ser cero o negativos";
            break;
        }

        if (!isset($_POST["idarticulo"]) || empty($_POST["idarticulo"])) {
            error_log("ERROR: No hay artículos en el detalle");
            echo "Error: Debe agregar al menos un artículo";
            break;
        }

        // ► Parse de fecha
        $tz  = new DateTimeZone('America/Lima');
        $raw = trim($fecha_hora);

        $fh = DateTime::createFromFormat('Y-m-d', $raw, $tz);
        if (!$fh) {
            $fh = DateTime::createFromFormat('d/m/Y', $raw, $tz);
        }
        if (!$fh) {
            http_response_code(400);
            error_log("ERROR: Fecha inválida: $raw");
            echo "Fecha inválida";
            break;
        }

        $now         = new DateTime('now', $tz);
        $hora_actual = $now->format('H:i:s');
        $fecha_sql   = $fh->format('Y-m-d') . ' ' . $hora_actual;

        error_log("Fecha SQL: $fecha_sql");
        error_log("Llamando a Ingreso::insertar()...");

        try {
            $rspta = $ingreso->insertar(
                $idproveedor,
                $idusuario,
                $tipo_comprobante,
                $serie_comprobante,
                $num_comprobante,
                $fecha_sql,
                $total_neto_guardar,
                $impuesto_total,
                $impuesto_porcentaje,
                $total_compra,
                $tipo_ingreso,
                isset($_POST["idarticulo"]) ? $_POST["idarticulo"] : [],
                isset($_POST["cantidad"])   ? $_POST["cantidad"]   : [],
                isset($_POST["precio_compra"]) ? $_POST["precio_compra"] : []
            );

            if (is_array($rspta) && isset($rspta['success']) && !$rspta['success']) {
                error_log("❌ Error lógico: " . $rspta['message']);
                echo $rspta['message'];
            } elseif ($rspta) {
                error_log("✅ Inserción exitosa");
                echo "Ingreso registrado";
            } else {
                error_log("❌ insertar() retornó false");
                echo "No se pudieron registrar todos los datos del ingreso";
            }
        } catch (Exception $e) {
            error_log("❌ EXCEPCIÓN: " . $e->getMessage());
            echo "Error: " . $e->getMessage();
        }

        error_log("=== GUARDARYEDITAR - FIN ===");
        break;

    // ───────────────────────────────────────
    // ANULAR
    // ───────────────────────────────────────
    case 'anular':
        $rspta = $ingreso->anular($idingreso);
        echo $rspta ? "Ingreso anulado" : "Ingreso no se puede anular";
        break;

    // ───────────────────────────────────────
    // MOSTRAR (para ver cabecera en formulario)
    // ───────────────────────────────────────
    case 'mostrar':
        $id = isset($_POST['idingreso']) ? (int)$_POST['idingreso']
            : (isset($_GET['id']) ? (int)$_GET['id'] : 0);

        header('Content-Type: application/json; charset=utf-8');

        if ($id <= 0) {
            echo json_encode(['error' => 'ID de ingreso requerido']);
            exit;
        }

        $raw = $ingreso->mostrar($id);
        echo json_encode($raw);
        break;

    // ───────────────────────────────────────
    // LISTAR DETALLE (tbody del detalle)
    // ───────────────────────────────────────
    case 'listarDetalle':
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id <= 0) {
            echo '';
            break;
        }

        $rspta = $ingreso->listarDetalle($id);

        while ($reg = $rspta->fetch_object()) {
            $pc  = (float)$reg->precio_compra;
            $qty = (float)$reg->cantidad;
            $sub = $pc * $qty;

            echo '<tr class="filas">';
            echo '<td></td>';
            echo '<td><input type="hidden" name="idarticulo[]" value="' . (int)$reg->idarticulo . '">' . htmlspecialchars($reg->nombre, ENT_QUOTES, 'UTF-8') . '</td>';
            echo '<td><input type="number" name="cantidad[]" value="' . $qty . '" min="1" readonly></td>';
            echo '<td><input type="number" name="precio_compra[]" value="' . number_format($pc, 2, '.', '') . '" step="0.01" min="0" readonly></td>';
            echo '<td><span name="subtotal" class="subtotal">' . number_format($sub, 2, '.', '') . '</span></td>';
            echo '</tr>';
        }
        break;

    // ───────────────────────────────────────
    // LISTAR (para DataTables)
    // ───────────────────────────────────────
    case 'listar':
        $desde  = isset($_GET['desde'])  ? trim($_GET['desde'])  : '';
        $hasta  = isset($_GET['hasta'])  ? trim($_GET['hasta'])  : '';
        $estado = isset($_GET['estado']) ? trim($_GET['estado']) : 'todos';

        $rspta = $ingreso->listar($desde, $hasta, $estado);

        $data = array();
        while ($reg = $rspta->fetch_object()) {
            $btnVer    = '<button class="btn btn-warning btn-sm" title="Ver" onclick="mostrar(' . $reg->idingreso . ')"><i class="fa fa-eye"></i></button>';
            $btnAnular = '<button class="btn btn-danger btn-sm" title="Anular" onclick="anular(' . $reg->idingreso . ')"><i class="fa fa-close"></i></button>';
            $btnPdf    = '<a target="_blank" href="../reportes/exIngreso.php?id=' . $reg->idingreso . '"><button class="btn btn-info btn-sm" title="Comprobante"><i class="fa fa-file"></i></button></a>';

            $ops = '<div style="display:flex;gap:5px;justify-content:center;">';
            if ($reg->estado == 'Aceptado') {
                $ops .= $btnVer . $btnAnular . $btnPdf;
            } else {
                $ops .= $btnVer . $btnPdf;
            }
            $ops .= '</div>';

            $data[] = array(
                "0" => $ops,
                "1" => $reg->fecha,
                "2" => $reg->proveedor,
                "3" => $reg->usuario,
                "4" => $reg->tipo_comprobante,
                "5" => $reg->serie_comprobante . '-' . $reg->num_comprobante,
                "6" => '<div style="text-align:right;font-weight:500;">S/. ' . number_format((float)$reg->total_compra, 2, '.', ',') . '</div>',
                "7" => ($reg->estado == 'Aceptado')
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

    // ───────────────────────────────────────
    // SELECT PROVEEDOR (combo)
    // ───────────────────────────────────────
    case 'selectProveedor':
        require_once "../modelos/Persona.php";
        $persona = new Persona();
        $rspta   = $persona->selectProveedoresActivos();

        header('Content-Type: text/html; charset=utf-8');
        while ($reg = $rspta->fetch_object()) {
            echo '<option value="' . $reg->idpersona . '">' . htmlspecialchars($reg->nombre, ENT_QUOTES, 'UTF-8') . '</option>';
        }
        exit;

        // ───────────────────────────────────────
        // LISTAR ARTÍCULOS (modal de selección)
        // ───────────────────────────────────────
    case 'listarArticulos':
        require_once "../modelos/Articulo.php";
        $articulo = new Articulo();
        $rspta    = $articulo->listarActivos();
        $data     = array();

        while ($reg = $rspta->fetch_object()) {
            $btn = '<button class="btn btn-warning" onclick="agregarDetalle('
                . (int)$reg->idarticulo . ',\''
                . addslashes($reg->nombre) . '\','
                . number_format((float)$reg->precio_compra, 2, '.', '')
                . ')"><span class="fa fa-plus"></span></button>';

            $nombre_imagen = empty($reg->imagen)
                ? "default.png"
                : htmlspecialchars($reg->imagen, ENT_QUOTES, 'UTF-8');
            $img = '<img src="../files/articulos/' . $nombre_imagen . '" style="width:46px;height:46px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb">';

            $data[] = array(
                "0" => $btn,
                "1" => htmlspecialchars($reg->nombre, ENT_QUOTES, 'UTF-8'),
                "2" => htmlspecialchars($reg->categoria, ENT_QUOTES, 'UTF-8'),
                "3" => htmlspecialchars($reg->marca, ENT_QUOTES, 'UTF-8'),
                "4" => htmlspecialchars($reg->codigo, ENT_QUOTES, 'UTF-8'),
                "5" => (int)$reg->stock,
                "6" => number_format((float)$reg->precio_compra, 2, '.', ''),
                "7" => $img
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

    // ───────────────────────────────────────
    // OBTENER ÚLTIMA SERIE Y NÚMERO
    // ───────────────────────────────────────
    case 'getLastSerieNumero':
        $tipo_comprobante = isset($_POST['tipo_comprobante']) ? limpiarCadena($_POST['tipo_comprobante']) : 'Boleta';
        $rspta = $ingreso->getLastSerieNumero($tipo_comprobante);

        if ($rspta) {
            $serie = $rspta['serie_comprobante'];
            $num   = $rspta['num_comprobante'];

            // Intentar incrementar el número
            $nuevo_num = (int)$num + 1;
            $nuevo_num_str = str_pad($nuevo_num, 10, "0", STR_PAD_LEFT);

            echo json_encode([
                'serie' => $serie,
                'numero' => $nuevo_num_str
            ]);
        } else {
            // Valores por defecto si no hay registros previos
            $serie_def = ($tipo_comprobante == 'Factura') ? 'F001' : 'B001';
            echo json_encode([
                'serie' => $serie_def,
                'numero' => '0000000001'
            ]);
        }
        break;

    case 'kpi_detalle':
        header('Content-Type: application/json; charset=utf-8');
        require_once "../config/Conexion.php";
        $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
        $result = array('success' => true, 'tipo' => $tipo, 'titulo' => '', 'descripcion' => '', 'datos' => array(), 'columnas' => array());

        switch ($tipo) {
            case 'hoy':
                $result['titulo'] = 'Compras de Hoy';
                $result['descripcion'] = 'Detalle de ingresos registrados hoy';
                $sql = "SELECT i.idingreso, DATE_FORMAT(i.fecha_hora, '%H:%i') as hora, 
                               p.nombre as proveedor, i.tipo_comprobante, 
                               CONCAT(i.serie_comprobante, '-', i.num_comprobante) as comprobante,
                               i.total_compra
                        FROM ingreso i
                        LEFT JOIN persona p ON i.idproveedor = p.idpersona
                        WHERE DATE(i.fecha_hora) = CURDATE() AND i.estado = 'Aceptado'
                        ORDER BY i.fecha_hora DESC";
                $rspta = ejecutarConsulta($sql);
                while ($reg = $rspta->fetch_object()) {
                    $result['datos'][] = array(
                        'hora' => $reg->hora,
                        'proveedor' => $reg->proveedor ? $reg->proveedor : 'Sin proveedor',
                        'tipo' => $reg->tipo_comprobante,
                        'comprobante' => $reg->comprobante,
                        'total' => 'S/ ' . number_format($reg->total_compra, 2)
                    );
                }
                $result['columnas'] = ['Hora', 'Proveedor', 'Tipo', 'Comprobante', 'Total'];
                break;

            case 'mes':
                $result['titulo'] = 'Compras del Mes';
                $result['descripcion'] = 'Resumen de ingresos del mes actual por proveedor';
                $sql = "SELECT p.nombre as proveedor, COUNT(i.idingreso) as cantidad, SUM(i.total_compra) as total
                        FROM ingreso i
                        LEFT JOIN persona p ON i.idproveedor = p.idpersona
                        WHERE MONTH(i.fecha_hora) = MONTH(CURDATE()) AND YEAR(i.fecha_hora) = YEAR(CURDATE()) AND i.estado = 'Aceptado'
                        GROUP BY i.idproveedor
                        ORDER BY total DESC LIMIT 20";
                $rspta = ejecutarConsulta($sql);
                while ($reg = $rspta->fetch_object()) {
                    $result['datos'][] = array(
                        'proveedor' => $reg->proveedor ? $reg->proveedor : 'Sin proveedor',
                        'cantidad' => (int)$reg->cantidad,
                        'total' => 'S/ ' . number_format($reg->total, 2)
                    );
                }
                $result['columnas'] = ['Proveedor', 'Cantidad', 'Total'];
                break;

            case 'historico':
                $result['titulo'] = 'Resumen Histórico de Compras';
                $result['descripcion'] = 'Compras agrupadas por mes (últimos 12 meses)';
                $sql = "SELECT DATE_FORMAT(i.fecha_hora, '%Y-%m') as mes, 
                               COUNT(i.idingreso) as cantidad, SUM(i.total_compra) as total
                        FROM ingreso i
                        WHERE i.estado = 'Aceptado' AND i.fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                        GROUP BY mes ORDER BY mes DESC";
                $rspta = ejecutarConsulta($sql);
                while ($reg = $rspta->fetch_object()) {
                    $result['datos'][] = array(
                        'mes' => $reg->mes,
                        'cantidad' => (int)$reg->cantidad,
                        'total' => 'S/ ' . number_format($reg->total, 2)
                    );
                }
                $result['columnas'] = ['Mes', 'Ingresos', 'Total'];
                break;

            case 'aceptados':
                $result['titulo'] = 'Ingresos Aceptados';
                $result['descripcion'] = 'Últimos 30 ingresos con estado aceptado';
                $sql = "SELECT DATE_FORMAT(i.fecha_hora, '%d/%m/%Y') as fecha, p.nombre as proveedor, 
                               i.tipo_comprobante, i.total_compra
                        FROM ingreso i
                        LEFT JOIN persona p ON i.idproveedor = p.idpersona
                        WHERE i.estado = 'Aceptado'
                        ORDER BY i.fecha_hora DESC LIMIT 30";
                $rspta = ejecutarConsulta($sql);
                while ($reg = $rspta->fetch_object()) {
                    $result['datos'][] = array(
                        'fecha' => $reg->fecha,
                        'proveedor' => $reg->proveedor ? $reg->proveedor : 'Sin proveedor',
                        'tipo' => $reg->tipo_comprobante,
                        'total' => 'S/ ' . number_format($reg->total_compra, 2)
                    );
                }
                $result['columnas'] = ['Fecha', 'Proveedor', 'Tipo', 'Total'];
                break;

            case 'anulados':
                $result['titulo'] = 'Ingresos Anulados';
                $result['descripcion'] = 'Lista de ingresos que han sido anulados';
                $sql = "SELECT DATE_FORMAT(i.fecha_hora, '%d/%m/%Y') as fecha, p.nombre as proveedor, 
                               i.tipo_comprobante, i.total_compra
                        FROM ingreso i
                        LEFT JOIN persona p ON i.idproveedor = p.idpersona
                        WHERE i.estado = 'Anulado'
                        ORDER BY i.fecha_hora DESC";
                $rspta = ejecutarConsulta($sql);
                while ($reg = $rspta->fetch_object()) {
                    $result['datos'][] = array(
                        'fecha' => $reg->fecha,
                        'proveedor' => $reg->proveedor ? $reg->proveedor : 'Sin proveedor',
                        'tipo' => $reg->tipo_comprobante,
                        'total' => 'S/ ' . number_format($reg->total_compra, 2)
                    );
                }
                $result['columnas'] = ['Fecha', 'Proveedor', 'Tipo', 'Total'];
                break;
        }
        echo json_encode($result);
        break;

    // ───────────────────────────────────────
    // DEFAULT
    // ───────────────────────────────────────
    default:
        http_response_code(400);
        echo "Operación no válida";
        break;
}

ob_end_flush();
