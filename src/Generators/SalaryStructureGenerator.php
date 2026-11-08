<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class SalaryStructureGenerator
{
    private PDO $db;
    private array $salaryStructures = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $branchIds): array
    {
//         if ($this->db->inTransaction()) {
//     $this->db->rollBack();
// }
        $this->db->beginTransaction();
        
        $stmt = $this->db->prepare("
            INSERT INTO salary_structures (branch_id, employee_type, salary_grade, basic_salary, house_allowance, medical_allowance, transport_allowance, other_allowances, deductions)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $teacherGrades = [
            'GRADE_1' => 8000,
            'GRADE_2' => 12000,
            'GRADE_3' => 20000,
            'GRADE_4' => 35000,
            'GRADE_5' => 60000,
        ];
        
        $staffGrades = [
            'GRADE_1' => 6000,
            'GRADE_2' => 10000,
            'GRADE_3' => 20000,
            'GRADE_4' => 28000,
            'GRADE_5' => 35000,
        ];
        
        foreach ($branchIds as $branchId) {
            // Teacher salary structures
            foreach ($teacherGrades as $grade => $basicSalary) {
                $stmt->execute([
                    $branchId,
                    'teacher',
                    $grade,
                    $basicSalary,
                    $basicSalary * 0.3, // House allowance 30%
                    $basicSalary * 0.1, // Medical allowance 10%
                    $basicSalary * 0.15, // Transport allowance 15%
                    $basicSalary * 0.05, // Other allowances 5%
                    $basicSalary * 0.05, // Deductions 5%
                ]);
                
                $this->salaryStructures["teacher_{$grade}"] = (int)$this->db->lastInsertId();
            }
            
            // Staff salary structures
            foreach ($staffGrades as $grade => $basicSalary) {
                $stmt->execute([
                    $branchId,
                    'staff',
                    $grade,
                    $basicSalary,
                    $basicSalary * 0.25, // House allowance 25%
                    $basicSalary * 0.08, // Medical allowance 8%
                    $basicSalary * 0.12, // Transport allowance 12%
                    $basicSalary * 0.05, // Other allowances 5%
                    $basicSalary * 0.04, // Deductions 4%
                ]);
                
                $this->salaryStructures["staff_{$grade}"] = (int)$this->db->lastInsertId();
            }
        }
        
        $this->db->commit();
        
        return $this->salaryStructures;
    }
    
    public function getSalaryStructures(): array
    {
        return $this->salaryStructures;
    }
}