<?php
/**
 * STANDALONE TEST - Shipping Method Selector Isolated Test
 */
session_start();

// Set fake session data
$_SESSION['cart_weight'] = 1.0;
$_SESSION['cart_total'] = 100000;
$_SESSION['province_id'] = 1;
$_SESSION['district_id'] = 1;
$_SESSION['subtotal'] = 100000;
$_SESSION['vat_amount'] = 10000;
$_SESSION['shipping_fee'] = 0;

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Shipping Methods</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body style="padding: 20px; background: #f5f5f5;">
    <div class="container">
        <h2>Isolated Test - Shipping Method Selector V2</h2>
        <p><strong>Purpose:</strong> This page includes ONLY the shipping_method_selector_v2.php without any other interference</p>
        
        <hr>
        
        <?php 
        // Include the shipping method selector
        include 'shipping_method_selector_v2.php'; 
        ?>
        
        <hr>
        
        <h3>Debug Information</h3>
        <p>View Page Source (Ctrl+U) to see HTML comments showing which methods were rendered.</p>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
