<?php
//Incluímos inicialmente la conexión a la base de datos
require "../config/Conexion.php";

class Venta
{
	//Implementamos nuestro constructor
	public function __construct() {}

	//Implementamos un método para insertar registros
	public function insertar($idcliente, $idusuario, $tipo_comprobante, $serie_comprobante, $num_comprobante, $fecha_hora, $impuesto, $total_venta, $idarticulo, $cantidad, $precio_venta, $descuento)
	{

		// Verificar que haya una caja abierta
		$sql_caja = "SELECT idcaja FROM caja WHERE estado = 'Abierta' LIMIT 1";
		$caja_abierta = ejecutarConsultaSimpleFila($sql_caja);
		
		if (!$caja_abierta) {
			// Si no hay caja abierta, no permitir venta
			return -1; // Código especial para "No hay caja abierta"
		}
		
		$idcaja = $caja_abierta['idcaja'];
		
		try {
			$sql = "INSERT INTO venta (idcliente,idusuario,tipo_comprobante,serie_comprobante,num_comprobante,fecha_hora,impuesto,total_venta,estado,idcaja)
			VALUES ('$idcliente','$idusuario','$tipo_comprobante','$serie_comprobante','$num_comprobante','$fecha_hora','$impuesto','$total_venta','Aceptado','$idcaja')";
			
			$idventanew = ejecutarConsulta_retornarID($sql);
			
			if (!$idventanew) {
			    throw new Exception("Error al insertar la venta.");
			}

			$num_elementos = 0;
			$sw = true;

			while ($num_elementos < count($idarticulo)) {
				$sql_detalle = "INSERT INTO detalle_venta(idventa, idarticulo,cantidad,precio_venta,descuento) VALUES ('$idventanew', '$idarticulo[$num_elementos]','$cantidad[$num_elementos]','$precio_venta[$num_elementos]','$descuento[$num_elementos]')";
				ejecutarConsulta($sql_detalle) or $sw = false;
				$num_elementos = $num_elementos + 1;
			}
			
			// Registrar movimiento en caja
			if ($sw) {
				$sql_movimiento = "INSERT INTO movimiento_caja (idcaja, tipo_movimiento, idventa, monto, descripcion, fecha_hora)
				                   VALUES ('$idcaja', 'venta', '$idventanew', '$total_venta', 'Venta $tipo_comprobante $serie_comprobante-$num_comprobante', '$fecha_hora')";
				ejecutarConsulta($sql_movimiento);
			}

			return $idventanew;
		} catch (Exception $e) {
		    // Log error manually if needed, or rethrow
		    error_log("Error Venta::insertar: " . $e->getMessage());
		    return 0;
		}
	}


	//Implementamos un método para anular la venta
	public function anular($idventa)
	{
		$sql = "UPDATE venta SET estado='Anulado' WHERE idventa='$idventa'";
		return ejecutarConsulta($sql);
	}


	//Implementar un método para mostrar los datos de un registro a modificar
	public function mostrar($idventa)
	{
		$sql = "SELECT v.idventa,DATE(v.fecha_hora) as fecha,v.idcliente,p.nombre as cliente,p.direccion,p.tipo_documento,p.num_documento,p.email,p.telefono,u.idusuario,u.nombre as usuario,v.tipo_comprobante,v.serie_comprobante,v.num_comprobante,v.total_venta,v.impuesto,v.estado FROM venta v INNER JOIN persona p ON v.idcliente=p.idpersona INNER JOIN usuario u ON v.idusuario=u.idusuario WHERE v.idventa='$idventa'";
		return ejecutarConsultaSimpleFila($sql);
	}

	public function actualizarNumero($idventa, $num_comprobante)
	{
		$sql = "UPDATE venta SET num_comprobante='$num_comprobante' WHERE idventa='$idventa'";
		return ejecutarConsulta($sql);
	}

	// Actualiza enlaces de documentos (Greenter local + NubeFact externo)
	public function actualizarEnlacesNubefact($idventa, $pdf_nubefact = '', $xml_local = '', $cdr_local = '', $xml_nubefact = '', $cdr_nubefact = '')
	{
		$updates = [];
		if ($pdf_nubefact !== '') $updates[] = "pdf_nubefact='" . addslashes($pdf_nubefact) . "'";
		if ($xml_nubefact !== '') $updates[] = "xml_nubefact='" . addslashes($xml_nubefact) . "'";
		if ($cdr_nubefact !== '') $updates[] = "cdr_nubefact='" . addslashes($cdr_nubefact) . "'";
		if ($xml_local !== '') $updates[] = "xml_local='" . addslashes($xml_local) . "'";
		if ($cdr_local !== '') $updates[] = "cdr_local='" . addslashes($cdr_local) . "'";

		if (empty($updates)) return false;

		$sql = "UPDATE venta SET " . implode(', ', $updates) . " WHERE idventa='$idventa'";
		return ejecutarConsulta($sql);
	}


	public function ventadetalle($idventa)
	{
		$sql = "SELECT a.nombre as articulo,a.codigo,d.cantidad,d.precio_venta,d.descuento,(d.cantidad*d.precio_venta-d.descuento) as subtotal FROM detalle_venta d INNER JOIN articulo a ON d.idarticulo=a.idarticulo WHERE d.idventa='$idventa'";
		return ejecutarConsulta($sql);
	}

	//Implementar un método para listar los registros
	public function listar($idarticulo = "")
	{
		$sql = "SELECT v.idventa,DATE(v.fecha_hora) as fecha,v.idcliente,p.nombre as cliente,u.idusuario,u.nombre as usuario,v.tipo_comprobante,v.serie_comprobante,v.num_comprobante,v.total_venta,v.impuesto,v.estado,v.pdf_nubefact,v.xml_nubefact,v.cdr_nubefact,v.xml_local,v.cdr_local FROM venta v INNER JOIN persona p ON v.idcliente=p.idpersona INNER JOIN usuario u ON v.idusuario=u.idusuario";

		if (!empty($idarticulo)) {
			$sql .= " INNER JOIN detalle_venta dv ON v.idventa = dv.idventa WHERE dv.idarticulo = '$idarticulo'";
		}

		$sql .= " ORDER by v.idventa desc";
		return ejecutarConsulta($sql);
	}

	public function listarDetalle($idventa)
	{
		$sql = "SELECT dv.idventa,dv.idarticulo,a.nombre,dv.cantidad,dv.precio_venta,dv.descuento,(dv.cantidad*dv.precio_venta-dv.descuento) as subtotal FROM detalle_venta dv inner join articulo a on dv.idarticulo=a.idarticulo where dv.idventa='$idventa'";
		return ejecutarConsulta($sql);
	}
}
