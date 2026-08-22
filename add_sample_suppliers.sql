-- Add sample suppliers if they don't exist
INSERT IGNORE INTO suppliers (supplier_code, name, contact_person, email, phone, address, city, country) VALUES
('MED001', 'MediCorp Pharmaceuticals', 'John Smith', 'john@medicorp.com', '555-0101', '123 Medical St', 'New York', 'USA'),
('PHAR002', 'PharmaTech Solutions', 'Sarah Johnson', 'sarah@pharmatech.com', '555-0102', '456 Pharma Ave', 'Los Angeles', 'USA'),
('LAB003', 'LabSupply Co.', 'Mike Wilson', 'mike@labsupply.com', '555-0103', '789 Lab Road', 'Chicago', 'USA'),
('CHEM004', 'ChemPro Industries', 'Dr. Robert Brown', 'robert@chempro.com', '555-0104', '321 Chemical Blvd', 'Houston', 'USA'),
('EQP005', 'Equipment Plus', 'Lisa Davis', 'lisa@equipmentplus.com', '555-0105', '654 Equipment Way', 'Phoenix', 'USA');
