<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class SystemLogGenerator
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO system_logs (level, module, message, context)
            VALUES (?, ?, ?, ?)
        ");
        
//         if ($this->db->inTransaction()) {
//     $this->db->rollBack();
// }
        $this->db->beginTransaction();
        $logCount = 0;
        $batchSize = 1000;
        
        $levels = ['debug', 'info', 'warning', 'error', 'critical'];
        $modules = ['database', 'auth', 'cache', 'email', 'queue', 'scheduler', 'api', 'file'];
        
        $messages = [
            'debug' => ['SQL Query executed', 'Cache hit for key', 'File processed successfully'],
            'info' => ['User logged in', 'Report generated', 'Email sent successfully', 'Backup completed'],
            'warning' => ['High memory usage detected', 'Slow query detected', 'Cache miss', 'Disk space low'],
            'error' => ['Database connection failed', 'File not found', 'Permission denied', 'Email failed'],
            'critical' => ['System crash', 'Data corruption detected', 'Security breach attempt', 'Service unavailable']
        ];
        
        // Generate 25,000 system logs
        for ($i = 0; $i < 25000; $i++) {
            $level = $levels[array_rand($levels)];
            $module = $modules[array_rand($modules)];
            $levelMessages = $messages[$level];
            $message = $levelMessages[array_rand($levelMessages)];
            
            $context = json_encode([
                'timestamp' => date('Y-m-d H:i:s', strtotime('-' . rand(0, 365) . ' days')),
                'memory' => rand(100, 1000) . 'MB',
                'execution_time' => rand(10, 5000) . 'ms',
                'ip' => '192.168.' . rand(1, 255) . '.' . rand(1, 255)
            ]);
            
            $stmt->execute([
                strtoupper($level),
                $module,
                "{$message} in {$module} module",
                $context
            ]);
            
            $logCount++;
            
            if ($logCount % $batchSize === 0) {
                $this->db->commit();
                $this->db->beginTransaction();
                echo "  Generated {$logCount} system logs...\n";
            }
        }
        
        $this->db->commit();
        echo "Total system logs generated: {$logCount}\n";
    }
}