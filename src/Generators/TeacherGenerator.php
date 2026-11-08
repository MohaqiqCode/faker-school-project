<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class TeacherGenerator
{
    private PDO $db;
    private UserGenerator $userGenerator;
    private array $teacherIds = [];
    private int $teacherCounter = 0;
    
    public function __construct(UserGenerator $userGenerator)
    {
        $this->db = Database::getInstance()->getConnection();
        $this->userGenerator = $userGenerator;
    }
    
    public function generate(int $branchId, int $teacherRoleId, int $year, int $count, array $deptIds): array
    {
        $teacherIds = [];
        
        $qualifications = ['B.Ed', 'M.Ed', 'BA in Education', 'MA in Literature', 'BSc Mathematics', 'MSc Physics', 'PhD'];
        $specializations = ['Language Teaching', 'Mathematics Education', 'Science Education', 'Islamic Studies', 'Computer Science'];
        $contractTypes = ['permanent', 'contract', 'probation'];
        
        for ($i = 0; $i < $count; $i++) {
            $this->teacherCounter++;
            $userId = $this->userGenerator->generateUser($branchId, $teacherRoleId, rand(0, 1) ? 'male' : 'female');
            $teacherCode = 'TCH' . sprintf('%06d', $this->teacherCounter);
            $employeeId = 'EMP' . sprintf('%08d', $this->teacherCounter);
            $deptId = $deptIds[array_rand($deptIds)];
            
            $stmt = $this->db->prepare("
                INSERT INTO teachers (user_id, teacher_code, employee_id, department_id, qualification, specialization, teaching_experience_years, joining_date, contract_type, salary_grade, bank_name, bank_account_number)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $joiningYear = max(2016, $year - rand(5, 10));
            
            $stmt->execute([
                $userId,
                $teacherCode,
                $employeeId,
                $deptId,
                $qualifications[array_rand($qualifications)],
                $specializations[array_rand($specializations)],
                rand(2, 25),
                "{$joiningYear}-" . sprintf('%02d-%02d', rand(1, 12), rand(1, 28)),
                $contractTypes[array_rand($contractTypes)],
                'GRADE_' . rand(1, 5),
                'Afghanistan International Bank',
                sprintf('%016d', rand(1000000000000000, 9999999999999999))
            ]);
            
            $teacherId = (int)$this->db->lastInsertId();
            $this->teacherIds[] = $teacherId;
            $teacherIds[] = $teacherId;
        }
        
        return $teacherIds;
    }
    
    public function getTeachers(): array
    {
        return $this->teacherIds;
    }
}