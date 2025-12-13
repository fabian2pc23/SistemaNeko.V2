<?php
// ajax/escritorio.php
ob_start();
if (strlen(session_id()) < 1) {
    session_start();
}

if (!isset($_SESSION["nombre"])) {
    header("Location: ../vistas/login.html");
} else {
    if (!empty($_SESSION['escritorio']) && (int)$_SESSION['escritorio'] === 1) {
        require_once "../config/Conexion.php";

        $op = isset($_GET["op"]) ? $_GET["op"] : "";

        switch ($op) {
            case 'kpi_detalle':
                header('Content-Type: application/json; charset=utf-8');
                $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
                $result = array(
                    'success' => true,
                    'tipo' => $tipo,
                    'titulo' => '',
                    'descripcion' => '',
                    'datos' => array(),
                    'columnas' => array()
                );

                switch ($tipo) {
                    // ============ KPIs del Resumen Ejecutivo ============
                    case 'ventas-historico':
                        $result['titulo'] = 'Ventas Históricas Totales';
                        $result['descripcion'] = 'Resumen de ventas por año';
                        $sql = "SELECT YEAR(v.fecha_hora) as anio, 
                                       COUNT(v.idventa) as cantidad,
                                       SUM(v.total_venta) as total
                                FROM venta v
                                WHERE v.estado = 'Aceptado'
                                GROUP BY YEAR(v.fecha_hora)
                                ORDER BY YEAR(v.fecha_hora) DESC";
                        $rspta = ejecutarConsulta($sql);
                        while ($reg = $rspta->fetch_object()) {
                            $result['datos'][] = array(
                                'anio' => $reg->anio,
                                'cantidad' => (int)$reg->cantidad,
                                'total' => 'S/ ' . number_format($reg->total, 2)
                            );
                        }
                        $result['columnas'] = ['Año', 'Ventas', 'Total'];
                        break;

                    case 'compras-historico':
                        $result['titulo'] = 'Compras Históricas Totales';
                        $result['descripcion'] = 'Resumen de compras por año';
                        $sql = "SELECT YEAR(i.fecha_hora) as anio, 
                                       COUNT(i.idingreso) as cantidad,
                                       SUM(i.total_compra) as total
                                FROM ingreso i
                                WHERE i.estado = 'Aceptado'
                                GROUP BY YEAR(i.fecha_hora)
                                ORDER BY YEAR(i.fecha_hora) DESC";
                        $rspta = ejecutarConsulta($sql);
                        while ($reg = $rspta->fetch_object()) {
                            $result['datos'][] = array(
                                'anio' => $reg->anio,
                                'cantidad' => (int)$reg->cantidad,
                                'total' => 'S/ ' . number_format($reg->total, 2)
                            );
                        }
                        $result['columnas'] = ['Año', 'Compras', 'Total'];
                        break;

                    case 'margen':
                    case 'margen-bruto':
                        $result['titulo'] = 'Análisis de Margen de Ganancia';
                        $result['descripcion'] = 'Comparativa de ventas, costos y márgenes por mes (últimos 12 meses)';
                        $sql = "SELECT 
                                    DATE_FORMAT(v.fecha_hora, '%Y-%m') as mes,
                                    SUM(dv.cantidad * dv.precio_venta) as ingresos,
                                    SUM(dv.cantidad * a.precio_compra) as costos
                                FROM detalle_venta dv
                                INNER JOIN venta v ON dv.idventa = v.idventa
                                INNER JOIN articulo a ON dv.idarticulo = a.idarticulo
                                WHERE v.estado = 'Aceptado'
                                AND v.fecha_hora >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                                GROUP BY DATE_FORMAT(v.fecha_hora, '%Y-%m')
                                ORDER BY mes DESC";
                        $rspta = ejecutarConsulta($sql);
                        while ($reg = $rspta->fetch_object()) {
                            $ingresos = (float)$reg->ingresos;
                            $costos = (float)$reg->costos;
                            $margen = $ingresos - $costos;
                            $porcentaje = $ingresos > 0 ? (($margen / $ingresos) * 100) : 0;
                            $result['datos'][] = array(
                                'mes' => $reg->mes,
                                'ingresos' => 'S/ ' . number_format($ingresos, 2),
                                'costos' => 'S/ ' . number_format($costos, 2),
                                'margen' => 'S/ ' . number_format($margen, 2),
                                'porcentaje' => number_format($porcentaje, 1) . '%'
                            );
                        }
                        $result['columnas'] = ['Mes', 'Ingresos', 'Costos', 'Margen', '% Rentab.'];
                        break;

                    case 'transacciones':
                        $result['titulo'] = 'Historial de Transacciones';
                        $result['descripcion'] = 'Últimas 50 transacciones registradas';
                        $sql = "SELECT DATE_FORMAT(v.fecha_hora, '%d/%m/%Y %H:%i') as fecha,
                                       p.nombre as cliente,
                                       v.tipo_comprobante,
                                       CONCAT(v.serie_comprobante, '-', v.num_comprobante) as comprobante,
                                       v.total_venta,
                                       v.estado
                                FROM venta v
                                LEFT JOIN persona p ON v.idcliente = p.idpersona
                                ORDER BY v.fecha_hora DESC
                                LIMIT 50";
                        $rspta = ejecutarConsulta($sql);
                        while ($reg = $rspta->fetch_object()) {
                            $result['datos'][] = array(
                                'fecha' => $reg->fecha,
                                'cliente' => $reg->cliente ?: 'Cliente',
                                'tipo' => $reg->tipo_comprobante,
                                'comprobante' => $reg->comprobante,
                                'total' => 'S/ ' . number_format($reg->total_venta, 2),
                                'estado' => $reg->estado
                            );
                        }
                        $result['columnas'] = ['Fecha', 'Cliente', 'Tipo', 'Comprobante', 'Total', 'Estado'];
                        break;

                    // ============ KPIs de Operaciones del Día ============
                    case 'compras-hoy':
                        $result['titulo'] = 'Compras de Hoy';
                        $result['descripcion'] = 'Detalle de compras registradas hoy';
                        $sql = "SELECT DATE_FORMAT(i.fecha_hora, '%H:%i') as hora,
                                       p.nombre as proveedor,
                                       i.tipo_comprobante,
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
                                'proveedor' => $reg->proveedor ?: 'Proveedor',
                                'tipo' => $reg->tipo_comprobante,
                                'comprobante' => $reg->comprobante,
                                'total' => 'S/ ' . number_format($reg->total_compra, 2)
                            );
                        }
                        $result['columnas'] = ['Hora', 'Proveedor', 'Tipo', 'Comprobante', 'Total'];
                        break;

                    case 'ventas-hoy':
                        $result['titulo'] = 'Ventas de Hoy';
                        $result['descripcion'] = 'Detalle de ventas registradas hoy';
                        $sql = "SELECT DATE_FORMAT(v.fecha_hora, '%H:%i') as hora,
                                       p.nombre as cliente,
                                       v.tipo_comprobante,
                                       CONCAT(v.serie_comprobante, '-', v.num_comprobante) as comprobante,
                                       v.total_venta
                                FROM venta v
                                LEFT JOIN persona p ON v.idcliente = p.idpersona
                                WHERE DATE(v.fecha_hora) = CURDATE() AND v.estado = 'Aceptado'
                                ORDER BY v.fecha_hora DESC";
                        $rspta = ejecutarConsulta($sql);
                        while ($reg = $rspta->fetch_object()) {
                            $result['datos'][] = array(
                                'hora' => $reg->hora,
                                'cliente' => $reg->cliente ?: 'Cliente',
                                'tipo' => $reg->tipo_comprobante,
                                'comprobante' => $reg->comprobante,
                                'total' => 'S/ ' . number_format($reg->total_venta, 2)
                            );
                        }
                        $result['columnas'] = ['Hora', 'Cliente', 'Tipo', 'Comprobante', 'Total'];
                        break;

                    // ============ KPIs del Análisis Financiero ============
                    case 'inventario':
                        $result['titulo'] = 'Detalle del Inventario';
                        $result['descripcion'] = 'Top 50 productos por valor en inventario';
                        $sql = "SELECT a.nombre,
                                       c.nombre as categoria,
                                       a.stock,
                                       a.precio_compra,
                                       (a.stock * a.precio_compra) as valor_inventario
                                FROM articulo a
                                INNER JOIN categoria c ON a.idcategoria = c.idcategoria
                                WHERE a.condicion = 1 AND a.stock > 0
                                ORDER BY valor_inventario DESC
                                LIMIT 50";
                        $rspta = ejecutarConsulta($sql);
                        while ($reg = $rspta->fetch_object()) {
                            $result['datos'][] = array(
                                'producto' => $reg->nombre,
                                'categoria' => $reg->categoria,
                                'stock' => (int)$reg->stock,
                                'precio' => 'S/ ' . number_format($reg->precio_compra, 2),
                                'valor' => 'S/ ' . number_format($reg->valor_inventario, 2)
                            );
                        }
                        $result['columnas'] = ['Producto', 'Categoría', 'Stock', 'P. Compra', 'Valor Inventario'];
                        break;

                    case 'clientes':
                        $result['titulo'] = 'Clientes Activos del Periodo';
                        $result['descripcion'] = 'Clientes que han realizado compras recientemente';
                        $sql = "SELECT p.nombre as cliente,
                                       p.tipo_documento,
                                       p.num_documento,
                                       COUNT(v.idventa) as compras,
                                       SUM(v.total_venta) as total_gastado,
                                       MAX(DATE_FORMAT(v.fecha_hora, '%d/%m/%Y')) as ultima_compra
                                FROM venta v
                                INNER JOIN persona p ON v.idcliente = p.idpersona
                                WHERE v.estado = 'Aceptado'
                                GROUP BY v.idcliente
                                ORDER BY total_gastado DESC
                                LIMIT 50";
                        $rspta = ejecutarConsulta($sql);
                        while ($reg = $rspta->fetch_object()) {
                            $result['datos'][] = array(
                                'cliente' => $reg->cliente,
                                'documento' => $reg->tipo_documento . ': ' . $reg->num_documento,
                                'compras' => (int)$reg->compras,
                                'total' => 'S/ ' . number_format($reg->total_gastado, 2),
                                'ultima' => $reg->ultima_compra
                            );
                        }
                        $result['columnas'] = ['Cliente', 'Documento', 'Compras', 'Total Gastado', 'Última Compra'];
                        break;

                    case 'ticket':
                        $result['titulo'] = 'Análisis de Ticket Promedio';
                        $result['descripcion'] = 'Últimas 30 ventas para análisis de ticket';
                        $sql = "SELECT DATE_FORMAT(v.fecha_hora, '%d/%m/%Y %H:%i') as fecha,
                                       p.nombre as cliente,
                                       v.tipo_comprobante,
                                       v.total_venta
                                FROM venta v
                                LEFT JOIN persona p ON v.idcliente = p.idpersona
                                WHERE v.estado = 'Aceptado'
                                ORDER BY v.fecha_hora DESC
                                LIMIT 30";
                        $rspta = ejecutarConsulta($sql);
                        $total = 0;
                        $count = 0;
                        while ($reg = $rspta->fetch_object()) {
                            $result['datos'][] = array(
                                'fecha' => $reg->fecha,
                                'cliente' => $reg->cliente ?: 'Cliente',
                                'tipo' => $reg->tipo_comprobante,
                                'total' => 'S/ ' . number_format($reg->total_venta, 2)
                            );
                            $total += $reg->total_venta;
                            $count++;
                        }
                        $promedio = $count > 0 ? $total / $count : 0;
                        $result['descripcion'] = 'Ticket promedio: S/ ' . number_format($promedio, 2) . ' | Mostrando últimas ' . $count . ' ventas';
                        $result['columnas'] = ['Fecha', 'Cliente', 'Tipo', 'Total'];
                        break;

                    // ============ KPIs de Stock Crítico ============
                    case 'stock-critico':
                        $result['titulo'] = 'Productos con Stock Crítico';
                        $result['descripcion'] = 'Productos con menos de 5 unidades en stock';
                        $sql = "SELECT a.nombre,
                                       c.nombre as categoria,
                                       m.nombre as marca,
                                       a.stock,
                                       a.precio_compra,
                                       a.precio_venta
                                FROM articulo a
                                INNER JOIN categoria c ON a.idcategoria = c.idcategoria
                                LEFT JOIN marca m ON a.idmarca = m.idmarca
                                WHERE a.condicion = 1 AND a.stock > 0 AND a.stock < 5
                                ORDER BY a.stock ASC";
                        $rspta = ejecutarConsulta($sql);
                        while ($reg = $rspta->fetch_object()) {
                            $result['datos'][] = array(
                                'producto' => $reg->nombre,
                                'categoria' => $reg->categoria,
                                'marca' => $reg->marca ?: '-',
                                'stock' => (int)$reg->stock,
                                'p_compra' => 'S/ ' . number_format($reg->precio_compra, 2),
                                'p_venta' => 'S/ ' . number_format($reg->precio_venta, 2)
                            );
                        }
                        $result['columnas'] = ['Producto', 'Categoría', 'Marca', 'Stock', 'P. Compra', 'P. Venta'];
                        break;

                    // ============ KPIs de Top Productos ============
                    case 'top-productos':
                        $result['titulo'] = 'Top Productos Más Vendidos';
                        $result['descripcion'] = 'Productos con mayor volumen de ventas';
                        $sql = "SELECT a.nombre,
                                       c.nombre as categoria,
                                       SUM(dv.cantidad) as unidades,
                                       SUM(dv.cantidad * dv.precio_venta) as ingresos
                                FROM detalle_venta dv
                                INNER JOIN venta v ON dv.idventa = v.idventa
                                INNER JOIN articulo a ON dv.idarticulo = a.idarticulo
                                INNER JOIN categoria c ON a.idcategoria = c.idcategoria
                                WHERE v.estado = 'Aceptado'
                                GROUP BY a.idarticulo
                                ORDER BY unidades DESC
                                LIMIT 30";
                        $rspta = ejecutarConsulta($sql);
                        while ($reg = $rspta->fetch_object()) {
                            $result['datos'][] = array(
                                'producto' => $reg->nombre,
                                'categoria' => $reg->categoria,
                                'unidades' => (int)$reg->unidades,
                                'ingresos' => 'S/ ' . number_format($reg->ingresos, 2)
                            );
                        }
                        $result['columnas'] = ['Producto', 'Categoría', 'Unidades Vendidas', 'Ingresos'];
                        break;

                    // ============ KPIs de Top Clientes ============
                    case 'top-clientes':
                        $result['titulo'] = 'Top Clientes';
                        $result['descripcion'] = 'Clientes con mayor facturación';
                        $sql = "SELECT p.nombre as cliente,
                                       COUNT(v.idventa) as compras,
                                       SUM(v.total_venta) as total
                                FROM venta v
                                INNER JOIN persona p ON v.idcliente = p.idpersona
                                WHERE v.estado = 'Aceptado'
                                GROUP BY v.idcliente
                                ORDER BY total DESC
                                LIMIT 20";
                        $rspta = ejecutarConsulta($sql);
                        while ($reg = $rspta->fetch_object()) {
                            $result['datos'][] = array(
                                'cliente' => $reg->cliente,
                                'compras' => (int)$reg->compras,
                                'total' => 'S/ ' . number_format($reg->total, 2)
                            );
                        }
                        $result['columnas'] = ['Cliente', 'Compras', 'Total Facturado'];
                        break;

                    // ============ KPIs de Rentabilidad ============
                    case 'productos-rentables':
                        $result['titulo'] = 'Productos Más Rentables';
                        $result['descripcion'] = 'Productos ordenados por margen de ganancia';
                        $sql = "SELECT a.nombre,
                                       c.nombre as categoria,
                                       SUM(dv.cantidad) as unidades,
                                       SUM(dv.cantidad * dv.precio_venta) as ingresos,
                                       SUM(dv.cantidad * a.precio_compra) as costos,
                                       (SUM(dv.cantidad * dv.precio_venta) - SUM(dv.cantidad * a.precio_compra)) as ganancia,
                                       ROUND(((SUM(dv.cantidad * dv.precio_venta) - SUM(dv.cantidad * a.precio_compra)) / 
                                              SUM(dv.cantidad * dv.precio_venta) * 100), 2) as margen
                                FROM detalle_venta dv
                                INNER JOIN venta v ON dv.idventa = v.idventa
                                INNER JOIN articulo a ON dv.idarticulo = a.idarticulo
                                INNER JOIN categoria c ON a.idcategoria = c.idcategoria
                                WHERE v.estado = 'Aceptado'
                                GROUP BY a.idarticulo
                                HAVING unidades >= 2
                                ORDER BY margen DESC
                                LIMIT 30";
                        $rspta = ejecutarConsulta($sql);
                        while ($reg = $rspta->fetch_object()) {
                            $result['datos'][] = array(
                                'producto' => $reg->nombre,
                                'categoria' => $reg->categoria,
                                'unidades' => (int)$reg->unidades,
                                'ganancia' => 'S/ ' . number_format($reg->ganancia, 2),
                                'margen' => $reg->margen . '%'
                            );
                        }
                        $result['columnas'] = ['Producto', 'Categoría', 'Unidades', 'Ganancia', '% Margen'];
                        break;

                    case 'productos-menos-rentables':
                        $result['titulo'] = 'Productos Menos Rentables';
                        $result['descripcion'] = 'Productos con menor margen de ganancia';
                        $sql = "SELECT a.nombre,
                                       c.nombre as categoria,
                                       SUM(dv.cantidad) as unidades,
                                       SUM(dv.cantidad * dv.precio_venta) as ingresos,
                                       SUM(dv.cantidad * a.precio_compra) as costos,
                                       (SUM(dv.cantidad * dv.precio_venta) - SUM(dv.cantidad * a.precio_compra)) as ganancia,
                                       ROUND(((SUM(dv.cantidad * dv.precio_venta) - SUM(dv.cantidad * a.precio_compra)) / 
                                              SUM(dv.cantidad * dv.precio_venta) * 100), 2) as margen
                                FROM detalle_venta dv
                                INNER JOIN venta v ON dv.idventa = v.idventa
                                INNER JOIN articulo a ON dv.idarticulo = a.idarticulo
                                INNER JOIN categoria c ON a.idcategoria = c.idcategoria
                                WHERE v.estado = 'Aceptado'
                                GROUP BY a.idarticulo
                                HAVING unidades >= 2
                                ORDER BY margen ASC
                                LIMIT 30";
                        $rspta = ejecutarConsulta($sql);
                        while ($reg = $rspta->fetch_object()) {
                            $result['datos'][] = array(
                                'producto' => $reg->nombre,
                                'categoria' => $reg->categoria,
                                'unidades' => (int)$reg->unidades,
                                'ganancia' => 'S/ ' . number_format($reg->ganancia, 2),
                                'margen' => $reg->margen . '%'
                            );
                        }
                        $result['columnas'] = ['Producto', 'Categoría', 'Unidades', 'Ganancia', '% Margen'];
                        break;

                    default:
                        $result['success'] = false;
                        $result['titulo'] = 'Tipo no reconocido';
                        $result['descripcion'] = 'El tipo de KPI solicitado no existe: ' . $tipo;
                        break;
                }
                echo json_encode($result);
                break;

            default:
                echo json_encode(array('success' => false, 'message' => 'Operación no definida'));
                break;
        }
    } else {
        echo json_encode(array('success' => false, 'message' => 'Acceso denegado'));
    }
}
ob_end_flush();
