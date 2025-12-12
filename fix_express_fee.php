<?php
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';
$db = Database::getInstance()->getConnection();
$db->exec('UPDATE shipping_fees SET base_fee = 45000 WHERE shipping_method_id = (SELECT id FROM shipping_methods WHERE code = "express")');
echo "✅ Updated express fee to 45,000₫\n";
