<?php 
ob_start();
if (strlen(session_id()) < 1){
	session_start();//Validamos si existe o no la sesión
}
if (!isset($_SESSION["nombre"]))
{
  header("Location: login.php");//Validamos el acceso solo a los usuarios logueados al sistema.
}
else
{
//Validamos el acceso solo al usuario logueado y autorizado.
if ($_SESSION['almacen']==1)
{
require_once "../modelos/Categoria.php";

$categoria=new Categoria();

$idcategoria=isset($_POST["idcategoria"])? limpiarCadena($_POST["idcategoria"]):"";
$nombre=isset($_POST["nombre"])? limpiarCadena($_POST["nombre"]):"";
$descripcion=isset($_POST["descripcion"])? limpiarCadena($_POST["descripcion"]):"";

switch ($_GET["op"]){
	case 'guardaryeditar':
	    if (empty($idcategoria)){
	        $rspta = $categoria->insertar($nombre,$descripcion);
	        if ($rspta === "duplicado") {
	            echo "duplicado";
	        } else {
	            echo $rspta ? "Categoría registrada" : "No se pudo registrar";
	        }
	    } else {
	        $rspta = $categoria->editar($idcategoria,$nombre,$descripcion);
	        echo $rspta ? "Categoría actualizada" : "No se pudo actualizar";
	    }
	break;

	case 'desactivar':
		$rspta=$categoria->desactivar($idcategoria);
 		echo $rspta ? "Categoría Desactivada" : "Categoría no se puede desactivar";
	break;

	case 'activar':
		$rspta=$categoria->activar($idcategoria);
 		echo $rspta ? "Categoría activada" : "Categoría no se puede activar";
	break;

	case 'mostrar':
		$rspta=$categoria->mostrar($idcategoria);
 		//Codificar el resultado utilizando json
 		echo json_encode($rspta);
	break;

	case 'listar':
		$rspta=$categoria->listar();
 		//Vamos a declarar un array
 		$data= Array();

 		while ($reg=$rspta->fetch_object()){
 			$data[]=array(
 				"0"=>($reg->condicion)?'<button class="btn btn-warning" onclick="mostrar('.$reg->idcategoria.')"><i class="fa fa-pencil"></i></button>'.
 					' <button class="btn btn-danger" onclick="desactivar('.$reg->idcategoria.')"><i class="fa fa-close"></i></button>':
 					'<button class="btn btn-warning" onclick="mostrar('.$reg->idcategoria.')"><i class="fa fa-pencil"></i></button>'.
 					' <button class="btn btn-primary" onclick="activar('.$reg->idcategoria.')"><i class="fa fa-check"></i></button>',
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

	// ==================== ENDPOINTS PARA KPIs ====================
	
	case 'categorias_sin_articulos':
		header('Content-Type: application/json; charset=utf-8');
		$rspta = $categoria->categoriasSinArticulos();
		$categorias = array();
		$total = 0;
		
		if ($rspta) {
			while ($reg = $rspta->fetch_object()) {
				$categorias[] = $reg->nombre;
				$total++;
			}
		}
		
		echo json_encode(array(
			'success' => true,
			'total' => $total,
			'categorias' => $categorias
		));
	break;

	case 'categorias_stock_critico':
		header('Content-Type: application/json; charset=utf-8');
		$rspta = $categoria->categoriasStockCritico();
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

	case 'categorias_nuevas':
		header('Content-Type: application/json; charset=utf-8');
		$rspta = $categoria->categoriasNuevas();
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