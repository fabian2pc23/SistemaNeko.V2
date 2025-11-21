<?php 
require_once "../modelos/Consultas.php";

$consulta=new Consultas();

switch ($_GET["op"]){
    case 'comprasfecha':
        $fecha_inicio=$_REQUEST["fecha_inicio"];
        $fecha_fin=$_REQUEST["fecha_fin"];

        $rspta=$consulta->comprasfecha($fecha_inicio,$fecha_fin);
        $data= Array();

        while ($reg=$rspta->fetch_object()){
            $data[]=array(
                "0"=>$reg->fecha,
                "1"=>$reg->usuario,
                "2"=>$reg->proveedor,
                "3"=>$reg->tipo_comprobante,
                "4"=>$reg->serie_comprobante.' '.$reg->num_comprobante,
                "5"=>$reg->total_compra,
                "6"=>$reg->impuesto,
                "7"=>($reg->estado=='Aceptado')?'<span class="label bg-green">Aceptado</span>':'<span class="label bg-red">Anulado</span>'
                );
        }
        $results = array(
            "sEcho"=>1, //Información para el datatables
            "iTotalRecords"=>count($data), //enviamos el total registros al datatable
            "iTotalDisplayRecords"=>count($data), //enviamos el total registros a visualizar
            "aaData"=>$data);
        echo json_encode($results);
    break;

    case 'ventasfechacliente':
        $fecha_inicio=$_REQUEST["fecha_inicio"];
        $fecha_fin=$_REQUEST["fecha_fin"];
        $idcliente=$_REQUEST["idcliente"];

        $rspta=$consulta->ventasfechacliente($fecha_inicio,$fecha_fin,$idcliente);
        $data= Array();

        while ($reg=$rspta->fetch_object()){
            $data[]=array(
                "0"=>$reg->fecha,
                "1"=>$reg->usuario,
                "2"=>$reg->cliente,
                "3"=>$reg->tipo_comprobante,
                "4"=>$reg->serie_comprobante.' '.$reg->num_comprobante,
                "5"=>$reg->total_venta,
                "6"=>$reg->impuesto,
                "7"=>($reg->estado=='Aceptado')?'<span class="label bg-green">Aceptado</span>':'<span class="label bg-red">Anulado</span>'
                );
        }
        $results = array(
            "sEcho"=>1, //Información para el datatables
            "iTotalRecords"=>count($data), //enviamos el total registros al datatable
            "iTotalDisplayRecords"=>count($data), //enviamos el total registros a visualizar
            "aaData"=>$data);
        echo json_encode($results);
    break;

    /* ---------- COMPRAS DASHBOARD AJAX ---------- */

    case 'comprasfecha_grafico':
        $fecha_inicio = $_REQUEST["fecha_inicio"];
        $fecha_fin = $_REQUEST["fecha_fin"];

        $rspta = $consulta->comprasfecha_grafico($fecha_inicio, $fecha_fin);
        $fechas = array();
        $totales = array();
        
        while ($reg = $rspta->fetch_object()) {
            $fechas[] = $reg->fecha;
            $totales[] = $reg->total;
        }
        
        echo json_encode(array(
            "fechas" => $fechas,
            "totales" => $totales
        ));
        break;

    case 'comprasfecha_proveedor':
        $fecha_inicio = $_REQUEST["fecha_inicio"];
        $fecha_fin = $_REQUEST["fecha_fin"];

        $rspta = $consulta->comprasfecha_proveedor($fecha_inicio, $fecha_fin);
        $proveedores = array();
        $totales = array();
        
        while ($reg = $rspta->fetch_object()) {
            $proveedores[] = $reg->proveedor;
            $totales[] = $reg->total;
        }
        
        echo json_encode(array(
            "proveedores" => $proveedores,
            "totales" => $totales
        ));
        break;

    case 'compras_categoria':
        $fecha_inicio = $_REQUEST["fecha_inicio"];
        $fecha_fin = $_REQUEST["fecha_fin"];
        $rspta = $consulta->compras_categoria($fecha_inicio, $fecha_fin);
        $labels = array(); $data = array();
        while ($reg = $rspta->fetch_object()) {
            $labels[] = $reg->categoria;
            $data[] = $reg->total;
        }
        echo json_encode(array("labels"=>$labels, "data"=>$data));
        break;

    case 'compras_productos_top':
        $fecha_inicio = $_REQUEST["fecha_inicio"];
        $fecha_fin = $_REQUEST["fecha_fin"];
        $rspta = $consulta->compras_productos_top($fecha_inicio, $fecha_fin);
        $labels = array(); $data = array();
        while ($reg = $rspta->fetch_object()) {
            $labels[] = $reg->articulo;
            $data[] = $reg->total;
        }
        echo json_encode(array("labels"=>$labels, "data"=>$data));
        break;

    case 'compras_comprobante':
        $fecha_inicio = $_REQUEST["fecha_inicio"];
        $fecha_fin = $_REQUEST["fecha_fin"];
        $rspta = $consulta->compras_comprobante($fecha_inicio, $fecha_fin);
        $labels = array(); $data = array();
        while ($reg = $rspta->fetch_object()) {
            $labels[] = $reg->tipo_comprobante;
            $data[] = $reg->total;
        }
        echo json_encode(array("labels"=>$labels, "data"=>$data));
        break;

    case 'compras_usuario':
        $fecha_inicio = $_REQUEST["fecha_inicio"];
        $fecha_fin = $_REQUEST["fecha_fin"];
        $rspta = $consulta->compras_usuario($fecha_inicio, $fecha_fin);
        $labels = array(); $data = array();
        while ($reg = $rspta->fetch_object()) {
            $labels[] = $reg->usuario;
            $data[] = $reg->total;
        }
        echo json_encode(array("labels"=>$labels, "data"=>$data));
        break;

    case 'compras_kpis':
        $fecha_inicio = $_REQUEST["fecha_inicio"];
        $fecha_fin = $_REQUEST["fecha_fin"];
        $rspta = $consulta->compras_kpis($fecha_inicio, $fecha_fin);
        echo json_encode($rspta);
        break;

    case 'compras_detalle_kpi':
        $fecha_inicio = $_REQUEST["fecha_inicio"];
        $fecha_fin = $_REQUEST["fecha_fin"];
        $tipo = $_REQUEST["tipo"];
        $rspta = $consulta->compras_detalle_kpi($fecha_inicio, $fecha_fin, $tipo);
        echo json_encode($rspta);
        break;

    /* ---------- VENTAS DASHBOARD AJAX ---------- */

    case 'ventas_kpis':
        $fecha_inicio = $_REQUEST["fecha_inicio"];
        $fecha_fin = $_REQUEST["fecha_fin"];
        $idcliente = isset($_REQUEST["idcliente"]) ? $_REQUEST["idcliente"] : '';
        $rspta = $consulta->ventas_kpis($fecha_inicio, $fecha_fin, $idcliente);
        echo json_encode($rspta);
        break;

    case 'ventas_grafico_dias':
        $fecha_inicio = $_REQUEST["fecha_inicio"];
        $fecha_fin = $_REQUEST["fecha_fin"];
        $idcliente = isset($_REQUEST["idcliente"]) ? $_REQUEST["idcliente"] : '';
        $rspta = $consulta->ventas_grafico_dias($fecha_inicio, $fecha_fin, $idcliente);
        $fechas = array();
        $totales = array();
        while ($reg = $rspta->fetch_object()) {
            $fechas[] = $reg->fecha;
            $totales[] = $reg->total;
        }
        echo json_encode(array("fechas" => $fechas, "totales" => $totales));
        break;

    case 'ventas_clientes_top':
        $fecha_inicio = $_REQUEST["fecha_inicio"];
        $fecha_fin = $_REQUEST["fecha_fin"];
        $rspta = $consulta->ventas_clientes_top($fecha_inicio, $fecha_fin);
        $labels = array(); $data = array();
        while ($reg = $rspta->fetch_object()) {
            $labels[] = $reg->cliente;
            $data[] = $reg->total;
        }
        echo json_encode(array("labels"=>$labels, "data"=>$data));
        break;

    case 'ventas_vendedores_top':
        $fecha_inicio = $_REQUEST["fecha_inicio"];
        $fecha_fin = $_REQUEST["fecha_fin"];
        $rspta = $consulta->ventas_vendedores_top($fecha_inicio, $fecha_fin);
        $labels = array(); $data = array();
        while ($reg = $rspta->fetch_object()) {
            $labels[] = $reg->vendedor;
            $data[] = $reg->total;
        }
        echo json_encode(array("labels"=>$labels, "data"=>$data));
        break;

    case 'ventas_categoria':
        $fecha_inicio = $_REQUEST["fecha_inicio"];
        $fecha_fin = $_REQUEST["fecha_fin"];
        $idcliente = isset($_REQUEST["idcliente"]) ? $_REQUEST["idcliente"] : '';
        $rspta = $consulta->ventas_categoria($fecha_inicio, $fecha_fin, $idcliente);
        $labels = array(); $data = array();
        while ($reg = $rspta->fetch_object()) {
            $labels[] = $reg->categoria;
            $data[] = $reg->total;
        }
        echo json_encode(array("labels"=>$labels, "data"=>$data));
        break;

    case 'ventas_productos_top':
        $fecha_inicio = $_REQUEST["fecha_inicio"];
        $fecha_fin = $_REQUEST["fecha_fin"];
        $idcliente = isset($_REQUEST["idcliente"]) ? $_REQUEST["idcliente"] : '';
        $rspta = $consulta->ventas_productos_top($fecha_inicio, $fecha_fin, $idcliente);
        $labels = array(); $data = array();
        while ($reg = $rspta->fetch_object()) {
            $labels[] = $reg->producto;
            $data[] = $reg->total;
        }
        echo json_encode(array("labels"=>$labels, "data"=>$data));
        break;

    case 'ventas_comprobante':
        $fecha_inicio = $_REQUEST["fecha_inicio"];
        $fecha_fin = $_REQUEST["fecha_fin"];
        $idcliente = isset($_REQUEST["idcliente"]) ? $_REQUEST["idcliente"] : '';
        $rspta = $consulta->ventas_comprobante($fecha_inicio, $fecha_fin, $idcliente);
        $labels = array(); $data = array();
        while ($reg = $rspta->fetch_object()) {
            $labels[] = $reg->tipo_comprobante;
            $data[] = $reg->total;
        }
        echo json_encode(array("labels"=>$labels, "data"=>$data));
        break;

    case 'ventas_detalle_kpi':
        $fecha_inicio = $_REQUEST["fecha_inicio"];
        $fecha_fin = $_REQUEST["fecha_fin"];
        $tipo = $_REQUEST["tipo"];
        $idcliente = isset($_REQUEST["idcliente"]) ? $_REQUEST["idcliente"] : '';
        $rspta = $consulta->ventas_detalle_kpi($fecha_inicio, $fecha_fin, $tipo, $idcliente);
        echo json_encode($rspta);
        break;
}
?>
