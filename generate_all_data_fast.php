<?php

require_once __DIR__ . '/vendor/autoload.php';

// Set memory and execution limits
ini_set('memory_limit', '4096M');
ini_set('max_execution_time', '53600'); // Unlimited
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load .env manually
if (file_exists(__DIR__ . '/.env')) {
    $envContent = file_get_contents(__DIR__ . '/.env');
    $lines = explode("\n", $envContent);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

echo "============================================\n";
echo "FAST DATA GENERATION - REDUCED VOLUME\n";
echo "============================================\n\n";

try {
    $db = \App\Core\Database::getInstance()->getConnection();
    
    // غیرفعال کردن foreign key checks برای سرعت بیشتر
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    $db->exec("SET UNIQUE_CHECKS = 0");
    $db->exec("SET AUTOCOMMIT = 0");
    
    echo "Database optimized for bulk insert!\n\n";
    
    // Run the seeder
    // $seeder = new \src\Seeders\DatabaseSeederFast();
     $seeder = new \App\Seeders\DatabaseSeederFast();
    $seeder->run();
    
    // فعال کردن مجدد
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    $db->exec("SET UNIQUE_CHECKS = 1");
    $db->exec("SET AUTOCOMMIT = 1");
    
    echo "\n============================================\n";
    echo "DATA GENERATION COMPLETED!\n";
    echo "============================================\n";
    
} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
    exit(1);
}