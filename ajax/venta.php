<?php
// ajax/venta.php
ob_start();
if (strlen(session_id()) < 1) {
    session_start(); //Validamos si existe o no la sesión
}

if (!isset($_SESSION["nombre"])) {
    header("Location: ../vistas/login.html"); //Validamos el acceso solo a los usuarios logueados al sistema.
} else {
    //Validamos el acceso solo al usuario logueado y autorizado.
    if ($_SESSION['ventas'] == 1) {
        require_once "../modelos/Venta.php";
        require_once "../modelos/Correlativo.php";

        $venta        = new Venta();
        $correlativo  = new Correlativo();

        $idventa          = isset($_POST["idventa"]) ? limpiarCadena($_POST["idventa"]) : "";
        $idcliente        = isset($_POST["idcliente"]) ? limpiarCadena($_POST["idcliente"]) : "";
        $idusuario        = $_SESSION["idusuario"] ?? 0;
        $tipo_comprobante = isset($_POST["tipo_comprobante"]) ? limpiarCadena($_POST["tipo_comprobante"]) : "";
        $serie_comprobante = isset($_POST["serie_comprobante"]) ? limpiarCadena($_POST["serie_comprobante"]) : "";
        $num_comprobante  = isset($_POST["num_comprobante"]) ? limpiarCadena($_POST["num_comprobante"]) : "";
        $fecha_hora       = isset($_POST["fecha_hora"]) ? limpiarCadena($_POST["fecha_hora"]) : "";
        $impuesto         = isset($_POST["impuesto"]) ? limpiarCadena($_POST["impuesto"]) : "";
        $total_venta      = isset($_POST["total_venta"]) ? limpiarCadena($_POST["total_venta"]) : "";

        switch ($_GET["op"]) {
            /* =========================================================
             * GUARDAR / EDITAR
             * =========================================================*/
            case 'guardaryeditar':
                header('Content-Type: application/json; charset=utf-8');

                // Incluir Modelos necesarios
                require_once "../modelos/Persona.php";
                require_once "../modelos/Articulo.php";

                $personaModel = new Persona();
                $articuloModel = new Articulo();

                $resp = [
                    'success' => false,
                    'message' => '',
                    'errors'  => [],
                    'sunat_response' => null // Para devolver datos de facturación
                ];

                try {
                    // --- 1. Sanitizar entradas básicas ---
                    $idventa          = isset($_POST['idventa']) ? (int)$_POST['idventa'] : 0;
                    $idcliente        = isset($_POST['idcliente']) ? (int)$_POST['idcliente'] : 0;
                    $tipo_comprobante = isset($_POST['tipo_comprobante']) ? limpiarCadena($_POST['tipo_comprobante']) : '';
                    $serie_comprobante = isset($_POST['serie_comprobante']) ? limpiarCadena($_POST['serie_comprobante']) : '';
                    $num_comprobante  = isset($_POST['num_comprobante']) ? limpiarCadena($_POST['num_comprobante']) : '';
                    $fecha_hora       = isset($_POST['fecha_hora']) ? limpiarCadena($_POST['fecha_hora']) : '';
                    $impuesto         = isset($_POST['impuesto']) ? (float)$_POST['impuesto'] : 0.0;
                    $total_venta_post = isset($_POST['total_venta']) ? (float)$_POST['total_venta'] : 0.0;

                    $idusuario = isset($_SESSION['idusuario']) ? (int)$_SESSION['idusuario'] : 0;

                    $idarticulo   = $_POST['idarticulo']   ?? [];
                    $cantidad     = $_POST['cantidad']     ?? [];
                    $precio_venta = $_POST['precio_venta'] ?? [];
                    $descuento    = $_POST['descuento']    ?? [];

                    // --- 2. Validaciones básicas ---
                    if ($idcliente <= 0) $resp['errors']['idcliente'] = 'Debe seleccionar un cliente válido.';

                    $validTypes = ['Boleta', 'Factura', 'Ticket'];
                    if (!in_array($tipo_comprobante, $validTypes, true)) $resp['errors']['tipo_comprobante'] = 'Tipo de comprobante inválido.';

                    if ($tipo_comprobante !== 'Ticket') {
                        if ($serie_comprobante === '') $resp['errors']['serie_comprobante'] = 'La serie es obligatoria.';
                        if ($num_comprobante === '') $resp['errors']['num_comprobante'] = 'El número es obligatorio.';
                    }
                    if ($fecha_hora === '') $resp['errors']['fecha_hora'] = 'La fecha es obligatoria.';

                    // --- 3. Detalles y Totales ---
                    $n1 = count($idarticulo);
                    if ($n1 === 0) $resp['errors']['detalles'] = 'Debe agregar items.';

                    if (!empty($resp['errors'])) {
                        $resp['message'] = 'Errores de validación.';
                        echo json_encode($resp);
                        break;
                    }

                    // --- 4. Calcular Totales y Preparar Items para SUNAT ---
                    $total_gravada = 0;
                    $total_igv = 0;
                    $total_venta_calc = 0;
                    $items_api = [];

                    for ($i = 0; $i < $n1; $i++) {
                        $idart = (int)$idarticulo[$i];
                        $cant  = (int)$cantidad[$i];
                        $prec  = (float)$precio_venta[$i]; // Precio con IGV
                        $desc  = (float)$descuento[$i];

                        // Recalcular subtotal línea
                        $subtotal_con_igv = ($cant * $prec) - $desc;
                        if ($subtotal_con_igv < 0) $subtotal_con_igv = 0;

                        $total_venta_calc += $subtotal_con_igv;

                        // Cálculos SUNAT (Base Imponible)
                        // Asumimos IGV 18% incluido en el precio
                        $valor_unitario = $prec / 1.18;
                        $precio_unitario = $prec;

                        $igv_unitario = $prec - $valor_unitario;

                        // Totales por linea para API
                        $mtoBaseIgv = $valor_unitario * $cant; // Valor venta del item (sin igv)
                        $mtoIgvItem = $igv_unitario * $cant;   // IGV del item

                        // Ajuste por descuento (el descuento también afecta la base imponible y el IGV)
                        // El descuento viene en valor monetario global por línea con IGV incluido
                        if ($desc > 0) {
                            $factor = 1 - ($desc / ($cant * $prec)); // Factor de descuento
                            $mtoBaseIgv *= $factor;
                            $mtoIgvItem *= $factor;
                            // El precio unitario referencial se mantiene, pero los montos totales bajan
                        }

                        $total_gravada += $mtoBaseIgv;
                        $total_igv += $mtoIgvItem;

                        // Obtener código de producto
                        $artData = ejecutarConsultaSimpleFila("SELECT codigo, nombre FROM articulo WHERE idarticulo='$idart'");
                        $codProducto = ($artData) ? $artData['codigo'] : 'ART-' . $idart;
                        $descripcion = ($artData) ? $artData['nombre'] : 'Producto ' . $idart;

                        $items_api[] = [
                            "codProducto" => $codProducto,
                            "descripcion" => $descripcion,
                            "unidad" => "NIU", // Unidades
                            "tipoPrecio" => "01", // Precio con impuestos
                            "cantidad" => $cant,
                            "mtoBaseIgv" => round($mtoBaseIgv, 2),
                            "mtoValorUnitario" => round($valor_unitario, 2),
                            "mtoPrecioUnitario" => round($precio_unitario, 2),
                            "codeAfectAlt" => "10",
                            "codeAfect" => "1000",
                            "nameAfect" => "IGV",
                            "tipoAfect" => "VAT",
                            "igvPorcent" => 18,
                            "igv" => round($mtoIgvItem, 2),
                            // Opcional: si la API pide igvOpi (operaciones onerosas), es lo mismo
                        ];
                    }

                    // Ajuste de totales finales (redondeo puede causar diferencias de centavos)
                    $total_final_api = round($total_gravada + $total_igv, 2);

                    // --- 5. Insertar Venta en BD ---
                    if (empty($idventa)) {
                        $rspta = $venta->insertar(
                            $idcliente,
                            $idusuario,
                            $tipo_comprobante,
                            $serie_comprobante,
                            $num_comprobante,
                            $fecha_hora,
                            $impuesto,
                            $total_final_api,
                            $idarticulo,
                            $cantidad,
                            $precio_venta,
                            $descuento
                        );

                        if ($rspta == -1) {
                             $resp['success'] = false;
                             $resp['message'] = "No hay caja abierta. Por favor, abra la caja antes de realizar una venta.";
                        } elseif ($rspta > 0) {
                            $idventa = $rspta; // Capturar el ID retornado
                            $correlativo->incrementar($tipo_comprobante);
                            $resp['success'] = true;
                            $resp['message'] = 'Venta registrada.';

                            // --- 6. FACTURACIÓN ELECTRÓNICA (SISTEMA DUAL) ---
                            try {
                                require_once "../config/sunat_config.php";

                                // Determinar qué proveedores usar según el tipo de comprobante
                                $usarGreenter = false;
                                $usarNubefact = false;

                                if ($tipo_comprobante == 'Factura') {
                                    $proveedor = FACTURA_PROVEEDOR;
                                } elseif ($tipo_comprobante == 'Boleta') {
                                    $proveedor = BOLETA_PROVEEDOR;
                                } elseif ($tipo_comprobante == 'Ticket') {
                                    $proveedor = TICKET_PROVEEDOR;
                                } else {
                                    $proveedor = 'ninguno';
                                }

                                // Determinar proveedores activos
                                if ($proveedor == 'greenter' && USAR_GREENTER) {
                                    $usarGreenter = true;
                                } elseif ($proveedor == 'nubefact' && USAR_NUBEFACT) {
                                    $usarNubefact = true;
                                } elseif ($proveedor == 'ambos') {
                                    $usarGreenter = USAR_GREENTER;
                                    $usarNubefact = USAR_NUBEFACT;
                                }

                                // Inicializar respuestas
                                $resp['greenter_response'] = null;
                                $resp['nubefact_response'] = null;

                                // Obtener datos cliente
                                $clientData = $personaModel->mostrar($idcliente);

                                // =========================================
                                // GREENTER (Conexión directa SUNAT)
                                // =========================================
                                if ($usarGreenter && ($tipo_comprobante == 'Factura' || $tipo_comprobante == 'Boleta')) {
                                    if (!class_exists('SoapClient')) {
                                        throw new Exception("La clase SoapClient no está habilitada en PHP. No se puede conectar con SUNAT.");
                                    }

                                    require_once "../config/GreenterApi.php";

                                    // Preparar items para Greenter
                                    $items_greenter = [];
                                    $rsptaDetalles = $venta->listarDetalle($idventa);

                                    while ($regDet = $rsptaDetalles->fetch_object()) {
                                        $p_venta = (float)$regDet->precio_venta;
                                        $cant = (int)$regDet->cantidad;
                                        $v_unitario = $p_venta / 1.18;
                                        $base_igv = $v_unitario * $cant;
                                        $igv_item = ($p_venta * $cant) - $base_igv;

                                        $artData = ejecutarConsultaSimpleFila("SELECT codigo FROM articulo WHERE idarticulo='" . $regDet->idarticulo . "'");
                                        $codProducto = $artData ? $artData['codigo'] : 'ART' . $regDet->idarticulo;

                                        $items_greenter[] = [
                                            'codigo' => $codProducto,
                                            'descripcion' => $regDet->nombre,
                                            'cantidad' => $cant,
                                            'valor_unitario' => round($v_unitario, 6),
                                            'precio_unitario' => $p_venta,
                                            'base_igv' => round($base_igv, 2),
                                            'igv' => round($igv_item, 2)
                                        ];
                                    }

                                    $serieGreenter = ($tipo_comprobante == 'Factura') ? GREENTER_SERIE_FACTURA : GREENTER_SERIE_BOLETA;
                                    $total_letras = GreenterApi::numtoletras($total_final_api);

                                    $dataGreenter = [
                                        'tipo_comprobante' => $tipo_comprobante,
                                        'serie' => $serieGreenter,
                                        'numero' => $num_comprobante,
                                        'fecha' => $fecha_hora,
                                        'cliente' => $clientData['nombre'],
                                        'num_documento' => $clientData['num_documento'],
                                        'direccion' => $clientData['direccion'] ?: '-',
                                        'gravada' => round($total_gravada, 2),
                                        'igv' => round($total_igv, 2),
                                        'total' => round($total_final_api, 2),
                                        'total_letras' => $total_letras,
                                        'items' => $items_greenter
                                    ];

                                    try {
                                        $greenter = new GreenterApi();
                                        $resGreenter = $greenter->emitirComprobante($dataGreenter);

                                        $resp['greenter_response'] = [
                                            'exito' => $resGreenter['exito'],
                                            'mensaje' => $resGreenter['mensaje'],
                                            'xml' => !empty($resGreenter['xml_local']) ? '../' . $resGreenter['xml_local'] : '',
                                            'cdr' => !empty($resGreenter['cdr_local']) ? '../' . $resGreenter['cdr_local'] : '',
                                            'sunat_description' => $resGreenter['sunat_description'] ?? ''
                                        ];

                                        // Si Greenter tuvo éxito, actualizar BD
                                        if ($resGreenter['exito']) {
                                            $venta->actualizarEnlacesNubefact($idventa, '', $resGreenter['xml_local'], $resGreenter['cdr_local']);
                                        }
                                    } catch (Exception $greenterEx) {
                                        $resp['greenter_response'] = [
                                            'exito' => false,
                                            'mensaje' => 'Error Greenter: ' . $greenterEx->getMessage()
                                        ];
                                    }
                                }

                                // =========================================
                                // NUBEFACT (Servicio externo)
                                // =========================================
                                if ($usarNubefact) {
                                    require_once "../config/SunatApi.php";

                                    // Tipo documento NubeFact: 1=Factura, 2=Boleta, 4=Ticket
                                    $tipoDocNube = 2; // Por defecto Boleta
                                    if ($tipo_comprobante == 'Factura') $tipoDocNube = 1;
                                    if ($tipo_comprobante == 'Ticket') $tipoDocNube = 4;

                                    // Tipo documento cliente
                                    $tipoDocCli = '0';
                                    $numDocCli = $clientData['num_documento'];
                                    if (strlen($numDocCli) == 8) $tipoDocCli = '1';
                                    if (strlen($numDocCli) == 11) $tipoDocCli = '6';

                                    // Serie NubeFact
                                    if ($tipo_comprobante == 'Factura') {
                                        $serieNubefact = NUBEFACT_SERIE_FACTURA;
                                    } elseif ($tipo_comprobante == 'Boleta') {
                                        $serieNubefact = NUBEFACT_SERIE_BOLETA;
                                    } else {
                                        $serieNubefact = NUBEFACT_SERIE_TICKET;
                                    }

                                    // Preparar items NubeFact
                                    $items_nubefact = [];
                                    $rsptaDetalles2 = $venta->listarDetalle($idventa);

                                    while ($regDet = $rsptaDetalles2->fetch_object()) {
                                        $p_venta = (float)$regDet->precio_venta;
                                        $cant = (int)$regDet->cantidad;
                                        $v_unitario = $p_venta / 1.18;
                                        $total_item_sin_igv = $v_unitario * $cant;
                                        $igv_item_total = ($p_venta * $cant) - $total_item_sin_igv;

                                        $items_nubefact[] = [
                                            "unidad_de_medida" => "NIU",
                                            "codigo" => "COD" . $regDet->idarticulo,
                                            "descripcion" => $regDet->nombre,
                                            "cantidad" => $cant,
                                            "valor_unitario" => round($v_unitario, 10),
                                            "precio_unitario" => $p_venta,
                                            "descuento" => "",
                                            "subtotal" => round($total_item_sin_igv, 2),
                                            "tipo_de_igv" => 1,
                                            "igv" => round($igv_item_total, 2),
                                            "total" => round($p_venta * $cant, 2),
                                            "anticipo_regularizacion" => "false"
                                        ];
                                    }

                                    $json_nubefact = [
                                        "operacion" => "generar_comprobante",
                                        "tipo_de_comprobante" => $tipoDocNube,
                                        "serie" => $serieNubefact,
                                        "sunat_transaction" => 1,
                                        "cliente_tipo_de_documento" => $tipoDocCli,
                                        "cliente_numero_de_documento" => $numDocCli,
                                        "cliente_denominacion" => $clientData['nombre'],
                                        "cliente_direccion" => $clientData['direccion'] ?: "-",
                                        "cliente_email" => $clientData['email'] ?: "",
                                        "fecha_de_emision" => date('d-m-Y', strtotime($fecha_hora)),
                                        "moneda" => 1,
                                        "porcentaje_de_igv" => 18.00,
                                        "total_gravada" => round($total_gravada, 2),
                                        "total_igv" => round($total_igv, 2),
                                        "total" => round($total_final_api, 2),
                                        "enviar_automaticamente_a_la_sunat" => "true",
                                        "enviar_automaticamente_al_cliente" => "false",
                                        "codigo_unico" => "V-" . $idventa,
                                        "condiciones_de_pago" => "CONTADO",
                                        "items" => $items_nubefact
                                    ];

                                    try {
                                        $sunat = new SunatApi();
                                        $resApi = $sunat->emitirComprobante($json_nubefact);
                                        $resp_decode = json_decode($resApi['response'], true);

                                        if ($resApi['status'] == 200 || $resApi['status'] == 201) {
                                            if (isset($resp_decode['errors'])) {
                                                $resp['nubefact_response'] = [
                                                    'exito' => false,
                                                    'mensaje' => 'Error NubeFact: ' . $resp_decode['errors']
                                                ];
                                            } else {
                                                $resp['nubefact_response'] = [
                                                    'exito' => true,
                                                    'mensaje' => 'Comprobante enviado a NubeFact',
                                                    'pdf' => $resp_decode['enlace_del_pdf'] ?? '',
                                                    'xml' => $resp_decode['enlace_del_xml'] ?? '',
                                                    'cdr' => $resp_decode['enlace_del_cdr'] ?? '',
                                                    'serie' => $resp_decode['serie'] ?? $serieNubefact,
                                                    'numero' => $resp_decode['numero'] ?? ''
                                                ];

                                                // Si no se usó Greenter, actualizar BD con NubeFact
                                                if (!$usarGreenter || empty($resp['greenter_response']['exito'])) {
                                                    $venta->actualizarEnlacesNubefact(
                                                        $idventa,
                                                        $resp_decode['enlace_del_pdf'] ?? '',
                                                        '', // xml_local (Greenter)
                                                        '', // cdr_local (Greenter)
                                                        $resp_decode['enlace_del_xml'] ?? '',
                                                        $resp_decode['enlace_del_cdr'] ?? ''
                                                    );
                                                } else {
                                                    // Si Greenter tuvo éxito, solo guardar enlaces NubeFact adicionales
                                                    $venta->actualizarEnlacesNubefact(
                                                        $idventa,
                                                        $resp_decode['enlace_del_pdf'] ?? '',
                                                        '',
                                                        '', // xml/cdr local ya guardados por Greenter
                                                        $resp_decode['enlace_del_xml'] ?? '',
                                                        $resp_decode['enlace_del_cdr'] ?? ''
                                                    );
                                                }
                                            }
                                        } else {
                                            $msg = $resp_decode['errors'] ?? $resApi['response'];
                                            $resp['nubefact_response'] = [
                                                'exito' => false,
                                                'mensaje' => 'Error NubeFact (' . $resApi['status'] . '): ' . $msg
                                            ];
                                        }
                                    } catch (Exception $nubefactEx) {
                                        $resp['nubefact_response'] = [
                                            'exito' => false,
                                            'mensaje' => 'Error NubeFact: ' . $nubefactEx->getMessage()
                                        ];
                                    }
                                }

                                // =========================================
                                // CONSOLIDAR RESPUESTA
                                // =========================================
                                $greenterOk = isset($resp['greenter_response']['exito']) && $resp['greenter_response']['exito'];
                                $nubefactOk = isset($resp['nubefact_response']['exito']) && $resp['nubefact_response']['exito'];

                                if ($greenterOk || $nubefactOk) {
                                    $mensajes = [];
                                    if ($greenterOk) $mensajes[] = 'Greenter: ' . ($resp['greenter_response']['sunat_description'] ?? 'OK');
                                    if ($nubefactOk) $mensajes[] = 'NubeFact: OK';

                                    $resp['sunat_response'] = [
                                        'exito' => true,
                                        'mensaje' => implode(' | ', $mensajes),
                                        'pdf' => $nubefactOk ? $resp['nubefact_response']['pdf'] : '../reportes/exFactura.php?id=' . $idventa,
                                        'xml' => $greenterOk ? $resp['greenter_response']['xml'] : ($nubefactOk ? $resp['nubefact_response']['xml'] : ''),
                                        'cdr' => $greenterOk ? $resp['greenter_response']['cdr'] : ($nubefactOk ? $resp['nubefact_response']['cdr'] : ''),
                                        'pdf_local' => '../reportes/exFactura.php?id=' . $idventa,
                                        // Datos específicos de cada proveedor
                                        'greenter' => $resp['greenter_response'],
                                        'nubefact' => $resp['nubefact_response']
                                    ];
                                } else if ($usarGreenter || $usarNubefact) {
                                    // Ambos fallaron
                                    $errores = [];
                                    if (isset($resp['greenter_response']['mensaje'])) $errores[] = $resp['greenter_response']['mensaje'];
                                    if (isset($resp['nubefact_response']['mensaje'])) $errores[] = $resp['nubefact_response']['mensaje'];

                                    $resp['sunat_response'] = [
                                        'exito' => false,
                                        'mensaje' => implode(' | ', $errores),
                                        'pdf_local' => '../reportes/exFactura.php?id=' . $idventa,
                                        'greenter' => $resp['greenter_response'],
                                        'nubefact' => $resp['nubefact_response']
                                    ];
                                }
                            } catch (Throwable $eInvoicing) {
                                // Error CRÍTICO en facturación, pero VENTA YA GUARDADA
                                $resp['sunat_response'] = [
                                    'exito' => false,
                                    'mensaje' => 'Venta guardada internamente, pero falló la facturación: ' . $eInvoicing->getMessage(),
                                    'pdf_local' => '../reportes/exFactura.php?id=' . $idventa,
                                ];
                                error_log("Error Facturación: " . $eInvoicing->getMessage() . "\n" . $eInvoicing->getTraceAsString(), 3, "venta_fact_error.log");
                            }
                            // Si no se usa ningún proveedor, no hay sunat_response
                        } else { // Cierre de if ($rspta > 0)
                            $resp['message'] = 'Error al registrar en BD.';
                        }
                    } else {
                        $resp['message'] = 'Edición no soportada.';
                    }
                } catch (Throwable $e) {
                    $resp['success'] = false;
                    $resp['message'] = "Error del Servidor: " . $e->getMessage();
                    error_log("Error en ajax/venta.php: " . $e->getMessage() . "\nStack: " . $e->getTraceAsString(), 3, "venta_error.log");
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
                $total = 0;
                echo '<thead style="background-color:#A9D0F5">
                        <th>Opciones</th>
                        <th>Artículo</th>
                        <th>Cantidad</th>
                        <th>Precio Venta</th>
                        <th>Descuento</th>
                        <th>Subtotal</th>
                      </thead>';

                while ($reg = $rspta->fetch_object()) {
                    echo '<tr class="filas">
                            <td></td>
                            <td>' . $reg->nombre . '</td>
                            <td>' . $reg->cantidad . '</td>
                            <td>' . $reg->precio_venta . '</td>
                            <td>' . $reg->descuento . '</td>
                            <td>' . $reg->subtotal . '</td>
                          </tr>';
                    $total = $total + ($reg->precio_venta * $reg->cantidad - $reg->descuento);
                }
                echo '<tfoot>
                        <th>TOTAL</th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th></th>
                        <th><h4 id="total">S/.' . $total . '</h4>
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
                $data  = array();

                while ($reg = $rspta->fetch_object()) {
                    if ($reg->tipo_comprobante == 'Ticket') {
                        $url = '../reportes/exTicket.php?id=';
                    } else {
                        $url = '../reportes/exFactura.php?id=';
                    }

                    // Botones base (Ver y Anular)
                    $botones = '';
                    if ($reg->estado == 'Aceptado') {
                        $botones .= '<button class="btn btn-warning btn-sm" onclick="mostrar(' . $reg->idventa . ')" title="Ver Detalle"><i class="fa fa-eye"></i></button> ';
                        $botones .= '<button class="btn btn-danger btn-sm" onclick="anular(' . $reg->idventa . ')" title="Anular"><i class="fa fa-close"></i></button> ';
                    } else {
                        $botones .= '<button class="btn btn-warning btn-sm" onclick="mostrar(' . $reg->idventa . ')" title="Ver Detalle"><i class="fa fa-eye"></i></button> ';
                    }

                    // Botón PDF Local (siempre disponible)
                    $botones .= '<a target="_blank" href="' . $url . $reg->idventa . '" title="Ver PDF Local"><button class="btn btn-secondary btn-sm" style="background:#6b7280;border:none;color:#fff;"><i class="fa fa-file-pdf-o"></i></button></a> ';

                    // ===== DOCUMENTOS NUBEFACT (Nube) =====
                    if (($reg->tipo_comprobante == 'Factura' || $reg->tipo_comprobante == 'Boleta' || $reg->tipo_comprobante == 'Ticket')) {
                        // PDF NubeFact
                        if (!empty($reg->pdf_nubefact)) {
                            $botones .= '<a target="_blank" href="' . htmlspecialchars($reg->pdf_nubefact) . '" title="PDF NubeFact"><button class="btn btn-sm" style="background:#dc2626;border:none;color:#fff;font-size:10px;"><i class="fa fa-cloud"></i> PDF</button></a> ';
                        }
                        // XML NubeFact
                        if (!empty($reg->xml_nubefact)) {
                            $botones .= '<a target="_blank" href="' . htmlspecialchars($reg->xml_nubefact) . '" title="XML NubeFact"><button class="btn btn-sm" style="background:#1e40af;border:none;color:#fff;font-size:10px;"><i class="fa fa-cloud"></i> XML</button></a> ';
                        }
                        // CDR NubeFact
                        if (!empty($reg->cdr_nubefact)) {
                            $botones .= '<a href="' . htmlspecialchars($reg->cdr_nubefact) . '" title="CDR NubeFact" download><button class="btn btn-sm" style="background:#0d9488;border:none;color:#fff;font-size:10px;"><i class="fa fa-cloud"></i> CDR</button></a> ';
                        }
                    }

                    // ===== DOCUMENTOS GREENTER (Local - SUNAT Directo) =====
                    // XML Local (Greenter)
                    if (!empty($reg->xml_local)) {
                        $botones .= '<a target="_blank" href="../' . htmlspecialchars($reg->xml_local) . '" title="XML Greenter (Local)"><button class="btn btn-sm" style="background:#2563eb;border:none;color:#fff;font-size:10px;"><i class="fa fa-file-code-o"></i> XML</button></a> ';
                    }

                    // CDR Local (Greenter)
                    if (!empty($reg->cdr_local)) {
                        $botones .= '<a href="../' . htmlspecialchars($reg->cdr_local) . '" title="CDR Greenter (Local)" download><button class="btn btn-sm" style="background:#059669;border:none;color:#fff;font-size:10px;"><i class="fa fa-file-archive-o"></i> CDR</button></a> ';
                    }

                    $data[] = array(
                        "0" => $botones,
                        "1" => $reg->fecha,
                        "2" => $reg->cliente,
                        "3" => $reg->usuario,
                        "4" => $reg->tipo_comprobante,
                        "5" => $reg->serie_comprobante . '-' . $reg->num_comprobante,
                        "6" => $reg->total_venta,
                        "7" => ($reg->estado == 'Aceptado')
                            ? '<span class="label bg-green">Aceptado</span>'
                            : '<span class="label bg-red">Anulado</span>'
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

            /* =========================================================
             * SELECT CLIENTE
             * =========================================================*/
            case 'selectCliente':
                require_once "../modelos/Persona.php";
                $persona = new Persona();

                $rspta = $persona->listarC();

                while ($reg = $rspta->fetch_object()) {
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
                $data  = array();

                while ($reg = $rspta->fetch_object()) {

                    $stock = (int)$reg->stock;

                    // No mostramos artículos sin stock
                    if ($stock <= 0) {
                        continue;
                    }

                    $data[] = array(
                        // Pasamos el stock original como cuarto parámetro
                        "0" => '<button class="btn btn-warning" onclick="agregarDetalle('
                            . $reg->idarticulo . ',\'' . $reg->nombre . '\',\'' . $reg->precio_venta . '\',' . $stock . ')">
                                <span class="fa fa-plus"></span>
                             </button>',
                        "1" => $reg->nombre,
                        "2" => $reg->categoria,
                        "3" => $reg->marca,
                        "4" => $reg->codigo,
                        "5" => '<span id="stock_disp_' . $reg->idarticulo . '">' . $stock . '</span>',
                        "6" => $reg->precio_venta,
                        "7" => "<img src='../files/articulos/" . $reg->imagen . "' height='50px' width='50px' >"
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
                    'impuesto' => 0
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

            case 'kpi_detalle':
                header('Content-Type: application/json; charset=utf-8');
                require_once "../config/Conexion.php";
                $tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
                $result = array('success' => true, 'tipo' => $tipo, 'titulo' => '', 'descripcion' => '', 'datos' => array(), 'columnas' => array());

                switch ($tipo) {
                    case 'hoy':
                        $result['titulo'] = 'Ventas de Hoy';
                        $result['descripcion'] = 'Detalle de ventas registradas hoy';
                        $sql = "SELECT DATE_FORMAT(v.fecha_hora, '%H:%i') as hora, 
                                       p.nombre as cliente, v.tipo_comprobante, 
                                       CONCAT(v.serie_comprobante, '-', v.num_comprobante) as comprobante,
                                       v.total_venta
                                FROM venta v
                                LEFT JOIN persona p ON v.idcliente = p.idpersona
                                WHERE DATE(v.fecha_hora) = CURDATE() AND v.estado = 'Aceptado'
                                ORDER BY v.fecha_hora DESC";
                        $rspta = ejecutarConsulta($sql);
                        while ($reg = $rspta->fetch_object()) {
                            $result['datos'][] = array(
                                'hora' => $reg->hora,
                                'cliente' => $reg->cliente ? $reg->cliente : 'Cliente',
                                'tipo' => $reg->tipo_comprobante,
                                'comprobante' => $reg->comprobante,
                                'total' => 'S/ ' . number_format($reg->total_venta, 2)
                            );
                        }
                        $result['columnas'] = ['Hora', 'Cliente', 'Tipo', 'Comprobante', 'Total'];
                        break;

                    case 'mes':
                        $result['titulo'] = 'Ventas del Mes';
                        $result['descripcion'] = 'Evolución diaria de ventas del mes actual';
                        $sql = "SELECT DATE_FORMAT(v.fecha_hora, '%d/%m') as dia, 
                                       COUNT(v.idventa) as cantidad, SUM(v.total_venta) as total
                                FROM venta v
                                WHERE MONTH(v.fecha_hora) = MONTH(CURDATE()) AND YEAR(v.fecha_hora) = YEAR(CURDATE()) AND v.estado = 'Aceptado'
                                GROUP BY DATE(v.fecha_hora)
                                ORDER BY v.fecha_hora DESC";
                        $rspta = ejecutarConsulta($sql);
                        while ($reg = $rspta->fetch_object()) {
                            $result['datos'][] = array(
                                'dia' => $reg->dia,
                                'cantidad' => (int)$reg->cantidad,
                                'total' => 'S/ ' . number_format($reg->total, 2)
                            );
                        }
                        $result['columnas'] = ['Día', 'Ventas', 'Total'];
                        break;

                    case 'ticket':
                        $result['titulo'] = 'Detalle de Tickets';
                        $result['descripcion'] = 'Últimas 30 ventas para análisis de ticket promedio';
                        $sql = "SELECT DATE_FORMAT(v.fecha_hora, '%d/%m/%Y %H:%i') as fecha, 
                                       p.nombre as cliente, v.total_venta
                                FROM venta v
                                LEFT JOIN persona p ON v.idcliente = p.idpersona
                                WHERE v.estado = 'Aceptado' AND MONTH(v.fecha_hora) = MONTH(CURDATE())
                                ORDER BY v.fecha_hora DESC LIMIT 30";
                        $rspta = ejecutarConsulta($sql);
                        while ($reg = $rspta->fetch_object()) {
                            $result['datos'][] = array(
                                'fecha' => $reg->fecha,
                                'cliente' => $reg->cliente ? $reg->cliente : 'Cliente',
                                'total' => 'S/ ' . number_format($reg->total_venta, 2)
                            );
                        }
                        $result['columnas'] = ['Fecha', 'Cliente', 'Total'];
                        break;

                    case 'tasa':
                        $result['titulo'] = 'Estado de Ventas del Mes';
                        $result['descripcion'] = 'Comparativa de ventas aceptadas vs anuladas';
                        $sql = "SELECT v.estado, COUNT(v.idventa) as cantidad, IFNULL(SUM(v.total_venta), 0) as total
                                FROM venta v
                                WHERE MONTH(v.fecha_hora) = MONTH(CURDATE()) AND YEAR(v.fecha_hora) = YEAR(CURDATE())
                                GROUP BY v.estado";
                        $rspta = ejecutarConsulta($sql);
                        while ($reg = $rspta->fetch_object()) {
                            $result['datos'][] = array(
                                'estado' => $reg->estado,
                                'cantidad' => (int)$reg->cantidad,
                                'total' => 'S/ ' . number_format($reg->total, 2)
                            );
                        }
                        $result['columnas'] = ['Estado', 'Cantidad', 'Total'];
                        break;

                    case 'top':
                        $result['titulo'] = 'Top Clientes del Mes';
                        $result['descripcion'] = 'Clientes con mayor facturación en el mes actual';
                        $sql = "SELECT p.nombre as cliente, COUNT(v.idventa) as compras, SUM(v.total_venta) as total
                                FROM venta v
                                JOIN persona p ON v.idcliente = p.idpersona
                                WHERE MONTH(v.fecha_hora) = MONTH(CURDATE()) AND YEAR(v.fecha_hora) = YEAR(CURDATE()) AND v.estado = 'Aceptado'
                                GROUP BY v.idcliente
                                ORDER BY total DESC LIMIT 15";
                        $rspta = ejecutarConsulta($sql);
                        while ($reg = $rspta->fetch_object()) {
                            $result['datos'][] = array(
                                'cliente' => $reg->cliente,
                                'compras' => (int)$reg->compras,
                                'total' => 'S/ ' . number_format($reg->total, 2)
                            );
                        }
                        $result['columnas'] = ['Cliente', 'Compras', 'Total'];
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
