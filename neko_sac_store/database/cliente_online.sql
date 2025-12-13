-- =====================================================
-- TABLA DE CLIENTES ONLINE (para tienda e-commerce)
-- Ejecutar en la base de datos bd_ferreteria
-- =====================================================

CREATE TABLE IF NOT EXISTS cliente_online (
    idcliente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) DEFAULT NULL,
    email VARCHAR(200) NOT NULL UNIQUE,
    telefono VARCHAR(20) DEFAULT NULL,
    direccion TEXT DEFAULT NULL,
    password_hash VARCHAR(255) DEFAULT NULL,
    
    -- OAuth fields
    oauth_provider ENUM('google', 'facebook', 'local') DEFAULT 'local',
    oauth_id VARCHAR(255) DEFAULT NULL,
    avatar_url VARCHAR(500) DEFAULT NULL,
    
    -- Status
    activo TINYINT(1) DEFAULT 1,
    email_verificado TINYINT(1) DEFAULT 0,
    
    -- Timestamps
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso DATETIME DEFAULT NULL,
    fecha_actualizacion DATETIME ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_email (email),
    INDEX idx_oauth (oauth_provider, oauth_id),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar relación a pedidos online
ALTER TABLE pedido_online 
ADD COLUMN idcliente INT DEFAULT NULL AFTER idpedido,
ADD CONSTRAINT fk_pedido_cliente 
    FOREIGN KEY (idcliente) REFERENCES cliente_online(idcliente) ON DELETE SET NULL;
