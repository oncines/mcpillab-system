-- Inventory Management Tables for MCPIL

-- Inventory Items table
CREATE TABLE IF NOT EXISTS inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(200) NOT NULL,
    barcode VARCHAR(100) UNIQUE,
    size VARCHAR(50),
    unit VARCHAR(20) NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50),
    supplier_id INT,
    location VARCHAR(100),
    min_stock_level INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- Inventory Transactions table (for tracking stock movements)
CREATE TABLE IF NOT EXISTS inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    transaction_type ENUM('beginning', 'delivery', 'adjustment', 'sale', 'return') NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    bodega_quantity DECIMAL(10,2) DEFAULT 0,
    shelves_quantity DECIMAL(10,2) DEFAULT 0,
    delivery_quantity DECIMAL(10,2) DEFAULT 0,
    unit_price DECIMAL(10,2),
    reference_number VARCHAR(50),
    notes TEXT,
    transaction_date DATE NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Inventory Stock Summary table (current stock levels)
CREATE TABLE IF NOT EXISTS inventory_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT UNIQUE NOT NULL,
    beginning_stock DECIMAL(10,2) DEFAULT 0,
    bodega_stock DECIMAL(10,2) DEFAULT 0,
    shelves_stock DECIMAL(10,2) DEFAULT 0,
    delivery_stock DECIMAL(10,2) DEFAULT 0,
    total_stock DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(12,2) DEFAULT 0,
    suggested_order DECIMAL(10,2) DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES inventory_items(id)
);

-- Insert sample inventory items
INSERT INTO inventory_items (item_name, barcode, size, unit, unit_price, category, supplier_id, location, min_stock_level) VALUES 
('ACEITE DE ALCAMPORADO', 'BAR001', '100ml', 'bottle', 150.00, 'chemicals', 1, 'Bodega-A1', 10),
('ACETONE', 'BAR002', '500ml', 'bottle', 89.50, 'chemicals', 2, 'Bodega-A2', 15),
('AGUA OXIGENADA', 'BAR003', '250ml', 'bottle', 45.75, 'chemicals', 1, 'Shelves-B1', 20),
('ALCOHOL', 'BAR004', '1L', 'bottle', 120.00, 'chemicals', 2, 'Bodega-A3', 12),
('BENZALKONIUM CHLORIDE', 'BAR005', '500ml', 'bottle', 200.00, 'chemicals', 3, 'Shelves-B2', 8),
('GENTIAN VIOLET', 'BAR006', '50ml', 'bottle', 75.25, 'chemicals', 1, 'Bodega-A4', 10),
('LOVELY BABY OIL', 'BAR007', '200ml', 'bottle', 95.50, 'consumables', 1, 'Shelves-B3', 15),
('MEGADONE [POVIDONE IODINE]', 'BAR008', '500ml', 'bottle', 180.00, 'chemicals', 2, 'Bodega-A5', 10),
('MCSON SCENT', 'BAR009', '100ml', 'bottle', 65.00, 'consumables', 3, 'Shelves-B4', 20),
('OIL OF WINTERGREEN', 'BAR010', '50ml', 'bottle', 85.75, 'chemicals', 1, 'Bodega-A6', 12),
('PURE TAWAS POWDER', 'BAR011', '100g', 'box', 35.50, 'consumables', 2, 'Shelves-B5', 25),
('REFINED MINERAL OIL [CLASS-A]', 'BAR012', '1L', 'bottle', 110.00, 'chemicals', 3, 'Bodega-A7', 10),
('SALICYLIC ACID 10%', 'BAR013', '250ml', 'bottle', 145.00, 'chemicals', 1, 'Shelves-B6', 8),
('CARE BABY OIL', 'BAR014', '200ml', 'bottle', 88.00, 'consumables', 2, 'Bodega-A8', 15),
('MURIATIC ACID', 'BAR015', '500ml', 'bottle', 95.00, 'chemicals', 3, 'Shelves-B7', 10),
('MURIATICA', 'BAR016', '1L', 'bottle', 125.00, 'chemicals', 1, 'Bodega-A9', 8),
('RUGBY', 'BAR017', '100ml', 'bottle', 55.50, 'consumables', 2, 'Shelves-B8', 20);

-- Initialize stock levels
INSERT INTO inventory_stock (item_id, beginning_stock, bodega_stock, shelves_stock, delivery_stock, total_stock, suggested_order) 
SELECT 
    id,
    FLOOR(RAND() * 50 + 20) as beginning_stock,
    FLOOR(RAND() * 30 + 10) as bodega_stock,
    FLOOR(RAND() * 20 + 5) as shelves_stock,
    FLOOR(RAND() * 10 + 2) as delivery_stock,
    0 as total_stock,
    CASE 
        WHEN FLOOR(RAND() * 50 + 20) < min_stock_level THEN FLOOR(min_stock_level * 1.5)
        ELSE 0
    END as suggested_order
FROM inventory_items;

-- Update total_stock and total_amount
UPDATE inventory_stock 
SET 
    total_stock = beginning_stock + bodega_stock + shelves_stock + delivery_stock,
    total_amount = (SELECT total_stock * unit_price FROM inventory_items ii WHERE ii.id = inventory_stock.item_id);
