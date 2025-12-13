<?php
ob_start();
if (strlen(session_id()) < 1) {
  session_start();
}

// ¿Usuario logueado?
if (!isset($_SESSION["nombre"])) {
  header("Location: ../vistas/login.html");
} else {

  // Permiso para acceder a este módulo (ajusta el índice según tu sistema)
  if ($_SESSION['acceso'] == 1) {

    require_once "../modelos/Rol.php";
    require_once "../config/Conexion.php";

    $rol = new Rol();

    $idrol  = isset($_POST["idrol"])  ? limpiarCadena($_POST["idrol"])  : "";
    $nombre = isset($_POST["nombre"]) ? limpiarCadena($_POST["nombre"]) : "";

    switch ($_GET["op"]) {

      case 'guardaryeditar':
        // Capturar permisos marcados
        $permisos = isset($_POST["permiso"]) ? $_POST["permiso"] : array();

        if (empty($idrol)) {

          // 1) Insertar el rol
          $rspta = $rol->insertar($nombre);

          // Si es numérico, es el ID insertado (Éxito)
          if (is_numeric($rspta) && $rspta > 0) {
            // 2) Insertar permisos del nuevo rol
            $rol->insertarPermisos($rspta, $permisos);
            echo "✅ Rol registrado con permisos exitosamente";
          } else {
            // Si devuelve string, es un mensaje de error
            echo !empty($rspta) ? $rspta : "❌ No se pudo registrar el rol.";
          }
        } else {

          // 1) Editar rol
          $rspta = $rol->editar($idrol, $nombre);

          // Si es true, es éxito. Si es string, es error.
          if ($rspta === true || $rspta === 1) {
            // 2) Borrar permisos actuales
            $rol->borrarPermisos($idrol);

            // 3) Insertar permisos nuevos seleccionados
            $rol->insertarPermisos($idrol, $permisos);

            echo "✅ Rol actualizado con permisos exitosamente";
          } else {
            // Si devuelve string, es un mensaje de error
            echo !empty($rspta) ? $rspta : "❌ No se pudo actualizar el rol.";
          }
        }
        break;

      case 'permisos':
        $idrol = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        require_once "../modelos/Permiso.php";
        $permiso = new Permiso();

        // Todos los permisos disponibles
        $rspta = $permiso->listar();

        // Permisos ya asociados al rol (si es edición)
        $marcados = array();
        if ($idrol > 0) {
          $rs2 = ejecutarConsulta("SELECT idpermiso FROM rol_permiso WHERE id_rol = '$idrol'");
          while ($reg = $rs2->fetch_object()) {
            $marcados[] = (int)$reg->idpermiso;
          }
        }

        // Generar checkboxes
        while ($reg = $rspta->fetch_object()) {
          $checked = in_array((int)$reg->idpermiso, $marcados) ? 'checked' : '';
          echo '<li>
                  <label>
                    <input type="checkbox" name="permiso[]" value="' . $reg->idpermiso . '" ' . $checked . '>
                    ' . $reg->nombre . '
                  </label>
                </li>';
        }
        break;

      case 'desactivar':
        $rspta = $rol->desactivar($idrol);
        // Si es string, es error. Si es true, éxito.
        if (is_string($rspta)) {
          echo $rspta;
        } else {
          echo $rspta ? "✅ Rol desactivado exitosamente" : "❌ No se pudo desactivar el rol";
        }
        break;

      case 'activar':
        $rspta = $rol->activar($idrol);
        echo $rspta ? "✅ Rol activado exitosamente" : "❌ No se pudo activar el rol";
        break;

      case 'mostrar':
        $rspta = $rol->mostrar($idrol);
        echo json_encode($rspta);
        break;

      case 'listar':
        $rspta = $rol->listar();
        $data = array();

        while ($reg = $rspta->fetch_object()) {

          $btns = ($reg->estado)
            ? '<button class="btn btn-warning btn-sm" onclick="mostrar(' . $reg->id_rol . ')" title="Editar">
                 <i class="fa fa-pencil"></i>
               </button>
               <button class="btn btn-danger btn-sm" onclick="desactivar(' . $reg->id_rol . ')" title="Desactivar">
                 <i class="fa fa-close"></i>
               </button>'
            : '<button class="btn btn-warning btn-sm" onclick="mostrar(' . $reg->id_rol . ')" title="Editar">
                 <i class="fa fa-pencil"></i>
               </button>
               <button class="btn btn-success btn-sm" onclick="activar(' . $reg->id_rol . ')" title="Activar">
                 <i class="fa fa-check"></i>
               </button>';

          $estado = $reg->estado
            ? '<span class="label bg-green">Activo</span>'
            : '<span class="label bg-red">Inactivo</span>';

          $data[] = array(
            "0" => $btns,
            "1" => $reg->id_rol,
            "2" => $reg->nombre,
            "3" => $estado,
            "4" => $reg->creado_en
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

      // Para llenar combos (solo roles activos)
      case 'selectRol':
        $rspta = $rol->listarActivos();
        while ($reg = $rspta->fetch_object()) {
          echo '<option value="' . $reg->id_rol . '">' . $reg->nombre . '</option>';
        }
        break;

      case 'kpi_detalle':
        header('Content-Type: application/json; charset=utf-8');
        $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
        $result = array('success' => true, 'tipo' => $tipo, 'titulo' => '', 'datos' => array(), 'columnas' => array());

        switch ($tipo) {
          case 'total':
            $result['titulo'] = 'Total de Roles';
            $sql = "SELECT r.nombre, r.estado, r.creado_en,
                           (SELECT COUNT(*) FROM usuario_roles_new ur WHERE ur.id_rol = r.id_rol) as usuarios,
                           (SELECT COUNT(*) FROM rol_permiso rp WHERE rp.id_rol = r.id_rol) as permisos
                    FROM rol_usuarios r ORDER BY r.nombre ASC";
            $rspta = ejecutarConsulta($sql);
            while ($reg = $rspta->fetch_object()) {
              $result['datos'][] = array(
                'nombre' => $reg->nombre,
                'usuarios' => (int)$reg->usuarios,
                'permisos' => (int)$reg->permisos,
                'estado' => $reg->estado ? 'Activo' : 'Inactivo'
              );
            }
            $result['columnas'] = ['Nombre', 'Usuarios Asignados', 'Permisos', 'Estado'];
            break;

          case 'activos':
            $result['titulo'] = 'Roles Activos';
            $sql = "SELECT r.nombre,
                           (SELECT COUNT(*) FROM usuario_roles_new ur WHERE ur.id_rol = r.id_rol) as usuarios,
                           (SELECT COUNT(*) FROM rol_permiso rp WHERE rp.id_rol = r.id_rol) as permisos
                    FROM rol_usuarios r WHERE r.estado = 1 ORDER BY r.nombre ASC";
            $rspta = ejecutarConsulta($sql);
            while ($reg = $rspta->fetch_object()) {
              $result['datos'][] = array(
                'nombre' => $reg->nombre,
                'usuarios' => (int)$reg->usuarios,
                'permisos' => (int)$reg->permisos
              );
            }
            $result['columnas'] = ['Nombre', 'Usuarios Asignados', 'Permisos'];
            break;

          case 'inactivos':
            $result['titulo'] = 'Roles Inactivos';
            $sql = "SELECT r.nombre, r.creado_en FROM rol_usuarios r WHERE r.estado = 0 ORDER BY r.nombre ASC";
            $rspta = ejecutarConsulta($sql);
            while ($reg = $rspta->fetch_object()) {
              $result['datos'][] = array(
                'nombre' => $reg->nombre,
                'creado_en' => $reg->creado_en
              );
            }
            $result['columnas'] = ['Nombre', 'Fecha Creación'];
            break;
        }
        echo json_encode($result);
        break;

      default:
        echo json_encode(array("error" => "Operación no válida"));
        break;
    }
  } else {
    require 'noacceso.php';
  }
}
ob_end_flush();
