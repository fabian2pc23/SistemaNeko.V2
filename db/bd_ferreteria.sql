-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 13-11-2025 a las 20:28:00
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

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
  `codigo` varchar(50) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `stock` int(11) NOT NULL,
  `precio_compra` decimal(11,2) NOT NULL DEFAULT 0.00,
  `precio_venta` decimal(10,2) NOT NULL,
  `descripcion` varchar(256) DEFAULT NULL,
  `imagen` varchar(50) DEFAULT NULL,
  `condicion` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `articulo`
--

INSERT INTO `articulo` (`idarticulo`, `idcategoria`, `codigo`, `nombre`, `stock`, `precio_compra`, `precio_venta`, `descripcion`, `imagen`, `condicion`) VALUES
(5, 8, '21321355677', 'Tambores de freno', 17, 32.00, 55.00, 'Tambores de Freno Descripción', '1760920283.jpg', 1),
(6, 8, '09227222', 'Pastillas de frenos', 38, 54.00, 90.00, 'Pastilla de frenos', '1760920293.jpg', 1),
(10, 8, '21321355672', 'Discos de embrague', 11, 42.00, 99.00, 'pieza fundamental del sistema de transmisión de un vehículo', '1761714848.jpg', 1),
(11, 8, '274584727348', 'Zapatas de freno', 2, 52.00, 79.77, 'componentes de metal con forma curva que se usan en los frenos de tambor', '', 1),
(13, 12, '5758575758758', 'Escaleras dos metros', 19, 50.00, 76.70, 'tec', '', 1);

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
(7, 'Cilindraje', 'Aceitado de la Marca Bosh', 1),
(8, 'Sistema de Embrague', 'Reúne las piezas que permiten transmitir la potencia del motor a la caja de cambios.', 1),
(11, 'Sistema de Frenos', 'ncluye todos los repuestos y componentes necesarios para garantizar la correcta detención del vehículo.', 1),
(12, 'Escaleras técnicas', 'amarillas', 1),
(13, 'ACEITES MOTO', '', 1),
(14, 'Neumaticos de automovil', 'neumaticos carro', 1);

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
  `subtotal` decimal(11,2) GENERATED ALWAYS AS (`cantidad` * `precio_compra`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `detalle_ingreso`
--

INSERT INTO `detalle_ingreso` (`iddetalle_ingreso`, `idingreso`, `idarticulo`, `cantidad`, `precio_compra`) VALUES
(13, 10, 5, 50, 36.50),
(14, 10, 6, 50, 36.50),
(15, 11, 5, 5, 36.00),
(16, 11, 6, 5, 114.00),
(17, 12, 6, 4, 32343.00),
(18, 12, 5, 3, 0.00),
(19, 12, 10, 1, 0.00),
(20, 13, 5, 10, 32.00),
(21, 13, 6, 15, 54.00),
(22, 14, 10, 5, 42.00),
(23, 14, 13, 5, 50.00),
(24, 15, 6, 2, 54.00),
(25, 16, 13, 2, 50.00),
(26, 16, 6, 2, 54.00),
(27, 17, 10, 2, 42.00),
(28, 17, 6, 2, 54.00),
(29, 18, 6, 2, 54.00);

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
(22, 10, 11, 3, 112.90, 0.00),
(23, 10, 10, 2, 100.00, 0.00),
(24, 10, 10, 4, 100.00, 0.00),
(25, 10, 6, 2, 115.00, 0.00),
(26, 11, 10, 1, 100.00, 0.00),
(27, 11, 6, 1, 115.00, 0.00),
(28, 12, 5, 1, 55.00, 0.00),
(29, 12, 5, 1, 55.00, 0.00),
(30, 12, 5, 1, 55.00, 0.00);

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
(15, 10, 98.00, 99.00, 'jhhuh', 'manual', NULL, 19, '2025-10-31 22:21:21');

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
  `subtotal` decimal(11,2) DEFAULT NULL,
  `impuesto_total` decimal(11,2) DEFAULT NULL,
  `total_compra` decimal(11,2) NOT NULL,
  `estado` enum('Aceptado','Anulado') NOT NULL DEFAULT 'Aceptado'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Volcado de datos para la tabla `ingreso`
--

INSERT INTO `ingreso` (`idingreso`, `idproveedor`, `idusuario`, `tipo_comprobante`, `serie_comprobante`, `num_comprobante`, `fecha_hora`, `subtotal`, `impuesto_total`, `total_compra`, `estado`) VALUES
(10, 13, 5, 'Factura', '001', '0001', '2025-10-19 00:00:00', 0.00, 0.00, 3650.00, 'Anulado'),
(11, 13, 5, 'Boleta', '002', '0002', '2025-10-30 00:00:00', 0.00, 0.00, 750.00, 'Aceptado'),
(12, 13, 5, 'Boleta', '1', '1', '2025-10-31 00:00:00', 0.00, 0.00, 129372.00, 'Anulado'),
(13, 13, 24, 'Factura', '01', '15', '2025-11-09 00:00:00', 0.00, 0.00, 1130.00, 'Aceptado'),
(14, 22, 5, 'Factura', '005', '0005', '2025-11-12 00:00:00', 0.00, 0.00, 460.00, 'Aceptado'),
(15, 13, 20, 'Factura', '', '5', '2025-11-13 00:00:00', NULL, 18.00, 108.00, 'Aceptado'),
(16, 21, 20, 'Factura', '', '15', '2025-11-13 00:00:00', NULL, 18.00, 208.00, 'Aceptado'),
(17, 13, 20, 'Factura', '511', '17', '2025-11-13 00:00:00', NULL, 0.00, 226.56, 'Aceptado'),
(18, 21, 20, 'Boleta', '60', '62', '2025-11-13 00:00:00', NULL, 18.00, 127.44, 'Aceptado');

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
(22, 'Proveedor', 'EMPRESA DEMO S.A.C.', 'RUC', '20479801275', 'Av. Siempre Viva 123, SAN BORJA - LIMA - LIMA', '999999999', '', 1);

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
(8, 2, 1),
(9, 2, 4),
(10, 2, 7),
(11, 3, 1),
(12, 3, 2),
(13, 3, 3),
(14, 3, 6);

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
(12, 'Seguridad', 1, '2025-11-12 14:03:26');

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
(16, 1, 1, 'BRISALINA VASQUEZ DE LA CRUZ', 'DNI', '75474940', '', '940367492', 'cristianmanfredy277@gmail.com', 'Admin', '$2y$10$hNcy5sDWFd8fiprsKSsdHOqNXIjC0rstbBb6nJCh45iMOX/zcfKky', '', 1),
(19, 1, 1, 'CARLOS JHEREMY SERPA CORTEZ', 'DNI', '74417406', 'Eleodoro Coral 270', '966853147', 'serg.dangr@hotmail.com', 'Admin', '$2y$10$USlXBqaNo8bOODAIE6MvYexSeVTywkuqBJ2MqnmN8.9pBsv9wMnJ6', 'vendedor.png', 1),
(20, 1, 1, 'FABIAN ALEXIS PAICO CARRILLO', 'DNI', '76960068', '', '', 'fabianpcfb@gmail.com', 'Admin', '$2y$10$s67ZH.X/xBvuQ4127TFMwOoGcLwNcQCbz/9I6eKvvH2sbWabzvshK', 'default.png', 1),
(21, 1, 2, 'ROBERTO MARTIN CELIS OSORES', 'DNI', '40029519', 'chiclayo', '+51979813011', 'c23919@utp.edu.pe', 'Vendedor', '$2y$10$QDh.yEsAlqCfdrhIhdGsnOewVTypBtEnOHLqgyV4aRAaZH4eMTxpm', 'vendedor.png', 1),
(22, 2, 1, 'CORTEZ FLORES ANDREA DEL CARMEN', 'RUC', '10406980788', 'Lambayeque- lambayeque', '921263349', 'carjher_neko2010@hotmail.com', 'Admin', '$2y$10$53uHDzv/cNYfRE1uQpQmBOFxzP0cQBs0ZtAEcBJSv7bM/b/Fo4o7y', 'vendedor.png', 1),
(24, NULL, 11, 'ROBERTO ADRIAN CELIS LECCA', 'DNI', '71667268', 'calle abc', '979813012', 'roceos@hotmail.com', 'Tecnico', '$2y$10$thUaWy8JczhZnnh/HdBYeeW7PBHwnRSSePwg0KjvYE74eNpGquNHq', '', 1),
(25, NULL, 3, 'KARLA VERONICA CARRILLO NUÑEZ', 'DNI', '17632545', '', '929359033', 'u20311541@utp.edu.pe', 'Almacenero', '$2y$10$b3rxaaojoCzRCQwpMoJLQu9avlvt2WjGX9sRUBq0OKD2oUc0En82u', '1762956733.jpg', 1),
(26, 1, 3, '', 'DNI', '04412417', '', '', 'fabianapaico086@gmail.com', 'Almacenero', '$2y$10$KBd/JvrtYyw4QtzB44W0ku1GHEP/FiwFDcJ.je/Gm7EgUDohpv16S', 'almacenero.png', 1),
(27, NULL, 1, '', 'DNI', '', '', '', 'fabianpcfb23@gmail.com', 'Admin', 'c97eda6a7a04e390a1c0d75fa8093157be5f3563da071393ea136543a0a0a215', 'usuario.png', 1);

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
(295, 16, 1),
(296, 16, 2),
(297, 16, 3),
(298, 16, 4),
(299, 16, 5),
(300, 16, 6),
(301, 16, 7),
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
(319, 24, 3),
(320, 24, 4),
(321, 24, 5),
(322, 25, 1),
(323, 25, 2),
(324, 25, 3),
(325, 25, 6),
(347, 26, 1),
(348, 26, 2),
(349, 26, 3),
(350, 26, 6),
(351, 27, 1),
(352, 27, 2),
(353, 27, 3),
(354, 27, 4),
(355, 27, 5),
(356, 27, 6),
(357, 27, 7);

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
(10, 14, 16, 'Boleta', '1', '1', '2025-10-30 00:00:00', 0.00, 427.90, 'Aceptado'),
(11, 15, 5, 'Boleta', '11', '11', '2025-10-31 00:00:00', 0.00, 215.00, 'Aceptado'),
(12, 15, 20, 'Boleta', '1', '1', '2025-10-31 00:00:00', 0.00, 165.00, 'Aceptado');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_precios_actuales`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_precios_actuales` (
`idarticulo` int(11)
,`nombre` varchar(100)
,`precio_venta` decimal(10,2)
,`precio_compra` decimal(11,2)
,`stock` int(11)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_precios_actuales`
--
DROP TABLE IF EXISTS `v_precios_actuales`;

CREATE ALGORITHM=UNDEFINED DEFINER=`admin`@`localhost` SQL SECURITY DEFINER VIEW `v_precios_actuales`  AS SELECT `a`.`idarticulo` AS `idarticulo`, `a`.`nombre` AS `nombre`, `a`.`precio_venta` AS `precio_venta`, `a`.`precio_compra` AS `precio_compra`, `a`.`stock` AS `stock` FROM `articulo` AS `a` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `articulo`
--
ALTER TABLE `articulo`
  ADD PRIMARY KEY (`idarticulo`),
  ADD UNIQUE KEY `nombre_UNIQUE` (`nombre`),
  ADD KEY `fk_articulo_categoria_idx` (`idcategoria`);

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`idcategoria`),
  ADD UNIQUE KEY `nombre_UNIQUE` (`nombre`);

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
  MODIFY `idarticulo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `idcategoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `detalle_ingreso`
--
ALTER TABLE `detalle_ingreso`
  MODIFY `iddetalle_ingreso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `detalle_venta`
--
ALTER TABLE `detalle_venta`
  MODIFY `iddetalle_venta` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de la tabla `historial_precios`
--
ALTER TABLE `historial_precios`
  MODIFY `id_historial` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `ingreso`
--
ALTER TABLE `ingreso`
  MODIFY `idingreso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de la tabla `password_reset`
--
ALTER TABLE `password_reset`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `permiso`
--
ALTER TABLE `permiso`
  MODIFY `idpermiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `persona`
--
ALTER TABLE `persona`
  MODIFY `idpersona` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `rol_permiso`
--
ALTER TABLE `rol_permiso`
  MODIFY `id_rol_permiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT de la tabla `rol_usuarios`
--
ALTER TABLE `rol_usuarios`
  MODIFY `id_rol` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `user_otp`
--
ALTER TABLE `user_otp`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `idusuario` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT de la tabla `usuario_permiso`
--
ALTER TABLE `usuario_permiso`
  MODIFY `idusuario_permiso` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=358;

--
-- AUTO_INCREMENT de la tabla `venta`
--
ALTER TABLE `venta`
  MODIFY `idventa` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `articulo`
--
ALTER TABLE `articulo`
  ADD CONSTRAINT `fk_articulo_categoria` FOREIGN KEY (`idcategoria`) REFERENCES `categoria` (`idcategoria`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `detalle_ingreso`
--
ALTER TABLE `detalle_ingreso`
  ADD CONSTRAINT `fk_detalle_ingreso_articulo` FOREIGN KEY (`idarticulo`) REFERENCES `articulo` (`idarticulo`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detalle_ingreso_ingreso` FOREIGN KEY (`idingreso`) REFERENCES `ingreso` (`idingreso`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Filtros para la tabla `venta`
--
ALTER TABLE `venta`
  ADD CONSTRAINT `fk_venta_persona` FOREIGN KEY (`idcliente`) REFERENCES `persona` (`idpersona`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_venta_usuario` FOREIGN KEY (`idusuario`) REFERENCES `usuario` (`idusuario`) ON DELETE NO ACTION ON UPDATE NO ACTION;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

