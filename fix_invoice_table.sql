-- Fix the purchase_invoices table to allow standalone invoices
-- This makes po_id nullable so invoices can be created without a purchase order

ALTER TABLE purchase_invoices 
MODIFY COLUMN po_id INT NULL;

-- If you want to set existing NULL values to a default, you can run:
-- UPDATE purchase_invoices SET po_id = NULL WHERE po_id = 0;
