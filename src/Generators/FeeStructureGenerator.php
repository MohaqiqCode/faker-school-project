<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class FeeStructureGenerator
{
    private PDO $db;
    private array $feeStructures = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $feeCategories, array $classes, array $academicYears): array
    {
//         if ($this->db->inTransaction()) {
//     $this->db->rollBack();
// }
        $this->db->beginTransaction();
        
        $stmt = $this->db->prepare("
            INSERT INTO fee_structures (fee_category_id, class_id, academic_year_id, amount, late_fee_amount, discount_allowed, due_day, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        foreach ($classes as $branchId => $grades) {
            if (!isset($feeCategories[$branchId])) continue;
            
            foreach ($grades as $grade => $classId) {
                if (!isset($academicYears[$branchId])) continue;
                
                foreach ($academicYears[$branchId] as $year => $academicYearId) {
                    $catIndex = 0;
                    foreach ($feeCategories[$branchId] as $categoryId) {
                        // Different amounts based on category and grade
                        $baseAmount = $this->getBaseAmount($catIndex, $grade);
                        $amount = $baseAmount + ($grade * 100);
                        
                        $stmt->execute([
                            $categoryId,
                            $classId,
                            $academicYearId,
                            $amount,
                            $amount * 0.1, // 10% late fee
                            rand(0, 20), // 0-20% discount allowed
                            10, // Due on 10th of month
                        ]);
                        
                        $feeStructId = (int)$this->db->lastInsertId();
                        $this->feeStructures[$categoryId][$classId][$academicYearId] = $feeStructId;
                        
                        $catIndex++;
                    }
                }
            }
        }
        
        $this->db->commit();
        
        return $this->feeStructures;
    }
    
    private function getBaseAmount(int $categoryIndex, int $grade): float
    {
        // Base amounts for different fee categories
        $baseAmounts = [
            0 => 500,  // Tuition Fee
            1 => 2000, // Admission Fee
            2 => 300,  // Library Fee
            3 => 400,  // Computer Lab Fee
            4 => 200,  // Sports Fee
            5 => 600,  // Examination Fee
            6 => 800,  // Transport Fee
            7 => 1500, // Hostel Fee
        ];
        
        return $baseAmounts[$categoryIndex] ?? 500;
    }
    
    public function getFeeStructures(): array
    {
        return $this->feeStructures;
    }
}