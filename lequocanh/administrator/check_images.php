<?php

/**
 * Image Path Checker Script
 * This script checks if image paths are working correctly
 */

echo "<h2>Image Path Checker</h2>";
echo "<hr>";

// Test paths
$testPaths = [
    "lequocanh/administrator/elements_LQA/img_LQA/no-image.png",
    "lequocanh/administrator/uploads/",
    "lequocanh/administrator/elements_LQA/img_LQA/"
];

echo "<h3>File System Check:</h3>";
foreach ($testPaths as $path) {
    if (file_exists($path)) {
        if (is_dir($path)) {
            echo "<p style='color: green;'>✓ Directory exists: $path</p>";
            // List files in directory
            $files = scandir($path);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    echo "<p style='margin-left: 20px;'>- $file</p>";
                }
            }
        } else {
            echo "<p style='color: green;'>✓ File exists: $path</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Path does not exist: $path</p>";
    }
}

echo "<hr>";
echo "<h3>GD Extension Check:</h3>";
if (extension_loaded('gd') && function_exists('gd_info')) {
    echo "<p style='color: green;'>✓ GD Extension is available</p>";
    $gdInfo = gd_info();
    echo "<p>GD Version: " . $gdInfo['GD Version'] . "</p>";
} else {
    echo "<p style='color: orange;'>⚠ GD Extension is not available - using fallback images</p>";
}

echo "<hr>";
echo "<p><a href='index.php'>← Back to Home</a></p>";
