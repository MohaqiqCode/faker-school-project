<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class PayrollGenerator
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $allTeachers, array $allEmployees, array $salaryStructures): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO payroll (user_id, salary_structure_id, pay_period_start, pay_period_end, basic_salary, allowances, deductions, payment_date, payment_method, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
//         if ($this->db->inTransaction()) {
//     $this->db->rollBack();
// }
        $this->db->beginTransaction();
        $payrollCount = 0;
        $batchSize = 500;
        
        // Process teacher payroll
        foreach ($allTeachers as $branchId => $years) {
            foreach ($years as $year => $teachers) {
                foreach ($teachers as $teacherId) {
                    $userId = $this->getUserIdFromTeacher($teacherId);
                    if (!$userId) continue;
                    
                    $salaryStructId = $this->getSalaryStructureId('teacher');
                    
                    // Monthly payroll for each year
                    for ($month = 1; $month <= 12; $month++) {
                        $payPeriodStart = sprintf('%d-%02d-01', $year, $month);
                        $payPeriodEnd = sprintf('%d-%02d-%02d', $year, $month, cal_days_in_month(CAL_GREGORIAN, $month, $year));
                        
                        $basicSalary = rand(8000, 60000);
                        $allowances = $basicSalary * 0.5; // 50% allowances
                        $deductions = $basicSalary * 0.05; // 5% deductions
                        
                        $stmt->execute([
                            $userId,
                            $salaryStructId,
                            $payPeriodStart,
                            $payPeriodEnd,
                            $basicSalary,
                            $allowances,
                            $deductions,
                            sprintf('%d-%02d-28', $year, $month),
                            'bank_transfer',
                            rand(0, 1) ? 'paid' : 'processed'
                        ]);
                        
                        $payrollCount++;
                        
                        if ($payrollCount % $batchSize === 0) {
                            $this->db->commit();
                            $this->db->beginTransaction();
                            echo "  Generated {$payrollCount} payroll records...\n";
                        }
                    }
                }
            }
        }
        
        // Process employee payroll
        foreach ($allEmployees as $branchId => $years) {
            foreach ($years as $year => $employees) {
                foreach ($employees as $employeeId) {
                    $userId = $this->getUserIdFromEmployee($employeeId);
                    if (!$userId) continue;
                    
                    $salaryStructId = $this->getSalaryStructureId('staff');
                    
                    for ($month = 1; $month <= 12; $month++) {
                        $payPeriodStart = sprintf('%d-%02d-01', $year, $month);
                        $payPeriodEnd = sprintf('%d-%02d-%02d', $year, $month, cal_days_in_month(CAL_GREGORIAN, $month, $year));
                        
                        $basicSalary = rand(6000, 35000);
                        $allowances = $basicSalary * 0.4;
                        $deductions = $basicSalary * 0.04;
                        
                        $stmt->execute([
                            $userId,
                            $salaryStructId,
                            $payPeriodStart,
                            $payPeriodEnd,
                            $basicSalary,
                            $allowances,
                            $deductions,
                            sprintf('%d-%02d-28', $year, $month),
                            'bank_transfer',
                            rand(0, 1) ? 'paid' : 'processed'
                        ]);
                        
                        $payrollCount++;
                        
                        if ($payrollCount % $batchSize === 0) {
                            $this->db->commit();
                            $this->db->beginTransaction();
                        }
                    }
                }
            }
        }
        
        $this->db->commit();
        echo "Total payroll records generated: {$payrollCount}\n";
    }
    
    private function getUserIdFromTeacher(int $teacherId): ?int
    {
        $stmt = $this->db->prepare("SELECT user_id FROM teachers WHERE id = ?");
        $stmt->execute([$teacherId]);
        $result = $stmt->fetch();
        return $result ? (int)$result['user_id'] : null;
    }
    
    private function getUserIdFromEmployee(int $employeeId): ?int
    {
        $stmt = $this->db->prepare("SELECT user_id FROM employees WHERE id = ?");
        $stmt->execute([$employeeId]);
        $result = $stmt->fetch();
        return $result ? (int)$result['user_id'] : null;
    }
    
    private function getSalaryStructureId(string $type): int
    {
        $grade = 'GRADE_' . rand(1, 5);
        $key = "{$type}_{$grade}";
        
        $stmt = $this->db->prepare("SELECT id FROM salary_structures WHERE employee_type = ? AND salary_grade = ? LIMIT 1");
        $stmt->execute([$type, $grade]);
        $result = $stmt->fetch();
        
        return $result ? (int)$result['id'] : 1;
    }
}