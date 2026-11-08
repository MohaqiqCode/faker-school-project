<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class PermissionGenerator
{
    private PDO $db;
    private array $permissions = [];
    
    private const MODULES = [
        'user_management', 'student_management', 'teacher_management',
        'attendance', 'examination', 'finance', 'library',
        'transport', 'inventory', 'notification', 'report', 'settings'
    ];
    
    private const ACTIONS = ['view', 'create', 'edit', 'delete', 'export', 'import', 'approve'];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(): array
    {
//         if ($this->db->inTransaction()) {
//     $this->db->rollBack();
// }
        $this->db->beginTransaction();
        
        $stmt = $this->db->prepare("
            INSERT INTO permissions (name, display_name, module, description)
            VALUES (?, ?, ?, ?)
        ");
        
        foreach (self::MODULES as $module) {
            foreach (self::ACTIONS as $action) {
                $name = "{$module}.{$action}";
                $displayName = ucfirst($action) . ' ' . ucwords(str_replace('_', ' ', $module));
                $description = "Allows user to {$action} " . str_replace('_', ' ', $module);
                
                $stmt->execute([$name, $displayName, $module, $description]);
                $this->permissions[$name] = (int)$this->db->lastInsertId();
            }
        }
        
        $this->db->commit();
        
        return $this->permissions;
    }
    
    public function assignPermissions(array $roles, array $permissions): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO role_permission (role_id, permission_id)
            VALUES (?, ?)
        ");
        
        // Super Admin gets all permissions
        if (isset($roles['super_admin'])) {
            foreach ($permissions as $permId) {
                $stmt->execute([$roles['super_admin'], $permId]);
            }
        }
        
        // Admin gets most permissions
        if (isset($roles['admin'])) {
            $adminPermissions = array_filter($permissions, function($key) {
                return strpos($key, 'delete') === false && strpos($key, 'settings') === false;
            }, ARRAY_FILTER_USE_KEY);
            foreach ($adminPermissions as $permId) {
                $stmt->execute([$roles['admin'], $permId]);
            }
        }
        
        // Teacher permissions
        if (isset($roles['teacher'])) {
            $teacherModules = ['student_management.view', 'attendance.view', 'attendance.create', 'attendance.edit', 'examination.view', 'examination.create'];
            foreach ($teacherModules as $module) {
                if (isset($permissions[$module])) {
                    $stmt->execute([$roles['teacher'], $permissions[$module]]);
                }
            }
        }
    }
    
    public function getPermissions(): array
    {
        return $this->permissions;
    }
}