<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class SemesterGenerator
{
    private PDO $db;
    private array $semesters = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $academicYearIds): array
    {
//         if ($this->db->inTransaction()) {
//     $this->db->rollBack();
// }
        $this->db->beginTransaction();
        
        $stmt = $this->db->prepare("
            INSERT INTO semesters (academic_year_id, semester_name, semester_number, start_date, end_date, is_current)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($academicYearIds as $branchId => $years) {
            foreach ($years as $year => $academicYearId) {
                // Semester 1: March to September
                $sem1Start = "{$year}-03-21";
                $sem1End = "{$year}-09-20";
                $isCurrent = ($year === 2026) ? 1 : 0;
                
                $stmt->execute([
                    $academicYearId,
                    "Semester 1 - {$year}",
                    1,
                    $sem1Start,
                    $sem1End,
                    $isCurrent
                ]);
                
                $semId = (int)$this->db->lastInsertId();
                $this->semesters[$academicYearId][1] = $semId;
                
                // Semester 2: September to March
                $sem2Start = "{$year}-09-21";
                $sem2End = ($year + 1) . "-03-20";
                
                $stmt->execute([
                    $academicYearId,
                    "Semester 2 - {$year}",
                    2,
                    $sem2Start,
                    $sem2End,
                    0
                ]);
                
                $semId = (int)$this->db->lastInsertId();
                $this->semesters[$academicYearId][2] = $semId;
            }
        }
        
        $this->db->commit();
        
        return $this->semesters;
    }
    
    public function getSemesters(): array
    {
        return $this->semesters;
    }
}