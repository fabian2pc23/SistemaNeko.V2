<?php 
ob_start();
if (strlen(session_id()) < 1){
	session_start();//Validamos si existe o no la sesión
}
if (!isset($_SESSION["nombre"]))
{
  header("Location: ../login.php");//Validamos el acceso solo a los usuarios logueados al sistema.
}
else
{
//Validamos el acceso solo al usuario logueado y autorizado.
if ($_SESSION['almacen']==1)
{
require_once "../modelos/Marca.php";

$marca=new Marca();

$idmarca=isset($_POST["idmarca"])? limpiarCadena($_POST["idmarca"]):"";
$nombre=isset($_POST["nombre"])? limpiarCadena($_POST["nombre"]):"";
$descripcion=isset($_POST["descripcion"])? limpiarCadena($_POST["descripcion"]):"";

switch ($_GET["op"]){
	case 'guardaryeditar':
	    if (empty($idmarca)){
	        $rspta = $marca->insertar($nombre,$descripcion);
	        if ($rspta === "duplicado") {
	            echo "duplicado";
	        } else {
	            echo $rspta ? "Marca registrada" : "No se pudo registrar";
	        }
	    } else {
	        $rspta = $marca->editar($idmarca,$nombre,$descripcion);
	        echo $rspta ? "Marca actualizada" : "No se pudo actualizar";
	    }
	break;

	case 'desactivar':
		$rspta=$marca->desactivar($idmarca);
 		echo $rspta ? "Marca Desactivada" : "Marca no se puede desactivar";
	break;

	case 'activar':
		$rspta=$marca->activar($idmarca);
 		echo $rspta ? "Marca activada" : "Marca no se puede activar";
	break;

	case 'mostrar':
		$rspta=$marca->mostrar($idmarca);
 		//Codificar el resultado utilizando json
 		echo json_encode($rspta);
	break;

	case 'listar':
		$rspta=$marca->listar();
 		//Vamos a declarar un array
 		$data= Array();

 		while ($reg=$rspta->fetch_object()){
 			$data[]=array(
 				"0"=>($reg->condicion)?'<button class="btn btn-warning" onclick="mostrar('.$reg->idmarca.')"><i class="fa fa-pencil"></i></button>'.
 					' <button class="btn btn-danger" onclick="desactivar('.$reg->idmarca.')"><i class="fa fa-close"></i></button>':
 					'<button class="btn btn-warning" onclick="mostrar('.$reg->idmarca.')"><i class="fa fa-pencil"></i></button>'.
 					' <button class="btn btn-primary" onclick="activar('.$reg->idmarca.')"><i class="fa fa-check"></i></button>',
 				"1"=>$reg->nombre,
 				"2"=>$reg->descripcion,
 				"3"=>($reg->condicion)?'<span class="label bg-green">Activado</span>':
 				'<span class="label bg-red">Desactivado</span>'
 				);
 		}
 		$results = array(
 			"sEcho"=>1, //Información para el datatables
 			"iTotalRecords"=>count($data), //enviamos el total registros al datatable
 			"iTotalDisplayRecords"=>count($data), //enviamos el total registros a visualizar
 			"aaData"=>$data);
 		echo json_encode($results);
	break;

	case 'select':
		$rspta = $marca->listarActivos();
		echo '<option value="">- Seleccione -</option>';
		while ($reg = $rspta->fetch_object()) {
			echo '<option value="'.$reg->idmarca.'">'.$reg->nombre.'</option>';
		}
	break;

	// ==================== ENDPOINTS PARA KPIs ====================
	
	case 'marcas_sin_articulos':
		header('Content-Type: application/json; charset=utf-8');
		$rspta = $marca->marcasSinArticulos();
		$marcas = array();
		$total = 0;
		
		if ($rspta) {
			while ($reg = $rspta->fetch_object()) {
				$marcas[] = $reg->nombre;
				$total++;
			}
		}
		
		echo json_encode(array(
			'success' => true,
			'total' => $total,
			'marcas' => $marcas
		));
	break;

	case 'marcas_stock_critico':
		header('Content-Type: application/json; charset=utf-8');
		$rspta = $marca->marcasStockCritico();
		if ($rspta && is_array($rspta)) {
			echo json_encode(array(
				'success' => true,
				'total' => isset($rspta['total']) ? (int)$rspta['total'] : 0
			));
		} else {
			echo json_encode(array(
				'success' => false,
				'total' => 0,
				'error' => 'No se pudo obtener el resultado'
			));
		}
	break;

	case 'marcas_nuevas':
		header('Content-Type: application/json; charset=utf-8');
		$rspta = $marca->marcasNuevas();
		if ($rspta && is_array($rspta)) {
			echo json_encode(array(
				'success' => true,
				'total' => isset($rspta['total']) ? (int)$rspta['total'] : 0
			));
		} else {
			echo json_encode(array(
				'success' => false,
				'total' => 0,
				'error' => 'No se pudo obtener el resultado'
			));
		}
	break;
}
//Fin de las validaciones de acceso
}
else
{
  require 'noacceso.php';
}
}
ob_end_flush();
?>