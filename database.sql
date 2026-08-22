-- MCPIL Pharmaceutical Laboratory Management System Database Schema

-- Create database
CREATE DATABASE IF NOT EXISTS mcpillab;
USE mcpillab;

-- Users table for authentication
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) DEFAULT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'manager', 'employee', 'store') DEFAULT 'employee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Employees table
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE,
    employee_id VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    department VARCHAR(50),
    position VARCHAR(50),
    hire_date DATE,
    salary DECIMAL(10,2),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Suppliers table
CREATE TABLE suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(50),
    country VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Purchase Orders table
CREATE TABLE purchase_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_number VARCHAR(20) UNIQUE NOT NULL,
    supplier_id INT NOT NULL,
    store_name VARCHAR(100) NOT NULL,
    order_date DATE NOT NULL,
    expected_delivery_date DATE,
    total_amount DECIMAL(12,2) NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Processing', 'Completed') DEFAULT 'Pending',
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Purchase Order Items table
CREATE TABLE purchase_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    description TEXT,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE
);

-- Purchase Order Messages table
CREATE TABLE purchase_order_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('admin', 'store') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Purchase Order Invoices table
CREATE TABLE purchase_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(20) UNIQUE NOT NULL,
    po_id INT NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE,
    amount DECIMAL(12,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    total_amount DECIMAL(12,2) NOT NULL,
    status ENUM('unpaid', 'partially_paid', 'paid') DEFAULT 'unpaid',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id)
);

-- Attendance table
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    check_in TIME,
    check_out TIME,
    break_duration INT DEFAULT 0, -- in minutes
    total_hours DECIMAL(4,2),
    status ENUM('present', 'absent', 'late', 'half_day') DEFAULT 'present',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    UNIQUE KEY unique_employee_date (employee_id, date)
);

-- Deliveries table
CREATE TABLE deliveries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_number VARCHAR(20) UNIQUE NOT NULL,
    po_id INT NOT NULL,
    supplier_id INT NOT NULL,
    delivery_date DATE NOT NULL,
    expected_date DATE,
    status ENUM('pending', 'in_transit', 'delivered', 'cancelled') DEFAULT 'pending',
    tracking_number VARCHAR(50),
    carrier VARCHAR(50),
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Delivery Items table
CREATE TABLE delivery_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_id INT NOT NULL,
    item_name VARCHAR(100) NOT NULL,
    quantity_ordered INT NOT NULL,
    quantity_delivered INT DEFAULT 0,
    quantity_pending INT NOT NULL,
    condition_status ENUM('good', 'damaged', 'missing') DEFAULT 'good',
    notes TEXT,
    FOREIGN KEY (delivery_id) REFERENCES deliveries(id) ON DELETE CASCADE
);

-- Camera Attendance table
CREATE TABLE camera_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    capture_date DATE NOT NULL,
    capture_time TIME NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    location_address TEXT,
    azimuth VARCHAR(20),
    temperature DECIMAL(5, 2),
    device_info TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    INDEX idx_employee_date (employee_id, capture_date)
);

-- Reports table (for storing generated reports)
CREATE TABLE reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(100) NOT NULL,
    report_type ENUM('purchase', 'delivery', 'attendance', 'employee', 'financial') NOT NULL,
    generated_by INT NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    file_path VARCHAR(255),
    parameters JSON,
    FOREIGN KEY (generated_by) REFERENCES users(id)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, full_name, role) VALUES 
(NULL, 'admin@mcpillab.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin'),
(NULL, 'store@mcpillab.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Store User', 'store');

-- Insert sample suppliers
INSERT INTO suppliers (supplier_code, name, contact_person, email, phone, address, city, country) VALUES 
('SUP001', 'MediSupply Co.', 'John Smith', 'john@medisupply.com', '+1-555-0101', '123 Pharma St', 'New York', 'USA'),
('SUP002', 'LabChem Inc.', 'Sarah Johnson', 'sarah@labchem.com', '+1-555-0102', '456 Chemical Ave', 'Boston', 'USA'),
('SUP003', 'BioTech Solutions', 'Michael Brown', 'michael@biotech.com', '+1-555-0103', '789 Bio Road', 'San Francisco', 'USA');

-- Insert sample employees
INSERT INTO employees (employee_id, first_name, last_name, email, phone, department, position, hire_date, salary) VALUES 
('EMP001', 'Alice', 'Williams', 'alice@mcpillab.com', '+1-555-0201', 'Laboratory', 'Lab Technician', '2023-01-15', 45000.00),
('EMP002', 'Bob', 'Johnson', 'bob@mcpillab.com', '+1-555-0202', 'Quality Control', 'QC Manager', '2022-06-10', 65000.00),
('EMP003', 'Carol', 'Davis', 'carol@mcpillab.com', '+1-555-0203', 'Purchasing', 'Purchasing Officer', '2023-03-20', 55000.00);
