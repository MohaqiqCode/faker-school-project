<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class SubjectGenerator
{
    private PDO $db;
    private array $subjects = [];
    
    public const CURRICULUM = [
        1 => ['Dari', 'Mathematics', 'Islamic Education', 'Art', 'Handwriting'],
        2 => ['Dari', 'Mathematics', 'Islamic Education', 'Art', 'Handwriting'],
        3 => ['Dari', 'Mathematics', 'Islamic Education', 'Science', 'Art'],
        4 => ['Dari', 'Mathematics', 'Islamic Education', 'Science', 'Social Studies', 'Art'],
        5 => ['Dari', 'Mathematics', 'Islamic Education', 'Science', 'Social Studies', 'Pashto', 'Art'],
        6 => ['Dari', 'Mathematics', 'Islamic Education', 'Science', 'Social Studies', 'Pashto', 'English'],
        7 => ['Dari', 'Pashto', 'English', 'Mathematics', 'General Science', 'Islamic Education', 'History', 'Geography', 'Computer'],
        8 => ['Dari', 'Pashto', 'English', 'Mathematics', 'Physics', 'Chemistry', 'Biology', 'Islamic Education', 'History', 'Geography', 'Computer'],
        9 => ['Dari', 'Pashto', 'English', 'Mathematics', 'Physics', 'Chemistry', 'Biology', 'Islamic Education', 'History', 'Geography', 'Computer'],
        10 => ['Dari', 'Pashto', 'English', 'Mathematics', 'Physics', 'Chemistry', 'Biology', 'Islamic Education', 'History', 'Geography', 'Computer'],
        11 => ['Dari', 'Pashto', 'English', 'Mathematics', 'Physics', 'Chemistry', 'Biology', 'Islamic Education', 'History', 'Geography', 'Computer'],
        12 => ['Dari', 'Pashto', 'English', 'Mathematics', 'Physics', 'Chemistry', 'Biology', 'Islamic Education', 'History', 'Geography', 'Computer'],
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $departmentIds): array
    {
//         if ($this->db->inTransaction()) {
//     $this->db->rollBack();
// }
        $this->db->beginTransaction();
        
        $stmt = $this->db->prepare("
            INSERT INTO subjects (department_id, subject_code, subject_name, description, credit_hours, is_core, is_active)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        
        $deptMap = $this->getDepartmentSubjectMapping();
        $subjectCounter = 0;
        
        foreach ($departmentIds as $branchId => $depts) {
            foreach (self::CURRICULUM as $grade => $subjects) {
                foreach ($subjects as $subjectName) {
                    $deptType = $deptMap[$subjectName] ?? 0;
                    if (!isset($depts[$deptType])) {
                        continue;
                    }
                    
                    $subjectCounter++;
                    $deptId = $depts[$deptType];
                    $subjectCode = 'SUB' . sprintf('%05d', $subjectCounter);
                    
                    $key = "{$branchId}_{$subjectName}";
                    
                    if (!isset($this->subjects[$key])) {
                        $stmt->execute([
                            $deptId,
                            $subjectCode,
                            $subjectName,
                            "{$subjectName} - Grade {$grade}",
                            $this->getCreditHours($grade, $subjectName),
                            $this->isCore($subjectName)
                        ]);
                        
                        $this->subjects[$key] = (int)$this->db->lastInsertId();
                    }
                }
            }
        }
        
        $this->db->commit();
        
        return $this->subjects;
    }
    
    private function getDepartmentSubjectMapping(): array
    {
        return [
            'Dari' => 2, 'Pashto' => 2, 'English' => 2,
            'Mathematics' => 1, 'Physics' => 0, 'Chemistry' => 0, 'Biology' => 0,
            'Science' => 0, 'General Science' => 0,
            'Islamic Education' => 4, 'History' => 3, 'Geography' => 3,
            'Social Studies' => 3, 'Computer' => 5,
            'Art' => 6, 'Handwriting' => 6
        ];
    }
    
    private function getCreditHours(int $grade, string $subject): float
    {
        if (in_array($subject, ['Mathematics', 'Physics', 'Chemistry', 'Biology'])) {
            return ($grade >= 8) ? 4.0 : 3.0;
        }
        if (in_array($subject, ['Dari', 'Pashto', 'English'])) {
            return 3.5;
        }
        return 2.0;
    }
    
    private function isCore(string $subject): int
    {
        $coreSubjects = ['Dari', 'Mathematics', 'Islamic Education', 'English'];
        return in_array($subject, $coreSubjects) ? 1 : 0;
    }
    
    public function getSubjects(): array
    {
        return $this->subjects;
    }
}