<?php
ob_start();
if (strlen(session_id()) < 1) {
	session_start(); //Validamos si existe o no la sesión
}
if (!isset($_SESSION["nombre"])) {
	header("Location: login.php"); //Validamos el acceso solo a los usuarios logueados al sistema.
} else {
	//Validamos el acceso solo al usuario logueado y autorizado.
	if ($_SESSION['almacen'] == 1) {
		require_once "../modelos/Categoria.php";

		$categoria = new Categoria();

		$idcategoria = isset($_POST["idcategoria"]) ? limpiarCadena($_POST["idcategoria"]) : "";
		$nombre = isset($_POST["nombre"]) ? limpiarCadena($_POST["nombre"]) : "";
		$descripcion = isset($_POST["descripcion"]) ? limpiarCadena($_POST["descripcion"]) : "";

		switch ($_GET["op"]) {
			case 'guardaryeditar':
				if (empty($idcategoria)) {
					$rspta = $categoria->insertar($nombre, $descripcion);
					if ($rspta === "duplicado") {
						echo "duplicado";
					} else {
						echo $rspta ? "Categoría registrada" : "No se pudo registrar";
					}
				} else {
					$rspta = $categoria->editar($idcategoria, $nombre, $descripcion);
					echo $rspta ? "Categoría actualizada" : "No se pudo actualizar";
				}
				break;

			case 'desactivar':
				$rspta = $categoria->desactivar($idcategoria);
				echo $rspta ? "Categoría Desactivada" : "Categoría no se puede desactivar";
				break;

			case 'activar':
				$rspta = $categoria->activar($idcategoria);
				echo $rspta ? "Categoría activada" : "Categoría no se puede activar";
				break;

			case 'mostrar':
				$rspta = $categoria->mostrar($idcategoria);
				//Codificar el resultado utilizando json
				echo json_encode($rspta);
				break;

			case 'listar':
				$rspta = $categoria->listar();
				//Vamos a declarar un array
				$data = array();

				while ($reg = $rspta->fetch_object()) {
					$data[] = array(
						"0" => ($reg->condicion) ? '<button class="btn btn-warning" onclick="mostrar(' . $reg->idcategoria . ')"><i class="fa fa-pencil"></i></button>' .
							' <button class="btn btn-danger" onclick="desactivar(' . $reg->idcategoria . ')"><i class="fa fa-close"></i></button>' :
							'<button class="btn btn-warning" onclick="mostrar(' . $reg->idcategoria . ')"><i class="fa fa-pencil"></i></button>' .
							' <button class="btn btn-primary" onclick="activar(' . $reg->idcategoria . ')"><i class="fa fa-check"></i></button>',
						"1" => $reg->nombre,
						"2" => $reg->descripcion,
						"3" => ($reg->condicion) ? '<span class="label bg-green">Activado</span>' :
							'<span class="label bg-red">Desactivado</span>'
					);
				}
				$results = array(
					"sEcho" => 1, //Información para el datatables
					"iTotalRecords" => count($data), //enviamos el total registros al datatable
					"iTotalDisplayRecords" => count($data), //enviamos el total registros a visualizar
					"aaData" => $data
				);
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

			case 'kpi_detalle':
				header('Content-Type: application/json; charset=utf-8');
				require_once "../config/Conexion.php";
				$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
				$result = array('success' => true, 'tipo' => $tipo, 'titulo' => '', 'descripcion' => '', 'datos' => array(), 'columnas' => array());

				switch ($tipo) {
					case 'total':
						$result['titulo'] = 'Total de Categorías';
						$result['descripcion'] = 'Listado de todas las categorías registradas en el sistema';
						$sql = "SELECT c.nombre, c.descripcion, 
				               CASE WHEN c.condicion = 1 THEN 'Activa' ELSE 'Inactiva' END as estado,
				               (SELECT COUNT(*) FROM articulo a WHERE a.idcategoria = c.idcategoria) as articulos
				        FROM categoria c ORDER BY c.nombre ASC";
						$rspta = ejecutarConsulta($sql);
						while ($reg = $rspta->fetch_object()) {
							$result['datos'][] = array(
								'nombre' => $reg->nombre,
								'descripcion' => $reg->descripcion ? $reg->descripcion : '-',
								'estado' => $reg->estado,
								'articulos' => (int)$reg->articulos
							);
						}
						$result['columnas'] = ['Nombre', 'Descripción', 'Estado', 'Artículos'];
						break;

					case 'activas':
						$result['titulo'] = 'Categorías Activas';
						$result['descripcion'] = 'Categorías habilitadas para uso en el sistema';
						$sql = "SELECT c.nombre, c.descripcion,
				               (SELECT COUNT(*) FROM articulo a WHERE a.idcategoria = c.idcategoria) as articulos
				        FROM categoria c WHERE c.condicion = 1 ORDER BY c.nombre ASC";
						$rspta = ejecutarConsulta($sql);
						while ($reg = $rspta->fetch_object()) {
							$result['datos'][] = array(
								'nombre' => $reg->nombre,
								'descripcion' => $reg->descripcion ? $reg->descripcion : '-',
								'articulos' => (int)$reg->articulos
							);
						}
						$result['columnas'] = ['Nombre', 'Descripción', 'Artículos'];
						break;

					case 'inactivas':
						$result['titulo'] = 'Categorías Inactivas';
						$result['descripcion'] = 'Categorías deshabilitadas del sistema';
						$sql = "SELECT c.nombre, c.descripcion FROM categoria c WHERE c.condicion = 0 ORDER BY c.nombre ASC";
						$rspta = ejecutarConsulta($sql);
						while ($reg = $rspta->fetch_object()) {
							$result['datos'][] = array(
								'nombre' => $reg->nombre,
								'descripcion' => $reg->descripcion ? $reg->descripcion : '-'
							);
						}
						$result['columnas'] = ['Nombre', 'Descripción'];
						break;

					case 'sin_articulos':
						$result['titulo'] = 'Categorías Sin Artículos';
						$result['descripcion'] = 'Categorías que no tienen productos asociados';
						$sql = "SELECT c.nombre, c.descripcion, CASE WHEN c.condicion = 1 THEN 'Activa' ELSE 'Inactiva' END as estado
				        FROM categoria c 
				        WHERE NOT EXISTS (SELECT 1 FROM articulo a WHERE a.idcategoria = c.idcategoria)
				        ORDER BY c.nombre ASC";
						$rspta = ejecutarConsulta($sql);
						while ($reg = $rspta->fetch_object()) {
							$result['datos'][] = array(
								'nombre' => $reg->nombre,
								'descripcion' => $reg->descripcion ? $reg->descripcion : '-',
								'estado' => $reg->estado
							);
						}
						$result['columnas'] = ['Nombre', 'Descripción', 'Estado'];
						break;

					case 'stock_critico':
						$result['titulo'] = 'Categorías con Stock Crítico';
						$result['descripcion'] = 'Categorías con artículos que tienen stock menor a 5 unidades';
						$sql = "SELECT c.nombre, COUNT(a.idarticulo) as articulos_criticos, MIN(a.stock) as stock_minimo
				        FROM categoria c
				        INNER JOIN articulo a ON c.idcategoria = a.idcategoria
				        WHERE a.stock < 5 AND a.condicion = 1
				        GROUP BY c.idcategoria
				        ORDER BY articulos_criticos DESC";
						$rspta = ejecutarConsulta($sql);
						while ($reg = $rspta->fetch_object()) {
							$result['datos'][] = array(
								'nombre' => $reg->nombre,
								'articulos_criticos' => (int)$reg->articulos_criticos,
								'stock_minimo' => (int)$reg->stock_minimo
							);
						}
						$result['columnas'] = ['Categoría', 'Art. Críticos', 'Stock Mín.'];
						break;

					case 'nuevas':
						$result['titulo'] = 'Categorías Nuevas (30 días)';
						$result['descripcion'] = 'Categorías creadas en los últimos 30 días';
						$sql = "SELECT c.nombre, c.descripcion, c.fecha_creacion,
				               CASE WHEN c.condicion = 1 THEN 'Activa' ELSE 'Inactiva' END as estado
				        FROM categoria c 
				        WHERE c.fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
				        ORDER BY c.fecha_creacion DESC";
						$rspta = ejecutarConsulta($sql);
						while ($reg = $rspta->fetch_object()) {
							$result['datos'][] = array(
								'nombre' => $reg->nombre,
								'descripcion' => $reg->descripcion ? $reg->descripcion : '-',
								'fecha' => $reg->fecha_creacion ? date('d/m/Y', strtotime($reg->fecha_creacion)) : '-',
								'estado' => $reg->estado
							);
						}
						$result['columnas'] = ['Nombre', 'Descripción', 'Fecha', 'Estado'];
						break;
				}
				echo json_encode($result);
				break;
		}
		//Fin de las validaciones de acceso
	} else {
		require 'noacceso.php';
	}
}
ob_end_flush();
