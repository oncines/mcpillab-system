<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Check if PO ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid PO ID');
}

$po_id = $_GET['id'];

// Get PO details
$po_details = get_po_details($po_id);

if (!$po_details) {
    die('Purchase Order not found');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Purchase Order - <?php echo $po_details['po_number']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .po-info {
            margin-bottom: 20px;
        }
        .po-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .po-info td {
            padding: 5px;
            border: 1px solid #ddd;
        }
        .po-info td:first-child {
            font-weight: bold;
            width: 150px;
            background-color: #f5f5f5;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .items-table .text-right {
            text-align: right;
        }
        .total {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
        }
        .notes {
            margin-top: 20px;
            padding: 10px;
            background-color: #f9f9f9;
            border-left: 4px solid #333;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align: right; margin-bottom: 20px;">
        <button onclick="window.print()" class="btn btn-primary">Print</button>
        <button onclick="window.close()" class="btn btn-secondary">Close</button>
    </div>
    
    <div class="header">
        <h1><?php echo APP_NAME; ?></h1>
        <h2>PURCHASE ORDER</h2>
    </div>
    
    <div class="po-info">
        <table>
            <tr>
                <td>PO Number:</td>
                <td><?php echo $po_details['po_number']; ?></td>
                <td>Date:</td>
                <td><?php echo date('F j, Y', strtotime($po_details['order_date'])); ?></td>
            </tr>
            <tr>
                <td>Supplier:</td>
                <td><?php echo $po_details['supplier_name']; ?></td>
                <td>Expected Delivery:</td>
                <td><?php echo $po_details['expected_delivery_date'] ? date('F j, Y', strtotime($po_details['expected_delivery_date'])) : 'N/A'; ?></td>
            </tr>
            <tr>
                <td>Status:</td>
                <td><?php echo $po_details['status']; ?></td>
                <td>Created By:</td>
                <td><?php echo $po_details['created_by_name'] ?? 'N/A'; ?></td>
            </tr>
        </table>
    </div>
    
    <h3>Items</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Item Name</th>
                <th class="text-right">Quantity</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total = 0;
            if (isset($po_details['items']) && !empty($po_details['items'])) {
                foreach ($po_details['items'] as $index => $item):
                    $item_total = $item['quantity'] * $item['unit_price'];
                    $total += $item_total;
            ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo $item['item_name']; ?></td>
                    <td class="text-right"><?php echo $item['quantity']; ?></td>
                    <td class="text-right"><?php echo number_format($item['unit_price'], 2); ?></td>
                    <td class="text-right"><?php echo number_format($item_total, 2); ?></td>
                </tr>
            <?php 
                endforeach;
            } else {
            ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No items found</td>
                </tr>
            <?php } ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" style="text-align: right;">Total Amount:</th>
                <th class="text-right"><?php echo number_format($total, 2); ?></th>
            </tr>
        </tfoot>
    </table>
    
    <?php if (!empty($po_details['notes'])): ?>
    <div class="notes">
        <h4>Notes:</h4>
        <p><?php echo nl2br($po_details['notes']); ?></p>
    </div>
    <?php endif; ?>
    
    <div class="footer">
        <p>This is a computer-generated purchase order. No signature required.</p>
        <p>Generated on: <?php echo date('F j, Y g:i A'); ?></p>
    </div>
</body>
</html>
