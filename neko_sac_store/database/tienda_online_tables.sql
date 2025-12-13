-- =====================================================
-- TABLAS PARA LA TIENDA ONLINE (NEKO SAC Store)
-- Ejecutar en la base de datos bd_ferreteria
-- =====================================================

-- Tabla de pedidos online
CREATE TABLE IF NOT EXISTS pedido_online (
    idpedido INT AUTO_INCREMENT PRIMARY KEY,
    codigo_pedido VARCHAR(50) NOT NULL UNIQUE,
    nombre_cliente VARCHAR(200) NOT NULL,
    email_cliente VARCHAR(200) NOT NULL,
    telefono_cliente VARCHAR(20) NOT NULL,
    direccion_entrega TEXT NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    igv DECIMAL(12,2) NOT NULL DEFAULT 0,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    metodo_pago ENUM('yape', 'tarjeta', 'efectivo') DEFAULT 'yape',
    notas_cliente TEXT,
    estado_pedido ENUM('pendiente', 'confirmado', 'enviado', 'entregado', 'cancelado') DEFAULT 'pendiente',
    estado_pago ENUM('pendiente', 'pagado', 'rechazado', 'reembolsado') DEFAULT 'pendiente',
    ip_cliente VARCHAR(45),
    user_agent TEXT,
    fecha_pedido DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_pago DATETIME,
    fecha_actualizacion DATETIME ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de detalles del pedido
CREATE TABLE IF NOT EXISTS detalle_pedido_online (
    iddetalle INT AUTO_INCREMENT PRIMARY KEY,
    idpedido INT NOT NULL,
    idarticulo INT NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(12,2) NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (idpedido) REFERENCES pedido_online(idpedido) ON DELETE CASCADE,
    FOREIGN KEY (idarticulo) REFERENCES articulo(idarticulo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla de transacciones de pago
CREATE TABLE IF NOT EXISTS transaccion_pago (
    idtransaccion INT AUTO_INCREMENT PRIMARY KEY,
    idpedido INT NOT NULL,
    metodo_pago ENUM('yape', 'tarjeta', 'efectivo') NOT NULL,
    codigo_transaccion VARCHAR(100) NOT NULL UNIQUE,
    monto DECIMAL(12,2) NOT NULL,
    estado ENUM('pendiente', 'aprobado', 'rechazado', 'reembolsado') DEFAULT 'pendiente',
    mensaje_respuesta TEXT,
    fecha_procesado DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idpedido) REFERENCES pedido_online(idpedido) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para pagos Yape (simulados)
CREATE TABLE IF NOT EXISTS pago_yape_simulado (
    idpago INT AUTO_INCREMENT PRIMARY KEY,
    idtransaccion INT NOT NULL,
    numero_operacion VARCHAR(50) NOT NULL,
    telefono_origen VARCHAR(20),
    nombre_pagador VARCHAR(200),
    fecha_operacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idtransaccion) REFERENCES transaccion_pago(idtransaccion) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para pagos con tarjeta (simulados)
CREATE TABLE IF NOT EXISTS pago_tarjeta_simulado (
    idpago INT AUTO_INCREMENT PRIMARY KEY,
    idtransaccion INT NOT NULL,
    ultimos_digitos VARCHAR(4) NOT NULL,
    tipo_tarjeta ENUM('visa', 'mastercard', 'amex', 'otro') DEFAULT 'visa',
    nombre_titular VARCHAR(200),
    fecha_expiracion VARCHAR(7),
    codigo_autorizacion VARCHAR(50),
    FOREIGN KEY (idtransaccion) REFERENCES transaccion_pago(idtransaccion) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Índices para mejorar rendimiento
CREATE INDEX idx_pedido_fecha ON pedido_online(fecha_pedido);
CREATE INDEX idx_pedido_estado ON pedido_online(estado_pedido, estado_pago);
CREATE INDEX idx_pedido_codigo ON pedido_online(codigo_pedido);
CREATE INDEX idx_transaccion_pedido ON transaccion_pago(idpedido);
