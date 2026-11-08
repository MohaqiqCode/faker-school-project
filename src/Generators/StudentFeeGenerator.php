<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class StudentFeeGenerator
{
    private PDO $db;
    private array $studentFees = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $allStudents, array $feeStructures, array $semesters): array
    {
        // if ($this->db->inTransaction()) {
        //     $this->db->rollBack();
        // }
        $this->db->beginTransaction();
        
        $stmt = $this->db->prepare("
            INSERT INTO student_fees (student_id, fee_structure_id, semester_id, total_amount, discount_amount, net_payable, due_date, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        $feeCount = 0;
        $batchSize = 500;
        
        foreach ($allStudents as $year => $students) {
            foreach ($students as $studentData) {
                // Fix: Use 'student_id' key instead of 'id'
                if (!isset($studentData['student_id'])) {
                    continue; // Skip if no student_id
                }
                
                $studentId = $studentData['student_id'];
                $classId = $studentData['class_id'] ?? null;
                
                if (!$classId) {
                    continue; // Skip if no class_id
                }
                
                // Generate fees for each fee structure applicable
                foreach ($feeStructures as $categoryId => $classStructures) {
                    if (isset($classStructures[$classId])) {
                        foreach ($classStructures[$classId] as $academicYearId => $feeStructureId) {
                            // Get semester for this academic year
                            $semesterId = null;
                            if (isset($semesters[$academicYearId][1])) {
                                $semesterId = $semesters[$academicYearId][1];
                            }
                            
                            $totalAmount = $this->getTotalAmount($categoryId, $classId);
                            $discountPercent = rand(0, 15);
                            $discountAmount = $totalAmount * ($discountPercent / 100);
                            $netPayable = $totalAmount - $discountAmount;
                            
                            $dueDate = "{$year}-" . sprintf('%02d-10', rand(3, 12));
                            
                            $stmt->execute([
                                $studentId,
                                $feeStructureId,
                                $semesterId,
                                $totalAmount,
                                $discountAmount,
                                $netPayable,
                                $dueDate
                            ]);
                            
                            $studentFeeId = (int)$this->db->lastInsertId();
                            $this->studentFees[] = $studentFeeId;
                            $feeCount++;
                            
                            if ($feeCount % $batchSize === 0) {
                                $this->db->commit();
                                $this->db->beginTransaction();
                                echo "  Generated {$feeCount} student fees...\n";
                            }
                        }
                    }
                }
            }
        }
        
        $this->db->commit();
        echo "Total student fees generated: {$feeCount}\n";
        
        return $this->studentFees;
    }
    
    // Remove or update this method since we're now passing data directly
    private function getStudentInfo(int $studentId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT current_class_id, branch_id 
            FROM students 
            WHERE id = ? AND is_active = 1
        ");
        $stmt->execute([$studentId]);
        return $stmt->fetch() ?: null;
    }
    
    private function getTotalAmount(int $categoryId, int $classId): float
    {
        // Get amount from fee structure
        $stmt = $this->db->prepare("
            SELECT amount FROM fee_structures 
            WHERE fee_category_id = ? AND class_id = ?
            LIMIT 1
        ");
        $stmt->execute([$categoryId, $classId]);
        $result = $stmt->fetch();
        
        return $result ? (float)$result['amount'] : 500.00;
    }
    
    public function getStudentFees(): array
    {
        return $this->studentFees;
    }
}