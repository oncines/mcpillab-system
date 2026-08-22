# Purchase Order Fixes

## Issues Fixed

1. **Function Parameter Mismatch**: The `create_purchase_order` and `update_purchase_order` functions were expecting `$supplier_name` but the form was passing `$supplier_id`. This has been fixed to use `$supplier_id` consistently.

2. **Missing Supplier Name in Queries**: The purchase order listing functions were not selecting the supplier name, causing empty supplier columns. Added LEFT JOIN with suppliers table to include supplier names.

3. **Missing editPO Function**: Added the missing `editPO` JavaScript function that was referenced but not implemented.

## Files Modified

- `functions.php`:
  - Fixed `create_purchase_order()` function parameters
  - Fixed `update_purchase_order()` function parameters
  - Updated `get_purchase_orders_admin()` to include supplier name
  - Updated `get_purchase_orders_store()` to include supplier name
  - Updated `get_po_details()` to include supplier name

- `purchase_order.php`:
  - Added missing `editPO()` JavaScript function

## Files Added

- `add_sample_suppliers.sql` - SQL script to add sample suppliers
- `test_po_creation.php` - Test script to verify PO creation works
- `run_po_test.html` - HTML interface to run the test

## Setup Instructions

1. First, run the SQL script to add sample suppliers:
   ```sql
   -- Execute add_sample_suppliers.sql in your database
   ```

2. Test the functionality:
   - Open `run_po_test.html` in your browser
   - Click "Run Test" to verify everything works
   - Or directly access `test_po_creation.php`

3. Use the Purchase Order system:
   - Log in as a store user
   - Go to `purchase_order.php`
   - Create a new purchase order with the sample suppliers

## Database Schema Requirements

Make sure your database has the following tables:
- `suppliers` (id, supplier_code, name, contact_person, email, phone, address, city, country, status)
- `purchase_orders` (id, po_number, supplier_id, store_name, order_date, expected_delivery_date, total_amount, status, notes, created_by)
- `purchase_order_items` (id, po_id, item_name, description, quantity, unit_price, total_price)

## Notes

- The system now properly uses supplier_id instead of supplier_name
- All purchase order listings now show supplier names
- The edit functionality is now properly implemented
- Test scripts are provided to verify the fixes
