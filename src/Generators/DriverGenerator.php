<?php

namespace App\Generators;

use App\Core\Database;
use PDO;

class DriverGenerator
{
    private PDO $db;
    private array $drivers = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generate(array $allEmployees, array $vehicles): array
    {
        $this->db->beginTransaction();
        
        $stmt = $this->db->prepare("
            INSERT INTO drivers (employee_id, license_number, license_expiry, experience_years, assigned_vehicle_id, is_active)
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        
        // Filter employees who are drivers
        $driverEmployees = $this->getDriverEmployees();
        
        foreach ($allEmployees as $branchId => $years) {
            if (!isset($vehicles[$branchId])) continue;
            
            $branchVehicles = $vehicles[$branchId];
            $vehicleIndex = 0;
            
            foreach ($years as $year => $employees) {
                foreach ($employees as $employeeId) {
                    if (!in_array($employeeId, $driverEmployees)) continue;
                    
                    $assignedVehicleId = $branchVehicles[$vehicleIndex % count($branchVehicles)] ?? null;
                    
                    $stmt->execute([
                        $employeeId,
                        'DL-' . sprintf('%08d', rand(10000000, 99999999)),
                        sprintf('%d-12-31', rand(2025, 2030)),
                        rand(5, 25),
                        $assignedVehicleId
                    ]);
                    
                    $driverId = (int)$this->db->lastInsertId();
                    $this->drivers[$branchId][] = $driverId;
                    
                    $vehicleIndex++;
                }
            }
        }
        
        $this->db->commit();
        
        return $this->drivers;
    }
    
    private function getDriverEmployees(): array
    {
        $stmt = $this->db->query("SELECT id FROM employees WHERE designation = 'Driver'");
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }
    
    public function getDrivers(): array
    {
        return $this->drivers;
    }
}