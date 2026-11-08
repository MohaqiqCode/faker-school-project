<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class AuditLogGenerator
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $userIds): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO audit_logs (user_id, action, module, description, old_values, new_values, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $this->db->beginTransaction();
        $logCount = 0;
        $batchSize = 1000;
        
        $modules = [
            'user_management', 'student_management', 'teacher_management',
            'attendance', 'examination', 'finance', 'library', 'transport', 'inventory'
        ];
        
        $actions = ['create', 'update', 'delete', 'view', 'login', 'logout', 'export', 'import'];
        
        // Generate 50,000 audit logs
        for ($i = 0; $i < 50000; $i++) {
            $userId = !empty($userIds) ? $userIds[array_rand($userIds)] : null;
            $action = $actions[array_rand($actions)];
            $module = $modules[array_rand($modules)];
            
            $oldValues = json_encode(['previous_state' => 'old_value_' . rand(1, 100)]);
            $newValues = json_encode(['new_state' => 'new_value_' . rand(1, 100)]);
            
            $ipAddresses = ['192.168.1.' . rand(1, 255), '10.0.0.' . rand(1, 255), '172.16.0.' . rand(1, 255)];
            
            $stmt->execute([
                $userId,
                $action,
                $module,
                "User performed {$action} on {$module}",
                $oldValues,
                $newValues,
                $ipAddresses[array_rand($ipAddresses)],
                'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]);
            
            $logCount++;
            
            if ($logCount % $batchSize === 0) {
                $this->db->commit();
                $this->db->beginTransaction();
                echo "  Generated {$logCount} audit logs...\n";
            }
        }
        
        $this->db->commit();
        echo "Total audit logs generated: {$logCount}\n";
    }
}