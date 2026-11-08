<?php

require_once __DIR__ . '/vendor/autoload.php';

// Set memory and execution limits
ini_set('memory_limit', '8048M');
// ini_set('max_execution_time', '3600');
ini_set('max_execution_time', '53600');
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Load .env manually if Dotenv is not working
if (file_exists(__DIR__ . '/.env')) {
    $envContent = file_get_contents(__DIR__ . '/.env');
    $lines = explode("\n", $envContent);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
    echo "Environment variables loaded manually!\n";
}

echo "============================================\n";
echo "AFGHAN SCHOOL MANAGEMENT SYSTEM\n";
echo "DATA WAREHOUSE POPULATION SCRIPT\n";
echo "============================================\n\n";

try {
    // Test database connection
    echo "Testing database connection...\n";
    $db = \App\Core\Database::getInstance()->getConnection();
    echo "Database connection successful!\n\n";
    
    // Run the seeder
    $seeder = new \App\Seeders\DatabaseSeeder();
    $seeder->run();
    
    echo "\n============================================\n";
    echo "DATA GENERATION COMPLETED SUCCESSFULLY!\n";
    echo "============================================\n";
    
} catch (Exception $e) {
    echo "\n============================================\n";
    echo "ERROR OCCURRED!\n";
    echo "============================================\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}