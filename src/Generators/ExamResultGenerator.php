<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class ExamResultGenerator
{
    private PDO $db;
    
    private const GRADE_DISTRIBUTION = [
        'excellent' => 20, // 85-100
        'good' => 30,      // 70-84
        'average' => 30,   // 50-69
        'weak' => 15,      // 35-49
        'fail' => 5        // 0-34
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $exams, array $allStudents): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO exam_results (exam_id, student_id, marks_obtained, practical_marks, internal_assessment, grade, grade_point, remarks, is_absent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE marks_obtained = VALUES(marks_obtained)
        ");
        
        $this->db->beginTransaction();
        $resultCount = 0;
        $batchSize = 1000;
        
        foreach ($exams as $examId) {
            $examInfo = $this->getExamInfo($examId);
            if (!$examInfo) continue;
            
            // Get students from the $allStudents array for the specific academic year
            $students = [];
            
            // Flatten the $allStudents array to get all students by year
            if (isset($examInfo['academic_year_id'])) {
                // We need to map academic_year_id to the year key in $allStudents
                $yearKey = $this->getYearFromAcademicYearId($examInfo['academic_year_id']);
                
                if ($yearKey && isset($allStudents[$yearKey])) {
                    // Get students in this class for this academic year
                    $students = $this->getStudentsInClass(
                        $allStudents[$yearKey], 
                        $examInfo['class_id']
                    );
                }
            }
            
            // If no students found from array, try database query as fallback
            if (empty($students)) {
                $students = $this->getStudentsForExam($examInfo['class_id'], $examInfo['academic_year_id']);
            }
            
            if (empty($students)) {
                echo "  Warning: No students found for exam {$examId} (class {$examInfo['class_id']}, year {$examInfo['academic_year_id']})\n";
                continue;
            }
            
            foreach ($students as $studentId) {
                $marks = $this->generateMarks();
                $totalMarks = $examInfo['total_marks'];
                
                $marksObtained = ($marks / 100) * $totalMarks;
                $grade = $this->calculateGrade($marks);
                $gradePoint = $this->calculateGradePoint($marks);
                
                $stmt->execute([
                    $examId,
                    $studentId,
                    round($marksObtained, 2),
                    rand(0, 1) ? round($marksObtained * 0.2, 2) : 0,
                    rand(0, 1) ? round($marksObtained * 0.1, 2) : 0,
                    $grade,
                    $gradePoint,
                    "Performance: {$grade}",
                    rand(1, 100) <= 2 ? 1 : 0 // 2% absent rate
                ]);
                
                $resultCount++;
                
                if ($resultCount % $batchSize === 0) {
                    $this->db->commit();
                    $this->db->beginTransaction();
                    echo "  Generated {$resultCount} exam results...\n";
                }
            }
        }
        
        $this->db->commit();
        echo "Total exam results generated: {$resultCount}\n";
    }
    
    private function getStudentsInClass(array $studentIds, int $classId): array
    {
        if (empty($studentIds)) return [];
        
        // Create placeholders for the IN clause
        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
        
        $stmt = $this->db->prepare("
            SELECT id 
            FROM students 
            WHERE id IN ({$placeholders}) 
            AND current_class_id = ? 
            AND is_active = 1
        ");
        
        $params = array_merge($studentIds, [$classId]);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
   private function getYearFromAcademicYearId(int $academicYearId): ?int
{
    $stmt = $this->db->prepare("SELECT year_name FROM academic_years WHERE id = ?");
    $stmt->execute([$academicYearId]);
    $result = $stmt->fetch();
    
    if ($result) {
        // Extract year from "Academic Year 2016-2017" format
        // Gets the first 4-digit number after "Academic Year "
        if (preg_match('/(\d{4})/', $result['year_name'], $matches)) {
            return (int)$matches[1];
        }
    }
    
    return null;
}
    
    private function generateMarks(): float
    {
        $rand = rand(1, 100);
        $cumulative = 0;
        
        foreach (self::GRADE_DISTRIBUTION as $grade => $percentage) {
            $cumulative += $percentage;
            if ($rand <= $cumulative) {
                switch ($grade) {
                    case 'excellent':
                        return rand(85, 100);
                    case 'good':
                        return rand(70, 84);
                    case 'average':
                        return rand(50, 69);
                    case 'weak':
                        return rand(35, 49);
                    case 'fail':
                        return rand(0, 34);
                }
            }
        }
        
        return rand(50, 69); // Default to average
    }
    
    private function calculateGrade(float $percentage): string
    {
        if ($percentage >= 85) return 'A';
        if ($percentage >= 70) return 'B';
        if ($percentage >= 50) return 'C';
        if ($percentage >= 35) return 'D';
        return 'F';
    }
    
    private function calculateGradePoint(float $percentage): float
    {
        if ($percentage >= 85) return 4.0;
        if ($percentage >= 70) return 3.0;
        if ($percentage >= 50) return 2.0;
        if ($percentage >= 35) return 1.0;
        return 0.0;
    }
    
    private function getExamInfo(int $examId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT class_id, academic_year_id, total_marks 
            FROM exams 
            WHERE id = ?
        ");
        $stmt->execute([$examId]);
        return $stmt->fetch() ?: null;
    }
    
  private function getStudentsForExam(int $classId, int $academicYearId): array
{
    $stmt = $this->db->prepare("
        SELECT DISTINCT s.id 
        FROM students s
        WHERE s.current_class_id = ? 
        AND s.is_active = 1
        AND s.admission_date <= (
            SELECT DATE(CONCAT(SUBSTRING_INDEX(SUBSTRING_INDEX(year_name, ' ', -1), '-', 1), '-12-31'))
            FROM academic_years 
            WHERE id = ?
        )
    ");
    $stmt->execute([$classId, $academicYearId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
}