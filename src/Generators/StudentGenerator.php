<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class StudentGenerator
{
    private PDO $db;
    private UserGenerator $userGenerator;
    private array $studentIds = [];
    private int $studentCounter = 0;
    
    public function __construct(UserGenerator $userGenerator)
    {
        $this->db = Database::getInstance()->getConnection();
        $this->userGenerator = $userGenerator;
    }
    
    public function generate(int $branchId, int $studentRoleId, int $classId, int $sectionId, int $year): int
    {
        $this->studentCounter++;
        $gender = rand(0, 1) ? 'male' : 'female';
        $userId = $this->userGenerator->generateUser($branchId, $studentRoleId, $gender);
        $studentCode = 'STD' . sprintf('%08d', $this->studentCounter);
        $admissionNumber = 'ADM' . sprintf('%08d', $this->studentCounter);
        
        $stmt = $this->db->prepare("
            INSERT INTO students (user_id, student_code, admission_number, branch_id, current_class_id, current_section_id, admission_date, admission_type, blood_group, emergency_contact_name, emergency_contact_phone, emergency_contact_relation, transportation_required, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        $bloodGroups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        $admissionDate = "{$year}-03-" . sprintf('%02d', rand(1, 28));
        $relations = ['father', 'mother', 'guardian'];
        
        $stmt->execute([
            $userId,
            $studentCode,
            $admissionNumber,
            $branchId,
            $classId,
            $sectionId,
            $admissionDate,
            rand(0, 1) ? 'new' : 'transfer',
            $bloodGroups[array_rand($bloodGroups)],
            'Emergency Contact',
            '+93' . sprintf('%09d', rand(700000000, 799999999)),
            $relations[array_rand($relations)],
            rand(0, 1)
        ]);
        
        $studentId = (int)$this->db->lastInsertId();
        $this->studentIds[] = $studentId;
        
        return $studentId;
    }
    
    public function getStudents(): array
    {
        return $this->studentIds;
    }
}