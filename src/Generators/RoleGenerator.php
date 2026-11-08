<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class RoleGenerator
{
    private PDO $db;
    private array $roles = [];
    
    private const ROLE_DATA = [
        ['name' => 'super_admin', 'display_name' => 'Super Administrator', 'description' => 'Full system access across all branches'],
        ['name' => 'admin', 'display_name' => 'Administrator', 'description' => 'Branch level administration'],
        ['name' => 'principal', 'display_name' => 'Principal', 'description' => 'School principal with management access'],
        ['name' => 'vice_principal', 'display_name' => 'Vice Principal', 'description' => 'Assistant to principal'],
        ['name' => 'teacher', 'display_name' => 'Teacher', 'description' => 'Teaching staff access'],
        ['name' => 'head_teacher', 'display_name' => 'Head Teacher', 'description' => 'Department head teacher'],
        ['name' => 'accountant', 'display_name' => 'Accountant', 'description' => 'Finance and fee management'],
        ['name' => 'librarian', 'display_name' => 'Librarian', 'description' => 'Library management access'],
        ['name' => 'transport_manager', 'display_name' => 'Transport Manager', 'description' => 'Transport management access'],
        ['name' => 'parent', 'display_name' => 'Parent', 'description' => 'Parent portal access'],
        ['name' => 'student', 'display_name' => 'Student', 'description' => 'Student portal access'],
        ['name' => 'receptionist', 'display_name' => 'Receptionist', 'description' => 'Front desk operations'],
        ['name' => 'inventory_manager', 'display_name' => 'Inventory Manager', 'description' => 'Inventory and asset management'],
    ];
    
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
        
        // Use INSERT IGNORE to skip duplicates
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO roles (name, display_name, description)
            VALUES (?, ?, ?)
        ");
        
        foreach (self::ROLE_DATA as $data) {
            $stmt->execute([
                $data['name'],
                $data['display_name'],
                $data['description']
            ]);
            
            // Get the ID (whether newly inserted or existing)
            $getIdStmt = $this->db->prepare("SELECT id FROM roles WHERE name = ?");
            $getIdStmt->execute([$data['name']]);
            $result = $getIdStmt->fetch();
            
            if ($result) {
                $this->roles[$data['name']] = (int)$result['id'];
            }
        }
        
        $this->db->commit();
        
        return $this->roles;
    }
    
    public function getRoles(): array
    {
        return $this->roles;
    }
}