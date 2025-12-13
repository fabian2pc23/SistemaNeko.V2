<?php

use App\Controllers\HomeController;
use App\Controllers\ProductController;
use App\Controllers\CartController;
use App\Controllers\CheckoutController;
use App\Controllers\AuthController;

/** @var App\Core\Router $router */

// Página principal tipo Renusa
$router->get('/', [HomeController::class, 'index']);

// Listado de productos / tienda
$router->get('/tienda', [ProductController::class, 'index']);

// Detalle de producto
$router->get('/producto/{id}', [ProductController::class, 'show']);

// Carrito
$router->get('/carrito', [CartController::class, 'index']);
$router->post('/carrito/agregar', [CartController::class, 'add']);
$router->post('/carrito/eliminar', [CartController::class, 'remove']);
$router->get('/carrito/count', [CartController::class, 'count']);

// Autenticación de clientes
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/registro', [AuthController::class, 'registroForm']);
$router->post('/registro', [AuthController::class, 'registro']);
$router->get('/logout', [AuthController::class, 'logout']);

// OAuth Social Login
$router->get('/auth/google', [AuthController::class, 'googleRedirect']);
$router->get('/auth/google/callback', [AuthController::class, 'googleCallback']);
$router->get('/auth/facebook', [AuthController::class, 'facebookRedirect']);
$router->get('/auth/facebook/callback', [AuthController::class, 'facebookCallback']);

// Mi cuenta
$router->get('/mi-cuenta', [AuthController::class, 'miCuenta']);
$router->post('/mi-cuenta/actualizar', [AuthController::class, 'actualizarPerfil']);

// Checkout
$router->get('/checkout', [CheckoutController::class, 'index']);
$router->post('/checkout/procesar-pago', [CheckoutController::class, 'procesarPago']);
$router->post('/checkout/procesar-yape', [CheckoutController::class, 'procesarYape']);
$router->post('/checkout/procesar-tarjeta', [CheckoutController::class, 'procesarTarjeta']);
$router->post('/checkout/procesar-culqi', [CheckoutController::class, 'procesarCulqi']);
$router->get('/checkout/confirmacion', [CheckoutController::class, 'confirmacion']);
