-- ======================================================================
-- Script para añadir tablas faltantes a la base de datos bd_ferreteria
-- Generado automáticamente - 2025-12-12
-- ======================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ======================================================================
-- 1. TABLA: caja
-- Descripción: Sistema de control de caja para ventas y compras
-- ======================================================================

CREATE TABLE IF NOT EXISTS `caja` (
  `idcaja` int(11) NOT NULL AUTO_INCREMENT,
  `idusuario` int(11) NOT NULL,
  `fecha_apertura` datetime NOT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `monto_inicial` decimal(11,2) NOT NULL DEFAULT 0.00,
  `monto_final` decimal(11,2) DEFAULT NULL,
  `total_ventas` decimal(11,2) NOT NULL DEFAULT 0.00,
  `total_compras` decimal(11,2) NOT NULL DEFAULT 0.00,
  `estado` enum('Abierta','Cerrada') NOT NULL DEFAULT 'Abierta',
  `observaciones` text DEFAULT NULL,
  PRIMARY KEY (`idcaja`),
  KEY `idx_usuario` (`idusuario`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha_apertura` (`fecha_apertura`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Añadir FK para caja (si la tabla se creó exitosamente)
ALTER TABLE `caja` 
  ADD CONSTRAINT `fk_caja_usuario` FOREIGN KEY (`idusuario`) REFERENCES `usuario` (`idusuario`);

-- ======================================================================
-- 2. TABLA: movimiento_caja
-- Descripción: Registra todos los movimientos de caja (ventas, compras, etc.)
-- ======================================================================

CREATE TABLE IF NOT EXISTS `movimiento_caja` (
  `idmovimiento` int(11) NOT NULL AUTO_INCREMENT,
  `idcaja` int(11) NOT NULL,
  `tipo_movimiento` enum('venta','compra','ingreso_manual','egreso_manual') NOT NULL,
  `idventa` int(11) DEFAULT NULL,
  `idingreso` int(11) DEFAULT NULL,
  `monto` decimal(11,2) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `fecha_hora` datetime NOT NULL,
  PRIMARY KEY (`idmovimiento`),
  KEY `idx_caja` (`idcaja`),
  KEY `idx_tipo` (`tipo_movimiento`),
  KEY `idx_venta` (`idventa`),
  KEY `idx_ingreso` (`idingreso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Añadir FKs para movimiento_caja
ALTER TABLE `movimiento_caja`
  ADD CONSTRAINT `fk_movimiento_caja` FOREIGN KEY (`idcaja`) REFERENCES `caja` (`idcaja`),
  ADD CONSTRAINT `fk_movimiento_ingreso` FOREIGN KEY (`idingreso`) REFERENCES `ingreso` (`idingreso`),
  ADD CONSTRAINT `fk_movimiento_venta` FOREIGN KEY (`idventa`) REFERENCES `venta` (`idventa`);

-- ======================================================================
-- 3. TRIGGER: Actualizar totales de caja al insertar movimiento
-- ======================================================================

DELIMITER $$
DROP TRIGGER IF EXISTS `tr_actualizar_total_ventas` $$
CREATE TRIGGER `tr_actualizar_total_ventas` AFTER INSERT ON `movimiento_caja` FOR EACH ROW BEGIN
    IF NEW.tipo_movimiento = 'venta' THEN
        UPDATE caja 
        SET total_ventas = total_ventas + NEW.monto 
        WHERE idcaja = NEW.idcaja;
    END IF;
    
    IF NEW.tipo_movimiento = 'compra' THEN
        UPDATE caja 
        SET total_compras = total_compras + NEW.monto 
        WHERE idcaja = NEW.idcaja;
    END IF;
END $$
DELIMITER ;

-- ======================================================================
-- 4. Añadir columna idcaja a tablas venta e ingreso (si no existe)
-- ======================================================================

-- Para tabla venta
SET @col_venta_caja = (SELECT COUNT(*) 
  FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'bd_ferreteria' 
    AND TABLE_NAME = 'venta' 
    AND COLUMN_NAME = 'idcaja');

SET @sql_venta = IF(@col_venta_caja = 0, 
  'ALTER TABLE `venta` ADD COLUMN `idcaja` int(11) DEFAULT NULL, ADD KEY `idx_venta_caja` (`idcaja`)', 
  'SELECT 1');
PREPARE stmt FROM @sql_venta;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- FK para venta.idcaja (solo si no existe)
SET @fk_venta_caja = (SELECT COUNT(*) 
  FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
  WHERE CONSTRAINT_SCHEMA = 'bd_ferreteria' 
    AND TABLE_NAME = 'venta' 
    AND CONSTRAINT_NAME = 'fk_venta_caja');

SET @sql_fk_venta = IF(@fk_venta_caja = 0, 
  'ALTER TABLE `venta` ADD CONSTRAINT `fk_venta_caja` FOREIGN KEY (`idcaja`) REFERENCES `caja` (`idcaja`)', 
  'SELECT 1');
PREPARE stmt FROM @sql_fk_venta;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Para tabla ingreso
SET @col_ingreso_caja = (SELECT COUNT(*) 
  FROM INFORMATION_SCHEMA.COLUMNS 
  WHERE TABLE_SCHEMA = 'bd_ferreteria' 
    AND TABLE_NAME = 'ingreso' 
    AND COLUMN_NAME = 'idcaja');

SET @sql_ingreso = IF(@col_ingreso_caja = 0, 
  'ALTER TABLE `ingreso` ADD COLUMN `idcaja` int(11) DEFAULT NULL, ADD KEY `idx_ingreso_caja` (`idcaja`)', 
  'SELECT 1');
PREPARE stmt FROM @sql_ingreso;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- FK para ingreso.idcaja (solo si no existe)
SET @fk_ingreso_caja = (SELECT COUNT(*) 
  FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
  WHERE CONSTRAINT_SCHEMA = 'bd_ferreteria' 
    AND TABLE_NAME = 'ingreso' 
    AND CONSTRAINT_NAME = 'fk_ingreso_caja');

SET @sql_fk_ingreso = IF(@fk_ingreso_caja = 0, 
  'ALTER TABLE `ingreso` ADD CONSTRAINT `fk_ingreso_caja` FOREIGN KEY (`idcaja`) REFERENCES `caja` (`idcaja`)', 
  'SELECT 1');
PREPARE stmt FROM @sql_fk_ingreso;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ======================================================================
-- 5. VISTA: v_caja_actual
-- Descripción: Muestra información de la caja actualmente abierta
-- ======================================================================

DROP VIEW IF EXISTS `v_caja_actual`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_caja_actual` AS 
SELECT 
    `c`.`idcaja` AS `idcaja`, 
    `c`.`idusuario` AS `idusuario`, 
    `u`.`nombre` AS `usuario`, 
    `c`.`fecha_apertura` AS `fecha_apertura`, 
    `c`.`fecha_cierre` AS `fecha_cierre`, 
    `c`.`monto_inicial` AS `monto_inicial`, 
    `c`.`monto_final` AS `monto_final`, 
    `c`.`total_ventas` AS `total_ventas`, 
    `c`.`total_compras` AS `total_compras`, 
    `c`.`monto_inicial` + `c`.`total_ventas` - `c`.`total_compras` AS `saldo_calculado`, 
    `c`.`estado` AS `estado`, 
    `c`.`observaciones` AS `observaciones`, 
    COUNT(DISTINCT `v`.`idventa`) AS `num_ventas`, 
    COUNT(DISTINCT `i`.`idingreso`) AS `num_compras` 
FROM 
    `caja` `c` 
    JOIN `usuario` `u` ON `c`.`idusuario` = `u`.`idusuario`
    LEFT JOIN `venta` `v` ON `v`.`idcaja` = `c`.`idcaja` AND `v`.`estado` = 'Aceptado'
    LEFT JOIN `ingreso` `i` ON `i`.`idcaja` = `c`.`idcaja` AND `i`.`estado` = 'Aceptado'
WHERE 
    `c`.`estado` = 'Abierta' 
GROUP BY 
    `c`.`idcaja`;

-- ======================================================================
-- 6. VISTA: v_historial_cajas
-- Descripción: Historial completo de todas las cajas (abiertas y cerradas)
-- ======================================================================

DROP VIEW IF EXISTS `v_historial_cajas`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `v_historial_cajas` AS 
SELECT 
    `c`.`idcaja` AS `idcaja`, 
    `c`.`idusuario` AS `idusuario`, 
    `u`.`nombre` AS `usuario`, 
    CAST(`c`.`fecha_apertura` AS DATE) AS `fecha`, 
    CAST(`c`.`fecha_apertura` AS TIME) AS `hora_apertura`, 
    CAST(`c`.`fecha_cierre` AS TIME) AS `hora_cierre`, 
    `c`.`monto_inicial` AS `monto_inicial`, 
    `c`.`monto_final` AS `monto_final`, 
    `c`.`total_ventas` AS `total_ventas`, 
    `c`.`total_compras` AS `total_compras`, 
    `c`.`monto_inicial` + `c`.`total_ventas` - `c`.`total_compras` AS `saldo_calculado`, 
    `c`.`monto_final` - (`c`.`monto_inicial` + `c`.`total_ventas` - `c`.`total_compras`) AS `diferencia`, 
    `c`.`estado` AS `estado`, 
    COUNT(DISTINCT `v`.`idventa`) AS `num_ventas`, 
    COUNT(DISTINCT `i`.`idingreso`) AS `num_compras` 
FROM 
    `caja` `c` 
    JOIN `usuario` `u` ON `c`.`idusuario` = `u`.`idusuario`
    LEFT JOIN `venta` `v` ON `v`.`idcaja` = `c`.`idcaja` AND `v`.`estado` = 'Aceptado'
    LEFT JOIN `ingreso` `i` ON `i`.`idcaja` = `c`.`idcaja` AND `i`.`estado` = 'Aceptado'
GROUP BY 
    `c`.`idcaja` 
ORDER BY 
    `c`.`fecha_apertura` DESC;

COMMIT;

-- ======================================================================
-- FIN DEL SCRIPT
-- ======================================================================
