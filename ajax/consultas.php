<?php
//Incluímos inicialmente la conexión a la base de datos
require "../config/Conexion.php";

class Consultas
{
    public function __construct(){}

    /* =======================================================
       ==========   KPI NUEVOS PARA TU DASHBOARD   ============
       ======================================================= */

    // Total de ventas acumuladas
    public function total_ventas_acumuladas() {
        $sql = "SELECT IFNULL(SUM(total_venta),0) AS total FROM venta WHERE estado='Aceptado'";
        return ejecutarConsultaSimpleFila($sql);
    }

    // Total compras acumuladas
    public function total_compras_acumuladas() {
        $sql = "SELECT IFNULL(SUM(total_compra),0) AS total FROM ingreso WHERE estado='Aceptado'";
        return ejecutarConsultaSimpleFila($sql);
    }

    // Total ventas del mes actual
    public function total_ventas_mes() {
        $sql = "SELECT IFNULL(SUM(total_venta),0) AS total 
                FROM venta 
                WHERE estado='Aceptado'
                AND MONTH(fecha_hora)=MONTH(CURDATE())
                AND YEAR(fecha_hora)=YEAR(CURDATE())";
        return ejecutarConsultaSimpleFila($sql);
    }

    // Total compras del mes actual
    public function total_compras_mes() {
        $sql = "SELECT IFNULL(SUM(total_compra),0) AS total 
                FROM ingreso 
                WHERE estado='Aceptado'
                AND MONTH(fecha_hora)=MONTH(CURDATE())
                AND YEAR(fecha_hora)=YEAR(CURDATE())";
        return ejecutarConsultaSimpleFila($sql);
    }

    // Total de ventas de hoy
    public function totalventahoy(){
        $sql="SELECT IFNULL(SUM(total_venta),0) AS total_venta 
              FROM venta 
              WHERE DATE(fecha_hora)=CURDATE() AND estado='Aceptado'";
        return ejecutarConsultaSimpleFila($sql);
    }

    // Total de compras de hoy
    public function totalcomprahoy(){
        $sql="SELECT IFNULL(SUM(total_compra),0) AS total_compra 
              FROM ingreso 
              WHERE DATE(fecha_hora)=CURDATE() AND estado='Aceptado'";
        return ejecutarConsultaSimpleFila($sql);
    }

    /* =======================================================
       ============  MÉTODOS YA EXISTENTES  ==================
       ======================================================= */

    public function comprasfecha($fecha_inicio,$fecha_fin){
        $sql="SELECT DATE(i.fecha_hora) AS fecha,u.nombre AS usuario,
        p.nombre AS proveedor,i.tipo_comprobante,i.serie_comprobante,
        i.num_comprobante,i.total_compra,i.impuesto,i.estado 
        FROM ingreso i INNER JOIN persona p ON i.idproveedor=p.idpersona 
        INNER JOIN usuario u ON i.idusuario=u.idusuario 
        WHERE DATE(i.fecha_hora)>='$fecha_inicio' AND DATE(i.fecha_hora)<='$fecha_fin'";
        return ejecutarConsulta($sql);
    }

    public function ventasfechacliente($fecha_inicio,$fecha_fin,$idcliente){
        $sql="SELECT DATE(v.fecha_hora) AS fecha,u.nombre AS usuario,
        p.nombre AS cliente,v.tipo_comprobante,v.serie_comprobante,
        v.num_comprobante,v.total_venta,v.impuesto,v.estado 
        FROM venta v INNER JOIN persona p ON v.idcliente=p.idpersona 
        INNER JOIN usuario u ON v.idusuario=u.idusuario 
        WHERE DATE(v.fecha_hora)>='$fecha_inicio' 
        AND DATE(v.fecha_hora)<='$fecha_fin' 
        AND v.idcliente='$idcliente'";
        return ejecutarConsulta($sql);
    }

    public function comprasultimos_10dias(){
        $sql="SELECT DATE(fecha_hora) AS fecha, SUM(total_compra) AS total 
              FROM ingreso 
              WHERE fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 10 DAY) 
              AND estado='Aceptado'
              GROUP BY DATE(fecha_hora)
              ORDER BY fecha DESC";
        return ejecutarConsulta($sql);
    }

    public function ventasultimos_12meses(){
        $sql="SELECT DATE_FORMAT(fecha_hora,'%M') AS fecha, SUM(total_venta) AS total 
              FROM venta 
              WHERE fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
              AND estado='Aceptado'
              GROUP BY MONTH(fecha_hora)
              ORDER BY fecha_hora ASC";
        return ejecutarConsulta($sql);
    }
}

?>
