<?php
// modelos/Ingreso.php
require_once "../config/Conexion.php";

class Ingreso
{
    public function __construct(){}

    /**
     * Inserta un nuevo ingreso.
     *
     * $tipo_ingreso:
     *   - 'compra'       : compra normal de proveedor (reposición)
     *   - 'alta_inicial' : primera carga de stock de un producto
     *   - 'ajuste'
     *   - 'devolucion'
     */
    public function insertar(
        $idproveedor,
        $idusuario,
        $tipo_comprobante,
        $serie_comprobante,
        $num_comprobante,
        $fecha_hora,        // 'Y-m-d H:i:s'
        $subtotal_neto,     // total sin IGV
        $impuesto_total,    // monto IGV
        $impuesto_porcentaje, // % IGV (ej. 18)
        $total_compra,      // neto + IGV
        $tipo_ingreso,      // compra | alta_inicial | ...
        $idarticulo,        // array
        $cantidad,          // array
        $precio_compra      // array
    ){
        // ----------------- Validaciones básicas -----------------
        if (empty($idusuario)) return false;

        if (!is_array($idarticulo) || !is_array($cantidad) || !is_array($precio_compra)) {
            return false;
        }

        $n = count($idarticulo);
        if ($n === 0 || $n !== count($cantidad) || $n !== count($precio_compra)) {
            return false;
        }

        // Si es compra normal, exijo proveedor
        if ($tipo_ingreso === 'compra' && empty($idproveedor)) {
            return false;
        }

        $subtotal_neto      = (float)$subtotal_neto;
        $impuesto_total     = (float)$impuesto_total;
        $total_compra       = (float)$total_compra;
        $impuesto_porcentaje= (float)$impuesto_porcentaje;

        if ($subtotal_neto <= 0 || $total_compra <= 0) {
            return false;
        }

        // ----------------- Transacción -----------------
        ejecutarConsulta("START TRANSACTION");

        // Cabecera de ingreso
        $sql = "INSERT INTO ingreso
                (idproveedor,
                 idusuario,
                 tipo_comprobante,
                 serie_comprobante,
                 num_comprobante,
                 fecha_hora,
                 subtotal,
                 impuesto_total,
                 impuesto,
                 total_compra,
                 tipo_ingreso,
                 estado)
                VALUES
                (
                 '$idproveedor',
                 '$idusuario',
                 '$tipo_comprobante',
                 '$serie_comprobante',
                 '$num_comprobante',
                 '$fecha_hora',
                 '$subtotal_neto',
                 '$impuesto_total',
                 '$impuesto_porcentaje',
                 '$total_compra',
                 '$tipo_ingreso',
                 'Aceptado'
                )";

        $idingresonew = ejecutarConsulta_retornarID($sql);
        if (!$idingresonew) {
            ejecutarConsulta("ROLLBACK");
            return false;
        }

        // ----------------- Detalle de ingreso -----------------
        $sw = true;

        for ($i = 0; $i < $n; $i++) {
            $ida  = (int)$idarticulo[$i];
            $cant = (float)$cantidad[$i];
            $pc   = (float)$precio_compra[$i];

            if ($ida <= 0 || $cant <= 0 || $pc < 0) {
                $sw = false;
                break;
            }

            $subtotal = $cant * $pc;

            // Obtener precio_venta actual del artículo para no usar subconsulta en el INSERT
            // y evitar conflicto con triggers (Error 1442)
            $sql_precio = "SELECT precio_venta FROM articulo WHERE idarticulo = '$ida'";
            $res_precio = ejecutarConsultaSimpleFila($sql_precio);
            $precio_venta_actual = $res_precio ? $res_precio['precio_venta'] : 0;

            $sql_detalle = "
                INSERT INTO detalle_ingreso
                    (idingreso, idarticulo, cantidad, precio_compra, subtotal, precio_venta)
                VALUES
                    (
                      '$idingresonew',
                      '$ida',
                      '$cant',
                      '$pc',
                      '$subtotal',
                      '$precio_venta_actual'
                    )";

            if (!ejecutarConsulta($sql_detalle)) {
                $sw = false;
                break;
            }

            // ⚠️ El stock se actualiza mediante TRIGGER en la base de datos.
            // No hacemos update explícito para evitar conflicto o duplicidad.

            // ✅ Actualizar precio de compra en la tabla artículo
            $sql_update_precio = "UPDATE articulo SET precio_compra = '$pc' WHERE idarticulo = '$ida'";
            if (!ejecutarConsulta($sql_update_precio)) {
                $sw = false;
                break;
            }
        }

        if ($sw) {
            ejecutarConsulta("COMMIT");
            return true;
        } else {
            ejecutarConsulta("ROLLBACK");
            return false;
        }
    }

    /**
     * Anula un ingreso (el trigger se encarga de revertir stock).
     */
    public function anular($idingreso){
        $sql = "UPDATE ingreso SET estado='Anulado' WHERE idingreso='$idingreso'";
        return ejecutarConsulta($sql);
    }

    /**
     * Devuelve la cabecera de un ingreso para mostrar en el formulario.
     */
    public function mostrar($idingreso){
        $sql = "SELECT 
                    i.idingreso,
                    DATE(i.fecha_hora) AS fecha,
                    i.idproveedor,
                    p.nombre AS proveedor,
                    u.idusuario,
                    u.nombre AS usuario,
                    i.tipo_comprobante,
                    i.serie_comprobante,
                    i.num_comprobante,
                    i.subtotal,
                    i.impuesto_total,
                    i.impuesto AS impuesto_porcentaje,
                    i.total_compra,
                    i.tipo_ingreso,
                    i.estado
                FROM ingreso i
                INNER JOIN persona p ON i.idproveedor = p.idpersona
                INNER JOIN usuario u ON i.idusuario   = u.idusuario
                WHERE i.idingreso = '$idingreso'";
        return ejecutarConsultaSimpleFila($sql);
    }

    /**
     * Detalle del ingreso (para ver y para recalcular totales en la vista).
     */
    public function listarDetalle($idingreso){
        $sql = "SELECT 
                    di.idingreso,
                    di.idarticulo,
                    a.nombre,
                    di.cantidad,
                    di.precio_compra,
                    (di.cantidad * di.precio_compra) AS subtotal
                FROM detalle_ingreso di
                INNER JOIN articulo a ON di.idarticulo = a.idarticulo
                WHERE di.idingreso = '$idingreso'";
        return ejecutarConsulta($sql);
    }

    /**
     * Listado general (DataTables) con filtros de fecha y estado opcionales.
     */
    public function listar($desde = '', $hasta = '', $estado = 'todos'){
        $where = "1=1";
        
        if ($desde !== '') {
            $where .= " AND DATE(i.fecha_hora) >= '$desde'";
        }
        if ($hasta !== '') {
            $where .= " AND DATE(i.fecha_hora) <= '$hasta'";
        }

        if ($estado === 'Aceptado') {
            $where .= " AND i.estado = 'Aceptado'";
        } elseif ($estado === 'Anulado') {
            $where .= " AND i.estado = 'Anulado'";
        }

        $sql = "SELECT 
                    i.idingreso,
                    DATE(i.fecha_hora) AS fecha,
                    p.nombre AS proveedor,
                    u.nombre AS usuario,
                    i.tipo_comprobante,
                    i.serie_comprobante,
                    i.num_comprobante,
                    i.total_compra,
                    i.estado,
                    i.tipo_ingreso
                FROM ingreso i
                INNER JOIN persona p ON i.idproveedor = p.idpersona
                INNER JOIN usuario u ON i.idusuario   = u.idusuario
                WHERE $where
                ORDER BY i.idingreso DESC";
        return ejecutarConsulta($sql);
    }

    /**
     * Cabecera para reportes (PDF de ingreso).
     */
    public function ingresocabecera($idingreso){
        $sql = "SELECT 
                    i.idingreso,
                    i.idproveedor,
                    p.nombre AS proveedor,
                    p.direccion,
                    p.tipo_documento,
                    p.num_documento,
                    p.email,
                    p.telefono,
                    i.idusuario,
                    u.nombre AS usuario,
                    i.tipo_comprobante,
                    i.serie_comprobante,
                    i.num_comprobante,
                    DATE(i.fecha_hora) AS fecha,
                    i.subtotal,
                    i.impuesto_total,
                    i.impuesto AS impuesto_porcentaje,
                    i.total_compra,
                    i.tipo_ingreso
                FROM ingreso i
                INNER JOIN persona p ON i.idproveedor = p.idpersona
                INNER JOIN usuario u ON i.idusuario   = u.idusuario
                WHERE i.idingreso = '$idingreso'";
        return ejecutarConsulta($sql);
    }

    /**
     * Detalle para reportes de ingreso.
     */
    public function ingresodetalle($idingreso){
        $sql = "SELECT 
                    a.nombre AS articulo,
                    a.codigo,
                    d.cantidad,
                    d.precio_compra,
                    (d.cantidad * d.precio_compra) AS subtotal
                FROM detalle_ingreso d
                INNER JOIN articulo a ON d.idarticulo = a.idarticulo
                WHERE d.idingreso = '$idingreso'";
        return ejecutarConsulta($sql);
    }
    /**
     * Obtiene la última serie y número según el tipo de comprobante.
     */
    public function getLastSerieNumero($tipo_comprobante) {
        $sql = "SELECT serie_comprobante, num_comprobante 
                FROM ingreso 
                WHERE tipo_comprobante = '$tipo_comprobante' 
                ORDER BY idingreso DESC LIMIT 1";
        return ejecutarConsultaSimpleFila($sql);
    }
}
