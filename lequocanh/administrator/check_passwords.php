<?php

require_once './lequocanh/administrator/elements_LQA/mod/database.php';
require_once './lequocanh/administrator/elements_LQA/mod/PasswordHelper.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Password Status Check</h2>";
echo "<hr>";

try {
    $db = Database::getInstance()->getConnection();

    $sql = "SELECT iduser, username, password FROM user";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalUsers = count($users);
    $plaintextCount = 0;
    $hashedCount = 0;

    echo "<p>Total users found: <strong>$totalUsers</strong></p>";
    echo "<hr>";

    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Username</th><th>Password Status</th><th>Password Preview</th></tr>";

    foreach ($users as $user) {
        $iduser = $user['iduser'];
        $username = $user['username'];
        $password = $user['password'];

        $isPlainText = PasswordHelper::isPlainText($password);

        if ($isPlainText) {
            $status = "<span style='color: orange;'>PLAIN TEXT</span>";
            $plaintextCount++;
        } else {
            $status = "<span style='color: green;'>HASHED</span>";
            $hashedCount++;
        }

        $preview = substr($password, 0, 20) . (strlen($password) > 20 ? '...' : '');

        echo "<tr>";
        echo "<td>$iduser</td>";
        echo "<td>$username</td>";
        echo "<td>$status</td>";
        echo "<td>$preview</td>";
        echo "</tr>";
    }

    echo "</table>";

    echo "<hr>";
    echo "<h3>Summary:</h3>";
    echo "<ul>";
    echo "<li><strong>Total users:</strong> $totalUsers</li>";
    echo "<li style='color: orange;'><strong>Plain text passwords:</strong> $plaintextCount</li>";
    echo "<li style='color: green;'><strong>Hashed passwords:</strong> $hashedCount</li>";
    echo "</ul>";

    if ($plaintextCount > 0) {
        echo "<p style='color: orange; font-weight: bold;'>⚠ You have $plaintextCount plain text password(s) that need to be migrated.</p>";
        echo "<p><a href='migrate_passwords.php' style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Run Migration Now</a></p>";
    } else {
        echo "<p style='color: green; font-weight: bold;'>✓ All passwords are properly hashed.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Back to Home</a></p>";
