<?php 
require "../config/Conexion.php";

class Consultas
{
    public function __construct(){}

    /* Compras entre fechas (ok) */
    public function comprasfecha($fecha_inicio,$fecha_fin){
        $sql="SELECT DATE(i.fecha_hora) as fecha,u.nombre as usuario, p.nombre as proveedor,
                     i.tipo_comprobante,i.serie_comprobante,i.num_comprobante,i.total_compra,
                     i.impuesto,i.estado
              FROM ingreso i
              INNER JOIN persona p ON i.idproveedor=p.idpersona
              INNER JOIN usuario u ON i.idusuario=u.idusuario
              WHERE DATE(i.fecha_hora)>='$fecha_inicio' 
                AND DATE(i.fecha_hora)<='$fecha_fin'";
        return ejecutarConsulta($sql);
    }

    /* Ventas por fecha y cliente (ok) */
    public function ventasfechacliente($fecha_inicio,$fecha_fin,$idcliente){
        $filtro_cliente = "";
        if (!empty($idcliente)) {
            $filtro_cliente = "AND v.idcliente='$idcliente'";
        }

        $sql="SELECT DATE(v.fecha_hora) as fecha,u.nombre as usuario, p.nombre as cliente,
                     v.tipo_comprobante,v.serie_comprobante,v.num_comprobante,v.total_venta,
                     v.impuesto,v.estado
              FROM venta v
              INNER JOIN persona p ON v.idcliente=p.idpersona
              INNER JOIN usuario u ON v.idusuario=u.idusuario
              WHERE DATE(v.fecha_hora)>='$fecha_inicio' 
                AND DATE(v.fecha_hora)<='$fecha_fin' 
                $filtro_cliente";
        return ejecutarConsulta($sql);
    }

    public function totalcomprahoy(){
        $sql="SELECT IFNULL(SUM(total_compra),0) as total_compra 
              FROM ingreso 
              WHERE DATE(fecha_hora)=CURDATE()";
        return ejecutarConsulta($sql);
    }

    public function totalventahoy(){
        $sql="SELECT IFNULL(SUM(total_venta),0) as total_venta 
              FROM venta 
              WHERE DATE(fecha_hora)=CURDATE()";
        return ejecutarConsulta($sql);
    }

    public function totalventahistorico(){
        $sql="SELECT IFNULL(SUM(total_venta),0) as total_venta 
              FROM venta 
              WHERE estado='Aceptado'";
        return ejecutarConsulta($sql);
    }

    public function totalcomprahistorico(){
        $sql="SELECT IFNULL(SUM(total_compra),0) as total_compra 
              FROM ingreso 
              WHERE estado='Aceptado'";
        return ejecutarConsulta($sql);
    }

    public function totaltransaccioneshistorico(){
        $sql="SELECT COUNT(*) as total_transacciones
              FROM venta 
              WHERE estado='Aceptado'";
        return ejecutarConsulta($sql);
    }

    /* ---------- FIX 1: últimos 10 días (agrupar por DÍA y ordenar asc) ---------- */
    public function comprasultimos_10dias(){
        $sql="SELECT DATE_FORMAT(DATE(i.fecha_hora),'%d-%m') AS fecha,
                     SUM(i.total_compra) AS total
              FROM ingreso i
              WHERE DATE(i.fecha_hora) >= DATE_SUB(CURDATE(), INTERVAL 9 DAY)
              GROUP BY DATE(i.fecha_hora)
              ORDER BY DATE(i.fecha_hora) ASC";
        return ejecutarConsulta($sql);
    }

    /* ---------- FIX 2: últimos 12 meses (año+mes, orden asc, 12 filas) ---------- */
    public function ventasultimos_12meses(){
        // Opción A: respetando el locale de MySQL (recomendada)
        $sql="SELECT DATE_FORMAT(v.fecha_hora,'%M') AS fecha,
                     SUM(v.total_venta) AS total
              FROM venta v
              WHERE v.fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
              GROUP BY YEAR(v.fecha_hora), MONTH(v.fecha_hora)
              ORDER BY YEAR(v.fecha_hora), MONTH(v.fecha_hora) ASC
              LIMIT 12";
        return ejecutarConsulta($sql); 
    }

    /* ---------- FIX 3: Compras por fecha para gráfico (agrupado por día) ---------- */
    public function comprasfecha_grafico($fecha_inicio, $fecha_fin){
        $sql="SELECT DATE(fecha_hora) as fecha, SUM(total_compra) as total
              FROM ingreso
              WHERE DATE(fecha_hora) >= '$fecha_inicio' 
                AND DATE(fecha_hora) <= '$fecha_fin'
                AND estado = 'Aceptado'
              GROUP BY DATE(fecha_hora)
              ORDER BY DATE(fecha_hora) ASC";
        return ejecutarConsulta($sql);
    }

    /* ---------- FIX 4: Compras por proveedor para gráfico (Top 10) ---------- */
    public function comprasfecha_proveedor($fecha_inicio, $fecha_fin){
        $sql="SELECT p.nombre as proveedor, SUM(i.total_compra) as total
              FROM ingreso i
              INNER JOIN persona p ON i.idproveedor = p.idpersona
              WHERE DATE(i.fecha_hora) >= '$fecha_inicio' 
                AND DATE(i.fecha_hora) <= '$fecha_fin'
                AND i.estado = 'Aceptado'
              GROUP BY p.nombre
              ORDER BY total DESC
              LIMIT 10";
        return ejecutarConsulta($sql);
    }

    /* ---------- FIX 5: Compras por Categoría (Pie) ---------- */
    public function compras_categoria($fecha_inicio, $fecha_fin){
        $sql="SELECT c.nombre as categoria, SUM(i.total_compra) as total
              FROM ingreso i
              INNER JOIN detalle_ingreso di ON i.idingreso = di.idingreso
              INNER JOIN articulo a ON di.idarticulo = a.idarticulo
              INNER JOIN categoria c ON a.idcategoria = c.idcategoria
              WHERE DATE(i.fecha_hora) >= '$fecha_inicio' 
                AND DATE(i.fecha_hora) <= '$fecha_fin'
                AND i.estado = 'Aceptado'
              GROUP BY c.nombre
              ORDER BY total DESC";
        return ejecutarConsulta($sql);
    }

    /* ---------- FIX 6: Top 5 Productos Comprados (Bar Horizontal) ---------- */
    public function compras_productos_top($fecha_inicio, $fecha_fin){
        $sql="SELECT a.nombre as articulo, SUM(di.cantidad) as cantidad, SUM(di.precio_compra * di.cantidad) as total
              FROM ingreso i
              INNER JOIN detalle_ingreso di ON i.idingreso = di.idingreso
              INNER JOIN articulo a ON di.idarticulo = a.idarticulo
              WHERE DATE(i.fecha_hora) >= '$fecha_inicio' 
                AND DATE(i.fecha_hora) <= '$fecha_fin'
                AND i.estado = 'Aceptado'
              GROUP BY a.nombre
              ORDER BY total DESC
              LIMIT 5";
        return ejecutarConsulta($sql);
    }

    /* ---------- FIX 7: Compras por Tipo Comprobante (Doughnut) ---------- */
    public function compras_comprobante($fecha_inicio, $fecha_fin){
        $sql="SELECT tipo_comprobante, COUNT(*) as cantidad, SUM(total_compra) as total
              FROM ingreso
              WHERE DATE(fecha_hora) >= '$fecha_inicio' 
                AND DATE(fecha_hora) <= '$fecha_fin'
                AND estado = 'Aceptado'
              GROUP BY tipo_comprobante";
        return ejecutarConsulta($sql);
    }

    /* ---------- FIX 8: Compras por Usuario (Bar) ---------- */
    public function compras_usuario($fecha_inicio, $fecha_fin){
        $sql="SELECT u.nombre as usuario, SUM(i.total_compra) as total
              FROM ingreso i
              INNER JOIN usuario u ON i.idusuario = u.idusuario
              WHERE DATE(i.fecha_hora) >= '$fecha_inicio' 
                AND DATE(i.fecha_hora) <= '$fecha_fin'
                AND i.estado = 'Aceptado'
              GROUP BY u.nombre
              ORDER BY total DESC";
        return ejecutarConsulta($sql);
    }

    /* ---------- FIX 9: KPIs Avanzados ---------- */
    public function compras_kpis($fecha_inicio, $fecha_fin){
        $sql="SELECT 
                IFNULL(SUM(total_compra),0) as total_compra,
                COUNT(*) as num_transacciones,
                IFNULL(AVG(total_compra),0) as ticket_promedio,
                IFNULL(MAX(total_compra),0) as compra_maxima,
                IFNULL(MIN(total_compra),0) as compra_minima
              FROM ingreso
              WHERE DATE(fecha_hora) >= '$fecha_inicio' 
                AND DATE(fecha_hora) <= '$fecha_fin'
                AND estado = 'Aceptado'";
        return ejecutarConsultaSimpleFila($sql);
    }

    /* ---------- FIX 10: Detalle de KPIs para SweetAlert ---------- */
    public function compras_detalle_kpi($fecha_inicio, $fecha_fin, $tipo){
        $sql = "";
        if ($tipo == 'max') {
            $sql = "SELECT i.fecha_hora, p.nombre as proveedor, i.total_compra, 
                           i.serie_comprobante, i.num_comprobante
                    FROM ingreso i
                    INNER JOIN persona p ON i.idproveedor = p.idpersona
                    WHERE DATE(i.fecha_hora) >= '$fecha_inicio' 
                      AND DATE(i.fecha_hora) <= '$fecha_fin'
                      AND i.estado = 'Aceptado'
                    ORDER BY i.total_compra DESC LIMIT 1";
        } else if ($tipo == 'min') {
            $sql = "SELECT i.fecha_hora, p.nombre as proveedor, i.total_compra, 
                           i.serie_comprobante, i.num_comprobante
                    FROM ingreso i
                    INNER JOIN persona p ON i.idproveedor = p.idpersona
                    WHERE DATE(i.fecha_hora) >= '$fecha_inicio' 
                      AND DATE(i.fecha_hora) <= '$fecha_fin'
                      AND i.estado = 'Aceptado'
                    ORDER BY i.total_compra ASC LIMIT 1";
        }
        return ejecutarConsultaSimpleFila($sql);
    }

    /* ---------- VENTAS DASHBOARD METHODS ---------- */

    /* KPIs de Ventas */
    public function ventas_kpis($fecha_inicio, $fecha_fin, $idcliente = ''){
        $filtro_cliente = "";
        if (!empty($idcliente)) {
            $filtro_cliente = "AND v.idcliente = '$idcliente'";
        }
        
        $sql="SELECT 
                IFNULL(SUM(total_venta),0) as total_venta,
                COUNT(*) as num_transacciones,
                IFNULL(AVG(total_venta),0) as ticket_promedio,
                IFNULL(MAX(total_venta),0) as venta_maxima,
                IFNULL(MIN(total_venta),0) as venta_minima
              FROM venta v
              WHERE DATE(v.fecha_hora) >= '$fecha_inicio' 
                AND DATE(v.fecha_hora) <= '$fecha_fin'
                AND v.estado = 'Aceptado'
                $filtro_cliente";
        return ejecutarConsultaSimpleFila($sql);
    }

    /* Gráfico de Ventas Diarias (Línea/Barra) */
    public function ventas_grafico_dias($fecha_inicio, $fecha_fin, $idcliente = ''){
        $filtro_cliente = "";
        if (!empty($idcliente)) {
            $filtro_cliente = "AND v.idcliente = '$idcliente'";
        }

        $sql="SELECT DATE(v.fecha_hora) as fecha, SUM(v.total_venta) as total
              FROM venta v
              WHERE DATE(v.fecha_hora) >= '$fecha_inicio' 
                AND DATE(v.fecha_hora) <= '$fecha_fin'
                AND v.estado = 'Aceptado'
                $filtro_cliente
              GROUP BY DATE(v.fecha_hora)
              ORDER BY DATE(v.fecha_hora) ASC";
        return ejecutarConsulta($sql);
    }

    /* Top Clientes */
    public function ventas_clientes_top($fecha_inicio, $fecha_fin){
        $sql="SELECT p.nombre as cliente, SUM(v.total_venta) as total
              FROM venta v
              INNER JOIN persona p ON v.idcliente = p.idpersona
              WHERE DATE(v.fecha_hora) >= '$fecha_inicio' 
                AND DATE(v.fecha_hora) <= '$fecha_fin'
                AND v.estado = 'Aceptado'
              GROUP BY p.nombre
              ORDER BY total DESC
              LIMIT 10";
        return ejecutarConsulta($sql);
    }

    /* Top Vendedores */
    public function ventas_vendedores_top($fecha_inicio, $fecha_fin){
        $sql="SELECT u.nombre as vendedor, SUM(v.total_venta) as total
              FROM venta v
              INNER JOIN usuario u ON v.idusuario = u.idusuario
              WHERE DATE(v.fecha_hora) >= '$fecha_inicio' 
                AND DATE(v.fecha_hora) <= '$fecha_fin'
                AND v.estado = 'Aceptado'
              GROUP BY u.nombre
              ORDER BY total DESC
              LIMIT 10";
        return ejecutarConsulta($sql);
    }

    /* Ventas por Categoría */
    public function ventas_categoria($fecha_inicio, $fecha_fin, $idcliente = ''){
        $filtro_cliente = "";
        if (!empty($idcliente)) {
            $filtro_cliente = "AND v.idcliente = '$idcliente'";
        }

        $sql="SELECT c.nombre as categoria, SUM(dv.precio_venta * dv.cantidad) as total
              FROM venta v
              INNER JOIN detalle_venta dv ON v.idventa = dv.idventa
              INNER JOIN articulo a ON dv.idarticulo = a.idarticulo
              INNER JOIN categoria c ON a.idcategoria = c.idcategoria
              WHERE DATE(v.fecha_hora) >= '$fecha_inicio' 
                AND DATE(v.fecha_hora) <= '$fecha_fin'
                AND v.estado = 'Aceptado'
                $filtro_cliente
              GROUP BY c.nombre
              ORDER BY total DESC";
        return ejecutarConsulta($sql);
    }

    /* Top Productos */
    public function ventas_productos_top($fecha_inicio, $fecha_fin, $idcliente = ''){
        $filtro_cliente = "";
        if (!empty($idcliente)) {
            $filtro_cliente = "AND v.idcliente = '$idcliente'";
        }

        $sql="SELECT a.nombre as producto, SUM(dv.precio_venta * dv.cantidad) as total
              FROM venta v
              INNER JOIN detalle_venta dv ON v.idventa = dv.idventa
              INNER JOIN articulo a ON dv.idarticulo = a.idarticulo
              WHERE DATE(v.fecha_hora) >= '$fecha_inicio' 
                AND DATE(v.fecha_hora) <= '$fecha_fin'
                AND v.estado = 'Aceptado'
                $filtro_cliente
              GROUP BY a.nombre
              ORDER BY total DESC
              LIMIT 10";
        return ejecutarConsulta($sql);
    }

    /* Ventas por Comprobante */
    public function ventas_comprobante($fecha_inicio, $fecha_fin, $idcliente = ''){
        $filtro_cliente = "";
        if (!empty($idcliente)) {
            $filtro_cliente = "AND v.idcliente = '$idcliente'";
        }

        $sql="SELECT v.tipo_comprobante, COUNT(*) as cantidad, SUM(v.total_venta) as total
              FROM venta v
              WHERE DATE(v.fecha_hora) >= '$fecha_inicio' 
                AND DATE(v.fecha_hora) <= '$fecha_fin'
                AND v.estado = 'Aceptado'
                $filtro_cliente
              GROUP BY v.tipo_comprobante";
        return ejecutarConsulta($sql);
    }

    /* Detalle KPI Ventas (Max/Min) */
    public function ventas_detalle_kpi($fecha_inicio, $fecha_fin, $tipo, $idcliente = ''){
        $filtro_cliente = "";
        if (!empty($idcliente)) {
            $filtro_cliente = "AND v.idcliente = '$idcliente'";
        }

        $orden = ($tipo == 'min') ? 'ASC' : 'DESC';

        $sql = "SELECT v.fecha_hora, p.nombre as cliente, v.total_venta, 
                       v.serie_comprobante, v.num_comprobante
                FROM venta v
                INNER JOIN persona p ON v.idcliente = p.idpersona
                WHERE DATE(v.fecha_hora) >= '$fecha_inicio' 
                  AND DATE(v.fecha_hora) <= '$fecha_fin'
                  AND v.estado = 'Aceptado'
                  $filtro_cliente
                ORDER BY v.total_venta $orden LIMIT 1";
        return ejecutarConsultaSimpleFila($sql);
    }
}
