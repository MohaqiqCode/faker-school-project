<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class ExamGenerator
{
    private PDO $db;
    private array $exams = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $examTypes, array $academicYears, array $semesters, array $classes, array $subjects, array $allTeachers): array
    {
//         if ($this->db->inTransaction()) {
//     $this->db->rollBack();
// }
        $this->db->beginTransaction();
        
        $stmt = $this->db->prepare("
            INSERT INTO exams (exam_type_id, academic_year_id, semester_id, class_id, subject_id, exam_date, start_time, end_time, total_marks, passing_marks, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?)
        ");
        
        $examCounter = 0;
        $batchSize = 500;
        
        foreach ($classes as $branchId => $grades) {
            foreach ($grades as $grade => $classId) {
                if (!isset($academicYears[$branchId])) continue;
                
                foreach ($academicYears[$branchId] as $year => $academicYearId) {
                    if (!isset($semesters[$academicYearId])) continue;
                    
                    $curriculumSubjects = SubjectGenerator::CURRICULUM[$grade] ?? [];
                    
                    foreach ($curriculumSubjects as $subjectName) {
                        $subjectKey = "{$branchId}_{$subjectName}";
                        if (!isset($subjects[$subjectKey])) continue;
                        
                        $subjectId = $subjects[$subjectKey];
                        
                        // Create Unit Tests (4 per year)
                        for ($i = 1; $i <= 4; $i++) {
                            $examDate = $this->generateExamDate($year, $i);
                            $semesterId = $i <= 2 ? $semesters[$academicYearId][1] : $semesters[$academicYearId][2];
                            
                            $teacherId = null;
                            if (isset($allTeachers[$branchId][$year]) && !empty($allTeachers[$branchId][$year])) {
                                $teacherId = $allTeachers[$branchId][$year][array_rand($allTeachers[$branchId][$year])];
                            }
                            
                            $stmt->execute([
                                $examTypes['unit_test'],
                                $academicYearId,
                                $semesterId,
                                $classId,
                                $subjectId,
                                $examDate,
                                '09:00:00',
                                '10:30:00',
                                50.00,
                                20.00,
                                $teacherId
                            ]);
                            
                            $this->exams[] = (int)$this->db->lastInsertId();
                            $examCounter++;
                        }
                        
                        // Create Midterm Exam
                        $midtermDate = "{$year}-06-15";
                        $semesterId = $semesters[$academicYearId][1];
                        
                        $teacherId = null;
                        if (isset($allTeachers[$branchId][$year]) && !empty($allTeachers[$branchId][$year])) {
                            $teacherId = $allTeachers[$branchId][$year][array_rand($allTeachers[$branchId][$year])];
                        }
                        
                        $stmt->execute([
                            $examTypes['mid_term'],
                            $academicYearId,
                            $semesterId,
                            $classId,
                            $subjectId,
                            $midtermDate,
                            '09:00:00',
                            '12:00:00',
                            100.00,
                            40.00,
                            $teacherId
                        ]);
                        
                        $this->exams[] = (int)$this->db->lastInsertId();
                        $examCounter++;
                        
                        // Create Final Exam
                        $finalDate = "{$year}-12-10";
                        $semesterId = $semesters[$academicYearId][2];
                        
                        $teacherId = null;
                        if (isset($allTeachers[$branchId][$year]) && !empty($allTeachers[$branchId][$year])) {
                            $teacherId = $allTeachers[$branchId][$year][array_rand($allTeachers[$branchId][$year])];
                        }
                        
                        $stmt->execute([
                            $examTypes['final'],
                            $academicYearId,
                            $semesterId,
                            $classId,
                            $subjectId,
                            $finalDate,
                            '09:00:00',
                            '12:00:00',
                            100.00,
                            40.00,
                            $teacherId
                        ]);
                        
                        $this->exams[] = (int)$this->db->lastInsertId();
                        $examCounter++;
                        
                        if ($examCounter % $batchSize === 0) {
                            $this->db->commit();
                            $this->db->beginTransaction();
                            echo "  Generated {$examCounter} exams...\n";
                        }
                    }
                }
            }
        }
        
        $this->db->commit();
        echo "Total exams generated: {$examCounter}\n";
        
        return $this->exams;
    }
    
    private function generateExamDate(int $year, int $unitNumber): string
    {
        $month = 3 + ($unitNumber - 1) * 2;
        return sprintf('%d-%02d-%02d', $year, $month, rand(15, 28));
    }
    
    public function getExams(): array
    {
        return $this->exams;
    }
}