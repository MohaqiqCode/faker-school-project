<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class DepartmentGenerator
{
    private PDO $db;
    private array $departments = [];
    
    private const DEPARTMENT_NAMES = [
        'Science Department', 'Mathematics Department', 'Languages Department',
        'Social Sciences Department', 'Islamic Studies Department', 'Computer Science Department',
        'Arts Department', 'Physical Education Department'
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $branchIds): array
    {
    //    if(!$this->db->inTransaction()){
    //         $this->db->beginTransaction();
    //    }
        $this->db->beginTransaction();
        
        $stmt = $this->db->prepare("
            INSERT INTO departments (branch_id, department_code, name, description, is_active)
            VALUES (?, ?, ?, ?, 1)
        ");
        
        foreach ($branchIds as $branchId) {
            foreach (self::DEPARTMENT_NAMES as $index => $name) {
                $deptCode = 'DEPT' . sprintf('%03d', $branchId) . sprintf('%02d', $index + 1);
                $stmt->execute([
                    $branchId,
                    $deptCode,
                    $name,
                    $name . ' responsible for curriculum and teaching'
                ]);
                
                $deptId = (int)$this->db->lastInsertId();
                $this->departments[$branchId][] = $deptId;
            }
        }
        
        $this->db->commit();
        
        return $this->departments;
    }
    
    public function getDepartments(): array
    {
        return $this->departments;
    }
}