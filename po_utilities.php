<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order Utilities</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .utility-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .utility-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #007bff;
        }
        .utility-card h3 {
            margin-top: 0;
            color: #007bff;
        }
        .utility-card p {
            color: #666;
            margin-bottom: 15px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-right: 10px;
            margin-bottom: 5px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Purchase Order System Utilities</h1>
        
        <div class="warning">
            <strong>Important:</strong> Use these utilities to set up and troubleshoot the Purchase Order system.
        </div>
        
        <div class="utility-grid">
            <div class="utility-card">
                <h3>🔧 Database Setup</h3>
                <p>Creates necessary tables and adds sample data if they don't exist.</p>
                <a href="setup_po_database.php" class="btn btn-success">Setup Database</a>
            </div>
            
            <div class="utility-card">
                <h3>🔄 Reset Database</h3>
                <p>Completely resets all PO tables and adds fresh sample data.</p>
                <a href="reset_po_database.php" class="btn btn-danger">Reset Database</a>
            </div>
            
            <div class="utility-card">
                <h3>👥 Check Suppliers</h3>
                <p>Verifies suppliers table exists and shows current suppliers.</p>
                <a href="check_suppliers.php" class="btn">Check Suppliers</a>
            </div>
            
            <div class="utility-card">
                <h3>📊 Check Tables</h3>
                <p>Shows structure and data count of all PO-related tables.</p>
                <a href="check_po_tables.php" class="btn">Check Tables</a>
            </div>
            
            <div class="utility-card">
                <h3>🔌 Test Connection</h3>
                <p>Tests database connection and shows all tables.</p>
                <a href="test_db_connection.php" class="btn">Test Connection</a>
            </div>
            
            <div class="utility-card">
                <h3>📝 Test PO Form</h3>
                <p>Simplified form to test PO creation with debug output.</p>
                <a href="test_po_form.php" class="btn">Test PO Form</a>
            </div>
            
            <div class="utility-card">
                <h3>🛍️ Purchase Orders</h3>
                <p>Go to the main Purchase Order management page.</p>
                <a href="purchase_order.php" class="btn">PO System</a>
            </div>
            
            <div class="utility-card">
                <h3>🏠 Dashboard</h3>
                <p>Return to the main dashboard.</p>
                <a href="dashboard.php" class="btn">Dashboard</a>
            </div>
        </div>
        
        <div style="margin-top: 30px; padding: 20px; background: #e9ecef; border-radius: 5px;">
            <h3>📋 Quick Setup Steps:</h3>
            <ol>
                <li>Run <strong>Setup Database</strong> to create tables and add sample data</li>
                <li>Use <strong>Test PO Form</strong> to verify creation works</li>
                <li>Log in with test credentials:
                    <ul>
                        <li>Admin: admin@mcpil.com / admin123</li>
                        <li>Store: store@mcpil.com / store123</li>
                    </ul>
                </li>
                <li>Start using the Purchase Order system</li>
            </ol>
        </div>
    </div>
</body>
</html>
