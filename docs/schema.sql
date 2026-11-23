-- =====================================================================
-- Base de Datos: food_manager
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `food_manager`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `food_manager`;

-- 1. Tabla de Usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id_usuario` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `correo` VARCHAR(150) NOT NULL UNIQUE,
  `contrasena_hash` VARCHAR(255) NOT NULL,
  `telefono` VARCHAR(20) NULL,
  `rol` ENUM('admin', 'cliente') NOT NULL DEFAULT 'cliente',
  `fecha_registro` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabla de Mesas
CREATE TABLE IF NOT EXISTS `mesas` (
  `id_mesa` INT AUTO_INCREMENT PRIMARY KEY,
  `numero_mesa` INT NOT NULL UNIQUE,
  `capacidad` INT NOT NULL DEFAULT 4,
  `estado` ENUM('disponible', 'ocupada', 'inactiva') NOT NULL DEFAULT 'disponible'
);

-- 3. Tabla de Platillos
CREATE TABLE IF NOT EXISTS `platillos` (
  `id_platillo` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(120) NOT NULL,
  `descripcion` TEXT NULL,
  `precio` DECIMAL(10, 2) NOT NULL,
  `categoria` VARCHAR(60) NOT NULL,
  `disponible` TINYINT(1) NOT NULL DEFAULT 1,
  `imagen` VARCHAR(255) NULL
);

-- 4. Tabla de Ingredientes
CREATE TABLE IF NOT EXISTS `ingredientes` (
  `id_ingrediente` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL UNIQUE,
  `cantidad_stock` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `unidad_medida` VARCHAR(30) NOT NULL,
  `stock_minimo` DECIMAL(10, 2) NOT NULL DEFAULT 0.00
);

-- 5. Tabla de Recetas (PK compuesta id_platillo, id_ingrediente)
CREATE TABLE IF NOT EXISTS `recetas` (
  `id_platillo` INT NOT NULL,
  `id_ingrediente` INT NOT NULL,
  `cantidad_requerida` DECIMAL(10, 2) NOT NULL,
  PRIMARY KEY (`id_platillo`, `id_ingrediente`),
  FOREIGN KEY (`id_platillo`) REFERENCES `platillos` (`id_platillo`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_ingrediente`) REFERENCES `ingredientes` (`id_ingrediente`)
    ON DELETE RESTRICT ON UPDATE CASCADE
);

-- 6. Tabla de Pedidos
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id_pedido` INT AUTO_INCREMENT PRIMARY KEY,
  `id_usuario` INT NOT NULL,
  `id_mesa` INT NULL,
  `fecha_pedido` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado` ENUM('pendiente', 'preparando', 'entregado', 'pagado', 'cancelado') NOT NULL DEFAULT 'pendiente',
  `total` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
  `fecha_cierre` DATETIME NULL,
  FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id_usuario`)
    ON DELETE RESTRICT ON UPDATE CASCADE,
  FOREIGN KEY (`id_mesa`) REFERENCES `mesas` (`id_mesa`)
    ON DELETE SET NULL ON UPDATE CASCADE
);

-- 7. Tabla de Detalle de Pedido
CREATE TABLE IF NOT EXISTS `detalle_pedido` (
  `id_detalle` INT AUTO_INCREMENT PRIMARY KEY,
  `id_pedido` INT NOT NULL,
  `id_platillo` INT NOT NULL,
  `cantidad` INT NOT NULL DEFAULT 1,
  `precio_unitario` DECIMAL(10, 2) NOT NULL,
  `subtotal` DECIMAL(10, 2) NOT NULL,
  FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id_pedido`)
    ON DELETE CASCADE ON UPDATE CASCADE,
  FOREIGN KEY (`id_platillo`) REFERENCES `platillos` (`id_platillo`)
    ON DELETE RESTRICT ON UPDATE CASCADE
);

-- Administrador inicial (correo: admin@foodmanager.com | contraseña: Admin123)
INSERT IGNORE INTO `usuarios` (`nombre`, `correo`, `contrasena_hash`, `telefono`, `rol`) VALUES
('Administrador', 'admin@foodmanager.com', '$2y$12$HbuCUvWrt0P4Le8G36BcO.L2J9fdLw4k1pq/bKIfQRRsda4jA7Eie', NULL, 'admin');
