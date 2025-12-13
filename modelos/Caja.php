<?php
// modelos/Caja.php
require_once "../config/Conexion.php";

class Caja
{
    public function __construct() {}

    /**
     * Abre una nueva caja
     */
    public function abrirCaja($idusuario, $monto_inicial, $observaciones = '')
    {
        // Validar idusuario
        if (empty($idusuario)) {
            return ['success' => false, 'message' => 'Error: Usuario no identificado. Inicie sesión nuevamente.'];
        }

        // Verificar que no haya una caja abierta
        $sql_verificar = "SELECT idcaja FROM caja WHERE estado = 'Abierta' LIMIT 1";
        $resultado = ejecutarConsultaSimpleFila($sql_verificar);
        
        if ($resultado) {
            return ['success' => false, 'message' => 'Ya existe una caja abierta. Debe cerrarla antes de abrir una nueva.'];
        }

        $fecha_apertura = date('Y-m-d H:i:s');
        
        $sql = "INSERT INTO caja (idusuario, fecha_apertura, monto_inicial, estado, observaciones)
                VALUES ('$idusuario', '$fecha_apertura', '$monto_inicial', 'Abierta', '$observaciones')";
        
        try {
            $idcaja = ejecutarConsulta_retornarID($sql);
            if ($idcaja) {
                return ['success' => true, 'idcaja' => $idcaja, 'message' => 'Caja abierta correctamente'];
            } else {
                return ['success' => false, 'message' => 'Error al abrir la caja (No ID returned)'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error SQL: ' . $e->getMessage()];
        }
    }

    /**
     * Cierra la caja actual
     */
    public function cerrarCaja($idcaja, $monto_final, $observaciones = '')
    {
        $fecha_cierre = date('Y-m-d H:i:s');
        
        $sql = "UPDATE caja 
                SET fecha_cierre = '$fecha_cierre',
                    monto_final = '$monto_final',
                    estado = 'Cerrada',
                    observaciones = CONCAT(IFNULL(observaciones, ''), '\n', '$observaciones')
                WHERE idcaja = '$idcaja' AND estado = 'Abierta'";
        
        $resultado = ejecutarConsulta($sql);
        
        if ($resultado) {
            return ['success' => true, 'message' => 'Caja cerrada correctamente'];
        } else {
            return ['success' => false, 'message' => 'Error al cerrar la caja o la caja ya está cerrada'];
        }
    }

    /**
     * Obtiene la caja actualmente abierta
     */
    public function obtenerCajaAbierta()
    {
        $sql = "SELECT * FROM v_caja_actual LIMIT 1";
        return ejecutarConsultaSimpleFila($sql);
    }

    /**
     * Verifica si hay una caja abierta
     */
    public function verificarCajaAbierta()
    {
        $sql = "SELECT idcaja FROM caja WHERE estado = 'Abierta' LIMIT 1";
        $resultado = ejecutarConsultaSimpleFila($sql);
        return $resultado ? $resultado['idcaja'] : false;
    }

    /**
     * Obtiene el resumen completo de una caja
     */
    public function obtenerResumenCaja($idcaja)
    {
        // Información de la caja
        $sql_caja = "SELECT c.*, u.nombre AS usuario, u.cargo 
                     FROM caja c 
                     INNER JOIN usuario u ON c.idusuario = u.idusuario 
                     WHERE c.idcaja = '$idcaja'";
        $caja = ejecutarConsultaSimpleFila($sql_caja);

        if (!$caja) {
            return false;
        }

        // Ventas de la caja
        $sql_ventas = "SELECT v.idventa, v.tipo_comprobante, v.serie_comprobante, 
                              v.num_comprobante, v.fecha_hora, v.total_venta,
                              p.nombre AS cliente
                       FROM venta v
                       INNER JOIN persona p ON v.idcliente = p.idpersona
                       WHERE v.idcaja = '$idcaja' AND v.estado = 'Aceptado'
                       ORDER BY v.fecha_hora";
        $ventas = ejecutarConsulta($sql_ventas);

        // Compras de la caja
        $sql_compras = "SELECT i.idingreso, i.tipo_comprobante, i.serie_comprobante,
                               i.num_comprobante, i.fecha_hora, i.total_compra,
                               p.nombre AS proveedor
                        FROM ingreso i
                        INNER JOIN persona p ON i.idproveedor = p.idpersona
                        WHERE i.idcaja = '$idcaja' AND i.estado = 'Aceptado'
                        ORDER BY i.fecha_hora";
        $compras = ejecutarConsulta($sql_compras);

        // Movimientos manuales
        $sql_movimientos = "SELECT * FROM movimiento_caja 
                           WHERE idcaja = '$idcaja' 
                           AND tipo_movimiento IN ('ingreso_manual', 'egreso_manual')
                           ORDER BY fecha_hora";
        $movimientos = ejecutarConsulta($sql_movimientos);

        return [
            'caja' => $caja,
            'ventas' => $ventas,
            'compras' => $compras,
            'movimientos' => $movimientos
        ];
    }

    /**
     * Lista el histórico de cajas
     */
    public function listarCajas($desde = '', $hasta = '', $estado = 'todos')
    {
        $where = "1=1";
        
        if ($desde !== '') {
            $where .= " AND DATE(c.fecha_apertura) >= '$desde'";
        }
        if ($hasta !== '') {
            $where .= " AND DATE(c.fecha_apertura) <= '$hasta'";
        }
        if ($estado === 'Abierta' || $estado === 'Cerrada') {
            $where .= " AND c.estado = '$estado'";
        }

        $sql = "SELECT * FROM v_historial_cajas WHERE $where ORDER BY fecha DESC, hora_apertura DESC";
        return ejecutarConsulta($sql);
    }

    /**
     * Registra un movimiento en la caja
     */
    public function registrarMovimiento($idcaja, $tipo_movimiento, $monto, $descripcion = '', $idventa = null, $idingreso = null)
    {
        $fecha_hora = date('Y-m-d H:i:s');
        
        $idventa_val = $idventa ? "'$idventa'" : "NULL";
        $idingreso_val = $idingreso ? "'$idingreso'" : "NULL";
        
        $sql = "INSERT INTO movimiento_caja (idcaja, tipo_movimiento, idventa, idingreso, monto, descripcion, fecha_hora)
                VALUES ('$idcaja', '$tipo_movimiento', $idventa_val, $idingreso_val, '$monto', '$descripcion', '$fecha_hora')";
        
        return ejecutarConsulta($sql);
    }

    /**
     * Obtiene los movimientos de una caja
     */
    public function obtenerMovimientos($idcaja)
    {
        $sql = "SELECT m.*, 
                       CASE 
                           WHEN m.tipo_movimiento = 'venta' THEN CONCAT(v.tipo_comprobante, ' ', v.serie_comprobante, '-', v.num_comprobante)
                           WHEN m.tipo_movimiento = 'compra' THEN CONCAT(i.tipo_comprobante, ' ', i.serie_comprobante, '-', i.num_comprobante)
                           ELSE m.descripcion
                       END AS detalle
                FROM movimiento_caja m
                LEFT JOIN venta v ON m.idventa = v.idventa
                LEFT JOIN ingreso i ON m.idingreso = i.idingreso
                WHERE m.idcaja = '$idcaja'
                ORDER BY m.fecha_hora DESC";
        
        return ejecutarConsulta($sql);
    }

    /**
     * Obtiene estadísticas de la caja actual
     */
    public function obtenerEstadisticasCajaActual()
    {
        $caja = $this->obtenerCajaAbierta();
        
        if (!$caja) {
            return false;
        }

        return [
            'idcaja' => $caja['idcaja'],
            'fecha_apertura' => $caja['fecha_apertura'],
            'monto_inicial' => $caja['monto_inicial'],
            'total_ventas' => $caja['total_ventas'],
            'total_compras' => $caja['total_compras'],
            'num_ventas' => $caja['num_ventas'],
            'num_compras' => $caja['num_compras'],
            'saldo_calculado' => $caja['saldo_calculado'],
            'usuario' => $caja['usuario']
        ];
    }

    /**
     * Registra un ingreso o egreso manual
     */
    public function registrarMovimientoManual($idcaja, $tipo, $monto, $descripcion)
    {
        if ($tipo !== 'ingreso_manual' && $tipo !== 'egreso_manual') {
            return false;
        }

        return $this->registrarMovimiento($idcaja, $tipo, $monto, $descripcion);
    }
}
