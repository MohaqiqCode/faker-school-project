<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class AcademicYearGenerator
{
    private PDO $db;
    private array $academicYears = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $branchIds): array
    {
        $this->db->beginTransaction();
        
        $stmt = $this->db->prepare("
            INSERT INTO academic_years (branch_id, year_name, start_date, end_date, is_current, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($branchIds as $branchId) {
            for ($year = 2016; $year <= 2026; $year++) {
                $yearName = "Academic Year {$year}-" . ($year + 1);
                $startDate = "{$year}-03-21";
                $endDate = ($year + 1) . "-03-20";
                $isCurrent = ($year === 2026) ? 1 : 0;
                $status = ($year >= 2026) ? 'active' : (($year >= 2025) ? 'inactive' : 'archived');
                
                $stmt->execute([
                    $branchId,
                    $yearName,
                    $startDate,
                    $endDate,
                    $isCurrent,
                    $status
                ]);
                
                $ayId = (int)$this->db->lastInsertId();
                $this->academicYears[$branchId][$year] = $ayId;
            }
        }
        
        $this->db->commit();
        
        return $this->academicYears;
    }
    
    public function getAcademicYears(): array
    {
        return $this->academicYears;
    }
}