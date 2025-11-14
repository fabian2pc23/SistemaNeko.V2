<?php 
// modelos/Ingreso.php
require "../config/Conexion.php";

class Ingreso
{
  public function __construct(){}

  public function insertar(
      $idproveedor, $idusuario, $tipo_comprobante, $serie_comprobante, $num_comprobante,
      $fecha_hora, $subtotal_neto, $impuesto_total, $total_compra,
      $idarticulo, $cantidad, $precio_compra
  ){
    if (empty($idproveedor) || empty($idusuario)) return false;
    if (!is_array($idarticulo) || !is_array($cantidad) || !is_array($precio_compra)) return false;
    if (count($idarticulo) !== count($cantidad) || count($idarticulo) !== count($precio_compra)) return false;

    ejecutarConsulta("START TRANSACTION");

    $sql = "INSERT INTO ingreso
            (idproveedor,idusuario,tipo_comprobante,serie_comprobante,num_comprobante,fecha_hora,subtotal,impuesto_total,total_compra,estado)
            VALUES
            ('$idproveedor','$idusuario','$tipo_comprobante','$serie_comprobante','$num_comprobante','$fecha_hora','$subtotal_neto','$impuesto_total','$total_compra','Aceptado')";
    
    $idingresonew = ejecutarConsulta_retornarID($sql);
    if (!$idingresonew) {
      ejecutarConsulta("ROLLBACK");
      return false;
    }

    $sw = true;
    for ($i=0; $i<count($idarticulo); $i++) {
      $ida = (int)$idarticulo[$i];
      $cant = (float)$cantidad[$i];
      $pc = (float)$precio_compra[$i];

      if ($ida <= 0 || $cant <= 0 || $pc < 0) { $sw = false; break; }

      $sql_detalle = "INSERT INTO detalle_ingreso
                      (idingreso, idarticulo, cantidad, precio_compra, subtotal)
                      VALUES
                      ('$idingresonew', '$ida', '$cant', '$pc', '".($cant*$pc)."')";
      if (!ejecutarConsulta($sql_detalle)) { $sw = false; break; }

      ejecutarConsulta("UPDATE articulo SET stock = stock + $cant WHERE idarticulo = $ida");
    }

    if ($sw) {
      ejecutarConsulta("COMMIT");
      return true;
    } else {
      ejecutarConsulta("ROLLBACK");
      return false;
    }
  }

  public function anular($idingreso){
    $sql="UPDATE ingreso SET estado='Anulado' WHERE idingreso='$idingreso'";
    return ejecutarConsulta($sql);
  }

  public function mostrar($idingreso){
    $sql="SELECT i.idingreso,
                 DATE(i.fecha_hora) as fecha,
                 i.idproveedor, p.nombre as proveedor,
                 u.idusuario, u.nombre as usuario,
                 i.tipo_comprobante,i.serie_comprobante,i.num_comprobante,
                 i.total_compra,i.impuesto_total,i.estado
          FROM ingreso i
          INNER JOIN persona p ON i.idproveedor=p.idpersona
          INNER JOIN usuario u ON i.idusuario=u.idusuario
          WHERE i.idingreso='$idingreso'";
    return ejecutarConsultaSimpleFila($sql);
  }

  public function listarDetalle($idingreso){
    $sql="SELECT di.idingreso,di.idarticulo,a.nombre,
                 di.cantidad,di.precio_compra,(di.cantidad*di.precio_compra) as subtotal
          FROM detalle_ingreso di
          INNER JOIN articulo a ON di.idarticulo=a.idarticulo
          WHERE di.idingreso='$idingreso'";
    return ejecutarConsulta($sql);
  }

  public function listar($desde = '', $hasta = ''){
    $where = "1=1";
    if ($desde !== '') $where .= " AND DATE(i.fecha_hora) >= '$desde'";
    if ($hasta !== '') $where .= " AND DATE(i.fecha_hora) <= '$hasta'";

    $sql = "SELECT i.idingreso,
                   DATE(i.fecha_hora) AS fecha,
                   p.nombre AS proveedor,
                   u.nombre AS usuario,
                   i.tipo_comprobante,i.serie_comprobante,i.num_comprobante,
                   i.total_compra,i.estado
            FROM ingreso i
            INNER JOIN persona p ON i.idproveedor = p.idpersona
            INNER JOIN usuario u ON i.idusuario = u.idusuario
            WHERE $where
            ORDER BY i.idingreso DESC";
    return ejecutarConsulta($sql);
  }

  public function ingresocabecera($idingreso){
    $sql="SELECT i.idingreso,i.idproveedor,p.nombre as proveedor,p.direccion,p.tipo_documento,
                 p.num_documento,p.email,p.telefono,i.idusuario,u.nombre as usuario,
                 i.tipo_comprobante,i.serie_comprobante,i.num_comprobante,DATE(i.fecha_hora) as fecha,
                 i.impuesto_total,i.total_compra
          FROM ingreso i
          INNER JOIN persona p ON i.idproveedor=p.idpersona
          INNER JOIN usuario u ON i.iduario=u.idusuario
          WHERE i.idingreso='$idingreso'";
    return ejecutarConsulta($sql);
  }

  public function ingresodetalle($idingreso){
    $sql="SELECT a.nombre as articulo,a.codigo,d.cantidad,d.precio_compra,
                 (d.cantidad*d.precio_compra) as subtotal
          FROM detalle_ingreso d
          INNER JOIN articulo a ON d.idarticulo=a.idarticulo
          WHERE d.idingreso='$idingreso'";
    return ejecutarConsulta($sql);
  }
}
?>