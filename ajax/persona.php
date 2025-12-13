<?php
ob_start();
if (strlen(session_id()) < 1) {
	session_start(); //Validamos si existe o no la sesión
}
if (!isset($_SESSION["nombre"])) {
	header("Location: ../vistas/login.html"); //Validamos el acceso solo a los usuarios logueados al sistema.
} else {
	//Validamos el acceso solo al usuario logueado y autorizado.
	if ($_SESSION['ventas'] == 1 || $_SESSION['compras'] == 1) {
		require_once "../modelos/Persona.php";

		$persona = new Persona();

		$idpersona = isset($_POST["idpersona"]) ? limpiarCadena($_POST["idpersona"]) : "";
		$tipo_persona = isset($_POST["tipo_persona"]) ? limpiarCadena($_POST["tipo_persona"]) : "";
		$nombre = isset($_POST["nombre"]) ? limpiarCadena($_POST["nombre"]) : "";
		$tipo_documento = isset($_POST["tipo_documento"]) ? limpiarCadena($_POST["tipo_documento"]) : "";
		$num_documento = isset($_POST["num_documento"]) ? limpiarCadena($_POST["num_documento"]) : "";
		$direccion = isset($_POST["direccion"]) ? limpiarCadena($_POST["direccion"]) : "";
		$telefono = isset($_POST["telefono"]) ? limpiarCadena($_POST["telefono"]) : "";
		$email = isset($_POST["email"]) ? limpiarCadena($_POST["email"]) : "";

		switch ($_GET["op"]) {

			// ==============================
			// CONSULTA POR DNI / RUC
			// ==============================
			case 'consultaDoc':
				header('Content-Type: application/json; charset=utf-8');

				$tipo   = isset($_POST['tipo'])   ? $_POST['tipo']   : '';
				$numero = isset($_POST['numero']) ? preg_replace('/\D/', '', $_POST['numero']) : '';

				if ($tipo !== 'DNI' && $tipo !== 'RUC') {
					echo json_encode(['ok' => false, 'msg' => 'Tipo inválido']);
					break;
				}
				if (($tipo === 'DNI' && strlen($numero) !== 8) || ($tipo === 'RUC' && strlen($numero) !== 11)) {
					echo json_encode(['ok' => false, 'msg' => 'Longitud inválida']);
					break;
				}

				try {
					require_once "../modelos/Persona.php";
					$p = new Persona();

					if ($tipo === 'DNI') {
						$info = $p->consultaRENIEC($numero);
						if ($info && !empty($info['nombre'])) {
							echo json_encode(['ok' => true, 'nombre' => $info['nombre'], 'direccion' => isset($info['direccion']) ? $info['direccion'] : '']);
						} else {
							echo json_encode(['ok' => false, 'msg' => 'DNI no encontrado']);
						}
					} else {
						$info = $p->consultaSUNAT($numero);
						if ($info && !empty($info['razon_social'])) {
							echo json_encode(['ok' => true, 'nombre' => $info['razon_social'], 'direccion' => isset($info['direccion']) ? $info['direccion'] : '']);
						} else {
							echo json_encode(['ok' => false, 'msg' => 'RUC no encontrado']);
						}
					}
				} catch (Exception $ex) {
					echo json_encode(['ok' => false, 'msg' => 'Error en servicio']);
				}
				break;

			// ==============================
			// GUARDAR Y EDITAR
			// ==============================
			case 'guardaryeditar':
				// DUPLICADO: validar antes de tocar la BD
				if (empty($idpersona)) {
					if ($persona->existeProveedor($num_documento, 0)) {
						http_response_code(409);
						echo "El proveedor ya existe en el sistema.";
						break;
					}
					$rspta = $persona->insertar($tipo_persona, $nombre, $tipo_documento, $num_documento, $direccion, $telefono, $email);
					echo $rspta ? "Persona registrada" : "Persona no se pudo registrar";
				} else {
					if ($persona->existeProveedor($num_documento, $idpersona)) {
						http_response_code(409);
						echo "Error: el documento ya está registrado en otro proveedor";
						break;
					}
					$rspta = $persona->editar($idpersona, $tipo_persona, $nombre, $tipo_documento, $num_documento, $direccion, $telefono, $email);
					echo $rspta ? "Persona actualizada" : "Persona no se pudo actualizar";
				}
				break;


			// ==============================
			// DESACTIVAR
			// ==============================
			case 'desactivar':
				$rspta = $persona->desactivar($idpersona);
				echo $rspta ? "Persona desactivada" : "No se pudo desactivar la persona";
				break;

			// ==============================
			// ACTIVAR
			// ==============================
			case 'activar':
				$rspta = $persona->activar($idpersona);
				echo $rspta ? "Persona activada" : "No se pudo activar la persona";
				break;

			// ==============================
			// MOSTRAR
			// ==============================
			case 'mostrar':
				$rspta = $persona->mostrar($idpersona);
				echo json_encode($rspta);
				break;

			// ==============================
			// LISTAR PROVEEDORES
			// ==============================
			case 'listarp':
				$rspta = $persona->listarp();
				$data = array();

				while ($reg = $rspta->fetch_object()) {
					$data[] = array(
						"0" => ($reg->condicion) ?
							'<button class="btn btn-warning" onclick="mostrar(' . $reg->idpersona . ')"><i class="fa fa-pencil"></i></button>' .
							' <button class="btn btn-danger" onclick="desactivar(' . $reg->idpersona . ')"><i class="fa fa-close"></i></button>'
							:
							'<button class="btn btn-warning" onclick="mostrar(' . $reg->idpersona . ')"><i class="fa fa-pencil"></i></button>' .
							' <button class="btn btn-primary" onclick="activar(' . $reg->idpersona . ')"><i class="fa fa-check"></i></button>',
						"1" => $reg->nombre,
						"2" => $reg->tipo_documento,
						"3" => $reg->num_documento,
						"4" => $reg->telefono,
						"5" => $reg->email,
						"6" => ($reg->condicion) ?
							'<span class="label bg-green">Activado</span>'
							:
							'<span class="label bg-red">Desactivado</span>'
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

			// ==============================
			// LISTAR CLIENTES
			// ==============================
			case 'listarc':
				$rspta = $persona->listarc();
				$data = array();

				while ($reg = $rspta->fetch_object()) {
					$data[] = array(
						"0" => ($reg->condicion) ?
							'<button class="btn btn-warning" onclick="mostrar(' . $reg->idpersona . ')"><i class="fa fa-pencil"></i></button>' .
							' <button class="btn btn-danger" onclick="desactivar(' . $reg->idpersona . ')"><i class="fa fa-close"></i></button>'
							:
							'<button class="btn btn-warning" onclick="mostrar(' . $reg->idpersona . ')"><i class="fa fa-pencil"></i></button>' .
							' <button class="btn btn-primary" onclick="activar(' . $reg->idpersona . ')"><i class="fa fa-check"></i></button>',
						"1" => $reg->nombre,
						"2" => $reg->tipo_documento,
						"3" => $reg->num_documento,
						"4" => $reg->telefono,
						"5" => $reg->email,
						"6" => ($reg->condicion) ?
							'<span class="label bg-green">Activado</span>'
							:
							'<span class="label bg-red">Desactivado</span>'
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

			case 'kpi_detalle':
				header('Content-Type: application/json; charset=utf-8');
				require_once "../config/Conexion.php";
				$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
				$tipo_persona = isset($_GET['persona']) ? $_GET['persona'] : 'Cliente';
				$result = array('success' => true, 'tipo' => $tipo, 'titulo' => '', 'descripcion' => '', 'datos' => array(), 'columnas' => array());

				switch ($tipo) {
					case 'total':
						$result['titulo'] = 'Total de ' . $tipo_persona . 's';
						$result['descripcion'] = 'Listado de todos los ' . strtolower($tipo_persona) . 's registrados';
						$sql = "SELECT p.nombre, p.tipo_documento, p.num_documento, p.telefono, p.email,
				               CASE WHEN p.condicion = 1 THEN 'Activo' ELSE 'Inactivo' END as estado
				        FROM persona p WHERE p.tipo_persona = '$tipo_persona' ORDER BY p.nombre ASC LIMIT 50";
						$rspta = ejecutarConsulta($sql);
						while ($reg = $rspta->fetch_object()) {
							$result['datos'][] = array(
								'nombre' => $reg->nombre,
								'documento' => $reg->tipo_documento . ': ' . $reg->num_documento,
								'telefono' => $reg->telefono ? $reg->telefono : '-',
								'email' => $reg->email ? $reg->email : '-',
								'estado' => $reg->estado
							);
						}
						$result['columnas'] = ['Nombre', 'Documento', 'Teléfono', 'Email', 'Estado'];
						break;

					case 'activos':
						$result['titulo'] = $tipo_persona . 's Activos';
						$result['descripcion'] = $tipo_persona . 's habilitados en el sistema';
						$sql = "SELECT p.nombre, p.tipo_documento, p.num_documento, p.telefono, p.email
				        FROM persona p WHERE p.tipo_persona = '$tipo_persona' AND p.condicion = 1 ORDER BY p.nombre ASC LIMIT 50";
						$rspta = ejecutarConsulta($sql);
						while ($reg = $rspta->fetch_object()) {
							$result['datos'][] = array(
								'nombre' => $reg->nombre,
								'documento' => $reg->tipo_documento . ': ' . $reg->num_documento,
								'telefono' => $reg->telefono ? $reg->telefono : '-',
								'email' => $reg->email ? $reg->email : '-'
							);
						}
						$result['columnas'] = ['Nombre', 'Documento', 'Teléfono', 'Email'];
						break;

					case 'inactivos':
						$result['titulo'] = $tipo_persona . 's Inactivos';
						$result['descripcion'] = $tipo_persona . 's deshabilitados del sistema';
						$sql = "SELECT p.nombre, p.tipo_documento, p.num_documento, p.telefono
				        FROM persona p WHERE p.tipo_persona = '$tipo_persona' AND p.condicion = 0 ORDER BY p.nombre ASC";
						$rspta = ejecutarConsulta($sql);
						while ($reg = $rspta->fetch_object()) {
							$result['datos'][] = array(
								'nombre' => $reg->nombre,
								'documento' => $reg->tipo_documento . ': ' . $reg->num_documento,
								'telefono' => $reg->telefono ? $reg->telefono : '-'
							);
						}
						$result['columnas'] = ['Nombre', 'Documento', 'Teléfono'];
						break;

					case 'top':
						if ($tipo_persona === 'Cliente') {
							$result['titulo'] = 'Top Clientes por Compras';
							$result['descripcion'] = 'Clientes con más compras realizadas';
							$sql = "SELECT p.nombre, COUNT(v.idventa) as total_compras, 
					               IFNULL(SUM(v.total_venta), 0) as monto_total
					        FROM persona p
					        LEFT JOIN venta v ON p.idpersona = v.idcliente AND v.estado = 'Aceptado'
					        WHERE p.tipo_persona = 'Cliente' AND p.condicion = 1
					        GROUP BY p.idpersona
					        HAVING total_compras > 0
					        ORDER BY total_compras DESC LIMIT 20";
							$rspta = ejecutarConsulta($sql);
							while ($reg = $rspta->fetch_object()) {
								$result['datos'][] = array(
									'nombre' => $reg->nombre,
									'compras' => (int)$reg->total_compras,
									'monto' => 'S/ ' . number_format($reg->monto_total, 2)
								);
							}
							$result['columnas'] = ['Cliente', 'Compras', 'Monto Total'];
						} else {
							$result['titulo'] = 'Top Proveedores';
							$result['descripcion'] = 'Proveedores con más ingresos registrados';
							$sql = "SELECT p.nombre, COUNT(i.idingreso) as total_ingresos, 
					               IFNULL(SUM(i.total_compra), 0) as monto_total
					        FROM persona p
					        LEFT JOIN ingreso i ON p.idpersona = i.idproveedor AND i.estado = 'Aceptado'
					        WHERE p.tipo_persona = 'Proveedor' AND p.condicion = 1
					        GROUP BY p.idpersona
					        HAVING total_ingresos > 0
					        ORDER BY total_ingresos DESC LIMIT 20";
							$rspta = ejecutarConsulta($sql);
							while ($reg = $rspta->fetch_object()) {
								$result['datos'][] = array(
									'nombre' => $reg->nombre,
									'ingresos' => (int)$reg->total_ingresos,
									'monto' => 'S/ ' . number_format($reg->monto_total, 2)
								);
							}
							$result['columnas'] = ['Proveedor', 'Ingresos', 'Monto Total'];
						}
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
