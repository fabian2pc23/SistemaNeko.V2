<?php
// ajax/caja.php
require_once "../modelos/Caja.php";

$caja = new Caja();

$idcaja = isset($_POST["idcaja"]) ? limpiarcadena($_POST["idcaja"]) : "";
$idusuario = isset($_POST["idusuario"]) ? limpiarcadena($_POST["idusuario"]) : "";
$monto_inicial = isset($_POST["monto_inicial"]) ? limpiarcadena($_POST["monto_inicial"]) : "";
$monto_final = isset($_POST["monto_final"]) ? limpiarcadena($_POST["monto_final"]) : "";
$observaciones = isset($_POST["observaciones"]) ? limpiarcadena($_POST["observaciones"]) : "";
$tipo_movimiento = isset($_POST["tipo_movimiento"]) ? limpiarcadena($_POST["tipo_movimiento"]) : "";
$monto = isset($_POST["monto"]) ? limpiarcadena($_POST["monto"]) : "";
$descripcion = isset($_POST["descripcion"]) ? limpiarcadena($_POST["descripcion"]) : "";

switch ($_GET["op"]) {
    case 'verificarCajaAbierta':
        $idcaja_abierta = $caja->verificarCajaAbierta();
        if ($idcaja_abierta) {
            echo json_encode(['success' => true, 'idcaja' => $idcaja_abierta]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No hay caja abierta']);
        }
        break;

    case 'abrirCaja':
        $resultado = $caja->abrirCaja($idusuario, $monto_inicial, $observaciones);
        echo json_encode($resultado);
        break;

    case 'cerrarCaja':
        $resultado = $caja->cerrarCaja($idcaja, $monto_final, $observaciones);
        echo json_encode($resultado);
        break;

    case 'obtenerCajaAbierta':
        $resultado = $caja->obtenerCajaAbierta();
        if ($resultado) {
            echo json_encode(['success' => true, 'data' => $resultado]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No hay caja abierta']);
        }
        break;

    case 'obtenerResumen':
        $resultado = $caja->obtenerResumenCaja($idcaja);
        if ($resultado) {
            // Convertir resultados de consulta a arrays
            $ventas_array = [];
            while ($row = $resultado['ventas']->fetch_assoc()) {
                $ventas_array[] = $row;
            }
            
            $compras_array = [];
            while ($row = $resultado['compras']->fetch_assoc()) {
                $compras_array[] = $row;
            }
            
            $movimientos_array = [];
            if ($resultado['movimientos']) {
                while ($row = $resultado['movimientos']->fetch_assoc()) {
                    $movimientos_array[] = $row;
                }
            }
            
            echo json_encode([
                'success' => true,
                'caja' => $resultado['caja'],
                'ventas' => $ventas_array,
                'compras' => $compras_array,
                'movimientos' => $movimientos_array
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Caja no encontrada']);
        }
        break;

    case 'obtenerEstadisticas':
        $resultado = $caja->obtenerEstadisticasCajaActual();
        if ($resultado) {
            echo json_encode(['success' => true, 'data' => $resultado]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No hay caja abierta']);
        }
        break;

    case 'listar':
        $desde = isset($_POST["fecha_inicio"]) ? limpiarcadena($_POST["fecha_inicio"]) : "";
        $hasta = isset($_POST["fecha_fin"]) ? limpiarcadena($_POST["fecha_fin"]) : "";
        $estado = isset($_POST["estado"]) ? limpiarcadena($_POST["estado"]) : "todos";
        
        $rspta = $caja->listarCajas($desde, $hasta, $estado);
        
        $data = Array();
        while ($reg = $rspta->fetch_object()) {
            $data[] = array(
                "0" => $reg->idcaja,
                "1" => $reg->fecha,
                "2" => $reg->usuario,
                "3" => $reg->hora_apertura,
                "4" => $reg->hora_cierre ? $reg->hora_cierre : '<span class="label label-warning">Abierta</span>',
                "5" => 'S/. ' . number_format($reg->monto_inicial, 2),
                "6" => $reg->monto_final ? 'S/. ' . number_format($reg->monto_final, 2) : '-',
                "7" => 'S/. ' . number_format($reg->total_ventas, 2),
                "8" => 'S/. ' . number_format($reg->total_compras, 2),
                "9" => 'S/. ' . number_format($reg->saldo_calculado, 2),
                "10" => $reg->diferencia !== null ? 'S/. ' . number_format($reg->diferencia, 2) : '-',
                "11" => $reg->num_ventas,
                "12" => $reg->num_compras,
                "13" => ($reg->estado == 'Abierta') 
                    ? '<span class="label label-success">Abierta</span>' 
                    : '<span class="label label-default">Cerrada</span>',
                "14" => '<button class="btn btn-info btn-xs" onclick="mostrarResumen(' . $reg->idcaja . ')"><i class="fa fa-eye"></i> Ver</button>' .
                        (($reg->estado == 'Cerrada') 
                            ? ' <button class="btn btn-warning btn-xs" onclick="imprimirCierre(' . $reg->idcaja . ')"><i class="fa fa-print"></i> PDF</button>' 
                            : '')
            );
        }
        
        $results = array(
            "sEcho" => 1,
            "iTotalRecords" => count($data),
            "iTotalDisplayRecords" => count($data),
            "aaData" => $data
        );
        
        echo json_encode($results);
        break;

    case 'registrarMovimientoManual':
        $idcaja_abierta = $caja->verificarCajaAbierta();
        if (!$idcaja_abierta) {
            echo json_encode(['success' => false, 'message' => 'No hay caja abierta']);
            break;
        }
        
        $resultado = $caja->registrarMovimientoManual($idcaja_abierta, $tipo_movimiento, $monto, $descripcion);
        if ($resultado) {
            echo json_encode(['success' => true, 'message' => 'Movimiento registrado correctamente']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al registrar movimiento']);
        }
        break;

    case 'obtenerMovimientos':
        $rspta = $caja->obtenerMovimientos($idcaja);
        
        $data = Array();
        while ($reg = $rspta->fetch_object()) {
            $tipo_badge = '';
            switch ($reg->tipo_movimiento) {
                case 'venta':
                    $tipo_badge = '<span class="label label-success">Venta</span>';
                    break;
                case 'compra':
                    $tipo_badge = '<span class="label label-danger">Compra</span>';
                    break;
                case 'ingreso_manual':
                    $tipo_badge = '<span class="label label-info">Ingreso</span>';
                    break;
                case 'egreso_manual':
                    $tipo_badge = '<span class="label label-warning">Egreso</span>';
                    break;
            }
            
            $data[] = array(
                "0" => date('d/m/Y H:i', strtotime($reg->fecha_hora)),
                "1" => $tipo_badge,
                "2" => $reg->detalle,
                "3" => 'S/. ' . number_format($reg->monto, 2)
            );
        }
        
        echo json_encode($data);
        break;
}
