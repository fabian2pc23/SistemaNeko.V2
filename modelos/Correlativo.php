<?php 
// modelos/Correlativo.php
//
// Usa la tabla comprobante_serie:
//
//  idcomprobante INT PK
//  tipo          ENUM('Boleta','Factura','Ticket')
//  serie         VARCHAR(4)
//  correlativo   INT       (último número usado)
//  impuesto      DECIMAL
//  estado        TINYINT(1) (1 = activo)

require "../config/Conexion.php";

class Correlativo
{
    public function __construct()
    {
    }

    /**
     * Obtiene la configuración de correlativo para un tipo de comprobante
     * desde la tabla comprobante_serie.
     *
     * Retorna un array asociativo con:
     *  - idcorrelativo
     *  - tipo_comprobante
     *  - serie_actual
     *  - numero_actual        (correlativo actual en BD)
     *  - numero_siguiente     (formateado con ceros)
     *  - longitud_numero      (fijo 8, por ejemplo)
     *  - impuesto
     */
    public function obtenerActual($tipo_comprobante)
    {
        // Por seguridad recortamos longitud
        $tipo = substr($tipo_comprobante, 0, 20);

        $sql = "SELECT 
                    idcomprobante AS idcorrelativo,
                    tipo         AS tipo_comprobante,
                    serie        AS serie_actual,
                    correlativo  AS numero_actual,
                    impuesto,
                    estado       AS condicion
                FROM comprobante_serie
                WHERE tipo = '$tipo'
                  AND estado = 1
                LIMIT 1";

        $row = ejecutarConsultaSimpleFila($sql);

        if (!$row || !isset($row['idcorrelativo'])) {
            // No hay configuración para ese tipo
            return null;
        }

        // Longitud fija de número correlativo (ajusta si quieres 7, 10, etc.)
        $longitud = 8;

        $numeroActual    = (int)$row['numero_actual'];   // correlativo último usado
        $numeroSiguiente = $numeroActual + 1;

        // Formatear con ceros a la izquierda
        $numeroSiguienteStr = str_pad(
            (string)$numeroSiguiente,
            $longitud,
            '0',
            STR_PAD_LEFT
        );

        $row['longitud_numero']  = $longitud;
        $row['numero_siguiente'] = $numeroSiguienteStr;

        return $row;
    }

    /**
     * Incrementa en 1 el correlativo (campo correlativo) de comprobante_serie
     * para el tipo de comprobante indicado.
     *
     * Se llama SOLO cuando la venta se registró correctamente.
     */
    public function incrementar($tipo_comprobante)
    {
        $tipo = substr($tipo_comprobante, 0, 20);

        $sql = "UPDATE comprobante_serie
                SET correlativo = correlativo + 1
                WHERE tipo = '$tipo'
                  AND estado = 1
                LIMIT 1";

        return ejecutarConsulta($sql);
    }

    /* ================== OPCIONAL: mantenimiento ================== */

    // Listar configuraciones (por si luego haces un mantenimiento gráfico)
    public function listar()
    {
        $sql = "SELECT
                    idcomprobante AS idcorrelativo,
                    tipo         AS tipo_comprobante,
                    serie        AS serie_actual,
                    correlativo  AS numero_actual,
                    impuesto,
                    estado       AS condicion
                FROM comprobante_serie
                ORDER BY tipo ASC";
        return ejecutarConsulta($sql);
    }

    // Editar una serie en particular
    public function editar($idcomprobante, $serie, $correlativo, $impuesto, $estado = 1)
    {
        $id   = (int)$idcomprobante;
        $ser  = substr($serie, 0, 4);
        $corr = (int)$correlativo;
        $imp  = (float)$impuesto;
        $est  = (int)$estado;

        $sql = "UPDATE comprobante_serie
                SET serie      = '$ser',
                    correlativo= $corr,
                    impuesto   = $imp,
                    estado     = $est
                WHERE idcomprobante = $id
                LIMIT 1";

        return ejecutarConsulta($sql);
    }
}
