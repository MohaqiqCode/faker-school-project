<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class StudentTransportGenerator
{
    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $allStudents, array $routes): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO student_transport (student_id, route_id, stop_id, allocation_date, end_date, monthly_fee, is_active)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        
//         if ($this->db->inTransaction()) {
//     $this->db->rollBack();
// }
        $this->db->beginTransaction();
        $allocationCount = 0;
        $batchSize = 500;
        
        // Get students who require transportation
        $transportStudents = $this->getTransportStudents();
        
        foreach ($allStudents as $year => $students) {
            foreach ($students as $studentId) {
                if (!in_array($studentId, $transportStudents)) continue;
                
                // Assign to a random route from the student's branch
                $studentBranch = $this->getStudentBranch($studentId);
                if (!$studentBranch || !isset($routes[$studentBranch])) continue;
                
                $branchRoutes = $routes[$studentBranch];
                $routeId = $branchRoutes[array_rand($branchRoutes)];
                
                // Get a random stop from this route
                $stopId = $this->getRandomStopId($routeId);
                
                $allocationDate = "{$year}-03-" . sprintf('%02d', rand(1, 28));
                $endDate = ($year + 1) . "-03-20";
                
                $stmt->execute([
                    $studentId,
                    $routeId,
                    $stopId,
                    $allocationDate,
                    $endDate,
                    rand(500, 2000)
                ]);
                
                $allocationCount++;
                
                if ($allocationCount % $batchSize === 0) {
                    $this->db->commit();
                    $this->db->beginTransaction();
                    echo "  Generated {$allocationCount} transport allocations...\n";
                }
            }
        }
        
        $this->db->commit();
        echo "Total transport allocations generated: {$allocationCount}\n";
    }
    
    private function getTransportStudents(): array
    {
        $stmt = $this->db->query("SELECT id FROM students WHERE transportation_required = 1 AND is_active = 1");
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }
    
    private function getStudentBranch(int $studentId): ?int
    {
        $stmt = $this->db->prepare("SELECT branch_id FROM students WHERE id = ?");
        $stmt->execute([$studentId]);
        $result = $stmt->fetch();
        return $result ? (int)$result['branch_id'] : null;
    }
    
    private function getRandomStopId(int $routeId): ?int
    {
        $stmt = $this->db->prepare("SELECT id FROM route_stops WHERE route_id = ? ORDER BY RAND() LIMIT 1");
        $stmt->execute([$routeId]);
        $result = $stmt->fetch();
        return $result ? (int)$result['id'] : null;
    }
}