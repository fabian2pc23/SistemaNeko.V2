<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Articulo;

class CheckoutController extends Controller
{
    /**
     * Mostrar página de checkout
     */
    public function index()
    {
        // Verificar que haya items en el carrito
        $items = $_SESSION['cart'] ?? [];

        if (empty($items)) {
            header('Location: ' . BASE_URL . '/tienda');
            exit;
        }

        // Calcular totales
        $subtotal = 0;
        $cartItems = [];

        foreach ($items as $id => $qty) {
            $producto = Articulo::find($id);
            if ($producto) {
                $itemSubtotal = $producto['precio_venta'] * $qty;
                $subtotal += $itemSubtotal;

                $cartItems[] = [
                    'producto' => $producto,
                    'cantidad' => $qty,
                    'subtotal' => $itemSubtotal
                ];
            }
        }

        // Calcular IGV (18% en Perú)
        $igv = $subtotal * 0.18;
        $total = $subtotal + $igv;

        // Obtener configuración de pasarela de pago
        require_once __DIR__ . '/../../config/PaymentConfig.php';
        $culqiPublicKey = \App\Config\PaymentConfig::getCulqiCredentials()['public_key'];
        $isSandbox = \App\Config\PaymentConfig::isSandbox();

        // Datos para la vista
        $data = [
            'items' => $cartItems,
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => $total,
            'cantidadItems' => count($items),
            'includeCheckoutJS' => true,
            'culqiPublicKey' => $culqiPublicKey,
            'isSandbox' => $isSandbox
        ];

        $this->view('checkout/index', $data);
    }

    /**
     * Procesar el pago
     */
    public function procesarPago()
    {
        // Iniciar buffer de salida para capturar cualquier output no deseado
        ob_start();

        // Asegurar que siempre se devuelva JSON
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Método no permitido'
            ]);
            exit;
        }

        try {
            // Validar datos del formulario
            $nombre = trim($_POST['nombre'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');
            $metodoPago = $_POST['metodo_pago'] ?? '';

            // Validaciones básicas
            if (empty($nombre) || empty($email) || empty($telefono) || empty($direccion)) {
                throw new \Exception('Por favor complete todos los campos requeridos');
            }

            if (!in_array($metodoPago, ['yape', 'tarjeta'])) {
                throw new \Exception('Método de pago no válido');
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Email no válido');
            }

            // Obtener items del carrito
            $items = $_SESSION['cart'] ?? [];
            if (empty($items)) {
                throw new \Exception('El carrito está vacío');
            }

            // Calcular totales
            $subtotal = 0;
            $detalles = [];

            foreach ($items as $id => $qty) {
                $producto = Articulo::find($id);
                if ($producto) {
                    // Verificar stock
                    if ($producto['stock'] < $qty) {
                        throw new \Exception('Stock insuficiente para ' . $producto['nombre']);
                    }

                    $itemSubtotal = $producto['precio_venta'] * $qty;
                    $subtotal += $itemSubtotal;

                    $detalles[] = [
                        'idarticulo' => $id,
                        'cantidad' => $qty,
                        'precio_unitario' => $producto['precio_venta'],
                        'subtotal' => $itemSubtotal
                    ];
                }
            }

            if (empty($detalles)) {
                throw new \Exception('No hay productos válidos en el carrito');
            }

            $igv = $subtotal * 0.18;
            $total = $subtotal + $igv;

            // Registrar pedido en base de datos
            $db = \App\Core\Database::getConnection();

            // Generar código de pedido único
            $codigoPedido = 'PED-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Insertar pedido
            $stmtPedido = $db->prepare("
                INSERT INTO pedido_online (
                    codigo_pedido, nombre_cliente, email_cliente, telefono_cliente,
                    direccion_entrega, subtotal, igv, total, metodo_pago,
                    notas_cliente, ip_cliente, user_agent, fecha_pedido
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $notas = $_POST['notas'] ?? null;
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            $stmtPedido->execute([
                $codigoPedido,
                $nombre,
                $email,
                $telefono,
                $direccion,
                $subtotal,
                $igv,
                $total,
                $metodoPago,
                $notas,
                $ip,
                $userAgent
            ]);

            $idPedido = $db->lastInsertId();

            if (!$idPedido) {
                throw new \Exception('Error al crear el pedido en la base de datos');
            }

            // Insertar detalles del pedido
            $stmtDetalle = $db->prepare("
                INSERT INTO detalle_pedido_online (idpedido, idarticulo, cantidad, precio_unitario, subtotal) 
                VALUES (?, ?, ?, ?, ?)
            ");

            foreach ($detalles as $detalle) {
                $stmtDetalle->execute([
                    $idPedido,
                    $detalle['idarticulo'],
                    $detalle['cantidad'],
                    $detalle['precio_unitario'],
                    $detalle['subtotal']
                ]);
            }

            // Guardar datos en sesión para el proceso de pago
            $_SESSION['checkout_data'] = [
                'idpedido' => $idPedido,
                'codigo_pedido' => $codigoPedido,
                'total' => $total,
                'metodo_pago' => $metodoPago,
                'nombre' => $nombre,
                'email' => $email,
                'telefono' => $telefono
            ];

            // Limpiar buffer y retornar respuesta JSON
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'idpedido' => $idPedido,
                'codigo_pedido' => $codigoPedido,
                'metodo_pago' => $metodoPago,
                'total' => $total
            ]);
        } catch (\Exception $e) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Procesar pago con Yape (simulado)
     */
    public function procesarYape()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }

        try {
            $checkoutData = $_SESSION['checkout_data'] ?? null;

            if (!$checkoutData) {
                throw new \Exception('Sesión expirada. Por favor intente nuevamente.');
            }

            $numeroOperacion = trim($_POST['numero_operacion'] ?? '');
            $telefonoOrigen = trim($_POST['telefono_yape'] ?? '');

            if (empty($numeroOperacion) || empty($telefonoOrigen)) {
                throw new \Exception('Complete todos los campos de Yape');
            }

            // Validar formato de número de operación (simulación)
            if (strlen($numeroOperacion) < 6) {
                throw new \Exception('Número de operación no válido');
            }

            $db = \App\Core\Database::getConnection();

            // Generar código de transacción único
            $codigoTransaccion = 'YAPE-' . date('YmdHis') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);

            // Insertar transacción
            $stmt = $db->prepare("
                INSERT INTO transaccion_pago (idpedido, metodo_pago, codigo_transaccion, monto, estado, fecha_procesado) 
                VALUES (?, 'yape', ?, ?, 'aprobado', NOW())
            ");

            $stmt->execute([
                $checkoutData['idpedido'],
                $codigoTransaccion,
                $checkoutData['total']
            ]);

            $idTransaccion = $db->lastInsertId();

            // Insertar datos de Yape
            $stmtYape = $db->prepare("
                INSERT INTO pago_yape_simulado (idtransaccion, numero_operacion, telefono_origen, nombre_pagador, fecha_operacion) 
                VALUES (?, ?, ?, ?, NOW())
            ");

            $stmtYape->execute([
                $idTransaccion,
                $numeroOperacion,
                $telefonoOrigen,
                $checkoutData['nombre']
            ]);

            // Actualizar estado del pedido
            $stmtUpdate = $db->prepare("UPDATE pedido_online SET estado_pago = 'pagado', fecha_pago = NOW() WHERE idpedido = ?");
            $stmtUpdate->execute([$checkoutData['idpedido']]);

            // Reducir stock de productos
            foreach ($_SESSION['cart'] as $id => $qty) {
                $stmtStock = $db->prepare("UPDATE articulo SET stock = stock - ? WHERE idarticulo = ?");
                $stmtStock->execute([$qty, $id]);
            }

            // Limpiar carrito
            unset($_SESSION['cart']);

            echo json_encode([
                'success' => true,
                'codigo_pedido' => $checkoutData['codigo_pedido'],
                'codigo_transaccion' => $codigoTransaccion,
                'mensaje' => '¡Pago exitoso con Yape!'
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Procesar pago con tarjeta (simulado)
     */
    public function procesarTarjeta()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }

        try {
            $checkoutData = $_SESSION['checkout_data'] ?? null;

            if (!$checkoutData) {
                throw new \Exception('Sesión expirada. Por favor intente nuevamente.');
            }

            $numeroTarjeta = str_replace(' ', '', $_POST['numero_tarjeta'] ?? '');
            $nombreTitular = trim($_POST['nombre_titular'] ?? '');
            $fechaExp = trim($_POST['fecha_expiracion'] ?? '');
            $cvv = trim($_POST['cvv'] ?? '');

            if (empty($numeroTarjeta) || empty($nombreTitular) || empty($fechaExp) || empty($cvv)) {
                throw new \Exception('Complete todos los campos de la tarjeta');
            }

            // Validaciones básicas (simulación)
            if (strlen($numeroTarjeta) < 15 || strlen($numeroTarjeta) > 16) {
                throw new \Exception('Número de tarjeta no válido');
            }

            if (strlen($cvv) < 3 || strlen($cvv) > 4) {
                throw new \Exception('CVV no válido');
            }

            // Determinar tipo de tarjeta
            $primerDigito = substr($numeroTarjeta, 0, 1);
            $tipoTarjeta = 'visa'; // Por defecto

            if ($primerDigito == '4') {
                $tipoTarjeta = 'visa';
            } elseif ($primerDigito == '5') {
                $tipoTarjeta = 'mastercard';
            } elseif ($primerDigito == '3') {
                $tipoTarjeta = 'amex';
            }

            // Obtener últimos 4 dígitos
            $ultimosDigitos = substr($numeroTarjeta, -4);

            $db = \App\Core\Database::getConnection();

            // Generar código de transacción único
            $codigoTransaccion = 'CARD-' . date('YmdHis') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);

            // Generar código de autorización
            $codigoAutorizacion = strtoupper(substr(md5(uniqid()), 0, 8));

            // Insertar transacción
            $stmt = $db->prepare("
                INSERT INTO transaccion_pago (idpedido, metodo_pago, codigo_transaccion, monto, estado, fecha_procesado, mensaje_respuesta) 
                VALUES (?, 'tarjeta', ?, ?, 'aprobado', NOW(), ?)
            ");

            $stmt->execute([
                $checkoutData['idpedido'],
                $codigoTransaccion,
                $checkoutData['total'],
                'Aprobado - Autorización: ' . $codigoAutorizacion
            ]);

            $idTransaccion = $db->lastInsertId();

            // Insertar datos de tarjeta (solo últimos 4 dígitos)
            $stmtTarjeta = $db->prepare("
                INSERT INTO pago_tarjeta_simulado (idtransaccion, ultimos_digitos, tipo_tarjeta, nombre_titular, fecha_expiracion, codigo_autorizacion) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmtTarjeta->execute([
                $idTransaccion,
                $ultimosDigitos,
                $tipoTarjeta,
                $nombreTitular,
                $fechaExp,
                $codigoAutorizacion
            ]);

            // Actualizar estado del pedido
            $stmtUpdate = $db->prepare("UPDATE pedido_online SET estado_pago = 'pagado', fecha_pago = NOW() WHERE idpedido = ?");
            $stmtUpdate->execute([$checkoutData['idpedido']]);

            // Reducir stock de productos
            foreach ($_SESSION['cart'] as $id => $qty) {
                $stmtStock = $db->prepare("UPDATE articulo SET stock = stock - ? WHERE idarticulo = ?");
                $stmtStock->execute([$qty, $id]);
            }

            // Limpiar carrito
            unset($_SESSION['cart']);

            echo json_encode([
                'success' => true,
                'codigo_pedido' => $checkoutData['codigo_pedido'],
                'codigo_transaccion' => $codigoTransaccion,
                'codigo_autorizacion' => $codigoAutorizacion,
                'mensaje' => '¡Pago exitoso con tarjeta!'
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Procesar pago con Culqi (Pasarela Real)
     * Recibe un token generado por Culqi.js en el frontend
     */
    public function procesarCulqi()
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Método no permitido']);
            exit;
        }

        try {
            $checkoutData = $_SESSION['checkout_data'] ?? null;

            if (!$checkoutData) {
                throw new \Exception('Sesión expirada. Por favor intente nuevamente.');
            }

            // Obtener token de Culqi del frontend
            $input = json_decode(file_get_contents('php://input'), true);
            $culqiToken = $input['token'] ?? $_POST['culqi_token'] ?? '';
            $email = $checkoutData['email'] ?? '';

            if (empty($culqiToken)) {
                throw new \Exception('Token de pago no recibido');
            }

            // Cargar servicio de Culqi
            require_once __DIR__ . '/../Services/CulqiGateway.php';
            $culqi = new \App\Services\CulqiGateway();

            // Crear cargo en Culqi
            $result = $culqi->createCharge(
                $culqiToken,
                $checkoutData['total'],
                $email,
                'Pedido ' . $checkoutData['codigo_pedido'] . ' - NEKO SAC Store',
                [
                    'pedido' => $checkoutData['codigo_pedido'],
                    'cliente' => $checkoutData['nombre']
                ]
            );

            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Error al procesar el pago con tarjeta');
            }

            $chargeData = $result['data'];
            $chargeId = $chargeData['id'] ?? '';
            $codigoAutorizacion = $chargeData['authorization_code'] ?? strtoupper(substr(md5(uniqid()), 0, 8));

            $db = \App\Core\Database::getConnection();

            // Guardar transacción exitosa
            $stmt = $db->prepare("
                INSERT INTO transaccion_pago (idpedido, metodo_pago, codigo_transaccion, monto, estado, fecha_procesado, mensaje_respuesta) 
                VALUES (?, 'tarjeta', ?, ?, 'aprobado', NOW(), ?)
            ");

            $stmt->execute([
                $checkoutData['idpedido'],
                $chargeId,
                $checkoutData['total'],
                'Culqi - Cargo aprobado: ' . ($chargeData['outcome']['user_message'] ?? 'OK')
            ]);

            $idTransaccion = $db->lastInsertId();

            // Guardar detalles de tarjeta (solo últimos 4 dígitos)
            $cardInfo = $chargeData['source'] ?? [];
            $stmtTarjeta = $db->prepare("
                INSERT INTO pago_tarjeta_simulado (idtransaccion, ultimos_digitos, tipo_tarjeta, nombre_titular, fecha_expiracion, codigo_autorizacion) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmtTarjeta->execute([
                $idTransaccion,
                $cardInfo['last_four'] ?? '****',
                strtolower($cardInfo['iin']['card_brand'] ?? 'visa'),
                $cardInfo['card_holder']['name'] ?? $checkoutData['nombre'],
                '',
                $codigoAutorizacion
            ]);

            // Actualizar estado del pedido
            $stmtUpdate = $db->prepare("UPDATE pedido_online SET estado_pago = 'pagado', fecha_pago = NOW() WHERE idpedido = ?");
            $stmtUpdate->execute([$checkoutData['idpedido']]);

            // Reducir stock de productos
            if (!empty($_SESSION['cart'])) {
                foreach ($_SESSION['cart'] as $id => $qty) {
                    $stmtStock = $db->prepare("UPDATE articulo SET stock = stock - ? WHERE idarticulo = ?");
                    $stmtStock->execute([$qty, $id]);
                }
            }

            // Limpiar carrito
            unset($_SESSION['cart']);

            echo json_encode([
                'success' => true,
                'codigo_pedido' => $checkoutData['codigo_pedido'],
                'codigo_transaccion' => $chargeId,
                'codigo_autorizacion' => $codigoAutorizacion,
                'mensaje' => '¡Pago exitoso con tarjeta!',
                'gateway' => 'culqi'
            ]);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Página de confirmación
     */
    public function confirmacion()
    {
        $checkoutData = $_SESSION['checkout_data'] ?? null;

        if (!$checkoutData) {
            header('Location: ' . BASE_URL . '/tienda');
            exit;
        }

        // Limpiar datos de sesión
        unset($_SESSION['checkout_data']);

        $this->view('checkout/confirmacion', $checkoutData);
    }
}
