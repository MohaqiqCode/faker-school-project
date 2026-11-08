<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class ClassGenerator
{
    private PDO $db;
    private array $classes = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $branchIds): array
    {
        $this->db->beginTransaction();
        
        $stmt = $this->db->prepare("
            INSERT INTO classes (branch_id, class_name, class_level, class_code, description, capacity, is_active)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        
        foreach ($branchIds as $branchId) {
            for ($grade = 1; $grade <= 12; $grade++) {
                $className = "Grade {$grade}";
                $classCode = "GRD{$grade}BR{$branchId}";
                $capacity = 40 + ($grade * 2);
                
                $stmt->execute([
                    $branchId,
                    $className,
                    $grade,
                    $classCode,
                    "Grade {$grade} Class",
                    $capacity
                ]);
                
                $classId = (int)$this->db->lastInsertId();
                $this->classes[$branchId][$grade] = $classId;
            }
        }
        
        $this->db->commit();
        
        return $this->classes;
    }
    
    public function getClasses(): array
    {
        return $this->classes;
    }
}