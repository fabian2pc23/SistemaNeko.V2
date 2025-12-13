<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Articulo;

class CartController extends Controller
{
    public function index(): void
    {
        $cart = $_SESSION['cart'] ?? [];

        $items = [];
        $total = 0.0;

        foreach ($cart as $id => $qty) {
            $producto = Articulo::find((int) $id);
            if (!$producto) {
                continue;
            }
            $cantidad = max(1, (int) $qty);
            $precio = (float) $producto['precio_venta'];
            $subtotal = $cantidad * $precio;

            $items[] = [
                'producto' => $producto,
                'cantidad' => $cantidad,
                'subtotal' => $subtotal,
            ];
            $total += $subtotal;
        }

        $this->view('cart/index', [
            'items' => $items,
            'total' => $total,
        ]);
    }

    /**
     * Agregar producto al carrito
     * Soporta tanto peticiones normales como AJAX
     */
    public function add(): void
    {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($id <= 0) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'ID de producto inválido']);
                exit;
            }
            $this->redirect('/carrito');
            return;
        }

        // Verificar que el producto existe y tiene stock
        $producto = Articulo::find($id);
        if (!$producto) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'Producto no encontrado']);
                exit;
            }
            $this->redirect('/carrito');
            return;
        }

        // Verificar stock
        $stockActual = (int) $producto['stock'];
        $cantidadEnCarrito = $_SESSION['cart'][$id] ?? 0;
        $qty = isset($_POST['cantidad']) ? max(1, (int) $_POST['cantidad']) : 1;

        if ($stockActual <= 0) {
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Producto agotado',
                    'stock' => 0
                ]);
                exit;
            }
            $this->redirect('/carrito');
            return;
        }

        // Verificar que no exceda el stock disponible
        if (($cantidadEnCarrito + $qty) > $stockActual) {
            $qty = max(0, $stockActual - $cantidadEnCarrito);
            if ($qty <= 0) {
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => false,
                        'error' => 'Ya tienes el máximo disponible en tu carrito',
                        'stock' => $stockActual
                    ]);
                    exit;
                }
                $this->redirect('/carrito');
                return;
            }
        }

        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        if (!isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id] = 0;
        }

        $_SESSION['cart'][$id] += $qty;

        // Calcular nuevo total del carrito
        $cartCount = array_sum($_SESSION['cart']);

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => '¡Producto agregado al carrito!',
                'producto' => [
                    'id' => $id,
                    'nombre' => $producto['nombre'],
                    'precio' => $producto['precio_venta'],
                    'imagen' => $producto['imagen'] ?? null,
                    'cantidad_agregada' => $qty
                ],
                'cart_count' => $cartCount,
                'cart_total' => $_SESSION['cart'][$id]
            ]);
            exit;
        }

        $this->redirect('/carrito');
    }

    /**
     * Eliminar producto del carrito (soporta AJAX)
     */
    public function remove(): void
    {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        if ($id > 0 && isset($_SESSION['cart'][$id])) {
            unset($_SESSION['cart'][$id]);
        }

        $cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => 'Producto eliminado del carrito',
                'cart_count' => $cartCount
            ]);
            exit;
        }

        $this->redirect('/carrito');
    }

    /**
     * Obtener cantidad de items en el carrito (para AJAX)
     */
    public function count(): void
    {
        header('Content-Type: application/json');
        $cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
        echo json_encode(['count' => $cartCount]);
        exit;
    }
}
