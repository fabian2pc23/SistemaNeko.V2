-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 27-11-2025 a las 08:20:42
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `bd_ferreteria`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `articulo`
--

CREATE TABLE `articulo` (
  `idarticulo` int(11) NOT NULL,
  `idcategoria` int(11) NOT NULL,
  `idmarca` int(11) DEFAULT NULL,
  `codigo` varchar(50) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `stock` int(11) NOT NULL,
  `precio_compra` decimal(11,2) NOT NULL DEFAULT 0.00,
  `precio_venta` decimal(11,2) NOT NULL DEFAULT 0.00,
  `descripcion` varchar(256) DEFAULT NULL,
  `imagen` varchar(50) DEFAULT NULL,
  `condicion` tinyint(1) NOT NULL DEFAULT 1,
  `costo_promedio` decimal(11,2) DEFAULT 0.00,
  `ultimo_costo` decimal(11,2) DEFAULT 0.00,
  `ultima_compra` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `articulo`
--

INSERT INTO `articulo` (`idarticulo`, `idcategoria`, `idmarca`, `codigo`, `nombre`, `stock`, `precio_compra`, `precio_venta`, `descripcion`, `imagen`, `condicion`, `costo_promedio`, `ultimo_costo`, `ultima_compra`) VALUES
(5, 8, 13, '21321355677', 'Disco de embrague reforzado', 16, 32.00, 60.00, 'Disco de embrague de alto rendimiento para vehículos de carga ligera.', 'art_20251121_223718_7046.jpg', 1, 0.00, 0.00, NULL),
(6, 8, 4, '09227222', 'Kit de embrague estándar 1.6', 0, 42.00, 80.00, 'Kit completo de disco, plato y collarín para motores 1.6, ideal para uso urbano.', 'art_20251121_223547_9734.png', 1, 0.00, 0.00, NULL),
(10, 8, NULL, '21321355672', 'Discos de embrague', 0, 42.00, 70.00, 'pieza fundamental del sistema de transmisión de un vehículo', '1761714848.jpg', 1, 0.00, 0.00, NULL),
(13, 12, NULL, '5758575758758', 'Escaleras dos metros', 15, 50.00, 100.00, 'tec', '', 0, 0.00, 0.00, NULL),
(25, 7, NULL, '7753446676164', 'cilindro dos tiempos moto', 1, 14.00, 21.48, 'wanxin', '', 1, 0.00, 0.00, NULL),
(26, 8, NULL, '42752752542', 'llantas tractor', 10, 15.00, 23.01, 'fsdfsdf', '', 1, 0.00, 0.00, NULL),
(27, 11, NULL, '527827212752', 'liquido de frenos moto', 25, 15.00, 23.01, '.', '', 1, 0.00, 0.00, NULL),
(28, 11, NULL, '5727275287', 'llantas para carro', 14, 14.00, 21.48, 'klkl', '', 1, 0.00, 0.00, NULL),
(29, 12, NULL, '7727662020292', 'Aviones', 0, 11.00, 40.00, 'AAA', 'art_20251114_122832_6967.jpg', 1, 0.00, 0.00, NULL),
(30, 12, NULL, '3595261442353', 'Avion', 3, 50.00, 10.00, 'Asa', 'art_20251114_122855_5304.jpg', 1, 0.00, 0.00, NULL),
(31, 12, NULL, '7760464142137', 'Cajas', 9, 50.00, 76.70, 'Cajas', 'art_20251114_122921_9659.jpg', 1, 0.00, 0.00, NULL),
(32, 14, NULL, '7738731991832', 'SERGIO', 0, 22.00, 33.75, '', '', 1, 0.00, 0.00, NULL),
(33, 11, 4, '7750410814041', 'Kit de zapatas de freno trasero', 0, 12.00, 32.00, 'Juego completo de zapatas reforzadas para freno trasero de tambor.', 'art_20251121_224051_2890.jpg', 1, 0.00, 0.00, NULL),
(34, 13, 13, '8629132889976', 'Disco de freno ventilado delantero', 0, 32.00, 90.00, 'Disco ventilado que mejora la disipación de calor en frenadas continuas.', 'art_20251121_224015_7680.jpg', 1, 0.00, 0.00, NULL),
(35, 11, 1, '3239646047813', 'Juego de pastillas de freno delanteras cerámicas', 7, 32.00, 49.09, 'Pastillas cerámicas de baja emisión de polvo, mejoran frenado y limpieza.', 'art_20251121_223942_9250.jpg', 1, 0.00, 0.00, NULL),
(36, 8, 1, '3412157446415', 'Cilindro maestro de embrague', 10, 38.00, 58.29, 'Bomba de embrague para sistemas hidráulicos, garantiza presión estable.', 'art_20251121_223900_9484.jpg', 1, 0.00, 0.00, NULL),
(38, 8, 12, '9968882601032', 'Collarín hidráulico de embrague', 3, 0.00, 0.00, 'Collarín hidráulico de larga duración para sistemas de embrague modernos.', 'art_20251121_223823_9745.jpg', 1, 0.00, 0.00, NULL),
(40, 8, NULL, '', 'Sodimac', 0, 0.00, 0.00, '', '', 1, 0.00, 0.00, NULL),
(41, 8, 11, '4154397747188', 'Remaches', 0, 0.00, 0.00, 'saas', '', 1, 0.00, 0.00, NULL),
(42, 8, 11, '5182848280166', 'asss', 3, 10.00, 15.34, 'sasa', '', 1, 0.00, 0.00, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

CREATE TABLE `categoria` (
  `idcategoria` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` varchar(256) DEFAULT NULL,
  `condicion` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `categoria`
--

INSERT INTO `categoria` (`idcategoria`, `nombre`, `descripcion`, `condicion`) VALUES
(7, 'Árbol de levas', 'Aceitado de la Marca Bosh', 0),
(8, 'Sistema de Embrague', 'Reúne las piezas que permiten transmitir la potencia del motor a la caja de cambios.', 1),
(11, 'Sistema de Frenos', 'Incluye todos los repuestos y componentes necesarios para garantizar la correcta detención del vehículo.', 1),
(12, 'Escaleras técnicas', 'amarillas', 1),
(13, 'Banda de distribución', 'saas', 1),
(14, 'Pastillas', '111', 1),
(15, 'Pistones y aros', '', 1),
(16, 'Motos', '', 1),
(17, 'Culatas y empaquetaduras', '', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comprobante_serie`
--

CREATE TABLE `comprobante_serie` (
  `idcomprobante` int(11) NOT NULL,
  `tipo` enum('Boleta','Factura','Ticket') NOT NULL,
  `serie` varchar(4) NOT NULL,
  `correlativo` int(11) NOT NULL DEFAULT 1,
  `impuesto` decimal(5,2) NOT NULL DEFAULT 18.00,
  `estado` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `comprobante_serie`
--

INSERT INTO `comprobante_serie` (`idcomprobante`, `tipo`, `serie`, `correlativo`, `impuesto`, `estado`) VALUES
(1, 'Boleta', 'B001', 18, 18.00, 1),
(2, 'Factura', 'F001', 5, 18.00, 1),
(3, 'Ticket', 'T001', 2, 18.00, 1),
(4, 'Boleta', 'B001', 10, 18.00, 1),
(5, 'Factura', 'F001', 4, 18.00, 1),
(6, 'Ticket', 'T001', 2, 18.00, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_ingreso`
--

CREATE TABLE `detalle_ingreso` (
  `iddetalle_ingreso` int(11) NOT NULL,
  `idingreso` int(11) NOT NULL,
  `idarticulo` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_compra` decimal(11,2) NOT NULL,
  `subtotal` decimal(11,2) NOT NULL DEFAULT 0.00,
  `precio_venta` decimal(11,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `detalle_ingreso`
--

INSERT INTO `detalle_ingreso` (`iddetalle_ingreso`, `idingreso`, `idarticulo`, `cantidad`, `precio_compra`, `subtotal`, `precio_venta`) VALUES
(13, 10, 5, 50, 36.50, 0.00, 36.80),
(14, 10, 6, 50, 36.50, 0.00, 36.80),
(15, 11, 5, 5, 36.00, 0.00, 36.80),
(16, 11, 6, 5, 114.00, 0.00, 115.00),
(17, 12, 6, 4, 32343.00, 0.00, 49614.16),
(18, 12, 5, 3, 0.00, 0.00, 36.80),
(19, 12, 10, 1, 0.00, 0.00, 100.00),
(20, 13, 5, 10, 32.00, 0.00, 55.00),
(21, 13, 6, 15, 54.00, 0.00, 90.00),
(22, 14, 10, 5, 42.00, 0.00, 99.00),
(23, 14, 13, 5, 50.00, 0.00, 76.70),
(24, 15, 10, 2, 42.00, 0.00, 0.00),
(25, 16, 10, 2, 42.00, 0.00, 0.00),
(26, 17, 27, 15, 15.00, 0.00, 23.01),
(27, 18, 28, 5, 14.00, 0.00, 21.48),
(29, 21, 30, 4, 11.00, 44.00, 16.87),
(30, 22, 30, 3, 11.00, 33.00, 16.87),
(31, 23, 29, 3, 11.00, 33.00, 16.87),
(32, 24, 31, 3, 22.00, 66.00, 33.75),
(33, 25, 25, 3, 14.00, 42.00, 21.48),
(34, 26, 10, 3, 42.00, 126.00, 0.00),
(35, 27, 29, 2, 11.00, 22.00, 16.87),
(36, 28, 38, 1, 32.00, 32.00, 0.00),
(37, 29, 38, 2, 32.00, 64.00, 0.00),
(38, 30, 29, 2, 31.00, 62.00, 16.87),
(39, 31, 30, 2, 32.00, 64.00, 16.87),
(40, 32, 31, 2, 32.00, 64.00, 33.75),
(41, 33, 35, 4, 32.00, 128.00, 0.00),
(42, 34, 35, 3, 32.00, 96.00, 0.00),
(43, 35, 36, 10, 38.00, 380.00, 0.00),
(44, 36, 30, 15, 32.00, 480.00, 49.09),
(45, 37, 31, 3, 40.00, 120.00, 60.00),
(46, 38, 31, 3, 30.00, 90.00, 60.00),
(47, 39, 31, 3, 70.00, 210.00, 60.00),
(48, 40, 30, 3, 50.00, 150.00, 50.00),
(49, 41, 31, 1, 50.00, 50.00, 60.00),
(50, 42, 42, 3, 10.00, 30.00, 0.00);

--
-- Disparadores `detalle_ingreso`
--
DELIMITER $$
CREATE TRIGGER `tr_updStockIngreso` AFTER INSERT ON `detalle_ingreso` FOR EACH ROW BEGIN
 UPDATE articulo SET stock = stock + NEW.cantidad 
 WHERE articulo.idarticulo = NEW.idarticulo;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_venta`
--

CREATE TABLE `detalle_venta` (
  `iddetalle_venta` int(11) NOT NULL,
  `idventa` int(11) NOT NULL,
  `idarticulo` int(11) NOT NULL,
  `cantidad` int(11) NOT NULL,
  `precio_venta` decimal(11,2) NOT NULL,
  `descuento` decimal(11,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `detalle_venta`
--

INSERT INTO `detalle_venta` (`iddetalle_venta`, `idventa`, `idarticulo`, `cantidad`, `precio_venta`, `descuento`) VALUES
(26, 11, 10, 1, 100.00, 0.00),
(27, 11, 6, 1, 115.00, 0.00),
(28, 12, 5, 1, 55.00, 0.00),
(29, 12, 5, 1, 55.00, 0.00),
(30, 12, 5, 1, 55.00, 0.00),
(31, 13, 29, 3, 16.87, 0.00),
(32, 13, 30, 2, 16.87, 0.00),
(33, 14, 29, 1, 16.87, 0.00),
(34, 14, 30, 1, 16.87, 0.00),
(35, 14, 31, 1, 33.75, 0.00),
(36, 15, 25, 1, 21.48, 0.00),
(37, 15, 25, 1, 21.48, 0.00),
(38, 15, 25, 1, 21.48, 0.00),
(39, 16, 29, 1, 16.87, 0.00),
(40, 16, 29, 1, 16.87, 0.00),
(41, 17, 30, 1, 16.87, 0.00),
(42, 17, 29, 1, 16.87, 0.00),
(43, 18, 25, 1, 21.48, 0.00),
(44, 18, 29, 1, 16.87, 0.00),
(45, 19, 29, 1, 16.87, 0.00),
(46, 19, 29, 1, 16.87, 0.00),
(47, 20, 29, 1, 16.87, 0.00),
(48, 20, 29, 1, 16.87, 0.00),
(49, 21, 29, 1, 16.87, 0.00),
(50, 21, 29, 1, 16.87, 0.00),
(51, 22, 29, 1, 16.87, 0.00),
(52, 22, 29, 1, 16.87, 0.00),
(53, 23, 25, 1, 21.48, 0.00),
(54, 23, 25, 1, 21.48, 0.00),
(55, 24, 29, 1, 16.87, 0.00),
(56, 24, 29, 1, 16.87, 0.00),
(57, 24, 29, 1, 16.87, 0.00),
(58, 24, 29, 1, 16.87, 0.00),
(59, 24, 29, 1, 16.87, 0.00),
(60, 25, 29, 4, 16.87, 0.00),
(61, 26, 25, 3, 21.48, 0.00),
(62, 27, 29, 4, 16.87, 0.00),
(63, 28, 30, 3, 16.87, 0.00),
(64, 29, 25, 6, 21.48, 0.00),
(65, 30, 30, 20, 16.87, 0.00),
(66, 31, 31, 16, 33.75, 0.00),
(67, 32, 5, 1, 10.00, 0.00),
(68, 33, 25, 2, 21.48, 0.00),
(69, 34, 30, 3, 16.87, 0.00),
(70, 35, 29, 3, 16.87, 0.00),
(71, 36, 30, 5, 49.09, 0.00);

--
-- Disparadores `detalle_venta`
--
DELIMITER $$
CREATE TRIGGER `tr_updStockVenta` AFTER INSERT ON `detalle_venta` FOR EACH ROW BEGIN
 UPDATE articulo SET stock = stock - NEW.cantidad 
 WHERE articulo.idarticulo = NEW.idarticulo;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `historial_precios`
--

CREATE TABLE `historial_precios` (
  `id_historial` int(11) NOT NULL,
  `idarticulo` int(11) NOT NULL,
  `precio_anterior` decimal(11,2) NOT NULL,
  `precio_nuevo` decimal(11,2) NOT NULL,
  `motivo` varchar(200) DEFAULT NULL,
  `fuente` enum('manual','ingreso') NOT NULL DEFAULT 'manual',
  `id_origen` int(11) DEFAULT NULL,
  `idusuario` int(11) DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `historial_precios`
--

INSERT INTO `historial_precios` (`id_historial`, `idarticulo`, `precio_anterior`, `precio_nuevo`, `motivo`, `fuente`, `id_origen`, `idusuario`, `fecha`) VALUES
(1, 10, 100.00, 21.00, 'Demo 3', 'manual', NULL, 5, '2025-10-31 16:46:34'),
(2, 5, 36.80, 32.00, 'Motivo Demo', 'manual', NULL, 5, '2025-10-31 16:47:14'),
(3, 10, 64.43, 80.00, 'Cambios de Proveedor', 'manual', NULL, 5, '2025-10-31 16:52:00'),
(4, 6, 49614.16, 322.00, 'Demo 2 precio', 'manual', NULL, 5, '2025-10-31 17:29:00'),
(5, 10, 80.00, 82.00, '', 'manual', NULL, 5, '2025-10-31 19:40:33'),
(6, 5, 49.09, 53.00, 'CAMBIO DEL DOLAR', 'manual', NULL, 5, '2025-10-31 19:41:48'),
(7, 10, 82.00, 90.00, 'j', 'manual', NULL, 5, '2025-10-31 19:43:28'),
(8, 10, 90.00, 92.00, 'Ajuste de precio', 'manual', NULL, 5, '2025-10-31 19:49:37'),
(9, 10, 92.00, 94.00, 'lo que sea', 'manual', NULL, 19, '2025-10-31 19:50:45'),
(10, 5, 53.00, 55.00, 'probando', 'manual', NULL, 19, '2025-10-31 19:51:29'),
(11, 6, 322.00, 323.00, 'Cambio de Dolar', 'manual', NULL, 5, '2025-10-31 19:57:38'),
(12, 6, 82.84, 90.00, 'alza de dolar', 'manual', NULL, 20, '2025-10-31 20:33:36'),
(13, 10, 94.00, 96.00, 'ajuste de precio', 'manual', NULL, 19, '2025-10-31 22:20:02'),
(14, 10, 96.00, 98.00, 'nadjnajsdm', 'manual', NULL, 19, '2025-10-31 22:20:48'),
(15, 10, 98.00, 99.00, 'jhhuh', 'manual', NULL, 19, '2025-10-31 22:21:21'),
(16, 5, 0.00, 49.09, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-20 20:58:02'),
(17, 5, 0.00, 49.09, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-20 20:58:04'),
(18, 5, 0.00, 49.09, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-20 20:58:05'),
(19, 5, 0.00, 62.89, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-20 20:58:14'),
(20, 5, 0.00, 62.89, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-20 20:58:14'),
(21, 5, 0.00, 49.09, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-20 20:58:19'),
(22, 6, 82.84, 33.75, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-20 20:59:38'),
(23, 6, 33.75, 49.09, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-20 20:59:43'),
(24, 33, 18.41, 32.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-20 21:02:56'),
(25, 6, 49.09, 64.43, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-20 21:05:16'),
(26, 5, 0.00, 49.09, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-20 21:05:38'),
(27, 5, 0.00, 51.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-20 21:05:43'),
(28, 5, 0.00, 51.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-20 21:05:44'),
(29, 5, 0.00, 51.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-20 21:05:44'),
(30, 5, 0.00, 51.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-20 21:05:44'),
(31, 6, 64.43, 80.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-20 21:16:51'),
(32, 6, 80.00, 92.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-20 21:17:23'),
(33, 6, 92.00, 100.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-20 22:22:31'),
(34, 5, 0.00, 111.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-20 22:33:51'),
(35, 6, 100.00, 64.43, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 00:32:09'),
(36, 34, 49.09, 80.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 01:48:28'),
(37, 30, 16.87, 49.09, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 11:53:50'),
(38, 29, 16.87, 40.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 11:55:37'),
(39, 30, 49.09, 50.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 11:57:33'),
(40, 31, 33.75, 60.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 11:58:40'),
(41, 10, 0.00, 70.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 11:59:16'),
(42, 5, 0.00, 50.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 14:37:15'),
(43, 5, 0.00, 50.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 14:37:16'),
(44, 5, 0.00, 50.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 14:37:17'),
(45, 5, 0.00, 50.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 14:37:17'),
(46, 5, 0.00, 51.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 14:37:19'),
(47, 5, 0.00, 51.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 14:37:19'),
(48, 5, 0.00, 51.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 14:37:20'),
(49, 38, 0.00, 10.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 14:37:26'),
(50, 5, 0.00, 50.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 14:37:36'),
(51, 34, 80.00, 90.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 14:37:53'),
(52, 35, 0.00, 49.09, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 14:38:02'),
(53, 5, 0.00, 49.09, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 14:38:19'),
(54, 5, 0.00, 49.09, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 14:38:20'),
(55, 5, 0.00, 100.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 14:38:24'),
(56, 5, 0.00, 100.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 14:38:27'),
(57, 36, 0.00, 58.29, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 14:52:24'),
(58, 5, 0.00, 59.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 14:52:34'),
(59, 13, 0.00, 100.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 14:52:45'),
(60, 30, 50.00, 76.70, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 15:11:57'),
(61, 31, 60.00, 76.70, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 15:15:51'),
(62, 30, 76.70, 60.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 15:16:02'),
(63, 30, 60.00, 10.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 15:16:12'),
(64, 38, 10.00, 0.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 16:38:23'),
(65, 5, 59.00, 60.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 19:55:20'),
(66, 42, 0.00, 15.34, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 19:57:18'),
(67, 6, 64.43, 80.00, 'Actualización en módulo Artículos', 'manual', NULL, 19, '2025-11-21 22:11:44');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingreso`
--

CREATE TABLE `ingreso` (
  `idingreso` int(11) NOT NULL,
  `idproveedor` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `tipo_comprobante` varchar(20) NOT NULL,
  `serie_comprobante` varchar(7) DEFAULT NULL,
  `num_comprobante` varchar(10) NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `subtotal` decimal(11,2) NOT NULL DEFAULT 0.00,
  `impuesto_total` decimal(11,2) NOT NULL DEFAULT 0.00,
  `impuesto` decimal(4,2) NOT NULL DEFAULT 18.00,
  `total_compra` decimal(11,2) NOT NULL,
  `tipo_ingreso` enum('compra','alta_inicial','ajuste','devolucion') NOT NULL DEFAULT 'compra',
  `estado` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `ingreso`
--

INSERT INTO `ingreso` (`idingreso`, `idproveedor`, `idusuario`, `tipo_comprobante`, `serie_comprobante`, `num_comprobante`, `fecha_hora`, `subtotal`, `impuesto_total`, `impuesto`, `total_compra`, `tipo_ingreso`, `estado`) VALUES
(10, 13, 5, 'Factura', '001', '0001', '2025-10-19 00:00:00', 0.00, 0.00, 18.00, 3650.00, 'compra', 'Anulado'),
(11, 13, 5, 'Boleta', '002', '0002', '2025-10-30 00:00:00', 0.00, 0.00, 0.00, 750.00, 'compra', 'Aceptado'),
(12, 13, 5, 'Boleta', '1', '1', '2025-10-31 00:00:00', 0.00, 0.00, 1.00, 129372.00, 'compra', 'Anulado'),
(13, 13, 24, 'Factura', '01', '15', '2025-11-09 00:00:00', 0.00, 0.00, 18.00, 1130.00, 'compra', 'Aceptado'),
(14, 22, 5, 'Factura', '005', '0005', '2025-11-12 00:00:00', 0.00, 0.00, 18.00, 460.00, 'compra', 'Aceptado'),
(15, 20, 20, 'Boleta', '', '747', '2025-11-12 00:00:00', 0.00, 0.00, 18.00, 84.00, 'compra', 'Aceptado'),
(16, 21, 20, 'Boleta', '', '5484', '2025-11-12 00:00:00', 0.00, 0.00, 0.00, 84.00, 'compra', 'Aceptado'),
(17, 13, 20, 'Boleta', '', '2262', '2025-11-12 00:00:00', 0.00, 0.00, 0.00, 225.00, 'compra', 'Aceptado'),
(18, 21, 20, 'Boleta', '', '42', '2025-11-12 00:00:00', 0.00, 0.00, 0.00, 70.00, 'compra', 'Aceptado'),
(21, 13, 19, 'Boleta', '', '0000000043', '2025-11-21 09:55:23', 44.00, 7.92, 18.00, 51.92, 'compra', 'Aceptado'),
(22, 13, 19, 'Boleta', '', '0000000044', '2025-11-21 10:25:07', 33.00, 5.94, 18.00, 38.94, 'compra', 'Aceptado'),
(23, 13, 19, 'Boleta', '', '0000000045', '2025-11-21 10:31:21', 33.00, 5.94, 18.00, 38.94, 'compra', 'Aceptado'),
(24, 13, 19, 'Boleta', '', '0000000046', '2025-11-21 10:31:42', 66.00, 11.88, 18.00, 77.88, 'compra', 'Aceptado'),
(25, 13, 19, 'Boleta', '', '0000000047', '2025-11-21 10:37:20', 42.00, 7.56, 18.00, 49.56, 'compra', 'Aceptado'),
(26, 13, 19, 'Boleta', '', '0000000048', '2025-11-21 10:37:56', 126.00, 22.68, 18.00, 148.68, 'compra', 'Aceptado'),
(27, 13, 19, 'Boleta', '', '0000000049', '2025-11-21 10:49:05', 22.00, 3.96, 18.00, 25.96, 'compra', 'Aceptado'),
(28, 13, 19, 'Boleta', '', '0000000050', '2025-11-21 11:03:55', 32.00, 5.76, 18.00, 37.76, 'compra', 'Aceptado'),
(29, 13, 19, 'Boleta', '', '0000000051', '2025-11-21 11:15:32', 64.00, 11.52, 18.00, 75.52, 'compra', 'Aceptado'),
(30, 13, 19, 'Boleta', '', '0000000052', '2025-11-21 11:16:35', 62.00, 11.16, 18.00, 73.16, 'compra', 'Aceptado'),
(31, 13, 19, 'Boleta', '', '0000000053', '2025-11-21 11:21:05', 64.00, 11.52, 18.00, 75.52, 'compra', 'Aceptado'),
(32, 13, 19, 'Factura', '005', '0000000006', '2025-11-21 11:22:24', 64.00, 11.52, 18.00, 75.52, 'compra', 'Aceptado'),
(33, 13, 19, 'Boleta', '', '0000000054', '2025-11-21 11:44:16', 128.00, 23.04, 18.00, 151.04, 'compra', 'Aceptado'),
(34, 20, 19, 'Boleta', '', '0000000055', '2025-11-21 11:45:04', 96.00, 17.28, 18.00, 113.28, 'compra', 'Aceptado'),
(35, 41, 19, 'Boleta', '', '0000000056', '2025-11-21 11:50:35', 380.00, 68.40, 18.00, 448.40, 'compra', 'Aceptado'),
(36, 41, 19, 'Boleta', '', '0000000057', '2025-11-21 11:56:09', 480.00, 86.40, 18.00, 566.40, 'compra', 'Aceptado'),
(37, 41, 19, 'Boleta', '', '0000000058', '2025-11-21 12:01:05', 120.00, 21.60, 18.00, 141.60, 'compra', 'Aceptado'),
(38, 41, 19, 'Boleta', '', '0000000059', '2025-11-21 12:01:43', 90.00, 16.20, 18.00, 106.20, 'compra', 'Aceptado'),
(39, 41, 19, 'Boleta', '', '0000000060', '2025-11-21 12:02:19', 210.00, 37.80, 18.00, 247.80, 'compra', 'Aceptado'),
(40, 41, 19, 'Boleta', '', '0000000061', '2025-11-21 15:10:46', 150.00, 27.00, 18.00, 177.00, 'compra', 'Aceptado'),
(41, 41, 19, 'Boleta', '', '0000000062', '2025-11-21 15:12:54', 50.00, 9.00, 18.00, 59.00, 'compra', 'Anulado'),
(42, 41, 19, 'Boleta', '', '0000000063', '2025-11-21 19:57:06', 30.00, 5.40, 18.00, 35.40, 'compra', 'Aceptado');

--
-- Disparadores `ingreso`
--
DELIMITER $$
CREATE TRIGGER `tr_updStockIngresoAnular` AFTER UPDATE ON `ingreso` FOR EACH ROW BEGIN
UPDATE articulo a
JOIN detalle_ingreso di
ON di.idarticulo = a.idarticulo
AND di.idingreso = new.idingreso
set a.stock = a.stock - di.cantidad;
end
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `marca`
--

CREATE TABLE `marca` (
  `idmarca` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(256) DEFAULT NULL,
  `condicion` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `marca`
--

INSERT INTO `marca` (`idmarca`, `nombre`, `descripcion`, `condicion`) VALUES
(1, 'Bosch', 'Partes eléctricas, sistemas de inyección, frenos, sensores.', 1),
(2, 'Monroe', 'marca especializada en suspensión (resortes, amortiguadores).', 1),
(4, 'Valeo', 'fabricante francés, sistemas eléctricos, iluminación, clima.', 1),
(11, 'Denso', 'marca del grupo General Motors, buena para baterías, bujías, suspensión.', 1),
(12, 'ZF Friedrichshafen', 'transmite, chasis, dirección, frenos.', 1),
(13, 'TRW', 'frenos, dirección', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_reset`
--

CREATE TABLE `password_reset` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `password_reset`
--

INSERT INTO `password_reset` (`id`, `user_id`, `token_hash`, `expires_at`, `used`, `created_at`) VALUES
(13, 19, '817bb7bf7e7cc1a94808f16ab05f1d48296619a92a35cd308f72ada9be0a80db', '2025-11-13 12:14:39', 0, '2025-11-13 10:14:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permiso`
--

CREATE TABLE `permiso` (
  `idpermiso` int(11) NOT NULL,
  `nombre` varchar(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `permiso`
--

INSERT INTO `permiso` (`idpermiso`, `nombre`) VALUES
(1, 'Escritorio'),
(2, 'Almacen'),
(3, 'Compras'),
(4, 'Ventas'),
(5, 'Acceso'),
(6, 'Consulta Compras'),
(7, 'Consulta Ventas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `persona`
--

CREATE TABLE `persona` (
  `idpersona` int(11) NOT NULL,
  `tipo_persona` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo_documento` varchar(20) DEFAULT NULL,
  `num_documento` varchar(20) DEFAULT NULL,
  `direccion` varchar(70) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `condicion` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `persona`
--

INSERT INTO `persona` (`idpersona`, `tipo_persona`, `nombre`, `tipo_documento`, `num_documento`, `direccion`, `telefono`, `email`, `condicion`) VALUES
(13, 'Proveedor', 'FERRETERIA EL PROVEEDOR S A', 'RUC', '20100712670', '', '932375900', 'provedorsa@gmail.com', 1),
(14, 'Cliente', 'WALTER ELEONEL GIL TERRONES', 'DNI', '16617373', 'CALLE E.DEMETRIO CORAL 270 URB PRIMAVERA', '966853147', 'serg.dangr@hotmail.com', 1),
(15, 'Cliente', 'ROBERTO MARTIN CELIS OSORES', 'DNI', '40029519', 'CAL LA MAR 178 URB SANTA VICTORIA', '966853142', 'U21227728@utp.edu.pe', 1),
(18, 'Cliente', 'JHON LENNYN MIJAHUANCA QUINTOS', 'DNI', '74702048', 'LIBERTAD C-10', '', '', 1),
(20, 'Proveedor', 'TIENDAS DEL MEJORAMIENTO DEL HOGAR S.A.', 'RUC', '20112273922', 'AV. ANGAMOS ESTE NRO. 1805 INT. 2', '932049468', 'sodimacperu@gmail.com', 1),
(21, 'Proveedor', 'FERRETERIA ESPINOZA E.I.R.L', 'RUC', '20613509870', 'JR. RAMON CASTILLA NRO. 301 URB. LAS PALMERAS', '959284023', 'ferreespinoza@gmail.com', 1),
(22, 'Proveedor', 'EMPRESA DEMO S.A.C.', 'RUC', '20479801275', 'Av. Siempre Viva 123, SAN BORJA - LIMA - LIMA', '999999999', '', 0),
(38, 'Cliente', 'JUAN CARLOS ANTONIO COLLAZOS QUIROZ', 'DNI', '16617372', 'AV.LOS TAMBOS 813', '985421212', '', 1),
(40, 'Cliente', 'LIDIA GONZALES DIAZ', 'DNI', '40029513', 'CALLE CAHUIDE 144', '985421212', '', 1),
(41, 'Proveedor', 'EMPRESA DEMO S.A.C.', 'RUC', '10202929292', 'Av. Siempre Viva 123, SAN BORJA - LIMA - LIMA', '966666666', '', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_permiso`
--

CREATE TABLE `rol_permiso` (
  `id_rol_permiso` int(11) NOT NULL,
  `id_rol` int(10) UNSIGNED NOT NULL,
  `idpermiso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rol_permiso`
--

INSERT INTO `rol_permiso` (`id_rol_permiso`, `id_rol`, `idpermiso`) VALUES
(1, 1, 1),
(2, 1, 2),
(3, 1, 3),
(4, 1, 4),
(5, 1, 5),
(6, 1, 6),
(7, 1, 7),
(26, 2, 1),
(27, 2, 4),
(25, 2, 7),
(11, 3, 1),
(12, 3, 2),
(13, 3, 3),
(14, 3, 6),
(29, 11, 2),
(30, 11, 6),
(17, 13, 1),
(15, 13, 6),
(16, 13, 7);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rol_usuarios`
--

CREATE TABLE `rol_usuarios` (
  `id_rol` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `rol_usuarios`
--

INSERT INTO `rol_usuarios` (`id_rol`, `nombre`, `estado`, `creado_en`) VALUES
(1, 'Admin', 1, '2025-10-16 16:26:58'),
(2, 'Vendedor', 1, '2025-10-16 16:26:58'),
(3, 'Almacenero', 1, '2025-10-16 16:26:58'),
(9, 'Supervisor', 1, '2025-10-29 03:40:12'),
(11, 'Tecnico', 1, '2025-11-12 02:00:06'),
(12, 'Seguridad', 1, '2025-11-12 14:03:26'),
(13, 'SCRUM MASTER', 1, '2025-11-14 20:09:34');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tipo_documento`
--

CREATE TABLE `tipo_documento` (
  `id_tipodoc` tinyint(3) UNSIGNED NOT NULL,
  `nombre` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `tipo_documento`
--

INSERT INTO `tipo_documento` (`id_tipodoc`, `nombre`) VALUES
(1, 'DNI'),
(3, 'PASAPORTE'),
(2, 'RUC');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user_otp`
--

CREATE TABLE `user_otp` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` int(11) NOT NULL,
  `code_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `idusuario` int(11) NOT NULL,
  `id_tipodoc` tinyint(3) UNSIGNED DEFAULT NULL,
  `id_rol` int(10) UNSIGNED DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo_documento` varchar(20) NOT NULL,
  `num_documento` varchar(20) NOT NULL,
  `direccion` varchar(70) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(50) DEFAULT NULL,
  `cargo` varchar(20) DEFAULT NULL,
  `clave` varchar(64) NOT NULL,
  `imagen` varchar(50) NOT NULL,
  `condicion` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`idusuario`, `id_tipodoc`, `id_rol`, `nombre`, `tipo_documento`, `num_documento`, `direccion`, `telefono`, `email`, `cargo`, `clave`, `imagen`, `condicion`) VALUES
(5, 1, 1, 'CRISTIAN MANFREDY DAVILA VALLE', 'DNI', '74134653', 'Barcelona 210 Urb San Juan', '932 375 500', 'cristiandavilavalle@gmail.com', 'Admin', '$2y$10$xKfdjvsK.3KRR11nFG3At.lv5IXkXMAaA0G9SQU6Lzr5F/Gj4LwoS', '1760918574.jpg', 1),
(19, 1, 1, 'CARLOS JHEREMY SERPA CORTEZ', 'DNI', '74417406', 'Eleodoro Coral 270', '966853147', 'serg.dangr@hotmail.com', 'Admin', '$2y$10$USlXBqaNo8bOODAIE6MvYexSeVTywkuqBJ2MqnmN8.9pBsv9wMnJ6', 'vendedor.png', 1),
(20, 1, 1, 'FABIAN ALEXIS PAICO CARRILLO', 'DNI', '76960068', '', '', 'fabianpcfb@gmail.com', 'Admin', '$2y$10$VjuvJlyFfA/0y2rhrwVRWOSmmiBLrJv8yPfP81jcxSG3LYcbwO5WO', 'default.png', 1),
(21, 1, 2, 'ROBERTO MARTIN CELIS OSORES', 'DNI', '40029519', 'chiclayo', '+51979813011', 'c23919@utp.edu.pe', 'Vendedor', '$2y$10$QDh.yEsAlqCfdrhIhdGsnOewVTypBtEnOHLqgyV4aRAaZH4eMTxpm', 'vendedor.png', 1),
(22, 2, 1, 'CORTEZ FLORES ANDREA DEL CARMEN', 'RUC', '10406980788', 'Lambayeque- lambayeque', '921263349', 'carjher_neko2010@hotmail.com', 'Admin', '$2y$10$53uHDzv/cNYfRE1uQpQmBOFxzP0cQBs0ZtAEcBJSv7bM/b/Fo4o7y', 'vendedor.png', 1),
(24, NULL, 11, 'ROBERTO ADRIAN CELIS LECCA', 'DNI', '71667268', 'CALLE LA MAR 178 URB. SANTA VICTORIA', '979813012', 'serg.el_master@hotmail.com', 'Tecnico', '$2y$10$gySp/qifWiMhJCn.SkmNR.W1.Shcgcun2DdtW59yRyK/2hIYKugFu', 'usuario.png', 1),
(28, 1, 1, 'JOSE EDUARDO ANGELES BRAVO', 'DNI', '72928002', 'LOTIZ. SAN MIGUEL MZ. B LT. 19', '940367492', 'darkedu1019@gmail.com', 'Admin', '$2y$10$38/zcWwdVSSvoOyqH8Kf2uyMWV9247Fh7TBG6LDPzvMXg84l1gue.', 'usuario.png', 1);

--
-- Disparadores `usuario`
--
DELIMITER $$
CREATE TRIGGER `tr_usuario_tipodoc_bi` BEFORE INSERT ON `usuario` FOR EACH ROW BEGIN
    DECLARE vtd VARCHAR(20);

    IF NEW.id_tipodoc IS NOT NULL THEN
        SELECT nombre INTO vtd
        FROM tipo_documento
        WHERE id_tipodoc = NEW.id_tipodoc
        LIMIT 1;

        IF vtd IS NOT NULL THEN
            SET NEW.tipo_documento = vtd;
        END IF;
    END IF;

    IF NEW.tipo_documento IS NULL THEN
        SET NEW.tipo_documento = '';
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_usuario_tipodoc_bu` BEFORE UPDATE ON `usuario` FOR EACH ROW BEGIN
    DECLARE vtd2 VARCHAR(20);

    IF (NEW.id_tipodoc <> OLD.id_tipodoc)
       OR (NEW.tipo_documento IS NULL OR TRIM(NEW.tipo_documento) = '') THEN

        IF NEW.id_tipodoc IS NOT NULL THEN
            SELECT nombre INTO vtd2
            FROM tipo_documento
            WHERE id_tipodoc = NEW.id_tipodoc
            LIMIT 1;

            IF vtd2 IS NOT NULL THEN
                SET NEW.tipo_documento = vtd2;
            END IF;
        END IF;
    END IF;

    IF NEW.tipo_documento IS NULL THEN
        SET NEW.tipo_documento = '';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_permiso`
--

CREATE TABLE `usuario_permiso` (
  `idusuario_permiso` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `idpermiso` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `usuario_permiso`
--

INSERT INTO `usuario_permiso` (`idusuario_permiso`, `idusuario`, `idpermiso`) VALUES
(274, 5, 1),
(275, 5, 2),
(276, 5, 3),
(277, 5, 4),
(278, 5, 5),
(279, 5, 6),
(280, 5, 7),
(281, 19, 1),
(282, 19, 2),
(283, 19, 3),
(284, 19, 4),
(285, 19, 5),
(286, 19, 6),
(287, 19, 7),
(288, 20, 1),
(289, 20, 2),
(290, 20, 3),
(291, 20, 4),
(292, 20, 5),
(293, 20, 6),
(294, 20, 7),
(302, 21, 1),
(303, 21, 4),
(304, 21, 7),
(308, 22, 1),
(309, 22, 2),
(310, 22, 3),
(311, 22, 4),
(312, 22, 5),
(313, 22, 6),
(314, 22, 7),
(386, 28, 5),
(387, 28, 2),
(388, 28, 3),
(389, 28, 6),
(390, 28, 7),
(391, 28, 1),
(392, 28, 4),
(393, 24, 7),
(394, 24, 1),
(395, 24, 4);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_rol`
--

CREATE TABLE `usuario_rol` (
  `id_usuario_rol` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL COMMENT 'ID del usuario',
  `id_rol` int(10) UNSIGNED NOT NULL COMMENT 'ID del rol',
  `es_principal` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=Rol principal del usuario, 0=Rol secundario',
  `activo` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1=Activo, 0=Inactivo',
  `asignado_por` int(11) DEFAULT NULL COMMENT 'Usuario que asignó este rol',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  `actualizado_en` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabla relacional: Un usuario puede tener múltiples roles';

--
-- Volcado de datos para la tabla `usuario_rol`
--

INSERT INTO `usuario_rol` (`id_usuario_rol`, `idusuario`, `id_rol`, `es_principal`, `activo`, `asignado_por`, `creado_en`, `actualizado_en`) VALUES
(1, 5, 1, 1, 1, NULL, '2025-11-14 23:52:00', NULL),
(2, 19, 1, 1, 1, NULL, '2025-11-14 23:52:00', NULL),
(3, 20, 1, 1, 1, NULL, '2025-11-14 23:52:00', NULL),
(4, 22, 1, 1, 1, NULL, '2025-11-14 23:52:00', NULL),
(5, 28, 1, 1, 1, NULL, '2025-11-14 23:52:00', NULL),
(6, 21, 2, 1, 1, NULL, '2025-11-14 23:52:00', NULL),
(7, 24, 11, 1, 1, NULL, '2025-11-14 23:52:00', NULL);

--
-- Disparadores `usuario_rol`
--
DELIMITER $$
CREATE TRIGGER `trg_usuario_rol_sync_principal` AFTER UPDATE ON `usuario_rol` FOR EACH ROW BEGIN
  -- Si se marca como principal, actualizar tabla usuario
  IF NEW.es_principal = 1 AND OLD.es_principal = 0 THEN
    UPDATE usuario u
    SET u.id_rol = NEW.id_rol,
        u.cargo = (SELECT nombre FROM rol_usuarios WHERE id_rol = NEW.id_rol LIMIT 1)
    WHERE u.idusuario = NEW.idusuario;
  END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_usuario_rol_validar_principal` BEFORE UPDATE ON `usuario_rol` FOR EACH ROW BEGIN
  DECLARE v_count INT;
  
  -- Si se desmarca como principal, verificar que haya otro principal
  IF OLD.es_principal = 1 AND NEW.es_principal = 0 THEN
    SELECT COUNT(*) INTO v_count
    FROM usuario_rol
    WHERE idusuario = NEW.idusuario
      AND id_rol != NEW.id_rol
      AND es_principal = 1
      AND activo = 1;
    
    IF v_count = 0 THEN
      SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'Debe haber al menos un rol marcado como principal';
    END IF;
  END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario_roles_new`
--

CREATE TABLE `usuario_roles_new` (
  `id_usuario_rol` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `id_rol` int(10) UNSIGNED NOT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1=Rol principal, 0=Rol secundario',
  `asignado_en` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `usuario_roles_new`
--

INSERT INTO `usuario_roles_new` (`id_usuario_rol`, `idusuario`, `id_rol`, `es_principal`, `asignado_en`) VALUES
(1, 5, 1, 1, '2025-11-14 19:34:31'),
(2, 19, 1, 1, '2025-11-14 19:34:31'),
(3, 20, 1, 1, '2025-11-14 19:34:31'),
(4, 22, 1, 1, '2025-11-14 19:34:31'),
(6, 21, 2, 1, '2025-11-14 19:34:31'),
(12, 28, 3, 0, '2025-11-18 22:53:29'),
(13, 28, 1, 1, '2025-11-18 22:53:29'),
(14, 24, 11, 1, '2025-11-18 22:54:16'),
(15, 24, 2, 0, '2025-11-18 22:54:16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `venta`
--

CREATE TABLE `venta` (
  `idventa` int(11) NOT NULL,
  `idcliente` int(11) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `tipo_comprobante` varchar(20) NOT NULL,
  `serie_comprobante` varchar(7) DEFAULT NULL,
  `num_comprobante` varchar(10) NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `impuesto` decimal(4,2) NOT NULL,
  `total_venta` decimal(11,2) NOT NULL,
  `estado` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `venta`
--

INSERT INTO `venta` (`idventa`, `idcliente`, `idusuario`, `tipo_comprobante`, `serie_comprobante`, `num_comprobante`, `fecha_hora`, `impuesto`, `total_venta`, `estado`) VALUES
(11, 15, 5, 'Boleta', '11', '11', '2025-10-31 00:00:00', 0.00, 215.00, 'Aceptado'),
(12, 15, 20, 'Boleta', '1', '1', '2025-10-31 00:00:00', 0.00, 165.00, 'Aceptado'),
(13, 14, 19, 'Boleta', '', '1', '2025-11-14 00:00:00', 18.00, 33.74, 'Aceptado'),
(14, 15, 24, 'Boleta', 'B001', '00000001', '2025-11-18 00:00:00', 18.00, 67.49, 'Aceptado'),
(15, 14, 24, 'Boleta', 'B001', '00000001', '2025-11-18 00:00:00', 18.00, 64.44, 'Aceptado'),
(16, 15, 24, 'Factura', 'F001', '00000001', '2025-11-18 00:00:00', 18.00, 33.74, 'Aceptado'),
(17, 15, 24, 'Boleta', 'B001', '00000002', '2025-11-19 00:00:00', 18.00, 33.74, 'Aceptado'),
(18, 15, 19, 'Factura', 'F001', '00000002', '2025-11-19 00:00:00', 18.00, 38.35, 'Aceptado'),
(19, 14, 19, 'Ticket', 'T001', '00000001', '2025-11-19 00:00:00', 18.00, 33.74, 'Aceptado'),
(20, 15, 19, 'Boleta', 'B001', '00000003', '2025-11-19 00:00:00', 18.00, 33.74, 'Aceptado'),
(21, 15, 19, 'Boleta', 'B001', '00000004', '2025-11-19 00:00:00', 18.00, 33.74, 'Aceptado'),
(22, 15, 19, 'Boleta', 'B001', '00000005', '2025-11-19 00:00:00', 18.00, 33.74, 'Aceptado'),
(23, 14, 19, 'Boleta', 'B001', '00000006', '2025-11-19 00:00:00', 18.00, 42.96, 'Aceptado'),
(24, 14, 19, 'Boleta', 'B001', '00000007', '2025-11-19 00:00:00', 18.00, 84.35, 'Aceptado'),
(25, 14, 19, 'Factura', 'F001', '00000003', '2025-11-19 00:00:00', 18.00, 67.48, 'Aceptado'),
(26, 14, 19, 'Boleta', 'B001', '00000008', '2025-11-19 00:00:00', 18.00, 64.44, 'Aceptado'),
(27, 14, 19, 'Boleta', 'B001', '00000009', '2025-11-19 00:00:00', 18.00, 67.48, 'Aceptado'),
(28, 14, 19, 'Factura', 'F001', '00000004', '2025-11-19 00:00:00', 18.00, 50.61, 'Aceptado'),
(29, 14, 19, 'Boleta', 'B001', '00000010', '2025-11-19 00:00:00', 18.00, 128.88, 'Aceptado'),
(30, 14, 19, 'Boleta', 'B001', '00000012', '2025-11-19 00:00:00', 18.00, 337.40, 'Aceptado'),
(31, 14, 19, 'Boleta', 'B001', '00000013', '2025-11-19 00:00:00', 18.00, 540.00, 'Aceptado'),
(32, 14, 19, 'Boleta', 'B001', '00000014', '2025-11-20 00:00:00', 18.00, 10.00, 'Aceptado'),
(33, 14, 19, 'Boleta', 'B001', '00000015', '2025-11-21 00:00:00', 18.00, 42.96, 'Aceptado'),
(34, 14, 19, 'Boleta', 'B001', '00000016', '2025-11-21 00:00:00', 18.00, 50.61, 'Aceptado'),
(35, 14, 19, 'Boleta', 'B001', '00000017', '2025-11-21 00:00:00', 18.00, 50.61, 'Aceptado'),
(36, 14, 19, 'Boleta', 'B001', '00000018', '2025-11-21 00:00:00', 18.00, 245.45, 'Aceptado');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_precios_actuales`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_precios_actuales` (
`idarticulo` int(11)
,`nombre` varchar(100)
,`precio_venta` decimal(11,2)
,`precio_compra` decimal(11,2)
,`stock` int(11)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_roles_estadisticas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_roles_estadisticas` (
`id_rol` int(10) unsigned
,`rol_nombre` varchar(50)
,`rol_activo` tinyint(1)
,`total_usuarios` bigint(21)
,`usuarios_principal` bigint(21)
,`usuarios_secundario` bigint(21)
,`permisos` mediumtext
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_usuario_permisos_acumulados`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_usuario_permisos_acumulados` (
`idusuario` int(11)
,`usuario_nombre` varchar(100)
,`idpermiso` int(11)
,`permiso_nombre` varchar(30)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_usuario_resumen_roles`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_usuario_resumen_roles` (
`idusuario` int(11)
,`usuario_nombre` varchar(100)
,`email` varchar(50)
,`condicion` tinyint(1)
,`total_roles` bigint(21)
,`roles_texto` mediumtext
,`rol_principal` varchar(50)
,`id_rol_principal` bigint(10) unsigned
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_usuario_roles`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_usuario_roles` (
`idusuario` int(11)
,`usuario_nombre` varchar(100)
,`email` varchar(50)
,`usuario_activo` tinyint(1)
,`id_rol` int(10) unsigned
,`rol_nombre` varchar(50)
,`es_principal` tinyint(1)
,`rol_activo` tinyint(1)
,`rol_asignado_en` timestamp
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_precios_actuales`
--
DROP TABLE IF EXISTS `v_precios_actuales`;

CREATE ALGORITHM=UNDEFINED DEFINER=`admin`@`localhost` SQL SECURITY DEFINER VIEW `v_precios_actuales`  AS SELECT `a`.`idarticulo` AS `idarticulo`, `a`.`nombre` AS `nombre`, `a`.`precio_venta` AS `precio_venta`, `a`.`precio_compra` AS `precio_compra`, `a`.`stock` AS `stock` FROM `articulo` AS `a` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_roles_estadisticas`
--
DROP TABLE IF EXISTS `v_roles_estadisticas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_roles_estadisticas`  AS SELECT `r`.`id_rol` AS `id_rol`, `r`.`nombre` AS `rol_nombre`, `r`.`estado` AS `rol_activo`, count(distinct `ur`.`idusuario`) AS `total_usuarios`, count(distinct case when `ur`.`es_principal` = 1 then `ur`.`idusuario` end) AS `usuarios_principal`, count(distinct case when `ur`.`es_principal` = 0 then `ur`.`idusuario` end) AS `usuarios_secundario`, group_concat(distinct `p`.`nombre` order by `p`.`nombre` ASC separator ', ') AS `permisos` FROM (((`rol_usuarios` `r` left join `usuario_rol` `ur` on(`r`.`id_rol` = `ur`.`id_rol` and `ur`.`activo` = 1)) left join `rol_permiso` `rp` on(`r`.`id_rol` = `rp`.`id_rol`)) left join `permiso` `p` on(`rp`.`idpermiso` = `p`.`idpermiso`)) GROUP BY `r`.`id_rol`, `r`.`nombre`, `r`.`estado` ORDER BY `r`.`nombre` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_usuario_permisos_acumulados`
--
DROP TABLE IF EXISTS `v_usuario_permisos_acumulados`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_usuario_permisos_acumulados`  AS SELECT DISTINCT `u`.`idusuario` AS `idusuario`, `u`.`nombre` AS `usuario_nombre`, `p`.`idpermiso` AS `idpermiso`, `p`.`nombre` AS `permiso_nombre` FROM (((`usuario` `u` join `usuario_rol` `ur` on(`u`.`idusuario` = `ur`.`idusuario`)) join `rol_permiso` `rp` on(`ur`.`id_rol` = `rp`.`id_rol`)) join `permiso` `p` on(`rp`.`idpermiso` = `p`.`idpermiso`)) WHERE `ur`.`activo` = 1 AND `u`.`condicion` = 1 ORDER BY `u`.`nombre` ASC, `p`.`nombre` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_usuario_resumen_roles`
--
DROP TABLE IF EXISTS `v_usuario_resumen_roles`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_usuario_resumen_roles`  AS SELECT `u`.`idusuario` AS `idusuario`, `u`.`nombre` AS `usuario_nombre`, `u`.`email` AS `email`, `u`.`condicion` AS `condicion`, count(`ur`.`id_rol`) AS `total_roles`, group_concat(concat(case when `ur`.`es_principal` = 1 then '⭐ ' else '• ' end,`r`.`nombre`) order by `ur`.`es_principal` DESC,`r`.`nombre` ASC separator ', ') AS `roles_texto`, max(case when `ur`.`es_principal` = 1 then `r`.`nombre` end) AS `rol_principal`, max(case when `ur`.`es_principal` = 1 then `r`.`id_rol` end) AS `id_rol_principal` FROM ((`usuario` `u` left join `usuario_rol` `ur` on(`u`.`idusuario` = `ur`.`idusuario` and `ur`.`activo` = 1)) left join `rol_usuarios` `r` on(`ur`.`id_rol` = `r`.`id_rol`)) GROUP BY `u`.`idusuario`, `u`.`nombre`, `u`.`email`, `u`.`condicion` ORDER BY `u`.`nombre` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_usuario_roles`
--
DROP TABLE IF EXISTS `v_usuario_roles`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_usuario_roles`  AS SELECT `u`.`idusuario` AS `idusuario`, `u`.`nombre` AS `usuario_nombre`, `u`.`email` AS `email`, `u`.`condicion` AS `usuario_activo`, `r`.`id_rol` AS `id_rol`, `r`.`nombre` AS `rol_nombre`, `ur`.`es_principal` AS `es_principal`, `ur`.`activo` AS `rol_activo`, `ur`.`creado_en` AS `rol_asignado_en` FROM ((`usuario` `u` join `usuario_rol` `ur` on(`u`.`idusuario` = `ur`.`idusuario`)) join `rol_usuarios` `r` on(`ur`.`id_rol` = `r`.`id_rol`)) ORDER BY `u`.`nombre` ASC, `ur`.`es_principal` DESC, `r`.`nombre` ASC ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `articulo`
--
ALTER TABLE `articulo`
  ADD PRIMARY KEY (`idarticulo`),
  ADD UNIQUE KEY `nombre_UNIQUE` (`nombre`),
  ADD KEY `fk_articulo_categoria_idx` (`idcategoria`),
  ADD KEY `fk_articulo_marca_idx` (`idmarca`);

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`idcategoria`),
  ADD UNIQUE KEY `nombre_UNIQUE` (`nombre`);

--
-- Indices de la tabla `comprobante_serie`
--
ALTER TABLE `comprobante_serie`
  ADD PRIMARY KEY (`idcomprobante`);

--
-- Indices de la tabla `detalle_ingreso`
--
ALTER TABLE `detalle_ingreso`
  ADD PRIMARY KEY (`iddetalle_ingreso`),
  ADD KEY `fk_detalle_ingreso_ingreso_idx` (`idingreso`),
  ADD KEY `fk_detalle_ingreso_articulo_idx` (`idarticulo`);

--
-- Indices de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD PRIMARY KEY (`iddetalle_venta`),
  ADD KEY `fk_detalle_venta_venta_idx` (`idventa`),
  ADD KEY `fk_detalle_venta_articulo_idx` (`idarticulo`);

--
-- Indices de la tabla `historial_precios`
--
ALTER TABLE `historial_precios`
  ADD PRIMARY KEY (`id_historial`),
  ADD KEY `idx_hp_articulo` (`idarticulo`),
  ADD KEY `idx_hp_usuario` (`idusuario`);

--
-- Indices de la tabla `ingreso`
--
ALTER TABLE `ingreso`
  ADD PRIMARY KEY (`idingreso`),
  ADD KEY `fk_ingreso_persona_idx` (`idproveedor`),
  ADD KEY `fk_ingreso_usuario_idx` (`idusuario`);

--
-- Indices de la tabla `marca`
--
ALTER TABLE `marca`
  ADD PRIMARY KEY (`idmarca`),
  ADD UNIQUE KEY `nombre_UNIQUE` (`nombre`);

--
-- Indices de la tabla `password_reset`
--
ALTER TABLE `password_reset`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_token` (`token_hash`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_expires` (`expires_at`);

--
-- Indices de la tabla `permiso`
--
ALTER TABLE `permiso`
  ADD PRIMARY KEY (`idpermiso`);

--
-- Indices de la tabla `persona`
--
ALTER TABLE `persona`
  ADD PRIMARY KEY (`idpersona`),
  ADD UNIQUE KEY `uniq_proveedor_doc` (`tipo_persona`,`num_documento`);

--
-- Indices de la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  ADD PRIMARY KEY (`id_rol_permiso`),
  ADD UNIQUE KEY `uk_rol_permiso` (`id_rol`,`idpermiso`),
  ADD KEY `fk_rol_permiso_rol` (`id_rol`),
  ADD KEY `fk_rol_permiso_permiso` (`idpermiso`);

--
-- Indices de la tabla `rol_usuarios`
--
ALTER TABLE `rol_usuarios`
  ADD PRIMARY KEY (`id_rol`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `tipo_documento`
--
ALTER TABLE `tipo_documento`
  ADD PRIMARY KEY (`id_tipodoc`),
  ADD UNIQUE KEY `nombre` (`nombre`);

--
-- Indices de la tabla `user_otp`
--
ALTER TABLE `user_otp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`idusuario`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_tipodoc` (`id_tipodoc`),
  ADD KEY `idx_documento` (`num_documento`),
  ADD KEY `fk_usuario_rol` (`id_rol`);

--
-- Indices de la tabla `usuario_permiso`
--
ALTER TABLE `usuario_permiso`
  ADD PRIMARY KEY (`idusuario_permiso`),
  ADD KEY `fk_usuario_permiso_permiso_idx` (`idpermiso`),
  ADD KEY `fk_usuario_permiso_usuario_idx` (`idusuario`);

--
-- Indices de la tabla `usuario_rol`
--
ALTER TABLE `usuario_rol`
  ADD PRIMARY KEY (`id_usuario_rol`),
  ADD UNIQUE KEY `uk_usuario_rol` (`idusuario`,`id_rol`) COMMENT 'Un usuario no puede tener el mismo rol 2 veces',
  ADD KEY `idx_usuario` (`idusuario`),
  ADD KEY `idx_rol` (`id_rol`),
  ADD KEY `idx_principal` (`es_principal`),
  ADD KEY `idx_activo` (`activo`),
  ADD KEY `idx_usuario_rol_activo_principal` (`idusuario`,`activo`,`es_principal`),
  ADD KEY `idx_usuario_rol_rol_activo` (`id_rol`,`activo`);

--
-- Indices de la tabla `usuario_roles_new`
--
ALTER TABLE `usuario_roles_new`
  ADD PRIMARY KEY (`id_usuario_rol`),
  ADD UNIQUE KEY `unique_usuario_rol` (`idusuario`,`id_rol`),
  ADD KEY `idx_usuario` (`idusuario`),
  ADD KEY `idx_rol` (`id_rol`),
  ADD KEY `idx_principal` (`idusuario`,`es_principal`);

--
-- Indices de la tabla `venta`
--
ALTER TABLE `venta`
  ADD PRIMARY KEY (`idventa`),
  ADD KEY `fk_venta_persona_idx` (`idcliente`),
  ADD KEY `fk_venta_usuario_idx` (`idusuario`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `articulo`
--
ALTER TABLE `articulo`
  MODIFY `idarticulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `idcategoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `comprobante_serie`
--
ALTER TABLE `comprobante_serie`
  MODIFY `idcomprobante` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `detalle_ingreso`
--
ALTER TABLE `detalle_ingreso`
  MODIFY `iddetalle_ingreso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `iddetalle_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT de la tabla `historial_precios`
--
ALTER TABLE `historial_precios`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=68;

--
-- AUTO_INCREMENT de la tabla `ingreso`
--
ALTER TABLE `ingreso`
  MODIFY `idingreso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de la tabla `marca`
--
ALTER TABLE `marca`
  MODIFY `idmarca` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `password_reset`
--
ALTER TABLE `password_reset`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `permiso`
--
ALTER TABLE `permiso`
  MODIFY `idpermiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `persona`
--
ALTER TABLE `persona`
  MODIFY `idpersona` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT de la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  MODIFY `id_rol_permiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `rol_usuarios`
--
ALTER TABLE `rol_usuarios`
  MODIFY `id_rol` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `user_otp`
--
ALTER TABLE `user_otp`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `idusuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `usuario_permiso`
--
ALTER TABLE `usuario_permiso`
  MODIFY `idusuario_permiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=396;

--
-- AUTO_INCREMENT de la tabla `usuario_rol`
--
ALTER TABLE `usuario_rol`
  MODIFY `id_usuario_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `usuario_roles_new`
--
ALTER TABLE `usuario_roles_new`
  MODIFY `id_usuario_rol` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `venta`
--
ALTER TABLE `venta`
  MODIFY `idventa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `articulo`
--
ALTER TABLE `articulo`
  ADD CONSTRAINT `fk_articulo_categoria` FOREIGN KEY (`idcategoria`) REFERENCES `categoria` (`idcategoria`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_articulo_marca` FOREIGN KEY (`idmarca`) REFERENCES `marca` (`idmarca`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `detalle_ingreso`
--
ALTER TABLE `detalle_ingreso`
  ADD CONSTRAINT `fk_detalle_ingreso_articulo` FOREIGN KEY (`idarticulo`) REFERENCES `articulo` (`idarticulo`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_detalle_ingreso_ingreso` FOREIGN KEY (`idingreso`) REFERENCES `ingreso` (`idingreso`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  ADD CONSTRAINT `fk_detalle_venta_articulo` FOREIGN KEY (`idarticulo`) REFERENCES `articulo` (`idarticulo`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_detalle_venta_venta` FOREIGN KEY (`idventa`) REFERENCES `venta` (`idventa`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `historial_precios`
--
ALTER TABLE `historial_precios`
  ADD CONSTRAINT `fk_hp_articulo` FOREIGN KEY (`idarticulo`) REFERENCES `articulo` (`idarticulo`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_hp_usuario` FOREIGN KEY (`idusuario`) REFERENCES `usuario` (`idusuario`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `ingreso`
--
ALTER TABLE `ingreso`
  ADD CONSTRAINT `fk_ingreso_persona` FOREIGN KEY (`idproveedor`) REFERENCES `persona` (`idpersona`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_ingreso_usuario` FOREIGN KEY (`idusuario`) REFERENCES `usuario` (`idusuario`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `password_reset`
--
ALTER TABLE `password_reset`
  ADD CONSTRAINT `fk_reset_usuario` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`idusuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  ADD CONSTRAINT `fk_rol_permiso_permiso` FOREIGN KEY (`idpermiso`) REFERENCES `permiso` (`idpermiso`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_rol_permiso_rol` FOREIGN KEY (`id_rol`) REFERENCES `rol_usuarios` (`id_rol`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `user_otp`
--
ALTER TABLE `user_otp`
  ADD CONSTRAINT `fk_otp_usuario` FOREIGN KEY (`user_id`) REFERENCES `usuario` (`idusuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD CONSTRAINT `fk_usuario_rol` FOREIGN KEY (`id_rol`) REFERENCES `rol_usuarios` (`id_rol`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuario_tipodoc` FOREIGN KEY (`id_tipodoc`) REFERENCES `tipo_documento` (`id_tipodoc`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuario_permiso`
--
ALTER TABLE `usuario_permiso`
  ADD CONSTRAINT `fk_usuario_permiso_permiso` FOREIGN KEY (`idpermiso`) REFERENCES `permiso` (`idpermiso`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_usuario_permiso_usuario` FOREIGN KEY (`idusuario`) REFERENCES `usuario` (`idusuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `usuario_rol`
--
ALTER TABLE `usuario_rol`
  ADD CONSTRAINT `fk_usuario_rol_rol` FOREIGN KEY (`id_rol`) REFERENCES `rol_usuarios` (`id_rol`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_usuario_rol_usuario` FOREIGN KEY (`idusuario`) REFERENCES `usuario` (`idusuario`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `venta`
--
ALTER TABLE `venta`
  ADD CONSTRAINT `fk_venta_persona` FOREIGN KEY (`idcliente`) REFERENCES `persona` (`idpersona`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_venta_usuario` FOREIGN KEY (`idusuario`) REFERENCES `usuario` (`idusuario`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
