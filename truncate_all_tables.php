<?php

require_once __DIR__ . '/vendor/autoload.php';

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    
    echo "Truncating all tables...\n";
    
    // Disable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Get all tables
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        $db->exec("TRUNCATE TABLE `{$table}`");
        echo "  Truncated: {$table}\n";
    }
    
    // Enable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "\nAll tables truncated successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}