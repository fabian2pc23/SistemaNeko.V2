<?php 
// ajax/venta.php
ob_start();
if (strlen(session_id()) < 1){
    session_start();//Validamos si existe o no la sesión
}

if (!isset($_SESSION["nombre"]))
{
    header("Location: ../vistas/login.html");//Validamos el acceso solo a los usuarios logueados al sistema.
}
else
{
    //Validamos el acceso solo al usuario logueado y autorizado.
    if ($_SESSION['ventas']==1)
    {
        require_once "../modelos/Venta.php";
        require_once "../modelos/Correlativo.php";

        $venta        = new Venta();
        $correlativo  = new Correlativo();

        $idventa          = isset($_POST["idventa"])? limpiarCadena($_POST["idventa"]):"";
        $idcliente        = isset($_POST["idcliente"])? limpiarCadena($_POST["idcliente"]):"";
        $idusuario        = $_SESSION["idusuario"] ?? 0;
        $tipo_comprobante = isset($_POST["tipo_comprobante"])? limpiarCadena($_POST["tipo_comprobante"]):"";
        $serie_comprobante= isset($_POST["serie_comprobante"])? limpiarCadena($_POST["serie_comprobante"]):"";
        $num_comprobante  = isset($_POST["num_comprobante"])? limpiarCadena($_POST["num_comprobante"]):"";
        $fecha_hora       = isset($_POST["fecha_hora"])? limpiarCadena($_POST["fecha_hora"]):"";
        $impuesto         = isset($_POST["impuesto"])? limpiarCadena($_POST["impuesto"]):"";
        $total_venta      = isset($_POST["total_venta"])? limpiarCadena($_POST["total_venta"]):"";

        switch ($_GET["op"])
        {
            /* =========================================================
             * GUARDAR / EDITAR
             * =========================================================*/
            case 'guardaryeditar':

                header('Content-Type: application/json; charset=utf-8');

                $resp = [
                    'success' => false,
                    'message' => '',
                    'errors'  => []
                ];

                try {

                    // --- 1. Sanitizar entradas básicas ---
                    $idventa          = isset($_POST['idventa']) ? (int)$_POST['idventa'] : 0;
                    $idcliente        = isset($_POST['idcliente']) ? (int)$_POST['idcliente'] : 0;
                    $tipo_comprobante = isset($_POST['tipo_comprobante']) ? limpiarCadena($_POST['tipo_comprobante']) : '';
                    $serie_comprobante= isset($_POST['serie_comprobante']) ? limpiarCadena($_POST['serie_comprobante']) : '';
                    $num_comprobante  = isset($_POST['num_comprobante']) ? limpiarCadena($_POST['num_comprobante']) : '';
                    $fecha_hora       = isset($_POST['fecha_hora']) ? limpiarCadena($_POST['fecha_hora']) : '';
                    $impuesto         = isset($_POST['impuesto']) ? (float)$_POST['impuesto'] : 0.0;
                    $total_venta_post = isset($_POST['total_venta']) ? (float)$_POST['total_venta'] : 0.0;

                    $idusuario = isset($_SESSION['idusuario']) ? (int)$_SESSION['idusuario'] : 0;

                    $idarticulo   = $_POST['idarticulo']   ?? [];
                    $cantidad     = $_POST['cantidad']     ?? [];
                    $precio_venta = $_POST['precio_venta'] ?? [];
                    $descuento    = $_POST['descuento']    ?? [];

                    // --- 2. Validaciones básicas de cabecera ---
                    if ($idcliente <= 0) {
                        $resp['errors']['idcliente'] = 'Debe seleccionar un cliente válido.';
                    }

                    $validTypes = ['Boleta','Factura','Ticket'];
                    if (!in_array($tipo_comprobante, $validTypes, true)) {
                        $resp['errors']['tipo_comprobante'] = 'Tipo de comprobante inválido.';
                    }

                    if ($tipo_comprobante !== 'Ticket') {
                        if ($serie_comprobante === '') {
                            $resp['errors']['serie_comprobante'] = 'La serie es obligatoria.';
                        }
                        if ($num_comprobante === '') {
                            $resp['errors']['num_comprobante'] = 'El número es obligatorio.';
                        }
                    }

                    if ($fecha_hora === '') {
                        $resp['errors']['fecha_hora'] = 'La fecha de la venta es obligatoria.';
                    }

                    if ($impuesto < 0 || $impuesto > 100) {
                        $resp['errors']['impuesto'] = 'El impuesto debe estar entre 0 y 100.';
                    }

                    // --- 3. Validaciones de detalle ---
                    $n1 = count($idarticulo);
                    $n2 = count($cantidad);
                    $n3 = count($precio_venta);
                    $n4 = count($descuento);

                    if ($n1 === 0) {
                        $resp['errors']['detalles'] = 'Debe agregar al menos un artículo al detalle.';
                    } elseif (!($n1 === $n2 && $n2 === $n3 && $n3 === $n4)) {
                        $resp['errors']['detalles'] = 'Los arreglos de detalle no coinciden en longitud.';
                    }

                    // --- 4. Si ya hay errores, devolvemos sin intentar guardar ---
                    if (!empty($resp['errors'])) {
                        $resp['message'] = 'Hay errores en el formulario.';
                        echo json_encode($resp);
                        break;
                    }

                    // --- 5. Recalcular el total en el backend ---
                    $total_venta_calc = 0.0;
                    for ($i = 0; $i < $n1; $i++) {
                        $idart = (int)$idarticulo[$i];
                        $cant  = (int)$cantidad[$i];
                        $prec  = (float)$precio_venta[$i];
                        $desc  = (float)$descuento[$i];

                        if ($idart <= 0) {
                            $resp['errors']["idarticulo_$i"] = 'Artículo inválido en la fila '.($i+1).'.';
                        }
                        if ($cant <= 0) {
                            $resp['errors']["cantidad_$i"] = 'La cantidad debe ser mayor a cero (fila '.($i+1).').';
                        }
                        if ($prec <= 0) {
                            $resp['errors']["precio_$i"] = 'El precio debe ser mayor a cero (fila '.($i+1).').';
                        }
                        if ($desc < 0) {
                            $resp['errors']["descuento_$i"] = 'El descuento no puede ser negativo (fila '.($i+1).').';
                        }

                        $subtotal = ($cant * $prec) - $desc;
                        if ($subtotal < 0) {
                            $resp['errors']["subtotal_$i"] = 'El descuento no puede superar el subtotal (fila '.($i+1).').';
                        }

                        $total_venta_calc += max(0, $subtotal);
                    }

                    if (!empty($resp['errors'])) {
                        $resp['message'] = 'Hay errores en las líneas del detalle.';
                        echo json_encode($resp);
                        break;
                    }

                    if ($total_venta_calc <= 0) {
                        $resp['errors']['total_venta'] = 'El total de la venta debe ser mayor a cero.';
                        $resp['message'] = 'El total calculado es cero.';
                        echo json_encode($resp);
                        break;
                    }

                    // Opcional: comparar con lo que vino del frontend
                    if (abs($total_venta_calc - $total_venta_post) > 0.01) {
                        // Te quedas con el calculado del servidor
                        $total_venta_post = $total_venta_calc;
                    }

                    // --- 6. Insertar la venta ---
                    if (empty($idventa)) {

                        $rspta = $venta->insertar(
                            $idcliente,
                            $idusuario,
                            $tipo_comprobante,
                            $serie_comprobante,
                            $num_comprobante,
                            $fecha_hora,
                            $impuesto,
                            $total_venta_post,
                            $idarticulo,
                            $cantidad,
                            $precio_venta,
                            $descuento
                        );

                        if ($rspta) {
                            // avanzar correlativo solo si la venta se guardó bien
                            $correlativo->incrementar($tipo_comprobante);

                            $resp['success'] = true;
                            $resp['message'] = 'Venta registrada correctamente.';
                        } else {
                            $resp['success'] = false;
                            $resp['message'] = 'No se pudieron registrar todos los datos de la venta.';
                        }
                    } else {
                        // Por ahora no implementas edición de ventas
                        $resp['success'] = false;
                        $resp['message'] = 'Edición de ventas no implementada.';
                    }

                } catch (Exception $e) {
                    $resp['success'] = false;
                    $resp['message'] = 'Error interno al registrar la venta.';
                    $resp['errors']['exception'] = $e->getMessage();
                }

                echo json_encode($resp);
            break;

            /* =========================================================
             * ANULAR
             * =========================================================*/
            case 'anular':
                $rspta = $venta->anular($idventa);
                echo $rspta ? "Venta anulada" : "Venta no se puede anular";
            break;

            /* =========================================================
             * MOSTRAR
             * =========================================================*/
            case 'mostrar':
                $rspta = $venta->mostrar($idventa);
                echo json_encode($rspta);
            break;

            /* =========================================================
             * LISTAR DETALLE
             * =========================================================*/
            case 'listarDetalle':
                $id = $_GET['id'];

                $rspta = $venta->listarDetalle($id);
                $total=0;
                echo '<thead style="background-color:#A9D0F5">
                        <th>Opciones</th>
                        <th>Artículo</th>
                        <th>Cantidad</th>
                        <th>Precio Venta</th>
                        <th>Descuento</th>
                        <th>Subtotal</th>
                      </thead>';

                while ($reg = $rspta->fetch_object())
                {
                    echo '<tr class="filas">
                            <td></td>
                            <td>'.$reg->nombre.'</td>
                            <td>'.$reg->cantidad.'</td>
                            <td>'.$reg->precio_venta.'</td>
                            <td>'.$reg->descuento.'</td>
                            <td>'.$reg->subtotal.'</td>
                          </tr>';
                    $total = $total + ($reg->precio_venta*$reg->cantidad-$reg->descuento);
                }
                echo '<tfoot>
                        <th>TOTAL</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th><h4 id="total">S/.'.$total.'</h4>
                            <input type="hidden" name="total_venta" id="total_venta">
                        </th> 
                      </tfoot>';
            break;

            /* =========================================================
             * LISTAR CABECERA
             * =========================================================*/
            case 'listar':
                $idarticulo = isset($_GET['idarticulo']) ? limpiarCadena($_GET['idarticulo']) : "";
                $rspta = $venta->listar($idarticulo);
                $data  = Array();

                while ($reg=$rspta->fetch_object()){
                    if($reg->tipo_comprobante=='Ticket'){
                        $url='../reportes/exTicket.php?id=';
                    }
                    else{
                        $url='../reportes/exFactura.php?id=';
                    }

                    $data[]=array(
                        "0"=>(($reg->estado=='Aceptado')
                                ? '<button class="btn btn-warning" onclick="mostrar('.$reg->idventa.')"><i class="fa fa-eye"></i></button>'.
                                  ' <button class="btn btn-danger" onclick="anular('.$reg->idventa.')"><i class="fa fa-close"></i></button>'
                                : '<button class="btn btn-warning" onclick="mostrar('.$reg->idventa.')"><i class="fa fa-eye"></i></button>'
                             ).
                             '<a target="_blank" href="'.$url.$reg->idventa.'">
                                <button class="btn btn-info"><i class="fa fa-file"></i></button>
                              </a>',
                        "1"=>$reg->fecha,
                        "2"=>$reg->cliente,
                        "3"=>$reg->usuario,
                        "4"=>$reg->tipo_comprobante,
                        "5"=>$reg->serie_comprobante.'-'.$reg->num_comprobante,
                        "6"=>$reg->total_venta,
                        "7"=>($reg->estado=='Aceptado')
                                ? '<span class="label bg-green">Aceptado</span>'
                                : '<span class="label bg-red">Anulado</span>'
                    );
                }
                $results = array(
                    "sEcho"=>1,
                    "iTotalRecords"=>count($data),
                    "iTotalDisplayRecords"=>count($data),
                    "aaData"=>$data
                );
                echo json_encode($results);
            break;

            /* =========================================================
             * SELECT CLIENTE
             * =========================================================*/
            case 'selectCliente':
                require_once "../modelos/Persona.php";
                $persona = new Persona();

                $rspta = $persona->listarC();

                while ($reg = $rspta->fetch_object())
                {
                    echo '<option value=' . $reg->idpersona . '>' . $reg->nombre . '</option>';
                }
            break;

            /* =========================================================
             * LISTAR ARTÍCULOS PARA VENTA
             *  - Solo stock > 0
             *  - Envía stockDisponible al onclick
             *  - Muestra stock en <span id="stock_disp_ID">
             * =========================================================*/
            case 'listarArticulosVenta':
                require_once "../modelos/Articulo.php";
                $articulo = new Articulo();

                $rspta = $articulo->listarActivosVenta();
                $data  = Array();

                while ($reg=$rspta->fetch_object()){

                    $stock = (int)$reg->stock;

                    // No mostramos artículos sin stock
                    if ($stock <= 0) {
                        continue;
                    }

                    $data[]=array(
                        // Pasamos el stock original como cuarto parámetro
                        "0"=>'<button class="btn btn-warning" onclick="agregarDetalle('
                                .$reg->idarticulo.',\''.$reg->nombre.'\',\''.$reg->precio_venta.'\','.$stock.')">
                                <span class="fa fa-plus"></span>
                             </button>',
                        "1"=>$reg->nombre,
                        "2"=>$reg->categoria,
                        "3"=>$reg->marca,
                        "4"=>$reg->codigo,
                        "5"=>'<span id="stock_disp_'.$reg->idarticulo.'">'.$stock.'</span>',
                        "6"=>$reg->precio_venta,
                        "7"=>"<img src='../files/articulos/".$reg->imagen."' height='50px' width='50px' >"
                    );
                }
                $results = array(
                    "sEcho"=>1,
                    "iTotalRecords"=>count($data),
                    "iTotalDisplayRecords"=>count($data),
                    "aaData"=>$data
                );
                echo json_encode($results);
            break;

            /* =========================================================
             * OBTENER CORRELATIVO (serie, número, IGV)
             * =========================================================*/
            case 'obtenerCorrelativo':

                header('Content-Type: application/json; charset=utf-8');

                $tipo = isset($_POST['tipo_comprobante'])
                        ? limpiarCadena($_POST['tipo_comprobante'])
                        : '';

                $resp = [
                    'success' => false,
                    'message' => '',
                    'serie'   => '',
                    'numero'  => '',
                    'impuesto'=> 0
                ];

                if ($tipo === '') {
                    $resp['message'] = 'Tipo de comprobante requerido.';
                    echo json_encode($resp);
                    break;
                }

                $row = $correlativo->obtenerActual($tipo);

                if (!$row) {
                    $resp['message'] = 'No hay serie configurada para este tipo de comprobante.';
                } else {
                    $resp['success']  = true;
                    $resp['serie']    = $row['serie_actual'];
                    $resp['numero']   = $row['numero_siguiente'];
                    $resp['impuesto'] = (float)$row['impuesto'];
                }

                echo json_encode($resp);
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
