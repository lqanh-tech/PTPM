<?php
/**
 * Test Support Tickets API
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'lequocanh/administrator/elements_LQA/mod/sessionManager.php';
require_once 'lequocanh/administrator/elements_LQA/mod/database.php';

SessionManager::start();

echo "<h1>Test Support Tickets API</h1>";

// Check session
echo "<h2>Session Info:</h2>";
echo "<pre>";
echo "USER: " . ($_SESSION['USER'] ?? 'NOT SET') . "\n";
echo "ADMIN: " . ($_SESSION['ADMIN'] ?? 'NOT SET') . "\n";
echo "</pre>";

// Test database connection
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    echo "<p style='color:green'>✓ Database connected</p>";
    
    // Check tables exist
    $tables = ['support_tickets', 'support_messages'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color:green'>✓ Table '$table' exists</p>";
        } else {
            echo "<p style='color:red'>✗ Table '$table' NOT FOUND</p>";
        }
    }
    
    // Check view exists
    $stmt = $conn->query("SHOW FULL TABLES WHERE Table_type = 'VIEW' AND Tables_in_" . $conn->query("SELECT DATABASE()")->fetchColumn() . " LIKE 'v_support_tickets_list'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✓ View 'v_support_tickets_list' exists</p>";
    } else {
        echo "<p style='color:orange'>⚠ View 'v_support_tickets_list' NOT FOUND - will create</p>";
        
        // Create view
        $createView = "CREATE OR REPLACE VIEW `v_support_tickets_list` AS
        SELECT 
            st.*,
            st.user_id as user_name,
            (SELECT COUNT(*) FROM support_messages WHERE ticket_id = st.id) as message_count,
            (SELECT COUNT(*) FROM support_messages WHERE ticket_id = st.id AND is_read = 0 AND sender_type = 'user') as unread_count,
            (SELECT created_at FROM support_messages WHERE ticket_id = st.id ORDER BY created_at DESC LIMIT 1) as last_message_at
        FROM support_tickets st";
        $conn->exec($createView);
        echo "<p style='color:green'>✓ View created</p>";
    }
    
    // Get tickets
    echo "<h2>Tickets:</h2>";
    $stmt = $conn->query("SELECT * FROM support_tickets ORDER BY created_at DESC LIMIT 5");
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($tickets, true) . "</pre>";
    
    // Get messages for first ticket
    if (!empty($tickets)) {
        $ticketId = $tickets[0]['id'];
        echo "<h2>Messages for Ticket #$ticketId:</h2>";
        $stmt = $conn->prepare("SELECT * FROM support_messages WHERE ticket_id = ? ORDER BY created_at ASC");
        $stmt->execute([$ticketId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>" . print_r($messages, true) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>Test API Endpoints:</h2>";
echo "<p>Open browser console and run these tests:</p>";
echo "<pre>";
echo "// Test user_list
fetch('../api/support_tickets.php?action=user_list', {credentials: 'include'})
  .then(r => r.json())
  .then(console.log);

// Test admin_list  
fetch('../api/support_tickets.php?action=admin_list', {credentials: 'include'})
  .then(r => r.json())
  .then(console.log);

// Test details (replace 1 with actual ticket_id)
fetch('../api/support_tickets.php?action=details&ticket_id=1', {credentials: 'include'})
  .then(r => r.json())
  .then(console.log);
";
echo "</pre>";
?>
